<?php

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/main.php';
require_once __DIR__ . '/cookies.php';

class Tds
{
    public static function getAction(): TdsAction
    {
        global $db;
        $dbCamp = $db->get_campaign_by_domain();
        if ($dbCamp === false) {
            $action = traficback(FiltrationCore::get_click_params());
        } else {
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            $clkr = new FiltrationCore();

            if ($clkr->click_matches_filters($c->white->filters)) {
                $db->add_white_click($clkr->click_params, $clkr->block_reason, $c->campaignId);
                $action = white($c);
            } else {
                $jscheck_passed = session_read('jscheck_passed');
                if ($c->black->jsBotDetection->enabled && is_null($jscheck_passed)) {
                    $action = jscheck($c);
                } else {
                    $flowIndex = self::pick_flow_index($clkr, $c->black->flows);
                    if ($flowIndex === null) {
                        $action = traficback($clkr->click_params);
                    } else {
                        $action = black($c, $flowIndex, $clkr->click_params);
                    }
                }
            }
        }
        return $action;
    }

    public static function getJsAction(array $prefill): JsAction
    {
        global $db;
        $dbCamp = $db->get_campaign_by_domain();
        if ($dbCamp === false) {
            $action = traficback(FiltrationCore::get_click_params($prefill));
        } else {
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            $clkr = new FiltrationCore($prefill);

            if ($clkr->click_matches_filters($c->white->filters)) {
                $db->add_white_click($clkr->click_params, $clkr->block_reason, $c->campaignId);
                $action = white($c);
            } else {
                $jscheck_passed = session_read('jscheck_passed');
                if ($c->black->jsBotDetection->enabled && is_null($jscheck_passed)) {
                    $action = jscheck($c);
                    $action->action = 'html_content';
                } else {
                    $flowIndex = self::pick_flow_index($clkr, $c->black->flows);
                    if ($flowIndex === null) {
                        $action = traficback($clkr->click_params);
                    } else {
                        $action = black($c, $flowIndex, $clkr->click_params);
                        if ($c->black->jsconnectAction === 'iframe') {
                            $action->action = 'html_iframe';
                        } else {
                            $action->action = 'html_content';
                        }
                    }
                }
            }
        }
        return JsAction::FromTdsAction($action);
    }

    public static function processJsCheck(): JsAction
    {
        global $db;
        $dbCamp = $db->get_campaign_by_domain();
        if ($dbCamp === false) { //campaign already deleted or domain changed? lol
            if (DebugMethods::on()) {
                $action = new JsAction("traficback", "js", "console.log('Debug: No campaign found for this domain!');");
            } else {
                $action = new JsAction("traficback", "error", "");
            }
            return $action;
        }

        //This means that the user didn't pass JS checks
        if (isset($_GET['reason'])) {
            $added = $db->add_white_click(FiltrationCore::get_click_params(), $_GET['reason'], $dbCamp['id']);
            if (DebugMethods::on()) {
                $msg = ($added ? "console.log('Debug: White click logged.');" : "console.log('Debug: Error adding white click!');");
                $action = new JsAction("white", "js", $msg);
            } else {
                $action = new JsAction("white", "error", "");
            }
        } else {
            $jscheck_start_time = session_read('jscheck_pending');
            $current_time = time();
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            // Convert from milliseconds to seconds
            $max_execution_time = $c->black->jsBotDetection->timeout / 1000;
            // Add 5 second buffer
            $allowed_time = $jscheck_start_time + $max_execution_time + 5;

            if ($current_time > $allowed_time) {
                // Attempt to pass JS check after timeout
                $db->add_white_click(FiltrationCore::get_click_params(), 'jscheck_scam_timeout', $dbCamp['id']);
                session_remove('jscheck_pending');
                if (DebugMethods::on()) {
                    $action = new JsAction("white", "js", "console.log('Debug: JS check scam - timeout exceeded');");
                } else {
                    $action = new JsAction("white", "error", "");
                }
                return $action;
            }
            
            // All security checks passed - remove pending flag and allow black
            session_remove('jscheck_pending');
            session_write('jscheck_passed', true);
            $clkr = new FiltrationCore();
            $flowIndex = self::pick_flow_index($clkr, $c->black->flows);
            if ($flowIndex === null) {
                $action = traficback($clkr->click_params);
                $action = JsAction::FromTdsAction($action);
            } else {
                $action = black($c, $flowIndex, $clkr->click_params);
                $action = JsAction::FromTdsAction($action);
                if ($c->black->jsconnectAction === 'iframe') {
                    $action->action = 'html_iframe';
                } else {
                    $action->action = 'html_content';
                }
            }
        }

        return $action;
    }

    public static function getPhpAction($apikey, array $prefill): PhpAction
    {
        global $db;
        $dbCamp = $db->get_campaign_by_apikey($apikey);
        if (empty($dbCamp)) {
            $action = traficback(FiltrationCore::get_click_params($prefill));
        } else {
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            $clkr = new FiltrationCore($prefill);

            if ($clkr->click_matches_filters($c->white->filters)) {
                $db->add_white_click($clkr->click_params, $clkr->block_reason, $c->campaignId);
                $action = white($c);
            } else {
                $jscheck_passed = session_read('jscheck_passed');
                if ($c->black->jsBotDetection->enabled && is_null($jscheck_passed)) {
                    $action = jscheck($c);
                } else {
                    $flowIndex = self::pick_flow_index($clkr, $c->black->flows);
                    if ($flowIndex === null) {
                        $action = traficback($clkr->click_params);
                    } else {
                        $action = black($c, $flowIndex, $clkr->click_params);
                    }
                }
            }
        }
        return PhpAction::FromTdsAction($action);
    }

    public static function pick_flow_index(FiltrationCore $clkr, array $flows): ?int
    {
        for ($i = 0; $i < count($flows); $i++) {
            if ($clkr->click_matches_filters($flows[$i]->filters)) {
                return $i;
            }
        }
        return null;
    }
}
