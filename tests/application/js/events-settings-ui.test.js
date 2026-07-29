'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const { parseEventThresholds } = require('../../../code/admin/js/campsettings/events.js');

const fixturePath = path.resolve(
    __dirname,
    '../../engine/fixtures/event-threshold-normalization.json'
);
const cases = JSON.parse(fs.readFileSync(fixturePath, 'utf8'));

test('threshold parsing drops invalid values, removes duplicates, and sorts numbers', () => {
    for (const fixture of cases) {
        assert.deepEqual(
            parseEventThresholds(fixture.raw, fixture.maximum).values,
            fixture.expected
        );
    }
});
