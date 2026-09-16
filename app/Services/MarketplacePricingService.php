<?php

namespace App\Services;

use App\Models\VendorDetail;

class MarketplacePricingService
{
    public const RATE = 0.05;

    public function buyerPrice(float|int|string|null $amount, mixed $vendor = null, ?int $vendorId = null): float
    {
        $amount = max(0, (float) $amount);

        return $this->isWeOfferWellness($vendor, $vendorId)
            ? round($amount, 2)
            : round($amount * (1 + self::RATE), 2);
    }

    public function isWeOfferWellness(mixed $vendor = null, ?int $vendorId = null): bool
    {
        if ($vendorId && (! is_object($vendor) || ! $vendor instanceof VendorDetail)) {
            $vendor = VendorDetail::query()->with('user')->find($vendorId);
        }

        $values = [
            data_get($vendor, 'vendor_name'),
            data_get($vendor, 'name'),
            data_get($vendor, 'user.name'),
            data_get($vendor, 'user.email'),
        ];
        $needle = preg_replace('/[^a-z0-9]+/i', '', strtolower(implode(' ', array_filter($values))));

        return str_contains((string) $needle, 'weofferwellness');
    }
}
