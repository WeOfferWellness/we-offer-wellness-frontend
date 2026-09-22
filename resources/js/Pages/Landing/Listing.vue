<script setup>
import { computed, ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import SiteLayout from '@/Layouts/SiteLayout.vue'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import ProductCard from '@/Components/ProductCard.vue'
import Pagination from '@/Components/Pagination.vue'
import DistanceFilter from '@/Components/DistanceFilter.vue'
import RecommendationsRail from '@/Components/RecommendationsRail.vue'
import {
  canonicalUrl,
  pageKeywords,
  shortOgDescription,
  shortOgTitle,
} from '@/lib/seo-meta'

const props = defineProps({
  type: { type: String, required: false, default: '' },
  city: { type: String, required: false, default: '' },
  category: { type: Object, required: false, default: null },
  products: { type: [Array, Object], default: () => [] },
  mapsKey: { type: String, default: '' },
})

const heading = computed(() => {
  const parts = []
  if (props.category?.name) parts.push(props.category.name)
  if (props.type) parts.push(props.type.slice(0,1).toUpperCase() + props.type.slice(1))
  if (props.city) parts.push('in ' + props.city)
  return parts.join(' ')
})

const sort = ref('popular')
const mode = ref('all')
function readModeFromUrl(){
  try { return new URLSearchParams(window.location.search || '').get('mode') || 'all' } catch { return 'all' }
}
onMounted(() => { mode.value = readModeFromUrl() })
try { document.addEventListener('inertia:success', () => { mode.value = readModeFromUrl() }) } catch {}
function setSort(s) {
  sort.value = s
  const url = new URL(window.location.href)
  url.searchParams.set('sort', s)
  router.visit(url.pathname + '?' + url.searchParams.toString(), { preserveScroll: true, preserveState: true })
}

function setMode(m) {
  const url = new URL(window.location.href)
  // Optimistically update active state immediately
  mode.value = (!m || m === 'all') ? 'all' : m
  url.searchParams.set('mode', mode.value)
  router.visit(url.pathname + (url.search ? '?' + url.searchParams.toString() : ''), { preserveScroll: true, preserveState: true })
}

// SEO
const canonical = computed(() => canonicalUrl(typeof window !== 'undefined' ? window.location.pathname : '/'))
const desc = computed(() => (heading.value ? `${heading.value} — curated by We Offer Wellness.` : 'Curated results from We Offer Wellness.'))
const ogTitle = computed(() => shortOgTitle(`${heading.value || 'Results'} | WOW®`))
const ogDesc = computed(() => shortOgDescription(desc.value))
const keywords = computed(() => pageKeywords({
  type: props.type,
  category: props.category,
  city: props.city,
  extra: [heading.value, 'curated results', 'live listings'],
}))
const items = computed(() => Array.isArray(props.products) ? props.products : (props.products?.data || []))
const filteredIds = ref([])
const showItems = computed(() => {
  if (!filteredIds.value.length) return items.value
  const set = new Set(filteredIds.value)
  return items.value.filter(p => set.has(p.id))
})
const breadcrumbLd = computed(() => {
  const crumbs = [
    { name: 'Home', item: (typeof window!=='undefined' ? window.location.origin : '/') },
  ]
  if (props.type) crumbs.push({ name: props.type.charAt(0).toUpperCase()+props.type.slice(1), item: (typeof window!=='undefined' ? (window.location.origin + '/' + props.type) : '/' + props.type) })
  if (props.category?.name) crumbs.push({ name: props.category.name, item: canonical.value })
  return {
    '@context': 'https://schema.org', '@type': 'BreadcrumbList',
    'itemListElement': crumbs.map((c,i) => ({ '@type': 'ListItem', position: i+1, name: c.name, item: c.item }))
  }
})
const itemListLd = computed(() => ({
  '@context': 'https://schema.org', '@type': 'ItemList',
  'itemListElement': items.value.slice(0,24).map((p,i)=>({ '@type':'ListItem', position:i+1, url:p.url, name:p.title }))
}))
</script>

<template>
  <Head :title="heading || 'Results'">
    <meta name="description" :content="desc" />
    <meta name="keywords" :content="keywords.join(', ')" />
    <link v-if="canonical" rel="canonical" :href="canonical" />
    <meta property="og:title" :content="ogTitle" />
    <meta property="og:description" :content="ogDesc" />
    <meta v-if="canonical" property="og:url" :content="canonical" />
    <script type="application/ld+json">{{ JSON.stringify(breadcrumbLd) }}</script>
    <script type="application/ld+json">{{ JSON.stringify(itemListLd) }}</script>
  </Head>
  <SiteLayout>
    <!-- Hero / Heading -->
    <section class="section">
      <div class="container-page">
        <Breadcrumbs :items="[
          props.city ? { label: props.city, href: canonicalUrl(`/locations/united-kingdom/${props.city.toLowerCase()}`) } : null,
          props.type ? { label: props.type.charAt(0).toUpperCase()+props.type.slice(1), href: canonicalUrl(`/${props.type}`) } : null,
          props.category?.name ? { label: props.category.name } : null,
        ].filter(Boolean)" />
        <div class="mb-5">
          <div class="kicker">Explore</div>
          <h1>{{ heading || 'Results' }}</h1>
          <p class="text-ink-600 mt-2">Curated practitioners, verified where applicable, with transparent pricing.</p>
        </div>

    <!-- Filters -->
    <div class="filters">
      <div class="mode">
        <span class="label">Mode</span>
        <div class="seg-group" role="tablist" aria-label="Mode">
          <button class="seg" :class="{ active: mode==='all' }" @click="setMode('all')" role="tab" :aria-selected="mode==='all'">All</button>
          <button class="seg" :class="{ active: mode==='online' }" @click="setMode('online')" role="tab" :aria-selected="mode==='online'">Online</button>
          <button class="seg" :class="{ active: mode==='in-person' }" @click="setMode('in-person')" role="tab" :aria-selected="mode==='in-person'">In‑person</button>
        </div>
      </div>
      <div class="sort">
        <span class="label">Sort</span>
        <select class="sort-select form-select form-select-sm w-auto" v-model="sort" @change="setSort(sort)">
          <option value="popular">Popular</option>
          <option value="newest">Newest</option>
          <option value="price_asc">Price (low→high)</option>
          <option value="price_desc">Price (high→low)</option>
        </select>
      </div>
    </div>
    <!-- Distance filter -->
    <DistanceFilter v-if="props.mapsKey" :maps-key="props.mapsKey" :products="items" @filtered="ids => filteredIds = ids" />
      </div>
    </section>

    <!-- Results grid -->
    <section class="section">
      <div class="container-page">
        <div v-if="showItems?.length" class="grid sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-4">
          <ProductCard v-for="p in showItems" :key="p.id" :product="p" :fluid="true" />
        </div>
        <div v-else class="card p-5 text-center text-ink-600">
          No listings yet — check back soon.
        </div>
        <Pagination v-if="!Array.isArray(props.products) && props.products?.links" :links="props.products.links" />
      </div>
    </section>

    <!-- Helpful rails below listings -->
    <RecommendationsRail v-if="!items.length && props.type==='therapies'" title="People also like" :query="{ type: 'therapies', sort: 'popular' }" :limit="12" />
    <RecommendationsRail v-if="!items.length && props.type==='therapies'" title="New this month" :query="{ type: 'therapies', sort: 'newest' }" :limit="12" />
  </SiteLayout>
</template>

<style scoped>
.form-select { border-radius: 12px; border-color: var(--ink-200); }
.filters{ display:flex; align-items:center; gap: 12px; padding: 10px 12px; background:#fff; border:1px solid var(--ink-200); border-radius: 14px; box-shadow: 0 6px 12px rgba(2,8,23,.06) }
.filters .label{ color: var(--ink-700); font-size:.9rem; margin-right:8px }
.filters .mode{ display:flex; align-items:center; gap:8px; flex-wrap:wrap }
.filters .sort{ margin-left:auto; display:flex; align-items:center; gap:8px }
.seg-group{ display:inline-flex; background:#f8fafc; border:1px solid var(--ink-200); border-radius:999px; padding:2px }
.seg{ appearance:none; border:0; background:transparent; padding:6px 12px; border-radius:999px; color: var(--ink-700); font-weight:600; font-size:.9rem; transition: all .15s ease; }
.seg:hover{ background:#eef2f7 }
.seg.active{ background: linear-gradient(180deg, #549483, #3b7768); color:#fff; box-shadow: 0 1px 0 rgba(255,255,255,.4) inset }
@media (max-width: 640px){ .filters{ flex-wrap:wrap } .filters .sort{ margin-left:0 } }
</style>
