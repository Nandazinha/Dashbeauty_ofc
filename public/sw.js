const CACHE_NAME = "dashbeauty-v1";
const urlsToCache = [
  "/",
  "/TCC/Dashbeauty/public/index.html",
  "/TCC/Dashbeauty/public/login.html",
  "/TCC/Dashbeauty/public/register.html",
  "/TCC/Dashbeauty/public/client.html",
  "/TCC/Dashbeauty/public/business.html",
  "/TCC/Dashbeauty/public/css/style.css",
  "/TCC/Dashbeauty/public/js/app.js",
  "/TCC/Dashbeauty/public/js/api.js",
  "/TCC/Dashbeauty/public/manifest.json",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(urlsToCache)),
  );
});

self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches
      .match(event.request)
      .then((response) => response || fetch(event.request)),
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        }),
      );
    }),
  );
});
