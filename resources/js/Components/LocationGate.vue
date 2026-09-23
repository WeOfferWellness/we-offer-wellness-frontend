<script setup>
import { ref, onMounted } from 'vue'

const open = ref(false)
const status = ref('idle') // 'idle' | 'locating' | 'saving'
const error = ref('')
const promptCookieName = 'wow_location_prompt_v2'
const rememberDays = 3650

function cookieGet(name){
  const m = document.cookie.match('(^|;)\\s*'+name+'\\s*=\\s*([^;]+)');
  return m ? decodeURIComponent(m.pop()) : ''
}
function cookieSet(name, value, days){
  const maxAge = days ? days*24*60*60 : 60*60*24*365*5
  document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax`
}
function csrfToken(){
  try { return document.querySelector('meta[name="csrf-token"]').content || window.__csrfToken || '' }
  catch { return window.__csrfToken || '' }
}

function markPromptSeen(){
  cookieSet(promptCookieName, '1', rememberDays)
  cookieSet('wow_geo_done', '1', rememberDays)
}

function hasStoredLocation(){
  return Boolean(cookieGet('wow_lat') && cookieGet('wow_lng'))
}

function isNearMePage(){
  return window.location.pathname === '/near-me'
}

function shouldOpen(){
  if (isNearMePage() && !hasStoredLocation()) {
    try { return sessionStorage.getItem('wow_near_me_prompted') !== '1' } catch { return true }
  }
  return cookieGet(promptCookieName) !== '1'
}

async function save(data){
  status.value = 'saving'
  try {
    const res = await fetch('/api/geo', { method:'POST', headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() }, body: JSON.stringify(data) })
    if (!res.ok) throw new Error('geo '+res.status)
  } catch(e){ /* ignore */ }
  markPromptSeen()
  open.value = false
  window.location.reload()
}

async function useMyLocation(){
  error.value = ''
  status.value = 'locating'
  if (!('geolocation' in navigator)) { status.value='idle'; error.value = 'Geolocation not available'; return }
  navigator.geolocation.getCurrentPosition(async (pos) => {
    const lat = pos.coords.latitude, lng = pos.coords.longitude
    let city='', region='', country='', postcode='', district='', full_name=''
    try {
      const key = window.WOW_MAPS_KEY || ''
      if (key) {
        const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json`)
        url.searchParams.set('access_token', key)
        url.searchParams.set('limit', '1')
        const res = await fetch(url)
        const json = await res.json()
        const feat = json?.features?.[0]
        if (feat) {
          const comps = feat?.context || []
          city = (comps.find(c=>c.id?.startsWith('place'))?.text) || (comps.find(c=>c.id?.startsWith('locality'))?.text) || ''
          region = (comps.find(c=>c.id?.startsWith('region'))?.text) || ''
          country = (comps.find(c=>c.id?.startsWith('country'))?.text) || ''
          postcode = (comps.find(c=>c.id?.startsWith('postcode'))?.text) || ''
          district = (comps.find(c=>c.id?.startsWith('district'))?.text) || ''
          full_name = feat.place_name || city || 'Current location'
        }
      }
    } catch {}
    const location = { lat, lng, city, region, country, postcode, district, full_name }
    await save({ ...location, mode: 'mixed' })
    window.dispatchEvent(new CustomEvent('wow:location-updated', { detail: { location } }))
  }, () => { status.value='idle'; error.value = 'We couldn\'t get your location.' }, { enableHighAccuracy:false, timeout:6000, maximumAge:60000 })
}

function allowLocation(){
  void useMyLocation()
}

function skipLocation(){
  if (isNearMePage()) {
    try { sessionStorage.setItem('wow_near_me_prompted', '1') } catch {}
  }
  markPromptSeen()
  open.value = false
}

onMounted(() => {
  open.value = shouldOpen()
})
</script>

<template>
  <div v-if="open" class="wow-location-banner" data-location-banner aria-hidden="false">
    <div class="wow-location-banner__panel" role="dialog" aria-modal="true" aria-labelledby="wowLocationTitle">
      <div class="wow-location-banner__simple">
        <p class="wow-location-banner__eyebrow">Your location</p>
        <h2 id="wowLocationTitle">Help us find locations near you</h2>
        <p>Share your location and we’ll show therapies, classes and events close to you first. We’ll remember your choice for future visits.</p>
        <div class="wow-location-banner__actions actions">
          <button type="button" class="wow-location-btn wow-location-btn--primary" :disabled="status!=='idle'" @click="allowLocation">
            <span v-if="status==='locating'">Locating…</span>
            <span v-else>Allow and remember</span>
          </button>
          <button type="button" class="wow-location-btn" @click="skipLocation">Not now</button>
        </div>
        <div v-if="error" class="wow-location-banner__error">{{ error }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wow-location-banner{
  position:fixed;
  inset:0;
  z-index:1200;
  display:grid;
  place-items:center;
  width:100%;
  padding:20px;
  background:rgba(11,48,40,.28);
  backdrop-filter:blur(4px);
  font-family:'Instrument Sans',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
.wow-location-banner[hidden]{ display:none !important; }
.wow-location-banner__panel{
  width:min(520px, 100%);
  background:#fff;
  color:#17201d;
  border-radius:8px;
  border:1px solid #dce4e0;
  box-shadow:0 28px 90px rgba(11,48,40,.22);
  padding:32px;
}
.wow-location-banner__eyebrow{
  text-transform:uppercase;
  letter-spacing:.24em;
  font-size:11px;
  color:#4f9482;
  font-weight:700;
  margin:0 0 8px;
}
.wow-location-banner__simple h2{
  margin:0 0 8px;
  color:#0b3028;
  font-family:'Playfair Display',serif;
  font-size:clamp(30px,5vw,42px);
  font-weight:500;
  line-height:1;
  letter-spacing:-.04em;
}
.wow-location-banner__simple p{
  margin:0 0 16px;
  font-size:14px;
  line-height:1.6;
  color:#68736f;
}
.wow-location-banner__actions.actions{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.wow-location-banner__actions .wow-location-btn{
  flex:1 1 auto;
  min-width:110px;
}
.wow-location-btn{
  height:42px;
  min-height:42px;
  border-radius:999px;
  font-size:13px;
  font-weight:600;
  border:1px solid #cfd9d5;
  background:#fff;
  color:#17201d;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;
  box-shadow:none;
  padding:0 18px;
  transition:background .2s ease, color .2s ease, border-color .2s ease;
}
.wow-location-btn:hover,
.wow-location-btn:focus-visible{
  background:#f3f7f5;
  color:#0b3028;
  border-color:#9eafa9;
  outline:none;
}
.wow-location-btn--primary{
  background:#4f9482;
  color:#fff;
  border-color:#4f9482;
  box-shadow:0 10px 22px rgba(79,148,130,.2);
}
.wow-location-btn:disabled{
  opacity:.7;
  cursor:not-allowed;
}
.wow-location-banner__error{
  margin-top:12px;
  color:#b91c1c;
  font-size:12px;
}
@media (max-width: 640px){
  .wow-location-banner__panel{ padding:26px 20px; }
  .wow-location-banner__actions .wow-location-btn{
    width:100%;
  }
}
</style>
