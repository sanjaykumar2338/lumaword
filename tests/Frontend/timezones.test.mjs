import assert from 'node:assert/strict';
import test from 'node:test';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { TimezoneSelect } from '../../storage/framework/testing/frontend/components/TimezoneSelect.js';
import {
    filterTimezoneOptions,
    isSupportedTimezone,
    resolveReminderTimezone,
} from '../../storage/framework/testing/frontend/lib/timezones.js';

const options = [
    { value: 'UTC', label: '(UTC+00:00) UTC' },
    { value: 'America/New_York', label: '(UTC-05:00) America/New_York' },
    { value: 'Asia/Kolkata', label: '(UTC+05:30) Asia/Kolkata' },
    { value: 'Europe/London', label: '(UTC+00:00) Europe/London' },
];

test('browser timezone is used only when a timezone has not been saved', () => {
    assert.equal(resolveReminderTimezone(false, 'UTC', options, 'Asia/Kolkata'), 'Asia/Kolkata');
    assert.equal(resolveReminderTimezone(true, 'America/New_York', options, 'Asia/Kolkata'), 'America/New_York');
    assert.equal(resolveReminderTimezone(true, 'UTC', options, 'Asia/Kolkata'), 'UTC');
    assert.equal(resolveReminderTimezone(false, 'UTC', options, 'Unsupported/Timezone'), 'UTC');
});

test('timezone options can be searched by identifier or UTC offset', () => {
    assert.deepEqual(filterTimezoneOptions(options, 'kolkata'), [options[2]]);
    assert.deepEqual(filterTimezoneOptions(options, '-05:00'), [options[1]]);
    assert.deepEqual(filterTimezoneOptions(options, 'europe'), [options[3]]);
    assert.equal(isSupportedTimezone('Asia/Kolkata', options), true);
    assert.equal(isSupportedTimezone('Moon/SeaOfTranquility', options), false);
});

test('timezone select renders the saved timezone as an accessible combobox', () => {
    const markup = renderToStaticMarkup(createElement(TimezoneSelect, {
        id: 'settings-timezone',
        label: 'Timezone',
        value: 'Asia/Kolkata',
        options,
        onChange: () => undefined,
        error: 'Select a valid timezone from the list.',
    }));

    assert.match(markup, /role="combobox"/);
    assert.match(markup, /value="\(UTC\+05:30\) Asia\/Kolkata"/);
    assert.match(markup, /aria-controls="settings-timezone-options"/);
    assert.match(markup, /aria-expanded="false"/);
    assert.match(markup, /aria-invalid="true"/);
    assert.match(markup, /Select a valid timezone from the list/);
});
