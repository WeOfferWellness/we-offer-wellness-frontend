@include('partials.offering-tabs', [
    'id' => 'home-online-offering-tabs',
    'eyebrow' => 'Online support when you need it',
    'title' => 'Support From the comfort of your own home',
    'intro' => 'Find online therapies, classes and one-to-one sessions that fit around your day — whether you want quiet time alone or support you can share.',
    'localLabel' => 'Under £50',
    'newLabel' => '£50–£99',
    'onlineLabel' => '£100+',
    'localOfferings' => $onlineUnder50 ?? collect(),
    'newOfferings' => $onlineUnder100 ?? collect(),
    'onlineOfferings' => $online100Plus ?? collect(),
    'preferredLocation' => null,
    'infinite' => true,
    'tabPriceRanges' => [
        'local' => ['min' => 0, 'max' => 50],
        'new' => ['min' => 50, 'max' => 99],
        'online' => ['min' => 100, 'max' => 999999],
    ],
])
