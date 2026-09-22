<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BackendOfferingsClient;

class ReviewStatsController extends Controller
{
    public function index(BackendOfferingsClient $offeringsClient)
    {
        $stats = $offeringsClient->reviewStats();
        $count = (int) ($stats['review_count'] ?? 0);
        $verifiedCount = (int) ($stats['verified_count'] ?? 0);
        if ($verifiedCount <= 0 && $count > 0) {
            $verifiedCount = $count;
        }

        return response()->json([
            'avg_rating' => $stats['avg_rating'] ?? null,
            'review_count' => $count,
            'verified_count' => $verifiedCount,
        ]);
    }
}
