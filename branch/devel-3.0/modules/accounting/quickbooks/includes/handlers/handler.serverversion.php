<?php

class ACCOUNTING_QUICKBOOKS_HANDLER_SERVERVERSION extends ACCOUNTING_QUICKBOOKS_HANDLER_BASE
{
	protected $soapRequiredVars = array();

	/**
	 * Handle the handler operation
	 *
	 * Method is the main function that will do the serverVersion handling
	 *
	 * @access public
	 * @return object The serverVersionResultSOAPOut object containing the clientVersion result
	 */
	public function handleSoapRequest()
	{
		/**
		 * Do our authenticity check
		 */
		if (!$this->checkInput()) {
			return new serverVersionResultSOAPOut("");
		}

		return new serverVersionResultSOAPOut("v" . $this->account->getSupportedXMLVersion());
	}
}

/**
 * The SOAP output object
 *
 * Class is the serverVersion SOAP output object
 */
class serverVersionResultSOAPOut
{
	public $serverVersionResult;

	public function __construct($msg="")
	{
		$this->serverVersionResult = $msg;
	}
}
