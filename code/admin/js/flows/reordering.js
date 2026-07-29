import { renumberSteps } from './templates.js?v=16072603';

var flowSortable = null;
var stepSortables = new Map();
var keyboardListenerBound = false;

function getPointerOptions() {
    return {
        forceFallback: true,
        fallbackOnBody: true,
        fallbackTolerance: 3,
        fallbackClass: 'flow-order-fallback'
    };
}

function getStepSection(row) {
    return document.getElementById('sec-step-' + row.dataset.flowIndex + '-' + row.dataset.stepIndex);
}

function isRedirectRow(row) {
    var section = getStepSection(row);
    var action = section ? section.querySelector('.flow-step-action:checked') : null;
    return !!action && action.value === 'redirect';
}

function getStepBoundary(flowSection, fi) {
    var boundary = flowSection ? flowSection.nextElementSibling : null;
    while (boundary && boundary.classList.contains('step-section') && boundary.dataset.flowIndex === String(fi)) {
        boundary = boundary.nextElementSibling;
    }
    return boundary;
}

export function syncStepOrder(fi) {
    var list = document.getElementById('steps-list-' + fi);
    var flowSection = document.getElementById('sec-flow-' + fi);
    if (!list || !flowSection) return;

    var rows = Array.from(list.querySelectorAll('.step-list-row'));
    var boundary = getStepBoundary(flowSection, fi);
    var parent = flowSection.parentNode;
    rows.forEach(function (row) {
        var section = getStepSection(row);
        if (section) parent.insertBefore(section, boundary);
    });

    var flowNav = document.querySelector('.flow-nav-item[data-flow-index="' + fi + '"]');
    if (flowNav) {
        var navBoundary = flowNav.nextElementSibling;
        while (navBoundary && navBoundary.classList.contains('step-nav-item') && navBoundary.dataset.flowIndex === String(fi)) {
            navBoundary = navBoundary.nextElementSibling;
        }
        rows.forEach(function (row) {
            var nav = document.querySelector('.step-nav-item[data-flow-index="' + fi + '"][data-step-index="' + row.dataset.stepIndex + '"]');
            if (nav) flowNav.parentNode.insertBefore(nav, navBoundary);
        });
    }

    renumberSteps(fi);
}

export function syncFlowOrder() {
    var list = document.getElementById('flows-list');
    if (!list) return;

    var rows = Array.from(list.querySelectorAll('.flow-list-row'));
    var scriptsSection = document.getElementById('sec-scripts');
    if (scriptsSection) {
        rows.forEach(function (row) {
            var fi = row.dataset.flowIndex;
            var flowSection = document.getElementById('sec-flow-' + fi);
            if (flowSection) scriptsSection.parentNode.insertBefore(flowSection, scriptsSection);
            document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]').forEach(function (section) {
                scriptsSection.parentNode.insertBefore(section, scriptsSection);
            });
        });
    }

    var scriptsNav = document.querySelector('a[href="#sec-scripts"]');
    var navBoundary = scriptsNav ? scriptsNav.closest('li') : null;
    if (navBoundary) {
        rows.forEach(function (row) {
            var fi = row.dataset.flowIndex;
            var flowNav = document.querySelector('.flow-nav-item[data-flow-index="' + fi + '"]');
            if (flowNav) navBoundary.parentNode.insertBefore(flowNav, navBoundary);
            document.querySelectorAll('.step-nav-item[data-flow-index="' + fi + '"]').forEach(function (stepNav) {
                navBoundary.parentNode.insertBefore(stepNav, navBoundary);
            });
        });
    }
}

function canMoveStep(evt) {
    if (isRedirectRow(evt.dragged)) return false;
    if (isRedirectRow(evt.related) && evt.willInsertAfter) return false;
    return true;
}

export function initializeStepSortable(fi) {
    var list = document.getElementById('steps-list-' + fi);
    if (!list || typeof Sortable === 'undefined' || stepSortables.has(String(fi))) return;

    var sortable = new Sortable(list, Object.assign(getPointerOptions(), {
        animation: 160,
        draggable: '.step-list-row',
        handle: '.step-drag-handle:not(.is-disabled)',
        ghostClass: 'flow-order-ghost',
        chosenClass: 'flow-order-chosen',
        dragClass: 'flow-order-drag',
        onMove: canMoveStep,
        onStart: function (evt) {
            evt.item.querySelector('.step-drag-handle')?.setAttribute('aria-grabbed', 'true');
        },
        onEnd: function (evt) {
            evt.item.querySelector('.step-drag-handle')?.removeAttribute('aria-grabbed');
            if (evt.oldIndex !== evt.newIndex) syncStepOrder(fi);
        }
    }));
    stepSortables.set(String(fi), sortable);
}

function moveRowByKeyboard(handle, direction) {
    var stepRow = handle.closest('.step-list-row');
    if (stepRow) {
        if (handle.classList.contains('is-disabled')) return;
        var targetStep = direction < 0 ? stepRow.previousElementSibling : stepRow.nextElementSibling;
        if (!targetStep || !targetStep.classList.contains('step-list-row')) return;
        if (direction > 0 && isRedirectRow(targetStep)) return;
        if (direction < 0) {
            stepRow.parentNode.insertBefore(stepRow, targetStep);
        } else {
            stepRow.parentNode.insertBefore(targetStep, stepRow);
        }
        syncStepOrder(stepRow.dataset.flowIndex);
        handle.focus();
        return;
    }

    var flowRow = handle.closest('.flow-list-row');
    if (!flowRow) return;
    var targetFlow = direction < 0 ? flowRow.previousElementSibling : flowRow.nextElementSibling;
    if (!targetFlow || !targetFlow.classList.contains('flow-list-row')) return;
    if (direction < 0) {
        flowRow.parentNode.insertBefore(flowRow, targetFlow);
    } else {
        flowRow.parentNode.insertBefore(targetFlow, flowRow);
    }
    syncFlowOrder();
    handle.focus();
}

function handleReorderKeydown(event) {
    var handle = event.target.closest('.reorder-handle');
    if (!handle || (event.key !== 'ArrowUp' && event.key !== 'ArrowDown')) return;
    event.preventDefault();
    moveRowByKeyboard(handle, event.key === 'ArrowUp' ? -1 : 1);
}

export function initializeFlowReordering() {
    var list = document.getElementById('flows-list');
    if (list && typeof Sortable !== 'undefined' && !flowSortable) {
        flowSortable = new Sortable(list, Object.assign(getPointerOptions(), {
            animation: 160,
            draggable: '.flow-list-row',
            handle: '.flow-drag-handle',
            ghostClass: 'flow-order-ghost',
            chosenClass: 'flow-order-chosen',
            dragClass: 'flow-order-drag',
            onStart: function (evt) {
                evt.item.querySelector('.flow-drag-handle')?.setAttribute('aria-grabbed', 'true');
            },
            onEnd: function (evt) {
                evt.item.querySelector('.flow-drag-handle')?.removeAttribute('aria-grabbed');
                if (evt.oldIndex !== evt.newIndex) syncFlowOrder();
            }
        }));
    }

    document.querySelectorAll('.steps-list').forEach(function (stepList) {
        initializeStepSortable(stepList.id.replace('steps-list-', ''));
    });
    if (!keyboardListenerBound) {
        document.addEventListener('keydown', handleReorderKeydown);
        keyboardListenerBound = true;
    }
}
