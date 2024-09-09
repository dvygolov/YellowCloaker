<?php
require_once __DIR__ . '/initialization.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../campaign.php';

$db = new Db();
$campId = $_REQUEST['campId'];
$s = $db->get_campaign_settings($campId);
$c = new Campaign($campId, $s);
?>
<!doctype html>
<html lang="en">
<?php include __DIR__.'/head.php' ?>

<body>
    <?php include __DIR__.'/header.php' ?>
    <div class="all-content-wrapper">

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
                            <input type="text" class="form-control" placeholder="domain.com" name="domains" value="<?= implode(',', $c->domains) ?>" />
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
                                            <input type="radio" <?= $c->white->action === 'folder' ? 'checked' : '' ?> value="folder" name="white.action" onclick="(document.getElementById('b_2').style.display = 'block'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'none')" />
                                            Local safe page from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->action === 'redirect' ? 'checked' : '' ?> value="redirect" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'block'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'none')" />
                                            Redirect
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->action === 'curl' ? 'checked' : '' ?> value="curl" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'block'); (document.getElementById('b_5').style.display = 'none')" />
                                            Load a website using CURL
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->action === 'error' ? 'checked' : '' ?> value="error" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'block')" />
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
            <div id="b_2" style="display:<?= $c->white->action === 'folder' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Safe page
                                folder:</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="white" name="white.folder.names" value="<?= implode(',', $c->white->folderNames) ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_3" style="display:<?= ($c->white->action === 'redirect' ? 'block' : 'none') ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Redirect
                                address:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="https://ya.ru" name="white.redirect.urls" value="<?= implode(',', $c->white->redirectUrls) ?>" />
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
                                                <input type="radio" <?= $c->white->redirectType === 301 ? 'checked' : '' ?> value="301" name="white.redirect.type" />
                                                301
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->white->redirectType === 302 ? 'checked' : '' ?> value="302" name="white.redirect.type" />
                                                302
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->white->redirectType === 303 ? 'checked' : '' ?> value="303" name="white.redirect.type" />
                                                303
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->white->redirectType === 307 ? 'checked' : '' ?> value="307" name="white.redirect.type" />
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
            <div id="b_4" style="display:<?= $c->white->action === 'curl' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Url for
                                loading:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="https://ya.ru" name="white.curl.urls" value="<?= implode(',', $c->white->curlUrls) ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_5" style="display:<?= $c->white->action === 'error' ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">HTTP-code:</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="404" name="white.error.codes" value="<?= implode(',', $c->white->errorCodes) ?>" />
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
                                            <input type="radio" <?= $c->white->domainFilterEnabled === false ? 'checked' : '' ?> value="false" name="white.domainfilter.use" onclick="(document.getElementById('b_6').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->domainFilterEnabled === true ? 'checked' : '' ?> value="true" name="white.domainfilter.use" onclick="(document.getElementById('b_6').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="b_6" style="display:<?= $c->white->domainFilterEnabled === true ? 'block' : 'none' ?>;">
                <div id="white_domainspecific">
                    <?php for ($j = 0; $j < count($c->white->domainSpecific); $j++) { ?>
                    <div class="form-group-inner white">
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro">Domain
                                        => Method:Action</label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="xxx.yyy.com" value="<?= $c->white->domainSpecific[$j]->name ?>" name="white.domainfilter.domains[<?= $j ?>][name]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <p>=></p>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="site:white" value="<?= $c->white->domainSpecific[$j]->action ?>" name="white.domainfilter.domains[<?= $j ?>][action]" />
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
                                            <input type="radio" <?= $c->white->jsChecks->enabled === false ? 'checked="checked"' : '' ?> value="false" name="white.jschecks.enabled" onclick="(document.getElementById('jscheckssettings').style.display = 'none')" />
                                            No, don't use
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" value="true" <?= $c->white->jsChecks->enabled === true ? 'checked="checked"' : '' ?> name="white.jschecks.enabled" onclick="(document.getElementById('jscheckssettings').style.display = 'block')" />
                                            Yes, use
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="jscheckssettings" style="display:<?=$c->white->jsChecks->enabled === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">JS-Test
                                timeout (msec): </label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="10000" name="white.jschecks.timeout" value="<?= $c->white->jsChecks->timeout ?>" />
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
                                                <input type="checkbox" name="white.jschecks.events[]" value="mousemove" <?= in_array('mousemove', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Mouse moves
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="keydown" <?= in_array('keydown', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Key presses
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="scroll" <?= in_array('scroll', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Scrolling
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="devicemotion" <?= in_array('devicemotion', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Device motion (Android only)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="deviceorientation" <?= in_array('deviceorientation', $c->white->jsChecks->events) ? 'checked' : '' ?> />
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
                                                <input type="checkbox" name="white.jschecks.events[]" value="audiocontext" <?= in_array('audiocontext', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Audio engine existence
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input id="tzcheck" type="checkbox" name="white.jschecks.events[]" value="timezone" <?= in_array('timezone', $c->white->jsChecks->events) ? 'checked' : '' ?> onchange="(document.getElementById('jscheckstz').style.display = this.checked ? 'block' : 'none')" />
                                                Time Zone
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="jscheckstz" class="form-group-inner" style="display:<?= in_array('timezone', $c->white->jsChecks->events) ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Minimum
                                allowed timezone</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="-3" name="white.jschecks.tzstart" value="<?= $c->white->jsChecks->tzMin ?>" />
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
                                <input type="text" class="form-control" placeholder="3" name="white.jschecks.tzend" value="<?= $c->white->jsChecks->tzMax ?>" />
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
                                            <input type="radio" <?= $c->black->preland->action === 'none' ? 'checked' : '' ?> value="none" name="black.prelanding.action" onclick="(document.getElementById('b_8').style.display = 'none')" />
                                            Don't use prelanding
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->black->preland->action === 'folder' ? 'checked' : '' ?> value="folder" name="black.prelanding.action" onclick="(document.getElementById('b_8').style.display = 'block')" />
                                            Local prelanding from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div id="b_8" style="display:<?= $c->black->preland->action === 'folder' ? 'block' : 'none' ?>;">
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
                                <input type="text" class="form-control" placeholder="p1,p2" name="black.prelanding.folders" value="<?= implode(',', $c->black->preland->folderNames) ?>" />
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
                                            <input type="radio" <?= $c->black->land->action === 'folder' ? 'checked' : '' ?> value="folder" name="black.landing.action" onclick="(document.getElementById('b_landings_redirect').style.display = 'none'); (document.getElementById('b_landings_folder').style.display = 'block')" />
                                            Local landing from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->black->land->action === 'redirect' ? 'checked' : '' ?> value="redirect" name="black.landing.action" onclick="(document.getElementById('b_landings_redirect').style.display = 'block'); (document.getElementById('b_landings_folder').style.display = 'none')" />
                                            Redirect
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_landings_folder" style="display:<?= $c->black->land->action === 'folder' ? 'block' : 'none' ?>;">
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
                                <input type="text" class="form-control" placeholder="l1,l2" name="black.landing.folders" value="<?= implode(',', $c->black->land->folderNames) ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_landings_redirect" style="display:<?= $c->black->land->action === 'redirect' ? 'block' : 'none' ?>;">
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
                                <input type="text" class="form-control" placeholder="https://ya.ru,https://google.com" name="black.landing.redirect.urls" value="<?= implode(',', $c->black->land->redirectUrls) ?>" />
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
                                                <input type="radio" <?= $c->black->land->redirectType === 301 ? 'checked' : '' ?> value="301" name="black.landing.redirect.type" />
                                                301
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->black->land->redirectType === 302 ? 'checked' : '' ?> value="302" name="black.landing.redirect.type" />
                                                302
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->black->land->redirectType === 303 ? 'checked' : '' ?> value="303" name="black.landing.redirect.type" />
                                                303
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->black->land->redirectType === 307 ? 'checked' : '' ?> value="307" name="black.landing.redirect.type" />
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
            <?php
            $use_js_redirect = ($s['black']['prelanding']['action'] === 'none' && $s['black']['landing']['action'] === 'redirect');
            $black_jsconnect_display = $use_js_redirect?'none':'block';
            ?>
            <div class="form-group-inner" id="black_jsconnect" style="display:<?=$black_jsconnect_display;?>">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">When adding
                            cloaker using js show Money page using: </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <?php if ($use_js_redirect) { ?>
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
                                            <input type="radio" <?= $s['black']['jsconnect'] === 'replace' ? 'checked' : '' ?> value="replace" name="black.jsconnect" />
                                            Content replace
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $s['black']['jsconnect'] === 'iframe' ? 'checked' : '' ?> value="iframe" name="black.jsconnect" />
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
                                            <input type="radio" <?= $c->saveUserFlow === false ? 'checked' : '' ?> value="false" name="saveuserflow" /> No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->saveUserFlow === true ? 'checked' : '' ?> value="true" name="saveuserflow" />
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
                        <label class="login2 pull-left pull-left-pro"> Should we use backfix?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->backfix === false ? 'checked' : '' ?> value="false" name="scripts.backfix.use" onclick="(document.getElementById('b_backfix').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->backfix === true ? 'checked' : '' ?> value="true" name="scripts.backfix.use" onclick="(document.getElementById('b_backfix').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_backfix" style="display:<?= $c->scripts->backfix === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> Backfix URL:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.backfix.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $c->scripts->backfixAddress?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                                            <input type="radio" <?= $c->scripts->replacePrelanding === false ? 'checked' : '' ?> value="false" name="scripts.prelandingreplace.use" onclick="(document.getElementById('b_10').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->replacePrelanding === true ? 'checked' : '' ?> value="true" name="scripts.prelandingreplace.use" onclick="(document.getElementById('b_10').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_10" style="display:<?= $c->scripts->replacePrelanding === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> Prelanding redirect URL:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.prelandingreplace.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $c->scripts->replacePrelandingAddress?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Should we open ThankYou page 
                        in a new tab and redirect landing page to another URL:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->replaceLanding === false ? 'checked' : '' ?> value="false" name="scripts.landingreplace.use" onclick="(document.getElementById('b_1010').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->replaceLanding === true ? 'checked' : '' ?> value="true" name="scripts.landingreplace.use" onclick="(document.getElementById('b_1010').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_1010" style="display:<?= $c->scripts->replaceLanding === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> Landing redirect URL:</label>
                        </div>
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.landingreplace.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $c->scripts->replaceLandingAddress ?>" />
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
                                            <input type="radio" <?= $c->scripts->imagesLazyLoad === false ? 'checked' : '' ?> value="false" name="scripts.imageslazyload" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->imagesLazyLoad === true ? 'checked' : '' ?> value="true" name="scripts.imageslazyload" />
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
                у вас в адресной строке было https://xxx.com?cn=MyCampaign<br />
                вы написали в настройке: cn => utm_campaign <br />
                в форме на ленде добавится
                <pre>&lt;input type="hidden" name="utm_campaign" value="MyCampaign"/&gt;</pre>
            </p>
            <div id="subs_container">
                <?php for ($i = 0; $i < count($c->subIds); $i++) {
                        $sn = $c->subIds[$i]->name;
                        $sr = $c->subIds[$i]->rewrite;
                ?>
                <div class="form-group-inner subs">
                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="subid" value="<?=$sn?>" name="subids[<?= $i ?>][name]" />
                            </div>
                        </div>
                        <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                            <p>=></p>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="sub_id" value="<?=$sr?>" name="subids[<?= $i ?>][rewrite]" />
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
            <h4>#8 Campaign statistics settings</h4>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Time zone to show statistics:
                        </label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <?= select_timezone('statistics.timezone', $c->statistics->timezone); ?>
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
                            <img src="img/info.ico" title="Your postback will look like this: https://yourdomain.com/postback.php?subid={subid}&payout={payout}&status={status}" />
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
                                    <img src="img/info.ico" title="Inside the S2S-postback address you can use the following macros: {subid}, {prelanding}, {landing}, {px}, {domain}, {status}" />
                                    Address:
                                </label>
                                <br /><br />
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="https://s2s-postback.com" value="<?= $s2sUrl ?>" name="postback.s2s[<?= $i ?>][url]" />
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
                                        <option value="GET" <?= ($s2sMethod === "GET" ? ' selected' : '') ?>> GET
                                        </option>
                                        <option value="POST" <?= ($s2sMethod === "POST" ? ' selected' : '') ?>> POST
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

                const urlParams = new URLSearchParams(window.location.search);
                const campId = urlParams.get('campId');
                if (campId === null) {
                    alert("No campaign ID found!");
                    return false;
                }

                let rules = $('#filtersbuilder').queryBuilder('getRules');
                let formData = new FormData(document.getElementById("saveconfig"));
                let filteredFormData = new FormData();
                for (let [key, value] of formData.entries()) {
                    if (!key.startsWith("filtersbuilder")) {
                        filteredFormData.append(key, value);
                    }
                }
                filteredFormData.append("filters", JSON.stringify(rules));
                let settingsBody = new URLSearchParams(filteredFormData.entries()).toString();

                let res = await fetch(`campeditor.php?action=save&campId=${campId}`, {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: settingsBody
                });
                let js = await res.json();
                if (js.error)
                    alert(`An error occured: ${js.result}`);
                else
                    alert("Settings saved!");
                return false;
            });
        });
    </script>
    <script src="js/filters.js"></script>
    <script>
        var rules_basic = <?=json_encode($c->filters)?>;

        $('#filtersbuilder').queryBuilder({
            filters: tdsFilters,
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
