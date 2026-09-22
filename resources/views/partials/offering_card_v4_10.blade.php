@php
    $cardClass = $eventStyle ? 'wow410-card--event' : '';
    $cardTypeLabel = $storeProduct ? 'Store' : ($gift ? 'Gift card' : $typeLabel);
    $cardButtonLabel = $storeProduct
        ? 'VIEW PRODUCT'
        : ($gift ? 'BUY GIFT CARD' : ($isPastEvent ? 'VIEW' : 'VIEW & BOOK'));
    $cardButtonAria = $storeProduct
        ? 'View product '.$title
        : ($gift ? 'Buy gift card '.$title : ($isPastEvent ? 'View details for '.$title : 'View and book '.$title));
    $cardAnalytics = e(json_encode($analyticsItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
@endphp

<article class="wow410-card {{ $cardClass }}"
    aria-label="{{ $cardButtonAria }}"
    data-wow-analytics-item="{!! $cardAnalytics !!}"
    @if($trackingId) data-product-id="{{ $trackingId }}" data-source-version="{{ $trackingSource }}" @endif
    @if($rankingRequestId !== '') data-ranking-request-id="{{ $rankingRequestId }}" @endif>
    <a href="{{ $gift ? url('/giftcards') : $url }}" class="wow410-card__link" aria-label="{{ $cardButtonAria }}"></a>

    @if($eventStyle)
        <div class="wow410-card__media">
            <img src="{{ $image }}" alt="{{ $title }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $placeholderImage }}';">
        </div>
        @if($start)<span class="wow410-card__date"><b>{{ $eventMonth }}</b><strong>{{ $eventDay }}</strong></span>@endif
        @if($businessAccelerator)<img class="wow410-card__rosette" src="https://studio.weofferwellness.co.uk/storage/uploads/images/78aa908f-334b-45c0-9220-1c4d84053c5e.png" alt="Business Accelerator partner">@endif
        <div class="wow410-card__event-content">
            <div class="wow410-card__event-tags">
                <span class="wow410-card__tag">{{ $category }}</span>
                <span class="wow410-card__tag wow410-card__tag--sub">{{ $cardTypeLabel }}</span>
            </div>
            <h3 class="wow410-card__title">{{ $title }}</h3>
            @if($provider)<p class="wow410-card__provider">with {{ ucwords(strtolower($provider)) }}</p>@endif
            <p class="wow410-card__location">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $locationLabel }}</span>
            </p>
            <div class="wow410-card__event-bottom">
                <div><small>From</small><strong>{{ $priceLabel }}</strong></div>
                @include('partials.offering-button', ['href' => $url, 'label' => $cardButtonLabel, 'ariaLabel' => $cardButtonAria])
            </div>
        </div>
    @else
        <div class="wow410-card__media">
            <img src="{{ $image }}" alt="{{ $title }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $placeholderImage }}';">
            @if($value('fomo_text'))<span class="wow410-card__signal">{{ $value('fomo_text') }}</span>@endif
            @if($businessAccelerator)<img class="wow410-card__rosette" src="https://studio.weofferwellness.co.uk/storage/uploads/images/78aa908f-334b-45c0-9220-1c4d84053c5e.png" alt="Business Accelerator partner">@endif
            <span class="wow410-card__type">{{ $cardTypeLabel }}</span>
            <div class="wow410-card__tags">
                <span class="wow410-card__tag">{{ $category }}</span>
                @if($subcategoryRaw)<span class="wow410-card__tag wow410-card__tag--sub">{{ $subcategoryRaw }}</span>@endif
            </div>
        </div>
        <div class="wow410-card__body">
            <h3 class="wow410-card__title">{{ $title }}</h3>
            @if($provider)<p class="wow410-card__provider">with {{ ucwords(strtolower($provider)) }}</p>@endif
            <div class="wow410-card__rating">
                <span class="wow410-card__stars">{{ str_repeat('★', min(5, max(0, round($rating)))) }}{{ str_repeat('☆', 5 - min(5, max(0, round($rating)))) }}</span>
                <span>{{ number_format($rating, 1) }} · {{ $reviews ? $reviews.' reviews' : 'Be the first to review' }}</span>
            </div>
            <p class="wow410-card__location">
                @if($online && !$locations->contains(fn ($item) => strtolower($item) !== 'online'))
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6.95 0 0 1 6.95 0M12 20v.01"/></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11Z"/><circle cx="12" cy="10" r="3"/></svg>
                @endif
                <span>{{ $locationLabel }}</span>
            </p>
            @if($description)<p class="wow410-card__description">{{ $description }}</p>@endif
            <div class="wow410-card__availability {{ $availabilityTone }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <span>{{ $availabilityLabel }}</span>
            </div>
        </div>
        <footer class="wow410-card__footer">
            <div class="wow410-card__price"><small>{{ $storeProduct ? 'Price' : 'From' }}</small><strong>{{ $priceLabel }}</strong></div>
            @include('partials.offering-button', ['href' => $gift ? url('/giftcards') : $url, 'label' => $cardButtonLabel, 'ariaLabel' => $cardButtonAria])
        </footer>
    @endif
</article>
