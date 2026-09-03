// PAYNET XOLIS - service worker (PWA)
const CACHE='xolis-v1';
self.addEventListener('install', function(e){
 self.skipWaiting();
 e.waitUntil(caches.open(CACHE).then(function(c){ return c.addAll(['logo.png','logo_full.png','manifest.webmanifest']).catch(function(){}); }));
});
self.addEventListener('activate', function(e){
 e.waitUntil(caches.keys().then(function(ks){ return Promise.all(ks.map(function(k){ if(k!==CACHE) return caches.delete(k); })); }));
 self.clients.claim();
});
// Network-first: sahifalar doim yangi bo'lsin, internet yo'q bo'lsa keshdan
self.addEventListener('fetch', function(e){
 var req=e.request;
 if(req.method!=='GET') return;
 e.respondWith(
  fetch(req).then(function(res){
   if(res && res.status===200 && req.url.indexOf('api.php')===-1){ var cp=res.clone(); caches.open(CACHE).then(function(c){ c.put(req,cp); }); }
   return res;
  }).catch(function(){ return caches.match(req); })
 );
});
