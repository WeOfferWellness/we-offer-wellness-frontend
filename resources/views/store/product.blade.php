@extends('layouts.app')

@section('content')
@php
    $product = is_array($product ?? null) ? $product : [];
    $title = (string) data_get($product, 'title', 'Product');
    $slug = (string) data_get($product, 'slug', '');
    $category = (string) data_get($product, 'category.slug', '');
    $categoryName = (string) data_get($product, 'category.name', 'Shop');
    $image = (string) data_get($product, 'image', '');
    $price = (float) data_get($product, 'price', 0);
    $price = app(\App\Services\MarketplacePricingService::class)->buyerPrice($price, ['vendor_name' => data_get($product, 'brand', 'We Offer Wellness')], null, false);
    $compare = data_get($product, 'compare_at_price');
    $variant = collect((array) data_get($product, 'variants', []))->first() ?: [];
    $variantId = (string) data_get($variant, 'id', '');
    $variantLabel = (string) data_get($variant, 'title', 'Paperback');
    $inStock = (bool) data_get($product, 'in_stock', true);
    $stock = (int) data_get($product, 'inventory_quantity', 0);
    $reviews = (array) data_get($product, 'reviews', []);
    $reviewCount = (int) data_get($product, 'review_count', count($reviews));
    $rating = data_get($product, 'rating');
    $productUrl = (string) data_get($product, 'url', url()->current());
    $cartData = [
        'id' => 'store-' . data_get($product, 'id', 0),
        'product-id' => data_get($product, 'id', 0),
        'source-version' => 'store',
        'title' => $title,
        'price' => number_format($price, 2, '.', ''),
        'variant-id' => $variantId,
        'variant-label' => $variantLabel,
        'image' => $image,
        'product-url' => $productUrl,
        'url' => $productUrl,
        'qty' => 1,
        'price-includes-marketplace-markup' => '1',
    ];
@endphp

<div
    hidden
    data-wow-analytics-page="product"
    data-wow-analytics-item="{!! e(json_encode([
        'id' => 'store-' . data_get($product, 'id', 0),
        'product_id' => data_get($product, 'id', 0),
        'title' => $title,
        'price' => $price,
        'currency' => strtoupper((string) data_get($product, 'currency', 'GBP')),
        'source_version' => 'store',
        'catalogue_type' => 'physical_product',
        'modality' => $categoryName,
        'variant_id' => $variantId,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !!}"
></div>

<style>
    .store-product-page{--sp-ink:#13221d;--sp-muted:#647069;--sp-line:#d9dfda;--sp-soft:#f5f8f5;--sp-green:#549483;--sp-green-hover:#417b6d;--sp-dark:#254735;--sp-pale:#eaf3ed;--sp-gold:#d3ab25;color:var(--sp-ink);background:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
    .store-product-page *{box-sizing:border-box}.store-product-page a{color:inherit}.store-product-page img{display:block;max-width:100%}
    .sp-breadcrumbs,.sp-product-wrap,.sp-trust,.sp-content{max-width:1440px;margin:0 auto;padding-left:24px;padding-right:24px}
    .sp-breadcrumbs{padding-top:18px;color:var(--sp-muted);font-size:13px}.sp-breadcrumbs ol{display:flex;gap:8px;list-style:none;padding:0;margin:0;flex-wrap:wrap}.sp-breadcrumbs li:not(:last-child):after{content:"/";margin-left:8px;color:#a2aaa5}.sp-breadcrumbs a{text-decoration:none}.sp-breadcrumbs a:hover{text-decoration:underline}
    .sp-product-wrap{padding-top:30px;padding-bottom:65px}.sp-grid{display:grid;grid-template-columns:minmax(360px,1.05fr) minmax(320px,.9fr) 350px;gap:40px;align-items:start}
    .sp-gallery{display:grid;grid-template-columns:76px minmax(0,1fr);gap:15px}.sp-thumbs{display:flex;flex-direction:column;align-items:flex-start;gap:10px}.sp-thumb{width:max-content;height:auto;min-height:0;border:1px solid var(--sp-line);background:#fff;padding:6px;cursor:pointer}.sp-thumb.is-active{border-color:var(--sp-dark);box-shadow:inset 0 0 0 1px var(--sp-dark)}.sp-thumb img{display:block;width:62px;height:auto;max-height:110px;object-fit:contain}.sp-stage{min-height:610px;background:var(--sp-soft);border:1px solid var(--sp-line);display:grid;place-items:center;padding:36px;position:relative;overflow:hidden}.sp-stage:before{content:"";position:absolute;inset:0;background-image:linear-gradient(#e8ede8 1px,transparent 1px),linear-gradient(90deg,#e8ede8 1px,transparent 1px);background-size:52px 52px;opacity:.4}.sp-stage img{position:relative;max-height:540px;width:auto;object-fit:contain;filter:drop-shadow(0 24px 26px rgba(29,45,34,.18))}.sp-stage small{position:absolute;bottom:13px;left:14px;background:#fff;border:1px solid var(--sp-line);padding:5px 8px;color:var(--sp-muted)}
    .sp-eyebrow{color:var(--sp-green);font-size:12px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;margin-bottom:14px}.sp-pill{display:inline-block;border:1px solid #b7ccbc;background:var(--sp-pale);padding:3px 8px;color:var(--sp-dark);margin-left:7px}.sp-copy h1{font-size:clamp(38px,4vw,62px);line-height:.98;letter-spacing:-.055em;margin:0 0 15px}.sp-author{color:var(--sp-muted);margin-bottom:18px}.sp-author a{color:var(--sp-dark);font-weight:750}.sp-rating{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:24px}.sp-stars{color:#d28b00;letter-spacing:2px;font-size:18px}.sp-rating a{color:var(--sp-green);font-size:14px;font-weight:700}.sp-divider{width:1px;height:17px;background:#bfc9c1}.sp-copy .sp-lede{font-size:18px;color:#334038;margin:0 0 20px}.sp-features{list-style:none;padding:0;margin:0 0 26px;display:grid;gap:10px}.sp-features li{display:grid;grid-template-columns:22px 1fr;gap:10px;color:#39473e}.sp-check{color:var(--sp-green);font-weight:900}
    .sp-facts{display:grid;grid-template-columns:repeat(3,1fr);border:1px solid var(--sp-line);margin-top:22px}.sp-fact{padding:13px}.sp-fact+ .sp-fact{border-left:1px solid var(--sp-line)}.sp-fact small{display:block;color:var(--sp-muted);font-size:12px;margin-bottom:4px}.sp-fact strong{font-size:13px}
    .sp-buy{border:1px solid #bfc9c1;background:#fff;padding:22px;position:sticky;top:130px;box-shadow:0 14px 38px rgba(25,45,33,.1)}.sp-buy-top{display:flex;justify-content:space-between;gap:14px}.sp-buy-label{color:var(--sp-muted);font-size:12px;text-transform:uppercase;letter-spacing:.08em;font-weight:750}.sp-stock{border:1px solid #b9d1bf;background:var(--sp-pale);color:var(--sp-dark);padding:4px 7px;font-size:12px;font-weight:800;height:max-content}.sp-price{font-size:36px;line-height:1;font-weight:850;letter-spacing:-.04em;margin-top:8px}.sp-compare{color:var(--sp-muted);text-decoration:line-through;font-size:13px;margin-left:8px}.sp-save{color:#a62c2c;font-size:13px;font-weight:750;margin:7px 0 18px}.sp-delivery{background:var(--sp-soft);border:1px solid var(--sp-line);padding:13px;margin-bottom:16px}.sp-delivery strong,.sp-delivery span{display:block}.sp-delivery span{color:var(--sp-muted);font-size:13px}.sp-purchase{display:grid;grid-template-columns:94px 1fr;gap:9px}.sp-qty{display:grid;grid-template-columns:28px 1fr 28px;border:1px solid #bfc9c1;min-height:48px}.sp-qty button{border:0;background:var(--sp-soft);cursor:pointer;font-size:19px}.sp-qty input{width:100%;border:0;text-align:center;font-weight:800;appearance:textfield;-moz-appearance:textfield}.sp-qty input::-webkit-outer-spin-button,.sp-qty input::-webkit-inner-spin-button{margin:0;-webkit-appearance:none}.sp-btn{border:1px solid transparent;min-height:48px;padding:12px 16px;cursor:pointer;font-weight:800;text-align:center;display:grid;place-items:center;border-radius:4px;transition:transform .18s ease,background .18s ease,border-color .18s ease}.sp-btn:hover{transform:translateY(-1px)}.sp-add-button{background:#fff;color:var(--sp-ink);border-color:#bfc9c1}.sp-add-button:hover{background:var(--sp-soft);border-color:var(--sp-green)}.sp-buy-button{background:var(--sp-green);color:#fff;border-color:var(--sp-green);width:100%;margin-top:10px}.sp-buy-button:hover{background:var(--sp-green-hover);border-color:var(--sp-green-hover)}.sp-total{display:flex;justify-content:space-between;gap:15px;border-top:1px solid var(--sp-line);margin:16px 0 0;padding-top:13px;color:var(--sp-muted);font-size:13px}.sp-total strong{color:var(--sp-ink);font-size:16px}.sp-secure{list-style:none;padding:0;margin:18px 0 0;display:grid;gap:9px;color:#536057;font-size:13px}.sp-secure li{display:grid;grid-template-columns:20px 1fr;gap:8px}.sp-pay{border-top:1px solid var(--sp-line);margin-top:18px;padding-top:14px;color:var(--sp-muted);font-size:12px}
    .sp-trust{display:grid;grid-template-columns:repeat(4,1fr);margin-bottom:74px}.sp-trust-item{border-top:1px solid var(--sp-line);border-bottom:1px solid var(--sp-line);padding:20px;display:grid;grid-template-columns:35px 1fr;gap:12px;align-items:center}.sp-trust-item+.sp-trust-item{border-left:1px solid var(--sp-line)}.sp-trust-icon{width:30px;height:30px;border:1px solid var(--sp-green);display:grid;place-items:center;align-items:center;justify-items:center;align-self:center;justify-self:center;color:var(--sp-dark);font-size:10px;font-weight:900;line-height:1;text-align:center}.sp-trust-item strong,.sp-trust-item span{display:block}.sp-trust-item span{color:var(--sp-muted);font-size:12px}.sp-content{padding-bottom:72px}.sp-heading{border-top:1px solid var(--sp-ink);padding-top:23px;margin-bottom:28px;display:grid;grid-template-columns:1fr .4fr;gap:35px;align-items:end}.sp-heading h2{font-size:clamp(31px,4vw,53px);line-height:1;letter-spacing:-.05em;margin:0}.sp-heading p{color:var(--sp-muted);margin:0}.sp-about{display:grid;grid-template-columns:1.3fr .7fr;gap:40px;margin-bottom:82px}.sp-panel{border:1px solid var(--sp-line);padding:30px}.sp-panel p{color:#3f4b44}.sp-panel p:last-child{margin-bottom:0}.sp-snapshot{border:1px solid var(--sp-line);padding:22px}.sp-snapshot h3{margin:0 0 12px}.sp-snapshot dl{margin:0}.sp-snapshot div{display:flex;justify-content:space-between;gap:15px;border-top:1px solid var(--sp-line);padding:10px 0;font-size:13px}.sp-snapshot dt{color:var(--sp-muted)}.sp-snapshot dd{font-weight:750;margin:0;text-align:right}.sp-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:82px}.sp-card{border:1px solid var(--sp-line);background:var(--sp-soft);min-height:250px;padding:25px;display:flex;flex-direction:column}.sp-card small{color:var(--sp-green);font-size:12px;font-weight:900;letter-spacing:.12em}.sp-card h3{font-size:23px;line-height:1.08;letter-spacing:-.035em;margin:auto 0 12px}.sp-card p{color:var(--sp-muted);margin:0}.sp-details{display:grid;grid-template-columns:1fr 350px;gap:28px;margin-bottom:82px}.sp-table{border:1px solid var(--sp-line)}.sp-row{display:grid;grid-template-columns:210px 1fr}.sp-row+.sp-row{border-top:1px solid var(--sp-line)}.sp-row dt,.sp-row dd{padding:15px 18px;margin:0}.sp-row dt{background:var(--sp-soft);color:var(--sp-muted);font-size:13px}.sp-row dd{font-weight:700}.sp-review-grid{display:grid;grid-template-columns:320px 1fr;gap:28px;margin-bottom:82px}.sp-summary{border:1px solid var(--sp-line);padding:25px}.sp-summary b{font-size:56px;line-height:1;letter-spacing:-.06em}.sp-summary p{color:var(--sp-muted)}.sp-review-card{border:1px solid var(--sp-line);padding:22px;margin-bottom:12px}.sp-review-card h3{margin:0;font-size:18px}.sp-review-card p{color:#3f4a43}.sp-review-card small{color:var(--sp-muted)}.sp-form{border:1px solid var(--sp-line);padding:22px;display:grid;gap:12px}.sp-form label{font-size:13px;font-weight:750}.sp-form input,.sp-form textarea{width:100%;border:1px solid #bfc9c1;padding:11px;font:inherit}.sp-form textarea{min-height:110px}.sp-faq{border-top:1px solid var(--sp-line);margin-bottom:20px}.sp-faq-item{border-bottom:1px solid var(--sp-line)}.sp-faq-button{width:100%;border:0;background:transparent;padding:18px 0;text-align:left;font-weight:800;display:flex;justify-content:space-between;cursor:pointer}.sp-faq-panel{display:none;color:var(--sp-muted);padding:0 0 18px;max-width:850px}.sp-faq-panel.is-open{display:block}.sp-mobile-buy{display:none}
    @media(max-width:1220px){.sp-grid{grid-template-columns:minmax(360px,1fr) minmax(320px,.85fr)}.sp-buy{grid-column:2;position:static}.sp-gallery{grid-row:span 2}}
    @media(max-width:900px){.sp-grid{grid-template-columns:1fr;gap:28px}.sp-gallery{grid-row:auto}.sp-buy{grid-column:auto}.sp-trust{grid-template-columns:repeat(2,1fr)}.sp-heading,.sp-about,.sp-details,.sp-review-grid{grid-template-columns:1fr}.sp-cards{grid-template-columns:1fr}}
    @media(max-width:640px){body{padding-bottom:74px}.sp-breadcrumbs,.sp-product-wrap,.sp-trust,.sp-content{padding-left:14px;padding-right:14px}.sp-breadcrumbs li:nth-child(2),.sp-breadcrumbs li:nth-child(3){display:none}.sp-gallery{grid-template-columns:1fr}.sp-thumbs{order:2;display:flex;overflow:auto}.sp-thumb{min-width:70px;height:auto;min-height:0}.sp-thumb img{width:58px;height:auto;max-height:80px}.sp-stage{min-height:450px;padding:20px}.sp-stage img{max-height:400px}.sp-copy h1{font-size:43px}.sp-facts{grid-template-columns:1fr}.sp-fact+.sp-fact{border-left:0;border-top:1px solid var(--sp-line)}.sp-buy{box-shadow:none;padding:18px}.sp-trust{grid-template-columns:1fr;margin-bottom:56px}.sp-trust-item+.sp-trust-item{border-left:0}.sp-panel{padding:22px}.sp-row{grid-template-columns:1fr}.sp-row dt{padding-bottom:4px}.sp-row dd{padding-top:4px}.sp-mobile-buy{display:grid;grid-template-columns:1fr 1.35fr;gap:10px;align-items:center;position:fixed;left:0;right:0;bottom:0;z-index:80;background:#fff;border-top:1px solid #bfc9c1;padding:10px 14px;box-shadow:0 -10px 28px rgba(29,45,34,.12)}.sp-mobile-buy strong{display:block;font-size:20px;line-height:1}.sp-mobile-buy small{color:var(--sp-green);font-size:11px;font-weight:800}}
</style>

<div class="store-product-page">
    <nav class="sp-breadcrumbs" aria-label="Breadcrumb"><ol>
        <li><a href="{{ url('/') }}">Home</a></li><li><a href="{{ url('/products') }}">Shop</a></li><li><a href="{{ url('/products?category=' . urlencode($category)) }}">{{ $categoryName }}</a></li><li aria-current="page">{{ $title }}</li>
    </ol></nav>

    <section class="sp-product-wrap" aria-labelledby="product-title"><div class="sp-grid">
        <div class="sp-gallery" aria-label="Product gallery">
            <div class="sp-thumbs"><button class="sp-thumb is-active" type="button"><img src="{{ $image }}" alt=""></button></div>
            <div class="sp-stage"><img src="{{ $image }}" alt="{{ $title }}"><small>Product image</small></div>
        </div>
        <div class="sp-copy">
            <div class="sp-eyebrow">{{ $categoryName }} <span class="sp-pill">{{ $inStock ? 'In stock' : 'Unavailable' }}</span></div>
            <h1 id="product-title">{{ $title }}</h1>
            <div class="sp-author">By <strong>{{ data_get($product, 'author', 'Ian Snowball') }}</strong> <span aria-hidden="true">·</span> Published by {{ data_get($product, 'publisher', 'New Haven Publishing') }}</div>
            <div class="sp-rating"><span class="sp-stars" aria-label="{{ $rating ? $rating . ' out of 5 stars' : 'Not yet rated' }}">{{ $rating ? '★★★★★' : '☆☆☆☆☆' }}</span><a href="#reviews">{{ $reviewCount ? number_format((float)$rating, 1) . ' from ' . $reviewCount . ' reviews' : 'Be the first to review' }}</a><span class="sp-divider"></span><span>Paperback · {{ data_get($product, 'pages', '168') }} pages</span></div>
            <p class="sp-lede">{{ data_get($product, 'summary', 'An honest, personal exploration of sound healing, the ideas behind the practice, and the development that can emerge when we begin to listen differently.') }}</p>
            <ul class="sp-features"><li><span class="sp-check">✓</span><span>Written for practitioners, the curious, and anyone exploring sound as part of wellbeing.</span></li><li><span class="sp-check">✓</span><span>Combines personal experience with esoteric and alternative perspectives.</span></li><li><span class="sp-check">✓</span><span>A reflective pocketbook of possibilities rather than a rigid instruction manual.</span></li></ul>
            <div class="sp-facts"><div class="sp-fact"><small>Format</small><strong>{{ $variantLabel }}</strong></div><div class="sp-fact"><small>Language</small><strong>English</strong></div><div class="sp-fact"><small>ISBN / SKU</small><strong>{{ data_get($product, 'sku', '—') }}</strong></div></div>
        </div>
        <aside class="sp-buy" aria-label="Purchase options"><div class="sp-buy-top"><div><div class="sp-buy-label">{{ $variantLabel }}</div><div class="sp-price">£{{ number_format($price, 2) }} @if($compare && $compare > $price)<span class="sp-compare">£{{ number_format((float)$compare, 2) }}</span>@endif</div></div><span class="sp-stock">{{ $inStock ? 'In stock' : 'Sold out' }}</span></div>@if($compare && $compare > $price)<div class="sp-save">Save £{{ number_format((float)$compare - $price, 2) }}</div>@endif<div class="sp-delivery"><strong>{{ data_get($product, 'requires_shipping', true) ? 'Delivery from £2.99' : 'Available instantly' }}</strong><span>{{ data_get($product, 'requires_shipping', true) ? 'Estimated 2–4 working days. Free delivery when your basket reaches £25.' : 'Your digital edition will be available after secure checkout.' }}</span></div><div class="sp-purchase"><div class="sp-qty"><button type="button" data-qty-minus>−</button><input type="number" min="1" max="20" value="1" aria-label="Quantity" data-qty><button type="button" data-qty-plus>+</button></div><button class="sp-btn sp-add-button js-add-to-cart js-open-cart" type="button" @foreach($cartData as $key=>$value) data-{{ $key }}="{{ $value }}" @endforeach>Add to cart</button></div><div class="sp-total"><span>Total for <span data-qty-label>1</span> item<span data-qty-plural>s</span></span><strong data-total>£{{ number_format($price, 2) }}</strong></div><button class="sp-btn sp-buy-button js-buy-now" type="button" @foreach($cartData as $key=>$value) data-{{ $key }}="{{ $value }}" @endforeach>Buy now</button><ul class="sp-secure"><li><span class="sp-check">✓</span><span>Secure Stripe checkout. Card details never touch our servers.</span></li><li><span class="sp-check">✓</span><span>Tracked UK delivery with email order confirmation.</span></li><li><span class="sp-check">✓</span><span>30-day returns in line with our store policy.</span></li></ul><div class="sp-pay">Visa · Mastercard · Amex · Apple Pay · Google Pay</div></aside>
    </div></section>

    <section class="sp-trust" aria-label="Shopping benefits"><div class="sp-trust-item"><span class="sp-trust-icon">UK</span><div><strong>Fast UK delivery</strong><span>Tracked dispatch and clear estimates</span></div></div><div class="sp-trust-item"><span class="sp-trust-icon">PAY</span><div><strong>Secure payment</strong><span>Protected checkout through Stripe</span></div></div><div class="sp-trust-item"><span class="sp-trust-icon">30</span><div><strong>Simple returns</strong><span>30 days to change your mind</span></div></div><div class="sp-trust-item"><span class="sp-trust-icon">BOOK</span><div><strong>Independent release</strong><span>Support wellness creators directly</span></div></div></section>

    <section class="sp-content" id="about"><div class="sp-heading"><h2>About this product.</h2><p>A thoughtful guide to sound healing, personal development, and the ideas that sit behind the practice.</p></div><div class="sp-about"><article class="sp-panel">{!! data_get($product, 'description', '<p>This pocketbook explores sound healing through personal experience, reflective practice, and an open-minded look at energy and resonance.</p>') !!}</article><aside class="sp-snapshot"><h3>Product snapshot</h3><dl><div><dt>Format</dt><dd>{{ $variantLabel }}</dd></div><div><dt>Author</dt><dd>{{ data_get($product, 'author', 'Ian Snowball') }}</dd></div><div><dt>Pages</dt><dd>{{ data_get($product, 'pages', '168') }}</dd></div><div><dt>Availability</dt><dd>{{ $inStock ? 'In stock' : 'Unavailable' }}</dd></div></dl></aside></div></section>
    <section class="sp-content" id="inside"><div class="sp-heading"><h2>What you will explore.</h2><p>Three clear reasons to make this part of your sound-healing journey.</p></div><div class="sp-cards"><article class="sp-card"><small>01 — PRACTICE</small><h3>Why sound healing is more than the instrument.</h3><p>Explore the intent, mindset and personal development behind the visible practice.</p></article><article class="sp-card"><small>02 — PERSPECTIVE</small><h3>Ideas that challenge conventional boundaries.</h3><p>Consider energy, resonance and alternative frameworks with curiosity.</p></article><article class="sp-card"><small>03 — JOURNEY</small><h3>A reflective book, not a rigid manual.</h3><p>An invitation to notice what resonates and shape your own relationship with the work.</p></article></div></section>
    <section class="sp-content" id="details"><div class="sp-heading"><h2>Product information.</h2><p>Everything you need to know before ordering.</p></div><div class="sp-details"><dl class="sp-table"><div class="sp-row"><dt>Title</dt><dd>{{ $title }}</dd></div><div class="sp-row"><dt>Author</dt><dd>{{ data_get($product, 'author', 'Ian Snowball') }}</dd></div><div class="sp-row"><dt>Publisher</dt><dd>{{ data_get($product, 'publisher', 'New Haven Publishing Ltd') }}</dd></div><div class="sp-row"><dt>Print length</dt><dd>{{ data_get($product, 'pages', '168') }} pages</dd></div><div class="sp-row"><dt>Dimensions</dt><dd>{{ data_get($product, 'dimensions.length_mm', 152) }} × {{ data_get($product, 'dimensions.width_mm', 11) }} × {{ data_get($product, 'dimensions.height_mm', 228) }} mm</dd></div><div class="sp-row"><dt>Product code</dt><dd>{{ data_get($product, 'sku', '—') }}</dd></div></dl><div class="sp-panel"><h3>Ordering &amp; delivery</h3><p>Orders are confirmed by email and dispatched using a tracked UK service. Delivery estimates are shown above.</p><a class="sp-btn sp-buy-button" href="#faq">View delivery FAQs</a></div></div></section>
    <section class="sp-content" id="faq"><div class="sp-heading"><h2>Delivery &amp; returns.</h2><p>Questions answered where customers naturally look for them.</p></div><div class="sp-faq"><div class="sp-faq-item"><button class="sp-faq-button" type="button" aria-expanded="false">When will my paperback arrive?<span>＋</span></button><div class="sp-faq-panel">UK orders are normally dispatched within one working day and delivered in approximately 2–4 working days.</div></div><div class="sp-faq-item"><button class="sp-faq-button" type="button" aria-expanded="false">Can I return the book?<span>＋</span></button><div class="sp-faq-panel">Unused physical books can be returned within 30 days under the store returns policy.</div></div></div></section>
</div>
<div class="sp-mobile-buy"><div><strong data-mobile-total>£{{ number_format($price, 2) }}</strong><small>{{ $inStock ? 'IN STOCK' : 'SOLD OUT' }}</small></div><button class="sp-btn sp-add-button js-add-to-cart js-open-cart" type="button" @foreach($cartData as $key=>$value) data-{{ $key }}="{{ $value }}" @endforeach>Add to cart</button></div>
<script>
(() => {
    const quantity = document.querySelector('[data-qty]');
    const unitPrice = {{ json_encode($price) }};
    const buttons = document.querySelectorAll('.store-product-page .js-add-to-cart, .store-product-page .js-buy-now');
    const total = document.querySelector('[data-total]');
    const mobileTotal = document.querySelector('[data-mobile-total]');
    const quantityLabel = document.querySelector('[data-qty-label]');
    const pluralLabel = document.querySelector('[data-qty-plural]');
    const mobileCartButton = document.querySelector('.sp-mobile-buy .js-add-to-cart');
    const syncQuantity = () => {
        const value = Math.min(20, Math.max(1, Number(quantity?.value || 1)));
        if (quantity) quantity.value = value;
        buttons.forEach((button) => button.dataset.qty = String(value));
        const formatted = `£${(unitPrice * value).toFixed(2)}`;
        if (total) total.textContent = formatted;
        if (mobileTotal) mobileTotal.textContent = formatted;
        if (quantityLabel) quantityLabel.textContent = String(value);
        if (pluralLabel) pluralLabel.textContent = value === 1 ? '' : 's';
    };
    document.querySelectorAll('.sp-faq-button').forEach((button) => button.addEventListener('click', () => {
        const panel = button.nextElementSibling;
        const open = panel.classList.toggle('is-open');
        button.setAttribute('aria-expanded', String(open));
        button.querySelector('span').textContent = open ? '−' : '＋';
    }));
    document.querySelectorAll('[data-qty-minus],[data-qty-plus]').forEach((button) => button.addEventListener('click', () => {
        if (!quantity) return;
        quantity.value = Number(quantity.value || 1) + (button.hasAttribute('data-qty-plus') ? 1 : -1);
        syncQuantity();
    }));
    quantity?.addEventListener('input', syncQuantity);
    quantity?.addEventListener('change', syncQuantity);
    mobileCartButton?.addEventListener('click', (event) => {
        if (typeof window.WOW_addToCart !== 'function') return;
        event.preventDefault();
        event.stopPropagation();
        window.WOW_addToCart(mobileCartButton);
    });
    syncQuantity();
})();
</script>
@endsection
