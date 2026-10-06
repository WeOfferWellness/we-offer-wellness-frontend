{{-- resources/views/about/index.blade.php --}}
@extends('layouts.app')

@php
    $pageTitle = $title ?? 'About We Offer Wellness®';
    $pageDescription = $metaDescription ?? 'Meet the founders of We Offer Wellness®, learn our story, and discover how we connect people with trusted holistic therapies, classes, events, and practitioner tools across the UK.';
    $pageCanonical = $canonical ?? url('/about');
    $pageOgImage = asset('images/about-social-preview.jpg');
    $seo = $seo ?? [
        'title' => 'About We Offer Wellness® | Trusted Holistic Therapies & Wellness Tools',
        'description' => $pageDescription,
        'canonical' => $pageCanonical,
        'og_image' => $pageOgImage,
        'og_image_alt' => 'We Offer Wellness about page social preview image',
        'site_name' => 'We Offer Wellness®',
        'twitter_card' => 'summary_large_image',
    ];

    $founders = [
        [
            'name' => 'Ian',
            'role' => 'CEO',
            'tagline' => 'A lifelong student of wellbeing, sound, and ancient wisdom.',
            'bio' => [
                "Ian began exploring meditation in 1991, particularly its connection with music, using percussive instruments such as chimes, singing bowls, bells, and drums. This naturally led him to playing gongs — a deep passion of his — and he now loves facilitating gong bath meditation sessions.",
                "For many years, Ian has been fascinated by ancient cultures, especially civilisations like Egypt and China, which continue to inspire him.",
                "He’s equally passionate about anything that promotes wellbeing, healing, and reconnecting people with their sense of humanness. He teaches Tai Chi Qigong, holds regular meditation sessions, and has written over 30 books — all expressions of his lifelong commitment to personal and collective growth.",
                "Ian is proud and grateful to be building We Offer Wellness® — a meaningful contribution to humanity and a platform to provide empowering wellness opportunities in a world that deeply needs them.",
            ],
            'highlights' => [
                'Meditation since 1991',
                'Gong bath facilitator',
                'Tai Chi Qigong teacher',
                'Author of 30+ books',
            ],
        ],
        [
            'name' => 'Loz',
            'role' => 'CSO',
            'tagline' => 'Two decades of holistic practice, grounded in empathy and experience.',
            'bio' => [
                "Lorraine has been a qualified holistic therapist since 2002 and brings over two decades of experience to her work.",
                "Her journey began with reflexology, but her passion for holistic wellness has led her to train in a wide range of therapies - from reiki and Indian head massage to life coaching and various forms of foot and facial reflexology.",
                "Known for her gentle, compassionate approach, Lorraine believes that empathy is just as important as technique when supporting others on their wellness journey.",
                "She has worked within the NHS, offering therapies in both a Drug & Alcohol Counselling Service and the Respiratory Clinic of an NHS Trust Hospital - combining years of experience with a deeply intuitive healing touch.",
                "Today, Lorraine offers a broad spectrum of treatments, including maternity and lymphatic reflexology; various facial therapies; lava shell and warm bamboo massage; EFT, and even animal and equine healing. She continues to support people (and animals!) with an open heart and a commitment to holistic care.",
            ],
            'highlights' => [
                'Qualified since 2002',
                'NHS experience',
                'Reflexology, Reiki, EFT + more',
                'Human + animal healing',
            ],
        ],
        [
            'name' => 'Tash',
            'role' => 'CCO',
            'tagline' => 'Holistic Health Coach & Breathwork Facilitator — and the friendly engine behind the scenes.',
            'bio' => [
                "Tash is a certified Holistic Health Coach and Breathwork Facilitator who, for many years, supported women and teenagers in regaining their menstrual cycles after experiencing hypothalamic amenorrhea — a condition often caused by mental stress, under-fuelling, or over-exercising.",
                "She’s also a proud mum to her beautiful toddler, Arabella, who’s very much part of the little We Offer Wellness® family!",
                "At WOW®, Tash plays a key role in the day-to-day running of the platform, managing communications with both providers and customers. She also leads our digital presence — especially on Instagram — helping to keep the wellness conversation flowing.",
                "In April 2024, she launched her podcast Seeking Wellness with Tash, created for anyone curious about alternative therapies or looking for practical ways to feel better. Give it a listen and follow along here!",
            ],
            'highlights' => [
                'Breathwork facilitator',
                'Holistic health coaching',
                'Community + communications',
                'Podcast host',
            ],
            // Replace with your real link when ready
            'link' => [
                'label' => 'Seeking Wellness with Tash',
                'href' => '#',
            ],
        ],
        [
            'name' => 'Dan',
            'role' => 'CTO',
            'tagline' => 'The one turning chaos into code (and caffeine into features).',
            'bio' => [
                "Known internally as Dandulf the Wizard by the We Offer Wellness® founders, Daniel is the spell-slinging technologist behind the platform.",
                "With over a decade of experience — including time at Google and Spotify — he’s architected everything from multi-vendor wellness marketplaces to AI-powered social networks, often while debugging issues that were definitely not his fault.",
                "As the mastermind behind @ease, Daniel leads all things tech, turning chaos into code and caffeine into features.",
            ],
            'highlights' => [
                'Platform architecture',
                'Marketplace + systems engineering',
                'Google + Spotify experience',
                '@ease product lead',
            ],
        ],
    ];
@endphp

@section('content')
<x-marketplace.page-section id="breadcrumbs" component="breadcrumbs" label="Breadcrumbs">
@include('partials.breadcrumbs', [
    'crumbs' => [
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'About We Offer Wellness'],
    ],
    'schemaUrl' => $pageCanonical,
])
</x-marketplace.page-section>

<style>
    :root{
        --ab-bg: #f6f7fb;
        --ab-card: rgba(255,255,255,.92);
        --ab-border: rgba(16,24,40,.12);
        --ab-ink: #0b1220;
        --ab-muted: rgba(11,18,32,.72);
        --ab-soft: rgba(11,18,32,.08);
        --ab-shadow: 0 18px 50px rgba(16,24,40,.10);
        --ab-radius: 18px;
        --ab-radius-lg: 22px;
        --ab-focus: 0 0 0 4px rgba(75, 137, 255, .18);
        --ab-accent: #4b89ff;
    }
    .ab{background: transparent; color: var(--ab-ink);}
    .ab .wrap{max-width: 1180px; margin: 0 auto; padding: 28px 18px 72px;}
    .ab .kicker{font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: rgba(11,18,32,.62);}
    .ab h1{font-size: clamp(30px, 3.2vw, 44px); line-height: 1.08; margin: 8px 0 10px; letter-spacing: -.025em;}
    .ab h2{font-size: clamp(20px, 2.1vw, 28px); margin: 0; letter-spacing: -.02em;}
    .ab p{margin: 0; color: var(--ab-muted); line-height: 1.65;}
    .ab .hero{
        border: 1px solid var(--ab-border);
        background:
            radial-gradient(1200px 600px at 70% -10%, rgba(75,137,255,.18), transparent 60%),
            radial-gradient(900px 520px at 10% 110%, rgba(0,245,212,.10), transparent 60%),
            var(--ab-card);
        box-shadow: var(--ab-shadow);
        border-radius: var(--ab-radius-lg);
        padding: 28px 22px;
        overflow: hidden;
        position: relative;
    }
    .ab .heroGrid{display:grid; gap: 18px; align-items: start;}
    @media (min-width: 980px){
        .ab .heroGrid{grid-template-columns: 1.2fr .8fr;}
    }
    .ab .heroCard{
        border: 1px solid rgba(16,24,40,.10);
        background: rgba(255,255,255,.72);
        border-radius: 18px;
        padding: 16px 16px;
        box-shadow: 0 10px 26px rgba(16,24,40,.06);
    }
    .ab .chips{display:flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;}
    .ab .chip{
        font-size: 12.5px;
        padding: 7px 10px;
        border-radius: 999px;
        border: 1px solid rgba(16,24,40,.10);
        background: rgba(255,255,255,.80);
        color: rgba(11,18,32,.74);
        user-select: none;
    }

    .ab .section{margin-top: 18px;}
    .ab .grid{display:grid; gap: 16px;}
    @media (min-width: 980px){
        .ab .grid.two{grid-template-columns: 1fr 1fr;}
        .ab .grid.three{grid-template-columns: repeat(3, 1fr);}
    }

    .ab .card{
        border: 1px solid var(--ab-border);
        background: var(--ab-card);
        border-radius: var(--ab-radius);
        box-shadow: 0 10px 26px rgba(16,24,40,.08);
        padding: 16px;
    }
    .ab .cardHd{display:flex; align-items:flex-start; justify-content:space-between; gap: 10px;}
    .ab .muted{color: rgba(11,18,32,.64); font-size: 13px;}

    .ab .timeline{display:grid; gap: 12px; margin-top: 12px;}
    .ab .step{
        display:flex; gap: 12px; align-items:flex-start;
        padding: 12px 12px;
        border-radius: 16px;
        border: 1px solid rgba(16,24,40,.10);
        background: rgba(255,255,255,.75);
    }
    .ab .dot{
        width: 28px; height: 28px; border-radius: 12px;
        display:flex; align-items:center; justify-content:center;
        border: 1px solid rgba(75,137,255,.28);
        background: rgba(75,137,255,.10);
        color: rgba(11,18,32,.90);
        flex: 0 0 auto;
        font-weight: 800;
        font-size: 12px;
    }
    .ab .step strong{display:block; font-size: 14px; letter-spacing:-.01em; color: rgba(11,18,32,.92);}
    .ab .step .txt{margin-top: 2px; font-size: 13.5px; color: rgba(11,18,32,.72); line-height: 1.6;}

    .ab .founders{margin-top: 16px;}
    .ab .foundersBanner{
        margin-top: 16px;
        overflow: hidden;
        border: 1px solid rgba(16,24,40,.12);
        border-radius: 20px;
        background: rgba(255,255,255,.9);
        box-shadow: 0 12px 30px rgba(16,24,40,.08);
    }
    .ab .foundersBanner img{
        display:block;
        width:100%;
        aspect-ratio: 21 / 9;
        object-fit: cover;
        object-position: center center;
    }
    @media (max-width: 767.98px){
        .ab .foundersBanner img{
            aspect-ratio: 16 / 9;
        }
    }
    .ab .founder{
        padding: 16px;
        border-radius: 20px;
        border: 1px solid rgba(16,24,40,.12);
        background: rgba(255,255,255,.78);
        box-shadow: 0 12px 30px rgba(16,24,40,.08);
        overflow:hidden;
    }
    .ab .avatar{
        width: 44px; height: 44px; border-radius: 16px;
        display:flex; align-items:center; justify-content:center;
        border: 1px solid rgba(16,24,40,.12);
        background: rgba(255,255,255,.95);
        font-weight: 900;
        letter-spacing:-.02em;
    }
    .ab .who{display:flex; gap: 12px; align-items:center;}
    .ab .who h3{margin:0; font-size: 16px; letter-spacing:-.01em;}
    .ab .who .role{
        display:inline-flex; align-items:center;
        font-size: 12px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(75,137,255,.25);
        background: rgba(75,137,255,.08);
        color: rgba(11,18,32,.78);
        font-weight: 800;
        letter-spacing:.02em;
        text-transform: uppercase;
    }
    .ab .tagline{margin-top: 10px; color: rgba(11,18,32,.72); font-size: 13.5px; line-height:1.55;}
    .ab .hl{display:flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;}
    .ab .pill{
        font-size: 12px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(16,24,40,.10);
        background: rgba(255,255,255,.82);
        color: rgba(11,18,32,.72);
    }

    .ab details{
        margin-top: 12px;
        border: 1px solid rgba(16,24,40,.10);
        background: rgba(255,255,255,.70);
        border-radius: 16px;
        padding: 12px 12px;
    }
    .ab summary{
        cursor: pointer;
        font-weight: 850;
        color: rgba(11,18,32,.90);
        list-style: none;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap: 10px;
    }
    .ab summary::-webkit-details-marker{display:none;}
    .ab summary .chev{
        width: 30px; height: 30px; border-radius: 12px;
        display:flex; align-items:center; justify-content:center;
        border: 1px solid rgba(16,24,40,.10);
        background: rgba(255,255,255,.85);
        transition: transform .18s ease;
    }
    .ab details[open] summary .chev{transform: rotate(180deg);}
    .ab .bio{margin-top: 10px; display:grid; gap: 10px;}
    .ab .bio p{color: rgba(11,18,32,.74);}

    .ab .vision{
        border: 1px solid rgba(16,24,40,.12);
        background:
            radial-gradient(1000px 520px at 15% 0%, rgba(75,137,255,.12), transparent 55%),
            rgba(255,255,255,.85);
        border-radius: 22px;
        box-shadow: var(--ab-shadow);
        padding: 18px;
    }
    .ab .ctaRow{display:flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;}
    .ab .btn{
        display:inline-flex; align-items:center; gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid rgba(16,24,40,.14);
        background: rgba(255,255,255,.92);
        color: rgba(11,18,32,.92);
        font-weight: 800;
        font-size: 13.5px;
        text-decoration:none;
        cursor:pointer;
        transition: transform .12s ease, background .12s ease;
    }
    .ab .btn:hover{transform: translateY(-1px); background: rgba(255,255,255,1);}
    .ab .btn.primary{
        border-color: rgba(75,137,255,.35);
        box-shadow: 0 0 0 4px rgba(75,137,255,.12);
    }
</style>

<main class="ab" aria-labelledby="about-title">
    <div class="wrap">

        {{-- HERO --}}
        <x-marketplace.page-section id="about-hero" component="about_hero" label="About hero">
        <section class="hero" aria-label="About We Offer Wellness®">
            <div class="heroGrid">
                <div>
                    <div class="kicker">About us</div>
                    <h1 id="about-title">{{ $pageTitle }}</h1>

                    <p>
                        We Offer Wellness® began with a simple idea — born from the frustration of searching for the perfect gift
                        for someone you love. Not another “I saw this in the aisle near the deodorant” gift… a real one. A gift of wellness.
                    </p>

                    <div class="chips" aria-label="The questions we kept asking">
                        <span class="chip">What?</span>
                        <span class="chip">Who?</span>
                        <span class="chip">When?</span>
                        <span class="chip">Where?</span>
                    </div>
                </div>

                <aside class="heroCard" aria-label="Why we built the platform">
                    <div class="cardHd">
                        <strong style="font-size:14px; letter-spacing:-.01em;">Why it matters</strong>
                        <span class="muted">In one place</span>
                    </div>
                    <p style="margin-top:8px;">
                        When the internet search becomes endless and overwhelming, wellness shouldn’t feel harder to find than peace itself.
                        We built one home for trusted holistic therapies — easy to browse, clear to understand, and designed around real needs.
                    </p>

                    <div class="ctaRow">
                        <a class="btn primary" href="{{ url('/help') }}">Visit Help Centre <span aria-hidden="true">→</span></a>
                        <a class="btn" href="{{ url('/safety-and-contraindications') }}">Safety info <span aria-hidden="true">→</span></a>
                    </div>
                </aside>
            </div>
        </section>
        </x-marketplace.page-section>

        {{-- OUR STORY --}}
        <x-marketplace.page-section id="about-story" component="about_story" label="Our story">
        <section class="section" aria-label="Our story">
            <div class="grid two">
                <div class="card">
                    <div class="cardHd">
                        <h2>Our Story</h2>
                        <div class="muted">The spark</div>
                    </div>

                    <div class="timeline">
                        <div class="step">
                            <div class="dot">01</div>
                            <div>
                                <strong>A familiar frustration</strong>
                                <div class="txt">
                                    Ian, our CEO, had the same problem every year when trying to think of original gifting ideas for his family.
                                    When your sock drawer is full to the brim and you have more body sprays than your bathroom cabinet can store,
                                    he wanted to gift something more memorable — a gift of wellness.
                                </div>
                            </div>
                        </div>

                        <div class="step">
                            <div class="dot">02</div>
                            <div>
                                <strong>The endless search</strong>
                                <div class="txt">
                                    This resulted in the problem of what, who, when, where — and the never-ending search on the internet
                                    became tiresome and overwhelming.
                                </div>
                            </div>
                        </div>

                        <div class="step">
                            <div class="dot">03</div>
                            <div>
                                <strong>One simple question</strong>
                                <div class="txt">
                                    What if all of this could be in one place…
                                </div>
                            </div>
                        </div>

                        <div class="step">
                            <div class="dot">04</div>
                            <div>
                                <strong>The seed was sown</strong>
                                <div class="txt">
                                    The seed of We Offer Wellness® was sown — a platform where holistic therapies could be found easily,
                                    with a range of therapies to suit all needs and all located in one place.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" aria-label="What We Offer Wellness® is for">
                    <div class="cardHd">
                        <h2>What we’re building</h2>
                        <div class="muted">Clarity + care</div>
                    </div>

                    <p style="margin-top:10px;">
                        We’re here to make holistic wellness easier to access — whether you’re booking for yourself or buying a gift
                        that actually lands (emotionally *and* practically).
                    </p>

                    <div class="timeline" style="margin-top:12px;">
                        <div class="step">
                            <div class="dot" aria-hidden="true">✓</div>
                            <div>
                                <strong>Clear discovery</strong>
                                <div class="txt">Find therapies that match your needs without drowning in tabs.</div>
                            </div>
                        </div>
                        <div class="step">
                            <div class="dot" aria-hidden="true">✓</div>
                            <div>
                                <strong>Trusted providers</strong>
                                <div class="txt">A platform built around safety, suitability, and professionalism.</div>
                            </div>
                        </div>
                        <div class="step">
                            <div class="dot" aria-hidden="true">✓</div>
                            <div>
                                <strong>Giftable wellness</strong>
                                <div class="txt">Memorable experiences that support real wellbeing.</div>
                            </div>
                        </div>
                    </div>

                    <div class="ctaRow">
                        <a class="btn" href="{{ url('/') }}">Explore offerings <span aria-hidden="true">→</span></a>
                        <a class="btn" href="{{ url('/help') }}">Get support <span aria-hidden="true">→</span></a>
                    </div>
                </div>
            </div>
        </section>
        </x-marketplace.page-section>

        {{-- FOUNDERS --}}
        <x-marketplace.page-section id="about-founders" component="about_founders" label="Founders">
        <section class="section founders" aria-label="The founders">
            <div class="card" style="padding:18px;">
                <div class="cardHd">
                    <h2>The Founders</h2>
                    <div class="muted">The humans behind the mission</div>
                </div>
                <p style="margin-top:10px;">
                    We Offer Wellness® is built by people who live and breathe this work — from holistic practice and community
                    to platform engineering. Different worlds, one goal: make wellbeing more accessible.
                </p>
            </div>

            <div class="foundersBanner" aria-label="We Offer Wellness founders banner">
                <img
                    src="https://studio.weofferwellness.co.uk/storage/uploads/images/d24a0088-7ec5-4019-88b4-86923a69e602.jpg"
                    alt="We Offer Wellness founders banner"
                    loading="lazy"
                    decoding="async"
                >
            </div>

            <div class="grid two" style="margin-top:16px;">
                @foreach($founders as $f)
                    <article class="founder" aria-label="{{ $f['name'] }} — {{ $f['role'] }}">
                        <div class="who">
                            <div class="avatar" aria-hidden="true">{{ strtoupper(mb_substr($f['name'], 0, 1)) }}</div>
                            <div style="flex:1;">
                                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                    <h3>{{ $f['name'] }}</h3>
                                    <span class="role">{{ $f['role'] }}</span>
                                </div>
                                <div class="muted" style="margin-top:2px;">{{ $f['tagline'] }}</div>
                            </div>
                        </div>

                        @if(!empty($f['highlights']))
                            <div class="hl" aria-label="Highlights">
                                @foreach($f['highlights'] as $h)
                                    <span class="pill">{{ $h }}</span>
                                @endforeach
                            </div>
                        @endif

                        <details>
                            <summary>
                                Read full bio
                                <span class="chev" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </summary>

                            <div class="bio">
                                @foreach($f['bio'] as $para)
                                    <p>{{ $para }}</p>
                                @endforeach

                                @if(!empty($f['link']['href']) && !empty($f['link']['label']))
                                    <div class="ctaRow">
                                        <a class="btn primary" href="{{ $f['link']['href'] }}">
                                            {{ $f['link']['label'] }} <span aria-hidden="true">→</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </details>
                    </article>
                @endforeach
            </div>
        </section>
        </x-marketplace.page-section>

        {{-- VISION + MISSION --}}
        <x-marketplace.page-section id="about-vision-mission" component="about_vision_mission" label="Vision & mission">
        <section class="section" aria-label="Vision and mission">
            <div class="vision">
                <div class="grid two">
                    <div class="card" style="box-shadow:none; background:rgba(255,255,255,.78);">
                        <div class="cardHd">
                            <h2>Our Vision</h2>
                            <div class="muted">A better direction</div>
                        </div>

                        <div style="margin-top:10px; display:grid; gap:10px;">
                            <p>
                                We feel the planet needs help, humans need help, and we live in times with such uncertainty, and with so many demands and pressures,
                                that it’s difficult to be unaffected by them.
                            </p>
                            <p>
                                However, we also live in such incredible times where anything is possible and where new and fresh opportunities await.
                                The global wellness community has much to offer and with the help of our global community of amazing We Offer Wellness Practitioners
                                and you, together we can make a difference.
                            </p>
                        </div>
                    </div>

                    <div class="card" style="box-shadow:none; background:rgba(75,137,255,.08); border-color:rgba(75,137,255,.22);">
                        <div class="cardHd">
                            <h2>Our Mission</h2>
                            <div class="muted">Simple, powerful</div>
                        </div>

                        <div style="margin-top:10px;">
                            <p style="font-size: 18px; font-weight: 900; letter-spacing:-.02em; color: rgba(11,18,32,.92);">
                                To Empower Lives, One Holistic Therapy at a Time
                            </p>

                            <p style="margin-top:10px;">
                                If you’re not sure where to start, our Help Centre and Safety guidance can point you in the right direction.
                            </p>

                            <div class="ctaRow">
                                <a class="btn primary" href="{{ url('/help') }}">Help Centre <span aria-hidden="true">→</span></a>
                                <a class="btn" href="{{ url('/safety-and-contraindications') }}">Safety & Contraindications <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        </x-marketplace.page-section>

    </div>
</main>
@endsection
