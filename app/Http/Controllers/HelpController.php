<?php

namespace App\Http\Controllers;

class HelpController extends Controller
{
    public function index()
    {
        return app(HelpCentreController::class)->index();
    }

    public function faq()
    {
        return app(HelpPagesController::class)->faq();
    }

    public function giftCards()
    {
        return app(HelpPagesController::class)->giftCards();
    }
}
