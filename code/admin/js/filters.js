
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
        id: 'bot',
        label: 'Bot',
        type: 'string',
        input: 'radio',
        values: {
            yes: 'Yes',
            no: 'No'
        },
        operators: ['equal']
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

function escapeCapOption(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function buildConversionCapFilter(id, label) {
    return {
        id: id,
        field: id,
        label: label,
        type: 'string',
        operators: ['less', 'less_or_equal', 'equal', 'not_equal', 'greater_or_equal', 'greater'],
        default_operator: 'less',
        input: function (rule, name) {
            var statuses = Array.isArray(window.campaignConversionStatuses) ? window.campaignConversionStatuses : [];
            var options = statuses.map(function (status) {
                return '<option value="' + escapeCapOption(status) + '">' + escapeCapOption(status) + '</option>';
            }).join('');
            return '<div class="conversion-cap-input">'
                + '<select class="form-select conversion-cap-statuses" name="' + name + '_statuses" multiple size="5" aria-label="Conversion statuses">' + options + '</select>'
                + '<input class="form-control conversion-cap-limit" name="' + name + '_limit" type="number" min="0" step="1" value="1" aria-label="Daily conversion limit">'
                + '</div>';
        },
        valueGetter: function (rule) {
            return {
                statuses: rule.$el.find('.conversion-cap-statuses').val() || [],
                limit: Number(rule.$el.find('.conversion-cap-limit').val() || 0)
            };
        },
        valueSetter: function (rule, value) {
            var normalized = value && typeof value === 'object' ? value : {};
            rule.$el.find('.conversion-cap-statuses').val(Array.isArray(normalized.statuses) ? normalized.statuses : []);
            rule.$el.find('.conversion-cap-limit').val(Number.isFinite(Number(normalized.limit)) ? Number(normalized.limit) : 1);
        },
        validation: {
            callback: function (value) {
                return value && Array.isArray(value.statuses) && value.statuses.length > 0
                    && Number.isFinite(Number(value.limit)) && Number(value.limit) >= 0;
            }
        }
    };
}

var conversionCapCampaignFilter = buildConversionCapFilter('conversion_cap_campaign', 'Conversion cap (campaign)');
var conversionCapFlowFilter = buildConversionCapFilter('conversion_cap_flow', 'Conversion cap (flow)');

function getFlowTdsFilters() {
    var enabled = document.getElementById('uniqueness-counting-toggle')?.checked === true;
    var filters = tdsFilters.concat([conversionCapCampaignFilter, conversionCapFlowFilter]);
    return enabled ? filters.concat([uniquenessFilter]) : filters;
}

function initFlowFilterBuilder(selector, rules) {
    var options = {
        operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat(paramOperators),
        filters: getFlowTdsFilters()
    };
    if (rules && Array.isArray(rules.rules) && rules.rules.length > 0) {
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
