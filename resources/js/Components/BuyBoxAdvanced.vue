<script setup>
import { onMounted } from 'vue'
import { Modal, Toast } from 'bootstrap'
import { useCart } from '@/stores/cart'

const emit = defineEmits(['add','buy'])
const props = defineProps({
  product: { type: Object, required: true },
  version: { type: String, default: 'auto' }, // 'auto'|'legacy'|'v3'
})

function normalizePlanKey(value) {
  return String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '_')
}

function getPractitionerBookingHeader(product) {
  const practitioner = product?.practitioner || null
  const planKey = normalizePlanKey(practitioner?.plan_key || practitioner?.plan_label)
  const planLabel = String(practitioner?.plan_label || '').trim().toLowerCase()
  const isPaidPlan = Boolean(practitioner?.is_paid_plan) || (!['starter', ''].includes(planKey) && planLabel !== 'starter')
  const firstName = String(practitioner?.first_name || practitioner?.name || '').trim().split(/\s+/)[0] || 'the practitioner'
  const title = isPaidPlan && product?.title ? String(product.title) : 'Discovery Call — We Offer Wellness®'

  return {
    label: 'Select a Date & Time',
    title,
    subtitle: isPaidPlan ? `with ${firstName}` : null,
    photo: isPaidPlan ? (practitioner?.photo || null) : null,
  }
}

const bookingHeader = getPractitionerBookingHeader(props.product)

onMounted(() => {
  try {
  // ---------- Build a local product model compatible with the template ----------
  const p = props.product || {}
  function normalizePennies(x){ const n = Number(x); if (!isFinite(n)) return 0; return Math.round(n * 100) }

  function isLegacyOptions(prod){
    const names = (prod.options||[]).map(o => String(o?.name||o?.meta_name||'').toLowerCase())
    return names.some(n => n.includes('person')) || names.some(n => n.includes('session')) || names.some(n => n.includes('location'))
  }

  function buildV3Model(prod){
    const hasOpts = Array.isArray(prod.options) && prod.options.length
    const hasVars = Array.isArray(prod.variants) && prod.variants.length
    // Derive Format from locations/mode when options are missing
    const locs = Array.isArray(prod.locations) ? prod.locations : []
    const normLocs = locs.map(v => String(v).toLowerCase())
    const hasOnlineOnly = normLocs.length>0 && normLocs.every(x => x === 'online')
    const hasPhysicalOnly = normLocs.length>0 && normLocs.every(x => x !== 'online')
    const hasBoth = normLocs.includes('online') && normLocs.some(x=>x!=='online')
    let derivedFormat = ['In-person','Online']
    if (hasOnlineOnly) derivedFormat = ['Online']
    else if (hasPhysicalOnly) derivedFormat = ['In-person']
    else if (hasBoth) derivedFormat = ['Online'] // prefer Online when both present per guidance
    else if (String(prod.mode||'').toLowerCase()==='online') derivedFormat = ['Online']
    else if (String(prod.mode||'').toLowerCase().includes('person')) derivedFormat = ['In-person']

    const fallbackOptions = [ { name:'Format', values: derivedFormat }, { name:'People', values:['1 Person','2 Persons','3+ Group'] } ]
    const basePounds = Number(prod.price_min ?? prod.price ?? 0)
    const basePennies = Math.max(0, Math.round(basePounds * 100))
    const onlinePennies = Math.round(basePennies * 0.92)
    const fallbackVariants = [
      { id: 'ip-1', options: ['In-person','1 Person'], price: basePennies || 35000, compare: 0, available: true },
      { id: 'ip-2', options: ['In-person','2 Persons'], price: (basePennies||35000)*2 - 5000, compare: (basePennies||35000)*2, available: true },
      { id: 'ip-g', options: ['In-person','3+ Group'], price: (basePennies||35000)*3 - 10000, compare: 0, available: true },
      { id: 'on-1', options: ['Online','1 Person'], price: onlinePennies || 32000, compare: 0, available: true },
      { id: 'on-2', options: ['Online','2 Persons'], price: (onlinePennies||32000)*2 - 4000, compare: (onlinePennies||32000)*2, available: true },
      { id: 'on-g', options: ['Online','3+ Group'], price: (onlinePennies||32000)*3 - 8000, compare: 0, available: true },
    ]
    const variants = hasVars ? prod.variants.map(v => { const px = normalizePennies(v.price); return { id: v.id, options: v.options || [], price: px, compare: normalizePennies(v.compare_at_price || v.compare), available: (v.available === false) ? false : (px > 0) } }) : fallbackVariants
    return {
      rating: Number(prod.rating || prod.avg_rating || 0) || 0,
      ratingCount: Number(prod.review_count || prod.ratings_count || 0) || 0,
      options: hasOpts ? prod.options : fallbackOptions,
      variants,
      groupMeta: null,
      rawLocations: Array.isArray(prod.locations) ? prod.locations : [],
    }
  }

  function buildLegacyModel(prod){
    const options = Array.isArray(prod.options) ? prod.options : []
    const variants = Array.isArray(prod.variants) ? prod.variants : []
    // Identify option indices
    const nameOf = (o)=>String(o?.name||o?.meta_name||'').toLowerCase()
    const locIdx = options.findIndex(o => nameOf(o).includes('location'))
    const perIdx = options.findIndex(o => {
      const n=nameOf(o); return n.includes('person')
    })
    const sesIdx = options.findIndex(o => nameOf(o).includes('session'))
    // Gather values
    const formatValues = []
    let rawLocationLabels = []
    if (locIdx >= 0) {
      const locValues = options[locIdx]?.values || []
      const hasOnline = locValues.some(v => String(v?.value||v).toLowerCase() === 'online' || String(v).toLowerCase() === 'online')
      const hasPhysical = locValues.some(v => String(v?.value||v).toLowerCase() !== 'online')
      if (hasOnline) formatValues.push('Online')
      if (hasPhysical) formatValues.push('In-person')
      rawLocationLabels = locValues.map(v => String(v?.value||v)).filter(Boolean)
      // If both exist and you prefer Online-only, uncomment next line
      // if (hasOnline && hasPhysical) { formatValues.splice( formatValues.indexOf('In-person'), 1 ) }
    }

    const personValuesRaw = perIdx>=0 ? (options[perIdx]?.values || []) : []
    const nums = personValuesRaw.map(v => parseInt(String(v?.value||v).replace(/[^0-9]/g,''),10)).filter(n => !isNaN(n)).sort((a,b)=>a-b)
    const peopleValues = []
    if (nums.includes(1)) peopleValues.push('1 Person')
    if (nums.includes(2)) peopleValues.push('2 Persons')
    const groupNums = nums.filter(n=>n>=3)
    let groupMin = null, groupMax = null
    if (groupNums.length){
      peopleValues.push('3+ Group')
      groupMin = Math.min(...groupNums)
      groupMax = Math.max(...groupNums)
    }

    // Sessions values (raw labels)
    const sessionsValues = sesIdx>=0 ? (options[sesIdx]?.values || []) : []
    const sessionsLabels = Array.isArray(sessionsValues) ? sessionsValues.map(v => String(v?.value||v)) : []

    // Normalize variants directly from backend variants, mapping to [Format? , People , Sessions?]
    const shapedVariants = []
    const defaultPeopleLabel = peopleValues[0] || '1 Person'
    const defaultFormatLabel = (formatValues.length===1 ? formatValues[0] : (formatValues.includes('Online') ? 'Online' : formatValues[0])) || null
    const defaultSessionLabel = sessionsLabels[0] || null
    const variantCount = variants.length
    let primaryDim = null
    if (sessionsLabels.length > 1 && sessionsLabels.length === variantCount) primaryDim = 'sessions'
    else if (peopleValues.length > 1 && peopleValues.length === variantCount) primaryDim = 'people'
    else if (formatValues.length > 1 && formatValues.length === variantCount) primaryDim = 'format'
    const canMapGrid = (!primaryDim && sessionsLabels.length>1 && peopleValues.length>1 && (sessionsLabels.length*peopleValues.length===variantCount) && formatValues.length<=1)

    variants.forEach((v, vi) => {
      const vopts = Array.isArray(v.options) ? v.options : []
      const px = normalizePennies(v.price)
      const cmp = normalizePennies(v.compare_at_price || v.compare || 0)
      let fmtLabel = null
      let peopleLabel = null
      let peopleTok = null
      let sesLabel = null
      let locLabel = null

      // Use backend-provided option positions when available
      if (locIdx>=0 && typeof vopts[locIdx] === 'string') {
        const locVal = String(vopts[locIdx])
        fmtLabel = (/online/i.test(locVal) ? 'Online' : 'In-person')
        locLabel = (/online/i.test(locVal) ? 'Online' : locVal)
      }
      if (perIdx>=0 && vopts[perIdx]!=null){
        const num = parseInt(String(vopts[perIdx]).replace(/[^0-9]/g,''),10)
        if (!isNaN(num)){
          if (num >= 3) { peopleLabel = '3+ Group'; peopleTok = String(num) }
          else { peopleLabel = (num===1?'1 Person':'2 Persons'); peopleTok = peopleLabel }
        }
      }
      if (sesIdx>=0 && vopts[sesIdx]!=null){ sesLabel = String(vopts[sesIdx]) }

      // Fallback mapping by index when backend options are empty or incomplete
      if (!fmtLabel && formatValues.length===1) fmtLabel = defaultFormatLabel
      if (!peopleLabel) peopleLabel = defaultPeopleLabel
      if (!sesLabel) sesLabel = defaultSessionLabel

      // Try to infer Sessions label from raw tokens if still unknown
      if (!sesLabel && sessionsLabels.length) {
        try {
          const tokens = vopts.map(x => String(x||''))
          const exact = sessionsLabels.find(lbl => tokens.includes(lbl))
          if (exact) sesLabel = exact
          else {
            // Numeric fallback: map numbers in tokens to numbers in session labels
            const tokNums = tokens.map(t => parseInt(String(t).replace(/[^0-9]/g,''),10)).filter(n => !isNaN(n))
            if (tokNums.length) {
              const match = sessionsLabels.find(lbl => tokNums.some(n => parseInt(String(lbl).replace(/[^0-9]/g,''),10) === n))
              if (match) sesLabel = match
            }
          }
        } catch {}
      }

      if (primaryDim === 'sessions' && sessionsLabels.length>vi) sesLabel = sessionsLabels[vi]
      else if (primaryDim === 'people' && peopleValues.length>vi) peopleLabel = peopleValues[vi]
      else if (primaryDim === 'format' && formatValues.length>vi) fmtLabel = formatValues[vi]
      else if (canMapGrid){
        const S = sessionsLabels.length
        const P = peopleValues.length
        const sIdx = vi % S
        const pIdx = Math.floor(vi / S) % P
        if (sessionsLabels.length) sesLabel = sessionsLabels[sIdx]
        if (peopleValues.length) peopleLabel = peopleValues[pIdx]
      }

      const norm = []
      if (fmtLabel) norm.push(fmtLabel)
      if (peopleTok || peopleLabel) norm.push(String(peopleTok || peopleLabel))
      if (sesLabel) norm.push(String(sesLabel))
      const shaped = { id: v.id, options: norm, price: px, compare: cmp, available: px>0 }
      // Attach a kv map so we can match by location even if it's not in options[]
      try{
        const kv = {}
        if (fmtLabel) kv.format = fmtLabel
        if (peopleTok || peopleLabel) kv.people = String(peopleTok || peopleLabel)
        if (sesLabel) kv.sessions = String(sesLabel)
        if (!kv.location) {
          if (locLabel) kv.location = locLabel
          else {
            // Attempt to resolve from raw tokens
            const tokens = vopts.map(x=>String(x||'').trim().toLowerCase())
            if (tokens.includes('online')) kv.location = 'Online'
            else if (rawLocationLabels && rawLocationLabels.length){
              const lower = rawLocationLabels.map(x=>String(x||'').trim().toLowerCase())
              const hit = tokens.find(t => lower.includes(t))
              if (hit){ kv.location = rawLocationLabels[ lower.indexOf(hit) ] }
            }
          }
        }
        shaped.kv = kv
      }catch{}
      shapedVariants.push(shaped)
    })

    const normalizedOptions = []
    if (formatValues.length > 0) normalizedOptions.push({ name:'Format', values: formatValues })
    if (peopleValues.length === 0) peopleValues.push('1 Person')
    normalizedOptions.push({ name:'People', values: peopleValues })
    if (sessionsLabels.length>0) normalizedOptions.push({ name:'Sessions', values: sessionsLabels })

    return {
      rating: Number(prod.rating || prod.avg_rating || 0) || 0,
      ratingCount: Number(prod.review_count || prod.ratings_count || 0) || 0,
      options: normalizedOptions,
      variants: shapedVariants,
      groupMeta: { min: groupMin ?? 3, max: groupMax ?? 10, counts: Array.from(new Set(groupNums||[])).sort((a,b)=>a-b), prices: {}, compare: {} },
      rawLocations: rawLocationLabels,
    }
  }

  const product = (props.version==='legacy') ? buildLegacyModel(p) : (props.version==='v3') ? buildV3Model(p) : (isLegacyOptions(p) ? buildLegacyModel(p) : buildV3Model(p))
  const rawVariants = Array.isArray(p.variants)? p.variants : []

  // ---------- Money & general helpers ----------
  function fmt(c){ try { return new Intl.NumberFormat('en-GB',{style:'currency',currency:'GBP'}).format(c/100) } catch { return '£'+(c/100).toFixed(2) } }
  const dfmt = (px)=>{ try { const n=Number(px||0); return `${(n/100).toFixed(2)}` } catch { return String(px) } }

  // Debug logging removed in production to avoid heavy console operations

  // ---------- URL sync helpers ----------
  function canonName(n){
    const s=String(n||'').toLowerCase()
    if (s.includes('person')) return 'people'
    if (s.includes('session')) return 'sessions'
    if (s.includes('format')) return 'format'
    if (s.includes('location')) return 'location'
    return s.replace(/[^a-z0-9]+/g,'_')
  }
  function selectedFromUrl(){
    try{
      const url=new URL(window.location.href)
      // If explicit Shopify-style variant id is present, prefer it
      const variantIdRaw = url.searchParams.get('variant')
      if (variantIdRaw){
        const sel = selectionForVariantId(variantIdRaw)
        if (sel && sel.length){ return sel }
      }
      if (url.searchParams.has('format')) { try { window.__wow_formatTouched = true } catch {} }
      return product.options.map(o=>{
        const key=canonName(o.name)
        const v=url.searchParams.get(key)
        if (!v) return (o.values?.[0]??null)
        let dec=decodeURIComponent(v)
        const values = o.values || []
        // Normalize common aliases
        if (key==='format'){
          const lc = dec.toLowerCase()
          if (lc.includes('online')) dec = 'Online'
          else dec = 'In-person'
        }
        if (key==='people'){
          if (dec==='1' || /(^|\b)1(\b|$)/.test(dec)) dec = '1 Person'
          else if (dec==='2' || /(^|\b)2(\b|$)/.test(dec)) dec = '2 Persons'
          else if (/3|group/i.test(dec)) dec = '3+ Group'
        }
        if (key==='sessions'){
          // Try exact match first, then prefix/contains match on numbers
          if (!values.includes(dec)){
            const num = dec.replace(/[^0-9]+/g,'')
            const cand = values.find(x => x===dec) || values.find(x => x.toLowerCase()===dec.toLowerCase()) || (num? values.find(x => x.replace(/[^0-9]+/g,'')===num) : null)
            if (cand) dec = cand
          }
        }
        return values.includes(dec)?dec:(values?.[0]??null)
      })
    }catch{ return product.options.map(o=>o.values?.[0]??null) }
  }
  function indexByName(target){
    const tn=String(target||'').toLowerCase()
    return product.options.findIndex(o=>String(o?.name||'').toLowerCase()===tn)
  }
  function peopleLabelFor(n){ if(n===1) return '1 Person'; if(n===2) return '2 Persons'; if(n>=3) return '3+ Group'; return null }
  function selectionForVariantId(vid){
    try{
      const v = (product.variants||[]).find(x=>String(x.id)===String(vid))
      if(!v) return null
      const sel = product.options.map(o=>o.values?.[0] ?? null)
      const kv = ensureVariantKV(v)
      const defs = optionDefs()
      defs.forEach(d=>{ if (kv[d.key]) sel[d.idx] = String(kv[d.key]) })
      return sel
    }catch{ return null }
  }
  function updateUrlFromSelection(){
    try{
      const url=new URL(window.location.href)
      // Remove all option-derived params (format/people/sessions/locations/etc.)
      try { (product.options||[]).forEach(o => { const key=canonName(o.name); url.searchParams.delete(key) }) } catch {}
      url.searchParams.delete('format')
      url.searchParams.delete('people')
      url.searchParams.delete('sessions')
      url.searchParams.delete('location')
      url.searchParams.delete('group')
      // Include resolved variant id (prefer current state's variant id)
      const vid = state.variant?.id ?? resolveRawVariantId(state.selected||[])
      if (vid!=null) url.searchParams.set('variant', String(vid))
      else url.searchParams.delete('variant')
      window.history.replaceState({}, '', url.toString())
    }catch{}
  }

  // ---------- UI State ----------
  // Track format touch globally to survive re-renders within this module
  if (typeof window !== 'undefined' && (window.__wow_formatTouched == null)) window.__wow_formatTouched = false
  // Allowed group counts derived from People option index
  function groupCounts(){
    try{
      // Prefer normalized counts from groupMeta if provided
      const metaCounts = (product.groupMeta && Array.isArray(product.groupMeta.counts)) ? product.groupMeta.counts.filter(n => Number.isFinite(n) && n>=3) : []
      if (metaCounts.length) return Array.from(new Set(metaCounts)).sort((a,b)=>a-b)
      const pi = peopleIndex()
      const countsFromOpts = (()=>{
        try{ const vals = pi>=0 ? (product.options?.[pi]?.values || []) : []; const ns = vals.map(v => parseInt(String(v).replace(/[^0-9]/g,''),10)).filter(n => !isNaN(n) && n>=3); return Array.from(new Set(ns)).sort((a,b)=>a-b) }catch{ return [] }
      })()
      const countsFromVariants = (()=>{
        try{
          const vlist = product.variants || []
          const ns=[]
          for (const v of vlist){
            const toks = Array.isArray(v?.options) ? v.options : []
            const tok = (pi>=0 ? toks[pi] : null)
            const n = parseInt(String(tok||'').replace(/[^0-9]/g,''),10)
            if (!isNaN(n) && n>=3) ns.push(n)
          }
          return Array.from(new Set(ns)).sort((a,b)=>a-b)
        }catch{ return [] }
      })()
      const out = countsFromOpts.length ? countsFromOpts : countsFromVariants
      if (out.length) return out
      // fallback to groupMeta range if present
      try{
        const min = (product.groupMeta?.min ?? 3)
        const max = (product.groupMeta?.max ?? 10)
        const arr=[]; for(let i=min;i<=max;i++) arr.push(i); return arr
      }catch{}
      return [3,4,5,6,7,8,9,10]
    }catch{ return [3,4,5,6,7,8,9,10] }
  }
  const state = { mode: 'evoucher', selected: selectedFromUrl(), qty: 1, variant: null, groupCount: (groupCounts()[0] ?? 3), locationLabel: null }

  // ---- refs
  const priceEl = document.getElementById('price')
  const compareEl = document.getElementById('compare')
  const variantIdEl = document.getElementById('variantId')
  const modeNote = document.getElementById('modeNote')
  const optionsWrap = document.getElementById('options')
  const addBtn = document.getElementById('addBtn')
  const buyNow = document.getElementById('buyNow')
  const qty = document.getElementById('qty')
  const dec = document.getElementById('dec')
  const inc = document.getElementById('inc')
  const toastEl = document.getElementById('addToast')
  const stars = document.getElementById('stars')
  const ratingText = document.getElementById('ratingText')
  const groupRange = document.getElementById('groupRange')
  const groupCount = document.getElementById('groupCount')
  const groupInc = document.getElementById('groupInc')
  const groupDec = document.getElementById('groupDec')

  // Mobile bar
  const mPrice = document.getElementById('mPrice')
  const mStars = document.getElementById('mStars')
  const mRatingText = document.getElementById('mRatingText')
  const mobileAdd = document.getElementById('mobileAdd')

  // Config modal (mobile)
  const configModalEl = document.getElementById('configModal')
  const configModal = configModalEl ? new Modal(configModalEl) : null
  const sheetOptions = document.getElementById('sheetOptions')
  const groupRangeSheet = document.getElementById('groupRangeSheet')
  const groupCountSheet = document.getElementById('groupCountSheet')
  const groupIncSheet = document.getElementById('groupIncSheet')
  const groupDecSheet = document.getElementById('groupDecSheet')
  const sheetBookLater = document.getElementById('sheetBookLater')
  const sheetBookNow = document.getElementById('sheetBookNow')
  const sheetConfirm = document.getElementById('sheetConfirm')
  const sheetSubtotal = document.getElementById('sheetSubtotal')

  // Availability controls (desktop block)
  const btnBookNow = document.getElementById('btnBookNow')
  const btnBookLater = document.getElementById('btnBookLater')
  const bookingChoice = document.getElementById('bookingChoice')
  const preferredDateValue = document.getElementById('preferredDateValue')
  const preferredTimeValue = document.getElementById('preferredTimeValue')
  const preferredTZValue = document.getElementById('preferredTZValue')
  const bookingSelectionRow = document.getElementById('bookingSelectionRow')
  const bookingSelectionText = document.getElementById('bookingSelectionText')
  const changeBooking = document.getElementById('changeBooking')

  // Booking modal refs
  const bookingModalEl = document.getElementById('bookingModal')
  const bookingModal = bookingModalEl ? new Modal(bookingModalEl) : null
  const bookingModalContent = document.getElementById('bookingModalContent')
  const calMonthLabel = document.getElementById('calMonthLabel')
  const calDayNames = document.getElementById('calDayNames')
  const calGrid = document.getElementById('calGrid')
  const calPrev = document.getElementById('calPrev')
  const calNext = document.getElementById('calNext')
  const slotList = document.getElementById('slotList')
  const bookingSummary = document.getElementById('bookingSummary')
  const confirmBooking = document.getElementById('confirmBooking')
  const tzCurrent = document.getElementById('tzCurrent')
  const tzSelect = document.getElementById('tzSelect')
  const modalHint = document.getElementById('modalHint')
  const mobileBack = document.getElementById('mobileBack')
  const holdTimer = document.getElementById('holdTimer')
  const holdCountdown = document.getElementById('holdCountdown')

  // Banner + icon refs
  const pillHoldBanner = document.getElementById('pillHoldBanner')
  const pillHoldCountdown = document.getElementById('pillHoldCountdown')
  const pillHourglass = pillHoldBanner ? pillHoldBanner.querySelector('i.bi-hourglass-split') : null

  const mobileFlow = { active:false, pendingAdd:false }
  const cart = useCart()

  /* ---------- Hold UI helpers ---------- */
  function hideHoldUI(){
    try{
      if (holdTimer){ holdTimer.style.display='none' }
      if (pillHoldBanner){ pillHoldBanner.style.setProperty('display','none','important'); pillHoldBanner.classList.remove('hourglass-active'); pillHoldBanner.setAttribute('hidden','') }
      pillHourglass?.classList.remove('hourglass-spin')
      if (pillHoldCountdown){ pillHoldCountdown.textContent='10:00' }
      if (holdCountdown){ holdCountdown.textContent='10:00' }
    }catch{}
  }
  function showHoldUI(){
    try{
      if (holdTimer){ holdTimer.style.display='block' }
      if (pillHoldBanner){ pillHoldBanner.style.setProperty('display','flex','important'); pillHoldBanner.classList.add('hourglass-active'); pillHoldBanner.removeAttribute('hidden') }
      pillHourglass?.classList.add('hourglass-spin')
    }catch{}
  }

  // ---------- Cart helpers for current selection ----------
  function currentVariantId(){ try { return state?.variant?.id ?? null } catch { return null } }
  function currentVariantKey(){ const vid=currentVariantId(); if (vid!=null) return `v${vid}`; try { return state?.selected ? `sel:${state.selected.join('|')}` : 'base' } catch { return 'base' } }
  function currentItemId(){ return `${p.id}:${currentVariantKey()}` }
  function getCartQty(){ try { const id=currentItemId(); const arr = cart.items?.value || []; const it = arr.find(x=>String(x.id)===String(id)); return it ? Number(it.qty)||0 : 0 } catch { return 0 } }
  function setQtyInput(val){ if (qty) qty.value = String(Math.max(1, Number(val)||1)); state.qty = parseInt(qty.value,10) }
  function refreshQtyFromCart(){ const q=getCartQty(); if (q>0) setQtyInput(q) }

  /* ---------- Ratings ---------- */
  function renderStars(){
    if (!stars) return
    stars.innerHTML = ''
    const full = Math.round(product.rating)
    for (let i=1;i<=5;i++){
      const icon = document.createElement('i')
      icon.className = i<=full ? 'bi bi-star-fill text-success' : 'bi bi-star text-secondary'
      stars.appendChild(icon)
    }
    if (ratingText) ratingText.textContent = `${(product.rating||0).toFixed(1)} (${product.ratingCount||0})`
    if (mStars) mStars.innerHTML = stars.innerHTML
    if (mRatingText) mRatingText.textContent = ratingText?.textContent || ''
  }

  /* ---------- Options UI (Debug Inspector functionality, styled for BuyBox) ---------- */
  function buildOptionsInto(container){
    if (!container) return
    container.innerHTML = ''

    // Small helpers
    const addLabel = (text)=>{ const d=document.createElement('div'); d.className='text-secondary small mb-1'; d.textContent=text; container.appendChild(d); return d }
    const addRow = ()=>{ const d=document.createElement('div'); d.className='pills mb-2'; container.appendChild(d); return d }
    const clearAria = (row)=> row && row.querySelectorAll('.pill').forEach(p=>p.setAttribute('aria-checked','false'))

    // Compute supports and sources
    const fi = formatIndex()
    const li = locationIndex()
    const formatValues = (fi>=0 ? (product.options?.[fi]?.values || []) : [])
    const rawLocs = Array.isArray(product.rawLocations) ? product.rawLocations.map(v=>String(v)) : []
    const locOptVals = li>=0 ? ((product.options?.[li]?.values||[]).map(v=>String(v))) : []
    const allLocsSrc = (locOptVals.length ? locOptVals : rawLocs)
    const supportsOnline = allLocsSrc.some(v=>/online/i.test(String(v))) || formatValues.some(v=>/online/i.test(String(v))) || String(props.product?.mode||'').toLowerCase()==='online'
    const supportsPhysical = allLocsSrc.some(v=>!(/online/i.test(String(v))))

    // Ensure initial location label defaults sensibly
    try{
      if (!state.locationLabel){
        if (supportsOnline && /online/i.test(String(selectedFormat()||''))) state.locationLabel = 'Online'
        else {
          const phys = allLocsSrc.find(v=>!(/online/i.test(String(v))))
          state.locationLabel = phys || (supportsOnline ? 'Online' : '')
        }
      }
    }catch{}

    // 1) Format row (if either Online or Physical supported)
    if (supportsOnline || supportsPhysical){
      addLabel('Format')
      const row = addRow()
      const addFmt = (lbl)=>{
        const b=document.createElement('button'); b.type='button'; b.className='pill'; b.setAttribute('role','radio')
        const isActive = String(selectedFormat()||'')===lbl
        b.setAttribute('aria-checked', isActive ? 'true' : 'false')
        b.textContent = lbl
        b.addEventListener('click',()=>{
          // Update selection for Format if option exists, else just influence variant choice
          if (fi>=0) state.selected[fi] = lbl
          try { window.__wow_formatTouched = true; window.dispatchEvent(new CustomEvent('wow:format-selected', { detail: { format: lbl } })) } catch {}
          // Keep a sensible location in sync with Format
          try{ state.locationLabel = (/online/i.test(lbl) ? 'Online' : (allLocsSrc.find(v=>!(/online/i.test(String(v)))) || '')) }catch{}
          updateVariant(); updatePriceUI(); updateUrlFromSelection();
          // Rebuild both containers so Format/Location rows reflect new state
          try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
        })
        row.appendChild(b)
      }
      if (supportsOnline) addFmt('Online')
      if (supportsPhysical) addFmt('In-person')
    }

    // 2) People row
    try{
      const pi = peopleIndex()
      const values = pi>=0 ? (product.options?.[pi]?.values || []) : []
      if (values.length){
        addLabel('People')
        const row = addRow()
        values.forEach(val=>{
          const b=document.createElement('button'); b.type='button'; b.className='pill'; b.setAttribute('role','radio')
          const isActive = String(state.selected?.[pi]||'')===String(val)
          b.setAttribute('aria-checked', isActive ? 'true' : 'false')
          b.textContent = String(val)
          b.addEventListener('click',()=>{
            state.selected[pi] = String(val)
            try { clearAria(row); b.setAttribute('aria-checked','true') } catch {}
            // Toggle group range visibility accordingly (both main and sheet)
            try { if (groupRange) groupRange.style.display = isGroup() ? 'block' : 'none' } catch {}
            try { if (groupRangeSheet) groupRangeSheet.style.display = isGroup() ? 'block' : 'none' } catch {}
            updateVariant(); updatePriceUI(); updateUrlFromSelection();
            try { if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
          })
          row.appendChild(b)
        })
      }
    }catch{}

    // 3) Sessions row (if meaningful)
    try{
      const si = (product.options||[]).findIndex(o => String(o?.name||'').toLowerCase()==='sessions')
      const values = si>=0 ? (product.options?.[si]?.values || []) : []
      if (values.length > 1){
        addLabel('Session(s)')
        const row = addRow()
        values.forEach(val=>{
          const b=document.createElement('button'); b.type='button'; b.className='pill'; b.setAttribute('role','radio')
          const isActive = String(state.selected?.[si]||'')===String(val)
          b.setAttribute('aria-checked', isActive ? 'true' : 'false')
          b.textContent = String(val)
          b.addEventListener('click',()=>{
            state.selected[si] = String(val)
            try { clearAria(row); b.setAttribute('aria-checked','true') } catch {}
            updateVariant(); updatePriceUI(); updateUrlFromSelection();
            try { if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
          })
          row.appendChild(b)
        })
      }
    }catch{}

    // 4) Location(s) row (moved to Overview → "Choose a location")
    try{
      // Assemble list
      const raw = (locOptVals.length ? locOptVals : rawLocs)
      let values = []
      if (supportsOnline) values.push('Online')
      raw.forEach(v=>{ const s=String(v||'').trim(); if (s && !/online/i.test(s)) values.push(s) })
      values = [...new Set(values)]
      const showLocInBuyBox = false // Rendered in Overview instead; BuyBox listens to events
      if (values.length && showLocInBuyBox){
        addLabel('Location(s)')
        const row = addRow()
        const current = String(state.locationLabel||'')
        values.forEach(val=>{
          const b=document.createElement('button'); b.type='button'; b.className='pill'; b.setAttribute('role','radio')
          const isActive = current ? (String(current)===String(val)) : (/online/i.test(String(val)) ? (/online/i.test(String(selectedFormat()||''))) : (!/online/i.test(String(selectedFormat()||''))))
          b.setAttribute('aria-checked', isActive ? 'true' : 'false')
          b.textContent=String(val)
          b.addEventListener('click',()=>{
            state.locationLabel = String(val)
            // Mirror into explicit Location option if present
            if (li>=0){
              const vals = product.options?.[li]?.values || []
              // Prefer exact label match if available, else first matching index
              const exact = vals.find(v => String(v)===String(val))
              if (exact) state.selected[li] = exact
              else if (typeof val === 'string' && vals.length){ state.selected[li] = vals[0] }
            }
            // Toggle format to match location selection
            if (fi>=0){
              const wantFmt = (/online/i.test(String(val)) ? 'Online' : 'In-person')
              state.selected[fi] = wantFmt
              try { window.__wow_formatTouched = true; window.dispatchEvent(new CustomEvent('wow:format-selected', { detail: { format: wantFmt } })) } catch {}
            }
            updateVariant(); updatePriceUI(); updateUrlFromSelection();
            // Rebuild both containers so Format/Location rows reflect new state
            try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
          })
          row.appendChild(b)
        })
      }
    }catch{}
  }
  function buildOptions(){ optionsWrap && (optionsWrap.innerHTML=''); buildOptionsInto(optionsWrap) }
  function optionDefs(){ return (product.options||[]).map((o,i)=>({ idx:i, name:o.name, key:canonName(o.name), values:o.values||[] })) }
  function ensureVariantKV(v){
    if (v && v.kv) return v.kv
    const kv = {}
    const defs = optionDefs()
    const vals = Array.isArray(v?.options)? v.options: []
    const rawLocs = Array.isArray(product?.rawLocations) ? product.rawLocations.map(s=>String(s||'').trim().toLowerCase()) : []
    for (let i=0;i<Math.min(vals.length, defs.length);i++){
      if (vals[i]!=null) kv[defs[i].key] = String(vals[i])
    }
    for (const tokRaw of vals){
      const tok = String(tokRaw||'')
      const lc = tok.toLowerCase()
      if (!kv.format && (lc==='online' || lc.includes('in-person'))) kv.format = (lc==='online'?'Online':'In-person')
      const num = parseInt(tok.replace(/[^0-9]/g,''),10)
      if (!isNaN(num) && !kv.people) kv.people = peopleLabelFor(num) || tok
      if (!kv.sessions){
        const so = defs.find(d=>d.key==='sessions')
        if (so && Array.isArray(so.values)){
          const labels = (so.values||[]).map(x=>String(x))
          if (labels.includes(tok)) kv.sessions = tok
          else {
            const nTok = parseInt(tok.replace(/[^0-9]/g,''),10)
            if (!isNaN(nTok)){
              const cand = labels.find(lbl => parseInt(String(lbl).replace(/[^0-9]/g,''),10) === nTok)
              if (cand) kv.sessions = cand
            }
          }
        }
      }
      // Resolve location against raw product location labels if present
      if (!kv.location){
        if (lc==='online') kv.location = 'Online'
        else if (rawLocs.length){
          const idx = rawLocs.indexOf(lc)
          if (idx>=0) kv.location = String(product.rawLocations[idx])
        }
      }
    }
    v.kv = kv
    return kv
  }
  function variantMatchesSelection(v, sel){
    const kv = ensureVariantKV(v)
    const defs = optionDefs()
    for (let i=0;i<defs.length;i++){
      const key = defs[i].key
      const wanted = String(sel[i] ?? '')
      if (!wanted) continue
      const have = kv[key] ? String(kv[key]) : null
      if (have && have !== wanted) return false
      if (!have){
        const raw = Array.isArray(v.options)? String(v.options[i]||'') : ''
        if (raw && raw !== wanted) return false
      }
    }
    // Optional location preference: if a non-online location is selected, prefer matching location variant
    try{
      const wantLocRaw = String(state?.locationLabel || '').trim()
      if (wantLocRaw){
        const wantIsOnline = /online/i.test(wantLocRaw)
        const vf = String(kv.format || '').toLowerCase()
        if (wantIsOnline) { if (vf && vf !== 'online') return false }
        else {
          // Physical: require in-person, and if variant exposes a specific location, it must match
          if (vf && vf !== 'in-person') return false
          const vLoc = String(kv.location || '').trim()
          if (vLoc && vLoc.toLowerCase() !== wantLocRaw.toLowerCase()) return false
        }
      }
    }catch{}
    return true
  }
  function findVariant(){
    try{
      const sel = state.selected || []
      const defs = optionDefs()
      const pi = defs.findIndex(d=>d.key==='people')
      const si = defs.findIndex(d=>d.key==='sessions')
      const fi = defs.findIndex(d=>d.key==='format')
      let wantPeople = null
      if (pi>=0){
        const lbl = String(sel[pi]||'')
        const low = lbl.toLowerCase()
        if (low.includes('3+') || low.includes('group')) {
          const gc = parseInt(String(state.groupCount||''),10)
          if (!isNaN(gc)) wantPeople = gc
        } else {
          const n = parseInt(lbl.replace(/[^0-9]/g,''),10)
          if (!isNaN(n)) wantPeople = n
        }
      }
      const wantSessions = si>=0 ? String(sel[si]||'') : ''
      const wantLocRaw = String(state.locationLabel||'').trim()
      const wantIsOnline = /online/i.test(wantLocRaw)
      const wantFormat = (!wantLocRaw && fi>=0) ? String(sel[fi]||'') : ''
      const sessionsOrdered = si>=0 ? ((product.options?.[si]?.values||[]).map(v=>String(v))) : []

      // Helper predicates
      const matchesSession = (v, sLbl) => {
        const kv = ensureVariantKV(v); const opts=v.options||[]; const wn=parseInt(String(sLbl||'').replace(/[^0-9]/g,''),10); if (!sLbl) return true; return kv.sessions ? (!isNaN(wn) ? (parseInt(String(kv.sessions).replace(/[^0-9]/g,''),10)===wn) : (String(kv.sessions)===sLbl)) : (!isNaN(wn) ? opts.some(t=>parseInt(String(t).replace(/[^0-9]/g,''),10)===wn) : opts.some(t=>String(t)===sLbl))
      }
      const matchesPeople = (v, n) => { if (isNaN(n)) return true; const kv = ensureVariantKV(v); const opts=v.options||[]; const have=parseInt(String(kv.people||'').replace(/[^0-9]/g,''),10); return (!isNaN(have) && have===n) || opts.some(t=>parseInt(String(t).replace(/[^0-9]/g,''),10)===n) }
      const matchesLocation = (v, loc) => {
        if (!loc) return true
        const w = String(loc).toLowerCase().replace(/[^a-z0-9]+/g,'')
        const kv = ensureVariantKV(v)
        const have = kv.location ? String(kv.location).toLowerCase().replace(/[^a-z0-9]+/g,'') : ''
        if (have) return have===w || have.includes(w) || w.includes(have)
        const opts=v.options||[]
        return opts.some(t=>{ const tn=String(t||'').toLowerCase().replace(/[^a-z0-9]+/g,''); if(!tn || /^[0-9]+$/.test(tn)) return false; return tn===w || tn.includes(w) || w.includes(tn) })
      }

      // Location-first: when a specific location is chosen, try to pick a variant within that location even if sessions differ
      try{
        if (wantLocRaw){
          let pool = (product.variants||[]).filter(v => matchesLocation(v, wantLocRaw))
          if (pool.length){
            if (wantSessions){ const hit = pool.filter(v => matchesSession(v, wantSessions)); if (hit.length) pool = hit }
            if (!isNaN(wantPeople)){ const hit = pool.filter(v => matchesPeople(v, wantPeople)); if (hit.length) pool = hit }
            if (pool.length === 1) return pool[0]
            if (sessionsOrdered.length){
              const rank = (v) => {
                const kv = ensureVariantKV(v); const lbl = String(kv.sessions || '')
                const idx = sessionsOrdered.indexOf(lbl)
                if (idx>=0) return idx
                const kn = parseInt(lbl.replace(/[^0-9]/g,''),10)
                if (!isNaN(kn)){ const cand = sessionsOrdered.findIndex(x => parseInt(String(x).replace(/[^0-9]/g,''),10)===kn); return cand>=0?cand:999 }
                return 999
              }
              pool = [...pool].sort((a,b)=>rank(a)-rank(b))
            }
            return pool[0]
          }
        }
      }catch{}

      const variants = Array.isArray(product.variants) ? product.variants : []
      // Stage 1: exact candidate filtering
      let cands = variants.filter(v => Array.isArray(v?.options) && v.options.length)
      try{
        if (wantLocRaw){
          const needOnline = wantIsOnline
          cands = cands.filter(v => { const kv=ensureVariantKV(v); const opts=v.options||[]; const isOnline=(String(kv.format||'').toLowerCase()==='online') || opts.some(t=>/online/i.test(String(t))); return needOnline ? isOnline : !isOnline })
        } else if (wantFormat) {
          const needOnline = /online/i.test(String(wantFormat))
          cands = cands.filter(v => { const kv=ensureVariantKV(v); const opts=v.options||[]; const isOnline=(String(kv.format||'').toLowerCase()==='online') || opts.some(t=>/online/i.test(String(t))); return needOnline ? isOnline : !isOnline })
        }
        if (!isNaN(wantPeople)){
          cands = cands.filter(v => { const kv=ensureVariantKV(v); const opts=v.options||[]; const have=parseInt(String(kv.people||'').replace(/[^0-9]/g,''),10); return (!isNaN(have) && have===wantPeople) || opts.some(t=>parseInt(String(t).replace(/[^0-9]/g,''),10)===wantPeople) })
        }
        if (wantSessions){
          cands = cands.filter(v => { const kv=ensureVariantKV(v); const opts=v.options||[]; const wn=parseInt(String(wantSessions).replace(/[^0-9]/g,''),10); return kv.sessions ? (!isNaN(wn) ? (parseInt(String(kv.sessions).replace(/[^0-9]/g,''),10)===wn) : (String(kv.sessions)===wantSessions)) : (!isNaN(wn) ? opts.some(t=>parseInt(String(t).replace(/[^0-9]/g,''),10)===wn) : opts.some(t=>String(t)===wantSessions)) })
        }
        if (cands.length === 1) return cands[0]
      }catch{}

      // Stage 2: scoring (sessions > people > location). Also respect format from location when set.
      let best=null, bestScore=-1
      for (const cand of variants){
        const kv = ensureVariantKV(cand)
        const opts = Array.isArray(cand.options)? cand.options: []
        // If Location is chosen, enforce online/physical filter
        if (wantLocRaw){
          const needOnline = wantIsOnline
          const isOnline = (String(kv.format||'').toLowerCase()==='online') || opts.some(t => /online/i.test(String(t)))
          if (needOnline && !isOnline) continue
          if (!needOnline && isOnline) continue
        } else if (wantFormat){
          const needOnline = /online/i.test(String(wantFormat))
          const isOnline = (String(kv.format||'').toLowerCase()==='online') || opts.some(t => /online/i.test(String(t)))
          if (needOnline && !isOnline) continue
          if (!needOnline && isOnline) continue
        }
        // If a Sessions selection is present, require sessions to match (exact label or numeric)
        if (si>=0 && wantSessions){
          const wantN = parseInt(String(wantSessions).replace(/[^0-9]/g,''),10)
          let matches = false
          if (kv.sessions){
            matches = (!isNaN(wantN) ? (parseInt(String(kv.sessions).replace(/[^0-9]/g,''),10)===wantN) : (String(kv.sessions)===wantSessions))
          }
          if (!matches){
            // Fallback: match against raw tokens
            matches = (!isNaN(wantN) ? opts.some(t => parseInt(String(t).replace(/[^0-9]/g,''),10)===wantN) : opts.some(t => String(t)===wantSessions))
          }
          if (!matches) continue
        }
        let score = 0
        // Sessions score (heaviest)
        if (si>=0 && wantSessions){
          const wantN = parseInt(String(wantSessions).replace(/[^0-9]/g,''),10)
          if (kv.sessions){
            if (!isNaN(wantN) ? (parseInt(String(kv.sessions).replace(/[^0-9]/g,''),10)===wantN) : (String(kv.sessions)===wantSessions)) score += 6
          } else if (!isNaN(wantN) ? opts.some(t => parseInt(String(t).replace(/[^0-9]/g,''),10)===wantN) : opts.some(t => String(t)===wantSessions)) {
            score += 6
          }
        }
        // People score (medium)
        if (!isNaN(wantPeople)){
          const haveN = parseInt(String(kv.people||'').replace(/[^0-9]/g,''),10)
          if (!isNaN(haveN) && haveN===wantPeople) score += 4
          else if (opts.some(t => parseInt(String(t).replace(/[^0-9]/g,''),10)===wantPeople)) score += 2
        }
        // Location score (light)
        if (wantLocRaw){
          const have = String(kv.location||'')
          if (have){
            if (have.toLowerCase() === wantLocRaw.toLowerCase()) score += 1
            else {
              const hn = have.toLowerCase(), wn = wantLocRaw.toLowerCase()
              if (hn.includes(wn) || wn.includes(hn)) score += 0.5
            }
          } else {
            const wn = wantLocRaw.toLowerCase().replace(/[^a-z0-9]+/g,'')
            const hit = opts.some(t => {
              const tn = String(t||'').toLowerCase().replace(/[^a-z0-9]+/g,'')
              if (!tn || /^[0-9]+$/.test(tn)) return false
              return (tn===wn || tn.includes(wn) || wn.includes(tn))
            })
            if (hit) score += 0.5
          }
        }
        if (score > bestScore){ best=cand; bestScore=score }
      }
      if (!best) best = (product.variants||[]).find(v=>variantMatchesSelection(v, sel))
      if (!best) best = (product.variants||[]).find(v=>v.available) || (product.variants||[])[0]
      return best
    }catch{ return (product.variants||[])[0] || null }
  }
  function peopleIndex(){
    const idx = product.options.findIndex(o => String(o?.name||'').toLowerCase()==='people')
    if (idx>=0) return idx
    return product.options.length===1 ? 0 : 1
  }
  function formatIndex(){ return product.options.findIndex(o => String(o?.name||'').toLowerCase()==='format') }
  function locationIndex(){
    const idx = product.options.findIndex(o => /location/i.test(String(o?.name||'')))
    return idx
  }
  function selectedFormat(){ const i = formatIndex(); return i>=0 ? state.selected?.[i] : undefined }
  function hasGroupOption(){
    const pi = peopleIndex();
    const vals = (product.options?.[pi]?.values)||[]
    return vals.some(v=>/group|3\+/.test(String(v).toLowerCase()))
  }
  function isGroup(){
    const pi = peopleIndex();
    return hasGroupOption() && /group|3\+/.test(String(state.selected?.[pi]||'').toLowerCase())
  }

  /* ---------- Group pricing ---------- */
  function variantFor(format,people){
    if (product.options.length<=1){
      return product.variants.find(v=>v.options?.[0]===people)
    }
    const fi = formatIndex(); const pi = peopleIndex();
    return product.variants.find(v=>v.options?.[fi]===format && v.options?.[pi]===people)
  }
  function normalizeToken(s){ return String(s||'').trim() }
  function resolveRawVariantId(sel){
    try{
      const defs = product.options || []
      for (const rv of rawVariants){
        const opts = Array.isArray(rv.options)? rv.options : []
        if (!opts.length || opts.length < defs.length) continue
        // Build a kv map like normalized variants
        const temp = { options: opts }
        const kv = ensureVariantKV(temp)
        let ok = true
        for (let i=0;i<defs.length;i++){
          const key = canonName(defs[i].name)
          const wanted = String(sel[i] ?? '')
          if (!wanted) continue
          const have = kv[key] ? String(kv[key]) : (opts[i] ? String(opts[i]) : '')
          // People: when '3+ Group' is selected, match by numeric groupCount against RAW tokens too
          if (key === 'people'){
            const wn = (()=>{ const n=parseInt(wanted.replace(/[^0-9]/g,''),10); return isNaN(n)?null:n })()
            const wantN = (wn!=null && wn>2) ? wn : ((String(wanted).toLowerCase().includes('group') || String(wanted).includes('3+')) ? parseInt(String(state.groupCount||'').replace(/[^0-9]/g,''),10) : wn)
            if (wantN!=null){
              // Prefer matching against any numeric token in the RAW variant options
              try {
                const tokNums = (Array.isArray(opts)?opts:[]).map(t=>parseInt(String(t||'').replace(/[^0-9]/g,''),10)).filter(n=>!isNaN(n))
                if (tokNums.includes(wantN)) { continue }
              } catch {}
              const haveN = (()=>{ const n=parseInt(String(have).replace(/[^0-9]/g,''),10); return isNaN(n)?null:n })()
              if (haveN!=null){ if (wantN !== haveN) { ok=false; break } else { continue } }
            }
          }
          // Sessions: allow numeric equivalence (RAW token match or label number)
          if (key === 'sessions'){
            const wn = (()=>{ const n=parseInt(wanted.replace(/[^0-9]/g,''),10); return isNaN(n)?null:n })()
            if (wn!=null){
              try{
                const tokNums = (Array.isArray(opts)?opts:[]).map(t=>parseInt(String(t||'').replace(/[^0-9]/g,''),10)).filter(n=>!isNaN(n))
                if (tokNums.includes(wn)) { continue }
              }catch{}
              const haveN = (()=>{ const n=parseInt(String(have).replace(/[^0-9]/g,''),10); return isNaN(n)?null:n })()
              if (haveN!=null){ if (wn !== haveN) { ok=false; break } else { continue } }
            }
          }
          if (normalizeToken(have) !== normalizeToken(wanted)) { ok=false; break }
        }
        if (ok) return rv.id
      }
      return null
    }catch{ return null }
  }
  function stepForFormat(format){
    // Prefer group step derived from legacy group map if available
    const gm = product.groupMeta
    if (gm && gm.prices && gm.prices[format]){
      const counts = Object.keys(gm.prices[format]).map(n=>parseInt(n,10)).filter(n=>n>=3 && !isNaN(n)).sort((a,b)=>a-b)
      if (counts.length>=2){
        const a = counts[0], b = counts[1]
        const pa = gm.prices[format][a], pb = gm.prices[format][b]
        if (pa!=null && pb!=null) return Math.max(0, pb - pa)
      }
    }
    const v1=variantFor(format,'1 Person');
    const v2=variantFor(format,'2 Persons');
    if(v1&&v2) return Math.max(0, v2.price - v1.price);
    const vg=variantFor(format,'3+ Group');
    if(v2&&vg) return Math.max(0, vg.price - v2.price);
    return 25000;
  }
  function priceForGroup(format,n){
    // Prefer explicit prices from legacy mapping
    const gm = product.groupMeta
    if (gm && gm.prices){
      if (format && gm.prices[format] && gm.prices[format][n]) return gm.prices[format][n]
      if (gm.prices._any && gm.prices._any[n]) return gm.prices._any[n]
    }
    const base=variantFor(format,'3+ Group');
    const step=stepForFormat(format);
    if(!base) return step*n;
    const extra=Math.max(0,n-3);
    return base.price + extra*step;
  }
  function compareForGroup(format,n){
    const gm = product.groupMeta
    if (gm && gm.compare){
      if (format && gm.compare[format] && gm.compare[format][n]) return gm.compare[format][n]
      if (gm.compare._any && gm.compare._any[n]) return gm.compare._any[n]
    }
    const v1=variantFor(format,'1 Person');
    const v2=variantFor(format,'2 Persons');
    const base=variantFor(format,'3+ Group');
    let step=0;
    if(v1&&v2&&v2.compare&&v1.compare&&v2.compare>v1.compare) step=v2.compare-v1.compare;
    else if(base&&base.compare) step=Math.round(base.compare/3);
    const extra=Math.max(0,n-3);
    return (base&&base.compare?base.compare:0) + extra*step;
  }
  function unitPriceWithMode(){ return Number(state?.variant?.price || 0) }
  function totals(){
    const unit=unitPriceWithMode();
    const total=unit*state.qty;
    let cmp=0;
    if(state.variant.compare&&state.variant.compare>state.variant.price){ cmp=state.variant.compare }
    return {unit,total,cmp: cmp?cmp*state.qty:0};
  }
  function updatePriceUI(){
    const t=totals();
    if (priceEl) priceEl.textContent = fmt(t.total)
    if (mPrice) mPrice.textContent = fmt(t.total)
    if (variantIdEl) { try { const vid = state?.variant?.id; variantIdEl.textContent = vid!=null ? `Variant ID: ${vid}` : '' } catch { variantIdEl.textContent = '' } }
    if (t.cmp && t.cmp>t.total){
      if (compareEl) { compareEl.textContent=fmt(t.cmp); compareEl.style.display='inline' }
    } else {
      if (compareEl) { compareEl.textContent=''; compareEl.style.display='none' }
    }
    updateSheetSubtotal();
  }
  function updateSheetSubtotal(){ if(!sheetSubtotal) return; const t=totals(); sheetSubtotal.textContent=`Subtotal: ${fmt(t.total)}` }
  // Ensure state.selected mirrors the resolved variant's options (keeps Sessions/People valid after Format changes)
  function applyVariantToSelection(v){
    try{
      if (!v) return
      const defs = optionDefs()
      const kv = ensureVariantKV(v)
      const raw = Array.isArray(v.options) ? v.options : []
      const sel = state.selected || []
      for (let i=0;i<defs.length;i++){
        const key = defs[i].key
        let want = kv[key] != null ? String(kv[key]) : (raw[i] != null ? String(raw[i]) : '')
        if (!want) continue
        if (key === 'format'){
          const lc = want.toLowerCase(); want = (lc.includes('online') ? 'Online' : 'In-person')
        }
        if (key === 'people'){
          const n = parseInt(want.replace(/[^0-9]/g,''),10)
          if (!isNaN(n)) want = (n===1?'1 Person':(n===2?'2 Persons':'3+ Group'))
        }
        sel[i] = want
      }
      state.selected = sel
    }catch{}
  }
  function updateVariant(){
    state.variant=findVariant();
    // Fallback: if variants do not encode Format dimension explicitly AND Format has 2+ options, choose by format order
    try{
      const fi = formatIndex()
      const hasFmtDim = (product.variants||[]).some(v => (v.options||[]).some(tok => /online|in-person/i.test(String(tok))))
      const hasMultipleFormatValues = (fi>=0) && Array.isArray(product.options?.[fi]?.values) && product.options[fi].values.length >= 2
      if (fi>=0 && !hasFmtDim && hasMultipleFormatValues){
        const f = state.selected?.[fi]
        const vlist = product.variants||[]
        if (vlist.length){ state.variant = (/online/i.test(String(f)) ? vlist[0] : (vlist[1]||vlist[0])) }
      }
    }catch{}
    // Do not auto-adjust Sessions/People when Format changes; preserve user's Sessions selection
    refreshQtyFromCart()
    const showGroup = hasGroupOption() && isGroup();
    if(groupRange){
      if(showGroup){
        groupRange.style.display='block';
        clampGroupCount();
        // Update label to dynamic range based on allowed values
        const counts = groupCounts();
        const min = counts[0] ?? 3
        const max = counts[counts.length-1] ?? 10
        const lbl = groupRange.querySelector('span.text-muted.small')
        if (lbl) lbl.textContent = `(${min}–${max})`
      } else {
        groupRange.style.display='none';
        const counts = groupCounts();
        state.groupCount = counts[0] ?? 3;
        if(groupCount) groupCount.value=String(state.groupCount)
      }
    }
    if (addBtn) {
      const canBuy = !!(state.variant && Number(state.variant.price||0) > 0)
      addBtn.disabled=!canBuy; addBtn.textContent=canBuy?'Add to cart':'Sold out'
    }
    // Debug logging removed
    updatePriceUI();
  }

  /* ---------- Qty & Group wiring ---------- */
  function clampGroupCount(){
    const counts = groupCounts()
    let v = parseInt(groupCount?.value||String(counts[0]??3), 10)
    if (isNaN(v)) v = counts[0] ?? 3
    // Snap to nearest allowed (prefer next higher if between)
    let snap = counts[0] ?? 3
    for (const n of counts){ if (v<=n){ snap=n; break } snap=n }
    if (groupCount) groupCount.value = String(snap)
    state.groupCount = snap
    // Re-resolve variant based on new group count, then sync price + URL
    updateVariant(); updatePriceUI(); updateUrlFromSelection()
  }
  function stepGroup(delta){
    const counts = groupCounts()
    const cur = parseInt(groupCount?.value||String(counts[0]??3),10)
    let idx = counts.findIndex(n => n===cur)
    if (idx<0) idx = 0
    idx = Math.max(0, Math.min(counts.length-1, idx + (delta<0?-1:1)))
    const v = counts[idx]
    if (groupCount) groupCount.value = String(v)
    state.groupCount = v
    updateVariant(); updatePriceUI(); updateUrlFromSelection()
  }
  function clampGroupCountSheet(){
    const counts = groupCounts()
    let v = parseInt(groupCountSheet?.value||String(counts[0]??3),10)
    if (isNaN(v)) v = counts[0] ?? 3
    let snap = counts[0] ?? 3
    for (const n of counts){ if (v<=n){ snap=n; break } snap=n }
    if (groupCountSheet) groupCountSheet.value = String(snap)
    state.groupCount = snap
    updateVariant(); updatePriceUI(); updateUrlFromSelection()
  }
  function stepGroupSheet(delta){
    const counts = groupCounts()
    const cur = parseInt(groupCountSheet?.value||String(counts[0]??3),10)
    let idx = counts.findIndex(n => n===cur)
    if (idx<0) idx = 0
    idx = Math.max(0, Math.min(counts.length-1, idx + (delta<0?-1:1)))
    const v = counts[idx]
    if (groupCountSheet) groupCountSheet.value = String(v)
    state.groupCount = v
    updateVariant(); updatePriceUI(); updateUrlFromSelection()
  }
  function wireQty(){
    if(inc && dec && qty){
      inc.addEventListener('click',()=>{
        const id = currentItemId();
        const arr = cart.items?.value || []
        const it = arr.find(x=>String(x.id)===String(id))
        if (it){ cart.updateQty(id, Number(it.qty||0)+1); setTimeout(()=>refreshQtyFromCart(),0) }
        else { qty.value=String(Math.max(1,parseInt(qty.value||'1',10)+1)); state.qty=parseInt(qty.value,10); updatePriceUI() }
      })
      dec.addEventListener('click',()=>{
        const id = currentItemId();
        const arr = cart.items?.value || []
        const it = arr.find(x=>String(x.id)===String(id))
        if (it){
          const cur = Number(it.qty||0)
          if (cur>1){ cart.updateQty(id, cur-1); setTimeout(()=>refreshQtyFromCart(),0) }
          else { try{ cart.remove(id) }catch{}; setQtyInput(1) }
        } else {
          qty.value=String(Math.max(1,parseInt(qty.value||'1',10)-1)); state.qty=parseInt(qty.value,10); updatePriceUI()
        }
      })
      qty.addEventListener('input',()=>{qty.value=(qty.value||'').replace(/[^0-9]/g,'')||'1';state.qty=parseInt(qty.value,10);updatePriceUI()})
    }
    if(groupInc && groupDec && groupCount){
      groupInc.addEventListener('click',()=>stepGroup(1))
      groupDec.addEventListener('click',()=>stepGroup(-1))
      groupCount.addEventListener('input',()=>{groupCount.value=groupCount.value.replace(/[^0-9]/g,'');clampGroupCount();updatePriceUI()})
      groupCount.addEventListener('blur',()=>{clampGroupCount();updatePriceUI()})
    }
    if(groupIncSheet && groupDecSheet && groupCountSheet){
      groupIncSheet.addEventListener('click',()=>stepGroupSheet(1))
      groupDecSheet.addEventListener('click',()=>stepGroupSheet(-1))
      groupCountSheet.addEventListener('input',()=>{groupCountSheet.value=groupCountSheet.value.replace(/[^0-9]/g,'');clampGroupCountSheet();updatePriceUI()})
      groupCountSheet.addEventListener('blur',()=>{clampGroupCountSheet();updatePriceUI()})
    }
  }

  function isMobile(){ return window.matchMedia('(max-width: 991px)').matches }
  function doAdd(){
    // Surface toast for quick feedback
    if (toastEl) { try { new Toast(toastEl).show() } catch {} }
    // Emit to parent to use app store/cart
    const detail = {
      qty: state.qty,
      groupCount: state.groupCount,
      selected: state.selected,
      booking: {
        when: bookingChoice?.value || 'later',
        date: preferredDateValue?.value || '',
        time: preferredTimeValue?.value || '',
        tz: preferredTZValue?.value || '',
      },
      location: state.locationLabel || '',
      ...(state?.hold?.expiresAt ? { holdExpiresAt: state.hold.expiresAt } : {}),
      ...(state?.hold?.id ? { reservationId: state.hold.id } : {}),
      variantId: state.variant?.id,
      unitPricePence: unitPriceWithMode(),
    }
    emit('add', detail)
  }

  /* ---------- Desktop CTAs ---------- */
  function wireCTA(){ addBtn?.addEventListener('click',e=>{ e.preventDefault(); doAdd() }) }

  /* ---------- Mobile bar CTA ---------- */
  mobileAdd?.addEventListener('click',()=>{
    buildOptionsInto(sheetOptions)
    if (groupRangeSheet) {
      groupRangeSheet.style.display=(hasGroupOption() && isGroup())?'block':'none'
      const counts = groupCounts()
      const min = counts[0] ?? 3
      const max = counts[counts.length-1] ?? 10
      const lbl = groupRangeSheet.querySelector('span.text-muted.small')
      if (lbl) lbl.textContent = `(${min}–${max})`
    }
    if (groupCountSheet) groupCountSheet.value=String(state.groupCount || (groupCounts()[0] ?? 3))
    if (bookingChoice?.value==='now'){ sheetBookLater?.classList.remove('active'); sheetBookNow?.classList.add('active') }
    else { sheetBookLater?.classList.add('active'); sheetBookNow?.classList.remove('active') }
    updateSheetSubtotal();
    configModal?.show();
  })

  // ----- Booking modal / availability (light demo logic preserved) -----
  const HOLD_MINUTES=10
  const bookings = {}
  function dateKey(d){ return d.toISOString().slice(0,10) }
  function ensureDay(d){ const k=dateKey(d); if(!bookings[k]) bookings[k]={booked:new Set(),reserved:{}}; return bookings[k] }
  // seed example
  ;(function seed(){ try { const day = new Date(Date.UTC(2025,9,7,0,0,0)); const d = ensureDay(day); d.booked.add('16:28'); const reservedStart = new Date(Date.UTC(2025,9,7,16,30)); d.reserved['16:30'] = { until: new Date(reservedStart.getTime()+HOLD_MINUTES*60000) } } catch {} })()

  const calendarState={viewYear:new Date().getFullYear(),viewMonth:new Date().getMonth(),selectedDate:null,selectedTime:null,tz:Intl.DateTimeFormat().resolvedOptions().timeZone||'Europe/London'}

  function exitMobileTimesMode(){ bookingModalContent?.classList.remove('mobile-times') }
  function clearBookingSelection(){
    if (bookingChoice) bookingChoice.value='later'
    if (preferredDateValue) preferredDateValue.value=''
    if (preferredTimeValue) preferredTimeValue.value=''
    if (preferredTZValue) preferredTZValue.value=''
    if (bookingSelectionRow) bookingSelectionRow.style.display='none'
    if (bookingSelectionText) bookingSelectionText.textContent=''
    calendarState.selectedDate=null; calendarState.selectedTime=null
    if (bookingSummary) bookingSummary.textContent='No date selected.'
    if (confirmBooking) confirmBooking.disabled=true
    if (modalHint) modalHint.textContent='Pick a date, then choose a time.'
    btnBookLater?.classList.add('active'); btnBookNow?.classList.remove('active')
    exitMobileTimesMode(); stopUserHold(); try{ releaseServerHold() }catch{}
  }
  btnBookLater?.addEventListener('click',clearBookingSelection)
  btnBookNow?.addEventListener('click',()=>{ btnBookNow.classList.add('active'); btnBookLater?.classList.remove('active'); if (bookingChoice) bookingChoice.value='now'; bookingModal?.show() })
  // When clicking "Change", reopen the modal on the same date/time (if set)
  changeBooking?.addEventListener('click',(e)=>{
    e.preventDefault()
    try {
      const ds = (preferredDateValue?.value || '').trim()
      const ts = (preferredTimeValue?.value || '').trim()
      if (ds) {
        const [y,m,d] = ds.split('-').map(x=>parseInt(x,10))
        if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
          const pre = new Date(y, m-1, d)
          calendarState.viewYear = pre.getFullYear()
          calendarState.viewMonth = pre.getMonth()
          calendarState.selectedDate = pre
          calendarState.selectedTime = ts || null
        }
      }
    } catch {}
    if (bookingChoice) bookingChoice.value='now'
    bookingModal?.show()
  })
  sheetBookLater?.addEventListener('click',()=>{ sheetBookLater.classList.add('active'); sheetBookNow?.classList.remove('active'); if (bookingChoice) bookingChoice.value='later' })
  // Ensure hold/banner hidden when deciding later from mobile config
  sheetBookLater?.addEventListener('click',()=>{ try{ clearBookingSelection(); hideHoldUI() }catch{} })
  sheetBookNow?.addEventListener('click',()=>{ sheetBookNow.classList.add('active'); sheetBookLater?.classList.remove('active'); if (bookingChoice) bookingChoice.value='now' })
  sheetConfirm?.addEventListener('click',()=>{
    if(bookingChoice?.value==='now' && !(preferredDateValue?.value && preferredTimeValue?.value)){
      configModal?.hide(); mobileFlow.active=true; mobileFlow.pendingAdd=true; bookingModal?.show(); return
    }
    configModal?.hide(); doAdd()
  })

  function generateSlotsForDate(d){ const day=d.getDay(); if(day===0||day===6) return []; const slots=[]; for(let h=9;h<=16;h++){slots.push(`${String(h).padStart(2,'0')}:00`);slots.push(`${String(h).padStart(2,'0')}:30`)} return slots }
  function renderDayNames(){ if(!calDayNames) return; calDayNames.innerHTML=''; ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(n=>{const el=document.createElement('div');el.className='cal-dayname text-center';el.textContent=n;calDayNames.appendChild(el)}) }
  function daysInMonth(y,m){return new Date(y,m+1,0).getDate()}
  function firstWeekday(y,m){const js=new Date(y,m,1).getDay();return (js+6)%7}
  function isSameDate(a,b){return a&&b&&a.getFullYear()===b.getFullYear()&&a.getMonth()===b.getMonth()&&a.getDate()===b.getDate()}
  function renderCalendar(){
    if(!calGrid||!calMonthLabel) return
    const y=calendarState.viewYear,m=calendarState.viewMonth
    calMonthLabel.textContent=new Date(y,m,1).toLocaleDateString(undefined,{month:'long',year:'numeric'})
    calGrid.innerHTML=''
    const lead=firstWeekday(y,m),total=daysInMonth(y,m)
    if(!calDayNames.children.length) renderDayNames()
    for(let i=0;i<lead;i++){const d=document.createElement('div');d.className='cal-cell';d.setAttribute('aria-disabled','true');calGrid.appendChild(d)}
    const today=new Date(); today.setHours(0,0,0,0)
    for(let day=1;day<=total;day++){
      const cellDate=new Date(y,m,day); cellDate.setHours(0,0,0,0)
      const btn=document.createElement('button'); btn.type='button'; btn.className='cal-cell'; btn.textContent=String(day)
      const past=cellDate<today
      const dayObj = ensureDay(new Date(Date.UTC(cellDate.getFullYear(), cellDate.getMonth(), cellDate.getDate())))
      const slots = generateSlotsForDate(cellDate)
      const availableSlots = slots.filter(s => !dayObj.booked.has(s) && !validReserved(dayObj, s))
      const noAvailability = slots.length === 0 || availableSlots.length === 0
      if (past || noAvailability) {
        btn.disabled = true
        btn.setAttribute('aria-disabled','true')
        btn.title = past ? 'Past date' : 'No availability'
      }
      btn.addEventListener('click',()=>{
        if(past || noAvailability) return
        calendarState.selectedDate=cellDate; calendarState.selectedTime=null
        ;[...calGrid.querySelectorAll('.cal-cell')].forEach(c=>c.classList.remove('active'))
        btn.classList.add('active'); renderSlots(); updateSummary(); if(confirmBooking) confirmBooking.disabled=true; if(modalHint) modalHint.textContent='Choose a time.'; if (isMobile()) bookingModalContent?.classList.add('mobile-times')
      })
      if(isSameDate(cellDate,calendarState.selectedDate) && !past && !noAvailability) btn.classList.add('active')
      calGrid.appendChild(btn)
    }
  }
  function validReserved(dayObj, timeKey){ const res = dayObj.reserved[timeKey]; if(!res) return null; const now = new Date(); if(now >= res.until){ delete dayObj.reserved[timeKey]; return null } return res }
  function renderSlots(){
    if(!slotList){ return }
    slotList.innerHTML=''
    const d=calendarState.selectedDate
    if(!d){ slotList.innerHTML='<div class="text-secondary small">Select a date to see available times.</div>';return }
    const slots=generateSlotsForDate(d); if(!slots.length){ slotList.innerHTML='<div class="text-secondary small">No times available for this date.</div>'; return }
    const dayObj = ensureDay(new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate())))
    slots.forEach(s=>{
      if(dayObj.booked.has(s)) return
      const reservedObj = validReserved(dayObj, s)
      const b=document.createElement('button'); b.type='button'; b.className='slot'; b.textContent=s
      if(reservedObj){
        b.classList.add('reserved'); b.disabled=true
        const span=document.createElement('span'); span.className='ms-1 small'; span.dataset.until = reservedObj.until.toISOString(); span.textContent='(reserved)'; b.appendChild(span)
      } else {
        b.addEventListener('click',()=>{
          ;[...slotList.querySelectorAll('.slot')].forEach(x=>x.classList.remove('active'))
          b.classList.add('active'); calendarState.selectedTime=s; updateSummary(); if(confirmBooking) confirmBooking.disabled=false; if(modalHint) modalHint.textContent='Nice choice — we’ll hold this for 10 minutes.'; startUserHold(d, s); if(isMobile()){ confirmBooking?.click() }
        })
      }
      if(calendarState.selectedTime===s) b.classList.add('active')
      slotList.appendChild(b)
    })
    // Disable per-second reserved countdown UI to avoid heavy updates
    // refreshReservedCountdowns()
  }
  function populateTimezones(){
    if(!tzSelect||!tzCurrent) return
    const tzs=['Europe/London','Europe/Dublin','Europe/Lisbon','Europe/Paris','Europe/Berlin','UTC','America/New_York','America/Chicago','America/Denver','America/Los_Angeles','Asia/Dubai','Asia/Kolkata','Asia/Singapore','Australia/Sydney']
    tzSelect.innerHTML=''; tzs.forEach(tz=>{const o=document.createElement('option');o.value=tz;o.textContent=tz;if(tz===calendarState.tz)o.selected=true;tzSelect.appendChild(o)})
    tzCurrent.textContent=calendarState.tz
    if (!tzSelect._bound) {
      tzSelect.addEventListener('change',()=>{calendarState.tz=tzSelect.value;tzCurrent.textContent=calendarState.tz;updateSummary()})
      tzSelect._bound = true
    }
  }
  function updateSummary(){
    if(!bookingSummary) return
    if(calendarState.selectedDate && calendarState.selectedTime){
      const ds=calendarState.selectedDate.toLocaleDateString(undefined,{weekday:'long',day:'numeric',month:'long',year:'numeric'})
      bookingSummary.innerHTML=`<div class="fw-semibold">${ds}</div><div>${calendarState.selectedTime} (${calendarState.tz})</div>`
    } else if(calendarState.selectedDate){
      const ds=calendarState.selectedDate.toLocaleDateString(undefined,{weekday:'long',day:'numeric',month:'long',year:'numeric'})
      bookingSummary.textContent=`${ds} — select a time`
    } else bookingSummary.textContent='No date selected.'
  }
  calPrev?.addEventListener('click',()=>{calendarState.viewMonth--; if(calendarState.viewMonth<0){calendarState.viewMonth=11;calendarState.viewYear--} renderCalendar()})
  calNext?.addEventListener('click',()=>{calendarState.viewMonth++; if(calendarState.viewMonth>11){calendarState.viewMonth=0;calendarState.viewYear++} renderCalendar()})
  mobileBack?.addEventListener('click',()=>{ bookingModalContent?.classList.remove('mobile-times') })

  // ---------- Holds / countdown ----------
  let userHoldInterval=null
  let userHoldUntil=null
  let userHoldKey=null
  let serverHoldId = null
  async function createServerHold(){
    try{
      const dateStr = preferredDateValue?.value || (calendarState.selectedDate ? calendarState.selectedDate.toISOString().slice(0,10) : '')
      const timeStr = preferredTimeValue?.value || calendarState.selectedTime || ''
      if (!dateStr || !timeStr) return
      const token = document.querySelector('meta[name="csrf-token"]')?.content || ''
      const res = await fetch('/api/reservations/hold', { method:'POST', headers:{ 'Content-Type':'application/json', ...(token?{'X-CSRF-TOKEN':token}:{}) }, body: JSON.stringify({ date: dateStr, time: String(timeStr).slice(0,5) }) })
      if (!res.ok) return
      const j = await res.json()
      if (j && j.ok && j.id){ serverHoldId = j.id; try { state.hold = state.hold||{}; state.hold.id = j.id; state.hold.expiresAt = j.expires_at || null } catch {} }
    }catch{}
  }
  async function releaseServerHold(){
    try{
      if (!serverHoldId) return
      const token = document.querySelector('meta[name="csrf-token"]')?.content || ''
      await fetch('/api/reservations/release', { method:'POST', headers:{ 'Content-Type':'application/json', ...(token?{'X-CSRF-TOKEN':token}:{}) }, body: JSON.stringify({ id: serverHoldId }) })
    }catch{} finally{ serverHoldId = null; try { state.hold = { id:null, expiresAt:null } } catch {} }
  }
  function mmss(ms){ const total=Math.max(0,Math.ceil(ms/1000)); const m=String(Math.floor(total/60)).padStart(2,'0'); const s=String(total%60).padStart(2,'0'); return `${m}:${s}` }
  function startUserHold(dateObj, timeStr){
    // Simplify: avoid timers and heavy updates; just reflect selection without hold logic
    try { stopUserHold() } catch {}
    try { showHoldUI(); if (pillHoldCountdown) pillHoldCountdown.textContent = '10:00'; if (holdCountdown) holdCountdown.textContent = '10:00' } catch {}
    userHoldUntil = null; userHoldKey = null
  }
  function stopUserHold(){
    hideHoldUI()
    if(userHoldInterval){ clearInterval(userHoldInterval); userHoldInterval=null }
    if(userHoldKey){ try{ const d = bookings[userHoldKey.k]; if(d && d.reserved[userHoldKey.timeStr]){ delete d.reserved[userHoldKey.timeStr] } }catch{} userHoldKey=null }
    userHoldUntil=null
    try{ releaseServerHold() }catch{}
  }
  function tickUserHold(){ /* disabled */ }
  function refreshReservedCountdowns(){ /* disabled */ }
  confirmBooking?.addEventListener('click',()=>{
    if(!(calendarState.selectedDate && calendarState.selectedTime)) return
    if (preferredDateValue) preferredDateValue.value=calendarState.selectedDate.toISOString().slice(0,10)
    if (preferredTimeValue) preferredTimeValue.value=calendarState.selectedTime
    if (preferredTZValue) preferredTZValue.value=calendarState.tz
    const ds=calendarState.selectedDate.toLocaleDateString(undefined,{weekday:'short',day:'numeric',month:'short',year:'numeric'})
    if (bookingSelectionText) bookingSelectionText.textContent=`${ds} • ${calendarState.selectedTime}`
    if (bookingSelectionRow) bookingSelectionRow.style.display='inline-block'
    bookingModal?.hide(); exitMobileTimesMode();
    // keep hold running and persist server-side hold (if logged in)
    try{ createServerHold() }catch{}
  })
  bookingModalEl?.addEventListener('shown.bs.modal',()=>{ exitMobileTimesMode(); if(!calDayNames?.children.length){ populateTimezones() } renderCalendar(); renderSlots(); updateSummary() })

  // ---------- Misc init ----------
  function updateMode(){ if (modeNote) modeNote.textContent='Instant email delivery' }
  function init(){
    // Ensure hold and banner are fully hidden on load
    hideHoldUI();
    try{ stopUserHold() }catch{}
    if (bookingChoice) bookingChoice.value = 'later'
    renderStars(); buildOptions();
    // Initial location from URL or infer from current format
    try{
      const url = new URL(window.location.href)
      const locParam = String(url.searchParams.get('location')||'')
      if (locParam) {
        state.locationLabel = locParam
      } else {
        const f = selectedFormat()
        if (/online/i.test(String(f))) state.locationLabel = 'Online'
        else {
          const raw = Array.isArray(product.rawLocations)? product.rawLocations: []
          const phys = raw.find(v=>!(/online/i.test(String(v)))) || ''
          state.locationLabel = phys || ''
        }
      }
    }catch{}
    // If no selection variables are present in the URL, use first-of-each and persist them
    try {
      const url = new URL(window.location.href)
      const hasAny = url.searchParams.has('variant') || url.searchParams.has('format') || url.searchParams.has('people') || url.searchParams.has('sessions') || url.searchParams.has('group')
      if (!hasAny) {
        try { window.__wow_formatTouched = true } catch {}
      }
    } catch {}
    // If a variant id exists in URL, force selection from it and align location/group
    try{
      const url=new URL(window.location.href)
      const varId=url.searchParams.get('variant')
      if (varId){
        const sel=selectionForVariantId(varId)
        if (sel && sel.length){
          // Update selection to reflect URL variant, then rebuild option pills UI
          state.selected = sel
          // Align location and group from the variant tokens
          try{
            const v = (product.variants||[]).find(x=>String(x.id)===String(varId))
            if (v){
              const kv = ensureVariantKV(v)
              if (kv.location) state.locationLabel = String(kv.location)
              const pi = peopleIndex()
              if (pi>=0 && String(state.selected?.[pi]||'')==='3+ Group'){
                const counts = groupCounts()
                const tok = (Array.isArray(v?.options)? v.options[pi]: '')
                const candN = parseInt(String(tok||'').replace(/[^0-9]/g,''),10)
                if (!isNaN(candN) && counts.length){
                  // Snap to closest count
                  let best=counts[0], bestDiff=Math.abs(candN-counts[0])
                  for (const n of counts){ const d=Math.abs(candN-n); if (d<bestDiff){ best=n; bestDiff=d } }
                  state.groupCount = best
                } else if (counts.length){
                  state.groupCount = counts[0]
                }
              }
            }
          }catch{}
          try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
          try { window.__wow_formatTouched = true } catch {}
        }
      }
    }catch{}
    state.variant = findVariant();
    updateVariant(); updateMode(); wireQty(); wireCTA(); updatePriceUI();
    // Ensure URL reflects the initial (or default) selection on page load
    updateUrlFromSelection();
    // Also inform overview to open matching location (Online/In-person)
    try { const f = selectedFormat(); if (f) window.dispatchEvent(new CustomEvent('wow:format-selected', { detail: { format: f } })) } catch {}
    // If a specific variant is in the URL, hint overview to select corresponding location index
    try {
      const url=new URL(window.location.href)
      const varId=url.searchParams.get('variant')
      if (varId){
        const idx = (product.variants||[]).findIndex(v => String(v.id)===String(varId))
        if (idx>=0) window.dispatchEvent(new CustomEvent('wow:location-index', { detail: { index: idx } }))
      }
    }catch{}
    window.addEventListener('resize',()=>{ bookingModalContent?.classList.remove('mobile-times') })
  }
  init()

  // Respond to external location/format selection from the overview panel
  try {
    window.addEventListener('wow:select-format', (ev) => {
      try {
        const f = String(ev?.detail?.format || '')
        if (!f) return
        const idx = formatIndex()
        if (idx >= 0) {
          state.selected[idx] = f
          // Update location preference in tandem with format, but do NOT override an existing physical location
          try{
            if (/online/i.test(String(f))) {
              state.locationLabel = 'Online'
            } else {
              const cur = String(state.locationLabel||'')
              if (!cur || /online/i.test(cur)) {
                const phys = Array.isArray(product.rawLocations) ? (product.rawLocations.find(l=>!(/online/i.test(String(l)))) || '') : ''
                state.locationLabel = phys || ''
              }
            }
          }catch{}
          updateVariant(); updateUrlFromSelection()
          // Refresh option pills UI so the active state reflects external change
          try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
          // Ensure variant reflects format when format isn't in variant options
          try{
            const fi = formatIndex()
            const hasFmtDim = (product.variants||[]).some(v => (v.options||[]).some(tok => /online|in-person/i.test(String(tok))))
            if (fi>=0 && !hasFmtDim){
              const vlist = product.variants||[]
              if (vlist.length){ state.variant = (/online/i.test(String(f)) ? vlist[0] : (vlist[1]||vlist[0])) }
              updatePriceUI(); updateUrlFromSelection();
              try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
            }
          }catch{}
          try { window.__wow_formatTouched = true } catch {}
        }
      } catch {}
    })
    // Location selection from overview: set the Locations option value if present
    window.addEventListener('wow:select-location', (ev) => {
      try {
        const lbl = String(ev?.detail?.label || '')
        if (lbl) state.locationLabel = lbl
        // Ensure Format mirrors the chosen location
        try{
          const fi = formatIndex()
          if (fi>=0){ state.selected[fi] = (/online/i.test(lbl) ? 'Online' : 'In-person') }
        }catch{}
        const idx = locationIndex()
        if (idx >= 0) {
          const values = product.options?.[idx]?.values || []
          if (lbl && values.includes(lbl)) {
            state.selected[idx] = lbl
          } else if (typeof ev?.detail?.index === 'number' && values[ev.detail.index]) {
            state.selected[idx] = values[ev.detail.index]
          }
          updateVariant(); updatePriceUI(); updateUrlFromSelection();
          try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
          try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
        } else {
          // No explicit Locations option → just recompute
          updateVariant(); updatePriceUI(); updateUrlFromSelection();
          try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
        }
      } catch {}
    })
    // Capture the chosen location label explicitly for cart meta
    window.addEventListener('wow:location-picked', (ev) => {
      try {
        const lbl = String(ev?.detail?.label || '')
        if (lbl) state.locationLabel = lbl
        // Also refresh variant/price/URL now that the label is set
        try{
          const fi = formatIndex()
          if (fi>=0){ state.selected[fi] = (/online/i.test(lbl) ? 'Online' : 'In-person') }
        }catch{}
        updateVariant(); updatePriceUI(); updateUrlFromSelection();
        try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
      } catch {}
    })
    // Variant selection broadcast (from inline inspector)
    window.addEventListener('wow:variant-select', (ev) => {
      try {
        const vid = ev?.detail?.id
        if (vid == null) return
        const sel = selectionForVariantId(vid)
        if (sel && sel.length){
          state.selected = sel
          // Align location/group using the chosen variant
          try{
            // Prefer RAW backend variant tokens to extract numeric group and location label
            const raw = (rawVariants||[]).find(x=>String(x.id)===String(vid))
            const toks = Array.isArray(raw?.options) ? raw.options.map(t=>String(t||'')) : []
            // Location from raw tokens
            try{
              const locTok = toks.find(t => !/^\s*$/.test(t) && !/^[0-9]+$/.test(t) && !/session/i.test(t) && !/person/i.test(t))
              if (locTok){
                const lbl = (/online/i.test(locTok) ? 'Online' : locTok)
                state.locationLabel = lbl
              }
            }catch{}
            // Group count from numeric token > 2
            try{
              const nums = toks.map(t => parseInt(String(t).replace(/[^0-9]/g,''),10)).filter(n => !isNaN(n))
              const candN = nums.find(n => n>2)
              if (typeof candN === 'number'){
                const counts = groupCounts()
                if (counts && counts.length){
                  // Snap to the closest available count
                  let best = counts[0], bestDiff = Math.abs(candN - counts[0])
                  for (const n of counts){ const d = Math.abs(candN - n); if (d < bestDiff){ best = n; bestDiff = d } }
                  state.groupCount = best
                } else {
                  state.groupCount = candN
                }
              }
            }catch{}
          }catch{}
          updateVariant(); updatePriceUI(); updateUrlFromSelection();
          try { buildOptions(); if (sheetOptions) buildOptionsInto(sheetOptions) } catch {}
          try { window.__wow_formatTouched = true } catch {}
        }
      } catch {}
    })
  } catch {}
  } catch (e) {
    try { console.error('[BuyBox] init failed', e) } catch {}
  }
})
</script>

<template>
  <!-- DESKTOP BUYBOX -->
  <aside class="buybox" id="buybox">
      <div class="card p-5 p-md-4">
        <!-- Price row (desktop) -->
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="d-flex align-items-center gap-2">
            <div class="price" id="price">£0.00</div>
            <div class="compare" id="compare" style="display:none"></div>
          </div>
          <div class="d-flex align-items-center gap-2 text-secondary small">
            <div class="stars" id="stars"></div>
            <div id="ratingText"></div>
            <div id="variantId" class="d-none d-md-inline"></div>
          </div>
        </div>

        <!-- Dynamic options will be injected here by JS -->
        <div id="options"></div>

        <!-- Group size (desktop) -->
        <div class="group-range mb-3" id="groupRange" style="display:none">
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

        <!-- Availability chooser (desktop) -->
        <div class="booking-wrap mt-3 mb-2">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-calendar-event"></i>
            <div class="fw-semibold">Availability</div>
            <span class="badge rounded-pill ms-auto badge-note">Select a date or decide later</span>
          </div>

          <div class="d-flex gap-2 mb-2" role="group" aria-label="Availability choice">
            <button type="button" class="btn btn-outline-secondary active" id="btnBookLater">
              <i class="bi bi-clock-history me-1"></i>Decide later
            </button>
            <button type="button" class="btn btn-outline-success" id="btnBookNow">
              <i class="bi bi-check2-circle me-1"></i>Select date
            </button>
          </div>

          <div class="small" id="bookingSelectionRow" style="display:none">
            <span class="sel-pill">
              <i class="bi bi-calendar2-week"></i>
              <span id="bookingSelectionText"></span>
              <a class="edit text-decoration-none ms-1" id="changeBooking">Change</a>
            </span>
          </div>

          <!-- countdown banner (hidden until a time is selected) -->
          <div class="small text-warning d-flex align-items-center gap-2 mt-2" id="pillHoldBanner" style="display:none !important;" hidden>
            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
            <span>Held for <span id="pillHoldCountdown">10:00</span></span>
          </div>

          <div class="small text-secondary mt-2" id="bookNote">
            Choose a date now, or decide later. We’ll still secure your order and you can confirm with your practitioner anytime.
          </div>

          <!-- Hidden booking fields -->
          <input type="hidden" id="bookingChoice" value="later">
          <input type="hidden" id="preferredDateValue" value="">
          <input type="hidden" id="preferredTimeValue" value="">
          <input type="hidden" id="preferredTZValue" value="">
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
          <button class="btn btn-main btn-lg" id="addBtn">Add to cart</button>
        </div>

        <div class="mode-note" id="modeNote"></div>

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

  <teleport to="body">
  <!-- MOBILE STICKY BAR (only visible ≤991px) -->
  <div id="mobileBar" class="d-lg-none">
      <div class="m-left">
        <div class="price" id="mPrice">£0.00</div>
        <div class="rating">
          <div class="stars" id="mStars"></div>
          <div class="text-secondary small" id="mRatingText"></div>
        </div>
      </div>
      <div class="m-right">
        <button class="btn btn-main" id="mobileAdd">Add to cart</button>
      </div>
    </div>

  <!-- Toast -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div id="addToast" class="toast text-bg-dark border-0" role="status" aria-live="polite" aria-atomic="true">
        <div class="d-flex"><div class="toast-body">Added to your cart</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
  </div>

  <!-- CONFIG MODAL (mobile) -->
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
                <div class="fw-semibold">Availability</div>
                <span class="badge rounded-pill ms-auto badge-note">Select a date or decide later</span>
              </div>
              <div class="d-flex gap-2" role="group" aria-label="Availability choice (sheet)">
                <button type="button" class="btn btn-outline-secondary active" id="sheetBookLater">
                  <i class="bi bi-clock-history me-1"></i>Decide later
                </button>
                <button type="button" class="btn btn-outline-success" id="sheetBookNow">
                  <i class="bi bi-check2-circle me-1"></i>Select date
                </button>
              </div>
              <div class="small text-secondary mt-2" id="sheetBookingNote">Choose now or decide later — your order is still secured.</div>
            </div>
          </div>
          <div class="modal-footer">
            <div class="me-auto small text-secondary" id="sheetSubtotal">Subtotal: £0.00</div>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-main" id="sheetConfirm">Confirm</button>
          </div>
        </div>
      </div>
  </div>

  <!-- Booking Modal (calendar/time) -->
  <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" id="bookingModalContent">
          <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
              <div v-if="bookingHeader.photo" class="flex-shrink-0">
                <img
                  :src="bookingHeader.photo"
                  :alt="bookingHeader.title + ' practitioner photo'"
                  class="rounded-circle border"
                  style="width:52px;height:52px;object-fit:cover;"
                >
              </div>
              <div>
                <div class="text-muted small">{{ bookingHeader.label }}</div>
                <h5 class="modal-title mb-0" id="bookingModalLabel">{{ bookingHeader.title }}</h5>
                <div v-if="bookingHeader.subtitle" class="small fw-semibold text-secondary">{{ bookingHeader.subtitle }}</div>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-4">
              <!-- LEFT: Calendar -->
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

              <!-- RIGHT: Times -->
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
            <button type="button" class="btn btn-main" id="confirmBooking" disabled>Confirm selection</button>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<style scoped>
/* Container + layout */
.buybox{position:sticky;top:90px;max-width:420px;margin-left:auto;margin-right:0}
.buybox .card{background:#fff;backdrop-filter:saturate(1.2) blur(12px);-webkit-backdrop-filter:saturate(1.2) blur(12px);border:1px solid #ddd;border-radius:11px;box-shadow:0 4px 10px rgba(0,0,0,.1)}
.chips{display:flex;gap:.5rem;flex-wrap:nowrap;width:100%}
.chip{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border:1px solid rgba(0,0,0,.08);background:#fff;border-radius:16px;padding:.6rem .9rem;font-weight:600;flex:1 1 0}
.chip.active{box-shadow:0 6px 14px rgba(16,185,129,.25);transform:translateY(-1px)}
.price{font-size:2rem;font-weight:800;color:#0f172a}
.compare{color:#9ca3af;text-decoration:line-through;font-size:1rem;margin-left:.5rem}
/* Dynamic option pills (created via JS) */
:deep(.pills){display:flex;flex-wrap:wrap;gap:.5rem}
:deep(.pill){border:1px solid #549483;background:#fff;border-radius:12px;padding:.6rem .9rem;font-weight:600}
:deep(.pill[aria-checked="true"]){border-color:#549483;box-shadow:0 6px 16px rgba(0,0,0,.15)}
.stepper{display:inline-grid;grid-template-columns:44px 64px 44px;align-items:center;border:1px solid rgba(0,0,0,.12);border-radius:14px;background:#fff}
.stepper button{border:0;background:transparent;height:46px;font-size:20px}
.stepper input{border:0;background:transparent;height:46px;text-align:center;font-weight:700}
.meta{display:flex;gap:1rem;flex-wrap:wrap;color:#374151}
.meta .item{display:flex;align-items:center;gap:.5rem;font-size:.9rem}
.trust{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}
.trust .cell{display:flex;align-items:center;gap:.6rem;border:1px solid rgba(0,0,0,.06);background:rgba(255,255,255,.8);border-radius:12px;padding:.75rem}
.mode-note{font-size:.85rem;color:#6b7280}
.rating{display:flex;align-items:center;gap:.4rem}
.btn-main{background:#549483;color:#fff;border:none}
.btn-basket{background:#f1f3f5;color:#111827;border:1px solid #d0d5dd}

/* Group size (stepper) */
.group-range{display:none}

/* Availability block */
.booking-wrap{border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fafafa}
.badge-note{font-size:.8rem;background:#eef7f2;color:#185a44;border:1px solid #c7e5d9}
.sel-pill{display:inline-flex;gap:.5rem;align-items:center;border:1px solid #e5e7eb;background:#fff;border-radius:999px;padding:.25rem .6rem}
.sel-pill .edit{cursor:pointer}

/* Calendar (dynamic elements created via JS) */
:deep(.cal-head){display:flex;align-items:center;justify-content:space-between}
:deep(.cal-month){font-weight:700}
:deep(.cal-grid){display:grid;grid-template-columns:repeat(7,1fr);gap:.5rem}
:deep(.cal-dayname){text-transform:uppercase;font-size:.75rem;color:#6b7280}
:deep(.cal-cell){position:relative;border:1px solid #e5e7eb;border-radius:14px;height:42px;display:flex;align-items:center;justify-content:center;background:#fff;cursor:pointer}
:deep(.cal-cell[aria-disabled="true"]){opacity:.4;cursor:not-allowed;background:#f8f9fa}
:deep(.cal-cell.active){outline:2px solid #549483;box-shadow:0 6px 16px rgba(0,0,0,.12)}
:deep(.slot){border:1px solid #e5e7eb;border-radius:10px;padding:.5rem .7rem;background:#fff;cursor:pointer}
:deep(.slot.active){border-color:#549483;outline:2px solid #549483}
:deep(.slot.reserved){border-color:#f59e0b;background:#fffbe6;cursor:not-allowed;opacity:.9}
:deep(.tz-pill){border:1px solid #e5e7eb;border-radius:12px;padding:.35rem .6rem;background:#fff}
:deep(.calendar-side){border-left:1px solid #f0f2f4}

/* Mobile-first calendar UI inside booking modal */
@media (max-width: 991px){
  .calendar-side{display:none;border-left:none;border-top:1px solid #f0f2f4;padding-top:1rem;margin-top:1rem}
  :deep(.modal-content.mobile-times) .calendar-side{display:block}
  :deep(.modal-content.mobile-times) .left-col{display:none !important}
  :deep(#slotList .slot){display:block;width:100%;text-align:left}
}

/* Force-hide selection row regardless of JS inline styles */
#bookingSelectionRow{ display: none !important }

/* ---------------- MOBILE ONLY UI ---------------- */
#mobileBar{display:none}

@media (max-width: 991px){
  .buybox{display:none !important}
  #mobileBar{
      display:flex;align-items:center;justify-content:space-between;gap:.75rem;
      position:fixed;left:0;right:0;bottom:0;z-index:1030;
      background:#fff;border-top:1px solid #e5e7eb;padding:.6rem .9rem;
      box-shadow:0 -6px 14px rgba(0,0,0,.06)
  }
  #mobileBar .m-left{display:flex;flex-direction:column}
  #mobileBar .price{font-size:1.35rem;margin:0}
  #mobileBar .rating{gap:.35rem;margin-top:.1rem}
  #mobileBar .rating .text-secondary{font-size:.8rem}
  #mobileBar .btn-main{padding:.6rem 1rem}
  #mobileBar .btn-basket{padding:.6rem 1rem}
}

@media (min-width: 1200px){.modal-xl{--bs-modal-width: 882px;}}

/* ===== Hourglass flip animation (applies while timer runs) ===== */
.hourglass-spin{ display:inline-block; transform-origin:50% 50%; animation:hourglassFlip 1.6s cubic-bezier(.35,.11,.27,.99) infinite }
@keyframes hourglassFlip{ 0%,12%{transform:rotate(0deg)} 45%,55%{transform:rotate(180deg)} 88%,100%{transform:rotate(360deg)} }
#pillHoldBanner.hourglass-active{ animation:bannerPulse 1.6s ease-in-out infinite }
@keyframes bannerPulse{ 0%,100%{filter:none} 50%{filter:drop-shadow(0 0 6px rgba(245,158,11,.45))} }
@media (prefers-reduced-motion:reduce){ .hourglass-spin,#pillHoldBanner.hourglass-active{animation:none} }
</style>
