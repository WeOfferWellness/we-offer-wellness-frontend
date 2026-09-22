@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? 'We Offer Wellness™' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  @php
    $pageCanonical = $seo['canonical'] ?? url()->current();
    $slug = $slug ?? request()->route('slug');
    $landingTitle = trim((string) ($landing['title'] ?? ''));
    $landingCategory = trim((string) \Illuminate\Support\Str::headline((string) $slug));
    $landingSectionLabel = trim((string) \Illuminate\Support\Str::headline((string) ($type ?? 'therapies')));
    $landingItems = collect($products ?? [])
        ->values()
        ->take(24)
        ->map(function ($product, $index) {
          return [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => data_get($product, 'url', ''),
            'name' => data_get($product, 'title', ''),
          ];
        })
        ->filter(fn (array $item) => !empty($item['url']))
        ->values()
        ->all();
    $itemListLd = [
      '@context' => 'https://schema.org',
      '@graph' => [
        [
          '@type' => 'CollectionPage',
          '@id' => $pageCanonical . '#webpage',
          'url' => $pageCanonical,
          'name' => $landingTitle !== '' ? $landingTitle : $landingSectionLabel,
          'mainEntity' => ['@id' => $pageCanonical . '#itemlist'],
          'isPartOf' => ['@id' => url('/') . '#website'],
        ],
        [
          '@type' => 'ItemList',
          '@id' => $pageCanonical . '#itemlist',
          'itemListElement' => $landingItems,
        ],
      ],
    ];
  $landingCrumbs = [
      ['label' => 'Home', 'url' => url('/')],
      ['label' => $landingSectionLabel !== '' ? $landingSectionLabel : 'Therapies', 'url' => url('/' . ($type ?? 'therapies'))],
  ];
    if ($landingCategory !== '') {
      $landingCrumbs[] = ['label' => $landingCategory, 'url' => url('/' . $slug)];
    }
    if ($landingTitle !== '' && strcasecmp($landingTitle, $landingCategory) !== 0) {
      $landingCrumbs[] = ['label' => $landingTitle];
    } elseif ($landingCategory === '' && $landingTitle !== '') {
      $landingCrumbs[] = ['label' => $landingTitle];
    }
  @endphp
  <script type="application/ld+json">{!! json_encode($itemListLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}</script>
  <style>
    .landing-wow{
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
      --radius:18px;
      --shadow:0 18px 54px rgba(16,24,40,.07);
      padding:0 0 72px;
      background:none;
    }
    .landing-wow__modalities{
      padding:32px 0 12px;
    }
    .landing-wow__modalities-head{
      display:flex;
      align-items:end;
      justify-content:space-between;
      gap:18px;
      margin-bottom:18px;
    }
    .landing-wow__modalities-head h2{
      margin:0;
      color:var(--ink);
      font-family:"Playfair Display",Georgia,"Times New Roman",serif;
      font-size:clamp(30px,4vw,50px);
      font-weight:500;
      letter-spacing:-.055em;
      line-height:.98;
    }
    .landing-wow__modalities-head p{
      max-width:66ch;
      margin:9px 0 0;
      color:var(--muted);
      line-height:1.55;
    }
    .landing-wow__modality-grid{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:12px;
    }
    .landing-wow__modality-card{
      display:block;
      min-height:142px;
      padding:18px;
      border:1px solid var(--line);
      border-radius:18px;
      background:#fff;
      color:var(--ink);
      text-decoration:none;
      transition:border-color .16s ease,transform .16s ease,box-shadow .16s ease;
    }
    .landing-wow__modality-card:hover{border-color:#a9cfc1;box-shadow:0 12px 28px rgba(16,24,40,.07);transform:translateY(-2px)}
    .landing-wow__modality-card strong{display:block;font-size:17px;line-height:1.2}
    .landing-wow__modality-card span{display:block;margin-top:8px;color:var(--muted);font-size:13px;line-height:1.45}
    @media(max-width:991px){.landing-wow__modality-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:640px){.landing-wow__modalities{padding-top:22px}.landing-wow__modalities-head{display:block}.landing-wow__modality-grid{gap:8px}.landing-wow__modality-card{min-height:0;padding:15px}.landing-wow__modality-card strong{font-size:15px}}
    .landing-wow__hero{
      padding:68px 0 24px;
    }
    .landing-wow__hero-grid{
      display:grid;
      grid-template-columns:minmax(0, 1fr) minmax(320px, .95fr);
      gap:22px;
      align-items:stretch;
    }
    .landing-wow__hero-copy,
    .landing-wow__hero-panel{
      background:rgba(255,255,255,.98);
      border:1px solid var(--line);
      border-radius:24px;
      box-shadow:var(--shadow);
    }
    .landing-wow__hero-copy{
      padding:32px;
    }
    .landing-wow__kicker{
      margin:0 0 10px;
      color:#344054;
      font-size:13px;
      font-weight:300;
      letter-spacing:.16em;
      text-transform:uppercase;
    }
    .landing-wow__hero-copy h1,
    .landing-wow__hero-panel h3,
    .landing-wow__results h2{
      margin:0;
      color:var(--ink);
      font-family:"Playfair Display", Georgia, "Times New Roman", serif;
      font-weight:500;
      letter-spacing:-.055em;
    }
    .landing-wow__hero-copy h1{
      max-width:12ch;
      font-size:clamp(42px, 5.8vw, 78px);
      line-height:.94;
    }
    .landing-wow__hero-copy p,
    .landing-wow__hero-panel p,
    .landing-wow__results p{
      color:var(--muted);
      line-height:1.6;
    }
    .landing-wow__hero-copy p{
      max-width:68ch;
      margin:16px 0 0;
      font-size:17px;
    }
    .landing-wow__points{
      display:grid;
      gap:10px;
      margin-top:24px;
    }
    .landing-wow__point{
      display:flex;
      gap:10px;
      align-items:flex-start;
      color:#344054;
      font-size:14px;
      line-height:1.45;
    }
    .landing-wow__point::before{
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
    .landing-wow__actions{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:26px;
    }
    .landing-wow__hero-panel{
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      gap:18px;
      padding:26px;
      background:linear-gradient(180deg, rgba(232,245,241,.72), rgba(255,255,255,.96));
    }
    .landing-wow__hero-panel h3{
      font-size:clamp(34px, 4vw, 54px);
      line-height:.96;
    }
    .landing-wow__hero-panel p{
      margin:14px 0 0;
      font-size:15px;
      line-height:1.55;
    }
    .landing-wow__panel-list{
      display:grid;
      grid-auto-flow:column;
      grid-auto-columns:calc((100% - 24px) / 3);
      gap:12px;
      margin-top:18px;
      overflow-x:auto;
      overflow-y:hidden;
      padding-bottom:6px;
      scroll-snap-type:x proximity;
      -webkit-overflow-scrolling:touch;
    }
    .landing-wow__panel-item{
      min-height:100%;
      padding:14px 15px;
      border-radius:16px;
      background:#fff;
      border:1px solid var(--line-soft);
      box-shadow:0 10px 26px rgba(16,24,40,.04);
      text-decoration:none;
      scroll-snap-align:start;
    }
    .landing-wow__panel-item strong{
      display:block;
      color:var(--ink);
      font-size:14px;
      line-height:1.35;
    }
    .landing-wow__panel-item span{
      display:block;
      margin-top:4px;
      color:var(--muted);
      font-size:13px;
      line-height:1.4;
    }
    .landing-wow__results{
      padding:26px;
      margin-top:0;
      background:transparent;
      border:0;
      border-radius:0;
      box-shadow:none;
    }
    .landing-wow__results-head{
      display:flex;
      flex-wrap:wrap;
      gap:18px;
      align-items:end;
      justify-content:space-between;
      margin-bottom:18px;
    }
    .landing-wow__results h2{
      font-size:clamp(34px, 4.4vw, 58px);
      line-height:.96;
    }
    .landing-wow__results p{
      max-width:72ch;
      margin:12px 0 0;
      font-size:12.75px;
    }
    .landing-wow__filters{
      padding:18px;
      border:1px solid var(--line-soft);
      border-radius:18px;
      background:linear-gradient(180deg, rgba(248,250,252,.95), rgba(255,255,255,.98));
      margin-bottom:22px;
    }
    .landing-wow__filters .form-label{
      font-weight:600;
      color:var(--ink);
    }
    .landing-wow__filters .form-control{
      border-radius:14px;
      border-color:#d8dee5;
      min-height:44px;
      box-shadow:none;
    }
    .landing-wow__filters .btn{
      min-height:44px;
      border-radius:14px;
    }
    .landing-wow__grid{
      display:grid;
      grid-template-columns:repeat(4, minmax(0,1fr));
      gap:24px;
    }
    .landing-wow__empty{
      padding:18px;
      border:1px dashed var(--line);
      border-radius:16px;
      color:var(--muted);
      background:#fff;
    }
    @media (max-width: 991px){
      .landing-wow__hero-grid,
      .landing-wow__grid{
        grid-template-columns:repeat(2, minmax(0,1fr));
      }
      .landing-wow__panel-list{
        grid-auto-columns:calc((100% - 12px) / 2);
      }
    }
    @media (max-width: 720px){
      .landing-wow__grid{
        grid-template-columns:1fr;
      }
      .landing-wow__panel-list{
        grid-auto-columns:88%;
      }
    }
    @media (max-width: 640px){
      .landing-wow__hero{
        padding-top:38px;
      }
      .landing-wow__hero-grid{
        grid-template-columns:1fr;
      }
      .landing-wow__hero-copy,
      .landing-wow__hero-panel{
        padding:20px;
        border-radius:20px;
      }
      .landing-wow__panel-list{
        display:grid;
        grid-template-columns:1fr;
        grid-auto-flow:row;
        grid-auto-columns:initial;
        overflow:visible;
        padding-bottom:0;
      }
      .landing-wow__results-head{
        display:block;
      }
      .landing-wow__filters{
        padding:14px;
      }
    }
  </style>
@endpush

@section('content')
@php
  $items = $products ?? collect();
  $landing = $landing ?? [];
  $filters = is_array($filters ?? null) ? $filters : request()->query();
  $sortValue = (string) ($filters['sort'] ?? '');
  $formatValue = (string) ($filters['format'] ?? '');
  $locationValue = trim((string) ($filters['location'] ?? ''));
  $sortLabel = match ($sortValue) {
    'price_asc' => 'Price: Low → High',
    'price_desc' => 'Price: High → Low',
    'rating_desc' => 'Top rated',
    default => 'Recommended',
  };
  $formatLabel = match ($formatValue) {
    'online' => 'Online',
    'in_person' => 'Near me',
    default => 'All',
  };
  $landingFilterSegments = [
    [
      'key' => 'sort',
      'label' => 'Sort',
      'value' => $sortLabel,
      'placeholder' => 'Recommended',
      'panelTitle' => 'Sort results',
      'panelSubtitle' => 'Choose how results are ordered.',
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
    [
      'key' => 'format',
      'label' => 'Format',
      'value' => $formatLabel,
      'placeholder' => 'All',
      'panelTitle' => 'Choose format',
      'panelSubtitle' => 'Online or in-person sessions.',
      'panelWidth' => 430,
      'options' => [
        [
          'label' => 'All',
          'value' => '',
          'subtitle' => 'Any format',
          'selected' => $formatValue === '',
        ],
        [
          'label' => 'Online',
          'value' => 'online',
          'subtitle' => 'Join from anywhere',
          'selected' => $formatValue === 'online',
        ],
        [
          'label' => 'Near me',
          'value' => 'in_person',
          'subtitle' => 'Physical sessions near you',
          'selected' => $formatValue === 'in_person',
        ],
      ],
    ],
    [
      'key' => 'location',
      'label' => 'Location',
      'value' => $locationValue !== '' ? $locationValue : 'Anywhere',
      'placeholder' => 'Anywhere',
      'panelTitle' => 'Filter by location',
      'panelSubtitle' => 'Search by city, county, or area.',
      'panelWidth' => 480,
      'kind' => 'input',
      'param' => 'location',
      'inputLabel' => 'Location',
      'inputValue' => $locationValue,
      'inputPlaceholder' => 'e.g. London, Kent',
      'buttonLabel' => 'Update location',
    ],
  ];
  $landingFilterChips = array_values(array_filter([
    $sortValue !== '' ? ['param' => 'sort', 'label' => 'Sort', 'value' => $sortLabel] : null,
    $formatValue !== '' ? ['param' => 'format', 'label' => 'Format', 'value' => $formatLabel] : null,
    $locationValue !== '' ? ['param' => 'location', 'label' => 'Location', 'value' => $locationValue] : null,
  ]));
@endphp

@include('partials.breadcrumbs', [
  'crumbs' => $landingCrumbs ?? [],
  'schemaUrl' => $pageCanonical ?? url()->current(),
  'chips' => array_filter([
    $landing['kicker'] ?? null,
    isset($items) ? $items->count() . ' live listings' : null,
  ]),
])

<div class="landing-wow">
@include('partials.landing-hero', [
    'heroEyebrow' => $landing['kicker'] ?? 'Explore',
    'heroTitle' => $landing['title'] ?? 'Wellness',
    'heroIntro' => $landing['intro'] ?? ($seo['description'] ?? ''),
    'heroActions' => array_values(array_filter([
      !empty($landing['primary_cta']) ? ['label' => $landing['primary_cta']['label'], 'href' => $landing['primary_cta']['href']] : null,
      !empty($landing['secondary_cta']) ? ['label' => $landing['secondary_cta']['label'], 'href' => $landing['secondary_cta']['href'], 'style' => 'outline'] : null,
    ])),
    'heroAsideTitle' => null,
    'heroAsideText' => '',
  ])

  @include('home.sections.discover_category', [
    'discoveryCategories' => $discoveryCategories ?? [],
    'browseUrl' => url('/'.($type ?? 'therapies')),
  ])

  <div class="container-page">
    @include('partials.guide_panel', [
      'guidePanelModality' => request()->route('modality'),
      'guidePanelFormat' => $type ?? null,
    ])
  </div>

  <section class="landing-wow__results">
    <div class="container-page">
      <style>
        .landing-results-layout{display:grid;grid-template-columns:250px minmax(0,1fr);gap:25px;align-items:start}
        .landing-results-filters .wow-sr-v5-desktop{padding:0}
        .landing-results-filters .wow-sr-v5-container{width:100%}
        .landing-results-filters .wow-sr-v5-header{display:none}
        .landing-results-filters .wow-sr-v5-layout{display:block}
        .landing-results-filters .wow-sr-v5-sidebar{position:sticky;top:140px}
        .landing-results-content{min-width:0}
        @media(max-width:1040px){.landing-results-layout{display:block}.landing-results-filters{display:none}}
      </style>
      <div class="landing-results-layout">
        <div class="landing-results-filters">
          @include('search.partials.desktop', [
            'products' => $items,
            'desktopResultsCount' => $items->count(),
            'desktopFullNavigation' => true,
            'showMap' => false,
            'filterOnly' => true,
            'searchMapData' => [],
            'searchRecommendationsHtml' => '',
            'searchAsyncBoot' => false,
            'searchUrl' => url('/' . $slug . '/' . ($type ?? 'therapies') . '/'),
          ])
        </div>
        <div class="landing-results-content">
      @if(($type ?? '') === 'therapies')
        <div class="landing-wow__results-head">
          <div>
            <div class="landing-wow__kicker">Featured offerings</div>
            <h2>Actual sessions you can book now</h2>
            <p>Live therapy offerings from trusted practitioners, ready to explore and book.</p>
          </div>
          <a href="/search?type=therapies" class="btn-wow btn-wow--outline btn-sm btn-arrow">
            Browse all
          </a>
        </div>
        @if(collect($featuredOfferings ?? [])->isNotEmpty())
          <div class="landing-wow__grid" style="margin-bottom:32px;">
            @foreach(collect($featuredOfferings)->take(8) as $product)
              @include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null])
            @endforeach
          </div>
        @endif
      @endif

      <div class="landing-wow__results-head">
        <div>
          <div class="landing-wow__kicker">Featured results</div>
          <h2>{{ $landing['title'] ?? 'Listings' }}</h2>
          <p>{{ $seo['description'] ?? ($landing['intro'] ?? '') }}</p>
        </div>
      </div>

      @include('search.partials.mobile', [
        'products' => $items,
        'mobileResultsCount' => $items->count(),
        'mobileFullNavigation' => true,
        'mobileShowMap' => false,
        'filterOnly' => true,
        'searchMapData' => [],
        'searchRecommendationsHtml' => '',
        'searchAsyncBoot' => false,
        'searchUrl' => url('/' . $slug . '/' . ($type ?? 'therapies') . '/'),
      ])
      @if($items->count())
        <div id="landing-products" class="landing-wow__grid">
          @foreach($items as $product)
            @include('partials.product_card_v4_1', [
              'product' => $product,
              'preferredLocation' => $filters['location'] ?? null,
            ])
          @endforeach
        </div>

        @php
          $meta = $results['meta'] ?? [];
          $current = (int)($meta['current_page'] ?? request()->query('page', 1));
          $last = (int)($meta['last_page'] ?? ($meta['total_pages'] ?? 1));
          $q = request()->query();
        @endphp

        @if($last > 1)
          <div class="flex items-center justify-center gap-3 mt-5">
            @if($current > 1)
              <a class="btn btn-light" href="{{ request()->url() . '?' . http_build_query(array_merge($q, ['page' => $current - 1])) }}">← Prev</a>
            @endif
            <span class="text-muted">Page {{ $current }} of {{ $last }}</span>
            @if($current < $last)
              <a class="btn btn-light" href="{{ request()->url() . '?' . http_build_query(array_merge($q, ['page' => $current + 1])) }}">Next →</a>
            @endif
          </div>
        @endif
      @else
        <div class="landing-wow__empty">
          No results yet. Try changing format, location or resetting filters.
        </div>
      @endif
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
