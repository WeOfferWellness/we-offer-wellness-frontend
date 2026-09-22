<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BehaviourDemandClient
{
    public function insights(): array
    {
        $baseUrl = rtrim((string) env('BACKEND_URL', env('BACKEND_ASSET_URL', '')), '/');
        if ($baseUrl === '') {
            return [];
        }

        try {
            $response = Http::acceptJson()->timeout(6)->retry(1, 150)->get($baseUrl.'/api/behaviour/location-insights');
        } catch (\Throwable) {
            return [];
        }

        return $response->successful() && is_array($response->json()) ? $response->json() : [];
    }
}
