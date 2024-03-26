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

        <form id="saveconfig" style="padding:35px;background-color:#1D2A48;">
            <h4>#0 Domains</h4>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="Enter all of your domains WITHOUT HTTP, comma-separated, WITHOUT SPACES! You can use *.xxx.com to match ALL subdomains." />
                            Domains list for this config:
                        </label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" class="form-control" placeholder="domain.com" name="domains" value="<?= implode(',', $domain_names) ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <hr />
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
                                            <input type="radio" <?= $white_action === 'folder' ? 'checked' : '' ?> value="folder" name="white.action" onclick="(document.getElementById('b_2').style.display = 'block'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'none')" />
                                            Local safe page from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $white_action === 'redirect' ? 'checked' : '' ?> value="redirect" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'block'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'none')" />
                                            Redirect
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $white_action === 'curl' ? 'checked' : '' ?> value="curl" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'block'); (document.getElementById('b_5').style.display = 'none')" />
                                            Load a website using CURL
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $white_action === 'error' ? 'checked' : '' ?> value="error" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'block')" />
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
            <div id="b_2" style="display:<?= $white_action === 'folder' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Safe page
                                folder:</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="white" name="white.folder.names" value="<?= implode(',', $white_folder_names) ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_3" style="display:<?= ($white_action === 'redirect' ? 'block' : 'none') ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Redirect
                                address:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="https://ya.ru" name="white.redirect.urls" value="<?= implode(',', $white_redirect_urls) ?>" />
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
                                                <input type="radio" <?= $white_redirect_type === '301' ? 'checked' : '' ?> value="301" name="white.redirect.type" />
                                                301
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $white_redirect_type === '302' ? 'checked' : '' ?> value="302" name="white.redirect.type" />
                                                302
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $white_redirect_type === '303' ? 'checked' : '' ?> value="303" name="white.redirect.type" />
                                                303
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $white_redirect_type === '307' ? 'checked' : '' ?> value="307" name="white.redirect.type" />
                                                307
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_4" style="display:<?= $white_action === 'curl' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Url for
                                loading:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="https://ya.ru" name="white.curl.urls" value="<?= implode(',', $white_curl_urls) ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_5" style="display:<?= $white_action === 'error' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">HTTP-code:</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="404" name="white.error.codes" value="<?= implode(',', $white_error_codes) ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">

                            <img src="img/info.ico" title="Allowed methods are: folder, redirect, curl,error" />
                            Show
                            individual
                            domain-specific safe page?
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $white_use_domain_specific === false ? 'checked' : '' ?> value="false" name="white.domainfilter.use" onclick="(document.getElementById('b_6').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $white_use_domain_specific === true ? 'checked' : '' ?> value="true" name="white.domainfilter.use" onclick="(document.getElementById('b_6').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="b_6" style="display:<?= $white_use_domain_specific === true ? 'block' : 'none' ?>;">
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
                                    <input type="text" class="form-control" placeholder="xxx.yyy.com" value="<?= $white_domain_specific[$j]["name"] ?>" name="white.domainfilter.domains[<?= $j ?>][name]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <p>=></p>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="site:white" value="<?= $white_domain_specific[$j]["action"] ?>" name="white.domainfilter.domains[<?= $j ?>][action]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-domain-item btn btn-sm btn-primary">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-domain-item" class="btn btn-sm btn-primary" href="javascript:;">Add</a>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="If JS filters are switched ON, then the user will be shown a safe page for a moment and only after all the checks are passed he'll be shown the money page." />
                            Use Javascript filters?
                            <small></small>
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $use_js_checks === false ? 'checked="checked"' : '' ?> value="false" name="white.jschecks.enabled" onclick="(document.getElementById('jscheckssettings').style.display = 'none')" />
                                            No, don't use
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" value="true" <?= $use_js_checks === true ? 'checked="checked"' : '' ?> name="white.jschecks.enabled" onclick="(document.getElementById('jscheckssettings').style.display = 'block')" />
                                            Yes, use
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="jscheckssettings" style="display:<?= $use_js_checks === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">JS-Test
                                timeout (msec): </label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="10000" name="white.jschecks.timeout" value="<?= $js_timeout ?>" />
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
                                                <input type="checkbox" name="white.jschecks.events[]" value="mousemove" <?= in_array('mousemove', $js_checks) ? 'checked' : '' ?> />
                                                Mouse moves
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="keydown" <?= in_array('keydown', $js_checks) ? 'checked' : '' ?> />
                                                Key presses
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="scroll" <?= in_array('scroll', $js_checks) ? 'checked' : '' ?> />
                                                Scrolling
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="devicemotion" <?= in_array('devicemotion', $js_checks) ? 'checked' : '' ?> />
                                                Device motion (Android only)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="deviceorientation" <?= in_array('deviceorientation', $js_checks) ? 'checked' : '' ?> />
                                                Device orientation (Android
                                                only)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="audiocontext" <?= in_array('audiocontext', $js_checks) ? 'checked' : '' ?> />
                                                Audio engine existence
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input id="tzcheck" type="checkbox" name="white.jschecks.events[]" value="timezone" <?= in_array('timezone', $js_checks) ? 'checked' : '' ?> onchange="(document.getElementById('jscheckstz').style.display = this.checked ? 'block' : 'none')" />
                                                Time Zone
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="jscheckstz" class="form-group-inner" style="display:<?= in_array('timezone', $js_checks) ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Minimum
                                allowed timezone</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="-3" name="white.jschecks.tzstart" value="<?= $js_tzstart ?>" />
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
                                <input type="text" class="form-control" placeholder="3" name="white.jschecks.tzend" value="<?= $js_tzend ?>" />
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
                                                <input type="radio" value="true" <?= $js_obfuscate === true ? 'checked="checked"' : '' ?> name="white.jschecks.obfuscate" />
                                                Yes
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" value="false" <?= $js_obfuscate === false ? 'checked="checked"' : '' ?> name="white.jschecks.obfuscate" />
                                                No
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <hr />
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
                                            <input type="radio" <?= $black_preland_action === 'none' ? 'checked' : '' ?> value="none" name="black.prelanding.action" onclick="(document.getElementById('b_8').style.display = 'none')" />
                                            Don't use prelanding
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $black_preland_action === 'folder' ? 'checked' : '' ?> value="folder" name="black.prelanding.action" onclick="(document.getElementById('b_8').style.display = 'block')" />
                                            Local prelanding from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div id="b_8" style="display:<?= $black_preland_action === 'folder' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">
                                <img src="img/info.ico" title="If you want to perform an A/B Test then enter several folders comma-separated, WITHOUT SPACES" />
                                Prelanding folder(s)
                            </label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="p1,p2" name="black.prelanding.folders" value="<?= implode(',', $black_preland_folder_names) ?>" />
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
                                            <input type="radio" <?= $black_land_action === 'folder' ? 'checked' : '' ?> value="folder" name="black.landing.action" onclick="(document.getElementById('b_landings_redirect').style.display = 'none'); (document.getElementById('b_landings_folder').style.display = 'block')" />
                                            Local landing from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $black_land_action === 'redirect' ? 'checked' : '' ?> value="redirect" name="black.landing.action" onclick="(document.getElementById('b_landings_redirect').style.display = 'block'); (document.getElementById('b_landings_folder').style.display = 'none')" />
                                            Redirect
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_landings_folder" style="display:<?= $black_land_action === 'folder' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">
                                <img src="img/info.ico" title="If you want to perform an A/B Test then enter several folders comma-separated, WITHOUT SPACES" />
                                Landing folder(s)
                            </label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="l1,l2" name="black.landing.folder.names" value="<?= implode(',', $black_land_folder_names) ?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> ThankYou Page language:</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="EN" name="black.landing.folder.customthankyoupage.language" value="<?= $black_land_thankyou_page_language ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_landings_redirect" style="display:<?= $black_land_action === 'redirect' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">
                                <img src="img/info.ico" title="If you need several redirects (split test) - separate them with commas without spaces" />
                                Redirect url(s):
                            </label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="https://ya.ru,https://google.com" name="black.landing.redirect.urls" value="<?= implode(',', $black_land_redirect_urls) ?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Redirect
                                type:</label>
                        </div>
                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                            <div class="bt-df-checkbox pull-left">

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $black_land_redirect_type === '301' ? 'checked' : '' ?> value="301" name="black.landing.redirect.type" />
                                                301
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $black_land_redirect_type === '302' ? 'checked' : '' ?> value="302" name="black.landing.redirect.type" />
                                                302
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $black_land_redirect_type === '303' ? 'checked' : '' ?> value="303" name="black.landing.redirect.type" />
                                                303
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $black_land_redirect_type === '307' ? 'checked' : '' ?> value="307" name="black.landing.redirect.type" />
                                                307
                                            </label>
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
                        <label class="login2 pull-left pull-left-pro">When adding
                            cloaker using js show Money page using: </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <?php if ($black_preland_action === 'none' && $black_land_action === 'redirect') { ?>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" checked value="redirect" name="black.jsconnect" />
                                            Redirect
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php } else { ?>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $black_jsconnect_action === 'replace' ? 'checked' : '' ?> value="replace" name="black.jsconnect" />
                                            Content replace
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $black_jsconnect_action === 'iframe' ? 'checked' : '' ?> value="iframe" name="black.jsconnect" />
                                            IFrame
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <hr />
            <h4>#3 Traffic Distribution Settings</h4>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="If Yes then the user will always be shown the same content on every visit" />
                            Save user flow:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $save_user_flow === false ? 'checked' : '' ?> value="false" name="tds.saveuserflow" /> No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $save_user_flow === true ? 'checked' : '' ?> value="true" name="tds.saveuserflow" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <hr />
            <h4>#5 Cloaker filters</h4>
            <div class="form-group-inner">
                <p>
                Here you define: which traffic will be ALLOWED to see the money pages.
                </p>
                <div class="row">
                    <div id="filtersbuilder"></div>
                </div>
            </div>
            <hr />
            <h4>#6 Additional scripts settings</h4>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Should we open landing in a new tab and
                            redirect prelanding page to another URL?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $replace_prelanding === false ? 'checked' : '' ?> value="false" name="scripts.prelandingreplace.use" onclick="(document.getElementById('b_10').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $replace_prelanding === true ? 'checked' : '' ?> value="true" name="scripts.prelandingreplace.use" onclick="(document.getElementById('b_10').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_10" style="display:<?= $replace_prelanding === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> Prelanding redirect URL:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.prelandingreplace.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $replace_prelanding_address ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Should we open Thankyou page in a new tab and redirect
                            landing page to another URL:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $replace_landing === false ? 'checked' : '' ?> value="false" name="scripts.landingreplace.use" onclick="(document.getElementById('b_1010').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $replace_landing === true ? 'checked' : '' ?> value="true" name="scripts.landingreplace.use" onclick="(document.getElementById('b_1010').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_1010" style="display:<?= $replace_landing === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> Landing redirect URL:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.landingreplace.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $replace_landing_address ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Use lazy loading for images?
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $images_lazy_load === false ? 'checked' : '' ?> value="false" name="scripts.imageslazyload" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $images_lazy_load === true ? 'checked' : '' ?> value="true" name="scripts.imageslazyload" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <hr />
            <h4>#7 Subids (utms) settings</h4>
            <p>
                Кло берёт из адресной строки те субметки, что слева и:<br />
                1. Если у вас локальный ленд, то кло записывает значения меток в
                каждую форму на ленде в поля с именами, которые справа<br />
                2. Если у вас ленд в ПП, то кло дописывает значения меток к ссылке
                ПП с именами, которые справа<br />
                Таким образом мы передаём значения субметок в ПП, чтобы в стате ПП
                отображалась нужная нам инфа <br />
                Ну и плюс это нужно для того, чтобы передавать subid для
                постбэка<br />
                Есть 3 "зашитые" метки: <br />
                - subid - уникальный идентификатор пользователя, создаётся при
                заходе пользователя на блэк, хранится в куки<br />
                - prelanding - название папки преленда<br />
                - landing - название папки ленда<br /><br />
                Пример: <br />
                у вас в адресной строке было http://xxx.com?cn=MyCampaign<br />
                вы написали в настройке: cn => utm_campaign <br />
                в форме на ленде добавится
                <pre>&lt;input type="hidden" name="utm_campaign" value="MyCampaign"/&gt;</pre>
            </p>
            <div id="subs_container">
                <?php for ($i = 0; $i < count($sub_ids); $i++) { ?>
                <div class="form-group-inner subs">
                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="subid" value="<?= $sub_ids[$i]["name"] ?>" name="subids[<?= $i ?>][name]" />
                            </div>
                        </div>
                        <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                            <p>=></p>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="sub_id" value="<?= $sub_ids[$i]["rewrite"] ?>" name="subids[<?= $i ?>][rewrite]" />
                            </div>
                        </div>
                        <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                            <a href="javascript:void(0)" class="remove-sub-item btn btn-sm btn-primary">Delete</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <a id="add-sub-item" class="btn btn-sm btn-primary" href="javascript:;">Add</a>

            <br />
            <hr />
            <h4>#8 Statistics settings</h4>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="Add it to url like: /admin/?password=xxxxx" />
                            Admin-panel password:
                        </label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="password" name="statistics.password" class="form-control" placeholder="12345" value="<?= $admin_password ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Time zone to show statistics:
                        </label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <?= select_timezone('statistics.timezone', $stats_timezone) ?>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <hr />
            <h4>#9 Postbacks settings</h4>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="Your postback will look smth like this: https://yourdomain.com/postback.php?subid={subid}&payout={payout}&status={status}" />
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
                            <input type="text" name="postback.lead" class="form-control" placeholder="Lead" value="<?= $lead_status_name ?>" />
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
                            <input type="text" name="postback.purchase" class="form-control" placeholder="Purchase" value="<?= $purchase_status_name ?>" />
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
                            <input type="text" name="postback.reject" class="form-control" placeholder="Reject" value="<?= $reject_status_name ?>" />
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
                            <input type="text" name="postback.trash" class="form-control" placeholder="Trash" value="<?= $trash_status_name ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> S2S-postbacks settings:</label>
                        <br />
                    </div>
                </div>

                <div id="s2s_container">
                    <?php 
                    for ( $i = 0; $i < count($s2s_postbacks); $i++) { ?>
                    <div class="form-group-inner s2s">
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro">
                                    <img src="img/info.ico" title="Inside the S2S-postback address you can use the following macros: {subid}, {prelanding}, {landing}, {px}, {domain}, {status}" />
                                    Address:
                                </label>
                                <br /><br />
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="https://s2s-postback.com" value="<?= $s2s_postbacks[$i]["url"] ?>" name="postback.s2s[<?= $i ?>][url]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a class="remove-s2s-item btn btn-sm btn-primary">Delete</a>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro"> S2S-Postback send method:
                                </label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <select class="form-control" name="postback.s2s[<?= $i ?>][method]">
                                        <option value="GET" <?= ($s2s_postbacks[$i]["method"] === "GET" ? ' selected' : '') ?>> GET
                                        </option>
                                        <option value="POST" <?= ($s2s_postbacks[$i]["method"] === "POST" ? ' selected' : '') ?>> POST
                                        </option>
                                    </select>
                                </div>
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
                                        <input id="<?=$status?><?=$i?>" type="checkbox" class="form-check-input" name="postback.s2s[<?= $i ?>][events][]" value="<?=$status?>" <?= (in_array($status, $s2s_postbacks[$i]["events"]) ? ' checked' : '') ?> />
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-s2s-item" class="btn btn-sm btn-primary">Add</a>
                <hr />
                <div class="form-group-inner">
                    <div class="login-btn-inner">
                        <div class="row">
                            <div class="col-lg-3"></div>
                            <div class="col-lg-9">
                                <div class="login-horizental cancel-wp pull-left">
                                    <button class="btn btn-sm btn-primary" type="submit">
                                        <strong>Save
                                            settings</strong>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("saveconfig")?.addEventListener("submit", async (e) => {
                e.preventDefault();

                let rules = $('#filtersbuilder').queryBuilder('getRules');
                let formData = new FormData(document.getElementById("saveconfig"));
                let filteredFormData = new FormData();

                for (let [key, value] of formData.entries()) {
                    if (!key.startsWith("filtersbuilder")) {
                        filteredFormData.append(key, value);
                    }
                }

                filteredFormData.append("tds.filters", JSON.stringify(rules));
                let res = await fetch("configmanager.php?action=save&name=<?= $config ?>", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(filteredFormData.entries()).toString()
                });
                let js = await res.json();
                if (js["result"] === "OK")
                    alert("Settings saved!")
                else
                    alert(`An error occured: ${js["result"]}`);
                return false;
            });
        });
    </script>
    <script>
        var rules_basic = <?=json_encode($tds_filters)?>;

        $('#filtersbuilder').queryBuilder({

            filters: [
                {
                    id: 'os',
                    label: 'OS',
                    input: 'text',
                    type: 'string',
                    operators: ['in', 'not_in'],
                    placeholder: 'Android,iOS,Windows,OS X',
                    size: 50
                },
                {
                    id: 'country',
                    label: 'Country',
                    input: 'text',
                    type: 'string',
                    operators: ['in', 'not_in'],
                    placeholder: 'RU,BY,UA'

                },
                {
                    id: 'language',
                    label: 'Language',
                    input: 'text',
                    type: 'string',
                    operators: ['in', 'not_in'],
                    placeholder: 'en,ru'
                },
                {
                    id: 'url',
                    label: 'URL',
                    input: 'text',
                    type: 'string',
                    operators: ['contains', 'not_contains'],
                    size: 100
                },
                {
                    id: 'useragent',
                    label: 'UserAgent',
                    input: 'text',
                    type: 'string',
                    operators: ['contains', 'not_contains'],
                    size: 100,
                    placeholder: 'facebook,facebot,curl,gce-spider,yandex.com,odklbot'
                },
                {
                    id: 'isp',
                    label: 'ISP',
                    input: 'text',
                    type: 'string',
                    operators: ['contains', 'not_contains'],
                    size: 100,
                    placeholder:'facebook,google,yandex,amazon,azure,digitalocean,microsoft'
                },
                {
                    id: 'referer',
                    label: 'Referer',
                    input: 'text',
                    type: 'string',
                    operators: ['not_equal', 'contains', 'not_contains'],
                    validation:{
                        allow_empty_value:true
                    },
                    size:100
                },
                {
                    id: 'vpntor',
                    label: 'VPN&Tor',
                    type: 'integer',
                    input: 'radio',
                    values: {
                        0: 'Detected',
                        1: 'NOT Detected'
                    },
                    operators: ['equal']
                },
                {
                    id: 'ipbase',
                    label: 'IP Base',
                    type: 'string',
                    operators: ['contains','not_contains'],
                    placeholder: 'path to base file(s) in bases folder: bots.txt',
                    size:100
                 }
            ],

            rules: rules_basic
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
