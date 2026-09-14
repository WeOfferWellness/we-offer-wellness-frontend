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

        $cacheKey = 'backend:offerings:'.sha1($baseUrl.'|'.json_encode($filters).'|'.$maxPages);

        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($baseUrl, $filters, $maxPages): Collection {
            $items = collect();
            $page = max(1, (int) ($filters['page'] ?? 1));

            for ($requestNumber = 0; $requestNumber < $maxPages; $requestNumber++, $page++) {
                try {
                    $response = Http::acceptJson()
                        ->withHeaders([
                            'Origin' => config('app.url'),
                            'Referer' => rtrim((string) config('app.url'), '/').'/',
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
                ->unique(fn (array $offering) => (string) data_get($offering, 'source_version', '').':'.(string) data_get($offering, 'id', ''))
                ->values();
        });
    }
}
