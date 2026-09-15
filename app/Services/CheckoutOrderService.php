<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Models\OrderCustomer;
use App\Models\OrderItem;
use App\Models\OfferingV3;
use App\Models\PaymentDetail;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorClient;
use App\Models\VendorDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Notifications\OrderCreatedNotification;

class CheckoutOrderService
{
    public function finalizePaidAttempt(CheckoutAttempt $attempt, array $context = []): ?Order
    {
        return DB::transaction(function () use ($attempt, $context) {
            $fresh = CheckoutAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if (! $fresh) {
                return null;
            }

            $order = null;

            if ($fresh->order_id) {
                $order = Order::with(['items.product.vendor.user', 'customerProfile', 'paymentDetail'])->find($fresh->order_id);
            } elseif ($fresh->stripe_session_id) {
                $order = Order::with(['items.product.vendor.user', 'customerProfile', 'paymentDetail'])
                    ->where('stripe_session_id', $fresh->stripe_session_id)
                    ->first();
            }

            if (! $order) {
                $order = Order::create([
                    'user_id' => $fresh->user_id,
                    'email' => $fresh->email,
                    'currency' => strtoupper((string) $fresh->currency),
                    'amount_total' => (int) $fresh->amount_total,
                    'total_price' => round(((int) $fresh->amount_total) / 100, 2),
                    'status' => 'pending',
                    'stripe_session_id' => $context['stripe_session_id'] ?? $fresh->stripe_session_id,
                    'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? $fresh->stripe_payment_intent_id,
                ]);

                foreach ($fresh->items ?? [] as $id => $item) {
                    OrderItem::create($this->buildOrderItemPayload($order, $id, is_array($item) ? $item : []));
                }
            }

            $order = $this->reconcilePaidOrder($order, [
                'email' => $fresh->email,
                'user_id' => $fresh->user_id,
                'first_name' => trim((string) ($fresh->meta['first_name'] ?? '')),
                'last_name' => trim((string) ($fresh->meta['last_name'] ?? '')),
                'stripe_session_id' => $context['stripe_session_id'] ?? $fresh->stripe_session_id,
                'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? $fresh->stripe_payment_intent_id,
            ]);

            $fresh->order_id = $order->id;
            $fresh->status = 'completed';
            $fresh->stripe_session_id = $order->stripe_session_id;
            $fresh->stripe_payment_intent_id = $order->stripe_payment_intent_id;
            $fresh->save();

            return $order;
        });
    }

    public function reconcilePaidOrder(Order $order, array $context = []): Order
    {
        return DB::transaction(function () use ($order, $context) {
            $freshOrder = Order::with(['items.product.vendor.user', 'customerProfile', 'paymentDetail'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            $shouldSendReceipt = $freshOrder->status !== 'paid';
            $shouldSendEmails = (bool) ($context['send_emails'] ?? true);
            $customerUser = $this->resolveCustomerUser($freshOrder, $context);
            $customerEmail = $this->resolveCustomerEmail($freshOrder, $customerUser, $context);
            $providerUserIds = $this->providerUserIdsForOrder($freshOrder);

            $freshOrder->status = 'paid';
            if ($customerEmail !== '' && $freshOrder->email !== $customerEmail) {
                $freshOrder->email = $customerEmail;
            }

            $sessionId = trim((string) ($context['stripe_session_id'] ?? ''));
            if ($sessionId !== '' && $freshOrder->stripe_session_id !== $sessionId) {
                $freshOrder->stripe_session_id = $sessionId;
            }

            $paymentIntentId = trim((string) ($context['stripe_payment_intent_id'] ?? ''));
            if ($paymentIntentId !== '' && $freshOrder->stripe_payment_intent_id !== $paymentIntentId) {
                $freshOrder->stripe_payment_intent_id = $paymentIntentId;
            }

            if (count($providerUserIds) === 1 && (int) $freshOrder->user_id !== (int) $providerUserIds[0]) {
                $freshOrder->user_id = (int) $providerUserIds[0];
            }

            $freshOrder->save();

            $this->syncCustomerProfile($freshOrder, $customerUser, $customerEmail);
            $this->syncPaymentDetails($freshOrder);
            $bookingIds = $this->syncBookings($freshOrder, $customerUser, $customerEmail);
            if ($bookingIds !== []) {
                DB::afterCommit(function () use ($bookingIds): void {
                    app(StudioCalendarSyncService::class)->syncBookingIds($bookingIds);
                });
            }
            $this->syncVendorClients($freshOrder, $customerUser);

            $freshOrder = $freshOrder->fresh(['items.product.vendor.user', 'customerProfile', 'paymentDetail']);
            if ($shouldSendEmails) {
                $this->queueOrderEmails($freshOrder, $shouldSendReceipt);
            }

            return $freshOrder;
        });
    }

    protected function buildOrderItemPayload(Order $order, string|int $id, array $item): array
    {
        $title = (string) ($item['title'] ?? ('Item '.$id));
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $raw = (float) ($item['price'] ?? 0);
        $unit = (int) round($raw * 100);
        $linePrice = round($unit / 100, 2);
        $image = $item['image'] ?? $item['img'] ?? null;
        $productId = $item['product_id'] ?? $item['productId'] ?? null;
        $variantLabel = $item['variant_label'] ?? null;
        $variantOptions = $item['options'] ?? [];
        if (! is_array($variantOptions)) {
            $variantOptions = [];
        }

        $incomingMeta = is_array($item['meta'] ?? null) ? $item['meta'] : [];
        $bookingMeta = is_array($item['booking'] ?? null) ? $item['booking'] : [];
        $selected = is_array($item['selected'] ?? null) ? array_values(array_filter($item['selected'])) : [];
        $groupCount = $item['group_count'] ?? $item['groupCount'] ?? null;
        $reservationId = $item['reservation_id'] ?? $item['reservationId'] ?? null;
        $holdExpiresAt = $item['hold_expires_at'] ?? $item['holdExpiresAt'] ?? null;
        $location = $item['location'] ?? ($incomingMeta['location'] ?? null);
        $variantId = $item['variant_id'] ?? $item['variantId'] ?? null;
        $sourceVersion = strtolower(trim((string) ($item['source_version'] ?? ($incomingMeta['source_version'] ?? ''))));
        $offeringId = $item['offering_id'] ?? $item['offeringId'] ?? ($incomingMeta['offering_id'] ?? null);
        if ($offeringId === null && $sourceVersion === 'v3' && is_numeric($productId)) {
            $offeringId = (int) $productId;
        }
        if ($sourceVersion !== 'v3' && is_numeric($productId)) {
            try {
                if (! Product::query()->where('id', (int) $productId)->exists() && OfferingV3::query()->where('id', (int) $productId)->exists()) {
                    $sourceVersion = 'v3';
                    $offeringId = (int) $productId;
                }
            } catch (\Throwable $e) {
                // Keep the legacy product path if the fallback lookup fails.
            }
        }

        $meta = $incomingMeta;
        $meta['url'] = $item['url'] ?? ($meta['url'] ?? null);
        $meta['image'] = $image ?? ($meta['image'] ?? null);
        $meta['variant_label'] = $variantLabel ?? ($meta['variant_label'] ?? null);
        $meta['product_id'] = $productId ?? ($meta['product_id'] ?? null);
        if ($sourceVersion === 'v3' && is_numeric($offeringId) && (int) $offeringId > 0) {
            $meta['source_version'] = 'v3';
            $meta['offering_id'] = (int) $offeringId;
        }

        if (! empty($variantOptions)) {
            $meta['variant_options'] = $variantOptions;
        }
        if (! empty($bookingMeta)) {
            $meta['booking'] = array_filter($bookingMeta, fn ($value) => ! is_null($value) && $value !== '');
        }
        if (! empty($selected)) {
            $meta['selected'] = $selected;
        }
        if (! is_null($groupCount) && $groupCount !== '') {
            $meta['group_count'] = (int) $groupCount;
        }
        if (! is_null($reservationId) && $reservationId !== '') {
            $meta['reservation_id'] = (int) $reservationId;
        }
        if (! is_null($holdExpiresAt) && $holdExpiresAt !== '') {
            $meta['hold_expires_at'] = (string) $holdExpiresAt;
        }
        if (! is_null($location) && $location !== '') {
            $meta['location'] = (string) $location;
        }
        if (! is_null($variantId) && $variantId !== '') {
            $meta['variant_id'] = $variantId;
        }

        $meta = array_filter($meta, function ($value) {
            return ! is_null($value) && $value !== '' && $value !== [];
        });

        return [
            'order_id' => $order->id,
            'product_id' => $sourceVersion === 'v3' ? null : ($productId ?: 0),
            'vendor_id' => $this->resolveVendorIdForItemData($productId, $sourceVersion, $offeringId, $incomingMeta),
            'name' => $title,
            'sku' => (string) $id,
            'unit_amount' => $unit,
            'quantity' => $qty,
            'meta' => $meta,
            'price' => $linePrice,
        ];
    }

    protected function resolveCustomerUser(Order $order, array $context = []): ?User
    {
        $user = $context['customer_user'] ?? null;
        if ($user instanceof User) {
            return $user;
        }

        $userId = (int) ($context['user_id'] ?? 0);
        if ($userId > 0) {
            $found = User::find($userId);
            if ($found) {
                return $found;
            }
        }

        $email = trim((string) ($context['email'] ?? $order->email ?? ''));
        if ($email === '') {
            return null;
        }

        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $this->updateClientUser($existing, $order, $context);
        }

        return $this->createClientUser($order, $email, $context);
    }

    protected function resolveCustomerEmail(Order $order, ?User $customerUser, array $context = []): string
    {
        return trim((string) ($context['email'] ?? $customerUser?->email ?? $order->email ?? ''));
    }

    protected function syncCustomerProfile(Order $order, ?User $customerUser, string $customerEmail): void
    {
        if (! $this->hasTable('order_customers') || $customerEmail === '') {
            return;
        }

        $existing = $order->customerProfile;
        $profile = $existing ?: new OrderCustomer(['order_id' => $order->id]);

        $profile->order_id = $order->id;
        $profile->user_id = $customerUser?->id;
        $profile->email = $customerEmail;
        $profile->first_name = $customerUser?->first_name ?: ($existing?->first_name ?? null);
        $profile->last_name = $customerUser?->last_name ?: ($existing?->last_name ?? null);
        $profile->phone = $customerUser?->phone ?: ($existing?->phone ?? null);
        $profile->address = $existing?->address ?? null;
        $profile->save();
    }

    protected function syncPaymentDetails(Order $order): void
    {
        if (! $this->hasTable('payment_details')) {
            return;
        }

        $transactionId = trim((string) ($order->stripe_payment_intent_id ?: $order->stripe_session_id ?: ''));
        if ($transactionId === '') {
            $existing = PaymentDetail::where('order_id', $order->id)->first();
            if ($existing) {
                $existing->payment_method = 'stripe';
                $existing->payment_status = 'paid';
                $existing->save();
            }

            return;
        }

        PaymentDetail::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => $transactionId,
            ]
        );
    }

    protected function syncBookings(Order $order, ?User $customerUser, string $customerEmail): array
    {
        if (! $this->hasTable('bookings') || ! $this->hasTable('reservations')) {
            return [];
        }

        $order->loadMissing('items.product.vendor.user');
        $bookingIds = [];

        foreach ($order->items as $item) {
            $bookingData = $this->extractBookingData($item);
            if (! $bookingData) {
                continue;
            }

            $providerUserId = $this->providerUserIdForItem($item);
            if (! $providerUserId) {
                continue;
            }

            $clientName = trim((string) (($customerUser?->name ?: trim(($customerUser?->first_name ?? '').' '.($customerUser?->last_name ?? ''))) ?: $customerEmail));

            $reservation = null;
            $reservationId = (int) ($bookingData['reservation_id'] ?? 0);
            if ($reservationId > 0) {
                $candidate = Reservation::find($reservationId);
                if ($candidate && (int) $candidate->user_id === $providerUserId) {
                    $reservation = $candidate;
                }
            }

            if (! $reservation) {
                $reservation = Reservation::where('order_id', $order->id)
                    ->where('user_id', $providerUserId)
                    ->where('date', $bookingData['date'])
                    ->where('start_time', $bookingData['start_time'])
                    ->first();
            }

            $reservationPayload = [
                'user_id' => $providerUserId,
                'order_id' => $order->id,
                'client_name' => $clientName !== '' ? $clientName : null,
                'client_email' => $customerEmail !== '' ? $customerEmail : null,
                'date' => $bookingData['date'],
                'start_time' => $bookingData['start_time'],
                'end_time' => $bookingData['end_time'],
                'is_confirmed' => true,
            ];

            if ($reservation) {
                $reservation->fill($reservationPayload)->save();
            } else {
                $reservation = Reservation::create($reservationPayload);
            }

            $booking = Booking::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'offering_id' => $this->resolveOfferingIdForItem($item),
                    'date' => $bookingData['date'],
                    'start_time' => $bookingData['start_time'],
                ],
                [
                    'reservation_id' => $reservation->id,
                    'user_id' => $providerUserId,
                    'price_option_id' => null,
                    'price_amount' => is_numeric($item->price ?? null) ? (float) $item->price : round(((int) $item->unit_amount) / 100, 2),
                    'audience_type' => $this->resolveAudienceType($item),
                    'pricing_type' => 'per_session',
                    'channel' => $this->resolveChannel($item),
                    'end_time' => $bookingData['end_time'],
                    'title' => $item->name,
                    'client_name' => $clientName !== '' ? $clientName : null,
                    'client_email' => $customerEmail !== '' ? $customerEmail : null,
                    'session_format' => $this->resolveSessionFormat($item),
                ]
            );
            $bookingIds[] = (int) $booking->id;
        }

        return array_values(array_unique($bookingIds));
    }

    protected function syncVendorClients(Order $order, ?User $customerUser): void
    {
        if (! $customerUser || ! $this->hasTable('vendor_clients')) {
            return;
        }

        $order->loadMissing('items.product.vendor.user');

        $bookingsByProvider = collect();
        $reservationsByProvider = collect();

        if ($this->hasTable('bookings')) {
            $bookingsByProvider = Booking::where('order_id', $order->id)
                ->orderByDesc('id')
                ->get()
                ->unique('user_id')
                ->keyBy('user_id');
        }

        if ($this->hasTable('reservations')) {
            $reservationsByProvider = Reservation::where('order_id', $order->id)
                ->orderByDesc('id')
                ->get()
                ->unique('user_id')
                ->keyBy('user_id');
        }

        $linkTime = $order->created_at ?? now();

        $vendors = $order->items
            ->map(fn (OrderItem $item) => $this->resolveVendorForItem($item))
            ->filter()
            ->unique('id')
            ->values();

        foreach ($vendors as $vendor) {
            $providerUserId = (int) ($vendor->user_id ?? 0);
            if ($providerUserId <= 0) {
                continue;
            }

            $vendorClient = VendorClient::firstOrNew([
                'vendor_id' => (int) $vendor->id,
                'client_user_id' => (int) $customerUser->id,
            ]);

            if (! $vendorClient->exists && ! $vendorClient->first_booked_at) {
                $vendorClient->first_booked_at = $linkTime;
            }

            $booking = $bookingsByProvider->get($providerUserId);
            $reservation = $reservationsByProvider->get($providerUserId);

            if ($booking) {
                $vendorClient->booking_id = $booking->id;
            }

            if ($reservation) {
                $vendorClient->reservation_id = $reservation->id;
            }

            $vendorClient->last_booked_at = $linkTime;
            $vendorClient->save();
        }
    }

    protected function extractBookingData(OrderItem $item): ?array
    {
        $meta = is_array($item->meta) ? $item->meta : [];
        $booking = is_array($meta['booking'] ?? null) ? $meta['booking'] : [];

        $date = trim((string) ($booking['date'] ?? ''));
        $time = trim((string) ($booking['time'] ?? ''));
        if ($date === '' || $time === '') {
            return null;
        }

        try {
            $dateValue = Carbon::parse($date)->toDateString();
            $start = $this->parseTime($time);
        } catch (\Throwable $e) {
            return null;
        }

        $durationMinutes = $this->resolveDurationMinutes($meta['duration'] ?? null);
        $end = $start->copy()->addMinutes($durationMinutes);

        return [
            'date' => $dateValue,
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'reservation_id' => (int) ($meta['reservation_id'] ?? 0),
        ];
    }

    protected function parseTime(string $value): Carbon
    {
        $value = trim($value);
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return Carbon::createFromFormat('H:i:s', $value);
        }

        return Carbon::createFromFormat('H:i', substr($value, 0, 5));
    }

    protected function resolveDurationMinutes(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(15, (int) $value);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 60;
        }

        if (preg_match('/(\d+)\s*(hour|hr)/i', $text, $matches)) {
            return max(15, ((int) $matches[1]) * 60);
        }

        if (preg_match('/(\d+)/', $text, $matches)) {
            return max(15, (int) $matches[1]);
        }

        return 60;
    }

    protected function providerUserIdsForOrder(Order $order): array
    {
        $order->loadMissing('items.product.vendor.user');

        return $order->items
            ->map(fn (OrderItem $item) => $this->providerUserIdForItem($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function providerUserIdForItem(OrderItem $item): ?int
    {
        $vendor = $this->resolveVendorForItem($item);
        $user = $vendor?->user;

        return $user ? (int) $user->id : null;
    }

    protected function resolveAudienceType(OrderItem $item): ?string
    {
        $meta = is_array($item->meta) ? $item->meta : [];
        $groupCount = (int) ($meta['group_count'] ?? 0);
        if ($groupCount >= 3) {
            return 'group';
        }

        $selected = is_array($meta['selected'] ?? null) ? $meta['selected'] : [];
        $haystack = strtolower(implode(' ', array_map('strval', $selected)));

        if (str_contains($haystack, 'group') || str_contains($haystack, '3+')) {
            return 'group';
        }
        if (str_contains($haystack, '2 person')) {
            return 'pair';
        }
        if (str_contains($haystack, '1 person')) {
            return 'solo';
        }

        return null;
    }

    protected function resolveChannel(OrderItem $item): string
    {
        $meta = is_array($item->meta) ? $item->meta : [];
        $format = strtolower((string) ($meta['format'] ?? ''));
        $location = strtolower((string) ($meta['location'] ?? ''));

        if (str_contains($format, 'online') || $location === 'online') {
            return 'online';
        }
        if ($format !== '' || $location !== '') {
            return 'in_person';
        }

        return 'frontend_checkout';
    }

    protected function resolveSessionFormat(OrderItem $item): ?string
    {
        $meta = is_array($item->meta) ? $item->meta : [];

        foreach (['format', 'displayType', 'variant_label'] as $key) {
            $value = trim((string) ($meta[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function createClientUser(Order $order, string $email, array $context = []): User
    {
        $profile = $order->customerProfile;
        $firstName = trim((string) ($context['first_name'] ?? $profile?->first_name ?? ''));
        $lastName = trim((string) ($context['last_name'] ?? $profile?->last_name ?? ''));
        $fullName = trim((string) ($context['name'] ?? trim($firstName.' '.$lastName)));
        $phone = trim((string) ($context['phone'] ?? $profile?->phone ?? ''));

        $user = User::create([
            'email' => $email,
            'name' => $fullName !== '' ? $fullName : $email,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'phone' => $phone !== '' ? $phone : null,
            'password' => Hash::make(Str::random(24)),
            'is_active' => false,
        ]);

        $this->assignCustomerRole($user);

        return $user;
    }

    protected function updateClientUser(User $user, Order $order, array $context = []): User
    {
        $profile = $order->customerProfile;
        $firstName = trim((string) ($context['first_name'] ?? $profile?->first_name ?? ''));
        $lastName = trim((string) ($context['last_name'] ?? $profile?->last_name ?? ''));
        $fullName = trim((string) ($context['name'] ?? trim($firstName.' '.$lastName)));
        $phone = trim((string) ($context['phone'] ?? $profile?->phone ?? ''));

        $updates = [];
        if ($fullName !== '' && (string) $user->name !== $fullName) {
            $updates['name'] = $fullName;
        }
        if ($firstName !== '' && (string) $user->first_name !== $firstName) {
            $updates['first_name'] = $firstName;
        }
        if ($lastName !== '' && (string) $user->last_name !== $lastName) {
            $updates['last_name'] = $lastName;
        }
        if ($phone !== '' && (string) $user->phone !== $phone) {
            $updates['phone'] = $phone;
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        $this->assignCustomerRole($user);

        return $user;
    }

    protected function assignCustomerRole(User $user): void
    {
        $roleId = Role::query()
            ->whereRaw('LOWER(name) = ?', ['client'])
            ->value('id');

        if (! $roleId) {
            $roleId = Role::query()
                ->whereRaw('LOWER(name) = ?', ['user'])
                ->value('id');
        }

        if (! $roleId) {
            return;
        }

        $user->roles()->syncWithoutDetaching([$roleId]);
    }

    protected function resolveVendorIdForItemData(?int $productId, string $sourceVersion, mixed $offeringId, array $meta): ?int
    {
        $metaVendorId = $meta['vendor_id'] ?? null;
        if (is_numeric($metaVendorId) && (int) $metaVendorId > 0) {
            return (int) $metaVendorId;
        }

        if ($sourceVersion === 'v3' && is_numeric($offeringId) && (int) $offeringId > 0) {
            return OfferingV3::query()->whereKey((int) $offeringId)->value('vendor_id');
        }

        if ($productId && (int) $productId > 0) {
            return Product::query()->whereKey((int) $productId)->value('vendor_id');
        }

        return null;
    }

    protected function hasTable(string $table): bool
    {
        static $known = [];

        if (array_key_exists($table, $known)) {
            return $known[$table];
        }

        try {
            $known[$table] = Schema::hasTable($table);
        } catch (\Throwable $e) {
            $known[$table] = false;
        }

        return $known[$table];
    }

    protected function queueOrderEmails(?Order $order, bool $sendReceipt): void
    {
        if (! $order) {
            return;
        }

        DB::afterCommit(function () use ($order, $sendReceipt) {
            $freshOrder = $order->fresh(['items', 'customerProfile']);
            if (! $freshOrder) {
                return;
            }

            if ($sendReceipt) {
                TransactionalMail::orderReceipt($freshOrder);
            }

            $this->sendVendorEmailsOnce($freshOrder);
            $this->sendOrderNotificationsOnce($freshOrder);
        });
    }

    protected function sendOrderNotificationsOnce(Order $order): void
    {
        if ($order->status !== 'paid' || $order->order_notifications_sent_at) {
            return;
        }

        $order->loadMissing(['items.product.vendor.user', 'customerProfile']);

        $customerUser = $order->customerProfile?->user;
        if ($customerUser) {
            try {
                $customerUser->notify(new OrderCreatedNotification('client', $order));
            } catch (\Throwable $e) {
                logger()->error('Client order notification failed.', [
                    'order_id' => $order->id,
                    'user_id' => $customerUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $providerIds = $this->providerUserIdsForOrder($order);
        foreach (User::query()->whereIn('id', $providerIds)->get() as $provider) {
            try {
                $provider->notify(new OrderCreatedNotification('practitioner', $order));
            } catch (\Throwable $e) {
                logger()->error('Practitioner order notification failed.', [
                    'order_id' => $order->id,
                    'user_id' => $provider->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $admins = User::query()
            ->whereHas('roles', fn ($roles) => $roles->whereRaw('LOWER(name) = ?', ['admin']))
            ->get();
        foreach ($admins as $admin) {
            try {
                $admin->notify(new OrderCreatedNotification('admin', $order));
            } catch (\Throwable $e) {
                logger()->error('Admin order notification failed.', [
                    'order_id' => $order->id,
                    'user_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                TransactionalMail::adminOrderNotification($order, $admin);
            } catch (\Throwable $e) {
                logger()->error('Admin order email failed.', [
                    'order_id' => $order->id,
                    'user_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (Schema::hasColumn('orders', 'order_notifications_sent_at')) {
            $order->forceFill(['order_notifications_sent_at' => now()])->save();
        }
    }

    protected function sendVendorEmailsOnce(Order $order): void
    {
        if ($order->status !== 'paid') {
            return;
        }

        $updates = [];
        $canTrackVendorNotified = $this->hasColumn('orders', 'vendor_notified_at');
        $canTrackVendorIntro = $this->hasColumn('orders', 'vendor_introduction_sent_at');

        try {
            if ((! $canTrackVendorNotified || ! $order->vendor_notified_at) && TransactionalMail::vendorOrderNotification($order)) {
                if ($canTrackVendorNotified) {
                    $updates['vendor_notified_at'] = now();
                }
            }
        } catch (\Throwable $e) {
            logger()->error('Practitioner order email failed.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            if ((! $canTrackVendorIntro || ! $order->vendor_introduction_sent_at) && TransactionalMail::vendorIntroduction($order)) {
                if ($canTrackVendorIntro) {
                    $updates['vendor_introduction_sent_at'] = now();
                }
            }
        } catch (\Throwable $e) {
            logger()->error('Practitioner booking introduction email failed.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        if (! empty($updates)) {
            $order->forceFill($updates)->save();
        }
    }

    protected function hasColumn(string $table, string $column): bool
    {
        static $known = [];
        $key = $table.'.'.$column;

        if (array_key_exists($key, $known)) {
            return $known[$key];
        }

        try {
            $known[$key] = Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            $known[$key] = false;
        }

        return $known[$key];
    }

    protected function resolveVendorForItem(OrderItem $item): ?VendorDetail
    {
        $product = $item->product;
        if ($product?->vendor) {
            return $product->vendor;
        }

        $offering = $this->resolveOfferingForItem($item);
        return $offering?->vendor;
    }

    protected function resolveOfferingForItem(OrderItem $item): ?OfferingV3
    {
        $meta = is_array($item->meta) ? $item->meta : [];
        $sourceVersion = strtolower(trim((string) ($meta['source_version'] ?? '')));
        $offeringId = $this->resolveOfferingIdForItem($item);

        if ($sourceVersion !== 'v3' && ! $offeringId) {
            return null;
        }

        if (! $offeringId) {
            return null;
        }

        try {
            return OfferingV3::query()->with(['vendor.user', 'type', 'category', 'media', 'coverMedia'])->find($offeringId);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveOfferingIdForItem(OrderItem $item): ?int
    {
        $meta = is_array($item->meta) ? $item->meta : [];
        $metaOfferingId = $meta['offering_id'] ?? null;
        if (is_numeric($metaOfferingId) && (int) $metaOfferingId > 0) {
            return (int) $metaOfferingId;
        }

        $sourceVersion = strtolower(trim((string) ($meta['source_version'] ?? '')));
        if ($sourceVersion === 'v3' && is_numeric($item->product_id ?? null) && (int) $item->product_id > 0) {
            return (int) $item->product_id;
        }

        return is_numeric($item->product_id ?? null) && (int) $item->product_id > 0 ? (int) $item->product_id : null;
    }
}
