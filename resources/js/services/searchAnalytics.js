const endpoint = `${String(import.meta.env.VITE_BACKEND_URL || '').replace(/\/$/, '')}/api/search-events`;
const pendingSearchKey = 'wow_pending_search_event';

function searchEventId() {
  if (window.crypto?.randomUUID) return window.crypto.randomUUID();

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
    const value = Math.floor(Math.random() * 16);
    const nibble = character === 'x' ? value : ((value & 0x3) | 0x8);
    return nibble.toString(16);
  });
}

function searchSessionId() {
  const existing = window.sessionStorage?.getItem('wow_search_session');
  if (existing) return existing;

  const next = searchEventId();
  window.sessionStorage?.setItem('wow_search_session', next);
  return next;
}

function inputValue(form, selectors) {
  for (const selector of selectors) {
    const element = form.querySelector(selector) || document.querySelector(selector);
    const value = element?.value ?? element?.textContent;
    const clean = String(value || '').trim();
    if (clean && !['where', 'location', 'what are you looking for?'].includes(clean.toLowerCase())) return clean;
  }

  return '';
}

function sourceFor(form) {
  if (form.closest('#wowsearch-main-search-modal')) return 'header-modal';
  if (window.location.pathname.startsWith('/search')) return 'search-page';
  if (form.closest('.hero, [class*="hero"], [data-hero]')) return 'hero';
  return 'site-search';
}

function postSearchEvent(payload, { duringNavigation = false } = {}) {
  if (duringNavigation && navigator.sendBeacon) {
    const body = new Blob([JSON.stringify(payload)], { type: 'text/plain;charset=UTF-8' });
    if (navigator.sendBeacon(endpoint, body)) return Promise.resolve();
  }

  return fetch(endpoint, {
    method: 'POST',
    credentials: 'include',
    keepalive: true,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload),
  }).catch(() => {});
}

export function logSearchEvent(form) {
  const searchTerm = inputValue(form, [
    '[name="what"]',
    '[name="q"]',
    '#wowsearch-desktop-what',
    '#wowsearch-mobile-what-input',
    '#wowsearch-mobile-what-display',
  ]);
  const locationQuery = inputValue(form, [
    '[name="where"]',
    '#wowsearch-desktop-where',
    '#wowsearch-mobile-where-input',
    '#wowsearch-mobile-where-display',
  ]);

  if (!searchTerm && !locationQuery) return null;

  const payload = {
    event_uuid: searchEventId(),
    search_term: searchTerm || null,
    location_query: locationQuery || null,
    source: sourceFor(form),
    device_type: window.matchMedia('(max-width: 767px)').matches ? 'mobile' : 'desktop',
    session_id: searchSessionId(),
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
  };

  window.sessionStorage?.setItem(pendingSearchKey, JSON.stringify(payload));

  // Beacon delivery survives the search handler's immediate page navigation.
  void postSearchEvent(payload, { duringNavigation: true });
  return payload;
}

function resultCount() {
  const count = document.querySelector('#wowMobileResultsCount, #searchResultsCount')?.textContent || '';
  const match = String(count).replace(/,/g, '').match(/\d+/);
  return match ? Number(match[0]) : null;
}

function updatePendingSearchWithResults(count) {
  if (!window.location.pathname.startsWith('/search') || !Number.isInteger(count)) return;

  try {
    const pending = JSON.parse(window.sessionStorage?.getItem(pendingSearchKey) || 'null');
    if (pending?.event_uuid) void postSearchEvent({ ...pending, items_shown: count });
  } catch (_) {
    // Search behavior must remain independent from analytics.
  }
}

export function installSearchAnalytics() {
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    const isSearchForm = form.matches('[role="search"]')
      || form.id.includes('search-form')
      || form.querySelector('[name="what"], #wowsearch-desktop-what, #wowsearch-mobile-what-input');

    if (isSearchForm) logSearchEvent(form);
  }, true);

  const reportInitialResults = () => {
    const count = resultCount();
    if (count !== null) updatePendingSearchWithResults(count);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', reportInitialResults, { once: true });
  } else {
    reportInitialResults();
  }

  window.addEventListener('wow:searchbar-v4:results-updated', (event) => {
    const count = Number(event.detail?.count);
    if (Number.isInteger(count)) updatePendingSearchWithResults(count);
  });

  document.querySelectorAll('#wowMobileResultsCount, #searchResultsCount').forEach((element) => {
    const observer = new MutationObserver(reportInitialResults);
    observer.observe(element, { childList: true, characterData: true, subtree: true });
  });
}
