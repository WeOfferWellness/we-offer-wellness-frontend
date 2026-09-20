import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import ui from '@nuxt/ui/vite';

const toNumberOr = (value, fallback) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
};

const toHostOr = (value, fallback) => {
    if (value === 'true') {
        return true;
    }

    if (value === 'false') {
        return false;
    }

    return value || fallback;
};

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    const devHost = toHostOr(env.VITE_DEV_SERVER_HOST, '0.0.0.0');
    const devPort = toNumberOr(env.VITE_DEV_SERVER_PORT, 5173);
    const previewHost = toHostOr(env.VITE_PREVIEW_HOST ?? env.VITE_DEV_SERVER_HOST, '0.0.0.0');
    const previewPort = toNumberOr(env.VITE_PREVIEW_PORT, 4173);

    const hmr = {};

    if (env.VITE_HMR_HOST) {
        hmr.host = env.VITE_HMR_HOST;
    }

    if (env.VITE_HMR_PORT) {
        hmr.port = toNumberOr(env.VITE_HMR_PORT, devPort);
    }

    if (env.VITE_HMR_PROTOCOL) {
        hmr.protocol = env.VITE_HMR_PROTOCOL;
    }

    const serverConfig = {
        host: devHost,
        port: devPort,
        strictPort: true,
    };

    if (env.VITE_DEV_SERVER_ORIGIN) {
        serverConfig.origin = env.VITE_DEV_SERVER_ORIGIN;
    }

    if (Object.keys(hmr).length > 0) {
        serverConfig.hmr = hmr;
    }

    return {
        build: {
            minify: 'esbuild',
            cssMinify: true,
            sourcemap: false,
            emptyOutDir: true,
            chunkSizeWarningLimit: 2000,
            esbuild: {
                drop: ['console', 'debugger'],
            },
        },
        server: serverConfig,
        preview: {
            host: previewHost,
            port: previewPort,
            strictPort: true,
        },
        plugins: [
            ui(),
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    // Provide a direct CSS entry so Blade calls like
                    // @vite('resources/css/we-offer-wellness-base-styles.css')
                    // resolve in production manifests when present.
                    'resources/css/we-offer-wellness-base-styles.css',
                    'resources/css/head-inline.css',
                ],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
    };
});
