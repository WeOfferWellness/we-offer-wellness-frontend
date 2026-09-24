@php
    $hasVendorReviews = (($product['review_count'] ?? 0) > 0) && ($product['rating'] ?? null) !== null;
    $practitioner = $product['practitioner'] ?? null;
    $planKey = strtolower(trim((string) ($practitioner['plan_key'] ?? $practitioner['plan_label'] ?? '')));
    $isPaidPlan = ! (($practitioner['is_paid_plan'] ?? false) === false || in_array($planKey, ['', 'starter'], true));
    $practitionerFirstName = trim((string) ($practitioner['first_name'] ?? ''));
    if ($practitionerFirstName === '' && ! empty($practitioner['name'])) {
        $practitionerFirstName = trim(explode(' ', (string) $practitioner['name'])[0] ?? '');
    }
    $bookingModalTitle = $isPaidPlan ? ($product['title'] ?? 'Experience') : 'Discovery Call — We Offer Wellness®';
    $bookingModalSubtitle = $isPaidPlan ? ('with ' . ($practitionerFirstName !== '' ? $practitionerFirstName : 'the practitioner')) : null;
    $bookingModalPhoto = $isPaidPlan ? ($practitioner['photo'] ?? null) : null;
    $initialVariant = $product['variants'][0] ?? null;
    $baseProductId = $product['id']
        ?? ($initialVariant['product_id'] ?? null)
        ?? ($initialVariant['id'] ?? null);
    $initialPrice = $product['variants_min_price'] ?? ($product['price'] ?? 0);
    if (is_numeric($initialPrice) && $initialPrice > 1000) { $initialPrice = $initialPrice / 100; }
    $initialPriceFormatted = is_numeric($initialPrice) ? number_format((float)$initialPrice, 2, '.', '') : '0.00';
    $primaryImage = $product['images'][0] ?? ($product['image'] ?? '');
    $productTitleSafe = $product['title'] ?? 'Experience';
    $productUrlCurrent = url()->current();
    $bookingFlow = strtolower(trim((string) ($product['booking_flow'] ?? 'flexible')));
    $bookingSourceVersion = strtolower(trim((string) ($product['source_version'] ?? 'legacy')));
    $isEventOffering = !empty($isEventOffering)
        || in_array(strtolower((string) ($type ?? '')), ['event', 'events'], true)
        || str_contains(strtolower((string) ($product['type'] ?? '')), 'event')
        || !empty($product['start_date'])
        || !empty($product['end_date']);
    $primaryActionLabel = $isEventOffering ? 'Book tickets' : 'Add to cart';
    $secondaryActionLabel = $isEventOffering ? 'Checkout' : 'Book now';
    $mobileActionLabel = $isEventOffering ? 'Book tickets' : 'Book now';
    $bookingHeaderLabel = $isEventOffering ? 'Book tickets' : 'Select a Date & Time';
@endphp

<style>
    .buybox{
        position:-webkit-sticky;
        position:sticky;
        top:calc(var(--wow-header-offset, 0px) + 24px);
        align-self:flex-start;
        z-index:20;
        width:100%;
        max-width:365.33px;
        margin-left:auto;
        margin-right:0;
        box-sizing:border-box;
    }
    .buybox .card {
        background: #fff;
        backdrop-filter: saturate(1.2) blur(12px);
        -webkit-backdrop-filter: saturate(1.2) blur(12px);
        border: 1px solid #ddd;
        border-radius: 11px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
        padding: 30px 25px 20px !important;
        width:100%;
        max-width:365.33px;
    }
    .chips{display:flex;gap:.5rem;flex-wrap:nowrap;width:100%}
    .chip{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border:1px solid rgba(0,0,0,.08);background:#fff;border-radius:16px;padding:.6rem .9rem;font-weight:600;flex:1 1 0}
    .chip.active{box-shadow:0 6px 14px rgba(16,185,129,.25);transform:translateY(-1px)}
    .price{font-size:2rem;font-weight:800;color:#0f172a}
    .compare{color:#9ca3af;text-decoration:line-through;font-size:1rem;margin-left:.5rem}
    .pills{display:flex;flex-wrap:wrap;gap:.5rem}
    .pill{border:1px solid #549483;background:#fff;border-radius:12px;padding:.6rem .9rem;font-weight:600}
    .pill[aria-checked="true"]{border-color:#549483;box-shadow:0 6px 16px rgba(0,0,0,.15)}
    .stepper{display:inline-grid;grid-template-columns:44px 64px 44px;align-items:center;border:1px solid rgba(0,0,0,.12);border-radius:14px;background:#fff}
    .stepper button{border:0;background:transparent;height:46px;font-size:20px}
    .stepper input{border:0;background:transparent;height:46px;text-align:center;font-weight:700}
    .meta{display:flex;gap:1rem;flex-wrap:wrap;color:#374151}
    .meta .item{display:flex;align-items:center;gap:.5rem;font-size:.9rem}
    .trust{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}
    .trust .cell{display:flex;align-items:center;gap:.6rem;border:1px solid rgba(0,0,0,.06);background:rgba(255,255,255,.8);border-radius:12px;padding:.75rem}
    .mode-note{font-size:.85rem;color:#6b7280}
    .rating{display:flex;align-items:center;gap:.4rem}
    /* Stars styling (independent of external icon sets) */
    .stars{display:inline-flex;align-items:center;gap:3px;transform:translateY(1px)}
    .star{width:18px;height:18px;display:inline-block;position:relative;background:currentColor;
      -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z'/%3E%3C/svg%3E") center/contain no-repeat;
              mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z'/%3E%3C/svg%3E") center/contain no-repeat}
    .star::after{content:"";position:absolute;inset:0;background:#333;pointer-events:none;
      -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='none' stroke='%23000' stroke-width='2' stroke-linejoin='round' stroke-linecap='round' d='M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z'/%3E%3C/svg%3E") center/contain no-repeat;
              mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='none' stroke='%23000' stroke-width='2' stroke-linejoin='round' stroke-linecap='round' d='M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z'/%3E%3C/svg%3E") center/contain no-repeat}
    .star--empty{color:transparent}
    .btn-main{background:#549483;color:#fff;border:none}
    .btn-basket{background:#f1f3f5;color:#111827;border:1px solid #d0d5dd}
    .group-range{display:none}
    .booking-wrap{border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fafafa}
    /* V3 booking UX: availability is chosen from Book now, not a separate Confirm later/Pick now block. */
    .buybox .booking-wrap{display:none!important}
    #configModal .booking-wrap{display:none!important}
    .availability-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px;align-items:start}
    .availability-action-stack{display:flex;flex-direction:column;gap:10px;width:100%;align-items:stretch}
    .availability-action-stack .btn,
    .availability-action-stack .preferred-time-box{width:100%}
    .badge-note{font-size:.8rem;background:#eef7f2;color:#185a44;border:1px solid #c7e5d9}
    .sel-pill{display:inline-flex;gap:.5rem;align-items:center;border:1px solid #e5e7eb;background:#fff;border-radius:999px;padding:.25rem .6rem}
    .sel-pill .edit{cursor:pointer}
    .booking-title-row{display:flex;align-items:center;gap:12px;margin-bottom:16px}
    .booking-title-left{display:flex;align-items:center;gap:8px;min-width:0}
    .booking-title-left i{font-size:1rem;line-height:1;color:inherit;flex:0 0 auto}
    .booking-title-left .fw-semibold{font-size:1rem;line-height:1.2;font-weight:600;color:inherit;white-space:nowrap}
    .booking-title-row .badge-note{margin-left:auto;flex:0 0 auto;white-space:nowrap}
    .preferred-time-box{margin-top:0;box-sizing:border-box;margin-bottom:20px}
    .preferred-time-box label{display:block;margin:0 0 6px 0}
    .preferred-time-box textarea{width:100%;box-sizing:border-box}
    .cal-head{display:flex;align-items:center;justify-content:space-between}
    .cal-month{font-weight:700}
    .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:.5rem}
    .cal-dayname{text-transform:uppercase;font-size:.75rem;color:#6b7280}
    .cal-cell{position:relative;border:1px solid #e5e7eb;border-radius:14px;height:42px;display:flex;align-items:center;justify-content:center;background:#fff;cursor:pointer}
    .cal-cell[aria-disabled="true"]{opacity:.4;cursor:not-allowed;background:#f8f9fa}
    .cal-cell.active{outline:2px solid #549483;box-shadow:0 6px 16px rgba(0,0,0,.12)}
    .slot{border:1px solid #e5e7eb;border-radius:10px;padding:.5rem .7rem;background:#fff;cursor:pointer}
    .slot.active{border-color:#549483;outline:2px solid #549483}
    .slot.reserved{border-color:#f59e0b;background:#fffbe6;cursor:not-allowed;opacity:.9}
    .tz-pill{border:1px solid #e5e7eb;border-radius:12px;padding:.35rem .6rem;background:#fff}
    .calendar-side{border-left:1px solid #f0f2f4}
    /* Sessions dropdown */
    .sessions-dd{margin:10px 0}
    .sd-label{font-size:.9rem;color:#6b7280;margin-bottom:6px}
    .sd-trigger{display:flex;align-items:center;justify-content:space-between;width:100%;height:46px;border:1px solid #549483;background:#fff;border-radius:12px;border-color:#549483;box-shadow:0 6px 16px rgba(0,0,0,.15);padding:.5rem .75rem;cursor:pointer}
    .sd-trigger:focus{outline:2px solid #549483}
    .sd-trigger .sd-val{display:flex;align-items:center;gap:.4rem;font-weight:600;color:#0f172a}
    .sd-badge{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;padding:.15rem .5rem;font-size:.75rem;background:#e8f4f1;color:#2b675b;border:1px solid #c7e5d9}
    .sd-pop{position:relative}
    .sd-popover{position:absolute;z-index:1050;left:0;right:0;top:calc(100% + 8px);background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 18px 40px rgba(2,8,23,.08);max-height:320px;overflow:auto;padding:6px}
    .sd-option{display:grid;grid-template-columns:1fr auto;align-items:center;gap:8px;border-radius:10px;padding:10px 12px;cursor:pointer}
    .sd-option:hover{background:#f5f7f9}
    .sd-option .sd-left{display:flex;flex-direction:column}
    .sd-option .sd-title{font-weight:700;color:#0f172a}
    .sd-option .sd-sub{font-size:.85rem;color:#667085}
    .sd-right{display:flex;align-items:center;gap:6px}
    .sd-check{width:22px;height:22px;border-radius:999px;background:#e8f4f1;color:#2b675b;display:none;align-items:center;justify-content:center}
    .sd-check svg{width:14px;height:14px}
    .sd-option.active{background:#2b675b}
    .sd-option.active .sd-title,.sd-option.active .sd-sub{color:#fff}
    .sd-option.active .sd-badge{background:#fff;color:#2b675b;border-color:#fff}
    .sd-option.active .sd-check{display:inline-flex;background:#fff;color:#2b675b}
    .sd-footer{position:sticky;bottom:0;background:#2b675b;border-radius:10px;padding:8px 12px;margin-top:6px;display:flex;align-items:center;justify-content:space-between;color:#fff}
    @media (max-width: 991px){
        .buybox{display:none !important}
        #mobileBar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;position:fixed;left:0;right:0;bottom:0;z-index:1030;background:#fff;border-top:1px solid #e5e7eb;padding:.6rem .9rem;box-shadow:0 -6px 14px rgba(0,0,0,.06)}
        #mobileBar .m-left{display:flex;flex-direction:column}
        #mobileBar .price{font-size:1.35rem;margin:0}
        #mobileBar .rating{gap:.35rem;margin-top:.1rem}
        #mobileBar .rating .text-secondary{font-size:.8rem}
        #mobileBar .btn-main{padding:.6rem 1rem}
        #mobileBar .btn-basket{padding:.6rem 1rem}
        .modal-content.mobile-times .calendar-side{display:block}
        .modal-content.mobile-times .left-col{display:none !important}
        #slotList .slot{display:block;width:100%;text-align:left}
    }
    @media (min-width: 1200px){.modal-xl{--bs-modal-width: 882px;}}
    .hourglass-spin{display:inline-block;transform-origin:50% 50%;animation:hourglassFlip 1.6s cubic-bezier(.35,.11,.27,.99) infinite}
    @keyframes hourglassFlip{0%,12%{transform:rotate(0deg)}45%,55%{transform:rotate(180deg)}88%,100%{transform:rotate(360deg)}}
    #pillHoldBanner.hourglass-active{animation:bannerPulse 1.6s ease-in-out infinite}
    @keyframes bannerPulse{0%,100%{filter:none}50%{filter:drop-shadow(0 0 6px rgba(245,158,11,.45))}}
    @media (prefers-reduced-motion:reduce){.hourglass-spin,#pillHoldBanner.hourglass-active{animation:none}}
</style>

<div class="container-wrap p-3">
    <aside class="buybox" id="buybox">
        <div class="card p-3 p-md-4">

            <div class="d-flex align-items-baseline gap-2 my-1 mt-0">
                <div class="price" id="price">£0.00</div><div class="compare" id="compare"></div>
            </div>

            <div class="rating mb-2 {{ $hasVendorReviews ? '' : 'd-none' }}">
                <div class="stars" id="stars"></div>
                <div class="text-secondary small" id="ratingText"></div>
            </div>

            <!-- Format block (In-person / Online) -->
            <div id="bbFormatBlock" class="mb-2" style="display:none">
                <div class="text-secondary small mb-1">Format</div>
                <div id="bbFormatPills" class="pills" role="radiogroup" aria-label="Format"></div>
            </div>

            <div id="options"></div>

            <div class="group-range mb-3" id="groupRange">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="text-secondary small mb-0" for="groupCount">Group size</label>
                    <span class="text-muted small">(3–10)</span>
                </div>
                <div class="stepper" aria-label="Group size">
                    <button type="button" id="groupDec">−</button>
                    <input id="groupCount" value="3" inputmode="numeric" aria-live="polite" aria-label="Group size">
                    <button type="button" id="groupInc">+</button>
                </div>
            </div>

            <div class="booking-wrap mt-3 mb-2" style="{{ $isEventOffering ? 'display:none' : '' }}">
                <div class="d-flex align-items-center gap-2 mb-2 booking-wrap-standard">
                    <i class="bi bi-calendar-event"></i>
                    <div class="fw-semibold">{{ $isEventOffering ? 'Tickets' : 'Availability' }}</div>
                    <span class="badge rounded-pill ms-auto badge-note">{{ $isEventOffering ? 'Book tickets' : 'Select a date' }}</span>
                </div>

                <div class="d-flex gap-2 mb-2 booking-wrap-standard" role="group" aria-label="Availability choice">
                    <button type="button" class="btn btn-outline-success" id="btnBookNow">
                        <i class="bi bi-check2-circle me-1"></i>Pick now
                    </button>
                    <button type="button" class="btn btn-outline-secondary active" id="btnBookLater">
                        <i class="bi bi-clock-history me-1"></i>Pick later
                    </button>
                </div>

                <div class="small booking-wrap-standard" id="bookingSelectionRow" style="display:none">
                    <span class="sel-pill">
                        <i class="bi bi-calendar2-week"></i>
                        <span id="bookingSelectionText"></span>
                        <a class="edit text-decoration-none ms-1" id="changeBooking">Change</a>
                    </span>
                </div>

                <div class="small text-warning d-flex align-items-center gap-2 mt-2 booking-wrap-standard" id="pillHoldBanner" style="display:none;">
                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                    <span>Held for <span id="pillHoldCountdown">10:00</span></span>
                </div>

                <div class="small text-secondary mt-2 booking-wrap-standard" id="bookNote">
                    {{ $isEventOffering ? 'Choose your ticket, then pick the date and time you want to attend.' : 'Choose a date now, or decide later. We’ll still secure your order and you can confirm with your practitioner anytime.' }}
                </div>

                <div class="booking-wrap-flexible d-none">
                    <div class="d-flex align-items-center gap-2 mb-2 booking-title-row">
                        <div class="booking-title-left">
                            <i class="bi bi-calendar-event"></i>
                            <div class="fw-semibold">Availability</div>
                        </div>
                        <span class="badge rounded-pill ms-auto badge-note">Flexible booking</span>
                    </div>

                    <div class="availability-actions" role="group" aria-label="Availability choice">
                        <button type="button" class="btn btn-outline-success active" id="btnBookLaterFlexible">
                            <i class="bi bi-check2-circle"></i>
                            Confirm later
                        </button>

                        <div class="availability-action-stack">
                            <button type="button" class="btn btn-outline-secondary" id="btnRequestTime">
                                <i class="bi bi-chat-dots"></i>
                                Add preference
                            </button>
                        </div>
                    </div>

                    <div class="preferred-time-box d-none" id="preferredTimeBox">
                        <label for="preferredTimeNote">Preferred days or times</label>
                        <textarea class="form-control" id="preferredTimeNote" placeholder="Example: Weekday evenings, Saturday morning, or any time next week"></textarea>
                    </div>

                    <div class="flexible-confirm-row" id="flexibleConfirmRow">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <span>Your order is secured today</span>
                    </div>

                    <p class="book-note" id="bookNoteFlexible">
                        This practitioner confirms times personally. Book now and you’ll agree a suitable date together after checkout.
                    </p>
                </div>

                <input type="hidden" id="bookingChoice" value="later">
                <input type="hidden" id="preferredDateValue" value="">
                <input type="hidden" id="preferredTimeValue" value="">
                <input type="hidden" id="preferredTZValue" value="">
                <input type="hidden" id="preferredTimeNoteValue" value="">
            </div>

            <div class="mb-4 d-flex align-items-center gap-3 mt-3">
                <label class="text-secondary small">Qty</label>
                <div class="stepper">
                    <button type="button" id="dec">−</button>
                    <input id="qty" value="1" inputmode="numeric">
                    <button type="button" id="inc">+</button>
                </div>
            </div>

            <div class="d-grid gap-2 mb-2" id="ctaWrap">
                <button class="btn btn-basket btn-lg js-add-to-cart" id="addBtn"
                        data-id="{{ $baseProductId }}"
                        data-product-id="{{ $baseProductId }}"
                        data-title="{{ e($productTitleSafe) }}"
                        data-price="{{ $initialPriceFormatted }}"
                        data-image="{{ $primaryImage }}"
                        data-url="{{ $productUrlCurrent }}"
                        data-qty="1"
                >{{ $primaryActionLabel }}</button>
                <button class="btn btn-main btn-lg" id="buyNow"
                        data-id="{{ $baseProductId }}"
                        data-product-id="{{ $baseProductId }}"
                        data-title="{{ e($productTitleSafe) }}"
                        data-price="{{ $initialPriceFormatted }}"
                        data-image="{{ $primaryImage }}"
                        data-url="{{ $productUrlCurrent }}"
                        data-qty="1"
                >{{ $secondaryActionLabel }}</button>
            </div>

            <div class="meta mb-3 mt-2">
                <div class="item"><i class="bi bi-patch-check"></i><span>90-day validity</span></div>
                <div class="item"><i class="bi bi-arrow-left-right"></i><span>Free exchanges</span></div>
                <div class="item"><i class="bi bi-leaf"></i><span>Carbon-neutral delivery</span></div>
            </div>

            <div class="trust mb-2">
                <div class="cell"><i class="bi bi-shield-lock"></i><div><div class="fw-semibold">Secure checkout</div><div class="text-secondary small">256-bit SSL • PCI-DSS</div></div></div>
                <div class="cell"><i class="bi bi-truck"></i><div><div class="fw-semibold">Instantly</div><div class="text-secondary small">to your inbox</div></div></div>
            </div>
        </div>
    </aside>
</div>

<div id="mobileBar" class="d-lg-none">
    <div class="m-left">
        <div class="price" id="mPrice">£0.00</div>
        <div class="rating {{ $hasVendorReviews ? '' : 'd-none' }}">
            <div class="stars" id="mStars"></div>
            <div class="text-secondary small" id="mRatingText"></div>
        </div>
    </div>
        <div class="m-right">
            <button class="btn btn-main" id="mobileAdd"
                data-id="{{ $baseProductId }}"
                data-product-id="{{ $baseProductId }}"
                data-title="{{ e($productTitleSafe) }}"
                data-price="{{ $initialPriceFormatted }}"
                data-image="{{ $primaryImage }}"
                data-url="{{ $productUrlCurrent }}"
                data-qty="1"
        >{{ $mobileActionLabel }}</button>
        </div>
    </div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="addToast" class="toast text-bg-dark border-0" role="status" aria-live="polite" aria-atomic="true">
        <div class="d-flex"><div class="toast-body">Added to your basket</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="configModalLabel">Customise your order</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="sheetOptions"></div>

                <div class="group-range mb-3" id="groupRangeSheet">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="text-secondary small mb-0" for="groupCountSheet">Group size</label>
                        <span class="text-muted small">(3–10)</span>
                    </div>
                    <div class="stepper" aria-label="Group size">
                        <button type="button" id="groupDecSheet">−</button>
                        <input id="groupCountSheet" value="3" inputmode="numeric" aria-live="polite" aria-label="Group size">
                        <button type="button" id="groupIncSheet">+</button>
                    </div>
                </div>

                <div class="booking-wrap">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-calendar-event"></i>
                        <div class="fw-semibold">{{ $isEventOffering ? 'Tickets' : 'Availability' }}</div>
                    <span class="badge rounded-pill ms-auto badge-note">{{ $isEventOffering ? 'Book tickets' : 'Select a date' }}</span>
                    </div>
                    <div class="d-flex gap-2" role="group" aria-label="Availability choice (sheet)">
                        <button type="button" class="btn btn-outline-success" id="sheetBookNow">
                            <i class="bi bi-check2-circle me-1"></i>{{ $isEventOffering ? 'Book tickets' : 'Pick now' }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary active" id="sheetBookLater">
                            <i class="bi bi-clock-history me-1"></i>{{ $isEventOffering ? 'Book later' : 'Pick later' }}
                        </button>
                    </div>
                    <div class="small text-secondary mt-2" id="sheetBookingNote">{{ $isEventOffering ? 'Choose your ticket to continue. The calendar stays open so you can pick the date and time you want.' : 'Choose now or decide later — your order is still secured.' }}</div>
                </div>

                <div class="booking-wrap booking-wrap-flexible d-none mt-3" id="sheetFlexibleWrap">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-calendar-event"></i>
                        <div class="fw-semibold">{{ $isEventOffering ? 'Tickets' : 'Availability' }}</div>
                        <span class="badge rounded-pill ms-auto badge-note">{{ $isEventOffering ? 'Book tickets' : 'Flexible booking' }}</span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success active" id="sheetBtnBookLaterFlexible">
                            <i class="bi bi-check2-circle me-1"></i>Confirm later
                        </button>

                        <button type="button" class="btn btn-outline-secondary" id="sheetBtnRequestTime">
                            <i class="bi bi-chat-dots me-1"></i>Add preference
                        </button>

                        <div class="preferred-time-box d-none" id="sheetPreferredTimeBox">
                            <label for="sheetPreferredTimeNote">Preferred days or times</label>
                            <textarea class="form-control" id="sheetPreferredTimeNote" placeholder="Example: Weekday evenings, Saturday morning, or any time next week"></textarea>
                        </div>
                    </div>

                    <div class="flexible-confirm-row mt-3" id="sheetFlexibleConfirmRow">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <span>{{ $isEventOffering ? 'Your ticket is secured today' : 'Your order is secured today' }}</span>
                    </div>

                    <p class="book-note mb-0" id="sheetBookNoteFlexible">
                        {{ $isEventOffering ? 'Tickets are reserved instantly. Choose the date and time from the calendar and we’ll hold it in your basket.' : 'This practitioner confirms times personally. Book now and you’ll agree a suitable date together after checkout.' }}
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto small text-secondary" id="sheetSubtotal">Subtotal: £0.00</div>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-main" id="sheetConfirm">{{ $isEventOffering ? 'Book tickets' : 'Confirm' }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" id="bookingModalContent">
            <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        @if($bookingModalPhoto)
                        <div class="flex-shrink-0">
                            <img
                                src="{{ $bookingModalPhoto }}"
                                alt="{{ $bookingModalTitle }} practitioner photo"
                                class="rounded-circle border"
                                style="width:52px;height:52px;object-fit:cover;"
                            >
                        </div>
                    @endif
                    <div>
                        <div class="text-muted small">{{ $bookingHeaderLabel }}</div>
                        <h5 class="modal-title mb-0" id="bookingModalLabel">{{ $bookingModalTitle }}</h5>
                        @if($bookingModalSubtitle)
                            <div class="small fw-semibold text-secondary">{{ $bookingModalSubtitle }}</div>
                        @endif
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-6 left-col">
                        <div class="cal-head mb-2">
                            <button class="btn btn-outline-secondary btn-sm" id="calPrev"><i class="bi bi-chevron-left"></i></button>
                            <div class="cal-month" id="calMonthLabel">Month YYYY</div>
                            <button class="btn btn-outline-secondary btn-sm" id="calNext"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <div class="cal-grid mb-2" id="calDayNames"></div>
                        <div class="cal-grid" id="calGrid" aria-label="Calendar dates"></div>
                        <div class="small text-secondary mt-2">
                            Time zone: <span class="tz-pill" id="tzCurrent"></span>
                            <select class="form-select form-select-sm d-inline-block ms-2" style="width:auto" id="tzSelect"></select>
                        </div>
                    </div>
                    <div class="col-lg-6 calendar-side">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button class="btn btn-outline-secondary btn-sm d-lg-none me-1" id="mobileBack">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <i class="bi bi-clock-history"></i><div class="fw-semibold">Available times</div>
                        </div>
                        <div id="slotList" class="d-flex flex-wrap gap-2"></div>
                        <hr class="my-3">
                        <div id="bookingSummary" class="small text-secondary">No date selected.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto text-secondary small" id="modalHint">Pick a date, then choose a time.</div>
                <div class="small fw-semibold text-warning" id="holdTimer" style="display:none;">
                    Holding your slot for <span id="holdCountdown">10:00</span>
                </div>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-main" id="confirmBooking" disabled>{{ $isEventOffering ? 'Book tickets' : 'Confirm selection' }}</button>
            </div>
        </div>
    </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Exact placeholder behaviour (demo data) from provided snippet
// Will be overridden with real product data if available from Blade.
const product={rating:4.9,ratingCount:128,options:[{name:"Format",values:["In-person","Online"]},{name:"People",values:["1 Person","2 Persons","3+ Group"]}],variants:[{id:1,options:["In-person","1 Person"],price:35000,compare:0,available:true},{id:2,options:["In-person","2 Persons"],price:65000,compare:70000,available:true},{id:3,options:["In-person","3+ Group"],price:90000,compare:0,available:true},{id:4,options:["Online","1 Person"],price:32000,compare:0,available:true},{id:5,options:["Online","2 Persons"],price:60000,compare:65000,available:true},{id:6,options:["Online","3+ Group"],price:85000,compare:0,available:true}]};
try{
  const bbOptions = @json($product['options'] ?? []);
  const bbVariants = @json($product['variants'] ?? []);
  const bbRating = @json($product['rating'] ?? null);
  const bbRatingCount = @json($product['review_count'] ?? null);
  function toPennies(v){ if(v==null) return null; let n=Number(v); if(!isFinite(n)) return null; if(n<1000) return Math.round(n*100); return Math.round(n); }
  if(Array.isArray(bbOptions) && bbOptions.length){
    product.options = bbOptions.map(function(o){
      const vals = Array.isArray(o.values) ? o.values.map(function(v){ return (v && typeof v==='object' && 'value' in v) ? (v.value||'') : String(v||'') }) : [];
      return { name: o.name || 'Option', values: vals };
    });
  }
  if(Array.isArray(bbVariants) && bbVariants.length){
    product.variants = bbVariants.map(function(v){
      const ops = Array.isArray(v.options) ? v.options : [];
      const price = toPennies(v.price ?? null);
      const compare = toPennies(v.compare ?? v.compare_at_price ?? null);
      return { id: v.id || 0, options: ops, price: price || 0, compare: compare || 0, available: true };
    });
  }
  if(bbRating!=null) product.rating = Number(bbRating)||0;
  if(bbRatingCount!=null) product.ratingCount = Number(bbRatingCount)||0;
}catch(e){}
const BASE_PRODUCT_ID = @json($baseProductId ?? null);
const BASE_PRODUCT_TITLE = @json($product['title'] ?? 'Experience');
const BASE_PRODUCT_IMAGE = @json($product['images'][0] ?? ($product['image'] ?? ''));
const BASE_PRODUCT_URL = @json(url()->current());
const BOOKING_FLOW = @json($bookingFlow);
const BOOKING_SOURCE_VERSION = @json($bookingSourceVersion);
const IS_EVENT_OFFERING = @json($isEventOffering);
const PRIMARY_ACTION_LABEL = @json($primaryActionLabel);
const SECONDARY_ACTION_LABEL = @json($secondaryActionLabel);
const MOBILE_ACTION_LABEL = @json($mobileActionLabel);
const BOOKING_ENDPOINT = BOOKING_SOURCE_VERSION === 'v3' ? 'offering' : 'product';
const IS_LIVE_BOOKING = BOOKING_FLOW === 'live';
const SERVER_DURATION_TEXT = @json(trim((string)($durationText ?? '')));
const HOLD_MINUTES=10;const bookings={};function dateKey(d){return d.toISOString().slice(0,10)}function ensureDay(d){const k=dateKey(d);if(!bookings[k])bookings[k]={booked:new Set(),reserved:{}};return bookings[k]}(function seed(){const day=new Date(Date.UTC(2025,9,7,0,0,0));const d=ensureDay(day);d.booked.add("16:28");const reservedStart=new Date(Date.UTC(2025,9,7,16,30));d.reserved["16:30"]={until:new Date(reservedStart.getTime()+HOLD_MINUTES*60000)}})();
const state={mode:"evoucher",selected:product.options.map(o=>o.values[0]),qty:1,variant:null,groupCount:3,recur:{cadence:"none",length:1}};const priceEl=document.getElementById("price"),compareEl=document.getElementById("compare"),optionsWrap=document.getElementById("options"),addBtn=document.getElementById("addBtn"),buyNow=document.getElementById("buyNow"),qty=document.getElementById("qty"),dec=document.getElementById("dec"),inc=document.getElementById("inc"),toastEl=document.getElementById("addToast"),stars=document.getElementById("stars"),ratingText=document.getElementById("ratingText"),groupRange=document.getElementById("groupRange"),groupCount=document.getElementById("groupCount"),groupInc=document.getElementById("groupInc"),groupDec=document.getElementById("groupDec");
const mPrice=document.getElementById('mPrice'),mStars=document.getElementById('mStars'),mRatingText=document.getElementById('mRatingText'),mobileAdd=document.getElementById('mobileAdd');
const configModalEl=document.getElementById('configModal'),configModal=new bootstrap.Modal(configModalEl),sheetOptions=document.getElementById('sheetOptions'),groupRangeSheet=document.getElementById('groupRangeSheet'),groupCountSheet=document.getElementById('groupCountSheet'),groupIncSheet=document.getElementById('groupIncSheet'),groupDecSheet=document.getElementById('groupDecSheet'),sheetBookLater=document.getElementById('sheetBookLater'),sheetBookNow=document.getElementById('sheetBookNow'),sheetConfirm=document.getElementById('sheetConfirm'),sheetSubtotal=document.getElementById('sheetSubtotal');
const sheetBookingWrap = configModalEl ? configModalEl.querySelector('.booking-wrap') : null;
const sheetBookingWrapFlexible = document.getElementById('sheetFlexibleWrap');
const sheetBtnBookLaterFlexible = document.getElementById('sheetBtnBookLaterFlexible');
const sheetBtnRequestTime = document.getElementById('sheetBtnRequestTime');
const sheetPreferredTimeBox = document.getElementById('sheetPreferredTimeBox');
const sheetPreferredTimeNote = document.getElementById('sheetPreferredTimeNote');
let sheetIntent='add';
let bookingIntent='select';
const btnBookNow=document.getElementById('btnBookNow'),btnBookLater=document.getElementById('btnBookLater'),bookingChoice=document.getElementById('bookingChoice'),preferredDateValue=document.getElementById('preferredDateValue'),preferredTimeValue=document.getElementById('preferredTimeValue'),preferredTZValue=document.getElementById('preferredTZValue'),bookingSelectionRow=document.getElementById('bookingSelectionRow'),bookingSelectionText=document.getElementById('bookingSelectionText'),changeBooking=document.getElementById('changeBooking');
const bookingModalEl=document.getElementById('bookingModal'),bookingModal=new bootstrap.Modal(bookingModalEl),bookingModalContent=document.getElementById('bookingModalContent'),calMonthLabel=document.getElementById('calMonthLabel'),calDayNames=document.getElementById('calDayNames'),calGrid=document.getElementById('calGrid'),calPrev=document.getElementById('calPrev'),calNext=document.getElementById('calNext'),slotList=document.getElementById('slotList'),bookingSummary=document.getElementById('bookingSummary'),confirmBooking=document.getElementById('confirmBooking'),tzCurrent=document.getElementById('tzCurrent'),tzSelect=document.getElementById('tzSelect'),modalHint=document.getElementById('modalHint'),mobileBack=document.getElementById('mobileBack'),holdTimer=document.getElementById('holdTimer'),holdCountdown=document.getElementById('holdCountdown');
const pillHoldBanner=document.getElementById('pillHoldBanner'),pillHoldCountdown=document.getElementById('pillHoldCountdown'),pillHourglass=pillHoldBanner.querySelector('i.bi-hourglass-split');
const PRACTITIONER_DATA = @json($product['practitioner'] ?? null);
const PRACTITIONER_PLAN_KEY = String(PRACTITIONER_DATA?.plan_key || PRACTITIONER_DATA?.plan_label || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-');
const IS_PAID_PRACTITIONER = Boolean(PRACTITIONER_DATA?.is_paid_plan) || (!['', 'starter'].includes(PRACTITIONER_PLAN_KEY));
const bookingState = {
  loading: false,
  loaded: false,
  cacheKey: '',
  error: null,
  payload: null,
  slotsByDay: {},
  reservationHolds: {},
  availabilitySettings: {},
};
const bookingWrap = document.querySelector('.booking-wrap');
const bookingWrapStandardEls = bookingWrap ? bookingWrap.querySelectorAll('.booking-wrap-standard') : [];
const bookingWrapFlexible = bookingWrap ? bookingWrap.querySelector('.booking-wrap-flexible') : null;
const btnBookLaterFlexible = document.getElementById('btnBookLaterFlexible');
const btnRequestTime = document.getElementById('btnRequestTime');
const preferredTimeBox = document.getElementById('preferredTimeBox');
const preferredTimeNote = document.getElementById('preferredTimeNote');
const preferredTimeNoteValue = document.getElementById('preferredTimeNoteValue');
const AVAILABILITY_TEXT = {
  voucher: 'Your voucher is valid for 12 months from the date of purchase. Please book and take your therapy before the expiry date.',
  leadTime: 'We recommend booking at least 2–4 weeks in advance to ensure your preferred slots are available.',
  duration: 'Please allow up to 60 minutes for the full therapy (plus a few minutes to settle in).',
};
function weekdayNameFromIso(isoDay){
  return ({1:'Monday',2:'Tuesday',3:'Wednesday',4:'Thursday',5:'Friday',6:'Saturday',7:'Sunday'})[Number(isoDay)] || null;
}
function joinWeekdayNames(names){
  const list = (Array.isArray(names) ? names : []).filter(Boolean);
  if (!list.length) return '';
  if (list.length === 1) return list[0];
  if (list.length === 2) return `${list[0]} and ${list[1]}`;
  const last = list[list.length - 1];
  return `${list.slice(0, -1).join(', ')}, and ${last}`;
}
function buildAvailabilityPatternFromSlots(slotsByDay){
  try{
    const seen = new Set();
    Object.keys(slotsByDay || {}).forEach(key => {
      const slots = slotsByDay?.[key]?.slots || [];
      if (!Array.isArray(slots) || !slots.length) return;
      const date = new Date(key + 'T00:00:00');
      if (Number.isNaN(date.getTime())) return;
      seen.add(date.getDay() === 0 ? 7 : date.getDay());
    });
    const names = Array.from(seen).sort((a,b)=>a-b).map(weekdayNameFromIso).filter(Boolean);
    if (!names.length) return '';
    if (names.length === 7) {
      return 'This therapy is available 7 days a week (subject to practitioner availability).';
    }
    return `This therapy is usually available on ${joinWeekdayNames(names)} (subject to practitioner availability).`;
  }catch(_e){
    return '';
  }
}
function parseDurationMinutesFromText(text){
  try{
    const value = String(text || '').toLowerCase().trim();
    if (!value) return 0;
    const hourMatch = value.match(/(\d+(?:\.\d+)?)\s*(hour|hr|hrs|hours)\b/);
    if (hourMatch) return Math.max(0, Math.round(parseFloat(hourMatch[1]) * 60));
    const minuteMatch = value.match(/(\d+(?:\.\d+)?)\s*(minute|min|mins|minutes)\b/);
    if (minuteMatch) return Math.max(0, Math.round(parseFloat(minuteMatch[1])));
    return 0;
  }catch(_e){
    return 0;
  }
}
function resolvedBookingDurationText(){
  const serverText = bookingState.payload?.bookingConfig?.duration || '';
  if (bookingState.loaded && serverText) {
    return serverText;
  }
  const localText = bookingVariantLabel() || (state.variant && state.variant.title ? String(state.variant.title).trim() : '');
  const minutes = parseDurationMinutesFromText(localText);
  if (minutes > 0) {
    return `${minutes} Minutes`;
  }
  return serverText || '60 Minutes';
}
function hasLiveAvailability(){
  try{
    return Object.values(bookingState.slotsByDay || {}).some(day => Array.isArray(day?.slots) && day.slots.length > 0);
  }catch(_e){
    return false;
  }
}
function syncBookingMode(){
  if (!bookingWrap) return;
  const flexibleMode = !IS_LIVE_BOOKING;
  bookingWrapStandardEls.forEach(function(el){
    el.classList.toggle('d-none', flexibleMode);
  });
  if (bookingWrapFlexible) {
    bookingWrapFlexible.classList.toggle('d-none', !flexibleMode);
  }
  if (sheetBookingWrap) {
    sheetBookingWrap.classList.toggle('d-none', flexibleMode);
  }
  if (sheetBookingWrapFlexible) {
    sheetBookingWrapFlexible.classList.toggle('d-none', !flexibleMode);
  }
  if (flexibleMode) {
    bookingChoice.value = 'later';
    preferredTimeValue.value = '';
    preferredDateValue.value = '';
    preferredTZValue.value = '';
    if (bookingSelectionRow) bookingSelectionRow.style.display = 'none';
    if (bookingSelectionText) bookingSelectionText.textContent = '';
    if (btnBookLaterFlexible) {
      btnBookLaterFlexible.classList.add('active', 'btn-outline-success');
      btnBookLaterFlexible.classList.remove('btn-outline-secondary');
    }
    if (btnRequestTime) {
      btnRequestTime.classList.remove('active', 'btn-outline-success');
      btnRequestTime.classList.add('btn-outline-secondary');
    }
    if (preferredTimeBox) preferredTimeBox.classList.add('d-none');
    if (sheetBtnBookLaterFlexible) {
      sheetBtnBookLaterFlexible.classList.add('active', 'btn-outline-success');
      sheetBtnBookLaterFlexible.classList.remove('btn-outline-secondary');
    }
    if (sheetBtnRequestTime) {
      sheetBtnRequestTime.classList.remove('active', 'btn-outline-success');
      sheetBtnRequestTime.classList.add('btn-outline-secondary');
    }
    if (sheetPreferredTimeBox) sheetPreferredTimeBox.classList.add('d-none');
  }
}
function syncAvailabilityCopy(){
  try{
    const patternEl = document.getElementById('wowAvailabilityPattern');
    const leadTimeEl = document.getElementById('wowAvailabilityLeadTime');
    const durationEl = document.getElementById('wowAvailabilityDuration');
    const voucherEl = document.getElementById('wowAvailabilityVoucher');
    if (voucherEl) voucherEl.textContent = AVAILABILITY_TEXT.voucher;
    if (bookingState.loaded) {
      const pattern = buildAvailabilityPatternFromSlots(bookingState.slotsByDay);
      if (patternEl && pattern) patternEl.textContent = pattern;
      const leadTime = bookingState.availabilitySettings?.leadTimeText || bookingState.payload?.bookingConfig?.lead_time_text || '';
      if (leadTimeEl) leadTimeEl.textContent = leadTime || AVAILABILITY_TEXT.leadTime;
      const durationText = resolvedBookingDurationText();
      if (durationEl) durationEl.textContent = durationText ? `Please allow up to ${durationText.replace(/\s+/g, ' ').trim().replace(/^(\d+)\s*Minutes?$/i, '$1 minutes')} for the full therapy (plus a few minutes to settle in).` : AVAILABILITY_TEXT.duration;
      if (patternEl && pattern) patternEl.style.display = '';
    } else {
      const durationText = resolvedBookingDurationText();
      if (durationEl && durationText) {
        durationEl.textContent = `Please allow up to ${durationText.replace(/\s+/g, ' ').trim().replace(/^(\d+)\s*Minutes?$/i, '$1 minutes')} for the full therapy (plus a few minutes to settle in).`;
      }
    }
    syncBookingMode();
  }catch(_e){}
}
function getCurrentPriceOptionId(){
  try {
    const id = state?.variant?.id ? String(state.variant.id) : '';
    const match = id.match(/po_(\d+)/i);
    if (match) return parseInt(match[1], 10);
    if (/^\d+$/.test(id)) return parseInt(id, 10);
    const url = new URL(window.location.href);
    const variant = String(url.searchParams.get('variant') || '');
    const qMatch = variant.match(/po_(\d+)/i);
    if (qMatch) return parseInt(qMatch[1], 10);
    return /^\d+$/.test(variant) ? parseInt(variant, 10) : null;
  } catch (_e) {
    return null;
  }
}
function bookingCacheKey(){
  return `${String(BASE_PRODUCT_ID || '')}:${String(getCurrentPriceOptionId() || 'default')}:${String(bookingVariantLabel() || '')}`;
}
function bookingDateKey(d){
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
function bookingSlotsForDate(d){
  return bookingState.slotsByDay?.[bookingDateKey(d)]?.slots || [];
}
function bookingHoldUntilForSlot(d, slotStart){
  return bookingState.reservationHolds?.[bookingDateKey(d)]?.[slotStart] || null;
}
function firstAvailableBookingDate(){
  try{
    const keys = Object.keys(bookingState.slotsByDay || {}).filter(key => {
      const slots = bookingState.slotsByDay?.[key]?.slots || [];
      return Array.isArray(slots) && slots.length > 0;
    }).sort();
    const key = keys[0];
    if(!key) return null;
    const date = new Date(key + 'T00:00:00');
    return Number.isNaN(date.getTime()) ? null : date;
  }catch(_e){
    return null;
  }
}
function syncCalendarToAvailability(forceFirst = false){
  try{
    if (!bookingState.loaded) return false;
    const selected = calendarState.selectedDate;
    const selectedHasAvailability = selected ? bookingSlotsForDate(selected).length > 0 : false;
    const firstAvailable = firstAvailableBookingDate();
    const target = forceFirst ? firstAvailable : (selectedHasAvailability ? selected : firstAvailable);
    if (!target) return false;
    calendarState.viewYear = target.getFullYear();
    calendarState.viewMonth = target.getMonth();
    if (forceFirst || !selectedHasAvailability) {
      calendarState.selectedDate = target;
      calendarState.selectedTime = null;
    }
    return true;
  }catch(_e){
    return false;
  }
}
async function loadBookingContext(force = false){
  if (!IS_LIVE_BOOKING) {
    return false;
  }
  const cacheKey = bookingCacheKey();
  if (!force && bookingState.loaded && bookingState.cacheKey === cacheKey) {
    return true;
  }
  bookingState.loading = true;
  bookingState.error = null;
  try {
    const priceOptionId = getCurrentPriceOptionId();
    const variantLabel = bookingVariantLabel();
    const url = new URL(`/api/booking/${BOOKING_ENDPOINT}/${encodeURIComponent(String(BASE_PRODUCT_ID || ''))}`, window.location.origin);
    if (priceOptionId) {
      url.searchParams.set('price_option_id', String(priceOptionId));
    }
    if (variantLabel) {
      url.searchParams.set('variant_label', variantLabel);
    }
    const response = await fetch(url.toString(), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });
    if (!response.ok) {
      throw new Error(`Availability request failed (${response.status})`);
    }
    const json = await response.json();
    const payload = json?.bookingPayload || {};
    bookingState.payload = payload;
    bookingState.slotsByDay = payload.slotsByDay || {};
    bookingState.reservationHolds = payload.reservationHolds || {};
    bookingState.availabilitySettings = payload.availabilitySettings || {};
    bookingState.cacheKey = cacheKey;
    bookingState.loaded = true;

    const timezone = bookingState.availabilitySettings.timezone || calendarState.tz;
    if (timezone) {
      calendarState.tz = timezone;
      if (tzCurrent) tzCurrent.textContent = timezone;
      if (tzSelect) {
        tzSelect.value = timezone;
        tzSelect.disabled = true;
      }
    }
    syncAvailabilityCopy();
    return true;
  } catch (error) {
    bookingState.error = error;
    bookingState.loaded = false;
    bookingState.payload = null;
    bookingState.slotsByDay = {};
    bookingState.reservationHolds = {};
    return false;
  } finally {
    bookingState.loading = false;
  }
}
function fmt(c){try{return new Intl.NumberFormat("en-GB",{style:"currency",currency:"GBP"}).format(c/100)}catch(e){return "£"+(c/100).toFixed(2)}}
function buildStarsHTML(r){
  const full=Math.floor(Number(r)||0);
  let out='';
  for(let i=1;i<=5;i++){
    out += (i<=full)
      ? '<span class="star" style="color:#f5c84b;"></span>'
      : '<span class="star star--empty"></span>';
  }
  return '<span class="stars" aria-hidden="true">'+out+'</span>';
}
function renderStars(){
  stars.innerHTML = buildStarsHTML(product.rating);
  ratingText.textContent = `${(Number(product.rating)||0).toFixed(1)} (${Number(product.ratingCount)||0})`;
  if(mStars){ mStars.innerHTML = stars.innerHTML; mRatingText.textContent = ratingText.textContent; }
}
function displayName(name){
  try {
    var s = String(name||'');
    if (/person/i.test(s)) return 'People';
    if (/session/i.test(s)) return 'Pick Sessions';
  } catch(e) {}
  return name || 'Option';
}
// ===== Sessions dropdown helpers =====
function availableValuesForOption(optIdx, opts){
  try{
    const options = opts || {}; const contextAware = !!options.contextAware;
    const declared = ((product.options?.[optIdx]?.values)||[]).map(v=>String(v||''));
    let pool = (product.variants||[]);
    if (contextAware){
      pool = pool.filter(v => {
        const ops = v.options||[];
        for (let j=0; j<(product.options||[]).length; j++){
          if (j===optIdx) continue;
          const sel = String(state.selected[j]||'');
          const got = String(ops[j]||'');
          if (!equalsAtIndex(j, sel, got)) return false;
        }
        return true;
      });
      if (!pool.length) pool = (product.variants||[]);
    }
    const allowed = new Set(); pool.forEach(v=>{ const val=String((v.options||[])[optIdx]||''); if(val) allowed.add(val); });
    return declared.filter(v => allowed.has(String(v)));
  }catch(e){ return (product.options?.[optIdx]?.values)||[] }
}
function parseSessions(val){
  try{
    const m = String(val||'').match(/(\d+)/);
    return m ? parseInt(m[1],10) : null;
  }catch(e){ return null }
}
function parseDurationRange(text){
  try{
    const raw = String(text || '').toLowerCase().trim();
    if (!raw) return null;
    const ranged = raw.match(/(\d+(?:\.\d+)?)\s*[-–—]\s*(\d+(?:\.\d+)?)\s*(hour|hr|hrs|hours|minute|min|mins|minutes)\b/i);
    if (ranged) {
      const unit = String(ranged[3] || '').toLowerCase();
      const a = parseFloat(ranged[1]);
      const b = parseFloat(ranged[2]);
      const factor = /hour|hr/.test(unit) ? 60 : 1;
      const min = Math.round(Math.min(a, b) * factor);
      const max = Math.round(Math.max(a, b) * factor);
      return { min, max };
    }
    const single = raw.match(/(\d+(?:\.\d+)?)\s*(hour|hr|hrs|hours|minute|min|mins|minutes)\b/i);
    if (single) {
      const unit = String(single[2] || '').toLowerCase();
      const value = parseFloat(single[1]);
      const minutes = Math.round(/hour|hr/.test(unit) ? value * 60 : value);
      return { min: minutes, max: minutes };
    }
    return null;
  }catch(_e){
    return null;
  }
}
function getBaseDurationInfo(){
  const text = bookingState.loaded
    ? (bookingState.payload?.bookingConfig?.duration || SERVER_DURATION_TEXT || '')
    : (SERVER_DURATION_TEXT || '');
  const parsed = parseDurationRange(text);
  return parsed ? { text, parsed } : null;
}
function hasResolvedDurationData(){
  const info = getBaseDurationInfo();
  if (!info) return false;
  const min = Number(info.parsed?.min || 0);
  const max = Number(info.parsed?.max || 0);
  if (min <= 0 || max <= 0) return false;
  if (min !== 60 || max !== 60) return true;
  return /[-–—]/.test(String(info.text || ''));
}
function getEffectiveSessionMinutes(val){
  const parsed = parseDurationRange(val);
  if (parsed && Number.isFinite(parsed.max) && parsed.max > 0) {
    return parsed.max;
  }
  const info = getBaseDurationInfo();
  if (info && Number.isFinite(info.parsed?.max) && info.parsed.max > 0 && hasResolvedDurationData()) {
    return info.parsed.max;
  }
  return 60;
}
function sessionsSummaryForValue(val, minTotal){
  const n = parseSessions(val) || 1;
  const unit = Math.round((minTotal||0)/n);
  return { n, unit };
}
function computeBestValue(variants, optIdx){
  // Build map sessionsCount -> min total price among all variants
  const map = new Map();
  (variants||[]).forEach(v=>{
    const val = (v.options||[])[optIdx];
    const sessions = parseSessions(val) || 1;
    if(!sessions || !v.price) return;
    const price = Number(v.price)||0;
    const cur = map.get(sessions);
    if(cur==null || price < cur) map.set(sessions, price);
  });
  // Find best by lowest unit price with tie-breakers
  let best = { n:null, unit:Infinity, total:Infinity };
  map.forEach((total, n)=>{
    const unit = total / n;
    if (unit < best.unit || (unit === best.unit && (n > best.n || (n === best.n && total < best.total)))){
      best = { n, unit, total };
    }
  });
  return best.n ? best : null;
}
function buildSessionsDropdown(container, optIdx, opt, { contextAware=false } = {}){
  const root = document.createElement('div'); root.className = 'sessions-dd';
  const label = document.createElement('div'); label.className = 'sd-label'; label.textContent = 'Pick sessions'; root.appendChild(label);

  const trig = document.createElement('button'); trig.type='button'; trig.className='sd-trigger'; trig.setAttribute('aria-haspopup','listbox'); trig.setAttribute('aria-expanded','false');
  const left = document.createElement('div'); left.className='sd-val';
  const chev = document.createElement('span'); chev.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
  trig.appendChild(left); trig.appendChild(chev);
  root.appendChild(trig);

  const popHold = document.createElement('div'); popHold.className='sd-pop';
  const pop = document.createElement('div'); pop.className='sd-popover'; pop.style.display='none'; pop.setAttribute('role','listbox');

  // Build options grouped by sessions count (context-aware, preserve order, limit to 3)
  function currentValues(){ return availableValuesForOption(optIdx, { contextAware: true }); }
  let valuesAll = currentValues();

  function computeBestContext(){
    const map = new Map();
    (product.variants||[]).forEach(v=>{
      const ops = v.options||[];
      // ensure other selections match context
      for (let j=0; j<(product.options||[]).length; j++){
        if (j===optIdx) continue;
        const sel = String(state.selected[j]||'');
        const got = String(ops[j]||'');
        if (!equalsAtIndex(j, sel, got)) return;
      }
      const val = String(ops[optIdx]||'');
      const sessions = parseSessions(val) || 1;
      const price = Number(v.price)||0;
      const cur = map.get(val);
      if (cur == null || price < cur.price) {
        map.set(val, { price, sessions });
      }
    });
    let best = { value:null, sessions:null, unit:Infinity, total:Infinity };
    map.forEach((entry, value)=>{
      const unit = entry.price / entry.sessions;
      if (unit < best.unit || (unit === best.unit && (entry.sessions > best.sessions || (entry.sessions === best.sessions && entry.price < best.total)))){
        best = { value, sessions: entry.sessions, unit, total: entry.price };
      }
    });
    return best.value ? best : null;
  }

  function labelForVal(val){
    const raw = String(val||'').trim();
    const n = parseSessions(raw);
    return (/^\d+$/.test(raw) && n) ? `${n} Sessions` : raw;
  }
  function priceInfoForVal(val){
    const n = parseSessions(val) || 1;
    let total = Infinity;
    (product.variants||[]).forEach(v=>{
      const ops = v.options||[];
      if (String(ops[optIdx]||'') !== String(val)) return;
      for (let j=0; j<(product.options||[]).length; j++){
        if (j===optIdx) continue;
        const sel = String(state.selected[j]||'');
        const got = String(ops[j]||'');
        if (!equalsAtIndex(j, sel, got)) return;
      }
      total = Math.min(total, Number(v.price)||0);
    });
    if(!isFinite(total)) total = 0;
    return { total, unit: total/(n||1) };
  }

  function renderPopover(){
    pop.innerHTML='';
    // Re-evaluate values at open time in case context changed
    valuesAll = currentValues();
    const best = computeBestContext();
    valuesAll.forEach(val=>{
      const { total, unit } = priceInfoForVal(val);
      const n = parseSessions(val) || 1;
      const row = document.createElement('div'); row.className='sd-option'; row.setAttribute('role','option'); row.tabIndex=0; row.dataset.sessions=String(n); row.dataset.value=String(val);
      const left = document.createElement('div'); left.className='sd-left';
      const ttl = document.createElement('div'); ttl.className='sd-title'; ttl.textContent = labelForVal(val);
      const sub = document.createElement('div'); sub.className='sd-sub'; sub.textContent = `${fmt(total)} total • ${fmt(unit)} / session`;
      left.appendChild(ttl); left.appendChild(sub);
      const right = document.createElement('div'); right.className='sd-right';
      if (best && best.value === val){ const badge=document.createElement('span'); badge.className='sd-badge'; badge.textContent='Best value'; right.appendChild(badge); }
      const chk = document.createElement('span'); chk.className='sd-check'; chk.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
      right.appendChild(chk);
      row.appendChild(left); row.appendChild(right);
      row.addEventListener('click',(e)=>{ e.preventDefault(); e.stopPropagation(); selectValue(val); closePop(); });
      row.addEventListener('keydown',(e)=>{
        if(e.key==='Enter' || e.key===' '){ e.preventDefault(); e.stopPropagation(); selectValue(val); closePop(); }
        if(e.key==='ArrowDown'){ e.preventDefault(); const nx = row.nextElementSibling && row.nextElementSibling.classList.contains('sd-option') ? row.nextElementSibling : pop.querySelector('.sd-option'); nx && nx.focus && nx.focus(); }
        if(e.key==='ArrowUp'){ e.preventDefault(); const pv = row.previousElementSibling && row.previousElementSibling.classList.contains('sd-option') ? row.previousElementSibling : pop.querySelector('.sd-option:last-of-type'); pv && pv.focus && pv.focus(); }
        if(e.key==='Escape'){ e.preventDefault(); closePop(); trig.focus(); }
      });
      pop.appendChild(row);
    });
    // Mark active
    const curVal = String(state.selected[optIdx]||'');
    pop.querySelectorAll('.sd-option').forEach(el=>{
      if (String(el.dataset.value||'') === curVal) el.classList.add('active');
    });
  }

  function openPop(){ pop.style.display='block'; trig.setAttribute('aria-expanded','true'); renderPopover(); setTimeout(()=>{ outsideBind(true); },0); }
  function closePop(){ pop.style.display='none'; trig.setAttribute('aria-expanded','false'); outsideBind(false); }
  function outsideBind(on){
    if(on){ document.addEventListener('click', outsideHandler); document.addEventListener('keydown', keyHandler); }
    else { document.removeEventListener('click', outsideHandler); document.removeEventListener('keydown', keyHandler); }
  }
  function outsideHandler(e){ if(!root.contains(e.target)) closePop(); }
  function keyHandler(e){ if(e.key==='Escape') closePop(); }

  function updateTrigger(){
    valuesAll = currentValues();
    const cur = String(state.selected[optIdx]||'');
    let n = parseSessions(cur);
    // If current selection isn't among available, reset to first
    const validVals = valuesAll;
    let changed = false;
    if(!validVals.includes(cur)){
      const fallback = validVals[0] || '';
      state.selected[optIdx] = fallback;
      n = parseSessions(fallback) || 1;
      changed = true;
    } else {
      n = n || 1;
    }
    left.innerHTML = '';
    const label = document.createElement('span'); label.textContent = (/^\d+$/.test(cur.trim()) && n ? `${n} Sessions` : cur);
    left.appendChild(label);
    const bc = computeBestContext(); if (bc && bc.value === cur){ const b = document.createElement('span'); b.className='sd-badge'; b.textContent='Best value'; left.appendChild(b); }
    if (changed) {
      try { updateVariant(); updateSheetSubtotal(); } catch(e){}
    }
  }
  function selectValue(val){
    state.selected[optIdx] = String(val);
    updateTrigger();
    syncOptionAria(optIdx);
    if (optIdx===findLocationIndex()) { try{ syncFormatUI(); }catch(e){} }
    updateVariant();
    updateSheetSubtotal();
    if(container===sheetOptions){ groupRangeSheet.style.display=isGroup()?"block":"none" }
  }

  trig.addEventListener('click', ()=>{ const open = trig.getAttribute('aria-expanded')==='true'; open ? closePop() : openPop(); });
  trig.addEventListener('keydown', (e)=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); const open = trig.getAttribute('aria-expanded')==='true'; open ? closePop() : openPop(); } if(e.key==='ArrowDown'){ e.preventDefault(); openPop(); const first=pop.querySelector('.sd-option'); first && first.focus && first.focus(); }});

  popHold.appendChild(pop);
  root.appendChild(popHold);
  container.appendChild(root);
  updateTrigger();
}
function buildOptionsInto(container){
  container.innerHTML="";
  const locIdx = findLocationIndex();
  (product.options||[]).forEach((opt,optIdx)=>{
    const isSessions = /session/i.test(String(opt?.name||''));
    if (isSessions){
      buildSessionsDropdown(container, optIdx, opt, { contextAware: false });
    } else {
      const label=document.createElement("div"); label.className="text-secondary small mb-1"; label.textContent=displayName(opt.name); container.appendChild(label);
      const row=document.createElement("div"); row.className="pills mb-2"; row.setAttribute('data-opt-idx', String(optIdx));
      if (optIdx===locIdx){ row.classList.add(container===sheetOptions ? 'sheet-location-row' : 'bb-location-row'); }
      const vals = availableValuesForOption(optIdx, { contextAware: false });
      // If current selection is not available, set to first available
      if(vals.length && !vals.includes(String(state.selected[optIdx]||''))){ state.selected[optIdx] = String(vals[0]); }
      vals.forEach(val=>{
        const txt = String(val||'');
        const b=document.createElement("button"); b.type="button"; b.className="pill"; b.setAttribute("role","radio"); b.dataset.optIdx=String(optIdx); b.dataset.optValue=txt;
        b.setAttribute("aria-checked", txt===state.selected[optIdx] ? "true":"false"); b.textContent=txt;
        b.addEventListener("click",()=>{
          state.selected[optIdx]=txt;
          syncOptionAria(optIdx);
          if (optIdx===locIdx) { try{ syncFormatUI(); }catch(e){} }
          updateVariant();
          updateSheetSubtotal();
          try{ setTimeout(()=>buildOptions(),0); }catch(e){}
          if(container===sheetOptions){ groupRangeSheet.style.display=isGroup()?"block":"none" }
        });
        row.appendChild(b)
      });
      container.appendChild(row)
      // Ensure initial aria is correct
      syncOptionAria(optIdx);
    }
  })
}

function syncOptionAria(optIdx){
  try{
    var locIdx = (__locIdxCache != null) ? __locIdxCache : findLocationIndex();
    const selector = '.pills[data-opt-idx="'+String(optIdx)+'"] .pill';
    document.querySelectorAll(selector).forEach(function(p){
      const val = String(p.dataset.optValue||'');
      const cur = String(state.selected[optIdx]||'');
      var match;
      if (optIdx===locIdx) {
        // Loose match for locations
        var isOnline = strNorm(val)==='online' || strNorm(cur)==='online';
        match = isOnline ? (strNorm(val)==='online' && strNorm(cur)==='online') : looseMatch(val, cur);
      } else {
        match = (strNorm(val)===strNorm(cur));
      }
      p.setAttribute('aria-checked', match ? 'true' : 'false');
    });
  }catch(e){}
}

function syncVariantQueryParam(){
  try {
    var url = new URL(window.location.href);
    var vid = (state.variant && state.variant.id) ? String(state.variant.id) : null;
    if (vid) {
      url.searchParams.set('variant', vid);
    } else {
      url.searchParams.delete('variant');
    }
    var next = url.toString();
    if (next !== window.location.href) {
      window.history.replaceState({}, '', next);
    }
  } catch (e) {}
}
function buildOptions(){optionsWrap.innerHTML="";buildOptionsInto(optionsWrap)}
function findLocationIndex(){
  var idx=-1; try{ (product.options||[]).forEach(function(o,i){ var n=String(o?.name||'').toLowerCase(); if(idx===-1 && (n.includes('location'))) idx=i; }); }catch(e){}
  return idx;
}
var __locIdxCache = null;
function syncFormatUI(){
  try{
    var locIdx = (__locIdxCache != null) ? __locIdxCache : findLocationIndex();
    if(locIdx == null || locIdx < 0) return;
    var pills = document.getElementById('bbFormatPills'); if(!pills) return;
    var cur = String(state.selected[locIdx]||'');
    var online = cur.toLowerCase()==='online';
    pills.querySelectorAll('.pill').forEach(function(p){
      var isOnline = String(p.dataset.variantLocation||'').toLowerCase()==='online';
      p.setAttribute('aria-checked', online ? (isOnline?'true':'false') : (!isOnline?'true':'false'));
    });
  }catch(e){}
}
function buildFormatBlock(){
  var block=document.getElementById('bbFormatBlock'); var pills=document.getElementById('bbFormatPills');
  if(!block||!pills) return; pills.innerHTML='';
  var locIdx=findLocationIndex(); __locIdxCache = locIdx; if(locIdx<0){ block.style.display='none'; return; }
  var vals=((product.options[locIdx]||{}).values||[]).map(function(v){ return String(v||'') });
  var hasOnline=vals.some(function(v){ return v.toLowerCase()==='online' });
  var phys=vals.filter(function(v){ return v && v.toLowerCase()!=='online' });
  var hasPhys=phys.length>0;
  // Show Format only if there is at least one non-Online (in-person) value
  if(!hasPhys){ block.style.display='none'; return; }
  function makeBtn(label, dataVal){ var b=document.createElement('button'); b.type='button'; b.className='pill'; b.setAttribute('role','radio'); b.dataset.variantLocation=dataVal; b.textContent=label; b.setAttribute('aria-checked','false'); return b; }
  if(hasPhys){ var inBtn=makeBtn('In-person', phys[0]); inBtn.addEventListener('click', function(){ state.selected[locIdx]=phys[0]; syncFormatUI(); syncOptionAria(locIdx); updateVariant(); }); pills.appendChild(inBtn); }
  if(hasOnline){ var onBtn=makeBtn('Online', 'Online'); onBtn.addEventListener('click', function(){ state.selected[locIdx]='Online'; syncFormatUI(); syncOptionAria(locIdx); updateVariant(); }); pills.appendChild(onBtn); }
  syncFormatUI(); block.style.display='block';
}
function strNorm(s){ return String(s||'').trim().toLowerCase(); }
function tok(s){ return strNorm(s).replace(/[^a-z0-9\s]+/g,' ').split(/\s+/).filter(Boolean); }
function overlap(a,b){ var A=new Set(tok(a)), B=new Set(tok(b)); var c=0; A.forEach(t=>{ if(B.has(t)) c++; }); return c; }
function locationSimilar(a,b){
  var A=strNorm(a), B=strNorm(b);
  if(!A||!B) return A===B;
  if(A==='online' || B==='online') return A==='online' && B==='online';
  if(A.includes(B) || B.includes(A)) return true;
  var shared = overlap(a,b);
  if(shared >= 3) return true;
  if(shared >= 1){
    var firstA = A.split(/\s+/)[0] || '';
    var firstB = B.split(/\s+/)[0] || '';
    if(firstA && firstB && (firstA.startsWith(firstB) || firstB.startsWith(firstA))) return true;
  }
  return false;
}
function looseMatch(a,b){ var A=strNorm(a), B=strNorm(b); if(!A||!B) return A===B; return A.includes(B) || B.includes(A) || locationSimilar(a,b); }
function equalsAtIndex(i, sel, got){
  var locIdx = (__locIdxCache != null) ? __locIdxCache : findLocationIndex();
  if (i===locIdx){ return locationSimilar(sel, got); }
  return strNorm(sel)===strNorm(got);
}

function applyVariantFromQuery(){
  try {
    var url = new URL(window.location.href);
    var varId = url.searchParams.get('variant');
    if (!varId) return;
    var match = (product.variants || []).find(function(v){ return String(v.id) === String(varId); });
    if (!match || !Array.isArray(match.options)) return;
    for (var i = 0; i < match.options.length; i++) {
      var want = String(match.options[i] || '');
      var values = (product.options[i] && product.options[i].values) ? product.options[i].values : [];
      var locIdx = (__locIdxCache != null) ? __locIdxCache : findLocationIndex();
      var chosen;
      if (i === locIdx) {
        if (strNorm(want) === 'online') {
          chosen = values.find(function(v){ return strNorm(v) === 'online'; }) || values[0] || want;
        } else {
          var best = null, bestScore = -1;
          values.forEach(function(v){ var sc = overlap(v, want); if (sc > bestScore) { best = v; bestScore = sc; } });
          chosen = best || values[0] || want;
        }
      } else {
        chosen = values.find(function(v){ return strNorm(v) === strNorm(want); }) || values[0] || want;
      }
      state.selected[i] = chosen;
    }
  } catch (e) {}
}
function findVariant(){
  var firstAvail = product.variants.find(function(v){ return v && v.available; }) || product.variants[0] || null;
  if(!Array.isArray(product.variants) || !product.variants.length) return firstAvail;
  var exact = product.variants.find(function(v){ var ops=v.options||[]; return (state.selected||[]).every(function(s, i){ return equalsAtIndex(i, s, ops[i]); }); });
  return exact || firstAvail;
}
function isGroup(){return (state.selected[1]||"").toLowerCase().includes("3+")}
function variantFor(format,people){return product.variants.find(v=>v.options[0]===format&&v.options[1]===people)}
function stepForFormat(format){const v1=variantFor(format,"1 Person");const v2=variantFor(format,"2 Persons");if(v1&&v2) return Math.max(0, v2.price - v1.price);const vg=variantFor(format,"3+ Group");if(v2&&vg) return Math.max(0, vg.price - v2.price);return 25000}
function priceForGroup(format,n){const base=variantFor(format,"3+ Group");const step=stepForFormat(format);if(!base) return step*n;const extra=Math.max(0,n-3);return base.price + extra*step}
function compareForGroup(format,n){const v1=variantFor(format,"1 Person");const v2=variantFor(format,"2 Persons");const base=variantFor(format,"3+ Group");let step=0;if(v1&&v2&&v2.compare&&v1.compare&&v2.compare>v1.compare) step=v2.compare-v1.compare;else if(base&&base.compare) step=Math.round(base.compare/3);const extra=Math.max(0,n-3);return (base&&base.compare?base.compare:0) + extra*step}
function unitPriceWithMode(){let base=state.variant.price;if(isGroup()){const format=state.selected[0];const n=Math.min(10, Math.max(3, parseInt(state.groupCount||3,10)));base=priceForGroup(format,n)}return base}
function totals(){
  const unit=unitPriceWithMode();
  const total=unit*state.qty;
  let cmpUnit=0;
  if(isGroup()){
    const format=state.selected[0];
    const n=Math.min(10, Math.max(3, parseInt(state.groupCount||3,10)));
    cmpUnit=compareForGroup(format,n);
  } else if(state.variant && state.variant.compare && state.variant.compare>state.variant.price){
    cmpUnit=state.variant.compare;
  }
  return { unit, total, cmpUnit };
}
function updatePriceUI(){
  const t=totals();
  // Show unit price for current variant selection
  priceEl.textContent=fmt(t.unit);
  if(mPrice) mPrice.textContent=fmt(t.unit);
  if(t.cmpUnit && t.cmpUnit>t.unit){ compareEl.textContent=fmt(t.cmpUnit); compareEl.style.display="inline"; }
  else { compareEl.textContent=""; compareEl.style.display="none"; }
  updateSheetSubtotal();
  syncCartButtons();
  try { document.dispatchEvent(new CustomEvent('wow:price', { detail: { unit: t.unit, compare: t.cmpUnit } })); } catch(e) {}
}
function updateSheetSubtotal(){if(!sheetSubtotal) return;const t=totals();sheetSubtotal.textContent=`Subtotal: ${fmt(t.total)}`}
function currentVariantLabel(){
  try {
    const selections = Array.isArray(state.selected) ? state.selected : [];
    const parts = selections.map(v => String(v||'').trim()).filter(Boolean);
    if(isGroup()){
      parts.push(`${state.groupCount || 3} people`);
    }
    return parts.join(' • ');
  } catch(_e) { return ''; }
}
function bookingVariantLabel(){
  return currentVariantLabel() || (state.variant && state.variant.title ? String(state.variant.title).trim() : '');
}
function syncCartButtons(){
  const variantId = state.variant && state.variant.id ? state.variant.id : null;
  const productId = BASE_PRODUCT_ID || (product?.id ?? '');
  const qtyVal = Math.max(1, Number(state.qty||1)||1);
  const pricePennies = unitPriceWithMode();
  const pricePounds = (pricePennies >= 0 ? (pricePennies/100).toFixed(2) : '0.00');
  const variantLabel = bookingVariantLabel();
  [addBtn, mobileAdd].forEach(function(btn){
    if(!btn) return;
    btn.dataset.id = String(productId);
    btn.dataset.productId = String(productId);
    btn.dataset.title = BASE_PRODUCT_TITLE || '';
    btn.dataset.price = pricePounds;
    btn.dataset.image = BASE_PRODUCT_IMAGE || '';
    btn.dataset.url = BASE_PRODUCT_URL || window.location.pathname;
    btn.dataset.qty = String(qtyVal);
    if(btn===addBtn){
      btn.classList.add('js-add-to-cart');
    }
    if(variantId){ btn.dataset.variantId = String(variantId); }
    else { delete btn.dataset.variantId; }
    if(variantLabel){ btn.dataset.variantLabel = variantLabel; } else { delete btn.dataset.variantLabel; }
  });
  if(buyNow){
    buyNow.dataset.id = String(productId);
    buyNow.dataset.productId = String(productId);
    buyNow.dataset.title = BASE_PRODUCT_TITLE || '';
    buyNow.dataset.price = pricePounds;
    buyNow.dataset.image = BASE_PRODUCT_IMAGE || '';
    buyNow.dataset.url = BASE_PRODUCT_URL || window.location.pathname;
    buyNow.dataset.qty = String(qtyVal);
    if(variantId){ buyNow.dataset.variantId = String(variantId); }
    else { delete buyNow.dataset.variantId; }
    if(variantLabel){ buyNow.dataset.variantLabel = variantLabel; }
    else { delete buyNow.dataset.variantLabel; }
  }
}
function updateVariant(){
  state.variant=findVariant();
  const show=isGroup();
  if(groupRange){
    if(show){groupRange.style.display="block";clampGroupCount();}
    else{groupRange.style.display="none";state.groupCount=3;if(groupCount) groupCount.value=3;}
  }
  addBtn.disabled=!state.variant || !state.variant.available;
  addBtn.textContent=(state.variant && state.variant.available)?PRIMARY_ACTION_LABEL:"Sold out";
  if (buyNow) buyNow.textContent = SECONDARY_ACTION_LABEL;
  if (mobileAdd) mobileAdd.textContent = MOBILE_ACTION_LABEL;
  updatePriceUI();
  try { syncFormatUI(); } catch(e) {}
  // Notify helper of current selection + variant id
  try{ document.dispatchEvent(new CustomEvent('wow:selected', { detail: { options: (state.selected||[]), variantId: state.variant ? state.variant.id : null } })); }catch(e){}
  syncVariantQueryParam();
  if (IS_LIVE_BOOKING) {
    bookingState.loaded = false;
    bookingState.cacheKey = '';
    loadBookingContext(false).then(syncAvailabilityCopy).catch(()=>{});
  }
}
function clampGroupCount(){let v=parseInt(groupCount.value||"3",10);if(isNaN(v)||v<3) v=3; if(v>10) v=10;groupCount.value=v; state.groupCount=v}
function stepGroup(delta){let v=parseInt(groupCount.value||"3",10); if(isNaN(v)) v=3;v=Math.min(10,Math.max(3,v+delta));groupCount.value=v; state.groupCount=v; updatePriceUI()}
function clampGroupCountSheet(){let v=parseInt(groupCountSheet.value||"3",10);if(isNaN(v)||v<3) v=3; if(v>10) v=10;groupCountSheet.value=v; state.groupCount=v}
function stepGroupSheet(delta){let v=parseInt(groupCountSheet.value||"3",10); if(isNaN(v)) v=3;v=Math.min(10,Math.max(3,v+delta));groupCountSheet.value=v; state.groupCount=v; updatePriceUI()}
function wireQty(){if(inc && dec && qty){inc.addEventListener("click",()=>{qty.value=Math.max(1,parseInt(qty.value||"1",10)+1);state.qty=parseInt(qty.value,10);updatePriceUI()});dec.addEventListener("click",()=>{qty.value=Math.max(1,parseInt(qty.value||"1",10)-1);state.qty=parseInt(qty.value,10);updatePriceUI()});qty.addEventListener("input",()=>{qty.value=qty.value.replace(/[^0-9]/g,"")||1;state.qty=parseInt(qty.value,10);updatePriceUI()})}if(groupInc && groupDec && groupCount){groupInc.addEventListener("click",()=>stepGroup(1));groupDec.addEventListener("click",()=>stepGroup(-1));groupCount.addEventListener("input",()=>{groupCount.value=groupCount.value.replace(/[^0-9]/g,"");clampGroupCount();updatePriceUI()});groupCount.addEventListener("blur",()=>{clampGroupCount();updatePriceUI()})}if(groupIncSheet && groupDecSheet && groupCountSheet){groupIncSheet.addEventListener("click",()=>stepGroupSheet(1));groupDecSheet.addEventListener("click",()=>stepGroupSheet(-1));groupCountSheet.addEventListener("input",()=>{groupCountSheet.value=groupCountSheet.value.replace(/[^0-9]/g,"");clampGroupCountSheet();updatePriceUI()});groupCountSheet.addEventListener("blur",()=>{clampGroupCountSheet();updatePriceUI()})}}
function isMobile(){return window.matchMedia('(max-width: 991px)').matches}
function triggerGlobalAdd(btn){
  if(!btn) return;
  if(typeof window !== 'undefined' && typeof window.WOW_addToCart === 'function'){
    try { window.WOW_addToCart(btn); return; } catch(_e){}
  }
  try {
    const evt = new MouseEvent('click', { bubbles:true, cancelable:true });
    btn.dispatchEvent(evt);
  } catch(_err) {}
}
function goToCart(){
  try { window.location.assign('/cart'); }
  catch(_e){ window.location.href = '/cart'; }
}
function doAddToCart(opts){
  try { syncCartButtons(); } catch(_e){}
  triggerGlobalAdd(addBtn);
  const toast = new bootstrap.Toast(toastEl); toast.show();
  if(opts && opts.redirect){ goToCart(); }
}
function wireCTA(){
  if(addBtn){
    addBtn.addEventListener('click',()=>{
      try{ new bootstrap.Toast(toastEl).show(); }catch(_e){}
    });
  }
}
function openConfigSheet(intent){
  sheetIntent = intent || 'add';
  buildOptionsInto(sheetOptions);
  groupRangeSheet.style.display=isGroup()?'block':'none';
  groupCountSheet.value=String(state.groupCount||3);
  if(bookingChoice.value==='now'){
    sheetBookLater?.classList.remove('active');
    sheetBookNow?.classList.add('active');
  }else{
    sheetBookLater?.classList.add('active');
    sheetBookNow?.classList.remove('active');
  }
  updateSheetSubtotal();
  configModal.show();
}
mobileAdd?.addEventListener('click',()=>{ openConfigSheet('buy'); });

async function continueBookNow(){
  bookingIntent = 'buy';

  if (IS_LIVE_BOOKING) {
    const loaded = await loadBookingContext(true);
    if (loaded && hasLiveAvailability()) {
      syncCalendarToAvailability(true);
      bookingModal.show();
      return;
    }
  }

  // Flexible/no-slot offerings never get a fake calendar.
  // Preserve the existing order flow and let the practitioner/date be confirmed later.
  doAddToCart({ redirect:true });
}

if(buyNow){
  buyNow.addEventListener('click',async function(e){
    e.preventDefault();
    if(isMobile()){
      openConfigSheet('buy');
      return;
    }
    buyNow.disabled = true;
    try {
      await continueBookNow();
    } finally {
      buyNow.disabled = false;
    }
  });
}
const calendarState={viewYear:new Date().getFullYear(),viewMonth:new Date().getMonth(),selectedDate:null,selectedTime:null,tz:Intl.DateTimeFormat().resolvedOptions().timeZone||'Europe/London'};
function exitMobileTimesMode(){bookingModalContent.classList.remove('mobile-times')}
function clearBookingSelection(){bookingChoice.value='later';preferredDateValue.value='';preferredTimeValue.value='';preferredTZValue.value='';bookingSelectionRow.style.display='none';bookingSelectionText.textContent='';calendarState.selectedDate=null;calendarState.selectedTime=null;bookingSummary.textContent='No date selected.';confirmBooking.disabled=true;modalHint.textContent='Pick a date, then choose a time.';btnBookLater?.classList.add('active');btnBookNow?.classList.remove('active');exitMobileTimesMode();stopUserHold()}
btnBookLater?.addEventListener('click',clearBookingSelection);btnBookNow?.addEventListener('click',()=>{btnBookNow.classList.add('active');btnBookLater.classList.remove('active');bookingChoice.value='now';bookingModal.show()});changeBooking?.addEventListener('click',(e)=>{e.preventDefault();btnBookNow.click()});
btnBookLaterFlexible?.addEventListener('click',()=>{
  bookingChoice.value='later';
  btnBookLaterFlexible.classList.add('active','btn-outline-success');
  btnBookLaterFlexible.classList.remove('btn-outline-secondary');
  btnRequestTime?.classList.remove('active','btn-outline-success');
  btnRequestTime?.classList.add('btn-outline-secondary');
  if (preferredTimeBox) preferredTimeBox.classList.add('d-none');
});
btnRequestTime?.addEventListener('click',()=>{
  bookingChoice.value='preferred';
  btnRequestTime.classList.add('active','btn-outline-secondary');
  btnRequestTime.classList.remove('btn-outline-success');
  btnBookLaterFlexible?.classList.remove('active','btn-outline-secondary');
  btnBookLaterFlexible?.classList.add('btn-outline-success');
  if (preferredTimeBox) preferredTimeBox.classList.remove('d-none');
});
preferredTimeNote?.addEventListener('input',()=>{ if (preferredTimeNoteValue) preferredTimeNoteValue.value = preferredTimeNote.value || ''; });
sheetBookLater?.addEventListener('click',()=>{sheetBookLater.classList.add('active');sheetBookNow.classList.remove('active');bookingChoice.value='later';});
sheetBookNow?.addEventListener('click',()=>{sheetBookNow.classList.add('active');sheetBookLater.classList.remove('active');bookingChoice.value='now';});
sheetBtnBookLaterFlexible?.addEventListener('click',()=>{
  bookingChoice.value='later';
  sheetBtnBookLaterFlexible.classList.add('active','btn-outline-success');
  sheetBtnBookLaterFlexible.classList.remove('btn-outline-secondary');
  sheetBtnRequestTime?.classList.remove('active','btn-outline-success');
  sheetBtnRequestTime?.classList.add('btn-outline-secondary');
  if (sheetPreferredTimeBox) sheetPreferredTimeBox.classList.add('d-none');
});
sheetBtnRequestTime?.addEventListener('click',()=>{
  bookingChoice.value='preferred';
  sheetBtnRequestTime.classList.add('active','btn-outline-secondary');
  sheetBtnRequestTime.classList.remove('btn-outline-success');
  sheetBtnBookLaterFlexible?.classList.remove('active','btn-outline-secondary');
  sheetBtnBookLaterFlexible?.classList.add('btn-outline-success');
  if (sheetPreferredTimeBox) sheetPreferredTimeBox.classList.remove('d-none');
});
sheetPreferredTimeNote?.addEventListener('input',()=>{ if (preferredTimeNoteValue) preferredTimeNoteValue.value = sheetPreferredTimeNote.value || ''; });
sheetConfirm?.addEventListener('click',async()=>{
  const intent = sheetIntent;
  configModal.hide();

  if (intent === 'buy' && IS_LIVE_BOOKING) {
    const loaded = await loadBookingContext(true);
    if (loaded && hasLiveAvailability()) {
      bookingIntent = 'buy';
      syncCalendarToAvailability(true);
      window.setTimeout(()=>bookingModal.show(), 180);
      sheetIntent='add';
      return;
    }
  }

  doAddToCart({ redirect: intent === 'buy' });
  sheetIntent='add';
});
function generateSlotsForDate(_d){ return []; }
function renderDayNames(){calDayNames.innerHTML='';['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(n=>{const el=document.createElement('div');el.className='cal-dayname text-center';el.textContent=n;calDayNames.appendChild(el)})}
function daysInMonth(y,m){return new Date(y,m+1,0).getDate()}function firstWeekday(y,m){const js=new Date(y,m,1).getDay();return (js+6)%7}function isSameDate(a,b){return a&&b&&a.getFullYear()===b.getFullYear()&&a.getMonth()===b.getMonth()&&a.getDate()===b.getDate()}
function renderCalendar(){const y=calendarState.viewYear,m=calendarState.viewMonth;calMonthLabel.textContent=new Date(y,m,1).toLocaleDateString(undefined,{month:'long',year:'numeric'});calGrid.innerHTML='';const lead=firstWeekday(y,m),total=daysInMonth(y,m);if(!calDayNames.children.length) renderDayNames();const today=new Date(); today.setHours(0,0,0,0);for(let i=0;i<lead;i++){const d=document.createElement('div');d.className='cal-cell';d.setAttribute('aria-disabled','true');calGrid.appendChild(d)}for(let day=1;day<=total;day++){const cellDate=new Date(y,m,day); cellDate.setHours(0,0,0,0);const btn=document.createElement('button'); btn.type='button'; btn.className='cal-cell'; btn.textContent=String(day);const past=cellDate<today; const slots=bookingState.loaded ? bookingSlotsForDate(cellDate) : []; const noAvailability=slots.length===0; if(past||noAvailability){btn.disabled=true;btn.setAttribute('aria-disabled','true');btn.title=past?'Past date':'No availability'}btn.addEventListener('click',()=>{if(past||noAvailability) return;calendarState.selectedDate=cellDate; calendarState.selectedTime=null;[...calGrid.querySelectorAll('.cal-cell')].forEach(c=>c.classList.remove('active'));btn.classList.add('active'); renderSlots(); updateSummary(); confirmBooking.disabled=true; modalHint.textContent='Choose a time.'; if (window.matchMedia('(max-width: 991px)').matches) bookingModalContent.classList.add('mobile-times');});if(isSameDate(cellDate,calendarState.selectedDate)&&!past&&!noAvailability) btn.classList.add('active');calGrid.appendChild(btn)}}
function validReserved(dayObj, timeKey){const res = dayObj.reserved[timeKey]; if(!res) return null; const now = new Date(); if(now >= res.until){delete dayObj.reserved[timeKey]; return null;} return res }
function mmss(ms){const total=Math.max(0,Math.ceil(ms/1000));const m=String(Math.floor(total/60)).padStart(2,'0');const s=String(total%60).padStart(2,'0');return `${m}:${s}`}
let userHoldInterval=null,userHoldUntil=null,userHoldKey=null;
function startUserHold(dateObj,timeStr){stopUserHold();const k=dateKey(new Date(Date.UTC(dateObj.getFullYear(),dateObj.getMonth(),dateObj.getDate())));const dayObj=ensureDay(new Date(Date.UTC(dateObj.getFullYear(),dateObj.getMonth(),dateObj.getDate())));userHoldUntil=new Date(Date.now()+HOLD_MINUTES*60000);dayObj.reserved[timeStr]={until:userHoldUntil};userHoldKey={k,timeStr};document.getElementById('holdTimer').style.display='block';const banner=document.getElementById('pillHoldBanner');banner.style.display='flex';banner.classList.add('hourglass-active');banner.querySelector('i')?.classList.add('hourglass-spin');tickUserHold();userHoldInterval=setInterval(tickUserHold,1000);renderSlots()}
function stopUserHold(){document.getElementById('holdTimer').style.display='none';document.getElementById('holdCountdown').textContent='10:00';const banner=document.getElementById('pillHoldBanner');banner.style.display='none';banner.classList.remove('hourglass-active');banner.querySelector('i')?.classList.remove('hourglass-spin');document.getElementById('pillHoldCountdown').textContent='10:00';if(userHoldInterval){clearInterval(userHoldInterval);userHoldInterval=null}if(userHoldKey){const d = bookings[userHoldKey.k];if(d && d.reserved[userHoldKey.timeStr]){delete d.reserved[userHoldKey.timeStr]}userHoldKey=null}userHoldUntil=null}
function tickUserHold(){if(!userHoldUntil){document.getElementById('holdTimer').style.display='none';const banner=document.getElementById('pillHoldBanner');banner.style.display='none';banner.classList.remove('hourglass-active');banner.querySelector('i')?.classList.remove('hourglass-spin');return}const remaining=userHoldUntil-Date.now();if(remaining<=0){stopUserHold();calendarState.selectedTime=null;updateSummary();confirmBooking.disabled=true;modalHint.textContent='Hold expired — please choose another time.';renderSlots();return}const t=mmss(remaining);document.getElementById('holdCountdown').textContent=t;document.getElementById('pillHoldCountdown').textContent=t}
function refreshReservedCountdowns(){const spans=[...document.querySelectorAll('button.slot.reserved span[data-until]')];if(!spans.length) return;const now=new Date();spans.forEach(s=>{const until=new Date(s.dataset.until);const remaining=until-now;if(remaining<=0){renderSlots()}else{s.textContent=`(${mmss(remaining)}) reserved`}})}
function renderSlots(){slotList.innerHTML='';const d=calendarState.selectedDate;if(!d){slotList.innerHTML='<div class="text-secondary small">Select a date to see available times.</div>';return}const slots=bookingState.loaded ? bookingSlotsForDate(d) : []; if(!slots.length){slotList.innerHTML='<div class="text-secondary small">No times available for this date.</div>';return;}const dayObj=null;slots.forEach(s=>{const slotStart=String(s?.start||s||''); if(dayObj && dayObj.booked.has(slotStart)) return;const reservedUntil=IS_LIVE_BOOKING&&bookingState.loaded?bookingHoldUntilForSlot(d,slotStart):(validReserved(dayObj,slotStart)?.until || null);const b=document.createElement('button');b.type='button';b.className='slot';b.textContent=slotStart;if(reservedUntil){b.classList.add('reserved');b.disabled=true;const span=document.createElement('span');span.className='ms-1 small';span.dataset.until=reservedUntil;span.textContent='(reserved)';b.appendChild(span)}else{b.addEventListener('click',()=>{[...slotList.querySelectorAll('.slot')].forEach(x=>x.classList.remove('active'));b.classList.add('active');calendarState.selectedTime=slotStart;updateSummary();confirmBooking.disabled=false;modalHint.textContent='Nice choice — we’ll hold this for 10 minutes.';startUserHold(d,slotStart);})}if(calendarState.selectedTime===slotStart) b.classList.add('active');slotList.appendChild(b)});refreshReservedCountdowns()}
function populateTimezones(){const tzs=['Europe/London','Europe/Dublin','Europe/Lisbon','Europe/Paris','Europe/Berlin','UTC','America/New_York','America/Chicago','America/Denver','America/Los_Angeles','Asia/Dubai','Asia/Kolkata','Asia/Singapore','Australia/Sydney'];tzSelect.innerHTML='';tzs.forEach(tz=>{const o=document.createElement('option');o.value=tz;o.textContent=tz;if(tz===calendarState.tz)o.selected=true;tzSelect.appendChild(o)});tzCurrent.textContent=calendarState.tz;if(IS_LIVE_BOOKING&&bookingState.loaded){tzSelect.value=calendarState.tz;tzSelect.disabled=true;return}tzSelect.addEventListener('change',()=>{calendarState.tz=tzSelect.value;tzCurrent.textContent=calendarState.tz;updateSummary()})}
function updateSummary(){if(calendarState.selectedDate && calendarState.selectedTime){const ds=calendarState.selectedDate.toLocaleDateString(undefined,{weekday:'long',day:'numeric',month:'long',year:'numeric'});bookingSummary.innerHTML=`<div class="fw-semibold">${ds}</div><div>${calendarState.selectedTime} (${calendarState.tz})</div>`}else if(calendarState.selectedDate){const ds=calendarState.selectedDate.toLocaleDateString(undefined,{weekday:'long',day:'numeric',month:'long',year:'numeric'});bookingSummary.textContent=`${ds} — select a time`}else bookingSummary.textContent='No date selected.'}
calPrev.addEventListener('click',()=>{calendarState.viewMonth--; if(calendarState.viewMonth<0){calendarState.viewMonth=11;calendarState.viewYear--} renderCalendar()});calNext.addEventListener('click',()=>{calendarState.viewMonth++; if(calendarState.viewMonth>11){calendarState.viewMonth=0;calendarState.viewYear++} renderCalendar()});mobileBack?.addEventListener('click',()=>{bookingModalContent.classList.remove('mobile-times')});
confirmBooking.addEventListener('click',()=>{
  if(!(calendarState.selectedDate && calendarState.selectedTime)) return;
  preferredDateValue.value=bookingDateKey(calendarState.selectedDate);
  preferredTimeValue.value=calendarState.selectedTime;
  preferredTZValue.value=calendarState.tz;
  bookingChoice.value='now';
  const ds=calendarState.selectedDate.toLocaleDateString(undefined,{weekday:'short',day:'numeric',month:'short',year:'numeric'});
  bookingSelectionText.textContent=`${ds} • ${calendarState.selectedTime}`;
  bookingSelectionRow.style.display='inline-block';
  bookingModal.hide();

  if (bookingIntent === 'buy') {
    bookingIntent = 'select';
    window.setTimeout(()=>doAddToCart({ redirect:true }), 160);
  }
});
bookingModalEl.addEventListener('shown.bs.modal',async()=>{
  bookingModalContent.classList.remove('mobile-times');
  if(!calDayNames.children.length){
    populateTimezones();
  }

  slotList.innerHTML='<div class="text-secondary small">Loading availability…</div>';
  const loaded = IS_LIVE_BOOKING ? await loadBookingContext(false) : false;

  if (!loaded || !hasLiveAvailability()) {
    if(modalHint) modalHint.textContent='No live availability is currently configured.';
    calendarState.selectedDate=null;
    calendarState.selectedTime=null;
    renderCalendar();
    renderSlots();
    updateSummary();
    return;
  }

  syncCalendarToAvailability(true);
  renderCalendar();
  renderSlots();
  updateSummary();
  confirmBooking.disabled=true;
  if(modalHint) modalHint.textContent='Choose a time for the first available date.';
});
configModalEl?.addEventListener('shown.bs.modal',async()=>{await loadBookingContext(false);syncBookingMode();});
// (mode note removed)
function wireCTA(){addBtn.addEventListener("click",e=>{e.preventDefault();const t=new bootstrap.Toast(toastEl);t.show()});buyNow.addEventListener("click",e=>{e.preventDefault();const t=new bootstrap.Toast(toastEl);t.show()})}
function init(){
  renderStars();
  buildFormatBlock();
  applyVariantFromQuery();
  buildOptions();
  updateVariant();
  wireQty();
  wireCTA();
  updatePriceUI();
  window.addEventListener('resize',()=>{ bookingModalContent.classList.remove('mobile-times'); });
  const load = loadBookingContext(false).then(syncAvailabilityCopy).catch(()=>{});
  if (IS_EVENT_OFFERING && IS_LIVE_BOOKING) {
    load.finally(() => {
      try {
        if (hasLiveAvailability()) {
          syncCalendarToAvailability(true);
          bookingModal.show();
        }
      } catch(_e) {}
    });
  }
}
init();

// Listen for external variant selection (from Product Data Helper)
try {
  document.addEventListener('wow:selectVariant', function(ev){
    try {
      var from = ev?.detail?.options; if(!Array.isArray(from) || !from.length) return;
      // Map provided options to existing values; for locations, use loose match to choose the exact value
      for (var i=0; i<from.length; i++) {
        var want = String(from[i]||'');
        var values = (product.options[i] && product.options[i].values) ? product.options[i].values : [];
        var locIdx = (__locIdxCache!=null)?__locIdxCache:findLocationIndex();
        var chosen;
        if (i===locIdx) {
          if (strNorm(want)==='online') {
            chosen = values.find(function(v){ return strNorm(v)==='online'; });
          } else {
            // pick closest by overlap
            var best = null, bestScore = -1;
            values.forEach(function(v){ var sc = overlap(v, want); if (sc>bestScore) { best=v; bestScore=sc; } });
            chosen = best || values[0] || want;
          }
        } else {
          chosen = values.find(function(v){ return strNorm(v)===strNorm(want); }) || values[0] || want;
        }
        state.selected[i] = chosen;
      }
      // Sync option rows UI
      for (var j=0; j<(product.options||[]).length; j++){ syncOptionAria(j); }
      // Sync format pills and update
      try { syncFormatUI(); } catch(e) {}
      updateVariant();
      // Rebuild options so Sessions dropdown reflects external change
      try { buildOptions(); } catch(e) {}
    } catch(e) {}
  });

  // External location set: pick closest location option, update selection
  document.addEventListener('wow:setLocation', function(ev){
    try{
      var locIdx = (__locIdxCache!=null)?__locIdxCache:findLocationIndex(); if(locIdx==null || locIdx<0) return;
      var want = String(ev?.detail?.location||'');
      var values = (product.options[locIdx] && product.options[locIdx].values) ? product.options[locIdx].values : [];
      var choice;
      if (strNorm(want)==='online') { choice = values.find(function(v){ return strNorm(v)==='online' }) || values[0] || want; }
      else {
        var best=null, bestScore=-1; values.forEach(function(v){ var sc=overlap(v,want); if(sc>bestScore){ best=v; bestScore=sc; } });
        choice = best || values[0] || want;
      }
      state.selected[locIdx] = choice;
      syncOptionAria(locIdx);
      try{ syncFormatUI(); }catch(e){}
      updateVariant();
      // Also emit wow:selected explicitly for downstream listeners
      try{ document.dispatchEvent(new CustomEvent('wow:selected', { detail: { options: (state.selected||[]), variantId: state.variant ? state.variant.id : null } })); }catch(e){}
    }catch(e){}
  });
} catch(e) {}
</script>
