{{-- resources/views/help/centre.blade.php --}}
@extends('layouts.app')

@php
    $pageTitle = $title ?? 'Help Centre';
    $pageDescription = $metaDescription ?? 'Find answers to common questions about booking, providers, and using We Offer Wellness™.';
    $pageCanonical = $canonical ?? url('/help');

    // Help Centre content model (single-page “docs” style)
    $categories = [
        [
            'id' => 'booking',
            'title' => 'Bookings & Payments',
            'desc' => 'Booking, rescheduling, refunds, invoices and payment queries.',
            'icon' => 'calendar',
        ],
        [
            'id' => 'account',
            'title' => 'Account & Privacy',
            'desc' => 'Login, email changes, data, privacy and notifications.',
            'icon' => 'shield',
        ],
        [
            'id' => 'providers',
            'title' => 'Providers & Safety',
            'desc' => 'How we review providers, safety, suitability and complaints.',
            'icon' => 'badge',
        ],
        [
            'id' => 'gifts',
            'title' => 'Gift Vouchers',
            'desc' => 'Buying, redeeming, expiry, swapping and resend issues.',
            'icon' => 'gift',
        ],
        [
            'id' => 'online',
            'title' => 'Online Sessions',
            'desc' => 'Joining links, tech setup, audio/video and troubleshooting.',
            'icon' => 'video',
        ],
        [
            'id' => 'corporate',
            'title' => 'Corporate Wellness',
            'desc' => 'Team bookings, invoicing, reporting and employee access.',
            'icon' => 'building',
        ],
    ];

    $articles = [
        // Booking & Payments
        [
            'id' => 'change-booking',
            'category' => 'booking',
            'title' => 'How to reschedule or change your booking',
            'excerpt' => 'What you can change, how far in advance, and what happens if the provider is unavailable.',
            'readTime' => '3 min',
            'updated' => 'February 2026',
            'tags' => ['Reschedule', 'Booking'],
            'popular' => true,
            'content' => '
                <p>If you need to reschedule, the fastest route is usually to message your practitioner via your booking confirmation (or the provider messaging area, if available in your account).</p>
                <h3>Best practice</h3>
                <ul>
                    <li>Give as much notice as possible (it increases the chance of a smooth switch).</li>
                    <li>Propose 2–3 alternative times rather than just asking “when are you free?”</li>
                    <li>If your booking is time-sensitive (travel, childcare, etc.), mention it upfront.</li>
                </ul>
                <h3>What if my practitioner can’t do the new time?</h3>
                <p>If your chosen practitioner cannot accommodate the new time, we’ll help you find the next best option where possible.</p>
            ',
        ],
        [
            'id' => 'refunds',
            'category' => 'booking',
            'title' => 'Refunds, cancellations and credit',
            'excerpt' => 'A clear overview of what’s refundable, what’s not, and what happens next.',
            'readTime' => '4 min',
            'updated' => 'February 2026',
            'tags' => ['Refunds', 'Cancellation'],
            'popular' => true,
            'content' => '
                <p>Refund eligibility depends on the specific offering and the timing of the cancellation. Some therapies may be non-refundable once a practitioner has allocated time and preparation.</p>
                <h3>Typical outcomes</h3>
                <ul>
                    <li><strong>Refund</strong> to original payment method (where applicable).</li>
                    <li><strong>Credit</strong> for future use (sometimes offered as an alternative).</li>
                    <li><strong>Reschedule</strong> as the preferred option when possible.</li>
                </ul>
                <p>If you’re unsure, email us with your order details and we’ll confirm the correct route.</p>
            ',
        ],
        [
            'id' => 'payment-failed',
            'category' => 'booking',
            'title' => 'My payment failed — what should I do?',
            'excerpt' => 'Common causes and quick fixes.',
            'readTime' => '2 min',
            'updated' => 'February 2026',
            'tags' => ['Payments'],
            'popular' => false,
            'content' => '
                <p>Payment failures are usually caused by one of the following:</p>
                <ul>
                    <li>Bank security checks (especially for first-time purchases)</li>
                    <li>Insufficient funds or card limits</li>
                    <li>Billing address mismatch</li>
                    <li>Temporary bank/provider outage</li>
                </ul>
                <p>Try again, double-check your billing details, and consider using a different card if it repeats.</p>
            ',
        ],
        [
            'id' => 'invoice-receipt',
            'category' => 'booking',
            'title' => 'Can I get a receipt or invoice?',
            'excerpt' => 'Yes — here’s what we can provide and how to request it.',
            'readTime' => '2 min',
            'updated' => 'February 2026',
            'tags' => ['Invoice'],
            'popular' => false,
            'content' => '
                <p>You can request an invoice/receipt by emailing us with your order number and the details you’d like on the invoice (company name, address, VAT details if relevant).</p>
            ',
        ],

        // Account & Privacy
        [
            'id' => 'reset-password',
            'category' => 'account',
            'title' => 'Resetting your password',
            'excerpt' => 'Locked out? No judgement. Here’s how to get back in quickly.',
            'readTime' => '2 min',
            'updated' => 'February 2026',
            'tags' => ['Login'],
            'popular' => true,
            'content' => '
                <p>Use the “Forgot password” link on the sign-in page. We’ll email you a secure reset link.</p>
                <h3>If the email doesn’t arrive</h3>
                <ul>
                    <li>Check spam/junk folders</li>
                    <li>Search for “We Offer Wellness” in your inbox</li>
                    <li>Wait 2–3 minutes (email can occasionally lag)</li>
                </ul>
                <p>If it still doesn’t arrive, email support and we’ll help.</p>
            ',
        ],
        [
            'id' => 'change-email',
            'category' => 'account',
            'title' => 'Changing your email address',
            'excerpt' => 'How to update your login email safely.',
            'readTime' => '2 min',
            'updated' => 'February 2026',
            'tags' => ['Account'],
            'popular' => false,
            'content' => '
                <p>For security, email changes may require verification. If you no longer have access to your old email, contact us and we’ll confirm ownership before switching.</p>
            ',
        ],
        [
            'id' => 'data-privacy',
            'category' => 'account',
            'title' => 'How we handle your personal data',
            'excerpt' => 'What we store, why we store it, and your options.',
            'readTime' => '4 min',
            'updated' => 'February 2026',
            'tags' => ['Privacy'],
            'popular' => false,
            'content' => '
                <p>We collect only what we need to provide the service (e.g., booking details and contact information). You can request access to your data or ask for deletion where applicable.</p>
                <p>For more detail, see our privacy policy.</p>
            ',
        ],

        // Providers & Safety
        [
            'id' => 'vetting',
            'category' => 'providers',
            'title' => 'How we vet providers on We Offer Wellness™',
            'excerpt' => 'What providers confirm on sign-up, and what we review.',
            'readTime' => '3 min',
            'updated' => 'February 2026',
            'tags' => ['Safety', 'Providers'],
            'popular' => true,
            'content' => '
                <p>Practitioners on We Offer Wellness™ are independent professionals. When they join, they confirm they hold appropriate qualifications, experience and (where required) professional insurance.</p>
                <p>We review profiles for clarity and alignment with our wellness guidelines and may remove providers where we have concerns about safety or professionalism.</p>
                <p>Want the full guidance? Read our Safety &amp; Contraindications page.</p>
            ',
        ],
        [
            'id' => 'pregnancy',
            'category' => 'providers',
            'title' => 'I’m pregnant — can I still book?',
            'excerpt' => 'What’s usually fine, what to avoid, and who to ask.',
            'readTime' => '3 min',
            'updated' => 'February 2026',
            'tags' => ['Pregnancy', 'Suitability'],
            'popular' => true,
            'content' => '
                <p>Many practitioners offer pregnancy-friendly options, but not all treatments are suitable during pregnancy or after birth.</p>
                <ul>
                    <li>Check the session description carefully</li>
                    <li>Tell your practitioner you’re pregnant</li>
                    <li>If unsure, check with your midwife or GP</li>
                </ul>
            ',
        ],
        [
            'id' => 'complaint',
            'category' => 'providers',
            'title' => 'Raising a concern or complaint',
            'excerpt' => 'How to report something, what we review, and what happens next.',
            'readTime' => '3 min',
            'updated' => 'February 2026',
            'tags' => ['Support'],
            'popular' => false,
            'content' => '
                <p>Email us at <a href="mailto:hello@weofferwellness.co.uk">hello@weofferwellness.co.uk</a> with details of what happened and the name of the practitioner.</p>
                <p>We review all safety-related reports and may follow up with you and the practitioner. Where appropriate, we may suspend or remove accounts from the platform.</p>
            ',
        ],

        // Gift Vouchers
        [
            'id' => 'buy-voucher',
            'category' => 'gifts',
            'title' => 'Buying a gift voucher',
            'excerpt' => 'How it works, delivery, and what the recipient sees.',
            'readTime' => '2 min',
            'updated' => 'February 2026',
            'tags' => ['Vouchers'],
            'popular' => true,
            'content' => '
                <p>Gift vouchers can be purchased online and sent to the recipient. If you need a resend or custom message, contact support with your order details.</p>
            ',
        ],
        [
            'id' => 'redeem-voucher',
            'category' => 'gifts',
            'title' => 'Redeeming a voucher',
            'excerpt' => 'Where to enter it and what to do if it doesn’t work.',
            'readTime' => '3 min',
            'updated' => 'February 2026',
            'tags' => ['Vouchers', 'Checkout'],
            'popular' => false,
            'content' => '
                <p>Enter the voucher code at checkout. If the code is rejected, double-check spelling and confirm it hasn’t already been used or expired.</p>
                <p>If it still fails, email support with the code and your intended booking.</p>
            ',
        ],

        // Online Sessions
        [
            'id' => 'join-online',
            'category' => 'online',
            'title' => 'Joining an online session',
            'excerpt' => 'Link access, audio/video checks and best setup tips.',
            'readTime' => '4 min',
            'updated' => 'February 2026',
            'tags' => ['Online', 'Tech'],
            'popular' => true,
            'content' => '
                <h3>Before you start</h3>
                <ul>
                    <li>Use a stable connection (Wi-Fi preferred)</li>
                    <li>Headphones recommended for sound baths or guided meditations</li>
                    <li>Test your mic/camera 5 minutes beforehand</li>
                </ul>
                <h3>If your link won’t open</h3>
                <ul>
                    <li>Try a different browser</li>
                    <li>Disable extensions that block popups</li>
                    <li>Open on mobile as a fallback</li>
                </ul>
            ',
        ],
        [
            'id' => 'audio-issues',
            'category' => 'online',
            'title' => 'Fixing audio issues (no sound / echo)',
            'excerpt' => 'The quick checklist that solves 90% of problems.',
            'readTime' => '3 min',
            'updated' => 'February 2026',
            'tags' => ['Audio'],
            'popular' => false,
            'content' => '
                <ul>
                    <li>Check the call audio device selection (output + input)</li>
                    <li>Unmute your device and your in-call controls</li>
                    <li>Try headphones to eliminate echo</li>
                    <li>Close other apps using the microphone</li>
                </ul>
            ',
        ],

        // Corporate
        [
            'id' => 'corp-booking',
            'category' => 'corporate',
            'title' => 'Corporate bookings and team sessions',
            'excerpt' => 'How group bookings work and what we need from you.',
            'readTime' => '4 min',
            'updated' => 'February 2026',
            'tags' => ['Corporate'],
            'popular' => false,
            'content' => '
                <p>For team bookings, we can help coordinate dates, delivery format (online/in-person), and access for employees.</p>
                <p>Email us with approximate headcount, preferred dates, location and goals (stress reduction, team reset, etc.).</p>
            ',
        ],
        [
            'id' => 'corp-invoice',
            'category' => 'corporate',
            'title' => 'Corporate invoicing and purchase orders',
            'excerpt' => 'Invoice details, PO references and payment timelines.',
            'readTime' => '3 min',
            'updated' => 'February 2026',
            'tags' => ['Invoice', 'PO'],
            'popular' => false,
            'content' => '
                <p>We can support invoicing and purchase orders. Send billing details, PO references, and any required invoice formatting.</p>
            ',
        ],
    ];

    $popular = array_values(array_filter($articles, fn($a) => !empty($a['popular'])));
    $articlesById = [];
    foreach ($articles as $a) { $articlesById[$a['id']] = $a; }

    $categoryMap = [];
    foreach ($categories as $c) { $categoryMap[$c['id']] = $c; }

    $quickLinks = [
        ['label' => 'Safety & Contraindications', 'href' => url('/safety-and-contraindications')],
        ['label' => 'Privacy Policy', 'href' => url('/privacy')],
        ['label' => 'Terms', 'href' => url('/terms')],
        ['label' => 'Cookies', 'href' => url('/cookies')],
    ];

    $popularSearches = ['Refund', 'Reschedule', 'Voucher', 'Pregnancy', 'Invoice', 'Audio issues'];
@endphp

@section('title', $pageTitle)

{{-- If your layout doesn’t use @yield("head"), move this into whatever your layout uses for meta tags --}}
@section('head')
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $pageCanonical }}">
@endsection

@section('content')
<x-marketplace.page-section id="breadcrumbs" component="breadcrumbs" label="Breadcrumbs">
@include('partials.breadcrumbs', [
    'crumbs' => [
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Help Centre'],
    ],
    'schemaUrl' => $pageCanonical,
])
</x-marketplace.page-section>

<style>
    :root{
        --hc-bg: #f6f7fb;
        --hc-card: rgba(255,255,255,.92);
        --hc-border: rgba(16,24,40,.12);
        --hc-ink: #0b1220;
        --hc-muted: rgba(11,18,32,.72);
        --hc-soft: rgba(11,18,32,.08);
        --hc-shadow: 0 18px 50px rgba(16,24,40,.10);
        --hc-radius: 18px;
        --hc-radius-lg: 22px;
        --hc-focus: 0 0 0 4px rgba(75, 137, 255, .18);
        --hc-accent: #4b89ff; /* subtle techy blue */
    }
    .hc{background: var(--hc-bg); color: var(--hc-ink);}
    .hc .wrap{max-width: 1180px; margin: 0 auto; padding: 28px 18px 64px;}
    .hc .hero{
        border: 1px solid var(--hc-border);
        background:
            radial-gradient(1200px 600px at 70% -10%, rgba(75,137,255,.18), transparent 60%),
            radial-gradient(1000px 500px at 10% 110%, rgba(0,245,212,.10), transparent 60%),
            var(--hc-card);
        box-shadow: var(--hc-shadow);
        border-radius: var(--hc-radius-lg);
        padding: 26px 22px;
    }
    .hc h1{font-size: clamp(28px, 3vw, 40px); line-height: 1.12; margin: 8px 0 10px; letter-spacing: -.02em;}
    .hc p{margin: 0; color: var(--hc-muted); line-height: 1.55;}
    .hc .kicker{font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: rgba(11,18,32,.62);}
    .hc .grid{display:grid; gap: 16px;}
    @media (min-width: 980px){
        .hc .grid{grid-template-columns: 320px 1fr;}
    }

    /* Search */
    .hc .searchRow{display:flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 18px;}
    .hc .search{
        flex: 1;
        min-width: 240px;
        display:flex;
        align-items:center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 999px;
        border: 1px solid var(--hc-border);
        background: rgba(255,255,255,.78);
    }
    .hc .search input{
        border: 0; outline: 0; background: transparent; width: 100%;
        font-size: 14px; color: var(--hc-ink);
    }
    .hc .search:focus-within{box-shadow: var(--hc-focus); border-color: rgba(75,137,255,.35);}
    .hc .chipRow{display:flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;}
    .hc .chip{
        border: 1px solid var(--hc-border);
        background: rgba(255,255,255,.75);
        padding: 8px 10px;
        border-radius: 999px;
        font-size: 13px;
        color: rgba(11,18,32,.80);
        cursor: pointer;
        user-select: none;
        transition: transform .12s ease, background .12s ease;
    }
    .hc .chip:hover{transform: translateY(-1px); background: rgba(255,255,255,.95);}

    /* Sidebar */
    .hc .panel{
        border: 1px solid var(--hc-border);
        background: var(--hc-card);
        border-radius: var(--hc-radius);
        box-shadow: 0 10px 26px rgba(16,24,40,.08);
        overflow:hidden;
    }
    .hc .panelHd{padding: 14px 14px 10px; border-bottom: 1px solid var(--hc-border);}
    .hc .panelHd strong{font-size: 13px; letter-spacing: .08em; text-transform: uppercase; color: rgba(11,18,32,.62);}
    .hc .catList{display:flex; flex-direction:column;}
    .hc .catBtn{
        display:flex; gap: 12px; align-items:flex-start;
        padding: 12px 14px;
        border: 0; background: transparent; text-align:left;
        cursor:pointer;
        border-bottom: 1px solid rgba(16,24,40,.06);
        transition: background .14s ease;
    }
    .hc .catBtn:hover{background: rgba(11,18,32,.04);}
    .hc .catBtn[aria-selected="true"]{
        background: rgba(75,137,255,.08);
        box-shadow: inset 3px 0 0 rgba(75,137,255,.65);
    }
    .hc .catTitle{font-weight: 700; font-size: 14px;}
    .hc .catDesc{font-size: 12.5px; color: rgba(11,18,32,.68); margin-top: 2px;}
    .hc .ico{
        width: 34px; height: 34px; border-radius: 12px;
        display:flex; align-items:center; justify-content:center;
        border: 1px solid rgba(16,24,40,.10);
        background: rgba(255,255,255,.85);
        flex: 0 0 auto;
    }
    .hc .ico svg{width: 18px; height: 18px; opacity: .86;}
    .hc .sideLinks{padding: 12px 14px;}
    .hc .sideLinks a{
        display:flex; justify-content:space-between; align-items:center;
        padding: 10px 12px; border-radius: 12px; text-decoration:none;
        border: 1px solid rgba(16,24,40,.08);
        background: rgba(255,255,255,.72);
        color: rgba(11,18,32,.88);
        font-size: 13.5px;
        margin-bottom: 10px;
    }
    .hc .sideLinks a:hover{background: rgba(255,255,255,.95);}

    /* Main */
    .hc .mainGrid{display:grid; gap: 16px;}
    @media (min-width: 980px){
        .hc .mainGrid{grid-template-columns: 1fr 420px;}
    }
    .hc .card{padding: 16px; border:1px solid var(--hc-border); background: var(--hc-card); border-radius: var(--hc-radius); box-shadow: 0 10px 26px rgba(16,24,40,.08);}
    .hc .cardTitle{display:flex; gap: 10px; align-items:center; justify-content:space-between;}
    .hc .cardTitle h2{margin: 0; font-size: 16px; letter-spacing: -.01em;}
    .hc .muted{color: rgba(11,18,32,.65); font-size: 13px;}

    .hc .articleList{display:flex; flex-direction:column; gap: 10px; margin-top: 12px;}
    .hc .articleBtn{
        width: 100%;
        text-align:left;
        border: 1px solid rgba(16,24,40,.10);
        background: rgba(255,255,255,.75);
        border-radius: 16px;
        padding: 12px 12px;
        cursor:pointer;
        transition: transform .12s ease, background .12s ease, border-color .12s ease;
    }
    .hc .articleBtn:hover{transform: translateY(-1px); background: rgba(255,255,255,.95); border-color: rgba(75,137,255,.25);}
    .hc .articleBtn[aria-selected="true"]{background: rgba(75,137,255,.08); border-color: rgba(75,137,255,.35); box-shadow: 0 0 0 4px rgba(75,137,255,.10);}
    .hc .articleBtn .t{font-weight: 750; font-size: 14px; color: rgba(11,18,32,.92);}
    .hc .articleBtn .e{font-size: 13px; color: rgba(11,18,32,.65); margin-top: 3px; line-height: 1.4;}
    .hc .metaRow{display:flex; gap: 10px; flex-wrap: wrap; margin-top: 8px;}
    .hc .pill{font-size: 12px; padding: 6px 10px; border-radius: 999px; border:1px solid rgba(16,24,40,.10); background: rgba(255,255,255,.72); color: rgba(11,18,32,.72);}

    /* Viewer */
    .hc .viewer h3{margin: 0; font-size: 20px; letter-spacing: -.02em;}
    .hc .viewer .byline{margin-top: 6px; display:flex; gap: 10px; flex-wrap: wrap; align-items:center;}
    .hc .viewer .content{margin-top: 14px; color: rgba(11,18,32,.78); line-height: 1.7;}
    .hc .viewer .content h3{font-size: 16px; margin-top: 16px;}
    .hc .viewer .content a{color: rgba(75,137,255,.95); text-decoration: none;}
    .hc .viewer .content a:hover{text-decoration: underline;}

    /* FAQ */
    .hc details{border:1px solid rgba(16,24,40,.10); background: rgba(255,255,255,.75); border-radius: 16px; padding: 12px 14px;}
    .hc details + details{margin-top: 10px;}
    .hc summary{cursor:pointer; font-weight: 750; color: rgba(11,18,32,.92);}
    .hc details p{margin-top: 10px;}

    /* Contact */
    .hc .cta{
        display:flex; gap: 12px; flex-wrap: wrap; align-items:center; justify-content:space-between;
        padding: 14px 14px;
        border-radius: 18px;
        border: 1px solid rgba(75,137,255,.25);
        background: rgba(75,137,255,.08);
    }
    .hc .btn{
        display:inline-flex; align-items:center; gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid rgba(16,24,40,.14);
        background: rgba(255,255,255,.88);
        color: rgba(11,18,32,.92);
        font-weight: 700;
        font-size: 13.5px;
        text-decoration:none;
        cursor:pointer;
    }
    .hc .btn:hover{background: rgba(255,255,255,1);}
    .hc .btn.primary{border-color: rgba(75,137,255,.35); box-shadow: 0 0 0 4px rgba(75,137,255,.12);}
</style>

<main class="hc" aria-labelledby="hc-title">
    <div class="wrap">

        {{-- HERO --}}
        <x-marketplace.page-section id="help-hero" component="help_hero" label="Help Centre hero">
        <section class="hero" aria-label="Help Centre">
            <div class="kicker">We Offer Wellness™ Support</div>
            <h1 id="hc-title">{{ $pageTitle }}</h1>
            <p>
                Search for answers, browse modalities, or contact us if you need help with a booking.
            </p>

            <div class="searchRow">
                <div class="search" role="search">
                    <span aria-hidden="true" style="display:inline-flex">
                        {{-- magnifier --}}
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input id="hcSearch" type="search" placeholder="Search the Help Centre (e.g. refund, voucher, pregnancy, invoice)…" autocomplete="off" />
                </div>

                <a class="btn primary" href="mailto:hello@weofferwellness.co.uk">
                    Contact support <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="chipRow" aria-label="Popular searches">
                @foreach($popularSearches as $q)
                    <button class="chip" type="button" data-hc-chip="{{ $q }}">{{ $q }}</button>
                @endforeach
            </div>
        </section>
        </x-marketplace.page-section>

        <x-marketplace.page-section id="help-content" component="help_content" label="Help Centre content">
        <div class="grid" style="margin-top: 16px;">

            {{-- SIDEBAR --}}
            <aside class="panel" aria-label="Help modalities">
                <div class="panelHd">
                    <strong>Modalities</strong>
                </div>

                <div class="catList" id="hcCategories">
                    @foreach($categories as $i => $c)
                        <button
                            type="button"
                            class="catBtn"
                            data-hc-cat="{{ $c['id'] }}"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                        >
                            <span class="ico" aria-hidden="true">
                                @if($c['icon'] === 'calendar')
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M7 3v2M17 3v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 7h16" stroke="currentColor" stroke-width="2"/><path d="M6 5h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2"/></svg>
                                @elseif($c['icon'] === 'shield')
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 2 20 6v7c0 5-3.5 8.7-8 9-4.5-.3-8-4-8-9V6l8-4Z" stroke="currentColor" stroke-width="2"/></svg>
                                @elseif($c['icon'] === 'badge')
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 2 19 6v7c0 4-2.7 7.4-7 8-4.3-.6-7-4-7-8V6l7-4Z" stroke="currentColor" stroke-width="2"/><path d="M9.5 12.2 11 13.7l3.6-3.9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif($c['icon'] === 'gift')
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M20 12v10H4V12" stroke="currentColor" stroke-width="2"/><path d="M22 7H2v5h20V7Z" stroke="currentColor" stroke-width="2"/><path d="M12 7v15" stroke="currentColor" stroke-width="2"/><path d="M12 7H7.5A2.5 2.5 0 1 1 10 4.5C10 6.7 12 7 12 7Z" stroke="currentColor" stroke-width="2"/><path d="M12 7h4.5A2.5 2.5 0 1 0 14 4.5C14 6.7 12 7 12 7Z" stroke="currentColor" stroke-width="2"/></svg>
                                @elseif($c['icon'] === 'video')
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="2"/><path d="m16 10 5-3v10l-5-3v-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 21V5a2 2 0 0 1 2-2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z" stroke="currentColor" stroke-width="2"/><path d="M14 3v6h6" stroke="currentColor" stroke-width="2"/></svg>
                                @endif
                            </span>

                            <span>
                                <div class="catTitle">{{ $c['title'] }}</div>
                                <div class="catDesc">{{ $c['desc'] }}</div>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="panelHd">
                    <strong>Quick links</strong>
                </div>
                <div class="sideLinks">
                    @foreach($quickLinks as $l)
                        <a href="{{ $l['href'] }}">
                            <span>{{ $l['label'] }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </aside>

            {{-- MAIN --}}
            <section class="mainGrid" aria-label="Help centre content">

                {{-- Article list --}}
                <div class="card" aria-label="Articles">
                    <div class="cardTitle">
                        <h2>Articles</h2>
                        <div class="muted">
                            <span id="hcResultsCount">{{ count($articles) }}</span> results
                        </div>
                    </div>

                    <div class="metaRow" style="margin-top:10px;">
                        <span class="pill" id="hcActiveCatPill">Modality: {{ $categories[0]['title'] }}</span>
                        <span class="pill" id="hcActiveSearchPill" style="display:none;">Search: <span id="hcActiveSearchText"></span></span>
                        <button class="pill" type="button" id="hcClear" style="cursor:pointer;">Clear filters</button>
                    </div>

                    <div class="articleList" id="hcArticleList">
                        @foreach($articles as $i => $a)
                            <button
                                type="button"
                                class="articleBtn"
                                data-hc-article="{{ $a['id'] }}"
                                data-hc-category="{{ $a['category'] }}"
                                data-hc-title="{{ $a['title'] }}"
                                data-hc-excerpt="{{ $a['excerpt'] }}"
                                aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            >
                                <div class="t">{{ $a['title'] }}</div>
                                <div class="e">{{ $a['excerpt'] }}</div>
                                <div class="metaRow">
                                    <span class="pill">{{ $categoryMap[$a['category']]['title'] ?? 'General' }}</span>
                                    <span class="pill">{{ $a['readTime'] }}</span>
                                    <span class="pill">Updated {{ $a['updated'] }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Viewer --}}
                <div class="card viewer" aria-label="Article viewer">
                    <div class="cardTitle">
                        <h2>Help Article</h2>
                        <div class="muted">Live preview</div>
                    </div>

                    <div id="hcViewer">
                        {{-- filled by JS --}}
                        <div class="muted" style="margin-top:12px;">
                            Select an article to view it here.
                        </div>
                    </div>

                    <div style="margin-top:14px;">
                        <div class="cta" aria-label="Need more help">
                            <div>
                                <div style="font-weight:800; letter-spacing:-.01em;">Still stuck?</div>
                                <div class="muted">Email us and we’ll help you out. (We don’t bite. Much.)</div>
                            </div>
                            <a class="btn" href="mailto:hello@weofferwellness.co.uk">
                                Email support <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- FAQ + Popular --}}
                <div class="card" aria-label="Popular topics and FAQs">
                    <div class="cardTitle">
                        <h2>Popular topics</h2>
                        <div class="muted">Quick answers</div>
                    </div>

                    <div class="articleList" style="margin-top:12px;">
                        @foreach(array_slice($popular, 0, 4) as $p)
                            <button
                                type="button"
                                class="articleBtn"
                                data-hc-article="{{ $p['id'] }}"
                                data-hc-category="{{ $p['category'] }}"
                                aria-selected="false"
                            >
                                <div class="t">{{ $p['title'] }}</div>
                                <div class="e">{{ $p['excerpt'] }}</div>
                                <div class="metaRow">
                                    <span class="pill">{{ $categoryMap[$p['category']]['title'] ?? 'General' }}</span>
                                    <span class="pill">{{ $p['readTime'] }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <div style="margin-top: 14px;">
                        <div class="cardTitle" style="margin-bottom:10px;">
                            <h2>FAQs</h2>
                            <div class="muted">The classics</div>
                        </div>

                        <details>
                            <summary>Are your therapies safe?</summary>
                            <p>Yes. Our providers follow industry best practices for health, safety and hygiene. If you have specific concerns, feel free to ask before booking.</p>
                        </details>

                        <details>
                            <summary>I’m pregnant — can I still book?</summary>
                            <p>Many practitioners offer pregnancy-friendly options, but not all treatments are suitable during pregnancy or after birth. Check the session description and tell your practitioner you’re pregnant. If unsure, check with your midwife or GP first.</p>
                        </details>

                        <details>
                            <summary>How do I raise a concern or complaint?</summary>
                            <p>Email <a href="mailto:hello@weofferwellness.co.uk">hello@weofferwellness.co.uk</a> with details of what happened and the practitioner name. We review all safety-related reports and may suspend or remove accounts where appropriate.</p>
                        </details>
                    </div>
                </div>

            </section>
        </div>
        </x-marketplace.page-section>
    </div>
</main>

<script>
    (function(){
        const categories = @json($categories);
        const categoryMap = @json($categoryMap);
        const articlesById = @json($articlesById);

        const $ = (s, el=document) => el.querySelector(s);
        const $$ = (s, el=document) => Array.from(el.querySelectorAll(s));

        const searchInput = $('#hcSearch');
        const articleList = $('#hcArticleList');
        const viewer = $('#hcViewer');
        const categoriesWrap = $('#hcCategories');
        const resultsCount = $('#hcResultsCount');
        const activeCatPill = $('#hcActiveCatPill');
        const activeSearchPill = $('#hcActiveSearchPill');
        const activeSearchText = $('#hcActiveSearchText');
        const clearBtn = $('#hcClear');

        let activeCategory = categories?.[0]?.id || null;
        let activeQuery = '';

        function setSelected(selector, attr, value){
            $$(selector).forEach(el => el.setAttribute(attr, el.getAttribute(value) ? 'false' : 'false'));
        }

        function setCat(catId){
            activeCategory = catId;
            // update sidebar selected
            $$('#hcCategories .catBtn').forEach(btn => {
                btn.setAttribute('aria-selected', btn.dataset.hcCat === catId ? 'true' : 'false');
            });

            const catTitle = categoryMap?.[catId]?.title || 'All';
            activeCatPill.textContent = 'Modality: ' + catTitle;

            filterArticles();
        }

        function setQuery(q){
            activeQuery = (q || '').trim();
            if(activeQuery){
                activeSearchText.textContent = activeQuery;
                activeSearchPill.style.display = '';
            }else{
                activeSearchPill.style.display = 'none';
                activeSearchText.textContent = '';
            }
            filterArticles();
        }

        function matchesQuery(article, q){
            if(!q) return true;
            const hay = (article.title + ' ' + article.excerpt + ' ' + (article.tags||[]).join(' ')).toLowerCase();
            return hay.includes(q.toLowerCase());
        }

        function filterArticles(){
            const items = $$('#hcArticleList .articleBtn');
            let shown = 0;

            items.forEach(btn => {
                const id = btn.dataset.hcArticle;
                const a = articlesById[id];
                if(!a){ btn.style.display = 'none'; return; }

                const inCat = !activeCategory || a.category === activeCategory;
                const inSearch = matchesQuery(a, activeQuery);

                const visible = inCat && inSearch;
                btn.style.display = visible ? '' : 'none';
                if(visible) shown++;
            });

            resultsCount.textContent = shown;

            // If the currently selected article is hidden, pick the first visible one
            const selected = items.find(x => x.getAttribute('aria-selected') === 'true' && x.style.display !== 'none');
            if(!selected){
                const first = items.find(x => x.style.display !== 'none');
                if(first) openArticle(first.dataset.hcArticle, true);
            }
        }

        function openArticle(articleId, silent){
            const a = articlesById[articleId];
            if(!a) return;

            // highlight in lists
            $$('.articleBtn[data-hc-article]').forEach(btn => {
                btn.setAttribute('aria-selected', btn.dataset.hcArticle === articleId ? 'true' : 'false');
            });

            // render viewer
            viewer.innerHTML = `
                <h3>${escapeHtml(a.title)}</h3>
                <div class="byline">
                    <span class="pill">${escapeHtml(categoryMap?.[a.category]?.title || 'General')}</span>
                    <span class="pill">${escapeHtml(a.readTime || '')}</span>
                    <span class="pill">Updated ${escapeHtml(a.updated || '')}</span>
                </div>
                <div class="content">${a.content || ''}</div>
                <div style="margin-top:14px;">
                    <a class="btn" href="mailto:hello@weofferwellness.co.uk?subject=${encodeURIComponent('Help Centre: ' + a.title)}">
                        Ask about this article <span aria-hidden="true">→</span>
                    </a>
                </div>
            `;

            if(!silent){
                history.replaceState(null, '', '#'+articleId);
            }
        }

        function escapeHtml(str){
            return String(str ?? '')
                .replaceAll('&','&amp;')
                .replaceAll('<','&lt;')
                .replaceAll('>','&gt;')
                .replaceAll('"','&quot;')
                .replaceAll("'",'&#039;');
        }

        // Events: categories
        categoriesWrap?.addEventListener('click', (e) => {
            const btn = e.target.closest('.catBtn');
            if(!btn) return;
            setCat(btn.dataset.hcCat);
        });

        // Events: article click (both lists)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.articleBtn[data-hc-article]');
            if(!btn) return;
            const id = btn.dataset.hcArticle;
            const a = articlesById[id];
            if(a){
                setCat(a.category); // keep UI consistent
                openArticle(id);
            }
        });

        // Events: search
        let t = null;
        searchInput?.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => setQuery(searchInput.value), 80);
        });

        // Chips
        $$('.chip[data-hc-chip]').forEach(chip => {
            chip.addEventListener('click', () => {
                const q = chip.dataset.hcChip || '';
                searchInput.value = q;
                setQuery(q);
            });
        });

        // Clear
        clearBtn?.addEventListener('click', () => {
            searchInput.value = '';
            setQuery('');
            setCat(categories?.[0]?.id || null);
        });

        // Initial state: hash or first popular or first in list
        const hashId = (location.hash || '').replace('#','').trim();
        if(hashId && articlesById[hashId]){
            const a = articlesById[hashId];
            setCat(a.category);
            openArticle(hashId, true);
        }else{
            // open first visible (default category) or first popular
            setCat(categories?.[0]?.id || null);
            const firstPopular = Object.values(articlesById).find(x => x.popular) || Object.values(articlesById)[0];
            if(firstPopular){
                openArticle(firstPopular.id, true);
                // also visually align selection in default category if possible
                filterArticles();
            }
        }

        // Apply initial filtering (category default)
        filterArticles();
    })();
</script>
@endsection
