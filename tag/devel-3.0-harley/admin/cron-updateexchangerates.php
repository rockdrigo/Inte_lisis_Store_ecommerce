<?php
// CLI only
if(isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD'])) {
	exit;
}

define('ISC_CLI', true);
define('NO_SESSION', true);

require_once(dirname(__FILE__).'/init.php');

// Now run the cron function to update the currencies
UpdateCurrenciesFromCron();