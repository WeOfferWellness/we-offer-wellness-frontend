<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PurchaseAnalyticsService
{
    /**
     * Claim the one browser purchase dispatch for an order.
     *
     * The conditional update is deliberately performed in the database: page
     * refreshes, back/forward navigation and duplicate payment callbacks all
     * observe the same claim rather than relying on browser storage alone.
     */
    public function claim(Order $order): bool
    {
        if (! $this->available()) {
            Log::warning('analytics.purchase.claim_unavailable', ['order_id' => $order->getKey()]);
            return false;
        }

        $claimed = Order::query()
            ->whereKey($order->getKey())
            ->whereIn('status', ['paid', 'completed', 'complete', 'confirmed'])
            ->whereNull('analytics_purchase_emitted_at')
            ->update(['analytics_purchase_emitted_at' => now()]);

        if ($claimed === 0) {
            Log::info('analytics.purchase.duplicate_suppressed', [
                'order_id' => $order->getKey(),
                'transaction_id' => $this->transactionId($order),
            ]);
        }

        return $claimed === 1;
    }

    public function transactionId(Order $order): string
    {
        return 'WOW-' . (string) $order->getKey();
    }

    public function isSuccessful(Order $order): bool
    {
        return in_array(strtolower((string) $order->status), ['paid', 'completed', 'complete', 'confirmed'], true);
    }

    public function payload(Order $order): array
    {
        $order->loadMissing('items');
        $currency = strtoupper(trim((string) ($order->currency ?: 'GBP')));
        $items = $order->items->map(function ($item): array {
            $meta = is_array($item->meta ?? null) ? $item->meta : [];
            $catalogueType = (string) ($meta['catalogue_type'] ?? ($meta['source_version'] ?? 'offering'));

            return array_filter([
                'item_id' => (string) ($item->sku ?: $item->product_id ?: $item->id),
                'item_name' => (string) ($item->name ?: 'Item'),
                'price' => round(((int) ($item->unit_amount ?? 0)) / 100, 2),
                'quantity' => max(1, (int) ($item->quantity ?? 1)),
                'provider_id' => isset($item->vendor_id) ? (string) $item->vendor_id : null,
                'offering_id' => isset($meta['offering_id']) ? (string) $meta['offering_id'] : null,
                'product_id' => isset($item->product_id) && $item->product_id ? (string) $item->product_id : null,
                'catalogue_type' => $catalogueType,
                'modality' => isset($meta['modality']) ? (string) $meta['modality'] : null,
                'booking_source' => isset($meta['booking_source']) ? (string) $meta['booking_source'] : null,
                'checkout_source' => isset($meta['checkout_source']) ? (string) $meta['checkout_source'] : null,
            ], static fn ($value): bool => $value !== null && $value !== '');
        })->values()->all();

        return [
            'transaction_id' => $this->transactionId($order),
            'order_id' => (string) $order->getKey(),
            'value' => round(((int) ($order->amount_total ?? 0)) / 100, 2),
            'currency' => $currency !== '' ? $currency : 'GBP',
            'items' => $items,
            'checkout_status' => 'success',
            'checkout_source' => 'checkout-success',
        ];
    }

    private function available(): bool
    {
        try {
            return Schema::hasColumn((new Order())->getTable(), 'analytics_purchase_emitted_at');
        } catch (\Throwable $exception) {
            Log::warning('analytics.purchase.schema_check_failed', ['message' => $exception->getMessage()]);
            return false;
        }
    }
}
