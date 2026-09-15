<?php

namespace App\Http\Controllers;

use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Models\OrderCustomer;
use App\Models\OrderItem;
use App\Models\OfferingV3;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class CheckoutController extends Controller
{
    protected const BOOKING_FEE_RATE = 0.05;

    public function createSession(Request $request)
    {
        $items = [];

        $payload = $request->input('items');
        if (is_array($payload) && !empty($payload)) {
            $normalized = [];
            $isList = array_is_list($payload);
            foreach ($payload as $key => $entry) {
                if (!is_array($entry)) continue;
                $id = $entry['id'] ?? ($isList ? null : $key);
                if (!$id) continue;
                $incomingMeta = is_array($entry['meta'] ?? null) ? $entry['meta'] : [];
                $incomingSelected = $entry['selected'] ?? ($incomingMeta['selected'] ?? []);
                if (!is_array($incomingSelected)) {
                    $incomingSelected = [];
                }
                $incomingBooking = $entry['booking'] ?? ($incomingMeta['booking'] ?? []);
                if (!is_array($incomingBooking)) {
                    $incomingBooking = [];
                }
                $incomingOptions = $entry['options'] ?? ($incomingMeta['variant_options'] ?? []);
                if (!is_array($incomingOptions)) {
                    $incomingOptions = [];
                }
                $normalized[(string)$id] = [
                    'id' => $id,
                    'product_id' => $entry['product_id'] ?? $entry['productId'] ?? null,
                    'vendor_id' => $entry['vendor_id'] ?? $entry['vendorId'] ?? null,
                    'variant_id' => $entry['variant_id'] ?? $entry['variantId'] ?? ($incomingMeta['variant_id'] ?? null),
                    'variant_label' => $entry['variant_label'] ?? $entry['options_label'] ?? null,
                    'source_version' => strtolower(trim((string) ($entry['source_version'] ?? ($incomingMeta['source_version'] ?? '')))),
                    'title' => (string)($entry['title'] ?? ('Item '.$id)),
                    'price' => (float)($entry['price'] ?? $entry['unit'] ?? 0),
                    'qty' => max(1, (int)($entry['qty'] ?? $entry['quantity'] ?? 1)),
                    'image' => $entry['image'] ?? $entry['img'] ?? null,
                    'url' => $entry['url'] ?? '#',
                    'meta' => $incomingMeta,
                    'booking' => $incomingBooking,
                    'selected' => array_values(array_filter($incomingSelected)),
                    'group_count' => $entry['group_count'] ?? $entry['groupCount'] ?? ($incomingMeta['group_count'] ?? $incomingMeta['groupCount'] ?? null),
                    'reservation_id' => $entry['reservation_id'] ?? $entry['reservationId'] ?? ($incomingMeta['reservation_id'] ?? null),
                    'hold_expires_at' => $entry['hold_expires_at'] ?? $entry['holdExpiresAt'] ?? ($incomingMeta['hold_expires_at'] ?? null),
                    'location' => $entry['location'] ?? ($incomingMeta['location'] ?? null),
                    'options' => $incomingOptions,
                ];

                if (
                    $normalized[(string)$id]['source_version'] === ''
                    && ! empty($normalized[(string)$id]['product_id'])
                ) {
                    try {
                        $legacyExists = Product::query()->where('id', (int) $normalized[(string)$id]['product_id'])->exists();
                        $v3Exists = OfferingV3::query()->where('id', (int) $normalized[(string)$id]['product_id'])->exists();
                        if (! $legacyExists && $v3Exists) {
                            $normalized[(string)$id]['source_version'] = 'v3';
                            $normalized[(string)$id]['meta']['source_version'] = 'v3';
                        }
                    } catch (\Throwable $e) {
                        Log::warning('checkout.create.source_version_infer_failed', ['e' => $e->getMessage()]);
                    }
                }
            }
            if (!empty($normalized)) {
                $items = $normalized;
                session(['cart.items' => $items]);
            }
        }

        if (empty($items)) {
            $items = session('cart.items', []);
        }
        if (empty($items)) {
            $cookieRaw = $request->cookie('wow_cart');
            if ($cookieRaw) {
                $restored = json_decode($cookieRaw, true) ?: [];
                if (is_array($restored) && !empty($restored)) {
                    $items = $restored;
                    session(['cart.items' => $items]);
                } else {
                    Log::warning('checkout.cookie.decode_failed', [
                        'len' => strlen($cookieRaw),
                        'raw_sample' => substr($cookieRaw, 0, 120),
                        'error' => json_last_error_msg(),
                    ]);
                }
            }
        }
        if (empty($items)) {
            Log::warning('checkout.empty_cart', [
                'session_has' => session()->has('cart.items'),
                'cookie_present' => (bool) $request->cookie('wow_cart'),
            ]);
            return response()->json(['ok'=>false,'error'=>'empty_cart'], 400);
        }

        $hasPhysicalProduct = collect($items)->contains(function ($item): bool {
            $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];
            $source = strtolower(trim((string) ($item['source_version'] ?? ($meta['source_version'] ?? ''))));
            $kind = strtolower(trim((string) ($meta['product_kind'] ?? $meta['type'] ?? '')));
            $id = strtolower(trim((string) ($item['id'] ?? '')));
            return $source === 'store'
                || str_starts_with($id, 'store-')
                || in_array($kind, ['physical', 'physical_product', 'store_product'], true);
        });

        // Build line items and compute totals
        $currency = 'gbp';
        $lineItems = [];
        $amountTotal = 0;
        foreach ($items as $id => $it) {
            $title = (string)($it['title'] ?? ('Item '.$id));
            $qty = max(1, (int)($it['qty'] ?? 1));
            $raw = (float)($it['price'] ?? 0);
            // Normalise to integer minor units (pence)
            // Cart prices are canonical major currency units. Explicit minor
            // units are converted at the boundary before they enter the cart.
            $unit = (int) round($raw * 100);
            $amountTotal += ($unit * $qty);
            $image = $it['image'] ?? $it['img'] ?? null;
            if ($image && !str_starts_with($image, 'http')) {
                $image = url($image);
            }
            $productData = [ 'name' => $title ];
            if ($image) {
                $productData['images'] = [$image];
            }
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => $productData,
                    'unit_amount' => $unit,
                ],
                'quantity' => $qty,
            ];
        }

        $bookingFee = $this->calculateBookingFee($amountTotal);
        if ($bookingFee > 0) {
            $lineItems[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'WOW Booking fee (5%)',
                            'description' => 'Booking fee applied at 5%',
                        ],
                        'unit_amount' => $bookingFee,
                    ],
                'quantity' => 1,
            ];
            $amountTotal += $bookingFee;
        }

        // Prepare checkout attempt (used to create the order only after payment succeeds)
        $attempt = null;
        $order = null;
        $guestFirstName = trim((string) $request->input('first_name', ''));
        $guestLastName = trim((string) $request->input('last_name', ''));
        $guestEmail = trim((string)$request->input('email', ''));
        $isGuestCheckout = ! $request->user();

        if ($isGuestCheckout && $guestFirstName === '') {
            return response()->json(['ok' => false, 'error' => 'first_name_required'], 422);
        }
        if ($guestEmail !== '' && !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok'=>false,'error'=>'invalid_email'], 422);
        }
        $resolvedEmail = optional($request->user())->email ?: $guestEmail;
        if (!$resolvedEmail) {
            return response()->json(['ok'=>false,'error'=>'email_required'], 422);
        }
        if ($this->hasCheckoutAttemptsTable()) {
            try {
                $attempt = CheckoutAttempt::create([
                    'user_id' => optional($request->user())->id,
                    'email' => $resolvedEmail,
                    'currency' => strtoupper($currency),
                    'amount_total' => $amountTotal,
                    'items' => $items,
                    'status' => 'pending',
                    'meta' => [
                        'first_name' => $guestFirstName,
                        'last_name' => $guestLastName,
                        'ip' => $request->ip(),
                        'user_agent' => substr((string)$request->userAgent(), 0, 255),
                        'booking_fee' => $bookingFee,
                        'booking_fee_rate' => self::BOOKING_FEE_RATE,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('checkout.create.attempt_failed', ['e' => $e->getMessage()]);
                return response()->json(['ok'=>false,'error'=>'order_failed'], 500);
            }
        } else {
            try {
                $order = $this->createPendingOrderFromItems(
                    $request,
                    $items,
                    $currency,
                    $amountTotal,
                    $resolvedEmail,
                    $guestFirstName,
                    $guestLastName
                );
            } catch (\Throwable $e) {
                Log::error('checkout.create.order_fallback_failed', ['e' => $e->getMessage()]);
                return response()->json(['ok'=>false,'error'=>'order_failed'], 500);
            }
        }

        // Create Stripe Checkout Session
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $metadata = [];
            if ($attempt) {
                $metadata['attempt_id'] = (string)$attempt->id;
            }
            if ($order) {
                $metadata['order_id'] = (string)$order->id;
            }

            $sessionPayload = [
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'metadata' => $metadata,
                'client_reference_id' => (string)($attempt?->id ?? $order?->id ?? ''),
                'customer_email' => $resolvedEmail,
                'success_url' => route('checkout.success', [], true).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel', [], true).'?session_id={CHECKOUT_SESSION_ID}',
            ];
            if ($hasPhysicalProduct) {
                $sessionPayload['shipping_address_collection'] = [
                    'allowed_countries' => ['GB'],
                ];
            }
            $session = StripeSession::create($sessionPayload);

            if ($attempt) {
                $attempt->stripe_session_id = $session->id ?? null;
                $attempt->save();
            }
            if ($order) {
                $order->stripe_session_id = $session->id ?? null;
                $order->save();
            }

            return response()->json(['ok'=>true,'url'=>$session->url]);
        } catch (\Throwable $e) {
            Log::error('checkout.create.stripe_failed', ['e' => $e->getMessage()]);
            if ($attempt) {
                $attempt->status = 'failed';
                $attempt->save();
            }
            if ($order && $order->status === 'pending') {
                $order->status = 'failed';
                $order->save();
            }
            return response()->json(['ok'=>false,'error'=>'stripe_failed'], 500);
        }
    }

    protected function hasCheckoutAttemptsTable(): bool
    {
        static $hasTable = null;
        if ($hasTable !== null) {
            return $hasTable;
        }

        try {
            $hasTable = Schema::hasTable((new CheckoutAttempt())->getTable());
        } catch (\Throwable $e) {
            Log::warning('checkout.create.attempt_table_check_failed', ['e' => $e->getMessage()]);
            $hasTable = false;
        }

        return $hasTable;
    }

    protected function calculateBookingFee(int $amountPence): int
    {
        return max(0, (int) round($amountPence * self::BOOKING_FEE_RATE));
    }

    protected function createPendingOrderFromItems(
        Request $request,
        array $items,
        string $currency,
        int $amountTotal,
        string $resolvedEmail,
        string $guestFirstName = '',
        string $guestLastName = ''
    ): Order {
        return DB::transaction(function () use ($request, $items, $currency, $amountTotal, $resolvedEmail, $guestFirstName, $guestLastName) {
            $orderPayload = [
                'user_id' => optional($request->user())->id,
                'email' => $resolvedEmail,
                'currency' => strtoupper($currency),
                'amount_total' => $amountTotal,
                'status' => 'pending',
            ];
            try {
                if (Schema::hasColumn('orders', 'total_price')) {
                    $orderPayload['total_price'] = round($amountTotal / 100, 2);
                }
            } catch (\Throwable $e) {
                Log::warning('checkout.create.orders_schema_check_failed', ['e' => $e->getMessage()]);
            }
            $order = Order::create($orderPayload);

            $hasProductId = false;
            $hasVendorId = false;
            $hasPrice = false;
            try {
                $hasProductId = Schema::hasColumn('order_items', 'product_id');
                $hasVendorId = Schema::hasColumn('order_items', 'vendor_id');
                $hasPrice = Schema::hasColumn('order_items', 'price');
            } catch (\Throwable $e) {
                Log::warning('checkout.create.order_items_schema_check_failed', ['e' => $e->getMessage()]);
            }

            foreach ($items as $id => $it) {
                $title = (string)($it['title'] ?? ('Item '.$id));
                $qty = max(1, (int)($it['qty'] ?? 1));
                $raw = (float)($it['price'] ?? 0);
                $unit = (int) round($raw * 100);
                $linePrice = round($unit / 100, 2);
                $image = $it['image'] ?? $it['img'] ?? null;
                $productId = $it['product_id'] ?? $it['productId'] ?? null;
                if (!$productId && isset($it['id'])) {
                    $itemId = (string)$it['id'];
                    if (is_numeric($itemId)) {
                        $productId = (int)$itemId;
                    } elseif (str_starts_with($itemId, 'p:') && is_numeric(substr($itemId, 2))) {
                        $productId = (int)substr($itemId, 2);
                    }
                }
                if (!$productId && is_string((string)$id) && str_starts_with((string)$id, 'p:') && is_numeric(substr((string)$id, 2))) {
                    $productId = (int)substr((string)$id, 2);
                }
                $vendorId = $it['vendor_id'] ?? $it['vendorId'] ?? null;
                $variantLabel = $it['variant_label'] ?? null;
                $variantOptions = $it['options'] ?? [];
                if (!is_array($variantOptions)) {
                    $variantOptions = [];
                }
                $incomingMeta = is_array($it['meta'] ?? null) ? $it['meta'] : [];
                $bookingMeta = is_array($it['booking'] ?? null) ? $it['booking'] : [];
                $selected = is_array($it['selected'] ?? null) ? array_values(array_filter($it['selected'])) : [];
                $groupCount = $it['group_count'] ?? $it['groupCount'] ?? null;
                $reservationId = $it['reservation_id'] ?? $it['reservationId'] ?? null;
                $holdExpiresAt = $it['hold_expires_at'] ?? $it['holdExpiresAt'] ?? null;
                $location = $it['location'] ?? ($incomingMeta['location'] ?? null);
                $variantId = $it['variant_id'] ?? $it['variantId'] ?? ($incomingMeta['variant_id'] ?? null);
                $sourceVersion = strtolower(trim((string) ($it['source_version'] ?? ($incomingMeta['source_version'] ?? ''))));
                if ($sourceVersion !== 'v3' && $productId) {
                    try {
                        if (! Product::query()->where('id', (int) $productId)->exists() && OfferingV3::query()->where('id', (int) $productId)->exists()) {
                            $sourceVersion = 'v3';
                        }
                    } catch (\Throwable $e) {
                        Log::warning('checkout.create.v3_fallback_check_failed', ['e' => $e->getMessage()]);
                    }
                }

                $meta = array_merge($incomingMeta, [
                    'url' => $it['url'] ?? ($incomingMeta['url'] ?? null),
                    'image' => $image ?? ($incomingMeta['image'] ?? null),
                    'variant_label' => $variantLabel ?? ($incomingMeta['variant_label'] ?? null),
                    'product_id' => $productId ?? ($incomingMeta['product_id'] ?? null),
                    'vendor_id' => $vendorId ?? ($incomingMeta['vendor_id'] ?? null),
                    'source_version' => $sourceVersion ?: ($incomingMeta['source_version'] ?? null),
                ]);
                if (!empty($variantOptions)) {
                    $meta['variant_options'] = $variantOptions;
                }
                if (!empty($bookingMeta)) {
                    $meta['booking'] = array_filter($bookingMeta, fn ($value) => !is_null($value) && $value !== '');
                }
                if (!empty($selected)) {
                    $meta['selected'] = $selected;
                }
                if (!is_null($groupCount) && $groupCount !== '') {
                    $meta['group_count'] = (int) $groupCount;
                }
                if (!is_null($reservationId) && $reservationId !== '') {
                    $meta['reservation_id'] = (int) $reservationId;
                }
                if (!is_null($holdExpiresAt) && $holdExpiresAt !== '') {
                    $meta['hold_expires_at'] = (string) $holdExpiresAt;
                }
                if (!is_null($location) && $location !== '') {
                    $meta['location'] = (string) $location;
                }
                if (!is_null($variantId) && $variantId !== '') {
                    $meta['variant_id'] = $variantId;
                }
                if ($sourceVersion === 'v3') {
                    $resolvedOfferingId = $productId ?: ($incomingMeta['offering_id'] ?? null);
                    if (is_numeric($resolvedOfferingId) && (int) $resolvedOfferingId > 0) {
                        $meta['offering_id'] = (int) $resolvedOfferingId;
                    }
                }

                $meta = array_filter($meta, function ($value) {
                    return !is_null($value) && $value !== '' && $value !== [];
                });

                $payload = [
                    'order_id' => $order->id,
                    'name' => $title,
                    'sku' => (string)$id,
                    'unit_amount' => $unit,
                    'quantity' => $qty,
                    'meta' => $meta,
                ];
                if ($hasProductId && ! in_array($sourceVersion, ['v3', 'store'], true)) {
                    // Legacy product-backed lines keep the foreign key; V3 offerings and Store products are stored in meta only.
                    $payload['product_id'] = $productId ?: 0;
                } elseif ($hasProductId) {
                    $payload['product_id'] = null;
                }
                if ($hasVendorId) {
                    $payload['vendor_id'] = $vendorId;
                }
                if ($hasPrice) {
                    $payload['price'] = $linePrice;
                }

                OrderItem::create($payload);
            }

            $this->syncPendingCustomerProfile($order, $request, $resolvedEmail, $guestFirstName, $guestLastName);

            return $order;
        });
    }

    protected function syncPendingCustomerProfile(
        Order $order,
        Request $request,
        string $resolvedEmail,
        string $guestFirstName = '',
        string $guestLastName = ''
    ): void {
        if (! Schema::hasTable('order_customers')) {
            return;
        }

        $user = $request->user();
        $firstName = trim((string) ($user?->first_name ?: $guestFirstName));
        $lastName = trim((string) ($user?->last_name ?: $guestLastName));
        $phone = trim((string) ($user?->phone ?? ''));

        OrderCustomer::updateOrCreate(
            ['order_id' => $order->id],
            [
                'user_id' => $user?->id,
                'first_name' => $firstName !== '' ? $firstName : null,
                'last_name' => $lastName !== '' ? $lastName : null,
                'email' => $resolvedEmail,
                'phone' => $phone !== '' ? $phone : null,
            ]
        );
    }
}
