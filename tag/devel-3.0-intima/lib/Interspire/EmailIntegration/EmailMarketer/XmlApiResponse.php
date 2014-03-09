<?php

class Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
{
	protected $_requestBody;

	protected $_responseBody;

	protected $_status;

	protected $_data;

	protected $_errorMessage;

	public function getRequestBody ()
	{
		return $this->_requestBody;
	}

	public function setRequestBody ($value)
	{
		$this->_requestBody = (string)$value;
		return $this;
	}

	public function getResponseBody ()
	{
		return $this->_responseBody;
	}

	public function setResponseBody ($value)
	{
		$this->_responseBody = (string)$value;
		return $this;
	}

	/**
	* Checks the status and returns true if the response indicates success.
	*
	* @return bool
	*/
	public function isSuccess ()
	{
		return $this->getStatus() == 'SUCCESS';
	}

	/**
	* Get the status string returned by the remote API. This is supposed to be either 'SUCCESS' or 'ERROR'.
	*
	* Tip: Use isSuccess() to test for success unless you need the string specifically.
	*
	* @return string
	*/
	public function getStatus ()
	{
		return $this->_status;
	}

	/**
	* put your comment there...
	*
	* @param string $value
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	*/
	public function setStatus ($value)
	{
		$this->_status = (string)$value;
		return $this;
	}

	/**
	* @return SimpleXMLElement or null if the response is not a successful response
	*/
	public function getData ()
	{
		return $this->_data;
	}

	/**
	* put your comment there...
	*
	* @param SimpleXMLElement $value
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	*/
	public function setData (SimpleXMLElement $value)
	{
		$this->_data = $value;
		return $this;
	}

	public function getErrorMessage ()
	{
		return $this->_errorMessage;
	}

	/**
	* put your comment there...
	*
	* @param string $value
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	*/
	public function setErrorMessage ($value)
	{
		$this->_errorMessage = (string)$value;
		return $this;
	}
}
