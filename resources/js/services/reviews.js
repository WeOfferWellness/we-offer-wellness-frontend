const cache = new Map()
const backendUrl = String(import.meta.env.VITE_BACKEND_URL || '').replace(/\/$/, '')

export async function fetchProductReviewSummary(productId) {
  const id = Number(productId)
  if (!Number.isFinite(id) || id <= 0) return { rating: null, review_count: 0, counts: {} }
  if (cache.has(id)) return cache.get(id)
  const p = (async () => {
    try {
      const endpoint = backendUrl
        ? `${backendUrl}/api/products/${id}/reviews/summary`
        : `/api/products/${id}/reviews/summary`
      const res = await fetch(endpoint, {
        cache: 'no-store',
        credentials: 'include',
        headers: { Accept: 'application/json' },
      })
      if (!res.ok) throw new Error(String(res.status))
      const data = await res.json()
      return {
        rating: typeof data?.rating === 'number' ? data.rating : null,
        review_count: Number(data?.review_count || 0),
        counts: data?.counts || {},
      }
    } catch (e) {
      console.warn('[reviews] summary failed', id, e)
      return { rating: null, review_count: 0, counts: {} }
    }
  })()
  cache.set(id, p)
  const out = await p
  cache.set(id, out)
  return out
}

export async function fetchFeaturedReviews(params = {}) {
  const query = new URLSearchParams()
  if (params.limit) query.set('limit', params.limit)
  if (params.minRating) query.set('min_rating', params.minRating)
  const qs = query.toString()
  const url = `${backendUrl || ''}/api/reviews/featured${qs ? `?${qs}` : ''}`
  try {
    const res = await fetch(url, {
      cache: 'no-store',
      credentials: 'include',
      headers: { Accept: 'application/json' },
    })
    if (!res.ok) throw new Error(String(res.status))
    const data = await res.json()
    return Array.isArray(data?.data) ? data.data : []
  } catch (e) {
    console.warn('[reviews] featured failed', e)
    return []
  }
}
