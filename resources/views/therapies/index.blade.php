{{-- resources/views/therapies/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
  .therapies-shell{
    position:relative;
    overflow:hidden;
    padding: 28px 0 72px;
    background:transparent;
  }
  .therapies-hero{
    display:grid;
    grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);
    gap:24px;
    align-items:stretch;
    padding: 28px;
    border:1px solid rgba(15,23,42,.10);
    border-radius:24px;
    background: rgba(255,255,255,.82);
    box-shadow: 0 20px 60px rgba(15,23,42,.08);
    backdrop-filter: blur(8px);
  }
  .therapies-kicker{
    margin:0 0 10px;
    color:#48685e;
    font-size:12px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
  }
  .therapies-hero h1{
    margin:0;
    color:var(--wow-ink,#101828);
    font-family: "Playfair Display", Georgia, "Times New Roman", serif;
    font-size: clamp(40px, 5vw, 72px);
    line-height:.96;
    letter-spacing:-.05em;
  }
  .therapies-hero p{
    margin:16px 0 0;
    max-width: 62ch;
    color:#596275;
    font-size:16px;
    line-height:1.65;
  }
  .therapies-actions{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:24px;
  }
  .therapies-pills{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:20px;
  }
  .therapies-pill{
    display:inline-flex;
    align-items:center;
    min-height:32px;
    padding:0 10px;
    border-radius:999px;
    background:#fff;
    border:1px solid rgba(15,23,42,.10);
    color:#344054;
    font-size:12px;
    font-weight:700;
    text-decoration:none;
  }
  .therapies-spotlight{
    display:grid;
    gap:12px;
    align-content:start;
  }
  .therapy-spot{
    padding:18px;
    border:1px solid rgba(15,23,42,.10);
    border-radius:18px;
    background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(247,249,248,.96));
    box-shadow: 0 12px 30px rgba(15,23,42,.05);
    text-decoration:none;
    color:inherit;
  }
  .therapy-spot small{
    display:block;
    color:#667085;
    font-size:12px;
    font-weight:700;
    letter-spacing:.12em;
    text-transform:uppercase;
    margin-bottom:10px;
  }
  .therapy-spot h3{
    margin:0;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
    font-size:28px;
    line-height:1;
    letter-spacing:-.04em;
    color:#101828;
  }
  .therapy-spot p{
    margin:10px 0 0;
    color:#596275;
    font-size:14px;
    line-height:1.55;
  }
  .therapies-stats{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:12px;
    margin-top:18px;
  }
  .therapies-stat{
    padding:14px 16px;
    border-radius:16px;
    background:#fff;
    border:1px solid rgba(15,23,42,.08);
    box-shadow:0 12px 28px rgba(15,23,42,.04);
  }
  .therapies-stat strong{
    display:block;
    font-size:20px;
    line-height:1;
    color:#101828;
  }
  .therapies-stat span{
    display:block;
    margin-top:6px;
    color:#667085;
    font-size:12px;
    line-height:1.35;
  }
  .therapies-section{
    margin-top:28px;
  }
  .therapies-section__head{
    display:flex;
    align-items:end;
    justify-content:space-between;
    gap:16px;
    margin-bottom:16px;
  }
  .therapies-section__head h2{
    margin:0;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
    font-size: clamp(28px, 3vw, 44px);
    line-height:1;
    letter-spacing:-.045em;
    color:#101828;
  }
  .therapies-section__head p{
    margin:6px 0 0;
    color:#667085;
    max-width:70ch;
  }
  .therapies-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
  }
  .therapies-card{
    display:flex;
    flex-direction:column;
    min-height:220px;
    padding:18px;
    border-radius:20px;
    border:1px solid rgba(15,23,42,.10);
    background:#fff;
    box-shadow:0 16px 40px rgba(15,23,42,.05);
    text-decoration:none;
    color:inherit;
    transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease;
  }
  .therapies-card:hover{
    transform: translateY(-2px);
    box-shadow:0 22px 60px rgba(15,23,42,.10);
    border-color: rgba(79,147,129,.22);
  }
  .therapies-card__badge{
    display:inline-flex;
    align-items:center;
    min-height:28px;
    width:fit-content;
    padding:0 10px;
    border-radius:999px;
    background:rgba(79,147,129,.12);
    color:#2d6d5f;
    font-size:12px;
    font-weight:800;
  }
  .therapies-card h3{
    margin:12px 0 0;
    font-size:24px;
    line-height:1.08;
    letter-spacing:-.04em;
    color:#101828;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
  }
  .therapies-card p{
    margin:10px 0 0;
    color:#596275;
    font-size:14px;
    line-height:1.6;
    flex:1 1 auto;
  }
  .therapies-card__footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-top:18px;
    color:#344054;
    font-weight:700;
    font-size:14px;
  }
  .therapies-card__footer .arrow{
    color:#4f9381;
    font-size:18px;
    line-height:1;
  }
  .therapies-finish{
    margin-top:28px;
    padding:22px 24px;
    border-radius:20px;
    background: linear-gradient(180deg, #101828, #0b1220);
    color:#fff;
    box-shadow:0 24px 70px rgba(16,24,40,.16);
  }
  .therapies-finish h2{
    margin:0;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
    font-size: clamp(28px, 3vw, 42px);
    line-height:1;
    letter-spacing:-.04em;
    color:#fff !important;
  }
  .therapies-finish p{
    margin:12px 0 0;
    max-width: 72ch;
    color: #fff !important;
    line-height:1.6;
  }
  .therapies-finish .btn-wow--ghost,
  .therapies-finish .btn-wow--ghost .btn-label{
    color:#fff !important;
  }
  .therapies-finish .btn-wow--ghost{
    border-color:rgba(255,255,255,.22);
    background:rgba(255,255,255,.06);
  }
  .therapies-finish .therapies-actions{
    margin-top:18px;
  }
  @media (max-width: 991.98px){
    .therapies-hero{ grid-template-columns:1fr; }
    .therapies-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    .therapies-stats{ grid-template-columns:1fr; }
  }
  @media (max-width: 767.98px){
    .therapies-shell{ padding-top:16px; }
    .therapies-grid{ grid-template-columns:1fr; }
    .therapies-section__head{ align-items:start; flex-direction:column; }
  }
</style>
@endpush

@push('head')
  <title>{{ $seo['title'] ?? 'Therapies | We Offer Wellness™' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  @php
    $schemaUrl = url('/therapies');
    $schemaTitle = 'Therapies';
    $schemaDescription = trim((string) ($seo['description'] ?? 'Explore holistic therapies and modalities, from sound healing and breathwork to massage and Reiki.'));
    $schemaTherapies = collect($therapies ?? [])->values();
    $schemaFeatured = collect($featuredOfferings ?? [])->values();
    $seoService = app(\App\Services\SeoStructureService::class);
    $cleanText = static function ($value): string {
      $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($value ?? ''))));
      return $text;
    };

    $schemaModalityItems = $schemaTherapies
      ->map(function (array $therapy, int $index) use ($cleanText) {
        $url = url('/therapies/' . ($therapy['slug'] ?? ''));
        $title = trim((string) ($therapy['title'] ?? 'Therapy'));
        $description = trim((string) ($therapy['seo_description'] ?? ''));

        $schemaItem = array_filter([
          '@type' => 'DefinedTerm',
          '@id' => $url . '#modality',
          'name' => $title,
          'url' => $url,
          'termCode' => (string) ($therapy['slug'] ?? ''),
          'description' => $cleanText($description) !== '' ? $cleanText($description) : null,
          'inDefinedTermSet' => 'Wellness Modalities',
        ], static fn ($value) => $value !== null && $value !== '');

        return [
          '@type' => 'ListItem',
          'position' => $index + 1,
          'url' => $url,
          'item' => $schemaItem,
        ];
      })
      ->values()
      ->all();

    $schemaFeaturedItems = $schemaFeatured
      ->map(function ($product, $index) use ($seoService, $cleanText) {
        $url = $seoService->canonicalProductUrl($product);
        $title = trim((string) data_get($product, 'title', ''));
        $image = method_exists($product, 'getFirstImageUrl') ? trim((string) $product->getFirstImageUrl()) : '';
        $hasImage = method_exists($product, 'hasDisplayableImage')
          ? (bool) $product->hasDisplayableImage()
          : ($image !== '' && ! str_contains($image, 'no-product-image.jpg'));
        $benefitText = data_get($product, 'benefit', data_get($product, 'summary', null));
        $providerName = trim((string) (
          data_get($product, 'vendor.vendor_name')
          ?? data_get($product, 'vendor_name')
          ?? ''
        ));
        $price = data_get($product, 'variants_min_price', data_get($product, 'price', null));

        $schemaItem = array_filter([
          '@type' => 'Service',
          '@id' => $url . '#service',
          'name' => $title !== '' ? $title : 'Untitled',
          'url' => $url,
          'serviceType' => 'Therapy Offering',
          'image' => $hasImage && $image !== '' ? [$image] : null,
          'description' => $cleanText($benefitText) !== '' ? $cleanText($benefitText) : null,
          'provider' => $providerName !== '' ? [
            '@type' => 'Organization',
            'name' => $providerName,
          ] : null,
          'offers' => is_numeric($price) && (float) $price > 0 ? [
            '@type' => 'Offer',
            'url' => $url,
            'price' => number_format((float) $price, 2, '.', ''),
            'priceCurrency' => 'GBP',
            'availability' => 'https://schema.org/InStock',
          ] : null,
        ], static fn ($value) => $value !== null && $value !== '');

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
      '@graph' => [
        [
          '@type' => 'Organization',
          '@id' => url('/') . '#organization',
          'name' => 'We Offer Wellness®',
          'url' => url('/'),
          'logo' => [
            '@type' => 'ImageObject',
            'url' => 'https://www.weofferwellness.co.uk/cdn/shop/files/logo-google-icon_05080e3a-98e5-42cd-b479-3b443028308c.png',
          ],
          'sameAs' => [
            'https://www.instagram.com/weofferwellness',
            'https://www.tiktok.com/@weofferwellness',
            'https://www.linkedin.com/company/weofferwellness',
            'https://www.facebook.com/WeOfferWellness',
          ],
        ],
        [
          '@type' => 'WebSite',
          '@id' => url('/') . '#website',
          'url' => url('/'),
          'name' => 'We Offer Wellness®',
          'publisher' => [
            '@id' => url('/') . '#organization',
          ],
        ],
        [
          '@type' => 'CollectionPage',
          '@id' => $schemaUrl . '#webpage',
          'url' => $schemaUrl,
          'name' => $schemaTitle . ' | We Offer Wellness™',
          'description' => $schemaDescription,
          'isPartOf' => [
            '@id' => url('/') . '#website',
          ],
          'publisher' => [
            '@id' => url('/') . '#organization',
          ],
          'about' => [
            '@id' => $schemaUrl . '#format',
          ],
          'mainEntity' => [
            '@id' => $schemaUrl . '#modality-list',
          ],
          'hasPart' => [
            '@id' => $schemaUrl . '#featured-offerings',
          ],
          'breadcrumb' => [
            '@id' => $schemaUrl . '#breadcrumb',
          ],
          'inLanguage' => 'en-GB',
        ],
        [
          '@type' => 'DefinedTerm',
          '@id' => $schemaUrl . '#format',
          'name' => $schemaTitle,
          'termCode' => 'therapies',
          'inDefinedTermSet' => 'Wellness Formats',
        ],
        [
          '@type' => 'ItemList',
          '@id' => $schemaUrl . '#modality-list',
          'name' => 'Therapy modalities',
          'numberOfItems' => $schemaTherapies->count(),
          'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
          'itemListElement' => $schemaModalityItems,
        ],
        [
          '@type' => 'ItemList',
          '@id' => $schemaUrl . '#featured-offerings',
          'name' => 'Featured therapy offerings',
          'numberOfItems' => $schemaFeatured->count(),
          'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
          'itemListElement' => $schemaFeaturedItems,
        ],
      ],
    ];
  @endphp
  @once
    <script type="application/ld+json">{!! json_encode($schemaJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
  @endonce
@endpush

@section('content')
@php
  $items = collect($therapies ?? []);
  $total = $items->count();
  $featured = $items->take(3);
  $categories = $items->pluck('title')->take(5)->values();
  $offerings = collect($featuredOfferings ?? []);
@endphp

@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Therapies'],
  ],
  'schemaUrl' => url('/therapies'),
  'chips' => array_filter([
    isset($offeringCount) ? number_format((int) $offeringCount) . ' live offerings' : null,
    isset($total) ? $total . ' therapies' : null,
  ]),
])

<section class="therapies-shell">
  <div class="container-page">
    <div class="therapies-hero">
      <div>
        <div class="therapies-kicker">Browse therapies</div>
        <h1>Find the therapy that fits how you want to feel.</h1>
        <p>
          Explore a curated collection of therapies across massage, Reiki, breathwork, sound healing and more. Use the search page when you want a broader mix of experiences, or dive into a therapy here when you already know the modality you want.
        </p>

        <div class="therapies-actions">
          <a href="/search?type=therapies" class="btn-wow btn-wow--cta btn-arrow" data-loader-init="1">
            <span class="btn-label">Search all therapies</span>
            <span class="btn-spinner" aria-hidden="true"><span class="spin"></span></span>
          </a>
          <a href="/search?mode=online&type=therapies" class="btn-wow btn-wow--ghost" data-loader-init="1">Browse online</a>
        </div>

        <div class="therapies-pills" aria-label="Popular therapy themes">
          @foreach($categories as $category)
            <a class="therapies-pill" href="{{ url('/search?what=' . urlencode($category)) }}">{{ $category }}</a>
          @endforeach
        </div>

        <div class="therapies-stats">
          <div class="therapies-stat">
            <strong>{{ number_format((int)($offeringCount ?? 0)) }}</strong>
            <span>Live offerings linked to these therapies</span>
          </div>
          <div class="therapies-stat">
            <strong>{{ $total }}</strong>
            <span>Therapy modalities to browse</span>
          </div>
          <div class="therapies-stat">
            <strong>Search</strong>
            <span>Use `/search` to refine by location, time, and format</span>
          </div>
        </div>
      </div>

      <div class="therapies-spotlight" aria-label="Featured therapies">
        @foreach($featured as $spot)
          <a class="therapy-spot" href="{{ route('therapies.show', ['slug' => $spot['slug']]) }}">
            <small>Featured</small>
            <h3>{{ $spot['title'] }}</h3>
            <p>{{ $spot['seo_description'] ?? 'Explore this modality and see live offerings matched to it.' }}</p>
          </a>
        @endforeach
      </div>
    </div>

    <div class="therapies-section">
      <div class="therapies-section__head">
        <div>
          <div class="kicker">All therapies</div>
          <h2>Browse the full modality library</h2>
          <p>Tap any therapy to see the related offerings, then refine by format, location, and price on the therapy page.</p>
        </div>
        <a href="/search?type=therapies" class="btn-wow btn-wow--outline btn-sm btn-arrow" data-loader-init="1">
          <span class="btn-label">Open search</span>
        </a>
      </div>

      <div class="therapies-grid">
        @foreach($items as $t)
          <a href="{{ route('therapies.show', ['slug' => $t['slug']]) }}" class="therapies-card">
            <span class="therapies-card__badge">Therapy</span>
            <h3>{{ $t['title'] }}</h3>
            <p>{{ $t['seo_description'] ?? 'Explore offerings for this therapy and see what is currently available.' }}</p>
            <div class="therapies-card__footer">
              <span>View offerings</span>
              <span class="arrow">→</span>
            </div>
          </a>
        @endforeach
      </div>

      <div class="therapies-section" style="margin-top:28px;">
        <div class="therapies-section__head">
          <div>
            <div class="kicker">Featured offerings</div>
            <h2>Actual sessions you can book now</h2>
            <p>These are live offerings matched to the therapies above and only shown when there is current availability to book.</p>
          </div>
          <a href="/search?type=therapies" class="btn-wow btn-wow--outline btn-sm btn-arrow" data-loader-init="1">
            <span class="btn-label">Browse all</span>
          </a>
        </div>

        @if($offerings->count())
          <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($offerings as $product)
              @include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null])
            @endforeach
          </div>
        @else
          <div class="card p-4" style="border-radius:18px;">
            <div class="text-muted">No live offerings with current availability were found for the current therapy collection yet.</div>
          </div>
        @endif
      </div>
    </div>

    <div class="therapies-finish">
      <div class="kicker" style="color:rgba(255,255,255,.7);">Need help deciding?</div>
      <h2>Use search if you want the best fit by what you need, where you are, and when you can book.</h2>
      <p>
        Search brings therapies, classes, workshops and retreats together. This page is for browsing the therapy modalities themselves, with a cleaner route into the right experiences.
      </p>
      <div class="therapies-actions">
        <a href="/search" class="btn-wow btn-wow--cta btn-arrow" data-loader-init="1">
          <span class="btn-label">Go to search</span>
          <span class="btn-spinner" aria-hidden="true"><span class="spin"></span></span>
        </a>
        <a href="/online" class="btn-wow btn-wow--ghost" data-loader-init="1">Explore online</a>
      </div>
    </div>
  </div>
</section>
@endsection
