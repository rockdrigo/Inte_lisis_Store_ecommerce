<?php

/**
* This class determines the task manager implementation to use on the current store
*/
if (class_exists('Interspire_TaskManager_Resque')) {
	abstract class Interspire_TaskManager_Base extends Interspire_TaskManager_Resque { }
} else {
	abstract class Interspire_TaskManager_Base extends Interspire_TaskManager_Internal { }
}
