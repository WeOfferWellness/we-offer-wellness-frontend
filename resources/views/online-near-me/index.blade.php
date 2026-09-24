{{-- resources/views/online-near-me/index.blade.php --}}
@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? 'Online & Near Me | We Offer Wellness®' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  <style>
    .wow-online-near-page{
      position:relative;
      overflow:hidden;
      background:#fff;
      color:#101828;
      padding:0 0 76px;
    }
    .wow-page-grid{
      position:absolute;
      inset:0;
      width:min(100% - 40px, 1280px);
      margin:0 auto;
      pointer-events:none;
      border-left:1px solid rgba(17,24,39,.08);
      border-right:1px solid rgba(17,24,39,.08);
      background-image:
        linear-gradient(to right, transparent calc(25% - 1px), rgba(17,24,39,.10) calc(25% - 1px), rgba(17,24,39,.10) 25%, transparent 25%),
        linear-gradient(to right, transparent calc(50% - 1px), rgba(17,24,39,.10) calc(50% - 1px), rgba(17,24,39,.10) 50%, transparent 50%),
        linear-gradient(to right, transparent calc(75% - 1px), rgba(17,24,39,.10) calc(75% - 1px), rgba(17,24,39,.10) 75%, transparent 75%);
    }
    .wow-page-grid::before,
    .wow-page-grid::after{
      content:"";
      position:absolute;
      top:0;
      bottom:0;
      width:1px;
      border-left:1px dashed rgba(17,24,39,.16);
    }
    .wow-page-grid::before{ left:25%; }
    .wow-page-grid::after{ left:75%; }
    .wow-online-near-container{
      position:relative;
      z-index:1;
      width:min(100% - 40px, 1280px);
      margin:0 auto;
    }
    .wow-online-near-hero{
      display:grid;
      grid-template-columns:minmax(0,.95fr) minmax(280px,.55fr);
      gap:48px;
      align-items:end;
      margin-bottom:30px;
    }
    .wow-kicker{
      margin:0 0 10px;
      color:#344054;
      font-size:13px;
      font-weight:300;
      letter-spacing:.16em;
      text-transform:uppercase;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-online-near-hero h1,
    .wow-route-card h2,
    .wow-quick-card h3,
    .wow-trust-panel h2{
      margin:0;
      color:#101828;
      font-family:'Playfair Display', Georgia, serif;
      font-weight:500;
      letter-spacing:-.055em;
    }
    .wow-online-near-hero h1{
      max-width:760px;
      font-size:clamp(58px, 7.2vw, 96px);
      line-height:.94;
    }
    .wow-online-near-hero p{
      max-width:680px;
      margin:16px 0 0;
      color:#596275;
      font-size:18px;
      line-height:1.55;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-hero-note{
      background:rgba(255,255,255,.96);
      border:1px solid #dfe4ea;
      border-radius:4px;
      padding:18px;
      box-shadow:0 12px 34px rgba(16,24,40,.035);
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-hero-note strong{
      display:block;
      margin-bottom:6px;
      color:#101828;
      font-size:15px;
      font-weight:700;
    }
    .wow-hero-note span{
      display:block;
      color:#667085;
      font-size:14px;
      line-height:1.5;
    }
    .wow-route-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:22px;
      margin-bottom:28px;
    }
    .wow-route-card{
      min-height:362px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      background:rgba(255,255,255,.96);
      border:1px solid #dfe4ea;
      border-radius:4px;
      box-shadow:0 12px 34px rgba(16,24,40,.035);
    }
    .wow-route-card__inner{ padding:24px; }
    .wow-route-card__top{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:14px;
      margin-bottom:26px;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-tag{
      min-height:30px;
      display:inline-flex;
      align-items:center;
      padding:0 10px;
      border:1px solid #f0c879;
      border-radius:4px;
      background:#ffe5b3;
      color:#6f4b10;
      font-size:12px;
      font-weight:600;
    }
    .wow-tag--blue{
      border-color:#c7d8fb;
      background:#e8f0ff;
      color:#254a85;
    }
    .wow-route-number{
      color:#667085;
      font-size:13px;
      font-weight:700;
    }
    .wow-route-card h2{
      max-width:520px;
      font-size:clamp(38px, 4.2vw, 58px);
      line-height:.98;
    }
    .wow-route-card p{
      max-width:590px;
      margin:16px 0 0;
      color:#596275;
      font-size:16px;
      line-height:1.55;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-route-pills{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:22px;
    }
    .wow-route-pills span{
      min-height:34px;
      display:inline-flex;
      align-items:center;
      padding:0 13px;
      border:1px solid #d9dee7;
      border-radius:999px;
      background:#fff;
      color:#344054;
      font-size:13px;
      font-weight:600;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-route-card__footer{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      padding:18px 24px;
      border-top:1px solid #edf0f2;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-route-card__footer small{
      color:#667085;
      font-size:13px;
      line-height:1.45;
    }
    .wow-online-near-page .btn-wow{
      min-height:42px;
      padding:0 22px;
      border-radius:4px;
      font-size:15px;
      font-weight:600;
      white-space:nowrap;
    }
    .wow-postcode-form{
      max-width:520px;
      margin-top:24px;
      position:relative;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-postcode-form label{
      display:block;
      margin-bottom:8px;
      color:#344054;
      font-size:13px;
      font-weight:700;
    }
    .wow-postcode-form__row{
      display:grid;
      grid-template-columns:1fr auto;
      gap:10px;
    }
    .wow-postcode-form input{
      width:100%;
      height:44px;
      border:1px solid #d0d5dd;
      border-radius:4px;
      background:#fff;
      color:#111827;
      padding:0 14px;
      font-size:15px;
      outline:none;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-postcode-form input:focus{
      border-color:#4f9381;
      box-shadow:0 0 0 3px rgba(79,147,129,.14);
    }
    .wow-search-panel__dropdown{
      position:absolute;
      left:0;
      right:0;
      top:calc(100% + 6px);
      z-index:20;
      background:#fff;
      border:1px solid #dfe4ea;
      border-radius:4px;
      box-shadow:0 16px 36px rgba(16,24,40,.08);
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
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-search-panel__dropdown span{
      display:block;
      color:#667085;
      font-size:12px;
      line-height:1.35;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-quick-browse{
      display:grid;
      grid-template-columns:repeat(4, minmax(0,1fr));
      gap:16px;
      margin-bottom:28px;
    }
    .wow-quick-card{
      display:flex;
      min-height:150px;
      flex-direction:column;
      justify-content:space-between;
      background:rgba(255,255,255,.96);
      border:1px solid #dfe4ea;
      border-radius:4px;
      padding:18px;
      text-decoration:none;
      box-shadow:0 10px 30px rgba(16,24,40,.03);
      transition:border-color 160ms ease, transform 160ms ease;
    }
    .wow-quick-card:hover{
      border-color:#4f9381;
      transform:translateY(-2px);
    }
    .wow-quick-card h3{
      font-size:28px;
      line-height:1;
    }
    .wow-quick-card p{
      margin:12px 0 0;
      color:#596275;
      font-size:14px;
      line-height:1.45;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-quick-card span{
      margin-top:16px;
      color:#4f9381;
      font-size:14px;
      font-weight:700;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-trust-panel{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:28px;
      background:rgba(255,255,255,.96);
      border:1px solid #dfe4ea;
      border-radius:4px;
      padding:26px 28px;
      box-shadow:0 12px 34px rgba(16,24,40,.035);
    }
    .wow-trust-panel h2{
      font-size:clamp(32px, 3.8vw, 46px);
      line-height:1;
    }
    .wow-trust-panel p{
      max-width:720px;
      margin:10px 0 0;
      color:#596275;
      font-size:15px;
      line-height:1.55;
      font-family:'Manrope', system-ui, sans-serif;
    }
    @media (max-width: 980px){
      .wow-online-near-hero,
      .wow-route-grid,
      .wow-quick-browse{
        grid-template-columns:1fr;
      }
      .wow-hero-note{ max-width:520px; }
      .wow-quick-browse{ grid-template-columns:repeat(2, minmax(0,1fr)); }
    }
    @media (max-width: 760px){
      .wow-online-near-page{ padding:0 0 58px; }
      .wow-route-card__footer,
      .wow-trust-panel{
        align-items:flex-start;
        flex-direction:column;
      }
    }
    @media (max-width: 560px){
      .wow-online-near-container,
      .wow-page-grid{
        width:min(100% - 28px, 1280px);
      }
      .wow-online-near-hero h1{ font-size:50px; }
      .wow-online-near-hero p{ font-size:16px; }
      .wow-route-card__inner,
      .wow-route-card__footer,
      .wow-trust-panel{ padding-left:18px; padding-right:18px; }
      .wow-route-card h2{ font-size:38px; }
      .wow-postcode-form__row,
      .wow-quick-browse{ grid-template-columns:1fr; }
      .wow-online-near-page .btn-wow,
      .wow-postcode-form button{ width:100%; }
    }
  </style>
@endpush

@section('content')
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Online & Near Me'],
  ],
  'schemaUrl' => url('/online-near-me'),
])

@include('partials.landing-hero', [
  'heroEyebrow' => 'Wellness discovery',
  'heroTitle' => 'Online & Near Me',
  'heroIntro' => 'Choose wellness support you can join from home, or discover trusted practitioners, therapies and events close by.',
  'heroActions' => [
    ['label' => 'Browse online', 'href' => url('/online'), 'style' => 'primary'],
    ['label' => 'Find nearby', 'href' => url('/locations'), 'style' => 'outline'],
  ],
  'heroAsideLabel' => 'Choose your route',
  'heroAsideTitle' => 'Simple, calm, and useful',
  'heroAsideText' => 'Two clear ways into the marketplace: flexible online support or wellness experiences close to you.',
])

@include('partials.hero-meta', [
  'items' => [
    ['label' => 'Trusted wellness options', 'strong' => true],
    'Online sessions',
    'Nearby practitioners',
  ],
])

<main class="wow-online-near-page">
  <div class="wow-page-grid" aria-hidden="true"></div>

  <div class="wow-online-near-container">
    <section class="wow-route-grid" aria-label="Choose online or near me">
      <article class="wow-route-card">
        <div class="wow-route-card__inner">
          <div class="wow-route-card__top">
            <span class="wow-tag">No travel needed</span>
            <span class="wow-route-number">Option 01</span>
          </div>

          <h2>Browse online</h2>
          <p>Find therapies, classes, workshops and one-to-one sessions you can join from home. Ideal when you want support without planning your day around travel or parking.</p>

          <div class="wow-route-pills">
            <span>Video sessions</span>
            <span>Flexible</span>
            <span>Solo</span>
            <span>Group</span>
          </div>
        </div>

        <footer class="wow-route-card__footer">
          <small>Best for remote support, busy schedules and quiet evenings at home.</small>
          <a href="{{ url('/online') }}" class="btn-wow btn-wow--primary">Browse online</a>
        </footer>
      </article>

      <article class="wow-route-card">
        <div class="wow-route-card__inner">
          <div class="wow-route-card__top">
            <span class="wow-tag wow-tag--blue">Local support</span>
            <span class="wow-route-number">Option 02</span>
          </div>

          <h2>Find near me</h2>
          <p>Search by location and discover wellness therapies, events, classes and practitioners nearby.</p>

          <form class="wow-postcode-form" id="wowNearMeForm" method="get" action="{{ url('/locations') }}" autocomplete="off">
            <label for="wow-postcode">Location</label>
            <div class="wow-postcode-form__row">
              <input id="wow-postcode" name="place" type="search" placeholder="e.g. Maidstone" autocomplete="off" required>
              <button type="submit" class="btn-wow btn-wow--primary">Search</button>
            </div>
            <div id="wowNearMeDropdown" class="wow-search-panel__dropdown" hidden></div>
          </form>
        </div>

        <footer class="wow-route-card__footer">
          <small>Best for local sessions, in-person events and practitioners close by.</small>
          <a href="{{ url('/locations') }}" class="btn-wow btn-wow--soft">View nearby</a>
        </footer>
      </article>
    </section>

    <section class="wow-quick-browse" aria-label="Quick browse links">
      <a href="{{ url('/therapies') }}" class="wow-quick-card">
        <div>
          <h3>Therapies</h3>
          <p>Explore massage, reiki, breathwork, coaching, yoga and more.</p>
        </div>
        <span>Browse therapies →</span>
      </a>

      <a href="{{ url('/needs') }}" class="wow-quick-card">
        <div>
          <h3>By Need</h3>
          <p>Find support for stress, sleep, pain, energy and emotional wellbeing.</p>
        </div>
        <span>Browse by need →</span>
      </a>

      <a href="{{ url('/events') }}" class="wow-quick-card">
        <div>
          <h3>Events</h3>
          <p>Discover wellness events, workshops, retreats and classes.</p>
        </div>
        <span>Browse events →</span>
      </a>

      <a href="{{ url('/online') }}" class="wow-quick-card">
        <div>
          <h3>Affordable</h3>
          <p>Start with lower-cost online sessions and accessible options.</p>
        </div>
        <span>Browse under £50 →</span>
      </a>
    </section>

    <section class="wow-trust-panel">
      <div>
        <p class="wow-kicker">Before you book</p>
        <h2>Wellness, with care</h2>
        <p>Always check suitability, practitioner details and any contraindications before booking. If you are unsure whether a therapy is right for you, speak to the practitioner first.</p>
      </div>

      <a href="{{ url('/safety-and-contraindications') }}" class="btn-wow btn-wow--soft">Read safety guidance</a>
    </section>
  </div>
</main>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('wowNearMeForm');
    var input = document.getElementById('wow-postcode');
    var dropdown = document.getElementById('wowNearMeDropdown');
    var token = @json(config('services.mapbox.token'));
    var timer = null;
    var results = [];
    var selected = null;

    function contextLabel(place, prefix) {
      var context = Array.isArray(place && place.context) ? place.context : [];
      var match = context.find(function (item) {
        return String(item && item.id || '').indexOf(prefix + '.') === 0;
      });
      return match && match.text ? String(match.text) : '';
    }

    function hideDropdown() {
      if (!dropdown) return;
      dropdown.hidden = true;
      dropdown.innerHTML = '';
    }

    function slugify(value) {
      return String(value || '')
        .toLowerCase()
        .replace(/&/g, ' and ')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    }

    function isGenericCounty(slug) {
      return ['england', 'scotland', 'wales', 'northern-ireland', 'united-kingdom', 'uk', 'u-k', 'gb', 'great-britain'].indexOf(slug) !== -1;
    }

    function normalizeCountrySlug(country) {
      var slug = slugify(country);
      if (!slug || isGenericCounty(slug)) {
        return 'united-kingdom';
      }
      return slug;
    }

    function normalizeCountySlug(county) {
      var slug = slugify(county);
      return slug && !isGenericCounty(slug) ? slug : '';
    }

    function contextValue(place, prefix) {
      var context = Array.isArray(place && place.context) ? place.context : [];
      var match = context.find(function (item) {
        return String(item && item.id || '').indexOf(prefix + '.') === 0;
      });
      return match && match.text ? String(match.text) : '';
    }

    function canonicalLocationPath(place) {
      var country = contextValue(place, 'country') || 'United Kingdom';
      var county = contextValue(place, 'district') || contextValue(place, 'region') || '';
      var town = contextValue(place, 'place') || String(place && place.text ? place.text : '').trim() || String(place && place.place_name ? place.place_name : '').split(',')[0] || '';

      var countrySlug = normalizeCountrySlug(country);
      var countySlug = normalizeCountySlug(county);
      var townSlug = slugify(town);

      if (townSlug === countrySlug) {
        townSlug = '';
      }

      if (countySlug && townSlug && countySlug === townSlug) {
        countySlug = '';
      }

      if (townSlug === 'london') {
        countySlug = '';
      }

      var segments = ['/locations', countrySlug];
      if (countySlug) segments.push(countySlug);
      if (townSlug && townSlug !== countrySlug && townSlug !== countySlug) {
        segments.push(townSlug);
      }
      return segments.join('/');
    }

    async function geocodeQuery(query) {
      if (!token || !query) return null;
      try {
        var url = new URL('https://api.mapbox.com/geocoding/v5/mapbox.places/' + encodeURIComponent(query) + '.json');
        url.searchParams.set('access_token', token);
        url.searchParams.set('autocomplete', 'true');
        url.searchParams.set('limit', '1');
        url.searchParams.set('types', 'place,postcode,locality,region,district,country');
        url.searchParams.set('country', 'gb');
        var res = await fetch(url.toString());
        if (!res.ok) return null;
        var data = await res.json();
        return Array.isArray(data.features) && data.features.length ? data.features[0] : null;
      } catch (error) {
        console.warn('[online-near-me] mapbox geocode error', error);
        return null;
      }
    }

    async function submitToCanonicalLocation() {
      var query = (input.value || '').trim();
      if (!query) return;
      var place = selected;
      if (!place) {
        place = await geocodeQuery(query);
      }
      if (place) {
        window.location.href = canonicalLocationPath(place);
        return;
      }

      var fallback = new URL(form.action || window.location.origin + '/locations');
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
      dropdown.innerHTML = items.map(function (item, index) {
        var main = item.text || item.place_name || '';
        var secondary = [contextLabel(item, 'place'), contextLabel(item, 'region'), contextLabel(item, 'country')].filter(Boolean).join(', ') || item.place_name || '';
        return '<button type="button" data-index="' + index + '"><strong>' + main + '</strong><span>' + secondary + '</span></button>';
      }).join('');
    }

    async function searchPlaces() {
      if (!token) return;
      var query = (input.value || '').trim();
      if (query.length < 2) {
        results = [];
        hideDropdown();
        return;
      }

      clearTimeout(timer);
      timer = setTimeout(async function () {
        try {
          var url = new URL('https://api.mapbox.com/geocoding/v5/mapbox.places/' + encodeURIComponent(query) + '.json');
          url.searchParams.set('access_token', token);
          url.searchParams.set('autocomplete', 'true');
          url.searchParams.set('limit', '6');
          url.searchParams.set('types', 'place,postcode,locality,region,district,country');
          url.searchParams.set('country', 'gb');

          var res = await fetch(url.toString());
          if (!res.ok) throw new Error('geocode failed');
          var data = await res.json();
          results = Array.isArray(data.features) ? data.features : [];
          showDropdown(results);
        } catch (error) {
          console.warn('[online-near-me] mapbox geocode error', error);
          results = [];
          hideDropdown();
        }
      }, 220);
    }

    if (!form) return;

    input.addEventListener('input', function () {
      selected = null;
      searchPlaces();
    });

    if (dropdown) {
      dropdown.addEventListener('mousedown', function (event) {
        var button = event.target.closest('button[data-index]');
        if (!button) return;
        var index = Number(button.getAttribute('data-index'));
        if (!Number.isFinite(index) || !results[index]) return;
        event.preventDefault();
        selected = results[index];
        input.value = selected.text || selected.place_name || input.value;
        hideDropdown();
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

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      submitToCanonicalLocation();
    });
  });
</script>
@endpush
