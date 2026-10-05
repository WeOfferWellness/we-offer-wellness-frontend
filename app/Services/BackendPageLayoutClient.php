<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BackendPageLayoutClient
{
    public function page(string $key): array
    {
        $key = trim($key);
        $default = $this->defaultLayout($key);

        if ($key === '') {
            return $default;
        }

        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');
        if ($baseUrl === '') {
            return $default;
        }

        $cacheKey = 'backend:site-page:v1:'.sha1($baseUrl.'|'.$key);

        try {
            $payload = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($baseUrl, $key) {
                $response = Http::acceptJson()
                    ->withHeaders([
                        'Origin' => config('app.url'),
                        'Referer' => rtrim((string) config('app.url'), '/').'/',
                    ])
                    ->timeout(4)
                    ->get($baseUrl.'/api/site-pages/'.rawurlencode($key));

                if (! $response->successful()) {
                    return null;
                }

                $json = $response->json();

                return is_array($json) ? $json : null;
            });
        } catch (\Throwable) {
            $payload = null;
        }

        if (! is_array($payload) || ! is_array($payload['sections'] ?? null)) {
            return $this->safeCacheGet($cacheKey.':last-good', $default);
        }

        $sections = $this->sanitizeSections($key, $payload['sections']);
        if ($sections === []) {
            return $default;
        }

        $result = [
            'page' => is_array($payload['page'] ?? null) ? $payload['page'] : ['key' => $key],
            'sections' => $sections,
        ];

        $this->safeCachePut($cacheKey.':last-good', $result, now()->addHours(6));

        return $result;
    }

    public function sections(string $key): array
    {
        return (array) ($this->page($key)['sections'] ?? []);
    }

    private function defaultLayout(string $key): array
    {
        return [
            'page' => ['key' => $key],
            'sections' => $this->sanitizeSections(
                $key,
                (array) config("site-pages.pages.{$key}.default_layout", [])
            ),
        ];
    }

    private function sanitizeSections(string $key, array $sections): array
    {
        $components = (array) config('site-pages.components', []);

        return collect($sections)
            ->filter(fn ($section): bool => is_array($section))
            ->filter(function (array $section) use ($components): bool {
                $component = trim((string) ($section['component'] ?? ''));

                return $component !== ''
                    && isset($components[$component])
                    && (bool) ($section['enabled'] ?? true);
            })
            ->values()
            ->map(function (array $section, int $index): array {
                return [
                    'id' => (string) ($section['id'] ?? ''),
                    'component' => (string) $section['component'],
                    'label' => (string) ($section['label'] ?? ''),
                    'enabled' => (bool) ($section['enabled'] ?? true),
                    'show_desktop' => (bool) ($section['show_desktop'] ?? true),
                    'show_mobile' => (bool) ($section['show_mobile'] ?? true),
                    'desktop_order' => max(0, (int) ($section['desktop_order'] ?? $index)),
                    'mobile_order' => max(0, (int) ($section['mobile_order'] ?? $index)),
                    'starts_at' => $section['starts_at'] ?? null,
                    'ends_at' => $section['ends_at'] ?? null,
                    'config' => is_array($section['config'] ?? null) ? $section['config'] : [],
                ];
            })
            ->values()
            ->all();
    }

    private function safeCacheGet(string $key, mixed $default): mixed
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
            // Frontend rendering must never fail because cache storage is unavailable.
        }
    }
}
