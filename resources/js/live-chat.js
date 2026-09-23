const modal = document.getElementById('wow-live-chat-modal');
const openers = document.querySelectorAll('[data-live-chat-open]');

if (modal && openers.length) {
    const close = () => {
        modal.hidden = true;
        document.dispatchEvent(new CustomEvent('wow:popup-closed', { detail: { key: 'live-chat' } }));
    };

    openers.forEach((button) => button.addEventListener('click', () => {
        modal.hidden = false;
        document.dispatchEvent(new CustomEvent('wow:popup-opened', { detail: { key: 'live-chat' } }));
        window.requestAnimationFrame(() => modal.querySelector('input')?.focus({ preventScroll: true }));
    }));

    modal.querySelectorAll('[data-live-chat-close]').forEach((button) => button.addEventListener('click', close));
    modal.querySelector('[data-live-chat-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const status = modal.querySelector('[data-live-chat-status]');
        if (status) status.textContent = 'Live chat is being connected. Please try again shortly.';
    });
}
