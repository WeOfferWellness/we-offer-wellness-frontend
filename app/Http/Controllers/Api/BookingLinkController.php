<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfferingV3;
use App\Models\Product;
use App\Services\BookingContextBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BookingLinkController extends Controller
{
    public function __construct(
        private BookingContextBuilder $contextBuilder
    ) {}

    public function availability(Request $request, OfferingV3 $offering)
    {
        // Reservations are created by Backend, so it must also be the source
        // of truth for what a customer may select.  A local availability
        // snapshot can otherwise show a slot that the hold endpoint rejects.
        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');
        if ($baseUrl !== '') {
            try {
                $response = Http::acceptJson()
                    ->withHeaders([
                        'Origin' => (string) config('app.url'),
                        'Referer' => rtrim((string) config('app.url'), '/').'/',
                    ])
                    ->timeout(10)
                    ->get($baseUrl.'/api/booking/offering/'.$offering->getKey(), array_filter([
                        'price_option_id' => $this->resolvePriceOptionId($request),
                    ], static fn ($value) => $value !== null));

                if ($response->successful()) {
                    return response()->json($response->json() ?: ['bookingPayload' => []]);
                }

                return response()->json(
                    $response->json() ?: ['message' => 'Booking service is unavailable.'],
                    $response->status()
                );
            } catch (\Throwable) {
                return response()->json(['message' => 'Booking service is unavailable.'], 503);
            }
        }

        $context = $this->contextBuilder->buildForOffering(
            $offering,
            $this->resolvePriceOptionId($request),
            $this->resolveVariantLabel($request)
        );

        return response()->json([
            'bookingPayload' => $context['bookingPayload'],
        ]);
    }

    public function availabilityForProduct(Request $request, Product $product)
    {
        $context = $this->contextBuilder->buildForProduct(
            $product,
            $this->resolvePriceOptionId($request),
            $this->resolveVariantLabel($request)
        );

        return response()->json([
            'bookingPayload' => $context['bookingPayload'],
        ]);
    }

    private function resolvePriceOptionId(Request $request): ?int
    {
        $value = $request->input('price_option_id');
        if ($value === null || $value === '') {
            $value = $request->query('price_option_id');
        }

        if ($value === null || $value === '') {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);
        return $integer === false ? null : (int) $integer;
    }

    private function resolveVariantLabel(Request $request): ?string
    {
        $value = $request->input('variant_label');
        if ($value === null || trim((string) $value) === '') {
            $value = $request->query('variant_label');
        }

        if ($value === null || trim((string) $value) === '') {
            $value = $request->input('session_label');
        }
        if ($value === null || trim((string) $value) === '') {
            $value = $request->query('session_label');
        }

        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}
