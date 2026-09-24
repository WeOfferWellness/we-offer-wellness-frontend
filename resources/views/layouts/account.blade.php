@extends('layouts.base')

@section('html-lang', str_replace('_', '-', app()->getLocale()))

@section('document-head')
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('page-title', 'We Offer Wellness™')</title>
    @php
        $favicon = asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
    <link rel="icon" href="{{ $favicon }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="msapplication-TileImage" content="{{ asset('favicon-192x192.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="manifest" href="/manifest.json?v=5">
    <meta name="theme-color" content="#90b9a9">  

    @php $manifest = public_path('build/manifest.json'); @endphp
    @if (file_exists($manifest))
        @vite('resources/js/analytics.js')
    @endif

    <style>
        :root {
            --wow-green:#4A8878;
            --wow-green-hover:#3F7F71;
            --wow-green-press:#376F63;
            --bg:#ECEFF3;
            --card:#FFFFFF;
            --ink:#0B1220;
            --muted:rgba(11,18,32,.66);
            --muted2:rgba(11,18,32,.50);
            --border:rgba(16,24,40,.12);
            --border2:rgba(16,24,40,.10);
            --shadow: 0 24px 70px rgba(16,24,40,.14);
            --shadow2: 0 12px 28px rgba(16,24,40,.10);
            --radius-xl: 3px;
            --radius-lg: 3px;
            --radius-md: 3px;
            --font: 'Manrope', system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            --ui: 'Instrument Sans', system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            --focus: 0 0 0 4px rgba(74,136,120,.18);
            --max: 1120px;
            --pad: clamp(18px, 2.2vw, 32px);
        }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body.account-auth-body {
            margin: 0;
            font-family: var(--font);
            color: var(--ink);
            background: #fefefe;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 50px 26px;
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
        }
        body.account-auth-body::before {
            content: none;
            display: none;
        }
        .account-auth-window {
            width: min(var(--max), 100%);
            min-height: min(680px, calc(100vh - 52px));
            background: var(--card);
            border: 1px solid rgba(255,255,255,.65);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            isolation: isolate;
            margin: 0 auto;
        }
        .account-auth-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(40px, 3.4vw, 76px);
            gap: 16px;
        }
        @media (max-width: 980px) {
            .account-auth-window { grid-template-columns: 1fr; }
            .account-auth-right { border-left: 0; border-top: 1px solid rgba(16,24,40,.08); }
        }
        @media (max-width: 767.98px) {
            .account-auth-right { display: none !important; }
        }
        .brand-mark { margin-bottom: 6px; user-select: none; }
        .auth-title { margin: 0; font-size: clamp(26px, 2.4vw, 34px); letter-spacing: -.02em; line-height: 1.12; }
        .auth-sub { margin: -6px 0 6px; color: var(--muted); font-size: 13px; max-width: 46ch; line-height: 1.5; font-family: var(--ui); }
        .account-auth-form { display: flex; flex-direction: column; gap: 12px; max-width: 420px; }
        .account-auth-field-group { display:flex; flex-direction:column; gap:6px; }
        .account-auth-field { position: relative; border-radius: 3px; border: 1px solid rgba(16,24,40,.14); background: #fff; box-shadow: 0 10px 18px rgba(16,24,40,.06); transition: box-shadow .15s ease, transform .15s ease, border-color .15s ease; }
        .account-auth-field--note { flex-direction:column; align-items:flex-start; gap:6px; padding:20px; background: rgba(11,18,32,.03); border-style:dashed; }
        .account-auth-field--note .account-auth-label { margin-bottom:0; font-size:13px; text-transform:uppercase; letter-spacing:.18em; color:rgba(11,18,32,.55); }
        .account-auth-field--note p { margin:0; color:rgba(11,18,32,.82); font-size:14px; }
        .account-auth-field:focus-within { border-color: rgba(74,136,120,.35); box-shadow: var(--focus), 0 12px 22px rgba(16,24,40,.08); transform: translateY(-1px); }
        .account-auth-label { display:block; font-family: var(--ui); font-size: 12px; color: rgba(11,18,32,.62); margin: 0 0 6px; }
        .account-auth-input { width:100%; border:0; outline:0; background:transparent; padding: 12px 42px 12px 12px; font-family: var(--ui); font-size: 14px; border-radius:3px; color: var(--ink); }
        .account-auth-toggle { position:absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 30px; height: 30px; border-radius: 10px; border: 1px solid rgba(16,24,40,.10); background: rgba(255,255,255,.95); display:flex; align-items:center; justify-content:center; cursor:pointer; transition: transform .15s ease, background .15s ease; }
        .account-auth-toggle:hover { transform: translateY(-50%) scale(1.04); }
        .account-auth-toggle:active { transform: translateY(-50%) scale(.98); }
        .account-auth-row { display:flex; align-items:center; justify-content:space-between; gap: 12px; font-family: var(--ui); font-size: 13px; color: rgba(11,18,32,.78); margin-top: 2px; }
        .account-auth-row a { color: rgba(11,18,32,.78); text-decoration:none; }
        .account-auth-row a:hover { text-decoration:underline; }
        .account-auth-check { display:flex; align-items:center; gap:10px; user-select:none; }
        .account-auth-check input { width:18px; height:18px; accent-color: var(--wow-green); }
        .btn {
            width:100%;
            border: 1px solid rgba(16,24,40,.22);
            border-radius: 12px;
            padding: 12px 14px;
            font-family: var(--ui);
            font-size: 16px;
            font-weight: 400;
            cursor:pointer;
            transition: transform .15s ease, background .15s ease, box-shadow .15s ease, color .15s ease, border-color .15s ease;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap: 10px;
            min-height: 46px;
            user-select:none;
            background:#fff !important;
            color: rgba(11,18,32,.82);
            box-shadow: 0 10px 22px rgba(16,24,40,.08);
            text-decoration:none;
        }
        .btn:hover,
        .btn:focus {
            background:#f7f7f7 !important;
            color: rgba(11,18,32,.90);
            border-color: rgba(0,0,0,.18);
            transform: translateY(-1px);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn--primary {
            border-color: rgba(0,0,0,.10);
            color:#fff;
            background:#549483 !important;
        }
        .btn--primary:hover,
        .btn--primary:focus {
            background:#4a8575 !important;
            color:#fff;
        }
        .account-auth-btn { width:100%; border-radius:12px; font-weight:700; font-size:14px; }
        .account-auth-inline { margin-top: 10px; font-family: var(--ui); font-size: 13px; color: rgba(11,18,32,.72); }
        .account-auth-inline a { color: var(--wow-green); text-decoration:none; font-weight:700; }
        .account-auth-inline a:hover { text-decoration:underline; }
        .account-auth-fine { margin-top: 10px; font-family: var(--ui); font-size: 12px; color: rgba(11,18,32,.56); line-height:1.45; }
        .account-auth-alert { display:none; border-radius: 12px; border: 1px solid rgba(217,45,32,.20); background: rgba(217,45,32,.08); color: rgba(160,28,19,.95); padding: 10px 12px; font-family: var(--ui); font-size: 13px; line-height:1.35; max-width: 420px; }
        .account-auth-alert--success { border-color: rgba(16,185,129,.35); background: rgba(16,185,129,.12); color:#065f46; }
        .account-auth-alert.show { display:block; }
        .account-auth-btn.is-loading { pointer-events:none; opacity:.85; }
        .account-auth-btn .spinner { width: 16px; height: 16px; border-radius: 999px; border: 2px solid rgba(255,255,255,.45); border-top-color: rgba(255,255,255,1); display:none; animation: spin .75s linear infinite; }
        .account-auth-btn.is-loading .spinner { display:inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .account-auth-right { position:relative; border-left: 1px solid rgba(16,24,40,.08); background: linear-gradient(180deg, rgba(245,247,250,.92), rgba(245,247,250,.72)); padding: calc(var(--pad) + 18px) var(--pad) var(--pad); display:flex; flex-direction:column; justify-content:space-between; gap: 18px; overflow:hidden; }
        .account-auth-quote { max-width:46ch; margin-left:auto; text-align:left; font-family: var(--ui); }
        .account-auth-quote p { margin:0; color: rgba(11,18,32,.78); font-size: 13px; line-height:1.55; }
        .account-auth-quote b { display:block; margin-top: 10px; font-size: 12px; color: rgba(11,18,32,.70); }
        .account-auth-quote span { display:block; font-size: 12px; color: rgba(11,18,32,.52); margin-top: 2px; }
        .media { position:relative; border-radius: 18px; border: 1px solid rgba(16,24,40,.10); background: radial-gradient(900px 500px at 30% 10%, rgba(74,136,120,.16), transparent 60%), linear-gradient(180deg, #ffffff, rgba(255,255,255,.85)); box-shadow: var(--shadow2); overflow:hidden; aspect-ratio: 16/11; display:flex; align-items:center; justify-content:center; padding: 16px; }
        .media img { width:100%; height:100%; border-radius: 14px; object-fit: cover; display:block; box-shadow: inset 0 0 0 1px rgba(16,24,40,.06); }
        .review { position:absolute; left: 33px; bottom: 33px; width: min(360px, calc(100% - 36px)); border-radius: 16px; border: 1px solid rgba(16,24,40,.12); background: rgba(255,255,255,.86); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 18px 34px rgba(16,24,40,.12); padding: 12px; display:flex; flex-direction:column; gap: 8px; transform: translateZ(0); }
        .reviewTop { display:flex; align-items:flex-start; justify-content:space-between; gap: 10px; }
        .stars { display:flex; gap: 4px; color: rgba(11,18,32,.82); letter-spacing: .06em; font-size: 12px; font-family: var(--ui); }
        .pill { font-family: var(--ui); font-size: 11px; font-weight: 700; padding: 6px 8px; border-radius: 999px; background: rgba(74,136,120,.10); border: 1px solid rgba(74,136,120,.18); color: rgba(11,18,32,.72); white-space:nowrap; }
        .reviewTitle { font-family: var(--ui); font-weight: 800; font-size: 13px; margin: 0; letter-spacing: -.01em; color: rgba(11,18,32,.90); }
        .reviewText { margin: 0; font-family: var(--ui); font-size: 12.5px; line-height: 1.45; color: rgba(11,18,32,.72); }
        .reviewMeta { display:flex; align-items:center; justify-content:space-between; gap: 10px; font-family: var(--ui); font-size: 12px; color: rgba(11,18,32,.55); margin-top: 2px; }
        .fadeOut{ animation: fadeOut .22s ease forwards; }
        .fadeIn{ animation: fadeIn .28s ease forwards; }
        .review { position:absolute; left: 33px; bottom: 33px; width: min(360px, calc(100% - 36px)); border-radius: 16px; border: 1px solid rgba(16,24,40,.12); background: rgba(255,255,255,.86); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 18px 34px rgba(16,24,40,.12); padding: 12px; display:flex; flex-direction:column; gap: 8px; }
        .reviewTop { display:flex; align-items:flex-start; justify-content:space-between; gap: 10px; }
        .stars { display:flex; gap: 4px; color: rgba(11,18,32,.82); letter-spacing: .06em; font-size: 12px; font-family: var(--ui); }
        .pill { font-family: var(--ui); font-size: 11px; font-weight: 700; padding: 6px 8px; border-radius: 999px; background: rgba(74,136,120,.10); border: 1px solid rgba(74,136,120,.18); color: rgba(11,18,32,.72); white-space:nowrap; }
        .reviewTitle { font-family: var(--ui); font-weight: 800; font-size: 13px; margin: 0; letter-spacing: -.01em; color: rgba(11,18,32,.90); }
        .reviewText { margin: 0; font-family: var(--ui); font-size: 12.5px; line-height: 1.45; color: rgba(11,18,32,.72); }
        .reviewMeta { display:flex; align-items:center; justify-content:space-between; gap: 10px; font-family: var(--ui); font-size: 12px; color: rgba(11,18,32,.55); margin-top: 2px; }
        @media (prefers-reduced-motion: reduce){ *{ transition:none !important; animation:none !important; } }
        @keyframes fadeOut{ to{ opacity:0; transform: translateY(4px); } }
        @keyframes fadeIn{ from{ opacity:0; transform: translateY(4px);} to{ opacity:1; transform: translateY(0);} }
    </style>
    @stack('styles')
@endsection

@section('document-body-class')
account-auth-body
@endsection

@section('document-body')
<main class="account-auth-window">
    <section class="account-auth-left" aria-label="Authentication form">
        <div class="brand-mark" role="img" aria-label="We Offer Wellness">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1240.46 141.78" height="28" aria-hidden="true">
                <defs><style>.cls-1-header {fill:#599d91}.cls-2-header {fill:#000}</style></defs>
                <g><g>
                    <path class="cls-2-header " d="M483.94,63.07v57h-23.01v-56.97l-9.85.03c6.06,20.44.68,44.4-19.84,54.14-13.48,6.41-29.22,6.51-42.6-.12-14.21-7.04-21.27-21.46-21.22-37.09.04-15.11,6.2-29.32,19.58-36.92,21.57-12.27,53.7-6.68,63.84,19.24l.5-16.16,9.59-.25c-.06-9.84,1.82-19.92,9.35-26.56,8.55-7.54,20.13-8.84,31.69-5.75l-3.26,17.23c-4.69-1.25-9.36-1.92-12.18,1.8-2.66,3.51-2.84,8.57-2.6,13.35l23.92.03c-.5-15.95,6.15-31.21,22.9-34.29,6.32-1.16,12.4-.62,19.32.19l-.9,18.22c-5.2-.83-10.13-1.73-13.92,1.15-4.44,3.37-4.68,9.23-4.25,14.65l14.51.18.61,17.91c8.02-13.24,19.98-19.44,34.91-18.99,13.69.42,25.13,8.26,29.2,21.63,2.32,7.62,2.96,15.74,1.21,23.32l-47.37.06c2.16,17.94,28.17,14.65,41.19,11.13l3.05,15.32c-23.3,8.48-58.89,7.46-65.25-22.51-2.28-10.74-1.09-20.65,3.87-30.99l-15.99.03v56.98h-23v-56.99h-24ZM423.77,64.15c-2.54-5.71-7.68-8.95-13.3-8.86-5.3.08-10.43,2.89-13.03,8.24-4.67,9.63-4.76,21.35-.39,31.11,2.56,5.72,7.77,9.04,13.4,9.1,16.2.18,20.01-24.56,13.32-39.59ZM590.93,74.23c-.49-5.48-1.7-8.71-4.81-11.19-4.16-3.31-9.96-3.44-14.72-1.03-4,2.03-6.82,6.66-7.55,12.29l27.08-.07h0Z"></path>
                    <path class="cls-2-header " d="M277.89,39.19l-25.1,80.84-24.11.06c-4.85-17.24-9.08-33.79-13.07-51.45l-4.05,17.25-9.65,34.16h-24.17l-23.81-80.87,26.2.07,11.26,58.94,14.84-58.99,20.3-.19,14.28,57.08,11.95-57.05,25.12.15h.01Z"></path>
                    <polygon class="cls-2-header " points="804.89 39.19 779.8 120.03 755.69 120.09 742.62 68.64 738.57 85.89 728.92 120.05 704.75 120.05 680.94 39.17 707.14 39.25 718.4 98.18 733.24 39.19 753.54 39.01 767.82 96.09 779.78 39.04 804.89 39.19"></polygon>
                    <path class="cls-2-header " d="M297.33,90.11c1.01,17.52,27.78,14.9,40.9,11.12l3.06,15.33c-12.65,4.55-25.92,5.84-39.07,3.16-24.39-4.96-32.05-30.29-24.66-51.02,5.46-15.3,19.31-23.88,35.39-23.65,13.57.2,25.14,7.16,29.74,20.16,2.78,7.87,3.43,16.08,1.93,24.82l-47.29.08h0ZM323.93,74.23c-.49-5.48-1.7-8.71-4.81-11.19-4.16-3.31-9.96-3.44-14.72-1.03-4,2.03-6.82,6.66-7.55,12.29l27.08-.07Z"></path>
                    <path class="cls-2-header " d="M985.84,66.82c-3.99-3.94-9.55-3.65-13.96-1.49-3.06,1.5-6.85,6.15-6.87,10.77l-.11,43.97h-22.94l-.42-73.87,19.55-.18,1.68,10.36c10.12-12.3,27.92-15.52,40.45-5.56,6.05,4.82,9.42,13.4,9.47,21.25l.28,48h-23.02l-.04-42.08c0-3.67-1.36-8.5-4.07-11.17h0Z"></path>
                    <path class="cls-2-header " d="M823.33,90.11c1.01,17.52,27.78,14.9,40.9,11.12l3.06,15.34c-12.65,4.55-25.92,5.84-39.07,3.16-24.38-4.97-32.05-30.26-24.65-51.02,5.45-15.3,19.31-23.88,35.38-23.65,13.57.2,25.15,7.16,29.74,20.16,2.78,7.87,3.43,16.08,1.93,24.82l-47.29.08h0ZM849.93,74.23c-.49-5.48-1.7-8.71-4.81-11.19-4.16-3.31-9.96-3.44-14.72-1.03-4,2.03-6.82,6.66-7.55,12.29l27.08-.07h0Z"></path>
                    <g>
                        <path class="cls-2-header " d="M1131.76,117.42c-13.93,5.8-29.2,4.36-42.9-1.29l4.08-16.4c7.18,4.02,29.11,9.43,29.96-.17.21-2.4-1.77-5.18-4.17-6.13l-13.21-5.19c-5.74-2.26-11.52-6.86-13.63-12.78-4.08-11.44,2.69-23.03,13.82-27.47,11.74-4.69,24.95-3.65,36.3,1.38l-4.04,15.56c-6.42-2.98-23.07-7.25-24.96.92-.42,1.82,1.49,5.08,3.45,5.87l14.88,5.97c8.38,3.36,13.66,10.61,13.86,18.89.21,8.77-4.25,17.03-13.42,20.84h-.02Z"></path>
                        <path class="cls-2-header " d="M1040.33,90.11c1.01,17.52,27.78,14.9,40.9,11.12l3.06,15.34c-12.65,4.55-25.92,5.84-39.07,3.16-24.38-4.96-32.05-30.28-24.66-51.02,5.45-15.3,19.31-23.88,35.39-23.65,13.57.2,25.15,7.16,29.74,20.16,2.78,7.87,3.43,16.08,1.93,24.82l-47.29.08h0ZM1066.93,74.23c-.49-5.48-1.71-8.71-4.81-11.19-4.16-3.31-9.96-3.44-14.72-1.03-4,2.03-6.82,6.66-7.55,12.29l27.08-.07h0Z"></path>
                        <path class="cls-2-header " d="M1188.76,117.42c-13.93,5.8-29.2,4.36-42.9-1.29l4.08-16.4c7.18,4.02,29.11,9.43,29.96-.17.21-2.4-1.77-5.18-4.17-6.12l-13.21-5.19c-5.74-2.26-11.52-6.86-13.63-12.78-4.08-11.44,2.69-23.03,13.82-27.47,11.74-4.69,24.95-3.65,36.3,1.38l-4.05,15.56c-6.4-3-23.14-7.21-24.96.9-.43,1.91,1.45,5.08,3.45,5.88l14.87,5.97c8.38,3.36,13.66,10.6,13.86,18.89.21,8.77-4.25,17.02-13.42,20.84h0Z"></path></g>
                    <g><rect class="cls-2-header " x="908.93" y="12.07" width="23" height="108"></rect><rect class="cls-2-header " x="875.93" y="12.07" width="23" height="108"></rect></g>
                    <path class="cls-2-header " d="M640.9,120.03l-22.94.05-.39-73.97,19.45-.04,1.19,14.28c4.45-10.27,13.04-17.02,25.15-14.92l.08,21.31c-3.87,0-6.43-.04-9.57.19-7.27.53-12.84,6.88-12.86,14.11l-.11,39h0Z"></path>
                    <g><path class="cls-1-header " d="M78.77,44.93l-20.39,17.6-20.31-17.61c-5.11-4.43-9.26-9.29-14.08-15.15C27.9,22.42,51.39.46,58.1,0s29.62,21.65,34.78,29.36c-4.34,5.84-8.74,10.94-14.11,15.57h0Z"></path>
                        <path class="cls-1-header " d="M56.4,141.78l-17.26-8.91C19.9,122.93-2.69,102.04.26,77.69l13.99,11.01c20.48,16.11,42.29,21.02,42.14,53.08h.01Z"></path>
                        <path class="cls-1-header " d="M60.62,141.61c-.68-31.52,21.74-36.99,41.98-52.91l13.99-11c3.11,24.27-20.19,45.94-39.53,55.48-5.26,2.87-9.54,5.15-16.43,8.44h-.01Z"></path>
                        <path class="cls-1-header " d="M55.01,113.27c-11.82-10.06-23.53-18.79-36.4-26.93-6.2-3.92-11.4-8.35-17.11-13.24.89-7.19,2.08-13.73,5.56-19.48l35.32,29.93c7.73,7.71,13.81,17.2,12.63,29.71h0Z"></path>
                        <path class="cls-1-header " d="M95.85,87.93c-12.15,7.63-22.98,16.17-34.07,25.27-.99-12.69,5.13-22.39,13.22-30.11l35.02-29.61c3,6.16,4.79,12.74,5.19,19.56-6.08,5.67-12.4,10.51-19.36,14.88h0Z"></path>
                        <path class="cls-1-header " d="M61.84,90.91c.3-17.28-3.8-21.71,7.79-32.02l27.89-24.79c2.95,4.5,5.67,9.03,8.74,14.41-8.52,10.17-17.85,18.5-28.28,26.77-5.86,4.65-10.38,9.91-16.14,15.62h0Z"></path>
                        <path class="cls-1-header " d="M54.91,90.53c-5.71-5.3-10.39-10.83-16.58-15.7-10.12-7.97-18.85-16.06-27.65-26.02,2.66-5.25,5.52-10.06,8.71-14.7l27.84,24.7c3.47,3.08,6.29,6.96,8.24,11.07l-.55,20.65h0Z"></path></g>
                    <g class="cls-2-header "><path d="M1224.34,24.61s.42.03.6.04c2.15.15,4.25.69,6.2,1.63,2.69,1.3,4.98,3.3,6.62,5.82s2.56,5.31,2.67,8.26l.02.68c0,.16,0,.31,0,.47l-.04.88c-.27,6.38-4.56,12.13-10.45,14.46-1.82.72-3.72,1.1-5.67,1.18-.43.02-.82.02-1.25,0-2.08-.08-4.13-.52-6.06-1.33-5.72-2.42-9.67-7.81-10.14-14.12-.01-.16-.03-.29-.03-.44v-.14s-.03-.04-.03-.03v-1.38c0-.14.04-.32.05-.48.42-6.36,4.42-11.76,10.19-14.15,1.78-.74,3.65-1.16,5.56-1.29.22-.01.41,0,.6-.04h1.15ZM1236,44.14c.46-2.38.32-4.84-.4-7.15-.51-1.64-1.33-3.16-2.4-4.47-2.19-2.7-5.37-4.31-8.8-4.53s-6.96,1.06-9.49,3.59c-1.63,1.64-2.82,3.7-3.43,5.97-.68,2.54-.65,5.24.09,7.77,1.33,4.53,4.89,7.98,9.46,8.96.97.21,1.94.29,2.94.29,6.06,0,10.89-4.5,12.03-10.44Z"></path>
                        <path d="M1230.43,46.15c.27,1.29.54,2.53,1.13,3.75h-3.99c-.34-.4-.64-1.41-.77-1.93l-.53-2.18c-.08-.35-.16-.67-.31-.99-.29-.6-.77-1.03-1.39-1.26-.47-.16-.94-.25-1.44-.25h-2.04s0,6.6,0,6.6h-3.79s0-16.66,0-16.66l2.85-.35c1.53-.19,3.04-.2,4.57-.14,1.74.1,3.38.35,4.75,1.52,1.03.88,1.47,2.22,1.4,3.57-.1,1.95-1.67,3.17-3.37,3.72-.01.07-.01.15,0,.21,1.94.6,2.56,2.54,2.93,4.36ZM1226.77,38c-.06-1.34-.99-2.04-2.2-2.29-1.01-.2-2.4-.14-3.39.09v4.77s1.95,0,1.95,0c1.58,0,3.73-.54,3.64-2.56Z"></path></g>
                </g></g>
            </svg>
        </div>
        @hasSection('auth-heading')
            <h1 class="auth-title">@yield('auth-heading')</h1>
        @endif
        @hasSection('auth-subheading')
            <p class="auth-sub">@yield('auth-subheading')</p>
        @endif
        @yield('auth-alert')
        @yield('auth-form')
        @yield('auth-meta')
    </section>
    @php
        $authReviewFeed = $authReviews ?? [];
        $authReviewFallback = [
            'rating' => 5,
            'title' => 'Genuinely effortless',
            'text' => 'The filters actually helped. Booked an online session in under two minutes.',
            'name' => 'Hannah',
            'location' => 'Manchester',
            'when' => 'Today',
        ];
        if (empty($authReviewFeed)) {
            $authReviewFeed = [$authReviewFallback];
        }
        $authReviewInitial = $authReviewFeed[0] ?? $authReviewFallback;
    @endphp
    <aside class="account-auth-right" aria-label="Customer stories">
        <div class="account-auth-quote">
            <p>“The easiest way to book a proper reset — without the endless scrolling and decision fatigue.”</p>
            <b>Customers, basically</b>
            <span>Verified bookings • Real reviews</span>
        </div>
        <div class="media" aria-label="Preview image">
            <img src="https://media.licdn.com/dms/image/v2/D4E22AQFMCdRaAjB9TA/feedshare-shrink_2048_1536/feedshare-shrink_2048_1536/0/1722597045741?e=1773878400&amp;v=beta&amp;t=OUshI77Gj0hHFK2_ClKBYaLdQrBoSxpZbhTfqH-5oUE" alt="We Offer Wellness marketplace preview" loading="lazy">
            <div class="review fadeIn" id="authReviewCard" aria-live="polite">
                <div class="reviewTop">
                    <div class="stars" id="authReviewStars">★★★★★</div>
                    <div class="pill">Verified booking</div>
                </div>
                <p class="reviewTitle" id="authReviewTitle">{{ $authReviewInitial['title'] ?? 'Verified booking experience' }}</p>
                <p class="reviewText" id="authReviewText">{{ $authReviewInitial['text'] ?? 'Clean site, clear pricing, and the booking took about a minute.' }}</p>
                <div class="reviewMeta">
                    @php
                        $locationSuffix = !empty($authReviewInitial['location']) ? ' • '.$authReviewInitial['location'] : '';
                        $initialName = trim(($authReviewInitial['name'] ?? 'Verified customer').$locationSuffix);
                    @endphp
                    <span id="authReviewName">{{ $initialName }}</span>
                    <span id="authReviewWhen">{{ $authReviewInitial['when'] ?? 'Recently' }}</span>
                </div>
            </div>
        </div>
    </aside>
</main>
<script>
(function(){
  const card = document.getElementById('authReviewCard');
  if (!card) return;
  const starsEl = document.getElementById('authReviewStars');
  const titleEl = document.getElementById('authReviewTitle');
  const textEl = document.getElementById('authReviewText');
  const nameEl = document.getElementById('authReviewName');
  const whenEl = document.getElementById('authReviewWhen');
  const reviews = @json($authReviewFeed ?? []);
  if (!reviews.length) return;
  const starString = (n = 5) => Array.from({ length: 5 }).map((_, i) => (i < n ? '★' : '☆')).join('');
  const applyReview = (review) => {
    if (!review) return;
    const rating = Math.max(1, Math.min(5, parseInt(review.rating || 5, 10))); 
    starsEl.textContent = starString(rating);
    titleEl.textContent = review.title || 'Verified booking';
    textEl.textContent = review.text || '';
    const location = review.location ? ` • ${review.location}` : '';
    nameEl.textContent = `${review.name || 'Verified customer'}${location}`;
    whenEl.textContent = review.when || 'Recently';
  };
  if (reviews.length === 1) return;
  let index = 0;
  setInterval(() => {
    index = (index + 1) % reviews.length;
    card.classList.remove('fadeIn');
    card.classList.add('fadeOut');
    setTimeout(() => {
      applyReview(reviews[index]);
      card.classList.remove('fadeOut');
      card.classList.add('fadeIn');
    }, 220);
  }, 5200);
})();
</script>
@stack('scripts')
@endsection
