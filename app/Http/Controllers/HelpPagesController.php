<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

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
        $body = <<<'HTML'
<p>WOW gift cards arrive instantly by email with your personalised message. Gift cards never expire and can be redeemed on therapies, classes, events and workshops.</p>
<ul>
  <li><strong>How to send:</strong> Choose an amount, enter the recipient’s name and email, and schedule delivery or send immediately.</li>
  <li><strong>How to redeem:</strong> Recipients enter the unique code at checkout. Balances can be used across multiple bookings.</li>
  <li><strong>Need a custom amount?</strong> <a href="/contact?topic=gifting">Contact the team</a> for bulk or corporate gifting.</li>
  <li><a href="/gift-cards">Browse gift cards</a></li>
  <li><a href="/gifts">See gifting ideas</a></li>
  <li><a href="/refunds-and-cancellations">Refunds & cancellations</a></li>
  <li><a href="/privacy">Privacy policy</a></li>
  <li><a href="/terms">Terms & conditions</a></li>
  <li><a href="/cookies">Cookies</a></li>
</ul>
HTML;
        return Inertia::render('General/Page', [
            'title' => 'Gift Card Help',
            'metaDescription' => 'How WOW gift vouchers work and how to redeem them.',
            'bodyHtml' => $body,
            'canonical' => url('/help/gift-cards'),
        ]);
    }
}
