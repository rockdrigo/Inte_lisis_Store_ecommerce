<?php

define('NO_SESSION', true);
require_once(dirname(__FILE__).'/admin/init.php');
$listener = new ISC_ADMIN_EBAY_NOTIFICATIONS_LISTENER();
$listener->handleRequest();
