@php
    $rawName = trim((string) optional($accountUser)->name);
    $firstName = trim(optional($accountUser)->first_name ?: \Illuminate\Support\Str::of($rawName)->before(' '));
    $derivedLast = '';
    if ($rawName && str_contains($rawName, ' ')) {
        $derivedLast = trim(\Illuminate\Support\Str::of($rawName)->after(' '));
    }
    $lastName = trim(optional($accountUser)->last_name ?: $derivedLast);
    $fullName = trim($firstName.' '.$lastName) ?: ($rawName ?: 'Customer');
    $initials = mb_strtoupper(mb_substr($firstName ?: $fullName, 0, 1).mb_substr($lastName ?: '', 0, 1));
    $initials = trim($initials) !== '' ? $initials : 'YOU';
@endphp

<aside class="account-sidebar" aria-label="Customer account navigation">
  <div class="account-card account-user-card">
    <span class="account-trigger__avatar account-user-avatar" aria-hidden="true">{{ $initials }}</span>
    <div>
      <div class="account-user-name">{{ $fullName }}</div>
    </div>
  </div>
  <nav class="account-nav" aria-label="Account sections">
    @foreach($navItems as $item)
      @php $isActive = ($current ?? 'dashboard') === $item['slug']; @endphp
      <a href="{{ $item['href'] }}" class="account-nav__link {{ $isActive ? 'is-active' : '' }}" aria-current="{{ $isActive ? 'page' : 'false' }}">
        <span class="account-nav__icon" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['label'], 0, 1)) }}</span>
        <span class="account-nav__text">
          <span class="label">{{ $item['label'] }}</span>
          <span class="hint">{{ $item['description'] }}</span>
        </span>
      </a>
    @endforeach
  </nav>
  <div class="account-card account-support">
    <p class="account-support__title">Need help?</p>
    <p class="account-support__text">Chat with our concierge team for booking changes, receipts, or personalised suggestions.</p>
    <div class="account-support__actions">
      <a class="account-support__link" href="mailto:hello@weofferwellness.co.uk">hello@weofferwellness.co.uk</a>
      <a class="btn-wow btn-wow--outline btn-sm" href="/contact">Message support</a>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="account-logout-form">
      @csrf
      <button type="submit" class="btn-wow btn-wow--primary btn-sm" style="width:100%; margin-top:10px; background:#000; color:#fff;">Log out</button>
    </form>
  </div>
</aside>
