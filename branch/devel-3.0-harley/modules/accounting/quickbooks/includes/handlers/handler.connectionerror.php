<?php

class ACCOUNTING_QUICKBOOKS_HANDLER_CONNECTIONERROR extends ACCOUNTING_QUICKBOOKS_HANDLER_BASE
{
	protected $inputVars = array("ticket", "hresult", "message");

	/**
	 * Handle the handler operation
	 *
	 * Method is the main function that will do the connectionError handling
	 *
	 * @access public
	 * @return object The connectionErrorResultSOAPOut object containing the connectionError result
	 */
	public function handleSoapRequest()
	{
		/**
		 * Do our authenticity check
		 */
		if (!$this->checkInput()) {
			return new connectionErrorResultSOAPOut();
		}

		$this->accounting->logError("Connection error", $this->soapObj->message);

		/**
		 * Close the import (basically the same thing as the closeconnection handler)
		 */
		if (!$this->accounting->closeImport()) {
			return new connectionErrorResultSOAPOut();
		}

		return new connectionErrorResultSOAPOut();
	}
}

/**
 * The SOAP output object
 *
 * Class is the serverVersion SOAP output object
 */
class connectionErrorResultSOAPOut
{
	public $connectionErrorResult;

	public function __construct($msg="")
	{
		$this->connectionErrorResult = $msg;
	}
}
