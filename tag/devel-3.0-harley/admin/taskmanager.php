<?php

define('NO_SESSION', true);
require_once(dirname(__FILE__).'/init.php');
Interspire_TaskManager::handleTriggerRequest();
