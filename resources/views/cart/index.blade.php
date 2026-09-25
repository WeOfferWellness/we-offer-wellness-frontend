@extends('layouts.app')

@section('content')
@php
  $items = session('cart.items', []);
  $serverCart = [];
  foreach (($items ?? []) as $id => $it) {
      $p = (float)($it['price'] ?? 0);
      $lineProductId = $it['product_id'] ?? (is_numeric($id) ? (int)$id : (\Illuminate\Support\Str::startsWith((string)$id, 'p:') ? (int)substr((string)$id, 2) : null));
      $serverCart[] = [
          'id'    => (string)$id,
          'product_id' => $lineProductId,
          'vendor_id' => $it['vendor_id'] ?? null,
          'variant_id' => $it['variant_id'] ?? null,
          'variant_label' => (string)($it['variant_label'] ?? ''),
          'title' => (string)($it['title'] ?? 'Item'),
          'url'   => (string)($it['url'] ?? '#'),
          'img'   => (string)($it['image'] ?? ''),
          'unit'  => round($p, 2),
          'qty'   => (int)($it['qty'] ?? 1),
          // Keep the booking selection intact until CheckoutController has
          // validated the reservation and created the Stripe line item.
          'meta' => is_array($it['meta'] ?? null) ? $it['meta'] : [],
          'booking' => is_array($it['booking'] ?? null) ? $it['booking'] : [],
          'selected' => is_array($it['selected'] ?? null) ? $it['selected'] : [],
          'group_count' => $it['group_count'] ?? null,
          'reservation_id' => $it['reservation_id'] ?? null,
          'hold_expires_at' => $it['hold_expires_at'] ?? null,
          'location' => $it['location'] ?? null,
          'source_version' => $it['source_version'] ?? null,
      ];
  }
@endphp

<section class="section">
  <div class="container-page">
    <div class="kicker">Your cart</div>
    <h1 class="mb-1">Ready to book</h1>
    <p class="lead-cart">Secure, safe and flexible — you can reschedule when needed.</p>

<div id="cartGrid" class="cart-grid {{ empty($serverCart) ? 'is-empty' : '' }}">
      <div class="cart-main card glass" id="cartMain">
        <div class="cart-head" id="cartHead">
          <div>Item</div><div>Qty</div><div>Price</div>
        </div>
        <div class="cart-body" id="cartBody"></div>
        <div class="cart-foot" id="cartFoot">
          <a href="/search" class="link-wow">Continue shopping</a>
          <button class="btn-wow btn-wow--outline" id="clearCartBtn" type="button">Clear cart</button>
        </div>
      </div>

      <aside class="cart-side card glass" id="checkout">
        <div class="sum-head" id="sumHeadTitle">Your cart</div>
        <div class="sum-body">
          <div class="panel" id="summaryWrap" style="{{ empty($serverCart) ? 'display:none' : '' }}">
            <div class="sum-row"><span>Subtotal</span><strong id="sum-subtotal">£0.00</strong></div>
            <div class="sum-row"><span>Discounts</span><strong id="sum-discount">-£0.00</strong></div>
            <div class="sum-row muted"><span>Taxes</span><span>Included where applicable</span></div>
            <div class="sum-sep"></div>
            <div class="sum-row total"><span>Total</span><strong id="sum-total">£0.00</strong></div>

            <div class="promo">
              <label for="promo-code">Promo code</label>
              <div class="promo-inline">
                <input id="promo-code" type="text" placeholder="Enter code" value="{{ session('cart_promo_code') }}">
                <button type="button" class="btn-wow btn-wow--outline" id="apply-promo">Apply</button>
              </div>
              <div id="promo-msg" class="promo-msg"></div>
            </div>

            <button class="btn-wow btn-wow--primary w-100" id="checkoutBtn" disabled>Continue to checkout</button>
            <div class="trust-hints">
              <div class="hint"><span class="dot"></span>Secure checkout</div>
              <div class="hint"><span class="dot"></span>Free reschedule window</div>
            </div>

            <div class="upsell" id="upsellFull">
              <div class="upsell-head">
                <strong>Complete your calm</strong>
                <small>Frequently added</small>
              </div>
              <div class="upsell-list" id="upsellListFull"></div>
            </div>
          </div>

          <div class="panel empty-wrap" id="emptyWrap" style="{{ empty($serverCart) ? '' : 'display:none' }}">
            <div class="empty-hero">
              <div class="empty-illu" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                  <path d="M7 8V7a5 5 0 0 1 10 0v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                  <path d="M6.5 8h11l1 12.5a2 2 0 0 1-2 2H7.5a2 2 0 0 1-2-2L6.5 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path>
                </svg>
              </div>
              <div class="empty-hero__body">
                <div class="empty-copy">
                  <h2>Your cart is empty</h2>
                  <p>Add a therapy and you’re good to go.</p>
                </div>
                <div class="empty-actions">
                  <a class="btn-wow btn-wow--primary" href="/search">Browse therapies</a>
                </div>
              </div>
            </div>

            <div class="upsell" id="upsellEmpty">
              <div class="upsell-head">
                <strong>Recommended for you</strong>
                <small>Quick add</small>
              </div>
              <div class="upsell-list" id="upsellListEmpty"></div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

<div id="guestCheckoutModal" class="guest-modal" aria-hidden="true">
  <div class="guest-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="guestModalTitle">
    <button type="button" class="btn-wow btn-wow--soft is-square btn-sm guest-modal__close" aria-label="Close" data-guest-close>×</button>
    <h2 id="guestModalTitle">Almost there</h2>
    <p>Please tell us who this order is for and where to send the confirmation. You can create an account later with the same email.</p>
    <form id="guestCheckoutForm">
      <label for="guestFirstName">First name <span aria-hidden="true">*</span></label>
      <input id="guestFirstName" type="text" required placeholder="First name" autocomplete="given-name">
      <label for="guestLastName">Last name</label>
      <input id="guestLastName" type="text" placeholder="Last name" autocomplete="family-name">
      <label for="guestEmail">Email address <span aria-hidden="true">*</span></label>
      <input id="guestEmail" type="email" required placeholder="you@example.com" autocomplete="email">
      <div id="guestError" class="guest-modal__error" role="alert"></div>
      <button type="submit" class="btn-wow btn-wow--primary" id="guestSubmitBtn">Continue as guest</button>
      <div class="guest-modal__links">
        <a href="{{ route('login', ['redirect' => '/cart']) }}" class="link-wow">Log in instead</a>
        <a href="{{ route('register', ['redirect' => '/cart']) }}" class="link-wow">Create an account</a>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const isAuthed = !!@json(auth()->check());
  function cookie(name){
    try{
      var match = document.cookie.split(';').map(function(row){ return row.trim(); }).find(function(row){ return row.startsWith(name+'='); });
      return match ? match.slice(name.length + 1) : '';
    }catch(e){
      return '';
    }
  }
  function csrfToken(){
    try {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.__csrfToken || ''
    } catch(_){ return window.__csrfToken || '' }
  }
  function post(url, data){
    var token = csrfToken();
    return fetch(url, {
      method:'POST',
      headers:{ 'Content-Type':'application/json','Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':token },
      body: JSON.stringify(data||{}),
      credentials:'same-origin'
    }).then(r=>r.json());
  }
  function trackCommerce(eventName, payload){
    try{
      if (window.WOWAnalytics && typeof window.WOWAnalytics.trackCommerce === 'function') {
        return window.WOWAnalytics.trackCommerce(eventName, payload || {});
      }
      if (window.WOWAnalytics && typeof window.WOWAnalytics.track === 'function') {
        return window.WOWAnalytics.track(eventName, payload || {});
      }
      if (typeof window.gtag === 'function') {
        var params = Object.assign({ flow_version: 'v3', wow_event_name: eventName }, payload || {});
        window.gtag('event', eventName, params);
        return true;
      }
    }catch(_){}
    return false;
  }
  function analyticsItems(list){
    return (list || []).map(function(it){
      return {
        id: String(it.id || it.product_id || it.variant_id || ''),
        title: String(it.title || 'Item'),
        price: Number(it.unit ?? it.price ?? 0) || 0,
        qty: Math.max(1, Number(it.qty || 1) || 1),
        product_id: it.product_id || null,
        variant_id: it.variant_id || null,
        variant_label: it.variant_label || '',
        source_version: it.source_version || null,
      };
    });
  }
  function money(n){ try{ return '£'+Number(n||0).toFixed(2) }catch(_){ return '£0.00' } }
  function mapEntry(x){
    if(!x) return null;
    var rawId = x.id ?? x.cartKey ?? (x.variant_id ? 'v:'+x.variant_id : (x.product_id ? 'p:'+x.product_id : x.variantId ? 'v:'+x.variantId : (x.productId ? 'p:'+x.productId : '')));
    var id = String(rawId ?? '');
    if(!id){ id = 'p:'+String(x.product_id || x.productId || Math.random().toString(36).slice(2)); }
    var rawPrice = Number(x.price_min ?? x.price ?? x.unit ?? 0);
    var variantLabel = x.variant_label || x.variantLabel || x.options_label || '';
    var meta = (x.meta && typeof x.meta === 'object') ? x.meta : {};
    if (x.booking && typeof x.booking === 'object' && !meta.booking) meta.booking = x.booking;
    if (Array.isArray(x.selected) && !meta.selected) meta.selected = x.selected;
    if ((x.groupCount ?? x.group_count) != null && meta.group_count == null && meta.groupCount == null) meta.group_count = x.groupCount ?? x.group_count;
    if ((x.reservationId ?? x.reservation_id) != null && meta.reservation_id == null && meta.reservationId == null) meta.reservation_id = x.reservationId ?? x.reservation_id;
    if ((x.holdExpiresAt ?? x.hold_expires_at) != null && meta.hold_expires_at == null && meta.holdExpiresAt == null) meta.hold_expires_at = x.holdExpiresAt ?? x.hold_expires_at;
    if (x.location && !meta.location) meta.location = x.location;
    if ((x.source_version ?? x.sourceVersion) != null && meta.source_version == null) meta.source_version = x.source_version ?? x.sourceVersion;
    return {
      id: id,
      title: x.title || x.name || 'Item',
      url: x.url || x.href || '#',
      img: x.image || x.img || '',
      unit: Number(rawPrice)||0,
      qty: Number(x.qty || x.quantity || 1) || 1,
      product_id: x.product_id || x.productId || meta.product_id || null,
      variant_id: x.variant_id || x.variantId || meta.variant_id || null,
      variant_label: variantLabel || meta.variant_label || '',
      meta: meta,
      source_version: x.source_version || x.sourceVersion || meta.source_version || null,
    };
  }
  function readLocalCart(){
    try{
      var raw = localStorage.getItem('wow_cart');
      if (!raw) raw = localStorage.getItem('wow_cart_v1');
      if (raw){
        var data = JSON.parse(raw);
        var items = (data && (data.items||data.cart||data)) || [];
        if (Array.isArray(items)) return items.map(mapEntry).filter(Boolean);
      }
      var c = decodeURIComponent(cookie('wow_cart')||'');
      if (c){ var obj = JSON.parse(c); var arr = Array.isArray(obj) ? obj : Object.values(obj||{}); return (arr||[]).filter(Boolean).map(mapEntry).filter(Boolean); }
    }catch(_){ }
    return [];
  }

  // The server session is authoritative after a V5 slot hold is added.  Do
  // not let an unrelated/stale localStorage cart hide it on the cart page.
  var cart = @json($serverCart);
  try{ if(!cart.length){ cart = readLocalCart() } }catch(_){ }
  var promo = { code:"", pct:0 };
  trackViewCart();

  var grid = document.getElementById('cartGrid');
  var cartBody = document.getElementById('cartBody');
  var cartHead = document.getElementById('cartHead');
  var cartFoot = document.getElementById('cartFoot');
  var sumHeadTitle = document.getElementById('sumHeadTitle');
  var sumSubtotal = document.getElementById('sum-subtotal');
  var sumDiscount = document.getElementById('sum-discount');
  var sumTotal = document.getElementById('sum-total');
  var upsellListFull = document.getElementById('upsellListFull');
  var upsellListEmpty = document.getElementById('upsellListEmpty');
  var upsellLoaded = false; var upsellPool = [];
  var viewCartTracked = false;

  function subtotal(){ return cart.reduce(function(s,it){ return s + (Number(it.unit||0) * Number(it.qty||1)); }, 0); }
  function discountAmount(){ return subtotal() * (promo.pct||0); }
  function feeBase(){ return Math.max(0, subtotal() - discountAmount()); }
    function total(){ return feeBase(); }
  function trackViewCart(){
    if (viewCartTracked) return;
    viewCartTracked = true;
    trackCommerce('wow_v3_view_cart', {
      items: analyticsItems(cart),
      currency: 'GBP',
      value: total(),
      item_count: cart.reduce(function(sum, it){ return sum + (Number(it.qty||1) || 1); }, 0),
      cart_subtotal: subtotal(),
      cart_total: total(),
      cart_state: cart.length ? 'filled' : 'empty',
      checkout_type: isAuthed ? 'account' : 'guest',
      source: 'blade-cart',
    });
  }

  function writeLocalFromCart(){
    try{
      var items = (cart||[]).map(function(it){
        var meta = (it && typeof it.meta === 'object') ? it.meta : {};
        return {
          id:String(it.id),
          product_id: it.product_id || meta.product_id || null,
          variant_id: it.variant_id || meta.variant_id || null,
          variant_label: it.variant_label || meta.variant_label || '',
          title:String(it.title||''),
          qty:Number(it.qty||1)||1,
          price:Number(it.unit||0),
          image:it.img||'',
          url:it.url||'#',
          meta: meta
        };
      });
      var bag = { items: items };
      items.forEach(function(it){ bag[String(it.id)] = it; });
      localStorage.setItem('wow_cart', JSON.stringify(bag));
      // CartController owns the encrypted wow_cart cookie. Writing a plain
      // JavaScript cookie here overwrote it and made a successful V5 add look
      // empty after the next request. Local storage remains a client fallback.
      try { window.dispatchEvent(new CustomEvent('wow:cart:change', { detail:{ items: items, source:'cart:page' } })); } catch(_){ }
    }catch(_){ }
  }

  function serializeCartForCheckout(){
    var map = {};
    try{
      cart.forEach(function(it){
        var id = (it && it.id != null) ? String(it.id) : '';
        if(!id) return;
        var meta = (it && typeof it.meta === 'object') ? it.meta : {};
        map[id] = {
          id: it.id,
          product_id: it.product_id || meta.product_id || null,
          variant_id: it.variant_id || meta.variant_id || null,
          variant_label: it.variant_label || meta.variant_label || '',
          title: it.title || '',
          price: Number(it.unit || it.price || 0),
          qty: Number(it.qty || 1) || 1,
          image: it.img || it.image || '',
          url: it.url || '#',
          meta: meta,
          booking: meta.booking || {},
          selected: Array.isArray(meta.selected) ? meta.selected : [],
          groupCount: meta.groupCount ?? meta.group_count ?? null,
          reservationId: meta.reservationId ?? meta.reservation_id ?? null,
          holdExpiresAt: meta.holdExpiresAt ?? meta.hold_expires_at ?? null,
          location: meta.location || null,
          source_version: it.source_version || meta.source_version || null,
          options: Array.isArray(meta.variant_options) ? meta.variant_options : []
        };
      });
    }catch(_){ }
    return map;
  }

  function renderSummary(){
    sumSubtotal.textContent = money(subtotal());
    sumDiscount.textContent = '-' + money(discountAmount());
    sumTotal.textContent = money(total());
    var checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = cart.length === 0;
    checkoutBtn.style.opacity = cart.length === 0 ? '.55' : '1';
    checkoutBtn.style.cursor = cart.length === 0 ? 'not-allowed' : 'pointer';
  }
  function escapeHtml(str){ return String(str||"").replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;'); }
  function renderUpsellTarget(target, list){
    if(!target) return;
    if(!Array.isArray(list) || !list.length){ target.innerHTML = ''; return; }
    target.innerHTML = list.map(function(it){
      var p = Number(it.price_min ?? it.price ?? 0);
      var img = it.image || (it.images && it.images[0]) || '';
      var slug = String(it.slug || it.handle || it.title || it.id || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
      var url = it.url || ('/' + String(it.format || 'therapies').toLowerCase() + '/' + String(it.modality || 'item').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') + '/' + slug);
      var title = escapeHtml(it.title||'');
      return '<div class="upsell-item" data-upsell="'+it.id+'">'
        + (img?('<img src="'+img+'" alt="">'):'<div style="width:52px;height:52px;border-radius:14px;background:#f3f5f7;border:1px solid #eceff3"></div>')
        + '<div><p class="upsell-title">'+title+'</p><div class="upsell-price">'+money(p)+'</div></div>'
        + '<button class="btn-wow btn-wow--soft btn-sm upsell-add" type="button" data-add="'+it.id+'">Add</button>'
      + '</div>';
    }).join('');
  }

  function renderUpsells(){
    if(!upsellLoaded) return;
    var list = Array.isArray(upsellPool)?upsellPool.slice():[];
    renderUpsellTarget(upsellListFull, list.slice(0,3));
    renderUpsellTarget(upsellListEmpty, list.slice(3,6).length?list.slice(3,6):list.slice(0,3));
  }

  function deriveSegFromCart(){
    try{ if(!cart || !cart.length) return ''; var url = String(cart[0].url||''); var m=url.match(/\/([a-z-]+)\//i); return m?m[1]:''; }catch(_){ return ''; }
  }

  function loadUpsell(){
    if(upsellLoaded) return; upsellLoaded=true;
    var seg = deriveSegFromCart();
    var endpoint = seg ? ('/api/products?limit=12&sort=popular&type='+encodeURIComponent(seg)) : '/api/products?limit=12&sort=popular';
    fetch(endpoint, { headers:{ 'Accept':'application/json' }})
      .then(function(r){ return r.json(); })
      .then(function(list){ upsellPool = Array.isArray(list)?list:[]; renderUpsells(); })
      .catch(function(){ upsellPool=[]; renderUpsells(); });
  }

  function renderCart(){
    var isEmpty = cart.length===0;
    grid.classList.toggle('is-empty', isEmpty);
    cartHead.classList.toggle('hidden', isEmpty);
    cartFoot.classList.toggle('hidden', isEmpty);
    cartBody.classList.toggle('hidden', isEmpty);
    document.getElementById('summaryWrap').style.display = isEmpty ? 'none' : '';
    document.getElementById('emptyWrap').style.display = isEmpty ? '' : 'none';
    sumHeadTitle.textContent = isEmpty ? 'Your cart' : 'Order summary';
    if (isEmpty){
      cartBody.innerHTML = '';
      renderSummary();
      writeLocalFromCart();
      return;
    }
    cartBody.innerHTML = cart.map(function(it){ var line = Number(it.unit||0) * Number(it.qty||1); var variant = it.variant_label ? '<div class="variant">'+escapeHtml(it.variant_label)+'</div>' : ''; return (
      '<div class="cart-row" data-id="'+escapeHtml(String(it.id))+'">'
      + '<div class="cart-item">'
        + '<a class="cart-img" href="'+escapeHtml(it.url||'#')+'">'+(it.img?('<img src="'+escapeHtml(it.img)+'" alt="">'):'')+'</a>'
        + '<div class="cart-info">'
          + '<a class="title" href="'+escapeHtml(it.url||'#')+'">'+escapeHtml(it.title||'')+'</a>'
          + variant
          + '<div class="meta">'
            + '<span class="pill"><span class="dot"></span>Unit: '+money(it.unit)+'</span>'
            + '<span class="pill">Line: '+money(line)+'</span>'
          + '</div>'
          + '<button class="btn-wow btn-wow--soft btn-sm cart-remove" type="button" data-remove="'+escapeHtml(String(it.id))+'" aria-label="Remove">Remove</button>'
        + '</div>'
      + '</div>'
      + '<div class="cart-qty">'
        + '<div class="qty">'
          + '<button type="button" class="btn-wow btn-wow--soft is-square btn-sm js-qdec" aria-label="Decrease">−</button>'
          + '<input type="number" class="qty-input" min="1" value="'+Number(it.qty||1)+'">'
          + '<button type="button" class="btn-wow btn-wow--soft is-square btn-sm js-qinc" aria-label="Increase">+</button>'
        + '</div>'
      + '</div>'
      + '<div class="cart-amt">'+money(line)+'</div>'
    + '</div>' ); }).join('');
    renderSummary();
    renderUpsells();
    writeLocalFromCart();
  }

  // Listen for cart changes from header dropdown/minicart and other add-to-cart actions
  try{
    var suppressChange = false;
    function safeRender(){ suppressChange = true; try{ renderCart(); } finally { suppressChange = false; } }
    window.addEventListener('wow:cart:change', function(e){ if (suppressChange) return; try { cart = readLocalCart(); safeRender(); } catch(_){ } });
  }catch(_){ }

  const guestModal = document.getElementById('guestCheckoutModal');
  const guestForm = document.getElementById('guestCheckoutForm');
  const guestFirstNameInput = document.getElementById('guestFirstName');
  const guestLastNameInput = document.getElementById('guestLastName');
  const guestEmailInput = document.getElementById('guestEmail');
  const guestError = document.getElementById('guestError');
  const guestSubmitBtn = document.getElementById('guestSubmitBtn');

  function openGuestModal(){
    guestError.textContent='';
    guestFirstNameInput.value='';
    guestLastNameInput.value='';
    guestEmailInput.value='';
    guestModal.classList.add('show');
    guestModal.setAttribute('aria-hidden','false');
    guestFirstNameInput.focus();
  }
  function closeGuestModal(){
    guestModal.classList.remove('show');
    guestModal.setAttribute('aria-hidden','true');
  }
  document.querySelectorAll('[data-guest-close]').forEach(btn => btn.addEventListener('click', closeGuestModal));
  guestModal?.addEventListener('click', function(e){ if(e.target===guestModal) closeGuestModal(); });

  function isValidEmail(v){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

  let checkoutBusy = false;

  guestForm?.addEventListener('submit', function(e){
    e.preventDefault(); if(checkoutBusy) return;
    const firstName = guestFirstNameInput.value.trim();
    const lastName = guestLastNameInput.value.trim();
    const email = guestEmailInput.value.trim();
    guestFirstNameInput.value = firstName;
    guestLastNameInput.value = lastName;
    guestEmailInput.value = email;
    guestFirstNameInput.setCustomValidity('');
    guestEmailInput.setCustomValidity('');
    if(!firstName) {
      guestFirstNameInput.setCustomValidity('Enter your first name.');
    }
    if(!email) {
      guestEmailInput.setCustomValidity('Enter your email address.');
    } else if(!isValidEmail(email)) {
      guestEmailInput.setCustomValidity('Enter a valid email address.');
    }
    if(!guestForm.reportValidity()) {
      guestError.textContent = guestFirstNameInput.validationMessage || guestEmailInput.validationMessage || '';
      guestFirstNameInput.setCustomValidity('');
      guestEmailInput.setCustomValidity('');
      return;
    }
    guestError.textContent='';
    checkoutBusy = true;
    trackCommerce('wow_v3_begin_checkout', {
      items: analyticsItems(cart),
      currency: 'GBP',
      value: total(),
      item_count: cart.reduce(function(sum, it){ return sum + (Number(it.qty||1) || 1); }, 0),
      checkout_type: 'guest',
      source: 'blade-cart',
    });
    guestSubmitBtn.disabled=true;
    guestSubmitBtn.textContent='Redirecting…';
      post('/checkout/session', {
        items: serializeCartForCheckout(),
        first_name: firstName,
        last_name: lastName,
        email: email
      })
      .then(function(res){
        if(res && res.url){ window.location.assign(res.url); return; }
        if(res && res.error){ throw new Error(res.error); }
        throw new Error('no url');
      })
      .catch(function(err){
        var code = err?.message || '';
        if(code==='first_name_required'){ guestError.textContent='We need your first name for the booking.'; }
        else if(code==='invalid_email'){ guestError.textContent='That email looks invalid. Please try again.'; }
        else if(code==='email_required'){ guestError.textContent='We need an email to send your receipt.'; }
        else if(code==='order_failed'){ guestError.textContent='Checkout is temporarily unavailable. Please try again in a moment.'; }
        else if(code==='stripe_failed'){ guestError.textContent='Secure payment is unavailable right now. Please try again.'; }
        else { guestError.textContent='Could not start checkout. Try again.'; }
        trackCommerce('wow_v3_checkout_failed', {
          items: analyticsItems(cart),
          currency: 'GBP',
          value: total(),
          item_count: cart.reduce(function(sum, it){ return sum + (Number(it.qty||1) || 1); }, 0),
          checkout_type: isAuthed ? 'account' : 'guest',
          error_code: code || 'unknown',
          source: 'blade-cart',
        });
        guestSubmitBtn.disabled=false; guestSubmitBtn.textContent='Continue as guest'; checkoutBusy=false;
      });
  });

  document.addEventListener('click', function(e){
    var row = e.target.closest('.cart-row');
    if(row && (e.target.closest('.js-qinc') || e.target.closest('.js-qdec'))){
      var id=row.getAttribute('data-id');
      var item=cart.find(function(x){return String(x.id)===String(id)});
      if(!item) return;
      var previousQty = Math.max(1, Number(item.qty||1) || 1);
      var nextQty = Math.max(1, previousQty + (e.target.closest('.js-qinc') ? 1 : -1));
      if (nextQty === previousQty) return;
      item.qty=nextQty;
      suppressChange=true; renderCart(); suppressChange=false;
      trackCommerce('wow_v3_update_cart_quantity', {
        items: analyticsItems([item]),
        currency: 'GBP',
        value: Number(item.unit||0) * nextQty,
        item_count: nextQty,
        previous_qty: previousQty,
        quantity_delta: nextQty - previousQty,
        cart_value: total(),
        source: 'blade-cart',
      });
      post('/api/cart/update',{id:id,qty:item.qty});
      return;
    }
    var rem = e.target.closest('[data-remove]');
    if(rem){
      var id=rem.getAttribute('data-remove');
      var removedItem = cart.find(function(x){return String(x.id)===String(id)});
      if (!removedItem) return;
      cart=cart.filter(function(x){return String(x.id)!==String(id)});
      suppressChange=true; renderCart(); suppressChange=false;
      trackCommerce('wow_v3_remove_from_cart', {
        items: analyticsItems([removedItem]),
        currency: 'GBP',
        value: Number(removedItem.unit||0) * Math.max(1, Number(removedItem.qty||1) || 1),
        item_count: Math.max(1, Number(removedItem.qty||1) || 1),
        cart_value: total(),
        source: 'blade-cart',
      });
      if (cart.length === 0) {
        post('/api/cart/clear', {}).catch(function(_){});
      } else {
        post('/api/cart/remove',{
          id:id,
          product_id: removedItem.product_id || null,
          variant_id: removedItem.variant_id || null,
          source_version: removedItem.source_version || null
        });
      }
      return;
    }
    if(e.target && e.target.id==='clearCartBtn'){
      var clearSnapshot = cart.slice();
      trackCommerce('wow_v3_clear_cart', {
        items: analyticsItems(clearSnapshot),
        currency: 'GBP',
        value: total(),
        item_count: clearSnapshot.reduce(function(sum, it){ return sum + (Number(it.qty||1) || 1); }, 0),
        source: 'blade-cart',
      });
      cart = [];
      suppressChange=true; renderCart(); suppressChange=false;
      // Update server (best-effort)
      post('/api/cart/clear',{}).catch(function(_){});
      // Also clear cookie immediately so refresh reflects empty state
      try{
        document.cookie = 'wow_cart=; Path=/; Max-Age=0; SameSite=Lax';
        document.cookie = 'wow_cart=; Domain=.weofferwellness.co.uk; Path=/; Max-Age=0; SameSite=Lax';
      }catch(_){ }
      return;
    }
    if(e.target && e.target.id==='apply-promo'){ var code=(document.getElementById('promo-code')?.value||'').trim(); var msg=document.getElementById('promo-msg'); post('/api/cart/promo',{code:code}).then(function(){ msg.textContent=code?("Code '"+code+"' applied"):'Code cleared'; }).catch(function(){ msg.textContent='Could not apply code'; }); return; }

    // Checkout via Stripe Checkout Session
    if(e.target && e.target.id==='checkoutBtn'){
      if (cart.length === 0) return;
      if (!isAuthed){
        openGuestModal();
        return;
      }
      var btn = e.target; var prev = btn.textContent; btn.disabled = true; btn.style.opacity='.65'; btn.textContent = 'Redirecting…';
      trackCommerce('wow_v3_begin_checkout', {
        items: analyticsItems(cart),
        currency: 'GBP',
        value: total(),
        item_count: cart.reduce(function(sum, it){ return sum + (Number(it.qty||1) || 1); }, 0),
        checkout_type: 'account',
        source: 'blade-cart',
      });
      post('/checkout/session', { items: serializeCartForCheckout() })
        .then(function(res){
          if(res && res.url){ window.location.assign(res.url); return; }
          if(res && res.error){ throw new Error(res.error); }
          throw new Error('no url');
        })
        .catch(function(err){
          const code = err?.message || '';
          const msg = code === 'email_required'
            ? 'Please update your account email before checking out.'
            : (code === 'order_failed'
              ? 'Checkout is temporarily unavailable. Please try again in a moment.'
              : (code === 'stripe_failed'
                ? 'Secure payment is unavailable right now. Please try again.'
                : 'Could not start checkout. Please try again.'));
          alert(msg);
          btn.disabled=false; btn.style.opacity='1'; btn.textContent=prev;
        });
      return;
    }

    var add = e.target.closest('[data-add]');
    if (add){
      var uid = add.getAttribute('data-add');
      var u = (upsellPool||[]).find(function(x){ return String(x.id)===String(uid) }); if(!u) return;
      var pRaw = Number(u.price_min ?? u.price ?? 0); var unit = pRaw;
      var cartId = 'p:'+String(uid);
      var ex = cart.find(function(x){ return String(x.id)===cartId });
      var addedItem = ex ? ex : {
        id: cartId,
        product_id:Number(uid)||uid,
        variant_id:null,
        variant_label:'',
        title:String(u.title||''),
        url:(u.url||('/' + String(u.format || 'therapies').toLowerCase() + '/' + String(u.modality || 'item').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') + '/' + String(u.slug || u.handle || u.title || uid || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''))),
        img:(u.image||(u.images&&u.images[0])||''),
        unit:unit,
        qty:1
      };
      if(ex){ ex.qty = Math.max(1, Number(ex.qty||1)+1); }
      else {
        cart.unshift(addedItem);
      }
      try{ post('/api/cart/add', { id: Number(uid)||uid, qty:1 }); }catch(_){}
      trackCommerce('wow_v3_add_to_cart', {
        items: analyticsItems([{ ...addedItem, qty: ex ? Number(ex.qty||1) : 1 }]),
        currency: 'GBP',
        value: Number(unit||0) * (ex ? Math.max(1, Number(ex.qty||1)) : 1),
        item_count: ex ? Math.max(1, Number(ex.qty||1)) : 1,
        source: 'blade-cart-upsell',
      });
      add.classList.add('is-added'); add.textContent='Added'; setTimeout(function(){ add.textContent='Add'; add.classList.remove('is-added'); }, 700);
      renderCart();
      return;
    }
  });
  document.addEventListener('input', function(e){ var inp=e.target.closest('.qty-input'); if(!inp) return; var row=e.target.closest('.cart-row'); if(!row) return; var id=row.getAttribute('data-id'); var item=cart.find(function(x){return String(x.id)===String(id)}); if(!item) return; var v=parseInt(inp.value||'1',10); if(!Number.isFinite(v)||v<1) v=1; item.qty=v; renderCart(); post('/api/cart/update',{id:id,qty:v}); });

  loadUpsell();
  renderCart();
})();
</script>

<style>
.lead-cart{ margin:0 0 18px; color: var(--ink-600); font-size:14px; font-weight:600; }
.cart-info .variant{ color: var(--ink-600); font-size:13px; margin:4px 0 0; }
.card.glass{
  position:relative;
  border-radius:3px;
  border:1px solid rgba(0,0,0,0.15);
  background: rgba(255,255,255,.85);
  overflow:hidden;
}

.cart-grid{ display:flex; align-items:flex-start; gap: var(--gap); --gap:14px; --sideBasis:34.5%; --ease:cubic-bezier(.2,.8,.2,1); --dur:.42s; transition: gap var(--dur) var(--ease); }
.cart-main{ flex:1 1 auto; min-width:0; max-width:100%; opacity:1; transform: translateX(0) scale(1); transition:max-width var(--dur) var(--ease), transform var(--dur) var(--ease), opacity .22s var(--ease); }
.cart-side{ flex:0 1 auto; flex-basis: var(--sideBasis); min-width:0; position:sticky; transition:flex-basis var(--dur) var(--ease), transform var(--dur) var(--ease); }
.cart-grid.is-empty{ --gap:0px; --sideBasis:100%; }
.cart-grid.is-empty .cart-main{ max-width:0; opacity:0; transform: translateX(-10px) scale(.98); pointer-events:none; overflow:hidden; }
@media (max-width: 991.98px){
  .cart-grid{ flex-direction:column; gap:14px; }
  .cart-side{ position:static; order:0; }
  .cart-main{ order:-1; }
  .cart-grid.is-empty .cart-main{ max-width:100%; opacity:1; transform:none; pointer-events:auto; overflow:visible; }
}

.cart-head{ display:grid; grid-template-columns: 1fr 150px 120px; gap:12px; padding:14px 16px; font-size:12px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color: var(--ink-600); border-bottom:1px solid rgba(16,24,40,.10); background: linear-gradient(180deg, rgba(255,255,255,.80), rgba(255,255,255,.52)); }
.cart-body{ padding:10px; display:flex; flex-direction:column; gap:10px; }
.cart-row{ display:grid; grid-template-columns: 1fr 150px 120px; gap:12px; align-items:center; padding:12px; border-radius:18px; border:1px solid var(--ink-200); background: rgba(255,255,255,.86); box-shadow: 0 12px 26px rgba(16,24,40,.06); }
.cart-item{ display:grid; grid-template-columns: 76px 1fr; gap:12px; align-items:center; min-width:0; }
.cart-img{ width:76px; height:76px; border-radius:18px; overflow:hidden; display:block; border:1px solid var(--ink-200); background:#f3f5f7; }
.cart-img img{ width:100%; height:100%; object-fit:cover; display:block; }
.title{ margin:0; font-weight:800; font-size:14px; letter-spacing:-.01em; line-height:1.22; color: var(--ink-900); text-decoration:none; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.title:hover{ text-decoration:underline; }
.meta{ margin-top:6px; font-size:12px; color: var(--ink-600); font-weight:650; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.pill{ display:inline-flex; align-items:center; gap:6px; padding: 5px 10px; border-radius:999px; border:1px solid var(--ink-200); background: rgba(255,255,255,.9); box-shadow: 0 10px 18px rgba(16,24,40,.05); font-size:12px; font-weight:750; color: var(--ink-700); }
.pill .dot{ width:8px;height:8px;border-radius:999px; background: var(--accent-600); }
.cart-remove{ margin-top:8px; }
.cart-remove.btn-wow{ font-size:12px; font-weight:800; padding:6px 12px; }
.cart-qty{ display:flex; justify-content:flex-start; }
.qty{ display:inline-flex; align-items:center; border-radius:999px; border:1px solid var(--ink-200); background: rgba(255,255,255,.92); box-shadow: 0 10px 18px rgba(16,24,40,.06); overflow:hidden; height:38px; }
.qty .btn-wow{ min-width:38px; height:38px; border-radius:0; padding:0; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:16px; }
.qty .btn-wow:first-child{ border-top-left-radius:999px; border-bottom-left-radius:999px; }
.qty .btn-wow:last-child{ border-top-right-radius:999px; border-bottom-right-radius:999px; }
.qty input{ width:46px; height:38px; border:0; outline:0; text-align:center; font-weight:900; font-size:13px; background:transparent; color: var(--ink-900); -moz-appearance:textfield; }
.qty input::-webkit-outer-spin-button, .qty input::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
.cart-amt{ text-align:right; font-weight:900; letter-spacing:-.01em; font-size:14px; color: var(--ink-900); }
.cart-foot{ padding: 12px 16px 16px; display:flex; align-items:center; justify-content:space-between; gap:10px; border-top: 1px solid rgba(16,24,40,.10); }
.link-wow{ color: var(--ink-800); font-weight:800; text-decoration: none; border-bottom: 1px solid var(--ink-300); padding-bottom: 2px; }
.link-wow:hover{ color: var(--ink-900); border-bottom-color: var(--ink-500); }

.sum-head{ padding:14px 16px; font-weight:900; letter-spacing:-.02em; border-bottom:1px solid rgba(16,24,40,.10); background: linear-gradient(180deg, rgba(255,255,255,.80), rgba(255,255,255,.52)); }
.sum-body{ padding:14px 16px 16px; position:relative; }
.panel{ transition: opacity .22s cubic-bezier(.2,.8,.2,1), transform .42s cubic-bezier(.2,.8,.2,1), max-height .42s cubic-bezier(.2,.8,.2,1); overflow:hidden; }
.sum-row{ display:flex; justify-content:space-between; align-items:baseline; gap:10px; font-size:13px; color: var(--ink-700); font-weight:650; }
.sum-row strong{ font-weight:900; letter-spacing:-.01em; color: var(--ink-900); }
.sum-row.muted{ color: var(--ink-600); font-weight:600; font-size:12px; }
.sum-sep{ height:1px; background: rgba(16,24,40,.10); margin: 2px 0; }
.sum-row.total{ font-size:14px; font-weight:900; letter-spacing:-.01em; }
.sum-row.total strong{ font-size:18px; }
.promo{ margin:16px 9px; padding:12px; border-radius:18px; border:1px solid var(--ink-200); background: rgba(255,255,255,.86); box-shadow: 0 12px 26px rgba(16,24,40,.06); }
.promo label{ display:block; font-size:12px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color: var(--ink-600); margin-bottom:8px; }
.promo-inline{ display:flex; gap:10px; align-items:center; }
.promo-inline input{ flex:1 1 auto; height:42px; border-radius:14px; border:1px solid var(--ink-200); background: rgba(255,255,255,.95); padding:0 12px; font-weight:800; outline:none; }
.promo-inline input:focus{ border-color: var(--accent-400); box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent-600) 25%, transparent); }
.promo-msg{ margin-top:8px; font-size:12px; font-weight:750; color: var(--ink-700); }
.trust-hints{ display:flex; flex-direction:column; gap:8px; margin-top:8px; color: var(--ink-600); font-size:12px; font-weight:650; }
.hint{ display:flex; align-items:center; gap:10px; }
.hint .dot{ width:10px;height:10px;border-radius:999px; background: var(--accent-600); box-shadow: 0 10px 18px color-mix(in srgb, var(--accent-600) 25%, transparent); }

.empty-wrap{ padding:6px 0 0; display:grid; grid-template-columns: 1fr; gap:14px; }
.empty-hero{ padding:16px; border-radius:22px; border:1px solid var(--ink-200); background: rgba(255,255,255,.86); box-shadow: 0 12px 26px rgba(16,24,40,.06); display:flex; gap:14px; align-items:flex-start; }
.empty-hero__body{ display:flex; align-items:center; justify-content:space-between; gap:16px; flex:1 1 auto; flex-wrap:wrap; }
.empty-illu{
  width: 54px;
  height: 54px;
  border-radius: 18px;
  display: grid;
  place-items: center;
  background: linear-gradient(180deg, rgba(84, 148, 131, .18), rgba(84, 148, 131, .06));
  border: 1px solid rgba(84, 148, 131, .22);
  box-shadow: 0 14px 30px rgba(84, 148, 131, .14);
  flex: 0 0 auto;
  color: rgba(11, 18, 32, .85);
}
.empty-copy{ flex:1 1 220px; }
.empty-hero h2{ margin:0; font-size:18px; letter-spacing:-.02em; font-weight:900; font-family:'Manrope', system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif; }
.sum-head, .title, .upsell-title{ font-family:'Manrope', system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif; }
.empty-hero p{ margin:6px 0 0; color: var(--ink-600); font-weight:650; font-size:13px; line-height:1.45; }
.empty-actions{ margin-top:0; display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; flex:0 0 auto; }

@media (max-width: 576px){
  .empty-hero__body{ flex-direction:column; align-items:flex-start; }
  .empty-actions{ width:100%; justify-content:flex-start; }
}

@media (max-width: 767.98px){ .cart-head{ display:none; } .cart-row{ grid-template-columns: 1fr; gap:10px; } .cart-amt{ text-align:left; } .cart-qty{ justify-content:flex-start; } .cart-item{ grid-template-columns: 70px 1fr; } .cart-img{ width:70px;height:70px; } }
@media (max-width: 767.98px){
  #cartMain{ width:100%; max-width:100%; }
  .cart-main{ width:100%; max-width:100%; }
  .cart-body{ padding-left:0; padding-right:0; }
  .cart-row{ grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce){ *{ transition:none !important; } }

/* Upsell block styling (align with template) */
.upsell{ margin-top:12px; padding:12px; border-radius:20px; border:1px solid var(--ink-200); background: rgba(255,255,255,.86); box-shadow: 0 12px 26px rgba(16,24,40,.06); }
.upsell-head{ display:flex; align-items:baseline; justify-content:space-between; gap:10px; margin-bottom:10px; }
.upsell-head strong{ font-weight:950; letter-spacing:-.01em; }
.upsell-head small{ color: var(--ink-600); font-weight:800; font-size:12px; }
.upsell-list{ display:flex; flex-direction:column; gap:10px; }
.upsell-item{ display:grid; grid-template-columns:52px 1fr auto; gap:10px; align-items:center; padding:10px; border-radius:16px; border:1px solid var(--ink-200); background: rgba(255,255,255,.95); }
.upsell-item img{ width:52px; height:52px; border-radius:14px; object-fit:cover; border:1px solid var(--ink-200); background:#f3f5f7; }
.upsell-title{ font-size:13px; font-weight:900; margin:0; line-height:1.2; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.upsell-price{ font-size:12px; color: var(--ink-700); font-weight:900; margin-top:4px; }
.upsell-add.btn-wow{ min-width:88px; justify-content:center; font-size:12px; font-weight:800; }
.upsell-add.is-added{ background: color-mix(in srgb, var(--accent-600) 12%, white); border-color: color-mix(in srgb, var(--accent-600) 45%, transparent); }
.guest-modal{ position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:center; justify-content:center; padding:20px; z-index:200; }
.guest-modal.show{ display:flex; }
.guest-modal__dialog{ width:min(420px, 96vw); background:#fff; border-radius:20px; padding:24px; position:relative; box-shadow:0 20px 40px rgba(15,23,42,.25); }
.guest-modal__close{ position:absolute; top:12px; right:12px; }
.guest-modal__close.btn-wow{ font-size:18px; padding:0; }
#guestCheckoutForm{ display:grid; gap:12px; margin-top:12px; }
#guestCheckoutForm input{ border:1px solid var(--ink-200); border-radius:12px; padding:12px; font-size:16px; }
.guest-modal__links{ display:flex; justify-content:space-between; font-weight:700; }
.guest-modal__error{ color:#dc2626; font-weight:700; min-height:18px; }
</style>
@endsection
