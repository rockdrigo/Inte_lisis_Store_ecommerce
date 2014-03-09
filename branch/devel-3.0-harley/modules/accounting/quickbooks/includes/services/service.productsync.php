<?php

include_once(dirname(__FILE__) . "/service.syncbase.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTSYNC extends ACCOUNTING_QUICKBOOKS_SERVICE_SYNCBASE
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

		$this->entityAPI = new ISC_ENTITY_PRODUCT();
		$this->type = "product";
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
		 * Firstly check to see if this is a QB product short name as it contains the product Id in it
		 */
		$prodName = "";
		if (array_key_exists("Name", $response)) {
			$prodName = $this->accounting->revertParentSeparator($response["Name"]);
		} else if (array_key_exists("FullName", $response)) {
			$prodName = $this->accounting->fullName2RealName($response["FullName"]);
		}

		if (trim($prodName) !== '') {
			$productId = $this->accounting->qbProductShortName2ProductNameId($prodName, true);
			$product = null;

			if (isId($productId)) {
				$product = $this->entityAPI->get($productId);
			}

			if (is_array($product)) {
				return $product;
			}
		}

		/**
		 * Else do the usual search
		 */
		$fields = array();

		if (array_key_exists("ManufacturerPartNumber", $response) && trim($response["ManufacturerPartNumber"]) !== '') {
			$fields["prodcode"] = array(
										"func" => "LOWER",
										"value" => isc_strtolower($response["ManufacturerPartNumber"])
								);
		} else if (trim($prodName) !== '') {
			$fields["prodname"] = array(
										"func" => "LOWER",
										"value" => isc_strtolower($prodName)
								);
		}

		if (empty($fields)) {
			return false;
		}

		$nodeId = $this->entityAPI->search($fields);

		if (!isId($nodeId)) {
			return false;
		}

		return $this->entityAPI->get($nodeId);
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
	 * @return int The new or updated node ID on success, FALSE on error
	 */
	public function syncResponseRecord2Store($responseData, $nodeData=false)
	{
		if (!is_array($responseData)) {
			$xargs = func_get_args();
			throw new QBException("Invalid arguments when syncing product record from QB", $xargs);
		}

		/**
		 * Make sure that this is NOT a product variation (unfortunately we cannot mask them out in the query). They cannot
		 * create a product variation on QB, only from the store, so a variation will always have a valid reference record
		 */
		$variationRef = $this->accounting->getReference("productvariation", '', $responseData["ListID"]);

		if (is_array($variationRef) || $this->accounting->isProductProductVariation($responseData) || $this->accounting->isProductParent($responseData)) {
			return false;
		}

		/**
		 * Check to see if this is a product short name. Only do this if $nodeData is empty
		 */
		if (!is_array($nodeData) && $this->accounting->isProductShortName($responseData["Name"])) {
			$productParts = $this->accounting->qbProductShortName2ProductNameId($responseData["Name"]);

			if (is_array($productParts) && isset($productParts["productid"]) && isId($productParts["productid"])) {
				$nodeData = $this->entityAPI->get($productParts["productid"]);
			}
		}

		/**
		 * We need to fill in the blanks with our node data if we have one
		 */
		if (is_array($nodeData)) {

			/**
			 * If this is a QB short name then reset it back to the original name
			 */
			if ($this->accounting->isProductShortName($responseData["Name"])) {
				$responseData["Name"] = $nodeData["prodname"];
			}

			$map = array(
						"prodname" => "Name",
						"prodcode" => "ManufacturerPartNumber",
						"prodprice" => "SalesPrice",
						"proddesc" => "SalesDesc",
						"prodcostprice" => "PurchaseDesc"
			);

			foreach ($map as $nodeKey => $responseKey) {
				if (!array_key_exists($responseKey, $responseData) || trim($responseData[$responseKey]) == '') {
					$responseData[$responseKey] = $nodeData[$nodeKey];
				}
			}
		}

		$savedata = array(
			"prodname" => $responseData["Name"],
			"prodcode" => $responseData["ManufacturerPartNumber"],
			"prodprice" => $responseData["SalesPrice"],
			"proddesc" => $responseData["SalesDesc"],
			"prodcostprice" => $responseData["PurchaseCost"]
		);

		$this->accounting->logDebug("The formatted product data from QB", array("response" => $responseData, "savedata" => $savedata));

		/**
		 * Got all the info, now create the database record
		 */
		$productId = false;
		if (is_array($nodeData) && array_key_exists("productid", $nodeData) && isId($nodeData["productid"])) {

			/**
			 * Are we allowed to sync any product levels?
			 */
			if ($this->accounting->getValue("invlevels") == ACCOUNTING_QUICKBOOKS_TYPE_QUICKBOOKS && array_key_exists("ReorderPoint", $responseData)) {
				$savedata["prodlowinv"] = $responseData["ReorderPoint"];
			}

			$savedata["productid"] = $nodeData["productid"];

			if ($this->entityAPI->edit($savedata) !== false) {
				$productId = $nodeData["productid"];
			}
		} else {

			$savedata["prodtype"] = PT_PHYSICAL;
			$savedata["prodvisible"] = 1;

			/**
			 * Assign inventory tracking for new products if it is set
			 */
			if (isset($responseData["QuantityOnHand"]) && $responseData["QuantityOnHand"] > 0) {
				$savedata["prodinvtrack"] = 1;
				$savedata["prodcurrentinv"] = $responseData["QuantityOnHand"];

				if (isset($responseData["ReorderPoint"]) && $responseData["ReorderPoint"] > 0) {
					$savedata["prodlowinv"] = $responseData["ReorderPoint"];
				}
			}

			$productId = $this->entityAPI->add($savedata);

			/**
			 * Assign the categories if we can
			 */
			if (isId($productId)) {
				$categories = $this->accounting->getValue("newprodcategoryidx");

				if (isId($categories)) {
					$categories = array($categories);
				}

				$categories = array_filter($categories, "isId");

				if (is_array($categories) && !empty($categories)) {
					$this->entityAPI->assignCategories($productId, $categories);
				}
			}
		}

		if (!isId($productId)) {
			throw new QBException("Cannot save product record with data from QB", array("SaveData" => $savedata, "NodeData" => $nodeData, "DB" => $GLOBALS["ISC_CLASS_DB"]->GetError()));
		}

		return $productId;
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
		$query = "SELECT p.productid
					FROM [|PREFIX|]products p
					WHERE prodvariationid = 0";

		return $this->accounting->getReferenceSQL($this->type, '', $query, true);
	}

	/**
	 * Find the next record to insert into QB
	 *
	 * Method will find the next record to insert into QB
	 *
	 * @access private
	 * @param int $productId The optional product record ID to retrieve. Default will find the next one
	 * @return array The product record on success, FALSE if no more records where found
	 */
	private function findNextProductRecord($productId=null)
	{
		static $lastImportedDate = null;

		if (!isId($productId)) {
			if (is_null($lastImportedDate)) {
				$lastImportedDate = $this->accounting->getLastImportedTimeStamp($this->type);

				/**
				 * Minus the self::modifiedTimeFudgeFactor from the last imported date so we can pick up the items that were
				 * skipped in the previous import
				 */
				$lastImportedDate -= self::modifiedTimeFudgeFactor;
			}

			$query = "SELECT productid
						FROM [|PREFIX|]products
						WHERE prodvariationid = 0";

			if ($lastImportedDate > 0) {

				$referenceQuery = $this->accounting->getReferenceSQL("product", '', "productid");

				if ($referenceQuery !== '') {
					$query .= " AND (prodlastmodified > " . (int)$lastImportedDate . "
									OR NOT EXISTS(" . $referenceQuery . ")) ";
				} else {
					$query .= " AND prodlastmodified > " . (int)$lastImportedDate;
				}
			}

			$lastProductId = $this->retrieveLastInsertedNodeId();

			if (isId($lastProductId)) {
				$query .= " AND productid > " . $lastProductId;
			}

			$query .= " ORDER BY productid ASC
						LIMIT 1";

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

			if (!$row) {
				return false;
			}

			$product = $this->entityAPI->get($row["productid"]);
		} else {
			$product = $this->entityAPI->get($productId);
		}

		if (!is_array($product)) {
			return false;
		}

		$reference = $this->accounting->getReference($this->type, '', '', $product["productid"], true);

		/**
		 * Don't check the sync dates if $productId was set as it was already checked before
		 */
		if (!isId($productId) && is_array($reference) && !$this->canSyncToQB($product, $reference)) {
			$this->storeInsertedNodeId($product["productid"]);
			return $this->findNextProductRecord();
		} else {
			if (!is_array($reference) || empty($reference)) {
				$service = "add";
			} else {
				$service = "edit";
			}

			return array("service" => $service, "data" => $product["productid"]);
		}
	}

	public function execRequest()
	{
		/**
		 * First we get the current state of this sync
		 */
		$state = $this->getSyncState();

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
					$this->execDeleteListFromQB($deletedExternalIdx);
				}

				/**
				 * Don't break here, we need to cascade down to the next one
				 */

			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_STORE:
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_MODIFIED_QUERY_QB);

				/**
				 * Do we actually have any records to delete?
				 */
				$deleteChildXML = $this->sendDeleteListToQB();

				if (is_string($deleteChildXML) && trim($deleteChildXML) !== '') {
					return $deleteChildXML;
					break;
				}

				/**
				 * Don't break here if we have no records to delete
				 */

			/**
			 * Deleting is all done, now we can start syncing up the existing data
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

				$childService = $this->findNextProductRecord();

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

								$childService = $this->findNextProductRecord();

								if (is_array($childService)) {
									return $this->execChildService($this->type, "query", $childService["data"]);
								} else {
									break;
								}
							} else {
								$childService = $this->findNextProductRecord($lastKid["nodeId"]);
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
