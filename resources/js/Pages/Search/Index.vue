<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import SiteLayout from '@/Layouts/SiteLayout.vue'
import UltraSearchBar from '@/Components/UltraSearchBar.vue'
import ProductCard from '@/Components/ProductCard.vue'
import MapPanel from '@/Components/MapPanel.vue'
import { fetchProducts } from '@/services/products'
import { reportSearchResults } from '@/services/searchAnalytics'
import {
  canonicalUrl,
  pageKeywords,
  shortOgDescription,
  shortOgTitle,
} from '@/lib/seo-meta'

const props = defineProps({
  mapsKey: { type: String, required: true },
})

const products = ref([])
const loading = ref(false)
const loadError = ref(false)
const filters = ref(paramsFromUrl())
const view = ref('list-map') // 'list' | 'list-map'
const userLoc = ref(readUserLocation())
const mobileQuery = '(max-width: 767.98px)'
const isMobile = ref(typeof window !== 'undefined' ? window.matchMedia(mobileQuery).matches : false)

function syncViewportState() {
  if (typeof window === 'undefined') return
  try {
    isMobile.value = window.matchMedia(mobileQuery).matches
  } catch {
    isMobile.value = false
  }
}

const showMap = computed(() => view.value === 'list-map' && !isMobile.value)
const showMapControls = computed(() => !isMobile.value)
const showMobileMap = computed(() => isMobile.value)

function paramsFromUrl() {
  const u = new URLSearchParams(window.location.search || '')
  const known = ['what','where','when','flexible','adults','group_type','sort','price_max','price_min','mode','type','tag']
  const out = {}
  known.forEach(k => { const v = u.get(k); if (v) out[k] = v })
  return out
}

function readUserLocation() {
  try {
    const get = (n) => (document.cookie.match('(^|;)\\s*'+n+'\\s*=\\s*([^;]+)')||[]).pop()
    const lat = parseFloat(decodeURIComponent(get('wow_lat')||''))
    const lng = parseFloat(decodeURIComponent(get('wow_lng')||''))
    if (Number.isFinite(lat) && Number.isFinite(lng)) return { lat, lng }
  } catch {}
  return null
}

async function load() {
  loading.value = true
  loadError.value = false
  filters.value = paramsFromUrl()
  try {
    products.value = await fetchProducts(filters.value, { throwOnError: true })
    reportSearchResults(products.value.length)
  } catch (error) {
    console.error('[search] load failed', error)
    loadError.value = true
    products.value = []
  } finally {
    loading.value = false
  }
}

const resultCount = computed(() => products.value.length)
const headline = computed(() => {
  const term = filters.value?.what
  return term ? `“${term}”` : 'all experiences'
})
const searchTitle = computed(() => 'Search Wellness Sessions')
const canonical = computed(() => canonicalUrl('/search'))
const ogTitle = computed(() => shortOgTitle(`${searchTitle.value} | WOW®`))
const ogDesc = computed(() => shortOgDescription('Find therapies, classes and events that match how you feel.'))
const keywords = computed(() => pageKeywords({
  type: filters.value?.type || 'search',
  extra: [
    filters.value?.what || '',
    filters.value?.where || '',
    filters.value?.mode === 'online' ? 'online sessions' : '',
    filters.value?.type || '',
    filters.value?.tag || '',
    'search therapies',
    'search classes',
    'search events',
  ],
}))
const filterTags = computed(() => {
  const tags = []
  if (filters.value?.where) tags.push(filters.value.where)
  if (filters.value?.mode) tags.push(filters.value.mode === 'online' ? 'Online only' : 'In person')
  if (filters.value?.type) tags.push(filters.value.type)
  if (filters.value?.tag) tags.push(`#${filters.value.tag}`)
  if (filters.value?.price_max) tags.push(`Under £${filters.value.price_max}`)
  if (filters.value?.flexible) tags.push('Flexible dates')
  return tags
})

function handlePopstate() {
  load()
}

onMounted(() => {
  syncViewportState()
  window.addEventListener('resize', syncViewportState, { passive: true })
  load()
  window.addEventListener('popstate', handlePopstate)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', syncViewportState)
  window.removeEventListener('popstate', handlePopstate)
})
</script>

<template>
  <Head :title="searchTitle">
    <meta name="robots" content="noindex,follow" />
    <meta name="keywords" :content="keywords.join(', ')" />
    <link rel="canonical" :href="canonical" />
    <meta property="og:title" :content="ogTitle" />
    <meta property="og:description" :content="ogDesc" />
    <meta property="og:url" :content="canonical" />
    <meta name="twitter:site" content="@weofferwellness" />
    <meta name="twitter:creator" content="@weofferwellness" />
    <meta name="twitter:title" :content="ogTitle" />
    <meta name="twitter:description" :content="ogDesc" />
  </Head>
  <SiteLayout>
    <div class="wow-search-results-page">
      <section class="wow-search-results-top">
        <div class="container-page">
          <UltraSearchBar id-prefix="search-top" :compact="true" :mobile-top-offset="80" />
        </div>
      </section>

      <section v-if="isMobile" class="wow-search-results-mobile" aria-label="Search results">
        <div v-if="showMobileMap" class="wow-search-results-mobile-map" aria-hidden="true">
          <MapPanel :api-key="props.mapsKey" :products="products" :user-location="userLoc" />
        </div>

        <div class="container-page wow-search-results-mobile-content">
          <div class="wow-search-results-heading wow-search-results-heading--mobile">
            <div class="kicker mb-1 text-ink-600 uppercase tracking-[0.2em]">Search results</div>
            <h1 class="wow-search-results-title">{{ resultCount }} therapies for {{ headline }}</h1>
          </div>

          <div v-if="filterTags.length" class="wow-search-results-tags wow-search-results-tags--mobile" aria-label="Applied filters">
            <span v-for="tag in filterTags" :key="tag" class="chip">{{ tag }}</span>
          </div>

          <div class="wow-search-results-list">
            <div v-if="loading" class="wow-search-results-state">Loading results…</div>
            <div v-else-if="loadError" class="wow-search-results-state">
              We’re having trouble loading results right now. Please refresh or adjust your filters.
            </div>
            <div v-else-if="products.length === 0" class="wow-search-results-state">
              No results matched your filters. Try widening your search.
            </div>
            <ProductCard v-else v-for="(p, i) in products" :key="p.id ?? i" :product="p" :fluid="true" />
          </div>
        </div>
      </section>

      <div v-else class="wow-search-results-stage" :class="{ 'is-list-only': !showMap }">
        <div v-if="showMap" class="wow-search-results-map" aria-hidden="true">
          <MapPanel :api-key="props.mapsKey" :products="products" :user-location="userLoc" />
        </div>

        <section class="wow-search-results-overlay" aria-label="Search results">
          <div class="container-page wow-search-results-content">
            <div class="wow-search-results-hero">
              <div class="wow-search-results-heading">
                <div class="kicker mb-1 text-ink-600 uppercase tracking-[0.2em]">Search results</div>
                <h1 class="wow-search-results-title">{{ resultCount }} therapies for {{ headline }}</h1>
              </div>

              <div class="wow-search-results-hero-tools">
                <div v-if="filterTags.length" class="wow-search-results-tags" aria-label="Applied filters">
                  <span v-for="tag in filterTags" :key="tag" class="chip">{{ tag }}</span>
                </div>

                <div v-if="showMapControls" class="btn-group wow-search-results-toggle" role="group" aria-label="View mode">
                  <button type="button" class="btn-wow btn-wow--ghost is-square btn-sm" :class="{ 'btn-wow--secondary': view==='list' }" @click="view='list'">List</button>
                  <button type="button" class="btn-wow btn-wow--ghost is-square btn-sm" :class="{ 'btn-wow--secondary': view==='list-map' }" @click="view='list-map'">List + Map</button>
                </div>
              </div>
            </div>

            <div v-if="userLoc && showMap" class="wow-search-results-note alert alert-info card p-3">
              Click a pin to calculate your travel time.
            </div>

            <div class="wow-search-results-list">
              <div v-if="loading" class="wow-search-results-state">Loading results…</div>
              <div v-else-if="loadError" class="wow-search-results-state">
                We’re having trouble loading results right now. Please refresh or adjust your filters.
              </div>
              <div v-else-if="products.length === 0" class="wow-search-results-state">
                No results matched your filters. Try widening your search.
              </div>
              <ProductCard v-else v-for="(p, i) in products" :key="p.id ?? i" :product="p" :fluid="true" />
            </div>
          </div>
        </section>
      </div>
    </div>
  </SiteLayout>
</template>

<style>
.wow-search-results-page{
  position:relative;
  isolation:isolate;
  min-height:100vh;
}

.wow-search-results-top{
  position:sticky;
  top:var(--wow-header-offset, 0px);
  z-index:240;
  padding:12px 0 10px;
  background:linear-gradient(180deg, rgba(255,255,255,.96) 0%, rgba(255,255,255,.82) 100%);
  backdrop-filter:blur(16px);
  -webkit-backdrop-filter:blur(16px);
}

.wow-search-results-stage{
  position:relative;
  isolation:isolate;
  min-height:calc(100vh - var(--wow-header-offset, 0px));
}

.wow-search-results-map{
  position:fixed;
  top:var(--wow-header-offset, 0px);
  right:0;
  bottom:0;
  width:min(46vw, 760px);
  z-index:0;
  overflow:hidden;
  box-shadow:inset 1px 0 0 rgba(255,255,255,.72);
}

.wow-search-results-map .maps-panel{
  width:100%;
  height:100%;
  min-height:0;
  border:0;
  border-radius:0;
  box-shadow:none;
}

.wow-search-results-overlay{
  position:relative;
  z-index:2;
  min-height:calc(100vh - var(--wow-header-offset, 0px));
  background:linear-gradient(90deg, rgba(248,250,252,.98) 0%, rgba(248,250,252,.95) 62%, rgba(248,250,252,.42) 100%);
}

.wow-search-results-content{
  padding-top:22px;
  padding-bottom:120px;
  padding-right:min(46vw, 760px);
}

.wow-search-results-hero{
  display:flex;
  flex-wrap:wrap;
  align-items:flex-end;
  justify-content:space-between;
  gap:16px;
}

.wow-search-results-heading{
  min-width:0;
}

.wow-search-results-title{
  margin:0;
  color:#111827;
  font-size:clamp(32px, 4.2vw, 68px);
  font-weight:850;
  line-height:.96;
  letter-spacing:-.065em;
}

.wow-search-results-hero-tools{
  display:flex;
  flex-wrap:wrap;
  align-items:center;
  justify-content:flex-end;
  gap:12px;
}

.wow-search-results-tags{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
}

.wow-search-results-note{
  margin-top:18px;
}

.wow-search-results-list{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
  gap:16px;
  margin-top:18px;
}

.wow-search-results-state{
  grid-column:1 / -1;
  min-height:180px;
  display:flex;
  align-items:center;
  justify-content:center;
  border:1px solid #e5ebf2;
  border-radius:28px;
  background:rgba(255,255,255,.95);
  box-shadow:0 16px 42px rgba(16,24,40,.055);
  padding:clamp(24px, 4vw, 40px);
  color:#667085;
  font-size:18px;
  font-weight:650;
  text-align:center;
}

.wow-search-results-stage.is-list-only .wow-search-results-content{
  padding-right:0;
}

.wow-search-results-stage.is-list-only .wow-search-results-map{
  display:none;
}

.wow-search-results-stage.is-list-only .wow-search-results-overlay{
  background:linear-gradient(180deg, rgba(248,250,252,.98) 0%, rgba(248,250,252,.98) 100%);
}

.wow-search-results-mobile{
  position:relative;
  z-index:2;
  padding-top:260px;
  padding-bottom:80px;
  background:linear-gradient(180deg, rgba(248,250,252,.98) 0%, rgba(248,250,252,.95) 100%);
}

.wow-search-results-mobile-map{
  position:fixed;
  top:calc(var(--wow-header-offset, 0px) + 88px);
  left:0;
  right:0;
  z-index:4;
  padding:0 12px;
}

.wow-search-results-mobile-map :deep(.maps-panel){
  width:100%;
  height:220px;
  border-radius:20px;
}

.wow-search-results-mobile-content{
  padding-top:14px;
  padding-bottom:0;
}

.wow-search-results-heading--mobile{
  margin-bottom:12px;
}

.wow-search-results-tags--mobile{
  margin-bottom:18px;
}

@media (max-width: 1040px){
  .wow-search-results-top{
    padding:10px 0 8px;
  }

  .wow-search-results-overlay{
    background:linear-gradient(180deg, rgba(248,250,252,.96) 0%, rgba(248,250,252,.90) 26%, rgba(248,250,252,.78) 100%);
  }

  .wow-search-results-content{
    padding-right:0;
    padding-top:16px;
  }

  .wow-search-results-hero{
    align-items:flex-start;
  }

  .wow-search-results-title{
    font-size:clamp(26px, 8vw, 38px);
  }

  .wow-search-results-hero-tools{
    width:100%;
    justify-content:flex-start;
  }

  .wow-search-results-list{
    grid-template-columns:1fr;
  }
}

@media (max-width: 767.98px){
  .wow-search-results-stage{
    min-height:auto;
  }

  .wow-search-results-mobile{
    padding-top:244px;
  }

  .wow-search-results-content{
    padding-top:14px;
    padding-bottom:80px;
  }

  .wow-search-results-hero{
    align-items:flex-start;
  }

  .wow-search-results-title{
    font-size:clamp(26px, 8vw, 38px);
  }

  .wow-search-results-list{
    grid-template-columns:1fr;
  }
}
</style>
