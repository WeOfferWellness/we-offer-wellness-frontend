@php
    $categoryCache = app(\App\Services\WhatCategoryCacheService::class)->load();
    $dynamicCategories = collect(data_get($categoryCache, 'categories', []))
        ->map(function (array $category): ?array {
            $slug = trim((string) ($category['slug'] ?? ''));
            $title = trim((string) ($category['title'] ?? ''));
            $total = (int) data_get($category, 'counts.total', 0);

            if ($slug === '' || $title === '' || $total <= 0) {
                return null;
            }

            $human = \Illuminate\Support\Str::headline(str_replace('-', ' ', $slug));

            return [
                'title' => $human,
                'href' => '/therapies/' . $slug,
                'count' => $total,
                'subtitle' => $total === 1 ? '1 live listing' : $total . ' live listings',
            ];
        })
        ->filter()
        ->take(12)
        ->values();

    $featuredPages = [
        ['href' => '/therapies/reiki', 'title' => 'Reiki', 'subtitle' => 'Trusted Reiki practitioners and distance sessions.'],
        ['href' => '/therapies/sound-healing', 'title' => 'Sound healing', 'subtitle' => 'Sound baths, workshops and live sessions.'],
        ['href' => '/therapies', 'title' => 'Holistic therapy', 'subtitle' => 'Broad wellness search across trusted therapies.'],
        ['href' => '/classes', 'title' => 'Wellness classes', 'subtitle' => 'Yoga, meditation, breathwork and group sessions.'],
        ['href' => '/therapies', 'title' => 'Holistic therapies UK', 'subtitle' => 'UK-wide hub for online and in-person listings.'],
    ];
@endphp

<section class="wow-popular-searches">
    <div class="container-page">
        <div class="wow-popular-searches__inner">
            <div class="wow-popular-searches__copy">
                <div class="wow-kicker">Popular searches</div>
                <h2>Fast entry points to the most useful landing pages</h2>
                <p>These are the direct paths people use when they want to browse by therapy, location or high-intent search. The modality links below are pulled from live inventory, so they stay aligned with what is actually available.</p>
            </div>

            <div class="wow-popular-searches__stack">
                <div class="wow-popular-searches__grid wow-popular-searches__grid--featured">
                    @foreach ($featuredPages as $page)
                        <a class="wow-popular-searches__card" href="{{ $page['href'] }}">
                            <strong>{{ $page['title'] }}</strong>
                            <span>{{ $page['subtitle'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="wow-popular-searches__subhead">
                    <h3>All live modality pages</h3>
                    <a href="/therapies">Browse therapies</a>
                </div>

                <div class="wow-popular-searches__grid wow-popular-searches__grid--categories">
                    @forelse ($dynamicCategories as $category)
                        <a class="wow-popular-searches__card wow-popular-searches__card--category" href="{{ $category['href'] }}">
                            <strong>{{ $category['title'] }}</strong>
                            <span>{{ $category['subtitle'] }}</span>
                        </a>
                    @empty
                        <div class="wow-popular-searches__empty">
                            Modality pages will appear here once live inventory is available.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .wow-popular-searches{
        display:none !important;
        margin: 0 0 64px;
    }
    .wow-popular-searches__inner{
        display:grid;
        grid-template-columns:minmax(0,.88fr) minmax(0,1.12fr);
        gap:20px;
        padding:24px;
        border:1px solid var(--wow-line);
        border-radius:18px;
        background:linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.98));
        box-shadow:0 18px 54px rgba(16,24,40,.06);
    }
    .wow-popular-searches__copy h2{
        margin:0;
        color:var(--wow-ink);
        font-family:var(--wow-serif);
        font-size:clamp(30px,3.6vw,46px);
        line-height:.98;
        letter-spacing:-.055em;
    }
    .wow-popular-searches__copy p{
        margin:14px 0 0;
        color:var(--wow-muted);
        font-size:15px;
        line-height:1.55;
        max-width:58ch;
    }
    .wow-popular-searches__grid{
        display:grid;
        gap:12px;
    }
    .wow-popular-searches__grid--featured{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .wow-popular-searches__grid--categories{
        grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    }
    .wow-popular-searches__card{
        display:block;
        padding:16px;
        border-radius:16px;
        border:1px solid var(--wow-soft-line);
        background:#fff;
        text-decoration:none;
        box-shadow:0 10px 26px rgba(16,24,40,.04);
    }
    .wow-popular-searches__card strong{
        display:block;
        color:var(--wow-ink);
        font-size:15px;
        line-height:1.35;
    }
    .wow-popular-searches__card span{
        display:block;
        margin-top:4px;
        color:var(--wow-muted);
        font-size:13px;
        line-height:1.45;
    }
    .wow-popular-searches__card--category strong{
        font-size:14px;
    }
    .wow-popular-searches__stack{
        display:grid;
        gap:16px;
    }
    .wow-popular-searches__subhead{
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:center;
    }
    .wow-popular-searches__subhead h3{
        margin:0;
        color:var(--wow-ink);
        font-size:15px;
        font-weight:700;
        letter-spacing:-.01em;
    }
    .wow-popular-searches__subhead a{
        color:var(--wow-green-dark);
        font-size:13px;
        font-weight:700;
        text-decoration:none;
    }
    .wow-popular-searches__empty{
        padding:16px;
        border:1px dashed var(--wow-soft-line);
        border-radius:16px;
        color:var(--wow-muted);
        background:#fff;
        font-size:14px;
    }
    @media (max-width: 992px){
        .wow-popular-searches__inner,
        .wow-popular-searches__grid{
            grid-template-columns:1fr;
        }
        .wow-popular-searches__subhead{
            flex-direction:column;
            align-items:flex-start;
        }
    }
</style>
