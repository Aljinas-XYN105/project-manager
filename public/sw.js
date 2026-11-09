const CACHE_VERSION = 'v3';
const STATIC_CACHE  = `workzen-static-${CACHE_VERSION}`;
const ASSET_CACHE   = `workzen-assets-${CACHE_VERSION}`;

// Auth/guest pages — never intercept these, always go straight to network
const SKIP_PATHS = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-email', '/offline'];

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

    // Skip Inertia XHR
    if (req.headers.get('X-Inertia')) return;

    // Never intercept auth/guest pages — let them hit the network directly
    if (SKIP_PATHS.some(p => url.pathname === p || url.pathname.startsWith(p + '/'))) return;

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

    // Navigation requests → network-first with timeout, offline fallback
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

function fetchWithTimeout(req, ms = 15000) {
    return Promise.race([
        fetch(req),
        new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), ms)),
    ]);
}

async function networkFirstWithOffline(req) {
    try {
        return await fetchWithTimeout(req);
    } catch {
        // Only show the offline page when the user is genuinely offline.
        // If they are online but the server is slow/down, let the browser show
        // its own error rather than a misleading "You're offline" page.
        if (!self.navigator.onLine) {
            const cached = await caches.match(req);
            if (cached) return cached;

            const offline = await caches.match('/offline');
            if (offline) return offline;
        }

        // Online but server unreachable — serve stale cache if available,
        // otherwise propagate the error (browser error page).
        const cached = await caches.match(req);
        if (cached) return cached;

        return new Response('<h1>Server unreachable</h1>', {
            headers: { 'Content-Type': 'text/html' },
        });
    }
}
