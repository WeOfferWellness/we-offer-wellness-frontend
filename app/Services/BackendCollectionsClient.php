<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BackendCollectionsClient
{
    public function collection(string $slug, array $query = []): ?array
    {
        $baseUrl = $this->baseUrl();
        $slug = trim($slug);

        if ($baseUrl === '' || $slug === '') {
            return null;
        }

        $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '');
        $cacheKey = 'backend:collection:v1:'.sha1($baseUrl.'|'.$slug.'|'.json_encode($query));

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['collection'])) {
            return $cached;
        }

        try {
            $response = $this->client()->get(
                $baseUrl.'/api/collections/'.rawurlencode($slug),
                $query
            );
        } catch (\Throwable) {
            return Cache::get($cacheKey.':last-good');
        }

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            return Cache::get($cacheKey.':last-good');
        }

        $payload = $response->json();
        if (! is_array($payload) || ! is_array($payload['collection'] ?? null) || ! is_array($payload['data'] ?? null)) {
            return Cache::get($cacheKey.':last-good');
        }

        Cache::put($cacheKey, $payload, now()->addMinute());
        Cache::put($cacheKey.':last-good', $payload, now()->addMinutes(30));

        return $payload;
    }

    public function indexableCollections(): array
    {
        $baseUrl = $this->baseUrl();
        if ($baseUrl === '') {
            return [];
        }

        $cacheKey = 'backend:collections:indexable:v1:'.sha1($baseUrl);

        try {
            $response = $this->client()->get($baseUrl.'/api/collections', [
                'indexable' => 1,
            ]);
        } catch (\Throwable) {
            return Cache::get($cacheKey.':last-good', []);
        }

        if (! $response->successful()) {
            return Cache::get($cacheKey.':last-good', []);
        }

        $rows = collect((array) data_get($response->json(), 'data', []))
            ->filter(fn ($row): bool => is_array($row))
            ->values()
            ->all();

        Cache::put($cacheKey, $rows, now()->addMinutes(5));
        Cache::put($cacheKey.':last-good', $rows, now()->addHours(6));

        return $rows;
    }

    private function client()
    {
        return Http::acceptJson()
            ->withHeaders([
                'Origin' => config('app.url'),
                'Referer' => rtrim((string) config('app.url'), '/').'/',
            ])
            ->timeout(8);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.backend_url', ''), '/');
    }
}
