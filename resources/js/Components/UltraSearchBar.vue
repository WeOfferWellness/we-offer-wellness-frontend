<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { fetchLocations } from '@/services/locations'
import { fetchWhatCategories } from '@/services/whatCategories'
import SearchRangeCalendar from './SearchRangeCalendar.vue'
import { logSearchValues } from '@/services/searchAnalytics'

const props = defineProps({
  idPrefix: { type: String, default: 'ultra' },
  /**
   * When true, the form is visually compact (used for sticky bar).
   */
  compact: { type: Boolean, default: false },
  /**
   * Show separate Group Type segment (Solo/Couple/Group).
   * Defaults to false — group type is not shown unless explicitly enabled.
   */
  showGroupType: { type: Boolean, default: false },
  /**
   * When true, render only the What field and put Search on its right.
   */
  onlyWhat: { type: Boolean, default: false },
})

const root = ref(null)
const isFlexible = ref(false)
const desktopNativeWhen = ref(false)
const whatValue = ref('')
const WHAT_LIMIT = 5

const state = {
  who: { adults: 2, children: 0 },
  range: { start: null, end: null },
  groupType: 'Solo',
}

let detachGlobal = null
let groupTypeTouched = false
let lastExactWhen = ''
const paneHideTimers = new WeakMap()
const paneOpenFrames = new WeakMap()

// Helpers to scope queries within this component instance
const q = (sel) => root.value?.querySelector(sel)
const qa = (sel) => Array.from(root.value?.querySelectorAll(sel) || [])
const id = (name) => `${props.idPrefix}-${name}`

const whatFilled = computed(() => whatValue.value.trim().length > 0)

function syncDesktopNativeWhen() {
  if (typeof window === 'undefined') {
    desktopNativeWhen.value = false
    return
  }
  try {
    desktopNativeWhen.value = window.matchMedia('(min-width: 992px)').matches
  } catch {
    desktopNativeWhen.value = false
  }
}

function parseDesktopDate(value) {
  const raw = String(value || '').trim()
  if (!raw) return null

  const iso = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (iso) {
    const date = new Date(Number(iso[1]), Number(iso[2]) - 1, Number(iso[3]))
    return Number.isNaN(date.getTime()) ? null : date
  }

  const parts = raw.match(/^(\d{1,2})\s+([A-Za-z]{3})\s+(\d{4})$/)
  if (!parts) return null

  const monthMap = {
    jan: 0,
    feb: 1,
    mar: 2,
    apr: 3,
    may: 4,
    jun: 5,
    jul: 6,
    aug: 7,
    sep: 8,
    oct: 9,
    nov: 10,
    dec: 11,
  }
  const month = monthMap[parts[2].toLowerCase()]
  if (month === undefined) return null

  const date = new Date(Number(parts[3]), month, Number(parts[1]))
  return Number.isNaN(date.getTime()) ? null : date
}

function toDesktopISODate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function setDesktopWhenValue(value) {
  const whenInput = q(`#${id('when')}`)
  if (!whenInput) return

  const nextValue = String(value || '').trim()
  whenInput.value = nextValue
  if (nextValue) {
    whenInput.dataset.rangeStart = nextValue
    whenInput.dataset.rangeEnd = nextValue
  } else {
    delete whenInput.dataset.rangeStart
    delete whenInput.dataset.rangeEnd
  }
}

function syncDesktopWhenFromValue(value) {
  const parsed = parseDesktopDate(value)
  if (!parsed) return false
  setDesktopWhenValue(toDesktopISODate(parsed))
  return true
}

function closeAll() {
  qa('.pane').forEach((p) => hidePane(p))
}

function updatePaneOverlayState() {
  if (typeof document === 'undefined') return
  const anyPaneVisible = qa('.pane').some((pane) => !pane.classList.contains('d-none'))
  document.body.classList.toggle('wow-search-pane-open', anyPaneVisible)
}

function hidePane(pane, immediate = false) {
  if (!pane) return

  const pending = paneHideTimers.get(pane)
  if (pending) {
    clearTimeout(pending)
    paneHideTimers.delete(pane)
  }

  const openFrame = paneOpenFrames.get(pane)
  if (openFrame) {
    cancelAnimationFrame(openFrame)
    paneOpenFrames.delete(pane)
  }

  pane.setAttribute('aria-hidden', 'true')

  if (immediate) {
    pane.classList.add('d-none')
    pane.classList.remove('is-open', 'is-closing')
    updatePaneOverlayState()
    return
  }

  if (pane.classList.contains('d-none')) return

  pane.classList.remove('is-open')
  pane.classList.add('is-closing')

  const timer = window.setTimeout(() => {
    pane.classList.add('d-none')
    pane.classList.remove('is-closing')
    paneHideTimers.delete(pane)
    updatePaneOverlayState()
  }, 180)

  paneHideTimers.set(pane, timer)
}

function showPane(pane) {
  if (!pane) return

  const pending = paneHideTimers.get(pane)
  if (pending) {
    clearTimeout(pending)
    paneHideTimers.delete(pane)
  }

  pane.classList.remove('d-none', 'is-closing')
  pane.setAttribute('aria-hidden', 'false')

  const openFrame = requestAnimationFrame(() => {
    pane.classList.add('is-open')
    paneOpenFrames.delete(pane)
    updatePaneOverlayState()
  })
  paneOpenFrames.set(pane, openFrame)
}

function parseWhereValues(raw = '') {
  return raw
    .split(',')
    .map((part) => part.replace(/\s+/g, ' ').trim())
    .filter(Boolean)
}

function payload() {
  const what = whatValue.value.trim()
  const whereList = parseWhereValues(q(`#${id('where')}`)?.value || '')
  const whenInput = q(`#${id('when')}`)
  const when = isFlexible.value ? '' : (whenInput?.value?.trim() || '')
  const whenStart = whenInput?.dataset?.rangeStart || ''
  const whenEnd = whenInput?.dataset?.rangeEnd || ''
  return { what, whereList, when, whenStart, whenEnd, flexible: isFlexible.value, who: { ...state.who } }
}

function onSubmit() {
  const whatInput = q(`#${id('what')}`)
  if (!whatFilled.value) {
    if (whatInput) {
      whatInput.setCustomValidity('Please enter what you want to search for.')
      whatInput.reportValidity()
      whatInput.setCustomValidity('')
      whatInput.focus()
    }
    return
  }

  const p = payload()
  logSearchValues({
    searchTerm: p.what,
    locationQuery: p.whereList.join(', '),
    source: props.idPrefix === 'modal' || String(props.idPrefix).includes('header') ? 'header-modal' : 'hero',
  })
  const params = new URLSearchParams()
  if (p.what) params.set('what', p.what)
  if (p.whereList.length) params.set('where', p.whereList.join(','))
  if (p.flexible) params.set('flexible', '1')
  else if (p.when) params.set('when', p.when)
  if (p.whenStart) params.set('when_start', p.whenStart)
  if (p.whenEnd) params.set('when_end', p.whenEnd)
  params.set('adults', String(p.who.adults))
  // No children parameter (UI removed)
  // Include group_type from the Who panel selection
  if (state.groupType) params.set('group_type', String(state.groupType).trim().toLowerCase())
  router.visit(`/search?${params.toString()}`, { method: 'get' })
}

function setDuration(days) {
  const api = typeof window !== 'undefined' ? window.__WOWRangeCalendars?.[props.idPrefix] : null
  if (api?.setDuration) {
    api.setDuration(days)
  }
}

function setFlexible(flag) {
  const next = !!flag
  isFlexible.value = next
  if (next) {
    qa(`#${id('seg-when')} .chip`).forEach((c) => c.classList.remove('primary'))
  } else {
    q(`#${id('chip-exact')}`)?.classList.add('primary')
  }
}

// Update the WHO summary/counts in the UI from state
function updateWhoSummary() {
  const adultsEl = q(`#${id('adults-val')}`)
  if (adultsEl) adultsEl.textContent = String(state.who.adults)
  const summary = q(`#${id('who-summary')}`)
  if (summary)
    summary.textContent = `${state.who.adults} adult${state.who.adults === 1 ? '' : 's'} · ${state.groupType}`
}

function autoGroupTypeForAdults(n){
  if (n <= 1) return 'Solo'
  if (n === 2) return 'Couple'
  return 'Group'
}

function syncGroupSelection(){
  const list = q(`#${id('group-type-list')}`)
  if (!list) return
  qa(`#${id('group-type-list')} .item`).forEach((b) => {
    const v = b.dataset.group || ''
    b.setAttribute('aria-selected', v === state.groupType ? 'true' : 'false')
  })
}

function fuzzyMatch(qs, s) {
  let q = (qs || '').trim().toLowerCase()
  s = (s || '').toLowerCase()
  if (!q) return { ok: true, ranges: [] }
  let i = 0, ranges = [], start = -1
  for (let j = 0; j < s.length && i < q.length; j++) {
    if (s[j] === q[i]) {
      if (start < 0) start = j
      i++
      if (i === q.length) {
        ranges.push([start, j])
        break
      }
    } else if (start >= 0) {
      start = -1
    }
  }
  return { ok: i === q.length, ranges }
}

function normalizeSearchText(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[\u2018\u2019\u201c\u201d]/g, "'")
    .replace(/[^a-z0-9]+/g, ' ')
    .trim()
}

function matchesSearchQuery(query, item) {
  return searchScore(query, item) < 999
}

function searchScore(query, item) {
  const q = normalizeSearchText(query)
  if (!q) return 999
  const title = normalizeSearchText(item.title)
  const hay = [
    item.title,
    item.cat,
    item.search,
  ]
    .map(normalizeSearchText)
    .join(' ')
  const tokens = q.split(/\s+/).filter(Boolean)
  if (title === q) return 0
  if (title.startsWith(q)) return 1
  if (title.split(/\s+/).some((token) => token.startsWith(q))) return 2
  if (title.includes(q)) return 3
  if (hay.includes(q)) return 4
  if (tokens.length && tokens.every((token) => hay.includes(token))) return 5
  return 999
}

function highlight(text, ranges) {
  if (!ranges.length) return text
  let out = '', last = 0
  ranges.forEach(([a, b]) => {
    out += text.slice(last, a) + `<span class="hl">` + text.slice(a, b + 1) + `</span>`
    last = b + 1
  })
  return out + text.slice(last)
}

import { fetchCatalog } from '@/services/catalog'
let whatCategories = []

function toTitleCase(s){
  return (s||'')
    .toLowerCase()
    .replace(/(^|\s|[-_/])([a-z])/g, (_, a, b) => (a || '') + b.toUpperCase())
}

// WHERE chips helpers
function getWhereChips() {
  return qa(`#${id('seg-where')} .where-chip`)
}
function syncWhereHidden() {
  const input = q(`#${id('where')}`)
  if (!input) return
  const vals = getWhereChips().map((chip) => chip.querySelector('.where-chip-text')?.textContent?.trim() || chip.textContent.trim())
  input.value = vals.join(',')
}
function placeCaretAtEnd(el) {
  try {
    el.focus()
    const range = document.createRange()
    range.selectNodeContents(el)
    range.collapse(false)
    const sel = window.getSelection()
    sel.removeAllRanges()
    sel.addRange(range)
  } catch {}
}
function ensureTrailingSpace(el){
  // add a space text node to make clicking next to chips easier
  if (!el.lastChild || el.lastChild.nodeType !== Node.TEXT_NODE || /\S/.test(el.lastChild.nodeValue || '')) {
    el.appendChild(document.createTextNode(' '))
  }
}
function collapseWhereChipsDisplay(){
  const editor = q(`#${id('where-editor')}`)
  if (!editor) return
  // remove existing +N indicators
  qa(`#${id('seg-where')} .where-plus`).forEach(n => n.remove())
  const chips = qa(`#${id('seg-where')} .where-chip`)
  // reset visibility
  chips.forEach(ch => ch.classList.remove('d-none'))
  if (chips.length > 2) {
    for (let i = 1; i < chips.length; i++) chips[i].classList.add('d-none')
    const more = document.createElement('span')
    more.className = 'where-plus'
    more.textContent = `+${chips.length - 1}`
    // place after first chip for clarity
    if (chips[1]) editor.insertBefore(more, chips[1])
    else editor.appendChild(more)
  }
}
function addWhereChip(val) {
  const editor = q(`#${id('where-editor')}`)
  if (!editor || !val) return
  // Dedupe
  const exists = getWhereChips().some((c) => (c.querySelector('.where-chip-text')?.textContent || c.textContent).trim().toLowerCase() === val.trim().toLowerCase())
  if (exists) { placeCaretAtEnd(editor); return }
  // Remove any plain trailing text (typed search) before adding the chip
  if (editor.lastChild && editor.lastChild.nodeType === Node.TEXT_NODE) {
    editor.lastChild.nodeValue = ''
  }
  const chip = document.createElement('span')
  chip.className = 'kicker where-chip'
  chip.setAttribute('contenteditable', 'false')
  const text = document.createElement('span')
  text.className = 'where-chip-text'
  text.textContent = val
  const btnX = document.createElement('button')
  btnX.type = 'button'
  btnX.className = 'chip-x'
  btnX.setAttribute('aria-label', 'Remove location')
  btnX.innerHTML = '<i class="bi bi-x"></i>'
  btnX.addEventListener('click', (ev) => {
    ev.preventDefault(); ev.stopPropagation();
    chip.remove();
    syncWhereHidden()
    updateWhereDropdownVisibility()
    collapseWhereChipsDisplay()
    placeCaretAtEnd(editor)
  })
  chip.appendChild(text)
  chip.appendChild(btnX)
  editor.appendChild(chip)
  ensureTrailingSpace(editor)
  syncWhereHidden()
  placeCaretAtEnd(editor)
  updateWhereDropdownVisibility()
  collapseWhereChipsDisplay()
}

function updateWhereDropdownVisibility(){
  const pane = q(`#${id('where-pane')}`)
  if (!pane) return
  const selected = new Set(getWhereChips().map((c)=> (c.querySelector('.where-chip-text')?.textContent || c.textContent).trim().toLowerCase()))
  qa(`#${id('where-pane')} .item`).forEach((item)=>{
    const v = (item.dataset.value || '').trim().toLowerCase()
    if (selected.has(v)) item.classList.add('d-none')
    else item.classList.remove('d-none')
  })
}

// Render WHERE suggestions list by injecting buttons into where-list container
function renderWhereList(items){
  const whereListEl = q(`#${id('where-list')}`)
  if (!whereListEl) return
  let html = ''
  items.forEach((it) => {
    const icon = (it.icon || 'geo') === 'wifi' ? '<i class="bi bi-wifi"></i>' : '<i class="bi bi-geo-alt"></i>'
    const sub = it.subtitle ? `<span class=\"text-muted ms-2\">${it.subtitle}</span>` : ''
    html += `<button type=\"button\" class=\"item\" data-value=\"${it.value}\">${icon}<span class=\"title\">${it.label}</span>${sub}</button>`
  })
  whereListEl.innerHTML = html
  updateWhereDropdownVisibility()
}

function renderWhat(qs = '') {
  const list = q(`#${id('what-list')}`)
  if (!list) return false
  const query = (qs || '').trim()
  const filtered = query.length < 2
    ? whatCategories.slice(0, WHAT_LIMIT)
    : whatCategories
      .map((item) => ({ item, score: searchScore(query, item) }))
      .filter((row) => row.score < 999)
      .sort((a, b) => (a.score - b.score) || a.item.title.localeCompare(b.item.title))
      .slice(0, WHAT_LIMIT)
      .map((row) => row.item)
  let html = ''
  if (!filtered.length) {
    list.innerHTML = ''
    return false
  } else {
    html += `<div class="section-title">${query.length < 2 ? 'Trending modalities' : 'Modalities'}</div><div>`
    filtered.forEach((x) => {
      const m = fuzzyMatch(query, x.title)
      const titleHTML = highlight(x.title, m.ranges)
      const subtitle = String(x.subtitle || (x.counts?.total ? `${x.counts.total} offerings` : 'Modality')).replace(/\bproducts?\b/gi, 'offerings')
      html += `
        <button type="button" class="item" role="option" data-value="${x.title}">
          <i class="bi bi-tag"></i>
          <span class="title">${titleHTML}</span>
          <span class="text-muted ms-2">${subtitle}</span>
        </button>`
    })
    html += `</div>`
  }
  list.innerHTML = html
  return true
}

function bindInteractions() {
  // Global click outside to close panes
  const onDocClick = (e) => {
    if (!root.value) return
    if (!e.target.closest('.seg')) closeAll()
  }
  document.addEventListener('click', onDocClick)
  detachGlobal = () => document.removeEventListener('click', onDocClick)

  // Helper: align dropdown pane adaptively left/right based on viewport space
  function alignPane(pane, seg){
    if (!pane || !seg) return
    pane.classList.remove('align-left','align-right')
    const desired = Math.min(560, Math.floor(window.innerWidth * 0.96))
    const r = seg.getBoundingClientRect()
    const spaceRight = window.innerWidth - r.left
    const useRight = spaceRight < desired
    pane.classList.add(useRight ? 'align-right' : 'align-left')
  }

  // WHEN
  const segWhen = q(`#${id('seg-when')}`)
  const whenPane = q(`#${id('when-pane')}`)
  const whenInput = q(`#${id('when')}`)

  segWhen?.addEventListener('click', (e) => {
    if (desktopNativeWhen.value) {
      whenInput?.showPicker?.()
      whenInput?.focus?.()
      return
    }
    if (e.target.closest('.pane')) return
    closeAll()
    showPane(whenPane)
  })

  whenInput?.addEventListener('change', () => {
    if (!desktopNativeWhen.value) return
    setDesktopWhenValue(whenInput.value)
  })
  whenInput?.addEventListener('input', () => {
    if (!desktopNativeWhen.value) return
    setDesktopWhenValue(whenInput.value)
  })

  qa(`#${id('seg-when')} .dur`).forEach((b) =>
    b.addEventListener('click', () => {
      if (isFlexible.value) return
      setDuration(parseInt(b.dataset.days || '0', 10))
    }),
  )
  q(`#${id('chip-exact')}`)?.addEventListener('click', (e) => {
    if (isFlexible.value) return
    qa(`#${id('seg-when')} .chip`).forEach((c) => c.classList.remove('primary'))
    e.currentTarget.classList.add('primary')
    const api = typeof window !== 'undefined' ? window.__WOWRangeCalendars?.[props.idPrefix] : null
    if (api?.clearSelection) api.clearSelection()
  })

  // WHERE
  const segWhere = q(`#${id('seg-where')}`)
  const whereInput = q(`#${id('where')}`)
  const whereEditor = q(`#${id('where-editor')}`)
  const wherePane = q(`#${id('where-pane')}`)
  const whereList = q(`#${id('where-list')}`)
  segWhere?.addEventListener('click', () => {
    closeAll()
    showPane(wherePane)
    alignPane(wherePane, segWhere)
    whereEditor?.focus()
    updateWhereDropdownVisibility()
  })
  whereEditor?.addEventListener('focus', () => {
    closeAll()
    showPane(wherePane)
    alignPane(wherePane, segWhere)
    updateWhereDropdownVisibility()
  })
  wherePane?.addEventListener('click', (e) => {
    const btn = e.target.closest('.item')
    if (!btn) return
    const val = btn.dataset.value || ''
    addWhereChip(val)
    updateWhereDropdownVisibility()
    closeAll()
    if (whereEditor) whereEditor.blur()
  })
  // Direct typing in editor: leave chips intact, do not change hidden until user selects
  // WHERE typed filter: debounce fetch
  let whereTimer = null
  whereEditor?.addEventListener('input', () => {
    if (!whereList) return
    const qs = (whereEditor.textContent || '').trim()
    clearTimeout(whereTimer)
    whereTimer = setTimeout(async () => {
      try {
        const items = await fetchLocations(12, qs)
        renderWhereList(items)
      } catch {}
    }, 180)
  })
  whereEditor?.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault() })
  // Clicking inside editor should place caret; removal handled by chip-x button

  // WHO
  const whoSeg = q(`#${id('seg-who')}`)
  const whoPane = q(`#${id('who-pane')}`)
  updateWhoSummary()

  whoSeg?.addEventListener('click', (e) => {
    if (e.target.closest('.pane')) return
    closeAll()
    showPane(whoPane)
    alignPane(whoPane, whoSeg)
  })
  whoPane?.addEventListener('click', (e) => {
    const incBtn = e.target.closest('[data-inc]')
    const decBtn = e.target.closest('[data-dec]')
    if (incBtn) {
      const k = incBtn.dataset.inc
      state.who[k] = Math.min(50, state.who[k] + 1)
      if (!groupTypeTouched) {
        state.groupType = autoGroupTypeForAdults(state.who.adults)
        syncGroupSelection()
      }
      updateWhoSummary()
    }
    if (decBtn) {
      const k = decBtn.dataset.dec
      const min = k === 'children' ? 0 : 1
      state.who[k] = Math.max(min, state.who[k] - 1)
      if (!groupTypeTouched) {
        state.groupType = autoGroupTypeForAdults(state.who.adults)
        syncGroupSelection()
      }
      updateWhoSummary()
    }
  })
  // Group type list inside Who panel
  const gtList = q(`#${id('group-type-list')}`)
  if (gtList) {
    // initialize selection
    syncGroupSelection()
    gtList.addEventListener('click', (e) => {
      const btn = e.target.closest('.item')
      if (!btn) return
      const val = btn.dataset.group || 'Solo'
      state.groupType = val
      groupTypeTouched = true
      syncGroupSelection()
      updateWhoSummary()
    })
  }
  q(`#${id('who-done')}`)?.addEventListener('click', () => hidePane(whoPane))

  // GROUP TYPE
  const segGroup = q(`#${id('seg-group')}`)
  const groupPane = q(`#${id('group-pane')}`)
  const groupSummary = q(`#${id('group-summary')}`)
  segGroup?.addEventListener('click', (e) => {
    if (e.target.closest('.pane')) return
    closeAll()
    showPane(groupPane)
  })
  groupPane?.addEventListener('click', (e) => {
    const btn = e.target.closest('.item')
    if (!btn) return
    qa(`#${id('group-pane')} .item`).forEach((x) => x.classList.remove('kicker'))
    btn.classList.add('kicker')
    const val = btn.dataset.value || ''
    if (groupSummary) groupSummary.textContent = val
    closeAll()
  })

  // WHAT
  const segWhat = q(`#${id('seg-what')}`)
  const whatInput = q(`#${id('what')}`)
  const whatPane = q(`#${id('what-pane')}`)
  const whatList = q(`#${id('what-list')}`)

  segWhat?.addEventListener('click', () => {
    closeAll()
    whatInput?.focus()
    if (renderWhat(whatValue.value || whatInput?.value || '')) {
      showPane(whatPane)
    }
  })
  whatInput?.addEventListener('focus', () => {
    if (renderWhat(whatValue.value || whatInput.value)) {
      showPane(whatPane)
    }
    else closeAll()
  })
  whatInput?.addEventListener('input', () => {
    whatValue.value = whatInput.value
    if (renderWhat(whatValue.value)) showPane(whatPane)
    else closeAll()
  })
  whatList?.addEventListener('click', (e) => {
    const btn = e.target.closest('.item')
    if (!btn) return
    if (whatInput) whatInput.value = btn.dataset.value || ''
    whatValue.value = whatInput?.value || ''
    closeAll()
    whatInput?.blur()
  })
}

function openFromHost(segment = 'what') {
  const target = String(segment || 'what').trim() || 'what'
  closeAll()
  if (target === 'what') {
    const whatInputEl = q(`#${id('what')}`)
    const whatPane = q(`#${id('what-pane')}`)
    if (whatInputEl) {
      whatInputEl.focus({ preventScroll: true })
      renderWhat(whatInputEl.value || whatValue.value || '')
      showPane(whatPane)
    }
    return
  }

  if (target === 'where') {
    const whereEditor = q(`#${id('where-editor')}`)
    const wherePane = q(`#${id('where-pane')}`)
    if (whereEditor) {
      whereEditor.focus({ preventScroll: true })
      showPane(wherePane)
    }
    return
  }

  if (target === 'when') {
    const whenInput = q(`#${id('when')}`)
    const whenPane = q(`#${id('when-pane')}`)
    if (whenInput) {
      whenInput.focus({ preventScroll: true })
      showPane(whenPane)
    }
    return
  }

  if (target === 'who') {
    const whoSeg = q(`#${id('seg-who')}`)
    const whoPane = q(`#${id('who-pane')}`)
    if (whoSeg) {
      whoSeg.focus({ preventScroll: true })
      showPane(whoPane)
    }
  }
}

function closeFromHost() {
  closeAll()
}

onMounted(async () => {
  syncDesktopNativeWhen()
  whatCategories = []

  // Load categories to power WHAT suggestions
  try {
    whatCategories = await fetchWhatCategories()
  } catch {}

  bindInteractions()

  if (typeof window !== 'undefined') {
    window.__WOWSearchBarV4 = window.__WOWSearchBarV4 || {}
    window.__WOWSearchBarV4[props.idPrefix] = {
      open: openFromHost,
      close: closeFromHost,
    }
  }

  // Load locations for WHERE list
  try {
    const items = await fetchLocations(5)
    renderWhereList(items)
  } catch {}

  // Prefill from URL query params if present (for Search page persistence)
  try {
    const urlParams = new URLSearchParams(window.location.search || '')
    // WHAT
    const whatVal = urlParams.get('what') || ''
    if (whatVal) {
      const whatInput = q(`#${id('what')}`)
      if (whatInput) whatInput.value = whatVal
      whatValue.value = whatVal
      renderWhat(whatVal)
    }
    // WHERE (comma-separated)
    const whereVal = urlParams.get('where') || ''
    if (whereVal) {
      whereVal.split(',').map(s => s.trim()).filter(Boolean).forEach(addWhereChip)
      collapseWhereChipsDisplay()
    }
    // WHEN (free text)
    const whenVal = urlParams.get('when') || ''
    if (whenVal && desktopNativeWhen.value) {
      const whenInput = q(`#${id('when')}`)
      if (whenInput) {
        if (!syncDesktopWhenFromValue(urlParams.get('when_start') || '')) {
          if (!syncDesktopWhenFromValue(whenVal.split(/\s+-\s+|\s+—\s+/)[0] || whenVal)) {
            whenInput.value = ''
          }
        }
      }
      lastExactWhen = whenVal
    }
    const whenStart = urlParams.get('when_start') || ''
    if (whenStart && desktopNativeWhen.value) {
      const whenInput = q(`#${id('when')}`)
      if (whenInput) {
        setDesktopWhenValue(whenStart)
        whenInput.value = whenStart
      }
    }
    const flexVal = (urlParams.get('flexible') || '').toLowerCase()
    if (flexVal && flexVal !== '0' && flexVal !== 'false') {
      setFlexible(true)
    }
    // WHO counts
    const adults = parseInt(urlParams.get('adults') || '', 10)
    if (!Number.isNaN(adults) && adults >= 1 && adults <= 50) state.who.adults = adults
    // Group type from URL or auto
    const gt = (urlParams.get('group_type') || '').trim()
    if (gt) {
      state.groupType = toTitleCase(gt)
      groupTypeTouched = true
    } else {
      state.groupType = autoGroupTypeForAdults(state.who.adults)
    }
    syncGroupSelection()
    updateWhoSummary()
  } catch {}
})

onBeforeUnmount(() => {
  if (detachGlobal) detachGlobal()
  if (typeof window !== 'undefined' && window.__WOWSearchBarV4) {
    delete window.__WOWSearchBarV4[props.idPrefix]
  }
  if (typeof document !== 'undefined') {
    document.body.classList.remove('wow-search-pane-open')
  }
})
</script>

<template>
  <div class="wow-ultra" :class="{ 'only-what': props.onlyWhat }" ref="root">
    <form class="bar" :class="{ 'bar-compact': compact }" role="search" @submit.prevent="onSubmit">
      <!-- WHAT -->
      <div class="seg" :id="id('seg-what')">
        <i class="bi bi-stars fs-5 text-muted"></i>
        <div class="flex-grow-1">
          <div class="seg-label">What</div>
          <input :id="id('what')" name="what" type="text" autocomplete="off" placeholder="Massage, yoga, breathwork…" role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-expanded="false" :aria-controls="id('what-pane')" required :aria-invalid="!whatFilled">
        </div>
        <!-- Inline Search button when onlyWhat mode -->
        <button v-if="props.onlyWhat" type="submit" class="btn-wow is-squarish btn-xl d-flex align-items-center gap-2" aria-label="Search" :disabled="!whatFilled" :aria-disabled="!whatFilled">
          <span class="btn-label">Search</span>
          <span class="btn-spinner" aria-hidden="true"><span class="spin"></span></span>
        </button>
        <div :id="id('what-pane')" class="pane narrow d-none" role="listbox" aria-label="What suggestions" aria-hidden="true">
          <div :id="id('what-list')" class="listy"></div>
        </div>
      </div>

      <!-- WHERE -->
      <div v-if="!props.onlyWhat" class="seg" :id="id('seg-where')">
        <i class="bi bi-geo-alt fs-5 text-muted"></i>
        <div class="flex-grow-1">
          <div class="seg-label">Where</div>
          <div :id="id('where-editor')" class="where-editor" contenteditable="true" data-placeholder="City, region, or 'Online'"></div>
          <input :id="id('where')" type="hidden">
        </div>
        <div :id="id('where-pane')" class="pane narrow d-none" role="listbox" aria-label="Trending places" aria-hidden="true">
          <div class="section-title">Trending destinations</div>
          <div class="listy" :id="id('where-list')" data-wow-location-list="1"></div>
        </div>
      </div>

      <!-- WHEN -->
      <div v-if="!props.onlyWhat" class="seg" :id="id('seg-when')">
        <i class="bi bi-calendar3 fs-5 text-muted"></i>
        <div class="flex-grow-1">
          <div class="seg-label">When</div>
          <input
            :id="id('when')"
            :type="desktopNativeWhen ? 'date' : 'text'"
            :placeholder="desktopNativeWhen ? '' : 'Select dates'"
            :readonly="!desktopNativeWhen"
            :aria-haspopup="desktopNativeWhen ? undefined : 'dialog'"
          >
        </div>

        <div v-if="!desktopNativeWhen" :id="id('when-pane')" class="pane d-none" aria-label="Calendar" aria-hidden="true">
          <div class="cal-body cal-body--range">
            <SearchRangeCalendar :prefix="props.idPrefix" />
          </div>
        </div>
      </div>

      <!-- WHO -->
      <div v-if="!props.onlyWhat" class="seg" :id="id('seg-who')">
        <i class="bi bi-person fs-5 text-muted"></i>
        <div class="flex-grow-1">
          <div class="seg-label">Who</div>
          <div :id="id('who-summary')" class="summary">2 adults · Solo</div>
        </div>
        <div :id="id('who-pane')" class="pane narrow d-none" aria-label="Guests" aria-hidden="true">
          <div class="section-title">Guests</div>
          <div class="listy">
            <div class="item" style="justify-content: space-between;">
              <div><div class="fw-semibold">Adults</div><small class="text-muted">18+</small></div>
              <div class="counter">
                <button type="button" class="btn btn-counter" data-dec="adults" aria-label="Decrease adults"><i class="bi bi-dash"></i></button>
                <span :id="id('adults-val')" class="fw-semibold">2</span>
                <button type="button" class="btn btn-counter" data-inc="adults" aria-label="Increase adults"><i class="bi bi-plus"></i></button>
              </div>
            </div>
            <div class="section-title">Group type</div>
            <div :id="id('group-type-list')">
              <button type="button" class="item" data-group="Solo" aria-selected="true"><i class="bi bi-person"></i><span class="title">Solo</span></button>
              <button type="button" class="item" data-group="Couple"><i class="bi bi-heart"></i><span class="title">Couple</span></button>
              <button type="button" class="item" data-group="Group"><i class="bi bi-people"></i><span class="title">Group</span></button>
            </div>
          </div>
          <div class="text-end p-3">
            <button type="button" class="btn btn-primary btn-sm" :id="id('who-done')">Done</button>
          </div>
        </div>
      </div>

      <!-- GROUP TYPE -->
      <div v-if="props.showGroupType && !props.onlyWhat" class="seg" :id="id('seg-group')">
        <i class="bi bi-people fs-5 text-muted"></i>
        <div class="flex-grow-1">
          <div class="seg-label">Group type</div>
          <div :id="id('group-summary')" class="summary">Solo</div>
        </div>
        <div :id="id('group-pane')" class="pane narrow d-none" aria-label="Group type" aria-hidden="true">
          <div class="listy">
            <button type="button" class="item kicker" data-value="Solo"><i class="bi bi-person"></i><span class="title">Solo</span></button>
            <button type="button" class="item" data-value="Couple"><i class="bi bi-heart"></i><span class="title">Couple</span></button>
            <button type="button" class="item" data-value="Group"><i class="bi bi-people"></i><span class="title">Group</span></button>
          </div>
        </div>
      </div>

      <button v-if="!props.onlyWhat" class="btn-wow is-squarish btn-xl" :disabled="!whatFilled" :aria-disabled="!whatFilled">
        <span class="btn-label">Search</span>
        <span class="btn-spinner" aria-hidden="true"><span class="spin"></span></span>
      </button>
    </form>
  </div>
</template>

<style>
.wow-ultra{
  --wow-blue:#1737c5;
  --wow-gold:#ffb300;
  --gold:#ffb300;
  --ink:#0f172a;
  --muted:#6b7280;
  --bg:#f6f8fb;
  --ring:0 0 0 3px rgba(23,55,197,.16);
  --shadow:0 14px 40px rgba(10,22,70,.14);
  --radius:14px;
  --soft-border:#e6e9f2;
  --brand-accent:#2c6bed;
  --accent-600:#e3572f;
}
.wow-ultra .seg-label,
.wow-ultra .seg input,
.wow-ultra .where-editor,
.wow-ultra .summary,
.wow-ultra .pane,
.wow-ultra .pane .section-title,
.wow-ultra .listy,
.wow-ultra .item,
.wow-ultra .item .title{ font-family: 'Manrope', var(--bs-font-sans-serif) !important; }

.wow-ultra .bar{
  background:rgba(255,255,255,.94);
  backdrop-filter:blur(22px) saturate(180%);
  -webkit-backdrop-filter:blur(22px) saturate(180%);
  border:1px solid rgba(255,255,255,.74);
  border-radius:18px;
  padding:5px;
  box-shadow:0 16px 46px rgba(16,24,40,.12);
  z-index:2000;
  display:flex;
  gap:5px;
  flex-wrap:wrap;
  transition:background-color 220ms ease, border-color 220ms ease, box-shadow 220ms ease, transform 220ms ease, border-radius 220ms ease;
}
@media (min-width: 992px){
  .wow-ultra .bar{
    border-radius:19px;
    border:1px solid rgba(16,24,40,.08);
  }
}
.wow-ultra .bar-compact{ gap:4px; padding:4px }

.wow-ultra .seg{
  flex:1 1 220px; display:flex; gap:10px; align-items:center;
  background:#fff; border:1px solid rgba(15,23,42,.12); border-radius:var(--radius);
  padding:14px 16px; position:relative; max-height:58px;
  box-shadow:0 1px 0 rgba(255,255,255,.65) inset;
  transition:background 160ms ease, box-shadow 160ms ease, border-color 160ms ease, transform 160ms ease, color 160ms ease;
}
.wow-ultra .seg:focus-within{ box-shadow:var(--ring); border-color:transparent }
.wow-ultra .seg:hover{
  background:#f8fafc;
  transform:translateY(-1px);
  border-color:rgba(15,23,42,.08);
}
.wow-ultra .seg-label{
  font-weight:600;
  color:#111827;
  font-size:11px; /* explicit per request */
  line-height:1;
  margin:0 0 2px 0; /* remove right shift/padding from global .label, tighten spacing above input */
}
.wow-ultra .seg input{
  border:0; outline:0; width:100%; background:transparent;
  font-size:1rem; line-height:1.25; padding:0; margin:0; /* avoid looking pushed down */
  transition:color 160ms ease;
}
.wow-ultra .where-editor{
  outline:0; min-height:1.25rem; font-size:1rem; line-height:1.25;
  transition:color 160ms ease, opacity 160ms ease;
}
.wow-ultra .where-editor:empty:before{
  content: attr(data-placeholder); color:#9ca3af;
}
.wow-ultra .where-editor .where-chip{ gap:0; padding-right:5px; margin-right:5px }
.wow-ultra .where-plus{ margin-left:2px; color:#6b7280; font-size:.9rem }
.wow-ultra .chip-x{
  background: transparent; border:0; color:#6b7280; padding:0; margin-left:2px;
  width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center; border-radius:9999px;
}
.wow-ultra .chip-x:hover{ background:#f3f4f6; color:#111827 }
.wow-ultra .summary{ color:#374151; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.wow-ultra .btn-search:focus{ box-shadow:var(--ring) }
.wow-ultra .btn-wow:disabled{
  opacity:.55;
  cursor:not-allowed;
  filter:saturate(.75);
}
.wow-ultra .btn-wow{
  transition:transform 160ms ease, box-shadow 160ms ease, background-color 160ms ease, border-color 160ms ease, color 160ms ease, opacity 160ms ease;
}
.wow-ultra .btn-wow:hover,
.wow-ultra .btn-wow:focus-visible{
  transform:translateY(-1px);
  box-shadow:0 14px 30px rgba(16,24,40,.18);
}

/* Inline search button now uses .btn-wow classes */
.wow-ultra.only-what .seg{ flex:1 1 100% }
.wow-ultra.only-what .bar{ flex-wrap:nowrap }

.wow-ultra .pane{
  position:absolute; left:0; right:0; top:calc(100% + 10px);
  background:#fff; border:1px solid var(--soft-border); border-radius:16px;
  box-shadow:var(--shadow); z-index:2100; overflow:hidden; text-align:left;
  opacity:0;
  visibility:hidden;
  pointer-events:none;
  transform:translateY(-8px) scale(.985);
  transition:opacity 180ms ease, transform 180ms ease, visibility 0s linear 180ms;
  will-change:opacity, transform;
}
.wow-ultra .pane.is-open{
  opacity:1;
  visibility:visible;
  pointer-events:auto;
  transform:translateY(0) scale(1);
  transition:opacity 180ms ease, transform 180ms ease, visibility 0s linear 0s;
}
.wow-ultra .pane.is-closing{
  opacity:0;
  transform:translateY(-6px) scale(.99);
}
.wow-ultra .pane.narrow{
  z-index:2100;
  left:0!important;
  right:0!important;
  width:min(560px,96vw);
  max-width:96vw;
  height:auto;
  max-height:304px;
  overflow:auto;
  -ms-overflow-style:none;
  scrollbar-width:none;
}
.wow-ultra .pane.narrow::-webkit-scrollbar{ width:0; height:0 }
@media (max-width: 991.98px){
  .wow-ultra .pane.narrow{
    width:auto;
    max-width:none;
  }
}
/* Adaptive alignment helpers */
.wow-ultra .pane.align-left{ left:0 !important; right:auto !important }
.wow-ultra .pane.align-right{ left:auto !important; right:0 !important }

.wow-ultra .pane .section-title{
  font-size:.85rem; font-weight:700; letter-spacing:.01em; color:#111827;
  padding:10px 14px; background:#f9fafb; border-bottom:1px solid #eef2f7;
}
.wow-ultra .listy{ max-height:360px; overflow:auto; padding:6px 0 }
.wow-ultra [data-wow-location-list="1"]{ max-height:none; overflow:hidden; padding:6px 0 }
.wow-ultra .item{ display:flex; align-items:center; gap:10px; padding:12px 14px; text-align:left; background:#fff; border:0; width:100%; transition:background 140ms ease, transform 140ms ease, color 140ms ease }
.wow-ultra .item:hover, .wow-ultra .item[aria-selected="true"]{ background:#f2f5ff; transform:translateX(1px) }
.wow-ultra .item .title{ font-weight:600; color:#0f172a }
.wow-ultra .item .type{ font-size:.75rem; padding:.1rem .5rem; border-radius:999px; background:#eef2ff; color:#2536eb; margin-left:.5rem }
.wow-ultra .hl{ background:linear-gradient(180deg,rgba(255,233,150,.0),rgba(255,233,150,.9)); border-radius:4px }

.wow-ultra .counter{ display:flex; align-items:center; gap:10px }
.wow-ultra .btn-counter{ width:28px; height:28px; padding:0; border-radius:50%; border:1px solid #cfd5e1; background:#fff; color:#111827; display:inline-flex; align-items:center; justify-content:center }
.wow-ultra .btn-counter:hover{ background:#f9fafb }
.wow-ultra .btn-counter .bi{ font-size:14px; line-height:1 }

.wow-ultra [id$='when-pane']{ left:50%; transform:translateX(-50%); right:auto; width:min(700px, 96vw); max-width:min(980px, 96vw); border-radius:18px }
.wow-ultra [id$='who-pane']{ left:auto; right:0; max-width:min(560px, 96vw); border-radius:18px }
@media (max-width: 768px){
  /* On small screens, make WHO pane span the segment width to avoid overflow */
  .wow-ultra [id$='who-pane']{ left:0; right:0; max-width:100%; }
}
.wow-ultra .cal-head{ display:flex; border-bottom:1px solid var(--soft-border); background:#fff }
.wow-ultra .cal-col{ width:50%; text-align:center; padding:18px 22px; font-weight:700; color:#0f172a; position:relative; background:none; border:0; cursor:pointer }
.wow-ultra .cal-col:focus-visible{ outline:2px solid var(--brand-accent); outline-offset:2px }
.wow-ultra .cal-col.active::after{ content:""; position:absolute; left:16px; right:16px; bottom:-1px; height:3px; background:var(--brand-accent); border-radius:3px }
.wow-ultra .cal-body{ padding:12px 16px 0 }
.wow-ultra .flexible-pane{ padding:1rem; text-align:center; border:1px dashed var(--soft-border); border-radius:12px; background:#fafbff; font-size:.95rem }
.wow-ultra .cal-foot{ display:flex; gap:12px; flex-wrap:wrap; padding:14px 16px; border-top:1px solid var(--soft-border); background:#fff; justify-content:center }
.wow-ultra .cal-foot.is-disabled .chip{ opacity:.5; cursor:not-allowed }
.wow-ultra .chip{ border-radius:999px; padding:12px 16px; font-weight:700; background:#fff; border:1px solid #cfd5e1 }
.wow-ultra .chip.primary{ border-color:var(--brand-accent); color:var(--brand-accent); box-shadow:inset 0 0 0 2px rgba(44,107,237,.06) }
.wow-ultra .chip .bi{ margin-right:8px }

.wow-ultra .cal-body .flatpickr-calendar{ border:0; box-shadow:none; margin:0 auto }
.wow-ultra .flatpickr-innerContainer{ padding:0 4px 8px }
.wow-ultra .flatpickr-months{ display:flex; justify-content:center; gap:18px }
.wow-ultra .flatpickr-months .flatpickr-month{ flex:0 1 auto }
.wow-ultra .flatpickr-months .flatpickr-current-month{ width:100% }
.wow-ultra .flatpickr-months .flatpickr-month .flatpickr-next-month,
.wow-ultra .flatpickr-months .flatpickr-month .flatpickr-prev-month{ top:10px }
.wow-ultra .flatpickr-months .flatpickr-month{ color:#0f172a; font-weight:700 }
.wow-ultra .flatpickr-weekdays{ margin-top:6px }
.wow-ultra .flatpickr-day{ border-radius:10px }
.wow-ultra .flatpickr-day.selected,
.wow-ultra .flatpickr-day.startRange,
.wow-ultra .flatpickr-day.endRange{ background:var(--brand-accent); border-color:var(--brand-accent); color:#fff }
.wow-ultra .flatpickr-day.inRange{ background:rgba(44,107,237,.12); border-color:rgba(44,107,237,.12) }

@media (max-width: 992px){
/* Search button layout handled by flex; sizing via .btn-wow sizes */
}

/* Specific tweak for home inline What list */
#home-inline-what-list { padding-top: 0px; }
</style>
