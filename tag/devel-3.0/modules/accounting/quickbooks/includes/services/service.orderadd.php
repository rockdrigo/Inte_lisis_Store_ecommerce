<?php

include_once(dirname(__FILE__) . "/service.orderbase.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_ORDERADD extends ACCOUNTING_QUICKBOOKS_SERVICE_ORDERBASE
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

		/**
		 * Now we can move on to actually creating the order
		 */
		if (is_array($this->spool["children"]) && !empty($this->spool["children"])) {
			$lastKid = end($this->spool["children"]);

			switch (isc_strtolower($lastKid["nodeType"])) {
				case "order":

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
							 * Our query kid was successful so we need to create the reference data from the
							 * response. If we can't create the reference then we need to error out
							 */
							if (!$this->setReferenceData($lastKid["response"])) {
								throw new QBException("Cannot create reference data from order query response", $queryResponse);
							}

							return $this->execChildService("order", "edit", $this->spool["nodeData"]);
							break;

						case "edit":

							/**
							 * If we have an error here then that would mean that adding created a duplicate error,
							 * querying for it return a record but editing that record returned an error. Bad news
							 */
							if ($lastKid["errNo"] > 0) {
								throw new QBException("Caught a QBJD error when editing an order record (from orderadd)", $lastKid);
							}

							/**
							 * OK, the account was added (edited) successfully, so mark this as successful and esacpe this service
							 */
							return $this->execNextService();
							break;
					}

					break;

				case "customer":

					/**
					 * If we've died when trying to create the customer then throw an exception so we can log it
					 */
					if ($lastKid["errNo"] > 0) {
						throw new QBException("Unable to add customer when trying to add order", array("order" => $this->spool, "customer" => $lastKid));
					}

					break;

				case "product":

					/**
					 * Same deal as with the customer child
					 */
					if ($lastKid["errNo"] > 0) {
						throw new QBException("Unable to add product when trying to add order", array("order" => $this->spool, "product" => $lastKid));
					}

					break;

			}
		}

		return parent::execRequest();
	}

	/**
	 * Catch all the errors here as adding and editing will most likely have the same solutions
	 */
	protected function handleErrorAll()
	{
		return $this->handleOrderError();
	}
}
