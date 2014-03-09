<?php

class ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTVARIATIONADD extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
{
	public function execRequest()
	{
		if (is_array($this->spool["children"]) && !empty($this->spool["children"])) {
			$lastKid = end($this->spool["children"]);

			switch (isc_strtolower($lastKid["service"])) {
				case "query":

					/**
					 * If we have an error here then that would mean that adding created a duplicate error
					 * but querying for it return nothing. Bad news
					 */
					if ($lastKid["errNo"] > 0) {
						throw new QBException("Caught a QBJD error when adding a product variation record", $lastKid);
					}

					/**
					 * Our query kid was successfully so we need to create the reference data from the
					 * response. If we can't create the reference then we need to error out
					 */
					if (!$this->setReferenceData($lastKid["response"])) {
						throw new QBException("Cannot create reference data from product query response", $queryResponse);
					}

					return $this->execChildService("productvariation", "edit", $this->spool["nodeData"]);
					break;

				case "edit":

					/**
					 * If we have an error here then that would mean that adding created a duplicate error,
					 * querying for it return a record but editing that record returned an error. Bad news
					 */
					if ($lastKid["errNo"] > 0) {
						throw new QBException("Caught a QBJD error when editing a product variation record (from productvariationadd)", $lastKid);
					}

					/**
					 * OK, the account was added (edited) successfully, so mark this as successful and esacpe this service
					 */
					return $this->execNextService();
					break;
			}
		}

		return parent::execRequest();
	}

	/**
	 * Duplicate record hook
	 */
	protected function handleError3100()
	{
		return $this->execChildService("productvariation", "query", $this->spool["nodeData"]);
	}
}
