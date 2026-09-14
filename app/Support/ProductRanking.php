<?php

namespace App\Support;

use App\Models\OfferingV3;
use App\Models\VendorAvailability;
use App\Models\VendorDetail;
use App\Services\AvailabilityWindowService;
use App\Services\BackendOfferingsClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductRanking
{
    public static function sortCollection(Collection $items, string $sort = 'popular'): Collection
    {
        $sort = strtolower(trim($sort));

        if (! app()->runningInConsole() && (string) request()->cookie('wow_visitor_id') !== '' && ! request()->filled('sort')) {
            return app(BackendOfferingsClient::class)->reorder($items);
        }

        if ($sort === 'newest') {
            return $items->sortByDesc(fn ($item) => optional(data_get($item, 'created_at'))->timestamp ?? 0)->values();
        }

        if ($sort === 'price_asc') {
            return $items->sortBy(fn ($item) => self::priceValue($item) ?? PHP_FLOAT_MAX)->values();
        }

        if ($sort === 'price_desc') {
            return $items->sortByDesc(fn ($item) => self::priceValue($item) ?? 0.0)->values();
        }

        if (in_array($sort, ['review_count_desc', 'reviews_desc', 'most_reviewed'], true)) {
            return $items->sort(function ($left, $right) {
                foreach ([
                    [self::reviewCountValue($left), self::reviewCountValue($right)],
                    [self::ratingValue($left), self::ratingValue($right)],
                ] as [$leftValue, $rightValue]) {
                    if (self::compareValues($leftValue, $rightValue)) {
                        continue;
                    }

                    return $rightValue <=> $leftValue;
                }

                $leftRank = optional(data_get($left, 'created_at'))->timestamp ?? 0;
                $rightRank = optional(data_get($right, 'created_at'))->timestamp ?? 0;

                if ($leftRank === $rightRank) {
                    return strcasecmp(self::titleValue($left), self::titleValue($right));
                }

                return $rightRank <=> $leftRank;
            })->values();
        }

        if ($sort === 'rating_desc') {
            return $items->sort(function ($left, $right) {
                foreach ([
                    [self::ratingValue($left), self::ratingValue($right)],
                    [self::reviewCountValue($left), self::reviewCountValue($right)],
                ] as [$leftValue, $rightValue]) {
                    if (self::compareValues($leftValue, $rightValue)) {
                        continue;
                    }

                    return $rightValue <=> $leftValue;
                }

                return strcasecmp(self::titleValue($left), self::titleValue($right));
            })->values();
        }

        return self::diversifyByVendor(
            $items->sort(fn ($left, $right) => self::compareDefault($left, $right))->values()
        );
    }

    public static function compareDefault(mixed $left, mixed $right): int
    {
        foreach ([
            [self::availabilityPriority($left), self::availabilityPriority($right)],
            [self::itemKindPriority($left), self::itemKindPriority($right)],
            [self::planPriority($left), self::planPriority($right)],
            [self::ratingValue($left), self::ratingValue($right)],
            [self::reviewCountValue($left), self::reviewCountValue($right)],
        ] as [$leftValue, $rightValue]) {
            if (self::compareValues($leftValue, $rightValue)) {
                continue;
            }

            return $rightValue <=> $leftValue;
        }

        return strcasecmp(self::titleValue($left), self::titleValue($right));
    }

    public static function diversifyByVendor(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $groups = $items
            ->groupBy(fn ($item) => self::vendorKey($item))
            ->sort(function (Collection $leftGroup, Collection $rightGroup) {
                return self::compareDefault($rightGroup->first(), $leftGroup->first());
            })
            ->values();

        $queues = $groups->map(function (Collection $group) {
            return $group->values();
        })->all();

        $diversified = collect();
        $exhausted = false;

        while (! $exhausted) {
            $exhausted = true;

            foreach ($queues as $index => $queue) {
                if ($queue->isEmpty()) {
                    continue;
                }

                $exhausted = false;
                $diversified->push($queue->shift());
                $queues[$index] = $queue;
            }
        }

        return $diversified->values();
    }

    public static function planPriority(mixed $item): int
    {
        $priority = data_get($item, 'plan_priority');
        if (is_numeric($priority)) {
            return (int) $priority;
        }

        $vendor = data_get($item, 'vendor');
        if (! $vendor instanceof VendorDetail) {
            return 0;
        }

        return (int) (self::resolveVendorPlan($vendor)['priority'] ?? 0);
    }

    public static function itemKindPriority(mixed $item): int
    {
        if ($item instanceof OfferingV3) {
            return 1;
        }

        $kind = strtolower(trim((string) data_get($item, 'item_kind', data_get($item, 'kind', 'product'))));

        return in_array($kind, ['offering', 'offerings', 'service', 'services'], true) ? 1 : 0;
    }

    public static function availabilityPriority(mixed $item): int
    {
        if (self::hasAvailabilityFlag($item)) {
            return 1;
        }

        $user = self::vendorUser($item);
        if (! $user) {
            return 0;
        }

        try {
            $weekly = AvailabilityWindowService::buildWeeklyWindows($user);
            foreach ($weekly as $day) {
                if (! empty($day['enabled']) && ! empty($day['windows'])) {
                    return 1;
                }
            }

            return VendorAvailability::query()
                ->where('user_id', $user->id)
                ->where('is_available', true)
                ->exists() ? 1 : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function ratingValue(mixed $item): float
    {
        $rating = data_get($item, 'rating', data_get($item, 'reviews_avg_rating', 0));

        return is_numeric($rating) ? (float) $rating : 0.0;
    }

    public static function reviewCountValue(mixed $item): int
    {
        foreach ([
            data_get($item, 'vendor_review_count'),
            data_get($item, 'vendor_reviews_count'),
            data_get($item, 'vendor.review_summary.count'),
            data_get($item, 'vendor.reviews_count'),
            data_get($item, 'vendor.review_count'),
            data_get($item, 'review_count'),
            data_get($item, 'reviews_count'),
        ] as $candidate) {
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }
        }

        return 0;
    }

    public static function priceValue(mixed $item): ?float
    {
        $price = data_get($item, 'price', data_get($item, 'variants_min_price', null));
        if (! is_numeric($price)) {
            return null;
        }

        $price = (float) $price;
        if ($price > 1000 && fmod($price, 100) === 0.0) {
            $price = $price / 100;
        }

        return $price;
    }

    public static function titleValue(mixed $item): string
    {
        return strtolower(trim((string) data_get($item, 'title', '')));
    }

    public static function vendorKey(mixed $item): string
    {
        $vendorId = data_get($item, 'vendor_id');
        if (is_numeric($vendorId) && (int) $vendorId > 0) {
            return 'vendor:'.(int) $vendorId;
        }

        $vendorName = trim((string) data_get($item, 'vendor_name', data_get($item, 'vendor.vendor_name', '')));
        if ($vendorName !== '') {
            return 'vendor:'.strtolower($vendorName);
        }

        $itemType = get_debug_type($item);
        $itemId = data_get($item, 'id');

        return $itemType.':'.(string) $itemId;
    }

    public static function resolveVendorPlan(?VendorDetail $vendor): array
    {
        $tierValue = null;

        try {
            $tier = null;
            if ($vendor) {
                if ($vendor->relationLoaded('tiers')) {
                    $tier = $vendor->tiers
                        ->sortByDesc(fn ($row) => $row->plan_started_at ?? $row->id ?? 0)
                        ->first();
                } else {
                    $tier = $vendor->tiers()->orderByDesc('plan_started_at')->orderByDesc('id')->first();
                }
            }
            $tierValue = (string) ($tier?->tier ?? '');
        } catch (\Throwable $e) {
            $tierValue = '';
        }

        $key = self::canonicalPlanKey($tierValue);
        $isStarter = $key === 'starter' || $key === '';

        return [
            'key' => $key ?: 'starter',
            'label' => self::planTitleForKey($key ?: 'starter'),
            'priority' => $isStarter ? 0 : 1,
        ];
    }

    public static function canonicalPlanKey(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = str_replace(['_', ' '], '-', $normalized);

        return match ($normalized) {
            'community', 'starter', 'standard', 'free-starter', 'starter-package' => 'starter',
            'core', 'business-accelerator', 'business-accelerator-package', 'businessaccelerator' => 'business-accelerator',
            'premium', 'premium-accelerator', 'premiumaccelerator' => 'premium-accelerator',
            'become-partner', 'partner' => 'become-partner',
            default => $normalized,
        };
    }

    public static function planTitleForKey(?string $value): string
    {
        return match (self::canonicalPlanKey($value)) {
            'starter' => 'Starter',
            'business-accelerator' => 'Business Accelerator',
            'premium-accelerator' => 'Premium Accelerator',
            'become-partner' => 'Become Partner',
            default => Str::headline(trim((string) $value)) ?: 'Plan',
        };
    }

    private static function hasAvailabilityFlag(mixed $item): bool
    {
        foreach (['has_availability', 'availability_set', 'availability_available'] as $key) {
            $value = data_get($item, $key);
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                return $value;
            }

            $filtered = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($filtered !== null) {
                return $filtered;
            }

            if (is_numeric($value)) {
                return (int) $value > 0;
            }

            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        $days = data_get($item, 'availability_days');
        if (is_array($days) && $days !== []) {
            return true;
        }

        $calendar = data_get($item, 'availability_calendar');
        if (is_array($calendar) && $calendar !== []) {
            return true;
        }

        return false;
    }

    private static function vendorUser(mixed $item): mixed
    {
        $user = data_get($item, 'vendor.user');
        if ($user) {
            return $user;
        }

        return null;
    }

    private static function compareValues(mixed $left, mixed $right): bool
    {
        if (is_float($left) || is_float($right)) {
            return abs((float) $left - (float) $right) < 0.000001;
        }

        return $left === $right;
    }
}
