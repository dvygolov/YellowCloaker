// ── Domain management ──
function buildDomainRow(domain, statusHtml) {
    return '<div class="form-group-inner domain-item"><div class="row">' +
        '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Domain:</label></div>' +
        '<div class="col-lg-3"><input type="text" class="form-control domain-name" value="' + domain + '" placeholder="domain.com" readonly /></div>' +
        '<div class="col-lg-1 domain-status-col">' + statusHtml + '</div>' +
        '<div class="col-lg-2 domain-action-col"><a href="javascript:void(0)" class="btn btn-danger btn-sm remove-domain-item" title="Delete"><i class="bi bi-trash"></i></a></div>' +
        '</div></div>';
}

function domainStatusIcon(data) {
    if (data.wildcard) {
        return '<i class="bi bi-asterisk domain-status" style="color:#f59e0b" title="Wildcard domain \u2014 DNS check skipped"></i>';
    }
    if (data.cloudflare) {
        return '<span class="domain-cf-badge domain-status" title="CloudFlare detected (IP: ' + (data.ip || '?') + ')">CF</span>';
    }
    if (data.resolves) {
        return '<i class="bi bi-check-circle-fill domain-status" style="color:#22c55e" title="Resolves to this server (' + (data.ip || '') + ')"></i>';
    }
    var errMsg = data.error || 'Domain does not resolve to this server';
    return '<i class="bi bi-exclamation-triangle-fill domain-status" style="color:#f59e0b" title="' + errMsg.replace(/"/g, '&quot;') + '"></i>';
}

function checkDomain(domain) {
    return fetch('domaincheck.php?domain=' + encodeURIComponent(domain))
        .then(function (r) { return r.json(); });
}

function checkDomainStatus(row) {
    var domain = row.querySelector('.domain-name').value.trim();
    if (!domain) return;
    var col = row.querySelector('.domain-status-col');
    col.innerHTML = '<i class="bi bi-hourglass-split domain-status" style="color:#94a3b8" title="Checking..."></i>';
    checkDomain(domain).then(function (data) {
        col.innerHTML = domainStatusIcon(data);
    }).catch(function () {
        col.innerHTML = '<i class="bi bi-question-circle domain-status" style="color:#94a3b8" title="Check failed"></i>';
    });
}

// Delete domain row (delegated)
document.addEventListener('click', function (e) {
    var delBtn = e.target.closest('.remove-domain-item');
    if (!delBtn) return;
    var row = delBtn.closest('.domain-item');
    if (row) row.remove();
    if (window.syncDomainWhiteSections) window.syncDomainWhiteSections();
});

// Add domain
document.getElementById('add-domain-item')?.addEventListener('click', function () {
    var domain = prompt('Enter domain (without http(s)://):');
    if (!domain || !domain.trim()) return;
    domain = domain.trim().replace(/^https?:\/\//i, '').replace(/\/+$/, '');
    if (!domain) return;

    // Check for duplicates
    var existing = document.querySelectorAll('#domains_container .domain-name');
    for (var i = 0; i < existing.length; i++) {
        if (existing[i].value.trim().toLowerCase() === domain.toLowerCase()) {
            alert('Domain "' + domain + '" is already added.');
            return;
        }
    }

    var loadingHtml = '<i class="bi bi-hourglass-split domain-status" style="color:#94a3b8" title="Checking..."></i>';
    var container = document.getElementById('domains_container');
    container.insertAdjacentHTML('beforeend', buildDomainRow(domain, loadingHtml));
    var newRow = container.querySelector('.domain-item:last-child');
    checkDomainStatus(newRow);
    if (window.syncDomainWhiteSections) window.syncDomainWhiteSections();
});

// Check all existing domains on page load
document.querySelectorAll('#domains_container .domain-item').forEach(function (row) {
    checkDomainStatus(row);
});

// Collect domains for save
window.collectDomainsData = function () {
    var domains = [];
    document.querySelectorAll('#domains_container .domain-name').forEach(function (inp) {
        var name = inp.value.trim();
        if (name) domains.push(name);
    });
    return domains;
};
