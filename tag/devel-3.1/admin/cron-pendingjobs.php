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

$cron = new ISC_CRON_PENDINGJOBS();

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

$cron->setNumberToProcess($i);
$cron->processDropboxDir();
$cron->doLog();
$cron->moveErrorFiles();
$cron->processDropboxDir();
$cron->doLog(true);
