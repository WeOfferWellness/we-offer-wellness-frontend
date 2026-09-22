<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        return app(LocationsController::class)->index($request, app(\App\Services\LocationDiscoveryService::class));
    }

    public function hierarchy(Request $request, string $country, ?string $county = null, ?string $town = null)
    {
        return app(LocationsController::class)->hierarchy($request, $country, $county, $town);
    }

    public function show(Request $request, string $slug)
    {
        return app(LocationsController::class)->show($request, $slug);
    }

    public function nearMe(Request $request)
    {
        return app(LocationsController::class)->nearMe($request);
    }

    public function cityCategory(Request $request, string $city, string $type, string $category)
    {
        return app(LocationsController::class)->cityCategory($request, $city, $type, $category);
    }

    public function online(Request $request)
    {
        return app(OnlineController::class)->index($request);
    }

    public function onlineModality(Request $request, string $modality)
    {
        return app(OnlineController::class)->show($request, $modality);
    }

    public function onlineNearMe(Request $request)
    {
        return app(OnlineNearMeController::class)->index($request);
    }

    public function categoryNearMe(Request $request, string $category, ?string $country = null, ?string $county = null, ?string $town = null)
    {
        return app(SeoMoneyPageController::class)->showNearMe($request, $category, $country, $county, $town);
    }

    public function modalityLocation(Request $request, string $format, string $modality, ?string $country = null, ?string $county = null, ?string $town = null)
    {
        return app(SeoMoneyPageController::class)->showStructuredNearMe($request, $format, $modality, $country, $county, $town);
    }
}
