<?php
include(dirname(__FILE__) . "/init.php");

if(!isset($_REQUEST['moduleId']) || $_REQUEST['moduleId'] == '') {
	exit;
}

if(!GetModuleById('checkout',$module, $_REQUEST['moduleId'])) {
	exit;
}

if(!method_exists($module, "GetAuthFrom")) {
	exit;
}


echo $module->GetAuthFrom();
