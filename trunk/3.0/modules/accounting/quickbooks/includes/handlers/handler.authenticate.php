<?php

class ACCOUNTING_QUICKBOOKS_HANDLER_AUTHENTICATE extends ACCOUNTING_QUICKBOOKS_HANDLER_BASE
{
	protected $soapRequiredVars = array("strUserName", "strPassword");

	/**
	 * Generate the UUID
	 *
	 * Method will generate the UUID needed for communicating back and forth with QuickBoks
	 *
	 * @access private
	 * @param string $prefix The optional prefix for the generated UUID
	 * @return string The UUID
	 */
	private function generateUUID($prefix="")
	{
		$chars = uniqid(md5(mt_rand()));
		$uuid = substr($chars,0,8) . "-";
		$uuid .= substr($chars,8,4) . "-";
		$uuid .= substr($chars,12,4) . "-";
		$uuid .= substr($chars,16,4) . "-";
		$uuid .= substr($chars,20,12);

		return $prefix . $uuid;
	}

	/**
	 * Handle the handler operation
	 *
	 * Method is the main function that will do the authentication handling
	 *
	 * @access public
	 * @return object The authenticateResultSOAPOut object containing the authentication result
	 */
	public function handleSoapRequest()
	{
		$uuid = $this->generateUUID();

		if (!$this->checkInput()) {
			return new authenticateResultSOAPOut($uuid, "nvu");
		}

		$password = $this->accounting->getSetupVariable("password");
		$username = $this->accounting->getSetupVariable("username");

		/**
		 * Check our credentials
		 */
		if ($this->soapObj->strUserName !== $username || $this->soapObj->strPassword !== $password) {
			// then the login info is bad.
			$this->accounting->setImportSessionError("Invalid username/password");
			return new authenticateResultSOAPOut($uuid, "nvu");

		/**
		 * Credentials are good, so let's check to see if we are already running
		 */
		}
			/**
			*  else if ($this->accounting->checkImportSessionLockLease(true)) {return new authenticateResultSOAPOut($uuid, "none");}
			* not going to do this one - basically it's checking to see if Quickbooks is minimized or not. Was causing a problem
			*/

		/**
		 * OK, we've been authenticated. Next we need to setup our by creating our lock file and saving our UUID
		 * to our import session. This is all handled with the $this->accounting->initImport() method.
		 */
		if (!$this->accounting->initImport($uuid)) {
			$this->accounting->setImportSessionError($this->accounting->getErrors(), false);

			/**
			 * Close of the import so we can basically reset everything back to normal
			 */
			$this->accounting->closeImport();

			return new authenticateResultSOAPOut($uuid, "none");
		}

		return new authenticateResultSOAPOut($uuid, "");
	}
}

/**
 * The SOAP output object
 *
 * Class is the authentication SOAP output object
 */
class authenticateResultSOAPOut
{
	public $authenticateResult;

	public function __construct($ticket, $status)
	{
		$this->authenticateResult = array($ticket, $status);
	}
}
