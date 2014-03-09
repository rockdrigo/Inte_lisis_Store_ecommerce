<?php
/**
* This file sets up an environment which can be included by resque for processing jobs within this store instance.
*/
define('ISC_CLI', true);
define('NO_SESSION', true);
include(dirname(dirname(__FILE__)) . '/admin/init.php');
