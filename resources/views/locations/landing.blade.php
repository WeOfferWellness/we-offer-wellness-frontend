@extends('layouts.app')

@php
  $scope = $scope ?? 'country';
  $location = (array) ($location ?? []);
  $discovery = (array) ($discovery ?? []);
  $directory = (array) ($directory ?? []);
  $label = trim((string) ($location['place'] ?? $location['label'] ?? 'United Kingdom'));
  $nearby = collect($discovery['nearby'] ?? []);
  $new = collect($discovery['new_nearby'] ?? []);
  $online = collect($discovery['online'] ?? []);
  $categories = collect($discovery['categories'] ?? []);
  $places = collect($discovery['popular_places'] ?? []);
  $prices = collect($discovery['price_bands'] ?? []);
  $isTown = $scope === 'town';
  $isCounty = $scope === 'county';
  $locationMediaBase = rtrim((string) config('services.location_media_url', 'https://studio.weofferwellness.co.uk'), '/');
  $locationImage = trim((string) ($location['image_path'] ?? ''));
  if ($locationImage !== '') {
    if (Str::startsWith(Str::lower($locationImage), ['http://', 'https://', '//'])) {
      $locationImage = preg_replace(
        '#^https?://(?:atease|testing\.studio|v3)\.weofferwellness\.co\.uk#i',
        $locationMediaBase,
        $locationImage
      ) ?: $locationImage;
    } else {
      $locationImage = $locationMediaBase.'/storage/'.ltrim($locationImage, '/');
    }
  }
@endphp

@push('head')
  <title>{{ $seo['title'] ?? 'Wellness in '.$label.' | We Offer Wellness' }}</title>
  <meta name="description" content="{{ $seo['description'] ?? 'Discover wellness experiences in '.$label.'.' }}">
  <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
  @if(!empty($seo['canonical']))<link rel="canonical" href="{{ $seo['canonical'] }}">@endif
  <style>
    .location-landing{color:#17202b;padding:22px 0 72px}
    .location-landing__container{width:min(100% - 32px,1280px);margin:auto}
    .location-landing__hero{padding:28px 0 32px;border-bottom:1px solid #e7ecef;background-color:#f7faf9;--location-hero-copy:#17202b;--location-hero-muted:#596275;--location-hero-accent:#2f7464;--location-hero-overlay:rgba(255,255,255,.78);@if($locationImage)background-image:linear-gradient(var(--location-hero-overlay),var(--location-hero-overlay)),url('{{ e($locationImage) }}');background-size:cover;background-position:center;@endif}
    .location-landing__hero.is-dark{--location-hero-copy:#fff;--location-hero-muted:rgba(255,255,255,.88);--location-hero-accent:#d7fff1;--location-hero-overlay:rgba(7,24,21,.62)}
    .location-landing__hero.is-light{--location-hero-copy:#17202b;--location-hero-muted:#465466;--location-hero-accent:#236957;--location-hero-overlay:rgba(255,255,255,.72)}
    .location-landing__hero{color:var(--location-hero-copy)}
    .location-landing__eyebrow{margin:0 0 8px;color:var(--location-hero-accent);font-size:12px;text-align:center;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
    .location-landing h1,.location-landing h2{margin:0;font-family:var(--wow-serif,'Playfair Display',Georgia,serif);font-weight:500;letter-spacing:-.045em}
    .location-landing h1{font-size:clamp(42px,6vw,74px);line-height:.95;text-align:center}
    .location-landing h2{font-size:clamp(30px,4vw,48px);line-height:1}
    .location-landing__intro{max-width:none;margin:15px auto 22px;color:var(--location-hero-muted);font-size:17px;line-height:1.55;text-align:center}
    .location-landing__search{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;gap:8px;max-width:900px}
    .location-landing__search label{display:block;margin:0 0 6px;color:#596275;font-size:12px;font-weight:800}
    .location-landing__search input{width:100%;height:46px;padding:0 12px;border:1px solid #ccd6dc;border-radius:4px;background:#fff;font:inherit}
    .location-landing__search button{align-self:end;height:46px;padding:0 22px;border:1px solid #4f9381;border-radius:4px;background:#4f9381;color:#fff;font-weight:800}
    .location-landing__section{padding-top:38px}
    .location-landing__head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:15px}
    .location-landing__copy{margin:8px 0 0;color:#667085;font-size:15px;line-height:1.5}
    .location-landing__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .location-landing__empty{padding:20px;border:1px solid #dfe7ea;border-radius:4px;color:#596275}
    .location-landing__tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .location-landing__tile{display:block;padding:17px;border:1px solid #dfe7ea;border-radius:4px;background:#fff;color:inherit;text-decoration:none}
    .location-landing__tile:hover{border-color:#4f9381;background:#f8fcfb}
    .location-landing__tile strong{display:block;font-size:17px}.location-landing__tile span{display:block;margin-top:5px;color:#667085;font-size:13px;line-height:1.4}
    .location-landing__directory{border-top:1px solid #e7ecef}.location-landing__directory details{border-bottom:1px solid #e7ecef}.location-landing__directory summary{padding:15px 0;cursor:pointer;font-weight:800;list-style:none}.location-landing__directory summary::-webkit-details-marker{display:none}.location-landing__directory ul{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:0;padding:0;list-style:none}.location-landing__directory li{min-width:0}.location-landing__directory a{display:block;height:100%;padding:15px 16px;border:1px solid #dfe7ea;border-radius:4px;background:#fff;color:#17202b;font-size:15px;font-weight:700;text-decoration:none;transition:border-color .15s,background-color .15s,transform .15s}.location-landing__directory a:hover{border-color:#4f9381;background:#f8fcfb;color:#2f7464;transform:translateY(-1px)}
    @media(max-width:900px){.location-landing__grid{grid-template-columns:repeat(2,minmax(0,1fr))}.location-landing__tiles,.location-landing__directory ul{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:620px){.location-landing__container{width:min(100% - 24px,520px)}.location-landing__search-wrap{width:min(100% - 24px,520px);margin:-8px auto 0}.location-landing__search{grid-template-columns:1fr 1fr}.location-landing__search button{grid-column:1/-1}.location-landing__head{display:block}.location-landing__grid,.location-landing__tiles,.location-landing__directory ul{gap:8px}.location-landing__directory a{padding:13px 12px;font-size:14px}.location-landing__grid .wow49-blade-card__event-content{padding:12px!important}}
  </style>
@endpush

@section('content')
  @php
    $crumbs = [['label' => 'Home', 'url' => url('/')], ['label' => 'Locations', 'url' => url('/locations')], ['label' => 'United Kingdom', 'url' => url('/locations/united-kingdom')]];
    if (!empty($location['county']) && !$isCounty) $crumbs[] = ['label' => $location['county'], 'url' => url('/locations/united-kingdom/'.($location['county_slug'] ?? Str::slug($location['county'])))];
    if ($isCounty) $crumbs[] = ['label' => $label];
    elseif ($isTown) $crumbs[] = ['label' => $label];
  @endphp
  @include('partials.breadcrumbs', ['crumbs' => $crumbs, 'schemaUrl' => $seo['canonical'] ?? url()->current()])

  <main class="location-landing">
    @include('partials.landing-hero', [
      'heroEyebrow' => 'Local wellness discovery',
      'heroTitle' => 'Wellness in '.$label,
      'heroIntro' => 'Discover therapies, classes, events and wellness experiences from practitioners across '.$label.'.',
      'heroImage' => $locationImage,
      'heroLocationLabel' => $label,
      'heroAsideTitle' => null,
      'heroAsideText' => '',
      'heroActions' => [['label' => 'Search all', 'href' => url('/search?where='.urlencode($label))]],
    ])
    <div class="location-landing__container">
      <section class="location-landing__section" aria-labelledby="local-offerings-title"><div class="location-landing__head"><div><h2 id="local-offerings-title">{{ $isTown ? 'Wellness near '.$label : 'Explore wellness in '.$label }}</h2><p class="location-landing__copy">Live marketplace offerings, ordered for local relevance and useful quality signals.</p></div>@if($nearby->isNotEmpty())<a class="btn-wow btn-wow--outline btn-sm" href="{{ url('/search?where='.urlencode($label)) }}">View all</a>@endif</div>@if($nearby->isNotEmpty())<div class="location-landing__grid">@foreach($nearby->take(8) as $product)@include('partials.product_card_v4_1',['product'=>$product,'preferredLocation'=>$label])@endforeach</div>@else<div class="location-landing__empty">We don't have any wellness offerings listed in {{ $label }} yet. Try a nearby area or explore online options.</div>@endif</section>

      @if($categories->isNotEmpty())<section class="location-landing__section"><div class="location-landing__head"><div><h2>Top wellness categories in {{ $label }}</h2><p class="location-landing__copy">Categories with genuine local marketplace supply.</p></div></div><div class="location-landing__tiles">@foreach($categories as $category)@php($categoryPath = trim((string) ($location['path'] ?? ''), '/'))<a class="location-landing__tile" href="{{ url('/therapies/'.($category['slug'] ?? Str::slug($category['name'] ?? '')).($categoryPath !== '' ? '/'.Str::after($categoryPath, 'locations/') : '')) }}"><strong>{{ $category['name'] }}</strong><span>{{ $category['count'] }} local {{ $category['count'] === 1 ? 'offering' : 'offerings' }}</span></a>@endforeach</div></section>@endif

      @if($places->isNotEmpty())<section class="location-landing__section"><div class="location-landing__head"><div><h2>Popular places{{ $isCounty ? ' in '.$label : '' }}</h2><p class="location-landing__copy">Towns with useful live marketplace supply and local interest.</p></div></div><div class="location-landing__tiles">@foreach($places as $place)<a class="location-landing__tile" href="{{ url($place['path'] ?? '/locations') }}"><strong>{{ $place['title'] ?? 'Location' }}</strong><span>{{ number_format((int) ($place['supply_count'] ?? 0)) }} wellness offerings</span></a>@endforeach</div></section>@endif

      @if($new->isNotEmpty())<section class="location-landing__section"><div class="location-landing__head"><div><h2>New in {{ $label }}</h2><p class="location-landing__copy">Recently published offerings from the live marketplace.</p></div></div><div class="location-landing__grid">@foreach($new->take(8) as $product)@include('partials.product_card_v4_1',['product'=>$product,'preferredLocation'=>$label])@endforeach</div></section>@endif

      @if($prices->isNotEmpty())<section class="location-landing__section"><div class="location-landing__head"><div><h2>Wellness by price</h2><p class="location-landing__copy">Explore price points with actual {{ $label }} supply.</p></div></div><div class="location-landing__tiles">@foreach($prices as $price)<a class="location-landing__tile" href="{{ url('/search?where='.urlencode($label).'&price_max='.($price['max'] ?? 500)) }}"><strong>{{ $price['label'] }}</strong><span>{{ $price['count'] }} local options</span></a>@endforeach</div></section>@endif

      @if($online->isNotEmpty())<section class="location-landing__section"><div class="location-landing__head"><div><h2>Also available online</h2><p class="location-landing__copy">Wellness experiences you can join from anywhere.</p></div><a class="btn-wow btn-wow--outline btn-sm" href="{{ url('/online') }}">Browse online</a></div><div class="location-landing__grid">@foreach($online->take(4) as $product)@include('partials.product_card_v4_1',['product'=>$product,'preferredLocation'=>null])@endforeach</div></section>@endif

      @if(!empty($directory['towns']))<section class="location-landing__section location-landing__directory"><div class="location-landing__head"><div><h2>Explore {{ $label }} towns</h2><p class="location-landing__copy">Browse towns with real public marketplace supply.</p></div></div><ul>@foreach($directory['towns'] as $town)<li><a href="{{ url($town['path'] ?? '/locations') }}">{{ $town['title'] ?? 'Town' }}</a></li>@endforeach</ul></section>@elseif(!empty($directory['counties']))<section class="location-landing__section location-landing__directory"><div class="location-landing__head"><div><h2>Explore UK counties</h2><p class="location-landing__copy">Browse regions with live wellness supply.</p></div></div><ul>@foreach($directory['counties'] as $county)<li><a href="{{ url($county['path'] ?? '/locations') }}">{{ $county['label'] ?? 'County' }}</a></li>@endforeach</ul></section>@endif
    </div>
  </main>
  @if($locationImage)
    <script>
      (() => {
        const hero = document.querySelector('.location-landing__hero[data-location-hero-image]');
        if (!hero) return;

        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => {
          try {
            const size = 32;
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d', { willReadFrequently: true });
            if (!context) return;

            canvas.width = size;
            canvas.height = size;
            context.drawImage(image, 0, 0, size, size);
            const pixels = context.getImageData(0, 0, size, size).data;
            let luminance = 0;
            let weight = 0;

            for (let index = 0; index < pixels.length; index += 4) {
              const alpha = pixels[index + 3] / 255;
              if (alpha === 0) continue;

              const red = pixels[index] / 255;
              const green = pixels[index + 1] / 255;
              const blue = pixels[index + 2] / 255;
              const relativeLuminance = 0.2126 * red + 0.7152 * green + 0.0722 * blue;
              luminance += relativeLuminance * alpha;
              weight += alpha;
            }

            if (weight > 0) {
              hero.classList.add(luminance / weight < 0.52 ? 'is-dark' : 'is-light');
            }
          } catch (error) {
            // Keep the readable light-overlay default when the remote image
            // does not permit canvas sampling.
          }
        };
        image.src = hero.dataset.locationHeroImage;
      })();
    </script>
  @endif
@endsection
