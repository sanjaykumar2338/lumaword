import assert from 'node:assert/strict';
import test from 'node:test';
import {
    BrowserPushError,
    disableBrowserPush,
    enableBrowserPush,
    getCurrentBrowserPushSubscription,
} from '../../storage/framework/testing/frontend/lib/browserPush.js';

const endpoint = 'https://push.example.test/subscriptions/browser-1';

test('refresh returns no active subscription when this browser is unsubscribed', async () => {
    const environment = browserPushEnvironment({ currentSubscription: null });

    const subscription = await getCurrentBrowserPushSubscription(environment);

    assert.strictEqual(subscription, null);
});

test('enable registers the worker, creates a subscription, and saves it', async () => {
    const calls = [];
    const subscription = pushSubscription();
    const environment = browserPushEnvironment({
        createdSubscription: subscription,
        calls,
    });

    const result = await enableBrowserPush('AQID', async (payload) => {
        calls.push(['save', payload]);
    }, environment);

    assert.strictEqual(result, subscription);
    assert.deepStrictEqual(calls.map(([name]) => name), ['permission', 'register', 'ready', 'get', 'subscribe', 'save']);
    assert.deepStrictEqual(calls.at(-1)[1], {
        endpoint,
        keys: { p256dh: 'public-key', auth: 'auth-token' },
        content_encoding: 'aes128gcm',
    });
    assert.deepStrictEqual([...calls.find(([name]) => name === 'subscribe')[1].applicationServerKey], [1, 2, 3]);
});

test('disable removes the backend subscription and unsubscribes the browser', async () => {
    const calls = [];
    const subscription = pushSubscription({ calls });

    await disableBrowserPush(subscription, async (subscriptionEndpoint) => {
        calls.push(['remove', subscriptionEndpoint]);
    });

    assert.deepStrictEqual(calls, [
        ['remove', endpoint],
        ['unsubscribe'],
    ]);
});

test('subscription failures return a clear error and do not save', async () => {
    let saved = false;
    const environment = browserPushEnvironment({
        subscribeError: new DOMException('Push service unavailable', 'AbortError'),
    });

    await assert.rejects(
        enableBrowserPush('AQID', async () => {
            saved = true;
        }, environment),
        (error) => error instanceof BrowserPushError
            && error.message === 'The browser could not create a push subscription. Check its notification settings and try again.',
    );
    assert.strictEqual(saved, false);
});

function browserPushEnvironment({
    currentSubscription = null,
    createdSubscription = pushSubscription(),
    subscribeError,
    calls = [],
} = {}) {
    const pushManager = {
        getSubscription: async () => {
            calls.push(['get']);

            return currentSubscription;
        },
        subscribe: async (options) => {
            calls.push(['subscribe', options]);

            if (subscribeError) {
                throw subscribeError;
            }

            return createdSubscription;
        },
    };

    return {
        requestPermission: async () => {
            calls.push(['permission']);

            return 'granted';
        },
        registerServiceWorker: async () => {
            calls.push(['register']);
        },
        getReadyRegistration: async () => {
            calls.push(['ready']);

            return { pushManager };
        },
        getRegistration: async () => ({ pushManager }),
    };
}

function pushSubscription({ calls = [] } = {}) {
    return {
        endpoint,
        toJSON: () => ({
            endpoint,
            keys: { p256dh: 'public-key', auth: 'auth-token' },
        }),
        unsubscribe: async () => {
            calls.push(['unsubscribe']);

            return true;
        },
    };
}
