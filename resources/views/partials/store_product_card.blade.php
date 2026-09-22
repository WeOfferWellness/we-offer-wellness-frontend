@php
    $title = trim((string) data_get($product, 'title', 'Physical product'));
    $summary = trim((string) data_get($product, 'summary', 'Physical product delivered directly to you.'));
    $brand = trim((string) data_get($product, 'brand', 'We Offer Wellness'));
    $image = trim((string) data_get($product, 'image', data_get($product, 'image_url', '')));
    $url = (string) data_get($product, 'url', '#');
    $price = data_get($product, 'price');
    $price = is_numeric($price) ? app(\App\Services\MarketplacePricingService::class)->buyerPrice($price, ['vendor_name' => $brand], null, false) : $price;
    $priceLabel = is_numeric($price) ? '£' . rtrim(rtrim(number_format((float) $price, 2, '.', ''), '0'), '.') : '£0';
    $trackingId = (int) data_get($product, 'id', 0);
    $rankingRequestId = trim((string) data_get($product, 'ranking_request_id', ''));
    $analyticsItem = [
        'id' => 'store-' . $trackingId,
        'product_id' => $trackingId,
        'title' => $title,
        'price' => is_numeric($price) ? (float) $price : 0,
        'currency' => 'GBP',
        'source_version' => 'store',
        'catalogue_type' => 'physical_product',
    ];
@endphp

{{--
@once
<style>
    .wow49-store-blade{position:relative;display:flex;flex-direction:column;width:100%;min-width:223px;max-width:300px;height:430px;overflow:hidden;border:1px solid rgba(16,24,40,.1);border-radius:13px;background:#fff;box-shadow:0 4px 16px rgba(16,24,40,.05);transition:transform 180ms ease,border-color 180ms ease,box-shadow 180ms ease}.wow49-store-blade:hover,.wow49-store-blade:focus-within{transform:translateY(-2px);border-color:rgba(79,147,129,.42);box-shadow:0 20px 48px rgba(16,24,40,.085)}.wow49-store-blade>a:first-child{position:absolute;inset:0;z-index:1}.wow49-store-blade__media{position:relative;height:145px;flex:0 0 145px;overflow:hidden;background:#eef2f4}.wow49-store-blade__media>img{width:100%;height:100%;object-fit:cover;transition:transform 240ms ease}.wow49-store-blade:hover .wow49-store-blade__media>img{transform:scale(1.035)}.wow49-store-blade__signal{position:absolute;top:10px;left:10px;padding:5px 10px;border-radius:999px;background:rgba(255,247,237,.94);color:#b54708;font-size:11px;font-weight:700}.wow49-store-blade__tags{position:absolute;bottom:10px;left:10px;display:flex;gap:4px}.wow49-store-blade__tags span{padding:4px 7px;border:1px solid rgba(240,200,121,.9);border-radius:999px;background:rgba(255,229,179,.96);color:#6f4b10;font-size:10px;font-weight:700}.wow49-store-blade__tags .type{border-color:rgba(199,216,251,.9);background:rgba(232,240,255,.96);color:#254a85}.wow49-store-blade__body{display:flex;flex:1;flex-direction:column;gap:6px;padding:11px 13px}.wow49-store-blade h3{display:-webkit-box;min-height:2.4em;margin:0;overflow:hidden;font-size:18px;font-weight:300;line-height:1.2;letter-spacing:-.04em;-webkit-box-orient:vertical;-webkit-line-clamp:2}.wow49-store-blade p{margin:0;color:#667085;font-size:12px}.wow49-store-blade__summary{display:-webkit-box;overflow:hidden;line-height:1.45;-webkit-box-orient:vertical;-webkit-line-clamp:3}.wow49-store-blade__availability{margin-top:auto;padding:5px 8px;border-radius:7px;background:#f6f8fa;color:#344054;font-size:12px;font-weight:600}.wow49-store-blade footer{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 13px 11px;border-top:1px solid #edf0f2}.wow49-store-blade small{display:block;color:#98a2b3;font-size:11px}.wow49-store-blade strong{font-size:20px;font-weight:400;letter-spacing:-.05em}.wow49-store-blade__buttons{display:flex;gap:5px}.wow49-store-blade button:not(.btn-wow),.wow49-store-blade footer a:not(.btn-wow){position:relative;z-index:3;display:inline-flex;align-items:center;justify-content:center;height:36px;padding:0 10px;border:1px solid #4f9381;border-radius:4px;background:#fff;color:#2f6f60;font-size:11px;text-decoration:none;cursor:pointer}    .wow49-store-blade footer a:not(.btn-wow){border:0;background:#4f9381;color:#fff}@media(max-width:560px){.wow49-store-blade{min-width:0;height:360px}.wow49-store-blade__media{height:138px;flex-basis:138px}.wow49-store-blade h3{font-size:15px}.wow49-store-blade footer{padding:9px 11px 10px}.wow49-store-blade strong{font-size:18px}.wow49-store-blade button:not(.btn-wow),.wow49-store-blade footer a:not(.btn-wow){height:34px;font-size:10px}}
</style>
@endonce
--}}

<article class="wow49-store-blade" aria-label="Product card {{ $title }}" data-wow-analytics-item="{!! e(json_encode($analyticsItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !!}" @if($trackingId > 0) data-product-id="{{ $trackingId }}" data-source-version="store" @endif @if($rankingRequestId !== '') data-ranking-request-id="{{ $rankingRequestId }}" @endif>
    <a href="{{ $url }}" aria-label="Open {{ $title }}"></a>
    <div class="wow49-store-blade__media">
        @if($image)<img src="{{ $image }}" alt="{{ $title }}" loading="lazy">@endif
        <span class="wow49-store-blade__signal">Ships to you</span>
        <div class="wow49-store-blade__tags"><span>Physical product</span><span class="type">Store</span></div>
    </div>
    <div class="wow49-store-blade__body"><h3>{{ $title }}</h3><p>{{ $brand }}</p><p class="wow49-store-blade__summary">{{ $summary }}</p><span class="wow49-store-blade__availability">Secure checkout</span></div>
    <footer><div><small>Price</small><strong>{{ $priceLabel }}</strong></div><a href="{{ $url }}" class="btn-wow btn-wow--primary btn-wow--card"><span class="btn-label">VIEW PRODUCT</span></a></footer>
</article>
