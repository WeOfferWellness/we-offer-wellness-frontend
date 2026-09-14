function onReady(callback) {
  if (typeof document === 'undefined') return;
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback, { once: true });
    return;
  }
  callback();
}

function buildUrl(base, params = {}) {
  const url = new URL(base, window.location.origin);
  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined || value === '') return;
    url.searchParams.set(key, String(value));
  });
  return url.toString();
}

async function fetchHtml(url, signal) {
  const result = await fetchHtmlResponse(url, signal);
  return result.html;
}

async function fetchHtmlResponse(url, signal) {
  const res = await fetch(url, {
    cache: 'no-store',
    credentials: 'same-origin',
    headers: {
      Accept: 'text/html',
      'X-Requested-With': 'XMLHttpRequest',
    },
    signal,
  });

  if (!res.ok) {
    throw new Error(`Request failed: ${res.status}`);
  }

  return { response: res, html: await res.text() };
}

function waitForHtmlImages(html, signal) {
  if (typeof document === 'undefined') {
    return Promise.resolve();
  }

  const template = document.createElement('template');
  template.innerHTML = html;

  const uniqueUrls = [...new Set(
    Array.from(template.content.querySelectorAll('img'))
      .map((img) => img.getAttribute('src') || img.currentSrc || '')
      .filter((src) => typeof src === 'string' && src.trim() !== '')
  )];

  if (!uniqueUrls.length) {
    return Promise.resolve();
  }

  // Card markup must never remain hidden behind skeletons because one remote
  // image is slow, blocked, or malformed. Images continue loading normally
  // after the real cards are inserted.
  return Promise.all(uniqueUrls.map((src) => new Promise((resolve) => {
    if (signal?.aborted) {
      resolve();
      return;
    }

    const image = new Image();
    let settled = false;

    const finish = () => {
      if (settled) return;
      settled = true;
      resolve();
    };

    image.onload = finish;
    image.onerror = finish;
    image.src = src;

    window.setTimeout(finish, 1500);

    if (image.complete) {
      finish();
    }
  })));
}

async function swapHtmlWhenReady(list, loadingHtml, html, signal) {
  const trimmed = typeof html === 'string' ? html.trim() : '';
  if (!trimmed) {
    list.innerHTML = loadingHtml;
    return;
  }

  if (signal?.aborted) return;
  list.innerHTML = trimmed;
}

async function appendHtmlWhenReady(list, html, signal) {
  const trimmed = typeof html === 'string' ? html.trim() : '';
  if (!trimmed) {
    return;
  }

  if (signal?.aborted) return;
  list.insertAdjacentHTML('beforeend', trimmed);
}

function setupScrollableRail(list, prev, next) {
  if (!list) return;

  function scrollByStep(dir) {
    const step = Math.max(280, Math.floor(list.clientWidth * 0.9));
    list.scrollBy({ left: dir * step, behavior: 'smooth' });
  }

  if (prev) prev.addEventListener('click', () => scrollByStep(-1));
  if (next) next.addEventListener('click', () => scrollByStep(1));
}

function countRootElements(html) {
  if (typeof document === 'undefined') return 0;
  const template = document.createElement('template');
  template.innerHTML = typeof html === 'string' ? html.trim() : '';
  return template.content.children.length;
}

function initPaginatedRail({ sectionId, listId, endpoint, params, prevId, nextId, emptyHtml }) {
  const section = document.getElementById(sectionId);
  const list = document.getElementById(listId);
  if (!section || !list) return;

  const loadingHtml = list.innerHTML;
  const prev = prevId ? document.getElementById(prevId) : null;
  const next = nextId ? document.getElementById(nextId) : null;
  const pageSize = Math.max(1, Number(list.dataset.railPageSize || 12) || 12);
  const hasLoadMore = String(list.dataset.railLoadMore || '0') === '1';
  let currentPage = 0;
  let hasMore = hasLoadMore;
  let loading = false;
  let controller = null;

  function setBusy(isBusy) {
    list.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    list.dataset.loadingState = isBusy ? 'true' : 'false';
    if (next) next.disabled = isBusy;
  }

  function scrollByStep(dir) {
    const amount = Math.min(900, list.clientWidth * 0.9);
    try {
      list.scrollBy({ left: dir * amount, behavior: 'smooth' });
    } catch {
      list.scrollLeft += dir * amount;
    }
  }

  function shouldLoadMoreOnNextClick() {
    if (!hasMore || loading) return false;
    const amount = Math.min(900, list.clientWidth * 0.9);
    const remaining = list.scrollWidth - list.scrollLeft - list.clientWidth;
    return remaining <= (amount + 48);
  }

  async function loadPage(page, { append = false } = {}) {
    if (loading) return false;
    loading = true;
    setBusy(true);

    const requestController = new AbortController();
    if (controller) {
      controller.abort();
    }
    controller = requestController;

    try {
      const url = buildUrl(endpoint, { ...params, page, limit: pageSize });
      const { response, html } = await fetchHtmlResponse(url, requestController.signal);
      if (requestController.signal.aborted) {
        return false;
      }

      const trimmed = typeof html === 'string' ? html.trim() : '';
      if (!trimmed) {
        if (!append) {
          list.innerHTML = emptyHtml;
        }
        hasMore = false;
        currentPage = Math.max(0, page - 1);
        list.dataset.hasMore = '0';
        list.dataset.page = String(currentPage);
        return false;
      }

      if (append) {
        await appendHtmlWhenReady(list, trimmed, requestController.signal);
      } else {
        await swapHtmlWhenReady(list, loadingHtml, trimmed, requestController.signal);
      }

      if (requestController.signal.aborted) {
        return false;
      }

      currentPage = page;
      const headerHasMore = response.headers.get('X-Has-More');
      if (headerHasMore !== null) {
        hasMore = !['0', 'false', 'no'].includes(String(headerHasMore).toLowerCase().trim());
      } else {
        hasMore = countRootElements(trimmed) >= pageSize;
      }

      list.dataset.page = String(currentPage);
      list.dataset.hasMore = hasMore ? '1' : '0';
      return true;
    } catch (error) {
      if (error && error.name === 'AbortError') {
        return false;
      }
      if (!append) {
        list.innerHTML = loadingHtml;
      }
      hasMore = false;
      list.dataset.hasMore = '0';
      return false;
    } finally {
      if (!requestController.signal.aborted) {
        loading = false;
        setBusy(false);
      }
    }
  }

  async function handleNextClick() {
    if (loading && currentPage === 0) return;
    if (shouldLoadMoreOnNextClick()) {
      const appended = await loadPage(currentPage + 1, { append: true });
      if (appended) {
        scrollByStep(1);
        return;
      }
    }
    scrollByStep(1);
  }

  if (prev) prev.addEventListener('click', () => scrollByStep(-1));
  if (next) next.addEventListener('click', (event) => {
    event.preventDefault();
    void handleNextClick();
  });

  void loadPage(1, { append: false });
}

function initStaticRail({ sectionId, listId, endpoint, params, prevId, nextId, emptyHtml }) {
  const section = document.getElementById(sectionId);
  const list = document.getElementById(listId);
  if (!section || !list) return;

  const loadingHtml = list.innerHTML;
  const prev = prevId ? document.getElementById(prevId) : null;
  const next = nextId ? document.getElementById(nextId) : null;

  setupScrollableRail(list, prev, next);
  list.setAttribute('aria-busy', 'true');
  list.dataset.loadingState = 'true';

  fetchHtml(buildUrl(endpoint, params))
    .then((html) => swapHtmlWhenReady(list, loadingHtml, html))
    .catch(() => {
      list.innerHTML = loadingHtml;
    })
    .finally(() => {
      list.removeAttribute('aria-busy');
      list.dataset.loadingState = 'false';
      if (!list.innerHTML.trim()) {
        list.innerHTML = loadingHtml;
      }
    });
}

function initComfortRail() {
  const section = document.getElementById('comfort-section');
  const list = document.getElementById('comfort-cards');
  if (!section || !list) return;

  const cta = document.getElementById('comfort-cta');
  const ctaLabel = cta ? cta.querySelector('.btn-label') : null;
  const loadingHtml = list.innerHTML;
  const prev = document.getElementById('comfort-prev');
  const next = document.getElementById('comfort-next');
  const priceButtons = Array.from(section.querySelectorAll('[data-price]'));
  const groupButtons = Array.from(section.querySelectorAll('[data-group]'));
  const cache = new Map();
  let currentPrice = 50;
  let currentGroup = 'solo';
  let controller = null;

  setupScrollableRail(list, prev, next);

  function cacheKey() {
    return `${currentPrice}|${currentGroup}`;
  }

  function setActiveButton(buttons, active) {
    buttons.forEach((button) => {
      const isActive = button === active;
      button.classList.toggle('active', isActive);
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
  }

  function updateCta() {
    if (!cta) return;
    cta.href = `/search?price_max=${encodeURIComponent(currentPrice)}&format=online&group_type=${encodeURIComponent(currentGroup)}`;
    if (ctaLabel) {
      ctaLabel.textContent = `See all under £${currentPrice} (${currentGroup})`;
    }
  }

  function showLoading() {
    list.setAttribute('aria-busy', 'true');
    list.dataset.loadingState = 'true';
    list.innerHTML = loadingHtml;
  }

  function renderHtml(html) {
    const trimmed = typeof html === 'string' ? html.trim() : '';
    if (!trimmed) {
      list.innerHTML = loadingHtml;
      return Promise.resolve();
    }
    const activeController = controller;
    return swapHtmlWhenReady(list, loadingHtml, trimmed, activeController?.signal)
      .catch(() => {
        list.innerHTML = loadingHtml;
      });
  }

  function fetchComfort() {
    const key = cacheKey();
    if (cache.has(key)) {
      renderHtml(cache.get(key) || '')
        .finally(() => {
          list.removeAttribute('aria-busy');
          list.dataset.loadingState = 'false';
        });
      return;
    }

    const requestController = new AbortController();
    if (controller) {
      controller.abort();
    }
    controller = requestController;
    showLoading();

    const url = buildUrl('/api/home/rails', {
      section: 'comfort',
      price_max: currentPrice,
      group_type: currentGroup,
      mode: 'online',
      limit: 12,
    });

    fetchHtml(url, requestController.signal)
      .then((html) => {
        if (requestController.signal.aborted) {
          return;
        }
        cache.set(key, html);
        return renderHtml(html);
      })
      .catch((error) => {
        if (error && error.name === 'AbortError') {
          return;
        }
        list.innerHTML = loadingHtml;
      })
      .finally(() => {
        if (requestController.signal.aborted) {
          return;
        }
        list.removeAttribute('aria-busy');
        list.dataset.loadingState = 'false';
      });
  }

  priceButtons.forEach((button) => {
    button.addEventListener('click', () => {
      currentPrice = parseInt(button.getAttribute('data-price') || '50', 10) || 50;
      setActiveButton(priceButtons, button);
      updateCta();
      fetchComfort();
    });
  });

  groupButtons.forEach((button) => {
    button.addEventListener('click', () => {
      currentGroup = button.getAttribute('data-group') || 'solo';
      setActiveButton(groupButtons, button);
      updateCta();
      fetchComfort();
    });
  });

  updateCta();
  fetchComfort();
}

onReady(() => {
  initStaticRail({
    sectionId: 'home-latest-catalogue',
    listId: 'home-latest-catalogue-cards',
    endpoint: '/api/home/rails',
    params: { section: 'latest' },
    prevId: 'latest-prev',
    nextId: 'latest-next',
    emptyHtml: 'No latest catalogue items are available right now.',
  });

  initPaginatedRail({
    sectionId: 'home-gifts',
    listId: 'home-gifts-cards',
    endpoint: '/api/home/rails',
    params: { section: 'gifts' },
    prevId: 'gifts-prev',
    nextId: 'gifts-next',
    emptyHtml: 'No gifts found under £50 right now. <a class="link-wow" href="/gifts">Browse all gifts</a>.',
  });

  initComfortRail();
});
