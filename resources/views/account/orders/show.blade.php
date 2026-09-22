@extends('account.base')

@section('account-content')
@php
    $money = fn ($value) => '£'.number_format(max(0, (int) $value) / 100, 2);
    $order = $order ?? null;
@endphp

@if(!$order)
  <div class="account-empty">
    <p>We couldn’t find that order. Head back to your orders list to try again.</p>
    <a class="btn-wow btn-wow--primary" href="{{ route('account.orders') }}">Back to orders</a>
  </div>
@else
  <article class="account-card">
    <div class="account-card__header">
      <p class="eyebrow">Receipt</p>
      <h2>Order #{{ $order->id }}</h2>
    </div>
    <div class="account-card__body account-order-detail">
      <div class="order-summary">
        <dl>
          <div>
            <dt>Status</dt>
            <dd><span class="order-status order-status--{{ $order->status }}">{{ \Illuminate\Support\Str::headline($order->status) }}</span></dd>
          </div>
          <div>
            <dt>Placed</dt>
            <dd>{{ optional($order->created_at)->format('l, j F Y · H:i') ?? '—' }}</dd>
          </div>
          <div>
            <dt>Charged to</dt>
            <dd>{{ strtoupper($order->currency ?? 'GBP') }} · {{ $money($order->amount_total) }}</dd>
          </div>
        </dl>
        <div class="order-actions">
          <a class="btn-wow btn-wow--outline" href="{{ route('account.orders') }}">Back to orders</a>
          <a class="btn-wow btn-wow--soft" href="mailto:hello@weofferwellness.co.uk?subject=Order%20%23{{ $order->id }}">Need adjustments?</a>
        </div>
      </div>
      <div class="order-items">
        <h3>Items</h3>
        <div class="order-items__list">
          @foreach($order->items as $item)
            <div class="order-item">
              <div>
                <p class="item-name">{{ $item->name }}</p>
                <p class="item-meta">Qty {{ $item->quantity }} · {{ $money($item->unit_amount) }} each</p>
              </div>
              <div class="item-amount">{{ $money($item->unit_amount * $item->quantity) }}</div>
            </div>
          @endforeach
        </div>
        <div class="order-total">
          <span>Total</span>
          <strong>{{ $money($order->amount_total) }}</strong>
        </div>
      </div>
    </div>
  </article>

  <div class="account-grid">
    @if($order->customerProfile)
      <div class="account-card">
        <div class="account-card__header">
          <p class="eyebrow">Booking contact</p>
          <h3>{{ trim($order->customerProfile->first_name.' '.$order->customerProfile->last_name) ?: 'Customer' }}</h3>
        </div>
        <div class="account-card__body">
          <p>{{ $order->customerProfile->email }}</p>
          @if($order->customerProfile->phone)
            <p>{{ $order->customerProfile->phone }}</p>
          @endif
          @if($order->customerProfile->address)
            <p class="account-address">{!! nl2br(e($order->customerProfile->address)) !!}</p>
          @endif
        </div>
      </div>
    @endif

    @if($order->shippingDetail)
      <div class="account-card">
        <div class="account-card__header">
          <p class="eyebrow">Delivery details</p>
          <h3>Shipping address</h3>
        </div>
        <div class="account-card__body">
          <p class="account-address">
            {{ $order->shippingDetail->address }}<br>
            {{ $order->shippingDetail->city }}, {{ $order->shippingDetail->state }} {{ $order->shippingDetail->postal_code }}<br>
            {{ strtoupper((string) $order->shippingDetail->country) }}
          </p>
          @if($order->shippingDetail->shipping_status)
            <p class="order-status order-status--{{ $order->shippingDetail->shipping_status }}">{{ \Illuminate\Support\Str::headline($order->shippingDetail->shipping_status) }}</p>
          @endif
        </div>
      </div>
    @endif
  </div>
@endif
@endsection
