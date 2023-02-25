<?php
require_once __DIR__ . '/initialization.php';
?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>
<body>
<?php include "menu.php" ?>
<div class="all-content-wrapper">
    <?php include "header.php" ?>
    <a id="top"></a>

    <form id="saveconfig">
        <div class="basic-form-area mg-tb-15">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="sparkline12-list">
                            <div class="sparkline12-graph">
                                <div class="basic-login-form-ad">
                                    <div class="row">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <div class="all-form-element-inner">
                                                <h4>#0 Domains</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                <img src="img/info.ico"
                                                                     title="Enter all of your domains (or IP-addresses) WITHOUT HTTP, comma-separated, WITHOUT SPACES! You can use *.xxx.com to match ALL subdomains."/>
                                                                Domains list for this config:
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" class="form-control"
                                                                       placeholder="domain.com" name="domains"
                                                                       value="<?= implode(',', $domain_names) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <h4>#1 Safe page settings</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Choose
                                                                method:</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $white_action === 'folder' ? 'checked' : '' ?>
                                                                                       value="folder"
                                                                                       name="white.action"
                                                                                       onclick="(document.getElementById('b_2').style.display='block'); (document.getElementById('b_3').style.display='none'); (document.getElementById('b_4').style.display='none'); (document.getElementById('b_5').style.display='none')">
                                                                                Local safe page from folder</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $white_action === 'redirect' ? 'checked' : '' ?>
                                                                                       value="redirect"
                                                                                       name="white.action"
                                                                                       onclick="(document.getElementById('b_2').style.display='none'); (document.getElementById('b_3').style.display='block'); (document.getElementById('b_4').style.display='none'); (document.getElementById('b_5').style.display='none')">
                                                                                Redirect </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $white_action === 'curl' ? 'checked' : '' ?>
                                                                                       value="curl" name="white.action"
                                                                                       onclick="(document.getElementById('b_2').style.display='none'); (document.getElementById('b_3').style.display='none'); (document.getElementById('b_4').style.display='block'); (document.getElementById('b_5').style.display='none')">
                                                                                Load a website using CURL
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $white_action === 'error' ? 'checked' : '' ?>
                                                                                       value="error" name="white.action"
                                                                                       onclick="(document.getElementById('b_2').style.display='none'); (document.getElementById('b_3').style.display='none'); (document.getElementById('b_4').style.display='none'); (document.getElementById('b_5').style.display='block')">
                                                                                Return HTTP-code <small>(for example,
                                                                                    404 for NotFound or 200 for
                                                                                    OK)</small>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_2"
                                                     style="display:<?= $white_action === 'folder' ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Safe page
                                                                    folder:</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="white" name="white.folder.names"
                                                                           value="<?= implode(',', $white_folder_names) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_3"
                                                     style="display:<?= $white_action === 'redirect' ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Redirect
                                                                    address:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="https://ya.ru"
                                                                           name="white.redirect.urls"
                                                                           value="<?= implode(',', $white_redirect_urls) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Choose
                                                                    Redirect HTTP-code:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $white_redirect_type === '301' ? 'checked' : '' ?>
                                                                                           value="301"
                                                                                           name="white.redirect.type"/>
                                                                                    301 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $white_redirect_type === '302' ? 'checked' : '' ?>
                                                                                           value="302"
                                                                                           name="white.redirect.type"/>
                                                                                    302 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $white_redirect_type === '303' ? 'checked' : '' ?>
                                                                                           value="303"
                                                                                           name="white.redirect.type"/>
                                                                                    303 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $white_redirect_type === '307' ? 'checked' : '' ?>
                                                                                           value="307"
                                                                                           name="white.redirect.type"/>
                                                                                    307 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="b_4"
                                                     style="display:<?= $white_action === 'curl' ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Url for
                                                                    loading:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="https://ya.ru"
                                                                           name="white.curl.urls"
                                                                           value="<?= implode(',', $white_curl_urls) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="b_5"
                                                     style="display:<?= $white_action === 'error' ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">HTTP-code:</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="404" name="white.error.codes"
                                                                           value="<?= implode(',', $white_error_codes) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Show
                                                                individual
                                                                domain-specific safe page? </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $white_use_domain_specific === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="white.domainfilter.use"
                                                                                       onclick="(document.getElementById('b_6').style.display='none')">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $white_use_domain_specific === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="white.domainfilter.use"
                                                                                       onclick="(document.getElementById('b_6').style.display='block')">
                                                                                Yes</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="b_6"
                                                     style="display:<?= $white_use_domain_specific === true ? 'block' : 'none' ?>;">
                                                    <div id="white_domainspecific">
                                                        <?php for ($j = 0; $j < count($white_domain_specific); $j++) { ?>
                                                            <div class="form-group-inner white">
                                                                <div class="row">
                                                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                        <label class="login2 pull-left pull-left-pro">Domain
                                                                            => Method:Action</label>
                                                                    </div>
                                                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                        <div class="input-group">
                                                                            <input type="text" class="form-control"
                                                                                   placeholder="xxx.yyy.com"
                                                                                   value="<?= $white_domain_specific[$j]["name"] ?>"
                                                                                   name="white.domainfilter.domains[<?= $j ?>][name]">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                        <p>=></p>
                                                                    </div>
                                                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                        <div class="input-group">
                                                                            <input type="text" class="form-control"
                                                                                   placeholder="site:white"
                                                                                   value="<?= $white_domain_specific[$j]["action"] ?>"
                                                                                   name="white.domainfilter.domains[<?= $j ?>][action]">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                        <a href="javascript:void(0)"
                                                                           class="remove-domain-item btn btn-sm btn-primary">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    <a id="add-domain-item" class="btn btn-sm btn-primary"
                                                       href="javascript:;">Add</a>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                <img src="img/info.ico"
                                                                     title="If JS filters are switched ON, then the user will be shown a safe page for a moment and only after all the checks are passed he'll be shown the money page."/>
                                                                Use Javascript filters?
                                                                <small>
                                                                </small>
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $use_js_checks === false ? 'checked="checked"' : '' ?>
                                                                                       value="false"
                                                                                       name="white.jschecks.enabled"
                                                                                       onclick="(document.getElementById('jscheckssettings').style.display = 'none')">
                                                                                No, don't use</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio"
                                                                                       value="true" <?= $use_js_checks === true ? 'checked="checked"' : '' ?>
                                                                                       name="white.jschecks.enabled"
                                                                                       onclick="(document.getElementById('jscheckssettings').style.display = 'block')">
                                                                                Yes, use </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="jscheckssettings"
                                                     style="display:<?= $use_js_checks === true ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">JS-Test
                                                                    timeout (msec): </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="10000"
                                                                           name="white.jschecks.timeout"
                                                                           value="<?= $js_timeout ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">What will
                                                                    be tested? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="checkbox"
                                                                                           name="white.jschecks.events[]"
                                                                                           value="mousemove" <?= in_array('mousemove', $js_checks) ? 'checked' : '' ?>>
                                                                                    Mouse moves</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="checkbox"
                                                                                           name="white.jschecks.events[]"
                                                                                           value="keydown" <?= in_array('keydown', $js_checks) ? 'checked' : '' ?>>
                                                                                    Key presses</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="checkbox"
                                                                                           name="white.jschecks.events[]"
                                                                                           value="scroll" <?= in_array('scroll', $js_checks) ? 'checked' : '' ?>>
                                                                                    Scrolling </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="checkbox"
                                                                                           name="white.jschecks.events[]"
                                                                                           value="devicemotion" <?= in_array('devicemotion', $js_checks) ? 'checked' : '' ?>>
                                                                                    Device motion (Android only)</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="checkbox"
                                                                                           name="white.jschecks.events[]"
                                                                                           value="deviceorientation" <?= in_array('deviceorientation', $js_checks) ? 'checked' : '' ?>>
                                                                                    Device orientation (Android
                                                                                    only)</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="checkbox"
                                                                                           name="white.jschecks.events[]"
                                                                                           value="audiocontext" <?= in_array('audiocontext', $js_checks) ? 'checked' : '' ?>>
                                                                                    Audio engine existence</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input id="tzcheck" type="checkbox"
                                                                                           name="white.jschecks.events[]"
                                                                                           value="timezone" <?= in_array('timezone', $js_checks) ? 'checked' : '' ?>
                                                                                           onchange="(document.getElementById('jscheckstz').style.display = this.checked?'block':'none')">
                                                                                    Time Zone </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="jscheckstz" class="form-group-inner"
                                                         style="display:<?= in_array('timezone', $js_checks) ? 'block' : 'none' ?>;">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Minimum
                                                                    allowed timezone</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="-3"
                                                                           name="white.jschecks.tzstart"
                                                                           value="<?= $js_tzstart ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Maximum
                                                                    allowed timezone</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="3" name="white.jschecks.tzend"
                                                                           value="<?= $js_tzend ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Obfuscate
                                                                    JS-test code?</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio"
                                                                                           value="true" <?= $js_obfuscate === true ? 'checked="checked"' : '' ?>
                                                                                           name="white.jschecks.obfuscate">
                                                                                    Yes </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio"
                                                                                           value="false" <?= $js_obfuscate === false ? 'checked="checked"' : '' ?>
                                                                                           name="white.jschecks.obfuscate">
                                                                                    No</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>
                                                <hr>
                                                <h4>#2 Money page settings</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Choose
                                                                prelanding loading method: </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_preland_action === 'none' ? 'checked' : '' ?>
                                                                                       value="none"
                                                                                       name="black.prelanding.action"
                                                                                       onclick="(document.getElementById('b_8').style.display='none')">
                                                                                Don't use prelanding</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_preland_action === 'folder' ? 'checked' : '' ?>
                                                                                       value="folder"
                                                                                       name="black.prelanding.action"
                                                                                       onclick="(document.getElementById('b_8').style.display='block')">
                                                                                Local prelanding from folder</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div id="b_8"
                                                     style="display:<?= $black_preland_action === 'folder' ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    <img src="img/info.ico"
                                                                         title="If you want to perform an A/B Test then enter several folders comma-separated, WITHOUT SPACES"/>
                                                                    Prelanding folder(s)
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="p1,p2"
                                                                           name="black.prelanding.folders"
                                                                           value="<?= implode(',', $black_preland_folder_names) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Choose landing
                                                                loading method:</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_land_action === 'folder' ? 'checked' : '' ?>
                                                                                       value="folder"
                                                                                       name="black.landing.action"
                                                                                       onclick="(document.getElementById('b_landings_redirect').style.display='none'); (document.getElementById('b_landings_folder').style.display='block')">
                                                                                Local landing from folder</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_land_action === 'redirect' ? 'checked' : '' ?>
                                                                                       value="redirect"
                                                                                       name="black.landing.action"
                                                                                       onclick="(document.getElementById('b_landings_redirect').style.display='block'); (document.getElementById('b_landings_folder').style.display='none')">
                                                                                Redirect </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_landings_folder"
                                                     style="display:<?= $black_land_action === 'folder' ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    <img src="img/info.ico"
                                                                         title="If you want to perform an A/B Test then enter several folders comma-separated, WITHOUT SPACES"/>
                                                                    Landing folder(s)
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="l1,l2"
                                                                           name="black.landing.folder.names"
                                                                           value="<?= implode(',', $black_land_folder_names) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">ThankYou
                                                                    Page Settings</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $black_land_use_custom_thankyou_page === true ? 'checked' : '' ?>
                                                                                           value="true"
                                                                                           name="black.landing.folder.customthankyoupage.use"
                                                                                           onclick="(document.getElementById('ctpage').style.display = 'block'); (document.getElementById('pppage').style.display = 'none')">
                                                                                    Use the Built-in ThankYou Page
                                                                                    <img src="img/info.ico"
                                                                                         title="First try this method! Only if smth doesn't work well - then use your own!"/>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $black_land_use_custom_thankyou_page === false ? 'checked' : '' ?>
                                                                                           value="false"
                                                                                           name="black.landing.folder.customthankyoupage.use"
                                                                                           onclick="(document.getElementById('ctpage').style.display = 'none'); (document.getElementById('pppage').style.display = 'block')">
                                                                                    Use the one from your landing(s)
                                                                                    <img src="img/info.ico"
                                                                                         title="You'll have to manually insert Facebook's/TikTok's or Google's pixels onto your thankyou page then"/>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div id="ctpage" class="form-group-inner"
                                                         style="display:<?= $black_land_use_custom_thankyou_page === true ? 'block' : 'none' ?>">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Built-in
                                                                    ThankYou Page language:</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="EN"
                                                                           name="black.landing.folder.customthankyoupage.language"
                                                                           value="<?= $black_land_thankyou_page_language ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    <img src="img/info.ico" title=""/>
                                                                    Relative path of the php-script that sends leads to
                                                                    the affiliate network:
                                                                    <img src="img/info.ico"
                                                                         title="Usually this file is named order.php, confirm.php or smth like that. Open your landing's index.html and look at the form's action attribute. It is usually written there."/>
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="order.php"
                                                                           name="black.landing.folder.conversions.script"
                                                                           value="<?= $black_land_conversion_script ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    Use upsells on the built-in ThankYou Page?
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $thankyou_upsell === true ? 'checked' : '' ?>
                                                                                           value="true"
                                                                                           name="black.landing.folder.customthankyoupage.upsell.use"
                                                                                           onclick="document.getElementById('thankupsell').style.display = 'block'">
                                                                                    Yes</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $thankyou_upsell === false ? 'checked' : '' ?>
                                                                                           value="false"
                                                                                           name="black.landing.folder.customthankyoupage.upsell.use"
                                                                                           onclick="document.getElementById('thankupsell').style.display = 'none'">No</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="thankupsell" class="form-group-inner"
                                                         style="display:<?= $thankyou_upsell === true ? 'block' : 'none' ?>">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    Upsell's Title:
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="This is a header"
                                                                           name="black.landing.folder.customthankyoupage.upsell.header"
                                                                           value="<?= $thankyou_upsell_header ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    Upsell's text:
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="This is a text"
                                                                           name="black.landing.folder.customthankyoupage.upsell.text"
                                                                           value="<?= $thankyou_upsell_text ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    Upsell's landing url:
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="https://google.com"
                                                                           name="black.landing.folder.customthankyoupage.upsell.url"
                                                                           value="<?= $thankyou_upsell_url ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    <img src="img/info.ico"
                                                                         title="This folder should be created inside 'thankyou/upsell'"/>
                                                                    Folder name for upsell images:
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="img"
                                                                           name="black.landing.folder.customthankyoupage.upsell.imgdir"
                                                                           value="<?= $thankyou_upsell_imgdir ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="pppage" class="form-group-inner"
                                                         style="display:<?= $black_land_use_custom_thankyou_page === false ? 'block' : 'none' ?>">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    Should I count conversions using the Submit Order
                                                                    button on the Landing Page?
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $black_land_log_conversions_on_button_click === false ? 'checked' : '' ?>
                                                                                           value="false"
                                                                                           name="black.landing.folder.conversions.logonbuttonclick">
                                                                                    No
                                                                                    <img src="img/info.ico"
                                                                                         title="If so, the cloaker will not be able to count conversions at all 🤷‍️"/>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $black_land_log_conversions_on_button_click === true ? 'checked' : '' ?>
                                                                                           value="true"
                                                                                           name="black.landing.folder.conversions.logonbuttonclick">
                                                                                    Yes </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    How should we track Facebook's conversions?
                                                                </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <img src="img/info.ico"
                                                                                         title="If you won't use the built-in ThankYou Page then you'll have to insert Facebook Pixel's Code inside your own thankyou page!"/>
                                                                                    <input type="radio" <?= $fb_add_button_pixel === false ? 'checked' : '' ?>
                                                                                           value="false"
                                                                                           name="pixels.fb.conversion.fireonbutton">
                                                                                    Using ThankYou Page
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $fb_add_button_pixel === true ? 'checked' : '' ?>
                                                                                           value="true"
                                                                                           name="pixels.fb.conversion.fireonbutton">
                                                                                    Using Submit Form Button on the
                                                                                    landing
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Откуда
                                                                    отстукивать в TikTok конверсию? </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $tt_add_button_pixel === false ? 'checked' : '' ?>
                                                                                           value="false"
                                                                                           name="pixels.tt.conversion.fireonbutton">
                                                                                    Со страницы спасибо <small>(если не
                                                                                        используете кастомную Спасибо,
                                                                                        впишите код
                                                                                        пикселя!)</small></label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $tt_add_button_pixel === true ? 'checked' : '' ?>
                                                                                           value="true"
                                                                                           name="pixels.tt.conversion.fireonbutton">
                                                                                    С кнопки на ленде </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_landings_redirect"
                                                     style="display:<?= $black_land_action === 'redirect' ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Адреса для
                                                                    редиректа: <small>(Можно НЕСКОЛЬКО через
                                                                        запятую)</small> </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="https://ya.ru,https://google.com"
                                                                           name="black.landing.redirect.urls"
                                                                           value="<?= implode(',', $black_land_redirect_urls) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Выберите
                                                                    код редиректа: </label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                                <div class="bt-df-checkbox pull-left">

                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $black_land_redirect_type === '301' ? 'checked' : '' ?>
                                                                                           value="301"
                                                                                           name="black.landing.redirect.type">
                                                                                    301 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $black_land_redirect_type === '302' ? 'checked' : '' ?>
                                                                                           value="302"
                                                                                           name="black.landing.redirect.type">
                                                                                    302 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $black_land_redirect_type === '303' ? 'checked' : '' ?>
                                                                                           value="303"
                                                                                           name="black.landing.redirect.type">
                                                                                    303 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                            <div class="i-checks pull-left">
                                                                                <label>
                                                                                    <input type="radio" <?= $black_land_redirect_type === '307' ? 'checked' : '' ?>
                                                                                           value="307"
                                                                                           name="black.landing.redirect.type">
                                                                                    307 </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Действие при
                                                                подключении кло через Javascript (для конструкторов)
                                                                <small>Если в качестве блэка выбран редирект, то и с
                                                                    конструктора ВСЕГДА будет редирект. Если же блэк
                                                                    локальный, то возможны только: подмена,
                                                                    iframe</small> </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_jsconnect_action === 'redirect' ? 'checked' : '' ?>
                                                                                       value="redirect"
                                                                                       name="black.jsconnect"> Редирект
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_jsconnect_action === 'replace' ? 'checked' : '' ?>
                                                                                       value="replace"
                                                                                       name="black.jsconnect"> Подмена
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_jsconnect_action === 'iframe' ? 'checked' : '' ?>
                                                                                       value="iframe"
                                                                                       name="black.jsconnect"> IFrame
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>
                                                <hr>
                                                <h4>#3 Metrics and pixels settings</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Google Tag Manager ID: </label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" class="form-control" placeholder=" "
                                                                       name="pixels.gtm.id" value="<?= $gtm_id ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Yandex.Metrika ID:</label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" class="form-control" placeholder=""
                                                                       name="pixels.ya.id" value="<?= $ya_id ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <h5>#3.1 Facebook Pixel Settings</h5>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Имя параметра
                                                                в котором лежит ID пикселя Facebook: </label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" class="form-control" placeholder="px"
                                                                       name="pixels.fb.subname"
                                                                       value="<?= $fbpixel_subname ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Добавлять ли
                                                                на проклы-ленды событие PageView? </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $fb_use_pageview === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="pixels.fb.pageview"> No
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $fb_use_pageview === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="pixels.fb.pageview"> Yes,
                                                                                add </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Добавлять
                                                                событие ViewContent после просмотра страницы в течении
                                                                указанного ниже времени? </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $fb_use_viewcontent === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="pixels.fb.viewcontent.use"
                                                                                       onclick="(document.getElementById('b_8-2').style.display='none')">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $fb_use_viewcontent === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="pixels.fb.viewcontent.use"
                                                                                       onclick="(document.getElementById('b_8-2').style.display='block')">
                                                                                Yes, add </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="b_8-2"
                                                     style="display:<?= $fb_use_viewcontent === true ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Время в
                                                                    сек после которго отправляется
                                                                    ViewContent:<br><small>если 0, то событие не будет
                                                                        вызвано</small> </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="30"
                                                                           name="pixels.fb.viewcontent.time"
                                                                           value="<?= $fb_view_content_time ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Процент
                                                                    проскролливания страницы, до вызова события
                                                                    ViewContent:<br><small>если 0, то событие не будет
                                                                        вызвано</small> </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="75"
                                                                           name="pixels.fb.viewcontent.percent"
                                                                           value="<?= $fb_view_content_percent ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Какое событие
                                                                будем использовать для конверсии в Facebook? <small>Например:
                                                                    Lead или Purchase</small></label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" class="form-control"
                                                                       placeholder="Lead"
                                                                       name="pixels.fb.conversion.event"
                                                                       value="<?= $fb_thankyou_event ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <h5>#3.2 TikTok pixel settings</h5>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Имя параметра
                                                                в котором лежит ID пикселя TikTok: </label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" class="form-control"
                                                                       placeholder="tpx" name="pixels.tt.subname"
                                                                       value="<?= $ttpixel_subname ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Добавлять ли
                                                                на проклы-ленды событие PageView? </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $tt_use_pageview === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="pixels.tt.pageview"> No
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $tt_use_pageview === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="pixels.tt.pageview"> Yes,
                                                                                add </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Добавлять
                                                                событие ViewContent после просмотра страницы в течении
                                                                указанного ниже времени? </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $tt_use_viewcontent === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="pixels.tt.viewcontent.use"
                                                                                       onclick="(document.getElementById('tt_8-2').style.display='none')">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $tt_use_viewcontent === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="pixels.tt.viewcontent.use"
                                                                                       onclick="(document.getElementById('tt_8-2').style.display='block')">
                                                                                Yes, add </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="tt_8-2"
                                                     style="display:<?= $tt_use_viewcontent === true ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Время в
                                                                    сек после которго отправляется
                                                                    ViewContent:<br><small>если 0, то событие не будет
                                                                        вызвано</small> </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="30"
                                                                           name="pixels.tt.viewcontent.time"
                                                                           value="<?= $tt_view_content_time ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Процент
                                                                    проскролливания страницы, до вызова события
                                                                    ViewContent:<br><small>если 0, то событие не будет
                                                                        вызвано</small> </label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" class="form-control"
                                                                           placeholder="75"
                                                                           name="pixels.tt.viewcontent.percent"
                                                                           value="<?= $tt_view_content_percent ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Какое событие
                                                                будем использовать для конверсии в TikTok? <small>Например:
                                                                    CompletePayment или AddPaymentInfo</small></label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" class="form-control"
                                                                       placeholder="Lead"
                                                                       name="pixels.tt.conversion.event"
                                                                       value="<?= $tt_thankyou_event ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>
                                                <hr>
                                                <h4>#4 Traffic Distribution Settings</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                TDS Mode:</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $tds_mode === 'on' ? 'checked' : '' ?>
                                                                                       value="on" name="tds.mode">
                                                                                Usual </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $tds_mode === 'full' ? 'checked' : '' ?>
                                                                                       value="full" name="tds.mode">
                                                                                Send all traffic to safe page</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $tds_mode === 'off' ? 'checked' : '' ?>
                                                                                       value="off" name="tds.mode">
                                                                                Send all traffic to money page (TDS off)</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Посылать
                                                                одного и того же юзера на одни и те же
                                                                проклы-ленды?</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $save_user_flow === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="tds.saveuserflow"> No
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $save_user_flow === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="tds.saveuserflow"> Yes,
                                                                                посылать </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <br>
                                                <hr>
                                                <h4>#5 Cloaker filters</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Allowed operating systems:</label>
                                                        </div>
                                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" name="tds.filters.allowed.os"
                                                                       class="form-control"
                                                                       placeholder="Android,iOS,Windows,OS X"
                                                                       value="<?= implode(',', $os_white) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Allowed
                                                                countries:
                                                                <small>(WW or empty for whole world)</small></label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" name="tds.filters.allowed.countries"
                                                                       class="form-control" placeholder="RU,UA"
                                                                       value="<?= implode(',', $country_white) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Список
                                                                разрешённых языков: <small>(any или пустое значение для
                                                                    всех языков)</small></label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" name="tds.filters.allowed.languages"
                                                                       class="form-control" placeholder="en,ru,de"
                                                                       value="<?= implode(',', $lang_white) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                            <label class="login2 pull-left pull-left-pro">Имя файла
                                                                дополнительной базы с запрещёнными IP-адресами <small>файл
                                                                    должен лежать в папке bases</small></label>
                                                        </div>
                                                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text"
                                                                       name="tds.filters.blocked.ips.filename"
                                                                       class="form-control" placeholder="blackbase.txt"
                                                                       value="<?= $ip_black_filename ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Дополнительная
                                                                база запрещённых IP в формате CIDR?</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $ip_black_cidr === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="tds.filters.blocked.ips.cidrformat">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $ip_black_cidr === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="tds.filters.blocked.ips.cidrformat">
                                                                                Yes </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Слова через
                                                                запятую, при наличии которых в адресе перехода (в
                                                                ссылке, по которой перешли), юзер будет отправлен на
                                                                whitepage</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" name="tds.filters.blocked.tokens"
                                                                       class="form-control" placeholder=""
                                                                       value="<?= implode(',', $tokens_black) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Слова через
                                                                запятую, которые обязательно должны быть в адресе. Если
                                                                хотя бы чего-то нет - показывается вайт</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" name="tds.filters.allowed.inurl"
                                                                       class="form-control" placeholder=""
                                                                       value="<?= implode(',', $url_should_contain) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Слова через
                                                                запятую, при наличии которых в UserAgent, юзер будет
                                                                отправлен на whitepage</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" class="form-control"
                                                                       placeholder="facebook,Facebot,curl,gce-spider,yandex.com/bots"
                                                                       name="tds.filters.blocked.useragents"
                                                                       value="<?= implode(',', $ua_black) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Blocking by ISP, for example: facebook,google</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text" name="tds.filters.blocked.isps"
                                                                       class="form-control"
                                                                       placeholder="facebook,google,yandex,amazon,azure,digitalocean"
                                                                       value="<?= implode(',', $isp_black) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Send all users without Referer to safe page?
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $block_without_referer === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="tds.filters.blocked.referer.empty">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $block_without_referer === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="tds.filters.blocked.referer.empty">
                                                                                Yes </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                If any of these words (comma-separated without spaces)
                                                                will be found
                                                                in Referer the user will be sent to safe page.
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="text"
                                                                       name="tds.filters.blocked.referer.stopwords"
                                                                       class="form-control" placeholder="adheart"
                                                                       value="<?= implode(',', $referer_stopwords) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">If VPN/Tor
                                                                detected,
                                                                send them to safe page?</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $block_vpnandtor === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="tds.filters.blocked.vpntor">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $block_vpnandtor === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="tds.filters.blocked.vpntor">
                                                                                Yes </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>
                                                <hr>
                                                <h4>#6 Additional scripts settings</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Back button actions:</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $back_button_action === 'off' ? 'checked' : '' ?>
                                                                                       value="off"
                                                                                       name="scripts.back.action"
                                                                                       onclick="(document.getElementById('b_9').style.display='none')">
                                                                                Leave it as it is</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $back_button_action === 'disable' ? 'checked' : '' ?>
                                                                                       value="disable"
                                                                                       name="scripts.back.action"
                                                                                       onclick="(document.getElementById('b_9').style.display='none')">
                                                                                Disable (user won't be able to go back)</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $back_button_action === 'replace' ? 'checked' : '' ?>
                                                                                       value="replace"
                                                                                       name="scripts.back.action"
                                                                                       onclick="(document.getElementById('b_9').style.display='block')">
                                                                                Make a redirect</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_9"
                                                     style="display:<?= $back_button_action === 'replace' ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">Back button redirect URL:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="scripts.back.value"
                                                                           class="form-control"
                                                                           placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}"
                                                                           value="<?= $replace_back_address ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Disable: text selection, page saving (Ctrl+S) and
                                                                context menu?
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $disable_text_copy === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="scripts.disabletextcopy">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $disable_text_copy === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="scripts.disabletextcopy">
                                                                                Yes, disable</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Should we open landing in a new tab and
                                                                redirect prelanding page to another URL?</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $replace_prelanding === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="scripts.prelandingreplace.use"
                                                                                       onclick="(document.getElementById('b_10').style.display='none')">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $replace_prelanding === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="scripts.prelandingreplace.use"
                                                                                       onclick="(document.getElementById('b_10').style.display='block')">
                                                                                Yes</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_10"
                                                     style="display:<?= $replace_prelanding === true ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    Prelanding redirect URL:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text"
                                                                           name="scripts.prelandingreplace.url"
                                                                           class="form-control"
                                                                           placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}"
                                                                           value="<?= $replace_prelanding_address ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Should we open Thankyou page in a new tab and redirect
                                                                landing page to another URL:
                                                                </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $replace_landing === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="scripts.landingreplace.use"
                                                                                       onclick="(document.getElementById('b_1010').style.display='none')">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $replace_landing === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="scripts.landingreplace.use"
                                                                                       onclick="(document.getElementById('b_1010').style.display='block')">
                                                                                Yes</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="b_1010"
                                                     style="display:<?= $replace_landing === true ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    Landing redirect URL:</label>
                                                            </div>
                                                            <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="scripts.landingreplace.url"
                                                                           class="form-control"
                                                                           placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}"
                                                                           value="<?= $replace_landing_address ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Should we use phone masks for form inputs?
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_land_use_phone_mask === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="scripts.phonemask.use"
                                                                                       onclick="(document.getElementById('b_11').style.display='none')">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $black_land_use_phone_mask === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="scripts.phonemask.use"
                                                                                       onclick="(document.getElementById('b_11').style.display='block')">
                                                                                Yes, add mask </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="b_11"
                                                     style="display:<?= $black_land_use_phone_mask === true ? 'block' : 'none' ?>;">
                                                    <div class="form-group-inner">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                                <label class="login2 pull-left pull-left-pro">
                                                                    Enter phone mask:</label>
                                                            </div>
                                                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                                <div class="input-group custom-go-button">
                                                                    <input type="text" name="scripts.phonemask.mask"
                                                                           class="form-control"
                                                                           placeholder="+421 999 999 999"
                                                                           value="<?= $black_land_phone_mask ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Enable Comebacker script?</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $comebacker === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="scripts.comebacker"> No
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $comebacker === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="scripts.comebacker">
                                                                                Yes</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Enable Callbacker script?
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $callbacker === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="scripts.callbacker">No
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $callbacker === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="scripts.callbacker">
                                                                                Yes</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Включить
                                                                скрипт, показывающий всплывающие сообщения о том, что
                                                                кто-то приобрёл товар?</label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $addedtocart === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="scripts.addedtocart"> No
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $addedtocart === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="scripts.addedtocart">
                                                                                Yes</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Use lazy loading for images?
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                                                            <div class="bt-df-checkbox pull-left">

                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $images_lazy_load === false ? 'checked' : '' ?>
                                                                                       value="false"
                                                                                       name="scripts.imageslazyload">
                                                                                No </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="i-checks pull-left">
                                                                            <label>
                                                                                <input type="radio" <?= $images_lazy_load === true ? 'checked' : '' ?>
                                                                                       value="true"
                                                                                       name="scripts.imageslazyload">
                                                                                Yes</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br>
                                                <hr>
                                                <h4>#7 Subids (utms) settings</h4>
                                                <p>Кло берёт из адресной строки те субметки, что слева и:<br>
                                                    1. Если у вас локальный ленд, то кло записывает значения меток в
                                                    каждую форму на ленде в поля с именами, которые справа<br>
                                                    2. Если у вас ленд в ПП, то кло дописывает значения меток к ссылке
                                                    ПП с именами, которые справа<br>
                                                    Таким образом мы передаём значения субметок в ПП, чтобы в стате ПП
                                                    отображалась нужная нам инфа <br>
                                                    Ну и плюс это нужно для того, чтобы передавать subid для
                                                    постбэка<br>
                                                    Есть 3 "зашитые" метки: <br>
                                                    - subid - уникальный идентификатор пользователя, создаётся при
                                                    заходе пользователя на блэк, хранится в куки<br>
                                                    - prelanding - название папки преленда<br>
                                                    - landing - название папки ленда<br><br/>
                                                    Пример: <br>
                                                    у вас в адресной строке было http://xxx.com?cn=MyCampaign<br>
                                                    вы написали в настройке: cn => utm_campaign <br/>
                                                    в форме на ленде добавится
                                                <pre>&lt;input type="hidden" name="utm_campaign" value="MyCampaign"/&gt;</pre>
                                                </p>
                                                <div id="subs_container">
                                                    <?php for ($i = 0; $i < count($sub_ids); $i++) { ?>
                                                        <div class="form-group-inner subs">
                                                            <div class="row">
                                                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                    <div class="input-group">
                                                                        <input type="text" class="form-control"
                                                                               placeholder="subid"
                                                                               value="<?= $sub_ids[$i]["name"] ?>"
                                                                               name="subids[<?= $i ?>][name]">
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                    <p>=></p>
                                                                </div>
                                                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                    <div class="input-group custom-go-button">
                                                                        <input type="text" class="form-control"
                                                                               placeholder="sub_id"
                                                                               value="<?= $sub_ids[$i]["rewrite"] ?>"
                                                                               name="subids[<?= $i ?>][rewrite]">
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                    <a href="javascript:void(0)"
                                                                       class="remove-sub-item btn btn-sm btn-primary">Delete</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                                <a id="add-sub-item" class="btn btn-sm btn-primary" href="javascript:;">Add</a>

                                                <br>
                                                <hr>
                                                <h4>#8 Statistics settings</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                <img src="img/info.ico"
                                                                     title="Add it to url like: /admin?password=xxxxx"/>
                                                                Admin-panel password:
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <input type="password" name="statistics.password"
                                                                       class="form-control" placeholder="12345"
                                                                       value="<?= $log_password ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                Time zone to show statistics:
                                                            </label>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                                            <div class="input-group custom-go-button">
                                                                <?= select_timezone('statistics.timezone', $stats_timezone) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <br/>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Настройка
                                                                отображения таблиц по субметкам в стате:</label>
                                                            <br/>
                                                            <br/>
                                                            <p>Слева название метки, которую кло возьмёт из адреса
                                                                перехода.</p>
                                                            <p>Справа название НА АНГЛИЙСКОМ таблицы, в которой будут
                                                                показаны все значения выбранной метки и их стата: клики,
                                                                конверсии</p>
                                                        </div>
                                                    </div>

                                                    <div id="stats_subs_container">
                                                        <?php for ($i = 0; $i < count($stats_sub_names); $i++) { ?>
                                                            <div class="form-group-inner stats_subs">
                                                                <div class="row">
                                                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                        <div class="input-group">
                                                                            <input type="text" class="form-control"
                                                                                   placeholder="camp"
                                                                                   value="<?= $stats_sub_names[$i]["name"] ?>"
                                                                                   name="statistics.subnames[<?= $i ?>][name]">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                        <p>=></p>
                                                                    </div>
                                                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                        <div class="input-group custom-go-button">
                                                                            <input type="text" class="form-control"
                                                                                   placeholder="Campaigns"
                                                                                   value="<?= $stats_sub_names[$i]["value"] ?>"
                                                                                   name="statistics.subnames[<?= $i ?>][value]">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                        <a href="javascript:void(0)"
                                                                           class="remove-stats-sub-item btn btn-sm btn-primary">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    <a id="add-stats-sub-item" class="btn btn-sm btn-primary"
                                                       href="javascript:;">Add</a>
                                                </div>
                                                <br>
                                                <hr>
                                                <h4>#9 Postbacks settings</h4>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">Здесь
                                                                необходимо прописать статусы лидов, в том виде, как их
                                                                вам отправляет в постбэке ПП:</label>
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
                                                                <input type="text" name="postback.lead"
                                                                       class="form-control" placeholder="Lead"
                                                                       value="<?= $lead_status_name ?>">
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
                                                                <input type="text" name="postback.purchase"
                                                                       class="form-control" placeholder="Purchase"
                                                                       value="<?= $purchase_status_name ?>">
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
                                                                <input type="text" name="postback.reject"
                                                                       class="form-control" placeholder="Reject"
                                                                       value="<?= $reject_status_name ?>">
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
                                                                <input type="text" name="postback.trash"
                                                                       class="form-control" placeholder="Trash"
                                                                       value="<?= $trash_status_name ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group-inner">
                                                    <div class="row">
                                                        <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                                                            <label class="login2 pull-left pull-left-pro">
                                                                S2S-postbacks settings:</label>
                                                            <br/>
                                                        </div>
                                                    </div>

                                                    <div id="s2s_container">
                                                        <?php for ($i = 0;
                                                        $i < count($s2s_postbacks);
                                                        $i++){ ?>
                                                        <div class="form-group-inner s2s">
                                                            <div class="row">
                                                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                    <label class="login2 pull-left pull-left-pro">Address:</label>
                                                                    <br/><br/>
                                                                    <p>Внутри адреса постбэка можно использовать
                                                                        следующие макросы:
                                                                        {subid}, {prelanding}, {landing}, {px},
                                                                        {domain}, {status}</p>
                                                                </div>
                                                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                    <div class="input-group">
                                                                        <input type="text" class="form-control"
                                                                               placeholder="https://s2s-postback.com"
                                                                               value="<?= $s2s_postbacks[$i]["url"] ?>"
                                                                               name="postback.s2s[<?= $i ?>][url]">
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                                                    <a class="remove-s2s-item btn btn-sm btn-primary">Delete</a>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                    <label class="login2 pull-left pull-left-pro">
                                                                        S2S-Postback send method:
                                                                    </label>
                                                                </div>
                                                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                    <div class="input-group">
                                                                        <select class="form-control"
                                                                                name="postback.s2s[<?= $i ?>][method]">
                                                                            <option value="GET" <?= ($s2s_postbacks[$i]["method"] === "GET" ? ' selected' : '') ?>>
                                                                                GET
                                                                            </option>
                                                                            <option value="POST"<?= ($s2s_postbacks[$i]["method"] === "POST" ? ' selected' : '') ?>>
                                                                                POST
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                                                    <label class="login2 pull-left pull-left-pro">
                                                                        Events for which S2S-postback will be sent:
                                                                    </label>
                                                                </div>
                                                                <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                                                                    <br/>
                                                                    <br/>
                                                                    <label class="form-check-input">
                                                                        <input type="checkbox" class="form-check-input"
                                                                               name="postback.s2s[<?= $i ?>][events][]"
                                                                               value="Lead"<?= (in_array("Lead", $s2s_postbacks[$i]["events"]) ? ' checked' : '') ?>>Lead</label>&nbsp;&nbsp;
                                                                    <label class="form-check-input">
                                                                        <input type="checkbox" class="form-check-input"
                                                                               name="postback.s2s[<?= $i ?>][events][]"
                                                                               value="Purchase"<?= (in_array("Purchase", $s2s_postbacks[$i]["events"]) ? ' checked' : '') ?>>Purchase</label>&nbsp;&nbsp;
                                                                    <label class="form-check-input">
                                                                        <input type="checkbox" class="form-check-input"
                                                                               name="postback.s2s[<?= $i ?>][events][]"
                                                                               value="Reject"<?= (in_array("Reject", $s2s_postbacks[$i]["events"]) ? ' checked' : '') ?>>Reject</label>&nbsp;&nbsp;
                                                                    <label class="form-check-input">
                                                                        <input type="checkbox" class="form-check-input"
                                                                               name="postback.s2s[<?= $i ?>][events][]"
                                                                               value="Trash"<?= (in_array("Trash", $s2s_postbacks[$i]["events"]) ? ' checked' : '') ?>>Trash
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <a id="add-s2s-item" class="btn btn-sm btn-primary">Add</a>
                                                    <hr>
                                                    <div class="form-group-inner">
                                                        <div class="login-btn-inner">
                                                            <div class="row">
                                                                <div class="col-lg-3"></div>
                                                                <div class="col-lg-9">
                                                                    <div class="login-horizental cancel-wp pull-left">
                                                                        <button class="btn btn-sm btn-primary"
                                                                                type="submit"><strong>Save
                                                                                settings</strong></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <a id="bottom"></a>
</div>
<!--cloneData-->
<script src="js/cloneData.js"></script>
<script>
    $('#add-domain-item').cloneData({
        mainContainerId: 'white_domainspecific',
        cloneContainer: 'white',
        removeButtonClass: 'remove-domain-item',
        maxLimit: 5,
        minLimit: 1,
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
</script>
</body>

<?php
function select_timezone($selectname, $selected = '')
{
    $zones = timezone_identifiers_list();
    $select = "<select name='" . $selectname . "' class='form-control'>";
    foreach ($zones as $zone) {
        $tz = new DateTimeZone($zone);
        $offset = $tz->getOffset(new DateTime) / 3600;
        $select .= '<option value="' . $zone . '"';
        $select .= ($zone == $selected ? ' selected' : '');
        $select .= '>' . $zone . ' ' . $offset . '</option>';
    }
    $select .= '</select>';
    return $select;
}

?>

</html>