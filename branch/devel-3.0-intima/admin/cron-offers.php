<?php

// CLI only
if(isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD'])) {
	exit;
}

define('ISC_CLI', true);
define('NO_SESSION', true);


require_once(dirname(__FILE__).'/init.php');

$Clave = substr($GLOBALS['ISC_CLASS_DB']->TablePrefix, 0, strlen($GLOBALS['ISC_CLASS_DB']->TablePrefix)-1);
$lockfile = GetConfig('syncArchiveDir').'cronlocks/'.$Clave.'-offers.lock';

define('ISC_CRON_OFFERS_LOCKFILE', $lockfile);

function cronLockFileExists(){
	if(file_exists(ISC_CRON_OFFERS_LOCKFILE)){
		return true;
	}
	else {
		return false;
	}
}

function createCronLockFile(){
	if(!cronLockFileExists()){
		if(file_put_contents(ISC_CRON_OFFERS_LOCKFILE, time())){
			return true;
		}
		else {
			print('Error al intentar crear el archivo de lock "'.ISC_CRON_OFFERS_LOCKFILE.'"'.PHP_EOL);
			return false;
		}
	}
	else{
		return false;
	}
}

function printe($param) {
	print $param.PHP_EOL;
}

function logAdd($severity, $msg) {
	$GLOBALS['cron_log'][] = array(
		'severity' => $severity,
		'msg' => $msg,
		'trace' => trace(false,true),
	);
}

if(cronLockFileExists()){
	print 'No se ejecuto cron porque se esta ejecutando en otro proceso.'.PHP_EOL;
	ob_flush();
	exit(0);
}
else {
	if(createCronLockFile()){
		print 'Archivo lockfile "'.ISC_CRON_OFFERS_LOCKFILE.'" creado'.PHP_EOL;
		ob_flush();
	}
	else {
		exit(0);
	}
}

$GLOBALS['ISC_ADMIN_INTELISIS_OFFERS'] = GetClass('ISC_ADMIN_INTELISIS_OFFERS');

if(!is_object($GLOBALS['ISC_ADMIN_INTELISIS_OFFERS'])){
	printe('Error al crear el objeto Cron-Offers');
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

$GLOBALS['ISC_ADMIN_INTELISIS_OFFERS']->RecaulculateOffers();

if(!unlink(ISC_CRON_OFFERS_LOCKFILE)){
	print('Error al eliminar el archivo lock "'.ISC_CRON_OFFERS_LOCKFILE.'".'.PHP_EOL);
}
else {
	print 'Archivo lockfile "'.ISC_CRON_OFFERS_LOCKFILE.'" eliminado.'.PHP_EOL;
}
