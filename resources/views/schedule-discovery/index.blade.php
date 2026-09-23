@extends('layouts.app')

@push('head')
    <title>{{ $seo['title'] ?? 'Schedule Discovery | We Offer Wellness™' }}</title>
    @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
    @if(!empty($seo['canonical']))<link rel="canonical" href="{{ $seo['canonical'] }}">@endif
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
    'heroTitle' => 'Schedule Discovery',
    'heroIntro' => 'Explore upcoming wellness events, workshops and experiences with useful timing and clear routes into booking.',
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

<section class="section">
    <div class="container">
        <p class="text-muted mb-0">Browse the live schedule to find upcoming wellness experiences and events.</p>
    </div>
</section>
@endsection
