// Helpers to normalise visibility logic without over-filtering when flags are absent
const toStr = (v) => typeof v === 'string' ? v.toLowerCase() : ''
const isTrue = (v) => v === true || v === 1 || v === '1' || toStr(v) === 'true' || toStr(v) === 'yes'
const NO_IMAGE_MARKERS = ['no-product-image.jpg', '/images/placeholder.png', 'placeholder.png']

function hasDisplayableImageUrl(value) {
  const url = String(value || '').trim()
  if (!url) return false
  const lower = url.toLowerCase()
  return !NO_IMAGE_MARKERS.some((marker) => lower.includes(marker))
}

function inferLive(p) {
  if (p == null || typeof p !== 'object') return null
  if ('live' in p) return isTrue(p.live)
  if ('is_live' in p) return isTrue(p.is_live)
  if ('published' in p) return isTrue(p.published)
  if ('is_published' in p) return isTrue(p.is_published)
  if ('active' in p) return isTrue(p.active)
  if ('is_active' in p) return isTrue(p.is_active)
  if ('status' in p) {
    const s = toStr(p.status)
    if (['live', 'active', 'published', 'approved'].includes(s)) return true
    if (['draft', 'pending', 'inactive', 'archived', 'disabled', 'unapproved', 'hidden'].includes(s)) return false
  }
  return null
}

function inferApproved(p) {
  if (p == null || typeof p !== 'object') return null
  if ('approved' in p) return isTrue(p.approved)
  if ('is_approved' in p) return isTrue(p.is_approved)
  if ('approval_status' in p) {
    const s = toStr(p.approval_status)
    if (s === 'approved') return true
    if (['pending', 'rejected'].includes(s)) return false
  }
  if ('status_approval' in p) {
    const s = toStr(p.status_approval)
    if (s === 'approved') return true
    if (['pending', 'rejected'].includes(s)) return false
  }
  return null
}

function pricePositive(p) {
  // Consider price_min and variant aggregates
  const candidates = [p?.price, p?.price_min, p?.variants_min_price]
  let n = candidates.map((v) => Number(v)).find((x) => Number.isFinite(x) && x > 0)
  if (n == null && Array.isArray(p?.variants)) {
    const vn = p.variants.map(v => Number(v?.price)).filter(x => Number.isFinite(x) && x > 0)
    if (vn.length) n = Math.min(...vn)
  }
  return Number.isFinite(n) && n > 0
}

function productVisible(p) {
  // Show broadly; only hide if we can explicitly tell it's not live/approved.
  const live = inferLive(p)
  const approved = inferApproved(p)
  if (live === false) return false
  if (approved === false) return false
  // Physical Store products can be listed before an image has been uploaded;
  // their V4.1 card has an intentional fallback tile.
  if (p?.kind === 'physical_product' || p?.source_type === 'physical_product') return true
  const imageCandidates = [
    p?.image,
    p?.image_url,
    p?.featured_image,
    p?.media?.[0]?.url,
    p?.media?.[0]?.original_url,
    p?.media?.[0]?.path,
  ]
  if (!imageCandidates.some(hasDisplayableImageUrl)) return false
  return true
}

export async function fetchProducts(params = {}, options = {}) {
  const opts = typeof options === 'object' && options !== null ? options : {}
  const qs = new URLSearchParams(params).toString();
  const backendUrl = String(import.meta.env.VITE_BACKEND_URL || '').replace(/\/$/, '')
  const candidates = [
    import.meta.env.VITE_OFFERINGS_URL || `${backendUrl}/api/behaviour/offerings`,
  ]

  if (import.meta.env.VITE_PRODUCTS_URL) {
    candidates.push(import.meta.env.VITE_PRODUCTS_URL)
  }

  let lastError = null

  for (const base of candidates) {
    const url = qs ? `${base}?${qs}` : base
    try {
      const res = await fetch(url, { cache: 'no-store', credentials: 'include', headers: { Accept: 'application/json' } })
      if (!res.ok) throw new Error(`Failed to load products: ${res.status}`)
      const data = await res.json()
      const list = Array.isArray(data)
        ? data
        : (Array.isArray(data?.data)
          ? data.data
          : (Array.isArray(data?.items)
            ? data.items
            : (Array.isArray(data?.products) ? data.products : (Array.isArray(data?.offerings) ? data.offerings : []))))
      const filtered = list.filter(productVisible)
      return sortFavorability(filtered.map((item) => ({
        ...item,
        image: item?.image || item?.image_url || null,
      })), params?.sort)
    } catch (e) {
      lastError = e
    }
  }

  console.warn('[products] fallback to empty list', lastError)
  if (opts.throwOnError) throw lastError
  return []
}

export function byTag(products, tag) {
  return products.filter((p) => (p.tags || []).includes(tag));
}

export function underPrice(products, limit) {
  // Strictly under the limit
  return products.filter((p) => Number(p.price) < limit);
}

export function sortByRating(products) {
  return [...products].sort((a, b) => (b.rating || 0) - (a.rating || 0));
}

export function sortByNewest(products) {
  return [...products].sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
}

// Advanced favorability ranking: rating × log(1+reviews)
function favorabilityScore(p) {
  const r = Number(p?.rating) || 0; // expected 0–5
  const c = Number(p?.review_count) || 0;
  // normalise rating to 0–1 and weight by review volume
  const rr = Math.max(0, Math.min(1, r / 5));
  const vol = Math.log1p(c); // diminishing returns
  return rr * vol;
}

export function sortFavorability(products, sortParam) {
  // Only apply this when explicit or when default "popular" requested.
  const want = !sortParam || sortParam === 'popular' || sortParam === 'favourability' || sortParam === 'favorability';
  if (!want) return products;
  return [...products].sort((a, b) => {
    const sa = favorabilityScore(a), sb = favorabilityScore(b);
    if (sb !== sa) return sb - sa;
    // tie-breakers: higher rating, then more reviews, then newest
    const ra = Number(a?.rating) || 0, rb = Number(b?.rating) || 0;
    if (rb !== ra) return rb - ra;
    const ca = Number(a?.review_count) || 0, cb = Number(b?.review_count) || 0;
    if (cb !== ca) return cb - ca;
    const da = new Date(a?.created_at || 0).getTime();
    const db = new Date(b?.created_at || 0).getTime();
    return db - da;
  });
}
