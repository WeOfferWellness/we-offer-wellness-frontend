<?php

namespace App\Http\Controllers;

use App\Services\BackendPageLayoutClient;
use App\Services\ProfilePageResolver;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(BackendPageLayoutClient $pageLayoutClient)
    {
        $title = 'About We Offer Wellness®';
        $description = 'Meet the founders of We Offer Wellness®, learn our story, and discover how we connect people with trusted holistic therapies, classes, events, and practitioner tools across the UK.';
        $ogImage = asset('images/about-social-preview.jpg');

        $route = request()->route();
        $routeUri = trim((string) $route?->uri(), '/');
        $routeIdentity = $routeUri === '' ? '/' : '/'.$routeUri;
        $routeKey = sha1(
            $routeIdentity.'|'.ltrim((string) $route?->getActionName(), '\\').'|'.trim((string) $route?->getName())
        );
        $page = $pageLayoutClient->page('route-'.$routeKey, 'wow-marketplace');
        $sections = is_array($page['managed_sections'] ?? null)
            ? $page['managed_sections']
            : (array) ($page['sections'] ?? []);

        $viewData = [
            'title' => $title,
            'metaDescription' => $description,
            'canonical' => url('/about'),
            'aboutPageSections' => $sections,
            'seo' => [
                'title' => 'About We Offer Wellness® | Trusted Holistic Therapies & Wellness Tools',
                'description' => $description,
                'canonical' => url('/about'),
                'og_image' => $ogImage,
                'og_image_alt' => 'We Offer Wellness about page social preview image',
                'site_name' => 'We Offer Wellness®',
                'twitter_card' => 'summary_large_image',
            ],
        ];

        $componentPreviewId = trim(mb_substr((string) request()->query('component_preview', ''), 0, 160));
        if ($componentPreviewId !== '') {
            $viewData['componentPreviewId'] = $componentPreviewId;
            $viewData['componentPreviewMode'] = true;

            return response(view('about.index', $viewData)->render())
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return view('about.index', $viewData);
    }

    /**
     * Display a team member placeholder profile page.
     */
    public function team(string $slug, ProfilePageResolver $resolver)
    {
        $user = $resolver->resolve($slug, 'team');

        abort_if($user === null, 404);

        return view('providers.show', [
            'seo' => $resolver->buildSeo($user, 'team', url('/about/team/'.$slug)),
            'slug' => $slug,
            'profileType' => 'team',
            'user' => $user,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
