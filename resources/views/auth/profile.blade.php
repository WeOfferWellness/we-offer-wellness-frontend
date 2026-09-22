@extends('account.base')

@section('account-content')
<style>
  .account-card.account-card--danger{ margin-top:20px; }
  .account-card--collapsible .account-card__header{ margin-bottom:0; }
  .account-card__toggle{
    display:flex;
    align-items:center;
    justify-content:space-between;
    width:100%;
    gap:12px;
    background:none;
    border:0;
    padding:0;
    cursor:pointer;
    text-align:left;
  }
  .account-card__toggle:focus-visible{ outline:2px solid var(--ink-900); outline-offset:4px; }
  .account-card__toggle-icon{
    width:32px;
    height:32px;
    border-radius:50%;
    background:rgba(0,0,0,.08);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    transition:transform .2s ease;
  }
  .account-card--collapsible.is-open .account-card__toggle-icon{ transform:rotate(180deg); }
  .account-card--collapsible .account-card__body[hidden]{ display:none; }
</style>
@php
    $status = $status ?? session('status');
    $profileUser = auth()->user();
    $firstName = trim($profileUser->first_name ?: \Illuminate\Support\Str::of($profileUser->name ?? '')->before(' '));
    $lastName = trim($profileUser->last_name ?: \Illuminate\Support\Str::of($profileUser->name ?? '')->after(' '));
    $initials = mb_strtoupper(mb_substr($firstName ?: 'You', 0, 1).mb_substr($lastName ?: '', 0, 1));
    $initials = trim($initials) !== '' ? $initials : 'YOU';
    $assetHost = rtrim(config('app.asset_url') ?: config('services.asset_host') ?: 'https://atease.weofferwellness.co.uk', '/');
    $profilePhoto = $profileUser->profile_picture
        ? $assetHost.'/storage/'.ltrim($profileUser->profile_picture, '/')
        : null;
@endphp

<div class="account-card">
  <div class="account-card__header">
    <p class="eyebrow">Contact details</p>
    <h2>Profile & preferences</h2>
  </div>
  <div class="account-card__body account-form-grid">
    @if (session('status') === 'profile-updated')
      <div class="account-alert account-alert--success">Profile updated successfully.</div>
    @elseif($status)
      <div class="account-alert account-alert--info">{{ $status }}</div>
    @endif

    @if ($mustVerifyEmail ?? false)
      @if (!auth()->user()->hasVerifiedEmail())
        <div class="account-alert account-alert--warning">
          <p><strong>Verify your email.</strong> We’ve sent a verification link so you can receive booking confirmations. <a href="{{ route('verification.send') }}" onclick="event.preventDefault(); document.getElementById('resend-verification').submit();">Resend email</a></p>
          <form id="resend-verification" method="POST" action="{{ route('verification.send') }}" style="display:none;">
            @csrf
          </form>
        </div>
      @endif
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="account-form" enctype="multipart/form-data">
      @csrf
      @method('PATCH')

      <div class="profile-photo-field">
        <div class="account-avatar account-avatar--profile" id="profileAvatar" aria-hidden="true">
          <img id="profileAvatarImg" src="{{ $profilePhoto ?? '' }}" alt="Profile photo" data-current="{{ $profilePhoto ?? '' }}" @unless($profilePhoto) hidden @endunless>
          <span id="profileAvatarInitials" @if($profilePhoto) hidden @endif>{{ $initials }}</span>
        </div>
        <label class="btn-wow btn-wow--outline btn-sm" for="profile-picture-upload">Upload photo</label>
        <input id="profile-picture-upload" type="file" name="profile_picture" accept="image/*" hidden>
      </div>
      <p class="text-sm" id="profile-photo-status" role="status" aria-live="polite" style="margin:4px 0 0;color:var(--ink-600);"></p>
      @error('profile_picture')<p class="field-error">{{ $message }}</p>@enderror

      <label for="profile-first-name">First name</label>
      <input id="profile-first-name" type="text" name="first_name" value="{{ old('first_name', $profileUser->first_name) }}" required>
      @error('first_name')<p class="field-error">{{ $message }}</p>@enderror

      <label for="profile-last-name">Last name</label>
      <input id="profile-last-name" type="text" name="last_name" value="{{ old('last_name', $profileUser->last_name) }}" required>
      @error('last_name')<p class="field-error">{{ $message }}</p>@enderror

      <label for="profile-email">Email address</label>
      <input id="profile-email" type="email" name="email" value="{{ old('email', $profileUser->email) }}" required>
      @error('email')<p class="field-error">{{ $message }}</p>@enderror

      <button type="submit" class="btn-wow btn-wow--primary">Save changes</button>
    </form>
  </div>
</div>

<div class="account-card account-card--danger account-card--collapsible" data-collapsible>
  <div class="account-card__header">
    <button type="button" class="account-card__toggle" data-collapsible-toggle aria-expanded="false">
      <div>
        <p class="eyebrow">Danger zone</p>
        <h2>Delete account</h2>
      </div>
      <span class="account-card__toggle-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 9l6 6 6-6" />
        </svg>
      </span>
    </button>
  </div>
  <div class="account-card__body" data-collapsible-body hidden>
    <p>Deleting your account removes upcoming bookings, saved preferences, and receipts. This action is permanent.</p>
    <form method="POST" action="{{ route('profile.destroy') }}" class="account-form" onsubmit="return confirm('Delete your account? This cannot be undone');">
      @csrf
      @method('DELETE')
      <label for="delete-password">Confirm your password</label>
      <input id="delete-password" type="password" name="password" required>
      @error('password')<p class="field-error">{{ $message }}</p>@enderror
      <button type="submit" class="btn-wow btn-wow--outline" style="margin-top:12px">Delete account</button>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const input = document.getElementById('profile-picture-upload');
  if (!input) return;
  const avatarImg = document.getElementById('profileAvatarImg');
  const initials = document.getElementById('profileAvatarInitials');
  const statusEl = document.getElementById('profile-photo-status');
  const uploadLabel = document.querySelector('label[for="profile-picture-upload"]');
  const uploadUrl = @json(route('profile.photo'));
  let lastServerUrl = (avatarImg && avatarImg.dataset.current) ? avatarImg.dataset.current : '';

  function showImage(url){
    if (!avatarImg) return;
    if (url) {
      avatarImg.src = url;
      avatarImg.hidden = false;
      if (initials) initials.hidden = true;
    } else {
      avatarImg.hidden = true;
      if (initials) initials.hidden = false;
    }
  }

  function setStatus(message, isError){
    if (!statusEl) return;
    statusEl.textContent = message || '';
    statusEl.style.color = isError ? '#dc2626' : 'var(--ink-600)';
  }

  function toggleUploading(isUploading){
    if (!uploadLabel) return;
    if (!uploadLabel.dataset.originalText) {
      uploadLabel.dataset.originalText = uploadLabel.textContent || '';
    }
    uploadLabel.textContent = isUploading ? 'Uploading…' : uploadLabel.dataset.originalText;
    uploadLabel.style.opacity = isUploading ? '0.7' : '';
    uploadLabel.style.pointerEvents = isUploading ? 'none' : '';
  }

  function uploadFile(file, fallbackUrl){
    if (!file || !window.fetch) {
      setStatus('', false);
      return;
    }
    const formData = new FormData();
    formData.append('profile_picture', file);
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    toggleUploading(true);
    setStatus('Uploading photo…');
    fetch(uploadUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      },
      body: formData
    })
      .then(async (response) => {
        if (response.ok) {
          return response.json();
        }
        const error = await response.json().catch(() => ({}));
        const message = error?.message || error?.errors?.profile_picture?.[0] || 'Upload failed.';
        throw new Error(message);
      })
      .then((data) => {
        if (data?.url) {
          lastServerUrl = data.url;
          if (avatarImg) {
            avatarImg.dataset.current = data.url;
          }
          showImage(data.url);
        }
        setStatus('Photo saved.');
      })
      .catch((error) => {
        setStatus(error?.message || 'Upload failed.', true);
        if (fallbackUrl) {
          showImage(fallbackUrl);
        } else {
          showImage('');
        }
      })
      .finally(() => {
        toggleUploading(false);
        input.value = '';
      });
  }

  input.addEventListener('change', function(){
    const file = this.files && this.files[0];
    if (!file) return;
    if (file.type && !/^image\//i.test(file.type)) {
      setStatus('Please upload an image file.', true);
      this.value = '';
      return;
    }
    const previousUrl = lastServerUrl;
    if (window.FileReader) {
      const reader = new FileReader();
      reader.onload = function(evt){
        if (evt.target?.result) {
          showImage(evt.target.result);
        }
      };
      reader.readAsDataURL(file);
    }
    uploadFile(file, previousUrl);
  });
})();

(function(){
  const cards = document.querySelectorAll('[data-collapsible]');
  if(!cards.length) return;
  cards.forEach(function(card){
    const toggle = card.querySelector('[data-collapsible-toggle]');
    const body = card.querySelector('[data-collapsible-body]');
    if(!toggle || !body) return;
    body.hidden = true;
    card.classList.remove('is-open');
    toggle.setAttribute('aria-expanded','false');
    toggle.addEventListener('click', function(){
      const isOpen = card.classList.toggle('is-open');
      body.hidden = !isOpen;
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  });
})();
</script>
@endpush
