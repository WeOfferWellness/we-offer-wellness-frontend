@extends('layouts.app')

@section('title', 'Become a Partner | We Offer Wellness™')
@section('meta_description', 'List your wellness offerings for free on We Offer Wellness and grow your practice with a dedicated profile, marketplace reach and WOW Studio tools.')

@section('content')
<style>
  .partners-page{--partner-ink:#101828;--partner-muted:#667085;--partner-green:#549483;--partner-green-dark:#417c6d;--partner-blue:#254a85;--partner-line:#e4e9ee;--partner-soft:#f4f8f7;color:var(--partner-ink);font-family:Inter,Manrope,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;overflow:hidden}
  .partners-page *{box-sizing:border-box}
  .partners-page .container-page{width:min(1160px,calc(100% - 40px));margin:0 auto}
  .partners-page h1,.partners-page h2,.partners-page h3,.partners-page p{margin-top:0}
  .partners-page h1,.partners-page h2{font-family:Playfair Display,Georgia,serif;font-weight:500;letter-spacing:-.045em}
  .partners-page h1{font-size:clamp(42px,6vw,76px);line-height:.98;margin-bottom:24px;max-width:760px}
  .partners-page h2{font-size:clamp(34px,4.4vw,56px);line-height:1.02;margin-bottom:18px}
  .partners-page h3{font-size:19px;line-height:1.15;margin-bottom:10px;letter-spacing:-.02em}
  .partners-page p{color:var(--partner-muted);line-height:1.65}
  .partners-hero{padding:96px 0 82px;background:linear-gradient(135deg,#f5faf8 0%,#fff 56%,#f4f7fc 100%);position:relative}
  .partners-hero:before{content:"";position:absolute;width:520px;height:520px;border-radius:50%;right:-220px;top:-230px;background:rgba(84,148,131,.08);pointer-events:none}
  .partners-hero__grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(360px,.8fr);gap:72px;align-items:center;position:relative}
  .partners-eyebrow,.partners-kicker{display:inline-flex;align-items:center;gap:9px;color:var(--partner-green-dark);font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;margin-bottom:20px}
  .partners-eyebrow:before,.partners-kicker:before{content:"";width:28px;height:2px;background:var(--partner-green);border-radius:2px}
  .partners-hero__copy>p{font-size:18px;max-width:680px;margin-bottom:28px}
  .partners-actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center}
  .partners-btn{display:inline-flex;align-items:center;justify-content:center;gap:12px;min-height:50px;padding:0 20px;border-radius:4px;border:1px solid transparent;text-decoration:none;font-size:13px;font-weight:800;transition:transform .18s ease,box-shadow .18s ease,background .18s ease}
  .partners-btn:hover{transform:translateY(-2px);box-shadow:0 12px 25px rgba(16,24,40,.1)}
  .partners-btn--primary{background:var(--partner-green);color:#fff}.partners-btn--primary:hover{background:var(--partner-green-dark);color:#fff}
  .partners-btn--outline{background:#fff;border-color:#b8c4cc;color:var(--partner-ink)}.partners-btn--outline:hover{color:var(--partner-green-dark)}
  .partners-hero__note{font-size:12px!important;margin:16px 0 0!important;color:#7a8792!important}
  .partners-console{border:1px solid rgba(16,24,40,.12);border-radius:18px;background:#fff;box-shadow:0 24px 70px rgba(16,24,40,.12);overflow:hidden;position:relative}
  .partners-console__top{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--partner-line);font-size:12px;font-weight:800}
  .partners-console__dots{display:flex;gap:5px}.partners-console__dots i{display:block;width:7px;height:7px;border-radius:50%;background:#d8e0e5}.partners-console__dots i:first-child{background:#f2b35d}.partners-console__dots i:nth-child(2){background:#82c8ae}
  .partners-console__body{padding:22px}.partners-console__welcome{display:flex;align-items:center;gap:12px;margin-bottom:22px}.partners-avatar{width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#549483,#254a85);display:grid;place-items:center;color:#fff;font-family:Playfair Display,Georgia,serif;font-size:22px}.partners-console__welcome strong{display:block;font-size:15px}.partners-console__welcome span{display:block;color:#98a2b3;font-size:11px;margin-top:3px}
  .partners-console__status{margin-left:auto;padding:7px 10px;border-radius:999px;background:#e8f5f1;color:#2f6f60;font-size:10px;font-weight:800;white-space:nowrap}
  .partners-console__stats{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-bottom:20px}.partners-stat{padding:13px;border:1px solid var(--partner-line);border-radius:10px}.partners-stat small{display:block;color:#98a2b3;font-size:10px;margin-bottom:6px}.partners-stat strong{font-size:20px;letter-spacing:-.04em}.partners-console__listing{border-radius:11px;background:#f8fafc;padding:13px;display:flex;align-items:center;gap:12px}.partners-listing-thumb{width:54px;height:54px;border-radius:8px;background:linear-gradient(135deg,#d8eee7,#dce7fa);position:relative;overflow:hidden}.partners-listing-thumb:after{content:"";position:absolute;width:42px;height:42px;right:-12px;bottom:-15px;border-radius:50%;background:#549483}.partners-console__listing strong{display:block;font-size:13px}.partners-console__listing span{display:block;color:#98a2b3;font-size:11px;margin-top:4px}
  .partners-section{padding:100px 0}.partners-section--soft{background:var(--partner-soft)}.partners-section--dark{background:#14232b;color:#fff}.partners-section--dark h2{color:#fff}.partners-section--dark p{color:#b8c6cc}.partners-intro{max-width:660px;margin-bottom:42px}.partners-intro p{font-size:17px}
  .partners-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.partners-card{background:#fff;border:1px solid var(--partner-line);border-radius:13px;padding:24px;min-height:220px}.partners-card__number{display:grid;place-items:center;width:34px;height:34px;border-radius:50%;background:#e8f5f1;color:var(--partner-green-dark);font-size:12px;font-weight:800;margin-bottom:27px}.partners-card p{font-size:13px;margin-bottom:0}.partners-card--dark{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.13)}.partners-card--dark .partners-card__number{background:rgba(135,209,185,.15);color:#9fe0c8}.partners-card--dark p{color:#b8c6cc}
  .partners-marketplace__grid{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);gap:72px;align-items:center}.partners-marketplace__grid h2{max-width:500px}.partners-marketplace__grid>div>p{max-width:540px}.partners-tags{display:flex;flex-wrap:wrap;gap:9px;margin-top:25px}.partners-tag{border:1px solid rgba(255,255,255,.18);border-radius:999px;padding:9px 12px;color:#d8e8e5;font-size:12px}.partners-marketplace__visual{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.partners-discovery{min-height:154px;padding:20px;border-radius:13px;background:#fff;color:var(--partner-ink)}.partners-discovery:nth-child(2){margin-top:28px;background:#d9eee8}.partners-discovery:nth-child(3){background:#dce7fa}.partners-discovery:nth-child(4){margin-top:28px;background:#f9e8c7}.partners-discovery small{display:block;color:#7b8791;font-size:10px;text-transform:uppercase;letter-spacing:.12em;margin-bottom:20px}.partners-discovery strong{display:block;font-family:Playfair Display,Georgia,serif;font-size:24px;font-weight:500;line-height:1.05}.partners-discovery span{display:block;color:#667085;font-size:11px;margin-top:9px}
  .partners-profile{display:grid;grid-template-columns:1fr 1fr;gap:72px;align-items:center}.partners-profile__copy p{max-width:520px}.partners-feature-list{display:grid;gap:15px;margin-top:28px}.partners-feature{display:flex;gap:14px;align-items:flex-start}.partners-feature__icon{flex:0 0 30px;width:30px;height:30px;border-radius:8px;background:#e8f5f1;color:var(--partner-green-dark);display:grid;place-items:center;font-size:14px;font-weight:800}.partners-feature strong{display:block;font-size:14px;margin-bottom:3px}.partners-feature span{display:block;color:var(--partner-muted);font-size:12px;line-height:1.5}.partners-profile-card{border:1px solid var(--partner-line);border-radius:15px;padding:20px;background:#fff;box-shadow:0 20px 55px rgba(16,24,40,.08)}.partners-profile-card__head{display:flex;align-items:center;gap:12px;padding-bottom:18px;border-bottom:1px solid var(--partner-line)}.partners-profile-card__avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#e8f5f1,#dce7fa);display:grid;place-items:center;color:var(--partner-green-dark);font-family:Playfair Display,Georgia,serif;font-size:25px}.partners-profile-card__head strong{display:block;font-size:16px}.partners-profile-card__head span{display:block;color:#98a2b3;font-size:11px;margin-top:4px}.partners-profile-card__verified{margin-left:auto;color:var(--partner-green);font-size:11px;font-weight:800}.partners-profile-card__body{padding:20px 0}.partners-profile-card__body h3{font-family:Playfair Display,Georgia,serif;font-size:25px;font-weight:500}.partners-profile-card__body p{font-size:12px;margin-bottom:20px}.partners-profile-card__chips{display:flex;gap:7px;flex-wrap:wrap}.partners-profile-card__chips span{background:#f1f5f6;border-radius:999px;padding:8px 10px;color:#596873;font-size:10px}.partners-profile-card__footer{display:flex;align-items:center;justify-content:space-between;padding-top:17px;border-top:1px solid var(--partner-line);font-size:11px;color:#7b8791}.partners-profile-card__footer strong{color:var(--partner-green-dark)}
  .partners-studio{padding:100px 0;background:linear-gradient(135deg,#edf8f4,#f5f7fc)}.partners-studio__head{display:flex;justify-content:space-between;gap:32px;align-items:end;margin-bottom:40px}.partners-studio__head .partners-intro{margin-bottom:0}.partners-studio__head p{max-width:640px}.partners-studio-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}.partners-studio-card{background:#fff;border:1px solid rgba(84,148,131,.17);border-radius:14px;padding:25px;display:flex;gap:18px;align-items:flex-start;min-height:190px}.partners-studio-card__icon{width:43px;height:43px;flex:0 0 43px;border-radius:11px;background:#142d35;color:#a4dfca;display:grid;place-items:center;font-size:18px;font-weight:800}.partners-studio-card h3{font-size:17px}.partners-studio-card p{font-size:12px;margin-bottom:0}.partners-studio-card__label{display:inline-block;color:var(--partner-green-dark);font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:11px}
  .partners-final{padding:100px 0;text-align:center}.partners-final h2{max-width:680px;margin:0 auto 18px}.partners-final p{max-width:600px;margin:0 auto 28px}.partners-final .partners-actions{justify-content:center}
  @media (max-width:900px){.partners-hero__grid,.partners-marketplace__grid,.partners-profile{grid-template-columns:1fr;gap:48px}.partners-hero__copy{max-width:760px}.partners-grid{grid-template-columns:repeat(2,1fr)}.partners-marketplace__visual{max-width:600px}.partners-profile-card{max-width:600px}.partners-studio__head{display:block}.partners-studio__head .partners-intro{margin-bottom:18px}}
  @media (max-width:620px){.partners-page .container-page{width:min(100% - 32px,1160px)}.partners-hero{padding:66px 0 58px}.partners-section,.partners-studio,.partners-final{padding:68px 0}.partners-hero__copy>p{font-size:16px}.partners-actions{align-items:stretch;flex-direction:column}.partners-btn{width:100%}.partners-console__stats{gap:6px}.partners-stat{padding:10px}.partners-stat strong{font-size:17px}.partners-grid,.partners-studio-grid{grid-template-columns:1fr}.partners-card{min-height:0}.partners-marketplace__visual{gap:8px}.partners-discovery{min-height:130px;padding:15px}.partners-discovery:nth-child(2),.partners-discovery:nth-child(4){margin-top:20px}.partners-profile-card{padding:16px}.partners-console__body{padding:16px}.partners-console__status{font-size:9px;padding:6px 7px}}
</style>

<div class="partners-page">
  <x-marketplace.page-section id="partners-hero" component="partners_hero" label="Partner hero">
  <section class="partners-hero">
    <div class="container-page partners-hero__grid">
      <div class="partners-hero__copy">
        <div class="partners-eyebrow">For practitioners and wellness businesses</div>
        <h1>Make your wellness work easier to find.</h1>
        <p>Become a We Offer Wellness Partner and put your practice, sessions, events, workshops, retreats or courses in front of people actively looking for wellbeing support.</p>
        <div class="partners-actions">
          <a class="partners-btn partners-btn--primary" href="{{ route('register', ['redirect' => '/account']) }}">Join 100% free <span aria-hidden="true">→</span></a>
          <a class="partners-btn partners-btn--outline" href="https://studio.weofferwellness.co.uk/" target="_blank" rel="noopener">Explore WOW Studio</a>
        </div>
        <p class="partners-hero__note">No joining fee. Create your profile, add your offerings and grow at your own pace.</p>
      </div>

      <div class="partners-console" aria-label="Illustration of a partner profile">
        <div class="partners-console__top"><span>Partner dashboard</span><span class="partners-console__dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
        <div class="partners-console__body">
          <div class="partners-console__welcome"><div class="partners-avatar">W</div><div><strong>Your wellness profile</strong><span>Ready to be discovered</span></div><span class="partners-console__status">Live profile</span></div>
          <div class="partners-console__stats"><div class="partners-stat"><small>Profile</small><strong>100%</strong></div><div class="partners-stat"><small>Offerings</small><strong>04</strong></div><div class="partners-stat"><small>Reach</small><strong>∞</strong></div></div>
          <div class="partners-console__listing"><div class="partners-listing-thumb" aria-hidden="true"></div><div><strong>Your next client starts here</strong><span>Clear details, availability and booking cues</span></div></div>
        </div>
      </div>
    </div>
  </section>
  </x-marketplace.page-section>

  <x-marketplace.page-section id="partners-model" component="partners_model" label="Partner model">
  <section class="partners-section partners-section--soft">
    <div class="container-page">
      <div class="partners-intro"><div class="partners-kicker">The partner model</div><h2>What does it mean to be a Partner?</h2><p>A Partner is a practitioner or wellness business with something valuable to offer. Your profile gives people a trusted place to understand your work, discover your offerings and take the next step.</p></div>
      <div class="partners-grid">
        <article class="partners-card"><div class="partners-card__number">01</div><h3>Build your profile</h3><p>Tell people who you are, what you do and the kind of experience they can expect from you.</p></article>
        <article class="partners-card"><div class="partners-card__number">02</div><h3>List your offerings</h3><p>Add sessions, therapies, classes, workshops, events, retreats and courses in one place.</p></article>
        <article class="partners-card"><div class="partners-card__number">03</div><h3>Be easier to find</h3><p>Give search engines and emerging AI discovery tools clearer information about your work.</p></article>
        <article class="partners-card"><div class="partners-card__number">04</div><h3>Grow your way</h3><p>Start with a free listing and add more tools when your practice is ready for them.</p></article>
      </div>
    </div>
  </section>
  </x-marketplace.page-section>

  <x-marketplace.page-section id="partners-marketplace" component="partners_marketplace" label="Marketplace positioning">
  <section class="partners-section partners-section--dark">
    <div class="container-page partners-marketplace__grid">
      <div><div class="partners-kicker">A marketplace for wellness</div><h2>Think Booking.com, but for wellness.</h2><p>We are building a dedicated marketplace for holistic therapies, classes, events, retreats and experiences. People can browse what is available, compare the right fit and book with confidence.</p><div class="partners-tags"><span class="partners-tag">Therapies</span><span class="partners-tag">Classes</span><span class="partners-tag">Events</span><span class="partners-tag">Retreats</span><span class="partners-tag">Workshops</span></div></div>
      <div class="partners-marketplace__visual" aria-hidden="true"><div class="partners-discovery"><small>Search discovery</small><strong>“Breathwork near me”</strong><span>Relevant local and online offerings</span></div><div class="partners-discovery"><small>Your profile</small><strong>Your story, clearly told</strong><span>One place for your work and links</span></div><div class="partners-discovery"><small>Booking cues</small><strong>Ready when they are</strong><span>Availability and next steps made clear</span></div><div class="partners-discovery"><small>GEO ready</small><strong>Useful context</strong><span>Structured information people can trust</span></div></div>
    </div>
  </section>
  </x-marketplace.page-section>

  <x-marketplace.page-section id="partners-profile" component="partners_profile" label="Partner profile">
  <section class="partners-section">
    <div class="container-page partners-profile">
      <div class="partners-profile__copy"><div class="partners-kicker">Your free foundation</div><h2>A dedicated home for your work.</h2><p>Your Partner profile brings your business story and offerings together. It helps a new client understand your approach before they decide to enquire or book.</p><div class="partners-feature-list"><div class="partners-feature"><div class="partners-feature__icon">✓</div><div><strong>One trusted profile</strong><span>Share your practice, location, online options, specialisms and social proof.</span></div></div><div class="partners-feature"><div class="partners-feature__icon">✓</div><div><strong>Offerings that make sense</strong><span>Present each service with clear details, pricing and booking information.</span></div></div><div class="partners-feature"><div class="partners-feature__icon">✓</div><div><strong>Made for discovery</strong><span>Build a stronger digital footprint without needing to become an SEO expert.</span></div></div></div></div>
      <div class="partners-profile-card"><div class="partners-profile-card__head"><div class="partners-profile-card__avatar">A</div><div><strong>Amara Wellness</strong><span>Somatic therapy · Online and in-person</span></div><div class="partners-profile-card__verified">● Verified</div></div><div class="partners-profile-card__body"><h3>Feel more at home in yourself</h3><p>A calm, practical space for nervous-system support, embodied movement and one-to-one care.</p><div class="partners-profile-card__chips"><span>Online</span><span>In-person</span><span>From £45</span><span>Request a time</span></div></div><div class="partners-profile-card__footer"><span>4 offerings listed</span><strong>View profile →</strong></div></div>
    </div>
  </section>
  </x-marketplace.page-section>

  <x-marketplace.page-section id="partners-studio" component="partners_studio" label="WOW Studio">
  <section class="partners-studio">
    <div class="container-page">
      <div class="partners-studio__head"><div class="partners-intro"><div class="partners-kicker">For the next stage</div><h2>When you are ready to grow, meet WOW Studio.</h2></div><p>WOW Studio is the practitioner workspace behind We Offer Wellness. The Business Accelerator brings the practical tools and automations that help you spend less time stitching systems together and more time with clients.</p></div>
      <div class="partners-studio-grid">
        <article class="partners-studio-card"><div class="partners-studio-card__icon">↗</div><div><span class="partners-studio-card__label">Business Accelerator</span><h3>Follow up and grow past clients</h3><p>Use thoughtful automations and next-step journeys to reconnect with past clients and create natural opportunities to upsell relevant support.</p></div></article>
        <article class="partners-studio-card"><div class="partners-studio-card__icon">@</div><div><span class="partners-studio-card__label">@ease calls</span><h3>Your calls, at ease</h3><p>Run secure practitioner and client calls inside Studio — familiar like Zoom, but designed around the wellness relationship.</p></div></article>
        <article class="partners-studio-card"><div class="partners-studio-card__icon">✦</div><div><span class="partners-studio-card__label">Client workspace</span><h3>Keep the relationship together</h3><p>Use secure messaging and client management tools to keep conversations, care and next actions in one organised place.</p></div></article>
        <article class="partners-studio-card"><div class="partners-studio-card__icon">⌁</div><div><span class="partners-studio-card__label">Track what works</span><h3>Links and QR codes with insight</h3><p>Create tracked links and tracked QR codes so you can see which campaigns, printed materials and recommendations bring people to you.</p></div></article>
      </div>
    </div>
  </section>
  </x-marketplace.page-section>

  <x-marketplace.page-section id="partners-cta" component="partners_cta" label="Partner call to action">
  <section class="partners-final"><div class="container-page"><div class="partners-kicker">Start simply</div><h2>Your next client may already be looking for what you do.</h2><p>Join We Offer Wellness for free, create your Partner profile and put your offerings where wellness-minded people are already searching.</p><div class="partners-actions"><a class="partners-btn partners-btn--primary" href="https://studio.weofferwellness.co.uk/register" target="_blank" rel="noopener">Become a Partner — free <span aria-hidden="true">→</span></a><a class="partners-btn partners-btn--outline" href="https://studio.weofferwellness.co.uk/" target="_blank" rel="noopener">See WOW Studio</a></div></div></section>
  </x-marketplace.page-section>
</div>
@endsection
