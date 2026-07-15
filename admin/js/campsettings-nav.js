var sidebar = document.querySelector('.camp-sidebar');
var storageKey = 'yellowtds:campaign-nav:' + (sidebar ? sidebar.dataset.campaignId : 'unknown');

function loadTreeState() {
    try {
        var saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
        return {
            safePagesCollapsed: saved.safePagesCollapsed === true,
            flowsCollapsed: saved.flowsCollapsed === true,
            collapsedFlows: Array.isArray(saved.collapsedFlows) ? saved.collapsedFlows.filter(function (key) { return typeof key === 'string'; }) : []
        };
    } catch (error) {
        return { safePagesCollapsed: false, flowsCollapsed: false, collapsedFlows: [] };
    }
}

var treeState = loadTreeState();

function saveTreeState() {
    try {
        localStorage.setItem(storageKey, JSON.stringify(treeState));
    } catch (error) {
        // Navigation remains functional when browser storage is unavailable.
    }
}

function getFlowKey(flowItem) {
    return flowItem.dataset.flowKey || flowItem.querySelector('a')?.textContent.trim() || flowItem.dataset.flowIndex;
}

function getFlowSteps(flowItem) {
    return Array.from(sidebar.querySelectorAll('.step-nav-item[data-flow-index="' + flowItem.dataset.flowIndex + '"]'));
}

function updateToggle(toggle, expanded, hasChildren, label) {
    if (!toggle) return;
    var parent = toggle.closest('.nav-tree-parent');
    if (parent) parent.classList.toggle('has-tree-children', hasChildren);
    toggle.hidden = !hasChildren;
    toggle.disabled = !hasChildren;
    toggle.setAttribute('aria-expanded', hasChildren && expanded ? 'true' : 'false');
    toggle.setAttribute('aria-label', (expanded ? 'Collapse ' : 'Expand ') + label);
    toggle.title = (expanded ? 'Collapse ' : 'Expand ') + label;
    var icon = toggle.querySelector('i');
    if (icon) icon.className = 'bi ' + (expanded ? 'bi-dash-square' : 'bi-plus-square');
}

function applyTreeState() {
    if (!sidebar) return;
    var domainSpecificEnabled = !!document.querySelector('.white-scope-radio[value="true"]:checked');
    var domainItems = Array.from(sidebar.querySelectorAll('.dws-nav-item'));
    var hasDomainPages = domainSpecificEnabled && domainItems.length > 0;
    var safePagesExpanded = !treeState.safePagesCollapsed;
    updateToggle(sidebar.querySelector('[data-tree-toggle="safe-pages"]'), safePagesExpanded, hasDomainPages, 'domain-specific safe pages');
    domainItems.forEach(function (domainItem) {
        domainItem.hidden = !hasDomainPages || !safePagesExpanded;
    });

    var flowItems = Array.from(sidebar.querySelectorAll('.flow-nav-item'));
    var rootExpanded = !treeState.flowsCollapsed;
    updateToggle(sidebar.querySelector('[data-tree-toggle="flows"]'), rootExpanded, flowItems.length > 0, 'flows');

    flowItems.forEach(function (flowItem) {
        var flowKey = getFlowKey(flowItem);
        var steps = getFlowSteps(flowItem);
        var flowExpanded = !treeState.collapsedFlows.includes(flowKey);
        flowItem.hidden = !rootExpanded;
        updateToggle(flowItem.querySelector('[data-tree-toggle="steps"]'), flowExpanded, steps.length > 0, 'steps for ' + flowKey);
        steps.forEach(function (stepItem) {
            stepItem.hidden = !rootExpanded || !flowExpanded;
        });
    });
}

function expandTargetPath(targetId) {
    if (!sidebar) return;
    if (/^sec-dws-/.test(targetId)) {
        treeState.safePagesCollapsed = false;
        saveTreeState();
        applyTreeState();
        return;
    }
    if (!/^sec-(flow-|step-)/.test(targetId)) return;
    treeState.flowsCollapsed = false;
    var stepMatch = targetId.match(/^sec-step-(.+)-(\d+)$/);
    if (stepMatch) {
        var flowItem = sidebar.querySelector('.flow-nav-item[data-flow-index="' + stepMatch[1] + '"]');
        if (flowItem) {
            var flowKey = getFlowKey(flowItem);
            treeState.collapsedFlows = treeState.collapsedFlows.filter(function (key) { return key !== flowKey; });
        }
    }
    saveTreeState();
    applyTreeState();
}

export function refreshCampaignNavTree() {
    applyTreeState();
}

export function showSection(targetId) {
    expandTargetPath(targetId);
    document.querySelectorAll('.camp-section').forEach(function (s) {
        s.classList.toggle('active', s.id === targetId);
    });
    document.querySelectorAll('.camp-sidebar a').forEach(function (link) {
        link.classList.toggle('active', link.getAttribute('href') === '#' + targetId);
    });
}

// Window export for backward compat
window.showSection = showSection;
window.refreshCampaignNavTree = refreshCampaignNavTree;

sidebar.addEventListener('click', function (e) {
    var toggle = e.target.closest('.campaign-nav-toggle');
    if (toggle) {
        e.preventDefault();
        e.stopPropagation();
        if (toggle.dataset.treeToggle === 'safe-pages') {
            treeState.safePagesCollapsed = !treeState.safePagesCollapsed;
        } else if (toggle.dataset.treeToggle === 'flows') {
            treeState.flowsCollapsed = !treeState.flowsCollapsed;
        } else {
            var flowItem = toggle.closest('.flow-nav-item');
            if (!flowItem) return;
            var flowKey = getFlowKey(flowItem);
            if (treeState.collapsedFlows.includes(flowKey)) {
                treeState.collapsedFlows = treeState.collapsedFlows.filter(function (key) { return key !== flowKey; });
            } else {
                treeState.collapsedFlows.push(flowKey);
            }
        }
        saveTreeState();
        applyTreeState();
        return;
    }

    var link = e.target.closest('a');
    if (!link) return;
    e.preventDefault();
    var targetId = link.getAttribute('href').substring(1);
    showSection(targetId);
    history.replaceState(null, '', '#' + targetId);
});

var hash = location.hash.substring(1);
applyTreeState();
if (hash && document.getElementById(hash)) {
    showSection(hash);
} else {
    var first = document.querySelector('.camp-section');
    if (first) showSection(first.id);
}
