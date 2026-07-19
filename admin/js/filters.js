
var tdsFilters = [
    {
        id: 'os',
        label: 'OS',
        input: 'text',
        type: 'string',
        operators: ['in', 'not_in'],
        placeholder: 'Android,iOS,Windows,OS X',
        size: 50
    },
    {
        id: 'osver',
        label: 'OS version',
        input: 'number',
        type: 'integer',
        operators: ['in', 'not_in','less_or_equal','greater_or_equal'],
        placeholder: 10,
        size: 50
    },
    {
        id: 'device',
        label: 'Device',
        input: 'text',
        type: 'string',
        operators: ['in', 'not_in'],
        placeholder: 'desktop,mobile',
        size: 70
    },
    {
        id: 'brand',
        label: 'Brand',
        input: 'text',
        type: 'string',
        operators: ['contains','not_contains','in', 'not_in'],
        size: 70
    },
    {
        id: 'model',
        label: 'Model',
        input: 'text',
        type: 'string',
        operators: ['contains','not_contains','in', 'not_in'],
        size: 70
    },
    {
        id: 'client',
        label: 'Client',
        input: 'text',
        type: 'string',
        operators: ['contains', 'not_contains','in', 'not_in'],
        size: 70
    },
    {
        id: 'clientver',
        label: 'ClientVer',
        input: 'text',
        type: 'string',
        operators: ['less_or_equal','greater_or_equal','in', 'not_in'],
        size: 50
    },
    {
        id: 'country',
        label: 'Country',
        input: 'text',
        type: 'string',
        operators: ['in', 'not_in'],
        placeholder: 'RU,BY,UA'

    },
    {
        id: 'lang',
        label: 'Language',
        input: 'text',
        type: 'string',
        operators: ['in', 'not_in'],
        placeholder: 'en,ru'
    },
    {
        id: 'useragent',
        label: 'UserAgent',
        input: 'text',
        type: 'string',
        operators: ['contains', 'not_contains'],
        size: 70,
        placeholder: 'facebook,facebot,curl,gce-spider,yandex.com,odklbot'
    },
    {
        id: 'isp',
        label: 'ISP',
        input: 'text',
        type: 'string',
        operators: ['contains', 'not_contains'],
        size: 70,
        placeholder: 'facebook,google,yandex,amazon,azure,digitalocean,microsoft'
    },
    {
        id: 'referer',
        label: 'Referer',
        input: 'text',
        type: 'string',
        operators: ['equal', 'not_equal', 'contains', 'not_contains'],
        validation: {
            allow_empty_value: true
        },
        size: 70
    },
    {
        id: 'domain',
        label: 'Domain',
        input: 'text',
        type: 'string',
        operators: ['in', 'not_in'],
        size: 70
    },
    {
        id: 'host',
        label: 'Host',
        input: 'text',
        type: 'string',
        operators: ['in', 'not_in'],
        size: 70
    },
    {
        id: 'vpntor',
        label: 'VPN&Tor',
        type: 'integer',
        input: 'radio',
        values: {
            0: 'Detected',
            1: 'NOT Detected'
        },
        operators: ['equal']
    },
    {
        id: 'ipbase',
        label: 'IP Base',
        type: 'string',
        operators: ['in', 'not_in'],
        placeholder: 'path to base file(s) in bases folder: bots1.txt,bots2.txt',
        size: 70
    },
    {
        id: 'urlparam',
        label: 'URL Parameter',
        type: 'string',
        input: 'text',                
        placeholder: ['URL parameter name', 'value(s) separated by comma'],
        operators: [
            'param_in',
            'param_not_in',
            'param_exists',
            'param_not_exists'
        ],
        size: 30
    }
];

var uniquenessFilter = {
    id: 'uniqueness',
    field: 'uniqueness',
    label: 'Uniqueness',
    type: 'string',
    input: 'radio',
    values: {
        campaign: 'Campaign',
        flow: 'Flow'
    },
    operators: ['is_unique', 'is_not_unique']
};

function getFlowTdsFilters() {
    var enabled = document.getElementById('uniqueness-counting-toggle')?.checked === true;
    return enabled ? tdsFilters.concat([uniquenessFilter]) : tdsFilters.slice();
}

function initFlowFilterBuilder(selector, rules) {
    var options = {
        operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat(paramOperators),
        filters: getFlowTdsFilters()
    };
    if (rules && Array.isArray(rules.rules)) {
        options.rules = rules;
    }
    $(selector).queryBuilder(options);
}

function rulesContainUniqueness(node) {
    if (!node || typeof node !== 'object') return false;
    if (node.id === 'uniqueness' || node.field === 'uniqueness') return true;
    return Object.values(node).some(function (value) {
        if (Array.isArray(value)) return value.some(rulesContainUniqueness);
        return value && typeof value === 'object' && rulesContainUniqueness(value);
    });
}

function getUniquenessRuleFlowNames() {
    var names = [];
    document.querySelectorAll('.flow-list-row').forEach(function (row) {
        var index = row.dataset.flowIndex;
        var builder = $('#flow-filters-' + index);
        var rules = {};
        try { rules = builder.queryBuilder('getRules') || {}; } catch (e) {}
        if (!rulesContainUniqueness(rules)) return;
        names.push(row.querySelector('.flow-name-label')?.value || ('Flow ' + (Number(index) + 1)));
    });
    return names;
}

function refreshFlowFilterBuilders() {
    document.querySelectorAll('[id^="flow-filters-"]').forEach(function (element) {
        var builder = $('#' + element.id);
        var rules = {};
        try { rules = builder.queryBuilder('getRules') || {}; } catch (e) {}
        try { builder.queryBuilder('destroy'); } catch (e) {}
        initFlowFilterBuilder('#' + element.id, rules);
    });
}

var paramOperators = [
  {
    type: 'param_in',
    nb_inputs: 2,
    multiple: false,
    apply_to: ['string'],
    label: 'in'
  },
  {
    type: 'param_not_in',
    nb_inputs: 2,
    multiple: false,
    apply_to: ['string'],
    label: 'not in'
  },
  {
    type: 'param_exists',
    nb_inputs: 1,
    multiple: false,
    apply_to: ['string'],
    label: 'exists'
  },
  {
    type: 'param_not_exists',
    nb_inputs: 1,
    multiple: false,
    apply_to: ['string'],
    label: 'not exists'
  },
  {
    type: 'is_unique',
    nb_inputs: 1,
    multiple: false,
    apply_to: ['string'],
    label: 'is unique'
  },
  {
    type: 'is_not_unique',
    nb_inputs: 1,
    multiple: false,
    apply_to: ['string'],
    label: 'is not unique'
  }
];
