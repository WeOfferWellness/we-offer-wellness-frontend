@extends('layouts.app')

@section('content')
@php
  $order = $order ?? null;
  $items = $order?->items ?? [];
  $analyticsItems = [];
  foreach ($items as $item) {
      $meta = is_array($item->meta ?? null) ? $item->meta : (is_object($item->meta ?? null) ? (array) $item->meta : []);
      $analyticsItems[] = [
          'id' => (string) ($item->sku ?? $item->product_id ?? $item->id ?? ''),
          'title' => (string) ($item->name ?? 'Item'),
          'price' => round(((float) ($item->unit_amount ?? 0)) / 100, 2),
          'qty' => (int) ($item->quantity ?? 1),
          'product_id' => $item->product_id ?? null,
          'variant_id' => $item->variant_id ?? null,
          'variant_label' => (string) ($meta['variant_label'] ?? ''),
          'source_version' => $meta['source_version'] ?? null,
      ];
  }
  $analyticsTotal = round((float) ($order?->amount_total ?? 0) / 100, 2);
  $analyticsCount = array_sum(array_map(static fn ($item) => (int) ($item['qty'] ?? 1), $analyticsItems));
@endphp
<section class="section">
  <div class="container-page">
    <div class="hero-card hero-card--warn">
      <div class="hero-icon" aria-hidden="true">!</div>
      <h1>Your checkout wasn’t completed.</h1>
      @if($order)
        <p>We held Order #{{ $order->id }}, but the payment didn’t go through. Your cart still has the items below.</p>
      @else
        <p>No worries—your cart is still intact. You can try the payment again or adjust your selection.</p>
      @endif
      <div class="cta-row">
        <a class="btn-wow btn-wow--primary" href="/cart">Return to cart</a>
        <a class="btn-wow btn-wow--outline" href="/search">Keep browsing</a>
        <a class="link-wow" href="/help">Need help?</a>
      </div>
    </div>
  </div>
</section>
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    try {
      var payload = {
        items: @json($analyticsItems),
        currency: 'GBP',
        value: @json($analyticsTotal),
        item_count: @json($analyticsCount),
        transaction_id: @json((string) ($order?->stripe_session_id ?? $order?->id ?? '')),
        order_id: @json((string) ($order?->id ?? '')),
        checkout_status: 'cancelled',
        source: 'checkout-cancel',
      };
      if (window.WOWAnalytics && typeof window.WOWAnalytics.trackCommerce === 'function') {
        window.WOWAnalytics.trackCommerce('wow_v3_payment_cancelled', payload);
      } else if (typeof window.gtag === 'function') {
        window.gtag('event', 'wow_v3_payment_cancelled', Object.assign({ flow_version: 'v3', wow_event_name: 'wow_v3_payment_cancelled' }, payload));
      }
    } catch (_) {}
  });
</script>
@endpush
@endsection
