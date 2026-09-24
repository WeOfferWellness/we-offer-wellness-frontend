@extends('layouts.base')

@section('html-lang', 'en')

@section('document-head')
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ $target }}">
<title>Taking you to We Offer Wellness</title>
    <style>
        :root { color-scheme: light; font-family: Manrope, Arial, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f5fbf8; color: #0b1320; }
        main { width: min(460px, calc(100% - 40px)); padding: 32px; border: 1px solid #d7ebe3; border-radius: 20px; background: #fff; box-shadow: 0 18px 50px rgba(16, 24, 40, .08); text-align: center; }
        .mark { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 14px; background: #d4fbe6; color: #0f6b57; font-size: 24px; font-weight: 800; }
        h1 { margin: 18px 0 8px; font-size: 22px; line-height: 1.2; }
        p { margin: 0; color: #667085; line-height: 1.5; }
        a { display: inline-flex; margin-top: 22px; padding: 11px 18px; border-radius: 999px; background: #105b4b; color: #fff; font-weight: 700; text-decoration: none; }
        a:hover { background: #0b463b; }
    </style>
@endsection

@section('document-body-class')

@endsection

@section('document-body')
<main>
        <span class="mark" aria-hidden="true">WOW</span>
        <h1>Taking you to the right place</h1>
        <p>This page is no longer available.</p>
        <a href="{{ $target }}">Continue</a>
    </main>
@endsection
