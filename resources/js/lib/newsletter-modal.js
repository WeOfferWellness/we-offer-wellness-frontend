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
  const reviewBadge = document.getElementById('wow-review-float');
  if (reviewBadge && !reviewBadge.dataset.initialized) {
    reviewBadge.dataset.initialized = 'true';
    let lastScrollY = window.scrollY;
    let scrollTicking = false;

    const updateReviewBadge = () => {
      const currentScrollY = window.scrollY;
      if (!window.matchMedia('(max-width: 767px)').matches) {
        reviewBadge.classList.remove('is-hidden');
        lastScrollY = currentScrollY;
        scrollTicking = false;
        return;
      }
      if (currentScrollY <= 24 || currentScrollY < lastScrollY) {
        reviewBadge.classList.remove('is-hidden');
      } else if (currentScrollY > lastScrollY) {
        reviewBadge.classList.add('is-hidden');
      }
      lastScrollY = currentScrollY;
      scrollTicking = false;
    };

    window.addEventListener('scroll', () => {
      if (scrollTicking) return;
      scrollTicking = true;
      window.requestAnimationFrame(updateReviewBadge);
    }, { passive: true });
    window.addEventListener('resize', updateReviewBadge, { passive: true });

    const countText = reviewBadge.querySelector('[data-review-count-text]');
    const serverCount = Number(reviewBadge.dataset.reviewCount);
    fetch('/api/review-stats', { headers: { Accept: 'application/json' } })
      .then((response) => response.ok ? response.json() : null)
      .then((data) => {
        const verifiedCount = Number(data?.verified_count);
        const reviewCount = Number(data?.review_count);
        const count = Number.isFinite(verifiedCount) && verifiedCount > 0
          ? verifiedCount
          : Number.isFinite(reviewCount) && reviewCount > 0
            ? reviewCount
            : serverCount;
        if (!countText || !Number.isFinite(count) || count <= 0) return;
        const formatted = new Intl.NumberFormat().format(count);
        countText.textContent = formatted;
        reviewBadge.setAttribute('aria-label', `${formatted} verified practitioner reviews`);
      })
      .catch(() => {});
  }

  const modal = document.getElementById('wow-newsletter-modal');
  const trigger = document.getElementById('wow-newsletter-trigger');
  const form = document.getElementById('wow-newsletter-form');
  const content = document.getElementById('wow-newsletter-content');
  const email = document.getElementById('wow-newsletter-email');
  const submit = document.getElementById('wow-newsletter-submit');

  const updateReviewBadgePosition = () => {
    if (!reviewBadge) return;

    const newsletterUnavailable = !trigger || trigger.hidden || isSubscribed();
    reviewBadge.classList.toggle('wow-review-float--bottom', newsletterUnavailable);
  };

  updateReviewBadgePosition();
  if (trigger && typeof MutationObserver !== 'undefined') {
    new MutationObserver(updateReviewBadgePosition).observe(trigger, {
      attributes: true,
      attributeFilter: ['hidden', 'class', 'style'],
    });
  }
  const message = document.getElementById('wow-newsletter-message');

  if (!modal || !trigger || !form || !content || !email || !submit || !message || modal.dataset.initialized) return;
  modal.dataset.initialized = 'true';

  let timer = null;
  let deferredOpenTimer = null;
  let lastFocused = null;
  let originalOverflow = '';
  let autoOpened = false;

  const stopAutoTriggers = () => {
    window.removeEventListener('scroll', onScroll);
    if (timer !== null) window.clearTimeout(timer);
    if (deferredOpenTimer !== null) window.clearTimeout(deferredOpenTimer);
    timer = null;
    deferredOpenTimer = null;
  };

  const isNavigationOpen = () => {
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileSearch = document.getElementById('mobile-search-drawer');
    const megaMenu = document.getElementById('mega-panel');
    const mobileMenuOpen = mobileMenu && window.getComputedStyle(mobileMenu).display !== 'none';
    const mobileSearchOpen = mobileSearch && (mobileSearch.classList.contains('is-visible') || mobileSearch.getAttribute('aria-hidden') === 'false');
    const megaMenuOpen = megaMenu && (megaMenu.classList.contains('is-open') || megaMenu.getAttribute('aria-hidden') === 'false');
    const accountMenuOpen = document.querySelector('.account-trigger[aria-expanded="true"], .account-menu.show, [data-account-menu].is-open');
    return Boolean(mobileMenuOpen || mobileSearchOpen || megaMenuOpen || accountMenuOpen);
  };

  const requestAutomaticOpen = () => {
    if (!isNavigationOpen()) {
      open(true);
      return;
    }
    if (deferredOpenTimer !== null) return;
    deferredOpenTimer = window.setTimeout(() => {
      deferredOpenTimer = null;
      requestAutomaticOpen();
    }, 450);
  };

  const open = (automatic = false) => {
    if (!modal.hidden || isSubscribed() || isNavigationOpen()) return;
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
    if (window.scrollY >= 320 || scrollProgress >= 0.3) requestAutomaticOpen();
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
      updateReviewBadgePosition();
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
    updateReviewBadgePosition();
    return;
  }
  trigger.hidden = false;
  updateReviewBadgePosition();
  if (isDismissedRecently()) return;

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  timer = window.setTimeout(() => requestAutomaticOpen(), 12000);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initNewsletterModal, { once: true });
} else {
  initNewsletterModal();
}

export { initNewsletterModal };
