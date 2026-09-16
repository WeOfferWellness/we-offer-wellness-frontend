@php
  $title = $title ?? 'Gallery';
  $imgs = collect($images ?? [])
    ->filter(fn($u) => is_string($u) && trim($u) !== '')
    ->map(fn($u) => trim($u))
    ->unique()
    ->map(function($u){
      // Replace v3.weofferwellness.co.uk with atease.weofferwellness.co.uk
      return str_replace('v3.weofferwellness.co.uk', 'atease.weofferwellness.co.uk', $u);
    })
    ->values()
    ->all();
@endphp

<section id="wowGallery" data-image-count="{{ count($imgs) }}" data-arrows="{{ count($imgs) > 4 ? 'on' : 'off' }}" aria-label="Image gallery" tabindex="0">
  <style>
    /* Scoped styles for WOW gallery */
    #wowGallery{
      position: relative;
      overflow: hidden;
      display:block;
    }

    #wowGallery{
      --bg: #ffffff;
      --card: #ffffff;
      --border: rgba(16,24,40,.10);
      --shadow: 0 12px 30px rgba(16,24,40,.10);
      --radius: 18px;
      --gap: 14px;

      /* Arrow theme */
      --arrowSize: 46px;
      --arrowIcon: rgba(11,18,32,.82);
      --arrowGlass: rgba(255,255,255,.78);
      --arrowBorder: rgba(255,255,255,.35);
      --arrowShadow: 0 14px 34px rgba(16,24,40,.18);
      --arrowShadowHover: 0 18px 44px rgba(16,24,40,.22);
      --arrowRing: rgba(46,125,90,.35);

      /* Edge glow/fade */
      --edgeFade: rgba(250,250,250,1);
      --edgeWidth: 90px;
    }

    #wowGallery .viewport{ overflow:hidden; border-radius:22px; }
    #wowGallery .track{ display:flex; width:100%; transition: transform 420ms cubic-bezier(.2,.9,.2,1); will-change: transform; }
    #wowGallery .page{ flex: 0 0 100%; padding: 18px 0px; }

    /* Mosaic layout */
    #wowGallery .mosaic{ display:grid; grid-template-columns: 1.7fr 1fr; gap: var(--gap); align-items:stretch; }
    #wowGallery .tile{ position:relative; border-radius: var(--radius); overflow:hidden; border:1px solid var(--border); background:#f2f4f7; min-height:140px; cursor:pointer; }
    #wowGallery .tile img{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transform:scale(1); transition: transform 420ms ease; }
    #wowGallery .tile:hover img{ transform: scale(1.03); }
    #wowGallery .tile--big{ min-height: 320px; }
    #wowGallery .rightGrid{ display:grid; grid-template-columns: 1fr 1.05fr; grid-template-rows: 1fr 1fr; gap: var(--gap); min-height:320px; }
    #wowGallery .tile--r1{ grid-column:1; grid-row:1; }
    #wowGallery .tile--r2{ grid-column:1; grid-row:2; }
    #wowGallery .tile--tall{ grid-column:2; grid-row:1 / span 2; }

    /* Arrows */
    #wowGallery .arrow{
      position:absolute; top:50%; transform: translateY(-50%); width: var(--arrowSize); height: var(--arrowSize);
      border-radius:999px; background: linear-gradient(180deg, rgba(255,255,255,.92), var(--arrowGlass));
      border:1px solid var(--arrowBorder); box-shadow: var(--arrowShadow); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
      display:grid; place-items:center; cursor:pointer; user-select:none; z-index:10;
      transition: transform 220ms cubic-bezier(.2,.9,.2,1), box-shadow 220ms cubic-bezier(.2,.9,.2,1), opacity 180ms ease, filter 180ms ease;
    }
    #wowGallery .arrow:hover{ transform: translateY(-50%) scale(1.06); box-shadow: var(--arrowShadowHover); filter: brightness(1.02); }
    #wowGallery .arrow:active{ transform: translateY(-50%) scale(.98); box-shadow: 0 10px 22px rgba(16,24,40,.16); }
    #wowGallery .arrow:focus-visible{ outline:0; box-shadow: var(--arrowShadow), 0 0 0 4px var(--arrowRing); }
    #wowGallery .arrow[disabled]{ opacity:0; pointer-events:none; }
    #wowGallery .arrow--left{ left:14px; }
    #wowGallery .arrow--right{ right:14px; }
    #wowGallery .arrow svg{ width:18px; height:18px; stroke: var(--arrowIcon); transition: transform 220ms ease; }
    #wowGallery .arrow--left:hover svg{ transform: translateX(-1px); }
    #wowGallery .arrow--right:hover svg{ transform: translateX(1px); }
    #wowGallery[data-arrows="off"] .arrow{ display:none; }
    #wowGallery[data-image-count="0"] .arrow,
    #wowGallery[data-image-count="1"] .arrow,
    #wowGallery[data-image-count="0"] .modal__nav,
    #wowGallery[data-image-count="1"] .modal__nav{ display:none; }

    /* Edge fades */
    #wowGallery::before, #wowGallery::after{
      content:""; position:absolute; top:0; bottom:0; width: var(--edgeWidth); z-index:6; pointer-events:none; opacity:0; transition: opacity 200ms ease;
    }
    #wowGallery::before{ left:0; background: linear-gradient(90deg, var(--edgeFade), rgba(250,250,250,0)); }
    #wowGallery::after{ right:0; background: linear-gradient(270deg, var(--edgeFade), rgba(250,250,250,0)); }
    #wowGallery[data-arrows="on"]:not(.at-start)::before{ opacity:1; }
    #wowGallery[data-arrows="on"]:not(.at-end)::after{ opacity:1; }

    /* Responsive */
    @media (max-width: 860px){
      #wowGallery{
        --bg: #ffffff;
        --card: #ffffff;
        --border: rgba(16, 24, 40, .10);
        --shadow: 0 12px 30px rgba(16, 24, 40, .10);
        --radius: 18px;
        --gap: 14px;
        --arrowSize: 46px;
        --arrowIcon: rgba(11, 18, 32, .82);
        --arrowGlass: rgba(255, 255, 255, .78);
        --arrowBorder: rgba(255, 255, 255, .35);
        --arrowShadow: 0 14px 34px rgba(16, 24, 40, .18);
        --arrowShadowHover: 0 18px 44px rgba(16, 24, 40, .22);
        --arrowRing: rgba(46, 125, 90, .35);
        --edgeFade: rgba(250, 250, 250, 1);
        --edgeWidth: 90px;
        margin-bottom: 15px;
      }
      #wowGallery .page{ padding:14px 0px; }
      #wowGallery .mosaic{ grid-template-columns:1fr; }
      #wowGallery .tile--big{ min-height:240px; }
      #wowGallery .rightGrid{ min-height:240px; }
      #wowGallery .mosaic--single .rightGrid{
        display: none !important;
        min-height: 0 !important;
      }
      #wowGallery{ --arrowSize:42px; --edgeWidth:70px; }
    }
    @media (max-width: 520px){
      #wowGallery{ --gap:10px; --radius:16px; --arrowSize:40px; --edgeWidth:62px; }
      #wowGallery .tile--big{ min-height:220px; }
      #wowGallery .rightGrid{ grid-template-columns:1fr 1fr; min-height:220px; }
      #wowGallery .tile--tall{ grid-row: 1 / span 2; }
      #wowGallery .arrow--left{ left:10px; }
      #wowGallery .arrow--right{ right:10px; }
      #wowGallery .mosaic--single{
        grid-template-columns: 1fr;
      }
      #wowGallery .mosaic--single .rightGrid{
        display: none;
      }
      #wowGallery .mosaic--compact{
        grid-template-columns: 1fr;
      }
      #wowGallery .mosaic--compact .rightGrid{
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
        grid-template-rows: 1fr;
        min-height: auto;
      }
      #wowGallery .mosaic--compact .tile--big{
        min-height: 220px;
      }
      #wowGallery .mosaic--compact .tile--r1,
      #wowGallery .mosaic--compact .tile--r2,
      #wowGallery .mosaic--compact .tile--tall{
        grid-column: auto;
        grid-row: auto;
        min-height: 120px;
      }
    }
    /* ===== Modal (scoped to gallery) ===== */
    #wowGallery .modal{ position: fixed; inset: 0; display: none; z-index: 3000; }
    #wowGallery .modal[aria-hidden="false"]{ display:block; }
    #wowGallery .modal__overlay{ position:absolute; inset:0; background: rgba(0,0,0,.85); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index:0; }
    #wowGallery .modal__top{ position:absolute; top: 18px; left: 0; right: 0; display:grid; grid-template-columns: 1fr auto 1fr; align-items:center; padding:0 18px; z-index:1; }
    #wowGallery .modal__count{
      justify-self: center;
      color: rgba(255, 255, 255, .85);
      font-weight: 700;
      letter-spacing: .08em;
      font-size: 18px;
      font-family: "DS-Digital", "Digital-7", "DSEG7Classic", "Segment7Standard", "Seven Segment", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-variant-numeric: tabular-nums;
      text-shadow: 0 1px 0 rgba(0, 0, 0, .35);
    }
    #wowGallery .modal__close{ pointer-events:auto; justify-self:end; width:38px; height:38px; border-radius:999px; border:0; background: rgba(255,255,255,.92); color: rgba(11,18,32,.85); display:grid; place-items:center; cursor:pointer; box-shadow: 0 12px 26px rgba(0,0,0,.22); transition: transform 180ms ease, filter 180ms ease; z-index:2; }
    #wowGallery .modal__close:hover{ transform: scale(1.06); filter: brightness(1.02); }
    #wowGallery .modal__close:active{ transform: scale(.98); }
    #wowGallery .modal__close:focus-visible{ outline:0; box-shadow: 0 12px 26px rgba(0,0,0,.22), 0 0 0 4px rgba(255,255,255,.22); }
    #wowGallery .modal__stage{ position:absolute; inset:0; display:grid; place-items:center; padding:72px 18px 96px; z-index:1; }
    #wowGallery .modal__figure{ width: min(980px, 92vw); }
    #wowGallery .modal__imageWrap{ position:relative; border-radius:18px; overflow:hidden; box-shadow: 0 24px 70px rgba(0,0,0,.35); background: rgba(255,255,255,.04); }
    #wowGallery .modal__img{ display:block; width:100%; height: min(520px, 62vh); object-fit:cover; border-radius:18px; background: rgba(255,255,255,.04); }
    #wowGallery .modal__nav{ position:absolute; top:50%; transform: translateY(-50%); width:44px; height:44px; border-radius:999px; border:0; background: rgba(255,255,255,.92); color: rgba(0,0,0,.78); display:grid; place-items:center; cursor:pointer; box-shadow: 0 16px 40px rgba(0,0,0,.22); transition: transform 180ms ease, filter 180ms ease, opacity 180ms ease; z-index:2; }
    #wowGallery .modal__nav:hover{ transform: translateY(-50%) scale(1.06); filter: brightness(1.02); }
    #wowGallery .modal__nav:active{ transform: translateY(-50%) scale(.98); }
    #wowGallery .modal__nav:focus-visible{ outline:0; box-shadow: 0 16px 40px rgba(0,0,0,.22), 0 0 0 4px rgba(255,255,255,.22); }
    #wowGallery .modal__nav[disabled]{ opacity:0; pointer-events:none; }
    #wowGallery .modal__nav--prev{ left:14px; }
    #wowGallery .modal__nav--next{ right:14px; }
    #wowGallery .modal__nav svg{ width:18px; height:18px; stroke-width:2.4; }
    #wowGallery .modal__bar{ margin-top:14px; display:grid; grid-template-columns:1fr; gap:10px; }
    #wowGallery .modal__productTitle{
      color: rgba(255, 255, 255, .92);
      font-weight: 400;
      font-size: 22px;
      line-height: 1.3;
      text-align: center;
      text-shadow: 0 1px 0 rgba(0, 0, 0, .22);
      font-family: 'Manrope', var(--bs-font-sans-serif);
    }

    @media (prefers-reduced-motion: reduce){
      #wowGallery .track, #wowGallery .tile img, #wowGallery .arrow, #wowGallery .arrow svg, #wowGallery .modal__close, #wowGallery .modal__nav{ transition:none !important; }
    }

    /* While modal is open, prevent helper panel from intercepting clicks */
    .wow-modal-open .wow-helper{ pointer-events: none !important; }
    .wow-modal-open .wow-helper .toggle{ pointer-events: none !important; }
  </style>

  <button class="arrow arrow--left" id="prevBtn" type="button" aria-label="Previous images">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
      <path d="M14.5 5.5L8.5 12l6 6.5"/>
    </svg>
  </button>

  <div class="viewport">
    <div class="track" id="track"></div>
  </div>

  <button class="arrow arrow--right" id="nextBtn" type="button" aria-label="Next images">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9.5 5.5L15.5 12l-6 6.5"/>
    </svg>
  </button>

  <!-- Modal viewer (scoped under #wowGallery) -->
  <div class="modal" id="imgModal" aria-hidden="true" aria-label="Image viewer" role="dialog">
    <div class="modal__overlay" id="modalOverlay"></div>
    <div class="modal__top">
      <div></div>
      <div class="modal__count" id="modalCount">01 / 01</div>
      <button class="modal__close" id="modalClose" type="button" aria-label="Close">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
          <path d="M6 6l12 12M18 6L6 18"/>
        </svg>
      </button>
    </div>
    <div class="modal__stage" role="document">
      <div class="modal__figure">
        <div class="modal__imageWrap">
          <button class="modal__nav modal__nav--prev" id="modalPrev" type="button" aria-label="Previous image">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.5 5.5L8.5 12l6 6.5"/>
            </svg>
          </button>
          <img class="modal__img" id="modalImg" alt="" src="" />
          <button class="modal__nav modal__nav--next" id="modalNext" type="button" aria-label="Next image">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9.5 5.5L15.5 12l-6 6.5"/>
            </svg>
          </button>
        </div>
        <div class="modal__bar">
          <div class="modal__productTitle" id="modalTitle"></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      const images = @json($imgs);
      const altText = @json($title);

      const gallery = document.getElementById("wowGallery");
      const track = document.getElementById("track");
      const prevBtn = document.getElementById("prevBtn");
      const nextBtn = document.getElementById("nextBtn");

      if (!gallery || !track || !prevBtn || !nextBtn) return;

      let pageIndex = 0;
      let pageCount = 0;

      function chunk(array, size){
        const out = [];
        for (let i = 0; i < array.length; i += size) out.push(array.slice(i, i + size));
        return out;
      }

      function tile(url, className, absoluteIndex){
        const div = document.createElement("div");
        div.className = `tile ${className || ""}`.trim();
        div.tabIndex = 0;
        div.setAttribute('role','button');
        div.setAttribute('aria-label','Open image');
        div.dataset.index = String(absoluteIndex ?? 0);
        const img = document.createElement("img");
        img.loading = "lazy";
        img.alt = altText || "";
        img.src = url;
        div.appendChild(img);
        const open = () => openModal(absoluteIndex ?? 0);
        div.addEventListener('click', open);
        div.addEventListener('keydown', (e) => { if(e.key==='Enter' || e.key===' '){ e.preventDefault(); open(); } });
        return div;
      }

      function build(){
        track.innerHTML = "";

        const pages = chunk(images, 4);
        pageCount = pages.length;

        gallery.dataset.arrows = pageCount > 1 ? "on" : "off";

        pages.forEach((set, pageIdx) => {
          if (!set || set.length === 0) return;

          const page = document.createElement("div");
          page.className = "page";

          const mosaic = document.createElement("div");
          mosaic.className = "mosaic";

          const base = pageIdx * 4;
          if (set.length === 1) {
            mosaic.classList.add("mosaic--single");
          } else if (set.length <= 3) {
            mosaic.classList.add("mosaic--compact");
          }
          if (set[0]) mosaic.appendChild(tile(set[0], "tile--big", base + 0));

          const right = document.createElement("div");
          right.className = "rightGrid";

          if (set[1]) right.appendChild(tile(set[1], "tile--r1", base + 1));
          if (set[2]) right.appendChild(tile(set[2], "tile--r2", base + 2));
          if (set[3]) right.appendChild(tile(set[3], "tile--tall", base + 3));

          mosaic.appendChild(right);
          page.appendChild(mosaic);
          track.appendChild(page);
        });

        pageIndex = 0;
        update();
        bindSwipe();
        observeHoverHint();
      }

      function update(){
        track.style.transform = `translateX(${-pageIndex * 100}%)`;

        const atStart = pageIndex === 0;
        const atEnd = pageIndex >= pageCount - 1;

        prevBtn.toggleAttribute("disabled", atStart);
        nextBtn.toggleAttribute("disabled", atEnd);

        gallery.classList.toggle("at-start", atStart);
        gallery.classList.toggle("at-end", atEnd);
      }

      function next(){ if (pageIndex < pageCount - 1){ pageIndex++; update(); } }
      function prev(){ if (pageIndex > 0){ pageIndex--; update(); } }

      prevBtn.addEventListener("click", prev);
      nextBtn.addEventListener("click", next);

      gallery.addEventListener("keydown", (e) => {
        if (e.key === "ArrowRight") next();
        if (e.key === "ArrowLeft") prev();
      });

      let cleanupSwipe = null;
      function bindSwipe(){
        if (cleanupSwipe) cleanupSwipe();

        let startX = 0, startY = 0, dragging = false;

        const onDown = (e) => {
          const p = "touches" in e ? e.touches[0] : e;
          startX = p.clientX; startY = p.clientY; dragging = true;
        };
        const onMove = (e) => {
          if (!dragging) return;
          const p = "touches" in e ? e.touches[0] : e;
          const dx = p.clientX - startX;
          const dy = p.clientY - startY;
          if (Math.abs(dy) > Math.abs(dx)) return;
          if ("touches" in e) e.preventDefault();
        };
        const onUp = (e) => {
          if (!dragging) return;
          dragging = false;
          const p = "changedTouches" in e ? e.changedTouches[0] : e;
          const dx = p.clientX - startX;
          if (dx < -50) next();
          if (dx > 50) prev();
        };
        const opts = { passive: false };
        gallery.addEventListener("mousedown", onDown);
        gallery.addEventListener("mousemove", onMove);
        gallery.addEventListener("mouseup", onUp);
        gallery.addEventListener("mouseleave", onUp);
        gallery.addEventListener("touchstart", onDown, opts);
        gallery.addEventListener("touchmove", onMove, opts);
        gallery.addEventListener("touchend", onUp, opts);
        cleanupSwipe = () => {
          gallery.removeEventListener("mousedown", onDown);
          gallery.removeEventListener("mousemove", onMove);
          gallery.removeEventListener("mouseup", onUp);
          gallery.removeEventListener("mouseleave", onUp);
          gallery.removeEventListener("touchstart", onDown, opts);
          gallery.removeEventListener("touchmove", onMove, opts);
          gallery.removeEventListener("touchend", onUp, opts);
        };
      }

      function observeHoverHint(){
        if (pageCount <= 1) return;
        const key = "wow_gallery_arrow_hint_v1";
        if (localStorage.getItem(key)) return;
        let interacted = false;
        const mark = () => { interacted = true; };
        gallery.addEventListener("pointerdown", mark, { once:true });
        gallery.addEventListener("keydown", mark, { once:true });
        setTimeout(() => {
          if (interacted) return;
          nextBtn.animate([
            { transform: "translateY(-50%) scale(1)", boxShadow: "0 14px 34px rgba(16,24,40,.18)" },
            { transform: "translateY(-50%) scale(1.12)", boxShadow: "0 18px 44px rgba(16,24,40,.24)" },
            { transform: "translateY(-50%) scale(1)", boxShadow: "0 14px 34px rgba(16,24,40,.18)" }
          ], { duration: 900, easing: "cubic-bezier(.2,.9,.2,1)" });
          localStorage.setItem(key, "1");
        }, 1500);
      }

      /* ===== Modal logic ===== */
      const modal = gallery.querySelector('#imgModal');
      const modalOverlay = gallery.querySelector('#modalOverlay');
      const modalClose = gallery.querySelector('#modalClose');
      const modalPrev = gallery.querySelector('#modalPrev');
      const modalNext = gallery.querySelector('#modalNext');
      const modalImg = gallery.querySelector('#modalImg');
      const modalCount = gallery.querySelector('#modalCount');
      const modalTitle = gallery.querySelector('#modalTitle');
      let modalIndex = 0;
      let lastFocusEl = null;

      function pad2(n){ return String(n).padStart(2,'0'); }
      function renderModal(){
        modalImg.src = images[modalIndex] || '';
        modalCount.textContent = pad2(modalIndex+1) + ' / ' + pad2(images.length);
        modalTitle.textContent = altText || '';
        modalPrev.toggleAttribute('disabled', modalIndex === 0);
        modalNext.toggleAttribute('disabled', modalIndex === images.length - 1);
      }
      function openModal(index){
        if(!images.length) return;
        lastFocusEl = document.activeElement;
        modalIndex = Math.max(0, Math.min(images.length - 1, (index ?? 0)));
        modal.setAttribute('aria-hidden','false');
        try{ modal.style.display = 'block'; document.body.style.overflow = 'hidden'; document.body.classList.add('wow-modal-open'); }catch{}
        renderModal();
        try{ modalClose.focus({preventScroll:true}); }catch{}
      }
      function closeModal(){
        modal.setAttribute('aria-hidden','true');
        try{ modal.style.display = 'none'; document.body.style.overflow = ''; document.body.classList.remove('wow-modal-open'); }catch{}
        modalImg.src = '';
        if(lastFocusEl && typeof lastFocusEl.focus === 'function') try{ lastFocusEl.focus(); }catch{}
      }
      function modalNextImg(){ if(modalIndex < images.length - 1){ modalIndex++; renderModal(); } }
      function modalPrevImg(){ if(modalIndex > 0){ modalIndex--; renderModal(); } }

      if(modal){
        modalClose?.addEventListener('click', closeModal);
        modalOverlay?.addEventListener('click', closeModal);
        modalClose?.addEventListener('keydown', function(e){ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); closeModal(); } });
        // Delegate clicks from nested SVG/path inside the close button
        modal.addEventListener('click', function(e){
          var c = e.target.closest ? e.target.closest('#modalClose') : null;
          if(c){ e.preventDefault(); closeModal(); }
        });
        modalNext?.addEventListener('click', modalNextImg);
        modalPrev?.addEventListener('click', modalPrevImg);
        window.addEventListener('keydown', function(e){
          if(modal.getAttribute('aria-hidden') === 'true') return;
          if(e.key === 'Escape') closeModal();
          if(e.key === 'ArrowRight') modalNextImg();
          if(e.key === 'ArrowLeft') modalPrevImg();
        });
        modalImg?.addEventListener('click', function(){ if(modalIndex < images.length - 1) modalNextImg(); });
      }

      if (images.length > 0) build(); else gallery.style.display = 'none';
    })();
  </script>
</section>
