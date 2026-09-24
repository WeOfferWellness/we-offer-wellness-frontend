<!DOCTYPE html>
<html lang="@yield('html-lang', 'en')">
<head>
    @include('partials.analytics.ga4-head')
    @yield('document-head')
</head>
<body class="@yield('document-body-class')">
    @yield('document-body')
    @include('partials.analytics-bridge')
</body>
</html>
