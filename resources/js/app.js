import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

// Keep Echo/Reverb real-time working
import './echo';

// Register service worker for PWA support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .catch(() => {});
    });
}

// Show full-page offline screen when internet is lost
if (!navigator.onLine) {
    window.location.href = '/offline';
}
window.addEventListener('offline', () => {
    window.location.href = '/offline';
});

createInertiaApp({
    title: (title) => `${title} — Workzen`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#7c3aed',
    },
});
