@extends('layouts.app')

@php
    $seo = [
        'title' => 'Holistic Therapy That Works | We Offer Wellness®',
        'description' => 'Holistic therapy, classes, workshops and retreats from trusted practitioners across the UK, online and in person, with live availability and local options.',
        'keywords' => [
            'We Offer Wellness',
            'WOW',
            'holistic therapy',
            'classes',
            'workshops',
            'events',
            'retreats',
            'reiki',
            'sound healing',
            'breathwork',
            'massage',
            'wellness marketplace',
        ],
        'twitter_title' => 'Holistic Therapy That Works | Events & Classes | We Offer Wellness®',
        'twitter_description' => 'Holistic therapy, classes, workshops and retreats from trusted practitioners across the UK, online and in person, with live availability, local options and booking paths.',
    ];
@endphp

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --wow-ink: #101828;
        --wow-text: #344054;
        --wow-muted: #667085;
        --wow-line: #dfe4ea;
        --wow-soft-line: #edf0f2;
        --wow-green: #4f9381;
        --wow-green-dark: #417c6d;
        --wow-green-soft: #e8f5f1;
        --wow-blue-soft: #e8f0ff;
        --wow-blue-text: #254a85;
        --wow-gold-soft: #ffe5b3;
        --wow-gold-text: #6f4b10;
        --wow-bg: #ffffff;
        --wow-soft-bg: #f8fafc;
        --wow-dark: #101828;
        --wow-serif: "Playfair Display", Georgia, "Times New Roman", serif;
        --wow-sans: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #fff;
        color: var(--wow-ink);
        font-family: var(--wow-sans);
    }

    a {
        color: inherit;
    }

    .wow-section-wrap {
        position: relative;
        overflow: hidden;
        background: #fff;
        padding: 68px 0 82px;
    }

    .wow-page-grid {
        position: absolute;
        inset: 0;
        width: min(100% - 40px, 1280px);
        margin: 0 auto;
        pointer-events: none;
        border-left: 1px solid rgba(17, 24, 39, 0.08);
        border-right: 1px solid rgba(17, 24, 39, 0.08);
    }

    .wow-grid-line {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 1px;
        border-left: 1px dashed rgba(17, 24, 39, 0.14);
    }

    .wow-grid-line--one { left: 25%; }
    .wow-grid-line--two { left: 50%; }
    .wow-grid-line--three { left: 75%; }

    .wow-container {
        position: relative;
        z-index: 1;
        width: min(100% - 40px, 1280px);
        margin: 0 auto;
    }

    .section,
    .wow-discovery-section,
    #mindful-times.wow-mindful-times-section {
        margin-bottom: 64px;
    }

    @media (max-width: 767.98px) {
        .section,
        .wow-discovery-section,
        #mindful-times.wow-mindful-times-section {
            margin-bottom: 0;
        }
    }

    .container > section {
        margin-bottom: 64px;
    }

    .container > section:last-child {
        margin-bottom: 0;
    }

    .wow-kicker {
        margin: 0 0 10px;
        color: #344054;
        font-size: 13px !important;
        font-weight: 300;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .wow-safe-card h2,
    .wow-approach-copy h2,
    .wow-chat-panel h2,
    .wow-principle-card h3,
    .wow-mini-card h3 {
        margin: 0;
        color: var(--wow-ink);
        font-family: var(--wow-serif);
        font-weight: 500;
        letter-spacing: -0.055em;
    }

    .wow-btn {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        padding: 0 22px;
        font-size: 15px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: background 160ms ease, border-color 160ms ease, color 160ms ease;
    }

    .wow-btn-primary {
        border: 1px solid #2f6f60;
        background: #2f6f60;
        color: #fff;
    }

    .wow-btn-primary:hover {
        background: #214f44;
        border-color: #214f44;
        color: #fff;
    }

    .wow-btn-outline {
        border: 1px solid #d0d5dd;
        background: #fff;
        color: #111827;
    }

    .wow-btn-outline:hover {
        border-color: var(--wow-green);
        color: var(--wow-green);
    }

    .wow-tag {
        min-height: 30px;
        display: inline-flex;
        align-items: center;
        padding: 0 10px;
        border: 1px solid #f0c879;
        border-radius: 4px;
        background: var(--wow-gold-soft);
        color: var(--wow-gold-text);
        font-size: 12px;
        font-weight: 500;
    }

    .wow-tag--green {
        border-color: rgba(79, 147, 129, 0.28);
        background: var(--wow-green-soft);
        color: #2f6f60;
    }

    .wow-tag--blue {
        border-color: #c7d8fb;
        background: var(--wow-blue-soft);
        color: var(--wow-blue-text);
    }

    .wow-safe-card {
        display: grid;
        grid-template-columns: minmax(0, 0.86fr) minmax(380px, 1.14fr);
        gap: 28px;
        align-items: stretch;
        margin-bottom: 76px;
        background: rgba(255, 255, 255, 0.98);
        border: 1px solid var(--wow-line);
        border-radius: 18px;
        box-shadow: 0 18px 54px rgba(16, 24, 40, 0.07);
    }

    .wow-safe-intro {
        padding: 30px 28px;
    }

    .wow-safe-card h2 {
        max-width: 520px;
        font-size: clamp(34px, 4vw, 54px);
        line-height: 0.98;
    }

    .wow-safe-card p {
        max-width: 560px;
        margin: 16px 0 0;
        color: #596275;
        font-size: 16px;
        line-height: 1.58;
    }

    .wow-safe-checks {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 24px;
    }

    .wow-safe-proof {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        padding: 18px;
        border-left: 1px solid var(--wow-soft-line);
    }

    .wow-score-card,
    .wow-proof-list {
        background: #fff;
        border: 1px solid var(--wow-soft-line);
        border-radius: 14px;
        box-shadow: 0 12px 32px rgba(16, 24, 40, 0.04);
    }

    .wow-score-card {
        min-height: 142px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 18px;
    }

    .wow-score-heading {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .wow-score-icon {
        width: 28px;
        height: 28px;
        flex: 0 0 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: var(--wow-green-soft);
        color: var(--wow-green);
        font-size: 15px;
        line-height: 1;
        font-weight: 800;
    }

    .wow-score-icon--dot {
        font-size: 16px;
    }

    .wow-score-icon--count {
        background: var(--wow-blue-soft);
        color: var(--wow-blue-text);
    }

    .wow-score-card strong {
        display: block;
        color: var(--wow-ink);
        font-size: 30px;
        line-height: 1;
        letter-spacing: -0.04em;
    }

    .wow-score-card > span {
        display: block;
        margin-top: 8px;
        color: var(--wow-muted);
        font-size: 13px;
        line-height: 1.35;
    }

    .wow-proof-list {
        grid-column: 1 / -1;
        padding: 4px 18px;
    }

    .wow-proof-row {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 12px;
        align-items: start;
        padding: 14px 0;
        border-bottom: 1px solid var(--wow-soft-line);
    }

    .wow-proof-row:last-child {
        border-bottom: 0;
    }

    .wow-proof-tick {
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: var(--wow-green-soft);
        color: var(--wow-green);
        font-size: 13px;
        font-weight: 800;
    }

    .wow-proof-row strong {
        display: block;
        color: var(--wow-ink);
        font-size: 15px;
        line-height: 1.3;
    }

    .wow-proof-row span:not(.wow-proof-tick) {
        display: block;
        margin-top: 4px;
        color: var(--wow-muted);
        font-size: 13px;
        line-height: 1.45;
    }

    .wow-approach-section {
        display: grid;
        grid-template-columns: minmax(0, 0.86fr) minmax(420px, 1.14fr);
        gap: 34px;
        align-items: start;
        margin-bottom: 28px;
    }

    .wow-approach-copy {
        position: sticky;
        top: 26px;
        padding-top: 6px;
    }

    .wow-approach-copy h2 {
        max-width: 620px;
        font-size: clamp(46px, 5.8vw, 78px);
        line-height: 0.94;
    }

    .wow-approach-copy p {
        max-width: 620px;
        margin: 18px 0 0;
        color: #596275;
        font-size: 13px;
        line-height: 1.6;
    }

    .wow-approach-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 26px;
    }

    .wow-principles {
        display: grid;
        gap: 14px;
    }

    .wow-principle-card {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 18px;
        background: rgba(255, 255, 255, 0.98);
        border: 1px solid var(--wow-line);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 12px 34px rgba(16, 24, 40, 0.035);
    }

    .wow-principle-number {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(47, 111, 96, 0.34);
        color: #24594d;
        font-size: 14px;
        font-weight: 800;
    }

    .wow-principle-card h3 {
        font-size: 28px;
        line-height: 1;
    }

    .wow-principle-card p {
        margin: 9px 0 0;
        color: #596275;
        font-size: 15px;
        line-height: 1.55;
    }

    .wow-chat-panel {
        display: grid;
        grid-template-columns: minmax(0, 0.9fr) minmax(380px, 1.1fr);
        gap: 28px;
        align-items: center;
        margin-top: 74px;
        margin-bottom: 50px;
        background: var(--wow-dark);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 18px;
        padding: 30px;
        box-shadow: none;
    }

    .wow-chat-panel .wow-kicker {
        color: rgba(255, 255, 255, 0.72);
    }

    .wow-chat-panel h2 {
        max-width: 560px;
        color: #fff;
        font-size: clamp(42px, 5vw, 68px);
        line-height: 0.96;
    }

    .wow-chat-panel p {
        max-width: 620px;
        margin: 16px 0 0;
        color: rgba(255, 255, 255, 0.76);
        font-size: 16px;
        line-height: 1.6;
    }

    .wow-chat-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 26px;
    }

    .wow-chat-panel .wow-btn-outline {
        border-color: rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
    }

    .wow-chat-panel .wow-btn-outline:hover {
        border-color: #fff;
        color: #fff;
    }

    .wow-chat-preview {
        display: grid;
        gap: 12px;
    }

    .wow-mini-card {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 14px;
        align-items: center;
        background: rgba(255, 255, 255, 0.10);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 14px;
        padding: 14px;
        text-decoration: none;
        transition: border-color 160ms ease, background 160ms ease;
    }

    .wow-mini-card:hover {
        background: rgba(255, 255, 255, 0.14);
        border-color: rgba(255, 255, 255, 0.28);
    }

    .wow-mini-avatar {
        width: 50px;
        height: 50px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
        position: relative;
    }

    .wow-mini-avatar::after {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        color: #fff;
        font-size: 18px;
        font-weight: 800;
        line-height: 1;
    }

    .wow-mini-avatar--article::after {
        content: "T";
    }

    .wow-mini-avatar--video::after {
        content: "▶";
        font-size: 15px;
    }

    .wow-mini-avatar--guide::after {
        content: "✓";
        color: var(--wow-green);
    }

    .wow-mini-card h3 {
        color: #fff;
        font-family: var(--wow-sans);
        font-size: 15px;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .wow-mini-card p {
        margin: 4px 0 0;
        color: rgba(255, 255, 255, 0.66);
        font-size: 13px;
        line-height: 1.35;
    }

    .wow-mini-time {
        min-height: 30px;
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0 10px;
        background: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.82);
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .wow-discovery-section {
        position: relative;
        overflow: hidden;
        background: transparent;
        padding: 0;
        margin-bottom: 0;
    }

    .wow-discovery-section--gift {
        padding-top: 0;
        margin-bottom: 64px;
    }

    .home-latest-section {
        padding-top: 30px;
        padding-bottom: 34px;
        border-top: 1px solid var(--wow-soft-line);
        border-bottom: 0;
    }

    .home-latest-section .product-showcase-heading__copy {
        max-width: 820px;
    }

    .wow-section-heading {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 24px;
        align-items: end;
        margin-bottom: 28px;
    }

    .wow-section-heading h2,
    .wow-gift-copy h2,
    .wow-gift-card-preview h3 {
        margin: 0;
        color: var(--wow-ink);
        font-family: var(--wow-serif);
        font-weight: 500;
        letter-spacing: -0.055em;
    }

    .wow-section-heading h2 {
        max-width: 760px;
        font-size: clamp(44px, 5.6vw, 72px);
        line-height: 0.94;
    }

    .wow-section-heading p {
        max-width: 650px;
        margin: 16px 0 0;
        color: #596275;
        font-size: 17px;
        line-height: 1.58;
    }

    .wow-gift-panel {
        display: grid;
        grid-template-columns: minmax(0, 0.8fr) minmax(380px, 1.2fr);
        gap: 30px;
        align-items: stretch;
        border-radius: 18px;
        padding: 26px;
    }

    .wow-gift-copy {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 340px;
    }

    .wow-gift-copy h2 {
        max-width: 500px;
        font-size: clamp(38px, 4.8vw, 60px);
        line-height: 0.96;
    }

    .wow-gift-copy p {
        max-width: 470px;
        margin: 16px 0 0;
        color: #596275;
        font-size: 16px;
        line-height: 1.58;
    }

    .wow-gift-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 24px;
    }

    .wow-gift-points {
        display: grid;
        gap: 10px;
        margin-top: 24px;
    }

    .wow-gift-points span {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        color: #344054;
        font-size: 14px;
        line-height: 1.45;
    }

    .wow-gift-points span::before {
        content: "✓";
        width: 22px;
        height: 22px;
        flex: 0 0 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: var(--wow-green-soft);
        color: var(--wow-green);
        font-size: 12px;
        font-weight: 800;
    }

    .wow-gift-visual {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 340px;
        background: #f8fafc;
        border: 1px solid var(--wow-soft-line);
        border-radius: 16px;
        padding: 24px;
    }

    .wow-gift-card-preview {
        width: min(100%, 560px);
        background: #fff;
        border: 1px solid var(--wow-line);
        border-radius: 18px;
        box-shadow: 0 20px 56px rgba(16, 24, 40, 0.10);
        overflow: hidden;
    }

    .wow-gift-card-preview__top {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        padding: 22px;
        border-bottom: 1px solid var(--wow-soft-line);
    }

    .wow-gift-card-preview__brand {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--wow-ink);
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .wow-gift-mark {
        width: 26px;
        height: 26px;
        border-radius: 999px;
        background: var(--wow-green);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        font-weight: 800;
    }

    .wow-gift-card-preview__amount {
        text-align: right;
    }

    .wow-gift-card-preview__amount small {
        display: block;
        color: var(--wow-muted);
        font-size: 12px;
        line-height: 1.2;
    }

    .wow-gift-card-preview__amount strong {
        display: block;
        margin-top: 4px;
        color: var(--wow-ink);
        font-size: 34px;
        line-height: 1;
        letter-spacing: -0.055em;
    }

    .wow-gift-card-preview__body {
        padding: 22px;
    }

    .wow-gift-card-preview h3 {
        font-size: 36px;
        line-height: 0.98;
    }

    .wow-gift-card-preview p {
        margin: 12px 0 0;
        color: var(--wow-muted);
        font-size: 14px;
        line-height: 1.5;
    }

    .wow-gift-amounts {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 18px;
    }

    .wow-gift-amounts span {
        min-height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #d0d5dd;
        border-radius: 999px;
        background: #fff;
        color: #344054;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 700;
    }

    .wow-gift-amounts span.is-active {
        border-color: var(--wow-green);
        background: var(--wow-green);
        color: #fff;
    }

    .wow-gift-card-preview__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 22px;
        background: #f8fafc;
        border-top: 1px solid var(--wow-soft-line);
        color: var(--wow-muted);
        font-size: 13px;
    }

    .wow-gift-card-preview__footer strong {
        color: #24594d;
    }

    .wow-gift-panel {
        position: relative;
        overflow: hidden;
        grid-template-columns: minmax(0, .9fr) minmax(420px, 1.1fr);
        gap: 42px;
        border: 0;
        border-radius: 22px;
        background: #183d34;
        box-shadow: 0 24px 70px rgba(24, 61, 52, .18);
        padding: 12px;
    }

    .wow-gift-copy {
        position: relative;
        z-index: 1;
        min-height: 390px;
        padding: 34px 24px 34px 30px;
    }

    .wow-gift-copy h2,
    .wow-gift-copy p,
    .wow-gift-copy .wow-kicker {
        color: #fff;
    }

    .wow-gift-copy h2 { max-width: 560px; font-size: clamp(42px, 5vw, 68px); }
    .wow-gift-copy p { color: rgba(255,255,255,.76); }
    .wow-gift-points span { color: rgba(255,255,255,.84); }
    .wow-gift-points span::before { background: rgba(220,235,228,.18); color: #dcebe4; }

    .wow-gift-visual {
        min-height: 390px;
        border: 0;
        border-radius: 16px;
        background: #dcebe4;
        padding: 28px;
        transform: rotate(1.2deg);
    }

    .wow-gift-card-preview {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 24px 48px rgba(24,61,52,.2);
        transform: rotate(-1.2deg);
    }

    .wow-gift-card-preview__body { padding: 26px; }
    .wow-gift-card-preview h3 { font-size: clamp(30px, 3.5vw, 46px); }
    .wow-gift-card-preview__footer { background: #f4f8f6; }

    @media (max-width: 980px) {
        .wow-gift-copy { min-height: 0; padding: 28px 22px 16px; }
        .wow-gift-visual { min-height: 340px; transform: none; }
        .wow-gift-card-preview { transform: none; }
    }

    @media (max-width: 980px) {
        .wow-safe-card,
        .wow-approach-section,
        .wow-chat-panel,
        .wow-gift-panel {
            grid-template-columns: 1fr;
        }

        .wow-safe-proof {
            border-left: 0;
            border-top: 1px solid var(--wow-soft-line);
        }

        .wow-approach-copy {
            position: static;
        }

    }

    @media (max-width: 700px) {
        .wow-section-wrap {
            padding: 42px 0 58px;
        }

        .wow-section-heading {
            display: block !important;
            grid-template-columns: none !important;
        }

        .wow-safe-proof {
            grid-template-columns: 1fr;
        }

        .wow-proof-list {
            grid-column: auto;
        }

        .wow-chat-panel,
        .wow-safe-intro {
            padding: 22px;
        }

        .wow-principle-card,
        .wow-mini-card {
            grid-template-columns: 1fr;
        }

        .wow-mini-time {
            width: fit-content;
        }

    }

    @media (max-width: 560px) {
        .wow-container,
        .wow-page-grid {
            width: min(100% - 28px, 1280px);
        }

        .wow-safe-card h2,
        .wow-chat-panel h2 {
            font-size: 42px;
        }

        .wow-approach-copy h2 {
            font-size: 48px;
        }

        .wow-btn {
            width: 100%;
        }

        .wow-gift-panel,
        .wow-gift-card-preview__top,
        .wow-gift-card-preview__body,
        .wow-gift-card-preview__footer {
            padding: 20px;
        }

        .wow-gift-copy h2 {
            font-size: 42px;
        }

        .wow-gift-visual {
            min-height: auto;
            padding: 14px;
        }

        .wow-gift-card-preview__top,
        .wow-gift-card-preview__footer {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }

        .wow-gift-card-preview__amount {
            text-align: left;
        }
    }

    @media (max-width: 767.98px) {
        .wow-section-wrap {
            --wow-green: #2f6f60;
            --wow-green-dark: #24594d;
        }

        .wow-gift-actions .btn-wow--primary,
        .wow-chat-actions .wow-btn-primary,
        .wow-approach-actions .wow-btn-primary {
            background: #2f6f60;
            border-color: #2f6f60;
            color: #fff;
        }

        .wow-gift-actions .btn-wow--primary:hover,
        .wow-chat-actions .wow-btn-primary:hover,
        .wow-approach-actions .wow-btn-primary:hover {
            background: #214f44;
            border-color: #214f44;
            color: #fff;
        }

        .wow-gift-amounts span.is-active {
            background: var(--wow-green-dark);
            border-color: var(--wow-green-dark);
            color: #fff;
        }

        #mindful-times .wow-news-pill--red {
            background: #f6d5dc;
            color: #8f1532;
            border: 1px solid rgba(143, 21, 50, 0.16);
        }
    }
</style>
@endpush

@section('content')

@include('home.sections.hero-slider')

@include('home.sections.popular_searches')

@if (!empty($hasClassesThisWeek))
@include('home.sections.schedule')
@endif

@include('home.sections.latest_catalogue')

@include('home.sections.discover_category')

@include('home.sections.gift_cards_occasion')

@include('home.sections.no-travel-needed')

@include('home.sections.gifts')

<div class="container">
    @include('home.sections.trust-feel-safe')
</div>

@include('home.sections.mindfultimes_guides_interviews')

<div class="container">
    @include('home.sections.practitioner_chats_converstions')

    @include('home.sections.our_approach')
</div>

@endsection
