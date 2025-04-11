self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : {};

    const options = {
        body: data.body || 'You have a new notification!',
        icon: data.icon || '/icon.png',
        badge: data.badge || '/badge.png'
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'New Notification', options)
    );
});

// Handle click event on notification
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('https://ur-sg.com/persoChat')
    );
});
