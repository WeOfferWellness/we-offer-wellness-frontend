<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DiscountCodeClient
{
    public function quote(string $code, int $subtotal, array $items = []): array
    {
        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');
        if ($baseUrl === '' || trim($code) === '' || $subtotal < 1) {
            return ['valid' => false, 'message' => 'Enter a valid discount code.'];
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'Origin' => rtrim((string) config('app.url'), '/'),
                    'Referer' => rtrim((string) config('app.url'), '/').'/cart',
                ])
                ->connectTimeout(3)
                ->timeout(6)
                ->post($baseUrl.'/api/discount-codes/quote', [
                    'code' => strtoupper(trim($code)),
                    'subtotal' => $subtotal,
                    'items' => $items,
                ]);
        } catch (\Throwable) {
            return ['valid' => false, 'unavailable' => true, 'message' => 'Discount codes are temporarily unavailable. Please try again.'];
        }

        $payload = $response->json();
        if (is_array($payload) && array_key_exists('valid', $payload)) {
            return $payload;
        }

        return ['valid' => false, 'unavailable' => true, 'message' => 'Discount codes are temporarily unavailable. Please try again.'];
    }
}
