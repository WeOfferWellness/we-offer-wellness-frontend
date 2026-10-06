<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BackendPageLayoutClient
{
    public function page(string $key, string $platformKey = 'wow-marketplace'): array
    {
        $key = trim($key);
        $platformKey = trim($platformKey) ?: 'wow-marketplace';
        $default = $this->defaultLayout($key);

        if ($key === '') {
            return $default;
        }

        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');
        if ($baseUrl === '') {
            return $default;
        }

        $cacheKey = 'backend:site-page:v2:'.sha1($baseUrl.'|'.$platformKey.'|'.$key);

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Origin' => config('app.url'),
                    'Referer' => rtrim((string) config('app.url'), '/').'/',
                    'Cache-Control' => 'no-cache',
                ])
                ->timeout(4)
                ->get(
                    $baseUrl.'/api/platforms/'.rawurlencode($platformKey).'/site-pages/'.rawurlencode($key)
                );

            $payload = $response->successful() && is_array($response->json())
                ? $response->json()
                : null;
        } catch (\Throwable) {
            $payload = null;
        }

        if (! is_array($payload) || ! is_array($payload['sections'] ?? null)) {
            return $this->safeCacheGet($cacheKey.':last-good', $default);
        }

        $sections = $this->sanitizeSections($key, $payload['sections'], true);
        $managedSections = $this->sanitizeSections(
            $key,
            is_array($payload['managed_sections'] ?? null)
                ? $payload['managed_sections']
                : $payload['sections'],
            false
        );

        if ($sections === [] && $managedSections === []) {
            return $default;
        }

        $result = [
            'page' => is_array($payload['page'] ?? null) ? $payload['page'] : ['key' => $key],
            'sections' => $sections,
            'managed_sections' => $managedSections,
        ];

        $this->safeCachePut($cacheKey.':last-good', $result, now()->addHours(6));

        return $result;
    }

    public function sections(string $key, string $platformKey = 'wow-marketplace'): array
    {
        return (array) ($this->page($key, $platformKey)['sections'] ?? []);
    }

    private function defaultLayout(string $key): array
    {
        $sections = $this->sanitizeSections(
            $key,
            (array) config("site-pages.pages.{$key}.default_layout", [])
        );

        return [
            'page' => ['key' => $key],
            'sections' => $sections,
            'managed_sections' => $sections,
        ];
    }

    private function sanitizeSections(string $key, array $sections, bool $activeOnly = true): array
    {
        $components = (array) config('site-pages.components', []);

        return collect($sections)
            ->filter(fn ($section): bool => is_array($section))
            ->filter(function (array $section) use ($components, $activeOnly): bool {
                $component = trim((string) ($section['component'] ?? ''));

                if ($component === '' || ! isset($components[$component])) {
                    return false;
                }

                return ! $activeOnly || (bool) ($section['active'] ?? $section['enabled'] ?? true);
            })
            ->values()
            ->map(function (array $section, int $index): array {
                return [
                    'id' => (string) ($section['id'] ?? ''),
                    'component' => (string) $section['component'],
                    'label' => (string) ($section['label'] ?? ''),
                    'enabled' => (bool) ($section['enabled'] ?? true),
                    'active' => (bool) ($section['active'] ?? $section['enabled'] ?? true),
                    'show_desktop' => (bool) ($section['show_desktop'] ?? true),
                    'show_mobile' => (bool) ($section['show_mobile'] ?? true),
                    'desktop_order' => max(0, (int) ($section['desktop_order'] ?? $index)),
                    'mobile_order' => max(0, (int) ($section['mobile_order'] ?? $index)),
                    'starts_at' => $section['starts_at'] ?? null,
                    'ends_at' => $section['ends_at'] ?? null,
                    'config' => is_array($section['config'] ?? null) ? $section['config'] : [],
                    'platform_component_id' => isset($section['platform_component_id'])
                        ? (int) $section['platform_component_id']
                        : null,
                    'platform_component' => is_array($section['platform_component'] ?? null)
                        ? $section['platform_component']
                        : null,
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
