// ── Form submit handler ──
function parseFormKey(key) {
    const segments = [];

    for (const part of key.split('.')) {
        const match = part.match(/^([^\[]+)(.*)$/);
        if (!match) {
            segments.push(part);
            continue;
        }

        segments.push(match[1]);
        const brackets = match[2].matchAll(/\[([^\]]*)\]/g);
        for (const bracket of brackets) {
            segments.push(bracket[1]);
        }
    }

    return segments;
}

function ensureContainer(parent, segment, nextSegment) {
    const shouldBeArray = nextSegment === '' || /^\d+$/.test(nextSegment);

    if (segment === '') {
        const container = shouldBeArray ? [] : {};
        parent.push(container);
        return container;
    }

    if (parent[segment] === undefined || parent[segment] === null || typeof parent[segment] !== 'object') {
        parent[segment] = shouldBeArray ? [] : {};
    }

    return parent[segment];
}

function assignFormValue(target, key, value) {
    const segments = parseFormKey(key);
    let current = target;

    for (let i = 0; i < segments.length; i++) {
        const segment = segments[i];
        const isLast = i === segments.length - 1;
        const nextSegment = segments[i + 1];

        if (isLast) {
            if (segment === '') {
                current.push(value);
            } else if (Array.isArray(current) && /^\d+$/.test(segment)) {
                current[Number(segment)] = value;
            } else if (current[segment] === undefined) {
                current[segment] = value;
            } else if (Array.isArray(current[segment])) {
                current[segment].push(value);
            } else {
                current[segment] = [current[segment], value];
            }
            return;
        }

        if (Array.isArray(current) && /^\d+$/.test(segment)) {
            const index = Number(segment);
            if (current[index] === undefined || current[index] === null || typeof current[index] !== 'object') {
                current[index] = nextSegment === '' || /^\d+$/.test(nextSegment) ? [] : {};
            }
            current = current[index];
            continue;
        }

        current = ensureContainer(current, segment, nextSegment);
    }
}

function compactArrays(value) {
    if (Array.isArray(value)) {
        return value
            .filter((item) => item !== undefined)
            .map((item) => compactArrays(item));
    }

    if (value && typeof value === 'object') {
        for (const key of Object.keys(value)) {
            value[key] = compactArrays(value[key]);
        }
    }

    return value;
}

document.getElementById("campsettings")?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById("save-settings-btn");
    const saveOverlay = document.getElementById("save-settings-overlay");
    const saveToast = document.getElementById("save-settings-toast");
    const showToast = function (message, isError) {
        if (!saveToast) {
            return;
        }
        if (saveToast._hideTimer) {
            clearTimeout(saveToast._hideTimer);
        }
        saveToast.textContent = message;
        saveToast.className = "save-settings-toast is-visible " + (isError ? "is-error" : "is-success");
        saveToast._hideTimer = setTimeout(function () {
            saveToast.className = "save-settings-toast";
        }, 1800);
    };

    if (saveBtn?.dataset.saving === "true") {
        return false;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const campId = urlParams.get('campId');
    if (campId === null) {
        alert("No campaign ID found!");
        return false;
    }

    if (typeof window.normalizeEventThresholdInputs === 'function') {
        window.normalizeEventThresholdInputs();
    }
    if (typeof window.normalizeTransactionIdParametersInput === 'function') {
        window.normalizeTransactionIdParametersInput();
    }

    let rules = $('#filtersbuilder').queryBuilder('getRules');
    let flowsJson = window.collectFlowsData ? window.collectFlowsData() : '[]';
    let whiteData = window.collectWhiteData ? window.collectWhiteData() : { folders: [], loadmode: {} };
    let domainsData = window.collectDomainsData ? window.collectDomainsData() : [];
    let dwsData = window.collectDomainSpecificData ? window.collectDomainSpecificData() : [];
    let scriptRules = window.collectScriptRedirectRules ? window.collectScriptRedirectRules() : { next: [], submit: [] };
    let formData = new FormData(document.getElementById("campsettings"));
    let payload = {};
    for (let [key, value] of formData.entries()) {
        if (!key.startsWith("filtersbuilder") && !key.startsWith("flow-filters-") && !key.startsWith("flow_") && !key.startsWith("dws_") && !key.startsWith("scripts.nextredirect.rules") && !key.startsWith("scripts.submitredirect.rules")) {
            assignFormValue(payload, key, value);
        }
    }
    payload = compactArrays(payload);
    payload.events = payload.events || {};
    payload.events.custom = Array.from(
        document.querySelectorAll('#custom-event-list .custom-event-name')
    ).map((input) => input.value.trim());
    payload.white = payload.white || {};
    payload.black = payload.black || {};
    payload.scripts = payload.scripts || {};
    payload.scripts.nextredirect = payload.scripts.nextredirect || {};
    payload.scripts.submitredirect = payload.scripts.submitredirect || {};
    payload.white.domainfilter = payload.white.domainfilter || {};
    payload.white.filters = rules;
    payload.black.flows = JSON.parse(flowsJson);
    if ((payload.uniqueness?.enabled === false || payload.uniqueness?.enabled === 'false')
        && typeof getUniquenessRuleFlowNames === 'function') {
        const affectedFlows = getUniquenessRuleFlowNames();
        if (affectedFlows.length) {
            showToast('Remove uniqueness rules from: ' + affectedFlows.join(', '), true);
            return false;
        }
    }
    payload.white.folders = whiteData.folders;
    payload.white.loadmode = whiteData.loadmode;
    payload.domains = domainsData;
    payload.white.domainfilter.domains = dwsData;
    payload.scripts.nextredirect.rules = scriptRules.next;
    payload.scripts.submitredirect.rules = scriptRules.submit;
    let settingsBody = JSON.stringify(payload);

    if (saveBtn) {
        saveBtn.dataset.saving = "true";
        saveBtn.disabled = true;
    }
    if (saveOverlay) {
        saveOverlay.classList.add("is-visible");
    }

    try {
        let res = await fetch(`campeditor.php?action=save&campId=${campId}`, {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
            },
            body: settingsBody
        });
        let js = await res.json();
        if (js.error) {
            showToast(js.result || "Error!", true);
        } else {
            showToast("Settings Saved", false);
        }
    } catch (err) {
        showToast("Error!", true);
    } finally {
        if (saveBtn) {
            saveBtn.dataset.saving = "false";
            saveBtn.disabled = false;
        }
        if (saveOverlay) {
            saveOverlay.classList.remove("is-visible");
        }
    }
    return false;
});
