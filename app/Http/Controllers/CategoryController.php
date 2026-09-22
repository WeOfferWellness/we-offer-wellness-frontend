<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function therapy(Request $request, string $slug)
    {
        return app(TherapiesController::class)->show($request, $slug);
    }

    public function hub(Request $request, string $category)
    {
        return app(LandingController::class)->categoryHub($request, $category);
    }

    public function show(Request $request, string $format, string $modality)
    {
        return app(LandingController::class)->formatModality($request, $format, $modality);
    }
}
