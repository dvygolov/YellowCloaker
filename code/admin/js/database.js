(() => {
    'use strict';

    const configNode = document.getElementById('databaseMaintenanceConfig');
    if (!configNode) return;

    const config = JSON.parse(configNode.textContent);
    const state = {
        summary: null,
        previewToken: null,
        previewSelection: null,
        job: null,
        running: false,
        stopRequested: false,
        pollTimer: null,
    };

    const elements = {
        alert: document.getElementById('databaseAlert'),
        rows: document.getElementById('campaignDatabaseRows'),
        selectAll: document.getElementById('selectAllCampaigns'),
        trafficback: null,
        cleanup: document.getElementById('cleanupDatabase'),
        compact: document.getElementById('compactDatabase'),
        progress: document.getElementById('maintenanceProgress'),
        progressTrack: document.querySelector('.maintenance-progress-track'),
        progressBar: document.getElementById('maintenanceProgressBar'),
        status: document.getElementById('maintenanceStatus'),
        message: document.getElementById('maintenanceMessage'),
        resume: document.getElementById('resumeMaintenance'),
        retry: document.getElementById('retryCompaction'),
        cancel: document.getElementById('cancelMaintenance'),
        result: document.getElementById('maintenanceResult'),
        modal: document.getElementById('cleanupConfirmModal'),
        previewSummary: document.getElementById('cleanupPreviewSummary'),
        trafficWarning: document.getElementById('activeTrafficWarning'),
        confirmation: document.getElementById('deleteConfirmation'),
        start: document.getElementById('startCleanup'),
        cancelConfirm: document.getElementById('cancelCleanupConfirm'),
    };

    function formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes < 0) return '—';
        if (bytes === 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / Math.pow(1024, index);
        return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: index === 0 ? 0 : 1 }).format(value)} ${units[index]}`;
    }

    function formatNumber(value) {
        return new Intl.NumberFormat().format(Number(value) || 0);
    }

    function formatDate(timestamp) {
        if (!Number.isFinite(timestamp) || timestamp <= 0) return null;
        return new Intl.DateTimeFormat(undefined, {
            year: 'numeric', month: 'short', day: '2-digit', timeZone: config.timezone,
        }).format(new Date(timestamp * 1000));
    }

    function formatStoredDates(oldest, newest) {
        const first = formatDate(oldest);
        const last = formatDate(newest);
        if (!first || !last) return 'No records';
        return first === last ? first : `${first} — ${last}`;
    }

    function wait(milliseconds) {
        return new Promise((resolve) => setTimeout(resolve, milliseconds));
    }

    async function request(action, payload = null, method = 'POST') {
        const options = {
            method,
            headers: { 'Accept': 'application/json' },
        };
        if (method === 'POST') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify({ ...payload, csrf: config.csrf });
        }
        const response = await fetch(`${config.api}?action=${encodeURIComponent(action)}`, options);
        let data;
        try {
            data = await response.json();
        } catch (error) {
            data = { error: `Invalid server response (HTTP ${response.status})` };
        }
        if (!response.ok || data.error) {
            const error = new Error(data.error || `HTTP ${response.status}`);
            error.retryable = data.retryable === true;
            throw error;
        }
        return data;
    }

    function showAlert(message, error = false) {
        elements.alert.textContent = message;
        elements.alert.classList.toggle('is-error', error);
        elements.alert.hidden = false;
    }

    function hideAlert() {
        elements.alert.hidden = true;
        elements.alert.textContent = '';
        elements.alert.classList.remove('is-error');
    }

    function selectedCampaignIds() {
        return Array.from(document.querySelectorAll('.database-campaign-select:checked'))
            .map((checkbox) => Number(checkbox.value));
    }

    function hasRunningJob() {
        return ['deleting', 'ready_to_compact', 'compaction_required', 'compacting', 'owned_by_another_session'].includes(state.job?.status);
    }

    function syncSelectionControls() {
        const selectable = Array.from(document.querySelectorAll('.database-record-select:not(:disabled)'));
        const selected = selectable.filter((checkbox) => checkbox.checked);
        elements.selectAll.checked = selectable.length > 0 && selected.length === selectable.length;
        elements.selectAll.indeterminate = selected.length > 0 && selected.length < selectable.length;
        elements.cleanup.disabled = selected.length < 1 || hasRunningJob() || state.running;
        elements.compact.disabled = hasRunningJob() || state.running;
    }

    function renderRows(campaigns, trafficback) {
        elements.rows.replaceChildren();
        if (!campaigns.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 7;
            cell.className = 'database-empty';
            cell.textContent = 'No campaigns found.';
            row.append(cell);
            elements.rows.append(row);
        }

        campaigns.forEach((campaign) => {
            const row = document.createElement('tr');
            const checkCell = document.createElement('td');
            checkCell.className = 'database-check';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'database-record-select database-campaign-select';
            checkbox.value = String(campaign.id);
            checkbox.disabled = Number(campaign.recordsRange) < 1;
            checkbox.setAttribute('aria-label', `Select ${campaign.name}`);
            checkbox.addEventListener('change', syncSelectionControls);
            checkCell.append(checkbox);

            const nameCell = document.createElement('td');
            nameCell.className = 'database-campaign-name';
            nameCell.textContent = campaign.name;

            const clicksCell = document.createElement('td');
            clicksCell.textContent = formatNumber(campaign.clicksRange);
            const blockedCell = document.createElement('td');
            blockedCell.textContent = formatNumber(campaign.blockedRange);
            const rangeCell = document.createElement('td');
            rangeCell.textContent = formatNumber(campaign.recordsRange);
            const totalCell = document.createElement('td');
            totalCell.textContent = formatNumber(campaign.recordsTotal);
            const datesCell = document.createElement('td');
            datesCell.className = 'database-muted';
            datesCell.textContent = formatStoredDates(campaign.oldest, campaign.newest);

            row.append(checkCell, nameCell, clicksCell, blockedCell, rangeCell, totalCell, datesCell);
            elements.rows.append(row);
        });

        const trafficbackRow = document.createElement('tr');
        trafficbackRow.className = 'trafficback-table-row';
        const trafficbackCheckCell = document.createElement('td');
        trafficbackCheckCell.className = 'database-check';
        const trafficbackCheckbox = document.createElement('input');
        trafficbackCheckbox.type = 'checkbox';
        trafficbackCheckbox.id = 'selectTrafficback';
        trafficbackCheckbox.className = 'database-record-select database-trafficback-select';
        trafficbackCheckbox.disabled = Number(trafficback.recordsRange) < 1;
        trafficbackCheckbox.setAttribute('aria-label', 'Select Trafficback');
        trafficbackCheckbox.addEventListener('change', syncSelectionControls);
        trafficbackCheckCell.append(trafficbackCheckbox);
        elements.trafficback = trafficbackCheckbox;

        const trafficbackNameCell = document.createElement('td');
        trafficbackNameCell.className = 'database-campaign-name';
        trafficbackNameCell.append(document.createTextNode('Trafficback'));
        const badge = document.createElement('span');
        badge.className = 'database-global-badge';
        badge.textContent = 'GLOBAL';
        trafficbackNameCell.append(badge);

        const clicksCell = document.createElement('td');
        clicksCell.className = 'database-muted';
        clicksCell.textContent = '—';
        const blockedCell = document.createElement('td');
        blockedCell.className = 'database-muted';
        blockedCell.textContent = '—';
        const rangeCell = document.createElement('td');
        rangeCell.textContent = formatNumber(trafficback.recordsRange);
        const totalCell = document.createElement('td');
        totalCell.textContent = formatNumber(trafficback.recordsTotal);
        const datesCell = document.createElement('td');
        datesCell.className = 'database-muted';
        datesCell.textContent = formatStoredDates(trafficback.oldest, trafficback.newest);

        trafficbackRow.append(trafficbackCheckCell, trafficbackNameCell, clicksCell, blockedCell, rangeCell, totalCell, datesCell);
        elements.rows.append(trafficbackRow);
    }

    function renderSummary(summary) {
        state.summary = summary;
        const trafficback = summary.trafficback || {};
        renderRows(summary.campaigns || [], trafficback);
        elements.selectAll.checked = false;
        elements.selectAll.indeterminate = false;
        syncSelectionControls();
    }

    function jobStatusLabel(status) {
        const labels = {
            deleting: 'Deleting statistics',
            ready_to_compact: 'Deletion complete',
            compaction_required: 'Compaction required',
            compacting: 'Compacting SQLite database',
            completed: 'Database maintenance completed',
            cancelled: 'Cleanup stopped',
            owned_by_another_session: 'Maintenance is active in another admin session',
        };
        return labels[status] || 'Database maintenance';
    }

    function resultMetric(label, value) {
        const wrapper = document.createElement('div');
        const labelNode = document.createElement('small');
        labelNode.textContent = label;
        const valueNode = document.createElement('strong');
        valueNode.textContent = value;
        wrapper.append(labelNode, valueNode);
        return wrapper;
    }

    function renderJob(job) {
        state.job = job;
        if (!job) {
            elements.progress.hidden = true;
            elements.progress.classList.remove('is-active');
            syncSelectionControls();
            return;
        }

        elements.progress.hidden = false;
        elements.progress.classList.toggle(
            'is-active',
            job.status === 'compacting' || (job.status === 'deleting' && state.running),
        );
        elements.status.textContent = jobStatusLabel(job.status);
        elements.message.textContent = job.message || '';
        const progress = Number.isFinite(Number(job.progress)) ? Number(job.progress) : 0;
        elements.progressBar.style.width = `${Math.max(0, Math.min(100, progress))}%`;
        elements.progressTrack.setAttribute('aria-valuenow', String(progress));

        elements.resume.hidden = !(job.status === 'deleting' && !state.running);
        elements.retry.hidden = !(['ready_to_compact', 'compaction_required'].includes(job.status) && !state.running);
        elements.cancel.hidden = !['deleting', 'ready_to_compact', 'compaction_required'].includes(job.status);
        elements.cancel.textContent = job.status === 'deleting' ? 'Stop' : 'Dismiss';
        elements.result.hidden = !['completed', 'cancelled', 'compaction_required'].includes(job.status);
        elements.result.replaceChildren();

        if (!elements.result.hidden) {
            elements.result.append(
                resultMetric('Deleted records', formatNumber(job.deleted?.total || 0)),
                resultMetric('Database before', formatBytes(job.databaseBefore?.bytes)),
                resultMetric('Database after', job.databaseAfter ? formatBytes(job.databaseAfter.bytes) : 'Not compacted'),
            );
            if (job.status === 'completed') {
                elements.result.append(resultMetric('Disk space reclaimed', formatBytes(job.physicalReclaimedBytes)));
            }
            if (job.status === 'compaction_required' && job.compaction) {
                elements.result.append(resultMetric('Free space required', formatBytes(job.compaction.requiredFreeBytes)));
            }
        }
        syncSelectionControls();

        if (job.status === 'compacting' && !state.running && state.pollTimer === null) {
            state.pollTimer = window.setTimeout(pollCompactionStatus, 1500);
        }
    }

    async function pollCompactionStatus() {
        state.pollTimer = null;
        if (state.running || state.job?.status !== 'compacting') return;
        try {
            const data = await request('status', null, 'GET');
            if (data.job) renderJob(data.job);
            if (data.job && data.job.status !== 'compacting') await loadSummary();
        } catch (error) {
            elements.message.textContent = `${error.message}. Status will be checked again.`;
            state.pollTimer = window.setTimeout(pollCompactionStatus, 3000);
        }
    }

    async function loadSummary() {
        hideAlert();
        try {
            const query = new URLSearchParams({
                action: 'summary',
                startdate: config.startdate,
                enddate: config.enddate,
            });
            const response = await fetch(`${config.api}?${query.toString()}`, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || data.error) throw new Error(data.error || `HTTP ${response.status}`);
            renderSummary(data.summary);
            if (data.summary.activeJob) {
                renderJob(data.summary.activeJob);
                if (data.summary.activeJob.status === 'owned_by_another_session') {
                    showAlert('Another admin session owns the active database maintenance job.');
                }
            } else if (!['completed', 'cancelled'].includes(state.job?.status)) {
                renderJob(null);
            }
        } catch (error) {
            showAlert(`Unable to load database statistics: ${error.message}`, true);
        }
    }

    function renderPreview(preview) {
        elements.previewSummary.replaceChildren();
        const headline = document.createElement('strong');
        headline.textContent = `${formatNumber(preview.counts.total)} primary records will be deleted`;
        const details = document.createElement('div');
        details.className = 'database-muted';
        details.textContent = `${formatNumber(preview.counts.clicks)} clicks · ${formatNumber(preview.counts.blocked)} blocked · ${formatNumber(preview.counts.trafficback)} trafficback`;
        const list = document.createElement('ul');
        preview.campaigns.forEach((campaign) => {
            const item = document.createElement('li');
            item.textContent = campaign.name;
            list.append(item);
        });
        if (preview.includeTrafficback) {
            const item = document.createElement('li');
            item.textContent = 'Global Trafficback statistics';
            list.append(item);
        }
        elements.previewSummary.append(headline, details, list);

        const recent = Number(preview.recent?.total || 0);
        elements.trafficWarning.hidden = recent < 1;
        if (recent > 0) {
            elements.trafficWarning.replaceChildren();
            const icon = document.createElement('i');
            icon.className = 'bi bi-broadcast';
            const text = document.createElement('div');
            text.innerHTML = `<strong>Live traffic detected.</strong><br>${formatNumber(recent)} new records arrived in the selected scope during the last five minutes. Batch deletion may briefly delay writes; VACUUM can block them longer.`;
            elements.trafficWarning.append(icon, text);
        }

        elements.confirmation.value = '';
        elements.start.disabled = true;
        $(elements.modal).modal({
            modalClass: 'database-modal',
            fadeDuration: 180,
            showClose: false,
            clickClose: false,
            escapeClose: false,
        });
        setTimeout(() => elements.confirmation.focus(), 50);
    }

    async function previewCleanup() {
        hideAlert();
        const campaignIds = selectedCampaignIds();
        const includeTrafficback = elements.trafficback.checked && !elements.trafficback.disabled;
        elements.cleanup.disabled = true;
        try {
            const data = await request('preview', {
                campaignIds,
                includeTrafficback,
                startdate: config.startdate,
                enddate: config.enddate,
            });
            state.previewToken = data.previewToken;
            state.previewSelection = { campaignIds, includeTrafficback };
            renderPreview(data.preview);
        } catch (error) {
            showAlert(`Unable to preview cleanup: ${error.message}`, true);
        } finally {
            syncSelectionControls();
        }
    }

    async function startCleanup() {
        if (!state.previewSelection || elements.confirmation.value !== 'DELETE') return;
        elements.start.disabled = true;
        elements.cancelConfirm.disabled = true;
        try {
            const data = await request('start', {
                ...state.previewSelection,
                startdate: config.startdate,
                enddate: config.enddate,
                confirmation: 'DELETE',
                previewToken: state.previewToken,
            });
            $.modal.close();
            renderJob(data.job);
            await driveJob();
        } catch (error) {
            showAlert(`Unable to start cleanup: ${error.message}`, true);
        } finally {
            elements.cancelConfirm.disabled = false;
            elements.start.disabled = elements.confirmation.value !== 'DELETE';
        }
    }

    async function compactJob() {
        if (!state.job?.id) return;
        const compactingJob = { ...state.job, status: 'compacting', message: 'VACUUM is rebuilding the SQLite database.' };
        renderJob(compactingJob);
        try {
            const data = await request('compact', { jobId: state.job.id });
            renderJob(data.job);
            await loadSummary();
        } catch (error) {
            showAlert(`Compaction could not finish: ${error.message}`, true);
            try {
                const status = await request('status', null, 'GET');
                if (status.job) renderJob(status.job);
            } catch (_) {
                renderJob({ ...state.job, status: 'compaction_required', message: error.message });
            }
        }
    }

    async function driveJob() {
        if (state.running || !state.job) return;
        state.running = true;
        state.stopRequested = false;
        renderJob(state.job);
        let retryDelay = 750;
        try {
            while (state.job?.status === 'deleting' && !state.stopRequested) {
                try {
                    const data = await request('batch', { jobId: state.job.id });
                    renderJob(data.job);
                    retryDelay = 750;
                } catch (error) {
                    if (error.retryable && !state.stopRequested) {
                        elements.message.textContent = `${error.message}. Retrying…`;
                        await wait(retryDelay);
                        retryDelay = Math.min(5000, retryDelay * 1.7);
                        continue;
                    }
                    throw error;
                }
                await wait(80);
            }
        } catch (error) {
            showAlert(`Cleanup paused: ${error.message}. Use Resume to continue.`, true);
        } finally {
            state.running = false;
            renderJob(state.job);
        }

        if (!state.stopRequested && state.job?.status === 'ready_to_compact') {
            state.running = true;
            try {
                await compactJob();
            } finally {
                state.running = false;
                renderJob(state.job);
            }
        } else if (!state.running && ['completed', 'cancelled'].includes(state.job?.status)) {
            await loadSummary();
        }
    }

    async function cancelJob() {
        if (!state.job?.id || !window.confirm('Stop this cleanup? Already deleted records will not be restored.')) return;
        state.stopRequested = true;
        if (state.running) {
            elements.message.textContent = 'Stopping after the current batch…';
            while (state.running) await wait(100);
        }
        try {
            const data = await request('cancel', { jobId: state.job.id });
            renderJob(data.job);
            await loadSummary();
        } catch (error) {
            showAlert(`Unable to stop cleanup: ${error.message}`, true);
        }
    }

    async function standaloneCompaction() {
        const recent = Number(state.summary?.recent?.total || 0);
        const activity = recent > 0 ? `\n\nLive traffic detected: ${formatNumber(recent)} records arrived in the last five minutes.` : '';
        if (!window.confirm(`VACUUM can block database writes while SQLite is rebuilt.${activity}\n\nCompact the database now?`)) return;

        state.running = true;
        syncSelectionControls();
        elements.progress.hidden = false;
        elements.progress.classList.add('is-active');
        elements.status.textContent = 'Compacting SQLite database';
        elements.message.textContent = 'Checking free space and starting VACUUM…';
        elements.progressBar.style.width = '100%';
        try {
            const data = await request('compact', {});
            renderJob(data.job);
            await loadSummary();
        } catch (error) {
            showAlert(`Compaction could not finish: ${error.message}`, true);
            await loadSummary();
        } finally {
            state.running = false;
            if (state.job?.status !== 'compacting') {
                elements.progress.classList.remove('is-active');
            }
            syncSelectionControls();
        }
    }

    elements.selectAll.addEventListener('change', () => {
        document.querySelectorAll('.database-record-select:not(:disabled)').forEach((checkbox) => {
            checkbox.checked = elements.selectAll.checked;
        });
        syncSelectionControls();
    });
    elements.cleanup.addEventListener('click', previewCleanup);
    elements.compact.addEventListener('click', standaloneCompaction);
    elements.confirmation.addEventListener('input', () => {
        elements.start.disabled = elements.confirmation.value !== 'DELETE';
    });
    elements.cancelConfirm.addEventListener('click', () => $.modal.close());
    elements.start.addEventListener('click', startCleanup);
    elements.resume.addEventListener('click', driveJob);
    elements.retry.addEventListener('click', async () => {
        state.running = true;
        try {
            await compactJob();
        } finally {
            state.running = false;
            renderJob(state.job);
        }
    });
    elements.cancel.addEventListener('click', cancelJob);

    loadSummary();
})();
