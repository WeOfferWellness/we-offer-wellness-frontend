<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LocationDiscoveryService
{
    private const BAD_LOCATION_WORDS = [
        'location', 'locations', 'unknown', 'none', 'n/a', 'na', 'tbc', 'tbd',
        'remote', 'virtual', 'various', 'various locations', 'multiple',
        'multiple locations', 'anywhere',
    ];

    public function __construct(
        private BackendOfferingsClient $offerings,
        private BehaviourDemandClient $demand,
    ) {}

    public function build(array $context, array $locationCatalog, string $scope = 'root'): array
    {
        $insights = $this->demand->insights();
        // Location pages must match against the complete live catalogue. A
        // fixed four-page window can omit valid local offerings when newer
        // listings fill the first 400 results.
        $catalogue = $this->offerings->catalogue(['status' => 'live', 'version' => 'all', 'per_page' => 100], 10);
        $terms = $this->contextTerms($context, $scope);
        $what = $this->normalise((string) ($context['what'] ?? ''));
        $label = trim((string) ($context['place'] ?? $context['label'] ?? '')) ?: 'you';
        $nearby = $catalogue
            ->filter(fn (array $item): bool => $this->isPublicItem($item))
            ->filter(fn (array $item): bool => $terms === [] || $this->matchesTerms($item, $terms, $scope))
            ->sortByDesc(fn (array $item): float => $this->score($item, $insights, $terms, $what))
            ->values();
        $online = $catalogue
            ->filter(fn (array $item): bool => $this->isOnline($item))
            ->sortByDesc(fn (array $item): float => $this->score($item, $insights, []))
            ->values();
        $newNearby = $nearby
            ->sortByDesc(fn (array $item): int => $this->timestamp($item, 'published_at', 'created_at', 'updated_at'))
            ->values();

        return [
            'label' => $label,
            'nearby' => $nearby->take(12)->values(),
            'nearby_count' => $nearby->count(),
            'new_nearby' => $newNearby->take(8)->values(),
            'online' => $online->take(8)->values(),
            'categories' => $this->categories($nearby, $insights),
            'popular_places' => $this->popularPlaces($locationCatalog, $insights, $context, $scope, $catalogue),
            'supply_paths' => $this->supplyPaths($locationCatalog, $catalogue),
            'price_bands' => $this->priceBands($nearby, $insights),
            'demand_available' => ! empty($insights),
        ];
    }

    private function categories(Collection $items, array $insights): Collection
    {
        return $items
            ->map(function (array $item) use ($insights): ?array {
                $name = trim((string) data_get($item, 'category.name', data_get($item, 'category_name', data_get($item, 'category', ''))));
                $slug = Str::slug($name);
                if ($name === '' || $slug === '') {
                    return null;
                }
                $key = $this->normalise($slug);
                return [
                    'name' => $name,
                    'slug' => $slug,
                    'count' => 1,
                    'demand_score' => $this->demandScore($insights, 'categories', $key),
                    'url' => url('/therapies/'.$slug),
                ];
            })
            ->filter()
            ->groupBy('slug')
            ->map(function (Collection $group): array {
                $first = $group->first();
                $first['count'] = $group->count();
                return $first;
            })
            ->sortByDesc(fn (array $item): float => $item['count'] * 100 + $item['demand_score'])
            ->take(8)
            ->values();
    }

    private function popularPlaces(array $catalog, array $insights, array $context, string $scope, Collection $catalogue): Collection
    {
        $demand = collect($insights['locations'] ?? []);
        return collect($catalog['flat'] ?? [])
            ->filter(function (array $item) use ($context, $scope, $catalogue): bool {
                if (($item['online'] ?? false)) return false;
                $country = $this->normalise((string) ($context['country_slug'] ?? $context['country'] ?? ''));
                $county = $this->normalise((string) ($context['county_slug'] ?? $context['county'] ?? ''));
                if ($scope === 'county' && $county !== '') return $this->normalise((string) ($item['county_slug'] ?? $item['county'] ?? '')) === $county && $catalogue->contains(fn (array $offering): bool => $this->matchesTerms($offering, [(string) ($item['title'] ?? '')], 'town'));
                if ($scope === 'town' && $county !== '') return $this->normalise((string) ($item['county_slug'] ?? $item['county'] ?? '')) === $county && $catalogue->contains(fn (array $offering): bool => $this->matchesTerms($offering, [(string) ($item['title'] ?? '')], 'town'));
                if ($scope === 'country' && $country !== '') return $this->normalise((string) ($item['country_slug'] ?? $item['country'] ?? '')) === $country && $catalogue->contains(fn (array $offering): bool => $this->matchesTerms($offering, [(string) ($item['title'] ?? '')], 'town'));
                return $catalogue->contains(fn (array $offering): bool => $this->matchesTerms($offering, [(string) ($item['title'] ?? '')], 'town'));
            })
            ->map(function (array $item) use ($demand, $catalogue): array {
                $name = trim((string) ($item['title'] ?? $item['label'] ?? ''));
                $key = $this->normalise($name);
                $signal = $demand->first(fn (array $row): bool => str_contains($this->normalise((string) ($row['key'] ?? '')), $key) || str_contains($key, $this->normalise((string) ($row['key'] ?? ''))));
                $item['demand_score'] = (float) ($signal['visitors'] ?? 0) * 10 + (float) ($signal['searches'] ?? 0);
                $item['supply_count'] = $catalogue->filter(fn (array $offering): bool => $this->matchesTerms($offering, [$name], 'town'))->count();
                return $item;
            })
            ->sortByDesc(fn (array $item): float => $this->placeScore($item))
            ->take(8)
            ->values();
    }

    private function supplyPaths(array $catalog, Collection $catalogue): array
    {
        return collect($catalog['flat'] ?? [])
            ->filter(fn (array $item): bool => !($item['online'] ?? false) && ($item['path'] ?? '') !== '' && $catalogue->contains(fn (array $offering): bool => $this->matchesTerms($offering, [(string) ($item['title'] ?? '')], 'town')))
            ->pluck('path')->filter()->unique()->values()->all();
    }

    private function priceBands(Collection $items, array $insights): Collection
    {
        $bands = [
            ['key' => 'under_30', 'label' => 'Under £30', 'min' => 0, 'max' => 30],
            ['key' => '30_59', 'label' => '£30–£59', 'min' => 30, 'max' => 60],
            ['key' => '60_99', 'label' => '£60–£99', 'min' => 60, 'max' => 100],
            ['key' => '100_plus', 'label' => '£100+', 'min' => 100, 'max' => null],
        ];
        return collect($bands)->map(function (array $band) use ($items, $insights): array {
            $count = $items->filter(function (array $item) use ($band): bool {
                $price = (float) data_get($item, 'price', data_get($item, 'price_min', 0));
                return $price > 0 && $price >= $band['min'] && ($band['max'] === null || $price < $band['max']);
            })->count();
            $band['count'] = $count;
            $band['demand_score'] = $this->demandScore($insights, 'price_bands', $band['key']);
            return $band;
        })->filter(fn (array $band): bool => $band['count'] > 0)->sortByDesc(fn (array $band): float => $band['demand_score'] + $band['count'])->values();
    }

    private function score(array $item, array $insights, array $terms, string $what = ''): float
    {
        $category = $this->normalise((string) data_get($item, 'category.slug', data_get($item, 'category_name', data_get($item, 'category', ''))));
        $categoryDemand = $this->demandScore($insights, 'categories', $category);
        $formatKey = $this->isOnline($item) ? 'online' : 'in_person';
        $formatDemand = $this->demandScore($insights, 'formats', $formatKey);
        $price = (float) data_get($item, 'price', data_get($item, 'price_min', 0));
        $priceKey = $price < 30 ? 'under_30' : ($price < 60 ? '30_59' : ($price < 100 ? '60_99' : '100_plus'));
        $priceDemand = $this->demandScore($insights, 'price_bands', $priceKey);
        $rating = (float) data_get($item, 'rating', data_get($item, 'reviews_avg_rating', 0));
        $reviews = (float) data_get($item, 'review_count', data_get($item, 'reviews_count', 0));
        $freshness = min(1, max(0, (now()->timestamp - $this->timestamp($item, 'updated_at', 'published_at', 'created_at')) / 31536000));
        $local = $terms === [] ? 0 : ($this->matchesTerms($item, $terms) ? 100 : 0);
        $titleAndCategory = $this->normalise(collect([$item['title'] ?? '', data_get($item, 'category.name', ''), $item['description'] ?? ''])->implode(' '));
        $whatMatch = $what !== '' && str_contains($titleAndCategory, $what) ? 80 : 0;
        return $local + $whatMatch + ($categoryDemand * .05) + ($formatDemand * .025) + ($priceDemand * .015) + ($rating * 4) + log(1 + $reviews) * 2 + (1 - $freshness);
    }

    private function demandScore(array $insights, string $dimension, string $key): float
    {
        $key = $this->normalise($key);
        return collect([
            ['window' => '7d', 'weight' => .5],
            ['window' => '30d', 'weight' => .3],
            ['window' => '90d', 'weight' => .2],
        ])->sum(function (array $window) use ($insights, $dimension, $key): float {
            $row = collect(data_get($insights, 'windows.'.$window['window'].'.'.$dimension, []))->first(fn (array $item): bool => $this->normalise((string) ($item['key'] ?? '')) === $key);
            return (float) ($row['score'] ?? 0) * $window['weight'];
        });
    }

    private function placeScore(array $item): float
    {
        return (float) data_get($item, 'demand_score', 0) + ((int) data_get($item, 'supply_count', 0) * 2);
    }

    private function contextTerms(array $context, string $scope): array
    {
        $values = match ($scope) {
            'town' => [$context['town'] ?? $context['city'] ?? $context['place'] ?? null],
            'county' => [$context['county'] ?? $context['region'] ?? $context['place'] ?? null],
            'country' => [$context['country'] ?? null],
            default => [$context['town'] ?? null, $context['city'] ?? null, $context['county'] ?? null, $context['region'] ?? null, $context['place'] ?? null],
        };
        return collect($values)
            ->map(fn ($value): string => trim((string) $value))->filter()->unique()->values()->all();
    }

    private function matchesTerms(array $item, array $terms, string $scope = 'root'): bool
    {
        $haystack = $this->normalise(collect([
            data_get($item, 'location'), data_get($item, 'location_name'), data_get($item, 'formatted_address'),
            data_get($item, 'address'), data_get($item, 'address_line_1'), data_get($item, 'address_line_2'),
            data_get($item, 'city'), data_get($item, 'county'), data_get($item, 'postcode'),
            data_get($item, 'locations', []), data_get($item, 'vendor.locations', []), data_get($item, 'vendor_name'),
        ])->flatten()->implode(' '));
        if ($scope === 'country' && collect($terms)->contains(fn (string $term): bool => in_array($this->normalise($term), ['united kingdom', 'uk', 'england', 'scotland', 'wales', 'northern ireland'], true))) {
            return true;
        }
        return collect($terms)->contains(fn (string $term): bool => str_contains($haystack, $this->normalise($term)));
    }

    private function isOnline(array $item): bool
    {
        $haystack = strtolower(collect([data_get($item, 'format'), data_get($item, 'online_only'), data_get($item, 'channels', []), data_get($item, 'locations', [])])->flatten()->implode(' '));
        return (bool) data_get($item, 'online_only', false) || str_contains($haystack, 'online');
    }

    private function isPublicItem(array $item): bool
    {
        $locationText = collect([
            data_get($item, 'location'), data_get($item, 'location_name'), data_get($item, 'formatted_address'),
            data_get($item, 'locations', []), data_get($item, 'vendor.locations', []),
        ])->flatten()->implode(' ');
        return ! preg_match('/(?:^|\b)(location|locations|unknown|n\/a|tbc|tbd|various|multiple locations)(?:$|\b)/i', $this->normalise($locationText));
    }

    private function timestamp(array $item, string ...$keys): int
    {
        foreach ($keys as $key) {
            $value = data_get($item, $key);
            if ($value) {
                $time = strtotime((string) $value);
                if ($time !== false) return $time;
            }
        }
        return 0;
    }

    private function normalise(string $value): string
    {
        return trim(Str::lower(Str::squish(preg_replace('/[^a-z0-9]+/i', ' ', $value) ?: '')));
    }
}
