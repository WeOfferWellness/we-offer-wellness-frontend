@php
    $searchModalSource = file_get_contents(base_path('search-modal.html'));
    preg_match_all('/<style\b[^>]*>.*?<\/style>/is', $searchModalSource, $searchModalStyles);
    preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $searchModalSource, $searchModalBody);
    $searchModalMarkup = $searchModalBody[1] ?? '';
    $searchModalStylesMarkup = implode("\n", $searchModalStyles[0] ?? []);
    $searchModalMarkup = preg_replace(
        '/\s*<!-- DEMO TRIGGER:.*?<\/div>\s*(?=<!-- MAIN SEARCH MODAL)/is',
        '',
        $searchModalMarkup,
        1
    );
    $searchModalMarkup = preg_replace('/\s*<script\b[^>]*>.*?<\/script>\s*/is', '', $searchModalMarkup);
@endphp
{!! $searchModalStylesMarkup !!}
{!! $searchModalMarkup !!}
