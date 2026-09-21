<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BackendDiscoveryClient
{
    public function modalityBoard(int $limit = 5): array
    {
        $baseUrl = rtrim((string) env('BACKEND_URL', env('BACKEND_ASSET_URL', '')), '/');
        if ($baseUrl === '') {
            return [];
        }

        $cacheKey = 'backend:discovery:'.sha1($baseUrl.'|'.$limit);

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($baseUrl, $limit): array {
            try {
                $response = Http::acceptJson()->timeout(3)->get($baseUrl.'/api/behaviour/discovery', ['limit' => min(5, max(5, $limit))]);
            } catch (\Throwable) {
                return [];
            }

            return $response->successful() && is_array($response->json('data'))
                ? $response->json('data')
                : [];
        });
    }
}
