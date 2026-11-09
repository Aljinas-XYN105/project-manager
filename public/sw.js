const CACHE_VERSION = 'v1';
const STATIC_CACHE  = `workzen-static-${CACHE_VERSION}`;
const ASSET_CACHE   = `workzen-assets-${CACHE_VERSION}`;

// ── Install ──────────────────────────────────────────────────
self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(STATIC_CACHE).then(cache =>
            cache.addAll(['/offline'])
        ).catch(() => {})
    );
});

// ── Activate: clean stale caches ─────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(k => k !== STATIC_CACHE && k !== ASSET_CACHE)
                    .map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch ─────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const req = event.request;
    const url = new URL(req.url);

    // Only handle same-origin GET requests
    if (req.method !== 'GET' || url.origin !== self.location.origin) return;

    // Skip Inertia XHR (they carry X-Inertia header; can't intercept headers
    // here, but they're same-origin fetches — let them pass to network)
    if (req.headers.get('X-Inertia')) return;

    // Vite-built assets: immutable hashed filenames → cache-first forever
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(req, ASSET_CACHE));
        return;
    }

    // Static public assets (icons, images, fonts) → cache-first
    if (
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/fonts/')  ||
        url.pathname.match(/\.(png|jpg|jpeg|webp|gif|svg|ico|woff2?|ttf|otf)$/)
    ) {
        event.respondWith(cacheFirst(req, ASSET_CACHE));
        return;
    }

    // Navigation requests → network-first, offline fallback
    if (req.mode === 'navigate') {
        event.respondWith(networkFirstWithOffline(req));
        return;
    }

    // Everything else → network only
});

// ── Strategies ────────────────────────────────────────────────
async function cacheFirst(req, cacheName) {
    const cached = await caches.match(req);
    if (cached) return cached;

    const response = await fetch(req);
    if (response.ok) {
        const cache = await caches.open(cacheName);
        cache.put(req, response.clone());
    }
    return response;
}

async function networkFirstWithOffline(req) {
    try {
        const response = await fetch(req);
        // Cache the latest successful page load
        if (response.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(req, response.clone());
        }
        return response;
    } catch {
        // Offline: try cached version first, then /offline fallback
        const cached = await caches.match(req);
        if (cached) return cached;

        const offline = await caches.match('/offline');
        if (offline) return offline;

        return new Response('<h1>You are offline</h1>', {
            headers: { 'Content-Type': 'text/html' },
        });
    }
}
