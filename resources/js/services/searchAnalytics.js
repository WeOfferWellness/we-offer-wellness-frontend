// Search reporting belongs to Studio.  VITE_BACKEND_URL is shared by a number
// of frontend integrations and currently points at the AtEase application, so
// using it here silently sent search telemetry to the wrong host.
const studioOrigin = String(
  import.meta.env.VITE_SEARCH_EVENTS_ORIGIN || 'https://studio.weofferwellness.co.uk',
).replace(/\/$/, '');
const endpoint = `${studioOrigin}/api/search-events`;
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
    if (navigator.sendBeacon(endpoint, body)) return Promise.resolve(true);
  }

  return fetch(endpoint, {
    method: 'POST',
    credentials: 'include',
    keepalive: true,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload),
  }).then((response) => response.ok).catch(() => false);
}

function recordSearch({ searchTerm = '', locationQuery = '', source = 'site-search' } = {}) {
  const cleanSearchTerm = String(searchTerm || '').trim();
  const cleanLocationQuery = String(locationQuery || '').trim();

  if (!cleanSearchTerm && !cleanLocationQuery) return null;

  const payload = {
    event_uuid: searchEventId(),
    search_term: cleanSearchTerm || null,
    // Keep an intentionally empty location as null. This makes an omitted
    // location distinct from a browser/IP estimate in the reporting view.
    location_query: cleanLocationQuery || null,
    source: String(source || 'site-search').slice(0, 80),
    device_type: window.matchMedia('(max-width: 767px)').matches ? 'mobile' : 'desktop',
    session_id: searchSessionId(),
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
  };

  window.sessionStorage?.setItem(pendingSearchKey, JSON.stringify(payload));

  // Beacon delivery survives the search handler's immediate page navigation.
  void postSearchEvent(payload, { duringNavigation: true });
  return payload;
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

  return recordSearch({ searchTerm, locationQuery, source: sourceFor(form) });
}

export function logSearchValues(values) {
  return recordSearch(values);
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
    if (pending?.event_uuid) {
      void postSearchEvent({ ...pending, items_shown: count }).then((saved) => {
        if (!saved) return;
        const current = JSON.parse(window.sessionStorage?.getItem(pendingSearchKey) || 'null');
        if (current?.event_uuid === pending.event_uuid) window.sessionStorage?.removeItem(pendingSearchKey);
      });
    }
  } catch (_) {
    // Search behavior must remain independent from analytics.
  }
}

export function reportSearchResults(count) {
  const parsed = Number(count);
  if (Number.isInteger(parsed) && parsed >= 0) {
    updatePendingSearchWithResults(parsed);
    window.dispatchEvent(new CustomEvent('wow:search-results-loaded', { detail: { count: parsed } }));
  }
}

function logSearchFromCurrentUrl() {
  if (!window.location.pathname.startsWith('/search')) return;

  const params = new URLSearchParams(window.location.search);
  const searchTerm = String(params.get('what') || params.get('q') || '').trim();
  const locationQuery = String(params.get('where') || params.get('location') || '').trim();
  if (!searchTerm && !locationQuery) return;

  // A submit immediately before navigation already leaves this event in the
  // session queue. Only create a URL-derived event when there is no matching
  // pending event, so shared/bookmarked search URLs are still measurable
  // without duplicating normal form submissions.
  try {
    const pending = JSON.parse(window.sessionStorage?.getItem(pendingSearchKey) || 'null');
    if (pending?.search_term === (searchTerm || null)
      && pending?.location_query === (locationQuery || null)) return;
  } catch (_) {
    // A storage failure must not prevent the search page from rendering.
  }

  recordSearch({ searchTerm, locationQuery, source: 'search-page-direct' });
}

export function installSearchAnalytics() {
  document.addEventListener('submit', (event) => {
    // JavaScript search controls supply their selected values through the
    // explicit event below. They prevent the native submit and may not retain
    // their selection in a form input, so do not create a second/incomplete row.
    if (event.defaultPrevented) return;
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    const isSearchForm = form.matches('[role="search"]')
      || form.id.includes('search-form')
      || form.querySelector('[name="what"], #wowsearch-desktop-what, #wowsearch-mobile-what-input');

    if (isSearchForm) logSearchEvent(form);
  });

  window.addEventListener('wow:search-submitted', (event) => {
    const detail = event.detail || {};
    recordSearch({
      searchTerm: detail.searchTerm || detail.what,
      locationQuery: detail.locationQuery || detail.where,
      source: detail.source || 'site-search',
    });
  });

  const reportInitialResults = () => {
    const count = resultCount();
    if (count !== null) updatePendingSearchWithResults(count);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      logSearchFromCurrentUrl();
      reportInitialResults();
    }, { once: true });
  } else {
    logSearchFromCurrentUrl();
    reportInitialResults();
  }

  window.addEventListener('wow:searchbar-v4:results-updated', (event) => {
    reportSearchResults(event.detail?.count);
  });

  document.querySelectorAll('#wowMobileResultsCount, #searchResultsCount').forEach((element) => {
    const observer = new MutationObserver(reportInitialResults);
    observer.observe(element, { childList: true, characterData: true, subtree: true });
  });
}
