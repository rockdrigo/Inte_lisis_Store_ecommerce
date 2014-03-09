<?php
$cron = dirname(__FILE__).'/cron-pendingjobs.php';

require_once(dirname(__FILE__).'/init.php');

/* Esto esta causando problemas
if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
	ini_set('open_basedir', ini_get('open_basedir').':'.GetConfig('syncDropboxDir').':'.GetConfig('syncArchiveDir'));
*/

$Clave = substr($GLOBALS['ISC_CLASS_DB']->TablePrefix, 0, strlen($GLOBALS['ISC_CLASS_DB']->TablePrefix)-1);
$lockfile = GetConfig('syncArchiveDir').'cronlocks/'.$Clave.'-pendingjobs.lock';

define('ISC_CRON_PENDINGJOBS_LOCKFILE', $lockfile);

function cronLockFileExists(){
	if(file_exists(ISC_CRON_PENDINGJOBS_LOCKFILE)){
		return true;
	}
	else {
		return false;
	}
}

function createCronLockFile(){
	if(!cronLockFileExists()){
		if(file_put_contents(ISC_CRON_PENDINGJOBS_LOCKFILE, time())){
			return true;
		}
		else {
			print('Error al intentar crear el archivo de lock "'.ISC_CRON_PENDINGJOBS_LOCKFILE.'"'.PHP_EOL);
			return false;
		}
	}
	else{
		return false;
	}
}

// Check directories
$checkdirs = array(
		GetConfig('syncArchiveDir'),
		GetConfig('syncArchiveDir').$Clave,
		GetConfig('syncArchiveDir').$Clave.'/Errores',
		GetConfig('syncArchiveDir').$Clave.'/Imagenes',
		GetConfig('syncArchiveDir').$Clave.'/procesados',
		GetConfig('syncArchiveDir').$Clave.'/XMLMalFormado',
		GetConfig('syncArchiveDir').'cronlocks/',
		GetConfig('syncDropboxDir'),
		GetConfig('syncDropboxDir').$Clave,
		GetConfig('syncDropboxDir').$Clave.'/Errores',
		GetConfig('syncDropboxDir').$Clave.'/Imagenes',
		GetConfig('syncDropboxDir').$Clave.'/procesados',
);

foreach($checkdirs as $dir){
	if(!is_dir($dir)) isc_mkdir($dir, '777');
}

if(cronLockFileExists()){
	print 'No se ejecuto cron porque se esta ejecutando en otro proceso.'.PHP_EOL;
	ob_flush();
	exit(0);
}
else {
	if(createCronLockFile()){
		print 'Archivo lockfile "'.ISC_CRON_PENDINGJOBS_LOCKFILE.'" creado'.PHP_EOL;
		ob_flush();
	}
	else {
		exit(0);
	}
}

$i = 0;

while($i<5)
{
sleep(10);
$out = shell_exec('php '.$cron);
print($out);
ob_flush();
$i++;
}

if(!unlink(ISC_CRON_PENDINGJOBS_LOCKFILE)){
	print('Error al eliminar el archivo lock "'.ISC_CRON_PENDINGJOBS_LOCKFILE.'".'.PHP_EOL);
}
else {
	print 'Archivo lockfile "'.ISC_CRON_PENDINGJOBS_LOCKFILE.'" eliminado.'.PHP_EOL;
}

