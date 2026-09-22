@php
    $slug = \Illuminate\Support\Str::slug($product->title ?: (string) $product->id);
    $url = app(\App\Services\SeoStructureService::class)->canonicalProductUrl($product);

    $toLower = function ($s) {
        return function_exists('mb_strtolower') ? mb_strtolower((string) $s, 'UTF-8') : strtolower((string) $s);
    };
    $ucWords = function ($s) {
        return function_exists('mb_convert_case') ? mb_convert_case((string) $s, MB_CASE_TITLE, 'UTF-8') : ucwords((string) $s);
    };
    $normalizeTypeLabel = function ($value) use ($toLower, $ucWords) {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 'Experience';
        }

        $normalized = $toLower(str_replace(['_', '-'], ' ', $raw));
        $map = [
            'therapies' => 'Therapy',
            'therapy' => 'Therapy',
            'workshops' => 'Workshop',
            'workshop' => 'Workshop',
            'events' => 'Event',
            'event' => 'Event',
            'classes' => 'Class',
            'class' => 'Class',
            'retreats' => 'Retreat',
            'retreat' => 'Retreat',
            'experiences' => 'Experience',
            'experience' => 'Experience',
        ];

        return $map[$normalized] ?? $ucWords($normalized);
    };

    $title = trim((string) ($product->title ?? 'Untitled'));
    $titleFormatted = $ucWords($toLower($title));
    $typeRaw = trim((string) ($product->product_type ?? 'Experience'));
    $typeLabel = $normalizeTypeLabel($typeRaw);

    $categoryRaw = $product->category?->name
        ?? ($product->category_name ?? null)
        ?? ($product->category_label ?? null)
        ?? ((is_string($product->category ?? null)) ? $product->category : null);
    if (is_array($categoryRaw)) {
        $categoryRaw = $categoryRaw['name'] ?? reset($categoryRaw) ?? null;
    }
    $categoryLabel = $categoryRaw ? $normalizeTypeLabel($categoryRaw) : null;
    $categoryBadgeLabel = $categoryLabel ?? $typeLabel;

    $image = $product->getFirstImageUrl();
    $hasDisplayableImage = method_exists($product, 'hasDisplayableImage')
        ? $product->hasDisplayableImage()
        : !str_contains((string) $image, 'no-product-image.jpg');
    $priceMin = $product->variants_min_price ?? ($product->price ?? null);
    $pricing = app(\App\Services\MarketplacePricingService::class);
    $priceMin = is_numeric($priceMin) ? $pricing->buyerPrice($priceMin, $product->vendor ?? null, (int) ($product->vendor_id ?? 0)) : $priceMin;
    $priceDisplay = is_numeric($priceMin)
        ? '£' . rtrim(rtrim(number_format((float) $priceMin, 2, '.', ''), '0'), '.')
        : '—';
    $compareMin = $product->variants_min_compare ?? ($product->compare_at_price ?? null);
    $compareMin = is_numeric($compareMin) ? $pricing->buyerPrice($compareMin, $product->vendor ?? null, (int) ($product->vendor_id ?? 0)) : $compareMin;
    $vendorUser = data_get($product, 'vendor.user');
    $starterPlan = $vendorUser instanceof \App\Models\User
        ? $vendorUser->isStarterPlan()
        : strtolower(trim((string) data_get($product, 'plan_key', ''))) === 'starter';
    $businessAcceleratorPlan = $vendorUser instanceof \App\Models\User
        ? $vendorUser->isBusinessAcceleratorPlan()
        : \Illuminate\Support\Str::slug((string) data_get($product, 'plan_key', '')) === 'business-accelerator';

    $vendorReviewSummary = data_get($product, 'vendor.review_summary');
    if (! is_array($vendorReviewSummary)) {
        $vendorReviewSummary = [];
    }

    $vendorReviewCount = (int) ($vendorReviewSummary['count'] ?? 0);
    $vendorReviewRating = isset($vendorReviewSummary['rating']) ? round((float) $vendorReviewSummary['rating'], 1) : null;
    $productReviewCount = (int) ($product->reviews_count ?? 0);
    $productReviewRating = isset($product->reviews_avg_rating) ? round((float) $product->reviews_avg_rating, 1) : null;

    $reviewCount = $vendorReviewCount > 0 ? $vendorReviewCount : $productReviewCount;
    $rating = $vendorReviewCount > 0 ? $vendorReviewRating : $productReviewRating;
    if ($reviewCount > 0 && (! is_numeric($rating) || (float) $rating <= 0)) {
        $rating = 5.0;
    }
    $reviewSummary = $reviewCount > 0
        ? number_format((float) $rating, 1) . ' · ' . $reviewCount . ' review' . ($reviewCount === 1 ? '' : 's')
        : 'Be the first to review';

    $provider = $starterPlan
        ? 'Wellness practitioner'
        : trim((string) (
            $product->vendor_name
            ?? data_get($product, 'vendor.vendor_name')
            ?? ''
        ));
    $providerFormatted = $provider !== ''
        ? ($starterPlan ? $provider : $ucWords($toLower(str_replace('_', ' ', $provider))))
        : null;

    $locations = $product->getLocations();
    $hasOnline = in_array('Online', $locations, true);
    $physical = array_values(array_filter($locations, fn ($l) => $l !== 'Online'));
    $physicalShort = [];
    $seenShort = [];
    foreach ($physical as $locRaw) {
        $short = trim((string) $locRaw);
        if ($short === '') {
            continue;
        }
        $key = mb_strtolower($short);
        if (!isset($seenShort[$key])) {
            $seenShort[$key] = true;
            $physicalShort[] = $short;
        }
    }
    $primaryLocation = $physicalShort[0] ?? null;
    $remainingCount = max(0, count($physicalShort) - ($primaryLocation ? 1 : 0));
    $exclusiveOnline = $hasOnline && count($physicalShort) === 0;

    $nextLabel = $product->next_label ?? $product->next ?? null;
    $benefitText = $product->benefit ?? ($product->summary ?? null);
    $fomoText = trim((string) ($product->fomo_text ?? ''));

    $availabilityDays = [];
    try {
        $vendorUser = optional(optional($product->vendor)->user);
        $defaultAvailability = collect(data_get($vendorUser, 'defaultAvailability', data_get($vendorUser, 'default_availability', [])));
        foreach ($defaultAvailability as $row) {
            $dayValue = data_get($row, 'day_of_week');
            $available = data_get($row, 'is_available', true);
            if (!filter_var($available, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) && (string) $available !== '1') {
                continue;
            }
            $day = is_numeric($dayValue) ? (int) $dayValue : null;
            if ($day === null) {
                $dayName = strtolower(trim((string) $dayValue));
                $map = [
                    'mon' => 1, 'monday' => 1,
                    'tue' => 2, 'tues' => 2, 'tuesday' => 2,
                    'wed' => 3, 'wednesday' => 3,
                    'thu' => 4, 'thur' => 4, 'thurs' => 4, 'thursday' => 4,
                    'fri' => 5, 'friday' => 5,
                    'sat' => 6, 'saturday' => 6,
                    'sun' => 7, 'sunday' => 7,
                ];
                $day = $map[$dayName] ?? null;
            }
            if ($day !== null) {
                $availabilityDays[] = ($day === 7) ? 0 : $day;
            }
        }
        $availabilityDays = array_values(array_unique($availabilityDays));
        $dayOrder = [1, 2, 3, 4, 5, 6, 0];
        usort($availabilityDays, function ($left, $right) use ($dayOrder) {
            return array_search($left, $dayOrder, true) <=> array_search($right, $dayOrder, true);
        });
    } catch (\Throwable $e) {
        $availabilityDays = [];
    }

    $signalText = $fomoText !== ''
        ? $fomoText
        : ($exclusiveOnline
            ? 'Online Exclusive'
            : ($remainingCount > 0
                ? '+' . $remainingCount . ' more locations'
                : ($nextLabel ? 'Next: ' . $nextLabel : null)));

    $calendarLabel = $availabilityDays ? 'Availability calendar' : 'Request day/time';
    $calendarNote = $availabilityDays ? 'Live calendar' : 'Practitioner confirms';
    $availabilityClass = $availabilityDays ? 'has-availability' : 'needs-availability';
    $durationLabel = $product->duration ?? null;
    $isPastEvent = (bool) data_get($product, 'is_past_event', \App\Support\EventListing::isPast($product) && \App\Support\EventListing::isEventLike($product));
    $priceNote = $isPastEvent
        ? 'Archived event · view only'
        : ($durationLabel ?: ($compareMin ? 'Compare at available' : 'Fixed price'));
    $searchCardId = 'search-card-' . $product->id;
    $giftCardHaystack = $toLower(implode(' ', array_filter([
        $title,
        $benefitText,
        $slug,
        $categoryRaw,
        $typeRaw,
        $product->product_type ?? null,
        $fomoText,
    ])));
    $isGiftCard = (bool) preg_match('/gift\s*card|giftcard|voucher|e-?gift/i', $giftCardHaystack);
@endphp

@if((is_numeric($priceMin) ? (float) $priceMin : 0.0) > 0.0)
  @if($isGiftCard)
    @include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null])
  @else
  @once
    <style>
      .wow-search-card-scope{
        --ink:#101828;
        --muted:#667085;
        --line:#dde3ea;
        --soft:#edf0f2;
        --green:#549483;
        --green-dark:#4a8575;
        --green-soft:#e8f5f1;
        --green-border:rgba(84,148,131,.24);
        --gold-soft:#ffe5b3;
        --gold-text:#6f4b10;
        --blue-soft:#e8f0ff;
        --blue-text:#254a85;
        --rose-soft:#fff1f3;
        --rose-text:#b42318;
        --warm-soft:#fff7ed;
        --warm-text:#b54708;
        --radius:14px;
        --shadow:0 12px 34px rgba(16,24,40,.045);
        font-family:'Manrope', var(--bs-font-sans-serif) !important;
        position:relative;
        overflow:visible;
      }
      .wow-search-card-scope,
      .wow-search-card-scope *{
        font-family:'Manrope', var(--bs-font-sans-serif) !important;
      }
      .wow-search-card-scope .wow-card{
        display:block;
        color:inherit;
        text-decoration:none;
        position:relative;
        overflow:visible;
      }
      .wow-search-card-scope .wow-card.md{
        min-width:0;
        height:360px;
      }
      .wow-search-card-scope .wow-card-search{
        border-radius:var(--radius);
      }
      .wow-search-card-scope .wow-card-search{
        height:360px;
        width:100%;
        max-width:none;
        align-self:start;
        overflow:hidden;
      }
      .wow-search-card-scope .wow-card-search{
        cursor:pointer;
      }
      .wow-search-card-scope .wow-row-card{
        position:relative;
        display:grid;
        grid-template-columns:188px minmax(0,1fr) 190px;
        height:100%;
        min-height:0;
        overflow:hidden;
        border:1px solid var(--line);
        border-radius:var(--radius);
        background:rgba(255,255,255,.98);
        box-shadow:var(--shadow);
        transition:transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease, background 180ms ease;
      }
      .wow-search-card-scope .wow-card:hover .wow-row-card,
      .wow-search-card-scope .wow-row-card:hover{
        z-index:4;
        transform:translateY(-2px);
        border-color:rgba(84,148,131,.34);
        box-shadow:0 20px 48px rgba(16,24,40,.085);
        background:rgba(255,255,255,1);
      }
      .wow-search-card-scope .wow-row-media{
        grid-column:1;
        min-height:0;
        height:100%;
        padding:12px;
        background:#fff;
        border-radius:14px 0 0 14px;
        position:relative;
        overflow:visible;
      }
      .wow-search-card-scope .wow-row-media-inner{
        position:relative;
        width:100%;
        height:100%;
        min-height:0;
        overflow:hidden;
        border-radius:10px;
        background:linear-gradient(135deg, rgba(84,148,131,.10), rgba(232,240,255,.55)), #eef2f4;
      }
      .wow-search-card-scope .wow-row-media-inner::after{
        content:"";
        position:absolute;
        inset:0 0 auto;
        height:66px;
        background:linear-gradient(180deg, rgba(16,24,40,.32), rgba(16,24,40,0));
        pointer-events:none;
      }
      .wow-search-card-scope .wow-row-media img{
        width:100%;
        height:100%;
        display:block;
        object-fit:cover;
        transition:transform 240ms ease;
      }
      .wow-search-card-scope .wow-card:hover .wow-row-media img{
        transform:scale(1.035);
      }
      .wow-search-card-scope .wow-row-media-fomo{
        position:absolute;
        z-index:3;
        top:9px;
        left:9px;
        max-width:calc(100% - 18px);
      }
      .wow-search-card-scope .wow-row-mobile-overlay{ display:none; }
      .wow-search-card-scope .wow-row-body{
        grid-column:2;
        min-width:0;
        display:flex;
        flex-direction:column;
        min-height:0;
        overflow:hidden;
        padding:14px 16px 13px;
        border-left:1px solid var(--soft);
      }
      .wow-search-card-scope .wow-row-top{
        display:grid;
        grid-template-columns:minmax(0,1fr) auto;
        gap:12px;
        align-items:start;
        margin-bottom:10px;
      }
      .wow-search-card-scope .badges{
        display:flex;
        flex-wrap:wrap;
        gap:7px;
        min-width:0;
      }
      .wow-search-card-scope .badge{
        min-height:28px;
        display:inline-flex;
        align-items:center;
        gap:7px;
        border-radius:5px;
        padding:0 10px;
        font-size:12.5px;
        font-weight:600;
        line-height:1;
      }
      .wow-search-card-scope .badge--warm{ border:1px solid rgba(240,200,121,.9); background:var(--gold-soft); color:var(--gold-text); }
      .wow-search-card-scope .badge--cool{ border:1px solid rgba(199,216,251,.95); background:var(--blue-soft); color:var(--blue-text); }
      .wow-search-card-scope .premium-badge-holder{
        position:absolute;
        left:20px;
        bottom:20px;
        right:auto;
        top:auto;
        z-index:20;
        width:46px;
        height:46px;
        overflow:visible;
        justify-self:start;
      }
      .wow-search-card-scope .premium-badge-drawer{
        position:absolute;
        top:auto;
        left:0;
        right:auto;
        bottom:0;
        height:46px;
        width:46px;
        display:flex;
        align-items:center;
        justify-content:flex-start;
        gap:9px;
        overflow:hidden;
        border:1px solid rgba(255,255,255,.28);
        border-radius:999px;
        background:rgba(16,151,150,.72);
        box-shadow:
          0 12px 26px rgba(17,24,39,.16),
          inset 0 1px 0 rgba(255,255,255,.14);
        backdrop-filter:blur(14px) saturate(145%);
        -webkit-backdrop-filter:blur(14px) saturate(145%);
        transition:
          width 340ms cubic-bezier(.2,.8,.2,1),
          height 220ms ease,
          border-color 220ms ease,
          background 220ms ease,
          box-shadow 220ms ease,
          transform 220ms ease;
      }
      .wow-search-card-scope .premium-badge-holder:hover .premium-badge-drawer,
      .wow-search-card-scope .premium-badge-holder:focus-within .premium-badge-drawer{
        width:224px;
        height:52px;
        border-color:rgba(255,255,255,.42);
        background:rgba(16,151,150,.86);
        box-shadow:
          0 20px 48px rgba(17,24,39,.24),
          inset 0 1px 0 rgba(255,255,255,.18);
        transform:none;
      }
      .wow-search-card-scope .premium-badge-copy{
        order:1;
        width:154px;
        min-width:154px;
        padding-left:0;
        padding-right:14px;
        opacity:0;
        transform:translateX(18px);
        transition:
          opacity 220ms ease 90ms,
          transform 280ms cubic-bezier(.2,.8,.2,1) 70ms;
        text-align:left;
      }
      .wow-search-card-scope .premium-badge-holder:hover .premium-badge-copy,
      .wow-search-card-scope .premium-badge-holder:focus-within .premium-badge-copy{
        opacity:1;
        transform:translateX(0);
      }
      .wow-search-card-scope .premium-badge-title{
        margin:0;
        color:#fff;
        font-size:14px;
        font-weight:400;
        line-height:1.1;
        letter-spacing:0;
        white-space:nowrap;
        text-shadow:0 1px 8px rgba(0,0,0,.16);
      }
      .wow-search-card-scope .premium-badge-small{
        display:block;
        margin-top:3px;
        color:rgba(255,255,255,.88);
        font-size:11px;
        font-weight:400;
        text-transform:uppercase;
        line-height:1.15;
        white-space:nowrap;
        text-shadow:0 1px 8px rgba(0,0,0,.12);
      }
      .wow-search-card-scope .premium-badge-button{
        order:0;
        position:relative;
        z-index:2;
        flex:0 0 42px;
        width:42px;
        height:42px;
        right:auto;
        margin-right:0;
        margin-left:1px;
        padding:0;
        border:0;
        border-radius:999px;
        background:rgba(255,255,255,.96);
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        box-shadow:0 7px 18px rgba(17,24,39,.14);
        transition:
          transform 260ms cubic-bezier(.2,.8,.2,1),
          box-shadow 220ms ease,
          background 220ms ease;
      }
      .wow-search-card-scope .premium-badge-holder:hover .premium-badge-button,
      .wow-search-card-scope .premium-badge-holder:focus-within .premium-badge-button{
        transform:rotate(9deg);
        background:#fff;
        box-shadow:0 10px 24px rgba(17,24,39,.18);
      }
      .wow-search-card-scope .premium-badge-button:hover,
      .wow-search-card-scope .premium-badge-button:focus-visible{
        outline:none;
      }
      .wow-search-card-scope .premium-badge-button img{
        width:32px;
        height:32px;
        display:block;
        object-fit:contain;
        filter:drop-shadow(0 3px 5px rgba(103,70,14,.24));
      }
      .wow-search-card-scope .premium-badge-sheen{
        position:absolute;
        top:-45%;
        left:-80%;
        width:70px;
        height:140px;
        background:linear-gradient(90deg, transparent, rgba(255,255,255,.36), transparent);
        transform:rotate(24deg);
        opacity:0;
        pointer-events:none;
      }
      .wow-search-card-scope .premium-badge-holder:hover .premium-badge-sheen,
      .wow-search-card-scope .premium-badge-holder:focus-within .premium-badge-sheen{
        animation:premiumSheen 900ms ease forwards;
      }
      @keyframes premiumSheen{
        0%{ left:-80%; opacity:0; }
        30%{ opacity:1; }
        100%{ left:112%; opacity:0; }
      }
      .wow-search-card-scope .wow-row-title{
        display:-webkit-box;
        max-width:680px;
        min-height:57px;
        margin:0;
        overflow:hidden;
        color:var(--ink);
        font-size:clamp(21px, 1.65vw, 26px);
        font-weight:500;
        line-height:1.04;
        letter-spacing:-0.055em;
        -webkit-box-orient:vertical;
        -webkit-line-clamp:2;
      }
      .wow-search-card-scope .wow-card:hover .wow-row-title{ color:var(--green); }
      .wow-search-card-scope .wow-row-provider{
        display:-webkit-box;
        margin:7px 0 0;
        overflow:hidden;
        color:#667085;
        font-size:13.5px;
        line-height:1.35;
        -webkit-box-orient:vertical;
        -webkit-line-clamp:1;
      }
      .wow-search-card-scope .wow-row-rating{
        display:flex;
        align-items:center;
        gap:8px;
        margin-top:10px;
        color:#101828;
        font-size:12.75px;
      }
      .wow-search-card-scope .wow-stars{ letter-spacing:1px; white-space:nowrap; }
      .wow-search-card-scope .wow-review-count{ color:#667085; white-space:nowrap; }
      .wow-search-card-scope .wow-row-description{
        display:-webkit-box;
        max-width:680px;
        min-height:0;
        max-height:2.95em;
        margin:8px 0 0;
        overflow:hidden;
        color:#344054;
        font-size:13.25px;
        line-height:1.35;
        -webkit-box-orient:vertical;
        -webkit-line-clamp:2;
      }
      .wow-search-card-scope .wow-meta{
        display:flex;
        flex-wrap:wrap;
        gap:7px;
        margin-top:10px;
        overflow:visible;
        max-height:32px;
      }
      .wow-search-card-scope .wow-meta .item{
        position:relative;
        min-height:28px;
        display:inline-flex;
        align-items:center;
        gap:6px;
        border:1px solid #e3e8ee;
        border-radius:999px;
        background:#fff;
        color:#596275;
        padding:0 10px;
        font-size:12.75px;
        line-height:1;
        white-space:nowrap;
      }
      .wow-search-card-scope .wow-chip-icon{
        width:13px;
        height:13px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:#667085;
      }
      .wow-search-card-scope .wow-chip-icon svg{
        width:13px;
        height:13px;
        display:block;
        fill:currentColor;
      }
      .wow-search-card-scope .wow-location-label{
        display:inline-flex;
        align-items:baseline;
        gap:0;
        white-space:nowrap;
      }
      .wow-search-card-scope .wow-location-count{
        position:relative;
        top:-0.26em;
        margin-left:2px;
        font-size:.72em;
        font-weight:800;
        letter-spacing:-0.02em;
        line-height:1;
      }
      .wow-search-card-scope .wow-location-item{
        cursor:default;
        overflow:visible;
      }
      .wow-search-card-scope .wow-location-dropdown{
        position:absolute;
        left:0;
        top:calc(100% + 8px);
        z-index:99999;
        width:216px;
        padding:8px;
        border:1px solid var(--line);
        border-radius:12px;
        background:#fff;
        box-shadow:0 22px 52px rgba(16,24,40,.18);
        opacity:0;
        visibility:hidden;
        transform:translateY(-4px);
        transition:opacity 140ms ease, visibility 140ms ease, transform 140ms ease;
      }
      .wow-search-card-scope .wow-location-item:hover .wow-location-dropdown,
      .wow-search-card-scope .wow-location-item:focus-within .wow-location-dropdown,
      .wow-search-card-scope .wow-location-item.is-open .wow-location-dropdown{
        opacity:1;
        visibility:visible;
        transform:translateY(0);
      }
      .wow-search-card-scope .wow-location-dropdown::before{
        content:"";
        position:absolute;
        left:18px;
        top:-6px;
        width:10px;
        height:10px;
        background:#fff;
        border-left:1px solid var(--line);
        border-top:1px solid var(--line);
        transform:rotate(45deg);
      }
      .wow-search-card-scope .wow-location-dropdown strong{
        display:block;
        margin:0 0 7px;
        color:var(--ink);
        font-size:12px;
        line-height:1.2;
      }
      .wow-search-card-scope .wow-location-dropdown span{
        display:flex;
        align-items:center;
        gap:7px;
        min-height:28px;
        padding:0 6px;
        border-radius:7px;
        color:#596275;
        font-size:12.5px;
        line-height:1;
      }
      .wow-search-card-scope .wow-location-dropdown span:hover{ background:#f8fafc; }
      .wow-search-card-scope .wow-row-availability{
        margin-top:11px;
        min-height:82px;
        max-height:94px;
        display:grid;
        grid-template-columns:1fr;
        gap:6px;
        align-items:start;
        border:1px solid #e3e8ee;
        border-radius:12px;
        background:#fff;
        padding:8px 10px 9px;
      }
      .wow-search-card-scope .wow-row-availability.has-availability{
        border-color:rgba(84,148,131,.24);
        background:linear-gradient(180deg, rgba(232,245,241,.62), rgba(255,255,255,.94)), #fff;
      }
      .wow-search-card-scope .wow-row-availability.needs-availability{
        border-style:dashed;
        background:linear-gradient(180deg, rgba(255,247,237,.52), rgba(255,255,255,.96)), #fff;
      }
      .wow-search-card-scope .wow-row-availability-copy{
        min-width:0;
        display:grid;
        align-content:start;
        gap:3px;
      }
      .wow-search-card-scope .wow-row-availability-title{
        min-width:0;
        display:flex;
        align-items:center;
        gap:6px;
        color:#344054;
        font-size:12px;
        font-weight:800;
        line-height:1.15;
      }
      .wow-search-card-scope .wow-row-availability.has-availability .wow-row-availability-title{ color:#2f6f60; }
      .wow-search-card-scope .wow-row-availability.needs-availability .wow-row-availability-title{ color:var(--warm-text); }
      .wow-search-card-scope .wow-row-availability-title span:last-child{
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
      }
      .wow-search-card-scope .wow-row-availability-note{
        padding-left:19px;
        color:#667085;
        font-size:11.25px;
        line-height:1.2;
        white-space:normal;
        overflow:visible;
        text-overflow:clip;
      }
      .wow-search-card-scope .wow-day-strip{
        display:grid;
        grid-template-columns:repeat(7, minmax(0,1fr));
        gap:4px;
        width:calc(100% - 19px);
        max-width:390px;
        margin-left:19px;
        align-self:start;
      }
      .wow-search-card-scope .wow-day{
        min-width:0;
        height:24px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid #edf0f2;
        border-radius:7px;
        background:#f8fafc;
        color:#98a2b3;
        font-size:10.8px;
        font-weight:800;
        letter-spacing:-.02em;
      }
      .wow-search-card-scope .wow-day.is-active{
        border-color:rgba(84,148,131,.28);
        background:var(--green-soft);
        color:#2f6f60;
      }
      .wow-search-card-scope .wow-day.is-request{
        border-style:dashed;
        background:#fff;
        color:#98a2b3;
      }
      .wow-search-card-scope .wow-row-request{
        height:26px;
        display:flex;
        align-items:center;
        justify-content:center;
        align-self:start;
        width:calc(100% - 19px);
        max-width:390px;
        margin-left:19px;
        border:1px dashed rgba(181,71,8,.24);
        border-radius:8px;
        background:#fff;
        color:var(--warm-text);
        font-size:11.2px;
        font-weight:800;
        letter-spacing:-.01em;
      }
      .wow-search-card-scope .wow-row-bottom{
        grid-column:3;
        display:grid;
        grid-template-rows:auto 1fr auto;
        gap:0;
        min-height:0;
        overflow:hidden;
        padding:15px 14px;
        border-left:1px solid var(--soft);
        background:#fff;
        border-radius:0 14px 14px 0;
      }
      .wow-search-card-scope .wow-price{
        margin:0;
      }
      .wow-search-card-scope .wow-price .from{
        display:block;
        color:#667085;
        font-size:12.5px;
        line-height:1.1;
      }
      .wow-search-card-scope .wow-price .now{
        display:block;
        margin-top:5px;
        color:var(--ink);
        font-size:28px;
        font-weight:700;
        line-height:1;
        letter-spacing:-.055em;
      }
      .wow-search-card-scope .wow-price-sub{
        margin:7px 0 0;
        color:#667085;
        font-size:12px;
        line-height:1.35;
      }
      .wow-search-card-scope .wow-price-note{
        display:flex;
        align-items:center;
        justify-content:flex-start;
        min-height:28px;
        margin-top:6px;
        color:#667085;
        font-size:11.75px;
        line-height:1.25;
      }
      .wow-search-card-scope .wow-actions{
        display:grid;
        gap:9px;
        align-self:end;
      }
      .wow-search-card-scope .btn{
        height:40px;
        border-radius:4px;
        font-size:15px;
        font-weight:400;
        border:1px solid rgba(16,24,40,.22);
        background:#fff !important;
        color:rgba(11,18,32,.82);
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        box-shadow:0 10px 22px rgba(16,24,40,.08);
        margin:0 !important;
        padding:0 14px;
        line-height:1;
        width:100%;
        transition:transform 150ms ease, border-color 150ms ease, color 150ms ease, background 150ms ease, box-shadow 150ms ease;
      }
      .wow-search-card-scope .btn:hover,
      .wow-search-card-scope .btn:focus{
        transform:translateY(-1px);
        border-color:rgba(84,148,131,.42);
        color:#549483;
      }
      .wow-search-card-scope .btn--primary,
      .wow-search-card-scope .btn-primary-list{
        border-color:rgba(0,0,0,.10);
        color:#fff;
        background:#549483 !important;
      }
      .wow-search-card-scope .btn--primary:hover,
      .wow-search-card-scope .btn--primary:focus,
      .wow-search-card-scope .btn-primary-list:hover,
      .wow-search-card-scope .btn-primary-list:focus{
        background:#4a8575 !important;
        color:#fff;
        border-color:rgba(0,0,0,.10);
      }
      .wow-search-card-scope .wow-cta-note{
        display:flex;
        align-items:center;
        justify-content:center;
        min-height:28px;
        color:#667085;
        font-size:11.75px;
        line-height:1.25;
        text-align:center;
      }
      .wow-search-card-scope .wow-fomo{
        max-width:100%;
        min-height:28px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border-radius:999px;
        padding:0 10px;
        border:1px solid transparent;
        font-size:11.5px;
        font-weight:700;
        letter-spacing:-.01em;
        line-height:1;
        margin:0;
        white-space:nowrap;
        backdrop-filter:blur(10px);
        box-shadow:0 10px 22px rgba(16,24,40,.10);
      }
      .wow-search-card-scope .wow-fomo::before{
        content:"";
        width:7px;
        height:7px;
        flex:0 0 7px;
        border-radius:999px;
        background:currentColor;
        opacity:.9;
      }
      .wow-search-card-scope .wow-fomo--green{ background:rgba(232,245,241,.94); color:#2f6f60; border-color:rgba(84,148,131,.20); }
      .wow-search-card-scope .wow-fomo--warm{ background:rgba(255,247,237,.94); color:var(--warm-text); border-color:rgba(181,71,8,.14); }
      .wow-search-card-scope .wow-fomo--rose{ background:rgba(255,241,243,.96); color:var(--rose-text); border-color:rgba(180,35,24,.12); }

      @media (max-width: 1220px) {
        .wow-search-card-scope .wow-row-card{
          grid-template-columns:190px minmax(0,1fr) 196px;
        }
      }
      @media (max-width: 900px) {
        .wow-search-card-scope .wow-row-card{
          grid-template-columns:164px minmax(0,1fr);
          min-height:0;
          height:100%;
        }
        .wow-search-card-scope .wow-row-media{
          min-height:210px;
        }
        .wow-search-card-scope .wow-row-body{
          border-left:1px solid var(--soft);
        }
        .wow-search-card-scope .wow-row-bottom{
          grid-column:1 / -1;
          grid-template-columns:minmax(0,1fr) minmax(260px,auto);
          grid-template-rows:auto;
          gap:16px;
          align-items:end;
          border-left:0;
          border-top:1px solid var(--soft);
          border-radius:0 0 14px 14px;
        }
        .wow-search-card-scope .wow-actions{ width:260px; }
        .wow-search-card-scope .wow-row-availability{
          grid-template-columns:1fr;
        }
        .wow-search-card-scope .wow-location-dropdown{
          left:auto;
          right:0;
        }
        .wow-search-card-scope .wow-location-dropdown::before{
          left:auto;
          right:18px;
        }
      }
      @media (max-width: 640px) {
        .wow-search-card-scope .wow-card.md{
          height:auto;
          min-height:0;
        }
        .wow-search-card-scope .wow-card-search{
          height:auto;
          min-height:0;
        }
        .wow-search-card-scope .wow-row-card{
          display:flex;
          flex-direction:column;
          min-height:0;
          height:auto;
          overflow:hidden;
          border-radius:14px;
        }
        .wow-search-card-scope .wow-row-media{
          width:100%;
          min-height:0;
          padding:0;
          border-radius:14px 14px 0 0;
        }
        .wow-search-card-scope .wow-row-media-inner{
          min-height:0;
          aspect-ratio:1.55 / 1;
          border-radius:14px 14px 0 0;
        }
        .wow-search-card-scope .wow-row-body{
          border-left:0;
          padding:14px 15px 10px;
        }
        .wow-search-card-scope .wow-row-title{
          min-height:auto;
          font-size:21px;
          line-height:1.08;
          letter-spacing:-.045em;
        }
        .wow-search-card-scope .wow-row-description{
          min-height:0;
          max-height:4.2em;
          margin-top:10px;
          font-size:13px;
          line-height:1.45;
        }
        .wow-search-card-scope .wow-row-availability{
          grid-template-columns:1fr;
          margin-top:11px;
          max-height:none;
        }
        .wow-search-card-scope .wow-row-bottom{
          display:grid;
          grid-template-columns:1fr;
          gap:10px;
          padding:12px 15px 14px;
          border-left:0;
          border-top:1px solid var(--soft);
          border-radius:0 0 14px 14px;
        }
        .wow-search-card-scope .wow-price .now{
          font-size:24px;
        }
        .wow-search-card-scope .wow-price-sub,
        .wow-search-card-scope .wow-price-note{
          display:none;
        }
        .wow-search-card-scope .wow-actions{
          width:100%;
          grid-template-columns:1fr 1fr;
          gap:7px;
          align-self:start;
        }
        .wow-search-card-scope .btn{
          height:38px;
          min-width:0;
          font-size:13.5px;
        }
        .wow-search-card-scope .wow-cta-note{ display:none; }
      }
      @media (max-width: 390px) {
        .wow-search-card-scope .wow-actions{
          grid-template-columns:1fr;
        }
        .wow-search-card-scope .badges{
          gap:5px;
        }
        .wow-search-card-scope .badge{
          font-size:12px;
          padding:0 9px;
        }
      }
    </style>
  @endonce

  <div class="wow-search-card-scope">
    @if($hasDisplayableImage)
    <article
      class="wow-card md is-fluid wow-card-search"
      aria-label="Open offering {{ $titleFormatted }}"
      role="link"
      tabindex="0"
      data-id="{{ $product->id }}"
      data-product-id="{{ $product->id }}"
      data-source-version="legacy"
      data-url="{{ $url }}"
      data-wow-analytics-item="{{ e(json_encode([
        'id' => $product->id,
        'title' => $titleFormatted,
        'price' => is_numeric($priceMin) ? (float) $priceMin : 0,
        'currency' => 'GBP',
        'source_version' => 'legacy',
        'catalogue_type' => strtolower($typeLabel),
        'modality' => $categoryLabel ?? $typeLabel,
        'provider_id' => $product->vendor_id ?? null,
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) }}"
    >
      <div class="wow-row-card">
        <div class="wow-row-media">
          <div class="wow-row-media-inner">
            <img src="{{ $image }}" alt="{{ $titleFormatted }}" loading="lazy">

            @if($signalText)
              <div class="wow-row-media-fomo">
                <span class="wow-fomo {{ str_contains(strtolower($signalText), 'drop') ? 'wow-fomo--rose' : (str_contains(strtolower($signalText), 'online') ? 'wow-fomo--green' : 'wow-fomo--warm') }}">{{ $signalText }}</span>
              </div>
            @endif
          </div>

          @if($businessAcceleratorPlan)
            <div class="premium-badge-holder">
              <div class="premium-badge-drawer">
                <div class="premium-badge-sheen"></div>

                <div class="premium-badge-copy">
                  <p class="premium-badge-title">Premium Partner</p>
                  <span class="premium-badge-small">Business Accelerator</span>
                </div>

                <button class="premium-badge-button" type="button" aria-label="Business Accelerator Premium Partner" title="Premium Partner" onclick="event.preventDefault(); event.stopPropagation();">
                  <img src="https://studio.weofferwellness.co.uk/storage/uploads/images/78aa908f-334b-45c0-9220-1c4d84053c5e.png" alt="Premium Partner rosette">
                </button>
              </div>
            </div>
          @endif
        </div>

        <div class="wow-row-body">
          <div class="wow-row-top">
            <div class="badges" aria-label="Badges">
              <span class="badge badge--warm">{{ $categoryBadgeLabel }}</span>
              <span class="badge badge--cool">{{ $typeLabel }}</span>
            </div>
          </div>

          <h2 class="wow-row-title">{{ $titleFormatted }}</h2>
          @if($providerFormatted)
            <p class="wow-row-provider">with {{ $providerFormatted }}</p>
          @endif

          <div class="wow-row-rating" aria-label="{{ $reviewCount > 0 ? 'Rating ' . number_format((float) $rating, 1) . ' · ' . $reviewCount . ' reviews' : 'Be the first to review' }}">
            <span class="wow-stars" aria-hidden="true">★★★★★</span>
            <span class="wow-review-count">{{ $reviewSummary }}</span>
          </div>

          @if($benefitText !== '')
            <p class="wow-row-description">{{ \Illuminate\Support\Str::limit($benefitText, 150) }}</p>
          @endif

          <div class="wow-meta">
            @if($exclusiveOnline)
              <span class="item">Online Exclusive</span>
            @elseif($hasOnline && $primaryLocation)
              <span class="item">Online + <span class="wow-location-label">{{ $primaryLocation }}</span></span>
            @elseif($primaryLocation)
              <span class="item">{{ $primaryLocation }}</span>
            @endif

            @if(count($physicalShort) > 1)
              <span class="item wow-location-item js-location-chip" role="button" tabindex="0" aria-label="Show available locations">
                <span class="wow-chip-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <path d="M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M17.8 13.938h-.011a7 7 0 1 0-11.464.144h-.016l.14.171c.1.127.2.251.3.371L12 21l5.13-6.248c.194-.209.374-.429.54-.659l.13-.155Z"/>
                  </svg>
                </span>
                <span class="label">Locations<span class="wow-location-label"><span class="wow-location-count">({{ count($physicalShort) }})</span></span></span>
                <span class="wow-location-dropdown" role="tooltip">
                  <strong>Available locations</strong>
                  @foreach($physicalShort as $loc)
                    <span>
                      <span class="wow-chip-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                          <path d="M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M17.8 13.938h-.011a7 7 0 1 0-11.464.144h-.016l.14.171c.1.127.2.251.3.371L12 21l5.13-6.248c.194-.209.374-.429.54-.659l.13-.155Z"/>
                        </svg>
                      </span>
                      {{ $loc }}
                    </span>
                  @endforeach
                </span>
              </span>
            @endif
          </div>

          <div class="wow-row-availability {{ $availabilityClass }}">
            <div class="wow-row-availability-copy">
              <div class="wow-row-availability-title">
                <span class="wow-chip-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 22 6.5v12A2.5 2.5 0 0 1 19.5 21h-15A2.5 2.5 0 0 1 2 18.5v-12A2.5 2.5 0 0 1 4.5 4H6V3a1 1 0 0 1 1-1Zm12.5 8h-15v8.5a.5.5 0 0 0 .5.5h14a.5.5 0 0 0 .5-.5V10ZM5 6a.5.5 0 0 0-.5.5V8h15V6.5A.5.5 0 0 0 19 6H5Z"></path>
                  </svg>
                </span>
                <span>{{ $calendarLabel }}</span>
              </div>
              <div class="wow-row-availability-note">{{ $calendarNote }}</div>
            </div>

            @if(count($availabilityDays))
              <div class="wow-day-strip" aria-label="Availability calendar">
                @foreach([1,2,3,4,5,6,0] as $day)
                  <span class="wow-day {{ in_array($day, $availabilityDays, true) ? 'is-active' : 'is-request' }}" title="{{ ['M','T','W','T','F','S','S'][$day] }}">{{ ['M','T','W','T','F','S','S'][$day] }}</span>
                @endforeach
              </div>
            @endif
          </div>
        </div>

        <div class="wow-row-bottom">
          <div class="wow-price-block">
            <p class="wow-price">
              <span class="from">From</span>
              <span class="now">{{ $priceDisplay }}</span>
            </p>
            <p class="wow-price-sub">{{ $priceNote }}</p>
            <span class="wow-price-note">{{ $isPastEvent ? 'Archived event · view only' : 'Secure checkout · instant confirmation' }}</span>
          </div>

          <div class="wow-actions">
            <button type="button" class="btn btn-details-list js-view-details" data-no-cart="1" onclick="window.location.href=@json($url); return false;" data-id="{{ $product->id }}" data-product-id="{{ $product->id }}" data-title="{{ $titleFormatted }}" data-url="{{ $url }}">Details</button>
            @unless($isPastEvent)
              <button type="button" class="btn btn--primary btn-primary-list js-buy-now" data-no-cart="1" onclick="window.location.href=@json($url); return false;" data-id="{{ $product->id }}" data-product-id="{{ $product->id }}" data-qty="1" data-title="{{ $titleFormatted }}" data-price="{{ number_format((float) $priceMin, 2, '.', '') }}" data-image="{{ $image }}" data-url="{{ $url }}">Book</button>
            @endunless
          </div>
        </div>
      </div>
    </article>
    @endif
  </div>

  @once
    <script>
      (function(){
        function navigateCard(card){
          const url = card && card.getAttribute('data-url');
          if (!url) return;
          try {
            window.location.assign(url);
          } catch(_){
            window.location.href = url;
          }
        }

        document.addEventListener('click', function(event){
          const chip = event.target.closest('.wow-search-card-scope .js-location-chip');
          if (chip){
            event.preventDefault();
            event.stopPropagation();

            document.querySelectorAll('.wow-search-card-scope .js-location-chip.is-open').forEach(function(openChip){
              if (openChip !== chip) openChip.classList.remove('is-open');
            });

            chip.classList.toggle('is-open');
            return;
          }

          const card = event.target.closest('.wow-search-card-scope .wow-card-search');
          if (card) {
            if (event.target.closest('button, a, input, textarea, select, label, summary, [role="button"]')) {
              return;
            }
            navigateCard(card);
            return;
          }

          document.querySelectorAll('.wow-search-card-scope .js-location-chip.is-open').forEach(function(openChip){
            openChip.classList.remove('is-open');
          });
        });

        document.addEventListener('keydown', function(event){
          if (event.key !== 'Escape') return;
          document.querySelectorAll('.wow-search-card-scope .js-location-chip.is-open').forEach(function(openChip){
            openChip.classList.remove('is-open');
          });
        });

        document.addEventListener('keydown', function(event){
          if (event.key !== 'Enter' && event.key !== ' ') return;
          const target = event.target instanceof Element ? event.target : null;
          if (!target) return;
          const card = target.closest('.wow-search-card-scope .wow-card-search');
          if (!card) return;
          if (target.closest('button, a, input, textarea, select, label, summary, [role="button"], .js-location-chip')) {
            return;
          }
          event.preventDefault();
          navigateCard(card);
        }, true);
      })();
    </script>
  @endonce
  @endif
@endif
