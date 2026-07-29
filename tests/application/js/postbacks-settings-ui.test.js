const test = require('node:test');
const assert = require('node:assert/strict');

const {
  filterCatalogValues,
  ordinaryEventCatalogFromState,
  parseThresholdCatalog,
  uniqueStrings,
} = require('../../../code/admin/js/campsettings/postbacks.js');

test('chip catalogs remove duplicates and already selected values', () => {
  assert.deepEqual(
    uniqueStrings(['Purchase', ' purchase ', 'Lead'], true),
    ['Purchase', 'Lead']
  );
  assert.deepEqual(
    filterCatalogValues(
      ['Lead', 'Purchase', 'Reject', 'Trash'],
      ['purchase'],
      'r',
      true
    ),
    ['Reject', 'Trash']
  );
});

test('ordinary S2S event catalog contains enabled event names but no performance metrics', () => {
  assert.deepEqual(
    ordinaryEventCatalogFromState({
      scrollEnabled: true,
      scrollThresholds: '90,50,50,invalid',
      timeEnabled: true,
      timeThresholds: '60,120',
      performanceEnabled: true,
      customEvents: [
        'cta_click',
        'performance',
        'performance_lcp',
        'scroll_75',
        'stay_30s',
        'BadEvent',
        'cta_click',
      ],
    }),
    ['scroll_50', 'scroll_90', 'stay_60s', 'stay_120s', 'cta_click']
  );
});

test('disabled collectors do not contribute threshold events', () => {
  assert.deepEqual(
    ordinaryEventCatalogFromState({
      scrollEnabled: false,
      scrollThresholds: '50',
      timeEnabled: false,
      timeThresholds: '60',
      customEvents: [],
    }),
    []
  );
  assert.deepEqual(parseThresholdCatalog(' 3, 1 ,3,0,101', 100), [1, 3]);
});
