@once
  @push('styles')
    <style>
      .wow-modality-board{margin:0 0 32px;padding:8px 0 18px}
      .wow-modality-grid{display:grid;grid-template-columns:repeat(10,minmax(0,1fr));grid-auto-rows:minmax(170px,1fr);gap:14px}
      .wow-modality-card{position:relative;min-height:0;overflow:hidden;border:1px solid #dfe4ea;border-radius:18px;background:rgba(255,255,255,.98);box-shadow:0 14px 42px rgba(16,24,40,.055);text-decoration:none;transition:transform 160ms ease,border-color 160ms ease,box-shadow 160ms ease}
      .wow-modality-card:not(.wow-modality-card--lead){grid-column:span 3;order:2}
      .wow-modality-card--lead{grid-column:4/span 4;grid-row:1/span 2;order:1;border-color:rgba(47,111,96,.42);box-shadow:0 16px 42px rgba(47,111,96,.1)}
      .wow-modality-card--side-left{grid-column:1/span 3}.wow-modality-card--side-right{grid-column:8/span 3}
      .wow-modality-card--side-left.wow-modality-card--top,.wow-modality-card--side-right.wow-modality-card--top{grid-row:1}
      .wow-modality-card--side-left.wow-modality-card--bottom,.wow-modality-card--side-right.wow-modality-card--bottom{grid-row:2}
      .wow-modality-card:hover{transform:translateY(-4px);border-color:rgba(79,147,129,.42);box-shadow:0 18px 44px rgba(16,24,40,.1)}
      .wow-modality-card__image{position:absolute;inset:0}.wow-modality-card__image::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(13,34,31,.04) 22%,rgba(13,34,31,.84) 100%)}
      .wow-modality-card__image img{display:block;width:100%;height:100%;object-fit:cover}
      .wow-modality-card__body{position:absolute;right:0;bottom:0;left:0;z-index:1;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-end;padding:18px;color:#fff;background:linear-gradient(180deg,transparent 20%,rgba(13,34,31,.58) 100%)}
      .wow-modality-card h3,.wow-modality-card p,.wow-modality-card .wow-card-link{color:#fff}.wow-modality-card h3{margin:0;font-family:var(--wow-serif,"Playfair Display",Georgia,serif);font-size:clamp(22px,2vw,30px);font-weight:500;line-height:.98;letter-spacing:-.055em}.wow-modality-card p{margin:10px 0 0;font-size:13px;line-height:1.45}.wow-card-link{display:inline-flex;margin-top:auto;padding-top:16px;color:#fff;font-size:13px;font-weight:800}
      .wow-modality-card__badge{position:absolute;top:12px;left:12px;padding:6px 9px;border-radius:999px;background:#2f6f60;color:#fff;font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
      @media(max-width:980px){.wow-modality-grid{grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:auto;gap:16px}.wow-modality-card,.wow-modality-card--lead{grid-column:auto!important;grid-row:auto!important;order:initial!important;min-height:350px}}
      @media(max-width:700px){.wow-modality-grid{grid-template-columns:1fr}.wow-modality-card{min-height:260px}}
    </style>
  @endpush
@endonce

@php
  $modalityItems = collect($items ?? [])->filter(fn ($item) => is_array($item))->take(5)->values();
@endphp

@if($modalityItems->isNotEmpty())
  <div class="wow-modality-board">
    <div class="wow-modality-grid">
      @foreach($modalityItems as $index => $item)
        @php
          $cardClass = match ($index) {
            0 => 'wow-modality-card wow-modality-card--lead',
            1 => 'wow-modality-card wow-modality-card--side-left wow-modality-card--top',
            2 => 'wow-modality-card wow-modality-card--side-left wow-modality-card--bottom',
            3 => 'wow-modality-card wow-modality-card--side-right wow-modality-card--top',
            default => 'wow-modality-card wow-modality-card--side-right wow-modality-card--bottom',
          };
          $name = (string) ($item['name'] ?? 'Wellness modality');
          $image = (string) ($item['image_url'] ?? '');
        @endphp
        <a href="{{ $item['url'] ?? url('/'.($item['slug'] ?? 'therapies')) }}" class="{{ $cardClass }}" data-loader-init="1">
          <div class="wow-modality-card__image">
            @if($image)<img src="{{ $image }}" alt="{{ $name }}">@endif
          </div>
          @if($index === 0)<span class="wow-modality-card__badge">A place to start</span>@endif
          <div class="wow-modality-card__body">
            <h3>{{ $name }}</h3>
            <p>{{ $item['description'] ?? 'Explore supportive wellness experiences for your next step.' }}</p>
            <span class="wow-card-link btn-wow btn-wow--text btn-arrow">Browse modality</span>
          </div>
        </a>
      @endforeach
    </div>
  </div>
@endif
