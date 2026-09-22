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
          'catalogue_type' => $meta['catalogue_type'] ?? ($meta['source_version'] ?? 'offering'),
          'provider_id' => $item->vendor_id ?? null,
          'offering_id' => $meta['offering_id'] ?? null,
          'modality' => $meta['modality'] ?? null,
          'booking_source' => $meta['booking_source'] ?? null,
          'checkout_source' => $meta['checkout_source'] ?? null,
      ];
  }
  $analyticsTotal = round((float) ($order?->amount_total ?? 0) / 100, 2);
  $analyticsCount = array_sum(array_map(static fn ($item) => (int) ($item['qty'] ?? 1), $analyticsItems));
@endphp
<section class="section">
  <div class="container-page">
    <div class="hero-card">
      <div class="hero-icon" aria-hidden="true">✓</div>
      <h1>Thank you! Your booking is confirmed.</h1>
      <p>Order #{{ $order?->id ?? '—' }} · Paid £{{ number_format(($order?->amount_total ?? 0)/100, 2) }}</p>
      @if(!empty($items))
        <div class="order-card">
          @foreach($items as $item)
            <div class="order-line">
              <span>{{ $item->quantity }} × {{ $item->name }}</span>
              <span>£{{ number_format(($item->unit_amount ?? 0)/100, 2) }}</span>
            </div>
          @endforeach
        </div>
      @endif
      <p>
        We’ve emailed your receipt and next steps. If you checked out as a guest, create or log into your account using the same email to manage your sessions.
      </p>
      <div class="cta-row">
        <a class="btn-wow btn-wow--primary" href="/login">Log in</a>
        <a class="btn-wow btn-wow--outline" href="/register">Create account</a>
        <a class="link-wow" href="/search">Discover more therapies</a>
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
        currency: @json(strtoupper((string) ($order?->currency ?: 'GBP'))),
        value: @json($analyticsTotal),
        item_count: @json($analyticsCount),
        transaction_id: @json('WOW-' . (string) ($order?->id ?? '')),
        order_id: @json((string) ($order?->id ?? '')),
        checkout_status: 'success',
        source: 'checkout-success',
      };
      if (@json((bool) ($purchaseAnalytics ?? false)) && window.WOWAnalytics && typeof window.WOWAnalytics.trackPurchase === 'function') {
        window.WOWAnalytics.trackPurchase(payload);
      }
    } catch (_) {}
    try { localStorage.removeItem('wow_cart'); } catch (_) {}
    try { localStorage.removeItem('wow_cart_v1'); } catch (_) {}
    try {
      document.cookie = 'wow_cart=; Path=/; Max-Age=0; SameSite=Lax';
      document.cookie = 'wow_cart=; Domain=.weofferwellness.co.uk; Path=/; Max-Age=0; SameSite=Lax';
    } catch (_) {}
    try {
      window.dispatchEvent(new CustomEvent('wow:cart:change', { detail: { items: [], count: 0, source: 'order:success' } }));
    } catch (_) {}
  });
</script>
@endpush
@endsection
