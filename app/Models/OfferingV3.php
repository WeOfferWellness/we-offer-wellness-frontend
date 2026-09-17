<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OfferingV3 extends Model
{
    use HasFactory;

    protected ?array $eventContextCache = null;

    protected $table = 'offerings';

    protected $fillable = [
        'vendor_id',
        'status',
        'source_version',
        'title',
        'slug',
        'summary',
        'type_id',
        'category_id',
        'subcategory_id',
        'price',
        'cover_media_id',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(VendorDetail::class, 'vendor_id');
    }

    public function type()
    {
        return $this->belongsTo(ProductType::class, 'type_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(ProductSubcategory::class, 'subcategory_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'offering_id')->orderBy('order');
    }

    public function coverMedia()
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function getSummaryAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return $value;
        }

        $details = DB::table('offering_details')
            ->where('offering_id', $this->id)
            ->first();

        $source = (string) ($details->description ?? '');
        if ($source === '') {
            return null;
        }

        $cleaned = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($source, ENT_QUOTES | ENT_HTML5)) ?? ''));
        if ($cleaned === '') {
            return null;
        }

        return Str::limit(Str::words($cleaned, 30, ''), 150, '...');
    }

    public function getLocations(): array
    {
        try {
            $locations = [];

            $channels = DB::table('offering_channels')
                ->where('offering_id', $this->id)
                ->where('is_enabled', true)
                ->pluck('channel')
                ->all();

            if (in_array('online', $channels, true)) {
                $locations[] = 'Online';
            }

            if (in_array('in_person', $channels, true)) {
                $rows = DB::table('offering_locations')
                    ->where('offering_id', $this->id)
                    ->orderBy('sort_order')
                    ->get();

                foreach ($rows as $row) {
                    $rowOnline = data_get($row, 'online', data_get($row, 'is_online', null));
                    if (filter_var($rowOnline, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)) {
                        $locations[] = 'Online';
                        continue;
                    }

                    $label = trim((string) ($row->label ?? ''));
                    if ($label === '') {
                        $label = trim((string) ($row->city ?? ''));
                    }
                    if ($label === '') {
                        $label = trim((string) ($row->address_line_1 ?? ''));
                    }
                    if ($label !== '') {
                        $locations[] = Str::contains(Str::lower($label), 'online') ? 'Online' : $label;
                    }
                }
            }

            return array_values(array_unique($locations));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getFormattedLocations()
    {
        $locations = $this->getLocations();
        $isOnline = in_array('Online', $locations, true);
        $physicalLocations = array_diff($locations, ['Online']);
        $physicalCount = count($physicalLocations);

        if ($isOnline && $physicalCount === 0) {
            return 'Available only Online';
        } elseif ($isOnline && $physicalCount > 0) {
            return 'Available in ' . $physicalCount . ' locations & Online';
        } elseif ($physicalCount > 0) {
            return 'Available in ' . $physicalCount . ' locations';
        }

        return 'No locations available';
    }

    public function getFirstImageUrl()
    {
        $firstImage = $this->relationLoaded('coverMedia')
            ? $this->coverMedia
            : $this->coverMedia()->first();

        if (!$firstImage) {
            $firstImage = $this->relationLoaded('media')
                ? $this->media->first()
                : $this->media()->first();
        }

        if (!$firstImage) {
            return asset('assets/img/no-product-image.jpg');
        }

        $path = (string) ($firstImage->media_url ?? '');
        if ($path === '') {
            return asset('assets/img/no-product-image.jpg');
        }

        $url = null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $url = $path;
        } else {
            $backend = rtrim((string) env('BACKEND_ASSET_URL', env('BACKEND_URL', '')), '/');
            $clean = ltrim($path, '/');
            $url = $backend ? ($backend . '/storage/' . $clean) : asset('storage/' . $clean);
        }

        $atease = rtrim((string) env('ATEASE_BASE_URL', env('ALT_STORAGE_BASE', 'https://atease.weofferwellness.co.uk')), '/');
        try {
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                $parts = parse_url($url) ?: [];
                $p = ($parts['path'] ?? '/') ?: '/';
                $q = isset($parts['query']) && $parts['query'] !== '' ? ('?' . $parts['query']) : '';
                return $atease . $p . $q;
            }
        } catch (\Throwable $e) {
        }

        return $atease . '/' . ltrim((string) $url, '/');
    }

    public function hasDisplayableImage(): bool
    {
        return $this->getFirstImageUrl() !== asset('assets/img/no-product-image.jpg');
    }

    public function getEventAttribute(): array
    {
        return $this->resolveEventContext()['event'];
    }

    public function getWhenAttribute(): array
    {
        return $this->resolveEventContext();
    }

    private function resolveEventContext(): array
    {
        if ($this->eventContextCache !== null) {
            return $this->eventContextCache;
        }

        $schedule = DB::table('offering_schedule')
            ->where('offering_id', $this->id)
            ->first();

        $scheduleType = strtolower((string) ($schedule->schedule_type ?? ''));
        $timezone = (string) ($schedule->timezone ?? 'Europe/London');

        if ($scheduleType !== 'event') {
            return $this->eventContextCache = [
                'type' => $scheduleType !== '' ? $scheduleType : 'open',
                'event' => [],
            ];
        }

        $hasStartDate = Schema::hasColumn('offering_schedule', 'event_start_date');
        $hasStartTime = Schema::hasColumn('offering_schedule', 'event_start_time');
        $hasEndDate = Schema::hasColumn('offering_schedule', 'event_end_date');
        $hasEndTime = Schema::hasColumn('offering_schedule', 'event_end_time');
        $hasScheduleLater = Schema::hasColumn('offering_schedule', 'schedule_later');
        $hasEventSchedule = Schema::hasColumn('offering_schedule', 'event_schedule');

        $occurrences = DB::table('offering_fixed_occurrences')
            ->where('offering_id', $this->id)
            ->orderBy('starts_at')
            ->get();

        $groupSettings = DB::table('offering_group_settings')
            ->where('offering_id', $this->id)
            ->first();

        $eventScheduleLater = $hasScheduleLater ? (bool) ($schedule->schedule_later ?? false) : false;
        $eventSchedule = $hasEventSchedule ? $this->decodeEventSchedulePayload($schedule?->event_schedule ?? null) : [];
        $eventScheduleDays = $this->normalizeEventScheduleDays((array) Arr::get($eventSchedule, 'days', []));

        $startDate = $hasStartDate && ! empty($schedule?->event_start_date)
            ? (string) $schedule->event_start_date
            : '';
        $startTime = $hasStartTime && ! empty($schedule?->event_start_time)
            ? substr((string) $schedule->event_start_time, 0, 5)
            : '';
        $endDate = $hasEndDate && ! empty($schedule?->event_end_date)
            ? (string) $schedule->event_end_date
            : '';
        $endTime = $hasEndTime && ! empty($schedule?->event_end_time)
            ? substr((string) $schedule->event_end_time, 0, 5)
            : '';

        if ($startDate === '' && $eventScheduleDays !== []) {
            $firstScheduledDay = reset($eventScheduleDays);
            $lastScheduledDay = end($eventScheduleDays);
            if (is_array($firstScheduledDay) && ! empty($firstScheduledDay['date'])) {
                $startDate = (string) $firstScheduledDay['date'];
            }
            if (is_array($lastScheduledDay) && ! empty($lastScheduledDay['date'])) {
                $endDate = (string) $lastScheduledDay['date'];
            }
        }

        if ($occurrences->isNotEmpty()) {
            $firstOccurrence = $occurrences->first();
            if ($firstOccurrence?->starts_at) {
                $startAt = Carbon::parse($firstOccurrence->starts_at, $timezone);
                $endAt = $firstOccurrence->ends_at ? Carbon::parse($firstOccurrence->ends_at, $timezone) : null;

                if ($startDate === '') {
                    $startDate = $startAt->toDateString();
                }
                if ($startTime === '') {
                    $startTime = $startAt->format('H:i');
                }
                if ($endDate === '') {
                    $endDate = $endAt ? $endAt->toDateString() : $startDate;
                }
                if ($endTime === '') {
                    $endTime = $endAt ? $endAt->format('H:i') : $startTime;
                }
            }
        }

        if ($startDate === '') {
            return $this->eventContextCache = [
                'type' => 'event',
                'event' => [],
            ];
        }

        if ($endDate === '') {
            $endDate = $startDate;
        }

        $dates = $this->buildEventDateSeries($startDate, $endDate);
        if ($dates === [] && $eventScheduleDays !== []) {
            foreach ($eventScheduleDays as $dayIndex => $day) {
                if (! is_array($day) || empty($day['date'])) {
                    continue;
                }

                $dates[] = [
                    'index' => $dayIndex + 1,
                    'date' => (string) $day['date'],
                    'start_date' => (string) $day['date'],
                    'start_time' => $startTime,
                    'end_date' => (string) $day['date'],
                    'end_time' => $endTime,
                    'capacity' => null,
                ];
            }
        }

        $firstOccurrence = $occurrences->first();
        $capacity = $firstOccurrence?->capacity ?? ($groupSettings?->max_people ?? 1);
        $capacity = max(1, min(1000, (int) ($capacity ?: 1)));

        return $this->eventContextCache = [
            'type' => 'event',
            'event' => [
                'type' => 'event',
                'timezone' => $timezone,
                'capacity' => $capacity,
                'start_date' => $startDate,
                'start_time' => $startTime,
                'end_date' => $endDate,
                'end_time' => $endTime,
                'schedule_later' => $eventScheduleLater,
                'schedule' => [
                    'days' => $eventScheduleDays,
                ],
                'dates' => $dates,
            ],
        ];
    }

    private function decodeEventSchedulePayload(mixed $payload): array
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        return is_array($payload) ? $payload : [];
    }

    private function normalizeEventScheduleDays(array $days): array
    {
        $normalizedDays = [];

        foreach ($days as $dayIndex => $day) {
            if (! is_array($day)) {
                continue;
            }

            $date = trim((string) Arr::get($day, 'date', ''));
            $label = trim((string) Arr::get($day, 'label', ''));
            $sessions = Arr::get($day, 'sessions', []);
            if (! is_array($sessions)) {
                $sessions = [];
            }

            $normalizedSessions = [];
            foreach ($sessions as $sessionIndex => $session) {
                if (! is_array($session)) {
                    continue;
                }

                $normalizedSessions[] = [
                    'id' => (string) (Arr::get($session, 'id', '') ?: sprintf('event_schedule_%s_%d_%d', $date !== '' ? $date : 'day', $dayIndex + 1, $sessionIndex + 1)),
                    'label' => trim((string) Arr::get($session, 'label', '')),
                    'space_area' => trim((string) Arr::get($session, 'space_area', Arr::get($session, 'spaceArea', ''))),
                    'start_time' => trim((string) Arr::get($session, 'start_time', Arr::get($session, 'startTime', ''))),
                    'end_time' => trim((string) Arr::get($session, 'end_time', Arr::get($session, 'endTime', ''))),
                    'notes' => trim((string) Arr::get($session, 'notes', '')),
                ];
            }

            $normalizedDays[] = [
                'id' => (string) (Arr::get($day, 'id', '') ?: sprintf('event_day_%s_%d', $date !== '' ? $date : 'day', $dayIndex + 1)),
                'date' => $date,
                'label' => $label !== '' ? $label : 'Day '.($dayIndex + 1),
                'sessions' => array_values($normalizedSessions),
            ];
        }

        return array_values($normalizedDays);
    }

    private function buildEventDateSeries(string $startDate, string $endDate): array
    {
        $startDate = trim($startDate);
        $endDate = trim($endDate);

        if ($startDate === '') {
            return [];
        }

        if ($endDate === '') {
            $endDate = $startDate;
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();
        } catch (\Throwable $e) {
            return [];
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $days = [];
        $cursor = $start->copy();
        $limit = 0;
        while ($cursor->lte($end) && $limit < 366) {
            $days[] = [
                'index' => count($days) + 1,
                'date' => $cursor->toDateString(),
                'start_date' => $cursor->toDateString(),
                'start_time' => '',
                'end_date' => $cursor->toDateString(),
                'end_time' => '',
                'capacity' => null,
            ];
            $cursor->addDay();
            $limit++;
        }

        return $days;
    }
}
