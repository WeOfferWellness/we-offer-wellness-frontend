<?php

namespace App\Http\Controllers;

use App\Services\BackendOfferingsClient;
use App\Support\EventListing;
use App\Support\ProductRanking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index(BackendOfferingsClient $offeringsClient)
    {
        $cacheVersion = $this->homeCacheVersion();

        if (request()->boolean('fresh')) {
            Cache::forget('home:index:v4:'.$cacheVersion);
            Cache::forget('home:index:html:v4:'.$cacheVersion);
        }

        $payload = Cache::remember('home:index:v4:'.$cacheVersion, now()->addMinutes(10), function () use ($offeringsClient): array {
            // Product, offering, and review data belongs to the Backend API only.
            $active = $offeringsClient->catalogue()
                ->filter(fn (array $offering): bool => $this->isPublicOffering($offering))
                ->reject(fn (array $offering) => EventListing::isPast($offering))
                ->values();

            $giftsUnder50 = ProductRanking::sortCollection(
                $active->filter(fn (array $offering) => $this->isGift($offering) && $this->price($offering) !== null && $this->price($offering) <= 50),
                'review_count_desc'
            )->take(12)->values();

            if ($giftsUnder50->isEmpty()) {
                $giftsUnder50 = ProductRanking::sortCollection(
                    $active->filter(fn (array $offering) => $this->price($offering) !== null && $this->price($offering) <= 50),
                    'review_count_desc'
                )->take(12)->values();
            }

            $onlineUnder50 = ProductRanking::sortCollection(
                $active->filter(fn (array $offering) => $this->isOnlineOnly($offering) && $this->price($offering) !== null && $this->price($offering) <= 50)
            )->take(12)->values();

            $latestCatalogue = $active
                ->sortByDesc(fn (array $offering) => Carbon::parse((string) data_get($offering, 'created_at', data_get($offering, 'published_at', '1970-01-01')))->getTimestamp())
                ->take(12)
                ->values();

            $reviewCount = (int) $active->sum(fn (array $offering) => (int) data_get($offering, 'review_count', 0));
            $ratings = $active->map(fn (array $offering) => (float) data_get($offering, 'rating', 0))->filter(fn (float $rating) => $rating > 0);

            return [
                'giftsUnder50' => $giftsUnder50,
                'onlineUnder50' => $onlineUnder50,
                'latestCatalogue' => $latestCatalogue,
                'hasClassesThisWeek' => $this->hasClassesThisWeek($active),
                'review_count' => $reviewCount,
                'verified_count' => $reviewCount,
                'avg_rating' => $ratings->isNotEmpty() ? round($ratings->avg(), 1) : null,
            ];
        });

        if (app()->environment('local') || auth()->check()) {
            return view('home.index', $payload);
        }

        // The rail markup is deliberately only a short-lived skeleton. Serving
        // cached homepage HTML can leave a visitor with an old bundle/rail pair
        // after a deploy, which means placeholders never get replaced. Keep the
        // catalogue data cached above, but always deliver current page markup.
        return response(view('home.index', $payload)->render())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function isGift(array $offering): bool
    {
        return Str::contains(Str::lower(implode(' ', [
            (string) data_get($offering, 'title', ''),
            (string) data_get($offering, 'summary', ''),
            (string) data_get($offering, 'category.name', ''),
            (string) data_get($offering, 'type.name', ''),
            implode(' ', (array) data_get($offering, 'tags', [])),
        ])), ['gift', 'voucher', 'card', 'present']);
    }

    private function isPublicOffering(array $offering): bool
    {
        return in_array(Str::lower(trim((string) data_get($offering, 'status', 'live'))), ['live', 'published'], true);
    }

    private function isOnlineOnly(array $offering): bool
    {
        $locations = collect((array) data_get($offering, 'locations', []))->map(fn ($location) => Str::lower((string) $location));

        return (bool) data_get($offering, 'online_only', false)
            || ($locations->contains('online') && $locations->filter(fn (string $location) => $location !== 'online')->isEmpty());
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
                resource_path('views/home/sections/latest_catalogue.blade.php'),
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
