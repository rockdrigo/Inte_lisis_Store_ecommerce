<?php

// CLI only
if(isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD'])) {
	exit;
}

define('ISC_CLI', true);
define('NO_SESSION', true);

function printe($param) {
	print $param.PHP_EOL;
}

require_once(dirname(__FILE__).'/init.php');

function logAdd($severity, $msg) {
	$GLOBALS['cron_log'][] = array(
		'severity' => $severity,
		'msg' => $msg,
		'trace' => trace(false,true),
	);
}

$cron = GetClass('ISC_CRON_PENDINGJOBS');

if(!is_object($cron)){
	printe('Error al crear el objeto Cron');
	exit(0);
}

parse_str(implode('&', array_slice($argv, 1)), $_GET);
if(isset($_GET['number'])) {
	$i = $_GET['number'];
}
else {
	$i = 0;
}
if(!isc_is_int($i)){
	$i = 0;
}

if(!isset($_GET['errors'])) {
	$e = 1;
}
else {
	$e = 0;
}

$cron->setNumberToProcess($i);
$cron->processDropboxDir();
if($e != 0) $cron->processErrorDir();