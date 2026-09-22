<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">

<section class="safe" id="trust-reviews" aria-label="Safety and review proof">
    <div class="wrap">
        <div class="safe-grid">
            <article class="hero-card">
                <p class="eyebrow">Feel safe to try</p>
                <h2>You’re in safe hands</h2>
                <p class="copy">Clear information, reviewed offerings and real feedback from people who have booked through We Offer Wellness®. Everything is designed to help you understand what you are choosing before you book.</p>

                <div class="proof-row">
                    <span class="proof"><span class="dot"></span>Verified reviews</span>
                    <span class="proof"><span class="dot"></span>Clear pricing</span>
                    <span class="proof"><span class="dot"></span>Practitioner-led</span>
                </div>
            </article>

            <div class="side">
                <div class="stats">
                    <div class="stat">
                        <strong>{{ $avg_rating !== null ? number_format((float) $avg_rating, 1).' / 5' : '—' }}</strong>
                        <span>Average rating from verified reviews</span>
                    </div>
                    <div class="stat">
                        <strong>{{ number_format((int) ($verified_count ?? $review_count ?? 0)) }}</strong>
                        <span>Verified practitioner reviews</span>
                    </div>
                    <div class="stat">
                        <strong>{{ number_format((int) ($live_offering_count ?? 0)) }}</strong>
                        <span>Live offerings available now</span>
                    </div>
                </div>

                <div class="checks">
                    <div class="check">
                        <i>✓</i>
                        <div><h3>Know what you are booking</h3><p>Format, price, location and expectations should be clear before checkout.</p></div>
                    </div>
                    <div class="check">
                        <i>✓</i>
                        <div><h3>Check suitability first</h3><p>Practitioner details, safety notes and contraindications should be easy to review.</p></div>
                    </div>
                    <div class="check">
                        <i>✓</i>
                        <div><h3>Real people, real experiences</h3><p>Reviews help explain how a session actually feels in practice.</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    #trust-reviews, #trust-reviews * { box-sizing: border-box; font-family: "Instrument Sans", sans-serif; }
    #trust-reviews .wrap { width: min(calc(100% - 48px), 1280px); margin-inline: auto; }
    .safe { padding: 68px 0; background: #f8faf9; border-block: 1px solid #e9eeeb; }
    .safe-grid { display: grid; grid-template-columns: 1.05fr .95fr; gap: 22px; }
    .hero-card { position: relative; min-height: 410px; padding: 38px; border: 1px solid var(--wow-line, #dce4e0); border-radius: 4px; background: linear-gradient(145deg, rgba(79,148,130,.12), rgba(255,255,255,.82) 48%, #fff); overflow: hidden; }
    .hero-card::after { content: ""; position: absolute; width: 300px; height: 300px; right: -120px; bottom: -120px; border: 1px solid rgba(79,148,130,.15); border-radius: 50%; box-shadow: 0 0 0 55px rgba(79,148,130,.035), 0 0 0 110px rgba(79,148,130,.02); }
    .eyebrow { margin: 0 0 12px; color: var(--wow-green, #4f9482); font-size: 11px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; }
    #trust-reviews .hero-card h2 { max-width: 560px; margin: 0; color: #0b3028; font-family: "Playfair Display", Georgia, "Times New Roman", serif; font-size: clamp(46px, 5vw, 72px); font-weight: 500; line-height: .95; letter-spacing: -.05em; }
    .copy { max-width: 620px; margin: 18px 0 0; color: var(--wow-muted, #68736f); font-size: 15px; line-height: 1.65; }
    .proof-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 26px; }
    .proof { display: inline-flex; align-items: center; gap: 7px; padding: 9px 11px; border: 1px solid #cfe1da; border-radius: 999px; background: #fff; font-size: 11px; font-weight: 600; }
    .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--wow-green, #4f9482); }
    .side { display: grid; gap: 12px; }
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .stat { padding: 18px; border: 1px solid var(--wow-line, #dce4e0); border-radius: 4px; background: #fff; }
    .stat strong { display: block; color: var(--wow-green-dark, #0b3028); font-size: 28px; line-height: 1; }
    .stat span { display: block; margin-top: 7px; color: var(--wow-muted, #68736f); font-size: 10px; line-height: 1.4; }
    .checks { overflow: hidden; border: 1px solid var(--wow-line, #dce4e0); border-radius: 4px; background: #fff; }
    .check { display: grid; grid-template-columns: 34px 1fr; gap: 12px; padding: 17px; border-bottom: 1px solid var(--wow-line-soft, #e9eeeb); }
    .check:last-child { border-bottom: 0; }
    .check i { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; background: #eef6f3; color: var(--wow-green, #4f9482); font-style: normal; font-weight: 700; }
    .check h3 { margin: 0 0 4px; font-size: 13px; }
    .check p { margin: 0; color: var(--wow-muted, #68736f); font-size: 11px; line-height: 1.5; }
    @media (max-width: 900px) { .safe-grid { grid-template-columns: 1fr; } .hero-card { min-height: auto; } }
    @media (max-width: 575px) { .wrap { width: calc(100% - 30px); } .safe { padding: 42px 0; } .hero-card { padding: 26px 20px; } .stats { grid-template-columns: 1fr 1fr; } .stats .stat:last-child { grid-column: 1 / -1; } }
</style>
