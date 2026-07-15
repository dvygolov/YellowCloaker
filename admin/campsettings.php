<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/campinit.php';
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../abtest.php';
global $c, $db, $campId;
?>
<!doctype html>
<html lang="en">
<?php include __DIR__.'/head.php' ?>
<link rel="stylesheet" href="<?=get_admin_base_url()?>css/campsettings.css?v=<?=filemtime(__DIR__.'/css/campsettings.css')?>">
<link rel="stylesheet" href="<?=get_admin_base_url()?>css/fileeditor.css?v=<?=filemtime(__DIR__.'/css/fileeditor.css')?>">

<body>
    <?php include __DIR__.'/header.php' ?>
    <div class="all-content-wrapper">
        <div class="camp-layout">
            <nav class="camp-sidebar" data-campaign-id="<?= (int)$campId ?>">
                <div class="camp-name">
                    <span class="camp-name-text"><?= htmlspecialchars($campName) ?></span>
                    <button
                        type="button"
                        class="camp-rename-btn"
                        id="renameCampaign"
                        data-campaign-id="<?= (int)$campId ?>"
                        data-campaign-name="<?= htmlspecialchars($campName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        title="Rename campaign"
                        aria-label="Rename campaign <?= htmlspecialchars($campName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    ><i class="bi bi-pencil-fill" aria-hidden="true"></i></button>
                </div>
                <ul>
                    <li><a href="#sec-domains" class="active">Domains</a></li>
                    <li class="safepage-nav-root nav-tree-parent">
                        <button type="button" class="campaign-nav-toggle" data-tree-toggle="safe-pages" aria-expanded="true" aria-label="Collapse domain-specific safe pages" title="Collapse domain-specific safe pages"><i class="bi bi-dash-square" aria-hidden="true"></i></button>
                        <a href="#sec-safepage">Safe Page</a>
                    </li>
                    <?php if ($c->white->domainFilterEnabled) foreach ($c->domains as $di => $domainName) { ?>
                    <li class="dws-nav-item" data-domain="<?= htmlspecialchars($domainName) ?>"><a href="#sec-dws-<?= $di ?>"><?= htmlspecialchars($domainName) ?></a></li>
                    <?php } ?>
                    <li class="flows-nav-root nav-tree-parent">
                        <button type="button" class="campaign-nav-toggle" data-tree-toggle="flows" aria-expanded="true" aria-label="Collapse flows" title="Collapse flows"><i class="bi bi-dash-square" aria-hidden="true"></i></button>
                        <a href="#sec-flows">Flows</a>
                    </li>
                    <?php foreach ($c->black->flows as $fi => $flow) { ?>
                    <li class="flow-nav-item nav-tree-parent" data-flow-index="<?= $fi ?>" data-flow-key="<?= htmlspecialchars($flow->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <button type="button" class="campaign-nav-toggle" data-tree-toggle="steps" aria-expanded="true" aria-label="Collapse steps for <?= htmlspecialchars($flow->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" title="Collapse steps"><i class="bi bi-dash-square" aria-hidden="true"></i></button>
                        <a href="#sec-flow-<?= $fi ?>"><?= htmlspecialchars($flow->name) ?></a>
                    </li>
                    <?php foreach ($flow->steps as $si => $step) { ?>
                    <li class="step-nav-item" data-flow-index="<?= $fi ?>" data-step-index="<?= $si ?>"><a href="#sec-step-<?= $fi ?>-<?= $si ?>">Step <?= $si + 1 ?></a></li>
                    <?php } ?>
                    <?php } ?>
                    <li><a href="#sec-scripts">Scripts</a></li>
                    <li><a href="#sec-postbacks">Postbacks</a></li>
                    <li><a href="#sec-api">API</a></li>
                </ul>
            </nav>
            <div class="camp-content">
        <form id="campsettings" autocomplete="off">
            <section id="sec-domains" class="camp-section active">
            <div class="flow-group domains-group">
                <span class="flow-group-title">
                    <i class="bi bi-info-circle admin-info-icon" title="Add all of the campaign's domains WITHOUT HTTP(S)! You can use *.xxx.com to match ALL subdomains."></i>Domains list
                </span>
                <div id="domains_container">
                    <?php foreach ($c->domains as $dn) { ?>
                    <div class="form-group-inner domain-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Domain:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control domain-name" value="<?= htmlspecialchars($dn) ?>" placeholder="domain.com" readonly /></div>
                            <div class="col-lg-1 domain-status-col"><i class="bi bi-hourglass-split domain-status" style="color:#94a3b8" title="Checking..."></i></div>
                            <div class="col-lg-2 domain-action-col"><a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn remove-domain-item" title="Delete"><i class="bi bi-trash"></i></a></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-domain-item" class="btn btn-primary campaign-action-btn" href="javascript:void(0)"><i class="bi bi-plus-circle"></i> Add Domain</a>
            </div>
            </section>

            <section id="sec-safepage" class="camp-section">
            <div class="flow-group">
            <span class="flow-group-title flow-group-title-with-help">
                <i class="bi bi-info-circle admin-info-icon setting-help-icon" tabindex="0" role="img" aria-label="Traffic matching these filters will be shown the safe page. Everyone else goes to the Flows section." data-tooltip="Traffic matching these filters will be shown the safe page. Everyone else goes to the Flows section."></i>
                Filters
            </span>
            <div class="form-group-inner">
                <div class="row">
                    <div id="filtersbuilder"></div>
                </div>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Scope</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">White page mode:</label></div>
                    <div class="col-lg-9">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= !$c->white->domainFilterEnabled ? 'checked' : '' ?> value="false" name="white.domainfilter.use" class="white-scope-radio" /> Global <i class="bi bi-info-circle admin-info-icon setting-help-icon" tabindex="0" role="img" aria-label="Uses the same white page configuration for every campaign domain." data-tooltip="Uses the same white page configuration for every campaign domain."></i></label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->white->domainFilterEnabled ? 'checked' : '' ?> value="true" name="white.domainfilter.use" class="white-scope-radio" /> Domain-Specific <i class="bi bi-info-circle admin-info-icon setting-help-icon" tabindex="0" role="img" aria-label="Creates an independent white page configuration for each campaign domain." data-tooltip="Creates an independent white page configuration for each campaign domain."></i></label>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div id="global-white-config" style="display:<?= !$c->white->domainFilterEnabled ? 'block' : 'none' ?>">
            <div class="flow-group">
            <span class="flow-group-title">Method</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Choose
                            method:</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->white->action === 'folder' ? 'checked' : '' ?> value="folder" name="white.action" onclick="(document.getElementById('b_2').style.display = 'block'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'none')" /> Local safe page from folder</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->white->action === 'redirect' ? 'checked' : '' ?> value="redirect" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'block'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'none')" /> Redirect</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->white->action === 'curl' ? 'checked' : '' ?> value="curl" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'block'); (document.getElementById('b_5').style.display = 'none')" /> Load a website using CURL</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->white->action === 'error' ? 'checked' : '' ?> value="error" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'block')" /> Return HTTP-code <i class="bi bi-info-circle admin-info-icon setting-help-icon" tabindex="0" role="img" aria-label="Examples: 404 Not Found or 200 OK." data-tooltip="Examples: 404 Not Found or 200 OK."></i></label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_2" style="display:<?= $c->white->action === 'folder' ? 'block' : 'none' ?>;">
                <div id="white_folder_container">
                    <?php for ($i = 0; $i < count($c->white->folderNames); $i++) {
                            $fn = $c->white->folderNames[$i];
                    ?>
                    <div class="form-group-inner white-folder-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Safe page folder:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control white-folder-name" value="<?= htmlspecialchars($fn) ?>" placeholder="white1" readonly /></div>
                            <div class="col-lg-4"><div class="btn-group campaign-icon-group"><a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="<?= htmlspecialchars($c->white->getLoadMode($fn)) ?>" data-modes="base,rewrite,direct" title="Loading mode"><i class="bi <?= match($c->white->getLoadMode($fn)) { 'rewrite' => 'bi-arrow-repeat', 'direct' => 'bi-hdd-network', default => 'bi-house-door' } ?>"></i></a><a href="javascript:void(0)" class="btn btn-warning white-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a><a href="javascript:void(0)" class="btn btn-danger remove-white-folder-item" title="Delete"><i class="bi bi-trash"></i></a></div></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn white-add-existing"><i class="bi bi-folder-symlink"></i> Add Existing</a>
                <a href="javascript:void(0)" class="btn btn-info campaign-action-btn white-upload-zip"><i class="bi bi-upload"></i> Upload ZIP</a>
            </div>
            <div id="b_3" style="display:<?= ($c->white->action === 'redirect' ? 'block' : 'none') ?>;">

                <div id="redirect_container">
                    <?php for ($i = 0; $i < count($c->white->redirectUrls); $i++) {
                            $ru = $c->white->redirectUrls[$i];
                    ?>
                    <div class="form-group-inner redirect-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">Redirect address:</label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="https://ya.ru" value="<?=$ru?>" name="white.redirect.urls[<?= $i ?>]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-redirect-item btn btn-danger campaign-icon-btn" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-redirect-item" class="btn btn-primary" href="javascript:;">+ Add Redirect</a>

                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Redirect type:</label>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <select class="form-select" name="white.redirect.type">
                                <?php foreach ([301,302,303,307] as $rt) { ?>
                                <option value="<?= $rt ?>" <?= $c->white->redirectType === $rt ? 'selected' : '' ?>><?= $rt ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_4" style="display:<?= $c->white->action === 'curl' ? 'block' : 'none' ?>;">
                <div id="curl_container">
                    <?php for ($i = 0; $i < count($c->white->curlUrls); $i++) {
                            $cu = $c->white->curlUrls[$i];
                    ?>
                    <div class="form-group-inner curl-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Curl address:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control white-curl-url" placeholder="https://ya.ru" value="<?=$cu?>" name="white.curls[<?= $i ?>]" /></div>
                            <div class="col-lg-2"><div class="btn-group campaign-icon-group"><a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="<?= htmlspecialchars($c->white->getLoadMode($cu)) ?>" data-modes="rewrite,direct" title="Loading mode"><i class="bi <?= $c->white->getLoadMode($cu) === 'direct' ? 'bi-hdd-network' : 'bi-arrow-repeat' ?>"></i></a><a href="javascript:void(0)" class="btn btn-danger remove-curl-item" title="Delete"><i class="bi bi-trash"></i></a></div></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-curl-item" class="btn btn-primary" href="javascript:;">+ Add Curl</a>
            </div>
            <div id="b_5" style="display:<?= $c->white->action === 'error' ? 'block' : 'none' ?>;">
                <div id="errorcodes_container">
                    <?php for ($i = 0; $i < count($c->white->errorCodes); $i++) {
                            $ec = $c->white->errorCodes[$i];
                    ?>
                    <div class="form-group-inner errorcode-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">HTTP code:</label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="404" value="<?=$ec?>" name="white.errorcodes[<?= $i ?>]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-errorcode-item btn btn-danger campaign-icon-btn" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-errorcode-item" class="btn btn-primary" href="javascript:;">+ Add HTTP Code</a>
            </div>
            </div>

            </div><!-- /#global-white-config -->

            </section>

            <?php
            // Build a lookup map: domain => DomainWhiteSettings
            $dwsMap = [];
            foreach ($c->white->domainSpecific as $dws) {
                $dwsMap[$dws->domain] = $dws;
            }
            foreach ($c->domains as $di => $domainName) {
                $dws = $dwsMap[$domainName] ?? null;
                $dwAction = $dws ? $dws->action : 'folder';
            ?>
            <section id="sec-dws-<?= $di ?>" class="camp-section dws-section" data-domain="<?= htmlspecialchars($domainName) ?>">
            <h5 class="dws-page-title"><i class="bi bi-globe2" aria-hidden="true"></i><?= htmlspecialchars($domainName) ?> — Safe Page</h5>

            <div class="flow-group dws-method-group">
            <span class="flow-group-title">Method</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Choose method:</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $dwAction === 'folder' ? 'checked' : '' ?> value="folder" name="dws_action_<?= $di ?>" class="dws-action" data-di="<?= $di ?>" /> Local safe page from folder</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $dwAction === 'redirect' ? 'checked' : '' ?> value="redirect" name="dws_action_<?= $di ?>" class="dws-action" data-di="<?= $di ?>" /> Redirect</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $dwAction === 'curl' ? 'checked' : '' ?> value="curl" name="dws_action_<?= $di ?>" class="dws-action" data-di="<?= $di ?>" /> Load a website using CURL</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $dwAction === 'error' ? 'checked' : '' ?> value="error" name="dws_action_<?= $di ?>" class="dws-action" data-di="<?= $di ?>" /> Return HTTP-code <i class="bi bi-info-circle admin-info-icon setting-help-icon" tabindex="0" role="img" aria-label="Examples: 404 Not Found or 200 OK." data-tooltip="Examples: 404 Not Found or 200 OK."></i></label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dws-folder-block" data-di="<?= $di ?>" style="display:<?= $dwAction === 'folder' ? 'block' : 'none' ?>">
                <div class="dws-folder-items">
                <?php if ($dws) foreach ($dws->folderNames as $fn) { ?>
                    <div class="form-group-inner dws-folder-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Safe page folder:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control dws-folder-name" value="<?= htmlspecialchars($fn) ?>" readonly /></div>
                            <div class="col-lg-4"><div class="btn-group campaign-icon-group">
                                <a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="<?= htmlspecialchars($dws->getLoadMode($fn)) ?>" data-modes="base,rewrite,direct" title="Loading mode"><i class="bi <?= match($dws->getLoadMode($fn)) { 'rewrite' => 'bi-arrow-repeat', 'direct' => 'bi-hdd-network', default => 'bi-house-door' } ?>"></i></a>
                                <a href="javascript:void(0)" class="btn btn-warning dws-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a>
                                <a href="javascript:void(0)" class="btn btn-danger dws-remove-folder" title="Delete"><i class="bi bi-trash"></i></a>
                            </div></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-existing" data-di="<?= $di ?>"><i class="bi bi-folder-symlink"></i> Add Existing</a>
                <a href="javascript:void(0)" class="btn btn-info campaign-action-btn dws-upload-zip" data-di="<?= $di ?>"><i class="bi bi-upload"></i> Upload ZIP</a>
            </div>

            <div class="dws-redirect-block" data-di="<?= $di ?>" style="display:<?= $dwAction === 'redirect' ? 'block' : 'none' ?>">
                <div class="dws-redirect-items">
                <?php if ($dws) foreach ($dws->redirectUrls as $ru) { ?>
                    <div class="form-group-inner dws-redirect-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect address:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control dws-redirect-url" value="<?= htmlspecialchars($ru) ?>" placeholder="https://example.com" /></div>
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn dws-remove-redirect" title="Delete"><i class="bi bi-trash"></i></a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-redirect" data-di="<?= $di ?>">+ Add Redirect</a>
                <div class="form-group-inner" style="margin-top:10px">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect type:</label></div>
                        <div class="col-lg-3"><select class="form-select dws-redirect-type">
                            <?php $dwRt = $dws ? $dws->redirectType : 302; foreach ([301,302,303,307] as $rt) { ?>
                            <option value="<?= $rt ?>" <?= $dwRt === $rt ? 'selected' : '' ?>><?= $rt ?></option>
                            <?php } ?>
                        </select></div>
                    </div>
                </div>
            </div>

            <div class="dws-curl-block" data-di="<?= $di ?>" style="display:<?= $dwAction === 'curl' ? 'block' : 'none' ?>">
                <div class="dws-curl-items">
                <?php if ($dws) foreach ($dws->curlUrls as $cu) { ?>
                    <div class="form-group-inner dws-curl-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Curl address:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control dws-curl-url" value="<?= htmlspecialchars($cu) ?>" placeholder="https://example.com" /></div>
                            <div class="col-lg-2"><div class="btn-group campaign-icon-group">
                                <a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="<?= htmlspecialchars($dws->getLoadMode($cu)) ?>" data-modes="rewrite,direct" title="Loading mode"><i class="bi <?= $dws->getLoadMode($cu) === 'direct' ? 'bi-hdd-network' : 'bi-arrow-repeat' ?>"></i></a>
                                <a href="javascript:void(0)" class="btn btn-danger dws-remove-curl" title="Delete"><i class="bi bi-trash"></i></a>
                            </div></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-curl" data-di="<?= $di ?>">+ Add Curl</a>
            </div>

            <div class="dws-error-block" data-di="<?= $di ?>" style="display:<?= $dwAction === 'error' ? 'block' : 'none' ?>">
                <div class="dws-error-items">
                <?php if ($dws) foreach ($dws->errorCodes as $ec) { ?>
                    <div class="form-group-inner dws-error-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">HTTP code:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control dws-error-code" value="<?= htmlspecialchars($ec) ?>" placeholder="404" /></div>
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn dws-remove-error" title="Delete"><i class="bi bi-trash"></i></a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn dws-add-error" data-di="<?= $di ?>">+ Add HTTP Code</a>
            </div>
            </div><!-- /.dws-method-group -->

            </section><!-- /sec-dws -->
            <?php } ?>

            <section id="sec-flows" class="camp-section">
            <div class="form-group-inner">
                <p>Flows are processed top-to-bottom. First flow whose filters match the visitor gets the traffic. Empty filters = catch-all.</p>
                <div id="flows-list">
                <?php foreach ($c->black->flows as $fi => $flow) { ?>
                    <div class="flow-list-row" data-flow-index="<?= $fi ?>">
                        <button type="button" class="reorder-handle flow-drag-handle" title="Drag to reorder" aria-label="Reorder <?= htmlspecialchars($flow->name) ?>. Drag or use the arrow keys."><i class="bi bi-grip-vertical" aria-hidden="true"></i></button>
                        <input type="text" class="form-control flow-name-label" value="<?= htmlspecialchars($flow->name) ?>" readonly />
                        <a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn flow-delete" title="Delete"><i class="bi bi-trash"></i></a>
                    </div>
                <?php } ?>
                </div>
                <a id="add-flow-btn" class="btn btn-primary campaign-action-btn" href="javascript:void(0)" style="margin-top:15px;">+ Add Flow</a>
            </div>
            <hr/>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <i class="bi bi-info-circle admin-info-icon" title="If Yes then the user will always be shown the same content on every visit"></i>
                            Save user flow (Sticky):
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->saveUserFlow === false ? 'checked' : '' ?> value="false" name="saveuserflow" /> No</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->saveUserFlow === true ? 'checked' : '' ?> value="true" name="saveuserflow" /> Yes</label>
                        </div>
                    </div>
                </div>
            </div>

            <?php $jbd = $c->black->jsBotDetection; ?>
            <div class="flow-group">
            <span class="flow-group-title">JS Bot Detection</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <i class="bi bi-info-circle admin-info-icon" title="If enabled, the user will first see a safe page. Only after browser-side checks confirm a real human will they see the money page."></i>
                            Enable JS Bot Detection:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= !$jbd->enabled ? 'checked' : '' ?> value="false" name="black.jsbotdetection.enabled" onclick="(document.getElementById('jbd-settings').style.display = 'none')" /> No</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $jbd->enabled ? 'checked' : '' ?> value="true" name="black.jsbotdetection.enabled" onclick="(document.getElementById('jbd-settings').style.display = 'block')" /> Yes</label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="jbd-settings" style="display:<?= $jbd->enabled ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Timeout (msec):</label></div>
                        <div class="col-lg-3"><input type="text" class="form-control" placeholder="10000" name="black.jsbotdetection.timeout" value="<?= $jbd->timeout ?>" /></div>
                    </div>
                </div>
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Tests:</label></div>
                        <div class="col-lg-9"><div class="ywb-radios">
                            <?php foreach (['pointerdown' => 'Mouse click / Touch start', 'keydown' => 'Text typing', 'devicemotion' => 'Device motion (Android only)', 'deviceorientation' => 'Device orientation (Android only)', 'audiocontext' => 'Audio engine existence', 'timezone' => 'Time zone'] as $ev => $evLabel) { ?>
                            <label class="ywb-radio-label"><input type="checkbox" name="black.jsbotdetection.events[]" value="<?= $ev ?>" <?= in_array($ev, $jbd->events) ? 'checked' : '' ?> <?= $ev === 'timezone' ? 'onchange="(document.getElementById(\'jbd-tz\').style.display = this.checked ? \'block\' : \'none\')"' : '' ?> /> <?= $evLabel ?></label>
                            <?php } ?>
                        </div></div>
                    </div>
                </div>
                <div id="jbd-tz" class="form-group-inner" style="display:<?= in_array('timezone', $jbd->events) ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Minimum allowed timezone</label></div>
                        <div class="col-lg-3"><input type="text" class="form-control" placeholder="-3" name="black.jsbotdetection.timezone.min" value="<?= $jbd->tzMin ?>" /></div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Maximum allowed timezone</label></div>
                        <div class="col-lg-3"><input type="text" class="form-control" placeholder="3" name="black.jsbotdetection.timezone.max" value="<?= $jbd->tzMax ?>" /></div>
                    </div>
                </div>
            </div>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <i class="bi bi-info-circle admin-info-icon" title="You can connect any website to YellowTDS using &lt;script src='https://yourwebsite.com/js/index.php'&gt;&lt;/script&gt;"></i>
                            Javascript Connect Action:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->black->jsconnectAction === 'replace' ? 'checked' : '' ?> value="replace" name="black.jsconnect" /> Content replace</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->black->jsconnectAction === 'iframe' ? 'checked' : '' ?> value="iframe" name="black.jsconnect" /> IFrame</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->black->jsconnectAction === 'redirect' ? 'checked' : '' ?> value="redirect" name="black.jsconnect" /> Redirect</label>
                        </div>
                    </div>
                </div>
            </div>
            </section>

            <?php foreach ($c->black->flows as $fi => $flow) { ?>
            <section id="sec-flow-<?= $fi ?>" class="camp-section flow-section" data-flow-index="<?= $fi ?>">
            <h5 class="flow-section-title"><?= htmlspecialchars($flow->name) ?></h5>

            <div class="flow-group">
            <span class="flow-group-title">Flow Filters</span>
            <div class="form-group-inner">
                <div class="row">
                    <div id="flow-filters-<?= $fi ?>"></div>
                </div>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Distribution</span>
            <div class="form-group-inner">
                <select class="form-select flow-dist" data-fi="<?= $fi ?>">
                    <option value="equal" <?= $flow->distribution === 'equal' ? 'selected' : '' ?>>Equal</option>
                    <option value="weighted" <?= $flow->distribution === 'weighted' ? 'selected' : '' ?>>Weighted</option>
                    <option value="thompson" <?= $flow->distribution === 'thompson' ? 'selected' : '' ?>>Thompson Sampling</option>
                </select>
            </div>
            <div class="flow-thompson-opts" id="flow-thompson-opts-<?= $fi ?>" style="display:<?= $flow->distribution === 'thompson' ? 'block' : 'none' ?>">
                <div class="form-group-inner">
                    <label class="login2 pull-left pull-left-pro">Optimize for:</label>
                    <div class="ywb-radios">
                        <label class="ywb-radio-label"><input type="radio" <?= $flow->optimize_for === 'Lead' ? 'checked' : '' ?> value="Lead" name="flow_<?= $fi ?>_optimize_for" class="flow-optimize-for" data-fi="<?= $fi ?>" /> Lead</label>
                        <label class="ywb-radio-label"><input type="radio" <?= $flow->optimize_for === 'Purchase' ? 'checked' : '' ?> value="Purchase" name="flow_<?= $fi ?>_optimize_for" class="flow-optimize-for" data-fi="<?= $fi ?>" /> Purchase</label>
                    </div>
                </div>
                <div class="form-group-inner flow-optimize-mode-wrap" id="flow-optimize-mode-wrap-<?= $fi ?>" style="display:<?= $flow->hasMultipleSteps() ? 'block' : 'none' ?>">
                    <label class="login2 pull-left pull-left-pro">Optimize mode:</label>
                    <div class="ywb-radios">
                        <label class="ywb-radio-label"><input type="radio" <?= $flow->optimize_mode === 'funnels' ? 'checked' : '' ?> value="funnels" name="flow_<?= $fi ?>_optimize_mode" class="flow-optimize-mode" data-fi="<?= $fi ?>" /> Funnels (step combos)</label>
                        <label class="ywb-radio-label"><input type="radio" <?= $flow->optimize_mode === 'separate' ? 'checked' : '' ?> value="separate" name="flow_<?= $fi ?>_optimize_mode" class="flow-optimize-mode" data-fi="<?= $fi ?>" /> Separate (independent per step)</label>
                    </div>
                </div>
                <?php
                // ── Win Probability computation ──
                if ($flow->distribution === 'thompson') {
                    $totalImp = 0;
                    $isFunnel = $flow->hasMultipleSteps() && $flow->optimize_mode === 'funnels';

                    if ($isFunnel) {
                        $stats = $db->get_funnel_stats($campId, $flow->name, $flow->optimize_for);
                        $statsMap = [];
                        foreach ($stats as $row) {
                            $pathArr = json_decode($row['path'], true);
                            $key = is_array($pathArr) ? implode(' → ', $pathArr) : $row['path'];
                            $statsMap[$key] = ['imp' => (int)$row['impressions'], 'conv' => (int)$row['conversions']];
                            $totalImp += (int)$row['impressions'];
                        }
                        $winProbData = AbTest::compute_win_probabilities($statsMap);
                    } else {
                        // Separate mode: per-step probabilities
                        $stepWinProbs = [];
                        foreach ($flow->steps as $si => $step) {
                            $curItems = $step->getItems();
                            if (count($curItems) < 2) continue;
                            $sStats = $db->get_variant_stats($campId, $flow->name, $si, $flow->optimize_for);
                            $sMap = [];
                            foreach ($sStats as $row) {
                                if (!in_array($row['variant'], $curItems, true)) continue;
                                $sMap[$row['variant']] = ['imp' => (int)$row['impressions'], 'conv' => (int)$row['conversions']];
                                $totalImp += (int)$row['impressions'];
                            }
                            $probs = AbTest::compute_win_probabilities($sMap);
                            if (!empty($probs)) $stepWinProbs[$si] = $probs;
                        }
                    }
                ?>
                <div class="flow-winprob">
                    <?php if ($totalImp < 10) { ?>
                        <div class="winprob-empty">Not enough data yet (<?= $totalImp ?> impressions)</div>
                    <?php } elseif ($isFunnel && !empty($winProbData)) { ?>
                        <div class="winprob-section-title">Funnel Win Probability</div>
                        <?php $isFirst = true; foreach ($winProbData as $variant => $prob) { ?>
                        <div class="winprob-row <?= $isFirst ? 'winprob-leader' : '' ?>">
                            <span class="winprob-name"><?= htmlspecialchars($variant) ?></span>
                            <div class="winprob-bar-wrap">
                                <div class="winprob-bar" style="width: <?= max($prob, 2) ?>%"></div>
                            </div>
                            <span class="winprob-pct"><?= $prob ?>%</span>
                        </div>
                        <?php $isFirst = false; } ?>
                    <?php } elseif (!$isFunnel && !empty($stepWinProbs)) { ?>
                        <?php foreach ($stepWinProbs as $si => $probs) { ?>
                        <div class="winprob-section-title">Step <?= $si + 1 ?> Win Probability</div>
                        <?php $isFirst = true; foreach ($probs as $variant => $prob) { ?>
                        <div class="winprob-row <?= $isFirst ? 'winprob-leader' : '' ?>">
                            <span class="winprob-name"><?= htmlspecialchars($variant) ?></span>
                            <div class="winprob-bar-wrap">
                                <div class="winprob-bar" style="width: <?= max($prob, 2) ?>%"></div>
                            </div>
                            <span class="winprob-pct"><?= $prob ?>%</span>
                        </div>
                        <?php $isFirst = false; } ?>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="winprob-empty">No variant data collected yet</div>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
            </div>

            <?php $hasRedirect = false; foreach ($flow->steps as $s) { if ($s->action === 'redirect') { $hasRedirect = true; break; } } ?>
            <div class="flow-group">
            <span class="flow-group-title">Steps</span>
            <div id="steps-list-<?= $fi ?>" class="steps-list">
                <?php $lastSi = count($flow->steps) - 1; foreach ($flow->steps as $si => $step) { $isStepRedirect = ($step->action === 'redirect'); ?>
                <div class="step-list-row" data-flow-index="<?= $fi ?>" data-step-index="<?= $si ?>">
                    <button type="button" class="reorder-handle step-drag-handle<?= $isStepRedirect ? ' is-disabled' : '' ?>" title="<?= $isStepRedirect ? 'Redirect must remain the last step' : 'Drag to reorder' ?>" aria-label="Reorder Step <?= $si + 1 ?>. Drag or use the arrow keys."<?= $isStepRedirect ? ' aria-disabled="true"' : '' ?>><i class="bi bi-grip-vertical" aria-hidden="true"></i></button>
                    <span class="step-list-label">Step <?= $si + 1 ?></span>
                    <span class="step-list-info"><?php
                        if ($step->action === 'redirect' && !empty($step->redirectUrls)) {
                            $hosts = array_map(function($ru) {
                                $u = is_array($ru) ? ($ru['url'] ?? '') : $ru;
                                try { $h = parse_url($u, PHP_URL_HOST); return $h ? preg_replace('/^www\./', '', $h) : 'redirect'; } catch (\Exception $e) { return 'redirect'; }
                            }, $step->redirectUrls);
                            echo htmlspecialchars(implode(', ', $hosts));
                        } elseif ($step->action === 'redirect') {
                            echo 'redirect';
                        } else {
                            echo count($step->folderNames) ? htmlspecialchars(implode(', ', $step->folderNames)) : 'empty';
                        }
                    ?></span>
                    <a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn flow-remove-step" title="Delete"><i class="bi bi-trash"></i></a>
                </div>
                <?php } ?>
            </div>
            <div style="margin-top:10px;">
                <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn flow-add-step" data-fi="<?= $fi ?>"<?= $hasRedirect ? ' style="pointer-events:none;opacity:0.5" aria-disabled="true"' : '' ?>><i class="bi bi-plus-circle"></i> Add Step</a>
            </div>
            </div>
            </section>

            <?php $lastSi = count($flow->steps) - 1; foreach ($flow->steps as $si => $step) { $isLast = ($si === $lastSi); ?>
            <section id="sec-step-<?= $fi ?>-<?= $si ?>" class="camp-section step-section" data-flow-index="<?= $fi ?>" data-step-index="<?= $si ?>">
            <h5 class="flow-section-title"><?= htmlspecialchars($flow->name) ?> &rsaquo; Step <?= $si + 1 ?></h5>

            <div class="flow-group">
            <span class="flow-group-title">Action</span>
            <div class="form-group-inner">
                <div class="ywb-radios">
                    <label class="ywb-radio-label">
                        <input type="radio" <?= ($step->action === 'folder' || !$isLast) ? 'checked' : '' ?> value="folder" name="flow_<?= $fi ?>_step_<?= $si ?>_action" class="flow-step-action" data-fi="<?= $fi ?>" data-si="<?= $si ?>" <?= !$isLast ? 'disabled' : '' ?> /> Local page(s) from folder
                    </label>
                    <label class="ywb-radio-label">
                        <input type="radio" <?= ($step->action === 'redirect' && $isLast) ? 'checked' : '' ?> value="redirect" name="flow_<?= $fi ?>_step_<?= $si ?>_action" class="flow-step-action" data-fi="<?= $fi ?>" data-si="<?= $si ?>" <?= !$isLast ? 'disabled' : '' ?> /> Redirect(s)
                    </label>
                </div>
                <?php if (!$isLast) { ?>
                <p class="step-action-hint" style="font-size:12px;margin-top:6px;">Only the last step can use redirects.</p>
                <?php } ?>
            </div>
            </div>

            <div class="flow-step-folders" style="display:<?= $step->action === 'folder' ? 'block' : 'none' ?>">
            <div class="flow-group">
            <span class="flow-group-title">Folders</span>
                <div class="flow-step-folder-items">
                <?php foreach ($step->folderNames as $ii => $fn) { ?>
                    <div class="form-group-inner flow-path-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Folder:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control flow-step-folder" value="<?= htmlspecialchars($fn) ?>" placeholder="folder1" readonly /></div>
                            <div class="col-lg-2 flow-weight-col" style="display:<?= $flow->distribution === 'weighted' ? 'block' : 'none' ?>">
                                <input type="number" step="1" class="form-control flow-step-weight" value="<?= $step->weights[$ii] ?? '' ?>" placeholder="%" style="width:70px" />
                            </div>
                            <div class="col-lg-3"><div class="btn-group campaign-icon-group"><a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn flow-step-mode" data-mode="<?= $step->isDirectLoad($fn) ? 'direct' : 'base' ?>" data-modes="base,direct" title="Loading mode"><i class="bi <?= $step->isDirectLoad($fn) ? 'bi-hdd-network' : 'bi-house-door' ?>"></i></a><a href="javascript:void(0)" class="btn btn-warning flow-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a><a href="javascript:void(0)" class="btn btn-danger flow-remove-step-item" title="Delete"><i class="bi bi-trash"></i></a></div></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn flow-step-add-existing" data-fi="<?= $fi ?>" data-si="<?= $si ?>"><i class="bi bi-folder-symlink"></i> Add Existing</a>
                <a href="javascript:void(0)" class="btn btn-info campaign-action-btn flow-step-upload-zip" data-fi="<?= $fi ?>" data-si="<?= $si ?>"><i class="bi bi-upload"></i> Upload ZIP</a>
            </div>
            </div>

            <div class="flow-step-redirects" style="display:<?= $step->action === 'redirect' ? 'block' : 'none' ?>">
            <div class="flow-group">
            <span class="flow-group-title">Redirects</span>
                <div class="flow-step-redirect-items">
                <?php foreach ($step->redirectUrls as $ri => $ru) { ?>
                    <div class="form-group-inner flow-path-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect URL:</label></div>
                            <div class="col-lg-4"><input type="text" class="form-control flow-step-redirect" value="<?= htmlspecialchars(is_array($ru) ? $ru['url'] : $ru) ?>" placeholder="https://..." /></div>
                            <div class="col-lg-2 flow-weight-col" style="display:<?= $flow->distribution === 'weighted' ? 'block' : 'none' ?>">
                                <input type="number" step="1" class="form-control flow-step-weight" value="<?= $step->weights[$ri] ?? '' ?>" placeholder="%" style="width:70px" />
                            </div>
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn flow-remove-step-item" title="Delete"><i class="bi bi-trash"></i></a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn flow-step-add-redirect" data-fi="<?= $fi ?>" data-si="<?= $si ?>">+ Add Redirect</a>
                <div class="form-group-inner" style="margin-top:10px">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect type:</label></div>
                        <div class="col-lg-3">
                            <select class="form-select flow-step-redirect-type" data-fi="<?= $fi ?>" data-si="<?= $si ?>">
                                <?php foreach ([301,302,303,307] as $rt) { ?>
                                <option value="<?= $rt ?>" <?= $step->redirectType === $rt ? 'selected' : '' ?>><?= $rt ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </section>
            <?php } ?>
            <?php } ?>

            <section id="sec-scripts" class="camp-section">
            <div class="flow-group">
            <span class="flow-group-title">Backfix</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> 
                            <i class="bi bi-info-circle admin-info-icon" title="Backfix is a script that will prevent the user from going back from out site. Instead the user fill be shown another money page that you'll choose."></i>
                            Should we use backfix?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->backfix === false ? 'checked' : '' ?> value="false" name="scripts.backfix.use" onclick="(document.getElementById('b_backfix').style.display = 'none')" /> No</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->backfix ? 'checked' : '' ?> value="true" name="scripts.backfix.use" onclick="(document.getElementById('b_backfix').style.display = 'block')" /> Yes</label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_backfix" style="display:<?= $c->scripts->backfix? 'block' : 'none' ?>;">
                <div id="backfix_urls_container">
                    <?php
                    $bfCount = max(count($c->scripts->backfixUrls), 1);
                    for ($i = 0; $i < $bfCount; $i++) {
                            $bu = $c->scripts->backfixUrls[$i] ?? '';
                    ?>
                    <div class="form-group-inner backfix-url-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">Backfix URL:</label>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="http://ya.ru?pixel={px}&clickid={clickid}" value="<?= $bu ?>" name="scripts.backfix.urls[<?= $i ?>]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-backfix-url-item btn btn-danger campaign-icon-btn" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-backfix-url-item" class="btn btn-primary" href="javascript:;">+ Add Backfix URL</a>
            </div>
            </div>
            <?php
                $scriptFlowNames = array_map(fn($flow) => $flow->name, $c->black->flows);
                $scriptFlowStepCounts = [];
                foreach ($c->black->flows as $flow) {
                    $scriptFlowStepCounts[$flow->name] = count($flow->steps);
                }
                $scriptMaxStepCount = empty($scriptFlowStepCounts) ? 0 : max($scriptFlowStepCounts);
                $scriptFlowOptionsHtml = '<option value="*">Any flow</option>';
                foreach ($scriptFlowNames as $flowName) {
                    $scriptFlowOptionsHtml .= '<option value="' . htmlspecialchars($flowName, ENT_QUOTES) . '">' . htmlspecialchars($flowName) . '</option>';
                }
                $nextRedirectRules = $c->scripts->nextRedirectRules;
                $submitRedirectRules = $c->scripts->submitRedirectRules;
                if ($c->scripts->nextRedirectUse && empty($nextRedirectRules)) {
                    $nextRedirectRules[] = ['flow' => '*', 'steps' => '*', 'url' => ''];
                }
                if ($c->scripts->submitRedirectUse && empty($submitRedirectRules)) {
                    $submitRedirectRules[] = ['flow' => '*', 'steps' => '*', 'url' => ''];
                }
            ?>

            <div class="flow-group">
            <span class="flow-group-title">Next Step Redirect</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro script-toggle-label">
                            <i class="bi bi-info-circle script-info-icon" title="If a rule matches the current flow and step, the next step opens in a new tab and the current tab is redirected to the rule URL."></i>
                            Enable Next Step Redirect?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->nextRedirectUse === false ? 'checked' : '' ?> value="false" name="scripts.nextredirect.use" data-toggle-target="next_redirect_rules_block" /> No</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->nextRedirectUse === true ? 'checked' : '' ?> value="true" name="scripts.nextredirect.use" data-toggle-target="next_redirect_rules_block" /> Yes</label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="next_redirect_rules_block" style="display:<?= $c->scripts->nextRedirectUse ? 'block' : 'none' ?>;">
                <div id="next_redirect_rules_container" class="script-rules-container">
                    <?php foreach ($nextRedirectRules as $ri => $rule) {
                        $ruleFlow = (string)($rule['flow'] ?? '*');
                        $ruleSteps = $rule['steps'] ?? '*';
                        $ruleStepsValue = $ruleSteps === '*' ? '*' : implode(',', $ruleSteps);
                        $ruleUrl = (string)($rule['url'] ?? '');
                    ?>
                    <div class="form-group-inner script-rule-item" data-rule-kind="next">
                        <div class="row script-rule-header-row">
                            <div class="col-lg-2"><label class="login2 pull-left pull-left-pro">Flow</label></div>
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Steps</label></div>
                            <div class="col-lg-6"><label class="login2 pull-left pull-left-pro">Redirect current tab to</label></div>
                        </div>
                        <div class="row script-rule-body-row">
                            <div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
                                <select class="form-select script-rule-flow" data-rule-kind="next" name="scripts.nextredirect.rules[<?= $ri ?>][flow]">
                                    <?= str_replace('value="' . htmlspecialchars($ruleFlow, ENT_QUOTES) . '"', 'value="' . htmlspecialchars($ruleFlow, ENT_QUOTES) . '" selected', $scriptFlowOptionsHtml) ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-4 col-sm-12 col-xs-12">
                                <input type="hidden" class="script-rule-steps-value" data-rule-kind="next" name="scripts.nextredirect.rules[<?= $ri ?>][steps]" value="<?= htmlspecialchars($ruleStepsValue, ENT_QUOTES) ?>" />
                                <div class="script-step-chips" data-rule-kind="next"></div>
                            </div>
                            <div class="col-lg-6 col-md-3 col-sm-12 col-xs-12">
                                <input type="text" class="form-control" name="scripts.nextredirect.rules[<?= $ri ?>][url]" value="<?= htmlspecialchars($ruleUrl, ENT_QUOTES) ?>" placeholder="https://example.com/path?clickid={clickid}" />
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12 script-rule-remove-col">
                                <button type="button" class="btn btn-outline-light campaign-icon-btn script-rule-move-up" title="Move up"><i class="bi bi-arrow-up"></i></button>
                                <button type="button" class="btn btn-outline-light campaign-icon-btn script-rule-move-down" title="Move down"><i class="bi bi-arrow-down"></i></button>
                                <a href="javascript:void(0)" class="remove-script-rule-item btn btn-danger campaign-icon-btn" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-next-redirect-rule" class="btn btn-primary" href="javascript:;">+ Add Rule</a>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Form Submit Redirect</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro script-toggle-label">
                            <i class="bi bi-info-circle script-info-icon" title="If a rule matches the current flow and terminal step, form submit opens in a new tab and the current tab is redirected to the rule URL."></i>
                            Enable Form Submit Redirect?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->submitRedirectUse === false ? 'checked' : '' ?> value="false" name="scripts.submitredirect.use" data-toggle-target="submit_redirect_rules_block" /> No</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->submitRedirectUse === true ? 'checked' : '' ?> value="true" name="scripts.submitredirect.use" data-toggle-target="submit_redirect_rules_block" /> Yes</label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="submit_redirect_rules_block" style="display:<?= $c->scripts->submitRedirectUse ? 'block' : 'none' ?>;">
                <div id="submit_redirect_rules_container" class="script-rules-container">
                    <?php foreach ($submitRedirectRules as $ri => $rule) {
                        $ruleFlow = (string)($rule['flow'] ?? '*');
                        $ruleSteps = $rule['steps'] ?? '*';
                        $ruleStepsValue = $ruleSteps === '*' ? '*' : implode(',', $ruleSteps);
                        $ruleUrl = (string)($rule['url'] ?? '');
                    ?>
                    <div class="form-group-inner script-rule-item" data-rule-kind="submit">
                    <div class="row script-rule-header-row">
                        <div class="col-lg-2"><label class="login2 pull-left pull-left-pro">Flow</label></div>
                        <div class="col-lg-8"><label class="login2 pull-left pull-left-pro">Redirect current tab to</label></div>
                    </div>
                    <div class="row script-rule-body-row">
                        <div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
                            <select class="form-select script-rule-flow" data-rule-kind="submit" name="scripts.submitredirect.rules[<?= $ri ?>][flow]">
                                <?= str_replace('value="' . htmlspecialchars($ruleFlow, ENT_QUOTES) . '"', 'value="' . htmlspecialchars($ruleFlow, ENT_QUOTES) . '" selected', $scriptFlowOptionsHtml) ?>
                            </select>
                        </div>
                            <input type="hidden" class="script-rule-steps-value" data-rule-kind="submit" name="scripts.submitredirect.rules[<?= $ri ?>][steps]" value="*" />
                            <div class="col-lg-8 col-md-7 col-sm-12 col-xs-12">
                                <input type="text" class="form-control" name="scripts.submitredirect.rules[<?= $ri ?>][url]" value="<?= htmlspecialchars($ruleUrl, ENT_QUOTES) ?>" placeholder="https://example.com/path?clickid={clickid}" />
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12 script-rule-remove-col">
                                <button type="button" class="btn btn-outline-light campaign-icon-btn script-rule-move-up" title="Move up"><i class="bi bi-arrow-up"></i></button>
                                <button type="button" class="btn btn-outline-light campaign-icon-btn script-rule-move-down" title="Move down"><i class="bi bi-arrow-down"></i></button>
                                <a href="javascript:void(0)" class="remove-script-rule-item btn btn-danger campaign-icon-btn" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-submit-redirect-rule" class="btn btn-primary" href="javascript:;">+ Add Rule</a>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Event Tracking</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Track scroll depth?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->scrollTrackingUse === false ? 'checked' : '' ?> value="false" name="scripts.events.scroll.use" data-toggle-target="scroll_tracking_block" /> No</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->scrollTrackingUse === true ? 'checked' : '' ?> value="true" name="scripts.events.scroll.use" data-toggle-target="scroll_tracking_block" /> Yes</label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="scroll_tracking_block" style="display:<?= $c->scripts->scrollTrackingUse ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Scroll thresholds, %</label>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <input type="text" class="form-control" name="scripts.events.scroll.thresholds" value="<?= htmlspecialchars(implode(',', $c->scripts->scrollTrackingThresholds), ENT_QUOTES) ?>" placeholder="50,75,90" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Track visible time on page?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->timeTrackingUse === false ? 'checked' : '' ?> value="false" name="scripts.events.time.use" data-toggle-target="time_tracking_block" /> No</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->timeTrackingUse === true ? 'checked' : '' ?> value="true" name="scripts.events.time.use" data-toggle-target="time_tracking_block" /> Yes</label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="time_tracking_block" style="display:<?= $c->scripts->timeTrackingUse ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Time thresholds, seconds</label>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <input type="text" class="form-control" name="scripts.events.time.thresholds" value="<?= htmlspecialchars(implode(',', $c->scripts->timeTrackingThresholds), ENT_QUOTES) ?>" placeholder="30,60,120" />
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <template id="script-rule-template-next">
                <div class="form-group-inner script-rule-item" data-rule-kind="__KIND__">
                    <div class="row script-rule-header-row">
                        <div class="col-lg-2"><label class="login2 pull-left pull-left-pro">Flow</label></div>
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Steps</label></div>
                        <div class="col-lg-6"><label class="login2 pull-left pull-left-pro">Redirect current tab to</label></div>
                    </div>
                    <div class="row script-rule-body-row">
                        <div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
                            <select class="form-select script-rule-flow" data-rule-kind="__KIND__" name="scripts.__FIELD__.rules[__INDEX__][flow]">
                                <?= $scriptFlowOptionsHtml ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-4 col-sm-12 col-xs-12">
                            <input type="hidden" class="script-rule-steps-value" data-rule-kind="__KIND__" name="scripts.__FIELD__.rules[__INDEX__][steps]" value="*" />
                            <div class="script-step-chips" data-rule-kind="__KIND__"></div>
                        </div>
                        <div class="col-lg-6 col-md-3 col-sm-12 col-xs-12">
                            <input type="text" class="form-control" name="scripts.__FIELD__.rules[__INDEX__][url]" value="" placeholder="https://example.com/path?clickid={clickid}" />
                        </div>
                        <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12 script-rule-remove-col">
                            <button type="button" class="btn btn-outline-light campaign-icon-btn script-rule-move-up" title="Move up"><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-outline-light campaign-icon-btn script-rule-move-down" title="Move down"><i class="bi bi-arrow-down"></i></button>
                            <a href="javascript:void(0)" class="remove-script-rule-item btn btn-danger campaign-icon-btn" title="Delete"><i class="bi bi-trash"></i></a>
                        </div>
                    </div>
                </div>
            </template>
            <template id="script-rule-template-submit">
                <div class="form-group-inner script-rule-item" data-rule-kind="submit">
                    <div class="row script-rule-header-row">
                        <div class="col-lg-2"><label class="login2 pull-left pull-left-pro">Flow</label></div>
                        <div class="col-lg-8"><label class="login2 pull-left pull-left-pro">Redirect current tab to</label></div>
                    </div>
                    <div class="row script-rule-body-row">
                        <div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
                            <select class="form-select script-rule-flow" data-rule-kind="submit" name="scripts.submitredirect.rules[__INDEX__][flow]">
                                <?= $scriptFlowOptionsHtml ?>
                            </select>
                        </div>
                        <input type="hidden" class="script-rule-steps-value" data-rule-kind="submit" name="scripts.submitredirect.rules[__INDEX__][steps]" value="*" />
                        <div class="col-lg-8 col-md-7 col-sm-12 col-xs-12">
                            <input type="text" class="form-control" name="scripts.submitredirect.rules[__INDEX__][url]" value="" placeholder="https://example.com/path?clickid={clickid}" />
                        </div>
                        <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12 script-rule-remove-col">
                            <button type="button" class="btn btn-outline-light campaign-icon-btn script-rule-move-up" title="Move up"><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-outline-light campaign-icon-btn script-rule-move-down" title="Move down"><i class="bi bi-arrow-down"></i></button>
                            <a href="javascript:void(0)" class="remove-script-rule-item btn btn-danger campaign-icon-btn" title="Delete"><i class="bi bi-trash"></i></a>
                        </div>
                    </div>
                </div>
            </template>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Use lazy loading for images?
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="ywb-radios">
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->imagesLazyLoad === false ? 'checked' : '' ?> value="false" name="scripts.imageslazyload" /> No</label>
                            <label class="ywb-radio-label"><input type="radio" <?= $c->scripts->imagesLazyLoad === true ? 'checked' : '' ?> value="true" name="scripts.imageslazyload" /> Yes</label>
                        </div>
                    </div>
                </div>
            </div>
            </section>

            <section id="sec-postbacks" class="camp-section">
            <div class="flow-group">
            <span class="flow-group-title">Postback</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                        <i class="bi bi-info-circle admin-info-icon" title="Put it into your Affiliate Network's postback URL. Change macros names if needed. Subid, payout and status parameters are required. Currency is optional, will be USD if omitted."></i>
                        Your postback URL example:
                    </label>
                    </div>
                    <div class="col-lg-7 col-md-7 col-sm-7 col-xs-12">
                        <div class="input-group custom-go-button">
                            <?php $tdsRoot = rtrim(get_tds_path(), '/'); ?>
                            <input type="text" readonly class="form-control" value="<?= $tdsRoot ?>/api/postback.php?clickid={sub1}&payout={payout}&currency=USD&status={status}"/>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            Here you need to write lead statuses in the format that
                            you get them from Affiliate Network's postback:
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Lead</label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" name="postback.events.lead" class="form-control" placeholder="Lead" value="<?= $c->postback->leadStatusName ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Purchase</label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" name="postback.events.purchase" class="form-control" placeholder="Purchase" value="<?= $c->postback->purchaseStatusName ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Reject</label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" name="postback.events.reject" class="form-control" placeholder="Reject" value="<?= $c->postback->rejectStatusName ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Trash</label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" name="postback.events.trash" class="form-control" placeholder="Trash" value="<?= $c->postback->trashStatusName ?>" />
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">S2S</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> S2S-postbacks settings:</label>
                        <br />
                    </div>
                </div>

                <div id="s2s_container">
                    <?php 
                    for ($i = 0; $i < count($c->postback->s2sPostbacks); $i++) { 
                        $s2sUrl = $c->postback->s2sPostbacks[$i]->url;
                        $s2sMethod = $c->postback->s2sPostbacks[$i]->method;
                        $s2sEvents = $c->postback->s2sPostbacks[$i]->events;
                    ?>
                    <div class="form-group-inner s2s">
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro">
                                    <i class="bi bi-info-circle admin-info-icon" title="Inside the S2S-postback address you can use the following macros: {clickid}, {userid}, {px}, {domain}, {status}"></i>
                                    Address:
                                </label>
                                <br /><br />
                            </div>
                            <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="https://s2s-postback.com" value="<?= $s2sUrl ?>" name="postback.s2s[<?= $i ?>][url]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a class="remove-s2s-item btn btn-danger campaign-icon-btn" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro"> S2S-Postback send method:
                                </label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <select class="form-select" name="postback.s2s[<?= $i ?>][method]">
                                    <option value="GET" <?= ($s2sMethod === "GET" ? ' selected' : '') ?>> GET
                                    </option>
                                    <option value="POST" <?= ($s2sMethod === "POST" ? ' selected' : '') ?>> POST
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro"> Events for which S2S-postback will be sent:
                                </label>
                            </div>
                            <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                                <br />
                                <br/>
                                <?php
                                $statuses = ['Lead','Purchase','Reject','Trash'];
                                foreach ($statuses as $status)
                                {?>
                                    <div class="form-check form-switch">
                                        <label for="<?=$status?><?=$i?>" class="form-check-label"><?=$status?></label>
                                        <input id="<?=$status?><?=$i?>" type="checkbox" class="form-check-input" name="postback.s2s[<?= $i ?>][events][]" value="<?=$status?>" <?= (in_array($status, $s2sEvents) ? ' checked' : '') ?> />
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-s2s-item" class="btn btn-primary">+ Add</a>
            </div>
            </div>
            </section>

            <section id="sec-api" class="camp-section">
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                        <i class="bi bi-info-circle admin-info-icon" title="API methods are described in docs"></i>
                        This campaign's API URL:
                    </label>
                    </div>
                    <div class="col-lg-7 col-md-7 col-sm-7 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" readonly class="form-control" value="<?= $tdsRoot ?>/api/phpconnect.php?apikey=<?= $c->apiKey ?>"/>
                        </div>
                    </div>
                </div>
            </div>

            </section>

            <div class="camp-save-bar">
                <button class="btn btn-lg btn-primary" type="submit" id="save-settings-btn">
                    <strong>Save settings</strong>
                </button>
            </div>
            <div id="save-settings-overlay" class="save-settings-overlay" aria-hidden="true">
                <div class="save-settings-overlay-card">
                    <div class="save-settings-overlay-spinner"></div>
                    <div class="save-settings-overlay-text">Saving settings...</div>
                </div>
            </div>
            <div id="save-settings-toast" class="save-settings-toast" aria-live="polite" aria-atomic="true"></div>
        </form>
            </div><!-- .camp-content -->
        </div><!-- .camp-layout -->
    </div><!-- .all-content-wrapper -->
    <!--cloneData-->
    <script src="js/cloneData.js"></script>
    <script>
        $('#add-redirect-item').cloneData({
            mainContainerId: 'redirect_container',
            cloneContainer: 'redirect-item',
            removeButtonClass: 'remove-redirect-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });
       
        $('#add-curl-item').cloneData({
            mainContainerId: 'curl_container',
            cloneContainer: 'curl-item',
            removeButtonClass: 'remove-curl-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-errorcode-item').cloneData({
            mainContainerId: 'errorcodes_container',
            cloneContainer: 'errorcode-item',
            removeButtonClass: 'remove-errorcode-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-backfix-url-item').cloneData({
            mainContainerId: 'backfix_urls_container',
            cloneContainer: 'backfix-url-item',
            removeButtonClass: 'remove-backfix-url-item',
            maxLimit: 10,
            minLimit: 0,
            removeConfirm: false
        });

        
        $('#add-sub-item').cloneData({
            mainContainerId: 'subs_container',
            cloneContainer: 'subs',
            removeButtonClass: 'remove-sub-item',
            maxLimit: 10,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-stats-sub-item').cloneData({
            mainContainerId: 'stats_subs_container',
            cloneContainer: 'stats_subs',
            removeButtonClass: 'remove-stats-sub-item',
            maxLimit: 10,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-s2s-item').cloneData({
            mainContainerId: 's2s_container',
            cloneContainer: 's2s',
            removeButtonClass: 'remove-s2s-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });

        window.scriptRedirectFlowStepCounts = <?= json_encode($scriptFlowStepCounts) ?>;
        window.scriptRedirectMaxStepCount = <?= (int)$scriptMaxStepCount ?>;
        function getRuleTemplateHtml(kind, index) {
            const templateId = kind === 'submit' ? 'script-rule-template-submit' : 'script-rule-template-next';
            return document.getElementById(templateId).innerHTML
                .replaceAll('__KIND__', kind)
                .replaceAll('__INDEX__', String(index));
        }

        function updateScriptRuleNames(kind) {
            document.querySelectorAll(`[data-rule-kind="${kind}"]`).forEach((item, index) => {
                item.querySelectorAll('input[name], select[name]').forEach((input) => {
                    input.name = input.name.replace(/rules\[\d+\]/, `rules[${index}]`);
                });
            });
        }

        function parseRuleSteps(value) {
            if (!value || value === '*') return '*';
            const steps = String(value)
                .split(',')
                .map((s) => s.trim())
                .filter((s) => s !== '' && !Number.isNaN(Number(s)))
                .map((s) => Number(s));
            if (steps.length === 0) return '*';
            return [...new Set(steps)].sort((a, b) => a - b);
        }

        function serializeRuleSteps(steps) {
            if (steps === '*' || !Array.isArray(steps) || steps.length === 0) return '*';
            return steps.join(',');
        }

        function renderStepChipsForRule(item) {
            if (!item) return;
            const flowSelect = item.querySelector('.script-rule-flow');
            const hiddenInput = item.querySelector('.script-rule-steps-value');
            const chipsContainer = item.querySelector('.script-step-chips');
            if (!flowSelect || !hiddenInput || !chipsContainer) return;

            const flowName = flowSelect.value;
            const stepCount = flowName === '*'
                ? Number(window.scriptRedirectMaxStepCount || 0)
                : Number(window.scriptRedirectFlowStepCounts[flowName] || 0);
            let selectedSteps = parseRuleSteps(hiddenInput.value);

            if (stepCount <= 0) {
                hiddenInput.value = '*';
                chipsContainer.innerHTML = '<button type="button" class="script-step-chip active" data-steps-any="true">Any step</button>';
                return;
            }

            if (selectedSteps !== '*') {
                selectedSteps = selectedSteps.filter((step) => step >= 0 && step < stepCount);
                if (selectedSteps.length === 0) {
                    selectedSteps = '*';
                }
            }

            let html = '<button type="button" class="script-step-chip' + (selectedSteps === '*' ? ' active' : '') + '" data-steps-any="true">Any</button>';
            for (let step = 0; step < stepCount; step++) {
                const active = selectedSteps !== '*' && selectedSteps.includes(step);
                html += '<button type="button" class="script-step-chip' + (active ? ' active' : '') + '" data-step-value="' + step + '">Step ' + (step + 1) + '</button>';
            }
            chipsContainer.innerHTML = html;
            hiddenInput.value = serializeRuleSteps(selectedSteps);
        }

        function initializeScriptRuleSelects(kind) {
            document.querySelectorAll(`.script-rule-item[data-rule-kind="${kind}"]`).forEach(renderStepChipsForRule);
        }

        function addScriptRule(kind) {
            const container = document.getElementById(`${kind}_redirect_rules_container`);
            if (!container) return;
            const index = container.querySelectorAll('.script-rule-item').length;
            container.insertAdjacentHTML('beforeend', getRuleTemplateHtml(kind, index));
            updateScriptRuleNames(kind);
            initializeScriptRuleSelects(kind);
        }

        function toggleScriptRulesBlock(targetId, enabled) {
            const block = document.getElementById(targetId);
            if (!block) return;
            block.style.display = enabled ? 'block' : 'none';
        }

        function collectRulesForKind(kind) {
            return Array.from(document.querySelectorAll(`.script-rule-item[data-rule-kind="${kind}"]`)).map((item) => {
                const flow = item.querySelector('.script-rule-flow')?.value || '*';
                const stepsInput = item.querySelector('.script-rule-steps-value');
                const stepsRaw = (stepsInput?.value || '').trim();
                const url = (item.querySelector('input[name*="[url]"]')?.value || '').trim();
                if (!url) return null;

                const steps = kind === 'submit' ? '*' : parseRuleSteps(stepsRaw);

                return { flow, steps, url };
            }).filter(Boolean);
        }

        window.collectScriptRedirectRules = function () {
            return {
                next: collectRulesForKind('next'),
                submit: collectRulesForKind('submit'),
            };
        };

        function syncToggleTarget(targetId) {
            const checkedRadio = document.querySelector(`input[data-toggle-target="${targetId}"]:checked`);
            toggleScriptRulesBlock(targetId, checkedRadio?.value === 'true');
        }

        const toggleTargets = new Set();
        document.querySelectorAll('input[data-toggle-target]').forEach((radio) => {
            radio.checked = radio.defaultChecked;
            toggleTargets.add(radio.dataset.toggleTarget);
            radio.addEventListener('change', () => {
                syncToggleTarget(radio.dataset.toggleTarget);
            });
        });
        toggleTargets.forEach(syncToggleTarget);

        document.getElementById('add-next-redirect-rule')?.addEventListener('click', () => addScriptRule('next'));
        document.getElementById('add-submit-redirect-rule')?.addEventListener('click', () => addScriptRule('submit'));

        document.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-script-rule-item');
            if (removeBtn) {
                const ruleItem = removeBtn.closest('.script-rule-item');
                if (!ruleItem) return;
                const kind = ruleItem.dataset.ruleKind;
                ruleItem.remove();
                if (kind) updateScriptRuleNames(kind);
                return;
            }

            const moveUpBtn = e.target.closest('.script-rule-move-up');
            if (moveUpBtn) {
                const ruleItem = moveUpBtn.closest('.script-rule-item');
                const kind = ruleItem?.dataset.ruleKind;
                if (!ruleItem || !kind) return;
                const prev = ruleItem.previousElementSibling;
                if (prev) {
                    ruleItem.parentNode.insertBefore(ruleItem, prev);
                    updateScriptRuleNames(kind);
                }
                return;
            }

            const moveDownBtn = e.target.closest('.script-rule-move-down');
            if (moveDownBtn) {
                const ruleItem = moveDownBtn.closest('.script-rule-item');
                const kind = ruleItem?.dataset.ruleKind;
                if (!ruleItem || !kind) return;
                const next = ruleItem.nextElementSibling;
                if (next) {
                    ruleItem.parentNode.insertBefore(next, ruleItem);
                    updateScriptRuleNames(kind);
                }
                return;
            }

            const chip = e.target.closest('.script-step-chip');
            if (chip) {
                const ruleItem = chip.closest('.script-rule-item');
                const hiddenInput = ruleItem?.querySelector('.script-rule-steps-value');
                if (!ruleItem || !hiddenInput) return;

                let selected = parseRuleSteps(hiddenInput.value);
                const isAny = chip.dataset.stepsAny === 'true';

                if (isAny) {
                    hiddenInput.value = '*';
                    renderStepChipsForRule(ruleItem);
                    return;
                }

                const stepValue = Number(chip.dataset.stepValue);
                if (selected === '*') {
                    selected = [];
                }
                if (!selected.includes(stepValue)) {
                    selected.push(stepValue);
                } else {
                    selected = selected.filter((step) => step !== stepValue);
                }
                hiddenInput.value = serializeRuleSteps(selected);
                renderStepChipsForRule(ruleItem);
                return;
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.matches('.script-rule-flow')) {
                renderStepChipsForRule(e.target.closest('.script-rule-item'));
            }
        });

        initializeScriptRuleSelects('next');
        initializeScriptRuleSelects('submit');
        
    </script>
    <script type="module" src="js/campsettings/load-mode.js"></script>
    <script type="module" src="js/campsettings/white-pages.js"></script>
    <script type="module" src="js/campsettings/domain-specific.js?v=<?= filemtime(__DIR__ . '/js/campsettings/domain-specific.js') ?>"></script>
    <script>window._dwsCounterInit = <?= count($c->domains) ?>;</script>
    <script type="module" src="js/campsettings/dws-sync.js?v=<?= filemtime(__DIR__ . '/js/campsettings/dws-sync.js') ?>"></script>
    <script type="module" src="js/campsettings/domains.js"></script>
    <script type="module" src="js/campsettings/form-submit.js"></script>
    <script src="js/filters.js"></script>
    <script>
        var rules_basic = <?=json_encode($c->white->filters)?>;

        $('#filtersbuilder').queryBuilder({
            operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat(paramOperators),
            filters: tdsFilters,
            <?php
            if (!empty($c->white->filters)) {
                echo 'rules: rules_basic,';
            }
            ?>
        });

        <?php foreach ($c->black->flows as $fi => $flow) { ?>
        var flow_rules_<?= $fi ?> = <?= json_encode($flow->filters) ?>;
        $('#flow-filters-<?= $fi ?>').queryBuilder({
            operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat(paramOperators),
            filters: tdsFilters,
            <?php if (!empty($flow->filters) && isset($flow->filters['rules'])) { ?>
            rules: flow_rules_<?= $fi ?>,
            <?php } ?>
        });
        <?php } ?>

    </script>
    <!-- Folder Picker Modal -->
    <div id="folderPickerModal" class="ywbmodal" style="max-width:420px !important;">
        <div class="fp-modal-content">
            <div class="fp-modal-header"><h5 style="margin:0;font-size:18px;color:#e2e8f0;">Select Folder</h5></div>
            <div class="fp-modal-body">
                <input type="text" id="fp-search" placeholder="Search..." class="fp-search-input">
                <div id="fp-list" class="fp-list-wrap"></div>
                <div id="fp-empty" style="display:none;color:#94a3b8;text-align:center;padding:20px 0;">No folders found. Upload a ZIP first.</div>
            </div>
            <div class="fp-modal-footer">
                <button type="button" class="btn btn-default campaign-action-btn" id="fp-cancel">Cancel</button>
                <button type="button" class="btn btn-info campaign-action-btn" id="fp-ok">OK</button>
            </div>
        </div>
    </div>
    <style>
        #folderPickerModal{background:#151b2d !important;padding:0 !important;border-radius:12px !important;overflow:hidden !important;}
        .fp-modal-content{display:flex;flex-direction:column;max-height:80vh;}
        .fp-modal-header{padding:12px 16px;border-bottom:1px solid #2a3245;}
        .fp-modal-body{padding:12px 16px;flex:1;overflow:hidden;display:flex;flex-direction:column;}
        .fp-search-input{width:100%;padding:6px 12px;background:#1a2235;border:1px solid #2a3245;border-radius:6px;color:#e2e8f0;font-size:14px;margin-bottom:10px;}
        .fp-search-input:focus{outline:none;border-color:#0084ff;}
        .fp-list-wrap{max-height:45vh;overflow-y:auto;}
        .fp-list-wrap::-webkit-scrollbar{width:8px;}
        .fp-list-wrap::-webkit-scrollbar-track{background:#1a2235;border-radius:4px;}
        .fp-list-wrap::-webkit-scrollbar-thumb{background:#2d3748;border-radius:4px;}
        .fp-modal-footer{padding:12px 16px;border-top:1px solid #2a3245;display:flex;justify-content:flex-end;gap:8px;}
        #fp-list label{display:block;padding:8px 12px;margin:0;border-radius:6px;cursor:pointer;color:#e2e8f0;font-size:14px;transition:background .15s}
        #fp-list label:hover{background:#1e2a3f}
        #fp-list input[type=radio]{margin-right:10px;accent-color:#0084ff}
        #fp-list label.fp-selected{background:#1a2a45}
    </style>

    <!-- Load Mode Modal -->
    <div id="loadModeModal" class="ywbmodal" style="max-width:380px !important;">
        <div class="fp-modal-content">
            <div class="fp-modal-header"><h5 style="margin:0;font-size:18px;color:#e2e8f0;">Loading Mode</h5></div>
            <div class="fp-modal-body" id="lm-body"></div>
            <div class="fp-modal-footer">
                <button type="button" class="btn btn-default campaign-action-btn" id="lm-cancel">Cancel</button>
                <button type="button" class="btn btn-info campaign-action-btn" id="lm-ok">OK</button>
            </div>
        </div>
    </div>
    <style>
        #loadModeModal{background:#151b2d !important;padding:0 !important;border-radius:12px !important;overflow:hidden !important;}
        .lm-option{display:block;padding:10px 14px;margin:0 0 4px;border-radius:6px;cursor:pointer;color:#e2e8f0;font-size:14px;transition:background .15s}
        .lm-option:hover{background:#1e2a3f}
        .lm-option input[type=radio]{margin-right:10px;accent-color:#0084ff}
        .lm-option.lm-selected{background:#1a2a45}
        .lm-desc{display:block;margin-left:26px;font-size:12px;color:#94a3b8;margin-top:2px}
    </style>

    <script>window.LANDING_FOLDER = <?= json_encode(get_cache_path('landingFolder')) ?>;window.WHITE_FOLDER = <?= json_encode(get_cache_path('whiteFolder')) ?>;</script>
    <!-- CodeMirror 6 local bundles -->
    <script src="js/cm6/html.min.js"></script>
    <script>window.CM6_HTML = cm6;</script>
    <script src="js/cm6/css.min.js"></script>
    <script>window.CM6_CSS = cm6;</script>
    <script src="js/cm6/javascript.min.js"></script>
    <script>window.CM6_JS = cm6;</script>
    <script src="js/cm6/php.min.js"></script>
    <script>window.CM6_PHP = cm6;</script>
    <script type="module" src="js/fileeditor.js"></script>
    <?php
    $flowModuleVersion = 0;
    foreach (glob(__DIR__ . '/js/flows/*.js') ?: [] as $flowModuleFile) {
        $flowModuleVersion = max($flowModuleVersion, (int) filemtime($flowModuleFile));
    }
    ?>
    <script type="module" src="js/flows/index.js?v=<?= $flowModuleVersion ?>"></script>
    <script type="module" src="js/campsettings-nav.js?v=<?= filemtime(__DIR__ . '/js/campsettings-nav.js') ?>"></script>

    <!-- ── Flow templates (used by js/flows/ modules) ── -->
    <template id="tpl-folder-row">
        <div class="form-group-inner flow-path-item"><div class="row">
            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro" data-role="folder-label">Folder:</label></div>
            <div class="col-lg-3"><input type="text" class="form-control" data-role="folder-input" value="" placeholder="folder" readonly /></div>
            <div class="col-lg-2 flow-weight-col" style="display:none">
                <input type="number" step="1" class="form-control" data-role="weight-input" value="" placeholder="%" style="width:70px" /></div>
            <div class="col-lg-3"><div class="btn-group campaign-icon-group">
                <a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-role="mode-btn" data-mode="base" data-modes="base,direct" title="Loading mode"><i class="bi bi-house-door"></i></a>
                <a href="javascript:void(0)" class="btn btn-warning flow-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a>
                <a href="javascript:void(0)" class="btn btn-danger" data-role="remove-btn" title="Delete"><i class="bi bi-trash"></i></a>
            </div></div>
        </div></div>
    </template>

    <template id="tpl-redirect-row">
        <div class="form-group-inner flow-path-item"><div class="row">
            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect URL:</label></div>
            <div class="col-lg-4"><input type="text" class="form-control flow-step-redirect" value="" placeholder="https://..." /></div>
            <div class="col-lg-2 flow-weight-col" style="display:none">
                <input type="number" step="1" class="form-control flow-step-weight" value="" placeholder="%" style="width:70px" /></div>
            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn flow-remove-step-item" title="Delete"><i class="bi bi-trash"></i></a></div>
        </div></div>
    </template>

    <template id="tpl-flow-section">
        <section id="sec-flow-__FI__" class="camp-section flow-section" data-flow-index="__FI__">
        <h5 class="flow-section-title">__FLOWNAME__</h5>

        <div class="flow-group"><span class="flow-group-title">Flow Filters</span>
        <div class="form-group-inner">
            <div class="row"><div id="flow-filters-__FI__"></div></div>
        </div></div>

        <div class="flow-group"><span class="flow-group-title">Distribution</span>
        <div class="form-group-inner">
            <select class="form-select flow-dist" data-fi="__FI__">
                <option value="equal" selected>Equal</option><option value="weighted">Weighted</option><option value="thompson">Thompson Sampling</option></select></div>

        <div class="flow-thompson-opts" id="flow-thompson-opts-__FI__" style="display:none">
            <div class="form-group-inner"><label class="login2 pull-left pull-left-pro">Optimize for:</label>
            <div class="ywb-radios">
                <label class="ywb-radio-label"><input type="radio" checked value="Lead" name="flow___FI___optimize_for" class="flow-optimize-for" data-fi="__FI__" /> Lead</label>
                <label class="ywb-radio-label"><input type="radio" value="Purchase" name="flow___FI___optimize_for" class="flow-optimize-for" data-fi="__FI__" /> Purchase</label>
            </div></div>
            <div class="form-group-inner flow-optimize-mode-wrap" id="flow-optimize-mode-wrap-__FI__" style="display:none">
            <label class="login2 pull-left pull-left-pro">Optimize mode:</label>
            <div class="ywb-radios">
                <label class="ywb-radio-label"><input type="radio" checked value="funnels" name="flow___FI___optimize_mode" class="flow-optimize-mode" data-fi="__FI__" /> Funnels (step combos)</label>
                <label class="ywb-radio-label"><input type="radio" value="separate" name="flow___FI___optimize_mode" class="flow-optimize-mode" data-fi="__FI__" /> Separate (independent per step)</label>
            </div></div></div></div>

        <div class="flow-group"><span class="flow-group-title">Steps</span>
        <div id="steps-list-__FI__" class="steps-list"></div>
        <div style="margin-top:10px;">
            <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn flow-add-step" data-fi="__FI__"><i class="bi bi-plus-circle"></i> Add Step</a>
        </div>
        </div>

        </section>
    </template>

    <template id="tpl-step-section">
        <section id="sec-step-__FI__-__SI__" class="camp-section step-section" data-flow-index="__FI__" data-step-index="__SI__">
        <h5 class="flow-section-title">__FLOWNAME__ &rsaquo; Step __STEPNUM__</h5>

        <div class="flow-group"><span class="flow-group-title">Action</span>
        <div class="form-group-inner">
            <div class="ywb-radios">
                <label class="ywb-radio-label">
                    <input type="radio" checked value="folder" name="flow___FI___step___SI___action" class="flow-step-action" data-fi="__FI__" data-si="__SI__" /> Local page(s) from folder
                </label>
                <label class="ywb-radio-label">
                    <input type="radio" value="redirect" name="flow___FI___step___SI___action" class="flow-step-action" data-fi="__FI__" data-si="__SI__" /> Redirect(s)
                </label>
            </div>
        </div></div>

        <div class="flow-step-folders" style="display:block">
        <div class="flow-group"><span class="flow-group-title">Folders</span>
            <div class="flow-step-folder-items"></div>
            <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn flow-step-add-existing" data-fi="__FI__" data-si="__SI__"><i class="bi bi-folder-symlink"></i> Add Existing</a>
            <a href="javascript:void(0)" class="btn btn-info campaign-action-btn flow-step-upload-zip" data-fi="__FI__" data-si="__SI__"><i class="bi bi-upload"></i> Upload ZIP</a>
        </div></div>

        <div class="flow-step-redirects" style="display:none">
        <div class="flow-group"><span class="flow-group-title">Redirects</span>
            <div class="flow-step-redirect-items"></div>
            <a href="javascript:void(0)" class="btn btn-primary campaign-action-btn flow-step-add-redirect" data-fi="__FI__" data-si="__SI__">+ Add Redirect</a>
            <div class="form-group-inner" style="margin-top:10px"><div class="row">
                <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect type:</label></div>
                <div class="col-lg-3"><select class="form-select flow-step-redirect-type" data-fi="__FI__" data-si="__SI__">
                    <option value="301">301</option><option value="302" selected>302</option><option value="303">303</option><option value="307">307</option>
                </select></div></div></div>
        </div></div>

        </section>
    </template>

</body>
</html>
