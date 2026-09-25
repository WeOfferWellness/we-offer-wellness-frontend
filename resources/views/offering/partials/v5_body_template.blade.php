@php
    /*
     * The v5 offering page deliberately owns its presentation.  It consumes
     * the same normalised product payload as the retired v3 renderer; pricing,
     * availability and cart endpoints remain application contracts, not UI
     * assumptions.
     */
    $offering = is_array($product ?? null) ? $product : [];
    $v5Title = trim((string) ($offering['title'] ?? 'Offering'));
    // `summary` is often a catalogue excerpt (and can end in an ellipsis).
    // The offering description is the source for the expandable detail.
    // Older payloads do not always put the full editor content in the same
    // field. Prefer the richest available source, rather than accidentally
    // rendering a short catalogue teaser as the entire About section.
    $v5DescriptionCandidates = array_filter([
        (string) ($offering['description'] ?? ''),
        (string) ($offering['body_html'] ?? ''),
        (string) ($offering['summary'] ?? ''),
    ], fn ($value) => trim($value) !== '');
    $v5Summary = (string) (collect($v5DescriptionCandidates)->sortByDesc(fn ($value) => mb_strlen(strip_tags($value)))->first() ?? '');
    // Supplier descriptions arrive with a mixture of pasted editor markup,
    // headings and list styles.  Keep the useful words, but render only our
    // own V5 paragraph/list elements so the public page remains consistent.
    $v5DescriptionSource = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $v5Summary);
    $v5DescriptionSource = preg_replace('#</?(?:p|div|section|article|h[1-6]|li|ul|ol|br)[^>]*>#i', "\n", $v5DescriptionSource);
    $v5DescriptionSource = html_entity_decode(strip_tags((string) $v5DescriptionSource), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $v5DescriptionSource = preg_replace("/[\t\x{00A0}]+/u", ' ', $v5DescriptionSource);
    $v5Lead = trim(preg_replace('/\s+/', ' ', (string) $v5DescriptionSource));
    $v5DescriptionBlocks = [];
    $v5List = null;
    foreach (preg_split('/\R+/', (string) $v5DescriptionSource) as $line) {
        $line = trim(preg_replace('/\s{2,}/', ' ', $line));
        if ($line === '') { $v5List = null; continue; }
        $kind = null;
        if (preg_match('/^(?:[•◦▪‣·\-*])\s*(.+)$/u', $line, $matches)) { $kind = 'ul'; $line = trim($matches[1]); }
        elseif (preg_match('/^\d+[.)]\s*(.+)$/', $line, $matches)) { $kind = 'ol'; $line = trim($matches[1]); }
        if ($kind) {
            if ($v5List !== $kind) { $v5DescriptionBlocks[] = ['type' => $kind, 'items' => []]; $v5List = $kind; }
            $v5DescriptionBlocks[array_key_last($v5DescriptionBlocks)]['items'][] = $line;
            continue;
        }
        $v5DescriptionBlocks[] = ['type' => 'p', 'text' => $line];
        $v5List = null;
    }
    if (!$v5DescriptionBlocks && trim(strip_tags($v5Summary)) !== '') $v5DescriptionBlocks[] = ['type' => 'p', 'text' => trim(strip_tags($v5Summary))];
    // Keep the hero concise and never echo source markup. The full clean
    // content is rendered in the About section below.
    $v5Lead = trim((string) data_get($v5DescriptionBlocks, '0.text', $v5Lead));
    $v5LeadWords = preg_split('/\s+/', $v5Lead, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($v5LeadWords) > 42) $v5Lead = implode(' ', array_slice($v5LeadWords, 0, 42)).'…';
    // A single long editor paragraph still needs the V5 Read more affordance.
    // Split only the rendered presentation, never the stored source content.
    $v5DescriptionPreviewLimit = 2;
    if (count($v5DescriptionBlocks) === 1 && ($v5DescriptionBlocks[0]['type'] ?? null) === 'p') {
        $v5Words = preg_split('/\s+/', (string) $v5DescriptionBlocks[0]['text'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($v5Words) > 28) {
            $v5DescriptionPreviewLimit = 1;
            $v5DescriptionBlocks = [
                ['type' => 'p', 'text' => implode(' ', array_slice($v5Words, 0, 28)).'…'],
                ['type' => 'p', 'text' => implode(' ', array_slice($v5Words, 28))],
            ];
        }
    }
    // The optional single-paragraph split above changes the rendered blocks,
    // so derive the visible/expandable groups only after normalisation.
    $v5DescriptionPreview = array_slice($v5DescriptionBlocks, 0, $v5DescriptionPreviewLimit);
    $v5DescriptionMore = array_slice($v5DescriptionBlocks, $v5DescriptionPreviewLimit);
    $v5ExpectSource = (string) ($offering['what_to_expect'] ?? 'Your practitioner will explain the session and answer any questions before you begin.');
    $v5ExpectSource = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $v5ExpectSource);
    $v5ExpectSource = preg_replace('#</?(?:p|div|section|article|h[1-6]|li|ul|ol|br)[^>]*>#i', "\n", $v5ExpectSource);
    $v5ExpectSource = html_entity_decode(strip_tags((string) $v5ExpectSource), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $v5ExpectBlocks = [];
    $v5ExpectList = null;
    foreach (preg_split('/\R+/', (string) $v5ExpectSource) as $line) {
        $line = trim(preg_replace('/\s{2,}/', ' ', $line));
        if ($line === '') { $v5ExpectList = null; continue; }
        $kind = null;
        if (preg_match('/^(?:[•◦▪‣·\-*✓])\s*(.+)$/u', $line, $matches)) { $kind = 'ul'; $line = trim($matches[1]); }
        elseif (preg_match('/^\d+[.)]\s*(.+)$/', $line, $matches)) { $kind = 'ol'; $line = trim($matches[1]); }
        if ($kind) {
            if ($v5ExpectList !== $kind) { $v5ExpectBlocks[] = ['type' => $kind, 'items' => []]; $v5ExpectList = $kind; }
            $v5ExpectBlocks[array_key_last($v5ExpectBlocks)]['items'][] = $line;
            continue;
        }
        $v5ExpectBlocks[] = ['type' => 'p', 'text' => $line];
        $v5ExpectList = null;
    }
    $v5IncludedSource = (string) ($offering['included'] ?? '');
    $v5IncludedSource = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $v5IncludedSource);
    $v5IncludedSource = preg_replace('#</?(?:p|div|li|ul|ol|br)[^>]*>#i', "\n", $v5IncludedSource);
    $v5IncludedSource = html_entity_decode(strip_tags((string) $v5IncludedSource), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $v5IncludedParts = preg_split('/(?=[✓•◦▪‣])|\R+/u', $v5IncludedSource) ?: [];
    $v5IncludedIntro = '';
    $v5IncludedItems = [];
    foreach ($v5IncludedParts as $includedPart) {
        $includedPart = trim(preg_replace('/\s{2,}/', ' ', $includedPart));
        if ($includedPart === '') continue;
        if (preg_match('/^[✓•◦▪‣]\s*(.+)$/u', $includedPart, $includedMatch)) $v5IncludedItems[] = trim($includedMatch[1]);
        elseif ($v5IncludedIntro === '') $v5IncludedIntro = $includedPart;
        else $v5IncludedItems[] = $includedPart;
    }
    $v5Currency = (string) ($offering['currency'] ?? 'GBP');
    $v5Source = strtolower((string) ($offering['source_version'] ?? 'legacy'));
    $v5Price = (float) ($offering['selectedVariantPrice'] ?? $offering['price_min'] ?? $offering['price'] ?? 0);
    $v5Images = array_values(array_filter((array) ($offering['images'] ?? [$offering['image'] ?? '']), fn ($image) => is_string($image) && trim($image) !== ''));
    if (!$v5Images) $v5Images = [asset('images/default-social-preview.jpg')];
    $v5ImageCount = count($v5Images);
    $v5RawLocations = array_values(array_filter((array) ($offering['locations'] ?? [])));
    $v5Locations = [];
    foreach ($v5RawLocations as $i => $location) {
        if (!is_array($location)) continue;
        $online = (bool) ($location['online'] ?? false) || str_contains(strtolower((string) ($location['label'] ?? $location['name'] ?? '')), 'online');
        $v5Locations[] = [
            'id' => (string) ($location['id'] ?? ($online ? 'online' : 'location-'.$i)),
            'label' => trim((string) ($location['label'] ?? $location['name'] ?? ($online ? 'Online' : 'Location'))),
            'short_label' => trim((string) ($location['city'] ?? $location['town'] ?? $location['locality'] ?? $location['label'] ?? $location['name'] ?? ($online ? 'Online' : 'Location'))),
            'address' => trim((string) ($location['full_address'] ?? $location['address'] ?? $location['notes'] ?? ($online ? 'Join from anywhere' : ''))),
            'lat' => $location['lat'] ?? $location['latitude'] ?? null,
            'lng' => $location['lng'] ?? $location['longitude'] ?? null,
            'online' => $online,
        ];
    }
    if (!$v5Locations) {
        $v5Locations[] = ['id' => 'online', 'label' => 'Online', 'short_label' => 'Online', 'address' => 'Join from anywhere', 'lat' => null, 'lng' => null, 'online' => true];
    }
    $v5Variants = [];
    foreach ((array) ($offering['booking_variants'] ?? $offering['variants'] ?? []) as $i => $variant) {
        if (!is_array($variant)) continue;
        $audienceType = strtolower(trim((string) ($variant['audience_type'] ?? '')));
        $pricingType = strtolower(trim((string) ($variant['pricing_type'] ?? '')));
        $selection = array_values(array_filter(array_map('strval', (array) ($variant['selection'] ?? []))));
        $text = strtolower(implode(' ', $selection));
        $kind = $audienceType === 'couple' || str_contains($text, 'couple') || str_contains($text, '2 person') ? 'couple' : ($audienceType === 'group' || str_contains($text, 'group') ? 'group' : 'single');
        $label = trim((string) ($variant['label'] ?? $variant['title'] ?? $variant['name'] ?? ''));
        if ($label === '' || strtolower($label) === 'option') {
            $label = match ($kind) { 'couple' => 'Couple', 'group' => 'Group', default => '1 Person' };
        }
        $priceOptionId = $variant['price_option_id'] ?? $variant['priceOptionId'] ?? null;
        if (!$priceOptionId && preg_match('/^po_(\d+)/', (string) ($variant['id'] ?? ''), $priceOptionMatch)) $priceOptionId = (int) $priceOptionMatch[1];
        $channels = array_values(array_unique(array_filter([
            (bool) ($variant['online'] ?? false) ? 'online' : null,
            (bool) ($variant['in_person'] ?? false) ? 'in_person' : null,
            str_contains($text, 'online') ? 'online' : null,
            (str_contains($text, 'in person') || str_contains($text, 'in-person')) ? 'in_person' : null,
        ])));
        $v5Variants[] = [
            'id' => (string) ($variant['id'] ?? $i),
            'label' => $label,
            'meta' => trim(implode(' · ', $selection)),
            'price' => (float) ($variant['price'] ?? $variant['amount'] ?? $v5Price),
            'price_option_id' => $priceOptionId ? (int) $priceOptionId : null,
            'kind' => $kind,
            'per_person' => (bool) ($variant['per_person'] ?? $variant['price_per_person'] ?? ($pricingType === 'per_person')),
            'group_min' => $kind === 'group' ? max(3, (int) ($variant['group_min'] ?? 3)) : null,
            'group_max' => is_numeric($variant['group_max'] ?? null) ? (int) $variant['group_max'] : null,
            'channels' => $channels,
            'active' => !array_key_exists('available', $variant) || (bool) $variant['available'],
        ];
    }
    if (!$v5Variants) $v5Variants[] = ['id' => 'default', 'label' => '1 Person', 'meta' => 'Private session', 'price' => $v5Price, 'price_option_id' => null, 'kind' => 'single', 'per_person' => false, 'channels' => [], 'active' => true];
    $v5GroupMax = data_get($offering, 'group_max') ?? data_get($offering, 'practitioner.group_max') ?? collect($v5Variants)->where('kind', 'group')->pluck('group_max')->filter()->max();
    $v5GroupMax = is_numeric($v5GroupMax) && (int) $v5GroupMax >= 3 ? (int) $v5GroupMax : null;
    $v5Duration = (int) ($offering['duration_minutes'] ?? $offering['duration'] ?? data_get($offering, 'booking.duration') ?? 0);
    $v5Duration = $v5Duration > 0 ? $v5Duration : null;
    $v5ActiveVariants = array_values(array_filter($v5Variants, fn (array $variant): bool => (bool) ($variant['active'] ?? true)));
    if (!$v5ActiveVariants) $v5ActiveVariants = $v5Variants;
    $v5FormatMoney = static function (float $amount) use ($v5Currency): string {
        if (abs($amount) < 0.00001) return 'Free';
        $prefix = strtoupper($v5Currency) === 'GBP' ? '£' : strtoupper($v5Currency).' ';
        return $prefix.number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    };
    $v5VariantsForLocation = static function (array $variants, array $location): array {
        $channel = !empty($location['online']) ? 'online' : 'in_person';
        $matches = array_values(array_filter($variants, static function (array $variant) use ($channel): bool {
            $channels = (array) ($variant['channels'] ?? []);
            return !$channels || in_array($channel, $channels, true);
        }));
        return $matches ?: $variants;
    };
    $v5PeopleValue = static function (array $variants): ?string {
        $types = array_values(array_unique(array_filter(array_map(fn (array $variant): string => (string) ($variant['kind'] ?? ''), $variants))));
        $hasSolo = in_array('single', $types, true);
        $hasCouple = in_array('couple', $types, true);
        $groups = array_values(array_filter($variants, fn (array $variant): bool => ($variant['kind'] ?? '') === 'group'));
        $hasGroup = !empty($groups);
        if (!$hasSolo && !$hasCouple && !$hasGroup) return null;
        if (!$hasGroup) return $hasSolo && $hasCouple ? '1–2 people' : ($hasCouple ? 'Couple' : '1 person');
        $min = min(array_map(fn (array $group): int => max(1, (int) ($group['group_min'] ?? 3)), $groups));
        $maxValues = array_values(array_filter(array_map(fn (array $group) => is_numeric($group['group_max'] ?? null) ? (int) $group['group_max'] : null, $groups)));
        $max = $maxValues ? max($maxValues) : null;
        $groupRange = $max ? $min.'–'.$max : $min.'+';
        if ($hasSolo && $hasCouple) return $max ? '1–'.$max.' people' : '1+ people';
        if ($hasCouple) return $max ? '2–'.$max.' people' : '2+ people';
        if ($hasSolo) return '1 or '.$groupRange;
        return $groupRange.' people';
    };
    $v5InitialLocation = $v5Locations[0];
    $v5InitialVariants = $v5VariantsForLocation($v5ActiveVariants, $v5InitialLocation);
    $v5OnlineLocations = array_values(array_filter($v5Locations, fn (array $location): bool => !empty($location['online'])));
    $v5PhysicalLocations = array_values(array_filter($v5Locations, fn (array $location): bool => empty($location['online'])));
    $v5LocationFact = null;
    if ($v5OnlineLocations && !$v5PhysicalLocations) $v5LocationFact = ['key' => 'location', 'label' => 'Location', 'value' => 'Online', 'icon' => 'globe', 'highlight' => true];
    elseif (count($v5PhysicalLocations) === 1 && !$v5OnlineLocations) $v5LocationFact = ['key' => 'location', 'label' => 'Location', 'value' => $v5PhysicalLocations[0]['short_label'] ?: $v5PhysicalLocations[0]['label'], 'icon' => 'pin', 'highlight' => true];
    elseif ($v5PhysicalLocations) {
        $physicalCount = count($v5PhysicalLocations);
        $v5LocationFact = ['key' => 'location', 'label' => 'Locations', 'value' => $v5OnlineLocations ? 'Online + '.$physicalCount.' '.($physicalCount === 1 ? 'location' : 'locations') : $physicalCount.' '.($physicalCount === 1 ? 'location' : 'locations'), 'icon' => 'pin', 'highlight' => true];
    }
    $v5QuickPrices = array_values(array_unique(array_map(fn (array $variant): float => (float) ($variant['price'] ?? 0), $v5InitialVariants)));
    sort($v5QuickPrices);
    $v5PriceFact = $v5QuickPrices ? ['key' => 'price', 'label' => count($v5QuickPrices) === 1 ? 'Price' : 'From', 'value' => $v5FormatMoney($v5QuickPrices[0]), 'icon' => 'price', 'dynamic' => 'price'] : null;
    $v5PeopleFactValue = $v5PeopleValue($v5InitialVariants);
    $v5PeopleFact = $v5PeopleFactValue ? ['key' => 'people', 'label' => 'People', 'value' => $v5PeopleFactValue, 'icon' => 'people', 'dynamic' => 'people'] : null;
    $v5SessionCount = (int) ($offering['session_count'] ?? $offering['sessions_count'] ?? $offering['package_sessions'] ?? data_get($offering, 'booking.session_count') ?? 1);
    $v5SessionCount = max(1, $v5SessionCount);
    $v5Venue = trim((string) ($offering['venue_type'] ?? $offering['setting'] ?? data_get($offering, 'venue.type') ?? ''));
    $v5Audience = trim((string) ($offering['audience_label'] ?? $offering['audience'] ?? data_get($offering, 'suitability.audience') ?? ''));
    $v5WomenOwned = (bool) ($offering['is_women_owned'] ?? data_get($offering, 'practitioner.is_women_owned') ?? false);
    $v5Confirmation = trim((string) ($offering['confirmation_method'] ?? ''));
    $v5QuickInfoFacts = array_values(array_filter([
        $v5LocationFact,
        $v5PriceFact,
        $v5PeopleFact,
        ['key' => 'sessions', 'label' => 'Sessions', 'value' => $v5SessionCount.' '.($v5SessionCount === 1 ? 'session' : 'sessions'), 'icon' => 'sessions'],
        $v5Duration ? ['key' => 'duration', 'label' => 'Duration', 'value' => $v5Duration.' min', 'icon' => 'duration'] : null,
        ['key' => 'availability', 'label' => 'Availability', 'value' => 'Live slots', 'icon' => 'availability', 'dynamic' => 'availability', 'defer' => true],
        $v5Venue !== '' ? ['key' => 'venue', 'label' => 'Venue', 'value' => $v5Venue, 'icon' => 'pin', 'compact' => true] : null,
        $v5Audience !== '' ? ['key' => 'audience', 'label' => "Who it's for", 'value' => $v5Audience, 'icon' => 'audience', 'compact' => true] : null,
        $v5WomenOwned ? ['key' => 'business_attribute', 'label' => 'Practitioner', 'value' => 'Women-owned', 'icon' => 'practitioner', 'compact' => true] : null,
        $v5Confirmation !== '' ? ['key' => 'confirmation', 'label' => 'Confirmation', 'value' => $v5Confirmation, 'icon' => 'confirmation', 'compact' => true] : null,
    ]));
    $v5Practitioner = is_array($offering['practitioner'] ?? null) ? $offering['practitioner'] : [];
    // The normalised offering payload calls the practitioner headshot `photo`.
    // Keep the other keys for older payloads, but never discard a real photo.
    $v5PractitionerImage = trim((string) ($v5Practitioner['photo'] ?? $v5Practitioner['image'] ?? $v5Practitioner['profile_image'] ?? $v5Practitioner['avatar'] ?? ''));
    $v5PractitionerProfileUrl = trim((string) ($v5Practitioner['profile_url'] ?? ''));
    $v5Reviews = array_values(array_filter((array) ($offering['client_reviews'] ?? $offering['reviews'] ?? []), fn ($review) => is_array($review)));
    $v5ReviewCount = max((int) ($offering['review_count'] ?? 0), count($v5Reviews));
    $v5ReviewAverage = $v5ReviewCount > 0 ? round((float) ($offering['rating'] ?? 0), 1) : 0.0;
    $v5ReviewStoreUrl = trim((string) ($v5Practitioner['review_url'] ?? ($v5PractitionerProfileUrl ? rtrim($v5PractitionerProfileUrl, '/').'/reviews' : '')));
    $v5ReviewModalOpen = auth()->check() && request()->boolean('review');
    $v5Related = array_values(array_filter((array) ($offering['related_offerings'] ?? $offering['related'] ?? []), fn ($item) => is_array($item) && (string) ($item['id'] ?? '') !== (string) ($offering['id'] ?? '')));
    $v5Guides = array_values(array_filter((array) ($offering['guides'] ?? []), fn ($guide) => is_array($guide)));
    $v5Format = trim((string) ($offering['modality'] ?? data_get($offering, 'category.name') ?? $type ?? 'Wellness'));
    $v5AboutType = strtolower(trim((string) ($offering['type'] ?? $type ?? 'offering')));
    if ($v5AboutType === '') $v5AboutType = 'offering';
    $v5Config = [
        'id' => (int) ($offering['id'] ?? 0), 'title' => $v5Title, 'source' => $v5Source, 'currency' => $v5Currency,
        'duration' => max(15, $v5Duration ?? 60), 'locations' => $v5Locations, 'variants' => $v5Variants,
        'selectedVariantId' => (string) ($offering['selectedVariantId'] ?? $v5Variants[0]['id']),
        'groupMax' => $v5GroupMax, 'mapboxToken' => (string) config('services.mapbox.token'),
        'practitionerProfileUrl' => $v5PractitionerProfileUrl,
        'reviewUrl' => trim((string) ($v5Practitioner['review_url'] ?? '')),
        'bookingEndpoint' => url('/api/booking/'.($v5Source === 'v3' ? 'offering' : 'product').'/'.(int) ($offering['id'] ?? 0)),
        'holdEndpoint' => url('/api/'.($v5Source === 'v3' ? 'booking/offering/'.(int) ($offering['id'] ?? 0).'/hold' : 'reservations/hold')), 'releaseEndpoint' => url('/api/reservations/release'),
        'cartEndpoint' => url('/api/cart/add'), 'cartUrl' => url('/cart'),
    ];
@endphp

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
<link href="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css" rel="stylesheet">
<style>
/* v5 is isolated so no retired offering selector participates in this layout. */
.wow-v5{--g:#4f9482;--gh:#3f806f;--gd:#0b3028;--ink:#17201d;--muted:#68736f;--line:#dce4e0;--soft:#f3f7f5;--soft2:#eef6f3;font-family:Instrument Sans,sans-serif;color:var(--ink);background:#fff;padding:0 0 72px}.wow-v5 *{box-sizing:border-box}.wow-v5 button,.wow-v5 input{font:inherit}.wow-v5 button{cursor:pointer}.wow-v5 a{color:inherit}.wow-v5__crumbs{border-block:1px solid var(--line)}.wow-v5__crumbs-in,.wow-v5__shell{width:min(calc(100% - 48px),1280px);margin:auto}.wow-v5__crumbs-in{height:48px;display:flex;align-items:center;gap:8px;overflow:auto;white-space:nowrap;font-size:12px}.wow-v5__crumbs-in a{color:var(--g);font-weight:600;text-decoration:none}.wow-v5__crumbs-in span{color:#89928e}.wow-v5__shell{display:grid;grid-template-columns:minmax(0,1fr) 372px;gap:28px;align-items:start;padding-top:30px}.wow-v5__eyebrow{margin:0 0 9px;color:var(--g);font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}.wow-v5 h1,.wow-v5 h2,.wow-v5 h3{color:var(--gd);font-family:Playfair Display,serif;font-weight:500}.wow-v5 h1{margin:0;font-size:clamp(40px,4.7vw,64px);line-height:.96;letter-spacing:-.045em}.wow-v5__lead{max-width:760px;margin:14px 0 0;color:var(--muted);font-size:15px;line-height:1.55}.wow-v5__gallery{position:relative;display:grid;grid-template-columns:1.45fr .68fr .9fr;grid-template-rows:205px 205px;gap:10px;margin-top:22px}.wow-v5__gallery figure{margin:0;overflow:hidden;border-radius:4px;background:#e9efec}.wow-v5__gallery figure:nth-child(1){grid-row:1/3}.wow-v5__gallery figure:nth-child(4){grid-column:3;grid-row:1/3}.wow-v5__gallery img{width:100%;height:100%;display:block;object-fit:cover;transition:transform .35s}.wow-v5__gallery figure:hover img{transform:scale(1.025)}.wow-v5__gallery button{position:absolute;top:50%;z-index:2;width:42px;height:42px;border:1px solid rgba(255,255,255,.9);border-radius:50%;background:#fffd;font-size:25px;transform:translateY(-50%)}.wow-v5__gallery .prev{left:10px}.wow-v5__gallery .next{left:calc(47.8% - 42px)}.wow-v5__gallery-count{position:absolute;right:10px;bottom:10px;padding:7px 10px;border-radius:999px;background:#fffd;font-size:11px;font-weight:700}.wow-v5__mobile-gallery{display:none}.wow-v5__snapshot{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin-top:16px}.wow-v5__snap{display:flex;gap:9px;align-items:center;min-height:60px;padding:10px;border:1px solid var(--line);border-radius:4px}.wow-v5__snap i{display:grid;place-items:center;width:32px;height:32px;border-radius:50%;background:var(--soft);color:var(--g);font-style:normal}.wow-v5__snap small,.wow-v5__snap strong{display:block}.wow-v5__snap small{color:var(--muted);font-size:9px}.wow-v5__snap strong{margin-top:2px;font-size:11px}.wow-v5__block{padding:32px 0;border-bottom:1px solid var(--line)}.wow-v5__block h2{margin:0;font-size:34px;letter-spacing:-.03em}.wow-v5__block p{max-width:760px;margin:10px 0 0;color:var(--muted);font-size:14px;line-height:1.7}.wow-v5__included{display:grid;gap:9px;margin:15px 0 0;padding:0;list-style:none;font-size:13px}.wow-v5__included li:before{content:'✓';margin-right:9px;color:var(--g);font-weight:700}.wow-v5__buybox{position:sticky;top:18px;z-index:5;border:1px solid var(--line);border-radius:4px;background:#fff;box-shadow:0 12px 32px #112d2612}.wow-v5__buyhead{padding:18px;border-bottom:1px solid var(--line)}.wow-v5__buyhead small{display:block;color:var(--muted);font-size:10px;letter-spacing:.1em;text-transform:uppercase}.wow-v5__price{margin-top:4px;font-size:34px;line-height:1}.wow-v5__status{display:inline-flex;gap:7px;align-items:center;margin-top:12px;padding:7px 9px;border-radius:4px;background:var(--soft2);color:#2f6659;font-size:10px;font-weight:700}.wow-v5__status:before{content:'';width:7px;height:7px;border-radius:50%;background:var(--g)}.wow-v5__buybody{padding:16px 18px 18px}.wow-v5__field{position:relative;margin-bottom:14px}.wow-v5__field>label{display:block;margin-bottom:6px;font-size:11px;font-weight:700}.wow-v5__trigger{width:100%;min-height:60px;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:11px 12px;border:1px solid var(--line);border-radius:4px;background:#fff;text-align:left}.wow-v5__trigger strong,.wow-v5__trigger small{display:block}.wow-v5__trigger strong{font-size:13px}.wow-v5__trigger small{margin-top:3px;color:var(--muted);font-size:10px}.wow-v5__trigger[aria-expanded=true]{border-color:var(--g);box-shadow:0 0 0 1px var(--g) inset}.wow-v5__menu{position:absolute;inset:calc(100% + 6px) 0 auto;z-index:10;display:none;padding:6px;border:1px solid var(--line);border-radius:4px;background:#fff;box-shadow:0 14px 30px #112d261f}.wow-v5__menu.open{display:block}.wow-v5__option{width:100%;display:flex;justify-content:space-between;align-items:center;gap:12px;min-height:54px;padding:10px;border:0;border-radius:3px;background:#fff;text-align:left}.wow-v5__option:hover,.wow-v5__option.selected{background:var(--soft2)}.wow-v5__option small{display:block;margin-top:3px;color:var(--muted);font-size:9px}.wow-v5__option b{font-size:12px}.wow-v5__group{display:none;margin:-4px 0 14px;padding:12px;border:1px solid #cfe1da;border-radius:4px;background:#f7fbf9}.wow-v5__group.show{display:block}.wow-v5__stepper{display:grid;grid-template-columns:38px 58px 38px;width:max-content;overflow:hidden;border:1px solid var(--line);border-radius:4px;background:#fff}.wow-v5__stepper button,.wow-v5__stepper input{height:38px;border:0;background:#fff;text-align:center}.wow-v5__stepper input{border-inline:1px solid var(--line);font-weight:700}.wow-v5__summary{padding:11px;border:1px solid #cee0d9;border-radius:4px;background:#f6faf8}.wow-v5__summary dl{display:grid;grid-template-columns:1fr auto;gap:7px 12px;margin:0}.wow-v5__summary dt,.wow-v5__summary dd{font-size:10px}.wow-v5__summary dt{color:var(--muted)}.wow-v5__summary dd{margin:0;text-align:right}.wow-v5__qty{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding:10px 11px;border:1px solid var(--line);border-radius:4px}.wow-v5__qty-controls{display:flex;overflow:hidden;border:1px solid var(--line);border-radius:4px}.wow-v5__qty-controls button,.wow-v5__qty-controls span{display:grid;place-items:center;width:34px;height:34px;border:0;background:#fff}.wow-v5__book{width:100%;height:48px;margin-top:13px;border:0;border-radius:4px;background:var(--g);color:#fff;font-weight:600}.wow-v5__book:hover{background:var(--gh)}.wow-v5__note{margin:9px 0 0;color:var(--muted);font-size:9px;text-align:center}.wow-v5__faq-item{border-bottom:1px solid var(--line)}.wow-v5__faq-item:last-child{border:0}.wow-v5__faq-item button{display:flex;justify-content:space-between;align-items:center;width:100%;min-height:64px;border:0;background:#fff;font-weight:700;text-align:left}.wow-v5__faq-item div{display:none;padding:0 0 18px;color:var(--muted);font-size:13px;line-height:1.65}.wow-v5__faq-item.open div{display:block}.wow-v5__person{display:grid;grid-template-columns:180px minmax(0,1fr);gap:28px}.wow-v5__person img{width:180px;aspect-ratio:1;object-fit:cover;border:1px solid var(--line);border-radius:4px}.wow-v5__person-actions{display:flex;gap:10px;margin-top:20px}.wow-v5__person-actions a,.wow-v5__person-actions button{min-height:44px;padding:0 16px;border:1px solid var(--g);border-radius:3px;background:#fff;color:var(--g);font-weight:600;text-decoration:none}.wow-v5__person-actions a{display:inline-flex;align-items:center;background:var(--g);color:#fff}.wow-v5__locations{display:grid;grid-template-columns:.78fr 1.22fr;min-height:370px;margin-top:18px;overflow:hidden;border:1px solid var(--line);border-radius:4px}.wow-v5__location-list{border-right:1px solid var(--line)}.wow-v5__location{display:grid;grid-template-columns:34px minmax(0,1fr) auto;gap:11px;align-items:center;width:100%;padding:16px;border:0;border-bottom:1px solid var(--line);background:#fff;text-align:left}.wow-v5__location.active{background:#f2f8f5;box-shadow:inset 3px 0 var(--g)}.wow-v5__location small{display:block;margin-top:3px;color:var(--muted);font-size:9px}.wow-v5__map{display:grid;place-items:center;padding:30px;background:linear-gradient(135deg,#eef6f3,#fbfdfc);color:var(--gd);text-align:center}.wow-v5__reviews,.wow-v5__related{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:18px}.wow-v5__review,.wow-v5__related-card{padding:18px;border:1px solid var(--line);border-radius:4px}.wow-v5__review blockquote{margin:12px 0 18px;font-size:14px;line-height:1.6}.wow-v5__stars{color:#b57b18}.wow-v5__guides{padding:42px 0 10px}.wow-v5__guides-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:22px}.wow-v5__guides-grid{display:grid;grid-template-columns:1.35fr 1fr 1fr;gap:10px}.wow-v5__guide{display:flex;flex-direction:column;justify-content:space-between;min-height:205px;padding:20px;border:1px solid var(--line);border-radius:4px;text-decoration:none}.wow-v5__guide.featured{grid-row:span 2;min-height:420px;background:linear-gradient(145deg,#f4f9f7,#fff 72%)}.wow-v5__guide span{color:var(--g);font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase}.wow-v5__guide h3{margin:18px 0 8px;font-size:23px}.wow-v5__guide.featured h3{font-size:38px}.wow-v5__mobilebar,.wow-v5__calendar{display:none}@media(max-width:991px){.wow-v5__shell{grid-template-columns:1fr}.wow-v5__aside{display:none}.wow-v5__snapshot{grid-template-columns:repeat(3,1fr)}}@media(max-width:700px){.wow-v5__crumbs-in,.wow-v5__shell{width:calc(100% - 30px)}.wow-v5__shell{padding-top:20px}.wow-v5__gallery{display:none}.wow-v5__mobile-gallery{position:relative;display:block;margin:18px -15px 0;overflow:hidden;background:#edf2ef}.wow-v5__mobile-track{display:flex;transition:transform .28s}.wow-v5__mobile-track figure{flex:0 0 100%;height:285px;margin:0}.wow-v5__mobile-track img{width:100%;height:100%;object-fit:cover}.wow-v5__mobile-gallery button{position:absolute;top:50%;width:40px;height:40px;border:1px solid #fffd;border-radius:50%;background:#fffd;font-size:22px;transform:translateY(-50%)}.wow-v5__mobile-gallery .prev{left:10px}.wow-v5__mobile-gallery .next{right:10px}.wow-v5__snapshot{gap:7px}.wow-v5__snap{display:block;min-height:78px;padding:9px 5px;text-align:center}.wow-v5__snap i{margin:auto auto 6px}.wow-v5__person{grid-template-columns:1fr}.wow-v5__person img{width:120px}.wow-v5__locations,.wow-v5__reviews,.wow-v5__related,.wow-v5__guides-grid{grid-template-columns:1fr}.wow-v5__location-list{border-right:0;border-bottom:1px solid var(--line)}.wow-v5__guides-head{align-items:start;flex-direction:column}.wow-v5__guide.featured{grid-row:auto;min-height:260px}.wow-v5__guide.featured h3{font-size:30px}.wow-v5__mobilebar{position:fixed;inset:auto 0 0;z-index:30;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 12px;background:#fff;border-top:1px solid var(--line);box-shadow:0 -8px 20px #0000000d}.wow-v5__mobilebar button{height:46px;padding:0 18px;border:0;border-radius:4px;background:var(--g);color:#fff;font-weight:600}.wow-v5{padding-bottom:80px}}
</style>
<style>
.wow-v5__map{position:relative;min-height:370px;overflow:hidden}
.wow-v5__map-canvas{position:absolute;inset:0}
.wow-v5__map-copy{position:relative;z-index:1;padding:30px}
.wow-v5__map.is-physical .wow-v5__map-copy{display:none}
.wow-v5__map.is-physical>div:not(.wow-v5__map-canvas){display:none}
.wow-v5__gallery .wow-v5__gallery-item{display:none;grid-column:auto;grid-row:auto;will-change:transform,opacity}.wow-v5__gallery .wow-v5__gallery-item.slot-0{display:block;grid-column:1;grid-row:1/3}.wow-v5__gallery .wow-v5__gallery-item.slot-1{display:block;grid-column:2;grid-row:1}.wow-v5__gallery .wow-v5__gallery-item.slot-2{display:block;grid-column:2;grid-row:2}.wow-v5__gallery .wow-v5__gallery-item.slot-3{display:block;grid-column:3;grid-row:1/3}.wow-v5__gallery.count-1{display:block;height:420px}.wow-v5__gallery.count-1 .wow-v5__gallery-item{display:block;height:420px}.wow-v5__gallery.count-2{grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:320px}.wow-v5__gallery.count-2 .wow-v5__gallery-item{display:block;height:320px;grid-column:auto;grid-row:auto}.wow-v5__gallery .wow-v5__gallery-item.is-moving{z-index:3}.wow-v5__gallery .wow-v5__gallery-item.is-entering{opacity:0}.wow-v5__gallery .wow-v5__gallery-item.is-entering.is-moving{opacity:1}.wow-v5__mobile-gallery-count{position:absolute;right:10px;bottom:10px;z-index:2;padding:6px 9px;border-radius:999px;background:#fffd;font-size:10px;font-weight:700}
.wow-v5__aside{position:relative;height:100%}
@media(min-width:992px){.wow-v5__buybox{top:140px}}
.wow-v5 .wow-v5__eyebrow{display:block;margin:0 0 9px!important;color:#4f9482!important;font-family:"Instrument Sans",sans-serif!important;font-size:11px!important;font-weight:700!important;letter-spacing:.18em!important;line-height:1.2!important;text-transform:uppercase!important}
.wow-quick-info{position:relative;overflow:hidden;margin-top:16px;border:1px solid var(--line);border-radius:4px;background:#fff}.wow-quick-info__grid{position:relative;display:grid;grid-template-columns:repeat(5,minmax(0,1fr))}.wow-quick-info__grid::before{content:"";position:absolute;top:30px;right:4%;left:4%;height:1px;background:linear-gradient(90deg,transparent 0%,rgba(79,148,130,.18) 7%,rgba(79,148,130,.18) 93%,transparent 100%)}.wow-quick-info__item{position:relative;z-index:1;display:grid;min-width:0;min-height:112px;padding:16px 18px 17px;border-right:1px solid var(--line);background:#fff;grid-template-rows:1fr auto auto}.wow-quick-info__item:last-child,.wow-quick-info__item:not([hidden]):not(:has(~ .wow-quick-info__item:not([hidden]))){border-right:0}.wow-quick-info__item--highlight{background:linear-gradient(135deg,#f4f9f7 0%,#fff 100%)}.wow-quick-info__icon{position:absolute;top:17px;left:18px;display:grid;width:28px;height:28px;border:1px solid #cfe0da;border-radius:50%;background:#fff;color:var(--g);box-shadow:0 0 0 6px #fff;place-items:center}.wow-quick-info__item--highlight .wow-quick-info__icon{box-shadow:0 0 0 6px #f7fbf9}.wow-quick-info__icon svg{width:15px;height:15px}.wow-quick-info__index{position:absolute;top:24px;right:15px;color:#a5afaa;font-size:8px;font-weight:700;letter-spacing:.12em}.wow-quick-info__label{align-self:end;margin:0 0 4px;color:var(--muted);font-family:"Instrument Sans",sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;line-height:1.2;text-transform:uppercase;grid-row:2}.wow-quick-info__value{min-height:23px;margin:0;overflow:hidden;color:var(--gd);font-family:"Instrument Sans",sans-serif;font-size:clamp(16px,1.8vw,21px);font-weight:650;letter-spacing:-.025em;line-height:1.05;text-overflow:ellipsis;white-space:nowrap;grid-row:3}.wow-quick-info__value--compact{font-size:15px}.wow-live-dot{position:relative;display:inline-block;width:7px;height:7px;margin-right:7px;border-radius:50%;background:var(--g);vertical-align:2px}.wow-live-dot:after{content:"";position:absolute;inset:-3px;border:1px solid rgba(79,148,130,.4);border-radius:50%;animation:wow-v5-quick-pulse 1.8s ease-out infinite}@keyframes wow-v5-quick-pulse{0%{transform:scale(.6);opacity:.8}70%{transform:scale(1.7);opacity:0}100%{opacity:0}}
.wow-v5__about-copy{max-width:760px}.wow-v5__about-copy p{margin:10px 0 0;color:var(--muted);font-size:14px;line-height:1.7}
/* Content is normalised before it is rendered, but old global list rules still
   exist on storefront pages. Keep every V5 list on the exact same typography. */
.wow-v5__about-list,.wow-v5__included{display:grid;gap:9px;margin:15px 0 0;color:var(--ink)!important;font-family:"Instrument Sans",sans-serif!important;font-size:13px!important;font-weight:400!important;line-height:1.55}
.wow-v5__about-list{padding-left:22px}.wow-v5__about-list li,.wow-v5__included li,.wow-v5__about-list li :is(strong,em,span,a),.wow-v5__included li :is(strong,em,span,a){color:var(--ink)!important;font-family:"Instrument Sans",sans-serif!important;font-size:13px!important;font-weight:400!important;line-height:1.55!important}
.wow-v5__about-list li,.wow-v5__included li{padding-left:2px}.wow-v5__about-list ol,.wow-v5__about-list{list-style-position:outside}.wow-v5__about-list ol li::marker,.wow-v5__about-list:is(ol) li::marker{color:var(--g);font-family:"Instrument Sans",sans-serif;font-size:13px;font-weight:700}
.wow-v5__about-list:not(ol),.wow-v5__included{padding-left:0;list-style:none}.wow-v5__about-list:not(ol) li,.wow-v5__included li{display:flex;gap:9px}.wow-v5__about-list:not(ol) li:before,.wow-v5__included li:before{content:'✓';flex:0 0 auto;color:var(--g)!important;font-family:"Instrument Sans",sans-serif!important;font-size:13px!important;font-weight:700!important;line-height:1.55!important}
.wow-v5__included,.wow-v5__included li,.wow-v5__included li :is(strong,em,span,a){color:var(--muted)!important;font-family:"Instrument Sans",sans-serif!important;font-size:14px!important;font-weight:400!important;line-height:1.7!important}.wow-v5__included li:before{color:var(--g)!important;font-size:14px!important;font-weight:700!important}
.wow-v5__about-toggle{display:inline-flex;align-items:center;gap:5px;min-height:32px;margin-top:14px;padding:0;border:0;background:transparent;color:var(--gd);font-size:10px;font-weight:700}.wow-v5__about-toggle span{display:inline-block;color:var(--g);font-size:12px;line-height:1;transition:transform .18s ease}.wow-v5__about-toggle[aria-expanded=true] span{transform:rotate(180deg)}
.wow-v5__review-head{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:22px}
.wow-v5__review-head-actions{display:flex;align-items:center;gap:12px}
.wow-v5__review-summary{display:flex;align-items:center;gap:12px}.wow-v5__review-summary>strong{font-size:44px;line-height:1;font-weight:600}.wow-v5__review-summary span>small{display:block;margin-top:3px;color:var(--muted);font-size:10px}.wow-v5__leave-review{display:inline-flex;align-items:center;height:40px;padding:0 15px;border:1px solid var(--g);border-radius:999px;color:var(--gd);font-size:11px;font-weight:700;text-decoration:none}.wow-v5__leave-review:hover{background:var(--soft)}
.wow-v5__reviews-empty{grid-column:1/-1;margin:0;color:var(--muted);font-size:14px}
.wow-v5__leave-review{justify-content:center;background:#fff}.wow-v5__review-modal{position:fixed;inset:0;z-index:1400;display:grid;place-items:center;padding:20px;background:rgba(10,23,20,.58)}.wow-v5__review-modal[hidden]{display:none}.wow-v5__review-modal-card{width:min(620px,100%);max-height:92vh;overflow:auto;border-radius:4px;background:#fff;box-shadow:0 24px 70px rgba(0,0,0,.24)}.wow-v5__review-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:20px;border-bottom:1px solid var(--line)}.wow-v5__review-modal-head h3{margin:3px 0 0;font-size:30px}.wow-v5__review-modal-head button{width:36px;height:36px;border:1px solid var(--line);border-radius:4px;background:#fff;font-size:20px}.wow-v5__review-form,.wow-v5__review-gate{display:grid;gap:10px;padding:20px}.wow-v5__review-form label{font-size:11px;font-weight:700}.wow-v5__review-form input,.wow-v5__review-form textarea{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:4px}.wow-v5__review-form textarea{min-height:120px;resize:vertical}.wow-v5__review-stars-input{display:flex;gap:5px}.wow-v5__review-stars-input button{border:0;background:transparent;color:#d6a82d;font-size:26px;padding:0}.wow-v5__review-stars-input button.is-off{color:#cbd3d0}.wow-v5__review-gate p{margin:0;color:var(--muted);line-height:1.6}.wow-v5__review-auth-tabs{display:flex;gap:8px;padding:0 20px}.wow-v5__review-auth-tabs button{height:36px;padding:0 12px;border:1px solid var(--line);border-radius:4px;background:#fff;font-size:11px;font-weight:700}.wow-v5__review-auth-tabs button.is-active{border-color:var(--g);background:var(--soft2);color:var(--gd)}.wow-v5__review-terms{display:flex;align-items:flex-start;gap:8px;color:var(--muted);font-weight:400!important;line-height:1.45}.wow-v5__review-terms input{width:auto!important;margin-top:2px}.wow-v5__review-terms a{color:var(--g)}.wow-v5__review-auth-error{margin:0;padding:9px 10px;border:1px solid #e4caca;border-radius:4px;background:#fff6f6;color:#8b3131;font-size:11px}
.wow-v5__calendar{--g:#4f9482;--gh:#3f806f;--gd:#0b3028;--ink:#17201d;--muted:#68736f;--line:#dce4e0;--soft:#f3f7f5;--soft2:#eef6f3;position:fixed;inset:0;z-index:1100;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(10,23,20,.55);color:var(--ink);font-family:Instrument Sans,sans-serif}.wow-v5__calendar-card{width:min(960px,100%);max-height:90vh;overflow:auto;border-radius:4px;background:#fff;box-shadow:0 24px 70px rgba(0,0,0,.2)}.wow-v5__calendar-head{display:flex;justify-content:space-between;gap:20px;padding:18px 20px;border-bottom:1px solid var(--line)}.wow-v5__calendar-head small{color:var(--muted);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.wow-v5__calendar-head h3{margin:4px 0 0;color:var(--gd);font-family:Playfair Display,serif;font-size:18px;font-weight:500}.wow-v5__calendar-close{width:36px;height:36px;padding:0;border:1px solid var(--line);border-radius:4px;background:#fff;color:var(--ink);font-size:20px}.wow-v5__calendar-grid{display:grid;grid-template-columns:1fr 1fr}.wow-v5__calendar-pane{padding:20px}.wow-v5__calendar-pane:first-child{border-right:1px solid var(--line)}.wow-v5__calendar-pane h4{margin:0 0 14px;color:var(--ink);font-size:14px}.wow-v5__calendar-days,.wow-v5__calendar-times{display:flex;flex-wrap:wrap;gap:8px}.wow-v5__calendar-slot{min-width:84px;height:44px;padding:0 10px;border:1px solid var(--line);border-radius:4px;background:#fff;color:var(--ink)}.wow-v5__calendar-slot:hover:not(:disabled),.wow-v5__calendar-slot.is-selected{border-color:var(--g);background:var(--soft2);color:var(--gd)}.wow-v5__calendar-slot:disabled{background:#f6f8f7;color:#c2cbc7}.wow-v5__calendar-hold{display:none;align-items:center;gap:10px;margin:0 0 16px;padding:11px 12px;border:1px solid #d3e3dd;border-radius:4px;background:#f4faf7;color:#28594d}.wow-v5__calendar-hold.show{display:flex}.wow-v5__calendar-hourglass{display:grid;place-items:center;width:26px;height:26px;border:1px solid #cfe1da;border-radius:50%;background:#fff;animation:wow-v5-spin 1.2s linear infinite}@keyframes wow-v5-spin{0%,45%{transform:rotate(0)}55%,95%{transform:rotate(180deg)}100%{transform:rotate(360deg)}}.wow-v5__calendar-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 20px;border-top:1px solid var(--line)}.wow-v5__calendar-foot>span{color:var(--muted);font-size:11px}.wow-v5__calendar-foot>span b{color:var(--ink)}.wow-v5__calendar-foot>div{display:flex;gap:8px}.wow-v5__calendar-action{border-radius:4px}
.wow-v5__calendar-month{display:flex;align-items:center;justify-content:space-between;margin-bottom:13px}.wow-v5__calendar-month button{width:40px;height:40px;border:1px solid var(--line);border-radius:4px;background:#fff;font-size:20px}.wow-v5__calendar-weekdays,.wow-v5__calendar-date-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}.wow-v5__calendar-weekdays{margin-bottom:6px}.wow-v5__calendar-weekdays span{text-align:center;color:var(--muted);font-size:10px}.wow-v5__calendar-date{height:46px;border:1px solid var(--line);border-radius:4px;background:#fff}.wow-v5__calendar-date:hover:not(:disabled),.wow-v5__calendar-date.is-selected{border-color:var(--g);background:var(--soft2)}.wow-v5__calendar-date:disabled{cursor:not-allowed;background:#f6f8f7;color:#c2cbc7}.wow-v5__calendar-blank{height:46px}.wow-v5__booking-sheet{--g:#4f9482;--gh:#3f806f;--gd:#0b3028;--ink:#17201d;--muted:#68736f;--line:#dce4e0;--soft:#f3f7f5;--soft2:#eef6f3;position:fixed;inset:0;z-index:1050;display:grid;place-items:center;padding:20px;background:rgba(10,23,20,.55);color:var(--ink);font-family:Instrument Sans,sans-serif}.wow-v5__booking-sheet-card{width:min(460px,100%);max-height:90vh;overflow:auto;border-radius:4px;background:#fff;box-shadow:0 24px 70px rgba(0,0,0,.2)}.wow-v5__booking-sheet-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid var(--line)}.wow-v5__booking-sheet-head small{color:var(--muted);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.wow-v5__booking-sheet-head h3{margin:4px 0 0;color:var(--gd);font-family:Playfair Display,serif;font-size:23px;font-weight:500;line-height:1.1}.wow-v5__booking-sheet-body{padding:16px 18px 18px}.wow-v5__booking-sheet-body>label,.wow-v5__booking-sheet-body [data-group-wrap]>label{display:block;margin:0 0 6px;color:var(--ink);font-size:11px;font-weight:700}.wow-v5__booking-sheet-options{display:grid;gap:7px;margin:8px 0 15px}.wow-v5__booking-sheet-options button{min-height:48px;padding:9px 10px;border:1px solid var(--line);border-radius:4px;background:#fff;color:var(--ink);text-align:left;font-size:12px;font-weight:600}.wow-v5__booking-sheet-options button.is-selected{border-color:var(--g);background:var(--soft2);color:var(--gd)}.wow-v5__booking-sheet .wow-v5__qty{color:var(--ink)}.wow-v5__booking-sheet .wow-v5__summary{color:var(--ink)}.wow-v5__booking-sheet .wow-v5__summary dt{color:var(--muted)}.wow-v5__booking-sheet .wow-v5__summary dd{color:var(--ink)}.wow-v5__booking-sheet .btn-wow{margin-top:14px;border-radius:4px}
@media(max-width:700px){.wow-v5__review-head{align-items:flex-start;flex-direction:column}.wow-v5__calendar{padding:0;align-items:flex-end}.wow-v5__calendar-card{width:100%;max-height:94vh;border-radius:4px 4px 0 0}.wow-v5__calendar-grid{grid-template-columns:1fr}.wow-v5__calendar-pane:first-child{border-right:0;border-bottom:1px solid var(--line)}.wow-v5__calendar-foot{align-items:flex-start;flex-direction:column}}
@media(min-width:701px) and (max-width:991px){.wow-v5__mobilebar{position:fixed;inset:auto 0 0;z-index:900;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 24px;background:#fff;border-top:1px solid var(--line);box-shadow:0 -8px 20px rgba(0,0,0,.05)}.wow-v5__mobilebar button{height:46px;padding:0 18px;border:0;border-radius:4px;background:var(--g);color:#fff;font-weight:600}.wow-v5{padding-bottom:76px}}
@media(max-width:700px){.wow-v5 a,.wow-v5 a:visited{color:var(--gd)!important}.wow-v5__person-actions a,.wow-v5__related-card a{color:#fff!important}.wow-v5__related-card a{display:inline-flex;align-items:center;justify-content:center;min-height:40px;margin-top:14px;padding:0 12px;border-radius:3px;background:var(--g);font-size:11px;font-weight:700;text-decoration:none}.wow-v5__booking-sheet{align-items:flex-end;padding:0;overflow:hidden}.wow-v5__booking-sheet-card{display:flex;flex-direction:column;width:100%;max-height:calc(100dvh - env(safe-area-inset-top) - 8px);border-radius:4px 4px 0 0}.wow-v5__booking-sheet-head{flex:0 0 auto}.wow-v5__booking-sheet-body{min-height:0;overflow-y:auto;overscroll-behavior:contain;padding-bottom:calc(22px + env(safe-area-inset-bottom))}}
@media(max-width:700px){.wow-quick-info{margin-right:-15px;margin-left:-15px;border-right:0;border-left:0;border-radius:0}.wow-quick-info__grid{grid-template-columns:repeat(3,minmax(0,1fr))}.wow-quick-info__grid::before{top:26px;right:8%;left:8%}.wow-quick-info__item{min-height:94px;padding:12px 7px 13px;text-align:center}.wow-quick-info__item[data-quick-position="3"]{border-right:0}.wow-quick-info__item[data-quick-position="4"],.wow-quick-info__item[data-quick-position="5"]{border-top:1px solid var(--line)}.wow-quick-info__item[data-quick-position="4"]{grid-column:span 2}.wow-quick-info__icon{top:13px;left:50%;width:25px;height:25px;transform:translateX(-50%);box-shadow:0 0 0 5px #fff}.wow-quick-info__item--highlight .wow-quick-info__icon{box-shadow:0 0 0 5px #f7fbf9}.wow-quick-info__icon svg{width:13px;height:13px}.wow-quick-info__index{display:none}.wow-quick-info__label{font-size:8px;letter-spacing:.06em}.wow-quick-info__value{font-size:15px;text-align:center}.wow-quick-info__value--compact{font-size:13px}}
/* Keep quick-info values compact at every viewport; labels carry the hierarchy. */
.wow-quick-info__value{font-size:16px}
@media(max-width:991px){body:has(#wowOfferingV5){--wow-utility-rail-bottom:76px}}
@media(max-width:700px){body:has(#wowOfferingV5){--wow-utility-rail-bottom:70px}.wow-quick-info__grid{border-left:1px solid var(--line);border-right:1px solid var(--line)}}
@media(prefers-reduced-motion:reduce){.wow-live-dot:after{animation:none}}
</style>
@endpush
@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-about-toggle]').forEach((button) => button.addEventListener('click', () => {
        const more = button.parentElement.querySelector('[data-about-more]');
        const expanded = button.getAttribute('aria-expanded') === 'true';
        more.hidden = expanded;
        button.setAttribute('aria-expanded', String(!expanded));
        button.firstChild.textContent = expanded ? 'Read more ' : 'Read less ';
    }));
    // Keep deep links deterministic: the selected server-resolved offering
    // price option is always represented by the existing `variant` query key.
    document.addEventListener('click', (event) => {
        const option = event.target.closest('[data-variant]');
        if (!option) return;
        try {
            const variant = JSON.parse(option.dataset.variant);
            const url = new URL(window.location.href);
            url.searchParams.set('variant', variant.id);
            window.history.replaceState({}, '', url);
        } catch (_) {}
    }, true);
    const modal = document.getElementById('v5ReviewModal');
    if (!modal) return;
    const close = () => { modal.hidden = true; };
    document.querySelectorAll('[data-review-open]').forEach((button) => button.addEventListener('click', () => { modal.hidden = false; modal.querySelector('[data-review-close], input, textarea')?.focus(); }));
    modal.querySelectorAll('[data-review-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) close(); });
    const rating = modal.querySelector('#v5ReviewRating');
    modal.querySelectorAll('[data-review-star]').forEach((button) => button.addEventListener('click', () => {
        const value = Number(button.dataset.reviewStar);
        if (rating) rating.value = value;
        modal.querySelectorAll('[data-review-star]').forEach((star) => star.classList.toggle('is-off', Number(star.dataset.reviewStar) > value));
    }));
    modal.querySelectorAll('[data-review-auth-tab]').forEach((button) => button.addEventListener('click', () => {
        const panel = button.dataset.reviewAuthTab;
        modal.querySelectorAll('[data-review-auth-tab]').forEach((tab) => tab.classList.toggle('is-active', tab === button));
        modal.querySelectorAll('[data-review-auth-panel]').forEach((form) => { form.hidden = form.dataset.reviewAuthPanel !== panel; });
        modal.querySelector(`[data-review-auth-panel="${panel}"] input`)?.focus();
    }));
    modal.querySelectorAll('[data-review-auth-panel]').forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('button[type="submit"]');
        const error = form.querySelector('[data-review-auth-error]') || document.createElement('p');
        error.dataset.reviewAuthError = '';
        error.className = 'wow-v5__review-auth-error';
        error.hidden = true;
        if (!error.parentElement) form.prepend(error);
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok && response.redirected) {
                window.location.assign(form.querySelector('[name="redirect"]').value);
                return;
            }
            const payload = await response.json().catch(() => ({}));
            const messages = Object.values(payload.errors || {}).flat();
            error.textContent = messages[0] || 'We could not complete that request. Please try again.';
            error.hidden = false;
        } catch (_) {
            error.textContent = 'We could not reach the login service. Please try again.';
            error.hidden = false;
        } finally { submit.disabled = false; }
    }));
})();
</script>
@endpush

<main class="wow-v5" id="wowOfferingV5" data-config='@json($v5Config)'>
    <div class="wow-v5__shell"><section><p class="wow-v5__eyebrow">{{ $v5Format }}</p><h1>{{ $v5Title }}</h1>@if($v5Lead)<p class="wow-v5__lead">{{ $v5Lead }}</p>@endif
        <div class="wow-v5__gallery count-{{ min($v5ImageCount, 4) }}" id="v5Gallery">
            @foreach($v5Images as $image)
                <figure class="wow-v5__gallery-item {{ $loop->index < 4 ? 'slot-'.$loop->index : '' }}"><img src="{{ $image }}" alt="{{ $v5Title }}"></figure>
            @endforeach
            @if($v5ImageCount >= 3)
                <button class="prev" type="button" aria-label="Previous image">‹</button>
                <button class="next" type="button" aria-label="Next image">›</button>
                <span class="wow-v5__gallery-count">1 / {{ $v5ImageCount }}</span>
            @endif
        </div>
        <div class="wow-v5__mobile-gallery" id="v5MobileGallery">
            <div class="wow-v5__mobile-track">
                @foreach($v5Images as $image)
                    <figure><img src="{{ $image }}" alt="{{ $v5Title }}"></figure>
                @endforeach
            </div>
            @if($v5ImageCount > 1)
                <button class="prev" type="button" aria-label="Previous image">‹</button>
                <button class="next" type="button" aria-label="Next image">›</button>
                <span class="wow-v5__mobile-gallery-count">1 / {{ $v5ImageCount }}</span>
            @endif
        </div>
        @include('offering.partials.quick_info.index', ['facts' => $v5QuickInfoFacts])
        <section class="wow-v5__block wow-v5__about" id="about">
            <p class="wow-v5__eyebrow">Overview</p><h2>About this {{ \Illuminate\Support\Str::singular($v5AboutType) }}</h2>
            <div class="wow-v5__about-copy">
                @forelse($v5DescriptionPreview as $block)
                    @if($block['type'] === 'p')
                        <p>{{ $block['text'] }}</p>
                    @elseif($block['type'] === 'ul')
                        <ul class="wow-v5__about-list">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                    @else
                        <ol class="wow-v5__about-list">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ol>
                    @endif
                @empty
                    <p>Find out more about this wellbeing offering and book with confidence.</p>
                @endforelse
                @if($v5DescriptionMore)
                    <div data-about-more hidden>
                        @foreach($v5DescriptionMore as $block)
                            @if($block['type'] === 'p')
                                <p>{{ $block['text'] }}</p>
                            @elseif($block['type'] === 'ul')
                                <ul class="wow-v5__about-list">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                            @else
                                <ol class="wow-v5__about-list">@foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach</ol>
                            @endif
                        @endforeach
                    </div>
                    <button type="button" class="wow-v5__about-toggle" data-about-toggle aria-expanded="false">Read more <span aria-hidden="true">↓</span></button>
                @endif
            </div>
        </section>
        @if($v5IncludedIntro !== '' || $v5IncludedItems)
            <section class="wow-v5__block"><p class="wow-v5__eyebrow">Included</p><h2>What’s included</h2>
                @if($v5IncludedIntro !== '')<p>{{ $v5IncludedIntro }}</p>@endif
                @if($v5IncludedItems)<ul class="wow-v5__included">@foreach($v5IncludedItems as $item)<li>{{ $item }}</li>@endforeach</ul>@endif
            </section>
        @endif
        <section class="wow-v5__block wow-v5__expect">
            <p class="wow-v5__eyebrow">What to expect</p><h2>What happens on the day?</h2>
            <div class="wow-v5__about-copy">
                @foreach($v5ExpectBlocks as $block)
                    @if($block['type'] === 'p')
                        <p>{{ $block['text'] }}</p>
                    @elseif($block['type'] === 'ul')
                        <ul class="wow-v5__about-list">
                            @foreach($block['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @else
                        <ol class="wow-v5__about-list">
                            @foreach($block['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ol>
                    @endif
                @endforeach
            </div>
        </section>
        <section class="wow-v5__block"><div id="v5Faq"><div class="wow-v5__faq-item"><button type="button">Participant guidelines<b>+</b></button><div>{!! \App\Support\ContentFormatter::format((string) ($offering['guidelines'] ?? $offering['suitability'] ?? 'Please review the offering information before booking.')) !!}</div></div><div class="wow-v5__faq-item"><button type="button">Cancellation &amp; changes<b>+</b></button><div>{!! \App\Support\ContentFormatter::format((string) ($offering['cancellation_policy'] ?? 'Terms are shown before checkout and in your confirmation email.')) !!}</div></div></div></section>
        @if($v5Practitioner)<section class="wow-v5__block"><div class="wow-v5__person"><img src="{{ $v5PractitionerImage ?: 'https://studio.weofferwellness.co.uk/assets/img/icons/no-user-icon.jpg' }}" alt="{{ $v5Practitioner['name'] ?? 'Practitioner' }}"><div><p class="wow-v5__eyebrow">Meet the practitioner</p><h2>{{ $v5Practitioner['name'] ?? 'Your practitioner' }}</h2><p>{{ $v5Practitioner['bio'] ?? $v5Practitioner['description'] ?? '' }}</p>@if($v5PractitionerProfileUrl)<div class="wow-v5__person-actions"><a href="{{ $v5PractitionerProfileUrl }}">View profile</a></div>@endif</div></div></section>@endif
        <section class="wow-v5__block"><p class="wow-v5__eyebrow">Choose where</p><h2>{{ count($v5Locations) > 1 ? 'Available locations' : 'Available location' }}</h2><div class="wow-v5__locations"><div class="wow-v5__location-list" id="v5Locations">@foreach($v5Locations as $index => $location)<button class="wow-v5__location {{ $index === 0 ? 'active' : '' }}" type="button" data-location="{{ $location['id'] }}"><span>⌖</span><span><strong>{{ $location['label'] }}</strong><small>{{ $location['address'] }}</small></span><span>→</span></button>@endforeach</div><div class="wow-v5__map" id="v5Map"><div><div style="font-size:28px;color:#4f9482">◎</div><h3 id="v5MapTitle">{{ $v5Locations[0]['online'] ? 'Join online' : $v5Locations[0]['label'] }}</h3><p id="v5MapCopy">{{ $v5Locations[0]['online'] ? 'Your joining link is sent after booking.' : $v5Locations[0]['address'] }}</p></div></div></div></section>
        <section class="wow-v5__block" id="reviews"><div class="wow-v5__review-head"><div><p class="wow-v5__eyebrow">Customer reviews</p><h2>What customers say</h2></div><div class="wow-v5__review-head-actions">
            @if($v5ReviewCount > 0)<div class="wow-v5__review-summary"><strong>{{ number_format($v5ReviewAverage, 1) }}</strong><span><span class="wow-v5__stars" aria-label="{{ $v5ReviewAverage }} out of 5 stars">★★★★★</span><small>{{ $v5ReviewCount }} verified {{ \Illuminate\Support\Str::plural('review', $v5ReviewCount) }}</small></span></div>@endif
            @if(!empty($v5Practitioner['review_url']) || $v5PractitionerProfileUrl)<button class="wow-v5__leave-review" type="button" data-review-open>Leave a review</button>@endif
        </div></div><div class="wow-v5__reviews">@forelse(array_slice($v5Reviews, 0, 3) as $review)<article class="wow-v5__review"><div class="wow-v5__stars" aria-label="{{ (int) ($review['rating'] ?? 0) }} out of 5 stars">{{ str_repeat('★', max(1, min(5, (int) ($review['rating'] ?? 5)))) }}</div><blockquote>“{{ $review['body'] ?? $review['review'] ?? '' }}”</blockquote><small>{{ $review['author'] ?? $review['name'] ?? 'Verified client' }}{{ !empty($review['date']) ? ' · '.$review['date'] : '' }}</small></article>@empty<p class="wow-v5__reviews-empty">No reviews yet.</p>@endforelse</div></section>
        @if($v5ReviewStoreUrl)
            <div class="wow-v5__review-modal" id="v5ReviewModal" role="dialog" aria-modal="true" aria-labelledby="v5ReviewTitle" @unless($v5ReviewModalOpen) hidden @endunless><section class="wow-v5__review-modal-card"><div class="wow-v5__review-modal-head"><div><p class="wow-v5__eyebrow">Share your experience</p><h3 id="v5ReviewTitle">Leave a review</h3></div><button type="button" data-review-close aria-label="Close">×</button></div>
                @auth
                    <form class="wow-v5__review-form" action="{{ $v5ReviewStoreUrl }}" method="post">@csrf<input type="hidden" name="redirect" value="{{ request()->fullUrlWithQuery(['review' => 1]) }}#reviews"><label>Your rating</label><div class="wow-v5__review-stars-input">@for($rating = 1; $rating <= 5; $rating++)<button type="button" data-review-star="{{ $rating }}" aria-label="{{ $rating }} stars">★</button>@endfor</div><input type="hidden" name="rating" id="v5ReviewRating" value="5"><label for="v5ReviewTitleInput">Review title <small>Optional</small></label><input id="v5ReviewTitleInput" name="review_title" maxlength="120"><label for="v5ReviewText">Your review</label><textarea id="v5ReviewText" name="review_text" minlength="10" maxlength="2000" required></textarea><button class="wow-v5__book" type="submit">Submit review</button></form>
                @else
                    <div class="wow-v5__review-gate"><p>Login or create a free client account to leave a review. Reviews are linked to real client accounts.</p><div class="wow-v5__review-auth-tabs"><button type="button" class="is-active" data-review-auth-tab="login">Login</button><button type="button" data-review-auth-tab="register">Create account</button></div>
                        <form class="wow-v5__review-form" data-review-auth-panel="login" action="{{ url('/login') }}" method="post">@csrf<input type="hidden" name="redirect" value="{{ request()->fullUrlWithQuery(['review' => 1]) }}#reviews"><label for="v5ReviewLoginEmail">Email</label><input id="v5ReviewLoginEmail" name="email" type="email" autocomplete="email" required><label for="v5ReviewLoginPassword">Password</label><input id="v5ReviewLoginPassword" name="password" type="password" autocomplete="current-password" required><button class="wow-v5__book" type="submit">Login</button></form>
                        <form class="wow-v5__review-form" data-review-auth-panel="register" action="{{ url('/register') }}" method="post" hidden>@csrf<input type="hidden" name="redirect" value="{{ request()->fullUrlWithQuery(['review' => 1]) }}#reviews"><label for="v5ReviewFirstName">First name</label><input id="v5ReviewFirstName" name="first_name" autocomplete="given-name" required><label for="v5ReviewLastName">Last name</label><input id="v5ReviewLastName" name="last_name" autocomplete="family-name" required><label for="v5ReviewRegisterEmail">Email</label><input id="v5ReviewRegisterEmail" name="email" type="email" autocomplete="email" required><label for="v5ReviewRegisterPassword">Create password</label><input id="v5ReviewRegisterPassword" name="password" type="password" autocomplete="new-password" required><label for="v5ReviewRegisterPasswordConfirm">Confirm password</label><input id="v5ReviewRegisterPasswordConfirm" name="password_confirmation" type="password" autocomplete="new-password" required><label class="wow-v5__review-terms"><input type="checkbox" name="terms" value="1" required> I agree to the <a href="/terms">Terms</a> and <a href="/privacy">Privacy Policy</a>.</label><button class="wow-v5__book" type="submit">Create account</button></form>
                    </div>
                @endauth
            </section></div>
        @endif
        @if($v5Related)<section class="wow-v5__block"><p class="wow-v5__eyebrow">Continue your wellness journey</p><h2>You may also like</h2><div class="wow-v5__related">@foreach(array_slice($v5Related,0,3) as $related)<article class="wow-v5__related-card"><small>{{ $related['format'] ?? $related['category'] ?? '' }}</small><h3>{{ $related['title'] ?? 'Offering' }}</h3><p>£{{ number_format((float) ($related['price_min'] ?? $related['price'] ?? 0), 2) }}</p><a href="{{ $related['url'] ?? '#' }}">VIEW &amp; BOOK</a></article>@endforeach</div></section>@endif
        @if($v5Guides)<section class="wow-v5__guides"><div class="wow-v5__guides-head"><div><p class="wow-v5__eyebrow">Explore guides</p><h2>{{ $v5Format }} guides</h2></div></div><div class="wow-v5__guides-grid">@foreach(array_slice($v5Guides,0,4) as $index => $guide)<a class="wow-v5__guide {{ $index === 0 ? 'featured' : '' }}" href="{{ $guide['url'] ?? '#' }}"><span>{{ $index === 0 ? 'Start here' : 'Guide' }}</span><div><h3>{{ $guide['title'] ?? 'Guide' }}</h3><p>{{ $guide['excerpt'] ?? $guide['summary'] ?? '' }}</p></div><b>Read guide →</b></a>@endforeach</div></section>@endif
    </section>
    <aside class="wow-v5__aside"><section class="wow-v5__buybox" id="v5Buybox"><div class="wow-v5__buyhead"><small>From</small><div class="wow-v5__price" id="v5Price">£{{ number_format($v5Price,2) }}</div><div class="wow-v5__status" id="v5Status">Checking availability</div></div><div class="wow-v5__buybody"><div class="wow-v5__field" id="v5LocationField"><label>Location</label><button class="wow-v5__trigger" id="v5LocationTrigger" type="button" aria-expanded="false"><span><strong id="v5LocationTitle">{{ $v5Locations[0]['label'] }}</strong><small id="v5LocationSub">{{ $v5Locations[0]['address'] }}</small></span><span>⌄</span></button><div class="wow-v5__menu" id="v5LocationMenu">@foreach($v5Locations as $index => $location)<button class="wow-v5__option {{ $index===0?'selected':'' }}" type="button" data-location-option="{{ $location['id'] }}"><span><b>{{ $location['label'] }}</b><small>{{ $location['address'] }}</small></span></button>@endforeach</div></div><div class="wow-v5__field"><label>Session</label><button class="wow-v5__trigger" id="v5VariantTrigger" type="button" aria-expanded="false"><span><strong id="v5VariantTitle">{{ $v5Variants[0]['label'] }}</strong><small id="v5VariantMeta">{{ $v5Variants[0]['meta'] }}</small></span><span>⌄</span></button><div class="wow-v5__menu" id="v5VariantMenu">@foreach($v5Variants as $index => $variant)<button class="wow-v5__option {{ $index===0?'selected':'' }}" type="button" data-variant='@json($variant)'><span><b>{{ $variant['label'] }}</b><small>{{ $variant['meta'] }}</small></span><b>£{{ number_format($variant['price'],2) }}{{ $variant['per_person']?' pp':'' }}</b></button>@endforeach</div></div><div class="wow-v5__group" id="v5Group"><div style="display:flex;justify-content:space-between;margin-bottom:10px"><span><b>Group size</b><small style="display:block;color:#68736f;font-size:9px">Minimum 3{{ $v5GroupMax ? ' · maximum '.$v5GroupMax : '' }}</small></span><b id="v5GroupUnit"></b></div><div class="wow-v5__stepper"><button type="button" id="v5GroupMinus">−</button><input id="v5GroupInput" type="number" min="3" @if($v5GroupMax) max="{{ $v5GroupMax }}" @endif value="3" aria-label="Group size"><button type="button" id="v5GroupPlus">+</button></div></div><div class="wow-v5__summary"><dl><dt>Location</dt><dd id="v5SummaryLocation">{{ $v5Locations[0]['label'] }}</dd><dt>Session</dt><dd id="v5SummaryVariant">{{ $v5Variants[0]['label'] }}</dd><dt>Date &amp; time</dt><dd id="v5SummaryDate">Choose after clicking Book now</dd><dt>Confirmation</dt><dd>Email confirmation</dd></dl></div><div class="wow-v5__qty"><span><b style="font-size:12px">Quantity</b><small style="display:block;color:#68736f;font-size:9px">Instant email confirmation</small></span><div class="wow-v5__qty-controls"><button type="button" id="v5QtyMinus">−</button><span id="v5Qty">1</span><button type="button" id="v5QtyPlus">+</button></div></div><button class="wow-v5__book" id="v5Book" type="button">Book now</button><p class="wow-v5__note" id="v5Note">Checking live availability…</p></div></section></aside></div>
    <div class="wow-v5__mobilebar"><span><b id="v5MobilePrice">£{{ number_format($v5Price,2) }}</b><small style="display:block;color:#68736f;font-size:10px">Begin your journey</small></span><button type="button" id="v5MobileBook">View &amp; Book</button></div>
</main>
@push('scripts')
<script data-cfasync="false" src="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js"></script>
<script>
(()=>{const page=document.querySelector('#wowOfferingV5');if(!page)return;const cfg=JSON.parse(page.dataset.config),$=(s,r=page)=>r.querySelector(s),$$=(s,r=page)=>[...r.querySelectorAll(s)],money=n=>new Intl.NumberFormat('en-GB',{style:'currency',currency:cfg.currency||'GBP'}).format(Number(n||0)),holdKey=`wow-offering-hold:${cfg.source}:${cfg.id}`;let location=cfg.locations[0],variant=cfg.variants.find(x=>x.id===cfg.selectedVariantId)||cfg.variants[0],group=Math.max(3,Number((cfg.variants.find(x=>x.id===cfg.selectedVariantId)||cfg.variants[0]).group_min||3)),qty=1,availability={},date=null,time=null,slotEnd=null,reservation=null,expires=null,timer=null;try{const saved=JSON.parse(sessionStorage.getItem(holdKey)||'null');if(saved&&new Date(saved.expires)>new Date()){reservation=saved.reservation;expires=saved.expires;date=saved.date;time=saved.time;slotEnd=saved.slot_end||null;}}catch(e){}
const setMenu=(trigger,menu)=>{trigger.onclick=e=>{e.stopPropagation();const open=trigger.getAttribute('aria-expanded')==='true';$$('.wow-v5__menu').forEach(x=>x.classList.remove('open'));$$('.wow-v5__trigger').forEach(x=>x.setAttribute('aria-expanded','false'));menu.classList.toggle('open',!open);trigger.setAttribute('aria-expanded',String(!open))}};document.addEventListener('click',()=>{$$('.wow-v5__menu').forEach(x=>x.classList.remove('open'));$$('.wow-v5__trigger').forEach(x=>x.setAttribute('aria-expanded','false'))});setMenu($('#v5LocationTrigger'),$('#v5LocationMenu'));setMenu($('#v5VariantTrigger'),$('#v5VariantMenu'));
const reviewLink=$('a[href="#reviews"]');if(reviewLink&&cfg.reviewUrl)reviewLink.href=cfg.reviewUrl;
let map=null,mapMarker=null;function syncMap(){const mapBox=$('#v5Map');if(!mapBox)return;mapBox.classList.toggle('is-physical',!location.online);if(location.online){if(map)mapBox.querySelector('.wow-v5__map-canvas')?.remove();return}if(!cfg.mapboxToken||!window.mapboxgl||!Number.isFinite(Number(location.lng))||!Number.isFinite(Number(location.lat)))return;let canvas=mapBox.querySelector('.wow-v5__map-canvas');if(!canvas){canvas=document.createElement('div');canvas.className='wow-v5__map-canvas';mapBox.prepend(canvas);window.mapboxgl.accessToken=cfg.mapboxToken;map=new window.mapboxgl.Map({container:canvas,style:'mapbox://styles/mapbox/standard',center:[Number(location.lng),Number(location.lat)],zoom:13});map.addControl(new window.mapboxgl.NavigationControl(),'top-right')}if(map){map.flyTo({center:[Number(location.lng),Number(location.lat)],zoom:13});if(mapMarker)mapMarker.remove();mapMarker=new window.mapboxgl.Marker().setLngLat([Number(location.lng),Number(location.lat)]).addTo(map)}}
page.addEventListener('click',event=>{if(event.target.closest('[data-location]'))setTimeout(syncMap,0)});setTimeout(syncMap,0);
function live(){return Object.values(availability.slotsByDay||{}).some(x=>(x.slots||[]).length)}
function price(){return variant.per_person&&variant.kind==='group'?variant.price*group:variant.price}
function variantsForLocation(){const channel=location.online?'online':'in_person',variants=cfg.variants.filter(item=>!item.channels?.length||item.channels.includes(channel));return variants.length?variants:cfg.variants}
function peopleSummary(variants){const kinds=[...new Set(variants.map(item=>item.kind).filter(Boolean))],solo=kinds.includes('single'),couple=kinds.includes('couple'),groups=variants.filter(item=>item.kind==='group');if(!solo&&!couple&&!groups.length)return null;if(!groups.length)return solo&&couple?'1–2 people':couple?'Couple':'1 person';const min=Math.min(...groups.map(item=>Math.max(1,Number(item.group_min||3)))),maxValues=groups.map(item=>Number(item.group_max)).filter(Number.isFinite),max=maxValues.length?Math.max(...maxValues):null,range=max?`${min}–${max}`:`${min}+`;if(solo&&couple)return max?`1–${max} people`:'1+ people';if(couple)return max?`2–${max} people`:'2+ people';if(solo)return `1 or ${range}`;return `${range} people`}
function updateQuickInfo(){const root=$('[data-quick-info]');if(!root)return;const variants=variantsForLocation(),prices=[...new Set(variants.map(item=>Number(item.price||0)))].sort((a,b)=>a-b),priceFact=root.querySelector('[data-quick-fact="price"]'),peopleFact=root.querySelector('[data-quick-fact="people"]');if(priceFact&&prices.length){priceFact.querySelector('[data-quick-label]').textContent=prices.length===1?'Price':'From';priceFact.querySelector('[data-quick-value]').textContent=money(prices[0])}if(peopleFact){const people=peopleSummary(variants);peopleFact.hidden=!people;if(people)peopleFact.querySelector('[data-quick-value]').textContent=people}const availabilityFact=root.querySelector('[data-quick-fact="availability"]');if(availabilityFact)availabilityFact.hidden=!live();const items=[...root.querySelectorAll('[data-quick-fact]')];let visible=0;items.forEach(item=>{if(item.dataset.quickFact==='availability'&&!live()){item.hidden=true;delete item.dataset.quickPosition;return}if(item.dataset.quickFact==='people'&&item.hidden){delete item.dataset.quickPosition;return}item.hidden=visible>=5;if(item.hidden){delete item.dataset.quickPosition;return}visible++;item.dataset.quickPosition=String(visible)});root.dataset.factCount=String(visible)}
function sync(){const p=price();$('#v5Price').textContent=money(p);$('#v5MobilePrice').textContent=money(p);$('#v5LocationTitle').textContent=location.label;$('#v5LocationSub').textContent=location.address;$('#v5SummaryLocation').textContent=location.label;$('#v5VariantTitle').textContent=variant.label;$('#v5VariantMeta').textContent=variant.meta||'';$('#v5SummaryVariant').textContent=variant.kind==='group'?`${variant.label} · ${group} people`:variant.label;$('#v5Qty').textContent=qty;$('#v5Group').classList.toggle('show',variant.kind==='group');$('#v5GroupInput').value=group;$('#v5GroupUnit').textContent=variant.per_person?`${money(variant.price)} pp`:money(variant.price);$('#v5Status').textContent=live()?'Live availability':'Flexible booking';$('#v5Note').textContent=live()?'Book now opens the live calendar.':'The practitioner will arrange the date with you after booking.';$('#v5SummaryDate').textContent=date&&time?`${date} · ${time}`:'Choose after clicking Book now';updateQuickInfo()}
async function load(){if(!reservation){date=null;time=null;}availability={};const p=new URLSearchParams();if(variant.price_option_id)p.set('price_option_id',variant.price_option_id);if(variant.label)p.set('variant_label',variant.label);if(reservation)p.set('reservation_id',reservation);try{const r=await fetch(`${cfg.bookingEndpoint}?${p}`,{headers:{Accept:'application/json'},credentials:'same-origin'});const data=await r.json();availability=data.bookingPayload||{};const first=Object.keys(availability.slotsByDay||{}).filter(k=>(availability.slotsByDay[k].slots||[]).length).sort()[0];if(first&&!date){date=first}}catch(e){}sync()}
function renderCalendar(){const old=$('.wow-v5__calendar');if(old)old.remove();if(!live())return;const box=document.createElement('section');box.className='wow-v5__calendar';box.style.cssText='display:block;position:fixed;inset:0;z-index:50;padding:20px;background:#0a17148c;overflow:auto';box.innerHTML=`<div style="width:min(900px,100%);margin:auto;background:#fff;border-radius:4px"><div style="display:flex;justify-content:space-between;padding:18px;border-bottom:1px solid #dce4e0"><div><small>Pick Date & Time</small><h3 style="margin:4px 0">${cfg.title||'Choose your session'}</h3></div><button type="button" data-close>×</button></div><div style="display:grid;grid-template-columns:1fr 1fr"><div style="padding:20px"><b>Available dates</b><div data-days style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px"></div></div><div style="padding:20px"><b>Available times</b><div data-times style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px"></div><div data-hold style="display:none;margin-top:16px;padding:10px;border:1px solid #cfe1da;background:#f4faf7">⌛ Your selected time is being held for <b></b></div></div></div><div style="display:flex;justify-content:flex-end;gap:8px;padding:15px;border-top:1px solid #dce4e0"><button type="button" data-close>Cancel</button><button type="button" data-confirm style="background:#4f9482;color:#fff;border:0;padding:0 15px">Continue</button></div></div>`;document.body.append(box);const days=$('[data-days]',box),times=$('[data-times]',box);Object.keys(availability.slotsByDay||{}).sort().forEach(key=>{const b=document.createElement('button');b.textContent=key;b.disabled=!(availability.slotsByDay[key].slots||[]).length;b.style.cssText='height:40px;border:1px solid #dce4e0;background:#fff';if(key===date)b.style.cssText+=';border-color:#4f9482;background:#eef6f3';b.onclick=()=>{date=key;time=null;renderCalendar();sync()};days.append(b)});(availability.slotsByDay?.[date]?.slots||[]).forEach(slot=>{const b=document.createElement('button');b.textContent=slot.start;b.style.cssText='height:40px;border:1px solid #dce4e0;background:#fff';if(time===slot.start)b.style.cssText+=';border-color:#4f9482;background:#eef6f3';b.onclick=()=>{time=slot.start;sync();renderCalendar()};times.append(b)});$$('[data-close]',box).forEach(b=>b.onclick=()=>box.remove());$('[data-confirm]',box).onclick=async()=>{if(!date||!time)return;await hold();if(reservation){box.remove();await add()}};if(expires){const h=$('[data-hold]',box);h.style.display='block';$('b',h).textContent=remaining()}}
renderCalendar=()=>{const old=document.querySelector('.wow-v5__calendar');if(old)old.remove();if(!live())return;const box=document.createElement('section');box.className='wow-v5__calendar';box.setAttribute('role','dialog');box.setAttribute('aria-modal','true');const dates=Object.keys(availability.slotsByDay||{}).sort();const label=value=>new Intl.DateTimeFormat('en-GB',{weekday:'short',day:'numeric',month:'short'}).format(new Date(`${value}T12:00:00`));box.innerHTML=`<div class="wow-v5__calendar-card"><div class="wow-v5__calendar-head"><div><small>Pick Date &amp; Time</small><h3>${cfg.title||'Choose your session'}</h3></div><button class="wow-v5__calendar-close" type="button" data-close aria-label="Close calendar">×</button></div><div class="wow-v5__calendar-grid"><div class="wow-v5__calendar-pane"><h4>Available dates</h4><div class="wow-v5__calendar-days" data-days></div></div><div class="wow-v5__calendar-pane"><h4>◷ Available times</h4><div class="wow-v5__calendar-hold" data-hold><span class="wow-v5__calendar-hourglass">⌛</span><span><strong>Your selected time is being held for <b></b></strong><small>Complete booking before the timer ends.</small></span></div><div class="wow-v5__calendar-times" data-times></div></div></div><div class="wow-v5__calendar-foot"><span>Selected: <b data-selected-date>${date?label(date):'choose a date'}</b> · <b data-selected-time>${time||'choose a time'}</b></span><div><button class="wow-v5__calendar-action" type="button" data-close>Cancel</button><button class="wow-v5__calendar-action wow-v5__calendar-confirm" type="button" data-confirm>Continue</button></div></div></div>`;document.body.append(box);const days=box.querySelector('[data-days]'),times=box.querySelector('[data-times]');dates.forEach(key=>{const button=document.createElement('button');const available=(availability.slotsByDay[key].slots||[]).length>0;button.className=`wow-v5__calendar-slot${key===date?' is-selected':''}`;button.textContent=label(key);button.disabled=!available;button.onclick=()=>{date=key;time=null;renderCalendar();sync()};days.append(button)});(availability.slotsByDay?.[date]?.slots||[]).forEach(slot=>{const button=document.createElement('button');button.className=`wow-v5__calendar-slot${slot.start===time?' is-selected':''}`;button.textContent=slot.start;button.onclick=()=>{time=slot.start;renderCalendar();sync()};times.append(button)});box.querySelectorAll('[data-close]').forEach(button=>button.onclick=()=>box.remove());box.querySelector('[data-confirm]').onclick=async()=>{if(!date||!time)return;await hold();if(reservation){box.remove();await add()}};if(expires){const hold=box.querySelector('[data-hold]');hold.classList.add('show');hold.querySelector('b').textContent=remaining()}};
let calendarView=null;
const dateKey=(value)=>`${value.getFullYear()}-${String(value.getMonth()+1).padStart(2,'0')}-${String(value.getDate()).padStart(2,'0')}`;
const calendarMonthLabel=(value)=>new Intl.DateTimeFormat('en-GB',{month:'long',year:'numeric'}).format(value);
const calendarDateLabel=(value)=>new Intl.DateTimeFormat('en-GB',{weekday:'short',day:'numeric',month:'short'}).format(new Date(`${value}T12:00:00`));
const holdLabel=()=>{const seconds=Math.max(0,Math.ceil((new Date(expires)-Date.now())/1000));return `${Math.floor(seconds/60)}:${String(seconds%60).padStart(2,'0')}`;};
renderCalendar=()=>{
    if(!live())return;
    const firstAvailable=Object.keys(availability.slotsByDay||{}).filter(key=>(availability.slotsByDay[key]?.slots||[]).length).sort()[0];
    if(!date&&firstAvailable) date=firstAvailable;
    if(!calendarView) calendarView=new Date(`${(date||firstAvailable)}T12:00:00`);
    const existing=document.querySelector('.wow-v5__calendar');if(existing)existing.remove();
    const box=document.createElement('section');box.className='wow-v5__calendar';box.setAttribute('role','dialog');box.setAttribute('aria-modal','true');box.setAttribute('aria-label','Choose a date and time');
    box.innerHTML=`<div class="wow-v5__calendar-card"><div class="wow-v5__calendar-head"><div><small>Pick Date &amp; Time</small><h3>Choose your session</h3></div><button class="wow-v5__calendar-close" type="button" data-close aria-label="Close calendar">×</button></div><div class="wow-v5__calendar-grid"><div class="wow-v5__calendar-pane"><div class="wow-v5__calendar-month"><button type="button" data-month="previous" aria-label="Previous month">‹</button><strong data-month-label></strong><button type="button" data-month="next" aria-label="Next month">›</button></div><div class="wow-v5__calendar-weekdays"><span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span><span>SAT</span><span>SUN</span></div><div class="wow-v5__calendar-date-grid" data-days></div></div><div class="wow-v5__calendar-pane"><h4>◷ Available times</h4><div class="wow-v5__calendar-hold" data-hold><span class="wow-v5__calendar-hourglass">⌛</span><span><strong>Your selected time is being held for <b></b></strong><small>Complete booking before the timer ends.</small></span></div><div class="wow-v5__calendar-times" data-times></div></div></div><div class="wow-v5__calendar-foot"><span>Selected: <b data-selected-date></b> · <b data-selected-time></b></span><div><button class="wow-v5__calendar-action" type="button" data-close>Cancel</button><button class="wow-v5__calendar-action wow-v5__calendar-confirm" type="button" data-confirm>Confirm Date &amp; Time</button></div></div></div>`;
    document.body.append(box);box.querySelector('.wow-v5__calendar-head h3').textContent=cfg.title||'Choose your session';box.querySelector('.wow-v5__calendar-foot [data-close]').className='btn-wow btn-wow--outline btn-wow--md wow-v5__calendar-action';box.querySelector('.wow-v5__calendar-foot [data-confirm]').className='btn-wow btn-wow--primary btn-wow--md wow-v5__calendar-action wow-v5__calendar-confirm';
    const render=()=>{
        box.querySelector('[data-month-label]').textContent=calendarMonthLabel(calendarView);
        box.querySelector('[data-selected-date]').textContent=date?calendarDateLabel(date):'choose a date';
        box.querySelector('[data-selected-time]').textContent=time||'choose a time';
        const grid=box.querySelector('[data-days]');grid.replaceChildren();
        const year=calendarView.getFullYear(),month=calendarView.getMonth(),first=new Date(year,month,1),offset=(first.getDay()+6)%7,total=new Date(year,month+1,0).getDate();
        for(let index=0;index<offset;index++){const blank=document.createElement('span');blank.className='wow-v5__calendar-blank';grid.append(blank)}
        for(let day=1;day<=total;day++){const current=new Date(year,month,day),key=dateKey(current),slots=availability.slotsByDay?.[key]?.slots||[],button=document.createElement('button');button.type='button';button.className=`wow-v5__calendar-date${key===date?' is-selected':''}`;button.textContent=day;button.disabled=slots.length===0;button.setAttribute('aria-label',calendarDateLabel(key)+(slots.length?'':' unavailable'));if(slots.length)button.onclick=()=>{date=key;time=null;slotEnd=null;sync();render()};grid.append(button)}
        const times=box.querySelector('[data-times]');times.replaceChildren();const slots=availability.slotsByDay?.[date]?.slots||[];
        if(!slots.length){const empty=document.createElement('p');empty.className='wow-v5__calendar-empty';empty.textContent='No times are available for this date.';times.append(empty)}
        slots.forEach(slot=>{const button=document.createElement('button');button.type='button';button.className=`wow-v5__calendar-slot${slot.start===time?' is-selected':''}`;button.textContent=slot.start;button.onclick=async()=>{if(time===slot.start&&reservation&&remaining()>0)return;time=slot.start;slotEnd=slot.end||null;sync();await hold();render()};times.append(button)});
        const hold=box.querySelector('[data-hold]');hold.classList.toggle('show',Boolean(expires));if(expires)hold.querySelector('b').textContent=holdLabel();
    };
    box.querySelectorAll('[data-close]').forEach(button=>button.onclick=()=>{calendarView=null;box.remove()});
    box.querySelectorAll('[data-month]').forEach(button=>button.onclick=()=>{calendarView=new Date(calendarView.getFullYear(),calendarView.getMonth()+(button.dataset.month==='next'?1:-1),1);render()});
    box.querySelector('[data-confirm]').onclick=async()=>{if(!date||!time)return;if(!reservation||remaining()<=0)await hold();if(reservation){calendarView=null;box.remove();await add()}};
    box.addEventListener('click',event=>{if(event.target===box){calendarView=null;box.remove()}});render();
};
function renderMobileBooking(){
    const existing=document.querySelector('.wow-v5__booking-sheet');if(existing)existing.remove();
    const sheet=document.createElement('section');sheet.className='wow-v5__booking-sheet';sheet.setAttribute('role','dialog');sheet.setAttribute('aria-modal','true');sheet.setAttribute('aria-label','Book this offering');
    sheet.innerHTML=`<div class="wow-v5__booking-sheet-card"><div class="wow-v5__booking-sheet-head"><div><small>Book your session</small><h3>${cfg.title||'Choose your session'}</h3></div><button class="wow-v5__calendar-close" type="button" data-close aria-label="Close booking">×</button></div><div class="wow-v5__booking-sheet-body"><div data-location-wrap><label>Location</label><div class="wow-v5__booking-sheet-options" data-locations></div></div><label>Session</label><div class="wow-v5__booking-sheet-options" data-variants></div><div data-group-wrap hidden><label>Group size</label><div class="wow-v5__stepper"><button type="button" data-group-minus>−</button><input type="number" min="3" data-group-input aria-label="Group size"><button type="button" data-group-plus>+</button></div></div><div class="wow-v5__qty"><span><b>Quantity</b></span><div class="wow-v5__qty-controls"><button type="button" data-qty-minus>−</button><span data-qty></span><button type="button" data-qty-plus>+</button></div></div><div class="wow-v5__summary" style="margin-top:12px"><dl><dt>Location</dt><dd data-summary-location></dd><dt>Session</dt><dd data-summary-variant></dd><dt>Date &amp; time</dt><dd data-summary-date></dd></dl></div><button class="btn-wow btn-wow--primary btn-wow--md btn-wow--block" type="button" data-book><span class="btn-label"></span></button></div></div>`;
    document.body.append(sheet);const render=()=>{const locations=sheet.querySelector('[data-locations]');locations.replaceChildren();sheet.querySelector('[data-location-wrap]').hidden=cfg.locations.length<2;cfg.locations.forEach(item=>{const button=document.createElement('button');button.type='button';button.textContent=item.label;button.classList.toggle('is-selected',item.id===location.id);button.onclick=()=>{location=item;date=time=null;const availableVariants=variantsForLocation();if(!availableVariants.some(candidate=>candidate.id===variant.id))variant=availableVariants[0];load().then(()=>{sync();render()})};locations.append(button)});const variants=sheet.querySelector('[data-variants]');variants.replaceChildren();variantsForLocation().forEach(item=>{const button=document.createElement('button');button.type='button';button.classList.toggle('is-selected',item.id===variant.id);button.textContent=`${item.label} · ${money(item.per_person&&item.kind==='group'?item.price*group:item.price)}`;button.onclick=()=>{variant=item;group=Math.max(3,Number(item.group_min||3));date=time=null;load().then(()=>{sync();render()})};variants.append(button)});const groupWrap=sheet.querySelector('[data-group-wrap]');groupWrap.hidden=variant.kind!=='group';const groupInput=sheet.querySelector('[data-group-input]');groupInput.value=group;if(cfg.groupMax)groupInput.max=cfg.groupMax;sheet.querySelector('[data-group-minus]').onclick=()=>{group=Math.max(3,group-1);load().then(()=>{sync();render()})};sheet.querySelector('[data-group-plus]').onclick=()=>{group=Math.min(cfg.groupMax||Infinity,group+1);load().then(()=>{sync();render()})};groupInput.onchange=()=>{group=Math.max(3,Math.min(cfg.groupMax||Infinity,Number(groupInput.value)||3));load().then(()=>{sync();render()})};sheet.querySelector('[data-qty]').textContent=qty;sheet.querySelector('[data-qty-minus]').onclick=()=>{qty=Math.max(1,qty-1);sync();render()};sheet.querySelector('[data-qty-plus]').onclick=()=>{qty++;sync();render()};sheet.querySelector('[data-summary-location]').textContent=location.label;sheet.querySelector('[data-summary-variant]').textContent=variant.kind==='group'?`${variant.label} · ${group} people`:variant.label;sheet.querySelector('[data-summary-date]').textContent=date&&time?`${date} · ${time}`:'Choose after clicking Book now';sheet.querySelector('[data-book] .btn-label').textContent=reservation&&date&&time?'Book now':live()?'Choose Date & Time':'Book now'};sheet.querySelector('[data-close]').onclick=()=>sheet.remove();sheet.addEventListener('click',event=>{if(event.target===sheet)sheet.remove()});sheet.querySelector('[data-book]').onclick=()=>{sheet.remove();if(reservation&&date&&time)return add();return live()?renderCalendar():add()};render();
}
function remaining(){return Math.max(0,Math.ceil((new Date(expires)-Date.now())/1000));}function start(){clearInterval(timer);timer=setInterval(()=>{const s=remaining();if(!s){clearInterval(timer);sessionStorage.removeItem(holdKey);reservation=expires=null;date=time=slotEnd=null;load();alert('Your selected slot is no longer held. Please choose another time.')}else{const el=document.querySelector('[data-hold] b');if(el)el.textContent=`${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`}},1000)}async function hold(){if(!date||!time)return;const [h,m]=time.split(':').map(Number),end=`${String(Math.floor(((h*60+m)+cfg.duration)/60)%24).padStart(2,'0')}:${String(((h*60+m)+cfg.duration)%60).padStart(2,'0')}`,token=document.querySelector('meta[name=csrf-token]')?.content;const r=await fetch(cfg.holdEndpoint,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json',...(token?{'X-CSRF-TOKEN':token}:{})},credentials:'same-origin',body:JSON.stringify({slot_date:date,slot_start:time,slot_end:end,price_option_id:variant.price_option_id,reservation_id:reservation})});if(!r.ok){reservation=expires=null;date=time=slotEnd=null;sessionStorage.removeItem(holdKey);alert('That time is no longer available.');await load();return}const x=await r.json();reservation=x.reservation_id||x.id;expires=x.hold_expires_at||x.expires_at;if(!reservation||!expires){reservation=expires=null;date=time=slotEnd=null;sessionStorage.removeItem(holdKey);alert('Unable to hold that time.');await load();return}sessionStorage.setItem(holdKey,JSON.stringify({reservation,expires,date,time,slot_end:slotEnd}));start();sync()}async function add(){if(reservation&&remaining()<=0){reservation=expires=null;date=time=slotEnd=null;sessionStorage.removeItem(holdKey);await load();alert('Your selected time is no longer held. Please choose another time.');return}const token=document.querySelector('meta[name=csrf-token]')?.content;const response=await fetch(cfg.cartEndpoint,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json',...(token?{'X-CSRF-TOKEN':token}:{})},credentials:'same-origin',body:JSON.stringify({id:cfg.id,qty,variant_id:variant.id,variant_label:variant.label,group_count:variant.kind==='group'?group:null,location:location.id,source_version:cfg.source,reservation_id:reservation,hold_expires_at:expires,booking:{date,time,slot_end:slotEnd,reservation_id:reservation,hold_expires_at:expires,location_id:location.id}})});if(!response.ok){alert('We could not add this offering to your basket. Please try again.');return}window.location.assign(cfg.cartUrl)}
async function hold(){
    if(!date||!time)return;
    const selectedSlot=(availability.slotsByDay?.[date]?.slots||[]).find(slot=>slot.start===time);
    const end=selectedSlot?.end||slotEnd;
    if(!end){slotEnd=null;alert('That time is no longer available.');await load();return}
    slotEnd=end;
    const token=document.querySelector('meta[name=csrf-token]')?.content;
    const response=await fetch(cfg.holdEndpoint,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json',...(token?{'X-CSRF-TOKEN':token}:{})},credentials:'same-origin',body:JSON.stringify({slot_date:date,slot_start:time,slot_end:end,price_option_id:variant.price_option_id,reservation_id:reservation})});
    if(!response.ok){reservation=expires=null;date=time=slotEnd=null;sessionStorage.removeItem(holdKey);alert('That time is no longer available.');await load();return}
    const payload=await response.json();reservation=payload.reservation_id||payload.id;expires=payload.hold_expires_at||payload.expires_at;
    if(!reservation||!expires){reservation=expires=null;date=time=slotEnd=null;sessionStorage.removeItem(holdKey);alert('Unable to hold that time.');await load();return}
    sessionStorage.setItem(holdKey,JSON.stringify({reservation,expires,date,time,slot_end:slotEnd}));start();sync();
}
function syncVariantOptions(){const valid=new Set(variantsForLocation().map(item=>item.id));$$('[data-variant]').forEach(button=>{const candidate=JSON.parse(button.dataset.variant);button.hidden=!valid.has(candidate.id);button.classList.toggle('selected',candidate.id===variant.id)})}
function selectLocation(next){location=next;const valid=variantsForLocation();if(!valid.some(item=>item.id===variant.id)){variant=valid[0];group=Math.max(3,Number(variant.group_min||3))}$$('[data-location-option]').forEach(button=>button.classList.toggle('selected',button.dataset.locationOption===location.id));$$('[data-location]').forEach(button=>button.classList.toggle('active',button.dataset.location===location.id));$('#v5MapTitle').textContent=location.online?'Join online':location.label;$('#v5MapCopy').textContent=location.online?'Your joining link is sent after booking.':location.address;date=time=null;syncVariantOptions();syncMap();load();sync()}
$$('[data-location-option]').forEach(button=>button.onclick=()=>selectLocation(cfg.locations.find(item=>item.id===button.dataset.locationOption)||location));$$('[data-location]').forEach(button=>button.onclick=()=>selectLocation(cfg.locations.find(item=>item.id===button.dataset.location)||location));$$('[data-variant]').forEach(button=>button.onclick=()=>{if(button.hidden)return;variant=JSON.parse(button.dataset.variant);group=Math.max(3,Number(variant.group_min||3));$$('[data-variant]').forEach(item=>item.classList.toggle('selected',item===button));date=time=null;load();sync()});$('#v5GroupMinus').onclick=()=>{group=Math.max(3,group-1);sync();load()};$('#v5GroupPlus').onclick=()=>{group=Math.min(cfg.groupMax||Infinity,group+1);sync();load()};$('#v5GroupInput').onchange=e=>{group=Math.max(3,Math.min(cfg.groupMax||Infinity,Number(e.target.value)||3));sync();load()};$('#v5QtyMinus').onclick=()=>{qty=Math.max(1,qty-1);sync()};$('#v5QtyPlus').onclick=()=>{qty++;sync()};$('#v5Book').onclick=()=>reservation&&date&&time?add():(live()?renderCalendar():add());$('#v5MobileBook').onclick=()=>renderMobileBooking();syncVariantOptions();$$('.wow-v5__faq-item button').forEach(b=>b.onclick=()=>{const i=b.parentElement;i.classList.toggle('open');$('b',b).textContent=i.classList.contains('open')?'−':'+'});
const gallery=$('#v5Gallery');
if(gallery){
 const galleryItems=$$('.wow-v5__gallery-item',gallery),galleryCount=$('.wow-v5__gallery-count',gallery),next=$('.next',gallery),prev=$('.prev',gallery);let galleryIndex=0,galleryBusy=false;
 const setGallerySlots=()=>galleryItems.forEach((item,index)=>{const offset=(index-galleryIndex+galleryItems.length)%galleryItems.length;for(let slot=0;slot<4;slot++)item.classList.remove(`slot-${slot}`);if(offset<4)item.classList.add(`slot-${offset}`)});
 const rotateGallery=direction=>{if(galleryBusy||galleryItems.length<3)return;galleryBusy=true;const before=new Map(galleryItems.map(item=>[item,item.getBoundingClientRect()]));galleryIndex=(galleryIndex+direction+galleryItems.length)%galleryItems.length;setGallerySlots();galleryItems.forEach(item=>{const startBox=before.get(item),endBox=item.getBoundingClientRect();item.classList.add('is-moving');if(startBox.width&&startBox.height&&endBox.width&&endBox.height){item.style.transformOrigin='top left';item.style.transition='none';item.style.transform=`translate(${startBox.left-endBox.left}px,${startBox.top-endBox.top}px) scale(${startBox.width/endBox.width},${startBox.height/endBox.height})`}else item.classList.add('is-entering')});gallery.offsetHeight;requestAnimationFrame(()=>galleryItems.forEach(item=>{item.style.transition='transform .48s cubic-bezier(.22,.8,.24,1),opacity .28s ease';item.style.transform='translate(0,0) scale(1)';item.classList.remove('is-entering')}));if(galleryCount)galleryCount.textContent=`${galleryIndex+1} / ${galleryItems.length}`;setTimeout(()=>{galleryItems.forEach(item=>{item.classList.remove('is-moving','is-entering');item.style.transition='';item.style.transform='';item.style.transformOrigin=''});galleryBusy=false},520)};
 next?.addEventListener('click',()=>rotateGallery(1));prev?.addEventListener('click',()=>rotateGallery(-1));
}
const mobileGallery=$('#v5MobileGallery'),track=$('.wow-v5__mobile-track',mobileGallery);
if(mobileGallery&&track&&track.children.length>1){const mobileCount=track.children.length,counter=$('.wow-v5__mobile-gallery-count',mobileGallery);let mobileIndex=0,startX=0;const mobile=direction=>{mobileIndex=(mobileIndex+direction+mobileCount)%mobileCount;track.style.transform=`translateX(-${mobileIndex*100}%)`;if(counter)counter.textContent=`${mobileIndex+1} / ${mobileCount}`};$('.prev',mobileGallery)?.addEventListener('click',()=>mobile(-1));$('.next',mobileGallery)?.addEventListener('click',()=>mobile(1));track.addEventListener('touchstart',event=>startX=event.touches[0].clientX,{passive:true});track.addEventListener('touchend',event=>{const distance=event.changedTouches[0].clientX-startX;if(Math.abs(distance)>45)mobile(distance<0?1:-1)},{passive:true})}if(reservation&&expires)start();load();sync()})();
</script>
@endpush
