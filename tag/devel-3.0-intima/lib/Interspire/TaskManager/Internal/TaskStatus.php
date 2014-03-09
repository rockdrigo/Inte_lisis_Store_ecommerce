<?php

/**
* Very, very basic TaskStatus model for the Internal task manager
*/
class Interspire_TaskManager_Internal_TaskStatus
{
	public $id;
	public $begin;
	public $end;
	public $success;
	public $message;

	public static function find($id)
	{
		$row = $GLOBALS['ISC_CLASS_DB']->FetchRow("SELECT `id`, `begin`, `end`, `success`, `message` FROM `[|PREFIX|]task_status` WHERE `id` = " . (int)$id);

		if (!$row) {
			return false;
		}

		return new self($row['id'], $row['begin'], $row['end'], $row['success'], $row['message']);
	}

	public function __construct($id, $begin, $end, $success, $message)
	{
		$this->id = (int)$id;
		$this->begin = (int)$begin;
		$this->end = (int)$end;
		$this->success = !!(int)$success;
		$this->message = (string)$message;
	}
}
