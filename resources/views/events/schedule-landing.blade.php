{{-- resources/views/events/schedule-landing.blade.php --}}
@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? 'Wellness Events | We Offer Wellness™' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
@endpush

@php
  $scheduleLanding = $scheduleLanding ?? [];
  $pageHeading = (string) ($scheduleLanding['pageTitle'] ?? 'Wellness events');
  $pageDescription = (string) ($scheduleLanding['pageDescription'] ?? '');
  $rangeLabel = (string) ($scheduleLanding['rangeLabel'] ?? '');
  $days = (array) ($scheduleLanding['days'] ?? []);
  $featuredEvents = (array) ($scheduleLanding['featuredEvents'] ?? []);
  $onlineEvents = (array) ($scheduleLanding['onlineEvents'] ?? []);
  $fallbackEvents = (array) ($scheduleLanding['fallbackEvents'] ?? []);
  $categoryCards = (array) ($scheduleLanding['categoryCards'] ?? []);
  $locationCards = (array) ($scheduleLanding['locationCards'] ?? []);
  $faqs = (array) ($scheduleLanding['faqs'] ?? []);
  $internalLinks = (array) ($scheduleLanding['internalLinks'] ?? []);
  $heroCopy = $pageDescription !== '' ? $pageDescription : 'Browse live wellness schedule content, helpful SEO copy and related landing pages.';
  $featuredCount = count($featuredEvents);
  $onlineCount = count($onlineEvents);
  $fallbackCount = count($fallbackEvents);

  $schemaBreadcrumb = [
    [
      '@type' => 'ListItem',
      'position' => 1,
      'name' => 'Home',
      'item' => url('/'),
    ],
    [
      '@type' => 'ListItem',
      'position' => 2,
      'name' => $pageHeading,
      'item' => url($pageCanonicalPath ?? '/'),
    ],
  ];

  $schemaItems = collect($featuredEvents)->map(function (array $item, int $index): array {
    $eventUrl = trim((string) ($item['display_url'] ?? ''));
    $title = trim((string) (data_get($item, 'display_title') ?? data_get($item, 'title', data_get($item, 'name', 'Wellness event'))));
    $location = trim((string) ($item['display_location'] ?? ''));
    $startTs = (int) ($item['display_sort_ts'] ?? 0);

    return [
      '@type' => 'ListItem',
      'position' => $index + 1,
      'name' => $title,
      'url' => $eventUrl,
    ];
  })->values()->all();

  $schema = [
    '@context' => 'https://schema.org',
    '@graph' => array_values(array_filter([
      [
        '@type' => 'CollectionPage',
        '@id' => url($pageCanonicalPath ?? '/'),
        'name' => $pageHeading,
        'description' => $heroCopy,
        'url' => url($pageCanonicalPath ?? '/'),
      ],
      [
        '@type' => 'BreadcrumbList',
        '@id' => url($pageCanonicalPath ?? '/') . '#breadcrumb',
        'itemListElement' => $schemaBreadcrumb,
      ],
      [
        '@type' => 'ItemList',
        '@id' => url($pageCanonicalPath ?? '/') . '#events',
        'name' => $pageHeading . ' featured events',
        'itemListElement' => $schemaItems,
      ],
      $faqs !== [] ? [
        '@type' => 'FAQPage',
        '@id' => url($pageCanonicalPath ?? '/') . '#faq',
        'mainEntity' => array_map(static function (array $faq): array {
          return [
            '@type' => 'Question',
            'name' => $faq['q'] ?? '',
            'acceptedAnswer' => [
              '@type' => 'Answer',
              'text' => $faq['a'] ?? '',
            ],
          ];
        }, $faqs),
      ] : null,
      ...collect($featuredEvents)->map(function (array $item): array {
        $title = trim((string) (data_get($item, 'display_title') ?? data_get($item, 'title', data_get($item, 'name', 'Wellness event'))));
        $url = trim((string) ($item['display_url'] ?? ''));
        $location = trim((string) ($item['display_location'] ?? ''));
        $startTs = (int) ($item['display_sort_ts'] ?? 0);

        if ($title === '' || $url === '' || $startTs <= 0) {
          return [];
        }

        $eventSchema = [
          '@type' => 'Event',
          'name' => $title,
          'url' => $url,
          'startDate' => \Carbon\Carbon::createFromTimestampUTC($startTs)->toAtomString(),
        ];

        if ($location !== '') {
          $eventSchema['location'] = [
            '@type' => 'Place',
            'name' => $location,
          ];
        }

        return $eventSchema;
      })->filter()->all(),
    ])),
  ];
@endphp

@push('head')
  <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => $pageHeading],
  ],
  'schemaUrl' => $pageCanonicalPath ?? url('/'),
])

<style>
  :root {
    --wow-ink: #101624;
    --wow-muted: #667085;
    --wow-border: #e5e7eb;
    --wow-soft: #f7faf9;
    --wow-card: #ffffff;
    --wow-green: #0f6b57;
    --wow-green-dark: #003c3c;
    --wow-mint: #d4fbe6;
    --wow-cream: #fff7e8;
    --wow-shadow: 0 24px 70px rgba(16, 24, 40, 0.10);
    --wow-soft-shadow: 0 14px 34px rgba(16, 24, 40, 0.07);
  }

  body {
    color: var(--wow-ink);
    background: #ffffff;
  }

  .wow-serif {
    font-family: Georgia, "Times New Roman", serif;
    letter-spacing: -0.055em;
  }

  .schedule-hero {
    position: relative;
    overflow: hidden;
    padding: 92px 0;
    background:
      linear-gradient(90deg, rgba(229, 231, 235, 0.68) 1px, transparent 1px) 0 0 / 25% 100%,
      linear-gradient(180deg, #ffffff 0%, #fbfcfc 100%);
  }
  .schedule-kicker {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .35rem .75rem;
    border-radius: 999px;
    background: rgba(212, 251, 230, 0.9);
    color: var(--wow-green-dark);
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .schedule-title {
    margin-top: 1rem;
    color: var(--wow-ink);
    font-size: clamp(44px, 5.4vw, 78px);
    line-height: 0.92;
    font-weight: 500;
    letter-spacing: -.055em;
  }
  .schedule-subtitle {
    max-width: 72ch;
    margin-top: 1rem;
    color: var(--wow-muted);
    font-size: 17px;
    line-height: 1.65;
  }
  .schedule-range {
    margin-top: .9rem;
    font-weight: 700;
    color: #18212f;
  }
  .schedule-section {
    padding: 1.75rem 0;
  }
  .schedule-card {
  height: 100%;
    border: 1px solid var(--wow-border);
    border-radius: 34px;
    background: #fff;
    box-shadow: var(--wow-shadow);
  }
  .schedule-day {
    min-height: 100%;
  }
  .schedule-day-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
    padding: 1rem 1rem .75rem;
    border-bottom: 1px solid #eef2f7;
  }
  .schedule-day-name {
    margin: 0;
    color: var(--wow-ink);
    font-size: 1rem;
    font-weight: 800;
  }
  .schedule-day-date {
    margin: .25rem 0 0;
    color: var(--wow-muted);
    font-size: .88rem;
  }
  .schedule-day-body {
    padding: 1rem;
  }
  .schedule-event-mini + .schedule-event-mini {
    margin-top: .75rem;
  }
  .schedule-event-mini {
    padding: .8rem .85rem;
    border-radius: 1rem;
    background: #f8fbfa;
    border: 1px solid #e4efe9;
  }
  .schedule-event-time {
    color: var(--wow-green);
    font-size: .82rem;
    font-weight: 800;
    letter-spacing: .02em;
  }
  .schedule-event-title {
    margin: .2rem 0 0;
    font-size: .98rem;
    font-weight: 700;
  }
  .schedule-event-meta,
  .schedule-event-copy {
    margin: .2rem 0 0;
    color: #667085;
    font-size: .86rem;
  }
  .schedule-event-link {
    display: inline-flex;
    margin-top: .45rem;
    font-size: .84rem;
    font-weight: 700;
    text-decoration: none;
  }
  .schedule-fallback {
    padding: 1rem;
    border-radius: 1rem;
    background: #f9fafb;
    border: 1px dashed #d9dfe8;
    color: #667085;
  }
  .schedule-pill {
    display: inline-flex;
    padding: .45rem .7rem;
    border-radius: 999px;
    border: 1px solid #cfd8e3;
    background: #ffffff;
    color: var(--wow-ink);
    text-decoration: none;
    font-size: .86rem;
    font-weight: 800;
    box-shadow: 0 12px 30px rgba(16, 24, 40, 0.06);
  }
  .schedule-card-link {
    display: block;
    color: inherit;
    text-decoration: none;
  }
  .schedule-feature-image {
    aspect-ratio: 16/10;
    object-fit: cover;
    width: 100%;
    border-top-left-radius: 1.25rem;
    border-top-right-radius: 1.25rem;
  }
  .schedule-muted {
    color: var(--wow-muted);
  }
  .schedule-list ol,
  .schedule-list ul {
    padding-left: 1.15rem;
    margin-bottom: 0;
  }
</style>

@include('partials.landing-hero', [
  'heroEyebrow' => 'Live wellness schedule',
  'heroTitle' => $pageHeading,
  'heroIntro' => $heroCopy,
  'heroActions' => [['label' => 'Browse online events', 'href' => url('/wellness-events/online'), 'style' => 'outline']],
  'heroAsideLabel' => 'Schedule window',
  'heroAsideTitle' => $rangeLabel !== '' ? $rangeLabel : 'Find your next event',
  'heroAsideText' => 'Live listings, useful timing and clear routes into wellness experiences.',
])

<section class="schedule-section pt-0">
  <div class="container">
    <div class="d-flex flex-wrap gap-2 mb-3">
      @foreach($internalLinks as $link)
        <a class="schedule-pill" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
      @endforeach
    </div>
  </div>
</section>

<section class="schedule-section pt-0">
  <div class="container">
    <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
      <div>
        <h2 class="h3 mb-1">Schedule calendar</h2>
        <p class="schedule-muted mb-0">{{ $timeframe === 'this-week' ? 'Monday to Sunday' : 'Friday evening to Sunday' }} event grid with live listings and no empty gaps.</p>
      </div>
      <a href="{{ url('/search') }}" class="btn btn-outline-secondary rounded-pill">Open full redirects</a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-{{ count($days) }} g-3">
      @foreach($days as $day)
        <div class="col">
          <div class="schedule-card schedule-day">
            <div class="schedule-day-head">
              <div>
                <p class="schedule-day-name mb-0">{{ $day['label'] }}</p>
                <p class="schedule-day-date">{{ $day['date']->format('l j F') }}</p>
              </div>
              <span class="badge text-bg-light">{{ count($day['items']) }} {{ count($day['items']) === 1 ? 'event' : 'events' }}</span>
            </div>
            <div class="schedule-day-body">
              @forelse($day['items'] as $item)
                @php
                  $title = trim((string) (data_get($item, 'display_title') ?? data_get($item, 'title', data_get($item, 'name', 'Wellness event'))));
                  $url = trim((string) ($item['display_url'] ?? ''));
                  $location = trim((string) ($item['display_location'] ?? ''));
                  $summary = trim((string) ($item['display_summary'] ?? ''));
                  $price = trim((string) (data_get($item, 'display_price') ?? data_get($item, 'price_from', data_get($item, 'price', ''))));
                  $spaces = data_get($item, 'display_spaces') ?? data_get($item, 'spaces_remaining');
                  $time = trim((string) ($item['display_time'] ?? ''));
                @endphp
                <div class="schedule-event-mini">
                  <div class="schedule-event-time">{{ $time !== '' ? $time : 'Time to be confirmed' }}</div>
                  <div class="schedule-event-title">{{ $title }}</div>
                  @if($location !== '')
                    <div class="schedule-event-meta">{{ $location }}</div>
                  @endif
                  @if($price !== '')
                    <div class="schedule-event-meta">{{ $price }}</div>
                  @endif
                  @if(is_numeric($spaces))
                    <div class="schedule-event-meta">{{ (int) $spaces }} spaces remaining</div>
                  @endif
                  @if($summary !== '')
                    <div class="schedule-event-copy">{{ \Illuminate\Support\Str::limit($summary, 110) }}</div>
                  @endif
                  @if($url !== '')
                    <a class="schedule-event-link" href="{{ $url }}">View event</a>
                  @endif
                </div>
              @empty
                <div class="schedule-fallback">
                  <p class="mb-2 fw-semibold">{{ $day['empty_text'] }}</p>
                  <a href="{{ url('/wellness-events/online') }}" class="link-success fw-semibold">Explore online wellness experiences</a>
                </div>
              @endforelse
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="schedule-section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="schedule-card p-4">
          <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <div>
              <h2 class="h3 mb-1">{{ $featuredCount > 0 ? 'Featured wellness events' : 'Featured wellness events this week' }}</h2>
              <p class="schedule-muted mb-0">Events with spaces available, sooner start times, strong imagery and fuller descriptions appear first.</p>
            </div>
            <span class="badge text-bg-dark">{{ $featuredCount }}</span>
          </div>

          <div class="row row-cols-1 row-cols-md-2 g-4">
            @forelse($featuredEvents as $item)
              @php
                $title = trim((string) (data_get($item, 'display_title') ?? data_get($item, 'title', data_get($item, 'name', 'Wellness event'))));
                $url = trim((string) ($item['display_url'] ?? ''));
                $location = trim((string) ($item['display_location'] ?? ''));
                $summary = trim((string) ($item['display_summary'] ?? ''));
                $image = trim((string) ($item['display_image'] ?? ''));
                $time = trim((string) ($item['display_time'] ?? ''));
                $date = trim((string) ($item['display_date'] ?? ''));
                $price = trim((string) (data_get($item, 'display_price') ?? data_get($item, 'price_from', data_get($item, 'price', ''))));
                $spaces = data_get($item, 'display_spaces') ?? data_get($item, 'spaces_remaining');
                $badge = trim((string) ($item['display_badge'] ?? 'Upcoming event'));
              @endphp
              <div class="col">
                <article class="schedule-card h-100">
                  @if($image !== '')
                    <img class="schedule-feature-image" src="{{ $image }}" alt="{{ $title }}">
                  @endif
                  <div class="p-4">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                      <span class="badge text-bg-light">{{ $badge }}</span>
                      @if($date !== '')<span class="badge text-bg-success">{{ $date }}</span>@endif
                    </div>
                    <h3 class="h5 mb-2">{{ $title }}</h3>
                    @if($time !== '')<div class="schedule-muted mb-1">{{ $time }}</div>@endif
                    @if($location !== '')<div class="schedule-muted mb-2">{{ $location }}</div>@endif
                    @if($summary !== '')<p class="mb-3">{{ \Illuminate\Support\Str::limit($summary, 150) }}</p>@endif
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                      @if($price !== '')<span class="badge text-bg-dark">{{ $price }}</span>@endif
                      @if(is_numeric($spaces))<span class="badge text-bg-warning">{{ (int) $spaces }} spaces left</span>@endif
                      @if($url !== '')
                        <a href="{{ $url }}" class="btn btn-sm btn-outline-dark rounded-pill ms-auto">Book</a>
                      @endif
                    </div>
                  </div>
                </article>
              </div>
            @empty
              <div class="col-12">
                <div class="schedule-fallback">
                  <p class="mb-0">No featured events are available yet for this date range. Browse the fallback inventory below.</p>
                </div>
              </div>
            @endforelse
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="schedule-card p-4 mb-4">
          <h2 class="h5 mb-3">Quick filters</h2>
          <div class="d-flex flex-wrap gap-2">
            @foreach([
              ['Sound baths', '/sound-baths/this-week'],
              ['Meditation', '/meditation-events/this-week'],
              ['Breathwork', '/breathwork-events/this-week'],
              ['Yoga', '/yoga-workshops/this-week'],
              ['Online', '/wellness-events/online'],
              ['Kent', '/wellness-events/this-week/kent'],
              ['London', '/wellness-events/this-week/london'],
            ] as [$label, $url])
              <a href="{{ url($url) }}" class="schedule-pill">{{ $label }}</a>
            @endforeach
          </div>
        </div>

        <div class="schedule-card p-4">
          <h2 class="h5 mb-3">What to expect</h2>
          <ol class="schedule-list mb-0">
            <li>Check the live day calendar first.</li>
            <li>Open a featured event for full details and booking.</li>
            <li>Use filters to narrow by type or location.</li>
            <li>Switch to online if you want a session from home.</li>
            <li>Bookmark this page for late availability.</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="schedule-section">
  <div class="container">
    <div class="schedule-card p-4">
      <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
          <h2 class="h3 mb-1">Online wellness events</h2>
          <p class="schedule-muted mb-0">Always include an online path so the page stays useful even when local inventory is light.</p>
        </div>
        <span class="badge text-bg-light">{{ $onlineCount }}</span>
      </div>

      <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        @forelse($onlineEvents as $item)
          @php
            $title = trim((string) (data_get($item, 'display_title') ?? data_get($item, 'title', data_get($item, 'name', 'Online wellness event'))));
            $url = trim((string) ($item['display_url'] ?? ''));
            $summary = trim((string) ($item['display_summary'] ?? ''));
            $time = trim((string) ($item['display_time'] ?? ''));
          @endphp
          <div class="col">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-body">
                <h3 class="h6">{{ $title }}</h3>
                @if($time !== '')<div class="schedule-muted mb-2">{{ $time }}</div>@endif
                @if($summary !== '')<p class="mb-3">{{ \Illuminate\Support\Str::limit($summary, 120) }}</p>@endif
                @if($url !== '')<a href="{{ $url }}" class="btn btn-sm btn-outline-success rounded-pill">Open</a>@endif
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="schedule-fallback">
              <p class="mb-0">No online sessions match this exact date range yet. Use the upcoming wellness events fallback below.</p>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>

<section class="schedule-section">
  <div class="container">
    <div class="schedule-card p-4">
      <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
          <h2 class="h3 mb-1">Upcoming events fallback</h2>
          <p class="schedule-muted mb-0">
            {{ $fallbackCount > 0 ? 'Not many events are listed for this exact range yet, so these upcoming sessions keep the page helpful.' : 'No fallback events are available right now.' }}
          </p>
        </div>
        <span class="badge text-bg-dark">{{ $fallbackCount }}</span>
      </div>

      <div class="list-group list-group-flush">
        @forelse($fallbackEvents as $item)
          @php
            $title = trim((string) (data_get($item, 'display_title') ?? data_get($item, 'title', data_get($item, 'name', 'Wellness event'))));
            $url = trim((string) ($item['display_url'] ?? ''));
            $summary = trim((string) ($item['display_summary'] ?? ''));
            $date = trim((string) ($item['display_date'] ?? ''));
            $location = trim((string) ($item['display_location'] ?? ''));
          @endphp
          <div class="list-group-item py-3">
            <div class="d-flex align-items-start justify-content-between gap-3">
              <div>
                <div class="fw-bold">{{ $title }}</div>
                <div class="schedule-muted">{{ $date }} @if($location !== '') · {{ $location }}@endif</div>
                @if($summary !== '')<div class="schedule-muted mt-1">{{ \Illuminate\Support\Str::limit($summary, 120) }}</div>@endif
              </div>
              @if($url !== '')
                <a href="{{ $url }}" class="btn btn-sm btn-outline-dark rounded-pill">Open</a>
              @endif
            </div>
          </div>
        @empty
          <div class="schedule-fallback">No fallback inventory is available yet.</div>
        @endforelse
      </div>
    </div>
  </div>
</section>

<section class="schedule-section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="schedule-card p-4 h-100">
          <h2 class="h3 mb-3">Popular wellness experiences</h2>
          <div class="row row-cols-1 row-cols-md-2 g-3">
            @foreach($categoryCards as $card)
              <div class="col">
                <a href="{{ $card['url'] }}" class="card h-100 text-decoration-none border-0 shadow-sm">
                  <div class="card-body">
                    <div class="badge text-bg-success mb-2">{{ $card['count'] }} results</div>
                    <h3 class="h6 mb-1">{{ $card['label'] }}</h3>
                    <div class="schedule-muted">Browse relevant events and landing pages.</div>
                    @if(!empty($card['secondary_url']))
                      <div class="mt-2 small text-decoration-underline">View alternate page</div>
                    @endif
                  </div>
                </a>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="schedule-card p-4 h-100">
          <h2 class="h3 mb-3">Explore wellness events by location</h2>
          <div class="row row-cols-1 row-cols-md-2 g-3">
            @foreach($locationCards as $card)
              <div class="col">
                <a href="{{ $card['url'] }}" class="card h-100 text-decoration-none border-0 shadow-sm">
                  <div class="card-body">
                    <h3 class="h6 mb-1">{{ $card['label'] }}</h3>
                    <div class="schedule-muted">{{ $card['count'] }} events available</div>
                    <div class="schedule-muted mt-1">{{ $card['subtitle'] }}</div>
                  </div>
                </a>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="schedule-section">
  <div class="container">
    <div class="schedule-card p-4">
      <h2 class="h3 mb-3">Find wellness events happening this {{ str_contains(strtolower($pageHeading), 'weekend') ? 'weekend' : 'week' }}</h2>
      <p>
        Whether you want to reset after work, try a sound bath, join a meditation session, or book a calming workshop close to home, this page helps you discover wellness events in a live, crawlable format.
      </p>
      <p>
        Browse in-person and online sessions, compare times and locations, and choose an experience that fits your schedule without hunting through thin date pages.
      </p>

      <div class="row g-4 mt-1">
        <div class="col-md-6">
          <h3 class="h5">Who these events are for</h3>
          <ul>
            <li>Beginners who want a gentle introduction.</li>
            <li>People feeling stressed or overloaded.</li>
            <li>Couples, friends and weekend planners.</li>
            <li>Corporate teams looking for wellbeing sessions.</li>
            <li>People who prefer online sessions from home.</li>
          </ul>
        </div>
        <div class="col-md-6">
          <h3 class="h5">What to expect</h3>
          <ol>
            <li>Arrival details and check-in instructions.</li>
            <li>What to bring, wear or prepare.</li>
            <li>Accessibility notes and session length.</li>
            <li>Online joining details if the event is remote.</li>
            <li>Booking confirmation and provider contact info.</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="schedule-section">
  <div class="container">
    <div class="schedule-card p-4">
      <h2 class="h3 mb-3">FAQs</h2>
      <div class="accordion" id="scheduleFaqs">
        @foreach($faqs as $index => $faq)
          <div class="accordion-item">
            <h3 class="accordion-header" id="faq-heading-{{ $index }}">
              <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="faq-collapse-{{ $index }}">
                {{ $faq['q'] ?? '' }}
              </button>
            </h3>
            <div id="faq-collapse-{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="faq-heading-{{ $index }}" data-bs-parent="#scheduleFaqs">
              <div class="accordion-body">{{ $faq['a'] ?? '' }}</div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

<section class="schedule-section pb-5">
  <div class="container">
    <div class="schedule-card p-4">
      <h2 class="h3 mb-3">Explore more wellness experiences</h2>
      <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach($internalLinks as $link)
          <a class="btn btn-sm btn-outline-secondary rounded-pill" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
        @endforeach
      </div>
      <div class="alert alert-success mb-0">
        Want to stay updated? Add event alerts or browse the full schedule to catch new listings as they are published.
      </div>
    </div>
  </div>
</section>
@endsection
