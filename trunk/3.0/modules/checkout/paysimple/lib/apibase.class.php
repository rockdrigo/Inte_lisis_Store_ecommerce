<?php

/**
 * This class provides a base for a SOAP service.
 * Any WSDL generated with the wsdlstubgen (http://www.mixedupduck.co.uk/wsdlstubgen)
 * will be able to extend this class and build upon the funcionality provided.
 *
 * This class is designed to work with NuSoap and PHP5
 */


class APIBase
{


	/**
	 * Debugging Mode - Set this flag to output Debugging information about your call.
	 *
	 * @var boolean
	 */
	public $debug = false;


	private $production = false;

	/**
	 * The property used to hold a soapclient
	 *
	 * @var soapclient
	 * @access private
	 */
	private $soapClient = null;



	/**
	 * If an error occurred with the last soap operation.
	 *
	 * @var boolean
	 * @access private
	 */
	private $error = false;



	/**
	 * The error message if there was a fault.
	 *
	 * @var String
	 * @access private
	 */
	private $errorMessage = "";



	/**
	 * Constructor.
	 * Add any initialisation code here.
	 */
	function __construct()
	{

	}



	/**
	 * Complete this method by adding the WSDL location here
	 *
	 * @access public
	 * @return url wsdlUrl
	 */
	public function getWSDL()
	{
		$productionWsdl = "https://www.paysimple.com/Gateway.asmx?WSDL";

		$sandboxWsdl = "https://sandbox.paysimple.com/Gateway.asmx?WSDL";
		if ($this->production)
			return $productionWsdl;
		else
			return $sandboxWsdl;
	}


 	/**
	* Sets up (if it is not already) a soapclient object
	*
	* @return soapclient
	*/
	public function GetClient()
	{
		if ($this->debug)
		{
			echo __CLASS__. "::". __FUNCTION__  . " - " . $this->getWSDL() . "<br />";
		}


		if($this->soapClient == null)
		{
			if ($this->debug)
				$trace = 1;
			else
				$trace = 0;
			$this->soapClient = new soapclient($this->getWSDL(), array('trace' => $trace));
		}

		return $this->soapClient;
	}



	/**
	* This makes the call to the Web Service.
	*
	* @access public
	*/
	function Call($operation, $arguments=array(), &$data)
	{
		$client = $this->GetClient();

		/**
		 * Call the Web Service
		 *
		 */
		try{
			$data = $client->$operation($arguments);
			$error = false;
		}
		catch (Exception $e){
			$error = true;
			$data = $e;
		}


		/**
		 * Check is the Soap Client returned a fault and return null
		 */


		return $error;
	}


	/**
	 * Sets an Error Message on this object.
	 *
	 * @access public
	 * @param string $message
	 */
	public function setError($message)
	{
		$this->error = true;
		$this->errorMessage = $message;
	}


	/**
	 * Returns the Error Message (if there is one)
	 *
	 * @access public
	 * @return string Error Message
	 */
	public function getErrorMessage()
	{
		return $this->errorMessage;
	}


	/**
	 * Use to check for Faults in your wrapper methods
	 *
	 * @access public
	 * @return boolean Error
	 */
	function isError()
	{
		return $this->error;
	}


	/**
	 * Resets All Errors.
	 *
	 * @access public
	 */
	function resetError()
	{
		$this->error = false;
		$this->errorMessage = "";
	}
}