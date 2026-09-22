@extends('account.base')

@section('account-content')
@php
    $money = fn ($value) => '£'.number_format(max(0, (int) $value) / 100, 2);
@endphp

<div class="account-card">
  <div class="account-card__header">
    <p class="eyebrow">Receipts</p>
    <h2>Order history</h2>
  </div>
  <div class="account-card__body">
    @if($orders->count() > 0)
      <div role="table" class="account-table">
        <div class="account-table__row account-table__head" role="rowgroup">
          <div>Order</div>
          <div>Date</div>
          <div>Total</div>
          <div>Status</div>
          <div></div>
        </div>
        @foreach($orders as $order)
          <div class="account-table__row" role="row">
            <div>
              <p class="order-id">#{{ $order->id }}</p>
              <p class="order-meta">{{ $order->items->pluck('name')->take(2)->implode(', ') }}</p>
            </div>
            <div>{{ optional($order->created_at)->format('j M Y, H:i') ?? '—' }}</div>
            <div class="order-amount">{{ $money($order->amount_total) }}</div>
            <div><span class="order-status order-status--{{ $order->status }}">{{ \Illuminate\Support\Str::headline($order->status) }}</span></div>
            <div>
              <a class="btn-wow btn-wow--outline btn-sm" href="{{ route('account.orders.show', $order->id) }}">View</a>
            </div>
          </div>
        @endforeach
      </div>
      <div class="account-pagination">
        {{ $orders->onEachSide(1)->links('pagination::bootstrap-4') }}
      </div>
    @else
      <div class="account-empty">
        <p>You haven’t made any bookings yet. Browse therapies, workshops, or online sessions to get started.</p>
        <a class="btn-wow btn-wow--primary" href="/needs">Start exploring</a>
      </div>
    @endif
  </div>
</div>
@endsection
