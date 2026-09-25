<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** The Frontend catalogue is sourced exclusively from the Backend API. */
class BackendOfferingsClient
{
    private ?Collection $personalisedCatalogue = null;

    public function reviewStats(): array
    {
        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');
        if ($baseUrl === '') {
            return [];
        }

        return Cache::remember('backend:review-stats:'.sha1($baseUrl), now()->addMinutes(10), function () use ($baseUrl): array {
            try {
                $response = Http::acceptJson()->timeout(3)->withHeaders([
                    'Origin' => config('app.url'),
                    'Referer' => rtrim((string) config('app.url'), '/').'/',
                ])->get($baseUrl.'/api/reviews/stats');
            } catch (\Throwable) {
                return [];
            }

            return $response->successful() && is_array($response->json()) ? $response->json() : [];
        });
    }

    public function catalogue(array $filters = [], int $maxPages = 2, ?bool $personalised = null): Collection
    {
        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');

        if ($baseUrl === '') {
            return collect();
        }

        $filters = array_merge([
            'status' => 'live',
            'version' => 'all',
            'per_page' => 100,
            'sort' => 'updated_at',
            'direction' => 'desc',
        ], $filters);

        $request = request();
        $hasVisitorCookie = (string) $request->cookie('wow_visitor_id') !== '';
        $usePersonalisation = $personalised ?? $hasVisitorCookie;
        $visitorScope = $usePersonalisation && $hasVisitorCookie
            ? ':visitor:'.sha1((string) $request->cookie('wow_visitor_id'))
            : ':public';
        $cacheKey = 'backend:offerings:'.sha1($baseUrl.'|'.json_encode($filters).'|'.$maxPages.'|'.($usePersonalisation ? 'personalised' : 'public')).$visitorScope;

        $load = function () use ($baseUrl, $filters, $maxPages, $request, $usePersonalisation): Collection {
            $items = collect();
            $page = max(1, (int) ($filters['page'] ?? 1));

            for ($requestNumber = 0; $requestNumber < $maxPages; $requestNumber++, $page++) {
                $client = Http::acceptJson()
                    ->withHeaders([
                        'Origin' => config('app.url'),
                        'Referer' => rtrim((string) config('app.url'), '/').'/',
                        'X-WOW-User-Agent' => (string) $request->userAgent(),
                        'X-WOW-Device-Class' => $this->deviceClass($request->userAgent()),
                        'X-WOW-Client-IP' => (string) $request->ip(),
                        ...($request->headers->has('cookie') ? ['Cookie' => $request->headers->get('cookie')] : []),
                    ])
                    ->timeout(4);
                try {
                    $path = $usePersonalisation ? '/api/behaviour/offerings' : '/api/offerings';
                    $response = $client->get($baseUrl.$path, array_merge($filters, ['page' => $page]));
                    if ($usePersonalisation && ! $response->successful()) {
                        $response = $client->get($baseUrl.'/api/offerings', array_merge($filters, ['page' => $page]));
                    }
                } catch (\Throwable) {
                    if (! $usePersonalisation) {
                        break;
                    }
                    try {
                        $response = $client->get($baseUrl.'/api/offerings', array_merge($filters, ['page' => $page]));
                    } catch (\Throwable) {
                        break;
                    }
                }

                if (! $response->successful()) {
                    break;
                }

                $payload = $response->json();
                $vendorDetails = data_get($payload, 'included.vendor_details', []);
                $rankingRequestId = trim((string) data_get($payload, 'meta.ranking_request_id', ''));
                $rows = collect(data_get($payload, 'data', []))
                    ->filter(fn ($offering): bool => is_array($offering))
                    ->map(function (array $offering) use ($vendorDetails, $rankingRequestId): array {
                        $vendorId = (string) data_get($offering, 'vendor_id', '');
                        $vendor = is_array($vendorDetails) ? ($vendorDetails[$vendorId] ?? null) : null;

                        if (is_array($vendor)) {
                            $offering['vendor'] = $vendor;
                        }
                        if ($rankingRequestId !== '') {
                            $offering['ranking_request_id'] = $rankingRequestId;
                        }

                        return $offering;
                    });

                $items = $items->concat($rows);
                $lastPage = (int) data_get($payload, 'meta.last_page', $page);

                if ($rows->isEmpty() || $page >= $lastPage) {
                    break;
                }

            }

            return $items
                ->unique(fn (array $offering) => (string) data_get($offering, 'source_type', data_get($offering, 'source_version', '')).':'.(string) data_get($offering, 'id', ''))
                ->values();
        };

        return Cache::remember(
            $cacheKey,
            now()->addSeconds($usePersonalisation ? 45 : 180),
            $load
        );
    }

    public function reorder(Collection $items, array $filters = [], int $maxPages = 2): Collection
    {
        if ((string) request()->cookie('wow_visitor_id') === '' || $items->isEmpty()) {
            return $items->values();
        }

        $catalogue = $this->personalisedCatalogue ??= $this->catalogue($filters, $maxPages);
        if ($catalogue->isEmpty()) {
            return $items->values();
        }
        $positions = $catalogue->values()->mapWithKeys(fn ($item, int $index) => [$this->offeringKey($item) => $index]);

        return $items->values()->sortBy(function ($item, int $index) use ($positions): int {
            return (int) ($positions->get($this->offeringKey($item), 100000 + $index));
        })->values();
    }

    private function offeringKey(mixed $item): string
    {
        $sourceVersion = strtolower(trim((string) data_get($item, 'source_version', data_get($item, 'version', ''))));
        $sourceType = strtolower(trim((string) data_get($item, 'source_type', data_get($item, 'kind', ''))));
        $source = $sourceVersion === 'v1-v2' ? 'legacy' : ($sourceType === 'physical_product' || $sourceVersion === 'store' ? 'store' : 'v3');
        if ($sourceVersion === '') {
            $itemType = get_debug_type($item);
            $source = str_contains($itemType, 'StoreProduct')
                ? 'store'
                : (str_contains($itemType, 'OfferingV3') ? 'v3' : (str_contains($itemType, 'Product') ? 'legacy' : $source));
        }

        return $source.':'.(int) data_get($item, 'id', 0);
    }

    private function deviceClass(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet') || str_contains($ua, 'android') && ! str_contains($ua, 'mobile')) {
            return 'tablet';
        }

        return preg_match('/android|iphone|ipod|mobile|windows phone|opera mini|iemobile/i', $ua) === 1
            ? 'mobile'
            : 'desktop';
    }
}
