@extends('layouts.base')

@section('html-lang', 'en')

@section('document-head')
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title>Santa Tracker 2026 | We Offer Wellness</title>
<meta name="description" content="Track Santa around the world with the We Offer Wellness Santa Tracker. Preview the route now and follow Christmas Eve live.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="{{ url('/santa-tracker') }}">
<link rel="icon" type="image/png" sizes="180x180" href="{{ asset('images/santa-tracker/santa-tracker-favicon.png') }}?v=285ab04e">
<link rel="shortcut icon" type="image/png" href="{{ asset('images/santa-tracker/santa-tracker-favicon.png') }}?v=285ab04e">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/santa-tracker/santa-tracker-favicon.png') }}?v=285ab04e">
<meta name="theme-color" content="#1769e0">
<style>
  :root {
    --santa-blue: #1769e0;
    --santa-blue-dark: #0e47a1;
    --santa-red: #d93025;
    --santa-green: #188038;
    --santa-yellow: #f9ab00;
    --santa-ink: #202124;
    --santa-muted: #5f6368;
    --santa-panel: #ffffff;
    --santa-border: rgba(32, 33, 36, .12);
    --santa-shadow: 0 10px 30px rgba(32, 33, 36, .18);
    --safe-bottom: env(safe-area-inset-bottom, 0px);
    --safe-top: env(safe-area-inset-top, 0px);
  }

  * { box-sizing: border-box; }
  html, body {
    margin: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #123b74;
    overscroll-behavior: none;
    touch-action: manipulation;
    -webkit-text-size-adjust: 100%;
  }
  body { font-family: Arial, Helvetica, sans-serif; color: var(--santa-ink); }
  button, input { font: inherit; }

  /* The document itself must never browser-zoom. Mapbox owns gestures inside
     the map surface, while cards/buttons remain ordinary single-tap UI. */
  .santa-app { touch-action: manipulation; }
  #santaMap,
  #santaMap .mapboxgl-canvas-container,
  #santaMap .mapboxgl-canvas { touch-action: none !important; }

  .santa-app {
    position: fixed;
    inset: 0;
    width: auto;
    height: auto;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
    background: #123b74;
    overscroll-behavior: none;
  }

  #santaMap {
    position: fixed;
    inset: 0;
    z-index: 1;
    width: auto !important;
    height: auto !important;
    min-width: 0;
    min-height: 0;
  }

  #santaMap .mapboxgl-canvas-container,
  #santaMap .mapboxgl-canvas {
    width: 100% !important;
    height: 100% !important;
  }

  .santa-map-tint {
    position: fixed;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    background: rgba(14, 49, 98, .08);
  }

  #snowCanvas {
    position: fixed;
    inset: 0;
    z-index: 8;
    width: 100% !important;
    height: 100% !important;
    pointer-events: none;
  }

  .santa-topbar {
    position: absolute;
    top: calc(14px + var(--safe-top));
    left: 14px;
    right: 14px;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    pointer-events: none;
  }

  .santa-brand,
  .santa-top-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    pointer-events: auto;
  }

  .santa-brand-card {
    display: flex;
    align-items: center;
    min-height: 52px;
    gap: 11px;
    padding: 8px 13px 8px 9px;
    background: #fff;
    border: 1px solid rgba(255,255,255,.75);
    border-radius: 18px;
    box-shadow: var(--santa-shadow);
  }

  .santa-brand-mark {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--santa-red);
    color: #fff;
    font-size: 21px;
    box-shadow: inset 0 0 0 2px rgba(255,255,255,.4);
  }

  .santa-brand-mark img,
  .santa-avatar img {
    display: block;
    width: 100%;
    height: 100%;
    border-radius: inherit;
    object-fit: cover;
  }

  .santa-brand-copy { line-height: 1.05; }
  .santa-brand-copy strong { display: block; font-size: 15px; font-weight: 700; letter-spacing: -.01em; }
  .santa-brand-copy span { display: block; margin-top: 3px; color: var(--santa-muted); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }

  .santa-icon-btn,
  .santa-chip,
  .santa-primary-btn {
    border: 0;
    cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
  }

  .santa-icon-btn:hover,
  .santa-chip:hover,
  .santa-primary-btn:hover { transform: translateY(-1px); }

  .santa-icon-btn {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #fff;
    color: var(--santa-ink);
    box-shadow: var(--santa-shadow);
    font-size: 20px;
  }

  .santa-chip {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 13px;
    border-radius: 22px;
    background: #fff;
    color: var(--santa-ink);
    box-shadow: var(--santa-shadow);
    font-size: 13px;
    font-weight: 700;
  }

  .santa-chip-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--santa-green);
    box-shadow: 0 0 0 4px rgba(24,128,56,.13);
  }

  .santa-chip.is-preview .santa-chip-dot { background: var(--santa-yellow); box-shadow: 0 0 0 4px rgba(249,171,0,.16); }

  .santa-side {
    position: absolute;
    top: calc(82px + var(--safe-top));
    right: 14px;
    bottom: 190px;
    z-index: 16;
    width: min(340px, calc(100vw - 28px));
    display: flex;
    flex-direction: column;
    background: rgba(255,255,255,.96);
    border: 1px solid rgba(255,255,255,.8);
    border-radius: 22px;
    box-shadow: var(--santa-shadow);
    overflow: hidden;
    transition: transform .24s ease, opacity .24s ease;
  }

  .santa-side.is-hidden {
    transform: translateX(calc(100% + 30px));
    opacity: 0;
    pointer-events: none;
  }

  .santa-side-head {
    padding: 18px 18px 13px;
    border-bottom: 1px solid var(--santa-border);
    background: #fff;
  }

  .santa-side-kicker {
    color: var(--santa-blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
  }

  .santa-side-title {
    margin: 5px 0 0;
    font-size: 22px;
    line-height: 1.06;
    letter-spacing: -.02em;
  }

  .santa-route-list {
    flex: 1;
    overflow: auto;
    padding: 8px 0 14px;
    scrollbar-width: thin;
  }

  .santa-stop {
    width: 100%;
    display: grid;
    grid-template-columns: 32px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 10px 16px;
    border: 0;
    background: transparent;
    text-align: left;
    cursor: pointer;
  }

  .santa-stop:hover { background: #f8f9fa; }
  .santa-stop.is-current { background: #e8f0fe; }
  .santa-stop.is-past { opacity: .62; }

  .santa-stop-icon {
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #eef3fb;
    font-size: 14px;
  }
  .santa-stop.is-current .santa-stop-icon { background: var(--santa-red); color: #fff; }
  .santa-stop.is-past .santa-stop-icon { background: #e6f4ea; color: var(--santa-green); }

  .santa-stop-main strong { display: block; font-size: 13px; line-height: 1.2; }
  .santa-stop-main span { display: block; margin-top: 2px; color: var(--santa-muted); font-size: 11px; }
  .santa-stop-time { color: var(--santa-muted); font-size: 11px; white-space: nowrap; }

  .santa-bottom {
    position: absolute;
    z-index: 18;
    left: 14px;
    right: 14px;
    bottom: calc(14px + var(--safe-bottom));
    display: grid;
    grid-template-columns: minmax(280px, 1.2fr) minmax(240px, .9fr) minmax(260px, 1fr);
    min-height: 150px;
    background: rgba(255,255,255,.97);
    border: 1px solid rgba(255,255,255,.8);
    border-radius: 24px;
    box-shadow: 0 14px 42px rgba(11, 43, 83, .28);
    overflow: hidden;
  }

  .santa-panel {
    position: relative;
    padding: 19px 20px;
    border-right: 1px solid var(--santa-border);
  }
  .santa-panel:last-child { border-right: 0; }

  .santa-panel-label {
    color: var(--santa-muted);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
  }

  .santa-location {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-top: 9px;
  }

  .santa-avatar {
    width: 56px;
    height: 56px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--santa-red);
    color: #fff;
    font-size: 30px;
  }

  .santa-location h1,
  .santa-next h2 {
    margin: 0;
    letter-spacing: -.03em;
  }

  .santa-location h1 { font-size: clamp(24px, 2.2vw, 35px); line-height: .98; }
  .santa-location p { margin: 6px 0 0; color: var(--santa-muted); font-size: 13px; }

  .santa-progress {
    margin-top: 14px;
    height: 5px;
    overflow: hidden;
    border-radius: 5px;
    background: #e8eaed;
  }
  .santa-progress > span {
    display: block;
    width: 0;
    height: 100%;
    border-radius: inherit;
    background: var(--santa-blue);
    transition: width .4s ease;
  }

  .santa-gifts-number {
    margin-top: 10px;
    font-size: clamp(31px, 3vw, 46px);
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.04em;
    color: var(--santa-blue);
    font-variant-numeric: tabular-nums;
  }

  .santa-gifts-copy {
    margin-top: 7px;
    color: var(--santa-muted);
    font-size: 12px;
  }

  .santa-stats {
    display: flex;
    gap: 16px;
    margin-top: 13px;
  }
  .santa-stat strong { display: block; font-size: 13px; }
  .santa-stat span { display: block; margin-top: 1px; color: var(--santa-muted); font-size: 10px; text-transform: uppercase; letter-spacing: .06em; }

  .santa-next h2 {
    margin-top: 7px;
    font-size: clamp(22px, 2.2vw, 31px);
    line-height: 1;
  }

  .santa-next-meta {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 8px;
    color: var(--santa-muted);
    font-size: 12px;
  }

  .santa-primary-btn {
    margin-top: 14px;
    min-height: 38px;
    padding: 0 16px;
    border-radius: 20px;
    background: var(--santa-blue);
    color: #fff;
    font-weight: 700;
    font-size: 12px;
    box-shadow: 0 5px 15px rgba(23,105,224,.22);
  }

  .santa-countdown-card {
    position: absolute;
    z-index: 15;
    left: 14px;
    top: calc(82px + var(--safe-top));
    width: min(290px, calc(100vw - 28px));
    padding: 15px 16px;
    border-radius: 18px;
    background: #fff;
    box-shadow: var(--santa-shadow);
  }

  .santa-countdown-card strong {
    display: block;
    margin-top: 4px;
    font-size: 22px;
    letter-spacing: -.02em;
    font-variant-numeric: tabular-nums;
  }

  .santa-countdown-card p {
    margin: 5px 0 0;
    color: var(--santa-muted);
    font-size: 11px;
    line-height: 1.35;
  }

  .santa-icon-btn.is-kindness {
    color: #c5221f;
  }

  .santa-icon-btn.is-kindness[aria-expanded="true"] {
    background: #fce8e6;
    color: #b31412;
  }

  .santa-kindness-prompt {
    position: absolute;
    z-index: 15;
    top: calc(188px + var(--safe-top));
    left: 14px;
    width: min(290px, calc(100vw - 28px));
    padding: 12px 14px;
    border: 0;
    border-radius: 18px;
    background: rgba(255,255,255,.97);
    color: var(--santa-ink);
    box-shadow: var(--santa-shadow);
    text-align: left;
    cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease;
  }

  .santa-kindness-prompt:hover {
    transform: translateY(-1px);
  }

  .santa-kindness-prompt-label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #b31412;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .09em;
    text-transform: uppercase;
  }

  .santa-kindness-prompt strong {
    display: block;
    margin-top: 5px;
    font-size: 13px;
    line-height: 1.28;
  }

  .santa-kindness-prompt small {
    display: block;
    margin-top: 5px;
    color: var(--santa-muted);
    font-size: 10px;
    font-weight: 700;
  }

  .santa-kindness-backdrop {
    position: absolute;
    inset: 0;
    z-index: 29;
    background: rgba(11, 18, 28, .34);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s ease;
  }

  .santa-kindness-backdrop.is-visible {
    opacity: 1;
    pointer-events: auto;
  }

  .santa-kindness-panel {
    position: absolute;
    z-index: 30;
    top: calc(82px + var(--safe-top));
    right: 14px;
    bottom: calc(190px + var(--safe-bottom));
    width: min(410px, calc(100vw - 28px));
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.86);
    border-radius: 22px;
    background: rgba(255,255,255,.985);
    box-shadow: 0 18px 60px rgba(11,43,83,.32);
    transform: none;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
  }

  .santa-kindness-panel.is-open {
    transform: none;
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  .santa-kindness-head {
    position: relative;
    flex: 0 0 auto;
    padding: 20px 54px 17px 20px;
    border-bottom: 1px solid var(--santa-border);
    background: linear-gradient(180deg, #fff7f4 0%, #fff 100%);
  }

  .santa-kindness-kicker {
    color: #b31412;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
  }

  .santa-kindness-head h2 {
    margin: 5px 0 0;
    font-size: 25px;
    line-height: 1;
    letter-spacing: -.035em;
  }

  .santa-kindness-head p {
    margin: 8px 0 0;
    color: var(--santa-muted);
    font-size: 12px;
    line-height: 1.45;
  }

  .santa-kindness-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 50%;
    background: #f1f3f4;
    color: var(--santa-ink);
    font-size: 21px;
    line-height: 1;
    cursor: pointer;
  }

  .santa-kindness-scroll {
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 14px;
  }

  .santa-scripture-card {
    padding: 15px 16px;
    border: 1px solid #e3e5e7;
    border-radius: 16px;
    background: #fff;
  }

  .santa-scripture-card + .santa-scripture-card {
    margin-top: 10px;
  }

  .santa-scripture-ref {
    color: var(--santa-blue-dark);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .santa-scripture-card blockquote {
    margin: 7px 0 0;
    font-size: 17px;
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: -.015em;
  }

  .santa-scripture-card p {
    margin: 8px 0 0;
    color: var(--santa-muted);
    font-size: 11px;
    line-height: 1.45;
  }

  .santa-kindness-challenge {
    margin-top: 10px;
    padding: 9px 10px;
    border-radius: 10px;
    background: #f1f8f3;
    color: #174c2a;
    font-size: 11px;
    line-height: 1.4;
  }

  .santa-kindness-challenge span {
    display: block;
    margin-bottom: 2px;
    color: var(--santa-green);
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .santa-kindness-foot {
    flex: 0 0 auto;
    padding: 11px 16px 13px;
    border-top: 1px solid var(--santa-border);
    color: var(--santa-muted);
    background: #fff;
    font-size: 10px;
    line-height: 1.4;
  }

  .santa-marker {
    position: relative;
    width: 92px;
    height: 70px;
    pointer-events: none;
    transform-origin: 50% 50%;
    filter: drop-shadow(0 5px 6px rgba(0,0,0,.26));
  }

  .santa-marker::before {
    content: "";
    position: absolute;
    left: 50%;
    top: 54%;
    width: 42px;
    height: 42px;
    margin: -21px 0 0 -21px;
    border-radius: 50%;
    background: rgba(255,255,255,.72);
    box-shadow: 0 0 0 6px rgba(217,48,37,.17);
    animation: santaPulse 1.8s ease-out infinite;
    z-index: 0;
  }

  .santa-marker-visual {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    z-index: 2;
  }

  .santa-marker-flight,
  .santa-marker-walk {
    position: absolute;
    left: 50%;
    top: 50%;
    display: block;
    max-width: none;
    user-select: none;
    -webkit-user-drag: none;
    transform-origin: 50% 55%;
    transition: opacity .18s ease, transform .18s ease;
  }

  .santa-marker-flight {
    width: 96px;
    height: 62px;
    object-fit: contain;
    opacity: 1;
    transform: translate(-50%, -50%);
  }

  .santa-marker-walk {
    width: 56px;
    height: 70px;
    object-fit: contain;
    opacity: 0;
    transform: translate(-50%, -48%) scale(.88);
  }

  .santa-marker.is-delivering {
    width: 64px;
    height: 76px;
    filter: drop-shadow(0 5px 6px rgba(0,0,0,.24));
  }

  .santa-marker.is-delivering::before {
    width: 38px;
    height: 38px;
    margin: -19px 0 0 -19px;
    background: rgba(255,255,255,.78);
    box-shadow: 0 0 0 6px rgba(24,128,56,.18);
    animation-duration: .85s;
  }

  .santa-marker.is-delivering .santa-marker-flight {
    opacity: 0;
    transform: translate(-50%, -50%) scale(.78);
  }

  .santa-marker.is-delivering .santa-marker-walk {
    opacity: 1;
    animation: santaWalkBob .46s ease-in-out infinite alternate;
  }

  @keyframes santaPulse {
    0% { transform: scale(.72); opacity: .9; }
    75%, 100% { transform: scale(1.38); opacity: 0; }
  }

  @keyframes santaWalkBob {
    0% { transform: translate(-50%, -48%) rotate(-2deg) scale(1); }
    100% { transform: translate(-50%, -52%) rotate(2deg) scale(1.025); }
  }

  .santa-map-stop {
    width: 19px;
    height: 19px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 3px solid #fff;
    background: var(--santa-blue);
    box-shadow: 0 2px 7px rgba(0,0,0,.2);
  }
  .santa-map-stop.is-past { background: var(--santa-green); }
  .santa-map-stop.is-current { background: var(--santa-red); width: 23px; height: 23px; }
  .santa-map-stop::after { content: ""; width: 5px; height: 5px; border-radius: 50%; background: #fff; }

  .santa-toast {
    position: absolute;
    z-index: 40;
    top: calc(80px + var(--safe-top));
    left: 50%;
    transform: translate(-50%, -12px);
    padding: 10px 14px;
    border-radius: 18px;
    background: #202124;
    color: #fff;
    font-size: 12px;
    box-shadow: var(--santa-shadow);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s ease, transform .2s ease;
  }
  .santa-toast.is-visible { opacity: 1; transform: translate(-50%, 0); }

  .mapboxgl-ctrl-bottom-left,
  .mapboxgl-ctrl-bottom-right { bottom: 176px !important; }
  .mapboxgl-ctrl-group { border-radius: 14px !important; overflow: hidden; box-shadow: var(--santa-shadow) !important; }
  .mapboxgl-popup-content { border-radius: 14px !important; padding: 10px 12px !important; font-family: Arial, Helvetica, sans-serif; }
  .mapboxgl-popup-content strong { font-size: 13px; }
  .mapboxgl-popup-content span { color: var(--santa-muted); font-size: 11px; }


  .santa-marker.is-delivering::after {
    content: "";
    position: absolute;
    z-index: 4;
    left: 50%;
    top: 62%;
    width: 12px;
    height: 12px;
    margin-left: -6px;
    border-radius: 2px;
    background:
      linear-gradient(90deg, transparent 42%, #f6d54a 42% 58%, transparent 58%),
      linear-gradient(transparent 42%, #f6d54a 42% 58%, transparent 58%),
      #d93025;
    box-shadow: 0 1px 2px rgba(0,0,0,.25);
    pointer-events: none;
    animation: santaGiftDrop .62s linear infinite;
  }

  @keyframes santaGiftDrop {
    0% { transform: translate(-50%, -5px) scale(.72) rotate(-8deg); opacity: 0; }
    18% { opacity: 1; }
    100% { transform: translate(-50%, 30px) scale(1.03) rotate(14deg); opacity: 0; }
  }

  @media (max-width: 600px) {
    .santa-marker { width: 78px; height: 60px; }
    .santa-marker-flight { width: 82px; height: 56px; }
    .santa-marker.is-delivering { width: 54px; height: 66px; }
    .santa-marker-walk { width: 48px; height: 62px; }
  }

  .santa-data-credit {
    flex: 0 0 auto;
    padding: 9px 16px 11px;
    border-top: 1px solid var(--santa-border);
    color: var(--santa-muted);
    background: #fff;
    font-size: 9px;
    line-height: 1.35;
  }

  @media (max-width: 900px) {
    .santa-side {
      top: auto;
      left: 8px;
      right: 8px;
      bottom: calc(154px + var(--safe-bottom));
      width: auto;
      height: min(56dvh, 470px);
      border-radius: 18px;
      transform: translateY(calc(100% + 180px));
      opacity: 0;
      pointer-events: none;
    }
    .santa-side.is-mobile-open {
      transform: translateY(0);
      opacity: 1;
      pointer-events: auto;
    }
    .santa-side.is-hidden {
      transform: translateY(calc(100% + 180px));
      opacity: 0;
    }

    .santa-bottom {
      left: 8px;
      right: 8px;
      bottom: calc(8px + var(--safe-bottom));
      grid-template-columns: 1.2fr .8fr;
      min-height: 136px;
      border-radius: 18px;
    }
    .santa-panel { padding: 13px 14px; }
    .santa-panel:nth-child(2) { border-right: 0; }
    .santa-panel:nth-child(3) { display: none; }
    .santa-avatar { width: 44px; height: 44px; font-size: 24px; }
    .santa-location h1 { font-size: 23px; }
    .santa-gifts-number { font-size: 28px; }
    .santa-countdown-card { top: calc(68px + var(--safe-top)); }
    .mapboxgl-ctrl-bottom-left,
    .mapboxgl-ctrl-bottom-right { bottom: 150px !important; }
  }

  @media (max-width: 600px) {
    html, body {
      width: 100%;
      height: 100%;
      min-height: 100%;
      overflow: hidden !important;
      overscroll-behavior: none;
    }

    .santa-app,
    #santaMap,
    .santa-map-tint,
    #snowCanvas {
      position: fixed !important;
      top: 0 !important;
      right: 0 !important;
      bottom: 0 !important;
      left: 0 !important;
      width: auto !important;
      height: auto !important;
    }

    .santa-topbar,
    .santa-countdown-card,
    .santa-bottom,
    .santa-side {
      position: fixed !important;
    }

    .santa-topbar {
      position: fixed !important;
      top: calc(6px + env(safe-area-inset-top, 0px)) !important;
      left: 6px;
      right: 6px;
      gap: 6px;
    }
    .santa-brand-card {
      min-height: 40px;
      gap: 7px;
      padding: 5px 9px 5px 5px;
      border-radius: 13px;
    }
    .santa-brand-mark { width: 29px; height: 29px; font-size: 16px; }
    .santa-brand-copy strong { font-size: 12px; }
    .santa-brand-copy span { font-size: 8px; margin-top: 2px; }
    .santa-top-actions { gap: 5px; }
    .santa-top-actions .santa-chip { display: none; }
    .santa-icon-btn {
      width: 36px;
      height: 36px;
      font-size: 16px;
      border-radius: 12px;
    }

    .santa-countdown-card {
      position: fixed !important;
      top: calc(53px + env(safe-area-inset-top, 0px)) !important;
      left: 6px;
      width: auto;
      max-width: min(184px, calc(100vw - 88px));
      padding: 7px 9px;
      border-radius: 12px;
      box-shadow: 0 5px 18px rgba(32,33,36,.14);
    }
    .santa-countdown-card .santa-panel-label {
      font-size: 8px;
      letter-spacing: .07em;
    }
    .santa-countdown-card strong { font-size: 13px; margin-top: 2px; }
    .santa-countdown-card p { display: none; }

    .santa-kindness-prompt {
      display: none;
    }

    .santa-kindness-backdrop {
      position: fixed !important;
    }

    .santa-kindness-panel {
      position: fixed !important;
      top: auto !important;
      right: 6px;
      bottom: calc(6px + env(safe-area-inset-bottom, 0px)) !important;
      left: 6px;
      width: auto;
      max-height: min(74dvh, 640px);
      border-radius: 18px;
      transform: none;
    }

    .santa-kindness-panel.is-open {
      transform: none;
    }

    .santa-kindness-head {
      padding: 16px 50px 14px 16px;
    }

    .santa-kindness-head h2 {
      font-size: 22px;
    }

    .santa-kindness-head p {
      font-size: 11px;
    }

    .santa-kindness-scroll {
      padding: 10px;
    }

    .santa-scripture-card {
      padding: 13px 14px;
      border-radius: 14px;
    }

    .santa-scripture-card blockquote {
      font-size: 15px;
    }

    .santa-bottom {
      position: fixed !important;
      top: auto !important;
      left: 6px;
      right: 6px;
      bottom: calc(6px + env(safe-area-inset-bottom, 0px)) !important;
      transform: none !important;
      display: grid;
      grid-template-columns: 1fr 1fr;
      grid-template-rows: auto auto;
      min-height: 0;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 8px 28px rgba(11,43,83,.24);
    }
    .santa-bottom::before {
      content: "";
      position: absolute;
      z-index: 2;
      top: 5px;
      left: 50%;
      width: 32px;
      height: 3px;
      border-radius: 3px;
      transform: translateX(-50%);
      background: #dadce0;
    }

    .santa-panel {
      min-width: 0;
      min-height: 0;
      padding: 10px 11px 9px;
      border-right: 0;
    }
    .santa-panel:first-child {
      grid-column: 1 / -1;
      padding-top: 15px;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--santa-border);
    }
    .santa-panel:nth-child(2),
    .santa-panel:nth-child(3) {
      display: block;
      padding-top: 8px;
      padding-bottom: 9px;
    }
    .santa-panel:nth-child(2) { border-right: 1px solid var(--santa-border); }

    .santa-panel-label {
      font-size: 8px;
      letter-spacing: .08em;
    }
    .santa-location {
      align-items: center;
      gap: 8px;
      margin-top: 5px;
    }
    .santa-avatar {
      width: 36px;
      height: 36px;
      font-size: 20px;
    }
    .santa-location h1 {
      font-size: 20px;
      line-height: 1;
    }
    .santa-location p {
      margin-top: 3px;
      max-width: calc(100vw - 84px);
      overflow: hidden;
      color: var(--santa-muted);
      font-size: 9px;
      line-height: 1.2;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .santa-progress {
      height: 3px;
      margin-top: 7px;
    }

    .santa-gifts-number {
      margin-top: 4px;
      font-size: 18px;
    }
    .santa-gifts-copy { display: none; }
    .santa-stats { gap: 7px; margin-top: 4px; }
    .santa-stat strong { font-size: 9px; }
    .santa-stat span { font-size: 7px; }
    .santa-stat:nth-child(2) { display: none; }

    .santa-next h2 {
      margin-top: 4px;
      font-size: 16px;
      line-height: 1;
    }
    .santa-next-meta {
      gap: 5px;
      margin-top: 4px;
      font-size: 8px;
      line-height: 1.2;
    }
    .santa-primary-btn { display: none; }

    .santa-side {
      position: fixed !important;
      top: auto !important;
      bottom: calc(145px + env(safe-area-inset-bottom, 0px)) !important;
      left: 6px;
      right: 6px;
      border-radius: 16px;
    }
    .mapboxgl-ctrl-bottom-left,
    .mapboxgl-ctrl-bottom-right { bottom: 144px !important; }
    .mapboxgl-ctrl-group { border-radius: 11px !important; }
    .mapboxgl-ctrl-group button { width: 34px; height: 34px; }
    .mapboxgl-ctrl-bottom-left .mapboxgl-ctrl { margin-left: 7px; }
    .mapboxgl-ctrl-bottom-right .mapboxgl-ctrl { margin-right: 7px; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; }
  }
</style>
@endsection

@section('document-body')
<div class="santa-app" id="santaApp">
  <div id="santaMap" aria-label="Interactive Santa route map"></div>
  <div class="santa-map-tint"></div>
  <canvas id="snowCanvas" aria-hidden="true"></canvas>

  <div class="santa-topbar">
    <div class="santa-brand">
      <a class="santa-brand-card" href="/" aria-label="We Offer Wellness home" style="color:inherit;text-decoration:none;">
        <span class="santa-brand-mark" aria-hidden="true">
          <img src="{{ asset('images/santa-tracker/santa-tracker-favicon.png') }}?v=285ab04e" alt="">
        </span>
        <span class="santa-brand-copy">
          <strong>Santa Tracker</strong>
          <span>by We Offer Wellness</span>
        </span>
      </a>
    </div>

    <div class="santa-top-actions">
      <button class="santa-chip is-preview" type="button" id="modeChip" aria-label="Tracking mode">
        <span class="santa-chip-dot"></span>
        <span id="modeChipText">Preview live</span>
      </button>
      <button class="santa-icon-btn is-kindness" type="button" id="kindnessBtn" aria-label="Open Giving and kindness" aria-expanded="false" title="Giving &amp; kindness">♥</button>
      <button class="santa-icon-btn" type="button" id="followBtn" aria-label="Follow Santa" title="Follow Santa">◎</button>
      <button class="santa-icon-btn" type="button" id="routeBtn" aria-label="Open Santa route" title="Route">☰</button>
    </div>
  </div>

  <div class="santa-countdown-card" id="countdownCard">
    <div class="santa-panel-label" id="countdownLabel">Christmas Eve countdown</div>
    <strong id="countdownValue">Loading…</strong>
    <p id="countdownCopy">Test mode is active now; live Christmas Eve timing switches on automatically.</p>
  </div>

  <button class="santa-kindness-prompt" type="button" id="kindnessPrompt" aria-label="Open Christmas giving and kindness">
    <span class="santa-kindness-prompt-label">♥ Christmas kindness</span>
    <strong id="kindnessPromptText">“It is more blessed to give than to receive.”</strong>
    <small id="kindnessPromptRef">Acts 20:35</small>
  </button>

  <div class="santa-kindness-backdrop" id="kindnessBackdrop" aria-hidden="true"></div>

  <aside class="santa-kindness-panel" id="kindnessPanel" role="dialog" aria-modal="true" aria-labelledby="kindnessTitle" aria-hidden="true">
    <div class="santa-kindness-head">
      <div class="santa-kindness-kicker">Christmas kindness</div>
      <h2 id="kindnessTitle">Giving with love</h2>
      <p>A little Christmas reminder from the Bible: giving is not only about presents. We can give our time, our help, kind words and love too.</p>
      <button class="santa-kindness-close" type="button" id="kindnessCloseBtn" aria-label="Close Giving and kindness">×</button>
    </div>

    <div class="santa-kindness-scroll">
      <article class="santa-scripture-card">
        <div class="santa-scripture-ref">Acts 20:35</div>
        <blockquote>“It is more blessed to give than to receive.”</blockquote>
        <p><strong>What it means:</strong> Giving can bring joy to someone else — and to us too.</p>
        <div class="santa-kindness-challenge"><span>Try this today</span>Give something that costs no money: your time, a smile, a kind word or some help.</div>
      </article>

      <article class="santa-scripture-card">
        <div class="santa-scripture-ref">2 Corinthians 9:7</div>
        <blockquote>“For God loves a cheerful giver.”</blockquote>
        <p><strong>What it means:</strong> Giving matters most when we choose to do it gladly, not because we feel forced.</p>
        <div class="santa-kindness-challenge"><span>Try this today</span>Do one kind thing happily, without waiting to be asked.</div>
      </article>

      <article class="santa-scripture-card">
        <div class="santa-scripture-ref">1 Corinthians 13:3</div>
        <blockquote>“But if I didn’t love others, I would have gained nothing.”</blockquote>
        <p><strong>What it means:</strong> What we give matters, but the love behind the gift matters even more.</p>
        <div class="santa-kindness-challenge"><span>Try this today</span>Before you give, ask yourself: “How can I make this person feel loved?”</div>
      </article>

      <article class="santa-scripture-card">
        <div class="santa-scripture-ref">Proverbs 11:25</div>
        <blockquote>“A generous person will prosper.”</blockquote>
        <p><strong>What it means:</strong> Generosity can make other people stronger, happier and more hopeful.</p>
        <div class="santa-kindness-challenge"><span>Try this today</span>Refresh someone today: include them, encourage them or lend a hand.</div>
      </article>
    </div>

    <div class="santa-kindness-foot">Small acts count. Christmas generosity can be a gift, a helping hand, patience, forgiveness or simply making time for someone.</div>
  </aside>

  <aside class="santa-side" id="routePanel" aria-label="Santa route">
    <div class="santa-side-head">
      <div class="santa-side-kicker">Around the world</div>
      <h2 class="santa-side-title">Santa’s route</h2>
      <div style="margin-top:8px;color:#5f6368;font-size:11px;line-height:1.4;">
        <strong style="color:#202124;">24 Dec 10:00 UTC → 25 Dec 11:00 UTC</strong><br>
        <span id="santaClockText">Exact position calculated from Santa’s UTC clock.</span>
      </div>
    </div>
    <div class="santa-route-list" id="routeList"></div>
    <div class="santa-data-credit">Town &amp; city data: GeoNames · CC BY 4.0</div>
  </aside>

  <section class="santa-bottom" aria-live="polite">
    <div class="santa-panel">
      <div class="santa-panel-label" id="activityLabel">Santa is heading through</div>
      <div class="santa-location">
        <div class="santa-avatar" aria-hidden="true">
          <img src="{{ asset('images/santa-tracker/santa-tracker-favicon.png') }}?v=285ab04e" alt="">
        </div>
        <div>
          <h1 id="currentCity">North Pole</h1>
          <p id="currentStatus">Preparing the sleigh for a test flight</p>
        </div>
      </div>
      <div class="santa-progress" aria-label="Journey progress"><span id="journeyProgress"></span></div>
    </div>

    <div class="santa-panel">
      <div class="santa-panel-label">Presents delivered</div>
      <div class="santa-gifts-number" id="giftCount">0</div>
      <div class="santa-gifts-copy">and climbing faster than an elf on espresso.</div>
      <div class="santa-stats">
        <div class="santa-stat"><strong id="speedStat">0 km/h</strong><span>Speed</span></div>
        <div class="santa-stat"><strong id="altitudeStat">0 m</strong><span>Altitude</span></div>
      </div>
    </div>

    <div class="santa-panel santa-next">
      <div class="santa-panel-label">Next stop</div>
      <h2 id="nextCity">Auckland</h2>
      <div class="santa-next-meta">
        <span id="distanceStat">— km away</span>
        <span id="etaStat">Arriving soon</span>
      </div>
      <button class="santa-primary-btn" type="button" id="nextStopBtn">Show next stop</button>
    </div>
  </section>

  <div class="santa-toast" id="santaToast" role="status"></div>
</div>

<script>
(function () {
  'use strict';

  var token = @json(config('services.mapbox.token'));
  var app = document.getElementById('santaApp');
  var activityLabelEl = document.getElementById('activityLabel');
  var santaClockTextEl = document.getElementById('santaClockText');
  var currentCityEl = document.getElementById('currentCity');
  var currentStatusEl = document.getElementById('currentStatus');
  var nextCityEl = document.getElementById('nextCity');
  var giftCountEl = document.getElementById('giftCount');
  var speedStatEl = document.getElementById('speedStat');
  var altitudeStatEl = document.getElementById('altitudeStat');
  var distanceStatEl = document.getElementById('distanceStat');
  var etaStatEl = document.getElementById('etaStat');
  var journeyProgressEl = document.getElementById('journeyProgress');
  var routePanel = document.getElementById('routePanel');
  var routeList = document.getElementById('routeList');
  var routeBtn = document.getElementById('routeBtn');
  var kindnessBtn = document.getElementById('kindnessBtn');
  var kindnessPanel = document.getElementById('kindnessPanel');
  var kindnessCloseBtn = document.getElementById('kindnessCloseBtn');
  var kindnessBackdrop = document.getElementById('kindnessBackdrop');
  var kindnessPrompt = document.getElementById('kindnessPrompt');
  var kindnessPromptText = document.getElementById('kindnessPromptText');
  var kindnessPromptRef = document.getElementById('kindnessPromptRef');
  var followBtn = document.getElementById('followBtn');
  var nextStopBtn = document.getElementById('nextStopBtn');
  var countdownValue = document.getElementById('countdownValue');
  var countdownLabel = document.getElementById('countdownLabel');
  var countdownCopy = document.getElementById('countdownCopy');
  var modeChip = document.getElementById('modeChip');
  var modeChipText = document.getElementById('modeChipText');
  var toastEl = document.getElementById('santaToast');

  var route = [
    { city: 'North Pole', country: 'Arctic', lng: 168, lat: 84.6, emoji: '❄️' },
    { city: 'North Pole', country: 'Home', lng: 168, lat: 84.6, emoji: '🏠' }
  ];

  var map = null;
  var santaMarker = null;
  var stopMarkers = [];
  var followSanta = true;
  var currentSegment = 0;
  var lastFrame = performance.now();
  var toastTimer = null;
  var routeDurationDemo = 240000;
  var demoStartedAt = performance.now() - 9000;
  var query = new URLSearchParams(window.location.search);
  var demoMode = query.get('demo') || '';
  var forceDemo = demoMode === '1';
  var winterDemo = demoMode === '3';
  var normalSpeedDemo = demoMode === '2' || winterDemo;
  var normalDemoStartedAt = performance.now();
  var fixedAtRaw = query.get('at') || query.get('time') || '';
  var fixedAtParsed = fixedAtRaw ? Date.parse(fixedAtRaw) : NaN;
  var fixedAtMs = Number.isFinite(fixedAtParsed) ? fixedAtParsed : null;
  var isChristmasLive = false;
  var SANTA_START_HOUR_UTC = 10;
  var SANTA_END_HOUR_UTC = 11;
  var SANTA_RUN_DURATION_MS = 25 * 60 * 60 * 1000;
  var SANTA_PRESENT_TARGET = 7854219632;
  var FLIGHT_TIME_RATIO = .32;
  var DELIVERY_TIME_RATIO = .68;
  var journeySchedule = [];
  var scheduleWindow = null;
  var currentSantaTimeMs = null;
  var deliveryManifest = null;
  var deliveryCache = {};
  var deliveryPending = {};
  var googleSantaInfo = null;
  var googleSantaRoute = null;
  var googleSantaExact = false;
  var googleTimeOffsetMs = 0;
  var googleTakeoffMs = null;
  var googleDurationMs = null;
  var googleRouteUrl = null;
  var localTrail = [];
  var visitedTrails = [];
  var currentTrailDestinationIndex = null;
  var lastTrailPlaceIndex = -1;
  var lastTrailUpdate = 0;
  var lastRouteTrailUpdate = 0;
  var lastJourneyProgress = 0;
  var currentTravelMode = 'flight';
  var kindnessPromptIndex = -1;
  var kindnessMoments = [
    { ref: 'Acts 20:35', text: '“It is more blessed to give than to receive.”' },
    { ref: '2 Corinthians 9:7', text: '“For God loves a cheerful giver.”' },
    { ref: '1 Corinthians 13:3', text: '“If I didn’t love others, I would have gained nothing.”' },
    { ref: 'Proverbs 11:25', text: '“A generous person will prosper.”' }
  ];

  function santaWindowForYear(year) {
    if (googleTakeoffMs && new Date(googleTakeoffMs).getUTCFullYear() === year) {
      var duration = Number(googleDurationMs || SANTA_RUN_DURATION_MS);
      return { year: year, startMs: Number(googleTakeoffMs), endMs: Number(googleTakeoffMs) + duration };
    }
    return {
      year: year,
      startMs: Date.UTC(year, 11, 24, SANTA_START_HOUR_UTC, 0, 0, 0),
      endMs: Date.UTC(year, 11, 25, SANTA_END_HOUR_UTC, 0, 0, 0)
    };
  }

  function resolveScheduleWindow(referenceMs) {
    var ref = new Date(referenceMs);
    var year = ref.getUTCFullYear();
    var thisYear = santaWindowForYear(year);

    if (fixedAtMs !== null) {
      return santaWindowForYear(new Date(fixedAtMs).getUTCFullYear());
    }

    if (referenceMs > thisYear.endMs) {
      return santaWindowForYear(year + 1);
    }

    return thisYear;
  }

  function formatUtcClock(ms, includeDate) {
    var d = new Date(ms);
    var hh = String(d.getUTCHours()).padStart(2, '0');
    var mm = String(d.getUTCMinutes()).padStart(2, '0');
    var ss = String(d.getUTCSeconds()).padStart(2, '0');
    if (!includeDate) return hh + ':' + mm + ':' + ss + ' UTC';
    var day = String(d.getUTCDate()).padStart(2, '0');
    var mon = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getUTCMonth()];
    return day + ' ' + mon + ' ' + hh + ':' + mm + ':' + ss + ' UTC';
  }

  function flagEmoji(countryCode) {
    var cc = String(countryCode || '').toUpperCase();
    if (!/^[A-Z]{2}$/.test(cc)) return '🌙';
    return String.fromCodePoint(cc.charCodeAt(0) + 127397, cc.charCodeAt(1) + 127397);
  }

  function applyWorldManifest(manifest) {
    var zones = Array.isArray(manifest && manifest.zones) ? manifest.zones : [];
    var worldRoute = [{ city: 'North Pole', country: 'Arctic', lng: 168, lat: 84.6, emoji: '❄️' }];

    zones.forEach(function (z) {
      worldRoute.push({
        city: z.hub || z.timezone || 'Christmas delivery zone',
        country: (z.hub_country ? z.hub_country + ' · ' : '') + String(z.timezone || '').replace(/_/g, ' '),
        lng: Number(z.hub_lng),
        lat: Number(z.hub_lat),
        emoji: flagEmoji(z.hub_country),
        dataKey: z.key,
        timezone: z.timezone,
        placeCount: Number(z.count || 0),
        chunkBase: z.chunk_base,
        chunkSize: Number(z.chunk_size || manifest.chunk_size || 5000),
        chunkCount: Number(z.chunk_count || 0),
        localStart: z.local_start || null,
        localEnd: z.local_end || null,
        startOffsetMs: Number(z.start_offset_ms || 0),
        flightEndOffsetMs: Number(z.flight_end_offset_ms || 0),
        endOffsetMs: Number(z.end_offset_ms || 0)
      });
    });

    worldRoute.push({ city: 'North Pole', country: 'Home', lng: 168, lat: 84.6, emoji: '🏠' });
    route = worldRoute;
  }

  function buildJourneySchedule(manifest) {
    var zones = Array.isArray(manifest && manifest.zones) ? manifest.zones : [];
    var cumulativePlaces = 0;
    journeySchedule = zones.map(function (z, i) {
      var startOffsetMs = Number(z.start_offset_ms || 0);
      var flightEndOffsetMs = Number(z.flight_end_offset_ms || startOffsetMs);
      var endOffsetMs = Number(z.end_offset_ms || flightEndOffsetMs);
      var placeCount = Math.max(0, Number(z.count || 0));
      var placeStart = cumulativePlaces;
      cumulativePlaces += placeCount;
      return {
        index: i,
        destinationIndex: i + 1,
        startOffsetMs: startOffsetMs,
        flightEndOffsetMs: flightEndOffsetMs,
        endOffsetMs: endOffsetMs,
        flightMs: Math.max(1, flightEndOffsetMs - startOffsetMs),
        deliveryMs: Math.max(0, endOffsetMs - flightEndOffsetMs),
        distance: i < route.length - 1 ? haversineKm(route[i], route[i + 1]) : 0,
        placeCount: placeCount,
        placeStart: placeStart,
        placeEnd: cumulativePlaces,
        timezone: z.timezone
      };
    });

    if (route.length >= 2) {
      var homeIndex = route.length - 1;
      var lastStart = journeySchedule.length ? journeySchedule[journeySchedule.length - 1].endOffsetMs : 0;
      journeySchedule.push({
        index: homeIndex - 1,
        destinationIndex: homeIndex,
        startOffsetMs: lastStart,
        flightEndOffsetMs: SANTA_RUN_DURATION_MS,
        endOffsetMs: SANTA_RUN_DURATION_MS,
        flightMs: Math.max(1, SANTA_RUN_DURATION_MS - lastStart),
        deliveryMs: 0,
        distance: haversineKm(route[homeIndex - 1], route[homeIndex]),
        placeCount: 0,
        placeStart: cumulativePlaces,
        placeEnd: cumulativePlaces,
        timezone: null
      });
    }
  }

  function formatLocalClock(ms, timeZone) {
    if (!timeZone || !Number.isFinite(ms)) return '';
    try {
      return new Intl.DateTimeFormat('en-GB', {
        timeZone: timeZone,
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      }).format(new Date(ms));
    } catch (_err) {
      return '';
    }
  }

  function getEffectiveSantaTimeMs() {
    var nowMs = Date.now() + Number(googleTimeOffsetMs || 0);
    scheduleWindow = resolveScheduleWindow(fixedAtMs !== null ? fixedAtMs : nowMs);

    if (fixedAtMs !== null) {
      currentSantaTimeMs = fixedAtMs;
      return currentSantaTimeMs;
    }

    if (forceDemo) {
      var demoProgress = ((performance.now() - demoStartedAt) % routeDurationDemo) / routeDurationDemo;
      currentSantaTimeMs = scheduleWindow.startMs + demoProgress * SANTA_RUN_DURATION_MS;
      return currentSantaTimeMs;
    }

    if (normalSpeedDemo) {
      var elapsedRealMs = Math.max(0, performance.now() - normalDemoStartedAt);
      currentSantaTimeMs = Math.min(scheduleWindow.endMs, scheduleWindow.startMs + elapsedRealMs);
      return currentSantaTimeMs;
    }

    currentSantaTimeMs = nowMs;
    return currentSantaTimeMs;
  }

  function getScheduleSegmentAt(timeMs) {
    if (!scheduleWindow) scheduleWindow = resolveScheduleWindow(timeMs);

    if (timeMs <= scheduleWindow.startMs) {
      return {
        status: 'waiting',
        index: 0,
        destinationIndex: 1,
        mode: 'waiting',
        phaseT: 0,
        overallProgress: 0
      };
    }

    if (timeMs >= scheduleWindow.endMs) {
      return {
        status: 'home',
        index: route.length - 2,
        destinationIndex: route.length - 1,
        mode: 'home',
        phaseT: 1,
        overallProgress: 1
      };
    }

    var elapsed = timeMs - scheduleWindow.startMs;

    if (!journeySchedule.length) {
      var fallbackProgress = Math.max(0, Math.min(1, elapsed / SANTA_RUN_DURATION_MS));
      var fallbackFloat = fallbackProgress * Math.max(1, route.length - 1);
      var fallbackIndex = Math.min(route.length - 2, Math.floor(fallbackFloat));
      return {
        status: 'live',
        index: fallbackIndex,
        destinationIndex: fallbackIndex + 1,
        mode: 'flight',
        phaseT: fallbackFloat - fallbackIndex,
        overallProgress: fallbackProgress,
        scheduleItem: null,
        elapsedMs: elapsed
      };
    }

    var item = journeySchedule[journeySchedule.length - 1];

    for (var i = 0; i < journeySchedule.length; i++) {
      if (elapsed < journeySchedule[i].endOffsetMs) {
        item = journeySchedule[i];
        break;
      }
    }

    var isFlight = elapsed < item.flightEndOffsetMs || item.deliveryMs <= 0;
    var phaseStart = isFlight ? item.startOffsetMs : item.flightEndOffsetMs;
    var phaseDuration = isFlight ? Math.max(1, item.flightMs) : Math.max(1, item.deliveryMs);
    var phaseT = Math.max(0, Math.min(1, (elapsed - phaseStart) / phaseDuration));

    return {
      status: 'live',
      index: item.index,
      destinationIndex: item.destinationIndex,
      mode: isFlight ? 'flight' : 'delivery',
      phaseT: phaseT,
      overallProgress: Math.max(0, Math.min(1, elapsed / SANTA_RUN_DURATION_MS)),
      scheduleItem: item,
      elapsedMs: elapsed
    };
  }

  function loadGoogleSantaRoute(forceRouteFetch) {
    var infoUrl = 'https://santa-api.appspot.com/info?client=web&language=en&rand=0.5';
    return fetch(infoUrl, { cache: 'no-store', mode: 'cors' })
      .then(function (response) {
        if (!response.ok) throw new Error('Google Santa info ' + response.status);
        return response.json();
      })
      .then(function (info) {
        googleSantaInfo = info || null;
        var localNow = Date.now();
        if (info && Number.isFinite(Number(info.now))) {
          googleTimeOffsetMs = Number(info.now) - localNow + Number(info.timeOffset || 0);
        }
        if (info && Number.isFinite(Number(info.takeoff))) googleTakeoffMs = Number(info.takeoff);
        if (info && Number.isFinite(Number(info.duration))) googleDurationMs = Number(info.duration);

        var rawRoute = info && info.route;
        var url = Array.isArray(rawRoute) ? rawRoute[0] : rawRoute;
        if (!url) return null;
        googleRouteUrl = url;

        var takeoff = Number(googleTakeoffMs || 0);
        var nearChristmas = takeoff && Math.abs((Date.now() + googleTimeOffsetMs) - takeoff) < 7 * 86400000;
        if (!nearChristmas && !forceRouteFetch) return null;

        return fetch(url, { cache: 'no-store', mode: 'cors' })
          .then(function (response) {
            if (!response.ok) throw new Error('Google Santa route ' + response.status);
            return response.json();
          });
      })
      .then(function (data) {
        if (!data || !Array.isArray(data.destinations) || data.destinations.length < 2) {
          googleSantaExact = false;
          return null;
        }

        var destinations = data.destinations;
        var first = destinations[0];
        var last = destinations[destinations.length - 1];
        var expectedYear = new Date(googleTakeoffMs || Date.now()).getUTCFullYear();
        var firstDeparture = Number(first.departure || 0);
        var lastArrival = Number(last.arrival || 0);
        var routeYear = firstDeparture ? new Date(firstDeparture).getUTCFullYear() : 0;
        var duration = lastArrival > firstDeparture ? lastArrival - firstDeparture : 0;

        // Google leaves an old canonical route online outside Christmas. Only call it exact
        // when Google has published timestamps for the current Christmas run.
        googleSantaExact = routeYear === expectedYear &&
          Math.abs(firstDeparture - Number(googleTakeoffMs || firstDeparture)) < 6 * 3600000 &&
          Math.abs(duration - Number(googleDurationMs || duration)) < 6 * 3600000;

        googleSantaRoute = googleSantaExact ? destinations : null;
        return googleSantaRoute;
      })
      .catch(function (error) {
        console.warn('Google Santa sync unavailable; using WOW night schedule.', error);
        googleSantaExact = false;
        googleSantaRoute = null;
        return null;
      });
  }

  function sphericalInterpolate(a, b, t) {
    var ratio = Math.max(0, Math.min(1, Number(t || 0)));
    var lat1 = Number(a.lat) * Math.PI / 180;
    var lon1 = Number(a.lng) * Math.PI / 180;
    var lat2 = Number(b.lat) * Math.PI / 180;
    var lon2 = Number(b.lng) * Math.PI / 180;
    var x1 = Math.cos(lat1) * Math.cos(lon1), y1 = Math.cos(lat1) * Math.sin(lon1), z1 = Math.sin(lat1);
    var x2 = Math.cos(lat2) * Math.cos(lon2), y2 = Math.cos(lat2) * Math.sin(lon2), z2 = Math.sin(lat2);
    var dot = Math.max(-1, Math.min(1, x1*x2 + y1*y2 + z1*z2));
    var omega = Math.acos(dot);
    if (omega < 1e-9) return { lat: Number(a.lat), lng: Number(a.lng) };
    var sinOmega = Math.sin(omega);
    var k1 = Math.sin((1-ratio)*omega) / sinOmega;
    var k2 = Math.sin(ratio*omega) / sinOmega;
    var x = k1*x1 + k2*x2, y = k1*y1 + k2*y2, z = k1*z1 + k2*z2;
    var lon = Math.atan2(y,x) * 180 / Math.PI;
    var hyp = Math.sqrt(x*x+y*y);
    var lat = Math.atan2(z,hyp) * 180 / Math.PI;
    return { lat: lat, lng: normaliseLng(lon) };
  }

  function getGoogleRouteState(timeMs) {
    var destinations = googleSantaExact && Array.isArray(googleSantaRoute) ? googleSantaRoute : null;
    if (!destinations || !destinations.length) return null;
    var first = destinations[0];
    var last = destinations[destinations.length - 1];
    if (timeMs < Number(first.departure || 0) || timeMs > Number(last.arrival || 0)) return null;

    var index = destinations.length - 1;
    for (var i = 0; i < destinations.length; i++) {
      if (Number(destinations[i].departure || 0) >= timeMs) { index = i; break; }
    }
    var dest = destinations[index];
    var prev = index > 0 ? destinations[index - 1] : null;
    var next = index + 1 < destinations.length ? destinations[index + 1] : null;

    if (!prev || timeMs >= Number(dest.arrival || 0)) {
      return {
        mode: 'google-stop', pos: { lat: Number(dest.location.lat), lng: Number(dest.location.lng) },
        city: dest.city || '', region: dest.region || '', prev: prev, dest: dest, next: next,
        headingTarget: next && next.location ? next.location : dest.location,
        presentsDelivered: Number(dest.presentsDelivered || 0)
      };
    }

    var depart = Number(prev.departure || 0);
    var arrive = Number(dest.arrival || depart + 1);
    var ratio = Math.max(0, Math.min(1, (timeMs - depart) / Math.max(1, arrive - depart)));
    return {
      mode: 'google-flight', pos: sphericalInterpolate(prev.location, dest.location, ratio),
      city: prev.city || '', region: prev.region || '', prev: prev, dest: dest, next: dest,
      headingTarget: dest.location,
      presentsDelivered: Math.floor(Number(prev.presentsDelivered || 0) + (Number(dest.presentsDelivered || 0) - Number(prev.presentsDelivered || 0)) * ratio)
    };
  }

  function loadMapbox() {
    if (!token) {
      showToast('Mapbox token is missing from the Frontend configuration.');
      return Promise.reject(new Error('Missing Mapbox token'));
    }
    if (window.mapboxgl) return Promise.resolve();

    var cssHref = 'https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css';
    if (!document.querySelector('link[href="' + cssHref + '"]')) {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = cssHref;
      document.head.appendChild(link);
    }

    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = 'https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js';
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  function normaliseLng(lng) {
    var x = lng;
    while (x > 180) x -= 360;
    while (x < -180) x += 360;
    return x;
  }

  function shortestLngDelta(a, b) {
    var d = b - a;
    if (d > 180) d -= 360;
    if (d < -180) d += 360;
    return d;
  }

  function lerp(a, b, t) { return a + (b - a) * t; }
  function ease(t) { return t * t * (3 - 2 * t); }

  function interpolateSegment(from, to, t) {
    var e = ease(Math.max(0, Math.min(1, t)));
    var lng = normaliseLng(from.lng + shortestLngDelta(from.lng, to.lng) * e);
    var arc = Math.sin(Math.PI * e);
    var lat = lerp(from.lat, to.lat, e) + arc * Math.min(10, Math.max(2, Math.abs(to.lat - from.lat) * .08));
    return { lng: lng, lat: Math.max(-84, Math.min(84, lat)) };
  }

  function haversineKm(a, b) {
    var R = 6371;
    var dLat = (b.lat - a.lat) * Math.PI / 180;
    var dLng = shortestLngDelta(a.lng, b.lng) * Math.PI / 180;
    var lat1 = a.lat * Math.PI / 180;
    var lat2 = b.lat * Math.PI / 180;
    var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.sin(dLng / 2) * Math.sin(dLng / 2) * Math.cos(lat1) * Math.cos(lat2);
    return 2 * R * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
  }

  function bearingDeg(a, b) {
    var lat1 = a.lat * Math.PI / 180;
    var lat2 = b.lat * Math.PI / 180;
    var dLng = shortestLngDelta(a.lng, b.lng) * Math.PI / 180;
    var y = Math.sin(dLng) * Math.cos(lat2);
    var x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
    return Math.atan2(y, x) * 180 / Math.PI;
  }

  function pushTrailPoint(lines, current, lng, lat) {
    var point = [normaliseLng(Number(lng)), Math.max(-84.8, Math.min(84.8, Number(lat)))];
    if (!Number.isFinite(point[0]) || !Number.isFinite(point[1])) return current;

    if (!current.length) {
      current.push(point);
      return current;
    }

    var prev = current[current.length - 1];
    if (Math.abs(prev[0] - point[0]) > 180) {
      if (current.length > 1) lines.push(current);
      current = [point];
      return current;
    }

    if (Math.abs(prev[0] - point[0]) > .00001 || Math.abs(prev[1] - point[1]) > .00001) current.push(point);
    return current;
  }

  function appendWowLeg(lines, current, from, to, maxT) {
    var tMax = Math.max(0, Math.min(1, Number(maxT || 0)));
    if (tMax <= 0) return current;
    var steps = Math.max(2, Math.ceil(10 * tMax));
    for (var i = 0; i <= steps; i++) {
      var t = tMax * (i / steps);
      var p = interpolateSegment(from, to, t);
      current = pushTrailPoint(lines, current, p.lng, p.lat);
    }
    return current;
  }

  function appendSphericalLeg(lines, current, from, to, maxT) {
    var tMax = Math.max(0, Math.min(1, Number(maxT || 0)));
    if (tMax <= 0) return current;
    var steps = Math.max(2, Math.ceil(12 * tMax));
    for (var i = 0; i <= steps; i++) {
      var t = tMax * (i / steps);
      var p = sphericalInterpolate(from, to, t);
      current = pushTrailPoint(lines, current, p.lng, p.lat);
    }
    return current;
  }

  function buildWowVisitedRouteLines(state) {
    var lines = [];
    var current = [];
    if (!state || state.mode === 'waiting' || !state.segment) return lines;

    if (state.mode === 'home') {
      for (var h = 0; h < route.length - 1; h++) current = appendWowLeg(lines, current, route[h], route[h + 1], 1);
      if (current.length > 1) lines.push(current);
      return lines;
    }

    var legIndex = Math.max(0, Number(state.segment.index || 0));
    for (var i = 0; i < legIndex && i < route.length - 1; i++) {
      current = appendWowLeg(lines, current, route[i], route[i + 1], 1);
    }

    if (legIndex < route.length - 1) {
      var currentLegT = state.mode === 'flight' ? Number(state.progress || 0) : 1;
      current = appendWowLeg(lines, current, route[legIndex], route[legIndex + 1], currentLegT);
    }

    if (current.length > 1) lines.push(current);
    return lines;
  }

  function buildGoogleVisitedRouteLines(timeMs) {
    var lines = [];
    var current = [];
    var destinations = googleSantaExact && Array.isArray(googleSantaRoute) ? googleSantaRoute : null;
    if (!destinations || destinations.length < 2) return lines;

    for (var i = 1; i < destinations.length; i++) {
      var prev = destinations[i - 1];
      var dest = destinations[i];
      var depart = Number(prev.departure || 0);
      var arrive = Number(dest.arrival || depart + 1);
      if (timeMs <= depart) break;

      if (timeMs >= arrive) {
        current = appendSphericalLeg(lines, current, prev.location, dest.location, 1);
        continue;
      }

      var ratio = Math.max(0, Math.min(1, (timeMs - depart) / Math.max(1, arrive - depart)));
      current = appendSphericalLeg(lines, current, prev.location, dest.location, ratio);
      break;
    }

    if (current.length > 1) lines.push(current);
    return lines;
  }

  function updateLongHaulTrail(state, googleState) {
    if (!map) return;
    var source = map.getSource('santa-route');
    if (!source) return;

    var now = performance.now();
    if (now - lastRouteTrailUpdate < 90) return;
    lastRouteTrailUpdate = now;

    var lines = googleState ? buildGoogleVisitedRouteLines(currentSantaTimeMs) : buildWowVisitedRouteLines(state);
    source.setData({
      type: 'Feature',
      properties: {},
      geometry: { type: 'MultiLineString', coordinates: lines }
    });
  }

  function loadDeliveryManifest() {
    if (deliveryManifest) return Promise.resolve(deliveryManifest);
    return fetch('/data/santa-world/manifest.json', { cache: 'force-cache' })
      .then(function (response) {
        if (!response.ok) throw new Error('Santa world manifest ' + response.status);
        return response.json();
      })
      .then(function (data) {
        deliveryManifest = data;
        return data;
      })
      .catch(function (error) {
        console.error(error);
        deliveryManifest = { total_places: 0, zones: [], chunk_size: 5000 };
        return deliveryManifest;
      });
  }

  function deliveryChunkKey(index, chunkIndex) {
    return index + ':' + chunkIndex;
  }

  function loadDeliveryChunk(index, chunkIndex) {
    var stop = route[index];
    if (!stop || !stop.chunkBase || !stop.chunkCount) return Promise.resolve([]);
    var ci = Math.max(0, Math.min(stop.chunkCount - 1, Number(chunkIndex || 0)));
    var key = deliveryChunkKey(index, ci);
    if (deliveryCache[key]) return Promise.resolve(deliveryCache[key]);
    if (deliveryPending[key]) return deliveryPending[key];

    var filename = String(ci).padStart(4, '0') + '.json';
    deliveryPending[key] = fetch(stop.chunkBase + filename, { cache: 'force-cache' })
      .then(function (response) {
        if (!response.ok) throw new Error('Santa place chunk ' + response.status);
        return response.json();
      })
      .then(function (data) {
        var places = Array.isArray(data && data.places) ? data.places : [];
        deliveryCache[key] = places;
        delete deliveryPending[key];
        return places;
      })
      .catch(function () {
        deliveryCache[key] = [];
        delete deliveryPending[key];
        return [];
      });

    return deliveryPending[key];
  }

  function primeDeliveryRegions(index, phaseT) {
    var stop = route[index];
    if (!stop || !stop.chunkCount || !stop.placeCount) return;
    var progress = Math.max(0, Math.min(1, Number(phaseT || 0)));
    var globalIndex = Math.min(stop.placeCount - 1, Math.floor(progress * Math.max(1, stop.placeCount - 1)));
    var chunkIndex = Math.floor(globalIndex / Math.max(1, stop.chunkSize));
    [chunkIndex, chunkIndex + 1].forEach(function (ci) {
      if (ci >= 0 && ci < stop.chunkCount) loadDeliveryChunk(index, ci);
    });
  }

  function interpolateLocal(a, b, t) {
    var e = Math.max(0, Math.min(1, t));
    return {
      lng: normaliseLng(a.lng + shortestLngDelta(a.lng, b.lng) * e),
      lat: lerp(a.lat, b.lat, e)
    };
  }

  function getJourneyState(progress) {
    var segment = getDisplaySegment(progress);
    var from = route[segment.index];
    var to = route[segment.destinationIndex];
    var destinationIndex = segment.destinationIndex;

    if (segment.status === 'waiting') {
      return {
        mode: 'waiting', segment: segment, from: route[0], to: route[1], destinationIndex: 1,
        progress: 0, pos: { lng: route[0].lng, lat: route[0].lat }, placeIndex: 0, placeCount: 0,
        placeName: 'North Pole', hopKm: 0, headingTarget: route[1]
      };
    }

    if (segment.status === 'home') {
      var home = route[route.length - 1];
      return {
        mode: 'home', segment: segment, from: home, to: home, destinationIndex: route.length - 1,
        progress: 1, pos: { lng: home.lng, lat: home.lat }, placeIndex: 0, placeCount: 0,
        placeName: 'North Pole', hopKm: 0, headingTarget: home
      };
    }

    if (segment.mode === 'flight' || !to || !to.dataKey) {
      var flightT = segment.t;
      return {
        mode: 'flight', segment: segment, from: from, to: to, destinationIndex: destinationIndex,
        progress: flightT, pos: interpolateSegment(from, to, flightT), placeIndex: 0,
        placeCount: Number(to && to.placeCount || 0), placeName: from.city,
        hopKm: haversineKm(from, to), headingTarget: to
      };
    }

    var localT = Math.max(0, Math.min(1, segment.t));
    var placeCount = Math.max(0, Number(to.placeCount || 0));
    if (placeCount < 2) {
      return {
        mode: 'delivery', segment: segment, from: from, to: to, destinationIndex: destinationIndex,
        progress: localT, pos: { lng: to.lng, lat: to.lat }, placeIndex: 0, placeCount: placeCount,
        placeName: to.city, hopKm: 0, headingTarget: to
      };
    }

    var floatIndex = localT * (placeCount - 1);
    var placeIndex = Math.min(placeCount - 2, Math.floor(floatIndex));
    var part = floatIndex - placeIndex;
    var chunkSize = Math.max(1, Number(to.chunkSize || 5000));
    var chunkIndex = Math.floor(placeIndex / chunkSize);
    var localIndex = placeIndex % chunkSize;
    var chunk = deliveryCache[deliveryChunkKey(destinationIndex, chunkIndex)] || [];
    var nextChunk = deliveryCache[deliveryChunkKey(destinationIndex, chunkIndex + 1)] || [];
    var aRaw = chunk[localIndex] || null;
    var bRaw = chunk[localIndex + 1] || nextChunk[0] || null;

    if (!aRaw || !bRaw) {
      loadDeliveryChunk(destinationIndex, chunkIndex);
      if (chunkIndex + 1 < to.chunkCount) loadDeliveryChunk(destinationIndex, chunkIndex + 1);
      return {
        mode: 'delivery', segment: segment, from: from, to: to, destinationIndex: destinationIndex,
        progress: localT, pos: { lng: to.lng, lat: to.lat }, placeIndex: placeIndex, placeCount: placeCount,
        placeName: to.city, hopKm: 0, headingTarget: to
      };
    }

    var a = { city: aRaw[0], lat: Number(aRaw[1]), lng: Number(aRaw[2]) };
    var b = { city: bRaw[0], lat: Number(bRaw[1]), lng: Number(bRaw[2]) };
    return {
      mode: 'delivery', segment: segment, from: from, to: to, destinationIndex: destinationIndex,
      progress: localT, pos: interpolateLocal(a, b, part), placeIndex: placeIndex, placeCount: placeCount,
      placeName: a.city || to.city, hopKm: haversineKm(a, b), headingTarget: b
    };
  }

  function renderTrailHistory() {
    if (!map) return;
    var source = map.getSource('santa-local-trail');
    if (!source) return;

    var lines = visitedTrails.slice();
    if (localTrail.length > 1) lines.push(localTrail.slice());

    source.setData({
      type: 'Feature',
      properties: {},
      geometry: { type: 'MultiLineString', coordinates: lines }
    });
  }

  function finishLocalTrail() {
    if (localTrail.length > 1) {
      visitedTrails.push(localTrail.slice());
    }
    localTrail = [];
    currentTrailDestinationIndex = null;
    lastTrailPlaceIndex = -1;
    renderTrailHistory();
  }

  function resetJourneyTrails() {
    localTrail = [];
    visitedTrails = [];
    currentTrailDestinationIndex = null;
    lastTrailPlaceIndex = -1;
    lastTrailUpdate = 0;
    renderTrailHistory();
  }

  function updateLocalTrail(state) {
    if (!map || !map.getSource('santa-local-trail')) return;

    if (state.mode !== 'delivery') {
      if (localTrail.length) finishLocalTrail();
      return;
    }

    if (currentTrailDestinationIndex !== state.destinationIndex) {
      if (localTrail.length > 1) visitedTrails.push(localTrail.slice());
      localTrail = [];
      currentTrailDestinationIndex = state.destinationIndex;
      lastTrailPlaceIndex = -1;
    }

    var now = performance.now();
    if (state.placeIndex === lastTrailPlaceIndex && now - lastTrailUpdate < 100) return;
    lastTrailUpdate = now;
    lastTrailPlaceIndex = state.placeIndex;

    var point = [state.pos.lng, state.pos.lat];
    var previous = localTrail.length ? localTrail[localTrail.length - 1] : null;
    if (!previous || Math.abs(previous[0] - point[0]) > .00001 || Math.abs(previous[1] - point[1]) > .00001) {
      localTrail.push(point);
    }

    // Keep the complete shape while bounding long live sessions on mobile.
    // When it grows large, downsample the whole trail instead of deleting its beginning.
    if (localTrail.length > 800) {
      var compact = [];
      for (var i = 0; i < localTrail.length; i += 2) compact.push(localTrail[i]);
      var finalPoint = localTrail[localTrail.length - 1];
      var compactLast = compact[compact.length - 1];
      if (!compactLast || compactLast[0] !== finalPoint[0] || compactLast[1] !== finalPoint[1]) compact.push(finalPoint);
      localTrail = compact;
    }

    renderTrailHistory();
  }

  function lockPageZoomOutsideMap() {
    var lastTouchEnd = 0;

    function isInsideMap(target) {
      return !!(target && target.closest && target.closest('#santaMap'));
    }

    // Preserve Mapbox double-click zoom, but never allow the browser to zoom
    // the surrounding Santa UI/cards.
    document.addEventListener('dblclick', function (event) {
      if (isInsideMap(event.target)) return;
      event.preventDefault();
      event.stopPropagation();
    }, { capture: true, passive: false });

    document.addEventListener('touchend', function (event) {
      if (isInsideMap(event.target)) {
        lastTouchEnd = 0;
        return;
      }

      var now = Date.now();
      if (now - lastTouchEnd <= 360) {
        event.preventDefault();
      }
      lastTouchEnd = now;
    }, { capture: true, passive: false });

    // Safari exposes native pinch zoom through gesture events. Keep those
    // gestures available to Mapbox, but block them everywhere else.
    ['gesturestart', 'gesturechange', 'gestureend'].forEach(function (type) {
      document.addEventListener(type, function (event) {
        if (isInsideMap(event.target)) return;
        event.preventDefault();
      }, { capture: true, passive: false });
    });
  }

  function setupVisualViewport() {
    var resizeFrame = 0;
    var resizeTimers = [];

    function resizeMap() {
      cancelAnimationFrame(resizeFrame);
      resizeFrame = requestAnimationFrame(function () {
        if (!map) return;
        try { map.resize(); } catch (_err) {}
      });
    }

    function settleResize() {
      resizeMap();
      resizeTimers.forEach(clearTimeout);
      resizeTimers = [
        setTimeout(resizeMap, 80),
        setTimeout(resizeMap, 260),
        setTimeout(resizeMap, 700)
      ];
    }

    window.addEventListener('resize', settleResize, { passive: true });
    window.addEventListener('orientationchange', settleResize, { passive: true });
    window.addEventListener('pageshow', settleResize, { passive: true });

    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', settleResize, { passive: true });
      window.visualViewport.addEventListener('scroll', resizeMap, { passive: true });
    }

    settleResize();
  }

  function removeMapboxBrandingControls() {
    var mapRoot = document.getElementById('santaMap');
    if (!mapRoot) return;

    mapRoot.querySelectorAll('.mapboxgl-ctrl-logo, .mapboxgl-ctrl-attrib').forEach(function (node) {
      node.remove();
    });
  }

  function watchForMapboxBrandingControls() {
    var mapRoot = document.getElementById('santaMap');
    if (!mapRoot || typeof MutationObserver === 'undefined') return;

    var observer = new MutationObserver(function () {
      removeMapboxBrandingControls();
    });

    observer.observe(mapRoot, { childList: true, subtree: true });
    removeMapboxBrandingControls();
  }

  function setLayerPaintSafe(layerId, property, value) {
    try { map.setPaintProperty(layerId, property, value); } catch (_err) {}
  }

  function applySnowCoveredMapStyle() {
    if (!map) return;

    try {
      map.setFog({
        color: '#dcecf7',
        'high-color': '#a9cde4',
        'horizon-blend': .10,
        'space-color': '#01040a',
        'star-intensity': .88
      });
    } catch (_err) {}

    var style = null;
    try { style = map.getStyle(); } catch (_err) {}
    var layers = style && Array.isArray(style.layers) ? style.layers : [];

    layers.forEach(function (layer) {
      var id = String(layer.id || '').toLowerCase();
      var sourceLayer = String(layer['source-layer'] || '').toLowerCase();
      var type = layer.type;
      var key = id + ' ' + sourceLayer;

      if (type === 'background') {
        setLayerPaintSafe(layer.id, 'background-color', '#eef6fb');
        return;
      }

      if (type === 'fill') {
        if (/water|ocean|river|lake|reservoir/.test(key)) {
          setLayerPaintSafe(layer.id, 'fill-color', '#88c8e8');
          setLayerPaintSafe(layer.id, 'fill-opacity', .96);
        } else if (/park|grass|wood|forest|landcover|landuse|national-park|natural/.test(key)) {
          setLayerPaintSafe(layer.id, 'fill-color', '#edf5f7');
          setLayerPaintSafe(layer.id, 'fill-opacity', .94);
        } else if (/building/.test(key)) {
          setLayerPaintSafe(layer.id, 'fill-color', '#dce7ed');
          setLayerPaintSafe(layer.id, 'fill-outline-color', '#c8d7df');
        } else if (/land|island|place/.test(key)) {
          setLayerPaintSafe(layer.id, 'fill-color', '#f8fbfd');
        }
        return;
      }

      if (type === 'fill-extrusion' && /building/.test(key)) {
        setLayerPaintSafe(layer.id, 'fill-extrusion-color', '#e3edf2');
        setLayerPaintSafe(layer.id, 'fill-extrusion-opacity', .88);
        return;
      }

      if (type === 'line') {
        if (/water|river|stream|canal/.test(key)) {
          setLayerPaintSafe(layer.id, 'line-color', '#73b9dc');
          setLayerPaintSafe(layer.id, 'line-opacity', .88);
        } else if (/road|street|motorway|trunk|primary|secondary|tertiary|path|bridge|tunnel/.test(key)) {
          setLayerPaintSafe(layer.id, 'line-color', '#ffffff');
          setLayerPaintSafe(layer.id, 'line-opacity', .72);
        } else if (/admin|boundary|border/.test(key)) {
          setLayerPaintSafe(layer.id, 'line-color', '#9eb7c7');
          setLayerPaintSafe(layer.id, 'line-opacity', .72);
        }
        return;
      }

      if (type === 'symbol') {
        if (/place|settlement|country|state|city|town|village|road|poi/.test(key)) {
          setLayerPaintSafe(layer.id, 'text-color', '#294457');
          setLayerPaintSafe(layer.id, 'text-halo-color', 'rgba(255,255,255,.96)');
          setLayerPaintSafe(layer.id, 'text-halo-width', 1.25);
        }
      }
    });

    // Snowy 3D relief: terrain + subtle cool hillshade.
    try {
      if (!map.getSource('wow-snow-dem')) {
        map.addSource('wow-snow-dem', {
          type: 'raster-dem',
          url: 'mapbox://mapbox.mapbox-terrain-dem-v1',
          tileSize: 512,
          maxzoom: 14
        });
      }
      map.setTerrain({ source: 'wow-snow-dem', exaggeration: .62 });
    } catch (_err) {}

    try {
      if (!map.getLayer('wow-snow-hillshade')) {
        var beforeId = null;
        var currentStyle = map.getStyle();
        if (currentStyle && Array.isArray(currentStyle.layers)) {
          for (var i = 0; i < currentStyle.layers.length; i++) {
            if (currentStyle.layers[i].type === 'symbol') { beforeId = currentStyle.layers[i].id; break; }
          }
        }
        map.addLayer({
          id: 'wow-snow-hillshade',
          type: 'hillshade',
          source: 'wow-snow-dem',
          paint: {
            'hillshade-shadow-color': '#8fa8b8',
            'hillshade-highlight-color': '#ffffff',
            'hillshade-accent-color': '#d7e5ed',
            'hillshade-exaggeration': .24
          }
        }, beforeId || undefined);
      }
    } catch (_err) {}
  }

  function initMap() {
    window.mapboxgl.accessToken = token;
    map = new window.mapboxgl.Map({
      container: 'santaMap',
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [15, 28],
      zoom: 1.4,
      minZoom: 1,
      maxZoom: 7,
      projection: 'globe',
      attributionControl: false,
      antialias: true
    });

    removeMapboxBrandingControls();
    watchForMapboxBrandingControls();

    map.addControl(new window.mapboxgl.NavigationControl({
      showCompass: true,
      showZoom: true,
      visualizePitch: false
    }), 'bottom-right');

    map.on('style.load', function () {
      removeMapboxBrandingControls();
      applySnowCoveredMapStyle();
    });

    map.on('load', function () {
      removeMapboxBrandingControls();
      map.addSource('santa-route', {
        type: 'geojson',
        data: {
          type: 'Feature',
          properties: {},
          geometry: { type: 'MultiLineString', coordinates: [] }
        }
      });

      map.addLayer({
        id: 'santa-route-shadow',
        type: 'line',
        source: 'santa-route',
        layout: { 'line-join': 'round', 'line-cap': 'round' },
        paint: {
          'line-color': '#ffffff',
          'line-width': 6,
          'line-opacity': .82
        }
      });

      map.addLayer({
        id: 'santa-route-line',
        type: 'line',
        source: 'santa-route',
        layout: { 'line-join': 'round', 'line-cap': 'round' },
        paint: {
          'line-color': '#d93025',
          'line-width': 3.2,
          'line-dasharray': [1.2, 1.2],
          'line-opacity': .95
        }
      });

      map.addSource('santa-local-trail', {
        type: 'geojson',
        data: {
          type: 'Feature',
          properties: {},
          geometry: { type: 'MultiLineString', coordinates: [] }
        }
      });

      map.addLayer({
        id: 'santa-local-trail-line',
        type: 'line',
        source: 'santa-local-trail',
        layout: { 'line-join': 'round', 'line-cap': 'round' },
        paint: {
          'line-color': '#f9ab00',
          'line-width': 3,
          'line-opacity': .88
        }
      });

      var santaEl = document.createElement('div');
      santaEl.className = 'santa-marker is-flight';
      santaEl.setAttribute('aria-hidden', 'true');
      santaEl.innerHTML =
        '<div class="santa-marker-visual">' +
          '<img class="santa-marker-flight" src="/images/santa-tracker/santa-sleigh.svg" alt="">' +
          '<img class="santa-marker-walk" src="/images/santa-tracker/santa-walking.svg" alt="">' +
        '</div>';
      santaMarker = new window.mapboxgl.Marker({
        element: santaEl,
        anchor: 'center',
        rotationAlignment: 'viewport'
      })
        .setLngLat([route[0].lng, 84])
        .addTo(map);

      route.forEach(function (stop, index) {
        var important = index === 0 || index === route.length - 1 || index < 6 || index % 12 === 0 || Number(stop.placeCount || 0) >= 75000;
        if (!important) return;
        var el = document.createElement('button');
        el.type = 'button';
        el.className = 'santa-map-stop';
        el.setAttribute('aria-label', stop.city + ', ' + stop.country);
        el.addEventListener('click', function () { focusStop(index); });
        var popup = new window.mapboxgl.Popup({ offset: 14, closeButton: false })
          .setHTML('<strong>' + escapeHtml(stop.city) + '</strong><br><span>' + escapeHtml(stop.country) + '</span>');
        var marker = new window.mapboxgl.Marker({ element: el, anchor: 'center' })
          .setLngLat([stop.lng, Math.min(stop.lat, 84)])
          .setPopup(popup)
          .addTo(map);
        stopMarkers.push({ marker: marker, el: el, routeIndex: index });
      });

      map.flyTo({
        center: [160, -10],
        zoom: 1.8,
        pitch: 0,
        duration: 1400,
        essential: true
      });
    });
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }

  function updateMode() {
    var timeMs = getEffectiveSantaTimeMs();
    var activeWindow = santaWindowForYear(new Date(timeMs).getUTCFullYear());
    isChristmasLive = fixedAtMs === null && !forceDemo && !normalSpeedDemo && timeMs >= activeWindow.startMs && timeMs < activeWindow.endMs;

    if (googleSantaExact && fixedAtMs === null && !forceDemo && !normalSpeedDemo) {
      modeChip.classList.remove('is-preview');
      modeChipText.textContent = 'Google route synced';
      countdownLabel.textContent = 'Santa clock';
      countdownCopy.textContent = 'Primary Santa position is synced to Google’s published Christmas Eve route.';
    } else if (fixedAtMs !== null) {
      modeChip.classList.add('is-preview');
      modeChipText.textContent = 'Fixed-time test';
      countdownLabel.textContent = 'Santa clock';
      countdownCopy.textContent = 'Frozen test position for ' + formatUtcClock(timeMs, true) + '.';
    } else if (forceDemo) {
      modeChip.classList.add('is-preview');
      modeChipText.textContent = 'Fast demo';
      countdownLabel.textContent = 'Accelerated Santa clock';
      countdownCopy.textContent = 'Accelerated preview of the Google-aligned 25-hour Christmas-night schedule.';
    } else if (winterDemo) {
      modeChip.classList.add('is-preview');
      modeChipText.textContent = 'Winter globe · 1×';
      countdownLabel.textContent = 'Normal-speed Santa clock';
      countdownCopy.textContent = 'Demo 3: normal-speed Santa route on the snow-covered winter globe.';
    } else if (normalSpeedDemo) {
      modeChip.classList.add('is-preview');
      modeChipText.textContent = 'Demo · 1× speed';
      countdownLabel.textContent = 'Normal-speed Santa clock';
      countdownCopy.textContent = 'Real-time demo: 1 real second equals 1 Santa second. No acceleration.';
    } else if (isChristmasLive) {
      modeChip.classList.remove('is-preview');
      modeChipText.textContent = 'Christmas Eve live';
      countdownLabel.textContent = 'Santa clock';
      countdownCopy.textContent = 'Live exact position from the canonical UTC route schedule.';
    } else {
      modeChip.classList.add('is-preview');
      modeChipText.textContent = 'Waiting for Santa';
      countdownLabel.textContent = 'Christmas Eve countdown';
      countdownCopy.textContent = 'Departs 24 Dec 10:00 UTC · Home 25 Dec 11:00 UTC.';
    }
  }

  function getJourneyProgress() {
    var timeMs = getEffectiveSantaTimeMs();
    var scheduleState = getScheduleSegmentAt(timeMs);
    return scheduleState.overallProgress;
  }

  function getDisplaySegment(progress) {
    var timeMs = currentSantaTimeMs !== null ? currentSantaTimeMs : getEffectiveSantaTimeMs();
    var scheduleState = getScheduleSegmentAt(timeMs);
    return {
      index: scheduleState.index,
      destinationIndex: scheduleState.destinationIndex,
      t: scheduleState.phaseT,
      mode: scheduleState.mode,
      status: scheduleState.status,
      overallProgress: scheduleState.overallProgress,
      scheduleItem: scheduleState.scheduleItem || null
    };
  }

  function segmentTelemetry(index, t, position) {
    var from = route[index];
    var to = route[index + 1];
    var remaining = haversineKm(position, to);
    var item = journeySchedule[index] || null;
    var flightHours = item ? Math.max(.001, item.flightMs / 3600000) : 1;
    var speed = Math.round(Math.max(4200, haversineKm(from, to) / flightHours));
    var deterministicWave = Math.sin((index + t) * 12.9898) * 160;
    var altitude = Math.round(8100 + Math.sin(Math.PI * t) * 2400 + deterministicWave);
    var minutes = item ? Math.max(1, Math.ceil((1 - t) * item.flightMs / 60000)) : 1;
    return {
      remaining: remaining,
      minutes: minutes,
      speed: speed,
      altitude: altitude
    };
  }

  function updateCountdown() {
    var timeMs = getEffectiveSantaTimeMs();
    var state = getScheduleSegmentAt(timeMs);
    var activeWindow = scheduleWindow || resolveScheduleWindow(timeMs);

    if (fixedAtMs !== null || forceDemo || state.status === 'live') {
      countdownValue.textContent = formatUtcClock(timeMs, false);
      if (santaClockTextEl) {
        santaClockTextEl.textContent = 'Exact tracker time: ' + formatUtcClock(timeMs, true) + '.';
      }
      return;
    }

    if (state.status === 'home') {
      countdownValue.textContent = 'Santa is home 🎅';
      return;
    }

    var target = activeWindow.startMs;
    var delta = Math.max(0, target - timeMs);
    var days = Math.floor(delta / 86400000);
    var hours = Math.floor((delta % 86400000) / 3600000);
    var mins = Math.floor((delta % 3600000) / 60000);
    var secs = Math.floor((delta % 60000) / 1000);
    countdownValue.textContent = days + 'd ' + String(hours).padStart(2, '0') + 'h ' + String(mins).padStart(2, '0') + 'm ' + String(secs).padStart(2, '0') + 's';
  }

  function renderRouteList(activeIndex) {
    routeList.innerHTML = route.map(function (stop, index) {
      var state = index < activeIndex ? ' is-past' : (index === activeIndex ? ' is-current' : '');
      var arrivalMs = null;

      if (scheduleWindow) {
        if (index === 0) {
          arrivalMs = scheduleWindow.startMs;
        } else if (index === route.length - 1) {
          arrivalMs = scheduleWindow.endMs;
        } else {
          var item = journeySchedule[index - 1];
          if (item) arrivalMs = scheduleWindow.startMs + item.flightEndOffsetMs;
        }
      }

      var time = arrivalMs !== null ? formatUtcClock(arrivalMs, false).replace(':00 UTC', ' UTC') : '— UTC';

      return '<button type="button" class="santa-stop' + state + '" data-stop="' + index + '">' +
        '<span class="santa-stop-icon">' + stop.emoji + '</span>' +
        '<span class="santa-stop-main"><strong>' + escapeHtml(stop.city) + '</strong><span>' + escapeHtml(stop.country) + (stop.timezone ? ' · ' + Number(stop.placeCount || 0).toLocaleString('en-GB') + ' places' : '') + '</span></span>' +
        '<span class="santa-stop-time">' + time + '</span>' +
      '</button>';
    }).join('');
  }

  function updateStopMarkers(activeIndex) {
    stopMarkers.forEach(function (item) {
      var index = Number(item.routeIndex || 0);
      item.el.classList.toggle('is-past', index < activeIndex);
      item.el.classList.toggle('is-current', index === activeIndex);
    });
  }

  function focusStop(index) {
    if (!map || !route[index]) return;
    followSanta = false;
    followBtn.textContent = '◎';
    map.flyTo({
      center: [route[index].lng, Math.max(-75, Math.min(75, route[index].lat))],
      zoom: Math.max(map.getZoom(), 3.5),
      duration: 900,
      essential: true
    });
    showToast(route[index].city + ', ' + route[index].country);
  }

  function showToast(message) {
    toastEl.textContent = message;
    toastEl.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove('is-visible'); }, 2200);
  }

  function calculateDeliveryOnlyPresents(state) {
    var totalPlaces = Math.max(1, Number(deliveryManifest && deliveryManifest.total_places || 0));
    if (!state || !state.segment) return 0;
    if (state.mode === 'waiting') return 0;
    if (state.mode === 'home') return SANTA_PRESENT_TARGET;

    var item = state.segment.scheduleItem;
    if (!item) return 0;

    // Critical rule: flights never deliver presents. The count is exactly the
    // cumulative total from all completed delivery zones until Santa reaches
    // the next populated-place delivery phase.
    var deliveredPlaces = Math.max(0, Number(item.placeStart || 0));

    if (state.mode === 'delivery') {
      var phase = Math.max(0, Math.min(1, Number(state.progress || 0)));
      deliveredPlaces += Math.max(0, Number(item.placeCount || 0)) * phase;
    }

    var ratio = Math.max(0, Math.min(1, deliveredPlaces / totalPlaces));
    return Math.floor(SANTA_PRESENT_TARGET * ratio);
  }

  function animate() {
    var progress = getJourneyProgress();

    // Preview mode loops. Only clear visited delivery trails when a whole new
    // Christmas journey begins, never when Santa simply moves to the next city.
    if (forceDemo && progress + .02 < lastJourneyProgress) resetJourneyTrails();
    lastJourneyProgress = progress;

    var state = getJourneyState(progress);
    var googleState = getGoogleRouteState(currentSantaTimeMs);
    var segment = state.segment;
    var from = state.from;
    var to = state.to;
    var pos = googleState ? googleState.pos : state.pos;
    var telemetry = segmentTelemetry(segment.index, state.mode === 'flight' ? state.progress : 1, pos);
    var activeHub = state.mode === 'delivery' ? state.destinationIndex : segment.index;


    if (state.destinationIndex > 0 && state.destinationIndex < route.length - 1) primeDeliveryRegions(state.destinationIndex, state.mode === 'delivery' ? state.progress : 0);

    updateLongHaulTrail(state, googleState);

    if (santaMarker) {
      santaMarker.setLngLat([pos.lng, pos.lat]);
      var el = santaMarker.getElement();
      var isDeliveringVisual = googleState ? googleState.mode === 'google-stop' : state.mode === 'delivery';
      el.classList.toggle('is-delivering', isDeliveringVisual);
      el.classList.toggle('is-flight', !isDeliveringVisual);

      var headingTarget = googleState && googleState.headingTarget ? googleState.headingTarget : (state.headingTarget || to);
      var heading = bearingDeg(pos, headingTarget);
      var flightAsset = el.querySelector('.santa-marker-flight');
      if (flightAsset) {
        var visualHeading = Math.max(-24, Math.min(24, heading * .10));
        flightAsset.style.transform = isDeliveringVisual
          ? 'translate(-50%, -50%) scale(.78)'
          : 'translate(-50%, -50%) rotate(' + visualHeading + 'deg)';
      }
    }

    updateLocalTrail(state);

    if (activeHub !== currentSegment) {
      currentSegment = activeHub;
      renderRouteList(currentSegment);
      updateStopMarkers(currentSegment);
      var active = routeList.querySelector('[data-stop="' + currentSegment + '"]');
      if (active) active.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    if (state.mode !== currentTravelMode) {
      currentTravelMode = state.mode;
      showToast(state.mode === 'delivery' ? '🎁 Rapid delivery mode' : '🛷 Long-haul flight');
    }

    if (googleState) {
      if (activityLabelEl) activityLabelEl.textContent = googleState.mode === 'google-stop' ? 'Google-synced stop' : 'Google-synced flight';
      currentCityEl.textContent = googleState.mode === 'google-stop' ? (googleState.dest.city || 'Santa') : (googleState.city || 'Santa');
      currentStatusEl.textContent = googleState.mode === 'google-stop'
        ? '🎁 ' + (googleState.dest.region || '') + ' · exact Google route time'
        : '🛷 Flying to ' + (googleState.dest.city || 'next stop') + ' · exact Google route time';
      nextCityEl.textContent = googleState.next && googleState.next.city ? googleState.next.city : 'Next stop';
      distanceStatEl.textContent = 'Google route synced';
      etaStatEl.textContent = formatUtcClock(currentSantaTimeMs, false);
    } else if (state.mode === 'waiting') {
      if (activityLabelEl) activityLabelEl.textContent = 'Santa departs from';
      currentCityEl.textContent = 'North Pole';
      currentStatusEl.textContent = '🛷 Departure: 24 Dec 10:00 UTC';
      nextCityEl.textContent = route[1] ? route[1].city : 'First stop';
      distanceStatEl.textContent = 'Route not started';
      etaStatEl.textContent = 'Waiting for Christmas Eve';
      speedStatEl.textContent = '0 km/h';
      altitudeStatEl.textContent = '0 m';
    } else if (state.mode === 'home') {
      if (activityLabelEl) activityLabelEl.textContent = 'Santa is back at';
      currentCityEl.textContent = 'North Pole';
      currentStatusEl.textContent = '🏠 Home: 25 Dec 11:00 UTC';
      nextCityEl.textContent = 'Journey complete';
      distanceStatEl.textContent = '0 km away';
      etaStatEl.textContent = 'Finished';
      speedStatEl.textContent = '0 km/h';
      altitudeStatEl.textContent = '0 m';
    } else if (state.mode === 'delivery') {
      if (activityLabelEl) activityLabelEl.textContent = 'Delivering presents in';
      currentCityEl.textContent = state.placeName || to.city;
      var localClock = formatLocalClock(currentSantaTimeMs, to.timezone);
      currentStatusEl.textContent = state.placeCount
        ? '🎁 ' + (localClock ? localClock + ' local · ' : '') + Math.min(state.placeCount, state.placeIndex + 1).toLocaleString('en-GB') + ' / ' + state.placeCount.toLocaleString('en-GB') + ' populated places'
        : '🎁 Delivering around ' + to.city;
      var nextLongIndex = Math.min(route.length - 1, state.destinationIndex + 1);
      nextCityEl.textContent = route[nextLongIndex].city;
      distanceStatEl.textContent = state.placeCount ? state.placeCount.toLocaleString('en-GB') + ' local stops' : 'Local deliveries';
      etaStatEl.textContent = 'Then long-haul';
      speedStatEl.textContent = Math.max(88000, Math.round(125000 + state.hopKm * 170)).toLocaleString('en-GB') + ' km/h';
      altitudeStatEl.textContent = Math.round(900 + Math.min(4200, state.hopKm * 5)).toLocaleString('en-GB') + ' m';
    } else {
      if (activityLabelEl) activityLabelEl.textContent = 'Santa is flying from';
      currentCityEl.textContent = from.city;
      currentStatusEl.textContent = state.progress > .72 ? '🛷 Approaching ' + to.city : '🛷 Flying to ' + to.city;
      nextCityEl.textContent = to.city;
      distanceStatEl.textContent = Math.max(0, Math.round(telemetry.remaining)).toLocaleString('en-GB') + ' km away';
      etaStatEl.textContent = telemetry.minutes <= 1 ? 'Arriving any moment' : 'About ' + telemetry.minutes + ' min';
      speedStatEl.textContent = telemetry.speed.toLocaleString('en-GB') + ' km/h';
      altitudeStatEl.textContent = telemetry.altitude.toLocaleString('en-GB') + ' m';
    }

    journeyProgressEl.style.width = (progress * 100).toFixed(2) + '%';
    updateKindnessPrompt(progress);

    var deliveryOnlyPresents = calculateDeliveryOnlyPresents(state);
    giftCountEl.textContent = deliveryOnlyPresents.toLocaleString('en-GB');

    if (map && followSanta) {
      var now = performance.now();
      var updateEvery = state.mode === 'delivery' ? 75 : 120;
      if (now - lastFrame > updateEvery) {
        var localZoom = state.hopKm > 600 ? 2.8 : (state.hopKm > 180 ? 3.5 : (window.innerWidth < 600 ? 4.3 : 4.8));
        map.easeTo({
          center: [pos.lng, pos.lat],
          zoom: state.mode === 'delivery' ? localZoom : (window.innerWidth < 600 ? 2.35 : 2.8),
          bearing: 0,
          pitch: state.mode === 'delivery' ? 34 : (window.innerWidth < 600 ? 14 : 24),
          duration: state.mode === 'delivery' ? 125 : 280,
          essential: true
        });
        lastFrame = now;
      }
    }

    requestAnimationFrame(animate);
  }

  function updateKindnessPrompt(progress) {
    if (!kindnessMoments.length || !kindnessPromptText || !kindnessPromptRef) return;
    var bounded = Math.max(0, Math.min(.9999, Number(progress) || 0));
    var index = Math.min(kindnessMoments.length - 1, Math.floor(bounded * kindnessMoments.length));
    if (index === kindnessPromptIndex) return;
    kindnessPromptIndex = index;
    kindnessPromptText.textContent = kindnessMoments[index].text;
    kindnessPromptRef.textContent = kindnessMoments[index].ref;
  }

  function openKindnessPanel() {
    if (!kindnessPanel) return;
    kindnessPanel.classList.add('is-open');
    kindnessPanel.setAttribute('aria-hidden', 'false');
    if (kindnessBackdrop) kindnessBackdrop.classList.add('is-visible');
    if (kindnessBtn) kindnessBtn.setAttribute('aria-expanded', 'true');
    if (window.innerWidth <= 900 && routePanel) routePanel.classList.remove('is-mobile-open');
  }

  function closeKindnessPanel() {
    if (!kindnessPanel) return;
    kindnessPanel.classList.remove('is-open');
    kindnessPanel.setAttribute('aria-hidden', 'true');
    if (kindnessBackdrop) kindnessBackdrop.classList.remove('is-visible');
    if (kindnessBtn) kindnessBtn.setAttribute('aria-expanded', 'false');
  }

  function setupKindnessPanel() {
    updateKindnessPrompt(0);
    if (kindnessBtn) kindnessBtn.addEventListener('click', function () {
      if (kindnessPanel && kindnessPanel.classList.contains('is-open')) closeKindnessPanel();
      else openKindnessPanel();
    });
    if (kindnessPrompt) kindnessPrompt.addEventListener('click', openKindnessPanel);
    if (kindnessCloseBtn) kindnessCloseBtn.addEventListener('click', closeKindnessPanel);
    if (kindnessBackdrop) kindnessBackdrop.addEventListener('click', closeKindnessPanel);
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeKindnessPanel();
    });
  }

  function setupRoutePanel() {
    renderRouteList(0);

    routeList.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-stop]');
      if (!btn) return;
      var index = Number(btn.getAttribute('data-stop'));
      if (Number.isFinite(index)) focusStop(index);
      if (window.innerWidth <= 900) routePanel.classList.remove('is-mobile-open');
    });

    routeBtn.addEventListener('click', function () {
      closeKindnessPanel();
      if (window.innerWidth <= 900) {
        routePanel.classList.toggle('is-mobile-open');
        routePanel.classList.remove('is-hidden');
      } else {
        routePanel.classList.toggle('is-hidden');
      }
    });

    followBtn.addEventListener('click', function () {
      followSanta = !followSanta;
      followBtn.textContent = followSanta ? '◉' : '◎';
      followBtn.setAttribute('aria-label', followSanta ? 'Stop following Santa' : 'Follow Santa');
      showToast(followSanta ? 'Following Santa' : 'Free camera');
    });

    nextStopBtn.addEventListener('click', function () {
      var nextIndex = Math.min(route.length - 1, currentSegment + 1);
      focusStop(nextIndex);
    });
  }

  function setupSnow() {
    var canvas = document.getElementById('snowCanvas');
    var ctx = canvas.getContext('2d');
    var flakes = [];
    var dpr = Math.min(2, window.devicePixelRatio || 1);
    var width = 0;
    var height = 0;

    function resize() {
      var rect = app.getBoundingClientRect();
      width = Math.max(1, Math.round(rect.width || document.documentElement.clientWidth || 1));
      height = Math.max(1, Math.round(rect.height || document.documentElement.clientHeight || 1));
      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      flakes = Array.from({ length: Math.min(100, Math.round(width / 8)) }, function () {
        return {
          x: Math.random() * width,
          y: Math.random() * height,
          r: 1 + Math.random() * 2.2,
          v: .35 + Math.random() * 1.0,
          sway: Math.random() * 2 - 1
        };
      });
    }

    function frame() {
      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = 'rgba(255,255,255,.78)';
      flakes.forEach(function (f) {
        f.y += f.v;
        f.x += Math.sin(f.y / 45) * .12 + f.sway * .08;
        if (f.y > height + 8) {
          f.y = -8;
          f.x = Math.random() * width;
        }
        ctx.beginPath();
        ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
        ctx.fill();
      });
      requestAnimationFrame(frame);
    }

    resize();
    window.addEventListener('resize', resize, { passive: true });
    if (window.visualViewport) window.visualViewport.addEventListener('resize', resize, { passive: true });
    frame();
  }

  function boot() {
    lockPageZoomOutsideMap();
    setupVisualViewport();
    updateMode();
    updateCountdown();
    setupKindnessPanel();
    setupRoutePanel();
    setupSnow();
    setInterval(updateCountdown, 1000);
    setInterval(function () {
      var now = Date.now() + Number(googleTimeOffsetMs || 0);
      var w = santaWindowForYear(new Date(now).getUTCFullYear());
      var closeToFlight = Math.abs(now - w.startMs) < 2 * 86400000 || (now >= w.startMs && now <= w.endMs);
      if (closeToFlight && !forceDemo && !normalSpeedDemo) loadGoogleSantaRoute(true).then(updateMode);
    }, 60000);

    Promise.all([loadDeliveryManifest(), loadGoogleSantaRoute(false)])
      .then(function (results) {
        var manifest = results[0];
        applyWorldManifest(manifest);
        buildJourneySchedule(manifest);
        scheduleWindow = resolveScheduleWindow(fixedAtMs !== null ? fixedAtMs : Date.now());
        updateMode();
        updateCountdown();
        renderRouteList(0);
        if (santaClockTextEl) {
          santaClockTextEl.textContent = Number(manifest.total_places || 0).toLocaleString('en-GB') + ' populated places · ' + Number(manifest.zone_count || 0).toLocaleString('en-GB') + ' timezones · Google-aligned night schedule.';
        }
        var initialState = getScheduleSegmentAt(getEffectiveSantaTimeMs());
        if (initialState.destinationIndex > 0 && initialState.destinationIndex < route.length - 1) {
          primeDeliveryRegions(initialState.destinationIndex, initialState.mode === 'delivery' ? initialState.phaseT : 0);
        }
        return loadMapbox();
      })
      .then(initMap)
      .then(function () {
        followBtn.textContent = '◉';
        setTimeout(function () {
          try { if (map) map.resize(); } catch (_err) {}
        }, 80);
        animate();
      })
      .catch(function (error) {
        console.error(error);
        showToast('The Santa tracker could not load its world route.');
      });
  }

  boot();
})();
</script>
@endsection
