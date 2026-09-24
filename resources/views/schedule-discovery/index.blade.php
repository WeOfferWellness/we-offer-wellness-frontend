@extends('layouts.app')

@push('head')
    <title>{{ $seo['title'] ?? 'Schedule Discovery | We Offer Wellness™' }}</title>
    @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
    @if(!empty($seo['canonical']))<link rel="canonical" href="{{ $seo['canonical'] }}">@endif
    @php
        $scheduleCanonical = $seo['canonical'] ?? url('/schedule-discovery');
        $scheduleItems = collect($scheduleOfferings ?? [])->take(50)->values()->map(function ($item, $index) {
            $rawUrl = (string) data_get($item, 'url', '');
            $itemUrl = $rawUrl === '' ? null : (str_starts_with($rawUrl, 'http') ? $rawUrl : url($rawUrl));

            return array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => $itemUrl,
                'name' => (string) data_get($item, 'title', 'Wellness session'),
            ]);
        })->all();
        $scheduleJsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => $scheduleCanonical.'#webpage',
                    'url' => $scheduleCanonical,
                    'name' => $seo['title'] ?? 'Find Wellness Sessions by Date',
                    'description' => $seo['description'] ?? null,
                    'mainEntity' => ['@id' => $scheduleCanonical.'#results'],
                ],
                [
                    '@type' => 'ItemList',
                    '@id' => $scheduleCanonical.'#results',
                    'name' => 'Bookable wellness sessions and classes',
                    'numberOfItems' => count($scheduleItems),
                    'itemListElement' => $scheduleItems,
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($scheduleJsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
@include('partials.breadcrumbs', [
    'crumbs' => [
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Schedule Discovery'],
    ],
    'schemaUrl' => url('/schedule-discovery'),
])

@include('partials.landing-hero', [
    'heroEyebrow' => 'Live wellness schedule',
    'heroTitle' => 'Find wellness sessions by date',
    'heroIntro' => 'Browse therapies, classes and wellness sessions with real practitioner availability, then choose a date that works for you.',
    'heroActions' => [['label' => 'Browse events', 'href' => url('/events'), 'style' => 'outline']],
    'heroAsideLabel' => 'A useful starting point',
    'heroAsideTitle' => 'Find your next event',
    'heroAsideText' => 'Start with the schedule, then narrow your search by format, location or experience.',
])

@include('partials.hero-meta', [
    'items' => [
        ['label' => 'Upcoming wellness events', 'strong' => true],
        ['label' => 'Online and in-person options'],
        ['label' => 'Clear timing and booking routes'],
    ],
])

@include('partials.schedule-discovery')
@endsection
