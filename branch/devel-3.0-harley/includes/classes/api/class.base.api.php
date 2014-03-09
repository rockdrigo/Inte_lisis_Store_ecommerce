<?php
abstract class API_BASE
{
	/**
	* Instance of the API class (router)
	*
	* @var XMLAPI
	*/
	protected $router = null;

	public function __construct($router)
	{
		$this->router = $router;
	}

	protected function SendResponse($output)
	{
		$this->router->SendResponse($output);
	}

	protected function BadRequest($message = '')
	{
		$this->router->BadRequest($message);
	}
}