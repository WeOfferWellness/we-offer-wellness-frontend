<?php

namespace App\Http\Controllers;

use App\Services\BackendCollectionsClient;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function show(
        Request $request,
        BackendCollectionsClient $collections,
        RedirectsController $redirects,
        ?string $slug = null
    ) {
        $slug = trim(rawurldecode((string) $slug));

        if ($slug === '') {
            return $redirects->shopifyCollection($request, null);
        }

        $payload = $collections->collection($slug, [
            'page' => max(1, (int) $request->query('page', 1)),
        ]);

        if ($payload === null) {
            return $redirects->shopifyCollection($request, $slug);
        }

        $collection = (array) ($payload['collection'] ?? []);
        $items = collect((array) ($payload['data'] ?? []))
            ->filter(fn ($item): bool => is_array($item))
            ->values();
        $meta = (array) ($payload['meta'] ?? []);
        $canonicalPath = trim((string) ($collection['canonical_path'] ?? ''));
        $canonicalPath = $canonicalPath !== '' ? '/'.ltrim($canonicalPath, '/') : '/collections/'.$slug;

        return view('collections.show', [
            'collection' => $collection,
            'items' => $items,
            'meta' => $meta,
            'slug' => $slug,
            'seo' => [
                'title' => (string) ($collection['meta_title'] ?? $collection['name'] ?? 'Wellness collection'),
                'description' => (string) ($collection['meta_description'] ?? $collection['description'] ?? ''),
                'canonical' => url($canonicalPath),
                'robots' => ! empty($collection['indexable']) ? 'index,follow' : 'noindex,follow',
            ],
        ]);
    }
}
