const FLOW_VERSION = 'v3'
const DEFAULT_CURRENCY = 'GBP'
const GA4_EVENT_ALIASES = {
  wow_v3_add_to_cart: 'add_to_cart',
  wow_v3_remove_from_cart: 'remove_from_cart',
  wow_v3_view_cart: 'view_cart',
  wow_v3_begin_checkout: 'begin_checkout',
  wow_v3_payment_success: 'purchase',
  wow_v3_purchase_success: 'purchase',
}

const PURCHASE_STORAGE_PREFIX = 'wow_purchase_sent:'

function getWindow() {
  return typeof window !== 'undefined' ? window : null
}

function coerceNumber(value, fallback = 0) {
  const num = Number(value)
  return Number.isFinite(num) ? num : fallback
}

function cleanObject(input) {
  const out = {}
  Object.entries(input || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    out[key] = value
  })
  return out
}

function toCommerceItem(item, index = 0) {
  if (!item || typeof item !== 'object') return null

  const rawId = item.id ?? item.product_id ?? item.productId ?? item.variant_id ?? item.variantId ?? item.sku ?? index
  const title = item.title ?? item.name ?? item.item_name ?? 'Item'
  const price = item.price ?? item.unit ?? item.unit_amount ?? item.value ?? 0
  const qty = item.qty ?? item.quantity ?? 1
  const variantLabel = item.variant_label ?? item.variantLabel ?? item.item_variant ?? ''
  const sourceVersion = item.source_version ?? item.sourceVersion ?? item.meta?.source_version ?? null
  const productId = item.product_id ?? item.productId ?? item.meta?.product_id ?? null
  const variantId = item.variant_id ?? item.variantId ?? item.meta?.variant_id ?? null
  const providerId = item.provider_id ?? item.providerId ?? item.vendor_id ?? item.meta?.provider_id ?? null
  const offeringId = item.offering_id ?? item.offeringId ?? item.meta?.offering_id ?? null
  const catalogueType = item.catalogue_type ?? item.catalogueType ?? item.meta?.catalogue_type ?? null
  const modality = item.modality ?? item.meta?.modality ?? null
  const bookingSource = item.booking_source ?? item.meta?.booking_source ?? null
  const checkoutSource = item.checkout_source ?? item.meta?.checkout_source ?? null

  return cleanObject({
    item_id: String(rawId),
    item_name: String(title),
    item_variant: variantLabel ? String(variantLabel) : undefined,
    item_category: sourceVersion ? String(sourceVersion) : undefined,
    price: coerceNumber(price, 0),
    quantity: Math.max(1, coerceNumber(qty, 1)),
    product_id: productId != null ? String(productId) : undefined,
    variant_id: variantId != null ? String(variantId) : undefined,
    source_version: sourceVersion ? String(sourceVersion) : undefined,
    provider_id: providerId != null ? String(providerId) : undefined,
    offering_id: offeringId != null ? String(offeringId) : undefined,
    catalogue_type: catalogueType ? String(catalogueType) : undefined,
    modality: modality ? String(modality) : undefined,
    booking_source: bookingSource ? String(bookingSource) : undefined,
    checkout_source: checkoutSource ? String(checkoutSource) : undefined,
  })
}

function buildCommerceItems(items) {
  if (!Array.isArray(items)) return []
  return items.map((item, index) => toCommerceItem(item, index)).filter(Boolean)
}

function track(eventName, params = {}) {
  const win = getWindow()
  if (!win) return false

  const payload = cleanObject({
    flow_version: FLOW_VERSION,
    wow_event_name: eventName,
    ...params,
  })

  try {
    win.dataLayer = win.dataLayer || []
    if (typeof win.gtag !== 'function') {
      win.gtag = function gtagStub(){
        try {
          win.dataLayer.push(arguments)
        } catch {}
      }
    }
  } catch {}

  try {
    if (typeof win.gtag === 'function') {
      win.gtag('event', eventName, payload)
      return true
    }
  } catch {}

  try {
    win.dataLayer = win.dataLayer || []
    win.dataLayer.push(['event', eventName, payload])
    return true
  } catch {}

  return false
}

function trackPageView(params = {}) {
  const win = getWindow()
  return track('page_view', {
    page_location: params.page_location || (win ? `${win.location.pathname}${win.location.search}${win.location.hash}` : ''),
    page_title: params.page_title || (typeof document !== 'undefined' ? document.title : ''),
    ...params,
  })
}

function trackCommerce(eventName, params = {}) {
  const items = buildCommerceItems(params.items || [])
  const explicitValue = params.value
  const computedValue = items.reduce((sum, item) => sum + (coerceNumber(item.price, 0) * coerceNumber(item.quantity, 1)), 0)
  const currency = params.currency || DEFAULT_CURRENCY
  const itemCount = params.item_count != null
    ? coerceNumber(params.item_count, items.reduce((sum, item) => sum + coerceNumber(item.quantity, 1), 0))
    : items.reduce((sum, item) => sum + coerceNumber(item.quantity, 1), 0)

  const canonicalEvent = GA4_EVENT_ALIASES[eventName] || eventName
  return track(canonicalEvent, cleanObject({
    ...params,
    currency,
    value: explicitValue != null ? coerceNumber(explicitValue, computedValue) : computedValue,
    item_count: itemCount,
    items,
  }))
}

function trackPurchase(params = {}) {
  const transactionId = String(params.transaction_id || '').trim()
  if (!transactionId) return false

  const win = getWindow()
  const key = `${PURCHASE_STORAGE_PREFIX}${transactionId}`
  try {
    if (win?.sessionStorage?.getItem(key) === '1') return false
    win?.sessionStorage?.setItem(key, '1')
  } catch (_) {
    // Server-side claim remains the authoritative idempotency boundary.
  }

  return trackCommerce('purchase', params)
}

const WOWAnalytics = {
  flowVersion: FLOW_VERSION,
  track,
  trackPageView,
  trackCommerce,
  trackPurchase,
  buildCommerceItems,
}

const win = getWindow()
if (win) {
  win.WOWAnalytics = WOWAnalytics
}

export { FLOW_VERSION, buildCommerceItems, track, trackCommerce, trackPurchase, trackPageView }
export default WOWAnalytics
