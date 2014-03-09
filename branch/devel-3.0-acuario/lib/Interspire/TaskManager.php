<?php

class Interspire_TaskManager extends Interspire_TaskManager_Base implements Interspire_TaskManagerInterface
{
	const TIME_NOW = 0;

	public static function createTask($queue, $class, $data = array(), $time = self::TIME_NOW)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		try {
			$result = parent::createTask($queue, $class, $data, $time);
			if ($result) {
				$log->LogSystemDebug('general', 'TaskManager::createTask successfully added task to "'. $queue . '" queue');
			} else {
				$log->LogSystemError('general', 'TaskManager::createTask failed with no exception');
			}
			return $result;
		} catch (Exception $exception) {
			$log->LogSystemError('general', 'TaskManager::createTask failed with exception', $exception->__toString());
			return false;
		}
	}
}
