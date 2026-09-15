@php
    $eventProduct = $p ?? [];
    $eventProductId = $eventProduct['id'] ?? ($product['id'] ?? null);
    $eventTitle = trim((string) ($eventProduct['title'] ?? 'Event'));
    $eventImage = trim((string) ($eventProduct['image'] ?? ($eventProduct['images'][0] ?? '')));
    $eventUrl = url()->current();

    $eventVariantRows = [];
    foreach (array_values($variants ?? []) as $index => $variant) {
        if (! is_array($variant)) {
            continue;
        }

        $label = trim((string) ($variant['options'][0] ?? $variant['title'] ?? 'Ticket'));
        $price = is_numeric($variant['price'] ?? null) ? (float) $variant['price'] : 0.0;

        $eventVariantRows[] = [
            'index' => $index,
            'id' => (string) ($variant['id'] ?? ('event_ticket_' . $index)),
            'label' => $label !== '' ? $label : 'Ticket',
            'price' => $price,
            'price_formatted' => number_format($price, 2, '.', ''),
            'available' => (bool) ($variant['available'] ?? true),
            'note' => $index === 0 || stripos($label, 'full event') !== false || stripos($label, 'event ticket') !== false
                ? 'Covers all event dates'
                : 'Single-day ticket',
        ];
    }

    if (empty($eventVariantRows)) {
        $fallbackPrice = is_numeric($eventProduct['price'] ?? null) ? (float) $eventProduct['price'] : 0.0;
        $eventVariantRows[] = [
            'index' => 0,
            'id' => 'event_ticket',
            'label' => 'Event ticket',
            'price' => $fallbackPrice,
            'price_formatted' => number_format($fallbackPrice, 2, '.', ''),
            'available' => true,
            'note' => 'Covers all event dates',
        ];
    }

    $eventStartDate = trim((string) ($eventProduct['start_date'] ?? ''));
    $eventStartTime = trim((string) ($eventProduct['start_time'] ?? ''));
    $eventEndDate = trim((string) ($eventProduct['end_date'] ?? ''));
    $eventEndTime = trim((string) ($eventProduct['end_time'] ?? ''));
    $eventRangeText = '';
    $eventCalendarMonths = [];
    $eventDaySeries = [];

    if ($eventStartDate !== '') {
        try {
            $eventStart = \Carbon\Carbon::parse($eventStartDate . ($eventStartTime !== '' ? ' ' . $eventStartTime : ''));
            $eventEnd = $eventEndDate !== ''
                ? \Carbon\Carbon::parse($eventEndDate . ($eventEndTime !== '' ? ' ' . $eventEndTime : ''))
                : $eventStart->copy();

            $startLabel = $eventStart->format('D j M' . ($eventStartTime !== '' ? ', g:i A' : ''));
            if ($eventEnd && $eventEnd->toDateString() !== $eventStart->toDateString()) {
                $endLabel = $eventEnd->format('D j M' . ($eventEndTime !== '' ? ', g:i A' : ''));
                $eventRangeText = $startLabel . ' – ' . $endLabel;
            } elseif ($eventEnd && $eventEndTime !== '' && $eventEndTime !== $eventStartTime) {
                $eventRangeText = $startLabel . ' – ' . $eventEnd->format('g:i A');
            } else {
                $eventRangeText = $startLabel;
            }

            $cursor = $eventStart->copy()->startOfDay();
            $limit = $eventEnd->copy()->startOfDay();
            $dayIndex = 1;

            while ($cursor->lte($limit) && $dayIndex <= 31) {
                $eventDaySeries[] = [
                    'index' => $dayIndex,
                    'date' => $cursor->toDateString(),
                    'label' => $cursor->format('D j M'),
                    'short_label' => $cursor->format('D j'),
                ];

                $cursor->addDay();
                $dayIndex++;
            }

            $eventDateTicketMap = [];
            foreach ($eventDaySeries as $seriesIndex => $day) {
                $dayDate = (string) ($day['date'] ?? '');
                if ($dayDate === '') {
                    continue;
                }

                $eventDateTicketMap[$dayDate] = min(count($eventVariantRows) - 1, $seriesIndex + 1);
            }

            $monthCursor = $eventStart->copy()->startOfMonth();
            $monthLimit = $eventEnd->copy()->startOfMonth();
            while ($monthCursor->lte($monthLimit)) {
                $monthStart = $monthCursor->copy()->startOfMonth();
                $daysInMonth = $monthStart->daysInMonth;
                $lead = (int) $monthStart->dayOfWeekIso - 1;
                $monthDays = [];

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = $monthStart->copy()->day($day)->startOfDay();
                    $dateString = $date->toDateString();
                    $isActive = $date->betweenIncluded($eventStart->copy()->startOfDay(), $eventEnd->copy()->endOfDay());
                    $ticketIndex = $isActive ? ($eventDateTicketMap[$dateString] ?? null) : null;

                    $monthDays[] = [
                        'day' => $day,
                        'date' => $dateString,
                        'active' => $isActive,
                        'ticket_index' => $ticketIndex,
                    ];
                }

                $eventCalendarMonths[] = [
                    'label' => $monthStart->format('F Y'),
                    'lead' => $lead,
                    'days' => $monthDays,
                ];

                $monthCursor->addMonthNoOverflow();
            }
        } catch (\Throwable $e) {
            $eventRangeText = trim(implode(' ', array_filter([$eventStartDate, $eventStartTime, $eventEndDate, $eventEndTime])));
        }
    }

    if (empty($eventDaySeries)) {
        $today = now();
        $eventDaySeries[] = [
            'index' => 1,
            'date' => $today->toDateString(),
            'label' => $today->format('D j M'),
            'short_label' => $today->format('D j'),
        ];
    }

    $eventDateTicketMap = $eventDateTicketMap ?? [];
    if (empty($eventDateTicketMap)) {
        foreach ($eventDaySeries as $seriesIndex => $day) {
            $dayDate = (string) ($day['date'] ?? '');
            if ($dayDate === '') {
                continue;
            }

            $eventDateTicketMap[$dayDate] = min(count($eventVariantRows) - 1, $seriesIndex + 1);
        }
    }

    if (empty($eventCalendarMonths)) {
        $monthStart = \Carbon\Carbon::parse($eventDaySeries[0]['date'])->startOfMonth();
        $daysInMonth = $monthStart->daysInMonth;
        $lead = (int) $monthStart->dayOfWeekIso - 1;
        $monthDays = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $monthStart->copy()->day($day)->startOfDay();
            $dateString = $date->toDateString();
            $isActive = array_key_exists($dateString, $eventDateTicketMap);
            $ticketIndex = $isActive ? $eventDateTicketMap[$dateString] : null;

            $monthDays[] = [
                'day' => $day,
                'date' => $dateString,
                'active' => $isActive,
                'ticket_index' => $ticketIndex,
            ];
        }

        $eventCalendarMonths[] = [
            'label' => $monthStart->format('F Y'),
            'lead' => $lead,
            'days' => $monthDays,
        ];
    }

    $eventFullRangeLabel = $eventRangeText !== '' ? $eventRangeText : 'Covers all event dates';

    foreach ($eventVariantRows as $variantIndex => &$variantRow) {
        $variantRow['date_label'] = $variantIndex === 0
            ? $eventFullRangeLabel
            : ($eventDaySeries[$variantIndex - 1]['label'] ?? $eventFullRangeLabel);

        if ($variantIndex === 0 || stripos($variantRow['label'], 'group') !== false || stripos($variantRow['label'], 'full') !== false) {
            $variantRow['dates'] = array_values(array_map(fn ($day) => $day['date'] ?? '', $eventDaySeries));
        } else {
            $variantRow['dates'] = [$eventDaySeries[$variantIndex - 1]['date'] ?? ($eventDaySeries[0]['date'] ?? '')];
        }
    }
    unset($variantRow);

    $eventSelectedIndex = 0;
    $eventSelectedTicket = $eventVariantRows[$eventSelectedIndex] ?? $eventVariantRows[0];
    $eventSelectedDateText = $eventSelectedTicket['date_label'] ?? $eventFullRangeLabel;
    $eventSelectedDates = $eventSelectedTicket['dates'] ?? [];
    $eventQty = 1;
    $eventCapacity = (int) ($eventProduct['capacity'] ?? data_get($eventProduct, 'event.capacity', 1000));
    $eventCapacity = max(1, min(1000, $eventCapacity > 0 ? $eventCapacity : 1000));
    $eventHasReviews = (($eventProduct['review_count'] ?? 0) > 0) && ($eventProduct['rating'] ?? null) !== null;
    $eventRating = $eventProduct['rating'] ?? null;
    $eventReviewCount = $eventProduct['review_count'] ?? 0;

    $calendarNote = 'Choose the full weekend or a day ticket below.';
    if (count($eventDaySeries) >= 2) {
        $shortLabels = array_values(array_filter([
            $eventDaySeries[0]['short_label'] ?? null,
            $eventDaySeries[1]['short_label'] ?? null,
        ]));
        if (count($shortLabels) === 2) {
            $calendarNote = 'Choose ' . $shortLabels[0] . ', ' . $shortLabels[1] . ', or the full weekend';
        }
    }

    $eventButtons = [
        'title' => $eventTitle,
        'image' => $eventImage,
        'url' => $eventUrl,
        'productId' => $eventProductId,
    ];
@endphp

@if($showBookingUi ?? true)

<style>
    .modal-fields-slot {
        display: contents;
    }
</style>

<aside class="ticket-panel" id="tickets" aria-label="Event booking">
    <div class="ticket-top">
        <div>
            <div class="price-label">Selected ticket</div>
            <div class="price" id="panelPrice">£{{ number_format((float) (($eventSelectedTicket['price'] ?? 0) * $eventQty), 2, '.', '') }}</div>
        </div>
        <span class="status-dot">Available</span>
    </div>

    <button class="btn checkout-button mobile-panel-pick" type="button" data-open-booking>
        Pick dates
    </button>

    <div class="booking-fields" id="bookingFields">
        <label class="ticket-label">Select date</label>

        @php $eventCalendarMonth = $eventCalendarMonths[0] ?? null; @endphp
        @if($eventCalendarMonth)
            <div class="calendar-box" aria-label="Open event date picker">
                <div class="calendar-head">
                    <div>
                        <div class="calendar-title">{{ $eventCalendarMonth['label'] }}</div>
                        <div class="calendar-note">{{ $calendarNote }}</div>
                    </div>
                </div>

                <div class="calendar-grid" id="calendarGrid">
                    <div class="calendar-day-name">Mon</div>
                    <div class="calendar-day-name">Tue</div>
                    <div class="calendar-day-name">Wed</div>
                    <div class="calendar-day-name">Thu</div>
                    <div class="calendar-day-name">Fri</div>
                    <div class="calendar-day-name">Sat</div>
                    <div class="calendar-day-name">Sun</div>

                    @for($i = 0; $i < (int) ($eventCalendarMonth['lead'] ?? 0); $i++)
                        <span class="calendar-date is-disabled" aria-hidden="true"></span>
                    @endfor

                    @foreach($eventCalendarMonth['days'] as $day)
                        @php
                            $ticketIndex = $day['ticket_index'];
                            $isSelectedDate = in_array($day['date'], $eventSelectedDates, true);
                            $dateClass = 'calendar-date';
                            if (! $day['active']) {
                                $dateClass .= ' is-disabled';
                            } elseif ($isSelectedDate && count($eventSelectedDates) > 1) {
                                $dateClass .= ' is-range';
                            } elseif ($isSelectedDate && count($eventSelectedDates) === 1) {
                                $dateClass .= ' is-selected';
                            } else {
                                $dateClass .= ' is-event-day';
                            }
                        @endphp
                        <button
                            type="button"
                            class="{{ $dateClass }}"
                            data-date="{{ $day['date'] }}"
                            data-ticket-index="{{ $ticketIndex !== null ? (int) $ticketIndex : '' }}"
                            @if(! $day['active']) disabled aria-disabled="true" @endif
                        >
                            {{ $day['day'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <label class="ticket-label" id="ticketDropdownLabelText">Choose ticket type</label>

        <div class="custom-ticket-dropdown" id="ticketDropdown">
            <input type="hidden" id="ticketSelect" value="{{ $eventSelectedIndex }}">

            <button
                class="custom-ticket-trigger"
                type="button"
                id="ticketDropdownTrigger"
                aria-haspopup="listbox"
                aria-expanded="false"
                aria-labelledby="ticketDropdownLabelText ticketDropdownLabel"
            >
                <span class="custom-ticket-copy">
                    <span class="custom-ticket-title" id="ticketDropdownLabel">{{ $eventSelectedTicket['label'] ?? 'Ticket' }}</span>
                    <span class="custom-ticket-meta" id="ticketDropdownMeta">{{ $eventSelectedDateText }}</span>
                </span>

                <span class="custom-ticket-price" id="ticketDropdownPrice">£{{ $eventSelectedTicket['price_formatted'] ?? '0.00' }}</span>

                <span class="custom-ticket-chevron" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </span>
            </button>

            <div class="custom-ticket-menu" id="ticketDropdownMenu" role="listbox" aria-labelledby="ticketDropdownLabelText">
                @foreach($eventVariantRows as $variantRow)
                    <button
                        class="custom-ticket-option{{ $loop->first ? ' is-selected' : '' }}"
                        type="button"
                        role="option"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        data-value="{{ $variantRow['index'] }}"
                    >
                        <span>
                            <span class="custom-ticket-option-title">{{ $variantRow['label'] }}</span>
                            <span class="custom-ticket-option-meta">{{ $variantRow['date_label'] ?? $eventFullRangeLabel }}</span>
                        </span>
                        <span class="custom-ticket-option-price">£{{ $variantRow['price_formatted'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="selected-summary">
            <div class="selected-summary-row">
                <span>Ticket</span>
                <strong id="summaryTicket">{{ $eventSelectedTicket['label'] ?? 'Ticket' }}</strong>
            </div>
            <div class="selected-summary-row">
                <span>Date</span>
                <strong id="summaryDate">{{ $eventSelectedDateText }}</strong>
            </div>
            <div class="selected-summary-row">
                <span>Delivery</span>
                <strong>E-ticket by email</strong>
            </div>
        </div>

        <div class="hold-banner" id="holdBanner">
            <span class="hourglass" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M7 3h10M7 21h10M8 3v5c0 1.3.7 2.5 1.8 3.2L12 12.5l2.2-1.3C15.3 10.5 16 9.3 16 8V3M8 21v-5c0-1.3.7-2.5 1.8-3.2L12 11.5l2.2 1.3C15.3 13.5 16 14.7 16 16v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </span>
            <span>Tickets held for <span id="holdTimer">10:00</span></span>
        </div>

        <div class="ticket-control">
            <div>
                <strong>Quantity</strong>
                <small>E-ticket by email</small>
            </div>

            <div class="qty" aria-label="Ticket quantity">
                <button type="button" id="minusQty">−</button>
                <span id="qtyValue">{{ $eventQty }}</span>
                <button type="button" id="plusQty">+</button>
            </div>
        </div>
    </div>

    <div class="desktop-ticket-buttons">
        <button
            class="btn checkout-button js-buy-now"
            type="button"
            id="eventBookNowBtn"
            data-id="{{ $eventSelectedTicket['id'] ?? '' }}"
            data-product-id="{{ $eventProductId }}"
            data-title="{{ e($eventButtons['title']) }}"
            data-price="{{ $eventSelectedTicket['price_formatted'] ?? '0.00' }}"
            data-image="{{ $eventButtons['image'] }}"
            data-url="{{ $eventButtons['url'] }}"
            data-qty="{{ $eventQty }}"
            data-variant-id="{{ $eventSelectedTicket['id'] ?? '' }}"
            data-variant-label="{{ $eventSelectedTicket['label'] ?? '' }}"
            data-source-version="v3"
        >
            Book now
        </button>

    </div>

    <p class="secure-note">
        Secure checkout. Confirmation and e-ticket sent by email.
    </p>
</aside>

@include('offering.partials.event_mobile_booking_ui')

@push('scripts')
<script>
(function () {
  function init() {
    const ticketData = @json($eventVariantRows);
    const ticketPanel = document.getElementById('tickets');
    const bookingFields = document.getElementById('bookingFields');
    const modalFieldsSlot = document.getElementById('modalFieldsSlot');
    const bookingModal = document.getElementById('bookingModal');
    const bookingBackdrop = document.getElementById('bookingBackdrop');
    const closeBookingModal = document.getElementById('closeBookingModal');
    const mobileTicketBar = document.getElementById('mobileTicketBar');
    const openBookingButtons = document.querySelectorAll('[data-open-booking]');

    const ticketSelect = document.getElementById('ticketSelect');
    const ticketDropdown = document.getElementById('ticketDropdown');
    const ticketDropdownTrigger = document.getElementById('ticketDropdownTrigger');
    const ticketDropdownMenu = document.getElementById('ticketDropdownMenu');
    const ticketDropdownLabel = document.getElementById('ticketDropdownLabel');
    const ticketDropdownMeta = document.getElementById('ticketDropdownMeta');
    const ticketDropdownPrice = document.getElementById('ticketDropdownPrice');
    const ticketDropdownOptions = Array.from(document.querySelectorAll('.custom-ticket-option'));
    const calendarButtons = Array.from(document.querySelectorAll('.calendar-date'));

    const panelPrice = document.getElementById('panelPrice');
    const mobilePrice = document.getElementById('mobilePrice');
    const mobileTicket = document.getElementById('mobileTicket');
    const summaryTicket = document.getElementById('summaryTicket');
    const summaryDate = document.getElementById('summaryDate');
    const qtyValue = document.getElementById('qtyValue');
    const minusQty = document.getElementById('minusQty');
    const plusQty = document.getElementById('plusQty');
    const holdBanner = document.getElementById('holdBanner');
    const holdTimer = document.getElementById('holdTimer');

    const desktopBookBtn = document.getElementById('eventBookNowBtn');
    const addToBasketBtn = document.getElementById('eventAddToBasketBtn');
    const modalBookBtn = document.getElementById('eventModalBookNowBtn');

    const productId = @json($eventProductId);
    const productTitle = @json($eventTitle);
    const productImage = @json($eventImage);
    const productUrl = @json($eventUrl);
    const maxQty = Math.max(1, Number(@json($eventCapacity ?? 1000)) || 1000);

    let selectedIndex = 0;
    let qty = Math.max(1, Number(qtyValue?.textContent || 1) || 1);
    let holdInterval = null;
    let holdSeconds = 600;

  function money(value) {
    const n = Number(value || 0);
    try {
      return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(n);
    } catch (_e) {
      return '£' + n.toFixed(2);
    }
  }

  function currentTicket() {
    return ticketData[selectedIndex] || ticketData[0];
  }

  function updateButtonData(button) {
    if (!button) {
      return;
    }

    const row = currentTicket();

    button.setAttribute('data-id', row.id || '');
    button.setAttribute('data-product-id', String(productId || ''));
    button.setAttribute('data-title', productTitle || '');
    button.setAttribute('data-price', row.price_formatted || '0.00');
    button.setAttribute('data-image', productImage || '');
    button.setAttribute('data-url', productUrl || '');
    button.setAttribute('data-qty', String(qty));
    button.setAttribute('data-variant-id', row.id || '');
    button.setAttribute('data-variant-label', row.label || '');
    button.setAttribute('data-source-version', 'v3');
  }

  function updateTicketFromSelect() {
    const row = currentTicket();
    const selectedDates = Array.isArray(row.dates) ? row.dates : [];

    if (panelPrice) {
      panelPrice.textContent = money(Number(row.price || 0) * qty);
    }
    if (mobilePrice) {
      mobilePrice.textContent = money(row.price || 0);
    }
    if (mobileTicket) {
      mobileTicket.textContent = row.label || 'Ticket';
    }
    if (summaryTicket) {
      summaryTicket.textContent = row.label || 'Ticket';
    }
    if (summaryDate) {
      summaryDate.textContent = row.date_label || '';
    }
    if (qtyValue) {
      qtyValue.textContent = String(qty);
    }
    if (ticketDropdownLabel) {
      ticketDropdownLabel.textContent = row.label || 'Ticket';
    }
    if (ticketDropdownMeta) {
      ticketDropdownMeta.textContent = row.date_label || '';
    }
    if (ticketDropdownPrice) {
      ticketDropdownPrice.textContent = money(row.price || 0);
    }

    ticketDropdownOptions.forEach((option, index) => {
      const active = index === selectedIndex;
      option.classList.toggle('is-selected', active);
      option.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    calendarButtons.forEach((button) => {
      const rawIndex = button.getAttribute('data-ticket-index') || '';
      const ticketIndex = rawIndex !== '' ? Number(rawIndex) : null;
      const isActive = rawIndex !== '';
      const isSelected = selectedDates.includes(button.dataset.date || '');

      button.classList.remove('is-selected', 'is-range');
      if (!isActive) {
        return;
      }
      if (isSelected && selectedDates.length > 1) {
        button.classList.add('is-range');
      } else if (isSelected && selectedDates.length === 1) {
        button.classList.add('is-selected');
      }
    });

    [desktopBookBtn, addToBasketBtn, modalBookBtn].forEach(updateButtonData);
  }

  function startHoldTimer() {
    clearInterval(holdInterval);
    holdSeconds = 600;

    if (holdBanner) {
      holdBanner.classList.add('is-active');
    }
    renderHoldTimer();

    holdInterval = setInterval(() => {
      holdSeconds -= 1;
      renderHoldTimer();

      if (holdSeconds <= 0) {
        clearInterval(holdInterval);
        if (holdBanner) {
          holdBanner.classList.remove('is-active');
        }
      }
    }, 1000);
  }

  function renderHoldTimer() {
    const minutes = Math.floor(holdSeconds / 60);
    const seconds = holdSeconds % 60;
    if (holdTimer) {
      holdTimer.textContent = `${minutes}:${String(seconds).padStart(2, '0')}`;
    }
  }

  function setActiveTicket(index, shouldHold = true) {
    const nextIndex = Math.max(0, Math.min(ticketData.length - 1, Number(index) || 0));
    selectedIndex = nextIndex;
    if (ticketSelect) {
      ticketSelect.value = String(selectedIndex);
    }
    updateTicketFromSelect();
    if (shouldHold) {
      startHoldTimer();
    }
  }

  function openTicketDropdown() {
    ticketDropdown?.classList.add('is-open');
    ticketDropdownTrigger?.setAttribute('aria-expanded', 'true');
  }

  function closeTicketDropdown() {
    ticketDropdown?.classList.remove('is-open');
    ticketDropdownTrigger?.setAttribute('aria-expanded', 'false');
  }

  function isMobileBookingMode() {
    return window.innerWidth <= 720;
  }

  function openModal() {
    if (!isMobileBookingMode() || !bookingFields || !bookingModal || !bookingBackdrop || !modalFieldsSlot) {
      return;
    }

    modalFieldsSlot.appendChild(bookingFields);
    bookingFields.style.display = 'block';
    bookingBackdrop.classList.add('is-open');
    bookingModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!bookingFields || !ticketPanel) {
      return;
    }

    const desktopButtons = ticketPanel.querySelector('.desktop-ticket-buttons');
    if (desktopButtons) {
      ticketPanel.insertBefore(bookingFields, desktopButtons);
    } else {
      ticketPanel.appendChild(bookingFields);
    }

    bookingBackdrop?.classList.remove('is-open');
    bookingModal?.classList.remove('is-open');
    document.body.style.overflow = '';

    if (isMobileBookingMode()) {
      bookingFields.style.display = '';
    }
  }

  function setupMobileStickyTicketBar() {
    if (!ticketPanel || !mobileTicketBar) {
      return;
    }

    if (!isMobileBookingMode()) {
      mobileTicketBar.classList.remove('is-visible');
      document.body.classList.remove('mobile-ticket-visible');
      return;
    }

    mobileTicketBar.classList.add('is-visible');
    document.body.classList.add('mobile-ticket-visible');

    const observer = new IntersectionObserver((entries) => {
      const entry = entries[0];
      const shouldShow = isMobileBookingMode();

      mobileTicketBar.classList.toggle('is-visible', shouldShow);
      document.body.classList.toggle('mobile-ticket-visible', shouldShow);
    }, {
      threshold: 0.12,
    });

    observer.observe(ticketPanel);
  }

  ticketDropdownTrigger?.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = ticketDropdown?.classList.contains('is-open');
    if (isOpen) {
      closeTicketDropdown();
    } else {
      openTicketDropdown();
    }
  });

  ticketDropdownOptions.forEach((option) => {
    option.addEventListener('click', (event) => {
      event.stopPropagation();
      setActiveTicket(Number(option.dataset.value || 0), true);
      closeTicketDropdown();
    });
  });

  calendarButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const ticketIndex = Number(button.dataset.ticketIndex || 0);
      setActiveTicket(ticketIndex, true);
      closeTicketDropdown();
    });
  });

  document.addEventListener('click', (event) => {
    if (ticketDropdown && !ticketDropdown.contains(event.target)) {
      closeTicketDropdown();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeTicketDropdown();
      if (bookingModal?.classList.contains('is-open')) {
        closeModal();
      }
    }
  });

  ticketSelect?.addEventListener('change', () => {
    setActiveTicket(Number(ticketSelect.value || 0), true);
  });

  minusQty?.addEventListener('click', () => {
    qty = Math.max(1, qty - 1);
    updateTicketFromSelect();
  });

  plusQty?.addEventListener('click', () => {
    qty = Math.min(maxQty, qty + 1);
    updateTicketFromSelect();
  });

  openBookingButtons.forEach((button) => {
    button.addEventListener('click', openModal);
  });

  closeBookingModal?.addEventListener('click', closeModal);
  bookingBackdrop?.addEventListener('click', closeModal);

  window.addEventListener('resize', () => {
    if (!isMobileBookingMode() && bookingModal?.classList.contains('is-open')) {
      closeModal();
    }

    if (!isMobileBookingMode() && mobileTicketBar) {
      mobileTicketBar.classList.remove('is-visible');
      document.body.classList.remove('mobile-ticket-visible');
    }
  });

    setActiveTicket(0, false);
    setupMobileStickyTicketBar();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
</script>
@endpush
@endif
