<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NeedService
{
    private const TTL_MINUTES = 10;

    public function all(): Collection
    {
        $baseUrl = $this->baseUrl();
        if ($baseUrl === '') return collect();

        // Do not let a transient Backend outage permanently cache an empty
        // taxonomy collection. A successful collection is cached normally;
        // an empty result is retried on the next request and the UI remains
        // graceful while the API is unavailable.
        $cached = Cache::get('frontend:public-needs:v1');
        if ($cached instanceof Collection && $cached->isNotEmpty()) {
            return $cached;
        }

        $load = function () use ($baseUrl): Collection {
            try {
                $response = Http::acceptJson()->timeout(6)->retry(1, 150)->withHeaders([
                    'Origin' => config('app.url'),
                    'Referer' => rtrim((string) config('app.url'), '/').'/',
                ])->get($baseUrl.'/api/catalog/needs');
                if (! $response->successful()) {
                    return collect();
                }

                $needs = collect(data_get($response->json(), 'needs', []))
                    ->filter(fn ($need) => is_array($need) && ($need['status'] ?? 'approved') === 'approved')
                    ->map(fn (array $need) => $this->normalise($need))
                    ->values();

                return $needs;
            } catch (\Throwable $exception) {
                report($exception);
                return collect();
            }
        };

        $needs = $load();
        if ($needs->isNotEmpty()) {
            Cache::put('frontend:public-needs:v1', $needs, now()->addMinutes(self::TTL_MINUTES));
        }

        return $needs;
    }

    public function find(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $cached = $this->all()->first(fn (array $need) => $need['slug'] === $slug);
        if ($cached) return $cached;

        $baseUrl = $this->baseUrl();
        if ($baseUrl === '') return null;
        try {
            $response = Http::acceptJson()->timeout(6)->retry(1, 150)->withHeaders([
                'Origin' => config('app.url'),
                'Referer' => rtrim((string) config('app.url'), '/').'/',
            ])->get($baseUrl.'/api/catalog/needs/'.rawurlencode($slug));
            if (! $response->successful()) return null;
            $need = data_get($response->json(), 'need');
            return is_array($need) && ($need['status'] ?? 'approved') === 'approved' ? $this->normalise($need) : null;
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    private function normalise(array $need): array
    {
        $name = trim((string) ($need['name'] ?? 'Need'));
        $slug = trim((string) ($need['slug'] ?? ''));
        $description = trim((string) ($need['description'] ?? ''));
        return [...$need, 'id' => isset($need['id']) ? (int) $need['id'] : null, 'name' => $name, 'title' => $name, 'key' => $slug, 'slug' => $slug, 'description' => $description, 'seo_title' => trim((string) ($need['seo_title'] ?? '')) ?: ($name.' Support | We Offer Wellness™'), 'seo_description' => trim((string) ($need['seo_description'] ?? '')) ?: ($description ?: 'Explore wellness support for '.$name.'.'), 'image_url' => $need['image_url'] ?? null, 'url' => trim((string) ($need['url'] ?? '')) ?: url('/needs/'.$slug)];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.backend_url', ''), '/');
    }
}
