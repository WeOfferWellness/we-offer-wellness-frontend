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

    private function renderCards(Collection $items, bool $forceNewCard = false): string
    {
        return $items
            ->filter()
            ->map(function (array $item) use ($forceNewCard): string {
                if (data_get($item, 'kind') === 'physical_product' || data_get($item, 'source_type') === 'physical_product') {
                    return view('partials.store_product_card', ['product' => (object) $item])->render();
                }

                return view('partials.product_card_v4_1', ['product' => $item, 'preferredLocation' => null, 'forceNewCard' => $forceNewCard])->render();
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
