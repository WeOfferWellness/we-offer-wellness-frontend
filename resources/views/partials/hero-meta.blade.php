@once
  @push('styles')
    <style>
      .wow-hero-meta { background: #fff; border-bottom: 1px solid #dfe5e2; }
      .wow-hero-meta__inner {
        display: flex;
        align-items: center;
        min-height: 52px;
        overflow-x: auto;
        scrollbar-width: none;
      }
      .wow-hero-meta__inner::-webkit-scrollbar { display: none; }
      .wow-hero-meta__item {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 8px;
        min-height: 52px;
        padding: 0 22px;
        border-right: 1px solid #e9eeeb;
        color: #59645f;
        font-family: "Instrument Sans", sans-serif;
        font-size: 13px;
      }
      .wow-hero-meta__item:first-child { padding-left: 0; }
      .wow-hero-meta__dot { width: 6px; height: 6px; flex: 0 0 6px; border-radius: 50%; background: #006b57; }
      .wow-hero-meta__strong { color: #17201d; font-weight: 600; }
      @media (max-width: 575.98px) {
        .wow-hero-meta__inner { min-height: 46px; }
        .wow-hero-meta__item { min-height: 46px; padding: 0 14px; font-size: 12px; }
      }
    </style>
  @endpush
@endonce

@php
  $heroMetaItems = collect($items ?? [])
    ->map(function ($item) {
      if (is_array($item)) {
        return [
          'label' => trim((string) ($item['label'] ?? $item['text'] ?? '')),
          'strong' => (bool) ($item['strong'] ?? false),
        ];
      }

      return ['label' => trim((string) $item), 'strong' => false];
    })
    ->filter(fn (array $item) => $item['label'] !== '')
    ->values();
@endphp

@if($heroMetaItems->isNotEmpty())
  <div class="wow-hero-meta">
    <div class="container-page wow-hero-meta__inner">
      @foreach($heroMetaItems as $index => $item)
        <div class="wow-hero-meta__item">
          @if($index === 0)<span class="wow-hero-meta__dot"></span>@endif
          <span class="{{ $index === 0 || $item['strong'] ? 'wow-hero-meta__strong' : '' }}">{{ $item['label'] }}</span>
        </div>
      @endforeach
    </div>
  </div>
@endif
