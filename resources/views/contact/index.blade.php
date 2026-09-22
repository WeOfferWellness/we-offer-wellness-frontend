@extends('layouts.app')

@section('title', $seo['title'] ?? 'Contact | We Offer Wellness™')
@section('meta_description', $seo['description'] ?? 'Get in touch with We Offer Wellness for booking support, partnerships and general enquiries.')

@section('content')
@php
  $topic = strtolower(trim((string) request()->query('topic', '')));
  $topicSubjects = [
    'support' => 'Booking support',
    'partners' => 'Partnership enquiry',
    'corporate' => 'Corporate wellness enquiry',
    'events' => 'Events and workshops enquiry',
    'gifting' => 'Gift cards enquiry',
    'feedback' => 'Website feedback',
    'safety' => 'Safety enquiry',
    'retreats' => 'Retreat enquiry',
  ];
  $subject = $topicSubjects[$topic] ?? 'General enquiry';
  $mailUrl = 'mailto:hello@weofferwellness.co.uk?subject='.rawurlencode($subject);
  $topics = [
    [
      'key' => 'support',
      'eyebrow' => 'Already booked?',
      'title' => 'Booking support',
      'copy' => 'Questions about a booking, availability, payment or what happens next? We can help point you in the right direction.',
      'href' => '/help',
      'label' => 'Visit the help centre',
    ],
    [
      'key' => 'partners',
      'eyebrow' => 'Work with us',
      'title' => 'Practitioners & partners',
      'copy' => 'Want to list your wellness offering or explore a partnership with We Offer Wellness?',
      'href' => '/partners',
      'label' => 'Explore partnerships',
    ],
    [
      'key' => 'corporate',
      'eyebrow' => 'For teams',
      'title' => 'Corporate wellness',
      'copy' => 'Tell us what your team or organisation is looking for and we will help shape the right experience.',
      'href' => '/corporate',
      'label' => 'Explore corporate wellness',
    ],
  ];
@endphp

@push('head')
  <style>
    .contact-page {
      --contact-ink: #17232b;
      --contact-muted: #627078;
      --contact-line: #dfe8e5;
      --contact-soft: #f2f8f5;
      --contact-green: #4f9381;
      --contact-green-dark: #397565;
      padding: 0 0 88px;
      color: var(--contact-ink);
      background: linear-gradient(180deg, #f8fbfa 0, #fff 430px);
    }
    .contact-page__hero {
      padding: 72px 0 48px;
      text-align: center;
    }
    .contact-page__eyebrow,
    .contact-page__card-eyebrow {
      margin: 0 0 12px;
      color: var(--contact-green-dark);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .18em;
      text-transform: uppercase;
    }
    .contact-page h1,
    .contact-page h2,
    .contact-page h3 {
      color: var(--contact-ink);
      font-family: "Playfair Display", Georgia, "Times New Roman", serif;
      font-weight: 500;
      letter-spacing: -.045em;
    }
    .contact-page h1 {
      max-width: 760px;
      margin: 0 auto;
      font-size: clamp(46px, 7vw, 86px);
      line-height: .95;
    }
    .contact-page__intro {
      max-width: 620px;
      margin: 22px auto 0;
      color: var(--contact-muted);
      font-size: 17px;
      line-height: 1.65;
    }
    .contact-page__selected {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 22px;
      padding: 9px 14px;
      border: 1px solid #cfe4dc;
      border-radius: 999px;
      background: #fff;
      color: var(--contact-green-dark);
      font-size: 13px;
      font-weight: 700;
    }
    .contact-page__selected::before {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--contact-green);
      content: "";
    }
    .contact-page__topics {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 18px;
      margin-top: 28px;
    }
    .contact-page__card {
      display: flex;
      min-height: 250px;
      flex-direction: column;
      align-items: flex-start;
      padding: 26px;
      border: 1px solid var(--contact-line);
      border-radius: 20px;
      background: rgba(255,255,255,.94);
      box-shadow: 0 16px 42px rgba(23,35,43,.055);
      text-align: left;
      text-decoration: none;
      transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .contact-page__card:hover,
    .contact-page__card:focus-visible {
      border-color: #a8d0c2;
      box-shadow: 0 20px 48px rgba(23,35,43,.1);
      transform: translateY(-3px);
    }
    .contact-page__card-eyebrow { margin-bottom: 15px; color: #7a898f; }
    .contact-page__card h2 {
      margin: 0;
      font-size: 30px;
      line-height: 1;
    }
    .contact-page__card p {
      flex: 1;
      margin: 14px 0 20px;
      color: var(--contact-muted);
      font-size: 14px;
      line-height: 1.6;
    }
    .contact-page__card-link {
      color: var(--contact-green-dark);
      font-size: 13px;
      font-weight: 800;
    }
    .contact-page__lower {
      display: grid;
      grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr);
      gap: 22px;
      margin-top: 22px;
    }
    .contact-page__email,
    .contact-page__aside {
      border-radius: 22px;
      padding: 32px;
    }
    .contact-page__email {
      background: var(--contact-ink);
      color: #fff;
    }
    .contact-page__email h2,
    .contact-page__email p { color: #fff; }
    .contact-page__email h2 {
      margin: 0;
      font-size: clamp(32px, 4vw, 48px);
      line-height: 1;
    }
    .contact-page__email p {
      max-width: 560px;
      margin: 15px 0 25px;
      color: #c7d5d5;
      line-height: 1.65;
    }
    .contact-page__email-address {
      display: inline-block;
      margin-bottom: 22px;
      color: #bce2d3;
      font-size: clamp(20px, 3vw, 28px);
      font-weight: 700;
      text-decoration: none;
    }
    .contact-page__email-address:hover { color: #fff; }
    .contact-page__email .btn-wow { background: #fff; color: var(--contact-ink); }
    .contact-page__aside {
      border: 1px solid var(--contact-line);
      background: var(--contact-soft);
    }
    .contact-page__aside h2 {
      margin: 0;
      font-size: 32px;
      line-height: 1;
    }
    .contact-page__aside p {
      margin: 15px 0 20px;
      color: var(--contact-muted);
      font-size: 14px;
      line-height: 1.65;
    }
    .contact-page__links {
      display: grid;
      gap: 10px;
      margin: 0;
      padding: 0;
      list-style: none;
    }
    .contact-page__links a {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      padding: 12px 0;
      border-top: 1px solid #d5e5df;
      color: var(--contact-green-dark);
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
    }
    .contact-page__links a::after { content: "→"; }
    @media (max-width: 820px) {
      .contact-page__topics,
      .contact-page__lower { grid-template-columns: 1fr; }
      .contact-page__hero { padding-top: 52px; }
    }
    @media (max-width: 520px) {
      .contact-page { padding-bottom: 56px; }
      .contact-page__hero { padding-bottom: 30px; }
      .contact-page__card,
      .contact-page__email,
      .contact-page__aside { padding: 24px; }
    }
  </style>
@endpush

@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Contact'],
  ],
  'schemaUrl' => $seo['canonical'] ?? url('/contact'),
])

<main class="contact-page">
  <div class="container-page">
    <header class="contact-page__hero">
      <p class="contact-page__eyebrow">We’re here to help</p>
      <h1>Let’s find the right way forward.</h1>
      <p class="contact-page__intro">Whether you need help with a booking, want to work with us, or have a question about wellness, send us a note and we’ll get back to you.</p>
      @if(isset($topicSubjects[$topic]))
        <div class="contact-page__selected">Enquiry: {{ $topicSubjects[$topic] }}</div>
      @endif
    </header>

    <section aria-label="Contact topics">
      <div class="contact-page__topics">
        @foreach($topics as $card)
          <a class="contact-page__card" href="{{ $card['href'] }}">
            <p class="contact-page__card-eyebrow">{{ $card['eyebrow'] }}</p>
            <h2>{{ $card['title'] }}</h2>
            <p>{{ $card['copy'] }}</p>
            <span class="contact-page__card-link">{{ $card['label'] }} →</span>
          </a>
        @endforeach
      </div>
    </section>

    <section class="contact-page__lower" aria-label="Contact details">
      <div class="contact-page__email">
        <p class="contact-page__eyebrow" style="color:#bce2d3;">Prefer email?</p>
        <h2>Send us a message.</h2>
        <p>Include a little context and, if relevant, your booking reference or practitioner name. This helps us get you to the right answer faster.</p>
        <a class="contact-page__email-address" href="{{ $mailUrl }}">hello@weofferwellness.co.uk</a>
        <br>
        <a class="btn-wow btn-wow--outline" href="{{ $mailUrl }}">Email the team <span aria-hidden="true">→</span></a>
      </div>

      <aside class="contact-page__aside">
        <p class="contact-page__eyebrow">Good to know</p>
        <h2>Start here.</h2>
        <p>For common questions, these pages may get you where you need to go straight away.</p>
        <ul class="contact-page__links">
          <li><a href="/help">Help centre</a></li>
          <li><a href="/help/faq">Frequently asked questions</a></li>
          <li><a href="/safety-and-contraindications">Safety &amp; contraindications</a></li>
        </ul>
      </aside>
    </section>
  </div>
</main>
@endsection
