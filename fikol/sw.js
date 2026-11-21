// Fikol przeniesiony na fikol.novopix.uk: ten worker czyści stare zapisy i odsyła na nowy adres.
self.addEventListener('install',()=>self.skipWaiting());
self.addEventListener('activate',e=>e.waitUntil((async()=>{
  for(const k of await caches.keys())await caches.delete(k);
  await self.registration.unregister();
  const cs=await self.clients.matchAll({type:'window'});
  cs.forEach(c=>c.navigate('https://fikol.novopix.uk/'));
})()));
