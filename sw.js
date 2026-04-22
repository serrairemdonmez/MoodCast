/**
 * MoodCast — Service Worker (PWA)
 * Temel sayfaları cache'ler, çevrimdışı çalışmayı destekler
 */
const CACHE = 'moodcast-v1';
const OFFLINE_URLS = [
    '/moodcast/',
    '/moodcast/index.php',
    '/moodcast/css/style.css',
    '/moodcast/js/app.js',
    '/moodcast/js/weather-canvas.js',
    '/moodcast/manifest.json',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(cache => cache.addAll(OFFLINE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;
    e.respondWith(
        fetch(e.request)
            .then(res => {
                const clone = res.clone();
                caches.open(CACHE).then(c => c.put(e.request, clone));
                return res;
            })
            .catch(() => caches.match(e.request))
    );
});
