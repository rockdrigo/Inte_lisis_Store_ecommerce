<?php

interface Interspire_TaskManagerInterface
{
	/**
	* Create a task in a specific queue
	*
	* @param string $queue Name of queue to place task in
	* @param string $class Class name that contains a perform method for this job
	* @param array $data An array of data to send to the job handler (typically an array of key/value pairs but can also be a plain array)
	* @param int $time Set a specific time for this job to execute; the job will not be processed before this time
	* @return mixed A task identifier, the exact type of which may differ depending on the implementation
	* @throws Interspire_TaskManager_InvalidCallbackException
	* @throws Interspire_TaskManager_InvalidArgumentException
	*/
	public static function createTask($queue, $callback, $data = array(), $time = Interspire_TaskManager::TIME_NOW);

	/**
	* Handle a browser request to trigger the next task. This may or may not do anything, depending on the implementation.
	*
	* @return void
	*/
	public static function handleTriggerRequest();

	/**
	* Return an HTML string which can be inserted into a browser response to instruct it to trigger the task manager script. This may or may not do anything, depending on the implementation.
	*
	* @return string
	*/
	public static function getTriggerHtml();

	/**
	* Returns true if tasks are waiting to be run.
	*
	* @return bool
	*/
	public static function hasTasks();
}
