@php
  $breadcrumbItems = collect($crumbs ?? [])
    ->map(function ($crumb) {
      return [
        'label' => trim((string) data_get($crumb, 'label', '')),
        'url' => trim((string) data_get($crumb, 'url', '')),
      ];
    })
    ->filter(fn (array $crumb) => $crumb['label'] !== '')
    ->values();

  $schemaEnabled = $schemaEnabled ?? true;
  $schemaUrl = trim((string) ($schemaUrl ?? url()->current())) ?: url()->current();
  $schemaId = trim((string) ($schemaId ?? ($schemaUrl . '#breadcrumb')));
  $renderVisual = $renderVisual ?? true;
  $currentIcon = trim((string) ($currentIcon ?? ''));

  $schemaJsonLd = null;
  $schemaList = [];
  if ($schemaEnabled && $breadcrumbItems->count() > 0) {
    foreach ($breadcrumbItems as $index => $crumb) {
      $itemUrl = trim((string) ($crumb['url'] ?? ''));
      if ($itemUrl === '') {
        $itemUrl = ($index === $breadcrumbItems->count() - 1) ? $schemaUrl : url()->current();
      }

      $schemaList[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $crumb['label'],
        'item' => $itemUrl,
      ];
    }

    $schemaJsonLd = [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      '@id' => $schemaId,
      'itemListElement' => $schemaList,
    ];
  }
@endphp

@once
  @push('styles')
    <style>
      .wow-breadcrumbs {
        background: #fff;
        border-bottom: 1px solid #e9eeeb;
      }
      .wow-breadcrumbs__inner {
        display: flex;
        align-items: center;
        min-height: 50px;
      }
      .wow-breadcrumbs__list {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0;
        padding: 0;
        list-style: none;
        font-family: "Instrument Sans", sans-serif;
        font-size: 13px;
        line-height: 1.4;
      }
      .wow-breadcrumbs__item {
        display: inline-flex;
        align-items: center;
        min-width: 0;
      }
      .wow-breadcrumbs__link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #006b57;
        font-weight: 500;
        text-decoration: none;
        transition: color .18s ease;
      }
      .wow-breadcrumbs__link:hover { color: #082f27; }
      .wow-breadcrumbs__icon,
      .wow-breadcrumbs__offering-icon,
      .wow-breadcrumbs__location-icon {
        width: 14px;
        height: 14px;
        flex: 0 0 auto;
      }
      .wow-breadcrumbs__icon { color: #006b57; }
      .wow-breadcrumbs__offering-icon { color: #8b9692; }
      .wow-breadcrumbs__location-icon { color: #006b57; }
      .wow-breadcrumbs__separator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #a4aeaa;
      }
      .wow-breadcrumbs__separator svg { width: 14px; height: 14px; }
      .wow-breadcrumbs__current {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        color: #66736e;
        font-weight: 400;
      }
      .wow-breadcrumbs__current span {
        max-width: 360px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      @media (max-width: 575.98px) {
        .wow-breadcrumbs__inner {
          min-height: 44px;
          overflow-x: auto;
          scrollbar-width: none;
        }
        .wow-breadcrumbs__inner::-webkit-scrollbar { display: none; }
        .wow-breadcrumbs__list {
          flex-wrap: nowrap;
          gap: 6px;
          white-space: nowrap;
          font-size: 12px;
        }
        .wow-breadcrumbs__separator svg { width: 12px; height: 12px; }
        .wow-breadcrumbs__icon,
        .wow-breadcrumbs__offering-icon,
        .wow-breadcrumbs__location-icon { width: 13px; height: 13px; }
        .wow-breadcrumbs__current span { max-width: 220px; }
      }
    </style>
  @endpush
@endonce

@if($schemaEnabled && !empty($schemaList))
  @push('head')
    @once
      <script type="application/ld+json">{!! json_encode($schemaJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
    @endonce
  @endpush
@endif

@if($renderVisual && $breadcrumbItems->count() > 1)
  <nav class="wow-breadcrumbs" aria-label="Breadcrumb">
    <div class="container-page wow-breadcrumbs__inner">
      <ol class="wow-breadcrumbs__list">
        @foreach($breadcrumbItems as $index => $crumb)
          @if($index > 0)
            <li class="wow-breadcrumbs__separator" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </li>
          @endif

          <li class="wow-breadcrumbs__item{{ $index === $breadcrumbItems->count() - 1 ? ' wow-breadcrumbs__current' : '' }}" @if($index === $breadcrumbItems->count() - 1) aria-current="page" @endif>
            @if($index < $breadcrumbItems->count() - 1)
              <a href="{{ $crumb['url'] !== '' ? $crumb['url'] : '#' }}" class="wow-breadcrumbs__link{{ $index === 0 ? ' wow-breadcrumbs__home' : '' }}" @if($index === 0) aria-label="Home" @endif>
                @if($index === 0)
                  <svg class="wow-breadcrumbs__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 10.8 12 3l9 7.8V21a1 1 0 0 1-1 1h-5.5v-7h-5v7H4a1 1 0 0 1-1-1V10.8Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                @endif
                <span>{{ $crumb['label'] }}</span>
              </a>
            @else
              @if($currentIcon === 'location')
                <svg class="wow-breadcrumbs__location-icon" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21s6-5.1 6-11a6 6 0 1 0-12 0c0 5.9 6 11 6 11Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                  <circle cx="12" cy="10" r="2" fill="none" stroke="currentColor" stroke-width="1.7" />
                </svg>
              @elseif($currentIcon === 'offering')
                <svg class="wow-breadcrumbs__offering-icon" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.7" />
                  <path d="M9 8h6M9 12h6M9 16h4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                </svg>
              @endif
              <span>{{ $crumb['label'] }}</span>
            @endif
          </li>
        @endforeach
      </ol>
    </div>
  </nav>
@endif
