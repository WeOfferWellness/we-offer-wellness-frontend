<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfferingV3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Keeps the storefront on its own origin while delegating slot ownership to
 * the Backend reservation service.  The Backend is the sole authority for a
 * hold expiry and slot-conflict decision.
 */
class OfferingReservationProxyController extends Controller
{
    public function hold(Request $request, OfferingV3 $offering)
    {
        $payload = $request->validate([
            'slot_date' => ['required', 'date'],
            'slot_start' => ['required', 'date_format:H:i'],
            'slot_end' => ['required', 'date_format:H:i'],
            'price_option_id' => ['nullable', 'integer'],
            'reservation_id' => ['nullable', 'integer'],
        ]);

        $baseUrl = rtrim((string) config('services.backend_url', ''), '/');
        if ($baseUrl === '') {
            return response()->json(['message' => 'Booking service is unavailable.'], 503);
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Origin' => (string) config('app.url'),
                    'Referer' => rtrim((string) config('app.url'), '/').'/',
                ])
                ->timeout(10)
                ->post($baseUrl.'/api/booking/offering/'.$offering->getKey().'/hold', $payload);
        } catch (\Throwable) {
            return response()->json(['message' => 'Booking service is unavailable.'], 503);
        }

        return response()->json($response->json() ?: ['message' => 'Unable to hold this slot.'], $response->status());
    }
}
