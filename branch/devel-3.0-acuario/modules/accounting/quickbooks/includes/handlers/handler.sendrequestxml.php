<?php

class ACCOUNTING_QUICKBOOKS_HANDLER_SENDREQUESTXML extends ACCOUNTING_QUICKBOOKS_HANDLER_BASE
{
	protected $inputVars = array("ticket", "strHCPResponse", "strCompanyFileName", "qbXMLCountry", "qbXMLMajorVers", "qbXMLMinorVers");

	/**
	 * Handle the handler operation
	 *
	 * Method is the main function that will do the sendRequestXML handling
	 *
	 * @access public
	 * @return object The sendRequestXMLResultSOAPOut object containing the clientVersion result
	 */
	public function handleSoapRequest()
	{
		/**
		 * Do our authenticity check
		 */
		if (!$this->checkInput()) {
			return new sendRequestXMLResultSOAPOut();
		}

		/**
		 * If this is the first time then we also get some extra data to record
		 */
		if ($this->soapObj->strHCPResponse !== "") {
			file_put_contents(dirname(__FILE__) . "/output.xml", "");
			$this->accounting->logDebug("SendXMLRequest First Go", $this->soapObj);
			$this->handleFirstGo();
		}

		$currentSpool = $this->accounting->getCurrentSpool();

		$this->accounting->logDebug("SendRequestXML Current SpoolID: " . $currentSpool["id"], $currentSpool);

		/**
		 * No more spools to process or there was an error. We should already have picked up the 'no more spools'
		 * scenario before so it is most likely an error which would already have been logged (hopefully)
		 */
		if (!is_array($currentSpool)) {
			$this->accounting->logDebug("SendXMLRequest getcurrent spool failed!");
			return new sendRequestXMLResultSOAPOut();
		}

		/**
		 * Try to exec the spool service. If for some reason we fail then try to set and run the next one, just
		 * in case
		 */
		while (true) {

			$sendRequestXML = $this->accounting->runService($currentSpool);

			if ($sendRequestXML !== false && trim($sendRequestXML) !== "") {
				break;
			}

			/**
			 * We errored somewhere. The error should be logged so just set the next spool if we can. If we can't find
			 * one then just return (meaning that there is none left)
			 */
			$nextSpool = $this->accounting->setNextSpool($currentSpool);

			if (!$nextSpool || is_null($nextSpool)) {
				$this->accounting->logDebug("SendRequestXML FAILED. No more spools found");
				break;
			}

			$this->accounting->logDebug("SendRequestXML FAILED. Next SpoolID is " . $nextSpool["id"], array("current" => $currentSpool, "next" => $nextSpool));
			$currentSpool = $nextSpool;
		}

		if (!is_string($sendRequestXML)) {
			$sendRequestXML = "";
		}

		return new sendRequestXMLResultSOAPOut($sendRequestXML);
	}

	/**
	 * Handle extra data on first go
	 *
	 * Method will handle the extra data that gets sent on the first go
	 *
	 * @access private
	 * @return void
	 */
	private function handleFirstGo()
	{
		$importSessData = array(
							"QBXML_VERSION" => (float)($this->soapObj->qbXMLMajorVers . "." . $this->soapObj->qbXMLMinorVers),
							"CLIENT_COUNTRY" => $this->soapObj->qbXMLCountry,
							"COMPANY_DATA" => $this->soapObj->strHCPResponse
		);

		/**
		 * Use the store country as a fallback if the $this->soapObj->qbXMLCountry variable is empty
		 */
		if (trim($this->soapObj->qbXMLCountry) == '') {
			$importSessData["CLIENT_COUNTRY"] = GetCountryISO2ByName(GetConfig("CompanyCountry"));
		}

		$this->accounting->setImportSessionValue("CompanyData", $importSessData);
	}
}

/**
 * The SOAP output object
 *
 * Class is the serverVersion SOAP output object
 */
class sendRequestXMLResultSOAPOut
{
	public $sendRequestXMLResult;

	public function __construct($msg="")
	{
		$this->sendRequestXMLResult = $msg;
	}
}

