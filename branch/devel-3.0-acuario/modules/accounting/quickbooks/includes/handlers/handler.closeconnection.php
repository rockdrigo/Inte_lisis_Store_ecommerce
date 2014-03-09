<?php

class ACCOUNTING_QUICKBOOKS_HANDLER_CLOSECONNECTION extends ACCOUNTING_QUICKBOOKS_HANDLER_BASE
{
	protected $soapRequiredVars = array("ticket");

	/**
	 * Handle the handler operation
	 *
	 * Method is the main function that will do the closeConnection handling
	 *
	 * @access public
	 * @return object The serverVersionResultSOAPOut object containing the closeConnection result
	 */
	public function handleSoapRequest()
	{
		/**
		 * If we're not logged in then there is nothing to close
		 */
		if (!$this->checkInput()) {
			return new closeConnectionResultSOAPOut("Unable to close connection");
		}

		/**
		 * OK, now we remove our successfully executed spools, unset our import session and also unset our lock
		 * file. All this is handled by the accounting object
		 */
		if (!$this->accounting->closeImport()) {
			$this->accounting->setImportSessionError("Unable to close connection", false);
			return new closeConnectionResultSOAPOut("Unable to close connection");
		}

		return new closeConnectionResultSOAPOut("Interspire Shopping Cart connection closed successfully");
	}
}

/**
 * The SOAP output object
 *
 * Class is the serverVersion SOAP output object
 */
class closeConnectionResultSOAPOut
{
	public $closeConnectionResult;

	public function __construct($msg='')
	{
		$this->closeConnectionResult = $msg;
	}
}
