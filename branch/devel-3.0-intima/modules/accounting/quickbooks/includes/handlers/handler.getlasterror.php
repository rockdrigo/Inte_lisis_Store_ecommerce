<?php

class ACCOUNTING_QUICKBOOKS_HANDLER_GETLASTERROR extends ACCOUNTING_QUICKBOOKS_HANDLER_BASE
{
	protected $inputVars = array("ticket");

	/**
	 * Handle the handler operation
	 *
	 * Method is the main function that will do the getLastError handling
	 *
	 * @access public
	 * @return object The getLastErrorResultSOAPOut object containing the getLastError result
	 */
	public function handleSoapRequest()
	{
		/**
		 * Do our authenticity check
		 */
		if (!$this->checkInput()) {
			return new getLastErrorResultSOAPOut();
		}

		$msg = $this->accounting->getImportSessionError();

		if ($msg !== "") {
			return new getLastErrorResultSOAPOut($msg);
		}

		return new getLastErrorResultSOAPOut();
	}
}

/**
 * The SOAP output object
 *
 * Class is the getLastError SOAP output object
 */
class getLastErrorResultSOAPOut
{
	public $getLastErrorResult;

	public function __construct($msg="")
	{
		$this->getLastErrorResult = $msg;
	}
}
