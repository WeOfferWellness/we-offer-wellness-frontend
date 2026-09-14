let locationsCatalogPromise = null
let locationsCatalogCache = null

function normalizeText(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[\u2018\u2019\u201c\u201d]/g, "'")
    .replace(/[^a-z0-9]+/g, ' ')
    .trim()
}

function slugify(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function isStreetLike(item) {
  const title = normalizeText(item?.title || item?.label || item?.slug || '')
  const source = normalizeText([item?.source, ...(item?.locations || []), item?.city, item?.town, item?.county, item?.region].filter(Boolean).join(' '))
  if (!title) return true
  return /(\broad\b|\bstreet\b|\blane\b|\bdrive\b|\bclose\b|\bavenue\b|\bway\b|\bpath\b|\bcrescent\b|\bgardens?\b|\bcourt\b|\bplace\b|\bterrace\b|\brow\b|\bhill\b|\bpark\b|\bmews\b|\bwalk\b|\brise\b|\bgreen\b|\bgrove\b|\bvale\b|\bsquare\b|\bgate\b|\bbottom\b|\bend\b|\bcommon\b|\bcorner\b|\bbridge\b|\bview\b|\bglen\b|\bfields\b|\bquay\b|\bwharf\b|\btrack\b|\brd\b|\bst\b|\bln\b|\bdr\b|\bave\b|\bwy\b|\bpl\b|\bter\b|\bct\b)/.test(source) || /(\broad\b|\bstreet\b|\blane\b|\bdrive\b|\bclose\b|\bavenue\b|\bway\b|\bpath\b|\bcrescent\b|\bgardens?\b|\bcourt\b|\bplace\b|\bterrace\b|\brow\b|\bhill\b|\bpark\b|\bmews\b|\bwalk\b|\brise\b|\bgreen\b|\bgrove\b|\bvale\b|\bsquare\b|\bgate\b|\bbottom\b|\bend\b|\bcommon\b|\bcorner\b|\bbridge\b|\bview\b|\bglen\b|\bfields\b|\bquay\b|\bwharf\b|\btrack\b|\brd\b|\bst\b|\bln\b|\bdr\b|\bave\b|\bwy\b|\bpl\b|\bter\b|\bct\b)/.test(title)
}

function buildLocationItems(payload, search = '', limit = 12) {
  const query = normalizeText(search)
  const source = Array.isArray(payload?.flat) && payload.flat.length
    ? payload.flat
    : (Array.isArray(payload?.suggestions) ? payload.suggestions : [])

  const seen = new Set()
  const items = source
    .map((item) => {
      const title = String(item?.title || item?.label || item?.slug || '').trim()
      const country = String(item?.country || '').trim()
      const county = String(item?.county || item?.district || item?.region || '').trim()
      const total = Number(item?.counts?.total || item?.counts?.products || item?.counts?.offerings || 0)
      const value = title || String(item?.place_name || '').trim() || String(item?.slug || '').trim()
      const slug = slugify(item?.slug || title || value)
      return {
        title: title || value,
        label: title || value,
        subtitle: item?.online ? 'Virtual' : [county, country].filter(Boolean).join(', '),
        value,
        icon: item?.online ? 'wifi' : 'geo',
        slug,
        country,
        county,
        district: county,
        region: String(item?.region || '').trim(),
        search: [title, country, county, item?.label, item?.place_name, slug].filter(Boolean).join(' '),
        online: !!item?.online,
        total,
      }
    })
    .filter((item) => !isStreetLike(item))
    .filter((item) => {
      if (!item.value) return false
      const key = normalizeText(item.value)
      if (!key || seen.has(key)) return false
      seen.add(key)
      return true
    })
    .sort((a, b) => {
      if (a.online !== b.online) return a.online ? -1 : 1
      if (a.total !== b.total) return b.total - a.total
      return a.title.localeCompare(b.title)
    })

  const filtered = query
    ? items.filter((item) => {
        const hay = normalizeText(item.search)
        const title = normalizeText(item.title)
        return title === query || title.includes(query) || hay.includes(query)
      })
    : items

  const maxItems = Math.max(5, Number(limit) || 12)
  const sliced = filtered.slice(0, maxItems)
  if (!sliced.some((item) => item.online)) {
    sliced.unshift({ label: 'Online', subtitle: 'Virtual', value: 'Online', icon: 'wifi', title: 'Online', slug: 'online', online: true })
  } else {
    const idx = sliced.findIndex((item) => item.online)
    if (idx > 0) {
      const [online] = sliced.splice(idx, 1)
      sliced.unshift(online)
    }
  }
  return sliced
}

async function loadLocationsCatalog() {
  if (locationsCatalogCache) return locationsCatalogCache
  if (!locationsCatalogPromise) {
    locationsCatalogPromise = fetch('/cache/locations.json', { cache: 'no-store' })
      .then((res) => {
        if (!res.ok) throw new Error(`locations catalog ${res.status}`)
        return res.json()
      })
      .then((json) => {
        locationsCatalogCache = json || {}
        return locationsCatalogCache
      })
      .catch((error) => {
        console.warn('[locations] catalog failed', error)
        locationsCatalogCache = {}
        return locationsCatalogCache
      })
  }
  return locationsCatalogPromise
}

export async function fetchLocations(limit = 12, search = '') {
  try {
    const catalog = await loadLocationsCatalog()
    return buildLocationItems(catalog, search, limit)
  } catch (e) {
    console.warn('[locations] failed', e)
    return []
  }
}
