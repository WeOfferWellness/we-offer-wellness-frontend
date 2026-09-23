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
    const agentStatus = modal.querySelector('[data-live-chat-agent-status]');
    const typingLabel = modal.querySelector('[data-live-chat-typing-label]');
    const typingRow = modal.querySelector('[data-live-chat-typing-row]');
    const reply = modal.querySelector('[data-live-chat-reply]');
    const replyInput = reply?.querySelector('textarea');
    const unreadBadge = document.querySelector('[data-live-chat-unread]');
    let token = (() => { try { return sessionStorage.getItem(tokenKey) || ''; } catch (_) { return ''; } })();
    let pollTimer = null;
    let typingTimer = null;
    let typingHeartbeatTimer = null;
    let unreadBaselineSet = false;
    let previousUnreadCount = 0;

    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const saveToken = (value) => { try { sessionStorage.setItem(tokenKey, value); } catch (_) {} };
    const setStatus = (value) => { if (status) status.textContent = value || ''; };
    const syncStartButton = () => {
        const name = form?.querySelector('[name="first_name"]');
        const email = form?.querySelector('[name="email"]');
        const message = form?.querySelector('[name="message"]');
        const messageField = form?.querySelector('[data-field="message"]');
        const submit = form?.querySelector('[type="submit"]');
        if (!submit) return;
        const identityReady = !!name?.value.trim() && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email?.value.trim() || '');
        if (messageField) messageField.hidden = !identityReady;
        if (message) {
            message.disabled = !identityReady;
            message.setAttribute('aria-disabled', identityReady ? 'false' : 'true');
            if (!identityReady) message.value = '';
        }
        const enabled = identityReady && !!message?.value.trim();
        submit.disabled = !enabled;
        submit.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    };
    const showThread = () => { prechat?.classList.remove('is-active'); prechat?.setAttribute('hidden', ''); thread?.removeAttribute('hidden'); thread?.classList.add('is-active'); };
    const setLayerOpen = (open) => { if (!layer) return; layer.hidden = !open; layer.setAttribute('aria-hidden', open ? 'false' : 'true'); };
    const initials = (name) => String(name || 'W').trim().charAt(0).toUpperCase() || 'W';
    const wowAvatar = '<img src="https://www.weofferwellness.co.uk/favicon-48x48.png" alt="We Offer Wellness">';
    modal.querySelectorAll('.wow-chat-brand__avatar').forEach((avatar) => {
        const statusDot = avatar.querySelector('.wow-chat-brand__status');
        avatar.innerHTML = wowAvatar + (statusDot ? statusDot.outerHTML : '');
    });
    modal.querySelectorAll('[data-live-chat-typing-row] .wow-chat-message__avatar').forEach((avatar) => {
        avatar.innerHTML = wowAvatar;
    });
    const updateUnreadBadge = (count) => {
        const unread = Math.max(0, Number(count) || 0);
        if (!unreadBadge) return;
        unreadBadge.textContent = unread > 99 ? '99+' : String(unread);
        unreadBadge.hidden = unread === 0;
    };
    const requestChatNotifications = async () => {
        if (!token || typeof Notification === 'undefined' || Notification.permission !== 'default') return;
        let hint = modal.querySelector('[data-live-chat-notification-hint]');
        if (!hint) {
            hint = document.createElement('p');
            hint.className = 'wow-chat-notification-hint';
            hint.dataset.liveChatNotificationHint = '1';
            hint.textContent = 'Allow notifications so WOW can let you know when a team member replies while you’re away.';
            modal.querySelector('[data-live-chat-messages]')?.before(hint);
        }
        if (hint) hint.hidden = false;
        try { await Notification.requestPermission(); } catch (_) {}
        if (hint) hint.hidden = true;
    };
    const setPresenceStatus = (value) => {
        if (agentStatus) agentStatus.textContent = value;
        if (threadStatus) threadStatus.textContent = value;
    };
    const chatPreviewCache = new Map();
    const normaliseChatUrl = (value = '') => {
        const raw = String(value || '').trim();
        if (!raw) return '';
        try { return new URL(/^https?:\/\//i.test(raw) ? raw : `https://${raw}`).toString(); } catch (_) { return ''; }
    };
    const extractChatUrl = (value = '') => {
        const match = String(value || '').match(/(?:https?:\/\/|www\.)[^\s<>'"]+|[a-z0-9.-]+\.[a-z]{2,}(?:\/[^\s<>'"]*)?/i);
        return match ? normaliseChatUrl(match[0].replace(/[.,;:!?)]$/, '')) : '';
    };
    const truncateChatTitle = (value = '') => {
        const title = String(value || '').trim();
        return title.length > 50 ? `${title.slice(0, 48)}..` : title;
    };
    const getChatPreview = async (url) => {
        const normalized = normaliseChatUrl(url);
        if (!normalized) return null;
        if (!chatPreviewCache.has(normalized)) {
            chatPreviewCache.set(normalized, fetch(`${backendUrl}/api/live-chat/preview?url=${encodeURIComponent(normalized)}`, { headers: { Accept: 'application/json' } })
                .then(async (response) => response.ok ? response.json() : null)
                .catch(() => null));
        }
        return chatPreviewCache.get(normalized);
    };
    const renderChatLinkPreview = (url, preview, removable = false) => {
        const normalized = normaliseChatUrl(url);
        if (!normalized) return '';
        const title = truncateChatTitle(preview?.title || new URL(normalized).hostname.replace(/^www\./, ''));
        const image = String(preview?.image || '').trim();
        const domain = String(preview?.domain || new URL(normalized).hostname.replace(/^www\./, '')).trim();
        const remove = removable ? '<button type="button" class="wow-chat-link-preview__remove" data-chat-preview-remove aria-label="Remove link preview">×</button>' : '';
        return `<a class="wow-chat-link-preview" href="${escapeHtml(normalized)}" target="_blank" rel="noopener noreferrer"><span class="wow-chat-link-preview__image">${image ? `<img src="${escapeHtml(image)}" alt="">` : ''}</span><span class="wow-chat-link-preview__copy"><strong>${escapeHtml(title)}</strong><small>${escapeHtml(domain)}</small></span>${remove}</a>`;
    };
    const hydrateChatLinkPreviews = async () => {
        const nodes = Array.from(messages?.querySelectorAll?.('[data-chat-message-content]') || []);
        await Promise.all(nodes.map(async (node) => {
            const raw = node.dataset.chatMessageContent || '';
            const url = extractChatUrl(raw);
            if (!url) return;
            const preview = await getChatPreview(url);
            if (!preview) return;
            const card = renderChatLinkPreview(url, preview);
            if (/^\s*(?:https?:\/\/|www\.)[^\s]+\s*$/i.test(raw)) node.innerHTML = card;
            else node.insertAdjacentHTML('beforeend', card);
        }));
    };
    let draftPreviewTimer = null;
    let draftPreviewRequest = 0;
    let pendingComposerUrl = '';
    const draftPreview = document.createElement('div');
    draftPreview.className = 'wow-chat-link-preview-draft';
    draftPreview.hidden = true;
    reply?.querySelector('.wow-chat-compose__row')?.append(draftPreview);
    const captureComposerUrl = (forceBoundary = false) => {
        if (!replyInput || pendingComposerUrl) return;
        const raw = replyInput.value;
        const match = raw.match(/(?:https?:\/\/|www\.)[^\s<>'"]+|[a-z0-9.-]+\.[a-z]{2,}(?:\/[^\s<>'"]*)?/i);
        if (!match) return;
        const end = (match.index || 0) + match[0].length;
        const boundary = forceBoundary || end === raw.length || /\s/.test(raw.charAt(end));
        if (!boundary) return;
        const normalized = normaliseChatUrl(match[0].replace(/[.,;:!?)]$/, ''));
        if (!normalized) return;
        pendingComposerUrl = normalized;
        replyInput.value = `${raw.slice(0, match.index || 0)}${raw.slice(end)}`.replace(/\s{2,}/g, ' ').trimStart();
    };
    const updateDraftPreview = () => {
        window.clearTimeout(draftPreviewTimer);
        const url = pendingComposerUrl || extractChatUrl(replyInput?.value || '');
        if (!url) { draftPreview.hidden = true; draftPreview.innerHTML = ''; return; }
        const requestId = ++draftPreviewRequest;
        draftPreviewTimer = window.setTimeout(async () => {
            const preview = await getChatPreview(url) || {};
            if (requestId !== draftPreviewRequest) return;
            draftPreview.innerHTML = renderChatLinkPreview(url, preview, true);
            draftPreview.hidden = false;
        }, 180);
    };
    draftPreview.addEventListener('click', (event) => {
        if (!event.target.closest('[data-chat-preview-remove]')) return;
        pendingComposerUrl = '';
        draftPreview.hidden = true;
        draftPreview.innerHTML = '';
        replyInput?.dispatchEvent(new Event('input', { bubbles: true }));
    });

    const renderMessages = (items, agentPresence = []) => {
        if (!messages) return;
        if (!Array.isArray(items) || !items.length) {
            messages.innerHTML = '<div class="wow-chat-empty">Your conversation is ready. Send us a message and we’ll be with you shortly.</div>';
            return;
        }
        let lastDay = '';
        const hasCustomerMessage = items.some((item) => item.sender_type === 'customer');
        const hasAgentReply = items.some((item) => item.sender_type === 'agent');
        const renderedMessages = items.map((item) => {
            const wow = item.sender_type === 'agent';
            const date = item.created_at ? new Date(item.created_at) : new Date();
            const day = date.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
            const time = date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            const dayMarkup = day !== lastDay ? `<div class="wow-chat-day">${escapeHtml(day)}</div>` : '';
            lastDay = day;
            const name = wow ? (item.sender_name || 'WOW team') : (item.sender_name || 'You');
            const receipt = !wow ? `<span class="wow-chat-message__checks" aria-label="${item.read_at ? 'Read' : 'Sent'}">${item.read_at ? '✓✓' : '✓'}</span>` : '';
            return `${dayMarkup}<div class="wow-chat-message wow-chat-message--${wow ? 'user' : 'wow'}"><div class="wow-chat-message__avatar">${wow ? wowAvatar : escapeHtml(initials(name))}</div><div class="wow-chat-message__group"><p class="wow-chat-message__name">${escapeHtml(name)}</p><div class="wow-chat-bubble"><div class="wow-chat-message__content" data-chat-message-content="${escapeHtml(item.message)}">${escapeHtml(item.message).replace(/\n/g, '<br>')}</div></div><div class="wow-chat-message__meta">${escapeHtml(time)}${receipt}</div></div></div>`;
        }).join('');
        const agents = Array.isArray(agentPresence) ? agentPresence : (agentPresence ? [agentPresence] : []);
        const connectionMessage = agents.length === 1
            ? `You are now connected to ${escapeHtml(agents[0].role || 'Admin')} ${escapeHtml(agents[0].name || 'support')}.`
            : agents.length > 1
                ? `Support Team · ${agents.length} members are ready to write back.`
                : 'We’re connecting you to a member of our support team now. They’ll be with you as soon as possible.';
        const connectionNotice = hasCustomerMessage && !hasAgentReply
            ? `<div class="wow-chat-connection-notice" role="status">${connectionMessage}</div>`
            : '';
        messages.innerHTML = renderedMessages + connectionNotice;
        messages.scrollTop = messages.scrollHeight;
        hydrateChatLinkPreviews();
    };

    const loadMessages = async () => {
        if (!token) return;
        try {
            const response = await fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/messages`, { cache: 'no-store', credentials: 'include', headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const payload = await response.json();
            const items = payload.messages || [];
            const unread = items.filter((item) => item.sender_type === 'agent' && !item.read_at).length;
            if (!unreadBaselineSet) {
                unreadBaselineSet = true;
            } else if (unread > previousUnreadCount && (modal.hidden || document.hidden || !document.hasFocus()) && typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                try { new Notification('WOW Support', { body: 'A member of the WOW team has replied to your live chat.', tag: `wow-live-chat-${token}` }); } catch (_) {}
            }
            previousUnreadCount = unread;
            updateUnreadBadge(unread);
            const online = !!payload.agents_online;
            const agents = online
                ? Array.isArray(payload.agent_presence)
                    ? payload.agent_presence
                    : payload.agent_presence && typeof payload.agent_presence === 'object'
                        ? [payload.agent_presence]
                        : []
                : [];
            renderMessages(items, agents);
            if (!modal.hidden && thread?.classList.contains('is-active')) {
                fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/read`, { method: 'POST', credentials: 'include', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: '{}' }).catch(() => {});
            }
            const typing = !!payload.agent_typing;
            const leadAgent = agents[0];
            const agentCount = Number(payload.agent_count ?? agents.length) || 0;
            const presenceStatus = typing
                ? agentCount === 1
                    ? `${leadAgent?.role || 'Admin'} ${leadAgent?.name || 'team member'} is typing…`
                    : agentCount > 1
                        ? `Support Team · ${agentCount} members are typing…`
                        : 'A member of our support team is typing…'
                : agentCount === 1
                    ? `You are now connected to ${leadAgent?.role || 'Admin'} ${leadAgent?.name || 'our support team'}`
                    : agentCount > 1
                        ? `Support Team · ${agentCount} members are ready to write back`
                        : 'We’re connecting you to a member of our support team now. They’ll be with you as soon as possible.';
            setPresenceStatus(presenceStatus);
            if (typingLabel) typingLabel.hidden = !typing;
            if (typingRow) typingRow.hidden = !typing;
            if (typing) { typingLabel?.scrollIntoView({ block: 'nearest' }); }
        } catch (_) {}
    };
    const startPolling = () => { loadMessages(); if (!pollTimer) pollTimer = window.setInterval(loadMessages, 500); };
    const stopPolling = () => { if (pollTimer) window.clearInterval(pollTimer); pollTimer = null; };
    const close = () => {
        modal.classList.add('is-closing');
        modal.classList.remove('is-open');
        window.setTimeout(() => { modal.classList.remove('is-closing'); modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); setLayerOpen(false); if (!token) stopPolling(); }, 420);
        document.dispatchEvent(new CustomEvent('wow:popup-closed', { detail: { key: 'live-chat' } }));
    };
    const open = () => {
        modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); setLayerOpen(true);
        requestAnimationFrame(() => { modal.classList.add('is-open'); (token ? replyInput : modal.querySelector('input'))?.focus({ preventScroll: true }); });
        document.dispatchEvent(new CustomEvent('wow:popup-opened', { detail: { key: 'live-chat' } }));
        if (token) { showThread(); startPolling(); requestChatNotifications(); }
    };

    openers.forEach((button) => button.addEventListener('click', open));
    modal.querySelectorAll('[data-live-chat-close],[data-live-chat-minimise]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) close(); });
    modal.querySelectorAll('.wow-chat-topic').forEach((button) => button.addEventListener('click', () => { const input = form?.querySelector('[name="message"]'); if (input?.disabled) { form?.querySelector('[name="first_name"]')?.focus(); return; } modal.querySelectorAll('.wow-chat-topic').forEach((item) => item.classList.remove('is-selected')); button.classList.add('is-selected'); if (input && !input.value.trim()) { input.value = `${button.textContent.trim()}: `; input.focus(); syncStartButton(); } }));
    form?.querySelectorAll('.wow-chat-control').forEach((input) => input.addEventListener('input', () => { input.closest('.wow-chat-field')?.classList.remove('is-invalid'); syncStartButton(); }));
    syncStartButton();
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const name = form.querySelector('[name="first_name"]'); const email = form.querySelector('[name="email"]'); const message = form.querySelector('[name="message"]');
        const validName = !!name?.value.trim(); const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email?.value.trim() || ''); const validMessage = !!message?.value.trim();
        [['name', validName], ['email', validEmail], ['message', validMessage]].forEach(([field, valid]) => form.querySelector(`[data-field="${field}"]`)?.classList.toggle('is-invalid', !valid));
        syncStartButton();
        if (!validName || !validEmail || !validMessage) return;
        const submit = form.querySelector('[type="submit"]'); if (submit) submit.disabled = true; setStatus('Starting your conversation…');
        try {
            const payload = Object.fromEntries(new FormData(form).entries()); payload.subscribe = !!form.querySelector('[name="subscribe"]')?.checked;
            const response = await fetch(`${backendUrl}/api/live-chat/conversations`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json(); if (!response.ok) throw new Error(result.message || 'Unable to start chat');
            token = result.conversation_token; saveToken(token); showThread(); setStatus(''); startPolling(); requestChatNotifications();
        } catch (error) { setStatus(error.message || 'Unable to start chat. Please try again.'); if (submit) submit.disabled = false; }
    });
    const sendTypingSignal = (active = true) => { if (!token || (active && !replyInput?.value.trim())) return; fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/typing`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ active }), keepalive: true }).catch(() => {}); };
    const stopTypingHeartbeat = (notify = false) => { window.clearTimeout(typingTimer); window.clearInterval(typingHeartbeatTimer); typingTimer = null; typingHeartbeatTimer = null; if (notify) sendTypingSignal(false); };
    reply?.addEventListener('submit', async (event) => { event.preventDefault(); const text = replyInput?.value.trim() || ''; const value = [pendingComposerUrl, text].filter(Boolean).join(' '); if (!value || !token) return; stopTypingHeartbeat(true); pendingComposerUrl = ''; replyInput.value = ''; replyInput.style.height = '42px'; updateDraftPreview(); try { const response = await fetch(`${backendUrl}/api/live-chat/conversations/${encodeURIComponent(token)}/messages`, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ message: value }) }); if (!response.ok) throw new Error(); await loadMessages(); } catch (_) { if (threadStatus) threadStatus.textContent = 'Message could not be sent. Please try again.'; } });
    reply?.querySelectorAll('.wow-chat-quick button').forEach((button) => button.addEventListener('click', () => { if (replyInput) { replyInput.value = button.textContent.trim(); replyInput.focus(); } }));
    replyInput?.addEventListener('keydown', (event) => { if (event.key === ' ') { captureComposerUrl(true); updateDraftPreview(); } });
    replyInput?.addEventListener('input', () => { replyInput.style.height = '42px'; replyInput.style.height = `${Math.min(replyInput.scrollHeight, 110)}px`; captureComposerUrl(); updateDraftPreview(); stopTypingHeartbeat(); if (!token || (!replyInput.value.trim() && !pendingComposerUrl)) { sendTypingSignal(false); return; } typingTimer = window.setTimeout(() => { sendTypingSignal(); typingHeartbeatTimer = window.setInterval(sendTypingSignal, 1000); }, 100); });
    replyInput?.addEventListener('blur', () => { if (!replyInput?.value.trim()) sendTypingSignal(false); });
    replyInput?.addEventListener('keydown', (event) => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); reply?.requestSubmit(); } });
    if (token) startPolling();
}
