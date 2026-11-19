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

// Show full-page offline screen when internet is lost.
// Debounce 800ms: some browsers fire a spurious 'offline' event during navigation.
let offlineTimer = null;
window.addEventListener('offline', () => {
    clearTimeout(offlineTimer);
    offlineTimer = setTimeout(() => {
        if (!navigator.onLine) {
            try { sessionStorage.setItem('offlineReturnUrl', window.location.href); } catch (_) {}
            window.location.href = '/offline';
        }
    }, 800);
});
window.addEventListener('online', () => clearTimeout(offlineTimer));

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
