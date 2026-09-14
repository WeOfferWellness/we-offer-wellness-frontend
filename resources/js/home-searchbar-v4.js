'use strict';

import { fetchWhatCategories } from './services/whatCategories';
import { fetchLocations } from './services/locations';

function onReady(callback) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback, { once: true });
    return;
  }
  callback();
}

function bootHomeSearchbarV4(root) {
    if (!root || root.dataset.wowMounted === '1') return;
    root.dataset.wowMounted = '1';

    const searchUrl = root.dataset.searchUrl || '/search';

    const state = {
      desktopWhereSelected: '',
      mobileWhat: '',
      mobileWhere: '',
      mobileWhereSelected: '',
      activeDesktop: null,
      activeModal: null,
      whatItems: [],
      whereItems: []
    };

    const $ = (selector, scope = root) => scope.querySelector(selector);
    const $$ = (selector, scope = root) => Array.from(scope.querySelectorAll(selector));

    function buildSearchUrl(what, where) {
      const url = new URL(searchUrl, window.location.origin);
      const whatValue = what.trim();
      const whereValue = where.trim();
      if (whatValue) url.searchParams.set('what', whatValue);
      if (whereValue === 'Near me') {
        url.searchParams.set('mode', 'near-me');
      } else if (whereValue) {
        url.searchParams.set('where', whereValue);
      }
      return `${url.pathname}${url.search}${url.hash}`;
    }

    function reportSearch(what, where) {
      const source = root.closest('.wow-search-page-hero')
        ? 'search-page'
        : (root.closest('.hero, [class*="hero"], [data-hero]') ? 'hero' : 'site-search');
      window.dispatchEvent(new CustomEvent('wow:search-submitted', {
        detail: { what: String(what || '').trim(), where: String(where || '').trim(), source },
      }));
    }

    function requestLocation() {
      if (!navigator.geolocation) {
        console.warn('Geolocation is not available in this browser.');
        return;
      }
      navigator.geolocation.getCurrentPosition(
              () => {},
              error => console.warn(`Unable to access your location: ${error.message}`),
              { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
      );
    }

    function replaceClasses(element, remove, add) {
      remove.forEach(c => element.classList.remove(c));
      add.forEach(c => element.classList.add(c));
    }

    // ----- Desktop -----
    const desktopForm = $('#wowsearch-desktop-search-form');
    const desktopShell = $('#wowsearch-desktop-search-shell');
    const whatField = $('#wowsearch-desktop-what-field');
    const whereField = $('#wowsearch-desktop-where-field');
    const whatInput = $('#wowsearch-desktop-what');
    const whereInput = $('#wowsearch-desktop-where');
    const whatDropdown = $('#wowsearch-desktop-what-dropdown');
    const whereDropdown = $('#wowsearch-desktop-where-dropdown');
    const desktopWhatList = $('#wowsearch-desktop-what-list');
    const desktopLocationList = $('#wowsearch-desktop-location-list');
    const clearWhat = $('#wowsearch-desktop-clear-what');
    const clearWhere = $('#wowsearch-desktop-clear-where');
    const nearMeChip = $('#wowsearch-desktop-near-me-chip');
    const whatHeading = $('#wowsearch-desktop-what-heading');
    const locationHeading = $('#wowsearch-desktop-location-heading');
    const locationHeadingWrap = $('#wowsearch-desktop-location-list-heading-wrap');
    const whatIcon = $('.wowsearch-desktop-what-icon');
    const whereIcon = $('.wowsearch-desktop-where-icon');

    if (!desktopForm || !desktopShell || !whatField || !whereField || !whatInput || !whereInput || !whatDropdown || !whereDropdown || !desktopWhatList || !desktopLocationList) {
      root.dataset.wowMounted = '0';
      return;
    }

    whatInput.value = root.dataset.initialQuery || whatInput.value || '';
    whereInput.value = root.dataset.initialWhere || whereInput.value || '';
    state.desktopWhereSelected = whereInput.value;
    state.mobileWhat = whatInput.value;
    state.mobileWhere = whereInput.value;
    state.mobileWhereSelected = whereInput.value;

    function setDesktopActive(which) {
      state.activeDesktop = which;
      const active = which === 'what' || which === 'where';
      desktopShell.classList.toggle('wowsearch-border-[rgba(155,165,180,0.45)]', !active);
      desktopShell.classList.toggle('wowsearch-border-[rgba(155,165,180,0.6)]', active);
      desktopShell.classList.toggle('wowsearch-shadow-[0_10px_30px_rgba(28,39,56,0.08)]', !active);
      desktopShell.classList.toggle('wowsearch-shadow-[0_0_0_3px_rgba(79,147,129,0.15),0_10px_30px_rgba(28,39,56,0.10)]', active);

      const whatActive = which === 'what';
      const whereActive = which === 'where';
      whatField.classList.toggle('wowsearch-bg-white', whatActive);
      whatField.classList.toggle('wowsearch-shadow-[0_2px_12px_rgba(16,24,40,0.06)]', whatActive);
      whatField.classList.toggle('wowsearch-hover:bg-black/[0.025]', !whatActive);
      whereField.classList.toggle('wowsearch-bg-white', whereActive);
      whereField.classList.toggle('wowsearch-shadow-[0_2px_12px_rgba(16,24,40,0.06)]', whereActive);
      whereField.classList.toggle('wowsearch-hover:bg-black/[0.025]', !whereActive);
      whatIcon.classList.toggle('wowsearch-text-[#4f9381]', whatActive);
      whatIcon.classList.toggle('wowsearch-text-[#8e9bb0]', !whatActive);
      whereIcon.classList.toggle('wowsearch-text-[#4f9381]', whereActive);
      whereIcon.classList.toggle('wowsearch-text-[#8e9bb0]', !whereActive);
      whatDropdown.hidden = !whatActive;
      whereDropdown.hidden = !whereActive;
      clearWhat.hidden = !(whatInput.value && whatActive);
      clearWhere.hidden = !(whereInput.value || state.desktopWhereSelected);
      if (whatActive) filterDesktopWhat();
      if (whereActive) filterDesktopWhere();
    }

    function createDesktopOption(item, type) {
      const option = document.createElement('li');
      const isWhat = type === 'what';
      const label = String(item.title || item.label || item.value || '').trim();
      option.className = isWhat ? 'wowsearch-desktop-what-option' : 'wowsearch-desktop-location-option';
      option.dataset.label = label;
      if (isWhat) option.dataset.cat = String(item.cat || item.type || 'Category').trim();

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'wowsearch-w-full wowsearch-flex wowsearch-items-center wowsearch-gap-3 wowsearch-px-4 wowsearch-py-2.5 wowsearch-hover:bg-[#f7f9fb] wowsearch-transition-colors wowsearch-text-left';

      const title = document.createElement('span');
      title.className = 'wowsearch-flex-1 wowsearch-text-[13.5px] wowsearch-text-[#1a202c] wowsearch-font-medium';
      title.textContent = label;
      button.append(title);

      if (isWhat) {
        const category = document.createElement('span');
        category.className = 'wowsearch-text-[10px] wowsearch-font-bold wowsearch-px-2 wowsearch-py-0.5 wowsearch-rounded-full wowsearch-bg-[#e8f5f1] wowsearch-text-[#2a5e52]';
        category.textContent = option.dataset.cat;
        button.append(category);
      }

      option.append(button);
      return option;
    }

    function renderDesktopOptions(list, items, type) {
      list.replaceChildren(...items.map(item => createDesktopOption(item, type)));
    }

    function filterDesktopWhat() {
      const query = whatInput.value.trim().toLowerCase();
      whatHeading.textContent = query
        ? 'Suggestions'
        : (state.whatItems.some(item => item.cat === 'Popular searches') ? 'Popular searches' : 'Popular experiences');
      const items = state.whatItems
        .filter(item => !query || `${item.title} ${item.label} ${item.value} ${item.cat} ${item.type}`.toLowerCase().includes(query))
        .slice(0, 8);
      renderDesktopOptions(desktopWhatList, items, 'what');
      whatDropdown.hidden = state.activeDesktop !== 'what' || items.length === 0;
    }

    function filterDesktopWhere() {
      const query = whereInput.value.trim().toLowerCase();
      locationHeading.textContent = query ? 'Matching' : 'Popular';
      const items = state.whereItems
        .filter(item => !item.online)
        .filter(item => !query || `${item.title} ${item.label} ${item.value} ${item.search}`.toLowerCase().includes(query))
        .slice(0, 8);
      renderDesktopOptions(desktopLocationList, items, 'where');
      locationHeadingWrap.hidden = !!query && items.length === 0;
    }

    function updateDesktopWhereDisplay() {
      const isNearMe = state.desktopWhereSelected === 'Near me';
      whereInput.hidden = isNearMe;
      nearMeChip.hidden = !isNearMe;
      clearWhere.hidden = !(whereInput.value || state.desktopWhereSelected);
    }

    whatField.addEventListener('click', event => {
      if (event.target.closest('button')) return;
      whatInput.focus();
    });
    whereField.addEventListener('click', event => {
      if (event.target.closest('button')) return;
      whereInput.focus();
    });
    whatInput.addEventListener('focus', () => setDesktopActive('what'));
    whereInput.addEventListener('focus', () => setDesktopActive('where'));
    whatInput.addEventListener('input', () => { clearWhat.hidden = !(whatInput.value && state.activeDesktop === 'what'); filterDesktopWhat(); });
    whereInput.addEventListener('input', () => { state.desktopWhereSelected = ''; clearWhere.hidden = !whereInput.value; filterDesktopWhere(); });

    whatDropdown.addEventListener('click', event => {
      const button = event.target.closest('.wowsearch-desktop-what-option button');
      if (!button || !whatDropdown.contains(button)) return;
      event.stopPropagation();
      const item = button.closest('.wowsearch-desktop-what-option');
      whatInput.value = item.dataset.label;
      setDesktopActive(null);
      whereInput.focus();
      setDesktopActive('where');
    });

    whereDropdown.addEventListener('click', event => {
      const button = event.target.closest('.wowsearch-desktop-location-option button');
      if (!button || !whereDropdown.contains(button)) return;
      event.stopPropagation();
      const value = button.closest('.wowsearch-desktop-location-option').dataset.label;
      whereInput.value = value;
      state.desktopWhereSelected = value;
      updateDesktopWhereDisplay();
      setDesktopActive(null);
    });

    $('#wowsearch-desktop-online').addEventListener('click', event => {
      event.stopPropagation();
      whereInput.value = 'Online';
      state.desktopWhereSelected = 'Online';
      updateDesktopWhereDisplay();
      setDesktopActive(null);
    });

    $('#wowsearch-desktop-use-location').addEventListener('click', event => {
      event.stopPropagation();
      whereInput.value = 'Near me';
      state.desktopWhereSelected = 'Near me';
      updateDesktopWhereDisplay();
      setDesktopActive(null);
      requestLocation();
    });

    clearWhat.addEventListener('click', event => {
      event.stopPropagation();
      whatInput.value = '';
      whatInput.focus();
      setDesktopActive('what');
      filterDesktopWhat();
    });
    clearWhere.addEventListener('click', event => {
      event.stopPropagation();
      whereInput.value = '';
      state.desktopWhereSelected = '';
      updateDesktopWhereDisplay();
      whereInput.focus();
      setDesktopActive('where');
    });

    desktopForm.addEventListener('submit', event => {
      event.preventDefault();
      if (!whatInput.value.trim()) { whatInput.focus(); setDesktopActive('what'); return; }
      reportSearch(whatInput.value, whereInput.value);
      window.location.href = buildSearchUrl(whatInput.value, whereInput.value);
    });

    document.addEventListener('mousedown', event => {
      if (!desktopForm.contains(event.target)) setDesktopActive(null);
    });

    // ----- Mobile -----
    const mobileForm = $('#wowsearch-mobile-search-form');
    const mobileWhatDisplay = $('#wowsearch-mobile-what-display');
    const mobileWhereDisplay = $('#wowsearch-mobile-where-display');
    const mobileNearMeChip = $('#wowsearch-mobile-near-me-chip');
    const mobileWhatIcon = $('.wowsearch-mobile-what-main-icon');
    const mobileWhereIcon = $('.wowsearch-mobile-where-main-icon');
    const whatModal = $('#wowsearch-mobile-what-modal');
    const whereModal = $('#wowsearch-mobile-where-modal');
    const mobileWhatInput = $('#wowsearch-mobile-what-input');
    const mobileWhereInput = $('#wowsearch-mobile-where-input');
    const mobileOnline = $('#wowsearch-mobile-online');
    const mobileLocationList = $('#wowsearch-mobile-location-list');

    $$('.wowsearch-mobile-modal-close').forEach(button => {
      const actions = document.createElement('div');
      actions.className = 'wowsearch-main-search-header-actions';
      actions.innerHTML = '<span class="wowsearch-main-search-esc" aria-hidden="true">ESC</span><button aria-label="Close search" class="wowsearch-main-search-close" data-wowsearch-main-close data-wowsearch-mobile-close type="button"><svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="18" xmlns="http://www.w3.org/2000/svg"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg></button>';
      button.replaceWith(actions);
    });

    function updateMobileMainDisplay() {
      if (state.mobileWhat) {
        mobileWhatDisplay.textContent = state.mobileWhat;
        replaceClasses(mobileWhatDisplay, ['wowsearch-text-[#687283]','wowsearch-font-normal'], ['wowsearch-text-[#111827]']);
        replaceClasses(mobileWhatIcon, ['wowsearch-text-[#8e9bb0]'], ['wowsearch-text-[#4f9381]']);
      } else {
        mobileWhatDisplay.textContent = 'Search therapies, events & more';
        replaceClasses(mobileWhatDisplay, ['wowsearch-text-[#111827]'], ['wowsearch-text-[#687283]','wowsearch-font-normal']);
        replaceClasses(mobileWhatIcon, ['wowsearch-text-[#4f9381]'], ['wowsearch-text-[#8e9bb0]']);
      }

      const where = state.mobileWhereSelected || state.mobileWhere;
      const isNearMe = where === 'Near me';
      mobileNearMeChip.hidden = !isNearMe;
      mobileWhereDisplay.hidden = isNearMe;
      if (!isNearMe) {
        mobileWhereDisplay.textContent = where || 'Near me, town or Online';
        if (where) replaceClasses(mobileWhereDisplay, ['wowsearch-text-[#687283]','wowsearch-font-normal'], ['wowsearch-text-[#111827]']);
        else replaceClasses(mobileWhereDisplay, ['wowsearch-text-[#111827]'], ['wowsearch-text-[#687283]','wowsearch-font-normal']);
      }
      replaceClasses(mobileWhereIcon, [where ? 'wowsearch-text-[#8e9bb0]' : 'wowsearch-text-[#4f9381]'], [where ? 'wowsearch-text-[#4f9381]' : 'wowsearch-text-[#8e9bb0]']);
    }

    function markMobileSelections() {
      $$('.wowsearch-mobile-what-option', whatModal).forEach(button => {
        const selected = button.dataset.label === state.mobileWhat;
        button.classList.toggle('wowsearch-bg-[#f0faf7]', selected);
        const check = $('.wowsearch-selection-check', button);
        check.hidden = !selected;
        check.classList.toggle('wowsearch-flex', selected);
      });
      const where = state.mobileWhereSelected;
      mobileOnline.classList.toggle('wowsearch-bg-[#f0faf7]', where === 'Online');
      $$('.wowsearch-mobile-location-option', whereModal).forEach(button => {
        const selected = button.dataset.label === where;
        button.classList.toggle('wowsearch-bg-[#f0faf7]', selected);
        const check = $('.wowsearch-selection-check', button);
        check.hidden = !selected;
        check.classList.toggle('wowsearch-flex', selected);
      });
    }

    function filterMobileWhat() {
      const query = mobileWhatInput.value.trim().toLowerCase();
      let visible = 0;
      $$('.wowsearch-mobile-what-option', whatModal).forEach(button => {
        const matches = !query || button.dataset.label.toLowerCase().includes(query) || button.dataset.cat.toLowerCase().includes(query);
        button.hidden = !matches || visible >= 8;
        if (!button.hidden) visible += 1;
      });
    }

    function filterMobileWhere() {
      const query = mobileWhereInput.value.trim().toLowerCase();
      const items = state.whereItems
        .filter(item => !item.online)
        .filter(item => !query || `${item.title} ${item.label} ${item.value} ${item.search}`.toLowerCase().includes(query))
        .slice(0, 8);

      mobileLocationList.replaceChildren(...items.map(item => {
        const label = String(item.title || item.label || item.value || '').trim();
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.label = label;
        button.className = 'wowsearch-mobile-location-option wowsearch-w-full wowsearch-flex wowsearch-items-center wowsearch-gap-3.5 wowsearch-px-3 wowsearch-py-[14px] wowsearch-border-b wowsearch-border-[#eef0f3] wowsearch-text-left wowsearch-last:border-0';

        const title = document.createElement('span');
        title.className = 'wowsearch-text-[15px] wowsearch-text-[#1a202c] wowsearch-font-medium wowsearch-flex-1';
        title.textContent = label;
        button.append(title);

        const check = document.createElement('span');
        check.hidden = true;
        check.className = 'wowsearch-selection-check wowsearch-w-5 wowsearch-h-5 wowsearch-rounded-full wowsearch-bg-[#4f9381] wowsearch-items-center wowsearch-justify-center';
        button.append(check);

        return button;
      }));
    }

    function openModal(modal, input) {
      if (state.activeModal) closeModal(state.activeModal);
      state.activeModal = modal;
      modal.hidden = false;
      $('.wowsearch-mobile-sheet', modal).style.height = '88dvh';
      document.body.style.overflow = 'hidden';
      if (modal === whatModal) { mobileWhatInput.value = state.mobileWhat; filterMobileWhat(); }
      else { mobileWhereInput.value = ''; filterMobileWhere(); }
      markMobileSelections();
      window.setTimeout(() => input.focus(), 100);
    }

    function closeModal(modal) {
      modal.hidden = true;
      $('.wowsearch-mobile-sheet', modal).style.height = '88dvh';
      if (state.activeModal === modal) state.activeModal = null;
      if (!state.activeModal) document.body.style.overflow = '';
      if (modal === whereModal) mobileWhereInput.value = '';
    }

    function enableSheetDrag(modal) {
      const handle = $('.wowsearch-mobile-sheet-handle', modal);
      const sheet = $('.wowsearch-mobile-sheet', modal);
      let drag = null;
      handle.addEventListener('pointerdown', event => {
        handle.setPointerCapture(event.pointerId);
        drag = { y: event.clientY, height: parseFloat(sheet.style.height) || 88 };
      });
      handle.addEventListener('pointermove', event => {
        if (!drag) return;
        const delta = ((drag.y - event.clientY) / window.innerHeight) * 100;
        const height = Math.min(100, Math.max(88, drag.height + delta));
        sheet.style.height = `${height}dvh`;
      });
      const finish = () => {
        if (!drag) return;
        drag = null;
        const height = parseFloat(sheet.style.height) || 88;
        sheet.style.height = `${height > 94 ? 100 : 88}dvh`;
      };
      handle.addEventListener('pointerup', finish);
      handle.addEventListener('pointercancel', finish);
    }

    $('#wowsearch-mobile-open-what').addEventListener('click', () => openModal(whatModal, mobileWhatInput));
    $('#wowsearch-mobile-open-where').addEventListener('click', () => openModal(whereModal, mobileWhereInput));
    $$('.wowsearch-mobile-modal-overlay, [data-wowsearch-mobile-close]').forEach(element => element.addEventListener('click', () => closeModal(element.closest('[id$="-modal"]'))));
    enableSheetDrag(whatModal);
    enableSheetDrag(whereModal);

    mobileWhatInput.addEventListener('input', filterMobileWhat);
    mobileWhereInput.addEventListener('input', filterMobileWhere);

    $$('.wowsearch-mobile-what-option').forEach(button => button.addEventListener('click', () => {
      state.mobileWhat = button.dataset.label;
      updateMobileMainDisplay();
      markMobileSelections();
      closeModal(whatModal);
    }));

    $('#wowsearch-mobile-use-location').addEventListener('click', () => {
      state.mobileWhere = 'Near me';
      state.mobileWhereSelected = 'Near me';
      updateMobileMainDisplay();
      markMobileSelections();
      requestLocation();
      closeModal(whereModal);
    });

    $('#wowsearch-mobile-online').addEventListener('click', () => {
      state.mobileWhere = 'Online';
      state.mobileWhereSelected = 'Online';
      updateMobileMainDisplay();
      markMobileSelections();
      closeModal(whereModal);
    });

    mobileLocationList.addEventListener('click', event => {
      const button = event.target.closest('.wowsearch-mobile-location-option');
      if (!button || !mobileLocationList.contains(button)) return;
      state.mobileWhere = button.dataset.label;
      state.mobileWhereSelected = button.dataset.label;
      updateMobileMainDisplay();
      markMobileSelections();
      closeModal(whereModal);
    });

    mobileForm.addEventListener('submit', event => {
      event.preventDefault();
      if (state.mobileWhat.trim()) {
        reportSearch(state.mobileWhat, state.mobileWhere);
        window.location.href = buildSearchUrl(state.mobileWhat, state.mobileWhere);
      }
    });

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && state.activeModal) closeModal(state.activeModal);
    });

    // Hero carousel transforms otherwise make fixed sheets relative to the hero.
    [whereModal, whatModal].forEach(modal => {
      modal.classList.add('wowsearch-component-scope');
      document.body.append(modal);
    });

    updateDesktopWhereDisplay();
    updateMobileMainDisplay();
    markMobileSelections();
    Promise.all([
      fetchWhatCategories(),
      fetchLocations(1000)
    ]).then(([whatItems, whereItems]) => {
      state.whatItems = Array.isArray(whatItems) ? whatItems : [];
      state.whereItems = Array.isArray(whereItems) ? whereItems : [];
      filterDesktopWhat();
      filterDesktopWhere();
      filterMobileWhere();
    });

    filterDesktopWhat();
    filterDesktopWhere();
  }

onReady(() => {
  try {
    document.querySelectorAll('[data-wow-home-searchbar-v4]').forEach(bootHomeSearchbarV4);
  } catch (error) {
    console.warn('[WOW] home searchbar v4 failed to initialise', error);
  }
});
