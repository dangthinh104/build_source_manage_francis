import './bootstrap';
import '../css/app.css';
import 'vue3-toastify/dist/index.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Vue3Toastify from 'vue3-toastify';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Global Error Handler for Chunk Loading Errors
 * 
 * When deploying new versions, old users may have stale asset references
 * that no longer exist on the server. This handler detects such errors
 * and automatically reloads the page to fetch the new versions.
 * 
 * NOTE: Only handles chunk loading errors, NOT all "invalid" responses.
 */

// Handle exceptions during navigation (dynamic import failures)
router.on('exception', (event) => {
    const error = event.detail?.error;
    if (error && (
        error.message?.includes('Failed to fetch dynamically imported module') ||
        error.message?.includes('Importing a module script failed') ||
        error.message?.includes('ChunkLoadError') ||
        error.message?.includes('Loading chunk') ||
        error.name === 'ChunkLoadError'
    )) {
        console.warn('[Inertia] Chunk loading error detected, reloading page...', error.message);
        event.preventDefault();
        window.location.reload();
    }
});

// Also handle Vite-specific preload errors (Vite 4+)
window.addEventListener('vite:preloadError', (event) => {
    console.warn('[Vite] Preload error detected, reloading page...');
    event.preventDefault();
    window.location.reload();
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Vue3Toastify)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
