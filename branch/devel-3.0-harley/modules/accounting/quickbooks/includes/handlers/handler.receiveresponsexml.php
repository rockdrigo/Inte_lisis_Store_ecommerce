<?php

class ACCOUNTING_QUICKBOOKS_HANDLER_RECEIVERESPONSEXML extends ACCOUNTING_QUICKBOOKS_HANDLER_BASE
{
	private $spool;
	private $response;
	private $responseAttribs;
	private $nextSpoolSet;

	protected $inputVars = array("ticket", "response", "hresult", "message");

	/**
	 * Handle the handler operation
	 *
	 * Method is the main function that will do the receiveResponseXML handling
	 *
	 * @access public
	 * @return object The receiveResponseXMLResultSOAPOut object containing the receiveResponseXML result
	 */
	public function handleSoapRequest()
	{
		/**
		 * Do our authenticity check
		 */
		if (!$this->checkInput()) {
			return new receiveResponseXMLResultSOAPOut();
		}

		/**
		 * Next we need to find the current spool
		 */
		$this->spool = $this->accounting->getCurrentSpool();
		if (!$this->spool) {
			$this->accounting->setImportSessionError("Cannot find current spool to handle response");
			return $this->returnWithPercentageRate();
		}

		$this->accounting->logDebug("ReceiveResponseXML Current SpoolID: " . $this->spool["id"], $this->spool);
		$this->accounting->logDebug("ReceiveResponseXML Current Response", $this->soapObj);

		/**
		 * Then we parse our response
		 */
		$this->response = $this->parseResponse();
		if (!$this->response) {
			$this->accounting->logDebug("ReceiveResponseXML invalid response", $this->soapObj);
			$this->accounting->setInternalSpoolError($this->spool);
			return $this->returnWithPercentageRate();
		}

		/**
		 * Set the response to the accounting record. Only record the "data" element
		 */
		if (!$this->accounting->setSpoolResponse($this->spool, $this->response["data"])) {
			$this->accounting->logDebug("ReceiveResponseXML unable to set spool response for SpoolID: " . $this->spool["id"], $this->response);
			$this->accounting->setInternalSpoolError($this->spool);
			$this->accounting->setImportSessionError($this->accounting->getErrors());
			return $this->returnWithPercentageRate();
		}

		/**
		 * Parse out and set the error number and message if we can
		 */
		$error = $this->parseOutError();

		if (is_array($error)) {
			$this->accounting->setSpoolError($this->spool, $error["errno"], $error["errmsg"]);
		}

		/**
		 * Re-get the spool again so we can get the response and error message
		 */
		$spoolId = $this->spool["id"];
		$this->spool = $this->accounting->getSpool($spoolId);

		/**
		 * We've got the current spool and the response from QB. Now we run the associated service
		 */
		$this->accounting->runService($this->spool, true);

		/**
		 * Re-get the spool again as it have have created children when running the request
		 */
		$this->spool = $this->accounting->getSpool($spoolId);

		return $this->returnWithPercentageRate();
	}

	/**
	 * Parse the qbXML response
	 *
	 * Method will convert the qbXML response into an array. The return will be an array with the "attributes" key
	 * being an array of all the attributes, and "data" will contain the data array of the XML
	 *
	 * @access private
	 * @return array The parsed qbXML on success, FALSE otherwise
	 */
	private function parseResponse()
	{
		if (trim($this->soapObj->response) == '') {
			$this->accounting->logError("ReceiveResponseXML Error", $this->soapObj);
			$this->accounting->setImportSessionError("Empty response XML, unable to handle");
			return false;
		}

		/**
		 * QBWC interprets the trademark symbol incorrectly so we need to fix it up here
		 */
		$xml = $this->soapObj->response;
		$xml = str_replace("&#153;", "&#8482;", $xml);

		try {
			$obj = @simplexml_load_string(trim($xml));
			$obj = @$obj->xpath("/QBXML/QBXMLMsgsRs/*[1]");

			if (!$obj) {
				throw new Exception();
			}

			$converted = $this->convertResponseToArray($obj);

			if (!is_array($converted) || !isset($converted[0])) {
				throw new Exception();
			}
		} catch(Exception $e) {
			$this->accounting->setImportSessionError(array("Cannot read/convert the response", $xml));
			return false;
		}

		$converted = $converted[0];
		$parsed = array(
						"attributes" => array(),
						"data" => array()
		);

		if (array_key_exists("@attributes", $converted)) {
			$parsed["attributes"] = $converted["@attributes"];
			unset($converted["@attributes"]);
		}

		$parsed["data"] = current($converted);

		return $parsed;
	}

	/**
	 * Parse out the error number and message from the response
	 *
	 * Method will parse out the error number and message from the response
	 *
	 * @access private
	 * @return array An array containing the error number and error message, NULL if no error was found, FALSE on error
	 */
	private function parseOutError()
	{
		if (!is_array($this->response) || !array_key_exists("attributes", $this->response)) {
			return false;
		}

		/**
		 * Empty attributes. Don't know if thats good or not so just return NULL
		 */
		if (!is_array($this->response["attributes"]) || !array_key_exists("statusCode", $this->response["attributes"])) {
			return null;
		}

		/**
		 * There was no error (good)
		 */
		if ((int)$this->response["attributes"]["statusCode"] == 0) {
			return null;
		}

		/**
		 * There was an error
		 */
		$error = array(
			"errno" => $this->response["attributes"]["statusCode"],
			"errmsg" => trim(@$this->response["attributes"]["statusMessage"])
		);

		return $error;
	}

	/**
	 * Convert the SimpleXML object to an array
	 *
	 * Method will convert the SimpleXML object to an array. It is used recursively
	 *
	 * @access private
	 * @param object $xml The SimpleXML object to convert
	 * @return array The converted SimpleXML object on success, FALSE on error
	 */
	private function convertResponseToArray($xml)
	{
		if (is_scalar($xml)) {
			return $xml;
		}

		$converted = (array)$xml;

		foreach (array_keys($converted) as $key) {
			if (is_scalar($converted[$key])) {
				$converted[$key] = (string)$converted[$key];
			} else {
				$converted[$key] = $this->{__FUNCTION__}($converted[$key]);
			}
		}

		return $converted;
	}

	/**
	 * Return the output with the percentage completed
	 *
	 * Method will return the class output with the percentage completed as the output argument value
	 *
	 * @access private
	 * @return object The output class object
	 */
	private function returnWithPercentageRate()
	{
		/**
		 * If setting the next spool fails then we're done
		 */
		if (!$this->accounting->setNextSpool($this->spool)) {
			return new receiveResponseXMLResultSOAPOut(100);
		}

		$total = $this->accounting->getSpoolCount('', false, false, false);
		$executed = $this->accounting->getSpoolCount('', false, false, true);

		$this->accounting->logDebug("Percentage rate", array("total" => $total, "executed" => $executed));

		if ($total === false || $executed === false) {
			$percentage = 100;
		} else if ($total == $executed) {
			$percentage = 100;
		} else {
			$percentage = floor((100/$total) * $executed);
		}

		return new receiveResponseXMLResultSOAPOut($percentage);
	}
}

/**
 * The SOAP output object
 *
 * Class is the serverVersion SOAP output object
 */
class receiveResponseXMLResultSOAPOut
{
	public $receiveResponseXMLResult;

	public function __construct($msg="")
	{
		$this->receiveResponseXMLResult = $msg;
	}
}
