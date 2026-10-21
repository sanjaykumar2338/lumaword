self.addEventListener('push', (event) => {
    const fallback = {
        title: 'Your daily moment with Luma',
        body: 'Take a few minutes to pause, reflect, and spend time in God’s Word today.',
        url: '/devotionals',
    };
    const data = event.data ? event.data.json() : fallback;

    event.waitUntil(self.registration.showNotification(data.title ?? fallback.title, {
        body: data.body ?? fallback.body,
        icon: '/logo-mark.png',
        badge: '/favicon-32x32.png',
        data: { url: data.url ?? fallback.url },
        tag: 'luma-devotional-reminder',
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = new URL(event.notification.data?.url ?? '/devotionals', self.location.origin).href;

    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
        const existing = windows.find((client) => client.url === url);

        return existing ? existing.focus() : clients.openWindow(url);
    }));
});
