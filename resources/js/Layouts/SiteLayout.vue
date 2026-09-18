<script setup>
import { ref, onMounted, onBeforeUnmount, defineAsyncComponent, watch, nextTick, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

import { useCart } from '@/stores/cart'
import CartDropdown from '@/Components/CartDropdown.vue'
import LocationGate from '@/Components/LocationGate.vue'
import ClassMenuPanel from '@/Components/nav/ClassMenuPanel.vue'
import V3HoldingHero from '@/Components/V3HoldingHero.vue'
import { fetchProducts } from '@/services/products'
const UltraSearchBarLazy = defineAsyncComponent(() => import('@/Components/UltraSearchBar.vue'))

const env = import.meta.env || {}
const mindfulTimesUrl = 'https://times.weofferwellness.co.uk'
const podcastUrl = `${mindfulTimesUrl}/seeking-wellness`
const sevenDayGuideHref = `${mindfulTimesUrl}/7-day-reset-starter-kit`
const corporateComingSoonUrl = '/corporate-wellness'
// Toggle corporate link visibility via env; default hidden
const showCorporateLink = computed(() => {
  const v = String(env.VITE_SHOW_CORPORATE_LINK ?? '').trim().toLowerCase()
  return v === '1' || v === 'true' || v === 'yes'
})
// Toggle all Business-related footer content via env; default hidden
const showBusinessFooter = computed(() => {
  const v = String(env.VITE_SHOW_BUSINESS_FOOTER ?? '').trim().toLowerCase()
  return v === '1' || v === 'true' || v === 'yes'
})
const giftCardsUrl = '/gift-cards'
const practitionerSignupUrl = env.VITE_ATEASE_SIGNUP_URL || 'https://atease.weofferwellness.co.uk/register'
const supportEmail = 'hello@weofferwellness.co.uk'
const v3FooterQuickLinks = [
  { label: 'FAQ', href: '/help/faq' },
  { label: 'Safety guidance', href: '/safety-and-contraindications' },
  { label: 'Refunds & cancellations', href: '/refunds-and-cancellations' },
  { label: 'Privacy policy', href: '/privacy' },
  { label: 'Terms', href: '/terms' },
]

const page = usePage()
const isV3Mode = computed(() => {
  const flag = String(env.VITE_V3_BUILD ?? '').trim().toLowerCase()
  return flag === 'true' || flag === '1' || flag === 'yes'
})
const isAuthenticated = computed(() => !!page.props?.auth?.user)
const accountHref = computed(() => (isAuthenticated.value ? '/dashboard' : '/login'))
const accountLabel = computed(() => (isAuthenticated.value ? 'Your account' : 'Sign in'))
const accountButtonLabel = computed(() => (isAuthenticated.value ? 'Account' : 'Sign in'))

const mobileOpen = ref(false)
const scrolled = ref(false)
const cartOpen = ref(false)
const cartAreaRef = ref(null)
const bump = ref(false)
const cart = useCart()
const openMenu = ref('') // which mega menu is open (desktop)
const megaWrapRef = ref(null)
const megaInnerRef = ref(null)
const megaHeight = ref(0)
const hasEvents = ref(null)
const hasRetreats = ref(null)
watch(mobileOpen, (v) => {
  try { document.body.style.overflow = v ? 'hidden' : '' } catch {}
})

const link = (label, href, opts = {}) => ({ label, href, ...opts })

const utilityPrimaryLinks = [
  link('Free 7-Day Reset', '/reset'),
  link('About We Offer Wellness®', '/about'),
  link('Help Centre', '/help'),
  link('Safety & Contraindications', '/safety-and-contraindications'),
]
const utilitySecondaryLinks = [
  // Hide clickable Corporate; show as coming soon text instead
  { label: 'Corporate (coming 2026)', href: corporateComingSoonUrl, disabled: true },
  link('For Practitioners', practitionerSignupUrl),
]

const nav = [
  { key: 'need', label: 'By Need', href: '/feel' },
  { key: 'therapies', label: 'Therapies', href: '/therapies' },
  { key: 'classes', label: 'Classes', href: '/classes' },
  { key: 'events', label: 'Events & Workshops', href: '/events' },
  { key: 'online', label: 'Online & Near Me', href: '/near-me' },
  { key: 'mindful', label: 'Mindful Times', href: '/mindful-times' },
]

const menus = {
  need: [
    { title: 'How are you feeling?', links: [
      link('Stress & anxiety', '/feel/stress-and-anxiety'),
      link('Trouble sleeping', '/feel/sleep-issues'),
      link('Low mood & burnout', '/feel/low-mood-burnout'),
      link('Overwhelmed & frazzled', '/feel/overwhelm'),
      link('Worry & racing thoughts', '/feel/worry'),
    ]},
    { title: 'What do you want to improve?', links: [
      link('Women’s health & hormones', '/feel/womens-health'),
      link('Men’s health', '/feel/mens-health'),
      link('Digestive & gut health', '/feel/digestive-health'),
      link('Fertility & pregnancy', '/feel/fertility-pregnancy'),
      link('Pain relief & tension', '/feel/pain-relief'),
      link('Nervous system & trauma support', '/feel/trauma-and-nervous-system'),
      link('Emotional balance & self-worth', '/feel/emotional-balance'),
    ]},
    { title: 'Help me choose', links: [
      link('Match me to a therapy (quiz)', '/help/which-therapy'),
      link('Gentle & beginner-friendly options', '/therapies?tag=gentle-beginner'),
      link('View all needs', '/feel'),
    ]},
  ],
  therapies: [
    { title: 'Popular therapies', links: [
      link('Massage therapy', '/therapy/massage'),
      link('Reiki', '/therapy/reiki'),
      link('Reflexology', '/therapy/reflexology'),
      link('Acupuncture', '/therapy/acupuncture'),
      link('Breathwork (1:1)', '/therapy/breathwork'),
      link('Hypnotherapy', '/therapy/hypnotherapy'),
      link('Coaching & counselling', '/therapy/coaching-and-counselling'),
      link('Sound healing (1:1)', '/therapy/sound-healing'),
      link('Yoga therapy', '/therapy/yoga-therapy'),
    ]},
    { title: 'By focus', links: [
      link('Mind & emotions', '/therapies?focus=mind'),
      link('Body & pain relief', '/therapies?focus=body'),
      link('Energy & spiritual', '/therapies?focus=energy'),
      link('Women’s health', '/therapies?focus=womens-health'),
      link('Men’s health', '/therapies?focus=mens-health'),
    ]},
    { title: 'Format & safety', links: [
      link('All therapies', '/therapies'),
      link('Online therapies', '/therapies?format=online'),
      link('In-person therapies', '/therapies?format=in-person'),
      link('Therapies near me', '/therapies?format=in-person&near=me'),
      link('Safety & contraindications', '/safety-and-contraindications'),
    ]},
  ],
  classes: [
    { title: 'Popular classes', links: [
      link('All classes', '/classes'),
      link('Gong bath class', '/classes?type=gong-bath'),
      link('Yoga Nidra', '/classes?type=yoga-nidra'),
      link('Gentle yoga / Slow flow', '/classes?type=gentle-yoga'),
      link('Breathwork class', '/classes?type=breathwork-class'),
      link('Qigong', '/classes?type=qigong'),
      link('Meditation circles', '/classes?type=meditation-circle'),
    ]},
    { title: 'By time', links: [
      link('Morning classes', '/classes?time=morning'),
      link('Lunchtime reset', '/classes?time=lunchtime'),
      link('Evening wind-down', '/classes?time=evening'),
      link('Weekend classes', '/classes?time=weekend'),
    ]},
    { title: 'By format & location', links: [
      link('Online classes', '/classes?format=online'),
      link('In-person classes', '/classes?format=in-person'),
      link('London classes', '/classes?location=london'),
      link('Kent classes', '/classes?location=kent'),
      link('View all cities', '/classes?format=in-person'),
      link('How booking works', '/classes/how-it-works', { divider: true }),
    ]},
  ],
  events: [
    { title: 'WOW-hosted', links: [
      link('All WOW events', '/events?host=wow'),
      link('Sound baths & journeys', '/events/wow/sound-baths'),
      link('Seasonal circles & ceremonies', '/events/wow/seasonal-circles'),
      link('Community & connection events', '/events/wow/community'),
      link('Special series (e.g. Summer Sound Bath Series)', '/events/wow/summer-sound-bath-series'),
    ]},
    { title: 'Practitioner workshops', links: [
      link('All practitioner workshops', '/events?host=practitioner'),
      link('Workshops for clients', '/events/practitioner/client-workshops'),
      link('Trainings & CPD', '/events/practitioner/trainings'),
      link('Breathwork trainings', '/events/practitioner/breathwork-training'),
      link('Somatic & nervous system workshops', '/events/practitioner/somatic-workshops'),
    ]},
    { title: 'When & where', links: [
      link('This week', '/events?timeframe=this-week'),
      link('This month', '/events?timeframe=this-month'),
      link('Online events', '/events?format=online'),
      link('In-person events', '/events?format=in-person'),
      link('Corporate wellness events (coming 2026)', '/corporate-wellness', { divider: true }),
    ]},
  ],
  gifts: [
    { title: 'Gift ideas', links: [
      link('Gift cards for any occasion', '/gift-cards'),
      link('Gifts for stress & burnout', '/feel/stress-and-anxiety?tag=giftable'),
      link('Gifts for better sleep', '/feel/sleep-issues?tag=giftable'),
      link('Gifts for new mums', '/feel/womens-health?tag=postnatal-gift'),
      link('How gift cards work', '/gift-cards#how-it-works'),
    ]},
  ],
  online: [
    { title: 'Format & location', links: [
      link('Online therapies', '/therapies?format=online'),
      link('Online classes & events', '/classes?format=online'),
      link('In-person therapies near me', '/therapies?format=in-person&near=me'),
      link('In-person classes near me', '/classes?format=in-person&near=me'),
      link('In-person events near me', '/events?format=in-person&near=me'),
      link('Explore by location', '/locations'),
    ]},
  ],
  mindful: [
    { title: 'Read, listen & learn', links: [
      link('Mindful Times (all articles)', '/mindful-times'),
      link('Podcast', '/podcast'),
      link('Practitioner chats (coming soon)', '/practitioner-chats'),
    ]},
  ],
  business: [
    { title: 'Corporate & partners', links: [
      link('Corporate wellness (coming 2026)', '/corporate-wellness'),
      link('Corporate gift cards', '/gift-cards/corporate'),
      link('Partner with We Offer Wellness', '/partners'),
    ]},
  ],
  practitioners: [
    { title: 'Practitioner hub', links: [
      link('Become a provider', practitionerSignupUrl),
      link('Practitioner login', 'https://atease.weofferwellness.co.uk/login', { external: true }),
      link('Workshops & trainings', '/events/practitioner/trainings'),
      link('Practitioner FAQs', '/help/practitioners'),
    ]},
  ],
}

const socialLinks = [
  { key: 'instagram', icon: 'bi-instagram', label: 'Instagram', href: env.VITE_SOCIAL_INSTAGRAM_URL ?? 'https://www.instagram.com/weofferwellness/' },
  { key: 'tiktok', icon: 'bi-tiktok', label: 'TikTok', href: env.VITE_SOCIAL_TIKTOK_URL ?? 'https://www.tiktok.com/@weofferwellness' },
  { key: 'linkedin', icon: 'bi-linkedin', label: 'LinkedIn', href: env.VITE_SOCIAL_LINKEDIN_URL ?? 'https://www.linkedin.com/company/weofferwellness/' },
  { key: 'youtube', icon: 'bi-youtube', label: 'YouTube', href: env.VITE_SOCIAL_YOUTUBE_URL ?? 'https://www.youtube.com/@weofferwellness' },
].filter(link => !!link.href)

const hasMenuFor = (key) => Array.isArray(menus[key]) && menus[key].some(col => Array.isArray(col?.links) && col.links.length)
const allowMega = (item) => item && item.hasMega !== false && hasMenuFor(item.key)

function handleNavTrigger(item) {
  if (!allowMega(item)) {
    openMenu.value = ''
    return
  }
  openMenu.value = item.key
}

function isDrawerMenu(item) {
  return allowMega(item)
}

const showNearMeButton = false

function onScroll() {
  scrolled.value = window.scrollY > 8
}

function onKeydown(e){ if (e.key === 'Escape') cartOpen.value = false }
function onDocClick(e){
  if (!cartOpen.value) return
  const el = cartAreaRef.value
  if (el && el.contains(e.target)) return
  cartOpen.value = false
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
  // Bump animation on add-to-cart
  const onAdd = () => {
    bump.value = false
    requestAnimationFrame(() => { bump.value = true; setTimeout(() => bump.value = false, 350) })
  }
  // window.addEventListener('wow:add-to-cart', onAdd)
  document.addEventListener('keydown', onKeydown)
  document.addEventListener('click', onDocClick, true)
  ;(async () => {
    try {
      const ev = await fetchProducts({ type: 'events', limit: 1, sort: 'popular' })
      hasEvents.value = Array.isArray(ev) && ev.length > 0
    } catch { hasEvents.value = null }
    try {
      const rt = await fetchProducts({ type: 'retreats', limit: 1, sort: 'popular' })
      hasRetreats.value = Array.isArray(rt) && rt.length > 0
    } catch { hasRetreats.value = null }
  })()
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  document.removeEventListener('keydown', onKeydown)
  document.removeEventListener('click', onDocClick, true)
})

function setMegaHeight(){
  try { const el = megaInnerRef.value; if (el) megaHeight.value = el.offsetHeight || 0 } catch {}
}
watch(openMenu, async (v) => { if (v) { await nextTick(); setMegaHeight() } })
watch([hasEvents, hasRetreats], async () => { if (openMenu.value) { await nextTick(); setMegaHeight() } })
function onResize(){ setMegaHeight() }
onMounted(() => { try{ window.addEventListener('resize', onResize, { passive:true }) }catch{} })
onBeforeUnmount(() => { try{ window.removeEventListener('resize', onResize) }catch{} })

function nearMe(){
  try {
    navigator.geolocation.getCurrentPosition(pos => {
      const v = { lat: pos.coords.latitude, lng: pos.coords.longitude, ts: Date.now() }
      try { localStorage.setItem('wow_geo', JSON.stringify(v)) } catch {}
      ;(async () => {
        let city = ''
        let region = ''
        let country = ''
        try {
          const key = window.WOW_MAPS_KEY || ''
          if (key) {
            const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${v.lng},${v.lat}.json`)
            url.searchParams.set('access_token', key)
            url.searchParams.set('limit', '1')
            const res = await fetch(url.toString())
            const json = await res.json()
            const feat = json?.features?.[0]
            if (feat) {
              const comps = feat?.context || []
              city = (comps.find(c => c.id?.startsWith('place'))?.text) || (comps.find(c => c.id?.startsWith('locality'))?.text) || ''
              region = (comps.find(c => c.id?.startsWith('region'))?.text) || ''
              country = (comps.find(c => c.id?.startsWith('country'))?.text) || ''
            }
          }
        } catch {}

        try {
          await fetch('/api/geo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.content || window.__csrfToken || '') },
            body: JSON.stringify({ lat: v.lat, lng: v.lng, city, region, country, mode: 'mixed' }),
          })
        } catch {}

        window.location.href = '/near-me'
      })()
    }, () => {
      window.location.href = '/near-me'
    }, { enableHighAccuracy: false, timeout: 5000, maximumAge: 60000 })
  } catch { window.location.href = '/near-me' }
}

function toggleCart(){
  try {
    if (window.matchMedia('(max-width: 767px)').matches) {
      window.location.href = '/cart'
      return
    }
  } catch {}
  cartOpen.value = !cartOpen.value
}

watch(cartOpen, (v) => {
  // Prevent background scroll when dropdown is open to avoid layout shift
  try { document.body.style.overflow = v ? 'hidden' : '' } catch {}
})

function openV3NotifyModal(event){
  try { event?.preventDefault?.() } catch {}
  if (typeof window === 'undefined') return
  try {
    window.dispatchEvent(new CustomEvent('wow:v3-open-modal'))
  } catch {}
}
</script>

<template>
  <div class="min-h-screen text-ink-800">
    <div class="pointer-events-none fixed inset-0 -z-10"></div>
    <div v-if="!isV3Mode" class="utility-bar hidden md:block">
      <div class="container-page">
        <div class="utility-links">
          <div class="utility-links__primary">
            <Link v-for="item in utilityPrimaryLinks" :key="item.href" :href="item.href">{{ item.label }}</Link>
          </div>
          <div class="utility-links__secondary">
            <template v-for="item in utilitySecondaryLinks" :key="item.label + (item.href||'')">
              <span v-if="item.disabled" class="link disabled" aria-disabled="true">{{ item.label }}</span>
              <Link v-else :href="item.href" class="link">{{ item.label }}</Link>
            </template>
          </div>
        </div>
      </div>
    </div>
    <!-- Header -->
    <header v-if="isV3Mode" class="wow-header v3-header sticky top-0 z-40 bg-white/95 backdrop-blur">
      <div class="container-page v3-header__inner">
        <Link href="/" class="v3-header__brand">
          <img src="https://cdn.shopify.com/s/files/1/0820/3947/2469/files/logo.png?v=1738109013" alt="We Offer Wellness" />
        </Link>
        <button type="button" class="v3-header__cta" @click="openV3NotifyModal">Get launch updates</button>
      </div>
    </header>
    <header v-else :class="['sticky top-0 z-40 bg-white/90 backdrop-blur border-b', scrolled ? 'shadow-sm' : '']" @mouseleave="openMenu=''" @keydown.esc="openMenu=''" style="border-bottom:1px solid #999;">
      <div class="container-page h-16 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link href="/" class="flex items-center gap-2 shrink-0">
            <img src="https://cdn.shopify.com/s/files/1/0820/3947/2469/files/logo.png?v=1738109013" alt="We Offer Wellness" class="h-8 w-auto" />
          </Link>
          <nav class="hidden md:flex items-center gap-1">
            <div
              v-for="item in nav"
              :key="item.key"
              class="nav-item"
              @mouseenter="handleNavTrigger(item)"
              @focusin="handleNavTrigger(item)"
            >
              <component
                :is="item.external ? 'a' : Link"
                :href="item.href"
                class="link-wow--nav"
                tabindex="0"
                @keydown.enter.prevent="handleNavTrigger(item)"
                :target="item.external ? (item.newTab === false ? undefined : '_blank') : undefined"
                :rel="item.external && item.newTab !== false ? 'noreferrer' : undefined"
              >
                {{ item.label }}
              </component>
              </div>
            </nav>
        </div>
        <div ref="cartAreaRef" class="hidden md:flex items-center gap-2 position-relative">
          <!-- Search icon routes to /search for a guaranteed experience -->
          <Link href="/search" class="icon-btn" aria-label="Search">
            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
            </svg>
          </Link>
          <button v-if="showNearMeButton" class="btn-wow btn-wow--outline is-squarish btn-md" @click="nearMe">
            <span class="btn-label">Near me</span>
            <span class="btn-spinner" aria-hidden="true"><span class="spin"></span></span>
          </button>
          <Link :href="accountHref" class="icon-btn" :aria-label="accountLabel">
            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.948 8.948 0 0 0 12 21Zm3-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
            </svg>
          </Link>
          <button class="icon-btn position-relative" @click="toggleCart" aria-label="Open cart" :aria-expanded="cartOpen">
            <!-- Cart icon (stroke) -->
            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312"/>
            </svg>
            <span :class="['position-absolute top-0 start-100 translate-middle badge rounded-pill cart-badge bg-danger', bump ? 'badge-bump' : '']">{{ cart.count }}</span>
          </button>
          <CartDropdown :open="cartOpen" @close="cartOpen = false" />
        </div>
        <!-- Removed desktop backdrop to keep dropdown feel and avoid modal-like overlay -->
        <button class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-ink-700 hover:bg-ink-100" @click="mobileOpen = !mobileOpen" aria-label="Toggle menu">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mobileOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"/></svg>
        </button>
      </div>
      <!-- Mobile drawer overlay -->
      <div v-if="mobileOpen" class="fixed inset-0 z-30 md:hidden bg-black/25" @click="mobileOpen=false"></div>
      <!-- Mobile drawer (full-height, scrollable) -->
      <div v-if="mobileOpen" class="mobile-drawer md:hidden">
        <div class="container-page py-2">
          <div class="mb-2">
            <!-- Sticky search at top of drawer -->
            <div class="card p-2"><UltraSearchBarLazy id-prefix="mobile-nav" /></div>
          </div>
          <div class="d-flex gap-2 mb-2">
            <button v-if="showNearMeButton" class="btn-wow btn-wow--outline is-squarish btn-md" @click="nearMe">
              <span class="btn-label">Near me</span>
              <span class="btn-spinner" aria-hidden="true"><span class="spin"></span></span>
            </button>
            <Link :href="giftCardsUrl" class="btn-wow btn-wow--ghost is-square btn-sm">WOW gift vouchers</Link>
            <Link :href="accountHref" class="btn-wow btn-wow--ghost is-square btn-sm">{{ accountButtonLabel }}</Link>
          </div>
          <div class="mobile-nav-scroll">
            <nav class="grid gap-1">
              <template v-for="item in nav" :key="item.key">
                <details v-if="isDrawerMenu(item)" class="drawer-item">
                  <summary>{{ item.label }}</summary>
                  <div class="ps-3 py-2 grid gap-1">
                    <template v-for="col in (menus[item.key]||[])" :key="col.title">
                      <Link
                        v-for="lnk in col.links"
                        :key="lnk.label + lnk.href"
                        :href="lnk.href"
                        :target="lnk.external ? '_blank' : undefined"
                        :rel="lnk.external ? 'noreferrer' : undefined"
                      >
                        {{ lnk.label }}
                      </Link>
                    </template>
                  </div>
                </details>
                <component
                  v-else
                  :is="item.external ? 'a' : Link"
                  :href="item.href"
                  class="drawer-link"
                  :target="item.external ? (item.newTab === false ? undefined : '_blank') : undefined"
                  :rel="item.external && item.newTab !== false ? 'noreferrer' : undefined"
                >
                  {{ item.label }}
                </component>
              </template>
              <Link href="/cart" class="px-3 py-2 rounded-lg text-sm text-ink-700 hover:text-ink-900 hover:bg-ink-100">Cart ({{ cart.count }})</Link>
            </nav>
            <div class="mt-4 pt-3 border-t border-ink-200">
              <div class="text-xs font-semibold uppercase tracking-[0.15em] text-ink-500 mb-2">Help &amp; Info</div>
              <div class="grid gap-1">
                <Link v-for="item in utilityPrimaryLinks" :key="item.href" :href="item.href" class="drawer-link">{{ item.label }}</Link>
              </div>
              <div class="mt-3 grid gap-1">
                <template v-for="item in utilitySecondaryLinks" :key="item.label + (item.href||'')">
                  <span v-if="item.disabled" class="drawer-link disabled" aria-disabled="true">{{ item.label }}</span>
                  <Link v-else :href="item.href" class="drawer-link">{{ item.label }}</Link>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Mega menu panels (desktop) inside header to avoid flicker on hover -->
      <div v-if="openMenu && menus[openMenu]" :class="['mega-panel d-none d-md-block', `mega-panel--${openMenu}`]">
        <div ref="megaWrapRef" class="mega-wrap" :style="{ height: megaHeight + 'px' }">
          <div ref="megaInnerRef" class="mega-inner">
            <div class="container-page py-4">
              <div class="grid md:grid-cols-3 gap-6">
                <div v-for="col in (menus[openMenu] || [])" :key="col.title || col.id" class="menu-col">
                  <div v-if="col.title" class="kicker mb-2">{{ col.title }}</div>
                  <ul class="list-unstyled m-0 p-0">
                    <li v-for="lnk in col.links" :key="lnk.label + lnk.href" :class="lnk.divider ? 'menu-divider' : ''">
                      <a :href="lnk.href" class="menu-link" :target="lnk.external ? '_blank' : undefined" :rel="lnk.external ? 'noreferrer' : undefined">{{ lnk.label }}</a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main :class="{ 'v3-mode': isV3Mode }">
      <V3HoldingHero v-if="isV3Mode" />
      <slot v-else />
    </main>


    <!-- Footer -->
    <footer v-if="isV3Mode" class="wow-footer v3-footer">
      <div class="container-page py-10">
        <div class="v3-footer__brand">
          <img src="https://cdn.shopify.com/s/files/1/0820/3947/2469/files/wow-logo-white_b5bc0fc0-ae06-4aa2-af86-f7a42ff78107.png?v=1757430233" alt="We Offer Wellness" />
          <p class="tagline">Holistic Therapy That Works — curated practitioners, launch discounts, and giveaways when v3 goes live.</p>
          <p class="about">We’re building the safest place to book holistic therapies, classes and events. V3 is focused on respected practitioners, trauma-aware care and simple booking.</p>
        </div>
        <div class="v3-footer__grid">
          <div>
            <div class="title">Need help?</div>
            <p>Email us anytime — we respond within 24 hours.</p>
            <a class="link" :href="`mailto:${supportEmail}`">{{ supportEmail }}</a>
            <p class="mt-2">Or message us via <a href="/contact?topic=support">the contact form</a>.</p>
          </div>
          <div>
            <div class="title">Quick links</div>
            <ul>
              <li v-for="item in v3FooterQuickLinks" :key="item.href">
                <component :is="item.href.startsWith('http') ? 'a' : Link" :href="item.href" class="link">{{ item.label }}</component>
              </li>
            </ul>
          </div>
          <div>
            <div class="title">Follow</div>
            <div class="social" v-if="socialLinks.length">
              <a v-for="link in socialLinks" :key="link.key" :href="link.href" :aria-label="link.label" class="social-btn" target="_blank" rel="noopener">
                <i :class="['bi', link.icon]"></i>
              </a>
            </div>
          </div>
        </div>
        <div class="v3-footer__cta">
          <div>
            <div class="title">Stay in the loop</div>
            <p>Hop on the v3 insider list for sneak peeks, soft-launch invites and early-bird experiences as we finish the build.</p>
          </div>
          <button type="button" class="btn-wow btn-wow--cta btn-sm" @click="openV3NotifyModal">Notify me</button>
        </div>
        <div class="footer-bottom">
          <p>© {{ new Date().getFullYear() }} We Offer Wellness</p>
          <div class="legal">
            <a href="/privacy">Privacy</a>
            <a href="/terms">Terms</a>
            <a href="/cookies">Cookies</a>
          </div>
        </div>
      </div>
    </footer>
    <footer v-else class="wow-footer mt-24">
      <div class="container-page py-10">
        <!-- Subscribe / brand row -->
        <div class="footer-hero">
          <div class="brand-col">
            <img src="https://cdn.shopify.com/s/files/1/0820/3947/2469/files/wow-logo-white_b5bc0fc0-ae06-4aa2-af86-f7a42ff78107.png?v=1757430233" alt="We Offer Wellness" class="logo" />
            <p class="tagline">Safe, trusted therapies and wellness services that help you feel better — today.</p>
            <p class="about-snippet">We Offer Wellness® connects you with trusted holistic therapies and gentle classes to support your mind, body and spirit — from the comfort of home or in-person with verified practitioners.</p>
          </div>
        <div class="subscribe-col">
            <div class="subscribe-card">
              <div class="social" v-if="socialLinks.length">
                <a v-for="link in socialLinks" :key="link.key" :href="link.href" :aria-label="link.label" class="social-btn" target="_blank" rel="noopener">
                  <i :class="['bi', link.icon]"></i>
                </a>
              </div>
              <div class="sub-title">Join our Community</div>
              <form class="subscribe" data-subscriber-form="footer-community" data-subscriber-source="footer:community" @submit.prevent>
                <input class="sub-input" name="first_name" type="text" required placeholder="First name" aria-label="First name" autocomplete="given-name" maxlength="80">
                <input class="sub-input" name="email" type="email" required placeholder="Email address" aria-label="Email address">
                <button class="sub-btn" type="submit">
                  <span>Join Community</span>
                  <i class="bi bi-arrow-right-short"></i>
                </button>
              </form>
              <div class="sub-note">No spam. Unsubscribe any time.</div>
            </div>
          </div>
        </div>

        <div class="footer-cta" v-if="showBusinessFooter">
          <div>
            <div class="title">Practitioners</div>
            <p>Share your expertise with the WOW community. Trauma-aware, inclusive practitioners are always welcome.</p>
          </div>
          <a :href="practitionerSignupUrl" class="btn-wow btn-wow--cta btn-sm" target="_blank" rel="noopener">
            Become a provider
          </a>
        </div>

        <div class="safety-card">
          <div>
            <div class="title">Safety &amp; Contraindications</div>
            <p>Always consult your GP or healthcare provider if you are pregnant, have a diagnosed condition or take prescription medication. Review contraindications for every therapy and contact the practitioner if you are unsure.</p>
            <p class="mt-2" style="color:#cbd5f5; max-width: 60ch;">
              This information does not replace medical advice. Stop any session if you feel unwell and seek urgent care for red‑flag symptoms (call 999 in the UK).
            </p>
          </div>
          <a href="/safety-and-contraindications" class="btn-wow btn-wow--ghost">Read full guidance</a>
        </div>

        <!-- Links grid -->
        <div class="links-col">
          <div class="col">
            <div class="title">Explore</div>
            <ul>
              <li><a href="/therapies">Therapies</a></li>
              <li><a href="/classes">Classes</a></li>
              <li><a href="/events">Events &amp; Workshops</a></li>
              <li><a :href="giftCardsUrl">WOW gift vouchers</a></li>
              <li><a :href="mindfulTimesUrl" target="_blank" rel="noopener">Mindful Times</a></li>
              <li><a :href="podcastUrl" target="_blank" rel="noopener">Podcast</a></li>
              <li><a :href="sevenDayGuideHref" target="_blank" rel="noopener">Free 7-day reset</a></li>
            </ul>
          </div>
          <div class="col">
            <div class="title">Company</div>
            <ul>
              <li><a href="/about">About</a></li>
              <li><a href="/contact">Contact</a></li>
              <li v-if="showCorporateLink"><a :href="corporateComingSoonUrl">Corporate wellness (coming 2026)</a></li>
              <li v-if="showBusinessFooter"><a href="/partners">Partner spotlight</a></li>
              <li><a :href="`${mindfulTimesUrl}#practitioner-chats`" target="_blank" rel="noopener">Practitioner chats</a></li>
            </ul>
          </div>
          <div class="col">
            <div class="title">Help Centre &amp; FAQ</div>
            <ul>
              <li><a href="/help">Help centre</a></li>
              <li><a href="/help/faq">FAQ</a></li>
              <li><a href="/safety-and-contraindications">Safety &amp; contraindications</a></li>
              <li><a href="/refunds-and-cancellations">Booking &amp; cancellations</a></li>
              <li><a href="/contact?topic=feedback">Contact support</a></li>
            </ul>
          </div>
        </div>

        <!-- Bottom bar -->
        <div class="footer-bottom mb-3">
          <p>© {{ new Date().getFullYear() }} We Offer Wellness</p>
          <div class="legal">
            <a href="/privacy">Privacy</a>
            <a href="/terms">Terms</a>
            <a href="/cookies">Cookies</a>
          </div>
          <a :href="practitionerSignupUrl" class="btn-wow btn-wow--cta btn-sm" target="_blank" rel="noopener">
            Become a provider
          </a>
        </div>
      </div>
    </footer>
  </div>
  <LocationGate v-if="!isV3Mode" />

</template>

<style>
/* Mobile drawer */
.mobile-drawer{ position: fixed; z-index: 40; top: 64px; left: 0; right: 0; bottom: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; background:#fff; border-top:1px solid rgba(148,163,184,.3); }
.mobile-nav-scroll{ max-height: none; overflow-y: visible; padding-right: 4px; }
.drawer-link{ display:block; padding:0.65rem 1rem; border-radius:0.75rem; font-weight:600; color:var(--ink-800); text-decoration:none; }
.drawer-link:hover{ background:var(--ink-100); color:var(--ink-900); }
.drawer-link.disabled, .utility-links__secondary .link.disabled{ color:#94a3b8; cursor:default; text-decoration:none; }
.utility-links__secondary .link{ color:inherit; text-decoration:none; }
.utility-bar{ background:#f8fafc; border-bottom:1px solid rgba(148,163,184,.3); font-size:.85rem }
.utility-bar .utility-links{ display:flex; align-items:center; gap:1.5rem; padding:.35rem 0; color:var(--ink-600); font-weight:500 }
.utility-links__primary, .utility-links__secondary{ display:flex; gap:1.25rem; }
.utility-links__secondary{ margin-left:auto; }
.utility-bar a{ color:inherit; text-decoration:none }
.utility-bar a:hover{ color:var(--ink-900) }

.v3-header{ border-bottom:1px solid rgba(15,23,42,.08); box-shadow:0 10px 35px rgba(15,23,42,.08); }
.v3-header__inner{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem 2rem; padding:.85rem 15px; }
.v3-header__brand{ display:flex; align-items:center; gap:.85rem; text-decoration:none; color:#0f172a; font-weight:600; }
.v3-header__brand img{ height:30px; width:auto; }
.v3-header__brand-copy span{ display:block; font-size:1rem; }
.v3-header__brand-copy small{ display:block; font-size:.85rem; font-weight:500; color:#475569; }
.v3-header__nav{ display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; }
.v3-header__link{ text-decoration:none; color:#475569; font-weight:600; padding:.4rem .75rem; border-radius:999px; transition:all .2s ease; }
.v3-header__link:hover{ background:#f1f5f9; color:#0f172a; }
.v3-header__cta{ border-radius:999px; padding:.55rem 1.4rem; background:#0b1323; color:#fff; text-decoration:none; font-weight:600; box-shadow:0 8px 20px rgba(15,23,42,.15); border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
.v3-header__cta:hover{ background:#111827; }
@media (max-width: 640px){
  .v3-header__inner{ flex-wrap:nowrap; flex-direction:row; align-items:center; justify-content:space-between; width:100%; }
  .v3-header__cta{ white-space:nowrap; }
}

main.v3-mode{
  background: radial-gradient(circle at 50% 50%, #f4c6a5 0%, #f9d9c2 18%, #fff3e9 55%);
  padding: 3rem 0 5rem;
}

/* Base mega panel tweaks */
.mega-panel{ border-top:1px solid var(--ink-200); background:#fff }
.mega-panel .menu-col--feature{ align-self:stretch }
.mega-panel .mega-wrap{ transition: height 180ms cubic-bezier(.2,.75,.2,1); overflow: hidden }
.mega-panel .mega-inner{ will-change: height }
.mega-panel .grid{ grid-template-columns: repeat(3, 270px) !important; justify-content: space-between; }
.mega-panel .menu-col{ width: 270px; }
.mega-panel .menu-col--feature{ width: 270px; }

/* Classes panel slight styling */
.mega-panel--classes{ box-shadow: inset 0 1px 0 rgba(2,8,23,.04) }
.mega-panel--classes .menu-col--feature{ background:#fff; border-radius: 12px; padding: 8px 12px; border:1px solid var(--ink-200) }
.mega-panel--classes .kicker{ background: linear-gradient(180deg, #e8f4f1, #edf7f4); color:#2b675b; border-color:#cfe5df }
.mega-panel--classes .menu-link:hover{ color:#2b675b }

/* Events panel slight styling */
.mega-panel--events{ background:#fff }
.mega-panel--events .event-feature{ position:relative; border-radius:12px; overflow:hidden; border:1px solid var(--ink-200); background:#fff }
.mega-panel--events .event-img{ width:100%; height:160px; object-fit:cover; display:block }
.mega-panel--events .event-copy{ padding:10px 12px }
.mega-panel--events .kicker{ background: linear-gradient(180deg, #f1edff, #f8f6ff); color:#5b51b5; border-color:#e1dcff }
.mega-panel--events .menu-link:hover{ color:#5b51b5 }

/* Therapies panel */
.mega-panel--therapies{ background:#fff }
.mega-panel--therapies .kicker{ background: linear-gradient(180deg, #e9f7f0, #f1fbf6); color:#2d6d5f; border-color:#cfe7df }
.mega-panel--therapies .menu-link:hover{ color:#2d6d5f }

/* Retreats panel */
.mega-panel--retreats{ background:#fff }
.mega-panel--retreats .kicker{ background: linear-gradient(180deg, #e5f6fd, #eefbff); color:#0d6b7a; border-color:#c8e9f2 }
.mega-panel--retreats .menu-link:hover{ color:#0d6b7a }

/* Gifts panel */
.mega-panel--gifts{ background:#fff }
.mega-panel--gifts .kicker{ background: linear-gradient(180deg, #fff1d6, #fff6e6); color:#8a5a19; border-color:#f7dbaf }
.mega-panel--gifts .menu-link:hover{ color:#8a5a19 }

/* Corporate panel */
.mega-panel--corporate{ background:#fff }
.mega-panel--corporate .kicker{ background: linear-gradient(180deg, #eaf1ff, #f2f6ff); color:#1f3b70; border-color:#d7e3ff }
.mega-panel--corporate .menu-link:hover{ color:#1f3b70 }

/* Providers panel */
.mega-panel--providers{ background:#fff }
.mega-panel--providers .kicker{ background: linear-gradient(180deg, #e9f4ff, #f1f8ff); color:#1b63a8; border-color:#d2e7ff }
.mega-panel--providers .menu-link:hover{ color:#1b63a8 }

/* About panel */
.mega-panel--about{ background:#fff }
.mega-panel--about .kicker{ background: linear-gradient(180deg, #eef2f7, #f5f7fb); color:#334155; border-color:#e2e8f0 }
.mega-panel--about .menu-link:hover{ color:#334155 }

/* Shared feature card styles */
.mega-panel .menu-feature{ border:1px solid var(--ink-200); border-radius:12px; overflow:hidden; background:#fff; height:100%; display:flex; flex-direction:column }
.mega-panel .menu-feature .feature-img{ width:100%; height:140px; object-fit:cover; display:block }
.mega-panel .menu-feature .feature-copy{ padding:10px 12px }
.mega-panel .menu-feature .h5{ font-weight:500; font-size:1.25rem; line-height:1.2; margin:2px 0 4px; color:#0b1323 }
.mega-panel .menu-feature .muted{ color: var(--ink-700) }
.mega-panel .menu-col--spacer{ visibility:hidden }
.mega-panel .menu-divider{ border-top:1px solid var(--ink-200); margin-top:0.4rem; padding-top:0.4rem }

/* Feature variants: keep white background */
.mega-panel--gifts .menu-feature.gift{ background:#fff }
.mega-panel--corporate .menu-feature.corporate{ background:#fff }
.mega-panel--providers .menu-feature.providers{ background:#fff }
.mega-panel--about .menu-feature.about{ background:#fff }

/* Shared hero layout */
.wow-hero-card{ border-radius:24px; padding:1.75rem; background:linear-gradient(135deg,#f6f8ff,#ffffff); border:1px solid rgba(15,23,42,.08); box-shadow:0 30px 80px rgba(15,23,42,.08); }
@media (min-width: 768px){ .wow-hero-card{ padding:2.75rem 3rem; } }
.wow-hero-grid{ display:grid; gap:1.5rem; }
@media (min-width: 768px){ .wow-hero-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); align-items:flex-start; } }
.wow-hero-panel{ background:#fff; border:1px solid var(--ink-200); border-radius:20px; padding:1.5rem; box-shadow:0 20px 50px rgba(15,23,42,.08); }
.wow-hero-panel ul{ margin:1rem 0 0; padding-left:1.25rem; color:var(--ink-700); }
.wow-hero-panel li{ margin-bottom:.35rem; }

/* Footer */
.wow-footer{ background:#020617; color:#fff; }

.v3-footer .v3-footer__brand img{ max-width:220px; }
.v3-footer .v3-footer__brand .tagline{ margin-top:1rem; font-size:1.15rem; color:#e2e8f0; }
.v3-footer .v3-footer__brand .about{ margin-top:.5rem; color:#cbd5f5; max-width:560px; }
.v3-footer__grid{ display:grid; gap:1.5rem; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); margin:2.5rem 0; }
.v3-footer__grid .title, .v3-footer__cta .title{ font-weight:600; text-transform:uppercase; font-size:.85rem; letter-spacing:.08em; color:#94a3b8; }
.v3-footer__grid p{ color:#cbd5f5; }
.v3-footer__grid ul{ list-style:none; margin:0; padding:0; }
.v3-footer__grid li{ margin-bottom:.4rem; }
.v3-footer__grid .link{ color:#e2e8f0; text-decoration:none; }
.v3-footer__grid .link:hover{ color:#fff; }
.v3-footer__grid .social{ display:flex; gap:.6rem; flex-wrap:wrap; }
.v3-footer__grid .social-btn{ width:40px; height:40px; border-radius:999px; border:1px solid rgba(255,255,255,.4); display:flex; align-items:center; justify-content:center; color:#fff; transition:background .2s ease,color .2s ease; }
.v3-footer__grid .social-btn:hover{ background:#fff; color:#0f172a; }
.v3-footer__cta{ border:1px solid rgba(255,255,255,.18); border-radius:16px; padding:1.25rem 1.5rem; display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between; background:rgba(15,23,42,.35); margin-bottom:2rem; }
.wow-footer .footer-hero{ display:flex; flex-wrap:wrap; gap:2rem; justify-content:space-between; margin-bottom:2.5rem; }
.wow-footer .brand-col{ max-width:420px; }
.wow-footer .brand-col .logo{ max-width:215px; }
.wow-footer .brand-col .tagline{ margin-top:1.5rem; font-size:1.05rem; line-height:1.5; color:#e2e8f0; }
.wow-footer .about-snippet{ margin-top:.75rem; color:#cbd5f5; max-width:360px; font-size:.95rem; }
.wow-footer .brand-col .social{ display:flex; gap:.75rem; margin-top:1rem; }
.wow-footer .brand-col .social-btn{ width:40px; height:40px; border-radius:999px; border:1px solid rgba(255,255,255,.4); display:flex; align-items:center; justify-content:center; color:#fff; transition:background .2s ease, color .2s ease; }
.wow-footer .brand-col .social-btn:hover{ background:#fff; color:#0f172a; }
.wow-footer .subscribe-card{ background:rgba(15,23,42,.7); border:1px solid rgba(255,255,255,.15); border-radius:18px; padding:1.5rem; }
.wow-footer .sub-title{ font-size:1.2rem; font-weight:600; margin-bottom:1rem; }
.wow-footer .subscribe{ display:flex; gap:.75rem; flex-wrap:wrap; }
.wow-footer .sub-input{ flex:1; min-width:220px; border-radius:999px; border:1px solid rgba(255,255,255,.4); background:rgba(15,23,42,.6); padding:.75rem 1rem; color:#fff; }
.wow-footer .sub-btn{ border-radius:999px; background:#fff; color:#0b1323; border:0; padding:.75rem 1.25rem; display:inline-flex; align-items:center; gap:.25rem; font-weight:600; }
.wow-footer .links-col{ display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:2rem; margin-bottom:2rem; }
.wow-footer .links-col .title{ font-weight:600; text-transform:uppercase; font-size:.85rem; letter-spacing:.08em; margin-bottom:1rem; color:#94a3b8; }
.wow-footer .links-col ul{ list-style:none; padding:0; margin:0; }
.wow-footer .links-col a{ color:#e2e8f0; text-decoration:none; display:inline-block; margin-bottom:.5rem; }
.wow-footer .links-col a:hover{ color:#fff; }
.wow-footer .footer-cta{ border:1px solid rgba(255,255,255,.18); border-radius:16px; padding:1.25rem 1.5rem; margin-bottom:2rem; display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between; background:rgba(15,23,42,.35); }
.wow-footer .footer-cta .title{ font-weight:600; text-transform:uppercase; font-size:.85rem; letter-spacing:.08em; color:#94a3b8; margin-bottom:.35rem; }
.wow-footer .footer-cta p{ margin:0; color:#cbd5f5; max-width:520px; }
.wow-footer .safety-card{ border:1px solid rgba(255,255,255,.2); border-radius:16px; padding:1.5rem; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; background:rgba(15,23,42,.35); margin-bottom:2rem; }
.wow-footer .safety-card .title{ font-weight:600; text-transform:uppercase; font-size:.85rem; letter-spacing:.08em; color:#94a3b8; margin-bottom:.35rem; }
.wow-footer .safety-card p{ margin:0; color:#cbd5f5; max-width:560px; }
.wow-footer .footer-bottom{ display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; border-top:1px solid rgba(255,255,255,.1); padding-top:1.5rem; }
.wow-footer .footer-bottom p{ margin:0; color:#94a3b8; }
.wow-footer .footer-bottom .legal{ display:flex; gap:1rem; }
.wow-footer .footer-bottom .legal a{ color:#94a3b8; text-decoration:none; }
.wow-footer .footer-bottom .legal a:hover{ color:#fff; }
</style>
