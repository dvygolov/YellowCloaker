import { collectMvt } from './mvt.js';

// ── Collect a single step's data from a step section element ──
function collectStepData(stepSec) {
    var action = 'folder';
    var actionRadio = stepSec.querySelector('input.flow-step-action:checked');
    if (actionRadio) action = actionRadio.value;

    var folders = [];
    var redirectUrls = [];
    var redirectType = 302;

    if (action === 'folder') {
        stepSec.querySelectorAll('.flow-step-folder').forEach(function (inp) {
            if (inp.value.trim()) {
                var folder = inp.value.trim();
                var item = inp.closest('.flow-path-item');
                var w = item.querySelector('.flow-step-weight');
                var modeBtn = item.querySelector('.flow-step-mode');
                folders.push({
                    name: folder,
                    loadtype: modeBtn ? (modeBtn.dataset.mode || 'base') : 'base',
                    weight: parseInt(w ? w.value : 0, 10) || 0,
                    mvt: collectMvt(item)
                });
            }
        });
    } else {
        stepSec.querySelectorAll('.flow-step-redirect').forEach(function (inp) {
            if (inp.value.trim()) {
                var url = inp.value.trim();
                var host = '';
                try { host = new URL(url).hostname.replace(/^www\./, ''); } catch (e) { host = 'redirect'; }
                var w = inp.closest('.flow-path-item').querySelector('.flow-step-weight');
                redirectUrls.push({
                    url: url,
                    label: host,
                    weight: parseInt(w ? w.value : 0, 10) || 0
                });
            }
        });
        var rtSel = stepSec.querySelector('select.flow-step-redirect-type');
        if (rtSel) redirectType = parseInt(rtSel.value);
    }

    return {
        action: action,
        folders: folders,
        redirect: { urls: redirectUrls, type: redirectType }
    };
}

// ── Collect distribution settings from a flow section ──
function collectDistributionData(sec) {
    var flowDist = 'equal';
    var flowDistSel = sec.querySelector('.flow-dist');
    if (flowDistSel) flowDist = flowDistSel.value;

    var optimizeFor = 'Lead';
    var ofRadio = sec.querySelector('.flow-optimize-for:checked');
    if (ofRadio) optimizeFor = ofRadio.value;

    var optimizeMode = 'funnels';
    var omRadio = sec.querySelector('.flow-optimize-mode:checked');
    if (omRadio) optimizeMode = omRadio.value;

    return {
        distribution: flowDist,
        optimize_for: optimizeFor,
        optimize_mode: optimizeMode
    };
}

// ── Collect all flows data as JSON for form submit ──
export function collectFlowsData() {
    var flows = [];
    var rows = document.querySelectorAll('.flow-list-row');
    rows.forEach(function (row) {
        var fi = row.dataset.flowIndex;
        var sec = document.getElementById('sec-flow-' + fi);
        if (!sec) return;

        var name = row.querySelector('.flow-name-label').value || 'Flow';

        // Flow filters from QueryBuilder
        var fb = $('#flow-filters-' + fi);
        var filters = {};
        try { filters = fb.queryBuilder('getRules') || {}; } catch (e) {}

        var dist = collectDistributionData(sec);

        // Collect steps from separate step sections
        var steps = [];
        document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]').forEach(function (stepSec) {
            steps.push(collectStepData(stepSec));
        });

        flows.push({
            name: name,
            filters: filters,
            distribution: dist.distribution,
            optimize_for: dist.optimize_for,
            optimize_mode: dist.optimize_mode,
            steps: steps
        });
    });
    return JSON.stringify(flows);
}
