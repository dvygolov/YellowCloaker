import { getFlowDist, redistributeWeights, redistributeWeightsAfterDelete } from './weights.js?v=16072602';
import { buildFolderRow, buildRedirectRow, buildFlowSection, buildStepSection, buildStepListRow, renumberSteps, updateStepListInfo, updateAllStepListInfo, updateStepControls } from './templates.js?v=16072603';
import { openFolderPicker } from './folder-picker.js?v=16072603';
import { handleZipUpload } from './zip-upload.js?v=16072602';
import { initializeStepSortable } from './reordering.js?v=16072602';

// ── State ──
var flowCounter = 0;

export function initFlowCounter() {
    flowCounter = document.querySelectorAll('.flow-list-row').length;
}

// ═══════════════════════════════════════════
// CHANGE handlers
// ═══════════════════════════════════════════

// ── Toggle step folders/redirects visibility ──
export function handleStepActionChange(e) {
    var sec = e.target.closest('.step-section');
    if (!sec) return;
    var folders = sec.querySelector('.flow-step-folders');
    var redirects = sec.querySelector('.flow-step-redirects');
    if (folders) folders.style.display = e.target.value === 'folder' ? 'block' : 'none';
    if (redirects) redirects.style.display = e.target.value === 'redirect' ? 'block' : 'none';
    updateStepListInfo(sec.dataset.flowIndex, sec.dataset.stepIndex);
    updateStepControls(sec.dataset.flowIndex);
}

// ── Toggle flow-level distribution (thompson opts + weight cols) ──
export function handleDistChange(e) {
    var fi = e.target.dataset.fi;
    var val = e.target.value;
    var opts = document.getElementById('flow-thompson-opts-' + fi);
    if (opts) opts.style.display = val === 'thompson' ? 'block' : 'none';
    // Show weight cols in flow section and all step sections
    var showW = val === 'weighted';
    var sec = document.getElementById('sec-flow-' + fi);
    if (sec) sec.querySelectorAll('.flow-weight-col').forEach(function (col) { col.style.display = showW ? 'block' : 'none'; });
    document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]').forEach(function (stepSec) {
        stepSec.querySelectorAll('.flow-weight-col').forEach(function (col) { col.style.display = showW ? 'block' : 'none'; });
    });
}

// ═══════════════════════════════════════════
// CLICK handlers
// ═══════════════════════════════════════════

// ── Delete step items (folder or redirect row) ──
export function handleRemoveStepItem(e) {
    var removeBtn = e.target.closest('.flow-remove-step-item');
    if (!removeBtn) return;
    var item = removeBtn.closest('.flow-path-item');
    if (!item) return;
    var landingInput = item.querySelector('.flow-step-folder');
    if (landingInput && !window.confirm('Delete landing "' + landingInput.value + '"?')) return;
    var stepSec = item.closest('.step-section');
    var fi = stepSec ? stepSec.dataset.flowIndex : '';
    var isWeighted = getFlowDist(fi) === 'weighted';
    var removedWeightInp = item.querySelector('input[type="number"]');
    var removedWeight = removedWeightInp ? (parseInt(removedWeightInp.value, 10) || 0) : 0;
    item.remove();
    if (isWeighted && stepSec) {
        var remaining = stepSec.querySelectorAll('.flow-step-weight');
        redistributeWeightsAfterDelete(remaining, removedWeight);
    }
    if (stepSec) updateStepListInfo(stepSec.dataset.flowIndex, stepSec.dataset.stepIndex);
}

// ── Add Existing folder to a step ──
export function handleStepAddExisting(e) {
    var btn = e.target.closest('.flow-step-add-existing');
    if (!btn) return;
    var fi = btn.dataset.fi;
    var stepSec = btn.closest('.step-section');
    if (!stepSec) return;
    var container = stepSec.querySelector('.flow-step-folder-items');
    var showWeight = getFlowDist(fi) === 'weighted';

    btn.disabled = true;
    fetch('listfolders.php').then(function (r) { return r.json(); }).then(function (data) {
        btn.disabled = false;
        if (data.error) { alert(data.result); return; }
        if (!data.folders.length) { alert('No folders found. Upload a ZIP first.'); return; }

        openFolderPicker(data.folders).then(function (choices) {
            if (!choices || !choices.length) return;
            choices.forEach(function (folder) {
                container.appendChild(buildFolderRow(folder, showWeight));
            });
            if (showWeight) {
                redistributeWeights(container.querySelectorAll('.flow-step-weight'));
            }
            updateStepListInfo(fi, stepSec.dataset.stepIndex);
        });
    }).catch(function (err) { btn.disabled = false; alert('Error: ' + err); });
}

// ── Upload ZIP to a step ──
export function handleStepUploadZip(e) {
    var btn = e.target.closest('.flow-step-upload-zip');
    if (!btn) return;
    handleZipUpload(btn);
}

// ── Add redirect to a step ──
export function handleStepAddRedirect(e) {
    var btn = e.target.closest('.flow-step-add-redirect');
    if (!btn) return;
    var fi = btn.dataset.fi;
    var stepSec = btn.closest('.step-section');
    if (!stepSec) return;
    var container = stepSec.querySelector('.flow-step-redirect-items');
    var showWeight = getFlowDist(fi) === 'weighted';
    container.appendChild(buildRedirectRow(showWeight));
    if (showWeight) {
        redistributeWeights(container.querySelectorAll('.flow-step-weight'));
    }
    updateStepListInfo(fi, stepSec.dataset.stepIndex);
}

// ── Update slug when redirect URL changes ──
export function handleRedirectUrlChange(e) {
    if (!e.target.classList.contains('flow-step-redirect')) return;
    var stepSec = e.target.closest('.step-section');
    if (stepSec) updateStepListInfo(stepSec.dataset.flowIndex, stepSec.dataset.stepIndex);
}

// ── Edit folder ──
export function handleEditFolder(e) {
    var btn = e.target.closest('.flow-edit-folder');
    if (!btn) return;
    var item = btn.closest('.flow-path-item');
    if (!item) return;
    var folderInput = item.querySelector('.flow-step-folder');
    if (!folderInput || !folderInput.value.trim()) {
        alert('Please enter a folder name first.');
        return;
    }
    if (typeof window.openFileEditor === 'function') {
        window.openFileEditor(folderInput.value.trim());
    } else {
        alert('File editor not loaded.');
    }
}

// ── Helper: get flow name from flow section ──
function getFlowName(fi) {
    var sec = document.getElementById('sec-flow-' + fi);
    if (!sec) return 'Flow';
    var title = sec.querySelector('.flow-section-title');
    return title ? title.textContent : 'Flow';
}

// ── Add step to a flow ──
export function handleAddStep(e) {
    var btn = e.target.closest('.flow-add-step');
    if (!btn) return;
    var fi = btn.dataset.fi;
    var listContainer = document.getElementById('steps-list-' + fi);
    if (!listContainer) return;
    var si = listContainer.querySelectorAll('.step-list-row').length;
    var flowName = getFlowName(fi);

    // 1. Add step list row
    listContainer.appendChild(buildStepListRow(fi, si));

    // 2. Add sidebar nav item (after last step-nav-item for this flow, or after flow-nav-item)
    var stepNav = document.createElement('li');
    stepNav.className = 'step-nav-item';
    stepNav.dataset.flowIndex = fi;
    stepNav.dataset.stepIndex = si;
    var stepNavLink = document.createElement('a');
    stepNavLink.href = '#sec-step-' + fi + '-' + si;
    stepNavLink.textContent = 'Step ' + (si + 1);
    stepNav.appendChild(stepNavLink);
    var existingStepNavs = document.querySelectorAll('.step-nav-item[data-flow-index="' + fi + '"]');
    if (existingStepNavs.length > 0) {
        existingStepNavs[existingStepNavs.length - 1].insertAdjacentElement('afterend', stepNav);
    } else {
        var flowNav = document.querySelector('.flow-nav-item[data-flow-index="' + fi + '"]');
        if (flowNav) flowNav.insertAdjacentElement('afterend', stepNav);
    }

    // 3. Add step section (before sec-scripts or next flow section)
    var sectionFrag = buildStepSection(fi, si, flowName);
    var nextSection = findInsertPointForStep(fi);
    if (nextSection) {
        nextSection.parentNode.insertBefore(sectionFrag, nextSection);
    } else {
        document.querySelector('.camp-content').appendChild(sectionFrag);
    }

    // 4. Renumber and update toggles
    renumberSteps(fi);
    if (window.refreshCampaignNavTree) window.refreshCampaignNavTree();

    // 5. Navigate to new step
    if (window.showSection) window.showSection('sec-step-' + fi + '-' + si);
}

// ── Find where to insert a new step section for a given flow ──
function findInsertPointForStep(fi) {
    // Find the last step section for this flow
    var stepSecs = document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]');
    if (stepSecs.length > 0) {
        return stepSecs[stepSecs.length - 1].nextElementSibling;
    }
    // Otherwise insert after the flow section itself
    var flowSec = document.getElementById('sec-flow-' + fi);
    if (flowSec) return flowSec.nextElementSibling;
    return document.getElementById('sec-scripts');
}

// ── Remove step from a flow ──
export function handleRemoveStep(e) {
    var btn = e.target.closest('.flow-remove-step');
    if (!btn) return;
    // Could be clicked from step-list-row or from within a step-section
    var listRow = btn.closest('.step-list-row');
    var fi, si;
    if (listRow) {
        fi = listRow.dataset.flowIndex;
        si = listRow.dataset.stepIndex;
    } else {
        return;
    }

    // Remove step section
    var stepSec = document.getElementById('sec-step-' + fi + '-' + si);
    if (stepSec) stepSec.remove();

    // Remove nav item
    var navItem = document.querySelector('.step-nav-item[data-flow-index="' + fi + '"][data-step-index="' + si + '"]');
    if (navItem) navItem.remove();

    // Remove list row
    listRow.remove();

    // Renumber
    renumberSteps(fi);
    if (window.refreshCampaignNavTree) window.refreshCampaignNavTree();
}

// ── Flow list: Delete ──
export function handleDeleteFlow(e) {
    var btn = e.target.closest('.flow-delete');
    if (!btn) return;
    if (!confirm('Delete this flow?')) return;
    var row = btn.closest('.flow-list-row');
    var fi = row.dataset.flowIndex;
    // Remove all step sections for this flow
    document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]').forEach(function (s) { s.remove(); });
    // Remove all step nav items for this flow
    document.querySelectorAll('.step-nav-item[data-flow-index="' + fi + '"]').forEach(function (s) { s.remove(); });
    // Remove flow section
    var sec = document.getElementById('sec-flow-' + fi);
    if (sec) sec.remove();
    // Remove flow sidebar nav item
    var nav = document.querySelector('.flow-nav-item[data-flow-index="' + fi + '"]');
    if (nav) nav.remove();
    // Remove list row
    row.remove();
    if (window.refreshCampaignNavTree) window.refreshCampaignNavTree();
}

// ── Add Flow ──
export function handleAddFlow() {
    var flowName = prompt('Enter flow name (cannot be changed later):');
    if (!flowName || !flowName.trim()) return;
    flowName = flowName.trim();

    // Validate uniqueness
    var existing = document.querySelectorAll('.flow-name-label');
    for (var i = 0; i < existing.length; i++) {
        if (existing[i].value === flowName) {
            alert('Flow name "' + flowName + '" already exists. Choose a different name.');
            return;
        }
    }

    var fi = 'new_' + flowCounter;
    flowCounter++;

    // 1. Add list row
    var row = document.createElement('div');
    row.className = 'flow-list-row';
    row.dataset.flowIndex = fi;
    row.innerHTML =
        '<button type="button" class="reorder-handle flow-drag-handle" title="Drag to reorder"><i class="bi bi-grip-vertical" aria-hidden="true"></i></button>' +
        '<input type="text" class="form-control flow-name-label" readonly />' +
        '<a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn flow-delete" title="Delete"><i class="bi bi-trash"></i></a>';
    row.querySelector('.flow-name-label').value = flowName;
    row.querySelector('.flow-drag-handle').setAttribute('aria-label', 'Reorder ' + flowName + '. Drag or use the arrow keys.');
    document.getElementById('flows-list').appendChild(row);

    // 2. Add sidebar nav item (after last step-nav-item or flow-nav-item, or after sec-flows)
    var flowNavItem = document.createElement('li');
    flowNavItem.className = 'flow-nav-item nav-tree-parent';
    flowNavItem.dataset.flowIndex = fi;
    flowNavItem.dataset.flowKey = flowName;
    var flowToggle = document.createElement('button');
    flowToggle.type = 'button';
    flowToggle.className = 'campaign-nav-toggle';
    flowToggle.dataset.treeToggle = 'steps';
    flowToggle.setAttribute('aria-expanded', 'true');
    flowToggle.setAttribute('aria-label', 'Collapse steps for ' + flowName);
    flowToggle.title = 'Collapse steps';
    flowToggle.innerHTML = '<i class="bi bi-dash-square" aria-hidden="true"></i>';
    var flowNavLink = document.createElement('a');
    flowNavLink.href = '#sec-flow-' + fi;
    flowNavLink.textContent = flowName;
    flowNavItem.appendChild(flowToggle);
    flowNavItem.appendChild(flowNavLink);
    var allStepNavs = document.querySelectorAll('.step-nav-item');
    var allFlowNavs = document.querySelectorAll('.flow-nav-item');
    if (allStepNavs.length > 0) {
        allStepNavs[allStepNavs.length - 1].insertAdjacentElement('afterend', flowNavItem);
    } else if (allFlowNavs.length > 0) {
        allFlowNavs[allFlowNavs.length - 1].insertAdjacentElement('afterend', flowNavItem);
    } else {
        var flowsNavLink = document.querySelector('a[href="#sec-flows"]');
        if (flowsNavLink) flowsNavLink.closest('li').insertAdjacentElement('afterend', flowNavItem);
    }

    // 3. Add flow section from template
    var sectionFrag = buildFlowSection(fi, flowName);

    // Insert section before sec-scripts (or at end of camp-content)
    var scriptsSection = document.getElementById('sec-scripts');
    if (scriptsSection) {
        scriptsSection.parentNode.insertBefore(sectionFrag, scriptsSection);
    } else {
        document.querySelector('.camp-content').appendChild(sectionFrag);
    }

    initializeStepSortable(fi);
    if (window.refreshCampaignNavTree) window.refreshCampaignNavTree();

    // 4. Init QueryBuilder for the new flow's filters
    if (typeof $ !== 'undefined' && typeof $.fn.queryBuilder !== 'undefined') {
        try {
            $('#flow-filters-' + fi).queryBuilder({
                operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat(typeof paramOperators !== 'undefined' ? paramOperators : []),
                filters: typeof getFlowTdsFilters !== 'undefined' ? getFlowTdsFilters() : (typeof tdsFilters !== 'undefined' ? tdsFilters : [])
            });
        } catch (e) { console.warn('Could not init QueryBuilder for flow ' + fi, e); }
    }

    // 5. Navigate to the new flow section
    if (window.showSection) window.showSection('sec-flow-' + fi);
}
