@once
<style>
  .product-v4-1-ghost-card-scope{
    --offering-ghost-base:#edf0f2;
    --offering-ghost-highlight:#f8fafb;
    --offering-ghost-border:rgba(16,24,40,.1);
    --offering-ghost-green:#eaf5f1;
    --offering-ghost-gold:#fff0d5;
    --offering-ghost-blue:#e8f0ff;
  }

  .product-v4-1-ghost-card-scope .product-v4-1-ghost-card{
    display:flex;
    flex-direction:column;
    width:100%;
    min-width:290px;
    max-width:300px;
    height:430px;
    min-height:430px;
    overflow:hidden;
    border:1px solid var(--offering-ghost-border);
    border-radius:13px;
    background:#fff;
    box-shadow:0 4px 16px rgba(16,24,40,.05);
  }

  .product-v4-1-ghost-card__media{
    position:relative;
    height:145px;
    flex-shrink:0;
    overflow:hidden;
    background:linear-gradient(135deg, #e9eef0, #f4f7f8);
  }

  .product-v4-1-ghost-card__media::after,
  .product-v4-1-ghost-card__fill::after{
    position:absolute;
    inset:0;
    content:"";
    transform:translateX(-120%);
    background:linear-gradient(100deg, transparent 0%, rgba(255,255,255,.18) 30%, rgba(255,255,255,.78) 50%, rgba(255,255,255,.18) 70%, transparent 100%);
    animation:offeringGhostShimmer 1.45s ease-in-out infinite;
  }

  .product-v4-1-ghost-card__signal,
  .product-v4-1-ghost-card__tag,
  .product-v4-1-ghost-card__fill{
    position:relative;
    display:block;
    overflow:hidden;
    background:var(--offering-ghost-base);
  }

  .product-v4-1-ghost-card__signal{
    position:absolute;
    top:10px;
    left:10px;
    width:104px;
    height:26px;
    border-radius:999px;
    background:var(--offering-ghost-gold);
  }

  .product-v4-1-ghost-card__tags{
    position:absolute;
    bottom:10px;
    left:10px;
    display:flex;
    gap:4px;
  }

  .product-v4-1-ghost-card__tag{
    width:66px;
    height:22px;
    border-radius:999px;
    background:var(--offering-ghost-gold);
  }

  .product-v4-1-ghost-card__tag--type{
    width:54px;
    background:var(--offering-ghost-blue);
  }

  .product-v4-1-ghost-card__body{
    display:flex;
    flex:1;
    flex-direction:column;
    gap:7px;
    min-height:0;
    padding:11px 13px 10px;
  }

  .product-v4-1-ghost-card__title{
    width:88%;
    height:43px;
    border-radius:7px;
  }

  .product-v4-1-ghost-card__provider{
    width:42%;
    height:12px;
    border-radius:999px;
  }

  .product-v4-1-ghost-card__rating{
    display:flex;
    align-items:center;
    gap:7px;
    height:14px;
  }

  .product-v4-1-ghost-card__stars{
    width:56px;
    height:12px;
    border-radius:4px;
    background:#f5d978;
  }

  .product-v4-1-ghost-card__rating-copy{
    width:108px;
    height:12px;
    border-radius:999px;
  }

  .product-v4-1-ghost-card__location{
    width:58%;
    height:12px;
    border-radius:999px;
  }

  .product-v4-1-ghost-card__description{
    display:grid;
    gap:5px;
    margin-top:2px;
  }

  .product-v4-1-ghost-card__description .product-v4-1-ghost-card__fill{
    height:10px;
    border-radius:999px;
  }

  .product-v4-1-ghost-card__description .product-v4-1-ghost-card__fill:nth-child(2){ width:84%; }
  .product-v4-1-ghost-card__description .product-v4-1-ghost-card__fill:nth-child(3){ width:68%; }

  .product-v4-1-ghost-card__availability{
    display:flex;
    align-items:center;
    gap:6px;
    margin-top:auto;
    padding:5px 8px;
    border-radius:7px;
    background:var(--offering-ghost-green);
  }

  .product-v4-1-ghost-card__availability-icon{
    width:11px;
    height:11px;
    border-radius:3px;
    background:#a6cfc3;
  }

  .product-v4-1-ghost-card__availability-copy{
    width:126px;
    height:12px;
    border-radius:999px;
    background:#d5ebe4;
  }

  .product-v4-1-ghost-card__footer{
    display:flex;
    flex-shrink:0;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    padding:9px 13px 11px;
    border-top:1px solid #edf0f2;
  }

  .product-v4-1-ghost-card__price{ display:grid; gap:5px; }
  .product-v4-1-ghost-card__price-label{ width:32px; height:10px; border-radius:999px; }
  .product-v4-1-ghost-card__price-value{ width:74px; height:21px; border-radius:999px; }

  .product-v4-1-ghost-card__button{
    width:104px;
    height:36px;
    border-radius:4px;
    background:#d9ece6;
  }

  @keyframes offeringGhostShimmer { to { transform:translateX(120%); } }

  @media (max-width:560px){
    .product-v4-1-ghost-card-scope .product-v4-1-ghost-card{
      min-width:0;
      max-width:none;
      height:360px;
      min-height:360px;
    }
    .product-v4-1-ghost-card__media{ height:138px; }
    .product-v4-1-ghost-card__body{ padding:10px 11px 9px; }
    .product-v4-1-ghost-card__description{ display:none; }
    .product-v4-1-ghost-card__footer{ padding:9px 11px 10px; }
    .product-v4-1-ghost-card__button{ height:34px; width:96px; }
  }
</style>
@endonce

<div class="product-v4-1-ghost-card-scope" aria-busy="true" aria-label="Loading offering">
  <article class="product-v4-1-ghost-card" aria-hidden="true">
    <div class="product-v4-1-ghost-card__media">
      <span class="product-v4-1-ghost-card__signal"></span>
      <div class="product-v4-1-ghost-card__tags">
        <span class="product-v4-1-ghost-card__tag"></span>
        <span class="product-v4-1-ghost-card__tag product-v4-1-ghost-card__tag--type"></span>
      </div>
    </div>
    <div class="product-v4-1-ghost-card__body">
      <span class="product-v4-1-ghost-card__fill product-v4-1-ghost-card__title"></span>
      <span class="product-v4-1-ghost-card__fill product-v4-1-ghost-card__provider"></span>
      <div class="product-v4-1-ghost-card__rating">
        <span class="product-v4-1-ghost-card__stars"></span>
        <span class="product-v4-1-ghost-card__fill product-v4-1-ghost-card__rating-copy"></span>
      </div>
      <span class="product-v4-1-ghost-card__fill product-v4-1-ghost-card__location"></span>
      <div class="product-v4-1-ghost-card__description">
        <span class="product-v4-1-ghost-card__fill"></span>
        <span class="product-v4-1-ghost-card__fill"></span>
        <span class="product-v4-1-ghost-card__fill"></span>
      </div>
      <div class="product-v4-1-ghost-card__availability">
        <span class="product-v4-1-ghost-card__availability-icon"></span>
        <span class="product-v4-1-ghost-card__availability-copy"></span>
      </div>
    </div>
    <footer class="product-v4-1-ghost-card__footer">
      <div class="product-v4-1-ghost-card__price">
        <span class="product-v4-1-ghost-card__fill product-v4-1-ghost-card__price-label"></span>
        <span class="product-v4-1-ghost-card__fill product-v4-1-ghost-card__price-value"></span>
      </div>
      <span class="product-v4-1-ghost-card__fill product-v4-1-ghost-card__button"></span>
    </footer>
  </article>
</div>
