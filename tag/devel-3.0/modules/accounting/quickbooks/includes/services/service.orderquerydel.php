<?php

include_once(dirname(__FILE__) . "/service.orderbase.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_ORDERQUERYDEL extends ACCOUNTING_QUICKBOOKS_SERVICE_ORDERBASE
{
	public function execRequest()
	{
		$entity = $this->entityObjectFactory();
		return $entity->buildQueryDelXML();
	}

	public function execResponse()
	{
		/**
		 * We don't want to deal with the response here
		 */
		return true;
	}
}
