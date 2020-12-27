<?php

function redirect($url,$redirect_type=302){
    $querystr = $_SERVER['QUERY_STRING'];
	if (!empty($querystr)) {
		$url = $url.'?'.$querystr;
	}
    if ($redirect_type===302) {
        header('Location: '.$url);
    } else {
        header('Location: '.$url, true, $redirect_type);
    }
}

function jsredirect($url){
    echo "<script type='text/javascript'> window.location='$url';</script>";
    return;
}
?>