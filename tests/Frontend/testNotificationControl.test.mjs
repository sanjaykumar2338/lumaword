import assert from 'node:assert/strict';
import test from 'node:test';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { TestNotificationControl } from '../../storage/framework/testing/frontend/components/TestNotificationControl.js';

test('test notification control is only shown for an active subscription and exposes sending state', () => {
    const inactiveMarkup = renderToStaticMarkup(createElement(TestNotificationControl, {
        active: false,
        disabled: false,
        sending: false,
        retryAfter: 0,
        status: null,
        onSend: () => undefined,
    }));
    const activeMarkup = renderToStaticMarkup(createElement(TestNotificationControl, {
        active: true,
        disabled: false,
        sending: false,
        retryAfter: 0,
        status: null,
        onSend: () => undefined,
    }));
    const sendingMarkup = renderToStaticMarkup(createElement(TestNotificationControl, {
        active: true,
        disabled: false,
        sending: true,
        retryAfter: 0,
        status: { type: 'success', message: 'Test notification sent successfully.' },
        onSend: () => undefined,
    }));
    const errorMarkup = renderToStaticMarkup(createElement(TestNotificationControl, {
        active: false,
        disabled: false,
        sending: false,
        retryAfter: 0,
        status: { type: 'error', message: 'The subscription has expired.' },
        onSend: () => undefined,
    }));

    assert.strictEqual(inactiveMarkup, '');
    assert.match(activeMarkup, />Send test notification<\/button>/);
    assert.match(sendingMarkup, /<button[^>]* disabled=""/);
    assert.match(sendingMarkup, />Sending test…<\/button>/);
    assert.match(sendingMarkup, /role="status"/);
    assert.match(sendingMarkup, /Test notification sent successfully/);
    assert.doesNotMatch(errorMarkup, /<button/);
    assert.match(errorMarkup, /role="alert"/);
    assert.match(errorMarkup, /The subscription has expired/);
});

test('test notification control stays disabled during the server retry window', () => {
    const cooldownMarkup = renderToStaticMarkup(createElement(TestNotificationControl, {
        active: true,
        disabled: false,
        sending: false,
        retryAfter: 61,
        status: { type: 'error', message: 'Too many test notifications.' },
        onSend: () => undefined,
    }));

    assert.match(cooldownMarkup, /<button[^>]* disabled=""/);
    assert.match(cooldownMarkup, />Try again in 2 minutes<\/button>/);
});
