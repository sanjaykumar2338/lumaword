export type PushSubscriptionPayload = {
    endpoint: string;
    keys: {
        p256dh: string;
        auth: string;
    };
    content_encoding: 'aes128gcm';
};

type PushManagerLike = {
    getSubscription: () => Promise<PushSubscription | null>;
    subscribe: (options: PushSubscriptionOptionsInit) => Promise<PushSubscription>;
};

type ServiceWorkerRegistrationLike = {
    pushManager: PushManagerLike;
};

export type BrowserPushEnvironment = {
    requestPermission: () => Promise<NotificationPermission>;
    registerServiceWorker: () => Promise<unknown>;
    getReadyRegistration: () => Promise<ServiceWorkerRegistrationLike>;
    getRegistration: () => Promise<ServiceWorkerRegistrationLike | undefined>;
};

export class BrowserPushError extends Error {}

export function isBrowserPushAvailable(): boolean {
    return typeof navigator !== 'undefined'
        && 'serviceWorker' in navigator
        && typeof window !== 'undefined'
        && 'PushManager' in window
        && 'Notification' in window;
}

export async function getCurrentBrowserPushSubscription(
    environment: BrowserPushEnvironment = browserPushEnvironment(),
): Promise<PushSubscription | null> {
    try {
        const registration = await environment.getRegistration();

        return await registration?.pushManager.getSubscription() ?? null;
    } catch {
        throw new BrowserPushError('Browser notification status could not be checked. Please refresh and try again.');
    }
}

export async function enableBrowserPush(
    vapidPublicKey: string,
    saveSubscription: (payload: PushSubscriptionPayload) => Promise<void>,
    environment: BrowserPushEnvironment = browserPushEnvironment(),
): Promise<PushSubscription> {
    const permission = await environment.requestPermission();

    if (permission !== 'granted') {
        throw new BrowserPushError('Notification permission was not granted. Allow notifications in your browser and try again.');
    }

    let subscription: PushSubscription;

    try {
        await environment.registerServiceWorker();
        const registration = await environment.getReadyRegistration();
        subscription = await registration.pushManager.getSubscription()
            ?? await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64UrlToUint8Array(vapidPublicKey),
            });
    } catch {
        throw new BrowserPushError('The browser could not create a push subscription. Check its notification settings and try again.');
    }

    const serialized = subscription.toJSON();

    if (!serialized.keys?.p256dh || !serialized.keys.auth) {
        throw new BrowserPushError('The browser returned an invalid push subscription. Please try again.');
    }

    await saveSubscription({
        endpoint: subscription.endpoint,
        keys: {
            p256dh: serialized.keys.p256dh,
            auth: serialized.keys.auth,
        },
        content_encoding: 'aes128gcm',
    });

    return subscription;
}

export async function disableBrowserPush(
    subscription: PushSubscription,
    removeSubscription: (endpoint: string) => Promise<void>,
): Promise<void> {
    await removeSubscription(subscription.endpoint);

    let unsubscribed: boolean;

    try {
        unsubscribed = await subscription.unsubscribe();
    } catch {
        throw new BrowserPushError('The browser could not remove its push subscription. Please try again.');
    }

    if (!unsubscribed) {
        throw new BrowserPushError('The browser did not remove its push subscription. Please try again.');
    }
}

function browserPushEnvironment(): BrowserPushEnvironment {
    return {
        requestPermission: () => Notification.requestPermission(),
        registerServiceWorker: () => navigator.serviceWorker.register('/service-worker.js'),
        getReadyRegistration: () => navigator.serviceWorker.ready,
        getRegistration: () => navigator.serviceWorker.getRegistration(),
    };
}

function base64UrlToUint8Array(value: string): Uint8Array<ArrayBuffer> {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const decoded = atob(base64);
    const bytes = new Uint8Array(new ArrayBuffer(decoded.length));

    for (let index = 0; index < decoded.length; index++) {
        bytes[index] = decoded.charCodeAt(index);
    }

    return bytes;
}
