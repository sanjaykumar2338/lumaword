import assert from 'node:assert/strict';
import test from 'node:test';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { NotificationHelp } from '../../storage/framework/testing/frontend/components/NotificationHelp.js';

test('notification help renders collapsed desktop and mobile guidance', () => {
    const markup = renderToStaticMarkup(createElement(NotificationHelp));

    assert.match(markup, /^<details/);
    assert.doesNotMatch(markup, /<details[^>]* open/);
    assert.match(markup, /How to Enable Notifications/);
    assert.match(markup, /Desktop \(Chrome, Edge, etc\.\)/);
    assert.match(markup, /Click <strong[^>]*>Enable Browser Notifications<\/strong>/);
    assert.match(markup, /Select <strong[^>]*>Allow<\/strong> when the browser asks for permission/);
    assert.match(markup, /Android/);
    assert.match(markup, /No Home Screen installation is required/);
    assert.match(markup, /iPhone \(Safari\)/);
    assert.match(markup, /Add to Home Screen/);
    assert.match(markup, /iOS 16\.4 or later is required/);
    assert.match(markup, /Do Not Disturb/);
    assert.match(markup, /Focus modes such as Personal, Sleep, or Do Not Disturb/);
    assert.match(markup, /Allow Notifications From → Add LUMA/);
    assert.match(markup, /Settings → Notifications → LUMA/);
});
