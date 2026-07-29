// ── Auto-sync: when domains change, rebuild domain-specific sections + sidebar nav ──
// _dwsCounter is set on window by inline PHP script before this module loads
var _dwsCounter = window._dwsCounterInit || 0;

window.syncDomainWhiteSections = function () {
    var currentDomains = window.collectDomainsData ? window.collectDomainsData() : [];

    function findSection(domain) {
        return Array.from(document.querySelectorAll('section.dws-section')).find(function (section) {
            return section.dataset.domain === domain;
        });
    }

    function findNavItem(domain) {
        return Array.from(document.querySelectorAll('.dws-nav-item')).find(function (item) {
            return item.dataset.domain === domain;
        });
    }

    // Remove sections + nav items for domains that no longer exist
    document.querySelectorAll('section.dws-section').forEach(function (sec) {
        if (currentDomains.indexOf(sec.dataset.domain) === -1) sec.remove();
    });
    document.querySelectorAll('.dws-nav-item').forEach(function (li) {
        if (currentDomains.indexOf(li.dataset.domain) === -1) li.remove();
    });
    // Keep sections and sidebar items in sync independently. Sections are
    // rendered server-side even in Global mode, while their nav items are not.
    var flowsSection = document.getElementById('sec-flows');
    var safePageNav = document.querySelector('a[href="#sec-safepage"]');
    var previousNavItem = safePageNav ? safePageNav.closest('li') : null;

    currentDomains.forEach(function (domain) {
        var section = findSection(domain);
        if (!section) {
            var secHtml = buildDwsSection(domain);
            flowsSection.insertAdjacentHTML('beforebegin', secHtml);
            section = findSection(domain);
        } else {
            // Moving the existing node preserves unsaved form values and keeps
            // domain-specific sections in the same order as the Domains list.
            flowsSection.insertAdjacentElement('beforebegin', section);
        }

        var navItem = findNavItem(domain);
        if (!navItem) {
            navItem = document.createElement('li');
            navItem.className = 'dws-nav-item';
            navItem.dataset.domain = domain;
            navItem.appendChild(document.createElement('a'));
        }

        var link = navItem.querySelector('a');
        link.href = '#' + section.id;
        link.textContent = domain;

        if (previousNavItem) {
            previousNavItem.insertAdjacentElement('afterend', navItem);
        }
        previousNavItem = navItem;
    });

    if (window.refreshCampaignNavTree) window.refreshCampaignNavTree();
};

function buildDwsSection(domain) {
    var n = _dwsCounter++;
    var secId = 'sec-dws-d' + n;
    var actName = 'dws_action_d' + n;
    var safeDomain = escapeHtml(domain);
    return '<section id="' + secId + '" class="camp-section dws-section" data-domain="' + safeDomain + '">' +
        '<h5 class="dws-page-title"><i class="bi bi-globe2" aria-hidden="true"></i>' + safeDomain + ' — Safe Page</h5>' +
        '<div class="flow-group dws-method-group"><span class="flow-group-title">Method</span>' +
        '<div class="form-group-inner"><div class="row">' +
        '<div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><label for="' + actName + '" class="login2 pull-left pull-left-pro">Choose method:</label></div>' +
        '<div class="col-lg-5 col-md-6 col-sm-6 col-xs-12">' +
        '<select id="' + actName + '" name="' + actName + '" class="form-select dws-action">' +
        '<option value="folder" selected>Local safe page from folder</option>' +
        '<option value="redirect">Redirect</option>' +
        '<option value="curl">Load a website using CURL</option>' +
        '<option value="error">Return HTTP-code</option>' +
        '</select></div></div></div>' +
        '<div class="dws-folder-block"><div class="dws-folder-items"></div>' +
        '<a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-existing"><i class="bi bi-folder-symlink"></i> Add Existing</a> ' +
        '<a href="javascript:void(0)" class="btn btn-info campaign-action-btn dws-upload-zip"><i class="bi bi-upload"></i> Upload ZIP</a></div>' +
        '<div class="dws-redirect-block" style="display:none"><div class="dws-redirect-items"></div>' +
        '<a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-redirect">+ Add Redirect</a>' +
        '<div class="form-group-inner" style="margin-top:10px"><div class="row">' +
        '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect type:</label></div>' +
        '<div class="col-lg-3"><select class="form-select dws-redirect-type"><option value="301">301</option><option value="302" selected>302</option><option value="303">303</option><option value="307">307</option></select></div>' +
        '</div></div></div>' +
        '<div class="dws-curl-block" style="display:none"><div class="dws-curl-items"></div>' +
        '<a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-curl">+ Add Curl</a></div>' +
        '<div class="dws-error-block" style="display:none"><div class="dws-error-items"></div>' +
        '<a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-error">+ Add HTTP Code</a></div>' +
        '</div>' +
        '</section>';
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
