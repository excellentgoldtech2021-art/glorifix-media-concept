/**
 * Glorifix Media Concept Limited - PWA Service Worker
 * Provides offline support & instant asset caching for fast mobile performance.
 */

const CACHE_NAME = 'glorifix-pwa-v1';
const PRECACHE_ASSETS = [
  './',
  'index.html',
  'about.html',
  'services.html',
  'pricing.html',
  'gallery.html',
  'contact.html',
  'css/style.css',
  'css/voice-agent.css',
  'js/app.js',
  'js/voice-agent.js',
  'assets/logo.png',
  'manifest.json'
];

// Install: Pre-cache core shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS).catch((err) => {
        console.warn('PWA Precache item warning:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// Activate: Clean up older cache versions
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: Network first with cache fallback for HTML, Cache first for assets
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Skip admin endpoints and external APIs from aggressive caching
  if (url.pathname.includes('/admin/') || url.pathname.includes('/api/')) {
    return;
  }

  // HTML Pages: Network-first, fallback to cache
  if (req.headers.get('accept') && req.headers.get('accept').includes('text/html')) {
    event.respondWith(
      fetch(req)
        .then((networkRes) => {
          if (networkRes && networkRes.status === 200) {
            const clone = networkRes.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
          }
          return networkRes;
        })
        .catch(() => caches.match(req).then((cached) => cached || caches.match('index.html')))
    );
    return;
  }

  // Static Assets (CSS, JS, Images, Fonts): Cache-first with background network update
  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) {
        // Background revalidate
        fetch(req).then((networkRes) => {
          if (networkRes && networkRes.status === 200) {
            caches.open(CACHE_NAME).then((cache) => cache.put(req, networkRes));
          }
        }).catch(() => {});
        return cached;
      }

      return fetch(req).then((networkRes) => {
        if (networkRes && networkRes.status === 200) {
          const clone = networkRes.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
        }
        return networkRes;
      }).catch(() => {
        return new Response('Offline resource unavailable', { status: 503, statusText: 'Offline' });
      });
    })
  );
});
