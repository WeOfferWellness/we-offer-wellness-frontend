{{-- resources/views/near-me/index.blade.php --}}
@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? 'Near Me | We Offer Wellness™' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  <style>
    .near-me-search{
      position:relative;
      margin-top:18px;
    }
    .near-me-search__row{
      display:grid;
      grid-template-columns:1fr auto;
      gap:10px;
    }
    .near-me-search input{
      width:100%;
    }
    .near-me-search__dropdown{
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
    .near-me-search__dropdown button{
      width:100%;
      display:block;
      text-align:left;
      padding:12px 14px;
      border:none;
      border-bottom:1px solid #edf0f2;
      background:#fff;
      cursor:pointer;
    }
    .near-me-search__dropdown button:last-child{
      border-bottom:none;
    }
    .near-me-search__dropdown button:hover,
    .near-me-search__dropdown button:focus-visible{
      background:#f8fafc;
      outline:none;
    }
    .near-me-search__dropdown strong{
      display:block;
      font-size:14px;
      line-height:1.4;
      color:#101828;
    }
    .near-me-search__dropdown span{
      display:block;
      font-size:12px;
      line-height:1.35;
      color:#667085;
    }
    .near-me-search__saved{
      margin-top:12px;
      padding:10px 12px;
      border:1px solid rgba(79,147,129,.18);
      border-radius:14px;
      background:#f3fbf8;
      color:#101828;
      font-size:13px;
    }
  </style>
@endpush

@section('content')
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Near Me'],
  ],
  'schemaUrl' => url('/near-me'),
])

@include('partials.landing-hero', [
  'heroEyebrow' => 'Find wellness near you',
  'heroTitle' => 'Near Me',
  'heroIntro' => 'Enter your location and we’ll show what’s available near you.',
  'heroAsideLabel' => 'A useful starting point',
  'heroAsideTitle' => 'Find support nearby',
  'heroAsideText' => 'Explore relevant therapies, classes and experiences based on where you are.',
])
@include('partials.hero-meta', [
  'items' => [
    ['label' => 'Location-based discovery', 'strong' => true],
    ['label' => 'Nearby therapies and experiences'],
    ['label' => 'Online options available'],
  ],
])

<section class="section">
  <div class="container-page" style="max-width:760px;">
    <div class="card p-4" style="border-radius:18px;">
      <form method="get" action="{{ url('/locations') }}" id="nearMeSearchForm" autocomplete="off">
        <label class="form-label">Location</label>
        <div class="near-me-search">
          <div class="near-me-search__row">
            <input class="form-control" id="nearMeSearchInput" name="place" placeholder="Start with your location" value="{{ request()->query('place', request()->query('postcode', $savedLocation['label'] ?? '')) }}" aria-autocomplete="list" aria-expanded="false">
            <button class="btn btn-primary" type="submit">Search</button>
          </div>
          <div id="nearMeSearchDropdown" class="near-me-search__dropdown" hidden></div>
        </div>
        <p class="text-muted mt-2 mb-0" style="font-size:13px;">
          We use this only to find nearby results — and we’ll remember it for future visits.
        </p>
        @if(!empty($savedLocation['label']))
          <p class="text-muted mt-2 mb-0" style="font-size:13px;">
            Using your saved location: <strong>{{ $savedLocation['label'] }}</strong>
          </p>
        @endif
      </form>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
  const token = @json(config('services.mapbox.token'));
  const form = document.getElementById('nearMeSearchForm');
  const input = document.getElementById('nearMeSearchInput');
  const dropdown = document.getElementById('nearMeSearchDropdown');
  let timer = null;
  let results = [];

  function hideDropdown() {
    if (!dropdown) return;
    dropdown.hidden = true;
    dropdown.innerHTML = '';
    input?.setAttribute('aria-expanded', 'false');
  }

  function contextLabel(place, prefix) {
    const context = Array.isArray(place?.context) ? place.context : [];
    const match = context.find((item) => String(item?.id || '').startsWith(prefix + '.'));
    return match && match.text ? String(match.text) : '';
  }

  function showDropdown(items) {
    if (!dropdown) return;
    if (!items.length) {
      hideDropdown();
      return;
    }

    dropdown.hidden = false;
    dropdown.innerHTML = items.map((item, index) => {
      const main = item.text || item.place_name || '';
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

  async function searchPlaces() {
    const query = (input?.value || '').trim();
    clearTimeout(timer);
    timer = setTimeout(async () => {
      if (query.length < 2) {
        results = [];
        hideDropdown();
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
        console.warn('[near-me] mapbox search failed', error);
        results = [];
        hideDropdown();
      }
    }, 220);
  }

  if (input) {
    input.addEventListener('input', searchPlaces);
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
      const selected = results[index];
      if (selected?.path) {
        window.location.href = selected.path;
        return;
      }
      if (input) {
        input.value = selected.text || selected.place_name || '';
      }
      hideDropdown();
      if (form && typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else if (form) {
        form.submit();
      }
    });
  }

  if (form) {
    form.addEventListener('submit', function () {
      hideDropdown();
    });
  }

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
