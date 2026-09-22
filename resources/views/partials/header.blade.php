<style>
    :root {
        --wow-header-offset: 0px;
    }
    #wow-header-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        background-color: #fff;
    }
    #wow-header-offset-spacer {
        height: var(--wow-header-offset, 0px);
    }
    button.md\:hidden.inline-flex.items-center.justify-center.p-2.rounded-md.text-ink-700.hover\:bg-ink-100 {
        border-radius: 40px !important;
    }
    .mobile-nav-text-trigger{
        border:1px solid rgba(15,23,42,.12);
        background:#fff;
        color:#0f172a;
        border-radius:9999px;
        min-height:40px;
        padding:0 16px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        font-size:14px;
        font-weight:700;
        letter-spacing:.01em;
        line-height:1;
        box-shadow:0 8px 18px rgba(15,23,42,.08);
        transition:background-color .18s ease, border-color .18s ease, color .18s ease, transform .18s ease, box-shadow .18s ease;
    }
    .mobile-nav-text-trigger:hover,
    .mobile-nav-text-trigger:focus,
    .mobile-nav-text-trigger:active{
        background:#f8fafc;
        box-shadow:0 10px 22px rgba(15,23,42,.12);
    }
    .mobile-nav-text-trigger:focus-visible{
        outline:2px solid currentColor;
        outline-offset:3px;
    }
    .mobile-search-trigger.is-open{
        background:#0f172a;
        border-color:#0f172a;
        color:#fff;
    }
    .mobile-nav-text-trigger__label{
        position:relative;
        z-index:1;
        pointer-events:none;
        white-space:nowrap;
    }
    /* hover state inherit existing bg hover */
    .utility-links__secondary .our-vibe-link{ border:1px solid transparent; background:transparent; padding:4px 12px; border-radius:4px; transition:background .2s ease, border-color .2s ease, box-shadow .2s ease; text-decoration:none; display:inline-flex; align-items:center; }
    .utility-links__secondary .our-vibe-link__logo{ display:block; width:auto; height:22px; max-width:96px; object-fit:contain; }
    .utility-links__secondary .our-vibe-link:hover{ background:#105b4b; border-color:#105b4b; color:#fff; box-shadow:0 10px 25px rgba(16,91,75,.2); }
    .utility-links__secondary .our-vibe-link:focus-visible{ outline:2px solid #105b4b; outline-offset:2px; }
    .utility-links__secondary .wow-practitioner-trigger{ border:none; background:rgba(16,91,75,.05); padding:6px 16px; border-radius:4px; font-weight:600; color:#0b1320; cursor:pointer; transition:background .2s ease, box-shadow .2s ease, color .2s ease; text-decoration:none; display:inline-flex; align-items:center; }
    .utility-links__secondary .wow-practitioner-trigger:hover{ background:#105b4b; color:#fff; box-shadow:0 10px 25px rgba(16,91,75,.25); }
    .utility-links__secondary .wow-practitioner-trigger:focus-visible{ outline:2px solid #105b4b; outline-offset:2px; }
    .wow-desktop-only{ display:none !important; }
    .wow-desktop-utility{ display:none !important; }
    .wow-mobile-tablet-only{ display:flex !important; }
    @media (min-width:1280px){
        .wow-desktop-utility{ display:block !important; }
        .wow-desktop-only{ display:flex !important; }
        .wow-mobile-tablet-only{ display:none !important; }
    }
    .practitioner-modal{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; padding:20px; z-index:1300; }
    .practitioner-modal::backdrop{ background:rgba(11,19,32,.72); }
    .practitioner-modal.is-visible{ display:flex; }
    .practitioner-modal[aria-hidden="true"]{ pointer-events:none; }
    .practitioner-modal__backdrop{ position:absolute; inset:0; background:rgba(11,19,32,.72); backdrop-filter:blur(6px); }
    .practitioner-modal__panel{ position:relative; background:#fff; border-radius:3px; width:min(560px, 100%); max-height:96vh; overflow-y:auto; padding:32px; box-shadow:0 24px 70px rgba(11,19,32,.25); animation:practitionerModalFade .25s ease; }
    @keyframes practitionerModalFade{ from{ opacity:0; transform:translateY(12px); } to{ opacity:1; transform:translateY(0); } }
    .practitioner-modal__close{ position:absolute; top:16px; right:16px; border:none; background:#f1f5f9; width:36px; height:36px; border-radius:50%; font-size:20px; color:#0f172a; cursor:pointer; display:flex; align-items:center; justify-content:center; }
    .practitioner-modal__close:hover{ background:#e2e8f0; }
    .practitioner-modal__eyebrow{ font-size:12px; text-transform:uppercase; letter-spacing:.2em; color:#0f766e; font-weight:700; margin-bottom:8px; }
    .practitioner-modal__subtitle{ color:var(--ink-600); margin-top:8px; }
    .practitioner-form{ display:flex; flex-direction:column; gap:18px; margin-top:20px; }
    .practitioner-form .field-row{ display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:16px; }
    .practitioner-form .field-group{ display:flex; flex-direction:column; }
    .practitioner-form label{ font-size:14px; font-weight:600; color:var(--ink-700); margin-bottom:6px; display:block; }
    .practitioner-form input[type="text"],
    .practitioner-form input[type="email"]{ width:100%; border:1px solid #d7dee7; border-radius:14px; padding:11px 14px; font-size:16px; transition:border-color .2s ease, box-shadow .2s ease; }
    .practitioner-form input:focus{ outline:none; border-color:#0f766e; box-shadow:0 0 0 3px rgba(15,118,110,.15); }
    .practice-mode{ display:flex; flex-wrap:wrap; gap:12px; }
    .practice-mode__option{ display:flex; align-items:center; gap:10px; border:1px solid transparent; border-radius:18px; padding:10px 16px; background:#f8fafc; font-weight:600; color:var(--ink-700); cursor:pointer; transition:all .2s ease; }
    .practice-mode__option input{ appearance:none; width:16px; height:16px; border:2px solid #0f766e; border-radius:4px; display:inline-block; position:relative; margin:0; flex-shrink:0; }
    .practice-mode__option span{ line-height:1; display:inline-block; }
    .practice-mode__option input:checked{ background:#0f766e; }
    .practice-mode__option input:checked::after{ content:""; position:absolute; inset:3px; background:#fff; border-radius:1px; }
    .practice-mode__option.is-active{ background:rgba(15,118,110,.1); border-color:#0f766e; color:#0b1320; }
    .practice-mode__legend{ font-weight:700; color:var(--ink-800); margin-bottom:8px; }
    .practitioner-form__hint{ font-size:13px; color:var(--ink-500); margin-top:4px; }
    .practitioner-form__message{ border-radius:14px; padding:12px 14px; font-weight:600; font-size:14px; display:none; }
    .practitioner-form__message.is-visible{ display:block; }
    .practitioner-form__message.is-success{ background:#ecfdf5; color:#047857; }
    .practitioner-form__message.is-error{ background:#fef2f2; color:#b91c1c; }
    .practitioner-form__submit{ width:100%; display:inline-flex; justify-content:center; }
    .practitioner-modal__panel h2{ font-size:28px; margin:0; color:var(--ink-900); }
    .practitioner-modal__panel p{ margin:0; }
    .mobile-search-drawer{
        position:fixed;
        inset:0;
        top:var(--wow-header-offset, 0px);
        z-index:1200;
        display:none;
        align-items:flex-start;
        justify-content:center;
        padding:16px;
    }
    .mobile-search-drawer.is-visible{ display:flex; }
    .mobile-search-drawer__backdrop{
        position:absolute;
        inset:0;
        background:rgba(11,19,32,.62);
        backdrop-filter:blur(10px);
        -webkit-backdrop-filter:blur(10px);
    }
    .mobile-search-drawer__inner{
        position:relative;
        width:min(720px, 100%);
        max-height:calc(100dvh - var(--wow-header-offset, 0px) - 32px);
        overflow:auto;
        margin-top:8px;
        z-index:1;
    }
    .mobile-search-drawer__panel{
        position:relative;
        background:none;
        border-radius:28px;
        box-shadow:0 28px 80px rgba(11,19,32,.24);
        padding:0;
        overflow:hidden;
    }
    .mobile-search-drawer__header{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
        padding:4px 4px 14px;
    }
    .mobile-search-drawer__eyebrow{
        margin:0 0 6px;
        color:#0f766e;
        font-size:12px;
        font-weight:800;
        letter-spacing:.16em;
        text-transform:uppercase;
    }
    .mobile-search-drawer__title{
        margin:0;
        color:#0f172a;
        font-family:inherit;
        font-size:22px;
        font-weight:850;
        line-height:1.05;
        letter-spacing:-.04em;
    }
    .mobile-search-drawer__subtitle{
        margin:8px 0 0;
        color:var(--ink-600);
        font-size:14px;
        line-height:1.45;
    }
    .mobile-search-drawer__search{
        width:100%;
    }
    .mobile-search-drawer__close{
        width:40px;
        height:40px;
        border:none;
        border-radius:9999px;
        background:#f1f5f9;
        color:#111827;
        display:flex;
        align-items:center;
        justify-content:center;
        box-shadow:0 8px 18px rgba(0,0,0,.08);
        flex:0 0 auto;
    }
    .mobile-search-drawer__close:hover{ background:#e2e8f0; }
    .mobile-search-trigger{
        position:relative;
        width:40px;
        height:40px;
        border-radius:9999px;
        transition:background-color .18s ease, transform .18s ease, box-shadow .18s ease;
    }
    .mobile-search-trigger__icon{
        position:absolute;
        inset:0;
        display:flex;
        align-items:center;
        justify-content:center;
        transition:opacity .18s ease, transform .18s ease;
    }
    .mobile-search-trigger__icon--search svg{
        width:24px;
        height:24px;
        color:#0f172a;
    }
    .mobile-search-trigger__icon--close{
        opacity:0;
        transform:scale(.82);
    }
    .mobile-search-trigger__icon--close svg{
        width:24px;
        height:24px;
        color:#fff;
    }
    .mobile-search-trigger.is-open{
        background:#dc2626;
    }
    .mobile-search-trigger.is-open .mobile-search-trigger__icon--search{
        opacity:0;
        transform:scale(.82);
    }
    .mobile-search-trigger.is-open .mobile-search-trigger__icon--close{
        opacity:1;
    }
    [data-mobile-search-trigger]{
        display:none !important;
    }
    .mobile-menu-trigger{
        width:40px;
        height:40px;
        min-height:40px;
        padding:0;
        border-color:rgba(15,23,42,.12);
        background:#fff;
        color:#0f172a;
    }
    .mobile-menu-trigger:hover,
    .mobile-menu-trigger:focus,
    .mobile-menu-trigger:active{
        border-color:rgba(15,23,42,.12);
        background:#fff;
        color:#0f172a;
        box-shadow:0 10px 22px rgba(15,23,42,.12);
    }
    .mobile-menu-trigger.is-open{
        border-color:#dc2626;
        background:#dc2626;
        color:#fff;
        box-shadow:0 10px 22px rgba(220,38,38,.22);
    }
    .mobile-menu-trigger svg{
        display:block;
        width:24px;
        height:24px;
        color:currentColor;
    }
    .mobile-menu-trigger.is-open svg{
        color:#fff;
    }
    .mobile-menu-trigger.is-open svg path{
        stroke:#fff !important;
    }
    .mobile-menu-trigger__icon{
        display:flex;
        align-items:center;
        justify-content:center;
        pointer-events:none;
    }
    .mobile-menu-trigger__icon--open{
        display:none;
    }
    .mobile-menu-trigger.is-open .mobile-menu-trigger__icon--closed{
        display:none;
    }
    .mobile-menu-trigger.is-open .mobile-menu-trigger__icon--open{
        display:flex;
    }
    @media (max-width: 480px){
        .practice-mode{ flex-direction:column; }
        .practitioner-form .field-row{ grid-template-columns:1fr; }
        .mobile-search-drawer{ padding:10px; }
        .mobile-search-drawer__inner{
            margin-top:0;
            width:100%;
            max-height:calc(100dvh - var(--wow-header-offset, 0px) - 20px);
        }
        .mobile-search-drawer__panel{ border-radius:22px; padding:12px; }
        .mobile-search-drawer__header{ padding:2px 2px 12px; }
        .mobile-search-drawer__title{ font-size:20px; }
    }
    @media (max-width: 1279.98px){
        .wow-mobile-logo-group{ margin-left:48px; }
        .wow-mobile-menu-anchor{
            position:absolute;
            left:12px;
            top:50%;
            transform:translateY(-50%);
        }
        .mobile-nav-text-trigger,
        .mobile-nav-text-trigger:hover,
        .mobile-nav-text-trigger:focus,
        .mobile-nav-text-trigger:active,
        .mobile-search-trigger.is-open,
        .mobile-menu-trigger,
        .mobile-menu-trigger:hover,
        .mobile-menu-trigger:focus,
        .mobile-menu-trigger:active,
        .mobile-menu-trigger.is-open{
            width:40px;
            min-width:40px;
            height:40px;
            min-height:40px;
            padding:0;
            border:0;
            background:transparent;
            box-shadow:none;
            color:#0f172a;
        }
        .mobile-menu-trigger.is-open svg,
        .mobile-menu-trigger.is-open svg path{ color:#0f172a; stroke:#0f172a !important; }
        .wow-brand-mark{ display:inline-flex !important; align-items:center; }
        .wow-brand-mark svg{ height:28px; width:auto; max-width:210px; }
    }
</style>
<style>
  /* When header becomes fixed after hitting top on desktop */

  /* (Removed hover animation for header mega state per request) */
</style>
@php
    $headerUser = auth()->user();
    $headerProfile = null;
    if ($headerUser) {
        $headerRawName = trim((string) $headerUser?->name);
        $headerFirst = trim($headerUser?->first_name ?: \Illuminate\Support\Str::of($headerRawName)->before(' '));
        $headerDerivedLast = '';
        if ($headerRawName && str_contains($headerRawName, ' ')) {
            $headerDerivedLast = trim(\Illuminate\Support\Str::of($headerRawName)->after(' '));
        }
        $headerLast = trim($headerUser?->last_name ?: $headerDerivedLast);
        $headerFullName = trim($headerFirst.' '.$headerLast) ?: ($headerRawName ?: 'Customer');
        $headerInitials = mb_strtoupper(mb_substr($headerFirst ?: $headerFullName, 0, 1).mb_substr($headerLast ?: '', 0, 1));
        $headerInitials = trim($headerInitials) !== '' ? $headerInitials : 'YOU';
        $headerProfile = [
            'full_name' => $headerFullName,
            'initials' => $headerInitials,
            'email' => $headerUser?->email,
        ];
    }

    $eventsMenuState = $eventsMenuState ?? ['visible' => true, 'links' => []];
    $eventsMenuLinks = array_merge([
        'events' => true,
        'workshops' => true,
        'classes' => false,
        'online' => false,
        'near_me' => false,
        'this_week' => false,
        'this_month' => false,
    ], $eventsMenuState['links'] ?? []);
    $eventsMenuVisible = $eventsMenuState['visible'] ?? true;

    $therapyCategoryLinks = [
        ['href' => '/therapies/reiki', 'label' => 'Reiki'],
        ['href' => '/therapies/sound-healing', 'label' => 'Sound healing'],
        ['href' => '/therapies/breathwork', 'label' => 'Breathwork'],
        ['href' => '/therapies/massage', 'label' => 'Massage'],
        ['href' => '/therapies/reflexology', 'label' => 'Reflexology'],
        ['href' => '/therapies/meditation', 'label' => 'Meditation'],
    ];

    $therapyFormatLinks = [
        ['href' => '/therapies', 'label' => 'All therapies'],
        ['href' => '/classes', 'label' => 'Classes'],
        ['href' => '/events', 'label' => 'Events'],
        ['href' => '/workshops', 'label' => 'Workshops'],
        ['href' => '/retreats', 'label' => 'Retreats'],
        ['href' => '/gifts', 'label' => 'Gift cards'],
    ];

    $localLinks = [
        ['href' => '/locations', 'label' => 'Locations'],
        ['href' => '/online', 'label' => 'Online sessions'],
        ['href' => '/online-near-me', 'label' => 'Find near you'],
    ];
@endphp
<!-- Overlay shown behind header mega menu -->
<div id="mega-overlay" class="mega-overlay" style="display:none"></div>
<div class="pointer-events-none fixed inset-0 -z-10"></div>
<header id="wow-header-container">
    <div class="utility-bar wow-desktop-utility">
        <div class="container-page">
            <div class="utility-links">
                <div class="utility-links__primary"><a href="/reset" style="display:none">Free 7-Day Reset</a><a href="/about">About We
                    Offer Wellness®</a><a href="/help" style="display:none;">Help Centre</a><a href="/safety-and-contraindications">Safety
                    &amp; Contraindications</a></div>
                <div class="utility-links__secondary"><a href="/for-business" style="display:none">For Business</a><a class="our-vibe-link" href="https://ourvibe.weofferwellness.co.uk" target="_blank" rel="noopener"><img class="our-vibe-link__logo" src="https://studio.weofferwellness.co.uk/storage/uploads/images/87378301-ba9a-444d-b948-328c9e6046bc.png" alt="OUR VIBE"></a><a class="wow-practitioner-trigger" href="https://studio.weofferwellness.co.uk/">Become a WOW Practitoner</a></div>
            </div>
        </div>
    </div>
    <div id="header-sentinel" style="position:relative;height:1px;width:1px"></div>
    <nav class="top-0 z-50 bg-white/90 backdrop-blur border-b"
            style="border-bottom: 1px solid rgba(153, 153, 153, 0.4); margin-top: -1px;">
        <div class="container container-page header-inner h-16 flex items-center justify-between position-relative">
            <div class="flex items-center gap-4 wow-mobile-logo-group"><a class="flex items-center gap-2 shrink-0" href="/" aria-label="We Offer Wellness">
                <!-- Inline SVG logo -->
                <span class="wow-brand-mark block" style="height:28px; display:inline-flex; align-items:center">
                <!-- BEGIN: WOW Logo -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1240.46 141.78" height="28" aria-hidden="true">
                      <defs><style>.cls-1-header {fill:#599d91}.cls-2-header {fill:#000}</style></defs>
                      <g><g>
                      <path class="cls-2-header " d="M483.94,63.07v57h-23.01v-56.97l-9.85.03c6.06,20.44.68,44.4-19.84,54.14-13.48,6.41-29.22,6.51-42.6-.12-14.21-7.04-21.27-21.46-21.22-37.09.04-15.11,6.2-29.32,19.58-36.92,21.57-12.27,53.7-6.68,63.84,19.24l.5-16.16,9.59-.25c-.06-9.84,1.82-19.92,9.35-26.56,8.55-7.54,20.13-8.84,31.69-5.75l-3.26,17.23c-4.69-1.25-9.36-1.92-12.18,1.8-2.66,3.51-2.84,8.57-2.6,13.35l23.92.03c-.5-15.95,6.15-31.21,22.9-34.29,6.32-1.16,12.4-.62,19.32.19l-.9,18.22c-5.2-.83-10.13-1.73-13.92,1.15-4.44,3.37-4.68,9.23-4.25,14.65l14.51.18.61,17.91c8.02-13.24,19.98-19.44,34.91-18.99,13.69.42,25.13,8.26,29.2,21.63,2.32,7.62,2.96,15.74,1.21,23.32l-47.37.06c2.16,17.94,28.17,14.65,41.19,11.13l3.05,15.32c-23.3,8.48-58.89,7.46-65.25-22.51-2.28-10.74-1.09-20.65,3.87-30.99l-15.99.03v56.98h-23v-56.99h-24ZM423.77,64.15c-2.54-5.71-7.68-8.95-13.3-8.86-5.3.08-10.43,2.89-13.03,8.24-4.67,9.63-4.76,21.35-.39,31.11,2.56,5.72,7.77,9.04,13.4,9.1,16.2.18,20.01-24.56,13.32-39.59ZM590.93,74.23c-.49-5.48-1.7-8.71-4.81-11.19-4.16-3.31-9.96-3.44-14.72-1.03-4,2.03-6.82,6.66-7.55,12.29l27.08-.07h0Z"/>
                      <path class="cls-2-header " d="M277.89,39.19l-25.1,80.84-24.11.06c-4.85-17.24-9.08-33.79-13.07-51.45l-4.05,17.25-9.65,34.16h-24.17l-23.81-80.87,26.2.07,11.26,58.94,14.84-58.99,20.3-.19,14.28,57.08,11.95-57.05,25.12.15h.01Z"/>
                      <polygon class="cls-2-header " points="804.89 39.19 779.8 120.03 755.69 120.09 742.62 68.64 738.57 85.89 728.92 120.05 704.75 120.05 680.94 39.17 707.14 39.25 718.4 98.18 733.24 39.19 753.54 39.01 767.82 96.09 779.78 39.04 804.89 39.19"/>
                      <path class="cls-2-header " d="M297.33,90.11c1.01,17.52,27.78,14.9,40.9,11.12l3.06,15.33c-12.65,4.55-25.92,5.84-39.07,3.16-24.39-4.96-32.05-30.29-24.66-51.02,5.46-15.3,19.31-23.88,35.39-23.65,13.57.2,25.14,7.16,29.74,20.16,2.78,7.87,3.43,16.08,1.93,24.82l-47.29.08h0ZM323.93,74.23c-.49-5.48-1.7-8.71-4.81-11.19-4.16-3.31-9.96-3.44-14.72-1.03-4,2.03-6.82,6.66-7.55,12.29l27.08-.07Z"/>
                      <path class="cls-2-header " d="M985.84,66.82c-3.99-3.94-9.55-3.65-13.96-1.49-3.06,1.5-6.85,6.15-6.87,10.77l-.11,43.97h-22.94l-.42-73.87,19.55-.18,1.68,10.36c10.12-12.3,27.92-15.52,40.45-5.56,6.05,4.82,9.42,13.4,9.47,21.25l.28,48h-23.02l-.04-42.08c0-3.67-1.36-8.5-4.07-11.17h0Z"/>
                      <path class="cls-2-header " d="M823.33,90.11c1.01,17.52,27.78,14.9,40.9,11.12l3.06,15.34c-12.65,4.55-25.92,5.84-39.07,3.16-24.38-4.97-32.05-30.26-24.65-51.02,5.45-15.3,19.31-23.88,35.38-23.65,13.57.2,25.15,7.16,29.74,20.16,2.78,7.87,3.43,16.08,1.93,24.82l-47.29.08h0ZM849.93,74.23c-.49-5.48-1.7-8.71-4.81-11.19-4.16-3.31-9.96-3.44-14.72-1.03-4,2.03-6.82,6.66-7.55,12.29l27.08-.07h0Z"/>
                      <g>
                      <path class="cls-2-header " d="M1131.76,117.42c-13.93,5.8-29.2,4.36-42.9-1.29l4.08-16.4c7.18,4.02,29.11,9.43,29.96-.17.21-2.4-1.77-5.18-4.17-6.13l-13.21-5.19c-5.74-2.26-11.52-6.86-13.63-12.78-4.08-11.44,2.69-23.03,13.82-27.47,11.74-4.69,24.95-3.65,36.3,1.38l-4.04,15.56c-6.42-2.98-23.07-7.25-24.96.92-.42,1.82,1.49,5.08,3.45,5.87l14.88,5.97c8.38,3.36,13.66,10.61,13.86,18.89.21,8.77-4.25,17.03-13.42,20.84h-.02Z"/>
                      <path class="cls-2-header " d="M1040.33,90.11c1.01,17.52,27.78,14.9,40.9,11.12l3.06,15.34c-12.65,4.55-25.92,5.84-39.07,3.16-24.38-4.96-32.05-30.28-24.66-51.02,5.45-15.3,19.31-23.88,35.39-23.65,13.57.2,25.15,7.16,29.74,20.16,2.78,7.87,3.43,16.08,1.93,24.82l-47.29.08h0ZM1066.93,74.23c-.49-5.48-1.71-8.71-4.81-11.19-4.16-3.31-9.96-3.44-14.72-1.03-4,2.03-6.82,6.66-7.55,12.29l27.08-.07h0Z"/>
                      <path class="cls-2-header " d="M1188.76,117.42c-13.93,5.8-29.2,4.36-42.9-1.29l4.08-16.4c7.18,4.02,29.11,9.43,29.96-.17.21-2.4-1.77-5.18-4.17-6.12l-13.21-5.19c-5.74-2.26-11.52-6.86-13.63-12.78-4.08-11.44,2.69-23.03,13.82-27.47,11.74-4.69,24.95-3.65,36.3,1.38l-4.05,15.56c-6.4-3-23.14-7.21-24.96.9-.43,1.91,1.45,5.08,3.45,5.88l14.87,5.97c8.38,3.36,13.66,10.6,13.86,18.89.21,8.77-4.25,17.02-13.42,20.84h0Z"/></g>
                      <g><rect class="cls-2-header " x="908.93" y="12.07" width="23" height="108"/><rect class="cls-2-header " x="875.93" y="12.07" width="23" height="108"/></g>
                      <path class="cls-2-header " d="M640.9,120.03l-22.94.05-.39-73.97,19.45-.04,1.19,14.28c4.45-10.27,13.04-17.02,25.15-14.92l.08,21.31c-3.87,0-6.43-.04-9.57.19-7.27.53-12.84,6.88-12.86,14.11l-.11,39h0Z"/>
                      <g><path class="cls-1-header " d="M78.77,44.93l-20.39,17.6-20.31-17.61c-5.11-4.43-9.26-9.29-14.08-15.15C27.9,22.42,51.39.46,58.1,0s29.62,21.65,34.78,29.36c-4.34,5.84-8.74,10.94-14.11,15.57h0Z"/>
                      <path class="cls-1-header " d="M56.4,141.78l-17.26-8.91C19.9,122.93-2.69,102.04.26,77.69l13.99,11.01c20.48,16.11,42.29,21.02,42.14,53.08h.01Z"/>
                      <path class="cls-1-header " d="M60.62,141.61c-.68-31.52,21.74-36.99,41.98-52.91l13.99-11c3.11,24.27-20.19,45.94-39.53,55.48-5.26,2.87-9.54,5.15-16.43,8.44h-.01Z"/>
                      <path class="cls-1-header " d="M55.01,113.27c-11.82-10.06-23.53-18.79-36.4-26.93-6.2-3.92-11.4-8.35-17.11-13.24.89-7.19,2.08-13.73,5.56-19.48l35.32,29.93c7.73,7.71,13.81,17.2,12.63,29.71h0Z"/>
                      <path class="cls-1-header " d="M95.85,87.93c-12.15,7.63-22.98,16.17-34.07,25.27-.99-12.69,5.13-22.39,13.22-30.11l35.02-29.61c3,6.16,4.79,12.74,5.19,19.56-6.08,5.67-12.4,10.51-19.36,14.88h0Z"/>
                      <path class="cls-1-header " d="M61.84,90.91c.3-17.28-3.8-21.71,7.79-32.02l27.89-24.79c2.95,4.5,5.67,9.03,8.74,14.41-8.52,10.17-17.85,18.5-28.28,26.77-5.86,4.65-10.38,9.91-16.14,15.62h0Z"/>
                      <path class="cls-1-header " d="M54.91,90.53c-5.71-5.3-10.39-10.83-16.58-15.7-10.12-7.97-18.85-16.06-27.65-26.02,2.66-5.25,5.52-10.06,8.71-14.7l27.84,24.7c3.47,3.08,6.29,6.96,8.24,11.07l-.55,20.65h0Z"/></g>
                      <g class="cls-2-header "><path d="M1224.34,24.61s.42.03.6.04c2.15.15,4.25.69,6.2,1.63,2.69,1.3,4.98,3.3,6.62,5.82s2.56,5.31,2.67,8.26l.02.68c0,.16,0,.31,0,.47l-.04.88c-.27,6.38-4.56,12.13-10.45,14.46-1.82.72-3.72,1.1-5.67,1.18-.43.02-.82.02-1.25,0-2.08-.08-4.13-.52-6.06-1.33-5.72-2.42-9.67-7.81-10.14-14.12-.01-.16-.03-.29-.03-.44v-.14s-.03-.04-.03-.03v-1.38c0-.14.04-.32.05-.48.42-6.36,4.42-11.76,10.19-14.15,1.78-.74,3.65-1.16,5.56-1.29.22-.01.41,0,.6-.04h1.15ZM1236,44.14c.46-2.38.32-4.84-.4-7.15-.51-1.64-1.33-3.16-2.4-4.47-2.19-2.7-5.37-4.31-8.8-4.53s-6.96,1.06-9.49,3.59c-1.63,1.64-2.82,3.7-3.43,5.97-.68,2.54-.65,5.24.09,7.77,1.33,4.53,4.89,7.98,9.46,8.96.97.21,1.94.29,2.94.29,6.06,0,10.89-4.5,12.03-10.44Z"/>
                      <path d="M1230.43,46.15c.27,1.29.54,2.53,1.13,3.75h-3.99c-.34-.4-.64-1.41-.77-1.93l-.53-2.18c-.08-.35-.16-.67-.31-.99-.29-.6-.77-1.03-1.39-1.26-.47-.16-.94-.25-1.44-.25h-2.04s0,6.6,0,6.6h-3.79s0-16.66,0-16.66l2.85-.35c1.53-.19,3.04-.2,4.57-.14,1.74.1,3.38.35,4.75,1.52,1.03.88,1.47,2.22,1.4,3.57-.1,1.95-1.67,3.17-3.37,3.72-.01.07-.01.15,0,.21,1.94.6,2.56,2.54,2.93,4.36ZM1226.77,38c-.06-1.34-.99-2.04-2.2-2.29-1.01-.2-2.4-.14-3.39.09v4.77s1.95,0,1.95,0c1.58,0,3.73-.54,3.64-2.56Z"/></g>
                      </g></g>
                    </svg>
                    <!-- END: WOW Logo -->
                </span>
            </a>
                <nav class="wow-desktop-only items-center gap-1 wow-desktop-nav" id="desktopNav" aria-label="Main navigation">
                    <div class="nav-item"><a class="link-wow--nav" data-mega-menu="need" tabindex="0" href="/needs">By Need</a></div>
                    <div class="nav-item"><a class="link-wow--nav" data-mega-menu="therapies" tabindex="0" href="/therapies">Therapies</a>
                    </div>
                    <div class="nav-item"><a class="link-wow--nav" data-mega-menu="events" tabindex="0" href="/events">Classes &amp; Events</a></div>
                    <div class="nav-item"><a class="link-wow--nav" data-mega-menu="locations" tabindex="0" href="/locations">Locations</a></div>
                    <div class="nav-item"><a class="link-wow--nav" data-no-mega="true" tabindex="0" href="/online">Online</a></div>
                    <div class="nav-item"><a class="link-wow--nav" data-no-mega="true" tabindex="0" href="https://times.weofferwellness.co.uk">Mindful
                        Times</a></div>
                    <span class="wow-nav-underline" id="navUnderline" aria-hidden="true"></span>
                </nav>
            </div>
            <div class="wow-desktop-only items-center gap-2 position-relative">
                <button
                    type="button"
                    class="icon-btn position-relative mobile-search-trigger wow-search-modal-trigger"
                    aria-label="Search"
                    aria-expanded="false"
                    data-wow-search-modal-trigger>
                    <span class="mobile-search-trigger__icon mobile-search-trigger__icon--search" aria-hidden="true">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                              d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"></path>
                        </svg>
                    </span>
                    <span class="mobile-search-trigger__icon mobile-search-trigger__icon--close" aria-hidden="true" hidden>
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </span>
                </button>
                <div class="account-wrap">
                    @auth
                        @php
                            $headerFullName = $headerProfile['full_name'] ?? 'Customer';
                            $headerInitials = $headerProfile['initials'] ?? 'YOU';
                        @endphp
                        <button type="button" class="icon-btn account-trigger" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                            <span class="account-trigger__avatar" aria-hidden="true">{{ $headerInitials }}</span>
                        </button>
                        <div class="account-dropdown" id="accountDropdown" hidden>
                            <div class="account-dropdown__header">
                                <p class="account-name">{{ $headerFullName }}</p>
                            </div>
                            <div class="account-actions account-actions--authed">
                                <div class="account-links-stack">
                                    <a class="account-link" href="{{ route('account.dashboard') }}">Overview</a>
                                    <a class="account-link" href="{{ route('account.orders') }}">Orders &amp; receipts</a>
                                    <a class="account-link" href="{{ route('profile.edit') }}">Profile &amp; contact</a>
                                </div>
                                <form method="POST" action="{{ route('logout') }}" class="accountdd-logout">
                                    @csrf
                                    <button type="submit" class="btn-wow btn-wow--primary btn-wow--sm accountdd-logout__btn"><span class="btn-label">Log out</span></button>
                                </form>
                            </div>
                        </div>
                    @else
                        <button type="button" class="icon-btn account-trigger" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.948 8.948 0 0 0 12 21Zm3-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                        </button>
                        <div class="account-dropdown" id="accountDropdown" hidden>
                            <p class="account-email">Sign in to manage bookings faster.</p>
                            <div class="account-actions">
                                <div class="cartdd-foot accountdd-foot">
                                    <a class="btn-wow btn-wow--outline btn-wow--sm visit-cart-btn" href="{{ route('login', ['redirect' => '/account']) }}"><span class="btn-label">Log in</span></a>
                                    <a class="btn-wow btn-wow--primary btn-wow--sm checkout-btn" href="{{ route('register', ['redirect' => '/account']) }}"><span class="btn-label">Sign up</span></a>
                                </div>
                                <a class="account-link" href="{{ route('password.request') }}">Forgot password?</a>
                            </div>
                        </div>
                    @endauth
                </div>
                <div class="cart-wrap position-relative">
                <a class="icon-btn position-relative cart-link" aria-label="Open cart" href="/cart">
                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312"></path>
                    </svg>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-badge bg-danger" style="display:none">0</span>
                </a>
                <div id="cart-dropdown" class="cart-dropdown2" hidden>
                    <div class="cartdd-head">
                      <div>Your cart <small id="cartCountLabel" style="font-weight:600;color:var(--ink-600);margin-left:6px"></small></div>
                      <small id="freeShipHint" style="font-weight:600;color:var(--ink-600)"></small>
                    </div>
                    <div class="cartdd-body" id="cartdd-body">
                        <div class="cartdd-empty mini-cart__empty">Your cart is empty</div>
                    </div>
                    <div class="cartdd-subtotal"><span>Subtotal</span><strong id="cartdd-subtotal">£0.00</strong></div>
                    <div class="cartdd-upsell-section" style="padding:10px 12px 0">
                      <div class="cartdd-upsell-head" id="cartdd-upsell-headline">Complete your calm</div>
                      <div class="cartdd-upsell" id="cartdd-upsell"></div>
                    </div>
                    <div class="cartdd-foot">
                      <a href="/cart" class="btn-wow btn-wow--outline btn-wow--sm visit-cart-btn"><span class="btn-label">Visit cart</span></a>
                      <a href="/checkout" class="btn-wow btn-wow--primary btn-wow--sm checkout-btn"><span class="btn-label">Checkout</span></a>
                    </div>
                </div>
                </div>
            </div><!---->
            <div class="wow-mobile-tablet-only items-center gap-3">
                <button
                    type="button"
                    class="mobile-nav-text-trigger mobile-search-trigger wow-search-modal-trigger"
                    aria-label="Search"
                    aria-expanded="false"
                    data-wow-search-modal-trigger>
                    <span class="mobile-search-trigger__icon mobile-search-trigger__icon--search" aria-hidden="true">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"></path>
                        </svg>
                    </span>
                </button>
                @auth
                    <a class="mobile-nav-text-trigger mobile-account-trigger" href="{{ route('account.dashboard') }}" aria-label="Account">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.948 8.948 0 0 0 12 21Zm3-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </a>
                @else
                    <a class="mobile-nav-text-trigger mobile-account-trigger" href="{{ route('login', ['redirect' => '/account']) }}" aria-label="Account">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.948 8.948 0 0 0 12 21Zm3-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </a>
                @endauth
                <button
                    type="button"
                    class="mobile-nav-text-trigger mobile-menu-trigger wow-mobile-menu-anchor"
                    data-wow-mobile-toggle
                    aria-label="Menu" aria-expanded="false">
                    <span class="mobile-menu-trigger__icon mobile-menu-trigger__icon--closed" aria-hidden="true">
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/>
                        </svg>
                    </span>
                    <span class="mobile-menu-trigger__icon mobile-menu-trigger__icon--open" aria-hidden="true">
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                        </svg>
                    </span>
                </button>
            </div>
        </div><!----><!---->
        <div class="wow-mega-layer" id="megaLayer">
            <div id="mega-panel" class="mega-panel wow-mega-shell" hidden>
                <span class="wow-mega-arrow" id="megaArrow"></span>
                <div class="wow-mega-viewport">
                    <div class="wow-mega-track" id="megaTrack">
                        <section class="wow-mega-pane" data-menu="need">
                            <div class="wow-mega-grid wow-mega-grid--3">
                                <div class="wow-mega-col">
                                    <p class="mega-kicker">How are you feeling?</p>
                                    <div class="wow-menu-list">
                                        @foreach(collect($publicNeeds ?? [])->take(4) as $need)
                                            <a class="menu-link" href="{{ route('needs.show', ['slug' => $need['slug']]) }}"><strong>{{ $need['name'] }}</strong>@if($need['description'] !== '')<span>{{ Str::limit($need['description'], 90) }}</span>@endif</a>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="wow-mega-col">
                                    <p class="mega-kicker">What do you want?</p>
                                    <div class="wow-menu-list">
                                        @foreach(collect($publicNeeds ?? [])->slice(4, 4) as $need)
                                            <a class="menu-link" href="{{ route('needs.show', ['slug' => $need['slug']]) }}"><strong>{{ $need['name'] }}</strong>@if($need['description'] !== '')<span>{{ Str::limit($need['description'], 90) }}</span>@endif</a>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="wow-mega-col wow-mega-col--divider">
                                    <p class="mega-kicker">For you</p>
                                    <div class="wow-soft-panel wow-soft-panel--green">
                                        <p class="mega-label">Recommended next</p>
                                        <h3>Not sure where to start?</h3>
                                        <p>Answer a few quick questions and browse therapies that match how you feel today.</p>
                                        <div class="wow-button-row">
                                            <a href="/needs" class="btn-wow btn-wow--primary btn-wow--sm btn-arrow">Browse needs</a>
                                            <a href="/therapies" class="btn-wow btn-wow--outline btn-wow--sm btn-arrow">View therapies</a>
                                        </div>
                                    </div>
                                    <div class="wow-for-you-card" data-need-default-block>
                                        <p class="mega-label">Popular this week</p>
                                        <ul class="list-unstyled m-0 p-0" data-need-default-popular></ul>
                                        <p class="mega-label">Trending online</p>
                                        <ul class="list-unstyled m-0 p-0" data-need-default-trending></ul>
                                    </div>
                                    <div class="wow-for-you-card" data-need-personalized-block hidden aria-hidden="true">
                                        <p class="mega-label">Continue browsing</p>
                                        <ul class="list-unstyled m-0 p-0" data-need-continue></ul>
                                        <p class="mega-label">Recommended next</p>
                                        <ul class="list-unstyled m-0 p-0" data-need-recommended></ul>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="wow-mega-pane" data-menu="therapies">
                            <div class="wow-mega-grid wow-mega-grid--3">
                                <div class="wow-mega-col wow-mega-col--wide">
                                    <div class="wow-mega-row">
                                        <p class="mega-kicker">Featured therapies</p>
                                        <a href="/therapies" class="btn-wow btn-wow--outline btn-wow--sm btn-arrow">View all therapies</a>
                                    </div>
                                    <div class="wow-image-card-grid">
                                        <a href="/therapies/reiki" class="wow-image-card wow-image-card--reiki"><div><h3>Reiki</h3><p>Energy-led support for calm and balance.</p></div></a>
                                        <a href="/therapies/massage" class="wow-image-card wow-image-card--massage"><div><h3>Massage</h3><p>Relax, release and ease physical tension.</p></div></a>
                                        <a href="/therapies/sound-healing" class="wow-image-card wow-image-card--sound"><div><h3>Sound healing</h3><p>Immersive calm through vibration and sound.</p></div></a>
                                        <a href="/therapies/breathwork" class="wow-image-card wow-image-card--breath"><div><h3>Breathwork</h3><p>Guided sessions for reset and emotional release.</p></div></a>
                                    </div>
                                </div>
                                <div class="wow-mega-col wow-mega-col--divider">
                                    <p class="mega-kicker">Browse by format</p>
                                    <div class="wow-menu-list">
                                        @foreach($therapyFormatLinks as $link)
                                            <a class="menu-link" href="{{ $link['href'] }}"><strong>{{ $link['label'] }}</strong><span>Explore {{ strtolower($link['label']) }} on We Offer Wellness®.</span></a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="wow-mega-pane" data-menu="events">
                            <div class="wow-mega-grid wow-mega-grid--2">
                                <a href="/events" class="wow-feature-card">
                                    <h3>OUR VIBE Festival 2026</h3>
                                    <p>Sound healing, meditation, talks, workshops and chilled wellbeing at Bilsington Priory Estate.</p>
                                        <span class="btn-wow btn-wow--primary btn-wow--sm btn-arrow">Explore event</span>
                                </a>
                                <div class="wow-mega-grid wow-mega-grid--2 wow-mega-grid--nested">
                                    <div class="wow-mega-col">
                                        <p class="mega-kicker">Browse by format</p>
                                        <div class="wow-menu-list">
                                            @if($eventsMenuLinks['events'] ?? false)<a class="menu-link" href="/events"><strong>Events</strong><span>Live wellbeing experiences and gatherings.</span></a>@endif
                                            @if($eventsMenuLinks['classes'] ?? false)<a class="menu-link" href="/classes"><strong>Classes</strong><span>Yoga, meditation, breathwork and more.</span></a>@endif
                                            @if($eventsMenuLinks['workshops'] ?? false)<a class="menu-link" href="/workshops"><strong>Workshops</strong><span>Learn, reset and take something useful away.</span></a>@endif
                                            <a class="menu-link" href="/retreats"><strong>Retreats</strong><span>Longer escapes for deeper rest.</span></a>
                                        </div>
                                    </div>
                                    <div class="wow-mega-col">
                                        <p class="mega-kicker">Explore</p>
                                        <div class="wow-menu-list">
                                            <a class="menu-link" href="/locations"><strong>Near you</strong><span>Local classes and events.</span></a>
                                            <a class="menu-link" href="/online"><strong>Online</strong><span>Join from wherever you are.</span></a>
                                            <a class="menu-link" href="/gifts"><strong>Gift experiences</strong><span>Thoughtful wellbeing gifts.</span></a>
                                            <a class="menu-link" href="https://times.weofferwellness.co.uk"><strong>Mindful Times</strong><span>Guides, stories and wellness reads.</span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="wow-mega-pane" data-menu="locations">
                            <div class="wow-mega-grid wow-mega-grid--2">
                                <div class="wow-map-card">
                                    <div class="wow-map-pin"></div>
                                    <div>
                                        <p class="mega-label">Find wellness near you</p>
                                        <h3>Explore trusted therapies by location.</h3>
                                        <div class="wow-button-row">
                                            <a href="/locations" class="btn-wow btn-wow--primary btn-wow--sm btn-arrow">View all locations</a>
                                            <a href="/online" class="btn-wow btn-wow--outline btn-wow--sm btn-arrow">Online sessions</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="wow-mega-grid wow-mega-grid--2 wow-mega-grid--nested">
                                    <div class="wow-mega-col">
                                        <p class="mega-kicker">Popular locations</p>
                                        <div class="wow-menu-list">
                                            <a class="menu-link" href="/locations/kent"><strong>Kent</strong><span>Local therapies across Kent.</span></a>
                                            <a class="menu-link" href="/locations/london"><strong>London</strong><span>Wellbeing sessions across the capital.</span></a>
                                            <a class="menu-link" href="/locations/bristol"><strong>Bristol</strong><span>Alternative therapies and classes.</span></a>
                                            <a class="menu-link" href="/locations/manchester"><strong>Manchester</strong><span>Urban wellness and events.</span></a>
                                        </div>
                                    </div>
                                    <div class="wow-mega-col">
                                        <p class="mega-kicker">Quick searches</p>
                                        <div class="wow-pill-row">
                                            <a href="/therapies/massage" class="wow-pill">Massage near me</a>
                                            <a href="/therapies/reiki" class="wow-pill">Reiki near me</a>
                                            <a href="/therapies/sound-healing" class="wow-pill">Sound healing near me</a>
                                            <a href="/therapies/meditation" class="wow-pill">Meditation near me</a>
                                            <a href="/therapies/reflexology" class="wow-pill">Reflexology near me</a>
                                            <a href="/therapies/breathwork" class="wow-pill">Breathwork near me</a>
                                        </div>
                                        <div class="wow-soft-panel">
                                            <h3>Online also available</h3>
                                            <p>When local options are limited, online therapies still give visitors a route to book.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
@include('partials.search-modal')
<div id="wow-header-offset-spacer" aria-hidden="true"></div>
<style>
/* Backdrop for mega menu (below header/utility, above content) */
.mega-overlay{
    position: fixed; inset: 0;
    background: rgba(27,99,168,.12);
    -webkit-backdrop-filter: blur(10px) saturate(1.2);
    backdrop-filter: blur(10px) saturate(1.2);
    z-index: 35; /* below header (40) and utility-bar (41), above content/search (30) */
    pointer-events: none;
}
/* Utility bar: scrolls normally (header overlays it on desktop) */
.utility-bar{ position: relative; z-index: 45; background: rgba(255,255,255,.98); backdrop-filter: blur(8px); }
/* Cart dropdown (desktop hover) */
.cart-wrap{ position: relative; }
.cart-dropdown2{ position:absolute; right:0; top:calc(100% + 10px); width: min(380px, 92vw); background:#fff; border:1px solid rgba(0,0,0,0.15); border-radius:3px; box-shadow: 0 24px 60px rgba(16,24,40,.16); overflow:hidden; z-index: 45; }
.cartdd-head{ padding:10px 14px; font-weight:700; background: linear-gradient(180deg,#fff,#f8fafc); border-bottom:1px solid #eef2f7 }
.cartdd-body{ max-height: 380px; overflow:auto }
.cartdd-empty{ padding:18px; color: var(--ink-600); text-align:center }
.cartdd-item{ display:flex; gap:10px; align-items:center; padding:12px 14px; padding-right:60px; border-bottom:1px solid #f1f5f9; position:relative; }
.cartdd-item:last-child{ border-bottom:0 }
.cartdd-img{ width:54px; height:54px; min-width:54px; flex:0 0 54px; border-radius:10px; overflow:hidden; border:1px solid #eceff3; background:#fafafa }
.cartdd-img img{ width:100%; height:100%; object-fit:cover; display:block; flex:0 0 54px }
.cartdd-info{ flex:1 1 auto; min-width:0 }
.cartdd-title{ display:block; max-width:100%; font-weight:600; color:#0b1323; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-decoration:none }
.cartdd-meta{ font-size:.9rem; color:#64748b }
.cartdd-amt{ font-weight:700; color:#0b1323; white-space:nowrap; flex:0 0 auto }
.cartdd-remove{ position:absolute; top:50%; right:14px; width:34px; height:34px; border-radius:50%; border:0; background:#dc2626; color:#fff; display:flex; align-items:center; justify-content:center; opacity:0; transform:translate(10px,-50%); transition:opacity .2s ease, transform .2s ease; cursor:pointer; pointer-events:none; }
.cartdd-remove svg{ width:18px; height:18px; }
.cartdd-item:hover .cartdd-remove,
.cartdd-remove:focus-visible{ opacity:1; transform:translate(0,-50%); pointer-events:auto; }
.cartdd-remove:focus-visible{ outline:2px solid #fff; outline-offset:2px; }
.cartdd-subtotal{ padding:10px 14px; display:flex; align-items:center; justify-content:space-between; border-top:1px solid #eef2f7; border-bottom:1px solid #eef2f7; background:#fff }
.cartdd-subtotal span{ font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:.02em }
.cartdd-upsell .upsell-item{ display:grid; grid-template-columns:46px 1fr auto; gap:10px; align-items:center; padding:8px 10px; border:1px solid #eef2f7; border-radius:10px; background:#fff; margin-bottom:8px }
.cartdd-upsell .upsell-item img{ width:46px; height:46px; object-fit:cover; border-radius:8px; border:1px solid #eceff3 }
.cartdd-upsell .upsell-title{ margin:0; font-size:13px; font-weight:700; color:#0b1323; line-height:1.25; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
.cartdd-upsell .upsell-price{ font-size:12px; color:#64748b; font-weight:700; margin-top:4px }
.cartdd-upsell-head{ font-weight:800; letter-spacing:-.01em; margin:0 0 8px; color:#0b1323 }
.cartdd-foot{ display:flex; gap:10px; align-items:center; justify-content:space-between; background:#fff; padding:10px 12px 14px }
.menu-link--disabled{
    pointer-events: none;
    opacity: .5;
    cursor: default;
}
.menu-col--foryou .mega-label{
    text-transform:uppercase;
    letter-spacing:.24em;
    font-size:11px;
    color:var(--ink-500);
    margin:12px 0 6px;
}
.menu-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-left:8px;
    padding:0 6px;
    font-size:10px;
    letter-spacing:.15em;
    text-transform:uppercase;
    border-radius:999px;
    background:#0b1220;
    color:#fff;
}
.menu-col--foryou .mega-label{
    text-transform:uppercase;
    letter-spacing:.24em;
    font-size:11px;
    color:var(--ink-500);
    margin:12px 0 6px;
}
/* Buttons styled like product card actions */
.cart-dropdown2 .btn,
.account-dropdown .btn{ height:38px; border-radius:4px; font-size:16px; font-weight:400; border:1px solid rgba(16,24,40,.22); background:#fff !important; color: rgba(11,18,32,.82); cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 10px 22px rgba(16,24,40,.08); text-decoration:none; flex:1 1 auto }
.cart-dropdown2 .btn--primary,
.account-dropdown .btn--primary{ border-color: rgba(0,0,0,.10); color:#fff; background:#549483 !important }
.accountdd-logout{ margin-top:15px; }
.accountdd-logout__btn{ width:100%; }
@media (min-width: 992px){
    .account-dropdown{
        height:auto;
        max-height:calc(100vh - 120px);
        overflow-y:auto;
    }
}
#visit-link{ font-weight:700; color:#2c6bed; text-decoration:none }
#visit-link:hover{ text-decoration:underline }
.account-links-stack{ display:flex; flex-direction:column; gap:6px; margin-top:4px; }
.account-links-stack .account-link{ font-weight:700; color:var(--ink-800); font-size:14px; }
.accountdd-logout{ margin-top:15px; }
.accountdd-logout__btn{ width:100%; }
.mobile-menu__account{ margin-top:16px; padding-top:16px; border-top:1px solid var(--ink-200); display:flex; flex-direction:column; gap:12px; }
.mobile-account-card{ display:flex; align-items:center; gap:12px; padding:12px; border-radius:14px; border:1px solid rgba(16,24,40,.08); background:#f8fafc; }
.mobile-account-card--guest{ flex-direction:column; align-items:flex-start; }
.mobile-account-avatar{ width:44px; height:44px; border-radius:50%; background:#105b4b; color:#fff; font-weight:700; display:flex; align-items:center; justify-content:center; }
.mobile-account-name{ margin:0; font-size:15px; font-weight:700; color:var(--ink-900); }
.mobile-account-email{ display:block; font-size:13px; color:var(--ink-600); }
.mobile-account-manage{ font-size:13px; font-weight:600; color:var(--wow-green); text-decoration:none; }
.mobile-account-manage:hover{ text-decoration:underline; }
.mobile-account-links{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:6px; }
.mobile-account-links a{ display:block; padding:10px 12px; border-radius:10px; background:#f5f7fa; text-decoration:none; color:var(--ink-800); font-weight:600; }
.mobile-account-links a:hover{ background:#e8eef7; color:var(--ink-900); }
.mobile-menu__link--button{ display:block; width:100%; text-align:left; border:none; background:none; padding:10px 12px; border-radius:10px; font-weight:600; color:var(--ink-800); cursor:pointer; }
.mobile-menu__link--button:hover{ background: var(--ink-100); color: var(--ink-900); }
.mobile-menu__link--button:focus-visible{ outline:2px solid currentColor; outline-offset:2px; }
.mobile-account-buttons{ display:flex; flex-direction:column; gap:10px; }
.mobile-account-logout button{ width:100%; }
.mobile-account-guest-title{ margin:0; font-weight:800; letter-spacing:-.01em; color:var(--ink-900); }
.mobile-account-guest-text{ margin:4px 0 0; color:var(--ink-700); font-size:13px; }
.mobile-menu__kicker{ margin:0 12px 8px; color:var(--ink-500); font-size:12px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }

/* Mega menu — Stripe-style polish */
#mega-panel .container-page{ padding:0 !important; border-radius:0; }
#mega-panel .mega-panel,
.mega-panel{ border-radius:0 0 4px 4px; }
#mega-panel,
#mega-panel *{ font-family:"Manrope", "Instrument Sans", var(--font-sans, system-ui) !important; }
#mega-panel [data-menu]{ display:none !important; }
#mega-panel[data-active="need"] [data-menu="need"],
#mega-panel[data-active="therapies"] [data-menu="therapies"],
#mega-panel[data-active="events"] [data-menu="events"]{ display:grid !important; }

#mega-panel .grid{ gap:0 !important; }
#mega-panel .menu-col{
    padding:40px 30px !important;
    min-width:0;
    border-left:1px solid transparent;
    border-radius:0 !important;
}
#mega-panel .menu-col:first-child{ border-left:0; }

#mega-panel .mega-kicker{
    font-family:"Instrument Sans", var(--font-sans, system-ui);
    font-size:12px;
    font-weight:600;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:rgba(74,136,120,.92);
    margin:0 0 12px;
}

#mega-panel .menu-link{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
    padding:9px 0;
    border-radius:3px;
    text-decoration:none;
    color:rgba(11,18,32,.86);
    font-weight:650;
    font-family:"Instrument Sans", var(--font-sans, system-ui);
    font-size:14px;
    transition:background .16s cubic-bezier(.2,.8,.2,1), transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s cubic-bezier(.2,.8,.2,1);
    position:relative;
}
#mega-panel .menu-link:hover{
    background:rgba(11,18,32,.08);
    transform:translateY(-1px);
    box-shadow:none;
    color:rgba(11,18,32,.92);
    padding:9px 10px;
}

#mega-panel .menu-col--foryou .mega-label,
#mega-panel .mega-label{
    font-family:"Instrument Sans", var(--font-sans, system-ui);
    font-size:10px;
    letter-spacing:.08em;
    font-weight:600;
    text-transform:uppercase;
    color:#443;
    margin:14px 0 8px;
}
#mega-panel .mega-quick-links .menu-link{ font-size:13px; font-weight:600; }

#mega-panel .list-unstyled{ display:flex; flex-direction:column; gap:8px; }

#mega-panel [data-menu="need"]{ grid-template-columns:1.05fr 1.05fr .95fr; }
#mega-panel [data-menu="need"] .menu-col--foryou{
    background:linear-gradient(180deg, rgba(15,23,42,.02), rgba(15,23,42,0));
    border-left:1px solid rgba(15,23,42,.08);
}

#mega-panel [data-menu="therapies"]{ grid-template-columns:1fr 1fr .92fr; }
#mega-panel [data-menu="therapies"] .menu-col + .menu-col{ border-left:1px solid rgba(15,23,42,.08); }
#mega-panel [data-menu="therapies"] .menu-col:nth-child(2){ background:rgba(15,23,42,.02); }

#mega-panel .menu-col--foryou .mega-label + ul{ margin-top:0; }

#mega-panel .menu-col .mega-label:first-child{ margin-top:0; }

@media (max-width: 991.98px){
    #mega-panel .grid{ grid-template-columns:1fr !important; }
    #mega-panel [data-menu="need"] .menu-col--foryou,
    #mega-panel [data-menu="therapies"] .menu-col + .menu-col{ border-left:0; border-top:1px solid rgba(15,23,42,.08); }
}

.wow-desktop-nav{
    position:relative;
    height:100%;
    align-items:center;
}
.wow-desktop-nav .nav-item > a.link-wow--nav{
    position:relative;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:13px 18px;
    border-radius:18px;
    color:#172033;
    font-size:18px;
    line-height:1;
    transition:background 220ms ease, color 220ms ease, transform 220ms ease;
}
.wow-desktop-nav .nav-item > a.link-wow--nav::after{ content:none !important; }
.wow-desktop-nav .nav-item > a.link-wow--nav:hover,
.wow-desktop-nav .nav-item > a.link-wow--nav:focus-visible,
.wow-desktop-nav .nav-item > a.link-wow--nav.is-active{
    background:#f1f2f4;
    color:#111827;
    outline:none;
}
.wow-nav-underline{
    position:absolute;
    left:0;
    bottom:7px;
    width:0;
    height:4px;
    border-radius:999px;
    background:#549483;
    opacity:0;
    transform:translateX(0);
    transition:transform 360ms cubic-bezier(.2,.9,.2,1), width 360ms cubic-bezier(.2,.9,.2,1), opacity 180ms ease;
    pointer-events:none;
}
.wow-mega-layer{
    --mega-max-width:1160px;
    --mega-edge-gap:18px;
    position:fixed;
    inset:0 0 auto;
    top:var(--wow-header-offset, 88px);
    z-index:990;
    pointer-events:none;
}
#mega-panel.wow-mega-shell{
    display:block !important;
    position:absolute !important;
    top:0px !important;
    left:50% !important;
    right:auto !important;
    width:min(var(--mega-max-width), calc(100vw - 36px)) !important;
    height:auto;
    min-height:0;
    max-width:none !important;
    background:rgba(255,255,255,.98);
    border:1px solid rgba(229,231,235,.95);
    border-radius:22px !important;
    box-shadow:0 24px 70px rgba(17,24,39,.16);
    overflow:visible;
    opacity:0;
    transform:translate3d(-50%, -10px, 0) scale(.985) !important;
    transform-origin:top center;
    transition:opacity 180ms ease, transform 260ms cubic-bezier(.18,.95,.2,1);
    pointer-events:none;
    will-change:transform, opacity;
}
#mega-panel.wow-mega-shell::before{
    content:"";
    position:absolute;
    left:0;
    right:0;
    top:-28px;
    height:28px;
    background:transparent;
}
#mega-panel.wow-mega-shell.is-open{
    opacity:1;
    transform:translate3d(-50%, 0, 0) scale(1) !important;
    pointer-events:auto;
}
.wow-mega-arrow{
    position:absolute;
    top:-9px;
    left:50%;
    width:16px;
    height:16px;
    background:#fff;
    border-left:1px solid rgba(229,231,235,.95);
    border-top:1px solid rgba(229,231,235,.95);
    transform:translateX(-50%) rotate(45deg);
    transition:left 430ms cubic-bezier(.18,.95,.2,1);
    z-index:2;
}
.wow-mega-viewport{
    position:relative;
    height:auto;
    overflow:hidden;
    border-radius:inherit;
    background:rgba(255,255,255,.98);
}
.wow-mega-track{
    display:flex;
    height:auto;
    transition:transform 460ms cubic-bezier(.18,.95,.2,1);
    will-change:transform;
    align-items:flex-start;
}
#mega-panel .wow-mega-pane[data-menu]{
    display:block !important;
    flex:0 0 100%;
    min-width:100%;
    height:auto;
    padding:42px 46px;
}
.wow-mega-grid{
    display:grid;
    gap:34px;
}
.wow-mega-grid--3{ grid-template-columns:1.1fr 1.1fr .9fr; }
.wow-mega-grid--2{ grid-template-columns:.95fr 1.05fr; }
.wow-mega-grid--nested{ gap:30px; }
.wow-mega-col{ min-width:0; }
.wow-mega-col--wide{ grid-column:span 2; }
.wow-mega-col--divider{
    padding-left:34px;
    border-left:1px solid #e5e7eb;
}
#mega-panel.wow-mega-shell .mega-kicker{
    margin:0 0 24px;
    color:#126c45;
    font-size:14px;
    line-height:1;
    font-weight:700;
    letter-spacing:.18em;
    text-transform:uppercase;
}
#mega-panel.wow-mega-shell .mega-label{
    margin:0 0 14px;
    color:#524a40;
    font-size:12px;
    line-height:1;
    font-weight:700;
    letter-spacing:.16em;
    text-transform:uppercase;
}
.wow-menu-list{
    display:grid;
    gap:9px;
}
#mega-panel.wow-mega-shell .menu-link{
    display:block;
    padding:12px;
    margin-left:-12px;
    border-radius:0px;
    color:#273142;
    text-decoration:none;
    transition:background 180ms ease, transform 180ms ease;
}
#mega-panel.wow-mega-shell .menu-link strong{
    display:block;
    color:#273142;
    font-size:20px;
    line-height:1.25;
    font-weight:700;
    letter-spacing:-.03em;
}
#mega-panel.wow-mega-shell .menu-link span{
    display:block;
    margin-top:4px;
    color:#6b7280;
    font-size:14px;
    line-height:1.4;
}
#mega-panel.wow-mega-shell .menu-link:hover,
#mega-panel.wow-mega-shell .menu-link:focus-visible{
    background:#f6f8f7;
    transform:translateX(4px);
    box-shadow:none;
    outline:none;
}
.wow-soft-panel{
    margin-top:24px;
    background:#f6f8f7;
    border:1px solid #e5e7eb;
    border-radius:20px;
    padding:22px;
}
.wow-soft-panel--green{
    margin-top:0;
    background:#edf6f3;
    border-color:rgba(84,148,131,.25);
}
.wow-soft-panel h3,
.wow-image-card h3,
.wow-feature-card h3,
.wow-map-card h3{
    margin:0;
    color:#273142;
    font-size:20px;
    line-height:1.2;
    letter-spacing:-.035em;
}
.wow-soft-panel p,
.wow-image-card p,
.wow-feature-card p{
    margin:8px 0 0;
    color:#6b7280;
    font-size:14px;
    line-height:1.55;
}
.wow-button-row{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:18px;
}
.wow-mini-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    min-height:40px;
    padding:0 14px;
    border-radius:999px;
    border:1px solid #e5e7eb;
    background:#fff;
    color:#273142;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
    transition:background 180ms ease, border-color 180ms ease, transform 180ms ease;
}
.wow-mini-btn:hover{
    transform:translateY(-1px);
    border-color:rgba(84,148,131,.45);
}
.wow-mini-btn--primary{
    background:#549483;
    color:#fff;
    border-color:#549483;
}
.wow-for-you-card{
    display:grid;
    gap:8px;
    margin-top:18px;
}
.wow-for-you-card .menu-link{
    margin-left:0 !important;
    padding:9px 0 !important;
    border-bottom:1px solid #e5e7eb;
}
.wow-mega-row{
    display:flex;
    justify-content:space-between;
    gap:20px;
    align-items:center;
    margin-bottom:24px;
}
.wow-mega-row .mega-kicker{ margin:0 !important; }
.wow-image-card-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:14px;
}
.wow-image-card,
.wow-feature-card{
    position:relative;
    overflow:hidden;
    border-radius:20px;
    background:#dfe8e5;
    isolation:isolate;
    padding:18px;
    display:flex;
    align-items:flex-end;
    min-height:154px;
    text-decoration:none;
    transition:transform 220ms ease, box-shadow 220ms ease;
}
.wow-image-card:hover,
.wow-feature-card:hover{
    transform:translateY(-3px);
    box-shadow:0 18px 40px rgba(17,24,39,.14);
}
.wow-image-card::before,
.wow-feature-card::before{
    content:"";
    position:absolute;
    inset:0;
    background-size:cover;
    background-position:center;
    z-index:-2;
    transform:scale(1.02);
    transition:transform 300ms ease;
}
.wow-image-card:hover::before{ transform:scale(1.08); }
.wow-image-card::after,
.wow-feature-card::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.58));
    z-index:-1;
}
.wow-image-card h3,
.wow-image-card p,
.wow-feature-card h3,
.wow-feature-card p{
    color:#fff;
    text-shadow:0 1px 20px rgba(0,0,0,.34);
}
.wow-image-card--reiki::before,
.wow-image-card--breath::before{ background-image:url("https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=900&q=80"); }
.wow-image-card--massage::before{ background-image:url("https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=900&q=80"); }
.wow-image-card--sound::before{ background-image:url("https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&fit=crop&w=900&q=80"); }
.wow-feature-card{
    min-height:330px;
    border-radius:24px;
    padding:26px;
    flex-direction:column;
    justify-content:flex-end;
    align-items:flex-start;
}
.wow-feature-card::before{ background-image:url("https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=1100&q=80"); }
.wow-feature-card::after{ background:linear-gradient(180deg, rgba(17,24,39,.02), rgba(17,24,39,.72)); }
.wow-feature-card h3{ font-size:28px; }
.wow-pill-row{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}
.wow-pill{
    display:inline-flex;
    align-items:center;
    min-height:38px;
    padding:0 14px;
    border:1px solid #e5e7eb;
    border-radius:999px;
    color:#273142;
    background:#fff;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
    transition:background 180ms ease, transform 180ms ease;
}
.wow-pill:hover{
    background:#edf6f3;
    transform:translateY(-1px);
}
.wow-map-card{
    position:relative;
    min-height:330px;
    border-radius:24px;
    overflow:hidden;
    background:radial-gradient(circle at 30% 35%, rgba(84,148,131,.5), transparent 16%), radial-gradient(circle at 54% 56%, rgba(84,148,131,.38), transparent 13%), radial-gradient(circle at 66% 28%, rgba(84,148,131,.3), transparent 11%), #e9f1ef;
    border:1px solid rgba(84,148,131,.22);
    padding:26px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
}
.wow-map-card::before{
    content:"";
    position:absolute;
    inset:22px;
    border-radius:20px;
    border:1px dashed rgba(50,111,97,.35);
    pointer-events:none;
}
.wow-map-card h3{
    max-width:360px;
    font-size:30px;
    line-height:1.05;
    letter-spacing:-.055em;
}
.wow-map-pin{
    width:42px;
    height:42px;
    border-radius:50% 50% 50% 0;
    background:#549483;
    transform:rotate(-45deg);
    box-shadow:0 12px 30px rgba(84,148,131,.36);
}
.wow-map-pin::after{
    content:"";
    position:absolute;
    inset:12px;
    border-radius:50%;
    background:#fff;
}
@media (max-width: 1080px){
    .wow-mega-layer{ display:none !important; }
    .wow-nav-underline{ display:none; }
}
</style>


        

        <!-- Mobile menu (drawer) -->
        <div id="mobile-menu-backdrop" class="mobile-menu-backdrop" aria-hidden="true"></div>
        <div id="mobile-menu" class="mobile-menu" style="display:none">
	    <nav class="mobile-menu__nav">
                <div class="mobile-menu__kicker">Explore</div>
                <ul class="mobile-menu__list">
                    <li><a class="mobile-menu__link" href="/needs">By Need</a></li>
                    <li><a class="mobile-menu__link" href="/therapies">Therapies</a></li>
                    <li><a class="mobile-menu__link" href="/events">Classes &amp; Events</a></li>
                    @foreach($localLinks as $link)
                        <li><a class="mobile-menu__link" href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                    <li><a class="mobile-menu__link" href="/gifts">Gifts</a></li>
                    @if($eventsMenuVisible)
                        <li><a class="mobile-menu__link" href="/workshops">Workshops</a></li>
                        <li><a class="mobile-menu__link" href="/retreats">Retreats</a></li>
                    @endif
                    <li><a class="mobile-menu__link" href="https://times.weofferwellness.co.uk">Mindful Times</a></li>
                    <li><a class="mobile-menu__link" href="/cart">Cart</a></li>
                </ul>
                <div class="mobile-menu__section">
                    <div class="mobile-menu__section-title">By need</div>
                    <ul class="mobile-menu__list">
                        @foreach(collect($publicNeeds ?? []) as $need)
                            <li><a class="mobile-menu__link" href="{{ route('needs.show', ['slug' => $need['slug']]) }}">{{ $need['name'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="mobile-menu__section">
                    <div class="mobile-menu__section-title">Modality</div>
                    <ul class="mobile-menu__list">
                        <li><a class="mobile-menu__link" href="/therapies/reiki">Reiki</a></li>
                        <li><a class="mobile-menu__link" href="/therapies/sound-healing">Sound healing</a></li>
                        <li><a class="mobile-menu__link" href="/therapies/breathwork">Breathwork</a></li>
                        <li><a class="mobile-menu__link" href="/therapies/massage">Massage</a></li>
                        <li><a class="mobile-menu__link" href="/therapies/reflexology">Reflexology</a></li>
                        <li><a class="mobile-menu__link" href="/therapies/meditation">Meditation</a></li>
                        <li><a class="mobile-menu__link" href="/classes?category=yoga">Yoga classes</a></li>
                    </ul>
                </div>
                <div class="mobile-menu__section">
                    <div class="mobile-menu__section-title">Help &amp; Info</div>
                    <ul class="mobile-menu__list">
                        <li><a class="mobile-menu__link" href="/about">About We Offer Wellness®</a></li>
                        <li><a class="mobile-menu__link" href="/safety-and-contraindications">Safety &amp; Contraindications</a></li>
                        <li>
                            <a href="https://studio.weofferwellness.co.uk/" class="mobile-menu__link">Become a WOW Practitoner</a>
                        </li>
                    </ul>
                </div>
                <div class="mobile-menu__account">
                    @auth
                        @php
                            $mobileFullName = $headerProfile['full_name'] ?? 'Customer';
                            $mobileInitials = $headerProfile['initials'] ?? 'YOU';
                        @endphp
                        <div class="mobile-account-card">
                            <span class="mobile-account-avatar" aria-hidden="true">{{ $mobileInitials }}</span>
                            <div class="mobile-account-copy">
                                <p class="mobile-account-name">{{ $mobileFullName }}</p>
                                @if(!empty($headerProfile['email']))
                                    <span class="mobile-account-email">{{ $headerProfile['email'] }}</span>
                                @endif
                                <a class="mobile-account-manage" href="{{ route('account.dashboard') }}">View account</a>
                            </div>
                        </div>
                        <ul class="mobile-account-links">
                            <li><a href="{{ route('account.dashboard') }}">Overview</a></li>
                            <li><a href="{{ route('account.orders') }}">Orders &amp; receipts</a></li>
                            <li><a href="{{ route('profile.edit') }}">Profile &amp; contact</a></li>
                        </ul>
                        <form method="POST" action="{{ route('logout') }}" class="mobile-account-logout">
                            @csrf
                            <button type="submit" class="btn-wow btn-wow--primary btn-wow--sm"><span class="btn-label">Log out</span></button>
                        </form>
                    @else
                        <div class="mobile-account-card mobile-account-card--guest">
                            <p class="mobile-account-guest-title">Account</p>
                            <p class="mobile-account-guest-text">Save favourites, manage bookings, and checkout faster.</p>
                        </div>
                        <div class="mobile-account-buttons">
                            <a class="btn-wow btn-wow--outline btn-wow--sm" href="{{ route('login', ['redirect' => '/account']) }}"><span class="btn-label">Log in</span></a>
                            <a class="btn-wow btn-wow--primary btn-wow--sm" href="{{ route('register', ['redirect' => '/account']) }}"><span class="btn-label">Create account</span></a>
                        </div>
                    @endauth
                </div>
            </nav>
        </div>

        <div id="mobile-search-drawer" class="mobile-search-drawer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="mobile-search-title">
            <div class="mobile-search-drawer__backdrop" data-mobile-search-close aria-hidden="true"></div>
            <div class="mobile-search-drawer__inner">
                <div class="mobile-search-drawer__panel">
                    <div
                        class="mobile-search-drawer__search"
                        data-wow-searchbar-v4
                        data-id-prefix="header-search"
                        data-search-url="{{ url('/search') }}"
                        data-result-count="0"
                        data-mobile-top-offset="0"
                        data-initial-query='@json(request()->query())'
                        data-static-layout="1"
                        data-show-chrome="0"
                        data-mobile-chrome="0"
                        data-navigate-on-submit="1"
                        data-force-mobile-layout="1"
                        data-default-active-segment="what"
                        data-hide-top-row="1"
                        data-hide-mobile-close="1"
                    ></div>
                </div>
            </div>
        </div>

        <div id="wowPractitionerModal" class="practitioner-modal" aria-hidden="true">
            <div class="practitioner-modal__backdrop" data-practitioner-dismiss></div>
            <div class="practitioner-modal__panel" role="dialog" aria-modal="true" aria-labelledby="wowPractitionerTitle" tabindex="-1">
                <button type="button" class="practitioner-modal__close" data-practitioner-dismiss aria-label="Close form">&times;</button>
                <p class="practitioner-modal__eyebrow">For practitioners</p>
                <h2 id="wowPractitionerTitle">Become a WOW Practitioner</h2>
                <p class="practitioner-modal__subtitle">Share a few details so we can keep you updated on onboarding windows, perks and support.</p>
                <form id="wowPractitionerForm" class="practitioner-form" novalidate>
                    <div class="field-row">
                        <div class="field-group">
                            <label for="wowPractitionerFirst">First name</label>
                            <input id="wowPractitionerFirst" name="first_name" type="text" autocomplete="given-name" required>
                        </div>
                        <div class="field-group">
                            <label for="wowPractitionerLast">Last name</label>
                            <input id="wowPractitionerLast" name="last_name" type="text" autocomplete="family-name" required>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="wowPractitionerEmail">Email</label>
                        <input id="wowPractitionerEmail" name="email" type="email" autocomplete="email" required>
                    </div>
                    <div class="field-group">
                        <label for="wowPractitionerBusiness">Business name</label>
                        <input id="wowPractitionerBusiness" name="business_name" type="text" required placeholder="Enter your solo practice or business name">
                        <p class="practitioner-form__hint">We'll use this as your business name on WOW.</p>
                    </div>
                    <div class="field-group">
                        <div class="practice-mode__legend">How do you currently hold sessions?</div>
                        <div class="practice-mode">
                            <label class="practice-mode__option">
                                <input type="checkbox" name="practice_online" value="1">
                                <span>Online</span>
                            </label>
                            <label class="practice-mode__option">
                                <input type="checkbox" name="practice_in_person" value="1">
                                <span>In-person</span>
                            </label>
                        </div>
                    </div>
                    <div class="field-group" id="wowPractitionerLocationGroup" hidden>
                        <label for="wowPractitionerLocation">Where do you host in-person? (general area)</label>
                        <input id="wowPractitionerLocation" name="in_person_locations" type="text" placeholder="e.g. Bristol, East Sussex or Hampstead">
                        <p class="practitioner-form__hint">Just provide the general location like county or town/city. We will not share this with anyone.</p>
                    </div>
                    <div id="wowPractitionerMessage" class="practitioner-form__message" role="alert" aria-live="polite" hidden></div>
                    <button type="submit" class="btn-wow btn-wow--primary btn-arrow practitioner-form__submit" data-loader-init="1">
                        <span class="btn-label">Send details</span>
                        <span class="btn-spinner" aria-hidden="true"><span class="spin"></span></span>
                    </button>
                </form>
            </div>
        </div>

        <script>
            (function(){
                if (typeof window === 'undefined' || typeof document === 'undefined') return;
                const header = document.getElementById('wow-header-container');
                if (!header) return;
                const spacer = document.getElementById('wow-header-offset-spacer');
                const root = document.documentElement;
                const applyOffset = () => {
                    const height = header.offsetHeight || 0;
                    const value = height + 'px';
                    if (root && root.style) {
                        root.style.setProperty('--wow-header-offset', value);
                    }
                    if (spacer) {
                        spacer.style.height = value;
                    }
                };
                const schedule = typeof window.requestAnimationFrame === 'function'
                    ? window.requestAnimationFrame.bind(window)
                    : (cb) => setTimeout(cb, 16);
                const updateOffset = () => schedule(applyOffset);
                updateOffset();
                if (typeof ResizeObserver !== 'undefined') {
                    try {
                        const observer = new ResizeObserver(updateOffset);
                        observer.observe(header);
                    } catch (_) {
                        window.addEventListener('resize', updateOffset);
                    }
                } else {
                    window.addEventListener('resize', updateOffset);
                }
                window.addEventListener('load', updateOffset);
            })();
        </script>

        <script>
            (function(){
                if (typeof window === 'undefined' || typeof document === 'undefined') return;
                const triggers = document.querySelectorAll('[data-mobile-search-trigger]');
                const modal = document.getElementById('mobile-search-drawer');
                const mobileMenu = document.getElementById('mobile-menu');
                const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
                const burger = document.querySelector('[data-wow-mobile-toggle]');
                const body = document.body;
                if (!triggers.length || !modal) return;
                let bodyOverflowBeforeSearch = '';
                const pathname = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
                const isHomePage = pathname === '/';
                const isSearchPage = pathname === '/search';
                const inlineSearchPrefix = isHomePage ? 'home-search-v4' : (isSearchPage ? 'search-v4' : '');

                const syncTriggerState = (isOpen) => {
                    triggers.forEach((button) => {
                        const label = button.querySelector('.mobile-nav-text-trigger__label');

                        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                        button.classList.toggle('is-open', isOpen);
                        button.setAttribute('aria-label', isOpen ? 'Close search' : 'Search');
                        if (label) label.textContent = isOpen ? 'Close search' : 'Search';
                    });
                };

                const syncBurgerState = (isOpen) => {
                    if (!burger) return;
                    burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    burger.setAttribute('aria-label', isOpen ? 'Close menu' : 'Menu');
                    burger.classList.toggle('is-open', isOpen);
                };

                const closeMobileMenu = () => {
                    if (!mobileMenu) return;
                    mobileMenu.style.display = 'none';
                    if (mobileMenuBackdrop) mobileMenuBackdrop.classList.remove('is-visible');
                    syncBurgerState(false);
                    try {
                        if (window.__WOWHamburger && typeof window.__WOWHamburger.set === 'function') {
                            window.__WOWHamburger.set(false);
                        }
                    } catch (_) {}
                };

                const focusInlineWhat = (restoreScroll = false) => {
                    if (!inlineSearchPrefix) return false;

                    const input = document.getElementById(`${inlineSearchPrefix}-what`);
                    if (input && typeof input.focus === 'function') {
                        const beforeY = restoreScroll ? (window.scrollY || window.pageYOffset || 0) : 0;
                        input.focus({ preventScroll: true });
                        if (restoreScroll) {
                            window.requestAnimationFrame(() => {
                                window.scrollTo(0, beforeY);
                            });
                        }
                        return true;
                    }

                    return false;
                };

                const openInlineSearch = () => {
                    if (!inlineSearchPrefix) return false;

                    try {
                        window.__WOWCloseMobileSearch?.();
                    } catch (_) {}

                    if (isHomePage) {
                        if (window.matchMedia && window.matchMedia('(max-width: 1040px)').matches) {
                            const beforeY = window.scrollY || window.pageYOffset || 0;
                            try {
                                const api = window.__WOWSearchBarV4?.[inlineSearchPrefix];
                                if (api?.open) {
                                    api.open('what');
                                    window.requestAnimationFrame(() => {
                                        window.scrollTo(0, beforeY);
                                    });
                                    return true;
                                }
                            } catch (_) {}

                            if (focusInlineWhat(true)) {
                                return true;
                            }
                            return true;
                        }

                        const root = document.querySelector('.wow-home-search-wrap') || document.getElementById(`${inlineSearchPrefix}-root`);
                        if (root && typeof root.scrollIntoView === 'function') {
                            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }

                        if (focusInlineWhat()) {
                            return true;
                        }

                        return true;
                    }

                    if (isSearchPage) {
                        if (focusInlineWhat()) {
                            return true;
                        }

                        try {
                            const api = window.__WOWSearchBarV4?.[inlineSearchPrefix];
                            if (api?.open) {
                                api.open('what');
                                return true;
                            }
                        } catch (_) {}
                    }

                    return focusInlineWhat();
                };

                const openSearch = () => {
                    if (modal.classList.contains('is-visible')) {
                        closeSearch();
                        return;
                    }
                    closeMobileMenu();
                    if (bodyOverflowBeforeSearch === '') {
                        bodyOverflowBeforeSearch = body.style.overflow || '';
                    }
                    body.style.overflow = 'hidden';
                    modal.classList.add('is-visible');
                    modal.setAttribute('aria-hidden', 'false');
                    syncTriggerState(true);
                    try {
                        window.__WOWSearchBarV4?.['header-search']?.open?.('what');
                    } catch (_) {}
                };

                const closeSearch = () => {
                    try {
                        window.__WOWSearchBarV4?.['header-search']?.close?.();
                    } catch (_) {}
                    modal.classList.remove('is-visible');
                    modal.setAttribute('aria-hidden', 'true');
                    body.style.overflow = bodyOverflowBeforeSearch;
                    bodyOverflowBeforeSearch = '';
                    syncTriggerState(false);
                };
                window.__WOWCloseMobileSearch = closeSearch;

                if (burger) {
                    burger.addEventListener('click', () => {
                        closeSearch();
                    }, true);
                }

                triggers.forEach((trigger) => {
                    trigger.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        if (inlineSearchPrefix) {
                            openInlineSearch();
                            return;
                        }
                        openSearch();
                    });
                });
                modal.addEventListener('click', (event) => {
                    if (event.target?.closest?.('[data-mobile-search-close]')) closeSearch();
                    if (event.target === modal) closeSearch();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal.classList.contains('is-visible')) {
                        closeSearch();
                    }
                });
            })();
        </script>

        <script>
            (function(){
                if (typeof window === 'undefined') return;
                const modal = document.getElementById('wowPractitionerModal');
                const form = document.getElementById('wowPractitionerForm');
                if (!modal || !form) return;
                const triggers = document.querySelectorAll('[data-practitioner-trigger]');
                if (!triggers.length) return;
                const closeTargets = modal.querySelectorAll('[data-practitioner-dismiss]');
                const locationGroup = document.getElementById('wowPractitionerLocationGroup');
                const locationInput = document.getElementById('wowPractitionerLocation');
                const inPersonCheckbox = form.querySelector('input[name="practice_in_person"]');
                const onlineCheckbox = form.querySelector('input[name="practice_online"]');
                const messageEl = document.getElementById('wowPractitionerMessage');
                const submitBtn = form.querySelector('button[type="submit"]');
                const body = document.body;
                const sessionTokenKey = 'wow_subscriber_session_token';
                const sessionStartKey = 'wow_subscriber_session_started_at';
                const overlay = modal.querySelector('.practitioner-modal__backdrop');
                let sessionStart = loadNumber(sessionStartKey);
                if (!sessionStart){
                    sessionStart = Date.now();
                    storeNumber(sessionStartKey, sessionStart);
                }

                const toggleLocation = () => {
                    if (!locationGroup) return;
                    const show = inPersonCheckbox?.checked;
                    locationGroup.hidden = !show;
                    if (!show && locationInput){
                        locationInput.value = '';
                    }
                };

                const updateModeClasses = () => {
                    form.querySelectorAll('.practice-mode__option').forEach(option => {
                        const input = option.querySelector('input[type="checkbox"]');
                        option.classList.toggle('is-active', !!input?.checked);
                    });
                };

                const openModal = () => {
                    modal.classList.add('is-visible');
                    modal.setAttribute('aria-hidden', 'false');
                    body.style.overflow = 'hidden';
                    setTimeout(() => {
                        const firstInput = form.querySelector('input[name="first_name"]');
                        firstInput?.focus();
                    }, 10);
                };

                const closeModal = () => {
                    modal.classList.remove('is-visible');
                    modal.setAttribute('aria-hidden', 'true');
                    body.style.overflow = '';
                    clearMessage();
                };

                const clearMessage = () => {
                    if (!messageEl) return;
                    messageEl.textContent = '';
                    messageEl.classList.remove('is-visible', 'is-success', 'is-error');
                    messageEl.hidden = true;
                };

                const showMessage = (text, isSuccess) => {
                    if (!messageEl) return;
                    messageEl.textContent = text;
                    messageEl.classList.add('is-visible');
                    messageEl.hidden = false;
                    messageEl.classList.toggle('is-success', !!isSuccess);
                    messageEl.classList.toggle('is-error', !isSuccess);
                };

                const loadToken = () => {
                    try { return sessionStorage.getItem(sessionTokenKey); } catch (_) { return null; }
                };

                const storeToken = (token) => {
                    if (!token) return;
                    try { sessionStorage.setItem(sessionTokenKey, token); } catch (_) {}
                };

                const collectMeta = () => {
                    const nav = typeof navigator !== 'undefined' ? navigator : {};
                    const scr = typeof window !== 'undefined' ? (window.screen || {}) : {};
                    let timezone = null;
                    try { timezone = Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (_) {}
                    const languages = Array.isArray(nav.languages) ? nav.languages.join(',') : (nav.language || null);
                    return {
                        timezone,
                        locale: nav.language || null,
                        languages,
                        platform: nav.platform || null,
                        user_agent: nav.userAgent || null,
                        device_memory: typeof nav.deviceMemory === 'number' ? String(nav.deviceMemory) : null,
                        hardware_concurrency: typeof nav.hardwareConcurrency === 'number' ? String(nav.hardwareConcurrency) : null,
                        screen_width: scr.width || null,
                        screen_height: scr.height || null,
                    };
                };

                const durationSeconds = () => {
                    return Math.max(0, Math.round((Date.now() - (sessionStart || Date.now())) / 1000));
                };

                triggers.forEach(btn => {
                    btn.addEventListener('click', (event) => {
                        event.preventDefault();
                        clearMessage();
                        openModal();
                    });
                });

                closeTargets.forEach(btn => btn.addEventListener('click', closeModal));
                overlay?.addEventListener('click', closeModal);

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal.classList.contains('is-visible')) {
                        closeModal();
                    }
                });

                inPersonCheckbox?.addEventListener('change', () => {
                    toggleLocation();
                    updateModeClasses();
                });
                form.querySelectorAll('.practice-mode__option input').forEach(input => {
                    input.addEventListener('change', updateModeClasses);
                });
                toggleLocation();
                updateModeClasses();

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    clearMessage();
                    if (!form.reportValidity()) return;
                    const formData = new FormData(form);
                    const online = !!onlineCheckbox?.checked;
                    const inPerson = !!inPersonCheckbox?.checked;
                    if (!online && !inPerson) {
                        showMessage('Select online, in-person or both.', false);
                        return;
                    }
                    let locationValue = '';
                    if (inPerson && locationInput) {
                        locationValue = locationInput.value.trim();
                        if (!locationValue) {
                            showMessage('Share a general in-person location.', false);
                            locationInput.focus();
                            return;
                        }
                    }

                    const firstName = (formData.get('first_name') || '').toString().trim();
                    const lastName = (formData.get('last_name') || '').toString().trim();
                    const email = (formData.get('email') || '').toString().trim();
                    const business = (formData.get('business_name') || '').toString().trim();
                    const name = [firstName, lastName].filter(Boolean).join(' ').trim();
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    const payload = Object.assign({
                        practitioner_interest: true,
                        first_name: firstName,
                        last_name: lastName,
                        name: name || null,
                        email,
                        business_name: business || null,
                        offers_online: online,
                        offers_in_person: inPerson,
                        in_person_locations: inPerson ? locationValue : null,
                        landing_path: 'header:become-wow-practitioner',
                        referrer: document.referrer ? document.referrer.substring(0, 2048) : null,
                        session_token: loadToken(),
                        session_started_at: new Date(sessionStart || Date.now()).toISOString(),
                        session_duration_seconds: durationSeconds(),
                    }, collectMeta());

                    submitBtn.disabled = true;
                    submitBtn.classList.add('is-loading');
                    submitBtn.setAttribute('aria-busy', 'true');
                    try {
                        const response = await fetch('/api/v3-subscribers', {
                            method: 'POST',
                            headers: Object.assign({
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            }, csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                            credentials: 'same-origin',
                            body: JSON.stringify(payload),
                        });
                        const bodyJson = await response.json().catch(() => ({}));
                        if (!response.ok || bodyJson.error) {
                            const message = extractError(bodyJson) || 'Something went wrong. Please try again in a moment.';
                            throw new Error(message);
                        }
                        if (bodyJson.session_token) {
                            storeToken(bodyJson.session_token);
                        }
                        form.reset();
                        toggleLocation();
                        updateModeClasses();
                        const successMessage = bodyJson.message || 'Check your email to confirm your subscription.';
                        showMessage(successMessage, true);
                    } catch (error) {
                        showMessage(error.message || 'Something went wrong. Please try again.', false);
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('is-loading');
                        submitBtn.removeAttribute('aria-busy');
                    }
                });

                function extractError(body){
                    if (!body) return null;
                    if (body.errors) {
                        const first = Object.values(body.errors)[0];
                        if (Array.isArray(first) && first.length) {
                            return first[0];
                        }
                    }
                    return body.error || null;
                }

                function loadNumber(key){
                    try {
                        const raw = sessionStorage.getItem(key);
                        return raw ? Number(raw) : null;
                    } catch (_) {
                        return null;
                    }
                }

                function storeNumber(key, value){
                    try { sessionStorage.setItem(key, String(value)); } catch (_) {}
                }
            })();
        </script>
