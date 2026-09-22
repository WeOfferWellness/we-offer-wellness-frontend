@extends('account.base')

@section('account-content')
@php
    $money = fn ($value) => '£'.number_format(max(0, (int) $value) / 100, 2);
    $recentOrders = $recentOrders ?? collect();
    $stats = $stats ?? [];
@endphp

<div class="account-stats">
  <div class="account-stat-card">
    <p class="label">Total orders</p>
    <p class="value">{{ number_format($stats['total_orders'] ?? 0) }}</p>
    <span class="caption">Since you joined</span>
  </div>
  <div class="account-stat-card">
    <p class="label">Upcoming or open</p>
    <p class="value">{{ number_format($stats['open_orders'] ?? 0) }}</p>
    <span class="caption">Pending or paid</span>
  </div>
  <div class="account-stat-card">
    <p class="label">Lifetime value</p>
    <p class="value">{{ $money($stats['lifetime_value'] ?? 0) }}</p>
    <span class="caption">Confirmed payments</span>
  </div>
  <div class="account-stat-card">
    <p class="label">Last booking</p>
    <p class="value">{{ optional($stats['last_order_at'])->format('j M Y') ?? '—' }}</p>
    <span class="caption">Most recent receipt</span>
  </div>
</div>

<div class="account-grid">
  <article class="account-card account-card--primary">
    <header class="account-card__header">
      <div>
        <p class="eyebrow">Recent activity</p>
        <h2>Latest orders</h2>
      </div>
      <a class="btn-wow btn-wow--outline btn-sm" href="{{ route('account.orders') }}">View all</a>
    </header>
    <div class="account-card__body">
      @forelse ($recentOrders as $order)
        <div class="account-order-row">
          <div class="account-order-row__main">
            <span class="order-id">#{{ $order->id }}</span>
            <span class="order-date">{{ optional($order->created_at)->format('j M Y, H:i') }}</span>
            <span class="order-meta">{{ $order->items->pluck('name')->take(2)->implode(', ') }}</span>
          </div>
          <div class="account-order-row__meta">
            <span class="order-amount">{{ $money($order->amount_total) }}</span>
            <span class="order-status order-status--{{ $order->status }}">{{ \Illuminate\Support\Str::headline($order->status) }}</span>
            <a class="order-link" href="{{ route('account.orders.show', $order->id) }}">Open</a>
          </div>
        </div>
      @empty
        <div class="account-empty">
          <p>You haven’t booked anything yet. When you checkout, your orders will appear here.</p>
          <a class="btn-wow btn-wow--primary" href="/therapies">Browse therapies</a>
        </div>
      @endforelse
    </div>
  </article>
  <article class="account-card account-card--secondary">
    <header class="account-card__header">
      <p class="eyebrow">Quick actions</p>
      <h2>Manage your week</h2>
    </header>
    <div class="account-card__body">
      <ul class="account-shortcuts">
        <li>
          <div>
            <p class="shortcut-title">Update your profile</p>
            <p class="shortcut-subtitle">Refresh your name, email, or marketing preferences.</p>
          </div>
          <a class="btn-wow btn-wow--outline btn-sm" href="{{ route('profile.edit') }}">Open profile</a>
        </li>
        <li>
          <div>
            <p class="shortcut-title">Rebook a favourite</p>
            <p class="shortcut-subtitle">Head back to the marketplace and explore what’s popular now.</p>
          </div>
          <a class="btn-wow btn-wow--soft btn-sm" href="/needs">Find therapies</a>
        </li>
        <li>
          <div>
            <p class="shortcut-title">Need concierge help?</p>
            <p class="shortcut-subtitle">Chat with us if you need to adjust times or attendees.</p>
          </div>
          <a class="btn-wow btn-wow--soft btn-sm" href="/contact">Contact support</a>
        </li>
      </ul>
    </div>
  </article>
</div>
@endsection
