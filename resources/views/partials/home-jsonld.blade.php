@php
  $siteUrl = rtrim((string) config('app.url', url('/')), '/') . '/';
  $studioUrl = 'https://studio.weofferwellness.co.uk/';
  $faviconUrl = url(config('app.favicon_url', '/favicon.png'));

  $categoryCatalog = app(\App\Services\WhatCategoryCacheService::class)->load();
  $homepageCategories = [];

  foreach ((array) data_get($categoryCatalog, 'categories', []) as $category) {
      $title = trim((string) data_get($category, 'title', ''));

      if ($title !== '') {
          $homepageCategories[] = $title;
      }
  }

  if ($homepageCategories === []) {
      $homepageCategories = [
          'Holistic Therapy',
          'Reiki',
          'Sound Healing',
          'Gong Baths',
          'Breathwork',
          'Yoga',
          'Meditation',
          'Massage Therapy',
          'Spiritual Wellbeing',
      ];
  }

  $knowsAbout = $homepageCategories;
  $offerCatalogItems = [];
  foreach ($homepageCategories as $category) {
      $offerName = $category . ' Offerings';

      $offerCatalogItems[] = [
          '@type' => 'Offer',
          'name' => $offerName,
          'itemOffered' => [
              '@type' => 'Service',
              'name' => $offerName,
              'serviceType' => $category,
          ],
      ];
  }

  $sameAs = [
      'https://studio.weofferwellness.co.uk/',
      'https://times.weofferwellness.co.uk/',
      'https://times.weofferwellness.co.uk/seeking-wellness',
      'https://uk.trustpilot.com/review/weofferwellness.co.uk',
      'https://www.facebook.com/p/We-Offer-Wellness-61551580484062/',
      'https://www.instagram.com/we_offer_wellness/',
      'https://uk.linkedin.com/company/we-offer-wellness-wow',
      'https://www.youtube.com/@WeOfferWellness',
      'https://www.eventbrite.co.uk/o/we-offer-wellness-88260749533',
      'https://open.spotify.com/show/5L8zM83I4zTtQVl0UvsUt7',
      'https://podcasts.apple.com/us/podcast/seeking-wellness-with-tash/id1804997207?uo=4',
      'https://www.youtube.com/@SeekingWellnesswithTash',
      'https://find-and-update.company-information.service.gov.uk/company/15323457',
  ];

  $organizationLd = [
      '@type' => 'Organization',
      '@id' => $siteUrl . '#organization',
      'name' => 'We Offer Wellness®',
      'alternateName' => 'WOW',
      'url' => $siteUrl,
      'logo' => [
          '@type' => 'ImageObject',
          'url' => $faviconUrl,
      ],
      'image' => [
          '@type' => 'ImageObject',
          'url' => url('/favicon-192x192.png'),
      ],
      'email' => 'hello@weofferwellness.co.uk',
      'telephone' => '+447958587756',
      'founder' => [
          ['@type' => 'Person', 'name' => 'Daniel Robert Harding'],
          ['@type' => 'Person', 'name' => 'Ian Snowball'],
          ['@type' => 'Person', 'name' => 'Natasha Tomlin'],
          ['@type' => 'Person', 'name' => 'Lorraine Prescott'],
      ],
      'address' => [
          '@type' => 'PostalAddress',
          'streetAddress' => '48 Salisbury Road, Penenden Heath',
          'addressLocality' => 'Maidstone',
          'addressRegion' => 'Kent',
          'postalCode' => 'ME14 2TX',
          'addressCountry' => 'GB',
      ],
      'sameAs' => $sameAs,
      'knowsAbout' => $knowsAbout,
  ];

  $softwareLd = [
      '@type' => 'SoftwareApplication',
      '@id' => 'https://studio.weofferwellness.co.uk/#software',
      'name' => 'WOW Studio',
      'url' => 'https://studio.weofferwellness.co.uk/',
      'applicationCategory' => 'BusinessApplication',
      'operatingSystem' => 'Web',
      'description' => 'WOW Studio is a practitioner platform for wellness professionals, including booking links, client management, encrypted messaging, @ease video calls, QR codes, short links and practitioner business tools.',
      'aggregateRating' => [
          '@type' => 'AggregateRating',
          'ratingValue' => 4.4,
          'reviewCount' => 21,
          'ratingCount' => 21,
          'bestRating' => 5,
          'worstRating' => 1,
      ],
      'featureList' => [
          'Public profile',
          'List up to 10 offerings',
          'Receive 100% of each sale',
          'Reviews visible',
          'Search boost',
          'Home page feature (rotation)',
          'Tech integrations and client management',
          'Unlimited enquiries',
          'Email support (72h response time)',
          'EasiForms (Typeform and Jotform alternative)',
          'Calendar integration and sync (Google, Outlook, iCal) with auto-availability',
          'Client management and business tools: invoicing, client portal, encrypted secure messaging, Zapier integration',
          'QR Code Generator with full data scan tracking',
          'Short Links (Bitly alternative) with price comparison links',
          'Peer-to-peer video conferencing up to 50 people (Zoom, Teams, Google Meet) with price comparison links',
      ],
      'offers' => [
          '@type' => 'Offer',
          'name' => 'WOW Studio Business Accelerator',
          'description' => '28-day free trial, then GBP 19.99 per month.',
          'url' => 'https://studio.weofferwellness.co.uk/',
          'price' => '19.99',
          'priceCurrency' => 'GBP',
          'availability' => 'https://schema.org/InStock',
          'seller' => [
              '@id' => $siteUrl . '#organization',
          ],
      ],
      'publisher' => [
          '@id' => $siteUrl . '#organization',
      ],
  ];

  $websiteLd = [
      '@type' => 'WebSite',
      '@id' => $siteUrl . '#website',
      'url' => $siteUrl,
      'name' => 'We Offer Wellness®',
      'alternateName' => 'WOW',
      'publisher' => [
          '@id' => $siteUrl . '#organization',
      ],
      'potentialAction' => [
          '@type' => 'SearchAction',
          'target' => [
              '@type' => 'EntryPoint',
              'urlTemplate' => $siteUrl . 'search?what={search_term_string}',
          ],
          'query-input' => 'required name=search_term_string',
      ],
  ];

  $webpageLd = [
      '@type' => 'WebPage',
      '@id' => $siteUrl . '#webpage',
      'url' => $siteUrl,
      'name' => 'We Offer Wellness® | Holistic Therapy That Works',
      'description' => 'Find trusted holistic therapies, online and in-person wellness sessions, wellness experiences, corporate wellness vouchers and practitioner tools across the United Kingdom.',
      'isPartOf' => [
          '@id' => $siteUrl . '#website',
      ],
      'about' => [
          '@id' => $siteUrl . '#organization',
      ],
      'primaryImageOfPage' => [
          '@type' => 'ImageObject',
          'url' => url('/favicon-192x192.png'),
      ],
  ];

  $businessLd = [
      '@type' => 'HealthAndBeautyBusiness',
      '@id' => $siteUrl . '#business',
      'name' => 'We Offer Wellness®',
      'url' => $siteUrl,
      'image' => url('/favicon-192x192.png'),
      'address' => [
          '@type' => 'PostalAddress',
          'streetAddress' => '48 Salisbury Road, Penenden Heath',
          'addressLocality' => 'Maidstone',
          'addressRegion' => 'Kent',
          'postalCode' => 'ME14 2TX',
          'addressCountry' => 'GB',
      ],
      'telephone' => '+447958587756',
      'sameAs' => $sameAs,
      'priceRange' => '££',
      'parentOrganization' => [
          '@id' => $siteUrl . '#organization',
      ],
      'areaServed' => [
          '@type' => 'Country',
          'name' => 'United Kingdom',
      ],
      'hasOfferCatalog' => [
          '@type' => 'OfferCatalog',
          'name' => 'We Offer Wellness Offerings',
          'itemListElement' => $offerCatalogItems,
      ],
  ];

  $homepageGraphLd = [
      '@context' => 'https://schema.org',
      '@graph' => [
          $organizationLd,
          $softwareLd,
          $websiteLd,
          $webpageLd,
          $businessLd,
      ],
  ];
@endphp

<script type="application/ld+json">
{!! json_encode($homepageGraphLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
</script>
