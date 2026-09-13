// sw.js — FINAL (aman: hanya cache aset statis, tidak pernah cache halaman PHP)
const CACHE = 'smart-attendance-v3';
const ASSETS = [
  'assets/css/public.css',
  'assets/js/public.js',
  'assets/css/admin.css',
  'assets/js/admin.js',
  'manifest.webmanifest'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)));
  self.skipWaiting();
});

// Bersihkan cache versi lama saat aktif
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);

  // Hanya GET, sesama origin, dan file statis (BUKAN .php / API / navigasi)
  if (e.request.method !== 'GET') return;
  if (url.origin !== self.location.origin) return;
  if (!/\.(css|js|png|jpg|jpeg|svg|ico|webmanifest)$/i.test(url.pathname)) return;

  e.respondWith(
    caches.match(e.request).then(hit => hit || fetch(e.request).then(resp => {
      // Simpan hanya respons sukses yang BUKAN redirect
      if (resp.ok && !resp.redirected) {
        const copy = resp.clone();
        caches.open(CACHE).then(c => c.put(e.request, copy));
      }
      return resp;
    }))
  );
});