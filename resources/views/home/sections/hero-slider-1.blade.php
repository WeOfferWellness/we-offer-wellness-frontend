{{-- Customer-focused homepage hero slide. --}}

<style>
  .whero.whero--s1 {
    min-height: 100%;
    overflow: visible;
    z-index: 2;
  }

  /* Slide 1: animated gradient and soft morphing forms inspired by the supplied background. */
  .whero.whero--s1 .whero-radial {
    z-index: -1;
    opacity: 1;
    background: linear-gradient(45deg, #82e6d0 0%, #8bdaf7 29%, #df9fe2 66%, #ffc08f 100%);
    background-size: 400% 400%;
    animation: wow-hero-s1-gradient 15s ease infinite;
  }

  .whero.whero--s1 .whero-radial::before,
  .whero.whero--s1 .whero-radial::after {
    inset: auto;
    width: 70vmax;
    height: 70vmax;
    background: rgba(255, 255, 255, .18);
    mix-blend-mode: soft-light;
    border-radius: 40% 60% 60% 40% / 70% 30% 70% 30%;
    animation: wow-hero-s1-morph 15s linear infinite alternate, wow-hero-s1-spin 20s linear infinite;
  }

  .whero.whero--s1 .whero-radial::before {
    left: -20vmin;
    top: -20vmin;
    transform-origin: 55% 55%;
  }

  .whero.whero--s1 .whero-radial::after {
    right: -10vmin;
    bottom: -8vmin;
    width: 70vmin;
    height: 70vmin;
    animation-duration: 10s, 26s;
    animation-direction: alternate, reverse;
    transform-origin: 20% 20%;
  }

  .whero.whero--s1 .s1-particles {
    position: absolute;
    inset: 0;
    z-index: -1;
    width: 100%;
    height: 100%;
    pointer-events: none;
    opacity: .82;
  }

  @keyframes wow-hero-s1-gradient {
    0%, 100% { background-position: 0 50%; }
    50% { background-position: 100% 50%; }
  }

  @keyframes wow-hero-s1-morph {
    0% { border-radius: 40% 60% 60% 40% / 70% 30% 70% 30%; }
    100% { border-radius: 40% 60%; }
  }

  @keyframes wow-hero-s1-spin {
    to { transform: rotate(1turn); }
  }

  @media (prefers-reduced-motion: reduce) {
    .whero.whero--s1 .whero-radial,
    .whero.whero--s1 .whero-radial::before,
    .whero.whero--s1 .whero-radial::after {
      animation: none;
    }
  }

  .wow-hero-swiper,
  .wow-hero-swiper .swiper-wrapper,
  .wow-hero-swiper .swiper-slide {
    overflow: visible;
  }

  .wow-hero-swiper {
    position: relative;
    z-index: 20;
  }

  .whero.whero--s1 .container.whero-pad {
    display: flex;
    align-items: center;
    min-height: 100%;
  }

  .wow-hero-swiper.wow-hero-swiper--single .whero.whero--s1 .container.whero-pad {
    min-height: auto;
  }

  .whero.whero--s1 .s1-copy {
    width: min(100%, 980px);
    margin: 0 auto;
    text-align: center;
  }

  .whero.whero--s1 .whero-eyebrow {
    display: inline-flex;
  }

  .whero.whero--s1 .whero-title,
  .whero.whero--s1 .whero-sub {
    margin-right: auto;
    margin-left: auto;
  }

  .whero.whero--s1 .whero-sub {
    max-width: 720px;
  }

  .whero.whero--s1 .s1-search {
    position: relative;
    z-index: 100;
    width: min(100%, 920px);
    margin: 2rem auto 0;
    text-align: left;
  }

  .whero.whero--s1 .s1-search .wow-ultra,
  .whero.whero--s1 .s1-search .pane {
    z-index: 3000;
  }

  @media (max-width: 767.98px) {
    .whero.whero--s1 {
      min-height: auto;
    }
  }

  @media (max-width: 575.98px) {
    .whero.whero--s1 .container.whero-pad {
      padding: 84px 20px 34px !important;
    }

    .whero.whero--s1 .s1-search {
      margin-top: 1.25rem;
    }
  }
</style>

<section class="whero whero--s1" aria-labelledby="homepage-hero-title">
  <div class="whero-radial" aria-hidden="true"></div>
  <canvas class="s1-particles" data-wow-hero-particles aria-hidden="true"></canvas>

  <div class="container whero-pad">
    <div class="s1-copy">
      <span class="whero-eyebrow">Holistic wellbeing, all in one place</span>
      <h1 id="homepage-hero-title" class="whero-title">Find holistic therapies, events, workshops, festivals and retreats</h1>
      <p class="whero-sub mt-3">Explore trusted practitioners and experiences online or near you, then choose the support, session or event that feels right for you.</p>

      <div class="s1-search">
        <x-home-searchbar-v4 id-prefix="hero-search-v4" mobile-top-offset="var(--wow-header-offset, 0px)" />
      </div>
    </div>
  </div>
</section>

<script>
  (function () {
    var canvas = document.querySelector('[data-wow-hero-particles]');
    if (!canvas || canvas.dataset.initialized) return;
    canvas.dataset.initialized = 'true';

    var hero = canvas.closest('.whero--s1');
    var context = canvas.getContext('2d');
    var particles = [];
    var width = 0;
    var height = 0;
    var pixelRatio = 1;
    var previousTime = 0;
    var visible = true;
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var colours = [
      'rgba(255,255,255,.42)',
      'rgba(255,244,207,.34)',
      'rgba(220,255,246,.36)',
      'rgba(239,218,255,.32)'
    ];

    function resize() {
      if (!hero) return;
      var bounds = hero.getBoundingClientRect();
      width = Math.max(1, bounds.width);
      height = Math.max(1, bounds.height);
      pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = Math.round(width * pixelRatio);
      canvas.height = Math.round(height * pixelRatio);
      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';
      context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
      if (!particles.length) createParticles();
    }

    function createParticles() {
      var count = width < 600 ? 6 : 10;
      particles = Array.from({ length: count }, function (_, index) {
        var radius = Math.max(26, Math.min(width, height) * (0.035 + (index % 4) * 0.012));
        return {
          x: Math.random() * width,
          y: Math.random() * height,
          radius: radius,
          speedX: (Math.random() - .5) * .035,
          speedY: (Math.random() - .5) * .028,
          phase: Math.random() * Math.PI * 2,
          colour: colours[index % colours.length]
        };
      });
    }

    function draw() {
      context.clearRect(0, 0, width, height);
      particles.forEach(function (particle) {
        var glow = context.createRadialGradient(
          particle.x - particle.radius * .28,
          particle.y - particle.radius * .28,
          0,
          particle.x,
          particle.y,
          particle.radius
        );
        glow.addColorStop(0, particle.colour);
        glow.addColorStop(1, 'rgba(255,255,255,0)');
        context.fillStyle = glow;
        context.beginPath();
        context.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
        context.fill();
      });
    }

    function animate(timestamp) {
      if (!previousTime) previousTime = timestamp;
      var delta = Math.min(timestamp - previousTime, 40);
      previousTime = timestamp;

      if (visible && !reducedMotion) {
        particles.forEach(function (particle) {
          particle.phase += delta * .00012;
          particle.x += particle.speedX * delta + Math.cos(particle.phase) * .012;
          particle.y += particle.speedY * delta + Math.sin(particle.phase * .8) * .01;
          if (particle.x < -particle.radius) particle.x = width + particle.radius;
          if (particle.x > width + particle.radius) particle.x = -particle.radius;
          if (particle.y < -particle.radius) particle.y = height + particle.radius;
          if (particle.y > height + particle.radius) particle.y = -particle.radius;
        });
      }
      draw();
      window.requestAnimationFrame(animate);
    }

    resize();
    window.addEventListener('resize', resize, { passive: true });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        visible = Boolean(entries[0] && entries[0].isIntersecting);
      }, { threshold: 0.01 }).observe(hero);
    }
    window.requestAnimationFrame(animate);
  })();
</script>
