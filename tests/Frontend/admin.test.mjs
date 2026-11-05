import assert from 'node:assert/strict';
import test from 'node:test';
import { chartPercent, formatAdminFeature, formatMicrosAsUsd } from '../../storage/framework/testing/frontend/lib/admin.js';

test('admin cost formatter converts stored micro-dollars without overstating precision', () => {
    assert.strictEqual(formatMicrosAsUsd(1_250_000), '$1.25');
    assert.strictEqual(formatMicrosAsUsd(375), '$0.0004');
});

test('admin chart helpers format labels and preserve visibility for non-zero values', () => {
    assert.strictEqual(formatAdminFeature('sermon_processing'), 'Sermon Processing');
    assert.strictEqual(chartPercent(1, 100), 4);
    assert.strictEqual(chartPercent(0, 100), 0);
});
