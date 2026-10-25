import assert from 'node:assert/strict';
import test from 'node:test';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { NotificationHelp } from '../../storage/framework/testing/frontend/components/NotificationHelp.js';

test('notification help renders collapsed operating-system guidance', () => {
    const markup = renderToStaticMarkup(createElement(NotificationHelp));

    assert.match(markup, /^<details/);
    assert.doesNotMatch(markup, /<details[^>]* open/);
    assert.match(markup, /Not receiving notifications\?/);
    assert.match(markup, /browser may not be enough/);
    assert.match(markup, /operating system must also allow Google Chrome/);
    assert.match(markup, /macOS/);
    assert.match(markup, /System Settings → Notifications → Google Chrome/);
    assert.match(markup, /Allow Notifications/);
    assert.match(markup, /Windows 10\/11/);
    assert.match(markup, /Settings → System → Notifications/);
    assert.match(markup, /Enable notifications and allow notifications from Google Chrome/);
    assert.match(markup, /Do Not Disturb/);
    assert.strictEqual(markup.match(/Settings → Privacy and security → Site settings → Notifications/g)?.length, 2);
    assert.strictEqual(markup.match(/Ensure notifications are allowed for Luma/g)?.length, 2);
});
