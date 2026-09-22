<?php

namespace App\Http\Controllers;

use App\Services\BackendDiscoveryClient;
use App\Services\BackendOfferingsClient;
use App\Support\EventListing;
use App\Support\ProductRanking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index(BackendOfferingsClient $offeringsClient, BackendDiscoveryClient $discoveryClient)
    {
        $cacheVersion = $this->homeCacheVersion();

        if (request()->boolean('fresh')) {
            Cache::forget('home:index:v6:'.$cacheVersion);
            Cache::forget('home:index:html:v6:'.$cacheVersion);
        }

        $payload = Cache::remember('home:index:v6:'.$cacheVersion, now()->addMinutes(10), function () use ($offeringsClient): array {
            // Product, offering, and review data belongs to the Backend API only.
            // The initial homepage must not wait for per-visitor ranking. The
            // generic catalogue is cached and personalisation can happen on
            // later discovery/search requests without blocking first paint.
            $active = $offeringsClient->catalogue([], 2, false)
                ->filter(fn (array $offering): bool => $this->isPublicOffering($offering))
                ->reject(fn (array $offering) => EventListing::isPast($offering))
                ->values();

            $giftCandidates = $active
                ->concat($offeringsClient->catalogue(['max_price' => 50, 'search' => 'gift'], 2, false))
                ->unique(fn (array $offering) => (string) data_get($offering, 'source_type', data_get($offering, 'source_version', '')).':'.(string) data_get($offering, 'id', ''));
            $explicitGifts = ProductRanking::sortCollection(
                $giftCandidates->filter(fn (array $offering) => $this->isGift($offering) && $this->price($offering) !== null && $this->price($offering) <= 50),
                'review_count_desc'
            );
            $physicalGifts = ProductRanking::sortCollection(
                $giftCandidates->filter(fn (array $offering) => ! $this->isGift($offering) && $this->isPhysicalProduct($offering) && $this->price($offering) !== null && $this->price($offering) <= 50),
                'review_count_desc'
            );
            $giftIdeas = ProductRanking::sortCollection(
                $giftCandidates->filter(fn (array $offering) => ! $this->isGift($offering) && ! $this->isPhysicalProduct($offering) && $this->price($offering) !== null && $this->price($offering) <= 50),
                'review_count_desc'
            );
            $giftsUnder50 = $explicitGifts->concat($physicalGifts)->concat($giftIdeas)->take(12)->values();

            $onlineUnder50 = ProductRanking::sortCollection(
                $active->filter(fn (array $offering) => $this->isOnlineOnly($offering) && $this->price($offering) !== null && $this->price($offering) <= 50)
            )->take(12)->values();

            $onlineUnder100 = ProductRanking::sortCollection(
                $active->filter(fn (array $offering) => $this->isOnlineOnly($offering) && $this->price($offering) !== null && $this->price($offering) > 50 && $this->price($offering) <= 100)
            )->take(12)->values();

            $latestCatalogue = $active
                ->sortByDesc(fn (array $offering) => Carbon::parse((string) data_get($offering, 'created_at', data_get($offering, 'published_at', '1970-01-01')))->getTimestamp())
                ->take(12)
                ->values();

            $reviewStats = $offeringsClient->reviewStats();
            $reviewCount = (int) ($reviewStats['review_count'] ?? 0);
            $verifiedCount = (int) ($reviewStats['verified_count'] ?? 0);
            if ($verifiedCount <= 0 && $reviewCount > 0) {
                $verifiedCount = $reviewCount;
            }
            $ratings = $active->map(fn (array $offering) => (float) data_get($offering, 'rating', 0))->filter(fn (float $rating) => $rating > 0);

            return [
                'giftsUnder50' => $giftsUnder50,
                'onlineUnder50' => $onlineUnder50,
                'onlineUnder100' => $onlineUnder100,
                'latestCatalogue' => $latestCatalogue,
                'hasClassesThisWeek' => $this->hasClassesThisWeek($active),
                'review_count' => $reviewCount,
                'verified_count' => $verifiedCount,
                'avg_rating' => $ratings->isNotEmpty() ? round($ratings->avg(), 1) : null,
            ];
        });

        // Behaviour discovery has its own short-lived Backend cache. Keep it
        // outside the longer catalogue cache so the homepage can promote the
        // current highest-interest category into the lead card.
        $payload['discoveryCategories'] = $discoveryClient->modalityBoard(5);

        $payload['onlineUnder50'] = $this->selectRotatingOnlineRow($payload['onlineUnder50']);
        $payload['onlineUnder100'] = $this->selectRotatingOnlineRow($payload['onlineUnder100']);

        if (app()->environment('local') || auth()->check()) {
            return view('home.index', $payload);
        }

        // The rail markup is deliberately only a short-lived skeleton. Serving
        // cached homepage HTML can leave a visitor with an old bundle/rail pair
        // after a deploy, which means placeholders never get replaced. Keep the
        // catalogue data cached above, but always deliver current page markup.
        return response(view('home.index', $payload)->render())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-WOW-Homepage-Performance', 'bounded-upstream-v1');
    }

    private function isGift(array $offering): bool
    {
        return preg_match('/\b(?:gift(?:\s*card)?|e-?gift|voucher)\b/i', implode(' ', [
            (string) data_get($offering, 'title', ''),
            (string) data_get($offering, 'summary', ''),
            (string) data_get($offering, 'category.name', ''),
            (string) data_get($offering, 'type.name', ''),
            implode(' ', (array) data_get($offering, 'tags', [])),
        ])) === 1;
    }

    private function selectRotatingOnlineRow(Collection $items): Collection
    {
        $groups = $items
            ->groupBy(fn (array $item): string => Str::lower(trim((string) data_get(
                $item,
                'category.slug',
                data_get($item, 'category.name', data_get($item, 'type.slug', 'other'))
            )) ?: 'other'))
            ->map(fn (Collection $group): Collection => $group->shuffle()->values())
            ->values();

        $selected = collect();
        while ($selected->count() < 5 && $groups->contains(fn (Collection $group): bool => $group->isNotEmpty())) {
            foreach ($groups as $index => $group) {
                if ($group->isEmpty() || $selected->count() >= 5) {
                    continue;
                }

                $selected->push($group->shift());
                $groups[$index] = $group;
            }
        }

        return $selected->values();
    }

    private function isPublicOffering(array $offering): bool
    {
        return in_array(Str::lower(trim((string) data_get($offering, 'status', 'live'))), ['live', 'published'], true);
    }

    private function isPhysicalProduct(array $offering): bool
    {
        return in_array(Str::lower((string) data_get($offering, 'source_type', data_get($offering, 'kind', ''))), ['physical_product', 'store_product'], true);
    }

    private function isOnlineOnly(array $offering): bool
    {
        $locations = collect((array) data_get($offering, 'locations', []))
            ->map(fn ($location) => Str::lower(trim((string) $location)))
            ->filter();
        $channels = collect((array) data_get($offering, 'channels', []))
            ->map(fn ($channel) => Str::lower(trim((string) $channel)))
            ->filter();

        return (bool) data_get($offering, 'online_only', false)
            || ($channels->contains('online') && $channels->reject(fn (string $channel) => $channel === 'online')->isEmpty()
                && $locations->reject(fn (string $location) => Str::contains($location, 'online'))->isEmpty())
            || ($locations->contains(fn (string $location) => Str::contains($location, 'online'))
                && $locations->reject(fn (string $location) => Str::contains($location, 'online'))->isEmpty());
    }

    private function price(array $offering): ?float
    {
        return ProductRanking::priceValue($offering);
    }

    private function hasClassesThisWeek(Collection $offerings): bool
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        return $offerings->contains(function (array $offering) use ($start, $end): bool {
            if (! Str::contains(Str::lower((string) data_get($offering, 'type.name', data_get($offering, 'type', ''))), 'class')) {
                return false;
            }

            $event = data_get($offering, 'event', data_get($offering, 'when.event', []));
            foreach (['date', 'start_date', 'end_date'] as $field) {
                $value = data_get($event, $field);
                if ($value && Carbon::parse((string) $value)->between($start, $end)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function homeCacheVersion(): string
    {
        $templateFingerprint = implode('|', array_map(
            static fn (string $path): string => $path.':'.(is_file($path) ? (string) filemtime($path) : 'missing'),
            [
                __FILE__,
                resource_path('views/home/index.blade.php'),
                resource_path('views/home/sections/discover_category.blade.php'),
                resource_path('views/home/sections/latest_catalogue.blade.php'),
                resource_path('views/home/sections/no-travel-needed.blade.php'),
                resource_path('views/home/sections/gifts.blade.php'),
                resource_path('views/home/sections/trust-feel-safe.blade.php'),
                resource_path('views/partials/product_showcase_section.blade.php'),
                resource_path('views/partials/product_card_v4_1.blade.php'),
                resource_path('views/partials/product_card_v4_1_ghost.blade.php'),
                public_path('build/manifest.json'),
                resource_path('js/home-offerings.js'),
            ]
        ));

        return sha1($templateFingerprint.'|'.now()->format('YmdH').intdiv((int) now()->format('i'), 10));
    }
}
