{{-- resources/views/needs/show.blade.php --}}
@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? (($need['title'] ?? 'Need').' | We Offer Wellness™') }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  <style>
    .wow-search-mobile-shell { display: none; }
    @media (max-width: 1040px) {
      .wow-search-mobile-shell { display: block; }
      .wow-search-desktop-shell { display: none !important; }
    }
    @media (min-width: 1041px) {
      .wow-search-mobile-shell { display: none !important; }
    }
  </style>
@endpush

@section('content')
@php
  $slug  = $need['slug'] ?? request()->route('slug');
  $items = $results['items'] ?? collect();
  if (!($items instanceof \Illuminate\Support\Collection)) {
    $items = collect($items ?? []);
  }
@endphp

@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Needs', 'url' => url('/needs')],
    ['label' => $need['title'] ?? 'Need'],
  ],
  'schemaUrl' => url('/needs/' . $slug),
])

@include('partials.landing-hero', [
  'heroEyebrow' => 'By need',
  'heroTitle' => $need['title'] ?? 'Need',
  'heroIntro' => $need['seo_description'] ?? '',
  'heroActions' => [['label' => 'All needs', 'href' => route('needs.index'), 'style' => 'outline']],
  'heroAsideLabel' => 'A useful starting point',
  'heroAsideTitle' => 'Find support that fits',
  'heroAsideText' => 'Explore relevant therapies, classes and experiences without the noise.',
])
@include('partials.hero-meta', [
  'items' => array_values(array_filter([
    isset($results['meta']['total']) ? number_format((int) $results['meta']['total']).' live offerings' : null,
    $need['title'] ?? 'Need support',
    'Online and in-person options',
  ])),
])

<section class="section">
  <div class="container-page">

    <div class="wow-search-mobile-shell">
      @include('search.partials.mobile', [
        'products' => $items,
        'mobileResultsCount' => $results['meta']['total'] ?? $items->count(),
        'mobileFullNavigation' => true,
        'mobileShowMap' => false,
        'searchMapData' => [],
        'searchRecommendationsHtml' => '',
        'searchAsyncBoot' => false,
        'searchUrl' => url('/needs/' . $slug),
      ])
    </div>

    <div class="wow-search-desktop-shell">
      @include('search.partials.desktop', [
        'products' => $items,
        'desktopResultsCount' => $results['meta']['total'] ?? $items->count(),
        'desktopFullNavigation' => true,
        'showMap' => false,
        'searchMapData' => [],
        'searchRecommendationsHtml' => '',
        'searchAsyncBoot' => false,
        'searchUrl' => url('/needs/' . $slug),
      ])
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
(function(){
  const mobileRoot = document.getElementById('wowMobileSearch');
  const mobileModal = mobileRoot?.querySelector('[data-filter-modal]');
  const mobileOpen = mobileRoot?.querySelector('[data-open-filters]');
  if (mobileRoot && mobileModal && mobileOpen) {
    mobileOpen.addEventListener('click', () => {
      mobileModal.hidden = false;
      document.body.classList.add('wow-sr-v5-no-scroll');
    });
  }
  const slug = @json($slug ?? null);
  const title = @json($need['title'] ?? 'Need');
  if (!slug) return;
  const entry = { slug: slug, title: title, url: @json(url('/needs/'.$slug)) };
  const key = 'wow_need_history';
  try {
    const raw = localStorage.getItem(key);
    let items = [];
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) items = parsed;
    }
    items = items.filter(item => item && item.slug !== entry.slug);
    items.unshift(entry);
    items = items.slice(0, 6);
    localStorage.setItem(key, JSON.stringify(items));
    document.dispatchEvent(new CustomEvent('wow:need-history', { detail: items }));
  } catch (_err) {}
})();
</script>
@endpush
