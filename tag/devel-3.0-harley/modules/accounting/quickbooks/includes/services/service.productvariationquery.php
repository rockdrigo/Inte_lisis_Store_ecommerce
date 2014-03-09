<?php

class ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTVARIATIONQUERY extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
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
