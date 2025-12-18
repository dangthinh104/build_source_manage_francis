import './bootstrap';
import '../css/app.css';
import 'vue3-toastify/dist/index.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Vue3Toastify from 'vue3-toastify';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Glob all page components - the warnings are harmless when using single bundle
const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, pages),
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

