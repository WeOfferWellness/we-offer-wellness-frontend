const backendUrl = String(import.meta.env.VITE_BACKEND_URL || '').replace(/\/$/, '')

export async function fetchOfferingAndPractitionerSuggestions(query, limit = 6) {
  const needle = String(query || '').trim()
  if (needle.length < 2) return []

  try {
    const params = new URLSearchParams({
      what: needle,
      limit: String(Math.max(12, limit * 4)),
    })
    const response = await fetch('/api/products?' + params.toString(), {
      cache: 'no-store',
      headers: { Accept: 'application/json' },
    })
    if (!response.ok) throw new Error('Failed to load practitioner suggestions: ' + response.status)

    const payload = await response.json()
    const items = Array.isArray(payload) ? payload : []
    const seenOfferings = new Set()
    const offerings = items
      .map((item) => {
        const title = String(item?.title || '').trim()
        const key = title.toLocaleLowerCase()
        if (!key || seenOfferings.has(key)) return null
        seenOfferings.add(key)
        return {
          cat: 'Offering',
          title,
          label: title,
          value: title,
          type: 'Offering',
          subtitle: 'Offering',
          slug: '',
          search: [title, item?.vendor_name].filter(Boolean).join(' '),
          counts: { products: 0, offerings: 1, total: 1 },
        }
      })
      .filter(Boolean)
      .slice(0, limit)

    const seenPractitioners = new Set()
    const practitioners = items
      .map((item) => String(item?.vendor_name || '').trim())
      .filter((name) => {
        const key = name.toLocaleLowerCase()
        if (!key || seenPractitioners.has(key)) return false
        seenPractitioners.add(key)
        return true
      })
      .slice(0, limit)
      .map((name) => ({
        cat: 'Practitioner',
        title: name,
        label: name,
        value: name,
        type: 'Practitioner',
        subtitle: 'Practitioner',
        slug: '',
        search: name,
        counts: { products: 0, offerings: 0, total: 0 },
      }))
    return [...offerings, ...practitioners]
  } catch (error) {
    console.warn('[what-categories] practitioner suggestions unavailable', error)
    return []
  }
}

export const fetchPractitionerSuggestions = fetchOfferingAndPractitionerSuggestions

async function fetchPopularSearches() {
  if (!backendUrl) return []

  try {
    const response = await fetch(`${backendUrl}/api/search-events/popular`, {
      cache: 'no-store',
      credentials: 'include',
      headers: { Accept: 'application/json' },
    })
    if (!response.ok) throw new Error(`Failed to load popular searches: ${response.status}`)

    const payload = await response.json()
    if (!payload?.enabled || !Array.isArray(payload.terms)) return []

    return payload.terms
      .map((item) => {
        const title = String(item?.term || '').trim()
        const searches = Number(item?.searches || 0)
        return {
          cat: 'Popular searches',
          title,
          label: title,
          value: title,
          type: 'Popular search',
          subtitle: searches ? `${searches.toLocaleString()} searches` : 'Popular search',
          slug: '',
          search: title,
          counts: { products: 0, offerings: 0, total: searches },
        }
      })
      .filter((item) => item.title)
  } catch (error) {
    console.warn('[what-categories] popular searches unavailable', error)
    return []
  }
}

export async function fetchWhatCategories() {
  try {
    const [res, popularSearches] = await Promise.all([
      fetch('/cache/what-categories.json', { cache: 'no-store' }),
      fetchPopularSearches(),
    ])
    if (!res.ok) throw new Error(`Failed to load what categories: ${res.status}`)

    const payload = await res.json()
    const categories = Array.isArray(payload?.categories) ? payload.categories : []

    const categoryItems = categories
      .map((item) => {
        const title = String(item?.title || item?.label || item?.value || '').trim()
        const value = String(item?.value || title).trim()
        const counts = item?.counts || {}
        const products = Number(counts?.products || 0)
        const offerings = Number(counts?.offerings || 0)
        const total = Number(counts?.total || products + offerings || 0)

        return {
          cat: String(item?.cat || 'Modalities').trim() || 'Modalities',
          title,
          label: title,
          value,
          type: String(item?.type || 'Modality').trim() || 'Modality',
          subtitle: String(item?.subtitle || '').trim().replace(/\bproducts?\b/gi, 'offerings'),
          slug: String(item?.slug || '').trim(),
          search: String(item?.search || `${title} ${item?.slug || ''}`).trim(),
          counts: { products, offerings, total },
        }
      })
      .filter((item) => item.title)
      .sort((a, b) => {
        const at = Number(a?.counts?.total || 0)
        const bt = Number(b?.counts?.total || 0)
        if (at !== bt) return bt - at
        return String(a.title).localeCompare(String(b.title))
      })

    const seen = new Set()
    return [...popularSearches, ...categoryItems].filter((item) => {
      const key = item.title.toLocaleLowerCase()
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
  } catch (error) {
    console.warn('[what-categories] failed', error)
    return []
  }
}
