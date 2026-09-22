@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? 'We Offer Wellness™' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  @php
    $pageCanonical = $seo['canonical'] ?? url()->current();
    $seoService = app(\App\Services\SeoStructureService::class);
    $schemaFormat = $seoService->canonicalFormatKey((string) data_get($page, 'format', 'therapies'));
    $schemaModality = $seoService->categorySlug((string) data_get($page, 'modality', data_get($page, 'category_slug', '')));
    $schemaModalityLabel = trim((string) data_get($page, 'schema_term_label', ''));
    if ($schemaModalityLabel === '') {
      $schemaModalityLabel = $seoService->categoryLabel($schemaModality);
    }
    $schemaPageTitle = trim((string) ($page['title'] ?? $page['h1'] ?? 'Search'));
    $schemaDescription = trim((string) ($seo['description'] ?? ($page['description'] ?? '')));
    $schemaLocation = collect($savedLocation ?? []);
    $schemaLocationLabel = trim((string) ($schemaLocation['label'] ?? ''));
    if ($schemaLocationLabel === '') {
      $schemaLocationLabel = trim(implode(', ', array_filter([
        trim((string) ($schemaLocation['city'] ?? '')),
        trim((string) ($schemaLocation['region'] ?? '')),
        trim((string) ($schemaLocation['country'] ?? '')),
      ])));
    }
    $schemaOrganizationId = url('/') . '#organization';
    $schemaWebsiteId = url('/') . '#website';
    $schemaLogoUrl = 'https://studio.weofferwellness.co.uk/storage/uploads/images/e9dc87f9-01bf-4ffd-be8f-e1f49a85bf41.png';
    $schemaLocationPath = trim((string) data_get($schemaLocation, 'path', ''));
    $schemaLocationPlaceId = $schemaLocationPath !== '' ? url($schemaLocationPath) . '#place' : $pageCanonical . '#place';
    $schemaLocationCountry = trim((string) data_get($schemaLocation, 'country', ''));
    $schemaLocationCountryCode = $schemaLocationCountry !== '' && preg_match('/^(united kingdom|uk|u\.k\.|great britain|gb|england|scotland|wales|northern ireland)$/i', $schemaLocationCountry)
      ? 'GB'
      : $schemaLocationCountry;
    $schemaItemType = trim((string) data_get($page, 'schema_item_type', 'Service'));
    if (!in_array($schemaItemType, ['Service', 'Event'], true)) {
      $schemaItemType = 'Service';
    }
    $schemaFaqItems = collect($page['faqs'] ?? [])
      ->map(function (array $faq) {
        return [
          'q' => trim((string) ($faq['q'] ?? '')),
          'a' => trim((string) ($faq['a'] ?? '')),
        ];
      })
      ->filter(fn (array $faq) => $faq['q'] !== '' && $faq['a'] !== '')
      ->values()
      ->all();

    $schemaBreadcrumbCrumbs = collect($pageCrumbs ?? [])
      ->map(function ($crumb) {
        return [
          'label' => trim((string) data_get($crumb, 'label', '')),
          'url' => trim((string) data_get($crumb, 'url', '')),
        ];
      })
      ->filter(fn (array $crumb) => $crumb['label'] !== '')
      ->values()
      ->all();

    $primaryCta = (array) ($page['primary_cta'] ?? []);
    $secondaryCta = (array) ($page['secondary_cta'] ?? []);
    $supportingCta = (array) ($page['supporting_cta'] ?? []);
    $locationSectionTitle = (string) ($page['location_section_title'] ?? 'Popular locations');
    $locationSectionIntro = (string) ($page['location_section_intro'] ?? 'These location pages are useful starting points for finding live listings by county, town or region.');
    $relatedLinksTitle = (string) ($page['related_links_title'] ?? 'Related pages');
    $relatedLinksIntro = (string) ($page['related_links_intro'] ?? 'These pages support the same search intent without creating duplicate URL families.');

    if ($schemaBreadcrumbCrumbs === []) {
      $pageTitle = trim((string) ($page['title'] ?? $page['h1'] ?? 'Search'));
      $pageBreadcrumb = trim((string) preg_replace('/\s*\|.*$/', '', $pageTitle));
      if ($pageBreadcrumb === '') {
        $pageBreadcrumb = trim((string) ($page['h1'] ?? 'Search'));
      }
      $schemaBreadcrumbCrumbs = [
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Search', 'url' => $searchBreadcrumbUrl ?? url('/search')],
        ['label' => $pageBreadcrumb],
      ];
    }
    $pageCrumbs = $schemaBreadcrumbCrumbs;

    $schemaLocationPlace = null;
    if ($schemaLocationLabel !== '' || $schemaLocationPath !== '') {
      $schemaLocationPlace = array_filter([
        '@type' => 'Place',
        '@id' => $schemaLocationPlaceId,
        'name' => $schemaLocationLabel !== '' ? $schemaLocationLabel : trim((string) data_get($schemaLocation, 'city', '')),
        'address' => array_filter([
          '@type' => 'PostalAddress',
          'addressLocality' => trim((string) data_get($schemaLocation, 'city', '')) ?: null,
          'addressRegion' => trim((string) data_get($schemaLocation, 'region', '')) ?: null,
          'addressCountry' => $schemaLocationCountryCode !== '' ? $schemaLocationCountryCode : null,
        ], static fn ($value) => $value !== null && $value !== ''),
      ], static fn ($value) => $value !== null && $value !== '');
    }

    $schemaItemList = collect($products ?? [])
      ->values()
      ->take(12)
      ->map(function ($product, $index) use ($seoService, $schemaModalityLabel, $schemaItemType, $pageCanonical, $schemaLocationPlace) {
          $url = $seoService->canonicalProductUrl($product);
          $title = trim((string) ($product->title ?? $product->name ?? 'Offering'));
          $image = method_exists($product, 'getFirstImageUrl') ? trim((string) $product->getFirstImageUrl()) : '';
          $hasImage = method_exists($product, 'hasDisplayableImage')
            ? (bool) $product->hasDisplayableImage()
            : ($image !== '' && ! str_contains($image, 'no-product-image.jpg'));
          $providerName = trim((string) (
            data_get($product, 'vendor.vendor_name')
            ?? data_get($product, 'vendor_name')
            ?? ''
          ));
          $price = data_get($product, 'variants_min_price', data_get($product, 'price', null));

          $schemaItem = [
            '@type' => $schemaItemType === 'Event' ? 'Event' : 'Service',
            '@id' => $url . ($schemaItemType === 'Event' ? '#event' : '#service'),
            'name' => $title,
            'url' => $url,
            'description' => trim((string) data_get($product, 'benefit', data_get($product, 'summary', data_get($product, 'description', '')))),
            'image' => $hasImage && $image !== '' ? [$image] : null,
          ];

          if ($schemaItemType === 'Event') {
            $schemaItem['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
            $schemaItem['eventStatus'] = 'https://schema.org/EventScheduled';
          } else {
            $schemaItem['serviceType'] = $schemaModalityLabel !== '' ? $schemaModalityLabel : 'Wellness service';
          }

          if ($providerName !== '') {
            $schemaItem['provider'] = [
              '@type' => 'Organization',
              'name' => $providerName,
            ];
          }

          if ($schemaLocationPlace !== null) {
            $schemaItem['areaServed'] = [
              '@id' => $pageCanonical . '#place',
            ];
          }

          if (is_numeric($price) && (float) $price > 0) {
            $schemaItem['offers'] = [
              '@type' => 'Offer',
              'url' => $url,
              'price' => number_format((float) $price, 2, '.', ''),
              'priceCurrency' => 'GBP',
              'availability' => 'https://schema.org/InStock',
            ];
          }

          $schemaItem = array_filter($schemaItem, static fn ($value) => $value !== null && $value !== '');

          return [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => $url,
            'item' => $schemaItem,
          ];
      })
      ->values()
      ->all();

    $schemaJsonLd = [
      '@context' => 'https://schema.org',
      '@graph' => array_values(array_filter([
        [
          '@type' => 'Organization',
          '@id' => $schemaOrganizationId,
          'name' => 'We Offer Wellness®',
          'url' => url('/'),
          'logo' => [
            '@type' => 'ImageObject',
            '@id' => $schemaOrganizationId . '-logo',
            'url' => $schemaLogoUrl,
          ],
        ],
        [
          '@type' => 'WebSite',
          '@id' => $schemaWebsiteId,
          'url' => url('/'),
          'name' => 'We Offer Wellness®',
          'publisher' => [
            '@id' => $schemaOrganizationId,
          ],
          'inLanguage' => 'en-GB',
        ],
        $schemaModalityLabel !== '' ? [
          '@type' => 'DefinedTerm',
          '@id' => $pageCanonical . '#modality',
          'name' => $schemaModalityLabel,
          'termCode' => $schemaModality,
          'inDefinedTermSet' => 'Wellness Modalities',
        ] : null,
        [
          '@type' => 'CollectionPage',
          '@id' => $pageCanonical . '#webpage',
          'url' => $pageCanonical,
          'name' => $schemaPageTitle,
          'description' => $schemaDescription,
          'about' => $schemaModalityLabel !== '' ? [
            '@id' => $pageCanonical . '#modality',
          ] : null,
          'mainEntity' => [
            '@id' => $pageCanonical . '#itemlist',
          ],
          'spatialCoverage' => $schemaLocationPlace !== null ? [
            '@id' => $schemaLocationPlaceId,
          ] : null,
          'contentLocation' => $schemaLocationPlace !== null ? [
            '@id' => $schemaLocationPlaceId,
          ] : null,
          'breadcrumb' => [
            '@id' => $pageCanonical . '#breadcrumb',
          ],
          'isPartOf' => [
            '@id' => $schemaWebsiteId,
          ],
          'publisher' => [
            '@id' => $schemaOrganizationId,
          ],
          'inLanguage' => 'en-GB',
        ],
        $schemaLocationPlace,
        [
          '@type' => 'ItemList',
          '@id' => $pageCanonical . '#itemlist',
          'name' => $schemaPageTitle . ' listings',
          'numberOfItems' => count($schemaItemList),
          'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
          'itemListElement' => $schemaItemList,
        ],
        $schemaFaqItems !== [] ? [
          '@type' => 'FAQPage',
          '@id' => $pageCanonical . '#faq',
          'mainEntity' => array_map(static function (array $faq): array {
            return [
              '@type' => 'Question',
              'name' => $faq['q'],
              'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['a'],
              ],
            ];
          }, $schemaFaqItems),
        ] : null,
      ])),
    ];
  @endphp
  <script type="application/ld+json">{!! json_encode($schemaJsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}</script>
  <style>
    .show-structured-near-me-page{
      --ink:#101828;
      --muted:#596275;
      --line:#dfe4ea;
      --line-soft:#edf0f2;
      --green:#4f9381;
      --green-dark:#417c6d;
      --green-soft:#e8f5f1;
      --gold-soft:#ffe5b3;
      --gold-text:#6f4b10;
      --blue-soft:#e8f0ff;
      --blue-text:#254a85;
      padding:0 0 84px;
    }
    .show-structured-near-me-grid{
      display:none;
      grid-template-columns:minmax(0,1fr) minmax(320px,.86fr);
      gap:24px;
      align-items:stretch;
    }
    .show-structured-near-me-copy,
    .show-structured-near-me-panel,
    .show-structured-near-me-section{
      background:rgba(255,255,255,.98);
      border:1px solid var(--line);
      border-radius:22px;
      box-shadow:0 18px 54px rgba(16,24,40,.07);
    }
    .show-structured-near-me-copy{
      padding:32px;
    }
    .show-structured-near-me-kicker{
      margin:0 0 10px;
      color:#344054;
      font-size:13px;
      font-weight:300;
      letter-spacing:.16em;
      text-transform:uppercase;
    }
    .show-structured-near-me-copy h1,
    .show-structured-near-me-panel h2,
    .show-structured-near-me-section h2{
      margin:0;
      color:var(--ink);
      font-family:"Playfair Display", Georgia, serif;
      font-weight:500;
      letter-spacing:-.055em;
    }
    .show-structured-near-me-copy h1{
      max-width:11ch;
      font-size:clamp(42px,5.8vw,78px);
      line-height:.94;
    }
    .show-structured-near-me-copy p,
    .show-structured-near-me-panel p,
    .show-structured-near-me-section p{
      color:var(--muted);
      line-height:1.6;
    }
    .show-structured-near-me-copy p{
      max-width:68ch;
      margin:16px 0 0;
      font-size:17px;
    }
    .show-structured-near-me-points{
      display:grid;
      gap:10px;
      margin-top:24px;
    }
    .show-structured-near-me-point{
      display:flex;
      gap:10px;
      align-items:flex-start;
      color:#344054;
      font-size:14px;
      line-height:1.45;
    }
    .show-structured-near-me-point::before{
      content:"✓";
      width:22px;
      height:22px;
      flex:0 0 22px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius:999px;
      background:var(--green-soft);
      color:var(--green);
      font-size:12px;
      font-weight:800;
      margin-top:1px;
    }
    .show-structured-near-me-actions{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:26px;
    }
    .show-structured-near-me-panel{
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      gap:18px;
      padding:26px;
      background:linear-gradient(180deg, rgba(232,245,241,.72), rgba(255,255,255,.96));
    }
    .show-structured-near-me-panel h2{
      font-size:clamp(34px,4vw,54px);
      line-height:.96;
    }
    .show-structured-near-me-panel p{
      margin:14px 0 0;
      font-size:15px;
      line-height:1.55;
    }
    .show-structured-near-me-search{
      display:grid;
      gap:10px;
      margin-top:18px;
      padding:18px;
      border:1px solid var(--line-soft);
      border-radius:18px;
      background:#fff;
    }
    .show-structured-near-me-search label{
      color:#344054;
      font-size:13px;
      font-weight:700;
    }
    .show-structured-near-me-search .row{
      display:grid;
      grid-template-columns:1fr auto;
      gap:10px;
    }
    .show-structured-near-me-search input{
      width:100%;
      height:44px;
      border:1px solid #d0d5dd;
      border-radius:14px;
      background:#fff;
      color:#111827;
      padding:0 14px;
      font-size:15px;
      outline:none;
    }
    .show-structured-near-me-search input:focus{
      border-color:var(--green);
      box-shadow:0 0 0 3px rgba(79,147,129,.14);
    }
    .show-structured-near-me-saved-location{
      margin-top:10px;
      padding:10px 12px;
      border-radius:14px;
      background:#f3fbf8;
      color:var(--ink);
      border:1px solid rgba(79,147,129,.18);
      font-size:13px;
    }
    .show-structured-near-me-search .btn{
      min-height:44px;
      border-radius:14px;
    }
    .show-structured-near-me-search{
      position:relative;
    }
    .show-structured-near-me-search__dropdown{
      position:absolute;
      left:0;
      right:0;
      top:100%;
      z-index:30;
      margin-top:8px;
      border:1px solid var(--line-soft);
      border-radius:16px;
      background:#fff;
      box-shadow:0 18px 42px rgba(16,24,40,.12);
      overflow:hidden;
    }
    .show-structured-near-me-search__dropdown button{
      width:100%;
      display:block;
      text-align:left;
      padding:12px 14px;
      border:none;
      border-bottom:1px solid var(--line-soft);
      background:#fff;
      cursor:pointer;
    }
    .show-structured-near-me-search__dropdown button:last-child{
      border-bottom:none;
    }
    .show-structured-near-me-search__dropdown button:hover,
    .show-structured-near-me-search__dropdown button:focus-visible{
      background:#f8fafc;
      outline:none;
    }
    .show-structured-near-me-search__dropdown strong{
      display:block;
      color:var(--ink);
      font-size:14px;
    }
    .show-structured-near-me-search__dropdown span{
      display:block;
      margin-top:3px;
      color:var(--muted);
      font-size:12px;
    }
    .show-structured-near-me-shell{
      margin-top:22px;
    }
    .show-structured-near-me-section{
      padding:26px;
      margin-top:24px;
    }
    .show-structured-near-me-section h2{
      font-size:clamp(30px,4vw,52px);
      line-height:.96;
    }
    .show-structured-near-me-section p{
      margin:0px;
      font-size:12.75px;
    }
    .location-landing__section{padding-top:38px}
    .location-landing__head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:15px}
    .location-landing__head h2{margin:0;color:var(--ink);font-family:"Playfair Display",Georgia,serif;font-size:clamp(30px,4vw,48px);font-weight:500;line-height:1;letter-spacing:-.045em}
    .location-landing__copy{margin:8px 0 0;color:var(--muted);font-size:15px;line-height:1.5}
    .location-landing__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .location-landing__empty{padding:20px;border:1px solid var(--line);border-radius:4px;color:var(--muted)}
    @media(min-width:1200px){.location-landing__grid{grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}}
    @media(min-width:768px) and (max-width:1199.98px){.location-landing__grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}}
    @media(max-width:900px){.location-landing__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:560px){.location-landing__head{display:block}.location-landing__grid{gap:16px}}
    .show-structured-near-me-grid-cards{
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:18px;
      margin-top:22px;
    }
    .show-structured-near-me-listing{
      margin-top:24px;
    }
    .show-structured-near-me-empty{
      margin-top:18px;
      padding:18px;
      border:1px dashed #d0d5dd;
      border-radius:18px;
      background:#fcfcfd;
      color:#344054;
    }
    .show-structured-near-me-links{
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:14px;
      margin-top:18px;
    }
    .show-structured-near-me-linkcard{
      display:block;
      padding:16px;
      border:1px solid var(--line-soft);
      border-radius:16px;
      background:#fff;
      text-decoration:none;
      box-shadow:0 10px 26px rgba(16,24,40,.04);
    }
    .show-structured-near-me-linkcard strong{
      display:block;
      color:var(--ink);
      font-size:15px;
      line-height:1.35;
    }
    .show-structured-near-me-linkcard span{
      display:block;
      margin-top:4px;
      color:var(--muted);
      font-size:13px;
      line-height:1.4;
    }
    .show-structured-near-me-faq{
      display:grid;
      gap:12px;
      margin-top:18px;
    }
    .show-structured-near-me-faq details{
      padding:16px 18px;
      border:1px solid var(--line-soft);
      border-radius:16px;
      background:#fff;
    }
    .show-structured-near-me-faq summary{
      cursor:pointer;
      list-style:none;
      font-weight:700;
      color:var(--ink);
    }
    .show-structured-near-me-faq summary::-webkit-details-marker{
      display:none;
    }
    .show-structured-near-me-faq p{
      margin-top:10px;
    }
    @media (max-width: 992px){
      .show-structured-near-me-grid,
      .show-structured-near-me-grid-cards,
      .show-structured-near-me-links{
        grid-template-columns:1fr;
      }
    }
    @media (max-width: 560px){
      .show-structured-near-me-copy,
      .show-structured-near-me-panel,
      .show-structured-near-me-section{
        padding:20px;
      }
      .show-structured-near-me-search .row{
        grid-template-columns:1fr;
      }
      .show-structured-near-me-copy h1{
        font-size:42px;
      }
    }
  </style>
@endpush

@section('content')
@php
  $popularLocations = collect($popularLocations ?? []);
  $products = collect($products ?? []);
  $savedLocation = collect($savedLocation ?? []);
  $catalogSuggestions = collect($catalogSuggestions ?? []);
  $locationTiles = $popularLocations->map(function (array $location): array {
    return array_merge($location, [
      'path' => $location['path'] ?? '/locations',
      'url' => $location['search_url'] ?? url($location['path'] ?? '/locations'),
      'supply_count' => (int) ($location['supply_count'] ?? data_get($location, 'counts.total', 0)),
    ]);
  })->values();
  $locationExplorerFeatured = $locationTiles->first();
  $locationExplorerItems = $locationTiles->skip(1)->values()->all();
  $hasOnlineSessions = collect($page['highlights'] ?? [])->contains(fn ($item) => str_contains(strtolower((string) $item), 'online'))
    || collect($page['related_links'] ?? [])->contains(fn ($item) => str_contains(strtolower((string) ($item['label'] ?? '')), 'online'));
  $heroImage = trim((string) ($savedLocation['image_path'] ?? ''));
  if ($heroImage !== '' && !preg_match('#^(?:https?:)?//#i', $heroImage)) {
    $heroImage = rtrim((string) config('services.location_media_url', 'https://studio.weofferwellness.co.uk'), '/') . '/storage/' . ltrim($heroImage, '/');
  }
@endphp

@include('partials.breadcrumbs', [
  'crumbs' => $pageCrumbs ?? [],
  'schemaUrl' => $pageCanonical ?? url()->current(),
  'currentIcon' => !empty($savedLocation['label']) ? 'location' : '',
])

@include('partials.landing-hero', [
  'heroEyebrow' => $page['kicker'] ?? 'Local wellness discovery',
  'heroTitle' => $page['h1'] ?? ($page['title'] ?? 'Wellness near you'),
  'heroIntro' => $page['intro'] ?? ($seo['description'] ?? ''),
  'heroImage' => $heroImage,
  'heroLocationLabel' => $savedLocation['label'] ?? '',
  'heroIsLocation' => true,
  'heroActions' => array_values(array_filter([
    !empty($primaryCta['label']) ? ['label' => $primaryCta['label'], 'href' => '#results'] : null,
    !empty($secondaryCta['label']) ? ['label' => $secondaryCta['label'], 'href' => '#faq', 'style' => 'outline'] : null,
  ])),
  'heroAsideTitle' => null,
  'heroAsideText' => '',
])

@include('partials.hero-meta', [
  'items' => array_values(array_filter([
    $products->isNotEmpty() ? ['label' => 'Local listings', 'strong' => true] : null,
    $popularLocations->isNotEmpty() ? 'Nearby options' : null,
    $hasOnlineSessions ? 'Online sessions' : null,
  ])),
])

<section class="show-structured-near-me-page">
  <div class="container-page">
    <div class="show-structured-near-me-grid">
      <div class="show-structured-near-me-copy">
        <div class="show-structured-near-me-kicker">{{ $page['kicker'] ?? 'Search' }}</div>
        <h1>{{ $page['h1'] ?? $page['title'] }}</h1>
        <p>{{ $page['intro'] ?? '' }}</p>

        <div class="show-structured-near-me-points">
          @foreach(($page['highlights'] ?? []) as $point)
            <div class="show-structured-near-me-point">{{ $point }}</div>
          @endforeach
        </div>

        <div class="show-structured-near-me-actions">
          <button type="button" class="btn btn-primary" data-scroll-target="results">{{ $primaryCta['label'] ?? 'Browse live listings' }}</button>
          <button type="button" class="btn btn-light" data-scroll-target="{{ !empty($secondaryCta['href']) && $secondaryCta['href'] === '#related-pages' ? 'related-pages' : 'faq' }}">{{ $secondaryCta['label'] ?? 'Read FAQs' }}</button>
          @if(!empty($supportingCta['label']))
            <a href="{{ $supportingCta['href'] ?? '#' }}" class="btn btn-outline-secondary">{{ $supportingCta['label'] }}</a>
          @endif
        </div>
      </div>

      <aside class="show-structured-near-me-panel">
        <div>
          <h2>Start with your location</h2>
          <p>{{ $page['search_helper'] ?? 'Enter your town or postcode to narrow the results.' }}</p>
          @if(!empty($savedLocation['label']))
            <div class="show-structured-near-me-saved-location">
              Using your saved location: <strong>{{ $savedLocation['label'] }}</strong>
            </div>
          @endif
        </div>

        <form class="show-structured-near-me-search" method="get" action="{{ $searchAction ?? url()->current() }}" id="moneyLocationSearchForm" autocomplete="off">
          <label for="money-place">Location</label>
          <div class="row">
            <input id="money-place" name="place" type="text" placeholder="{{ $page['search_placeholder'] ?? 'e.g. Maidstone' }}" value="{{ request()->query('place', request()->query('postcode', $savedLocation['label'] ?? '')) }}">
            <button class="btn btn-primary" type="submit">Search</button>
          </div>
          <p class="text-muted mb-0" style="font-size:13px; line-height:1.45;">
            We will use this to help you filter {{ $page['result_label'] ?? 'live listings' }} by location and remember your choice for next time.
          </p>
          <input type="hidden" id="money-city" name="city" value="{{ request()->query('city', $savedLocation['city'] ?? '') }}">
          <input type="hidden" id="money-region" name="region" value="{{ request()->query('region', $savedLocation['region'] ?? '') }}">
          <input type="hidden" id="money-country" name="country" value="{{ request()->query('country', $savedLocation['country'] ?? '') }}">
          <input type="hidden" id="money-lat" name="lat" value="{{ request()->query('lat', $savedLocation['lat'] ?? '') }}">
          <input type="hidden" id="money-lng" name="lng" value="{{ request()->query('lng', $savedLocation['lng'] ?? '') }}">
          <input type="hidden" id="money-location-path" name="location_path" value="{{ ltrim((string) ($savedLocation['path'] ?? ''), '/') }}">
          <div id="moneyLocationDropdown" class="show-structured-near-me-search__dropdown" hidden></div>
        </form>

        <div>
          <h2 style="font-size:clamp(24px,2.8vw,32px);">{{ $locationSectionTitle }}</h2>
          <p>{{ $locationSectionIntro }}</p>
        </div>
      </aside>
    </div>

    <section class="location-landing__section" data-money-section="results">
      <div class="location-landing__head">
        <div>
          <h2>{{ $page['result_label'] ?? 'Live listings' }}</h2>
          <p class="location-landing__copy">{{ $page['result_intro'] ?? 'Browse the strongest matches available now.' }}</p>
        </div>
      </div>

      @if($products->isNotEmpty())
        <div class="location-landing__grid">
          @foreach($products as $product)
            @include('partials.product_card_v4_1', ['product' => $product])
          @endforeach
        </div>
      @else
        <div class="location-landing__empty">
          {{ $page['empty_state'] ?? 'We do not currently have direct local listings for this exact search, so start with online options, nearby towns and related format pages above.' }}
        </div>
      @endif
    </section>

    @if($locationExplorerFeatured !== null)
      @include('partials.location-explorer', [
        'eyebrow' => 'Explore nearby',
        'heading' => $locationSectionTitle,
        'intro' => $locationSectionIntro,
        'featured' => $locationExplorerFeatured,
        'items' => $locationExplorerItems,
        'id' => 'nearby-location-heading',
      ])
    @endif

    <section class="show-structured-near-me-section" id="related-pages">
      <h2>{{ $relatedLinksTitle }}</h2>
      <p>{{ $relatedLinksIntro }}</p>
      <div class="show-structured-near-me-links">
        @foreach(($page['related_links'] ?? []) as $link)
          <a class="show-structured-near-me-linkcard" href="{{ $link['href'] ?? '#' }}">
            <strong>{{ $link['label'] ?? 'Related page' }}</strong>
            <span>Open the canonical landing page</span>
          </a>
        @endforeach
      </div>
    </section>

    @if(!empty($page['faqs'] ?? []))
      <section class="show-structured-near-me-section" data-money-section="faq">
        <h2>Frequently asked questions</h2>
        <div class="show-structured-near-me-faq">
          @foreach(($page['faqs'] ?? []) as $faq)
            <details>
              <summary>{{ $faq['q'] ?? '' }}</summary>
              <p>{{ $faq['a'] ?? '' }}</p>
            </details>
          @endforeach
        </div>
      </section>
    @endif

    @include('partials.guide_panel', [
      'guidePanelModality' => data_get($page, 'modality', data_get($page, 'category_slug', request()->route('modality'))),
      'guidePanelFormat' => data_get($page, 'format', null),
    ])
  </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
  const token = @json(config('services.mapbox.token'));
  const form = document.getElementById('moneyLocationSearchForm');
  const input = document.getElementById('money-place');
  const cityInput = document.getElementById('money-city');
  const regionInput = document.getElementById('money-region');
  const countryInput = document.getElementById('money-country');
  const latInput = document.getElementById('money-lat');
  const lngInput = document.getElementById('money-lng');
  const pathInput = document.getElementById('money-location-path');
  const dropdown = document.getElementById('moneyLocationDropdown');
  const catalog = @json($catalogSuggestions->values());
  const baseUrl = @json($searchAction ?? url()->current());
  const autoLocateOnLoad = String(baseUrl || '').includes('-near-me');
  const promptCookieName = 'wow_location_prompt_v2';
  const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
  const basePath = new URL(baseUrl, window.location.origin).pathname.replace(/\/+$/, '') || '/';
  const shouldAutoLocate = autoLocateOnLoad && currentPath === basePath;

  let timer = null;
  let results = [];
  let selected = null;

  function normalizeText(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[\u2018\u2019\u201c\u201d]/g, "'")
      .replace(/&/g, ' and ')
      .replace(/[^a-z0-9]+/g, ' ')
      .replace(/\b(and|of|the)\b/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function contextLabel(place, prefix) {
    const context = Array.isArray(place?.context) ? place.context : [];
    const match = context.find((item) => String(item?.id || '').startsWith(prefix + '.'));
    return match && match.text ? String(match.text) : '';
  }

  function hideDropdown() {
    if (!dropdown) return;
    dropdown.hidden = true;
    dropdown.innerHTML = '';
  }

  function cookieGet(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&') + '=([^;]*)'));
    if (!match) return '';
    try { return decodeURIComponent(match[1]); } catch { return match[1] || ''; }
  }

  function cookieSet(name, value, days) {
    const maxAge = days ? days * 24 * 60 * 60 : 60 * 60 * 24 * 365 * 5;
    document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
  }

  function storeResolvedLocation(payload) {
    try {
      cookieSet('wow_location', JSON.stringify(payload), 3650);
      if (payload?.path) cookieSet('wow_location_path', String(payload.path), 3650);
      cookieSet(promptCookieName, '1', 3650);
      cookieSet('wow_geo_done', '1', 3650);
      if (payload?.city) cookieSet('wow_city', payload.city, 3650);
      if (payload?.region) cookieSet('wow_region', payload.region, 3650);
      if (payload?.country) cookieSet('wow_country', payload.country, 3650);
      if (payload?.coords?.lat !== undefined) cookieSet('wow_lat', String(payload.coords.lat), 3650);
      if (payload?.coords?.lng !== undefined) cookieSet('wow_lng', String(payload.coords.lng), 3650);
    } catch {}
  }

  async function persistGeo(data) {
    try {
      await fetch('/api/geo', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || window.__csrfToken || '',
        },
        body: JSON.stringify(data),
      });
    } catch {}
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
  }

  function localSuggestions(query) {
    const needle = normalizeText(query);
    if (!needle) return [];
    return catalog.filter((item) => {
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

  function resolveCatalogLocation({ city = '', region = '', country = '', name = '' } = {}) {
    const cityNeedle = normalizeText(city);
    const regionNeedle = normalizeText(region);
    const countryNeedle = normalizeText(country);
    const nameNeedle = normalizeText(name);

    let best = null;
    let bestScore = 0;

    catalog.forEach((item) => {
      const path = String(item?.path || '');
      if (!path || item?.online) return;

      const itemCity = normalizeText(item?.city || item?.town || item?.title || item?.label || '');
      const itemRegion = normalizeText(item?.county || item?.district || item?.region || '');
      const itemCountry = normalizeText(item?.country || '');
      const itemTitle = normalizeText([item?.title, item?.label, item?.slug].filter(Boolean).join(' '));

      let score = 0;

      if (cityNeedle && itemCity) {
        if (itemCity === cityNeedle) score += 12;
        else if (itemCity.includes(cityNeedle) || cityNeedle.includes(itemCity)) score += 8;
      }

      if (regionNeedle && itemRegion) {
        if (itemRegion === regionNeedle) score += 6;
        else if (itemRegion.includes(regionNeedle) || regionNeedle.includes(itemRegion)) score += 4;
      }

      if (countryNeedle && itemCountry) {
        if (itemCountry === countryNeedle) score += 3;
        else if (itemCountry.includes(countryNeedle) || countryNeedle.includes(itemCountry)) score += 1;
      }

      if (nameNeedle && itemTitle) {
        if (itemTitle === nameNeedle) score += 5;
        else if (itemTitle.includes(nameNeedle) || nameNeedle.includes(itemTitle)) score += 2;
      }

      if (cityNeedle && normalizeText(path).includes(cityNeedle)) score += 2;
      if (regionNeedle && normalizeText(path).includes(regionNeedle)) score += 1;
      if (score > bestScore) {
        bestScore = score;
        best = item;
      }
    });

    return best;
  }

  function readSavedLocation() {
    let parsed = null;
    try {
      const raw = cookieGet('wow_location');
      parsed = raw ? JSON.parse(raw) : null;
    } catch {
      parsed = null;
    }

    const saved = {
      name: parsed?.name || '',
      city: parsed?.city || cookieGet('wow_city') || '',
      region: parsed?.region || cookieGet('wow_region') || '',
      country: parsed?.country || cookieGet('wow_country') || '',
      path: cookieGet('wow_location_path') || parsed?.path || '',
      coords: parsed?.coords || {
        lat: cookieGet('wow_lat') || '',
        lng: cookieGet('wow_lng') || '',
      },
    };

    const match = saved.path
      ? (catalog.find((item) => String(item?.path || '') === String(saved.path)) || null)
      : resolveCatalogLocation(saved);

    if (!match && !saved.city && !saved.region && !saved.country) {
      return null;
    }

    return {
      ...saved,
      match,
      path: match?.path || saved.path || '',
      city: saved.city || match?.city || match?.town || '',
      region: saved.region || match?.county || match?.district || match?.region || '',
      country: saved.country || match?.country || '',
      name: saved.name || match?.title || match?.label || '',
    };
  }

  function setSelectionContext(item) {
    const context = Array.isArray(item?.context) ? item.context : [];
    const place = item?.text || item?.title || item?.place_name || input?.value || '';
    const city = item?.city || context.find((c) => String(c?.id || '').startsWith('place.'))?.text || context.find((c) => String(c?.id || '').startsWith('locality.'))?.text || place;
    const region = item?.region || context.find((c) => String(c?.id || '').startsWith('region.'))?.text || context.find((c) => String(c?.id || '').startsWith('district.'))?.text || item?.county || item?.district || '';
    const country = item?.country || context.find((c) => String(c?.id || '').startsWith('country.'))?.text || '';
    const lat = item?.center?.[1] ?? item?.geometry?.coordinates?.[1] ?? item?.lat ?? '';
    const lng = item?.center?.[0] ?? item?.geometry?.coordinates?.[0] ?? item?.lng ?? '';
    const catalogMatch = resolveCatalogLocation({
      city,
      region,
      country,
      name: item?.place_name || item?.text || place,
    });

    if (input) input.value = place;
    if (cityInput) cityInput.value = city || '';
    if (regionInput) regionInput.value = region || '';
    if (countryInput) countryInput.value = country || '';
    if (latInput) latInput.value = lat || '';
    if (lngInput) lngInput.value = lng || '';
    if (pathInput) {
      const resolvedPath = item?.path || catalogMatch?.path || '';
      pathInput.value = resolvedPath ? String(resolvedPath).replace(/^\/locations\/?/, '') : '';
    }
  }

  function submitToCategoryPage() {
    const query = (input?.value || '').trim();
    if (!query) return;

    if (pathInput?.value) {
      const nextPath = String(pathInput.value).replace(/^\/+/, '').replace(/^locations\/?/, '');
      window.location.href = `${baseUrl.replace(/\/+$/, '')}/${nextPath}`;
      return;
    }

    const target = new URL(baseUrl, window.location.origin);
    target.searchParams.set('place', query);
    if (cityInput?.value) target.searchParams.set('city', cityInput.value);
    if (regionInput?.value) target.searchParams.set('region', regionInput.value);
    if (countryInput?.value) target.searchParams.set('country', countryInput.value);
    if (latInput?.value) target.searchParams.set('lat', latInput.value);
    if (lngInput?.value) target.searchParams.set('lng', lngInput.value);
    window.location.href = target.toString();
  }

  function applyResolvedLocationToForm(savedLocation) {
    if (!savedLocation) return false;
    const path = String(savedLocation.path || '').replace(/^\/+/, '').replace(/^locations\/?/, '');
    if (savedLocation.name && input) input.value = savedLocation.name;
    if (cityInput) cityInput.value = savedLocation.city || '';
    if (regionInput) regionInput.value = savedLocation.region || '';
    if (countryInput) countryInput.value = savedLocation.country || '';
    if (pathInput) pathInput.value = path;
    if (savedLocation.coords?.lat !== undefined && latInput) latInput.value = String(savedLocation.coords.lat || '');
    if (savedLocation.coords?.lng !== undefined && lngInput) lngInput.value = String(savedLocation.coords.lng || '');
    return Boolean(path);
  }

  function redirectUsingSavedLocation() {
    const savedLocation = readSavedLocation();
    if (!savedLocation) return false;
    const hasCanonicalPath = applyResolvedLocationToForm(savedLocation);
    if (hasCanonicalPath && pathInput?.value) {
      const nextPath = String(pathInput.value).replace(/^\/+/, '').replace(/^locations\/?/, '');
      window.location.href = `${baseUrl.replace(/\/+$/, '')}/${nextPath}`;
      return true;
    }
    return false;
  }

  async function resolveBrowserLocationAndSubmit() {
    if (!shouldAutoLocate) return;
    if (!navigator.geolocation) return;
    if (redirectUsingSavedLocation()) return;

    navigator.geolocation.getCurrentPosition(async (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      let city = '';
      let region = '';
      let country = '';
      let name = '';
      let resolvedPath = '';

      try {
        if (token) {
          const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json`);
          url.searchParams.set('access_token', token);
          url.searchParams.set('limit', '1');
          const res = await fetch(url.toString());
          if (res.ok) {
            const data = await res.json();
            const place = Array.isArray(data.features) && data.features.length ? data.features[0] : null;
            if (place) {
              selected = place;
              setSelectionContext(place);
              name = place.place_name || place.text || '';
              resolvedPath = pathInput?.value ? String(pathInput.value) : '';
              city = cityInput?.value || '';
              region = regionInput?.value || '';
              country = countryInput?.value || '';
            }
          }
        }
      } catch (error) {
        console.warn('[seo-money] auto geolocation mapbox reverse geocode failed', error);
      }

      if (!resolvedPath) {
        const fallbackTarget = new URL(baseUrl, window.location.origin);
        fallbackTarget.searchParams.set('lat', String(lat));
        fallbackTarget.searchParams.set('lng', String(lng));
        if (city) fallbackTarget.searchParams.set('city', city);
        if (region) fallbackTarget.searchParams.set('region', region);
        if (country) fallbackTarget.searchParams.set('country', country);
        window.location.href = fallbackTarget.toString();
        return;
      }

      const payload = {
        name: name || city || 'Current location',
        city,
        region,
        country,
        path: resolvedPath || '',
        coords: { lat, lng },
      };

      storeResolvedLocation(payload);
      await persistGeo({ lat, lng, city, region, country, mode: 'mixed' });
      submitToCategoryPage();
    }, () => {}, {
      enableHighAccuracy: true,
      timeout: 5000,
      maximumAge: 60000,
    });
  }

  async function searchPlaces() {
    const query = (input?.value || '').trim();
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
        console.warn('[seo-money] mapbox search failed', error);
        results = [];
        hideDropdown();
      }
    }, 220);
  }

  if (input) {
    input.addEventListener('input', function () {
      selected = null;
      if (pathInput) pathInput.value = '';
      if (cityInput) cityInput.value = '';
      if (regionInput) regionInput.value = '';
      if (countryInput) countryInput.value = '';
      if (latInput) latInput.value = '';
      if (lngInput) lngInput.value = '';
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
      selected = results[index];
      setSelectionContext(selected);
      submitToCategoryPage();
    });
  }

  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const query = (input?.value || '').trim();
      if (!query) return;

      const localMatch = localSuggestions(query).find((item) => normalizeText(item.title || item.text) === normalizeText(query));
      if (localMatch) {
        selected = localMatch;
        setSelectionContext(localMatch);
        submitToCategoryPage();
        return;
      }

      if (!token) {
        submitToCategoryPage();
        return;
      }

      (async () => {
        try {
          const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json`);
          url.searchParams.set('access_token', token);
          url.searchParams.set('autocomplete', 'true');
          url.searchParams.set('limit', '1');
          url.searchParams.set('types', 'place,postcode,locality,region,district,country');
          url.searchParams.set('country', 'gb');
          const res = await fetch(url.toString());
          if (res.ok) {
            const data = await res.json();
            const place = Array.isArray(data.features) && data.features.length ? data.features[0] : null;
            if (place) {
              selected = place;
              setSelectionContext(place);
            }
          }
        } catch (error) {
          console.warn('[seo-money] mapbox geocode failed', error);
        }
        submitToCategoryPage();
      })();
    });
  }

  void resolveBrowserLocationAndSubmit();

  document.querySelectorAll('[data-scroll-target]').forEach((button) => {
    button.addEventListener('click', function () {
      const targetId = button.getAttribute('data-scroll-target');
      const target = targetId ? document.querySelector(`[data-money-section="${targetId}"]`) : null;
      if (target && target.scrollIntoView) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  document.addEventListener('click', function (event) {
    if (!dropdown || !form) return;
    if (!form.contains(event.target)) {
      hideDropdown();
      return;
    }
    if (!dropdown.contains(event.target) && event.target !== input) {
      hideDropdown();
    }
  });
})();
</script>
@endpush
