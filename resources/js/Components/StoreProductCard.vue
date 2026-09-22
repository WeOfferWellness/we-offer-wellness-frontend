<script setup>
import { computed } from 'vue'
import { useCart } from '@/stores/cart'

const props = defineProps({ product: { type: Object, required: true }, fluid: { type: Boolean, default: false } })
const cart = useCart()
const product = computed(() => props.product || {})
const price = computed(() => Number(product.value.price || 0))
const money = computed(() => new Intl.NumberFormat('en-GB', { style: 'currency', currency: product.value.currency || 'GBP' }).format(price.value))
const href = computed(() => product.value.url || `/products/${product.value.slug}`)
const analyticsItem = computed(() => JSON.stringify({
  id: `store-${product.value.id || ''}`,
  product_id: product.value.id || '',
  title: product.value.title || 'Product',
  price: price.value,
  currency: product.value.currency || 'GBP',
  source_version: 'store',
  catalogue_type: 'physical_product',
}))
function addToCart() {
  cart.add({ id: `store-${product.value.id}`, product_id: product.value.id, source_version: 'store', title: product.value.title, price: price.value, image: product.value.image, url: href.value, meta: { type: 'physical', product_kind: 'physical_product', store_product_id: product.value.id, source_version: 'store' } })
}
</script>

<template>
  <article class="wow49-store-card" :class="{ 'wow49-store-card--fluid': fluid }" :aria-label="`Product card ${product.title}`" :data-product-id="product.id" data-source-version="store" :data-wow-analytics-item="analyticsItem" :data-ranking-request-id="product.ranking_request_id || null">
    <a :href="href" class="wow49-store-card__link" :aria-label="`Open ${product.title}`"></a>
    <div class="wow49-store-card__media"><img v-if="product.image" :src="product.image" :alt="product.title" loading="lazy"><span>Ships to you</span><div><b>Physical product</b><b class="is-type">Store</b></div></div>
    <div class="wow49-store-card__body"><h3>{{ product.title }}</h3><p>{{ product.brand || 'We Offer Wellness' }}</p><p class="wow49-store-card__summary">{{ product.summary || 'Physical product delivered directly to you.' }}</p><span class="wow49-store-card__availability">Secure checkout</span></div>
    <footer><div><small>Price</small><strong>{{ money }}</strong></div><div><button type="button" @click.prevent.stop="addToCart">ADD</button><a :href="href">VIEW PRODUCT</a></div></footer>
  </article>
</template>

<style scoped>
.wow49-store-card{position:relative;display:flex;flex-direction:column;width:100%;min-width:290px;max-width:300px;height:430px;overflow:hidden;border:1px solid rgba(16,24,40,.1);border-radius:13px;background:#fff;box-shadow:0 4px 16px rgba(16,24,40,.05);transition:transform 180ms ease,border-color 180ms ease,box-shadow 180ms ease}.wow49-store-card--fluid{max-width:300px}.wow49-store-card:hover,.wow49-store-card:focus-within{transform:translateY(-2px);border-color:rgba(79,147,129,.42);box-shadow:0 20px 48px rgba(16,24,40,.085)}.wow49-store-card__link{position:absolute;inset:0;z-index:1}.wow49-store-card__media{position:relative;height:145px;flex:0 0 145px;overflow:hidden;background:#eef2f4}.wow49-store-card__media img{width:100%;height:100%;object-fit:cover;transition:transform 240ms ease}.wow49-store-card:hover .wow49-store-card__media img{transform:scale(1.035)}.wow49-store-card__media>span{position:absolute;top:10px;left:10px;padding:5px 10px;border-radius:999px;background:rgba(255,247,237,.94);color:#b54708;font-size:11px;font-weight:700}.wow49-store-card__media>div{position:absolute;bottom:10px;left:10px;display:flex;gap:4px}.wow49-store-card__media b{height:22px;padding:4px 7px;border:1px solid rgba(240,200,121,.9);border-radius:999px;background:rgba(255,229,179,.96);color:#6f4b10;font-size:10px}.wow49-store-card__media .is-type{border-color:rgba(199,216,251,.9);background:rgba(232,240,255,.96);color:#254a85}.wow49-store-card__body{display:flex;flex:1;flex-direction:column;gap:6px;padding:11px 13px}.wow49-store-card__body h3{display:-webkit-box;min-height:2.4em;margin:0;overflow:hidden;font-size:18px;font-weight:300;line-height:1.2;letter-spacing:-.04em;-webkit-box-orient:vertical;-webkit-line-clamp:2}.wow49-store-card__body p{margin:0;color:#667085;font-size:12px}.wow49-store-card__summary{display:-webkit-box;overflow:hidden;line-height:1.45;-webkit-box-orient:vertical;-webkit-line-clamp:3}.wow49-store-card__availability{margin-top:auto;padding:5px 8px;border-radius:7px;background:#f6f8fa;color:#344054;font-size:12px;font-weight:600}.wow49-store-card footer{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 13px 11px;border-top:1px solid #edf0f2}.wow49-store-card small{display:block;color:#98a2b3;font-size:11px}.wow49-store-card strong{font-size:20px;font-weight:400;letter-spacing:-.05em}.wow49-store-card footer>div:last-child{display:flex;gap:5px}.wow49-store-card button,.wow49-store-card footer a{position:relative;z-index:3;height:36px;padding:0 10px;border:1px solid #4f9381;border-radius:4px;background:#fff;color:#2f6f60;font:400 11px inherit;line-height:34px;text-decoration:none;cursor:pointer}.wow49-store-card footer a{border:0;background:#4f9381;color:#fff}@media(max-width:560px){.wow49-store-card{min-width:0;height:360px}.wow49-store-card__media{height:138px;flex-basis:138px}.wow49-store-card__body h3{font-size:15px}.wow49-store-card footer{padding:9px 11px 10px}.wow49-store-card strong{font-size:18px}.wow49-store-card button,.wow49-store-card footer a{height:34px;line-height:32px;font-size:10px}}
</style>
