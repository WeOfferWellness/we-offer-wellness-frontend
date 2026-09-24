<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BackendAvailabilityClient
{
    public function forUsers(array $userIds, array $durations = [], int $days = 30, bool $includeSlots = false): array
    {
        $userIds = collect($userIds)
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($userIds === []) {
            return [];
        }

        $baseUrl = rtrim((string) env('BACKEND_URL', env('VITE_BACKEND_URL', env('BACKEND_ASSET_URL', ''))), '/');
        if ($baseUrl === '') {
            return [];
        }

        $cacheKey = 'backend:availability:'.sha1(json_encode([$baseUrl, $userIds, $durations, $days, $includeSlots]));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($baseUrl, $userIds, $durations, $days, $includeSlots): array {
            try {
                $response = Http::acceptJson()
                    ->withHeaders([
                        'Origin' => config('app.url'),
                        'Referer' => rtrim((string) config('app.url'), '/').'/',
                    ])
                    ->timeout(8)
                    ->retry(1, 150)
                    ->post($baseUrl.'/api/availability/batch', [
                        'user_ids' => $userIds,
                        'durations' => $durations,
                        'days' => $days,
                        'include_slots' => $includeSlots,
                    ]);
            } catch (\Throwable) {
                return [];
            }

            $data = $response->successful() ? $response->json('data', []) : [];

            return is_array($data) ? $data : [];
        });
    }
}
