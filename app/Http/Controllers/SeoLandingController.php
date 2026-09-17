<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Support\EventListing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoLandingController extends Controller
{
    private const TYPES = ['workshops', 'classes', 'retreats', 'gifts'];

    public function show(Request $request, string $type)
    {
        $type = strtolower(trim($type));
        abort_unless(in_array($type, self::TYPES, true), 404);

        $config = $this->configForType($type);
        $categorySlug = trim((string) $request->query('category', ''));
        $category = $categorySlug !== '' ? $this->findCategoryBySlug($categorySlug) : null;
        $hasFilters = $request->hasAny(['format', 'location', 'sort', 'page', 'per_page']);

        $categories = ProductCategory::query()
            ->withCount(['products as products_count' => function ($query) use ($type) {
                $this->applyTypeFilter($query, $type);
            }])
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->take(8)
            ->get()
            ->map(function (ProductCategory $category) {
                $name = trim((string) ($category->name ?? ''));
                return [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'count' => (int) ($category->products_count ?? 0),
                ];
            })
            ->filter(fn (array $category) => $category['count'] > 0)
            ->values();

        $products = $this->queryProducts($request, $type, $category)
            ->take(12)
            ->values();

        if ($category) {
            $baseTitle = $config['title'];
            $config['title'] = $baseTitle . ' · ' . $category->name;
            $config['description'] = $category->name . ' options across ' . $baseTitle . ' from trusted practitioners.';
            $config['intro'] = $category->name . ' options within ' . $baseTitle . ' on We Offer Wellness.';
        }

        return view('landing.show', [
            'seo' => [
                'title' => $config['title'],
                'description' => $config['description'],
                'robots' => $hasFilters ? 'noindex,follow' : 'index,follow',
                'canonical' => url('/' . $type),
            ],
            'landing' => $config,
            'type' => $type,
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    private function configForType(string $type): array
    {
        return match ($type) {
            'workshops' => [
                'kicker' => 'Learn & practice',
                'title' => 'Workshops',
                'description' => 'Explore wellbeing workshops with trusted facilitators, from breathwork and sound to creativity, movement and team wellbeing.',
                'intro' => 'Browse hands-on wellbeing workshops designed for individuals, teams and communities. Use the filters to find the right session by format, location or focus.',
                'points' => [
                    'Small group formats',
                    'Online and in-person options',
                    'Trusted facilitators and clear outcomes',
                ],
                'primary_cta' => ['label' => 'Browse workshops', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'Search all results', 'href' => '/search?type=workshops'],
            ],
            'classes' => [
                'kicker' => 'Weekly rhythm',
                'title' => 'Classes',
                'description' => 'Find recurring wellness classes across yoga, movement, meditation and breath-led practices.',
                'intro' => 'Choose from classes that fit into your week, with options for online, in-person and location-based discovery.',
                'points' => [
                    'Weekly timetable and recurring sessions',
                    'Morning, lunchtime and evening options',
                    'Yoga, meditation and movement classes',
                ],
                'primary_cta' => ['label' => 'View classes', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'See the schedule', 'href' => '/classes?format=online'],
            ],
            'retreats' => [
                'kicker' => 'Getaways',
                'title' => 'Retreats',
                'description' => 'Discover restorative retreats and day escapes designed to help people reset, recharge and reconnect.',
                'intro' => 'Explore retreat experiences with nervous-system-friendly itineraries, trusted facilitators and a clear sense of the experience before you book.',
                'points' => [
                    'Day, weekend and bespoke retreats',
                    'Nature-led and restorative experiences',
                    'Ideal for personal or group bookings',
                ],
                'primary_cta' => ['label' => 'Browse retreats', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'Enquire about retreats', 'href' => '/contact?topic=retreats'],
            ],
            'gifts' => [
                'kicker' => 'High-intent gifting',
                'title' => 'Gifts',
                'description' => 'Send giftable wellness experiences and digital vouchers for therapies, classes, workshops and retreats.',
                'intro' => 'Find flexible gifting options that can be sent instantly and redeemed across the We Offer Wellness collection.',
                'points' => [
                    'Instant digital delivery',
                    'Redeemable across multiple categories',
                    'A strong fit for birthdays, rewards and care packages',
                ],
                'primary_cta' => ['label' => 'Browse gifts', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'Gift cards', 'href' => '/gift-cards'],
            ],
            default => [
                'kicker' => 'Explore',
                'title' => ucfirst($type),
                'description' => 'Browse wellness experiences from trusted practitioners.',
                'intro' => 'Explore curated wellness listings and browse popular options on this landing page.',
                'points' => [
                    'Trusted practitioner listings',
                    'Search-friendly landing page',
                    'Popular options surfaced first',
                ],
                'primary_cta' => ['label' => 'Browse listings', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'Search all results', 'href' => '/search'],
            ],
        };
    }

    private function queryProducts(Request $request, string $type, ProductCategory|ProductSubcategory|null $category = null)
    {
        $builder = Product::query()
            ->with(['media', 'options.values', 'category', 'subcategory'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        $this->applyTypeFilter($builder, $type);

        if ($category) {
            $builder->where('category_id', $category instanceof ProductSubcategory ? $category->category_id : $category->id);
            if ($category instanceof ProductSubcategory) {
                $builder->where('subcategory_id', $category->id);
            }
        }

        $mode = strtolower((string) $request->query('format', $request->query('mode', '')));
        if (in_array($mode, ['online', 'in-person'], true)) {
            $builder->whereHas('options', function ($query) use ($mode) {
                $query->where('meta_name', 'locations')
                    ->whereHas('values', function ($valuesQuery) use ($mode) {
                        if ($mode === 'online') {
                            $valuesQuery->whereRaw("LOWER(TRIM(COALESCE(value,''))) = 'online'");
                        } else {
                            $valuesQuery->whereRaw("LOWER(TRIM(COALESCE(value,''))) <> 'online'")
                                ->whereRaw("TRIM(COALESCE(value,'')) <> ''");
                        }
                    });
            });
        }

        if ($location = trim((string) $request->query('location', ''))) {
            $like = '%' . $location . '%';
            $builder->whereHas('options', function ($query) use ($like) {
                $query->where('meta_name', 'locations')
                    ->whereHas('values', function ($valuesQuery) use ($like) {
                        $valuesQuery->where('value', 'like', $like);
                    });
            });
        }

        $sort = strtolower((string) $request->query('sort', 'popular'));
        if ($sort === 'newest') {
            $builder->latest('id');
        } elseif ($sort === 'price_asc') {
            $builder->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $builder->orderBy('price', 'desc');
        } else {
            $builder->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')
                ->orderByRaw('COALESCE(reviews_avg_rating, 0) DESC')
                ->orderByRaw('COALESCE(reviews_count, 0) DESC');
        }

        return $builder->get()
            ->reject(fn ($product) => EventListing::isPast($product))
            ->values();
    }

    private function findCategoryBySlug(string $slug): ProductCategory|ProductSubcategory|null
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }

        foreach (ProductCategory::query()->get() as $category) {
            if (Str::slug((string) $category->name) === $slug) {
                return $category;
            }
        }

        foreach (ProductSubcategory::query()->whereIn('status', ['approved', 'live'])->get() as $subcategory) {
            if (Str::slug((string) ($subcategory->slug ?: $subcategory->name)) === $slug) {
                return $subcategory;
            }
        }

        return null;
    }

    private function applyTypeFilter($query, string $type): void
    {
        $type = strtolower($type);

        if ($type === 'workshops') {
            $query->whereRaw("LOWER(COALESCE(product_type,'')) like '%workshop%'");
            return;
        }

        if ($type === 'classes') {
            $query->whereRaw("LOWER(COALESCE(product_type,'')) like '%class%'");
            return;
        }

        if ($type === 'retreats') {
            $query->whereRaw("LOWER(COALESCE(product_type,'')) like '%retreat%'");
            return;
        }

        if ($type === 'gifts') {
            $query->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(tags_list,'')) like '%gift%'")
                    ->orWhereRaw("LOWER(COALESCE(product_type,'')) like '%gift%'");
            });
        }
    }
}
