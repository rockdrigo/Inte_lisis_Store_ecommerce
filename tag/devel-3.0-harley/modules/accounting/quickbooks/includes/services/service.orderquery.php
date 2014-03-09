<?php

include_once(dirname(__FILE__) . "/service.orderbase.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_ORDERQUERY extends ACCOUNTING_QUICKBOOKS_SERVICE_ORDERBASE
{
	public function execRequest()
	{
		if (array_key_exists("modifiedDate", $this->spool["nodeData"])) {
			$entity = $this->entityObjectFactory();
			return $entity->buildQuerySyncXML();
		} else {
			return parent::execRequest();
		}
	}
}
