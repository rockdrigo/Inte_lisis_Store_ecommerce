<?php

include_once(dirname(__FILE__) . "/service.syncbase.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTVARIATIONSYNC extends ACCOUNTING_QUICKBOOKS_SERVICE_SYNCBASE
{
	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @param array $spool The formatted spool array that we are working with
	 * @param object $accounting The accounting object
	 * @param bool $isResponse TRUE to specify thatb this service is a response, FASLE for a request. Default is FALSE
	 * @return void
	 */
	public function __construct($spool, $accounting, $isResponse=false)
	{
		parent::__construct($spool, $accounting, $isResponse);

		$this->entityAPI = new ISC_ENTITY_PRODUCTVARIATION();
		$this->type = "productvariation";
		$this->referenceDataExternalKey = "ListID";
	}

	/**
	 * Do a product search based on the response data
	 *
	 * Methood will search for the product record based on the response data
	 *
	 * @access public
	 * @param array $response The response data from QB
	 * @return array The product record on success, FALSE if it cannot be found
	 */
	public function searchNodeByDB($response)
	{
		if (!is_array($response)) {
			return false;
		}

		/**
		 * The name should already be in QB product variation short name format
		 */
		$prodName = "";
		if (array_key_exists("Name", $response)) {
			$prodName = $this->accounting->revertParentSeparator($response["Name"]);
		} else if (array_key_exists("FullName", $response)) {
			$prodName = $this->accounting->fullName2RealName($response["FullName"]);
		}

		if ($this->accounting->isProductVariationShortName($prodName)) {
			$variationId = $this->accounting->qbProductVariationShortName2ProductVariationNameId($prodName, true);
			$variation = null;

			if (isId($variationId)) {
				$variation = $this->entityAPI->get($variationId);
			}

			if (is_array($variation)) {
				return $variation;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get the last modified dates
	 *
	 * Method will return an array containing the last modified dates. The key 'node' will point to last modified
	 * time in the $nodeData array and the key 'response' will point to the last modified time in the $responseData
	 * array
	 *
	 * @access protected
	 * @param array $nodeData The node data array
	 * @param array $responseData The reponse data from QB
	 * @return array The array of last modified dates on success, FALSE on error
	 */
	protected function getLastModifiedDates($nodeData, $responseData)
	{
		if (!is_array($nodeData) || !is_array($responseData)) {
			return false;
		}

		if (!array_key_exists("prodlastmodified", $nodeData)) {
			return false;
		}

		if (!array_key_exists("TimeModified", $responseData)) {
			return false;
		}

		$dates = array(
			"node" => $nodeData["prodlastmodified"],
			"response" => strtotime($responseData["TimeModified"])
		);

		$check = array_filter($dates, "is_numeric");

		if (!is_array($check) || count($check) !== 2) {
			return false;
		}

		return $dates;
	}

	/**
	 * Insert/Update the node with the response record
	 *
	 * Method will insert/update the node with the response record
	 *
	 * @access public
	 * @param array $responseData The reponse data from QB
	 * @param array $nodeData The optional node data array. If set then update, else insert
	 * @return int The new or updtaed node ID on success, FALSE on error
	 */
	public function syncResponseRecord2Store($responseData, $nodeData)
	{
		if (!is_array($responseData)) {
			$xargs = func_get_args();
			throw new QBException("Invalid arguments when syncing product record from QB", $xargs);
		}

		/**
		 * Make sure that this is NOT a normal product (unfortunately we cannot mask them out in the query). They cannot
		 * create a product variation on QB, only from the store, so a variation will always have a valid node record
		 */
		if (!is_array($nodeData) || !$this->accounting->isProductProductVariation($responseData) || $this->accounting->isProductParent($responseData)) {
			return false;
		}

		$savedata = array(
			"combinationid" => $nodeData["combinationid"]
		);

		if (isset($responseData["ManufacturerPartNumber"]) && trim($responseData["ManufacturerPartNumber"]) !== '') {
			$savedata["vcsku"] = $responseData["ManufacturerPartNumber"];
		}

		/**
		 * Fix up the price as is could be fixed or difference based on the related product
		 */
		if (isset($responseData["SalesPrice"]) && trim($responseData["SalesPrice"]) !== '' && trim($nodeData["vcpricediff"]) !== '') {
			if (isc_strtolower($nodeData["vcpricediff"]) == "fixed") {
				$savedata["vcprice"] = $responseData["SalesPrice"];
			} else if ((float)$nodeData["prodprice"] !== (float)$responseData["SalesPrice"]) {
				if ((float)$responseData["SalesPrice"] > (float)$nodeData["prodprice"]) {
					$savedata["vcprice"] = ((float)$responseData["SalesPrice"] - (float)$nodeData["prodprice"]);
					$savedata["vcpricediff"] = "add";
				} else {
					$savedata["vcprice"] = ((float)$nodeData["prodprice"] - (float)$responseData["SalesPrice"]);
					$savedata["vcpricediff"] = "subtract";
				}
			}
		}

		$this->accounting->logDebug("The formatted product variation data from QB", array("response" => $responseData, "savedata" => $savedata));

		/**
		 * Got all the info, now edit the database record
		 */
		if ($this->entityAPI->edit($savedata) !== false) {
			return $nodeData["combinationid"];
		} else {
			throw new QBException("Cannot save product variation record with data from QB", array("SaveData" => $savedata, "NodeData" => $nodeData, "DB" => $GLOBALS["ISC_CLASS_DB"]->GetError()));
		}
	}

	/**
	 * Build SQL needed for getting any deleted records
	 *
	 * Method will return the SQL needed for retrieving any deleted records on the store. The SQL will return the
	 * entire accountingref columns
	 *
	 * @access protected
	 * @return string The SQL on success, FALSE on error
	 */
	protected function getDeletedNodesSQL()
	{
		$query = "SELECT c.combinationid
					FROM [|PREFIX|]product_variation_combinations c";

		return $this->accounting->getReferenceSQL($this->type, '', $query, true);
	}

	/**
	 * Find the next record to insert into QB
	 *
	 * Method will find the next record to insert into QB
	 *
	 * @access private
	 * @param int $combinationId The optional combination record ID to retrieve. Default will find the next one
	 * @return array The product record on success, FALSE if no more records where found
	 */
	private function findNextProductVariationCombinationRecord($combinationId=null)
	{
		static $lastImportedDate = null;

		if (!isId($combinationId)) {
			if (is_null($lastImportedDate)) {
				$lastImportedDate = $this->accounting->getLastImportedTimeStamp($this->type);

				/**
				 * Minus the self::modifiedTimeFudgeFactor from the last imported date so we can pick up the items that were
				 * skipped in the previous import
				 */
				$lastImportedDate -= self::modifiedTimeFudgeFactor;
			}

			$query = "SELECT c.combinationid
						FROM [|PREFIX|]product_variation_combinations c
							JOIN [|PREFIX|]products p ON c.vcvariationid = p.prodvariationid AND c.vcproductid = p.productid
						WHERE p.prodvariationid > 0 ";

			if ($lastImportedDate > 0) {

				$referenceQuery = $this->accounting->getReferenceSQL("productvariation", '', "c.combinationid");

				if ($referenceQuery !== '') {
					$query .= " AND (p.prodlastmodified > " . (int)$lastImportedDate . "
									OR NOT EXISTS(" . $referenceQuery . ")) ";
				} else {
					$query .= " AND p.prodlastmodified > " . (int)$lastImportedDate;
				}
			}

			$lastCombinationId = $this->retrieveLastInsertedNodeId();

			if (isId($lastCombinationId)) {
				$query .= " AND c.combinationid > " . $lastCombinationId;
			}

			$query .= " ORDER BY c.combinationid ASC
						LIMIT 1";

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

			if (!$row) {
				return false;
			}

			$variation = $this->entityAPI->get($row["combinationid"]);
		} else {
			$variation = $this->entityAPI->get($combinationId);
		}

		if (!is_array($variation)) {
			return false;
		}

		$reference = $this->accounting->getReference($this->type, '', '', $variation["combinationid"], true);

		/**
		 * Don't check the sync dates if $combinationId was set as it was already checked before
		 */
		if (!isId($combinationId) && is_array($reference) && !$this->canSyncToQB($variation, $reference)) {
			$this->storeInsertedNodeId($variation["combinationid"]);
			return $this->findNextProductVariationCombinationRecord();
		} else {
			if (!is_array($reference) || empty($reference)) {
				$service = "add";
			} else {
				$service = "edit";
			}

			return array("service" => $service, "data" => $variation["combinationid"]);
		}
	}

	/**
	 * Overide the parent method to NOT delete any variations
	 *
	 * Method will override the parent method to not delete the variation but instead update the corresponding
	 * products modified time so that it will get imported when syncing products
	 *
	 * @access protected
	 * @param array $externalIdx An array of deleted external ID's
	 * @return bool TRUE if the matching products were updated, FALSE if not
	 */
	protected function execDeleteListFromQB($externalIdx)
	{
		if (!is_array($externalIdx)) {
			return false;
		}

		if (empty($externalIdx)) {
			return true;
		}

		/**
		 * Convert the $externalIdx to make the key the ListID as checking through array keys is alot quicker than checking
		 * through the values
		 */
		$tmpValues = array_fill(0, count($externalIdx), true);
		$externalIdx = array_combine($externalIdx, $tmpValues);

		/**
		 * OK, the $externalIdx has been parsed. Next we run a query to get all the productvariation reference records
		 */
		$combinationIdx = array();
		$subQuery = "SELECT c.combinationid
						FROM [|PREFIX|]product_variation_combinations c
							JOIN [|PREFIX|]products p ON c.vcvariationid = p.prodvariationid AND c.vcproductid = p.productid
						WHERE p.prodvariationid > 0 ";

		$query = $this->accounting->getReferenceSQL($this->type, '', $subQuery);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {

			/**
			 * If this ListID is in the $externalIdx then save the node ID
			 */
			if (array_key_exists($row["accountingrefexternalid"], $externalIdx)) {
				$combinationIdx[] = $row["accountingrefnodeid"];
			}
		}

		/**
		 * Now run a query to update the last modified time of all the products that match variation combinations
		 */
		if (empty($combinationIdx)) {
			return true;
		}

		$time = time();
		$query = "UPDATE [|PREFIX|]products p
					JOIN [|PREFIX|]product_variation_combinations c ON p.productid = c.vcproductid
						AND p.prodvariationid = c.vcvariationid
					SET p.prodlastmodified='" . $GLOBALS["ISC_CLASS_DB"]->Quote($time) . "'
					WHERE c.combinationid IN(" . implode(",", $combinationIdx) . ")";

		$GLOBALS["ISC_CLASS_DB"]->Query($query);

		return true;
	}

	public function execRequest()
	{
		/**
		 * First we get the current state of this sync
		 */
		$state = $this->getSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_QB);

		switch ($state) {

			/**
			 * The first state is to query for any deleted items
			 */
			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_QB:
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_INACTIVE_QUERY_QB);

				return $this->execChildService($this->type, "querydel", array());
				break;

			/**
			 * Then check for any in-active items aswell
			 */
			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_INACTIVE_QUERY_QB:
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_DELETE_QUERY_QB);

				reset($this->spool["children"]);
				$queryKid = end($this->spool["children"]);

				$this->saveDeleteQuery($queryKid["response"]);

				return $this->execChildService($this->type, "queryinactive", array());
				break;

			/**
			 * Next we need to parse the response from the querydel service
			 */
			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_DELETE_QUERY_QB:
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_STORE);

				reset($this->spool["children"]);
				$queryKid = end($this->spool["children"]);

				$this->saveDeleteQuery($queryKid["response"]);

				$deletedExternalIdx = $this->getDeleteQueryExternalIdx();

				if (is_array($deletedExternalIdx) && !empty($deletedExternalIdx)) {

					/**
					 * This function is overwritten further up (can't delete variations or mark as inactive in QB)
					 */
					$this->execDeleteListFromQB($deletedExternalIdx);
				}

				/**
				 * Don't break here, we need to cascade down to the next one
				 */

			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_STORE:
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_MODIFIED_QUERY_QB);

				/**
				 * Do we actually have any records to delete? This is overridden by the way
				 */
				$deleteChildXML = $this->sendDeleteListToQB();

				if (is_string($deleteChildXML) && trim($deleteChildXML) !== '') {
					return $deleteChildXML;
					break;
				}

				/**
				 * Don't break here if we have no records to delete
				 */

			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_MODIFIED_QUERY_QB:
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_MODIFIED_QUERY_QB_FIRSTTIME);

				$nodeData = array(
					"modifiedDate" => $this->accounting->getLastImportedTimeStamp($this->type)
				);

				/**
				 * QB can't handle dates that start from epic so add a day to it if it is
				 */
				if (trim($nodeData["modifiedDate"]) == '' || $nodeData["modifiedDate"] == 0) {
					$nodeData["modifiedDate"] = 86400;
				}

				return $this->execChildService($this->type, "query", $nodeData);
				break;

			/**
			 * We have the modified data from QB, now we need to parse it
			 */
			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_MODIFIED_QUERY_QB_FIRSTTIME:
			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_MODIFIED_QUERY_QB:

				/**
				 * If this is the first time here the save the response so we can have a dynamic list
				 * of what record spool is next
				 */
				if ($state == ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_MODIFIED_QUERY_QB_FIRSTTIME) {
					$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_MODIFIED_QUERY_QB);

					reset($this->spool["children"]);
					$queryKid = end($this->spool["children"]);
					$this->setQBPendingExternalIdx($queryKid["response"]);
				}

				/**
				 * If we still have current spools to process then go do it
				 */
				if ($this->getnextQBPendingExternalIdx()) {
					$rtn = $this->syncResponseRecords2Store();

					if (is_string($rtn)) {
						return $rtn;
					}
				}

				/**
				 * Don't break here as we need to cascade down into the next case (sending our info to QB)
				 */
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_MODIFIED_QUERY_STORE);

				/**
				 * This is so we know that we just finished this case when going to the next one
				 */
				$justFinishedCache = true;

			/**
			 * This case is for sending our info into QB
			 */
			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_MODIFIED_QUERY_STORE:

				if (!isset($justFinishedCache) || !$justFinishedCache) {
					$lastKid = end($this->spool["children"]);
				} else {
					$lastKid = '';
				}

				/**
				 * Store the node ID if the last child service was not a "query"
				 */
				if (is_array($lastKid) && $lastKid["service"] !== "query") {
					$this->storeInsertedNodeId($lastKid["nodeId"]);
				}

				$childService = $this->findNextProductVariationCombinationRecord();

				/**
				 * Run the child service if we have any
				 */
				if (is_array($childService)) {
					if (!is_array($lastKid) || $lastKid["service"] !== "query") {
						return $this->execChildService($this->type, "query", $childService["data"]);
					} else {

						/**
						 * If our last child service was a "query" then record the reference
						 */
						if ($lastKid["service"] == "query" && $lastKid["errNo"] == 0 && is_array($lastKid["response"]) && !empty($lastKid["response"])) {

							/**
							 * If setting the reference failed then loop again. We also re-get the child service again just in case if the service
							 * changed from an 'add' to an 'edit' or if setting the reference failed
							 */
							if (!$this->setReferenceDataStatically($this->type, $lastKid["nodeId"], $lastKid["response"])) {
								$this->storeInsertedNodeId($lastKid["nodeId"]);

								$childService = $this->findNextProductVariationCombinationRecord();

								if (is_array($childService)) {
									return $this->execChildService($this->type, "query", $childService["data"]);
								} else {
									break;
								}
							} else {
								$childService = $this->findNextProductVariationCombinationRecord($lastKid["nodeId"]);
							}
						}

						/**
						 * If our last kid was a query and it failed, then remove the reference and set the child service to "add", just to save time
						 */
						if ($lastKid["service"] == "query" && $lastKid["errNo"] > 0) {
							$this->accounting->unsetReference($this->type, '', '', $lastKid["nodeId"]);
							$childService["service"] = "add";
						}

						if (is_array($childService)) {
							return $this->execChildService($this->type, $childService["service"], $childService["data"]);
						}
					}
				}

				break;
		}

		/**
		 * OK, the sync is done. Set the last imported timestamp for this sync
		 */
		 $this->accounting->setLastImportedTimeStamp($this->type);

		return $this->execNextService();
	}
}