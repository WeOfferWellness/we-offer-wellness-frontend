const modal = document.getElementById('wow-live-chat-modal');
const openers = document.querySelectorAll('[data-live-chat-open]');
const backendUrl = String(import.meta.env.VITE_BACKEND_URL || 'https://studio.weofferwellness.co.uk').replace(/\/$/, '');
const tokenKey = 'wow_live_chat_token';

if (modal && openers.length) {
    const form = modal.querySelector('[data-live-chat-form]');
    const thread = modal.querySelector('[data-live-chat-thread]');
    const messages = modal.querySelector('[data-live-chat-messages]');
    const threadStatus = modal.querySelector('[data-live-chat-thread-status]');
    const readToken = () => { try { return sessionStorage.getItem(tokenKey) || ''; } catch (_) { return ''; } };
    const saveToken = (value) => { try { sessionStorage.setItem(tokenKey, value); } catch (_) {} };
    let token = readToken();
    let pollTimer = null;
    let typingTimer = null;
    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const setStatus = (value) => { if (threadStatus) threadStatus.textContent = value; };
    const showThread = () => { if (form) form.hidden = true; if (thread) thread.hidden = false; };
    const renderMessages = (items) => {
        if (!messages) return;
        messages.innerHTML = items.map((item) => `<div class="wow-live-chat-message wow-live-chat-message--${escapeHtml(item.sender_type)}"><strong>${escapeHtml(item.sender_name)}</strong><p>${escapeHtml(item.message)}</p></div>`).join('');
        messages.scrollTop = messages.scrollHeight;
    };
    const loadMessages = async () => {
        if (!token) return;
        try {
            const response = await fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/messages`, { credentials: 'include', headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const payload = await response.json();
            renderMessages(payload.messages || []);
            setStatus(payload.agent_typing ? 'A WOW team member is typing…' : payload.agents_online ? 'A WOW team member is online.' : 'We’ll reply as soon as someone is available.');
        } catch (_) {}
    };
    const startPolling = () => { loadMessages(); if (!pollTimer) pollTimer = window.setInterval(loadMessages, 3000); };
    const stopPolling = () => { if (pollTimer) window.clearInterval(pollTimer); pollTimer = null; };
    const close = () => {
        modal.hidden = true;
        stopPolling();
        document.dispatchEvent(new CustomEvent('wow:popup-closed', { detail: { key: 'live-chat' } }));
    };

    openers.forEach((button) => button.addEventListener('click', () => {
        modal.hidden = false;
        document.dispatchEvent(new CustomEvent('wow:popup-opened', { detail: { key: 'live-chat' } }));
        if (token) { showThread(); startPolling(); }
        window.requestAnimationFrame(() => modal.querySelector('input')?.focus({ preventScroll: true }));
    }));

    modal.querySelectorAll('[data-live-chat-close]').forEach((button) => button.addEventListener('click', close));
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const status = modal.querySelector('[data-live-chat-status]');
        const submit = form.querySelector('button[type="submit"]');
        if (submit) submit.disabled = true;
        if (status) status.textContent = 'Starting your conversation…';
        try {
            const payload = Object.fromEntries(new FormData(form).entries());
            payload.subscribe = form.querySelector('[name="subscribe"]')?.checked || false;
            const response = await fetch(`${backendUrl}/api/live-chat/conversations`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Unable to start chat');
            token = result.conversation_token;
            saveToken(token);
            showThread();
            startPolling();
        } catch (error) {
            if (status) status.textContent = error.message || 'Unable to start chat. Please try again.';
            if (submit) submit.disabled = false;
        }
    });
    modal.querySelector('[data-live-chat-reply]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const reply = event.currentTarget;
        const input = reply.querySelector('input');
        if (!input?.value.trim() || !token) return;
        const value = input.value.trim();
        input.value = '';
        try {
            await fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/messages`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ message: value }) });
            await loadMessages();
        } catch (_) { setStatus('Message could not be sent. Please try again.'); }
    });

    const replyInput = modal.querySelector('[data-live-chat-reply] input');
    replyInput?.addEventListener('input', () => {
        if (!token || !replyInput.value.trim()) return;
        window.clearTimeout(typingTimer);
        typingTimer = window.setTimeout(() => {
            fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/typing`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({}),
                keepalive: true,
            }).catch(() => {});
        }, 250);
    });
}
