const backendUrl = String(import.meta.env.VITE_BACKEND_URL || '').replace(/\/$/, '');
const consentEndpoint = `${backendUrl}/api/behaviour/consent`;
const eventsEndpoint = `${backendUrl}/api/behaviour/events`;
const preferencesKey = 'wow_cookie_preferences';
const consentVersion = '1';

let personalisationEnabled = false;
let pendingEvents = [];
let flushTimer = null;

function uuid() {
  if (window.crypto?.randomUUID) return window.crypto.randomUUID();
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
    const value = Math.floor(Math.random() * 16);
    const nibble = character === 'x' ? value : ((value & 0x3) | 0x8);
    return nibble.toString(16);
  });
}

function readPreferences() {
  try {
    const value = JSON.parse(window.localStorage?.getItem(preferencesKey) || 'null');
    return value && value.version === 1 ? value : null;
  } catch (_) {
    return null;
  }
}

function csrfToken() {
  const token = document.cookie
    .split('; ')
    .find((entry) => entry.startsWith('XSRF-TOKEN='))
    ?.split('=')
    .slice(1)
    .join('=');

  return token ? decodeURIComponent(token) : '';
}

async function updateConsent(enabled) {
  if (!backendUrl) return false;

  const response = await fetch(consentEndpoint, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(csrfToken() ? { 'X-XSRF-TOKEN': csrfToken() } : {}),
    },
    body: JSON.stringify({ enabled, consent_version: consentVersion }),
  });

  if (!response.ok) throw new Error(`Behaviour consent failed: ${response.status}`);
  personalisationEnabled = enabled;
  return true;
}

async function syncConsent(preferences) {
  if (!preferences) return;

  try {
    await updateConsent(preferences.personalization === true);
    if (personalisationEnabled) collectOfferingImpressions();
  } catch (error) {
    console.warn('[WOW] Behaviour consent could not be synchronised.', error);
  }
}

function offeringReference(element) {
  const idValue = element?.dataset?.productId || element?.dataset?.id || '';
  const rawId = String(idValue).replace(/^store-/, '');
  const id = Number.parseInt(rawId, 10);
  if (!Number.isInteger(id) || id < 1) return null;

  const version = String(element?.dataset?.sourceVersion || '').toLowerCase();
  return {
    source: version === 'store' || String(idValue).startsWith('store-')
      ? 'store'
      : (version === 'v3' ? 'v3' : 'legacy'),
    id,
  };
}

function queueEvent(eventType, details = {}) {
  if (!personalisationEnabled) return;

  pendingEvents.push({
    event_uuid: uuid(),
    event_type: eventType,
    occurred_at: new Date().toISOString(),
    page_context: window.location.pathname.slice(0, 255),
    ...details,
  });

  if (pendingEvents.length >= 10) {
    void flushEvents();
    return;
  }

  function pageMetadata() {
    const path = window.location.pathname.toLowerCase();
    let pageType = 'page';
    if (path === '/' || path === '') pageType = 'homepage';
    else if (/\/(search|find|results)/.test(path)) pageType = 'search';
    else if (/\/(offering|product|store)\//.test(path)) pageType = 'product';
    else if (/\/(therapies|classes|events|retreats|workshops|courses|readings|categories|locations)/.test(path)) pageType = 'category';
    else if (/\/(about|contact|privacy|terms|cookies)/.test(path)) pageType = 'landing';

    return {
      page_type: pageType,
      page_title: document.title.slice(0, 255),
      href: window.location.href.slice(0, 2048),
      device_class: window.matchMedia('(max-width: 767px)').matches
        ? 'mobile'
        : (window.matchMedia('(max-width: 1024px)').matches ? 'tablet' : 'desktop'),
      viewport_width: window.innerWidth,
      viewport_height: window.innerHeight,
    };
  }

  function categoryContext() {
    const path = window.location.pathname.split('/').filter(Boolean);
    return path.length ? path[0].slice(0, 255) : null;
  }

  window.clearTimeout(flushTimer);
  flushTimer = window.setTimeout(() => { void flushEvents(); }, 1500);
}

async function flushEvents() {
  if (!personalisationEnabled || !pendingEvents.length || !backendUrl) return;

  const events = pendingEvents.splice(0, 50);
  try {
    const response = await fetch(eventsEndpoint, {
      method: 'POST',
      credentials: 'include',
      keepalive: true,
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ events }),
    });
    if (!response.ok) throw new Error(`Behaviour events failed: ${response.status}`);
  } catch (error) {
    pendingEvents.unshift(...events);
    console.warn('[WOW] Behaviour events could not be delivered.', error);
  }
}

function collectOfferingImpressions() {
  const seen = new Set();
  document.querySelectorAll('[data-product-id], [data-id]').forEach((element) => {
    const offering = offeringReference(element);
    if (!offering) return;
    const key = `${offering.source}:${offering.id}`;
    if (seen.has(key)) return;
    seen.add(key);
    queueEvent('offering_impression', { offering });
  });
}

function installInteractions() {
  const metadata = pageMetadata();
  queueEvent('page_view', { metadata });
  if (metadata.page_type === 'category') {
    queueEvent('category_view', { metadata: { ...metadata, category: categoryContext() } });
  } else if (metadata.page_type === 'product') {
    queueEvent('product_view', { metadata });
  } else if (metadata.page_type === 'landing') {
    queueEvent('landing_page_view', { metadata });
  }

  document.addEventListener('click', (event) => {
    const clicked = event.target instanceof Element ? event.target : null;
    const element = clicked?.closest('[data-product-id], [data-id]');
    const offering = offeringReference(element);
    const target = clicked?.closest('a,button,[role="button"],input,select,textarea');
    if (offering) {
      queueEvent('offering_opened', {
        offering,
        metadata: { ...pageMetadata(), interaction: target?.tagName?.toLowerCase() || 'offering' },
      });
    } else if (target) {
      const label = String(target.getAttribute('aria-label') || target.textContent || target.getAttribute('name') || '').trim();
      queueEvent('page_interaction', {
        metadata: {
          ...pageMetadata(),
          interaction: target.tagName.toLowerCase(),
          target: label.slice(0, 255),
          href: target.getAttribute('href') || window.location.href,
        },
      });
    }
  }, true);

  document.addEventListener('submit', (event) => {
    if (!(event.target instanceof HTMLFormElement)) return;
    if (event.target.matches('[role="search"], form[id*="search"]')) {
      queueEvent('search_performed', { metadata: pageMetadata() });
    } else {
      queueEvent('page_interaction', {
        metadata: { ...pageMetadata(), interaction: 'form_submit', target: event.target.getAttribute('id') || 'form' },
      });
    }
  }, true);

  document.addEventListener('change', (event) => {
    const target = event.target instanceof HTMLElement ? event.target : null;
    if (!target) return;
    queueEvent('page_interaction', {
      metadata: { ...pageMetadata(), interaction: 'field_change', target: target.getAttribute('name') || target.id || target.tagName.toLowerCase() },
    });
  }, true);

  let maxScroll = 0;
  window.addEventListener('scroll', () => {
    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
    if (scrollable <= 0) return;
    const percent = Math.round((window.scrollY / scrollable) * 100);
    if (percent >= maxScroll + 25) {
      maxScroll = percent;
      queueEvent('page_interaction', { metadata: { ...pageMetadata(), interaction: 'scroll', target: `${percent}%` } });
    }
  }, { passive: true });

  const observer = new MutationObserver(() => collectOfferingImpressions());
  observer.observe(document.body, { childList: true, subtree: true });
  window.addEventListener('pagehide', () => { void flushEvents(); });
}

export function installBehaviourTelemetry() {
  if (!backendUrl || typeof window === 'undefined') return;

  document.addEventListener('wow:cookie-preferences', (event) => {
    void syncConsent(event.detail || readPreferences());
  });
  installInteractions();

  const preferences = readPreferences();
  if (preferences?.personalization === true) {
    void syncConsent(preferences);
  }
}
