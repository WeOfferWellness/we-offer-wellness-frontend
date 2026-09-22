<?php

namespace App\Http\Controllers;

use App\Services\GuideRegistryService;
use App\Services\BackendGuideService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(GuideRegistryService $guides, BackendGuideService $backendGuides): View
    {
        $page = $guides->guidesHub();
        $remote = collect($backendGuides->all())->map(fn (array $guide): array => [
            'label' => $guide['title'] ?? 'Guide',
            'summary' => $guide['summary'] ?? '',
            'url' => url('/'.($guide['format'] ?? 'therapies').'/'.($guide['modality'] ?? '').'/guides/'.($guide['slug'] ?? '')),
        ]);
        $page['popular_guides'] = $remote->concat($page['popular_guides'] ?? [])->unique('url')->values()->take(12)->all();
        return view('guides.hub', [
            'page' => $page,
        ]);
    }

    public function format(GuideRegistryService $guides, string $format): View
    {
        $page = $guides->formatHub($format);
        if ($page !== null) {
            $page = $this->mergeRemoteHubGuides($page, app(BackendGuideService::class)->all(), $format);
        }
        abort_if($page === null, 404);

        return view('guides.hub', ['page' => $page]);
    }

    public function modality(GuideRegistryService $guides, string $format, string $modality): View
    {
        $page = $guides->modalityHub($format, $modality);
        if ($page !== null) {
            $page = $this->mergeRemoteHubGuides($page, app(BackendGuideService::class)->all(), $format, $modality);
        }
        abort_if($page === null, 404);

        return view('guides.hub', ['page' => $page]);
    }

    public function show(GuideRegistryService $guides, BackendGuideService $backendGuides, string $format, string $modality, string $guide): View
    {
        if ($remote = $backendGuides->find($format, $modality, $guide)) {
            $page = $this->remotePage($remote, $format, $modality, $guide);
            return view('guides.show', ['page' => $page]);
        }

        $page = $guides->guidePage($format, $modality, $guide);
        abort_if($page === null, 404);

        return view('guides.show', ['page' => $page]);
    }

    public function legacy(GuideRegistryService $guides, string $legacySlug): RedirectResponse
    {
        $target = $guides->legacyRedirects()['/' . trim($legacySlug, '/')] ?? null;
        abort_if($target === null, 404);

        return redirect($target, 301);
    }

    private function remotePage(array $guide, string $format, string $modality, string $slug): array
    {
        $category = data_get($guide, 'category.name') ?: \Illuminate\Support\Str::headline($modality);
        return [
            'title' => $guide['title'] ?? 'Guide', 'h1' => $guide['title'] ?? 'Guide', 'format' => $format,
            'modality' => $modality, 'modality_label' => $category, 'slug' => $slug,
            'intro' => $guide['intro'] ?? ($guide['summary'] ?? ''), 'quick_answer' => $guide['quick_answer'] ?? ($guide['summary'] ?? ''),
            'sections' => (array) ($guide['sections'] ?? []), 'faqs' => (array) ($guide['faqs'] ?? []),
            'safety_note' => $guide['safety_note'] ?? 'Check suitability with the practitioner before booking.',
            'offerings' => [], 'nearby_links' => [], 'online_links' => [], 'related_guides' => [], 'practitioners' => [],
            'breadcrumbs' => [['label' => 'Guides', 'url' => url('/guides')], ['label' => $guide['title'] ?? 'Guide', 'url' => url('/'.$format.'/'.$modality.'/guides/'.$slug)]],
            'seo' => ['title' => $guide['seo_title'] ?: (($guide['title'] ?? 'Guide').' | We Offer Wellness®'), 'description' => $guide['seo_description'] ?: ($guide['summary'] ?? ''), 'canonical' => url('/'.$format.'/'.$modality.'/guides/'.$slug), 'robots' => 'index,follow'],
        ];
    }

    private function mergeRemoteHubGuides(array $page, array $guides, string $format, ?string $modality = null): array
    {
        $remote = collect($guides)
            ->filter(function (array $guide) use ($format, $modality): bool {
                if (($guide['format'] ?? '') !== $format) return false;
                return $modality === null || ($guide['modality'] ?? '') === $modality;
            })
            ->map(fn (array $guide): array => [
                'title' => $guide['title'] ?? 'Guide',
                'label' => $guide['title'] ?? 'Guide',
                'summary' => $guide['summary'] ?? '',
                'url' => url('/'.($guide['format'] ?? $format).'/'.($guide['modality'] ?? $modality).'/guides/'.($guide['slug'] ?? '')),
            ])
            ->filter(fn (array $guide): bool => $guide['url'] !== '')
            ->values();

        if ($remote->isEmpty()) return $page;

        $merge = fn (string $key, int $limit): array => $remote
            ->concat(collect($page[$key] ?? []))
            ->unique('url')
            ->take($limit)
            ->values()
            ->all();

        $page['popular_guides'] = $merge('popular_guides', 12);
        $page['what_is_guides'] = $merge('what_is_guides', 12);
        return $page;
    }
}
