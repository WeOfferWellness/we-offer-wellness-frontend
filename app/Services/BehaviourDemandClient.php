<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BehaviourDemandClient
{
    public function insights(): array
    {
        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');
        if ($baseUrl === '') {
            return [];
        }

        try {
            $response = Http::acceptJson()->timeout(6)->retry(1, 150)->withHeaders([
                'Origin' => config('app.url'),
                'Referer' => rtrim((string) config('app.url'), '/').'/',
            ])->get($baseUrl.'/api/behaviour/location-insights');
        } catch (\Throwable) {
            return [];
        }

        return $response->successful() && is_array($response->json()) ? $response->json() : [];
    }
}
