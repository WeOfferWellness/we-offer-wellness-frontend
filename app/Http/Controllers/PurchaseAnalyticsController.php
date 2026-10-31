<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PurchaseAnalyticsService;
use Illuminate\Http\JsonResponse;

class PurchaseAnalyticsController extends Controller
{
    public function acknowledge(Order $order, PurchaseAnalyticsService $purchaseAnalytics): JsonResponse
    {
        $acknowledged = $purchaseAnalytics->acknowledge($order);

        return response()->json([
            'acknowledged' => $acknowledged,
            'transaction_id' => $purchaseAnalytics->transactionId($order),
        ], $acknowledged ? 200 : 409);
    }
}
