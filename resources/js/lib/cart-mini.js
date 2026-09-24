import { trackCommerce } from './wow-analytics'

// Lightweight cart interactions: add-to-cart, badge count, and dropdown open
(() => {
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }
  function isDesktop(){ try{ return window.matchMedia('(min-width: 992px)').matches }catch(_){ return true } }
  function csrf(){
    try {
      return document.querySelector('meta[name="csrf-token"]').content || window.__csrfToken || ''
    } catch(_){ return window.__csrfToken || '' }
  }
  function cookie(name){
    try{
      const match = document.cookie.split(';').map(row => row.trim()).find(row => row.startsWith(name + '='));
      return match ? match.slice(name.length + 1) : '';
    }catch(_){ return '' }
  }
  function money(n){ try{ var x=Number(n); return '£'+x.toFixed(2) }catch(_){ return '£0.00' } }

  const LS_KEY = 'wow_cart';
  function loadLS(){ try{ return JSON.parse(localStorage.getItem(LS_KEY)||'{}')||{} }catch(_){ return {} } }
  function saveLS(obj){ try{ localStorage.setItem(LS_KEY, JSON.stringify(obj||{})); }catch(_){ } }
  function getItems(){
    const o = loadLS();
    if (Array.isArray(o.items)) return o.items;
    const out=[]; Object.keys(o||{}).forEach(k=>{ if(k!=='items'){ out.push(o[k]); } }); return out;
  }
  function setItems(items){
    const bag = { items: items||[] };
    (items||[]).forEach(it => {
      if(!it) return;
      const key = String(it.id ?? it.cartKey ?? (it.variant_id ? 'v:'+it.variant_id : (it.product_id ? 'p:'+it.product_id : '')));
      if(!key) return;
      const payload = { ...it, id: key };
      bag[key] = payload;
    });
    saveLS(bag);
    try {
      const detail = { items: getItems(), count: countItems(), source: 'mini:set' };
      window.dispatchEvent(new CustomEvent('wow:cart:change', { detail }));
    } catch(_){ }
  }
  function countItems(){ return getItems().reduce((sum,it)=> sum + (Number(it.qty||1)||1), 0); }
  function upsertItem(newItem){
    const items = getItems();
    const cartKey = String(newItem.id || newItem.cartKey || (newItem.variantId ? 'v:'+newItem.variantId : (newItem.productId ? 'p:'+newItem.productId : '')) || '');
    if(!cartKey) return;
    const idx = items.findIndex(it => String(it.id||it.cartKey||'') === cartKey);
    const base = {
      id: cartKey,
      cartKey,
      title: newItem.title,
      price: newItem.price,
      image: newItem.image,
      url: newItem.url,
      product_id: newItem.productId || newItem.product_id || null,
      variant_id: newItem.variantId || newItem.variant_id || null,
      variant_label: newItem.variantLabel || newItem.variant_label || '',
      source_version: newItem.sourceVersion || newItem.source_version || null,
      meta: newItem.meta || {},
    };
    const qty = Number(newItem.qty||1)||1;
    if(idx>=0){
      items[idx].qty = Number(items[idx].qty||1) + qty;
      items[idx] = { ...items[idx], ...base };
    }
    else {
      items.push({ ...base, qty });
    }
    setItems(items);
  }

  function updateBadgeUI(n){
    try{
      const badges = qsa('.cart-badge');
      if(!badges.length) return;
      const c = Number(n||0);
      badges.forEach((b) => {
        try{
          b.textContent = String(c);
          b.style.display = c>0 ? 'inline-block' : 'none';
        }catch(_inner){}
      });
    }catch(_){ }
  }
  function fetchCountAndUpdateBadge(){
    return fetch('/api/cart/count', { credentials:'same-origin' })
      .then(r=>r.json())
      .then(j => { updateBadgeUI(Number(j.count||0)); })
      .catch(()=>{ updateBadgeUI(countItems()); });
  }

  // Expose dropdown open/render hooks if available
  const showDropdown = () => { try{ (window.__cartDropdownShow||function(){})(); }catch(_){ } };
  const renderDropdownFromLS = () => {
    try{
      const items = getItems();
      (window.__cartDropdownRender||function(){ })(items);
    }catch(_){ }
  };

  function serverAdd(payload){
    // Store V3 products are owned by the Studio backend and are carried by
    // the browser cart until checkout; the legacy frontend cart endpoint
    // cannot resolve their IDs as legacy products.
    if (String(payload.sourceVersion || payload.source_version || '').toLowerCase() === 'store') {
      return Promise.resolve({ ok: true, local_only: true });
    }
    const qty = Number(payload.qty || 1) || 1;
    return fetch('/api/cart/add', {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf() },
      credentials:'same-origin',
      body: JSON.stringify({ id: payload.productId || payload.id, variant_id: payload.variantId || null, variant_label: payload.variantLabel || null, source_version: payload.sourceVersion || payload.source_version || null, qty })
    }).then(r => r.json()).catch(()=>{ throw new Error('server failed'); });
  }

  function addToCart(payload, opts){
    const options = opts || {}
    const qty = Number(payload.qty || 1) || 1;
    // Always update local storage for resilience
    upsertItem({ ...payload, qty });
    // Try server add; on success refresh count; on failure, use LS count
    const isStoreProduct = String(payload.sourceVersion || payload.source_version || '').toLowerCase() === 'store';
    const request = serverAdd({ productId: payload.productId || payload.id, variantId: payload.variantId || null, variantLabel: payload.variantLabel || null, sourceVersion: payload.sourceVersion || payload.source_version || null, qty }).then(()=>{
      if (isStoreProduct) updateBadgeUI(countItems());
      else fetchCountAndUpdateBadge();
      return true;
    }).catch(()=>{
      updateBadgeUI(countItems());
      return false;
    }).finally(()=>{
      if (options.openCart && isDesktop()) { renderDropdownFromLS(); showDropdown(); }
      try {
        const detail = { items: getItems(), count: countItems(), source: 'mini:add' };
        window.dispatchEvent(new CustomEvent('wow:cart:change', { detail }));
      } catch(_){ }
    });
    return request;
  }

  function navigateToUrl(btn){
    const url = btn.getAttribute('data-url') || '';
    if (!url) return;
    try {
      window.location.assign(url);
    } catch(_){
      window.location.href = url;
    }
  }

  // Keep dropdown and badge in sync when other pages change the cart
  try{
    window.addEventListener('wow:cart:change', function(){
      try { updateBadgeUI(countItems()); renderDropdownFromLS(); } catch(_){ }
    });
  }catch(_){ }

  function handleAddFromBtn(btn, ev){
    if (ev){ try{ ev.preventDefault(); ev.stopPropagation(); }catch(_){} }
    const productId = btn.getAttribute('data-product-id') || btn.getAttribute('data-product') || btn.getAttribute('data-id');
    const variantId = btn.getAttribute('data-variant-id') || '';
    const id = btn.getAttribute('data-id');
    const title = btn.getAttribute('data-title')||'';
    const price = Number(btn.getAttribute('data-price')||'0');
    const image = btn.getAttribute('data-image')||'';
    const url = btn.getAttribute('data-url')||'';
    const qty = Number(btn.getAttribute('data-qty')||'1') || 1;
    const variantLabel = btn.getAttribute('data-variant-label')||'';
    const priceIncludesMarkup = btn.getAttribute('data-price-includes-marketplace-markup') === '1';
    const openCart = btn.classList.contains('js-open-cart') || btn.classList.contains('open-cart-dropdown');
    const cartKey = variantId ? `v:${variantId}` : `p:${productId || id || ''}`;
    const resolvedId = cartKey || id;
    if(!resolvedId) return null;
    try {
      trackCommerce('wow_v3_add_to_cart', {
        items: [{
          id: resolvedId,
          title,
          price,
          qty,
          product_id: productId || id || null,
          variant_id: variantId || null,
          variant_label: variantLabel || '',
          source_version: btn.getAttribute('data-source-version') || null,
        }],
        currency: 'GBP',
        value: Number(price || 0) * qty,
        item_count: qty,
        interaction_source: 'mini',
      })
    } catch(_){}
    return addToCart({ id: resolvedId, cartKey: resolvedId, productId: productId || id, variantId: variantId || null, variantLabel, sourceVersion: btn.getAttribute('data-source-version') || null, title, price, image, url, qty, meta: priceIncludesMarkup ? { price_includes_marketplace_markup: true } : {} }, { openCart });
  }
  // Delegate add-to-cart clicks (capture to beat anchor navigation)
  document.addEventListener('click', function(e){
    const detailsBtn = e.target.closest('.js-view-details');
    if (detailsBtn) {
      e.preventDefault();
      e.stopPropagation();
      navigateToUrl(detailsBtn);
      return;
    }

    const btn = e.target.closest('.js-add-to-cart');
    if(!btn) return;
    handleAddFromBtn(btn, e);
  }, true);
  function goToCart(){
    try { window.location.assign('/cart'); }
    catch(_){ window.location.href = '/cart'; }
  }
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.js-buy-now');
    if(!btn) return;

    if (btn.getAttribute('data-no-cart') === '1') {
      e.preventDefault();
      e.stopPropagation();
      navigateToUrl(btn);
      return;
    }

    const res = handleAddFromBtn(btn, e);
    const finish = () => { goToCart(); };
    if(res && typeof res.finally === 'function'){
      res.finally(finish);
    } else {
      finish();
    }
  }, true);
  // Expose global helper for inline fallbacks
  try{ window.WOW_addToCart = function(el){ handleAddFromBtn(el); return false; }; }catch(_){ }

  // On load, set badge from server or LS
  fetchCountAndUpdateBadge();
})();
