{{-- resources/views/events/show.blade.php --}}
@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? (($event['title'] ?? 'Event').' | We Offer Wellness™') }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
@endpush

@push('styles')
  <style>
    .wow-event-page{
      background:
        radial-gradient(circle at top left, rgba(230, 0, 35, 0.06), transparent 28%),
        radial-gradient(circle at top right, rgba(36, 89, 77, 0.06), transparent 26%),
        linear-gradient(180deg, #fff, #fcfcfd 45%, #fff);
    }
    .wow-event-shell{
      width:min(100% - 32px, 1320px);
      margin:0 auto;
    }
    .wow-event-hero{
      display:grid;
      grid-template-columns:minmax(0, 1.1fr) minmax(320px, 0.9fr);
      gap:24px;
      align-items:stretch;
      padding:34px 0 22px;
    }
    .wow-event-hero__content,
    .wow-event-hero__media,
    .wow-event-panel,
    .wow-event-schedule,
    .wow-event-related{
      border:1px solid rgba(15, 23, 42, 0.08);
      background:#fff;
      box-shadow:0 14px 42px rgba(16,24,40,.06);
    }
    .wow-event-hero__content{
      border-radius:22px;
      padding:28px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      min-height:100%;
    }
    .wow-event-kicker{
      margin:0 0 10px;
      color:#344054;
      font-size:13px;
      font-weight:300;
      letter-spacing:0.16em;
      text-transform:uppercase;
    }
    .wow-event-title{
      margin:0;
      color:#101828;
      font-family:"Playfair Display", Georgia, "Times New Roman", serif;
      font-size:clamp(42px, 6vw, 78px);
      font-weight:500;
      line-height:0.94;
      letter-spacing:-0.06em;
      max-width:12ch;
    }
    .wow-event-summary{
      margin:18px 0 0;
      color:#596275;
      font-size:18px;
      line-height:1.65;
      max-width:68ch;
    }
    .wow-event-meta{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:22px;
    }
    .wow-pill{
      display:inline-flex;
      align-items:center;
      min-height:30px;
      padding:6px 10px;
      border-radius:999px;
      background:#f2f4f7;
      color:#344054;
      border:1px solid #e5e7eb;
      font-size:12px;
      line-height:1;
      font-weight:700;
      letter-spacing:0.02em;
    }
    .wow-pill--accent{
      background:#f6d5dc;
      color:#8f1532;
      border-color:rgba(143, 21, 50, 0.15);
    }
    .wow-event-stats{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:12px;
      margin-top:24px;
    }
    .wow-stat{
      border:1px solid #edf0f2;
      border-radius:16px;
      padding:14px 16px;
      background:#fafafa;
    }
    .wow-stat span{
      display:block;
      color:#667085;
      font-size:12px;
      font-weight:700;
      letter-spacing:0.08em;
      text-transform:uppercase;
    }
    .wow-stat strong{
      display:block;
      margin-top:8px;
      color:#101828;
      font-size:16px;
      line-height:1.25;
      font-weight:700;
    }
    .wow-event-hero__media{
      border-radius:22px;
      overflow:hidden;
      position:relative;
      min-height:100%;
      background:#111827;
    }
    .wow-event-hero__media img{
      width:100%;
      height:100%;
      display:block;
      object-fit:cover;
    }
    .wow-event-hero__media::after{
      content:"Event";
      position:absolute;
      left:18px;
      top:18px;
      min-height:30px;
      display:inline-flex;
      align-items:center;
      border-radius:999px;
      background:#e60023;
      color:#fff;
      padding:0 12px;
      font-size:12px;
      font-weight:800;
      letter-spacing:0.04em;
      text-transform:uppercase;
    }
    .wow-event-main{
      display:grid;
      grid-template-columns:minmax(0, 1.2fr) minmax(300px, 0.8fr);
      gap:24px;
      align-items:start;
      padding:0 0 20px;
    }
    .wow-event-panel,
    .wow-event-schedule,
    .wow-event-related{
      border-radius:20px;
      overflow:hidden;
    }
    .wow-event-panel{
      padding:26px;
    }
    .wow-panel-head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:18px;
      margin-bottom:18px;
    }
    .wow-panel-head h2,
    .wow-section-heading h2{
      margin:0;
      color:#101828;
      font-family:"Playfair Display", Georgia, "Times New Roman", serif;
      font-weight:500;
      letter-spacing:-0.06em;
      line-height:0.96;
    }
    .wow-panel-head h2{
      font-size:clamp(30px, 4vw, 48px);
      max-width:14ch;
    }
    .wow-panel-copy{
      color:#596275;
      font-size:16px;
      line-height:1.65;
      max-width:66ch;
      margin:0;
    }
    .wow-event-body{
      color:#344054;
      font-size:16px;
      line-height:1.75;
    }
    .wow-event-body p:first-child{
      margin-top:0;
    }
    .wow-event-body a{
      color:#24594d;
      font-weight:700;
      text-decoration:none;
    }
    .wow-event-body a:hover{
      text-decoration:underline;
    }
    .wow-event-side{
      display:grid;
      gap:16px;
    }
    .wow-side-card{
      border-radius:18px;
      border:1px solid #edf0f2;
      background:#fff;
      padding:18px;
      box-shadow:0 10px 24px rgba(16,24,40,.04);
    }
    .wow-side-card h3{
      margin:0 0 12px;
      color:#101828;
      font-size:18px;
      line-height:1.2;
      letter-spacing:-0.03em;
    }
    .wow-side-list{
      display:grid;
      gap:12px;
    }
    .wow-side-row{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:12px;
      padding-bottom:12px;
      border-bottom:1px solid #edf0f2;
    }
    .wow-side-row:last-child{
      border-bottom:0;
      padding-bottom:0;
    }
    .wow-side-row span{
      color:#667085;
      font-size:12px;
      font-weight:700;
      letter-spacing:0.08em;
      text-transform:uppercase;
      flex:0 0 auto;
    }
    .wow-side-row strong{
      color:#101828;
      font-size:14px;
      line-height:1.35;
      text-align:right;
      font-weight:700;
    }
    .wow-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      width:100%;
      min-height:44px;
      border-radius:999px;
      border:1px solid #d0d5dd;
      background:#fff;
      color:#101828;
      font-weight:700;
      text-decoration:none;
      transition:transform 160ms ease, border-color 160ms ease, background 160ms ease;
    }
    .wow-btn:hover{
      transform:translateY(-1px);
      border-color:#8b9aa8;
      background:#f8fafc;
      text-decoration:none;
      color:#101828;
    }
    .wow-event-schedule{
      margin-top:8px;
      padding:28px;
    }
    .wow-section-heading{
      display:grid;
      grid-template-columns:minmax(0, 1fr) auto;
      gap:24px;
      align-items:end;
      margin-bottom:24px;
    }
    .wow-section-heading p{
      max-width:780px;
      margin:14px 0 0;
      color:#596275;
      font-size:17px;
      line-height:1.58;
    }
    .wow-event-schedule h2{
      font-size:clamp(34px, 4.8vw, 58px);
      max-width:14ch;
    }
    .wow-schedule-tabs{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-bottom:18px;
    }
    .wow-schedule-tab{
      appearance:none;
      border:1px solid #d0d5dd;
      border-radius:999px;
      background:#fff;
      color:#101828;
      padding:12px 16px;
      font:inherit;
      font-size:15px;
      font-weight:700;
      cursor:pointer;
      transition:background 160ms ease, color 160ms ease, border-color 160ms ease;
    }
    .wow-schedule-tab:hover{
      border-color:#24594d;
    }
    .wow-schedule-tab.is-active{
      background:#24594d;
      border-color:#24594d;
      color:#fff;
    }
    .wow-day{
      display:grid;
      gap:12px;
    }
    .wow-timeline{
      display:grid;
      gap:12px;
    }
    .wow-timeline-item{
      display:grid;
      grid-template-columns:150px minmax(0, 1fr);
      gap:18px;
      align-items:start;
      border:1px solid #edf0f2;
      border-radius:16px;
      background:#fcfcfd;
      padding:18px;
    }
    .wow-timeline-time{
      color:#8f1532;
      font-size:13px;
      line-height:1.2;
      font-weight:800;
      letter-spacing:0.08em;
      text-transform:uppercase;
      padding-top:3px;
    }
    .wow-timeline-item h3{
      margin:0;
      color:#101828;
      font-size:20px;
      line-height:1.15;
      letter-spacing:-0.03em;
    }
    .wow-timeline-item p{
      margin:10px 0 0;
      color:#596275;
      font-size:15px;
      line-height:1.6;
    }
    .wow-room-carousel{
      position:relative;
      display:grid;
      grid-template-columns:38px minmax(0, 1fr) 38px;
      gap:8px;
      align-items:start;
    }
    .wow-room-carousel__viewport{
      min-width:0;
      overflow-x:auto;
      overflow-y:hidden;
      scroll-behavior:smooth;
      scroll-snap-type:x mandatory;
      scrollbar-width:none;
      -webkit-overflow-scrolling:touch;
    }
    .wow-room-carousel__viewport::-webkit-scrollbar{
      display:none;
    }
    .wow-room-grid{
      display:flex;
      flex-wrap:nowrap;
      gap:12px;
      align-items:stretch;
      min-width:100%;
    }
    .wow-room{
      flex:0 0 calc((100% - 12px) / 2);
      min-width:0;
      overflow:hidden;
      border:1px solid #dfe4ea;
      border-radius:18px;
      background:#fff;
      scroll-snap-align:start;
    }
    .wow-room-grid.has-one-room .wow-room{
      flex-basis:100%;
    }
    .wow-room-grid.has-two-rooms .wow-room{
      flex-basis:calc((100% - 12px) / 2);
    }
    .wow-room--tone-1{ background:#f5f9ff; border-color:#cfe2f7; }
    .wow-room--tone-2{ background:#f6fbf8; border-color:#cfe7d8; }
    .wow-room--tone-3{ background:#faf8ff; border-color:#ddd3f5; }
    .wow-room--tone-4{ background:#fffaf0; border-color:#ead9aa; }
    .wow-room__header{
      min-height:84px;
      border-bottom:1px solid #edf0f2;
      padding:14px;
      background:#fff;
    }
    .wow-room--tone-1 .wow-room__header{ background:#eef6ff; }
    .wow-room--tone-2 .wow-room__header{ background:#effaf3; }
    .wow-room--tone-3 .wow-room__header{ background:#f5f1ff; }
    .wow-room--tone-4 .wow-room__header{ background:#fff5dc; }
    .wow-room__name{
      margin:0;
      color:#101828;
      font-size:18px;
      line-height:1.15;
      letter-spacing:-0.025em;
      font-weight:700;
    }
    .wow-room__note{
      margin:6px 0 0;
      color:#667085;
      font-size:13px;
      line-height:1.35;
    }
    .wow-room__slots{
      display:grid;
      grid-auto-rows:1fr;
      gap:12px;
      padding:12px;
      background:transparent;
    }
    .wow-session{
      height:100%;
      display:flex;
      flex-direction:column;
      border:1px solid #edf0f2;
      border-radius:16px;
      background:rgba(255,255,255,0.9);
      padding:16px;
      overflow:hidden;
    }
    .wow-session__time{
      display:block;
      margin:0 0 12px;
      color:#8f1532;
      font-size:13px;
      line-height:1.2;
      font-weight:800;
      letter-spacing:0.08em;
      text-transform:uppercase;
      flex:0 0 auto;
    }
    .wow-session__title{
      margin:0;
      color:#101828;
      font-size:20px;
      line-height:1.18;
      letter-spacing:-0.04em;
      font-weight:500;
      display:-webkit-box;
      -webkit-line-clamp:4;
      -webkit-box-orient:vertical;
      overflow:hidden;
    }
    .wow-session__desc{
      margin:10px 0 0;
      color:#596275;
      font-size:15px;
      line-height:1.5;
      display:-webkit-box;
      -webkit-line-clamp:5;
      -webkit-box-orient:vertical;
      overflow:hidden;
    }
    .wow-session__host{
      margin:auto 0 0;
      padding-top:12px;
      color:#596275;
      font-size:14px;
      line-height:1.35;
    }
    .wow-session__host strong{
      color:#101828;
      font-weight:700;
    }
    .wow-room__empty{
      height:240px;
      display:flex;
      align-items:center;
      justify-content:center;
      border:1px dashed #d0d5dd;
      border-radius:16px;
      background:rgba(255,255,255,0.65);
      padding:16px;
      color:#667085;
      font-size:15px;
      line-height:1.45;
      text-align:center;
    }
    .wow-room-arrow{
      width:38px;
      height:54px;
      appearance:none;
      border:1px solid #d0d5dd;
      border-radius:12px;
      background:#fff;
      color:#24594d;
      font:inherit;
      font-size:28px;
      line-height:1;
      font-weight:300;
      cursor:pointer;
      box-shadow:0 10px 22px rgba(7,27,54,0.08);
      transition:background 160ms ease, color 160ms ease, border-color 160ms ease, opacity 160ms ease;
    }
    .wow-room-arrow:hover:not(:disabled){
      border-color:#24594d;
      background:#24594d;
      color:#fff;
    }
    .wow-room-arrow:disabled{
      opacity:0.32;
      cursor:not-allowed;
      box-shadow:none;
    }
    .wow-room-carousel.is-not-scrollable .wow-room-arrow,
    .wow-room-carousel.is-not-scrollable .wow-room-progress{
      display:none;
    }
    .wow-room-carousel.is-not-scrollable{
      grid-template-columns:minmax(0, 1fr);
    }
    .wow-room-progress{
      grid-column:2;
      display:flex;
      justify-content:center;
      gap:6px;
      margin-top:12px;
    }
    .wow-room-progress__dot{
      width:8px;
      height:8px;
      border:1px solid #d0d5dd;
      border-radius:999px;
      background:#fff;
    }
    .wow-room-progress__dot.is-active{
      border-color:#24594d;
      background:#24594d;
    }
    .wow-related-head{
      display:flex;
      flex-direction:column;
      gap:8px;
      margin-bottom:18px;
    }
    .wow-related-head h2{
      margin:0;
      color:#101828;
      font-family:"Playfair Display", Georgia, "Times New Roman", serif;
      font-size:clamp(34px, 4.8vw, 58px);
      font-weight:500;
      line-height:0.96;
      letter-spacing:-0.06em;
    }
    .wow-related-head p{
      margin:0;
      color:#596275;
      font-size:17px;
      line-height:1.58;
      max-width:72ch;
    }
    .wow-related-board{
      display:grid;
      grid-template-columns:minmax(0, 1.25fr) minmax(320px, 0.75fr);
      gap:20px;
      align-items:stretch;
    }
    .wow-related-lead{
      display:grid;
      grid-template-columns:minmax(280px, 0.95fr) minmax(0, 1.05fr);
      min-height:420px;
      border-radius:20px;
      overflow:hidden;
      background:#fff;
      border:1px solid #dfe4ea;
      box-shadow:0 14px 42px rgba(16,24,40,.055);
      text-decoration:none;
      color:inherit;
    }
    .wow-related-lead:hover{
      text-decoration:none;
      color:inherit;
    }
    .wow-related-lead__media{
      background:#111827;
    }
    .wow-related-lead__media img,
    .wow-coverage-thumb img,
    .wow-editor-thumb img{
      width:100%;
      height:100%;
      display:block;
      object-fit:cover;
    }
    .wow-related-lead__body{
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      padding:26px;
    }
    .wow-news-meta{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      align-items:center;
      margin-bottom:18px;
    }
    .wow-news-pill{
      min-height:28px;
      display:inline-flex;
      align-items:center;
      border-radius:999px;
      background:#f2f4f7;
      color:#344054;
      padding:0 10px;
      font-size:12px;
      font-weight:700;
    }
    .wow-news-pill--red{
      background:#f6d5dc;
      color:#8f1532;
      border:1px solid rgba(143, 21, 50, 0.16);
    }
    .wow-related-lead__body h3{
      margin:0;
      color:#101828;
      font-family:"Playfair Display", Georgia, "Times New Roman", serif;
      font-size:clamp(34px, 4.6vw, 48px);
      font-weight:500;
      line-height:0.96;
      letter-spacing:-0.06em;
    }
    .wow-related-lead__body p{
      max-width:560px;
      margin:18px 0 0;
      color:#596275;
      font-size:16px;
      line-height:1.58;
    }
    .wow-story-footer{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:18px;
      margin-top:28px;
      padding-top:18px;
      border-top:1px solid #edf0f2;
      color:#667085;
      font-size:13px;
    }
    .wow-read-link{
      color:#24594d;
      font-weight:800;
      white-space:nowrap;
    }
    .wow-coverage-rail{
      border-radius:20px;
      overflow:hidden;
      background:#fff;
      border:1px solid #dfe4ea;
      box-shadow:0 14px 42px rgba(16,24,40,.055);
      display:flex;
      flex-direction:column;
      height:100%;
    }
    .wow-coverage-rail-head{
      padding:18px 20px;
      border-bottom:1px solid #edf0f2;
    }
    .wow-coverage-rail-head h3,
    .wow-editor-head h3{
      margin:0;
      color:#101828;
      font-size:18px;
      line-height:1.2;
      letter-spacing:-0.03em;
    }
    .wow-coverage-rail-head p,
    .wow-editor-head p{
      margin:8px 0 0;
      color:#667085;
      font-size:13px;
      line-height:1.45;
    }
    .wow-coverage-list{
      display:grid;
      flex:1;
    }
    .wow-coverage-item{
      display:grid;
      grid-template-columns:96px minmax(0, 1fr);
      gap:14px;
      padding:14px 18px;
      border-bottom:1px solid #edf0f2;
      text-decoration:none;
      color:inherit;
      transition:background 160ms ease;
    }
    .wow-coverage-item:last-child{
      border-bottom:0;
    }
    .wow-coverage-item:hover{
      background:#f8fafc;
    }
    .wow-coverage-thumb{
      width:96px;
      height:72px;
      overflow:hidden;
      border-radius:10px;
      background:#eef2f4;
    }
    .wow-coverage-copy strong{
      display:block;
      color:#101828;
      font-size:14px;
      line-height:1.25;
      letter-spacing:-0.02em;
    }
    .wow-coverage-copy span{
      display:block;
      margin-top:6px;
      color:#667085;
      font-size:12px;
      line-height:1.4;
    }
    .wow-editor-list{
      border-radius:20px;
      overflow:hidden;
      background:#fff;
      border:1px solid #dfe4ea;
      box-shadow:0 14px 42px rgba(16,24,40,.055);
    }
    .wow-editor-head{
      padding:20px;
      border-bottom:1px solid #edf0f2;
    }
    .wow-editor-item{
      display:grid;
      grid-template-columns:76px 1fr;
      gap:14px;
      padding:16px 20px;
      border-bottom:1px solid #edf0f2;
      text-decoration:none;
      transition:background 160ms ease;
      color:inherit;
    }
    .wow-editor-item:last-child{
      border-bottom:0;
    }
    .wow-editor-item:hover{
      background:#f8fafc;
    }
    .wow-editor-thumb{
      width:76px;
      height:58px;
      overflow:hidden;
      border-radius:8px;
      background:#eef2f4;
      flex:0 0 auto;
    }
    .wow-editor-item strong{
      display:block;
      color:#101828;
      font-size:14px;
      line-height:1.25;
      letter-spacing:-0.02em;
    }
    .wow-editor-item span{
      display:block;
      margin-top:6px;
      color:#8f1532;
      font-size:12px;
      font-weight:700;
    }
    .wow-empty{
      padding:18px 20px;
      color:#667085;
      font-size:14px;
    }
    @media (max-width: 1080px){
      .wow-event-hero,
      .wow-event-main,
      .wow-related-board{
        grid-template-columns:1fr;
      }
      .wow-event-hero__media{
        min-height:360px;
      }
      .wow-related-lead{
        min-height:0;
        grid-template-columns:1fr;
      }
    }
    @media (max-width: 760px){
      .wow-event-shell{
        width:min(100% - 24px, 1320px);
      }
      .wow-event-hero{
        padding-top:20px;
      }
      .wow-event-hero__content,
      .wow-event-panel,
      .wow-event-schedule{
        padding:22px;
      }
      .wow-event-stats{
        grid-template-columns:1fr;
      }
      .wow-section-heading{
        display:block !important;
        grid-template-columns:none !important;
      }
      .wow-room-carousel{
        grid-template-columns:34px minmax(0, 1fr) 34px;
        gap:6px;
      }
      .wow-room-arrow{
        width:34px;
        height:50px;
        font-size:24px;
      }
      .wow-timeline-item{
        grid-template-columns:1fr;
        gap:10px;
      }
      .wow-related-lead__body{
        padding:22px;
      }
      .wow-story-footer{
        flex-direction:column;
        align-items:flex-start;
        margin-top:18px;
      }
      .wow-coverage-item{
        grid-template-columns:76px minmax(0, 1fr);
      }
      .wow-coverage-thumb{
        width:76px;
        height:58px;
      }
    }
    @media (max-width: 560px){
      .wow-event-shell{
        width:min(100% - 20px, 1320px);
      }
      .wow-event-title{
        font-size:clamp(36px, 11vw, 54px);
      }
      .wow-event-summary{
        font-size:16px;
      }
      .wow-event-hero__media{
        min-height:240px;
      }
      .wow-event-panel h2,
      .wow-event-schedule h2,
      .wow-related-head h2{
        font-size:42px;
      }
    }
  </style>
@endpush

@section('content')
@php
  $title = (string) ($event['title'] ?? 'Event');
  $img = (string) ($event['image'] ?? ($event['featured_image'] ?? ''));
  $summary = trim((string) ($event['summary'] ?? ''));
  $body = $event['description'] ?? ($event['body_html'] ?? ($event['content'] ?? null));
  $relatedArticles = collect($relatedArticles ?? []);

  $dateValue = trim((string) data_get($event, 'date', data_get($event, 'start_date', '')));
  $startTime = trim((string) data_get($event, 'time', data_get($event, 'start_time', '')));
  $endTime = trim((string) data_get($event, 'end_time', ''));
  $location = trim((string) data_get($event, 'location', data_get($event, 'venue', '')));
  $format = trim((string) data_get($event, 'format', data_get($event, 'event_type', '')));
  $eventStatus = ! empty($event['display_is_past']) ? 'Past event' : 'Event';

  $dateLabel = $dateValue !== '' ? $dateValue : 'Date TBC';
  $longDateLabel = $dateValue !== '' ? $dateValue : 'Date TBC';
  try {
      if ($dateValue !== '') {
          $parsedDate = \Carbon\Carbon::parse($dateValue);
          $dateLabel = $parsedDate->format('D j M Y');
          $longDateLabel = $parsedDate->format('l j F Y');
      }
  } catch (\Throwable $e) {
  }

  $timeLabel = '';
  if ($startTime !== '' || $endTime !== '') {
      $timeLabel = trim(($startTime !== '' ? $startTime : 'Time TBC') . ($endTime !== '' ? ' - ' . $endTime : ''));
  }
  $eventSnapshot = array_values(array_filter([
      $dateLabel,
      $timeLabel !== '' ? $timeLabel : null,
      $location !== '' ? $location : null,
      $format !== '' ? $format : null,
  ]));

  $scheduleSource = data_get($event, 'event.schedule', data_get($event, 'schedule', []));
  if ($scheduleSource instanceof \Illuminate\Support\Collection) {
      $scheduleSource = $scheduleSource->all();
  }
  if (is_object($scheduleSource)) {
      $scheduleSource = (array) $scheduleSource;
  }
  if (! is_array($scheduleSource)) {
      $scheduleSource = [];
  }

  $scheduleDays = [];
  $rawDays = data_get($scheduleSource, 'days', []);
  if ($rawDays instanceof \Illuminate\Support\Collection) {
      $rawDays = $rawDays->all();
  }
  if (! is_array($rawDays)) {
      $rawDays = [];
  }

  foreach ($rawDays as $dayIndex => $day) {
      if (is_object($day)) {
          $day = (array) $day;
      }
      if (! is_array($day)) {
          continue;
      }

      $dayDate = trim((string) data_get($day, 'date', ''));
      $dayLabel = trim((string) data_get($day, 'label', ''));
      $rawSessions = data_get($day, 'sessions', []);
      if ($rawSessions instanceof \Illuminate\Support\Collection) {
          $rawSessions = $rawSessions->all();
      }
      if (! is_array($rawSessions)) {
          $rawSessions = [];
      }

      $sessions = [];
      foreach ($rawSessions as $sessionIndex => $session) {
          if (is_object($session)) {
              $session = (array) $session;
          }
          if (! is_array($session)) {
              continue;
          }

          $label = trim((string) data_get($session, 'label', data_get($session, 'title', '')));
          $spaceArea = trim((string) data_get($session, 'space_area', data_get($session, 'spaceArea', '')));
          $sessionStart = trim((string) data_get($session, 'start_time', data_get($session, 'startTime', '')));
          $sessionEnd = trim((string) data_get($session, 'end_time', data_get($session, 'endTime', '')));
          $notes = trim((string) data_get($session, 'notes', data_get($session, 'description', '')));
          $facilitator = trim((string) data_get($session, 'facilitator', data_get($session, 'host', '')));

          if ($label === '' && $spaceArea === '' && $sessionStart === '' && $sessionEnd === '' && $notes === '' && $facilitator === '') {
              continue;
          }

          $sessions[] = [
              'id' => (string) (data_get($session, 'id', '') ?: sprintf('event_schedule_%s_%d_%d', $dayDate !== '' ? $dayDate : 'day', $dayIndex + 1, $sessionIndex + 1)),
              'label' => $label !== '' ? $label : 'Session ' . ($sessionIndex + 1),
              'space_area' => $spaceArea,
              'start_time' => $sessionStart,
              'end_time' => $sessionEnd,
              'notes' => $notes,
              'facilitator' => $facilitator,
          ];
      }

      $scheduleDays[] = [
          'id' => (string) (data_get($day, 'id', '') ?: sprintf('event_day_%s_%d', $dayDate !== '' ? $dayDate : 'day', $dayIndex + 1)),
          'date' => $dayDate,
          'label' => $dayLabel !== '' ? $dayLabel : ($dayDate !== '' ? \Carbon\Carbon::parse($dayDate)->format('D j M') : 'Day ' . ($dayIndex + 1)),
          'sessions' => $sessions,
      ];
  }

  $scheduleDays = array_map(static function (array $day): array {
      $sessions = array_values(array_filter($day['sessions'] ?? [], fn ($session) => is_array($session)));
      $spaces = [];

      foreach ($sessions as $session) {
          $spaceArea = trim((string) ($session['space_area'] ?? ''));
          if ($spaceArea === '') {
              continue;
          }
          if (! in_array($spaceArea, $spaces, true)) {
              $spaces[] = $spaceArea;
          }
      }

      $grouped = [];
      if (count($spaces) > 1) {
          foreach ($spaces as $space) {
              $grouped[] = [
                  'label' => $space,
                  'sessions' => array_values(array_filter($sessions, static fn ($session) => trim((string) ($session['space_area'] ?? '')) === $space)),
              ];
          }
      }

      return [
          'id' => (string) ($day['id'] ?? ''),
          'date' => (string) ($day['date'] ?? ''),
          'label' => (string) ($day['label'] ?? 'Day'),
          'sessions' => $sessions,
          'spaces' => $spaces,
          'grouped_sessions' => $grouped,
          'has_multiple_spaces' => count($spaces) > 1,
      ];
  }, $scheduleDays);
@endphp

<section class="wow-event-page section">
  <div class="wow-event-shell">
    <div class="wow-event-hero">
      <div class="wow-event-hero__content">
        <div>
          <p class="wow-event-kicker">Events &amp; Workshops</p>
          <h1 class="wow-event-title">{{ $title }}</h1>
          @if($summary !== '')
            <p class="wow-event-summary">{{ $summary }}</p>
          @endif

          <div class="wow-event-meta">
            <span class="wow-pill wow-pill--accent">{{ $eventStatus }}</span>
            @if($location !== '')
              <span class="wow-pill">{{ $location }}</span>
            @endif
            @if($format !== '')
              <span class="wow-pill">{{ $format }}</span>
            @endif
          </div>
        </div>

        <div class="wow-event-stats">
          <div class="wow-stat">
            <span>Date</span>
            <strong>{{ $longDateLabel }}</strong>
          </div>
          <div class="wow-stat">
            <span>Time</span>
            <strong>{{ $timeLabel !== '' ? $timeLabel : 'Time TBC' }}</strong>
          </div>
          <div class="wow-stat">
            <span>Location</span>
            <strong>{{ $location !== '' ? $location : 'Venue TBC' }}</strong>
          </div>
          <div class="wow-stat">
            <span>Format</span>
            <strong>{{ $format !== '' ? $format : 'In person' }}</strong>
          </div>
        </div>
      </div>

      <div class="wow-event-hero__media">
        @if($img !== '')
          <img src="{{ $img }}" alt="{{ $title }}" loading="eager" decoding="async">
        @endif
      </div>
    </div>

    <div class="wow-event-main">
      <article class="wow-event-panel">
        <div class="wow-panel-head">
          <div>
            <p class="wow-event-kicker">Overview</p>
            <h2>What this event is about</h2>
          </div>
        </div>

        <div class="wow-event-body">
          @if($body)
            {!! $body !!}
          @else
            <p>Details coming soon.</p>
          @endif
        </div>
      </article>

      <aside class="wow-event-side">
        <div class="wow-side-card">
          <h3>Quick details</h3>
          <div class="wow-side-list">
            @foreach([
              'Date' => $dateLabel,
              'Time' => $timeLabel !== '' ? $timeLabel : null,
              'Location' => $location !== '' ? $location : null,
              'Format' => $format !== '' ? $format : null,
              'Status' => $eventStatus,
            ] as $label => $value)
              @if($value)
                <div class="wow-side-row">
                  <span>{{ $label }}</span>
                  <strong>{{ $value }}</strong>
                </div>
              @endif
            @endforeach
          </div>
        </div>

        <div class="wow-side-card">
          <h3>Navigation</h3>
          <a class="btn-wow btn-wow--outline btn-arrow" href="{{ route('events.index') }}">Back to events</a>
        </div>
      </aside>
    </div>

    @if(! empty($scheduleDays))
      <section class="wow-event-schedule" id="schedule">
        <div class="wow-section-heading">
          <div>
            <p class="wow-event-kicker">Event schedule</p>
            <h2>Two calm days, one big reset.</h2>
            <p>Browse the published sessions for each day. Tap a tab to switch between them.</p>
          </div>
        </div>

        @if(count($scheduleDays) > 1)
          <div class="wow-schedule-tabs" role="tablist" aria-label="Event schedule days">
            @foreach($scheduleDays as $day)
              <button class="wow-schedule-tab{{ $loop->first ? ' is-active' : '' }}" type="button" data-day="{{ $day['id'] }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="schedule-{{ $day['id'] }}">
                {{ $day['label'] }}
              </button>
            @endforeach
          </div>
        @endif

        @foreach($scheduleDays as $day)
          <div class="wow-day" id="schedule-{{ $day['id'] }}" role="tabpanel" data-schedule="{{ $day['id'] }}" @if(! $loop->first) hidden @endif>
            @if(! empty($day['has_multiple_spaces']))
              <div class="wow-room-carousel" data-room-carousel>
                <button type="button" class="wow-room-arrow wow-room-arrow--prev" data-room-prev aria-label="Show previous spaces">‹</button>

                <div class="wow-room-carousel__viewport" data-room-viewport tabindex="0" aria-label="Event spaces">
                  <div class="wow-room-grid {{ count($day['grouped_sessions'] ?? []) === 1 ? 'has-one-room' : (count($day['grouped_sessions'] ?? []) === 2 ? 'has-two-rooms' : 'has-many-rooms') }}">
                    @foreach($day['grouped_sessions'] ?? [] as $spaceIndex => $spaceGroup)
                      <section class="wow-room wow-room--tone-{{ ($spaceIndex % 4) + 1 }}">
                        <header class="wow-room__header">
                          <h3 class="wow-room__name">{{ $spaceGroup['label'] ?? 'General' }}</h3>
                          <p class="wow-room__note">{{ count($spaceGroup['sessions'] ?? []) }} session{{ count($spaceGroup['sessions'] ?? []) === 1 ? '' : 's' }}</p>
                        </header>

                        <div class="wow-room__slots">
                          @forelse($spaceGroup['sessions'] ?? [] as $sessionIndex => $session)
                            @php
                              $sessionLabel = trim((string) ($session['label'] ?? ''));
                              $sessionStart = trim((string) ($session['start_time'] ?? ''));
                              $sessionEnd = trim((string) ($session['end_time'] ?? ''));
                              $sessionNotes = trim((string) ($session['notes'] ?? ''));
                              $sessionFacilitator = trim((string) ($session['facilitator'] ?? ''));
                            @endphp
                            <article class="wow-session">
                              <time class="wow-session__time">{{ $sessionStart !== '' || $sessionEnd !== '' ? trim(($sessionStart !== '' ? $sessionStart : 'All day') . ($sessionEnd !== '' ? ' - ' . $sessionEnd : '')) : ($day['label'] ?? 'Day') }}</time>
                              <h3 class="wow-session__title">{{ $sessionLabel !== '' ? $sessionLabel : 'Session ' . ($sessionIndex + 1) }}</h3>
                              @if($sessionNotes !== '')
                                <p class="wow-session__desc">{{ $sessionNotes }}</p>
                              @endif
                              @if($sessionFacilitator !== '')
                                <p class="wow-session__host"><strong>Facilitator:</strong> {{ $sessionFacilitator }}</p>
                              @endif
                            </article>
                          @empty
                            <div class="wow-room__empty">No sessions added for this space yet.</div>
                          @endforelse
                        </div>
                      </section>
                    @endforeach
                  </div>
                </div>

                <button type="button" class="wow-room-arrow wow-room-arrow--next" data-room-next aria-label="Show next spaces">›</button>
                <div class="wow-room-progress" data-room-progress aria-hidden="true"></div>
              </div>
            @else
              <div class="wow-timeline">
                @forelse($day['sessions'] ?? [] as $sessionIndex => $session)
                  @php
                    $sessionLabel = trim((string) ($session['label'] ?? ''));
                    $sessionSpaceArea = trim((string) ($session['space_area'] ?? ''));
                    $sessionStart = trim((string) ($session['start_time'] ?? ''));
                    $sessionEnd = trim((string) ($session['end_time'] ?? ''));
                    $sessionNotes = trim((string) ($session['notes'] ?? ''));
                    $sessionFacilitator = trim((string) ($session['facilitator'] ?? ''));
                  @endphp
                  <article class="wow-timeline-item">
                    <div class="wow-timeline-time">
                      {{ $sessionStart !== '' || $sessionEnd !== '' ? trim(($sessionStart !== '' ? $sessionStart : 'All day') . ($sessionEnd !== '' ? ' - ' . $sessionEnd : '')) : ($day['label'] ?? 'Day') }}
                    </div>
                    <div>
                      <h3>{{ $sessionLabel !== '' ? $sessionLabel : 'Session ' . ($sessionIndex + 1) }}</h3>
                      @if($sessionSpaceArea !== '')
                        <p style="margin:0 0 8px; color:#8f1532; font-size:12px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase;">{{ $sessionSpaceArea }}</p>
                      @endif
                      <p>
                        {{ $sessionNotes !== '' ? $sessionNotes : 'Session details will be added here soon.' }}
                        @if($sessionFacilitator !== '')
                          <br>Facilitator: {{ $sessionFacilitator }}.
                        @endif
                      </p>
                    </div>
                  </article>
                @empty
                  <article class="wow-timeline-item">
                    <div class="wow-timeline-time">{{ $day['label'] }}</div>
                    <div>
                      <h3>Schedule coming soon</h3>
                      <p>No sessions for this day yet.</p>
                    </div>
                  </article>
                @endforelse
              </div>
            @endif
          </div>
        @endforeach
      </section>
    @endif

    @if($relatedArticles->isNotEmpty())
      <section class="wow-event-schedule wow-event-related" style="margin-top:24px;" id="mindful-times">
        <div class="wow-related-head">
          <div>
            <p class="wow-event-kicker">Latest updates</p>
            <h2>OUR VIBE articles, stories &amp; coverage</h2>
            <p>Explore the latest OUR VIBE updates from We Offer Wellness® - including practitioner interviews, festival stories, wellbeing features and event coverage.</p>
          </div>
        </div>

        <div class="wow-related-board">
          @php $featuredArticle = $relatedArticles->first(); @endphp
          @if($featuredArticle)
            <a class="wow-related-lead" href="{{ $featuredArticle['href'] }}" target="_blank" rel="noopener" aria-label="{{ $featuredArticle['title'] }}">
              <div class="wow-related-lead__media">
                @if(!empty($featuredArticle['image']))
                  <img src="{{ $featuredArticle['image'] }}" alt="{{ $featuredArticle['title'] }}">
                @endif
              </div>
              <div class="wow-related-lead__body">
                <div>
                  <div class="wow-news-meta">
                    <span class="wow-news-pill wow-news-pill--red">Featured</span>
                    <span class="wow-news-pill">{{ $featuredArticle['category'] ?? 'OUR VIBE' }}</span>
                  </div>
                  <h3>{{ $featuredArticle['title'] }}</h3>
                  @if(! empty($featuredArticle['excerpt']))
                    <p>{{ $featuredArticle['excerpt'] }}</p>
                  @endif
                </div>
                <div class="wow-story-footer">
                  <span>Latest from Mindful Times</span>
                  <span class="wow-read-link">Read article →</span>
                </div>
              </div>
            </a>
          @endif

          <aside class="wow-coverage-rail" aria-label="More coverage">
            <div class="wow-coverage-rail-head">
              <p class="wow-event-kicker">More coverage</p>
              <h3>Quick reads and related stories</h3>
              <p>More OUR VIBE pieces to browse without leaving the page.</p>
            </div>

            <div class="wow-coverage-list">
              @foreach($relatedArticles->slice(1, 4) as $article)
                <a href="{{ $article['href'] }}" class="wow-coverage-item" target="_blank" rel="noopener">
                  @if(! empty($article['image']))
                    <div class="wow-coverage-thumb">
                      <img loading="lazy" src="{{ $article['image'] }}" alt="{{ $article['title'] }}">
                    </div>
                  @endif
                  <div class="wow-coverage-copy">
                    <strong>{{ $article['title'] }}</strong>
                    <span>{{ $article['category'] ?? 'OUR VIBE' }}</span>
                  </div>
                </a>
              @endforeach
            </div>
          </aside>
        </div>
      </section>
    @endif
  </div>
</section>

@if(! empty($scheduleDays) && count($scheduleDays) > 1)
  @push('scripts')
    <script>
      (function(){
        const tabs = document.querySelectorAll('.wow-schedule-tab');
        const panels = document.querySelectorAll('[data-schedule]');

        function activate(dayId){
          tabs.forEach(tab => {
            const active = tab.dataset.day === dayId;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
          });
          panels.forEach(panel => {
            panel.hidden = panel.dataset.schedule !== dayId;
          });
          const activePanel = document.querySelector('[data-schedule="'+dayId+'"]');
          if (activePanel) {
            activePanel.querySelector('[data-room-carousel]') && initRoomCarousel(activePanel);
          }
        }

        function getRoomStep(viewport){
          const firstRoom = viewport.querySelector('.wow-room');
          if (!firstRoom) return viewport.clientWidth;
          const track = viewport.querySelector('.wow-room-grid');
          const gap = track ? parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 0) : 0;
          return firstRoom.getBoundingClientRect().width + gap;
        }

        function getVisibleRoomCount(viewport){
          const step = getRoomStep(viewport);
          if (!step) return 1;
          return Math.max(1, Math.round(viewport.clientWidth / step));
        }

        function getActiveRoomIndex(viewport){
          const step = getRoomStep(viewport);
          if (!step) return 0;
          return Math.round(viewport.scrollLeft / step);
        }

        function updateRoomCarousel(carousel){
          const viewport = carousel.querySelector('[data-room-viewport]');
          const prev = carousel.querySelector('[data-room-prev]');
          const next = carousel.querySelector('[data-room-next]');
          const rooms = [...carousel.querySelectorAll('.wow-room')];
          const progress = carousel.querySelector('[data-room-progress]');

          if (!viewport || !prev || !next) return;

          const maxScroll = Math.max(0, viewport.scrollWidth - viewport.clientWidth);
          const hasOverflow = rooms.length > getVisibleRoomCount(viewport) && maxScroll > 2;

          carousel.classList.toggle('is-not-scrollable', !hasOverflow);
          prev.disabled = !hasOverflow || viewport.scrollLeft <= 2;
          next.disabled = !hasOverflow || viewport.scrollLeft >= maxScroll - 2;

          if (progress) {
            if (!hasOverflow) {
              progress.innerHTML = '';
              return;
            }

            const visibleCount = getVisibleRoomCount(viewport);
            const maxStartIndex = Math.max(0, rooms.length - visibleCount);
            const activeIndex = Math.min(maxStartIndex, Math.max(0, getActiveRoomIndex(viewport)));

            progress.innerHTML = Array.from({ length: maxStartIndex + 1 }).map((_, index) => `
              <span class="wow-room-progress__dot ${index === activeIndex ? 'is-active' : ''}"></span>
            `).join('');
          }
        }

        function initRoomCarousel(scope){
          const carousel = scope.querySelector('[data-room-carousel]');
          if (!carousel || carousel.dataset.bound === '1') return;
          const viewport = carousel.querySelector('[data-room-viewport]');
          const prev = carousel.querySelector('[data-room-prev]');
          const next = carousel.querySelector('[data-room-next]');
          if (!viewport || !prev || !next) return;

          carousel.dataset.bound = '1';

          const move = direction => {
            viewport.scrollBy({
              left: getRoomStep(viewport) * direction,
              behavior: 'smooth'
            });
          };

          prev.addEventListener('click', () => move(-1));
          next.addEventListener('click', () => move(1));
          viewport.addEventListener('scroll', () => updateRoomCarousel(carousel), { passive: true });
          window.addEventListener('resize', () => updateRoomCarousel(carousel));
          updateRoomCarousel(carousel);
        }

        tabs.forEach(tab => {
          tab.addEventListener('click', () => activate(tab.dataset.day));
        });

        document.querySelectorAll('[data-room-carousel]').forEach(carousel => updateRoomCarousel(carousel));

        const first = document.querySelector('.wow-schedule-tab');
        if (first) {
          activate(first.dataset.day);
        }
      })();
    </script>
  @endpush
@endif
@endsection
