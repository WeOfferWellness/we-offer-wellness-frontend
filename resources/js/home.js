import { gsap } from 'gsap';
import { createApp } from 'vue';
import ui from '@nuxt/ui/vue-plugin';
import { initSubscriberForms } from './lib/subscriber-forms';
import SearchRangeCalendar from './Components/SearchRangeCalendar.vue';
import SearchBarV4 from './Components/SearchBarV4.vue';
import { fetchWhatCategories } from './services/whatCategories';
import { fetchLocations } from './services/locations';

function runIdle(fn) {
  try {
    if (typeof window !== 'undefined' && 'requestIdleCallback' in window) {
      window.requestIdleCallback(fn, { timeout: 300 });
      return;
    }
  } catch (_err) {}
  setTimeout(fn, 1);
}

function onDocumentReady(fn) {
  if (typeof document === 'undefined') return;
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn, { once: true });
    return;
  }
  fn();
}

// Minimal interactivity for header mega menu, mobile menu, and ultra search bar panes

function createHamburgerTimeline(button){
  if (!button || typeof window === 'undefined') return null;
  try {
    const timeline = gsap.timeline({ paused: true });
    timeline.set(button, { '--origin': 'right center' });
    timeline.to(button, {
      '--before-scale': 1,
      '--after-scale': 1,
      duration: 0.1,
      ease: 'power2.out'
    });
    timeline.to(button, {
      '--span-scale': 0,
      '--before-top': '12px',
      '--after-top': '12px',
      duration: 0.15,
      ease: 'power2.inOut'
    }, '<0.1');
    timeline.set(button, { '--origin': 'center center' });
    timeline.to(button, {
      '--before-rot': '45deg',
      '--after-rot': '-45deg',
      duration: 0.15,
      ease: 'power3.out'
    }, '>0.1');
    return timeline;
  } catch(_err) {
    return null;
  }
}

function setupHamburgerController(button){
  const timeline = createHamburgerTimeline(button);
  if (!timeline) return null;
  let state = false;
  const controller = {
    set(open){
      const next = Boolean(open);
      if (next === state) return;
      state = next;
      if (next) { timeline.play(); }
      else { timeline.reverse(); }
    },
    toggle(){ this.set(!state); },
    isOpen(){ return state; }
  };
  try {
    window.__WOWHamburger = controller;
    if (Array.isArray(window.__WOWHamburgerQueue) && window.__WOWHamburgerQueue.length) {
      window.__WOWHamburgerQueue.forEach((queuedState) => {
        try { controller.set(queuedState); } catch(_inner){}
      });
      window.__WOWHamburgerQueue = [];
    }
    window.dispatchEvent(new CustomEvent('wow:hamburger-ready', { detail: controller }));
  } catch(_){ }
  return controller;
}

function initMegaMenu() {
  const nav = document.getElementById('desktopNav');
  const layer = document.getElementById('megaLayer');
  const shell = document.getElementById('mega-panel');
  const arrow = document.getElementById('megaArrow');
  const track = document.getElementById('megaTrack');
  const underline = document.getElementById('navUnderline');
  const overlay = document.getElementById('mega-overlay');
  if (!nav || !layer || !shell || !arrow || !track) return;

  const navItems = Array.from(nav.querySelectorAll('.link-wow--nav'));
  const dropdownItems = navItems.filter((item) => item.dataset.megaMenu);
  const panes = Array.from(shell.querySelectorAll('.wow-mega-pane[data-menu]'));
  const menuOrder = panes.map((pane) => pane.dataset.menu);
  const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

  let activeMenu = null;
  let activeTrigger = null;
  let closeTimer = null;
  let hasOpenedOnce = false;

  const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
  const getPane = (name) => panes.find((pane) => pane.dataset.menu === name);
  const getTrigger = (name) => dropdownItems.find((item) => item.dataset.megaMenu === name);

  const getMegaWidth = () => {
    const maxWidth = parseFloat(window.getComputedStyle(layer).getPropertyValue('--mega-max-width')) || 1160;
    const edgeGap = parseFloat(window.getComputedStyle(layer).getPropertyValue('--mega-edge-gap')) || 18;
    return Math.min(maxWidth, window.innerWidth - edgeGap * 2);
  };

  const clearActiveItems = () => {
    navItems.forEach((item) => {
      item.classList.remove('is-active');
      item.setAttribute('aria-expanded', 'false');
    });
  };

  const updateUnderline = (item) => {
    if (!underline || !item) return;
    const navRect = nav.getBoundingClientRect();
    const itemRect = item.getBoundingClientRect();
    underline.style.opacity = '1';
    underline.style.width = `${Math.max(0, itemRect.width - 28)}px`;
    underline.style.transform = `translateX(${itemRect.left - navRect.left + 14}px)`;
  };

  const hideUnderline = () => {
    if (!underline) return;
    underline.style.opacity = '0';
    underline.style.width = '0';
  };

  const positionArrow = (item) => {
    const width = getMegaWidth();
    const itemRect = item.getBoundingClientRect();
    const shellLeft = (window.innerWidth - width) / 2;
    const itemCenter = itemRect.left + itemRect.width / 2;
    shell.style.width = `${width}px`;
    arrow.style.left = `${clamp(itemCenter - shellLeft, 32, width - 32)}px`;
  };

  const updateHeight = (pane) => {
    const styles = window.getComputedStyle(shell);
    const borderTop = parseFloat(styles.borderTopWidth) || 0;
    const borderBottom = parseFloat(styles.borderBottomWidth) || 0;
    shell.style.height = `${pane.scrollHeight + borderTop + borderBottom}px`;
  };

  const openMega = (menuName, focusContent = false) => {
    const trigger = getTrigger(menuName);
    const pane = getPane(menuName);
    if (!trigger || !pane) return;

    window.clearTimeout(closeTimer);
    clearActiveItems();
    trigger.classList.add('is-active');
    trigger.setAttribute('aria-expanded', 'true');
    activeTrigger = trigger;

    updateUnderline(trigger);
    positionArrow(trigger);
    updateHeight(pane);

    const index = menuOrder.indexOf(menuName);
    if (!hasOpenedOnce) {
      track.style.transition = 'none';
      track.style.transform = `translateX(-${index * 100}%)`;
      window.requestAnimationFrame(() => {
        track.style.transition = '';
      });
    } else {
      track.style.transform = `translateX(-${index * 100}%)`;
    }

    activeMenu = menuName;
    hasOpenedOnce = true;
    shell.classList.add('is-open');
    shell.setAttribute('aria-hidden', 'false');
    if (overlay) overlay.style.display = 'block';

    if (focusContent) {
      const first = pane.querySelector(focusableSelector);
      if (first) first.focus();
    }
  };

  const closeMega = (immediate = false, focusTrigger = false) => {
    const run = () => {
      activeMenu = null;
      clearActiveItems();
      hideUnderline();
      shell.classList.remove('is-open');
      shell.setAttribute('aria-hidden', 'true');
      if (overlay) overlay.style.display = 'none';
      hasOpenedOnce = false;
      if (focusTrigger && activeTrigger) activeTrigger.focus();
    };
    window.clearTimeout(closeTimer);
    if (immediate) {
      run();
    } else {
      closeTimer = window.setTimeout(run, 140);
    }
  };

  const cancelClose = () => window.clearTimeout(closeTimer);

  navItems.forEach((item, index) => {
    const menuName = item.dataset.megaMenu;
    item.setAttribute('aria-haspopup', menuName ? 'true' : 'false');
    item.setAttribute('aria-expanded', 'false');
    item.addEventListener('mouseenter', () => menuName ? openMega(menuName) : closeMega(true));
    item.addEventListener('focus', () => menuName ? openMega(menuName) : closeMega(true));
    item.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
        event.preventDefault();
        const nextIndex = event.key === 'ArrowRight'
          ? (index + 1) % navItems.length
          : (index - 1 + navItems.length) % navItems.length;
        navItems[nextIndex]?.focus();
      } else if ((event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') && menuName) {
        event.preventDefault();
        openMega(menuName, true);
      } else if (event.key === 'Escape') {
        event.preventDefault();
        closeMega(true, true);
      }
    });
  });

  nav.addEventListener('mouseenter', cancelClose);
  nav.addEventListener('mouseleave', () => closeMega());
  layer.addEventListener('mouseenter', cancelClose);
  layer.addEventListener('mouseleave', () => closeMega());

  shell.addEventListener('keydown', (event) => {
    if (!activeMenu) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closeMega(true, true);
      return;
    }
    if (!['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    const pane = getPane(activeMenu);
    const focusable = pane ? Array.from(pane.querySelectorAll(focusableSelector)) : [];
    if (!focusable.length) return;
    const currentIndex = focusable.indexOf(document.activeElement);
    let nextIndex = currentIndex;
    if (event.key === 'ArrowDown' || event.key === 'ArrowRight') nextIndex += 1;
    if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') nextIndex -= 1;
    if (event.key === 'Home') nextIndex = 0;
    if (event.key === 'End') nextIndex = focusable.length - 1;
    if (nextIndex < 0) nextIndex = focusable.length - 1;
    if (nextIndex >= focusable.length) nextIndex = 0;
    event.preventDefault();
    focusable[nextIndex]?.focus();
  });

  window.addEventListener('resize', () => {
    if (!activeMenu) return;
    const trigger = getTrigger(activeMenu);
    const pane = getPane(activeMenu);
    if (!trigger || !pane) return;
    positionArrow(trigger);
    updateHeight(pane);
    updateUnderline(trigger);
  });

  document.addEventListener('click', (event) => {
    if (!activeMenu) return;
    if (nav.contains(event.target) || shell.contains(event.target)) return;
    closeMega(true);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && activeMenu) closeMega(true, true);
  });
}

function initMobileMenu() {
  const toggle = document.querySelector('[data-wow-mobile-toggle]') || document.querySelector('header button[aria-label="Menu"]');
  if (!toggle || !toggle.classList.contains('hamburger')) return;
  setupHamburgerController(toggle);
}

function setupUltraSearchBar(prefix) {
  if (typeof window !== 'undefined' && window.setupUltraSearchBar && window.setupUltraSearchBar !== setupUltraSearchBar) {
    try { return window.setupUltraSearchBar(prefix); } catch (_) {}
  }
  // Find the specific ultra-search container for this prefix
  const root = (
    document.querySelector(`#${prefix}-root`) ||
    document.querySelector(`#${prefix}-seg-what`)?.closest('.wow-ultra') ||
    document.getElementById(`${prefix}-what`)?.closest('.wow-ultra') ||
    null
  );
  if (root && root.dataset && root.dataset.wowUltraBound === '1') return;
  function byId(s){ return document.getElementById(prefix + '-' + s); }
  const panes = ['what-pane','where-pane','when-pane','who-pane'];
  function hideAll(){ panes.forEach((id) => { const el = byId(id); if (el) el.classList.add('d-none'); }); const what = byId('what'); if (what) what.setAttribute('aria-expanded','false'); }
  function openPane(which){ hideAll(); const pane = byId(which+'-pane'); if(pane){ pane.classList.remove('d-none'); } if(which==='what'){ const what = byId('what'); if(what) what.setAttribute('aria-expanded','true'); } }
  function normalizeText(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[\u2018\u2019\u201c\u201d]/g, "'")
      .replace(/[^a-z0-9]+/g, ' ')
      .trim();
  }
  let whereSource = [];
  let whereSourceReady = false;
  let whatSource = [];
  let whatSourceReady = false;
  function renderWhere(qs) {
    const list = byId('where-list');
    if (!list) return false;
    const query = String(qs || '').trim();
    if (!whereSourceReady) {
      list.innerHTML = '<button type="button" class="item" aria-disabled="true"><span class="title">Loading trending destinations…</span></button>';
      return false;
    }
    const needle = normalizeText(query);

    let items = (whereSource || []).slice()
    if (needle) {
      items = items.filter((item) => normalizeText([item.title, item.label, item.search, item.country, item.county, item.region].join(' ')).includes(needle));
    } else {
      items = items.slice(0, 5)
    }
    if (!items.length) {
      list.innerHTML = '<button type="button" class="item" aria-disabled="true"><span class="title">No locations found</span></button>';
      return false;
    }
    list.innerHTML = items.slice(0, needle ? 12 : 5).map((item) => {
      const icon = item?.online ? '<i class="bi bi-wifi"></i>' : '<i class="bi bi-geo-alt"></i>';
      const sub = item?.subtitle ? `<span class="text-muted ms-2">${item.subtitle}</span>` : '';
      return `<button type="button" class="item" role="option" data-value="${item.value || item.title || ''}">${icon}<span class="title">${item.title || ''}</span>${sub}</button>`;
    }).join('');
    return true;
  }
  function searchScore(query, item) {
    const q = normalizeText(query);
    if (!q) return 999;
    const title = normalizeText(item?.title || '');
    const hay = normalizeText([item?.title, item?.cat, item?.type, item?.search, item?.subtitle].filter(Boolean).join(' '));
    const tokens = q.split(/\s+/).filter(Boolean);
    if (title === q) return 0;
    if (title.indexOf(q) === 0) return 1;
    if (title.split(/\s+/).some((token) => token.indexOf(q) === 0)) return 2;
    if (title.indexOf(q) !== -1) return 3;
    if (hay.indexOf(q) !== -1) return 4;
    if (tokens.length && tokens.every((token) => hay.indexOf(token) !== -1)) return 5;
    return 999;
  }
  function renderWhat(qs) {
    const list = byId('what-list');
    if (!list) return false;
    const query = String(qs || '').trim();
    if (!whatSourceReady) {
      list.innerHTML = '<button type="button" class="item" aria-disabled="true"><span class="title">Loading modalities…</span></button>';
      return false;
    }

    const items = query.length < 2
      ? (whatSource || []).slice(0, 5)
      : (whatSource || [])
        .map((item) => ({ item, score: searchScore(query, item) }))
        .filter((row) => row.score < 999)
        .sort((a, b) => {
          if (a.score !== b.score) return a.score - b.score;
          return String(a.item.title || '').localeCompare(String(b.item.title || ''));
        })
        .slice(0, 5)
        .map((row) => row.item);

    if (!items.length) {
      list.innerHTML = '';
      return false;
    }

    list.innerHTML = `<div class="section-title">${query.length < 2 ? 'Trending modalities' : 'Modalities'}</div><div>` + items.map((item) => {
      const title = String(item?.title || '')
      const subtitle = String(item?.subtitle || (Number(item?.counts?.total || 0) ? `${Number(item.counts.total)} offerings` : 'Modality')).replace(/\bproducts?\b/gi, 'offerings')
      return `<button type="button" class="item" role="option" data-value="${title}"><i class="bi bi-tag"></i><span class="title">${title}</span><span class="text-muted ms-2">${subtitle || 'Modality'}</span></button>`
    }).join('') + '</div>'
    return true;
  }
  const whatInput = byId('what');
  if(whatInput){
    const refreshWhat = () => {
      if (renderWhat(whatInput.value || '')) openPane('what');
      else hideAll();
    };
    whatInput.addEventListener('focus', refreshWhat);
    whatInput.addEventListener('input', refreshWhat);
    const segWhat = byId('seg-what');
    if(segWhat){ segWhat.addEventListener('click', refreshWhat); }
  }
  const whereEditor = byId('where-editor'); if(whereEditor){ whereEditor.addEventListener('focus', ()=>{ renderWhere(whereEditor.textContent || ''); openPane('where'); }); whereEditor.addEventListener('click', ()=>{ renderWhere(whereEditor.textContent || ''); openPane('where'); }); whereEditor.addEventListener('input', ()=>{ renderWhere(whereEditor.textContent || ''); openPane('where'); }); }
  const whenInput = byId('when'); if(whenInput){ whenInput.addEventListener('focus', ()=>openPane('when')); whenInput.addEventListener('click', ()=>openPane('when')); }
  const whoSeg = byId('seg-who'); if(whoSeg){ whoSeg.addEventListener('click', ()=>openPane('who')); }
  // Close only when clicking outside this specific bar
  document.addEventListener('click', (e)=>{ if(root && !root.contains(e.target)) hideAll(); });
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') hideAll(); });
  const whatList = byId('what-list'); if(whatList && byId('what')){ whatList.addEventListener('click', (e)=>{ const btn = e.target.closest('.item'); if(btn && btn.dataset.value){ byId('what').value = btn.dataset.value; hideAll(); byId('what').blur(); } }); }
  const whereHidden = byId('where'); if(byId('where-list') && whereEditor){ byId('where-list').addEventListener('click', (e)=>{ const btn = e.target.closest('.item'); if(btn && btn.dataset.value){ whereEditor.textContent = btn.dataset.value; if(whereHidden) whereHidden.value = btn.dataset.value; hideAll(); whereEditor.blur(); } }); }
  const whoDone = byId('who-done'); if(whoDone){ whoDone.addEventListener('click', ()=>hideAll()); }

  fetchWhatCategories()
    .then((items) => {
      whatSource = items || [];
      whatSourceReady = true;
      renderWhat((whatInput && whatInput.value) || '');
    })
    .catch(() => {
      whatSource = [];
      whatSourceReady = true;
      renderWhat((whatInput && whatInput.value) || '');
    });

  if (whereEditor) {
    fetch('/cache/locations.json', { cache: 'no-store' })
      .then((res) => (res && res.ok) ? res.json() : null)
      .then((payload) => {
        const source = Array.isArray(payload?.flat) && payload.flat.length ? payload.flat : (Array.isArray(payload?.suggestions) ? payload.suggestions : []);
        const seen = new Set();
        whereSource = source.map((item) => {
          const title = String(item?.title || item?.label || item?.slug || '').trim();
          const country = String(item?.country || '').trim();
          const county = String(item?.county || item?.district || item?.region || '').trim();
          const slug = String(item?.slug || title || '').toLowerCase().replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
          return {
            title,
            label: title,
            value: title,
            subtitle: item?.online ? 'Virtual' : [county, country].filter(Boolean).join(', '),
            slug,
            country,
            county,
            region: String(item?.region || '').trim(),
            online: !!item?.online,
            total: Number(item?.counts?.total || item?.counts?.products || item?.counts?.offerings || 0),
            search: [title, country, county, item?.label, item?.place_name, slug].filter(Boolean).join(' '),
          };
        }).filter((item) => {
          const key = normalizeText(item.value);
          if (!key || seen.has(key)) return false;
          seen.add(key);
          return true;
        }).sort((a, b) => {
          if (a.online !== b.online) return a.online ? -1 : 1;
          const at = Number(a.total || 0);
          const bt = Number(b.total || 0);
          if (at !== bt) return bt - at;
          return a.title.localeCompare(b.title);
        });
        whereSourceReady = true;
        renderWhere(whereEditor.textContent || '');
      })
      .catch(() => {
        whereSource = [];
        whereSourceReady = true;
        renderWhere(whereEditor.textContent || '');
      });
  }

  // Shared Who panel controls (Adults counter + group type)
  (function initWhoControls(){
    const pane = byId('who-pane');
    const adultsEl = byId('adults-val');
    const groupList = byId('group-type-list');
    const summaryEl = byId('who-summary');
    if (!pane || !adultsEl || pane.dataset.wowWhoBound === '1') return;

    let groupTouched = false;

    function clampAdults(n){
      const num = Number(n);
      if (!Number.isFinite(num)) return 0;
      return Math.max(0, Math.min(20, Math.round(num)));
    }

    function getAdults(){
      return clampAdults((adultsEl.textContent || adultsEl.value || '0').trim());
    }

    function setGroupSelection(name){
      if (!groupList) return;
      const target = name == null ? '' : String(name || '');
      Array.from(groupList.querySelectorAll('[data-group]')).forEach((btn) => {
        const isMatch = String(btn.getAttribute('data-group')) === target;
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
      const sel = groupList.querySelector('[data-group][aria-selected="true"]');
      return (sel?.getAttribute?.('data-group') || '').trim();
    }

    function updateSummary(){
      if (!summaryEl) return;
      const adults = getAdults();
      const group = getSelectedGroup();
      const parts = [];
      if (adults > 0) parts.push(`${adults} ${adults === 1 ? 'adult' : 'adults'}`);
      if (group) parts.push(group);
      summaryEl.textContent = parts.length ? parts.join(' · ') : 'Add guests';
    }

    function applyAdults(n){
      const next = clampAdults(n);
      adultsEl.textContent = String(next);
      if (!groupTouched) setGroupSelection(groupForAdults(next));
      updateSummary();
    }

    pane.addEventListener('click', (event) => {
      const dec = event.target.closest('[data-dec="adults"]');
      const inc = event.target.closest('[data-inc="adults"]');
      if (!dec && !inc) return;
      event.preventDefault();
      const current = getAdults();
      applyAdults(current + (inc ? 1 : -1));
    });

    if (groupList) {
      groupList.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-group]');
        if (!btn) return;
        const group = (btn.getAttribute('data-group') || '').trim();
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

    // Initial sync
    updateSummary();
    pane.dataset.wowWhoBound = '1';
  })();
}

function initAccountDropdown() {
  const wrap = document.querySelector('.account-wrap');
  const trigger = wrap?.querySelector('.account-trigger');
  const panel = wrap?.querySelector('.account-dropdown');
  if (!wrap || !trigger || !panel) return;

  const isDesktop = () => {
    try { return window.matchMedia('(min-width: 992px)').matches; } catch (_) { return true; }
  };

  let hideTimer = null;

  function openPanel() {
    closeHeaderDropdownExcept('account');
    panel.hidden = false;
    panel.classList.add('show');
    trigger.setAttribute('aria-expanded', 'true');
  }

  function closePanel() {
    panel.hidden = true;
    panel.classList.remove('show');
    trigger.setAttribute('aria-expanded', 'false');
  }

  wrap.addEventListener('mouseenter', () => {
    if (!isDesktop()) return;
    if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
    openPanel();
  });

  wrap.addEventListener('mouseleave', () => {
    if (!isDesktop()) return;
    hideTimer = setTimeout(closePanel, 120);
  });

  trigger.addEventListener('click', (e) => {
    e.preventDefault();
    if (isDesktop()) {
      openPanel();
    } else {
      panel.hidden ? openPanel() : closePanel();
    }
  });

  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) {
      closePanel();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closePanel();
    }
  });

  try {
    window.__WOWCloseAccountDropdown = closePanel;
  } catch (_err) {}
}

function initHeaderSearchModal() {
  const modal = document.getElementById('wowsearch-main-search-modal');
  const dialog = modal?.querySelector('.wowsearch-main-search-dialog');
  const triggers = Array.from(document.querySelectorAll('[data-wow-search-modal-trigger]'));
  if (!modal || !dialog || !triggers.length || modal.dataset.wowMounted === '1') return;

  const $ = (selector, scope = modal) => scope.querySelector(selector);
  const $$ = (selector, scope = modal) => Array.from(scope.querySelectorAll(selector));
  const state = {
    open: false,
    activeModal: null,
    desktopWhereSelected: '',
    mobileWhat: '',
    mobileWhere: '',
    mobileWhereSelected: '',
    whatItems: [],
    whereItems: [],
    returnFocus: null,
    previousOverflow: '',
  };

  const desktopForm = $('#wowsearch-desktop-search-form');
  const desktopShell = $('#wowsearch-desktop-search-shell');
  const whatField = $('#wowsearch-desktop-what-field');
  const whereField = $('#wowsearch-desktop-where-field');
  const whatInput = $('#wowsearch-desktop-what');
  const whereInput = $('#wowsearch-desktop-where');
  const whatDropdown = $('#wowsearch-desktop-what-dropdown');
  const whereDropdown = $('#wowsearch-desktop-where-dropdown');
  const desktopWhatList = $('#wowsearch-desktop-what-list') || $('#wowsearch-desktop-what-dropdown ul');
  const desktopLocationList = $('#wowsearch-desktop-location-list') || $('#wowsearch-desktop-where-dropdown ul');
  const clearWhat = $('#wowsearch-desktop-clear-what');
  const clearWhere = $('#wowsearch-desktop-clear-where');
  const nearMeChip = $('#wowsearch-desktop-near-me-chip');
  const whatHeading = $('#wowsearch-desktop-what-heading');
  const locationHeading = $('#wowsearch-desktop-location-heading');
  const locationHeadingWrap = $('#wowsearch-desktop-location-list-heading-wrap');
  const whatIcon = $('.wowsearch-desktop-what-icon');
  const whereIcon = $('.wowsearch-desktop-where-icon');
  const mobileForm = $('#wowsearch-mobile-search-form');
  const mobileWhatDisplay = $('#wowsearch-mobile-what-display');
  const mobileWhereDisplay = $('#wowsearch-mobile-where-display');
  const mobileNearMeChip = $('#wowsearch-mobile-near-me-chip');
  const mobileWhatIcon = $('.wowsearch-mobile-what-main-icon');
  const mobileWhereIcon = $('.wowsearch-mobile-where-main-icon');
  // These two sheets sit alongside the main dialog in search-modal.html.
  const whatModal = document.getElementById('wowsearch-mobile-what-modal');
  const whereModal = document.getElementById('wowsearch-mobile-where-modal');
  const mobileWhatInput = document.getElementById('wowsearch-mobile-what-input');
  const mobileWhereInput = document.getElementById('wowsearch-mobile-where-input');
  const mobileOnline = document.getElementById('wowsearch-mobile-online');
  const mobileLocationList = document.getElementById('wowsearch-mobile-location-list') || whereModal?.querySelector('.wowsearch-flex-1.wowsearch-overflow-y-auto');

  document.querySelectorAll('.wowsearch-mobile-modal-close').forEach((button) => {
    const actions = document.createElement('div');
    actions.className = 'wowsearch-main-search-header-actions';
    actions.innerHTML = '<span class="wowsearch-main-search-esc" aria-hidden="true">ESC</span><button aria-label="Close search" class="wowsearch-main-search-close" data-wowsearch-main-close data-wowsearch-mobile-close type="button"><svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="18" xmlns="http://www.w3.org/2000/svg"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg></button>';
    button.replaceWith(actions);
  });

  if (!desktopForm || !desktopShell || !whatInput || !whereInput || !mobileForm || !whatModal || !whereModal) {
    // The header can be deferred by page-specific rendering. Do not mark this
    // instance as mounted until every control from the supplied modal exists.
    window.setTimeout(initHeaderSearchModal, 50);
    return;
  }

  modal.dataset.wowMounted = '1';

  const buildSearchUrl = (what, where) => {
    const url = new URL('/search', window.location.origin);
    const whatValue = String(what || '').trim();
    const whereValue = String(where || '').trim();
    if (whatValue) url.searchParams.set('what', whatValue);
    if (whereValue === 'Near me') url.searchParams.set('mode', 'near-me');
    else if (whereValue) url.searchParams.set('where', whereValue);
    return `${url.pathname}${url.search}`;
  };

  const requestLocation = () => {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(() => {}, () => {}, {
      enableHighAccuracy: false,
      timeout: 10000,
      maximumAge: 300000,
    });
  };

  const categoryTone = (value) => {
    switch (String(value || '').trim().toLowerCase()) {
      case 'therapy': return { background: '#e8f5f1', color: '#2a5e52' };
      case 'class': return { background: '#eff6ff', color: '#1d4ed8' };
      case 'event': return { background: '#fffbeb', color: '#b45309' };
      case 'retreat': return { background: '#fff1f2', color: '#be123c' };
      case 'session': return { background: '#eef2ff', color: '#4338ca' };
      case 'workshop': return { background: '#faf5ff', color: '#7e22ce' };
      default: return { background: '#f4f6fb', color: '#344054' };
    }
  };

  const createSparklesIcon = (size) => {
    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    icon.setAttribute('aria-hidden', 'true');
    icon.setAttribute('viewBox', '0 0 24 24');
    icon.setAttribute('width', String(size));
    icon.setAttribute('height', String(size));
    icon.setAttribute('fill', 'none');
    icon.setAttribute('stroke', 'currentColor');
    icon.setAttribute('stroke-width', '2');
    icon.setAttribute('stroke-linecap', 'round');
    icon.setAttribute('stroke-linejoin', 'round');
    icon.classList.add('wowsearch-shrink-0', 'wowsearch-text-[#4f9381]', 'wowsearch-opacity-60');
    icon.innerHTML = '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path>';
    return icon;
  };

  function renderWhatItems() {
    const items = state.whatItems;
    desktopWhatList?.replaceChildren(...items.map((item) => {
      const li = document.createElement('li');
      li.className = 'wowsearch-desktop-what-option';
      li.dataset.label = item.title || item.label || item.value;
      li.dataset.cat = item.cat || item.type || 'Modalities';
      const tone = categoryTone(li.dataset.cat);
      li.innerHTML = `<button class="wowsearch-w-full wowsearch-flex wowsearch-items-center wowsearch-gap-3 wowsearch-px-4 wowsearch-py-2.5 wowsearch-hover:bg-[#f7f9fb] wowsearch-transition-colors wowsearch-text-left" type="button"><span class="wowsearch-flex-1 wowsearch-text-[13.5px] wowsearch-text-[#1a202c] wowsearch-font-medium"></span><span class="wowsearch-text-[10px] wowsearch-font-bold wowsearch-px-2 wowsearch-py-0.5 wowsearch-rounded-full"></span></button>`;
      const button = li.querySelector('button');
      button.prepend(createSparklesIcon(13));
      li.querySelector('span').textContent = li.dataset.label;
      const category = li.querySelectorAll('span')[1];
      category.textContent = li.dataset.cat;
      category.style.backgroundColor = tone.background;
      category.style.color = tone.color;
      return li;
    }) || []);

    const mobileOptions = state.whatItems.map((item) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'wowsearch-mobile-what-option wowsearch-w-full wowsearch-flex wowsearch-items-center wowsearch-gap-3.5 wowsearch-px-3 wowsearch-py-[14px] wowsearch-border-b wowsearch-border-[#eef0f3] wowsearch-text-left';
      button.dataset.label = item.title || item.label || item.value;
      button.dataset.cat = item.cat || item.type || 'Modalities';
      button.innerHTML = `<span class="wowsearch-w-8 wowsearch-h-8 wowsearch-flex wowsearch-items-center wowsearch-justify-center wowsearch-shrink-0"></span><span class="wowsearch-flex-1"><strong class="wowsearch-block wowsearch-text-[15px] wowsearch-text-[#1a202c] wowsearch-font-semibold"></strong><small class="wowsearch-text-[12px] wowsearch-text-[#98a2b3]"></small></span><span class="wowsearch-selection-check wowsearch-w-5 wowsearch-h-5 wowsearch-rounded-full wowsearch-bg-[#4f9381] wowsearch-items-center wowsearch-justify-center" hidden>✓</span>`;
      button.querySelector('span').append(createSparklesIcon(16));
      button.querySelector('strong').textContent = button.dataset.label;
      button.querySelector('small').textContent = button.dataset.cat;
      return button;
    });
    const mobileWhatList = whatModal.querySelector('.wowsearch-flex-1.wowsearch-overflow-y-auto');
    mobileWhatList?.replaceChildren(...mobileOptions);
  }

  function renderLocationItems() {
    const items = state.whereItems.filter((item) => !item.online);
    desktopLocationList?.replaceChildren(...items.map((item) => {
      const li = document.createElement('li');
      li.className = 'wowsearch-desktop-location-option';
      li.dataset.label = item.title || item.label || item.value;
      li.innerHTML = '<button class="wowsearch-w-full wowsearch-flex wowsearch-items-center wowsearch-gap-3 wowsearch-px-4 wowsearch-py-2.5 wowsearch-hover:bg-[#f7f9fb] wowsearch-transition-colors wowsearch-text-left" type="button"><span class="wowsearch-flex-1 wowsearch-text-[13.5px] wowsearch-text-[#1a202c] wowsearch-font-medium"></span></button>';
      li.querySelector('span').textContent = li.dataset.label;
      return li;
    }) || []);
    mobileLocationList?.replaceChildren(...items.map((item) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'wowsearch-mobile-location-option wowsearch-w-full wowsearch-flex wowsearch-items-center wowsearch-gap-3.5 wowsearch-px-3 wowsearch-py-[14px] wowsearch-border-b wowsearch-border-[#eef0f3] wowsearch-text-left';
      button.dataset.label = item.title || item.label || item.value;
      button.innerHTML = '<span class="wowsearch-flex-1 wowsearch-text-[15px] wowsearch-text-[#1a202c] wowsearch-font-medium"></span><span class="wowsearch-selection-check wowsearch-w-5 wowsearch-h-5 wowsearch-rounded-full wowsearch-bg-[#4f9381] wowsearch-items-center wowsearch-justify-center" hidden>✓</span>';
      button.querySelector('span').textContent = button.dataset.label;
      return button;
    }) || []);
    filterMobileWhere();
  }

  function filterDesktopWhat() {
    const query = whatInput.value.trim().toLowerCase();
    let visible = 0;
    if (whatHeading) whatHeading.textContent = query
      ? 'Suggestions'
      : (state.whatItems.some((item) => item.cat === 'Popular searches') ? 'Popular searches' : 'Popular experiences');
    $$('.wowsearch-desktop-what-option', desktopWhatList).forEach((item) => {
      const matches = !query || `${item.dataset.label} ${item.dataset.cat}`.toLowerCase().includes(query);
      item.hidden = !matches || visible >= 8;
      if (!item.hidden) visible += 1;
    });
    if (whatDropdown) whatDropdown.hidden = state.activeModal !== 'desktop-what' || visible === 0;
  }

  function filterDesktopWhere() {
    const query = whereInput.value.trim().toLowerCase();
    let visible = 0;
    if (locationHeading) locationHeading.textContent = query ? 'Matching' : 'Popular';
    $$('.wowsearch-desktop-location-option', desktopLocationList).forEach((item) => {
      const matches = !query || item.dataset.label.toLowerCase().includes(query);
      item.hidden = !matches || visible >= (query ? 6 : 8);
      if (!item.hidden) visible += 1;
    });
    if (locationHeadingWrap) locationHeadingWrap.hidden = !!query && visible === 0;
  }

  function setDesktopActive(which) {
    state.activeModal = which;
    const active = which === 'desktop-what' || which === 'desktop-where';
    desktopShell.classList.toggle('wowsearch-border-[rgba(155,165,180,0.45)]', !active);
    desktopShell.classList.toggle('wowsearch-border-[rgba(155,165,180,0.6)]', active);
    whatDropdown.hidden = which !== 'desktop-what';
    whereDropdown.hidden = which !== 'desktop-where';
    if (clearWhat) clearWhat.hidden = !(whatInput.value && which === 'desktop-what');
    if (clearWhere) clearWhere.hidden = !(whereInput.value || state.desktopWhereSelected);
    whatIcon?.classList.toggle('wowsearch-text-[#4f9381]', which === 'desktop-what');
    whereIcon?.classList.toggle('wowsearch-text-[#4f9381]', which === 'desktop-where');
    if (which === 'desktop-what') filterDesktopWhat();
    if (which === 'desktop-where') filterDesktopWhere();
  }

  function updateDesktopWhere() {
    const nearMe = state.desktopWhereSelected === 'Near me';
    whereInput.hidden = nearMe;
    nearMeChip.hidden = !nearMe;
    clearWhere.hidden = !(whereInput.value || state.desktopWhereSelected);
  }

  function filterMobileWhat() {
    const query = mobileWhatInput.value.trim().toLowerCase();
    let visible = 0;
    $$('.wowsearch-mobile-what-option', whatModal).forEach((button) => {
      const matches = !query || `${button.dataset.label} ${button.dataset.cat}`.toLowerCase().includes(query);
      button.hidden = !matches || visible >= 8;
      if (!button.hidden) visible += 1;
    });
  }

  function filterMobileWhere() {
    const query = mobileWhereInput.value.trim().toLowerCase();
    let visible = 0;
    $$('.wowsearch-mobile-location-option', whereModal).forEach((button) => {
      const matches = !query || button.dataset.label.toLowerCase().includes(query);
      button.hidden = !matches || visible >= 8;
      if (!button.hidden) visible += 1;
    });
  }

  function updateMobileDisplay() {
    const where = state.mobileWhereSelected || state.mobileWhere;
    mobileWhatDisplay.textContent = state.mobileWhat || 'Search therapies, events & more';
    mobileWhereDisplay.textContent = where || 'Near me, town or Online';
    mobileWhereDisplay.hidden = where === 'Near me';
    mobileNearMeChip.hidden = where !== 'Near me';
    mobileWhatDisplay.classList.toggle('wowsearch-text-[#111827]', !!state.mobileWhat);
    mobileWhereDisplay.classList.toggle('wowsearch-text-[#111827]', !!where);
    mobileWhatIcon?.classList.toggle('wowsearch-text-[#4f9381]', !!state.mobileWhat);
    mobileWhereIcon?.classList.toggle('wowsearch-text-[#4f9381]', !!where);
  }

  function markMobileSelections() {
    $$('.wowsearch-mobile-what-option', whatModal).forEach((button) => {
      const selected = button.dataset.label === state.mobileWhat;
      button.classList.toggle('wowsearch-bg-[#f0faf7]', selected);
      const check = $('.wowsearch-selection-check', button);
      if (check) {
        check.hidden = !selected;
        check.classList.toggle('wowsearch-flex', selected);
      }
    });

    const selectedWhere = state.mobileWhereSelected;
    mobileOnline?.classList.toggle('wowsearch-bg-[#f0faf7]', selectedWhere === 'Online');
    $$('.wowsearch-mobile-location-option', whereModal).forEach((button) => {
      const selected = button.dataset.label === selectedWhere;
      button.classList.toggle('wowsearch-bg-[#f0faf7]', selected);
      const check = $('.wowsearch-selection-check', button);
      if (check) {
        check.hidden = !selected;
        check.classList.toggle('wowsearch-flex', selected);
      }
    });
  }

  function closeSubModal(which = state.activeModal) {
    if (which === 'what' || which === 'all') whatModal.hidden = true;
    if (which === 'where' || which === 'all') whereModal.hidden = true;
    if (which === 'what' || which === 'where' || which === 'all') state.activeModal = 'main';
    document.body.style.overflow = 'hidden';
  }

  function selectMobileWhat(value) {
    const selection = String(value || '').trim();
    if (!selection) return;
    state.mobileWhat = selection;
    updateMobileDisplay();
    markMobileSelections();
    closeSubModal('what');
  }

  function selectMobileWhere(value) {
    const selection = String(value || '').trim();
    if (!selection) return;
    state.mobileWhere = selection;
    state.mobileWhereSelected = selection;
    updateMobileDisplay();
    markMobileSelections();
    closeSubModal('where');
  }

  function openSubModal(which) {
    const sub = which === 'what' ? whatModal : whereModal;
    whatModal.hidden = which !== 'what';
    whereModal.hidden = which !== 'where';
    state.activeModal = which;
    if (which === 'what') {
      mobileWhatInput.value = state.mobileWhat;
      filterMobileWhat();
    } else {
      mobileWhereInput.value = '';
      filterMobileWhere();
    }
    markMobileSelections();
    window.setTimeout(() => (which === 'what' ? mobileWhatInput : mobileWhereInput).focus(), 0);
  }

  function closeMainSearch() {
    if (!state.open) return;
    closeSubModal('all');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
    document.body.style.overflow = state.previousOverflow;
    state.open = false;
    state.activeModal = null;
    state.returnFocus?.focus?.({ preventScroll: true });
  }

  function openMainSearch(trigger) {
    if (state.open) return;
    state.open = true;
    state.activeModal = 'main';
    state.returnFocus = trigger;
    state.previousOverflow = document.body.style.overflow || '';
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    triggers.forEach((item) => item.setAttribute('aria-expanded', 'true'));
    document.body.style.overflow = 'hidden';
    dialog.focus({ preventScroll: true });
  }

  // Bind directly to the controls: legacy header code also delegates clicks at
  // document level, so a direct handler keeps this modal independent of it.
  triggers.forEach((trigger) => {
    trigger.onclick = (event) => {
      event.preventDefault();
      if (state.open) {
        closeMainSearch();
        return;
      }
      openMainSearch(trigger);
    };
  });
  $$('[data-wowsearch-main-close]').forEach((element) => element.addEventListener('click', closeMainSearch));
  dialog.addEventListener('click', (event) => {
    if (event.target === modal.querySelector('.wowsearch-main-search-backdrop')) closeMainSearch();
  });

  whatInput.addEventListener('focus', () => setDesktopActive('desktop-what'));
  whereInput.addEventListener('focus', () => setDesktopActive('desktop-where'));
  whatInput.addEventListener('input', filterDesktopWhat);
  whereInput.addEventListener('input', () => { state.desktopWhereSelected = ''; filterDesktopWhere(); });
  whatField?.addEventListener('click', (event) => { if (!event.target.closest('button')) whatInput.focus(); });
  whereField?.addEventListener('click', (event) => { if (!event.target.closest('button')) whereInput.focus(); });
  clearWhat?.addEventListener('click', () => { whatInput.value = ''; whatInput.focus(); });
  clearWhere?.addEventListener('click', () => { state.desktopWhereSelected = ''; whereInput.value = ''; whereInput.focus(); updateDesktopWhere(); });

  desktopWhatList?.addEventListener('click', (event) => {
    const item = event.target.closest('.wowsearch-desktop-what-option');
    if (!item) return;
    whatInput.value = item.dataset.label;
    whereInput.focus();
  });
  desktopLocationList?.addEventListener('click', (event) => {
    const item = event.target.closest('.wowsearch-desktop-location-option');
    if (!item) return;
    whereInput.value = item.dataset.label;
    state.desktopWhereSelected = item.dataset.label;
    updateDesktopWhere();
    setDesktopActive(null);
  });
  $('#wowsearch-desktop-online')?.addEventListener('click', () => {
    whereInput.value = 'Online';
    state.desktopWhereSelected = 'Online';
    updateDesktopWhere();
    setDesktopActive(null);
  });
  $('#wowsearch-desktop-use-location')?.addEventListener('click', () => {
    whereInput.value = 'Near me';
    state.desktopWhereSelected = 'Near me';
    updateDesktopWhere();
    setDesktopActive(null);
    requestLocation();
  });
  desktopForm.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!whatInput.value.trim()) return setDesktopActive('desktop-what');
    window.location.href = buildSearchUrl(whatInput.value, whereInput.value);
  });

  document.getElementById('wowsearch-mobile-open-what')?.addEventListener('click', () => openSubModal('what'));
  document.getElementById('wowsearch-mobile-open-where')?.addEventListener('click', () => openSubModal('where'));
  document.querySelectorAll('.wowsearch-mobile-modal-overlay, [data-wowsearch-mobile-close]').forEach((element) => element.addEventListener('click', () => {
    const sheet = element.closest('#wowsearch-mobile-what-modal, #wowsearch-mobile-where-modal');
    closeSubModal(sheet?.id === 'wowsearch-mobile-what-modal' ? 'what' : 'where');
  }));
  mobileWhatInput.addEventListener('input', filterMobileWhat);
  mobileWhereInput.addEventListener('input', filterMobileWhere);
  mobileWhatInput.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    const match = $$('.wowsearch-mobile-what-option', whatModal).find((item) => !item.hidden);
    selectMobileWhat(match?.dataset.label || mobileWhatInput.value);
  });
  mobileWhereInput.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    const match = $$('.wowsearch-mobile-location-option', whereModal).find((item) => !item.hidden);
    selectMobileWhere(match?.dataset.label || mobileWhereInput.value);
  });
  whatModal.addEventListener('click', (event) => {
    const item = event.target.closest('.wowsearch-mobile-what-option');
    if (!item) return;
    selectMobileWhat(item.dataset.label);
  });
  whereModal.addEventListener('click', (event) => {
    const item = event.target.closest('.wowsearch-mobile-location-option');
    if (!item) return;
    selectMobileWhere(item.dataset.label);
  });
  document.getElementById('wowsearch-mobile-use-location')?.addEventListener('click', () => {
    selectMobileWhere('Near me');
    requestLocation();
  });
  mobileOnline?.addEventListener('click', () => {
    selectMobileWhere('Online');
  });
  mobileForm.addEventListener('submit', (event) => {
    event.preventDefault();
    if (state.mobileWhat.trim()) window.location.href = buildSearchUrl(state.mobileWhat, state.mobileWhereSelected || state.mobileWhere);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && state.open) closeMainSearch();
  });
  document.addEventListener('mousedown', (event) => {
    if (state.open && !desktopForm.contains(event.target)) setDesktopActive(null);
  });

  Promise.all([fetchWhatCategories(), fetchLocations(1000)]).then(([whatItems, whereItems]) => {
    if (Array.isArray(whatItems) && whatItems.length) {
      state.whatItems = whatItems;
      renderWhatItems();
    }
    if (Array.isArray(whereItems) && whereItems.length) {
      state.whereItems = whereItems;
      renderLocationItems();
    }
    filterDesktopWhat();
    filterDesktopWhere();
    markMobileSelections();
  });
  updateDesktopWhere();
  updateMobileDisplay();
}

function mountSearchRangeCalendars() {
  try {
    document.querySelectorAll('[id$="-calendarMount"]').forEach((el) => {
      if (!el || el.dataset.wowMounted === '1') return;
      const prefix = String(el.id || '').replace(/-calendarMount$/, '');
      if (!prefix) return;
      el.dataset.wowMounted = '1';
      try {
        createApp(SearchRangeCalendar, { prefix }).use(ui).mount(el);
      } catch (err) {
        el.dataset.wowMounted = '0';
        console.warn('[WOW] calendar mount failed', err);
      }
    });
  } catch (err) {
    console.warn('[WOW] calendar bootstrap skipped', err);
  }
}

function mountSearchBarV4() {
  try {
    document.querySelectorAll('[data-wow-searchbar-v4]').forEach((el) => {
      if (!el || el.dataset.wowMounted === '1') return;
      el.dataset.wowMounted = '1';

      let initialQuery = {};
      try {
        initialQuery = JSON.parse(el.dataset.initialQuery || '{}') || {};
      } catch (_err) {
        initialQuery = {};
      }

      const readBool = (value, fallback = false) => {
        if (value === undefined || value === null || value === '') return fallback;
        return ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
      };

      const props = {
        idPrefix: el.dataset.idPrefix || 'search-v4',
        searchUrl: el.dataset.searchUrl || '/search',
        resultCount: Number(el.dataset.resultCount || 0),
        mobileTopOffset: el.dataset.mobileTopOffset || 12,
        initialQuery,
        staticLayout: readBool(el.dataset.staticLayout, false),
        showChrome: readBool(el.dataset.showChrome, true),
        mobileChrome: readBool(el.dataset.mobileChrome, false),
        navigateOnSubmit: readBool(el.dataset.navigateOnSubmit, false),
        forceMobileLayout: readBool(el.dataset.forceMobileLayout, false),
        defaultActiveSegment: el.dataset.defaultActiveSegment || '',
        hideTopRow: readBool(el.dataset.hideTopRow, false),
        hideMobileClose: readBool(el.dataset.hideMobileClose, false),
      };

      try {
        createApp(SearchBarV4, props).use(ui).mount(el);
      } catch (err) {
        el.dataset.wowMounted = '0';
        console.warn('[WOW] search bar v4 mount failed', err);
      }
    });
  } catch (err) {
    console.warn('[WOW] search bar v4 bootstrap skipped', err);
  }
}

function mountHomeSearchBarV4() {
  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')

  const normalizeText = (value) => String(value || '')
    .toLowerCase()
    .replace(/[‘’“”]/g, "'")
    .replace(/[^a-z0-9]+/g, ' ')
    .trim()

  const scoreItem = (query, item) => {
    const q = normalizeText(query)
    if (!q) return 999
    const title = normalizeText(item?.title || item?.label || item?.value || '')
    const hay = normalizeText([item?.title, item?.label, item?.value, item?.slug, item?.search, item?.subtitle, item?.type, item?.cat].filter(Boolean).join(' '))
    if (title === q) return 0
    if (title.startsWith(q)) return 1
    if (title.includes(q)) return 2
    if (hay.includes(q)) return 3
    const tokens = q.split(/\s+/).filter(Boolean)
    if (tokens.length && tokens.every((token) => hay.includes(token))) return 4
    return 999
  }

  const buildSearchUrl = (baseUrl, params) => {
    const url = new URL(baseUrl || '/search', window.location.origin)
    const whatValue = String(params?.what || '').trim()
    const whereValue = String(params?.where || '').trim()
    const modeValue = String(params?.mode || '').trim()

    if (whatValue) url.searchParams.set('what', whatValue)
    if (whereValue && whereValue !== 'Near me') url.searchParams.set('where', whereValue)
    if (modeValue && modeValue !== 'near-me' && whereValue !== 'Near me') {
      url.searchParams.set('mode', modeValue)
    }

    return `${url.pathname}${url.search}${url.hash}`
  }

  const categoryTone = (value) => {
    switch (String(value || '').trim().toLowerCase()) {
      case 'therapy':
        return { bg: '#e8f5f1', text: '#2a5e52' }
      case 'class':
        return { bg: '#eff6ff', text: '#1d4ed8' }
      case 'event':
        return { bg: '#fffbeb', text: '#b45309' }
      case 'retreat':
        return { bg: '#fff1f2', text: '#be123c' }
      case 'session':
        return { bg: '#eef2ff', text: '#4338ca' }
      case 'workshop':
        return { bg: '#faf5ff', text: '#7e22ce' }
      case 'gift':
        return { bg: '#fefce8', text: '#a16207' }
      default:
        return { bg: '#f4f6fb', text: '#344054' }
    }
  }

  const renderDesktopWhatItems = (items) => items.map((item) => {
    const title = item.value || item.title || ''
    const cat = item.cat || item.type || 'Experience'
    const tone = categoryTone(cat)
    return `
      <li class="desktop-what-option" data-label="${escapeHtml(title)}" data-cat="${escapeHtml(cat)}">
        <button type="button" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-[#f7f9fb] transition-colors text-left group">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles text-[#4f9381] shrink-0 opacity-60" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg>
          <span class="flex-1 text-[13.5px] text-[#1a202c] font-medium">${escapeHtml(title)}</span>
          <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:${tone.bg}; color:${tone.text};">${escapeHtml(cat)}</span>
        </button>
      </li>
    `
  }).join('')

  const renderMobileWhatItems = (items, selected) => items.map((item) => {
    const title = item.value || item.title || ''
    const cat = item.cat || item.type || 'Experience'
    const isSelected = title === selected
    return `
      <button type="button" class="mobile-what-option w-full flex items-center gap-3.5 px-3 py-[14px] border-b border-[#eef0f3] text-left last:border-0 ${isSelected ? 'bg-[#f0faf7]' : ''}" data-label="${escapeHtml(title)}" data-cat="${escapeHtml(cat)}">
        <span class="w-8 h-8 flex items-center justify-center shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles text-[#4f9381]" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg></span>
        <span class="text-[15px] text-[#1a202c] font-medium flex-1">${escapeHtml(title)}</span>
        <span class="selection-check w-5 h-5 rounded-full bg-[#4f9381] ${isSelected ? 'flex' : 'hidden'} items-center justify-center"><span class="text-white text-[10px] font-bold">✓</span></span>
      </button>
    `
  }).join('')

  const renderDesktopWhereItems = (items) => items.map((item) => `
    <li class="desktop-location-option" data-label="${escapeHtml(item.value || item.title || '')}">
      <button type="button" class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-[#f7f9fb] transition-colors text-left">
        ${item.online
          ? '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wifi text-blue-500 shrink-0" aria-hidden="true"><path d="M12 20h.01"></path><path d="M2 8.82a15 15 0 0 1 20 0"></path><path d="M5 12.859a10 10 0 0 1 14 0"></path><path d="M8.5 16.429a5 5 0 0 1 7 0"></path></svg>'
          : '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building-2 text-[#8e9bb0] shrink-0" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>'}
        <span class="text-[13.5px] text-[#1a202c] font-medium">${escapeHtml(item.value || item.title || '')}</span>
      </button>
    </li>
  `).join('')

  const renderMobileWhereItems = (items, selected) => items.map((item) => {
    const value = item.value || item.title || ''
    const isSelected = value === selected
    return `
      <button type="button" class="mobile-location-option w-full flex items-center gap-3.5 px-3 py-[14px] border-b border-[#eef0f3] text-left last:border-0 ${isSelected ? 'bg-[#f0faf7]' : ''}" data-label="${escapeHtml(value)}">
        <span class="w-8 h-8 flex items-center justify-center shrink-0">${item.online
          ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wifi text-blue-500" aria-hidden="true"><path d="M12 20h.01"></path><path d="M2 8.82a15 15 0 0 1 20 0"></path><path d="M5 12.859a10 10 0 0 1 14 0"></path><path d="M8.5 16.429a5 5 0 0 1 7 0"></path></svg>'
          : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building-2 text-[#8e9bb0]" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>'}</span>
        <span class="text-[15px] text-[#1a202c] font-medium flex-1">${escapeHtml(value)}</span>
        <span class="selection-check w-5 h-5 rounded-full bg-[#4f9381] ${isSelected ? 'flex' : 'hidden'} items-center justify-center"><span class="text-white text-[10px] font-bold">✓</span></span>
      </button>
    `
  }).join('')

  const requestLocation = () => {
    if (!navigator.geolocation) return
    navigator.geolocation.getCurrentPosition(
      () => {},
      () => {},
      { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
    )
  }

  try {
    document.querySelectorAll('[data-wow-home-searchbar-v4]').forEach((el) => {
      if (!el || el.dataset.wowMounted === '1') return
      el.dataset.wowMounted = '1'

      const $ = (selector, root = el) => root.querySelector(selector)
      const $$ = (selector, root = el) => Array.from(root.querySelectorAll(selector))
      const searchUrl = el.dataset.searchUrl || '/search'
      const desktopForm = $('#desktop-search-form')
      const mobileForm = $('#mobile-search-form')
      const desktopShell = $('#desktop-search-shell')
      const whatField = $('#desktop-what-field')
      const whereField = $('#desktop-where-field')
      const whatInput = $('#desktop-what')
      const whereInput = $('#desktop-where')
      const whatDropdown = $('#desktop-what-dropdown')
      const whereDropdown = $('#desktop-where-dropdown')
      const clearWhat = $('#desktop-clear-what')
      const clearWhere = $('#desktop-clear-where')
      const nearMeChip = $('#desktop-near-me-chip')
      const whatHeading = $('#desktop-what-heading')
      const locationHeading = $('#desktop-location-heading')
      const locationHeadingWrap = $('#desktop-location-list-heading-wrap')
      const whatIcon = $('.desktop-what-icon')
      const whereIcon = $('.desktop-where-icon')
      const desktopWhatList = $('#desktop-what-list')
      const desktopLocationList = $('#desktop-location-list')
      const desktopModeInput = $('#desktop-mode')

      const mobileWhatDisplay = $('#mobile-what-display')
      const mobileWhereDisplay = $('#mobile-where-display')
      const mobileNearMeChip = $('#mobile-near-me-chip')
      const mobileWhatIcon = $('.mobile-what-main-icon')
      const mobileWhereIcon = $('.mobile-where-main-icon')
      const whatModal = $('#mobile-what-modal')
      const whereModal = $('#mobile-where-modal')
      const mobileWhatInput = $('#mobile-what-input')
      const mobileWhereInput = $('#mobile-where-input')
      const mobileWhatList = $('#mobile-what-list')
      const mobileWhereList = $('#mobile-where-list')
      const mobileModeInput = $('#mobile-mode')
      const mobileOnline = $('#mobile-online')

      if (!desktopForm || !mobileForm || !desktopShell || !whatInput || !whereInput || !desktopWhatList || !desktopLocationList || !mobileWhatList || !mobileWhereList) {
        el.dataset.wowMounted = '0'
        return
      }

      const initialWhat = String(el.dataset.initialQuery || '').trim()
      const initialWhere = String(el.dataset.initialWhere || '').trim()
      const initialMode = String(el.dataset.initialMode || '').trim()

      const state = {
        activeDesktop: null,
        activeModal: null,
        what: initialWhat,
        where: initialWhere,
        mode: initialMode,
        whatItems: [],
        whereItems: [],
      }

      const syncHiddenInputs = () => {
        if (desktopModeInput) desktopModeInput.value = state.mode
        if (mobileModeInput) mobileModeInput.value = state.mode
      }

      const updateDesktopWhereDisplay = () => {
        const isNearMe = state.where === 'Near me'
        whereInput.hidden = isNearMe
        nearMeChip.hidden = !isNearMe
        clearWhere.hidden = !(state.where || state.mode === 'online')
      }

      const updateMobileMainDisplay = () => {
        mobileWhatDisplay.textContent = state.what || 'Search therapies, events & more'
        mobileWhatDisplay.classList.toggle('text-[#111827]', !!state.what)
        mobileWhatDisplay.classList.toggle('text-[#687283]', !state.what)
        mobileWhatDisplay.classList.toggle('font-normal', !state.what)
        mobileWhatIcon.classList.toggle('is-active', !!state.what)

        const isNearMe = state.where === 'Near me'
        mobileNearMeChip.hidden = !isNearMe
        mobileWhereDisplay.hidden = isNearMe
        if (!isNearMe) {
          mobileWhereDisplay.textContent = state.where || 'Near me, town or Online'
          mobileWhereDisplay.classList.toggle('text-[#111827]', !!state.where)
          mobileWhereDisplay.classList.toggle('text-[#687283]', !state.where)
          mobileWhereDisplay.classList.toggle('font-normal', !state.where)
        }
        mobileWhereIcon.classList.toggle('is-active', !!state.where)
      }

      const renderDesktopWhat = () => {
        const query = whatInput.value.trim().toLowerCase()
        const source = query.length < 2
          ? state.whatItems.slice(0, 8)
          : state.whatItems
            .map((item) => ({ item, score: scoreItem(query, item) }))
            .filter((row) => row.score < 999)
            .sort((a, b) => a.score - b.score || String(a.item.title || '').localeCompare(String(b.item.title || '')))
            .slice(0, 8)
            .map((row) => row.item)
        whatHeading.textContent = query
          ? 'Suggestions'
          : (state.whatItems.some((item) => item.cat === 'Popular searches') ? 'Popular searches' : 'Popular experiences')
        desktopWhatList.innerHTML = renderDesktopWhatItems(source)
        whatDropdown.hidden = state.activeDesktop !== 'what' || source.length === 0
      }

      const renderDesktopWhere = () => {
        const query = whereInput.value.trim().toLowerCase()
        const source = query.length < 2
          ? state.whereItems.filter((item) => !item.online).slice(0, 8)
          : state.whereItems
            .filter((item) => !item.online)
            .map((item) => ({ item, score: scoreItem(query, item) }))
            .filter((row) => row.score < 999)
            .sort((a, b) => a.score - b.score || String(a.item.title || '').localeCompare(String(b.item.title || '')))
            .slice(0, 6)
            .map((row) => row.item)
        locationHeading.textContent = query ? 'Matching' : 'Popular'
        locationHeadingWrap.hidden = query && source.length === 0
        desktopLocationList.innerHTML = renderDesktopWhereItems(source)
      }

      const renderMobileWhat = () => {
        const query = mobileWhatInput.value.trim().toLowerCase()
        const source = query
          ? state.whatItems
            .filter((item) => scoreItem(query, item) < 999)
            .slice(0, 20)
          : state.whatItems.slice(0, 25)
        mobileWhatList.innerHTML = renderMobileWhatItems(source, state.what)
      }

      const renderMobileWhere = () => {
        const query = mobileWhereInput.value.trim().toLowerCase()
        const source = query
          ? state.whereItems.filter((item) => !item.online && scoreItem(query, item) < 999).slice(0, 20)
          : state.whereItems.filter((item) => !item.online).slice(0, 12)
        mobileWhereList.innerHTML = renderMobileWhereItems(source, state.where)
        mobileOnline.classList.toggle('bg-[#f0faf7]', state.where === 'Online')
      }

      const syncInputs = () => {
        whatInput.value = state.what
        if (state.where !== 'Near me') whereInput.value = state.where
        mobileWhatInput.value = state.what
        mobileWhereInput.value = state.where === 'Near me' ? '' : state.where
        clearWhat.hidden = !(state.what && state.activeDesktop === 'what')
        syncHiddenInputs()
        updateDesktopWhereDisplay()
        updateMobileMainDisplay()
      }

      const setDesktopActive = (which) => {
        state.activeDesktop = which
        desktopShell.classList.toggle('is-active', which === 'what' || which === 'where')
        whatField.classList.toggle('is-active', which === 'what')
        whereField.classList.toggle('is-active', which === 'where')
        whatIcon.classList.toggle('is-active', which === 'what')
        whereIcon.classList.toggle('is-active', which === 'where')
        whatDropdown.hidden = which !== 'what'
        whereDropdown.hidden = which !== 'where'
        clearWhat.hidden = !(state.what && which === 'what')
        updateDesktopWhereDisplay()
        if (which === 'what') renderDesktopWhat()
        if (which === 'where') renderDesktopWhere()
      }

      const openModal = (modal, input) => {
        if (state.activeModal && state.activeModal !== modal) closeModal(state.activeModal)
        state.activeModal = modal
        modal.hidden = false
        document.body.style.overflow = 'hidden'
        if (modal === whatModal) {
          mobileWhatInput.value = state.what
          renderMobileWhat()
        } else {
          mobileWhereInput.value = ''
          renderMobileWhere()
        }
        window.setTimeout(() => input.focus(), 100)
      }

      const closeModal = (modal) => {
        if (!modal) return
        modal.hidden = true
        const sheet = $('.mobile-sheet', modal)
        if (sheet) sheet.style.height = '88dvh'
        if (state.activeModal === modal) state.activeModal = null
        if (!state.activeModal) document.body.style.overflow = ''
        if (modal === whereModal) mobileWhereInput.value = ''
      }

      const enableSheetDrag = (modal) => {
        const handle = $('.mobile-sheet-handle', modal)
        const sheet = $('.mobile-sheet', modal)
        if (!handle || !sheet) return
        let drag = null
        handle.addEventListener('pointerdown', (event) => {
          handle.setPointerCapture(event.pointerId)
          drag = { y: event.clientY, height: parseFloat(sheet.style.height) || 88 }
        })
        handle.addEventListener('pointermove', (event) => {
          if (!drag) return
          const delta = ((drag.y - event.clientY) / window.innerHeight) * 100
          const height = Math.min(100, Math.max(88, drag.height + delta))
          sheet.style.height = `${height}dvh`
        })
        const finish = () => {
          if (!drag) return
          drag = null
          const height = parseFloat(sheet.style.height) || 88
          sheet.style.height = `${height > 94 ? 100 : 88}dvh`
        }
        handle.addEventListener('pointerup', finish)
        handle.addEventListener('pointercancel', finish)
      }

      const submitSearch = () => {
        if (!state.what.trim()) return false
        window.location.assign(buildSearchUrl(searchUrl, {
          what: state.what,
          where: state.where,
          mode: state.where === 'Online' ? 'online' : state.where === 'Near me' ? 'near-me' : '',
        }))
        return true
      }

      const applyWhat = (value) => {
        state.what = String(value || '').trim()
        syncInputs()
        renderDesktopWhat()
        renderMobileWhat()
      }

      const applyWhere = (value, mode = '') => {
        state.where = String(value || '').trim()
        state.mode = String(mode || '').trim()
        syncInputs()
        renderDesktopWhere()
        renderMobileWhere()
      }

      desktopForm.addEventListener('submit', (event) => {
        event.preventDefault()
        if (!submitSearch()) {
          whatInput.focus()
          setDesktopActive('what')
        }
      })
      mobileForm.addEventListener('submit', (event) => {
        event.preventDefault()
        submitSearch()
      })

      whatField.addEventListener('click', (event) => {
        if (event.target.closest('button')) return
        whatInput.focus()
      })
      whereField.addEventListener('click', (event) => {
        if (event.target.closest('button')) return
        if (!whereInput.hidden) whereInput.focus()
        setDesktopActive('where')
      })
      whatInput.addEventListener('focus', () => setDesktopActive('what'))
      whereInput.addEventListener('focus', () => setDesktopActive('where'))
      whatInput.addEventListener('input', () => {
        state.what = whatInput.value.trim()
        clearWhat.hidden = !(state.what && state.activeDesktop === 'what')
        updateMobileMainDisplay()
        renderDesktopWhat()
      })
      whereInput.addEventListener('input', () => {
        state.where = whereInput.value.trim()
        state.mode = state.where === 'Online' ? 'online' : ''
        updateDesktopWhereDisplay()
        updateMobileMainDisplay()
        renderDesktopWhere()
      })

      clearWhat.addEventListener('mousedown', (event) => {
        event.preventDefault()
        applyWhat('')
        whatInput.focus()
        setDesktopActive('what')
      })
      clearWhere.addEventListener('mousedown', (event) => {
        event.preventDefault()
        applyWhere('', '')
        whereInput.hidden = false
        whereInput.focus()
        setDesktopActive('where')
      })

      $('#desktop-online')?.addEventListener('mousedown', (event) => {
        event.preventDefault()
        applyWhere('Online', 'online')
        setDesktopActive(null)
      })
      $('#desktop-use-location')?.addEventListener('mousedown', (event) => {
        event.preventDefault()
        applyWhere('Near me', 'near-me')
        requestLocation()
        setDesktopActive(null)
      })

      $('#mobile-open-what')?.addEventListener('click', () => openModal(whatModal, mobileWhatInput))
      $('#mobile-open-where')?.addEventListener('click', () => openModal(whereModal, mobileWhereInput))
      $$('.mobile-modal-overlay, .mobile-modal-close').forEach((element) => {
        element.addEventListener('click', () => closeModal(element.closest('[id$="-modal"]')))
      })
      enableSheetDrag(whatModal)
      enableSheetDrag(whereModal)

      mobileWhatInput.addEventListener('input', renderMobileWhat)
      mobileWhereInput.addEventListener('input', renderMobileWhere)
      $('#mobile-use-location')?.addEventListener('click', () => {
        applyWhere('Near me', 'near-me')
        requestLocation()
        closeModal(whereModal)
      })
      mobileOnline?.addEventListener('click', () => {
        applyWhere('Online', 'online')
        closeModal(whereModal)
      })

      el.addEventListener('mousedown', (event) => {
        const desktopWhatButton = event.target.closest('.desktop-what-option button')
        if (desktopWhatButton) {
          event.preventDefault()
          const item = desktopWhatButton.closest('.desktop-what-option')
          applyWhat(item?.dataset.label || '')
          whereInput.hidden = false
          whereInput.focus()
          setDesktopActive('where')
          return
        }
        const desktopWhereButton = event.target.closest('.desktop-location-option button')
        if (desktopWhereButton) {
          event.preventDefault()
          const item = desktopWhereButton.closest('.desktop-location-option')
          applyWhere(item?.dataset.label || '', '')
          setDesktopActive(null)
          return
        }
      })

      el.addEventListener('click', (event) => {
        const mobileWhatButton = event.target.closest('.mobile-what-option')
        if (mobileWhatButton) {
          applyWhat(mobileWhatButton.dataset.label || '')
          closeModal(whatModal)
          return
        }
        const mobileWhereButton = event.target.closest('.mobile-location-option')
        if (mobileWhereButton) {
          applyWhere(mobileWhereButton.dataset.label || '', '')
          closeModal(whereModal)
        }
      })

      document.addEventListener('mousedown', (event) => {
        if (!desktopForm.contains(event.target)) setDesktopActive(null)
      })
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          setDesktopActive(null)
          if (state.activeModal) closeModal(state.activeModal)
        }
      })

      Promise.all([
        fetchWhatCategories().catch(() => []),
        fetchLocations(24, '').catch(() => []),
      ]).then(([whatItems, whereItems]) => {
        state.whatItems = Array.isArray(whatItems) ? whatItems : []
        state.whereItems = Array.isArray(whereItems) ? whereItems : []
        syncInputs()
        renderDesktopWhat()
        renderDesktopWhere()
        renderMobileWhat()
        renderMobileWhere()
      }).catch(() => {
        syncInputs()
        renderDesktopWhat()
        renderDesktopWhere()
        renderMobileWhat()
        renderMobileWhere()
      })

      syncInputs()
      renderDesktopWhat()
      renderDesktopWhere()
      renderMobileWhat()
      renderMobileWhere()
    })
  } catch (err) {
    console.warn('[WOW] home search bar v4 bootstrap skipped', err)
  }
}

function closeHeaderDropdownExcept(source) {
  try {
    if (source !== 'account' && typeof window.__WOWCloseAccountDropdown === 'function') {
      window.__WOWCloseAccountDropdown();
    }
  } catch (_err) {}
  try {
    if (source !== 'cart' && typeof window.__WOWCloseCartDropdown === 'function') {
      window.__WOWCloseCartDropdown();
    }
  } catch (_err) {}
}

onDocumentReady(() => {
  runIdle(() => { try { initMegaMenu(); } catch (e) {} });
  runIdle(() => { try { initMobileMenu(); } catch (e) {} });
  runIdle(() => { try { ['home-template','home-sticky'].forEach(prefix => setupUltraSearchBar(prefix)); } catch (e) {} });
  runIdle(() => { try { mountSearchRangeCalendars(); } catch (e) {} });
  try { mountSearchBarV4(); } catch (e) {}
  runIdle(() => { try { initAccountDropdown(); } catch (e) {} });
  runIdle(() => { try { initHeaderSearchModal(); } catch (e) {} });
  runIdle(() => { try { initSubscriberForms(); } catch (e) {} });

  // Cart dropdown: hover on desktop shows mini cart; mobile click navigates
  const wrap = document.querySelector('.cart-wrap');
  const link = document.querySelector('.cart-wrap .cart-link');
  const panel = document.getElementById('cart-dropdown');
  if (wrap && link && panel) {
    function isDesktop(){ try { return window.matchMedia('(min-width: 992px)').matches } catch(_) { return true } }
    let loaded = false; let hideTimer = null;
    function money(n){ try{ var x=Number(n); if(x>=1000) x=x/100; return '£'+x.toFixed(2) }catch(_){ return '£0.00' } }
      function updateTotals(items){
        try{
          var sub = 0, count = 0;
          (items||[]).forEach(function(it){ var p = Number(it.price||0); if(p>=1000) p=p/100; var q = Number(it.qty||1)||1; sub += p*q; count += q; });
          var subEl = panel.querySelector('#cartdd-subtotal'); if(subEl) subEl.textContent = money(sub);
          var label = panel.querySelector('#cartCountLabel'); if(label) label.textContent = count>0 ? (count===1?'1 item':(count+' items')) : '';
          var hint = panel.querySelector('#freeShipHint'); if(hint){ try{ hint.textContent = 'Instant delivery'; }catch(_e){} }
        }catch(_){ }
      }
    function renderItems(items){
      try{
        const body = panel.querySelector('#cartdd-body');
        if(!body) return;
        if(!Array.isArray(items) || items.length===0){ body.innerHTML = '<div class="cartdd-empty mini-cart__empty">Your cart is empty</div>'; updateTotals([]); updateBadgeFrom([]); return; }
        body.innerHTML = items.map(function(it){
          var img = it.image ? '<div class="cartdd-img"><img src="'+String(it.image).replace(/"/g,'&quot;')+'" alt=""></div>' : '<div class="cartdd-img"></div>';
          var title = String(it.title||'').replace(/[&<>"']/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));
          var qty = Number(it.qty||1);
          var amt = (it.price!=null) ? money((Number(it.price)||0) * qty) : '';
          var variantRaw = (it.variant_label || it.subtitle || '').trim();
          var metaParts = [];
          if(variantRaw){
            var safeVariant = variantRaw.replace(/[&<>"']/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));
            metaParts.push(safeVariant);
          }
          metaParts.push('Qty '+qty);
          var removeBtn = '<button class="cartdd-remove remove-btn js-remove" type="button" aria-label="Remove item" data-remove="'+String(it.id||'')+'">'
            + '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">'
            +   '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />'
            + '</svg>'
            + '</button>';
          return '<div class="cartdd-item" data-id="'+String(it.id||'')+'">'
            + img
            + '<div class="cartdd-info"><a class="cartdd-title" href="'+(it.url||'#')+'">'+title+'</a><div class="cartdd-meta">'+metaParts.join(' • ')+'</div></div>'
            + '<div class="cartdd-amt">'+amt+'</div>'
            + removeBtn
            + '</div>';
        }).join('');
        updateTotals(items);
        updateBadgeFrom(items);
      }catch(_){ /* no-op */ }
    }
    function updateBadgeFrom(items){
      try{
        var badges = document.querySelectorAll('.cart-badge');
        if(!badges.length) return;
        var c=0; (items||[]).forEach(function(it){ c += Number(it.qty||1)||1; });
        badges.forEach(function(b){
          try{
            b.textContent=String(c);
            b.style.display=c>0?'inline-block':'none';
          }catch(_inner){}
        });
      }catch(_){ }
    }
    function readLocalCart(){
      try{
        var raw = localStorage.getItem('wow_cart'); if(!raw) return [];
        var data = JSON.parse(raw);
        var items = data && (data.items||data.cart||data) || [];
        if(!Array.isArray(items)) return [];
        return items.map(function(x){
          return {
            title:x.title||x.name,
            qty:Number(x.qty||x.quantity||1),
            price:x.price_min||x.price,
            image:x.image||x.img,
            url:x.url||x.href,
            id:x.id,
            variant_label:x.variant_label||x.options_label||''
          };
        });
      }catch(_){ return []; }
    }
    function writeLocalCart(items){
      try{
        var bag={ items: items||[] };
        (items||[]).forEach(function(it){ if(it && typeof it.id!=='undefined'){ bag[String(it.id)] = it; } });
        localStorage.setItem('wow_cart', JSON.stringify(bag));
      }catch(_){ }
      try {
        var cookieItems = [];
        (items||[]).forEach(function(it){
          if(!it || typeof it.id === 'undefined') return;
          cookieItems.push({
            id: String(it.id),
            product_id: it.product_id || null,
            variant_id: it.variant_id || null,
            variant_label: it.variant_label || '',
            source_version: it.source_version || it.meta?.source_version || null,
            title: it.title || '',
            price: Number(it.price || it.unit || 0),
            qty: Number(it.qty || 1) || 1,
            image: it.image || it.img || '',
            url: it.url || '#'
          });
        });
        if (cookieItems.length) {
          var encoded = encodeURIComponent(JSON.stringify(cookieItems));
          document.cookie = 'wow_cart=; Path=/; Max-Age=0; SameSite=Lax';
          document.cookie = 'wow_cart=' + encoded + '; Domain=.weofferwellness.co.uk; Path=/; Max-Age=' + (60*60*24*30) + '; SameSite=Lax';
        } else {
          document.cookie = 'wow_cart=; Path=/; Max-Age=0; SameSite=Lax';
          document.cookie = 'wow_cart=; Domain=.weofferwellness.co.uk; Path=/; Max-Age=0; SameSite=Lax';
        }
      } catch(_){ }
      try { window.dispatchEvent(new CustomEvent('wow:cart:change', { detail:{ items: items||[], source:'header:write' } })); } catch(_){ }
    }
    function removeFromLocalCart(id){
      try{
        var items=readLocalCart().filter(function(it){ return String(it.id)!==String(id); });
        writeLocalCart(items);
        try { window.dispatchEvent(new CustomEvent('wow:cart:change', { detail:{ items: items||[], source:'header:remove' } })); } catch(_){ }
        return items;
      }catch(_){ return []; }
    }
    function loadMini(){
      if (loaded) return; loaded = true;
      fetch('/api/cart/mini?t='+Date.now(), { headers:{ 'Accept':'application/json' }, credentials:'same-origin' })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
          var serverItems = Array.isArray(data?.items) ? data.items : [];
          var localItems = readLocalCart();
          var byId = new Map();
          var storeProductIds = new Set(localItems.filter(function(item){ return String(item?.source_version || item?.meta?.source_version || '').toLowerCase() === 'store'; }).map(function(item){ return String(item.product_id || item.id || ''); }));
          serverItems.forEach(function(item){
            if(!item || item.id == null) return;
            if(storeProductIds.has(String(item.product_id || item.id))) return;
            byId.set(String(item.id), item);
          });
          localItems.forEach(function(item){
            if(!item || item.id == null) return;
            var key = String(item.id);
            // Store products are browser-cart lines until checkout; never let
            // an empty/legacy server response erase them.
            if(String(item.source_version || item.meta?.source_version || '').toLowerCase() === 'store' || !byId.has(key)) byId.set(key, item);
          });
          var items = Array.from(byId.values());
          writeLocalCart(items);
          renderItems(items);
        })
        .catch(() => { renderItems(readLocalCart()); });
    }
      function show(){
        if(!isDesktop()) return;
        closeHeaderDropdownExcept('cart');
        loadMini();
        try{ var hint = panel.querySelector('#freeShipHint'); if(hint) hint.textContent = 'Instant delivery'; }catch(_){}
        panel.hidden = false;
      }
    function hide(){ panel.hidden = true; }
    // Defer showing/rotation to the upsell-aware handler below
    wrap.addEventListener('mouseenter', () => { if (hideTimer) { clearTimeout(hideTimer); hideTimer=null; } });
    wrap.addEventListener('mouseleave', () => { hideTimer = setTimeout(hide, 120); });
    // Prevent default on desktop clicks to keep dropdown open; on mobile it navigates
    link.addEventListener('click', (e) => { if (isDesktop()) { e.preventDefault(); show(); } });
    // Expose helpers for external triggers (e.g., add-to-cart)
    try { window.__cartDropdownShow = show; } catch(_){ }
    try { window.__WOWCloseCartDropdown = hide; } catch(_){ }
    try { window.__cartDropdownRender = function(items){ try{ renderItems(items); panel.hidden=false; }catch(_){ } } } catch(_){ }
    // Remove handler inside dropdown
    try{
      panel.addEventListener('click', function(e){
        var btn = e.target.closest('.js-remove,[data-remove]'); if(!btn) return;
        e.preventDefault(); e.stopPropagation();
        var id = btn.getAttribute('data-remove') || btn.dataset.remove || btn.getAttribute('data-id'); if(!id) return;
        var next = removeFromLocalCart(id); renderItems(next);
        // server remove in background
        try {
          var token=(document.querySelector('meta[name="csrf-token"]')?.content)||window.__csrfToken||'';
          fetch('/api/cart/remove', {
            method:'POST',
            headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':token },
            credentials:'same-origin',
            body: JSON.stringify({ id:id })
          }).then(() => fetchCountAndUpdateBadge()).catch(()=>{});
        } catch(_){ }
      });
    }catch(_){ }
    // Upsell loader
    try{
      var upsellLoaded = false;
      var upsellPool = [];
      var upsellIndex = 0;
      var headlineIndex = 0;
      function esc(s){ return String(s||'').replace(/[&<>"']/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c])); }
      function ensureHeadlineEl(){
        try{
          var el = panel.querySelector('#cartdd-upsell-headline');
          if(!el){
            el = document.createElement('div');
            el.id = 'cartdd-upsell-headline';
            el.className = 'cartdd-upsell-headline';
            el.style.fontWeight = '600';
            el.style.color = 'var(--ink-700)';
            el.style.padding = '8px 12px 0';
            var subtotal = panel.querySelector('.cartdd-subtotal');
            if (subtotal && subtotal.parentNode) {
              subtotal.insertAdjacentElement('afterend', el);
            } else {
              var ups = panel.querySelector('#cartdd-upsell');
              if (ups) ups.insertAdjacentElement('afterbegin', el); else panel.appendChild(el);
            }
          }
          return el;
        }catch(_){ return null; }
      }
      function slugifySegment(value){
        return String(value || '')
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '');
      }
      function buildUpsellUrl(item){
        if (item && item.url) return item.url;
        var formatSource = item && (
          item.format
          || (item.type && (item.type.slug || item.type.name))
          || item.type
          || 'therapies'
        );
        var modalitySource = item && (
          item.modality
          || (item.category && (item.category.slug || item.category.name))
          || item.category_name
          || item.category_label
          || (item.type && (item.type.modality || item.type.slug || item.type.name))
          || ''
        );
        var format = slugifySegment(formatSource);
        var modality = slugifySegment(modalitySource);
        var slug = slugifySegment(item && (item.slug || item.handle || item.title || item.name || item.id || ''));
        var sourceVersion = String((item && item.source_version) || (item && item.sourceType) || (item && item.source_type) || '').toLowerCase();
        var isStructuredOffering = sourceVersion === 'v3' || sourceVersion === 'offering';
        if (format && modality && slug) return '/' + format + '/' + modality + '/' + slug;
        if (format && slug) return '/' + format + '/' + slug;
        return '/' + (slug || 'offerings');
      }
      function sliceAndRender(pool){
        // rotate 3 items each time
        if (!Array.isArray(pool) || !pool.length) { renderUpsell([]); return; }
        var idsInCart = (readLocalCart()||[]).map(function(it){ return String(it.id||''); });
        var filtered = pool.filter(function(p){ return idsInCart.indexOf(String(p.id||''))===-1; });
        if (!filtered.length) filtered = pool.slice();
        var start = upsellIndex % filtered.length;
        var view = [];
        for (var i=0;i<Math.min(3, filtered.length);i++) view.push(filtered[(start+i)%filtered.length]);
        upsellIndex = (upsellIndex + 3) % filtered.length;
        renderUpsell(view);
      }
      function renderUpsell(list){
        try{
          var wrapU = panel.querySelector('#cartdd-upsell'); if(!wrapU) return;
          if(!Array.isArray(list) || !list.length){ wrapU.innerHTML = ''; return; }
          wrapU.innerHTML = list.map(function(it){
            var p = Number(it.price_min ?? it.price ?? 0); if(p>=1000) p=p/100;
            var img = it.image || (it.images && it.images[0]) || '';
            var url = buildUpsellUrl(it);
            var title = esc(it.title||'');
            return '<div class="upsell-item">'
              + (img?('<img src="'+img+'" alt="">'):'<div style="width:46px;height:46px;border-radius:8px;background:#f3f5f7;border:1px solid #eceff3"></div>')
              + '<div><p class="upsell-title">'+title+'</p><div class="upsell-price">'+money(p)+'</div></div>'
              + '<button class="btn-wow btn-wow--outline btn-sm js-add-to-cart" data-id="'+it.id+'" data-product-id="'+it.id+'" data-title="'+title+'" data-price="'+p.toFixed(2)+'" data-image="'+img+'" data-url="'+url+'">Add</button>'
            + '</div>';
          }).join('');
        }catch(_){ }
      }
      function deriveTypeFromCart(){
        try{
          var items = readLocalCart(); if(!items || !items.length) return '';
          var url = String(items[0].url||'');
          var m = url.match(/\/([a-z-]+)\//i); return m?m[1]:'';
        }catch(_){ return ''; }
      }
      function cartComposition(){
        try{
          var items = readLocalCart(); if(!items || !items.length) return 'unknown';
          var flags = items.map(function(it){ var t=(it.title||'')+ ' '+ (it.url||''); return /online/i.test(t); });
          var anyOnline = flags.some(Boolean); var anyOffline = flags.some(function(f){ return !f; });
          if (anyOnline && !anyOffline) return 'online-only';
          if (!anyOnline && anyOffline) return 'in-person-only';
          if (anyOnline && anyOffline) return 'mixed';
          return 'unknown';
        }catch(_){ return 'unknown'; }
      }
      function chooseHeadline(){
        var mode = cartComposition();
        var sets = {
          'online-only': [
            'Popular online picks right now',
            'Instant calm, no travel required',
            'More online favourites you’ll actually use',
            'Pair it with a quick reset',
            'Top-rated online sessions',
            'Online best-sellers this week',
              'Most booked online therapies',
            'Finish strong: add an online upgrade'
          ],
          'in-person-only': [
            'Popular near you',
            'Most booked in your area',
            'Wellness people nearby love',
            'Nearby favourites to match your booking',
            'Make a day of it',
            'Limited spots near you',
            'New in your area',
            'Top-rated nearby practitioners'
          ],
          'mixed': [
            'Complete the set: online + in-person',
            'Balance your week',
            'Before & after: prep online, go in-person',
            'Your calm, but smarter',
            'Most paired with what’s in your basket'
          ],
          'unknown': [ 'Complete your calm' ]
        };
        var list = sets[mode] || sets['unknown'];
        var text = list[ headlineIndex % list.length ];
        headlineIndex = (headlineIndex + 1) % list.length;
        return text;
      }
      function setHeadline(){ try{ var h = ensureHeadlineEl(); if(h){ h.textContent = chooseHeadline(); } }catch(_){ } }
      function loadUpsell(){ if(upsellLoaded) return; upsellLoaded=true;
        var seg = deriveTypeFromCart();
        var endpoint = seg ? ('/api/products?limit=12&sort=popular&type='+encodeURIComponent(seg)) : '/api/products?limit=12&sort=popular';
        fetch(endpoint, { headers:{ 'Accept':'application/json' }})
          .then(r=>r.json())
          .then(function(list){ upsellPool = Array.isArray(list)?list:[]; setHeadline(); sliceAndRender(upsellPool); })
          .catch(function(){ renderUpsell([]); });
      }
      // Only rotate when dropdown transitions from closed -> open.
      wrap.addEventListener('mouseenter', function(){
        var wasClosed = !!panel.hidden;
        show();
        if(!upsellLoaded){ loadUpsell(); return; }
        if (wasClosed) { setHeadline(); sliceAndRender(upsellPool); }
      });
    }catch(_){ }
  }

});
