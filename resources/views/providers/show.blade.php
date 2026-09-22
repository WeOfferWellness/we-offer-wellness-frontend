@extends('layouts.app')

@php
    use App\Support\ContentFormatter;
    use App\Support\ProductRanking;
    use App\Models\OfferingV3;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $profileType = $profileType ?? 'practitioner';
    $user = $user ?? null;

    abort_if(! $user, 404);

    $vendor = $user->vendorDetail;
    $settingsData = is_array($user->settings?->settings_data ?? null) ? ($user->settings?->settings_data ?? []) : [];
    $profileData = $user->profile;

    $displayName = trim((string) ($user->public_display_name ?: ''));
    if ($displayName === '') {
        $displayName = $profileType === 'team' ? 'Team member' : 'Practitioner';
    }

    $roleLabel = $user->isStarterPlan()
        ? ''
        : trim((string) ($settingsData['practice_label'] ?? ''));
    if ($roleLabel === '') {
        $roleLabel = $profileType === 'team' ? 'We Offer Wellness team' : ($user->public_role_label ?: 'Wellness practitioner');
    }

    $experienceLabel = trim((string) ($settingsData['experience_years'] ?? ''));
    $languageLabel = $user->language
        ? Str::of($user->language)->replace(['_', '-'], ' ')->title()->toString()
        : 'English';

    $bioHtml = ContentFormatter::format((string) ($user->bio?->bio ?? ''));
    $bioPlain = trim(strip_tags($bioHtml));
    if ($bioPlain === '') {
        $bioHtml = '<p>' . e($profileType === 'team'
            ? 'This team profile is being prepared. A fuller bio will be added soon.'
            : 'This practitioner profile is being prepared. A fuller bio will be added soon.') . '</p>';
    }

    $profilePicture = $user->profile_picture_url ?: null;
    $coverImage = $user->cover_image_url ?: null;
    $defaultImage = asset('images/default-social-preview.jpg');

    $splitList = static function ($value): array {
        if (is_array($value)) {
            $flattened = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    $text = trim((string) ($item['title'] ?? $item['name'] ?? $item['label'] ?? ''));
                    if ($text !== '') {
                        $flattened[] = $text;
                    }
                    continue;
                }

                $text = trim((string) $item);
                if ($text !== '') {
                    $flattened[] = $text;
                }
            }

            return array_values(array_unique($flattened));
        }

        $text = trim((string) $value);
        if ($text === '') {
            return [];
        }

        if ((str_starts_with($text, '[') && str_ends_with($text, ']')) || (str_starts_with($text, '{') && str_ends_with($text, '}'))) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                    $flattened = [];
                    foreach ($decoded as $decodedItem) {
                        if (is_array($decodedItem)) {
                            $subText = trim((string) ($decodedItem['title'] ?? $decodedItem['name'] ?? $decodedItem['label'] ?? ''));
                            if ($subText !== '') {
                                $flattened[] = $subText;
                            }
                            continue;
                        }

                    $decodedText = trim((string) $decodedItem);
                    if ($decodedText !== '') {
                        $flattened[] = $decodedText;
                    }
                }

                return array_values(array_unique($flattened));
            }
        }

        $parts = preg_split('/[\n,;|]+/', str_replace('•', ',', $text)) ?: [];

        return array_values(array_unique(array_filter(array_map('trim', $parts))));
    };

    $qualifications = $splitList(
        data_get($vendor, 'qualifications')
        ?: data_get($vendor, 'credentials')
        ?: data_get($profileData, 'skills')
        ?: data_get($settingsData, 'qualifications')
        ?: ''
    );

    $accreditations = $splitList(
        data_get($vendor, 'accreditations')
        ?: data_get($settingsData, 'accreditations')
        ?: ''
    );

    $insurance = $vendor?->currentInsurance;
    $insuranceDocuments = collect($insurance?->documents ?? []);
    $insuranceProvider = trim((string) ($insurance?->insurance_provider ?? ''));
    $insuranceType = trim((string) ($insurance?->insurance_type ?? ''));
    $coverageAmount = trim((string) ($insurance?->coverage_amount ?? ''));
    $insuranceStatus = trim((string) ($insurance?->status ?? ''));
    $insuranceValidFrom = filled($insurance?->valid_from ?? null)
        ? Carbon::parse($insurance->valid_from)->format('j M Y')
        : '';
    $insuranceValidUntil = filled($insurance?->valid_until ?? null)
        ? Carbon::parse($insurance->valid_until)->format('j M Y')
        : '';

    $now = now();

    $legacyProducts = collect($vendor?->products ?? [])->filter(static function ($product): bool {
        $status = strtolower((string) data_get($product, 'status.status', ''));

        return $status === '' || in_array($status, ['live', 'approved'], true) || blank($product->product_status_id);
    })->values();

    $vendorOfferings = collect($vendor?->offerings ?? [])->filter(static function ($offering) use ($now): bool {
        if (! $offering instanceof OfferingV3) {
            return false;
        }

        $status = strtolower((string) ($offering->status ?? ''));
        if (! in_array($status, ['live', 'approved'], true)) {
            return false;
        }

        $publishedAt = $offering->published_at ?? null;
        if (! filled($publishedAt)) {
            return false;
        }

        try {
            return Carbon::parse($publishedAt)->lte($now);
        } catch (\Throwable $e) {
            return false;
        }
    })->values();

    $visibleProducts = ProductRanking::sortCollection($legacyProducts->concat($vendorOfferings))->values();

    $productCards = $visibleProducts->take(4)->map(static function ($product) use ($defaultImage): array {
        $locations = method_exists($product, 'getLocations') ? (array) $product->getLocations() : [];
        $hasOnline = in_array('Online', $locations, true);
        $physicalLocations = array_values(array_filter($locations, static fn ($location): bool => Str::of((string) $location)->lower()->contains('online') === false));
        $categoryName = trim((string) (optional($product->category)->name ?? ''));
        $title = trim((string) ($product->title ?? 'Wellness session'));
        $summary = trim((string) ($product->summary ?? ''));
        $price = is_numeric($product->price ?? null) ? (float) $product->price : null;
        $priceLabel = $price !== null
            ? 'From £' . number_format($price, 2)
            : 'Price on request';
        $filters = [];
        $haystack = Str::lower(trim($title . ' ' . $summary . ' ' . $categoryName));

        if ($hasOnline) {
            $filters[] = 'online';
        }
        if (! empty($physicalLocations)) {
            $filters[] = 'in-person';
        }
        if (Str::contains($haystack, ['sound', 'gong', 'bath'])) {
            $filters[] = 'sound';
        }

        return [
            'id' => $product->id,
            'title' => $title,
            'summary' => $summary !== '' ? $summary : 'A trusted wellness session designed to support the client experience.',
            'image' => method_exists($product, 'getFirstImageUrl') ? $product->getFirstImageUrl() : $defaultImage,
            'price_label' => $priceLabel,
            'category' => $categoryName !== '' ? $categoryName : 'Therapy',
            'location_label' => $hasOnline && ! empty($physicalLocations)
                ? 'Online & studio'
                : ($hasOnline ? 'Online Exclusive' : (! empty($physicalLocations) ? 'Studio' : 'Session')),
            'filters' => array_values(array_unique($filters)),
            'url' => app(\App\Services\SeoStructureService::class)->canonicalProductUrl($product),
            'badge' => $hasOnline && ! empty($physicalLocations)
                ? 'Online & studio'
                : ($hasOnline ? 'Online Exclusive' : (empty($physicalLocations) ? 'Session' : 'Studio')),
            'location_count' => count($physicalLocations),
        ];
    })->values();

    $offeringCardProducts = $visibleProducts->values();

    $productLocationLabels = $visibleProducts->flatMap(static function ($product): array {
        $locations = method_exists($product, 'getLocations') ? (array) $product->getLocations() : [];

        return array_values(array_filter(array_map(static function ($location): string {
            return trim((string) $location);
        }, $locations)));
    })->filter()->values();

    $hasOnlineLocation = $productLocationLabels->contains('Online');
    $hasPhysicalOfferLocations = $productLocationLabels->contains(static function (string $location): bool {
        return $location !== 'Online';
    });

    $allLocationRows = collect($vendor?->locations ?? []);
    $physicalLocations = $allLocationRows->map(static function ($location, int $index): ?array {
        $label = trim((string) ($location->label ?? ''));
        $address = trim((string) ($location->formatted_address ?? ''));
        $country = trim((string) ($location->country ?? ''));
        $combined = trim(implode(', ', array_filter([$label, $address, $country])));
        $normalizedCombined = Str::of($combined)
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->replaceMatches('/\s*,\s*/', ',')
            ->toString();
        $normalizedLabel = Str::of($label)->lower()->replaceMatches('/\s+/', ' ')->trim()->toString();
        $normalizedAddress = Str::of($address)->lower()->replaceMatches('/\s+/', ' ')->trim()->toString();
        $countryOnlyLabels = [
            'uk',
            'u.k.',
            'gb',
            'great britain',
            'united kingdom',
            'england',
            'scotland',
            'wales',
            'northern ireland',
        ];
        $isCountryOnly = in_array($normalizedLabel, $countryOnlyLabels, true)
            || in_array($normalizedAddress, $countryOnlyLabels, true);
        $hasZeroCoordinates = is_numeric($location->lat ?? null)
            && is_numeric($location->lng ?? null)
            && (float) $location->lat === 0.0
            && (float) $location->lng === 0.0;

        if (
            $combined === ''
            || Str::of($combined)->lower()->contains('online')
            || Str::of($combined)->lower()->contains('null')
            || $isCountryOnly
            || $hasZeroCoordinates
        ) {
            return null;
        }

        $lat = is_numeric($location->lat ?? null) ? (float) $location->lat : null;
        $lng = is_numeric($location->lng ?? null) ? (float) $location->lng : null;

        return [
            'id' => 'loc-physical-' . ($index + 1),
            'label' => $label !== '' ? $label : 'Location ' . ($index + 1),
            'address' => $address !== '' ? $address : 'Address available after booking',
            'lat' => $lat,
            'lng' => $lng,
            'online' => false,
            'dedupe_key' => $normalizedLabel . '|' . $normalizedAddress . '|' . $normalizedCombined,
        ];
    })->filter()
        ->unique('dedupe_key')
        ->map(static function (array $location): array {
            unset($location['dedupe_key']);

            return $location;
        })
        ->values();

    if (! $hasPhysicalOfferLocations) {
        $physicalLocations = collect();
    }

    $hasPhysicalLocations = $physicalLocations->isNotEmpty();
    $hasSoundFilter = $visibleProducts->contains(static function ($product): bool {
        $locations = method_exists($product, 'getLocations') ? (array) $product->getLocations() : [];
        $categoryName = trim((string) (optional($product->category)->name ?? ''));
        $title = trim((string) ($product->title ?? 'Wellness session'));
        $summary = trim((string) ($product->summary ?? ''));
        $haystack = Str::lower(trim($title . ' ' . $summary . ' ' . $categoryName));

        return Str::contains($haystack, ['sound', 'gong', 'bath'])
            || in_array('Sound', $locations, true)
            || in_array('Sound Therapy', $locations, true);
    });

    $formatSummary = $profileType === 'team'
        ? 'Team profile'
        : ($hasOnlineLocation && $hasPhysicalLocations
            ? 'Online & studio'
            : ($hasOnlineLocation ? 'Online Exclusive' : 'Studio'));

    $locationSummary = $hasOnlineLocation && $hasPhysicalLocations
        ? $physicalLocations->count() . ' locations + online'
        : ($hasOnlineLocation ? 'Online Exclusive' : ($hasPhysicalLocations ? $physicalLocations->count() . ' locations' : 'Not listed'));

    $bookingSummary = $visibleProducts->isNotEmpty()
        ? 'Live availability'
        : 'By request';

    $lowestPrice = $visibleProducts
        ->filter(static fn ($product): bool => is_numeric($product->price ?? null) && (float) $product->price > 0)
        ->sortBy('price')
        ->first();

    $priceSummary = $lowestPrice && is_numeric($lowestPrice->price ?? null)
        ? 'From £' . number_format((float) $lowestPrice->price, 2)
        : 'Price on request';

    $reviewRedirectUrl = request()->fullUrlWithQuery(['review' => 1]) . '#reviews';
    $canLeaveReview = $profileType === 'practitioner' && $vendor !== null;

    $reviewItems = $vendor
        ? collect($vendor?->customerReviews ?? [])
            ->filter(static function ($review): bool {
                return trim((string) ($review->review_text ?? '')) !== '';
            })
            ->sortByDesc(static function ($review): int {
                if (filled($review->created_at ?? null)) {
                    try {
                        return Carbon::parse($review->created_at)->timestamp;
                    } catch (\Throwable $e) {
                        return (int) ($review->id ?? 0);
                    }
                }

                return (int) ($review->id ?? 0);
            })
            ->values()
        : collect();

    $activeUserReview = auth()->check()
        ? $reviewItems->firstWhere('user_id', auth()->id())
        : null;
    $activeReviewRating = (int) ($activeUserReview->rating ?? 5);
    $activeReviewText = trim((string) ($activeUserReview->review_text ?? ''));
    $reviewAuthMode = old('auth_mode', 'login');
    $showReviewAuthModal = $canLeaveReview && ! auth()->check() && $errors->any();
    $recaptchaEnabled = config('recaptcha.enabled') && config('recaptcha.site_key');

    $reviewCount = $reviewItems->count();
    $reviewAverage = $reviewCount > 0 ? round((float) $reviewItems->avg('rating'), 1) : 0.0;
    $reviewSummary = $reviewCount > 0
        ? number_format($reviewAverage, 1) . ' · ' . $reviewCount . ' review' . ($reviewCount === 1 ? '' : 's')
        : 'Be the first to review';

    $hasCredentialsSection = ! empty($qualifications) || $insurance !== null || ! empty($accreditations);

    $heroStats = [
        [
            'label' => 'Offerings',
            'value' => $visibleProducts->count() > 0
                ? $visibleProducts->count() . ' session' . ($visibleProducts->count() === 1 ? '' : 's')
                : 'No live sessions',
        ],
        [
            'label' => 'Format',
            'value' => $formatSummary,
        ],
        [
            'label' => 'Insurance',
            'value' => $insurance ? 'Listed' : 'Not listed',
        ],
        [
            'label' => 'Reviews',
            'value' => $reviewSummary,
        ],
    ];

    $quickInfoCards = [
        [
            'label' => 'Focus',
            'value' => $roleLabel,
            'icon' => 'focus',
        ],
        [
            'label' => 'Format',
            'value' => $formatSummary,
            'icon' => 'format',
        ],
        [
            'label' => 'Booking',
            'value' => $bookingSummary,
            'icon' => 'booking',
        ],
        [
            'label' => 'Locations',
            'value' => $locationSummary,
            'icon' => 'location',
        ],
        [
            'label' => 'Reviews',
            'value' => $reviewSummary,
            'icon' => 'review',
        ],
    ];

    $heroSlides = collect(array_filter(array_merge(
        [$coverImage],
        $productCards->pluck('image')->all()
    )))->unique()->take(3)->values()->all();

    if (empty($heroSlides)) {
        $heroSlides = [$defaultImage];
    }

    $heroBadge = $profileType === 'team'
        ? 'Team member'
        : 'Verified practitioner';

    $heroWatermark = $profileType === 'team'
        ? 'TEAM'
        : 'PROFILE';

    $heroPills = array_values(array_filter([
        $roleLabel,
        $formatSummary,
        $experienceLabel !== '' ? $experienceLabel . ' years experience' : null,
    ]));

    $profileSnapshot = [
        ['label' => $profileType === 'team' ? 'Profile type' : 'Practitioner', 'value' => $displayName],
        ['label' => 'Offerings', 'value' => $visibleProducts->count() > 0 ? (string) $visibleProducts->count() : 'None'],
        ['label' => 'Format', 'value' => $formatSummary],
        ['label' => 'Locations', 'value' => $locationSummary],
        ['label' => 'Insurance', 'value' => $insurance ? ($insuranceProvider !== '' ? $insuranceProvider : 'Listed') : 'Not listed'],
        ['label' => 'Language', 'value' => $languageLabel],
    ];

    $availabilityGuide = [
        ['label' => 'Booking', 'value' => $bookingSummary],
        ['label' => 'Locations', 'value' => $locationSummary],
        ['label' => 'Online', 'value' => $hasOnlineLocation ? 'Available' : 'Not listed'],
        ['label' => 'Reviews', 'value' => $reviewSummary],
    ];

    $locationMode = $hasOnlineLocation && $hasPhysicalLocations
        ? 'mixed'
        : ($hasOnlineLocation ? 'online-only' : ($hasPhysicalLocations ? 'studio-only' : 'none'));

    $locationTitle = $locationMode === 'online-only'
        ? 'Online Exclusive.'
        : ($locationMode === 'mixed'
            ? 'Online or studio.'
            : ($locationMode === 'studio-only'
                ? 'Choose the studio that works for you.'
                : 'Locations.'));

    $locationIntro = $locationMode === 'online-only'
        ? 'Your sessions are delivered online only. The joining link is sent after booking.'
        : ($locationMode === 'mixed'
            ? 'Pick a studio location or switch to the online session during checkout.'
            : ($locationMode === 'studio-only'
                ? 'Pick your preferred location during checkout or switch it from the booking panel.'
                : 'Location details will appear here once the profile is completed.'));

    $onlineOverlayTitle = $locationMode === 'online-only' ? 'Online Exclusive' : 'Online session selected';
    $onlineOverlayCopy = $locationMode === 'online-only'
        ? 'No venue map is needed. Your joining link is sent after booking.'
        : 'No venue map is needed while online is selected. Your joining link is sent after booking.';

    $selectedLocationId = $locationMode === 'online-only'
        ? 'loc-online'
        : ($physicalLocations->first()['id'] ?? 'loc-online');

    $selectedLocationType = $selectedLocationId === 'loc-online' ? 'online' : 'map';
    $locationCards = $physicalLocations->values()->all();
    if ($hasOnlineLocation) {
        $locationCards[] = [
            'id' => 'loc-online',
            'label' => $locationMode === 'online-only' ? 'Online Exclusive' : 'Online session',
            'address' => 'Live session link sent after booking',
            'online' => true,
        ];
    }

    $physicalLocationData = $physicalLocations->map(static function (array $location): array {
        return [
            'id' => $location['id'],
            'label' => $location['label'],
            'address' => $location['address'],
            'lat' => $location['lat'],
            'lng' => $location['lng'],
        ];
    })->values()->all();

    $videoSource = 'https://www.pexels.com/download/video/4359824/';
    $videoPoster = $heroSlides[0] ?? $defaultImage;

    $breadcrumbs = $profileType === 'team'
        ? [
            ['label' => 'Home', 'url' => url('/')],
            ['label' => 'About', 'url' => url('/about')],
            ['label' => 'Team', 'url' => url('/about#team')],
            ['label' => $displayName],
        ]
        : [
            ['label' => 'Home', 'url' => url('/')],
            ['label' => 'Practitioners', 'url' => url('/providers')],
            ['label' => $displayName],
        ];

    $navLinks = [
        ['label' => 'Overview', 'href' => '#overview'],
        ['label' => 'About', 'href' => '#about'],
    ];
    if ($hasCredentialsSection) {
        $navLinks[] = ['label' => 'Credentials', 'href' => '#credentials'];
    }
    $navLinks[] = ['label' => 'Offerings', 'href' => '#offerings'];
    $navLinks[] = ['label' => 'Locations', 'href' => '#locations'];
    $navLinks[] = ['label' => 'Reviews', 'href' => '#reviews'];
@endphp

<div
    hidden
    data-wow-analytics-practitioner
    data-wow-analytics-item="{!! e(json_encode([
        'practitioner_id' => (string) ($user->id ?? ''),
        'profile_type' => $profileType,
        'category_count' => (int) $visibleProducts->count(),
        'location' => $productLocationLabels->first() ?? null,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !!}"
></div>

@push('styles')
@if($hasPhysicalLocations)
<link href="https://api.mapbox.com/mapbox-gl-js/v3.5.1/mapbox-gl.css" rel="stylesheet">
@endif
<style>
    .wow-profile-page {
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
        --red-soft: #fff1ed;
        --red: #ba5b43;
        --shadow: 0 18px 50px rgba(25, 53, 44, 0.12);
        --shadow-soft: 0 10px 28px rgba(25, 53, 44, 0.08);
        --radius: 4px;
        --max: 1180px;
        color: var(--ink);
        background: transparent;
    }

    .wow-profile-page,
    .wow-profile-page * {
        box-sizing: border-box;
    }

    .wow-profile-page img {
        width: 100%;
        display: block;
    }

    .wow-profile-page a {
        color: inherit;
        text-decoration: none;
    }

    .wow-profile-page button {
        font: inherit;
    }

    .wow-profile-page button,
    .wow-profile-page .btn,
    .wow-profile-page .filter-btn,
    .wow-profile-page .offering-card-btn,
    .wow-profile-page .profile-nav-link,
    .wow-profile-page .contact-action,
    .wow-profile-page .location-card,
    .wow-profile-page .map-location-option {
        font-weight: 400 !important;
    }

    .wow-profile-page .btn {
        appearance: none;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 48px;
        padding: 0 16px;
        border-radius: var(--radius);
        font-size: 15px;
        transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
    }

    .wow-profile-page .btn:hover {
        transform: translateY(-1px);
    }

    .wow-profile-page .btn-primary {
        color: #ffffff;
        background: var(--green);
        border-color: var(--green);
    }

    .wow-profile-page .btn-primary:hover {
        background: var(--green-hover);
        border-color: var(--green-hover);
    }

    .wow-profile-page .btn-secondary {
        color: var(--ink);
        background: #ffffff;
        border-color: var(--line-dark);
    }

    .wow-profile-page .profile-hero-wrap {
        max-width: var(--max);
        margin: 18px auto 0;
        padding: 0 20px;
    }

    .wow-profile-page .profile-hero {
        position: relative;
        min-height: 560px;
        overflow: hidden;
        border-radius: var(--radius);
        background: #11241e;
        box-shadow: var(--shadow);
    }

    .wow-profile-page .hero-slide {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        opacity: 0;
        transform: scale(1.02);
        transition: opacity 900ms ease, transform 5500ms ease;
    }

    .wow-profile-page .hero-slide.is-active {
        opacity: 1;
        transform: scale(1);
    }

    .wow-profile-page .hero-overlay {
        position: absolute;
        inset: 0;
        z-index: 1;
        background: rgba(14, 34, 27, 0.58);
        pointer-events: none;
    }

    .wow-profile-page .hero-watermark {
        position: absolute;
        right: 18px;
        top: 34px;
        z-index: 2;
        color: rgba(255, 255, 255, 0.08);
        font-size: clamp(64px, 11vw, 136px);
        line-height: 0.84;
        letter-spacing: -0.08em;
        text-transform: uppercase;
        pointer-events: none;
        font-weight: 500;
        text-align: right;
    }

    .wow-profile-page .profile-hero-content {
        position: relative;
        z-index: 3;
        min-height: 560px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 390px;
        gap: 28px;
        align-items: end;
        padding: 42px;
    }

    .wow-profile-page .profile-hero-main {
        max-width: 760px;
    }

    .wow-profile-page .kicker-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }

    .wow-profile-page .pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: #eefbf6;
        font-size: 13px;
    }

    .wow-profile-page .hero-profile-lockup {
        display: grid;
        grid-template-columns: 132px minmax(0, 1fr);
        gap: 18px;
        align-items: center;
        max-width: 760px;
    }

    .wow-profile-page .hero-profile-avatar,
    .wow-profile-page .hero-profile-avatar-fallback {
        width: 132px;
        height: 132px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid rgba(255, 255, 255, 0.92);
        box-shadow: 0 18px 48px rgba(2, 18, 32, 0.24);
    }

    .wow-profile-page .hero-profile-avatar-fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--green-soft);
        color: var(--green-dark);
        font-size: 34px;
        letter-spacing: -0.04em;
        font-weight: 600;
        text-transform: uppercase;
    }

    .wow-profile-page .hero-profile-label {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 6px 9px;
        margin-bottom: 10px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.86);
        font-size: 12px;
    }

    .wow-profile-page .profile-hero h1 {
        max-width: 760px;
        margin: 0;
        color: #ffffff;
        font-size: clamp(44px, 6vw, 84px);
        line-height: 0.94;
        letter-spacing: -0.065em;
        font-weight: 500;
    }

    .wow-profile-page .hero-profile-role {
        display: block;
        margin-top: 10px;
        color: rgba(255, 255, 255, 0.78);
        font-size: 16px;
        line-height: 1.45;
    }

    .wow-profile-page .hero-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        max-width: 820px;
        margin-top: 34px;
    }

    .wow-profile-page .hero-stat {
        min-height: 92px;
        padding: 15px;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.11);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #ffffff;
    }

    .wow-profile-page .hero-stat small {
        display: block;
        margin-bottom: 6px;
        color: rgba(255, 255, 255, 0.66);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .wow-profile-page .hero-stat strong {
        display: block;
        font-size: 15px;
        line-height: 1.25;
        font-weight: 400;
    }

    .wow-profile-page .profile-card {
        position: sticky;
        top: 22px;
        align-self: start;
        padding: 18px;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid var(--line);
        box-shadow: 0 22px 60px rgba(2, 18, 32, 0.22);
        backdrop-filter: blur(16px);
    }

    .wow-profile-page .profile-card-head {
        display: grid;
        grid-template-columns: 86px 1fr;
        gap: 14px;
        align-items: center;
    }

    .wow-profile-page .profile-avatar,
    .wow-profile-page .profile-avatar-fallback {
        width: 86px;
        height: 86px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #ffffff;
        box-shadow: var(--shadow-soft);
    }

    .wow-profile-page .profile-avatar-fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--green-soft);
        color: var(--green-dark);
        font-size: 24px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .wow-profile-page .profile-card h2 {
        margin: 0 0 5px;
        color: var(--ink);
        font-size: 24px;
        line-height: 1;
        letter-spacing: -0.045em;
        font-weight: 500;
    }

    .wow-profile-page .profile-card-role {
        display: block;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.35;
    }

    .wow-profile-page .trust-row {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 14px;
    }

    .wow-profile-page .trust-chip,
    .wow-profile-page .location-chip,
    .wow-profile-page .offering-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 28px;
        padding: 5px 8px;
        border: 1px solid #cbe5da;
        border-radius: var(--radius);
        background: var(--green-soft);
        color: var(--green-dark);
        font-size: 12px;
        line-height: 1.2;
    }

    .wow-profile-page .trust-chip::before,
    .wow-profile-page .location-chip::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
        opacity: 0.62;
    }

    .wow-profile-page .profile-mini-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-top: 16px;
    }

    .wow-profile-page .profile-mini-stat {
        padding: 12px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
    }

    .wow-profile-page .profile-mini-stat small {
        display: block;
        color: var(--muted);
        font-size: 10px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .wow-profile-page .profile-mini-stat strong {
        display: block;
        margin-top: 4px;
        color: var(--ink);
        font-size: 15px;
        font-weight: 400;
    }

    .wow-profile-page .profile-card .btn {
        min-height: 42px;
        font-size: 13px;
    }

    .wow-profile-page .profile-nav {
        position: sticky;
        top: 0;
        z-index: 20;
        max-width: var(--max);
        margin: 18px auto 0;
        padding: 0 20px;
    }

    .wow-profile-page .profile-nav-inner {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding: 8px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: var(--shadow-soft);
        backdrop-filter: blur(14px);
    }

    .wow-profile-page .profile-nav-link {
        flex: 0 0 auto;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        padding: 0 12px;
        border: 1px solid transparent;
        border-radius: var(--radius);
        color: var(--muted);
        font-size: 13px;
        white-space: nowrap;
    }

    .wow-profile-page .profile-nav-link:hover,
    .wow-profile-page .profile-nav-link.is-active {
        color: var(--green-dark);
        background: var(--green-soft);
        border-color: #cbe5da;
    }

    .wow-profile-page .content-wrap {
        max-width: var(--max);
        margin: 24px auto 100px;
        padding: 0 20px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 28px;
        align-items: start;
    }

    .wow-profile-page .content-main {
        min-width: 0;
    }

    .wow-profile-page .section {
        padding: 34px;
        margin-bottom: 22px;
        border-radius: var(--radius);
        background: #ffffff;
        border: 1px solid var(--line);
        box-shadow: var(--shadow-soft);
    }

    .wow-profile-page .section.flat {
        padding: 0;
        background: transparent;
        border: 0;
        box-shadow: none;
    }

    .wow-profile-page .section-heading {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 22px;
    }

    .wow-profile-page .eyebrow {
        margin: 0 0 8px;
        color: var(--green);
        font-size: 12px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .wow-profile-page .section h2 {
        margin: 0;
        color: var(--ink);
        font-size: clamp(26px, 3vw, 40px);
        line-height: 1;
        letter-spacing: -0.05em;
        font-weight: 500;
    }

    .wow-profile-page .section-intro {
        max-width: 680px;
        color: var(--muted);
        font-size: 16px;
        line-height: 1.65;
        margin: 12px 0 0;
    }

    .wow-profile-page .quick-info-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }

    .wow-profile-page .quick-info-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        min-height: 154px;
        padding: 18px 12px;
        text-align: center;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .wow-profile-page .quick-info-card:hover {
        transform: translateY(-1px);
        border-color: #cbe5da;
        box-shadow: var(--shadow-soft);
    }

    .wow-profile-page .quick-info-icon {
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

    .wow-profile-page .quick-info-icon svg {
        width: 28px;
        height: 28px;
    }

    .wow-profile-page .quick-info-card small {
        display: block;
        color: var(--muted);
        font-size: 11px;
        line-height: 1.1;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .wow-profile-page .quick-info-card strong {
        display: block;
        max-width: 120px;
        color: var(--ink);
        line-height: 1.08;
        letter-spacing: -0.035em;
        font-weight: 500;
        font-size: 13px;
    }

    .wow-profile-page .rich-text {
        color: var(--muted);
        font-size: 15px;
        line-height: 1.75;
        overflow-wrap: anywhere;
    }

    .wow-profile-page .rich-text :where(article, section, header, footer, div) {
        margin: 0 0 18px;
    }

    .wow-profile-page .rich-text :where(h2, h3, h4, h5, h6) {
        color: var(--ink);
        margin: 24px 0 10px;
        line-height: 1.12;
        letter-spacing: -0.035em;
        font-weight: 500;
    }

    .wow-profile-page .rich-text h2 {
        font-size: clamp(25px, 2.4vw, 34px);
    }

    .wow-profile-page .rich-text h3 {
        font-size: clamp(22px, 2vw, 28px);
    }

    .wow-profile-page .rich-text p {
        margin: 0 0 14px;
    }

    .wow-profile-page .rich-text p:empty,
    .wow-profile-page .rich-text div:empty,
    .wow-profile-page .rich-text span:empty,
    .wow-profile-page .rich-text p:has(> br:only-child),
    .wow-profile-page .rich-text p:has(> span:empty:only-child) {
        display: none !important;
    }

    .wow-profile-page .rich-text blockquote {
        margin: 22px 0;
        padding: 18px 20px;
        border-left: 4px solid var(--green);
        background: var(--soft);
        color: var(--green-dark);
        border-radius: var(--radius);
    }

    .wow-profile-page .rich-text ul,
    .wow-profile-page .rich-text ol {
        list-style: none;
        margin: 14px 0 0;
        padding-left: 0;
    }

    .wow-profile-page .rich-text li {
        position: relative;
        margin: 0 0 10px;
        padding-left: 26px;
    }

    .wow-profile-page .rich-text ul > li::before {
        content: "";
        position: absolute;
        top: 0.72em;
        left: 2px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--green);
        box-shadow: 0 0 0 5px var(--green-soft);
        transform: translateY(-50%);
    }

    .wow-profile-page .credential-layout {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .wow-profile-page .credential-card {
        padding: 18px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
    }

    .wow-profile-page .credential-card h3 {
        margin: 0 0 12px;
        color: var(--ink);
        font-size: 18px;
        font-weight: 500;
        letter-spacing: -0.025em;
    }

    .wow-profile-page .credential-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .wow-profile-page .credential-tag {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 6px 9px;
        border: 1px solid #cbe5da;
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--green-dark);
        font-size: 12px;
    }

    .wow-profile-page .offering-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .wow-profile-page .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .wow-profile-page .filter-btn {
        min-height: 38px;
        padding: 0 12px;
        border: 1px solid var(--line-dark);
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--muted);
        cursor: pointer;
        font-size: 13px;
    }

    .wow-profile-page .filter-btn:hover,
    .wow-profile-page .filter-btn.is-active {
        color: var(--green-dark);
        border-color: #cbe5da;
        background: var(--green-soft);
    }

    .wow-profile-page .offering-count {
        color: var(--muted);
        font-size: 13px;
    }

    .wow-profile-page .offerings-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .wow-profile-page .offering-card {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 14px;
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #ffffff;
        box-shadow: var(--shadow-soft);
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .wow-profile-page .offering-card:hover {
        transform: translateY(-1px);
        border-color: #cbe5da;
        box-shadow: var(--shadow);
    }

    .wow-profile-page .offering-media {
        position: relative;
        min-height: 180px;
        background: #dbeae4;
    }

    .wow-profile-page .offering-media img {
        height: 100%;
        object-fit: cover;
    }

    .wow-profile-page .offering-media-badge {
        position: absolute;
        left: 10px;
        top: 10px;
        padding: 6px 8px;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.94);
        color: var(--green-dark);
        font-size: 12px;
        box-shadow: var(--shadow-soft);
    }

    .wow-profile-page .offering-card-body {
        min-width: 0;
        display: flex;
        flex-direction: column;
        padding: 14px 14px 14px 0;
    }

    .wow-profile-page .offering-card h3 {
        margin: 0;
        color: var(--ink);
        font-size: 19px;
        line-height: 1.12;
        letter-spacing: -0.035em;
        font-weight: 500;
    }

    .wow-profile-page .offering-card p {
        margin: 9px 0 0;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.5;
    }

    .wow-profile-page .offering-meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 12px;
    }

    .wow-profile-page .offering-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: auto;
        padding-top: 14px;
    }

    .wow-profile-page .offering-price {
        display: grid;
        gap: 2px;
    }

    .wow-profile-page .offering-price span {
        color: var(--muted);
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .wow-profile-page .offering-price strong {
        color: var(--ink);
        font-size: 19px;
        font-weight: 500;
        letter-spacing: -0.035em;
    }

    .wow-profile-page .offering-card-btn {
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 12px;
        border-radius: var(--radius);
        color: #ffffff;
        background: var(--green);
        border: 1px solid var(--green);
        font-size: 13px;
        white-space: nowrap;
    }

    .wow-profile-page .offering-card-btn:hover {
        background: var(--green-hover);
        border-color: var(--green-hover);
    }

    .wow-profile-page .offerings-grid--v4 {
        display: flex;
        flex-wrap: nowrap;
        gap: 14px;
        overflow-x: auto;
        overflow-y: visible;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 6px;
        scrollbar-width: none;
        align-items: stretch;
    }

    .wow-profile-page .offerings-grid--v4::-webkit-scrollbar {
        display: none;
    }

    .wow-profile-page .offering-card-shell {
        display: flex;
        flex: 0 0 calc((100% - 14px) / 2);
        min-width: calc((100% - 14px) / 2);
        max-width: calc((100% - 14px) / 2);
        scroll-snap-align: start;
        scroll-snap-stop: always;
        width: auto;
        align-self: stretch;
    }

    .wow-profile-page .offering-card-shell > * {
        width: 100%;
    }

    .wow-profile-page .offering-card-shell .wow-therapy-card-scope {
        display: flex;
        width: 100%;
    }

    .wow-profile-page .offering-card-shell .wow-therapy-card-scope .wow-card.md,
    .wow-profile-page .offering-card-shell .wow-event-card-v4 {
        width: 100% !important;
        max-width: none !important;
        max-height: none !important;
        flex: 1 1 auto !important;
    }

    .wow-profile-page .offering-card-shell .wow-therapy-card-scope .therapy-card {
        width: 100% !important;
    }

    .wow-profile-page .offering-card-shell .wow-event-card-v4 {
        display: block;
    }

    .wow-profile-page .offering-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .wow-profile-page .offering-toolbar-right {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
    }

    .wow-profile-page .offering-carousel-controls {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .wow-profile-page .offering-carousel-controls .carousel-arrow {
        width: 42px;
        height: 42px;
    }

    .wow-profile-page .review-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .wow-profile-page .review-card {
        padding: 20px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
    }

    .wow-profile-page .review-stars {
        color: var(--amber);
        letter-spacing: 0.08em;
        font-size: 13px;
    }

    .wow-profile-page .review-card p {
        margin: 12px 0 0;
        color: var(--ink);
        line-height: 1.6;
        font-size: 15px;
    }

    .wow-profile-page .review-card footer {
        margin-top: 14px;
        color: var(--muted);
        font-size: 13px;
    }

    .wow-profile-page .review-gate-card {
        display: grid;
        gap: 18px;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        padding: 22px;
        margin-bottom: 18px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
    }

    .wow-profile-page .review-gate-copy h3,
    .wow-profile-page .review-composer-head h3,
    .wow-profile-page .review-modal-head h3 {
        margin: 0;
        color: var(--ink);
        font-size: 24px;
        line-height: 1.05;
        letter-spacing: -0.04em;
        font-weight: 500;
    }

    .wow-profile-page .review-gate-copy p,
    .wow-profile-page .review-composer-head p,
    .wow-profile-page .review-modal-body p {
        margin: 9px 0 0;
        color: var(--muted);
        font-size: 15px;
        line-height: 1.55;
    }

    .wow-profile-page .review-gate-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .wow-profile-page .review-gate-actions .btn {
        min-height: 44px;
        white-space: nowrap;
    }

    .wow-profile-page .review-composer-card {
        padding: 22px;
        margin-bottom: 18px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
    }

    .wow-profile-page .review-composer-head {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    .wow-profile-page .review-composer-head p:first-child {
        margin-top: 0;
    }

    .wow-profile-page .review-switch-account {
        min-height: 40px;
        flex: 0 0 auto;
        font-size: 13px;
    }

    .wow-profile-page .review-write-form {
        display: grid;
        gap: 12px;
    }

    .wow-profile-page .review-rating-field {
        display: grid;
        gap: 8px;
    }

    .wow-profile-page .review-rating-field label,
    .wow-profile-page .review-auth-field label {
        color: var(--green-dark);
        font-size: 12px;
    }

    .wow-profile-page .review-star-input {
        display: inline-flex;
        width: fit-content;
        gap: 4px;
        padding: 6px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #ffffff;
    }

    .wow-profile-page .review-star-btn {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: var(--radius);
        background: transparent;
        color: #c8d1cc;
        cursor: pointer;
        font-size: 22px;
        line-height: 1;
    }

    .wow-profile-page .review-star-btn.is-active {
        color: var(--amber);
        background: #fff8e5;
    }

    .wow-profile-page .review-field {
        display: grid;
        gap: 6px;
    }

    .wow-profile-page .review-field input,
    .wow-profile-page .review-field textarea,
    .wow-profile-page .review-auth-field input,
    .wow-profile-page .review-auth-field textarea {
        width: 100%;
        min-height: 44px;
        padding: 12px;
        border: 1px solid var(--line-dark);
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--ink);
        font: inherit;
        font-size: 14px;
        font-weight: 400;
    }

    .wow-profile-page .review-field input,
    .wow-profile-page .review-field textarea,
    .wow-profile-page .review-auth-field textarea {
        min-height: 140px;
        resize: vertical;
        line-height: 1.5;
    }

    .wow-profile-page .review-field input:focus,
    .wow-profile-page .review-field textarea:focus,
    .wow-profile-page .review-auth-field input:focus,
    .wow-profile-page .review-auth-field textarea:focus {
        outline: none;
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(84, 148, 131, 0.16);
    }

    .wow-profile-page .review-submit {
        min-height: 44px;
        white-space: nowrap;
        font-size: 13px;
    }

    .wow-profile-page .review-privacy-note,
    .wow-profile-page .review-auth-note {
        margin: 2px 0 0;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .wow-profile-page .review-auth-alert {
        margin: 0 0 12px;
        padding: 12px;
        border: 1px solid #f1c8c0;
        border-radius: var(--radius);
        background: var(--red-soft);
        color: var(--red);
        font-size: 13px;
        line-height: 1.45;
    }

    .wow-profile-page .review-success-alert {
        margin: 0 0 12px;
        padding: 12px;
        border: 1px solid #cbe5da;
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--green-dark);
        font-size: 13px;
        line-height: 1.45;
    }

    .wow-profile-page .review-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 900;
        display: none;
        background: rgba(14, 34, 27, 0.56);
    }

    .wow-profile-page .review-modal-backdrop.is-open {
        display: block;
    }

    .wow-profile-page .review-modal {
        position: fixed;
        left: 50%;
        top: 50%;
        z-index: 910;
        display: none;
        width: min(92vw, 720px);
        max-height: 92vh;
        overflow: auto;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #ffffff;
        box-shadow: 0 24px 80px rgba(7, 29, 51, 0.22);
        transform: translate(-50%, -50%);
    }

    .wow-profile-page .review-modal.is-open {
        display: block;
    }

    .wow-profile-page .review-modal-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 18px;
        border-bottom: 1px solid var(--line);
        background: var(--soft);
    }

    .wow-profile-page .review-modal-head .eyebrow {
        margin-bottom: 6px;
    }

    .wow-profile-page .review-modal-close {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border: 1px solid var(--line-dark);
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--ink);
        cursor: pointer;
        font-size: 24px;
        line-height: 1;
    }

    .wow-profile-page .review-modal-tabs {
        display: flex;
        gap: 8px;
        padding: 18px 18px 0;
    }

    .wow-profile-page .review-modal-tab {
        flex: 1;
        min-height: 42px;
        padding: 0 14px;
        border: 1px solid var(--line-dark);
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--muted);
        cursor: pointer;
        font-size: 13px;
    }

    .wow-profile-page .review-modal-tab.is-active {
        color: var(--green-dark);
        border-color: #cbe5da;
        background: var(--green-soft);
    }

    .wow-profile-page .review-modal-panel {
        display: none;
        padding: 18px;
    }

    .wow-profile-page .review-modal-panel.is-active {
        display: block;
    }

    .wow-profile-page .review-auth-form {
        display: grid;
        gap: 12px;
    }

    .wow-profile-page .review-auth-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .wow-profile-page .review-auth-field {
        display: grid;
        gap: 6px;
    }

    .wow-profile-page .review-auth-field--full {
        grid-column: 1 / -1;
    }

    .wow-profile-page .review-auth-check {
        display: inline-flex;
        align-items: flex-start;
        gap: 10px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.45;
    }

    .wow-profile-page .review-auth-check input {
        margin-top: 3px;
        flex: 0 0 auto;
    }

    .wow-profile-page .review-auth-check span a {
        color: inherit;
        font-weight: 600;
        text-decoration: none;
    }

    .wow-profile-page .review-auth-check span a:hover {
        text-decoration: underline;
    }

    .wow-profile-page .review-auth-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .wow-profile-page .review-auth-actions .btn {
        min-height: 44px;
    }

    .wow-profile-page .is-hidden {
        display: none !important;
    }

    .wow-profile-page .map-section-layout {
        display: grid;
        grid-template-columns: 0.8fr 1.2fr;
        gap: 14px;
    }

    .wow-profile-page .location-list {
        display: block;
        max-height: 390px;
        overflow-y: auto;
        padding-right: 6px;
        scrollbar-width: thin;
        scrollbar-color: rgba(84, 148, 131, 0.45) transparent;
    }

    .wow-profile-page .location-list::-webkit-scrollbar {
        width: 6px;
    }

    .wow-profile-page .location-list::-webkit-scrollbar-thumb {
        background: rgba(84, 148, 131, 0.45);
        border-radius: var(--radius);
    }

    .wow-profile-page .location-card {
        width: 100%;
        display: block;
        margin-bottom: 10px;
        padding: 15px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #ffffff;
        color: var(--ink);
        text-align: left;
        cursor: pointer;
        transition: border-color 0.18s ease, background 0.18s ease, transform 0.18s ease;
    }

    .wow-profile-page .location-card.is-active,
    .wow-profile-page .location-card:hover {
        border-color: #cbe5da;
        background: var(--green-soft);
        transform: translateY(-1px);
        margin-top: 2px;
    }

    .wow-profile-page .location-card h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 500;
    }

    .wow-profile-page .location-card p {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.45;
    }

    .wow-profile-page .map-box {
        position: relative;
        min-height: 390px;
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--line);
        background: #eaf3ee;
    }

    .wow-profile-page .mapbox-map {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }

    .wow-profile-page .map-online-video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
        background: #10251f;
    }

    .wow-profile-page .map-online-overlay,
    .wow-profile-page .map-map-overlay {
        position: absolute;
        inset: 0;
        z-index: 2;
        display: none;
        align-items: end;
        padding: 20px;
        background: linear-gradient(180deg, rgba(14, 34, 27, 0) 0%, rgba(14, 34, 27, 0.42) 100%);
        pointer-events: none;
    }

    .wow-profile-page .map-online-overlay > div,
    .wow-profile-page .map-map-overlay > div {
        max-width: 320px;
        padding: 16px;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(255, 255, 255, 0.36);
        box-shadow: var(--shadow-soft);
    }

    .wow-profile-page .map-online-overlay strong,
    .wow-profile-page .map-map-overlay strong {
        display: block;
        color: var(--ink);
        font-size: 16px;
        margin-bottom: 6px;
    }

    .wow-profile-page .map-online-overlay span,
    .wow-profile-page .map-map-overlay span {
        display: block;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.45;
    }

    .wow-profile-page .map-box.is-online-mode .map-online-video {
        display: block;
    }

    .wow-profile-page .map-box.is-online-mode .map-online-overlay {
        display: flex;
    }

    .wow-profile-page .map-box.is-online-mode .mapbox-map {
        display: none;
    }

    .wow-profile-page .map-box.is-map-mode .mapbox-map {
        display: block;
    }

    .wow-profile-page .map-empty-state {
        padding: 24px;
        border: 1px dashed var(--line-dark);
        border-radius: var(--radius);
        background: var(--soft);
        color: var(--muted);
    }

    .wow-profile-page .side-stack {
        position: sticky;
        top: 136px;
        display: grid;
        gap: 16px;
        align-self: start;
    }

    .wow-profile-page .side-card {
        padding: 22px;
        border-radius: var(--radius);
        background: #ffffff;
        border: 1px solid var(--line);
        box-shadow: var(--shadow-soft);
    }

    .wow-profile-page .side-card h3 {
        margin: 0 0 14px;
        color: var(--ink);
        font-size: 19px;
        letter-spacing: -0.03em;
        font-weight: 500;
    }

    .wow-profile-page .mini-list {
        display: grid;
        gap: 12px;
    }

    .wow-profile-page .mini-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--line);
        color: var(--muted);
        font-size: 14px;
    }

    .wow-profile-page .mini-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .wow-profile-page .mini-row strong {
        color: var(--ink);
        text-align: right;
        font-weight: 400;
    }

    .wow-profile-page .availability-list {
        display: grid;
        gap: 8px;
    }

    .wow-profile-page .availability-day {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 12px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--soft);
        color: var(--muted);
        font-size: 13px;
    }

    .wow-profile-page .availability-day strong {
        color: var(--ink);
        font-weight: 400;
    }

    .wow-profile-page .empty-state {
        padding: 18px;
        border: 1px dashed var(--line-dark);
        border-radius: var(--radius);
        background: var(--soft);
        color: var(--muted);
    }

    .wow-profile-page .profile-meta-badge {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 6px 9px;
        margin-bottom: 10px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.86);
        font-size: 12px;
    }

    .wow-profile-page .hero-card-stack {
        display: grid;
        gap: 12px;
    }

    .wow-profile-page .hero-card-stack .profile-mini-stats {
        margin-top: 0;
    }

    .wow-profile-page .hero-card-stack .trust-row {
        margin-top: 0;
    }

    .wow-profile-page .hero-card-stack .profile-card-actions {
        margin-top: 0;
    }

    .wow-profile-page .hero-card-stack .profile-panel-top {
        padding-bottom: 16px;
        border-bottom: 1px solid var(--line);
    }

    @media (max-width: 1100px) {
        .wow-profile-page .profile-hero-content,
        .wow-profile-page .content-wrap {
            grid-template-columns: 1fr;
        }

        .wow-profile-page .profile-card,
        .wow-profile-page .side-stack {
            position: relative;
            top: auto;
        }

        .wow-profile-page .hero-stat-grid,
        .wow-profile-page .quick-info-grid,
        .wow-profile-page .credential-layout,
        .wow-profile-page .review-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .wow-profile-page .offerings-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .wow-profile-page .profile-hero-wrap,
        .wow-profile-page .content-wrap,
        .wow-profile-page .profile-nav {
            padding-left: 12px;
            padding-right: 12px;
        }

        .wow-profile-page .profile-hero-wrap {
            margin-top: 12px;
        }

        .wow-profile-page .profile-hero {
            min-height: auto;
            overflow: hidden;
        }

        .wow-profile-page .hero-slide {
            height: 270px;
            bottom: auto;
        }

        .wow-profile-page .hero-overlay {
            height: 270px;
            bottom: auto;
        }

        .wow-profile-page .hero-watermark {
            display: none;
        }

        .wow-profile-page .profile-hero-content {
            display: block;
            min-height: auto;
            padding: 214px 12px 12px;
        }

        .wow-profile-page .profile-hero-main {
            max-width: none;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(16px);
        }

        .wow-profile-page .pill {
            background: var(--green-soft);
            border-color: #cbe5da;
            color: var(--green-dark);
        }

        .wow-profile-page .hero-profile-lockup {
            grid-template-columns: 82px minmax(0, 1fr);
            gap: 12px;
        }

        .wow-profile-page .hero-profile-avatar,
        .wow-profile-page .hero-profile-avatar-fallback {
            width: 82px;
            height: 82px;
            border-width: 4px;
            box-shadow: var(--shadow-soft);
        }

        .wow-profile-page .hero-profile-label {
            color: var(--green-dark);
            background: var(--green-soft);
            border-color: #cbe5da;
        }

        .wow-profile-page .profile-hero h1 {
            color: var(--ink);
            font-size: 42px;
            line-height: 0.96;
        }

        .wow-profile-page .hero-profile-role {
            color: var(--muted);
            font-size: 14px;
            margin-top: 6px;
        }

        .wow-profile-page .hero-stat-grid {
            display: none;
        }

        .wow-profile-page .profile-card {
            margin-top: 12px;
            box-shadow: var(--shadow-soft);
        }

        .wow-profile-page .profile-nav {
            margin-top: 12px;
        }

        .wow-profile-page .profile-nav-inner {
            padding: 6px;
        }

        .wow-profile-page .content-wrap {
            display: block;
            margin-top: 18px;
        }

        .wow-profile-page .section {
            padding: 22px;
            margin-bottom: 16px;
        }

        .wow-profile-page .section-heading {
            display: block;
        }

        .wow-profile-page .quick-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .wow-profile-page .quick-info-card {
            min-height: 130px;
            padding: 13px 8px;
        }

        .wow-profile-page .quick-info-icon {
            width: 44px;
            height: 44px;
            box-shadow: none;
        }

        .wow-profile-page .quick-info-icon svg {
            width: 22px;
            height: 22px;
        }

        .wow-profile-page .quick-info-card strong {
            font-size: 14px;
        }

        .wow-profile-page .credential-layout,
        .wow-profile-page .review-grid,
        .wow-profile-page .map-section-layout {
            grid-template-columns: 1fr;
        }

        .wow-profile-page .offerings-grid {
            grid-template-columns: 1fr;
        }

        .wow-profile-page .offering-card {
            grid-template-columns: 1fr;
        }

        .wow-profile-page .offering-media {
            min-height: 230px;
        }

        .wow-profile-page .offering-card-body {
            padding: 16px;
        }

        .wow-profile-page .offering-bottom {
            align-items: stretch;
            flex-direction: column;
        }

        .wow-profile-page .offering-card-btn {
            min-height: 42px;
        }

        .wow-profile-page .offerings-grid--v4 {
            gap: 12px;
        }

        .wow-profile-page .offering-card-shell {
            flex-basis: 100%;
            min-width: 100%;
            max-width: 100%;
        }

        .wow-profile-page .location-list {
            max-height: 260px;
        }

        .wow-profile-page .map-box {
            min-height: 300px;
        }

        .wow-profile-page .side-stack {
            display: block;
            margin-top: 16px;
        }

        .wow-profile-page .side-card {
            margin-bottom: 16px;
        }

        .wow-profile-page .review-gate-card,
        .wow-profile-page .review-composer-head,
        .wow-profile-page .review-modal-head {
            grid-template-columns: 1fr;
        }

        .wow-profile-page .review-gate-card {
            display: grid;
        }

        .wow-profile-page .review-gate-actions {
            justify-content: flex-start;
        }

        .wow-profile-page .review-gate-actions .btn {
            width: 100%;
        }

        .wow-profile-page .review-composer-head {
            display: grid;
        }

        .wow-profile-page .review-switch-account,
        .wow-profile-page .review-submit {
            width: 100%;
            justify-self: stretch;
        }

        .wow-profile-page .review-star-input {
            width: 100%;
            justify-content: space-between;
        }

        .wow-profile-page .review-star-btn {
            flex: 1;
        }

        .wow-profile-page .review-auth-grid {
            grid-template-columns: 1fr;
        }

        .wow-profile-page .review-modal {
            left: 0;
            right: 0;
            top: auto;
            bottom: 0;
            width: auto;
            max-height: 92vh;
            overflow: auto;
            border-radius: var(--radius) var(--radius) 0 0;
            transform: none;
        }

        .wow-profile-page .review-modal-tabs {
            padding-left: 12px;
            padding-right: 12px;
        }

        .wow-profile-page .review-modal-panel {
            padding: 12px;
        }

    }

    .wow-profile-page .mapboxgl-map {
        font-family: inherit;
    }

    .wow-profile-page .mapboxgl-ctrl-logo,
    .wow-profile-page .mapboxgl-ctrl-attrib {
        transform: scale(0.82);
        transform-origin: bottom left;
    }

    .wow-profile-page .wow-map-marker {
        width: 34px;
        height: 34px;
        background: var(--green);
        border: 4px solid #ffffff;
        box-shadow: 0 10px 28px rgba(7, 29, 51, 0.24);
        transform: rotate(45deg);
        border-radius: 50% 50% 50% 0;
        position: relative;
    }

    .wow-profile-page .wow-map-marker::after {
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
</style>
@endpush

@section('content')
<main class="wow-profile-page practitioner-page">
    @include('partials.breadcrumbs', [
        'crumbs' => $breadcrumbs,
        'schemaUrl' => $seo['canonical'] ?? url()->current(),
        'mobileCurrent' => $displayName,
    ])

    <header class="profile-hero-wrap">
        <section class="profile-hero">
            @foreach($heroSlides as $slideIndex => $slideImage)
                <div class="hero-slide{{ $slideIndex === 0 ? ' is-active' : '' }}" style="background-image:url('{{ $slideImage }}');"></div>
            @endforeach

            <div class="hero-overlay"></div>
            <div class="hero-watermark">{!! nl2br(e($heroWatermark)) !!}</div>

            <div class="profile-hero-content">
                <div class="profile-hero-main">
                    <div class="kicker-row">
                        @foreach($heroPills as $pill)
                            <span class="pill">{{ $pill }}</span>
                        @endforeach
                    </div>

                    <div class="hero-profile-lockup">
                        @if($profilePicture)
                            <img class="hero-profile-avatar" src="{{ $profilePicture }}" alt="{{ $displayName }} profile photo">
                        @else
                            <div class="hero-profile-avatar-fallback" aria-hidden="true">{{ Str::upper(Str::substr($displayName, 0, 2)) }}</div>
                        @endif

                        <div>
                            <span class="hero-profile-label">{{ $heroBadge }}</span>
                            <h1>{{ $displayName }}</h1>
                            <span class="hero-profile-role">{{ $roleLabel }}</span>
                        </div>
                    </div>

                    <div class="hero-stat-grid">
                        @foreach($heroStats as $stat)
                            <div class="hero-stat">
                                <small>{{ $stat['label'] }}</small>
                                <strong>{{ $stat['value'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="profile-card profile-hero-panel" id="profileCard">
                    <div class="profile-panel-top">
                        <span class="profile-meta-badge">{{ $profileType === 'team' ? 'Team profile' : 'Practitioner profile' }}</span>
                        <h2>{{ $profileType === 'team' ? 'Meet ' . $displayName : 'Book with ' . $displayName }}</h2>
                        <p>
                            {{ $profileType === 'team'
                                ? 'Meet the team member behind this profile and learn more about their role at We Offer Wellness.'
                                : 'Browse available therapies, check locations and choose the session that works best for you.' }}
                        </p>
                    </div>

                    <div class="trust-row">
                        <span class="trust-chip">{{ $heroBadge }}</span>
                        <span class="trust-chip">{{ $insurance ? 'Insurance listed' : 'Insurance pending' }}</span>
                        <span class="trust-chip">{{ $reviewCount > 0 ? $reviewCount . ' review' . ($reviewCount === 1 ? '' : 's') : 'Be the first to review' }}</span>
                    </div>

                    <div class="profile-mini-stats">
                        <div class="profile-mini-stat">
                            <small>From</small>
                            <strong>{{ $priceSummary }}</strong>
                        </div>
                        <div class="profile-mini-stat">
                            <small>Locations</small>
                            <strong>{{ $locationSummary }}</strong>
                        </div>
                        <div class="profile-mini-stat">
                            <small>Status</small>
                            <strong>{{ $insurance ? 'Approved' : 'Listed' }}</strong>
                        </div>
                    </div>

                </aside>
            </div>
        </section>
    </header>

    <nav class="profile-nav" aria-label="Profile sections">
        <div class="profile-nav-inner">
            @foreach($navLinks as $navLink)
                <a class="profile-nav-link{{ $loop->first ? ' is-active' : '' }}" href="{{ $navLink['href'] }}">{{ $navLink['label'] }}</a>
            @endforeach
        </div>
    </nav>

    <div class="content-wrap">
        <div class="content-main">
            <section class="section" id="overview">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Quick profile</p>
                        <h2>{{ $displayName }} at a glance.</h2>
                        <p class="section-intro">
                            A simple overview of the profile details people usually check before choosing a practitioner.
                        </p>
                    </div>
                </div>

                <div class="quick-info-grid" aria-label="Practitioner quick profile">
                    @foreach($quickInfoCards as $card)
                        <article class="quick-info-card">
                            <span class="quick-info-icon" aria-hidden="true">
                                @switch($card['icon'])
                                    @case('focus')
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"></path><path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        @break
                                    @case('format')
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 21a8.8 8.8 0 1 0 0-17.6A8.8 8.8 0 0 0 12 21Z" stroke="currentColor" stroke-width="1.8"></path><path d="M4.2 10.5h4.2l2.1 2.1v2.7l2.1 2.1h2.7M19.4 8.4h-4.7l-1.9-1.9H9.6L8 4.9M12.8 20.8v-3.2l2.1-2.1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        @break
                                    @case('booking')
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M7 3v3M17 3v3M4.5 9h15M6.5 5h11A2.5 2.5 0 0 1 20 7.5v10A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-10A2.5 2.5 0 0 1 6.5 5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><path d="m9 15 2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        @break
                                    @case('location')
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-5.2 7-11.2A7 7 0 0 0 5 9.8C5 15.8 12 21 12 21Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path><path d="M12 12.2a2.4 2.4 0 1 0 0-4.8 2.4 2.4 0 0 0 0 4.8Z" stroke="currentColor" stroke-width="1.8"></path></svg>
                                        @break
                                    @default
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.8"></path><path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                @endswitch
                            </span>
                            <div>
                                <small>{{ $card['label'] }}</small>
                                <strong>{{ $card['value'] }}</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section" id="about">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">About {{ $displayName }}</p>
                        <h2>{{ $profileType === 'team' ? 'Learn more about this team member.' : 'Calm, supportive therapies for deep rest and reconnection.' }}</h2>
                        <p class="section-intro">
                            {{ $profileType === 'team'
                                ? 'Learn more about the role and background behind this public team profile.'
                                : 'Learn more about the practitioner’s approach, session style and the kind of support available.' }}
                        </p>
                    </div>
                </div>

                <div class="rich-text">
                    {!! $bioHtml !!}
                    @if(! empty($heroPills))
                        <h3>Highlights</h3>
                        <ul>
                            @foreach($heroPills as $pill)
                                <li>{{ $pill }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            @if($hasCredentialsSection)
                <section class="section" id="credentials">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">Qualifications & trust</p>
                            <h2>Qualifications, insurance and accreditations.</h2>
                            <p class="section-intro">
                                Key details are shown clearly so visitors can book with confidence.
                            </p>
                        </div>
                    </div>

                    <div class="credential-layout">
                        <article class="credential-card">
                            <h3>Qualifications</h3>
                            <div class="credential-tags">
                                @forelse($qualifications as $qualification)
                                    <span class="credential-tag">{{ $qualification }}</span>
                                @empty
                                    <span class="credential-tag">No qualifications listed yet</span>
                                @endforelse
                            </div>
                        </article>

                        <article class="credential-card">
                            <h3>Insurance</h3>
                            <div class="credential-tags">
                                @if($insurance)
                                    @if($insuranceProvider !== '')
                                        <span class="credential-tag">{{ $insuranceProvider }}</span>
                                    @endif
                                    @if($insuranceType !== '')
                                        <span class="credential-tag">{{ $insuranceType }}</span>
                                    @endif
                                    @if($coverageAmount !== '')
                                        <span class="credential-tag">{{ $coverageAmount }}</span>
                                    @endif
                                    @if($insuranceValidFrom !== '' || $insuranceValidUntil !== '')
                                        <span class="credential-tag">
                                            {{ trim($insuranceValidFrom !== '' ? $insuranceValidFrom : '') }}
                                            {{ $insuranceValidUntil !== '' ? ' - ' . $insuranceValidUntil : '' }}
                                        </span>
                                    @endif
                                    <span class="credential-tag">{{ $insuranceStatus !== '' ? Str::title($insuranceStatus) : 'Listed' }}</span>
                                    <span class="credential-tag">{{ $insuranceDocuments->count() }} document{{ $insuranceDocuments->count() === 1 ? '' : 's' }}</span>
                                @else
                                    <span class="credential-tag">No insurance listed yet</span>
                                @endif
                            </div>
                        </article>

                        <article class="credential-card">
                            <h3>Accreditations</h3>
                            <div class="credential-tags">
                                @forelse($accreditations as $accreditation)
                                    <span class="credential-tag">{{ $accreditation }}</span>
                                @empty
                                    <span class="credential-tag">No accreditations listed yet</span>
                                @endforelse
                            </div>
                        </article>
                    </div>
                </section>
            @endif

            <section class="section" id="offerings">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Practitioner offerings</p>
                        <h2>Choose a therapy from {{ $displayName }}.</h2>
                        <p class="section-intro">
                            Browse available therapies and choose the format, location and session style that feels right for you.
                        </p>
                    </div>
                </div>

                <div class="offering-toolbar">
                    <div class="filter-row" aria-label="Filter offerings">
                        <button class="filter-btn is-active" type="button" data-offering-filter="all">All</button>
                        <button class="filter-btn" type="button" data-offering-filter="in-person">In-person</button>
                        <button class="filter-btn" type="button" data-offering-filter="online">Online</button>
                        @if($hasSoundFilter)
                            <button class="filter-btn" type="button" data-offering-filter="sound">Sound therapy</button>
                        @endif
                    </div>
                    <div class="offering-toolbar-right">
                        <span class="offering-count" id="offeringCount">{{ $offeringCardProducts->count() }} offering{{ $offeringCardProducts->count() === 1 ? '' : 's' }}</span>
                        <div class="offering-carousel-controls" id="offeringCarouselControls" @if($offeringCardProducts->count() <= 2) hidden @endif>
                            <button class="carousel-arrow" type="button" id="offeringCarouselPrev" aria-label="Previous offerings" aria-controls="offeringsGrid" @if($offeringCardProducts->count() <= 2) disabled @endif>
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 18l-6-6 6-6"></path>
                                </svg>
                            </button>
                            <button class="carousel-arrow" type="button" id="offeringCarouselNext" aria-label="Next offerings" aria-controls="offeringsGrid" @if($offeringCardProducts->count() <= 2) disabled @endif>
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 6l6 6-6 6"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                @if($offeringCardProducts->isNotEmpty())
                    <div class="offerings-grid offerings-grid--v4" id="offeringsGrid">
                        @foreach($offeringCardProducts as $product)
                            @php
                                $locations = method_exists($product, 'getLocations') ? (array) $product->getLocations() : [];
                                $hasOnline = in_array('Online', $locations, true);
                                $physicalLocations = array_values(array_filter($locations, static fn ($location): bool => Str::of((string) $location)->lower()->contains('online') === false));
                                $categoryName = trim((string) (optional($product->category)->name ?? ''));
                                $title = trim((string) ($product->title ?? 'Wellness session'));
                                $summary = trim((string) ($product->summary ?? ''));
                                $filters = [];
                                $haystack = Str::lower(trim($title . ' ' . $summary . ' ' . $categoryName));

                                if ($hasOnline) {
                                    $filters[] = 'online';
                                }

                                if (! empty($physicalLocations)) {
                                    $filters[] = 'in-person';
                                }

                                if (Str::contains($haystack, ['sound', 'gong', 'bath'])) {
                                    $filters[] = 'sound';
                                }
                            @endphp
                            <div class="offering-card-shell" data-offering-card data-offering-tags="{{ implode(' ', array_values(array_unique($filters))) }}">
                                @include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null])
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        No live offerings are linked to this profile yet.
                    </div>
                @endif
            </section>

            <section class="section" id="locations">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Locations</p>
                        <h2>{{ $locationTitle }}</h2>
                        <p class="section-intro">{{ $locationIntro }}</p>
                    </div>
                </div>

                @if($hasPhysicalLocations || $hasOnlineLocation)
                    <div class="map-section-layout">
                        <div class="location-list" aria-label="Available locations">
                            @foreach($locationCards as $locationCard)
                                <button class="location-card{{ $locationCard['id'] === $selectedLocationId ? ' is-active' : '' }}" type="button" data-location-card data-location-id="{{ $locationCard['id'] }}" data-location-online="{{ ! empty($locationCard['online']) ? '1' : '0' }}">
                                    <h3>{{ $locationCard['label'] }}</h3>
                                    <p>{{ $locationCard['address'] }}</p>
                                </button>
                            @endforeach
                        </div>

                        <div class="map-box {{ $selectedLocationType === 'online' ? 'is-online-mode' : 'is-map-mode' }}" id="desktopMapBox">
                            @if($hasPhysicalLocations)
                                <div class="mapbox-map" id="desktopMap"></div>
                                <div class="map-map-overlay">
                                    <div>
                                        <strong>Studio location selected</strong>
                                        <span>Use the location list to switch between venues or move back to the online session.</span>
                                    </div>
                                </div>
                            @endif

                            @if($hasOnlineLocation)
                                <video class="map-online-video" id="desktopOnlineVideo" autoplay muted loop playsinline poster="{{ $videoPoster }}">
                                    <source src="{{ $videoSource }}" type="video/mp4">
                                </video>
                                <div class="map-online-overlay">
                                    <div>
                                        <strong>{{ $onlineOverlayTitle }}</strong>
                                        <span>{{ $onlineOverlayCopy }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="map-empty-state">
                        Location details will appear here once this profile includes studio or online availability.
                    </div>
                @endif
            </section>

            <section class="section" id="reviews">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Reviews</p>
                        <h2>Customer reviews.</h2>
                        <p class="section-intro">
                            Feedback from customers who have booked sessions with this practitioner.
                        </p>
                    </div>
                </div>

                @if(session('status'))
                    <div class="review-success-alert">{{ session('status') }}</div>
                @endif

                @if($canLeaveReview)
                    @auth
                        <div class="review-composer-card" id="reviewComposerCard" aria-live="polite">
                            <div class="review-composer-head">
                                <div>
                                    <p class="eyebrow">Write a review</p>
                                    <h3>{{ $activeUserReview ? 'Update your review.' : 'Share your experience.' }}</h3>
                                    <p>
                                        {{ $activeUserReview
                                            ? 'You can update the review you already left for this practitioner.'
                                            : 'Your review will be linked to your client account.' }}
                                    </p>
                                </div>
                            </div>

                            <form class="review-write-form" id="reviewComposerForm" action="{{ route('practioner.reviews.store', ['slug' => $slug]) }}" method="post">
                                @csrf

                                <div class="review-rating-field">
                                    <label>Rating</label>
                                    <input type="hidden" name="rating" id="reviewRatingInput" value="{{ old('rating', $activeReviewRating) }}">
                                    <div class="review-star-input" id="reviewStarInput" aria-label="Choose rating">
                                        @for($rating = 1; $rating <= 5; $rating++)
                                            <button class="review-star-btn{{ $rating <= old('rating', $activeReviewRating) ? ' is-active' : '' }}" type="button" data-rating="{{ $rating }}" aria-label="{{ $rating }} star{{ $rating === 1 ? '' : 's' }}">★</button>
                                        @endfor
                                    </div>
                                    @error('rating')
                                        <div class="review-auth-alert">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="review-field">
                                    <label for="reviewTitle">Review title</label>
                                    <input id="reviewTitle" name="review_title" type="text" value="{{ old('review_title', $activeUserReview?->title ?? '') }}" placeholder="Optional headline">
                                    @error('review_title')
                                        <div class="review-auth-alert">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="review-field">
                                    <label for="reviewBody">Your review</label>
                                    <textarea id="reviewBody" name="review_text" rows="5" placeholder="Share what your session was like..." required>{{ old('review_text', $activeReviewText) }}</textarea>
                                    @error('review_text')
                                        <div class="review-auth-alert">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button class="btn btn-primary review-submit" type="submit">{{ $activeUserReview ? 'Update review' : 'Post review' }}</button>

                                <p class="review-privacy-note">
                                    Your review will appear under your client account.
                                </p>
                            </form>
                        </div>
                    @endauth

                    @guest
                        <div class="review-gate-card">
                            <div class="review-gate-copy">
                                <p class="eyebrow">Leave a review</p>
                                <h3>Login or create an account to leave a review.</h3>
                                <p>
                                    Reviews are linked to client accounts so feedback stays trusted for people choosing a practitioner.
                                </p>
                            </div>

                            <div class="review-gate-actions">
                                <button class="btn btn-secondary" type="button" data-review-auth-open="login">Login</button>
                                <button class="btn btn-primary" type="button" data-review-auth-open="register">Create account</button>
                            </div>
                        </div>
                    @endguest
                @endif

                @if($reviewItems->isNotEmpty())
                    <div class="review-grid">
                        @foreach($reviewItems->take(3) as $review)
                            @php
                                $reviewerName = trim((string) (data_get($review, 'user.public_display_name') ?: $review->reviewer ?: 'Verified client'));
                                if ($reviewerName === '') {
                                    $reviewerName = 'Verified client';
                                }
                                $reviewLabel = filled($review->product?->title ?? null)
                                    ? $review->product->title
                                    : 'Practitioner review';
                            @endphp
                            <article class="review-card">
                                <div class="review-stars">{{ str_repeat('★', max(0, min(5, (int) round((float) ($review->rating ?? 0))))) }}</div>
                                <p>{{ trim((string) ($review->review_text ?? '')) !== '' ? $review->review_text : 'A customer rated this practitioner.' }}</p>
                                <footer>
                                    {{ $reviewerName }} · {{ $reviewLabel }}
                                    @if(filled($review->created_at ?? null))
                                        · {{ Carbon::parse($review->created_at)->format('j M Y') }}
                                    @endif
                                </footer>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        No public reviews have been added yet.
                    </div>
                @endif
            </section>
        </div>

        <aside class="side-stack">
            <div class="side-card">
                <h3>Profile snapshot</h3>
                <div class="mini-list">
                    @foreach($profileSnapshot as $row)
                        <div class="mini-row"><span>{{ $row['label'] }}</span><strong>{{ $row['value'] }}</strong></div>
                    @endforeach
                </div>
            </div>

            <div class="side-card">
                <h3>Availability guide</h3>
                <div class="availability-list">
                    @foreach($availabilityGuide as $row)
                        <div class="availability-day"><span>{{ $row['label'] }}</span><strong>{{ $row['value'] }}</strong></div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

    @if($canLeaveReview)
        <div class="review-modal-backdrop{{ $showReviewAuthModal ? ' is-open' : '' }}" id="reviewAuthBackdrop"></div>

        <section class="review-modal{{ $showReviewAuthModal ? ' is-open' : '' }}" id="reviewAuthModal" role="dialog" aria-label="Login or create an account to leave a review" aria-modal="true">
            <div class="review-modal-head">
                <div>
                    <p class="eyebrow">Leave a review</p>
                    <h3>Login or create an account.</h3>
                </div>
                <button class="review-modal-close" type="button" id="closeReviewAuthModal" aria-label="Close review authentication modal">×</button>
            </div>

            <div class="review-modal-tabs" role="tablist" aria-label="Review authentication options">
                <button class="review-modal-tab{{ $reviewAuthMode === 'login' ? ' is-active' : '' }}" type="button" data-review-auth-tab="login" role="tab" aria-selected="{{ $reviewAuthMode === 'login' ? 'true' : 'false' }}">Login</button>
                <button class="review-modal-tab{{ $reviewAuthMode === 'register' ? ' is-active' : '' }}" type="button" data-review-auth-tab="register" role="tab" aria-selected="{{ $reviewAuthMode === 'register' ? 'true' : 'false' }}">Create account</button>
            </div>

            <div class="review-modal-panel{{ $reviewAuthMode === 'login' ? ' is-active' : '' }}" data-review-auth-panel="login" role="tabpanel">
                <p>Use your existing client account to leave a review for this practitioner.</p>

                <form class="review-auth-form" method="POST" action="{{ route('login') }}">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ $reviewRedirectUrl }}">
                    <input type="hidden" name="auth_mode" value="login">

                    <div class="review-auth-field">
                        <label for="reviewLoginEmail">Email</label>
                        <input id="reviewLoginEmail" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                        @error('email')
                            <div class="review-auth-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="review-auth-field">
                        <label for="reviewLoginPassword">Password</label>
                        <input id="reviewLoginPassword" name="password" type="password" autocomplete="current-password" required>
                        @error('password')
                            <div class="review-auth-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="review-auth-check">
                        <input type="checkbox" name="remember">
                        <span>Keep me signed in on this device.</span>
                    </label>

                    <button class="btn btn-primary review-submit" type="submit">Login</button>
                </form>
            </div>

            <div class="review-modal-panel{{ $reviewAuthMode === 'register' ? ' is-active' : '' }}" data-review-auth-panel="register" role="tabpanel">
                <p>Create a client account, then you can leave a review for this practitioner.</p>

                <form class="review-auth-form" method="POST" action="{{ route('register') }}">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ $reviewRedirectUrl }}">
                    <input type="hidden" name="auth_mode" value="register">

                    <div class="review-auth-grid">
                        <div class="review-auth-field">
                            <label for="reviewFirstName">First name</label>
                            <input id="reviewFirstName" name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name" required>
                            @error('first_name')
                                <div class="review-auth-alert">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="review-auth-field">
                            <label for="reviewLastName">Last name</label>
                            <input id="reviewLastName" name="last_name" type="text" value="{{ old('last_name') }}" autocomplete="family-name" required>
                            @error('last_name')
                                <div class="review-auth-alert">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="review-auth-field">
                        <label for="reviewRegisterEmail">Email</label>
                        <input id="reviewRegisterEmail" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                        @error('email')
                            <div class="review-auth-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="review-auth-grid">
                        <div class="review-auth-field">
                            <label for="reviewRegisterPassword">Password</label>
                            <input id="reviewRegisterPassword" name="password" type="password" autocomplete="new-password" required>
                            @error('password')
                                <div class="review-auth-alert">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="review-auth-field">
                            <label for="reviewRegisterPasswordConfirmation">Confirm password</label>
                            <input id="reviewRegisterPasswordConfirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        </div>
                    </div>

                    <label class="review-auth-check">
                        <input type="checkbox" name="terms" {{ old('terms') ? 'checked' : '' }} required>
                        <span>I agree to the <a href="/terms">Terms</a> and <a href="/privacy">Privacy Policy</a>.</span>
                    </label>
                    @error('terms')
                        <div class="review-auth-alert">{{ $message }}</div>
                    @enderror

                    @if($recaptchaEnabled)
                        <div class="review-auth-field review-auth-field--full">
                            <label>Security check</label>
                            <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div>
                            @error('g-recaptcha-response')
                                <div class="review-auth-alert">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <button class="btn btn-primary review-submit" type="submit">Create account</button>
                </form>
            </div>
        </section>
    @endif

</main>
@endsection

@push('scripts')
@if($hasPhysicalLocations)
<script src="https://api.mapbox.com/mapbox-gl-js/v3.5.1/mapbox-gl.js"></script>
@endif
@if($recaptchaEnabled)
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
<script>
(function () {
    const heroSlides = document.querySelectorAll('.hero-slide');
    if (heroSlides.length > 1) {
        let heroSlideIndex = 0;
        setInterval(() => {
            heroSlides[heroSlideIndex].classList.remove('is-active');
            heroSlideIndex = (heroSlideIndex + 1) % heroSlides.length;
            heroSlides[heroSlideIndex].classList.add('is-active');
        }, 4500);
    }

    const filterButtons = document.querySelectorAll('[data-offering-filter]');
    const offeringCards = document.querySelectorAll('[data-offering-card]');
    const offeringCount = document.getElementById('offeringCount');
    const offeringsGrid = document.getElementById('offeringsGrid');
    const offeringCarouselControls = document.getElementById('offeringCarouselControls');
    const offeringCarouselPrev = document.getElementById('offeringCarouselPrev');
    const offeringCarouselNext = document.getElementById('offeringCarouselNext');

    let offeringCarouselFrame = null;

    function getVisibleOfferingCards() {
        return Array.from(offeringCards).filter(card => ! card.hidden);
    }

    function updateOfferingCarouselControls() {
        if (! offeringsGrid || ! offeringCarouselControls || ! offeringCarouselPrev || ! offeringCarouselNext) {
            return;
        }

        const visibleCount = getVisibleOfferingCards().length;
        const maxScrollLeft = Math.max(0, offeringsGrid.scrollWidth - offeringsGrid.clientWidth);
        const hasOverflow = visibleCount > 2 && maxScrollLeft > 1;

        if (! hasOverflow) {
            offeringCarouselControls.hidden = true;
            offeringCarouselPrev.disabled = true;
            offeringCarouselNext.disabled = true;
            offeringsGrid.scrollTo({ left: 0, behavior: 'auto' });
            return;
        }

        offeringCarouselControls.hidden = false;
        offeringCarouselPrev.disabled = offeringsGrid.scrollLeft <= 1;
        offeringCarouselNext.disabled = offeringsGrid.scrollLeft >= (maxScrollLeft - 1);
    }

    function scheduleOfferingCarouselUpdate(resetScroll = false) {
        if (! offeringsGrid || ! offeringCarouselControls || ! offeringCarouselPrev || ! offeringCarouselNext) {
            return;
        }

        if (resetScroll) {
            offeringsGrid.scrollTo({ left: 0, behavior: 'auto' });
        }

        if (offeringCarouselFrame !== null) {
            cancelAnimationFrame(offeringCarouselFrame);
        }

        offeringCarouselFrame = requestAnimationFrame(() => {
            offeringCarouselFrame = null;
            updateOfferingCarouselControls();
        });
    }

    function scrollOfferings(direction) {
        if (! offeringsGrid) {
            return;
        }

        offeringsGrid.scrollBy({
            left: direction * offeringsGrid.clientWidth,
            behavior: 'smooth',
        });
    }

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const filter = button.dataset.offeringFilter || 'all';

            filterButtons.forEach(item => item.classList.remove('is-active'));
            button.classList.add('is-active');

            let visibleCount = 0;

            offeringCards.forEach(card => {
                const tags = (card.dataset.offeringTags || '').split(/\s+/).filter(Boolean);
                const shouldShow = filter === 'all' || tags.includes(filter);

                card.hidden = ! shouldShow;
                if (shouldShow) {
                    visibleCount += 1;
                }
            });

            if (offeringCount) {
                offeringCount.textContent = `${visibleCount} offering${visibleCount === 1 ? '' : 's'}`;
            }

            scheduleOfferingCarouselUpdate(true);
        });
    });

    if (offeringCarouselPrev) {
        offeringCarouselPrev.addEventListener('click', () => {
            scrollOfferings(-1);
        });
    }

    if (offeringCarouselNext) {
        offeringCarouselNext.addEventListener('click', () => {
            scrollOfferings(1);
        });
    }

    if (offeringsGrid) {
        offeringsGrid.addEventListener('scroll', () => {
            scheduleOfferingCarouselUpdate();
        }, { passive: true });
    }

    scheduleOfferingCarouselUpdate();

    const mobileProfileBar = document.getElementById('mobileProfileBar');
    const profileCard = document.getElementById('profileCard');

    if (mobileProfileBar && profileCard && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            const entry = entries[0];
            const shouldShow = window.innerWidth <= 760 && ! entry.isIntersecting;

            mobileProfileBar.classList.toggle('is-visible', shouldShow);
            document.body.classList.toggle('mobile-bar-visible', shouldShow);
        }, {
            threshold: 0.1,
        });

        observer.observe(profileCard);
    }

    const profileNavLinks = document.querySelectorAll('.profile-nav-link');
    const navSections = [...profileNavLinks]
        .map(link => document.querySelector(link.getAttribute('href')))
        .filter(Boolean);

    if (navSections.length && 'IntersectionObserver' in window) {
        const navObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (! entry.isIntersecting) {
                    return;
                }

                profileNavLinks.forEach(link => {
                    link.classList.toggle('is-active', link.getAttribute('href') === `#${entry.target.id}`);
                });
            });
        }, {
            rootMargin: '-35% 0px -55% 0px',
            threshold: 0,
        });

        navSections.forEach(section => navObserver.observe(section));
    }

    const reviewAuthModal = document.getElementById('reviewAuthModal');
    const reviewAuthBackdrop = document.getElementById('reviewAuthBackdrop');
    const reviewAuthOpenButtons = document.querySelectorAll('[data-review-auth-open]');
    const reviewAuthTabs = document.querySelectorAll('[data-review-auth-tab]');
    const reviewAuthPanels = document.querySelectorAll('[data-review-auth-panel]');
    const closeReviewAuthModal = document.getElementById('closeReviewAuthModal');
    const reviewComposerCard = document.getElementById('reviewComposerCard');
    const reviewStarInput = document.getElementById('reviewStarInput');
    const reviewRatingInput = document.getElementById('reviewRatingInput');
    const reviewShouldOpenModal = @json($showReviewAuthModal);
    const reviewInitialMode = @json($reviewAuthMode);
    const reviewShouldScroll = @json($canLeaveReview && auth()->check() && (request()->boolean('review') || $errors->has('rating') || $errors->has('review_text') || $errors->has('review_title')));
    let selectedReviewRating = Number(reviewRatingInput?.value || 5);

    function setReviewAuthMode(mode) {
        const nextMode = mode === 'register' ? 'register' : 'login';

        reviewAuthTabs.forEach(tab => {
            const isActive = tab.dataset.reviewAuthTab === nextMode;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        reviewAuthPanels.forEach(panel => {
            panel.classList.toggle('is-active', panel.dataset.reviewAuthPanel === nextMode);
        });

        if (reviewAuthModal) {
            reviewAuthModal.dataset.activeMode = nextMode;
        }
    }

    function focusReviewAuthField(mode) {
        if (!reviewAuthModal) {
            return;
        }

        const panel = reviewAuthModal.querySelector(`[data-review-auth-panel="${mode}"]`);
        if (!panel) {
            return;
        }

        const firstInput = panel.querySelector('input:not([type="hidden"])');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 80);
        }
    }

    function openReviewAuthModal(mode = reviewInitialMode) {
        if (!reviewAuthModal) {
            return;
        }

        const nextMode = mode === 'register' ? 'register' : 'login';
        setReviewAuthMode(nextMode);
        reviewAuthModal.classList.add('is-open');

        if (reviewAuthBackdrop) {
            reviewAuthBackdrop.classList.add('is-open');
        }

        document.body.style.overflow = 'hidden';
        focusReviewAuthField(nextMode);
    }

    function closeReviewAuthModalFn() {
        if (!reviewAuthModal) {
            return;
        }

        reviewAuthModal.classList.remove('is-open');

        if (reviewAuthBackdrop) {
            reviewAuthBackdrop.classList.remove('is-open');
        }

        document.body.style.overflow = '';
    }

    function updateReviewStars(rating) {
        const nextRating = Math.max(1, Math.min(5, Number(rating) || 5));
        selectedReviewRating = nextRating;

        if (reviewRatingInput) {
            reviewRatingInput.value = String(nextRating);
        }

        document.querySelectorAll('.review-star-btn').forEach(button => {
            button.classList.toggle('is-active', Number(button.dataset.rating) <= selectedReviewRating);
        });
    }

    reviewAuthOpenButtons.forEach(button => {
        button.addEventListener('click', () => {
            openReviewAuthModal(button.dataset.reviewAuthOpen || reviewInitialMode);
        });
    });

    reviewAuthTabs.forEach(button => {
        button.addEventListener('click', () => {
            setReviewAuthMode(button.dataset.reviewAuthTab || 'login');
            focusReviewAuthField(button.dataset.reviewAuthTab || 'login');
        });
    });

    if (closeReviewAuthModal) {
        closeReviewAuthModal.addEventListener('click', closeReviewAuthModalFn);
    }

    if (reviewAuthBackdrop) {
        reviewAuthBackdrop.addEventListener('click', closeReviewAuthModalFn);
    }

    if (reviewStarInput) {
        reviewStarInput.addEventListener('click', event => {
            const button = event.target.closest('.review-star-btn');

            if (!button) {
                return;
            }

            updateReviewStars(Number(button.dataset.rating));
        });

        updateReviewStars(selectedReviewRating);
    }

    if (reviewShouldOpenModal) {
        openReviewAuthModal(reviewInitialMode);
    }

    if (reviewComposerCard && reviewShouldScroll) {
        setTimeout(() => {
            reviewComposerCard.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }, 180);
    }

    const locationButtons = document.querySelectorAll('[data-location-card]');
    const mapBox = document.getElementById('desktopMapBox');
    const onlineVideo = document.getElementById('desktopOnlineVideo');
    const mapContainer = document.getElementById('desktopMap');
    const hasPhysicalLocations = @json($hasPhysicalLocations);
    const hasOnlineLocation = @json($hasOnlineLocation);
    const onlineOnly = @json($locationMode === 'online-only');
    const selectedLocationId = @json($selectedLocationId);
    const locationData = @json($physicalLocationData);
    const mapToken = @json(config('services.mapbox.token'));
    let profileMap = null;

    function setSelectedLocation(locationId) {
        locationButtons.forEach(button => {
            button.classList.toggle('is-active', button.dataset.locationId === locationId);
        });
    }

    function setMode(mode) {
        if (! mapBox) {
            return;
        }

        mapBox.classList.toggle('is-online-mode', mode === 'online');
        mapBox.classList.toggle('is-map-mode', mode !== 'online');

        if (onlineVideo) {
            if (mode === 'online') {
                onlineVideo.play().catch(() => {});
            } else {
                onlineVideo.pause();
                onlineVideo.currentTime = 0;
            }
        }
    }

    function createMarker() {
        const markerEl = document.createElement('div');
        markerEl.className = 'wow-map-marker';
        return markerEl;
    }

    function initMap() {
        if (! hasPhysicalLocations || ! mapContainer || ! window.mapboxgl || ! mapToken) {
            return;
        }

        mapboxgl.accessToken = mapToken;

        const first = locationData[0] || null;
        const center = first && Number.isFinite(Number(first.lng)) && Number.isFinite(Number(first.lat))
            ? [Number(first.lng), Number(first.lat)]
            : [-0.146, 51.391];

        profileMap = new mapboxgl.Map({
            container: 'desktopMap',
            style: 'mapbox://styles/mapbox/standard',
            center,
            zoom: 10.8,
            pitch: 52,
            bearing: -18,
            antialias: true
        });

        profileMap.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');

        profileMap.on('style.load', () => {
            try {
                profileMap.setConfigProperty('basemap', 'lightPreset', 'day');
            } catch (_error) {}

            locationData.forEach(location => {
                if (! Number.isFinite(Number(location.lat)) || ! Number.isFinite(Number(location.lng))) {
                    return;
                }

                new mapboxgl.Marker({
                    element: createMarker(),
                    anchor: 'bottom'
                })
                    .setLngLat([Number(location.lng), Number(location.lat)])
                    .addTo(profileMap);
            });
        });
    }

    function focusMap(locationId) {
        if (! profileMap || ! locationId) {
            return;
        }

        const location = locationData.find(item => item.id === locationId);
        if (! location || ! Number.isFinite(Number(location.lat)) || ! Number.isFinite(Number(location.lng))) {
            return;
        }

        profileMap.flyTo({
            center: [Number(location.lng), Number(location.lat)],
            zoom: 13.4,
            pitch: 52,
            bearing: -18,
            duration: 900
        });
    }

    locationButtons.forEach(button => {
        button.addEventListener('click', () => {
            const locationId = button.dataset.locationId || '';
            const isOnline = button.dataset.locationOnline === '1' || locationId === 'loc-online';

            setSelectedLocation(locationId);
            setMode(isOnline ? 'online' : 'map');

            if (! isOnline) {
                focusMap(locationId);
            }
        });
    });

    if (hasPhysicalLocations) {
        initMap();

        if (onlineOnly) {
            setMode('online');
        } else {
            setMode('map');
            if (selectedLocationId && selectedLocationId !== 'loc-online') {
                focusMap(selectedLocationId);
            }
        }
    } else if (hasOnlineLocation) {
        setMode('online');
    }

    window.addEventListener('resize', () => {
        if (profileMap) {
            profileMap.resize();
        }

        scheduleOfferingCarouselUpdate();

        if (window.innerWidth > 760 && mobileProfileBar) {
            mobileProfileBar.classList.remove('is-visible');
            document.body.classList.remove('mobile-bar-visible');
        }
    });
})();
</script>
@endpush
