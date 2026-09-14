import { basePayload, submitSubscriber } from './subscriber-forms';

const DISMISSAL_DAYS = 3;
const SUBSCRIBED_COOKIE = 'wow_newsletter_subscribed';
const DISMISSED_COOKIE = 'wow_newsletter_dismissed_at';

function getCookie(name) {
  const prefix = `${encodeURIComponent(name)}=`;
  return document.cookie.split('; ').find((entry) => entry.startsWith(prefix))?.slice(prefix.length) || '';
}

function setCookie(name, value, days) {
  document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; max-age=${Math.round(days * 86400)}; path=/; samesite=lax`;
}

function deleteCookie(name) {
  document.cookie = `${encodeURIComponent(name)}=; max-age=0; path=/; samesite=lax`;
}

function isSubscribed() {
  return getCookie(SUBSCRIBED_COOKIE) === '1';
}

function isDismissedRecently() {
  const dismissedAt = Number(getCookie(DISMISSED_COOKIE));
  return Number.isFinite(dismissedAt) && (Date.now() - dismissedAt) < (DISMISSAL_DAYS * 86400000);
}

function initNewsletterModal() {
  const modal = document.getElementById('wow-newsletter-modal');
  const trigger = document.getElementById('wow-newsletter-trigger');
  const form = document.getElementById('wow-newsletter-form');
  const content = document.getElementById('wow-newsletter-content');
  const email = document.getElementById('wow-newsletter-email');
  const submit = document.getElementById('wow-newsletter-submit');
  const message = document.getElementById('wow-newsletter-message');

  if (!modal || !trigger || !form || !content || !email || !submit || !message || modal.dataset.initialized) return;
  modal.dataset.initialized = 'true';

  let timer = null;
  let lastFocused = null;
  let originalOverflow = '';
  let autoOpened = false;

  const stopAutoTriggers = () => {
    window.removeEventListener('scroll', onScroll);
    if (timer !== null) window.clearTimeout(timer);
    timer = null;
  };

  const open = (automatic = false) => {
    if (!modal.hidden || isSubscribed()) return;
    lastFocused = document.activeElement;
    originalOverflow = document.body.style.overflow;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    if (automatic) {
      autoOpened = true;
      stopAutoTriggers();
    }
    requestAnimationFrame(() => modal.querySelector('.wow-newsletter-close')?.focus({ preventScroll: true }));
  };

  const close = ({ remember = true } = {}) => {
    if (modal.hidden) return;
    modal.hidden = true;
    document.body.style.overflow = originalOverflow;
    if (remember && !isSubscribed()) setCookie(DISMISSED_COOKIE, String(Date.now()), DISMISSAL_DAYS);
    lastFocused?.focus?.({ preventScroll: true });
  };

  const onScroll = () => {
    const maxScroll = Math.max(document.documentElement.scrollHeight - window.innerHeight, 0);
    const scrollProgress = maxScroll ? window.scrollY / maxScroll : 0;
    if (window.scrollY >= 320 || scrollProgress >= 0.3) open(true);
  };

  trigger.addEventListener('click', () => open(false));
  modal.querySelectorAll('[data-wow-newsletter-close]').forEach((element) => {
    element.addEventListener('click', () => close());
  });

  document.addEventListener('keydown', (event) => {
    if (modal.hidden) return;
    if (event.key === 'Escape') close();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    message.textContent = '';
    message.classList.remove('is-error');
    if (!email.checkValidity()) {
      email.reportValidity?.();
      return;
    }

    const originalContents = submit.innerHTML;
    submit.disabled = true;
    submit.textContent = 'Subscribing...';
    try {
      await submitSubscriber({
        ...basePayload('site:newsletter-modal'),
        email: email.value.trim(),
        source: 'site:newsletter-modal',
        tags: ['wow_weekly_newsletter'],
      });
      setCookie(SUBSCRIBED_COOKIE, '1', 365);
      deleteCookie(DISMISSED_COOKIE);
      stopAutoTriggers();
      content.classList.add('is-success');
      trigger.hidden = true;
    } catch (error) {
      message.textContent = error.message || 'Something went wrong. Please try again.';
      message.classList.add('is-error');
    } finally {
      submit.disabled = false;
      submit.innerHTML = originalContents;
    }
  });

  if (isSubscribed()) {
    trigger.hidden = true;
    return;
  }
  trigger.hidden = false;
  if (isDismissedRecently()) return;

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  timer = window.setTimeout(() => open(true), 12000);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initNewsletterModal, { once: true });
} else {
  initNewsletterModal();
}

export { initNewsletterModal };
