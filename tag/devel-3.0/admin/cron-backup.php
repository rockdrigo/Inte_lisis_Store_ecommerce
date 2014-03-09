<?php
// CLI only
if(isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD'])) {
	exit;
}

define('ISC_CLI', true);
define('NO_SESSION', true);

require_once(dirname(__FILE__).'/init.php');

// Cron based backup script
$GLOBALS['ISC_CLASS_ADMIN_BACKUP'] = GetClass('ISC_ADMIN_BACKUP');

// Automatic backups disabled
if(!GetConfig('BackupsAutomatic')) {
	exit;
}

// Set request variables for the backup function
$_REQUEST = array(
	"backupmethod" => GetConfig('BackupsAutomaticMethod'),
	"backupdb" => GetConfig('BackupsAutomaticDatabase'),
	"backupimages" => GetConfig('BackupsAutomaticImages'),
	"backupdigitalproducts" => GetConfig('BackupsAutomaticDownloads')
);

// Now run the backup function
$GLOBALS['ISC_CLASS_ADMIN_BACKUP']->Verbose = false;
$GLOBALS['ISC_CLASS_ADMIN_BACKUP']->CreateBackup2();