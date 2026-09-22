<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BackendDiscoveryClient
{
    public function modalityBoard(int $limit = 5, ?string $location = null): array
    {
        $baseUrl = rtrim((string) env('BACKEND_URL', env('BACKEND_ASSET_URL', '')), '/');
        if ($baseUrl === '') {
            return [];
        }

        $location = trim((string) $location);
        $cacheKey = 'backend:discovery:'.sha1($baseUrl.'|'.$limit.'|'.$location);

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($baseUrl, $limit, $location): array {
            try {
                $response = Http::acceptJson()->timeout(3)->get($baseUrl.'/api/behaviour/discovery', array_filter([
                    'limit' => min(5, max(5, $limit)),
                    'location' => $location !== '' ? $location : null,
                ], static fn ($value): bool => $value !== null && $value !== ''));
            } catch (\Throwable) {
                return [];
            }

            return $response->successful() && is_array($response->json('data'))
                ? $response->json('data')
                : [];
        });
    }
}
