<?php

namespace App\Http\Controllers;

use App\Support\WowEventsFeed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ScheduleDiscoveryController extends Controller
{
    public function index()
    {
        return view('schedule-discovery.index', [
            'seo' => [
                'title' => 'Schedule Discovery | We Offer Wellness™',
                'description' => 'Schedule Discovery placeholder page.',
                'canonical' => url('/schedule-discovery'),
            ],
        ]);
    }

    private const TOPICS = [
        'wellness-events' => [
            'label' => 'Wellness Events',
            'keyword' => null,
        ],
        'sound-baths' => [
            'label' => 'Sound Baths',
            'keyword' => 'sound bath',
        ],
        'meditation-events' => [
            'label' => 'Meditation Events',
            'keyword' => 'meditation',
        ],
        'breathwork-events' => [
            'label' => 'Breathwork Events',
            'keyword' => 'breathwork',
        ],
        'yoga-workshops' => [
            'label' => 'Yoga Workshops',
            'keyword' => 'yoga workshop',
        ],
    ];

    private const TIMEFRAMES = [
        'this-week',
        'this-weekend',
        'today',
        'tomorrow',
        'next-week',
        'online',
    ];

    public function show(Request $request, string $topic, string $timeframe, ?string $location = null)
    {
        $topic = $this->canonicalTopic($topic);
        $timeframe = $this->canonicalTimeframe($timeframe);
        abort_if($topic === null || $timeframe === null, 404);

        $topicMeta = self::TOPICS[$topic];
        $timeframeLabel = $this->timeframeLabel($timeframe);
        $location = trim(rawurldecode((string) $location));

        $query = [
            'page' => max(1, (int) $request->query('page', 1)),
            'per_page' => min(48, max(8, (int) $request->query('per_page', 24))),
            'sort' => 'date_asc',
        ];

        if ($topicMeta['keyword']) {
            $query['type'] = (string) $topicMeta['keyword'];
        }

        if ($location !== '') {
            $query['location'] = $location;
        }

        if ($timeframe === 'online') {
            $query['format'] = 'online';
            $query['date'] = 'upcoming';
        } elseif ($range = $this->timeframeDateRange($timeframe)) {
            $query['date'] = $range;
        }

        $results = WowEventsFeed::list($query);
        $items = collect($results['items'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => $this->decorateEventItem($item))
            ->values();

        [$upcomingEvents, $pastEvents] = $this->splitEvents($items);

        $pageHeading = $topicMeta['label'];
        $pageTitle = trim($pageHeading . ' ' . $timeframeLabel);
        if ($location !== '') {
            $pageTitle .= ' in ' . Str::headline(str_replace('-', ' ', $location));
        }

        $pageDescription = trim(sprintf(
            'Browse %s %s%s on We Offer Wellness.',
            Str::lower($pageHeading),
            Str::lower($timeframeLabel),
            $location !== '' ? ' in ' . Str::headline(str_replace('-', ' ', $location)) : ''
        ));

        $canonicalPath = $this->canonicalPath($topic, $timeframe, $location);
        $isScheduleLanding = $topic === 'wellness-events' && in_array($timeframe, ['this-week', 'this-weekend'], true);
        $scheduleLanding = $isScheduleLanding
            ? $this->buildScheduleLandingData($timeframe, $items, $upcomingEvents)
            : null;
        if ($scheduleLanding !== null) {
            $pageTitle = $scheduleLanding['pageTitle'];
            $pageDescription = $scheduleLanding['pageDescription'];
        }

        $filters = [
            'type' => (string) ($query['type'] ?? ''),
            'format' => (string) ($query['format'] ?? ''),
            'location' => (string) ($query['location'] ?? ''),
            'date' => (string) ($query['date'] ?? ''),
            'sort' => (string) ($query['sort'] ?? ''),
            'page' => (int) ($query['page'] ?? 1),
            'per_page' => (int) ($query['per_page'] ?? 24),
        ];

        $view = $scheduleLanding !== null ? 'events.schedule-landing' : 'events.index';

        return view($view, [
            'seo' => [
                'title' => $pageTitle . ' | We Offer Wellness™',
                'description' => $pageDescription,
                'robots' => $scheduleLanding['robots'] ?? 'index,follow',
                'canonical' => url($canonicalPath),
            ],
            'pageHeading' => $pageHeading,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'pageCrumb' => $pageHeading,
            'pageCanonicalPath' => $canonicalPath,
            'filters' => $filters,
            'results' => $results,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'scheduleLanding' => $scheduleLanding,
            'timeframe' => $timeframe,
        ]);
    }

    private function canonicalTopic(string $topic): ?string
    {
        $topic = strtolower(trim(rawurldecode($topic)));

        return array_key_exists($topic, self::TOPICS) ? $topic : null;
    }

    private function canonicalTimeframe(string $timeframe): ?string
    {
        $timeframe = strtolower(trim(rawurldecode($timeframe)));

        return in_array($timeframe, self::TIMEFRAMES, true) ? $timeframe : null;
    }

    private function canonicalPath(string $topic, string $timeframe, string $location = ''): string
    {
        $path = '/' . $topic . '/' . $timeframe;

        if ($location !== '') {
            $path .= '/' . Str::slug($location);
        }

        return $path;
    }

    private function timeframeLabel(string $timeframe): string
    {
        return match ($timeframe) {
            'this-week' => 'This Week',
            'this-weekend' => 'This Weekend',
            'today' => 'Today',
            'tomorrow' => 'Tomorrow',
            'next-week' => 'Next Week',
            'online' => 'Online',
            default => Str::headline($timeframe),
        };
    }

    private function timeframeDateRange(string $timeframe): ?string
    {
        $now = Carbon::now();

        return match ($timeframe) {
            'this-week' => $now->copy()->startOfWeek()->toDateString() . '..' . $now->copy()->endOfWeek()->toDateString(),
            'this-weekend' => $now->copy()->startOfWeek()->addDays(5)->startOfDay()->toDateString() . '..' . $now->copy()->startOfWeek()->addDays(6)->endOfDay()->toDateString(),
            'today', 'tomorrow', 'next-week' => null,
            default => null,
        };
    }

    private function splitEvents(Collection $items): array
    {
        $upcoming = [];
        $past = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (! empty($item['display_is_past'])) {
                $past[] = $item;
                continue;
            }

            $upcoming[] = $item;
        }

        $upcoming = collect($upcoming)
            ->sortBy(fn (array $item) => (int) ($item['display_sort_ts'] ?? PHP_INT_MAX))
            ->values()
            ->all();

        $past = collect($past)
            ->sortByDesc(fn (array $item) => (int) ($item['display_sort_ts'] ?? 0))
            ->values()
            ->all();

        return [$upcoming, $past];
    }

    /**
     * @param Collection<int, array<string, mixed>> $items
     * @param array<int, array<string, mixed>> $upcomingEvents
     * @param array<int, array<string, mixed>> $pastEvents
     * @return array<string, mixed>
     */
    private function buildScheduleLandingData(string $timeframe, Collection $items, array $upcomingEvents): array
    {
        [$rangeStart, $rangeEnd] = $this->scheduleRange($timeframe);
        $rangeLabel = $this->formatScheduleRangeLabel($rangeStart, $rangeEnd, $timeframe);
        $subtitle = $timeframe === 'this-week'
            ? 'Discover wellness events happening this week, including sound baths, meditation sessions, breathwork workshops, yoga classes, retreats and relaxing experiences online and across the UK.'
            : 'Find calming wellness events this weekend, including sound baths, meditation, breathwork, yoga workshops, retreats and online wellbeing sessions.';
        $heroTitle = $timeframe === 'this-week' ? 'Wellness events this week' : 'Wellness events this weekend';
        $exactItems = $items->filter(function (array $item) use ($rangeStart, $rangeEnd): bool {
            $ts = (int) ($item['display_sort_ts'] ?? 0);
            return $ts > 0 && $ts >= $rangeStart->getTimestamp() && $ts <= $rangeEnd->getTimestamp();
        })->values();
        $relevantCount = $exactItems->count();
        $robots = $relevantCount >= 3 ? 'index,follow' : 'noindex,follow';

        $dayLabels = $timeframe === 'this-week'
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
            : ['Friday evening', 'Saturday', 'Sunday'];
        $days = [];
        foreach ($dayLabels as $index => $label) {
            $dayDate = $timeframe === 'this-week'
                ? $rangeStart->copy()->addDays($index)
                : $rangeStart->copy()->addDays($index);

            $dayItems = $exactItems->filter(function (array $item) use ($dayDate): bool {
                $ts = (int) ($item['display_sort_ts'] ?? 0);
                return $ts > 0
                    && $ts >= $dayDate->copy()->startOfDay()->getTimestamp()
                    && $ts <= $dayDate->copy()->endOfDay()->getTimestamp();
            })->values();

            $days[] = [
                'label' => $label,
                'date' => $dayDate,
                'date_label' => $dayDate->format('j M'),
                'items' => $dayItems,
                'empty_text' => $timeframe === 'this-week'
                    ? 'No events listed for ' . $label . ' yet'
                    : ($label === 'Friday evening' ? 'No Friday evening events listed yet' : 'No ' . $label . ' events listed yet'),
            ];
        }

        $featuredEvents = $exactItems
            ->sortBy(fn (array $item): string => sprintf(
                '%d-%010d',
                empty($item['display_image']) ? 1 : 0,
                (int) ($item['display_sort_ts'] ?? 0)
            ))
            ->take(8)
            ->values()
            ->all();

        $onlineEvents = $exactItems
            ->filter(fn (array $item): bool => str_contains(strtolower((string) ($item['display_location'] ?? '')), 'online'))
            ->values()
            ->all();

        if ($onlineEvents === []) {
            $onlineEvents = collect($upcomingEvents)
                ->filter(fn (array $item): bool => str_contains(strtolower((string) ($item['display_location'] ?? '')), 'online'))
                ->take(6)
                ->values()
                ->all();
        }

        $fallbackEvents = collect($upcomingEvents)
            ->filter(fn (array $item): bool => ((int) ($item['display_sort_ts'] ?? 0)) > $rangeEnd->getTimestamp())
            ->take(9)
            ->values()
            ->all();

        if ($fallbackEvents === []) {
            $fallbackEvents = collect($upcomingEvents)->take(9)->values()->all();
        }

        $categoryCards = $this->buildDiscoveryCards($exactItems, [
            'Sound baths' => ['/sound-baths/this-week', '/sound-baths/this-weekend'],
            'Meditation' => ['/meditation-events/this-week', '/meditation-events/this-weekend'],
            'Breathwork' => ['/breathwork-events/this-week', '/breathwork-events/this-weekend'],
            'Yoga' => ['/yoga-workshops/this-week', '/yoga-workshops/this-weekend'],
            'Online wellness' => ['/wellness-events/online'],
            'Retreats' => ['/wellness-events/this-week', '/wellness-events/this-weekend'],
            'Corporate wellbeing' => ['/wellness-events/this-week', '/wellness-events/this-weekend'],
            'Reiki' => ['/wellness-events/this-week', '/wellness-events/this-weekend'],
            'Massage' => ['/wellness-events/this-week', '/wellness-events/this-weekend'],
            'Mindfulness' => ['/wellness-events/this-week', '/wellness-events/this-weekend'],
        ]);

        $locationCards = $this->buildLocationCards($exactItems, [
            'Kent',
            'London',
            'Essex',
            'Surrey',
            'Sussex',
            'Online',
        ]);

        $faqs = $timeframe === 'this-week'
            ? [
                ['q' => 'What wellness events are happening this week?', 'a' => 'You can find sound baths, meditation sessions, breathwork workshops, yoga classes and other wellness experiences happening this week.'],
                ['q' => 'Can I book online wellness events?', 'a' => 'Yes, some events are available online while others take place in person.'],
                ['q' => 'Can I book last-minute?', 'a' => 'Many wellness events accept last-minute bookings depending on availability.'],
                ['q' => 'Are wellness events suitable for beginners?', 'a' => 'Most wellness events are beginner-friendly, but check the event description before booking.'],
                ['q' => 'How do I find wellness events near me?', 'a' => 'Use the location links or choose online sessions if you prefer to join from home.'],
            ]
            : [
                ['q' => 'What wellness events are happening this weekend?', 'a' => 'You can discover sound baths, meditation sessions, breathwork workshops, yoga classes and relaxing wellness experiences happening this weekend.'],
                ['q' => 'Can I find wellness events near me this weekend?', 'a' => 'Yes, you can filter by location or choose online wellness events.'],
                ['q' => 'Can I book a sound bath this weekend?', 'a' => 'If sound baths are available, they will appear in the schedule and event listings.'],
                ['q' => 'What should I bring to a wellness event?', 'a' => 'This depends on the session. Some events recommend a yoga mat, blanket, water bottle or comfortable clothing.'],
                ['q' => 'Are weekend wellness events good for stress?', 'a' => 'Many people book weekend wellness events to relax, reset and support their wellbeing.'],
            ];

        return [
            'pageTitle' => $heroTitle,
            'pageDescription' => $subtitle,
            'robots' => $robots,
            'rangeLabel' => $rangeLabel,
            'days' => $days,
            'featuredEvents' => $featuredEvents,
            'onlineEvents' => $onlineEvents,
            'fallbackEvents' => $fallbackEvents,
            'categoryCards' => $categoryCards,
            'locationCards' => $locationCards,
            'faqs' => $faqs,
            'internalLinks' => [
                ['label' => 'Wellness events this week', 'url' => url('/wellness-events/this-week')],
                ['label' => 'Wellness events this weekend', 'url' => url('/wellness-events/this-weekend')],
                ['label' => 'Online wellness events', 'url' => url('/wellness-events/online')],
                ['label' => 'Sound baths', 'url' => url('/sound-baths/this-week')],
                ['label' => 'Meditation experiences', 'url' => url('/meditation-events/this-week')],
                ['label' => 'Breathwork workshops', 'url' => url('/breathwork-events/this-week')],
                ['label' => 'Yoga workshops', 'url' => url('/yoga-workshops/this-week')],
                ['label' => 'Wellness experiences in Kent', 'url' => url('/wellness-events/this-week/kent')],
                ['label' => 'Wellness experiences in London', 'url' => url('/wellness-events/this-week/london')],
            ],
        ];
    }

    /**
     * @return array{0: Carbon,1: Carbon}
     */
    private function scheduleRange(string $timeframe): array
    {
        $now = Carbon::now();

        return match ($timeframe) {
            'this-weekend' => [
                $now->copy()->startOfWeek()->addDays(4)->startOfDay(),
                $now->copy()->startOfWeek()->addDays(6)->endOfDay(),
            ],
            default => [
                $now->copy()->startOfWeek()->startOfDay(),
                $now->copy()->endOfWeek()->endOfDay(),
            ],
        };
    }

    private function formatScheduleRangeLabel(Carbon $start, Carbon $end, string $timeframe): string
    {
        return match ($timeframe) {
            'this-weekend' => 'Showing events from ' . $start->format('l j F') . ' to ' . $end->format('l j F'),
            default => 'Showing events from ' . $start->format('l j F') . ' to ' . $end->format('l j F'),
        };
    }

    /**
     * @param Collection<int, array<string, mixed>> $items
     * @param array<string, array<int, string>> $links
     * @return array<int, array<string, mixed>>
     */
    private function buildDiscoveryCards(Collection $items, array $links): array
    {
        $rules = [
            'Sound baths' => ['sound bath', 'sound healing'],
            'Meditation' => ['meditation', 'mindfulness'],
            'Breathwork' => ['breathwork', 'breathing'],
            'Yoga' => ['yoga'],
            'Online wellness' => ['online'],
            'Retreats' => ['retreat'],
            'Corporate wellbeing' => ['corporate'],
            'Reiki' => ['reiki'],
            'Massage' => ['massage'],
            'Mindfulness' => ['mindfulness'],
        ];

        $cards = [];
        foreach ($rules as $label => $needles) {
            $count = $items->filter(function (array $item) use ($needles): bool {
                $haystack = strtolower(trim(implode(' ', [
                    (string) ($item['display_title'] ?? data_get($item, 'title', data_get($item, 'name', ''))),
                    (string) ($item['display_summary'] ?? ''),
                    (string) ($item['display_location'] ?? ''),
                ])));

                foreach ($needles as $needle) {
                    if (str_contains($haystack, $needle)) {
                        return true;
                    }
                }

                return false;
            })->count();

            $cards[] = [
                'label' => $label,
                'count' => $count,
                'url' => $links[$label][0] ?? url('/wellness-events/this-week'),
                'secondary_url' => $links[$label][1] ?? null,
            ];
        }

        return $cards;
    }

    /**
     * @param Collection<int, array<string, mixed>> $items
     * @param array<int, string> $locations
     * @return array<int, array<string, mixed>>
     */
    private function buildLocationCards(Collection $items, array $locations): array
    {
        $cards = [];
        foreach ($locations as $location) {
            $count = $items->filter(function (array $item) use ($location): bool {
                $haystack = strtolower(trim(implode(' ', [
                    (string) ($item['display_location'] ?? ''),
                    (string) ($item['display_title'] ?? data_get($item, 'title', data_get($item, 'name', ''))),
                    (string) ($item['display_summary'] ?? ''),
                ])));

                return str_contains($haystack, strtolower($location));
            })->count();

            $cards[] = [
                'label' => $location,
                'count' => $count,
                'url' => $location === 'Online'
                    ? url('/wellness-events/online')
                    : url('/wellness-events/this-week/' . Str::slug($location)),
                'subtitle' => $location === 'Online'
                    ? 'Online wellbeing sessions available'
                    : 'Explore wellness experiences in ' . $location,
            ];
        }

        return $cards;
    }

    private function decorateEventItem(array $item): array
    {
        $startAt = $this->eventStartAt($item);
        $endAt = $this->eventEndAt($item, $startAt);
        $isPast = $startAt ? $startAt->lt(Carbon::now()) : false;

        $displayDate = $startAt ? $startAt->format('D, j M Y') : 'Date to be confirmed';
        $displayTime = '';
        if ($startAt) {
            $startTime = $startAt->format('g:i A');
            if ($endAt && $endAt->gt($startAt)) {
                $displayTime = $endAt->toDateString() !== $startAt->toDateString()
                    ? $startTime . ' - ' . $endAt->format('D, j M g:i A')
                    : ($endAt->format('g:i A') !== $startTime ? $startTime . ' - ' . $endAt->format('g:i A') : $startTime);
            } else {
                $displayTime = $startTime;
            }
        }

        $displayLocation = trim((string) data_get($item, 'location', data_get($item, 'venue.name', data_get($item, 'event.location', data_get($item, 'event.venue', '')))));
        if ($displayLocation === '') {
            $displayLocation = trim((string) data_get($item, 'venue.address', ''));
        }

        $displaySummary = trim((string) data_get($item, 'summary', data_get($item, 'description_short', data_get($item, 'description', ''))));
        $displayImage = trim((string) data_get($item, 'image', data_get($item, 'image_url', data_get($item, 'featured_image', ''))));
        $displayUrl = trim((string) data_get($item, 'url', data_get($item, 'link', '')));
        if ($displayUrl === '') {
            $slug = trim((string) data_get($item, 'slug', data_get($item, 'handle', '')));
            $displayUrl = $slug !== '' ? url('/events/' . ltrim($slug, '/')) : '';
        } elseif (! str_starts_with($displayUrl, 'http')) {
            $displayUrl = url($displayUrl);
        }

        return array_merge($item, [
            'display_when' => trim($displayDate . ($displayTime !== '' ? ' • ' . $displayTime : '')),
            'display_date' => $displayDate,
            'display_time' => $displayTime,
            'display_location' => $displayLocation,
            'display_summary' => $displaySummary,
            'display_image' => $displayImage,
            'display_url' => $displayUrl,
            'display_badge' => $isPast ? 'Past event' : 'Upcoming event',
            'display_is_past' => $isPast,
            'display_sort_ts' => $startAt?->getTimestamp() ?? 0,
        ]);
    }

    private function eventStartAt(array $item): ?Carbon
    {
        $timezone = $this->eventTimezone($item);
        $date = trim((string) data_get($item, 'when.event.start_date', data_get($item, 'event.start_date', data_get($item, 'start_date', data_get($item, 'date', '')))));
        $time = trim((string) data_get($item, 'when.event.start_time', data_get($item, 'event.start_time', data_get($item, 'start_time', ''))));

        if ($date === '') {
            return null;
        }

        $candidate = trim($date . ($time !== '' ? ' ' . $time : ''));

        try {
            return Carbon::parse($candidate, $timezone);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function eventEndAt(array $item, ?Carbon $startAt = null): ?Carbon
    {
        $timezone = $this->eventTimezone($item);
        $date = trim((string) data_get($item, 'when.event.end_date', data_get($item, 'event.end_date', data_get($item, 'end_date', ''))));
        $time = trim((string) data_get($item, 'when.event.end_time', data_get($item, 'event.end_time', data_get($item, 'end_time', ''))));

        if ($date === '' && ! $startAt) {
            return null;
        }

        $candidateDate = $date !== '' ? $date : ($startAt ? $startAt->toDateString() : '');
        $candidate = trim($candidateDate . ($time !== '' ? ' ' . $time : ''));

        if ($candidate === '') {
            return $startAt ? $startAt->copy() : null;
        }

        try {
            return Carbon::parse($candidate, $timezone);
        } catch (\Throwable $e) {
            return $startAt ? $startAt->copy() : null;
        }
    }

    private function eventTimezone(array $item): string
    {
        $timezone = trim((string) data_get($item, 'when.event.timezone', data_get($item, 'event.timezone', data_get($item, 'timezone', config('app.timezone', 'UTC')))));

        return $timezone !== '' ? $timezone : config('app.timezone', 'UTC');
    }
}
