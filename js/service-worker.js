// service-worker.js

self.addEventListener('push', function(event) {
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: 'assets/icons/icon-192.png',
        badge: 'assets/icons/icon-192.png' // สำหรับ Android
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientsArr => {
            const hadWindowToFocus = clientsArr.some(windowClient => windowClient.url === self.location.origin + '/index.php' ? (windowClient.focus(), true) : false);
            if (!hadWindowToFocus) clients.openWindow(self.location.origin + '/index.php').then(windowClient => windowClient ? windowClient.focus() : null);
        })
    );
});
