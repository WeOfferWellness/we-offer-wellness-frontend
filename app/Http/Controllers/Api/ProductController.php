<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\EventListing;
use App\Support\ProductRanking;
use App\Support\ProductSearchFilters;
use App\Services\AvailabilityWindowService;
use App\Services\SeoStructureService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $base = Product::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->withMin('variants', 'price')
            ->withMax('variants', 'price')
            ->with(['media', 'options.values', 'category', 'vendor.tiers', 'vendor.user.defaultAvailability', 'vendor.user.settings']);

        $what = $request->string('what')->toString();
        $applySearch = function ($query) use ($what) {
            if (!$what) {
                return;
            }

            $this->applyWhatSearchConstraint($query, $what);
        };

        // Filters
        $query = clone $base;
        // Only visible products: include live/approved OR legacy rows without status set
        $query->where(function($q){
            $q->whereHas('status', function($qs){ $qs->whereIn('status', ['live','approved']); })
              ->orWhereNull('product_status_id');
        });
        $applySearch($query);

        $modeInput = $request->filled('mode') ? $request->string('mode')->toString() : $request->string('format')->toString();
        Log::info('search.products', [
            'what' => $what,
            'where' => $request->input('where'),
            'mode' => $modeInput,
            'type' => $request->input('type'),
            'user_id' => optional($request->user())->id,
        ]);

        if ($type = $request->string('type')->toString()) {
            // Apply a flexible type filter that mirrors LandingController
            // to avoid over-restricting to exact product_type values
            $this->applyTypeFilter($query, $type);
        }

        $tag = $request->string('tag')->toString();
        if ($tag) {
            $lc = strtolower($tag);
            if (in_array($lc, ['gift','gifts'], true)) {
                // Treat "gift" broadly: gift, gifts, voucher, card, present
                $query->where(function($q){
                    $q->whereRaw("LOWER(COALESCE(tags_list,'')) like '%gift%'")
                      ->orWhereRaw("LOWER(COALESCE(tags_list,'')) like '%voucher%'")
                      ->orWhereRaw("LOWER(COALESCE(tags_list,'')) like '%card%'")
                      ->orWhereRaw("LOWER(COALESCE(tags_list,'')) like '%present%'");
                });
            } else {
                $safe = str_replace(['%','_'], ['\\%','\\_'], strtolower($tag));
                $query->whereRaw("LOWER(COALESCE(tags_list,'')) like ?", ['%'.$safe.'%']);
            }
        }

        // Mode (Online / In-person) derived from options meta 'locations'
        // Accept alias: format=online|in-person
        $mode = $modeInput;
        if ($mode) {
            $mode = strtolower($mode);
            $query->whereHas('options', function ($q) use ($mode) {
                $q->where('meta_name', 'locations')
                  ->whereHas('values', function ($q2) use ($mode) {
                      if ($mode === 'online') {
                          $q2->whereRaw('LOWER(value) = ?', ['online']);
                      } else {
                          $q2->whereRaw('LOWER(value) != ?', ['online']);
                      }
                  });
            });
        }

        $whereInput = $request->string('where')->toString();
        ProductSearchFilters::applyWhereFilter($query, $whereInput);

        $adults = $request->has('adults') ? (int) $request->input('adults') : null;
        $groupType = $request->has('group_type') ? $request->string('group_type')->toString() : null;
        ProductSearchFilters::applyWhoFilter($query, $adults, $groupType);

        if ($request->filled('price_max')) {
            $pm = (float) $request->input('price_max');
            // Unit-aware price filter:
            // - Treat values < 1000 as pounds
            // - Treat values >= 1000 as pennies
            $query->where(function($q) use ($pm) {
                $q->where(function($qp) use ($pm){
                        $qp->where('price', '<', 1000)->where('price', '<=', $pm);
                    })
                  ->orWhere(function($qp) use ($pm){
                        $qp->where('price', '>=', 1000)->where('price', '<=', $pm * 100);
                    })
                  ->orWhereHas('variants', function($qv) use ($pm){
                        $qv->where(function($qq) use ($pm){
                                $qq->where('price', '<', 1000)->where('price', '<=', $pm);
                            })
                           ->orWhere(function($qq) use ($pm){
                                $qq->where('price', '>=', 1000)->where('price', '<=', $pm * 100);
                            });
                    });
            });
        }

        $sort = $request->string('sort', 'popular')->toString();
        // Category filter: by id or by name/slug via `category`
        if ($request->filled('category_id')) {
            $query->where('category_id', (int)$request->input('category_id'));
        } elseif ($request->filled('category')) {
            $cat = trim((string) $request->input('category'));
            if (is_numeric($cat)) {
                $query->where('category_id', (int)$cat);
            } else {
                $like = '%'.strtolower($cat).'%';
                $ids = \App\Models\ProductCategory::query()
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->pluck('id')->all();
                if (!empty($ids)) {
                    $query->whereIn('category_id', $ids);
                }
            }
        }

        // Anytime filter: products with no scheduled date/time
        if ($request->boolean('anytime')) {
            $query->whereRaw("(JSON_EXTRACT(meta_json, '$.date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.date')) = '')")
                  ->whereRaw("(JSON_EXTRACT(meta_json, '$.start_date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.start_date')) = '')")
                  ->whereRaw("(JSON_EXTRACT(meta_json, '$.end_date') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.end_date')) = '')");
        }

        $limit = (int) $request->integer('limit', 50);
        $products = ProductRanking::sortCollection(
            $query->get()
                ->reject(fn (Product $product) => EventListing::isPast($product))
                ->filter(fn (Product $product) => method_exists($product, 'hasDisplayableImage') ? $product->hasDisplayableImage() : true)
                ->values(),
            $sort
        )->take($limit)->values();
        // Fallback: if filtering by tag + price yields nothing, retry without tag
        if ($products->isEmpty() && $tag && $request->filled('price_max')) {
            $retry = clone $base;
            $retry->where(function($q){
                $q->whereHas('status', function($qs){ $qs->whereIn('status', ['live','approved']); })
                  ->orWhereNull('product_status_id');
            });
            $applySearch($retry);
            if ($type = $request->string('type')->toString()) {
                $this->applyTypeFilter($retry, $type);
            }
            if ($modeInput) {
                $mode = strtolower($modeInput);
                $retry->whereHas('options', function ($q) use ($mode) {
                    $q->where('meta_name', 'locations')
                      ->whereHas('values', function ($q2) use ($mode) {
                          if ($mode === 'online') {
                              $q2->whereRaw('LOWER(value) = ?', ['online']);
                          } else {
                              $q2->whereRaw('LOWER(value) != ?', ['online']);
                          }
                      });
                });
            }
            ProductSearchFilters::applyWhereFilter($retry, $whereInput);
            ProductSearchFilters::applyWhoFilter($retry, $adults, $groupType);
            if ($request->filled('price_max')) {
                $pm = (float) $request->input('price_max');
                $retry->where(function($q) use ($pm) {
                    $q->where(function($qp) use ($pm){
                            $qp->where('price', '<', 1000)->where('price', '<=', $pm);
                        })
                      ->orWhere(function($qp) use ($pm){
                            $qp->where('price', '>=', 1000)->where('price', '<=', $pm * 100);
                        })
                      ->orWhereHas('variants', function($qv) use ($pm){
                            $qv->where(function($qq) use ($pm){
                                    $qq->where('price', '<', 1000)->where('price', '<=', $pm);
                                })
                               ->orWhere(function($qq) use ($pm){
                                    $qq->where('price', '>=', 1000)->where('price', '<=', $pm * 100);
                                });
                        });
                });
            }
            if ($request->filled('category_id')) {
                $retry->where('category_id', (int)$request->input('category_id'));
            }
            $sort = $request->string('sort', 'popular')->toString();

            $products = ProductRanking::sortCollection(
                $retry->get()
                    ->reject(fn (Product $product) => EventListing::isPast($product))
                    ->filter(fn (Product $product) => method_exists($product, 'hasDisplayableImage') ? $product->hasDisplayableImage() : true)
                    ->values(),
                $sort
            )->take($limit)->values();
        }

        // Transform
        $items = $products->map(function (Product $p) {
            $seo = app(SeoStructureService::class);
            $locations = $p->getLocations();
            $isOnline = in_array('Online', $locations, true);
            $physicalLocations = array_values(array_filter($locations, fn($l) => $l !== 'Online'));
            $vendor = $p->vendor;
            $vendorUser = $vendor?->user;
            $availabilityDays = collect(AvailabilityWindowService::buildWeeklyWindows($vendorUser))
                ->filter(fn (array $rule) => ! empty($rule['enabled']) && ! empty($rule['windows']))
                ->keys()
                ->map(fn (string $day) => [
                    'mon' => 1,
                    'tue' => 2,
                    'wed' => 3,
                    'thu' => 4,
                    'fri' => 5,
                    'sat' => 6,
                    'sun' => 0,
                ][$day] ?? null)
                ->filter(fn ($day) => $day !== null)
                ->unique()
                ->values()
                ->all();
            $planKey = $vendor?->tiers
                ?->sortByDesc(fn ($tier) => $tier->plan_started_at ?? $tier->id)
                ->first()?->tier;

            $meta = $p->meta_json ?? [];
            $lat = $meta['lat'] ?? null;
            $lng = $meta['lng'] ?? null;
            $date = $meta['date'] ?? null;
            $start = $meta['start_date'] ?? null;
            $end = $meta['end_date'] ?? null;

            // Map type to URL segment
            $t = strtolower((string) $p->product_type);
            $tags = strtolower((string) $p->tags_list);
            // Normalize prices (always GBP pounds)
            $norm = function($v){ if(!is_numeric($v)) return null; $n = (float)$v; if($n >= 1000) $n = $n/100; return $n; };
            $pPrice = $norm($p->price ?? null);
            $vMin = $norm($p->variants_min_price ?? null);
            $vMax = $norm($p->variants_max_price ?? null);
            $minPrice = null; $maxPrice = null;
            foreach ([$pPrice, $vMin] as $cand) { if($cand !== null) { $minPrice = $minPrice === null ? $cand : min($minPrice, $cand); } }
            foreach ([$pPrice, $vMax] as $cand) { if($cand !== null) { $maxPrice = $maxPrice === null ? $cand : max($maxPrice, $cand); } }

            return [
                'id' => $p->id,
                'title' => $p->title,
                'summary' => $p->summary,
                'type' => $p->product_type ?: 'experience',
                'category' => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name] : null,
                'mode' => $isOnline && count($physicalLocations) === 0 ? 'Online' : (count($physicalLocations) ? 'In-person' : null),
                'location' => $physicalLocations[0] ?? ($isOnline ? 'Online' : null),
                'locations' => $locations,
                'vendor_name' => $vendor?->vendor_name
                    ?: ($vendorUser?->full_name ?: $vendorUser?->name),
                'plan_key' => $planKey ?: ($vendorUser?->account_type ?? null),
                'availability_days' => $availabilityDays,
                'lat' => is_numeric($lat) ? (float)$lat : null,
                'lng' => is_numeric($lng) ? (float)$lng : null,
                'date' => $date,
                'start_date' => $start,
                'end_date' => $end,
                'price' => $minPrice,
                'price_min' => $minPrice,
                'price_max' => $maxPrice,
                'compare_at_price' => $meta['compare_at_price'] ?? null,
                'currency' => $meta['currency'] ?? 'GBP',
                'rating' => round((float)($p->reviews_avg_rating ?? 0), 1) ?: null,
                'review_count' => (int)($p->reviews_count ?? 0),
                'image' => $p->getFirstImageUrl(),
                'tags' => $p->tags_list ? array_map('trim', explode(',', $p->tags_list)) : [],
                'url' => $seo->canonicalProductUrl($p),
            ];
        });

        return response()->json($items);
    }

    private function applyWhatSearchConstraint($query, string $what): void
    {
        $pattern = '%' . $what . '%';
        $starterKeys = ['starter', 'standard', 'community', 'free-starter', 'starter-package', ''];
        $starterSql = implode("', '", array_map(
            static fn (string $value): string => str_replace("'", "''", $value),
            $starterKeys
        ));
        $latestTierSql = "(SELECT LOWER(COALESCE(vt.tier, '')) FROM vendor_tiers vt WHERE vt.vendor_id = vendor_details.id ORDER BY COALESCE(vt.plan_started_at, vt.id) DESC, vt.id DESC LIMIT 1)";

        $query->where(function ($q) use ($pattern, $starterSql, $latestTierSql) {
            $q->where('title', 'like', $pattern)
                ->orWhere('summary', 'like', $pattern)
                ->orWhere('body_html', 'like', $pattern)
                ->orWhere('what_to_expect', 'like', $pattern)
                ->orWhere('included', 'like', $pattern)
                ->orWhere('tags_list', 'like', $pattern)
                ->orWhereHas('vendor', function ($vendorQ) use ($pattern, $starterSql, $latestTierSql) {
                    $vendorQ->where(function ($identityQ) use ($pattern) {
                        $identityQ->where('vendor_name', 'like', $pattern)
                            ->orWhereHas('user', function ($userQ) use ($pattern) {
                                $userQ->where('first_name', 'like', $pattern)
                                    ->orWhere('last_name', 'like', $pattern)
                                    ->orWhere('name', 'like', $pattern);
                            });
                    })
                        ->whereHas('user', function ($userQ) use ($starterSql) {
                            $userQ->whereRaw("LOWER(COALESCE(account_type, '')) NOT IN ('{$starterSql}')");
                        })
                        ->whereRaw("LOWER(COALESCE($latestTierSql, '')) NOT IN ('{$starterSql}')");
                });
        });
    }

    // Flexible type filtering consistent with LandingController
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
            // Fallback: substring match on product_type
            $safe = str_replace(['%','_'], ['\\%','\\_'], $lc);
            $query->whereRaw("LOWER(COALESCE(product_type,'')) like ?", ['%'.$safe.'%']);
        }
    }
}
