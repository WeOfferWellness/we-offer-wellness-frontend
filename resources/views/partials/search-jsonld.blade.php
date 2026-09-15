@php
  use Illuminate\Support\Str;

  $siteUrl = rtrim((string) config('app.url', url('/')), '/') . '/';
  $searchUrl = url()->full();
  $searchBaseUrl = url('/search');

  $queryWhat = trim((string) request('what', ''));
  $queryWhere = trim((string) request('where', ''));
  $queryWhen = trim((string) request('when', ''));

  $searchTitle = $queryWhat !== ''
      ? $queryWhat . ' Wellness Sessions'
      : 'Search Holistic Therapies and Wellness Sessions';
  if ($queryWhere !== '') {
      $searchTitle .= ' in ' . $queryWhere;
  }
  if ($queryWhen !== '') {
      $searchTitle .= ' available ' . $queryWhen;
  }
  $searchTitle .= ' | We Offer Wellness®';

  $description = 'Search trusted holistic therapies, wellness sessions, classes and online experiences';
  $description .= $queryWhere !== '' ? ' in ' . $queryWhere : ' across the UK';
  if ($queryWhen !== '') {
      $description .= ' available ' . $queryWhen;
  }
  $description .= '.';

  $sourceItems = [];
  if (is_object($products ?? null) && method_exists($products, 'items')) {
      $sourceItems = (array) $products->items();
  } elseif ($products instanceof \Illuminate\Support\Collection) {
      $sourceItems = $products->all();
  } elseif (is_array($products ?? null)) {
      $sourceItems = $products;
  }

  $normalizeMoney = function (mixed $value): ?float {
      if (!is_numeric($value)) {
          return null;
      }

      $amount = (float) $value;

      return round($amount, 2);
  };

  $cleanText = function (mixed $value, int $limit = 180): ?string {
      $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));

      if ($text === '') {
          return null;
      }

      return Str::limit($text, $limit, '…');
  };

  $buildProvider = function (mixed $item) use ($siteUrl) {
      $vendorName = trim((string) (
          data_get($item, 'vendor_name')
          ?: data_get($item, 'vendor.vendor_name')
          ?: data_get($item, 'vendor.user.name')
          ?: data_get($item, 'practitioner_name')
          ?: data_get($item, 'provider')
          ?: ''
      ));

      if ($vendorName === '') {
          return [
              '@type' => 'Organization',
              'name' => 'We Offer Wellness®',
              'url' => $siteUrl,
          ];
      }

      $providerType = data_get($item, 'vendor_name') || data_get($item, 'vendor.vendor_name')
          ? 'Organization'
          : 'Person';

      return [
          '@type' => $providerType,
          'name' => $vendorName,
      ];
  };

  $searchResults = [];
  foreach ($sourceItems as $index => $item) {
      $url = trim((string) data_get($item, 'url', ''));
      if ($url === '') {
          continue;
      }

      $title = trim((string) data_get($item, 'title', data_get($item, 'name', 'Wellness Session')));
      $title = $title !== '' ? $title : 'Wellness Session';

      $categoryName = trim((string) (data_get($item, 'category.name') ?: ''));
      if ($categoryName === '') {
          $categoryRaw = data_get($item, 'category');
          if (is_string($categoryRaw)) {
              $categoryName = trim($categoryRaw);
          }
      }

      $serviceType = trim((string) (
          $categoryName
          ?: data_get($item, 'type')
          ?: data_get($item, 'product_type')
          ?: 'Wellness Session'
      ));

      $itemLd = [
          '@type' => 'Service',
          '@id' => $url . '#service',
          'name' => $title,
          'serviceType' => $serviceType,
          'url' => $url,
          'description' => $cleanText(
              data_get($item, 'summary')
              ?: data_get($item, 'description')
              ?: data_get($item, 'what_to_expect')
              ?: data_get($item, 'body_html')
          ),
          'image' => trim((string) data_get($item, 'image', '')) ?: null,
          'provider' => $buildProvider($item),
          'areaServed' => [
              '@type' => 'Country',
              'name' => 'United Kingdom',
          ],
      ];

      $price = $normalizeMoney(data_get($item, 'price', data_get($item, 'variants_min_price', null)));
      $currency = strtoupper(trim((string) data_get($item, 'currency', 'GBP'))) ?: 'GBP';

      if ($price !== null && $price > 0) {
          $itemLd['offers'] = [
              '@type' => 'Offer',
              'url' => $url,
              'price' => $price,
              'priceCurrency' => $currency,
              'seller' => $buildProvider($item),
          ];
      }

      $rating = data_get($item, 'rating');
      $reviewCount = (int) data_get($item, 'review_count', data_get($item, 'reviews_count', 0));
      if (is_numeric($rating) && (float) $rating > 0 && $reviewCount > 0) {
          $itemLd['aggregateRating'] = [
              '@type' => 'AggregateRating',
              'ratingValue' => number_format((float) $rating, 1, '.', ''),
              'reviewCount' => $reviewCount,
              'bestRating' => 5,
              'worstRating' => 1,
          ];
      }

      $searchResults[] = [
          '@type' => 'ListItem',
          'position' => count($searchResults) + 1,
          'url' => $url,
          'name' => $title,
          'item' => array_filter($itemLd, static fn ($value) => $value !== null && $value !== ''),
      ];
  }

  $searchLd = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'CollectionPage',
              '@id' => $searchUrl . '#webpage',
              'url' => $searchUrl,
              'name' => $searchTitle,
              'description' => $description,
              'isPartOf' => [
                  '@id' => $siteUrl . '#website',
              ],
              'about' => [
                  '@id' => $siteUrl . '#organization',
              ],
              'mainEntity' => [
                  '@id' => $searchUrl . '#results',
              ],
          ],
          [
              '@type' => 'ItemList',
              '@id' => $searchUrl . '#results',
              'name' => $queryWhat !== '' ? $queryWhat . ' search results' : 'Wellness search results',
              'numberOfItems' => count($searchResults),
              'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
              'itemListElement' => $searchResults,
          ],
          [
          ],
      ],
  ];
@endphp

<script type="application/ld+json">{!! json_encode($searchLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
