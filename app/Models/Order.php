<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** @mixin \Illuminate\Database\Eloquent\Builder */

class Order extends Model
{
    use HasFactory;

    protected const CUSTOMER_VISIBLE_STATUSES = [
        'paid',
        'completed',
        'complete',
        'confirmed',
        'cancelled',
        'canceled',
        'refunded',
    ];

    protected $fillable = [
        'user_id',
        'email',
        'currency',
        'amount_total',
        'total_price',
        'status',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'vendor_notified_at',
        'vendor_introduction_sent_at',
        'order_notifications_sent_at',
        'analytics_purchase_emitted_at',
    ];

    protected $casts = [
        'vendor_notified_at' => 'datetime',
        'vendor_introduction_sent_at' => 'datetime',
        'order_notifications_sent_at' => 'datetime',
        'analytics_purchase_emitted_at' => 'datetime',
    ];

    /**
     * All line items included in the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Customer profile information captured at checkout.
     */
    public function customerProfile(): HasOne
    {
        return $this->hasOne(OrderCustomer::class);
    }

    public function customer(): HasOne
    {
        return $this->customerProfile();
    }

    /**
     * Shipping/contact detail record for physical items.
     */
    public function shippingDetail(): HasOne
    {
        return $this->hasOne(ShippingDetail::class);
    }

    public function shippingDetails(): HasOne
    {
        return $this->shippingDetail();
    }

    public function paymentDetail(): HasOne
    {
        return $this->hasOne(PaymentDetail::class);
    }

    public function paymentDetails(): HasOne
    {
        return $this->paymentDetail();
    }

    /**
     * Scope orders that are real customer-facing receipts, not pre-checkout shells.
     */
    public function scopeVisibleToCustomer(Builder $query): Builder
    {
        return $query->where(function (Builder $visible) {
            $visible->whereIn('status', self::CUSTOMER_VISIBLE_STATUSES)
                ->orWhereNotNull('stripe_payment_intent_id')
                ->orWhereHas('paymentDetail');
        });
    }

    /**
     * Scope orders that belong to the authenticated customer (by id or email).
     */
    public function scopeForCustomer(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inner) use ($user) {
            $inner->where('user_id', $user->id)
                ->orWhereHas('customerProfile', function (Builder $profileQuery) use ($user) {
                    $profileQuery->where('user_id', $user->id);
                    if ($user->email) {
                        $profileQuery->orWhere('email', $user->email);
                    }
                });
            if ($user->email) {
                $inner->orWhere('email', $user->email);
            }
        });
    }
}
