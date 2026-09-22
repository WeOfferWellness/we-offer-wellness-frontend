@once
<style>
  .product-v4-1-ghost-card-scope {
    --wow410-skeleton: #edf0f2;
    --wow410-skeleton-soft: #f5f7f8;
    --wow410-skeleton-green: #eaf5f1;
    --wow410-skeleton-gold: #fff0d5;
    --wow410-skeleton-blue: #e8f0ff;
    --wow410-skeleton-shimmer: rgba(255, 255, 255, .72);
    display: block;
    width: 100%;
    min-width: 0;
  }

  .product-v4-1-ghost-card-scope.wow410-card {
    display: grid !important;
    grid-template-rows: 146px minmax(0, 1fr) auto;
    height: var(--wow410-card-h-desktop, 448px);
    min-width: 0;
    max-width: none;
    overflow: hidden;
    border-color: rgba(16, 24, 40, .1);
    border-radius: 13px;
    background: #fff;
    box-shadow: 0 4px 16px rgba(16, 24, 40, .05);
  }

  .wow410-skeleton-block {
    position: relative;
    overflow: hidden;
    background: var(--wow410-skeleton);
  }

  .wow410-skeleton-block::after {
    position: absolute;
    inset: 0;
    content: "";
    transform: translateX(-120%);
    background: linear-gradient(100deg, transparent 0%, rgba(255, 255, 255, .16) 30%, var(--wow410-skeleton-shimmer) 50%, rgba(255, 255, 255, .16) 70%, transparent 100%);
    animation: wow410SkeletonShimmer 1.45s ease-in-out infinite;
  }

  .product-v4-1-ghost-card__media {
    position: relative;
    min-width: 0;
    overflow: hidden;
    background: linear-gradient(135deg, #e9eef0, #f4f7f8);
  }

  .product-v4-1-ghost-card__signal,
  .product-v4-1-ghost-card__type {
    position: absolute;
    z-index: 4;
    top: 10px;
    height: 24px;
    border-radius: 999px;
  }

  .product-v4-1-ghost-card__signal { right: 10px; width: 76px; background: var(--wow410-skeleton-gold); }
  .product-v4-1-ghost-card__type { left: 10px; width: 64px; background: var(--wow410-skeleton-blue); }

  .product-v4-1-ghost-card__tags {
    position: absolute;
    bottom: 10px;
    left: 10px;
    display: flex;
    gap: 4px;
    max-width: calc(100% - 20px);
  }

  .product-v4-1-ghost-card__tag { width: 66px; height: 22px; border-radius: 999px; background: var(--wow410-skeleton-gold); }
  .product-v4-1-ghost-card__tag--sub { width: 54px; background: var(--wow410-skeleton-soft); }

  .product-v4-1-ghost-card__body {
    display: flex;
    min-height: 0;
    flex-direction: column;
    gap: 4px;
    overflow: hidden;
    padding: 11px 13px 10px;
  }

  .product-v4-1-ghost-card__title { width: 88%; height: 58px; border-radius: 7px; }
  .product-v4-1-ghost-card__provider { width: 48%; height: 27px; border-radius: 7px; }

  .product-v4-1-ghost-card__rating { display: flex; min-height: 16px; align-items: center; gap: 5px; }
  .product-v4-1-ghost-card__stars { width: 56px; height: 12px; border-radius: 4px; background: #f5d978; }
  .product-v4-1-ghost-card__rating-copy { width: 108px; height: 12px; border-radius: 999px; }
  .product-v4-1-ghost-card__location { width: 58%; height: 17px; border-radius: 999px; }

  .product-v4-1-ghost-card__description { display: grid; gap: 5px; margin-top: 1px; }
  .product-v4-1-ghost-card__description .wow410-skeleton-block { height: 10px; border-radius: 999px; }
  .product-v4-1-ghost-card__description .wow410-skeleton-block:nth-child(2) { width: 84%; }
  .product-v4-1-ghost-card__description .wow410-skeleton-block:nth-child(3) { width: 68%; }

  .product-v4-1-ghost-card__availability {
    display: flex;
    min-height: 28px;
    align-items: center;
    gap: 5px;
    margin-top: auto;
    padding: 5px 8px;
    overflow: hidden;
    border-radius: 7px;
    background: var(--wow410-skeleton-green);
  }

  .product-v4-1-ghost-card__availability-icon { width: 11px; height: 11px; flex: 0 0 11px; border-radius: 3px; background: #a6cfc3; }
  .product-v4-1-ghost-card__availability-copy { width: 126px; height: 12px; border-radius: 999px; background: #d5ebe4; }

  .product-v4-1-ghost-card__footer {
    position: relative;
    z-index: 2;
    display: flex;
    min-height: 56px;
    align-items: center;
    justify-content: space-between;
    gap: 7px;
    padding: 9px 12px 10px;
    border-top: 1px solid #edf0f2;
    background: #fff;
  }

  .product-v4-1-ghost-card__price { display: grid; gap: 5px; }
  .product-v4-1-ghost-card__price-label { width: 32px; height: 10px; border-radius: 999px; }
  .product-v4-1-ghost-card__price-value { width: 74px; height: 21px; border-radius: 999px; }
  .product-v4-1-ghost-card__button { width: 94px; height: 36px; border-radius: 4px; background: #d9ece6; }

  @keyframes wow410SkeletonShimmer { to { transform: translateX(120%); } }

  @media (min-width: 768px) and (max-width: 1199.98px) {
    .product-v4-1-ghost-card-scope.wow410-card { grid-template-rows: 145px minmax(0, 1fr) auto; height: var(--wow410-card-h-tablet, 432px); }
  }

  @media (max-width: 767.98px) {
    .product-v4-1-ghost-card-scope.wow410-card { grid-template-rows: 126px minmax(0, 1fr) auto; height: var(--wow410-card-h-mobile, 372px); border-radius: 11px; }
    .product-v4-1-ghost-card__signal, .product-v4-1-ghost-card__type { top: 7px; height: 21px; }
    .product-v4-1-ghost-card__signal { right: 7px; width: 64px; }
    .product-v4-1-ghost-card__type { left: 7px; width: 54px; }
    .product-v4-1-ghost-card__tags { bottom: 7px; left: 7px; max-width: calc(100% - 14px); }
    .product-v4-1-ghost-card__tag { width: 58px; height: 19px; }
    .product-v4-1-ghost-card__tag--sub { width: 46px; }
    .product-v4-1-ghost-card__body { gap: 3px; padding: 9px 9px 8px; }
    .product-v4-1-ghost-card__title { height: 45px; }
    .product-v4-1-ghost-card__provider { height: 25px; }
    .product-v4-1-ghost-card__description { display: none; }
    .product-v4-1-ghost-card__availability { min-height: 25px; padding: 4px 6px; }
    .product-v4-1-ghost-card__footer { min-height: 52px; padding: 8px 8px 9px; }
    .product-v4-1-ghost-card__price-label { width: 28px; height: 9px; }
    .product-v4-1-ghost-card__price-value { width: 58px; height: 18px; }
    .product-v4-1-ghost-card__button { width: 78px; height: 33px; }
  }
</style>
@endonce

<article class="product-v4-1-ghost-card-scope wow410-card" aria-busy="true" aria-label="Loading offering" aria-hidden="true">
  <div class="product-v4-1-ghost-card__media">
    <span class="product-v4-1-ghost-card__type wow410-skeleton-block"></span>
    <span class="product-v4-1-ghost-card__signal wow410-skeleton-block"></span>
    <div class="product-v4-1-ghost-card__tags">
      <span class="product-v4-1-ghost-card__tag wow410-skeleton-block"></span>
      <span class="product-v4-1-ghost-card__tag product-v4-1-ghost-card__tag--sub wow410-skeleton-block"></span>
    </div>
  </div>

  <div class="product-v4-1-ghost-card__body">
    <span class="product-v4-1-ghost-card__title wow410-skeleton-block"></span>
    <span class="product-v4-1-ghost-card__provider wow410-skeleton-block"></span>
    <div class="product-v4-1-ghost-card__rating">
      <span class="product-v4-1-ghost-card__stars wow410-skeleton-block"></span>
      <span class="product-v4-1-ghost-card__rating-copy wow410-skeleton-block"></span>
    </div>
    <span class="product-v4-1-ghost-card__location wow410-skeleton-block"></span>
    <div class="product-v4-1-ghost-card__description">
      <span class="wow410-skeleton-block"></span>
      <span class="wow410-skeleton-block"></span>
      <span class="wow410-skeleton-block"></span>
    </div>
    <div class="product-v4-1-ghost-card__availability">
      <span class="product-v4-1-ghost-card__availability-icon wow410-skeleton-block"></span>
      <span class="product-v4-1-ghost-card__availability-copy wow410-skeleton-block"></span>
    </div>
  </div>

  <footer class="product-v4-1-ghost-card__footer">
    <div class="product-v4-1-ghost-card__price">
      <span class="product-v4-1-ghost-card__price-label wow410-skeleton-block"></span>
      <span class="product-v4-1-ghost-card__price-value wow410-skeleton-block"></span>
    </div>
    <span class="product-v4-1-ghost-card__button wow410-skeleton-block"></span>
  </footer>
</article>
