{{-- resources/views/online/index.blade.php --}}
@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? 'Online | We Offer Wellness™' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  @if(!empty($seo['canonical']))<link rel="canonical" href="{{ $seo['canonical'] }}">@endif
@endpush

@section('content')
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => $modalityLabel ?? 'Online', 'url' => $pageUrl ?? url('/online')],
  ],
  'schemaUrl' => $pageUrl ?? url('/online'),
])

@php
  $items = collect($results['items'] ?? []);
  $isMobile = (bool) ($isMobile ?? false);
  $sortValue = (string) ($filters['sort'] ?? '');
  $sortLabel = match ($sortValue) {
    'price_asc' => 'Price: Low → High',
    'price_desc' => 'Price: High → Low',
    'rating_desc' => 'Top rated',
    default => 'Recommended',
  };
  $onlineFilterSegments = [
    [
      'key' => 'sort',
      'label' => 'Sort',
      'value' => $sortLabel,
      'placeholder' => 'Recommended',
      'panelTitle' => 'Sort results',
      'panelSubtitle' => 'Choose how online experiences are ordered.',
      'panelWidth' => 430,
      'options' => [
        [
          'label' => 'Recommended',
          'value' => '',
          'subtitle' => 'Best match for this page',
          'count' => 'Default',
          'selected' => $sortValue === '',
        ],
        [
          'label' => 'Price: Low → High',
          'value' => 'price_asc',
          'subtitle' => 'Cheaper options first',
          'selected' => $sortValue === 'price_asc',
        ],
        [
          'label' => 'Price: High → Low',
          'value' => 'price_desc',
          'subtitle' => 'Higher-priced options first',
          'selected' => $sortValue === 'price_desc',
        ],
        [
          'label' => 'Top rated',
          'value' => 'rating_desc',
          'subtitle' => 'Highest reviewed offerings first',
          'selected' => $sortValue === 'rating_desc',
        ],
      ],
    ],
  ];
  $onlineFilterChips = $sortValue !== ''
    ? [['param' => 'sort', 'label' => 'Sort', 'value' => $sortLabel]]
    : [];
@endphp

<section class="section">
  <div class="container-page">
    <div class="mb-4">
      <div class="kicker">Browse</div>
      <h1>{{ $modalityLabel ?? 'Online' }}</h1>
      <p class="text-ink-600 mt-2" style="max-width:70ch;">
        @if(!empty($modality))
          Online {{ strtolower((string) $modalityLabel) }} experiences you can join from anywhere — calm, convenient, and actually enjoyable.
        @else
          Online wellness therapies you can join from anywhere — calm, convenient, and actually enjoyable.
        @endif
      </p>
    </div>

    @if(!empty($modality))
      @include('partials.guide_panel', [
        'guidePanelModality' => $modality,
      ])
    @endif

    @if($isMobile)
      @include('search.partials.mobile', [
        'products' => $items,
        'mobileResultsCount' => $items->count(),
        'mobileFullNavigation' => true,
        'mobileShowMap' => false,
        'filterOnly' => false,
        'resultsHeading' => 'All online results',
        'searchMapData' => [],
        'searchRecommendationsHtml' => '',
        'searchAsyncBoot' => false,
        'searchUrl' => $pageUrl ?? url('/online'),
      ])
    @else
      @include('search.partials.desktop', [
        'products' => $items,
        'desktopResultsCount' => $items->count(),
        'desktopFullNavigation' => true,
        'showMap' => false,
        'filterOnly' => false,
        'resultsHeading' => 'All online results',
        'searchMapData' => [],
        'searchRecommendationsHtml' => '',
        'searchAsyncBoot' => false,
        'searchUrl' => $pageUrl ?? url('/online'),
      ])
    @endif

  </div>
</section>
@endsection
