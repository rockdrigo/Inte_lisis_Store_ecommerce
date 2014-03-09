<?php

define('ISC_AJAX', 1);

include(dirname(__FILE__)."/init.php");

// this is necessary for the flash uploader as it doesnt send cookies, but we can restore the session
if (!isset($_COOKIE['STORESUITE_CP_TOKEN']) && isset($_SESSION['STORESUITE_CP_TOKEN'])) {
	$_COOKIE['STORESUITE_CP_TOKEN'] = $_SESSION['STORESUITE_CP_TOKEN'];
}

if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->IsLoggedIn()) {
	$className = 'ISC_ADMIN_REMOTE';
	if (array_key_exists('remoteSection', $_REQUEST) && trim($_REQUEST['remoteSection']) !== '') {
		$containNonAlphaNum = preg_match('/[^a-zA-Z0-9_]/', trim($_REQUEST['remoteSection']));
		if ($containNonAlphaNum) {
			exit;
		}
		$className .= '_' . isc_strtoupper(trim($_REQUEST['remoteSection']));
	}

	$GLOBALS['ISC_CLASS_ADMIN_REMOTE'] = GetClass($className);
	$GLOBALS['ISC_CLASS_ADMIN_REMOTE']->HandleToDo();
}