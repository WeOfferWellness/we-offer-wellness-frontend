<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Support\EventListing;
use App\Services\BackendDiscoveryClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Public offering-type entry points.
 */
class TypeController extends Controller
{
    public function therapies(Request $request)
    {
        return $this->renderTypeLanding($request, 'therapies');
    }

    public function classes(Request $request)
    {
        return $this->renderTypeLanding($request, 'classes');
    }

    public function events(Request $request)
    {
        return $this->renderTypeLanding($request, 'events');
    }

    public function workshops(Request $request)
    {
        return $this->renderTypeLanding($request, 'workshops');
    }

    public function retreats(Request $request)
    {
        return $this->renderTypeLanding($request, 'retreats');
    }

    public function holisticTherapiesUk(Request $request)
    {
        return app(SeoMoneyPageController::class)->show($request, 'holistic-therapies-uk');
    }

    private function renderTypeLanding(Request $request, string $type)
    {
        $config = match ($type) {
            'therapies' => [
                'kicker' => 'Browse therapies', 'title' => 'Therapies',
                'description' => 'Find massage, Reiki, breathwork, sound healing and other therapy experiences from trusted practitioners.',
                'intro' => 'Choose a therapy to see live offerings and refine by format, location or price.',
                'points' => [],
                'primary_cta' => ['label' => 'Search therapies', 'href' => '/search?type=therapies'],
                'secondary_cta' => ['label' => 'Browse online', 'href' => '/online'],
            ],
            'events' => [
                'kicker' => 'Discover & connect', 'title' => 'Events',
                'description' => 'Discover wellbeing events, gatherings and experiences from trusted hosts.',
                'intro' => 'Find upcoming events that fit your interests, format and location.',
                'points' => ['Upcoming experiences', 'Online and in-person options', 'Clear dates, formats and booking details'],
                'primary_cta' => ['label' => 'Browse events', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'Browse online', 'href' => '/online'],
            ],
            'workshops' => [
                'kicker' => 'Learn & practice', 'title' => 'Workshops',
                'description' => 'Explore wellbeing workshops with trusted facilitators, from breathwork and sound to creativity, movement and team wellbeing.',
                'intro' => 'Browse hands-on wellbeing workshops designed for individuals, teams and communities. Use the filters to find the right session by format, location or focus.',
                'points' => ['Small group formats', 'Online and in-person options', 'Trusted facilitators and clear outcomes'],
                'primary_cta' => ['label' => 'Browse workshops', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'Search all results', 'href' => '/search?type=workshops'],
            ],
            'classes' => [
                'kicker' => 'Weekly rhythm', 'title' => 'Classes',
                'description' => 'Find recurring wellness classes across yoga, movement, meditation and breath-led practices.',
                'intro' => 'Choose from classes that fit into your week, with options for online, in-person and location-based discovery.',
                'points' => ['Weekly timetable and recurring sessions', 'Morning, lunchtime and evening options', 'Yoga, meditation and movement classes'],
                'primary_cta' => ['label' => 'View classes', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'See the schedule', 'href' => '/classes?format=online'],
            ],
            'retreats' => [
                'kicker' => 'Getaways', 'title' => 'Retreats',
                'description' => 'Discover restorative retreats and day escapes designed to help people reset, recharge and reconnect.',
                'intro' => 'Explore retreat experiences with nervous-system-friendly itineraries, trusted facilitators and a clear sense of the experience before you book.',
                'points' => ['Day, weekend and bespoke retreats', 'Nature-led and restorative experiences', 'Ideal for personal or group bookings'],
                'primary_cta' => ['label' => 'Browse retreats', 'href' => '#landing-products'],
                'secondary_cta' => ['label' => 'Enquire about retreats', 'href' => '/contact?topic=retreats'],
            ],
        };
        $category = null;
        if ($slug = trim((string) $request->query('category', ''))) {
            $category = $this->findCategoryBySlug($slug);
        }
        $hasFilters = $request->hasAny(['format', 'location', 'sort', 'page', 'per_page']);
        $categories = Cache::remember('type-landing:categories:'.$type, now()->addMinutes(5), function () use ($type) {
            return ProductCategory::query()->withCount(['products as products_count' => fn ($q) => $this->applyTypeFilter($q, $type)])
                ->orderByDesc('products_count')->orderBy('name')->take(8)->get()->map(fn (ProductCategory $c) => ['name' => $c->name, 'slug' => Str::slug($c->name), 'count' => (int) $c->products_count])
                ->filter(fn (array $c) => $c['count'] > 0)->values();
        });
        $products = $this->queryProducts($request, $type, $category)->take(12)->values();
        if ($category) {
            $baseTitle = $config['title'];
            $config['title'] .= ' · '.$category->name;
            $config['description'] = $category->name.' options across '.$baseTitle.' from trusted practitioners.';
            $config['intro'] = $category->name.' options within '.$baseTitle.' on We Offer Wellness.';
        }
        return view('landing.show', [
            'seo' => ['title' => $config['title'], 'description' => $config['description'], 'robots' => $hasFilters ? 'noindex,follow' : 'index,follow', 'canonical' => url('/'.$type)],
            'landing' => $config,
            'type' => $type,
            'categories' => $categories,
            'discoveryCategories' => app(BackendDiscoveryClient::class)->modalityBoard(5),
            'products' => $products,
            'featuredOfferings' => $type === 'therapies' ? $products->take(8)->values() : collect(),
        ]);
    }

    private function queryProducts(Request $request, string $type, ProductCategory|ProductSubcategory|null $category = null)
    {
        $builder = Product::query()->with(['media', 'options.values', 'category', 'subcategory'])->withCount('reviews')->withAvg('reviews', 'rating');
        $this->applyTypeFilter($builder, $type);
        if ($category) {
            $builder->where('category_id', $category instanceof ProductSubcategory ? $category->category_id : $category->id);
            if ($category instanceof ProductSubcategory) $builder->where('subcategory_id', $category->id);
        }
        $mode = strtolower((string) $request->query('format', $request->query('mode', '')));
        if (in_array($mode, ['online', 'in-person'], true)) $builder->whereHas('options', function ($q) use ($mode) { $q->where('meta_name', 'locations')->whereHas('values', function ($vq) use ($mode) { $mode === 'online' ? $vq->whereRaw("LOWER(TRIM(COALESCE(value,''))) = 'online'") : $vq->whereRaw("LOWER(TRIM(COALESCE(value,''))) <> 'online'")->whereRaw("TRIM(COALESCE(value,'')) <> ''"); }); });
        if ($location = trim((string) $request->query('location', ''))) $builder->whereHas('options', fn ($q) => $q->where('meta_name', 'locations')->whereHas('values', fn ($vq) => $vq->where('value', 'like', '%'.$location.'%')));
        match (strtolower((string) $request->query('sort', 'popular'))) {
            'newest' => $builder->latest('id'), 'price_asc' => $builder->orderBy('price'), 'price_desc' => $builder->orderByDesc('price'),
            default => $builder->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
        };
        return $builder->limit(24)->get()->reject(fn ($product) => EventListing::isPast($product))->take(12)->values();
    }

    private function applyTypeFilter($query, string $type): void
    {
        $term = match (strtolower($type)) {
            'classes' => 'class',
            'events' => 'event',
            'workshops' => 'workshop',
            'retreats' => 'retreat',
            default => strtolower($type),
        };
        $query->whereHas('status', fn ($status) => $status->whereIn('status', ['live', 'approved']));
        $query->whereRaw('LOWER(COALESCE(product_type,\'\')) like ?', ['%'.$term.'%']);
    }

    private function findCategoryBySlug(string $slug): ProductCategory|ProductSubcategory|null
    {
        $slug = Str::slug($slug);
        return ProductCategory::query()->get()->first(fn ($c) => Str::slug($c->slug ?: $c->name) === $slug)
            ?? ProductSubcategory::query()->whereIn('status', ['approved', 'live'])->get()->first(fn ($s) => Str::slug($s->slug ?: $s->name) === $slug);
    }
}
