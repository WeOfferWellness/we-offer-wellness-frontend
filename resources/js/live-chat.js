const modal = document.getElementById('wow-live-chat-modal');
const openers = document.querySelectorAll('[data-live-chat-open]');
const backendUrl = String(import.meta.env.VITE_BACKEND_URL || 'https://studio.weofferwellness.co.uk').replace(/\/$/, '');
const tokenKey = 'wow_live_chat_token';

if (modal && openers.length) {
    const layer = modal.closest('[data-wow-popup-layer]');
    const prechat = modal.querySelector('[data-live-chat-prechat]');
    const thread = modal.querySelector('[data-live-chat-thread]');
    const form = modal.querySelector('[data-live-chat-form]');
    const messages = modal.querySelector('[data-live-chat-messages]');
    const status = modal.querySelector('[data-live-chat-status]');
    const threadStatus = modal.querySelector('[data-live-chat-thread-status]');
    const typingLabel = modal.querySelector('[data-live-chat-typing-label]');
    const typingRow = modal.querySelector('[data-live-chat-typing-row]');
    const reply = modal.querySelector('[data-live-chat-reply]');
    const replyInput = reply?.querySelector('textarea');
    let token = (() => { try { return sessionStorage.getItem(tokenKey) || ''; } catch (_) { return ''; } })();
    let pollTimer = null;
    let typingTimer = null;

    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const saveToken = (value) => { try { sessionStorage.setItem(tokenKey, value); } catch (_) {} };
    const setStatus = (value) => { if (status) status.textContent = value || ''; };
    const showThread = () => { prechat?.classList.remove('is-active'); prechat?.setAttribute('hidden', ''); thread?.removeAttribute('hidden'); thread?.classList.add('is-active'); };
    const setLayerOpen = (open) => { if (!layer) return; layer.hidden = !open; layer.setAttribute('aria-hidden', open ? 'false' : 'true'); };
    const initials = (name) => String(name || 'W').trim().charAt(0).toUpperCase() || 'W';

    const renderMessages = (items) => {
        if (!messages) return;
        if (!Array.isArray(items) || !items.length) {
            messages.innerHTML = '<div class="wow-chat-empty">Your conversation is ready. Send us a message and we’ll be with you shortly.</div>';
            return;
        }
        let lastDay = '';
        messages.innerHTML = items.map((item) => {
            const wow = item.sender_type === 'agent';
            const date = item.created_at ? new Date(item.created_at) : new Date();
            const day = date.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
            const time = date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            const dayMarkup = day !== lastDay ? `<div class="wow-chat-day">${escapeHtml(day)}</div>` : '';
            lastDay = day;
            const name = wow ? (item.sender_name || 'WOW team') : (item.sender_name || 'You');
            return `${dayMarkup}<div class="wow-chat-message wow-chat-message--${wow ? 'wow' : 'user'}"><div class="wow-chat-message__avatar">${escapeHtml(initials(wow ? 'W' : name))}</div><div class="wow-chat-message__group"><p class="wow-chat-message__name">${escapeHtml(name)}</p><div class="wow-chat-bubble">${escapeHtml(item.message).replace(/\n/g, '<br>')}</div><div class="wow-chat-message__meta">${escapeHtml(time)}${wow ? '<span class="wow-chat-message__checks" aria-label="Sent">✓✓</span>' : ''}</div></div></div>`;
        }).join('');
        messages.scrollTop = messages.scrollHeight;
    };

    const loadMessages = async () => {
        if (!token) return;
        try {
            const response = await fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/messages`, { credentials: 'include', headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const payload = await response.json();
            renderMessages(payload.messages || []);
            const online = !!payload.agents_online;
            const typing = !!payload.agent_typing;
            if (threadStatus) threadStatus.textContent = typing ? 'A WOW team member is typing…' : online ? 'A WOW team member is online' : 'We’ll reply as soon as someone is available.';
            if (typingLabel) typingLabel.hidden = !typing;
            if (typingRow) typingRow.hidden = !typing;
            if (typing) { typingLabel?.scrollIntoView({ block: 'nearest' }); }
        } catch (_) {}
    };
    const startPolling = () => { loadMessages(); if (!pollTimer) pollTimer = window.setInterval(loadMessages, 3000); };
    const stopPolling = () => { if (pollTimer) window.clearInterval(pollTimer); pollTimer = null; };
    const close = () => {
        modal.classList.add('is-closing');
        modal.classList.remove('is-open');
        window.setTimeout(() => { modal.classList.remove('is-closing'); modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); setLayerOpen(false); stopPolling(); }, 420);
        document.dispatchEvent(new CustomEvent('wow:popup-closed', { detail: { key: 'live-chat' } }));
    };
    const open = () => {
        modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); setLayerOpen(true);
        requestAnimationFrame(() => { modal.classList.add('is-open'); (token ? replyInput : modal.querySelector('input'))?.focus({ preventScroll: true }); });
        document.dispatchEvent(new CustomEvent('wow:popup-opened', { detail: { key: 'live-chat' } }));
        if (token) { showThread(); startPolling(); }
    };

    openers.forEach((button) => button.addEventListener('click', open));
    modal.querySelectorAll('[data-live-chat-close],[data-live-chat-minimise]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) close(); });
    modal.querySelectorAll('.wow-chat-topic').forEach((button) => button.addEventListener('click', () => { modal.querySelectorAll('.wow-chat-topic').forEach((item) => item.classList.remove('is-selected')); button.classList.add('is-selected'); const input = form?.querySelector('[name="message"]'); if (input && !input.value.trim()) { input.value = `${button.textContent.trim()}: `; input.focus(); } }));
    form?.querySelectorAll('.wow-chat-control').forEach((input) => input.addEventListener('input', () => input.closest('.wow-chat-field')?.classList.remove('is-invalid')));
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const name = form.querySelector('[name="first_name"]'); const email = form.querySelector('[name="email"]'); const message = form.querySelector('[name="message"]');
        const validName = !!name?.value.trim(); const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email?.value.trim() || ''); const validMessage = !!message?.value.trim();
        [['name', validName], ['email', validEmail], ['message', validMessage]].forEach(([field, valid]) => form.querySelector(`[data-field="${field}"]`)?.classList.toggle('is-invalid', !valid));
        if (!validName || !validEmail || !validMessage) return;
        const submit = form.querySelector('[type="submit"]'); if (submit) submit.disabled = true; setStatus('Starting your conversation…');
        try {
            const payload = Object.fromEntries(new FormData(form).entries()); payload.subscribe = !!form.querySelector('[name="subscribe"]')?.checked;
            const response = await fetch(`${backendUrl}/api/live-chat/conversations`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json(); if (!response.ok) throw new Error(result.message || 'Unable to start chat');
            token = result.conversation_token; saveToken(token); showThread(); setStatus(''); startPolling();
        } catch (error) { setStatus(error.message || 'Unable to start chat. Please try again.'); if (submit) submit.disabled = false; }
    });
    reply?.addEventListener('submit', async (event) => { event.preventDefault(); const value = replyInput?.value.trim(); if (!value || !token) return; replyInput.value = ''; replyInput.style.height = '42px'; try { const response = await fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/messages`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ message: value }) }); if (!response.ok) throw new Error(); await loadMessages(); } catch (_) { if (threadStatus) threadStatus.textContent = 'Message could not be sent. Please try again.'; } });
    reply?.querySelectorAll('.wow-chat-quick button').forEach((button) => button.addEventListener('click', () => { if (replyInput) { replyInput.value = button.textContent.trim(); replyInput.focus(); } }));
    replyInput?.addEventListener('input', () => { replyInput.style.height = '42px'; replyInput.style.height = `${Math.min(replyInput.scrollHeight, 110)}px`; if (!token || !replyInput.value.trim()) return; window.clearTimeout(typingTimer); typingTimer = window.setTimeout(() => fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/typing`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: '{}', keepalive: true }).catch(() => {}), 250); });
    replyInput?.addEventListener('keydown', (event) => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); reply?.requestSubmit(); } });
}
