{{-- resources/views/home/sections/hero-slider.blade.php --}}

  {{-- Swiper CSS --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

  <style>
    /* Keep your existing whero styling intact. This only styles the slider wrapper + controls. */
    .wow-hero-swiper {
      --wow-hero-band-height: clamp(480px, 58vh, 700px);
      position: relative;
      overflow: hidden;
      min-height: var(--wow-hero-band-height);
    }
    .wow-hero-swiper .swiper-wrapper,
    .wow-hero-swiper .swiper-slide {
      min-height: var(--wow-hero-band-height);
    }
    .wow-hero-swiper .swiper-slide > .whero {
      width: 100%;
    }
    .wow-hero-swiper .swiper-slide > .whero .whero-pad {
      min-height: var(--wow-hero-band-height);
    }

    .wow-hero-swiper .whero-eyebrow,
    .wow-hero-swiper .whero-title,
    .wow-hero-swiper .whero-sub {
      color: #000 !important;
    }

    @media (max-width: 767.98px) {
      .wow-hero-swiper .swiper-wrapper,
      .wow-hero-swiper .swiper-slide {
        min-height: auto;
        height: auto !important;
      }
      .wow-hero-pagination .swiper-pagination-bullet{
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        min-height: 44px !important;
        margin: 0 !important;
        background: transparent;
        border: 0;
        position: relative;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
      }
      .wow-hero-pagination .swiper-pagination-bullet::before{
        content:"";
        position:absolute;
        left:50%;
        top:50%;
        width:10px;
        height:10px;
        transform:translate(-50%,-50%);
        border-radius:999px;
        background: rgba(255,255,255,.22);
        border:1px solid #fff;
      }
      .wow-hero-pagination .swiper-pagination-bullet-active::before{
        width:26px;
        background: rgba(255,255,255,.65);
        border-color: rgba(255,255,255,.65);
      }
    }
    @media (min-width: 992px) {
      .wow-hero-swiper {
        --wow-hero-band-height: 780px;
        height: 780px;
        min-height: 780px;
      }
      .wow-hero-swiper .swiper-wrapper,
      .wow-hero-swiper .swiper-slide {
        height: 100%;
        min-height: 100%;
      }
      .wow-hero-swiper .swiper-slide {
        display: flex;
        align-items: stretch;
      }
      .wow-hero-swiper .swiper-slide > .whero {
        height: 100%;
      }
      .wow-hero-swiper .swiper-slide > .whero .whero-pad {
        height: 100%;
        min-height: 100%;
      }
    }

    .wow-hero-nav{
      position:absolute;
      left:0; right:0;
      top: 18px;
      z-index: 10;
      pointer-events:none;
    }
    .wow-hero-nav .container{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:14px;
    }

    .wow-home-search-wrap{
      position:relative;
      z-index:50;
    }

    @media (min-width: 1041px) {
      .wow-home-search-wrap{
        scroll-margin-top: 127px;
      }
    }

    @media (max-width: 1040px) {
      .wow-hero-swiper:has(.wow-search-filter.is-mobile-expanded) {
        z-index: 5000;
      }

      .wow-home-search-wrap {
        position: sticky;
        top: 70px;
        z-index: 3000;
        padding: 0;
      }

      .wow-home-search-wrap:has(.wow-search-filter.is-mobile-expanded) {
        position: fixed;
        top: 55px;
        left: 0;
        right: 0;
        width: 100%;
      }
    }

    .wow-hero-btn {
        pointer-events: auto;
        width: 44px;
        height: 40px;
        border-radius: 999px;
        border: 1px solid #999;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: rgba(16, 24, 40, .85);
    }
    .wow-hero-btn:focus{ outline:none; box-shadow: 0 0 0 4px rgba(68,76,231,.20), 0 12px 30px rgba(16,24,40,.18); }

    .wow-hero-pagination{
      pointer-events:auto;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:8px;
    }

    /* Swiper pagination bullets -> slick pill dots */
    .wow-hero-pagination .swiper-pagination-bullet{
      width: 10px; height:10px;
      border-radius: 999px;
      opacity: 1;
      background: rgba(255,255,255,.22);
      border: 1px solid #fff;
      transition: width .18s ease, background .18s ease, border-color .18s ease;
      margin: 0 !important;
    }
    .wow-hero-pagination .swiper-pagination-bullet-active{
      width: 26px;
      background: rgba(255,255,255,.65);
      border-color: rgba(255,255,255,.65);
    }

    /* =========================
       Per-slide BACKGROUNDS
       =========================
       We keep .whero-radial but override its background per slide.
       (No class renames, only add modifiers)
    */

    /* Slide 1: keep your current rainbow rays vibe */
    .whero.whero--s1 .whero-radial{
      background:
        conic-gradient(from 120deg at 58% 52%,
          rgba(255,164,198,.85),
          rgba(255,214,165,.85),
          rgba(255,248,184,.85),
          rgba(180,255,196,.85),
          rgba(146,232,255,.85),
          rgba(174,174,255,.85),
          rgba(214,170,255,.85),
          rgba(255,164,198,.85)
        );
      opacity: .95;
    }

    /* Slide 2: cooler mint/sky burst */
    .whero.whero--s2 .whero-radial{
      background:
        conic-gradient(from 135deg at 60% 50%,
          rgba(120,255,230,.85),
          rgba(135,220,255,.85),
          rgba(164,180,255,.85),
          rgba(200,255,220,.85),
          rgba(120,255,230,.85)
        );
      opacity: .92;
    }

    /* Slide 3: warm sunset / festival energy */
    .whero.whero--s3 .whero-radial{
      background:
        conic-gradient(from 110deg at 58% 52%,
          rgba(255,168,124,.88),
          rgba(255,216,142,.88),
          rgba(255,176,220,.88),
          rgba(196,170,255,.88),
          rgba(150,220,255,.88),
          rgba(255,168,124,.88)
        );
      opacity: .93;
    }

    /* Optional: slide-specific text tweaks hooks (if you want them later) */
    .whero--s2 .whero-title { /* e.g. */ }
    .whero--s3 .whero-title { /* e.g. */ }

    @media (max-width: 575px){
      .wow-hero-nav{
        top: 10px;
        bottom: auto;
      }
      .wow-hero-nav .container{
        gap: 10px;
      }
      .wow-hero-btn{
        width: 40px;
        height: 40px;
      }
    }

    @media (max-width: 991.98px){
      .browser-window{ display:none !important; }
    }

    /* Ensure consistent visual height across slides on desktop by
       constraining slide 2 elements to fit the same hero band height
       as slide 1. This reduces poster + typography sizes on desktop. */
    @media (min-width: 992px) {
      .wow-hero-swiper .whero-stack { min-height: 360px; }
      .wow-hero-swiper .whero-panel { top: 180px; width: min(600px, 100%); }
      .wow-hero-swiper .whero-browser-chrome { height: 40px; }
      .wow-hero-swiper .whero-browser-page { padding: 12px; }
      .wow-hero-swiper .whero-grid-viewport { margin-top: 12px; }
      .wow-hero-swiper .whero-panel .card { min-height: 120px; width: 200px; flex: 0 0 200px; }
      .wow-hero-swiper .whero-spark,
      .wow-hero-swiper .spark { height: 72px; }

    }
  </style>

{{-- Swiper slider --}}
@php($heroHasMultipleSlides = false)
<div class="swiper wow-hero-swiper" data-hero-swiper>
  <div class="swiper-wrapper">
    @if(false)
      {{-- WOW Studio slide retained for later reuse, currently hidden from the homepage. --}}
      <div class="swiper-slide">
        @include('home.sections.hero-slider-3')
      </div>
    @endif
    <div class="swiper-slide">
      @include('home.sections.hero-slider-1')
    </div>
  </div>

  @if($heroHasMultipleSlides)
  {{-- Controls overlay --}}
  <div class="wow-hero-nav">
    <div class="container">
      <!-- Prev -->
      <button type="button" class="wow-hero-btn wow-hero-prev" aria-label="Previous slide">
        <i class="bi bi-chevron-left"></i>
      </button>

      <!-- Pause / Play -->
      <button type="button"
              class="wow-hero-btn wow-hero-toggle"
              data-hero-toggle
              aria-label="Pause autoplay"
              aria-pressed="false">
        <i class="bi bi-pause-fill"></i>
      </button>

      <!-- Dots -->
      <div class="wow-hero-pagination"></div>

      <!-- Next -->
      <button type="button" class="wow-hero-btn wow-hero-next" aria-label="Next slide">
        <i class="bi bi-chevron-right"></i>
      </button>
    </div>
  </div>
  @endif
</div>

@include('home.sections.mindful_times_ribbon')

  {{-- Swiper JS --}}
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

 <script>
   document.addEventListener('DOMContentLoaded', () => {
    const el = document.querySelector('[data-hero-swiper]');
    if (!el) return;

    const toggleBtn = document.querySelector('[data-hero-toggle]');
    const slideCount = el.querySelectorAll('.swiper-wrapper > .swiper-slide').length;
    const hasMultipleSlides = slideCount > 1;
    if (!hasMultipleSlides) {
      return;
    }
    let isPaused = false;

	    const swiper = new Swiper(el, {
	      loop: hasMultipleSlides,
	      speed: 650,
	      effect: 'slide',

       autoplay: hasMultipleSlides ? {
         delay: 5000,
         disableOnInteraction: false,
         pauseOnMouseEnter: true,
       } : false,

       navigation: {
         nextEl: '.wow-hero-next',
         prevEl: '.wow-hero-prev',
       },

       pagination: {
         el: '.wow-hero-pagination',
         clickable: true,
       },

       keyboard: {
         enabled: true,
         onlyInViewport: true,
       },

	      a11y: {
	        enabled: true,
	      },
	    });

	    function setPaused(nextPaused) {
	      isPaused = !!nextPaused;

       // If the toggle button isn't present, still allow the rest of the slider to work
       if (!toggleBtn) {
         if (isPaused) swiper.autoplay.stop();
         else swiper.autoplay.start();
         return;
       }

       const icon = toggleBtn.querySelector('i');

       if (isPaused) {
         swiper.autoplay.stop();
         toggleBtn.setAttribute('aria-label', 'Play autoplay');
         toggleBtn.setAttribute('aria-pressed', 'true');
         if (icon) icon.className = 'bi bi-play-fill';
       } else {
         swiper.autoplay.start();
         toggleBtn.setAttribute('aria-label', 'Pause autoplay');
         toggleBtn.setAttribute('aria-pressed', 'false');
         if (icon) icon.className = 'bi bi-pause-fill';
       }
     }

     // Start paused if user prefers reduced motion
     if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
       setPaused(true);
     }

     // Click to toggle pause/play
     if (toggleBtn) {
       toggleBtn.addEventListener('click', () => setPaused(!isPaused));
     }

     // Optional: if you want autoplay to resume after manual swipe unless paused
     swiper.on('touchEnd', () => {
       if (!isPaused) swiper.autoplay.start();
     });
   });
 </script>
