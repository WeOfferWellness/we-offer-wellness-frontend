<section class="section" aria-labelledby="browse-outcome-title">
    <div class="container">
        <div class="kicker">Reset routes</div>
        <h2 id="browse-outcome-title">Browse by outcome</h2>
        <div class="row g-3 mt-1">
            @php
                $outcomes = [
                  ['title' => 'Stress Relief', 'href' => '/therapies/stress-relief'],
                  ['title' => 'Sleep Reset', 'href' => '/therapies/sleep'],
                  ['title' => 'Energy Boost', 'href' => '/therapies/energy'],
                  ['title' => 'Emotional Balance', 'href' => '/therapies/emotional-balance'],
                  ['title' => 'Deep Recovery', 'href' => '/therapies/recovery'],
                  ['title' => 'Couples Reset', 'href' => '/therapies/couples'],
                  ['title' => 'Corporate Burnout', 'href' => '/events/burnout-recovery'],
                ];
            @endphp
            @foreach ($outcomes as $o)
                <div class="col-12 col-sm-6 col-lg-3">
                    <a class="card p-3 h-100 d-block" href="{{ $o['href'] }}" data-analytics="home_category_click" data-category="{{ $o['title'] }}">
                        <div class="fw-semibold">{{ $o['title'] }}</div>
                        <div class="text-muted mt-1">Online • In‑person</div>
                        <div class="small mt-2 text-muted">From £ —</div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
