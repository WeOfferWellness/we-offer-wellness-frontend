const SESSION_TOKEN_KEY = 'wow_subscriber_session_token';
const SESSION_START_KEY = 'wow_subscriber_session_started_at';
const DEFAULT_SUCCESS_MESSAGE = 'Check your email to confirm your subscription.';
const SUBSCRIBER_FORM_SELECTOR = 'form[data-subscriber-form]';
const EMAIL_INPUT_SELECTOR = 'input[type="email"], input[name="email"]';
// Post through the frontend proxy by default so the browser keeps the same
// Laravel session/CSRF context. A direct Studio URL is opt-in for integrations.
const subscriberApiBase = String(
  import.meta.env.VITE_SUBSCRIBER_API_URL
    || import.meta.env.VITE_STUDIO_API_URL
    || '',
).replace(/\/$/, '');
const subscriberEndpoint = subscriberApiBase
  ? `${subscriberApiBase}/api/v3-subscribers`
  : '/api/v3-subscribers';

let sessionStart = loadNumber(SESSION_START_KEY);
if (!sessionStart) {
  sessionStart = Date.now();
  storeNumber(SESSION_START_KEY, sessionStart);
}

let toastEl = null;
let toastTimer = null;
let submitListenerBound = false;
const pendingSubmissions = new WeakMap();

function loadNumber(key) {
  try {
    const raw = sessionStorage.getItem(key);
    return raw ? Number(raw) : null;
  } catch (_err) {
    return null;
  }
}

function storeNumber(key, value) {
  try {
    sessionStorage.setItem(key, String(value));
  } catch (_err) {}
}

function loadToken() {
  try {
    return sessionStorage.getItem(SESSION_TOKEN_KEY);
  } catch (_err) {
    return null;
  }
}

function storeToken(token) {
  if (!token) return;
  try {
    sessionStorage.setItem(SESSION_TOKEN_KEY, token);
  } catch (_err) {}
}

function sessionDurationSeconds() {
  return Math.max(0, Math.round((Date.now() - (sessionStart || Date.now())) / 1000));
}

function collectMeta() {
  const nav = typeof navigator !== 'undefined' ? navigator : {};
  const scr = typeof window !== 'undefined' ? window.screen || {} : {};
  let timezone = null;
  try {
    timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
  } catch (_err) {}
  const languages = Array.isArray(nav.languages) ? nav.languages.join(',') : (nav.language || null);
  return {
    timezone,
    locale: nav.language || null,
    languages,
    platform: nav.platform || null,
    user_agent: nav.userAgent || null,
    device_memory: typeof nav.deviceMemory === 'number' ? String(nav.deviceMemory) : null,
    hardware_concurrency: typeof nav.hardwareConcurrency === 'number' ? String(nav.hardwareConcurrency) : null,
    screen_width: scr.width || null,
    screen_height: scr.height || null,
  };
}

function basePayload(source) {
  const landing = source || 'site:subscribe-form';
  return {
    landing_path: landing,
    referrer: document.referrer ? document.referrer.substring(0, 2048) : null,
    session_token: loadToken(),
    session_started_at: new Date(sessionStart || Date.now()).toISOString(),
    session_duration_seconds: sessionDurationSeconds(),
    ...collectMeta(),
  };
}

function collectFormPayload(form) {
  const payload = {};
  const formData = new FormData(form);

  for (const [rawKey, rawValue] of formData.entries()) {
    if (!rawKey) continue;
    if (rawValue instanceof File) continue;

    const key = rawKey.endsWith('[]') ? rawKey.slice(0, -2) : rawKey;
    const value = typeof rawValue === 'string' ? rawValue.trim() : rawValue;
    if (value === '') continue;

    if (Object.prototype.hasOwnProperty.call(payload, key)) {
      payload[key] = Array.isArray(payload[key]) ? payload[key].concat(value) : [payload[key], value];
    } else if (rawKey.endsWith('[]')) {
      payload[key] = [value];
    } else {
      payload[key] = value;
    }
  }

  const tagAttr = form.getAttribute('data-subscriber-tags') || '';
  const tagList = tagAttr
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean);

  if (tagList.length) {
    payload.tags = Array.isArray(payload.tags)
      ? Array.from(new Set(payload.tags.concat(tagList)))
      : tagList;
  }

  return payload;
}

async function submitSubscriber(additionalPayload = {}) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const payload = Object.assign({}, additionalPayload);
  const response = await fetch(subscriberEndpoint, {
    method: 'POST',
    headers: Object.assign({
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }, csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
    credentials: subscriberApiBase ? 'include' : 'same-origin',
    body: JSON.stringify(payload),
  });
  const bodyJson = await response.json().catch(() => ({}));
  if (!response.ok || bodyJson.error) {
    const message = extractError(bodyJson) || 'Something went wrong. Please try again in a moment.';
    const error = new Error(message);
    error.body = bodyJson;
    throw error;
  }
  if (bodyJson.session_token) {
    storeToken(bodyJson.session_token);
  }
  return bodyJson;
}

function extractError(body) {
  if (!body) return null;
  if (body.errors) {
    const first = Object.values(body.errors)[0];
    if (Array.isArray(first) && first.length) {
      return first[0];
    }
  }
  return body.error || null;
}

function ensureFeedbackEl(form) {
  let el = form.querySelector('[data-subscriber-feedback]');
  if (!el) {
    el = document.createElement('p');
    el.dataset.subscriberFeedback = '1';
    el.className = 'subscriber-feedback';
    el.setAttribute('aria-live', 'polite');
    el.hidden = true;
    form.appendChild(el);
  }
  return el;
}

function ensureToast() {
  if (toastEl && document.body.contains(toastEl)) return toastEl;
  toastEl = document.createElement('div');
  toastEl.className = 'subscriber-toast';
  toastEl.setAttribute('role', 'status');
  toastEl.setAttribute('aria-live', 'polite');
  toastEl.hidden = true;
  document.body.appendChild(toastEl);
  return toastEl;
}

function showToast(message) {
  const el = ensureToast();
  const text = message || DEFAULT_SUCCESS_MESSAGE;
  el.textContent = text;
  el.hidden = false;
  el.classList.add('is-visible');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    el.classList.remove('is-visible');
    el.hidden = true;
  }, 3200);
}

function showMessage(el, text = '', isSuccess) {
  if (!el) {
    if (isSuccess === true) showToast(text);
    return;
  }

  if (typeof isSuccess !== 'boolean') {
    el.textContent = '';
    el.hidden = true;
    el.classList.remove('is-success', 'is-error');
    return;
  }

  if (isSuccess) {
    const msg = text || DEFAULT_SUCCESS_MESSAGE;
    el.textContent = msg;
    el.hidden = true;
    el.classList.add('is-success');
    el.classList.remove('is-error');
    showToast(msg);
    return;
  }

  const message = text || '';
  el.textContent = message;
  el.hidden = !message;
  el.classList.remove('is-success');
  if (message) {
    el.classList.add('is-error');
  } else {
    el.classList.remove('is-error');
  }
}

function setLoading(form, loading) {
  const btn = form.querySelector('[type="submit"]');
  if (!btn) return;
  btn.disabled = loading;
  btn.classList.toggle('is-loading', !!loading);
  if (loading) {
    btn.setAttribute('aria-busy', 'true');
  } else {
    btn.removeAttribute('aria-busy');
  }
}

function findSubscriberForm(node) {
  if (!node) return null;
  if (typeof node.closest === 'function') {
    return node.closest(SUBSCRIBER_FORM_SELECTOR);
  }
  let current = node;
  while (current && current !== document) {
    if (current.matches?.(SUBSCRIBER_FORM_SELECTOR)) return current;
    current = current.parentElement;
  }
  return null;
}

function findEmailInput(form) {
  return form.querySelector(EMAIL_INPUT_SELECTOR);
}

async function handleSubscriberSubmit(form) {
  const input = findEmailInput(form);
  if (!input) return;

  const feedback = ensureFeedbackEl(form);
  const source = form.getAttribute('data-subscriber-source') || form.getAttribute('data-subscriber-form') || 'site:subscribe-form';

  showMessage(feedback);
  const email = input.value.trim();
  if (!email) {
    showMessage(feedback, 'Please enter your email address.', false);
    input.focus();
    return;
  }
  if (input.checkValidity && !input.checkValidity()) {
    input.reportValidity?.();
    return;
  }

  if (pendingSubmissions.get(form)) {
    return;
  }

  pendingSubmissions.set(form, true);
  setLoading(form, true);

  try {
    const payload = Object.assign(basePayload(source), collectFormPayload(form), { email });
    const result = await submitSubscriber(payload);
    form.reset?.();
    const successMessage = result?.message || DEFAULT_SUCCESS_MESSAGE;
    form.dispatchEvent(new CustomEvent('wow:subscriber-success', {
      bubbles: true,
      detail: { form, result },
    }));
    showMessage(feedback, successMessage, true);
  } catch (error) {
    showMessage(feedback, error.message || 'Something went wrong. Please try again.', false);
  } finally {
    pendingSubmissions.delete(form);
    setLoading(form, false);
  }
}

function initSubscriberForms() {
  if (typeof document === 'undefined' || submitListenerBound) return;
  submitListenerBound = true;

  document.addEventListener('submit', (event) => {
    const form = findSubscriberForm(event.target);
    if (!form || form.tagName !== 'FORM') return;
    event.preventDefault();
    handleSubscriberSubmit(form);
  });
}

try {
  window.WOWSubscriber = {
    submit: submitSubscriber,
    basePayload,
    loadToken,
    storeToken,
    sessionDurationSeconds,
  };
  window.dispatchEvent(new CustomEvent('wow:subscriber-ready'));
} catch (_err) {}

try {
  initSubscriberForms();
} catch (_err) {}

export { initSubscriberForms, submitSubscriber, basePayload };
