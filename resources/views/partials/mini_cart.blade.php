@php
  $items = $items ?? [];
  $count = $count ?? array_sum(array_map(fn($it) => (int) ($it['qty'] ?? 0), $items));
  $total = $total ?? 0;
  $money = fn($value) => '£' . number_format(max(0, (float) $value), 2);
@endphp
<div class="cartdd-body" data-mini-cart>
  @if(empty($items))
    <div class="cartdd-empty mini-cart__empty">Your cart is empty</div>
  @else
    @foreach($items as $item)
      <div class="cartdd-item" data-id="{{ $item['id'] }}">
        <div class="cartdd-img">
          @if(!empty($item['image']))
            <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}">
          @endif
        </div>
        <div class="cartdd-info">
          <a class="cartdd-title" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
          <div class="cartdd-meta">Qty {{ $item['qty'] }} @if(!empty($item['price'])) • {{ $money($item['price']) }} each @endif</div>
        </div>
        <div class="cartdd-amt">{{ $money(($item['price'] ?? 0) * $item['qty']) }}</div>
        <button class="cartdd-remove remove-btn js-remove" type="button" aria-label="Remove item" data-remove="{{ $item['id'] }}">
          <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
          </svg>
        </button>
      </div>
    @endforeach
  @endif
</div>
<div class="cartdd-subtotal" data-mini-subtotal>
  <span>Total</span>
  <strong>{{ $money($total) }}</strong>
</div>
<div class="cartdd-foot" data-mini-foot>
  <a href="/cart" class="btn-wow btn-wow--outline btn-wow--sm visit-cart-btn"><span class="btn-label">Visit cart</span></a>
  <a href="/checkout" class="btn-wow btn-wow--primary btn-wow--sm checkout-btn"><span class="btn-label">Checkout</span></a>
</div>
