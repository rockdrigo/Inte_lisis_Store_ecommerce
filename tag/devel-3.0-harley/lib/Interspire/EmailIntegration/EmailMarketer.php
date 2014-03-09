<?php

/**
* This class, and other EmailIntegration_EmailMarketer_* classes, implements a PHP library for accessing the Email Marketer XML api.
*
* Note: this class, though mostly app-neutral, contains PostToRemoteFileAndGetResponse() which is an ISC method and should be replaced with something cross-app
*/
class Interspire_EmailIntegration_EmailMarketer
{
	protected $_xmlApiUrl;

	protected $_username;

	protected $_usertoken;

	public function __construct ($url, $username, $usertoken)
	{
		$this->setXmlApiUrl($url);
		$this->setUsername($username);
		$this->setUsertoken($usertoken);
	}

	public function getXmlApiUrl ()
	{
		return $this->_xmlApiUrl;
	}

	/**
	* put your comment there...
	*
	* @param mixed $value
	* @return Interspire_EmailIntegration_EmailMarketer
	*/
	public function setXmlApiUrl ($value)
	{
		$this->_xmlApiUrl = $value;
		return $this;
	}

	public function getUsername ()
	{
		return $this->_username;
	}

	/**
	* put your comment there...
	*
	* @param mixed $value
	* @return Interspire_EmailIntegration_EmailMarketer
	*/
	public function setUsername ($value)
	{
		$this->_username = $value;
		return $this;
	}

	public function getUsertoken ()
	{
		return $this->_usertoken;
	}

	/**
	* put your comment there...
	*
	* @param mixed $value
	* @return Interspire_EmailIntegration_EmailMarketer
	*/
	public function setUsertoken ($value)
	{
		$this->_usertoken = $value;
		return $this;
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function xmlApiTest ()
	{
		return $this->_xmlApiRequest('authentication', 'xmlapitest');
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function addSubscriberToList ($emailaddress, $mailinglist, $format = 'html', $confirmed = 'false', $customfields = array())
	{
		$details = array(
			'emailaddress' => $emailaddress,
			'mailinglist' => (int)$mailinglist,
			'format' => $format,
		);

		if ($confirmed) {
			$details['confirmed'] = 1;
		}

		if (!empty($customfields)) {
			// @hack because php arrays can't represent multiple 'item' entries as required by AddSubscriberToList, create that fragment to pass to xmlApiRequest (which will import it into the final xml)
			$dom = new DOMDocument();
			$xmlCustomFields = $dom->createElement('customfields');
			$dom->appendChild($xmlCustomFields);
			foreach ($customfields as $field => $value) {
				$item = $dom->createElement('item');
				$xmlCustomFields->appendChild($item);

				$item->appendChild($dom->createElement('fieldid', $field));

				if (is_array($value)) {
					// it seems that if an array needs to be sent to IEM it should be like...
					//
					// <item>
					// 	<fieldid>123</fieldid>
					// 	<value>Foo</value>
					// 	<value>Bar</value>
					// 	...
					// </item>
					//
					// I can't see any support for naming those array elements (like ideally <value id="whatever">)
					foreach ($value as $subkey => $subvalue) {
						$item->appendChild($dom->createElement('value', $subvalue));
					}
				} else {
					$item->appendChild($dom->createElement('value', $value));
				}
			}

			// it doesn't need to have an array key
			$details[] = $dom;
		}

		return $this->_xmlApiRequest('subscribers', 'AddSubscriberToList', $details);
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function deleteSubscriber ($list, $emailaddress)
	{
		return $this->_xmlApiRequest('subscribers', 'DeleteSubscriber', array(
			'emailaddress' => $emailaddress,
			'list' => $list,
		));
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function getCustomFields ($listids)
	{
		return $this->_xmlApiRequest('lists', 'GetCustomFields', array(
			'listids' => $listids,
		));
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function getLists ()
	{
		return $this->_xmlApiRequest('user', 'GetLists');
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function getSubscribers ($list, $searchinfo = array())
	{
		$searchinfo['List'] = $list;

		return $this->_xmlApiRequest('subscribers', 'GetSubscribers', array(
			'searchinfo' => $searchinfo,
		));

		/*
		example response:

		<response>
		<status>SUCCESS</status>
		<data><count>1</count>
		<subscriberlist>
			<item>
				<subscriberid>5</subscriberid>
				<emailaddress>test@example.com</emailaddress>
				<format>h</format>
				<subscribedate>1280910677</subscribedate>
				<confirmed>0</confirmed>
				<unsubscribed>0</unsubscribed>
				<bounced>0</bounced>
				<listid>1</listid>
			</item>
		</subscriberlist></data></response>
		*/
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function isSubscriberOnList ($email, $list)
	{
		return $this->_xmlApiRequest('subscribers', 'IsSubscriberOnList', array(
			'Email' => $email,
			'List' => $list,
		));
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function updateSubscriberIP ($emailaddress, $listid, $ipaddress)
	{
		return $this->_xmlApiRequest('subscribers', 'UpdateSubscriberIP', array(
			'emailaddress' => $emailaddress,
			'listid' => $listid,
			'ipaddress' => $ipaddress,
		));
	}

	/**
	* put your comment there...
	*
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function getAllListsForEmailAddress ($email, $listids = array(), $main_listid = 0)
	{
		return $this->_xmlApiRequest('subscribers', 'GetAllListsForEmailAddress', array(
			'email' => $email,
			'listids' => $listids,
			'main_listid' => $main_listid,
		));
	}

	/**
	* "Gets all subscriber custom fields for a particular list. This is used by autoresponders and sending so it only has to load all custom fields once per run."
	*
	* Note: This is not in the XML API documentation but it is in the api/subscribers.php file. This doc block has been copied from there.
	*
	* @param array $listids An array of listid's that the custom fields are attached to. If this is not an array, it is turned into one for easy processing.
	* @param array $limit_fields An array of field names to fetch custom field information for. These are strings (eg 'Name') and are the placeholders in the newsletter/autoresponder that are going to be replaced.
	* @param array $subscriberids An array of subscriberids to fetch custom field information for.
	* @param array $custom_fieldids An array of custom field IDs that should additionally be added into the result
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	public function getAllSubscriberCustomFields ($listids = array(), $limit_fields = array(), $subscriberids = array(), $custom_fieldids = array())
	{
		return $this->_xmlApiRequest('subscribers', 'GetAllSubscriberCustomFields', array(
			'listids' => $listids,
			'limit_fields' => $limit_fields,
			'custom_fieldids' => $custom_fieldids,
			'subscriberids' => $subscriberids,
		));
	}

	/**
	* put your comment there...
	*
	* @param string $requestType
	* @param string $requestMethod
	* @param array $requestDetails
	* @param int $timeout
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	* @throws Interspire_EmailIntegration_EmailMarketer_Exception
	*/
	protected function _xmlApiRequest ($requestType, $requestMethod, $requestDetails = array(), $timeout = null)
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;

		$request = $dom->createElement('xmlrequest');
		$dom->appendChild($request);

		$request->appendChild($dom->createElement('username', $this->getUsername()));
		$request->appendChild($dom->createElement('usertoken', $this->getUsertoken()));
		$request->appendChild($dom->createElement('requesttype', $requestType));
		$request->appendChild($dom->createElement('requestmethod', $requestMethod));

		// create the <details></details> block based on $requestDetails array
		$details = $dom->createElement('details');
		$request->appendChild($details);

		if (empty($requestDetails)) {
			// the iem xml api fails if details is present but empty, so...
			$details->appendChild($dom->createElement('empty'));
		} else {
			$this->_arrayToDOMElements($requestDetails, $details);
		}

		$xml = $dom->saveXML();

		$responseBody = PostToRemoteFileAndGetResponse($this->getXmlApiUrl(), $xml, $timeout, $error);

		if (!$responseBody) {
			if ($error == ISC_REMOTEFILE_ERROR_NONE) {
				throw new Interspire_EmailIntegration_EmailMarketer_Exception_ServerError;
			}
			throw new Interspire_EmailIntegration_EmailMarketer_Exception_ConnectionFailure('', $error);
		}

		$previousUseInternalErrors = libxml_use_internal_errors(true);
		try {
			$result = new SimpleXMLElement($responseBody);
			if ($result === false) {
				$exception = new Interspire_EmailIntegration_EmailMarketer_Exception_InvalidResponse;
				$exception->requestBody = $xml;
				$exception->responseBody = $responseBody;
				throw $exception;
			}
		} catch (Exception $exception) {
			$exception = new Interspire_EmailIntegration_EmailMarketer_Exception_InvalidResponse;
			$exception->requestBody = $xml;
			$exception->responseBody = $responseBody;
			throw $exception;
		}
		libxml_use_internal_errors($previousUseInternalErrors);

		$response = new Interspire_EmailIntegration_EmailMarketer_XmlApiResponse;

		$response
			->setRequestBody($xml)
			->setResponseBody($responseBody)
			->setStatus($result->status);

		if ($response->isSuccess()) {
			$response->setData($result->data);
		} else {
			$response->setErrorMessage($result->errormessage);
		}
		return $response;
	}

	/**
	* Returns true if the provided array $array is associative
	*
	* @param array $array
	* @return bool
	*/
	protected function _isArrayAssociative ($array)
	{
		foreach ($array as $index => $value) {
			if (!is_int($index)) {
				return true;
			}
		}
		return false;
	}

	/**
	* Takes an array of key/value pairs and recursively dumps it into a SimpleXMLElement.
	*
	* @param array $array
	* @param DOMElement $element
	* @return void
	*/
	protected function _arrayToDOMElements ($array, DOMElement $root)
	{
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				if ($this->_isArrayAssociative($value)) {
					// an associative array is converted into a set of uniquely-named child elements
					$element = $root->ownerDocument->createElement($key);
					$root->appendChild($element);
					$this->_arrayToDOMElements($value, $element);
				} else {
					// a numerically indexed array is converted into a set of child elements named as $key
					foreach ($value as $subvalue) {
						$root->appendChild($root->ownerDocument->createElement($key, $subvalue));
					}
				}
				continue;
			}

			if ($value instanceof DOMDocument) {
				// @hack a dom fragment has already been provided for this (i.e. AddSubscriberToList which has multiple <item> tags which can't be represented in a php array)
				$root->appendChild($root->ownerDocument->importNode($value->documentElement, true));
				continue;
			}

			// basic value
			$root->appendChild($root->ownerDocument->createElement($key, $value));
		}
	}
}
