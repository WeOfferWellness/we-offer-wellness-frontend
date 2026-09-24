// Load Bootstrap CSS first, then custom app styles
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'flatpickr/dist/flatpickr.min.css';
import '../css/app.css';
import '../css/wow-cards.css';
import '../css/site.css';
import '../css/offering-cards-v49.css';
import '../css/offering-cards-v49-mobile.css';
import '../css/offering-cards-v410.css';
import '../css/wow-buttons.css';
import '../css/home-hero.css';
import '../css/home-searchbar-v4.css';
import '../css/home-searchbar-viewport.css';
import '../css/newsletter-modal.css';
// Load Bootstrap JS (Popper included via dependency)
import 'bootstrap';
import './bootstrap';
import { installSearchAnalytics } from './services/searchAnalytics';
import { installBehaviourTelemetry } from './services/behaviourTelemetry';

installSearchAnalytics();
installBehaviourTelemetry();

// Keep the first shared bundle small. These modules initialise homepage and
// header enhancements after the server-rendered document is available; none
// of them is required to paint the initial Blade page.
function loadSiteEnhancements() {
    return Promise.all([
        import('./home'),
        import('./home-searchbar-v4'),
        import('./home-offerings'),
    ]).catch((error) => {
        console.error('[WOW] Site enhancements failed to load:', error);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadSiteEnhancements, { once: true });
} else {
    void loadSiteEnhancements();
}

import { initDrawRandomUnderline } from './lib/wow-links';
import { initClickLoaders } from './lib/wow-buttons';
import './analytics';
import './lib/cart-shortcuts';
import './lib/cart-mini';
import './lib/newsletter-modal';
import './popup-controller';
import './live-chat';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const inertiaRoot = document.getElementById('app');
const isInertiaPage = inertiaRoot && inertiaRoot.dataset && inertiaRoot.dataset.page;

if (isInertiaPage) {
    // Blade pages use this shared bundle for header/cart behavior but do not
    // need the Inertia/Vue/Nuxt UI runtime. Load that larger stack only when
    // Laravel has actually rendered an Inertia root.
    Promise.all([
        import('@inertiajs/vue3'),
        import('laravel-vite-plugin/inertia-helpers'),
        import('vue'),
        import('@nuxt/ui/vue-plugin'),
        import('../../vendor/tightenco/ziggy'),
        import('../css/nuxt-ui.css'),
    ]).then(([inertia, helpers, vueModule, uiModule, ziggyModule]) => {
        const { createInertiaApp } = inertia;
        const { resolvePageComponent } = helpers;
        const { createApp, h } = vueModule;
        const ui = uiModule.default;
        const { ZiggyVue } = ziggyModule;

        createInertiaApp({
            // If a page passes a full title that already contains the app name,
            // don't append it again. Otherwise, append using a hyphen separator.
            title: (title) => {
                const t = String(title || '').trim();
                return t && t.includes(appName) ? t : `${t} - ${appName}`;
            },
            resolve: (name) =>
                resolvePageComponent(
                    `./Pages/${name}.vue`,
                    import.meta.glob('./Pages/**/*.vue'),
                ),
            setup({ el, App, props, plugin }) {
                try {
                    const authUser = props?.initialPage?.props?.auth?.user || props?.auth?.user;
                    const authIntent = window.sessionStorage?.getItem('wow_auth_intent');
                    if (authUser && (authIntent === 'login' || authIntent === 'sign_up')) {
                        window.WOWAnalytics?.track?.(authIntent, { method: 'site-auth' });
                        window.sessionStorage?.removeItem('wow_auth_intent');
                    }
                } catch (_) {}

                const vue = createApp({ render: () => h(App, props) })
                    .use(plugin)
                    .use(ui)
                    .use(ZiggyVue)
                    .mount(el);

                // Init link underline + button loaders on first mount
                try { initDrawRandomUnderline(); initClickLoaders(); } catch {}

                // Re-init after each successful Inertia navigation
                try {
                    document.addEventListener('inertia:success', () => {
                        initDrawRandomUnderline();
                        initClickLoaders();
                    });
                } catch {}

                return vue;
            },
            progress: {
                color: '#549483',
            },
        });
    }).catch((error) => {
        console.error('[WOW] Inertia runtime failed to load:', error);
    });
} else {
    // Non-Inertia Blade views still load this bundle for shared UI behaviour (header, cart, etc.)
    console.debug('[WOW] Inertia mount skipped: #app root not present on this page.');
}
