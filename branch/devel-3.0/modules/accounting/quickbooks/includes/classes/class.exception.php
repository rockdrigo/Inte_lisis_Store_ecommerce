<?php

class QBException extends Exception
{
	protected $logSubject;
	protected $logMessage;

	public function __construct($message, $code=0, $previous=null)
	{
		$this->logSubject = null;
		$this->logMessage = null;

		if (is_array($message)) {
			$this->logSubject = $message[0];
			$this->logMessage = $message[1];
			$message = $message[0];
		} else {
			$this->logSubject = $message;
			$this->logMessage = $code;
			$code = 0;
		}

		$message = (string)$message;

		parent::__construct($message, $code); //, $previous
	}

	public function getQBMessage()
	{
		if (!is_null($this->logSubject)) {
			if (!is_scalar($this->logMessage)) {
				$message = print_r($this->logMessage, true);
			} else {
				$message = $this->logMessage;
			}

			return array($this->logSubject, $this->logMessage);
		}

		return parent::getMessage();
	}
}
