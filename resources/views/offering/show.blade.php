@extends('layouts.app')

@section('content')

@php
  $p = $product ?? [];
  $title = $p['title'] ?? 'Therapy';
  $type = $p['type'] ?? 'therapy';
  $rating = $p['rating'] ?? null;
  $reviewCount = $p['review_count'] ?? 0;
  $priceMin = $p['price_min'] ?? ($p['price'] ?? null);
  $summary = trim((string)($p['summary'] ?? ''));
  $seoTitle = trim((string)($p['seo_title'] ?? ''));
  $seoDescriptionOverride = trim((string)($p['seo_description'] ?? ''));
  $body = trim((string)($p['body_html'] ?? ''));
  $what = trim((string)($p['what_to_expect'] ?? ''));
  $included = trim((string)($p['included'] ?? ''));
  $safety = trim((string)($p['safety_notes'] ?? ''));
  $contra = trim((string)($p['contraindications'] ?? ''));
  $imageValue = (string) ($p['image'] ?? '');
  $images = $p['images'] ?? ($imageValue !== '' ? [ $imageValue ] : []);
  $pageTitle = $seoTitle !== ''
    ? $seoTitle
    : trim((string) ($title !== '' ? $title . ' | We Offer Wellness®' : 'We Offer Wellness®'));
  $metaSource = trim((string) ($summary ?: $what ?: $included ?: $body ?: ''));
  $metaDescription = $seoDescriptionOverride !== ''
    ? $seoDescriptionOverride
    : ($metaSource !== ''
      ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($metaSource)) ?? $metaSource), 160, '…')
      : ('Book ' . $title . ' with trusted practitioners at We Offer Wellness®.'));
  $ogImage = '';
  $firstImage = trim((string) ($images[0] ?? ''));
  if ($firstImage !== '') {
    $ogImage = preg_match('#^https?://#i', $firstImage) ? $firstImage : url($firstImage);
  }
  if ($ogImage === '') {
    $ogImage = asset('images/default-social-preview.jpg');
  }
  $variants = $p['variants'] ?? [];
  $options = $p['options'] ?? [];
  $mode = trim((string)($p['mode'] ?? ''));
  $locations = is_array($p['locations'] ?? null) ? array_values(array_filter($p['locations'])) : [];
  $availabilityPattern = '';
  $availabilityLeadTime = '';
  $availabilityDuration = '';
  $durationText = '';
  $bookingVariantLabel = trim((string)($p['booking_variant_label'] ?? ''));
  $variantDurationMins = 0;
  $eventType = strtolower(trim((string) ($p['type'] ?? $type ?? '')));
  $isPublicEventType = in_array($eventType, ['event', 'events', 'workshop', 'workshops', 'retreat', 'retreats'], true)
    || str_contains($eventType, 'event')
    || str_contains($eventType, 'workshop')
    || str_contains($eventType, 'retreat');
  $eventStartDate = trim((string) ($p['start_date'] ?? ''));
  $eventStartTime = trim((string) ($p['start_time'] ?? ''));
  $eventEndDate = trim((string) ($p['end_date'] ?? ''));
  $eventEndTime = trim((string) ($p['end_time'] ?? ''));
  $hasValidEventStart = false;
  if ($eventStartDate !== '') {
    try {
      \Carbon\Carbon::parse($eventStartDate . ($eventStartTime !== '' ? ' ' . $eventStartTime : ''));
      $hasValidEventStart = true;
    } catch (Throwable $e) {
      $hasValidEventStart = false;
    }
  }
  $isEventOffering = $isPublicEventType && $hasValidEventStart;
  $isV3Offering = strtolower(trim((string) ($p['source_version'] ?? ''))) === 'v3';
  $isGiftCardOffering = str_contains(strtolower((string) ($type ?? '')), 'gift')
    || str_contains(strtolower($title), 'gift card')
    || str_contains(strtolower((string) data_get($p, 'category.name', '')), 'gift');
  $isPastEvent = (bool) data_get($p, 'is_past_event', \App\Support\EventListing::isPast($p));
  $isServiceSchema = $isV3Offering && ! $isEventOffering && ! $isGiftCardOffering;
  $showBookingUi = $isEventOffering && ! $isPastEvent;
  $showPaymentModule = ! ($isEventOffering && $isPastEvent);
  $eventDateSummary = '';
  if ($isEventOffering && $eventStartDate !== '') {
    try {
      $start = \Carbon\Carbon::parse($eventStartDate . ($eventStartTime !== '' ? ' ' . $eventStartTime : ''));
      $end = $eventEndDate !== '' ? \Carbon\Carbon::parse($eventEndDate . ($eventEndTime !== '' ? ' ' . $eventEndTime : '')) : null;
      $startLabel = $start->format('D j M' . ($eventStartTime !== '' ? ', g:i A' : ''));
      if ($end && $end->toDateString() !== $start->toDateString()) {
        $endLabel = $end->format('D j M' . ($eventEndTime !== '' ? ', g:i A' : ''));
        $eventDateSummary = $startLabel . ' – ' . $endLabel;
      } elseif ($end && $eventEndTime !== '' && $eventEndTime !== $eventStartTime) {
        $eventDateSummary = $startLabel . ' – ' . $end->format('g:i A');
      } else {
        $eventDateSummary = $startLabel;
      }
    } catch (\Throwable $e) {
      $eventDateSummary = trim(implode(' ', array_filter([$eventStartDate, $eventStartTime])));
    }
  }
  if ($bookingVariantLabel !== '') {
    if (preg_match('/(\d+(?:\.\d+)?)\s*(hour|hr|hrs|hours)\b/i', $bookingVariantLabel, $m)) {
      $variantDurationMins = (int) round(((float) $m[1]) * 60);
    } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(minute|min|mins|minutes)\b/i', $bookingVariantLabel, $m)) {
      $variantDurationMins = (int) round((float) $m[1]);
    }
  }
  $durationCandidates = [];
  if ($variantDurationMins <= 0) {
    foreach (($variants ?? []) as $variant) {
      $texts = [];
      if (is_array($variant)) {
        $texts[] = (string) ($variant['title'] ?? '');
        $metadata = is_array($variant['metadata'] ?? null) ? $variant['metadata'] : [];
        foreach (['duration', 'duration_minutes', 'duration_mins'] as $key) {
          $texts[] = (string) ($metadata[$key] ?? '');
        }
      }
      foreach ($texts as $text) {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(hour|hr|hrs|hours)\b/i', $text, $m)) {
          $durationCandidates[] = (int) round(((float) $m[1]) * 60);
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(minute|min|mins|minutes)\b/i', $text, $m)) {
          $durationCandidates[] = (int) round((float) $m[1]);
        }
      }
    }
    foreach (($options ?? []) as $opt) {
      $name = strtolower((string) ($opt['name'] ?? $opt['meta_name'] ?? ''));
      if ($name === '' || ! str_contains($name, 'session')) {
        continue;
      }
      foreach (($opt['values'] ?? []) as $value) {
        $text = is_array($value) ? (string) ($value['value'] ?? '') : (string) $value;
        if (preg_match('/(\d+(?:\.\d+)?)\s*(hour|hr|hrs|hours)\b/i', $text, $m)) {
          $durationCandidates[] = (int) round(((float) $m[1]) * 60);
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(minute|min|mins|minutes)\b/i', $text, $m)) {
          $durationCandidates[] = (int) round((float) $m[1]);
        }
      }
    }
  }
  $durationCandidates = array_values(array_filter($durationCandidates, fn ($n) => is_numeric($n) && (int) $n >= 15));
  $durationRangeText = '';
  if ($variantDurationMins > 0) {
    $durationRangeText = $variantDurationMins . ' min';
  } elseif ($durationCandidates) {
    $minDuration = min($durationCandidates);
    $maxDuration = max($durationCandidates);
    $durationRangeText = $minDuration === $maxDuration
      ? ($minDuration . ' min')
      : ($minDuration . ' - ' . $maxDuration . ' min');
  }
  $bookingMeta = $p['booking'] ?? [];
  $slotsByDay = is_array($bookingMeta['slotsByDay'] ?? null) ? $bookingMeta['slotsByDay'] : [];
  $hasFutureBookableSlot = false;
  foreach ($slotsByDay as $slots) {
    if (is_array($slots['slots'] ?? null) && $slots['slots'] !== []) {
      $hasFutureBookableSlot = true;
      break;
    }
  }
  $hasFutureBookableSlot = $hasFutureBookableSlot || (($p['availability_state'] ?? '') === 'available');
  $availabilityConfigured = $hasFutureBookableSlot
    || (($p['availability_state'] ?? '') === 'unavailable')
    || ! empty($bookingMeta['weeklyWindows']);
  $availableDays = [];
  foreach ($slotsByDay as $dayKey => $slots) {
    if (!is_array($slots) || empty($slots)) {
      continue;
    }
    try {
      $dt = \Carbon\Carbon::parse((string) $dayKey);
      $availableDays[(int) $dt->dayOfWeekIso] = true;
    } catch (\Throwable $e) {
      continue;
    }
  }
  if (!empty($availableDays)) {
    ksort($availableDays);
    $map = [
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
      7 => 'Sunday',
    ];
    $names = array_values(array_map(fn($i) => $map[$i] ?? null, array_keys($availableDays)));
    $names = array_values(array_filter($names));
    if (count($names) === 7) {
      $availabilityPattern = 'Published availability is shown in the booking calendar.';
    } elseif (count($names) === 1) {
      $availabilityPattern = 'Published availability is shown in the booking calendar.';
    } elseif (count($names) === 2) {
      $availabilityPattern = 'Published availability is shown in the booking calendar.';
    } else {
      $last = array_pop($names);
      $availabilityPattern = 'Published availability is shown in the booking calendar.';
    }
  }
  $availabilityLeadTime = trim((string) ($p['booking']['lead_time_text'] ?? ''));
  $availabilityDuration = $p['booking']['duration_text'] ?? '';
  if ($variantDurationMins > 0) {
    $durationText = $variantDurationMins . ' min';
    $availabilityDuration = 'Please allow up to ' . $variantDurationMins . ' minutes for the full therapy (plus a few minutes to settle in).';
  } elseif ($durationCandidates) {
    $minDuration = min($durationCandidates);
    $maxDuration = max($durationCandidates);
    $durationText = $minDuration === $maxDuration
      ? ($minDuration . ' min')
      : ($minDuration . ' - ' . $maxDuration . ' min');
    $availabilityDuration = 'Please allow up to ' . $maxDuration . ' minutes for the full therapy (plus a few minutes to settle in).';
  } elseif ($availabilityDuration === '') {
    $durationMins = $p['duration_minutes'] ?? $p['duration'] ?? null;
    if (is_numeric($durationMins) && (int) $durationMins > 0) {
      $durationText = (int) $durationMins . ' min';
      $availabilityDuration = 'Please allow up to ' . (int) $durationMins . ' minutes for the full therapy (plus a few minutes to settle in).';
    }
  }
  $reviewPreviewWords = 55;
  // Extract Location(s) list from options
  $locationValues = [];
  foreach (($options ?? []) as $opt) {
    $name = isset($opt['name']) ? strtolower($opt['name']) : '';
    if (str_contains($name, 'location')) {
      foreach (($opt['values'] ?? []) as $v) {
        $val = is_array($v) ? ($v['value'] ?? '') : (string) $v;
        $val = trim($val);
        if ($val !== '') { $locationValues[] = $val; }
      }
    }
  }
  // Make unique while preserving order
  $seen = [];
  $locationsList = [];
  foreach ($locationValues as $lv) {
    $k = strtolower($lv);
    if (!isset($seen[$k])) { $seen[$k] = true; $locationsList[] = $lv; }
  }
  $participantRange = null;
  foreach (($options ?? []) as $opt) {
    $name = strtolower((string)($opt['name'] ?? $opt['meta_name'] ?? ''));
    if ($name === '' || !str_contains($name, 'person')) {
      continue;
    }
    $vals = $opt['values'] ?? [];
    if (!is_array($vals) || empty($vals)) {
      break;
    }
    $numbers = [];
    $allNumeric = true;
    foreach ($vals as $val) {
      $raw = is_array($val) ? ($val['value'] ?? '') : (string) $val;
      $raw = trim($raw);
      if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
        $allNumeric = false;
        break;
      }
      $numbers[] = (int) $raw;
    }
    if ($allNumeric && !empty($numbers)) {
      sort($numbers);
      $min = $numbers[0];
      $max = $numbers[count($numbers) - 1];
      $participantRange = [
        'label' => $min === $max ? 'For ' . $min : ('For ' . $min . '–' . $max),
        'suffix' => $max === 1 ? 'participant' : 'participants',
      ];
    }
    break;
  }
@endphp

@php
  $schemaUrl = trim((string) ($p['url'] ?? url()->current()));
  $schemaImageSource = $images instanceof \Illuminate\Support\Collection
    ? $images->all()
    : (is_array($images) ? $images : []);
  $schemaImages = array_values(array_unique(array_filter(array_map(function ($img) {
    $img = trim((string) $img);
    if ($img === '') {
      return null;
    }
    if (preg_match('#^https?://#i', $img)) {
      return $img;
    }
    return url($img);
  }, $schemaImageSource))));
  $schemaOffers = null;
  $schemaPrice = $p['price_min'] ?? ($p['price'] ?? null);
  $schemaPriceCurrency = (string) ($p['currency'] ?? 'GBP');
  $schemaMoneyValue = static function (mixed $value): ?float {
    if (! is_numeric($value)) {
      return null;
    }

    $amount = (float) $value;
    return round($amount, 2);
  };
  $schemaProviderName = trim((string) (
    data_get($p, 'vendor_name')
    ?: data_get($p, 'vendor.vendor_name')
    ?: data_get($p, 'vendor.user.name')
    ?: data_get($p, 'practitioner.name')
    ?: data_get($p, 'practitioner_name')
    ?: ''
  ));
  $schemaProviderType = data_get($p, 'vendor_name') || data_get($p, 'vendor.vendor_name')
    ? 'Organization'
    : 'Person';
  $schemaProviderUrl = trim((string) (
    data_get($p, 'vendor.profile_url')
    ?: data_get($p, 'vendor.user.practitioner_profile_url')
    ?: data_get($p, 'vendor.user.profile_url')
    ?: data_get($p, 'practitioner.profile_url')
    ?: data_get($p, 'practitioner_profile_url')
    ?: ''
  ));
  $schemaProviderId = $schemaProviderUrl !== ''
    ? rtrim($schemaProviderUrl, '/') . '#' . ($schemaProviderType === 'Person' ? 'person' : 'organization')
    : null;
  $schemaServiceHasFutureAvailability = ! $isEventOffering && ! $isGiftCardOffering && $hasFutureBookableSlot;
  $schemaBuildOffers = static function (array $variants, ?float $fallbackPrice, string $fallbackName, bool $includeValidFrom = false) use ($schemaPriceCurrency, $schemaUrl, $schemaMoneyValue, $schemaServiceHasFutureAvailability): array {
    $offers = [];

    foreach ($variants as $variant) {
      if (! is_array($variant)) {
        continue;
      }

      $variantId = trim((string) ($variant['id'] ?? ''));
      $variantSelection = array_values(array_filter(array_map('trim', (array) ($variant['selection'] ?? $variant['options'] ?? []))));
      $variantLabel = trim((string) ($variant['label'] ?? implode(' • ', $variantSelection)));
      if ($variantLabel === '') {
        $variantLabel = $variantId !== '' ? $variantId : $fallbackName;
      }

      $variantPrice = $schemaMoneyValue($variant['price'] ?? null);
      if ($variantPrice === null || $variantPrice <= 0) {
        continue;
      }

      $variantUrl = $schemaUrl;
      if ($variantId !== '') {
        $variantUrl .= (str_contains($schemaUrl, '?') ? '&' : '?') . 'variant=' . rawurlencode($variantId);
      }

      $offer = [
        '@type' => 'Offer',
        '@id' => $schemaUrl . '#offer-' . ($variantId !== '' ? \Illuminate\Support\Str::slug($variantId) : \Illuminate\Support\Str::slug($variantLabel)),
        'name' => $variantLabel,
        'url' => $variantUrl,
        'price' => $variantPrice,
        'priceCurrency' => $schemaPriceCurrency,
        'seller' => [
          '@id' => url('/') . '#organization',
        ],
      ];

      if ($schemaServiceHasFutureAvailability && (! array_key_exists('available', $variant) || (bool) $variant['available'])) {
        $offer['availability'] = 'https://schema.org/InStock';
      } elseif (array_key_exists('available', $variant) && ! (bool) $variant['available']) {
        $offer['availability'] = 'https://schema.org/OutOfStock';
      }

      $offers[] = array_filter($offer, static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    if ($offers === [] && $fallbackPrice !== null && $fallbackPrice > 0) {
      $offer = [
        '@type' => 'Offer',
        '@id' => $schemaUrl . '#offer-default',
        'name' => $fallbackName,
        'url' => $schemaUrl,
        'price' => $fallbackPrice,
        'priceCurrency' => $schemaPriceCurrency,
        'seller' => [
          '@id' => url('/') . '#organization',
        ],
      ];

      if ($schemaServiceHasFutureAvailability) {
        $offer['availability'] = 'https://schema.org/InStock';
      }

      $offers[] = array_filter($offer, static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    return $offers;
  };
  $schemaFallbackPrice = $schemaMoneyValue($schemaPrice);
  $schemaOffers = $schemaBuildOffers(
    is_array($variants) ? $variants : [],
    $schemaFallbackPrice,
    $title . ($isEventOffering ? ' ticket' : ' session'),
    $isEventOffering
  );
  $schemaOfferPrices = [];
  foreach (is_array($schemaOffers) ? $schemaOffers : [] as $schemaOffer) {
    $schemaOfferPrice = data_get($schemaOffer, 'price');
    if (is_numeric($schemaOfferPrice)) {
      $schemaOfferPrices[] = (float) $schemaOfferPrice;
    }
  }

  $schemaPriceRange = null;
  if ($schemaOfferPrices !== []) {
    $schemaMinPrice = min($schemaOfferPrices);
    $schemaMaxPrice = max($schemaOfferPrices);
    $schemaCurrencySymbol = $schemaPriceCurrency === 'GBP'
      ? '£'
      : (($schemaPriceCurrency !== '') ? ($schemaPriceCurrency . ' ') : '£');
    $schemaPriceRange = $schemaMinPrice === $schemaMaxPrice
      ? $schemaCurrencySymbol . number_format((float) $schemaMinPrice, 2)
      : $schemaCurrencySymbol . number_format((float) $schemaMinPrice, 2) . ' - ' . $schemaCurrencySymbol . number_format((float) $schemaMaxPrice, 2);
  }

  $schemaTelephone = trim((string) (
    data_get($p, 'practitioner.telephone')
    ?: data_get($p, 'practitioner.phone')
    ?: data_get($p, 'vendor.telephone')
    ?: data_get($p, 'vendor.phone')
    ?: data_get($p, 'practitioner.phone')
    ?: ''
  ));
  $schemaBusinessAddress = data_get($p, 'practitioner.address');
  if (! is_array($schemaBusinessAddress) || $schemaBusinessAddress === []) {
    $schemaVendorLocations = is_array(data_get($p, 'practitioner.locations', [])) ? data_get($p, 'practitioner.locations', []) : [];
    if ($schemaVendorLocations === []) {
      $schemaVendorLocations = is_array(data_get($p, 'vendor.locations', [])) ? data_get($p, 'vendor.locations', []) : [];
    }
    foreach ($schemaVendorLocations as $schemaVendorLocation) {
      $schemaLocationAddress = data_get($schemaVendorLocation, 'address');
      if (is_array($schemaLocationAddress) && $schemaLocationAddress !== []) {
        $schemaBusinessAddress = $schemaLocationAddress;
        break;
      }
    }
  }

  $schemaWeeklyWindows = is_array(data_get($bookingMeta, 'weeklyWindows', [])) ? data_get($bookingMeta, 'weeklyWindows', []) : [];
  $schemaHoursAvailable = [];
  if ($schemaWeeklyWindows !== []) {
    $schemaDayNames = [
      'mon' => 'Monday',
      'tue' => 'Tuesday',
      'wed' => 'Wednesday',
      'thu' => 'Thursday',
      'fri' => 'Friday',
      'sat' => 'Saturday',
      'sun' => 'Sunday',
    ];
    $schemaHourGroups = [];

    foreach ($schemaWeeklyWindows as $dayKey => $dayConfig) {
      if (! is_array($dayConfig) || ! (bool) data_get($dayConfig, 'enabled', false)) {
        continue;
      }

      $dayName = $schemaDayNames[$dayKey] ?? \Illuminate\Support\Str::headline((string) $dayKey);
      foreach ((array) data_get($dayConfig, 'windows', []) as $window) {
        if (! is_array($window)) {
          continue;
        }

        $opens = trim((string) ($window['start'] ?? ''));
        $closes = trim((string) ($window['end'] ?? ''));
        if ($opens === '' || $closes === '') {
          continue;
        }

        $groupKey = $opens . '|' . $closes;
        if (! isset($schemaHourGroups[$groupKey])) {
          $schemaHourGroups[$groupKey] = [
            'dayOfWeek' => [],
            'opens' => $opens,
            'closes' => $closes,
          ];
        }

        $schemaHourGroups[$groupKey]['dayOfWeek'][] = $dayName;
      }
    }

    $schemaHoursAvailable = array_values(array_map(
      static function (array $group): array {
        return array_filter([
          '@type' => 'OpeningHoursSpecification',
          'dayOfWeek' => array_values(array_unique($group['dayOfWeek'] ?? [])),
          'opens' => $group['opens'] ?? null,
          'closes' => $group['closes'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
      },
      $schemaHourGroups
    ));
  }
  $schemaReviewSource = array_merge(
    is_array($p['client_reviews'] ?? null) ? $p['client_reviews'] : [],
    is_array($p['reviews'] ?? null) ? $p['reviews'] : []
  );
  $schemaReviews = [];
  $schemaRatingValues = [];
  $schemaReviewKeys = [];
  foreach ($schemaReviewSource as $review) {
    $reviewText = trim((string) ($review['body'] ?? $review['review'] ?? $review['review_text'] ?? ''));
    if ($reviewText === '') {
      continue;
    }

    $reviewRatingRaw = $review['rating'] ?? $review['ratingValue'] ?? null;
    $reviewRating = is_numeric($reviewRatingRaw) && (float) $reviewRatingRaw >= 1 && (float) $reviewRatingRaw <= 5
      ? (float) $reviewRatingRaw
      : null;

    $authorName = trim((string) ($review['author'] ?? data_get($review, 'user.name', 'Verified customer')));
    $datePublished = trim((string) ($review['date'] ?? $review['created_at'] ?? ''));
    $schemaReviewKey = strtolower($authorName . '|' . ($reviewRating ?? '') . '|' . $reviewText);
    if (isset($schemaReviewKeys[$schemaReviewKey])) {
      continue;
    }
    $schemaReviewKeys[$schemaReviewKey] = true;
    $schemaReview = [
      '@type' => 'Review',
      'reviewBody' => $reviewText,
      'author' => [
        '@type' => 'Person',
        'name' => $authorName !== '' ? $authorName : 'Verified customer',
      ],
    ];
    if ($reviewRating !== null) {
      $schemaReview['reviewRating'] = [
        '@type' => 'Rating',
        'ratingValue' => $reviewRating,
        'bestRating' => 5,
        'worstRating' => 1,
      ];
    }
    if ($datePublished !== '') {
      $schemaReview['datePublished'] = $datePublished;
    }
    $schemaReviews[] = $schemaReview;
    $schemaRatingValues[] = $reviewRating;
  }
  $schemaAggregate = null;
  $schemaReviewCount = max((int) ($p['review_count'] ?? 0), count($schemaReviews));
  $schemaVendorReviewSummary = data_get($p, 'vendor.review_summary');
  if (! is_array($schemaVendorReviewSummary)) {
    $schemaVendorReviewSummary = [];
  }
  $schemaVendorReviewCount = (int) ($schemaVendorReviewSummary['count'] ?? 0);
  $schemaVendorReviewRating = isset($schemaVendorReviewSummary['rating']) ? round((float) $schemaVendorReviewSummary['rating'], 1) : null;
  $schemaReviewCount = max($schemaReviewCount, $schemaVendorReviewCount);
  $schemaRatingFallback = is_numeric($p['rating'] ?? null) && (float) $p['rating'] > 0
    ? (float) $p['rating']
    : ($schemaVendorReviewRating !== null && $schemaVendorReviewRating > 0 ? $schemaVendorReviewRating : null);
  if ($schemaReviewCount > 0 && ($schemaRatingValues !== [] || $schemaRatingFallback !== null)) {
    $ratingValue = $schemaRatingValues !== []
      ? (array_sum($schemaRatingValues) / max(1, count($schemaRatingValues)))
      : $schemaRatingFallback;
    $schemaAggregate = [
      '@type' => 'AggregateRating',
      'ratingValue' => number_format((float) $ratingValue, 1, '.', ''),
      'reviewCount' => $schemaReviewCount,
      'bestRating' => 5,
      'worstRating' => 1,
    ];
  }
  $schemaProduct = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    '@id' => $schemaUrl . '#product',
    'name' => $title,
    'description' => trim(strip_tags($summary ?: $body ?: $what ?: '')),
    'image' => $schemaImages ?: null,
    'url' => $schemaUrl,
    'mainEntityOfPage' => $schemaUrl,
    'brand' => [
      '@type' => 'Brand',
      'name' => 'We Offer Wellness',
    ],
    'category' => $type,
    'provider' => [
      '@type' => 'Organization',
      'name' => 'We Offer Wellness',
      'url' => url('/'),
    ],
    'hoursAvailable' => $schemaHoursAvailable ?: null,
    'sku' => isset($p['id']) ? (string) $p['id'] : null,
    'offers' => $schemaOffers,
    'aggregateRating' => $schemaAggregate,
  ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

  $schemaService = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    '@id' => $schemaUrl . '#service',
    'name' => $title,
    'description' => trim(strip_tags($summary ?: $body ?: $what ?: '')),
    'image' => $schemaImages ?: null,
    'url' => $schemaUrl,
    'mainEntityOfPage' => $schemaUrl,
    'serviceType' => trim((string) (data_get($p, 'category.name') ?: $type)) ?: null,
    'provider' => $schemaProviderId !== null ? ['@id' => $schemaProviderId] : null,
    'hoursAvailable' => $availabilityConfigured && $schemaHoursAvailable !== [] ? $schemaHoursAvailable : null,
    'offers' => $schemaOffers,
    'aggregateRating' => $schemaAggregate,
    'sku' => isset($p['id']) ? (string) $p['id'] : null,
  ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

  $schemaServiceCatalogItem = $schemaService;
  unset($schemaServiceCatalogItem['@context']);

  $schemaOfferCatalogName = trim((string) (
    data_get($p, 'type.name')
    ?: ucfirst((string) $type)
  ));
  if ($schemaOfferCatalogName === '') {
    $schemaOfferCatalogName = 'Wellness Services';
  }

  $schemaOfferCatalog = array_filter([
    '@type' => 'OfferCatalog',
    'name' => $schemaOfferCatalogName,
    'itemListElement' => [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'item' => $schemaServiceCatalogItem,
      ],
    ],
  ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

  $schemaLocalBusiness = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    '@id' => url('/') . '#localbusiness',
    'name' => 'We Offer Wellness',
    'url' => url('/'),
    'image' => $schemaImages[0] ?? null,
    'description' => trim(strip_tags($summary ?: $body ?: $what ?: '')),
    'telephone' => $schemaTelephone !== '' ? $schemaTelephone : null,
    'priceRange' => $schemaPriceRange ?: null,
    'address' => $schemaBusinessAddress ?: null,
    'hasOfferCatalog' => $schemaOfferCatalog,
  ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

  $schemaProviderEntity = null;
  if ($schemaProviderId !== null && $schemaProviderName !== '') {
    $schemaProviderEntity = array_filter([
      '@type' => $schemaProviderType,
      '@id' => $schemaProviderId,
      'name' => $schemaProviderName,
      'url' => $schemaProviderUrl !== '' ? $schemaProviderUrl : null,
    ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
  }
  $schemaServiceGraphItem = $schemaService;
  unset($schemaServiceGraphItem['@context']);
  $schemaServiceGraph = [
    '@context' => 'https://schema.org',
    '@graph' => array_values(array_filter([
      [
        '@type' => 'Organization',
        '@id' => url('/') . '#organization',
        'name' => 'We Offer Wellness®',
        'url' => url('/'),
      ],
      [
        '@type' => 'WebSite',
        '@id' => url('/') . '#website',
        'name' => 'We Offer Wellness®',
        'url' => url('/'),
        'publisher' => ['@id' => url('/') . '#organization'],
      ],
      [
        '@type' => 'WebPage',
        '@id' => $schemaUrl . '#webpage',
        'url' => $schemaUrl,
        'name' => $title . ' | We Offer Wellness®',
        'isPartOf' => ['@id' => url('/') . '#website'],
        'mainEntity' => ['@id' => $schemaUrl . '#service'],
        'publisher' => ['@id' => url('/') . '#organization'],
      ],
      $schemaProviderEntity,
      $schemaServiceGraphItem,
    ], static fn ($value) => $value !== null && $value !== '' && $value !== [])),
  ];

  $schemaEventGraph = null;
  if ($isEventOffering) {
    $schemaEventTimezone = trim((string) data_get($p, 'event.timezone', 'Europe/London'));
    if ($schemaEventTimezone === '') {
      $schemaEventTimezone = 'Europe/London';
    }

    $schemaEventDateTime = function (?string $date, ?string $time = null) use ($schemaEventTimezone): ?string {
      $date = trim((string) $date);
      $time = trim((string) $time);
      if ($date === '') {
        return null;
      }

      try {
        $raw = $time !== '' ? ($date . ' ' . $time) : $date;
        return \Carbon\Carbon::parse($raw, $schemaEventTimezone)->toAtomString();
      } catch (\Throwable $e) {
        return null;
      }
    };

    $schemaEventSummarySource = trim((string) ($summary ?: $what ?: $included ?: $body ?: ''));
    $schemaEventDescription = $schemaEventSummarySource !== ''
      ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($schemaEventSummarySource)) ?? $schemaEventSummarySource), 240, '…')
      : ('Book ' . $title . ' with trusted practitioners at We Offer Wellness®.');

    $schemaEventPractitioner = is_array($p['practitioner'] ?? null) ? $p['practitioner'] : [];
    $schemaEventPractitionerName = trim((string) ($schemaEventPractitioner['name'] ?? ''));
    $schemaEventPractitionerUrl = trim((string) ($schemaEventPractitioner['profile_url'] ?? ''));
    $schemaEventOrganizer = $schemaProviderId !== null && $schemaProviderName !== ''
      ? ['@id' => $schemaProviderId]
      : ['@id' => url('/') . '#organization'];

    $schemaEventLocations = is_array($p['locations'] ?? null) ? array_values(array_filter($p['locations'])) : [];
    $schemaEventHasOnline = collect($schemaEventLocations)->contains(fn ($location) => str_contains(strtolower((string) $location), 'online'));
    $schemaEventHasPhysical = collect($schemaEventLocations)->contains(fn ($location) => !str_contains(strtolower((string) $location), 'online'));
    $schemaEventAttendanceMode = $schemaEventHasOnline && ! $schemaEventHasPhysical
      ? 'https://schema.org/OnlineEventAttendanceMode'
      : 'https://schema.org/OfflineEventAttendanceMode';

    $schemaEventPlace = null;
    $schemaEventLocation = null;
    $schemaEventVenueLocations = array_values(array_filter(is_array($p['venue_locations'] ?? null) ? $p['venue_locations'] : []));
    $schemaEventVenue = is_array($schemaEventVenueLocations[0] ?? null) ? $schemaEventVenueLocations[0] : [];
    $schemaEventVenueName = trim((string) data_get($schemaEventVenue, 'label', ''));
    $schemaEventStreetAddress = trim((string) data_get($schemaEventVenue, 'address_line_1', ''));
    $schemaEventAddressLine2 = trim((string) data_get($schemaEventVenue, 'address_line_2', ''));
    $schemaEventCity = trim((string) data_get($schemaEventVenue, 'city', ''));
    $schemaEventCounty = trim((string) data_get($schemaEventVenue, 'county', ''));
    $schemaEventPostcode = trim((string) data_get($schemaEventVenue, 'postcode', ''));
    $schemaEventCountry = trim((string) data_get($schemaEventVenue, 'country', 'GB')) ?: 'GB';
    $schemaEventLat = data_get($schemaEventVenue, 'lat');
    $schemaEventLng = data_get($schemaEventVenue, 'lng');
    $schemaEventHasGeo = is_numeric($schemaEventLat) && is_numeric($schemaEventLng);
    $schemaEventMapsUrl = '';
    if ($schemaEventHasGeo) {
      $schemaEventMapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) $schemaEventLat . ',' . (string) $schemaEventLng);
    } else {
      $schemaEventMapQuery = trim(implode(', ', array_filter([
        $schemaEventVenueName,
        $schemaEventStreetAddress,
        $schemaEventAddressLine2,
        $schemaEventCity,
        $schemaEventCounty,
        $schemaEventPostcode,
        $schemaEventCountry,
      ])));
      if ($schemaEventMapQuery !== '') {
        $schemaEventMapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($schemaEventMapQuery);
      }
    }

    if ($schemaEventAttendanceMode !== 'https://schema.org/OnlineEventAttendanceMode') {
      $schemaEventPlace = array_filter([
        '@type' => 'Place',
        '@id' => $schemaUrl . '#place',
        'name' => $schemaEventVenueName !== '' ? $schemaEventVenueName : null,
        'url' => $schemaEventMapsUrl !== '' ? $schemaEventMapsUrl : null,
        'address' => array_filter([
          '@type' => 'PostalAddress',
          'streetAddress' => trim(implode(', ', array_filter([$schemaEventStreetAddress, $schemaEventAddressLine2]))),
          'addressLocality' => $schemaEventCity !== '' ? $schemaEventCity : null,
          'addressRegion' => $schemaEventCounty !== '' ? $schemaEventCounty : null,
          'postalCode' => $schemaEventPostcode !== '' ? $schemaEventPostcode : null,
          'addressCountry' => $schemaEventCountry,
        ], static fn ($value) => $value !== null && $value !== ''),
        'geo' => $schemaEventHasGeo ? [
          '@type' => 'GeoCoordinates',
          'latitude' => (float) $schemaEventLat,
          'longitude' => (float) $schemaEventLng,
        ] : null,
        'hasMap' => $schemaEventMapsUrl !== '' ? $schemaEventMapsUrl : null,
      ], static fn ($value) => $value !== null && $value !== '');

      $schemaEventLocation = [
        '@id' => $schemaUrl . '#place',
      ];
    } else {
      $schemaEventLocation = [
        '@type' => 'VirtualLocation',
        'url' => $schemaUrl,
      ];
    }

    $schemaEventOffers = [];
    $schemaEventValidFrom = null;
    $schemaEventValidFromRaw = trim((string) (
      data_get($p, 'event.valid_from')
      ?: data_get($p, 'event.validFrom')
      ?: data_get($p, 'event.on_sale_at')
      ?: data_get($p, 'event.sale_start_at')
      ?: ''
    ));
    if ($schemaEventValidFromRaw !== '') {
      try {
        $schemaEventValidFrom = \Carbon\Carbon::parse($schemaEventValidFromRaw)->toAtomString();
      } catch (\Throwable $e) {
        $schemaEventValidFrom = null;
      }
    }
    if ($showBookingUi) {
      foreach ((array) ($p['variants'] ?? []) as $variant) {
        if (! is_array($variant)) {
          continue;
        }

        $variantId = trim((string) ($variant['id'] ?? ''));
        $variantLabel = trim((string) ($variant['label'] ?? implode(' • ', array_filter(array_map('trim', (array) ($variant['selection'] ?? $variant['options'] ?? []))))));
        if ($variantLabel === '') {
          $variantLabel = $variantId !== '' ? $variantId : 'Ticket';
        }

        $variantPrice = $schemaMoneyValue($variant['price'] ?? null);
        if ($variantPrice === null || $variantPrice < 0) {
          continue;
        }

        $variantUrl = $schemaUrl;
        if ($variantId !== '') {
          $variantUrl .= (str_contains($schemaUrl, '?') ? '&' : '?') . 'variant=' . rawurlencode($variantId);
        }

        $schemaEventOffer = array_filter([
          '@type' => 'Offer',
          '@id' => $schemaUrl . '#offer-' . ($variantId !== '' ? \Illuminate\Support\Str::slug($variantId) : \Illuminate\Support\Str::slug($variantLabel)),
          'name' => $variantLabel,
          'url' => $variantUrl,
          'price' => $variantPrice,
          'priceCurrency' => $schemaPriceCurrency,
          'seller' => [
            '@id' => url('/') . '#organization',
          ],
        ], static fn ($value) => $value !== null && $value !== '');
        if (array_key_exists('available', $variant)) {
          $schemaEventOffer['availability'] = (bool) $variant['available']
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';
        }
        if ($schemaEventValidFrom !== null) {
          $schemaEventOffer['validFrom'] = $schemaEventValidFrom;
        }
        $schemaEventOffers[] = $schemaEventOffer;
      }

      if ($schemaEventOffers === [] && $schemaFallbackPrice !== null && $schemaFallbackPrice >= 0) {
        $schemaEventOffer = [
          '@type' => 'Offer',
          '@id' => $schemaUrl . '#offer-default',
          'name' => $title . ' ticket',
          'url' => $schemaUrl,
          'price' => $schemaFallbackPrice,
          'priceCurrency' => $schemaPriceCurrency,
          'seller' => [
            '@id' => url('/') . '#organization',
          ],
        ];
        if ($schemaEventValidFrom !== null) {
          $schemaEventOffer['validFrom'] = $schemaEventValidFrom;
        }
        $schemaEventOffers[] = $schemaEventOffer;
      }
    }

    $schemaEventReviewSource = array_merge(
      is_array($p['client_reviews'] ?? null) ? $p['client_reviews'] : [],
      is_array($p['reviews'] ?? null) ? $p['reviews'] : []
    );
    $schemaEventReviews = [];
    $schemaEventRatingValues = [];
    $schemaEventReviewKeys = [];
    foreach ($schemaEventReviewSource as $review) {
      $reviewText = trim((string) ($review['body'] ?? $review['review'] ?? $review['review_text'] ?? ''));
      if ($reviewText === '') {
        continue;
      }

      $reviewRatingRaw = $review['rating'] ?? $review['ratingValue'] ?? null;
      $reviewRating = is_numeric($reviewRatingRaw) && (float) $reviewRatingRaw >= 1 && (float) $reviewRatingRaw <= 5
        ? (float) $reviewRatingRaw
        : null;

      $authorName = trim((string) ($review['author'] ?? data_get($review, 'user.name', 'Verified customer')));
      $datePublished = trim((string) ($review['date'] ?? $review['created_at'] ?? ''));
      $schemaEventReviewKey = strtolower($authorName . '|' . ($reviewRating ?? '') . '|' . $reviewText);
      if (isset($schemaEventReviewKeys[$schemaEventReviewKey])) {
        continue;
      }
      $schemaEventReviewKeys[$schemaEventReviewKey] = true;
      $schemaEventReview = [
        '@type' => 'Review',
        'reviewBody' => $reviewText,
        'author' => [
          '@type' => 'Person',
          'name' => $authorName !== '' ? $authorName : 'Verified customer',
        ],
      ];
      if ($reviewRating !== null) {
        $schemaEventReview['reviewRating'] = [
          '@type' => 'Rating',
          'ratingValue' => $reviewRating,
          'bestRating' => 5,
          'worstRating' => 1,
        ];
      }
      if ($datePublished !== '') {
        $schemaEventReview['datePublished'] = $datePublished;
      }

      $schemaEventReviews[] = $schemaEventReview;
      $schemaEventRatingValues[] = $reviewRating;
    }

    $schemaEventAggregate = null;
    $schemaEventReviewCount = max((int) ($p['review_count'] ?? 0), count($schemaEventReviews));
    $schemaEventRatingFallback = is_numeric($p['rating'] ?? null) && (float) $p['rating'] > 0
      ? (float) $p['rating']
      : null;
    if ($schemaEventReviewCount > 0 && ($schemaEventRatingValues !== [] || $schemaEventRatingFallback !== null)) {
      $ratingValue = $schemaEventRatingValues !== []
        ? (array_sum($schemaEventRatingValues) / max(1, count($schemaEventRatingValues)))
        : $schemaEventRatingFallback;
      $schemaEventAggregate = [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format((float) $ratingValue, 1, '.', ''),
        'reviewCount' => $schemaEventReviewCount,
        'bestRating' => 5,
        'worstRating' => 1,
      ];
    }

    $schemaEventBreadcrumbItems = [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => url('/'),
      ],
      [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => 'Events',
        'item' => url('/events'),
      ],
      [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => trim((string) (data_get($p, 'category.name') ?: ucfirst($type))),
        'item' => url('/' . \Illuminate\Support\Str::slug((string) (data_get($p, 'category.name') ?: ucfirst($type)))),
      ],
      [
        '@type' => 'ListItem',
        'position' => 4,
        'name' => $title,
        'item' => $schemaUrl,
      ],
    ];

    $schemaEventPerformers = [];
    if ($schemaEventPractitionerName !== '') {
      $schemaEventPerformers[] = array_filter([
        '@type' => 'Person',
        'name' => $schemaEventPractitionerName,
        'url' => $schemaEventPractitionerUrl !== '' ? $schemaEventPractitionerUrl : null,
      ], static fn ($value) => $value !== null && $value !== '');
    }

    $schemaEventLinks = [];
    $schemaEventLinksSource = data_get($p, 'event_links', []);
    if ($schemaEventLinksSource instanceof \Illuminate\Support\Collection) {
      $schemaEventLinksSource = $schemaEventLinksSource->all();
    }
    if (is_string($schemaEventLinksSource)) {
      $schemaEventLinksSource = json_decode($schemaEventLinksSource, true) ?: [];
    }
    if (is_object($schemaEventLinksSource)) {
      $schemaEventLinksSource = (array) $schemaEventLinksSource;
    }
    if (! is_array($schemaEventLinksSource)) {
      $schemaEventLinksSource = [];
    }
    foreach ($schemaEventLinksSource as $schemaEventLink) {
      if (is_object($schemaEventLink)) {
        $schemaEventLink = (array) $schemaEventLink;
      }
      if (! is_array($schemaEventLink)) {
        continue;
      }
      $schemaEventLinkUrl = trim((string) ($schemaEventLink['url'] ?? ''));
      if ($schemaEventLinkUrl === '') {
        continue;
      }
      $schemaEventLinks[] = $schemaEventLinkUrl;
    }
    $schemaEventLinks = array_values(array_unique($schemaEventLinks));

    $schemaEventVideoUrl = trim((string) data_get($p, 'video_url', ''));
    $schemaEventVideo = null;
    if ($schemaEventVideoUrl !== '') {
      $schemaEventVideoUploadDate = trim((string) data_get($p, 'created_at', data_get($p, 'event.created_at', '')));
      if ($schemaEventVideoUploadDate === '') {
        $schemaEventVideoUploadDate = now()->toAtomString();
      } else {
        try {
          $schemaEventVideoUploadDate = \Carbon\Carbon::parse($schemaEventVideoUploadDate)->toAtomString();
        } catch (\Throwable $e) {
          $schemaEventVideoUploadDate = now()->toAtomString();
        }
      }
      $schemaEventVideo = [
        '@id' => $schemaUrl . '#video',
        '@type' => 'VideoObject',
        'name' => $title . ' video',
        'description' => $schemaEventDescription,
        'contentUrl' => $schemaEventVideoUrl,
        'url' => $schemaEventVideoUrl,
        'thumbnailUrl' => $schemaImages[0] ?? null,
        'encodingFormat' => str_contains(strtolower($schemaEventVideoUrl), '.mov') ? 'video/quicktime' : null,
        'uploadDate' => $schemaEventVideoUploadDate,
      ];
    }

    $schemaEventVenuePlace = null;
    $schemaEventSpacePlaces = [];
    $schemaEventSubEvents = [];
    $schemaEventSchedule = data_get($p, 'event.schedule', []);
    if (is_object($schemaEventSchedule)) {
      $schemaEventSchedule = (array) $schemaEventSchedule;
    }
    if (! is_array($schemaEventSchedule)) {
      $schemaEventSchedule = [];
    }
    $schemaEventScheduleDays = $schemaEventSchedule['days'] ?? [];
    if ($schemaEventScheduleDays instanceof \Illuminate\Support\Collection) {
      $schemaEventScheduleDays = $schemaEventScheduleDays->all();
    }
    if (! is_array($schemaEventScheduleDays)) {
      $schemaEventScheduleDays = [];
    }
    $schemaEventSpaceMap = [];
    $schemaEventVenueName = trim((string) $schemaEventVenueName);
    $schemaEventPostalAddress = null;
    if ($schemaEventStreetAddress !== '' || $schemaEventAddressLine2 !== '' || $schemaEventCity !== '' || $schemaEventCounty !== '' || $schemaEventPostcode !== '') {
      $schemaEventPostalAddress = array_filter([
        '@type' => 'PostalAddress',
        'streetAddress' => trim(implode(', ', array_filter([$schemaEventStreetAddress, $schemaEventAddressLine2]))),
        'addressLocality' => $schemaEventCity !== '' ? $schemaEventCity : null,
        'addressRegion' => $schemaEventCounty !== '' ? $schemaEventCounty : null,
        'postalCode' => $schemaEventPostcode !== '' ? $schemaEventPostcode : null,
        'addressCountry' => $schemaEventCountry !== '' ? $schemaEventCountry : 'GB',
      ], static fn ($value) => $value !== null && $value !== '');
    }
    $schemaEventVenuePlace = $schemaEventPlace ?? [
      '@type' => 'Place',
      '@id' => $schemaUrl . '#venue',
      'name' => $schemaEventVenueName !== '' ? $schemaEventVenueName : null,
      'address' => $schemaEventPostalAddress,
      'image' => $schemaImages[0] ?? null,
    ];
    $schemaEventVenuePlaceId = trim((string) data_get($schemaEventVenuePlace, '@id', $schemaUrl . '#venue'));

    foreach ($schemaEventScheduleDays as $dayIndex => $day) {
      if (is_object($day)) {
        $day = (array) $day;
      }
      if (! is_array($day)) {
        continue;
      }

      $dayDate = trim((string) data_get($day, 'date', ''));
      $rawSessions = data_get($day, 'sessions', []);
      if ($rawSessions instanceof \Illuminate\Support\Collection) {
        $rawSessions = $rawSessions->all();
      }
      if (! is_array($rawSessions)) {
        $rawSessions = [];
      }

      foreach ($rawSessions as $sessionIndex => $session) {
        if (is_object($session)) {
          $session = (array) $session;
        }
        if (! is_array($session)) {
          continue;
        }

        $spaceName = trim((string) data_get($session, 'space_area', ''));
        $spaceKey = $spaceName !== '' ? \Illuminate\Support\Str::slug($spaceName) : 'space';
        if ($spaceName !== '' && ! isset($schemaEventSpaceMap[$spaceKey])) {
          $schemaEventSpaceMap[$spaceKey] = $schemaUrl . '#space-' . $spaceKey;
          $schemaEventSpacePlaces[] = [
            '@type' => 'Place',
            '@id' => $schemaEventSpaceMap[$spaceKey],
            'name' => $spaceName,
            'address' => $schemaEventPostalAddress,
            'image' => $schemaImages[0] ?? null,
            'containedInPlace' => [
              '@id' => $schemaEventVenuePlaceId,
            ],
          ];
        }

        $sessionTitle = trim((string) data_get($session, 'label', ''));
        if ($sessionTitle === '') {
          continue;
        }
        $startTimeValue = trim((string) data_get($session, 'start_time', ''));
        $endTimeValue = trim((string) data_get($session, 'end_time', ''));
        $sessionFacilitator = trim((string) data_get($session, 'facilitator', data_get($session, 'practitioner', data_get($session, 'facilitator_name', ''))));
        $sessionNotes = trim((string) data_get($session, 'notes', ''));
        $sessionDescription = $sessionNotes;
        $sessionPerformer = null;
        if ($sessionFacilitator !== '') {
          $sessionPerformer = [
            '@type' => 'Organization',
            'name' => $sessionFacilitator,
          ];
          if ($sessionDescription === '') {
            $sessionDescription = $sessionFacilitator;
          }
        }
        if (preg_match('/Facilitator:\s*(.+?)(?:\.)?$/i', $sessionNotes, $sessionMatch)) {
          $sessionPerformerName = trim((string) $sessionMatch[1]);
          if ($sessionPerformerName !== '') {
            $sessionPerformer = [
              '@type' => 'Organization',
              'name' => $sessionPerformerName,
            ];
            $sessionDescription = trim((string) preg_replace('/\s*Facilitator:\s*.+?\.?$/i', '', $sessionNotes));
          }
        }

        $sessionId = $schemaUrl . '#' . \Illuminate\Support\Str::slug(trim(implode(' ', array_filter([
          $dayDate,
          $spaceName,
          $sessionTitle,
          $startTimeValue,
        ]))), '-');
        if ($sessionId === $schemaUrl . '#') {
          $sessionId = $schemaUrl . '#session-' . $dayIndex . '-' . $sessionIndex;
        }

        $schemaEventSubEvents[] = [
          '@id' => $sessionId,
          '@type' => 'Event',
          'name' => $sessionTitle,
          'description' => $sessionDescription !== '' ? $sessionDescription : null,
          'startDate' => $schemaEventDateTime($dayDate, $startTimeValue),
          'endDate' => $schemaEventDateTime($dayDate, $endTimeValue !== '' ? $endTimeValue : $startTimeValue),
          'eventStatus' => $isPastEvent ? 'https://schema.org/EventCompleted' : 'https://schema.org/EventScheduled',
          'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
          'location' => $spaceName !== '' && isset($schemaEventSpaceMap[$spaceKey])
            ? ['@id' => $schemaEventSpaceMap[$spaceKey]]
            : ['@id' => $schemaEventVenuePlaceId],
          'organizer' => $schemaEventOrganizer,
          'performer' => $sessionPerformer,
          'url' => $schemaUrl,
          'image' => $schemaImages ?: null,
          'offers' => $schemaOffers ?: null,
          'isPartOf' => [
            '@id' => $schemaUrl . '#event',
          ],
          'superEvent' => [
            '@id' => $schemaUrl . '#event',
          ],
        ];
      }
    }

    $schemaEventGraphNodes = [
      [
        '@type' => 'Organization',
        '@id' => url('/') . '#organization',
        'name' => 'We Offer Wellness®',
        'alternateName' => 'WOW',
        'url' => url('/'),
        'logo' => [
          '@type' => 'ImageObject',
          'url' => 'https://studio.weofferwellness.co.uk/storage/uploads/images/e9dc87f9-01bf-4ffd-be8f-e1f49a85bf41.png',
        ],
        'sameAs' => [
          'https://studio.weofferwellness.co.uk/',
          'https://times.weofferwellness.co.uk/',
          'https://times.weofferwellness.co.uk/seeking-wellness',
          'https://uk.trustpilot.com/review/weofferwellness.co.uk',
          'https://www.facebook.com/p/We-Offer-Wellness-61551580484062/',
          'https://www.instagram.com/we_offer_wellness/',
          'https://uk.linkedin.com/company/we-offer-wellness-wow',
          'https://www.youtube.com/@WeOfferWellness',
          'https://www.eventbrite.co.uk/o/we-offer-wellness-88260749533',
          'https://open.spotify.com/show/5L8zM83I4zTtQVl0UvsUt7',
          'https://podcasts.apple.com/us/podcast/seeking-wellness-with-tash/id1804997207?uo=4',
          'https://www.youtube.com/@SeekingWellnesswithTash',
          'https://find-and-update.company-information.service.gov.uk/company/15323457',
        ],
      ],
      [
        '@type' => 'WebSite',
        '@id' => url('/') . '#website',
        'name' => 'We Offer Wellness®',
        'url' => url('/'),
        'publisher' => [
          '@id' => url('/') . '#organization',
        ],
      ],
      [
        '@type' => 'WebPage',
        '@id' => $schemaUrl . '#webpage',
        'url' => $schemaUrl,
        'name' => $title . ' | We Offer Wellness®',
        'description' => $schemaEventDescription,
        'isPartOf' => [
          '@id' => url('/') . '#website',
        ],
        'about' => [
          '@id' => $schemaUrl . '#event',
        ],
        'mainEntity' => [
          '@id' => $schemaUrl . '#event',
        ],
        'sameAs' => $schemaEventLinks ?: null,
        'breadcrumb' => [
          '@id' => $schemaUrl . '#breadcrumb',
        ],
        'publisher' => [
          '@id' => url('/') . '#organization',
        ],
        'inLanguage' => 'en-GB',
      ],
      [
        '@type' => 'BreadcrumbList',
        '@id' => $schemaUrl . '#breadcrumb',
        'name' => $title . ' breadcrumb trail',
        'itemListElement' => $schemaEventBreadcrumbItems,
      ],
      $schemaEventVenuePlace,
      $schemaEventSpacePlaces ?: null,
      $schemaEventVideo,
      [
        '@type' => 'Event',
        '@id' => $schemaUrl . '#event',
        'name' => $title,
        'alternateName' => trim((string) data_get($p, 'event.title', '')),
        'description' => $schemaEventDescription,
        'url' => $schemaUrl,
        'mainEntityOfPage' => [
          '@id' => $schemaUrl . '#webpage',
        ],
        'image' => $schemaImages ?: null,
        'startDate' => $schemaEventDateTime($eventStartDate, $eventStartTime),
        'endDate' => $schemaEventDateTime($eventEndDate !== '' ? $eventEndDate : $eventStartDate, $eventEndTime !== '' ? $eventEndTime : $eventStartTime),
        'doorTime' => $schemaEventDateTime($eventStartDate, $eventStartTime),
        'eventStatus' => $isPastEvent ? 'https://schema.org/EventCompleted' : 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => $schemaEventAttendanceMode,
        'location' => $schemaEventLocation,
        'sameAs' => $schemaEventLinks ?: null,
        'organizer' => $schemaEventOrganizer,
        'performer' => $schemaEventPerformers ?: null,
        'video' => $schemaEventVideo ? ['@id' => $schemaUrl . '#video'] : null,
        'subEvent' => $schemaEventSubEvents ?: null,
        'aggregateRating' => $schemaEventAggregate,
        'keywords' => array_values(array_filter(array_merge([
          trim((string) ($p['title'] ?? '')),
          trim((string) data_get($p, 'category.name', '')),
          trim((string) ($p['type'] ?? 'event')),
          trim((string) (data_get($p, 'category.name') ?: '')),
          $schemaEventVenueName !== '' ? $schemaEventVenueName : null,
          $schemaEventHasOnline ? 'online' : 'in-person',
        ], (array) ($p['tags'] ?? [])))),
        'offers' => $schemaEventOffers ?: null,
        'isAccessibleForFree' => collect($schemaEventOffers)->contains(fn ($offer): bool => isset($offer['price']) && (float) $offer['price'] === 0.0),
        'maximumAttendeeCapacity' => is_numeric(data_get($p, 'capacity')) ? (int) data_get($p, 'capacity') : null,
      ],
    ];
    $schemaEventGraph = [
      '@context' => 'https://schema.org',
      '@graph' => array_values(array_filter($schemaEventGraphNodes, static fn ($value) => $value !== null && $value !== '')),
    ];
  }
  $schemaJsonLd = $isEventOffering
    ? $schemaEventGraph
    : ($isGiftCardOffering ? $schemaProduct : $schemaServiceGraph);
  $schemaFormatSlug = strtolower(trim((string) ($p['format'] ?? ($isEventOffering ? 'events' : 'therapies'))));
  if (! in_array($schemaFormatSlug, ['therapies', 'classes', 'events', 'workshops', 'retreats', 'gifts'], true)) {
    $schemaFormatSlug = $isEventOffering ? 'events' : 'therapies';
  }
  $schemaFormatLabel = \Illuminate\Support\Str::headline($schemaFormatSlug);
  $schemaModalityLabel = trim((string) (data_get($p, 'category.name') ?: data_get($p, 'modality') ?: ''));
  $schemaModalitySlug = \Illuminate\Support\Str::slug($schemaModalityLabel);
  $schemaFormatUrl = url('/' . $schemaFormatSlug);
  $schemaModalityUrl = $schemaModalitySlug !== '' ? url('/' . $schemaFormatSlug . '/' . $schemaModalitySlug) : $schemaFormatUrl;
  $breadcrumbPricingVendor = [
    'vendor_name' => data_get($p, 'vendor_name') ?: data_get($p, 'vendor.vendor_name'),
    'user' => [
      'name' => data_get($p, 'vendor.user.name', data_get($p, 'practitioner_name', '')),
      'email' => data_get($p, 'vendor.user.email', ''),
    ],
  ];
  $breadcrumbPrice = is_numeric($priceMin)
    ? app(\App\Services\MarketplacePricingService::class)->buyerPrice(
        $priceMin,
        $breadcrumbPricingVendor,
        (int) data_get($p, 'vendor_id', 0),
        data_get($p, 'kind') !== 'physical_product'
    )
    : null;
@endphp

<div
  hidden
  data-wow-analytics-page="offering"
  data-wow-analytics-item="{!! e(json_encode([
    'id' => data_get($p, 'id', data_get($p, 'offering_id', '')),
    'offering_id' => data_get($p, 'offering_id', data_get($p, 'id', '')),
    'title' => $title,
    'price' => $priceMin,
    'currency' => strtoupper((string) data_get($p, 'currency', 'GBP')),
    'source_version' => $p['source_version'] ?? 'unknown',
    'catalogue_type' => $isEventOffering ? 'event' : 'offering',
    'modality' => data_get($p, 'category.name', data_get($p, 'category.slug', '')),
    'provider_id' => data_get($p, 'vendor_id', data_get($p, 'provider_id', '')),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !!}"
></div>

@push('head')
  @once
    @if(!empty($schemaJsonLd))
      <script type="application/ld+json">{!! json_encode($schemaJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
    @endif
  @endonce
@endpush

@include('partials.breadcrumbs', [
  'schemaEnabled' => ! $isEventOffering,
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    [
      'label' => $schemaFormatLabel,
      'url' => $schemaFormatUrl,
    ],
    [
      'label' => $schemaModalityLabel !== '' ? $schemaModalityLabel : $schemaFormatLabel,
      'url' => $schemaModalityUrl,
    ],
    ['label' => $title],
  ],
  'schemaUrl' => $schemaUrl,
  'chips' => array_filter([
    $mode !== '' ? $mode : null,
    $breadcrumbPrice !== null ? 'From £' . number_format((float) $breadcrumbPrice, 2) : null,
  ]),
])

<section class="section product-page">
  <style>
    /* Force Manrope for all headings/titles on product page */
    .product-page h1,
    .product-page h2,
    .product-page h3,
    .product-page .wow-section-title,
    .product-page .wow-acc-title,
    .product-page .wow-title {
      font-family: 'Manrope', var(--bs-font-sans-serif) !important;
    }
    .product-page h1 { font-weight: 600 !important; }

    /* FOMO text styling within content-bottom */
    .content-bottom .fomo {
      margin: 0 0 8px;
      font-size: var(--fomo);
      font-weight: 600;
      color: rgba(11, 18, 32, .84);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  </style>
  <div class="container-page">
    @if($isEventOffering)
      <div class="row g-4 align-items-start">
        <div class="col-12">
          @include('offering.partials.event_body_template', ['product' => $p, 'type' => $type, 'showBookingUi' => $showBookingUi])
        </div>
      </div>
    @elseif($isV3Offering)
      @include('offering.partials.v3_body_template', ['product' => $p, 'type' => $type, 'showPaymentModule' => $showPaymentModule])
    @else
      @include('offering.partials.v3_body_template', ['product' => $p, 'type' => $type, 'showPaymentModule' => $showPaymentModule])
      @if(false)
      <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
          <div class="mb-3">
            <div class="kicker mb-1">{{ ucfirst($type ?? 'Therapy') }}</div>
            <h1 class="h2 m-0">{{ $title ?? 'Offering' }}</h1>
          </div>
          @if(!empty($images))
            @include('offering.partials.gallery', ['images' => $images, 'title' => $title])
          @else
            <div class="card p-2">
              <div class="ratio ratio-16x9 bg-ink-100 rounded"></div>
            </div>
          @endif

          @include('offering.partials.details_accordion', [
            'summary' => $summary,
            'body' => $body,
            'what' => $what,
            'included' => $included,
            'safety' => $safety,
            'contra' => $contra,
            'locationsList' => $locationsList,
            'participantRange' => $participantRange,
            'mode' => $mode,
            'locations' => $locations,
            'availability_pattern' => $availabilityPattern,
            'availability_lead_time' => $availabilityLeadTime,
            'availability_duration' => $availabilityDuration,
            'duration_text' => $durationText,
          ])
        </div>

        <div class="col-12 col-lg-4">
          @include('offering.partials.advanced_buybox')

          @include('offering.partials.variant_helper')

          @php
            $clientReviews = $p['client_reviews'] ?? [];
          @endphp
          @if(!empty($clientReviews))
            <div class="card p-4 mt-4">
              <h3 class="h6 m-0">Client reviews</h3>
              <div class="mt-3 d-grid gap-3">
                @foreach($clientReviews as $review)
                  @php
                    $body = trim((string)($review['body'] ?? ''));
                    $preview = $body;
                    $needsToggle = false;
                    if ($body !== '') {
                      $preview = \Illuminate\Support\Str::words($body, $reviewPreviewWords, '…');
                      $needsToggle = ($preview !== $body);
                    }
                  @endphp
                  <div class="p-3 border rounded bg-ink-50 js-review-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="fw-semibold text-ink-900">{{ $review['author'] ?? 'Verified client' }}</div>
                      <div class="text-warning small" aria-label="{{ $review['rating'] ?? 0 }} out of 5 stars">
                        @for($i = 1; $i <= 5; $i++)
                          <i class="bi {{ $i <= ($review['rating'] ?? 0) ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                      </div>
                    </div>
                    <p class="mb-2 text-ink-800 js-review-body" style="white-space:pre-line;" data-expanded="false">{{ $preview }}</p>
                    @if($needsToggle)
                      <button type="button" class="btn btn-link px-0 text-decoration-underline fw-semibold small js-review-toggle" aria-expanded="false">Read more</button>
                      <template class="js-review-preview">{{ $preview }}</template>
                      <template class="js-review-full">{{ $body }}</template>
                    @endif
                    <div class="small text-muted">{{ $review['date'] ?? '' }}</div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>
      @endif
    @endif
  </div>
</section>

@endsection

@push('scripts')
<script>
(function(){
  function hydrateReview(card){
    var body = card.querySelector('.js-review-body');
    var toggle = card.querySelector('.js-review-toggle');
    if(!body || !toggle) return;
    var fullTpl = card.querySelector('.js-review-full');
    var previewTpl = card.querySelector('.js-review-preview');
    var fullText = fullTpl ? fullTpl.textContent.trim() : '';
    var previewText = previewTpl ? previewTpl.textContent.trim() : body.textContent.trim();
    if(!fullText || fullText === previewText){
      toggle.remove();
      return;
    }
    var expanded = false;
    function apply(){
      body.textContent = expanded ? fullText : previewText;
      toggle.textContent = expanded ? 'Read less' : 'Read more';
      toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      body.dataset.expanded = expanded ? 'true' : 'false';
    }
    toggle.addEventListener('click', function(){
      expanded = !expanded;
      apply();
    });
    apply();
  }
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.js-review-card').forEach(hydrateReview);
  });
})();
</script>
@endpush

 
