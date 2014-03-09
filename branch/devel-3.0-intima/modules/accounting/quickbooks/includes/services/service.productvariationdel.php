<?php

class ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTVARIATIONDEL extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
{
	public function execRequest()
	{
		$entity = $this->entityObjectFactory();
		return $entity->buildDelXML();
	}

	public function execResponse()
	{
		/**
		 * We don't want to deal with the response here
		 */
		return true;
	}
}
