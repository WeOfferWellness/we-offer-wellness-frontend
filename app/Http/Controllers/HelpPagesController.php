<?php

namespace App\Http\Controllers;

class HelpPagesController extends Controller
{
    public function faq()
    {
        return view('help.faq', [
            'seo' => [
                'title' => 'FAQ | We Offer Wellness®',
                'description' => 'Common booking, payment, account and provider questions for We Offer Wellness®.',
                'canonical' => url('/help/faq'),
            ],
        ]);
    }

    public function giftCards()
    {
        return app(GiftCardsController::class)->index();
    }
}
