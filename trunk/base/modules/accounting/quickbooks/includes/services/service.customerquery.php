<?php

class ACCOUNTING_QUICKBOOKS_SERVICE_CUSTOMERQUERY extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
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
