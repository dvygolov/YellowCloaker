(function () {
    const state = { settings: null, revision: 0, plugins: null };

    function node(selector) { return document.querySelector(selector); }

    function setStatus(message, kind = '') {
        const status = node('#settingsSaveStatus');
        if (!status) return;
        status.textContent = message || '';
        status.className = `settings-save-status ${kind}`.trim();
    }

    function setUpdateStatus(message, kind = '') {
        const status = node('#settingsUpdateStatus');
        if (!status) return;
        status.textContent = message || '';
        status.className = `settings-update-status ${kind}`.trim();
    }

    function setBusy(busy) {
        ['#saveSettings', '#updateCloaker', '#updateGeoBases'].forEach((selector) => {
            const element = node(selector);
            if (element) element.disabled = busy;
        });
    }

    function clearErrors() {
        document.querySelectorAll('#settingsForm .settings-field-error').forEach((element) => element.remove());
        document.querySelectorAll('#settingsForm .is-invalid').forEach((element) => element.classList.remove('is-invalid'));
    }

    function showFieldErrors(fields) {
        clearErrors();
        Object.entries(fields || {}).forEach(([field, message]) => {
            const input = node(`#settingsForm [name="${CSS.escape(field)}"]`);
            if (!input) return;
            input.classList.add('is-invalid');
            const error = document.createElement('small');
            error.className = 'settings-field-error';
            error.textContent = message;
            input.insertAdjacentElement('afterend', error);
        });
    }

    function field(name) { return node(`#settingsForm [name="${name}"]`); }

    function fillFields(settings) {
        ['adminPassword', 'adminDomain', 'adminIp', 'adminPath', 'dbConnection', 'cachingDir', 'landingFolder', 'whiteFolder', 'whiteCurlCache', 'devicesCache', 'currencyCache', 'proxyVpnCache']
            .forEach((name) => { if (field(name)) field(name).value = settings[name] ?? ''; });
        field('adminPassword').value = '';
        field('useUTP').checked = !!settings.useUTP;
        field('debug').checked = !!settings.debug;
    }

    function renderCurrentIp(ip) {
        const button = node('#addCurrentAdminIp');
        const currentIp = String(ip || '').trim();
        button.hidden = currentIp === '';
        button.dataset.ip = currentIp;
        button.textContent = currentIp === '' ? '' : `Add current IP: ${currentIp}`;
    }

    function createPluginRow(type, id, meta, config) {
        const row = document.createElement('div');
        row.className = 'settings-plugin-row';
        row.dataset.pluginType = type;
        row.dataset.pluginId = id;

        const identity = document.createElement('div');
        identity.className = 'settings-plugin-identity';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'settings-plugin-enabled';
        checkbox.checked = !!config.enabled;
        checkbox.setAttribute('role', 'switch');
        checkbox.setAttribute('aria-label', `Enable ${id}`);
        const text = document.createElement('span');
        text.innerHTML = `<strong>${escapeHtml(id)}</strong><small>${escapeHtml(meta.class || meta.file || '')}</small>`;
        const toggle = document.createElement('label');
        toggle.className = 'settings-plugin-toggle';
        const track = document.createElement('span');
        track.className = 'settings-plugin-switch-track';
        track.setAttribute('aria-hidden', 'true');
        const status = document.createElement('span');
        status.className = 'settings-plugin-state';
        toggle.append(checkbox, track, status);
        identity.append(toggle, text);
        row.append(identity);

        let preferred = null;
        if (type === 'currency') {
            preferred = document.createElement('input');
            preferred.type = 'text';
            preferred.className = 'settings-plugin-preferred';
            preferred.placeholder = 'Preferred: RUB, THB';
            preferred.value = (config.preferredCurrencies || []).join(', ');
            row.append(preferred);
        }

        const syncEnabledState = () => {
            const enabled = checkbox.checked;
            row.classList.toggle('is-disabled', !enabled);
            row.dataset.enabled = enabled ? 'true' : 'false';
            status.textContent = enabled ? 'Enabled' : 'Disabled';
            if (preferred) preferred.disabled = !enabled;
        };
        checkbox.addEventListener('change', syncEnabledState);
        syncEnabledState();
        return row;
    }

    function escapeHtml(value) {
        const element = document.createElement('div');
        element.textContent = String(value);
        return element.innerHTML;
    }

    function renderPlugins(settings, catalog) {
        const currencyContainer = node('#currencyPlugins');
        const vpnContainer = node('#vpnPlugins');
        currencyContainer.replaceChildren();
        vpnContainer.replaceChildren();

        Object.entries(catalog.currency || {}).forEach(([id, meta]) => {
            const config = settings.plugins?.currency?.items?.[id] || { enabled: false, preferredCurrencies: [] };
            currencyContainer.append(createPluginRow('currency', id, meta, config));
        });
        Object.entries(catalog.vpn || {}).forEach(([id, meta]) => {
            const config = settings.plugins?.vpn?.items?.[id] || { enabled: false };
            vpnContainer.append(createPluginRow('vpn', id, meta, config));
        });
        node('#vpnMode').value = settings.plugins?.vpn?.mode === 'most' ? 'most' : 'any';

        const errors = node('#pluginErrors');
        const errorItems = catalog.errors || [];
        errors.hidden = errorItems.length === 0;
        errors.innerHTML = errorItems.length
            ? `<strong>Invalid plugins</strong>${errorItems.map((item) => `<div>${escapeHtml(item.file)}: ${escapeHtml(item.error)}</div>`).join('')}`
            : '';
    }

    function collectPluginItems(type) {
        const items = {};
        document.querySelectorAll(`.settings-plugin-row[data-plugin-type="${type}"]`).forEach((row) => {
            const id = row.dataset.pluginId;
            const item = { enabled: !!row.querySelector('.settings-plugin-enabled')?.checked };
            if (type === 'currency') {
                item.preferredCurrencies = (row.querySelector('.settings-plugin-preferred')?.value || '')
                    .split(',')
                    .map((value) => value.trim().toUpperCase())
                    .filter(Boolean);
            }
            items[id] = item;
        });
        return items;
    }

    function collectSettings() {
        const settings = { ...state.settings };
        ['adminPassword', 'adminDomain', 'adminIp', 'adminPath', 'dbConnection', 'cachingDir', 'landingFolder', 'whiteFolder', 'whiteCurlCache', 'devicesCache', 'currencyCache', 'proxyVpnCache']
            .forEach((name) => { settings[name] = field(name).value.trim(); });
        settings.useUTP = field('useUTP').checked;
        settings.debug = field('debug').checked;
        settings.plugins = {
            currency: { items: collectPluginItems('currency') },
            vpn: { mode: node('#vpnMode').value, items: collectPluginItems('vpn') },
        };
        return settings;
    }

    async function loadSettings() {
        node('#settingsLoading').hidden = false;
        node('#settingsForm').hidden = true;
        setStatus('');
        clearErrors();
        const response = await fetch('settingseditor.php', { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Failed to load settings');
        state.settings = result.settings;
        state.revision = result.revision;
        state.plugins = result.plugins;
        fillFields(result.settings);
        renderCurrentIp(result.currentIp);
        renderPlugins(result.settings, result.plugins);
        node('#cloakerVersion').textContent = `Installed version: ${result.updates?.currentVersion || 'unknown'}`;
        node('#geoBasesVersion').textContent = `Installed bases: ${result.updates?.geoBases || 'unknown'}`;
        node('#settingsLoading').hidden = true;
        node('#settingsForm').hidden = false;
    }

    async function openSettings(event) {
        event?.preventDefault();
        $('#settingsModal').modal({
            modalClass: 'ywbmodal settings-modal',
            fadeDuration: 200,
            showClose: false,
        });
        try {
            await loadSettings();
        } catch (error) {
            node('#settingsLoading').textContent = error.message;
            setStatus(error.message, 'error');
        }
    }

    async function saveSettings() {
        setBusy(true);
        setStatus('Saving…');
        clearErrors();
        try {
            const response = await fetch('settingseditor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ settings: collectSettings(), revision: state.revision }),
            });
            const result = await response.json();
            if (!response.ok) {
                if (result.fields) showFieldErrors(result.fields);
                throw new Error(result.error || 'Failed to save settings');
            }
            state.settings = result.settings;
            state.revision = result.revision;
            setStatus('Settings saved', 'success');
            if (result.redirect) {
                window.location.href = new URL(result.redirect, window.location.href).href;
                return;
            }
            fillFields(result.settings);
        } catch (error) {
            setStatus(error.message, 'error');
        } finally {
            setBusy(false);
        }
    }

    function startUpdateOverlay(message) {
        const overlay = node('#updateOverlay');
        const typing = node('#typing-text');
        overlay.style.display = 'flex';
        const rainCleanup = setupMatrixRain();
        const typingCleanup = typeText(message, typing);
        return () => {
            rainCleanup?.();
            typingCleanup?.();
            overlay.style.display = 'none';
        };
    }

    async function updateGeoBases() {
        setBusy(true);
        setUpdateStatus('Updating GeoBases…');
        const cleanup = startUpdateOverlay('GEOBASES UPDATING...');
        try {
            const response = await fetch('../bases/update.php', { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || result.error) throw new Error(result.result || 'GeoBases update failed');
            setUpdateStatus(result.result.trim(), 'success');
            node('#geoBasesVersion').textContent = 'Installed bases: just updated';
        } catch (error) {
            setUpdateStatus(error.message, 'error');
        } finally {
            cleanup();
            setBusy(false);
        }
    }

    async function updateCloaker() {
        setBusy(true);
        setUpdateStatus('Checking for updates…');
        const cleanup = startUpdateOverlay('SYSTEM UPDATING...');
        try {
            const check = await sendAutoupdateRequest('check');
            if (!check.success) throw new Error(check.message || 'Update check failed');
            if (!check.hasUpdate) {
                setUpdateStatus('The installed version is up to date.', 'success');
                return;
            }
            if (!window.confirm(`Update to version ${check.version}?`)) {
                setUpdateStatus(`Version ${check.version} is available.`);
                return;
            }
            setUpdateStatus(`Installing ${check.version}…`);
            const result = await sendAutoupdateRequest('update');
            if (!result.success) throw new Error(result.message || 'Update failed');
            setUpdateStatus(result.message, 'success');
            window.location.reload();
        } catch (error) {
            setUpdateStatus(error.message, 'error');
        } finally {
            cleanup();
            setBusy(false);
        }
    }

    function activateTab(name) {
        document.querySelectorAll('.settings-tab-button').forEach((button) => button.classList.toggle('active', button.dataset.settingsTab === name));
        document.querySelectorAll('.settings-tab-panel').forEach((panel) => panel.classList.toggle('active', panel.dataset.settingsPanel === name));
    }

    document.addEventListener('DOMContentLoaded', () => {
        node('#openSettings')?.addEventListener('click', openSettings);
        node('#saveSettings')?.addEventListener('click', saveSettings);
        node('#updateGeoBases')?.addEventListener('click', updateGeoBases);
        node('#updateCloaker')?.addEventListener('click', updateCloaker);
        node('#addCurrentAdminIp')?.addEventListener('click', () => {
            const button = node('#addCurrentAdminIp');
            if (!button.dataset.ip) return;
            field('adminIp').value = button.dataset.ip;
            field('adminIp').classList.remove('is-invalid');
            field('adminIp').focus();
        });
        node('#closeSettings')?.addEventListener('click', () => $.modal.close());
        node('#cancelSettings')?.addEventListener('click', () => $.modal.close());
        document.querySelectorAll('.settings-tab-button').forEach((button) => button.addEventListener('click', () => activateTab(button.dataset.settingsTab)));
    });
})();
