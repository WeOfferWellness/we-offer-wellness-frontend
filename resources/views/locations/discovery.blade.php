@extends('layouts.app')

@php
  $discovery = is_array($discovery ?? null) ? $discovery : [];
  $locationLabel = trim((string) ($discovery['label'] ?? '')) ?: 'you';
  $nearby = collect($discovery['nearby'] ?? []);
  $newNearby = collect($discovery['new_nearby'] ?? []);
  $online = collect($discovery['online'] ?? []);
  $categories = collect($discovery['categories'] ?? []);
  $popularPlaces = collect($discovery['popular_places'] ?? []);
  $priceBands = collect($discovery['price_bands'] ?? []);
  $suggestions = collect(data_get($locationCatalog, 'suggestions', []))->take(80);
@endphp

@push('head')
  <title>{{ $seo['title'] ?? 'Wellness Near You | We Offer Wellness' }}</title>
  <meta name="description" content="{{ $seo['description'] ?? 'Discover wellness therapies, classes, events and practitioners near you.' }}">
  <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
  <style>
    .locations-discovery{color:#17202b;padding:28px 0 70px}
    .locations-discovery__container{width:min(100% - 32px,1280px);margin:0 auto}
    .locations-discovery__hero{padding:30px 0 34px;border-bottom:1px solid #e7ecef}
    .locations-discovery__search-wrap{width:min(100% - 32px,1280px);margin:-18px auto 0;position:relative;z-index:2}
    .locations-discovery__eyebrow{margin:0 0 8px;color:#4f9381;font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
    .locations-discovery h1,.locations-discovery h2{margin:0;font-family:var(--wow-serif,'Playfair Display',Georgia,serif);font-weight:500;letter-spacing:-.045em}
    .locations-discovery h1{max-width:700px;font-size:clamp(42px,6vw,76px);line-height:.95}
    .locations-discovery h2{font-size:clamp(30px,4vw,48px);line-height:1}
    .locations-discovery__intro{max-width:640px;margin:16px 0 24px;color:#596275;font-size:17px;line-height:1.55}
    .locations-discovery__search{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;gap:8px;max-width:900px}
    .locations-discovery__search label{display:block;color:#596275;font-size:12px;font-weight:700;margin:0 0 6px}
    .locations-discovery__search input{width:100%;height:48px;padding:0 13px;border:1px solid #ccd6dc;border-radius:4px;color:#17202b;background:#fff;font:inherit}
    .locations-discovery__search input:focus{outline:0;border-color:#4f9381;box-shadow:0 0 0 3px rgba(79,147,129,.15)}
    .locations-discovery__search button{align-self:end;height:48px;padding:0 22px;border:1px solid #4f9381;border-radius:4px;background:#4f9381;color:#fff;font-weight:800;cursor:pointer}
    .locations-discovery__search button:hover{background:#3e7e6d}
    .locations-discovery__section{padding-top:38px}
    .locations-discovery__section-head{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:16px}
    .locations-discovery__copy{margin:8px 0 0;color:#667085;font-size:15px;line-height:1.5}
    .locations-discovery__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .locations-discovery__grid .wow49-blade-card{min-width:0}
    .locations-discovery__empty{padding:22px;border:1px solid #e1e7eb;border-radius:4px;color:#596275}
    .locations-discovery__place-grid,.locations-discovery__category-grid,.locations-discovery__price-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .locations-discovery__place,.locations-discovery__category,.locations-discovery__price{display:block;padding:18px;border:1px solid #dfe7ea;border-radius:4px;background:#fff;color:inherit;text-decoration:none}
    .locations-discovery__place:hover,.locations-discovery__category:hover,.locations-discovery__price:hover{border-color:#4f9381;background:#f8fcfb}
    .locations-discovery__place strong,.locations-discovery__category strong,.locations-discovery__price strong{display:block;font-size:17px}
    .locations-discovery__place span,.locations-discovery__category span,.locations-discovery__price span{display:block;margin-top:5px;color:#667085;font-size:13px;line-height:1.4}
    .locations-discovery__directory{border-top:1px solid #e7ecef}
    .locations-discovery__directory details{border-bottom:1px solid #e7ecef}
    .locations-discovery__directory summary{padding:15px 0;cursor:pointer;font-weight:800;list-style:none}
    .locations-discovery__directory summary::-webkit-details-marker{display:none}
    .locations-discovery__directory ul{display:flex;flex-wrap:wrap;gap:8px 22px;margin:0;padding:0 0 18px;list-style:none}
    .locations-discovery__directory a{color:#4f9381;text-decoration:none}
    @media(max-width:900px){.locations-discovery__grid{grid-template-columns:repeat(2,minmax(0,1fr))}.locations-discovery__place-grid,.locations-discovery__category-grid,.locations-discovery__price-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:620px){.locations-discovery{padding-top:8px}.locations-discovery__container{width:min(100% - 24px,520px)}.locations-discovery__hero{padding:24px 0 26px}.locations-discovery__search-wrap{width:min(100% - 24px,520px);margin:-12px auto 0}.locations-discovery__search{grid-template-columns:1fr 1fr}.locations-discovery__search button{grid-column:1/-1}.locations-discovery__section-head{display:block}.locations-discovery__grid{gap:8px}.locations-discovery__place-grid,.locations-discovery__category-grid,.locations-discovery__price-grid{gap:8px}.locations-discovery__place,.locations-discovery__category,.locations-discovery__price{padding:14px}.locations-discovery__grid .wow49-blade-card__event-content{padding:12px!important}}
  </style>
@endpush

@section('content')
  @include('partials.breadcrumbs', ['crumbs' => [['label' => 'Home', 'url' => url('/')], ['label' => 'Locations']], 'schemaUrl' => url('/locations')])
  <main class="locations-discovery">
    <div class="locations-discovery__container">
      @include('partials.landing-hero', [
        'heroEyebrow' => 'Local wellness discovery',
        'heroTitle' => 'Find wellness near you',
        'heroIntro' => 'Discover therapies, classes, events and wellness experiences available near you.',
        'heroImage' => data_get($locationSearch ?? [], 'image_path', ''),
        'heroLocationLabel' => $locationLabel !== 'you' ? $locationLabel : '',
        'heroAsideTitle' => null,
        'heroAsideText' => '',
      ])
      <div class="locations-discovery__search-wrap">
        <x-home-searchbar-v4
          id-prefix="locations-discovery-search-v4"
          :search-url="url('/search')"
          initial-where="{{ $locationQuery ?? '' }}"
        />
      </div>

      <section class="locations-discovery__section" aria-labelledby="nearby-title">
        <div class="locations-discovery__section-head"><div><h2 id="nearby-title">Wellness near {{ $locationLabel }}</h2><p class="locations-discovery__copy">Live marketplace offerings, ranked for local relevance and useful quality signals.</p></div>@if($nearby->isNotEmpty())<a class="btn-wow btn-wow--outline btn-sm" href="{{ url('/search?where='.urlencode($locationLabel)) }}">View all</a>@endif</div>
        @if($nearby->isNotEmpty())<div class="locations-discovery__grid">@foreach($nearby->take(8) as $product)@include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => $locationLabel])@endforeach</div>@else<div class="locations-discovery__empty">We don't have anything listed in {{ $locationLabel }} yet. Try a nearby area or explore online options.</div>@endif
      </section>

      @if($popularPlaces->isNotEmpty())<section class="locations-discovery__section"><div class="locations-discovery__section-head"><div><h2>Popular places</h2><p class="locations-discovery__copy">Places with useful live supply and sustained marketplace interest.</p></div></div><div class="locations-discovery__place-grid">@foreach($popularPlaces as $place)<a class="locations-discovery__place" href="{{ url($place['path'] ?? '/locations') }}"><strong>{{ $place['title'] ?? $place['label'] ?? 'Location' }}</strong><span>{{ number_format((int) ($place['supply_count'] ?? 0)) }} wellness offerings</span></a>@endforeach</div></section>@endif

      @if($newNearby->isNotEmpty())<section class="locations-discovery__section"><div class="locations-discovery__section-head"><div><h2>New near {{ $locationLabel }}</h2><p class="locations-discovery__copy">Recently published offerings from the live marketplace.</p></div></div><div class="locations-discovery__grid">@foreach($newNearby->take(8) as $product)@include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => $locationLabel])@endforeach</div></section>@endif

      @if($categories->isNotEmpty())<section class="locations-discovery__section"><div class="locations-discovery__section-head"><div><h2>Explore wellness near {{ $locationLabel }}</h2><p class="locations-discovery__copy">Categories chosen from real local supply and wider wellness demand.</p></div></div><div class="locations-discovery__category-grid">@foreach($categories as $category)<a class="locations-discovery__category" href="{{ $category['url'] }}"><strong>{{ $category['name'] }}</strong><span>{{ $category['count'] }} local {{ $category['count'] === 1 ? 'offering' : 'offerings' }}</span></a>@endforeach</div></section>@endif

      @if($priceBands->isNotEmpty())<section class="locations-discovery__section"><div class="locations-discovery__section-head"><div><h2>Explore by price</h2><p class="locations-discovery__copy">Useful price points with live local availability.</p></div></div><div class="locations-discovery__price-grid">@foreach($priceBands as $band)<a class="locations-discovery__price" href="{{ url('/search?where='.urlencode($locationLabel).'&price_max='.($band['max'] ?? 500)) }}"><strong>{{ $band['label'] }}</strong><span>{{ $band['count'] }} local options</span></a>@endforeach</div></section>@endif

      @if($online->isNotEmpty())<section class="locations-discovery__section"><div class="locations-discovery__section-head"><div><h2>Also available online</h2><p class="locations-discovery__copy">Wellness experiences you can join from anywhere.</p></div><a class="btn-wow btn-wow--outline btn-sm" href="{{ url('/online') }}">Browse online</a></div><div class="locations-discovery__grid">@foreach($online->take(4) as $product)@include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null])@endforeach</div></section>@endif

      <section class="locations-discovery__section locations-discovery__directory" aria-labelledby="directory-title"><div class="locations-discovery__section-head"><div><h2 id="directory-title">Explore wellness across the UK</h2><p class="locations-discovery__copy">Browse the location directory when you already know where you want to go.</p></div></div>@foreach(collect(data_get($locationCatalog, 'countries', []))->filter(fn ($country) => !($country['online'] ?? false))->take(8) as $country)<details><summary>{{ $country['label'] ?? 'United Kingdom' }}</summary><ul>@foreach(collect($country['counties'] ?? [])->take(18) as $county)<li><a href="{{ url($county['path'] ?? '/locations') }}">{{ $county['label'] ?? 'County' }}</a></li>@endforeach</ul></details>@endforeach</section>
    </div>
  </main>
@endsection
