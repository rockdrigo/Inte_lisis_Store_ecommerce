<?php

class ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTQUERYDEL extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
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