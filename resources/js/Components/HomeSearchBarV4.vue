<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  idPrefix: { type: String, default: 'home-search-v4' },
  searchUrl: { type: String, default: '/search' },
})

const what = ref('')
const where = ref('')
const activeDesktopMenu = ref('')
const activeMobileMenu = ref('')
const whatInput = ref(null)
const whereInput = ref(null)
const mobileInput = ref(null)
const referenceWhat = [
  ['Massage Therapy', 'Therapy'], ['Sound Healing', 'Therapy'], ['Breathwork', 'Therapy'],
  ['Yoga', 'Class'], ['Pilates', 'Class'], ['Reiki', 'Therapy'], ['Meditation', 'Class'],
  ['Acupuncture', 'Therapy'], ['Reflexology', 'Therapy'], ['Hypnotherapy', 'Therapy'],
  ['Life Coaching', 'Session'], ['Yin Yoga', 'Class'], ['Hot Yoga', 'Class'],
  ['Pregnancy Yoga', 'Class'], ['Sound Bath', 'Event'], ['Wellness Retreat', 'Retreat'],
  ['Couples Massage', 'Therapy'], ['Aromatherapy', 'Therapy'], ['Kinesiology', 'Therapy'],
  ['EFT / Tapping', 'Session'], ['Nutrition Coaching', 'Session'], ['Craniosacral Therapy', 'Therapy'],
  ['Chakra Balancing', 'Session'], ['Corporate Wellness', 'Workshop'], ["Women's Circle", 'Event'], ['Gift Card', 'Gift'],
].map(([title, cat]) => ({ title, value: title, cat }))
const referenceLocations = ['London', 'Kent', 'Manchester', 'Birmingham', 'Bristol', 'Edinburgh', 'Brighton', 'Leeds']

const popularWhat = computed(() => {
  const query = what.value.trim().toLowerCase()
  const matches = query
    ? referenceWhat.filter((item) => `${item.title} ${item.cat}`.toLowerCase().includes(query))
    : referenceWhat
  return matches.slice(0, 8)
})

const mobileWhatResults = computed(() => {
  const query = what.value.trim().toLowerCase()
  return (query ? referenceWhat.filter((item) => `${item.title} ${item.cat}`.toLowerCase().includes(query)) : referenceWhat)
})
const locationResults = computed(() => {
  const query = where.value.trim().toLowerCase()
  return referenceLocations.filter((location) => !query || location.toLowerCase().includes(query))
})
const isMenuOpen = computed(() => !!activeDesktopMenu.value || !!activeMobileMenu.value)

function closeMenus() {
  activeDesktopMenu.value = ''
  activeMobileMenu.value = ''
}

function openDesktopMenu(menu) {
  activeMobileMenu.value = ''
  activeDesktopMenu.value = menu
  nextTick(() => (menu === 'what' ? whatInput.value : whereInput.value)?.focus())
}

function openMobileMenu(menu) {
  activeDesktopMenu.value = ''
  activeMobileMenu.value = menu
  nextTick(() => mobileInput.value?.focus())
}

function submit() {
  if (!what.value.trim()) {
    if (activeMobileMenu.value) {
      openMobileMenu('what')
    } else {
      openDesktopMenu('what')
    }
    return
  }
  const params = new URLSearchParams()
  if (what.value.trim()) params.set('what', what.value.trim())
  if (where.value === 'Near me') {
    params.set('mode', 'near-me')
  } else if (where.value.trim()) {
    params.set('where', where.value.trim())
  }
  closeMenus()
  router.visit(`${props.searchUrl}${params.size ? `?${params.toString()}` : ''}`, { method: 'get' })
}

function selectWhat(item) {
  what.value = item.value || item.title
  const wasDesktop = activeDesktopMenu.value === 'what'
  closeMenus()
  if (wasDesktop) nextTick(() => whereInput.value?.focus())
}

function selectWhere(item) {
  where.value = typeof item === 'string' ? item : (item.value || item.title)
  closeMenus()
}

function selectOnline() {
  where.value = 'Online'
  closeMenus()
}

function useMyLocation() {
  where.value = 'Near me'
  closeMenus()
  navigator.geolocation?.getCurrentPosition(() => {}, () => {}, {
    enableHighAccuracy: false,
    timeout: 6000,
    maximumAge: 60000,
  })
}

function handleKeydown(event) {
  if (event.key === 'Escape') closeMenus()
}

function handleDocumentClick(event) {
  if (!event.target.closest('.home-v4-search, .home-v4-search__sheet')) closeMenus()
}

watch(isMenuOpen, (open) => {
  document.documentElement.classList.toggle('home-v4-search-open', open)
})

onMounted(async () => {
  const params = new URLSearchParams(window.location.search)
  what.value = params.get('what') || ''
  where.value = params.get('mode') === 'near-me' ? 'Near me' : (params.get('where') || '')
  document.addEventListener('keydown', handleKeydown)
  document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
  document.documentElement.classList.remove('home-v4-search-open')
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('click', handleDocumentClick)
})
</script>

<template>
  <div class="home-v4-search">
    <form class="home-v4-search__desktop" :class="{ 'is-active': activeDesktopMenu }" role="search" @submit.prevent="submit">
      <div class="home-v4-search__field" :class="{ 'is-active': activeDesktopMenu === 'what' }" @click="openDesktopMenu('what')">
        <i class="bi bi-stars" aria-hidden="true"></i>
        <label :for="`${idPrefix}-what`">Search</label>
        <input :id="`${idPrefix}-what`" ref="whatInput" v-model="what" type="search" autocomplete="off" placeholder="Therapies, events, classes & more" @focus="openDesktopMenu('what')">
        <button v-if="what" class="home-v4-search__clear" type="button" aria-label="Clear search" @click.stop="what = ''">×</button>
        <div v-show="activeDesktopMenu === 'what'" class="home-v4-search__menu home-v4-search__menu--what">
          <p>{{ what ? 'Suggestions' : 'Popular experiences' }}</p>
          <button v-for="item in popularWhat" :key="item.title" type="button" @click="selectWhat(item)">
            <i class="bi bi-stars" aria-hidden="true"></i>
            <strong>{{ item.title }}</strong>
            <small class="home-v4-search__category" :class="`is-${item.cat.toLowerCase().replace(/\s+/g, '-')}`">{{ item.cat }}</small>
          </button>
          <span v-if="!popularWhat.length" class="home-v4-search__empty">No matching experiences.</span>
        </div>
      </div>

      <span class="home-v4-search__divider" aria-hidden="true"></span>

      <div class="home-v4-search__field" :class="{ 'is-active': activeDesktopMenu === 'where' }" @click="openDesktopMenu('where')">
        <i class="bi bi-geo-alt" aria-hidden="true"></i>
        <label :for="`${idPrefix}-where`">Where</label>
        <input v-if="where !== 'Near me'" :id="`${idPrefix}-where`" ref="whereInput" v-model="where" type="search" autocomplete="off" placeholder="Town, city or Online" @focus="openDesktopMenu('where')">
        <span v-else class="home-v4-search__near-chip"><i class="bi bi-navigation" aria-hidden="true"></i>Near me</span>
        <button v-if="where" class="home-v4-search__clear" type="button" aria-label="Clear location" @click.stop="where = ''">×</button>
        <div v-show="activeDesktopMenu === 'where'" class="home-v4-search__menu home-v4-search__menu--where">
          <button type="button" @click="useMyLocation"><i class="bi bi-navigation"></i><span><strong>Use my location</strong><small>Find wellness near you</small></span></button>
          <button type="button" @click="selectOnline"><i class="bi bi-wifi"></i><span><strong>Online</strong><small>Join from anywhere</small></span></button>
          <p>{{ where ? 'Matching' : 'Popular' }}</p>
          <button v-for="location in locationResults" :key="location" type="button" @click="selectWhere(location)"><i class="bi bi-building"></i><strong>{{ location }}</strong></button>
          <span v-if="!locationResults.length" class="home-v4-search__empty">No matching locations.</span>
        </div>
      </div>

      <button class="home-v4-search__submit" type="submit" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button>
    </form>

    <form class="home-v4-search__mobile" role="search" @submit.prevent="submit">
      <button class="home-v4-search__mobile-trigger" type="button" @click="openMobileMenu('what')">
        <i class="bi bi-stars" aria-hidden="true"></i>
        <span :class="{ 'is-placeholder': !what }">{{ what || 'Search therapies, events & more' }}</span>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
      </button>
      <button class="home-v4-search__mobile-trigger" type="button" @click="openMobileMenu('where')">
        <i class="bi bi-geo-alt" aria-hidden="true"></i>
        <span v-if="where !== 'Near me'" :class="{ 'is-placeholder': !where }">{{ where || 'Near me, town or Online' }}</span>
        <span v-else class="home-v4-search__near-chip"><i class="bi bi-navigation" aria-hidden="true"></i>Near me</span>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
      </button>
      <button class="home-v4-search__mobile-submit" type="submit">Search</button>
    </form>

    <teleport to="body">
      <div v-if="activeMobileMenu" class="home-v4-search__sheet-backdrop" @click="closeMenus"></div>
      <section v-if="activeMobileMenu" class="home-v4-search__sheet" role="dialog" aria-modal="true" :aria-label="activeMobileMenu === 'what' ? 'Search experiences' : 'Choose location'">
        <div class="home-v4-search__sheet-handle"></div>
        <header><strong>{{ activeMobileMenu === 'what' ? 'What are you looking for?' : 'Where?' }}</strong><button type="button" aria-label="Close" @click="closeMenus">×</button></header>
        <div class="home-v4-search__sheet-input">
          <i class="bi bi-search" aria-hidden="true"></i>
          <input v-if="activeMobileMenu === 'what'" ref="mobileInput" v-model="what" type="search" placeholder="Therapies, events, classes & more">
          <input v-else ref="mobileInput" v-model="where" type="search" placeholder="Town, city or postcode">
        </div>
        <div v-if="activeMobileMenu === 'where'" class="home-v4-search__sheet-list">
          <button type="button" @click="useMyLocation"><i class="bi bi-navigation"></i><span><strong>Use my location</strong><small>Find wellness near you</small></span><i class="bi bi-chevron-right"></i></button>
          <button type="button" @click="selectOnline"><i class="bi bi-wifi"></i><span><strong>Online</strong><small>Join from anywhere</small></span><i class="bi bi-chevron-right"></i></button>
          <button v-for="location in locationResults" :key="location" type="button" @click="selectWhere(location)"><i class="bi bi-building"></i><span><strong>{{ location }}</strong></span><i class="bi bi-chevron-right"></i></button>
        </div>
        <div v-else class="home-v4-search__sheet-list">
          <button v-for="item in mobileWhatResults" :key="item.title" type="button" @click="selectWhat(item)"><span><strong>{{ item.title }}</strong><small>{{ item.cat }}</small></span><i class="bi bi-chevron-right"></i></button>
          <span v-if="!mobileWhatResults.length" class="home-v4-search__empty">No matching experiences.</span>
        </div>
      </section>
    </teleport>
  </div>
</template>

<style>
.home-v4-search { font-family: Inter, var(--bs-font-sans-serif); position:relative; z-index:10; }
.home-v4-search__desktop { display:flex; align-items:center; max-width:900px; height:68px; margin:auto; padding:4px; border:1px solid rgba(155,165,180,.45); border-radius:999px; background:rgba(255,255,255,.97); box-shadow:0 10px 30px rgba(28,39,56,.08); transition:border-color .15s, box-shadow .15s; }
.home-v4-search__desktop.is-active { border-color:rgba(155,165,180,.6); box-shadow:0 0 0 3px rgba(79,147,129,.15), 0 10px 30px rgba(28,39,56,.10); }
.home-v4-search__field { position:relative; display:grid; grid-template-columns:24px minmax(0,1fr) auto; grid-template-rows:17px 22px; align-items:center; column-gap:10px; flex:1; min-width:0; height:100%; padding:8px 16px; border-radius:999px; cursor:text; }
.home-v4-search__field:first-child { flex:1.7; }
.home-v4-search__field:hover { background:rgba(0,0,0,.025); }
.home-v4-search__field.is-active { background:#fff; box-shadow:0 2px 12px rgba(16,24,40,.06); }
.home-v4-search__field.is-active > i { color:#4f9381; }
.home-v4-search__field > i { grid-row:1 / 3; color:#8e9bb0; font-size:17px; }
.home-v4-search__field label { color:#758096; font-size:11px; font-weight:650; line-height:1; }
.home-v4-search__field input { grid-column:2; width:100%; min-width:0; border:0; outline:0; padding:0; color:#1a202c; background:transparent; font-size:14.5px; font-weight:500; }
.home-v4-search__field input::placeholder { color:#818896; font-weight:400; opacity:1; }
.home-v4-search__near-chip { grid-column:2; display:inline-flex; width:max-content; align-items:center; gap:6px; border-radius:999px; padding:4px 10px; color:#2a5e52; background:#e8f5f1; font-size:13px; font-weight:650; }
.home-v4-search__divider { width:1px; height:36px; background:#e5e8ed; }
.home-v4-search__clear { grid-column:3; grid-row:1 / 3; width:20px; height:20px; border:0; border-radius:50%; color:#4a5568; background:#e4e8ee; font-size:17px; line-height:18px; }
.home-v4-search__submit { flex:0 0 auto; width:58px; height:58px; margin-left:4px; border:0; border-radius:50%; color:#fff; background:#101828; box-shadow:0 4px 12px rgba(0,0,0,.18); font-size:20px; transition:transform .15s, background .15s; }
.home-v4-search__submit:hover { background:#1d2939; transform:scale(.96); }
.home-v4-search__menu { position:absolute; top:calc(100% + 8px); left:0; width:100%; min-width:320px; max-height:425px; overflow:auto; border:1px solid rgba(0,0,0,.1); border-radius:16px; background:#fff; box-shadow:0 16px 48px rgba(16,24,40,.14); z-index:30; }
.home-v4-search__menu--where { min-width:280px; }
.home-v4-search__menu p { margin:0; padding:12px 16px 5px; color:#98a2b3; font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
.home-v4-search__menu button, .home-v4-search__sheet-list button { display:flex; width:100%; align-items:center; gap:12px; padding:10px 16px; border:0; border-top:1px solid #f4f5f7; color:#1a202c; background:#fff; text-align:left; }
.home-v4-search__menu button:hover, .home-v4-search__sheet-list button:hover { background:#f0faf7; }
.home-v4-search__menu button > span, .home-v4-search__sheet-list button > span { display:grid; gap:2px; flex:1; }
.home-v4-search__menu strong, .home-v4-search__sheet-list strong { font-size:14px; font-weight:650; }
.home-v4-search__menu small, .home-v4-search__sheet-list small { color:#98a2b3; font-size:12px; }
.home-v4-search__menu button > i:first-child { width:28px; color:#8e9bb0; text-align:center; }
.home-v4-search__menu--what button > i { color:#4f9381; font-size:13px; opacity:.6; }
.home-v4-search__menu--what button > strong { flex:1; color:#1a202c; font-size:13.5px; font-weight:500; }
.home-v4-search__menu--where button > strong { color:#1a202c; font-size:13.5px; font-weight:500; }
.home-v4-search__category { flex:0 0 auto; border-radius:999px; padding:2px 8px; background:#e8f5f1; color:#2a5e52 !important; font-size:10px !important; font-weight:700; }
.home-v4-search__category.is-class { background:#eff6ff; color:#1d4ed8 !important; }
.home-v4-search__category.is-session { background:#eef2ff; color:#4338ca !important; }
.home-v4-search__category.is-event { background:#fffbeb; color:#b45309 !important; }
.home-v4-search__category.is-retreat { background:#fff1f2; color:#be123c !important; }
.home-v4-search__category.is-workshop { background:#faf5ff; color:#7e22ce !important; }
.home-v4-search__category.is-gift { background:#fefce8; color:#a16207 !important; }
.home-v4-search__empty { display:block; padding:16px; color:#98a2b3; font-size:13px; }
.home-v4-search__mobile { display:none; }
.home-v4-search__sheet-backdrop { position:fixed; inset:0; z-index:4000; background:rgba(16,24,40,.45); backdrop-filter:blur(2px); }
.home-v4-search__sheet { position:fixed; right:0; bottom:0; left:0; z-index:4001; display:flex; flex-direction:column; max-height:min(78dvh, 680px); border-radius:24px 24px 0 0; background:#fff; box-shadow:0 -20px 60px rgba(16,24,40,.2); }
.home-v4-search__sheet-handle { width:38px; height:4px; margin:10px auto 4px; border-radius:99px; background:#dce1e7; }
.home-v4-search__sheet header { display:flex; align-items:center; justify-content:space-between; padding:10px 20px 14px; color:#1a202c; font-size:18px; }
.home-v4-search__sheet header button { width:32px; height:32px; border:0; border-radius:50%; color:#697386; background:#f2f4f7; font-size:22px; line-height:1; }
.home-v4-search__sheet-input { position:relative; margin:0 20px 12px; }
.home-v4-search__sheet-input > i { position:absolute; top:50%; left:15px; color:#697386; transform:translateY(-50%); }
.home-v4-search__sheet-input input { width:100%; height:52px; border:1px solid #dce1e7; border-radius:14px; outline:0; padding:0 14px 0 42px; color:#1a202c; font-size:15px; }
.home-v4-search__sheet-input input:focus { border-color:#4f9381; box-shadow:0 0 0 3px rgba(79,147,129,.16); }
.home-v4-search__sheet-list { overflow-y:auto; padding-bottom:max(12px, env(safe-area-inset-bottom)); }
.home-v4-search__sheet-list button { min-height:68px; gap:14px; padding:12px 24px; }
.home-v4-search__sheet-list button > i:first-child { display:inline-flex; width:38px; height:38px; align-items:center; justify-content:center; border-radius:50%; color:#4f9381; background:#e8f5f1; }
.home-v4-search__sheet-list button > i:last-child { color:#b8c0cc; font-size:14px; }
.home-v4-search__sheet-list strong { font-size:15px; }
@media (max-width:767.98px) {
  .home-v4-search__desktop { display:none; }
  .home-v4-search__mobile { display:grid; gap:10px; max-width:520px; margin:auto; }
  .home-v4-search__mobile-trigger { display:flex; width:100%; height:60px; align-items:center; gap:14px; padding:0 19px; border:1px solid rgba(117,128,150,.42); border-radius:999px; color:#1a202c; background:#fff; box-shadow:0 8px 24px rgba(28,39,56,.09); text-align:left; }
  .home-v4-search__mobile-trigger > i:first-child { color:#8e9bb0; font-size:18px; }
  .home-v4-search__mobile-trigger span { flex:1; overflow:hidden; font-size:15px; font-weight:500; text-overflow:ellipsis; white-space:nowrap; }
  .home-v4-search__mobile-trigger .home-v4-search__near-chip { display:inline-flex; flex:0 1 auto; width:max-content; align-items:center; color:#2a5e52; }
  .home-v4-search__mobile-trigger span.is-placeholder { color:#687283; font-weight:400; }
  .home-v4-search__mobile-trigger > i:last-child { color:#b8c0cc; }
  .home-v4-search__mobile-submit { height:52px; border:0; border-radius:999px; color:#fff; background:#101828; font-size:15px; font-weight:650; }
}
</style>
