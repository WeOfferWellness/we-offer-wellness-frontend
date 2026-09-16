@php
    $offering = is_array($product ?? null) ? $product : [];
    $title = trim((string) ($offering['title'] ?? 'Experience'));
    $summary = trim((string) ($offering['summary'] ?? ''));
    $bodyHtml = trim((string) ($offering['body_html'] ?? ''));
    $whatToExpect = trim((string) ($offering['what_to_expect'] ?? ''));
    $included = trim((string) ($offering['included'] ?? ''));
    $safetyNotes = trim((string) ($offering['safety_notes'] ?? ''));
    $contraindications = trim((string) ($offering['contraindications'] ?? ''));
    $mode = trim((string) ($offering['mode'] ?? ''));
    $bookingFlow = strtolower(trim((string) ($offering['booking_flow'] ?? 'flexible')));
    $sourceVersion = strtolower(trim((string) ($offering['source_version'] ?? '')));
    $isStoreProduct = (bool) ($isStoreProduct ?? false) || ($offering['kind'] ?? '') === 'physical_product';
    $productOnly = (bool) ($productOnly ?? false);
    $usesLegacyBuybox = $isStoreProduct;
    $currency = strtoupper(trim((string) ($offering['currency'] ?? 'GBP')));
    $rawPrice = is_numeric($offering['price'] ?? null) ? (float) $offering['price'] : 0.0;
    $rawPriceMin = is_numeric($offering['price_min'] ?? null) ? (float) $offering['price_min'] : $rawPrice;
    $rawPriceMax = is_numeric($offering['price_max'] ?? null) ? (float) $offering['price_max'] : $rawPrice;
    $pricing = app(\App\Services\MarketplacePricingService::class);
    $pricingVendor = [
        'vendor_name' => $offering['vendor_name'] ?? data_get($offering, 'vendor.name', data_get($offering, 'vendor_details.name', '')),
        'user' => ['name' => data_get($offering, 'vendor.user.name', ''), 'email' => data_get($offering, 'vendor.user.email', '')],
    ];
    $price = $pricing->buyerPrice($rawPrice, $pricingVendor, (int) ($offering['vendor_id'] ?? 0), ! $isStoreProduct);
    $priceMin = $pricing->buyerPrice($rawPriceMin, $pricingVendor, (int) ($offering['vendor_id'] ?? 0), ! $isStoreProduct);
    $priceMax = $pricing->buyerPrice($rawPriceMax, $pricingVendor, (int) ($offering['vendor_id'] ?? 0), ! $isStoreProduct);
    $rating = is_numeric($offering['rating'] ?? null) ? (float) $offering['rating'] : null;
    $reviewCount = is_numeric($offering['review_count'] ?? null) ? (int) $offering['review_count'] : 0;
    if ($reviewCount > 0 && (! is_numeric($rating) || (float) $rating <= 0)) {
        $rating = 5.0;
    }
    $filledStars = $reviewCount > 0 ? max(0, min(5, (int) round((float) $rating))) : 0;
    $reviewSummary = $reviewCount > 0
        ? number_format((float) $rating, 1) . ' · ' . $reviewCount . ' review' . ($reviewCount === 1 ? '' : 's')
        : 'Be the first to review';
    $mobileTicketText = ($rating !== null && $rating > 0 && $reviewCount > 0)
        ? ('Rated ' . number_format($rating, 1) . ' · ' . $reviewCount . ' review' . ($reviewCount === 1 ? '' : 's'))
        : 'Begin your journey';
    $typeLabelRaw = trim((string) data_get($offering, 'type', 'Therapy'));
    $typeLabelKey = Str::slug($typeLabelRaw);
    $typeLabelMap = [
        'therapy' => 'Therapy',
        'therapies' => 'Therapy',
        'class' => 'Class',
        'classes' => 'Class',
        'retreat' => 'Retreat',
        'retreats' => 'Retreat',
        'event' => 'Event',
        'events' => 'Event',
        'workshop' => 'Workshop',
        'workshops' => 'Workshop',
    ];
    $typeLabel = $typeLabelMap[$typeLabelKey] ?? Str::of($typeLabelRaw)->singular()->headline()->toString();
    if ($typeLabel === '') {
        $typeLabel = 'Therapy';
    }
    $durationRaw = $offering['duration'] ?? null;
    $durationMinutes = 60;
    $categoryLabel = trim((string) data_get($offering, 'category.name', ''));
    if ($categoryLabel === '') {
        $categoryLabel = trim((string) data_get($offering, 'category.slug', ''));
    }
    if ($categoryLabel !== '') {
        $categoryLabel = Str::of($categoryLabel)->headline()->toString();
    }
    if (is_numeric($durationRaw)) {
        $durationMinutes = max(15, (int) $durationRaw);
    } elseif (is_string($durationRaw) && preg_match('/(\d+(?:\.\d+)?)/', $durationRaw, $durationMatch)) {
        $durationMinutes = max(15, (int) round((float) $durationMatch[1]));
    }
    $durationTone = static function (int $minutes): string {
        $roundedHours = round(($minutes / 60) * 2) / 2;

        if ($roundedHours > 3) {
            return '#D94B3D';
        }

        if ($roundedHours > 1.5) {
            return '#F0B429';
        }

        return '#6AA8FD';
    };
    $durationCompactLabelFormatter = static function (int $minutes): string {
        if ($minutes < 100) {
            return $minutes . ' MINS';
        }

        $hours = round(($minutes / 60) * 2) / 2;
        $hoursLabel = rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');

        return $hoursLabel . ' HOUR';
    };
    $durationLabelFormatter = static function (int $minutes): string {
        if ($minutes < 100) {
            return $minutes . ' Minutes';
        }

        $hours = round(($minutes / 60) * 2) / 2;
        $hoursLabel = rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');

        return $hoursLabel . ' Hours';
    };
    $durationLabel = $durationLabelFormatter($durationMinutes);
    $durationCompactLabel = $durationCompactLabelFormatter($durationMinutes);
    $renderDurationIconSvg = static function (int $minutes) use ($durationCompactLabelFormatter, $durationTone): string {
        $label = $durationCompactLabelFormatter($minutes);
        [$primary, $secondary] = array_pad(preg_split('/\s+/', $label, 2) ?: [], 2, '');
        $accent = $durationTone($minutes);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 505.4 521" role="img" aria-hidden="true" focusable="false" style="--timer-accent: {$accent};">
  <style>
    .timer-accent { fill: var(--timer-accent, #69a6fa); }
    .timer-ink { fill: #040506; }
    .time-count,
    .time-label {
      font-family: VarelaRound-Regular, 'Varela Round', sans-serif;
      fill: #040506;
      text-anchor: middle;
      dominant-baseline: middle;
      alignment-baseline: middle;
      paint-order: stroke fill;
      stroke: #ffffff;
      stroke-width: 2px;
    }
    .time-count {
      font-size: 160px;
      font-weight: 400;
    }
    .time-label {
      font-size: 92px;
      font-weight: 700;
      letter-spacing: 4px;
    }
  </style>
  <g>
    <g>
      <path class="timer-ink" d="M229.24,474.13c-31.12-5.29-61.37-15.41-88.11-33.13-40.48-26.83-70.64-65.62-84.69-112.34-13.42-44.65-12.13-92.91,3.78-136.65,15.06-41.42,43.48-75.73,81.32-97.61,5.21-3.02,11.5-1.73,14.19,4.05l6.22,13.38,28.29-64.58-61.61-16.83,7.19,18.7c3.93,6.92.61,13.19-5.7,16.75-16.46,9.3-31.35,20.73-44.9,33.96-44.29,43.24-66.85,102.4-67.36,164.06-.48,57.83,18.22,113.43,56.14,157.29,39.03,45.14,87.31,71.91,146.2,81.5l4.23,2.46c1.2,3.78,1.99,8.99-.38,12-3.61,4.57-7.4,3.87-12.27,3.06-45.54-7.61-86.63-27.41-121.63-57.21C34.9,415.93,1.55,347.77.07,274.93c-.67-32.85,3.19-64.26,14.03-95.09,18.89-53.73,54.73-99.03,103.59-128.38l-9.6-24.72c-1.53-3.95.42-9.98,3.02-13.14,1.94-2.35,6.96-5.66,10.89-4.59l76.96,20.95c3.99,1.09,7.73,2.9,9.43,5.55,2.21,3.45,3.06,8.31,1.43,12.03l-36.95,83.9c-2.05,4.67-4.49,8.04-9.39,8.57-3.91.43-9.2-1.33-10.91-5.41l-8.57-20.39c-76.88,47.79-98.37,155.77-60.62,234.58,22.95,47.91,65.08,83.33,115.65,99.15,12.06,3.77,23.66,5.62,35.54,8.47,4.89,1.17,6.5,8.22,5.42,11.53-1.59,4.9-5.98,6.97-10.73,6.17Z"/>
      <path class="timer-accent" d="M229.24,474.13l-3,23.78c-.35,2.56-1.05,5.44-1.82,7.23l-4.23-2.46c-58.89-9.59-107.17-36.36-146.2-81.5-37.92-43.86-56.62-99.46-56.14-157.29.52-61.66,23.07-120.82,67.36-164.06,13.55-13.23,28.43-24.66,44.9-33.96,6.31-3.56,9.63-9.83,5.7-16.75l-7.19-18.7,61.61,16.83-28.29,64.58-6.22-13.38c-2.69-5.79-8.97-7.07-14.19-4.05-37.83,21.88-66.26,56.19-81.32,97.61-15.91,43.75-17.2,92-3.78,136.65,14.05,46.72,44.21,85.52,84.69,112.34,26.74,17.72,56.99,27.84,88.11,33.13Z"/>
    </g>
    <g>
      <path class="timer-ink" d="M285.74,506.04l5.54-3.3c85.41-12.6,154.15-75.52,182.75-156.09,25.72-72.43,15.07-155.43-29.26-218.2-6.84-9.12-13.24-16.94-21.54-25.39l-20.75,21.1c50.4,51.57,66.3,126.83,48.96,195.83-4.18,16.65-10.54,31.71-18.42,46.61-30.99,58.65-87.61,98.27-152.72,107.65-4.98.72-9.89-1.6-11.01-7.05-.69-3.35,1.24-10.3,6.36-10.98,68.05-9.06,125.58-54.23,150.88-118.1,9.71-24.52,14.61-49.57,13.8-76.28-1.53-50.37-18.33-93.55-54.32-128.75-4.6-4.5-5.69-12.68-1.04-17.41l27.26-27.73c3.67-3.74,8.16-5.71,13.35-5.18,4.27.44,7.28,3.65,10.57,7.21,15.69,16.93,29.06,34.68,39.65,55.35,10.81,21.09,19.02,42.54,23.82,66.1,7.58,37.17,8.01,75.42-.51,112.57-22.65,98.67-101.59,180.23-202.83,196.86-4.51.74-8.07-1.56-10.16-4.49-2.3-3.22.12-7.51-.37-10.33Z"/>
      <path class="timer-accent" d="M285.74,506.04l-5.44-31.79c65.11-9.39,121.73-49,152.72-107.65,7.87-14.9,14.23-29.96,18.42-46.61,17.34-69,1.44-144.27-48.96-195.83l20.75-21.1c8.3,8.44,14.7,16.26,21.54,25.39,44.32,62.78,54.98,145.77,29.26,218.2-28.61,80.57-97.35,143.49-182.75,156.09l-5.54,3.3Z"/>
    </g>
    <path class="timer-ink" d="M255.72,518.2c-8.5.13-13.59-6.97-14.01-14.01-.44-7.39,4.4-14.43,12.66-15.21s14.49,6.01,14.97,13.45-4.65,15.63-13.63,15.76Z"/>
    <g>
      <g>
        <path class="timer-ink" d="M376.16,102.36c-3.58,5.6-10.51,10.58-16.75,6.15-12.82-9.11-26.22-16.97-40.64-23.12-7.06-3.01-7.67-10.71-5.03-16.92l17.83-41.96c4.48-7.64,11.9-8.44,19.3-5.3,16.59,7.03,32.12,15.26,45.23,27.71,6.23,5.92,5.26,14.04.94,20.8l-20.88,32.64ZM363.22,89.28l18.38-28.74c-10.8-9.01-22.98-16.29-36.2-21.27l-12.89,32.28c11.39,5.23,20.94,10.71,30.7,17.72Z"/>
        <path class="timer-accent" d="M363.22,89.28c-9.76-7.02-19.32-12.5-30.7-17.72l12.89-32.28c13.21,4.98,25.4,12.26,36.2,21.27l-18.38,28.74Z"/>
      </g>
      <g>
        <path class="timer-ink" d="M298.33,64.96c-4.43,21.03-14.76,8.33-50.07,10.16-7.36.38-10.3-6.07-10.77-12.24l-3.53-45.99c-.54-7.08,3.96-13.07,10.65-14.62,16.55-3.84,32.65-2.36,49.18.98,4.51.91,8.62,2.22,11.15,5.48,2.67,3.44,3.56,7.92,2.62,12.36l-9.24,43.87ZM281.51,57.52l7.57-36.01c-12.59-2.49-24.13-3.11-36.84-1.22l2.88,35.53c9.26.05,17.63.63,26.39,1.71Z"/>
        <path class="timer-accent" d="M281.51,57.52c-8.76-1.08-17.13-1.66-26.39-1.71l-2.88-35.53c12.71-1.88,24.25-1.27,36.84,1.22l-7.57,36.01Z"/>
      </g>
    </g>
  </g>
  <g id="timer-text" aria-label="{$label}">
    <text id="timer-number" class="time-count timer-ink" x="50%" y="45%">{$primary}</text>
    <text id="timer-unit" class="time-label timer-ink" x="50%" y="65%">{$secondary}</text>
  </g>
</svg>
SVG;
    };
    $galleryImages = is_array($offering['images'] ?? null)
        ? array_values(array_unique(array_filter($offering['images'])))
        : [];
    $primaryImage = trim((string) ($offering['image'] ?? ''));
    if ($primaryImage !== '' && ! in_array($primaryImage, $galleryImages, true)) {
        array_unshift($galleryImages, $primaryImage);
    }

    $heroImages = $galleryImages;
    if (empty($heroImages)) {
        $heroImages = [asset('images/default-social-preview.jpg')];
    }
    $heroImages = array_slice($heroImages, 0, 3);
    $galleryImages = array_slice($galleryImages, 0, 3);

    $practitioner = is_array($offering['practitioner'] ?? null) ? $offering['practitioner'] : [];
    $practitionerName = trim((string) ($practitioner['name'] ?? ''));
    if ($practitionerName === '') {
        $practitionerName = 'Practitioner';
    }
    $practitionerBio = trim((string) ($practitioner['bio'] ?? ''));
    $practitionerLocation = trim((string) ($practitioner['location'] ?? ''));
    $practitionerCredentialsRaw = $practitioner['credentials'] ?? '';
    if (is_array($practitionerCredentialsRaw)) {
        $practitionerCredentialsRaw = array_values(array_filter(array_map(
            static fn ($credential): string => is_array($credential)
                ? trim((string) ($credential['title'] ?? $credential['name'] ?? $credential['label'] ?? ''))
                : trim((string) $credential),
            $practitionerCredentialsRaw
        )));
    }
    $practitionerCredentials = is_array($practitionerCredentialsRaw)
        ? implode(', ', $practitionerCredentialsRaw)
        : trim((string) $practitionerCredentialsRaw);
    $practitionerProfileUrl = trim((string) ($practitioner['profile_url'] ?? ''));
    $practitionerSpecialties = is_array($practitioner['specialties'] ?? null) ? array_values(array_filter(array_map('trim', $practitioner['specialties']))) : [];
    $splitList = static function (string $text): array {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return [];
        }
        $parts = preg_split('/[\n,;|]+/', str_replace('•', ',', $text)) ?: [];
        return array_values(array_filter(array_map('trim', $parts)));
    };
    $credentialTags = $splitList($practitionerCredentials);
    if (empty($credentialTags)) {
        $credentialTags = array_slice($practitionerSpecialties, 0, 3);
    }

    $normalizeCountryShort = static function (string $country): string {
        $country = trim($country);
        if ($country === '') {
            return 'UK';
        }

        $slug = Str::slug($country);
        if (in_array($slug, ['uk', 'u-k', 'gb', 'great-britain', 'united-kingdom', 'england', 'scotland', 'wales', 'northern-ireland'], true)) {
            return 'UK';
        }

        return Str::of($country)->headline()->toString();
    };

    $normalizeLocationKey = static function (string $value) use ($normalizeCountryShort): string {
        $value = trim(Str::of($value)->replaceMatches('/\s+/', ' ')->toString());
        if ($value === '') {
            return '';
        }

        $value = preg_split('/\s*,\s*/', $value) ?: [$value];
        $value = trim((string) ($value[0] ?? ''));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\b(?:uk|u\.k\.|gb|great britain|united kingdom|england|scotland|wales|northern ireland)\b/i', '', $value) ?? $value;
        $value = preg_replace('/\b[A-Z]{1,2}\d[\dA-Z]?\s*\d[A-Z]{2}\b/i', '', $value) ?? $value;
        $value = preg_replace('/\b[A-Z]{1,2}\d[\dA-Z]?\b/i', '', $value) ?? $value;
        $value = Str::of($value)->replaceMatches('/[^a-z0-9]+/i', ' ')->replaceMatches('/\s+/', ' ')->trim()->lower()->toString();

        return $value;
    };

    $venueLocationsRaw = is_array($offering['venue_locations'] ?? null) ? $offering['venue_locations'] : [];
    $simpleLocationsRaw = is_array($offering['locations'] ?? null) ? $offering['locations'] : [];
    $hasStructuredVenueLocations = ! empty($venueLocationsRaw);
    $locations = [];

    foreach ($venueLocationsRaw as $index => $row) {
        if (! is_array($row)) {
            continue;
        }

        $label = trim((string) ($row['label'] ?? ''));
        $isOnlineVenue = ! empty($row['online']) || str_contains(strtolower($label), 'online');
        if ($isOnlineVenue) {
            $locations[] = [
                'id' => 'loc-online',
                'label' => 'Online session',
                'address' => 'Live session link sent after booking',
                'notes' => 'Online appointment',
                'lat' => null,
                'lng' => null,
                'online' => true,
                'location_key' => 'online',
                'variant_ids' => [],
            ];
            continue;
        }
        $streetAddressLines = array_values(array_filter([
            trim((string) ($row['address_line_1'] ?? '')),
            trim((string) ($row['address_line_2'] ?? '')),
        ]));
        $streetAddress = trim(implode(', ', $streetAddressLines));
        $compactAddress = trim(implode(', ', array_filter([
            trim((string) ($row['city'] ?? '')),
            trim((string) ($row['county'] ?? '')),
            trim((string) ($row['postcode'] ?? '')),
        ])));
        $country = $normalizeCountryShort((string) ($row['country'] ?? ''));
        $fullAddress = trim(implode(', ', array_filter([
            $streetAddress,
            trim((string) ($row['city'] ?? '')),
            trim((string) ($row['county'] ?? '')),
            trim((string) ($row['postcode'] ?? '')),
            $country,
        ])));
        $lat = is_numeric($row['lat'] ?? null) ? (float) $row['lat'] : null;
        $lng = is_numeric($row['lng'] ?? null) ? (float) $row['lng'] : null;
        $notes = trim((string) ($row['notes'] ?? ''));
        $displayLabel = $label !== ''
            ? $label
            : trim(implode(', ', array_filter([
                trim((string) ($row['city'] ?? '')),
                trim((string) ($row['county'] ?? '')),
                $country,
            ])));
        $displayLabel = $displayLabel !== ''
            ? $displayLabel
            : ($streetAddress !== '' ? $streetAddress : ('Location ' . ($index + 1)));

        $locations[] = [
            'id' => 'loc-venue-' . ($index + 1),
            'label' => $displayLabel,
            'address' => $notes !== '' ? $notes : ($fullAddress !== '' ? $fullAddress : 'Available'),
            'street_address' => $streetAddress,
            'full_address' => $fullAddress !== '' ? $fullAddress : $streetAddress,
            'notes' => $notes,
            'lat' => $lat,
            'lng' => $lng,
            'online' => false,
            'location_key' => $normalizeLocationKey($displayLabel !== '' ? $displayLabel : $label),
            'variant_ids' => [],
        ];
    }

    foreach ($simpleLocationsRaw as $index => $locationLabel) {
        $label = trim((string) $locationLabel);
        if ($label === '') {
            continue;
        }

        $lower = strtolower($label);
        if ($lower === 'online' || str_contains($lower, 'online')) {
            $locations[] = [
                'id' => 'loc-online',
                'label' => 'Online session',
                'address' => 'Live session link sent after booking',
                'notes' => 'Online appointment',
                'lat' => null,
                'lng' => null,
                'online' => true,
            ];
            continue;
        }

        if ($hasStructuredVenueLocations) {
            continue;
        }

        if (! collect($locations)->contains(fn ($location) => strtolower((string) ($location['label'] ?? '')) === strtolower($label))) {
            $locations[] = [
                'id' => 'loc-simple-' . ($index + 1),
                'label' => $label,
                'address' => '',
                'notes' => '',
                'lat' => null,
                'lng' => null,
                'online' => false,
            ];
        }
    }

    if (empty($locations)) {
        $locations[] = [
            'id' => 'loc-online',
            'label' => 'Location to be confirmed',
            'address' => 'Your practitioner will confirm the location after booking.',
            'notes' => 'Location to be confirmed',
            'lat' => null,
            'lng' => null,
            'online' => true,
        ];
    }

    $knownLocationKeys = [];
    foreach ($locations as $location) {
        foreach ([
            (string) ($location['label'] ?? ''),
            (string) ($location['address'] ?? ''),
            (string) ($location['notes'] ?? ''),
        ] as $candidate) {
            $candidateKey = $normalizeLocationKey($candidate);
            if ($candidateKey !== '') {
                $knownLocationKeys[$candidateKey] = true;
            }
        }

        if (! empty($location['online'])) {
            $knownLocationKeys['online'] = true;
            $knownLocationKeys['exclusively online'] = true;
            $knownLocationKeys['online session'] = true;
        }
    }

    $physicalLocations = array_values(array_filter($locations, fn ($location) => empty($location['online'])));
    $hasOnlineLocation = collect($locations)->contains(fn ($location) => ! empty($location['online']));

    $variantSelections = collect(is_array($offering['variants'] ?? null) ? $offering['variants'] : [])->flatMap(static function (array $variant): array {
        return array_map(static fn ($value): string => strtolower(trim((string) $value)), (array) ($variant['selection'] ?? []));
    });
    $hasInPersonVariant = $variantSelections->contains(static fn (string $value): bool => str_contains($value, 'in-person') || str_contains($value, 'in person'));
    if (empty($physicalLocations) && $hasInPersonVariant && is_array($practitioner['locations'] ?? null)) {
        $fallbackLocations = [];
        foreach ($practitioner['locations'] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $address = trim((string) ($row['formatted_address'] ?? $row['address'] ?? ''));
            $combined = trim(implode(', ', array_filter([$label, $address])));
            $normalized = strtolower(preg_replace('/\s+/', ' ', $combined) ?? $combined);
            $lat = is_numeric($row['lat'] ?? null) ? (float) $row['lat'] : null;
            $lng = is_numeric($row['lng'] ?? null) ? (float) $row['lng'] : null;

            if (
                $combined === ''
                || str_contains($normalized, 'online')
                || str_contains($normalized, 'null')
                || preg_match('/^(uk|u\.k\.|gb|great britain|united kingdom|england|scotland|wales|northern ireland)$/i', $label)
                || ($lat === 0.0 && $lng === 0.0)
            ) {
                continue;
            }

            $fallbackLocations[] = [
                'id' => 'loc-practitioner-' . ($index + 1),
                'label' => $label !== '' ? $label : $address,
                'address' => $address !== '' ? $address : $label,
                'street_address' => '',
                'full_address' => $address !== '' ? $address : $label,
                'notes' => '',
                'lat' => $lat,
                'lng' => $lng,
                'online' => false,
                'location_key' => $normalizeLocationKey($label !== '' ? $label : $address),
                'variant_ids' => [],
            ];
        }

        $physicalLocations = collect($fallbackLocations)
            ->unique(static fn (array $location): string => strtolower(trim((string) ($location['label'] ?? '') . '|' . ($location['address'] ?? ''))))
            ->values()
            ->all();
        $locations = array_merge($physicalLocations, $locations);
    }

    $onlineOnlyLocation = $hasOnlineLocation && count($physicalLocations) === 0;
    if ($onlineOnlyLocation) {
        $locations = array_map(static function (array $location): array {
            if (! empty($location['online'])) {
                $location['label'] = 'Online session';
                $location['address'] = 'Live session link sent after booking';
                $location['notes'] = 'Online appointment';
            }

            return $location;
        }, $locations);
    }
    $selectedLocationId = $physicalLocations[0]['id'] ?? ($locations[0]['id'] ?? 'loc-online');
    $selectedLocationLabel = $locations[0]['label'] ?? 'Location';
    $selectedLocationAddress = $locations[0]['address'] ?? '';
    foreach ($locations as $location) {
        if (($location['id'] ?? '') === $selectedLocationId) {
            $selectedLocationLabel = $location['label'] ?? $selectedLocationLabel;
            $selectedLocationAddress = $location['address'] ?? $selectedLocationAddress;
            break;
        }
    }

    $variantFormatValues = collect(is_array($offering['variants'] ?? null) ? $offering['variants'] : [])->flatMap(static function (array $variant): array {
        return array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            (array) ($variant['selection'] ?? [])
        );
    });
    $hasOnlineVariant = $variantFormatValues->contains(static fn (string $value): bool => str_contains($value, 'online'));
    $hasInPersonVariant = $variantFormatValues->contains(static fn (string $value): bool => str_contains($value, 'in-person') || str_contains($value, 'in person'));
    $hasOnlineAvailability = $hasOnlineLocation || $hasOnlineVariant || str_contains(strtolower($mode), 'online');
    $hasInPersonAvailability = count($physicalLocations) > 0
        || $hasInPersonVariant
        || str_contains(strtolower($mode), 'person');

    if ($hasOnlineAvailability && $hasInPersonAvailability) {
        $formatLabel = 'Online + In-person';
    } elseif ($hasOnlineAvailability) {
        $formatLabel = 'Exclusively online';
    } else {
        $formatLabel = 'In-person';
    }

    $locationSummary = count($physicalLocations) > 1
        ? (count($physicalLocations) . ' locations')
        : (count($physicalLocations) === 1 ? $physicalLocations[0]['label'] : ($hasOnlineLocation ? 'Exclusively online' : 'Location to be confirmed'));
    if ($hasOnlineLocation && count($physicalLocations) > 0) {
        $locationSummary = count($physicalLocations) . ' locations + online';
    } elseif ($onlineOnlyLocation) {
        $locationSummary = 'Online session';
    }
    $locationHeading = $onlineOnlyLocation ? 'Exclusively online.' : 'Choose the studio that works for you.';
    $locationIntro = $onlineOnlyLocation
        ? 'Your sessions are delivered online only. The joining link is sent after booking.'
        : 'Pick your preferred location during checkout or switch it from the booking panel.';
    $onlineOverlayTitle = $onlineOnlyLocation ? 'Exclusively online' : 'Online session selected';
    $onlineOverlayCopy = $onlineOnlyLocation
        ? 'No venue map is needed. Your joining link is sent after booking.'
        : 'No venue map needed. Your joining link is sent after booking.';
    $videoSource = 'https://www.pexels.com/download/video/4359824/';
    $videoPoster = $onlineOnlyLocation
        ? 'https://studio.weofferwellness.co.uk/storage/uploads/images/93131c74-12f2-4eaa-a2cb-747f6cc70fc4.jpg'
        : ($heroImages[0] ?? 'https://studio.weofferwellness.co.uk/storage/uploads/images/93131c74-12f2-4eaa-a2cb-747f6cc70fc4.jpg');

    $bookingSummaryLabel = $bookingFlow === 'live' ? 'Live availability' : 'Flexible booking';
    $priceSummary = $priceMin !== $priceMax ? ('From £' . number_format($priceMin, 2)) : ('£' . number_format($price, 2));
    $formatOnlineIconSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720.73 720.73" role="img" aria-hidden="true" focusable="false">
  <defs>
    <style>
      .cls-1 { fill: #000000; }
      .cls-2 { fill: #a7cb63; }
      .cls-3 { fill: #6baafa; }
    </style>
  </defs>
  <path class="cls-1" d="M720.73,360.37c0,199.03-161.34,360.37-360.37,360.37S0,559.39,0,360.37,161.34,0,360.37,0s360.37,161.34,360.37,360.37ZM263.58,123.78c27.9,8.64,54.54,12.32,82.31,13.46V30.78c-22.41,5.46-41.8,24.76-54.89,43.29-10.9,15.43-20.02,31.63-27.43,49.71ZM398.76,43.11c-7.57-5.28-14.9-9.6-24.52-12.16l.06,106.42c28.55-1.03,55.12-4.46,82.47-12.3-13.51-31.21-31.96-61.16-58.01-81.96ZM237.09,114.39c12.33-27.67,26.41-51.56,45.56-74.84-15.52,2.12-28.77,7.53-42.81,12.31-20.02,8.46-38.75,18.05-58.12,30.16,16.28,14.41,34.78,24.69,55.37,32.37ZM492.04,112.51c17.44-7.81,33.79-16.75,47.5-30.44-31.99-20.48-65.85-35.43-102.94-42.78,20.01,23.54,34.23,48.41,46.16,75.81,3.35-.26,6.04-1.14,9.28-2.59ZM156.8,251.12c9.45,2.97,15.35,13.69,24.04,10.82,15.44-5.1,7.88-46.83,36.09-55.05l19.31-5.62c8.04-2.34,14.83-6.57,19.56-13.46,6.81-9.91,9.87-21.61,7.65-33.86-39.27-10.77-76.32-26.11-106.34-54.94-43.22,34.24-75.92,77.82-97.95,127.92,10.63,19.41,19.19,38.82,25.99,59.59,3.4,10.37,9.67,18.31,16.7,26.5,12.67,12.99,25.55,24.64,39.29,36.77l9.49-.29c-4.48-12.47-10.42-21.74-18.23-31.13l-13.19-15.86c-7.01-8.42-6.54-23.94-4.46-35.85,2.89-16.52,27.11-20.24,42.04-15.55ZM521.81,273.48l-12.55-6.99c-5.91-3.29-12.74-4.83-19.43-2.59l-39.57,13.26c-30.58,10.25-38.3,44.3-26.73,72.8l208.69-.03c3.41-9.86,1.58-18.92-1.17-28.19l-7.08-23.85c-1.64-5.52-.39-11.21,3.32-15.82,8.85-11.02,35.02-6.69,51.72-3.28-17.64-70.22-57.99-134.24-115.17-179.27-20.13,19.66-44.01,33.13-70.49,43.37l5.71,22.9c2.5,9-2.07,17.66-11.01,20.34l-24.53,7.35c-3.42,1.02-5.08,4.04-5.37,7.44l-1.17,14.05c-.77,9.18-14.7,13.72-17.97,20.83-1.97,4.29.6,7.8,4.18,8.65,24.35,5.77,40.88-14.17,65.94-6.64,10.19,3.06,19.92,8.14,28.18,14.52,5.73,4.43,5.63,13.01,2.14,17.69-4.14,5.55-11.19,7.07-17.64,3.47ZM427.19,210.2c3.02-2.3,4.36-5.29,4.2-9.18-.61-14.9,7.47-27.78,21.46-32.23l17.17-5.45-3.95-10.88c-30.52,8.45-60.57,12.74-91.83,13.77l.03,183.64,20.54.08c-8.79-32.3-1.61-66.97,25.98-87.07-15.72-16.48-11.67-38.91,6.4-52.68ZM141.44,277.91c-3.04,4.2.56,10.93,3.66,13.58l8.77,10.52c11.95,13.85,20.37,29.53,25.41,47.92h166.61s0-183.66,0-183.66c-18.3-1.14-36.34-2.95-55.16-5.87.38,18.07-5.22,34.73-16.45,47.79-20.2,23.49-40.87,18.7-49.75,25.48-6.86,5.23-5.91,46.07-36.84,54.13-13.03,3.39-25.19.37-34.98-7.77-2.6-2.16-8.52-5.92-11.28-2.11ZM84.7,333.59c-12.54-12.38-21.33-26.42-26.75-42.75-3.28-9.89-6.89-19.3-11.53-28.46-9.33,29.21-14.59,57.52-15.83,87.54h70.64s-16.54-16.34-16.54-16.34ZM690.02,349.96c-.61-14.86-2.08-28.58-4.84-42.72l-32.12-5.21,6.59,22.38c1.34,8.53,1.66,16.2.51,25.42l29.85.13ZM150.81,507.53c-.71-5.16-3.01-9.84-6.46-12.99l-14.15-12.92c-13.04-12.64-20.2-29.07-20.75-47.33-.92-20.34,5.05-39.76,18.66-55.59l-97.71-.02c4.07,96.78,48.03,181.09,123.13,240.5,2.14.94,4.1.44,5.55-1.02,6.75-6.79,13.57-12.31,21.98-18.52-13.08-16.76-22.47-35.57-25.35-56.49l-4.91-35.61ZM277.92,535.37c-10.32,7.45-16.5,17.24-17.21,29.54,28.03-7.29,55.39-10.62,85.15-11.7l.05-174.56-119.18.05,21.02,21.38c3.86,4.89,8.9,7.76,14.93,9.14,8.8,2.02,17.24,4.19,25.61,7.21,17.66,6.36,28.38,21.58,28.87,40.65.8,31.04-14.36,59.2-39.23,78.29ZM491.61,433.41c.08-6.47-.83-12.14-2.52-17.55l-21.48-2.65c-23.79-2.93-44.6-15-58.68-34.55l-34.71.11-.02,174.3c36.19,1.48,71.01,6.62,105.59,17.83,6.68-14.91,11.8-29,15.81-43.77-3.09-9.84-4.94-19.16-4.81-29.28l.81-64.43ZM533.52,524.9c20.22-3.65,39.82-27.74,49.8-45.56,5.66-10.12,8.65-20.77,9.59-32.44.83-11.62,2.55-22.84,7.8-33.24l17.66-34.99h-169.94c8.32,5.39,17.82,7.07,27.44,8.38l19.27,2.62c18.74,2.55,23.73,24.51,23.45,43.25l-.9,59.98c-.12,7.82.69,15.51,2.18,22.9,1.28,6.32,6.45,10.41,13.66,9.11ZM517.09,549.74l-11.82,31.57c22.14,9.78,41.79,22.55,59.54,39.92,43.23-33.48,76.71-75.84,98.56-124.82,16.27-37.36,24.91-76.54,26.81-117.7h-41.69s-19.93,38.87-19.93,38.87c-5.06,9.94-8.04,20.09-8.87,31.34-.99,13.38-4.11,25.83-10.03,37.87-9.08,18.44-21.67,34.26-37.29,47.55s-34.34,20.77-55.29,15.41ZM235.87,550.28c2.13-10.97,8.09-20.18,16.05-27.33l14.8-13.28c14.69-13.18,23.71-31.86,23.43-51.62l22.75-6.07c9.84-2.63,17.42-7.37,24.55-14.59l21.77-22.05c16.19-16.4,50.02-18.15,63.29,1.93,12.3,18.6,12.91,46.56-3.56,61.72l-14.55,13.4c-8.29,7.64-12.76,17.3-14.39,28.61l-5.29,36.76c-2.45,16.99-10.68,32.84-22.2,44.87l-26-11.29-4.68-24.15ZM345.87,690.2l.03-108.24c-28.83.95-56.68,4.8-84.17,12.53,7.99,18.1,16.68,34.39,27.65,49.91,13.64,19.3,33.28,39.92,56.49,45.8ZM433.42,650.88c13.47-16.61,24.51-33.63,34.2-53.55-30.54-9.82-61.5-14.24-93.46-15.37l.09,109.2c24.08-4.4,43.81-22.59,59.18-40.28ZM285.18,682.27c-21.16-23.05-35.88-49.17-49.6-78.62-20.3,7.53-38.81,18.27-54.33,32.68-.46,1.58,1.63,4.04,3.29,5.06,31.1,19.26,64.51,33.5,99.77,41.69l.87-.82ZM536.79,640.92c1.01-.63,2.48-1.76,2.24-2.53l-.85-2.78c-13.53-11.87-28.42-21.24-45.31-28.37-13.17,27.82-29.81,52.25-50.59,74.34,34.39-8.54,65.3-22.47,94.51-40.66ZM285.78,682.74l-.58.99.58-.99Z"/>
  <path class="cls-3" d="M491.61,433.41l-.81,64.43c-.13,10.12,1.72,19.44,4.81,29.28-4.01,14.78-9.13,28.86-15.81,43.77-34.58-11.21-69.4-16.35-105.59-17.83l.02-174.3,34.71-.11c14.08,19.55,34.89,31.62,58.68,34.55l21.48,2.65c1.69,5.42,2.6,11.08,2.52,17.55Z"/>
  <path class="cls-2" d="M236.87,550.28l-4.68,24.15-26,11.29c-11.51-12.03-19.75-27.87-22.2-44.87l-5.29-36.76c-1.63-11.31-6.09-20.97-14.39-28.61l-14.55-13.4c-16.46-15.16-15.85-43.12-3.56-61.72,13.27-20.08,47.1-18.32,63.29-1.93l21.77,22.05c7.13,7.22,14.71,11.96,24.55,14.59l22.75,6.07c7.84,2.09,12.47,8.47,12.59,16.92.28,19.76-8.74,38.43-23.43,51.62l-14.8,13.28c-7.96,7.14-13.92,16.35-16.05,27.33Z"/>
  <path class="cls-3" d="M150.81,507.53l4.91,35.61c2.88,20.92,12.26,39.73,25.35,56.49-8.41,6.21-15.23,11.73-21.98,18.52-1.45,1.46-3.41,1.96-5.55,1.02-75.1-59.41-119.06-143.72-123.13-240.5l97.71.02c-13.6,15.83-19.58,35.25-18.66,55.59.54,18.26,7.71,34.69,20.75,47.33l14.15,12.92c3.45,3.15,5.75,7.83,6.46,12.99Z"/>
  <path class="cls-3" d="M517.09,549.74c20.95,5.36,39.13-1.66,55.29-15.41s28.21-29.11,37.29-47.55c5.92-12.03,9.05-24.48,10.03-37.87.83-11.25,3.81-21.4,8.87-31.34l19.93-38.86h41.69c-1.9,41.15-10.54,80.33-26.81,117.69-21.86,48.99-55.34,91.34-98.56,124.82-17.75-17.37-37.4-30.14-59.54-39.92l11.82-31.57Z"/>
  <g>
    <path class="cls-2" d="M521.81,273.48c6.45,3.59,13.51,2.07,17.64-3.47,3.49-4.68,3.59-13.26-2.14-17.69-8.26-6.38-17.98-11.45-28.18-14.52-25.06-7.53-41.59,12.41-65.94,6.64-3.59-.85-6.15-4.37-4.18-8.65,3.27-7.11,17.2-11.65,17.97-20.83l1.17-14.05c.28-3.4,1.95-6.41,5.37-7.44l24.53-7.35c8.94-2.68,13.51-11.33,11.01-20.34l-5.71-22.9c26.48-10.23,50.36-23.7,70.49-43.37,57.18,45.03,97.53,109.05,115.17,179.27-16.71-3.41-42.87-7.74-51.72,3.28-3.71,4.62-4.96,10.3-3.32,15.82l7.08,23.85c2.75,9.26,4.58,18.33,1.17,28.19l-208.69.03c-11.57-28.5-3.86-62.55,26.73-72.8l39.57-13.26c6.69-2.24,13.52-.71,19.43,2.59l12.55,6.99Z"/>
    <path class="cls-2" d="M533.52,524.9c-7.21,1.3-12.38-2.78-13.66-9.11-1.49-7.39-2.3-15.08-2.18-22.9l.9-59.98c.28-18.75-4.71-40.7-23.45-43.25l-19.27-2.62c-9.61-1.31-19.12-2.99-27.44-8.37h169.94s-17.66,34.99-17.66,34.99c-5.25,10.4-6.97,21.62-7.8,33.24-.94,11.67-3.93,22.32-9.59,32.44-9.98,17.83-29.58,41.91-49.8,45.56Z"/>
  </g>
  <path class="cls-3" d="M277.92,535.37c24.87-19.1,40.02-47.25,39.23-78.29-.49-19.07-11.2-34.29-28.87-40.65-8.37-3.01-16.81-5.19-25.61-7.21-6.03-1.38-11.07-4.25-14.93-9.14l-21.02-21.38,119.18-.05-.05,174.56c-29.76,1.08-57.12,4.41-85.15,11.7.71-12.3,6.89-22.09,17.21-29.54Z"/>
  <path class="cls-3" d="M433.42,650.88c-15.36,17.69-35.1,35.87-59.18,40.28l-.09-109.2c31.96,1.13,62.92,5.55,93.46,15.37-9.69,19.92-20.73,36.93-34.2,53.55Z"/>
  <path class="cls-3" d="M345.87,690.2c-23.21-5.88-42.85-26.5-56.49-45.8-10.97-15.52-19.66-31.81-27.65-49.91,27.49-7.73,55.34-11.58,84.17-12.53l-.03,108.24Z"/>
  <path class="cls-3" d="M536.79,640.92c-29.21,18.19-60.11,32.12-94.51,40.66,20.78-22.09,37.42-46.52,50.59-74.34,16.89,7.13,31.78,16.5,45.31,28.37l.85,2.78c.24.77-1.24,1.91-2.24,2.53Z"/>
  <path class="cls-3" d="M84.7,333.59l16.54,16.34H30.6c1.24-30.02,6.5-58.33,15.83-87.54,4.64,9.16,8.24,18.57,11.53,28.46,5.42,16.33,14.21,30.36,26.75,42.75Z"/>
  <path class="cls-3" d="M690.02,349.96l-29.85-.13c1.15-9.23.83-16.89-.51-25.42l-6.59-22.38,32.12,5.21c2.75,14.14,4.22,27.86,4.84,42.72Z"/>
  <path class="cls-3" d="M284.31,683.09c-35.26-8.19-68.67-22.44-99.77-41.69-1.66-1.03-3.75-3.48-3.29-5.06,15.52-14.4,34.03-25.15,54.33-32.68,13.72,29.45,28.44,55.56,49.6,78.62l.6.47-.58.99-.89-.64Z"/>
  <g>
    <path class="cls-3" d="M141.44,277.91c2.76-3.81,8.67-.05,11.28,2.11,9.79,8.14,21.96,11.16,34.98,7.77,30.93-8.06,29.98-48.9,36.84-54.13,8.89-6.78,29.55-1.99,49.75-25.48,11.23-13.06,16.82-29.72,16.45-47.79,18.83,2.93,36.87,4.73,55.16,5.87v183.68s-166.62-.01-166.62-.01c-5.04-18.38-13.46-34.07-25.41-47.92l-8.77-10.52c-3.09-2.65-6.7-9.38-3.66-13.58Z"/>
    <path class="cls-2" d="M156.8,251.12c-14.93-4.69-39.15-.98-42.04,15.55-2.08,11.91-2.55,27.42,4.46,35.85l13.19,15.86c7.81,9.39,13.75,18.66,18.23,31.13l-9.49.29c-13.74-12.13-26.62-23.78-39.29-36.77-7.03-8.19-13.3-16.12-16.7-26.5-6.8-20.76-15.36-40.18-25.99-59.59,22.02-50.1,54.73-93.68,97.95-127.92,30.02,28.83,67.07,44.17,106.34,54.94,2.22,12.25-.84,23.95-7.65,33.86-4.73,6.88-11.52,11.12-19.56,13.46l-19.31,5.62c-28.21,8.21-20.65,49.95-36.09,55.05-8.7,2.87-14.59-7.85-24.04-10.82Z"/>
    <path class="cls-3" d="M427.19,210.2c-18.06,13.77-22.12,36.2-6.4,52.68-27.59,20.1-34.77,54.77-25.98,87.07l-20.54-.08-.03-183.64c31.26-1.03,61.31-5.32,91.83-13.77l3.95,10.88-17.17,5.45c-13.99,4.45-22.07,17.33-21.46,32.23.16,3.89-1.18,6.88-4.2,9.18Z"/>
    <path class="cls-3" d="M398.76,43.11c26.04,20.8,44.49,50.75,58.01,81.96-27.35,7.84-53.92,11.27-82.47,12.3l-.06-106.42c9.62,2.56,16.95,6.88,24.52,12.16Z"/>
    <path class="cls-3" d="M263.58,123.78c7.41-18.08,16.53-34.28,27.43-49.71,13.08-18.53,32.48-37.83,54.89-43.29v106.46c-27.77-1.15-54.41-4.83-82.32-13.46Z"/>
    <path class="cls-3" d="M492.04,112.51c-3.24,1.45-5.93,2.34-9.28,2.59-11.93-27.41-26.15-52.27-46.16-75.81,37.09,7.35,70.95,22.3,102.94,42.78-13.7,13.7-30.06,22.63-47.5,30.44Z"/>
    <path class="cls-3" d="M237.09,114.39c-20.59-7.68-39.09-17.95-55.37-32.37,19.36-12.12,38.1-21.7,58.12-30.16,14.05-4.78,27.29-10.2,42.81-12.31-19.16,23.29-33.23,47.17-45.56,74.84Z"/>
  </g>
</svg>
SVG;
    $formatIconUrl = null;
    $formatLabelLower = strtolower($formatLabel);
    if ((str_contains($formatLabelLower, 'online') || $onlineOnlyLocation)
        && ! str_contains($formatLabelLower, '&')
        && ! str_contains($formatLabelLower, '+')) {
        $formatIconUrl = null;
    } elseif ((str_contains($formatLabelLower, 'in-person') || str_contains($formatLabelLower, 'in person'))
        && ! str_contains($formatLabelLower, '&')
        && ! str_contains($formatLabelLower, '+')) {
        $formatIconUrl = asset('images/offering-format-icons/format-inperson.png');
    } elseif (count($physicalLocations) > 0 && ! $hasOnlineLocation) {
        $formatIconUrl = asset('images/offering-format-icons/format-inperson.png');
    }
    $watermarkWords = array_slice(preg_split('/\s+/', strtoupper($title)) ?: [], 0, 2);
    if (empty($watermarkWords)) {
        $watermarkWords = [strtoupper($mode ?: 'WELLNESS')];
    }
    $watermark = implode('<br>', array_map(fn ($word) => e($word), $watermarkWords));

    $quickInfoIconSvg = static function (string $icon) use ($formatIconUrl, $formatOnlineIconSvg): string {
        return match ($icon) {
            'clock' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"></path><path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
            'format' => $formatIconUrl !== null
                ? '<img src="' . e($formatIconUrl) . '" alt="" aria-hidden="true" loading="lazy" decoding="async">'
                : $formatOnlineIconSvg,
            'person' => '<svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
            'online' => '<svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4.37 7.657c2.063.528 2.396 2.806 3.202 3.87 1.07 1.413 2.075 1.228 3.192 2.644 1.805 2.289 1.312 5.705 1.312 6.705M20 15h-1a4 4 0 0 0-4 4v1M8.587 3.992c0 .822.112 1.886 1.515 2.58 1.402.693 2.918.351 2.918 2.334 0 .276 0 2.008 1.972 2.008 2.026.031 2.026-1.678 2.026-2.008 0-.65.527-.9 1.177-.9H20M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
            'booking' => '<svg viewBox="0 0 24 24" fill="none"><path d="M7 3v3M17 3v3M4.5 9h15M6.5 5h11A2.5 2.5 0 0 1 20 7.5v10A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-10A2.5 2.5 0 0 1 6.5 5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><path d="m9 15 2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
            'price' => '<svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17.345a4.76 4.76 0 0 0 2.558 1.618c2.274.589 4.512-.446 4.999-2.31.487-1.866-1.273-3.9-3.546-4.49-2.273-.59-4.034-2.623-3.547-4.488.486-1.865 2.724-2.899 4.998-2.31.982.236 1.87.793 2.538 1.592m-3.879 12.171V21m0-18v2.2"/></svg>',
            'delivery' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path><path d="m5.4 6.8 6.1 5.1c.3.25.74.25 1.05 0l6.05-5.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"></path><path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
        };
    };

    $variantCards = [];
    $variantsRaw = is_array($offering['variants'] ?? null) ? array_values(array_filter($offering['variants'], fn ($variant) => is_array($variant))) : [];
    foreach ($variantsRaw as $index => $variant) {
        $variantId = trim((string) ($variant['id'] ?? ''));
        $variantOptions = is_array($variant['options'] ?? null) ? array_values(array_filter(array_map('trim', $variant['options']))) : [];
        $variantLabel = trim((string) ($variant['label'] ?? implode(' • ', $variantOptions)));
        if ($variantLabel === '') {
            $variantLabel = 'Option ' . ($index + 1);
        }
        $variantPrice = is_numeric($variant['price'] ?? null) ? (float) $variant['price'] : $rawPrice;
        $variantCompare = is_numeric($variant['compare'] ?? null) ? (float) $variant['compare'] : null;
        $priceOptionId = null;
        if (preg_match('/^po_(\d+)(?:_tier_(\d+))?$/', $variantId, $variantMatches)) {
            $priceOptionId = (int) $variantMatches[1];
        } elseif (preg_match('/^(\d+)$/', $variantId, $variantMatches)) {
            $priceOptionId = (int) $variantMatches[1];
        }

        $variantCards[] = [
            'id' => $variantId !== '' ? $variantId : ('variant-' . ($index + 1)),
            'label' => $variantLabel,
            'meta' => $variantOptions ? implode(' · ', $variantOptions) : $variantLabel,
            'selection' => $variantOptions,
            'price' => $variantPrice,
            'compare' => $variantCompare,
            'available' => ! array_key_exists('available', $variant) || (bool) $variant['available'],
            'price_option_id' => $priceOptionId,
        ];
    }

    if (empty($variantCards)) {
        $variantCards[] = [
            'id' => 'variant-default',
            'label' => '1 Session',
            'meta' => 'Default option',
            'selection' => ['Default'],
            'price' => $rawPrice,
            'compare' => null,
            'available' => true,
            'price_option_id' => null,
        ];
    }
    foreach ($variantCards as &$variantCard) {
        $variantCard['price'] = $pricing->buyerPrice($variantCard['price'] ?? 0, $pricingVendor, (int) ($offering['vendor_id'] ?? 0), ! $isStoreProduct);
        if (is_numeric($variantCard['compare'] ?? null)) {
            $variantCard['compare'] = $pricing->buyerPrice($variantCard['compare'], $pricingVendor, (int) ($offering['vendor_id'] ?? 0), ! $isStoreProduct);
        }
    }
    unset($variantCard);
    $sessionCountForLabel = static function (string $label): int {
        if (preg_match('/(\d+)/', $label, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    };
    $bestVariantIndex = null;
    $bestVariantUnit = null;
    foreach ($variantCards as $variantIndex => $variantCard) {
        $sessionText = trim((string) ($variantCard['label'] ?? ''));
        foreach (($variantCard['selection'] ?? []) as $selectionValue) {
            $selectionValue = trim((string) $selectionValue);
            if (preg_match('/session/i', $selectionValue) || preg_match('/^\d+$/', $selectionValue)) {
                $sessionText = $selectionValue;
                break;
            }
        }
        $sessionCount = $sessionCountForLabel($sessionText);
        $variantCards[$variantIndex]['session_count'] = $sessionCount;
        $unitPrice = $sessionCount > 0 ? ((float) ($variantCard['price'] ?? 0) / $sessionCount) : (float) ($variantCard['price'] ?? 0);
        $variantCards[$variantIndex]['unit_price'] = $unitPrice;
        if ($bestVariantUnit === null || $unitPrice < $bestVariantUnit || ($unitPrice === $bestVariantUnit && $sessionCount > (int) ($variantCards[$bestVariantIndex]['session_count'] ?? 0))) {
            $bestVariantUnit = $unitPrice;
            $bestVariantIndex = $variantIndex;
        }
    }
    if ($bestVariantIndex !== null && isset($variantCards[$bestVariantIndex])) {
        $variantCards[$bestVariantIndex]['is_best_value'] = true;
    }

    $selectedVariantId = (string) ($offering['selectedVariantId'] ?? ($variantCards[0]['id'] ?? ''));
    $selectedVariantCard = null;
    foreach ($variantCards as $variantCard) {
        if ((string) ($variantCard['id'] ?? '') === $selectedVariantId) {
            $selectedVariantCard = $variantCard;
            break;
        }
    }
    if (! $selectedVariantCard) {
        $selectedVariantCard = $variantCards[0];
        $selectedVariantId = (string) ($selectedVariantCard['id'] ?? '');
    }
    $selectedVariantLabel = trim((string) ($offering['selectedVariantLabel'] ?? ($selectedVariantCard['label'] ?? '')));
    if ($selectedVariantLabel === '') {
        $selectedVariantLabel = (string) ($selectedVariantCard['label'] ?? 'Option');
    }
    $selectedVariantSelection = array_values(array_filter(array_map('trim', (array) ($offering['selectedVariantSelection'] ?? ($selectedVariantCard['selection'] ?? [])))));
    $selectedVariantPrice = is_numeric($offering['selectedVariantPrice'] ?? null)
        ? (float) $offering['selectedVariantPrice']
        : (float) ($selectedVariantCard['price'] ?? 0);
    $selectedVariantPriceOptionId = is_numeric($offering['selectedVariantPriceOptionId'] ?? null)
        ? (int) $offering['selectedVariantPriceOptionId']
        : ($selectedVariantCard['price_option_id'] ?? null);

    // V3 group options are stored as a per-person rate.
    $groupPricing = false;
    $groupMin = null;
    $groupMax = null;
    foreach ($variantCards as $variantCard) {
        $variantText = implode(' ', array_filter(array_merge(
            [(string) ($variantCard['label'] ?? '')],
            array_map('strval', (array) ($variantCard['selection'] ?? []))
        )));
        if (preg_match('/(?:3\+\s*group|(\d+)\s*(?:-|–|to)\s*(\d+)\s*group|(\d+)\s*people?)/i', $variantText, $groupMatch)) {
            $groupPricing = true;
            $minimum = (int) ($groupMatch[1] ?? $groupMatch[3] ?? 3);
            $maximum = isset($groupMatch[2]) && $groupMatch[2] !== ''
                ? (int) $groupMatch[2]
                : (isset($groupMatch[3]) && $groupMatch[3] !== '' ? $minimum : null);
            if ($minimum >= 3) {
                $groupMin = $groupMin === null ? $minimum : min($groupMin, $minimum);
                if ($maximum !== null) {
                    $groupMax = $groupMax === null ? $maximum : max($groupMax, $maximum);
                }
            }
        }
    }
    $groupMin = $groupMin ?? 3;
    $selectedGroupCount = $groupMin;
    $selectedGroupText = implode(' ', array_filter(array_merge(
        [(string) ($selectedVariantLabel ?? '')],
        array_map('strval', $selectedVariantSelection)
    )));
    if (preg_match('/(\d+)\s*(?:-|–|to)\s*(\d+)\s*group/i', $selectedGroupText, $selectedGroupMatch)) {
        $selectedGroupCount = max($groupMin, (int) $selectedGroupMatch[1]);
    } elseif (preg_match('/(\d+)\s*people?/i', $selectedGroupText, $selectedGroupMatch)) {
        $selectedGroupCount = max($groupMin, (int) $selectedGroupMatch[1]);
    }
    if ($groupMax !== null) {
        $groupMax = max($groupMin, $groupMax);
        $selectedGroupCount = min($groupMax, $selectedGroupCount);
    }

    $venueLocationLookup = [];
    foreach ($locations as $location) {
        $locationKey = trim((string) ($location['location_key'] ?? ''));
        if ($locationKey === '') {
            continue;
        }

        $venueLocationLookup[$locationKey] = $location;
    }

    $variantLocationGroups = [];
    $inPersonVariantIds = [];
    foreach ($variantCards as $variantCard) {
        $selection = array_values(array_filter(array_map('trim', (array) ($variantCard['selection'] ?? []))));
        if (empty($selection)) {
            continue;
        }

        $locationValue = null;
        $locationKey = null;
        foreach (array_reverse($selection) as $selectionValue) {
            $selectionText = trim((string) $selectionValue);
            if ($selectionText === '') {
                continue;
            }

            $selectionLower = strtolower($selectionText);
            if (str_contains($selectionLower, 'online')) {
                $locationValue = 'Online session';
                $locationKey = 'online';
                break;
            }
            if (str_contains($selectionLower, 'in-person') || str_contains($selectionLower, 'in person')) {
                $inPersonVariantIds[] = (string) ($variantCard['id'] ?? '');
                if (count($physicalLocations) === 1) {
                    $physicalLocation = $physicalLocations[0];
                    $locationValue = (string) ($physicalLocation['label'] ?? 'In-person');
                    $locationKey = (string) ($physicalLocation['location_key'] ?? '');
                }
                break;
            }

            $selectionKey = $normalizeLocationKey($selectionText);
            if ($selectionKey !== '' && isset($knownLocationKeys[$selectionKey])) {
                $locationValue = $selectionText;
                $locationKey = $selectionKey;
                break;
            }
        }

        if ($locationValue === null || $locationValue === '' || $locationKey === null || $locationKey === '') {
            continue;
        }

        if (! isset($variantLocationGroups[$locationKey])) {
            $variantLocationGroups[$locationKey] = [
                'value' => $locationValue,
                'variant_ids' => [],
            ];
        }

        $variantLocationGroups[$locationKey]['variant_ids'][] = (string) ($variantCard['id'] ?? '');
    }

    if (! empty($variantLocationGroups)) {
        $rebuiltLocations = [];
        foreach ($variantLocationGroups as $locationKey => $group) {
            $venueLocation = $venueLocationLookup[$locationKey] ?? null;
            $locationValue = trim((string) ($group['value'] ?? ''));
            $locationLabel = $locationValue;
            $locationAddress = $locationValue;
            $isOnlineLocation = str_contains(strtolower($locationValue), 'online');

            if (is_array($venueLocation)) {
                $locationLabel = trim((string) ($venueLocation['label'] ?? $locationLabel));
                $locationAddress = trim((string) ($venueLocation['full_address'] ?? $venueLocation['address'] ?? $locationAddress));
                $isOnlineLocation = $isOnlineLocation || ! empty($venueLocation['online']);
            }

            $rebuiltLocations[] = [
                'id' => is_array($venueLocation) && ! empty($venueLocation['id'])
                    ? (string) $venueLocation['id']
                    : 'loc-variant-' . ($locationKey !== '' ? $locationKey : Str::slug($locationValue ?: 'location')),
                'label' => $locationLabel !== '' ? $locationLabel : $locationValue,
                'address' => $locationAddress !== '' ? $locationAddress : 'Available',
                'street_address' => is_array($venueLocation) ? (string) ($venueLocation['street_address'] ?? '') : '',
                'full_address' => is_array($venueLocation) ? (string) ($venueLocation['full_address'] ?? $locationAddress) : $locationAddress,
                'notes' => is_array($venueLocation) ? (string) ($venueLocation['notes'] ?? '') : '',
                'lat' => is_array($venueLocation) ? ($venueLocation['lat'] ?? null) : null,
                'lng' => is_array($venueLocation) ? ($venueLocation['lng'] ?? null) : null,
                'online' => $isOnlineLocation,
                'location_key' => $locationKey,
                'variant_ids' => array_values(array_unique(array_filter(array_map('strval', $group['variant_ids'] ?? [])))),
            ];
        }

        $rebuiltLocationKeys = array_values(array_filter(array_map(
            static fn (array $location): string => (string) ($location['location_key'] ?? ''),
            $rebuiltLocations
        )));
        $remainingLocations = array_values(array_filter($locations, static function (array $location) use ($rebuiltLocationKeys): bool {
            if (! empty($location['online'])) {
                return true;
            }

            return ! in_array((string) ($location['location_key'] ?? ''), $rebuiltLocationKeys, true);
        }));
        $locations = array_merge($rebuiltLocations, $remainingLocations);
    }

    if (count($physicalLocations) > 1 && ! empty($inPersonVariantIds)) {
        $locations = array_map(static function (array $location) use ($inPersonVariantIds): array {
            if (empty($location['online'])) {
                $location['variant_ids'] = array_values(array_unique(array_merge(
                    (array) ($location['variant_ids'] ?? []),
                    $inPersonVariantIds
                )));
            }

            return $location;
        }, $locations);
    }

    $dedupedLocations = [];
    $seenLocationKeys = [];
    foreach ($locations as $location) {
        $locationKey = ! empty($location['online'])
            ? 'online'
            : ('location:' . (string) ($location['location_key'] ?? $location['id'] ?? Str::slug((string) ($location['label'] ?? 'location'))));

        if (isset($seenLocationKeys[$locationKey])) {
            continue;
        }

        $seenLocationKeys[$locationKey] = true;
        $dedupedLocations[] = $location;
    }
    $locations = $dedupedLocations;

    $physicalLocations = array_values(array_filter($locations, fn ($location) => empty($location['online'])));
    $hasOnlineLocation = collect($locations)->contains(fn ($location) => ! empty($location['online']));
    $onlineOnlyLocation = $hasOnlineLocation && count($physicalLocations) === 0;
    if ($onlineOnlyLocation) {
        $locations = array_map(static function (array $location): array {
            if (! empty($location['online'])) {
                $location['label'] = 'Exclusively online';
                $location['address'] = 'Live session link sent after booking';
                $location['notes'] = 'Online appointment';
            }

            return $location;
        }, $locations);
    }

    $selectedLocationId = $physicalLocations[0]['id'] ?? ($locations[0]['id'] ?? 'loc-online');
    $selectedLocationLabel = $locations[0]['label'] ?? 'Location';
    $selectedLocationAddress = $locations[0]['address'] ?? '';
    foreach ($locations as $location) {
        if (($location['id'] ?? '') === $selectedLocationId) {
            $selectedLocationLabel = $location['label'] ?? $selectedLocationLabel;
            $selectedLocationAddress = $location['address'] ?? $selectedLocationAddress;
            break;
        }
    }

    $selectedVariantMode = strtolower(trim(implode(' ', $selectedVariantSelection)));
    $preferredLocation = null;
    $selectedVariantLocationKey = null;
    foreach (array_reverse($selectedVariantSelection) as $selectionValue) {
        $selectionKey = $normalizeLocationKey((string) $selectionValue);
        if ($selectionKey !== '' && isset($knownLocationKeys[$selectionKey]) && $selectionKey !== 'online') {
            $selectedVariantLocationKey = $selectionKey;
            break;
        }
    }
    if ($selectedVariantMode !== '') {
        if ($selectedVariantLocationKey !== null) {
            $preferredLocation = collect($locations)->first(fn (array $location): bool => ($location['location_key'] ?? '') === $selectedVariantLocationKey);
        } elseif (str_contains($selectedVariantMode, 'in-person') || str_contains($selectedVariantMode, 'in person')) {
            $preferredLocation = collect($locations)->first(fn (array $location): bool => empty($location['online']));
        } elseif (str_contains($selectedVariantMode, 'online')) {
            $preferredLocation = collect($locations)->first(fn (array $location): bool => ! empty($location['online']));
        }
    }

    if (is_array($preferredLocation)) {
        $selectedLocationId = (string) ($preferredLocation['id'] ?? $selectedLocationId);
        $selectedLocationLabel = trim((string) ($preferredLocation['label'] ?? $selectedLocationLabel));
        $selectedLocationAddress = trim((string) ($preferredLocation['address'] ?? $selectedLocationAddress));
    }

    $quickInfo = [
        [
            'label' => 'Duration',
            'value' => $durationLabel,
            'icon' => 'clock',
        ],
        [
            'label' => 'Format',
            'value' => $formatLabel,
            'icon' => 'format',
        ],
        [
            'label' => 'Booking',
            'value' => $bookingSummaryLabel,
            'icon' => 'booking',
        ],
        [
            'label' => 'Price',
            'value' => $priceSummary,
            'icon' => 'price',
        ],
        [
            'label' => 'Confirmation',
            'value' => 'Instant email',
            'icon' => 'delivery',
        ],
    ];
    $quickInfoMobile = array_map(static function (array $card) use ($durationCompactLabel): array {
        $value = (string) ($card['value'] ?? '');

        switch ((string) ($card['label'] ?? '')) {
            case 'Duration':
                $value = $durationCompactLabel;
                break;
            case 'Booking':
                $value = 'Availability';
                break;
            case 'Price':
                $value = preg_replace('/\.00$/', '', $value) ?? $value;
                break;
            case 'Confirmation':
                $value = 'Instant';
                break;
        }

        $card['value'] = $value;

        return $card;
    }, $quickInfo);

    if ($isStoreProduct) {
        $stockQuantity = is_numeric($offering['inventory_quantity'] ?? null)
            ? max(0, (int) $offering['inventory_quantity'])
            : null;
        $isInStock = (bool) ($offering['in_stock'] ?? true) && ($stockQuantity === null || $stockQuantity > 0);
        if (! $isInStock) {
            $stockStatusLabel = 'Out of stock';
            $stockStatusClass = 'is-urgent';
        } elseif ($stockQuantity !== null && $stockQuantity <= 10) {
            $stockStatusLabel = 'Only ' . $stockQuantity . ' left — order soon';
            $stockStatusClass = 'is-urgent';
        } elseif ($stockQuantity !== null && $stockQuantity < 30) {
            $stockStatusLabel = 'Low stock — ' . $stockQuantity . ' remaining';
            $stockStatusClass = 'is-low';
        } else {
            $stockStatusLabel = 'In stock';
            $stockStatusClass = '';
        }
        $typeLabel = 'Product';
        $formatLabel = 'Physical product';
        $locationSummary = 'Ships to you';
        $durationLabel = 'Ships to you';
        $bookingSummaryLabel = $offering['in_stock'] ?? true ? 'In stock' : 'Unavailable';
        $priceSummary = '£' . number_format($price, 2);
        $quickInfo = [
            ['label' => 'Format', 'value' => 'Physical product', 'icon' => 'format'],
            ['label' => 'Delivery', 'value' => 'Ships to you', 'icon' => 'delivery'],
            ['label' => 'Stock', 'value' => $stockStatusLabel, 'icon' => 'booking'],
            ['label' => 'Price', 'value' => $priceSummary, 'icon' => 'price'],
        ];
        $quickInfoMobile = $quickInfo;
    }

    $renderRichHtml = static function (string $value): string {
        return \App\Support\ContentFormatter::format($value);
    };

    $supportList = array_values(array_filter([
        $safetyNotes,
        $contraindications,
    ]));
    $guidePanel = null;
    try {
        $guideService = app(\App\Services\GuideRegistryService::class);
        $seoService = app(\App\Services\SeoStructureService::class);
        $guideModalityCandidates = array_values(array_filter(array_map('trim', [
            (string) ($offering['modality'] ?? ''),
            (string) data_get($offering, 'category.slug', ''),
            (string) data_get($offering, 'category.name', ''),
        ])));
        $guideFormat = $seoService->canonicalFormatKey((string) ($offering['format'] ?? $type ?? $formatLabel ?? 'therapies'));
        foreach ($guideModalityCandidates as $guideModalityCandidate) {
            $guideHub = $guideService->modalityHub($guideFormat, $guideModalityCandidate);
            if (! empty($guideHub)) {
                $guidePanel = [
                    'title' => (string) ($guideHub['h1'] ?? $guideHub['title'] ?? 'Related guides'),
                    'eyebrow' => 'Explore guides',
                    'summary' => (string) ($guideHub['intro'] ?? ''),
                    'hub_url' => (string) data_get($guideHub, 'seo.canonical', ''),
                    'hub_label' => 'Browse all guides',
                    'links' => array_values((array) ($guideHub['popular_guides'] ?? [])),
                ];
                break;
            }

            $guidePanel = $guideService->modalityGuidePanel($guideModalityCandidate, $guideFormat);
            if (! empty($guidePanel)) {
                break;
            }
        }
    } catch (\Throwable $e) {
        $guidePanel = null;
    }
    $offeringConfig = [
        'id' => (int) ($offering['id'] ?? 0),
        'title' => $title,
        'url' => (string) ($offering['url'] ?? url()->current()),
        'price' => (float) $price,
        'priceMin' => (float) $priceMin,
        'priceMax' => (float) $priceMax,
        'currency' => $currency,
        'bookingFlow' => $bookingFlow,
        'mode' => $mode,
        'durationLabel' => $durationLabel,
        'durationMinutes' => $durationMinutes,
        'images' => $heroImages,
        'locations' => $locations,
        'selectedLocationId' => $selectedLocationId,
        'selectedLocationLabel' => $selectedLocationLabel,
        'selectedLocationAddress' => $selectedLocationAddress,
        'variants' => $variantCards,
        'selectedVariantId' => $selectedVariantId,
        'selectedVariantLabel' => $selectedVariantLabel,
        'selectedVariantSelection' => $selectedVariantSelection,
        'selectedVariantPrice' => $selectedVariantPrice,
        'selectedVariantPriceOptionId' => $selectedVariantPriceOptionId,
        'groupPricing' => $groupPricing,
        'groupMin' => $groupMin,
        'groupMax' => $groupMax,
        'selectedGroupCount' => $selectedGroupCount,
        'practitioner' => $practitioner,
        'sourceVersion' => $sourceVersion !== '' ? $sourceVersion : 'legacy',
        'bookingEndpoint' => url('/api/booking/' . ($sourceVersion === 'v3' ? 'offering' : 'product') . '/' . (int) ($offering['id'] ?? 0)),
        'cartAddEndpoint' => url('/api/cart/add'),
        'cartUrl' => url('/cart'),
        'reservationHoldEndpoint' => url('/api/reservations/hold'),
        'reservationReleaseEndpoint' => url('/api/reservations/release'),
        'mapboxToken' => config('services.mapbox.token'),
    ];
@endphp

@push('styles')
<link href="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css" rel="stylesheet">
<style>
    .wow-v3-offering-page {
        --ink: #13221d;
        --muted: #6e7d78;
        --soft: #f6fbf8;
        --soft-2: #edf7f1;
        --line: #dbe8e1;
        --line-dark: rgba(19, 34, 29, 0.16);
        --green: #549483;
        --green-hover: #417b6d;
        --green-dark: #234a40;
        --green-soft: #e7f4ef;
        --white: #ffffff;
        --amber: #f5a400;
        --shadow: 0 18px 50px rgba(25, 53, 44, 0.12);
        --shadow-soft: 0 10px 28px rgba(25, 53, 44, 0.08);
        --radius: 4px;
        --max: 1180px;
        color: var(--ink);
        background: none !important;
        background-color: transparent !important;
        padding-bottom: 24px;
    }

    .wow-v3-offering-page, .wow-v3-offering-page * { box-sizing: border-box; }
    .wow-v3-offering-page img { width: 100%; display: block; }
    .wow-v3-offering-page a { color: inherit; text-decoration: none; }
    .wow-v3-offering-page button { font: inherit; }
    .wow-v3-offering-page button,
    .wow-v3-offering-page .btn,
    .wow-v3-offering-page .custom-select-trigger,
    .wow-v3-offering-page .custom-select-option,
    .wow-v3-offering-page .location-option,
    .wow-v3-offering-page .location-change-btn,
    .wow-v3-offering-page .availability-card,
    .wow-v3-offering-page .date-time-date,
    .wow-v3-offering-page .time-option,
    .wow-v3-offering-page .qty button,
    .wow-v3-offering-page .mobile-ticket-bar strong,
    .wow-v3-offering-page .selected-summary strong,
    .wow-v3-offering-page .mini-row strong {
        font-weight: 400 !important;
    }

    .wow-v3-offering-page .page-nav {
        max-width: var(--max);
        margin: 20px auto 0;
        padding: 0 20px;
    }
    .wow-v3-offering-page .page-nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 10px;
        border: 1px solid var(--line);
        background: var(--white);
        border-radius: var(--radius);
        box-shadow: var(--shadow-soft);
    }
    .wow-v3-offering-page .breadcrumbs {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        color: var(--muted);
        font-size: 13px;
        white-space: nowrap;
        overflow: auto;
    }
    .wow-v3-offering-page .crumb {
        display: inline-flex;
        align-items: center;
        padding: 7px 10px;
        background: var(--soft);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        color: var(--green-dark);
    }
    .wow-v3-offering-page .crumb-current { background: var(--green-soft); border-color: #cbe5da; }
    .wow-v3-offering-page .nav-badges { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .wow-v3-offering-page .nav-badge {
        padding: 7px 10px;
        border-radius: var(--radius);
        background: var(--white);
        border: 1px solid var(--line);
        color: var(--green-dark);
        font-size: 13px;
    }
    .wow-v3-offering-page .nav-badge.green { background: var(--green-soft); border-color: #cbe5da; }

    .wow-v3-offering-page .hero-wrap {
        max-width: var(--max);
        margin: 18px auto 0;
        padding: 0;
    }
    .wow-v3-offering-page .hero {
        position: relative;
        min-height: 0;
        height: auto;
        overflow: visible;
        border-radius: var(--radius);
        background: transparent;
        box-shadow: none;
    }
    .wow-v3-offering-page .hero-slide,
    .wow-v3-offering-page .hero-overlay,
    .wow-v3-offering-page .hero-watermark,
    .wow-v3-offering-page .hero-review-badge { display: none; }
    .wow-v3-offering-page .hero-slide {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        opacity: 0;
        transform: scale(1.02);
        transition: opacity 900ms ease, transform 5500ms ease;
    }
    .wow-v3-offering-page .hero-slide.is-active { opacity: 1; transform: scale(1); }
    .wow-v3-offering-page .hero-overlay {
        position: absolute;
        inset: 0;
        z-index: 1;
        background: rgba(14, 34, 27, 0.56);
        pointer-events: none;
    }
    .wow-v3-offering-page .hero-watermark {
        position: absolute;
        right: 18px;
        top: 34px;
        z-index: 2;
        color: rgba(255, 255, 255, 0.08);
        font-size: clamp(70px, 12vw, 150px);
        line-height: 0.84;
        letter-spacing: -0.08em;
        text-transform: uppercase;
        pointer-events: none;
        font-weight: 500;
        text-align: right;
    }
    .wow-v3-offering-page .hero-review-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 4;
        display: inline-flex;
        flex-direction: column;
        gap: 6px;
        min-width: 172px;
        max-width: min(320px, calc(100% - 40px));
        padding: 12px 14px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 16px 36px rgba(7, 20, 14, 0.22);
        backdrop-filter: blur(16px);
        color: var(--ink);
    }
    .wow-v3-offering-page .hero-review-badge__eyebrow {
        color: var(--green-dark);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .wow-v3-offering-page .hero-review-badge__rating {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--ink);
        font-size: 12.5px;
        line-height: 1.2;
    }
    .wow-v3-offering-page .hero-review-badge__stars {
        display: inline-flex;
        gap: 2px;
        flex-shrink: 0;
    }
    .wow-v3-offering-page .hero-review-badge__star {
        width: 14px;
        height: 14px;
        display: inline-block;
        background: #f5c84b;
        -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z'/%3E%3C/svg%3E") center/contain no-repeat;
        mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z'/%3E%3C/svg%3E") center/contain no-repeat;
    }
    .wow-v3-offering-page .hero-review-badge__star.is-empty {
        background: #d0d5dd;
    }
    .wow-v3-offering-page .hero-review-badge__copy {
        color: var(--green-dark);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: -.01em;
    }
    .wow-v3-offering-page .hero-content {
        position: relative;
        z-index: 3;
        width: 100%;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 0;
        min-height: 0;
        padding: 0;
        align-items: start;
    }
    .wow-v3-offering-page .hero-main {
        width: 100%;
        max-width: none;
        color: #000;
    }
    /* The checkout is relocated into .side-stack by the page bootstrap. Keep
       it out of the hero grid while that move is pending so the title never
       renders with a blank reserved column or vertical gap. */
    .wow-v3-offering-page .hero-content > .booking-panel,
    .wow-v3-offering-page .hero-content > .legacy-buybox-shell {
        position: absolute;
        inset-inline-end: 0;
        inset-block-start: 0;
        width: min(380px, 100%);
    }
    .wow-v3-offering-page .side-stack > .booking-panel,
    .wow-v3-offering-page .side-stack > .legacy-buybox-shell {
        position: static;
        inset: auto;
        width: auto;
    }
    /* Keep the title and category block anchored to the top of the hero. The
       booking panel may remain bottom-aligned, but the primary heading should
       never be pushed down by the hero's vertical alignment. */
    .wow-v3-offering-page:not(.wow-v3-product-only) .hero-main { align-self: start; }
    .wow-v3-offering-page .kicker-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
    .wow-v3-offering-page .pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: var(--radius);
        background: #fff;
        border: 1px solid var(--line);
        color: #000;
        font-size: 13px;
    }
    .wow-v3-offering-page .hero h1 {
        width: 100%;
        max-width: none;
        margin: 0;
        color: #000000;
        font-size: clamp(32px, 4.5vw, 56px);
        line-height: 0.94;
        letter-spacing: -0.065em;
        font-weight: 500;
    }
    .wow-v3-offering-page .hero-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 28px; }
    .wow-v3-offering-page .btn {
        appearance: none;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 50px;
        padding: 0 18px;
        border-radius: var(--radius);
        font-size: 15px;
        transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
    }
    .wow-v3-offering-page .btn:hover { transform: translateY(-1px); }
    .wow-v3-offering-page .btn-primary,
    .wow-v3-offering-page .checkout-button {
        color: #ffffff;
        background: var(--green);
        border-color: var(--green);
    }
    .wow-v3-offering-page .btn-primary:hover,
    .wow-v3-offering-page .checkout-button:hover {
        background: var(--green-hover);
        border-color: var(--green-hover);
    }
    .wow-v3-offering-page .btn-secondary {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.28);
    }
    .wow-v3-offering-page .hero-meta {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        max-width: 1120px;
        margin-top: 34px;
        margin-bottom: 22px;
    }
    .wow-v3-offering-page .quick-glance-section {
        display: none;
    }

    .wow-v3-offering-page .booking-panel {
        display: block;
        visibility: hidden;
        align-self: end;
        min-width: 0;
        padding: 22px;
        border-radius: var(--radius);
        background: #ffffff;
        border: 1px solid var(--line);
        box-shadow: 0 22px 60px rgba(2, 18, 32, 0.22);
    }
    .wow-v3-offering-page .booking-panel.is-positioned { visibility: visible; }
    .wow-v3-offering-page .legacy-buybox-shell {
        align-self: end;
        min-width: 0;
    }
    .wow-v3-offering-page .legacy-buybox-shell .container-wrap {
        padding: 0 !important;
    }
    .wow-v3-offering-page .legacy-buybox-shell .buybox {
        position: relative;
        top: auto;
        max-width: 380px;
        margin-left: auto;
    }
    .wow-v3-offering-page .legacy-buybox-shell .buybox .card {
        max-width: 380px;
        border-radius: var(--radius);
        border-color: var(--line);
        box-shadow: 0 22px 60px rgba(2, 18, 32, 0.22);
    }
    .wow-v3-offering-page .booking-top {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--line);
    }
    .wow-v3-offering-page .price-label {
        color: var(--muted);
        font-size: 12px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .wow-v3-offering-page .price {
        margin-top: 3px;
        color: var(--ink);
        font-size: 34px;
        line-height: 1;
        letter-spacing: -0.04em;
        font-weight: 500;
    }
    .wow-v3-offering-page .status-dot {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--green-dark);
        background: var(--green-soft);
        border: 1px solid #cbe5da;
        padding: 7px 9px;
        border-radius: var(--radius);
        font-size: 12px;
        white-space: nowrap;
    }
    .wow-v3-offering-page .status-dot::before {
        content: "";
        width: 7px;
        height: 7px;
        background: var(--green);
        border-radius: 2px;
    }
    .wow-v3-offering-page .status-dot.is-low {
        color: #815c00;
        background: #fff7d6;
        border-color: #f0d36a;
    }
    .wow-v3-offering-page .status-dot.is-low::before { background: #d49a00; }
    .wow-v3-offering-page .status-dot.is-urgent {
        color: #a33127;
        background: #fff0ee;
        border-color: #edaaa2;
    }
    .wow-v3-offering-page .status-dot.is-urgent::before { background: #d94b3d; }
    .wow-v3-offering-page .mobile-panel-pick { display: none; margin-top: 14px; }
    .wow-v3-offering-page .booking-fields { display: block; }
    .wow-v3-offering-page .field-label {
        display: block;
        margin: 16px 0 8px;
        color: var(--ink);
        font-size: 13px;
        font-weight: 400;
    }
    .wow-v3-offering-page .selected-location-card {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: var(--radius);
        background: var(--soft);
        border: 1px solid var(--line);
    }
    .wow-v3-offering-page .selected-location-card strong {
        display: block;
        color: var(--ink);
        font-size: 15px;
        font-weight: 500;
    }
    .wow-v3-offering-page .selected-location-card span {
        display: block;
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.4;
    }
    .wow-v3-offering-page .location-change-btn {
        min-height: 38px;
        padding: 0 12px;
        border-radius: var(--radius);
        border: 1px solid var(--line-dark);
        background: #ffffff;
        color: var(--green-dark);
        cursor: pointer;
    }
    .wow-v3-offering-page .location-strip {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    .wow-v3-offering-page .location-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 9px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--muted);
        font-size: 12px;
        cursor: pointer;
    }
    .wow-v3-offering-page .location-chip.is-active {
        color: var(--green-dark);
        border-color: #cbe5da;
        background: var(--green-soft);
    }
    .wow-v3-offering-page .location-chip::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
        opacity: 0.6;
    }
    .wow-v3-offering-page .custom-select { position: relative; }
    .wow-v3-offering-page .custom-select-trigger {
        width: 100%;
        min-height: 52px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border: 1px solid var(--line-dark);
        background: #ffffff;
        color: var(--ink);
        border-radius: var(--radius);
        cursor: pointer;
        text-align: left;
    }
    .wow-v3-offering-page .custom-select-trigger:hover,
    .wow-v3-offering-page .custom-select.is-open .custom-select-trigger {
        border-color: var(--green);
        background: var(--soft);
    }
    .wow-v3-offering-page .custom-select-value { display: grid; gap: 2px; }
    .wow-v3-offering-page .custom-select-value strong { font-size: 14px; font-weight: 400; }
    .wow-v3-offering-page .custom-select-value span { color: var(--muted); font-size: 12px; }
    .wow-v3-offering-page .custom-select-arrow {
        width: 10px;
        height: 10px;
        border-right: 1.5px solid var(--muted);
        border-bottom: 1.5px solid var(--muted);
        transform: rotate(45deg);
        transition: transform 0.18s ease;
    }
    .wow-v3-offering-page .custom-select.is-open .custom-select-arrow { transform: rotate(225deg); }
    .wow-v3-offering-page .custom-select-list {
        position: absolute;
        z-index: 20;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        display: none;
        background: #ffffff;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow-soft);
        overflow: hidden;
    }
    .wow-v3-offering-page .custom-select.is-open .custom-select-list { display: block; }
    .wow-v3-offering-page .custom-select-option {
        width: 100%;
        display: grid;
        gap: 3px;
        padding: 12px;
        border: 0;
        border-bottom: 1px solid var(--line);
        background: #ffffff;
        color: var(--ink);
        cursor: pointer;
        text-align: left;
    }
    .wow-v3-offering-page .custom-select-option:last-child { border-bottom: 0; }
    .wow-v3-offering-page .custom-select-option:hover,
    .wow-v3-offering-page .custom-select-option.is-selected { background: var(--soft); }
    .wow-v3-offering-page .custom-select-option strong { font-weight: 400; font-size: 14px; }
    .wow-v3-offering-page .custom-select-option span { color: var(--muted); font-size: 12px; }
    .wow-v3-offering-page .best-value-badge {
        display: inline-flex;
        width: fit-content;
        margin-top: 5px;
        padding: 4px 7px;
        border-radius: var(--radius);
        background: var(--green-soft);
        border: 1px solid #cbe5da;
        color: var(--green-dark);
        font-size: 11px;
        line-height: 1;
    }
    .wow-v3-offering-page .availability-mode {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 10px;
    }
    .wow-v3-offering-page .availability-card {
        min-height: 54px;
        border: 1px solid var(--line-dark);
        background: #ffffff;
        border-radius: var(--radius);
        cursor: pointer;
        color: var(--ink);
        padding: 8px;
        text-align: left;
    }
    .wow-v3-offering-page .availability-card span {
        display: block;
        margin-top: 2px;
        color: var(--muted);
        font-size: 11px;
    }
    .wow-v3-offering-page span#pickAvailabilityTitle {
        font-size: 16px;
    }
    .wow-v3-offering-page .availability-card.is-selected {
        border-color: var(--green);
        background: var(--green-soft);
    }
    .wow-v3-offering-page .selected-summary {
        margin-top: 12px;
        padding: 12px;
        border-radius: var(--radius);
        background: var(--soft);
        border: 1px solid var(--line);
    }
    .wow-v3-offering-page .selected-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        color: var(--muted);
        font-size: 12px;
    }
    .wow-v3-offering-page .selected-summary-row + .selected-summary-row { margin-top: 7px; }
    .wow-v3-offering-page .selected-summary strong { color: var(--ink); text-align: right; }
    .wow-v3-offering-page .hold-banner {
        display: none;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        padding: 12px;
        border-radius: var(--radius);
        background: #fff7e4;
        border: 1px solid #ffdca3;
        color: var(--amber);
    }
    .wow-v3-offering-page .hold-banner.is-active { display: flex; }
    .wow-v3-offering-page .hourglass {
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        animation: wowHourglassFlip 2.2s infinite ease-in-out;
        transform-origin: 50% 50%;
    }
    .wow-v3-offering-page .hourglass svg { width: 22px; height: 22px; }
    @keyframes wowHourglassFlip {
        0% { transform: rotate(0deg); }
        38% { transform: rotate(180deg); }
        50% { transform: rotate(180deg); }
        88% { transform: rotate(360deg); }
        100% { transform: rotate(360deg); }
    }
    .wow-v3-offering-page .ticket-control {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 12px;
        border-radius: var(--radius);
        background: var(--soft);
        border: 1px solid var(--line);
        margin: 14px 0;
    }
    .wow-v3-offering-page .ticket-control strong {
        display: block;
        color: var(--ink);
        font-size: 14px;
        font-weight: 400;
    }
    .wow-v3-offering-page .ticket-control small {
        display: block;
        color: var(--muted);
        font-size: 12px;
        margin-top: 3px;
    }
    .wow-v3-offering-page .ticket-control-group {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex: 1;
        padding-right: 14px;
        border-right: 1px solid var(--line);
    }
    .wow-v3-offering-page .qty {
        display: inline-flex;
        align-items: center;
        border-radius: var(--radius);
        background: #ffffff;
        border: 1px solid var(--line-dark);
    }
    .wow-v3-offering-page .qty button {
        width: 38px;
        height: 38px;
        border: 0;
        background: transparent;
        cursor: pointer;
        color: var(--ink);
        font-size: 18px;
    }
    .wow-v3-offering-page .qty span {
        min-width: 34px;
        text-align: center;
        color: var(--ink);
    }
    .wow-v3-offering-page .checkout-button {
        width: 100%;
        min-height: 54px;
        border-radius: var(--radius);
    }
    .wow-v3-offering-page .secondary-checkout {
        width: 100%;
        min-height: 50px;
        margin-top: 10px;
        margin-bottom: 10px;
        color: var(--ink);
        background: #ffffff;
        border: 1px solid var(--line-dark);
    }
    .wow-v3-offering-page .secure-note {
        margin: 13px 4px 2px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.45;
        text-align: center;
    }

    .wow-v3-offering-page .content-wrap {
        max-width: var(--max);
        margin: 34px auto 100px;
        padding: 0 20px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 380px;
        gap: 28px;
        align-items: start;
    }
    .wow-v3-offering-page .content-main { min-width: 0; }
    .wow-v3-offering-page .section {
        padding: 34px;
        margin-bottom: 22px;
        border-radius: var(--radius);
        background: #ffffff;
        border: 1px solid var(--line);
        box-shadow: var(--shadow-soft);
    }
    .wow-v3-offering-page .section.flat {
        padding: 0;
        background: transparent;
        border: 0;
        box-shadow: none;
    }
    .wow-v3-offering-page .section-heading {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 22px;
    }
    .wow-v3-offering-page .section-heading--guides {
        display: block;
    }
    .wow-v3-offering-page .section-heading--guides .guides-header-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }
    .wow-v3-offering-page .section-heading--guides .guides-header-copy {
        min-width: 0;
    }
    .wow-v3-offering-page .section-heading--guides .btn-primary {
        white-space: nowrap;
        flex-shrink: 0;
        align-self: flex-start;
        margin-top: 2px;
    }
    .wow-v3-offering-page .section-heading--guides .section-intro {
        max-width: 760px;
        margin-top: 10px;
    }
    .wow-v3-offering-page .eyebrow {
        margin: 0 0 8px;
        color: var(--green);
        font-size: 12px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }
    .wow-v3-offering-page .section h2 {
        margin: 0;
        color: var(--ink);
        font-size: clamp(26px, 3vw, 40px);
        line-height: 1;
        letter-spacing: -0.05em;
        font-weight: 500;
    }
    .wow-v3-offering-page .section-intro {
        max-width: 620px;
        color: var(--muted);
        font-size: 16px;
        line-height: 1.65;
        margin: 12px 0 0;
    }
    .wow-v3-offering-page .gallery {
        display: grid;
        gap: 12px;
    }
    .wow-v3-offering-page .gallery.gallery-count-1 {
        grid-template-columns: 1fr;
        height: 460px;
    }
    .wow-v3-offering-page .gallery.gallery-count-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        height: 460px;
    }
    .wow-v3-offering-page .gallery.gallery-count-3 {
        grid-template-columns: 1.3fr 0.7fr;
        height: 460px;
    }
    .wow-v3-offering-page .gallery-main,
    .wow-v3-offering-page .gallery-side,
    .wow-v3-offering-page .gallery-tile {
        position: relative;
        overflow: hidden;
        border-radius: var(--radius);
        background: #dcebe5;
    }
    .wow-v3-offering-page .gallery-main { min-height: 430px; }
    .wow-v3-offering-page .gallery.gallery-count-1 .gallery-main { min-height: 430px; }
    .wow-v3-offering-page .gallery.gallery-count-2 .gallery-main { min-height: 430px; }
    .wow-v3-offering-page .gallery-side { display: grid; gap: 12px; background: transparent; }
    .wow-v3-offering-page .gallery-side .gallery-tile { min-height: 209px; }
    .wow-v3-offering-page .gallery img {
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .wow-v3-offering-page .gallery-main:hover img,
    .wow-v3-offering-page .gallery-tile:hover img { transform: scale(1.035); }
    .wow-v3-offering-page .gallery-mobile-slider {
        display: none;
        position: relative;
        overflow: hidden;
        border-radius: 10px;
        background: #dcebe5;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06);
    }
    .wow-v3-offering-page .gallery-mobile-track {
        display: flex;
        height: 100%;
        transition: transform 0.35s ease;
        will-change: transform;
    }
    .wow-v3-offering-page .gallery-mobile-slide {
        position: relative;
        flex: 0 0 100%;
        min-width: 100%;
        overflow: hidden;
    }
    .wow-v3-offering-page .gallery-mobile-slide img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }
    .wow-v3-offering-page .gallery-mobile-nav,
    .wow-v3-offering-page .gallery-mobile-dots {
        display: none;
    }
    .wow-v3-offering-page .quick-info-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
    }
    .wow-v3-offering-page .quick-info-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        padding: 20px;
        text-align: center;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }
    .wow-v3-offering-page .quick-info-card:hover {
        transform: translateY(-1px);
        border-color: #cbe5da;
        box-shadow: var(--shadow-soft);
    }
    .wow-v3-offering-page .quick-info-card > div {
        display: grid;
        gap: 3px;
        justify-items: center;
    }
    .wow-v3-offering-page .quick-info-icon {
        width: 58px;
        height: 58px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--line);
        border-radius: 50%;
        background: #ffffff;
        color: var(--green);
        box-shadow: 0 10px 24px rgba(25, 53, 44, 0.08);
    }
    .wow-v3-offering-page .quick-info-icon--duration {
        width: 60px;
        height: 60px;
    }
    .wow-v3-offering-page .quick-info-icon--format {
        width: 60px;
        height: 60px;
    }
    .wow-v3-offering-page .quick-info-icon svg,
    .wow-v3-offering-page .quick-info-icon img {
        width: 28px;
        height: 28px;
        display: block;
        object-fit: contain;
    }
    .wow-v3-offering-page .quick-info-icon--duration svg {
        width: 60px;
        height: 60px;
    }
    .wow-v3-offering-page .quick-info-icon--format img {
        width: 60px;
        height: 60px;
    }
    .wow-v3-offering-page .quick-info-card small {
        margin-bottom: 0;
        color: var(--muted);
        font-size: 11px;
        line-height: 1.1;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }
    .wow-v3-offering-page .quick-info-card strong {
        max-width: 120px;
        color: var(--ink);
        font-size: 12px;
        line-height: 1.08;
        letter-spacing: -0.035em;
        font-weight: 500;
    }
    .wow-v3-offering-page .hero-meta .quick-info-card { min-height: auto; }
    .wow-v3-offering-page .hero-meta .quick-info-card strong {
        max-width: none;
        font-size: 13px;
    }
    .wow-v3-offering-page .split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .wow-v3-offering-page .info-panel {
        padding: 24px;
        border-radius: var(--radius);
        background: var(--soft);
        border: 1px solid var(--line);
    }
    .wow-v3-offering-page .info-panel h3 {
        margin: 0 0 12px;
        color: var(--ink);
        font-size: 22px;
        letter-spacing: -0.04em;
        font-weight: 500;
    }
    .wow-v3-offering-page .info-panel p,
    .wow-v3-offering-page .info-panel li,
    .wow-v3-offering-page .practitioner-rich-text,
    .wow-v3-offering-page .practitioner-rich-text li {
        color: var(--muted);
        line-height: 1.7;
        font-size: 15px;
    }
    .wow-v3-offering-page .info-panel ul,
    .wow-v3-offering-page .practitioner-rich-text ul {
        list-style: none;
        margin: 14px 0 0;
        padding-left: 0;
    }
    .wow-v3-offering-page .info-panel li,
    .wow-v3-offering-page .practitioner-rich-text li {
        position: relative;
        margin: 0 0 10px;
        padding-left: 24px;
    }
    .wow-v3-offering-page .info-panel ul > li::before,
    .wow-v3-offering-page .practitioner-rich-text ul > li::before {
        content: "";
        position: absolute;
        top: 0.7em;
        left: 2px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--green);
        box-shadow: 0 0 0 5px var(--green-soft);
        transform: translateY(-50%);
    }
    .wow-v3-offering-page .practitioner-rich-text {
        color: var(--muted);
        font-size: 15px;
        line-height: 1.75;
        overflow-wrap: anywhere;
    }
    .wow-v3-offering-page .practitioner-rich-text p { margin: 0 0 14px; }
    .wow-v3-offering-page .practitioner-rich-text h3,
    .wow-v3-offering-page .practitioner-rich-text h4,
    .wow-v3-offering-page .practitioner-rich-text h5,
    .wow-v3-offering-page .practitioner-rich-text h6 {
        color: var(--ink);
        margin: 24px 0 10px;
        line-height: 1.12;
        letter-spacing: -0.035em;
        font-weight: 500;
    }
    .wow-v3-offering-page .practitioner-rich-text h3 { font-size: clamp(22px, 2vw, 28px); }
    .wow-v3-offering-page .practitioner-rich-text h4 { font-size: 21px; }
    .wow-v3-offering-page .practitioner-rich-text h5 { font-size: 18px; }
    .wow-v3-offering-page .practitioner-rich-text h6 {
        font-size: 15px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--green-dark);
    }
    .wow-v3-offering-page .practitioner-rich-text blockquote {
        margin: 22px 0;
        padding: 18px 20px;
        border-left: 4px solid var(--green);
        background: var(--soft);
        color: var(--green-dark);
        border-radius: var(--radius);
    }
    .wow-v3-offering-page .practitioner-rich-text blockquote p:last-child { margin-bottom: 0; }
    .wow-v3-offering-page .practitioner-rich-text a {
        color: var(--green-dark);
        text-decoration: underline;
        text-decoration-color: rgba(84, 148, 131, 0.45);
        text-decoration-thickness: 2px;
        text-underline-offset: 3px;
    }
    .wow-v3-offering-page .practitioner-rich-text a:hover { color: var(--green); }
    .wow-v3-offering-page .practitioner-rich-text table {
        width: 100%;
        margin: 22px 0;
        border-collapse: collapse;
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #ffffff;
    }
    .wow-v3-offering-page .practitioner-rich-text th,
    .wow-v3-offering-page .practitioner-rich-text td {
        padding: 12px;
        border: 1px solid var(--line);
        text-align: left;
        vertical-align: top;
    }
    .wow-v3-offering-page .practitioner-rich-text th {
        color: var(--ink);
        background: var(--soft);
        font-weight: 500;
    }
    .wow-v3-offering-page .practitioner-rich-text pre {
        max-width: 100%;
        overflow-x: auto;
        margin: 22px 0;
        padding: 16px;
        border-radius: var(--radius);
        background: #10251f;
        color: #e9fff6;
        border: 1px solid rgba(16, 37, 31, 0.2);
    }
    .wow-v3-offering-page .practitioner-rich-text code {
        padding: 0.16em 0.35em;
        border-radius: var(--radius);
        background: var(--green-soft);
        color: var(--green-dark);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: 0.92em;
    }
    .wow-v3-offering-page .practitioner-rich-text pre code {
        padding: 0;
        background: transparent;
        color: inherit;
    }
    .wow-v3-offering-page .guide-card-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }
    .wow-v3-offering-page .guide-card {
        display: block;
        min-height: 148px;
        padding: 18px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }
    .wow-v3-offering-page .guide-card:hover {
        transform: translateY(-1px);
        border-color: #cbe5da;
        box-shadow: var(--shadow-soft);
    }
    .wow-v3-offering-page .guide-card strong {
        display: block;
        color: var(--ink);
        font-size: 16px;
        line-height: 1.25;
        font-weight: 500;
    }
    .wow-v3-offering-page .guide-card span {
        display: block;
        margin-top: 10px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.5;
    }
    .wow-v3-offering-page .map-section-layout {
        display: grid;
        grid-template-columns: 0.75fr 1.25fr;
        gap: 12px;
    }
    .wow-v3-offering-page .location-list {
        display: block;
        max-height: 360px;
        overflow-y: auto;
        padding-right: 6px;
        margin-bottom: 10px;
        scrollbar-width: thin;
        scrollbar-color: rgba(84, 148, 131, 0.45) transparent;
    }
    .wow-v3-offering-page .location-list::-webkit-scrollbar { width: 6px; }
    .wow-v3-offering-page .location-list::-webkit-scrollbar-thumb {
        background: rgba(84, 148, 131, 0.45);
        border-radius: var(--radius);
    }
    .wow-v3-offering-page .location-card {
        width: 100%;
        margin-bottom: 10px;
        padding: 18px;
        border-radius: var(--radius);
        background: var(--soft);
        border: 1px solid var(--line);
        text-align: left;
        cursor: pointer;
    }
    .wow-v3-offering-page .location-card:last-child { margin-bottom: 0; }
    .wow-v3-offering-page .location-card h3 {
        margin: 0 0 8px;
        font-size: 18px;
        font-weight: 500;
    }
    .wow-v3-offering-page .location-card p {
        margin: 0;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.55;
    }
    .wow-v3-offering-page .map-online-video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
        background: #10251f;
    }
    .wow-v3-offering-page .map-box {
        position: relative;
        min-height: 360px;
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--line);
        background: #eaf3ee;
    }
    .wow-v3-offering-page .mapbox-map {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
    .wow-v3-offering-page .mapboxgl-map { font-family: inherit; }
    .wow-v3-offering-page .mapboxgl-ctrl-logo,
    .wow-v3-offering-page .mapboxgl-ctrl-attrib {
        transform: scale(0.82);
        transform-origin: bottom left;
    }
    .wow-v3-offering-page .map-box.is-online-mode .map-online-video {
        display: block;
    }
    .wow-v3-offering-page .map-box.is-online-mode .map-online-overlay {
        display: flex;
    }
    .wow-v3-offering-page .map-box.is-online-mode .mapbox-map {
        display: none;
    }
    .wow-v3-offering-page .wow-map-marker {
        width: 34px;
        height: 34px;
        background: var(--green);
        border: 4px solid #ffffff;
        box-shadow: 0 10px 28px rgba(7, 29, 51, 0.24);
        transform: rotate(45deg);
        border-radius: 50% 50% 50% 0;
    }
    .wow-v3-offering-page .wow-map-marker::after {
        content: "";
        position: absolute;
        width: 9px;
        height: 9px;
        left: 50%;
        top: 50%;
        background: #ffffff;
        transform: translate(-50%, -50%);
        border-radius: 50%;
    }
    .wow-v3-offering-page .side-stack {
        position: sticky;
        top: 136px;
        display: grid;
        gap: 16px;
        align-self: start;
    }
    .wow-v3-offering-page .side-card {
        padding: 22px;
        border-radius: var(--radius);
        background: #ffffff;
        border: 1px solid var(--line);
        box-shadow: var(--shadow-soft);
    }
    .wow-v3-offering-page .side-card h3 {
        margin: 0 0 14px;
        color: var(--ink);
        font-size: 19px;
        letter-spacing: -0.03em;
        font-weight: 500;
    }
    .wow-v3-offering-page .mini-list { display: grid; gap: 12px; }
    .wow-v3-offering-page .mini-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--line);
        color: var(--muted);
        font-size: 14px;
    }
    .wow-v3-offering-page .mini-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }
    .wow-v3-offering-page .mini-row strong {
        color: var(--ink);
        text-align: right;
    }
    .wow-v3-offering-page .practitioner-side-card { background: var(--soft); }
    .wow-v3-offering-page .practitioner-side-head {
        display: grid;
        grid-template-columns: 64px 1fr;
        gap: 12px;
        align-items: center;
    }
    .wow-v3-offering-page .practitioner-side-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #ffffff;
        box-shadow: var(--shadow-soft);
    }
    .wow-v3-offering-page .side-eyebrow {
        display: block;
        margin-bottom: 5px;
        color: var(--green);
        font-size: 11px;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }
    .wow-v3-offering-page .practitioner-side-card p {
        margin: 14px 0 0;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.6;
    }
    .wow-v3-offering-page .practitioner-credentials {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }
    .wow-v3-offering-page .credential-group {
        padding: 12px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #ffffff;
    }
    .wow-v3-offering-page .credential-group span {
        display: block;
        color: var(--muted);
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .wow-v3-offering-page .credential-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }
    .wow-v3-offering-page .credential-tag {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 5px 8px;
        border: 1px solid #cbe5da;
        border-radius: var(--radius);
        background: var(--green-soft);
        color: var(--green-dark);
        font-size: 12px;
        line-height: 1.2;
    }
    .wow-v3-offering-page .practitioner-profile-link {
        margin-top: 14px;
        width: fit-content;
        min-height: 38px;
        margin-left: auto;
        padding: 0 14px;
        justify-self: end;
        font-size: 13px;
    }

    .wow-v3-offering-page .mobile-ticket-bar {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 800;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 10px;
        background: #ffffff;
        border-top: 1px solid var(--line-dark);
        box-shadow: 0 18px 44px rgba(7, 29, 51, 0.18);
    }
    .wow-v3-offering-page .mobile-ticket-bar.is-visible { display: flex; }
    .wow-v3-offering-page .mobile-ticket-bar strong {
        display: block;
        color: var(--ink);
        font-size: 20px;
        font-weight: 600 !important;
        letter-spacing: -0.03em;
    }
    .wow-v3-offering-page .mobile-ticket-bar span {
        color: var(--muted);
        font-size: 12px;
    }
    .wow-v3-offering-page .mobile-ticket-bar .checkout-button {
        width: auto;
        min-width: 124px;
        min-height: 48px;
    }
    @media (max-width: 720px) {
        .wow-v3-offering-page .mobile-ticket-bar {
            display: flex;
        }
    }
    .wow-v3-offering-page .modal-backdrop,
    .wow-v3-offering-page .calendar-modal-backdrop,
    .wow-v3-offering-page .location-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 900;
        display: none;
        background: rgba(14, 34, 27, 0.56);
    }
    .wow-v3-offering-page .modal-backdrop.is-open,
    .wow-v3-offering-page .calendar-modal-backdrop.is-open,
    .wow-v3-offering-page .location-modal-backdrop.is-open { display: block; }
    .wow-v3-offering-page .booking-modal,
    .wow-v3-offering-page .date-time-modal,
    .wow-v3-offering-page .location-modal {
        position: fixed;
        z-index: 1000;
        display: none;
        background: #ffffff;
        border: 1px solid var(--line);
        box-shadow: 0 24px 80px rgba(7, 29, 51, 0.22);
    }
    .wow-v3-offering-page .booking-modal.is-open,
    .wow-v3-offering-page .date-time-modal.is-open,
    .wow-v3-offering-page .location-modal.is-open { display: block; }
    .wow-v3-offering-page .booking-modal {
        left: 0;
        right: 0;
        bottom: 0;
        max-height: 92vh;
        overflow: auto;
        border-radius: var(--radius) var(--radius) 0 0;
    }
    .wow-v3-offering-page .booking-modal-head,
    .wow-v3-offering-page .date-time-modal-head,
    .wow-v3-offering-page .location-modal-head {
        position: sticky;
        top: 0;
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: #ffffff;
        border-bottom: 1px solid var(--line);
    }
    .wow-v3-offering-page .booking-modal-head h3,
    .wow-v3-offering-page .date-time-modal-head h3,
    .wow-v3-offering-page .location-modal-head h3 {
        margin: 0;
        color: var(--ink);
        font-size: 20px;
        letter-spacing: -0.03em;
        font-weight: 500;
    }
    .wow-v3-offering-page .modal-kicker {
        display: block;
        margin-bottom: 4px;
        color: var(--muted);
        font-size: 14px;
        letter-spacing: 0;
    }
    .wow-v3-offering-page .booking-modal-close,
    .wow-v3-offering-page .date-time-modal-close,
    .wow-v3-offering-page .location-modal-close {
        width: 40px;
        height: 40px;
        border-radius: var(--radius);
        border: 1px solid var(--line-dark);
        background: #ffffff;
        color: var(--ink);
        font-size: 22px;
        cursor: pointer;
    }
    .wow-v3-offering-page .booking-modal-body,
    .wow-v3-offering-page .location-modal-body {
        padding: 16px;
    }
    .wow-v3-offering-page .booking-modal-footer,
    .wow-v3-offering-page .date-time-modal-footer,
    .wow-v3-offering-page .location-modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 2;
        padding: 12px 16px 16px;
        background: #ffffff;
        border-top: 1px solid var(--line);
    }
    .wow-v3-offering-page .modal-fields-slot { display: contents; }
    .wow-v3-offering-page .date-time-modal {
        left: 50%;
        top: 50%;
        width: min(94vw, 980px);
        max-height: 90vh;
        overflow: hidden;
        border-radius: var(--radius);
        transform: translate(-50%, -50%);
    }
    .wow-v3-offering-page .date-time-modal-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 430px;
    }
    .wow-v3-offering-page .date-time-calendar-panel,
    .wow-v3-offering-page .date-time-slots-panel {
        padding: 22px;
    }
    .wow-v3-offering-page .date-time-calendar-panel { border-right: 1px solid var(--line); }
    .wow-v3-offering-page .date-time-calendar-head {
        display: grid;
        grid-template-columns: 44px 1fr 44px;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }
    .wow-v3-offering-page .calendar-nav-btn {
        width: 44px;
        height: 44px;
        border: 1px solid var(--line-dark);
        background: #ffffff;
        color: var(--ink);
        border-radius: var(--radius);
        cursor: pointer;
        font-size: 24px;
    }
    .wow-v3-offering-page .date-time-calendar-title {
        text-align: center;
        color: var(--ink);
        font-size: 21px;
        font-weight: 500;
    }
    .wow-v3-offering-page .date-time-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 9px;
    }
    .wow-v3-offering-page .date-time-day-name {
        text-align: center;
        color: var(--muted);
        font-size: 12px;
        text-transform: uppercase;
    }
    .wow-v3-offering-page .date-time-date {
        min-height: 54px;
        border-radius: var(--radius);
        border: 1px solid var(--line);
        background: #ffffff;
        color: var(--ink);
        cursor: pointer;
        font-size: 18px;
    }
    .wow-v3-offering-page .date-time-date:hover {
        border-color: var(--green);
        background: var(--soft);
    }
    .wow-v3-offering-page .date-time-date.is-disabled {
        color: #c4ccc8;
        background: #f8fbf9;
        cursor: not-allowed;
    }
    .wow-v3-offering-page .date-time-date.is-selected {
        border-color: var(--green);
        background: var(--green-soft);
        box-shadow: inset 0 0 0 2px var(--green);
    }
    .wow-v3-offering-page .timezone-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 16px;
        color: var(--muted);
        font-size: 14px;
    }
    .wow-v3-offering-page .timezone-pill {
        padding: 7px 10px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--ink);
    }
    .wow-v3-offering-page .date-time-slots-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 4px 0 16px;
        color: var(--ink);
        font-size: 22px;
        font-weight: 500;
    }
    .wow-v3-offering-page .time-options {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--line);
    }
    .wow-v3-offering-page .time-option {
        min-width: 86px;
        min-height: 52px;
        padding: 0 16px;
        border: 1px solid var(--line);
        background: #ffffff;
        color: var(--ink);
        border-radius: var(--radius);
        cursor: pointer;
        font-size: 18px;
    }
    .wow-v3-offering-page .time-option:hover,
    .wow-v3-offering-page .time-option.is-selected {
        border-color: var(--green);
        background: var(--green-soft);
    }
    .wow-v3-offering-page .time-option.is-disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .wow-v3-offering-page .date-time-helper {
        margin: 18px 0 0;
        color: var(--muted);
        font-size: 18px;
        line-height: 1.5;
    }
    .wow-v3-offering-page .date-time-modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    .wow-v3-offering-page .date-time-footer-note { color: var(--muted); font-size: 15px; }
    .wow-v3-offering-page .date-time-footer-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .wow-v3-offering-page .date-time-confirm[disabled] {
        opacity: 0.45;
        cursor: not-allowed;
        transform: none;
    }
    .wow-v3-offering-page .date-time-confirm {
        white-space: nowrap;
    }
    .wow-v3-offering-page .location-modal {
        left: 50%;
        top: 50%;
        width: min(94vw, 980px);
        max-height: 88vh;
        overflow: auto;
        border-radius: var(--radius);
        transform: translate(-50%, -50%);
    }
    .wow-v3-offering-page .location-modal-layout {
        display: grid;
        grid-template-columns: minmax(260px, 0.82fr) minmax(340px, 1.18fr);
        gap: 14px;
        align-items: stretch;
    }
    .wow-v3-offering-page .location-modal-options {
        display: block;
        max-height: 440px;
        overflow-y: auto;
        padding-right: 6px;
        scrollbar-width: thin;
        scrollbar-color: rgba(84, 148, 131, 0.45) transparent;
    }
    .wow-v3-offering-page .location-modal-options::-webkit-scrollbar { width: 6px; }
    .wow-v3-offering-page .location-modal-options::-webkit-scrollbar-thumb {
        background: rgba(84, 148, 131, 0.45);
        border-radius: var(--radius);
    }
    .wow-v3-offering-page .location-modal-options .location-option { margin-bottom: 10px; }
    .wow-v3-offering-page .location-modal-options .location-option:last-child { margin-bottom: 0; }
    .wow-v3-offering-page .location-option {
        width: 100%;
        min-height: 78px;
        text-align: left;
        cursor: pointer;
        background: #ffffff;
        border: 1px solid var(--line-dark);
        border-radius: var(--radius);
        color: var(--ink);
        padding: 13px;
        transition: border-color 0.18s ease, background 0.18s ease;
    }
    .wow-v3-offering-page .location-option:hover,
    .wow-v3-offering-page .location-option.is-selected {
        border-color: var(--green);
        background: var(--soft);
    }
    .wow-v3-offering-page .option-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 15px;
    }
    .wow-v3-offering-page .option-pill {
        display: inline-flex;
        padding: 4px 7px;
        border: 1px solid #cbe5da;
        background: var(--green-soft);
        color: var(--green-dark);
        font-size: 11px;
        border-radius: var(--radius);
    }
    .wow-v3-offering-page .option-sub {
        display: block;
        margin-top: 5px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.45;
    }
    .wow-v3-offering-page .location-modal-map-card {
        position: relative;
        min-height: 440px;
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #eaf3ee;
        box-shadow: var(--shadow-soft);
    }
    .wow-v3-offering-page .location-modal-map-card .map-online-video {
        display: none;
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        background: #10251f;
    }
    .wow-v3-offering-page .location-modal-map {
        position: absolute;
        inset: 0;
    }
    .wow-v3-offering-page .location-modal-map-card.is-online .map-online-video {
        display: block;
    }
    .wow-v3-offering-page .location-modal-map-caption {
        position: absolute;
        left: 12px;
        right: 12px;
        bottom: 12px;
        z-index: 2;
        padding: 12px;
        border: 1px solid rgba(219, 232, 225, 0.9);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: var(--shadow-soft);
        backdrop-filter: blur(12px);
    }
    .wow-v3-offering-page .location-modal-map-caption span {
        display: block;
        color: var(--ink);
        font-size: 15px;
        font-weight: 500;
    }
    .wow-v3-offering-page .location-modal-map-caption small {
        display: block;
        margin-top: 4px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.4;
    }
    .wow-v3-offering-page .map-online-overlay {
        position: absolute;
        inset: 0;
        z-index: 2;
        display: none;
        align-items: end;
        padding: 20px;
        background: linear-gradient(180deg, rgba(14, 34, 27, 0) 0%, rgba(14, 34, 27, 0.42) 100%);
        pointer-events: none;
    }
    .wow-v3-offering-page .map-online-overlay > div {
        max-width: 320px;
        padding: 16px;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(255, 255, 255, 0.36);
        box-shadow: var(--shadow-soft);
    }
    .wow-v3-offering-page .map-online-overlay strong {
        display: block;
        color: var(--ink);
        font-size: 16px;
        margin-bottom: 6px;
    }
    .wow-v3-offering-page .map-online-overlay span {
        display: block;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.45;
    }
    .wow-v3-offering-page .map-box.is-online .map-online-overlay,
    .wow-v3-offering-page .location-modal-map-card.is-online .map-online-overlay { display: flex; }
    .wow-v3-offering-page .map-box.is-online .mapbox-map,
    .wow-v3-offering-page .location-modal-map-card.is-online .location-modal-map,
    .wow-v3-offering-page .location-modal-map-card.is-online .location-modal-map-caption {
        opacity: 0;
        pointer-events: none;
    }
    .wow-v3-offering-page .location-modal-map-card.is-online .location-modal-map {
        display: none;
    }

    @media (max-width: 980px) {
        .wow-v3-offering-page .hero-content,
        .wow-v3-offering-page .content-wrap { grid-template-columns: 1fr; }
        .wow-v3-offering-page .hero-content { padding: 28px; }
        .wow-v3-offering-page .side-stack { position: relative; top: auto; }
        .wow-v3-offering-page .hero-meta,
        .wow-v3-offering-page .quick-info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .wow-v3-offering-page .gallery,
        .wow-v3-offering-page .split,
        .wow-v3-offering-page .guide-card-grid,
        .wow-v3-offering-page .map-section-layout,
        .wow-v3-offering-page .location-modal-layout { grid-template-columns: 1fr; }
    }

    @media (max-width: 720px) {
        .wow-v3-offering-page .hero-wrap {
            margin: 0 auto;
        }
        .wow-v3-offering-page .booking-panel {
            display: none !important;
        }
        .wow-v3-offering-page .page-nav { display: none; }
        .wow-v3-offering-page .hero { min-height: auto; overflow: visible; background: transparent; box-shadow: none; }
        .wow-v3-offering-page .hero-slide,
        .wow-v3-offering-page .hero-overlay,
        .wow-v3-offering-page .hero-watermark,
        .wow-v3-offering-page .hero-review-badge { display: none; }
        .wow-v3-offering-page .hero-content {
            display: block;
            min-height: auto;
            padding: 0;
        }
        .wow-v3-offering-page .hero-main {
            max-width: none;
            padding: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
            backdrop-filter: none;
            border-radius: 0;
        }
        .wow-v3-offering-page .pill {
            background: var(--green-soft);
            border-color: #cbe5da;
            color: var(--green-dark);
        }
        .wow-v3-offering-page .hero h1 {
            color: var(--ink);
            font-size: 40px;
            line-height: 0.96;
        }
        .wow-v3-offering-page .hero-actions { display: none; }
        .wow-v3-offering-page .hero-meta { display: none; }
        .wow-v3-offering-page .quick-glance-section { display: block; }
        .wow-v3-offering-page .quick-glance-section .section-heading { display: none; }
        .wow-v3-offering-page .store-v3-quick-glance .section-heading { display: flex; }
        .wow-v3-offering-page .store-v3-mobile-product-image{display:block;margin:22px 0 0;overflow:hidden;border:1px solid var(--line);border-radius:var(--radius);background:var(--soft)}
        .wow-v3-offering-page .store-v3-mobile-product-image img{display:block;width:100%;height:260px;object-fit:cover}
        .wow-v3-offering-page .store-v3-desktop-gallery { display: none; }
        .wow-v3-offering-page .content-main {
            display: flex;
            flex-direction: column;
        }
        .wow-v3-offering-page .section.flat {
            order: -2;
            padding: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
        }
        .wow-v3-offering-page section.section.flat {
            border: none !important;
            box-shadow: none !important;
            padding: 0px !important;
            background: transparent !important;
        }
        .wow-v3-offering-page .gallery {
            display: none;
        }
        .wow-v3-offering-page .gallery-mobile-slider {
            display: block;
            aspect-ratio: 1.43 / 1;
            margin-bottom: 16px;
        }
        .wow-v3-offering-page .gallery-mobile-track {
            height: 100%;
        }
        .wow-v3-offering-page .gallery-mobile-slide {
            height: 100%;
        }
        .wow-v3-offering-page .gallery-mobile-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.96);
            color: #101827;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.14);
            display: grid;
            place-items: center;
            font-size: 24px;
            z-index: 5;
        }
        .wow-v3-offering-page .gallery-mobile-nav--prev { left: 10px; }
        .wow-v3-offering-page .gallery-mobile-nav--next { right: 10px; }
        .wow-v3-offering-page .gallery-mobile-dots {
            position: absolute;
            left: 50%;
            bottom: 12px;
            transform: translateX(-50%);
            z-index: 6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 25px;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        }
        .wow-v3-offering-page .gallery-mobile-dots button {
            width: 7px;
            height: 7px;
            border-radius: 99px;
            border: 0;
            background: rgba(17, 39, 32, 0.36);
            padding: 0;
        }
        .wow-v3-offering-page .gallery-mobile-dots button.is-active {
            width: 18px;
            background: var(--green);
        }
        .wow-v3-offering-page .quick-glance-section {
            order: -1;
        }
        .wow-v3-offering-page .section-heading--guides {
            gap: 12px;
        }
        .wow-v3-offering-page .section-heading--guides .guides-header-row {
            gap: 12px;
        }
        .wow-v3-offering-page .desktop-booking-buttons { display: none !important; }
        .wow-v3-offering-page .section {
            padding: 22px;
            margin-bottom: 16px;
        }
        .wow-v3-offering-page .gallery {
            grid-template-columns: 1fr;
            height: auto;
        }
        .wow-v3-offering-page .gallery-main { min-height: 330px; }
        .wow-v3-offering-page .gallery-side .gallery-tile { min-height: 230px; }
        .wow-v3-offering-page .quick-info-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
        }
        .wow-v3-offering-page .quick-info-card {
            height: auto !important;
            min-height: auto;
            border: none;
            padding: 0;
            gap: 0;
            border-radius: var(--radius);
            background: none;
            box-shadow: none;
        }
        .wow-v3-offering-page .quick-info-card:hover {
            transform: none;
            border-color: transparent;
            box-shadow: none;
        }
        .wow-v3-offering-page .quick-info-card small {
            display: none;
        }
        .wow-v3-offering-page .quick-info-card strong {
            font-size: 12px;
            line-height: 1.15;
        }
        .wow-v3-offering-page .quick-info-icon {
            width: 56px !important;
            height: 56px !important;
        }
        .wow-v3-offering-page .quick-info-icon--duration,
        .wow-v3-offering-page .quick-info-icon--format {
            width: 56px !important;
            height: 56px !important;
        }
        .wow-v3-offering-page .quick-info-icon svg,
        .wow-v3-offering-page .quick-info-icon img {
            width: 56px !important;
            height: 56px !important;
        }
        .wow-v3-offering-page .quick-info-icon--duration svg,
        .wow-v3-offering-page .quick-info-icon--format img {
            width: 56px !important;
            height: 56px !important;
        }
        .wow-v3-offering-page .quick-info-card small,
        .wow-v3-offering-page .quick-info-card strong {
            display: none;
        }
        .wow-v3-offering-page .section {
            margin-bottom: 16px;
        }
        .wow-v3-offering-page .section:not(.quick-glance-section) {
            padding: 22px;
            background: #ffffff;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-soft);
            border-radius: var(--radius);
        }
        .wow-v3-offering-page .quick-glance-section {
            padding: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
        }
        .wow-v3-offering-page .map-box { min-height: 320px; }
        .wow-v3-offering-page .location-list { max-height: 260px; }
        .wow-v3-offering-page .practitioner-profile-link {
            width: fit-content;
            min-height: 38px;
            margin: 14px 0 0 auto;
            padding: 0 14px;
            justify-self: end;
            align-self: end;
        }
        .wow-v3-offering-page .booking-modal,
        .wow-v3-offering-page .date-time-modal,
        .wow-v3-offering-page .location-modal {
            left: 0;
            right: 0;
            bottom: 0;
            top: auto;
            width: auto;
            max-height: 92vh;
            overflow: auto;
            border-radius: var(--radius) var(--radius) 0 0;
            transform: none;
        }
        .wow-v3-offering-page .date-time-modal-body {
            display: block;
            min-height: 0;
        }
        .wow-v3-offering-page .date-time-calendar-panel {
            border-right: 0;
            border-bottom: 1px solid var(--line);
            padding: 16px;
        }
        .wow-v3-offering-page .date-time-slots-panel { padding: 16px; }
        .wow-v3-offering-page .date-time-date {
            min-height: 44px;
            font-size: 16px;
        }
        .wow-v3-offering-page .time-option {
            min-width: 78px;
            min-height: 48px;
            font-size: 17px;
        }
        .wow-v3-offering-page .date-time-modal-footer {
            align-items: stretch;
            flex-direction: column;
        }
        .wow-v3-offering-page .date-time-footer-actions {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 10px;
        }
        .wow-v3-offering-page .date-time-confirm {
            font-size: 14px;
        }
        .wow-v3-offering-page .location-modal-body { padding-bottom: 4px; }
        .wow-v3-offering-page .location-modal-layout {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .wow-v3-offering-page .location-modal-map-card {
            order: -1;
            min-height: 230px;
            box-shadow: none;
        }
        .wow-v3-offering-page .location-modal-options {
            max-height: 320px;
            padding-right: 4px;
        }
        .wow-v3-offering-page .location-modal-map-caption {
            left: 10px;
            right: 10px;
            bottom: 10px;
            padding: 10px;
        }
        body.wow-v3-mobile-ticket-visible { padding-bottom: 82px; }
    }
    .store-v3-reviews .offering-reviews__header{display:flex;align-items:flex-start;justify-content:space-between;gap:24px}
    .store-v3-reviews .offering-reviews__header h2{margin:0;color:var(--ink);font-size:clamp(28px,3vw,42px);letter-spacing:-.035em}
    .wow-v3-offering-page .store-v3-reviews h2{font-family:inherit;font-size:clamp(26px,3vw,40px);line-height:1;letter-spacing:-.05em;font-weight:500;color:var(--ink)}
    .store-v3-reviews .offering-reviews__header .kicker{margin:0 0 8px;color:var(--green);font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}
    .store-v3-reviews .offering-reviews__badge{display:inline-flex;align-items:center;padding:10px 14px;border:1px solid var(--line);border-radius:3px;background:var(--soft);color:var(--green-dark);font-size:13px;font-weight:700;white-space:nowrap}
    .store-v3-review-empty{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:24px;padding:24px;border:1px solid var(--line);border-radius:3px;background:var(--soft);color:var(--ink)}
    .store-v3-review-empty div{display:flex;flex-direction:column;gap:6px}.store-v3-review-empty span{color:var(--muted);font-size:13px}.store-v3-review-empty .btn{flex:0 0 auto}
    .store-v3-mobile-product-image{display:none}
    .store-v3-desktop-gallery{display:block}
    .wow-v3-product-only .hero-wrap{min-height:0;background:transparent}
    .wow-v3-product-only .hero{min-height:0;height:auto;background:transparent;box-shadow:none}
    .wow-v3-product-only .hero-slide,.wow-v3-product-only .hero-overlay,.wow-v3-product-only .hero-watermark{display:none}
    .wow-v3-product-only .hero-content{max-width:1280px;padding:0;margin:0 auto;display:grid;grid-template-columns:minmax(280px,1fr) minmax(360px,.8fr);gap:clamp(28px,6vw,84px);align-items:start}
    .wow-v3-product-only .hero-main{display:block;min-width:0}
    .wow-v3-product-only .store-v3-product-only-image{display:flex;align-items:center;justify-content:center;order:-1;margin:0;min-height:clamp(360px,62vh,680px);border:1px solid var(--line);border-radius:var(--radius);background:#fff;overflow:hidden}
    .wow-v3-product-only .store-v3-product-only-image img{width:100%;height:100%;max-height:680px;object-fit:contain}
    .wow-v3-product-only .hero-content>.booking-panel{grid-column:2;grid-row:1}
    .wow-v3-product-only .content-wrap{display:none}
    .wow-v3-product-only .hero-review-badge{display:none}
    @media(max-width:760px){
        .wow-v3-product-only .hero-wrap,.wow-v3-product-only .hero{min-height:0}
        .wow-v3-product-only .hero-content{padding:24px 16px 34px}
        .wow-v3-product-only .hero-content{display:flex;flex-direction:column;align-items:stretch;gap:22px}
        .wow-v3-product-only .hero-main{display:block}
        .wow-v3-product-only .store-v3-product-only-image{order:0;min-height:360px}
        .wow-v3-product-only .hero-content>.booking-panel{order:3}
    }
    .store-v3-review-actions{display:flex!important;flex-direction:row!important;gap:10px!important}.store-v3-review-composer{display:grid;grid-template-columns:minmax(220px,.7fr) minmax(0,1.3fr);gap:28px;margin-top:24px;padding:24px;border:1px solid var(--line);border-radius:3px;background:#fff;box-shadow:var(--shadow)}.store-v3-review-composer h3{margin:0;color:var(--ink);font-size:22px}.store-v3-review-composer p:not(.eyebrow){color:var(--muted);font-size:13px}.store-v3-review-composer form{display:grid;gap:12px}.store-v3-review-stars{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px}.store-v3-review-stars input{position:absolute;opacity:0}.store-v3-review-stars span{color:#c9d2ce;font-size:28px;cursor:pointer}.store-v3-review-stars label:hover span,.store-v3-review-stars label:has(input:checked) span,.store-v3-review-stars label:has(~ label input:checked) span{color:#d49a2a}.store-v3-review-input{width:100%;box-sizing:border-box;border:1px solid var(--line);border-radius:3px;padding:12px 14px;background:#fff;color:var(--ink);font:inherit}.store-v3-review-input:focus{outline:2px solid rgba(84,148,131,.25);border-color:var(--green)}
    .store-v3-checkout-panel .store-v3-compare-price{margin-top:6px;color:#98a29e;font-size:15px;font-weight:700;text-decoration:line-through;text-decoration-thickness:1.5px;text-decoration-color:#98a29e}
    .store-v3-variant-picker{display:grid;gap:8px;margin:20px 0 0}.store-v3-variant-picker>span{color:var(--muted);font-size:12px;font-weight:700}.store-v3-variant-options{display:grid;gap:8px}.store-v3-variant-option{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:3px;background:#fff;color:var(--ink);text-align:left;cursor:pointer}.store-v3-variant-option:hover,.store-v3-variant-option.is-selected{border-color:var(--green);box-shadow:inset 0 0 0 1px var(--green)}.store-v3-variant-option.is-selected{background:var(--green-pale)}.store-v3-variant-option[disabled]{cursor:not-allowed;opacity:.5}.store-v3-variant-option strong{font-size:13px}.store-v3-variant-option small{color:var(--muted);font-size:12px;white-space:nowrap}
    .store-v3-purchase-summary{display:grid;gap:0;margin:22px 0 20px;border:1px solid var(--line);border-radius:3px;background:var(--soft)}
    .store-v3-purchase-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:13px 14px;border-bottom:1px solid var(--line)}
    .store-v3-purchase-row:last-child{border-bottom:0}.store-v3-purchase-row span{color:var(--muted);font-size:12px}.store-v3-purchase-row strong{max-width:68%;color:var(--ink);font-size:13px;text-align:right;line-height:1.35}
    .store-v3-stock-copy.is-low{color:#815c00!important}.store-v3-stock-copy.is-urgent{color:#a33127!important}
    @media(max-width:760px){.store-v3-reviews .offering-reviews__header{display:block}.store-v3-reviews .offering-reviews__summary{margin-top:14px}.store-v3-review-empty{align-items:stretch;flex-direction:column}.store-v3-review-empty .btn{width:100%}.store-v3-review-actions{flex-direction:column!important}.store-v3-review-composer{grid-template-columns:1fr}}
</style>
@endpush

<div class="wow-v3-offering-page {{ $productOnly ? 'wow-v3-product-only' : '' }}">
    <header class="hero-wrap">
        <section class="hero">
            @foreach($heroImages as $index => $heroImage)
                <div class="hero-slide {{ $index === 0 ? 'is-active' : '' }}" style="background-image:url('{{ $heroImage }}');"></div>
            @endforeach
            <div class="hero-overlay"></div>
            <div class="hero-watermark">{!! $watermark !!}</div>
            @if($reviewCount > 0)
                <div class="hero-review-badge" aria-label="{{ 'Rated ' . number_format((float) $rating, 1) . ' out of 5 from ' . $reviewCount . ' review' . ($reviewCount === 1 ? '' : 's') }}">
                    <span class="hero-review-badge__eyebrow">Reviews</span>
                    <div class="hero-review-badge__rating" aria-hidden="true">
                        <span class="hero-review-badge__stars">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="hero-review-badge__star {{ $i > $filledStars ? 'is-empty' : '' }}"></span>
                            @endfor
                        </span>
                        <span class="hero-review-badge__copy">{{ $reviewSummary }}</span>
                    </div>
                </div>
            @endif

            <div class="hero-content">
                <div class="hero-main">
                    <div class="kicker-row">
                        <span class="pill">{{ $typeLabel }}</span>
                        <span class="pill">{{ $categoryLabel !== '' ? $categoryLabel : $typeLabel }}</span>
                    </div>

                    <h1>{{ $title }}</h1>

                    @if($isStoreProduct && ! empty($primaryImage))
                        <div class="store-v3-mobile-product-image {{ $productOnly ? 'store-v3-product-only-image' : '' }}">
                            <img src="{{ $primaryImage }}" alt="{{ $title }}" loading="eager">
                        </div>
                    @endif

                </div>

                @if($showPaymentModule ?? true)
                @if($isStoreProduct)
                    <aside class="booking-panel store-v3-checkout-panel" id="booking">
                        <div class="booking-top">
                            <div>
                                <div class="price-label">Price</div>
                                @if(data_get($offering, 'compare_at_price') !== null && (float) data_get($offering, 'compare_at_price') > $price)
                                    <div class="store-v3-compare-price">£{{ number_format((float) data_get($offering, 'compare_at_price'), 2) }}</div>
                                @endif
                                <div class="price">£{{ number_format($price, 2) }}</div>
                            </div>
                            <span class="status-dot {{ $stockStatusClass }}">{{ $stockStatusLabel }}</span>
                        </div>

                        @if(count($variantCards) > 1)
                            <div class="store-v3-variant-picker" aria-label="Choose an option">
                                <span>Choose an option</span>
                                <div class="store-v3-variant-options" role="radiogroup">
                                    @foreach($variantCards as $variantCard)
                                        <button
                                            class="store-v3-variant-option {{ (string) ($variantCard['id'] ?? '') === (string) $selectedVariantId ? 'is-selected' : '' }}"
                                            type="button"
                                            role="radio"
                                            aria-checked="{{ (string) ($variantCard['id'] ?? '') === (string) $selectedVariantId ? 'true' : 'false' }}"
                                            data-variant-id="{{ $variantCard['id'] }}"
                                            data-variant-label="{{ $variantCard['label'] }}"
                                            data-variant-price="{{ number_format((float) ($variantCard['price'] ?? 0), 2, '.', '') }}"
                                            data-variant-compare="{{ $variantCard['compare'] !== null ? number_format((float) $variantCard['compare'], 2, '.', '') : '' }}"
                                            {{ empty($variantCard['available']) ? 'disabled' : '' }}
                                        >
                                            <strong>{{ $variantCard['label'] }}</strong>
                                            <small>£{{ number_format((float) ($variantCard['price'] ?? 0), 2) }}</small>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="store-v3-purchase-summary">
                            <div class="store-v3-purchase-row"><span>Product</span><strong>{{ $title }}</strong></div>
                            <div class="store-v3-purchase-row"><span>Delivery</span><strong>{{ ($offering['requires_shipping'] ?? true) ? 'Ships to you' : 'Digital delivery' }}</strong></div>
                            @if($stockQuantity !== null)<div class="store-v3-purchase-row"><span>Availability</span><strong class="store-v3-stock-copy {{ $stockStatusClass }}">{{ $stockStatusLabel }}</strong></div>@endif
                        </div>

                        <div class="desktop-booking-buttons">
                            <button class="btn checkout-button js-buy-now" type="button" data-id="store-{{ $offering['id'] ?? 0 }}" data-product-id="{{ $offering['id'] ?? 0 }}" data-source-version="store" data-price-includes-marketplace-markup="1" data-title="{{ $title }}" data-price="{{ number_format($price, 2, '.', '') }}" data-variant-id="{{ $selectedVariantId }}" data-variant-label="{{ $selectedVariantLabel }}" data-image="{{ $primaryImage }}" data-product-url="{{ $offering['url'] ?? url()->current() }}" data-url="{{ $offering['url'] ?? url()->current() }}" data-qty="1">Buy now</button>
                        </div>
                        <p class="secure-note">Secure checkout. Stripe payment. Email order confirmation.</p>
                    </aside>
                @elseif($usesLegacyBuybox)
                    <div class="legacy-buybox-shell">
                        @include('offering.partials.advanced_buybox')
                    </div>
                @else
                    <aside class="booking-panel" id="booking">
                        <div class="booking-top">
                            <div>
                                <div class="price-label">From</div>
                                <div class="price" id="panelPrice">{{ $priceSummary }}</div>
                            </div>
                            <span class="status-dot" id="statusDot">{{ $bookingSummaryLabel }}</span>
                        </div>

                        <button class="btn checkout-button mobile-panel-pick" type="button" data-open-booking>
                            Choose location &amp; date
                        </button>

                        <div class="booking-fields" id="bookingFields">
                            <label class="field-label">Location</label>

                            <div class="selected-location-card">
                                <div>
                                    <strong id="selectedLocationTitle">{{ $selectedLocationLabel }}</strong>
                                    <span id="selectedLocationAddress">{{ $selectedLocationAddress }}</span>
                                </div>
                                <button class="location-change-btn" type="button" data-open-locations>
                                    Change
                                </button>
                            </div>

                            <div class="location-strip" aria-label="Available locations">
                                @foreach($locations as $location)
                                    <button
                                        class="location-chip {{ ($location['id'] ?? '') === $selectedLocationId ? 'is-active' : '' }}"
                                        type="button"
                                        data-location-chip="{{ $location['id'] }}"
                                        data-location-label="{{ $location['label'] }}"
                                    >
                                        {{ $location['label'] }}
                                    </button>
                                @endforeach
                            </div>

                            <label class="field-label">Pick session</label>

                            <div class="custom-select" id="sessionSelect">
                                <button class="custom-select-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="custom-select-value">
                                        <strong id="sessionSelectTitle">{{ $selectedVariantLabel }}</strong>
                                        <span id="sessionSelectMeta">{{ $selectedVariantCard['meta'] ?? $selectedVariantLabel }} · {{ '£' . number_format($selectedVariantPrice, 2) }}</span>
                                    </span>
                                    <span class="custom-select-arrow" aria-hidden="true"></span>
                                </button>

                                <div class="custom-select-list" role="listbox">
                                    @foreach($variantCards as $variantCard)
                                        <button
                                            class="custom-select-option {{ $variantCard['id'] === $selectedVariantId ? 'is-selected' : '' }}"
                                            type="button"
                                            data-value="{{ $variantCard['id'] }}"
                                            data-title="{{ $variantCard['label'] }}"
                                            data-meta="{{ $variantCard['meta'] }}"
                                        data-price="{{ $variantCard['price'] }}"
                                        data-price-option-id="{{ $variantCard['price_option_id'] ?? '' }}"
                                        data-selection="{{ implode('|', $variantCard['selection'] ?? []) }}"
                                        data-best-value="{{ ! empty($variantCard['is_best_value']) ? '1' : '0' }}"
                                        data-unit-price="{{ $variantCard['unit_price'] ?? '' }}"
                                    >
                                        <strong>{{ $variantCard['label'] }}</strong>
                                        <span>
                                            {{ $variantCard['meta'] }} · {{ '£' . number_format((float) $variantCard['price'], 2) }}
                                            @if(($variantCard['session_count'] ?? 1) > 1)
                                                · {{ '£' . number_format((float) ($variantCard['unit_price'] ?? 0), 2) }}/session
                                            @endif
                                        </span>
                                        @if(! empty($variantCard['is_best_value']))
                                            <em class="best-value-badge">Best value</em>
                                        @endif
                                    </button>
                                @endforeach
                                </div>
                            </div>

                            <div id="groupSizeControl" class="ticket-control group-size-control" hidden>
                                <div>
                                    <strong>Group size</strong>
                                    <small id="groupPricePerPerson">Per person</small>
                                </div>

                                <div class="qty" aria-label="Group size">
                                    <button type="button" id="minusGroup">−</button>
                                    <span id="groupValue">3</span>
                                    <button type="button" id="plusGroup">+</button>
                                </div>
                            </div>

                            <label class="field-label" id="availabilityFieldLabel">Availability</label>

                            <div class="availability-mode">
                                <button class="availability-card is-selected" type="button" data-availability-mode="confirm">
                                    Confirm later
                                    <span id="confirmAvailabilityCopy">Book now, confirm date later</span>
                                </button>
                                <button class="availability-card" type="button" data-availability-mode="pick">
                                    <span id="pickAvailabilityTitle">Pick Date &amp; Time</span>
                                    <span id="pickAvailabilityCopy">Open calendar</span>
                                </button>
                            </div>

                            <div class="selected-summary">
                                <div class="selected-summary-row"><span>Location</span><strong id="summaryLocation">{{ $selectedLocationLabel }}</strong></div>
                                <div class="selected-summary-row"><span>Session</span><strong id="summarySession">{{ $selectedVariantLabel }}</strong></div>
                                <div class="selected-summary-row"><span>Date &amp; time</span><strong id="summaryDate">Confirm later</strong></div>
                                <div class="selected-summary-row"><span>Confirmation</span><strong>Email confirmation</strong></div>
                            </div>

                            <div class="hold-banner" id="holdBanner">
                                <span class="hourglass" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M7 3h10M7 21h10M8 3v5c0 1.3.7 2.5 1.8 3.2L12 12.5l2.2-1.3C15.3 10.5 16 9.3 16 8V3M8 21v-5c0-1.3.7-2.5 1.8-3.2L12 11.5l2.2 1.3C15.3 13.5 16 14.7 16 16v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span>Held for <span id="holdTimer">10:00</span></span>
                            </div>

                            <div class="ticket-control">
                                <div>
                                    <strong>Quantity</strong>
                                    <small>Instant email confirmation</small>
                                </div>

                                <div class="qty" aria-label="Quantity">
                                    <button type="button" id="minusQty">−</button>
                                    <span id="qtyValue">1</span>
                                    <button type="button" id="plusQty">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="desktop-booking-buttons">
                            <button class="btn checkout-button" type="button" id="desktopPrimaryAction">Book now</button>
                        </div>

                        <p class="secure-note">Secure checkout. Email confirmation. Live booking support where available.</p>
                    </aside>
                @endif
                @endif
            </div>
        </section>
    </header>

    <div class="content-wrap">
        <div class="content-main">
            @if($isStoreProduct)
                @if(! empty($galleryImages))
                    <section class="section flat store-v3-desktop-gallery">
                        <div class="gallery gallery-count-{{ count($galleryImages) }}">
                            @if(count($galleryImages) === 1)
                                <div class="gallery-main">
                                    <img src="{{ $galleryImages[0] }}" alt="{{ $title }} image">
                                </div>
                            @elseif(count($galleryImages) === 2)
                                <div class="gallery-main">
                                    <img src="{{ $galleryImages[0] }}" alt="{{ $title }} image">
                                </div>
                                <div class="gallery-main">
                                    <img src="{{ $galleryImages[1] }}" alt="{{ $title }} image">
                                </div>
                            @else
                                <div class="gallery-main">
                                    <img src="{{ $galleryImages[0] }}" alt="{{ $title }} image">
                                </div>
                                <div class="gallery-side">
                                    <div class="gallery-tile">
                                        <img src="{{ $galleryImages[1] }}" alt="{{ $title }} image">
                                    </div>
                                    <div class="gallery-tile">
                                        <img src="{{ $galleryImages[2] }}" alt="{{ $title }} image">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif
                @if(! $productOnly)
                <section class="section" id="about-therapy">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">Overview</p>
                            <h2>About this product</h2>
                        </div>
                    </div>
                    <div class="practitioner-rich-text">
                        {!! $renderRichHtml($bodyHtml ?: $summary) !!}
                    </div>
                </section>

                <section class="section quick-glance-section store-v3-quick-glance">
                    <div class="section-heading"><div><p class="eyebrow">Product snapshot</p><h2>At a glance</h2><p class="section-intro">The essentials before you order.</p></div></div>
                    <div class="quick-info-grid" aria-label="Product information">
                        @foreach($quickInfoMobile as $card)
                            <article class="quick-info-card"><span class="quick-info-icon" aria-hidden="true">{!! $quickInfoIconSvg($card['icon']) !!}</span><div><small>{{ $card['label'] }}</small><strong>{{ $card['value'] }}</strong></div></article>
                        @endforeach
                    </div>
                </section>

                <section class="section store-v3-specifications">
                    <div class="section-heading"><div><p class="eyebrow">Specifications</p><h2>Product information</h2></div></div>
                    <div class="split">
                        <article class="info-panel"><h3>Product details</h3><div class="mini-list">
                            @if(data_get($offering, 'sku'))<div class="mini-row"><span>SKU</span><strong>{{ data_get($offering, 'sku') }}</strong></div>@endif
                            @if(data_get($offering, 'weight_grams') !== null)<div class="mini-row"><span>Weight</span><strong>{{ data_get($offering, 'weight_grams') }} g</strong></div>@endif
                            @if(data_get($offering, 'dimensions.length_mm'))<div class="mini-row"><span>Dimensions</span><strong>{{ data_get($offering, 'dimensions.length_mm') }} × {{ data_get($offering, 'dimensions.width_mm') }} × {{ data_get($offering, 'dimensions.height_mm') }} mm</strong></div>@endif
                            <div class="mini-row"><span>Brand</span><strong>{{ data_get($offering, 'brand', 'We Offer Wellness®') }}</strong></div>
                        </div></article>
                        <article class="info-panel"><h3>Ordering &amp; delivery</h3><div class="mini-list">
                            <div class="mini-row"><span>Delivery</span><strong>{{ data_get($offering, 'requires_shipping') ? 'Ships to you' : 'Digital delivery' }}</strong></div>
                            <div class="mini-row"><span>Payment</span><strong>Secure Stripe checkout</strong></div>
                            <div class="mini-row"><span>Confirmation</span><strong>Email order confirmation</strong></div>
                        </div></article>
                    </div>
                </section>

                @php
                    $storeReviewProduct = $offering;
                    $storeReviewProduct['client_reviews'] = is_array($offering['reviews'] ?? null) ? $offering['reviews'] : [];
                    $storeReviewProduct['practitioner'] = [
                        'name' => (string) ($offering['brand'] ?? 'We Offer Wellness®'),
                        'review_url' => route('store.product.reviews.store', ['category' => data_get($offering, 'category.slug'), 'slug' => data_get($offering, 'slug')]),
                    ];
                @endphp
                @include('offering.partials.reviews_section', ['product' => $storeReviewProduct, 'type' => 'product'])
                @endif
            @else
            @if(! empty($galleryImages))
                <section class="section flat">
                    <div class="gallery gallery-count-{{ count($galleryImages) }}">
                        @if(count($galleryImages) === 1)
                            <div class="gallery-main">
                                <img src="{{ $galleryImages[0] }}" alt="{{ $title }} image">
                            </div>
                        @elseif(count($galleryImages) === 2)
                            <div class="gallery-main">
                                <img src="{{ $galleryImages[0] }}" alt="{{ $title }} image">
                            </div>
                            <div class="gallery-main">
                                <img src="{{ $galleryImages[1] }}" alt="{{ $title }} image">
                            </div>
                        @else
                            <div class="gallery-main">
                                <img src="{{ $galleryImages[0] }}" alt="{{ $title }} image">
                            </div>

                            <div class="gallery-side">
                                <div class="gallery-tile">
                                    <img src="{{ $galleryImages[1] }}" alt="{{ $title }} image">
                                </div>
                                <div class="gallery-tile">
                                    <img src="{{ $galleryImages[2] }}" alt="{{ $title }} image">
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="gallery-mobile-slider" data-mobile-gallery aria-label="{{ $title }} image gallery">
                        <div class="gallery-mobile-track" data-mobile-gallery-track>
                            @foreach($galleryImages as $index => $galleryImage)
                                <div class="gallery-mobile-slide {{ $index === 0 ? 'is-active' : '' }}" data-mobile-gallery-slide>
                                    <img src="{{ $galleryImage }}" alt="{{ $title }} image">
                                </div>
                            @endforeach
                        </div>

                        @if(count($galleryImages) > 1)
                            <button class="gallery-mobile-nav gallery-mobile-nav--prev" type="button" data-mobile-gallery-prev aria-label="Previous image">
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            </button>
                            <button class="gallery-mobile-nav gallery-mobile-nav--next" type="button" data-mobile-gallery-next aria-label="Next image">
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>

                            <div class="gallery-mobile-dots" data-mobile-gallery-dots aria-label="Gallery position">
                                @foreach($galleryImages as $index => $galleryImage)
                                    <button
                                        type="button"
                                        class="{{ $index === 0 ? 'is-active' : '' }}"
                                        data-mobile-gallery-dot="{{ $index }}"
                                        aria-label="{{ 'Image ' . ($index + 1) }}"
                                    ></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if($sourceVersion === 'v3' && ! $isStoreProduct)
            <div class="hero-meta">
                @foreach($quickInfo as $card)
                    @php
                        $isDurationCard = ($card['label'] ?? '') === 'Duration';
                        $isFormatImage = ($card['label'] ?? '') === 'Format' && ! empty($formatIconUrl);
                    @endphp
                    <article class="quick-info-card {{ $isFormatImage ? 'quick-info-card--image' : '' }}">
                        <span class="quick-info-icon {{ $isDurationCard ? 'quick-info-icon--duration' : '' }} {{ $isFormatImage ? 'quick-info-icon--format' : '' }}" aria-hidden="true">
                            @if($isDurationCard)
                                {!! $renderDurationIconSvg($durationMinutes) !!}
                            @elseif($isFormatImage)
                                <img src="{{ $formatIconUrl }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
                            @else
                                {!! $quickInfoIconSvg($card['icon']) !!}
                            @endif
                        </span>
                        <div>
                            <small>{{ $card['label'] }}</small>
                            <strong>{{ $card['value'] }}</strong>
                        </div>
                    </article>
                @endforeach
            </div>
            @endif

            <section class="section quick-glance-section">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Quick info</p>
                        <h2>At a glance</h2>
                        <p class="section-intro">
                            A quick overview of the session format, timing, delivery and booking flow.
                        </p>
                    </div>
                </div>

                <div class="quick-info-grid" aria-label="Therapy quick information">
                    @foreach($quickInfoMobile as $card)
                        <article class="quick-info-card">
                            <span class="quick-info-icon {{ ($card['label'] ?? '') === 'Duration' ? 'quick-info-icon--duration' : '' }}" aria-hidden="true">
                                {!! ($card['label'] ?? '') === 'Duration' ? $renderDurationIconSvg($durationMinutes) : $quickInfoIconSvg($card['icon']) !!}
                            </span>
                            <div>
                                <small>{{ $card['label'] }}</small>
                                <strong>{{ $card['value'] }}</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section" id="about-therapy">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Overview</p>
                        <h2>About this {{ strtolower($typeLabel) }}</h2>
                    </div>
                </div>

                <div class="practitioner-rich-text">
                    {!! $renderRichHtml($bodyHtml ?: $summary) !!}
                    @if($whatToExpect !== '')
                        <h4>What to expect</h4>
                        {!! $renderRichHtml($whatToExpect) !!}
                    @endif
                </div>
            </section>

            @if(!empty($guidePanel))
                <section class="section" id="related-guides">
                    <div class="section-heading section-heading--guides">
                        <div class="guides-header-row">
                            <div class="guides-header-copy">
                            <p class="eyebrow">{{ $guidePanel['eyebrow'] ?? 'Explore guides' }}</p>
                            <h2>{{ $guidePanel['title'] ?? 'Related guides' }}</h2>
                            </div>
                            @if(!empty($guidePanel['hub_url']))
                                <a class="btn btn-primary" href="{{ $guidePanel['hub_url'] }}">{{ $guidePanel['hub_label'] ?? 'Browse guides' }}</a>
                            @endif
                        </div>
                        @if(!empty($guidePanel['summary']))
                            <p class="section-intro">{{ $guidePanel['summary'] }}</p>
                        @endif
                    </div>

                    @if(!empty($guidePanel['links']))
                        <div class="guide-card-grid">
                            @foreach($guidePanel['links'] as $guideLink)
                                <a class="guide-card" href="{{ $guideLink['url'] ?? '#' }}">
                                    <strong>{{ $guideLink['title'] ?? $guideLink['label'] ?? 'Guide' }}</strong>
                                    @if(!empty($guideLink['summary']))
                                        <span>{{ $guideLink['summary'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @else
                <section class="section">
                    <div class="split">
                        <article class="info-panel">
                            <h3>What’s included</h3>
                            {!! $renderRichHtml($included ?: ('Your booking includes a ' . $durationLabel . ' session with email confirmation after checkout.')) !!}
                        </article>
                        <article class="info-panel">
                            <h3>What to expect</h3>
                            {!! $renderRichHtml($whatToExpect ?: 'Further details will be confirmed with the practitioner after checkout.') !!}
                        </article>
                    </div>
                </section>
            @endif

            <section class="section" id="locations">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Locations</p>
                        <h2>{{ $locationHeading }}</h2>
                        <p class="section-intro">{{ $locationIntro }}</p>
                    </div>
                </div>

                <div class="map-section-layout">
                <div class="location-list" aria-label="Available locations">
                    @foreach($locations as $location)
                        <button
                            class="location-card{{ $location['id'] === $selectedLocationId ? ' is-active' : '' }}"
                            type="button"
                                data-location-card
                            data-location-id="{{ $location['id'] }}"
                            data-location-online="{{ ! empty($location['online']) ? '1' : '0' }}"
                        >
                            <h3>{{ $location['label'] }}</h3>
                            <p>{{ $location['address'] ?: ($location['notes'] ?: 'Available') }}</p>
                        </button>
                    @endforeach
                </div>
                    <div class="map-box{{ $onlineOnlyLocation ? ' is-online-mode' : '' }}" id="desktopMapBox">
                        @if($hasOnlineLocation)
                            <video class="map-online-video" id="desktopOnlineVideo" autoplay muted loop playsinline poster="{{ $videoPoster }}">
                                <source src="{{ $videoSource }}" type="video/mp4">
                            </video>
                        @endif
                        <div class="mapbox-map" id="desktopMap"></div>
                        <div class="map-online-overlay">
                            <div>
                                <strong>{{ $onlineOverlayTitle }}</strong>
                                <span>{{ $onlineOverlayCopy }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section">
                <div class="split">
                    <article class="info-panel">
                        <h3>Participant guidelines</h3>
                        <ul>
                            <li>Minimum age: 18 unless otherwise agreed by the practitioner.</li>
                            <li>Wear comfortable clothing suitable for gentle movement.</li>
                            <li>Tell the practitioner about injuries, pregnancy or medical conditions before attending.</li>
                        </ul>
                        @if($safetyNotes !== '')
                            <h4>Safety notes</h4>
                            {!! $renderRichHtml($safetyNotes) !!}
                        @endif
                        @if($contraindications !== '')
                            <h4>Contraindications</h4>
                            {!! $renderRichHtml($contraindications) !!}
                        @endif
                    </article>
                    <article class="info-panel">
                        <h3>What happens on the day?</h3>
                        <p>
                            Arrive at your chosen location, settle into the space and move through a calm, restorative session.
                            Your booking confirmation and any follow-up instructions will land in your inbox after checkout.
                        </p>
                    </article>
                </div>
            </section>

            @include('offering.partials.reviews_section', ['product' => $product, 'type' => $type])
            @endif
        </div>

        <aside class="side-stack">
            @if($isStoreProduct)
                <div class="side-card">
                    <h3>Product snapshot</h3>
                    <div class="mini-list">
                        <div class="mini-row"><span>Format</span><strong>Physical product</strong></div>
                        <div class="mini-row"><span>Delivery</span><strong>Ships to you</strong></div>
                        <div class="mini-row"><span>Stock</span><strong class="store-v3-stock-copy {{ $stockStatusClass }}">{{ $stockStatusLabel }}</strong></div>
                        <div class="mini-row"><span>Price</span><strong>£{{ number_format($price, 2) }}</strong></div>
                    </div>
                </div>
                <div class="side-card">
                    <h3>Good to know</h3>
                    <div class="mini-list">
                        <div class="mini-row"><span>Payment</span><strong>Secure Stripe checkout</strong></div>
                        <div class="mini-row"><span>Confirmation</span><strong>Email receipt</strong></div>
                        <div class="mini-row"><span>Returns</span><strong>See store policy</strong></div>
                    </div>
                </div>
            @else
            <div class="side-card practitioner-side-card" id="practitioner">
                <div class="practitioner-side-head">
                    <img class="practitioner-side-avatar" src="{{ $practitioner['photo'] ?? asset('images/default-social-preview.jpg') }}" alt="Practitioner profile photo">
                    <div>
                        <span class="side-eyebrow">Meet the practitioner</span>
                        <h3>{{ $practitionerName }}</h3>
                    </div>
                </div>

                <p>
                    {{ $practitionerBio !== '' ? $practitionerBio : ($summary !== '' ? $summary : 'Calm, welcoming support for restorative sessions and gentle wellbeing experiences.') }}
                </p>

                @if($practitionerLocation !== '')
                    <div class="practitioner-credentials" aria-label="Practitioner location and credentials">
                        <div class="credential-group">
                            <span>Location</span>
                            <div class="credential-tags">
                                <strong class="credential-tag">{{ $practitionerLocation }}</strong>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!empty($credentialTags))
                    <div class="practitioner-credentials" aria-label="Practitioner qualifications and credentials">
                        <div class="credential-group">
                            <span>Credentials</span>
                            <div class="credential-tags">
                                @foreach($credentialTags as $credentialTag)
                                    <strong class="credential-tag">{{ $credentialTag }}</strong>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <a class="btn btn-primary practitioner-profile-link" href="{{ $practitionerProfileUrl !== '' ? $practitionerProfileUrl : url('/therapies') }}">
                    View profile
                </a>
            </div>

            <div class="side-card">
                <h3>Therapy snapshot</h3>
                    <div class="mini-list">
                        <div class="mini-row"><span>Format</span><strong>{{ $typeLabel }}</strong></div>
                        <div class="mini-row"><span>Locations</span><strong>{{ $locationSummary }}</strong></div>
                        @if($sourceVersion === 'v3')
                        <div class="mini-row"><span>Duration</span><strong>{{ $durationLabel }}</strong></div>
                        @endif
                        <div class="mini-row"><span>Booking</span><strong>{{ $bookingSummaryLabel }}</strong></div>
                        <div class="mini-row"><span>Price</span><strong>{{ $priceSummary }}</strong></div>
                    </div>
            </div>

            <div class="side-card">
                <h3>Good to know</h3>
                <div class="mini-list">
                    <div class="mini-row"><span>Booking changes</span><strong>Discuss after checkout</strong></div>
                    <div class="mini-row"><span>Confirmation</span><strong>Email receipt</strong></div>
                    <div class="mini-row"><span>Secure checkout</span><strong>Yes</strong></div>
                </div>
            </div>
            @endif
        </aside>
    </div>

    @if($isStoreProduct && ! $productOnly)
    <div class="mobile-ticket-bar store-v3-mobile-buybar" id="storeMobileBuyBar">
        <div>
            <strong>£{{ number_format($price, 2) }}</strong>
            <span class="store-v3-mobile-stock {{ $stockStatusClass }}">{{ $stockStatusLabel }} · Ships to you</span>
        </div>
        <button class="btn checkout-button js-buy-now" type="button" data-id="store-{{ $offering['id'] ?? 0 }}" data-product-id="{{ $offering['id'] ?? 0 }}" data-source-version="store" data-price-includes-marketplace-markup="1" data-title="{{ $title }}" data-price="{{ number_format($price, 2, '.', '') }}" data-variant-id="{{ $selectedVariantId }}" data-variant-label="{{ $selectedVariantLabel }}" data-image="{{ $primaryImage }}" data-product-url="{{ $offering['url'] ?? url()->current() }}" data-url="{{ $offering['url'] ?? url()->current() }}" data-qty="1">Buy now</button>
    </div>
    @elseif(! $usesLegacyBuybox && ($showPaymentModule ?? true))
    <div class="mobile-ticket-bar" id="mobileTicketBar">
        <div>
            <strong id="mobilePrice">{{ $priceSummary }}</strong>
            <span id="mobileTicket">{{ $mobileTicketText }}</span>
        </div>
        <button class="btn checkout-button" type="button" data-open-booking>Choose location &amp; date</button>
    </div>

    <div class="modal-backdrop" id="bookingBackdrop"></div>

    <section class="booking-modal" id="bookingModal" aria-label="Mobile booking modal">
        <div class="booking-modal-head">
            <h3>Choose location &amp; date</h3>
            <button class="booking-modal-close" type="button" id="closeBookingModal">×</button>
        </div>
        <div class="booking-modal-body">
            <div class="modal-fields-slot" id="modalFieldsSlot"></div>
        </div>
        <div class="booking-modal-footer">
            <button class="btn checkout-button" type="button" id="mobilePrimaryAction">Book now</button>
        </div>
    </section>

    <div class="calendar-modal-backdrop" id="calendarBackdrop"></div>

    <section class="date-time-modal" id="dateTimeModal" aria-label="Pick Date & Time" aria-modal="true">
        <div class="date-time-modal-head">
            <h3>
                <span class="modal-kicker">Pick Date &amp; Time</span>
                {{ $title }}
            </h3>
            <button class="date-time-modal-close" type="button" id="closeDateTimeModal">×</button>
        </div>

        <div class="date-time-modal-body">
            <div class="date-time-calendar-panel">
                <div class="date-time-calendar-head">
                    <button class="calendar-nav-btn" type="button" id="prevMonthBtn" aria-label="Previous month">‹</button>
                    <div class="date-time-calendar-title" id="calendarTitle">Loading…</div>
                    <button class="calendar-nav-btn" type="button" id="nextMonthBtn" aria-label="Next month">›</button>
                </div>

                <div class="date-time-calendar-grid" id="dateTimeCalendarGrid">
                    <div class="date-time-day-name">Mon</div>
                    <div class="date-time-day-name">Tue</div>
                    <div class="date-time-day-name">Wed</div>
                    <div class="date-time-day-name">Thu</div>
                    <div class="date-time-day-name">Fri</div>
                    <div class="date-time-day-name">Sat</div>
                    <div class="date-time-day-name">Sun</div>
                </div>

                <div class="timezone-row">
                    <span>Time zone:</span>
                    <span class="timezone-pill" id="timezoneLabel">Europe/London</span>
                </div>
            </div>

            <div class="date-time-slots-panel">
                <div class="date-time-slots-title">
                    <span aria-hidden="true">◷</span>
                    <span>Available times</span>
                </div>

                <div class="time-options" id="timeOptions">
                    <div class="time-option" style="opacity:.65;cursor:default;">Pick a date</div>
                </div>

                <p class="date-time-helper" id="dateTimeHelper">
                    Pick a date, then choose a time.
                </p>
            </div>
        </div>

        <div class="date-time-modal-footer">
            <div class="date-time-footer-note" id="dateTimeFooterNote">Pick a date, then choose a time.</div>
            <div class="date-time-footer-actions">
                <button class="btn secondary-checkout" type="button" id="cancelDateTimeModal">Cancel</button>
                <button class="btn checkout-button date-time-confirm" type="button" id="confirmDateTime" disabled>Confirm Date &amp; Time</button>
            </div>
        </div>
    </section>

    <div class="location-modal-backdrop" id="locationBackdrop"></div>

    <section class="location-modal" id="locationModal" aria-label="Choose a location" aria-modal="true">
        <div class="location-modal-head">
            <h3>
                <span class="modal-kicker">Choose location</span>
                Where would you like to attend?
            </h3>
            <button class="location-modal-close" type="button" id="closeLocationModal">×</button>
        </div>

        <div class="location-modal-body">
            <div class="location-modal-layout">
                <div class="location-modal-options" aria-label="Available offering locations">
                    @foreach($locations as $location)
                        <button class="location-option {{ ($location['id'] ?? '') === $selectedLocationId ? 'is-selected' : '' }}" type="button" data-location="{{ $location['id'] }}">
                            <span class="option-main">
                                <span>{{ $location['label'] }}</span>
                                @if(! ($onlineOnlyLocation && ! empty($location['online'])))
                                    <span class="option-pill">{{ ! empty($location['online']) ? 'Online' : 'Available' }}</span>
                                @endif
                            </span>
                            <span class="option-sub">{{ $location['address'] ?: ($location['notes'] ?: 'Available') }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="location-modal-map-card" id="locationModalMapCard">
                    @if($hasOnlineLocation)
                        <video class="map-online-video" id="locationModalOnlineVideo" autoplay muted loop playsinline poster="{{ $videoPoster }}">
                            <source src="{{ $videoSource }}" type="video/mp4">
                        </video>
                    @endif
                    <div class="location-modal-map" id="locationModalMap"></div>
                    <div class="map-online-overlay">
                        <div>
                            <strong>{{ $onlineOverlayTitle }}</strong>
                            <span>{{ $onlineOverlayCopy }}</span>
                        </div>
                    </div>
                    <div class="location-modal-map-caption">
                        <span id="locationModalMapTitle">{{ $selectedLocationLabel }}</span>
                        <small id="locationModalMapAddress">{{ $selectedLocationAddress }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="location-modal-footer">
            <button class="btn checkout-button" type="button" id="confirmLocationModal">Use selected location</button>
        </div>
    </section>
    @endif
</div>

@if(! $usesLegacyBuybox && ($showPaymentModule ?? true))
@push('scripts')
<script data-cfasync="false" src="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js"></script>
<script>
(function () {
    window.WOW_MAPS_KEY = window.WOW_MAPS_KEY || @json(config('services.mapbox.token'));
    window.__WOW_V3_OFFERING = @json($offeringConfig);

    const config = window.__WOW_V3_OFFERING || {};
    const body = document.body;
    const page = document.querySelector('.wow-v3-offering-page');
    if (!page) return;

    const mapToken = window.WOW_MAPS_KEY || config.mapboxToken || '';
    function syncMapboxAccessToken() {
        if (mapToken && window.mapboxgl && window.mapboxgl.accessToken !== mapToken) {
            window.mapboxgl.accessToken = mapToken;
        }
    }

    function waitForMapboxReady(timeoutMs = 8000) {
        syncMapboxAccessToken();

        if (window.mapboxgl && window.mapboxgl.Map) {
            return Promise.resolve(true);
        }

        return new Promise(resolve => {
            const startedAt = Date.now();
            const interval = setInterval(() => {
                syncMapboxAccessToken();
                if (window.mapboxgl && window.mapboxgl.Map) {
                    clearInterval(interval);
                    resolve(true);
                    return;
                }

                if (Date.now() - startedAt >= timeoutMs) {
                    clearInterval(interval);
                    resolve(false);
                }
            }, 100);
        });
    }

    syncMapboxAccessToken();
    const offeringId = Number(config.id || 0);
    const cartAddEndpoint = config.cartAddEndpoint || '/api/cart/add';
    const bookingEndpoint = config.bookingEndpoint || '';
    const reservationHoldEndpoint = config.reservationHoldEndpoint || '/api/reservations/hold';
    const reservationReleaseEndpoint = config.reservationReleaseEndpoint || '/api/reservations/release';
    const cartUrl = config.cartUrl || '/cart';

    const heroSlides = Array.from(page.querySelectorAll('.hero-slide'));
    const panelPrice = page.querySelector('#panelPrice');
    const mobilePrice = page.querySelector('#mobilePrice');
    const summaryLocation = page.querySelector('#summaryLocation');
    const summarySession = page.querySelector('#summarySession');
    const summaryDate = page.querySelector('#summaryDate');
    const selectedLocationTitle = page.querySelector('#selectedLocationTitle');
    const selectedLocationAddress = page.querySelector('#selectedLocationAddress');
    const qtyValue = page.querySelector('#qtyValue');
    const minusQty = page.querySelector('#minusQty');
    const plusQty = page.querySelector('#plusQty');
    const groupSizeControl = page.querySelector('#groupSizeControl');
    const groupValue = page.querySelector('#groupValue');
    const minusGroup = page.querySelector('#minusGroup');
    const plusGroup = page.querySelector('#plusGroup');
    const groupPricePerPerson = page.querySelector('#groupPricePerPerson');
    const holdBanner = page.querySelector('#holdBanner');
    const holdTimer = page.querySelector('#holdTimer');
    const bookingFields = page.querySelector('#bookingFields');
    const modalFieldsSlot = page.querySelector('#modalFieldsSlot');
    const bookingPanel = page.querySelector('#booking');
    const heroContent = page.querySelector('.hero-content');
    const sideStack = page.querySelector('.side-stack');
    const bookingHome = bookingPanel?.parentElement || null;
    const bookingModal = page.querySelector('#bookingModal');
    const bookingBackdrop = page.querySelector('#bookingBackdrop');
    const closeBookingModal = page.querySelector('#closeBookingModal');
    const mobileTicketBar = page.querySelector('#mobileTicketBar');
    const mobilePrimaryAction = page.querySelector('#mobilePrimaryAction');
    const desktopPrimaryAction = page.querySelector('#desktopPrimaryAction');
    const desktopSecondaryAction = page.querySelector('#desktopSecondaryAction');
    const openBookingButtons = page.querySelectorAll('[data-open-booking]');
    const openLocationButtons = page.querySelectorAll('[data-open-locations]');
    const locationChips = page.querySelectorAll('[data-location-chip]');
    const sessionSelect = page.querySelector('#sessionSelect');
    const sessionTrigger = page.querySelector('#sessionSelect .custom-select-trigger');
    const sessionOptions = page.querySelectorAll('#sessionSelect .custom-select-option');
    const sessionTitle = page.querySelector('#sessionSelectTitle');
    const sessionMeta = page.querySelector('#sessionSelectMeta');
    const locationModal = page.querySelector('#locationModal');
    const locationBackdrop = page.querySelector('#locationBackdrop');
    const closeLocationModal = page.querySelector('#closeLocationModal');
    const confirmLocationModal = page.querySelector('#confirmLocationModal');
    const locationOptions = page.querySelectorAll('#locationModal .location-option');
    const locationModalMapCard = page.querySelector('#locationModalMapCard');
    const locationModalMapTitle = page.querySelector('#locationModalMapTitle');
    const locationModalMapAddress = page.querySelector('#locationModalMapAddress');
    const dateTimeModal = page.querySelector('#dateTimeModal');
    const calendarBackdrop = page.querySelector('#calendarBackdrop');
    const closeDateTimeModal = page.querySelector('#closeDateTimeModal');
    const cancelDateTimeModal = page.querySelector('#cancelDateTimeModal');
    const confirmDateTime = page.querySelector('#confirmDateTime');
    const calendarTitle = page.querySelector('#calendarTitle');
    const timezoneLabel = page.querySelector('#timezoneLabel');
    const dateTimeCalendarGrid = page.querySelector('#dateTimeCalendarGrid');
    const timeOptions = page.querySelector('#timeOptions');
    const dateTimeHelper = page.querySelector('#dateTimeHelper');
    const dateTimeFooterNote = page.querySelector('#dateTimeFooterNote');
    const prevMonthBtn = page.querySelector('#prevMonthBtn');
    const nextMonthBtn = page.querySelector('#nextMonthBtn');
    const desktopMapBox = page.querySelector('#desktopMapBox');
    const desktopMapEl = page.querySelector('#desktopMap');
    const onlineVideo = page.querySelector('#desktopOnlineVideo');
    const availabilityFieldLabel = page.querySelector('#availabilityFieldLabel');
    const pickAvailabilityTitle = page.querySelector('#pickAvailabilityTitle');
    const pickAvailabilityCopy = page.querySelector('#pickAvailabilityCopy');
    const confirmAvailabilityCopy = page.querySelector('#confirmAvailabilityCopy');
    const mobileMapBarVisibleClass = 'wow-v3-mobile-ticket-visible';

    const locations = Array.isArray(config.locations) ? config.locations : [];
    const variants = Array.isArray(config.variants) ? config.variants : [];
    const sourceVersion = config.sourceVersion || 'legacy';

    let selectedLocationId = config.selectedLocationId || (locations[0] && locations[0].id) || 'loc-online';
    let pendingLocationId = selectedLocationId;
    let selectedVariant = variants.find(v => String(v.id) === String(config.selectedVariantId)) || variants[0] || null;
    let selectedVariantId = selectedVariant ? String(selectedVariant.id) : '';
    let selectedVariantLabel = selectedVariant ? String(selectedVariant.label || '') : (config.selectedVariantLabel || 'Option');
    let selectedVariantSelection = Array.isArray(selectedVariant?.selection) ? selectedVariant.selection.slice() : [];
    let selectedVariantPrice = Number(selectedVariant?.price ?? config.price ?? 0);
    let selectedPriceOptionId = Number(selectedVariant?.price_option_id ?? 0) || null;
    let groupCount = Number(config.selectedGroupCount || config.groupMin || 3) || 3;
    let qty = 1;
    let selectedAvailabilityMode = 'confirm';
    let selectedDateKey = null;
    let selectedTime = null;
    let selectedDateLabel = '';
    let holdExpiresAt = null;
    let reservationId = null;
    let holdSeconds = 600;
    let holdTimerHandle = null;
    let holdActive = false;
    let activeModal = null;
    let returnAfterChildModal = null;
    let bookingMonth = new Date();
    let minAvailableDate = null;
    let maxAvailableDate = null;
    let bookingPayload = null;
    let slotsByDay = {};
    let reservationHolds = {};
    let requestMode = false;
    let bookingTimezone = 'Europe/London';
    let bookingDurationMinutes = Number(config.durationMinutes || 60) || 60;
    let desktopMap = null;
    let locationModalMap = null;
    let desktopMarkers = [];
    let locationModalMarkers = [];

    function qs(selector, root = document) {
        return root.querySelector(selector);
    }

    function qsa(selector, root = document) {
        return Array.from(root.querySelectorAll(selector));
    }

    function isMobile() {
        return window.innerWidth <= 720;
    }

    function placeBookingPanel() {
        if (!bookingPanel || !sideStack || !bookingHome) return;
        if (isMobile()) {
            if (bookingPanel.parentElement !== bookingHome) bookingHome.appendChild(bookingPanel);
            bookingPanel.classList.add('is-positioned');
            return;
        }

        if (bookingPanel.parentElement !== sideStack) sideStack.prepend(bookingPanel);
        bookingPanel.classList.add('is-positioned');
    }

    function money(value) {
        const n = Number(value || 0);
        try {
            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: config.currency || 'GBP' }).format(n);
        } catch (e) {
            return `£${n.toFixed(2)}`;
        }
    }

    function parseDateKey(dateKey) {
        const value = String(dateKey || '');
        return value ? new Date(`${value}T12:00:00`) : new Date();
    }

    function pad(num) {
        return String(num).padStart(2, '0');
    }

    function dateKeyFromDate(date) {
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    function hasAnySlots(slotMap) {
        return Object.values(slotMap || {}).some(day => Array.isArray(day?.slots) && day.slots.length > 0);
    }

    function generateRequestSlots() {
        const generated = {};
        const earliest = new Date(Date.now() + (72 * 60 * 60 * 1000));
        const cursor = new Date(earliest);
        cursor.setHours(0, 0, 0, 0);
        const end = new Date(cursor);
        end.setDate(end.getDate() + 56);

        while (cursor <= end) {
            const day = cursor.getDay();
            if (day >= 1 && day <= 5) {
                const dateKey = dateKeyFromDate(cursor);
                const slots = [];
                for (let hour = 9; hour <= 17; hour += 1) {
                    const slotDate = new Date(cursor);
                    slotDate.setHours(hour, 0, 0, 0);
                    if (slotDate.getTime() < earliest.getTime()) {
                        continue;
                    }
                    slots.push({
                        start: `${pad(hour)}:00`,
                        iso: slotDate.toISOString(),
                        request: true,
                    });
                }
                if (slots.length) {
                    generated[dateKey] = { slots };
                }
            }
            cursor.setDate(cursor.getDate() + 1);
        }

        return generated;
    }

    function syncAvailabilityCopy() {
        if (availabilityFieldLabel) {
            availabilityFieldLabel.textContent = requestMode ? 'Request day/time' : 'Availability';
        }
        if (confirmAvailabilityCopy) {
            confirmAvailabilityCopy.textContent = 'Book now, confirm date later';
        }
        if (pickAvailabilityTitle) {
            pickAvailabilityTitle.textContent = requestMode ? 'Request Date' : 'Pick Date & Time';
        }
        if (pickAvailabilityCopy) {
            pickAvailabilityCopy.textContent = requestMode ? 'Weekdays, 9am-5pm' : 'Open calendar';
        }
    }

    function formatDateLabel(dateKey) {
        try {
            return new Intl.DateTimeFormat('en-GB', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            }).format(parseDateKey(dateKey));
        } catch (e) {
            return String(dateKey || '');
        }
    }

    function monthLabel(date) {
        try {
            return new Intl.DateTimeFormat('en-GB', { month: 'long', year: 'numeric' }).format(date);
        } catch (e) {
            return date.toISOString().slice(0, 7);
        }
    }

    function cookieSet(name, value, maxAgeSeconds) {
        document.cookie = `${name}=${encodeURIComponent(value)}; Path=/; Max-Age=${maxAgeSeconds}; SameSite=Lax`;
    }

    function readStoredCart() {
        try {
            const raw = localStorage.getItem('wow_cart') || localStorage.getItem('wow_cart_v1');
            if (!raw) return { items: [] };
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                return { items: parsed };
            }
            if (parsed && typeof parsed === 'object' && Array.isArray(parsed.items)) {
                return parsed;
            }
        } catch (e) {}
        return { items: [] };
    }

    function writeStoredCart(lineItem) {
        const bag = readStoredCart();
        const items = Array.isArray(bag.items) ? bag.items.slice() : [];
        const existingIndex = items.findIndex(item => String(item.id) === String(lineItem.id));
        if (existingIndex >= 0) {
            const nextQty = Math.max(1, Number(items[existingIndex].qty || 1) + Number(lineItem.qty || 1));
            items[existingIndex] = {
                ...items[existingIndex],
                ...lineItem,
                qty: nextQty,
            };
        } else {
            items.push(lineItem);
        }

        const nextBag = { items };
        items.forEach(item => {
            nextBag[String(item.id)] = item;
        });

        try {
            localStorage.setItem('wow_cart', JSON.stringify(nextBag));
            localStorage.setItem('wow_cart_v1', JSON.stringify(nextBag));
        } catch (e) {}

        const cookieItems = items.map(item => ({
            id: String(item.id),
            product_id: item.product_id || null,
            variant_id: item.variant_id || null,
            variant_label: item.variant_label || '',
            title: item.title || '',
            price: Number(item.price) || 0,
            qty: Number(item.qty) || 1,
            image: item.image || null,
            url: item.url || '#',
        }));
        if (cookieItems.length) {
            try {
                document.cookie = 'wow_cart=; Path=/; Max-Age=0; SameSite=Lax';
                document.cookie = 'wow_cart=' + encodeURIComponent(JSON.stringify(cookieItems)) + '; Domain=.weofferwellness.co.uk; Path=/; Max-Age=' + (60 * 60 * 24 * 30) + '; SameSite=Lax';
            } catch (e) {}
        } else {
            try {
                document.cookie = 'wow_cart=; Path=/; Max-Age=0; SameSite=Lax';
                document.cookie = 'wow_cart=; Domain=.weofferwellness.co.uk; Path=/; Max-Age=0; SameSite=Lax';
            } catch (e) {}
        }
        try {
            window.dispatchEvent(new CustomEvent('wow:cart:change', { detail: { items, count: items.reduce((sum, it) => sum + (Number(it.qty) || 0), 0), source: 'v3-offering' } }));
        } catch (e) {}
    }

    function cartLineId() {
        if (!selectedVariantId) return `p:${offeringId}`;
        const groupKey = variantIsGroup(selectedVariant)
            ? `:g:${groupCount}`
            : '';
        return `v:${selectedVariantId}${groupKey}`;
    }

    function selectedLocation() {
        return locations.find(location => String(location.id) === String(selectedLocationId)) || locations[0] || {
            id: 'loc-online',
            label: 'Location to be confirmed',
            address: 'Your practitioner will confirm after booking.',
            online: true,
        };
    }

    function locationKeyFromText(value) {
        let text = String(value || '').trim().toLowerCase();
        if (!text) {
            return '';
        }

        text = text.split(',')[0].trim();
        if (!text) {
            return '';
        }

        text = text.replace(/\b(?:uk|u\.k\.|gb|great britain|united kingdom|england|scotland|wales|northern ireland)\b/g, ' ');
        text = text.replace(/\b[A-Z]{1,2}\d[\dA-Z]?\s*\d[A-Z]{2}\b/gi, ' ');
        text = text.replace(/\b[A-Z]{1,2}\d[\dA-Z]?\b/gi, ' ');
        text = text.replace(/[^a-z0-9]+/gi, ' ').replace(/\s+/g, ' ').trim();

        return text;
    }

    function locationKeyForLocation(location) {
        if (!location) {
            return '';
        }

        return String(location.location_key || locationKeyFromText(location.label || location.address || location.notes || '') || '');
    }

    function firstOnlineLocation() {
        return locations.find(location => Boolean(location.online)) || null;
    }

    function firstPhysicalLocation() {
        return locations.find(location => ! Boolean(location.online)) || null;
    }

    function variantLocationPreference(variant) {
        const text = Array.isArray(variant?.selection) ? variant.selection.join(' ').toLowerCase() : '';
        if (!text) {
            return null;
        }
        if (text.includes('in-person') || text.includes('in person')) {
            return 'in-person';
        }
        if (text.includes('online')) {
            return 'online';
        }
        return null;
    }

    function variantLocationKey(variant) {
        if (variant && typeof variant === 'object' && String(variant.location_key || '').trim() !== '') {
            return String(variant.location_key).trim();
        }

        const selection = Array.isArray(variant?.selection) ? variant.selection : [];
        for (let i = selection.length - 1; i >= 0; i -= 1) {
            const key = locationKeyFromText(selection[i]);
            if (key !== '') {
                return key;
            }
        }

        return '';
    }

    function variantSelectionSignature(variant) {
        const selection = Array.isArray(variant?.selection) ? variant.selection : [];
        const locationKey = variantLocationKey(variant);
        return selection
            .map(value => String(value || '').trim().toLowerCase())
            .filter(value => {
                if (value === '' || value === 'online' || value === 'in-person' || value === 'in person' || value === 'exclusively online') {
                    return false;
                }

                return locationKeyFromText(value) !== locationKey;
            });
    }

    function variantSelectionSignatureKey(variant) {
        return variantSelectionSignature(variant).join(' | ');
    }

    function variantForLocation(location, currentVariant = selectedVariant) {
        const currentSignature = variantSelectionSignatureKey(currentVariant);
        const isOnline = Boolean(location?.online);
        const desiredKey = location ? locationKeyForLocation(location) : '';
        const candidateIds = Array.isArray(location?.variant_ids) ? location.variant_ids.map(id => String(id)) : [];
        const sameLocationVariants = candidateIds.length > 0
            ? variants.filter(candidate => candidateIds.includes(String(candidate.id)))
            : variants.filter(candidate => {
                const preference = variantLocationPreference(candidate);
                if (isOnline) return preference === 'online';
                if (preference !== 'in-person') return false;
                return desiredKey === '' || variantLocationKey(candidate) === desiredKey || variantLocationKey(candidate) === '';
            });

        if (sameLocationVariants.length > 0) {
            const exactMatch = sameLocationVariants.find(candidate => variantSelectionSignatureKey(candidate) === currentSignature);
            if (exactMatch) {
                return exactMatch;
            }

            return sameLocationVariants[0];
        }

        return currentVariant || variants[0] || null;
    }

    function selectedLocationLabel() {
        const location = selectedLocation();
        return String(location.label || 'Location');
    }

    function selectedLocationAddressValue() {
        const location = selectedLocation();
        return String(location.address || location.notes || '');
    }

    function bookingSummaryText() {
        if (holdActive) return `Held for ${holdTimerText()}`;
        if (selectedAvailabilityMode === 'pick') {
            if (!selectedDateKey || !selectedTime) return 'Pick Date & Time';
            return `${formatDateLabel(selectedDateKey)} at ${selectedTime}`;
        }
        return 'Confirm later';
    }

    function holdTimerText() {
        const minutes = Math.floor(holdSeconds / 60);
        const seconds = holdSeconds % 60;
        return `${minutes}:${pad(seconds)}`;
    }

    function clearHoldState() {
        holdActive = false;
        reservationId = null;
        holdExpiresAt = null;
        clearInterval(holdTimerHandle);
        holdTimerHandle = null;
        holdBanner?.classList.remove('is-active');
    }

    async function releaseReservationIfAny() {
        if (!reservationId) {
            clearHoldState();
            return;
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content || window.__csrfToken || '';
            await fetch(reservationReleaseEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ id: reservationId }),
            });
        } catch (e) {}

        clearHoldState();
    }

    function startHoldCountdown(expiresAt) {
        holdActive = true;
        holdExpiresAt = expiresAt;
        holdBanner?.classList.add('is-active');
        const target = new Date(expiresAt).getTime();
        holdSeconds = Math.max(0, Math.ceil((target - Date.now()) / 1000));
        if (holdTimer) holdTimer.textContent = holdTimerText();
        clearInterval(holdTimerHandle);
        holdTimerHandle = setInterval(() => {
            holdSeconds -= 1;
            if (holdTimer) holdTimer.textContent = holdTimerText();
            if (holdSeconds <= 0) {
                clearHoldState();
                updatePrimaryActions();
            }
        }, 1000);
    }

    function allVariantsSelected() {
        return Boolean(selectedVariantId);
    }

    function syncVariantQueryParam() {
        try {
            const url = new URL(window.location.href);
            if (selectedVariantId) {
                url.searchParams.set('variant', selectedVariantId);
            } else {
                url.searchParams.delete('variant');
            }
            const next = url.toString();
            if (next !== window.location.href) {
                window.history.replaceState({}, '', next);
            }
        } catch (e) {}
    }

    function syncPanelSummary() {
        const isGroup = variantIsGroup(selectedVariant);
        const groupTotal = isGroup ? selectedVariantPrice * groupCount : selectedVariantPrice;
        const total = groupTotal * qty;
        panelPrice.textContent = money(total);
        mobilePrice.textContent = money(total);
        selectedLocationTitle.textContent = selectedLocationLabel();
        selectedLocationAddress.textContent = selectedLocationAddressValue();
        summaryLocation.textContent = selectedLocationLabel();
        summarySession.textContent = selectedVariantLabel;
        summaryDate.textContent = bookingSummaryText();
        qtyValue.textContent = String(qty);
        if (groupValue) groupValue.textContent = String(groupCount);
        if (groupSizeControl) groupSizeControl.hidden = !isGroup;
        if (groupPricePerPerson) groupPricePerPerson.textContent = isGroup ? `${money(selectedVariantPrice)} per person` : 'Per person';
        timezoneLabel.textContent = bookingTimezone || 'Europe/London';
        qsa('[data-location-chip]', page).forEach(chip => {
            chip.classList.toggle('is-active', String(chip.dataset.locationChip) === String(selectedLocationId));
        });
        updatePrimaryActions();
        syncVariantQueryParam();
    }

    function updatePrimaryActions() {
        if (desktopPrimaryAction) {
            desktopPrimaryAction.disabled = false;
            desktopPrimaryAction.textContent = 'Book now';
        }
        if (mobilePrimaryAction) {
            mobilePrimaryAction.disabled = false;
            mobilePrimaryAction.textContent = 'Book now';
        }
        if (desktopSecondaryAction) {
            desktopSecondaryAction.disabled = false;
            desktopSecondaryAction.textContent = 'Add to cart';
        }
        if (confirmDateTime) {
            confirmDateTime.disabled = !(selectedAvailabilityMode === 'pick' && selectedDateKey && selectedTime);
        }
    }

    function renderLocationOptions() {
        locationOptions.forEach(option => {
            option.classList.toggle('is-selected', String(option.dataset.location) === String(pendingLocationId));
        });
        focusLocationModalMap(pendingLocationId, false);
    }

    function renderSessionOptions() {
        sessionOptions.forEach(option => {
            const isSelected = String(option.dataset.value) === String(selectedVariantId);
            option.classList.toggle('is-selected', isSelected);
        });
        if (sessionTitle) sessionTitle.textContent = selectedVariantLabel;
        if (sessionMeta) {
            const unitPrice = Number(selectedVariant?.unit_price || 0);
            const sessionCount = Number(selectedVariant?.session_count || 1);
            const suffix = sessionCount > 1 && unitPrice > 0 ? ` · ${money(unitPrice)}/session` : '';
            const best = selectedVariant?.is_best_value ? ' · Best value' : '';
            sessionMeta.textContent = `${selectedVariant?.meta || selectedVariantLabel} · ${money(selectedVariantPrice)}${suffix}${best}`;
        }
    }

    function dateIsInRange(dateKey) {
        if (!minAvailableDate || !maxAvailableDate) return true;
        return dateKey >= minAvailableDate && dateKey <= maxAvailableDate;
    }

    function renderCalendarMonth() {
        const date = new Date(bookingMonth.getFullYear(), bookingMonth.getMonth(), 1, 12, 0, 0, 0);
        calendarTitle.textContent = monthLabel(date);
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = new Date(year, month, 1, 12, 0, 0, 0);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const offset = (firstDay.getDay() + 6) % 7;

        const fragment = document.createDocumentFragment();
        for (let i = 0; i < offset; i += 1) {
            const spacer = document.createElement('div');
            spacer.className = 'date-time-date is-disabled';
            spacer.style.opacity = '0';
            spacer.setAttribute('aria-hidden', 'true');
            fragment.appendChild(spacer);
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const cellDate = new Date(year, month, day, 12, 0, 0, 0);
            const dateKey = `${year}-${pad(month + 1)}-${pad(day)}`;
            const dayData = slotsByDay[dateKey] || null;
            const hasSlots = Boolean(dayData && Array.isArray(dayData.slots) && dayData.slots.length);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'date-time-date';
            button.textContent = String(day);
            button.dataset.date = dateKey;
            button.title = formatDateLabel(dateKey);
            if (!hasSlots || !dateIsInRange(dateKey)) {
                button.disabled = true;
                button.classList.add('is-disabled');
            }
            if (String(selectedDateKey) === String(dateKey)) {
                button.classList.add('is-selected');
            }
            button.addEventListener('click', () => {
                selectedDateKey = dateKey;
                selectedTime = null;
                selectedDateLabel = formatDateLabel(dateKey);
                clearHoldState();
                renderCalendarMonth();
                renderTimeOptions(dateKey);
                syncPanelSummary();
                renderDateTimeModalState();
            });
            fragment.appendChild(button);
        }

        dateTimeCalendarGrid.querySelectorAll('.date-time-date:not(.date-time-day-name)').forEach(node => {
            if (node.classList.contains('date-time-day-name')) return;
        });

        // Remove previous date cells while keeping weekday headings.
        Array.from(dateTimeCalendarGrid.querySelectorAll('.date-time-date')).forEach(node => {
            if (!node.classList.contains('date-time-day-name')) {
                node.remove();
            }
        });
        dateTimeCalendarGrid.appendChild(fragment);

        updateCalendarNav();
    }

    function updateCalendarNav() {
        if (prevMonthBtn) {
            prevMonthBtn.disabled = Boolean(minAvailableDate && bookingMonth <= new Date(`${minAvailableDate.slice(0, 7)}-01T00:00:00`));
        }
        if (nextMonthBtn) {
            nextMonthBtn.disabled = Boolean(maxAvailableDate && bookingMonth >= new Date(`${maxAvailableDate.slice(0, 7)}-01T00:00:00`));
        }
    }

    function renderTimeOptions(dateKey) {
        const dayData = slotsByDay[dateKey] || { slots: [] };
        const slots = Array.isArray(dayData.slots) ? dayData.slots : [];
        timeOptions.innerHTML = '';

        if (!slots.length) {
            const placeholder = document.createElement('div');
            placeholder.className = 'time-option is-disabled';
            placeholder.style.opacity = '.65';
            placeholder.style.cursor = 'default';
            placeholder.textContent = bookingPayload?.slotsByDay && Object.keys(bookingPayload.slotsByDay).length
                ? 'No slots on this date'
                : (requestMode ? 'No request times on this date' : 'No live slots');
            timeOptions.appendChild(placeholder);
            if (dateTimeHelper) {
                dateTimeHelper.textContent = bookingPayload?.slotsByDay && Object.keys(bookingPayload.slotsByDay).length
                    ? 'Pick another date.'
                    : (requestMode ? 'Choose another weekday request time.' : 'No live slots are currently published. Confirm later if you want to book now.');
            }
            if (dateTimeFooterNote) {
                dateTimeFooterNote.textContent = bookingPayload?.slotsByDay && Object.keys(bookingPayload.slotsByDay).length
                    ? 'Pick another date.'
                    : (requestMode ? 'Choose another weekday request time.' : 'No live slots are currently published.');
            }
            return;
        }

        slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'time-option';
            button.textContent = slot.start || slot.iso || 'Slot';
            button.dataset.time = slot.start || '';
            button.dataset.iso = slot.iso || '';
            const holdUntil = reservationHolds?.[dateKey]?.[slot.start];
            if (holdUntil && new Date(holdUntil).getTime() > Date.now()) {
                button.disabled = true;
                button.classList.add('is-disabled');
                button.textContent += ' (held)';
            } else {
                button.addEventListener('click', () => {
                    selectedTime = slot.start;
                    selectedDateLabel = formatDateLabel(dateKey);
                    qsa('.time-option', timeOptions).forEach(item => item.classList.remove('is-selected'));
                    button.classList.add('is-selected');
                    syncPanelSummary();
                    renderDateTimeModalState();
                });
            }
            if (selectedTime && selectedDateKey === dateKey && selectedTime === slot.start) {
                button.classList.add('is-selected');
            }
            timeOptions.appendChild(button);
        });

        if (dateTimeHelper) {
            dateTimeHelper.textContent = selectedDateKey ? `${formatDateLabel(dateKey)} — select a time` : (requestMode ? 'Pick a weekday, then choose a preferred time.' : 'Pick a date, then choose a time.');
        }
        if (dateTimeFooterNote) {
            dateTimeFooterNote.textContent = selectedDateKey ? `${formatDateLabel(dateKey)} — select a time` : (requestMode ? 'Pick a weekday, then choose a preferred time.' : 'Pick a date, then choose a time.');
        }
    }

    function renderDateTimeModalState() {
        if (!selectedDateKey) {
            dateTimeHelper.textContent = bookingPayload?.slotsByDay && Object.keys(bookingPayload.slotsByDay).length
                ? (requestMode ? 'Pick a weekday, then choose a preferred time.' : 'Pick a date, then choose a time.')
                : (requestMode ? 'Pick a weekday, then choose a preferred time.' : 'No live slots are currently published.');
            dateTimeFooterNote.textContent = bookingPayload?.slotsByDay && Object.keys(bookingPayload.slotsByDay).length
                ? (requestMode ? 'Pick a weekday, then choose a preferred time.' : 'Pick a date, then choose a time.')
                : (requestMode ? 'Pick a weekday, then choose a preferred time.' : 'No live slots are currently published.');
            confirmDateTime.disabled = true;
            renderCalendarMonth();
            return;
        }

        if (!selectedTime) {
            renderTimeOptions(selectedDateKey);
            confirmDateTime.disabled = true;
            return;
        }

        const text = `${formatDateLabel(selectedDateKey)} at ${selectedTime}`;
        dateTimeHelper.textContent = text;
        dateTimeFooterNote.textContent = text;
        confirmDateTime.disabled = !(selectedAvailabilityMode === 'pick' && selectedDateKey && selectedTime);
    }

    function setAvailabilityMode(mode) {
        selectedAvailabilityMode = mode;
        qsa('.availability-card', page).forEach(card => {
            card.classList.toggle('is-selected', String(card.dataset.availabilityMode) === mode);
        });

        if (mode === 'confirm') {
            selectedDateKey = null;
            selectedTime = null;
            selectedDateLabel = '';
            clearHoldState();
            syncPanelSummary();
            renderDateTimeModalState();
            return;
        }

        syncPanelSummary();
        renderDateTimeModalState();
    }

    async function fetchBookingAvailability() {
        if (!bookingEndpoint) {
            slotsByDay = {};
            reservationHolds = {};
            bookingPayload = null;
            renderCalendarMonth();
            renderDateTimeModalState();
            return;
        }

        const params = new URLSearchParams();
        if (selectedPriceOptionId) {
            params.set('price_option_id', String(selectedPriceOptionId));
        }
        if (selectedVariantLabel) {
            params.set('variant_label', selectedVariantLabel);
        }

        try {
            const res = await fetch(`${bookingEndpoint}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const json = await res.json();
            bookingPayload = json.bookingPayload || null;
            slotsByDay = bookingPayload?.slotsByDay || {};
            reservationHolds = bookingPayload?.reservationHolds || {};
            requestMode = !hasAnySlots(slotsByDay);
            if (requestMode) {
                slotsByDay = generateRequestSlots();
                reservationHolds = {};
            }
            bookingTimezone = bookingPayload?.availabilitySettings?.timezone || 'Europe/London';
            bookingDurationMinutes = Number(bookingPayload?.duration || config.durationMinutes || 60) || 60;
            const keys = Object.keys(slotsByDay).sort();
            minAvailableDate = keys[0] || null;
            maxAvailableDate = keys[keys.length - 1] || null;
            bookingMonth = minAvailableDate ? parseDateKey(minAvailableDate) : new Date();
        } catch (error) {
            slotsByDay = {};
            reservationHolds = {};
            bookingPayload = null;
            requestMode = true;
            slotsByDay = generateRequestSlots();
            minAvailableDate = null;
            maxAvailableDate = null;
            const keys = Object.keys(slotsByDay).sort();
            minAvailableDate = keys[0] || null;
            maxAvailableDate = keys[keys.length - 1] || null;
            bookingMonth = minAvailableDate ? parseDateKey(minAvailableDate) : new Date();
        }

        syncAvailabilityCopy();
        renderCalendarMonth();
        renderTimeOptions(selectedDateKey || minAvailableDate || '');
        renderDateTimeModalState();
        updatePrimaryActions();
    }

    function setSelectedLocation(locationId) {
        if (!locationId) return;
        selectedLocationId = String(locationId);
        pendingLocationId = selectedLocationId;
        const location = selectedLocation();
        const targetVariant = variantForLocation(location);
        if (targetVariant && String(targetVariant.id) !== String(selectedVariantId)) {
            setSelectedVariantById(targetVariant.id);
        }
        selectedLocationTitle.textContent = location.label || 'Location';
        selectedLocationAddress.textContent = location.address || location.notes || '';
        renderLocationOptions();
        syncPanelSummary();
        syncVariantQueryParam();
        focusMapsOnSelectedLocation();
    }

    function setSelectedVariantById(variantId) {
        const next = variants.find(v => String(v.id) === String(variantId));
        if (!next) return;
        selectedVariant = next;
        selectedVariantId = String(next.id);
        selectedVariantLabel = String(next.label || '');
        selectedVariantSelection = Array.isArray(next.selection) ? next.selection.slice() : [];
        selectedVariantPrice = Number(next.price ?? 0);
        selectedPriceOptionId = Number(next.price_option_id ?? 0) || null;
        groupCount = groupCountForVariant(next);

        const preference = variantLocationPreference(next);
        const currentLocation = selectedLocation();
        if (preference === 'in-person' && currentLocation?.online) {
            const fallback = firstPhysicalLocation();
            if (fallback && String(fallback.id) !== String(currentLocation.id)) {
                setSelectedLocation(fallback.id);
            }
        } else if (preference === 'online' && currentLocation && ! currentLocation.online) {
            const fallback = firstOnlineLocation();
            if (fallback && String(fallback.id) !== String(currentLocation.id)) {
                setSelectedLocation(fallback.id);
            }
        }

        renderSessionOptions();
        selectedDateKey = null;
        selectedTime = null;
        selectedDateLabel = '';
        clearHoldState();
        syncPanelSummary();
        syncVariantQueryParam();
        fetchBookingAvailability();
    }

    function variantIsGroup(variant) {
        const text = [variant?.label, ...(Array.isArray(variant?.selection) ? variant.selection : [])]
            .filter(Boolean).join(' ');
        return /group/i.test(text) || /(?:\d+\s*people?)/i.test(text);
    }

    function groupCountForVariant(variant) {
        const text = [variant?.label, ...(Array.isArray(variant?.selection) ? variant.selection : [])]
            .filter(Boolean).join(' ');
        const range = text.match(/(\d+)\s*(?:-|–|to)\s*(\d+)\s*group/i);
        const people = text.match(/(\d+)\s*people?/i);
        const minimum = range ? Number(range[1]) : (people ? Number(people[1]) : Number(config.groupMin || 3));
        const hasMaximum = config.groupMax !== null
            && config.groupMax !== undefined
            && config.groupMax !== ''
            && Number.isFinite(Number(config.groupMax));
        const maximum = hasMaximum ? Number(config.groupMax) : Infinity;
        return Math.min(maximum, Math.max(Number(config.groupMin || 3), minimum || 3));
    }

    function buildLineItem() {
        const location = selectedLocation();
        const isGroup = variantIsGroup(selectedVariant);
        const linePrice = isGroup ? selectedVariantPrice * groupCount : selectedVariantPrice;
        return {
            id: cartLineId(),
            product_id: offeringId,
            variant_id: selectedVariantId || null,
            variant_label: selectedVariantLabel,
            title: config.title || document.title,
            price: Number(linePrice || 0),
            qty,
            image: heroSlides[0]?.style?.backgroundImage ? (config.images?.[0] || null) : (config.images?.[0] || null),
            url: config.url || window.location.pathname,
            options: Array.isArray(selectedVariantSelection) ? selectedVariantSelection : [],
            booking: {
                mode: selectedAvailabilityMode,
                booking_flow: config.bookingFlow || 'flexible',
                request_mode: requestMode,
                date: selectedDateKey,
                time: selectedTime,
                date_label: selectedDateKey ? formatDateLabel(selectedDateKey) : null,
                start_time: selectedTime,
                timezone: bookingTimezone,
                reservation_id: reservationId,
                hold_expires_at: holdExpiresAt,
                duration_minutes: bookingDurationMinutes,
                location_id: location.id || null,
                location_label: location.label || null,
            },
            selected: Array.isArray(selectedVariantSelection) ? selectedVariantSelection : [],
            group_count: isGroup ? groupCount : qty,
            reservation_id: reservationId,
            hold_expires_at: holdExpiresAt,
            location: selectedLocationId,
            meta: {
                source_version: sourceVersion,
                product_id: offeringId,
                variant_id: selectedVariantId || null,
                variant_label: selectedVariantLabel,
                variant_options: Array.isArray(selectedVariantSelection) ? selectedVariantSelection : [],
                booking: {
                    mode: selectedAvailabilityMode,
                    booking_flow: config.bookingFlow || 'flexible',
                    request_mode: requestMode,
                    date: selectedDateKey,
                    time: selectedTime,
                    date_label: selectedDateKey ? formatDateLabel(selectedDateKey) : null,
                    start_time: selectedTime,
                    timezone: bookingTimezone,
                    reservation_id: reservationId,
                    hold_expires_at: holdExpiresAt,
                    duration_minutes: bookingDurationMinutes,
                    location_id: location.id || null,
                    location_label: location.label || null,
                },
                selected: Array.isArray(selectedVariantSelection) ? selectedVariantSelection : [],
                group_count: isGroup ? groupCount : qty,
                reservation_id: reservationId,
                hold_expires_at: holdExpiresAt,
                location: selectedLocationId,
                mode: config.mode || '',
                duration: config.durationLabel || '',
                title: config.title || '',
            },
            source_version: sourceVersion,
        };
    }

    function persistCartLocally() {
        const lineItem = buildLineItem();
        writeStoredCart(lineItem);
        return lineItem;
    }

    async function postCart(lineItem) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content || window.__csrfToken || '';
            await fetch(cartAddEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    id: offeringId,
                    qty: lineItem.qty,
                    variant_id: lineItem.variant_id,
                    variant_label: lineItem.variant_label,
                    source_version: sourceVersion,
                    product_id: offeringId,
                    title: lineItem.title,
                    price: lineItem.price,
                    image: lineItem.image,
                    url: lineItem.url,
                    options: lineItem.options,
                    selected: lineItem.selected,
                    group_count: lineItem.group_count,
                    reservation_id: lineItem.reservation_id,
                    hold_expires_at: lineItem.hold_expires_at,
                    location: lineItem.location,
                    booking: lineItem.booking,
                    meta: lineItem.meta,
                }),
            });
        } catch (error) {}
    }

    async function reserveSelectedSlot() {
        if (selectedAvailabilityMode !== 'pick' || !selectedDateKey || !selectedTime) {
            return false;
        }

        if (requestMode) {
            clearHoldState();
            return true;
        }

        if (reservationId || holdActive) {
            return true;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.content || window.__csrfToken || '';
        const payload = {
            date: selectedDateKey,
            time: selectedTime,
            duration_minutes: bookingDurationMinutes,
        };

        try {
            const response = await fetch(reservationHoldEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                const json = await response.json();
                reservationId = json.id || null;
                startHoldCountdown(json.expires_at || new Date(Date.now() + 600000).toISOString());
                return true;
            }
        } catch (error) {}

        reservationId = null;
        startHoldCountdown(new Date(Date.now() + 600000).toISOString());
        return true;
    }

    async function addToBasket(openCartAfter = false) {
        const lineItem = persistCartLocally();
        await postCart(lineItem);
        if (openCartAfter) {
            window.location.assign(cartUrl);
        }
    }

    async function handlePrimaryAction(openCartAfter = true) {
        if (selectedAvailabilityMode === 'pick') {
            if (!selectedDateKey || !selectedTime) {
                openDateTimeModal();
                return;
            }

            if (!holdActive) {
                await reserveSelectedSlot();
            }
        }

        await addToBasket(openCartAfter);
    }

    function openModalLayer(name) {
        const map = {
            booking: bookingModal,
            date: dateTimeModal,
            location: locationModal,
        };
        const backdrop = {
            booking: bookingBackdrop,
            date: calendarBackdrop,
            location: locationBackdrop,
        };
        const modal = map[name];
        const layerBackdrop = backdrop[name];
        if (!modal || !layerBackdrop) return;

        qsa('.booking-modal, .date-time-modal, .location-modal', page).forEach(item => item.classList.remove('is-open'));
        qsa('.modal-backdrop, .calendar-modal-backdrop, .location-modal-backdrop', page).forEach(item => item.classList.remove('is-open'));
        modal.classList.add('is-open');
        layerBackdrop.classList.add('is-open');
        activeModal = name;
        document.body.style.overflow = 'hidden';
    }

    function closeAllModals() {
        qsa('.booking-modal, .date-time-modal, .location-modal', page).forEach(item => item.classList.remove('is-open'));
        qsa('.modal-backdrop, .calendar-modal-backdrop, .location-modal-backdrop', page).forEach(item => item.classList.remove('is-open'));
        activeModal = null;
        document.body.style.overflow = '';
    }

    function restoreBookingFields() {
        if (bookingFields && modalFieldsSlot && bookingFields.parentElement === modalFieldsSlot) {
            bookingPanel.insertBefore(bookingFields, bookingPanel.querySelector('.desktop-booking-buttons'));
        }
        if (isMobile()) {
            bookingFields.style.display = '';
        } else {
            bookingFields.style.display = 'block';
        }
    }

    function openBookingModal() {
        if (!isMobile()) return;
        closeAllChildModals();
        modalFieldsSlot.appendChild(bookingFields);
        bookingFields.style.display = 'block';
        openModalLayer('booking');
        setTimeout(() => resizeMaps(), 50);
    }

    function closeBookingModalFn() {
        closeAllModals();
        restoreBookingFields();
        returnAfterChildModal = null;
    }

    function closeAllChildModals() {
        qsa('.date-time-modal, .location-modal', page).forEach(item => item.classList.remove('is-open'));
        qsa('.calendar-modal-backdrop, .location-modal-backdrop', page).forEach(item => item.classList.remove('is-open'));
        if (activeModal !== 'booking') {
            document.body.style.overflow = '';
        }
    }

    function openChildModal(name) {
        returnAfterChildModal = activeModal === 'booking' && isMobile() ? 'booking' : null;
        closeAllModals();
        openModalLayer(name);

        if (name === 'location') {
            pendingLocationId = selectedLocationId;
            renderLocationOptions();
            setTimeout(() => {
                initLocationModalMap();
                resizeMaps();
                focusLocationModalMap(pendingLocationId, false);
            }, 50);
        }

        if (name === 'date') {
            renderCalendarMonth();
            renderTimeOptions(selectedDateKey || minAvailableDate || '');
            renderDateTimeModalState();
        }
    }

    function closeChildModal(name, options = {}) {
        const shouldReopenParent = options.reopenParent !== false;
        const shouldReturnToBooking = shouldReopenParent && returnAfterChildModal === 'booking' && isMobile();

        closeAllModals();

        if (shouldReturnToBooking) {
            returnAfterChildModal = null;
            openBookingModal();
            return;
        }

        restoreBookingFields();
        returnAfterChildModal = null;
    }

    function createMarker() {
        const markerEl = document.createElement('div');
        markerEl.className = 'wow-map-marker';
        return markerEl;
    }

    function physicalLocations() {
        return locations.filter(location => !location.online && hasCoordinates(location));
    }

    function hasCoordinates(location) {
        if (!location || location.lat === null || location.lng === null || location.lat === '' || location.lng === '') {
            return false;
        }

        return Number.isFinite(Number(location.lat)) && Number.isFinite(Number(location.lng));
    }

    function initDesktopMap() {
        if (!desktopMapEl || desktopMap || !window.mapboxgl || !window.mapboxgl.Map) return;
        syncMapboxAccessToken();
        const physical = physicalLocations();
        if (!physical.length) return;

        const center = [Number(physical[0].lng), Number(physical[0].lat)];
        desktopMap = new window.mapboxgl.Map({
            container: 'desktopMap',
            style: 'mapbox://styles/mapbox/standard',
            center,
            zoom: 10.4,
            pitch: 55,
            bearing: -18,
            antialias: true,
        });

        desktopMap.addControl(new window.mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');
        desktopMap.on('style.load', () => {
            try {
                desktopMap.setConfigProperty('basemap', 'lightPreset', 'day');
            } catch (error) {}
            renderDesktopMarkers();
        });
    }

    function initLocationModalMap() {
        if (!page.querySelector('#locationModalMap') || locationModalMap || !window.mapboxgl || !window.mapboxgl.Map) return;
        syncMapboxAccessToken();
        const physical = physicalLocations();
        const center = physical.length ? [Number(physical[0].lng), Number(physical[0].lat)] : [-0.146, 51.391];

        locationModalMap = new window.mapboxgl.Map({
            container: 'locationModalMap',
            style: 'mapbox://styles/mapbox/standard',
            center,
            zoom: 10.7,
            pitch: 45,
            bearing: -18,
            antialias: true,
        });

        locationModalMap.on('style.load', () => {
            try {
                locationModalMap.setConfigProperty('basemap', 'lightPreset', 'day');
            } catch (error) {}
            renderLocationModalMarkers();
        });
    }

    function renderDesktopMarkers() {
        if (!desktopMap) return;
        desktopMarkers.forEach(marker => marker.remove());
        desktopMarkers = [];
        const physical = physicalLocations();
        if (!physical.length) return;

        const bounds = new window.mapboxgl.LngLatBounds();
        physical.forEach(location => {
            const coords = [Number(location.lng), Number(location.lat)];
            const marker = new window.mapboxgl.Marker({ element: createMarker(), anchor: 'bottom' })
                .setLngLat(coords)
                .addTo(desktopMap);
            desktopMarkers.push(marker);
            bounds.extend(coords);
        });
        desktopMap.fitBounds(bounds, { padding: 60, duration: 0 });
    }

    function renderLocationModalMarkers() {
        if (!locationModalMap) return;
        locationModalMarkers.forEach(marker => marker.remove());
        locationModalMarkers = [];
        const physical = physicalLocations();
        if (!physical.length) return;

        const bounds = new window.mapboxgl.LngLatBounds();
        physical.forEach(location => {
            const coords = [Number(location.lng), Number(location.lat)];
            const marker = new window.mapboxgl.Marker({ element: createMarker(), anchor: 'bottom' })
                .setLngLat(coords)
                .addTo(locationModalMap);
            locationModalMarkers.push(marker);
            bounds.extend(coords);
        });
        locationModalMap.fitBounds(bounds, { padding: 60, duration: 0 });
    }

    function focusMapsOnSelectedLocation() {
        const location = selectedLocation();
        if (desktopMap && location && !location.online && hasCoordinates(location)) {
            desktopMap.flyTo({
                center: [Number(location.lng), Number(location.lat)],
                zoom: 13.4,
                pitch: 55,
                bearing: -18,
                duration: 900,
            });
        }

        if (locationModalMap && location && !location.online && hasCoordinates(location)) {
            locationModalMap.flyTo({
                center: [Number(location.lng), Number(location.lat)],
                zoom: 13.4,
                pitch: 45,
                bearing: -18,
                duration: 700,
            });
        }

        if (desktopMapBox) {
            desktopMapBox.classList.toggle('is-online-mode', Boolean(location.online));
        }
        if (onlineVideo) {
            if (location.online) {
                onlineVideo.play().catch(() => {});
            } else {
                onlineVideo.pause();
            }
        }
        if (locationModalMapCard) {
            locationModalMapCard.classList.toggle('is-online', Boolean(location.online));
        }
    }

    function focusLocationModalMap(locationId, animate = true) {
        const location = locations.find(item => String(item.id) === String(locationId)) || selectedLocation();
        if (!locationModalMap || !location || location.online || !hasCoordinates(location)) {
            if (locationModalMapCard) {
                locationModalMapCard.classList.toggle('is-online', Boolean(location?.online));
            }
            return;
        }

        const action = animate ? 'flyTo' : 'jumpTo';
        locationModalMap[action]({
            center: [Number(location.lng), Number(location.lat)],
            zoom: 13.4,
            pitch: 45,
            bearing: -18,
            duration: animate ? 700 : 0,
        });

        if (locationModalMapTitle) locationModalMapTitle.textContent = location.label || 'Location';
        if (locationModalMapAddress) locationModalMapAddress.textContent = location.address || location.notes || '';
        if (locationModalMapCard) locationModalMapCard.classList.toggle('is-online', Boolean(location.online));
    }

    function resizeMaps() {
        if (desktopMap) desktopMap.resize();
        if (locationModalMap) locationModalMap.resize();
    }

    function openDateTimeModal() {
        openChildModal('date');
        renderCalendarMonth();
        renderTimeOptions(selectedDateKey || minAvailableDate || '');
        renderDateTimeModalState();
    }

    function slideHero() {
        if (heroSlides.length <= 1) return;
        let activeIndex = 0;
        setInterval(() => {
            heroSlides[activeIndex].classList.remove('is-active');
            activeIndex = (activeIndex + 1) % heroSlides.length;
            heroSlides[activeIndex].classList.add('is-active');
        }, 7000);
    }

    function navigateCalendar(direction) {
        bookingMonth = new Date(bookingMonth.getFullYear(), bookingMonth.getMonth() + direction, 1, 12, 0, 0, 0);
        renderCalendarMonth();
        renderTimeOptions(selectedDateKey || '');
        renderDateTimeModalState();
    }

    async function bootstrap() {
        if (sessionSelect) {
            sessionTrigger?.setAttribute('aria-expanded', 'false');
            renderSessionOptions();
        }

        renderLocationOptions();
        syncPanelSummary();
        updatePrimaryActions();
        if (selectedAvailabilityMode === 'pick') {
            qsa('.availability-card', page).forEach(card => {
                card.classList.toggle('is-selected', String(card.dataset.availabilityMode) === 'pick');
            });
        } else {
            qsa('.availability-card', page).forEach(card => {
                card.classList.toggle('is-selected', String(card.dataset.availabilityMode) === 'confirm');
            });
        }

        await fetchBookingAvailability();
        slideHero();
        await waitForMapboxReady();
        initDesktopMap();
        initLocationModalMap();
        focusMapsOnSelectedLocation();
        renderLocationModalMarkers();
        renderDesktopMarkers();
    }

    if (sessionTrigger) {
        sessionTrigger.addEventListener('click', () => {
            const isOpen = sessionSelect.classList.toggle('is-open');
            sessionTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    sessionOptions.forEach(option => {
        option.addEventListener('click', () => {
            qsa('#sessionSelect .custom-select-option', page).forEach(item => item.classList.remove('is-selected'));
            option.classList.add('is-selected');
            setSelectedVariantById(option.dataset.value);
            sessionSelect.classList.remove('is-open');
            sessionTrigger?.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('click', event => {
        if (sessionSelect && !sessionSelect.contains(event.target)) {
            sessionSelect.classList.remove('is-open');
            sessionTrigger?.setAttribute('aria-expanded', 'false');
        }
    });

    locationChips.forEach(chip => {
        chip.addEventListener('click', () => setSelectedLocation(chip.dataset.locationChip));
    });

    openLocationButtons.forEach(button => {
        button.addEventListener('click', () => openChildModal('location'));
    });

    locationOptions.forEach(option => {
        option.addEventListener('click', () => {
            const locationId = option.dataset.location;
            if (!locationId) return;
            pendingLocationId = locationId;
            setSelectedLocation(locationId);
        });
    });

    confirmLocationModal.addEventListener('click', () => {
        setSelectedLocation(pendingLocationId);
        closeChildModal('location', { reopenParent: true });
    });
    closeLocationModal.addEventListener('click', () => closeChildModal('location', { reopenParent: true }));
    locationBackdrop.addEventListener('click', () => closeChildModal('location', { reopenParent: true }));

    confirmDateTime.addEventListener('click', async () => {
        if (!(selectedAvailabilityMode === 'pick' && selectedDateKey && selectedTime)) return;
        await reserveSelectedSlot();
        closeChildModal('date', { reopenParent: true });
    });
    closeDateTimeModal.addEventListener('click', () => closeChildModal('date', { reopenParent: true }));
    cancelDateTimeModal.addEventListener('click', () => closeChildModal('date', { reopenParent: true }));
    calendarBackdrop.addEventListener('click', () => closeChildModal('date', { reopenParent: true }));

    qsa('.availability-card', page).forEach(card => {
        card.addEventListener('click', () => {
            const mode = card.dataset.availabilityMode;
            setAvailabilityMode(mode);
            if (mode === 'pick') {
                openDateTimeModal();
            }
        });
    });

    if (prevMonthBtn) prevMonthBtn.addEventListener('click', () => navigateCalendar(-1));
    if (nextMonthBtn) nextMonthBtn.addEventListener('click', () => navigateCalendar(1));

    timeOptions.addEventListener('click', event => {
        const button = event.target.closest('.time-option');
        if (!button || !button.dataset.time || button.classList.contains('is-disabled')) return;
        qsa('.time-option', timeOptions).forEach(item => item.classList.remove('is-selected'));
        button.classList.add('is-selected');
        selectedTime = button.dataset.time;
        selectedDateLabel = selectedDateKey ? formatDateLabel(selectedDateKey) : '';
        syncPanelSummary();
        renderDateTimeModalState();
    });

    minusQty.addEventListener('click', () => {
        qty = Math.max(1, qty - 1);
        syncPanelSummary();
    });
    plusQty.addEventListener('click', () => {
        qty = Math.min(20, qty + 1);
        syncPanelSummary();
    });

    minusGroup?.addEventListener('click', () => {
        groupCount = Math.max(Number(config.groupMin || 3), groupCount - 1);
        syncPanelSummary();
    });
    plusGroup?.addEventListener('click', () => {
        const hasMaximum = config.groupMax !== null
            && config.groupMax !== undefined
            && config.groupMax !== ''
            && Number.isFinite(Number(config.groupMax));
        const maximum = hasMaximum ? Number(config.groupMax) : Infinity;
        groupCount = Math.min(maximum, groupCount + 1);
        syncPanelSummary();
    });

    [desktopPrimaryAction, mobilePrimaryAction].filter(Boolean).forEach(button => {
        button.addEventListener('click', () => handlePrimaryAction(true));
    });
    if (desktopSecondaryAction) {
        desktopSecondaryAction.addEventListener('click', () => handlePrimaryAction(false));
    }

    openBookingButtons.forEach(button => {
        button.addEventListener('click', openBookingModal);
    });
    closeBookingModal.addEventListener('click', closeBookingModalFn);
    bookingBackdrop.addEventListener('click', closeBookingModalFn);

    document.addEventListener('click', event => {
        const locationCard = event.target.closest('[data-location-card]');
        if (locationCard) {
            const locationId = locationCard.dataset.locationId;
            if (locationId) {
                setSelectedLocation(locationId);
            }
        }
    });

    window.addEventListener('resize', () => {
        placeBookingPanel();
        if (!isMobile() && bookingModal.classList.contains('is-open')) {
            closeBookingModalFn();
        }
        if (!isMobile()) {
            mobileTicketBar?.classList.remove('is-visible');
            body.classList.remove(mobileMapBarVisibleClass);
        }
        initDesktopMap();
        initLocationModalMap();
        if (locationModal.classList.contains('is-open')) {
            focusLocationModalMap(pendingLocationId, false);
        }
        resizeMaps();
    });

    function updateMobileStickyBar() {
        if (!bookingPanel || !mobileTicketBar) return;
        if (!isMobile()) {
            mobileTicketBar.classList.remove('is-visible');
            body.classList.remove(mobileMapBarVisibleClass);
            return;
        }

        mobileTicketBar.classList.add('is-visible');
        body.classList.add(mobileMapBarVisibleClass);

        const observer = new IntersectionObserver(entries => {
            const entry = entries[0];
            const shouldShow = isMobile();
            mobileTicketBar.classList.toggle('is-visible', shouldShow);
            body.classList.toggle(mobileMapBarVisibleClass, shouldShow);
        }, { threshold: 0.12 });

        observer.observe(bookingPanel);
    }

    bootstrap();
    placeBookingPanel();
    updateMobileStickyBar();
})();
</script>
@if($isStoreProduct)
<script>
(() => {
    const options = Array.from(document.querySelectorAll('.store-v3-variant-option'));
    if (!options.length) return;

    const price = document.querySelector('.store-v3-checkout-panel .price');
    const compare = document.querySelector('.store-v3-checkout-panel .store-v3-compare-price');
    const actions = Array.from(document.querySelectorAll('.store-v3-checkout-panel [data-variant-id], #storeMobileBuyBar [data-variant-id]'));
    const baseUrl = new URL(window.location.href);

    const money = value => `£${Number(value || 0).toFixed(2)}`;
    const sync = option => {
        const variantId = option.dataset.variantId || '';
        const variantLabel = option.dataset.variantLabel || '';
        const variantPrice = Number(option.dataset.variantPrice || 0);
        const variantCompare = Number(option.dataset.variantCompare || 0);

        options.forEach(item => {
            const selected = item === option;
            item.classList.toggle('is-selected', selected);
            item.setAttribute('aria-checked', selected ? 'true' : 'false');
        });
        if (price) price.textContent = money(variantPrice);
        if (compare) {
            compare.textContent = variantCompare > variantPrice ? money(variantCompare) : '';
            compare.hidden = !(variantCompare > variantPrice);
        }
        actions.forEach(action => {
            action.dataset.variantId = variantId;
            action.dataset.variantLabel = variantLabel;
            action.dataset.price = variantPrice.toFixed(2);
        });
        baseUrl.searchParams.set('variant', variantId);
        window.history.replaceState({}, '', baseUrl.toString());
    };

    options.forEach(option => option.addEventListener('click', () => sync(option)));
})();
</script>
@endif
@endpush
@endif
