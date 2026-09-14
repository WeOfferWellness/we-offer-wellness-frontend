@extends('layouts.app')

@section('body-class', 'search-page-body')

@push('head')
  @include('partials.search-jsonld')
@endpush

<style>
@media (min-width: 992px){
  .results-scroll{ padding-right: 6px; }
  .search-layout.sr-list-only .results-scroll{
    padding-right: 0px;
  }
  .map-wrap{ position: relative; border-radius: 13px; overflow: hidden; }
  /* Adjust height to account for header + search bar */
  .map{ width: 100%; height: calc(100vh - 80px - 67px); border: 1px solid var(--ink-200); border-radius: 3px; overflow: hidden; }
}
.search-page-shell{
  position:relative;
  overflow:visible;
}
.search-content-wrapper{
  position:relative;
  overflow:visible;
}
.search-page-body #wow-header-container{
  z-index: 5001;
}
.wow-search-page-hero #wowsearch-desktop-what-field,
.wow-search-page-hero #wowsearch-desktop-where-field{
  position:relative;
}
.wow-search-page-hero #wowsearch-desktop-what-dropdown,
.wow-search-page-hero #wowsearch-desktop-where-dropdown{
  position:absolute !important;
  top:calc(100% + 8px) !important;
  right:auto !important;
  bottom:auto !important;
  left:0 !important;
  z-index:20000 !important;
}
.wow-ultra .bar{
  z-index: 4900;
}
.wow-ultra .pane,
.wow-ultra #search-top-what-pane,
.wow-ultra #search-top-where-pane,
.wow-ultra #search-top-when-pane,
.wow-ultra #search-top-who-pane,
.wow-ultra #search-top-group-pane{
  z-index: 20000 !important;
}
body.wow-search-pane-open #search-v4-root,
body.wow-search-pane-open .wow-ultra{
  position: relative;
  z-index: 60000 !important;
  isolation: isolate;
}
/* Segmented controls (search controls only) */
.search-controls .seg-group{ display:inline-flex; background:#f8fafc; border:1px solid var(--ink-200); border-radius:999px; padding:2px }
.search-controls .seg{ appearance:none; border:0; background:transparent; padding:6px 12px; border-radius:999px; color: var(--ink-700); font-weight:600; font-size:.9rem; transition: all .15s ease; }
.search-controls .seg:hover{ background:#eef2f7 }
.search-controls .seg.active, .search-controls .seg[aria-selected="true"]{ background: linear-gradient(180deg, #549483, #3b7768); color:#fff; box-shadow: 0 1px 0 rgba(255,255,255,.4) inset }
.search-controls .seg-group > .seg:first-of-type{ margin-right: 5px; }
/* Custom map markers */
.wow-marker{ width: 34px; height: 34px; border-radius: 999px; background:#fff; border:1px solid rgba(16,24,40,.18); display:flex; align-items:center; justify-content:center; position: relative; transform-origin: bottom center; will-change: transform; cursor: pointer; }
.mapboxgl-marker{ pointer-events: auto; z-index: 5; }
.wow-marker::after{ content:""; width:10px; height:10px; border-radius:999px; background:#549483; box-shadow: 0 0 0 5px rgba(84,56,255,.18); }
/* Desktop-only temporary glass styling for search bar */
@media (min-width: 992px){
  .wow-ultra .bar{
    background: rgba(255,255,255,.14);
    border-radius: 19px;
    border:3px solid rgba(0,0,0,0.1);
    position: fixed;
    top: 126px;
    z-index: 2000;
    left: 50%;
    transform: translateX(-50%);
    width: min(1200px, calc(100vw - 32px));
    -webkit-backdrop-filter: blur(14px);
    backdrop-filter: blur(14px);
    box-shadow: 0 14px 40px rgba(16,24,40,.14);
    transition: top .2s ease, width .18s ease;
    overflow: visible;
  }
  .wow-ultra .bar::before{
    content:"";
    position:absolute;
    inset:0;
    border-radius: inherit;
    pointer-events:none;
    background: linear-gradient(180deg, rgba(255,255,255,.28), rgba(255,255,255,.08));
    opacity:.55;
  }
  .wow-ultra .bar > *{ position: relative; z-index: 1; }
  .wow-ultra{ padding-top: 74px; }
  .search-compact .wow-ultra .bar{ top: 126px; }
  .search-compact .wow-ultra .bar{ width: 400px; height: 74px; overflow: hidden; }
  .wow-ultra .seg{ transition: opacity .12s ease; }
  .search-compact .wow-ultra #search-top-seg-where,
  .search-compact .wow-ultra #search-top-seg-when,
  .search-compact .wow-ultra #search-top-seg-who{ opacity: 0; pointer-events: none; }
  .search-compact .wow-ultra:hover .bar,
  .search-compact .wow-ultra:focus-within .bar{ width: min(1200px, calc(100vw - 32px)); overflow: visible; }
  .search-compact .wow-ultra:hover #search-top-seg-where,
  .search-compact .wow-ultra:hover #search-top-seg-when,
  .search-compact .wow-ultra:hover #search-top-seg-who,
  .search-compact .wow-ultra:focus-within #search-top-seg-where,
  .search-compact .wow-ultra:focus-within #search-top-seg-when,
  .search-compact .wow-ultra:focus-within #search-top-seg-who{ opacity: 1; pointer-events: auto; }
  .wow-ultra .btn-wow.is-squarish.btn-xl{ transition: opacity .12s ease; }
  .search-compact .wow-ultra .btn-wow.is-squarish.btn-xl{ opacity: 0; pointer-events: none; }
  .search-compact .wow-ultra:hover .btn-wow.is-squarish.btn-xl,
  .search-compact .wow-ultra:focus-within .btn-wow.is-squarish.btn-xl{ opacity: 1; pointer-events: auto; }
}
.wow-marker.is-active{ transform: scale(1.06); border-color: rgba(84,56,255,.45); box-shadow: 0 18px 54px rgba(84,56,255,.24); }
/* Desktop default: show text label, hide icon on Search button */
.btn-wow.is-squarish.btn-xl .btn-label{ display:inline; }
.btn-wow.is-squarish.btn-xl .btn-icon{ display:none; }

/* Mobile: hide Where, When, Who segments; keep What + Search visible */
@media (max-width: 991.98px){
  #search-top-seg-where,
  #search-top-seg-when,
  #search-top-seg-who{ display: none !important; }
}
/* Hide/show columns for list/map view at all widths */
.search-layout.sr-list-only .col-map{ display:none; }
.search-layout.sr-list-only .col-results{
  flex: 0 0 100%;
  max-width: 100%;
}
/* Toggle which card is shown per view */
.search-layout .result-view-map{ display:block; }
.search-layout .result-view-list{ display:none; }
.search-layout.sr-list-only .result-view-map{ display:none; }
.search-layout.sr-list-only .result-view-list{ display:block; }
.search-layout .result-view-map,
.search-layout .result-view-list{
  text-align:left;
}
.search-layout .result-view-map .product-v4-1-card-scope,
.search-layout .result-view-list .product-v4-1-card-scope,
.search-layout .result-view-map .product-v4-1-ghost-card-scope,
.search-layout .result-view-list .product-v4-1-ghost-card-scope{
  display:inline-block;
  text-align:left;
}
/* Disabled seg buttons */
.seg[disabled], .seg[aria-disabled="true"]{ opacity: .5; cursor: not-allowed; }
@media (max-width: 991.98px){
  .wow-mobile-search-page__map-wrap{ display:none !important; }
  .wow-mobile-search-page #wowMobileResultsGrid .result-view-map{ display:none !important; }
  .wow-mobile-search-page #wowMobileResultsGrid .result-view-list{ display:block !important; }
  .search-layout.sr-map-only .col-results{ display:none }
  .search-layout.sr-list-only .col-map{ display:none }
  .col-map .map{ height: 60vh }
  .search-controls{ display:none !important; }
  .wow-ultra .bar{
    background: rgba(255, 255, 255, .14);
    border-radius: 33px;
    border: none;
    border-top: 1px solid rgba(255, 255, 255, 0.5);
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    position: fixed;
    top: 84px;
    z-index: 2000;
    left: 12px;
    right: 12px;
    -webkit-backdrop-filter: blur(14px);
    backdrop-filter: blur(14px);
    box-shadow: 0 14px 40px rgba(16, 24, 40, .14);
  }
  .wow-ultra .bar::before{ content:""; position:absolute; inset:0; border-radius: inherit; pointer-events:none; background: linear-gradient(180deg, rgba(255,255,255,.28), rgba(255,255,255,.08)); opacity:.55; }
  .wow-ultra .bar > *{ position: relative; z-index: 1; }
  .wow-ultra .seg{ border-radius:40px; }
  .btn-wow.is-squarish.btn-xl{
    border-radius:50% !important;
    position:absolute; right:11px; top:50%; transform: translateY(-50%);
    display:inline-flex; align-items:center; justify-content:center;
    width:45px; height:45px; min-width:45px; min-height:45px; max-width:45px; max-height:45px;
    padding:0 !important; line-height:45px; overflow:hidden;
  }
  .btn-wow.is-squarish.btn-xl .btn-label{ display:none; }
  .btn-wow.is-squarish.btn-xl .btn-icon{ display:inline-flex; }
  .btn-wow.is-squarish.btn-xl .icon-search{ width:24px; height:24px; color:#fff; }
  .wow-ultra{ padding-top: 58px; }
}
/* Search-only card sizing */
.search-layout .result-view-map .wow-card.md{
  width: 280px;
  max-width: 280px;
  height: 609px;
  margin: 0 auto !important;
}
.search-layout .result-view-map .therapy-card{
  width: 280px;
}
.search-layout .result-view-map .product-v4-ghost-card-scope .wow-card.md{
  width: 280px;
  max-width: 280px;
  flex: 0 0 280px;
  margin-inline: auto;
}
.search-layout .result-view-map .product-v4-ghost-card-scope .product-v4-ghost-card{
  width: 280px;
}
.search-layout .result-view-list .wow-card.md{
  --card-h: 530px;
  width: 100%;
  max-width: none;
  margin-inline: 0;
}
.search-layout .result-view-list .wow-card.md.wow-event-card-v4{
  width: 280px;
  max-width: 280px;
  flex: 0 0 280px;
  margin-inline: auto;
}
.search-layout .result-view-list .therapy-card{
  width: 100%;
}
@media (min-width: 1600px){
  /* Ultra-wide map view: 3 columns only above xxl */
  .search-layout:not(.sr-list-only) .results-scroll .row > div{
    flex:0 0 33.333333%;
    max-width:33.333333%;
  }
}
/* Search result tags styled like product badges */
#sr-tags{ display:flex; flex-wrap:wrap; gap:8px; }
#sr-tags .badge{ height:32px; display:inline-flex; align-items:center; gap:4px; padding:0 10px; border-radius:3px; border:1px solid rgba(16,24,40,.10); font-weight:600; font-size:12px; line-height:1; white-space:nowrap; margin-bottom:10px; }
#sr-tags .badge--warm{ background:#ffe7c2; color:#6b4b12 }
#sr-tags .badge--cool{ background:#dfe9ff; color:#1f3a77 }
/* Ensure Who pane never overflows right edge and has constrained height */
.wow-ultra #search-top-who-pane{
  left:auto !important; right:0 !important;
  width: min(560px, 96vw); max-width:96vw;
  height:auto; max-height:304px; overflow:auto;
  -ms-overflow-style: none; scrollbar-width: none;
}
.wow-ultra #search-top-who-pane::-webkit-scrollbar{ width:0; height:0 }
/* Requested narrow pane sizing */
.wow-ultra .pane.narrow{
  z-index: 2100;
  left: 0px !important;
  right: 0px !important;
  width: min(560px, 96vw);
  max-width: 96vw;
  height: auto;
  max-height: 304px;
  overflow: auto;
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.wow-ultra .pane.narrow::-webkit-scrollbar{ width:0; height:0 }
.wow-ultra #search-top-who-pane .listy{ max-height: none; overflow: visible; }

.wow-search-mobile-shell{
  display:none;
}

@media (max-width: 1040px){
  .wow-search-page-hero{
    display:block;
  }

  .wow-search-mobile-shell{
    display:block;
  }

  .wow-search-desktop-shell{
    display:none !important;
  }
}

@media (min-width: 1041px){
  .wow-search-mobile-shell{
    display:none !important;
  }
}
</style>

@section('content')
@php
  $searchWhat = trim((string) request('what', ''));
  $searchWhere = trim((string) request('where', ''));
  $searchWhen = trim((string) request('when', ''));
  $searchBreadcrumb = $searchWhat !== '' ? $searchWhat : ($searchWhere !== '' ? $searchWhere : 'Search results');
  if ($searchWhat !== '' && $searchWhere !== '') {
    $searchBreadcrumb = $searchWhat . ' in ' . $searchWhere;
  }
  if ($searchWhat !== '' && $searchWhen !== '') {
    $searchBreadcrumb .= ' ' . $searchWhen;
  }
@endphp

<div class="search-page-shell pt-4 pb-2 bg-transparent">
  <div class="wow-search-page-hero">
    <x-home-searchbar-v4 :search-url="url('/search?view=list')" />
  </div>

  <div class="wow-search-mobile-shell">
    @include('search.partials.mobile')
  </div>

  @include('partials.breadcrumbs', [
    'crumbs' => [
      ['label' => 'Home', 'url' => url('/')],
      ['label' => 'Search', 'url' => url('/search')],
      ['label' => $searchBreadcrumb],
    ],
    'schemaUrl' => url()->full(),
    'renderVisual' => false,
    'chips' => array_filter([
      isset($resultCount) ? $resultCount . ' results' : null,
      'Live listings',
    ]),
  ])

  <div class="search-content-wrapper">
      <div class="wow-search-desktop-shell">
        @include('search.partials.desktop')
      </div>
  </div>
</div>

@endsection
