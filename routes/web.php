<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\StoreProductsController;
use Inertia\Inertia;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OfferingController;
use App\Http\Controllers\ScheduleDiscoveryController;
use App\Http\Controllers\HelpController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Review;
use App\Http\Controllers\Api\V3SubscriberController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutResultController;
use App\Http\Controllers\SafetyContraindicationsController;
use App\Http\Controllers\HelpCentreController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\NeedsController;
use App\Http\Controllers\TherapiesController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\SeoLandingController;
use App\Http\Controllers\OnlineController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\OnlineNearMeController;
use App\Http\Controllers\SeoMoneyPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProvidersController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CorporateController;
use App\Http\Controllers\GiftCardsController;
use App\Http\Controllers\OfferingsHubController;
use App\Http\Controllers\StaticPagesController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\HelpPagesController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\RedirectsController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\AiDiscoveryController;
use App\Http\Controllers\SubscriberController;
use App\Services\LocationCatalogService;
use App\Services\IndexNowService;

// Direct preview route for the branded not-found page. Keep this before the
// catch-all SEO routes so it can never be treated as a page slug.
Route::view('/404', 'errors.404');

Route::get('/favicon.ico', function () {
    return redirect()->route('favicon', [], 308);
});

Route::get('/favicon.png', function () {
    $image = Http::timeout(5)->get('https://studio.weofferwellness.co.uk/storage/uploads/images/a4a125ff-e25a-48e3-bdf2-12af9182cdce.png');
    abort_unless($image->successful(), 404);

    // Google accepts square favicon assets at a multiple of 48px. Normalize
    // the upstream brand mark once per response so browser and crawler
    // consumers receive the same valid 192px favicon.
    $source = @imagecreatefromstring($image->body());
    if ($source !== false) {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $canvas = imagecreatetruecolor(192, 192);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);

        $scale = min(192 / $sourceWidth, 192 / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $x = (int) floor((192 - $width) / 2);
        $y = (int) floor((192 - $height) / 2);
        imagecopyresampled($canvas, $source, $x, $y, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        ob_start();
        imagepng($canvas, null, 9);
        $body = ob_get_clean();
        imagedestroy($canvas);
        imagedestroy($source);
    } else {
        $body = $image->body();
    }

    return response($body, 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400, s-maxage=86400',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->name('favicon');

Route::get('/', [HomeController::class, 'index']);

Route::prefix('subscribe')->group(function () {
    Route::get('/confirm/{token}', [SubscriberController::class, 'confirm'])->name('subscribe.confirm');
    Route::get('/preferences/{token}', [SubscriberController::class, 'preferences'])->name('subscribe.preferences');
    Route::post('/preferences/{token}', [SubscriberController::class, 'updatePreferences'])->name('subscribe.preferences.update');
    Route::get('/unsubscribe/{token}', [SubscriberController::class, 'unsubscribe'])->name('subscribe.unsubscribe');
    Route::get('/resubscribe/{token}', [SubscriberController::class, 'resubscribe'])->name('subscribe.resubscribe');
});

$locationCatalog = app(LocationCatalogService::class)->load();
$countrySlugs = array_values(array_unique(array_filter(array_map(
    static fn ($country): string => trim((string) data_get($country, 'slug', '')),
    (array) data_get($locationCatalog, 'countries', [])
))));
$countryPattern = $countrySlugs !== []
    ? implode('|', array_map(static fn (string $slug): string => preg_quote($slug, '~'), $countrySlugs))
    : 'united-kingdom';
$seoSlugPattern = '[A-Za-z0-9][A-Za-z0-9\-]*';

// Online & Near Me hub
Route::get('/online-near-me', [LocationController::class, 'onlineNearMe'])->name('onlineNearMe.index');
Route::get('/schedule-discovery', [ScheduleDiscoveryController::class, 'index'])->name('schedule-discovery.index');

$wellnessEventTopicRoutes = [
    'wellness-events' => [
        'timeframes' => ['this-week', 'this-weekend', 'today', 'tomorrow', 'next-week', 'online'],
        'locations' => ['kent', 'london'],
    ],
    'sound-baths' => [
        'timeframes' => ['this-week', 'this-weekend'],
    ],
    'meditation-events' => [
        'timeframes' => ['this-week', 'this-weekend'],
    ],
    'breathwork-events' => [
        'timeframes' => ['this-week', 'this-weekend'],
    ],
    'yoga-workshops' => [
        'timeframes' => ['this-week', 'this-weekend'],
    ],
];

foreach ($wellnessEventTopicRoutes as $topic => $config) {
    foreach ($config['timeframes'] as $timeframe) {
        Route::get("/{$topic}/{$timeframe}", [ScheduleDiscoveryController::class, 'show'])
            ->defaults('topic', $topic)
            ->defaults('timeframe', $timeframe)
            ->name("wellness-events.{$topic}.{$timeframe}");
    }

    foreach (($config['locations'] ?? []) as $location) {
        foreach (['this-week', 'this-weekend'] as $timeframe) {
            Route::get("/{$topic}/{$timeframe}/{$location}", [ScheduleDiscoveryController::class, 'show'])
                ->defaults('topic', $topic)
                ->defaults('timeframe', $timeframe)
                ->defaults('location', $location)
                ->name("wellness-events.{$topic}.{$timeframe}.{$location}");
        }
    }
}

/** Guides */
Route::get('/guides', [GuideController::class, 'index'])->name('guides.index');
Route::get('/{format}/guides', [GuideController::class, 'format'])
    ->where('format', 'therapies|classes|events|workshops|retreats')
    ->name('guides.format');
Route::get('/{format}/{modality}/guides', [GuideController::class, 'modality'])
    ->where([
        'format' => 'therapies|classes|events|workshops|retreats',
        'modality' => $seoSlugPattern,
    ])
    ->name('guides.modality');
Route::get('/{format}/{modality}/guides/{guide}', [GuideController::class, 'show'])
    ->where([
        'format' => 'therapies|classes|events|workshops|retreats',
        'modality' => $seoSlugPattern,
        'guide' => $seoSlugPattern,
    ])
    ->name('guides.show');
Route::permanentRedirect('/what-is-reiki', '/therapies/reiki/guides/what-is-reiki');
Route::permanentRedirect('/reiki-benefits', '/therapies/reiki/guides/what-is-reiki');
Route::permanentRedirect('/what-is-reflexology', '/therapies/reflexology/guides/what-is-reflexology');
Route::permanentRedirect('/yoga-for-back-pain', '/classes/yoga/guides/yoga-for-back-pain');

/** By Need */
Route::get('/needs', [NeedsController::class, 'index'])->name('needs.index');
Route::get('/needs/{slug}', [NeedsController::class, 'show'])
    ->where('slug', $seoSlugPattern)
    ->name('needs.show');

/** Therapies */
Route::get('/therapies', [TypeController::class, 'therapies'])->name('therapies.index');
Route::get('/therapies/{slug}', [CategoryController::class, 'therapy'])
    ->where('slug', $seoSlugPattern)
    ->name('therapies.show');

/** Events & Workshops */
Route::get('/events-workshops/{slug}', [OfferingController::class, 'showEvent'])
    ->where('slug', $seoSlugPattern)
    ->name('events-workshops.show');

/** Online */
Route::get('/online', [LocationController::class, 'online'])->name('online.index');
Route::get('/online/{modality}', [LocationController::class, 'onlineModality'])
    ->where('modality', $seoSlugPattern)
    ->name('online.modality');
Route::get('/{format}/{modality}/{country}/{county?}/{town?}', [LocationController::class, 'modalityLocation'])
    ->where([
        'format' => '(therapies|classes|events|workshops|retreats)',
        'modality' => $seoSlugPattern,
        'country' => $countryPattern,
        'county' => $seoSlugPattern,
        'town' => $seoSlugPattern,
    ])
    ->name('seo-money.structured-near-me');
Route::get('/holistic-therapies-uk', [TypeController::class, 'holisticTherapiesUk'])
    ->defaults('slug', 'holistic-therapies-uk')
    ->name('seo-money.holistic-therapies-uk');
Route::get('/{category}-near-me/{country?}/{county?}/{town?}', [LocationController::class, 'categoryNearMe'])
    ->where([
        'category' => $seoSlugPattern,
        'country' => $seoSlugPattern,
        'county' => $seoSlugPattern,
        'town' => $seoSlugPattern,
    ])
    ->name('seo-money.near-me');

/** Locations + Near Me */
Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('/locations/{country}/{county?}/{town?}', [LocationController::class, 'hierarchy'])
    ->where([
        'country' => 'united-kingdom',
        'county' => '[A-Za-z][A-Za-z0-9\-]*',
        'town' => '[A-Za-z][A-Za-z0-9\-]*',
    ])
    ->name('locations.hierarchy');
Route::get('/locations/{slug}', [LocationController::class, 'show'])
    ->where('slug', '[A-Za-z][A-Za-z0-9\-]*')
    ->name('locations.show');
Route::get('/near-me', [LocationController::class, 'nearMe'])->name('nearMe');
Route::get('/products', [OfferingController::class, 'indexLegacy'])->name('store.products.index');
Route::get('/product/{category}/{slug}', [OfferingController::class, 'showLegacyByCategory'])
    ->where(['category' => '[^/]+', 'slug' => '[^/]+'])
    ->name('store.product.show');
Route::post('/product/{category}/{slug}/reviews', [OfferingController::class, 'storeReview'])
    ->middleware('auth')
    ->where(['category' => '[^/]+', 'slug' => '[^/]+'])
    ->name('store.product.reviews.store');
Route::get('/products/{slug}', [OfferingController::class, 'showLegacy'])->where('slug', '[^/]+')->name('store.products.show');
Route::get('/{prefix}/custom/{pixel}/sandbox/modern/products/{handle}', [RedirectsController::class, 'shopifyProductSandbox'])
    ->where([
        'prefix' => '[^/]+',
        'pixel' => '[^/]+',
        'handle' => '[^/]+',
    ]);
Route::get('/collections/{slug?}', [RedirectsController::class, 'shopifyCollection'])
    ->where('slug', '[^/]*');
Route::get('/pages/{path}', [RedirectsController::class, 'shopifyPage'])
    ->where('path', '.*');
Route::get('/account/login', [RedirectsController::class, 'shopifyAccountLogin']);
// Misc redirects are handled by the backend redirect table.

// V3 holding page
Route::get('/v3', function () {
    return Inertia::render('V3/Holding', [
        'meta' => [
            'title' => 'We Offer Wellness v3 — Coming Soon',
            'description' => 'We’re at the tail end of v3. Join for giveaways, discounts, and launch access.',
            'canonical' => url('/v3'),
        ],
    ]);
})->name('v3.holding');

Route::get('/search', [SearchController::class, 'index'])->name('search');
// Stripe Checkout session (web POST with CSRF)
Route::get('/checkout/session', [CartController::class, 'page'])->name('checkout.session.get');
Route::post('/checkout/session', [CheckoutController::class, 'createSession'])->name('checkout.session');

Route::get('/dashboard', [CustomerAccountController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::prefix('account')->group(function () {
        Route::get('/', [CustomerAccountController::class, 'dashboard'])->name('account.dashboard');
        Route::get('/orders', [CustomerAccountController::class, 'orders'])->name('account.orders');
        Route::get('/orders/{order}', [CustomerAccountController::class, 'showOrder'])
            ->whereNumber('order')
            ->name('account.orders.show');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Legacy /profile URL support.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.legacy');

    // Admin utilities: backup and clear Pages with confirmation token
    Route::get('/admin/pages/backup', [\App\Http\Controllers\Admin\PagesAdminController::class, 'backup'])
        ->name('admin.pages.backup');
    Route::post('/admin/pages/clear', [\App\Http\Controllers\Admin\PagesAdminController::class, 'clear'])
        ->name('admin.pages.clear');
});

require __DIR__.'/auth.php';

// Geolocation persistence (cookies)
Route::post('/api/geo', [\App\Http\Controllers\GeoController::class, 'update']);

// ------------------------------------------------------------------
// Landing/Hubs and Universal Pattern pages
// ------------------------------------------------------------------

// Canonical hub pages
Route::get('/events', [TypeController::class, 'events'])->name('events.index');
Route::get('/events/{slug}', [OfferingController::class, 'showEvent'])
    ->where('slug', '[A-Za-z][A-Za-z0-9\-]*')
    ->name('events.show');
Route::get('/giftcards', [GiftCardsController::class, 'index'])->name('giftcards.index');
Route::get('/workshops', [TypeController::class, 'workshops'])
    ->defaults('type', 'workshops')
    ->name('workshops.index');
Route::get('/classes', [TypeController::class, 'classes'])
    ->defaults('type', 'classes')
    ->name('classes.index');
Route::get('/retreats', [TypeController::class, 'retreats'])
    ->defaults('type', 'retreats')
    ->name('retreats.index');
Route::get('/gifts', [SeoLandingController::class, 'show'])
    ->defaults('type', 'gifts')
    ->name('gifts.index');

// Canonical format/modality landing pages
Route::get('/online/{modality}/{offering}', [OfferingController::class, 'showOnline'])
    ->where([
        'modality' => $seoSlugPattern,
        'offering' => '[A-Za-z0-9][A-Za-z0-9\-]*',
    ])
    ->name('online.modality-offering');
Route::get('/{format}/{modality}/{country}/{county}/{town}/{offering}', [OfferingController::class, 'showAtLocation'])
    ->where([
        'format' => 'therapies|events|workshops|classes|retreats|gifts',
        'modality' => $seoSlugPattern,
        'country' => $seoSlugPattern,
        'county' => $seoSlugPattern,
        'town' => $seoSlugPattern,
        'offering' => '[A-Za-z0-9][A-Za-z0-9\-]*',
    ])
    ->name('landing.format-modality-location-offering');
Route::get('/{format}/{modality}/{country}/{county}/{town}', [LocationController::class, 'modalityLocation'])
    ->where([
        'format' => 'therapies|events|workshops|classes|retreats|gifts',
        'modality' => $seoSlugPattern,
        'country' => $seoSlugPattern,
        'county' => $seoSlugPattern,
        'town' => $seoSlugPattern,
    ])
    ->name('landing.format-modality-location');
Route::get('/{format}/{modality}/{offering}', [OfferingController::class, 'show'])
    ->where([
        'format' => 'therapies|events|workshops|classes|retreats|gifts',
        'modality' => $seoSlugPattern,
        'offering' => '[A-Za-z0-9][A-Za-z0-9\-]*',
    ])
    ->name('landing.format-modality-offering');
Route::get('/{format}/{modality}', [CategoryController::class, 'show'])
    ->where([
        'format' => 'therapies|events|workshops|classes|retreats|gifts',
        'modality' => $seoSlugPattern,
    ])
    ->name('landing.format-modality');
// Legacy redirects are handled by the backend redirect table.
// Keep one-segment public pages from being swallowed by the generic category hub.
$reservedCategorySlugs = [
    'about',
    'cart',
    'checkout',
    'contact',
    'corporate',
    'corporate-wellbeing',
    'corporate-wellness',
    'cookies',
    'dashboard',
    'event',
    'events',
    'experience',
    'experiences',
    'gift',
    'gift-cards',
    'gift-vouchers',
    'giftcards',
    'help',
    'mindful-times',
    'near-me',
    'online',
    'partners',
    'plan',
    'privacy',
    'providers',
    'refunds-and-cancellations',
    'reviews',
    'safety-and-contraindications',
    'search',
    'sitemap',
    'terms',
    'therapy',
    'therapies',
    'v3',
    'workshop',
    'workshops',
    'class',
    'classes',
    'retreat',
    'retreats',
    'needs',
    'locations',
    'online-near-me',
];
$reservedCategoryPattern = implode('|', array_map(
    static fn (string $slug): string => preg_quote($slug, '/'),
    $reservedCategorySlugs
));
Route::get('/{category}', [CategoryController::class, 'hub'])
    ->where('category', '(?!(?:' . $reservedCategoryPattern . ')$)[A-Za-z][A-Za-z0-9\-]*')
    ->name('landing.category');

// Legacy hubs are handled by the backend redirect table.
// Pain-point landing pages
Route::get('/need/{need}', [LandingController::class, 'need']);
// Quiz plan results page
Route::get('/plan', [LandingController::class, 'plan']);

// Removed universal category catch-all to avoid conflicts with Blade routes

// City pages (limited whitelist to avoid conflicting with known routes)
$cities = implode('|', [
    'london','manchester','birmingham','leeds','bristol','brighton','liverpool','glasgow','edinburgh','cardiff','kent',
]);
Route::get('/{city}/{type}/{category}', [LocationsController::class, 'cityCategory'])
    ->where(['city' => $cities, 'type' => 'therapies|events|workshops|classes'])
    ->name('locations.city-category');

// Legacy experiences routes remain controller-backed for compatibility.
Route::get('/experiences', [RedirectsController::class, 'experiencesIndex']);
Route::get('/experience', [RedirectsController::class, 'experienceIndex']);
Route::get('/experience/{slug}', [RedirectsController::class, 'experienceSlug']);
Route::get('/experiences/{slug}', [RedirectsController::class, 'experiencesSlug']);

Route::get('/reviews', [ReviewsController::class, 'index'])->name('reviews.index');
if (config('wow.enable_static_pages')) {
Route::get('/contact', function(){
    return Inertia::render('General/Page', [
        'title' => 'Contact',
        'metaDescription' => 'Get in touch with We Offer Wellness.',
        'bodyHtml' => '<p>Email us at <a href="mailto:hello@weofferwellness.co.uk">hello@weofferwellness.co.uk</a> or use the form below.</p>',
        'canonical' => url('/contact'),
    ]);
});
Route::get('/corporate/{slug}', function (string $slug) {
    $slug = strtolower($slug);
    $pages = [
        'wellbeing-workshops' => [
            'title' => 'Wellbeing Workshops',
            'desc' => 'Hands‑on sessions for stress, sleep and energy — tailored to your team.',
            'features' => [
                ['title' => 'Stress & burnout', 'text' => 'Breath, nervous‑system resets and micro‑mobility.'],
                ['title' => 'Energy & focus', 'text' => 'Cold exposure education, breath and recovery.'],
                ['title' => 'Sleep & recovery', 'text' => 'Wind‑down routines and restorative practices.'],
            ],
        ],
        'meditation' => [
            'title' => 'Meditation for Teams',
            'desc' => 'Guided meditations to reduce stress and improve focus — online or on‑site.',
            'features' => [
                ['title' => 'Beginner‑friendly', 'text' => 'No experience required.'],
                ['title' => 'Repeatable', 'text' => 'Weekly or monthly cadence.'],
                ['title' => 'Measurable', 'text' => 'Attendance and feedback tracking.'],
            ],
        ],
        'breathwork' => [
            'title' => 'Breathwork for Teams',
            'desc' => 'Science‑backed breathing to regulate stress and improve resilience.',
            'features' => [
                ['title' => 'Downshift fast', 'text' => 'Short parasympathetic techniques.'],
                ['title' => 'Focus boosts', 'text' => 'CO₂ tolerance and cadence work.'],
                ['title' => 'Hybrid delivery', 'text' => 'Online live or in‑person.'],
            ],
        ],
        'sound-bath' => [
            'title' => 'Workplace Sound Baths',
            'desc' => 'Immersive relaxation sessions to reduce stress and improve sleep. On‑site. ',
            'features' => [
                ['title' => 'All equipment', 'text' => 'We bring mats and instruments.'],
                ['title' => '30–60 minutes', 'text' => 'Fits lunch‑and‑learn or after‑work slots.'],
                ['title' => 'Group sizes', 'text' => 'From 10 to 60 attendees.'],
            ],
        ],
        'gift-vouchers' => [
            'title' => 'Staff Gifting',
            'desc' => 'Digital gift cards and curated experiences for employees and clients.',
            'features' => [
                ['title' => 'Instant delivery', 'text' => 'Email or bulk CSV.'],
                ['title' => 'Custom amounts', 'text' => '£25 – £200+.'],
                ['title' => 'Brandable', 'text' => 'Your logo and message.'],
            ],
        ],
        'employee-rewards' => [
            'title' => 'Employee Rewards',
            'desc' => 'Meaningful perks that support mental health and recovery.',
            'features' => [
                ['title' => 'Perk portals', 'text' => 'HRIS and benefit‑platform friendly.'],
                ['title' => 'Usage tracking', 'text' => 'Redemption and feedback.'],
                ['title' => 'Flexible billing', 'text' => 'Invoice or card.'],
            ],
        ],
    ];
    if (!isset($pages[$slug])) { abort(404); }
    return Inertia::render('Corporate/Service', [
        'slug' => $slug,
        'page' => $pages[$slug],
    ]);
});
}

// Cart page
Route::get('/cart', [CartController::class, 'page']);

// Checkout routes
Route::get('/checkout', [CartController::class, 'page'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'create']);
Route::get('/checkout/success', [CheckoutResultController::class, 'success'])->name('checkout.success');
Route::get('/checkout/cancel', [CheckoutResultController::class, 'cancel'])->name('checkout.cancel');

// API routes moved to routes/api.php

// Providers handled via ProvidersController below

// Help / Partners (guarded)
Route::get('/partners', [StaticPagesController::class, 'partners']);

// XML sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemap-schedules.xml', [SitemapController::class, 'schedules']);
Route::get('/sitemap-index.xml', [SitemapController::class, 'indexFile']);
Route::get('/sitemaps/{segment}.xml', [SitemapController::class, 'segment'])
    ->where('segment', '[A-Za-z0-9\-]+');
Route::get('/llms.txt', [AiDiscoveryController::class, 'llms']);
Route::get('/llms-small.txt', [AiDiscoveryController::class, 'llmsSmall']);
Route::get('/llms-full.txt', [AiDiscoveryController::class, 'llmsFull']);
Route::get('/.well-known/ai.txt', [AiDiscoveryController::class, 'policy']);
Route::get('/indexnow.txt', function (IndexNowService $indexNow) {
    return response($indexNow->key(), 200)->header('Content-Type', 'text/plain; charset=utf-8');
});
Route::get('/search-console/oauth/callback', function (Request $request) {
    $code = trim((string) $request->query('code', ''));
    $error = trim((string) $request->query('error', ''));
    $state = trim((string) $request->query('state', ''));

    if ($error !== '') {
        return response()->make(
            "<!doctype html><html><head><meta charset=\"utf-8\"><title>Search Console OAuth</title></head><body style=\"font-family:system-ui;padding:24px\"><h1>OAuth failed</h1><p><strong>Error:</strong> " . e($error) . '</p></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store']
        );
    }

    return response()->make(
        "<!doctype html><html><head><meta charset=\"utf-8\"><title>Search Console OAuth</title></head><body style=\"font-family:system-ui;padding:24px\"><h1>Authorization received</h1><p>Copy the code below and run the exchange command.</p><pre style=\"white-space:pre-wrap;word-break:break-word;background:#f5f5f5;padding:16px;border-radius:8px\">" . e($code) . "</pre><p><strong>State:</strong> " . e($state) . "</p></body></html>",
        200,
        ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store']
    );
});

// General content pages (always available)
Route::get('/privacy', [StaticPagesController::class, 'show'])->defaults('slug', 'privacy');
Route::get('/terms', [StaticPagesController::class, 'show'])->defaults('slug', 'terms');
Route::get('/cookies', [StaticPagesController::class, 'show'])->defaults('slug', 'cookies');
Route::get('/refunds-and-cancellations', [StaticPagesController::class, 'show'])->defaults('slug', 'refunds-and-cancellations');

Route::get('/safety-and-contraindications', [SafetyContraindicationsController::class, 'index'])
    ->name('safety-and-contraindications');

Route::get('/help', [HelpController::class, 'index'])->name('help');
Route::get('/help/faq', [HelpController::class, 'faq'])->name('help.faq');
Route::get('/help/gift-cards', [HelpController::class, 'giftCards'])->name('help.gift-cards');

Route::get('/about', [AboutController::class, 'index'])
    ->name('about');
Route::get('/about/team/{slug}', [AboutController::class, 'team'])
    ->where('slug', '[A-Za-z0-9\-]+');

// Providers directory
Route::get('/providers', [ProvidersController::class, 'index']);
Route::get('/practioner/{slug}', [ProvidersController::class, 'show']);
Route::post('/practioner/{slug}/reviews', [ProvidersController::class, 'storeReview'])
    ->middleware('auth')
    ->name('practioner.reviews.store');
Route::get('/provider/{slug}', [ProvidersController::class, 'show']);

// Contact
Route::get('/contact', [ContactController::class, 'index']);

// Corporate
Route::get('/corporate', [CorporateController::class, 'hub']);
Route::get('/corporate-wellness', [CorporateController::class, 'comingSoon']);

// Gift cards
Route::get('/gift-cards', [StaticPagesController::class, 'giftCards']);

// Final one-segment fallback for DB-driven legal pages, then legacy pages
Route::get('/{slug}', [StaticPagesController::class, 'show'])
    ->where('slug', '[A-Za-z0-9][A-Za-z0-9\-]*');

// Dynamic CMS-like pages stored in shared DB (from Backend admin)
Route::fallback([\App\Http\Controllers\PageController::class, 'show'])
    ->where('fallbackPlaceholder', '^(?!api\/).*$');
