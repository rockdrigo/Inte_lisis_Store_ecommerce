<?php
define('NO_SESSION', true);
require_once(dirname(__FILE__) . '/admin/init.php');
Interspire_TaskManager::handleTriggerRequest();
