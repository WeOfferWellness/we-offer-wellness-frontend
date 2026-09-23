const backendUrl = String(import.meta.env.VITE_BACKEND_URL || 'https://studio.weofferwellness.co.uk').replace(/\/$/, '');
const preferencesKey = 'wow_cookie_preferences';
const consentEndpoint = `${backendUrl}/api/behaviour/consent`;
const eventsEndpoint = `${backendUrl}/api/behaviour/events`;
const cardSelector = '[data-product-id][data-source-version], .offering-card[data-product-id]';
const meaningfulPercent = Number(import.meta.env.VITE_BEHAVIOUR_MEANINGFUL_VISIBILITY_PERCENT || 60);
const minimumVisibleMs = Number(import.meta.env.VITE_BEHAVIOUR_MINIMUM_VISIBLE_MS || 900);

let enabled = false;
let pending = [];
let flushTimer;
let sequence = 0;
let visibilityInstalled = false;
const cards = new WeakMap();
const hoverTimers = new WeakMap();
let lastSearchContextKey = '';
let consentSyncPromise = null;

const uuid = () => window.crypto?.randomUUID?.() || 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
  const n = Math.floor(Math.random() * 16);
  return (c === 'x' ? n : ((n & 3) | 8)).toString(16);
});

function preferences() {
  try { return JSON.parse(window.localStorage?.getItem(preferencesKey) || 'null'); } catch (_) { return null; }
}

function offeringFor(element) {
  const id = Number.parseInt(String(element?.dataset?.productId || ''), 10);
  if (!Number.isInteger(id) || id < 1) return null;
  const version = String(element.dataset.sourceVersion || '').toLowerCase();
  return { source: version === 'store' ? 'store' : (version === 'v3' ? 'v3' : 'legacy'), id };
}

function rankingFor(element) {
  const id = String(element?.dataset?.rankingRequestId || '');
  return /^[0-9a-f]{8}-[0-9a-f-]{27,}$/i.test(id) ? id : null;
}

function pageMetadata() {
  const path = window.location.pathname;
  const segments = path.split('/').filter(Boolean);
  return {
    surface: path.startsWith('/search') ? 'search' : 'marketplace',
    page_type: segments[0] || 'home',
    category: path.startsWith('/therapies/') ? (segments[1] || null) : null,
    device_class: window.matchMedia('(max-width: 767px)').matches ? 'mobile' : (window.matchMedia('(max-width: 1024px)').matches ? 'tablet' : 'desktop'),
  };
}

function searchContext() {
  const params = new URLSearchParams(window.location.search);
  const path = window.location.pathname;
  const segments = path.split('/').filter(Boolean);
  const context = {};
  const category = params.get('category') || (segments[0] === 'therapies' && segments[1] ? segments[1] : '');
  const type = params.get('type') || (['classes', 'events', 'workshops', 'retreats'].includes(segments[0]) ? segments[0].replace(/s$/, '') : '');
  const mode = params.get('mode') || params.get('format') || (segments[0] === 'online' ? 'online' : '');
  const minPrice = Number(params.get('min_price') || params.get('price_min'));
  const maxPrice = Number(params.get('max_price') || params.get('price_max'));
  if (category) context.category_slug = category.slice(0, 100);
  if (type) context.type_slug = type.slice(0, 100);
  if (mode) context.delivery_mode = mode === 'online' ? 'online' : 'in_person';
  if (Number.isFinite(minPrice) && minPrice > 0) context.price_min = minPrice;
  if (Number.isFinite(maxPrice) && maxPrice > 0) context.price_max = maxPrice;
  if (path.startsWith('/locations')) {
    const searchedLocation = params.get('place') || params.get('where') || '';
    const searchedWhat = params.get('what') || '';
    if (searchedLocation) context.location_query = searchedLocation.slice(0, 100);
    if (searchedWhat && !context.category_slug) context.category_slug = searchedWhat.slice(0, 100);
  }
  return context;
}

function queueCurrentSearch() {
  const isSearchSurface = window.location.pathname.startsWith('/search') || window.location.pathname.startsWith('/locations');
  if (!enabled || !isSearchSurface) return;
  const key = `${window.location.pathname}?${new URLSearchParams(window.location.search).toString()}`;
  if (key === lastSearchContextKey) return;
  lastSearchContextKey = key;
  queue('search_performed', { search_context: searchContext(), metadata: pageMetadata() });
}

function queue(eventType, details = {}) {
  if (!enabled) return;
  pending.push({ event_uuid: uuid(), event_type: eventType, occurred_at: new Date().toISOString(), sequence: ++sequence, page_context: window.location.pathname.slice(0, 255), ...details });
  window.clearTimeout(flushTimer);
  if (pending.length >= 10) void flush();
  else flushTimer = window.setTimeout(() => { void flush(); }, 3000);
}

async function flush() {
  if (!enabled || !pending.length || !backendUrl) return;
  const events = pending.splice(0, 50);
  try {
    const response = await fetch(eventsEndpoint, { method: 'POST', credentials: 'include', keepalive: true, headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ events }) });
    if (!response.ok) throw new Error(String(response.status));
  } catch (_) {
    // Telemetry is never allowed to affect browsing; retain only this page's small batch.
    pending.unshift(...events);
  }
}

function elapsed(state, now = performance.now()) {
  if (!state.activeSince || document.hidden) return 0;
  return Math.min(now - state.activeSince, 30000);
}

function finishCard(element, state, reason) {
  const active = elapsed(state);
  state.visibleMs += active;
  state.focusedMs += active;
  state.activeSince = 0;
  const meaningful = state.visibleMs >= minimumVisibleMs && state.maxPercent >= meaningfulPercent;
  if (!meaningful || state.sent) return;
  state.sent = true;
  const visibility = { visible_ms: Math.round(state.visibleMs), focused_ms: Math.round(state.focusedMs), max_percent: Math.round(state.maxPercent), viewport_entries: state.entries, revisits: Math.max(0, state.entries - 1), scroll_backs: state.scrollBacks, scroll_speed: state.speed };
  queue('offering_meaningful_view', { offering: offeringFor(element), ranking_request_id: rankingFor(element), visibility, metadata: { ...pageMetadata(), reason } });
  if (state.visibleMs >= 2500 || state.scrollBacks) queue('offering_linger', { offering: offeringFor(element), ranking_request_id: rankingFor(element), visibility, metadata: pageMetadata() });
}

function installVisibility() {
  if (visibilityInstalled) return;
  visibilityInstalled = true;
  let lastY = window.scrollY;
  let lastAt = performance.now();
  let direction = 0;
  window.addEventListener('scroll', () => {
    const now = performance.now(); const delta = window.scrollY - lastY; const velocity = Math.abs(delta) / Math.max(1, now - lastAt);
    const nextDirection = Math.sign(delta);
    document.querySelectorAll(cardSelector).forEach((element) => {
      const state = cards.get(element);
      if (!state?.activeSince) return;
      if (nextDirection && direction && nextDirection !== direction) state.scrollBacks += 1;
      state.speed = velocity > 2 ? 'fast' : (velocity < 0.25 ? 'slow' : 'normal');
    });
    if (nextDirection) direction = nextDirection;
    lastY = window.scrollY; lastAt = now;
  }, { passive: true });

  const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
    const element = entry.target; const state = cards.get(element); if (!state) return;
    const percent = entry.intersectionRatio * 100;
    state.maxPercent = Math.max(state.maxPercent, percent);
    if (percent >= meaningfulPercent && !document.hidden) {
      if (!state.activeSince) {
        state.activeSince = performance.now();
        state.entries += 1;
        if (state.entries > 1) queue('offering_revisited', { offering: offeringFor(element), ranking_request_id: rankingFor(element), metadata: pageMetadata() });
      }
    } else if (state.activeSince) {
      const wasFastPass = state.visibleMs + elapsed(state) < minimumVisibleMs && state.speed === 'fast';
      finishCard(element, state, 'viewport_exit');
      if (wasFastPass && !state.fastSkipSent) {
        state.fastSkipSent = true;
        queue('fast_skip', { offering: offeringFor(element), ranking_request_id: rankingFor(element), metadata: pageMetadata() });
      }
    }
  }), { threshold: [0, 0.6, 1] });

  const observe = () => document.querySelectorAll(cardSelector).forEach((element) => {
    if (cards.has(element) || !offeringFor(element)) return;
    cards.set(element, { visibleMs: 0, focusedMs: 0, maxPercent: 0, entries: 0, scrollBacks: 0, speed: 'normal', activeSince: 0, sent: false, fastSkipSent: false });
    observer.observe(element);
    queue('offering_impression', { offering: offeringFor(element), metadata: pageMetadata() });
  });
  observe();
  new MutationObserver(observe).observe(document.body, { childList: true, subtree: true });
  document.addEventListener('visibilitychange', () => document.querySelectorAll(cardSelector).forEach((element) => {
    const state = cards.get(element); if (!state) return;
    if (document.hidden && state.activeSince) finishCard(element, state, 'page_hidden');
    if (!document.hidden && state.maxPercent >= meaningfulPercent) state.activeSince = performance.now();
  }));
}

async function syncConsent(value) {
  const allowed = value?.personalization === true;
  if (!backendUrl) return;
  if (consentSyncPromise) return consentSyncPromise;
  consentSyncPromise = (async () => {
  try {
    const response = await fetch(consentEndpoint, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ enabled: allowed, consent_version: '1' }) });
    if (!response.ok) throw new Error(String(response.status));
    enabled = allowed;
    if (enabled) { queue('page_view', { search_context: searchContext(), metadata: pageMetadata() }); queueCurrentSearch(); installVisibility(); }
    else pending = [];
  } catch (_) { enabled = false; pending = []; }
  })().finally(() => { consentSyncPromise = null; });
  return consentSyncPromise;
}

export function installBehaviourTelemetry() {
  if (!backendUrl || typeof window === 'undefined' || window.__wowBehaviourTelemetryInstalled) return;
  window.__wowBehaviourTelemetryInstalled = true;
  document.addEventListener('wow:cookie-preferences', (event) => { void syncConsent(event.detail || preferences()); });
  window.addEventListener('wow:location-updated', (event) => {
    const location = event.detail?.location;
    if (!location || typeof location !== 'object') return;
    queue('location_changed', {
      metadata: { ...pageMetadata(), location },
    });
  });
  if (preferences()?.personalization === true) void syncConsent(preferences());
  window.addEventListener('wow:search-results-loaded', queueCurrentSearch);
  document.addEventListener('click', (event) => {
    const element = event.target instanceof Element ? event.target.closest(cardSelector) : null;
    if (element && offeringFor(element)) queue('offering_opened', { offering: offeringFor(element), ranking_request_id: rankingFor(element), metadata: pageMetadata() });
  }, true);
  // Desktop hover is attention, not intent by itself. A short qualifying delay
  // stops fly-over mouse movement becoming behavioural data.
  if (window.matchMedia?.('(hover: hover) and (pointer: fine)').matches) {
    document.addEventListener('pointerover', (event) => {
      const element = event.target instanceof Element ? event.target.closest(cardSelector) : null;
      if (!element || !offeringFor(element) || hoverTimers.has(element)) return;
      const timer = window.setTimeout(() => {
        hoverTimers.delete(element);
        const state = cards.get(element);
        if (state?.hoverSent) return;
        if (state) state.hoverSent = true;
        queue('offering_focus', { offering: offeringFor(element), ranking_request_id: rankingFor(element), metadata: { ...pageMetadata(), interaction: 'desktop_hover' } });
      }, 650);
      hoverTimers.set(element, timer);
    }, true);
    document.addEventListener('pointerout', (event) => {
      const element = event.target instanceof Element ? event.target.closest(cardSelector) : null;
      if (!element) return;
      const timer = hoverTimers.get(element);
      if (timer) { window.clearTimeout(timer); hoverTimers.delete(element); }
    }, true);
  }
  window.addEventListener('pagehide', () => { void flush(); });
}
