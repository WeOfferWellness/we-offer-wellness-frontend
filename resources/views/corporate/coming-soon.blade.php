@extends('layouts.app')

@section('title', $seo['title'] ?? 'Corporate Wellness 2026')
@section('meta_description', $seo['description'] ?? '')
@section('meta_robots', $seo['robots'] ?? 'noindex,follow')

@push('styles')
<style>
  .corporate-hero{
    position:relative;
    overflow:hidden;
    padding:clamp(56px, 8vw, 110px) 0 36px;
    background:
      radial-gradient(circle at top left, rgba(89,157,145,.22), transparent 30%),
      radial-gradient(circle at 85% 15%, rgba(15,23,42,.10), transparent 28%),
      linear-gradient(180deg, #f8fcfb 0%, #ffffff 58%, #f6f8fb 100%);
  }
  .corporate-hero__grid{
    display:grid;
    gap:28px;
    align-items:start;
    grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);
  }
  .corporate-kicker{
    display:inline-flex;
    align-items:center;
    gap:8px;
    border:1px solid rgba(15,23,42,.12);
    background:#fff;
    border-radius:999px;
    padding:8px 14px;
    font-size:.85rem;
    font-weight:700;
    color:#0f172a;
    letter-spacing:.02em;
    margin-bottom:18px;
  }
  .corporate-kicker__dot{
    width:10px;
    height:10px;
    border-radius:999px;
    background:#599d91;
    box-shadow:0 0 0 6px rgba(89,157,145,.12);
  }
  .corporate-hero h1{
    margin:0 0 18px;
    font-size:clamp(2.4rem, 4vw, 4.6rem);
    line-height:.95;
    letter-spacing:-.04em;
    max-width:10ch;
  }
  .corporate-hero .lead{
    font-size:1.08rem;
    line-height:1.75;
    color:#334155;
    max-width:62ch;
  }
  .corporate-badges{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin:24px 0 0;
    padding:0;
    list-style:none;
  }
  .corporate-badges li{
    border-radius:999px;
    background:#0f172a;
    color:#fff;
    padding:10px 14px;
    font-size:.88rem;
    font-weight:600;
  }
  .corporate-panel{
    position:relative;
    z-index:1;
    background:#fff;
    border:1px solid rgba(15,23,42,.10);
    border-radius:28px;
    box-shadow:0 24px 70px rgba(15,23,42,.10);
    padding:24px;
  }
  .corporate-panel h2{
    margin:0 0 8px;
    font-size:1.65rem;
  }
  .corporate-panel p{
    color:#475569;
    margin:0 0 18px;
    line-height:1.6;
  }
  .corporate-form{
    display:grid;
    gap:14px;
  }
  .corporate-form__grid{
    display:grid;
    gap:14px;
    grid-template-columns:repeat(2, minmax(0,1fr));
  }
  .corporate-field{
    display:grid;
    gap:6px;
  }
  .corporate-field label{
    font-weight:700;
    font-size:.92rem;
    color:#0f172a;
  }
  .corporate-field input,
  .corporate-field select{
    width:100%;
    border:1px solid rgba(15,23,42,.15);
    border-radius:14px;
    padding:14px 15px;
    font-size:1rem;
    background:#fff;
    color:#0f172a;
  }
  .corporate-field input:focus,
  .corporate-field select:focus{
    outline:none;
    border-color:#599d91;
    box-shadow:0 0 0 4px rgba(89,157,145,.16);
  }
  .corporate-form__wide{
    grid-column:1 / -1;
  }
  .corporate-form .btn-wow{
    width:100%;
    justify-content:center;
    min-height:52px;
  }
  .corporate-note{
    font-size:.9rem;
    color:#64748b;
    line-height:1.55;
    margin:0;
  }
  .corporate-sections{
    padding:28px 0 78px;
  }
  .corporate-card-grid{
    display:grid;
    gap:18px;
    grid-template-columns:repeat(3, minmax(0,1fr));
    margin-top:18px;
  }
  .corporate-card{
    background:#fff;
    border:1px solid rgba(15,23,42,.08);
    border-radius:22px;
    padding:22px;
    box-shadow:0 16px 42px rgba(15,23,42,.06);
  }
  .corporate-card h3{
    margin:0 0 10px;
    font-size:1.15rem;
  }
  .corporate-card p,
  .corporate-card li{
    color:#475569;
    line-height:1.65;
  }
  .corporate-card ul{
    margin:0;
    padding-left:18px;
  }
  .corporate-banner{
    margin-top:22px;
    padding:22px;
    border-radius:22px;
    background:linear-gradient(90deg, #0f172a, #1f3a36);
    color:#fff;
    box-shadow:0 18px 42px rgba(15,23,42,.18);
  }
  .corporate-banner p{
    margin:0;
    color:rgba(255,255,255,.88);
    line-height:1.7;
  }
  @media (max-width: 991.98px){
    .corporate-hero__grid,
    .corporate-card-grid,
    .corporate-form__grid{
      grid-template-columns:1fr;
    }
    .corporate-hero h1{
      max-width:none;
    }
  }
</style>
@endpush

@section('content')
<x-marketplace.page-section id="corporate-hero" component="corporate_hero" label="Corporate hero">
<section class="corporate-hero">
  <div class="container-page">
    <div class="corporate-hero__grid">
      <div>
        <div class="corporate-kicker"><span class="corporate-kicker__dot"></span> Corporate Wellness 2026</div>
        <h1>A new way to support happier, healthier teams is coming.</h1>
        <p class="lead">
          We Offer Wellness® is building a smarter corporate wellness offering for modern teams.
          From wellbeing days and team building to employee rewards, corporate gift vouchers and live workshops,
          we are creating a simpler way to reward and support people at work.
        </p>
        <p class="lead">
          Whether your team is hybrid, remote or office-based, this launch will help businesses book meaningful
          wellbeing experiences without the usual admin drag.
        </p>

        <ul class="corporate-badges" aria-label="Key benefits">
          <li>Wellbeing days</li>
          <li>Employee rewards</li>
          <li>Team building</li>
          <li>Workshops</li>
          <li>Gift vouchers</li>
        </ul>
      </div>

      <aside class="corporate-panel" aria-labelledby="corporateWaitlistTitle">
        <h2 id="corporateWaitlistTitle">Join the 2026 waiting list</h2>
        <p>Subscribe now for launch updates, early access and first look at corporate packages.</p>

        <form class="corporate-form"
              data-subscriber-form="corporate-waitlist"
              data-subscriber-source="corporate-wellness:coming-soon"
              data-subscriber-tags="corporate_interest"
              novalidate>
          <div class="corporate-form__grid">
            <div class="corporate-field">
              <label for="corp_name">Full name</label>
              <input id="corp_name" type="text" name="name" autocomplete="name" placeholder="Alex Taylor">
            </div>
            <div class="corporate-field">
              <label for="corp_email">Work email</label>
              <input id="corp_email" type="email" name="email" autocomplete="email" required placeholder="alex@company.co.uk">
            </div>
            <div class="corporate-field">
              <label for="corp_company">Company name</label>
              <input id="corp_company" type="text" name="business_name" autocomplete="organization" placeholder="Company Ltd">
            </div>
            <div class="corporate-field">
              <label for="corp_size">Team size</label>
              <select id="corp_size" name="team_size">
                <option value="">Select team size</option>
                <option value="1-10">1-10</option>
                <option value="11-25">11-25</option>
                <option value="26-50">26-50</option>
                <option value="51-100">51-100</option>
                <option value="100+">100+</option>
              </select>
            </div>
            <div class="corporate-field corporate-form__wide">
              <label for="corp_interest">What are you interested in?</label>
              <select id="corp_interest" name="interest">
                <option value="">Select an option</option>
                <option value="Corporate wellness days">Corporate wellness days</option>
                <option value="Employee rewards">Employee rewards</option>
                <option value="Team building activities">Team building activities</option>
                <option value="Corporate gift vouchers">Corporate gift vouchers</option>
                <option value="Online wellness workshops">Online wellness workshops</option>
                <option value="WOW Studio access">WOW Studio access</option>
                <option value="Not sure yet">Not sure yet</option>
              </select>
            </div>
          </div>

          <button class="btn-wow btn-wow--primary" type="submit">Join the 2026 list</button>
          <p class="corporate-note">No spam. Just useful updates, early access and workplace wellbeing opportunities.</p>
        </form>
      </aside>
    </div>
  </div>
</section>
</x-marketplace.page-section>

<x-marketplace.page-section id="corporate-content" component="corporate_content" label="Corporate content">
<section class="corporate-sections">
  <div class="container-page">
    <div class="corporate-card-grid">
      <article class="corporate-card">
        <h3>Built for modern teams</h3>
        <p>We are designing a corporate wellness platform that makes it easy to support people across remote, hybrid and office settings.</p>
      </article>
      <article class="corporate-card">
        <h3>What is coming in 2026?</h3>
        <ul>
          <li>Corporate wellness experiences</li>
          <li>Employee rewards and incentives</li>
          <li>Flexible corporate gift vouchers</li>
          <li>Online and in-person wellbeing options</li>
        </ul>
      </article>
      <article class="corporate-card">
        <h3>Why join early?</h3>
        <ul>
          <li>Early access before launch</li>
          <li>Launch offers and partnership updates</li>
          <li>Priority access to new experiences</li>
          <li>Chance to shape the offer</li>
        </ul>
      </article>
    </div>

    <div class="corporate-card-grid" style="margin-top:18px;">
      <article class="corporate-card">
        <h3>Who it is for</h3>
        <ul>
          <li>HR and People teams</li>
          <li>Wellbeing managers</li>
          <li>Startups and growing teams</li>
          <li>Hybrid and remote businesses</li>
        </ul>
      </article>
      <article class="corporate-card">
        <h3>Examples of support</h3>
        <ul>
          <li>Mindfulness and breathwork</li>
          <li>Sound healing and massage</li>
          <li>Wellness workshops and classes</li>
          <li>Wellbeing days and team sessions</li>
        </ul>
      </article>
      <article class="corporate-card">
        <h3>How it works</h3>
        <p>Subscribe now, tell us what you need, and we’ll keep you posted as the corporate wellness launch gets closer.</p>
      </article>
    </div>

    <div class="corporate-banner">
      <p><strong>Support your people before burnout becomes the business strategy.</strong> We Offer Wellness® is building a more accessible way for companies to discover, book and manage workplace wellbeing support. Join the list and be first in line.</p>
    </div>
  </div>
</section>
</x-marketplace.page-section>
@endsection
