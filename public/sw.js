/* ═══════════════════════════════════════════════════════════════════
   MedBuddy service worker — the patient app only.

   HOW THIS DIFFERS FROM THE PROTOTYPE'S, AND WHY.

   The static app kept its data in localStorage, so every page was a shell
   it could cache and serve whole. This one is server-rendered: a cached
   /me would show yesterday's doses as though they were today's, and a
   cached Livewire snapshot would desynchronise the moment the patient
   ticked anything. So HTML is network-first here, not cache-first.

   What this buys, honestly:
     · The app installs to a home screen and opens like an app.
     · Styles and fonts are served from cache, so nothing re-flows or
       swaps fonts on a slow connection — a font swap mid-dose reads as
       the app being broken.
     · Losing the connection gets a real page with the emergency number
       and the last dose list on it, not the browser's error screen.

   What it does NOT buy, and must not be claimed to:
     · Ticking off a dose still needs a connection. Doing that offline
       needs a local write queue and a sync/conflict story, which is a
       real piece of work rather than a cache policy.
   ═══════════════════════════════════════════════════════════════════ */

const VERSION = 'medbuddy-v1';
const ASSETS = VERSION + '-assets';
const PAGES  = VERSION + '-pages';

/* Cached on install so the very first offline load already has them. */
const SHELL = [
  '/offline',
  '/manifest.webmanifest',
  '/styles/fonts.css',
  '/styles/tokens.css',
  '/styles/app.css',
  '/styles/shell.css',
  '/styles/chart.css',
  '/styles/respond.css',
  '/js/recorder.js',
  // Fonts are cached because a swap-in mid-dose looks like a fault.
  '/assets/fonts/plex-sans-400-latin.woff2',
  '/assets/fonts/plex-sans-500-latin.woff2',
  '/assets/fonts/plex-sans-600-latin.woff2',
  '/assets/fonts/plex-mono-400-latin.woff2',
  '/assets/fonts/plex-mono-500-latin.woff2',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(ASSETS)
      // addAll rejects entirely if one file 404s, which would leave the app
      // with no cache at all. Add individually so one miss costs one file.
      .then(function (cache) {
        return Promise.all(SHELL.map(function (url) {
          return cache.add(url).catch(function () {});
        }));
      })
      .then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys()
      .then(function (keys) {
        return Promise.all(keys
          .filter(function (k) { return k !== ASSETS && k !== PAGES; })
          .map(function (k) { return caches.delete(k); }));
      })
      .then(function () { return self.clients.claim(); })
  );
});

function isPatientPage(url) {
  return url.pathname === '/me' || url.pathname.indexOf('/me/') === 0;
}

function isCacheableAsset(url) {
  return /\.(css|woff2?|js|svg|png|ico|webmanifest)$/.test(url.pathname)
    && url.pathname.indexOf('/livewire/') !== 0;
}

self.addEventListener('fetch', function (event) {
  const request = event.request;

  // Only GET, only this origin. Livewire's update endpoint is a POST and is
  // never touched; neither is anything going anywhere else.
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // The clinic console is a desk tool on a wired connection. Caching it would
  // only create a way for staff to read stale patient data.
  if (url.pathname.indexOf('/clinic') === 0) return;

  /* ── Static assets: cache first ─────────────────────────────────── */
  if (isCacheableAsset(url)) {
    event.respondWith(
      caches.match(request).then(function (cached) {
        if (cached) return cached;
        return fetch(request).then(function (response) {
          if (response && response.ok) {
            const copy = response.clone();
            caches.open(ASSETS).then(function (c) { c.put(request, copy); }).catch(function () {});
          }
          return response;
        });
      })
    );
    return;
  }

  /* ── Patient pages: network first, cache as a fallback ──────────── */
  if (request.mode === 'navigate' && isPatientPage(url)) {
    event.respondWith(
      fetch(request)
        .then(function (response) {
          if (response && response.ok) {
            const copy = response.clone();
            caches.open(PAGES).then(function (c) { c.put(request, copy); }).catch(function () {});
          }
          return response;
        })
        .catch(function () {
          // The last good copy of this exact screen, if we have one; the
          // offline page if we do not. Either beats the browser's error.
          return caches.match(request).then(function (cached) {
            return cached || caches.match('/offline');
          });
        })
    );
  }
});
