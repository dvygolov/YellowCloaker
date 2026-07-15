// ── Auto-sync: when domains change, rebuild domain-specific sections + sidebar nav ──
// _dwsCounter is set on window by inline PHP script before this module loads
var _dwsCounter = window._dwsCounterInit || 0;

window.syncDomainWhiteSections = function () {
    var currentDomains = window.collectDomainsData ? window.collectDomainsData() : [];
    var isDomainSpecific = !!document.querySelector('.white-scope-radio[value="true"]:checked');
    // Remove sections + nav items for domains that no longer exist
    document.querySelectorAll('section.dws-section').forEach(function (sec) {
        if (currentDomains.indexOf(sec.dataset.domain) === -1) sec.remove();
    });
    document.querySelectorAll('.dws-nav-item').forEach(function (li) {
        if (currentDomains.indexOf(li.dataset.domain) === -1) li.remove();
    });
    // Add sections + nav items for new domains
    var flowsSection = document.getElementById('sec-flows');
    currentDomains.forEach(function (domain) {
        if (!document.querySelector('section.dws-section[data-domain="' + CSS.escape(domain) + '"]')) {
            // Insert section before sec-flows
            var secHtml = buildDwsSection(domain);
            flowsSection.insertAdjacentHTML('beforebegin', secHtml);
            // Insert sidebar nav item
            var navHtml = '<li class="dws-nav-item" data-domain="' + domain.replace(/"/g, '&quot;') + '" style="' + (isDomainSpecific ? '' : 'display:none') + '"><a href="#sec-dws-d' + (_dwsCounter - 1) + '">&nbsp;&nbsp;' + domain.replace(/</g, '&lt;') + '</a></li>';
            var lastDwsNav = document.querySelectorAll('.dws-nav-item');
            if (lastDwsNav.length > 0) {
                lastDwsNav[lastDwsNav.length - 1].insertAdjacentHTML('afterend', navHtml);
            } else {
                var safePageNav = document.querySelector('a[href="#sec-safepage"]');
                if (safePageNav) safePageNav.closest('li').insertAdjacentHTML('afterend', navHtml);
            }
        }
    });
};

function buildDwsSection(domain) {
    var n = _dwsCounter++;
    var secId = 'sec-dws-d' + n;
    var actName = 'dws_action_d' + n;
    return '<section id="' + secId + '" class="camp-section dws-section" data-domain="' + domain.replace(/"/g, '&quot;') + '">' +
        '<h5>' + domain.replace(/</g, '&lt;') + ' — Safe Page</h5>' +
        '<div class="form-group-inner"><div class="row">' +
        '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Method:</label></div>' +
        '<div class="col-lg-9"><div class="bt-df-checkbox pull-left">' +
        '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" checked value="folder" name="' + actName + '" class="dws-action" /> Local folder</label></div></div></div>' +
        '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="redirect" name="' + actName + '" class="dws-action" /> Redirect</label></div></div></div>' +
        '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="curl" name="' + actName + '" class="dws-action" /> CURL</label></div></div></div>' +
        '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="error" name="' + actName + '" class="dws-action" /> HTTP Code</label></div></div></div>' +
        '</div></div></div></div>' +
        '<div class="dws-folder-block"><div class="dws-folder-items"></div>' +
        '<a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-existing"><i class="bi bi-folder-symlink"></i> Add Existing</a> ' +
        '<a href="javascript:void(0)" class="btn btn-info campaign-action-btn dws-upload-zip"><i class="bi bi-upload"></i> Upload ZIP</a></div>' +
        '<div class="dws-redirect-block" style="display:none"><div class="dws-redirect-items"></div>' +
        '<a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-redirect">+ Add URL</a>' +
        '<div class="form-group-inner" style="margin-top:10px"><div class="row">' +
        '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect type:</label></div>' +
        '<div class="col-lg-3"><select class="form-select dws-redirect-type"><option value="301">301</option><option value="302" selected>302</option><option value="303">303</option><option value="307">307</option></select></div>' +
        '</div></div></div>' +
        '<div class="dws-curl-block" style="display:none"><div class="dws-curl-items"></div>' +
        '<a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-curl">+ Add CURL</a></div>' +
        '<div class="dws-error-block" style="display:none"><div class="dws-error-items"></div>' +
        '<a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-error">+ Add Code</a></div>' +
        '</section>';
}
