// ── Clone a <template> by id, return DocumentFragment ──
export function cloneTemplate(id) {
    var tpl = document.getElementById(id);
    if (!tpl) throw new Error('Template not found: ' + id);
    return tpl.content.cloneNode(true);
}

// ── Fill placeholders in a cloned fragment ──
// Replaces data-fi, name attributes containing __FI__, text content, ids, etc.
function fillPlaceholders(fragment, fi, extraReplacements) {
    // Replace __FI__ in all attributes and text
    var walker = document.createTreeWalker(fragment, NodeFilter.SHOW_ELEMENT);
    while (walker.nextNode()) {
        var el = walker.currentNode;
        for (var i = 0; i < el.attributes.length; i++) {
            var attr = el.attributes[i];
            if (attr.value.indexOf('__FI__') !== -1) {
                attr.value = attr.value.replace(/__FI__/g, fi);
            }
        }
    }
    // Extra replacements (key → value in attributes)
    if (extraReplacements) {
        Object.keys(extraReplacements).forEach(function (placeholder) {
            var val = extraReplacements[placeholder];
            var allEls = fragment.querySelectorAll('*');
            allEls.forEach(function (el) {
                for (var i = 0; i < el.attributes.length; i++) {
                    var attr = el.attributes[i];
                    if (attr.value.indexOf(placeholder) !== -1) {
                        attr.value = attr.value.replace(new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), val);
                    }
                }
                // Also replace in text content for labels/titles
                if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
                    var text = el.childNodes[0].textContent;
                    if (text.indexOf(placeholder) !== -1) {
                        el.childNodes[0].textContent = text.replace(new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), val);
                    }
                }
            });
        });
    }
    return fragment;
}

// ── Build a folder row from template (step-based) ──
export function buildFolderRow(folderName, showWeight) {
    var frag = cloneTemplate('tpl-folder-row');

    var labelEl = frag.querySelector('[data-role="folder-label"]');
    if (labelEl) labelEl.textContent = 'Folder:';

    var folderInput = frag.querySelector('[data-role="folder-input"]');
    if (folderInput) {
        folderInput.value = folderName;
        folderInput.className = 'form-control folder-value-input flow-step-folder';
        folderInput.tabIndex = -1;
    }

    var weightCol = frag.querySelector('.flow-weight-col');
    if (weightCol) weightCol.style.display = showWeight ? 'block' : 'none';

    var weightInput = frag.querySelector('[data-role="weight-input"]');
    if (weightInput) weightInput.className = 'form-control flow-step-weight';

    var removeBtn = frag.querySelector('[data-role="remove-btn"]');
    if (removeBtn) removeBtn.className = 'btn btn-danger flow-remove-step-item';

    var modeBtn = frag.querySelector('[data-role="mode-btn"]');
    if (modeBtn) modeBtn.classList.add('flow-step-mode');

    return frag;
}

// ── Build a redirect row from template ──
export function buildRedirectRow(showWeight) {
    var frag = cloneTemplate('tpl-redirect-row');

    var weightCol = frag.querySelector('.flow-weight-col');
    if (weightCol) weightCol.style.display = showWeight ? 'block' : 'none';

    return frag;
}

// ── Build a step section from template ──
export function buildStepSection(fi, si, flowName) {
    var frag = cloneTemplate('tpl-step-section');
    var stepNum = parseInt(si, 10) + 1;
    fillPlaceholders(frag, fi, {
        '__SI__': String(si),
        '__FLOWNAME__': flowName,
        '__STEPNUM__': String(stepNum)
    });
    // Set the title text (fillPlaceholders only handles attributes + single text nodes)
    var title = frag.querySelector('.flow-section-title');
    if (title) title.innerHTML = flowName + ' &rsaquo; Step ' + stepNum;
    return frag;
}

// ── Build a step list row (for the steps list inside flow section) ──
export function buildStepListRow(fi, si) {
    var div = document.createElement('div');
    div.className = 'step-list-row';
    div.dataset.flowIndex = fi;
    div.dataset.stepIndex = si;
    div.innerHTML =
        '<button type="button" class="reorder-handle step-drag-handle" title="Drag to reorder" aria-label="Reorder Step ' + (parseInt(si, 10) + 1) + '. Drag or use the arrow keys."><i class="bi bi-grip-vertical" aria-hidden="true"></i></button>' +
        '<span class="step-list-label">Step ' + (parseInt(si, 10) + 1) + '</span>' +
        '<span class="step-list-info">empty</span>' +
        '<a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn flow-remove-step" title="Delete"><i class="bi bi-trash"></i></a>';
    return div;
}

// ── Renumber all steps for a flow: updates list rows, nav items, step sections ──
export function renumberSteps(fi) {
    // 1. Renumber step-list-rows
    var listContainer = document.getElementById('steps-list-' + fi);
    if (listContainer) {
        var rows = listContainer.querySelectorAll('.step-list-row');
        rows.forEach(function (row, idx) {
            row.dataset.stepIndex = idx;
            var label = row.querySelector('.step-list-label');
            if (label) label.textContent = 'Step ' + (idx + 1);
            var handle = row.querySelector('.step-drag-handle');
            if (handle) handle.setAttribute('aria-label', 'Reorder Step ' + (idx + 1) + '. Drag or use the arrow keys.');
        });
    }

    // 2. Renumber sidebar nav items
    var navItems = document.querySelectorAll('.step-nav-item[data-flow-index="' + fi + '"]');
    navItems.forEach(function (item, idx) {
        item.dataset.stepIndex = idx;
        var a = item.querySelector('a');
        if (a) {
            a.setAttribute('href', '#sec-step-' + fi + '-' + idx);
            a.textContent = 'Step ' + (idx + 1);
        }
    });

    // 3. Renumber step sections
    var sections = document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]');
    // Get flow name from the flow section
    var flowSec = document.getElementById('sec-flow-' + fi);
    var flowName = '';
    if (flowSec) {
        var titleEl = flowSec.querySelector('.flow-section-title');
        if (titleEl) flowName = titleEl.textContent;
    }

    sections.forEach(function (sec, idx) {
        sec.id = 'sec-step-' + fi + '-' + idx;
        sec.dataset.stepIndex = idx;
        var title = sec.querySelector('.flow-section-title');
        if (title) title.innerHTML = flowName + ' &rsaquo; Step ' + (idx + 1);
        // Update radio name attributes
        sec.querySelectorAll('.flow-step-action').forEach(function (radio) {
            radio.name = 'flow_' + fi + '_step_' + idx + '_action';
            radio.dataset.si = idx;
        });
        // Update data-si on buttons
        sec.querySelectorAll('[data-si]').forEach(function (el) {
            el.dataset.si = idx;
        });
    });

    // 4. Update action radio disabled state (only last step can use redirect)
    updateLastStepToggle(fi);

    // 5. Update step controls (Add Step button, drag handle state)
    updateStepControls(fi);

    // 6. Update optimize mode visibility (show only if 2+ steps)
    var stepCount = sections.length;
    var wrap = document.getElementById('flow-optimize-mode-wrap-' + fi);
    if (wrap) wrap.style.display = stepCount > 1 ? 'block' : 'none';
}

// ── Enable folder/redirect toggle only on the last step ──
export function updateLastStepToggle(fi) {
    var sections = document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]');
    var lastIdx = sections.length - 1;
    sections.forEach(function (sec, idx) {
        var isLast = (idx === lastIdx);
        sec.querySelectorAll('.flow-step-action').forEach(function (radio) {
            radio.disabled = !isLast;
        });
        // If not last and currently set to redirect, force back to folder
        if (!isLast) {
            var checkedRadio = sec.querySelector('.flow-step-action:checked');
            if (checkedRadio && checkedRadio.value === 'redirect') {
                var folderRadio = sec.querySelector('.flow-step-action[value="folder"]');
                if (folderRadio) folderRadio.checked = true;
                var folders = sec.querySelector('.flow-step-folders');
                var redirects = sec.querySelector('.flow-step-redirects');
                if (folders) folders.style.display = 'block';
                if (redirects) redirects.style.display = 'none';
            }
        }
        // Show/hide hint
        var hint = sec.querySelector('.step-action-hint');
        if (!hint) {
            var actionGroup = sec.querySelector('.flow-step-action')?.closest('.form-group-inner');
            if (actionGroup && !isLast) {
                var p = document.createElement('p');
                p.className = 'step-action-hint';
                p.style.cssText = 'font-size:12px;margin-top:6px;';
                p.textContent = 'Only the last step can use redirects.';
                actionGroup.appendChild(p);
            }
        } else {
            hint.style.display = isLast ? 'none' : 'block';
        }
    });
}

// ── Update step-list-info slug from step section content ──
export function updateStepListInfo(fi, si) {
    var stepSec = document.getElementById('sec-step-' + fi + '-' + si);
    var listRow = document.querySelector('.step-list-row[data-flow-index="' + fi + '"][data-step-index="' + si + '"]');
    if (!stepSec || !listRow) return;
    var infoEl = listRow.querySelector('.step-list-info');
    if (!infoEl) return;

    var checkedRadio = stepSec.querySelector('.flow-step-action:checked');
    var action = checkedRadio ? checkedRadio.value : 'folder';

    if (action === 'redirect') {
        var hosts = [];
        stepSec.querySelectorAll('.flow-step-redirect').forEach(function (inp) {
            var url = inp.value.trim();
            if (url) {
                try { var h = new URL(url).hostname.replace(/^www\./, ''); hosts.push(h); } catch (e) { hosts.push('redirect'); }
            }
        });
        infoEl.textContent = hosts.length ? hosts.join(', ') : 'redirect';
    } else {
        var folders = [];
        stepSec.querySelectorAll('.flow-step-folder').forEach(function (inp) {
            if (inp.value.trim()) folders.push(inp.value.trim());
        });
        infoEl.textContent = folders.length ? folders.join(', ') : 'empty';
    }
}

// ── Update all step list slugs for a flow ──
export function updateAllStepListInfo(fi) {
    var sections = document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]');
    sections.forEach(function (sec, idx) {
        updateStepListInfo(fi, idx);
    });
}

// ── Update step controls: disable Add Step if any step is redirect, lock redirect in last position ──
export function updateStepControls(fi) {
    var sections = document.querySelectorAll('.step-section[data-flow-index="' + fi + '"]');
    var hasRedirect = false;
    sections.forEach(function (sec) {
        var checked = sec.querySelector('.flow-step-action:checked');
        if (checked && checked.value === 'redirect') hasRedirect = true;
    });

    // Disable/enable Add Step button
    var addBtn = document.querySelector('.flow-add-step[data-fi="' + fi + '"]');
    if (addBtn) {
        if (hasRedirect) {
            addBtn.classList.add('disabled');
            addBtn.setAttribute('aria-disabled', 'true');
            addBtn.style.pointerEvents = 'none';
            addBtn.style.opacity = '0.5';
        } else {
            addBtn.classList.remove('disabled');
            addBtn.removeAttribute('aria-disabled');
            addBtn.style.pointerEvents = '';
            addBtn.style.opacity = '';
        }
    }

    // A redirect is terminal and must stay last, so its drag handle is disabled.
    var listContainer = document.getElementById('steps-list-' + fi);
    if (!listContainer) return;
    listContainer.querySelectorAll('.step-list-row').forEach(function (row) {
        var si = row.dataset.stepIndex;
        var stepSec = document.getElementById('sec-step-' + fi + '-' + si);
        var isRedirect = false;
        if (stepSec) {
            var checked = stepSec.querySelector('.flow-step-action:checked');
            isRedirect = checked && checked.value === 'redirect';
        }
        var handle = row.querySelector('.step-drag-handle');
        if (handle) {
            handle.classList.toggle('is-disabled', isRedirect);
            handle.title = isRedirect ? 'Redirect must remain the last step' : 'Drag to reorder';
            if (isRedirect) {
                handle.setAttribute('aria-disabled', 'true');
            } else {
                handle.removeAttribute('aria-disabled');
            }
        }
    });
}

// ── Build a full flow section from template ──
export function buildFlowSection(fi, flowName) {
    var frag = cloneTemplate('tpl-flow-section');
    fillPlaceholders(frag, fi, { '__FLOWNAME__': flowName });

    // Set the title text
    var title = frag.querySelector('.flow-section-title');
    if (title) title.textContent = flowName;

    return frag;
}
