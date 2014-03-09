<?php

class ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTVARIATIONEDIT extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
{
	public function execRequest()
	{
		if (is_array($this->spool["children"]) && !empty($this->spool["children"])) {
			$lastKid = end($this->spool["children"]);

			switch (isc_strtolower($lastKid["service"])) {
				case "add":

					/**
					 * Adding will handle both adding a new product OR editing an existing product with a
					 * bad reference, so either way just escape it here
					 */
					return $this->execNextService();
					break;
			}
		}

		return parent::execRequest();
	}

	/**
	 * Cannot find record hook
	 */
	protected function handleError3120()
	{
		$this->accounting->unsetReference("productvariation", '', '', $this->spool["nodeId"]);
		return $this->execChildService("productvariation", "add", $this->spool["nodeData"]);
	}

	/**
	 * Edit sequence out of sync hook
	 */
	protected function handleError3200()
	{
		$this->accounting->unsetReference("productvariation", '', '', $this->spool["nodeId"]);
		return $this->execChildService("productvariation", "add", $this->spool["nodeData"]);
	}
}
