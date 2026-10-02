<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BackendOfferingsClient;
use App\Support\EventListing;
use App\Support\ProductRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HomeRailsController extends Controller
{
    public function __construct(private readonly BackendOfferingsClient $offeringsClient)
    {
    }

    public function index(Request $request)
    {
        $section = Str::lower(trim((string) $request->input('section', '')));

        if ($section === 'matches') {
            $items = $this->matchedCatalogue($request)->take(12)->values();

            return response($this->renderCards($items, true))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'private, max-age=30');
        }

        if ($section === 'latest') {
            return response($this->renderCards($this->catalogue()->sortByDesc(fn (array $item) => $this->timestamp($item))->take(12), true))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=60');
        }

        if ($section === 'gifts') {
            $limit = max(1, min((int) $request->integer('limit', 12), 24));
            $page = max(1, (int) $request->integer('page', 1));
            $catalogue = $this->catalogue(['max_price' => 50], 6)
                ->concat($this->catalogue(['max_price' => 50, 'search' => 'gift'], 2))
                ->unique(fn (array $item) => (string) data_get($item, 'source_type', data_get($item, 'source_version', '')).':'.(string) data_get($item, 'id', ''));
            $explicitGifts = ProductRanking::sortCollection($catalogue->filter(fn (array $item) => $this->isGift($item) && $this->price($item) !== null), 'review_count_desc');
            $physicalGifts = ProductRanking::sortCollection($catalogue->filter(fn (array $item) => ! $this->isGift($item) && $this->isPhysicalProduct($item) && $this->price($item) !== null), 'review_count_desc');
            $giftIdeas = ProductRanking::sortCollection($catalogue->filter(fn (array $item) => ! $this->isGift($item) && ! $this->isPhysicalProduct($item) && $this->price($item) !== null), 'review_count_desc');
            $items = $explicitGifts->concat($physicalGifts)->concat($giftIdeas)->values();
            $offset = ($page - 1) * $limit;

            return response($this->renderCards($items->slice($offset, $limit)->values(), true))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=60')
                ->header('X-Page', (string) $page)
                ->header('X-Page-Size', (string) $limit)
                ->header('X-Has-More', ($offset + $limit) < $items->count() ? '1' : '0')
                ->header('X-Total-Count', (string) $items->count());
        }

        if ($section === 'comfort') {
            $limit = max(1, min((int) $request->integer('limit', 4), 12));
            $page = max(1, (int) $request->integer('page', 1));
            $priceMin = max(0, (float) $request->input('price_min', 0));
            $priceMax = max(1, (float) $request->input('price_max', 50));
            $groupType = Str::lower(trim((string) $request->input('group_type', 'all')));
            $mode = Str::lower(trim((string) $request->input('mode', 'online')));

            $items = $this->catalogue(['max_price' => $priceMax], 6)
                ->filter(function (array $item) use ($priceMin, $priceMax): bool {
                    $price = $this->price($item);

                    return $price !== null && $price > $priceMin && $price <= $priceMax;
                })
                ->filter(fn (array $item) => $this->matchesMode($item, $mode))
                ->filter(fn (array $item) => $this->matchesGroupType($item, $groupType));
            $items = ProductRanking::sortCollection($items)->values();
            $offset = ($page - 1) * $limit;
            $pageItems = $items->slice($offset, $limit)->values();

            return response($this->renderCards($pageItems, true))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'public, max-age=60, s-maxage=300, stale-while-revalidate=60')
                ->header('X-Page', (string) $page)
                ->header('X-Page-Size', (string) $limit)
                ->header('X-Has-More', ($offset + $limit) < $items->count() ? '1' : '0')
                ->header('X-Total-Count', (string) $items->count());
        }

        return response('', 404);
    }

    private function catalogue(array $filters = [], int $maxPages = 2): Collection
    {
        // Homepage rails are shared public content. Do not make their first
        // paint depend on a cold per-visitor ranking request.
        return $this->offeringsClient->catalogue($filters, $maxPages, false)
            ->filter(fn (array $item): bool => $this->isPublicOffering($item))
            ->reject(fn (array $item) => EventListing::isPast($item))
            ->values();
    }

    private function matchedCatalogue(Request $request): Collection
    {
        $catalogue = $this->catalogue([], 6);
        if ($catalogue->isEmpty()) {
            return $catalogue;
        }

        $signals = [
            'type' => $this->csvValues($request->input('type_id', $request->input('type'))),
            'category' => $this->csvValues($request->input('category_id', $request->input('category'))),
            'subcategory' => $this->csvValues($request->input('subcategory_id', $request->input('subcategory'))),
            'need' => $this->csvValues($request->input('need')),
            'marketplace_tag' => $this->csvValues($request->input('marketplace_tag')),
            'occasion' => $this->csvValues($request->input('occasion')),
            'audience' => $this->csvValues($request->input('audience')),
        ];
        $format = Str::lower(trim((string) $request->input('format', '')));
        $minimum = $request->filled('min_price') ? max(0, (float) $request->input('min_price')) : null;
        $maximum = $request->filled('max_price') ? max(0, (float) $request->input('max_price')) : null;

        $eligible = $catalogue->filter(function (array $item) use ($format, $minimum, $maximum): bool {
            if (in_array($format, ['online', 'in-person'], true) && ! $this->matchesMode($item, $format)) {
                return false;
            }
            $price = $this->price($item);
            if ($minimum !== null && ($price === null || $price < $minimum)) {
                return false;
            }
            if ($maximum !== null && ($price === null || $price > $maximum)) {
                return false;
            }

            return true;
        })->values();

        // Format and price are preferences, not dead ends. If that combination
        // has no live supply, continue with the live catalogue and rank by the
        // available taxonomy signals instead of returning an empty page.
        if ($eligible->isEmpty()) {
            $eligible = $catalogue;
        }

        $position = 0;
        $ranked = $eligible->map(function (array $item) use ($signals, &$position): array {
            $item['_wellness_match_score'] = $this->matchScore($item, $signals);
            $item['_wellness_match_position'] = $position++;

            return $item;
        })->sort(function (array $left, array $right): int {
            return [$right['_wellness_match_score'], -$right['_wellness_match_position']]
                <=> [$left['_wellness_match_score'], -$left['_wellness_match_position']];
        })->values();

        // Fill a sparse preference result with other live offerings. This keeps
        // recommendations useful while still placing the strongest matches first.
        return $ranked->concat($catalogue)
            ->unique(fn (array $item) => (string) data_get($item, 'source_type', data_get($item, 'source_version', '')).':'.(string) data_get($item, 'id', ''))
            ->values();
    }

    private function csvValues(mixed $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn (string $item): string => Str::lower(trim($item)))
            ->filter()
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function matchScore(array $item, array $signals): int
    {
        $dimensions = [
            'type' => $this->dimensionValues(data_get($item, 'type')),
            'category' => $this->dimensionValues(data_get($item, 'category')),
            'subcategory' => $this->dimensionValues(data_get($item, 'subcategory')),
            'need' => $this->dimensionValues(data_get($item, 'needs')),
            'marketplace_tag' => $this->dimensionValues(data_get($item, 'marketplace_tags', data_get($item, 'tags'))),
            'occasion' => $this->dimensionValues(data_get($item, 'occasions')),
            'audience' => $this->dimensionValues(data_get($item, 'audiences')),
        ];

        $score = 0;
        foreach ($signals as $dimension => $wanted) {
            if ($wanted === []) {
                continue;
            }
            $score += count(array_intersect($wanted, $dimensions[$dimension] ?? []));
        }

        return $score;
    }

    private function dimensionValues(mixed $dimension): array
    {
        $rows = is_array($dimension) && array_is_list($dimension) ? $dimension : [$dimension];

        return collect($rows)->flatMap(function ($row): array {
            if (is_array($row)) {
                return array_filter([
                    Str::lower(trim((string) ($row['id'] ?? ''))),
                    Str::lower(trim((string) ($row['slug'] ?? ''))),
                    Str::lower(trim((string) ($row['name'] ?? ''))),
                ]);
            }

            return [Str::lower(trim((string) $row))];
        })->filter()->unique()->values()->all();
    }

    private function renderCards(Collection $items, bool $forceNewCard = false): string
    {
        return $items
            ->filter()
            ->map(function (array $item) use ($forceNewCard): string {
                return view('partials.product_card_v4_1', [
                    'product' => $item,
                    'preferredLocation' => null,
                    'forceNewCard' => $forceNewCard,
                    'cardVersion' => 'v4.10',
                ])->render();
            })
            ->implode('');
    }

    private function isGift(array $item): bool
    {
        return preg_match('/\b(?:gift(?:\s*card)?|e-?gift|voucher)\b/i', implode(' ', [
            (string) data_get($item, 'title', ''),
            (string) data_get($item, 'summary', ''),
            (string) data_get($item, 'category.name', ''),
            (string) data_get($item, 'type.name', ''),
            implode(' ', (array) data_get($item, 'tags', [])),
        ])) === 1;
    }

    private function isPhysicalProduct(array $item): bool
    {
        return in_array(Str::lower((string) data_get($item, 'source_type', data_get($item, 'kind', ''))), ['physical_product', 'store_product'], true);
    }

    private function price(array $item): ?float
    {
        return ProductRanking::priceValue($item);
    }

    private function matchesMode(array $item, string $mode): bool
    {
        $locations = collect((array) data_get($item, 'locations', []))->map(fn ($location) => Str::lower((string) $location));
        $channels = collect((array) data_get($item, 'channels', []))->map(fn ($channel) => Str::lower((string) $channel));
        $online = (bool) data_get($item, 'online_only', false) || $channels->contains('online') || $locations->contains(fn (string $location) => Str::contains($location, 'online'));
        $physical = $channels->contains('in_person') || $locations->contains(fn (string $location) => ! Str::contains($location, 'online'));

        return match ($mode) {
            'online' => $online && ! $physical,
            'in-person' => $physical,
            default => true,
        };
    }

    private function matchesGroupType(array $item, string $groupType): bool
    {
        if ($groupType === '' || $groupType === 'all') {
            return true;
        }

        if (! in_array($groupType, ['solo', 'couple', 'group'], true)) {
            return true;
        }

        $audiences = collect((array) data_get($item, 'audiences', []))->map(fn ($audience) => Str::lower((string) $audience));
        if ($audiences->contains($groupType)) {
            return true;
        }

        $text = Str::lower(implode(' ', [
            (string) data_get($item, 'title', ''),
            (string) data_get($item, 'summary', ''),
            (string) data_get($item, 'type.name', ''),
            (string) data_get($item, 'category.name', ''),
        ]));

        return match ($groupType) {
            'solo' => Str::contains($text, ['solo', '1 person', '1-to-1', '1:1']),
            'couple' => Str::contains($text, ['couple', '2 person', 'pair', 'duo']),
            'group' => Str::contains($text, ['group', 'workshop', 'class']),
        };
    }

    private function timestamp(array $item): int
    {
        $value = data_get($item, 'created_at', data_get($item, 'published_at'));

        return $value ? Carbon::parse((string) $value)->getTimestamp() : (int) data_get($item, 'id', 0);
    }

    private function isPublicOffering(array $item): bool
    {
        return in_array(Str::lower(trim((string) data_get($item, 'status', 'live'))), ['live', 'published'], true);
    }
}
