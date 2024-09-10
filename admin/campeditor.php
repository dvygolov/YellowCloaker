<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../campaign.php';

$passOk = check_password(false);
if (!$passOk)
    return send_camp_result("Error: password check not passed!");

$action = $_REQUEST['action'];
$name = $_REQUEST['name']??'';
$campId = $_REQUEST['campId']??-1;
$db = new Db();

switch ($action) {
    case 'add':
        $campId = $db->add_campaign($name);
        if ($campId===false)
            return send_camp_result("Error adding new campaign!",true);
        break;
    case 'dup':
        $clonedId = $db->clone_campaign($campId);
        if ($clonedId===false)
            return send_camp_result("Error duplicating campaign!",true);
        break;
    case 'del':
        $delRes = $db->delete_campaign($campId);
        if ($delRes===false)
            return send_camp_result("Error deleting campaign!",true);
        break;
    case 'ren':
        $renRes = $db->rename_campaign($campId, $name);
        if ($renRes===false)
            return send_camp_result("Error renaming campaign!",true);
        break;
    case 'save':
        $s = $db->get_campaign_settings($campId);
        foreach($_POST as $key=>$value){
            if ($key==="filters"){ //special case cause we store filters in json format
                $arrFilters=json_decode($value,true);
                $s['filters'] = $arrFilters;
            }
            else
                setArrayValue($s,$key,$value);
        }
        $c = new Campaign($campId,$s);
        $saveRes = $db->save_campaign_settings($campId, $s);
        if($saveRes===false)
            return send_camp_result("Error saving campaign!",true);
        break;
    default:
        return send_camp_result("Error: wrong action!",true);
}
return send_camp_result("OK");

function send_camp_result($msg,$error=false): void
{
    $res = ["result" => $msg];
    if ($error){
        $res['error']=true;
    }
    header('Content-type: application/json');
    http_response_code(200);
    $json = json_encode($res);
    echo $json;
}

function setArrayValue(&$array, $underscoreString, $newValue) {
    // Split the underscrore notation string into keys
    $keys = explode('_', $underscoreString);
    
    // Traverse the array using each key
    $current = &$array;
    foreach ($keys as $key) {
        // If the key doesn't exist, create it as an empty array
        if (!isset($current[$key])) {
            $current[$key] = [];
        }
        // Move to the next level
        $current = &$current[$key];
    }

    if (is_string($newValue)&&is_array($current)){
        $arrValue = (empty($newValue)?[]:explode(',',$newValue));
        $current = $arrValue;
    }
    else if ($newValue==='false'|| $newValue==='true'){
        $boolValue=filter_var($newValue,FILTER_VALIDATE_BOOLEAN);
        $current=$boolValue;
    }
    else{
        // Set the new value at the final key
        $current = $newValue;
    }
}
