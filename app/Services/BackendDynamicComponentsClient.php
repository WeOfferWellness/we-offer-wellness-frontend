<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BackendDynamicComponentsClient
{
    public function offerings(
        array $config = [],
        array $runtimeFilters = [],
        string $platformKey = 'wow-marketplace'
    ): array {
        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');

        if ($baseUrl === '') {
            return $this->emptyPayload();
        }

        $query = [
            'collection' => trim((string) ($config['collection_slug'] ?? '')),
            'modality' => $this->listValue($config['modalities'] ?? []),
            'offering_type' => $this->listValue($config['offering_types'] ?? []),
            'tag' => $this->listValue($config['tags'] ?? []),
            'need' => $this->listValue($config['needs'] ?? []),
            'occasion' => $this->listValue($config['occasions'] ?? []),
            'location' => $this->listValue($config['locations'] ?? []),
            'format' => trim((string) ($runtimeFilters['format'] ?? '')),
            'location_filter' => trim((string) ($runtimeFilters['location'] ?? '')),
            'sort' => trim((string) ($runtimeFilters['sort'] ?? ($config['sort'] ?? 'default'))),
            'page' => max(1, (int) ($runtimeFilters['page'] ?? 1)),
            'per_page' => max(1, min((int) (
                $runtimeFilters['per_page']
                ?? $config['per_page']
                ?? $config['limit']
                ?? 12
            ), 100)),
        ];

        $query = array_filter($query, static function ($value): bool {
            if (is_array($value)) {
                return $value !== [];
            }

            return $value !== null && $value !== '';
        });

        $cacheKey = 'backend:dynamic-component-offerings:v1:'.sha1(
            $baseUrl.'|'.$platformKey.'|'.json_encode($query)
        );

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Origin' => config('app.url'),
                    'Referer' => rtrim((string) config('app.url'), '/').'/',
                ])
                ->timeout(8)
                ->get(
                    $baseUrl.'/api/platforms/'.rawurlencode($platformKey).'/component-offerings',
                    $query
                );
        } catch (\Throwable) {
            return $this->safeCacheGet($cacheKey.':last-good', $this->emptyPayload());
        }

        if (! $response->successful()) {
            return $this->safeCacheGet($cacheKey.':last-good', $this->emptyPayload());
        }

        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
            return $this->safeCacheGet($cacheKey.':last-good', $this->emptyPayload());
        }

        $this->safeCachePut($cacheKey, $payload, now()->addSeconds(30));
        $this->safeCachePut($cacheKey.':last-good', $payload, now()->addHours(6));

        return $payload;
    }

    private function listValue(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return collect(is_array($value) ? $value : [])
            ->flatten()
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function emptyPayload(): array
    {
        return [
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'per_page' => 12,
                'total' => 0,
                'last_page' => 1,
                'from' => null,
                'to' => null,
            ],
        ];
    }

    private function safeCacheGet(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    private function safeCachePut(string $key, mixed $value, mixed $ttl): void
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (\Throwable) {
            // Rendering should continue even when the optional cache store is unavailable.
        }
    }
}
