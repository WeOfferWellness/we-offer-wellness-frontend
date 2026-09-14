<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** The Frontend catalogue is sourced exclusively from the Backend API. */
class BackendOfferingsClient
{
    public function catalogue(array $filters = [], int $maxPages = 2): Collection
    {
        $baseUrl = rtrim((string) env('BACKEND_URL', env('BACKEND_ASSET_URL', '')), '/');

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
        $cacheKey = 'backend:offerings:'.sha1($baseUrl.'|'.json_encode($filters).'|'.$maxPages);

        $load = function () use ($baseUrl, $filters, $maxPages, $request): Collection {
            $items = collect();
            $page = max(1, (int) ($filters['page'] ?? 1));

            for ($requestNumber = 0; $requestNumber < $maxPages; $requestNumber++, $page++) {
                try {
                    $response = Http::acceptJson()
                        ->withHeaders([
                            'Origin' => config('app.url'),
                            'Referer' => rtrim((string) config('app.url'), '/').'/',
                            'X-WOW-User-Agent' => (string) $request->userAgent(),
                            'X-WOW-Device-Class' => $this->deviceClass($request->userAgent()),
                            'X-WOW-Client-IP' => (string) $request->ip(),
                            ...($request->headers->has('cookie') ? ['Cookie' => $request->headers->get('cookie')] : []),
                        ])
                        ->timeout(8)
                        ->retry(1, 150)
                        ->get($baseUrl.'/api/offerings', array_merge($filters, ['page' => $page]));
                } catch (\Throwable) {
                    break;
                }

                if (! $response->successful()) {
                    break;
                }

                $payload = $response->json();
                $vendorDetails = data_get($payload, 'included.vendor_details', []);
                $rows = collect(data_get($payload, 'data', []))
                    ->filter(fn ($offering): bool => is_array($offering))
                    ->map(function (array $offering) use ($vendorDetails): array {
                        $vendorId = (string) data_get($offering, 'vendor_id', '');
                        $vendor = is_array($vendorDetails) ? ($vendorDetails[$vendorId] ?? null) : null;

                        if (is_array($vendor)) {
                            $offering['vendor'] = $vendor;
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

        return $hasVisitorCookie
            ? $load()
            : Cache::remember($cacheKey, now()->addMinutes(3), $load);
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
