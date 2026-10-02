@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? 'Wellness collection | We Offer Wellness®' }}</title>
  @if(!empty($seo['description']))
    <meta name="description" content="{{ $seo['description'] }}">
  @endif
  <meta name="robots" content="{{ $seo['robots'] ?? 'noindex,follow' }}">
  <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
  @php
    $canonical = $seo['canonical'] ?? url()->current();
    $collectionName = trim((string) data_get($collection, 'name', 'Wellness collection'));
    $collectionHeading = trim((string) data_get($collection, 'h1', $collectionName)) ?: $collectionName;
    $collectionDescription = trim((string) data_get($collection, 'description', data_get($seo, 'description', '')));
    $total = (int) data_get($meta, 'total', $items->count());
    $itemList = $items
      ->values()
      ->map(function ($item, $index) use ($canonical) {
        $url = trim((string) data_get($item, 'url', ''));
        if ($url === '') {
          try {
            $url = app(\App\Services\SeoStructureService::class)->canonicalProductUrl($item);
          } catch (\Throwable) {
            $url = '';
          }
        }

        return [
          '@type' => 'ListItem',
          'position' => $index + 1,
          'url' => $url !== '' ? url($url) : $canonical,
          'name' => (string) data_get($item, 'title', ''),
        ];
      })
      ->values()
      ->all();
    $collectionSchema = [
      '@context' => 'https://schema.org',
      '@graph' => [
        [
          '@type' => 'CollectionPage',
          '@id' => $canonical.'#webpage',
          'url' => $canonical,
          'name' => $collectionHeading,
          'description' => $collectionDescription,
          'mainEntity' => ['@id' => $canonical.'#itemlist'],
          'isPartOf' => ['@id' => url('/').'#website'],
        ],
        [
          '@type' => 'ItemList',
          '@id' => $canonical.'#itemlist',
          'numberOfItems' => $total,
          'itemListElement' => $itemList,
        ],
      ],
    ];
  @endphp
  <script type="application/ld+json">{!! json_encode($collectionSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
@php
  $currentPage = max(1, (int) data_get($meta, 'current_page', 1));
  $lastPage = max(1, (int) data_get($meta, 'last_page', 1));
  $pageQuery = request()->query();
@endphp

@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => $collectionName],
  ],
  'schemaUrl' => $canonical,
])

@include('partials.landing-hero', [
  'heroEyebrow' => 'Collection',
  'heroTitle' => $collectionHeading,
  'heroIntro' => $collectionDescription,
  'heroAsideLabel' => 'Curated discovery',
  'heroAsideTitle' => 'Find something that fits',
  'heroAsideText' => 'Explore live wellness offerings together in one useful place.',
])

@include('partials.hero-meta', [
  'items' => array_values(array_filter([
    ['label' => number_format($total).' live '.($total === 1 ? 'listing' : 'listings'), 'strong' => true],
    $items->contains(fn ($item) => in_array('online', array_map('strtolower', (array) data_get($item, 'channels', [])), true))
      ? ['label' => 'Online options available']
      : null,
    $items->contains(fn ($item) => (int) data_get($item, 'physical_location_count', 0) > 0)
      ? ['label' => 'In-person options available']
      : null,
  ])),
])

<section class="wow-collection-results" id="collection-offerings">
  <div class="container-page">
    <div class="wow-collection-results__head">
      <div>
        <p class="wow-collection-results__eyebrow">Explore the collection</p>
        <h2>{{ $collectionName }}</h2>
      </div>
    </div>

    @if($items->isNotEmpty())
      <div class="wow410-grid">
        @foreach($items as $product)
          @include('partials.product_card_v4_1', [
            'product' => $product,
            'cardVersion' => 'v4.10',
          ])
        @endforeach
      </div>

      @if($lastPage > 1)
        <nav class="wow-collection-pagination" aria-label="Collection pages">
          <div>
            @if($currentPage > 1)
              @include('partials.wow-button', [
                'href' => request()->url().'?'.http_build_query(array_merge($pageQuery, ['page' => $currentPage - 1])),
                'label' => 'Previous',
                'variant' => 'outline',
                'size' => 'md',
              ])
            @endif
          </div>

          <span>Page {{ $currentPage }} of {{ $lastPage }}</span>

          <div>
            @if($currentPage < $lastPage)
              @include('partials.wow-button', [
                'href' => request()->url().'?'.http_build_query(array_merge($pageQuery, ['page' => $currentPage + 1])),
                'label' => 'Next',
                'variant' => 'outline',
                'size' => 'md',
                'arrow' => true,
              ])
            @endif
          </div>
        </nav>
      @endif
    @else
      <div class="wow-collection-empty">
        <h2>No live offerings in this collection yet</h2>
        <p>This collection will update automatically as matching offerings become available.</p>
        @include('partials.wow-button', [
          'href' => url('/search'),
          'label' => 'Browse all wellness',
          'variant' => 'outline',
          'size' => 'md',
          'arrow' => true,
        ])
      </div>
    @endif
  </div>
</section>

<style>
  .wow-collection-results{padding:40px 0 72px}
  .wow-collection-results__head{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:24px}
  .wow-collection-results__eyebrow{margin:0 0 8px;color:#006b57;font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}
  .wow-collection-results__head h2,.wow-collection-empty h2{margin:0;color:#092c25;font-family:"Playfair Display",Georgia,serif;font-size:clamp(30px,4vw,48px);font-weight:500;line-height:1.02}
  .wow-collection-pagination{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:16px;margin-top:32px;padding-top:20px;border-top:1px solid #dfe5e2}
  .wow-collection-pagination>div:last-child{text-align:right}
  .wow-collection-pagination>span{color:#67736f;font-size:13px}
  .wow-collection-empty{padding:40px 0;border-top:1px solid #dfe5e2}
  .wow-collection-empty p{max-width:620px;margin:14px 0 22px;color:#67736f;line-height:1.6}
  @media(max-width:575.98px){.wow-collection-results{padding:28px 0 56px}.wow-collection-pagination{grid-template-columns:1fr 1fr}.wow-collection-pagination>span{grid-column:1/-1;grid-row:1;text-align:center}.wow-collection-pagination>div{grid-row:2}}
</style>
@endsection
