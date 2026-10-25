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
        status: null,
        onSend: () => undefined,
    }));
    const activeMarkup = renderToStaticMarkup(createElement(TestNotificationControl, {
        active: true,
        disabled: false,
        sending: false,
        status: null,
        onSend: () => undefined,
    }));
    const sendingMarkup = renderToStaticMarkup(createElement(TestNotificationControl, {
        active: true,
        disabled: false,
        sending: true,
        status: { type: 'success', message: 'Test notification sent successfully.' },
        onSend: () => undefined,
    }));
    const errorMarkup = renderToStaticMarkup(createElement(TestNotificationControl, {
        active: false,
        disabled: false,
        sending: false,
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
