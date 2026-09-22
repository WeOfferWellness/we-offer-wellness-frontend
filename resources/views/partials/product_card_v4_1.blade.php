@php
    $value = fn ($key, $default = null) => data_get($product, $key, $default);
    $storeProduct = collect([
        $value('kind'),
        $value('product_kind'),
        $value('source_type'),
    ])->contains(fn ($value) => in_array(strtolower(trim((string) $value)), ['physical_product', 'store_product'], true))
        || (bool) $value('store_product_id', false);
    $title = trim((string) $value('title', $value('name', 'Untitled')));
    $url = trim((string) $value('url', '')) ?: app(\App\Services\SeoStructureService::class)->canonicalProductUrl($product);
    $image = is_object($product) && method_exists($product, 'getFirstImageUrl')
        ? $product->getFirstImageUrl()
        : (string) ($value('image', $value('image_url', $value('featured_image', ''))));
    $placeholderImage = rtrim((string) config('services.location_media_url', 'https://studio.weofferwellness.co.uk'), '/').'/assets/img/no-product-image.jpg';
    if (trim((string) $image) === '') {
        $image = $placeholderImage;
    }
    $price = $value('variants_min_price', $value('price_min', $value('price', $value('base_price'))));
    $pricing = app(\App\Services\MarketplacePricingService::class);
    $pricingVendor = [
        'vendor_name' => $value('vendor_name', $value('practitioner_name', $value('vendor.name', $value('vendor_details.name', '')))),
        'user' => ['name' => $value('vendor.user.name', ''), 'email' => $value('vendor.user.email', '')],
    ];
    $price = is_numeric($price) ? $pricing->buyerPrice($price, $pricingVendor, (int) $value('vendor_id', 0)) : $price;
    $priceLabel = is_numeric($price) ? '£' . rtrim(rtrim(number_format((float) $price, 2, '.', ''), '0'), '.') : '£0';
    $typeRaw = strtolower(trim((string) ($value('type.name', $value('type_label', $value('type_name', $value('product_type', 'therapy')))))));
    $categoryRaw = (string) $value('category.name', $value('category.label', $value('category_name', $value('category_label', $value('category', '')))));
    $subcategoryRaw = trim((string) $value('subcategory.name', $value('subcategory.label', $value('subcategory_name', $value('subcategory_label', '')))));
    $typeSource = $typeRaw . ' ' . strtolower($categoryRaw) . ' ' . $url;
    $kind = 'therapy';
    if (str_contains($typeSource, 'retreat')) {
        $kind = 'retreat';
    } elseif (str_contains($typeSource, 'workshop')) {
        $kind = 'workshop';
    } elseif (str_contains($typeSource, 'event')) {
        $kind = 'event';
    } elseif (str_contains($typeSource, 'class')) {
        $kind = 'class';
    }
    $typeLabel = ucfirst($kind);
    $category = trim($categoryRaw) !== '' ? ucwords(strtolower(str_replace(['_', '-'], ' ', $categoryRaw))) : $typeLabel;
    $categoryWords = preg_split('/\s+/', $category, -1, PREG_SPLIT_NO_EMPTY);
    $categoryShort = mb_strlen($category) > 20 && count($categoryWords) > 1
        ? implode('', array_map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)), $categoryWords))
        : $category;
    $categoryIsAbbreviated = $categoryShort !== $category;
    $gift = (bool) preg_match('/gift\s*card|giftcard|voucher|e-?gift/i', strtolower(implode(' ', [$title, $categoryRaw, $typeRaw, (string) $value('slug', '')])));
    $eventStyle = in_array($kind, ['event', 'workshop', 'retreat'], true);
    $provider = trim((string) $value('vendor_name', $value('practitioner_name', $value('vendor.name', $value('vendor_details.name', '')))));
    $locations = is_object($product) && method_exists($product, 'getLocations') ? $product->getLocations() : (array) $value('locations', [$value('location', $value('location_name', $value('venue', '')))]);
    $locations = collect($locations)->map(function ($location) {
        return is_object($location) || is_array($location)
            ? trim((string) data_get($location, 'formatted_address', data_get($location, 'label', data_get($location, 'name', data_get($location, 'city', '')))))
            : trim((string) $location);
    })->filter()->values();
    $channels = collect((array) $value('channels', []))
        ->map(fn ($channel) => strtolower(trim((string) $channel)));
    $online = (bool) $value('online_only', false)
        || $channels->contains('online')
        || $locations->contains(fn ($location) => strtolower($location) === 'online')
        || str_contains(strtolower((string) $value('format', '')), 'online');
    $onlineOnly = $online && ($locations->isEmpty() || $locations->every(fn ($item) => strtolower($item) === 'online'));
    $location = $locations->first(fn ($item) => strtolower($item) !== 'online') ?: ($onlineOnly ? 'Online Exclusive' : ($online ? 'Online' : 'In person'));
    $countryFallback = trim((string) $value('country', $value('vendor.country', $value('vendor.user.country', $value('vendor_details.country', '')))));
    $countryCode = function (string $country): string {
        return match (strtolower(trim($country))) {
            'gb', 'uk', 'u.k.', 'united kingdom', 'great britain', 'england', 'scotland', 'wales', 'northern ireland' => 'UK',
            'us', 'u.s.', 'usa', 'u.s.a.', 'united states', 'united states of america' => 'USA',
            'au', 'australia' => 'AU',
            'ca', 'canada' => 'CA',
            'ie', 'ireland' => 'IE',
            'nz', 'new zealand' => 'NZ',
            default => '',
        };
    };
    if ($location === 'Online' || $location === 'In person') {
        $locationLabel = $location;
    } else {
        $parts = collect(preg_split('/\\s*,\\s*/', $location) ?: [])
            ->map(fn ($part) => trim((string) $part))
            ->reject(fn ($part) => preg_match('/^(?:[A-Z]{1,2}\\d[A-Z\\d]?\\s*\\d[A-Z]{2}|\\d{5}(?:-\\d{4})?)$/i', $part) === 1)
            ->filter()
            ->values();
        $country = $countryCode((string) $parts->last());
        if ($country !== '') {
            $parts->pop();
        } else {
            $country = $countryCode($countryFallback);
        }
        $place = (string) $parts->first();
        $locationLabel = implode(', ', array_filter([$place ?: $location, $country]));
    }
    $planKey = strtolower(trim((string) $value('plan_key', $value('plan_label', $value('vendor.plan_key', $value('vendor.plan_label', $value('vendor.plan.slug', $value('vendor.plan.name', $value('vendor_details.plan_key', $value('vendor_details.plan_label', $value('vendor.tier.tier', $value('vendor.user.tier.tier', $value('vendor.user.account_type', '')))))))))))));
    $businessAccelerator = in_array(str_replace(['_', ' '], '-', $planKey), ['business-accelerator', 'businessaccelerator', 'business-accelerator-package', 'core'], true);
    $rating = (float) $value('rating', $value('reviews_avg_rating', 0));
    $reviews = (int) $value('review_count', $value('reviews_count', 0));
    $description = collect([
        $value('benefit'),
        $value('summary'),
        $value('description_short'),
        $value('description'),
        $value('excerpt'),
        $value('body_html'),
        $value('what_to_expect'),
        $value('included'),
    ])->map(fn ($text) => trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? ''))->first(fn (string $text): bool => $text !== '') ?? '';
    $start = $eventStyle ? \App\Support\EventListing::startAt($product) : null;
    $isPastEvent = $eventStyle && (bool) $value('is_past_event', \App\Support\EventListing::isPast($product));
    $eventMonth = $start?->format('M');
    $eventDay = $start?->format('d');
    $trackingId = $value('id');
    $trackingId = is_numeric($trackingId) && (int) $trackingId > 0 ? (int) $trackingId : null;
    $trackingSource = strtolower(trim((string) $value('source_version', $value('version', 'legacy'))));
    $trackingSource = in_array($trackingSource, ['v3', 'store'], true) ? $trackingSource : 'legacy';
    $rankingRequestId = trim((string) $value('ranking_request_id', ''));
    $analyticsItem = [
        'id' => $trackingId ?? '',
        'title' => $title,
        'price' => is_numeric($price) ? (float) $price : 0,
        'currency' => 'GBP',
        'source_version' => $trackingSource,
        'catalogue_type' => $kind,
        'modality' => $category,
        'provider_id' => $value('vendor_id', $value('provider_id', '')),
    ];

    $dayMap = ['mon' => 1, 'monday' => 1, 'tue' => 2, 'tuesday' => 2, 'wed' => 3, 'wednesday' => 3, 'thu' => 4, 'thursday' => 4, 'fri' => 5, 'friday' => 5, 'sat' => 6, 'saturday' => 6, 'sun' => 0, 'sunday' => 0];
    $availableDays = [];
    $vendorUser = $value('vendor.user');
    $weeklyWindows = $vendorUser instanceof \App\Models\User
        ? \App\Services\AvailabilityWindowService::buildWeeklyWindows($vendorUser)
        : [];
    foreach ($weeklyWindows as $dayName => $rule) {
        if (data_get($rule, 'enabled') === true && count((array) data_get($rule, 'windows', []))) {
            $availableDays[] = $dayMap[strtolower($dayName)] ?? null;
        }
    }
    $sources = [$value('availability_days', []), $value('availability_calendar', []), $value('vendor.user.defaultAvailability', $value('vendor.user.default_availability', []))];
    $weekly = $value('vendor.availability.weekly_rules', $value('vendor_details.availability.weekly_rules', []));
    foreach ((array) $weekly as $dayName => $rule) {
        if (data_get($rule, 'enabled') === true && count((array) data_get($rule, 'windows', []))) {
            $availableDays[] = $dayMap[strtolower($dayName)] ?? null;
        }
    }
    foreach ($sources as $source) {
        foreach (is_array($source) ? $source : [] as $row) {
            $enabled = data_get($row, 'is_available', data_get($row, 'available', data_get($row, 'enabled', true)));
            if (in_array($enabled, [false, 0, '0', 'false', 'no'], true)) continue;
            $day = data_get($row, 'day_of_week', data_get($row, 'day', data_get($row, 'weekday', is_scalar($row) ? $row : null)));
            $day = is_numeric($day) ? (int) $day : ($dayMap[strtolower(trim((string) $day))] ?? null);
            if ($day !== null) $availableDays[] = $day === 7 ? 0 : $day;
        }
    }
    $availableDays = array_values(array_unique(array_filter($availableDays, fn ($day) => is_int($day) && $day >= 0 && $day <= 6)));
    $today = now()->dayOfWeek;
    $availabilityTone = 'neutral';
    if (!$availableDays) {
        $availabilityLabel = 'Flexible dates';
    } elseif (count($availableDays) === 7) {
        $availabilityLabel = 'Available every day';
        $availableToday = true;
        $availabilityTone = 'today';
    } else {
        $offset = 0;
        while ($offset < 7 && !in_array(($today + $offset) % 7, $availableDays, true)) $offset++;
        $availabilityLabel = $offset === 0 ? 'Available today' : ($offset === 1 ? 'Available tomorrow' : 'Next available: ' . ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][($today + $offset) % 7]);
        $availableToday = $offset === 0;
        $availabilityTone = $offset === 0 ? 'today' : ($offset === 1 ? 'tomorrow' : 'neutral');
    }
@endphp

{{-- v4.10 is now the canonical renderer for every offering-card caller. --}}
@if(($cardVersion ?? 'v4.10') === 'v4.10')
    @include('partials.offering_card_v4_10')
@elseif($storeProduct)
    @include('partials.store_product_card', ['product' => $product])
@else

{{--
@once
<style>
    .wow49-blade-card{position:relative;display:flex;flex-direction:column;width:100%;min-width:223px;max-width:300px;height:430px;overflow:hidden;border:1px solid rgba(16,24,40,.1);border-radius:13px;background:#fff;box-shadow:0 4px 16px rgba(16,24,40,.05);color:#101828;transition:transform 180ms ease,border-color 180ms ease,box-shadow 180ms ease}.wow49-blade-card:hover,.wow49-blade-card:focus-within{transform:translateY(-2px);border-color:rgba(79,147,129,.42);box-shadow:0 20px 48px rgba(16,24,40,.085)}.wow49-blade-card__link{position:absolute;inset:0;z-index:1}.wow49-blade-card__media{position:relative;height:145px;flex:0 0 145px;overflow:hidden;background:#eef2f4}.wow49-blade-card__media>img:first-child,.wow49-blade-card__event-image>img,.wow49-blade-card__gift-media>img{width:100%;height:100%;display:block;object-fit:cover;transition:transform 240ms ease}.wow49-blade-card:hover .wow49-blade-card__media>img:first-child,.wow49-blade-card:hover .wow49-blade-card__event-image>img,.wow49-blade-card:hover .wow49-blade-card__gift-media>img{transform:scale(1.035)}.wow49-blade-card__signal{position:absolute;top:10px;left:10px;z-index:2;padding:5px 10px;border-radius:999px;background:rgba(255,247,237,.94);color:#b54708;font-size:11px;font-weight:700}.wow49-blade-card__rosette{position:absolute;top:8px;right:8px;z-index:3;width:48px;height:48px;object-fit:contain}.wow49-blade-card__tags{position:absolute;bottom:10px;left:10px;z-index:2;display:flex;gap:4px}.wow49-blade-card__tags span{height:22px;padding:4px 7px;border:1px solid rgba(240,200,121,.9);border-radius:999px;background:rgba(255,229,179,.96);color:#6f4b10;font-size:10px;font-weight:700}.wow49-blade-card__tags .type{border-color:rgba(199,216,251,.9);background:rgba(232,240,255,.96);color:#254a85}.wow49-blade-card__body{display:flex;flex:1;flex-direction:column;gap:4px;min-height:0;overflow:hidden;padding:11px 13px 10px}.wow49-blade-card h3{display:-webkit-box;min-height:2.4em;margin:0;overflow:hidden;color:#101828;font-size:18px;font-weight:300;line-height:1.2;letter-spacing:-.04em;-webkit-box-orient:vertical;-webkit-line-clamp:2}.wow49-blade-card:hover h3{color:#4f9381}.wow49-blade-card__provider,.wow49-blade-card__location{margin:0;color:#667085;font-size:12px}.wow49-blade-card__rating{display:flex;gap:5px;color:#344054;font-size:12px}.wow49-blade-card__stars{color:#f5c84b;letter-spacing:-1px}.wow49-blade-card__description{display:-webkit-box;min-height:4.35em;margin:0;overflow:hidden;color:#667085;font-size:12.5px;line-height:1.45;-webkit-box-orient:vertical;-webkit-line-clamp:3}.wow49-blade-card__availability{display:flex;align-items:center;gap:5px;margin-top:auto;padding:5px 8px;border-radius:7px;background:#f6f8fa;color:#344054;font-size:12px;font-weight:600}.wow49-blade-card__availability.today{background:#eaf5f1;color:#2f6f60}.wow49-blade-card__footer{position:relative;z-index:2;display:flex;flex-shrink:0;align-items:center;justify-content:space-between;gap:8px;padding:9px 13px 11px;border-top:1px solid #edf0f2}.wow49-blade-card__footer small,.wow49-blade-card__event-bottom small{display:block;color:#98a2b3;font-size:11px;line-height:1}.wow49-blade-card__footer strong{font-size:20px;font-weight:400;letter-spacing:-.05em}.wow49-blade-card__buttons{display:flex;gap:5px}.wow49-blade-card__button{position:relative;z-index:3;display:inline-flex;align-items:center;justify-content:center;height:36px;padding:0 12px;border:0;border-radius:4px;background:#4f9381;color:#fff;font-size:11px;text-decoration:none;white-space:nowrap;cursor:pointer}.wow49-blade-card__button.secondary{border:1px solid #4f9381;background:#fff;color:#2f6f60}.wow49-blade-card--event{background:#20312d}.wow49-blade-card__event-image{position:absolute;inset:0}.wow49-blade-card__event-image:after{position:absolute;inset:0;content:"";background:linear-gradient(to top,rgba(10,18,30,.9),rgba(10,18,30,.28) 60%,rgba(0,0,0,.05))}.wow49-blade-card__date{position:absolute;top:12px;left:12px;z-index:3;display:flex;width:48px;height:54px;flex-direction:column;align-items:center;justify-content:center;border-radius:10px;background:rgba(255,255,255,.97);box-shadow:0 6px 18px rgba(0,0,0,.2)}.wow49-blade-card__date b{color:#4f9381;font-size:9px;letter-spacing:.08em;text-transform:uppercase}.wow49-blade-card__date strong{font-size:21px;line-height:1.1}.wow49-blade-card__event-content{position:relative;z-index:2;display:flex;flex:1;flex-direction:column;justify-content:flex-end;padding:14px;color:#fff}.wow49-blade-card--event .wow49-blade-card__tags{position:static;margin-bottom:7px}.wow49-blade-card--event h3{color:#fff}.wow49-blade-card--event .wow49-blade-card__provider,.wow49-blade-card--event .wow49-blade-card__location{color:rgba(255,255,255,.8)}.wow49-blade-card__event-bottom{display:flex;align-items:center;justify-content:space-between;gap:8px}.wow49-blade-card__event-bottom small{color:rgba(255,255,255,.6)}.wow49-blade-card__event-bottom strong{font-size:19px;font-weight:400}.wow49-blade-card--gift .wow49-blade-card__gift-media{position:relative;height:70%;flex:0 0 70%;overflow:hidden;background:linear-gradient(135deg,#eff8f5,#fff)}.wow49-blade-card__gift-badge{position:absolute;top:10px;left:10px;padding:5px 9px;border:1px solid rgba(79,147,129,.2);border-radius:999px;background:rgba(232,245,241,.95);color:#2f6f60;font-size:10.5px;font-weight:700}.wow49-blade-card--gift .wow49-blade-card__body{gap:2px;padding:12px 14px 10px}    .wow49-blade-card--gift h3{min-height:0}@media(max-width:560px){.wow49-blade-card{min-width:0;height:360px}.wow49-blade-card__media{height:138px;flex-basis:138px}.wow49-blade-card h3{font-size:15px}.wow49-blade-card__description{display:none}.wow49-blade-card__body{padding:10px 11px 9px}.wow49-blade-card__footer{padding:9px 11px 10px}.wow49-blade-card__button{height:34px;padding:0 10px;font-size:10px}.wow49-blade-card__rosette{width:40px;height:40px}}
</style>
@endonce
--}}

@if($gift)
<article class="wow49-blade-card wow49-blade-card--gift" aria-label="Gift card {{ $title }}" data-wow-analytics-item="{!! e(json_encode($analyticsItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !!}" @if($trackingId) data-product-id="{{ $trackingId }}" data-source-version="{{ $trackingSource }}" @endif @if($rankingRequestId !== '') data-ranking-request-id="{{ $rankingRequestId }}" @endif>
    <a href="{{ url('/giftcards') }}" class="wow49-blade-card__link" aria-label="Buy {{ $title }}"></a>
    <div class="wow49-blade-card__gift-media"><img src="{{ $image }}" alt="{{ $title }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $placeholderImage }}';"><span class="wow49-blade-card__gift-badge">Digital gift card</span></div>
    <div class="wow49-blade-card__body"><h3>{{ $title }}</h3><p class="wow49-blade-card__provider">Instant email delivery</p></div>
    <footer class="wow49-blade-card__footer"><div><small>From</small><strong>{{ $priceLabel }}</strong></div><a href="{{ url('/giftcards') }}" class="btn-wow btn-wow--primary btn-wow--card wow49-blade-card__button"><span class="btn-label">BUY GIFT CARD</span></a></footer>
</article>
@elseif($eventStyle)
<article class="wow49-blade-card wow49-blade-card--event" aria-label="{{ $typeLabel }} card {{ $title }}" data-wow-analytics-item="{!! e(json_encode($analyticsItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !!}" @if($trackingId) data-product-id="{{ $trackingId }}" data-source-version="{{ $trackingSource }}" @endif @if($rankingRequestId !== '') data-ranking-request-id="{{ $rankingRequestId }}" @endif>
    <a href="{{ $url }}" class="wow49-blade-card__link" aria-label="{{ $isPastEvent ? 'View details for' : 'View and book' }} {{ $title }}"></a>
    <div class="wow49-blade-card__event-image"><img src="{{ $image }}" alt="{{ $title }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $placeholderImage }}';"></div>
    @if($start)<span class="wow49-blade-card__date"><b>{{ $eventMonth }}</b><strong>{{ $eventDay }}</strong></span>@endif
    @if($isPastEvent)<span class="wow49-blade-card__past-status">PAST EVENT</span>@endif
    @if($businessAccelerator)<img class="wow49-blade-card__rosette" src="https://studio.weofferwellness.co.uk/storage/uploads/images/78aa908f-334b-45c0-9220-1c4d84053c5e.png" alt="Business Accelerator partner">@endif
    <span class="wow49-blade-card__type-top">{{ $typeLabel }}</span><div class="wow49-blade-card__event-content"><div class="wow49-blade-card__tags"><span class="wow49-blade-card__category {{ $categoryIsAbbreviated ? 'is-abbreviated' : '' }}" @if($categoryIsAbbreviated) data-mobile-label="{{ $categoryShort }}" @endif>{{ $category }}</span>@if($subcategoryRaw)<span class="wow49-blade-card__subcategory">{{ $subcategoryRaw }}</span>@endif</div><h3>{{ $title }}</h3>@if($provider)<p class="wow49-blade-card__provider">with {{ ucwords(strtolower($provider)) }}</p>@endif<p class="wow49-blade-card__location"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 7 7 11 7 11Z"/><circle cx="12" cy="10" r="3"/></svg>{{ $locationLabel }}</p><div class="wow49-blade-card__event-bottom"><div><small>From</small><strong>{{ $priceLabel }}</strong></div><a href="{{ $url }}" class="btn-wow btn-wow--primary btn-wow--card wow49-blade-card__button"><span class="btn-label">{{ $isPastEvent ? 'VIEW' : 'VIEW & BOOK' }}</span></a></div></div>
</article>
@else
<article class="wow49-blade-card" aria-label="Offering card {{ $title }}" data-wow-analytics-item="{!! e(json_encode($analyticsItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !!}" @if($trackingId) data-product-id="{{ $trackingId }}" data-source-version="{{ $trackingSource }}" @endif @if($rankingRequestId !== '') data-ranking-request-id="{{ $rankingRequestId }}" @endif>
    <a href="{{ $url }}" class="wow49-blade-card__link" aria-label="View and book {{ $title }}"></a>
    <div class="wow49-blade-card__media"><img src="{{ $image }}" alt="{{ $title }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $placeholderImage }}';">@if($value('fomo_text'))<span class="wow49-blade-card__signal">{{ $value('fomo_text') }}</span>@endif @if($businessAccelerator)<img class="wow49-blade-card__rosette" src="https://studio.weofferwellness.co.uk/storage/uploads/images/78aa908f-334b-45c0-9220-1c4d84053c5e.png" alt="Business Accelerator partner">@endif<span class="wow49-blade-card__type-top">{{ $typeLabel }}</span><div class="wow49-blade-card__tags"><span class="wow49-blade-card__category {{ $categoryIsAbbreviated ? 'is-abbreviated' : '' }}" @if($categoryIsAbbreviated) data-mobile-label="{{ $categoryShort }}" @endif>{{ $category }}</span>@if($subcategoryRaw)<span class="wow49-blade-card__subcategory">{{ $subcategoryRaw }}</span>@endif</div></div>
    <div class="wow49-blade-card__body"><h3>{{ $title }}</h3>@if($provider)<p class="wow49-blade-card__provider">with {{ ucwords(strtolower($provider)) }}</p>@endif<div class="wow49-blade-card__rating"><span class="wow49-blade-card__stars">{{ str_repeat('★', min(5, max(0, round($rating)))) }}{{ str_repeat('☆', 5 - min(5, max(0, round($rating)))) }}</span><span>{{ number_format($rating, 1) }} · {{ $reviews ? $reviews . ' reviews' : 'Be the first to review' }}</span></div><p class="wow49-blade-card__location">@if($online && !$locations->contains(fn ($item) => strtolower($item) !== 'online'))<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6.95 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1" fill="currentColor" stroke="none"/></svg>@else<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11Z"/><circle cx="12" cy="10" r="3"/></svg>@endif{{ $locationLabel }}</p>@if($description)<p class="wow49-blade-card__description">{{ $description }}</p>@endif<div class="wow49-blade-card__availability {{ $availabilityTone }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span>{{ $availabilityLabel }}</span></div></div>
    <footer class="wow49-blade-card__footer"><div><small>From</small><strong>{{ $priceLabel }}</strong></div><a href="{{ $url }}" class="btn-wow btn-wow--primary btn-wow--card wow49-blade-card__button"><span class="btn-label">VIEW &amp; BOOK</span></a></footer>
</article>
@endif
@endif
