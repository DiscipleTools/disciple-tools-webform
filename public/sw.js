var Version = "1.1"
var CACHE = `dt_form_${Version}`;

self.addEventListener('install', function(evt) {
  console.log('The service worker is being installed.');
  evt.waitUntil(precache());
});

// self.addEventListener('fetch', function(evt) {
//   if (evt.request.method === "GET") {
//     console.log('The service worker is serving the asset.');
// console.log(evt);
//     evt.respondWith(fromCache(evt.request));
//     evt.waitUntil(update(evt.request));
//   }
// });


self.addEventListener('fetch', function(event) {
  console.log('Handling fetch event for', event.request.url);

  event.respondWith(

    // Opens Cache objects that start with 'font'.
    caches.open(CACHE).then(function(cache) {
      return cache.match(event.request).then(function(response) {

        if (response) {
          console.log('Found response in cache:', response);
          update(event.request);
          return response;
        }
        console.log('Fetching request from the network');

        return fetch(event.request).then(function(networkResponse) {
          console.log(networkResponse);
          cache.add(event.request, networkResponse.clone());

          return networkResponse;
        });
      }).catch(function(error) {

        // Handles exceptions that arise from match() or fetch().
        console.error('Error in fetch handler:', error);

        throw error;
      });
    })
  );
});

function precache() {
  return caches.open(CACHE).then(function (cache) {
    console.log('Opened cache');
    return cache.addAll([
      '/wp-content/plugins/disciple-tools-webform/public/jquery-migrate.min.js',
      '/wp-content/plugins/disciple-tools-webform/public/jquery.min.js',
      '/wp-content/plugins/disciple-tools-webform/public/jquery.validate.min.js',
      '/wp-content/plugins/disciple-tools-webform/public/public.js?ver=1.1',
      '/wp-content/plugins/disciple-tools-webform/public/spinner.svg'

    ]);
  })
}

// function fromCache(request) {
//   return caches.open(CACHE).then(function (cache) {
//     return cache.match(request).then(function (matching) {
//       return matching || Promise.reject('no-match');
//     });
//   });
// }

function update(request) {
  return caches.open(CACHE).then(function (cache) {
    return fetch(request).then(function (response) {
      console.log("updated");
      return cache.put(request, response);
    });
  });
}
