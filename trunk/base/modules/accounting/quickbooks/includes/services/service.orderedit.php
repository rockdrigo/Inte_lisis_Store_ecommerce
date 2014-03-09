<?php

include_once(dirname(__FILE__) . "/service.orderbase.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_ORDEREDIT extends ACCOUNTING_QUICKBOOKS_SERVICE_ORDERBASE
{
	public function execRequest()
	{
		/**
		 * Make sure that we have a valid customer
		 */
		$customerXML = $this->validateCustomer();
		if (is_string($customerXML)) {
			return $customerXML;
		}

		/**
		 * Plus all our products as well
		 */
		$productXML = $this->validateProducts();
		if (is_string($productXML)) {
			return $productXML;
		}

		if (is_array($this->spool["children"]) && !empty($this->spool["children"])) {
			$lastKid = end($this->spool["children"]);

			switch (isc_strtolower($lastKid["service"])) {
				case "query":

					/**
					 * If we have an error here then that would mean that adding created a duplicate error
					 * but querying for it return nothing. Bad news
					 */
					if ($lastKid["errNo"] > 0) {
						throw new QBException("Caught a QBJD error when adding an order record", $lastKid);
					}

					/**
					 * Our query kid was successfully so we need to create the reference data from the
					 * response. If we can't create the reference then we need to error out
					 */
					if (!$this->setReferenceData($lastKid["response"])) {
						throw new QBException("Cannot create reference data from order query response", $queryResponse);
					}

					/**
					 * Reset the reference data and try again
					 */
					$this->setReferenceData($lastKid["response"]);
					$this->spool = $this->accounting->getSpool($this->spool["id"]);
					break;

				case "add":

					/**
					 * Adding would have handled both adding a new order OR editing an existing order with a
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
		$this->accounting->unsetReference("order", '', '', $this->spool["nodeId"]);
		return $this->execChildService("order", "add", $this->spool["nodeData"]);
	}

	/**
	 * Edit sequence out of sync hook
	 */
	protected function handleError3200()
	{
		$this->accounting->unsetReference("order", '', '', $this->spool["nodeId"]);
		return $this->execChildService("order", "query", $this->spool["nodeData"]);
	}

	/**
	 * Catch all the reaminding errors here
	 */
	protected function handleErrorAll()
	{
		return $this->handleOrderError();
	}
}
