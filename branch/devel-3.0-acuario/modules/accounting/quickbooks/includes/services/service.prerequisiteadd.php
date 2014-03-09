<?php

class ACCOUNTING_QUICKBOOKS_SERVICE_PREREQUISITEADD extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
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
						throw new QBException("Caught a QBJD error when adding a prerequisite record", $lastKid);
					}

					/**
					 * Our query kid was successfully so we need to create the reference data from the
					 * response. If we can't create the reference then we need to error out
					 */
					$queryResponse = $lastKid["response"];
					$queryResponse += $lastKid["nodeData"];

					if (!$this->setReferenceData($queryResponse, '*')) {
						throw new QBException("Cannot create reference data from prerequisite query response", $queryResponse);
					}

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
		return $this->execChildService("prerequisite", "query", $this->spool["nodeData"]);
	}
}
