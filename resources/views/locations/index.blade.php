{{-- resources/views/locations/index.blade.php --}}
@extends('layouts.app')

@php
  $mapboxKey = config('services.mapbox.token');
  $resolved = $locationSearch ?? null;
  $results = collect($locations ?? []);
  $physicalResults = $results->filter(fn ($location) => !($location['online'] ?? false))->values();
  $searchPhysicalResults = $resolved ? $physicalResults->take(5)->values() : $physicalResults;
  $onlineResult = $results->first(fn ($location) => ($location['online'] ?? false) === true);
  $catalogCountries = collect($locationCatalog['countries'] ?? [])
    ->sort(function (array $left, array $right): int {
      $leftSlug = \Illuminate\Support\Str::slug((string) ($left['slug'] ?? $left['label'] ?? ''));
      $rightSlug = \Illuminate\Support\Str::slug((string) ($right['slug'] ?? $right['label'] ?? ''));

      if ($leftSlug === 'united-kingdom' && $rightSlug !== 'united-kingdom') {
        return -1;
      }

      if ($rightSlug === 'united-kingdom' && $leftSlug !== 'united-kingdom') {
        return 1;
      }

      return strcmp((string) ($left['label'] ?? $left['title'] ?? ''), (string) ($right['label'] ?? $right['title'] ?? ''));
    })
    ->values();
  $catalogFlat = collect($locationCatalog['flat'] ?? []);
  $catalogSuggestions = collect($locationCatalog['suggestions'] ?? [])->map(function (array $location) {
    return [
      'text' => $location['title'] ?? '',
      'place_name' => trim(implode(', ', array_filter([
        $location['title'] ?? '',
        $location['county'] ?? $location['district'] ?? '',
        $location['country'] ?? '',
      ]))),
      'path' => $location['path'] ?? null,
      'online' => (bool) ($location['online'] ?? false),
      'slug' => $location['slug'] ?? '',
      'title' => $location['title'] ?? '',
      'country' => $location['country'] ?? '',
      'county' => $location['county'] ?? '',
      'district' => $location['district'] ?? '',
      'region' => $location['region'] ?? '',
    ];
  })->values()->all();
  $featuredOrder = ['united-kingdom' => 0, 'online' => 1, 'london' => 2, 'manchester' => 3, 'brighton-and-hove' => 4, 'kent' => 5];
  $featuredLocations = $catalogFlat
    ->sortBy(function (array $location) use ($featuredOrder): int {
      $slug = \Illuminate\Support\Str::slug((string) ($location['title'] ?? $location['slug'] ?? ''));
      return $featuredOrder[$slug] ?? 99;
    })
    ->filter(function (array $location) use ($featuredOrder): bool {
      $slug = \Illuminate\Support\Str::slug((string) ($location['title'] ?? $location['slug'] ?? ''));
      return isset($featuredOrder[$slug]);
    })
    ->values();
  $offeringMapItems = isset($products) && $products instanceof \Illuminate\Pagination\LengthAwarePaginator
    ? collect($products->items())->map(function ($item) {
        return [
          'title' => (string) data_get($item, 'marker_title', data_get($item, 'title', '')),
          'slug' => \Illuminate\Support\Str::slug((string) data_get($item, 'title', '')),
          'lat' => data_get($item, 'lat'),
          'lng' => data_get($item, 'lng'),
          'distance' => data_get($item, 'distance_label'),
        ];
      })->filter(function (array $item) {
        return is_numeric($item['lat'] ?? null) && is_numeric($item['lng'] ?? null);
      })->values()->all()
    : [];
  $mapItems = !empty($offeringMapItems)
    ? $offeringMapItems
    : $searchPhysicalResults->take(8)->map(function ($location) {
        return [
          'title' => $location['title'] ?? '',
          'slug' => $location['slug'] ?? '',
          'lat' => $location['lat'] ?? null,
          'lng' => $location['lng'] ?? null,
          'distance' => $location['distance_label'] ?? null,
        ];
      })->values()->all();
@endphp

@push('head')
  <title>{{ $seo['title'] ?? 'Locations | We Offer Wellness®' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  <style>
    .wow-locations-page{
      position:relative;
      overflow:hidden;
      background:none;
      color:#101828;
      padding:44px 0 72px;
    }
    .wow-page-grid{
      display:none;
    }
    .wow-locations-container{
      position:relative;
      z-index:1;
      width:min(100% - 40px, 1360px);
      margin:0 auto;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-locations-hero{
      display:grid;
      grid-template-columns:minmax(0,1.1fr) minmax(290px,.6fr);
      gap:30px;
      align-items:end;
      margin-bottom:24px;
    }
    .wow-kicker{
      margin:0 0 10px;
      color:#344054;
      font-size:13px;
      font-weight:300;
      letter-spacing:.16em;
      text-transform:uppercase;
    }
    .wow-locations-hero h1,
    .wow-locations-section h2,
    .wow-location-card h3{
      margin:0;
      color:#101828;
      font-family:'Playfair Display', Georgia, serif;
      font-weight:500;
      letter-spacing:-.05em;
    }
    .wow-locations-hero h1{
      font-size:clamp(46px, 5.6vw, 78px);
      line-height:.96;
      max-width:860px;
    }
    .wow-locations-hero p{
      max-width:760px;
      margin:16px 0 0;
      color:#596275;
      font-size:18px;
      line-height:1.58;
    }
    .wow-search-panel{
      background:rgba(255,255,255,.92);
      border:1px solid rgba(16,24,40,.08);
      border-radius:20px;
      box-shadow:0 20px 48px rgba(16,24,40,.08);
      padding:20px;
      backdrop-filter:blur(10px);
    }
    .wow-search-panel__label{
      display:block;
      margin-bottom:8px;
      color:#344054;
      font-size:13px;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:.1em;
    }
    .wow-search-panel__row{
      display:grid;
      grid-template-columns:1fr auto;
      gap:10px;
      align-items:start;
    }
    .wow-search-panel input{
      width:100%;
      height:46px;
      border:1px solid #d0d5dd;
      border-radius:14px;
      background:#fff;
      color:#111827;
      padding:0 14px;
      font-size:15px;
      outline:none;
    }
    .wow-search-panel input:focus{
      border-color:#4f9381;
      box-shadow:0 0 0 3px rgba(79,147,129,.14);
    }
    .wow-search-panel button{
      min-height:46px;
      padding:0 20px;
      border-radius:14px;
      border:1px solid #4f9381;
      background:linear-gradient(180deg, #5ea691, #4f9381);
      color:#fff;
      font-weight:700;
      cursor:pointer;
      box-shadow:0 10px 22px rgba(79,147,129,.22);
    }
    .wow-search-panel button:hover{
      background:#417c6d;
      border-color:#417c6d;
    }
    .wow-search-panel__helper{
      margin-top:10px;
      color:#667085;
      font-size:13px;
      line-height:1.45;
    }
    .wow-search-panel__menu{
      position:relative;
    }
    .wow-search-panel__dropdown{
      position:absolute;
      left:0;
      right:0;
      top:calc(100% + 6px);
      z-index:20;
      background:#fff;
      border:1px solid #dfe4ea;
      border-radius:16px;
      box-shadow:0 18px 40px rgba(16,24,40,.12);
      overflow:hidden;
    }
    .wow-search-panel__dropdown button{
      width:100%;
      border:0;
      border-bottom:1px solid #edf0f2;
      background:#fff;
      text-align:left;
      color:#101828;
      padding:12px 14px;
      min-height:auto;
      border-radius:0;
      display:block;
    }
    .wow-search-panel__dropdown button:hover{
      background:#f8fafc;
    }
    .wow-search-panel__dropdown strong{
      display:block;
      font-size:14px;
      line-height:1.4;
      margin-bottom:2px;
    }
    .wow-search-panel__dropdown span{
      display:block;
      color:#667085;
      font-size:12px;
      line-height:1.35;
    }
    .wow-locations-layout{
      display:grid;
      grid-template-columns:minmax(0,.72fr) minmax(0,1.28fr);
      gap:28px;
      margin-top:26px;
    }
    .wow-locations-sidebar{
      display:flex;
      flex-direction:column;
      gap:16px;
    }
    .wow-locations-map,
    .wow-search-summary,
    .wow-location-card,
    .wow-online-card,
    .wow-empty-card,
    .wow-offerings-card,
    .wow-directory-card{
      background:rgba(255,255,255,.98);
      border:1px solid rgba(16,24,40,.08);
      border-radius:20px;
      box-shadow:0 14px 36px rgba(16,24,40,.06);
    }
    .wow-search-summary{
      padding:22px;
    }
    .wow-search-summary p{
      margin:0;
      color:#596275;
      font-size:15px;
      line-height:1.55;
    }
    .wow-search-summary strong{
      display:block;
      margin-bottom:6px;
      color:#101828;
      font-size:18px;
      font-family:'Playfair Display', Georgia, serif;
      font-weight:500;
    }
    .wow-search-summary__meta{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:14px;
    }
    .wow-pill{
      min-height:30px;
      display:inline-flex;
      align-items:center;
      padding:0 10px;
      border:1px solid #d9dee7;
      border-radius:999px;
      background:#fff;
      color:#344054;
      font-size:12px;
      font-weight:700;
    }
    .wow-locations-map{
      overflow:hidden;
      min-height:480px;
      position:relative;
      background:linear-gradient(180deg, #f7faf8, #ecf4f0);
    }
    .wow-locations-map--embedded{
      min-height:360px;
      margin-top:16px;
    }
    .wow-locations-map__canvas{
      width:100%;
      height:480px;
      min-height:480px;
      border-radius:20px;
    }
    .wow-locations-map--embedded .wow-locations-map__canvas{
      height:360px;
      min-height:360px;
    }
    @media (min-width: 1081px){
      .wow-locations-map--embedded{
        position: sticky;
        top: 127px;
        z-index: 5;
      }
      .wow-locations-map--embedded .wow-locations-map__canvas{
        height: calc(100vh - 80px - 67px);
        min-height: 620px;
      }
    }
    .wow-locations-section{
      margin-top:32px;
    }
    .wow-locations-section h2{
      font-size:clamp(28px, 3.2vw, 44px);
      line-height:1;
      margin-bottom:14px;
    }
    .wow-locations-section__copy{
      color:#596275;
      font-size:16px;
      line-height:1.55;
      max-width:860px;
      margin:0 0 18px;
    }
    .wow-location-list{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:16px;
    }
    .wow-location-card{
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      min-height:240px;
      padding:22px;
    }
    .wow-location-card__top{
      display:flex;
      justify-content:space-between;
      gap:12px;
      margin-bottom:18px;
    }
    .wow-location-card__title{
      font-size:32px;
      line-height:.98;
    }
    .wow-location-card__label{
      align-self:flex-start;
      min-height:30px;
      display:inline-flex;
      align-items:center;
      padding:0 10px;
      border-radius:4px;
      background:#e8f0ff;
      color:#254a85;
      border:1px solid #c7d8fb;
      font-size:12px;
      font-weight:700;
      white-space:nowrap;
    }
    .wow-location-card__copy{
      margin:14px 0 0;
      color:#596275;
      font-size:15px;
      line-height:1.55;
    }
    .wow-location-card__meta{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:18px;
    }
    .wow-location-card__footer{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-top:18px;
      padding-top:16px;
      border-top:1px solid #edf0f2;
    }
    .wow-location-card__footer small{
      color:#667085;
      font-size:13px;
      line-height:1.45;
    }
    .wow-online-card{
      padding:22px;
      border-left:4px solid #4f9381;
    }
    .wow-online-card h3{
      font-size:36px;
      line-height:.98;
    }
    .wow-online-card p{
      margin:12px 0 0;
      color:#596275;
      font-size:15px;
      line-height:1.55;
    }
    .wow-directory-grid{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:16px;
    }
    .wow-directory-card{
      padding:22px;
      text-decoration:none;
      color:inherit;
    }
    .wow-directory-card h3{
      font-size:28px;
      line-height:1;
      margin-bottom:12px;
    }
    .wow-directory-card p{
      margin:0;
      color:#596275;
      font-size:15px;
      line-height:1.55;
    }
    .wow-directory-card span{
      display:inline-flex;
      margin-top:18px;
      color:#4f9381;
      font-size:14px;
      font-weight:700;
    }
    .wow-empty-card{
      padding:24px;
    }
    .wow-empty-card p{
      margin:0;
      color:#596275;
      font-size:15px;
      line-height:1.55;
    }
    .wow-offerings-card{
      padding:24px;
    }
    .wow-offerings-card h2{
      margin:0 0 10px;
      color:#101828;
      font-family:'Playfair Display', Georgia, serif;
      font-weight:500;
      letter-spacing:-.05em;
      font-size:clamp(28px, 3.2vw, 44px);
      line-height:1;
    }
    .wow-offerings-card p{
      margin:0;
      color:#596275;
      font-size:15px;
      line-height:1.55;
    }
    .wow-offerings-card__meta{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:14px;
    }
    .wow-offerings-card__count{
      min-height:30px;
      display:inline-flex;
      align-items:center;
      padding:0 10px;
      border:1px solid #d9dee7;
      border-radius:999px;
      background:#fff;
      color:#344054;
      font-size:12px;
      font-weight:700;
    }

    /* The offering results use the shared search controls. Keep the desktop
       sidebar out of the mobile layout and mount the same compact toolbar and
       filter sheet used by the main search page instead. */
    .wow-location-desktop-results{ display:block; }
    .wow-location-mobile-results{ display:none; }
    .wow-location-mobile-results [data-map-toggle]{ display:none !important; }
    @media (max-width:1040px){
      .wow-location-desktop-results{ display:none; }
      .wow-location-mobile-results{ display:block; }
    }
    .wow-trending-grid,
    .wow-country-stack,
    .wow-county-stack{
      display:grid;
      gap:14px;
    }
    .wow-trending-grid{
      grid-template-columns:repeat(3, minmax(0, 1fr));
    }
    .wow-trend-card,
    .wow-country-card,
    .wow-county-card{
      display:block;
      text-decoration:none;
      color:inherit;
      background:rgba(255,255,255,.96);
      border:1px solid rgba(16,24,40,.08);
      border-radius:18px;
      box-shadow:0 12px 28px rgba(16,24,40,.05);
      overflow:hidden;
    }
    .wow-trend-card{
      padding:18px;
      transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }
    .wow-trend-card:hover{
      transform:translateY(-2px);
      border-color:rgba(79,147,129,.24);
      box-shadow:0 18px 38px rgba(16,24,40,.08);
    }
    .wow-trend-card strong{
      display:block;
      font-size:18px;
      line-height:1.15;
      margin-top:10px;
    }
    .wow-trend-card span{
      color:#596275;
      font-size:14px;
      line-height:1.45;
    }
    .wow-trend-card__top,
    .wow-country-card summary,
    .wow-county-card summary{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
    }
    .wow-country-card,
    .wow-county-card{
      padding:0;
    }
    .wow-country-card summary,
    .wow-county-card summary{
      cursor:pointer;
      list-style:none;
      padding:18px 20px;
    }
    .wow-country-card summary::-webkit-details-marker,
    .wow-county-card summary::-webkit-details-marker{
      display:none;
    }
    .wow-country-card > summary strong,
    .wow-county-card > summary strong{
      display:block;
      font-size:18px;
      margin-bottom:2px;
    }
    .wow-country-card > summary span,
    .wow-county-card > summary span{
      color:#596275;
      font-size:14px;
    }
    .wow-town-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:12px;
      padding:0 20px 20px;
    }
    .wow-town-link{
      display:flex;
      gap:12px;
      align-items:flex-start;
      padding:14px 14px;
      border-radius:16px;
      border:1px solid rgba(16,24,40,.08);
      background:#fff;
      color:inherit;
      text-decoration:none;
      transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }
    .wow-town-link:hover{
      transform:translateY(-1px);
      border-color:rgba(79,147,129,.26);
      box-shadow:0 12px 24px rgba(16,24,40,.06);
    }
    .wow-town-link strong{
      display:block;
      margin-bottom:2px;
      font-size:15px;
    }
    .wow-town-link span{
      display:block;
      color:#667085;
      font-size:13px;
      line-height:1.4;
    }
    .wow-discovery-section{
      position:relative;
      overflow:hidden;
      background:transparent;
      padding:0;
      margin-top:32px;
      margin-bottom:0;
    }
    .wow-discovery-section--gift{
      margin-bottom:64px;
    }
    .wow-gift-panel{
      display:grid;
      grid-template-columns:minmax(0,.8fr) minmax(380px,1.2fr);
      gap:30px;
      align-items:stretch;
      padding:26px;
      background:rgba(255,255,255,.98);
      border:1px solid rgba(16,24,40,.08);
      border-radius:18px;
      box-shadow:0 14px 42px rgba(16,24,40,.055);
    }
    .wow-gift-copy{
      display:flex;
      flex-direction:column;
      justify-content:center;
      min-height:340px;
    }
    .wow-gift-copy h2,
    .wow-gift-card-preview h3{
      margin:0;
      color:var(--wow-ink);
      font-family:var(--wow-serif);
      font-weight:500;
      letter-spacing:-.055em;
    }
    .wow-gift-copy h2{
      max-width:500px;
      font-size:clamp(38px,4.8vw,60px);
      line-height:.96;
    }
    .wow-gift-copy p{
      max-width:470px;
      margin:16px 0 0;
      color:#596275;
      font-size:16px;
      line-height:1.58;
    }
    .wow-gift-actions{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:24px;
    }
    .wow-gift-points{
      display:grid;
      gap:10px;
      margin-top:24px;
    }
    .wow-gift-points span{
      display:flex;
      gap:10px;
      align-items:flex-start;
      color:#344054;
      font-size:14px;
      line-height:1.45;
    }
    .wow-gift-points span::before{
      content:"✓";
      width:22px;
      height:22px;
      flex:0 0 22px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius:999px;
      background:#e6f4ef;
      color:#4f9381;
      font-size:12px;
      font-weight:800;
    }
    .wow-gift-visual{
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:340px;
      background:#f8fafc;
      border:1px solid #e3e8ee;
      border-radius:16px;
      padding:24px;
    }
    .wow-gift-card-preview{
      width:min(100%,560px);
      background:#fff;
      border:1px solid #e3e8ee;
      border-radius:18px;
      box-shadow:0 20px 56px rgba(16,24,40,.10);
      overflow:hidden;
    }
    .wow-gift-card-preview__top{
      display:flex;
      justify-content:space-between;
      gap:18px;
      padding:22px;
      border-bottom:1px solid #edf0f2;
    }
    .wow-gift-card-preview__brand{
      display:flex;
      align-items:center;
      gap:10px;
      color:#101828;
      font-size:14px;
      font-weight:800;
      letter-spacing:-.04em;
    }
    .wow-gift-mark{
      width:26px;
      height:26px;
      border-radius:999px;
      background:#4f9381;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:14px;
      font-weight:800;
    }
    .wow-gift-card-preview__amount{
      text-align:right;
    }
    .wow-gift-card-preview__amount small{
      display:block;
      color:#667085;
      font-size:12px;
      line-height:1.2;
    }
    .wow-gift-card-preview__amount strong{
      display:block;
      margin-top:4px;
      color:#101828;
      font-size:34px;
      line-height:1;
      letter-spacing:-.055em;
    }
    .wow-gift-card-preview__body{
      padding:22px;
    }
    .wow-gift-card-preview h3{
      font-size:36px;
      line-height:.98;
    }
    .wow-gift-card-preview p{
      margin:12px 0 0;
      color:#667085;
      font-size:14px;
      line-height:1.5;
    }
    .wow-gift-amounts{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:18px;
    }
    .wow-gift-amounts span{
      min-height:34px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border:1px solid #d0d5dd;
      border-radius:999px;
      background:#fff;
      color:#344054;
      padding:0 14px;
      font-size:13px;
      font-weight:700;
    }
    .wow-gift-amounts span.is-active{
      border-color:#4f9381;
      background:#4f9381;
      color:#fff;
    }
    .wow-gift-card-preview__footer{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      padding:18px 22px;
      background:#f8fafc;
      border-top:1px solid #edf0f2;
      color:#667085;
      font-size:13px;
    }
    .wow-gift-card-preview__footer strong{
      color:#4f9381;
    }
    @media (max-width: 1080px){
      .wow-locations-hero,
      .wow-locations-layout{
        grid-template-columns:1fr;
      }
      .wow-trending-grid,
      .wow-directory-grid,
      .wow-location-list{
        grid-template-columns:1fr;
      }
      .wow-town-grid{
        grid-template-columns:1fr;
      }
      .wow-gift-panel{
        grid-template-columns:1fr;
      }
    }
    @media (max-width: 640px){
      .wow-locations-page{ padding:32px 0 56px; }
      .wow-locations-container,
      .wow-page-grid{ width:min(100% - 28px, 1360px); }
      .wow-search-panel__row{
        grid-template-columns:1fr;
      }
      .wow-search-panel button,
      .wow-location-card__footer .btn-wow{
        width:100%;
      }
      .wow-location-card__title,
      .wow-online-card h3{
        font-size:30px;
      }
      .wow-gift-panel,
      .wow-gift-card-preview__top,
      .wow-gift-card-preview__body,
      .wow-gift-card-preview__footer{
        padding:18px;
      }
      .wow-gift-copy h2{
        font-size:clamp(32px, 11vw, 46px);
      }
      .wow-gift-card-preview h3{
        font-size:30px;
      }
      .wow-gift-card-preview__top,
      .wow-gift-card-preview__footer{
        flex-direction:column;
        align-items:flex-start;
      }
      .wow-gift-card-preview__amount{
        text-align:left;
      }
      .wow-country-card summary,
      .wow-county-card summary{
        padding:16px;
      }
      .wow-town-grid{
        padding:0 16px 16px;
      }
    }
  </style>
@endpush

@section('content')
@php
  $locationBreadcrumb = trim((string) data_get($locationSearch, 'label', $locationQuery ?? ''));
  if ($locationBreadcrumb === '') {
    $locationBreadcrumb = 'Locations';
  }
  $locationCrumbs = [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Locations', 'url' => url('/locations')],
  ];
  if (strcasecmp($locationBreadcrumb, 'Locations') !== 0) {
    $locationCrumbs[] = ['label' => $locationBreadcrumb];
  }
@endphp

@include('partials.breadcrumbs', [
  'crumbs' => $locationCrumbs,
  'schemaUrl' => $seo['canonical'] ?? url()->full(),
])

<main class="wow-locations-page">
  <div class="wow-page-grid" aria-hidden="true"></div>

  <div class="wow-locations-container">
    <header class="wow-locations-hero">
      <div>
        <p class="wow-kicker">Find</p>
        <h1>Search by location and we’ll sort the nearest wellness options for you.</h1>
        <p>Start typing a town, city or region and Mapbox will suggest the right place. We’ll then rank therapies, classes, events and practitioners by distance, with online shown when it’s the better fit.</p>
      </div>

      <div class="wow-search-panel">
        <form method="get" action="{{ url('/locations') }}" id="wowLocationSearchForm" autocomplete="off">
          <label class="wow-search-panel__label" for="wowLocationQuery">Location</label>
          <div class="wow-search-panel__menu">
            <div class="wow-search-panel__row">
              <input
                id="wowLocationQuery"
                name="place"
                type="search"
                value="{{ $locationQuery ?? '' }}"
                placeholder="Start typing a city, town or area"
                required
                aria-autocomplete="list"
                aria-expanded="false"
              >
              <button type="submit" class="btn-wow btn-wow--primary">Search</button>
            </div>
            <div id="wowLocationDropdown" class="wow-search-panel__dropdown" hidden></div>
          </div>
          <div class="wow-search-panel__helper">
            Try “Maidstone”, “London”, “Cardiff” or a postcode. We’ll resolve the county, city, town and country for you.
          </div>
          @if(empty($resolved) && !empty($savedLocation['label'] ?? ''))
            <div class="wow-search-panel__helper" style="margin-top:8px;">
              Using your saved location: <strong>{{ $savedLocation['label'] }}</strong>
            </div>
          @endif
          <input type="hidden" name="postcode" id="wowLocationPostcode" value="">
          <input type="hidden" name="town" id="wowLocationTown" value="">
          <input type="hidden" name="city" id="wowLocationCity" value="">
          <input type="hidden" name="county" id="wowLocationCounty" value="">
          <input type="hidden" name="country" id="wowLocationCountry" value="">
          <input type="hidden" name="lat" id="wowLocationLat" value="">
          <input type="hidden" name="lng" id="wowLocationLng" value="">
        </form>
      </div>
    </header>

    @if($resolved)
      <section class="wow-search-summary">
        <strong>Showing results for {{ $resolved['label'] ?? $locationQuery }}</strong>
        <p>
          @if(!empty($resolved['town']) || !empty($resolved['county']) || !empty($resolved['country']))
            {{ collect([$resolved['town'] ?? null, $resolved['county'] ?? null, $resolved['country'] ?? null])->filter()->join(', ') }}
          @else
            We found the closest wellness results based on your search.
          @endif
        </p>
        <div class="wow-search-summary__meta">
          @if(!empty($resolved['place']))<span class="wow-pill">{{ $resolved['place'] }}</span>@endif
          @if(!empty($resolved['town']))<span class="wow-pill">{{ $resolved['town'] }}</span>@endif
          @if(!empty($resolved['city']) && $resolved['city'] !== ($resolved['town'] ?? null))<span class="wow-pill">{{ $resolved['city'] }}</span>@endif
          @if(!empty($resolved['county']))<span class="wow-pill">{{ $resolved['county'] }}</span>@endif
          @if(!empty($resolved['region']) && $resolved['region'] !== ($resolved['county'] ?? null))<span class="wow-pill">{{ $resolved['region'] }}</span>@endif
          @if(!empty($resolved['country']))<span class="wow-pill">{{ $resolved['country'] }}</span>@endif
        </div>
      </section>

      <section class="wow-locations-section">
        <h2>Offerings in this area</h2>
        <p class="wow-locations-section__copy">These are the actual therapies, classes, workshops and retreats matched to your selected location, with the map dotted to those experiences.</p>
      </section>

      @if(isset($products) && $products->count())
        <div class="wow-location-desktop-results">
          @include('search.partials.desktop', [
            'resultsHeading' => 'Offerings near ' . ($resolved['town'] ?? $resolved['place'] ?? $resolved['county'] ?? $resolved['country'] ?? 'your location'),
            'products' => $products,
            'resultCount' => $resultCount ?? $products->total(),
            'showMap' => false,
          ])
        </div>
        <div class="wow-location-mobile-results">
          @include('search.partials.mobile', [
            'products' => $products,
            'resultCount' => $resultCount ?? $products->total(),
            'searchMapData' => $offeringMapItems,
            'mobileFullNavigation' => true,
            'mobileShowMap' => false,
          ])
        </div>
      @endif

      <section class="wow-locations-section">
        <h2>Nearest results</h2>
        <p class="wow-locations-section__copy">These are ranked by distance from your selected location. If there’s a better local option later, the list updates automatically when you search again.</p>

        <div class="wow-location-list">
          @foreach($searchPhysicalResults as $location)
            <article class="wow-location-card">
              <div>
                <div class="wow-location-card__top">
                  <span class="wow-location-card__label">{{ $location['distance_label'] ?? 'Nearby' }}</span>
                  <span class="wow-pill">{{ $location['slug'] ?? 'location' }}</span>
                </div>
                <h3 class="wow-location-card__title">{{ $location['title'] }}</h3>
                <p class="wow-location-card__copy">
                  {{ $location['seo_description'] ?? ('Explore holistic health and wellness in ' . $location['title'] . '.') }}
                </p>
                <div class="wow-location-card__meta">
                  @if(!empty($location['distance_miles']))<span class="wow-pill">{{ number_format((float) $location['distance_miles'], (float) $location['distance_miles'] < 10 ? 1 : 0) }} miles away</span>@endif
                  @if(!empty($location['lat']) && !empty($location['lng']))<span class="wow-pill">On map</span>@endif
                </div>
              </div>
              <div class="wow-location-card__footer">
                <small>{{ $location['distance_label'] ?? 'Available nearby' }}</small>
                <a class="btn-wow btn-wow--primary" href="{{ url($location['path'] ?? ('/locations/' . $location['slug'])) }}">View location</a>
              </div>
            </article>
          @endforeach
        </div>
      </section>
    @else
      <section class="wow-locations-section">
        <h2>Trending destinations</h2>
        <p class="wow-locations-section__copy">Our most searched places. Online stays first, then the strongest local destinations.</p>
        <div class="wow-trending-grid">
          @foreach($featuredLocations as $loc)
            <a href="{{ url($loc['path'] ?? ('/locations/' . $loc['slug'])) }}" class="wow-trend-card">
              <div class="wow-trend-card__top">
                <span class="wow-location-card__label">{{ $loc['online'] ? 'Online' : ($loc['county'] ?? $loc['district'] ?? 'Location') }}</span>
                <span class="wow-pill">{{ number_format((int) (($loc['counts']['total'] ?? 0)), 0) }} listings</span>
              </div>
              <div style="display:flex; gap:10px; align-items:flex-start;">
                <span class="wow-loc-icon {{ $loc['online'] ? 'wow-loc-icon--online' : '' }}">
                  @if($loc['online'])
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="12" rx="2"></rect><path d="M8 21h8"></path><path d="M12 17v4"></path></svg>
                  @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-6-4.35-6-9a6 6 0 1 1 12 0c0 4.65-6 9-6 9z"/><circle cx="12" cy="12" r="2"/></svg>
                  @endif
                </span>
                <div>
                  <strong>{{ $loc['title'] }}</strong>
                  <span>{{ $loc['online'] ? 'Virtual wellness sessions from anywhere' : trim(implode(', ', array_filter([$loc['county'] ?? $loc['district'] ?? '', $loc['country'] ?? '']))) }}</span>
                </div>
              </div>
            </a>
          @endforeach
        </div>
      </section>

      <section class="wow-locations-section">
        <h2>Browse by country and county</h2>
        <p class="wow-locations-section__copy">Grouped so the page feels structured instead of a long flat list. Tap a country to open its counties and towns.</p>
        <div class="wow-country-stack">
          @foreach($catalogCountries as $country)
            @continue(($country['online'] ?? false) === true)
            <details class="wow-country-card" @if(($country['slug'] ?? '') === 'united-kingdom') open @endif>
              <summary>
                <span>
                  <strong>{{ $country['label'] }}</strong>
                  <span>{{ number_format((int) ($country['counts']['total'] ?? 0)) }} listings</span>
                </span>
                <span class="wow-pill">Country</span>
              </summary>
              <div class="wow-county-stack">
                @foreach($country['counties'] as $county)
                  <details class="wow-county-card" @if($loop->first) open @endif>
                    <summary>
                      <span>
                        <strong>{{ $county['label'] }}</strong>
                        <span>{{ number_format((int) ($county['counts']['total'] ?? 0)) }} listings</span>
                      </span>
                      <span class="wow-pill">County / district</span>
                    </summary>
                    <div class="wow-town-grid">
                      @foreach($county['towns'] as $town)
                        <a href="{{ url($town['path'] ?? ('/locations/' . $town['slug'])) }}" class="wow-town-link">
                          <span class="wow-loc-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-6-4.35-6-9a6 6 0 1 1 12 0c0 4.65-6 9-6 9z"/><circle cx="12" cy="12" r="2"/></svg>
                          </span>
                          <span>
                            <strong>{{ $town['title'] }}</strong>
                            <span>{{ trim(implode(', ', array_filter([$town['county'] ?? $town['district'] ?? '', $town['country'] ?? '']))) }}</span>
                          </span>
                        </a>
                      @endforeach
                    </div>
                  </details>
                @endforeach
              </div>
            </details>
          @endforeach
        </div>
      </section>
    @endif

    @include('home.sections.gift_cards_occasion')
  </div>
</main>

@push('scripts')
  <script>
    (() => {
      const init = () => {
        const root = document.querySelector('.wow-location-mobile-results #wowMobileSearch');
        const modal = root?.querySelector('[data-filter-modal]');
        if (!root || !modal || root.dataset.initialized === 'true') return;
        root.dataset.initialized = 'true';
        const close = () => {
          modal.hidden = true;
          document.body.classList.remove('wow-sr-v5-no-scroll');
        };
        const open = () => {
          modal.hidden = false;
          document.body.classList.add('wow-sr-v5-no-scroll');
        };
        const sync = () => {
          const url = new URL(window.location.href);
          const values = {
            sort: url.searchParams.get('sort') || 'popular',
            type: url.searchParams.get('type') || '',
            rating: url.searchParams.get('rating') || '',
            price: Number(url.searchParams.get('price_max') || 500),
          };
          root.querySelectorAll('[data-draft-name]').forEach((button) => {
            button.classList.toggle('is-selected', values[button.dataset.draftName] === button.dataset.draftValue);
          });
          root.querySelectorAll('[data-draft-price]').forEach((range) => {
            range.value = String(Math.min(500, Math.max(10, values.price)));
          });
          root.querySelectorAll('[data-draft-price-label]').forEach((label) => {
            label.textContent = values.price >= 500 ? 'Any price' : 'Under £' + values.price;
          });
        };

        root.addEventListener('click', (event) => {
          if (event.target.closest('[data-open-filters]')) { open(); sync(); return; }
          if (event.target.closest('[data-close-filters]')) { close(); return; }
          const sectionToggle = event.target.closest('[data-mobile-section-toggle]');
          if (sectionToggle) {
            const section = sectionToggle.closest('.wow-sr-v5-mobile-filter-section');
            section.classList.toggle('is-collapsed');
            sectionToggle.setAttribute('aria-expanded', String(!section.classList.contains('is-collapsed')));
            return;
          }
          const choice = event.target.closest('[data-draft-name]');
          if (choice) {
            root.querySelectorAll('[data-draft-name="' + choice.dataset.draftName + '"]').forEach((item) => {
              item.classList.toggle('is-selected', item === choice);
            });
            return;
          }
          if (event.target.closest('[data-clear-filters]')) {
            const url = new URL(window.location.href);
            ['sort', 'type', 'rating', 'price_max'].forEach((name) => url.searchParams.delete(name));
            window.history.replaceState({}, '', url.toString());
            sync();
            return;
          }
          if (event.target.closest('[data-apply-filters]')) {
            const url = new URL(window.location.href);
            const selected = (name) => root.querySelector('[data-draft-name="' + name + '"].is-selected')?.dataset.draftValue || '';
            const sort = selected('sort');
            const type = selected('type');
            const rating = selected('rating');
            const price = root.querySelector('[data-draft-price]')?.value || '500';
            [['sort', sort, 'popular'], ['type', type, ''], ['rating', rating, ''], ['price_max', price, '500']].forEach(([name, value, defaultValue]) => {
              if (!value || value === defaultValue) url.searchParams.delete(name);
              else url.searchParams.set(name, value);
            });
            window.location.assign(url.toString());
          }
        });
        root.querySelectorAll('[data-draft-price]').forEach((range) => {
          range.addEventListener('input', () => {
            root.querySelectorAll('[data-draft-price-label]').forEach((label) => {
              label.textContent = Number(range.value) >= 500 ? 'Any price' : 'Under £' + range.value;
            });
          });
        });
        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && !modal.hidden) close();
        });
        sync();
      };
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
      else init();
    })();
  </script>
@endpush
@endsection

@push('scripts')
<script>
(function () {
  const token = @json($mapboxKey);
  const form = document.getElementById('wowLocationSearchForm');
  const input = document.getElementById('wowLocationQuery');
  const dropdown = document.getElementById('wowLocationDropdown');
  const postcodeInput = document.getElementById('wowLocationPostcode');
  const townInput = document.getElementById('wowLocationTown');
  const cityInput = document.getElementById('wowLocationCity');
  const countyInput = document.getElementById('wowLocationCounty');
  const countryInput = document.getElementById('wowLocationCountry');
  const latInput = document.getElementById('wowLocationLat');
  const lngInput = document.getElementById('wowLocationLng');
  const mapEl = document.getElementById('wowLocationsMap');
  const canMap = !!(mapEl && token);
  const localCatalog = @json($catalogSuggestions);

  let timer = null;
  let results = [];
  let selected = null;

  function contextLabel(place, prefix) {
    const context = Array.isArray(place?.context) ? place.context : [];
    const match = context.find((item) => String(item?.id || '').startsWith(prefix + '.'));
    return match && match.text ? String(match.text) : '';
  }

  function hideDropdown() {
    if (!dropdown) return;
    dropdown.hidden = true;
    dropdown.innerHTML = '';
    input?.setAttribute('aria-expanded', 'false');
  }

  function slugify(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/&/g, ' and ')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function normalizeText(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, ' ')
      .trim();
  }

  function isGenericCounty(slug) {
    return ['england', 'scotland', 'wales', 'northern-ireland', 'united-kingdom', 'uk', 'u-k', 'gb', 'great-britain'].indexOf(slug) !== -1;
  }

  function normalizeCountrySlug(country) {
    const slug = slugify(country);
    if (!slug || isGenericCounty(slug)) {
      return 'united-kingdom';
    }
    return slug;
  }

  function normalizeCountySlug(county) {
    const slug = slugify(county);
    return slug && !isGenericCounty(slug) ? slug : '';
  }

  function canonicalLocationPath(place) {
    if (place?.path) {
      return place.path;
    }

    const country = countryInput.value || contextLabel(place, 'country') || 'United Kingdom';
    const county = countyInput.value || contextLabel(place, 'district') || contextLabel(place, 'region') || '';
    const town = townInput.value || contextLabel(place, 'place') || place?.text || place?.place_name || '';

    const countrySlug = normalizeCountrySlug(country);
    let countySlug = normalizeCountySlug(county);
    let townSlug = slugify(town);

    if (townSlug === countrySlug) {
      townSlug = '';
    }

    if (countySlug && townSlug && countySlug === townSlug) {
      countySlug = '';
    }

    if (townSlug === 'london') {
      countySlug = '';
    }

    const segments = ['/locations', countrySlug];
    if (countySlug) segments.push(countySlug);
    if (townSlug && townSlug !== countrySlug && townSlug !== countySlug) {
      segments.push(townSlug);
    }
    return segments.join('/');
  }

  function localSuggestions(query) {
    const needle = normalizeText(query);
    if (!needle) {
      return [];
    }

    return localCatalog.filter((item) => {
      const haystack = normalizeText([
        item.text,
        item.place_name,
        item.country,
        item.county,
        item.district,
        item.region,
        item.title,
        item.slug,
      ].filter(Boolean).join(' '));
      return haystack.includes(needle);
    });
  }

  async function geocodeQuery(query) {
    if (!token || !query) return null;
    try {
      const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json`);
      url.searchParams.set('access_token', token);
      url.searchParams.set('autocomplete', 'true');
      url.searchParams.set('limit', '1');
      url.searchParams.set('types', 'place,postcode,locality,region,district,country');
      url.searchParams.set('country', 'gb');
      const res = await fetch(url.toString());
      if (!res.ok) return null;
      const data = await res.json();
      return Array.isArray(data.features) && data.features.length ? data.features[0] : null;
    } catch (error) {
      console.warn('[locations] mapbox geocode failed', error);
      return null;
    }
  }

  async function submitToCanonicalLocation() {
    const query = (input.value || '').trim();
    if (!query) return;

    if (selected?.path) {
      window.location.href = selected.path;
      return;
    }

    const localMatch = localSuggestions(query).find((item) => normalizeText(item.title || item.text) === normalizeText(query));
    if (localMatch && localMatch.path) {
      window.location.href = localMatch.path;
      return;
    }

    let place = selected;
    if (!place) {
      place = await geocodeQuery(query);
    }

    if (place) {
      if (place.path) {
        window.location.href = place.path;
        return;
      }
      window.location.href = canonicalLocationPath(place);
      return;
    }

    const fallback = new URL(form.action || window.location.origin + '/locations');
    fallback.searchParams.set('place', query);
    window.location.href = fallback.toString();
  }

  function showDropdown(items) {
    if (!dropdown) return;
    if (!items.length) {
      hideDropdown();
      return;
    }

    dropdown.hidden = false;
    dropdown.innerHTML = items.map((item, index) => {
      const main = item.text || item.title || item.place_name || '';
      const secondary = [
        contextLabel(item, 'place'),
        contextLabel(item, 'region'),
        contextLabel(item, 'country')
      ].filter(Boolean).join(', ') || item.place_name || '';
      return `
        <button type="button" data-index="${index}">
          <strong>${main}</strong>
          <span>${secondary}</span>
        </button>
      `;
    }).join('');
    input?.setAttribute('aria-expanded', 'true');
  }

  function setSelection(item) {
    selected = item;
    const place = item.text || item.place_name || input.value;
    input.value = place;
    postcodeInput.value = place;
    townInput.value = contextLabel(item, 'place') || item.text || '';
    cityInput.value = townInput.value;
    countyInput.value = contextLabel(item, 'region') || contextLabel(item, 'district') || '';
    countryInput.value = contextLabel(item, 'country') || '';
    latInput.value = item.center?.[1] ?? item.geometry?.coordinates?.[1] ?? '';
    lngInput.value = item.center?.[0] ?? item.geometry?.coordinates?.[0] ?? '';
    hideDropdown();
  }

  async function searchPlaces() {
    const query = (input.value || '').trim();

    clearTimeout(timer);
    timer = setTimeout(async () => {
      if (query.length < 2) {
        results = [];
        hideDropdown();
        return;
      }

      const localMatches = localSuggestions(query).slice(0, 6);
      if (localMatches.length) {
        results = localMatches;
        showDropdown(results);
        return;
      }

      if (!token) return;

      try {
        const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json`);
        url.searchParams.set('access_token', token);
        url.searchParams.set('autocomplete', 'true');
        url.searchParams.set('limit', '6');
        url.searchParams.set('types', 'place,postcode,locality,region,district,country');
        url.searchParams.set('country', 'gb');

        const res = await fetch(url.toString());
        if (!res.ok) throw new Error('mapbox geocode failed');
        const data = await res.json();
        results = Array.isArray(data.features) ? data.features : [];
        showDropdown(results);
      } catch (error) {
        console.warn('[locations] mapbox search failed', error);
        results = [];
        hideDropdown();
      }
    }, 220);
  }

  if (input) {
    input.addEventListener('input', function () {
      selected = null;
      postcodeInput.value = '';
      townInput.value = '';
      cityInput.value = '';
      countyInput.value = '';
      countryInput.value = '';
      latInput.value = '';
      lngInput.value = '';
      searchPlaces();
    });

    input.addEventListener('focus', function () {
      if (results.length) showDropdown(results);
    });
  }

  if (dropdown) {
    dropdown.addEventListener('mousedown', function (event) {
      const button = event.target.closest('button[data-index]');
      if (!button) return;
      const index = Number(button.getAttribute('data-index'));
      if (!Number.isFinite(index) || !results[index]) return;
      event.preventDefault();
      setSelection(results[index]);
      submitToCanonicalLocation();
    });
  }

  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      submitToCanonicalLocation();
    });
  }

  document.addEventListener('click', function (event) {
    if (!dropdown) return;
    if (!form.contains(event.target)) {
      hideDropdown();
      return;
    }
    if (!dropdown.contains(event.target) && event.target !== input) {
      hideDropdown();
    }
  });

  async function loadMapbox() {
    if (!canMap || !window.mapboxgl) return;
    if (!mapEl) return;

    const items = @json($mapItems);

    const center = [
      Number(mapEl.dataset.centerLng || -0.1276),
      Number(mapEl.dataset.centerLat || 51.5072),
    ];

    window.mapboxgl.accessToken = token;
    const map = new window.mapboxgl.Map({
      container: mapEl,
      style: 'mapbox://styles/mapbox/streets-v12',
      center,
      zoom: 8.6,
    });

    map.addControl(new window.mapboxgl.NavigationControl(), 'top-right');

    const bounds = new window.mapboxgl.LngLatBounds();
    let markerCount = 0;

    items.forEach((item) => {
      if (item.lat === null || item.lng === null) return;
      const el = document.createElement('div');
      el.className = 'wow-map-marker';
      el.style.cssText = 'width:16px;height:16px;border-radius:999px;background:#4f9381;border:2px solid #fff;box-shadow:0 0 0 4px rgba(79,147,129,.15);';
      new window.mapboxgl.Marker({ element: el, anchor: 'bottom' })
        .setLngLat([Number(item.lng), Number(item.lat)])
        .setPopup(new window.mapboxgl.Popup({ offset: 8 }).setHTML('<div style="font-weight:600">' + (item.title || '') + '</div>' + (item.distance ? '<div style="font-size:12px;color:#667085">' + item.distance + '</div>' : '')))
        .addTo(map);
      bounds.extend([Number(item.lng), Number(item.lat)]);
      markerCount++;
    });

    if (markerCount > 0) {
      map.fitBounds(bounds, { padding: 56, maxZoom: 11, duration: 0 });
    }
  }

  function ensureMapbox(cb) {
    if (!canMap) return;
    if (window.mapboxgl && window.mapboxgl.Map) {
      cb();
      return;
    }

    if (!document.querySelector('link[href*="mapbox-gl.css"]')) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css';
      document.head.appendChild(link);
    }

    const script = document.createElement('script');
    script.src = 'https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js';
    script.async = true;
    script.defer = true;
    script.onload = cb;
    document.head.appendChild(script);
  }

  ensureMapbox(loadMapbox);
})();
</script>
@endpush
