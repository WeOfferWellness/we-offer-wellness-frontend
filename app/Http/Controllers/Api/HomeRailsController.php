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
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        if ($section === 'gifts') {
            $limit = max(1, min((int) $request->integer('limit', 12), 24));
            $page = max(1, (int) $request->integer('page', 1));
            $catalogue = $this->catalogue(['max_price' => 50], 6);
            $items = ProductRanking::sortCollection($catalogue->filter(fn (array $item) => $this->isGift($item) && $this->price($item) !== null), 'review_count_desc');
            if ($items->isEmpty()) {
                $items = ProductRanking::sortCollection($catalogue->filter(fn (array $item) => $this->price($item) !== null), 'review_count_desc');
            }
            $offset = ($page - 1) * $limit;

            return response($this->renderCards($items->slice($offset, $limit)->values(), true))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Page', (string) $page)
                ->header('X-Page-Size', (string) $limit)
                ->header('X-Has-More', ($offset + $limit) < $items->count() ? '1' : '0')
                ->header('X-Total-Count', (string) $items->count());
        }

        if ($section === 'comfort') {
            $limit = max(1, min((int) $request->integer('limit', 12), 24));
            $priceMax = max(1, (float) $request->input('price_max', 50));
            $groupType = Str::lower(trim((string) $request->input('group_type', 'solo')));
            $mode = Str::lower(trim((string) $request->input('mode', 'online')));

            $items = $this->catalogue(['max_price' => $priceMax], 6)
                ->filter(fn (array $item) => $this->price($item) !== null)
                ->filter(fn (array $item) => $this->matchesMode($item, $mode))
                ->filter(fn (array $item) => $this->matchesGroupType($item, $groupType));

            return response($this->renderCards(ProductRanking::sortCollection($items)->take($limit)))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return response('', 404);
    }

    private function catalogue(array $filters = [], int $maxPages = 2): Collection
    {
        return $this->offeringsClient->catalogue($filters, $maxPages)
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
        return Str::contains(Str::lower(implode(' ', [
            (string) data_get($item, 'title', ''),
            (string) data_get($item, 'summary', ''),
            (string) data_get($item, 'category.name', ''),
            (string) data_get($item, 'type.name', ''),
            implode(' ', (array) data_get($item, 'tags', [])),
        ])), ['gift', 'voucher', 'card', 'present']);
    }

    private function price(array $item): ?float
    {
        return ProductRanking::priceValue($item);
    }

    private function matchesMode(array $item, string $mode): bool
    {
        $locations = collect((array) data_get($item, 'locations', []))->map(fn ($location) => Str::lower((string) $location));
        $online = (bool) data_get($item, 'online_only', false) || $locations->contains('online');
        $physical = $locations->contains(fn (string $location) => $location !== 'online');

        return match ($mode) {
            'online' => $online && ! $physical,
            'in-person' => $physical,
            default => true,
        };
    }

    private function matchesGroupType(array $item, string $groupType): bool
    {
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
