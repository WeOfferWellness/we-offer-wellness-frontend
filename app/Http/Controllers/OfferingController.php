<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OfferingController extends Controller
{
    public function indexLegacy(Request $request)
    {
        return app(StoreProductsController::class)->index($request);
    }

    public function showLegacy(Request $request, string $slug)
    {
        return app(StoreProductsController::class)->legacyShow($slug);
    }

    public function showLegacyByCategory(Request $request, string $category, string $slug)
    {
        return app(StoreProductsController::class)->show($request, $category, $slug);
    }

    public function storeReview(Request $request, string $category, string $slug)
    {
        return app(StoreProductsController::class)->storeReview($request, $category, $slug);
    }

    public function showEvent(Request $request, string $slug)
    {
        return app(EventsController::class)->show($request, $slug);
    }

    public function show(Request $request, string $format, string $modality, string $offering)
    {
        return app(LandingController::class)->offering($request, $format, $modality, $offering);
    }

    public function showAtLocation(Request $request, string $format, string $modality, string $country, string $county, string $town, string $offering)
    {
        return app(LandingController::class)->offeringLocation($request, $format, $modality, $country, $county, $town, $offering);
    }

    public function showOnline(Request $request, string $modality, string $offering)
    {
        return app(LandingController::class)->onlineOffering($request, $modality, $offering);
    }
}
