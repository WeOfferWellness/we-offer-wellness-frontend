@php
    $item = is_array($product ?? null) ? (object) $product : $product;
    $get = fn (string $key, $default = null) => data_get($product, $key, $default);

    // Derive URL segment from product_type or tags
    $t = strtolower((string) $get('product_type', ''));
    $tags = strtolower((string) $get('tags_list', ''));
    $slug = \Illuminate\Support\Str::slug((string) ($get('title', $get('name', '')) ?: $get('id', '')));
    $url = app(\App\Services\SeoStructureService::class)->canonicalProductUrl($product);

    $image = method_exists($item, 'getFirstImageUrl')
        ? $item->getFirstImageUrl()
        : ($get('image') ?: $get('media.0.media_url'));
    $hasDisplayableImage = method_exists($item, 'hasDisplayableImage')
        ? $item->hasDisplayableImage()
        : !empty($image) && !str_contains((string) $image, 'no-product-image.jpg');
    $title = $get('title', 'Untitled');
    // Title case: first letter of each word uppercase
    $toLower = function($s){ return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s); };
    $ucWords = function($s){ return function_exists('mb_convert_case') ? mb_convert_case($s, MB_CASE_TITLE, 'UTF-8') : ucwords($s); };
    $normalizeTypeLabel = function ($value) use ($toLower, $ucWords) {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $map = [
            'therapies' => 'Therapy',
            'workshops' => 'Workshop',
            'events' => 'Event',
            'classes' => 'Class',
            'retreats' => 'Retreat',
            'experiences' => 'Experience',
        ];

        $key = $toLower($raw);
        if (isset($map[$key])) {
            return $map[$key];
        }

        return $ucWords($toLower(str_replace(['_', '-'], ' ', $raw)));
    };
    $titleFormatted = $ucWords($toLower($title));
    $type = $normalizeTypeLabel($get('product_type', 'Therapy'));
    $category = $normalizeTypeLabel(data_get($product, 'category.name'));
    $priceMin = $get('variants_min_price', $get('price', null));
    $rating = is_numeric($get('reviews_avg_rating', null)) ? round((float) $get('reviews_avg_rating'), 1) : null;
    $reviewCount = (int) ($get('reviews_count', 0));
@endphp

@php
    // Hide zero-priced products globally (temporary request)
    $__priceVal = is_numeric($priceMin) ? (float)$priceMin : null;
    if ($__priceVal === null || $__priceVal <= 0.0) { return; }
@endphp

@once
  @push('head')
    <style>
    /* Small product card — compact height + slimmer layout */
    .wow-card-sm-wrap{ --border:#e5e7eb; --ink:#0b1323; --muted:#64748b; --shadow:0 10px 24px rgba(2,8,23,.06); }
    .wow-card-sm{ display:grid; grid-template-columns: 120px 1fr; gap:12px; align-items:stretch; width:100%; text-decoration: none; color:inherit; background:#fff; border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow: var(--shadow); }
    .wow-card-sm .thumb{ height:100%; min-height:120px; background:#f8fafc; border-right:1px solid var(--border); }
    .wow-card-sm .thumb img{ width:100%; height:100%; object-fit:cover; display:block }
    .wow-card-sm .body{ display:flex; flex-direction:column; gap:6px; padding:10px 12px }
    .wow-card-sm .type{ font-size:.82rem; color:var(--muted) }
    .wow-card-sm .title{ font-size:1rem; line-height:1.25; font-weight:600; color:var(--ink); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
    .wow-card-sm .rating{ font-size:.85rem; color:#111827 }
    .wow-card-sm .bottom{ margin-top:auto; display:flex; align-items:center; justify-content:space-between; gap:8px }
    .wow-card-sm .price{ font-size:1rem; font-weight:600; color:var(--ink) }
    .wow-card-sm .price small{ font-weight:500; color:var(--muted) }
    .wow-card-sm .cta{ color:#0f1e2e; text-decoration: none; font-weight:600 }
    @media (max-width: 575.98px){ .wow-card-sm{ grid-template-columns: 100px 1fr } .wow-card-sm .thumb{ min-height:100px } }
    </style>
  @endpush
@endonce

@if($hasDisplayableImage)
<a href="{{ $url }}" class="wow-card-sm" aria-label="{{ $titleFormatted }}">
  <div class="thumb">
    @if($image)
      <img src="{{ $image }}" alt="{{ $titleFormatted }}">
    @endif
  </div>
  <div class="body">
    <div class="type">{{ $category ?: $type }}</div>
    <div class="title">{{ $titleFormatted }}</div>
    @if($rating && $reviewCount)
      <div class="rating">★ {{ number_format($rating, 1) }} <small class="text-muted">({{ $reviewCount }})</small></div>
    @endif
    <div class="bottom">
      @if($priceMin)
        <div class="price">£{{ number_format((float)$priceMin, 2) }} <small>from</small></div>
      @else
        <div></div>
      @endif
      <span class="cta btn-wow btn-wow--text btn-arrow">View</span>
    </div>
  </div>
</a>
@endif
