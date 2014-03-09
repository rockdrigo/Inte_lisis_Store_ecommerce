<?php

include_once(dirname(__FILE__) . "/service.syncbase.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_CUSTOMERSYNC extends ACCOUNTING_QUICKBOOKS_SERVICE_SYNCBASE
{
	const emailPreFixFillin = "no-email-address";

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

		$this->entityAPI = new ISC_ENTITY_CUSTOMER();
		$this->type = "customer";
		$this->referenceDataExternalKey = "ListID";
	}

	/**
	 * Check to see if the email address is unique
	 *
	 * Method will check to is if the email address is unique
	 *
	 * @access private
	 * @param string $email The email address to search for
	 * @param mixed $nodeData The optional customer ID / data array of the customer. Default is empty string (new customer)
	 * @return bool TRUE if the email address is unique, FALSE if not
	 */
	private function checkEmailIsUnique($email, $nodeData='')
	{
		if (trim($email) == "") {
			return false;
		}

		if (is_array($nodeData) && array_key_exists("customerid", $nodeData)) {
			$nodeData = $nodeData["customerid"];
		}

		$negateFields = array();
		$searchFields = array(
			"custconemail" => $email
		);

		if (isId($nodeData)) {
			$negateFields = array("customerid");
			$searchFields["customerid"] = $nodeData;
		}

		if ($this->entityAPI->search($searchFields, '', $negateFields)) {
			return false;
		}

		return true;
	}

	/**
	 * Do a customer search based on the response data
	 *
	 * Methood will search for the customer record based on the response data
	 *
	 * @access public
	 * @param array $response The response data from QB
	 * @return array The customer record on success, FALSE if it cannot be found
	 */
	public function searchNodeByDB($response)
	{
		if (!is_array($response)) {
			return false;
		}

		/**
		 * We need to have all the name parts and the email address as emails are not unique on QB and the name
		 * is not unique on Shopping Cart
		 */
		if (!array_key_exists("FirstName", $response) || trim($response["FirstName"]) == '') {
			return false;
		} else if (!array_key_exists("LastName", $response) || trim($response["LastName"]) == '') {
			return false;
		} else if (!array_key_exists("Email", $response) || trim($response["Email"]) == '') {
			return false;
		}

		$fields = array(
			"custconfirstname" => array(
										"func" => "LOWER",
										"value" => isc_strtolower(trim($response["FirstName"]))
								),
			"custconlastname" => array(
										"func" => "LOWER",
										"value" => isc_strtolower(trim($response["LastName"]))
								),
			"custconemail" => array(
										"func" => "LOWER",
										"value" => isc_strtolower(trim($response["Email"]))
								)
		);

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

		if (!array_key_exists("custlastmodified", $nodeData)) {
			return false;
		}

		if (!array_key_exists("TimeModified", $responseData)) {
			return false;
		}

		$dates = array(
			"node" => $nodeData["custlastmodified"],
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
	 * Method will insert/update the node with the response record. Its made public so order syncing can you it also
	 *
	 * @access public
	 * @param array $responseData The reponse data from QB
	 * @param array $nodeData The optional node data array. If set then update, else insert
	 * @return int The new or updtaed node ID on success, FALSE on error
	 */
	public function syncResponseRecord2Store($responseData, $nodeData=false)
	{
		static $_cacheHTTPHost = null;

		/**
		 * Firstly, don't import if this has a customer type as it is a guest checkout customer
		 */
		if ($this->accounting->isCustomerGuestCheckout($responseData) || $this->accounting->isCustomerParent($responseData)) {
			return false;
		}

		/**
		 * Set the fillin email address domain name
		 */
		if (is_null($_cacheHTTPHost)) {
			$parts = parse_url(GetConfig("ShopPath"));

			if (!$parts || !isset($parts["host"]) || trim($parts["host"]) == '') {
				if (function_exists("apache_getenv")) {
					$_cacheHTTPHost = @apache_getenv("HTTP_HOST");
				}

				if (!$_cacheHTTPHost) {
					$_cacheHTTPHost = @$_SERVER["HTTP_HOST"];
				}
			} else {
				$_cacheHTTPHost = $parts["host"];
			}
		}

		if (!is_array($responseData)) {
			$xargs = func_get_args();
			throw new QBException("Invalid arguments when syncing customer record from QB", $xargs);
		}

		/**
		 * Check to see if this is a customer short name. Only do this if $nodeData is empty
		 */
		if (!is_array($nodeData) && $this->accounting->isCustomerShortName($responseData["Name"])) {
			$customerParts = $this->accounting->qbCustomerShortName2CustomerNameId($responseData["Name"]);

			if (is_array($customerParts) && isset($customerParts["customerid"]) && isId($customerParts["customerid"])) {
				$nodeData = $this->entityAPI->get($customerParts["customerid"]);
			}
		}

		/**
		 * We need to fill in the blanks with our node data if we have one
		 */
		if (is_array($nodeData)) {
			$map = array(
						"custconfirstname" => "FirstName",
						"custconlastname" => "LastName",
						"custconemail" => "Email",
						"custconphone" => "Phone",
						"custconcompany" => "CompanyName"
			);

			foreach ($map as $nodeKey => $responseKey) {
				if (!array_key_exists($responseKey, $responseData) || trim($responseData[$responseKey]) == '') {
					$responseData[$responseKey] = $nodeData[$nodeKey];
				}
			}
		}

		/**
		 * Create a fillin for the email address if it is not set
		 */
		if (!array_key_exists("Email", $responseData) || trim($responseData["Email"]) == '' || !is_email_address($responseData["Email"])) {
			$emailAddress = self::emailPreFixFillin .  mt_rand(10000, 99999) . "@" . $_cacheHTTPHost;

		/**
		 * Else check to see if it is unique
		 */
		} else if (!$this->checkEmailIsUnique($responseData["Email"], $nodeData)) {
			$emailAddress = self::emailPreFixFillin .  mt_rand(10000, 99999) . "@" . $_cacheHTTPHost;
			$this->accounting->logWarning("The QuickBooks customer '" . $responseData["FirstName"] . " " . $responseData["LastName"] . "' has a non-unique email address. Changing their email address to " . $emailAddress);

		/**
		 * Its all cool
		 */
		} else {
			$emailAddress = $responseData["Email"];
		}

		/**
		 * If the first or last name are 25 chanracters then use the original values. This is because QBWC puts a 25 character
		 * limit on those fields and will cut it off if it is exceeded, so best to be safe than sorry
		 */
		if (is_array($nodeData)) {
			if (strlen($responseData["FirstName"]) == 25) {
				$this->accounting->logWarning("The QuickBooks customer '" . $responseData["FirstName"] . " " . $responseData["LastName"] . "' has had their first name truncated. Defaulting to the original first name of '" . $nodeData["custconfirstname"] . "'");
				$responseData["FirstName"] = $nodeData["custconfirstname"];
			}

			if (strlen($responseData["LastName"]) == 25) {
				$this->accounting->logWarning("The QuickBooks customer '" . $responseData["FirstName"] . " " . $responseData["LastName"] . "' has had their last name truncated. Defaulting to the original last name of '" . $nodeData["custconlastname"] . "'");
				$responseData["LastName"] = $nodeData["custconlastname"];
			}
		}

		$savedata = array(
			"custconfirstname" => @$responseData["FirstName"],
			"custconlastname" => @$responseData["LastName"],
			"custconemail" => $emailAddress,
			"custconphone" => @$responseData["Phone"],
			"custconcompany" => @$responseData["CompanyName"]
		);

		/**
		 * The addresses
		 */
		$addresses = array();

		foreach (array("BillAddress", "ShipAddress") as $addressType) {

			if (!array_key_exists($addressType, $responseData) || !is_array($responseData[$addressType])) {
				continue;
			}

			if (trim(@$responseData[$addressType]["Addr1"]) !== '') {

				/**
				 * Firstly lets check to see if we already have this addressd
				 */
				$shipId = '';

				if (is_array($nodeData) && isset($nodeData["addresses"]) && is_array($nodeData["addresses"])) {
					foreach ($nodeData["addresses"] as $address) {
						if (isc_strtolower(trim($address["shipaddress1"])) == isc_strtolower(trim(@$responseData[$addressType]["Addr1"]))) {
							$shipId = $address["shipid"];

							/**
							 * Now fill in the blanks
							 */
							$map = array(
										"shipaddress1" => "Addr1",
										"shipaddress2" => "Addr2",
										"shipcity" => "City",
										"shipstate" => "State",
										"shipzip" => "PostalCode",
										"shipcountry" => "Country"
							);

							foreach ($map as $nodeKey => $responseKey) {
								if (!array_key_exists($responseKey, $responseData[$addressType]) || trim($responseData[$addressType][$responseKey]) == '') {
									$responseData[$addressType][$responseKey] = $address[$nodeKey];
								}
							}
						}
					}
				}

				/**
				 * Find the country and state IDs
				 */
				$countryId = $this->getCountryId(@$responseData[$addressType]["Country"], $properCountryName);
				$stateId = '';

				if (isId($countryId) && trim(@$responseData[$addressType]["State"]) !== '') {
					$responseData[$addressType]["Country"] = $properCountryName;
					$stateId = $this->getStateId($responseData[$addressType]["State"], $countryId, $properStateName);
					if (!isId($stateId)) {
						$stateId = '';
					} else if (trim($properStateName) !== '') {
						$responseData[$addressType]["State"] = $properStateName;
					}
				} else {
					$countryId = '';
				}

				$addresses[] = array(
									"shipid" => $shipId,
									"shipfirstname" => @$responseData["FirstName"],
									"shiplastname" => @$responseData["LastName"],
									"shipcompany" => @$responseData["CompanyName"],
									"shipaddress1" => @$responseData[$addressType]["Addr1"],
									"shipaddress2" => @$responseData[$addressType]["Addr2"],
									"shipcity" => @$responseData[$addressType]["City"],
									"shipstate" => @$responseData[$addressType]["State"],
									"shipzip" => @$responseData[$addressType]["PostalCode"],
									"shipcountry" => @$responseData[$addressType]["Country"],
									"shipphone" => @$responseData["Phone"],
									"shipstateid" => $stateId,
									"shipcountryid" => $countryId
				);

				/**
				 * Set something to each field if it is NULL as the database can't handle NULL values for this schema
				 */
				foreach ($addresses[count($addresses)-1] as $addKey => $addVal) {
					if (is_null($addVal)) {
						$addresses[count($addresses)-1][$addKey] = '';
					}
				}
			}
		}

		if (!empty($addresses)) {
			$savedata["addresses"] = $addresses;
		}

		$this->accounting->logDebug("The formatted customer data from QB", array("Savedata" => $savedata, "Response" => $responseData));

		/**
		 * Got all the info, now create the database record
		 */
		$customerId = false;
		if (is_array($nodeData) && array_key_exists("customerid", $nodeData) && isId($nodeData["customerid"])) {
			$savedata["customerid"] = $nodeData["customerid"];
			if ($this->entityAPI->edit($savedata, false, true) !== false) {
				$customerId = $nodeData["customerid"];
			}
		} else {
			$savedata["password"] = Interspire_String::generateReadablePassword();
			$customerId = $this->entityAPI->add($savedata);
		}

		if (!isId($customerId)) {
			throw new QBException("Cannot save customer record with data from QB", array("SaveData" => $savedata, "NodeData" => $nodeData, "DB" => $GLOBALS["ISC_CLASS_DB"]->GetError()));
		}

		return $customerId;
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
		$query = "SELECT c.customerid
					FROM [|PREFIX|]customers c";

		$sql = $this->accounting->getReferenceSQL($this->type, '', $query, true);

		return $sql;
	}

	/**
	 * Find the next record to insert into QB
	 *
	 * Method will find the next record to insert into QB
	 *
	 * @access private
	 * @param int $customerId The optional customer record ID to retrieve. Default will find the next one
	 * @return array The customer record on success, FALSE if no more records where found
	 */
	private function findNextCustomerRecord($customerId=null)
	{
		static $lastImportedDate = null;

		if (!isId($customerId)) {
			if (is_null($lastImportedDate)) {
				$lastImportedDate = $this->accounting->getLastImportedTimeStamp($this->type);

				/**
				 * Minus the self::modifiedTimeFudgeFactor from the last imported date so we can pick up the items that were
				 * skipped in the previous import
				 */
				$lastImportedDate -= self::modifiedTimeFudgeFactor;
			}

			$query = "SELECT customerid
						FROM [|PREFIX|]customers
						WHERE 1=1";

			if ($lastImportedDate > 0) {

				$referenceQuery = $this->accounting->getReferenceSQL("customer", '', "customerid");

				if ($referenceQuery !== '') {
					$query .= " AND (custlastmodified > " . (int)$lastImportedDate . "
									OR NOT EXISTS(" . $referenceQuery . ")) ";
				} else {
					$query .= " AND custlastmodified > " . (int)$lastImportedDate;
				}
			}

			$lastCustomerId = $this->retrieveLastInsertedNodeId();

			if (isId($lastCustomerId)) {
				$query .= " AND customerid > " . $lastCustomerId;
			}

			$query .= " ORDER BY customerid ASC
						LIMIT 1";

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

			if (!$row) {
				return false;
			}

			$customer = $this->entityAPI->get($row["customerid"]);
		} else {
			$customer = $this->entityAPI->get($customerId);
		}

		if (!is_array($customer)) {
			return false;
		}

		$reference = $this->accounting->getReference($this->type, '', '', $customer["customerid"], true);

		/**
		 * Don't check the sync dates if $customerId was set as it was already checked before
		 */
		if (!isId($customerId) && is_array($reference) && !$this->canSyncToQB($customer, $reference)) {
			$this->storeInsertedNodeId($customer["customerid"]);
			return $this->findNextCustomerRecord();
		} else {
			if (!is_array($reference) || empty($reference)) {
				$service = "add";
			} else {
				$service = "edit";
			}

			return array("service" => $service, "data" => $customer["customerid"]);
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

				$childService = $this->findNextCustomerRecord();

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

								$childService = $this->findNextCustomerRecord();

								if (is_array($childService)) {
									return $this->execChildService($this->type, "query", $childService["data"]);
								} else {
									break;
								}
							} else {
								$childService = $this->findNextCustomerRecord($lastKid["nodeId"]);
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