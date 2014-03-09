<?php

/**
* ISC_ADMIN_EBAY_API provides functionality to perform requests to the eBay API.
*
* Usage:
* $api = new ISC_ADMIN_EBAY_API();
* $xml = $api->getBaseXMLRequest('GeteBayDetails');
* $api->DoRequest('GeteBayDetails', $xml);
*
*/
class ISC_ADMIN_EBAY_API extends ISC_ADMIN_BASE {
	/**
	 * @var constant Compatabily Level of eBay API
	 */
	const COMPATABILITY_LEVEL = 655;

	/**
	 * @var constant Gateway URL of eBay Sandbox API
	 */
	const EBAY_SANDBOX_URL = 'https://api.sandbox.ebay.com/ws/api.dll';

	/**
	 * @var constant Gateway URL of eBay Production API
	 */
	const EBAY_PRODUCTION_URL = 'https://api.ebay.com/ws/api.dll';

	/**
	* @var int The eBay site id
	*/
	private $eBaySiteId = 0;

	public function __construct($eBaySiteId = 0)
	{
		parent::__construct();

		$this->eBaySiteId = $eBaySiteId;

		$this->engine->LoadLangFile('ebay.api');
	}

	/**
	* Gets the eBay HTTP headers for a specific request
	*
	* @param string $requestName The name of the request to get headers for
	* @return array The HTTP headers
	*/
	private function getXMLRequestHeaders($requestName)
	{

		// defining header with the correct configs
		$headers = array (
			'X-EBAY-API-COMPATIBILITY-LEVEL: ' . self::COMPATABILITY_LEVEL,
			'X-EBAY-API-DEV-NAME: ' . GetConfig("EbayDevId"),
			'X-EBAY-API-APP-NAME: ' . GetConfig("EbayAppId"),
			'X-EBAY-API-CERT-NAME: ' . GetConfig("EbayCertId"),
			'X-EBAY-API-CALL-NAME: ' . $requestName,
			'X-EBAY-API-SITEID: ' . $this->eBaySiteId,
		);

		// return the header
		return $headers;
	}

	/**
	* Builds a base SimpleXML object for a specific request
	*
	* @param string $requestName The name of the request
	* @return SimpleXMLElement The SimpleXMLElement object for the request
	*/
	public function getBaseXMLRequest($requestName)
	{
		$requestCommand = $requestName . 'Request';

		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><' . $requestCommand . ' xmlns="urn:ebay:apis:eBLBaseComponents" />');
		$requesterCredentials = $xml->addChild('RequesterCredentials');
		$requesterCredentials->addChild('eBayAuthToken', GetConfig('EbayUserToken'));

		return $xml;
	}

	/**
	* Sends a request to the eBay API
	*
	* @param string $requestName The name of the request to perform
	* @param SimpleXMLElement $requestXMLObject The XML request object
	* @return SimpleXMLElement The XML response object
	*/
	public function DoRequest($requestName, $requestXMLObject)
	{
		$requestXML = $requestXMLObject->asXML();

		$this->log->LogSystemDebug('ebay', 'API Request: ' . $requestName, isc_html_escape($requestXML));

		//initialise a CURL session
		$connection = curl_init();

		// get the url we're using
		if (GetConfig('EbayTestMode') == 'production') {
			$ebayURL = self::EBAY_PRODUCTION_URL;
		}
		else {
			$ebayURL = self::EBAY_SANDBOX_URL;
		}

		// Setup our CURL headers
		curl_setopt_array($connection,
			array(
				CURLOPT_URL => $ebayURL,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HEADER => false,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $requestXML,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 60,
				CURLOPT_HTTPHEADER => $this->getXMLRequestHeaders($requestName)
			)
		);

		//Send the Request
		$response = curl_exec($connection);

		$this->log->LogSystemDebug('ebay', 'API Response: ' . $requestName, isc_html_escape($response));

		if ($response === false) {
			$curlErrorMessage = curl_error($connection) . ' (' . curl_errno($connection) . ')';
			throw new ISC_EBAY_API_CONNECTION_EXCEPTION($requestName, $requestXML, $curlErrorMessage);
		}

		$responseXMLObject = new SimpleXMLElement($response);

		// did the request fail?
		if ((string)$responseXMLObject->Ack == 'Failure') {
			$errorMessage = "";
			foreach ($responseXMLObject->Errors as $error) {
				if ($errorMessage) {
					$errorMessage .= "\n";
				}
				$code = (string)$error->ErrorCode;
				$errorMessage .= (string)$error->LongMessage . ' (' . $code . ')';
			}

			throw new ISC_EBAY_API_REQUEST_EXCEPTION((string)$responseXMLObject->Ack, $requestName, $requestXML, $responseXMLObject, $errorMessage);
		}

		return $responseXMLObject;
	}
}

class ISC_EBAY_API_EXCEPTION extends Exception {
	private $requestXML;
	private $requestName;

	/**
	* @param string $requestName The name of the request
	* @param string $requestXML The XML of the request
	* @param string $errorMessage The error message
	* @return ISC_EBAY_API_REQUEST_EXCEPTION
	*/
	public function __construct($requestName, $requestXML, $errorMessage)
	{
		$this->requestName = $requestName;
		$this->requestXML = $requestXML;

		parent::__construct($errorMessage);
	}

	/**
	* Gets the name of the request that triggered this exception
	*
	* @return string The request name
	*/
	public function getRequestName()
	{
		return $this->operation;
	}

	/**
	* Gets the XML request that triggered this exception
	*
	* @return string The XML request
	*/
	public function getRequestXML()
	{
		return $this->requestXML;
	}
}

class ISC_EBAY_API_CONNECTION_EXCEPTION extends ISC_EBAY_API_EXCEPTION { }

class ISC_EBAY_API_REQUEST_EXCEPTION extends ISC_EBAY_API_EXCEPTION {
	protected $ack;

	private $responseXML;

	/**
	* put your comment there...
	*
	* @param mixed $ack
	* @param mixed $requestName
	* @param mixed $requestXML
	* @param SimpleXMLElement $responseXML
	* @param mixed $errorMessage
	* @return ISC_EBAY_API_REQUEST_EXCEPTION
	*/
	public function __construct($ack, $requestName, $requestXML, $responseXML, $errorMessage)
	{
		$this->ack = $ack;
		$this->responseXML = $responseXML;

		parent::__construct($requestName, $requestXML, $errorMessage);
	}

	/**
	* Get the acknowledgement code
	*
	* @return string The Ack code
	*/
	public function getAck()
	{
		return $this->ack;
	}

	/**
	* Gets the response XML object
	*
	* @return SimpleXMLElement The response
	*/
	public function getResponseXML()
	{
		return $this->responseXML;
	}
}
