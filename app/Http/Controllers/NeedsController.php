<?php

namespace App\Http\Controllers;

use App\Services\BackendOfferingsClient;
use App\Services\NeedService;
use App\Support\EventListing;
use App\Support\ProductRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NeedsController extends Controller
{
    public function __construct(
        private readonly NeedService $needs,
        private readonly BackendOfferingsClient $offerings,
    ) {}

    public function index(Request $request)
    {
        return view('needs.index', [
            'seo' => ['title' => 'Browse by Need | We Offer Wellness™', 'description' => 'Find holistic therapies and experiences by what you need most.', 'robots' => 'index,follow'],
            'needs' => $this->needs->all()->all(),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $need = $this->needs->find($slug);
        abort_if($need === null, 404);

        $filters = [
            'q' => $need['name'], 'need' => $need['slug'],
            'format' => (string) $request->query('format', ''),
            'location' => (string) $request->query('location', ''),
            'sort' => (string) $request->query('sort', ''),
            'type' => (string) $request->query('type', ''),
            'rating' => (string) $request->query('rating', ''),
            'price_max' => (string) $request->query('price_max', ''),
            'page' => max(1, (int) $request->query('page', 1)),
            'per_page' => min(48, max(8, (int) $request->query('per_page', 24))),
        ];
        $hasFacets = (bool) (
            $filters['format']
            || $filters['location']
            || $filters['sort']
            || $filters['type']
            || $filters['rating']
            || $filters['price_max']
            || $request->has('page')
            || $request->has('per_page')
        );

        return view('needs.show', [
            'seo' => ['title' => $need['seo_title'], 'description' => $need['seo_description'], 'robots' => $hasFacets ? 'noindex,follow' : 'index,follow', 'canonical' => url('/needs/'.$slug)],
            'need' => $need,
            'filters' => $filters,
            'results' => $this->fetchOfferings($filters),
        ]);
    }

    private function fetchOfferings(array $query): array
    {
        $cacheKey = 'needs:offerings:backend:'.md5(json_encode($query));
        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($query): array {
            $filters = ['need' => $query['need'], 'per_page' => 100];
            $format = strtolower((string) ($query['format'] ?? ''));
            if ($format === 'online' || $format === 'in_person') $filters['mode'] = $format;
            if (($query['location'] ?? '') !== '') $filters['search'] = $query['location'];
            if (($query['type'] ?? '') !== '') $filters['type'] = $query['type'];
            if (($query['rating'] ?? '') !== '') $filters['rating'] = $query['rating'];
            if (($query['price_max'] ?? '') !== '') $filters['price_max'] = $query['price_max'];

            $items = $this->offerings->catalogue($filters, 6)
                ->reject(fn ($item) => EventListing::isPast($item))->values();
            $items = ProductRanking::sortCollection($items, (string) ($query['sort'] ?? ''));
            $total = $items->count();
            $page = max(1, (int) ($query['page'] ?? 1));
            $perPage = max(1, (int) ($query['per_page'] ?? 24));

            return ['items' => $items->forPage($page, $perPage)->values(), 'meta' => ['current_page' => $page, 'last_page' => max(1, (int) ceil($total / $perPage)), 'total' => $total]];
        });
    }
}
