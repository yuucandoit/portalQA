/**
 * QA Portal - Service Worker
 * PWA offline shell, static asset caching, and web push notifications
 */

const CACHE_NAME = 'qa-portal-v1.0.0';

const PRECACHE_ASSETS = [
    '/offline',
    '/manifest.webmanifest',
    '/manifest.json',
    '/favicon.ico',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/icon-maskable-192x192.png',
    '/icons/icon-maskable-512x512.png',
    '/icons/apple-touch-icon.png'
];

// 1. Install Event: Precache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[PWA SW] Precache partial error (ignored):', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// 2. Activate Event: Clean up outdated caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME && name.startsWith('qa-portal-')) {
                        console.log('[PWA SW] Removing old cache:', name);
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Fetch Event: Smart routing & caching
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Don't intercept non-http(s) or WebSocket requests
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Never cache sensitive endpoints or mutating routes
    if (url.pathname.includes('/logout') ||
        url.pathname.startsWith('/broadcasting/') ||
        url.pathname.includes('/chat/channels/') && url.pathname.includes('/messages') ||
        url.port === '6001' || url.port === '8080') {
        return;
    }

    // Navigation requests (HTML documents) -> Network-first with /offline fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Only cache successful standard navigation responses
                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cache = await caches.open(CACHE_NAME);
                    // Try to serve previously cached version of this page
                    const cachedResponse = await cache.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Otherwise serve the offline fallback page
                    const offlineShell = await cache.match('/offline');
                    return offlineShell || new Response('Koneksi terputus. Silakan periksa jaringan Anda.', {
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
                    });
                })
        );
        return;
    }

    // Static assets (Vite build, icons, fonts, CSS/JS) -> Cache-first with Network fallback
    const isStaticAsset = url.pathname.startsWith('/build/') ||
                          url.pathname.startsWith('/icons/') ||
                          url.pathname.startsWith('/assets/') ||
                          url.hostname.includes('fonts.googleapis.com') ||
                          url.hostname.includes('fonts.gstatic.com') ||
                          url.hostname.includes('cdnjs.cloudflare.com');

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return networkResponse;
                }).catch(() => {
                    // Static asset failed and not in cache
                    return new Response('', { status: 408 });
                });
            })
        );
        return;
    }

    // Default -> Network first, fallback to cache
    event.respondWith(
        fetch(request)
            .then((networkResponse) => {
                return networkResponse;
            })
            .catch(() => {
                return caches.match(request);
            })
    );
});

// 4. Web Push Notification Event
self.addEventListener('push', (event) => {
    let data = {
        title: 'QA Portal',
        body: 'Anda memiliki notifikasi baru.',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-96x96.png',
        url: '/chat'
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            data = Object.assign(data, parsed);
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/icons/icon-192x192.png',
        badge: data.badge || '/icons/icon-96x96.png',
        data: { url: data.url || '/' },
        vibrate: [100, 50, 100],
        tag: data.tag || 'qa-portal-notif',
        renotify: true
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// 5. Notification Click Handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data && event.notification.data.url ? event.notification.data.url : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// 6. Handle messages from client tabs
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

