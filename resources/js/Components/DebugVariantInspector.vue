<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  product: { type: Object, required: true },
  inline: { type: Boolean, default: false },
  showTable: { type: Boolean, default: true },
})

const options = computed(() => Array.isArray(props.product?.options) ? props.product.options : [])
const variants = computed(() => Array.isArray(props.product?.variants) ? props.product.variants : [])

// Panel visibility (hidden by default)
const open = ref(false)
// Avoid writing the URL while we are initialising from it
const suppressUrl = ref(false)
// Avoid emitting variant/url updates while applying external events
const applyingExternal = ref(false)

function fmtPrice(raw, c='GBP'){
  const n = Number(raw)
  try {
    if (Number.isFinite(n)) return new Intl.NumberFormat(undefined,{style:'currency',currency:c}).format(n)
    if (Number.isFinite(n)) return new Intl.NumberFormat(undefined,{style:'currency',currency:c}).format(n)
  } catch {}
  return String(raw ?? '—')
}

// Selection mirrors option order
const sel = ref([])
function initSel(){
  try {
    const arr = options.value || []
    const next = []
    for (let i=0;i<arr.length;i++){
      const values = (arr[i]?.values || [])
      const first = values[0]
      next[i] = String((first?.value ?? first) ?? '')
    }
    sel.value = next
    // If Persons' first value represents a group (numeric > 2), activate group mode and set proper group index
    const pi = personsIdx.value
    if (pi>=0){
      const firstPersons = String(sel.value[pi] || '')
      const n = numFrom(firstPersons)
      if (n!=null && n > 2){
        groupMode.value = true
        // ensure groupIndex aligns to the first groupValues entry
        const gv = groupValues.value
        const found = gv.findIndex(v => numFrom(v) === n)
        groupIndex.value = found>=0 ? found : 0
        // normalize selection to the canonical value from groupValues (ensures exact equality with tokens)
        if (gv.length){ sel.value[pi] = String(gv[groupIndex.value]) }
      } else {
        groupMode.value = false
      }
    }
  } catch { sel.value = [] }
}

// Resolve exact variant with numeric-aware equality for People/Sessions
const sessionsIdx = computed(() => {
  const arr = options.value || []
  return arr.findIndex(o => /session/i.test(String(o?.name||o?.meta_name||'')))
})
function tokensEqual(i, want, tok){
  try{
    const wi = personsIdx.value
    const si = sessionsIdx.value
    if (i === wi || i === si){
      const wn = numFrom(want)
      const tn = numFrom(tok)
      if (wn != null && tn != null) return wn === tn
    }
    return String(want) === String(tok)
  } catch { return String(want) === String(tok) }
}
function findVariantExact(){
  try{
    const list = variants.value || []
    const want = (sel.value||[]).map(v => String(v))
    for (const v of list){
      const toks = Array.isArray(v?.options) ? v.options.map(x=>String(x)) : []
      let ok = true
      for (let i=0;i<want.length;i++){
        const w = String(want[i]||'')
        const t = String(toks[i]||'')
        if (!w) continue
        if (!tokensEqual(i, w, t)) { ok = false; break }
      }
      if (ok) return v
    }
    return null
  }catch{ return null }
}
function findVariantBest(){
  const exact = findVariantExact()
  if (exact) return exact
  try{
    const list = variants.value || []
    if (!list.length) return null
    const want = (sel.value||[]).map(v => String(v))
    let pool = list.slice()
    for (let i=0;i<want.length;i++){
      const w = String(want[i]||'')
      if (!w) continue
      const hits = pool.filter(v => {
        const t = String((Array.isArray(v?.options)? v.options[i]: '')||'')
        return tokensEqual(i, w, t)
      })
      if (hits.length) pool = hits
    }
    return pool[0] || list[0] || null
  }catch{ return null }
}
const activeVariant = computed(() => findVariantBest())

// Format controls derived from Location(s) option
function norm(s){ return String(s||'').toLowerCase().replace(/[^a-z0-9]+/g,'') }
const locIdx = computed(() => {
  const arr = options.value || []
  const i = arr.findIndex(o => /location/i.test(String(o?.name||o?.meta_name||'')))
  return i
})
// For inline mode (payment module), do not render the Location(s) option row
const optionsToRender = computed(() => {
  const arr = options.value || []
  const li = locIdx.value
  const list = arr.map((opt, i) => ({ opt, oi: i }))
  if (props.inline && li >= 0) return list.filter(x => x.oi !== li)
  return list
})
const locValues = computed(() => {
  const idx = locIdx.value
  const arr = options.value || []
  const vals = idx>=0 ? (arr[idx]?.values || []) : []
  return vals.map(v => String(v?.value ?? v))
})
const supportsOnline = computed(() => locValues.value.some(v => norm(v) === 'online'))
const supportsPhysical = computed(() => locValues.value.some(v => norm(v) !== 'online'))
const selectedFormat = computed(() => {
  const idx = locIdx.value
  if (idx < 0) return null
  const cur = String(sel.value?.[idx] || '')
  return /online/i.test(cur) ? 'Online' : 'In-person'
})
function setFormat(fmt){
  const idx = locIdx.value
  if (idx < 0) return
  const wantOnline = /online/i.test(String(fmt))
  // Desired: pick a variant that aligns with current non-location selections
  const base = (sel.value || []).map(v => String(v))
  const list = variants.value || []
  const pool = list.filter(v => {
    const toks = Array.isArray(v?.options) ? v.options.map(x=>String(x)) : []
    const tok = toks[idx] ? norm(toks[idx]) : ''
    const isOnline = (tok === 'online')
    return wantOnline ? isOnline : (!isOnline && tok !== '')
  })
  let target = null
  // 1) Exact match on all non-location dimensions
  target = pool.find(v => {
    const toks = Array.isArray(v?.options) ? v.options.map(x=>String(x)) : []
    for (let i=0;i<Math.max(base.length, toks.length);i++){
      if (i === idx) continue
      const w = String(base[i]||'')
      const t = String(toks[i]||'')
      if (w && t && w !== t) return false
    }
    return true
  }) || null
  // 2) Best match: maximize number of matched non-location dimensions
  if (!target && pool.length){
    let best = null; let bestScore = -1
    for (const v of pool){
      const toks = Array.isArray(v?.options) ? v.options.map(x=>String(x)) : []
      let score = 0
      for (let i=0;i<Math.max(base.length, toks.length);i++){
        if (i === idx) continue
        const w = String(base[i]||'')
        const t = String(toks[i]||'')
        if (w && t && w === t) score += 1
      }
      if (score > bestScore){ best = v; bestScore = score }
    }
    target = best
  }
  if (target){
    const toks = Array.isArray(target?.options) ? target.options.map(x=>String(x)) : []
    const arr = options.value || []
    const next = []
    for (let i=0;i<arr.length;i++){ next[i] = String(toks[i] ?? (arr[i]?.values?.[0] ?? '')) }
    sel.value = next
    try{ updateUrl(); notifyVariant() }catch{}
    return
  }
  // 3) Fallback: set just the location selection from option values
  if (wantOnline) sel.value[idx] = 'Online'
  else { sel.value[idx] = (locValues.value.find(v => norm(v) !== 'online') || '') }
  try{ updateUrl(); notifyVariant() }catch{}
}

// URL sync: only set variant id (debug)
function setFromVariantId(vid){
  try{
    const v = (variants.value||[]).find(x => String(x?.id) === String(vid))
    if (!v) return false
    const toks = Array.isArray(v?.options) ? v.options.map(x=>String(x)) : []
    const arr = options.value || []
    const next = []
    for (let i=0;i<arr.length;i++) next[i] = String(toks[i] ?? (arr[i]?.values?.[0] ?? ''))
    sel.value = next
    // sync group mode for Persons
    const pi = personsIdx.value
    if (pi>=0){
      const cur = String(sel.value[pi]||'')
      const n = numFrom(cur)
      const gv = groupValues.value
      if (n!=null && n>2){
        groupMode.value = true
        const idx = gv.findIndex(x => numFrom(x)===n)
        groupIndex.value = idx>=0 ? idx : 0
      } else groupMode.value = false
    }
    // Ensure Location(s) value mirrors the variant tokens even if order differs
    try{
      const li = (options.value||[]).findIndex(o => /location/i.test(String(o?.name||o?.meta_name||'')))
      if (li>=0){
        const vals = Array.isArray(options.value?.[li]?.values) ? options.value[li].values : []
        const toStr = (x)=>String((x&&typeof x==='object'&&x.value!=null)?x.value:x)
        const list = vals.map(v=>toStr(v).trim())
        const norm = (s)=>String(s||'').toLowerCase().replace(/[^a-z0-9]+/g,'')
        const wantTok = (()=>{
          // Prefer explicit Online
          const hasOnline = toks.some(t => /online/i.test(String(t)))
          if (hasOnline) return 'Online'
          let best=''
          for (const t of toks){ const n=norm(t); if (n && !/^\d+$/.test(n) && n!=='online'){ best=t; break } }
          return best
        })()
        if (wantTok){
          const lc = wantTok.trim().toLowerCase()
          let match = list.find(v => v.toLowerCase() === lc)
          if (!match){ const wn = norm(wantTok); match = list.find(v => { const n=norm(v); return n&&(n===wn||n.includes(wn)||wn.includes(n)) }) || '' }
          if (match) sel.value[li] = match
        }
      }
    }catch{}
    return true
  }catch{ return false }
}
function updateUrl(){
  try{
    const url = new URL(window.location.href)
    // Remove option-derived params so they don't override the variant
    url.searchParams.delete('format')
    url.searchParams.delete('people')
    url.searchParams.delete('sessions')
    url.searchParams.delete('location')
    url.searchParams.delete('group')
    const vid = activeVariant.value?.id
    if (vid!=null) url.searchParams.set('variant', String(vid))
    else url.searchParams.delete('variant')
    window.history.replaceState({},'',url.toString())
  }catch{}
}
function notifyVariant(){
  try {
    const ev = new CustomEvent('wow:variant-select', { detail: { id: activeVariant.value?.id ?? null, selection: (sel.value||[]).slice() } })
    window.dispatchEvent(ev)
  } catch {}
}
onMounted(() => {
  initSel()
  try{
    const url = new URL(window.location.href)
    const vid = url.searchParams.get('variant')
    if (vid){
      suppressUrl.value = true
      setFromVariantId(vid)
      // release on next microtask to allow watchers after initial state
      queueMicrotask(() => { suppressUrl.value = false; notifyVariant() })
    } else {
      notifyVariant()
    }
  }catch{}
  if (!suppressUrl.value) updateUrl()
  // Keep inline inspector in sync with external location/format selection
  try {
    const onFmt = (ev) => {
      try {
        const f = String(ev?.detail?.format || '')
        if (!f) return
        const arr = options.value || []
        const fi = arr.findIndex(o => /format/i.test(String(o?.name||o?.meta_name||'')))
        if (fi >= 0) {
          applyingExternal.value = true
          if (/online/i.test(f)) sel.value[fi] = 'Online'
          else sel.value[fi] = 'In-person'
          queueMicrotask(() => { applyingExternal.value = false })
        }
      } catch {}
    }
    const onLoc = (ev) => {
      try {
        const lbl = String(ev?.detail?.label || '')
        if (!lbl) return
        const arr = options.value || []
        const li = arr.findIndex(o => /location/i.test(String(o?.name||o?.meta_name||'')))
        if (li >= 0) {
          const values = Array.isArray(arr[li]?.values) ? arr[li].values : []
          const toStr = (x)=>String((x&&typeof x==='object'&&x.value!=null)?x.value:x)
          const list = values.map(v=>toStr(v).trim())
          const lc = lbl.trim().toLowerCase()
          let match = list.find(v => v.toLowerCase() === lc)
          if (!match){
            const norm = (s)=>String(s||'').toLowerCase().replace(/[^a-z0-9]+/g,'')
            const want = norm(lbl)
            match = list.find(v => { const n=norm(v); return n && (n===want || n.includes(want) || want.includes(n)) }) || ''
          }
          if (match) sel.value[li] = match
          applyingExternal.value = true
          queueMicrotask(() => { applyingExternal.value = false })
        }
      } catch {}
    }
    window.addEventListener('wow:select-format', onFmt)
    window.addEventListener('wow:select-location', onLoc)
    try { window.__dv_onFmt = onFmt; window.__dv_onLoc = onLoc } catch {}
  } catch {}
})
onBeforeUnmount(() => {
  try { if (window.__dv_onFmt) window.removeEventListener('wow:select-format', window.__dv_onFmt) } catch {}
  try { if (window.__dv_onLoc) window.removeEventListener('wow:select-location', window.__dv_onLoc) } catch {}
})
watch(sel, () => { if (suppressUrl.value || applyingExternal.value) return; updateUrl(); notifyVariant() }, { deep: true })
watch(activeVariant, () => { if (suppressUrl.value || applyingExternal.value) return; updateUrl(); notifyVariant() })

// For the table: normalize rows aligned to option count
const tableCols = computed(() => (options.value||[]).map(o => (o.name || o.meta_name || 'Option')).map(String))
const tableRows = computed(() => {
  const cols = tableCols.value
  return (variants.value||[]).map(v => {
    const toks = Array.isArray(v?.options) ? v.options.map(x=>String(x)) : []
    const row = {}
    cols.forEach((_, idx) => { row[idx] = toks[idx] || '' })
    return { id: v.id, price: v.price, tokens: row }
  })
})

// Persons (group) controls
const personsIdx = computed(() => {
  const arr = options.value || []
  return arr.findIndex(o => /person/i.test(String(o?.name||o?.meta_name||'')))
})
function numFrom(s){ const m = String(s||'').match(/\d+/); return m ? parseInt(m[0],10) : null }
const personValues = computed(() => {
  const pi = personsIdx.value
  const arr = options.value || []
  const vals = pi>=0 ? (arr[pi]?.values || []) : []
  return vals.map(v => String(v?.value ?? v))
})
const groupValues = computed(() => {
  const arr = personValues.value
  const grp = arr.filter(v => { const n = numFrom(v); return n!=null && n > 2 })
  // sort ascending by numeric
  return [...grp].sort((a,b) => (numFrom(a)??0) - (numFrom(b)??0))
})
const personsHasTwo = computed(() => personValues.value.some(v => numFrom(v) === 2))
const groupMode = ref(false)
const groupIndex = ref(0)
function syncGroupIndex(){
  const pi = personsIdx.value
  if (pi<0) return
  const cur = String(sel.value?.[pi] || '')
  const n = numFrom(cur)
  const arr = groupValues.value
  if (!arr.length){ groupIndex.value = 0; return }
  const idx = arr.findIndex(v => numFrom(v) === n)
  groupIndex.value = (idx>=0 ? idx : 0)
}
watch(sel, () => syncGroupIndex(), { deep: true })
function toggleGroup(){
  groupMode.value = !groupMode.value
  if (groupMode.value){
    // Ensure selection is in group range; if not, jump to first group value
    const pi = personsIdx.value
    const arr = groupValues.value
    if (pi>=0 && arr.length){ sel.value[pi] = String(arr[0]); groupIndex.value = 0; try{ updateUrl(); notifyVariant() }catch{} }
  }
}
function incGroup(delta){
  const pi = personsIdx.value
  if (pi<0) return
  const arr = groupValues.value
  if (!arr.length) return
  let idx = groupIndex.value + (delta<0 ? -1 : 1)
  idx = Math.max(0, Math.min(arr.length - 1, idx))
  groupIndex.value = idx
  sel.value[pi] = String(arr[idx])
  try{ updateUrl(); notifyVariant() }catch{}
}

// Unified selection setter to collapse group mode when choosing 1 or 2
function setSelection(oi, value){
  try{
    const pi = personsIdx.value
    if (oi === pi){
      const n = numFrom(value)
      if (n != null && n <= 2) groupMode.value = false
    }
    sel.value[oi] = String(value)
    try{ updateUrl(); notifyVariant() }catch{}
  }catch{ sel.value[oi] = String(value) }
}

</script>

<template>
  <template v-if="!inline">
    <!-- Toggle button (fixed on left side) -->
    <button type="button" class="dv-toggle" :aria-expanded="open ? 'true' : 'false'" title="Debug Variant Inspector" @click="open = !open">
      <span v-if="!open">▶</span>
      <span v-else>◀</span>
    </button>

    <!-- Slide-in debug panel -->
    <div class="dv-panel" :class="{ open }">
      <div class="dv-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <strong>Debug Variant Inspector</strong>
          <div class="text-secondary small">Variant ID: <span>{{ activeVariant?.id ?? '—' }}</span> • Price: <span>{{ fmtPrice(activeVariant?.price, product?.currency || 'GBP') }}</span></div>
        </div>

      <!-- Format (derived from Locations) -->
      <div v-if="locIdx>=0 && (supportsOnline || supportsPhysical)" class="mb-2">
        <div class="text-secondary small mb-1">Format</div>
        <div class="d-flex flex-wrap gap-2">
          <button v-if="supportsOnline" type="button" class="btn btn-sm" :class="selectedFormat==='Online' ? 'btn-primary' : 'btn-outline-secondary'" @click="() => setFormat('Online')">Online</button>
          <button v-if="supportsPhysical" type="button" class="btn btn-sm" :class="selectedFormat==='In-person' ? 'btn-primary' : 'btn-outline-secondary'" @click="() => setFormat('In-person')">In-person</button>
        </div>
      </div>

      <!-- Options -->
      <div v-for="(opt, oi) in options" :key="oi" class="mb-2">
        <div class="text-secondary small mb-1">{{ opt?.name || opt?.meta_name || ('Option ' + (oi+1)) }}</div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <template v-for="(val, vi) in (opt?.values || [])" :key="vi">
            <!-- For Persons: only show buttons up to 2; hide 3+ (handled by Group) -->
            <button v-if="oi!==personsIdx || !numFrom(val?.value ?? val) || numFrom(val?.value ?? val) <= 2"
                    type="button"
                    :class="['btn','btn-sm', (String(sel[oi]||'')===String(val?.value ?? val)) ? 'btn-primary' : 'btn-outline-secondary']"
                    @click="() => setSelection(oi, (val?.value ?? val))">
              {{ (val && typeof val==='object') ? (val.value ?? String(val)) : String(val) }}
            </button>
            <!-- Insert Group toggle after the '2' value when Persons has 3+ values -->
            <template v-if="oi===personsIdx && groupValues.length && numFrom(val?.value ?? val)===2">
              <button type="button" class="btn btn-sm" :class="groupMode ? 'btn-primary' : 'btn-outline-secondary'" @click="toggleGroup">Group</button>
            </template>
          </template>
          <!-- If there is no explicit '2' value but we have group values, still show Group button -->
          <template v-if="oi===personsIdx && groupValues.length && !personsHasTwo">
            <button type="button" class="btn btn-sm" :class="groupMode ? 'btn-primary' : 'btn-outline-secondary'" @click="toggleGroup">Group</button>
          </template>
        </div>
        <!-- Plus/minus row appears beneath when in group mode -->
        <div v-if="oi===personsIdx && groupMode && groupValues.length" class="mt-2 d-flex align-items-center gap-2">
          <span class="text-secondary small">Group count</span>
          <button type="button" class="btn btn-sm btn-outline-secondary" @click="() => incGroup(-1)">−</button>
          <div class="px-2 small fw-semibold">{{ groupValues[groupIndex] }}</div>
          <button type="button" class="btn btn-sm btn-outline-secondary" @click="() => incGroup(1)">+</button>
        </div>
      </div>

      <!-- Variants table -->
      <div v-if="showTable" class="table-responsive mt-3">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="white-space:nowrap">Variant ID</th>
              <th v-for="(col, ci) in tableCols" :key="ci">{{ col }}</th>
              <th>Price</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in tableRows" :key="row.id" :class="{'table-primary': String(row.id)===String(activeVariant?.id)}">
              <td><code>{{ row.id }}</code></td>
              <td v-for="(col, ci) in tableCols" :key="ci">{{ row.tokens[ci] }}</td>
              <td>{{ fmtPrice(row.price, product?.currency || 'GBP') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      </div>
    </div>
  </template>
  <template v-else>
    <div class="dv-card dv-inline">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <strong>Debug Variant Inspector</strong>
        <div class="text-secondary small">Variant ID: <span>{{ activeVariant?.id ?? '—' }}</span> • Price: <span>{{ fmtPrice(activeVariant?.price, product?.currency || 'GBP') }}</span></div>
      </div>

      <!-- Format (derived from Locations) -->
      <div v-if="locIdx>=0 && (supportsOnline || supportsPhysical)" class="mb-2">
        <div class="text-secondary small mb-1">Format</div>
        <div class="d-flex flex-wrap gap-2">
          <button v-if="supportsOnline" type="button" class="btn btn-sm" :class="selectedFormat==='Online' ? 'btn-primary' : 'btn-outline-secondary'" @click="() => setFormat('Online')">Online</button>
          <button v-if="supportsPhysical" type="button" class="btn btn-sm" :class="selectedFormat==='In-person' ? 'btn-primary' : 'btn-outline-secondary'" @click="() => setFormat('In-person')">In-person</button>
        </div>
      </div>

      <!-- Options (inline: hide Location(s)) -->
      <div v-for="({opt, oi}) in optionsToRender" :key="oi" class="mb-2">
        <div class="text-secondary small mb-1">{{ opt?.name || opt?.meta_name || ('Option ' + (oi+1)) }}</div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <template v-for="(val, vi) in (opt?.values || [])" :key="vi">
            <!-- For Persons: only show buttons up to 2; hide 3+ (handled by Group) -->
            <button v-if="oi!==personsIdx || !numFrom(val?.value ?? val) || numFrom(val?.value ?? val) <= 2"
                    type="button"
                    :class="['btn','btn-sm', (String(sel[oi]||'')===String(val?.value ?? val)) ? 'btn-primary' : 'btn-outline-secondary']"
                    @click="() => setSelection(oi, (val?.value ?? val))">
              {{ (val && typeof val==='object') ? (val.value ?? String(val)) : String(val) }}
            </button>
            <!-- Insert Group toggle after the '2' value when Persons has 3+ values -->
            <template v-if="oi===personsIdx && groupValues.length && numFrom(val?.value ?? val)===2">
              <button type="button" class="btn btn-sm" :class="groupMode ? 'btn-primary' : 'btn-outline-secondary'" @click="toggleGroup">Group</button>
            </template>
          </template>
          <!-- If there is no explicit '2' value but we have group values, still show Group button -->
          <template v-if="oi===personsIdx && groupValues.length && !personsHasTwo">
            <button type="button" class="btn btn-sm" :class="groupMode ? 'btn-primary' : 'btn-outline-secondary'" @click="toggleGroup">Group</button>
          </template>
        </div>
        <!-- Plus/minus row appears beneath when in group mode -->
        <div v-if="oi===personsIdx && groupMode && groupValues.length" class="mt-2 d-flex align-items-center gap-2">
          <span class="text-secondary small">Group count</span>
          <button type="button" class="btn btn-sm btn-outline-secondary" @click="() => incGroup(-1)">−</button>
          <div class="px-2 small fw-semibold">{{ groupValues[groupIndex] }}</div>
          <button type="button" class="btn btn-sm btn-outline-secondary" @click="() => incGroup(1)">+</button>
        </div>
      </div>
    </div>
  </template>
</template>

<style scoped>
.dv-toggle{ position: fixed; left: 8px; top: 40%; z-index: 2147483646; width: 32px; height: 32px; border-radius: 999px; border:1px solid var(--ink-300); background:#fff; color: var(--ink-800); display:flex; align-items:center; justify-content:center; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
.dv-toggle:hover{ background: var(--ink-50) }
.dv-panel{ position: fixed; left: 0; top: 80px; bottom: 20px; width: 75vw; max-width: 90vw; background: #fff; border-right:1px solid var(--ink-200); box-shadow: 0 4px 24px rgba(0,0,0,.12); transform: translateX(-105%); transition: transform .2s ease-in-out; z-index: 2147483645; overflow:auto; padding: 12px; }
.dv-panel.open{ transform: translateX(0) }
.dv-card{ border:1px dashed var(--ink-300); background:#fffef8; padding:12px; border-radius:8px }
.dv-inline{ position: static; border-style: solid; background:#fff }
</style>
