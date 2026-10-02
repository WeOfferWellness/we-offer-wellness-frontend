@extends('layouts.app')

@section('body-class', 'wellness-match-results-page')

@push('head')
<meta name="robots" content="noindex,follow">
<meta name="description" content="Your personalised We Offer Wellness recommendations.">
@endpush

@section('content')
<main class="wellness-results" data-wellness-results>
  <section class="wellness-results__hero">
    <div class="wellness-results__orb wellness-results__orb--one" aria-hidden="true"></div>
    <div class="wellness-results__orb wellness-results__orb--two" aria-hidden="true"></div>
    <div class="wellness-results__hero-inner">
      <p class="wellness-results__eyebrow">YOUR WELLNESS MATCH</p>
      <h1>Ta-Da, Your Results!</h1>
      <p class="wellness-results__lead">Explore our handpicked recommendations, tailored to your preferences</p>
      <button class="wellness-results__reset" type="button" data-wellness-reset>Changed your mind? <strong>Reset and take the quiz again</strong></button>
      <div class="wellness-results__preferences" data-wellness-preferences hidden></div>
    </div>
  </section>

  <section class="wellness-results__content" aria-live="polite">
    <div class="product-showcase-heading wellness-results__heading">
      <div class="product-showcase-heading__copy"><div class="kicker">Chosen for you</div><h2>Your strongest matches</h2>
      <p data-wellness-summary>We’re comparing your answers with live wellness experiences.</p>
      </div>
    </div>
    <div class="wow410-grid wellness-results__grid" data-wellness-grid><p class="wellness-results__loading">Loading recommendations…</p></div>
    <div class="wellness-results__empty" data-wellness-empty hidden>
      <h2>Let’s find your matches</h2>
      <p>Complete the quick finder so we can tailor this page to you.</p>
      <button class="btn-wow btn-wow--primary btn-wow--md" type="button" data-wellness-reset>Start the wellness finder</button>
    </div>
  </section>
</main>

<style>
.wellness-match-results-page main{overflow:hidden}.wellness-results{--match-green:#549483;--match-dark:#101828;--match-copy:#475467;background:#fbfcfb;color:var(--match-dark);min-height:75vh}.wellness-results__hero{position:relative;isolation:isolate;overflow:hidden;padding:86px 24px 78px;background:linear-gradient(145deg,#f8f5ee 0%,#f2f8f5 48%,#fff 100%);border-bottom:1px solid #dfe8e4}.wellness-results__hero-inner{position:relative;z-index:1;width:min(900px,100%);margin:auto;text-align:center}.wellness-results__eyebrow{margin:0 0 16px;color:#3e7467;font-size:11px;font-weight:750;letter-spacing:.22em}.wellness-results h1{margin:0;font-family:"Playfair Display",Georgia,serif;font-size:clamp(44px,6vw,78px);font-weight:750;line-height:1;letter-spacing:-.045em}.wellness-results__lead{max-width:680px;margin:22px auto 0;color:var(--match-copy);font-size:clamp(16px,2vw,20px);line-height:1.6}.wellness-results__reset{margin-top:22px;padding:8px 0;border:0;border-bottom:1px solid transparent;background:transparent;color:#52635f;font-size:13px;cursor:pointer}.wellness-results__reset:hover,.wellness-results__reset:focus-visible{border-bottom-color:#3e7467;color:#254d43;outline:0}.wellness-results__preferences{display:flex;justify-content:center;flex-wrap:wrap;gap:8px;margin:30px auto 0}.wellness-results__preference{padding:7px 11px;border:1px solid #cfe1db;border-radius:999px;background:rgba(255,255,255,.72);color:#315e53;font-size:12px;font-weight:650}.wellness-results__orb{position:absolute;border-radius:50%;filter:blur(1px);z-index:-1}.wellness-results__orb--one{width:430px;height:430px;right:-170px;top:-190px;background:rgba(84,148,131,.15)}.wellness-results__orb--two{width:330px;height:330px;left:-170px;bottom:-210px;background:rgba(218,195,155,.2)}.wellness-results__content{width:min(1240px,calc(100% - 40px));margin:auto;padding:64px 0 90px}.wellness-results__heading{display:flex;align-items:end;justify-content:space-between;gap:32px;margin-bottom:28px}.wellness-results__heading h2{margin:0;font-family:"Playfair Display",Georgia,serif;font-size:clamp(30px,4vw,45px);letter-spacing:-.03em}.wellness-results__heading>p{max-width:440px;margin:0;color:#667085;line-height:1.6}.wellness-results__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}.wellness-result-card{display:flex;min-width:0;flex-direction:column;background:#fff;border:1px solid #dfe5e3;color:inherit;text-decoration:none;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.wellness-result-card:hover{transform:translateY(-4px);border-color:#9cbdb4;box-shadow:0 20px 48px rgba(16,24,40,.1)}.wellness-result-card__image{position:relative;aspect-ratio:1.45;overflow:hidden;background:#edf2f0}.wellness-result-card__image img{width:100%;height:100%;object-fit:cover;transition:transform .35s ease}.wellness-result-card:hover .wellness-result-card__image img{transform:scale(1.035)}.wellness-result-card__match{position:absolute;left:12px;top:12px;padding:7px 9px;background:rgba(16,24,40,.86);color:#fff;font-size:10px;font-weight:750;letter-spacing:.08em;text-transform:uppercase}.wellness-result-card__body{display:flex;flex:1;flex-direction:column;padding:19px}.wellness-result-card__meta{display:flex;justify-content:space-between;gap:10px;color:#667085;font-size:11px}.wellness-result-card h3{margin:10px 0 6px;font-family:"Playfair Display",Georgia,serif;font-size:23px;line-height:1.16}.wellness-result-card__vendor{margin:0;color:#667085;font-size:12px}.wellness-result-card__reasons{display:flex;flex-wrap:wrap;gap:6px;margin:18px 0 0}.wellness-result-card__reason{padding:5px 8px;background:#eef6f3;color:#315e53;font-size:10px;font-weight:650}.wellness-result-card__footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;padding-top:20px}.wellness-result-card__price{font-size:13px;font-weight:750}.wellness-result-card__arrow{color:#3e7467;font-size:20px}.wellness-result-card--loading{min-height:390px;overflow:hidden}.wellness-result-card--loading span{display:block;height:235px;background:#eef1f0}.wellness-result-card--loading i,.wellness-result-card--loading b{display:block;height:14px;margin:22px 18px 0;background:#eef1f0}.wellness-result-card--loading b{width:58%;margin-top:12px}.wellness-result-card--loading:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.75),transparent);animation:matchShimmer 1.25s infinite;transform:translateX(-100%)}.wellness-result-card--loading{position:relative}.wellness-results__empty{text-align:center;padding:70px 20px;background:#fff;border:1px solid #dfe5e3}.wellness-results__empty h2{font-family:"Playfair Display",Georgia,serif;font-size:38px;margin:0 0 10px}.wellness-results__empty p{color:#667085;margin:0 0 22px}@keyframes matchShimmer{to{transform:translateX(100%)}}@media(max-width:900px){.wellness-results__grid{grid-template-columns:repeat(2,minmax(0,1fr))}.wellness-results__heading{align-items:flex-start;flex-direction:column}}@media(max-width:600px){.wellness-results__hero{padding:62px 18px 56px}.wellness-results__content{width:calc(100% - 28px);padding:44px 0 68px}.wellness-results__grid{grid-template-columns:1fr}.wellness-results__heading{gap:12px}.wellness-results h1{font-size:45px}}
/* Results use the homepage showcase geometry and typography. */
.wellness-results__content{width:min(1240px,calc(100% - 40px));padding-top:56px}
.wellness-results__heading{margin-bottom:28px}
.wellness-results__heading .product-showcase-heading__copy h2{font-size:clamp(44px,5.5vw,76px);font-weight:500;line-height:.94;letter-spacing:-.06em}
.wellness-results__heading .product-showcase-heading__copy p{max-width:680px;margin:14px 0 0;color:#596275;font-size:17px;line-height:1.58}
.wellness-results__grid{grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}
@media(max-width:1100px){.wellness-results__grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
@media(max-width:900px){.wellness-results__grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:700px){.wellness-results__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:520px){.wellness-results__grid{grid-template-columns:1fr}}
</style>

<script data-cfasync="false">
(() => {
  const root=document.querySelector('[data-wellness-results]');if(!root)return;
  const grid=root.querySelector('[data-wellness-grid]'),empty=root.querySelector('[data-wellness-empty]'),summary=root.querySelector('[data-wellness-summary]'),preferences=root.querySelector('[data-wellness-preferences]');
  const esc=(value='')=>String(value).replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
  const uuid=()=>crypto.randomUUID?.()||'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,char=>{const value=Math.random()*16|0;return(char==='x'?value:(value&3|8)).toString(16)});
  const trackBehaviour=(eventType,metadata={},offering=null)=>fetch('https://studio.weofferwellness.co.uk/api/behaviour/events',{method:'POST',credentials:'include',keepalive:true,headers:{Accept:'application/json','Content-Type':'application/json'},body:JSON.stringify({events:[{event_uuid:uuid(),event_type:eventType,occurred_at:new Date().toISOString(),sequence:Date.now(),page_context:location.pathname,metadata:{surface:'wellness_match_results',page_type:'wellness_match_results',device_class:matchMedia('(max-width:767px)').matches?'mobile':matchMedia('(max-width:1024px)').matches?'tablet':'desktop',...metadata},...(offering?{offering}: {})}]})}).catch(()=>{});
  const stored=(()=>{try{return JSON.parse(sessionStorage.getItem('wowWellnessMatchResults')||'null')}catch{return null}})();
  const openFinder=()=>{sessionStorage.removeItem('wowWellnessMatchResults');for(let index=localStorage.length-1;index>=0;index--){const key=localStorage.key(index);if(key?.startsWith('wowWellnessMatchProgress:'))localStorage.removeItem(key)}trackBehaviour('wellness_match_reset',{interaction:'results_start_again'});const open=()=>{const trigger=document.querySelector('[data-wow-popup-trigger="wellness-match"]');if(trigger&&!trigger.closest('[hidden]')){trigger.click();return true}return false};if(!open()){let count=0;const timer=setInterval(()=>{if(open()||count++>30)clearInterval(timer)},100)}};
  root.querySelectorAll('[data-wellness-reset]').forEach(button=>button.addEventListener('click',openFinder));
  if(!stored?.answers?.length){grid.hidden=true;empty.hidden=false;summary.textContent='Complete the finder to see recommendations selected around your needs.';return}
  const queryParams=new URLSearchParams(location.search),apiFilterKeys=['type_id','type','category_id','category','subcategory_id','subcategory','need','marketplace_tag','occasion','audience','min_price','max_price'],cardsUrl=new URL('/api/home/rails',window.location.origin);cardsUrl.searchParams.set('section','matches');apiFilterKeys.filter(key=>queryParams.get(key)).forEach(key=>cardsUrl.searchParams.set(key,queryParams.get(key)));
  Promise.resolve().then(async()=>{const response=await fetch(cardsUrl,{headers:{Accept:'text/html'}});if(!response.ok)throw new Error('Unable to load matched offerings');const html=(await response.text()).trim();if(!html)throw new Error('No matched offerings');grid.innerHTML=html;const count=grid.querySelectorAll('article').length,preferencesValues=stored.answers.flatMap(answer=>answer.values||[]).filter(Boolean).slice(0,8);preferences.innerHTML=preferencesValues.map(value=>`<span class="wellness-results__preference">${esc(value)}</span>`).join('');preferences.hidden=!preferences.children.length;summary.textContent=`${count} live recommendations from the unified wellness catalogue.`;grid.hidden=false;empty.hidden=true;trackBehaviour('wellness_match_results_viewed',{result_count:count,delivery_mode:'homepage_card_renderer'})}).catch(()=>{grid.hidden=true;empty.hidden=false;empty.querySelector('h2').textContent='We couldn’t load your matches';empty.querySelector('p').textContent='Please try again. Your quiz answers are still saved in this browser.'});
  grid.addEventListener('click',event=>{const card=event.target.closest('article');if(!card)return;const link=card.querySelector('a[href]');trackBehaviour('wellness_match_recommendation_opened',{recommendation_title:card.querySelector('h3')?.textContent?.trim()||'',href:link?.href||''});});
})();
</script>
@endsection
