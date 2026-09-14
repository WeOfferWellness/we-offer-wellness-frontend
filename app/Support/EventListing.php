<?php

namespace App\Support;

use Carbon\Carbon;

class EventListing
{
    /**
     * Determine the timezone to use when interpreting a listing's date fields.
     */
    public static function timezone(mixed $item): string
    {
        $timezone = trim((string) data_get(
            $item,
            'when.event.timezone',
            data_get($item, 'event.timezone', data_get($item, 'timezone', data_get($item, 'meta_json.timezone', config('app.timezone', 'UTC'))))
        ));

        return $timezone !== '' ? $timezone : config('app.timezone', 'UTC');
    }

    /**
     * Parse the next available start timestamp we can infer from the item.
     */
    public static function startAt(mixed $item): ?Carbon
    {
        $timezone = self::timezone($item);
        $dates = [];
        // Read through accessors before normalising Eloquent models to arrays.
        // OfferingV3 exposes event dates via its `when` and `event` accessors.
        $queue = [[
            'starts_at' => data_get($item, 'starts_at'),
            'start_date' => data_get($item, 'start_date'),
            'start_time' => data_get($item, 'start_time'),
            'date' => data_get($item, 'date'),
            'when' => data_get($item, 'when', []),
            'event' => data_get($item, 'event', []),
            'meta_json' => data_get($item, 'meta_json', []),
            'dates' => data_get($item, 'dates', []),
            'upcoming_dates' => data_get($item, 'upcoming_dates', []),
            'occurrences' => data_get($item, 'occurrences', []),
            'schedule' => data_get($item, 'schedule', []),
        ]];
        $visited = 0;

        while ($queue !== [] && $visited < 64) {
            $visited++;
            $current = array_shift($queue);

            if ($current instanceof \Illuminate\Support\Collection) {
                $current = $current->all();
            } elseif ($current instanceof \Traversable) {
                $current = iterator_to_array($current, false);
            } elseif (is_object($current)) {
                $current = (array) $current;
            }

            if (! is_array($current)) {
                continue;
            }

            $time = self::valueFrom($current, ['start_time', 'time']);
            foreach (['starts_at', 'start_date', 'date', 'start', 'day'] as $key) {
                $parsed = self::parseStartValue(data_get($current, $key), $time, $timezone);
                if ($parsed) {
                    $dates[] = $parsed;
                }
            }

            foreach (['when.event', 'event', 'meta_json.when.event', 'meta_json.event', 'dates', 'upcoming_dates', 'occurrences', 'schedule.days', 'schedule.occurrences'] as $path) {
                $nested = data_get($current, $path);
                if ($nested !== null && $nested !== []) {
                    $queue[] = $nested;
                }
            }
        }

        if ($dates === []) {
            return null;
        }

        usort($dates, fn (Carbon $left, Carbon $right) => $left->getTimestamp() <=> $right->getTimestamp());
        $today = Carbon::now($timezone)->startOfDay();

        foreach ($dates as $date) {
            if ($date->gte($today)) {
                return $date;
            }
        }

        return $dates[0];
    }

    private static function valueFrom(array $source, array $keys): string
    {
        foreach ($keys as $key) {
            $value = data_get($source, $key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    private static function parseStartValue(mixed $date, string $time, string $timezone): ?Carbon
    {
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->setTimezone($timezone);
        }

        if (! is_scalar($date)) {
            return null;
        }

        $rawDate = trim((string) $date);
        if ($rawDate === '' || preg_match('/^(?:mon|tue|wed|thu|fri|sat|sun)(?:day)?$/i', $rawDate) || preg_match('/^\d{1,2}$/', $rawDate)) {
            return null;
        }

        try {
            return Carbon::parse($rawDate . ($time !== '' ? ' ' . $time : ''), $timezone);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse the latest end timestamp we can infer from the item.
     */
    public static function endAt(mixed $item, ?Carbon $startAt = null): ?Carbon
    {
        $timezone = self::timezone($item);
        $date = self::dateValue($item, [
            'when.event.end_date',
            'event.end_date',
            'end_date',
            'meta_json.end_date',
        ]);
        $time = self::dateValue($item, [
            'when.event.end_time',
            'event.end_time',
            'end_time',
            'meta_json.end_time',
        ]);

        if ($date === '' && ! $startAt) {
            return null;
        }

        $rawDate = $date !== '' ? $date : ($startAt ? $startAt->toDateString() : '');
        if ($rawDate === '') {
            return null;
        }

        $raw = $rawDate;
        if ($time !== '') {
            $raw .= ' ' . $time;
        }

        try {
            $parsed = Carbon::parse($raw, $timezone);
        } catch (\Throwable $e) {
            $parsed = $startAt ? $startAt->copy() : null;
        }

        if (! $parsed) {
            return $startAt ? $startAt->copy() : null;
        }

        if ($time === '' && ! self::hasTimeComponent($rawDate)) {
            return $parsed->copy()->endOfDay();
        }

        return $parsed;
    }

    /**
     * Return true when the listing has a date in the past and should be hidden from feeds.
     */
    public static function isPast(mixed $item, ?Carbon $now = null): bool
    {
        $startAt = self::startAt($item);
        $endAt = self::endAt($item, $startAt);

        if (! $startAt && ! $endAt) {
            return false;
        }

        $timezone = self::timezone($item);
        $now = $now ? $now->copy()->setTimezone($timezone) : Carbon::now($timezone);

        $effectiveEnd = $endAt ?: $startAt;
        if (! $effectiveEnd) {
            return false;
        }

        if ($endAt && ! self::hasExplicitTime($item, [
            'when.event.end_date',
            'event.end_date',
            'end_date',
            'meta_json.end_date',
        ], [
            'when.event.end_time',
            'event.end_time',
            'end_time',
            'meta_json.end_time',
        ])) {
            $effectiveEnd = $effectiveEnd->copy()->endOfDay();
        } elseif (! $endAt && $startAt && ! self::hasExplicitTime($item, [
            'when.event.start_date',
            'event.start_date',
            'start_date',
            'date',
            'meta_json.start_date',
            'meta_json.date',
        ], [
            'when.event.start_time',
            'event.start_time',
            'start_time',
            'meta_json.start_time',
        ])) {
            $effectiveEnd = $effectiveEnd->copy()->endOfDay();
        }

        return $effectiveEnd->lt($now);
    }

    /**
     * Return true if the item appears to have any scheduled date at all.
     */
    public static function hasScheduledDate(mixed $item): bool
    {
        return self::startAt($item) !== null || self::endAt($item) !== null;
    }

    /**
     * Return true when the listing looks like an event/workshop entry rather than a general service.
     */
    public static function isEventLike(mixed $item): bool
    {
        $textValue = static function (mixed $value): string {
            if (is_array($value)) {
                $value = reset($value);
            }

            if (is_object($value)) {
                $value = data_get($value, 'name', data_get($value, 'title', data_get($value, 'slug', '')));
            }

            return trim((string) $value);
        };

        $scheduleType = strtolower(trim((string) data_get($item, 'when.type', data_get($item, 'event.type', ''))));
        if (in_array($scheduleType, ['event', 'workshop', 'class', 'retreat'], true)) {
            return true;
        }

        $haystack = strtolower(trim(implode(' ', array_filter([
            $textValue(data_get($item, 'title')),
            $textValue(data_get($item, 'slug')),
            $textValue(data_get($item, 'handle')),
            $textValue(data_get($item, 'type')),
            $textValue(data_get($item, 'type.name')),
            $textValue(data_get($item, 'type_label')),
            $textValue(data_get($item, 'product_type')),
            $textValue(data_get($item, 'category')),
            $textValue(data_get($item, 'category.name')),
            $textValue(data_get($item, 'summary')),
            $textValue(data_get($item, 'description')),
            $textValue(data_get($item, 'event.title')),
        ]))));

        if ($haystack === '') {
            return self::hasScheduledDate($item);
        }

        foreach ([
            'event',
            'events',
            'workshop',
            'workshops',
            'seminar',
            'masterclass',
            'webinar',
            'conference',
            'meetup',
            'talk',
            'festival',
            'circle',
            'retreat',
            'class',
        ] as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private static function dateValue(mixed $item, array $paths): string
    {
        foreach ($paths as $path) {
            $value = trim((string) data_get($item, $path, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function hasTimeComponent(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        return str_contains($value, 'T') || (bool) preg_match('/\b\d{1,2}:\d{2}\b/', $value);
    }

    private static function hasExplicitTime(mixed $item, array $datePaths, array $timePaths): bool
    {
        foreach ($datePaths as $path) {
            if (self::hasTimeComponent((string) data_get($item, $path, ''))) {
                return true;
            }
        }

        foreach ($timePaths as $path) {
            if (trim((string) data_get($item, $path, '')) !== '') {
                return true;
            }
        }

        return false;
    }
}
