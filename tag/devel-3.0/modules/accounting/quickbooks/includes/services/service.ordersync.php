<?php

include_once(dirname(__FILE__) . "/service.syncbase.php");
include_once(dirname(__FILE__) . "/service.customersync.php");
include_once(dirname(__FILE__) . "/service.productsync.php");
include_once(dirname(__FILE__) . "/service.productvariationsync.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_ORDERSYNC extends ACCOUNTING_QUICKBOOKS_SERVICE_SYNCBASE
{
	const tmpOrderDataSessKey = "TmpOrderData";

	private $customerAPI;
	private $productAPI;
	private $productVariationAPI;

	private $customerSyncAPI;
	private $productSyncAPI;

	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @param array $spool The formatted spool array that we are working with
	 * @param object $accounting The accounting object
	 * @param bool $isResponse TRUE to specify that this service is a response, FASLE for a request. Default is FALSE
	 * @return void
	 */
	public function __construct($spool, $accounting, $isResponse=false)
	{
		parent::__construct($spool, $accounting, $isResponse);

		$this->customerAPI = new ISC_ENTITY_CUSTOMER();
		$this->productAPI = new ISC_ENTITY_PRODUCT();
		$this->productVariationAPI = new ISC_ENTITY_PRODUCTVARIATION();

		$this->entityAPI = new ISC_ENTITY_ORDER();
		$this->type = "order";
		$this->referenceDataSetup = array(
						"TxnID",
						"EditSequence",
						"TimeCreated",
						"TimeModified"
		);

		$this->customerSyncAPI = new ACCOUNTING_QUICKBOOKS_SERVICE_CUSTOMERSYNC(array(), $this->accounting, $isResponse);
		$this->productSyncAPI = new ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTSYNC(array(), $this->accounting, $isResponse);
		$this->productVariationSyncAPI = new ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTVARIATIONSYNC(array(), $this->accounting, $isResponse);

		$this->referenceDataExternalKey = "TxnID";
	}

	private function setCustomer2Order($orderTxnId, $customerId)
	{
		if (trim($orderTxnId) == '') {
			return false;
		}

		$orderData = $this->accounting->getImportSessionValue(self::tmpOrderDataSessKey);

		if (!is_array($orderData)) {
			$orderData = array();
		}

		if (!array_key_exists($orderTxnId, $orderData)) {
			$orderData[$orderTxnId] = array(
										"customerId" => null,
										"products" => array()
			);
		}

		/**
		 * Mark as 0 for guest checkout
		 */
		if (!isId($customerId)) {
			$customerId = 0;
		}

		$orderData[$orderTxnId]["customerId"] = $customerId;

		if (!$this->accounting->setImportSessionValue(self::tmpOrderDataSessKey, $orderData)) {
			return false;
		}

		return true;
	}

	private function getCustomer4Order($orderTxnId)
	{
		if (trim($orderTxnId) == '') {
			return false;
		}

		$orderData = $this->accounting->getImportSessionValue(self::tmpOrderDataSessKey);

		if (!is_array($orderData) || !isset($orderData[$orderTxnId]) || !isset($orderData[$orderTxnId]["customerId"])) {
			return null;
		}

		return $orderData[$orderTxnId]["customerId"];
	}

	private function setProductListId2Order($orderTxnId, $productListId, $productType, $productNodeData, $productResponse)
	{
		if (trim($orderTxnId) == '') {
			return false;
		}

		$orderData = $this->accounting->getImportSessionValue(self::tmpOrderDataSessKey);

		if (!is_array($orderData)) {
			$orderData = array();
		}

		if (!array_key_exists($orderTxnId, $orderData)) {
			$orderData[$orderTxnId] = array(
										"customerId" => null,
										"products" => array()
			);
		}

		if (trim($productListId) == '' || trim($productType) == '' || !is_array($productNodeData) || !is_array($productResponse)) {
			return false;
		}

		$orderData[$orderTxnId]["products"][$productListId] = array(
										"productType" => $productType,
										"productNodeData" => $productNodeData,
										"productResponse" => $productResponse
		);

		return $this->accounting->setImportSessionValue(self::tmpOrderDataSessKey, $orderData);
	}

	private function getProductListIds4Order($orderTxnId)
	{
		if (trim($orderTxnId) == '') {
			return false;
		}

		$orderData = $this->accounting->getImportSessionValue(self::tmpOrderDataSessKey);

		if (!is_array($orderData) || !isset($orderData[$orderTxnId]) || !isset($orderData[$orderTxnId]["products"])) {
			return null;
		}

		return $orderData[$orderTxnId]["products"];
	}

	private function checkProductListId4Order($orderTxnId, $productListId)
	{
		$xargs = func_get_args();

		if (trim($orderTxnId) == '' || trim($productListId) == '') {
			return false;
		}

		$products = $this->getProductListIds4Order($orderTxnId);

		if (!is_array($products) || !array_key_exists($productListId, $products)) {
			return false;
		}

		return true;
	}

	/**
	 * Do a order search based on the response data
	 *
	 * Methood will search for the order record based on the response data
	 *
	 * @access protected
	 * @param array $response The response data from QB
	 * @return array The order record on success, FALSE if it cannot be found
	 */
	protected function searchNodeByDB($response)
	{
		if (!is_array($response)) {
			return false;
		}

		$nodeId = null;

		if (array_key_exists("RefNumber", $response) && trim($response["RefNumber"]) !== '') {
			$nodeId = $this->accounting->qbOrderRefNum2OrderId($response["RefNumber"]);
		}

		if (is_null($nodeId) || !isId($nodeId)) {
			return false;
		}

		$entity = $this->entityAPI->get($nodeId);
		$query = "
				SELECT *
				FROM [|PREFIX|]order_addresses a
				LEFT JOIN [|PREFIX|]order_shipping s ON (s.order_address_id=a.id)
				WHERE a.order_id=" . $nodeId;
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)){
			foreach($row as $key=>$value){
				$entity[$key] = $value;
			}
		}

		return $entity;
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

		if (!array_key_exists("ordlastmodified", $nodeData)) {
			return false;
		}

		if (!array_key_exists("TimeModified", $responseData)) {
			return false;
		}

		$dates = array(
			"node" => $nodeData["ordlastmodified"],
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
	 * @access protected
	 * @param array $responseData The reponse data from QB
	 * @param array $nodeData The optional node data array. If set then update, else insert
	 * @return int The new or updtaed node ID on success, FALSE on error
	 */
	protected function syncResponseRecord2Store($responseData, $nodeData=false)
	{
		if (!is_array($responseData)) {
			$xargs = func_get_args();
			throw new QBException("Invalid arguments when syncing order record from QB", $xargs);
		}

		/**
		 * Get our stored customer ID if we have one
		 */
		$customerId = $this->getCustomer4Order($responseData["TxnID"]);

		/**
		 * Get the customer ListID and find the matching customer ID if we can. We need to have a customer ListID
		 */
		if (is_null($customerId)) {
			if (!isset($responseData["CustomerRef"]["ListID"])) {
				throw new QBException("Unable to find customer ListID when syncing orders", $responseData);
			}

			$customerRef = $this->accounting->getReference("customer", '', $responseData["CustomerRef"]["ListID"], '', false);

			/**
			 * If we don't have a reference then use the existing customer ID if we have one
			 */
			if (!is_array($customerRef)) {

				if (is_array($nodeData) && array_key_exists("ordcustid", $nodeData) && isId($nodeData["ordcustid"])) {
					$customerId = $nodeData["ordcustid"];

				/**
				 * Else if we do have a nodeData but no customer ID then its a guest checkout
				 */
				} else if (is_array($nodeData) && (!array_key_exists("ordcustid", $nodeData) || !isId($nodeData["ordcustid"]))) {
					$customerId = '';

				/**
				 * Else it is a new customer which we do not have a record for
				 */
				} else {

					$lastKid = end($this->spool["children"]);

					if ($lastKid["nodeType"] == "customer" && $lastKid["service"] == "query") {

						/**
						 * If we couldn't find the record then error out
						 */
						if ($lastKid["errNo"] > 0) {
							throw new QBException("Unable to find customer when syncing orders from QB", $responseData["CustomerRef"]["ListID"]);
						}

						/**
						 * Check to see if this is an anonymous customer (guest checkout). If so then don't create a customer record
						 */
						if ($this->accounting->isCustomerGuestCheckout($lastKid["response"])) {
							$customerId = '';

						/**
						 * Else it is a real customer so create it
						 */
						 } else {

							$customerId = $this->customerSyncAPI->syncResponseRecord2Store($lastKid["response"]);

							if (!isId($customerId)) {
								throw new QBException("Unable to create customer record when syncing orders from QB", $lastKid["response"]);
							}

							$referenceDataSetup = $this->customerSyncAPI->referenceDataSetup;
							$referenceDataExternalKey = $this->customerSyncAPI->referenceDataExternalKey;

							$referenceReturn = $this->setReferenceDataStatically("customer", $customerId, $lastKid["response"], '', $referenceDataSetup, $referenceDataExternalKey);

							if (isId($referenceReturn)) {
								$customerRef = $this->accounting->getReference("customer", $referenceReturn, '', '', false);
							}
						}

					} else {
						$childRequestData = array(
												"ListID" => $responseData["CustomerRef"]["ListID"]
						);

						return $this->execChildService("customer", "query", $childRequestData);
					}
				}

			/**
			 * Else we have the customer but not the order yet
			 */
			} else if (is_array($customerRef)) {
				$customerId = $customerRef["accountingrefnodeid"];

			/**
			 * Else we got no customer
			 */
			} else {
				$customerId = '';
			}

			/**
			 * Save the customer ID for this order
			 */
			$this->setCustomer2Order($responseData["TxnID"], $customerId);
		}

		/**
		 * If we have a custom ID then get the customer record as we'll need it later on
		 */
		$customerNodeData = '';

		if (isId($customerId)) {
			$customerNodeData = $this->customerAPI->get($customerId);
		}

		if ($this->accounting->getValue("orderoption") == "order") {
			$salesLineRetTag = "SalesOrderLineRet";
		} else {
			$salesLineRetTag = "SalesReceiptLineRet";
		}

		/**
		 * OK, we got the customer, now we need to get all the products
		 */

		if (!isset($responseData[$salesLineRetTag]) || !is_array($responseData[$salesLineRetTag]) || empty($responseData[$salesLineRetTag])) {
			throw new QBException("Missing/Invalid product array when syncing orders", array("tag" => $salesLineRetTag, "response" => $responseData));
		}

		/**
		 * Set aside some vars for shipping costs and the tax component
		 */
		$productSubTotal = 0;
		$shippingCost = 0;
		$taxCost = 0;

		/**
		 * Sanatize it
		 */
		if (!isset($responseData[$salesLineRetTag][0])) {
			$responseData[$salesLineRetTag] = array($responseData[$salesLineRetTag]);
		}

		foreach ($responseData[$salesLineRetTag] as $product) {

			/**
			 * Check to see if we have already recorded this product
			 */
			if ($this->checkProductListId4Order($responseData["TxnID"], $product["ItemRef"]["ListID"])) {
				continue;
			}

			/**
			 * OK, we haven't done this one yet so lets do it. If we have any kids then deal with them first
			 */
			$lastKid = end($this->spool["children"]);

			if ($lastKid["service"] == "query" && ($lastKid["nodeType"] == "product" || $lastKid["nodeType"] == "productvariation")) {

				/**
				 * If we couldn't find the record then error out
				 */
				if ($lastKid["errNo"] > 0) {
					throw new QBException("Unable to find product when syncing orders from QB", $product["ItemRef"]["ListID"]);
				}

				/**
				 * Else try to add in this product/variation
				 */
				if ($lastKid["nodeType"] == "productvariation") {
					$productFatcory =& $this->productVariationSyncAPI;
				} else {
					$productFatcory =& $this->productSyncAPI;
				}

				$productData = $productFatcory->searchNodeByDB($lastKid["response"]);
				$productId = $productFatcory->syncResponseRecord2Store($lastKid["response"], $productData);

				/**
				 * Dam! We can't add it. Error out of here as we really needed that product for the order
				 */
				if (!isId($productId)) {
					throw new QBException("Unable to create product/variation record when syncing orders from QB", $lastKid["response"]);
				}

				/**
				 * Set the reference for this product
				 */
				$referenceDataSetup = $productFatcory->referenceDataSetup;
				$referenceDataExternalKey = $productFatcory->referenceDataExternalKey;

				$this->setReferenceDataStatically($lastKid["nodeType"], $productId, $lastKid["response"], '', $referenceDataSetup, $referenceDataExternalKey);
			}

			/**
			 * There aren't any query kids so try and find the reference for this product/variation/other product
			 */
			$checkTypes = array("product", "productvariation", "prerequisite");
			$productRef = "";
			$productType = "";

			foreach ($checkTypes as $checkType) {
				$productRef = $this->accounting->getReference($checkType, '', $product["ItemRef"]["ListID"], '', false);

				if (is_array($productRef)) {
					$productType = $checkType;
					break;
				}
			}

			/**
			 * Check to see if this is a prerequisite (shipping & tax costs)
			 */
			if ($productType == "prerequisite") {
				switch (isc_strtolower(trim($productRef["accountingrefvalue"]["Type"]))) {
					case "shipping":
						$cost = ($product["Quantity"] * $product["Rate"]);
						break;

					case "tax":
						$cost = ($product["Quantity"] * $product["Rate"]);
						break;
				}

				$productNodeData = array(
										"Type" => isc_strtolower(trim($productRef["accountingrefvalue"]["Type"])),
										"Cost" => $cost
				);

				$this->setProductListId2Order($responseData["TxnID"], $product["ItemRef"]["ListID"], $productType, $productNodeData, $product);

				/**
				 * We don't want to insert this in the order_products table
				 */
				continue;
			}

			/**
			 * OK, prerequisites are done, now for the rest. If no reference then send out a query child
			 */
			if (!is_array($productRef)) {

				if ($this->accounting->isProductVariationShortName($product["ItemRef"]["FullName"])) {
					$productType = "productvariation";
				} else {
					$productType = "product";
				}

				$childRequestData = array(
										"ListID" => $product["ItemRef"]["ListID"]
				);

				return $this->execChildService($productType, "query", $childRequestData);
			}

			/**
			 * Must have a reference by now
			 */
			if (!is_array($productRef)) {
				throw new QBException("Unable to find product reference when syncing order ID: " . $this->spool["nodeId"], $responseData);
			}

			$prodNodeData = '';

			if ($productType == "productvariation") {
				$prodNodeData = $this->productVariationAPI->get($productRef["accountingrefnodeid"]);
			} else {
				$prodNodeData = $this->productAPI->get($productRef["accountingrefnodeid"]);
			}

			/**
			 * If no prodNodeData then no go
			 */
			if (!is_array($prodNodeData)) {
				throw new QBException("Unable to find " . $productType . " node data when syncing order ID: " . $this->spool["nodeId"], array("order" => $responseData, "prodNodeId" => $productRef["accountingrefnodeid"]));
			}

			/**
			 * Lastly, save this product to our tmp cache
			 */
			$this->setProductListId2Order($responseData["TxnID"], $product["ItemRef"]["ListID"], $productType, $prodNodeData, $product);
		}

		/**
		 * OK, now retrieve all our product from our tmp cache to build the products for this order
		 */
		$products = array();
		$taxCost = $shippingCost = 0;
		$cacheProducts = $this->getProductListIds4Order($responseData["TxnID"]);

		if (!is_array($cacheProducts) || empty($cacheProducts)) {
			throw new QBException("Empty product cache array when syncing order ID: " . $this->spool["nodeId"], $responseData);
		}

		foreach ($cacheProducts as $productListId => $product) {

			/**
			 * Add up our stored shipping and tax costs if we have any
			 */
			if ($product["productType"] == "prerequisite") {
				switch (isc_strtolower(trim($product["productNodeData"]["Type"]))) {
					case "shipping":
						$shippingCost = $product["productNodeData"]["Cost"];
						break;

					case "tax":
						$taxCost = $product["productNodeData"]["Cost"];
						break;
				}

				continue;
			}

			$prodCode = '';
			$prodVariationId = 0;
			$prodOptions = array();

			if ($product["productType"] == "productvariation") {
				$prodCode = $product["productNodeData"]["vcsku"];
				$prodVariationId = $product["productNodeData"]["combinationid"];
				$prodOptions = $product["productNodeData"]["prodvariationarray"];
			}

			if (trim($prodCode) == '') {
				$prodCode = $product["productNodeData"]["prodcode"];
			}

			$products[] = array(
								"product_id" => $product["productNodeData"]["productid"],
								"product_name" => $product["productNodeData"]["prodname"],
								"product_code" => $prodCode,
								"quantity" => max(1, $product["productResponse"]["Quantity"]),
								"product_price" => $product["productResponse"]["Rate"],
								"original_price" => $product["productResponse"]["Rate"],
								"variation_id" => $prodVariationId,
								"options" => $prodOptions
			);

			/**
			 * Check to see if this is an existing product in an already existing order
			 */
			if (is_array($nodeData) && isset($nodeData["products"]) && is_array($nodeData["products"])) {
				foreach ($nodeData["products"] as $existingProduct) {
					if ($existingProduct["productid"] == $product["productNodeData"]["productid"] && isset($existingProduct["prodorderid"])) {
						$products[count($products)-1]["existing_order_product"] = $existingProduct["prodorderid"];
					}
				}
			}

			/**
			 * Add up our sub total
			 */
			$productSubTotal += $product["productResponse"]["Amount"];
		}

		/**
		 * OK, we have all the products and the customer details. Now for the actual order details
		 */
		$savedata = array(
						"ordcustid" => $customerId,
						"subtotal_ex_tax" => $productSubTotal,
						"total_tax" => $taxCost,
						"shipping_cost_ex_tax" => $shippingCost,
						"total_inc_tax" => ($productSubTotal + $taxCost + $shippingCost),
						"products" => $products
		);

		if (isset($responseData["Memo"])) {
			$savedata["ordnotes"] = $responseData["Memo"];
		}

		/**
		 * Add in the addresses
		 */
		$addressMap = array(
						"shipaddress1" => "Addr1",
						"shipaddress2" => "Addr2",
						"shipcity" => "City",
						"shipstate" => "State",
						"shipzip" => "PostalCode",
						"shipcountry" => "Country"
		);

		foreach (array("BillAddress", "ShipAddress") as $addressType) {

			if (!array_key_exists($addressType, $responseData) || !is_array($responseData[$addressType])) {
				$responseData[$addressType] = array();
			}

			if ($addressType == "BillAddress") {
				$addressKey = "billingaddress";
			} else {
				$addressKey = "shippingaddress";
			}

			$savedata[$addressKey] = array();

			foreach ($addressMap as $columnName => $refKey) {

				if (!isset($responseData[$addressType][$refKey]) && !is_array($nodeData)) {
					$responseData[$addressType][$refKey] = '';
				}

				if (isset($responseData[$addressType][$refKey])) {
					$savedata[$addressKey][$columnName] = $responseData[$addressType][$refKey];
				}
			}

			/**
			 * Find the country and state IDs
			 */
			$countryId = $this->getCountryId(@$savedata[$addressKey]["shipcountry"], $properCountryName);
			$stateId = '';

			if (isId($countryId) && trim(@$savedata[$addressKey]["shipstate"]) !== '') {
				$savedata[$addressKey]["shipcountry"] = $properCountryName;
				$stateId = $this->getStateId($savedata[$addressKey]["shipstate"], $countryId, $properStateName);
				if (!isId($stateId)) {
					$stateId = '';
				} else if (trim($properStateName) !== '') {
					$savedata[$addressKey]["shipstate"] = $properStateName;
				}
			} else {
				$countryId = '';
			}

			if (is_array($nodeData) || !isId($stateId)) {
				$savedata[$addressKey]["shipstateid"] = $stateId;
			}

			if (is_array($nodeData) || !isId($countryId)) {
				$savedata[$addressKey]["shipcountryid"] = $countryId;
			}

			/**
			 * Fill in the name. Use whatever QB gave us regardless
			 */
			$customerName = @$responseData["CustomerRef"]["FullName"];

			if ($this->accounting->isCustomerShortName($customerName)) {
				$tmpName = $this->accounting->qbCustomerShortName2CustomerNameId($customerName);
				if (is_array($tmpName) && array_key_exists("customername", $tmpName)) {
					$customerName = $tmpName["customername"];
				}
			} else if ($this->accounting->isCustomerGuestShortName($customerName)) {
				$tmpName = $this->accounting->qbCustomerGuestShortName2CustomerGuestNameId($customerName);
				if (is_array($tmpName) && array_key_exists("customerguestname", $tmpName)) {
					$customerName = $tmpName["customerguestname"];
				}
			}

			$nameParts = explode(" ", $customerName);

			if (count($nameParts) > 2) {
				$firstName = implode(" ", array_slice($nameParts, 0, count($nameParts)-1));
				$lastName = $nameParts[count($nameParts)-1];
			} else if (count($nameParts) == 1) {
				$firstName = $nameParts[0];
				$lastName = "";
			} else {
				$firstName = $nameParts[0];
				$lastName = $nameParts[1];
			}

			$savedata[$addressKey]["shipfirstname"] = $firstName;
			$savedata[$addressKey]["shiplastname"] = $lastName;

			/**
			 * Set something to each field if it is NULL as the database can't handle NULL values for this schema
			 */
			foreach ($savedata[$addressKey] as $addKey => $addVal) {
				if (is_null($addVal)) {
					$savedata[$addressKey][$addKey] = '';
				}
			}
		}

		/**
		 * If we don't have a $nodeData then we can still fill in some blanks
		 */
		if (!is_array($nodeData)) {
			$savedata["ordtoken"] = GenerateOrderToken();
			$savedata["ordstatus"] = ORDER_STATUS_COMPLETED;
			$savedata["orderpaymentmodule"] = "manual";
			$savedata["orderpaymentmethod"] = GetLang("QuickBooksDefaultPaymentName");
			$savedata["total_inc_tax"] = $savedata["totalcost"];
			$savedata["handling_cost_ex_tax"] = 0;
			$savedata["handling_cost_inc_tax"] = 0;

			if (isset($savedata["billingaddress"]["shipcountry"])) {
				$savedata["ordgeoipcountry"] = $savedata["billingaddress"]["shipcountry"];
				$savedata["ordgeoipcountrycode"] = GetCountryISO2ByName($savedata["billingaddress"]["shipcountry"]);
			}

			if (is_array($customerNodeData)) {
				$savedata["ordbillemail"] = $customerNodeData["custconemail"];
				$savedata["ordbillphone"] = $customerNodeData["custconphone"];
				$savedata["ordshipemail"] = $customerNodeData["custconemail"];
				$savedata["ordshipphone"] = $customerNodeData["custconphone"];
			}
		} else {
			$savedata["orderid"] = $nodeData["orderid"];
		}

		/**
		 * Alright, we have EVERYTHING, now create/update EVERYTHING
		 */
		$orderId = false;
		if (is_array($nodeData)) {

			/**
			 * Reset the inventory levels before we update it
			 */
			if ($this->accounting->getValue("invlevels") !== ACCOUNTING_QUICKBOOKS_TYPE_QUICKBOOKS) {
				UpdateInventoryOnReturn($savedata["orderid"]); /* /lib/orders.php */
			}

			if ($this->entityAPI->edit($savedata) !== false) {
				$orderId = $savedata["orderid"];
			}

			/**
			 * Now sync back the inventory levels
			 */
			if ($this->accounting->getValue("invlevels") !== ACCOUNTING_QUICKBOOKS_TYPE_QUICKBOOKS) {
				DecreaseInventoryFromOrder($orderId);
			}

		} else {
			$orderId = $this->entityAPI->add($savedata);

			/**
			 * Sync up the inventory levels as each order is marked as completed
			 */
			if ($this->accounting->getValue("invlevels") !== ACCOUNTING_QUICKBOOKS_TYPE_QUICKBOOKS) {
				DecreaseInventoryFromOrder($orderId);
			}
		}

		if (!isId($orderId)) {
			$this->accounting->logError("ORDER DATA", array("SaveData" => $savedata, "NodeData" => $nodeData, "DB" => $GLOBALS["ISC_CLASS_DB"]->GetError()));
			throw new QBException("Cannot save order record with data from QB", array("SaveData" => $savedata, "NodeData" => $nodeData, "DB" => $GLOBALS["ISC_CLASS_DB"]->GetError()));
		}

		return $orderId;
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
		$query = "SELECT o.orderid
					FROM [|PREFIX|]orders o WHERE o.deleted = 0";

		return $this->accounting->getReferenceSQL($this->type, '', $query, true);
	}

	/**
	 * Find the next record to insert into QB
	 *
	 * Method will find the next record to insert into QB
	 *
	 * @access private
	 * @param int $orderId The optional order record ID to retrieve. Default will find the next one
	 * @return array The order record on success, FALSE if no more records where found
	 */
	private function findNextOrderRecord($orderId=null)
	{
		static $lastImportedDate = null;

		if (!isId($orderId)) {
			if (is_null($lastImportedDate)) {
				$lastImportedDate = $this->accounting->getLastImportedTimeStamp($this->type);

				/**
				 * Minus the self::modifiedTimeFudgeFactor from the last imported date so we can pick up the items that were
				 * skipped in the previous import
				 */
				$lastImportedDate -= self::modifiedTimeFudgeFactor;
			}

			/**
			 * This query will also check to see if the customer exists and all the associated products as all these links will
			 * need to be valid when creating an order on QB
			 */
			$query = "SELECT o.orderid, IF(o.ordcustid = '', 0, 1) AS checkforcustomer, c.customerid AS customercheck,
							COUNT(op.orderprodid) AS productordercount, COUNT(p.productid) AS productcount
						FROM [|PREFIX|]orders o
							LEFT JOIN [|PREFIX|]customers c ON o.ordcustid = c.customerid
							LEFT JOIN [|PREFIX|]order_products op ON o.orderid = op.orderorderid
							LEFT JOIN [|PREFIX|]products p ON op.ordprodid = p.productid
						WHERE o.ordstatus IN (" . ORDER_STATUS_COMPLETED . "," . ORDER_STATUS_SHIPPED . ") AND o.deleted = 0 ";

			if ($lastImportedDate > 0) {

				$referenceQuery = $this->accounting->getReferenceSQL("order", '', "o.orderid");

				if ($referenceQuery !== '') {
					$query .= " AND (o.ordlastmodified > " . (int)$lastImportedDate . "
									OR NOT EXISTS(" . $referenceQuery . ")) ";
				} else {
					$query .= " AND o.ordlastmodified > " . (int)$lastImportedDate;
				}
			}

			$lastOrderId = $this->retrieveLastInsertedNodeId();

			if (isId($lastOrderId)) {
				$query .= " AND o.orderid > " . $lastOrderId;
			}

			$query .= " GROUP BY o.orderid
						ORDER BY o.orderid ASC
						LIMIT 1";

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

			if (!$row) {
				return false;
			}

			$order = $this->entityAPI->get($row["orderid"]);

			$query = "
				SELECT *
				FROM [|PREFIX|]order_addresses a
				LEFT JOIN [|PREFIX|]order_shipping s ON (s.order_address_id=a.id)
				WHERE a.order_id=" . $row['orderid'] . ' LIMIT 1';
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);
			foreach($row as $key=>$value){
				switch ($key) {
					case 'first_name' :
						$order['ordshipfirstname'] = $value;
						break;
					case 'last_name' :
						$order['ordshiplastname'] = $value;
						break;
					case 'company' :
						$order['ordshipcompany'] = $value;
						break;
					case 'address_1' :
						$order['ordshipstreet1'] = $value;
						break;
					case 'address_2' :
						$order['ordshipstreet2'] = $value;
						break;
					case 'city' :
						$order['ordshipcity'] = $value;
						break;
					case 'zip' :
						$order['ordshipzip'] = $value;
						break;
					case 'country' :
						$order['ordshipcountry'] = $value;
						break;
					case 'country_iso2' :
						$order['ordshipcountrycode'] = $value;
						break;
					case 'country_id' :
						$order['ordshipcountryid'] = $value;
						break;
					case 'state' :
						$order['ordshipstate'] = $value;
						break;
					case 'state_id' :
						$order['ordshipstateid'] = $value;
						break;
					case 'email' :
						$order['ordshipemail'] = $value;
						break;
					case 'phone' :
						$order['ordshipphone'] = $value;
						break;
					default:
						break;
				}
			}
		} else {
			$order = $this->entityAPI->get($orderId);

			$query = "
				SELECT *
				FROM [|PREFIX|]order_addresses a
				LEFT JOIN [|PREFIX|]order_shipping s ON (s.order_address_id=a.id)
				WHERE a.order_id=" . $orderId . ' LIMIT 1';
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);
			foreach($row as $key=>$value){
				switch ($key) {
					case 'first_name' :
						$order['ordshipfirstname'] = $value;
						break;
					case 'last_name' :
						$order['ordshiplastname'] = $value;
						break;
					case 'company' :
						$order['ordshipcompany'] = $value;
						break;
					case 'address_1' :
						$order['ordshipstreet1'] = $value;
						break;
					case 'address_2' :
						$order['ordshipstreet2'] = $value;
						break;
					case 'city' :
						$order['ordshipcity'] = $value;
						break;
					case 'zip' :
						$order['ordshipzip'] = $value;
						break;
					case 'country' :
						$order['ordshipcountry'] = $value;
						break;
					case 'country_iso2' :
						$order['ordshipcountrycode'] = $value;
						break;
					case 'country_id' :
						$order['ordshipcountryid'] = $value;
						break;
					case 'state' :
						$order['ordshipstate'] = $value;
						break;
					case 'state_id' :
						$order['ordshipstateid'] = $value;
						break;
					case 'email' :
						$order['ordshipemail'] = $value;
						break;
					case 'phone' :
						$order['ordshipphone'] = $value;
						break;
					default:
						break;
				}
			}
		}

		if (!is_array($order)) {
			return false;
		}

		$reference = $this->accounting->getReference($this->type, '', '', $order["orderid"], true);

		/**
		 * Don't check the sync dates if $orderId was set as it was already checked before
		 */
		if (!isId($orderId) && is_array($reference) && !$this->canSyncToQB($order, $reference)) {
			$this->storeInsertedNodeId($order["orderid"]);
			return $this->findNextOrderRecord();
		} else {
			if (!is_array($reference) || empty($reference)) {

				/**
				 * Check for the customer and all its products but only if $orderId is not an ID as it would have already
				 * been checked beforehand
				 */
				if (!isId($orderId)) {

					/**
					 * We need to check to see if we have a valid customer to submit with this order (handles guest checkout too)
					 */
					if ($row["checkforcustomer"] == "1" && !isId($row["customercheck"])) {
						$this->accounting->logError("Unable to sync (add) across order ID: " . $order["orderid"] . " as the customer has been deleted for this order");
						$this->storeInsertedNodeId($order["orderid"]);
						return $this->findNextOrderRecord();
					}

					/**
					 * We also need to check to see if we have all the products for this order
					 */
					if ($row["productordercount"] !== $row["productcount"]) {
						$this->accounting->logError("Unable to sync (add) across order ID: " . $order["orderid"] . " as some/all of the products have been deleted for this order");
						$this->storeInsertedNodeId($order["orderid"]);
						return $this->findNextOrderRecord();
					}
				}

				$service = "add";
			} else {
				$service = "edit";
			}

			return array("service" => $service, "data" => $order["orderid"]);
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
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_DELETE_QUERY_QB);

				return $this->execChildService($this->type, "querydel", array());
				break;

			/**
			 * Next we need to parse the response from the querydel service
			 */
			case ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_DELETE_QUERY_QB:
				$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_STORE);

				reset($this->spool["children"]);
				$queryKid = end($this->spool["children"]);

				/**
				 * To make this as generic as possible, we have to set it and then get it in the same op
				 */
				$this->saveDeleteQuery($queryKid["response"]);

				$deletedExternalIdx = $this->getDeleteQueryExternalIdx();

				$this->execDeleteListFromQB($deletedExternalIdx);

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
				 * Don't break here is we have no records to delete
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

				$childService = $this->findNextOrderRecord();

				/**
				 * Run the child service if we have any
				 */
				if (is_array($childService)) {
					if (!is_array($lastKid) || $lastKid["service"] !== "query") {
						if($childService["service"] == 'add') {
							return $this->execChildService($this->type, $childService["service"], $childService["data"]);
						}
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

								$childService = $this->findNextOrderRecord();

								if (is_array($childService)) {
									return $this->execChildService($this->type, "query", $childService["data"]);
								} else {
									break;
								}
							} else {
								$childService = $this->findNextOrderRecord($lastKid["nodeId"]);
							}
						}

						/**
						 * If our last kid was a query and it failed, then remove the reference and set the child service to "add", just to save time
						 */
						if ($lastKid["service"] == "query" && $lastKid["errNo"] > 0) {
							$this->accounting->unsetReference($this->type, '', '', $lastKid["nodeId"]);
							$childService["service"] = "add";
						}
						return $this->execChildService($this->type, $childService["service"], $childService['data']);
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
