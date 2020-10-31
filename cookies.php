<?php
function ywbsetcookie($name,$value,$expires,$path){
	header("Set-Cookie: {$name}={$value}; Expires={$expires}; Path={$path}; SameSite=None; Secure",false);
}
?>