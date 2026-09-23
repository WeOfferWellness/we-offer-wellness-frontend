<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
</head>
<body class="antialiased @yield('body-class')">
@php
  $showLocationPrompt = request()->is('/')
    || request()->is('locations*')
    || request()->is('near-me')
    || request()->is('*-near-me*')
    || request()->is('therapies*')
    || request()->is('classes*')
    || request()->is('events*')
    || request()->is('retreats*')
    || request()->is('workshops*')
    || request()->is('courses*')
    || request()->is('readings*');
@endphp

  <div class="text-ink-800">
      @include('partials.header')
      <main>
          @yield('content')
      </main>
      @include('partials.footer')
      @include('partials.cookie-banner')
      @include('partials.analytics-bridge')
      @include('partials.newsletter-modal')
  </div>

@if($showLocationPrompt)
<div id="wow-location-banner" class="wow-location-banner" hidden>
  <div class="wow-location-banner__panel" role="dialog" aria-modal="true" aria-labelledby="wowLocationTitle">
    <div class="wow-location-banner__simple">
      <p class="wow-location-banner__eyebrow">Your location</p>
      <h2 id="wowLocationTitle">Help us find locations near you</h2>
      <p>Share your location and we’ll show therapies, classes and events close to you first. We’ll remember your choice for future visits.</p>
      <div class="wow-location-banner__actions actions">
        <button type="button" class="wow-location-btn wow-location-btn--primary" data-wow-location-allow>
          <span>Allow and remember</span>
        </button>
        <button type="button" class="wow-location-btn" data-wow-location-skip>Not now</button>
      </div>
      <div class="wow-location-banner__error" data-wow-location-error hidden></div>
    </div>
  </div>
</div>
<style>
.wow-location-banner{
  position:fixed;
  inset:0;
  z-index:1200;
  display:grid;
  place-items:center;
  width:100%;
  padding:20px;
  background:rgba(11,48,40,.28);
  backdrop-filter:blur(4px);
  font-family:'Instrument Sans',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
.wow-location-banner__panel{
  width:min(520px, 100%);
  background:#fff;
  color:#17201d;
  border-radius:8px;
  border:1px solid #dce4e0;
  box-shadow:0 28px 90px rgba(11,48,40,.22);
  padding:32px;
}
.wow-location-banner__eyebrow{
  text-transform:uppercase;
  letter-spacing:.24em;
  font-size:11px;
  color:#4f9482;
  font-weight:700;
  margin:0 0 8px;
}
.wow-location-banner__simple h2{
  margin:0 0 8px;
  color:#0b3028;
  font-family:'Playfair Display',serif;
  font-size:clamp(30px,5vw,42px);
  font-weight:500;
  line-height:1;
  letter-spacing:-.04em;
}
.wow-location-banner__simple p{
  margin:0 0 16px;
  font-size:14px;
  line-height:1.6;
  color:#68736f;
}
.wow-location-banner__actions.actions{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.wow-location-banner__actions .wow-location-btn{
  flex:1 1 auto;
  min-width:110px;
}
.wow-location-btn{
  height:42px;
  min-height:42px;
  border-radius:999px;
  font-size:13px;
  font-weight:600;
  border:1px solid #cfd9d5;
  background:#fff;
  color:#17201d;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;
  box-shadow:none;
  padding:0 18px;
  transition:background .2s ease, color .2s ease, border-color .2s ease;
}
.wow-location-btn:hover,
.wow-location-btn:focus-visible{
  background:#f3f7f5;
  color:#0b3028;
  border-color:#9eafa9;
  outline:none;
}
.wow-location-btn--primary{
  background:#4f9482;
  color:#fff;
  border-color:#4f9482;
  box-shadow:0 10px 22px rgba(79,148,130,.2);
}
.wow-location-btn:disabled{
  opacity:.7;
  cursor:not-allowed;
}
.wow-location-banner__error{
  margin-top:12px;
  color:#b91c1c;
  font-size:12px;
}
@media (max-width: 640px){
  .wow-location-banner__panel{ padding:26px 20px; }
  .wow-location-banner__actions .wow-location-btn{
    width:100%;
  }
}
</style>
<script>
(function () {
  const banner = document.getElementById('wow-location-banner');
  if (!banner) return;

  const allowBtn = banner.querySelector('[data-wow-location-allow]');
  const skipBtn = banner.querySelector('[data-wow-location-skip]');
  const errorEl = banner.querySelector('[data-wow-location-error]');
  const rememberDays = 3650;
  const promptCookieName = 'wow_location_prompt_v2';

  function cookieGet(name){
    const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&') + '=([^;]*)'));
    if (!match) return '';
    try { return decodeURIComponent(match[1]); } catch { return match[1] || ''; }
  }

  function cookieSet(name, value, days){
    const maxAge = days ? days * 24 * 60 * 60 : 60 * 60 * 24 * 365 * 5;
    document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
  }

  function markPromptSeen() {
    cookieSet(promptCookieName, '1', rememberDays);
    cookieSet('wow_geo_done', '1', rememberDays);
  }

  function saveLocationCookie(payload) {
    try {
      cookieSet('wow_location', JSON.stringify(payload), rememberDays);
    } catch {}
  }

  function hasStoredLocation(){
    return Boolean(cookieGet('wow_lat') && cookieGet('wow_lng'));
  }

  function isNearMePage(){
    return window.location.pathname === '/near-me';
  }

  function hideBanner() {
    banner.hidden = true;
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

  async function useMyLocation() {
    errorEl.hidden = true;
    errorEl.textContent = '';

    if (!navigator.geolocation) {
      errorEl.textContent = 'Geolocation is not available in this browser.';
      errorEl.hidden = false;
      return;
    }

    navigator.geolocation.getCurrentPosition(async (pos) => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      let city = '';
      let region = '';
      let country = '';
      let name = 'Current location';
      let postcode = '';
      let district = '';

      try {
        const key = window.WOW_MAPS_KEY || '';
        if (key) {
          const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json`);
          url.searchParams.set('access_token', key);
          url.searchParams.set('limit', '1');
          const res = await fetch(url.toString());
          const json = await res.json();
          const feat = json?.features?.[0];
          if (feat) {
            const comps = feat?.context || [];
            city = (comps.find(c => c.id?.startsWith('place'))?.text) || (comps.find(c => c.id?.startsWith('locality'))?.text) || '';
            region = (comps.find(c => c.id?.startsWith('region'))?.text) || '';
            country = (comps.find(c => c.id?.startsWith('country'))?.text) || '';
            postcode = (comps.find(c => c.id?.startsWith('postcode'))?.text) || '';
            district = (comps.find(c => c.id?.startsWith('district'))?.text) || '';
            name = feat.place_name || city || 'Current location';
          }
        }
      } catch {}

      const location = {
        lat, lng, city, region, country, postcode, district,
        full_name: name,
      };
      saveLocationCookie({
        name,
        city,
        region,
        country,
        postcode,
        district,
        coords: { lat, lng },
      });
      await persistGeo({ lat, lng, city, region, country, mode: 'mixed' });
      window.dispatchEvent(new CustomEvent('wow:location-updated', { detail: { location } }));
      markPromptSeen();
      hideBanner();
      window.location.reload();
    }, () => {
      errorEl.textContent = 'We couldn’t get your location.';
      errorEl.hidden = false;
    }, { enableHighAccuracy: false, timeout: 6000, maximumAge: 60000 });
  }

  function shouldShow() {
    if (isNearMePage() && !hasStoredLocation()) {
      try { return sessionStorage.getItem('wow_near_me_prompted') !== '1'; } catch { return true; }
    }
    return cookieGet(promptCookieName) !== '1';
  }

  allowBtn?.addEventListener('click', function () {
    void useMyLocation();
  });

  skipBtn?.addEventListener('click', function () {
    if (isNearMePage()) {
      try { sessionStorage.setItem('wow_near_me_prompted', '1'); } catch {}
    }
    markPromptSeen();
    hideBanner();
  });

  banner.hidden = !shouldShow();

})();
</script>
@endif


<script data-cfasync="false">
  window.WOW_MAPS_KEY = window.WOW_MAPS_KEY || @json(config('services.mapbox.token'));
</script>
<script data-cfasync="false">
(function(){
  var WOW_ULTRA_SEARCH_SOURCE_PROMISE = null;
  var WOW_ULTRA_SEARCH_SOURCE_CACHE = null;
  var WOW_ULTRA_LOCATION_SOURCE_PROMISE = null;
  var WOW_ULTRA_LOCATION_SOURCE_CACHE = null;

  function wowUltraNormalize(value){
    return String(value || '')
      .toLowerCase()
      .replace(/[\u2018\u2019\u201c\u201d]/g, "'")
      .replace(/[^a-z0-9]+/g, ' ')
      .trim();
  }

  function wowUltraEscapeHtml(value){
    return String(value ?? '').replace(/[&<>"']/g, function(ch){
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }

  function wowUltraCanonicalPlanKey(value){
    var normalized = String(value || '').toLowerCase().trim().replace(/[_ ]+/g, '-');
    switch (normalized) {
      case 'community':
      case 'starter':
      case 'standard':
      case 'free-starter':
      case 'starter-package':
        return 'starter';
      case 'core':
      case 'business-accelerator':
      case 'business-accelerator-package':
      case 'businessaccelerator':
        return 'business-accelerator';
      case 'premium':
      case 'premium-accelerator':
      case 'premiumaccelerator':
        return 'premium-accelerator';
      case 'become-partner':
      case 'partner':
        return 'become-partner';
      default:
        return normalized;
    }
  }

  function wowUltraPlanTitle(value){
    switch (wowUltraCanonicalPlanKey(value)) {
      case 'starter': return 'Starter';
      case 'business-accelerator': return 'Business Accelerator';
      case 'premium-accelerator': return 'Premium Accelerator';
      case 'become-partner': return 'Become Partner';
      default:
        return String(value || '')
          .replace(/[-_]+/g, ' ')
          .replace(/\b\w/g, function(m){ return m.toUpperCase(); }) || 'Plan';
    }
  }

  function wowUltraTypeLabel(value){
    var x = String(value || '').toLowerCase();
    if (x.indexOf('class') !== -1) return 'Class';
    if (x.indexOf('workshop') !== -1) return 'Workshop';
    if (x.indexOf('event') !== -1) return 'Event';
    if (x.indexOf('retreat') !== -1) return 'Retreat';
    if (x.indexOf('gift') !== -1) return 'Gift';
    return 'Therapy';
  }

  function wowUltraGroupLabel(value){
    var x = String(value || '').toLowerCase();
    if (x.indexOf('class') !== -1) return 'Classes';
    if (x.indexOf('workshop') !== -1) return 'Workshops';
    if (x.indexOf('event') !== -1) return 'Events';
    if (x.indexOf('retreat') !== -1) return 'Retreats';
    if (x.indexOf('gift') !== -1) return 'Gifts';
    return 'Therapies';
  }

  function wowUltraMatches(query, item){
    return wowUltraSearchScore(query, item) < 999;
  }

  function wowUltraSearchScore(query, item){
    var q = wowUltraNormalize(query);
    if (!q) return 999;
    var hay = [
      item.title,
      item.cat,
      item.type,
      item.vendor_name,
      item.plan_label,
      item.subtitle,
    ].map(wowUltraNormalize).join(' ');
    var tokens = q.split(/\s+/).filter(Boolean);
    var title = wowUltraNormalize(item.title);
    if (title === q) return 0;
    if (title.indexOf(q) === 0) return 1;
    if (title.split(/\s+/).some(function(token){ return token.indexOf(q) === 0; })) return 2;
    if (title.indexOf(q) !== -1) return 3;
    if (hay.indexOf(q) !== -1) return 4;
    if (tokens.length && tokens.every(function(token){ return hay.indexOf(token) !== -1; })) return 5;
    return 999;
  }

  function wowUltraBuildSearchSource(payload){
    var categories = Array.isArray(payload && payload.categories) ? payload.categories : [];

    return {
      categories: categories.map(function(item){
        var title = String(item && (item.title || item.label || item.value) || '').trim();
        var counts = item && item.counts ? item.counts : {};
        return {
          cat: String((item && item.cat) || 'Modalities'),
          title: title,
          label: title,
          value: String((item && item.value) || title),
          type: String((item && item.type) || 'Modality'),
          subtitle: String((item && item.subtitle) || ''),
          slug: String((item && item.slug) || ''),
          search: String((item && item.search) || [title, item && item.slug].filter(Boolean).join(' ')),
          counts: {
            products: Number(counts.products || 0),
            offerings: Number(counts.offerings || 0),
            total: Number(counts.total || 0)
          }
        };
      }).filter(function(item){ return !!item.title; }).sort(function(a, b){
        var at = Number(a.counts && a.counts.total || 0);
        var bt = Number(b.counts && b.counts.total || 0);
        if (at !== bt) return bt - at;
        return String(a.title || '').localeCompare(String(b.title || ''));
      }),
    };
  }

  function wowUltraLoadSearchSource(){
    if (WOW_ULTRA_SEARCH_SOURCE_CACHE) return Promise.resolve(WOW_ULTRA_SEARCH_SOURCE_CACHE);
    if (WOW_ULTRA_SEARCH_SOURCE_PROMISE) return WOW_ULTRA_SEARCH_SOURCE_PROMISE;

    WOW_ULTRA_SEARCH_SOURCE_PROMISE = fetch('/cache/what-categories.json', { cache: 'no-store' })
      .then(function(res){ if (!res.ok) throw new Error('catalog ' + res.status); return res.json(); })
      .then(function(payload){
        WOW_ULTRA_SEARCH_SOURCE_CACHE = wowUltraBuildSearchSource(payload);
        return WOW_ULTRA_SEARCH_SOURCE_CACHE;
      })
      .catch(function(){
        WOW_ULTRA_SEARCH_SOURCE_CACHE = { categories: [] };
        return WOW_ULTRA_SEARCH_SOURCE_CACHE;
      });

    return WOW_ULTRA_SEARCH_SOURCE_PROMISE;
  }

  function wowUltraBuildLocationSource(payload){
    var source = Array.isArray(payload && payload.flat) && payload.flat.length ? payload.flat : (Array.isArray(payload && payload.suggestions) ? payload.suggestions : []);
    var seen = {};
    return source.map(function(item){
      var title = String((item && (item.title || item.label || item.slug)) || '').trim();
      var country = String((item && item.country) || '').trim();
      var county = String((item && (item.county || item.district || item.region)) || '').trim();
      var slug = String((item && item.slug) || title || '').toLowerCase().replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
      return {
        title: title || 'Location',
        label: title || 'Location',
        value: title || 'Location',
        subtitle: item && item.online ? 'Virtual' : [county, country].filter(Boolean).join(', '),
        icon: item && item.online ? 'wifi' : 'geo',
        path: item && item.path ? item.path : null,
        slug: slug,
        country: country,
        county: county,
        district: county,
        region: String((item && item.region) || '').trim(),
        online: !!(item && item.online),
        total: Number((item && item.counts && (item.counts.total || item.counts.products || item.counts.offerings)) || 0),
        search: [title, country, county, item && item.label, item && item.place_name, slug].filter(Boolean).join(' '),
      };
    }).filter(function(item){
      var key = wowUltraNormalize(item.value);
      if (!key || seen[key]) return false;
      seen[key] = true;
      return true;
    }).sort(function(a, b){
      if (a.online !== b.online) return a.online ? -1 : 1;
      var at = Number(a.total || 0);
      var bt = Number(b.total || 0);
      if (at !== bt) return bt - at;
      return String(a.title || '').localeCompare(String(b.title || ''));
    });
  }

  function wowUltraLoadLocationSource(){
    if (WOW_ULTRA_LOCATION_SOURCE_CACHE) return Promise.resolve(WOW_ULTRA_LOCATION_SOURCE_CACHE);
    if (WOW_ULTRA_LOCATION_SOURCE_PROMISE) return WOW_ULTRA_LOCATION_SOURCE_PROMISE;

    WOW_ULTRA_LOCATION_SOURCE_PROMISE = fetch('/cache/locations.json', { cache: 'no-store' })
      .then(function(res){ if (!res.ok) throw new Error('locations ' + res.status); return res.json(); })
      .then(function(payload){
        WOW_ULTRA_LOCATION_SOURCE_CACHE = wowUltraBuildLocationSource(payload);
        return WOW_ULTRA_LOCATION_SOURCE_CACHE;
      })
      .catch(function(){
        WOW_ULTRA_LOCATION_SOURCE_CACHE = [];
        return WOW_ULTRA_LOCATION_SOURCE_CACHE;
      });

    return WOW_ULTRA_LOCATION_SOURCE_PROMISE;
  }

  // Warm the cache immediately so the autocomplete feels instant once users start typing.
  wowUltraLoadSearchSource();
  wowUltraLoadLocationSource();

  function setupUltraSearchBar(prefix){
    var root = document.querySelector('[id^="'+prefix+'-seg-"]')?.closest('.wow-ultra') || document.querySelector('#'+prefix+'-seg-what')?.closest('.wow-ultra');
    // If structure not found, bail
    if(!root) return;
    if (root.dataset.wowUltraBound === '1') return;
    root.dataset.wowUltraBound = '1';

    function byId(s){ return document.getElementById(prefix + '-' + s) }
    var panes = ['what-pane','where-pane','when-pane','who-pane'];
    var WHAT_LIMIT_PER_SECTION = 5;

    function hideAll(){
      panes.forEach(function(id){ var el = byId(id); if(el) el.classList.add('d-none') })
      var what = byId('what'); if(what) what.setAttribute('aria-expanded','false');
    }
    function openPane(which){
      hideAll();
      var pane = byId(which+'-pane');
      if(pane){ pane.classList.remove('d-none') }
      if(which==='what'){ var what = byId('what'); if(what) what.setAttribute('aria-expanded','true') }
    }

    // Open on clicks/focus
    var whatInput = byId('what');
    var whatSource = null;
    var whatSourceReady = false;
    var whereSource = null;
    var whereSourceReady = false;

    function renderWhat(qs){
      var list = byId('what-list');
      if (!list) return false;

      var query = (qs || '').trim();
      if (!whatSourceReady) {
        list.innerHTML = '<button type="button" class="item" aria-disabled="true"><span class="title">Loading modalities…</span></button>';
        return false;
      }

      var categories = (whatSource && whatSource.categories ? whatSource.categories : []);
      categories = query.length < 2
        ? categories.slice(0, WHAT_LIMIT_PER_SECTION)
        : categories
          .map(function(item){ return { item: item, score: wowUltraSearchScore(query, item) }; })
          .filter(function(row){ return row.score < 999; })
          .sort(function(a, b){
            if (a.score !== b.score) return a.score - b.score;
            return String(a.item.title || '').localeCompare(String(b.item.title || ''));
          })
          .slice(0, WHAT_LIMIT_PER_SECTION)
          .map(function(row){ return row.item; });

      if (!categories.length) {
        list.innerHTML = '';
        hideAll();
        return false;
      }

      var html = '<div class="section-title">' + (query.length < 2 ? 'Trending modalities' : 'Modalities') + '</div><div>';
      categories.forEach(function(item){
        var subtitle = item && item.subtitle ? item.subtitle : (item && item.counts && Number(item.counts.total || 0) ? Number(item.counts.total || 0) + ' uses' : 'Modality');
        html += '<button type="button" class="item" role="option" data-value="' + wowUltraEscapeHtml(item.value || item.title || '') + '">'
          + '<i class="bi bi-tag"></i>'
          + '<span class="title">' + wowUltraEscapeHtml(item.title || '') + '</span>'
          + '<span class="text-muted ms-2">' + wowUltraEscapeHtml(subtitle) + '</span>'
          + '</button>';
      });
      html += '</div>';
      list.innerHTML = html;
      return true;
    }

    function renderWhere(qs){
      var list = byId('where-list');
      if (!list) return false;
      var query = (qs || '').trim();
      if (!whereSourceReady) {
        list.innerHTML = '<button type="button" class="item" aria-disabled="true"><span class="title">Loading trending destinations…</span></button>';
        return false;
      }

      var items = (whereSource || []).slice();
      if (query) {
        var needle = wowUltraNormalize(query);
        items = items.filter(function(item){
          return wowUltraNormalize([item.title, item.label, item.search, item.country, item.county, item.region].join(' ')).indexOf(needle) !== -1;
        });
      } else {
        items = items.slice(0, 5);
      }

      if (!items.length) {
        list.innerHTML = '<button type="button" class="item" aria-disabled="true"><span class="title">No locations found</span></button>';
        return false;
      }

      var html = '';
      items.slice(0, query ? 12 : 5).forEach(function(item){
        var icon = item && item.online ? '<i class="bi bi-wifi"></i>' : '<i class="bi bi-geo-alt"></i>';
        var sub = item && item.subtitle ? '<span class="text-muted ms-2">' + wowUltraEscapeHtml(item.subtitle) + '</span>' : '';
        html += '<button type="button" class="item" role="option" data-value="' + wowUltraEscapeHtml(item.value || item.title || '') + '">'
          + icon
          + '<span class="title">' + wowUltraEscapeHtml(item.title || '') + '</span>'
          + sub
          + '</button>';
      });
      list.innerHTML = html;
      return true;
    }

    function refreshWhat(){
      var qs = whatInput ? whatInput.value : '';
      var hasResults = renderWhat(qs);
      if (hasResults) {
        openPane('what');
      } else {
        hideAll();
      }
    }

    wowUltraLoadSearchSource().then(function(source){
      whatSource = source;
      whatSourceReady = true;
      if (whatInput && renderWhat(whatInput.value || '') && document.activeElement === whatInput) {
        openPane('what');
      }
    });
    wowUltraLoadLocationSource().then(function(source){
      whereSource = source;
      whereSourceReady = true;
      renderWhere((byId('where-editor') && byId('where-editor').textContent) || '');
    });

    if(whatInput){
      whatInput.addEventListener('focus', function(e){ refreshWhat(); });
      whatInput.addEventListener('input', function(e){ refreshWhat(); });
      var segWhat = byId('seg-what');
      if(segWhat){ segWhat.addEventListener('click', function(){ refreshWhat(); }) }
    }

    var whereEditor = byId('where-editor');
    if(whereEditor){
      whereEditor.addEventListener('focus', function(){ renderWhere(whereEditor.textContent || ''); openPane('where') });
      whereEditor.addEventListener('click', function(){ renderWhere(whereEditor.textContent || ''); openPane('where') });
      whereEditor.addEventListener('input', function(){ renderWhere(whereEditor.textContent || ''); openPane('where') });
    }
    // Also open when clicking the whole segment (icon/label area)
    var segWhere = byId('seg-where');
    if(segWhere){ segWhere.addEventListener('click', function(){ openPane('where') }) }

    var whenInput = byId('when');
    if(whenInput){
      whenInput.addEventListener('focus', function(){ openPane('when') });
      whenInput.addEventListener('click', function(){ openPane('when') });
    }
    var segWhen = byId('seg-when');
    if(segWhen){ segWhen.addEventListener('click', function(){ openPane('when') }) }

    var whoSeg = byId('seg-who');
    if(whoSeg){
      whoSeg.addEventListener('click', function(){ openPane('who') });
    }

    // Close when clicking outside
    document.addEventListener('click', function(e){
      try{
        const path = typeof e.composedPath === 'function' ? e.composedPath() : [];
        if (path.length && path.includes(root)) return;
        if (root && root.contains(e.target)) return;
        if (e.target && typeof e.target.closest === 'function') {
          if (e.target.closest('.pane')) return;
          if (e.target.closest('.seg')) return;
        }
        hideAll();
      }catch(_){ /* no-op */ }
    });
    // ESC closes
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') hideAll() });

    // Item selections
    var whatList = byId('what-list');
    if(whatList && whatInput){
      whatList.addEventListener('click', function(e){
        try { e.stopPropagation(); } catch(_) {}
        var btn = e.target.closest('.item');
        if(btn && btn.dataset.value){ whatInput.value = btn.dataset.value; hideAll(); whatInput.blur(); }
      });
    }
    var whereHidden = byId('where');
    if(byId('where-list') && whereEditor){
      byId('where-list').addEventListener('click', function(e){
        try { e.stopPropagation(); } catch(_) {}
        var btn = e.target.closest('.item');
        if(btn && btn.dataset.value){
          try { e.preventDefault(); e.stopPropagation(); } catch(_) {}
          whereEditor.textContent = btn.dataset.value;
          if(whereHidden) whereHidden.value = btn.dataset.value;
          hideAll();
          try { whereEditor.blur(); } catch(_) {}
        }
      });
    }
    var whoDone = byId('who-done');
    if(whoDone){ whoDone.addEventListener('click', function(e){ try { e.stopPropagation(); } catch(_) {} hideAll() }) }

    // Shared Who controls: adults counter + group type selection
    (function initWhoControls(){
      var pane = byId('who-pane');
      var adultsEl = byId('adults-val');
      var groupList = byId('group-type-list') || byId('group-type-list') || document.getElementById(prefix + '-group-type-list');
      var summaryEl = byId('who-summary');
      if (!pane || !adultsEl || pane.dataset.wowWhoBound === '1') return;

      var groupTouched = false;

      function clampAdults(n){
        var num = Number(n);
        if (!Number.isFinite(num)) return 0;
        return Math.max(0, Math.min(20, Math.round(num)));
      }

      function getAdults(){
        return clampAdults((adultsEl.textContent || adultsEl.value || '0').trim());
      }

      function setGroupSelection(name){
        if (!groupList) return;
        var target = name == null ? '' : String(name || '');
        Array.from(groupList.querySelectorAll('[data-group]')).forEach(function(btn){
          var isMatch = String(btn.getAttribute('data-group')) === target;
          btn.setAttribute('aria-selected', isMatch ? 'true' : 'false');
        });
      }

      function groupForAdults(n){
        if (n <= 0) return '';
        if (n === 1) return 'Solo';
        if (n === 2) return 'Couple';
        return 'Group';
      }

      function getSelectedGroup(){
        if (!groupList) return '';
        var sel = groupList.querySelector('[data-group][aria-selected="true"]');
        return (sel?.getAttribute?.('data-group') || '').trim();
      }

      function updateSummary(){
        if (!summaryEl) return;
        var adults = getAdults();
        var group = getSelectedGroup();
        var parts = [];
        if (adults > 0) parts.push(adults + ' ' + (adults === 1 ? 'adult' : 'adults'));
        if (group) parts.push(group);
        summaryEl.textContent = parts.length ? parts.join(' · ') : 'Add guests';
      }

      function applyAdults(n){
        var next = clampAdults(n);
        adultsEl.textContent = String(next);
        if (!groupTouched) setGroupSelection(groupForAdults(next));
        updateSummary();
      }

      pane.addEventListener('click', function(event){
        try { event.stopPropagation(); } catch(_) {}
        var dec = event.target.closest('[data-dec="adults"]');
        var inc = event.target.closest('[data-inc="adults"]');
        if (!dec && !inc) return;
        event.preventDefault();
        var current = getAdults();
        applyAdults(current + (inc ? 1 : -1));
      });

      if (groupList) {
        groupList.addEventListener('click', function(event){
          try { event.stopPropagation(); } catch(_) {}
          var btn = event.target.closest('[data-group]');
          if (!btn) return;
          var group = (btn.getAttribute('data-group') || '').trim();
          if (!group) return;
          groupTouched = true;
          if (group === 'Solo') {
            setGroupSelection('Solo');
            applyAdults(1);
          } else if (group === 'Couple') {
            setGroupSelection('Couple');
            applyAdults(2);
          } else {
            setGroupSelection('Group');
            applyAdults(Math.max(3, getAdults() || 3));
          }
        });
      }

      var whenPane = byId('when-pane');
      if (whenPane) {
        whenPane.addEventListener('click', function(event){
          try { event.stopPropagation(); } catch(_) {}
        });
      }

      updateSummary();
      pane.dataset.wowWhoBound = '1';
    })();
    // Ensure panes start closed on load
    try{ hideAll(); } catch(_){ }

    // Submit handler → build /search URL
    try {
      var form = root.closest('form') || root.querySelector('form') || document.querySelector('.wow-ultra form.bar');
      if(form){
        form.addEventListener('submit', function(e){
          try { e.preventDefault(); } catch(_) {}
          var whatEl = byId('what');
          var what = (whatEl && whatEl.value ? whatEl.value : '').trim();
          if(!what){
            try {
              if (whatEl) {
                whatEl.setCustomValidity('Please enter what you want to search for.');
                whatEl.reportValidity();
                whatEl.focus();
                whatEl.setCustomValidity('');
              }
            } catch(_e) {}
            return;
          }
          var params = new URLSearchParams();
          // what
          if(what) params.set('what', what);
          // where
          var whereHidden = byId('where');
          var whereText = byId('where-editor')?.textContent?.trim();
          var where = (whereHidden && whereHidden.value) ? whereHidden.value : (whereText || '');
          if (where) params.set('where', where);
          // when (as-is string)
          var whenEl = byId('when');
          var when = whenEl?.value?.trim();
          if(when) params.set('when', when);
          if (whenEl?.dataset?.rangeStart) params.set('when_start', whenEl.dataset.rangeStart);
          if (whenEl?.dataset?.rangeEnd) params.set('when_end', whenEl.dataset.rangeEnd);
          // group type
          var groupList = byId('group-type-list') || document.getElementById(prefix + '-group-type-list');
          if(groupList){
            var sel = groupList.querySelector('.item[aria-selected="true"]');
            var gt = sel?.getAttribute('data-group') || sel?.textContent?.trim();
            if(gt){ params.set('group_type', gt.toLowerCase()); }
          }
          // adults count
          var adultsVal = document.getElementById(prefix + '-adults-val');
          var adults = adultsVal ? parseInt(adultsVal.textContent, 10) : NaN;
          if(Number.isFinite(adults) && adults > 0) params.set('adults', String(adults));
          // Build URL and navigate
          var url = '/search' + (params.toString() ? ('?' + params.toString()) : '');
          try { window.location.assign(url); } catch(_) { window.location.href = url; }
        });
      }
    } catch(err) { /* no-op */ }
  }

  try { window.setupUltraSearchBar = setupUltraSearchBar; } catch (_) {}

  // Initialize bars present on the page
  ['home-template','home-sticky','search-top','header-search'].forEach(function(prefix){
    try { setupUltraSearchBar(prefix) } catch(err) { /* no-op */ }
  });

  // Hide any nav/menu link labelled "Recordings" (temporary)
  try {
    var navLinks = document.querySelectorAll('header .nav-item a, .mega-panel a, nav a');
    navLinks.forEach(function(a){
      if(/recordings/i.test((a.textContent||'').trim())){
        var hideEl = a.closest('.nav-item') || a;
        hideEl.style.display = 'none';
      }
    });
  } catch {}

  // Hide any section/cards that promote on-demand or recorded content (temporary)
  try {
    var phrases = [/on\s?-?\s?demand/i, /recorded/i, /recordings/i, /replay/i];
    var headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6, .kicker');
    headings.forEach(function(h){
      var text = (h.textContent || '').trim();
      if(phrases.some(function(rx){ return rx.test(text) })){
        var container = h.closest('section') || h.closest('.section') || h.closest('.card') || h.closest('.container-page');
        if(container){ container.style.display = 'none'; }
      }
    });
  } catch {}

  // Mega menu: show/hide + switch content
  try {
    var headerEl = document.querySelector('header');
    var panel = document.getElementById('mega-panel');
    if (headerEl && panel) {
      if (!panel.classList.contains('wow-mega-shell')) {
        function showMenu(key){
          if(!key){ hideMenu(); return }
          panel.hidden = false;
          panel.setAttribute('data-active', key);
        }
        function hideMenu(){ panel.hidden = true; panel.removeAttribute('data-active'); }

        // Attach to nav links via data-mega-menu attribute (e.g., data-mega-menu="need").
        // If a link has no mega menu, hovering it will close any open panel.
        headerEl.querySelectorAll('.nav-item > a.link-wow--nav').forEach(function(a){
          var key = a.getAttribute('data-mega-menu');
          a.addEventListener('mouseenter', function(){ key ? showMenu(key) : hideMenu(); });
          a.addEventListener('focus', function(){ key ? showMenu(key) : hideMenu(); });
        });
        // Keep open when hovering panel; close on leaving header+panel area
        var closeTimer;
        function scheduleClose(){ clearTimeout(closeTimer); closeTimer = setTimeout(hideMenu, 400); }
        function cancelClose(){ clearTimeout(closeTimer); }
        // Only close when leaving BOTH header and panel areas
        headerEl.addEventListener('mouseleave', function(e){
          try { if (panel.contains(e.relatedTarget)) return; } catch(_) {}
          scheduleClose();
        });
        headerEl.addEventListener('mouseenter', cancelClose);
        panel.addEventListener('mouseenter', cancelClose);
        panel.addEventListener('mouseleave', function(e){
          try { if (headerEl.contains(e.relatedTarget)) return; } catch(_) {}
          scheduleClose();
        });
        // Defensive: keep open on any movement within panel
        panel.addEventListener('mousemove', cancelClose);
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') hideMenu() });
      }
    }
  } catch {}

  // Mobile menu toggle
  try {
    var burger = document.querySelector('[data-wow-mobile-toggle]') || document.querySelector('button[aria-label="Menu"]');
    var mobile = document.getElementById('mobile-menu');
    var mobileBackdrop = document.getElementById('mobile-menu-backdrop');
    if (burger && mobile){
      function setBodyScroll(disabled){ try{ document.body.style.overflow = disabled ? 'hidden' : ''; }catch{} }
      function syncHamburger(state){
        try{
          if (window.__WOWHamburger && typeof window.__WOWHamburger.set === 'function') {
            window.__WOWHamburger.set(state);
          } else {
            window.__WOWHamburgerQueue = window.__WOWHamburgerQueue || [];
            window.__WOWHamburgerQueue.push(state);
          }
        }catch(_err){}
      }
      function syncBurgerLabel(state) {
        try {
          if (!burger) return;
          burger.setAttribute('aria-label', state ? 'Close menu' : 'Menu');
          burger.setAttribute('aria-expanded', state ? 'true' : 'false');
          burger.classList.toggle('is-open', state);
          var label = burger.querySelector('.mobile-nav-text-trigger__label');
          if (label) {
            label.textContent = state ? 'Close menu' : 'Menu';
          }
        } catch(_err) {}
      }
      var open = false;
      function closeMobile(){ mobile.style.display = 'none'; if (mobileBackdrop) mobileBackdrop.classList.remove('is-visible'); syncBurgerLabel(false); setBodyScroll(false); syncHamburger(false); open = false; }
      function openMobile(){
        try {
          if (typeof window.__WOWCloseMobileSearch === 'function') {
            window.__WOWCloseMobileSearch();
          }
          const searchDrawer = document.getElementById('mobile-search-drawer');
          if (searchDrawer) {
            searchDrawer.classList.remove('is-visible');
            searchDrawer.setAttribute('aria-hidden', 'true');
          }
          const searchTrigger = document.querySelector('[data-mobile-search-trigger]');
          if (searchTrigger) {
            searchTrigger.classList.remove('is-open');
            searchTrigger.setAttribute('aria-expanded', 'false');
            searchTrigger.setAttribute('aria-label', 'Search');
            const searchLabel = searchTrigger.querySelector('.mobile-nav-text-trigger__label');
            if (searchLabel) searchLabel.textContent = 'Search';
          }
        } catch(_err){}
        mobile.style.display = 'block';
        if (mobileBackdrop) mobileBackdrop.classList.add('is-visible');
        syncBurgerLabel(true);
        setBodyScroll(true);
        syncHamburger(true);
        open = true;
      }
      burger.addEventListener('click', function(){ open ? closeMobile() : openMobile(); });
      document.addEventListener('keydown', function(e){ if(e.key==='Escape' && open){ closeMobile(); }});
      mobile.addEventListener('click', function(e){ var a = e.target.closest('a'); if(a){ closeMobile(); }});
      if (mobileBackdrop) mobileBackdrop.addEventListener('click', closeMobile);
      // Close if window resized to desktop
      window.addEventListener('resize', function(){ if(window.innerWidth >= 768 && open){ closeMobile(); }});
    }
  } catch {}
})();
</script>
<script>
(function(){
  const COOKIE_KEY = 'wow_cookie_preferences';
  const NEED_HISTORY_KEY = 'wow_need_history';
  const THERAPY_HISTORY_KEY = 'wow_therapy_history';
  const THERAPY_SAVED_KEY = 'wow_saved_therapies';

  const needNodes = {
    popular: document.querySelector('[data-need-default-popular]'),
    trending: document.querySelector('[data-need-default-trending]'),
    defaultBlock: document.querySelector('[data-need-default-block]'),
    personalizedBlock: document.querySelector('[data-need-personalized-block]'),
    continueList: document.querySelector('[data-need-continue]'),
    recommendedList: document.querySelector('[data-need-recommended]')
  };

  const therapyNodes = {
    popular: document.querySelector('[data-therapy-popular-list]'),
    personalizedBlock: document.querySelector('[data-therapy-personalized-block]'),
    recentList: document.querySelector('[data-therapy-recent]'),
    savedList: document.querySelector('[data-therapy-saved]')
  };

  const needCatalogue = @json(array_values($publicNeeds ?? []));
  const needDefaults = {
    popular: needCatalogue.slice(0, 3).map((need) => ({
      slug: need.slug,
      title: need.name,
      url: `/needs/${encodeURIComponent(need.slug)}`,
    })),
    trending: needCatalogue.slice(3, 6).map((need) => ({
      slug: need.slug,
      title: need.name,
      url: `/needs/${encodeURIComponent(need.slug)}`,
    })),
  };

  const therapyDefaults = {
    pinned: [
      { title: 'Massage therapy', url: '/therapy/massage', id: null },
      { title: 'Reiki', url: '/therapy/reiki', id: null },
      { title: 'Reflexology', url: '/therapy/reflexology', id: null },
      { title: 'Acupuncture', url: '/therapy/acupuncture', id: null },
      { title: 'Breathwork (1:1)', url: '/therapy/breathwork', id: null },
      { title: 'Hypnotherapy', url: '/therapy/hypnotherapy', id: null },
      { title: 'Coaching & counselling', url: '/therapy/coaching-and-counselling', id: null },
      { title: 'Sound healing', url: '/therapy/sound-healing', id: null },
    ],
    rotation: [
      { title: 'Somatic experiencing', url: '/therapy/somatic-experiencing', id: null },
      { title: 'Craniosacral therapy', url: '/therapy/craniosacral-therapy', id: null },
      { title: 'Lymphatic drainage', url: '/therapy/lymphatic-drainage', id: null },
      { title: 'Corporate desk reset', url: '/therapy/corporate-wellness', id: null },
    ],
    defaultColumn: [
      { title: 'Massage therapy', url: '/therapy/massage', id: null },
      { title: 'Reiki', url: '/therapy/reiki', id: null },
      { title: 'Reflexology', url: '/therapy/reflexology', id: null },
      { title: 'Acupuncture', url: '/therapy/acupuncture', id: null },
      { title: 'Breathwork (1:1)', url: '/therapy/breathwork', id: null },
    ]
  };

  function readStorageArray(key) {
    try {
      const raw = localStorage.getItem(key);
      const data = raw ? JSON.parse(raw) : [];
      return Array.isArray(data) ? data : [];
    } catch (_err) {
      return [];
    }
  }

  function readCookiePrefs() {
    try {
      const raw = localStorage.getItem(COOKIE_KEY);
      const data = raw ? JSON.parse(raw) : null;
      return (data && typeof data === 'object') ? data : null;
    } catch (_err) {
      return null;
    }
  }

  function canPersonalize() {
    const prefs = readCookiePrefs();
    return !!(prefs && prefs.personalization === true);
  }

  function renderLinks(target, items, options = {}) {
    if (!target) return;
    const fallback = options.fallback || 'No suggestions yet';
    if (!items || !items.length) {
      target.innerHTML = `<li><span class="menu-link menu-link--disabled">${fallback}</span></li>`;
      return;
    }
    const cartIds = options.cartIds || new Set();
    target.innerHTML = items.map((item) => {
      if (!item || !item.title) return '';
      const id = deriveId(item);
      const badge = (options.showBasket && id && cartIds.has(id)) ? '<span class="menu-pill">In basket</span>' : '';
      return `<li><a class="menu-link" href="${item.url}">${item.title}${badge}</a></li>`;
    }).join('');
  }

  function deriveId(entry) {
    if (!entry) return null;
    if (entry.id) return String(entry.id);
    if (entry.url) {
      const match = entry.url.match(/\/([0-9]+)-/);
      if (match) return match[1];
    }
    return null;
  }

  function compactCartEntry(entry) {
    if (!entry) return null;
    const meta = entry.meta && typeof entry.meta === 'object' ? entry.meta : {};
    const id = entry.id || entry.product_id || meta.product_id || entry.variant_id || meta.variant_id;
    if (!id) return null;
    return {
      id: String(id),
      product_id: entry.product_id || meta.product_id || null,
      variant_id: entry.variant_id || meta.variant_id || null,
      variant_label: entry.variant_label || meta.variant_label || '',
      source_version: entry.source_version || meta.source_version || null,
      title: entry.title || entry.name || '',
      price: Number(entry.price || entry.unit || 0) || 0,
      qty: Math.max(1, Number(entry.qty || entry.quantity || 1) || 1),
      image: entry.image || entry.img || null,
      url: entry.url || entry.href || '#',
    };
  }

  function compactCartPayload(payload) {
    const items = Array.isArray(payload)
      ? payload
      : (payload && Array.isArray(payload.items))
        ? payload.items
        : (payload && typeof payload === 'object')
          ? Object.values(payload)
          : [];
    return items.map(compactCartEntry).filter(Boolean);
  }

  function rewriteCartCookieIfNeeded() {
    try {
      const matches = document.cookie.split(';').map(row => row.trim()).filter(row => row.startsWith('wow_cart='));
      if (!matches.length) return;

      const byId = new Map();
      matches.forEach((cookieRow) => {
        try {
          const raw = decodeURIComponent(cookieRow.slice('wow_cart='.length));
          const parsed = JSON.parse(raw);
          compactCartPayload(parsed).forEach((item) => {
            const id = String(item?.id || '');
            if (!id || byId.has(id)) return;
            byId.set(id, item);
          });
        } catch (_err) {}
      });

      const compact = Array.from(byId.values());
      const next = JSON.stringify(compact);
      document.cookie = `wow_cart=; Path=/; Max-Age=0; SameSite=Lax`;
      document.cookie = `wow_cart=; Domain=.weofferwellness.co.uk; Path=/; Max-Age=0; SameSite=Lax`;
      if (compact.length) {
        document.cookie = `wow_cart=${encodeURIComponent(next)}; Domain=.weofferwellness.co.uk; Path=/; Max-Age=${60 * 60 * 24 * 30}; SameSite=Lax`;
      }
    } catch (_err) {}
  }

  rewriteCartCookieIfNeeded();

  function readCartIds() {
    try {
      const cookie = document.cookie.split(';').map(row => row.trim()).find(row => row.startsWith('wow_cart='));
      if (!cookie) return new Set();
      const payload = JSON.parse(decodeURIComponent(cookie.split('=')[1] || '[]'));
      const ids = new Set();
      if (Array.isArray(payload)) {
        payload.forEach((item) => {
          if (item && item.id) ids.add(String(item.id));
        });
      } else if (payload && typeof payload === 'object') {
        Object.keys(payload).forEach((key) => {
          const line = payload[key];
          const id = line && (line.id || key);
          if (id) ids.add(String(id));
        });
      }
      return ids;
    } catch (_err) {
      return new Set();
    }
  }

  function updateNeedColumn() {
    if (!needNodes.popular) return;
    renderLinks(needNodes.popular, needDefaults.popular);
    renderLinks(needNodes.trending, needDefaults.trending);
    const history = readStorageArray(NEED_HISTORY_KEY);
    const allowPersonalization = canPersonalize() && history.length;
    if (!allowPersonalization) {
      if (needNodes.defaultBlock) needNodes.defaultBlock.hidden = false;
      if (needNodes.personalizedBlock) {
        needNodes.personalizedBlock.hidden = true;
        needNodes.personalizedBlock.setAttribute('aria-hidden', 'true');
      }
      return;
    }

    if (needNodes.defaultBlock) needNodes.defaultBlock.hidden = true;
    if (needNodes.personalizedBlock) {
      needNodes.personalizedBlock.hidden = false;
      needNodes.personalizedBlock.setAttribute('aria-hidden', 'false');
    }

    const continueItems = history.slice(0, 3);
    renderLinks(needNodes.continueList, continueItems);
    const recommendedPool = needDefaults.popular.concat(needDefaults.trending);
    const recommended = recommendedPool.filter(item => continueItems.every(entry => entry.slug !== item.slug)).slice(0, 3);
    renderLinks(needNodes.recommendedList, (recommended.length ? recommended : needDefaults.popular.slice(0, 3)));
  }

  function buildTherapyPopular() {
    const list = therapyNodes.popular;
    if (!list) return;
    const base = therapyDefaults.pinned.slice(0, 6);
    const pool = therapyDefaults.rotation.length ? therapyDefaults.rotation : therapyDefaults.pinned.slice(6);
    const seed = pool.length ? Math.floor(Date.now() / (1000 * 60 * 60 * 24)) : 0;
    const rotation = [];
    for (let i = 0; i < 2; i++) {
      if (!pool.length) break;
      rotation.push(pool[(seed + i) % pool.length]);
    }
    const combined = base.concat(rotation);
    renderLinks(list, combined, { cartIds: readCartIds(), showBasket: true });
  }

  function updateTherapyColumn() {
    buildTherapyPopular();
    const history = readStorageArray(THERAPY_HISTORY_KEY);
    const saved = readStorageArray(THERAPY_SAVED_KEY).slice(0, 4);
    const allowPersonalization = !!therapyNodes.personalizedBlock && canPersonalize() && (history.length || saved.length);
    if (!allowPersonalization) {
      if (therapyNodes.personalizedBlock) {
        therapyNodes.personalizedBlock.hidden = true;
        therapyNodes.personalizedBlock.setAttribute('aria-hidden', 'true');
      }
      return;
    }

    if (therapyNodes.personalizedBlock) {
      therapyNodes.personalizedBlock.hidden = false;
      therapyNodes.personalizedBlock.setAttribute('aria-hidden', 'false');
    }

    const cartIds = readCartIds();
    renderLinks(therapyNodes.recentList, history.slice(0, 4), { cartIds, showBasket: true, fallback: 'No history yet' });
    renderLinks(therapyNodes.savedList, saved.slice(0, 4), { cartIds, showBasket: true, fallback: 'No saved therapies' });
  }

  function runAll(){
    updateNeedColumn();
    updateTherapyColumn();
  }

  document.addEventListener('wow:cookie-preferences', runAll);
  document.addEventListener('wow:need-history', runAll);
  document.addEventListener('wow:therapy-history', runAll);
  document.addEventListener('wow:saved-therapies', runAll);
  document.addEventListener('wow:cart-updated', runAll);
  window.addEventListener('storage', runAll);
  window.addEventListener('focus', runAll);
  runAll();
})();
</script>
@stack('scripts')

</body>
</html>
