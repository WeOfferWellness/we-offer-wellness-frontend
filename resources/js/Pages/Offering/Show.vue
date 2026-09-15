<script setup>
import { Head } from '@inertiajs/vue3'
import SiteLayout from '@/Layouts/SiteLayout.vue'
import { useCart } from '@/stores/cart'
import { ref, computed, onMounted, watch } from 'vue'
import ReviewList from '@/Components/ReviewList.vue'
import BuyBoxLegacy from '@/Components/BuyBoxLegacy.vue'
import BuyBoxV3 from '@/Components/BuyBoxV3.vue'
import WowxpExperience from '@/Components/WowxpExperience.vue'
import RecRail from '@/Components/Recommendations/Rail.vue'
import { recordProductView, recordFormat, recordLocation, recordVendor } from '@/services/recs'

const props = defineProps({
  type: { type: String, required: true },
  product: { type: Object, required: true },
})

const siteName = 'We Offer Wellness®'

function fmt(n, c='GBP'){ try { return new Intl.NumberFormat(undefined, { style:'currency', currency:c }).format(Number(n)) } catch { return n } }
const cart = useCart()
const adding = ref(false)
const qty = ref(1)
const delivery = ref('evoucher') // 'evoucher' | 'giftpack'
const isLegacy = computed(() => {
  const opts = Array.isArray(props.product?.options) ? props.product.options : []
  const names = opts.map(o => String(o?.name || o?.meta_name || '').toLowerCase())
  return names.some(n => n.includes('session')) || names.some(n => n.includes('location')) || names.some(n => n.includes('person'))
})
async function addToCart(detail){
  if (adding.value) return
  adding.value = true
  const p = props.product
  const qtyVal = Math.max(1, Number(detail?.qty ?? qty.value) || 1)
  const unitPrice = (detail && detail.unitPricePence != null)
    ? (Number(detail.unitPricePence) / 100)
    : (detail?.unitPrice != null ? Number(detail.unitPrice) : (p.price || p.price_min))
  const meta = {
    ...(detail?.booking ? { booking: detail.booking } : {}),
    ...(detail?.selected ? { selected: detail.selected } : {}),
    ...(detail?.groupCount ? { groupCount: detail.groupCount } : {}),
    ...(detail?.variantId ? { variantId: detail.variantId } : {}),
    ...(detail?.reservationId ? { reservationId: detail.reservationId } : {}),
    ...(detail?.holdExpiresAt ? { holdExpiresAt: detail.holdExpiresAt } : {}),
    displayType: typeDisplayLabel.value,
    practitioner: practitionerName.value,
    format: detail?.selected?.find(sel => /online|person/i.test(sel)) || bookingFormat.value,
    duration: bookingDuration.value,
    location: detail?.location || bookingLocation.value,
    nextAvailability: bookingNextAvailability.value,
    source_version: p.source_version || 'legacy',
    product_id: p.id,
    variant_label: Array.isArray(detail?.selected) ? detail.selected.join(' • ') : null,
  }
  // Make cart items unique per variant selection so different prices don't merge
  const variantKey = detail?.variantId ? `v${detail.variantId}` : (detail?.selected ? `sel:${detail.selected.join('|')}` : 'base')
  const itemId = `${p.id}:${variantKey}`
  cart.add({
    id: itemId,
    product_id: p.id,
    variant_id: detail?.variantId || null,
    variant_label: Array.isArray(detail?.selected) ? detail.selected.join(' • ') : '',
    title: p.title,
    price: unitPrice,
    image: p.image,
    url: window.location.pathname,
    qty: qtyVal,
    meta,
  })
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || ''
    await fetch('/api/cart/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        id: p.id,
        qty: qtyVal,
        variant_id: detail?.variantId || null,
        variant_label: Array.isArray(detail?.selected) ? detail.selected.join(' • ') : '',
        source_version: p.source_version === 'v3' ? 'v3' : null,
      }),
    })
  } catch {}
  /* disabled analytics event */
  setTimeout(() => adding.value = false, 250)
}

// No payment/checkout trigger needed per request

const hasRange = computed(() => Number(props.product.price_max || 0) > Number(props.product.price_min || props.product.price || 0))
const priceLabel = computed(() => hasRange.value ? `From ${fmt(props.product.price_min || props.product.price, props.product.currency)}` : fmt(props.product.price, props.product.currency))

function formatDurationLabel(value) {
  if (!value) return null
  if (typeof value === 'number') {
    if (value >= 60 && value % 60 === 0) return `${value / 60} hr`
    return `${value} min`
  }
  const str = String(value)
  if (/hour|hr/i.test(str)) return str
  const num = parseInt(str.replace(/[^0-9]/g, ''), 10)
  if (!isNaN(num)) {
    return num >= 60 && num % 60 === 0 ? `${num / 60} hr` : `${num} min`
  }
  return str
}

const bookingDuration = computed(() => formatDurationLabel(props.product?.duration))
const bookingFormat = computed(() => {
  const mode = String(props.product?.mode || '').toLowerCase()
  if (mode.includes('online') && mode.includes('person')) return 'Online or in-person'
  if (mode.includes('online')) return 'Online'
  if (mode.includes('person')) return 'In-person'
  const locs = Array.isArray(props.product?.locations) ? props.product.locations.map(l => String(l || '').toLowerCase()) : []
  if (locs.length) {
    const hasOnline = locs.includes('online')
    const hasPhysical = locs.some(l => l !== 'online')
    if (hasOnline && hasPhysical) return 'Online or in-person'
    if (hasOnline) return 'Online'
    if (hasPhysical) return 'In-person'
  }
  return null
})
const bookingLocation = computed(() => props.product?.location || (Array.isArray(props.product?.locations) ? props.product.locations.find(Boolean) : null) || (bookingFormat.value === 'Online' ? 'Online' : null))
const bookingNextAvailability = computed(() => whenText())
const bookingHighlights = computed(() => {
  return [
    { label: 'Duration', value: bookingDuration.value },
    { label: 'Format', value: bookingFormat.value },
    { label: 'Location', value: bookingLocation.value },
    { label: 'Next availability', value: bookingNextAvailability.value },
  ].filter(item => !!item.value)
})

const typeDisplayLabel = computed(() => {
  const map = {
    therapy: 'Therapy',
    therapies: 'Therapy',
    class: 'Class',
    classes: 'Class',
    event: 'Event',
    events: 'Event',
    workshop: 'Workshop',
    workshops: 'Workshop',
    retreat: 'Retreat',
    retreats: 'Retreat',
  }
  const raw = String(props.type || props.product?.type || '').toLowerCase()
  return map[raw] || 'Session'
})

const practitionerName = computed(() => props.product?.practitioner?.name || null)

// SEO meta
function plain(text){ return String(text||'').replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim() }
const metaDescription = computed(() => {
  const p = props.product
  const src = p.summary || p.what_to_expect || p.description || p.body_html || ''
  const s = plain(src).slice(0, 300)
  return s.length > 160 ? (s.slice(0,157) + '…') : s
})
const pageTitle = computed(() => `${String(props.product?.title || 'Offering')} | ${siteName}`)
const canonical = computed(() => String(props.product?.url || ''))

function imageCandidate(value) {
  if (!value) return ''
  if (typeof value === 'string') return value
  if (typeof value === 'object') {
    return String(value.url || value.src || value.path || value.original_url || value.original || '')
  }
  return ''
}

const ogImage = computed(() => absoluteUrl(
  imageCandidate(props.product?.image)
  || imageCandidate(Array.isArray(props.product?.images) && props.product.images[0] ? props.product.images[0] : null)
  || imageCandidate(props.product?.coverMedia)
  || imageCandidate(props.product?.cover_media)
))

function absoluteUrl(value) {
  const raw = String(value || '').trim()
  if (!raw) return ''
  if (/^https?:\/\//i.test(raw)) return raw
  try {
    return new URL(raw, typeof window !== 'undefined' ? window.location.origin : 'https://www.weofferwellness.co.uk').toString()
  } catch {
    return raw
  }
}

function toInt(value) {
  const n = Number(value)
  return Number.isFinite(n) ? Math.trunc(n) : 0
}

function buildSchemaReviews() {
  const source = [
    ...(Array.isArray(props.product?.client_reviews) ? props.product.client_reviews : []),
    ...(Array.isArray(props.product?.reviews) ? props.product.reviews : []),
  ]

  const out = []
  const seen = new Set()

  for (const review of source) {
    const body = String(review?.body || review?.review || review?.review_text || '').trim()
    if (!body) continue
    let rating = toInt(review?.rating ?? review?.ratingValue)
    if (rating <= 0) rating = 5
    const author = String(review?.author || review?.user?.name || 'Verified customer').trim() || 'Verified customer'
    const key = `${author}|${rating}|${body}`
    if (seen.has(key)) continue
    seen.add(key)
    const node = {
      '@type': 'Review',
      'reviewBody': body,
      'reviewRating': {
        '@type': 'Rating',
        'ratingValue': rating,
        'bestRating': 5,
        'worstRating': 1,
      },
      'author': {
        '@type': 'Person',
        'name': author,
      },
    }
    const published = String(review?.date || review?.created_at || '').trim()
    if (published) node.datePublished = published
    out.push(node)
  }

  return out
}

// ----- Debug inspector (variant/price) -----
const dbgOptions = computed(() => Array.isArray(props.product?.options) ? props.product.options : [])
const dbgSel = ref([])
function initDebugSel(){ try { dbgSel.value = dbgOptions.value.map(o => (Array.isArray(o?.values) && o.values.length ? String(o.values[0]) : '')) } catch { dbgSel.value = [] } }
function normName(s){ return String(s||'').toLowerCase().replace(/[^a-z]+/g,'') }
const dbgLocOpt = computed(() => {
  const arr = Array.isArray(dbgOptions.value) ? dbgOptions.value : []
  // Prefer exact "location(s)" (normalized) match
  const exact = arr.find(o => normName(o?.name||o?.meta_name||'') === 'locations')
  if (exact) return exact
  // Fallback: first option that mentions location
  return arr.find(o => /location/i.test(String(o?.name||o?.meta_name||''))) || null
})
const dbgLocIdx = computed(() => {
  const arr = Array.isArray(dbgOptions.value) ? dbgOptions.value : []
  const opt = dbgLocOpt.value
  const idx = arr.indexOf(opt)
  return idx
})
// Options to render in the generic list (exclude Location(s), but keep original indices)
const dbgOptionsFiltered = computed(() => {
  const arr = Array.isArray(dbgOptions.value) ? dbgOptions.value : []
  return arr.map((opt, oi) => ({ opt, oi }))
    .filter(({ opt }) => {
      const nm = normName(opt?.name || opt?.meta_name || '')
      if (nm === 'locations') return false
      if (/location/i.test(String(opt?.name || opt?.meta_name || ''))) return false
      return true
    })
})
const dbgLocations = computed(() => {
  const opt = dbgLocOpt.value
  const optVals = opt && Array.isArray(opt.values) ? opt.values : []
  const fromOpts = optVals.map(v => String(v)).filter(Boolean)
  if (fromOpts.length) return fromOpts
  const locs = Array.isArray(props.product?.locations) ? props.product.locations : []
  return locs.map(v => String(v)).filter(Boolean)
})
const dbgSupportsOnline = computed(() => dbgLocations.value.some(v => /online/i.test(String(v))))
const dbgSupportsPhysical = computed(() => dbgLocations.value.some(v => !/online/i.test(String(v))))
const dbgLocation = ref('')
function initDbgLocation(){
  try{
    const idx = dbgLocIdx.value
    if (idx < 0) return
    const cur = String(dbgSel.value?.[idx] || '')
    // Default to a physical location if available, else Online if present
    if (cur) { dbgLocation.value = cur }
    else if (dbgSupportsPhysical.value) { dbgLocation.value = dbgLocations.value.find(v => !/online/i.test(String(v))) || '' }
    else if (dbgSupportsOnline.value) { dbgLocation.value = 'Online' }
  }catch{}
}
function setDbgLocation(lbl){
  const idx = dbgLocIdx.value
  if (idx < 0) return
  dbgLocation.value = String(lbl)
  dbgSel.value[idx] = String(lbl)
}
function dbgFindVariant(){
  try{
    const variants = Array.isArray(props.product?.variants) ? props.product.variants : []
    const want = (dbgSel.value || []).map(String)
    const arr = Array.isArray(dbgOptions.value) ? dbgOptions.value : []
    const pi = arr.findIndex(o => /person/i.test(String(o?.name||o?.meta_name||'')))
    const si = arr.findIndex(o => /session/i.test(String(o?.name||o?.meta_name||'')))
    const li = dbgLocIdx.value
    const norm = (s)=>String(s||'').toLowerCase().replace(/[^a-z0-9]+/g,'')
    const num = (s)=>{ const n=parseInt(String(s||'').replace(/[^0-9]/g,''),10); return isNaN(n)?null:n }

    const wantPeople = (pi>=0 ? num(want[pi]) : null)
    const wantSessions = (si>=0 ? String(want[si]||'') : '')
    const wantLoc = (li>=0 ? String(want[li]||'') : '')
    const wantIsOnline = /online/i.test(wantLoc)
    const locations = Array.isArray(dbgLocations.value) ? dbgLocations.value : []
    const sessionsOrdered = (si>=0 && Array.isArray(arr[si]?.values)) ? arr[si].values.map(v => String(v?.value ?? v)) : []

    function kvFor(v){
      try{
        const kv = {}
        const toks = Array.isArray(v?.options) ? v.options.map(String) : []
        for (const t of toks){
          const lc = String(t||'').toLowerCase()
          if (lc.includes('online')) kv.format = 'Online'
          if (lc.includes('in-person') || lc.includes('in person') || lc.includes('inperson')) kv.format = 'In-person'
          const n = num(t); if (n!=null && kv.people==null) kv.people = n
        }
        if (!kv.location){
          // Exact Online
          if (toks.some(t => norm(t)==='online')) kv.location = 'Online'
          else {
            // Fuzzy-match any known location label
            for (const loc of locations){
              const ln = norm(String(loc))
              if (!ln) continue
              const hit = toks.find(tt => { const tn=norm(tt); return tn && (tn===ln || tn.includes(ln) || ln.includes(tn)) })
              if (hit){ kv.location = String(loc); break }
            }
          }
        }
        if (!kv.format) kv.format = (kv.location==='Online' ? 'Online' : 'In-person')
        // Sessions: try exact match against option values from schema, else numeric fallback
        try{
          const sVals = (si>=0 && Array.isArray(arr[si]?.values)) ? arr[si].values.map(v => String(v?.value ?? v)) : []
          const exact = sVals.find(lbl => toks.some(t => String(t)===lbl))
          if (exact) kv.sessions = exact
          else {
            // Numeric fallback: map any number in tokens to sessions label with same number
            const wantN = num(wantSessions)
            if (wantN!=null){
              const tok = toks.find(t => num(t)===wantN)
              if (tok) {
                const cand = sVals.find(lbl => num(lbl)===wantN)
                kv.sessions = cand || String(tok)
              }
            } else {
              // Try any token number vs labels' numbers
              const tokNums = toks.map(t => num(t)).filter(n => n!=null)
              const cand = sVals.find(lbl => tokNums.some(n => num(lbl)===n))
              if (cand) kv.sessions = cand
            }
          }
        }catch{}
        return kv
      }catch{ return {} }
    }

    // Helper predicates
    const matchesSession = (v, sLbl) => {
      const kv = kvFor(v); const opts=v.options||[]; const wn=num(sLbl); if (!sLbl) return true; return kv.sessions ? (wn!=null ? num(kv.sessions)===wn : String(kv.sessions)===sLbl) : (wn!=null ? opts.some(t=>num(t)===wn) : opts.some(t=>String(t)===sLbl))
    }
    const matchesPeople = (v, n) => { if (n==null) return true; const kv = kvFor(v); const opts=v.options||[]; return (kv.people!=null && Number(kv.people)===Number(n)) || opts.some(t => num(t)===n) }
    const matchesLocation = (v, loc) => {
      if (!loc) return true
      const w = norm(loc)
      const kv = kvFor(v)
      const have = kv.location ? norm(String(kv.location)) : ''
      if (have) return have===w || have.includes(w) || w.includes(have)
      const opts=v.options||[]
      return opts.some(t=>{ const tn=norm(String(t)); return tn && (tn===w || tn.includes(w) || w.includes(tn)) })
    }

    // Location-first resolution: if a specific location is chosen, find best variant within that location
    try{
      if (wantLoc){
        let pool = variants.filter(v => matchesLocation(v, wantLoc))
        if (pool.length){
          if (wantSessions){ const hit = pool.filter(v => matchesSession(v, wantSessions)); if (hit.length) pool = hit }
          if (wantPeople!=null){ const hit = pool.filter(v => matchesPeople(v, wantPeople)); if (hit.length) pool = hit }
          if (pool.length === 1) return pool[0]
          if (sessionsOrdered.length){
            const rank = (v) => {
              const kv = kvFor(v); const lbl = String(kv.sessions || '')
              const idx = sessionsOrdered.indexOf(lbl)
              if (idx>=0) return idx
              const kn = num(lbl)
              if (kn!=null){ const cand = sessionsOrdered.findIndex(x => num(x)===kn); return cand>=0?cand:999 }
              return 999
            }
            pool = [...pool].sort((a,b)=>rank(a)-rank(b))
          }
          return pool[0]
        }
      }
    }catch{}

    // First, try to find an exact match for the explicitly selected dimensions
    try{
      let cands = variants.filter(v => Array.isArray(v?.options) && v.options.length)
      // Ignore explicit Format selection; rely on selected Location if any
      if (wantPeople!=null){
        cands = cands.filter(v => { const kv=kvFor(v); const opts=v.options||[]; return (kv.people!=null && kv.people===wantPeople) || opts.some(t=>num(t)===wantPeople) })
      }
      if (wantSessions){
        cands = cands.filter(v => { const kv=kvFor(v); const opts=v.options||[]; const wn=num(wantSessions); return kv.sessions ? (wn!=null ? num(kv.sessions)===wn : String(kv.sessions)===wantSessions) : (wn!=null ? opts.some(t=>num(t)===wn) : opts.some(t=>String(t)===wantSessions)) })
      }
      if (wantLoc){
        const w = norm(wantLoc)
        cands = cands.filter(v => { const kv=kvFor(v); const have = kv.location ? norm(String(kv.location)) : ''; if (have) return have===w || have.includes(w) || w.includes(have); const opts=v.options||[]; return opts.some(t=>{ const tn=norm(String(t)); return tn && (tn===w || tn.includes(w) || w.includes(tn)) }) })
      }
      if (cands.length === 1) return cands[0]
    }catch{}

    // Otherwise, score and pick the best variant (prefer sessions > people > location)
    let best = null
    let bestScore = -1
    for (const v of variants){
      const opts = Array.isArray(v?.options) ? v.options : []
      if (!opts.length) continue
      const kv = kvFor(v)
      // If a Location selection implies Online/Physical, filter accordingly
      if (li>=0 && wantLoc){
        const needOnline = wantIsOnline
        const isOnline = (kv.format==='Online') || opts.some(t => /online/i.test(String(t)))
        if (needOnline && !isOnline) continue
        if (!needOnline && isOnline) continue
      }
      // If a Sessions selection is present, require sessions to match (exact label or numeric)
      if (si>=0 && wantSessions){
        const wantN = num(wantSessions)
        let matches = false
        if (kv.sessions){
          matches = (wantN!=null ? (num(kv.sessions)===wantN) : (String(kv.sessions)===wantSessions))
        }
        if (!matches){
          // Fallback: match against raw tokens
          matches = (wantN!=null ? opts.some(t => num(t)===wantN) : opts.some(t => String(t)===wantSessions))
        }
        if (!matches) continue
      }
      // Base score
      let score = 0
      // Sessions score (heaviest)
      if (si>=0 && wantSessions){
        const wantN = num(wantSessions)
        if (kv.sessions){
          if (wantN!=null ? (num(kv.sessions)===wantN) : (String(kv.sessions)===wantSessions)) score += 6
        } else if (wantN!=null ? opts.some(t => num(t)===wantN) : opts.some(t => String(t)===wantSessions)) {
          score += 6
        }
      }
      // People score (medium)
      if (wantPeople!=null){
        if (kv.people!=null && kv.people===wantPeople) score += 4
        else if (opts.some(t => num(t)===wantPeople)) score += 2
      }
      // Location score (light)
      if (li>=0 && wantLoc){
        const w = norm(wantLoc)
        const have = kv.location ? norm(String(kv.location)) : ''
        if (have){
          if (have===w) score += 1
          else if (have.includes(w) || w.includes(have)) score += 0.5
        } else {
          // Try fuzzy against raw tokens, ignoring numeric-only tokens
          const hit = opts.some(t => { const tn=norm(String(t)); if(!tn || /^[0-9]+$/.test(tn)) return false; return tn===w || tn.includes(w) || w.includes(tn) })
          if (hit) score += 0.5
        }
      }
      if (score > bestScore){ best = v; bestScore = score }
    }
    return best || null
  }catch{ return null }
}
const dbgVariant = computed(() => dbgFindVariant())
const dbgPrice = computed(() => {
  // touch selection to ensure reactive updates even if same object ref is returned
  try { JSON.stringify(dbgSel.value) } catch {}
  const v = dbgVariant.value
  const raw = v?.price
  const n = Number(raw)
  if (Number.isFinite(n)) return fmt(n, props.product?.currency || 'GBP')
  return raw != null ? String(raw) : '—'
})
// Debug disabled: onMounted(() => { initDebugSel(); initDbgLocation() })
// URL <-> Debug inspector sync
function dbgSetFromVariantId(vid){
  try{
    const variants = Array.isArray(props.product?.variants) ? props.product.variants : []
    const v = variants.find(x => String(x?.id) === String(vid))
    if (!v || !Array.isArray(v?.options)) return false
    const want = v.options.map(String)
    const arr = Array.isArray(dbgOptions.value) ? [...dbgOptions.value] : []
    const sel = []
    for (let i=0;i<arr.length;i++) sel[i] = want[i] || (Array.isArray(arr[i]?.values) ? String(arr[i].values[0]) : '')
    dbgSel.value = sel
    const li = dbgLocIdx.value
    if (li>=0){ const loc = String(sel[li]||''); dbgLocation.value = loc }
    return true
  }catch{ return false }
}
function dbgSelectedFromUrl(){
  try{
    const url = new URL(window.location.href)
    const varId = url.searchParams.get('variant')
    const hasAny = url.searchParams.has('people') || url.searchParams.has('sessions') || url.searchParams.has('location')
    if (!hasAny && varId && dbgSetFromVariantId(varId)) return
    const arr = Array.isArray(dbgOptions.value) ? [...dbgOptions.value] : []
    const sel = []
    // no explicit format handling
    // people
    const pi = arr.findIndex(o => /person/i.test(String(o?.name||o?.meta_name||'')))
    if (pi>=0){ const pn = parseInt(String(url.searchParams.get('people')||''),10); if (!isNaN(pn)) sel[pi] = String(pn) }
    // sessions
    const si = arr.findIndex(o => /session/i.test(String(o?.name||o?.meta_name||'')))
    if (si>=0){ const s = String(url.searchParams.get('sessions')||''); if (s) sel[si] = s }
    // location param (preferred over inference)
    const li = dbgLocIdx.value
    const locParam = String(url.searchParams.get('location')||'')
    if (li>=0 && locParam){
      const list = dbgLocations.value || []
      const match = list.find(v => String(v).toLowerCase() === locParam.toLowerCase())
      if (match){
        sel[li] = match
        dbgLocation.value = match
        // location picked; no explicit format
      }
    }
    if (li>=0 && !sel[li]){
      const phys = dbgLocations.value.find(v => !/online/i.test(String(v))) || (dbgSupportsOnline.value ? 'Online' : '')
      sel[li] = phys
      dbgLocation.value = phys
    }
    // fill gaps with first values
    for (let i=0;i<arr.length;i++){ if (!sel[i]) sel[i] = (Array.isArray(arr[i]?.values) && arr[i].values[0] ? String(arr[i].values[0]) : '') }
    dbgSel.value = sel
  }catch{}
}
function updateDebugUrl(){
  try{
    const url = new URL(window.location.href)
    // Only keep variant param for debug sync to avoid polluting URL.
    // Clear other selection params possibly present from older logic.
    url.searchParams.delete('format')
    url.searchParams.delete('people')
    url.searchParams.delete('sessions')
    url.searchParams.delete('location')
    const vid = dbgVariant.value?.id
    if (vid!=null) url.searchParams.set('variant', String(vid))
    else url.searchParams.delete('variant')
    window.history.replaceState({}, '', url.toString())
  }catch{}
}
// Debug disabled: onMounted(() => { dbgSelectedFromUrl(); updateDebugUrl() })
// Debug disabled: watch(dbgSel, () => updateDebugUrl(), { deep: true })
// Debug disabled: watch(dbgLocation, () => updateDebugUrl())

// JSON-LD
const ldProduct = computed(() => {
  const p = props.product
  const images = Array.isArray(p.images) && p.images.length ? p.images : (p.image ? [p.image] : [])
  const schemaImages = images.map(absoluteUrl).filter(Boolean)
  const offerPrice = Number(p.price_min || p.price || 0)
  const reviewCount = Number(p.review_count || p.reviews_count || 0)
  const ratingValue = Number(p.rating || p.reviews_avg_rating || 0)
  const obj = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    '@id': canonical.value ? `${canonical.value}#product` : undefined,
    'name': p.title,
    'description': plain(p.summary || p.description || p.body_html || ''),
    'image': schemaImages,
    'url': canonical.value || undefined,
    'mainEntityOfPage': canonical.value || undefined,
    'category': p?.category?.name || undefined,
    'sku': p?.id ? String(p.id) : undefined,
    'brand': { '@type': 'Brand', 'name': 'We Offer Wellness', 'url': absoluteUrl('/') },
    'offers': offerPrice ? {
      '@type': 'Offer',
      'price': offerPrice,
      'priceCurrency': p.currency || 'GBP',
      'url': canonical.value || undefined,
      'availability': 'https://schema.org/InStock',
      'itemCondition': 'https://schema.org/NewCondition',
      'seller': { '@type': 'Organization', 'name': 'We Offer Wellness', 'url': absoluteUrl('/') },
    } : undefined,
  }
  const schemaReviews = buildSchemaReviews()
  const aggregateReviewCount = Math.max(reviewCount, schemaReviews.length)
  const aggregateRatingValue = ratingValue > 0 ? ratingValue : (aggregateReviewCount > 0 ? 5 : 0)
  if (aggregateReviewCount > 0) {
    obj.aggregateRating = {
      '@type': 'AggregateRating',
      'ratingValue': aggregateRatingValue.toFixed(1),
      'reviewCount': aggregateReviewCount,
      'bestRating': 5,
      'worstRating': 1,
    }
  }
  if (schemaReviews.length) {
    obj.review = schemaReviews
  }
  return obj
})

function parseEventDateTime(dateValue, timeValue) {
  if (!dateValue) return null
  const raw = timeValue
    ? `${dateValue}T${timeValue}:00`
    : (/[T\s]/.test(dateValue) ? dateValue : `${dateValue}T00:00:00`)
  const parsed = new Date(raw)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function schemaDateTimeValue(dateValue, timeValue) {
  if (!dateValue) return undefined
  if (!timeValue) return dateValue
  const parsed = parseEventDateTime(dateValue, timeValue)
  if (!parsed) return dateValue
  const pad = (value) => String(Math.trunc(Math.abs(value))).padStart(2, '0')
  const offsetMinutes = -parsed.getTimezoneOffset()
  const sign = offsetMinutes >= 0 ? '+' : '-'
  const absOffset = Math.abs(offsetMinutes)
  const offsetHours = pad(Math.floor(absOffset / 60))
  const offsetMins = pad(absOffset % 60)
  return `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())}T${pad(parsed.getHours())}:${pad(parsed.getMinutes())}:${pad(parsed.getSeconds())}${sign}${offsetHours}:${offsetMins}`
}

function formatEventRange(startDate, startTime, endDate, endTime) {
  const start = parseEventDateTime(startDate, startTime)
  const end = parseEventDateTime(endDate || startDate, endTime || startTime)
  if (!start || !end) return null
  const sameYear = start.getFullYear() === end.getFullYear()
  const startDateLabel = start.toLocaleDateString(undefined, { month: 'short', day: 'numeric', ...(sameYear ? {} : { year: 'numeric' }) })
  const endDateLabel = end.toLocaleDateString(undefined, { month: 'short', day: 'numeric', ...(sameYear ? {} : { year: 'numeric' }) })
  if (startTime || endTime) {
    const startTimeLabel = start.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
    const endTimeLabel = end.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
    if (start.toDateString() === end.toDateString()) {
      return `${startDateLabel}, ${startTimeLabel} – ${endTimeLabel}`
    }
    return `${startDateLabel}, ${startTimeLabel} – ${endDateLabel}, ${endTimeLabel}`
  }
  if (start.toDateString() === end.toDateString()) {
    return startDateLabel
  }
  return `${startDateLabel} – ${endDateLabel}`
}

const ldEvent = computed(() => {
  const p = props.product
  const hasDate = !!(p?.date || p?.start_date || p?.start_time || p?.end_time)
  if (!hasDate) return null
  const loc = (Array.isArray(p?.locations) ? p.locations : []).find(v => String(v||'').trim()) || (p.location || '')
  const attendance = (String(p?.mode||'').toLowerCase() === 'online' || String(loc).toLowerCase() === 'online')
    ? 'https://schema.org/OnlineEventAttendanceMode'
    : 'https://schema.org/OfflineEventAttendanceMode'
  const schemaReviews = buildSchemaReviews()
  const reviewCount = Number(p.review_count || p.reviews_count || 0)
  const ratingValue = Number(p.rating || p.reviews_avg_rating || 0)
  const aggregateReviewCount = Math.max(reviewCount, schemaReviews.length)
  const aggregateRatingValue = ratingValue > 0 ? ratingValue : (aggregateReviewCount > 0 ? 5 : 0)
  const data = {
    '@context': 'https://schema.org',
    '@type': 'Event',
    '@id': canonical.value ? `${canonical.value}#event` : undefined,
    'name': p.title,
    'eventAttendanceMode': attendance,
    'startDate': schemaDateTimeValue(p.start_date || p.date, p.start_time),
    'endDate': schemaDateTimeValue(p.end_date || p.start_date || p.date, p.end_time),
    'eventStatus': 'https://schema.org/EventScheduled',
    'organizer': { '@type': 'Organization', 'name': 'We Offer Wellness', 'url': (typeof window!== 'undefined' ? window.location.origin : '') },
  }
  if (canonical.value) data.url = canonical.value
  if (aggregateReviewCount > 0) {
    data.aggregateRating = {
      '@type': 'AggregateRating',
      'ratingValue': aggregateRatingValue.toFixed(1),
      'reviewCount': aggregateReviewCount,
      'bestRating': 5,
      'worstRating': 1,
    }
  }
  if (schemaReviews.length) data.review = schemaReviews
  return data
})

function whenText(){
  const p = props.product
  const eventRange = formatEventRange(p.start_date || p.date, p.start_time, p.end_date || p.start_date || p.date, p.end_time)
  if (eventRange) {
    return eventRange
  }
  if (p.date){ try { return new Date(p.date).toLocaleString(undefined, { dateStyle:'medium', timeStyle:'short' }) } catch { return null } }
  return null
}

function goToSafety(){
  try {
    document.querySelector('#safety')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  } catch {}
}

// Gallery state
const idx = ref(0)
const images = computed(() => Array.isArray(props.product.images) && props.product.images.length ? props.product.images : (props.product.image ? [props.product.image] : []))
function sel(i){ idx.value = i }

// Recommendations now rendered via RecRail component
const tab = ref('overview')
onMounted(async () => {
  try { recordProductView(props.product); if (props.product?.vendor_id) recordVendor(props.product.vendor_id) } catch {}
  // Track format/location selections from page events
  try{
    const onFmt = (ev)=>{ const f=String(ev?.detail?.format||''); if (f) recordFormat(f) }
    const onLoc = (ev)=>{ const s=String(ev?.detail?.label||''); if (s) recordLocation(s) }
    window.addEventListener('wow:format-selected', onFmt)
    window.addEventListener('wow:location-picked', onLoc)
    // store handlers for cleanup
    try { window.__wow_rec_onFmt = onFmt; window.__wow_rec_onLoc = onLoc } catch {}
  }catch{}
})
try{ onUnmounted(()=>{ try{ if (window.__wow_rec_onFmt) window.removeEventListener('wow:format-selected', window.__wow_rec_onFmt) }catch{}; try{ if (window.__wow_rec_onLoc) window.removeEventListener('wow:location-picked', window.__wow_rec_onLoc) }catch{} }) }catch{}

// Tabs visibility depending on available content
const hasIncluded = computed(() => !!(props.product?.included && String(props.product.included).trim().length > 0))
watch(tab, (t) => {
  if ((t === 'included' || t === 'need') && !hasIncluded.value) tab.value = 'overview'
})

// Map: locations list + Mapbox map with pins
let mapboxgl
const mapEl = ref(null)
const locListEl = ref(null)
let map = null
const locs = computed(() => Array.isArray(props.product?.locations) ? props.product.locations.filter(Boolean) : [])
const geo = ref([]) // [{name, lng, lat}]
const activeLoc = ref(-1)

function updateMapHeight(){
  try {
    const h = locListEl.value?.offsetHeight || 400
    if (mapEl.value) { mapEl.value.style.height = h + 'px' }
    if (map) { map.resize() }
  } catch {}
}

async function ensureMap() {
  if (!locs.value.length || !mapEl.value) return
  try {
    if (!mapboxgl) {
      const mod = await import('mapbox-gl')
      mapboxgl = mod.default || mod
      mapboxgl.accessToken = window.WOW_MAPS_KEY || ''
    }
    updateMapHeight()
    if (!map) {
      map = new mapboxgl.Map({ container: mapEl.value, style: 'mapbox://styles/mapbox/streets-v11', center: [-0.12,51.5], zoom: 9 })
      setTimeout(updateMapHeight, 50)
    }
    // Geocode all locations once
    if (geo.value.length === 0) {
      const key = window.WOW_MAPS_KEY || ''
      const results = []
      for (const name of locs.value) {
        try {
          const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(name)}.json`)
          url.searchParams.set('access_token', key)
          url.searchParams.set('limit', '1')
          const res = await fetch(url)
          const j = await res.json()
          const feat = j?.features?.[0]
          if (feat && Array.isArray(feat.center)) {
            results.push({ name, lng: feat.center[0], lat: feat.center[1] })
          }
        } catch {}
      }
      geo.value = results
    }
    // Add markers and fit bounds
    const bounds = new mapboxgl.LngLatBounds()
    for (const g of geo.value) {
      bounds.extend([g.lng, g.lat])
      new mapboxgl.Marker().setLngLat([g.lng, g.lat]).addTo(map)
    }
    if (!bounds.isEmpty()) map.fitBounds(bounds, { padding: 40 })
    updateMapHeight()
  } catch {}
}

function zoomTo(idx) {
  activeLoc.value = idx
  const g = geo.value[idx]
  if (map && g) map.flyTo({ center: [g.lng, g.lat], zoom: 13 })
}

watch(tab, (t) => { if (t === 'locations') ensureMap() })
onMounted(() => { window.addEventListener('resize', updateMapHeight, { passive:true }) })
// Not strictly necessary, but safe cleanup
try{ onUnmounted(() => { window.removeEventListener('resize', updateMapHeight) }) }catch{}
</script>

<template>
  <SiteLayout>
    <Head :title="pageTitle">
      <meta name="description" :content="metaDescription" />
      <link v-if="canonical" rel="canonical" :href="canonical" />
      <meta property="og:type" content="product" />
      <meta property="og:site_name" :content="siteName" />
      <meta property="og:title" :content="pageTitle" />
      <meta property="og:description" :content="metaDescription" />
      <meta v-if="canonical" property="og:url" :content="canonical" />
      <meta v-if="ogImage" property="og:image" :content="ogImage" />
      <meta v-if="ogImage" property="og:image:alt" :content="product.title" />
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" :content="pageTitle" />
      <meta name="twitter:description" :content="metaDescription" />
      <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
      <meta v-if="ogImage" name="twitter:image:alt" :content="product.title" />
      <script type="application/ld+json">{{ JSON.stringify(ldProduct) }}</script>
      <script v-if="ldEvent" type="application/ld+json">{{ JSON.stringify(ldEvent) }}</script>
    </Head>
    <section class="section">
      <div class="container-page">
        <!-- Debug inspector removed for production -->

        <!-- Breadcrumbs -->
        <div class="mb-3 d-flex align-items-center gap-2 text-sm text-ink-600">
          <a href="/" class="muted-link">Home</a>
          <span class="text-ink-400">/</span>
          <a :href="'/' + type" class="muted-link text-capitalize">{{ type }}</a>
        </div>

        <div class="grid gap-6 off-grid pb-adjust">
          <main id="content" class="main-col">
            <WowxpExperience :product="product" :type="type" />
          </main>

          <!-- Sticky purchase panel (right on desktop) choose legacy vs v3 -->
          <aside class="booking-card" id="booking-card">
            <div class="booking-card__head">
              <div class="booking-card__price">{{ priceLabel }}</div>
              <div class="booking-card__meta" v-if="bookingHighlights.length">
                <div v-for="item in bookingHighlights" :key="item.label">
                  <small>{{ item.label }}</small>
                  <strong>{{ item.value }}</strong>
                </div>
              </div>
            </div>
            <component :is="isLegacy ? BuyBoxLegacy : BuyBoxV3" :product="product" @add="addToCart" />
            <div class="booking-card__safety">
              <button type="button" class="booking-card__safety-link" @click="goToSafety">
                <span class="booking-card__icon" aria-hidden="true"></span>
                Check safety &amp; contraindications
              </button>
            </div>
          </aside>

        </div>
        <!-- Full-width recommendations row below Reviews/main grid -->
        <div class="mt-6 pb-adjust">
          <RecRail :product="product" :type="type" :sections="['you_may_also_like','people_also_booked','more_from_provider','near_you','trending_category']" />
        </div>
      </div>
    </section>

    <!-- Mobile bar handled by BuyBoxAdvanced -->
  </SiteLayout>
</template>

<style scoped>
.h3{ font-size:1.75rem; font-weight:600 }
.h-max{ height: max-content }
.thumb{ border:1px solid var(--ink-200); border-radius:8px; overflow:hidden; padding:0; background:#fff; height:64px; width:96px; flex:0 0 auto; opacity:.8 }
.thumb img{ display:block; width:100%; height:100%; object-fit:cover }
.thumb.active{ outline:2px solid var(--brand-500); opacity:1 }
.trust-row{ display:flex; flex-wrap:wrap; gap:10px; }
.trust-item{ display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background: var(--ink-100); color: var(--ink-700); font-size:.85rem }
.rec-item{ transition: box-shadow .2s ease, transform .2s ease; }
.rec-item:hover{ box-shadow: 0 10px 18px rgba(0,0,0,.08); transform: translateY(-2px) }
/* Layout tweaks: left sticky panel and responsive grid */
.off-grid{ display:grid; grid-template-columns: 1fr; margin-bottom: 20px; }
@media (min-width: 1024px){ /* lg */
  .off-grid{ grid-template-columns: minmax(0,1fr) 380px; align-items: start; }
}
.sticky-right{ position: sticky; top: 5.5rem; }
.booking-card{ position:sticky; top:5.5rem; background:#fff; border:1px solid var(--ink-200); border-radius:20px; padding:1.25rem; box-shadow:0 20px 60px rgba(15,23,42,.08); display:flex; flex-direction:column; gap:1rem }
.booking-card__price{ font-size:1.75rem; font-weight:700 }
.booking-card__meta{ display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.75rem; margin-top:.5rem }
.booking-card__meta small{ display:block; color:var(--ink-500); text-transform:uppercase; letter-spacing:.08em; font-size:.75rem }
.booking-card__meta strong{ font-size:1rem; color:var(--ink-800) }
.booking-card__safety{ border-top:1px solid var(--ink-100); padding-top:.75rem }
.booking-card__safety-link{ border:0; background:transparent; color:var(--ink-600); display:flex; gap:.35rem; align-items:center; font-size:.9rem; font-weight:600; text-decoration:underline; padding:0 }
.booking-card__icon{ width:14px; height:14px; background:var(--ink-500); -webkit-mask:url('https://cdn.jsdelivr.net/npm/heroicons@2.1.5/20/solid/shield-exclamation.svg') no-repeat center/contain; mask:url('https://cdn.jsdelivr.net/npm/heroicons@2.1.5/20/solid/shield-exclamation.svg') no-repeat center/contain }
/* Mobile sticky buy bar */
.buybar-mobile{ position: fixed; left:0; right:0; bottom:0; z-index:60; background:#fff; border-top:1px solid var(--ink-200); }
.pb-adjust{ padding-bottom: 84px; }
@media (min-width: 768px){ .pb-adjust{ padding-bottom: 0 } .buybar-mobile{ display:none } }
/* CTA panel options */
.btn-ghost.active-opt{ background: var(--ink-100); }
.flex-1{ flex: 1 1 auto; }
.qty{ display:inline-flex; align-items:center; gap:6px; border:1px solid var(--ink-200); border-radius:10px; padding:2px 6px }
.qty input{ width:48px; text-align:center; border:0; outline:none; background:transparent }
/* Tabs */
.tabs{ display:flex; gap:2px; border-bottom:1px solid var(--ink-200); padding: 0 .5rem; }
.tab{ appearance:none; background:transparent; border:0; padding:.75rem 1rem; color: var(--ink-700); border-bottom:2px solid transparent }
.tab.active{ color: var(--ink-900); border-bottom-color: var(--brand-600); font-weight:600 }
.accordion{ display:grid; gap:10px }
.acc-item{ background:#fff; border:1px solid var(--ink-200); border-radius:10px; padding:.5rem .75rem }
.acc-item > summary{ cursor:pointer; font-weight:600 }
.card-plain{ border: 0 !important; box-shadow: none !important; }
/* Locations split layout */
.loc-grid{ display:grid; grid-template-columns: minmax(0,1fr) 520px; gap: 16px; align-items: stretch }
@media (max-width: 1024px){ .loc-grid{ grid-template-columns: 1fr } }
.loc-list{ display:flex; flex-direction:column; gap:8px; }
.loc-item{ text-align:left; border:1px solid var(--ink-200); background:#fff; border-radius:10px; padding:.6rem .75rem; color:var(--ink-800) }
.loc-item:hover{ background: var(--ink-100) }
.loc-item.active{ outline: 2px solid var(--brand-500); background: #fff }
.map-pane{ position: relative; }
.mapbox{ display:block; width:100%; height:100%; min-height:380px; }
</style>
