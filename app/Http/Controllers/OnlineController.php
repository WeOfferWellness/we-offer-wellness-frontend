<?php

namespace App\Http\Controllers;

use App\Models\OfferingV3;
use App\Models\Product;
use App\Support\EventListing;
use App\Support\ProductRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OnlineController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderOnlinePage($request, '');
    }

    public function show(Request $request, string $modality)
    {
        return $this->renderOnlinePage($request, Str::slug($modality));
    }

    private function fetchOfferings(array $query): array
    {
        $cacheKey = 'online:list:local:' . md5(json_encode($query));

        return $this->rememberSafely($cacheKey, now()->addMinutes(5), function () use ($query) {
            return $this->buildOfferingsPage($query);
        });
    }

    private function buildOfferingsPage(array $query): array
    {
        $items = $this->localOnlineItems((string) ($query['modality'] ?? ''));
        $sorted = ProductRanking::sortCollection($items, (string) ($query['sort'] ?? 'popular'))->values();

        $perPage = max(8, min((int) ($query['per_page'] ?? 24), 48));
        $currentPage = max(1, (int) ($query['page'] ?? 1));
        $total = $sorted->count();
        $lastPage = max(1, (int) ceil(max($total, 1) / $perPage));
        $currentPage = min($currentPage, $lastPage);

        $pageItems = $sorted
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        return [
            'items' => $pageItems->all(),
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'total' => $total,
                'per_page' => $perPage,
                'total_pages' => $lastPage,
            ],
        ];
    }

    private function localOnlineItems(string $modality = ''): Collection
    {
        $products = Product::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->withMin('variants', 'price')
            ->withMax('variants', 'price')
            ->with(['media', 'options.values', 'category', 'vendor.tiers', 'vendor.user.settings'])
            ->where(function ($q): void {
                $q->whereHas('status', function ($qs): void {
                    $qs->whereIn('status', ['live', 'approved']);
                })->orWhereNull('product_status_id');
            })
            ->whereHas('options', function ($oq): void {
                $oq->where('meta_name', 'locations')
                    ->whereHas('values', function ($vq): void {
                        $vq->whereRaw('LOWER(value) = ?', ['online']);
                    });
            })
            ->get()
            ->reject(fn (Product $product) => EventListing::isPast($product))
            ->filter(function (Product $product): bool {
                return method_exists($product, 'hasDisplayableImage')
                    ? $product->hasDisplayableImage()
                    : true;
            })
            ->map(function (Product $product): Product {
                $product->source_version = 'v1-v2';
                return $product;
            })
            ->when($modality !== '', function (Collection $items) use ($modality) {
                return $items->filter(fn (Product $product): bool => $this->matchesModality($product, $modality))->values();
            })
            ->values();

        $offerings = OfferingV3::query()
            ->with(['category', 'type', 'vendor.tiers', 'vendor.user.settings', 'media', 'coverMedia'])
            ->whereIn('status', ['live', 'approved'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get()
            ->reject(fn (OfferingV3 $offering) => EventListing::isPast($offering))
            ->filter(function (OfferingV3 $offering): bool {
                $locations = $offering->getLocations();
                return in_array('Online', $locations, true);
            })
            ->filter(function (OfferingV3 $offering): bool {
                return method_exists($offering, 'hasDisplayableImage')
                    ? $offering->hasDisplayableImage()
                    : true;
            })
            ->map(function (OfferingV3 $offering): OfferingV3 {
                $offering->source_version = 'v3';
                return $offering;
            })
            ->when($modality !== '', function (Collection $items) use ($modality) {
                return $items->filter(fn (OfferingV3 $offering): bool => $this->matchesModality($offering, $modality))->values();
            })
            ->values();

        return $products->concat($offerings)->values();
    }

    private function renderOnlinePage(Request $request, string $modality): \Illuminate\View\View
    {
        $modality = Str::slug($modality);
        $isMobile = preg_match('/android|iphone|ipod|mobile|windows phone|opera mini|iemobile/i', (string) $request->userAgent()) === 1;
        $defaultPerPage = $isMobile ? 12 : 24;
        $requestedPerPage = (int) $request->query('per_page', $defaultPerPage);
        $filters = [
            'format' => 'online',
            'modality' => $modality,
            'sort' => (string) $request->query('sort', ''),
            'page' => max(1, (int) $request->query('page', 1)),
            'per_page' => min(48, max(8, $requestedPerPage)),
        ];

        $results = $this->fetchOfferings($filters);
        $label = $modality !== '' ? Str::headline(str_replace('-', ' ', $modality)) : 'Online';
        $hasFacets = (bool) (
            $filters['sort'] ||
            $request->has('page') ||
            $request->has('per_page')
        );

        return view('online.index', [
            'seo' => [
                'title' => ($modality !== '' ? $label . ' Online' : 'Online Experiences') . ' | We Offer Wellness™',
                'description' => $modality !== ''
                    ? 'Browse online ' . strtolower($label) . ' experiences from trusted practitioners — calm, convenient, and ready wherever you are.'
                    : 'Join online wellness experiences from trusted practitioners — calming, convenient, and ready wherever you are.',
                'robots' => $hasFacets ? 'noindex,follow' : 'index,follow',
                'canonical' => $modality !== '' ? url('/online/' . $modality) : url('/online'),
            ],
            'filters' => $filters,
            'results' => $results,
            'modality' => $modality,
            'modalityLabel' => $modality !== '' ? $label : 'Online',
            'pageUrl' => $modality !== '' ? url('/online/' . $modality) : url('/online'),
        ]);
    }

    private function matchesModality(mixed $item, string $modality): bool
    {
        $modality = Str::slug($modality);
        if ($modality === '') {
            return true;
        }

        $candidates = array_filter([
            (string) data_get($item, 'category.slug', ''),
            (string) data_get($item, 'category.name', ''),
            (string) data_get($item, 'type.name', ''),
            (string) data_get($item, 'product_type', ''),
        ]);

        foreach ($candidates as $candidate) {
            $candidate = Str::slug((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            if ($candidate === $modality || str_contains($candidate, $modality) || str_contains($modality, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function rememberSafely(string $key, mixed $ttl, callable $callback): mixed
    {
        try {
            if (Cache::has($key)) {
                return Cache::get($key);
            }
        } catch (\Throwable $e) {
            // Cache backend unavailable or not writable. Fall back to live data.
        }

        $value = $callback();

        try {
            Cache::put($key, $value, $ttl);
        } catch (\Throwable $e) {
            // Ignore cache write failures.
        }

        return $value;
    }
}
