function syncBooleanSwitch(toggle) {
    const valueTargetId = toggle.dataset.valueTarget;
    const hiddenInput = valueTargetId ? document.getElementById(valueTargetId) : null;
    if (hiddenInput) {
        hiddenInput.value = toggle.checked ? 'true' : 'false';
    }

    const controlsId = toggle.dataset.controls;
    const controlledElement = controlsId ? document.getElementById(controlsId) : null;
    if (controlledElement) {
        controlledElement.hidden = !toggle.checked;
        toggle.setAttribute('aria-expanded', toggle.checked ? 'true' : 'false');
    }
}

document.querySelectorAll('.campaign-switch-input').forEach((toggle) => {
    syncBooleanSwitch(toggle);
    toggle.addEventListener('change', () => syncBooleanSwitch(toggle));
});

const uniquenessMethod = document.getElementById('uniqueness-method');
const uniquenessGetRow = document.getElementById('uniqueness-get-row');

function syncUniquenessMethodVisibility() {
    if (!uniquenessMethod || !uniquenessGetRow) {
        return;
    }

    uniquenessGetRow.hidden = uniquenessMethod.value !== 'get';
}

uniquenessMethod?.addEventListener('change', syncUniquenessMethodVisibility);
syncUniquenessMethodVisibility();
