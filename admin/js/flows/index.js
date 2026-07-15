import { collectFlowsData } from './collectors.js';
import { openFolderPicker } from './folder-picker.js';
import {
    initFlowCounter,
    handleStepActionChange,
    handleDistChange,
    handleRemoveStepItem,
    handleStepAddExisting,
    handleStepUploadZip,
    handleStepAddRedirect,
    handleEditFolder,
    handleAddStep,
    handleRemoveStep,
    handleDeleteFlow,
    handleAddFlow,
    handleRedirectUrlChange
} from './handlers.js';
import { initializeFlowReordering } from './reordering.js';

// ── Window exports for backward compat with inline scripts ──
window.collectFlowsData = collectFlowsData;
window.openFolderPicker = openFolderPicker;

// ── Event dispatch maps ──
var clickSelectors = [
    { sel: '.flow-remove-step-item', fn: handleRemoveStepItem },
    { sel: '.flow-step-add-existing', fn: handleStepAddExisting },
    { sel: '.flow-step-upload-zip', fn: handleStepUploadZip },
    { sel: '.flow-step-add-redirect', fn: handleStepAddRedirect },
    { sel: '.flow-edit-folder', fn: handleEditFolder },
    { sel: '.flow-add-step', fn: handleAddStep },
    { sel: '.flow-remove-step', fn: handleRemoveStep },
    { sel: '.flow-delete', fn: handleDeleteFlow }
];

var changeSelectors = [
    { sel: '.flow-step-action', fn: handleStepActionChange },
    { sel: '.flow-dist', fn: handleDistChange }
];

// ── Single delegated click listener ──
document.addEventListener('click', function (e) {
    for (var i = 0; i < clickSelectors.length; i++) {
        if (e.target.closest(clickSelectors[i].sel)) {
            clickSelectors[i].fn(e);
            return;
        }
    }
});

// ── Single delegated change listener ──
document.addEventListener('change', function (e) {
    for (var i = 0; i < changeSelectors.length; i++) {
        if (e.target.matches(changeSelectors[i].sel)) {
            changeSelectors[i].fn(e);
            return;
        }
    }
});

// ── Delegated input listener (for redirect URL slug updates) ──
document.addEventListener('input', function (e) {
    if (e.target.classList.contains('flow-step-redirect')) {
        handleRedirectUrlChange(e);
    }
});

// ── Init ──
initFlowCounter();
initializeFlowReordering();

// ── Add Flow button ──
var addFlowBtn = document.getElementById('add-flow-btn');
if (addFlowBtn) addFlowBtn.addEventListener('click', handleAddFlow);
