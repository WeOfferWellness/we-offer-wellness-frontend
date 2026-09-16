<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\LegalDocument;
use App\Models\OfferingV3;
use App\Models\Platform;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Review;
use App\Models\VendorDetail;
use App\Services\BookingContextBuilder;
use App\Services\SeoStructureService;
use App\Support\EventListing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LandingController extends Controller
{
    // Supported hubs/types
    private const TYPES = ['therapies', 'events', 'workshops', 'classes', 'retreats', 'gifts'];

    private function seo(): SeoStructureService
    {
        return app(SeoStructureService::class);
    }

    private function redirectWithQuery(Request $request, string $target, int $status = 301)
    {
        $query = trim((string) $request->getQueryString());
        if ($query !== '') {
            $target .= str_contains($target, '?') ? '&' : '?';
            $target .= $query;
        }

        return redirect()->to($target, $status);
    }

    private function temporaryCategoryRedirect(string $target)
    {
        return response()
            ->view('redirecting', ['target' => $target], 302)
            ->header('Location', $target);
    }

    public function hub(Request $request, string $type)
    {
        $type = strtolower($type);
        if (! in_array($type, self::TYPES, true) && $type !== 'near-me') {
            abort(404);
        }

        // Top categories by product count (optionally filtered by type hint)
        $categories = ProductCategory::query()
            ->withCount(['products as products_count' => function ($q) use ($type) {
                $this->applyTypeFilter($q, $type);
            }])
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->take(24)
            ->get()
            ->map(function ($cat) use ($type) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $this->slugify($cat->name),
                    'count' => (int) ($cat->products_count ?? 0),
                    'url' => $this->seo()->modalityPageUrl($type, (string) $cat->name),
                ];
            })->values();

        // Fallback if counts are zero (some schemas may not link correctly)
        if ($categories->sum('count') === 0) {
            try {
                $agg = Product::query();
                $this->applyTypeFilter($agg, $type);
                $agg = $agg->selectRaw('category_id, COUNT(*) as cnt')
                    ->whereNotNull('category_id')
                    ->groupBy('category_id')
                    ->orderByDesc('cnt')
                    ->limit(24)
                    ->get();
                $ids = $agg->pluck('category_id')->filter()->unique()->values();
                $map = ProductCategory::query()->whereIn('id', $ids)->get()->keyBy('id');
                $categories = $agg->map(function ($row) use ($map, $type) {
                    $cat = $map->get($row->category_id);
                    if (! $cat) {
                        return null;
                    }

                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'slug' => $this->slugify($cat->name),
                        'count' => (int) ($row->cnt ?? 0),
                        'url' => $this->seo()->modalityPageUrl($type, (string) $cat->name),
                    ];
                })->filter()->values();
            } catch (\Throwable $e) {
                // keep empty categories; frontend will hide the section
            }
        }

        // Products for the hub with pagination
        $perPage = (int) $request->integer('per_page', $type === 'therapies' ? 60 : 24);
        $perPage = max(6, min($perPage, 120));
        $page = (int) $request->integer('page', 1);
        $cookieCity = trim((string) $request->cookie('wow_city', ''));

        $geoStatus = $type === 'near-me'
            ? ($cookieCity !== '' ? 'ready' : 'needs-location')
            : null;

        if ($type === 'near-me' && $cookieCity === '') {
            $paginator = new LengthAwarePaginator([], 0, $perPage, $page);
        } else {
            $queryType = $type === 'near-me' ? null : $type;
            $cityOverride = $type === 'near-me' ? $cookieCity : null;
            $paginator = $this->queryProducts($queryType, null, $request, $cityOverride)->paginate($perPage, ['*'], 'page', $page);

            if ($type !== 'near-me' && $paginator->total() === 0 && $cookieCity !== '') {
                // If city filter zeroed results, try without city
                $paginator = $this->queryProducts($queryType, null, $request, '')->paginate($perPage, ['*'], 'page', $page);
            } elseif ($type === 'near-me' && $cookieCity !== '' && $paginator->total() === 0) {
                // Fall back to a broader mix so the page still renders helpful content
                $paginator = $this->queryProducts(null, null, $request, '')->paginate($perPage, ['*'], 'page', $page);
            }
        }
        // Map products into view model while preserving paginator meta
        $mapped = $paginator->getCollection()->map(fn ($p) => $this->transformProduct($p));
        $paginator->setCollection($mapped);

        if ($type === 'near-me') {
            $categories = collect();
        }

        return Inertia::render('Landing/Hub', [
            'type' => $type,
            'categories' => $categories,
            'products' => $paginator,
            'mapsKey' => env('GOOGLE_MAPS_API_KEY'),
            'geoStatus' => $geoStatus,
            'userCity' => $cookieCity,
        ]);
    }

    public function categoryHub(Request $request, string $category)
    {
        $category = trim($category);

        $document = $this->findLegalDocument($category);
        if ($document) {
            return $this->renderLegalDocument($document);
        }

        $cat = $this->findCategoryBySlug($category);

        if (! $cat) {
            abort(404);
        }

        return $this->redirectWithQuery($request, $this->seo()->modalityPageUrl('therapies', (string) $cat->name), 301);
    }

    private function findLegalDocument(string $slug): ?LegalDocument
    {
        $slug = strtolower(trim($slug));
        $slug = match ($slug) {
            'terms-of-service' => 'terms',
            'privacy-policy' => 'privacy',
            default => $slug,
        };

        $platform = $this->resolvePlatform();

        $document = LegalDocument::query()
            ->where('platform_id', $platform->id)
            ->where('slug', $slug)
            ->first();

        if ($document) {
            return $document;
        }

        return LegalDocument::query()
            ->where('slug', $slug)
            ->orderByRaw('CASE WHEN platform_id = ? THEN 0 ELSE 1 END', [$platform->id])
            ->first();
    }

    private function renderLegalDocument(LegalDocument $document)
    {
        $platform = $document->platform ?: $this->resolvePlatform();

        return view('legal.document', [
            'document' => $document,
            'platform' => $platform,
            'slug' => $document->slug,
        ]);
    }

    private function resolvePlatform(): Platform
    {
        $platformName = match (true) {
            str_contains(request()->getHost(), 'studio.weofferwellness.co.uk') => 'WOW Studio',
            default => 'WOW Store',
        };

        return Platform::query()->firstOrCreate(['name' => $platformName]);
    }

    public function formatModality(Request $request, string $format, string $modality)
    {
        $format = strtolower(trim($format));
        if (! in_array($format, self::TYPES, true)) {
            abort(404);
        }

        $cat = $this->findCategoryBySlug($modality);
        if (! $cat) {
            abort(404);
        }

        $products = $this->queryProducts($format, $cat->id, $request)->limit(12)->get();
        $title = $cat->name.' '.ucfirst($format);

        return view('landing.show', [
            'seo' => [
                'title' => $title.' | We Offer Wellness',
                'description' => $cat->name.' '.ucfirst($format).' options on We Offer Wellness.',
                'robots' => $request->hasAny(['page', 'per_page', 'sort', 'mode']) || $products->isEmpty() ? 'noindex,follow' : 'index,follow',
                'canonical' => $this->seo()->modalityPageUrl($format, (string) $cat->name),
            ],
            'landing' => [
                'kicker' => ucfirst($format),
                'title' => $title,
                'intro' => 'Browse '.$cat->name.' '.$format.' listings across trusted practitioners.',
                'points' => [
                    'Curated and search-friendly',
                    'Online and in-person availability',
                    'Popular results surfaced first',
                ],
                'primary_cta' => ['label' => 'Browse listings', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'View category', 'href' => $this->seo()->modalityPageUrl($format, (string) $cat->name)],
            ],
            'type' => $format,
            'categories' => ProductCategory::query()
                ->withCount(['products as products_count' => function ($q) use ($format) {
                    $this->applyTypeFilter($q, $format);
                }])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->take(8)
                ->get()
                ->map(function (ProductCategory $category) use ($format) {
                    $name = trim((string) ($category->name ?? ''));

                    return [
                        'name' => $name,
                        'slug' => $this->slugify($name),
                        'count' => (int) ($category->products_count ?? 0),
                        'url' => $this->seo()->modalityPageUrl($format, $name),
                    ];
                })
                ->filter(fn (array $category) => $category['count'] > 0)
                ->values(),
            'products' => $products,
        ]);
    }

    public function formatModalityLocation(Request $request, string $format, string $modality, string $country, string $county, string $town)
    {
        $format = strtolower(trim($format));
        if (! in_array($format, self::TYPES, true)) {
            abort(404);
        }

        $cat = $this->findCategoryBySlug($modality);
        if (! $cat) {
            abort(404);
        }

        $slug = $this->slugify($cat->name);
        $countryLabel = ucwords(str_replace('-', ' ', trim($country)));
        $countyLabel = ucwords(str_replace('-', ' ', trim($county)));
        $townLabel = ucwords(str_replace('-', ' ', trim($town)));
        $locationLabel = trim(implode(', ', array_filter([$townLabel, $countyLabel, $countryLabel])));
        $products = $this->queryProducts($format, $cat->id, $request, $locationLabel)->limit(12)->get();
        $title = $cat->name.' '.ucfirst($format).' in '.$locationLabel;

        return view('landing.show', [
            'seo' => [
                'title' => $title.' | We Offer Wellness',
                'description' => $cat->name.' '.ucfirst($format).' in '.$locationLabel.'.',
                'robots' => $request->hasAny(['page', 'per_page', 'sort', 'mode']) || $products->isEmpty() ? 'noindex,follow' : 'index,follow',
                'canonical' => $this->seo()->modalityLocationPageUrl($format, (string) $cat->name, $country, $county, $town),
            ],
            'landing' => [
                'kicker' => ucfirst($format),
                'title' => $title,
                'intro' => 'Browse '.$cat->name.' '.$format.' in '.$locationLabel.'.',
                'points' => [
                    'Ranked for the selected location',
                    'Online fallback available',
                    'Trusted practitioners and venues',
                ],
                'primary_cta' => ['label' => 'Browse listings', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'View category', 'href' => $this->seo()->modalityPageUrl($format, (string) $cat->name)],
            ],
            'type' => $format,
            'city' => $townLabel,
            'country' => $countryLabel,
            'county' => $countyLabel,
            'town' => $townLabel,
            'categories' => ProductCategory::query()
                ->withCount(['products as products_count' => function ($q) use ($format) {
                    $this->applyTypeFilter($q, $format);
                }])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->take(8)
                ->get()
                ->map(function (ProductCategory $category) use ($format) {
                    $name = trim((string) ($category->name ?? ''));

                    return [
                        'name' => $name,
                        'slug' => $this->slugify($name),
                        'count' => (int) ($category->products_count ?? 0),
                        'url' => $this->seo()->modalityPageUrl($format, $name),
                    ];
                })
                ->filter(fn (array $category) => $category['count'] > 0)
                ->values(),
            'products' => $products,
        ]);
    }

    public function categoryType(Request $request, string $category, string $type)
    {
        $type = strtolower(trim($type));
        if (! in_array($type, self::TYPES, true)) {
            abort(404);
        }

        $cat = $this->findCategoryBySlug($category);
        if (! $cat) {
            abort(404);
        }

        $products = $this->queryProducts($type, $cat->id, $request)->limit(12)->get();

        $title = $cat->name.' '.ucfirst($type);

        return view('landing.show', [
            'seo' => [
                'title' => $title.' | We Offer Wellness',
                'description' => $cat->name.' '.ucfirst($type).' options on We Offer Wellness.',
                'robots' => $request->hasAny(['page', 'per_page', 'sort', 'mode']) || $products->isEmpty() ? 'noindex,follow' : 'index,follow',
                'canonical' => $this->seo()->modalityPageUrl($type, (string) $cat->name),
            ],
            'landing' => [
                'kicker' => ucfirst($type),
                'title' => $title,
                'intro' => 'Browse '.$cat->name.' '.$type.' listings across trusted practitioners.',
                'points' => [
                    'Curated and search-friendly',
                    'Online and in-person availability',
                    'Popular results surfaced first',
                ],
                'primary_cta' => ['label' => 'Browse listings', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'View category', 'href' => $this->seo()->modalityPageUrl($type, (string) $cat->name)],
            ],
            'type' => $type,
            'categories' => ProductCategory::query()
                ->withCount(['products as products_count' => function ($q) use ($type) {
                    $this->applyTypeFilter($q, $type);
                }])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->take(8)
                ->get()
                ->map(function (ProductCategory $category) use ($type) {
                    $name = trim((string) ($category->name ?? ''));

                    return [
                        'name' => $name,
                        'slug' => $this->slugify($name),
                        'count' => (int) ($category->products_count ?? 0),
                        'url' => $this->seo()->modalityPageUrl($type, $name),
                    ];
                })
                ->filter(fn (array $category) => $category['count'] > 0)
                ->values(),
            'products' => $products,
        ]);
    }

    public function categoryTypeLocation(Request $request, string $category, string $type, string $location)
    {
        $type = strtolower(trim($type));
        if (! in_array($type, self::TYPES, true)) {
            abort(404);
        }

        $cat = $this->findCategoryBySlug($category);
        if (! $cat) {
            abort(404);
        }

        $locationName = trim(str_replace(['-', '+'], ' ', $location));
        $products = $this->queryProducts($type, $cat->id, $request, $locationName)->limit(12)->get();
        $title = $cat->name.' '.ucfirst($type).' in '.ucwords($locationName);

        return view('landing.show', [
            'seo' => [
                'title' => $title.' | We Offer Wellness',
                'description' => $cat->name.' '.ucfirst($type).' in '.ucwords($locationName).'.',
                'robots' => $request->hasAny(['page', 'per_page', 'sort', 'mode']) || $products->isEmpty() ? 'noindex,follow' : 'index,follow',
                'canonical' => $this->seo()->modalityPageUrl($type, (string) $cat->name),
            ],
            'landing' => [
                'kicker' => ucfirst($type),
                'title' => $title,
                'intro' => 'Browse '.$cat->name.' '.$type.' in '.ucwords($locationName).'.',
                'points' => [
                    'Ranked for the selected location',
                    'Online fallback available',
                    'Trusted practitioners and venues',
                ],
                'primary_cta' => ['label' => 'Browse listings', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'View category', 'href' => $this->seo()->modalityPageUrl($type, (string) $cat->name)],
            ],
            'type' => $type,
            'city' => ucwords($locationName),
            'categories' => ProductCategory::query()
                ->withCount(['products as products_count' => function ($q) use ($type) {
                    $this->applyTypeFilter($q, $type);
                }])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->take(8)
                ->get()
                ->map(function (ProductCategory $category) use ($type) {
                    $name = trim((string) ($category->name ?? ''));

                    return [
                        'name' => $name,
                        'slug' => $this->slugify($name),
                        'count' => (int) ($category->products_count ?? 0),
                        'url' => $this->seo()->modalityPageUrl($type, $name),
                    ];
                })
                ->filter(fn (array $category) => $category['count'] > 0)
                ->values(),
            'products' => $products,
        ]);
    }

    public function category(Request $request, string $type, string $category)
    {
        $type = strtolower($type);
        if (! in_array($type, self::TYPES, true)) {
            abort(404);
        }

        $cat = $this->findCategoryBySlug($category);
        if (! $cat) {
            // Fallback: virtual category by slug terms (noindex is handled in page head via canonical elsewhere)
            return $this->renderVirtualCategory($request, $type, $category, null);
        }

        $perPage = (int) $request->integer('per_page', 48);
        $perPage = max(6, min($perPage, 120));
        $page = (int) $request->integer('page', 1);
        $cookieCity = trim((string) $request->cookie('wow_city', ''));
        $paginator = $this->queryProducts($type, $cat->id, $request)->paginate($perPage, ['*'], 'page', $page);
        if ($paginator->total() === 0 && $cookieCity !== '') {
            $paginator = $this->queryProducts($type, $cat->id, $request, '')->paginate($perPage, ['*'], 'page', $page);
        }
        $paginator->setCollection($paginator->getCollection()->map(fn ($p) => $this->transformProduct($p)));

        return Inertia::render('Landing/Listing', [
            'type' => $type,
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $this->slugify($cat->name),
            ],
            'products' => $paginator,
            'mapsKey' => env('GOOGLE_MAPS_API_KEY'),
        ]);
    }

    public function city(Request $request, string $city)
    {
        $city = trim($city);
        // lightweight showcase across types for a city name (string match on locations)
        $products = $this->queryProducts(null, null, $request, $city)
            ->limit(24)
            ->get()
            ->map(fn ($p) => $this->transformProduct($p));

        return Inertia::render('Landing/City', [
            'city' => $city,
            'products' => $products,
        ]);
    }

    public function cityCategory(Request $request, string $city, string $type, string $category)
    {
        $type = strtolower($type);
        if (! in_array($type, self::TYPES, true)) {
            abort(404);
        }
        $cat = $this->findCategoryBySlug($category);
        if (! $cat) {
            return $this->renderVirtualCategory($request, $type, $category, $city);
        }

        $perPage = (int) $request->integer('per_page', 48);
        $perPage = max(6, min($perPage, 120));
        $page = (int) $request->integer('page', 1);
        $paginator = $this->queryProducts($type, $cat->id, $request, $city)
            ->paginate($perPage, ['*'], 'page', $page);
        $paginator->setCollection($paginator->getCollection()->map(fn ($p) => $this->transformProduct($p)));

        return Inertia::render('Landing/Listing', [
            'city' => $city,
            'type' => $type,
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $this->slugify($cat->name),
            ],
            'products' => $paginator,
            'mapsKey' => env('GOOGLE_MAPS_API_KEY'),
        ]);
    }

    private function renderVirtualCategory(Request $request, string $type, string $slug, ?string $city)
    {
        $limit = (int) $request->integer('limit', 50);
        $terms = $this->synonymsForSlug($slug, $type);

        $q = Product::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with(['media', 'options.values', 'category']);

        // Type narrowing
        $this->applyTypeFilter($q, $type);

        // Mode filter (same rules as queryProducts)
        $modeParam = strtolower((string) $request->string('mode'));
        $cookieMode = strtolower((string) $request->cookie('wow_mode', ''));
        $mode = in_array($modeParam, ['online', 'in-person', 'all'], true) ? $modeParam : (in_array($cookieMode, ['online', 'in-person', 'all'], true) ? $cookieMode : '');
        if ($mode !== '') {
            $q->whereHas('options', function ($oq) use ($mode) {
                $oq->where(function ($w) {
                    $w->whereRaw("LOWER(TRIM(COALESCE(meta_name,''))) = 'locations'")
                        ->orWhereRaw("LOWER(TRIM(COALESCE(name,''))) IN ('location(s)','locations')");
                });
                if ($mode === 'online') {
                    $oq->whereHas('values', function ($vq) {
                        $vq->whereRaw("LOWER(TRIM(COALESCE(value,''))) = 'online'");
                    });
                } elseif ($mode === 'in-person') {
                    $oq->whereHas('values', function ($vq) {
                        $vq->whereRaw("LOWER(TRIM(COALESCE(value,''))) <> 'online'")
                            ->whereRaw("TRIM(COALESCE(value,'')) <> ''");
                    });
                }
            });
        }

        // Case-insensitive text relevance across key fields
        $q->where(function ($qq) use ($terms) {
            foreach ($terms as $t) {
                $like = '%'.strtolower($t).'%';
                $qq->orWhereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(summary) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(body_html) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(what_to_expect) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(included) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(tags_list,\'\')) LIKE ?', [$like]);
            }
        });

        // City scoping where provided
        if ($city !== null && trim($city) !== '') {
            $like = '%'.trim($city).'%';
            $q->whereHas('options', function ($oq) use ($like) {
                $oq->where('meta_name', 'locations')
                    ->whereHas('values', fn ($vq) => $vq->where('value', 'like', $like));
            });
        }

        // Popular first
        $q->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')
            ->orderByRaw('COALESCE(reviews_avg_rating, 0) DESC')
            ->orderByRaw('COALESCE(reviews_count, 0) DESC');

        $items = $q->limit($limit)->get();
        $products = $items->map(fn ($p) => $this->transformProduct($p));

        // Broaden if empty: include any type while keeping text relevance
        if ($products->isEmpty()) {
            $q2 = Product::query()
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->with(['media', 'options.values', 'category']);
            $q2->where(function ($qq) use ($terms) {
                foreach ($terms as $t) {
                    $like = '%'.strtolower($t).'%';
                    $qq->orWhereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(summary) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(body_html) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(what_to_expect) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(included) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(tags_list,\'\')) LIKE ?', [$like]);
                }
            });
            $q2->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')
                ->orderByRaw('COALESCE(reviews_avg_rating, 0) DESC')
                ->orderByRaw('COALESCE(reviews_count, 0) DESC');
            $items = $q2->limit($limit)->get();
            $products = $items->map(fn ($p) => $this->transformProduct($p));
        }

        $name = ucwords(str_replace('-', ' ', strtolower($slug)));

        return Inertia::render('Landing/Listing', [
            'city' => $city,
            'type' => $type,
            'category' => ['id' => null, 'name' => $name, 'slug' => strtolower($slug)],
            'products' => $products,
        ]);
    }

    private function synonymsForSlug(string $slug, string $type): array
    {
        $s = strtolower($slug);
        $map = [
            // Therapies
            'massage-therapy' => ['massage', 'deep tissue', 'sports massage', 'swedish massage'],
            'manual-lymphatic-drainage' => ['manual lymphatic drainage', 'mld', 'lymphatic drainage'],
            'reiki' => ['reiki'],
            'acupuncture' => ['acupuncture'],
            'reflexology' => ['reflexology', 'foot reflexology'],
            'sound-therapy' => ['sound therapy', 'sound healing', 'sound bath', 'gong bath'],
            // Events
            'sound-bath' => ['sound bath', 'gong bath', 'sound healing'],
            'gong-bath' => ['gong bath', 'sound bath'],
            'breathwork' => ['breathwork', 'breathing', 'pranayama'],
            'meditation' => ['meditation', 'mindfulness'],
            'reiki-circles' => ['reiki circle', 'reiki share'],
            'ice-bath-workshops' => ['ice bath', 'cold immersion', 'cold plunge'],
            // Classes
            'yoga' => ['yoga', 'vinyasa', 'yin', 'hatha'],
            'qigong' => ['qigong', 'chi kung'],
            'tre' => ['tre', 'tension release exercises', 'trauma release exercises'],
            'pilates' => ['pilates'],
            // Gifts
            'massage-gift-voucher' => ['massage', 'gift'],
            'reiki-gift-voucher' => ['reiki', 'gift'],
            'sound-bath-gift-voucher' => ['sound bath', 'gift'],
        ];
        if (isset($map[$s])) {
            return $map[$s];
        }
        // Fallback to slug tokens
        $tokens = array_filter(explode('-', preg_replace('~[^a-z0-9\-]+~', '-', $s)));
        if (empty($tokens)) {
            return [$s];
        }

        return [str_replace('-', ' ', $s), ...$tokens];
    }

    public function need(Request $request, string $need)
    {
        $slug = strtolower(trim($need));
        $map = [
            'sleep' => ['name' => 'Sleep better', 'terms' => ['sleep', 'insomnia', 'rest', 'nidra']],
            'stress' => ['name' => 'Stress reset', 'terms' => ['stress relief', 'calm', 'relaxation', 'breathwork', 'anxiety']],
            'energy' => ['name' => 'Energy boost', 'terms' => ['energy', 'focus', 'breath', 'mobility', 'sauna', 'cold']],
            'pain' => ['name' => 'Pain relief', 'terms' => ['pain relief', 'mobility', 'massage', 'acupuncture', 'physio']],
        ];
        // alias support
        if (! isset($map[$slug])) {
            if (in_array($slug, ['sleep-better'])) {
                $slug = 'sleep';
            } elseif (in_array($slug, ['stress-relief', 'calm'])) {
                $slug = 'stress';
            } elseif (in_array($slug, ['energy-boost'])) {
                $slug = 'energy';
            } elseif (in_array($slug, ['pain-relief'])) {
                $slug = 'pain';
            }
        }
        // Add support for additional recognised needs without 404
        if (! isset($map[$slug])) {
            // Minimal curated additions
            if ($slug === 'gut') {
                $map['gut'] = [
                    'name' => 'Gut health',
                    'terms' => ['gut', 'digestion', 'digestive', 'microbiome', 'bloating', 'stomach', 'ibs'],
                ];
            }
        }
        // Generic fallback: treat any slug as a free-text need page
        if (! isset($map[$slug])) {
            $readable = ucwords(str_replace('-', ' ', $slug));
            $tokens = array_filter(explode('-', $slug));
            $terms = array_values(array_unique(array_filter(array_merge([$slug, strtolower($readable)], $tokens))));
            $map[$slug] = ['name' => $readable, 'terms' => $terms];
        }

        $conf = $map[$slug];
        $terms = $conf['terms'];

        $q = Product::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with(['media', 'options.values', 'category']);

        $q->where(function ($qq) use ($terms) {
            foreach ($terms as $t) {
                $like = '%'.$t.'%';
                $qq->orWhere('title', 'like', $like)
                    ->orWhere('summary', 'like', $like)
                    ->orWhere('body_html', 'like', $like)
                    ->orWhere('what_to_expect', 'like', $like)
                    ->orWhere('included', 'like', $like)
                    ->orWhere('tags_list', 'like', $like);
            }
        });

        // Prefer popular within matches using weighted favorability
        $q->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')
            ->orderByRaw('COALESCE(reviews_avg_rating, 0) DESC')
            ->orderByRaw('COALESCE(reviews_count, 0) DESC');

        $products = $q->limit(48)->get()->map(fn ($p) => $this->transformProduct($p));

        return Inertia::render('Landing/Need', [
            'need' => ['slug' => $slug, 'name' => $conf['name']],
            'products' => $products,
        ]);
    }

    public function plan(Request $request)
    {
        $what = trim((string) $request->string('what'));
        $type = trim((string) $request->string('type'));
        $mode = strtolower((string) $request->string('mode'));
        $where = trim((string) $request->string('where'));
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;
        $when = strtolower((string) $request->string('when'));

        $q = Product::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with(['media', 'options.values', 'category']);

        // Text search across relevant fields
        if ($what !== '') {
            $q->where(function ($qq) use ($what) {
                $pattern = '%'.$what.'%';
                $qq->where('title', 'like', $pattern)
                    ->orWhere('summary', 'like', $pattern)
                    ->orWhere('body_html', 'like', $pattern)
                    ->orWhere('what_to_expect', 'like', $pattern)
                    ->orWhere('included', 'like', $pattern)
                    ->orWhere('tags_list', 'like', $pattern);
            });
        }

        // Type filter
        if ($type !== '') {
            $this->applyTypeFilter($q, $type);
        }

        // Mode filter based on locations option
        if (in_array($mode, ['online', 'in-person'], true)) {
            $q->whereHas('options', function ($oq) use ($mode) {
                $oq->where('meta_name', 'locations')
                    ->whereHas('values', function ($vq) use ($mode) {
                        if ($mode === 'online') {
                            $vq->where('value', 'Online');
                        } else {
                            $vq->where('value', '!=', 'Online');
                        }
                    });
            });
        }

        // Where (city or area) matches options->values
        if ($where !== '') {
            $like = '%'.$where.'%';
            $q->whereHas('options', function ($oq) use ($like) {
                $oq->where('meta_name', 'locations')
                    ->whereHas('values', function ($vq) use ($like) {
                        $vq->where('value', 'like', $like);
                    });
            });
        }

        // Price
        if ($priceMax !== null) {
            $pm = $priceMax;
            $q->where(function ($qq) use ($pm) {
                $qq->where('price', '<=', $pm)
                    ->orWhere('price', '<=', $pm * 100);
            });
        }

        // When window (best-effort; only applies when date fields exist)
        $now = now();
        if ($when === 'this week') {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
            $q->where(function ($qq) use ($start, $end) {
                $qq->whereBetween('meta_json->date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('meta_json->start_date', '<=', $end->toDateString())
                            ->where('meta_json->end_date', '>=', $start->toDateString());
                    });
            });
        } elseif ($when === 'this weekend') {
            $sat = $now->copy()->startOfWeek()->addDays(5); // Saturday
            $sun = $now->copy()->startOfWeek()->addDays(6)->endOfDay();
            $q->where(function ($qq) use ($sat, $sun) {
                $qq->whereBetween('meta_json->date', [$sat->toDateString(), $sun->toDateString()])
                    ->orWhere(function ($q2) use ($sat, $sun) {
                        $q2->where('meta_json->start_date', '<=', $sun->toDateString())
                            ->where('meta_json->end_date', '>=', $sat->toDateString());
                    });
            });
        } elseif ($when === 'next month') {
            $first = $now->copy()->addMonthNoOverflow()->startOfMonth();
            $last = $first->copy()->endOfMonth();
            $q->where(function ($qq) use ($first, $last) {
                $qq->whereBetween('meta_json->date', [$first->toDateString(), $last->toDateString()])
                    ->orWhere(function ($q2) use ($first, $last) {
                        $q2->where('meta_json->start_date', '<=', $last->toDateString())
                            ->where('meta_json->end_date', '>=', $first->toDateString());
                    });
            });
        }

        // Sort by weighted favorability within the filters
        $q->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')
            ->orderByRaw('COALESCE(reviews_avg_rating, 0) DESC')
            ->orderByRaw('COALESCE(reviews_count, 0) DESC');

        $perPage = (int) $request->integer('per_page', 48);
        $perPage = max(6, min($perPage, 120));
        $page = (int) $request->integer('page', 1);
        $paginator = $q->paginate($perPage, ['*'], 'page', $page);
        $paginator->setCollection($paginator->getCollection()->map(fn ($p) => $this->transformProduct($p)));

        $fallback = [];
        if ($paginator->total() === 0) {
            $fb = Product::query()
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->with(['media', 'options.values', 'category']);
            // keep mode and where if given
            if (in_array($mode, ['online', 'in-person'], true)) {
                $fb->whereHas('options', function ($oq) use ($mode) {
                    $oq->where('meta_name', 'locations')
                        ->whereHas('values', function ($vq) use ($mode) {
                            if ($mode === 'online') {
                                $vq->where('value', 'Online');
                            } else {
                                $vq->where('value', '!=', 'Online');
                            }
                        });
                });
            }
            if ($where !== '') {
                $like = '%'.$where.'%';
                $fb->whereHas('options', function ($oq) use ($like) {
                    $oq->where('meta_name', 'locations')
                        ->whereHas('values', fn ($vq) => $vq->where('value', 'like', $like));
                });
            }
            $fb->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')
                ->orderByRaw('COALESCE(reviews_avg_rating, 0) DESC')
                ->orderByRaw('COALESCE(reviews_count, 0) DESC');
            $fallback = $fb->limit(24)->get()->map(fn ($p) => $this->transformProduct($p))->values();
        }

        return Inertia::render('Landing/Plan', [
            'answers' => [
                'what' => $what,
                'type' => $type,
                'mode' => $mode,
                'where' => $where,
                'price_max' => $priceMax,
                'when' => $when,
            ],
            'products' => $paginator,
            'fallback' => $fallback,
        ]);
    }

    private function findCategoryBySlug(string $slug): ?ProductCategory
    {
        $slug = strtolower($slug);
        // Attempt to match by slugified name
        $cats = ProductCategory::query()->get();
        foreach ($cats as $c) {
            if ($this->slugify($c->name) === $slug) {
                return $c;
            }
        }

        return null;
    }

    private function slugify(?string $name): string
    {
        $s = strtolower(trim((string) $name));
        $s = preg_replace('~[^a-z0-9]+~', '-', $s ?? '') ?? '';

        return trim($s, '-');
    }

    private function normalizeLookupKey(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return preg_replace('~[^a-z0-9]+~', '', $value) ?? '';
    }

    /**
     * Build a handful of token combinations so legacy slugs with collapsed
     * punctuation still resolve to the correct product or offering.
     *
     * @return array<int, array<int, string>>
     */
    private function candidateLookupTokenSets(string $handle): array
    {
        $tokens = preg_split('~[^a-z0-9]+~i', strtolower(trim($handle)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_filter(array_unique($tokens), static fn ($token): bool => strlen($token) >= 2));
        if ($tokens === []) {
            return [];
        }

        $sets = [];
        if (count($tokens) > 1 && ctype_digit($tokens[0])) {
            // A canonical title such as "1:1 ..." normalizes to a URL that
            // begins "11-...", while the stored title still contains the
            // punctuation. Search on the meaningful trailing words and use
            // normalizeLookupKey() below to confirm the exact candidate.
            $sets[] = array_slice($tokens, 1);
        }

        $shortTokens = array_values(array_filter($tokens, static fn ($token): bool => strlen($token) <= 8));
        if ($shortTokens !== []) {
            $sets[] = $shortTokens;
        }

        if ($tokens !== $shortTokens) {
            $sets[] = $tokens;
        }

        foreach ($tokens as $index => $token) {
            if (strlen($token) <= 8) {
                continue;
            }

            $subset = $tokens;
            unset($subset[$index]);
            $subset = array_values($subset);
            if ($subset !== [] && count($subset) >= 2) {
                $sets[] = $subset;
            }
        }

        $unique = [];
        foreach ($sets as $set) {
            $key = json_encode($set);
            if ($key === false) {
                continue;
            }
            $unique[$key] = $set;
        }

        return array_values($unique);
    }

    private function applyTypeFilter($query, ?string $type): void
    {
        if (! $type) {
            return;
        }
        $lc = strtolower($type);
        if ($lc === 'events') {
            $query->where(function ($q) {
                $q->whereRaw("LOWER(product_type) like '%event%'")
                    ->orWhereRaw("LOWER(product_type) like '%workshop%'")
                    ->orWhereNotNull('meta_json->date')
                    ->orWhereNotNull('meta_json->start_date');
            });
        } elseif ($lc === 'workshops') {
            $query->whereRaw("LOWER(product_type) like '%workshop%'");
        } elseif ($lc === 'classes') {
            $query->whereRaw("LOWER(product_type) like '%class%'");
        } elseif ($lc === 'therapies') {
            $query->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(product_type,'')) not like '%event%'")
                    ->whereRaw("LOWER(COALESCE(product_type,'')) not like '%workshop%'")
                    ->whereRaw("LOWER(COALESCE(product_type,'')) not like '%class%'");
            });
        } elseif ($lc === 'retreats') {
            $query->whereRaw("LOWER(product_type) like '%retreat%'");
        } elseif ($lc === 'gifts') {
            $query->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(tags_list,'')) like '%gift%'")
                    ->orWhereRaw("LOWER(COALESCE(product_type,'')) like '%gift%'");
            });
        }
    }

    private function queryProducts(?string $type, ?int $categoryId, Request $request, ?string $city = null)
    {
        $q = Product::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with(['media', 'options.values', 'category']);

        $q->where(function ($visible) {
            $visible->whereHas('status', function ($qs) {
                $qs->whereIn('status', ['live', 'approved']);
            })->orWhereNull('product_status_id');
        });

        if ($categoryId) {
            $q->where('category_id', $categoryId);
        }
        if ($type) {
            $this->applyTypeFilter($q, $type);
        }
        // Apply city filter either from explicit param or from cookies (wow_city)
        $cookieCity = trim((string) $request->cookie('wow_city', ''));
        // If $city is provided (even empty string), use it; else fall back to cookie
        $useCity = ($city !== null) ? trim((string) $city) : ($cookieCity ?: null);
        if ($useCity) {
            $like = '%'.$useCity.'%';
            $q->whereHas('options', function ($oq) use ($like) {
                $oq->where('meta_name', 'locations')
                    ->whereHas('values', function ($vq) use ($like) {
                        $vq->where('value', 'like', $like);
                    });
            });
        }

        // Mode filter: query param takes precedence over cookie; accept alias `format`
        $modeParam = strtolower((string) ($request->filled('mode') ? $request->string('mode') : $request->string('format')));
        $cookieMode = strtolower((string) $request->cookie('wow_mode', ''));
        $mode = in_array($modeParam, ['online', 'in-person', 'all'], true) ? $modeParam : (in_array($cookieMode, ['online', 'in-person', 'all'], true) ? $cookieMode : '');
        if ($mode !== '') {
            $q->whereHas('options', function ($oq) use ($mode) {
                // Target the Locations option only (case-insensitive exact names)
                $oq->where(function ($w) {
                    $w->whereRaw("LOWER(TRIM(COALESCE(meta_name,''))) = 'locations'")
                        ->orWhereRaw("LOWER(TRIM(COALESCE(name,''))) IN ('location(s)','locations')");
                });
                if ($mode === 'online') {
                    // EXACT value 'Online' (case-insensitive). Do not match partials like 'online (live)'.
                    $oq->whereHas('values', function ($vq) {
                        $vq->whereRaw("LOWER(TRIM(COALESCE(value,''))) = 'online'");
                    });
                } elseif ($mode === 'in-person') {
                    // Any non-empty value that is NOT exactly 'Online'
                    $oq->whereHas('values', function ($vq) {
                        $vq->whereRaw("LOWER(TRIM(COALESCE(value,''))) <> 'online'")
                            ->whereRaw("TRIM(COALESCE(value,'')) <> ''");
                    });
                } else {
                    // 'all': just ensure Locations exists (handled above)
                }
            });
        }

        // Anytime (on-demand): no scheduled date/time in meta
        if ($request->boolean('anytime')) {
            $q->whereRaw("(JSON_EXTRACT(meta_json, '$.date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.date')) = '')")
                ->whereRaw("(JSON_EXTRACT(meta_json, '$.start_date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.start_date')) = '')")
                ->whereRaw("(JSON_EXTRACT(meta_json, '$.end_date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.end_date')) = '')");
        }

        // Sorting preference: popular by default
        $sort = strtolower($request->string('sort', 'popular')->toString());
        if ($sort === 'newest') {
            $q->latest('id');
        } elseif ($sort === 'price_asc') {
            $q->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $q->orderBy('price', 'desc');
        } else {
            $q->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')
                ->orderByRaw('COALESCE(reviews_avg_rating, 0) DESC')
                ->orderByRaw('COALESCE(reviews_count, 0) DESC');
        }

        return $q;
    }

    private function transformProduct(Product $p): array
    {
        $locations = method_exists($p, 'getLocations') ? $p->getLocations() : [];
        $isOnline = in_array('Online', $locations, true);
        $physical = array_values(array_filter($locations, fn ($l) => $l !== 'Online'));
        $meta = $p->meta_json ?? [];
        $seo = $this->seo();
        $eventPayload = data_get($meta, 'event', []);
        $primaryDate = $this->extractPrimaryDateValue($eventPayload)
            ?? $this->extractPrimaryDateValue(data_get($meta, 'when.event', []))
            ?? $this->extractPrimaryDateValue($meta);

        return [
            'id' => $p->id,
            'title' => $p->title,
            'seo_title' => trim((string) data_get($meta, 'seo_title', '')),
            'seo_description' => trim((string) data_get($meta, 'seo_description', '')),
            'source_version' => 'legacy',
            'type' => $p->product_type ?: 'experience',
            'format' => $seo->inferFormatKeyFromProduct($p),
            'modality' => $seo->inferModalitySlugFromProduct($p),
            'category' => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name] : null,
            'mode' => $isOnline && count($physical) === 0 ? 'Online' : (count($physical) ? 'In-person' : null),
            'location' => $physical[0] ?? ($isOnline ? 'Online' : null),
            'locations' => $locations,
            'price' => $p->price ?? null,
            'compare_at_price' => $meta['compare_at_price'] ?? null,
            'currency' => $meta['currency'] ?? 'GBP',
            'rating' => round((float) ($p->reviews_avg_rating ?? 0), 1) ?: null,
            'review_count' => (int) ($p->reviews_count ?? 0),
            'image' => method_exists($p, 'getFirstImageUrl') ? $p->getFirstImageUrl() : null,
            'tags' => $p->tags_list ? array_map('trim', explode(',', $p->tags_list)) : [],
            'booking_flow' => $this->legacyProductBookingFlow($p),
            'date' => $primaryDate,
            'start_date' => $primaryDate,
            'event' => is_array($eventPayload) ? $eventPayload : [],
            'url' => $seo->canonicalProductUrl($p),
        ];
    }

    private function extractPrimaryDateValue(mixed $payload): ?string
    {
        $queue = [$payload];
        $visited = 0;

        while ($queue !== [] && $visited < 64) {
            $visited++;
            $current = array_shift($queue);

            if ($current instanceof \Illuminate\Support\Collection) {
                $current = $current->all();
            } elseif ($current instanceof \Traversable) {
                $current = iterator_to_array($current, false);
            } elseif (is_object($current)) {
                $current = (array) $current;
            }

            if (is_string($current) || is_numeric($current)) {
                $candidate = trim((string) $current);
                if ($candidate !== '') {
                    try {
                        return Carbon::parse($candidate)->toDateString();
                    } catch (\Throwable $e) {
                        // continue scanning nested payloads
                    }
                }
                continue;
            }

            if (! is_array($current)) {
                continue;
            }

            foreach (['start_date', 'date', 'starts_at', 'start', 'day'] as $key) {
                $candidate = trim((string) data_get($current, $key, ''));
                if ($candidate === '') {
                    continue;
                }

                try {
                    return Carbon::parse($candidate)->toDateString();
                } catch (\Throwable $e) {
                    // try nested structures below
                }
            }

            foreach (['dates', 'upcoming_dates', 'schedule', 'days', 'event', 'when'] as $key) {
                $nested = data_get($current, $key);
                if ($nested !== null && $nested !== []) {
                    $queue[] = $nested;
                }
            }

            foreach ($current as $nested) {
                if (is_array($nested) || is_object($nested) || $nested instanceof \Traversable) {
                    $queue[] = $nested;
                }
            }
        }

        return null;
    }

    private function typeSegment(Product $p): string
    {
        $t = strtolower((string) $p->product_type);
        $tags = strtolower((string) $p->tags_list);
        if (str_contains($t, 'workshop')) {
            return 'workshops';
        }
        if (str_contains($t, 'event')) {
            return 'events';
        }
        if (str_contains($t, 'class')) {
            return 'classes';
        }
        if (str_contains($t, 'retreat')) {
            return 'retreats';
        }
        if (str_contains($t, 'gift') || str_contains($tags, 'gift')) {
            return 'gifts';
        }

        return 'therapies';
    }

    private function offeringTypeSegment(string $value): string
    {
        $type = strtolower(trim($value));
        if (str_contains($type, 'workshop')) {
            return 'workshops';
        }
        if (str_contains($type, 'event')) {
            return 'events';
        }
        if (str_contains($type, 'class')) {
            return 'classes';
        }
        if (str_contains($type, 'retreat')) {
            return 'retreats';
        }
        if (str_contains($type, 'gift')) {
            return 'gifts';
        }

        return 'therapies';
    }

    public function offering(\Illuminate\Http\Request $request, string $format, string $modality, string $handle)
    {
        $format = strtolower($format);
        if (! in_array($format, self::TYPES, true)) {
            abort(404);
        }

        // Accept forms: "{id}-{slug}", "{id}", or legacy "{handle}"
        $id = null;
        if (preg_match('/^(\d+)(?:-.+)?$/', (string) $handle, $m)) {
            $id = (int) $m[1];
        }

        $product = $this->resolveProductForOffering($id, (string) $handle);
        if (! $product) {
            $draftProduct = $this->resolveProductForOffering($id, (string) $handle, true);
            if ($draftProduct && strtolower((string) ($draftProduct->status?->status ?? '')) === 'draft') {
                return $this->temporaryCategoryRedirect($this->draftOfferingCategoryUrl($draftProduct));
            }

            $offering = $this->resolveV3Offering($id, (string) $handle);
            if (! $offering) {
                $draftOffering = $this->resolveV3Offering($id, (string) $handle, true);
                if ($draftOffering
                    && strtolower((string) $draftOffering->status) === 'draft'
                    && $this->seo()->inferFormatKeyFromOffering($draftOffering) === 'events') {
                    return $this->temporaryCategoryRedirect($this->draftOfferingCategoryUrl($draftOffering));
                }

                abort(404);
            }

            $requestedVariantId = trim((string) $request->query('variant', ''));
            $requestedVariantId = $requestedVariantId !== '' ? $requestedVariantId : null;
            $requestedPriceOptionId = $this->resolveRequestedPriceOptionId($request);
            $productData = $this->transformV3Offering($offering, $format, $requestedVariantId, $requestedPriceOptionId);

            $canonicalPath = trim((string) parse_url((string) ($productData['url'] ?? ''), PHP_URL_PATH), '/');
            if ($canonicalPath !== '' && $canonicalPath !== trim((string) $request->path(), '/')) {
                return $this->redirectWithQuery($request, (string) ($productData['url'] ?? url('/therapies')));
            }

            $relatedArticles = $this->relatedOurVibeArticles($productData);

            return view('offering.show', [
                'type' => $format,
                'product' => $productData,
                'seo' => $this->offeringSeoData($productData),
                'relatedArticles' => $relatedArticles,
            ]);
        }

        $canonicalTarget = $this->seo()->canonicalProductUrl($product);
        $canonicalPath = trim((string) parse_url($canonicalTarget, PHP_URL_PATH), '/');
        if ($canonicalPath !== '' && $canonicalPath !== trim((string) $request->path(), '/')) {
            return $this->redirectWithQuery($request, $canonicalTarget);
        }

        $slug = $this->slugify($product->title ?: (string) $product->id);
        $meta = $product->meta_json ?? [];
        $metaEvent = data_get($meta, 'event', []);
        if ($metaEvent instanceof \Illuminate\Support\Collection) {
            $metaEvent = $metaEvent->all();
        }
        if (is_object($metaEvent)) {
            $metaEvent = (array) $metaEvent;
        }
        if (! is_array($metaEvent)) {
            $metaEvent = [];
        }

        $metaVenueLocations = data_get($meta, 'venue_locations', []);
        if ($metaVenueLocations instanceof \Illuminate\Support\Collection) {
            $metaVenueLocations = $metaVenueLocations->all();
        }
        if (is_object($metaVenueLocations)) {
            $metaVenueLocations = (array) $metaVenueLocations;
        }
        if (! is_array($metaVenueLocations)) {
            $metaVenueLocations = [];
        }

        $metaVenueLocationLabels = array_values(array_filter(array_map(function ($loc) {
            if (is_object($loc)) {
                $loc = (array) $loc;
            }
            if (! is_array($loc)) {
                return null;
            }

            return trim(implode(', ', array_filter([
                (string) ($loc['label'] ?? ''),
                (string) ($loc['city'] ?? $loc['locality'] ?? ''),
                (string) ($loc['region'] ?? $loc['county'] ?? ''),
            ])));
        }, $metaVenueLocations)));

        $metaSummary = trim((string) data_get($meta, 'summary', ''));
        $metaWhatToExpect = trim((string) data_get($meta, 'what_to_expect_md', data_get($meta, 'what_to_expect', '')));
        $metaIncluded = trim((string) data_get($meta, 'included_md', data_get($meta, 'included', '')));
        $metaVideoUrl = trim((string) data_get($meta, 'video_url', data_get($meta, 'video_embed_url', data_get($meta, 'media.video_url', ''))));
        $metaDate = trim((string) data_get($meta, 'date', data_get($metaEvent, 'start_date', '')));
        $metaStartDate = trim((string) data_get($meta, 'start_date', data_get($metaEvent, 'start_date', $metaDate)));
        $metaStartTime = trim((string) data_get($meta, 'start_time', data_get($metaEvent, 'start_time', '')));
        $metaEndDate = trim((string) data_get($meta, 'end_date', data_get($metaEvent, 'end_date', $metaStartDate)));
        $metaEndTime = trim((string) data_get($meta, 'end_time', data_get($metaEvent, 'end_time', '')));
        $metaTimezone = trim((string) data_get($meta, 'timezone', data_get($metaEvent, 'timezone', config('app.timezone', 'UTC'))));
        $locations = $metaVenueLocationLabels ?: $product->getLocations();
        $isOnline = in_array('Online', $locations, true);
        $phys = array_values(array_filter($locations, fn ($l) => $l !== 'Online'));
        $legacyVenueLocations = [];
        try {
            if (Schema::hasTable('vendor_locations')) {
                $legacyVenueLocations = DB::table('vendor_locations')
                    ->where('product_id', $product->id)
                    ->orderBy('id')
                    ->get([
                        'label',
                        'line1',
                        'line2',
                        'city',
                        'county',
                        'postcode',
                        'country',
                        'formatted_address',
                        'lat',
                        'lng',
                    ])
                    ->map(static function ($location): array {
                        return [
                            'label' => trim((string) ($location->label ?? '')),
                            'address_line_1' => trim((string) ($location->line1 ?? '')),
                            'address_line_2' => trim((string) ($location->line2 ?? '')),
                            'city' => trim((string) ($location->city ?? '')),
                            'county' => trim((string) ($location->county ?? '')),
                            'postcode' => trim((string) ($location->postcode ?? '')),
                            'country' => trim((string) ($location->country ?? '')),
                            'formatted_address' => trim((string) ($location->formatted_address ?? '')),
                            'lat' => is_numeric($location->lat ?? null) ? (float) $location->lat : null,
                            'lng' => is_numeric($location->lng ?? null) ? (float) $location->lng : null,
                            'online' => false,
                        ];
                    })
                    ->filter(static fn (array $location): bool => $location['label'] !== '')
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            $legacyVenueLocations = [];
        }
        $legacyVenueLocations = array_map(function (array $location): array {
            if (! empty($location['online']) || (is_numeric($location['lat'] ?? null) && is_numeric($location['lng'] ?? null))) {
                return $location;
            }

            $resolved = $this->geocodeVenueLocation([
                'label' => $location['label'] ?? '',
                'address_line_1' => $location['address_line_1'] ?? '',
                'address_line_2' => $location['address_line_2'] ?? '',
                'city' => $location['city'] ?? '',
                'county' => $location['county'] ?? '',
                'postcode' => $location['postcode'] ?? '',
                'country' => $location['country'] ?? 'United Kingdom',
            ]);
            if (is_array($resolved)) {
                $location['lat'] = $resolved['lat'] ?? $location['lat'] ?? null;
                $location['lng'] = $resolved['lng'] ?? $location['lng'] ?? null;
            }

            return $location;
        }, $legacyVenueLocations);
        $knownLegacyVenueLabels = array_fill_keys(array_map(
            static fn (array $location): string => strtolower(trim((string) ($location['label'] ?? ''))),
            $legacyVenueLocations
        ), true);
        foreach ($phys as $locationLabel) {
            $locationLabel = trim((string) $locationLabel);
            $locationKey = strtolower($locationLabel);
            if ($locationLabel === '' || isset($knownLegacyVenueLabels[$locationKey])) {
                continue;
            }

            $resolved = $this->geocodeVenueLocation([
                'label' => $locationLabel,
                'country' => 'United Kingdom',
            ]);
            $legacyVenueLocations[] = [
                'label' => $locationLabel,
                'address_line_1' => '',
                'address_line_2' => '',
                'city' => '',
                'county' => '',
                'postcode' => '',
                'country' => 'United Kingdom',
                'formatted_address' => $locationLabel.', United Kingdom',
                'lat' => is_array($resolved) ? ($resolved['lat'] ?? null) : null,
                'lng' => is_array($resolved) ? ($resolved['lng'] ?? null) : null,
                'online' => false,
            ];
            $knownLegacyVenueLabels[$locationKey] = true;
        }
        if ($isOnline) {
            $legacyVenueLocations[] = [
                'label' => 'Online session',
                'address_line_1' => '',
                'address_line_2' => '',
                'city' => '',
                'county' => '',
                'postcode' => '',
                'country' => '',
                'formatted_address' => 'Live session link sent after booking',
                'lat' => null,
                'lng' => null,
                'online' => true,
            ];
        }
        $images = $product->media->map(function ($m) {
            $url = (string) ($m->media_url ?? '');
            if ($url === '') {
                return null;
            }
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                return $url;
            }
            $backend = rtrim((string) env('BACKEND_ASSET_URL', env('BACKEND_URL', '')), '/');
            $clean = ltrim($url, '/');

            return $backend ? $backend.'/storage/'.$clean : asset('storage/'.$clean);
        })->filter()->values();

        // Eager-load options + values + variants for the buy box
        $product->loadMissing(['options.values', 'variants']);
        $priceOptionId = $this->resolveRequestedPriceOptionId($request);
        $variantLabel = $this->resolveRequestedVariantLabel($product, $request);

        // Build options array and a lookup of value id -> label for variant option_ids mapping
        $valueLookup = [];
        $optionsArr = $product->options->map(function ($o) use (&$valueLookup) {
            $vals = $o->values->pluck('value', 'id')->all();
            foreach ($vals as $id => $label) {
                $valueLookup[$id] = $label;
            }

            return [
                'name' => $o->name ?? $o->meta_name,
                'meta_name' => $o->meta_name,
                'values' => array_values($vals),
            ];
        })->values()->all();

        $variantsArr = $product->variants->map(function ($v) use ($valueLookup) {
            // Prefer explicit options array; else derive from option_ids
            $optList = [];
            if (is_array($v->options) && count($v->options)) {
                $optList = array_values($v->options);
            } elseif (is_string($v->options) && ! empty($v->options)) {
                // Some variants store options as a JSON string like {"option1":"1","option2":"3","option3":"Online"}
                $decoded = json_decode($v->options, true);
                if (is_array($decoded) && ! empty($decoded)) {
                    // Keep natural option ordering by key name if present
                    // Accept option1, option2, option3... else fallback to array_values
                    $ordered = [];
                    foreach (['option1', 'option2', 'option3', 'option4', 'option5'] as $k) {
                        if (array_key_exists($k, $decoded)) {
                            $ordered[] = $decoded[$k];
                            unset($decoded[$k]);
                        }
                    }
                    $optList = array_values(array_merge($ordered, $decoded));
                }
            } elseif (! empty($v->option_ids)) {
                $ids = json_decode($v->option_ids, true) ?: [];
                foreach ($ids as $oid) {
                    if (isset($valueLookup[$oid])) {
                        $optList[] = $valueLookup[$oid];
                    }
                }
            }

            return [
                'id' => $v->id,
                'options' => $optList,
                'price' => $v->price, // pounds or pence; frontend normalizes
                'compare' => $v->metadata['compare_at_price'] ?? null,
                'available' => ($v->inventory_quantity ?? 0) > 0 || is_null($v->inventory_quantity),
            ];
        })->values()->all();

        // Fallback: if variants have no option labels but there is a Sessions option, infer mapping by price order vs session duration order
        try {
            $allEmpty = true;
            foreach ($variantsArr as $vv) {
                if (! empty($vv['options'])) {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty && is_array($optionsArr) && count($optionsArr)) {
                // Attempt a full cartesian combination from option values first
                $combos = [[]];
                foreach ($optionsArr as $idx => $opt) {
                    $vals = $opt['values'] ?? [];
                    if (empty($vals)) {
                        foreach ($combos as &$combo) {
                            $combo[$idx] = '';
                        }
                        unset($combo);

                        continue;
                    }
                    $expanded = [];
                    foreach ($combos as $combo) {
                        foreach ($vals as $val) {
                            $next = $combo;
                            $next[$idx] = (string) $val;
                            $expanded[] = $next;
                        }
                    }
                    $combos = $expanded;
                    if (count($combos) > 300) {
                        $combos = [];
                        break;
                    }
                }

                if (count($combos) > 0 && count($combos) === count($variantsArr)) {
                    $optionCount = count($optionsArr);
                    foreach ($variantsArr as $idx => $variantData) {
                        $combo = $combos[$idx] ?? [];
                        $normalized = [];
                        for ($oi = 0; $oi < $optionCount; $oi++) {
                            $normalized[$oi] = (string) ($combo[$oi] ?? ($optionsArr[$oi]['values'][0] ?? ''));
                        }
                        $variantsArr[$idx]['options'] = $normalized;
                    }
                } else {
                    $peopleIdx = null;
                    $sessionsIdx = null;
                    $locIdx = null;
                    foreach ($optionsArr as $i => $opt) {
                        $nm = strtolower(trim((string) ($opt['meta_name'] ?? $opt['name'] ?? '')));
                        if ($peopleIdx === null && str_contains($nm, 'person')) {
                            $peopleIdx = $i;
                        }
                        if ($sessionsIdx === null && str_contains($nm, 'session')) {
                            $sessionsIdx = $i;
                        }
                        if ($locIdx === null && str_contains($nm, 'location')) {
                            $locIdx = $i;
                        }
                    }
                    if ($sessionsIdx !== null) {
                        $sessionVals = $optionsArr[$sessionsIdx]['values'] ?? [];
                        // Parse labels to a numeric score in minutes
                        $score = function ($label) {
                            $s = strtolower(trim((string) $label));
                            if (preg_match('/(\d+(?:\.\d+)?)\s*(hour|hr|hours|hrs)/', $s, $m)) {
                                return (float) $m[1] * 60;
                            }
                            if (preg_match('/(\d+(?:\.\d+)?)\s*(min|mins|minute|minutes)/', $s, $m)) {
                                return (float) $m[1];
                            }
                            if (preg_match('/\d+/', $s, $m)) {
                                return (float) $m[0];
                            }

                            return 0.0;
                        };
                        $pairs = [];
                        foreach ($sessionVals as $lbl) {
                            $pairs[] = ['label' => $lbl, 'n' => $score($lbl)];
                        }
                        usort($pairs, function ($a, $b) {
                            return $a['n'] <=> $b['n'];
                        });
                        $sortedSession = array_map(fn ($p) => $p['label'], $pairs);

                        // Sort variants by numeric price ascending
                        $sortedVariants = $variantsArr;
                        usort($sortedVariants, function ($a, $b) {
                            return (float) $a['price'] <=> (float) $b['price'];
                        });

                        // Prepare default labels for people and location (first values if available)
                        $peopleLabel = ($peopleIdx !== null && isset($optionsArr[$peopleIdx]['values'][0])) ? (string) $optionsArr[$peopleIdx]['values'][0] : '';
                        $locLabel = ($locIdx !== null && isset($optionsArr[$locIdx]['values'][0])) ? (string) $optionsArr[$locIdx]['values'][0] : '';
                        $optCount = count($optionsArr);

                        // Map by index
                        $mapped = [];
                        foreach ($sortedVariants as $i => $sv) {
                            $sessionLabel = (string) ($sortedSession[$i] ?? ($sessionVals[$i] ?? ''));
                            $optList = array_fill(0, $optCount, '');
                            if ($peopleIdx !== null) {
                                $optList[$peopleIdx] = $peopleLabel;
                            }
                            if ($sessionsIdx !== null) {
                                $optList[$sessionsIdx] = $sessionLabel;
                            }
                            if ($locIdx !== null) {
                                $optList[$locIdx] = $locLabel;
                            }
                            $sv['options'] = $optList;
                            $mapped[$sv['id']] = $sv;
                        }
                        // Rebuild in original order with mapped options
                        foreach ($variantsArr as $k => $vv) {
                            if (isset($mapped[$vv['id']])) {
                                $variantsArr[$k] = $mapped[$vv['id']];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) { /* swallow fallback errors */
        }

        $selectedLegacyVariant = $priceOptionId !== null
            ? collect($variantsArr)->first(fn (array $variant): bool => (int) ($variant['id'] ?? 0) === (int) $priceOptionId)
            : null;
        $selectedLegacyVariantSelection = is_array($selectedLegacyVariant['options'] ?? null)
            ? array_values(array_filter(array_map('trim', $selectedLegacyVariant['options'])))
            : [];
        $selectedLegacyVariantLabel = $variantLabel
            ?: (is_array($selectedLegacyVariant) ? implode(' • ', $selectedLegacyVariantSelection) : null);

        $vendor = $product->vendor;
        $clientReviews = [];
        $vendorReviewCount = 0;
        $vendorRatingAvg = null;
        if ($vendor) {
            $vendorReviewBase = Review::query()
                ->where('vendor_id', $vendor->id)
                ->whereRaw("TRIM(COALESCE(review_text, '')) <> ''");

            $vendorReviewCount = (int) (clone $vendorReviewBase)->count();
            if ($vendorReviewCount > 0) {
                $vendorRatingAvg = round((float) ((clone $vendorReviewBase)->avg('rating') ?? 0), 1);
            }

            $clientReviews = (clone $vendorReviewBase)
                ->with('user')
                ->latest('created_at')
                ->take(6)
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => (int) ($review->rating ?? 0),
                        'body' => trim((string) $review->review_text),
                        'author' => optional($review->user)->name ?? 'Verified client',
                        'date' => optional($review->created_at)->format('M Y') ?? '',
                        'title' => trim((string) ($review->title ?? '')),
                        'user_id' => $review->user_id,
                    ];
                })->values()->all();
        }
        $ratingFallback = round((float) ($product->reviews_avg_rating ?? 0), 1) ?: null;
        $countFallback = (int) ($product->reviews_count ?? 0);
        if ($vendorReviewCount > 0 && $vendorRatingAvg !== null) {
            $ratingFallback = $vendorRatingAvg;
            $countFallback = $vendorReviewCount;
        }
        $metaSafety = $meta['safety_notes'] ?? ($meta['safety'] ?? '');
        $metaContra = $meta['contraindications'] ?? '';
        $metaBenefits = $meta['benefits'] ?? [];
        $metaWhoFor = $meta['who_for'] ?? [];
        $metaWhoNot = $meta['who_not_for'] ?? [];
        $metaFaq = $meta['faq'] ?? [];
        $metaAftercare = $meta['aftercare'] ?? ($meta['after_care'] ?? '');

        $data = [
            'id' => $product->id,
            'title' => $product->title,
            'source_version' => 'legacy',
            'booking_flow' => $this->legacyProductBookingFlow($product, $priceOptionId, $variantLabel),
            'type' => $product->product_type ?: 'experience',
            'category' => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            'rating' => $ratingFallback,
            'review_count' => $countFallback,
            'vendor_rating' => $vendorRatingAvg,
            'vendor_review_count' => $vendorReviewCount,
            'price' => $product->price ?? null,
            'price_min' => $product->variants_min_price ?? ($product->price ?? null),
            'price_max' => $product->variants_max_price ?? ($product->price ?? null),
            'compare_at_price' => $meta['compare_at_price'] ?? null,
            'currency' => $meta['currency'] ?? 'GBP',
            'image' => $product->getFirstImageUrl(),
            'images' => $images,
            'options' => $optionsArr,
            'variants' => $variantsArr,
            'selectedVariantId' => $selectedLegacyVariant['id'] ?? ($variantsArr[0]['id'] ?? null),
            'selectedVariantLabel' => $selectedLegacyVariantLabel,
            'selectedVariantSelection' => $selectedLegacyVariantSelection,
            'mode' => $isOnline && count($phys) === 0 ? 'Online' : (count($phys) ? 'In-person' : null),
            'location' => $phys[0] ?? ($isOnline ? 'Online' : (trim((string) data_get($meta, 'location', data_get($meta, 'venue.name', ''))) ?: null)),
            'locations' => $locations,
            'description' => (string) ($product->description ?? ''),
            'summary' => $metaSummary !== '' ? $metaSummary : (string) ($product->summary ?? ''),
            'body_html' => (string) ($product->body_html ?? ''),
            'what_to_expect' => $metaWhatToExpect !== '' ? $metaWhatToExpect : (string) ($product->what_to_expect ?? ''),
            'included' => $metaIncluded !== '' ? $metaIncluded : (string) ($product->included ?? ''),
            'aftercare' => (string) $metaAftercare,
            'duration' => $meta['duration'] ?? $meta['duration_minutes'] ?? null,
            'tags' => $product->tags_list ? array_values(array_filter(array_map('trim', explode(',', $product->tags_list)))) : [],
            'benefits' => is_array($metaBenefits) ? array_values(array_filter($metaBenefits)) : [],
            'who_for' => is_array($metaWhoFor) ? array_values(array_filter($metaWhoFor)) : [],
            'who_not_for' => is_array($metaWhoNot) ? array_values(array_filter($metaWhoNot)) : [],
            'faq' => is_array($metaFaq) ? array_values(array_filter($metaFaq)) : [],
            'safety_notes' => (string) $metaSafety,
            'contraindications' => (string) $metaContra,
            'date' => $metaDate !== '' ? $metaDate : null,
            'start_date' => $metaStartDate !== '' ? $metaStartDate : null,
            'start_time' => $metaStartTime !== '' ? $metaStartTime : null,
            'end_date' => $metaEndDate !== '' ? $metaEndDate : null,
            'end_time' => $metaEndTime !== '' ? $metaEndTime : null,
            'timezone' => $metaTimezone,
            'capacity' => data_get($metaEvent, 'capacity', data_get($meta, 'capacity')),
            'event' => $metaEvent,
            'venue_locations' => $metaVenueLocations ?: $legacyVenueLocations,
            'video_url' => $metaVideoUrl,
            'practitioner' => $this->practitionerPayload($vendor, $vendor?->user),
            'reviews' => $product->reviews->map(function ($r) {
                return [
                    'id' => $r->id,
                    'rating' => (int) ($r->rating ?? 0),
                    'review' => (string) ($r->review ?? ''),
                    'user' => $r->user ? [
                        'id' => $r->user->id,
                        'name' => trim(($r->user->name ?? '') ?: ($r->user->email ?? 'User')),
                    ] : null,
                    'created_at' => optional($r->created_at)->toIso8601String(),
                ];
            })->values(),
            'client_reviews' => $clientReviews,
            'url' => $this->seo()->canonicalProductUrl($product),
            'booking_variant_label' => $variantLabel,
            'is_past_event' => EventListing::isPast($product),
        ];

        $relatedArticles = $this->relatedOurVibeArticles($data);

        return view('offering.show', [
            'type' => $format,
            'product' => $data,
            'seo' => $this->offeringSeoData($data),
            'relatedArticles' => $relatedArticles,
        ]);
    }

    private function offeringSeoData(array $product): array
    {
        $title = trim((string) ($product['title'] ?? 'Offering'));
        $seoTitle = trim((string) ($product['seo_title'] ?? ''));
        $descriptionSource = trim((string) strip_tags((string) (
            $product['seo_description']
            ?? $product['summary']
            ?? $product['what_to_expect']
            ?? $product['description']
            ?? $product['body_html']
            ?? ''
        )));
        $descriptionSource = preg_replace('/\s+/', ' ', $descriptionSource) ?? $descriptionSource;
        $description = $descriptionSource !== ''
            ? \Illuminate\Support\Str::limit($descriptionSource, 160, '…')
            : ('Book '.$title.' with trusted practitioners at We Offer Wellness®.');

        return [
            'title' => $seoTitle !== '' ? $seoTitle : $title.' | We Offer Wellness®',
            'description' => $description,
            'canonical' => trim((string) ($product['url'] ?? url()->current())),
            'og_image' => $this->resolveOfferingSeoImage($product),
            'og_image_alt' => $title,
            'site_name' => 'We Offer Wellness®',
            'twitter_card' => 'summary_large_image',
            'og_type' => 'product',
        ];
    }

    private function resolveOfferingSeoImage(array $product): string
    {
        $imageCandidates = [];

        foreach (['cover_image', 'image'] as $key) {
            if (! empty($product[$key])) {
                $imageCandidates[] = $product[$key];
            }
        }

        if (! empty($product['images']) && is_array($product['images'])) {
            $imageCandidates = array_merge($imageCandidates, $product['images']);
        }

        foreach ($imageCandidates as $candidate) {
            $resolved = $this->normalizeOfferingSeoImageCandidate($candidate);
            if ($resolved !== '') {
                return $resolved;
            }
        }

        return asset('images/default-social-preview.jpg');
    }

    private function normalizeOfferingSeoImageCandidate(mixed $candidate): string
    {
        $value = '';

        if (is_array($candidate)) {
            foreach (['url', 'src', 'path', 'original_url', 'original', 'media_url', 'image'] as $key) {
                $raw = trim((string) data_get($candidate, $key, ''));
                if ($raw !== '') {
                    $value = $raw;
                    break;
                }
            }
        } elseif (is_object($candidate)) {
            foreach (['url', 'src', 'path', 'original_url', 'original', 'media_url', 'image'] as $key) {
                $raw = trim((string) data_get($candidate, $key, ''));
                if ($raw !== '') {
                    $value = $raw;
                    break;
                }
            }
        } else {
            $value = trim((string) $candidate);
        }

        if ($value === '') {
            return '';
        }

        if (str_contains($value, 'no-product-image.jpg')) {
            return '';
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        return url('/'.ltrim($value, '/'));
    }

    private function resolveV3Offering(?int $id, string $handle, bool $includeUnpublished = false): ?OfferingV3
    {
        $canonicalId = false;
        if (preg_match('/^offering-(\d+)(?:-.+)?$/', trim($handle), $canonicalMatch)) {
            $id = (int) $canonicalMatch[1];
            $canonicalId = true;
        } elseif (str_starts_with(trim($handle), 'product-')) {
            return null;
        }

        // Canonical title slugs can legitimately begin with a number (for
        // example "21-day-...", "7-breathwork-..." or "11-..." from
        // "1:1"). Resolve that complete slug before treating its prefix as a
        // legacy database ID. Genuine "{id}-{old-slug}" URLs still fall back
        // to the ID lookup below when no matching title/slug exists.
        if ($id && ! $canonicalId && ! ctype_digit(trim($handle))) {
            $matchedByHandle = $this->resolveV3Offering(null, $handle, $includeUnpublished);
            if ($matchedByHandle) {
                return $matchedByHandle;
            }
        }

        $query = OfferingV3::query()->with(['category', 'type', 'vendor.user.tier', 'vendor.locations', 'media', 'coverMedia']);

        if ($id) {
            $query->where('id', $id);
        } else {
            $normalizedHandle = $this->slugify($handle);
            $query->where(function ($builder) use ($handle, $normalizedHandle): void {
                $builder->where('slug', $handle)
                    ->orWhere('slug', $normalizedHandle)
                    ->orWhere('title', $handle)
                    ->orWhere('title', $normalizedHandle);
            });
        }

        $offering = $query->first();
        if ($offering && ($includeUnpublished || in_array((string) $offering->status, ['live', 'approved'], true))) {
            return $offering;
        }

        if ($id) {
            return null;
        }

        $normalizedHandle = $this->slugify($handle);
        $normalizedLookupKey = $this->normalizeLookupKey($handle);

        foreach ($this->candidateLookupTokenSets($handle) as $tokens) {
            $candidates = OfferingV3::query()
                ->with(['category', 'type', 'vendor.user.tier', 'vendor.locations', 'media', 'coverMedia'])
                ->where(function ($builder) use ($tokens): void {
                    foreach ($tokens as $token) {
                        $builder->where(function ($tokenBuilder) use ($token): void {
                            $like = '%'.$token.'%';
                            $tokenBuilder->whereRaw('LOWER(COALESCE(slug, "")) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(COALESCE(title, "")) LIKE ?', [$like]);
                        });
                    }
                })
                ->get();

            if ($candidates->isEmpty()) {
                continue;
            }

            $preferred = $candidates->first(function (OfferingV3 $candidate) use ($normalizedHandle, $normalizedLookupKey): bool {
                return $this->normalizeLookupKey((string) ($candidate->slug ?? '')) === $normalizedLookupKey
                    || $this->normalizeLookupKey((string) ($candidate->title ?? '')) === $normalizedLookupKey
                    || $this->slugify((string) ($candidate->slug ?? '')) === $normalizedHandle
                    || $this->slugify((string) ($candidate->title ?? '')) === $normalizedHandle;
            });

            if ($preferred && ($includeUnpublished || in_array((string) $preferred->status, ['live', 'approved'], true))) {
                return $preferred;
            }
        }

        return null;
    }

    private function mapboxCountryCode(string $country): string
    {
        $country = strtolower(trim($country));

        if (in_array($country, ['gb', 'uk', 'u.k.', 'united kingdom', 'great britain', 'england', 'scotland', 'wales', 'northern ireland'], true)) {
            return 'gb';
        }

        return '';
    }

    private function geocodeVenueLocation(array $location): ?array
    {
        $query = trim(implode(', ', array_filter([
            trim((string) ($location['label'] ?? '')),
            trim((string) ($location['address_line_1'] ?? '')),
            trim((string) ($location['address_line_2'] ?? '')),
            trim((string) ($location['city'] ?? '')),
            trim((string) ($location['county'] ?? '')),
            trim((string) ($location['postcode'] ?? '')),
            trim((string) ($location['country'] ?? '')),
        ])));

        if ($query === '') {
            return null;
        }

        $cacheKey = 'wow.offering.geocode.'.md5(Str::lower($query));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query, $location) {
            $token = trim((string) config('services.mapbox.token'));
            if ($token === '') {
                return null;
            }

            try {
                $params = [
                    'access_token' => $token,
                    'limit' => 1,
                    'autocomplete' => 'false',
                    'types' => 'address,place,poi,locality,neighborhood,postcode,region,district',
                ];

                $country = $this->mapboxCountryCode((string) ($location['country'] ?? ''));
                if ($country !== '') {
                    $params['country'] = $country;
                }

                $response = Http::timeout(8)->get(
                    'https://api.mapbox.com/geocoding/v5/mapbox.places/'.rawurlencode($query).'.json',
                    $params
                );

                if (! $response->ok()) {
                    return null;
                }

                $feature = $response->json('features.0');
                if (! is_array($feature)) {
                    return null;
                }

                $center = $feature['center'] ?? ($feature['geometry']['coordinates'] ?? null);
                if (! is_array($center) || ! isset($center[0], $center[1])) {
                    return null;
                }

                return [
                    'lat' => (float) $center[1],
                    'lng' => (float) $center[0],
                ];
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    private function transformV3Offering(OfferingV3 $offering, string $type, ?string $requestedVariantId = null, ?int $requestedPriceOptionId = null): array
    {
        $channels = DB::table('offering_channels')
            ->where('offering_id', $offering->id)
            ->where('is_enabled', true)
            ->pluck('channel')
            ->all();
        $locations = method_exists($offering, 'getLocations') ? $offering->getLocations() : [];
        $isOnline = in_array('Online', $locations, true);
        $physical = array_values(array_filter($locations, fn ($l) => $l !== 'Online'));
        $images = $offering->media
            ->map(function ($m) {
                $url = (string) ($m->media_url ?? '');
                if ($url === '') {
                    return null;
                }
                if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                    return $url;
                }
                $backend = rtrim((string) env('BACKEND_ASSET_URL', env('BACKEND_URL', '')), '/');
                $clean = ltrim($url, '/');

                return $backend ? $backend.'/storage/'.$clean : asset('storage/'.$clean);
            })
            ->filter()
            ->values()
            ->all();

        $meta = DB::table('offering_meta')->where('offering_id', $offering->id)->first();
        $details = DB::table('offering_details')->where('offering_id', $offering->id)->first();
        $schedule = DB::table('offering_schedule')->where('offering_id', $offering->id)->first();
        $eventPayload = (array) ($offering->event ?? []);
        $eventPayloadDate = $this->extractPrimaryDateValue($eventPayload);
        $descriptionHtml = \App\Support\ContentFormatter::format((string) ($details->description ?? ''));
        $whatToExpectHtml = \App\Support\ContentFormatter::format((string) ($details->what_to_expect ?? ''));
        $includedHtml = \App\Support\ContentFormatter::format((string) ($details->whats_included ?? ''));
        $venueLocations = [];
        if (Schema::hasTable('offering_locations')) {
            $venueLocations = DB::table('offering_locations')
                ->where('offering_id', $offering->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get([
                    'label',
                    'address_line_1',
                    'address_line_2',
                    'city',
                    'county',
                    'postcode',
                    'country',
                    'lat',
                    'lng',
                    'notes',
                ])
                ->map(function ($row) {
                    $label = trim((string) ($row->label ?? ''));
                    $addressLine1 = trim((string) ($row->address_line_1 ?? ''));
                    $addressLine2 = trim((string) ($row->address_line_2 ?? ''));
                    $city = trim((string) ($row->city ?? ''));
                    $county = trim((string) ($row->county ?? ''));
                    $postcode = trim((string) ($row->postcode ?? ''));
                    $country = trim((string) ($row->country ?? ''));
                    $lat = is_numeric($row->lat ?? null) ? (float) $row->lat : null;
                    $lng = is_numeric($row->lng ?? null) ? (float) $row->lng : null;

                    if ($lat === null || $lng === null) {
                        $resolved = $this->geocodeVenueLocation([
                            'label' => $label,
                            'address_line_1' => $addressLine1,
                            'address_line_2' => $addressLine2,
                            'city' => $city,
                            'county' => $county,
                            'postcode' => $postcode,
                            'country' => $country,
                        ]);

                        if (is_array($resolved)) {
                            $lat = $resolved['lat'] ?? $lat;
                            $lng = $resolved['lng'] ?? $lng;
                        }
                    }

                    return [
                        'label' => $label !== '' ? $label : trim(implode(', ', array_filter([$addressLine1, $city]))),
                        'address_line_1' => $addressLine1,
                        'address_line_2' => $addressLine2,
                        'city' => $city,
                        'county' => $county,
                        'postcode' => $postcode,
                        'country' => $country,
                        'lat' => $lat,
                        'lng' => $lng,
                        'notes' => trim((string) ($row->notes ?? '')),
                    ];
                })
                ->filter(fn (array $location) => trim((string) ($location['label'] ?? '')) !== '')
                ->values()
                ->all();
        }
        $firstOccurrence = DB::table('offering_fixed_occurrences')
            ->where('offering_id', $offering->id)
            ->orderBy('starts_at')
            ->first();
        $priceOptions = DB::table('offering_price_options')
            ->where('offering_id', $offering->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $priceTiers = DB::table('offering_price_tiers')
            ->whereIn('price_option_id', $priceOptions->pluck('id'))
            ->get();
        $isEventType = str_contains(strtolower((string) ($offering->type?->name ?? '')), 'event');

        $extractPriceOptionId = static function (?string $variantId): ?int {
            $variantId = trim((string) $variantId);
            if ($variantId === '') {
                return null;
            }

            if (preg_match('/^po_(\d+)(?:_tier_(\d+))?$/', $variantId, $matches)) {
                return (int) $matches[1];
            }

            if (preg_match('/^(\d+)$/', $variantId, $matches)) {
                return (int) $matches[1];
            }

            return null;
        };

        $selectVariantCard = static function (array $variantCards, ?string $requestedVariantId, ?int $requestedPriceOptionId) use ($extractPriceOptionId): array {
            if (empty($variantCards)) {
                return [];
            }

            $selectedVariantCard = $variantCards[0];
            $requestedVariantId = trim((string) $requestedVariantId);

            if ($requestedVariantId !== '') {
                foreach ($variantCards as $variantCard) {
                    if (strcasecmp((string) ($variantCard['id'] ?? ''), $requestedVariantId) === 0) {
                        $selectedVariantCard = $variantCard;
                        break;
                    }
                }
            }

            if ($requestedPriceOptionId !== null) {
                foreach ($variantCards as $variantCard) {
                    $variantPriceOptionId = $variantCard['price_option_id'] ?? $extractPriceOptionId((string) ($variantCard['id'] ?? ''));
                    if ($variantPriceOptionId !== null && (int) $variantPriceOptionId === (int) $requestedPriceOptionId) {
                        $selectedVariantCard = $variantCard;
                        break;
                    }
                }
            }

            return $selectedVariantCard;
        };

        $venueLocations = [];
        if (Schema::hasTable('offering_locations')) {
            $venueLocations = DB::table('offering_locations')
                ->where('offering_id', $offering->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get([
                    'label',
                    'address_line_1',
                    'address_line_2',
                    'city',
                    'county',
                    'postcode',
                    'country',
                    'lat',
                    'lng',
                    'notes',
                ])
                ->map(function ($row) {
                    $label = trim((string) ($row->label ?? ''));
                    $addressLine1 = trim((string) ($row->address_line_1 ?? ''));
                    $addressLine2 = trim((string) ($row->address_line_2 ?? ''));
                    $city = trim((string) ($row->city ?? ''));
                    $county = trim((string) ($row->county ?? ''));
                    $postcode = trim((string) ($row->postcode ?? ''));
                    $country = trim((string) ($row->country ?? ''));
                    $lat = is_numeric($row->lat ?? null) ? (float) $row->lat : null;
                    $lng = is_numeric($row->lng ?? null) ? (float) $row->lng : null;

                    if ($lat === null || $lng === null) {
                        $resolved = $this->geocodeVenueLocation([
                            'label' => $label,
                            'address_line_1' => $addressLine1,
                            'address_line_2' => $addressLine2,
                            'city' => $city,
                            'county' => $county,
                            'postcode' => $postcode,
                            'country' => $country,
                        ]);

                        if (is_array($resolved)) {
                            $lat = $resolved['lat'] ?? $lat;
                            $lng = $resolved['lng'] ?? $lng;
                        }
                    }

                    return [
                        'label' => $label !== '' ? $label : trim(implode(', ', array_filter([$city, $county, $postcode]))),
                        'address_line_1' => $addressLine1,
                        'address_line_2' => $addressLine2,
                        'city' => $city,
                        'county' => $county,
                        'postcode' => $postcode,
                        'country' => $country,
                        'lat' => $lat,
                        'lng' => $lng,
                        'notes' => trim((string) ($row->notes ?? '')),
                    ];
                })
                ->filter(fn (array $location) => trim((string) ($location['label'] ?? '')) !== '')
                ->values()
                ->all();
        }

        if ($isEventType) {
            $startDate = null;
            $endDate = null;
            $startTime = null;
            $endTime = null;
            if ($firstOccurrence?->starts_at) {
                $startAt = Carbon::parse($firstOccurrence->starts_at, (string) ($schedule->timezone ?? 'Europe/London'));
                $endAt = $firstOccurrence->ends_at
                    ? Carbon::parse($firstOccurrence->ends_at, (string) ($schedule->timezone ?? 'Europe/London'))
                    : null;
                $startDate = $startAt->toDateString();
                $endDate = $endAt ? $endAt->toDateString() : $startDate;
                $startTime = $startAt->format('H:i');
                $endTime = $endAt ? $endAt->format('H:i') : $startTime;
            }

            if ($startDate === null && $eventPayloadDate !== null) {
                $startDate = $eventPayloadDate;
                $endDate = $eventPayloadDate;
            }

            $eventDates = [];
            if ($startDate) {
                $eventDates = [];
                $cursor = Carbon::parse($startDate, (string) ($schedule->timezone ?? 'Europe/London'))->startOfDay();
                $limit = Carbon::parse($endDate ?: $startDate, (string) ($schedule->timezone ?? 'Europe/London'))->startOfDay();
                $safety = 0;
                while ($cursor->lte($limit) && $safety < 366) {
                    $eventDates[] = [
                        'index' => count($eventDates) + 1,
                        'date' => $cursor->toDateString(),
                        'label' => $cursor->format('D j M'),
                    ];
                    $cursor->addDay();
                    $safety++;
                }
            }

            $eventPriceOptions = $priceOptions
                ->filter(fn ($option) => strtolower(trim((string) ($option->audience_type ?? ''))) === 'event')
                ->sortBy('sort_order')
                ->values();
            $ticketOption = $eventPriceOptions->first(fn ($option) => strtolower(trim((string) ($option->pricing_type ?? ''))) === 'ticket');
            $dayOptions = $eventPriceOptions->filter(fn ($option) => strtolower(trim((string) ($option->pricing_type ?? ''))) === 'day')->values();

            $eventVariants = [];
            if ($ticketOption || $eventPriceOptions->isEmpty()) {
                $ticketPrice = $ticketOption?->price_amount;
                if ($ticketPrice === null) {
                    $ticketPrice = $dayOptions->first()?->price_amount ?? $offering->price ?? 0;
                }
                $eventVariants[] = [
                    'id' => $ticketOption ? 'po_'.(string) $ticketOption->id : 'event_ticket',
                    'options' => ['Full event ticket'],
                    'selection' => ['Full event ticket'],
                    'price' => (float) $ticketPrice,
                    'compare' => null,
                    'available' => true,
                ];
            }

            if ($dayOptions->isNotEmpty()) {
                foreach ($dayOptions as $index => $option) {
                    $dayMeta = $eventDates[$index] ?? null;
                    $eventVariants[] = [
                        'id' => 'po_'.(string) $option->id,
                        'options' => [
                            $dayMeta ? ('Day '.$dayMeta['index'].' · '.$dayMeta['label']) : ('Day '.($index + 1)),
                        ],
                        'selection' => [
                            $dayMeta ? ('Day '.$dayMeta['index'].' · '.$dayMeta['label']) : ('Day '.($index + 1)),
                        ],
                        'price' => (float) ($option->price_amount ?? $offering->price ?? 0),
                        'compare' => null,
                        'available' => true,
                    ];
                }
            }

            if (empty($eventVariants)) {
                $fallbackPrice = $offering->price;
                if ($fallbackPrice === null) {
                    $firstOptionPrice = $priceOptions->first()?->price_amount;
                    $fallbackPrice = is_numeric($firstOptionPrice) ? (float) $firstOptionPrice : 0.0;
                }

                $eventVariants[] = [
                    'id' => 'event_ticket',
                    'options' => ['Full event ticket'],
                    'selection' => ['Full event ticket'],
                    'price' => (float) $fallbackPrice,
                    'compare' => null,
                    'available' => true,
                ];
            }

            $optionValues = array_values(array_unique(array_filter(array_map(
                fn ($variant) => (string) ($variant['options'][0] ?? ''),
                $eventVariants
            ))));
            if (empty($optionValues)) {
                $optionValues = ['Full event ticket'];
            }

            $minPrice = collect([$offering->price])
                ->merge($priceOptions->pluck('price_amount'))
                ->merge($priceTiers->pluck('price_amount'))
                ->filter(fn ($v) => is_numeric($v))
                ->map(fn ($v) => (float) $v)
                ->min();
            $maxPrice = collect([$offering->price])
                ->merge($priceOptions->pluck('price_amount'))
                ->merge($priceTiers->pluck('price_amount'))
                ->filter(fn ($v) => is_numeric($v))
                ->map(fn ($v) => (float) $v)
                ->max();

            $ticketOptionMeta = [
                'name' => 'Ticket type',
                'meta_name' => 'ticket',
                'values' => $optionValues,
            ];

            $selectedVariantCard = $selectVariantCard($eventVariants, $requestedVariantId, $requestedPriceOptionId);
            $selectedVariantId = (string) ($selectedVariantCard['id'] ?? ($eventVariants[0]['id'] ?? 'event_ticket'));
            $selectedVariantSelection = array_values(array_filter(array_map('trim', (array) ($selectedVariantCard['selection'] ?? ($selectedVariantCard['options'] ?? [])))));
            $selectedVariantLabel = trim((string) ($selectedVariantCard['label'] ?? (empty($selectedVariantSelection) ? ($selectedVariantCard['options'][0] ?? 'Full event ticket') : implode(' • ', $selectedVariantSelection))));
            if ($selectedVariantLabel === '') {
                $selectedVariantLabel = 'Full event ticket';
            }
            $selectedVariantPrice = (float) ($selectedVariantCard['price'] ?? $offering->price ?? 0);
            $selectedVariantPriceOptionId = $extractPriceOptionId($selectedVariantId);
            $isPastEvent = EventListing::isPast([
                'start_date' => $startDate,
                'start_time' => $startTime,
                'end_date' => $endDate,
                'end_time' => $endTime,
                'timezone' => (string) ($schedule->timezone ?? 'Europe/London'),
            ]);

            return [
                'id' => $offering->id,
                'title' => $offering->title,
                'source_version' => 'v3',
                'booking_flow' => $this->offeringBookingFlow($offering),
                'type' => $offering->type?->name ?: 'experience',
                'format' => $this->seo()->inferFormatKeyFromOffering($offering),
                'modality' => $this->seo()->inferModalitySlugFromOffering($offering),
                'category' => $offering->category ? ['id' => $offering->category->id, 'name' => $offering->category->name] : null,
                'rating' => ($vendorReviewSummary = $offering->vendor?->review_summary ?? ['count' => 0, 'rating' => null])['rating'] ?? null,
                'review_count' => $vendorReviewSummary['count'] ?? 0,
                'vendor_rating' => $vendorReviewSummary['rating'] ?? null,
                'vendor_review_count' => $vendorReviewSummary['count'] ?? 0,
                'price' => (float) ($offering->price ?? $minPrice ?? 0),
                'price_min' => (float) ($minPrice ?? $offering->price ?? 0),
                'price_max' => (float) ($maxPrice ?? $offering->price ?? 0),
                'compare_at_price' => null,
                'currency' => 'GBP',
                'image' => $offering->getFirstImageUrl(),
                'images' => $images,
                'options' => [$ticketOptionMeta],
                'variants' => $eventVariants,
                'selectedVariantId' => $selectedVariantId,
                'selectedVariantLabel' => $selectedVariantLabel,
                'selectedVariantSelection' => $selectedVariantSelection,
                'selectedVariantPrice' => $selectedVariantPrice,
                'selectedVariantPriceOptionId' => $selectedVariantPriceOptionId,
                'mode' => $isOnline && count($physical) === 0 ? 'Online' : (count($physical) ? 'In-person' : null),
                'location' => $physical[0] ?? ($isOnline ? 'Online' : null),
                'locations' => $locations,
                'description' => $descriptionHtml,
                'summary' => (string) ($offering->summary ?? ''),
                'body_html' => $descriptionHtml,
                'what_to_expect' => $whatToExpectHtml,
                'included' => $includedHtml,
                'aftercare' => '',
                'duration' => null,
                'tags' => array_values(array_filter(array_map('trim', array_filter([
                    $offering->type?->name,
                    $offering->category?->name,
                ])))),
                'benefits' => [],
                'who_for' => [],
                'who_not_for' => [],
                'faq' => [],
                'safety_notes' => '',
                'contraindications' => '',
                'date' => $startDate,
                'start_date' => $startDate,
                'start_time' => $startTime,
                'end_date' => $endDate,
                'end_time' => $endTime,
                'practitioner' => $this->practitionerPayload($offering->vendor, $offering->vendor?->user),
                'video_url' => trim((string) ($details->video_url ?? '')),
                'event_links' => array_values(array_filter(array_map(static function ($link) {
                    if (is_string($link)) {
                        $link = json_decode($link, true);
                    }
                    if (is_object($link)) {
                        $link = (array) $link;
                    }
                    if (! is_array($link)) {
                        return null;
                    }

                    $url = trim((string) ($link['url'] ?? ''));
                    if ($url === '') {
                        return null;
                    }

                    return [
                        'id' => trim((string) ($link['id'] ?? '')),
                        'label' => trim((string) ($link['label'] ?? '')),
                        'url' => $url,
                    ];
                }, is_string($details->event_links ?? null) ? (json_decode((string) $details->event_links, true) ?: []) : (is_array($details->event_links ?? null) ? $details->event_links : [])))),
                'capacity' => is_numeric(data_get($eventPayload, 'capacity')) ? (int) data_get($eventPayload, 'capacity') : null,
                'event' => $eventPayload,
                'when' => ['event' => $eventPayload],
                'venue_locations' => $venueLocations,
                'reviews' => collect(),
                'client_reviews' => $this->vendorClientReviews($offering->vendor),
                'url' => $this->seo()->canonicalOfferingUrl($offering),
                'is_past_event' => $isPastEvent,
            ];
        }

        $formatValues = [];
        if (in_array('online', $channels, true)) {
            $formatValues[] = 'Online';
        }
        if (in_array('in_person', $channels, true) || (! empty($physical) && ! in_array('In-person', $formatValues, true))) {
            $formatValues[] = 'In-person';
        }
        $formatValues = array_values(array_unique($formatValues));

        $buildPeopleLabel = function (string $audienceType, string $pricingType, $option, $tierRow = null): ?string {
            $audienceType = strtolower(trim($audienceType));
            $pricingType = strtolower(trim($pricingType));
            if ($tierRow) {
                $min = (int) ($tierRow->min_qty ?? 0);
                $max = $tierRow->max_qty !== null ? (int) $tierRow->max_qty : null;
                if ($min > 0) {
                    if ($min === 1) {
                        return '1 Person';
                    }
                    if ($min === 2) {
                        return '2 Persons';
                    }
                    if ($max === null || $max <= $min) {
                        return $min >= 3 ? (($min === 3) ? '3+ Group' : $min.' People') : null;
                    }

                    return $min.'-'.$max.' Group';
                }
            }

            if ($audienceType === 'solo') {
                return '1 Person';
            }
            if ($audienceType === 'couple') {
                return '2 Persons';
            }
            if ($audienceType === 'group') {
                if ($pricingType === 'per_person') {
                    return '3+ Group';
                }

                $minPeople = (int) ($option->min_qty ?? 0);
                if ($minPeople >= 3) {
                    return $minPeople === 3 ? '3+ Group' : $minPeople.' People';
                }

                return '3+ Group';
            }

            return null;
        };

        $formatLabelForOption = function ($option): ?string {
            $channel = strtolower(trim((string) ($option->channel ?? '')));
            if ($channel === 'online') {
                return 'Online';
            }
            if ($channel === 'in_person') {
                return 'In-person';
            }

            return null;
        };

        $variantLocationPriority = static function (array $variant): int {
            $text = strtolower(trim(implode(' ', array_filter(array_map(
                static fn ($value): string => trim((string) $value),
                (array) ($variant['selection'] ?? $variant['options'] ?? [])
            )))));

            if ($text === '') {
                return 2;
            }

            if (str_contains($text, 'in-person') || str_contains($text, 'in person')) {
                return 0;
            }

            if (str_contains($text, 'online')) {
                return 1;
            }

            return 2;
        };

        $peopleValues = [];
        $variants = [];
        $groupPriceOptionIds = [];
        foreach ($priceOptions as $option) {
            $audienceType = strtolower(trim((string) ($option->audience_type ?? '')));
            $pricingType = strtolower(trim((string) ($option->pricing_type ?? '')));
            $formatLabel = $formatLabelForOption($option);
            if ($formatLabel === null && count($formatValues) === 1) {
                $formatLabel = $formatValues[0];
            }
            $basePeopleLabel = $buildPeopleLabel($audienceType, $pricingType, $option);
            $tierRows = $priceTiers->where('price_option_id', (int) $option->id)->values();

            if ($audienceType === 'group' && $pricingType === 'per_person' && $tierRows->isNotEmpty()) {
                $groupPriceOptionIds[] = (int) $option->id;

                foreach ($tierRows as $tierRow) {
                    $peopleLabel = $buildPeopleLabel($audienceType, $pricingType, $option, $tierRow);
                    if ($peopleLabel !== null) {
                        $peopleValues[] = $peopleLabel;
                    }

                    $variantOptions = [];
                    if ($formatLabel !== null) {
                        $variantOptions[] = $formatLabel;
                    }
                    if ($peopleLabel !== null) {
                        $variantOptions[] = $peopleLabel;
                    }

                    $variants[] = [
                        'id' => 'po_'.(string) $option->id.'_tier_'.(string) $tierRow->id,
                        'options' => $variantOptions,
                        'selection' => $variantOptions,
                        'price' => (float) ($tierRow->price_amount ?? $option->price_amount ?? 0),
                        'compare' => null,
                        'available' => (bool) ($option->is_active ?? true),
                        '__order' => count($variants),
                    ];
                }

                continue;
            }

            if ($basePeopleLabel !== null) {
                $peopleValues[] = $basePeopleLabel;
            }

            $variants[] = [
                'id' => 'po_'.(string) $option->id,
                'options' => array_values(array_filter([$formatLabel, $basePeopleLabel], fn ($v) => $v !== null && $v !== '')),
                'selection' => array_values(array_filter([$formatLabel, $basePeopleLabel], fn ($v) => $v !== null && $v !== '')),
                'price' => (float) ($option->price_amount ?? 0),
                'compare' => null,
                'available' => (bool) ($option->is_active ?? true),
                '__order' => count($variants),
            ];
        }
        usort($variants, static function (array $left, array $right) use ($variantLocationPriority): int {
            $priorityDiff = $variantLocationPriority($left) <=> $variantLocationPriority($right);
            if ($priorityDiff !== 0) {
                return $priorityDiff;
            }

            return (int) ($left['__order'] ?? 0) <=> (int) ($right['__order'] ?? 0);
        });
        $variants = array_values(array_map(static function (array $variant): array {
            unset($variant['__order']);

            return $variant;
        }, $variants));
        $peopleValues = array_values(array_unique(array_filter($peopleValues)));
        if (empty($peopleValues)) {
            $peopleValues[] = '1 Person';
        }

        $options = [];
        if (! empty($formatValues)) {
            $options[] = [
                'name' => 'Format',
                'meta_name' => 'format',
                'values' => $formatValues,
            ];
        }
        $options[] = [
            'name' => 'People',
            'meta_name' => 'people',
            'values' => $peopleValues,
        ];

        $minPrice = collect([$offering->price])
            ->merge($priceOptions->pluck('price_amount'))
            ->merge($priceTiers->pluck('price_amount'))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (float) $v)
            ->min();
        $maxPrice = collect([$offering->price])
            ->merge($priceOptions->pluck('price_amount'))
            ->merge($priceTiers->pluck('price_amount'))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (float) $v)
            ->max();

        $selectedVariantCard = $selectVariantCard($variants, $requestedVariantId, $requestedPriceOptionId);
        $selectedVariantId = (string) ($selectedVariantCard['id'] ?? ($variants[0]['id'] ?? 'variant-default'));
        $selectedVariantSelection = array_values(array_filter(array_map('trim', (array) ($selectedVariantCard['selection'] ?? ($selectedVariantCard['options'] ?? [])))));
        $selectedVariantLabel = trim((string) ($selectedVariantCard['label'] ?? (empty($selectedVariantSelection) ? ($selectedVariantCard['options'][0] ?? 'Option') : implode(' • ', $selectedVariantSelection))));
        if ($selectedVariantLabel === '') {
            $selectedVariantLabel = 'Option';
        }
        $selectedVariantPrice = (float) ($selectedVariantCard['price'] ?? $offering->price ?? 0);
        $selectedVariantPriceOptionId = $extractPriceOptionId($selectedVariantId);
        $bookingContext = app(BookingContextBuilder::class)->buildForOffering($offering, $selectedVariantPriceOptionId, $selectedVariantLabel);
        $bookingPayload = is_array($bookingContext['bookingPayload'] ?? null) ? $bookingContext['bookingPayload'] : [];
        $bookingPayload['weeklyWindows'] = $bookingContext['weeklyWindows'] ?? ($bookingPayload['weeklyWindows'] ?? []);
        $bookingPayload['availabilitySettings'] = $bookingContext['availabilitySettings'] ?? ($bookingPayload['availabilitySettings'] ?? []);

        return [
            'id' => $offering->id,
            'title' => $offering->title,
            'seo_title' => trim((string) data_get($meta, 'seo_title', '')),
            'seo_description' => trim((string) data_get($meta, 'seo_description', '')),
            'source_version' => 'v3',
            'booking_flow' => $this->offeringBookingFlow($offering),
            'type' => $offering->type?->name ?: 'experience',
            'format' => $this->seo()->inferFormatKeyFromOffering($offering),
            'modality' => $this->seo()->inferModalitySlugFromOffering($offering),
            'category' => $offering->category ? ['id' => $offering->category->id, 'name' => $offering->category->name] : null,
            'rating' => ($vendorReviewSummary = $offering->vendor?->review_summary ?? ['count' => 0, 'rating' => null])['rating'] ?? null,
            'review_count' => $vendorReviewSummary['count'] ?? 0,
            'vendor_rating' => $vendorReviewSummary['rating'] ?? null,
            'vendor_review_count' => $vendorReviewSummary['count'] ?? 0,
            'price' => $offering->price ?? null,
            'price_min' => $minPrice ?? $offering->price ?? null,
            'price_max' => $maxPrice ?? $offering->price ?? null,
            'compare_at_price' => null,
            'currency' => 'GBP',
            'image' => $offering->getFirstImageUrl(),
            'images' => $images,
            'options' => $options,
            'variants' => $variants,
            'selectedVariantId' => $selectedVariantId,
            'selectedVariantLabel' => $selectedVariantLabel,
            'selectedVariantSelection' => $selectedVariantSelection,
            'selectedVariantPrice' => $selectedVariantPrice,
            'selectedVariantPriceOptionId' => $selectedVariantPriceOptionId,
            'mode' => $isOnline && count($physical) === 0 ? 'Online' : (count($physical) ? 'In-person' : null),
            'location' => $physical[0] ?? ($isOnline ? 'Online' : null),
            'locations' => $locations,
            'description' => (string) ($details->description ?? ''),
            'summary' => (string) ($offering->summary ?? ''),
            'body_html' => (string) ($details->description ?? ''),
            'what_to_expect' => (string) ($details->what_to_expect ?? ''),
            'included' => (string) ($details->whats_included ?? ''),
            'aftercare' => '',
            'duration' => $bookingContext['duration'] ?? ($meta->duration_minutes ?? null),
            'tags' => array_values(array_filter(array_map('trim', array_filter([
                $offering->type?->name,
                $offering->category?->name,
            ])))),
            'benefits' => [],
            'who_for' => [],
            'who_not_for' => [],
            'faq' => [],
            'safety_notes' => '',
            'contraindications' => '',
            'date' => null,
            'start_date' => null,
            'end_date' => null,
            'venue_locations' => $venueLocations,
            'booking' => $bookingPayload,
            'practitioner' => $this->practitionerPayload($offering->vendor, $offering->vendor?->user),
            'reviews' => collect(),
            'client_reviews' => $this->vendorClientReviews($offering->vendor),
            'url' => $this->seo()->canonicalOfferingUrl($offering),
            'is_past_event' => false,
        ];
    }

    public function offeringCanonical(Request $request, string $offering)
    {
        return $this->redirectOfferingToCanonical($request, $offering);
    }

    public function offeringLocation(Request $request, string $format, string $modality, string $country, string $county, string $town, string $offering)
    {
        $format = strtolower(trim($format));
        if (! in_array($format, self::TYPES, true)) {
            abort(404);
        }

        return $this->redirectOfferingToCanonical($request, $offering);
    }

    public function onlineOffering(Request $request, string $modality, string $offering)
    {
        $id = null;
        if (preg_match('/^(\d+)(?:-.+)?$/', (string) $offering, $match)) {
            $id = (int) $match[1];
        }

        $resolvedProduct = $this->resolveProductForOffering($id, (string) $offering);
        if ($resolvedProduct) {
            $canonicalTarget = $this->seo()->canonicalProductUrl($resolvedProduct);
            $canonicalPath = trim((string) parse_url($canonicalTarget, PHP_URL_PATH), '/');
            if ($canonicalPath !== '' && $canonicalPath !== trim((string) $request->path(), '/')) {
                return $this->redirectWithQuery($request, $canonicalTarget, 301);
            }

            $productData = $this->transformProduct($resolvedProduct);

            return view('offering.show', [
                'type' => $this->seo()->inferFormatKeyFromProduct($resolvedProduct),
                'product' => $productData,
                'seo' => $this->offeringSeoData($productData),
            ]);
        }

        $resolvedOffering = $this->resolveV3Offering($id, (string) $offering);
        if ($resolvedOffering) {
            $requestedVariantId = trim((string) $request->query('variant', ''));
            $requestedVariantId = $requestedVariantId !== '' ? $requestedVariantId : null;
            $requestedPriceOptionId = $this->resolveRequestedPriceOptionId($request);
            $productData = $this->transformV3Offering($resolvedOffering, $this->seo()->inferFormatKeyFromOffering($resolvedOffering), $requestedVariantId, $requestedPriceOptionId);

            $canonicalPath = trim((string) parse_url((string) ($productData['url'] ?? ''), PHP_URL_PATH), '/');
            if ($canonicalPath !== '' && $canonicalPath !== trim((string) $request->path(), '/')) {
                return $this->redirectWithQuery($request, (string) ($productData['url'] ?? url('/online')), 301);
            }

            return view('offering.show', [
                'type' => $this->seo()->inferFormatKeyFromOffering($resolvedOffering),
                'product' => $productData,
                'seo' => $this->offeringSeoData($productData),
            ]);
        }

        abort(404);
    }

    private function redirectOfferingToCanonical(Request $request, string $offering)
    {
        $id = null;
        if (preg_match('/^(\d+)(?:-.+)?$/', (string) $offering, $match)) {
            $id = (int) $match[1];
        }

        $resolvedProduct = $this->resolveProductForOffering($id, (string) $offering);
        if ($resolvedProduct) {
            return $this->redirectWithQuery($request, $this->seo()->canonicalProductUrl($resolvedProduct), 301);
        }

        $resolvedOffering = $this->resolveV3Offering($id, (string) $offering);
        if ($resolvedOffering) {
            return $this->redirectWithQuery($request, $this->seo()->canonicalOfferingUrl($resolvedOffering), 301);
        }

        abort(404);
    }

    private function draftOfferingCategoryUrl(mixed $offering): string
    {
        if ($offering instanceof OfferingV3) {
            $format = $this->seo()->inferFormatKeyFromOffering($offering);
            $modality = $this->seo()->inferModalitySlugFromOffering($offering);
        } else {
            $format = $this->seo()->inferFormatKeyFromProduct($offering);
            $modality = $this->seo()->inferModalitySlugFromProduct($offering);
        }

        if ($modality === '' || $modality === $format) {
            return $this->seo()->formatPageUrl($format);
        }

        return $this->seo()->modalityPageUrl($format, $modality);
    }

    private function resolveProductForOffering(?int $id, string $handle, bool $includeUnpublished = false): ?Product
    {
        $canonicalId = false;
        if (preg_match('/^product-(\d+)(?:-.+)?$/', trim($handle), $canonicalMatch)) {
            $id = (int) $canonicalMatch[1];
            $canonicalId = true;
        } elseif (str_starts_with(trim($handle), 'offering-')) {
            return null;
        }

        // A leading number is often part of the canonical title rather than a
        // record ID. Prefer the complete title/handle match, then retain the
        // historical ID-based fallback for old "{id}-{slug}" links.
        if ($id && ! $canonicalId && ! ctype_digit(trim($handle))) {
            $matchedByHandle = $this->resolveProductForOffering(null, $handle, $includeUnpublished);
            if ($matchedByHandle) {
                return $matchedByHandle;
            }
        }

        $query = Product::query()
            ->with(['media', 'options.values', 'variants', 'reviews.user', 'category', 'status', 'vendor.user.tier'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if (! $includeUnpublished) {
            // Direct offering URLs must follow the same public visibility
            // contract as listing/search queries. Without this constraint, a
            // draft product could still render when its numeric ID or legacy
            // handle was requested.
            $query->where(function ($visible) {
                $visible->whereHas('status', function ($status) {
                    $status->whereIn('status', ['live', 'approved']);
                })->orWhereNull('product_status_id');
            });
        }

        if ($id) {
            return $query->where('id', $id)->first();
        }

        $handle = trim($handle);
        $normalizedHandle = $this->slugify($handle);
        $normalizedLookupKey = $this->normalizeLookupKey($handle);

        $exactMatches = (clone $query)
            ->where(function ($builder) use ($handle, $normalizedHandle): void {
                $builder->where('handle', $handle)
                    ->orWhere('handle', $normalizedHandle);
            })
            ->get();

        if ($exactMatches->isNotEmpty()) {
            $preferred = $exactMatches->first(function (Product $product) use ($normalizedHandle): bool {
                return $this->slugify((string) ($product->title ?? '')) === $normalizedHandle;
            });

            return $preferred ?: $exactMatches->first();
        }

        foreach ($this->candidateLookupTokenSets($handle) as $tokens) {
            $candidates = (clone $query)
                ->where(function ($builder) use ($tokens): void {
                    foreach ($tokens as $token) {
                        $builder->where(function ($tokenBuilder) use ($token): void {
                            $like = '%'.$token.'%';
                            $tokenBuilder->whereRaw('LOWER(COALESCE(title, "")) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(COALESCE(handle, "")) LIKE ?', [$like]);
                        });
                    }
                })
                ->get();

            if ($candidates->isEmpty()) {
                continue;
            }

            $preferred = $candidates->first(function (Product $product) use ($normalizedHandle, $normalizedLookupKey): bool {
                return $this->normalizeLookupKey((string) ($product->title ?? '')) === $normalizedLookupKey
                    || $this->normalizeLookupKey((string) ($product->handle ?? '')) === $normalizedLookupKey
                    || $this->slugify((string) ($product->title ?? '')) === $normalizedHandle
                    || $this->slugify((string) ($product->handle ?? '')) === $normalizedHandle;
            });

            if ($preferred) {
                return $preferred;
            }
        }

        return null;
    }

    private function legacyProductBookingFlow(Product $product, ?int $priceOptionId = null, ?string $variantLabel = null): string
    {
        try {
            $context = app(BookingContextBuilder::class)->buildForProduct($product, $priceOptionId, $variantLabel);
            $slotsByDay = $context['slotsByDay'] ?? [];
            foreach ($slotsByDay as $day) {
                if (is_array($day['slots'] ?? null) && count($day['slots']) > 0) {
                    return 'live';
                }
            }
        } catch (\Throwable $e) {
            // Fall back to flexible when the availability builder cannot resolve the product.
        }

        return 'flexible';
    }

    private function resolveRequestedPriceOptionId(\Illuminate\Http\Request $request): ?int
    {
        $value = $request->input('variant');
        if ($value === null || $value === '') {
            $value = $request->query('variant');
        }

        if ($value === null || $value === '') {
            $value = $request->input('price_option_id');
        }
        if ($value === null || $value === '') {
            $value = $request->query('price_option_id');
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/po_(\d+)/i', (string) $value, $match)) {
            return (int) $match[1];
        }

        return filter_var($value, FILTER_VALIDATE_INT) === false ? null : (int) $value;
    }

    private function resolveRequestedVariantLabel(Product $product, \Illuminate\Http\Request $request): ?string
    {
        $priceOptionId = $this->resolveRequestedPriceOptionId($request);
        if (! $priceOptionId) {
            return null;
        }

        $variant = null;
        if ($product->relationLoaded('variants')) {
            $variant = $product->variants->first(fn ($item) => (int) $item->id === (int) $priceOptionId);
        }

        if (! $variant) {
            $variant = DB::table('product_variants')
                ->where('product_id', $product->id)
                ->where('id', $priceOptionId)
                ->first();
        }

        if (! $variant) {
            return null;
        }

        $parts = [];
        $append = static function (&$parts, mixed $value): void {
            $text = trim((string) $value);
            if ($text !== '') {
                $parts[] = $text;
            }
        };

        $options = $variant->options ?? null;
        if (is_array($options)) {
            foreach ($options as $optionValue) {
                $append($parts, $optionValue);
            }
        } elseif (is_string($options) && $options !== '') {
            $decoded = json_decode($options, true);
            if (is_array($decoded)) {
                foreach ($decoded as $optionValue) {
                    $append($parts, $optionValue);
                }
            }
        }

        if (empty($parts) && ! empty($variant->title)) {
            $append($parts, $variant->title);
        }

        $metadata = is_array($variant->metadata ?? null) ? $variant->metadata : [];
        foreach (['session', 'sessions', 'duration', 'duration_minutes', 'duration_mins'] as $key) {
            if (! empty($metadata[$key])) {
                $append($parts, $metadata[$key]);
            }
        }

        $parts = array_values(array_filter($parts));

        return $parts ? implode(' • ', $parts) : null;
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<int, array<string, mixed>>
     */
    private function relatedOurVibeArticles(array $event): array
    {
        $haystack = strtolower(implode(' ', array_filter([
            (string) ($event['title'] ?? ''),
            (string) ($event['slug'] ?? ''),
            (string) ($event['handle'] ?? ''),
            (string) ($event['summary'] ?? ''),
            (string) ($event['description'] ?? ''),
            (string) ($event['body_html'] ?? ''),
            (string) ($event['content'] ?? ''),
        ])));

        $normalized = Str::of($haystack)
            ->replace([' ', '-', '_'], '')
            ->toString();

        if (! Str::contains($normalized, 'ourvibe')) {
            return [];
        }

        $articles = Article::query()
            ->with(['featuredMedia', 'backendFeaturedMedia', 'category'])
            ->where(function ($query): void {
                $query->whereRaw(
                    "LOWER(REPLACE(REPLACE(CONCAT(COALESCE(title, ''), ' ', COALESCE(content, '')), ' ', ''), '-', '')) LIKE ?",
                    ['%ourvibe%']
                );
            })
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('live', 'published', 'active')")
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();

        if ($articles->isEmpty()) {
            return [];
        }

        $timesBase = rtrim((string) env('TIMES_BASE_URL', 'https://times.weofferwellness.co.uk'), '/');
        $backendBase = rtrim((string) env('BACKEND_ASSET_URL', env('BACKEND_URL', '')), '/');

        return $articles->map(function (Article $article) use ($timesBase, $backendBase): array {
            $image = $this->resolveArticleImage($article, $backendBase);
            $href = $this->articleHref($article, $timesBase);
            $excerpt = trim((string) Str::of((string) $article->content)->stripTags()->squish()->limit(180));

            return [
                'id' => $article->id,
                'title' => (string) $article->title,
                'excerpt' => $excerpt,
                'href' => $href,
                'image' => $image,
                'category' => optional($article->category)->name ?? 'OUR VIBE',
                'published_at' => $article->created_at?->format('d M Y'),
            ];
        })->all();
    }

    private function resolveArticleImage(Article $article, string $backendBase): ?string
    {
        $media = $article->backendFeaturedMedia ?: $article->featuredMedia;
        if (! $media) {
            $media = $article->backendMedia()->first() ?: $article->media()->first();
        }

        if (! $media) {
            return null;
        }

        $path = $media->url ?? $media->path ?? $media->media_url ?? null;
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $clean = ltrim($path, '/');

        return $backendBase !== ''
            ? $backendBase.'/storage/'.$clean
            : asset('storage/'.$clean);
    }

    private function articleHref(Article $article, string $timesBase): string
    {
        $category = optional($article->category)->name ?: 'journal';
        $year = optional($article->created_at)->format('Y') ?: date('Y');
        $month = optional($article->created_at)->format('m') ?: date('m');
        $slug = Str::slug((string) ($article->title ?: 'article'));

        return $timesBase.'/'.Str::slug($category).'/'.$year.'/'.$month.'/'.$slug.'-'.$article->id;
    }

    private function offeringBookingFlow(OfferingV3 $offering): string
    {
        try {
            return DB::table('offering_schedule')->where('offering_id', $offering->id)->exists() ? 'live' : 'flexible';
        } catch (\Throwable $e) {
            return 'flexible';
        }
    }

    private function practitionerPayload(?VendorDetail $vendor, ?\App\Models\User $user = null): ?array
    {
        if (! $vendor && ! $user) {
            return null;
        }

        $user ??= $vendor?->user;
        $firstName = trim((string) ($user?->first_name ?: Str::of((string) ($user?->name ?? ''))->before(' ')));
        $fullName = trim((string) ($user?->public_display_name ?? ''));
        $vendorReviewSummary = $vendor?->review_summary ?? ['count' => 0, 'rating' => null];
        $planKey = $this->normalizePlanKey(
            $user?->tier?->tier
                ?? $user?->account_type
                ?? ($vendor?->tiers()->orderByDesc('plan_started_at')->orderByDesc('id')->value('tier'))
        );
        $profilePicture = $user?->profile_picture ? $this->profilePhotoUrl($user->profile_picture) : null;
        $normalizeTelephone = static function (?string $value): ?string {
            $digits = preg_replace('/\D+/', '', (string) $value);
            if ($digits === '') {
                return null;
            }

            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            }

            if (str_starts_with($digits, '0') && strlen($digits) === 11) {
                $digits = '44'.substr($digits, 1);
            }

            return '+'.ltrim($digits, '+');
        };
        $normalizeCountryCode = static function (?string $value): ?string {
            $country = strtolower(trim((string) $value));
            if ($country === '') {
                return null;
            }

            return in_array($country, ['gb', 'uk', 'u.k.', 'united kingdom', 'great britain', 'england', 'scotland', 'wales', 'northern ireland'], true)
                ? 'GB'
                : strtoupper($country);
        };

        $vendorLocations = [];
        $vendorPrimaryLocation = null;
        if ($vendor) {
            $vendor->loadMissing('locations');
            $vendorLocations = collect($vendor->locations ?? [])
                ->map(static function ($location) use ($normalizeCountryCode): ?array {
                    $locationData = is_array($location) ? $location : (method_exists($location, 'getAttributes') ? $location->getAttributes() : []);
                    if (! is_array($locationData)) {
                        return null;
                    }

                    $label = trim((string) ($locationData['label'] ?? ''));
                    $formattedAddress = trim((string) ($locationData['formatted_address'] ?? ''));
                    $line1 = trim((string) ($locationData['line1'] ?? $locationData['address_line_1'] ?? $locationData['street_address'] ?? $locationData['building_name'] ?? ''));
                    $line2 = trim((string) ($locationData['line2'] ?? $locationData['address_line2'] ?? $locationData['address_line_2'] ?? ''));
                    $city = trim((string) ($locationData['city'] ?? ''));
                    $county = trim((string) ($locationData['county'] ?? ''));
                    $postcode = trim((string) ($locationData['postcode'] ?? ''));
                    $country = trim((string) ($locationData['country'] ?? ''));
                    $countryCode = $normalizeCountryCode($country);

                    $streetAddress = trim(implode(', ', array_filter([$line1, $line2])));
                    if ($streetAddress === '') {
                        $streetAddress = $formattedAddress !== '' ? $formattedAddress : $label;
                    }

                    $address = array_filter([
                        '@type' => 'PostalAddress',
                        'streetAddress' => $streetAddress !== '' ? $streetAddress : null,
                        'addressLocality' => $city !== '' ? $city : null,
                        'addressRegion' => $county !== '' ? $county : (($city === '' && $label !== '' && ! in_array(strtolower($label), ['united kingdom', 'great britain', 'gb', 'uk'], true)) ? $label : null),
                        'postalCode' => $postcode !== '' ? $postcode : null,
                        'addressCountry' => $countryCode,
                    ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

                    return array_filter([
                        'label' => $label !== '' ? $label : null,
                        'formatted_address' => $formattedAddress !== '' ? $formattedAddress : null,
                        'line1' => $line1 !== '' ? $line1 : null,
                        'line2' => $line2 !== '' ? $line2 : null,
                        'city' => $city !== '' ? $city : null,
                        'county' => $county !== '' ? $county : null,
                        'postcode' => $postcode !== '' ? $postcode : null,
                        'country' => $country !== '' ? $country : null,
                        'address' => $address ?: null,
                        'lat' => is_numeric($locationData['lat'] ?? null) ? (float) $locationData['lat'] : null,
                        'lng' => is_numeric($locationData['lng'] ?? null) ? (float) $locationData['lng'] : null,
                    ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
                })
                ->filter()
                ->values()
                ->all();

            $bestScore = -1;
            foreach ($vendorLocations as $location) {
                $score = 0;
                foreach (['line1', 'line2', 'city', 'county', 'postcode', 'formatted_address'] as $field) {
                    if (trim((string) ($location[$field] ?? '')) !== '') {
                        $score++;
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $vendorPrimaryLocation = $location;
                }
            }
        }

        $vendorAddress = null;
        if (is_array($vendorPrimaryLocation)) {
            $vendorAddress = $vendorPrimaryLocation['address'] ?? null;
            if (! is_array($vendorAddress)) {
                $vendorAddress = null;
            }
        }

        $credentials = $vendor?->credentials ?? $vendor?->qualifications ?? '';
        if (is_string($credentials)) {
            $decodedCredentials = json_decode($credentials, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedCredentials)) {
                $credentials = $decodedCredentials;
            }
        }
        if (! is_array($credentials)) {
            $credentials = [$credentials];
        }
        $credentials = array_values(array_unique(array_filter(array_map(
            static function ($credential): string {
                if (is_array($credential)) {
                    return trim((string) ($credential['title'] ?? $credential['name'] ?? $credential['label'] ?? ''));
                }

                return trim((string) $credential);
            },
            $credentials
        ))));

        return [
            'name' => $fullName !== '' ? $fullName : ($vendor?->vendor_name ?? ''),
            'first_name' => $firstName !== '' ? $firstName : $fullName,
            'bio' => $vendor?->bio ?? $vendor?->about ?? '',
            'credentials' => $credentials,
            'photo' => $profilePicture ?? ($vendor?->photo_url ?? $vendor?->headshot_url ?? $vendor?->avatar_url ?? null),
            'profile_url' => $user?->practitioner_profile_url ?? null,
            'review_url' => filled($user?->practitioner_profile_url ?? null)
                ? rtrim((string) ($user?->practitioner_profile_url ?? ''), '/').'/reviews'
                : null,
            'location' => $vendor?->location ?? '',
            'telephone' => $normalizeTelephone($user?->phone ?? null),
            'phone' => $normalizeTelephone($user?->phone ?? null),
            'vendor_contact' => $vendor?->vendor_contact ?? null,
            'address' => $vendorAddress,
            'locations' => $vendorLocations,
            'specialties' => is_array($vendor?->specialties ?? null) ? array_values(array_filter($vendor->specialties)) : [],
            'plan_key' => $planKey,
            'plan_label' => $this->planTitleForKey($planKey),
            'is_paid_plan' => ! in_array($planKey, ['starter', ''], true),
            'rating' => $vendorReviewSummary['rating'] ?? null,
            'review_count' => $vendorReviewSummary['count'] ?? 0,
            'vendor_rating' => $vendorReviewSummary['rating'] ?? null,
            'vendor_review_count' => $vendorReviewSummary['count'] ?? 0,
        ];
    }

    private function normalizePlanKey(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = str_replace(['_', ' '], '-', $normalized);

        return match ($normalized) {
            'community', 'starter', 'standard', 'free-starter', 'starter-package' => 'starter',
            'core', 'business-accelerator', 'business-accelerator-package', 'businessaccelerator' => 'business-accelerator',
            'premium', 'premium-accelerator', 'premiumaccelerator' => 'premium-accelerator',
            'become-partner', 'partner' => 'become-partner',
            default => $normalized,
        };
    }

    private function vendorClientReviews(?VendorDetail $vendor, int $limit = 6): array
    {
        if (! $vendor) {
            return [];
        }

        return Review::query()
            ->where('vendor_id', $vendor->id)
            ->whereRaw("TRIM(COALESCE(review_text, '')) <> ''")
            ->with('user')
            ->latest('created_at')
            ->take($limit)
            ->get()
            ->map(function (Review $review) {
                return [
                    'id' => $review->id,
                    'rating' => (int) ($review->rating ?? 0),
                    'body' => trim((string) $review->review_text),
                    'author' => optional($review->user)->name ?? 'Verified client',
                    'date' => optional($review->created_at)->format('M Y') ?? '',
                    'title' => trim((string) ($review->title ?? '')),
                    'user_id' => $review->user_id,
                ];
            })
            ->values()
            ->all();
    }

    private function planTitleForKey(?string $value): string
    {
        return match ($this->normalizePlanKey($value)) {
            'starter' => 'Starter',
            'business-accelerator' => 'Business Accelerator',
            'premium-accelerator' => 'Premium Accelerator',
            'become-partner' => 'Become Partner',
            default => Str::headline(trim((string) $value)) ?: 'Plan',
        };
    }

    private function profilePhotoUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $assetHost = rtrim((string) (config('app.asset_url') ?: config('services.asset_host') ?: 'https://atease.weofferwellness.co.uk'), '/');

        return $assetHost.'/storage/'.ltrim($path, '/');
    }
}
