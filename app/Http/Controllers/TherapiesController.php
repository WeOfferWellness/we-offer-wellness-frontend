<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\VendorAvailability;
use App\Services\AvailabilityWindowService;
use App\Support\EventListing;
use App\Support\ProductRanking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TherapiesController extends Controller
{
    public function index(Request $request)
    {
        $therapies = $this->therapiesIndex();
        $offeringCount = Product::query()
            ->whereHas('status', function ($qs) {
                $qs->whereIn('status', ['live', 'approved']);
            })
            ->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(product_type,'')) like '%therap%'");
            })
            ->count();
        $featuredOfferings = Product::query()
            ->with(['media', 'category', 'options.values', 'vendor.tiers', 'vendor.user.settings'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->withMin('variants', 'price')
            ->whereHas('status', function ($qs) {
                $qs->whereIn('status', ['live', 'approved']);
            })
            ->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(product_type,'')) like '%therap%'");
            })
            ->latest('updated_at')
            ->limit(240)
            ->get()
            ->reject(fn ($product) => EventListing::isPast($product))
            ->map(function (Product $product) {
                $nextAvailableAt = $this->nextAvailableAt($product);
                $product->setAttribute('next_available_at', $nextAvailableAt?->toIso8601String());
                $product->setAttribute('next_available_timestamp', $nextAvailableAt?->timestamp);
                $product->setAttribute('has_availability_schedule', ProductRanking::availabilityPriority($product) > 0);

                return $product;
            })
            ->filter(fn (Product $product) => filled($product->next_available_at ?? null) || (bool) ($product->has_availability_schedule ?? false))
            ->values();

        $featuredOfferings = ProductRanking::sortCollection($featuredOfferings)->take(8)->values();

        return view('therapies.index', [
            'seo' => [
                'title' => 'Therapies | We Offer Wellness™',
                'description' => 'Explore holistic therapies and modalities, from sound healing and breathwork to massage and Reiki.',
                'robots' => 'index,follow',
            ],
            'therapies' => $therapies,
            'featuredOfferings' => $featuredOfferings,
            'offeringCount' => $offeringCount,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $therapy = $this->findTherapyBySlug($slug);
        abort_if($therapy === null, 404);

        $filters = [
            'therapy'  => $therapy['key'],
            'format'   => (string) $request->query('format', ''),
            'location' => (string) $request->query('location', ''),
            'sort'     => (string) $request->query('sort', ''),
            'page'     => max(1, (int) $request->query('page', 1)),
            'per_page' => min(48, max(8, (int) $request->query('per_page', 24))),
        ];

        $results = $this->fetchOfferings($filters);

        $hasFacets = (bool) (
            $filters['format'] ||
            $filters['location'] ||
            $filters['sort'] ||
            $request->has('page') ||
            $request->has('per_page')
        );

        return view('therapies.show', [
            'seo' => [
                'title' => $therapy['seo_title'] ?? ($therapy['title'] . ' | We Offer Wellness™'),
                'description' => $therapy['seo_description'] ?? ('Browse ' . $therapy['title'] . ' experiences and therapies.'),
                'robots' => $hasFacets ? 'noindex,follow' : 'index,follow',
                'canonical' => url('/therapies/' . $slug),
            ],
            'therapy' => $therapy,
            'filters' => $filters,
            'results' => $results,
        ]);
    }

    private function therapiesIndex(): array
    {
        return [
            [
                'key' => 'sound-healing',
                'slug' => 'sound-healing',
                'title' => 'Sound Healing',
                'seo_title' => 'Sound Healing | We Offer Wellness™',
                'seo_description' => 'Explore sound baths, gong sessions and vibrational therapy experiences.',
            ],
            [
                'key' => 'reiki',
                'slug' => 'reiki',
                'title' => 'Reiki',
                'seo_title' => 'Reiki Healing | Distance Reiki, In-Person Sessions & Online Support | We Offer Wellness™',
                'seo_description' => 'Explore Reiki, distance Reiki and in-person sessions with trusted practitioners across the UK. Compare online and local options, then book with confidence.',
            ],
            [
                'key' => 'reflexology',
                'slug' => 'reflexology',
                'title' => 'Reflexology',
                'seo_title' => 'Reflexology | We Offer Wellness™',
                'seo_description' => 'Pressure-point bodywork and foot therapy to support circulation and relaxation.',
            ],
            [
                'key' => 'acupuncture',
                'slug' => 'acupuncture',
                'title' => 'Acupuncture',
                'seo_title' => 'Acupuncture | We Offer Wellness™',
                'seo_description' => 'Acupuncture and TCM-inspired sessions focusing on balance, repair and regulation.',
            ],
            [
                'key' => 'breathwork',
                'slug' => 'breathwork',
                'title' => 'Breathwork (1:1)',
                'seo_title' => 'Breathwork | 1:1 Sessions, Workshops & Online Support | We Offer Wellness™',
                'seo_description' => 'Browse breathwork sessions, workshops and online options from trusted practitioners. Find the right style for stress relief, regulation and deeper self-connection.',
            ],
            [
                'key' => 'massage',
                'slug' => 'massage',
                'title' => 'Massage Therapy',
                'seo_title' => 'Massage Therapy | We Offer Wellness™',
                'seo_description' => 'Discover massage experiences for relaxation, recovery and whole-body care.',
            ],
            [
                'key' => 'hypnotherapy',
                'slug' => 'hypnotherapy',
                'title' => 'Hypnotherapy',
                'seo_title' => 'Hypnotherapy | We Offer Wellness™',
                'seo_description' => 'Hypnotherapy sessions for habit change, calm and subconscious support.',
            ],
            [
                'key' => 'corporate-wellness',
                'slug' => 'corporate-wellness',
                'title' => 'Corporate Desk Reset',
                'seo_title' => 'Corporate Wellness | We Offer Wellness™',
                'seo_description' => 'On-site massage, breathwork and reset sessions curated for teams and events.',
            ],
            [
                'key' => 'somatic-experiencing',
                'slug' => 'somatic-experiencing',
                'title' => 'Somatic Experiencing',
                'seo_title' => 'Somatic Experiencing | We Offer Wellness™',
                'seo_description' => 'Body-first nervous system work to support release, grounding and resilience.',
            ],
            [
                'key' => 'meditation',
                'slug' => 'meditation',
                'title' => 'Meditation & Mindfulness',
                'seo_title' => 'Meditation | We Offer Wellness™',
                'seo_description' => 'Explore guided meditations and mindfulness experiences for everyday wellbeing.',
            ],
        ];
    }

    private function findTherapyBySlug(string $slug): ?array
    {
        foreach ($this->therapiesIndex() as $therapy) {
            if (($therapy['slug'] ?? null) === $slug) {
                return $therapy;
            }
        }

        $category = ProductCategory::query()
            ->get()
            ->first(function (ProductCategory $category) use ($slug): bool {
                return Str::slug((string) ($category->slug ?: $category->name)) === $slug;
            });

        if (! $category) {
            $subcategory = ProductSubcategory::query()
                ->whereIn('status', ['approved', 'live'])
                ->get()
                ->first(fn (ProductSubcategory $subcategory): bool => Str::slug((string) ($subcategory->slug ?: $subcategory->name)) === $slug);
            if (! $subcategory) {
                return null;
            }

            $title = trim((string) ($subcategory->name ?? '')) ?: Str::headline($slug);

            return [
                'key' => $slug,
                'slug' => $slug,
                'title' => $title,
                'category_id' => (int) $subcategory->category_id,
                'subcategory_id' => (int) $subcategory->id,
                'seo_title' => $title . ' | We Offer Wellness™',
                'seo_description' => 'Explore ' . $title . ' experiences and therapies.',
            ];
        }

        $title = trim((string) ($category->name ?? ''));
        if ($title === '') {
            $title = Str::headline($slug);
        }

        return [
            'key' => (string) ($category->slug ?: $slug),
            'slug' => (string) ($category->slug ?: $slug),
            'title' => $title,
            'category_id' => (int) $category->id,
            'subcategory_id' => null,
            'seo_title' => $title . ' | We Offer Wellness™',
            'seo_description' => 'Explore ' . $title . ' experiences and therapies.',
        ];
    }

    private function fetchOfferings(array $query): array
    {
        $cacheKey = 'therapies:offerings:' . md5(json_encode($query));

        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($query) {
            $therapyKey = $query['therapy'] ?? null;
            if (!$therapyKey) {
                return ['items' => [], 'meta' => []];
            }

            $perPage = (int)($query['per_page'] ?? 24);
            $page    = max(1, (int)($query['page'] ?? 1));

            $builder = Product::query()
                ->with(['media', 'category', 'subcategory', 'options.values', 'vendor.tiers', 'vendor.user.settings'])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->withMin('variants', 'price')
                ->withMax('variants', 'price')
                ->where(function ($q) {
                    $q->whereRaw("LOWER(COALESCE(product_type,'')) like '%therap%'");
                })
                ->where(function ($q) use ($therapyKey) {
                    $taxonomy = $this->taxonomyForTherapy($therapyKey);
                    if ($taxonomy) {
                        $q->where('category_id', $taxonomy['category_id']);
                        if ($taxonomy['subcategory_id']) {
                            $q->where('subcategory_id', $taxonomy['subcategory_id']);
                        }
                    } else {
                        $slug = strtolower($therapyKey);
                        $q->whereRaw("LOWER(COALESCE(tags_list,'')) like ?", ['%'.$slug.'%'])
                          ->orWhereRaw("LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(meta_json, '$.therapy_slug')), '')) = ?", [$slug]);
                    }
                })
                ->where(function ($q) {
                    $q->whereHas('status', function ($qs) {
                        $qs->whereIn('status', ['live', 'approved']);
                    });
                });

            $format = strtolower((string) ($query['format'] ?? ''));
            if ($format === 'online') {
                $builder->whereHas('options', function ($q) {
                    $q->where('meta_name', 'locations')
                        ->whereHas('values', function ($q2) {
                            $q2->whereRaw('LOWER(value) = ?', ['online']);
                        });
                });
            } elseif ($format === 'in_person') {
                $builder->whereHas('options', function ($q) {
                    $q->where('meta_name', 'locations')
                        ->whereHas('values', function ($q2) {
                            $q2->whereRaw('LOWER(value) != ?', ['online']);
                        });
                });
            }

            if ($location = trim((string) ($query['location'] ?? ''))) {
                $builder->whereHas('options', function ($q) use ($location) {
                    $q->where('meta_name', 'locations')
                        ->whereHas('values', function ($q2) use ($location) {
                            $q2->where('value', 'like', "%{$location}%");
                        });
                });
            }

            $sort = $query['sort'] ?? '';
            $items = $builder->get()
                ->reject(fn ($product) => EventListing::isPast($product))
                ->values();
            $items = ProductRanking::sortCollection($items, $sort);
            $total = $items->count();
            $items = $items->forPage($page, $perPage)->values();

            $lastPage = max(1, (int) ceil($total / max(1, $perPage)));

            return [
                'items' => $items,
                'meta'  => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'per_page'     => $perPage,
                ],
            ];
        });
    }

    private function taxonomyForTherapy(string $slug): ?array
    {
        $safe = strtolower($slug);
        $title = collect($this->therapiesIndex())
            ->firstWhere('slug', $slug)['title'] ?? $slug;

        $category = ProductCategory::query()
            ->where(function ($query) use ($safe, $title) {
                $query->whereRaw('LOWER(slug) like ?', ['%'.$safe.'%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%'.$safe.'%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%'.strtolower($title).'%']);
            })
            ->first();

        if ($category) {
            return ['category_id' => (int) $category->id, 'subcategory_id' => null];
        }

        $subcategory = ProductSubcategory::query()
            ->whereIn('status', ['approved', 'live'])
            ->where(function ($query) use ($safe, $title) {
                $query->where('slug', $safe)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($title)]);
            })
            ->first();

        return $subcategory
            ? ['category_id' => (int) $subcategory->category_id, 'subcategory_id' => (int) $subcategory->id]
            : null;
    }

    private function nextAvailableAt(Product $item): ?Carbon
    {
        static $nextAvailabilityCache = [];

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

        if (array_key_exists($cacheKey, $nextAvailabilityCache)) {
            return $nextAvailabilityCache[$cacheKey];
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
                $nextAvailabilityCache[$cacheKey] = $next;

                return $next;
            }
        } catch (\Throwable $e) {
            // Fall through and cache the miss so repeated items don't keep recalculating.
        }

        $nextAvailabilityCache[$cacheKey] = null;

        return null;
    }

    private function availabilityDurationMinutes(Product $item, array $settings): int
    {
        $meta = is_array($item->meta_json ?? null) ? $item->meta_json : [];
        foreach (['duration_minutes', 'duration_mins', 'duration'] as $key) {
            $duration = $this->parseDurationMinutes($meta[$key] ?? null);
            if ($duration > 0) {
                return max(15, $duration);
            }
        }

        return max(15, (int) ($settings['slotInterval'] ?? 60) ?: 60);
    }

    private function parseDurationMinutes(mixed $value): int
    {
        if (is_numeric($value)) {
            $value = (float) $value;
            if ($value > 1000 && fmod($value, 100) === 0.0) {
                $value /= 100;
            }

            return (int) round($value);
        }

        if (is_string($value)) {
            $text = trim(strtolower($value));
            if ($text === '') {
                return 0;
            }

            if (preg_match('/(\d+(?:\.\d+)?)\s*(min|mins|minute|minutes)\b/', $text, $matches)) {
                return (int) round((float) $matches[1]);
            }

            if (preg_match('/(\d+(?:\.\d+)?)\s*(hr|hrs|hour|hours)\b/', $text, $matches)) {
                return (int) round(((float) $matches[1]) * 60);
            }
        }

        return 0;
    }

}
