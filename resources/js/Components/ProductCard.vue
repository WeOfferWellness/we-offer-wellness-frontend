<script setup>
import { computed } from 'vue'
import StoreProductCard from '@/Components/StoreProductCard.vue'

const props = defineProps({
  product: { type: Object, required: true },
  fluid: { type: Boolean, default: false },
})

const PREMIUM_ROSETTE_URL = 'https://studio.weofferwellness.co.uk/storage/uploads/images/78aa908f-334b-45c0-9220-1c4d84053c5e.png'
const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const DAY_ORDER = [1, 2, 3, 4, 5, 6, 0]

function text(value) {
  return String(value ?? '').trim()
}

function titleCase(value) {
  return text(value).replace(/\s+/g, ' ').replace(/\w\S*/g, (word) => word[0].toUpperCase() + word.slice(1).toLowerCase())
}

function slug(value) {
  return text(value).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
}

function publicProductUrl(product) {
  const explicit = text(product?.url)
  if (explicit) return explicit
  const format = { therapy: 'therapies', class: 'classes', event: 'events', workshop: 'workshops', retreat: 'retreats' }[productType(product)] || 'therapies'
  const modality = slug(product?.category?.slug || product?.category?.name || product?.category_name || product?.category_label || 'wellness')
  const offering = slug(product?.slug || product?.handle || product?.title || product?.name || product?.id)
  return `/${format}/${modality}/${offering}`
}

function plainText(value) {
  const source = text(value)
  if (!source) return ''
  if (typeof document === 'undefined') return source.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/\s+/g, ' ').trim()
  const element = document.createElement('div')
  element.innerHTML = source
  return text(element.textContent).replace(/\s+/g, ' ')
}

function money(value) {
  let amount = Number(value)
  if (!Number.isFinite(amount) || amount <= 0) return null
  if (amount > 1000 && amount % 100 === 0) amount /= 100
  return new Intl.NumberFormat('en-GB', { style: 'currency', currency: props.product?.currency || 'GBP', maximumFractionDigits: 2 }).format(amount)
}

function planKey(value) {
  const raw = text(value).toLowerCase().replace(/[_\s]+/g, '-')
  return {
    core: 'business-accelerator',
    businessaccelerator: 'business-accelerator',
    'business-accelerator-package': 'business-accelerator',
  }[raw] || raw
}

function productType(product) {
  const raw = text(
    product?.type?.name || product?.type_label || product?.type_name
    || (typeof product?.type === 'string' ? product.type : '')
    || product?.product_type,
  ).toLowerCase()
  const category = text(product?.category?.name || product?.category_name || product?.category_label || product?.category).toLowerCase()
  const source = `${raw} ${category} ${text(product?.url)}`
  if (/(event|workshop|retreat)/.test(source)) return source.includes('retreat') ? 'retreat' : (source.includes('workshop') ? 'workshop' : 'event')
  if (source.includes('class')) return 'class'
  return 'therapy'
}

function typeLabel(type) {
  return { therapy: 'Therapy', event: 'Event', workshop: 'Workshop', retreat: 'Retreat', class: 'Class' }[type] || 'Therapy'
}

function categoryLabel(product, fallback) {
  const value = product?.category?.name || product?.category?.label || product?.category?.title || product?.category?.slug || product?.offering?.category?.name || product?.offering_category_name || product?.offering_category || product?.category_name || product?.category_label || (typeof product?.category === 'string' ? product.category : '')
  return text(value) ? titleCase(value) : fallback
}

function locations(product) {
  const source = Array.isArray(product?.locations) && product.locations.length
    ? product.locations
    : [product?.matched_location_label || product?.location || product?.location_name || product?.venue]
  return source.map((location) => {
    if (typeof location === 'object' && location) return text(location.name || location.label || location.title || location.city || location.location)
    return text(location)
  }).filter(Boolean)
}

function hasOnline(product) {
  return product?.online_only === true
    || (Array.isArray(product?.channels) && product.channels.some((channel) => text(channel).toLowerCase() === 'online'))
    || text(product?.format).toLowerCase().includes('online')
    || (Array.isArray(product?.tags) && product.tags.some((tag) => text(tag).toLowerCase() === 'online'))
    || locations(product).some((location) => location.toLowerCase() === 'online')
}

function availabilityDays(product) {
  const allSources = [
    product?.availability_days,
    product?.availability_calendar,
    product?.vendor?.availability_days,
    product?.vendor_details?.availability_days,
    product?.vendor_details?.defaultAvailability,
    product?.vendor_details?.default_availability,
    product?.vendor?.user?.defaultAvailability,
    product?.vendor?.user?.default_availability,
    product?.user?.defaultAvailability,
    product?.user?.default_availability,
  ]
  const weekly = product?.vendor?.availability?.weekly_rules || product?.vendor_details?.availability?.weekly_rules || product?.availability?.weekly_rules || {}
  const lookup = { mon: 1, monday: 1, tue: 2, tues: 2, tuesday: 2, wed: 3, wednesday: 3, thu: 4, thur: 4, thursday: 4, fri: 5, friday: 5, sat: 6, saturday: 6, sun: 0, sunday: 0 }
  const active = new Set()

  Object.entries(weekly).forEach(([day, rule]) => {
    if (rule?.enabled === true && Array.isArray(rule.windows) && rule.windows.length) active.add(lookup[day.toLowerCase()])
  })
  allSources.forEach((source) => {
    const entries = Array.isArray(source) ? source : Object.values(source || {})
    entries.forEach((entry) => {
      if (typeof entry === 'number' || typeof entry === 'string') {
        const day = /^\d+$/.test(String(entry)) ? Number(entry) : lookup[text(entry).toLowerCase()]
        if (Number.isInteger(day)) active.add(day === 7 ? 0 : day)
        return
      }
      const enabled = entry?.is_available ?? entry?.available ?? entry?.enabled ?? true
      if ([false, 0, '0', 'false', 'no'].includes(enabled)) return
      const rawDay = entry?.day_of_week ?? entry?.day ?? entry?.weekday ?? entry?.date_day
      const day = /^\d+$/.test(String(rawDay)) ? Number(rawDay) : lookup[text(rawDay).toLowerCase()]
      if (Number.isInteger(day)) active.add(day === 7 ? 0 : day)
    })
  })
  return DAY_ORDER.filter((day) => active.has(day))
}

function parseDate(value, time = '') {
  const raw = text(value)
  if (!raw) return null
  const date = new Date(time ? `${raw}T${time}` : raw.includes('T') ? raw : `${raw}T00:00:00`)
  return Number.isNaN(date.getTime()) ? null : date
}

function firstEventDate(product) {
  const meta = product?.meta_json && typeof product.meta_json === 'object' ? product.meta_json : {}
  const sources = [
    product?.when?.event,
    product?.event,
    meta?.when?.event,
    meta?.event,
    product,
    meta,
  ].filter((source) => source && typeof source === 'object')
  const dates = []

  for (const source of sources) {
    const candidates = [
      source,
      ...(Array.isArray(source?.dates) ? source.dates : []),
      ...(Array.isArray(source?.upcoming_dates) ? source.upcoming_dates : []),
      ...(Array.isArray(source?.occurrences) ? source.occurrences : []),
      ...(Array.isArray(source?.schedule?.days) ? source.schedule.days : []),
      ...(Array.isArray(source?.schedule?.occurrences) ? source.schedule.occurrences : []),
    ]

    for (const candidate of candidates) {
      if (!candidate) continue
      const value = typeof candidate === 'object'
        ? (candidate.starts_at || candidate.start_date || candidate.date || candidate.start || candidate.day)
        : candidate
      if (/^(?:mon|tue|wed|thu|fri|sat|sun)(?:day)?$/i.test(text(value)) || /^\d{1,2}$/.test(text(value))) continue
      const time = typeof candidate === 'object'
        ? (candidate.start_time || candidate.time || source.start_time || '')
        : source.start_time || ''
      const parsed = parseDate(value, time)
      if (parsed) dates.push(parsed)
    }
  }

  if (!dates.length) return null
  dates.sort((left, right) => left.getTime() - right.getTime())
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return dates.find((date) => date >= today) || dates[0]
}

function eventDate(product) {
  return firstEventDate(product)
}

function eventRange(product) {
  const event = product?.when?.event || product?.event || product
  const start = eventDate(product)
  const end = parseDate(event?.end_date || event?.finish_date || product?.end_date, event?.end_time || product?.end_time)
  if (!start) return 'View event dates'
  const date = start.toLocaleDateString('en-GB', { month: 'short', day: 'numeric' })
  const startTime = event?.start_time || product?.start_time
  if (!end) return startTime ? `${date}, ${start.toLocaleTimeString('en-GB', { hour: 'numeric', minute: '2-digit' })}` : date
  const endDate = end.toLocaleDateString('en-GB', { month: 'short', day: 'numeric' })
  const endTime = end.toLocaleTimeString('en-GB', { hour: 'numeric', minute: '2-digit' })
  return start.toDateString() === end.toDateString()
    ? `${date}, ${start.toLocaleTimeString('en-GB', { hour: 'numeric', minute: '2-digit' })} - ${endTime}`
    : `${date} - ${endDate}`
}

const product = computed(() => props.product || {})
const isStoreProduct = computed(() => [
  product.value.kind,
  product.value.product_kind,
  product.value.source_type,
].some((value) => ['physical_product', 'store_product'].includes(text(value).toLowerCase())) || Boolean(product.value.store_product_id))
const type = computed(() => productType(product.value))
const isEvent = computed(() => ['event', 'workshop', 'retreat'].includes(type.value))
const isGift = computed(() => /gift\s*card|giftcard|voucher|e-?gift/i.test([product.value.title, product.value.name, product.value.slug, product.value.category?.name, product.value.product_type].map(text).join(' ')))
const title = computed(() => titleCase(product.value.title || product.value.name || 'Untitled'))
const provider = computed(() => {
  const name = product.value.vendor_name || product.value.practitioner_name || product.value.provider || product.value.vendor?.name || product.value.vendor?.vendor_name || product.value.vendor_details?.name || product.value.vendor_details?.vendor_name
  return text(name) ? titleCase(name) : ''
})
const image = computed(() => product.value.image || product.value.image_url || product.value.featured_image || product.value.media?.[0]?.url || product.value.media?.[0]?.original_url || '')
const url = computed(() => publicProductUrl(product.value))
const price = computed(() => money(product.value.variants_min_price ?? product.value.price_min ?? product.value.price ?? product.value.base_price) || '£0')
const rating = computed(() => Number(product.value.rating ?? product.value.reviews_avg_rating ?? product.value.vendor_review_rating ?? product.value.vendor?.review_summary?.rating ?? 0))
const reviews = computed(() => Number(product.value.review_count ?? product.value.reviews_count ?? product.value.vendor_review_count ?? product.value.vendor?.review_summary?.count ?? 0))
const filledStars = computed(() => Math.max(0, Math.min(5, Math.round(rating.value))))
const category = computed(() => categoryLabel(product.value, typeLabel(type.value)))
const days = computed(() => availabilityDays(product.value))
const online = computed(() => hasOnline(product.value))
const physicalLocations = computed(() => locations(product.value).filter((location) => location.toLowerCase() !== 'online'))
const location = computed(() => online.value && !physicalLocations.value.length ? 'Online' : (physicalLocations.value[0] || (online.value ? 'Online' : 'In person')))
const distance = computed(() => text(product.value.distance || product.value.distance_label))
const locationLine = computed(() => distance.value ? `${location.value} · ${distance.value}` : location.value)
const availability = computed(() => {
  if (!days.value.length) return { label: 'Contact to check availability', today: false }
  if (days.value.length === 7) return { label: 'Available every day', today: true }
  const today = new Date().getDay()
  for (let offset = 0; offset < 7; offset += 1) {
    const day = (today + offset) % 7
    if (!days.value.includes(day)) continue
    return { label: offset === 0 ? 'Available today' : (offset === 1 ? 'Available tomorrow' : `Next available: ${DAY_NAMES[day]}`), today: offset === 0 }
  }
  return { label: 'Contact to book', today: false }
})
const isBusinessAccelerator = computed(() => planKey(product.value.plan_key || product.value.plan_label || product.value.vendor?.plan_key || product.value.vendor?.plan_label || product.value.vendor_details?.plan_key || product.value.vendor_details?.plan_label || product.value.vendor?.user?.tier?.tier) === 'business-accelerator')
const signal = computed(() => text(product.value.fomo_text) || (online.value && !physicalLocations.value.length ? 'Exclusively online' : (physicalLocations.value.length > 1 ? `+${physicalLocations.value.length - 1} more locations` : (text(product.value.next_label || product.value.next) ? `Next: ${text(product.value.next_label || product.value.next)}` : ''))))
const eventStart = computed(() => eventDate(product.value))
const eventMonth = computed(() => eventStart.value ? eventStart.value.toLocaleDateString('en-GB', { month: 'short' }) : 'Soon')
const eventDay = computed(() => eventStart.value ? String(eventStart.value.getDate()).padStart(2, '0') : '—')
const description = computed(() => plainText(product.value.benefit || product.value.summary || product.value.description_short || product.value.description || product.value.excerpt || product.value.body_html || product.value.what_to_expect || product.value.included))
const trackingId = computed(() => Number(product.value.id) > 0 ? Number(product.value.id) : null)
const trackingSource = computed(() => {
  if (isStoreProduct.value) return 'store'
  return text(product.value.source_version).toLowerCase() === 'v1-v2' ? 'legacy' : 'v3'
})
const rankingRequestId = computed(() => text(product.value.ranking_request_id) || null)

</script>

<template>
  <StoreProductCard v-if="isStoreProduct" :product="product" :fluid="fluid" />
  <article
    v-else-if="isGift"
    class="wow49-card wow49-card--gift"
    :class="{ 'wow49-card--fluid': fluid }"
    :aria-label="`Gift card ${title}`"
    :data-product-id="trackingId"
    :data-source-version="trackingSource"
    :data-ranking-request-id="rankingRequestId"
  >
    <a href="/giftcards" class="wow49-card__link" :aria-label="`Buy ${title}`"></a>
    <div class="wow49-card__gift-media">
      <img v-if="image" :src="image" :alt="title" loading="lazy">
      <span class="wow49-card__gift-badge">Digital gift card</span>
    </div>
    <div class="wow49-card__body">
      <h3 class="wow49-card__title">{{ title }}</h3>
      <p class="wow49-card__provider">Instant email delivery</p>
    </div>
    <footer class="wow49-card__footer"><div><small>From</small><strong>{{ price }}</strong></div><a href="/giftcards" class="wow49-card__button">BUY GIFT CARD</a></footer>
  </article>

  <article
    v-else-if="isEvent"
    class="wow49-card wow49-card--event"
    :class="{ 'wow49-card--fluid': fluid }"
    :aria-label="`${typeLabel(type)} card ${title}`"
    :data-product-id="trackingId"
    :data-source-version="trackingSource"
    :data-ranking-request-id="rankingRequestId"
  >
    <a :href="url" class="wow49-card__link" :aria-label="`View and book ${title}`"></a>
    <div class="wow49-card__event-background"><img v-if="image" :src="image" :alt="title" loading="lazy"></div>
    <span class="wow49-card__date"><b>{{ eventMonth }}</b><strong>{{ eventDay }}</strong></span>
    <img v-if="isBusinessAccelerator" class="wow49-card__rosette" :src="PREMIUM_ROSETTE_URL" alt="Business Accelerator partner">
    <div class="wow49-card__event-content">
      <div class="wow49-card__tags"><span>{{ category }}</span><span class="is-type">{{ typeLabel(type) }}</span></div>
      <h3 class="wow49-card__title">{{ title }}</h3>
      <p v-if="provider" class="wow49-card__provider">with {{ provider }}</p>
      <p class="wow49-card__location"><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11Z"/><circle cx="12" cy="10" r="3"/></svg>{{ locationLine }}</p>
      <div class="wow49-card__event-bottom"><div><small>From</small><strong>{{ price }}</strong></div><a :href="url" class="wow49-card__button">VIEW & BOOK</a></div>
    </div>
  </article>

  <article
    v-else
    class="wow49-card"
    :class="{ 'wow49-card--fluid': fluid }"
    :aria-label="`Offering card ${title}`"
    :data-product-id="trackingId"
    :data-source-version="trackingSource"
    :data-ranking-request-id="rankingRequestId"
  >
    <a :href="url" class="wow49-card__link" :aria-label="`View and book ${title}`"></a>
    <div class="wow49-card__media">
      <img v-if="image" :src="image" :alt="title" loading="lazy">
      <span v-if="signal" class="wow49-card__signal">{{ signal }}</span>
      <img v-if="isBusinessAccelerator" class="wow49-card__rosette" :src="PREMIUM_ROSETTE_URL" alt="Business Accelerator partner">
      <div class="wow49-card__tags"><span>{{ category }}</span><span class="is-type">{{ typeLabel(type) }}</span></div>
    </div>
    <div class="wow49-card__body">
      <h3 class="wow49-card__title">{{ title }}</h3>
      <p v-if="provider" class="wow49-card__provider">with {{ provider }}</p>
      <div class="wow49-card__rating"><span class="wow49-card__stars">{{ '★'.repeat(filledStars) }}{{ '☆'.repeat(5 - filledStars) }}</span><span>{{ rating.toFixed(1) }} · {{ reviews ? `${reviews} reviews` : 'Be the first to review' }}</span></div>
      <p class="wow49-card__location"><svg v-if="online && !physicalLocations.length" viewBox="0 0 24 24" fill="none"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6.95 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1" fill="currentColor" stroke="none"/></svg><svg v-else viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11Z"/><circle cx="12" cy="10" r="3"/></svg>{{ locationLine }}</p>
      <p v-if="description" class="wow49-card__description">{{ description }}</p>
      <div class="wow49-card__availability" :class="{ 'is-today': availability.today }"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span>{{ availability.label }}</span></div>
    </div>
    <footer class="wow49-card__footer"><div><small>From</small><strong>{{ price }}</strong></div><a :href="url" class="wow49-card__button">VIEW & BOOK</a></footer>
  </article>
</template>

<style scoped>
.wow49-card{position:relative;display:flex;flex-direction:column;width:100%;min-width:290px;max-width:300px;height:430px;min-height:430px;overflow:hidden;border:1px solid rgba(16,24,40,.1);border-radius:13px;background:#fff;box-shadow:0 4px 16px rgba(16,24,40,.05);color:#101828;font-family:"DM Sans",system-ui,sans-serif;transition:transform 180ms ease,border-color 180ms ease,box-shadow 180ms ease}.wow49-card--fluid{max-width:300px}.wow49-card:hover,.wow49-card:focus-within{transform:translateY(-2px);border-color:rgba(79,147,129,.42);box-shadow:0 20px 48px rgba(16,24,40,.085)}.wow49-card__link{position:absolute;inset:0;z-index:1}.wow49-card__media{position:relative;height:145px;flex:0 0 145px;overflow:hidden;background:#eef2f4}.wow49-card__media>img:first-child,.wow49-card__event-background>img,.wow49-card__gift-media>img{width:100%;height:100%;display:block;object-fit:cover;transition:transform 240ms ease}.wow49-card:hover .wow49-card__media>img:first-child,.wow49-card:hover .wow49-card__event-background>img,.wow49-card:hover .wow49-card__gift-media>img{transform:scale(1.035)}.wow49-card__signal{position:absolute;top:10px;left:10px;z-index:2;max-width:calc(100% - 20px);overflow:hidden;padding:5px 10px;border-radius:999px;background:rgba(255,247,237,.94);color:#b54708;font-size:11px;font-weight:700;white-space:nowrap;text-overflow:ellipsis}.wow49-card__rosette{position:absolute;top:8px;right:8px;z-index:3;width:48px;height:48px;object-fit:contain}.wow49-card__tags{position:absolute;bottom:10px;left:10px;z-index:2;display:flex;gap:4px}.wow49-card__tags span{display:inline-flex;align-items:center;height:22px;padding:0 7px;border:1px solid rgba(240,200,121,.9);border-radius:999px;background:rgba(255,229,179,.96);color:#6f4b10;font-size:10px;font-weight:700}.wow49-card__tags .is-type{border-color:rgba(199,216,251,.9);background:rgba(232,240,255,.96);color:#254a85}.wow49-card__body{display:flex;flex:1;flex-direction:column;gap:4px;min-height:0;overflow:hidden;padding:11px 13px 10px}.wow49-card__title{display:-webkit-box;min-height:2.4em;margin:0;overflow:hidden;color:#101828;font-size:18px;font-weight:300;line-height:1.2;letter-spacing:-.04em;-webkit-box-orient:vertical;-webkit-line-clamp:2}.wow49-card:hover .wow49-card__title{color:#4f9381}.wow49-card__provider,.wow49-card__location{margin:0;color:#667085;font-size:12px}.wow49-card__location{display:flex;align-items:center;gap:4px}.wow49-card__location svg,.wow49-card__availability svg{width:11px;height:11px;flex-shrink:0;stroke:currentColor;stroke-width:1.9}.wow49-card__rating{display:flex;align-items:center;gap:5px;color:#344054;font-size:12px}.wow49-card__stars{color:#f5c84b;font-size:12px;letter-spacing:-1px}.wow49-card__description{display:-webkit-box;min-height:2.9em;margin:0;overflow:hidden;color:#667085;font-size:12.5px;line-height:1.45;-webkit-box-orient:vertical;-webkit-line-clamp:2}.wow49-card__availability{display:flex;align-items:center;gap:5px;margin-top:auto;padding:5px 8px;border-radius:7px;background:#f6f8fa;color:#344054;font-size:12px;font-weight:600}.wow49-card__availability.is-today{background:#eaf5f1;color:#2f6f60}.wow49-card__footer{position:relative;z-index:2;display:flex;flex-shrink:0;align-items:center;justify-content:space-between;gap:8px;padding:9px 13px 11px;border-top:1px solid #edf0f2}.wow49-card__footer small,.wow49-card__event-bottom small{display:block;color:#98a2b3;font-size:11px;line-height:1}.wow49-card__footer strong{color:#101828;font-size:20px;font-weight:400;letter-spacing:-.05em}.wow49-card__button{position:relative;z-index:3;display:inline-flex;align-items:center;justify-content:center;height:36px;padding:0 14px;border:0;border-radius:4px;background:#4f9381;color:#fff;font:400 12.5px "DM Sans",system-ui,sans-serif;letter-spacing:.01em;text-decoration:none;white-space:nowrap;cursor:pointer}.wow49-card__event-background{position:absolute;inset:0;background:#20312d}.wow49-card__event-background::after{position:absolute;inset:0;content:"";background:linear-gradient(to top,rgba(10,18,30,.9),rgba(10,18,30,.28) 60%,rgba(0,0,0,.05))}.wow49-card__date{position:absolute;top:12px;left:12px;z-index:3;display:flex;width:48px;height:54px;flex-direction:column;align-items:center;justify-content:center;border-radius:10px;background:rgba(255,255,255,.97);box-shadow:0 6px 18px rgba(0,0,0,.2)}.wow49-card__date b{color:#4f9381;font-size:9px;letter-spacing:.08em;text-transform:uppercase}.wow49-card__date strong{color:#101828;font-size:21px;line-height:1.1;letter-spacing:-.04em}.wow49-card__event-content{position:relative;z-index:2;display:flex;flex:1;flex-direction:column;justify-content:flex-end;padding:14px;color:#fff}.wow49-card--event .wow49-card__tags{position:static;margin-bottom:7px}.wow49-card--event .wow49-card__title{color:#fff}.wow49-card--event .wow49-card__provider,.wow49-card--event .wow49-card__location{color:rgba(255,255,255,.8)}.wow49-card--event .wow49-card__provider{margin-bottom:6px}.wow49-card--event .wow49-card__location{margin-bottom:10px;font-size:11.5px}.wow49-card__event-bottom{display:flex;align-items:center;justify-content:space-between;gap:8px}.wow49-card__event-bottom small{color:rgba(255,255,255,.6);font-size:10px}.wow49-card__event-bottom strong{color:#fff;font-size:19px;font-weight:400}.wow49-card--gift .wow49-card__gift-media{position:relative;display:flex;height:70%;flex:0 0 70%;overflow:hidden;background:linear-gradient(135deg,#eff8f5,#fff)}.wow49-card__gift-badge{position:absolute;top:10px;left:10px;z-index:2;padding:5px 9px;border:1px solid rgba(79,147,129,.2);border-radius:999px;background:rgba(232,245,241,.95);color:#2f6f60;font-size:10.5px;font-weight:700}.wow49-card--gift .wow49-card__body{gap:2px;padding:12px 14px 10px}.wow49-card--gift .wow49-card__title{min-height:0}@media(max-width:560px){.wow49-card{min-width:0;height:360px;min-height:360px}.wow49-card__media{height:138px;flex-basis:138px}.wow49-card__title{font-size:15px}.wow49-card__description{display:none}.wow49-card__body{padding:10px 11px 9px}.wow49-card__footer{padding:9px 11px 10px}.wow49-card__footer strong{font-size:18px}.wow49-card__button{height:34px;padding:0 10px;font-size:10.5px}.wow49-card--event .wow49-card__event-content{padding:11px}.wow49-card__rosette{width:40px;height:40px}}
</style>
