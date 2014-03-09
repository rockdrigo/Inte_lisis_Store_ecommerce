<?php

abstract class ACCOUNTING_QUICKBOOKS_HANDLER_BASE
{
	protected $accounting;
	protected $soapObj;

	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @param object $soapObj The requested SOAP input object
	 * @param object $accounting The accounting object
	 * @return void
	 */
	public function __construct($soapObj, $accounting)
	{
		$this->soapObj = $soapObj;
		$this->accounting = $accounting;
	}

	/**
	 * All handers that extend off this should have this method defined
	 */
	abstract protected function handleSoapRequest();

	/**
	 * Check SOAP object
	 *
	 * Method will check to see if the required fields are present in the $this->soapObj SOAP object
	 *
	 * @access protected
	 * @param bool $logOnError TRUE to log error if check fails. Default is TRUE
	 * @return bool true if all fields are present, FALSE if not
	 */
	protected function checkInput($logOnError=true)
	{
		if (!isset($this->soapRequiredVars) || !is_array($this->soapRequiredVars)) {
			return true;
		}

		foreach ($this->soapRequiredVars as $field) {
			if (!isset($this->soapObj->$field)) {

				if ($logOnError) {
					$this->accounting->setImportSessionError(array("Invalid/Missing variables in " . get_class($this), $this->soapObj));
				}

				return false;
			}
		}

		return true;
	}
}
