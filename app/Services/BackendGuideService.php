<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BackendGuideService
{
    public function all(bool $fresh = false): array
    {
        $base = rtrim((string) env('BACKEND_URL', env('BACKEND_ASSET_URL', '')), '/');
        if ($base === '') return [];

        if ($fresh) {
            Cache::forget('frontend:guides:backend:v1');
        }

        return Cache::remember('frontend:guides:backend:v1', now()->addMinutes(10), function () use ($base): array {
            try {
                $response = Http::acceptJson()->timeout(6)->retry(1, 150)->get($base.'/api/catalog/guides');
                $guides = $response->successful() ? $response->json('guides', []) : [];
                return is_array($guides) ? $guides : [];
            } catch (\Throwable $exception) {
                report($exception);
                return [];
            }
        });
    }

    public function find(string $format, string $modality, string $slug): ?array
    {
        foreach ($this->all() as $guide) {
            if (($guide['format'] ?? '') === $format && ($guide['modality'] ?? '') === $modality && ($guide['slug'] ?? '') === $slug) return $guide;
        }

        $base = rtrim((string) env('BACKEND_URL', env('BACKEND_ASSET_URL', '')), '/');
        if ($base === '') return null;

        return Cache::remember('frontend:guide:'.$format.':'.$modality.':'.$slug, now()->addMinutes(10), function () use ($base, $format, $modality, $slug): ?array {
            try {
                $response = Http::acceptJson()->timeout(6)->retry(1, 150)->get($base.'/api/catalog/guides/'.rawurlencode($format).'/'.rawurlencode($modality).'/'.rawurlencode($slug));
                return $response->successful() && is_array($response->json('guide')) ? $response->json('guide') : null;
            } catch (\Throwable $exception) {
                report($exception);
                return null;
            }
        });
    }
}
