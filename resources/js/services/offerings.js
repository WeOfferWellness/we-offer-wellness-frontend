const DEFAULT_IMAGE_MARKERS = ['no-product-image.jpg', 'placeholder.png', '/images/no-product-image.jpg'];

const DAY_ORDER = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
const DAY_LABELS = {
  mon: 'M',
  tue: 'T',
  wed: 'W',
  thu: 'T',
  fri: 'F',
  sat: 'S',
  sun: 'S',
};

function toStr(value) {
  return typeof value === 'string' ? value : String(value ?? '');
}

function lower(value) {
  return toStr(value).toLowerCase().trim();
}

function titleCase(value) {
  return toStr(value)
    .toLowerCase()
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/\b\w/g, (m) => m.toUpperCase());
}

function legacyTypeLabel(value) {
  const raw = toStr(value).trim()
  if (!raw) return 'Experience'
  const normalized = raw.toLowerCase().replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim()
  const map = {
    therapies: 'Therapy',
    therapy: 'Therapy',
    workshops: 'Workshop',
    workshop: 'Workshop',
    events: 'Event',
    event: 'Event',
    classes: 'Class',
    class: 'Class',
    retreats: 'Retreat',
    retreat: 'Retreat',
    experiences: 'Experience',
    experience: 'Experience',
  }
  return map[normalized] || titleCase(normalized)
}

function escapeHtml(value) {
  return toStr(value).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[c] || c));
}

function stripEmoji(value) {
  return toStr(value)
    .replace(/[\u{1F1E6}-\u{1F1FF}\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{FE0F}\u{200D}]/gu, '')
    .replace(/\s{2,}/g, ' ')
    .trim();
}

function moneyValue(value) {
  const num = Number(value);
  if (!Number.isFinite(num)) return null;
  return Math.round(num * 100) / 100;
}

function formatMoney(value) {
  const price = moneyValue(value);
  if (price === null) return '';
  return `£${price.toFixed(2)}`;
}

function isWeOfferWellnessProvider(item) {
  const values = [
    item?.vendor_name,
    item?.vendor?.name,
    item?.vendor_details?.name,
    item?.vendor?.user?.name,
    item?.vendor?.user?.email,
  ].map((value) => lower(value).replace(/[^a-z0-9]/g, ''));
  return values.some((value) => value.includes('weofferwellness'));
}

function customerPrice(value, item) {
  const amount = moneyValue(value);
  if (amount === null) return amount;
  const physical = ['physical_product', 'store_product'].includes(lower(item?.kind || item?.product_kind || item?.source_type));
  if (physical) return moneyValue(isWeOfferWellnessProvider(item) ? amount : amount * 1.05);
  if (isWeOfferWellnessProvider(item)) return Math.ceil(amount);
  return Math.ceil(amount * 1.05);
}

function hasDisplayableImageUrl(value) {
  const url = lower(value);
  if (!url) return false;
  return !DEFAULT_IMAGE_MARKERS.some((marker) => url.includes(marker));
}

function normalizeVendorDetails(vendor) {
  if (!vendor || typeof vendor !== 'object') return null;
  return vendor;
}

export function normalizeOfferingsPayload(payload) {
  const vendorDetails = payload?.included?.vendor_details && typeof payload.included.vendor_details === 'object'
    ? payload.included.vendor_details
    : {};

  const rankingRequestId = payload?.meta?.ranking_request_id || null;
  const items = Array.isArray(payload?.data) ? payload.data.map((item) => {
    const vendor = vendorDetails[String(item?.vendor_id ?? '')] || item?.vendor || null;
    return {
      ...item,
      image: item?.image || item?.image_url || null,
      ranking_request_id: rankingRequestId,
      vendor_details: normalizeVendorDetails(vendor),
    };
  }) : [];

  return {
    items,
    vendorDetails,
    meta: payload?.meta || {},
    filters: payload?.filters || {},
  };
}

export async function fetchOfferings(params = {}, options = {}) {
  const opts = typeof options === 'object' && options !== null ? options : {};
  const qs = new URLSearchParams(
    Object.entries(params)
      .filter(([, value]) => value !== null && value !== undefined && value !== '')
      .reduce((carry, [key, value]) => {
        carry[key] = value;
        return carry;
      }, {})
  ).toString();
  const base = opts.baseUrl
    || import.meta.env.VITE_OFFERINGS_URL
    || `${String(import.meta.env.VITE_BACKEND_URL || '').replace(/\/$/, '')}/api/behaviour/offerings`;
  const url = qs ? `${base}?${qs}` : base;

  try {
    const res = await fetch(url, { cache: 'no-store', credentials: 'include', headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`Failed to load offerings: ${res.status}`);
    const payload = await res.json();
    return normalizeOfferingsPayload(payload);
  } catch (error) {
    console.warn('[offerings] fetch failed', error);
    if (opts.throwOnError) throw error;
    return normalizeOfferingsPayload({ data: [], included: { vendor_details: {} }, meta: {}, filters: {} });
  }
}

function normalizeText(value) {
  return lower(value)
    .replace(/[\u2018\u2019\u201c\u201d]/g, "'")
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();
}

function priceValue(item) {
  const candidates = [
    item?.price,
    item?.price_raw,
    item?.metrics?.price,
    item?.vendor_details?.price,
  ];
  for (const candidate of candidates) {
    const price = moneyValue(candidate);
    if (price !== null) return price;
  }
  return null;
}

function ratingValue(item) {
  const candidates = [
    item?.rating,
    item?.reviews_avg_rating,
    item?.vendor_details?.rating_average,
  ];
  for (const candidate of candidates) {
    const value = Number(candidate);
    if (Number.isFinite(value)) return value;
  }
  return 0;
}

function reviewCountValue(item) {
  const candidates = [
    item?.review_count,
    item?.reviews_count,
    item?.vendor_details?.reviews_count,
  ];
  for (const candidate of candidates) {
    const value = Number(candidate);
    if (Number.isFinite(value)) return value;
  }
  return 0;
}

function planPriority(item) {
  const value = item?.vendor_details?.plan?.priority ?? item?.vendor?.plan_priority ?? item?.vendor?.plan?.priority ?? 0;
  return Number.isFinite(Number(value)) ? Number(value) : 0;
}

function hasAvailabilityFlag(item) {
  if (!item || typeof item !== 'object') return false;

  const vendor = item.vendor_details || item.vendor || {};
  const availability = vendor.availability || {};
  const weekly = availability.weekly_rules || {};
  const specific = availability.specific_windows || {};

  if (Array.isArray(item.availability_days) && item.availability_days.length > 0) return true;
  if (Array.isArray(item.availability_calendar) && item.availability_calendar.length > 0) return true;
  if (availability.accepting_bookings === true && Object.keys(weekly).length > 0) {
    return Object.values(weekly).some((day) => day?.enabled && Array.isArray(day.windows) && day.windows.length > 0);
  }
  if (Object.keys(specific).length > 0) return true;
  return false;
}

function availabilityPriority(item) {
  return hasAvailabilityFlag(item) ? 1 : 0;
}

function itemKindPriority(item) {
  if (!item || typeof item !== 'object') return 0;
  if (lower(item.source_version) === 'v3') return 1;
  if (lower(item.source_type) === 'offering') return 1;
  return 0;
}

function isStructuredOffering(item) {
  return lower(item?.source_version) === 'v3' || lower(item?.source_type) === 'offering';
}

function titleValue(item) {
  return lower(item?.title || '');
}

function compareValues(left, right) {
  if (typeof left === 'number' || typeof right === 'number') {
    return Math.abs(Number(left ?? 0) - Number(right ?? 0)) < 0.000001;
  }
  return left === right;
}

function compareDefault(left, right) {
  for (const [l, r] of [
    [availabilityPriority(left), availabilityPriority(right)],
    [itemKindPriority(left), itemKindPriority(right)],
    [planPriority(left), planPriority(right)],
    [ratingValue(left), ratingValue(right)],
    [reviewCountValue(left), reviewCountValue(right)],
  ]) {
    if (compareValues(l, r)) continue;
    return Number(r) - Number(l);
  }

  return titleValue(left).localeCompare(titleValue(right));
}

function vendorKey(item) {
  const vendorId = item?.vendor_id || item?.vendor_details?.id || item?.vendor?.id;
  if (Number.isFinite(Number(vendorId)) && Number(vendorId) > 0) {
    return `vendor:${Number(vendorId)}`;
  }

  const vendorName = lower(item?.vendor_details?.name || item?.vendor?.name || item?.vendor_name || '');
  if (vendorName) {
    return `vendor:${vendorName}`;
  }

  return `${item?.source_version || item?.source_type || 'item'}:${item?.id || ''}`;
}

function diversifyByVendor(items) {
  if (!Array.isArray(items) || !items.length) return [];

  const groups = new Map();
  items.forEach((item) => {
    const key = vendorKey(item);
    if (!groups.has(key)) {
      groups.set(key, []);
    }
    groups.get(key).push(item);
  });

  const sortedGroups = [...groups.values()]
    .map((group) => group.slice())
    .sort((left, right) => compareDefault(right[0], left[0]));

  const queues = sortedGroups.map((group) => group.slice());
  const diversified = [];
  let exhausted = false;

  while (!exhausted) {
    exhausted = true;
    for (const queue of queues) {
      if (!queue.length) continue;
      exhausted = false;
      diversified.push(queue.shift());
    }
  }

  return diversified;
}

function sortLatest(items) {
  return [...items].sort((left, right) => {
    const leftKind = itemKindPriority(left);
    const rightKind = itemKindPriority(right);
    if (leftKind !== rightKind) return rightKind - leftKind;

    const leftDate = new Date(left?.published_at || left?.created_at || 0).getTime();
    const rightDate = new Date(right?.published_at || right?.created_at || 0).getTime();
    if (leftDate !== rightDate) return rightDate - leftDate;

    return titleValue(left).localeCompare(titleValue(right));
  });
}

export function sortOfferings(items, sort = 'popular') {
  const list = Array.isArray(items) ? items.slice() : [];
  const mode = lower(sort || 'popular');

  if (mode === 'latest' || mode === 'newest' || mode === 'created_at') {
    return sortLatest(list);
  }

  if (mode === 'price_asc') {
    return list.sort((left, right) => (priceValue(left) ?? Number.POSITIVE_INFINITY) - (priceValue(right) ?? Number.POSITIVE_INFINITY));
  }

  if (mode === 'price_desc') {
    return list.sort((left, right) => (priceValue(right) ?? 0) - (priceValue(left) ?? 0));
  }

  if (mode === 'rating_desc') {
    return list.sort((left, right) => {
      const ratingDiff = ratingValue(right) - ratingValue(left);
      if (ratingDiff !== 0) return ratingDiff;
      const reviewDiff = reviewCountValue(right) - reviewCountValue(left);
      if (reviewDiff !== 0) return reviewDiff;
      return titleValue(left).localeCompare(titleValue(right));
    });
  }

  return diversifyByVendor(list.sort(compareDefault));
}

export function isGiftCard(item) {
  const haystack = normalizeText([
    item?.title,
    item?.summary,
    item?.slug,
    item?.category?.name,
    item?.type?.name,
    item?.product_type,
    item?.fomo_text,
  ].filter(Boolean).join(' '));

  return ['gift card', 'giftcard', 'voucher'].some((needle) => haystack.includes(normalizeText(needle)));
}

export function isDisplayableImage(item) {
  return hasDisplayableImageUrl(item?.image_url || item?.image || item?.featured_image || '');
}

export function isOnlineOnly(item) {
  if (!item || typeof item !== 'object') return false;
  if (item.online_only === true) return true;

  const channels = (item.channels || []).map((channel) => lower(channel));
  if (channels.includes('online') && !channels.includes('in_person')) return true;

  const locations = (item.locations || []).map((location) => lower(location));
  if (locations.length && locations.every((location) => location.includes('online'))) {
    return true;
  }

  return false;
}

export function hasOnlineDelivery(item) {
  if (!item || typeof item !== 'object') return false;
  if (item.online_only === true || item.online === true) return true;

  const channels = (item.channels || []).map((channel) => lower(channel));
  if (channels.some((channel) => ['online', 'remote', 'virtual'].includes(channel))) return true;

  return (item.locations || []).some((location) => lower(location).includes('online'));
}

export function matchesGroupType(item, groupType) {
  const mode = lower(groupType);
  if (!['solo', 'couple', 'group'].includes(mode)) {
    return true;
  }

  const audiences = (item?.audiences || []).map((audience) => lower(audience));
  if (audiences.includes(mode)) {
    return true;
  }

  const haystack = normalizeText([
    item?.title,
    item?.summary,
    item?.category?.name,
    item?.type?.name,
    item?.product_type,
    item?.slug,
    item?.vendor_details?.name,
  ].filter(Boolean).join(' '));

  if (mode === 'solo') {
    return /(^|[^a-z0-9])(solo|single|individual|private|1\s*to\s*1|1:1)([^a-z0-9]|$)/i.test(haystack);
  }

  if (mode === 'couple') {
    return /(^|[^a-z0-9])(couple|pair|duo|2\s*person)([^a-z0-9]|$)/i.test(haystack);
  }

  return /(^|[^a-z0-9])(group|class|workshop|family|team|party|ceremony)([^a-z0-9]|$)/i.test(haystack);
}

function matchesGiftKeywords(item) {
  const haystack = normalizeText([
    item?.title,
    item?.summary,
    item?.slug,
    item?.category?.name,
    item?.type?.name,
    item?.product_type,
  ].filter(Boolean).join(' '));

  return ['gift', 'voucher', 'present', 'gift card', 'giftcard', 'card'].some((needle) => haystack.includes(normalizeText(needle)));
}

export function selectGiftRailItems(items, limit = 12) {
  const base = Array.isArray(items) ? items.slice() : [];
  const priced = base.filter((item) => {
    const price = priceValue(item);
    return price !== null && price <= 50;
  });

  const giftFirst = priced.filter(matchesGiftKeywords);
  const source = giftFirst.length ? giftFirst : priced;

  return sortOfferings(source, 'popular').slice(0, limit);
}

export function selectLatestRailItems(items, limit = 12) {
  return sortOfferings(items, 'latest').slice(0, limit);
}

export function selectComfortRailItems(items, { priceMax = 50, groupType = 'solo' } = {}, limit = 12) {
  const max = Number(priceMax) || 50;
  const filtered = (Array.isArray(items) ? items : []).filter((item) => {
    const price = priceValue(item);
    if (price === null || price > max) return false;
    if (!isOnlineOnly(item)) return false;
    if (!matchesGroupType(item, groupType)) return false;
    return true;
  });

  return sortOfferings(filtered, 'popular').slice(0, limit);
}

function renderStars(rating = 0) {
  const filled = Math.max(0, Math.min(5, Math.round(Number(rating) || 0)));
  return `
    <span class="stars" aria-hidden="true">
      ${Array.from({ length: 5 }, (_, index) => {
        const position = index + 1;
        const color = position <= filled ? '#f5c84b' : '#d0d5dd';
        const emptyClass = position > filled ? ' star--empty' : '';
        return `<span class="star${emptyClass}" style="color:${color};"></span>`;
      }).join('')}
    </span>
  `;
}

function buildAvailabilityDays(item) {
  const vendor = item?.vendor_details || item?.vendor || {};
  const weekly = vendor?.availability?.weekly_rules || {};
  const active = new Set();

  Object.entries(weekly).forEach(([day, config]) => {
    if (!DAY_ORDER.includes(day)) return;
    if (!config || config.enabled !== true || !Array.isArray(config.windows) || !config.windows.length) {
      return;
    }
    active.add(day);
  });

  return DAY_ORDER.map((day) => {
    const label = DAY_LABELS[day];
    const classes = active.has(day) ? 'wow-day is-active' : 'wow-day is-request';
    return `<span class="${classes}" title="${label}">${label}</span>`;
  }).join('');
}

function buildSignalText(item) {
  if (item?.online_only) {
    return 'Exclusively online';
  }

  const count = Number(item?.physical_location_count || 0);
  if (count > 0) {
    return `+${count} more locations`;
  }

  return 'Request day/time';
}

function buildLocationChip(item) {
  const locations = Array.isArray(item?.locations) ? item.locations.filter(Boolean) : [];
  if (!locations.length) return '';
  const first = locations.find((location) => !lower(location).includes('online')) || locations[0];
  return `
    <span class="therapy-card__chip">
      <span class="wow-chip-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M17.8 13.938h-.011a7 7 0 1 0-11.464.144h-.016l.14.171c.1.127.2.251.3.371L12 21l5.13-6.248c.194-.209.374-.429.54-.659l.13-.155Z"/></svg>
      </span>
      ${escapeHtml(first)}
    </span>
  `;
}

function normalizePlanKey(value) {
  const normalized = toStr(value)
    .trim()
    .toLowerCase()
    .replace(/[_\s]+/g, '-');

  const map = {
    community: 'starter',
    starter: 'starter',
    standard: 'starter',
    'free-starter': 'starter',
    'starter-package': 'starter',
    core: 'business-accelerator',
    businessaccelerator: 'business-accelerator',
    'business-accelerator-package': 'business-accelerator',
    premium: 'premium-accelerator',
    premiumaccelerator: 'premium-accelerator',
    'premium-accelerator-package': 'premium-accelerator',
    'become-partner': 'become-partner',
    partner: 'become-partner',
  };

  return map[normalized] || normalized;
}

function eventSourceFromItem(item) {
  const base = item && typeof item === 'object' ? item : {};
  const nested = item?.when?.event || item?.event || {};
  return {
    ...base,
    ...(nested && typeof nested === 'object' ? nested : {}),
  };
}

function itemHasEventSchedule(item) {
  const event = eventSourceFromItem(item);
  return Boolean(
    String(event?.type || item?.when?.type || '').toLowerCase() === 'event'
    || event?.date
    || event?.start_date
    || event?.end_date
    || event?.start_time
    || event?.end_time
    || item?.date
    || item?.start_date
    || item?.end_date
    || item?.start_time
    || item?.end_time
    || (Array.isArray(event?.dates) && event.dates.length)
    || (Array.isArray(event?.upcoming_dates) && event.upcoming_dates.length)
    || (Array.isArray(event?.availability_dates) && event.availability_dates.length)
  );
}

function parseEventDateTime(value, timeValue) {
  if (!value) return null;
  const raw = timeValue
    ? `${value}T${timeValue}:00`
    : (/[T\s]/.test(String(value)) ? String(value) : `${value}T00:00:00`);
  const parsed = new Date(raw);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function eventHasPassed(item, event) {
  if (item?.is_past_event || item?.display_is_past) return true;
  const start = parseEventDateTime(event?.start_date || event?.date || null, event?.start_time || null);
  const end = parseEventDateTime(event?.end_date || event?.finish_date || event?.start_date || event?.date || null, event?.end_time || event?.finish_time || event?.start_time || null);
  const boundary = end || start;
  return !!boundary && boundary.getTime() < Date.now();
}

function formatEventRange(startDate, startTime, endDate, endTime) {
  const start = parseEventDateTime(startDate, startTime);
  const end = parseEventDateTime(endDate || startDate, endTime || startTime);
  if (!start || !end) return '';

  const sameDay = start.toDateString() === end.toDateString();
  const sameYear = start.getFullYear() === end.getFullYear();
  const startDateLabel = start.toLocaleDateString(undefined, { month: 'short', day: 'numeric', ...(sameYear ? {} : { year: 'numeric' }) });
  const endDateLabel = end.toLocaleDateString(undefined, { month: 'short', day: 'numeric', ...(sameYear ? {} : { year: 'numeric' }) });

  if (startTime || endTime) {
    const startTimeLabel = start.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    const endTimeLabel = end.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    return sameDay
      ? `${startDateLabel}, ${startTimeLabel} – ${endTimeLabel}`
      : `${startDateLabel}, ${startTimeLabel} – ${endDateLabel}, ${endTimeLabel}`;
  }

  return sameDay ? startDateLabel : `${startDateLabel} – ${endDateLabel}`;
}

function buildEventTileUrl(baseUrl, isoDate) {
  if (!baseUrl) return isoDate ? `?date=${isoDate}` : '#';
  if (!isoDate) return baseUrl;
  return String(baseUrl).includes('?') ? `${baseUrl}&date=${isoDate}` : `${baseUrl}?date=${isoDate}`;
}

function eventTilesFromValue(value, baseUrl) {
  if (!value) return [];

  if (typeof value === 'string') {
    const parsed = parseEventDateTime(value, null);
    if (!parsed) return [];
    const iso = parsed.toISOString().slice(0, 10);
    return [{
      month: parsed.toLocaleDateString(undefined, { month: 'short' }),
      day: String(parsed.getDate()).padStart(2, '0'),
      url: buildEventTileUrl(baseUrl, iso),
    }];
  }

  if (typeof value === 'object') {
    const startValue = value.date || value.start_date || value.start || value.value || null;
    const startTime = value.start_time || value.time || null;
    const endValue = value.end_date || value.finish_date || null;
    const endTime = value.end_time || null;
    const month = value.month || value.mon || value.short_month;
    const dayValue = value.day ?? value.date_day ?? value.day_of_month;
    const explicitUrl = value.url || value.href || null;
    const startParsed = parseEventDateTime(startValue, startTime);
    const tiles = [];

    if (month && dayValue !== undefined && dayValue !== null) {
      tiles.push({
        month: String(month),
        day: String(dayValue).padStart(2, '0'),
        url: explicitUrl || buildEventTileUrl(baseUrl, startParsed ? startParsed.toISOString().slice(0, 10) : null),
      });
    } else if (startParsed) {
      const iso = startParsed.toISOString().slice(0, 10);
      tiles.push({
        month: startParsed.toLocaleDateString(undefined, { month: 'short' }),
        day: String(startParsed.getDate()).padStart(2, '0'),
        url: explicitUrl || buildEventTileUrl(baseUrl, iso),
      });
    }

    const endParsed = parseEventDateTime(endValue || startValue, endTime || startTime);
    if (startParsed && endParsed && startParsed.toDateString() !== endParsed.toDateString()) {
      const endIso = endParsed.toISOString().slice(0, 10);
      tiles.push({
        month: endParsed.toLocaleDateString(undefined, { month: 'short' }),
        day: String(endParsed.getDate()).padStart(2, '0'),
        url: buildEventTileUrl(baseUrl, endIso),
      });
    }

    return tiles;
  }

  return [];
}

function eventTilesForItem(item, baseUrl) {
  const event = eventSourceFromItem(item);
  const rawSources = [];
  if (Array.isArray(event?.dates)) rawSources.push(...event.dates);
  if (Array.isArray(event?.upcoming_dates)) rawSources.push(...event.upcoming_dates);
  if (Array.isArray(event?.availability_dates)) rawSources.push(...event.availability_dates);

  const tiles = rawSources.reduce((acc, entry) => acc.concat(eventTilesFromValue(entry, baseUrl)), []);
  const start = parseEventDateTime(event?.start_date || event?.date || null, event?.start_time || null);
  const end = parseEventDateTime(event?.end_date || event?.start_date || event?.date || null, event?.end_time || null);

  if (!tiles.length) {
    if (start) {
      tiles.push({
        month: start.toLocaleDateString(undefined, { month: 'short' }),
        day: String(start.getDate()).padStart(2, '0'),
        url: buildEventTileUrl(baseUrl, start.toISOString().slice(0, 10)),
      });
    }
    if (end && (!start || end.toDateString() !== start.toDateString())) {
      tiles.push({
        month: end.toLocaleDateString(undefined, { month: 'short' }),
        day: String(end.getDate()).padStart(2, '0'),
        url: buildEventTileUrl(baseUrl, end.toISOString().slice(0, 10)),
      });
    }
  } else if (start && end && start.toDateString() !== end.toDateString()) {
    const endIso = end.toISOString().slice(0, 10);
    const hasEndTile = tiles.some((tile) => {
      const tileUrl = String(tile?.url || '');
      return tileUrl.includes(endIso)
        || (tile?.month === end.toLocaleDateString(undefined, { month: 'short' }) && tile?.day === String(end.getDate()).padStart(2, '0'));
    });

    if (!hasEndTile) {
      tiles.push({
        month: end.toLocaleDateString(undefined, { month: 'short' }),
        day: String(end.getDate()).padStart(2, '0'),
        url: buildEventTileUrl(baseUrl, endIso),
      });
    }
  }

  const total = tiles.length;
  if (total > 4) {
    return [...tiles.slice(0, 3), { month: 'More', day: `+${total - 3}`, url: `${baseUrl || '#'}#dates`, more: true }];
  }

  return tiles;
}

function eventLocationLabelFor(item) {
  // A hybrid offering must not be labelled In-person just because it also has
  // a venue. Online is an available delivery choice and takes precedence here.
  if (hasOnlineDelivery(item)) {
    return isOnlineOnly(item) ? 'Online' : 'Online available';
  }

  const candidates = [];
  if (item?.matched_location_label) candidates.push(item.matched_location_label);
  if (item?.location_name) candidates.push(item.location_name);
  if (item?.location) candidates.push(item.location);
  if (item?.venue) candidates.push(item.venue);
  if (Array.isArray(item?.locations)) {
    item.locations.forEach((value) => {
      if (!value) return;
      if (typeof value === 'string') {
        candidates.push(value);
      } else if (typeof value === 'object') {
        candidates.push(value.name || value.label || value.title || value.city || value.location || '');
      }
    });
  }

  const first = candidates
    .map((value) => toStr(value).trim())
    .find((value) => value && lower(value) !== 'online');

  if (first) {
    return titleCase(first.replace(/,?\s*(united kingdom|uk)$/i, '').trim());
  }

  return 'In-person';
}

function slugify(value) {
  return toStr(value)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function inferFormatKey(item) {
  const raw = lower(
    item?.format
    || item?.format_key
    || item?.type_key
    || item?.type?.format
    || item?.type?.slug
    || item?.type?.name
    || item?.type
    || item?.product_type
    || '',
  )

  if (raw.includes('class')) return 'classes'
  if (raw.includes('event')) return 'events'
  if (raw.includes('workshop')) return 'workshops'
  if (raw.includes('retreat')) return 'retreats'
  if (raw.includes('gift')) return 'gifts'
  return 'therapies'
}

function inferModalityKey(item) {
  const candidates = [
    item?.modality,
    item?.category?.slug,
    item?.category?.name,
    item?.category_name,
    item?.category_label,
    item?.type?.slug,
    item?.type?.name,
    item?.type_name,
  ]

  for (const candidate of candidates) {
    const value = slugify(candidate)
    if (value) return value
  }

  return slugify(item?.slug || item?.title || item?.handle || item?.id || '')
}

function buildSeoUrl(item) {
  const slug = slugify(item?.slug || item?.handle || item?.title || item?.name || item?.id || '')
  const format = inferFormatKey(item)
  const modality = inferModalityKey(item)
  const structured = isStructuredOffering(item)

  if (format && modality && slug) {
    return `/${format}/${modality}/${slug}`
  }

  if (format && slug) {
    return `/${format}/${slug}`
  }

  return `/${slug || 'offerings'}`
}

export function renderOfferingCard(item) {
  if (!item || typeof item !== 'object') return '';
  const giftCard = isGiftCard(item);

  const id = item.id;
  const sourceVersion = item.source_version || item.sourceType || item.source_type || 'v1-v2';
  const url = item.url || buildSeoUrl(item);
  const title = titleCase(item.title || 'Untitled');
  const provider = item?.vendor_details?.name || item?.vendor?.name || item?.vendor_name || '';
  const providerFormatted = provider ? titleCase(provider) : '';
  const businessAccelerator = normalizePlanKey(
    item?.plan_key
    || item?.plan_label
    || item?.vendor_details?.plan_key
    || item?.vendor_details?.plan_label
    || item?.vendor?.user?.tier?.tier
    || item?.vendor?.user?.account_type
  ) === 'business-accelerator';
  const price = customerPrice(priceValue(item), item);
  const priceLabel = formatMoney(price);
  const categoryRaw = item?.category?.name || item?.category_name || item?.category_label || '';
  const typeRaw = item?.type?.name
    || item?.type_label
    || item?.type_name
    || (typeof item?.type === 'string' ? item.type : '')
    || item?.product_type
    || 'Experience';
  const hasEventSchedule = itemHasEventSchedule(item);
  let typeLabel = legacyTypeLabel(typeRaw);
  if (hasEventSchedule) {
    typeLabel = 'Event';
  }
  const categoryLabel = categoryRaw ? legacyTypeLabel(categoryRaw) : legacyTypeLabel(typeRaw);
  const eventCard = hasEventSchedule || typeLabel === 'Event' || categoryLabel === 'Event';
  if (!giftCard && !eventCard && !isDisplayableImage(item)) return '';
  const benefitText = stripEmoji(item?.summary || item?.benefit || item?.description || '');
  const rating = ratingValue(item);
  const reviewCount = reviewCountValue(item);
  const signalText = buildSignalText(item);
  const durationLabel = item?.duration || '';
  const hasOnline = hasOnlineDelivery(item);
  const physicalLocations = Array.isArray(item?.locations)
    ? item.locations.filter((location) => lower(location) !== 'online')
    : [];
  const exclusiveOnline = hasOnline && physicalLocations.length === 0;
  const availabilityDays = buildAvailabilityDays(item);
  const calendarLabel = availabilityDays ? 'Availability calendar' : 'Request day/time';
  const calendarNote = availabilityDays ? 'Live calendar' : 'Practitioner confirms';
  const imageUrl = item.image_url || item.image || item.featured_image || '';
  const event = eventSourceFromItem(item);
  const eventRangeLabel = formatEventRange(
    event?.start_date || event?.date || null,
    event?.start_time || null,
    event?.end_date || event?.start_date || event?.date || null,
    event?.end_time || null,
  );
  const eventTiles = eventTilesForItem(item, url);
  const visibleEventTiles = eventTiles.filter((tile) => tile && !tile.placeholder && tile.month !== 'Soon' && tile.day !== '—');
  const eventBadge = (() => {
    const parsed = parseEventDateTime(event?.start_date || event?.date || null, event?.start_time || null);
    if (parsed) {
      return {
        month: parsed.toLocaleDateString(undefined, { month: 'short' }),
        day: String(parsed.getDate()).padStart(2, '0'),
      };
    }
    const first = visibleEventTiles.find((tile) => tile && !tile.more);
    return first || { month: 'Soon', day: '—' };
  })();
  const eventAvailabilityTitle = visibleEventTiles.length > 1 ? 'Fixed dates' : (eventBadge.month !== 'Soon' ? 'Fixed date' : 'Event dates');
  const eventAvailabilityNote = eventRangeLabel || (visibleEventTiles.length > 1 ? 'Choose a date' : 'View dates');
  const eventLocationLabel = eventLocationLabelFor(item);
  const eventDescription = benefitText || 'Upcoming event details coming soon.';
  const eventPriceLabel = priceLabel || '£0.00';
  const isPastEvent = eventHasPassed(item, event);

  if (giftCard) {
    return `
      <div class="wow-card md gift-card-card-wrap">
        <article class="gift-card-card" aria-label="Gift card ${escapeHtml(id)}">
          <div class="gift-card-card__media">
            ${isDisplayableImage(item) ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(title)}" loading="lazy">` : `
              <div class="gift-card-card__fallback">Gift Card</div>
            `}
            <span class="gift-card-card__badges">
              <span class="wow-badge wow-badge--gold">Gift card</span>
              <span class="wow-badge wow-badge--blue">Instant delivery</span>
            </span>
          </div>

          <div class="gift-card-card__body">
            <p class="gift-card-card__eyebrow">Digital gift card</p>
            <h3 class="gift-card-card__title">${escapeHtml(title)}</h3>
            ${providerFormatted ? `<p class="gift-card-card__provider">by ${escapeHtml(providerFormatted)}</p>` : ''}
            ${benefitText
              ? `<p class="gift-card-card__summary">${escapeHtml(benefitText).slice(0, 130)}</p>`
              : `<p class="gift-card-card__summary">A flexible digital gift card you can send instantly and redeem across We Offer Wellness.</p>`}

            <div class="gift-card-card__meta">
              <span class="gift-card-card__chip gift-card-card__chip--green">Instant email delivery</span>
            </div>
          </div>

          <footer class="gift-card-card__footer">
            <div class="gift-card-card__price">
              <small>From</small>
              <strong>${escapeHtml(priceLabel || '£0.00')}</strong>
            </div>

            <div class="gift-card-card__actions">
              <a href="/giftcards" class="btn-wow btn-wow--cta btn-sm">
                <span class="btn-label">View gift cards</span>
              </a>
            </div>
          </footer>
        </article>
      </div>
    `;
  }

  if (eventCard) {
    return `
      <article class="wow-card md wow-event-card-v4 ${imageUrl && hasDisplayableImageUrl(imageUrl) ? 'is-loading' : 'is-missing'}" aria-label="Event card ${escapeHtml(id)}" data-product-id="${escapeHtml(id)}" data-source-version="${escapeHtml(sourceVersion)}" data-ranking-request-id="${escapeHtml(item?.ranking_request_id || '')}"${imageUrl && hasDisplayableImageUrl(imageUrl) ? ' aria-busy="true"' : ''}>
        <a href="${escapeHtml(url)}" class="wow-event-card-v4__link" aria-label="View ${escapeHtml(title)}"></a>

        <div class="wow-event-card-v4__image">
          ${imageUrl && hasDisplayableImageUrl(imageUrl) ? `
            <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(title)}" loading="lazy"
              onload="var card=this.closest('.wow-card'); if(card){card.classList.remove('is-loading'); card.classList.add('is-image-loaded'); card.setAttribute('aria-busy','false');}"
              onerror="var card=this.closest('.wow-card'); if(card){card.classList.remove('is-loading'); card.classList.add('is-image-missing'); card.setAttribute('aria-busy','false');} this.remove();">
          ` : ''}
        </div>

        <div class="wow-event-card-v4__shade"></div>

        <div class="wow-event-card-v4__top">
          <span class="wow-event-card-v4__date">
            <span class="wow-event-card-v4__date-month">${escapeHtml(eventBadge.month)}</span>
            <span class="wow-event-card-v4__date-day">${escapeHtml(eventBadge.day)}</span>
          </span>

          ${businessAccelerator ? `
            <div class="premium-badge-holder">
              <div class="premium-badge-drawer">
                <div class="premium-badge-sheen"></div>

                <div class="premium-badge-copy">
                  <p class="premium-badge-title">Premium Partner</p>
                  <span class="premium-badge-small">Business Accelerator</span>
                </div>

                <button
                  type="button"
                  class="premium-badge-button"
                  aria-label="Business Accelerator Premium Partner"
                  title="Premium Partner"
                  onclick="event.preventDefault(); event.stopPropagation();"
                >
                  <img src="https://studio.weofferwellness.co.uk/storage/uploads/images/78aa908f-334b-45c0-9220-1c4d84053c5e.png" alt="Premium Partner rosette">
                </button>
              </div>
            </div>
          ` : ''}
        </div>

        <div class="wow-event-card-v4__panel">
          <div class="wow-event-card-v4__tags">
            <span class="wow-event-card-v4__tag">${escapeHtml(categoryLabel)}</span>
            <span class="wow-event-card-v4__tag wow-event-card-v4__tag--blue">${escapeHtml(typeLabel)}</span>
          </div>

          <h3 class="wow-event-card-v4__title">${escapeHtml(title)}</h3>

          <div class="wow-event-card-v4__hidden">
            ${providerFormatted ? `<p class="wow-event-card-v4__provider">with ${escapeHtml(providerFormatted)}</p>` : ''}

            ${reviewCount > 0 ? `
              <div class="rating-row" aria-label="Rated ${rating ? rating.toFixed(1) : '0.0'} out of 5">
                ${renderStars(rating)}
                <span>${rating ? rating.toFixed(1) : '0.0'} · ${reviewCount} review${reviewCount === 1 ? '' : 's'}</span>
              </div>
            ` : ''}

            <div class="wow-event-card-v4__meta">
              <span class="wow-event-card-v4__meta-line">
                <span class="wow-event-card-v4__meta-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11Z" stroke="currentColor" stroke-width="2"></path>
                    <path d="M12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" stroke="currentColor" stroke-width="2"></path>
                  </svg>
                </span>
                ${escapeHtml(eventLocationLabel)}
              </span>

              <span class="wow-event-card-v4__meta-line">
                <span class="wow-event-card-v4__meta-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M7 3v3M17 3v3M4.5 9.25h15" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    <path d="M6.75 5h10.5C18.77 5 20 6.23 20 7.75v10.5C20 19.77 18.77 21 17.25 21H6.75C5.23 21 4 19.77 4 18.25V7.75C4 6.23 5.23 5 6.75 5Z" stroke="currentColor" stroke-width="2"></path>
                  </svg>
                </span>
                ${escapeHtml(eventRangeLabel || eventAvailabilityTitle)}
              </span>
            </div>

            <p class="wow-event-card-v4__description">${escapeHtml(eventDescription)}</p>

            <div class="wow-event-card-v4__availability">
              <div class="wow-event-card-v4__availability-head">
                <p class="wow-event-card-v4__availability-title">
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 3v3M17 3v3M4.5 9.25h15" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    <path d="M6.75 5h10.5C18.77 5 20 6.23 20 7.75v10.5C20 19.77 18.77 21 17.25 21H6.75C5.23 21 4 19.77 4 18.25V7.75C4 6.23 5.23 5 6.75 5Z" stroke="currentColor" stroke-width="2"></path>
                  </svg>
                  ${escapeHtml(eventAvailabilityTitle)}
                </p>

                <p class="wow-event-card-v4__availability-note">${escapeHtml(eventAvailabilityNote)}</p>
              </div>

              <div class="wow-event-card-v4__availability-grid">
                ${visibleEventTiles.map((tile) => {
                  if (tile.more) {
                    return `
                      <a href="${escapeHtml(tile.url || '#')}" class="wow-event-card-v4__date-more" aria-label="View ${escapeHtml(tile.day)} more dates">
                        <span class="wow-event-card-v4__date-more-top">More</span>
                        <span class="wow-event-card-v4__date-more-number">${escapeHtml(tile.day)}</span>
                      </a>
                    `;
                  }

                  return `
                    <a href="${escapeHtml(tile.url || url)}" class="wow-event-card-v4__date-mini" aria-label="${escapeHtml(tile.month)} ${escapeHtml(tile.day)}">
                      <span class="wow-event-card-v4__date-mini-month">${escapeHtml(tile.month)}</span>
                      <span class="wow-event-card-v4__date-mini-day">${escapeHtml(tile.day)}</span>
                    </a>
                  `;
                }).join('')}
              </div>
            </div>
          </div>

          <footer class="wow-event-card-v4__footer">
            <div>
              <span class="wow-event-card-v4__price-label">From</span>
              <span class="wow-event-card-v4__price">${escapeHtml(eventPriceLabel)}</span>
            </div>

            <div class="wow-event-card-v4__actions">
              <a href="${escapeHtml(url)}" class="wow-event-card-v4__book-btn">${isPastEvent ? 'View details' : 'Book'}</a>
            </div>
          </footer>
        </div>
      </article>
    `;
  }

  return `
    <a href="${escapeHtml(url)}" class="wow-card md">
      <article class="therapy-card" aria-label="Offering card ${escapeHtml(id)}" data-product-id="${escapeHtml(id)}" data-source-version="${escapeHtml(sourceVersion)}" data-ranking-request-id="${escapeHtml(item?.ranking_request_id || '')}">
        <div class="therapy-card__media">
          <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(title)}" loading="lazy">

          ${signalText ? `<span class="therapy-card__signal">${escapeHtml(signalText)}</span>` : ''}

          ${businessAccelerator ? `
            <div class="premium-badge-holder">
              <div class="premium-badge-drawer">
                <div class="premium-badge-sheen"></div>

                <div class="premium-badge-copy">
                  <p class="premium-badge-title">Premium Partner</p>
                  <span class="premium-badge-small">Business Accelerator</span>
                </div>

                <button
                  type="button"
                  class="premium-badge-button"
                  aria-label="Business Accelerator Premium Partner"
                  title="Premium Partner"
                  onclick="event.preventDefault(); event.stopPropagation();"
                >
                  <img src="https://studio.weofferwellness.co.uk/storage/uploads/images/78aa908f-334b-45c0-9220-1c4d84053c5e.png" alt="Premium Partner rosette">
                </button>
              </div>
            </div>
          ` : ''}

          <span class="therapy-card__badges">
            <span class="wow-badge wow-badge--gold">${escapeHtml(categoryLabel)}</span>
            <span class="wow-badge wow-badge--blue">${escapeHtml(typeLabel)}</span>
          </span>
        </div>

        <div class="therapy-card__body">
          <h3 class="therapy-card__title">${escapeHtml(title)}</h3>
          ${providerFormatted ? `<p class="therapy-card__provider">with ${escapeHtml(providerFormatted)}</p>` : ''}

          <div class="rating-row" aria-label="Rated ${rating ? rating.toFixed(1) : '0.0'} out of 5">
            ${renderStars(rating)}
            <span>${rating ? rating.toFixed(1) : '0.0'} · ${reviewCount} reviews</span>
          </div>

          <p class="therapy-card__description" style="position:relative;margin:0;color:#344054;font-size:12.75px;line-height:1.42;display:-webkit-box;min-height:calc(1.42em * 3);max-height:calc(1.42em * 3);overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:3;">
            <span style="position:relative;z-index:1;">${escapeHtml(benefitText)}</span>
            <span aria-hidden="true" style="position:absolute;left:0;right:0;bottom:0;height:1.15em;background:linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,.84) 68%, #fff 100%);pointer-events:none;z-index:2;"></span>
          </p>

          <div class="therapy-card__meta">
            ${durationLabel ? `<span class="therapy-card__chip">${escapeHtml(durationLabel)}</span>` : ''}
            ${hasOnline ? `<span class="therapy-card__chip therapy-card__chip--online">${exclusiveOnline ? 'Exclusively online' : 'Online'}</span>` : ''}
            ${!exclusiveOnline && physicalLocations.length ? buildLocationChip(item) : ''}
          </div>

          <div class="therapy-card__availability ${hasOnline || availabilityDays ? 'has-availability' : 'needs-availability'}">
            <div class="therapy-card__availability-top">
              <div class="therapy-card__availability-label">
                <span class="wow-chip-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 22 6.5v12A2.5 2.5 0 0 1 19.5 21h-15A2.5 2.5 0 0 1 2 18.5v-12A2.5 2.5 0 0 1 4.5 4H6V3a1 1 0 0 1 1-1Zm12.5 8h-15v8.5a.5.5 0 0 0 .5.5h14a.5.5 0 0 0 .5-.5V10ZM5 6a.5.5 0 0 0-.5.5V8h15V6.5A.5.5 0 0 0 19 6H5Z"/></svg>
                </span>
                <span>${escapeHtml(calendarLabel)}</span>
              </div>
              <span class="therapy-card__availability-note">${escapeHtml(calendarNote)}</span>
            </div>

            ${availabilityDays ? `<div class="wow-day-strip" aria-label="Availability calendar">${availabilityDays}</div>` : ''}
          </div>
        </div>

        <footer class="therapy-card__footer">
          <div class="therapy-card__price">
            <small>${price !== null && item?.price_raw && Number(item.price_raw) > Number(price) ? 'From' : 'From'}</small>
            <strong>${escapeHtml(priceLabel || '£0.00')}</strong>
          </div>

          <div class="therapy-card__actions">
            <button type="button" class="btn-wow btn-wow--outline btn-sm js-add-to-cart js-open-cart"
              data-id="${escapeHtml(id)}"
              data-product-id="${escapeHtml(id)}"
              data-source-version="${escapeHtml(sourceVersion)}"
              data-title="${escapeHtml(title)}"
              data-price="${price !== null ? price.toFixed(2) : '0.00'}"
              data-image="${escapeHtml(imageUrl)}"
              data-url="${escapeHtml(url)}"
            >
              <span class="btn-label">Add to cart</span>
            </button>
            <button type="button" class="btn-wow btn-wow--cta btn-sm js-buy-now"
              data-id="${escapeHtml(id)}"
              data-product-id="${escapeHtml(id)}"
              data-source-version="${escapeHtml(sourceVersion)}"
              data-title="${escapeHtml(title)}"
              data-price="${price !== null ? price.toFixed(2) : '0.00'}"
              data-image="${escapeHtml(imageUrl)}"
              data-url="${escapeHtml(url)}"
              data-qty="1"
            >
              <span class="btn-label">Book</span>
            </button>
          </div>
        </footer>
      </article>
    </a>
  `;
}
