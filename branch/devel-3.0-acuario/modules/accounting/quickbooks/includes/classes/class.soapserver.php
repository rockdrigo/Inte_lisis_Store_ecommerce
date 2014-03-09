<?php

final class ACCOUNTING_QUICKBOOKS_CLASS_SOAPSERVER extends SoapServer
{
	private $accounting;

	public function __construct($wsdl, $accounting)
	{
		parent::__construct($wsdl, array("encoding" => "utf-8"));

		$this->accounting = $accounting;
		$this->setObject($this);
	}

	public function handle()
	{
		if (array_key_exists("HTTP_RAW_POST_DATA", $GLOBALS)) {
			$data = $GLOBALS["HTTP_RAW_POST_DATA"];
		} else {
			$data = file_get_contents("php://input");
		}

		@parent::handle($data);
	}

	/**
	 * Provide the server version
	 *
	 * Method will provide the server version
	 *
	 * @access public
	 * @param object $obj The SOAP input object
	 * @return string The result string to pass back
	 */
	public function serverVersion($obj)
	{
		return $this->execHandler(__FUNCTION__, $obj);
	}

	/**
	 * Provide the client version
	 *
	 * Method will provide the client version
	 *
	 * @access public
	 * @param object $obj The SOAP input object
	 * @return string The result string to pass back
	 */
	public function clientVersion($obj)
	{
		return $this->execHandler(__FUNCTION__, $obj);
	}

	/**
	 * Authentication handler
	 *
	 * Method will authenticate the current user
	 *
	 * @access public
	 * @param object $obj The SOAP input object
	 * @return object The result object to pass back
	 */
	public function authenticate($obj)
	{
		return $this->execHandler(__FUNCTION__, $obj);
	}

	/**
	 * getLastError handler
	 *
	 * Method will return the last error
	 *
	 * @access public
	 * @param object $obj The SOAP input object
	 * @return object The result object to pass back
	 */
	public function getLastError($obj)
	{
		return $this->execHandler(__FUNCTION__, $obj);
	}

	/**
	 * closeConnection handler
	 *
	 * Method will close the currency connection
	 *
	 * @access public
	 * @param object $obj The SOAP input object
	 * @return object The result object to pass back
	 */
	public function closeConnection($obj)
	{
		return $this->execHandler(__FUNCTION__, $obj);
	}

	/**
	 * sendRequestXML handler
	 *
	 * Method will handle all request to QBWC
	 *
	 * @access public
	 * @param object $obj The SOAP input object
	 * @return object The result object to pass back
	 */
	public function sendRequestXML($obj)
	{
		return $this->execHandler(__FUNCTION__, $obj);
	}

	/**
	 * receiveResponseXML handler
	 *
	 * Method will handle all resopnses from QBWC
	 *
	 * @access public
	 * @param object $obj The SOAP input object
	 * @return object The result object to pass back
	 */
	public function receiveResponseXML($obj)
	{
		return $this->execHandler(__FUNCTION__, $obj);
	}

	/**
	 * Execute a handler service
	 *
	 * Method is a wrapper for handling all executed services
	 *
	 * @access private
	 * @param string $handler The service to execute
	 * @param object $soap The SOAP input object
	 * @return mixed The output of the executed service
	 */
	private function execHandler($handler, $soap)
	{
		if (trim($handler) == '' || !is_object($soap)) {
			$xargs = func_get_args();
			$this->accounting->logError("Invalid supplied SOAP arguments", $xargs);
			return '';
		}

		$classSchema = $this->accounting->findModuleClass("handlers", $handler);

		if (!is_array($classSchema)) {
			$this->accounting->logError("Cannot find handler class file", "1st argument does not resolve to a class file ('" . $handler . "')");
			return '';
		}

		$classFile = $classSchema["file"];
		$className = $classSchema["class"];

		@include_once($classFile);

		if (!class_exists($className)) {
			$this->accounting->logError("Cannot find class in class file", $classSchema);
			return '';
		}

		try {
			$handlerClass = new $className($soap, $this->accounting);
			$handlerOutput = $handlerClass->handleSoapRequest();
		} catch (Exception $e) {
			$this->accounting->logError("Error invoking handleSoapRequest() method on " . $classSchema["class"] . " handler", $e->getMessage());
			return '';
		}

		return $handlerOutput;
	}
}
