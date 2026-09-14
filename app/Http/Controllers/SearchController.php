<?php

namespace App\Http\Controllers;

use App\Models\OfferingV3;
use App\Models\Booking;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Reservation;
use App\Models\VendorAvailability;
use App\Models\VendorDetail;
use App\Models\VendorTier;
use App\Services\AvailabilityWindowService;
use App\Support\EventListing;
use App\Support\ProductRanking;
use App\Support\ProductSearchFilters;
use App\Services\SeoStructureService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    private array $nextAvailabilityCache = [];

    public function index(Request $request)
    {
        $format = strtolower((string) $request->query('format', ''));
        if ($format === 'online') {
            return redirect('/classes?mode=online&anytime=1', 302);
        }

        if ($request->has('category')) {
            $raw = trim((string) $request->query('category'));
            if ($raw !== '') {
                $slug = Str::slug($raw);
                $type = strtolower((string) $request->query('type', 'therapies'));
                $allowed = ['therapies','events','workshops','classes','retreats','gifts'];
                if (!in_array($type, $allowed, true)) { $type = 'therapies'; }
                return redirect('/'.$type.'/'.$slug, 302);
            }
        }

        if ($format && !$request->has('mode')) {
            $qs = $request->query();
            $qs['mode'] = $format;
            unset($qs['format']);
            $to = url('/search') . '?' . http_build_query($qs);
            return redirect($to, 302);
        }

        if (! $request->expectsJson()) {
            $what = Str::squish((string) $request->query('what', ''));
            $where = Str::squish((string) $request->query('where', ''));
            $when = Str::squish((string) $request->query('when', ''));

            $seoTitleBase = 'Search all Offerings';
            if ($what !== '' && $where !== '') {
                $seoTitleBase = 'Search ' . $what . ' in ' . $where;
            } elseif ($what !== '') {
                $seoTitleBase = 'Search ' . $what;
            } elseif ($where !== '') {
                $seoTitleBase = 'Search in ' . $where;
            }

            $seoDescription = $what !== ''
                ? 'Search ' . $what . ' and browse live therapies, classes, events and workshops on We Offer Wellness.'
                : 'Search all offerings and browse live therapies, classes, events and workshops on We Offer Wellness.';

            if ($where !== '') {
                $seoDescription = 'Search live therapies, classes, events and workshops in ' . $where . ' on We Offer Wellness.';
            }

            if ($when !== '') {
                $seoDescription .= ' Available ' . $when . '.';
            }

            $emptyProducts = new LengthAwarePaginator(
                collect(),
                0,
                24,
                1,
                ['path' => url()->current(), 'query' => $request->query()]
            );

            return view('search.index', [
                'mapsKey' => env('GOOGLE_MAPS_API_KEY'),
                'products' => $emptyProducts,
                'resultCount' => 0,
                'perPage' => 24,
                'searchGridHtml' => '',
                'searchPaginationHtml' => '',
                'searchMapData' => [],
                'searchAsyncBoot' => true,
                'seo' => [
                    'title' => $seoTitleBase . ' | We Offer Wellness®',
                    'description' => $seoDescription,
                    'canonical' => url()->full(),
                    'og_type' => 'website',
                ],
            ]);
        }

        // Server-rendered results to support Blade product card + map
        $base = Product::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->withMin('variants', 'price')
            ->withMax('variants', 'price')
            ->with(['media', 'options.values', 'category', 'status', 'vendor.locations', 'vendor.tiers', 'vendor.user.settings']);

        $query = clone $base;
        // Only published products
        $query->where(function($q){
            $q->whereHas('status', function($qs){ $qs->whereIn('status', ['live','approved']); });
        });

        $what = $request->string('what')->toString();
        if ($what) {
            $this->applyWhatSearchConstraint($query, $what);
        }

        // Type filter
        $type = $request->string('type')->toString();
        if ($type) {
            $this->applyTypeFilter($query, $type);
        }

        // Tag filter
        if ($tag = $request->string('tag')->toString()) {
            $lc = strtolower($tag);
            if (in_array($lc, ['gift','gifts'], true)) {
                $query->where(function($q){
                    $q->whereRaw("LOWER(COALESCE(tags_list,'')) like '%gift%'")
                      ->orWhereRaw("LOWER(COALESCE(tags_list,'')) like '%voucher%'")
                      ->orWhereRaw("LOWER(COALESCE(tags_list,'')) like '%card%'")
                      ->orWhereRaw("LOWER(COALESCE(tags_list,'')) like '%present%'");
                });
            } else {
                $safe = str_replace(['%','_'], ['\\%','\\_'], $lc);
                $query->whereRaw("LOWER(COALESCE(tags_list,'')) like ?", ['%'.$safe.'%']);
            }
        }

        // Mode (online/in-person)
        $modeInput = $request->filled('mode') ? $request->string('mode')->toString() : $request->string('format')->toString();
        if ($modeInput) {
            $mode = strtolower($modeInput);
            $query->whereHas('options', function ($q) use ($mode) {
                $q->where('meta_name', 'locations')
                  ->whereHas('values', function ($q2) use ($mode) {
                      if ($mode === 'online') { $q2->whereRaw('LOWER(value) = ?', ['online']); }
                      else { $q2->whereRaw('LOWER(value) != ?', ['online']); }
                  });
            });
        }

        ProductSearchFilters::applyWhereFilter($query, $request->string('where')->toString());
        $locationContext = $this->resolveSearchLocationContext($request);

        $adults = $request->has('adults') ? (int) $request->input('adults') : null;
        $groupType = $request->has('group_type') ? $request->string('group_type')->toString() : null;
        ProductSearchFilters::applyWhoFilter($query, $adults, $groupType);

        if ($request->filled('price_max')) {
            $pm = (float) $request->input('price_max');
            $query->where(function($q) use ($pm) {
                $q->where(function($qp) use ($pm){ $qp->where('price', '<', 1000)->where('price', '<=', $pm); })
                  ->orWhere(function($qp) use ($pm){ $qp->where('price', '>=', 1000)->where('price', '<=', $pm * 100); })
                  ->orWhereHas('variants', function($qv) use ($pm){
                      $qv->where(function($qq) use ($pm){ $qq->where('price', '<', 1000)->where('price', '<=', $pm); })
                         ->orWhere(function($qq) use ($pm){ $qq->where('price', '>=', 1000)->where('price', '<=', $pm * 100); });
                  });
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int)$request->input('category_id'));
        } elseif ($request->filled('category')) {
            $cat = strtolower((string) $request->input('category'));
            $like = '%'.$cat.'%';
            $ids = ProductCategory::query()
                ->whereRaw('LOWER(name) LIKE ?', [$like])
                ->pluck('id')->all();
            if (!empty($ids)) $query->whereIn('category_id', $ids);
        }

        if ($request->boolean('anytime')) {
            $query->whereRaw("(JSON_EXTRACT(meta_json, '$.date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.date')) = '')")
                  ->whereRaw("(JSON_EXTRACT(meta_json, '$.start_date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.start_date')) = '')")
                  ->whereRaw("(JSON_EXTRACT(meta_json, '$.end_date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.end_date')) = '')");
        }

        $sort = $request->string('sort', 'popular')->toString();

        $perPage = (int) $request->integer('per_page', 48);
        $perPage = min(96, max(12, $perPage));

        $availabilityRange = $this->requestedAvailabilityRange($request);
        $productItems = $query->get();
        if ($availabilityRange) {
            $productItems = $productItems
                ->filter(fn (Product $product) => $this->hasAvailabilityInRange($product, $availabilityRange))
                ->values();
        }
        $productItems = $productItems
            ->reject(fn (Product $product) => EventListing::isPast($product) && ! $this->isEventLikeProduct($product))
            ->filter(fn (Product $product) => method_exists($product, 'hasDisplayableImage') ? $product->hasDisplayableImage() : true)
            ->values();
        $productItems = $productItems->map(fn (Product $product) => $this->decorateSearchProduct($product));

        $offeringItems = $this->buildV3SearchItems($request, $what, $type, $tag, $modeInput, $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null);
        if ($availabilityRange) {
            $offeringItems = $offeringItems
                ->filter(fn (OfferingV3 $offering) => $this->hasAvailabilityInRange($offering, $availabilityRange))
                ->values();
        }
        if ($groupType) {
            $offeringItems = $offeringItems
                ->filter(fn (OfferingV3 $offering) => $this->offeringMatchesGroupType($offering, $groupType))
                ->values();
        }
        $offeringItems = $offeringItems
            ->reject(fn (OfferingV3 $offering) => EventListing::isPast($offering) && ! $this->isEventLikeOffering($offering))
            ->filter(fn (OfferingV3 $offering) => method_exists($offering, 'hasDisplayableImage') ? $offering->hasDisplayableImage() : true)
            ->values();
        $offeringItems = $offeringItems->map(fn (OfferingV3 $offering) => $this->decorateSearchOffering($offering));

        $items = $productItems->concat($offeringItems)->values();

        if ($locationContext) {
            $items = $items->map(function ($item) use ($locationContext) {
                $distance = $this->bestSearchDistanceFromLocation($item, $locationContext);

                if ($distance !== null) {
                    $item->setAttribute('search_distance_miles', $distance);
                }

                return $item;
            });
        }

        $items = $this->sortSearchItems($items, $sort, $locationContext);
        if (in_array(strtolower(trim($sort)), ['', 'popular', 'relevance'], true)) {
            $items = $this->prioritizeExactCategoryMatches($items, $what);
        }

        $ratingFilter = trim((string) $request->input('rating', ''));
        if ($ratingFilter !== '') {
            $items = $this->applyRatingFilter($items, $ratingFilter);
        }

        // Finished dated experiences remain discoverable, but never compete
        // with upcoming offerings or appear among the recommendations.
        $items = $this->sortPastEventItemsLast($items);

        $total = $items->count();
        $recommendations = $this->buildSearchRecommendations($items, $request, $locationContext);
        $recommendedKeys = collect($recommendations)->pluck('item')->map(fn ($item) => $this->searchItemKey($item))->all();
        $catalogItems = $items->reject(fn ($item) => in_array($this->searchItemKey($item), $recommendedKeys, true))->values();
        $page = max(1, (int) $request->integer('page', 1));
        $catalogTotal = $catalogItems->count();
        $lastPage = max(1, (int) ceil($catalogTotal / max(1, $perPage)));
        $page = min($page, $lastPage);
        $products = new LengthAwarePaginator(
            $catalogItems->forPage($page, $perPage)->values(),
            $catalogTotal,
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );
        // Keep every filtered offering on the map, including the four promoted
        // recommendations that are intentionally removed from the catalogue.
        $searchMapData = $this->buildSearchMapData($items);

        $seoWhat = Str::squish($what);
        $seoWhere = Str::squish((string) $request->string('where')->toString());
        $seoTitleBase = 'Search all Offerings';
        if ($seoWhat !== '' && $seoWhere !== '') {
            $seoTitleBase = 'Search ' . $seoWhat . ' in ' . $seoWhere;
        } elseif ($seoWhat !== '') {
            $seoTitleBase = 'Search ' . $seoWhat;
        } elseif ($seoWhere !== '') {
            $seoTitleBase = 'Search in ' . $seoWhere;
        }

        $seoDescription = $seoWhat !== ''
            ? 'Search ' . $seoWhat . ' and browse live therapies, classes, events and workshops on We Offer Wellness.'
            : 'Search all offerings and browse live therapies, classes, events and workshops on We Offer Wellness.';

        $gridHtml = view('search.partials.results_cards', [
            'products' => $products,
            'showEmpty' => $catalogTotal > 0 || empty($recommendations),
        ])->render();
        $recommendationsHtml = view('search.partials.recommendations', [
            'recommendations' => $recommendations,
            'resultCount' => $total,
        ])->render();
        $paginationHtml = ($products instanceof LengthAwarePaginator && $products->total() > 0)
            ? $products->withQueryString()->onEachSide(1)->links('pagination::bootstrap-4')->render()
            : '';

        if ($request->expectsJson()) {
            return response()->json([
                'count' => $total,
                'count_text' => $total . ' matching offerings',
                'grid_html' => $gridHtml,
                'recommendations_html' => $recommendationsHtml,
                'pagination_html' => $paginationHtml,
                'map_data' => $searchMapData,
            ]);
        }

        return view('search.index', [
            'mapsKey' => env('GOOGLE_MAPS_API_KEY'),
            'products' => $products,
            'resultCount' => $total,
            'perPage' => $perPage,
            'searchGridHtml' => $gridHtml,
            'searchRecommendationsHtml' => $recommendationsHtml,
            'searchPaginationHtml' => $paginationHtml,
            'searchMapData' => $searchMapData,
            'seo' => [
                'title' => $seoTitleBase . ' | We Offer Wellness®',
                'description' => $seoDescription,
                'canonical' => url()->full(),
                'og_type' => 'website',
            ],
        ]);
    }

    private function buildV3SearchItems(
        Request $request,
        string $what,
        ?string $type,
        ?string $tag,
        ?string $modeInput,
        ?float $priceMax
    ): Collection {
        $query = OfferingV3::query()
            ->with(['category', 'type', 'vendor.user.settings', 'vendor.tiers', 'media', 'coverMedia'])
            ->whereIn('status', ['live', 'approved']);

        if ($what !== '') {
            $this->applyWhatSearchConstraint($query, $what, true);
        }

        if ($type) {
            $this->applyV3TypeFilter($query, $type);
        }

        if ($tag = $request->string('tag')->toString()) {
            $lc = strtolower($tag);
            if (in_array($lc, ['gift','gifts'], true)) {
                $query->where(function($q){
                    $q->whereRaw("LOWER(COALESCE(title,'')) like '%gift%'")
                      ->orWhereRaw("LOWER(COALESCE(summary,'')) like '%gift%'")
                      ->orWhereHas('category', function ($cq) {
                          $cq->whereRaw("LOWER(COALESCE(name,'')) like '%gift%'");
                      })
                      ->orWhereHas('type', function ($tq) {
                          $tq->whereRaw("LOWER(COALESCE(name,'')) like '%gift%'");
                      });
                });
            } else {
                $safe = str_replace(['%','_'], ['\\%','\\_'], $lc);
                $query->where(function($q) use ($safe) {
                    $q->whereRaw("LOWER(COALESCE(title,'')) like ?", ['%'.$safe.'%'])
                      ->orWhereRaw("LOWER(COALESCE(summary,'')) like ?", ['%'.$safe.'%'])
                      ->orWhereHas('category', function ($cq) use ($safe) {
                          $cq->whereRaw("LOWER(COALESCE(name,'')) like ?", ['%'.$safe.'%']);
                      })
                      ->orWhereHas('type', function ($tq) use ($safe) {
                          $tq->whereRaw("LOWER(COALESCE(name,'')) like ?", ['%'.$safe.'%']);
                      });
                });
            }
        }

        if ($priceMax !== null) {
            $query->where('price', '<=', $priceMax);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        } elseif ($request->filled('category')) {
            $cat = strtolower(trim((string) $request->input('category')));
            if ($cat !== '') {
                $like = '%'.$cat.'%';
                $ids = ProductCategory::query()
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->pluck('id')
                    ->all();
                if (!empty($ids)) {
                    $query->whereIn('category_id', $ids);
                }
            }
        }

        return $query->get();
    }

    private function applyWhatSearchConstraint($query, string $what, bool $includeOfferingDetails = false): void
    {
        $pattern = '%' . $what . '%';
        $starterKeys = ['starter', 'standard', 'community', 'free-starter', 'starter-package', ''];
        $starterSql = implode("', '", array_map(
            static fn (string $value): string => str_replace("'", "''", $value),
            $starterKeys
        ));
        $latestTierSql = "(SELECT LOWER(COALESCE(vt.tier, '')) FROM vendor_tiers vt WHERE vt.vendor_id = vendor_details.id ORDER BY COALESCE(vt.plan_started_at, vt.id) DESC, vt.id DESC LIMIT 1)";

        $query->where(function ($q) use ($pattern, $starterSql, $latestTierSql, $includeOfferingDetails) {
            $q->where('title', 'like', $pattern)
                ->orWhere('summary', 'like', $pattern)
                ->orWhereHas('category', function ($categoryQuery) use ($pattern) {
                    $categoryQuery->where('name', 'like', $pattern);
                })
                ->orWhereHas('vendor', function ($vendorQ) use ($pattern, $starterSql, $latestTierSql) {
                    $vendorQ->where('vendor_name', 'like', $pattern)
                        ->whereHas('user', function ($userQ) use ($starterSql) {
                            $userQ->whereRaw("LOWER(COALESCE(account_type, '')) NOT IN ('{$starterSql}')");
                        })
                        ->whereRaw("LOWER(COALESCE($latestTierSql, '')) NOT IN ('{$starterSql}')");
                });

            if ($includeOfferingDetails) {
                $q->orWhereExists(function ($dq) use ($pattern) {
                    $dq->selectRaw('1')
                        ->from('offering_details')
                        ->whereColumn('offering_details.offering_id', 'offerings.id')
                        ->where(function ($inner) use ($pattern) {
                            $inner->where('description', 'like', $pattern)
                                ->orWhere('what_to_expect', 'like', $pattern)
                                ->orWhere('whats_included', 'like', $pattern);
                        });
                });
            } else {
                $q->orWhere('body_html', 'like', $pattern)
                    ->orWhere('what_to_expect', 'like', $pattern)
                    ->orWhere('included', 'like', $pattern)
                    ->orWhere('tags_list', 'like', $pattern);
            }
        });
    }

    private function requestedAvailabilityRange(Request $request): ?array
    {
        $start = $this->parseSearchDate($request->string('when_start')->toString());
        $end = $this->parseSearchDate($request->string('when_end')->toString());

        if (! $start && $request->filled('when')) {
            $when = trim($request->string('when')->toString());
            $parts = preg_split('/\s+-\s+|\s+—\s+/', $when) ?: [];
            $start = $this->parseSearchDate($parts[0] ?? $when);
            $end = $this->parseSearchDate($parts[1] ?? '');
        }

        if (! $start) {
            return null;
        }

        if (! $end) {
            $end = $start->copy();
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    private function parseSearchDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function hasAvailabilityInRange(Product|OfferingV3 $item, array $range): bool
    {
        $vendor = $item->vendor;
        $user = $vendor?->user;
        if (! $user) {
            return false;
        }

        $settings = AvailabilityWindowService::extractAvailabilitySettings($user);
        $timezone = (string) ($settings['timezone'] ?? config('app.timezone', 'Europe/London'));

        try {
            $rangeStart = Carbon::parse($range['start'], $timezone)->startOfDay();
            $rangeEnd = Carbon::parse($range['end'], $timezone)->startOfDay();
        } catch (\Throwable $e) {
            return false;
        }

        if ($rangeEnd->lt($rangeStart)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd, $rangeStart];
        }

        $bookingHorizon = max(1, min(365, (int) ($settings['bookingHorizon'] ?? 30)));
        $horizonEnd = Carbon::now($timezone)->startOfDay()->addDays($bookingHorizon)->endOfDay();
        if ($rangeStart->gt($horizonEnd)) {
            return false;
        }
        if ($rangeEnd->gt($horizonEnd)) {
            $rangeEnd = $horizonEnd->copy()->startOfDay();
        }

        $weeklyWindows = AvailabilityWindowService::buildWeeklyWindows($user);
        $specificRecords = VendorAvailability::where('user_id', $user->id)
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->orderBy('date')
            ->get();
        $specificWindows = AvailabilityWindowService::buildSpecificWindows($specificRecords);

        $holdCutoff = Carbon::now($timezone)->subMinutes(10);
        $reservationRecords = Reservation::where('user_id', $user->id)
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->where(function ($query) use ($holdCutoff) {
                $query->where('is_confirmed', true)
                    ->orWhere('created_at', '>=', $holdCutoff);
            })
            ->orderBy('date')
            ->get();

        $bookingRecords = Booking::where('user_id', $user->id)
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->orderBy('date')
            ->get();

        $duration = $this->availabilityDurationMinutes($item, $settings);
        $days = max(1, (int) $rangeStart->diffInDays($rangeEnd) + 1);
        $slotsByDay = AvailabilityWindowService::generateSlots(
            $weeklyWindows,
            $settings,
            $duration,
            $days,
            $rangeStart,
            $specificWindows,
            $reservationRecords->concat($bookingRecords)->all()
        );

        foreach ($slotsByDay as $day) {
            if (! empty($day['slots']) && is_array($day['slots'])) {
                return true;
            }
        }

        return false;
    }

    private function availabilityDurationMinutes(Product|OfferingV3 $item, array $settings): int
    {
        if ($item instanceof OfferingV3) {
            $schedule = DB::table('offering_schedule')->where('offering_id', $item->id)->first();
            $duration = (int) ($schedule->duration_minutes ?? 0);
            if ($duration > 0) {
                return max(15, $duration);
            }
        }

        if ($item instanceof Product) {
            $meta = is_array($item->meta_json ?? null) ? $item->meta_json : [];
            foreach (['duration_minutes', 'duration_mins', 'duration'] as $key) {
                $duration = $this->parseDurationMinutes($meta[$key] ?? null);
                if ($duration > 0) {
                    return max(15, $duration);
                }
            }
        }

        return max(15, (int) ($settings['slotInterval'] ?? 60) ?: 60);
    }

    private function parseDurationMinutes(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $text = strtolower(trim((string) $value));
        if ($text === '') {
            return 0;
        }

        if (preg_match('/(\d+)\s*(hour|hr|hrs|hours)/i', $text, $match)) {
            return (int) $match[1] * 60;
        }

        if (preg_match('/(\d+)\s*(minute|min|mins|minutes)/i', $text, $match)) {
            return (int) $match[1];
        }

        if (preg_match('/\b(\d+)\b/', $text, $match)) {
            return (int) $match[1];
        }

        return 0;
    }

    private function sortSearchItems(Collection $items, string $sort, ?array $locationContext = null): Collection
    {
        $sort = strtolower(trim($sort));

        if (in_array($sort, ['', 'popular', 'relevance'], true)) {
            if ($locationContext) {
                return $items->sort(function ($left, $right) {
                    return $this->compareSearchItemsByLocation($left, $right);
                })->values();
            }

            return $items->sort(function ($left, $right) {
                return $this->compareSearchItemsByRelevance($left, $right);
            })->values();
        }

        return ProductRanking::sortCollection($items, $sort);
    }

    private function sortPastEventItemsLast(Collection $items): Collection
    {
        return $items
            ->partition(fn ($item) => ! (bool) data_get($item, 'is_past_event', false))
            ->flatten(1)
            ->values();
    }

    /**
     * Pick a small, explainable recommendation layer without changing the
     * underlying catalogue ranking. Every badge is backed by a material
     * difference rather than simply decorating rows 1-4.
     */
    private function buildSearchRecommendations(Collection $items, Request $request, ?array $locationContext): array
    {
        $items = $items
            ->reject(fn ($item) => (bool) data_get($item, 'is_past_event', false))
            ->values();

        if ($items->isEmpty()) {
            return [];
        }

        $exactCategoryMatches = $this->exactCategoryMatches($items, (string) $request->input('what', ''));
        // A category selected exactly by the customer is stronger intent than
        // a broad text hit in a title or description.
        $ordered = ($exactCategoryMatches->isNotEmpty() ? $exactCategoryMatches : $items)->values();
        $best = $ordered->first();
        $mode = strtolower(trim((string) ($request->input('mode') ?: $request->input('format', ''))));
        $allOnline = $ordered->every(fn ($item) => $this->isSearchItemOnline($item));
        $canClaimBest = $locationContext !== null || $mode === 'online' || $allOnline;
        $recommendations = [[
            'item' => $best,
            'label' => $canClaimBest ? ($allOnline && $mode !== 'in-person' ? 'BEST ONLINE MATCH' : 'BEST MATCH') : 'RECOMMENDED',
            'priority' => true,
        ]];
        $used = [$this->searchItemKey($best) => true];
        $candidates = $ordered->skip(1)->values();

        $add = function ($item, string $label) use (&$recommendations, &$used): bool {
            if (!$item) {
                return false;
            }
            $key = $this->searchItemKey($item);
            if (isset($used[$key])) {
                return false;
            }
            $used[$key] = true;
            $recommendations[] = ['item' => $item, 'label' => $label, 'priority' => false];
            return true;
        };

        $bestPrice = ProductRanking::priceValue($best);
        $value = $candidates
            ->filter(fn ($item) => $bestPrice !== null && ($price = ProductRanking::priceValue($item)) !== null && $price <= ($bestPrice * .85) && $price < $bestPrice)
            ->sortBy(fn ($item) => ProductRanking::priceValue($item) ?? PHP_FLOAT_MAX)
            ->first();
        if ($value) {
            $saving = max(0, (int) round($bestPrice - ProductRanking::priceValue($value)));
            $label = $this->isSearchItemOnline($value) && $saving >= 10
                ? 'ONLINE & £' . $saving . ' LESS'
                : 'BEST VALUE';
            $add($value, $label);
        }

        $rating = $candidates
            ->reject(fn ($item) => isset($used[$this->searchItemKey($item)]))
            ->sort(function ($left, $right) {
                $leftScore = ProductRanking::ratingValue($left) * log(1 + ProductRanking::reviewCountValue($left));
                $rightScore = ProductRanking::ratingValue($right) * log(1 + ProductRanking::reviewCountValue($right));
                return $rightScore <=> $leftScore;
            })
            ->first(function ($item) use ($best) {
                return ProductRanking::ratingValue($item) >= ProductRanking::ratingValue($best) + .2
                    || ProductRanking::reviewCountValue($item) >= max(10, ProductRanking::reviewCountValue($best) * 2);
            });
        $add($rating, 'TOP RATED');

        $now = now()->timestamp;
        $soonest = $candidates
            ->reject(fn ($item) => isset($used[$this->searchItemKey($item)]))
            ->filter(fn ($item) => is_numeric(data_get($item, 'next_available_timestamp')))
            ->sortBy(fn ($item) => (int) data_get($item, 'next_available_timestamp'))
            ->first(function ($item) use ($best, $now) {
                $candidate = (int) data_get($item, 'next_available_timestamp');
                $baseline = (int) data_get($best, 'next_available_timestamp', PHP_INT_MAX);
                return $candidate <= $now + 86400 && $candidate + 86400 < $baseline;
            });
        $add($soonest, 'AVAILABLE TODAY');

        if ($locationContext) {
            $closest = $candidates
                ->reject(fn ($item) => isset($used[$this->searchItemKey($item)]))
                ->filter(fn ($item) => is_numeric(data_get($item, 'search_distance_miles')))
                ->sortBy(fn ($item) => (float) data_get($item, 'search_distance_miles'))
                ->first(function ($item) use ($best) {
                    $candidate = (float) data_get($item, 'search_distance_miles');
                    $baseline = data_get($best, 'search_distance_miles');
                    return is_numeric($baseline) && $candidate + .5 < (float) $baseline;
                });
            $add($closest, 'CLOSEST');
        }

        foreach ($candidates as $fallback) {
            if (count($recommendations) >= 4) {
                break;
            }
            $add($fallback, 'RECOMMENDED');
        }

        // Mobile presents one honest decision pair. If there is no exact
        // location/online match (or no meaningful value alternative), lead
        // with the strongest review-backed option and a neutral recommendation.
        if (! $canClaimBest || ! $value) {
            $topRated = collect($recommendations)->first(fn ($recommendation) => $recommendation['label'] === 'TOP RATED');
            $recommended = collect($recommendations)->first(fn ($recommendation) => $recommendation['label'] === 'RECOMMENDED');
            if ($topRated && $recommended) {
                $priorityKeys = [$this->searchItemKey($topRated['item']), $this->searchItemKey($recommended['item'])];
                $recommendations = collect($recommendations)
                    ->sortBy(fn ($recommendation) => in_array($this->searchItemKey($recommendation['item']), $priorityKeys, true)
                        ? array_search($this->searchItemKey($recommendation['item']), $priorityKeys, true)
                        : 99)
                    ->values()
                    ->all();
            }
        }

        return array_slice($recommendations, 0, 4);
    }

    private function searchItemKey(mixed $item): string
    {
        $source = (string) data_get($item, 'source_version', get_class($item));
        return $source . ':' . (string) data_get($item, 'id');
    }

    private function isSearchItemOnline(mixed $item): bool
    {
        $mode = strtolower(trim((string) data_get($item, 'mode', '')));
        if ($mode === 'online') {
            return true;
        }
        $locations = data_get($item, 'locations', []);
        return is_array($locations) && count($locations) > 0
            && collect($locations)->every(fn ($location) => strtolower(trim((string) (is_array($location) ? ($location['name'] ?? '') : $location))) === 'online');
    }

    private function prioritizeExactCategoryMatches(Collection $items, string $what): Collection
    {
        $exactMatches = $this->exactCategoryMatches($items, $what);
        if ($exactMatches->isEmpty()) {
            return $items->values();
        }

        $exactKeys = $exactMatches
            ->map(fn ($item) => $this->searchItemKey($item))
            ->flip();

        return $items
            ->sortByDesc(fn ($item) => $exactKeys->has($this->searchItemKey($item)))
            ->values();
    }

    private function exactCategoryMatches(Collection $items, string $what): Collection
    {
        $needle = $this->normaliseSearchLabel($what);
        if ($needle === '') {
            return collect();
        }

        return $items
            ->filter(fn ($item) => $this->normaliseSearchLabel($this->searchItemCategoryName($item)) === $needle)
            ->values();
    }

    private function searchItemCategoryName(mixed $item): string
    {
        $category = data_get($item, 'category');
        if (is_scalar($category)) {
            return (string) $category;
        }

        return (string) data_get($category, 'name', data_get($item, 'category_name', ''));
    }

    private function normaliseSearchLabel(string $value): string
    {
        return Str::lower(Str::squish($value));
    }

    private function compareSearchItemsByRelevance(mixed $left, mixed $right): int
    {
        $leftNext = $this->nextAvailabilitySortValue($left);
        $rightNext = $this->nextAvailabilitySortValue($right);
        if ($leftNext !== $rightNext) {
            return $leftNext <=> $rightNext;
        }

        foreach ([
            [ProductRanking::itemKindPriority($left), ProductRanking::itemKindPriority($right)],
            [ProductRanking::planPriority($left), ProductRanking::planPriority($right)],
            [ProductRanking::ratingValue($left), ProductRanking::ratingValue($right)],
            [ProductRanking::reviewCountValue($left), ProductRanking::reviewCountValue($right)],
        ] as [$leftValue, $rightValue]) {
            if ($leftValue === $rightValue) {
                continue;
            }

            return $rightValue <=> $leftValue;
        }

        return strcasecmp(ProductRanking::titleValue($left), ProductRanking::titleValue($right));
    }

    private function compareSearchItemsByLocation(mixed $left, mixed $right): int
    {
        $leftDistance = data_get($left, 'search_distance_miles');
        $rightDistance = data_get($right, 'search_distance_miles');

        $leftHasDistance = is_numeric($leftDistance);
        $rightHasDistance = is_numeric($rightDistance);

        if ($leftHasDistance || $rightHasDistance) {
            if ($leftHasDistance !== $rightHasDistance) {
                return $leftHasDistance ? -1 : 1;
            }

            $leftValue = (float) $leftDistance;
            $rightValue = (float) $rightDistance;
            if (abs($leftValue - $rightValue) > 0.01) {
                return $leftValue <=> $rightValue;
            }
        }

        return $this->compareSearchItemsByRelevance($left, $right);
    }

    private function resolveSearchLocationContext(Request $request): ?array
    {
        $raw = trim((string) $request->string('where')->toString());
        if ($raw === '' || $this->isOnlineSearchLocation($raw)) {
            return null;
        }

        return $this->geocodeSearchLocation($raw);
    }

    private function isOnlineSearchLocation(string $value): bool
    {
        return Str::lower(trim($value)) === 'online';
    }

    private function geocodeSearchLocation(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $token = trim((string) config('services.mapbox.token'));
        if ($token === '') {
            return null;
        }

        $cacheKey = 'wow.search.location.' . md5(Str::lower($query));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query, $token) {
            try {
                $response = Http::timeout(8)->get(
                    'https://api.mapbox.com/geocoding/v5/mapbox.places/' . rawurlencode($query) . '.json',
                    [
                        'access_token' => $token,
                        'limit' => 1,
                        'autocomplete' => 'true',
                        'types' => 'place,locality,region,postcode,country,district,neighborhood,address',
                    ]
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
                    'label' => (string) ($feature['place_name'] ?? $query),
                    'lat' => (float) $center[1],
                    'lng' => (float) $center[0],
                    'relevance' => is_numeric($feature['relevance'] ?? null) ? (float) $feature['relevance'] : null,
                ];
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    private function bestSearchDistanceFromLocation(mixed $item, array $locationContext): ?float
    {
        $originLat = is_numeric($locationContext['lat'] ?? null) ? (float) $locationContext['lat'] : null;
        $originLng = is_numeric($locationContext['lng'] ?? null) ? (float) $locationContext['lng'] : null;

        if ($originLat === null || $originLng === null) {
            return null;
        }

        $distances = [];
        foreach ($this->searchItemLocationPoints($item) as $point) {
            if (! is_numeric($point['lat'] ?? null) || ! is_numeric($point['lng'] ?? null)) {
                continue;
            }

            $distances[] = $this->distanceMiles(
                $originLat,
                $originLng,
                (float) $point['lat'],
                (float) $point['lng']
            );
        }

        if (empty($distances)) {
            return null;
        }

        return min($distances);
    }

    private function searchItemLocationPoints(mixed $item): array
    {
        $points = [];

        foreach ([
            ['lat' => data_get($item, 'lat'), 'lng' => data_get($item, 'lng')],
            ['lat' => data_get($item, 'latitude'), 'lng' => data_get($item, 'longitude')],
            ['lat' => data_get($item, 'coords.lat'), 'lng' => data_get($item, 'coords.lng')],
        ] as $candidate) {
            if (is_numeric($candidate['lat'] ?? null) && is_numeric($candidate['lng'] ?? null)) {
                $points[] = [
                    'lat' => (float) $candidate['lat'],
                    'lng' => (float) $candidate['lng'],
                ];
            }
        }

        $vendor = data_get($item, 'vendor');
        $locations = $vendor && method_exists($vendor, 'relationLoaded') && $vendor->relationLoaded('locations')
            ? $vendor->locations
            : [];

        foreach ($locations as $location) {
            $lat = $location->lat ?? null;
            $lng = $location->lng ?? null;
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                continue;
            }

            $points[] = [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
            ];
        }

        $meta = data_get($item, 'meta_json', []);
        if (is_array($meta)) {
            $lat = $meta['lat'] ?? null;
            $lng = $meta['lng'] ?? null;
            if (is_numeric($lat) && is_numeric($lng)) {
                $points[] = [
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                ];
            }
        }

        return $points;
    }

    private function distanceMiles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 3958.7613;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function applyRatingFilter(Collection $items, string $ratingFilter): Collection
    {
        $value = strtolower(trim($ratingFilter));

        if ($value === '') {
            return $items;
        }

        if ($value === 'reviewed') {
            return $items->filter(function ($item) {
                $reviewCount = (int) data_get($item, 'review_count', data_get($item, 'reviews_count', 0));
                $vendorReviewCount = (int) data_get($item, 'vendor_review_count', 0);

                return ($reviewCount + $vendorReviewCount) > 0;
            })->values();
        }

        if (! is_numeric($value)) {
            return $items;
        }

        $threshold = (float) $value;

        return $items->filter(function ($item) use ($threshold) {
            $rating = data_get($item, 'rating', data_get($item, 'vendor_rating', data_get($item, 'reviews_avg_rating', data_get($item, 'avg_rating', null))));

            return is_numeric($rating) ? (float) $rating >= $threshold : false;
        })->values();
    }

    private function applyV3TypeFilter($query, ?string $type): void
    {
        if (!$type) return;
        $lc = strtolower(trim($type));

        if (in_array($lc, ['events', 'event'], true)) {
            $query->where(function ($q) {
                $q->whereHas('type', function ($tq) {
                    $tq->whereRaw("LOWER(COALESCE(name,'')) like '%event%'")
                       ->orWhereRaw("LOWER(COALESCE(name,'')) like '%workshop%'");
                })->orWhereHas('category', function ($cq) {
                    $cq->whereRaw("LOWER(COALESCE(name,'')) like '%event%'")
                       ->orWhereRaw("LOWER(COALESCE(name,'')) like '%workshop%'");
                });
            });
        } elseif (in_array($lc, ['workshops', 'workshop'], true)) {
            $query->whereHas('type', function ($tq) {
                $tq->whereRaw("LOWER(COALESCE(name,'')) like '%workshop%'");
            });
        } elseif (in_array($lc, ['classes', 'class'], true)) {
            $query->whereHas('type', function ($tq) {
                $tq->whereRaw("LOWER(COALESCE(name,'')) like '%class%'");
            });
        } elseif (in_array($lc, ['retreats', 'retreat'], true)) {
            $query->where(function ($q) {
                $q->whereHas('type', function ($tq) {
                    $tq->whereRaw("LOWER(COALESCE(name,'')) like '%retreat%'");
                })->orWhereHas('category', function ($cq) {
                    $cq->whereRaw("LOWER(COALESCE(name,'')) like '%retreat%'");
                });
            });
        } elseif (in_array($lc, ['gifts', 'gift'], true)) {
            $query->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(title,'')) like '%gift%'")
                  ->orWhereRaw("LOWER(COALESCE(summary,'')) like '%gift%'")
                  ->orWhereHas('type', function ($tq) {
                      $tq->whereRaw("LOWER(COALESCE(name,'')) like '%gift%'");
                  })
                  ->orWhereHas('category', function ($cq) {
                      $cq->whereRaw("LOWER(COALESCE(name,'')) like '%gift%'");
                  });
            });
        }
    }

    private function offeringMatchesGroupType(OfferingV3 $offering, ?string $groupType): bool
    {
        $groupType = strtolower(trim((string) $groupType));
        if (! in_array($groupType, ['solo', 'couple', 'group'], true)) {
            return true;
        }

        $rows = DB::table('offering_price_options')
            ->where('offering_id', $offering->id)
            ->select(['audience_type', 'pricing_type'])
            ->get();

        if ($rows->isNotEmpty()) {
            foreach ($rows as $row) {
                $audience = strtolower(trim((string) ($row->audience_type ?? '')));
                $pricing = strtolower(trim((string) ($row->pricing_type ?? '')));

                if ($groupType === 'solo' && ($audience === 'solo' || str_contains($pricing, 'solo'))) {
                    return true;
                }

                if ($groupType === 'couple' && ($audience === 'couple' || str_contains($pricing, 'couple'))) {
                    return true;
                }

                if ($groupType === 'group' && ($audience === 'group' || str_contains($pricing, 'group'))) {
                    return true;
                }
            }
        }

        $haystack = strtolower(trim(implode(' ', array_filter([
            (string) ($offering->title ?? ''),
            (string) ($offering->summary ?? ''),
            (string) ($offering->type?->name ?? ''),
            (string) ($offering->category?->name ?? ''),
        ]))));

        if ($groupType === 'solo') {
            return str_contains($haystack, 'solo') || str_contains($haystack, '1 person') || str_contains($haystack, '1-to-1') || str_contains($haystack, '1:1');
        }

        if ($groupType === 'couple') {
            return str_contains($haystack, 'couple') || str_contains($haystack, '2 person') || str_contains($haystack, 'pair') || str_contains($haystack, 'duo');
        }

        return str_contains($haystack, 'group') || str_contains($haystack, 'workshop') || str_contains($haystack, 'class');
    }

    private function offeringMode(OfferingV3 $offering): ?string
    {
        $locations = $offering->getLocations();
        $hasOnline = in_array('Online', $locations, true);
        $physical = array_values(array_filter($locations, fn ($location) => $location !== 'Online'));

        if ($hasOnline && count($physical) === 0) {
            return 'online';
        }

        if (count($physical) > 0 && !$hasOnline) {
            return 'in-person';
        }

        if ($hasOnline && count($physical) > 0) {
            return 'mixed';
        }

        return null;
    }

    private function offeringUrl(OfferingV3 $offering): string
    {
        return app(SeoStructureService::class)->canonicalOfferingUrl($offering);
    }

    private function applyTypeFilter($query, ?string $type): void
    {
        if (!$type) return;
        $lc = strtolower(trim($type));
        if ($lc === 'events' || $lc === 'event') {
            $query->where(function($q){
                $q->whereRaw("LOWER(product_type) like '%event%'")
                  ->orWhereRaw("LOWER(product_type) like '%workshop%'")
                  ->orWhereNotNull('meta_json->date')
                  ->orWhereNotNull('meta_json->start_date');
            });
        } elseif ($lc === 'workshops' || $lc === 'workshop') {
            $query->whereRaw("LOWER(product_type) like '%workshop%'");
        } elseif ($lc === 'classes' || $lc === 'class') {
            $query->whereRaw("LOWER(product_type) like '%class%'");
        } elseif (in_array($lc, ['therapies','therapy','experience','experiences'], true)) {
            $query->where(function($q){
                $q->whereRaw("LOWER(COALESCE(product_type,'')) not like '%event%'")
                  ->whereRaw("LOWER(COALESCE(product_type,'')) not like '%workshop%'")
                  ->whereRaw("LOWER(COALESCE(product_type,'')) not like '%class%'");
            });
        } elseif ($lc === 'retreats' || $lc === 'retreat') {
            $query->whereRaw("LOWER(product_type) like '%retreat%'");
        } elseif ($lc === 'gifts' || $lc === 'gift') {
            $query->where(function($q){
                $q->whereRaw("LOWER(COALESCE(tags_list,'')) like '%gift%'")
                  ->orWhereRaw("LOWER(COALESCE(product_type,'')) like '%gift%'");
            });
        } else {
            $safe = str_replace(['%','_'], ['\\%','\\_'], $lc);
            $query->whereRaw("LOWER(COALESCE(product_type,'')) like ?", ['%'.$safe.'%']);
        }
    }

    private function decorateSearchProduct(Product $product): Product
    {
        $seo = app(SeoStructureService::class);
        $locations = method_exists($product, 'getLocations') ? $product->getLocations() : [];
        $isOnline = in_array('Online', $locations, true);
        $physical = array_values(array_filter($locations, fn ($location) => $location !== 'Online'));
        $vendorPlan = $this->resolveVendorPlan($product->vendor);
        $meta = $product->meta_json ?? [];
        $product->setAttribute('type', $product->product_type ?: 'experience');
        $product->setAttribute('format', $seo->inferFormatKeyFromProduct($product));
        $product->setAttribute('modality', $seo->inferModalitySlugFromProduct($product));
        $product->setAttribute('category', $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null);
        $product->setAttribute('mode', $isOnline && count($physical) === 0 ? 'Online' : (count($physical) ? 'In-person' : null));
        $product->setAttribute('location', $physical[0] ?? ($isOnline ? 'Online' : null));
        $product->setAttribute('locations', $locations);
        $product->setAttribute('price', $product->price ?? null);
        $product->setAttribute('compare_at_price', $meta['compare_at_price'] ?? null);
        $product->setAttribute('currency', $meta['currency'] ?? 'GBP');
        $product->setAttribute('rating', round((float) ($product->reviews_avg_rating ?? 0), 1) ?: null);
        $product->setAttribute('review_count', (int) ($product->reviews_count ?? 0));
        $product->setAttribute('image', method_exists($product, 'getFirstImageUrl') ? $product->getFirstImageUrl() : null);
        $product->setAttribute('tags', $product->tags_list ? array_map('trim', explode(',', $product->tags_list)) : []);
        $product->setAttribute('url', $seo->canonicalProductUrl($product));
        $product->setAttribute('vendor_name', $product->vendor?->vendor_name ?? null);
        $product->setAttribute('source_version', 'legacy');
        $product->setAttribute('plan_key', $vendorPlan['key']);
        $product->setAttribute('plan_label', $vendorPlan['label']);
        $product->setAttribute('plan_priority', $vendorPlan['priority']);
        $nextAvailableAt = $this->nextAvailableAt($product);
        $product->setAttribute('next_available_at', $nextAvailableAt?->toIso8601String());
        $product->setAttribute('next_available_timestamp', $nextAvailableAt?->timestamp);
        $isEventLike = EventListing::isEventLike($product);
        $product->setAttribute('is_event_like', $isEventLike);
        $product->setAttribute('is_past_event', $isEventLike && EventListing::isPast($product));

        return $product;
    }

    private function typeSegment(Product $product): string
    {
        $type = strtolower((string) ($product->product_type ?? ''));
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

    private function decorateSearchOffering(OfferingV3 $offering): OfferingV3
    {
        $vendorPlan = $this->resolveVendorPlan($offering->vendor);
        $vendorReviewSummary = $offering->vendor?->review_summary ?? ['count' => 0, 'rating' => null];
        $isEventLike = EventListing::isEventLike($offering);

        $offering->setAttribute('product_type', (string) ($offering->type?->name ?? $offering->category?->name ?? 'experience'));
        $offering->setAttribute('tags_list', trim(implode(',', array_filter([
            (string) ($offering->type?->name ?? ''),
            (string) ($offering->category?->name ?? ''),
            (string) ($offering->summary ?? ''),
        ]))));
        $offering->setAttribute('vendor_name', $offering->vendor?->vendor_name ?? null);
        $offering->setAttribute('source_version', 'v3');
        $offering->setAttribute('variants_min_price', $offering->price);
        $offering->setAttribute('reviews_avg_rating', null);
        $offering->setAttribute('reviews_count', 0);
        $offering->setAttribute('url', $this->offeringUrl($offering));
        $offering->setAttribute('image', $offering->getFirstImageUrl());
        $offering->setAttribute('locations', $offering->getLocations());
        $offering->setAttribute('mode', $this->offeringMode($offering));
        $offering->setAttribute('rating', isset($vendorReviewSummary['rating']) ? round((float) $vendorReviewSummary['rating'], 1) : null);
        $offering->setAttribute('review_count', (int) ($vendorReviewSummary['count'] ?? 0));
        $offering->setAttribute('vendor_rating', isset($vendorReviewSummary['rating']) ? round((float) $vendorReviewSummary['rating'], 1) : null);
        $offering->setAttribute('vendor_review_count', (int) ($vendorReviewSummary['count'] ?? 0));
        $offering->setAttribute('plan_key', $vendorPlan['key']);
        $offering->setAttribute('plan_label', $vendorPlan['label']);
        $offering->setAttribute('plan_priority', $vendorPlan['priority']);
        $nextAvailableAt = $this->nextAvailableAt($offering);
        $offering->setAttribute('next_available_at', $nextAvailableAt?->toIso8601String());
        $offering->setAttribute('next_available_timestamp', $nextAvailableAt?->timestamp);
        $offering->setAttribute('is_event_like', $isEventLike);
        $offering->setAttribute('is_past_event', $isEventLike && EventListing::isPast($offering));

        return $offering;
    }

    private function buildSearchMapData(Collection $items): array
    {
        $mapData = [];

        foreach ($items as $item) {
            $vendor = $item->vendor ?? null;
            $locations = $vendor && $vendor->relationLoaded('locations') ? $vendor->locations : [];
            $title = (string) ($item->title ?? '');
            $url = (string) data_get($item, 'url', '');
            $added = 0;

            foreach ($locations as $location) {
                $lat = $location->lat ?? null;
                $lng = $location->lng ?? null;
                if (! is_numeric($lat) || ! is_numeric($lng)) {
                    continue;
                }

                $mapData[] = [
                    'pid' => (string) ($item->id ?? ''),
                    'title' => $title,
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'label' => trim((string) ($location->city ?? '') . ', ' . (string) ($location->address ?? '')),
                    'price_label' => $this->searchMapPriceLabel($item),
                    'image' => (string) data_get($item, 'image', ''),
                    'rating' => data_get($item, 'rating', data_get($item, 'vendor_rating', null)),
                    'review_count' => (int) data_get($item, 'review_count', data_get($item, 'vendor_review_count', 0)),
                    'url' => $url,
                ];
                $added++;
            }

            if ($added > 0) {
                continue;
            }

            $meta = data_get($item, 'meta_json', []);
            $lat = is_array($meta) ? ($meta['lat'] ?? null) : null;
            $lng = is_array($meta) ? ($meta['lng'] ?? null) : null;
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                continue;
            }

            $mapData[] = [
                'pid' => (string) ($item->id ?? ''),
                'title' => $title,
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'label' => (string) data_get($item, 'category.name', 'Location'),
                'price_label' => $this->searchMapPriceLabel($item),
                'image' => (string) data_get($item, 'image', ''),
                'rating' => data_get($item, 'rating', data_get($item, 'vendor_rating', null)),
                'review_count' => (int) data_get($item, 'review_count', data_get($item, 'vendor_review_count', 0)),
                'url' => $url,
            ];
        }

        return $mapData;
    }

    private function searchMapPriceLabel(Product|OfferingV3 $item): ?string
    {
        $minCandidates = [
            data_get($item, 'price_min'),
            data_get($item, 'variants_min_price'),
            data_get($item, 'price'),
            data_get($item, 'base_price'),
        ];

        $maxCandidates = [
            data_get($item, 'price_max'),
            data_get($item, 'variants_max_price'),
        ];

        $minPrice = null;
        foreach ($minCandidates as $candidate) {
            if (is_numeric($candidate)) {
                $minPrice = (float) $candidate;
                break;
            }
        }

        if ($minPrice === null) {
            return null;
        }

        if ($minPrice <= 0) {
            return 'Free';
        }

        $maxPrice = null;
        foreach ($maxCandidates as $candidate) {
            if (is_numeric($candidate)) {
                $maxPrice = (float) $candidate;
                break;
            }
        }

        $formatted = '£' . rtrim(rtrim(number_format($minPrice, 2, '.', ''), '0'), '.');
        if ($maxPrice !== null && abs($maxPrice - $minPrice) > 0.01) {
            return 'From ' . $formatted;
        }

        return $formatted;
    }

    private function isEventLikeProduct(Product $product): bool
    {
        return EventListing::isEventLike($product);
    }

    private function isEventLikeOffering(OfferingV3 $offering): bool
    {
        return EventListing::isEventLike($offering);
    }

    private function nextAvailabilitySortValue(mixed $item): int
    {
        $timestamp = data_get($item, 'next_available_timestamp');
        if (is_numeric($timestamp) && (int) $timestamp > 0) {
            return (int) $timestamp;
        }

        $value = data_get($item, 'next_available_at');
        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->timestamp;
            } catch (\Throwable $e) {
                return PHP_INT_MAX;
            }
        }

        return PHP_INT_MAX;
    }

    private function nextAvailableAt(Product|OfferingV3 $item): ?Carbon
    {
        $vendor = $item->vendor;
        $user = $vendor?->user;
        if (! $user) {
            return null;
        }

        $settings = AvailabilityWindowService::extractAvailabilitySettings($user);
        $timezone = (string) ($settings['timezone'] ?? config('app.timezone', 'Europe/London'));
        $duration = $this->availabilityDurationMinutes($item, $settings);
        $bookingHorizon = max(1, min(365, (int) ($settings['bookingHorizon'] ?? 30)));
        $cacheKey = implode(':', [
            (string) $user->id,
            (string) $duration,
            (string) $bookingHorizon,
            $timezone,
        ]);

        if (array_key_exists($cacheKey, $this->nextAvailabilityCache)) {
            return $this->nextAvailabilityCache[$cacheKey];
        }

        try {
            $anchor = Carbon::now($timezone)->startOfDay();
            $rangeEnd = $anchor->copy()->addDays($bookingHorizon)->endOfDay();
            $weeklyWindows = AvailabilityWindowService::buildWeeklyWindows($user);
            $specificRecords = VendorAvailability::where('user_id', $user->id)
                ->whereBetween('date', [$anchor->toDateString(), $rangeEnd->toDateString()])
                ->orderBy('date')
                ->get();
            $specificWindows = AvailabilityWindowService::buildSpecificWindows($specificRecords);

            $holdCutoff = Carbon::now($timezone)->subMinutes(10);
            $reservationRecords = Reservation::where('user_id', $user->id)
                ->whereBetween('date', [$anchor->toDateString(), $rangeEnd->toDateString()])
                ->where(function ($query) use ($holdCutoff) {
                    $query->where('is_confirmed', true)
                        ->orWhere('created_at', '>=', $holdCutoff);
                })
                ->orderBy('date')
                ->get();

            $bookingRecords = Booking::where('user_id', $user->id)
                ->whereBetween('date', [$anchor->toDateString(), $rangeEnd->toDateString()])
                ->orderBy('date')
                ->get();

            $slotsByDay = AvailabilityWindowService::generateSlots(
                $weeklyWindows,
                $settings,
                $duration,
                $bookingHorizon,
                $anchor,
                $specificWindows,
                $reservationRecords->concat($bookingRecords)->all()
            );

            foreach ($slotsByDay as $day) {
                $firstSlot = $day['slots'][0]['iso'] ?? null;
                if (! $firstSlot) {
                    continue;
                }

                $next = Carbon::parse($firstSlot, $timezone);
                $this->nextAvailabilityCache[$cacheKey] = $next;

                return $next;
            }
        } catch (\Throwable $e) {
            // Fall through and cache the miss so repeated items don't keep recalculating.
        }

        $this->nextAvailabilityCache[$cacheKey] = null;

        return null;
    }

    private function resolveVendorPlan(?VendorDetail $vendor): array
    {
        $tierValue = null;

        try {
            $tier = null;
            if ($vendor) {
                if ($vendor->relationLoaded('tiers')) {
                    $tier = $vendor->tiers
                        ->sortByDesc(fn ($row) => $row->plan_started_at ?? $row->id ?? 0)
                        ->first();
                } else {
                    $tier = $vendor->tiers()->orderByDesc('plan_started_at')->orderByDesc('id')->first();
                }
            }
            $tierValue = (string) ($tier?->tier ?? '');
        } catch (\Throwable $e) {
            $tierValue = '';
        }

        $key = $this->canonicalPlanKey($tierValue);
        $isStarter = $key === 'starter' || $key === '';

        return [
            'key' => $key ?: 'starter',
            'label' => $this->planTitleForKey($key ?: 'starter'),
            'priority' => $isStarter ? 0 : 1,
        ];
    }

    private function canonicalPlanKey(?string $value): string
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

    private function planTitleForKey(?string $value): string
    {
        return match ($this->canonicalPlanKey($value)) {
            'starter' => 'Starter',
            'business-accelerator' => 'Business Accelerator',
            'premium-accelerator' => 'Premium Accelerator',
            'become-partner' => 'Become Partner',
            default => Str::headline(trim((string) $value)) ?: 'Plan',
        };
    }
}
