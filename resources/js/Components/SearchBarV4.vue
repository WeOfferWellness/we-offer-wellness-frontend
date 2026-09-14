<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import SearchRangeCalendar from './SearchRangeCalendar.vue'
import { fetchLocations } from '@/services/locations'
import { fetchWhatCategories } from '@/services/whatCategories'
import { logSearchValues } from '@/services/searchAnalytics'

const props = defineProps({
  idPrefix: { type: String, default: 'search-v4' },
  searchUrl: { type: String, default: '/search' },
  resultCount: { type: [Number, String], default: 0 },
  initialQuery: { type: Object, default: () => ({}) },
  mobileTopOffset: { type: [Number, String], default: 12 },
  staticLayout: { type: Boolean, default: false },
  showChrome: { type: Boolean, default: true },
  mobileChrome: { type: Boolean, default: false },
  navigateOnSubmit: { type: Boolean, default: false },
  forceMobileLayout: { type: Boolean, default: false },
  defaultActiveSegment: { type: String, default: '' },
  hideTopRow: { type: Boolean, default: false },
  hideMobileClose: { type: Boolean, default: false },
})

const root = ref(null)
const whatInput = ref(null)
const whereInput = ref(null)
const whenInput = ref(null)

const state = reactive({
  what: '',
  where: '',
  when: '',
  whenStart: '',
  whenEnd: '',
  adults: 0,
  groupType: '',
  sort: 'popular',
  priceMax: '',
  rating: '',
  type: '',
  onlineOnly: false,
  anytime: false,
  viewMode: 'map',
  mapMode: '2d',
})

const activeSegment = ref(null)
const filterDrawerOpen = ref(false)
const filterSections = reactive({
  sort: true,
  price: true,
  type: true,
  rating: false,
  mode: false,
})
const mobileExpanded = ref(false)
const scrollCollapsed = ref(false)
const scrollExpanded = ref(false)
const displayCount = ref(Number(props.resultCount) || 0)
const isSearchPageModal = computed(() => props.idPrefix === 'search-v4')
const whatSuggestions = ref([])
const whereSuggestions = ref([])
const whatCatalog = ref([])
const whatLoaded = ref(false)
const whereLoaded = ref(false)

let queryTimer = null
let whereTimer = null
let ignoreQueryEmit = false
let resultsListener = null
let outsideClickHandler = null
let escapeHandler = null
let popStateHandler = null
let rootResizeObserver = null
let scrollCollapseTimer = null
let bodyOverflowBeforeLock = ''
let bodyLockSnapshot = null
let bodyScrollYBeforeLock = 0
let touchScrollLockHandler = null

function isWithinSearchSurface(target) {
  if (typeof document === 'undefined') return true
  const rootEl = root.value
  if (!rootEl) return false
  const node = target?.nodeType === Node.ELEMENT_NODE ? target : target?.parentElement
  if (node && rootEl.contains(node)) return true
  if (target?.closest && target.closest('.wow-search-filter')) return true
  if (target?.closest && target.closest('.wow-filter-drawer')) return true
  return false
}

function removeTouchScrollLock() {
  if (typeof document === 'undefined' || !touchScrollLockHandler) return
  document.removeEventListener('touchmove', touchScrollLockHandler, { passive: false })
  document.removeEventListener('wheel', touchScrollLockHandler, { passive: false })
  touchScrollLockHandler = null
}

function addTouchScrollLock() {
  if (typeof document === 'undefined' || touchScrollLockHandler) return

  touchScrollLockHandler = (event) => {
    if (isWithinSearchSurface(event.target)) return
    event.preventDefault()
  }

  document.addEventListener('touchmove', touchScrollLockHandler, { passive: false })
  document.addEventListener('wheel', touchScrollLockHandler, { passive: false })
}

const sortLabels = {
  popular: 'Recommended',
  newest: 'Newest',
  price_asc: 'Price: low to high',
  price_desc: 'Price: high to low',
  rating_desc: 'Highest rated',
}

const ratingLabels = {
  reviewed: 'Reviewed only',
  '4.5': '4.5+ stars',
  '4': '4.0+ stars',
}

const typeLabels = {
  therapies: 'Therapy',
  therapy: 'Therapy',
  classes: 'Classes',
  class: 'Classes',
  events: 'Events',
  event: 'Events',
  retreats: 'Retreats',
  retreat: 'Retreats',
  gifts: 'Gifts',
  gift: 'Gifts',
}

const mobileTopOffsetValue = computed(() => {
  const raw = props.mobileTopOffset
  if (raw === null || raw === undefined || raw === '') return '12px'

  if (typeof raw === 'number' && Number.isFinite(raw)) {
    return `${raw}px`
  }

  const text = String(raw).trim()
  if (!text) return '12px'
  return /^\d+$/.test(text) ? `${text}px` : text
})

const rootStyle = computed(() => ({
  '--wow-search-filter-mobile-top': mobileTopOffsetValue.value,
  ...(props.staticLayout && mobileExpanded.value && isMobile() ? {
    position: 'fixed',
    top: mobileTopOffsetValue.value,
    right: '0',
    bottom: 'auto',
    left: '0',
    transform: 'none',
    width: '100vw',
    maxWidth: '100vw',
    margin: '0',
    zIndex: '1950',
  } : props.staticLayout ? {
    position: 'relative',
    top: 'auto',
    right: 'auto',
    bottom: 'auto',
    left: 'auto',
    transform: 'none',
    width: '100%',
    maxWidth: 'none',
    margin: '0',
    zIndex: '10',
  } : {}),
}))

function id(name) {
  return `${props.idPrefix}-${name}`
}

function isMobile() {
  return typeof window !== 'undefined' && window.matchMedia('(max-width: 1040px)').matches
}

function isCompactLayout() {
  return props.forceMobileLayout || isMobile()
}

function normalizeText(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[\u2018\u2019\u201c\u201d]/g, "'")
    .replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim()
}

function toTitleCase(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/(^|\s|[-_/])([a-z])/g, (_, prefix, letter) => `${prefix || ''}${letter.toUpperCase()}`)
}

function parseQuerySource() {
  const raw = props.initialQuery && typeof props.initialQuery === 'object' ? props.initialQuery : {}
  if (Object.keys(raw).length > 0) return raw

  try {
    const params = new URLSearchParams(window.location.search || '')
    const out = {}
    params.forEach((value, key) => {
      out[key] = value
    })
    return out
  } catch (_err) {
    return {}
  }
}

function safeInteger(value, fallback = 1, min = 1, max = 20) {
  const parsed = Number.parseInt(String(value ?? ''), 10)
  if (!Number.isFinite(parsed)) return fallback
  return Math.max(min, Math.min(max, parsed))
}

function formatDate(date) {
  if (!date) return ''
  const parsed = typeof date === 'string' ? new Date(`${date}T00:00:00`) : date
  if (Number.isNaN(parsed.getTime())) return ''
  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(parsed)
}

function formatDateRange(start, end) {
  if (!start) return ''
  if (start && !end) return formatDate(start)
  const a = formatDate(start)
  const b = formatDate(end || start)
  return a && b ? `${a} - ${b}` : a || b
}

function hasMeaningfulWhenValue(value) {
  const text = String(value || '').trim()
  return !!text && text !== 'Select dates'
}

function autoGroupType(adults) {
  if (adults <= 1) return 'Solo'
  if (adults === 2) return 'Couple'
  return 'Group'
}

function groupTypeLabel(value) {
  const raw = String(value || '').trim()
  if (raw) return toTitleCase(raw)
  if (state.adults > 0) return autoGroupType(state.adults)
  return ''
}

function groupTypeToAdults(value) {
  const group = groupTypeLabel(value)
  if (group === 'Couple') return 2
  if (group === 'Group') return 3
  if (group === 'Solo') return 1
  return 0
}

function buildParams() {
  const params = new URLSearchParams()

  if (state.what.trim()) params.set('what', state.what.trim())
  if (state.where.trim()) params.set('where', state.where.trim())
  if (hasMeaningfulWhenValue(state.when)) {
    params.set('when', state.when.trim())
  } else if (state.whenStart) {
    params.set('when', formatDateRange(state.whenStart, state.whenEnd))
  }
  if (state.whenStart) params.set('when_start', state.whenStart)
  if (state.whenEnd) params.set('when_end', state.whenEnd)
  if (state.adults > 0) params.set('adults', String(state.adults))
  if (state.adults > 0 && state.groupType) params.set('group_type', String(state.groupType).trim().toLowerCase())
  if (state.sort && state.sort !== 'popular') params.set('sort', state.sort)
  if (state.priceMax) params.set('price_max', String(state.priceMax))
  if (state.rating) params.set('rating', String(state.rating))
  if (state.type) params.set('type', String(state.type))
  if (state.onlineOnly) params.set('mode', 'online')
  if (state.anytime) params.set('anytime', '1')
  params.set('view', state.viewMode)
  params.set('map_mode', state.mapMode)

  return params
}

function buildUrl() {
  const params = buildParams()
  const qs = params.toString()
  return qs ? `${props.searchUrl}?${qs}` : props.searchUrl
}

function dispatchStateEvent(name, detail) {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new CustomEvent(name, { detail }))
}

function emitQueryChange(reason = 'filter', immediate = false) {
  if (ignoreQueryEmit) return
  if (queryTimer) {
    clearTimeout(queryTimer)
    queryTimer = null
  }

  const run = () => {
    dispatchStateEvent('wow:searchbar-v4:query-change', {
      reason,
      url: buildUrl(),
      params: Object.fromEntries(buildParams().entries()),
      state: { ...state },
    })
  }

  if (immediate) {
    run()
    return
  }

  queryTimer = window.setTimeout(run, 220)
}

function emitLayoutChange(reason = 'layout') {
  if (ignoreQueryEmit) return
  dispatchStateEvent('wow:searchbar-v4:layout-change', {
    reason,
    url: buildUrl(),
    params: Object.fromEntries(buildParams().entries()),
    state: { ...state },
  })
}

function expandMobileSearch() {
  if (props.staticLayout && !isCompactLayout()) return
  if (isCompactLayout()) {
    mobileExpanded.value = true
  }
}

function collapseMobileSearch() {
  if (props.staticLayout && !isCompactLayout()) {
    mobileExpanded.value = false
    closeFilterDrawer()
    return
  }

  if (!isCompactLayout()) return

  whatInput.value?.blur?.()
  whereInput.value?.blur?.()
  whenInput.value?.blur?.()
  closeFilterDrawer()
  mobileExpanded.value = false
  if (typeof document !== 'undefined') {
    document.body.style.overflow = bodyOverflowBeforeLock || ''
    document.documentElement.style.overflow = ''
  }
  bodyOverflowBeforeLock = ''
}

function clearScrollCollapseTimer() {
  if (!scrollCollapseTimer) return
  clearTimeout(scrollCollapseTimer)
  scrollCollapseTimer = null
}

function scheduleScrollCollapse() {
  if (props.staticLayout) return
  clearScrollCollapseTimer()

  if (typeof window === 'undefined') return

  scrollCollapseTimer = window.setTimeout(() => {
    scrollCollapseTimer = null

    if (isCompactLayout() || !scrollCollapsed.value || filterDrawerOpen.value) return
    if (root.value?.matches(':hover') || root.value?.matches(':focus-within')) return

    scrollExpanded.value = false
    closeSegments()
  }, 180)
}

function updateScrollCollapsedSearch() {
  if (props.staticLayout) {
    scrollCollapsed.value = false
    scrollExpanded.value = false
    return
  }

  if (isCompactLayout()) {
    scrollCollapsed.value = false
    scrollExpanded.value = false
    return
  }

  const shouldCollapse = typeof window !== 'undefined' && window.scrollY > 8
  scrollCollapsed.value = shouldCollapse

  if (!shouldCollapse) {
    clearScrollCollapseTimer()
    scrollExpanded.value = false
    return
  }

  scrollExpanded.value = false

  if (!filterDrawerOpen.value) {
    closeSegments()
  }

  syncActivePanelPosition()
}

function syncActivePanelPosition() {
  if (typeof window === 'undefined' || !root.value || !activeSegment.value || isCompactLayout()) {
    activePanelStyle.value = {}
    return
  }

  const card = root.value.querySelector('.wow-search-card')
  const segment = root.value.querySelector(`[data-search-segment="${activeSegment.value}"]`)

  if (!card || !segment) {
    activePanelStyle.value = {}
    return
  }

  const cardRect = card.getBoundingClientRect()
  const segmentRect = segment.getBoundingClientRect()
  const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0
  const width = activeSegment.value === 'when'
    ? Math.min(900, Math.max(320, viewportWidth - 32))
    : activeSegment.value === 'who'
      ? Math.min(560, Math.max(320, viewportWidth - 32))
      : Math.min(420, Math.max(320, viewportWidth - 32))
  const desiredLeft = Math.max(0, Math.round(segmentRect.left - cardRect.left))
  const maxLeft = Math.max(0, Math.round(cardRect.width - width))

  const style = {
    width: `${width}px`,
    transform: 'none',
    left: `${Math.min(desiredLeft, maxLeft)}px`,
    right: 'auto',
  }

  activePanelStyle.value = style
}

function ensureScrollExpanded() {
  if (props.staticLayout) return
  if (isCompactLayout() || filterDrawerOpen.value) return
  scrollExpanded.value = true
}

function handleRootMouseEnter() {
  clearScrollCollapseTimer()
  ensureScrollExpanded()
}

function handleRootMouseLeave() {
  if (isCompactLayout() || !scrollCollapsed.value || filterDrawerOpen.value) return
  if (root.value?.matches(':focus-within')) return
  scheduleScrollCollapse()
}

function handleRootFocusIn() {
  clearScrollCollapseTimer()
  ensureScrollExpanded()
}

function handleRootFocusOut(event) {
  if (isCompactLayout() || !scrollCollapsed.value || filterDrawerOpen.value) return
  if (activeSegment.value === 'when') return

  const related = event?.relatedTarget
  if (related && root.value?.contains(related)) return
  if (root.value?.matches(':hover') || root.value?.matches(':focus-within')) return

  scheduleScrollCollapse()
}

function closeMobileFilterToExpandedSearch() {
  if (props.staticLayout && !isCompactLayout()) {
    closeFilterDrawer()
    return
  }

  if (!isCompactLayout()) {
    closeFilterDrawer()
    return
  }

  collapseMobileSearch()
}

function syncBodyScrollLock() {
  if (typeof document === 'undefined') return
  if (props.forceMobileLayout && props.staticLayout) return
  const shouldLock = filterDrawerOpen.value
    || mobileExpanded.value
    || (props.staticLayout && isMobile() && !!activeSegment.value)

  const shouldUseTouchLock = props.staticLayout && isMobile() && !filterDrawerOpen.value

  if (shouldLock) {
    if (!bodyLockSnapshot) {
      bodyLockSnapshot = {
        body: {
          overflow: document.body.style.overflow || '',
          position: document.body.style.position || '',
          top: document.body.style.top || '',
          left: document.body.style.left || '',
          right: document.body.style.right || '',
          width: document.body.style.width || '',
        },
        html: {
          overflow: document.documentElement.style.overflow || '',
        },
      }
    }

    if (shouldUseTouchLock) {
      document.body.style.position = bodyLockSnapshot.body.position
      document.body.style.top = bodyLockSnapshot.body.top
      document.body.style.left = bodyLockSnapshot.body.left
      document.body.style.right = bodyLockSnapshot.body.right
      document.body.style.width = bodyLockSnapshot.body.width
      document.body.style.overflow = 'hidden'
      document.documentElement.style.overflow = 'hidden'
      addTouchScrollLock()
      return
    }

    removeTouchScrollLock()
    bodyScrollYBeforeLock = typeof window !== 'undefined'
      ? (window.scrollY || window.pageYOffset || 0)
      : 0
    document.body.style.position = 'fixed'
    document.body.style.top = `-${bodyScrollYBeforeLock}px`
    document.body.style.left = '0'
    document.body.style.right = '0'
    document.body.style.width = '100%'
    document.body.style.overflow = 'hidden'
    document.documentElement.style.overflow = 'hidden'
    return
  }

  removeTouchScrollLock()
  if (bodyLockSnapshot) {
    document.body.style.overflow = bodyLockSnapshot.body.overflow
    document.body.style.position = bodyLockSnapshot.body.position
    document.body.style.top = bodyLockSnapshot.body.top
    document.body.style.left = bodyLockSnapshot.body.left
    document.body.style.right = bodyLockSnapshot.body.right
    document.body.style.width = bodyLockSnapshot.body.width
    document.documentElement.style.overflow = bodyLockSnapshot.html.overflow
    if (bodyLockSnapshot.body.position === 'fixed' && typeof window !== 'undefined') {
      window.scrollTo(0, bodyScrollYBeforeLock)
    }
  } else {
    document.body.style.overflow = ''
    document.documentElement.style.overflow = ''
  }

  bodyLockSnapshot = null
  bodyScrollYBeforeLock = 0
}

function resetFilterSections() {
  filterSections.sort = true
  filterSections.price = true
  filterSections.type = true
  filterSections.rating = false
  filterSections.mode = false
}

function openSegment(name) {
  ensureScrollExpanded()
  expandMobileSearch()

  if (isCompactLayout() && filterDrawerOpen.value) {
    closeFilterDrawer()
  }

  activeSegment.value = name
  syncBodyScrollLock()
  nextTick(syncActivePanelPosition)

  if (activeSegment.value === 'what') {
    refreshWhatSuggestions(state.what)
  }

  if (activeSegment.value === 'where') {
    refreshWhereSuggestions(state.where)
  }
}

function closeSegments() {
  activeSegment.value = null
  activePanelStyle.value = {}
  syncBodyScrollLock()
}

function toggleFilterDrawer() {
  if (!props.showChrome && !props.mobileChrome) return
  if (filterDrawerOpen.value) {
    closeFilterDrawer()
    return
  }

  expandMobileSearch()
  closeSegments()
  filterDrawerOpen.value = true
  resetFilterSections()
  syncBodyScrollLock()
}

function closeFilterDrawer() {
  if (!props.showChrome && !props.mobileChrome) {
    filterDrawerOpen.value = false
    syncBodyScrollLock()
    return
  }
  closeSegments()
  filterDrawerOpen.value = false
  syncBodyScrollLock()
}

function openFromHost(segment = null) {
  if (!props.forceMobileLayout && !props.staticLayout) return
  mobileExpanded.value = true
  activeSegment.value = null
  openSegment(segment || props.defaultActiveSegment || 'what')
  nextTick(() => {
    if ((segment || props.defaultActiveSegment || 'what') === 'what') {
      whatInput.value?.focus?.()
    }
  })
}

function closeFromHost() {
  if (!props.forceMobileLayout) return
  closeSegments()
  filterDrawerOpen.value = false
  mobileExpanded.value = false
  activePanelStyle.value = {}
}

watch([mobileExpanded, filterDrawerOpen], syncBodyScrollLock, { immediate: true })
watch(activeSegment, syncBodyScrollLock, { immediate: true })

async function toggleFilterSection(section) {
  const isOpening = !filterSections[section]

  if (isCompactLayout() && isOpening) {
    Object.keys(filterSections).forEach((key) => {
      filterSections[key] = key === section
    })
  } else {
    filterSections[section] = !filterSections[section]
  }

  if (!isCompactLayout() || !isOpening) return

  await nextTick()
  const panel = root.value?.querySelector?.(`[data-filter-panel="${section}"]`)
  panel?.scrollIntoView?.({ behavior: 'smooth', block: 'start', inline: 'nearest' })
}

function setViewMode(mode) {
  const next = mode === 'list' ? 'list' : 'map'
  if (state.viewMode === next) return
  state.viewMode = next
  emitLayoutChange('view')
}

function setMapMode(mode) {
  const next = mode === '3d' ? '3d' : '2d'
  if (state.mapMode === next) return
  state.mapMode = next
  emitLayoutChange('map_mode')
}

function applySearch(immediate = true) {
  clearScrollCollapseTimer()
  if (!state.what.trim()) {
    whatInput.value?.focus?.()
    return
  }

  if (isCompactLayout() && !props.staticLayout) {
    collapseMobileSearch()
  } else {
    closeSegments()
    closeFilterDrawer()
  }
  if (props.navigateOnSubmit && typeof window !== 'undefined') {
    logSearchValues({
      searchTerm: state.what,
      locationQuery: state.where,
      source: props.idPrefix === 'header-search' ? 'header-modal' : 'search-page',
    })
    window.location.assign(buildUrl())
    return
  }
  logSearchValues({
    searchTerm: state.what,
    locationQuery: state.where,
    source: props.idPrefix === 'header-search' ? 'header-modal' : 'search-page',
  })
  emitQueryChange('submit', immediate)
}

function setWhatValue(value, immediate = false) {
  state.what = String(value || '')
  if (immediate) emitQueryChange('what', true)
  else emitQueryChange('what')
}

function setWhereValue(value, immediate = false) {
  state.where = String(value || '')
  if (immediate) emitQueryChange('where', true)
  else emitQueryChange('where')
}

function setWhenValue(value, start = '', end = '', immediate = false) {
  const nextValue = String(value || '').trim()
  state.when = hasMeaningfulWhenValue(nextValue) ? nextValue : ''
  state.whenStart = String(start || '')
  state.whenEnd = String(end || '')
  if (immediate) emitQueryChange('when', true)
  else emitQueryChange('when')
}

function syncFromQuery() {
  const query = parseQuerySource()
  ignoreQueryEmit = true

  closeFilterDrawer()
  mobileExpanded.value = false
  state.what = String(query.what || '').trim()
  state.where = String(query.where || '').trim()
  state.when = String(query.when || '').trim()
  if (state.when === 'Select dates') state.when = ''
  state.whenStart = String(query.when_start || '').trim()
  state.whenEnd = String(query.when_end || '').trim()
  const parsedAdults = safeInteger(query.adults, 0, 0, 20)
  const parsedGroupType = groupTypeLabel(query.group_type || '')

  if (parsedGroupType) {
    state.groupType = parsedGroupType
    state.adults = parsedAdults > 0 ? parsedAdults : groupTypeToAdults(parsedGroupType)
  } else if (parsedAdults > 0) {
    state.adults = parsedAdults
    state.groupType = autoGroupType(parsedAdults)
  } else {
    state.adults = 0
    state.groupType = ''
  }
  state.sort = String(query.sort || 'popular').trim() || 'popular'
  state.priceMax = String(query.price_max || '').trim()
  state.rating = String(query.rating || '').trim()
  state.type = String(query.type || '').trim()
  state.onlineOnly = String(query.mode || '').toLowerCase() === 'online'
  state.anytime = ['1', 'true', 'yes', 'on'].includes(String(query.anytime || '').toLowerCase())
  state.viewMode = String(query.view || 'map').toLowerCase() === 'list' ? 'list' : 'map'
  state.mapMode = String(query.map_mode || '2d').toLowerCase() === '3d' ? '3d' : '2d'

  displayCount.value = Number(props.resultCount) || 0
  ignoreQueryEmit = false
}

function refreshWhatSuggestions(query) {
  const needle = normalizeText(query)
  const source = Array.isArray(whatCatalog.value) ? whatCatalog.value : []

  if (!needle) {
    whatSuggestions.value = source.slice(0, 3)
    return
  }

  whatSuggestions.value = source
    .map((item) => {
      const hay = normalizeText([item.title, item.subtitle, item.search, item.slug].filter(Boolean).join(' '))
      const title = normalizeText(item.title)
      let score = 999

      if (title === needle) score = 0
      else if (title.startsWith(needle)) score = 1
      else if (title.includes(needle)) score = 2
      else if (hay.includes(needle)) score = 3
      else {
        const tokens = needle.split(/\s+/).filter(Boolean)
        if (tokens.length && tokens.every((token) => hay.includes(token))) score = 4
      }

      return { item, score }
    })
    .filter((row) => row.score < 999)
    .sort((left, right) => (left.score - right.score) || String(left.item.title || '').localeCompare(String(right.item.title || '')))
    .slice(0, 3)
    .map((row) => row.item)
}

let whatLoadPromise = null

function loadWhatSuggestions() {
  if (whatLoadPromise) return whatLoadPromise
  whatLoadPromise = fetchWhatCategories()
    .then((items) => {
      whatCatalog.value = items || []
      whatLoaded.value = true
      refreshWhatSuggestions(state.what)
      return whatCatalog.value
    })
    .catch((error) => {
      console.warn('[searchbar-v4] what suggestions failed', error)
      whatCatalog.value = []
      whatLoaded.value = true
      refreshWhatSuggestions(state.what)
      return []
    })
  return whatLoadPromise
}

function refreshWhereSuggestions(query) {
  if (whereTimer) {
    clearTimeout(whereTimer)
    whereTimer = null
  }

  const limit = isMobile() ? 3 : 5

  whereTimer = window.setTimeout(async () => {
    try {
      whereSuggestions.value = (await fetchLocations(limit, query)).slice(0, limit)
      whereLoaded.value = true
    } catch (error) {
      console.warn('[searchbar-v4] where suggestions failed', error)
      whereSuggestions.value = []
      whereLoaded.value = true
    }
  }, 180)
}

function onWhatInput(event) {
  setWhatValue(event.target.value)
  if (activeSegment.value === 'what') {
    refreshWhatSuggestions(state.what)
  }
}

function onWhereInput(event) {
  setWhereValue(event.target.value)
  if (activeSegment.value === 'where') {
    refreshWhereSuggestions(state.where)
  }
}

function onWhenInput(event) {
  const target = event.target
  const value = target?.value || ''
  const start = target?.dataset?.rangeStart || ''
  const end = target?.dataset?.rangeEnd || ''
  setWhenValue(value, start, end)
}

function selectWhat(item) {
  if (!item) return
  setWhatValue(item.value || item.title || '', true)
  closeSegments()
  whatInput.value?.blur?.()
}

function selectWhere(item) {
  if (!item) return
  setWhereValue(item.value || item.title || '', true)
  closeSegments()
  whereInput.value?.blur?.()
}

function whereIconMeta(item) {
  const title = normalizeText(item?.title || item?.label || item?.value || '')
  const online = !!item?.online || item?.icon === 'wifi' || title === 'online'

  if (online) {
    return {
      icon: 'bi bi-wifi',
      bg: '#effaf6',
      color: '#1f9d68',
    }
  }

  if (title.includes('nearby')) {
    return {
      icon: 'bi bi-geo-alt',
      bg: '#eef5ff',
      color: '#3b82c4',
    }
  }

  if (title.includes('london')) {
    return {
      icon: 'bi bi-building',
      bg: '#f5f1ec',
      color: '#8a6f50',
    }
  }

  if (title.includes('kent')) {
    return {
      icon: 'bi bi-tree',
      bg: '#eefaf1',
      color: '#159447',
    }
  }

  if (title.includes('manchester')) {
    return {
      icon: 'bi bi-buildings',
      bg: '#fff4e8',
      color: '#ef8a2f',
    }
  }

  if (title.includes('bristol')) {
    return {
      icon: 'bi bi-compass',
      bg: '#fff0f4',
      color: '#ef4778',
    }
  }

  if (title.includes('bath')) {
    return {
      icon: 'bi bi-droplet',
      bg: '#fff1f5',
      color: '#ff4f7d',
    }
  }

  return {
    icon: 'bi bi-geo-alt',
    bg: '#eef5ff',
    color: '#549483',
  }
}

function applyDuration(days) {
  const api = typeof window !== 'undefined' ? window.__WOWRangeCalendars?.[props.idPrefix] : null
  if (api?.setDuration) {
    api.setDuration(days)
  }
}

function clearDate() {
  const api = typeof window !== 'undefined' ? window.__WOWRangeCalendars?.[props.idPrefix] : null
  if (api?.clearSelection) {
    api.clearSelection()
  }
}

function guestsLabel() {
  if (state.adults <= 0) return 'Any guests'
  return `${state.adults} ${state.adults === 1 ? 'guest' : 'guests'}`
}

function setAdults(value) {
  const next = safeInteger(value, 0, 0, 20)
  state.adults = next
  state.groupType = next > 0 ? autoGroupType(next) : ''
  emitQueryChange('guests')
}

function setGroupType(value) {
  state.groupType = groupTypeLabel(value)
  state.adults = groupTypeToAdults(state.groupType)
  emitQueryChange('guests')
}

function setSort(value) {
  state.sort = String(value || 'popular')
  emitQueryChange('sort', true)
}

function setPriceMax(value) {
  state.priceMax = String(value || '').trim()
  emitQueryChange('price_max')
}

function setRating(value) {
  state.rating = String(value || '').trim()
  emitQueryChange('rating', true)
}

function setType(value) {
  state.type = String(value || '').trim()
  emitQueryChange('type', true)
}

function setOnlineOnly(next) {
  state.onlineOnly = Boolean(next)
  emitQueryChange('mode', true)
}

function setAnytime(next) {
  state.anytime = Boolean(next)
  emitQueryChange('anytime', true)
}

function clearFilterPanel(key) {
  if (key === 'sort') {
    state.sort = 'popular'
  } else if (key === 'price') {
    state.priceMax = ''
  } else if (key === 'rating') {
    state.rating = ''
  } else if (key === 'more') {
    state.type = ''
    state.onlineOnly = false
    state.anytime = false
  }

  emitQueryChange(key, true)
}

function clearAllFilters() {
  state.sort = 'popular'
  state.priceMax = ''
  state.rating = ''
  state.type = ''
  state.onlineOnly = false
  state.anytime = false
  state.adults = 0
  state.groupType = ''
  emitQueryChange('clear_all', true)
}

function removeChip(key) {
  if (key === 'what') {
    setWhatValue('', true)
  } else if (key === 'where') {
    setWhereValue('', true)
  } else if (key === 'when') {
    clearDate()
  } else if (key === 'guests') {
    state.adults = 0
    state.groupType = ''
    emitQueryChange('guests', true)
  } else if (key === 'sort') {
    state.sort = 'popular'
    emitQueryChange('sort', true)
  } else if (key === 'price') {
    state.priceMax = ''
    emitQueryChange('price_max', true)
  } else if (key === 'rating') {
    state.rating = ''
    emitQueryChange('rating', true)
  } else if (key === 'type') {
    state.type = ''
    emitQueryChange('type', true)
  } else if (key === 'online') {
    state.onlineOnly = false
    emitQueryChange('mode', true)
  } else if (key === 'anytime') {
    state.anytime = false
    emitQueryChange('anytime', true)
  }
}

function closePanelsOnOutsideClick(event) {
  if (!root.value) return

  const target = event?.target
  const path = typeof event?.composedPath === 'function' ? event.composedPath() : []
  const pathContainsRoot = path.length > 0 && path.includes(root.value)
  const targetIsInsideSearchUi = (() => {
    if (!target || typeof target !== 'object') return false
    if (typeof target.closest === 'function') {
      return !!target.closest(
        '.wow-search-filter, .wow-search-card, .wow-search-main, .wow-search-top-row, .wow-panel, .wow-range-calendar, .wow-range-calendar__toolbar, .wow-range-calendar__months, .wow-range-calendar__grid, .wow-range-calendar__cell, .wow-range-calendar__nav, [data-search-segment], [data-filter-drawer]'
      )
    }

    return path.some((node) => {
      if (!node || typeof node !== 'object' || !node.classList) return false
      return node.classList.contains('wow-search-filter')
        || node.classList.contains('wow-search-card')
        || node.classList.contains('wow-search-main')
        || node.classList.contains('wow-search-top-row')
        || node.classList.contains('wow-panel')
        || node.classList.contains('wow-range-calendar')
        || node.classList.contains('wow-range-calendar__toolbar')
        || node.classList.contains('wow-range-calendar__months')
        || node.classList.contains('wow-range-calendar__grid')
        || node.classList.contains('wow-range-calendar__cell')
        || node.classList.contains('wow-range-calendar__nav')
    })
  })()

  if (target && typeof target.closest === 'function') {
    if (target.closest('[data-filter-drawer]')) return
    if (target.closest('.wow-filter-backdrop')) return
  }

  if (pathContainsRoot || root.value.contains(event.target) || targetIsInsideSearchUi) return

  if (activeSegment.value === 'when') {
    const insideCalendar = path.some((node) => {
      if (!node || typeof node !== 'object') return false
      if (node === root.value) return true
      if (typeof node.classList === 'undefined') return false
      return node.classList.contains('wow-panel--calendar')
        || node.classList.contains('wow-range-calendar')
        || node.classList.contains('wow-range-calendar__cell')
        || node.classList.contains('wow-range-calendar__nav')
        || node.classList.contains('wow-range-calendar__grid')
        || node.classList.contains('wow-range-calendar__month')
        || node.classList.contains('wow-range-calendar__months')
    })

    if (insideCalendar) return
  }

  if (isCompactLayout()) {
    if (filterDrawerOpen.value) {
      closeMobileFilterToExpandedSearch()
    } else {
      collapseMobileSearch()
    }
    return
  }

  closeSegments()
  closeFilterDrawer()
}

function handleResultsUpdated(event) {
  const detail = event?.detail || {}
  if (typeof detail.count === 'number') {
    displayCount.value = detail.count
  } else if (typeof detail.countText === 'string') {
    const match = detail.countText.match(/^(\d+)/)
    if (match) {
      displayCount.value = Number(match[1]) || displayCount.value
    }
  }
}

const activeChips = computed(() => {
  const chips = []
  if (state.what.trim()) chips.push({ key: 'what', label: 'What', value: state.what.trim() })
  if (state.where.trim()) chips.push({ key: 'where', label: 'Where', value: state.where.trim() })
  if (hasMeaningfulWhenValue(state.when) || state.whenStart) {
    chips.push({
      key: 'when',
      label: 'When',
      value: hasMeaningfulWhenValue(state.when) ? state.when.trim() : formatDateRange(state.whenStart, state.whenEnd),
    })
  }
  if (state.adults > 0 || state.groupType) chips.push({ key: 'guests', label: 'Who', value: state.adults > 0 ? `${guestsLabel()} · ${groupTypeLabel(state.groupType)}` : guestsLabel() })
  if (state.sort && state.sort !== 'popular') chips.push({ key: 'sort', label: 'Sort', value: sortLabels[state.sort] || state.sort })
  if (state.priceMax) chips.push({ key: 'price', label: 'Price', value: `Up to £${state.priceMax}` })
  if (state.rating) chips.push({ key: 'rating', label: 'Rating', value: ratingLabels[state.rating] || state.rating })
  if (state.type) chips.push({ key: 'type', label: 'Format', value: typeLabels[state.type] || toTitleCase(state.type) })
  if (state.onlineOnly) chips.push({ key: 'online', label: 'Mode', value: 'Online only' })
  if (state.anytime) chips.push({ key: 'anytime', label: 'Timing', value: 'Anytime' })
  return chips
})

const filterBadgeCount = computed(() => activeChips.value.length)

const whenLabel = computed(() => {
  if (hasMeaningfulWhenValue(state.when)) return state.when.trim()
  if (state.whenStart) return formatDateRange(state.whenStart, state.whenEnd)
  return 'Select dates'
})

const guestsSummary = computed(() => {
  if (state.adults <= 0 && !state.groupType) return 'Any guests'
  const label = guestsLabel()
  const group = groupTypeLabel(state.groupType) || autoGroupType(state.adults)
  return `${label} · ${group}`
})

const searchBackdropOpen = computed(() => (
  (props.showChrome || props.mobileChrome)
  && isMobile()
  && !filterDrawerOpen.value
  && (mobileExpanded.value || (!!props.staticLayout && !!activeSegment.value))
))
const activePanelStyle = ref({})

const sortSummary = computed(() => sortLabels[state.sort] || 'Recommended')
const priceSummary = computed(() => (state.priceMax ? `Up to £${state.priceMax}` : 'Any price'))
const ratingSummary = computed(() => ratingLabels[state.rating] || 'Any rating')
const typeSummary = computed(() => (state.type ? (typeLabels[state.type] || toTitleCase(state.type)) : 'Any format'))

onMounted(async () => {
  syncFromQuery()
  await loadWhatSuggestions()
  refreshWhereSuggestions(state.where)
  updateScrollCollapsedSearch()

  resultsListener = (event) => handleResultsUpdated(event)
  outsideClickHandler = (event) => closePanelsOnOutsideClick(event)
  escapeHandler = (event) => {
    if (event.key === 'Escape') {
      if (props.forceMobileLayout && props.staticLayout) {
        closeSegments()
        closeFilterDrawer()
        return
      }
      if (isCompactLayout()) {
        if (filterDrawerOpen.value) {
          closeMobileFilterToExpandedSearch()
          return
        }

        collapseMobileSearch()
        return
      }

      closeSegments()
      closeFilterDrawer()
    }
  }

  if (typeof window !== 'undefined') {
    window.addEventListener('wow:searchbar-v4:results-updated', resultsListener)
    document.addEventListener('click', outsideClickHandler)
    document.addEventListener('keydown', escapeHandler)
    if (!props.staticLayout) {
      window.addEventListener('scroll', updateScrollCollapsedSearch, { passive: true })
      window.addEventListener('resize', updateScrollCollapsedSearch)
    }
    popStateHandler = () => {
      syncFromQuery()
      loadWhatSuggestions()
      refreshWhereSuggestions(state.where)
      if (!props.staticLayout) {
        updateScrollCollapsedSearch()
      }
    }
    window.addEventListener('popstate', popStateHandler)
  }

  if (typeof window !== 'undefined') {
    window.__WOWSearchBarV4 = window.__WOWSearchBarV4 || {}
    window.__WOWSearchBarV4[props.idPrefix] = {
      open: openFromHost,
      close: closeFromHost,
    }
  }

})

onBeforeUnmount(() => {
  if (queryTimer) clearTimeout(queryTimer)
  if (whereTimer) clearTimeout(whereTimer)

  if (typeof window !== 'undefined' && resultsListener) {
    window.removeEventListener('wow:searchbar-v4:results-updated', resultsListener)
  }

  if (typeof window !== 'undefined' && popStateHandler) {
    window.removeEventListener('popstate', popStateHandler)
  }

  if (typeof window !== 'undefined') {
    window.removeEventListener('scroll', updateScrollCollapsedSearch)
    window.removeEventListener('resize', updateScrollCollapsedSearch)
  }

  if (outsideClickHandler) {
    document.removeEventListener('click', outsideClickHandler)
  }

  if (escapeHandler) {
    document.removeEventListener('keydown', escapeHandler)
  }

  if (rootResizeObserver) {
    rootResizeObserver.disconnect()
    rootResizeObserver = null
  }

  if (typeof window !== 'undefined' && window.__WOWSearchBarV4) {
    delete window.__WOWSearchBarV4[props.idPrefix]
  }

  if (typeof document !== 'undefined') {
    if (bodyLockSnapshot) {
      document.body.style.overflow = bodyLockSnapshot.body.overflow
      document.body.style.position = bodyLockSnapshot.body.position
      document.body.style.top = bodyLockSnapshot.body.top
      document.body.style.left = bodyLockSnapshot.body.left
      document.body.style.right = bodyLockSnapshot.body.right
      document.body.style.width = bodyLockSnapshot.body.width
      document.documentElement.style.overflow = bodyLockSnapshot.html.overflow
    } else {
      document.body.style.overflow = bodyOverflowBeforeLock
    }
  }
  bodyOverflowBeforeLock = ''
  bodyLockSnapshot = null
  bodyScrollYBeforeLock = 0
  removeTouchScrollLock()

  clearScrollCollapseTimer()
})
</script>

<template>
  <div class="wow-search-filter-shell">
    <section
      ref="root"
      class="wow-search-filter"
      :style="rootStyle"
      :class="{
        'is-static-layout': staticLayout,
        'is-mobile-expanded': mobileExpanded,
        'is-force-mobile-layout': forceMobileLayout,
        'is-scroll-collapsed': scrollCollapsed,
        'is-scroll-expanded': scrollExpanded,
        'is-filter-open': filterDrawerOpen,
        'is-filter-drawer-open': filterDrawerOpen,
        'is-panel-open': !!activeSegment || filterDrawerOpen,
      }"
      @mouseenter="handleRootMouseEnter"
      @mouseleave="handleRootMouseLeave"
      @focusin="handleRootFocusIn"
      @focusout="handleRootFocusOut"
    >
    <div v-if="!hideTopRow && (showChrome || mobileExpanded)" class="wow-search-top-row" aria-label="Search tools">
      <button
        v-if="mobileExpanded && !hideMobileClose"
        class="wow-mobile-search-close"
        type="button"
        @click="collapseMobileSearch"
        aria-label="Close search"
      >
        <i class="bi bi-x-lg" aria-hidden="true"></i>
      </button>

      <button
      v-if="showChrome"
      class="wow-filter-icon-btn"
        type="button"
        :class="{ 'is-open': filterDrawerOpen }"
        @click="toggleFilterDrawer"
        :aria-expanded="filterDrawerOpen ? 'true' : 'false'"
        aria-label="Open filters"
      >
        <i class="bi bi-sliders" aria-hidden="true"></i>
        <span class="wow-filter-badge">{{ filterBadgeCount }}</span>
      </button>

      <div v-if="showChrome" class="wow-desktop-map-controls" aria-label="Desktop view and map mode controls">
        <div class="wow-segmented-control" data-control-group="view">
          <span class="wow-control-label">View</span>
          <span class="wow-control-pill">
            <button type="button" :class="{ 'is-active': state.viewMode === 'map' }" :aria-pressed="state.viewMode === 'map' ? 'true' : 'false'" @click="setViewMode('map')">Map</button>
            <button type="button" :class="{ 'is-active': state.viewMode === 'list' }" :aria-pressed="state.viewMode === 'list' ? 'true' : 'false'" @click="setViewMode('list')">List</button>
          </span>
        </div>

        <div class="wow-segmented-control" data-control-group="mode">
          <span class="wow-control-label">Mode</span>
          <span class="wow-control-pill">
            <button type="button" :class="{ 'is-active': state.mapMode === '2d' }" :aria-pressed="state.mapMode === '2d' ? 'true' : 'false'" @click="setMapMode('2d')">2D</button>
            <button type="button" :class="{ 'is-active': state.mapMode === '3d' }" :aria-pressed="state.mapMode === '3d' ? 'true' : 'false'" @click="setMapMode('3d')">3D</button>
          </span>
        </div>
      </div>
    </div>

    <form class="wow-search-card" :action="searchUrl" role="search" @submit.prevent="applySearch(true)">
      <div class="wow-search-main">
        <div class="wow-segment wow-segment--what" data-search-segment="what" @click="openSegment('what')">
          <span class="wow-icon" aria-hidden="true">
            <i class="bi bi-stars"></i>
          </span>
          <span class="wow-copy">
            <span class="wow-label">What</span>
            <input
              :id="id('what')"
              ref="whatInput"
              :value="state.what"
              type="search"
              name="what"
              autocomplete="off"
              placeholder="Massage, yoga, breathwork..."
              required
              :aria-expanded="activeSegment === 'what' ? 'true' : 'false'"
              :aria-controls="id('what-pane')"
              @focus="openSegment('what')"
              @input="onWhatInput"
            >
            <button
              v-if="state.what"
              type="button"
              class="wow-inline-clear"
              aria-label="Clear What"
              @mousedown.stop.prevent
              @click.stop.prevent="setWhatValue('', true)"
            >
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
          </span>
        </div>

        <div
          v-show="activeSegment === 'what'"
          :id="id('what-pane')"
          class="wow-panel wow-panel--what"
          :style="activePanelStyle"
          :class="{ 'is-open': activeSegment === 'what' }"
          role="listbox"
          aria-label="What suggestions"
          @pointerdown.stop
          @mousedown.stop
          @click.stop
        >
          <div class="wow-panel-inner">
            <div class="wow-panel-head">
              <strong>What are you looking for?</strong>
              <span>Pick a popular search or type your own.</span>
            </div>
            <div class="wow-location-list">
              <button type="button" v-for="item in whatSuggestions" :key="item.title" @mousedown.prevent="selectWhat(item)">
                <strong>{{ item.title }}</strong>
                <span>{{ item.subtitle || item.cat || 'Modality' }}</span>
              </button>
            </div>
            <div v-if="!whatLoaded" class="wow-panel-empty">Loading categories...</div>
            <div v-else-if="whatSuggestions.length === 0" class="wow-panel-empty">No matches found.</div>
          </div>
        </div>

        <div class="wow-segment wow-segment--where" data-search-segment="where" @click="openSegment('where')">
          <span class="wow-icon" aria-hidden="true">
            <i class="bi bi-geo-alt"></i>
          </span>
          <span class="wow-copy">
            <span class="wow-label">Where</span>
            <input
              :id="id('where')"
              ref="whereInput"
              :value="state.where"
              type="search"
              name="where"
              autocomplete="off"
              placeholder="City, region, or Online"
              :aria-expanded="activeSegment === 'where' ? 'true' : 'false'"
              :aria-controls="id('where-pane')"
              @focus="openSegment('where')"
              @input="onWhereInput"
            >
            <button
              v-if="state.where"
              type="button"
              class="wow-inline-clear"
              aria-label="Clear Where"
              @mousedown.stop.prevent
              @click.stop.prevent="setWhereValue('', true)"
            >
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
          </span>
        </div>

        <div
          v-show="activeSegment === 'where'"
          :id="id('where-pane')"
          class="wow-panel wow-panel--where"
          :style="activePanelStyle"
          :class="{ 'is-open': activeSegment === 'where' }"
          role="listbox"
          aria-label="Where suggestions"
          @pointerdown.stop
          @mousedown.stop
          @click.stop
        >
          <div class="wow-panel-inner">
            <div class="wow-panel-head">
              <strong>Where should we look?</strong>
              <span>Search online or by city, region, town or postcode.</span>
            </div>
            <div class="wow-location-list wow-location-list--icons">
              <button type="button" v-for="item in whereSuggestions" :key="item.value || item.title" @mousedown.prevent="selectWhere(item)">
                <span class="wow-destination-icon" :style="{ '--destination-bg': whereIconMeta(item).bg, '--destination-colour': whereIconMeta(item).color }" aria-hidden="true">
                  <i :class="whereIconMeta(item).icon"></i>
                </span>
                <span class="wow-destination-copy">
                  <strong>{{ item.title }}</strong>
                  <span>{{ item.subtitle || 'Destination' }}</span>
                </span>
              </button>
            </div>
            <div v-if="!whereLoaded" class="wow-panel-empty">Loading locations...</div>
            <div v-else-if="whereSuggestions.length === 0" class="wow-panel-empty">No locations found.</div>
          </div>
        </div>

        <div
          class="wow-segment wow-segment--when"
          role="button"
          tabindex="0"
          @click="openSegment('when')"
          @keydown.enter.prevent="openSegment('when')"
          @keydown.space.prevent="openSegment('when')"
          :aria-expanded="activeSegment === 'when' ? 'true' : 'false'"
        >
          <span class="wow-icon" aria-hidden="true">
            <i class="bi bi-calendar3"></i>
          </span>
          <span class="wow-copy">
            <span class="wow-label">When</span>
            <input
              :id="id('when')"
              ref="whenInput"
              :value="state.when || whenLabel"
              type="text"
              name="when"
              readonly
              aria-haspopup="dialog"
              placeholder="Select dates"
              @input="onWhenInput"
              @change="onWhenInput"
            >
          </span>
        </div>

        <div
          v-show="activeSegment === 'when'"
          :id="id('when-pane')"
          class="wow-panel wow-panel--calendar"
          :style="activePanelStyle"
          :class="{ 'is-open': activeSegment === 'when' }"
          aria-label="Calendar"
          @pointerdown.stop
          @mousedown.stop
          @click.stop
        >
          <div class="wow-panel-inner">
            <div class="wow-calendar-shell">
              <SearchRangeCalendar :prefix="props.idPrefix" />
            </div>
            <div class="wow-panel-actions">
              <button type="button" class="wow-clear-btn" @click="clearDate">Clear</button>
              <button type="button" class="wow-mini-btn" @click="applyDuration(1)">Today</button>
              <button type="button" class="wow-mini-btn" @click="applyDuration(2)">Tomorrow</button>
              <button type="button" class="wow-mini-btn" @click="applyDuration(7)">Next 7 days</button>
            </div>
          </div>
        </div>

        <div
          class="wow-segment wow-segment--who"
          role="button"
          tabindex="0"
          @click="openSegment('who')"
          @keydown.enter.prevent="openSegment('who')"
          @keydown.space.prevent="openSegment('who')"
          :aria-expanded="activeSegment === 'who' ? 'true' : 'false'"
        >
          <span class="wow-icon" aria-hidden="true">
            <i class="bi bi-person"></i>
          </span>
          <span class="wow-copy">
            <span class="wow-label">Who</span>
            <span class="wow-value">{{ guestsSummary }}</span>
          </span>
        </div>

        <div
          v-show="activeSegment === 'who'"
          :id="id('who-pane')"
          class="wow-panel wow-panel--who"
          :style="activePanelStyle"
          :class="{ 'is-open': activeSegment === 'who' }"
          aria-label="Guests"
          @pointerdown.stop
          @mousedown.stop
          @click.stop
        >
          <div class="wow-panel-inner">
            <div class="wow-panel-head">
              <strong>How many people?</strong>
              <span>Useful for private, couple or group sessions.</span>
            </div>
            <div class="wow-guest-row">
              <div>
                <strong>Guests</strong>
                <span>Adults attending</span>
              </div>
              <div class="wow-stepper">
                <button type="button" @click="setAdults(state.adults - 1)" aria-label="Decrease adults"><i class="bi bi-dash"></i></button>
                <strong>{{ state.adults }}</strong>
                <button type="button" @click="setAdults(state.adults + 1)" aria-label="Increase adults"><i class="bi bi-plus"></i></button>
              </div>
            </div>

            <div class="wow-panel-head wow-panel-head--tight">
              <strong>Group type</strong>
              <span>Solo, couple or group sessions.</span>
            </div>
            <div class="wow-pill-grid">
              <button type="button" class="wow-mini-pill" :class="{ 'is-active': state.groupType === 'Solo' }" @click="setGroupType('Solo')">Solo</button>
              <button type="button" class="wow-mini-pill" :class="{ 'is-active': state.groupType === 'Couple' }" @click="setGroupType('Couple')">Couple</button>
              <button type="button" class="wow-mini-pill" :class="{ 'is-active': state.groupType === 'Group' }" @click="setGroupType('Group')">Group</button>
            </div>

            <div class="wow-panel-actions">
              <button type="button" class="wow-done-btn" @click="closeSegments">Done</button>
            </div>
          </div>
        </div>

        <button class="wow-submit" type="submit" aria-label="Search" @click.prevent="applySearch(true)">
          <span class="visually-hidden">Search</span>
          <i class="bi bi-search" aria-hidden="true"></i>
        </button>
      </div>

    </form>

    <teleport v-if="showChrome || mobileChrome" to="body">
      <button
        v-show="searchBackdropOpen"
        type="button"
        class="wow-search-backdrop"
        aria-label="Close search dropdown"
        @click="collapseMobileSearch"
      ></button>
    </teleport>

    <div v-if="showChrome" class="wow-search-bottom-row" aria-label="Search filters">
      <div class="wow-active-chips" data-chip-list>
        <span v-for="chip in activeChips" :key="chip.key" class="wow-chip">
          <strong>{{ chip.label }}:</strong>
          <span>{{ chip.value }}</span>
          <button type="button" class="wow-chip-remove" @click="removeChip(chip.key)" :aria-label="`Remove ${chip.label}`">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path d="M6.7 6.7a1 1 0 0 1 1.4 0L12 10.6l3.9-3.9a1 1 0 1 1 1.4 1.4L13.4 12l3.9 3.9a1 1 0 1 1-1.4 1.4L12 13.4l-3.9 3.9a1 1 0 0 1-1.4-1.4l3.9-3.9-3.9-3.9a1 1 0 0 1 0-1.4Z"/>
            </svg>
          </button>
        </span>
      </div>

      <div class="wow-filter-actions">
        <div class="wow-results-count wow-results-count--compact">
          <strong>{{ displayCount }}</strong><span>results</span>
        </div>
      </div>
    </div>

    <teleport v-if="showChrome || mobileChrome" to="body">
      <div class="wow-filter-backdrop" :class="{ 'is-open': filterDrawerOpen }" @click="closeMobileFilterToExpandedSearch"></div>
    </teleport>

    <teleport v-if="showChrome || mobileChrome" to="body">
      <div
        class="wow-filter-drawer"
        :class="{ 'is-open': filterDrawerOpen, 'is-search-page-modal': isSearchPageModal }"
        data-filter-drawer
        role="dialog"
        aria-modal="true"
        aria-label="Search filters"
      >
        <div class="wow-filter-drawer-handle" aria-hidden="true">
          <span></span>
        </div>

        <div class="wow-filter-modal-header">
          <div>
            <p class="wow-filter-modal-kicker">Filters</p>
            <h2 class="wow-filter-modal-title">Refine your search</h2>
            <span class="wow-filter-modal-subtitle">Sort, price, rating and booking style are all ready to adjust.</span>
          </div>
          <button class="wow-filter-modal-close" type="button" @click="closeMobileFilterToExpandedSearch" aria-label="Close filters">×</button>
        </div>

        <div class="wow-filter-modal-body">
          <div class="wow-filter-modal-columns">
            <div class="wow-filter-modal-column">
              <div class="wow-filter-panel" data-filter-panel="sort" :class="{ 'is-open': filterSections.sort }">
                <button
                  type="button"
                  class="wow-filter-accordion-toggle"
                  :class="{ 'is-open': filterSections.sort }"
                  @click="toggleFilterSection('sort')"
                  :aria-expanded="filterSections.sort ? 'true' : 'false'"
                >
                  <span class="wow-filter-accordion-copy">
                    <strong>Sort results</strong>
                    <span>Choose how offerings should be ordered.</span>
                  </span>
                  <i class="bi" :class="filterSections.sort ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                </button>
                <div v-show="filterSections.sort" class="wow-filter-panel-body">
                  <div class="wow-panel-inner">
                    <div class="wow-location-list">
                      <button type="button" :class="{ 'is-active': state.sort === 'popular' }" @click="setSort('popular')"><strong>Recommended</strong><span>Best match for this page</span></button>
                      <button type="button" :class="{ 'is-active': state.sort === 'rating_desc' }" @click="setSort('rating_desc')"><strong>Highest rated</strong><span>Prioritise verified reviews</span></button>
                      <button type="button" :class="{ 'is-active': state.sort === 'price_asc' }" @click="setSort('price_asc')"><strong>Price: low to high</strong><span>Budget-friendly first</span></button>
                      <button type="button" :class="{ 'is-active': state.sort === 'price_desc' }" @click="setSort('price_desc')"><strong>Price: high to low</strong><span>Higher-priced offerings first</span></button>
                      <button type="button" :class="{ 'is-active': state.sort === 'newest' }" @click="setSort('newest')"><strong>Newest</strong><span>Recently added offerings</span></button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="wow-filter-panel" data-filter-panel="price" :class="{ 'is-open': filterSections.price }">
                <button
                  type="button"
                  class="wow-filter-accordion-toggle"
                  :class="{ 'is-open': filterSections.price }"
                  @click="toggleFilterSection('price')"
                  :aria-expanded="filterSections.price ? 'true' : 'false'"
                >
                  <span class="wow-filter-accordion-copy">
                    <strong>Set price range</strong>
                    <span>Keep results inside a comfortable budget.</span>
                  </span>
                  <i class="bi" :class="filterSections.price ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                </button>
                <div v-show="filterSections.price" class="wow-filter-panel-body">
                  <div class="wow-panel-inner">
                    <div class="wow-price-grid">
                      <div class="wow-price-boxes">
                        <div class="wow-price-input">
                          <label>Max price</label>
                          <input :id="id('price-max')" type="number" min="0" step="1" :value="state.priceMax" @input="setPriceMax($event.target.value)">
                        </div>
                      </div>
                      <input class="wow-range" type="range" min="10" max="500" step="5" :value="state.priceMax || 170" @input="setPriceMax($event.target.value)">
                      <div class="wow-price-presets">
                        <button class="wow-mini-pill" type="button" :class="{ 'is-active': state.priceMax === '50' }" @click="setPriceMax('50')">Under £50</button>
                        <button class="wow-mini-pill" type="button" :class="{ 'is-active': state.priceMax === '100' }" @click="setPriceMax('100')">Under £100</button>
                        <button class="wow-mini-pill" type="button" :class="{ 'is-active': state.priceMax === '170' }" @click="setPriceMax('170')">Under £170</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="wow-filter-modal-column">
              <div class="wow-filter-panel" data-filter-panel="type" :class="{ 'is-open': filterSections.type }">
                <button
                  type="button"
                  class="wow-filter-accordion-toggle"
                  :class="{ 'is-open': filterSections.type }"
                  @click="toggleFilterSection('type')"
                  :aria-expanded="filterSections.type ? 'true' : 'false'"
                >
                  <span class="wow-filter-accordion-copy">
                    <strong>Format:</strong>
                    <span>Type:</span>
                  </span>
                  <i class="bi" :class="filterSections.type ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                </button>
                <div v-show="filterSections.type" class="wow-filter-panel-body">
                  <div class="wow-panel-inner">
                    <div class="wow-pill-grid">
                      <button class="wow-choice-pill" type="button" :class="{ 'is-active': state.type === 'therapies' }" @click="setType('therapies')">Therapy</button>
                      <button class="wow-choice-pill" type="button" :class="{ 'is-active': state.type === 'classes' }" @click="setType('classes')">Class</button>
                      <button class="wow-choice-pill" type="button" :class="{ 'is-active': state.type === 'events' }" @click="setType('events')">Event</button>
                      <button class="wow-choice-pill" type="button" :class="{ 'is-active': state.type === 'retreats' }" @click="setType('retreats')">Retreat</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="wow-filter-panel" data-filter-panel="rating" :class="{ 'is-open': filterSections.rating }">
                <button
                  type="button"
                  class="wow-filter-accordion-toggle"
                  :class="{ 'is-open': filterSections.rating }"
                  @click="toggleFilterSection('rating')"
                  :aria-expanded="filterSections.rating ? 'true' : 'false'"
                >
                  <span class="wow-filter-accordion-copy">
                    <strong>Rating</strong>
                    <span>Prioritise offerings with stronger customer feedback.</span>
                  </span>
                  <i class="bi" :class="filterSections.rating ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                </button>
                <div v-show="filterSections.rating" class="wow-filter-panel-body">
                  <div class="wow-panel-inner">
                    <div class="wow-location-list">
                      <button type="button" :class="{ 'is-active': !state.rating }" @click="setRating('')">
                        <strong>Any rating</strong>
                        <span>Show everything</span>
                      </button>
                      <button type="button" :class="{ 'is-active': state.rating === '4.5' }" @click="setRating('4.5')">
                        <strong>4.5+ stars</strong>
                        <span>Only highly rated offerings</span>
                      </button>
                      <button type="button" :class="{ 'is-active': state.rating === '4' }" @click="setRating('4')">
                        <strong>4.0+ stars</strong>
                        <span>Well-rated offerings and practitioners</span>
                      </button>
                      <button type="button" :class="{ 'is-active': state.rating === 'reviewed' }" @click="setRating('reviewed')">
                        <strong>Reviewed only</strong>
                        <span>Hide offerings with no reviews yet</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="wow-filter-panel" data-filter-panel="mode" :class="{ 'is-open': filterSections.mode }">
                <button
                  type="button"
                  class="wow-filter-accordion-toggle"
                  :class="{ 'is-open': filterSections.mode }"
                  @click="toggleFilterSection('mode')"
                  :aria-expanded="filterSections.mode ? 'true' : 'false'"
                >
                  <span class="wow-filter-accordion-copy">
                    <strong>Mode</strong>
                    <span>Online sessions or bookings that can happen anytime.</span>
                  </span>
                  <i class="bi" :class="filterSections.mode ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                </button>
                <div v-show="filterSections.mode" class="wow-filter-panel-body">
                  <div class="wow-panel-inner">
                    <div class="wow-toggle-row">
                      <button type="button" class="wow-toggle-line" :class="{ 'is-active': state.onlineOnly }" @click="setOnlineOnly(!state.onlineOnly)">
                        <span class="wow-toggle-text">
                          <strong>Online only</strong>
                          <span>Filter to remote sessions</span>
                        </span>
                        <span class="wow-switch" aria-hidden="true"></span>
                      </button>
                      <button type="button" class="wow-toggle-line" :class="{ 'is-active': state.anytime }" @click="setAnytime(!state.anytime)">
                        <span class="wow-toggle-text">
                          <strong>Anytime</strong>
                          <span>Hide date-specific offerings</span>
                        </span>
                        <span class="wow-switch" aria-hidden="true"></span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="wow-filter-modal-footer">
          <button class="wow-clear-btn" type="button" @click="clearAllFilters">Clear all</button>
          <button class="wow-done-btn" type="button" @click="applySearch(true)">Show {{ displayCount }} results</button>
        </div>
      </div>
    </teleport>
    </section>
  </div>
</template>

<style>
.wow-search-filter{
  position:relative;
  z-index:1950;
  box-sizing:border-box;
  font-family:Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  font-size:14px;
  line-height:1.2;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
  overflow-x:clip;
}

.wow-search-filter.is-static-layout{
  overflow:visible;
  isolation:isolate;
}

.wow-search-backdrop{
  position:fixed;
  inset:0;
  z-index:1900;
  border:0;
  padding:0;
  background:rgba(15, 23, 42, .28);
  backdrop-filter:blur(2px);
  -webkit-backdrop-filter:blur(2px);
  pointer-events:auto;
  cursor:default;
}

.wow-search-filter *{
  box-sizing:border-box;
}

.wow-search-top-row{
  width:100%;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  margin-bottom:10px;
}

.wow-filter-icon-btn{
  position:relative;
  width:46px;
  height:46px;
  border:1px solid rgba(84,148,131,.24);
  border-radius:999px;
  background:rgba(255,255,255,.98);
  color:#111827;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  box-shadow:0 12px 28px rgba(16,24,40,.08);
  transition:background 160ms ease, transform 160ms ease, box-shadow 160ms ease, color 160ms ease;
}

.wow-filter-icon-btn:hover,
.wow-filter-icon-btn.is-open{
  background:#e8f5f1;
  color:#2f6f60;
  transform:translateY(-1px);
  box-shadow:0 16px 34px rgba(16,24,40,.10);
}

.wow-filter-icon-btn i{
  font-size:18px;
  line-height:1;
}

.wow-filter-badge{
  position:absolute;
  top:-8px;
  right:-8px;
  min-width:24px;
  min-height:24px;
  padding:0 7px;
  border:2px solid #fff;
  border-radius:999px;
  background:#549483;
  color:#fff;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  box-shadow:0 8px 18px rgba(16,24,40,.10);
  font-size:12px;
  font-weight:800;
}

.wow-results-display-row{
  flex:0 0 auto;
  display:flex;
  align-items:center;
  justify-content:flex-end;
  gap:18px;
  margin-left:auto;
}

.wow-results-count{
  min-height:40px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border:1px solid rgba(84,148,131,.20);
  border-radius:999px;
  background:#e8f5f1;
  color:#2f6f60;
  padding:0 14px;
  font-size:14px;
  font-weight:750;
  white-space:nowrap;
}

.wow-results-count span{
  color:#667085;
  font-weight:550;
  margin-left:5px;
}

.wow-results-count strong{
  font-size:inherit;
}

.wow-results-count--compact{
  min-height:38px;
}

.wow-desktop-map-controls{
  flex:0 0 auto;
  display:flex;
  align-items:center;
  justify-content:flex-end;
  gap:22px;
  margin:0;
  padding:0;
}

.wow-segmented-control{
  display:inline-flex;
  align-items:center;
  gap:8px;
  color:#111827;
  white-space:nowrap;
}

.wow-control-label{
  font-size:15px;
  font-weight:650;
  letter-spacing:-.02em;
  color:#111827;
}

.wow-control-pill{
  min-height:42px;
  display:inline-flex;
  align-items:center;
  gap:2px;
  padding:3px;
  border:1px solid #dce4ec;
  border-radius:999px;
  background:#f8fafc;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.8);
}

.wow-control-pill button{
  min-width:58px;
  min-height:34px;
  border:0;
  border-radius:999px;
  background:transparent;
  color:#344054;
  padding:0 14px;
  font-size:14px;
  font-weight:750;
  letter-spacing:-.015em;
  transition:background 160ms ease, color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
}

.wow-control-pill button:hover{
  color:#111827;
  background:rgba(255,255,255,.72);
}

.wow-control-pill button.is-active{
  background:#549483;
  color:#fff;
  box-shadow:0 6px 14px rgba(84,148,131,.26);
}

.wow-control-pill button.is-active:hover{
  color:#fff;
  transform:translateY(-1px);
}

.wow-search-card{
  position:relative;
  width:100%;
  min-width:0;
  border:1px solid rgba(207,215,227,.96);
  border-radius:60px;
  background:rgba(255,255,255,.97);
  box-shadow:0 16px 42px rgba(16,24,40,.075);
  backdrop-filter:blur(16px);
  padding:6px;
}

.wow-search-main{
  display:grid;
  grid-template-columns:minmax(220px, 1.15fr) minmax(220px, 1.1fr) minmax(180px, .82fr) minmax(170px, .76fr) minmax(86px, .28fr);
  gap:8px;
  min-height:86px;
  min-width:0;
}

.wow-segment{
  min-width:0;
  border:0;
  border-radius:52px;
  background:transparent;
  color:#111827;
  display:grid;
  grid-template-columns:42px minmax(0, 1fr);
  gap:12px;
  align-items:center;
  padding:0 18px;
  text-align:left;
  text-decoration:none;
  transition:background 160ms ease, box-shadow 160ms ease, transform 160ms ease;
  position:relative;
}

.wow-segment:hover,
.wow-segment:focus-within{
  background:#fff;
}

.wow-segment:focus-within{
  box-shadow:inset 0 0 0 1px rgba(84,148,131,.18), 0 0 0 4px rgba(84,148,131,.18);
  transform:translateY(-1px);
}

.wow-icon{
  width:40px;
  height:40px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  color:#4b5563;
}

.wow-icon i{
  font-size:20px;
}

.wow-copy{
  position:relative;
  min-width:0;
  display:block;
}

.wow-label{
  display:block;
  color:#667085;
  font-size:14px;
  line-height:1.1;
  margin-bottom:5px;
}

.wow-copy input{
  width:100%;
  border:0;
  outline:0;
  background:transparent;
  color:#111827;
  font-size:14px;
  line-height:1.2;
  letter-spacing:-.015em;
  padding:0 34px 0 0;
  appearance:none;
  -webkit-appearance:none;
}

.wow-copy input[type="search"]::-webkit-search-cancel-button,
.wow-copy input[type="search"]::-webkit-search-decoration,
.wow-copy input[type="search"]::-webkit-search-results-button,
.wow-copy input[type="search"]::-webkit-search-results-decoration{
  appearance:none;
  -webkit-appearance:none;
  display:none;
}

.wow-inline-clear{
  position:absolute;
  top:calc(50% - 10px);
  right:0;
  width:24px;
  height:24px;
  border:1px solid rgba(84,148,131,.16);
  border-radius:999px;
  background:linear-gradient(180deg, rgba(255,255,255,.98), rgba(241,246,244,.9));
  color:#436c60;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  transform:none;
  box-shadow:0 6px 16px rgba(16,24,40,.08);
  transition:transform .16s ease, background-color .16s ease, border-color .16s ease, box-shadow .16s ease, color .16s ease;
}

.wow-inline-clear:hover{
  transform:scale(1.04);
  border-color:rgba(84,148,131,.32);
  background:linear-gradient(180deg, #fff, #e7f3ef);
  color:#2f5d51;
  box-shadow:0 10px 20px rgba(16,24,40,.12);
}

.wow-inline-clear:focus-visible{
  outline:none;
  box-shadow:0 0 0 3px rgba(84,148,131,.18), 0 10px 20px rgba(16,24,40,.12);
}

.wow-copy input::placeholder,
.wow-value.is-placeholder{
  color:#777f89;
}

.wow-value{
  display:block;
  max-width:100%;
  color:#111827;
  font-size:14px;
  line-height:1.2;
  letter-spacing:-.015em;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

.wow-submit{
  border:0;
  border-radius:999px;
  background:#050505;
  color:#fff;
  min-width:76px;
  padding:0;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:0;
  font-size:0;
  font-weight:550;
  letter-spacing:-.035em;
  transition:transform 160ms ease, box-shadow 160ms ease, background 160ms ease;
}

.wow-submit:hover{
  background:#111827;
  transform:translateY(-1px);
  box-shadow:0 16px 34px rgba(17,24,39,.24);
}

.wow-submit i{
  font-size:22px;
}

.wow-search-bottom-row{
  width:100%;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:14px;
  padding:12px 6px 0;
}

.wow-active-chips{
  min-width:0;
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  flex:1 1 auto;
}

.wow-chip{
  min-height:36px;
  display:inline-flex;
  align-items:center;
  gap:8px;
  border:1px solid #e1e7ee;
  border-radius:999px;
  background:#fff;
  color:#1f2937;
  padding:0 10px 0 13px;
  font-size:13px;
  box-shadow:0 10px 24px rgba(16,24,40,.055);
}

.wow-chip-remove{
  width:22px;
  height:22px;
  border:1px solid rgba(84,148,131,.18);
  border-radius:999px;
  background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(241,246,244,.88));
  color:#3f6e61;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  font-size:10px;
  line-height:1;
  box-shadow:0 6px 14px rgba(16,24,40,.08);
  transition:transform .16s ease, box-shadow .16s ease, background-color .16s ease, border-color .16s ease, color .16s ease;
}

.wow-chip-remove svg{
  width:12px;
  height:12px;
  display:block;
  fill:currentColor;
}

.wow-chip-remove:hover{
  transform:translateY(-1px);
  border-color:rgba(84,148,131,.34);
  background:linear-gradient(180deg, #fff, #e8f4f0);
  color:#2f5d51;
  box-shadow:0 8px 18px rgba(16,24,40,.12);
}

.wow-chip-remove:focus-visible{
  outline:none;
  box-shadow:0 0 0 3px rgba(84,148,131,.18), 0 8px 18px rgba(16,24,40,.12);
}

.wow-filter-actions{
  flex:0 0 auto;
  display:flex;
  align-items:center;
  gap:10px;
  margin-left:auto;
}

.wow-filter-backdrop{
  position:fixed;
  inset:0;
  z-index:450;
  background:rgba(16,24,40,.42);
  opacity:0;
  visibility:hidden;
  pointer-events:none;
  backdrop-filter:blur(2px);
  -webkit-backdrop-filter:blur(2px);
  transition:opacity 180ms ease, visibility 180ms ease;
}

.wow-filter-backdrop.is-open{
  opacity:1;
  visibility:visible;
  pointer-events:auto;
}

.wow-filter-drawer{
  position:fixed;
  top:50%;
  left:50%;
  z-index:2000;
  width:min(680px, calc(100vw - 48px));
  max-height:80vh;
  overflow:hidden;
  margin:0;
  border-radius:24px;
  background:#fff;
  box-shadow:0 28px 80px rgba(16,24,40,.22);
  display:flex;
  flex-direction:column;
  opacity:0;
  visibility:hidden;
  pointer-events:none;
  transform:translate(-50%, -50%) scale(.985);
  transition:opacity 180ms ease, visibility 180ms ease, transform 180ms ease;
}

.wow-filter-drawer.is-open{
  opacity:1;
  visibility:visible;
  pointer-events:auto;
  transform:translate(-50%, -50%) scale(1);
}

.wow-filter-drawer-handle{
  display:none;
  justify-content:center;
  padding:10px 0 0;
}

.wow-filter-drawer-handle span{
  width:36px;
  height:4px;
  border-radius:999px;
  background:#e0e4eb;
}

.wow-filter-modal-header{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:18px;
  padding:20px 24px 16px;
  border-bottom:1px solid #f0f0f0;
}

.wow-filter-modal-kicker{
  margin:0 0 6px;
  color:#437c6d;
  font-size:12px;
  font-weight:850;
  letter-spacing:.12em;
  text-transform:uppercase;
}

.wow-filter-modal-title{
  margin:0;
  color:#111827;
  font-size:24px;
  font-weight:850;
  line-height:1.05;
  letter-spacing:-.045em;
}

.wow-filter-modal-subtitle{
  display:block;
  margin-top:8px;
  color:#667085;
  font-size:14px;
  line-height:1.45;
}

.wow-filter-modal-footer{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  padding:12px 24px 20px;
  border-top:1px solid #f0f0f0;
  background:rgba(255,255,255,.97);
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
}

.wow-filter-modal-close{
  width:42px;
  height:42px;
  border:1px solid #e1e7ee;
  border-radius:999px;
  background:#fff;
  color:#111827;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  font-size:24px;
  line-height:1;
}

.wow-filter-modal-body{
  flex:1 1 auto;
  min-height:0;
  max-height:none;
  overflow-y:auto;
  overflow-x:hidden;
  padding:0 24px 20px;
  scrollbar-width:thin;
  scrollbar-color:#111827 transparent;
}

.wow-filter-modal-columns{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:0 32px;
}

.wow-filter-modal-column{
  display:block;
}

.wow-filter-modal-body::-webkit-scrollbar{
  width:6px;
}

.wow-filter-modal-body::-webkit-scrollbar-track{
  background:transparent;
}

.wow-filter-modal-body::-webkit-scrollbar-thumb{
  background:#111827;
  border-radius:999px;
}

.wow-filter-panel{
  display:block;
  border-bottom:1px solid rgba(16,24,40,.07);
  padding-bottom:0;
}

.wow-filter-accordion-toggle{
  width:100%;
  min-height:74px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  padding:14px 0;
  border:0;
  background:none;
  color:#111827;
  text-align:left;
  cursor:pointer;
  transition:color 160ms ease, transform 160ms ease;
}

.wow-filter-accordion-toggle:hover,
.wow-filter-accordion-toggle:focus-visible{
  outline:none;
}

.wow-filter-accordion-toggle.is-open{
  color:#111827;
}

.wow-filter-accordion-copy{
  min-width:0;
  display:grid;
  gap:4px;
}

.wow-filter-accordion-copy strong{
  color:#101828;
  font-size:16px;
  font-weight:850;
  line-height:1.15;
  letter-spacing:-.03em;
}

.wow-filter-accordion-copy span{
  color:#667085;
  font-size:13px;
  line-height:1.35;
}

.wow-filter-accordion-toggle i{
  flex:0 0 auto;
  color:#667085;
  font-size:18px;
  transition:transform 160ms ease, color 160ms ease;
}

.wow-filter-accordion-toggle.is-open i{
  color:#2f6f60;
  transform:rotate(180deg);
}

.wow-filter-panel-body{
  border-top:none;
  background:transparent;
}

.wow-panel{
  position:absolute;
  left:0;
  right:0;
  top:calc(100% + 10px);
  width:auto;
  margin:0;
  background:#fff;
  border:1px solid #dbe2ea;
  border-radius:22px;
  box-shadow:0 14px 34px rgba(16,24,40,.08);
  overflow:hidden;
  text-align:left;
  z-index:1950;
}

.wow-search-filter.is-static-layout .wow-search-card{
  position:relative;
  z-index:2;
}

.wow-search-filter.is-static-layout .wow-panel{
  z-index:4000;
}

.wow-panel--calendar{
  width:min(900px, calc(100vw - 32px));
  left:auto;
  right:0;
  transform:none;
}

@media (min-width: 1041px){
  #hero-search-v4-when-pane{
    width:auto;
  }
}

.wow-panel--what,
.wow-panel--where{
  width:min(420px, calc(100vw - 32px));
}

.wow-panel--who{
  right:0;
  left:auto;
  width:min(560px, 96vw);
}

.wow-panel-inner{
  padding:18px;
}

.wow-panel-head{
  display:grid;
  gap:4px;
  margin-bottom:14px;
}

.wow-panel-head--tight{
  margin-top:6px;
}

.wow-panel-head strong{
  color:#111827;
  font-size:18px;
  font-weight:800;
  letter-spacing:-.025em;
}

.wow-panel-head span{
  color:#667085;
  font-size:13px;
  line-height:1.45;
}

.wow-panel-empty{
  padding:12px 2px 2px;
  color:#667085;
  font-size:13px;
}

.wow-pill-grid{
  display:flex;
  flex-wrap:wrap;
  gap:9px;
}

.wow-pill,
.wow-mini-pill,
.wow-choice-pill,
.wow-clear-btn,
.wow-done-btn{
  min-height:42px;
  border:1px solid #dbe2ea;
  border-radius:999px;
  background:#fff;
  color:#344054;
  padding:0 15px;
  font-size:14px;
  font-weight:750;
  transition:background 150ms ease, border-color 150ms ease, color 150ms ease, transform 150ms ease;
}

.wow-pill{
  display:grid;
  align-content:center;
  gap:2px;
  text-align:left;
  flex:1 1 180px;
  min-width:180px;
  min-height:60px;
  padding:10px 14px;
}

.wow-pill:hover,
.wow-mini-pill:hover,
.wow-choice-pill:hover,
.wow-clear-btn:hover,
.wow-done-btn:hover{
  transform:translateY(-1px);
}

.wow-pill.is-active,
.wow-mini-pill.is-active,
.wow-choice-pill.is-active{
  background:#e8f5f1;
  border-color:rgba(84,148,131,.34);
  color:#2f6f60;
}

.wow-pill-title{
  display:block;
  color:inherit;
  font-size:14px;
  font-weight:800;
}

.wow-pill-subtitle{
  display:block;
  color:#667085;
  font-size:12px;
  font-weight:600;
}

.wow-location-list{
  display:grid;
  gap:8px;
}

.wow-location-list button{
  width:100%;
  min-height:58px;
  display:grid;
  gap:3px;
  border:1px solid transparent;
  border-radius:12px;
  background:#fff;
  text-align:left;
  padding:11px 13px;
  transition:background 150ms ease, border-color 150ms ease, transform 150ms ease;
}

.wow-location-list button:hover,
.wow-location-list button.is-active{
  background:#e8f5f1;
  border-color:rgba(79,147,129,.35);
}

.wow-location-list strong{
  color:#111827;
  font-size:15px;
  font-weight:800;
}

.wow-location-list span{
  color:#667085;
  font-size:13px;
}

.wow-location-list--icons{
  gap:10px;
  max-height:440px;
  overflow-y:auto;
  padding-right:4px;
  scrollbar-width:thin;
  scrollbar-color:rgba(17,24,39,.45) transparent;
}

.wow-location-list--icons::-webkit-scrollbar{
  width:5px;
}

.wow-location-list--icons::-webkit-scrollbar-track{
  background:transparent;
}

.wow-location-list--icons::-webkit-scrollbar-thumb{
  background:rgba(17,24,39,.45);
  border-radius:999px;
}

.wow-location-list--icons button{
  min-height:72px;
  grid-template-columns:56px minmax(0, 1fr);
  align-items:center;
  gap:16px;
  padding:8px 10px;
  border-radius:20px;
}

.wow-location-list--icons button:hover{
  background:#fbfcfd;
  border-color:rgba(84,148,131,.16);
}

.wow-destination-icon{
  width:56px;
  height:56px;
  border-radius:16px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  background:var(--destination-bg, #eef5ff);
  color:var(--destination-colour, #549483);
  flex:0 0 auto;
}

.wow-destination-icon i{
  font-size:26px;
  line-height:1;
}

.wow-destination-copy{
  min-width:0;
  display:grid;
  gap:3px;
}

.wow-destination-copy strong{
  color:#20242b;
  font-size:15px;
  font-weight:800;
  letter-spacing:-.015em;
}

.wow-destination-copy span{
  color:#667085;
  font-size:13px;
  line-height:1.35;
}

.wow-calendar-shell{
  padding-top:4px;
}

.wow-panel-actions{
  display:flex;
  justify-content:space-between;
  gap:12px;
  margin-top:14px;
}

.wow-clear-btn{
  border:0;
  background:#f2f4f7;
  color:#667085;
}

.wow-done-btn{
  border:0;
  background:#549483;
  color:#fff;
}

.wow-mini-btn{
  min-height:42px;
  border:1px solid #dbe2ea;
  border-radius:999px;
  background:#fff;
  color:#344054;
  padding:0 15px;
  font-size:14px;
  font-weight:750;
  transition:background 150ms ease, border-color 150ms ease, color 150ms ease, transform 150ms ease;
}

.wow-mini-btn:hover,
.wow-mini-btn:focus-visible{
  background:var(--wow-green-soft);
  border-color:rgba(84,148,131,.34);
  color:#2f6f60;
  transform:translateY(-1px);
}

.wow-price-grid{
  display:grid;
  gap:14px;
}

.wow-price-boxes{
  display:grid;
  grid-template-columns:1fr;
  gap:12px;
}

.wow-price-input label{
  display:block;
  color:#667085;
  font-size:13px;
  font-weight:700;
  margin-bottom:7px;
}

.wow-price-input input,
.wow-range{
  width:100%;
}

.wow-price-input input{
  min-height:48px;
  border:1px solid #dbe2ea;
  border-radius:15px;
  padding:0 13px;
  color:#111827;
  font-size:15px;
}

.wow-range{
  accent-color:#549483;
}

.wow-price-presets{
  display:grid;
  grid-template-columns:repeat(3, 1fr);
  gap:8px;
}

.wow-more-section{
  display:grid;
  gap:16px;
}

.wow-more-block{
  display:grid;
  gap:10px;
}

.wow-more-heading{
  margin:0;
  color:#111827;
  font-size:13px;
  font-weight:850;
  letter-spacing:.08em;
  text-transform:uppercase;
}

.wow-toggle-row{
  display:grid;
  gap:8px;
}

.wow-toggle-line{
  min-height:50px;
  border:1px solid #dbe2ea;
  border-radius:15px;
  background:#fff;
  display:grid;
  grid-template-columns:minmax(0, 1fr) 46px;
  gap:12px;
  align-items:center;
  padding:0 12px 0 14px;
  text-align:left;
}

.wow-toggle-line.is-active{
  border-color:rgba(79,147,129,.28);
  background:rgba(232,245,241,.5);
}

.wow-toggle-text strong{
  display:block;
  font-size:14px;
}

.wow-toggle-text span{
  display:block;
  margin-top:2px;
  color:#667085;
  font-size:12px;
}

.wow-switch{
  width:44px;
  height:26px;
  border-radius:999px;
  background:#d0d5dd;
  position:relative;
  transition:background 150ms ease;
}

.wow-switch::after{
  content:"";
  position:absolute;
  width:20px;
  height:20px;
  left:3px;
  top:3px;
  border-radius:999px;
  background:#fff;
  transition:transform 150ms ease;
}

.wow-toggle-line.is-active .wow-switch{
  background:#549483;
}

.wow-toggle-line.is-active .wow-switch::after{
  transform:translateX(18px);
}

.wow-guest-row{
  min-height:76px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:18px;
  border:1px solid #edf0f4;
  border-radius:18px;
  padding:14px;
  background:#fbfcfd;
}

.wow-guest-row strong{
  display:block;
  color:#111827;
  font-size:16px;
  font-weight:800;
}

.wow-guest-row span{
  display:block;
  margin-top:3px;
  color:#667085;
  font-size:13px;
}

.wow-stepper{
  display:inline-flex;
  align-items:center;
  gap:12px;
}

.wow-stepper button{
  width:42px;
  height:42px;
  border:1px solid #dbe2ea;
  border-radius:999px;
  background:#fff;
  color:#111827;
  font-size:24px;
  line-height:1;
}

.wow-stepper strong{
  min-width:28px;
  text-align:center;
  font-size:18px;
}

.wow-search-filter-shell{
  width:100%;
}

@media (min-width: 1041px){
  .wow-search-filter-shell{
    position:relative;
    width:100%;
  }

  .wow-search-filter{
    position:fixed;
    top:calc(var(--wow-header-offset, 0px) + 12px);
    left:50%;
    transform:translateX(-50%);
    z-index:180;
    width:100%;
    max-width:min(850px, calc(100vw - 32px));
    margin:0;
    padding:0;
    border:1px solid transparent;
    border-radius:28px;
    background:transparent;
    box-shadow:none;
    backdrop-filter:none;
    -webkit-backdrop-filter:none;
    transition:max-width 220ms ease, padding 220ms ease, background-color 220ms ease, border-color 220ms ease, box-shadow 220ms ease, border-radius 220ms ease;
    will-change:max-width, padding, background-color, border-color, box-shadow, border-radius;
  }

  .wow-search-filter.is-static-layout{
    position:relative !important;
    top:auto !important;
    left:auto !important;
    right:auto !important;
    bottom:auto !important;
    transform:none !important;
    width:100% !important;
    max-width:none !important;
    margin:0 !important;
    padding:0 !important;
    border:0 !important;
    background:none !important;
    box-shadow:none !important;
    backdrop-filter:none !important;
    -webkit-backdrop-filter:none !important;
    z-index:5000 !important;
    overflow:visible !important;
  }

  .wow-panel{
    right:350px;
    width:400px;
  }

  .wow-search-filter.is-static-layout .wow-panel{
    z-index:6000;
  }

  .wow-panel--who{
    right:65px !important;
  }

  div#search-v4-when-pane{
    width:700px;
    right:350px;
  }

  .wow-search-card{
    height:68px;
    min-height:68px;
    padding:6px;
    border-radius:999px;
  }

  .wow-search-main{
    height:100%;
    min-height:0;
    grid-template-columns:minmax(140px, 1fr) minmax(140px, 1fr) minmax(140px, 1fr) minmax(140px, 1fr) 58px;
    gap:6px;
  }

  .wow-segment{
    min-height:56px;
    height:56px;
    border-radius:999px;
    grid-template-columns:34px minmax(0, 1fr);
    gap:8px;
    padding:0 13px;
  }

  .wow-segment .wow-icon{
    width:34px;
    height:34px;
  }

  .wow-segment .wow-icon i{
    font-size:17px;
  }

  .wow-label{
    font-size:12px;
    margin-bottom:3px;
  }

  .wow-copy input,
  .wow-value{
    font-size:15px;
  }

  .wow-submit{
    min-width:62px;
    min-height:56px;
    height:56px;
  }

  .wow-submit i{
    font-size:20px;
  }

  .wow-filter-modal-header,
  .wow-filter-modal-footer{
    display:flex;
  }

  .wow-filter-panel{
    display:block;
    margin-top:12px;
  }

  .wow-filter-panel.is-open{
    padding-bottom:16px;
  }

  .wow-filter-drawer{
    top:50%;
    left:50%;
    z-index:2000;
    width:min(680px, calc(100vw - 48px));
    max-height:80vh;
    height:auto;
  }

  .wow-filter-drawer.is-search-page-modal .wow-filter-modal-columns{
    display:block;
  }

  .wow-filter-drawer.is-search-page-modal .wow-filter-modal-column{
    display:block;
  }

  .wow-search-filter.is-filter-drawer-open{
    z-index:1800;
  }
}

@media (max-width: 1280px){
  .wow-search-main{
    grid-template-columns:minmax(140px, 1fr) minmax(140px, 1fr) minmax(140px, 1fr) minmax(140px, 1fr) 58px;
  }
}

@media (max-width: 1040px){
  .wow-search-filter{
    padding:0 24px !important;
  }

  .wow-search-top-row{
    flex-wrap:wrap;
  }

  .wow-search-card{
    border-radius:26px;
    padding:7px;
  }

  .wow-search-main{
    grid-template-columns:1fr;
    gap:6px;
  }

  .wow-segment{
    min-height:66px;
    border-radius:18px;
    grid-template-columns:32px minmax(0, 1fr);
    gap:8px;
    padding:0 11px;
  }

  .wow-segment .wow-icon{
    width:32px;
    height:32px;
  }

  .wow-segment .wow-icon i{
    font-size:18px;
  }

  .wow-label{
    font-size:12px;
  }

  .wow-copy input,
  .wow-value{
    font-size:15px;
  }

  .wow-submit{
    min-height:58px;
    border-radius:999px;
    font-size:0;
  }

  .wow-search-bottom-row{
    display:grid;
    grid-template-columns:1fr auto;
    gap:10px;
    padding:10px 2px 0;
  }

  .wow-filter-drawer{
    position:fixed;
    inset:auto 0 0;
    z-index:2000;
    width:100%;
    max-height:88vh;
    margin:0;
    border-radius:24px 24px 0 0;
    overflow:hidden;
    transform:translateY(calc(100% + 20px));
    box-shadow:0 -20px 60px rgba(16,24,40,.2);
  }

  .wow-filter-drawer.is-open{
    transform:translateY(0);
  }

  .wow-filter-drawer-handle{
    display:flex;
  }

  .wow-filter-modal-header,
  .wow-filter-modal-footer{
    display:flex;
  }

  .wow-filter-modal-header{
    padding:12px 20px 12px;
  }

  .wow-filter-modal-title{
    font-size:22px;
  }

  .wow-filter-modal-subtitle{
    font-size:13px;
  }

  .wow-filter-modal-body{
    overflow-y:auto;
    overflow-x:hidden;
    padding:0 20px 0;
    margin-right:0;
  }

  .wow-filter-modal-columns{
    display:block;
  }

  .wow-filter-modal-column{
    display:block;
  }

  .wow-filter-panel{
    border-bottom:1px solid rgba(16,24,40,.07);
  }

  .wow-filter-panel.is-open{
    padding-bottom:16px;
  }

  .wow-filter-accordion-toggle{
    min-height:74px;
    padding:14px 0;
  }

  .wow-filter-accordion-copy strong{
    font-size:16px;
  }

  .wow-filter-accordion-copy span{
    font-size:13px;
  }

  .wow-filter-panel-body{
    padding-bottom:16px;
  }

  .wow-panel-inner{
    padding:0;
  }

  .wow-location-list button{
    min-height:58px;
    border-radius:12px;
  }

  .wow-price-grid{
    gap:12px;
  }

  .wow-price-presets{
    grid-template-columns:repeat(3, 1fr);
  }

  .wow-panel-actions{
    display:none;
  }

  .wow-toggle-line{
    min-height:52px;
  }

  .wow-filter-modal-footer{
    padding:12px 20px 20px;
    gap:10px;
  }

  .wow-filter-modal-footer .wow-clear-btn,
  .wow-filter-modal-footer .wow-done-btn{
    height:46px;
  }

  .wow-segment .wow-copy{
    align-self:stretch;
    display:flex;
    flex-direction:column;
    justify-content:center;
    height:66px;
  }

  .wow-location-list--icons{
    max-height:none;
    overflow:visible;
    padding-right:0;
  }

  .wow-location-list--icons button{
    min-height:68px;
    grid-template-columns:50px minmax(0, 1fr);
    gap:13px;
  }

  .wow-destination-icon{
    width:50px;
    height:50px;
    border-radius:15px;
  }

  .wow-destination-icon i{
    font-size:24px;
  }
}

@media (max-width: 620px){
  .wow-results-display-row{
    width:100%;
    justify-content:space-between;
  }

  .wow-results-count{
    margin-left:auto;
  }

  .wow-desktop-map-controls{
    display:none !important;
  }

  .wow-filter-icon-btn{
    width:42px;
    height:42px;
  }

  .wow-mobile-search-close{
    width:42px;
    height:42px;
    border:1px solid rgba(84,148,131,.24);
    border-radius:999px;
    background:#fff;
    color:#111827;
    display:none;
    align-items:center;
    justify-content:center;
    box-shadow:0 12px 28px rgba(16,24,40,.08);
    transition: background 160ms ease, transform 160ms ease, box-shadow 160ms ease, color 160ms ease;
  }

  .wow-mobile-search-close:hover,
  .wow-mobile-search-close:focus-visible{
    background:var(--wow-green-soft);
    color:#2f6f60;
    transform:translateY(-1px);
    box-shadow:0 16px 34px rgba(16,24,40,.10);
  }

  .wow-search-filter.is-mobile-expanded .wow-search-card{
    border-radius:22px;
    box-shadow:0 24px 70px rgba(16,24,40,.22);
  }

  .wow-search-filter.is-mobile-expanded{
    position:fixed;
    top:65px;
    left:0;
    right:0;
    width:100vw;
    max-width:100vw;
    height:100vh;
    max-height:none;
    z-index:1950;
    padding:12px 0 14px;
    overflow-y:auto;
    overflow-x:hidden;
    overscroll-behavior:contain;
    -webkit-overflow-scrolling:touch;
    background:rgba(0, 0, 0, .7);
    backdrop-filter:blur(14px) saturate(140%);
    -webkit-backdrop-filter:blur(14px) saturate(140%);
    box-shadow:none;
  }

  .wow-search-filter.is-mobile-expanded .wow-search-card{
    width:calc(100vw - 24px);
    max-width:none;
    margin:0 12px;
    overflow:visible;
  }

  .wow-search-bottom-row{
    grid-template-columns:1fr;
  }

  .wow-active-chips{
    display:none !important;
  }

  .wow-filter-actions{
    width:auto;
    justify-content:flex-end;
  }
}

@media (max-width: 1040px){
  .wow-search-filter.is-static-layout.is-mobile-expanded{
    position:fixed;
    top:var(--wow-search-filter-mobile-top, 70px);
    top:100px !important;
    left:0;
    right:0;
    width:100vw;
    max-width:100vw;
    height:calc(100dvh - var(--wow-search-filter-mobile-top, 70px));
    max-height:none;
    z-index:5000 !important;
    padding:12px 0 14px;
    margin:0;
    background:transparent;
    backdrop-filter:none;
    -webkit-backdrop-filter:none;
    box-shadow:none;
    overflow-y:auto;
    overflow-x:hidden;
    overscroll-behavior:contain;
    -webkit-overflow-scrolling:touch;
  }

  .wow-search-filter.is-static-layout.is-mobile-expanded.is-panel-open{
    background:none;
    padding:0 24px !important;
  }

  .wow-search-filter.is-static-layout.is-mobile-expanded.is-force-mobile-layout.is-panel-open{
    padding:0 24px !important;
    top:100px !important;
  }

  .wow-search-filter.is-static-layout.is-mobile-expanded .wow-search-card{
    width:100%;
    max-width:none;
    margin:0;
  }

  .wow-search-filter:not(.is-mobile-expanded) .wow-search-top-row{
    display:none;
  }

  .wow-search-filter:not(.is-mobile-expanded) .wow-search-card{
    border-radius:999px;
    padding:6px;
  }

  .wow-search-filter:not(.is-mobile-expanded) .wow-search-main{
    grid-template-columns:1fr !important;
    gap:0;
    min-height:auto;
  }

  .wow-search-filter:not(.is-mobile-expanded) .wow-segment--what{
    min-height:58px;
    border-radius:999px;
    grid-template-columns:34px minmax(0, 1fr);
    padding:0 14px;
    background:transparent;
  }

  .wow-search-filter:not(.is-mobile-expanded) .wow-segment--where,
  .wow-search-filter:not(.is-mobile-expanded) .wow-segment--when,
  .wow-search-filter:not(.is-mobile-expanded) .wow-segment--who,
  .wow-search-filter:not(.is-mobile-expanded) .wow-submit,
  .wow-search-filter:not(.is-mobile-expanded) .wow-search-bottom-row{
    display:none !important;
  }

  .wow-search-filter.is-mobile-expanded .wow-search-top-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:nowrap;
    width:calc(100% - 24px);
    gap:12px;
    margin:10px 12px 8px;
    margin-bottom:8px;
  }

  .wow-search-filter.is-mobile-expanded .wow-mobile-search-close{
    display:inline-flex;
  }

  .wow-active-chips{
    display:none !important;
  }

  .wow-search-filter:not(.is-mobile-expanded) .wow-search-card{
    border-radius:22px;
  }

  .wow-search-filter.is-mobile-expanded .wow-search-card{
    border-radius:26px;
    box-shadow:0 24px 70px rgba(16,24,40,.22);
  }

  .wow-search-filter.is-mobile-expanded .wow-search-bottom-row{
    display:grid;
    width:calc(100% - 24px);
    margin:0 12px;
  }

  .wow-search-filter.is-mobile-expanded .wow-desktop-map-controls{
    display:none !important;
  }

  .wow-search-filter.is-mobile-expanded{
    position:fixed;
    top:65px;
    left:0;
    right:0;
    width:100vw;
    max-width:100vw;
    height:calc(100dvh - 65px);
    max-height:none;
    z-index:1950;
    padding:12px 0 14px;
    overflow-y:auto;
    overflow-x:hidden;
    overscroll-behavior:contain;
    -webkit-overflow-scrolling:touch;
    background:none;
    backdrop-filter:none;
    -webkit-backdrop-filter:none;
    box-shadow:none;
  }

  .wow-search-filter.is-mobile-expanded .wow-search-card{
    width:calc(100% - 26px);
    max-width:none;
    margin:0 12px;
  }

  .wow-search-filter.is-mobile-expanded .wow-search-bottom-row{
    width:calc(100% - 24px);
    margin:0 12px;
  }
}

@media (max-width: 1040px){
  .wow-search-filter{
    position:fixed;
    top:82px;
    left:14px;
    right:14px;
    z-index:1950;
    width:auto;
    max-width:none;
    margin:0;
  }

  .wow-search-filter.is-panel-open{
    z-index:1950;
  }

  .wow-panel--what{
    position:static !important;
    top:auto !important;
    right:auto !important;
    bottom:auto !important;
    left:auto !important;
    inset:auto !important;
    width:100% !important;
    max-width:none;
    grid-column:1 / -1;
    margin:0 0 6px;
    border:1px solid #dbe2ea;
    border-radius:22px;
    background:#fff;
    box-shadow:0 14px 34px rgba(16,24,40,.08);
    overflow:hidden;
    display:none;
    opacity:1;
    visibility:visible;
    pointer-events:auto;
    transform:none;
  }

  .wow-search-filter.is-mobile-expanded .wow-panel--where,
  .wow-search-filter.is-mobile-expanded .wow-panel--calendar,
  .wow-search-filter.is-mobile-expanded .wow-panel--who{
    position:static !important;
    top:auto !important;
    right:auto !important;
    bottom:auto !important;
    left:auto !important;
    inset:auto !important;
    width:100% !important;
    max-width:none;
    grid-column:1 / -1;
    margin:0 0 6px;
    border:1px solid #dbe2ea;
    border-radius:22px;
    background:#fff;
    box-shadow:0 14px 34px rgba(16,24,40,.08);
    overflow:hidden;
    opacity:1;
    visibility:visible;
    pointer-events:auto;
    transform:none;
  }

  .wow-panel--what.is-open{
    display:block;
    border:1px solid #dbe2ea;
  }

  .wow-search-filter.is-mobile-expanded .wow-panel--where.is-open,
  .wow-search-filter.is-mobile-expanded .wow-panel--calendar.is-open,
  .wow-search-filter.is-mobile-expanded .wow-panel--who.is-open{
    display:block;
  }

  .wow-panel--what .wow-panel-inner{
    padding:16px;
  }

  .wow-search-filter.is-mobile-expanded .wow-panel--where .wow-panel-inner,
  .wow-search-filter.is-mobile-expanded .wow-panel--calendar .wow-panel-inner,
  .wow-search-filter.is-mobile-expanded .wow-panel--who .wow-panel-inner{
    padding:16px;
  }

  .wow-panel--what .wow-panel-head{
    margin-bottom:12px;
  }

  .wow-search-filter.is-mobile-expanded .wow-panel--where .wow-panel-head,
  .wow-search-filter.is-mobile-expanded .wow-panel--calendar .wow-panel-head,
  .wow-search-filter.is-mobile-expanded .wow-panel--who .wow-panel-head{
    margin-bottom:12px;
  }

  .wow-panel--what .wow-location-list{
    display:grid;
    gap:8px;
  }

  .wow-search-filter.is-mobile-expanded .wow-panel--where .wow-location-list,
  .wow-search-filter.is-mobile-expanded .wow-panel--calendar .wow-location-list,
  .wow-search-filter.is-mobile-expanded .wow-panel--who .wow-location-list{
    display:grid;
    gap:8px;
  }

  .wow-panel--what .wow-panel-empty{
    padding-top:12px;
  }

  .wow-search-filter.is-mobile-expanded .wow-panel--where .wow-panel-empty,
  .wow-search-filter.is-mobile-expanded .wow-panel--calendar .wow-panel-empty,
  .wow-search-filter.is-mobile-expanded .wow-panel--who .wow-panel-empty{
    padding-top:12px;
  }
}

@media (prefers-reduced-motion: reduce){
  *,
  *::before,
  *::after{
    animation-duration:.001ms !important;
    transition-duration:.001ms !important;
    scroll-behavior:auto !important;
  }
}

@media (min-width: 1041px){
  .wow-search-filter{
    top:calc(var(--wow-header-offset, 0px) + 12px);
    z-index:1950;
    width:min(1234px, calc(100vw - 32px));
    max-width:none;
    padding:8px;
    border:0;
    border-radius:28px;
    background:none;
    box-shadow:none;
    backdrop-filter:none;
    -webkit-backdrop-filter:none;
    transition:width 320ms cubic-bezier(.25,.46,.45,.94), padding 220ms ease, border-radius 220ms ease, transform 220ms ease;
    will-change:width;
  }

  .wow-search-filter.is-static-layout{
    position:relative !important;
    top:auto !important;
    left:auto !important;
    right:auto !important;
    bottom:auto !important;
    transform:none !important;
    width:100% !important;
    max-width:none !important;
    padding:0 !important;
    border:0 !important;
    background:none !important;
    box-shadow:none !important;
    backdrop-filter:none !important;
    -webkit-backdrop-filter:none !important;
    z-index:1 !important;
    overflow:visible !important;
  }

  .wow-search-filter.is-static-layout .wow-search-card{
    box-shadow:0 16px 42px rgba(16,24,40,.075);
    border:1px solid rgba(207,215,227,.96);
    background:rgba(255,255,255,.97);
  }

  .wow-search-filter.is-static-layout .wow-search-main{
    overflow:visible;
  }

  .wow-search-filter.is-scroll-collapsed{
    width:calc(min(1234px, calc(100vw - 30px)) + 2px);
    padding:10px 8px;
    border:1px solid #ccc;
    border-top:1px solid #ddd;
    border-bottom:1px solid #aaa;
    border-radius:28px;
    background:rgba(255,255,255,.5);
    box-shadow:0 20px 56px #10182824;
    backdrop-filter:blur(5px);
    -webkit-backdrop-filter:blur(5px);
  }

  .wow-search-filter.is-scroll-collapsed::after,
  .wow-search-filter.is-scroll-collapsed.is-filter-open::after{
    content:"";
    position:absolute;
    inset:0;
    border-radius:inherit;
    pointer-events:none;
    background:rgba(255,255,255,.18);
    backdrop-filter:blur(1px);
    -webkit-backdrop-filter:blur(1px);
    z-index:0;
  }

  .wow-search-filter.is-scroll-collapsed .wow-search-card,
  .wow-search-filter.is-scroll-collapsed.is-filter-open .wow-search-card,
  .wow-search-filter.is-scroll-collapsed > *{
    position:relative;
    z-index:1;
  }

  .wow-search-filter.is-scroll-collapsed.is-filter-open{
    width:calc(min(1234px, calc(100vw - 30px)) + 2px);
    padding:10px 8px;
    border:1px solid #ccc;
    border-top:1px solid #ddd;
    border-bottom:1px solid #aaa;
    border-radius:28px;
    background:rgba(255,255,255,.5);
    box-shadow:0 20px 56px #10182824;
    backdrop-filter:blur(5px);
    -webkit-backdrop-filter:blur(5px);
  }

  .wow-search-filter.is-scroll-collapsed .wow-search-card,
  .wow-search-filter.is-scroll-collapsed.is-filter-open .wow-search-card{
    box-shadow:var(--wow-shadow);
    border:1px solid #ddd;
  }

  .wow-search-filter.is-panel-open .wow-search-card{
    background:#eee;
  }

  .wow-search-filter.is-panel-open{
    z-index:1950;
  }

  .wow-search-card,
  .wow-search-bottom-row,
  .wow-search-top-row{
    width:100%;
  }

  .wow-search-top-row{
    padding:0 6px;
  }

  .wow-search-main{
    grid-template-columns:minmax(140px, 1fr) minmax(140px, 1fr) minmax(140px, 1fr) minmax(140px, 1fr) 58px;
  }

  .wow-search-filter.is-scroll-collapsed .wow-search-top-row{
    display:flex;
    justify-content:space-between;
    margin-bottom:10px;
    padding:0 6px;
  }

  .wow-search-main > .wow-segment,
  .wow-segment{
    padding-left:11px;
    padding-right:11px;
    gap:7px;
  }

  .wow-submit{
    min-width:58px;
  }

  .wow-search-filter.is-force-mobile-layout{
    position:relative !important;
    top:100px !important;
    left:auto !important;
    right:auto !important;
    bottom:auto !important;
    transform:none !important;
    width:100% !important;
    max-width:none !important;
    height:auto !important;
    max-height:none !important;
    padding:0 24px !important;
    margin:0 !important;
    background:transparent !important;
    border:0 !important;
    box-shadow:none !important;
    backdrop-filter:none !important;
    -webkit-backdrop-filter:none !important;
    overflow:visible !important;
  }

  .wow-search-filter.is-static-layout.is-mobile-expanded.is-force-mobile-layout.is-panel-open{
    padding:0 24px !important;
    top:100px !important;
  }

  .wow-search-filter.is-force-mobile-layout .wow-search-top-row{
    display:none !important;
  }

  .wow-search-filter.is-force-mobile-layout .wow-search-card{
    width:100% !important;
    max-width:none !important;
    height:auto !important;
    margin:0 !important;
    padding:7px !important;
    border-radius:26px !important;
    box-shadow:0 24px 70px rgba(16,24,40,.22) !important;
    overflow:visible !important;
    background:#fff !important;
  }

  .wow-search-filter.is-force-mobile-layout .wow-search-main{
    grid-template-columns:1fr !important;
    gap:6px !important;
    min-height:auto !important;
  }

  .wow-search-filter.is-force-mobile-layout .wow-segment{
    min-height:66px;
    border-radius:18px;
    grid-template-columns:32px minmax(0, 1fr);
    gap:8px;
    padding:0 11px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-segment .wow-icon{
    width:32px;
    height:32px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-segment .wow-icon i{
    font-size:18px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-label{
    font-size:12px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-copy input,
  .wow-search-filter.is-force-mobile-layout .wow-value{
    font-size:15px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-copy{
    align-self:stretch;
    display:flex;
    flex-direction:column;
    justify-content:center;
    height:66px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-panel--what,
  .wow-search-filter.is-force-mobile-layout .wow-panel--where,
  .wow-search-filter.is-force-mobile-layout .wow-panel--calendar,
  .wow-search-filter.is-force-mobile-layout .wow-panel--who{
    position:static !important;
    top:auto !important;
    right:auto !important;
    bottom:auto !important;
    left:auto !important;
    inset:auto !important;
    width:100% !important;
    max-width:none !important;
    grid-column:1 / -1;
    margin:0 0 6px;
    border:1px solid #dbe2ea;
    border-radius:22px;
    background:#fff;
    box-shadow:0 14px 34px rgba(16,24,40,.08);
    overflow:hidden;
    opacity:1;
    visibility:visible;
    pointer-events:auto;
    transform:none;
  }

  .wow-search-filter.is-force-mobile-layout .wow-panel--what.is-open,
  .wow-search-filter.is-force-mobile-layout .wow-panel--where.is-open,
  .wow-search-filter.is-force-mobile-layout .wow-panel--calendar.is-open,
  .wow-search-filter.is-force-mobile-layout .wow-panel--who.is-open{
    display:block;
  }

  .wow-search-filter.is-force-mobile-layout .wow-panel--what .wow-panel-inner,
  .wow-search-filter.is-force-mobile-layout .wow-panel--where .wow-panel-inner,
  .wow-search-filter.is-force-mobile-layout .wow-panel--calendar .wow-panel-inner,
  .wow-search-filter.is-force-mobile-layout .wow-panel--who .wow-panel-inner{
    padding:16px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-panel--what .wow-panel-head,
  .wow-search-filter.is-force-mobile-layout .wow-panel--where .wow-panel-head,
  .wow-search-filter.is-force-mobile-layout .wow-panel--calendar .wow-panel-head,
  .wow-search-filter.is-force-mobile-layout .wow-panel--who .wow-panel-head{
    margin-bottom:12px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-panel--what .wow-location-list,
  .wow-search-filter.is-force-mobile-layout .wow-panel--where .wow-location-list,
  .wow-search-filter.is-force-mobile-layout .wow-panel--calendar .wow-location-list,
  .wow-search-filter.is-force-mobile-layout .wow-panel--who .wow-location-list{
    display:grid;
    gap:8px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-panel--what .wow-panel-empty,
  .wow-search-filter.is-force-mobile-layout .wow-panel--where .wow-panel-empty,
  .wow-search-filter.is-force-mobile-layout .wow-panel--calendar .wow-panel-empty,
  .wow-search-filter.is-force-mobile-layout .wow-panel--who .wow-panel-empty{
    padding-top:12px;
  }

  .wow-search-filter.is-force-mobile-layout .wow-panel-actions{
    display:none;
  }

  .wow-search-filter.is-force-mobile-layout .wow-submit{
    min-height:58px;
    border-radius:999px;
    width:100%;
    font-size:0;
  }
}
</style>
