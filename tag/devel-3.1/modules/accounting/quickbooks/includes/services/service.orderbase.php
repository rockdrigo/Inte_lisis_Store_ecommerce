<?php

abstract class ACCOUNTING_QUICKBOOKS_SERVICE_ORDERBASE extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
{
	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @param array $spool The formatted spool array that we are working with
	 * @param object $accounting The accounting object
	 * @param bool $isResponse TRUE to specify thatb this service is a response, FALSE for a request. Default is FALSE
	 * @return void
	 */
	public function __construct($spool, $accounting, $isResponse=false)
	{
		parent::__construct($spool, $accounting, $isResponse);

		$this->referenceDataSetup = array(
						"TxnID",
						"EditSequence",
						"TimeCreated",
						"TimeModified"
		);

		$this->referenceDataExternalKey = "TxnID";
	}

	/**
	 * Handle the order response
	 *
	 * Method will handle all the order responses. Mainly it is used for recording the "orderitem" references
	 *
	 * @access public
	 * @return bool The output of the parent execResponse method on success, throw an error on failure
	 */
	public function execResponse()
	{
		$output = parent::execResponse();

		if (isc_strtolower(trim($this->spool["service"])) == "query") {
			return $output;
		}

		/**
		 * If we died at the parent then there is no point in continuing
		 */
		if (!$output) {
			return $output;
		}

		if ($this->accounting->getValue("orderoption") == "order") {
			$salesLineRetTag = "SalesOrderLineRet";
		} else {
			$salesLineRetTag = "SalesReceiptLineRet";
		}

		/**
		 * Record the TxnLineID's for all the products
		 */
		if (!isset($this->spool["response"][$salesLineRetTag]) || !is_array($this->spool["response"][$salesLineRetTag])) {
			throw new QBException("Unable to find the " . $salesLineRetTag . " records for order ID: " . $this->spool["nodeId"], array("Tag" => $salesLineRetTag, " response" => $this->spool["response"]));
		}

		/**
		 * Remove all the TxnListID records for this order first as QB will change the TxnListID records EVERYTIME
		 * you add/edit an order (fun times)
		 */
		$searchData = array(
			"OrderID" => $this->spool["nodeId"]
		);

		$orderItemRef = $this->accounting->getReference("orderitem", $searchData, '', '', false);

		while (is_array($orderItemRef)) {
			$this->accounting->unsetReference("orderitem", $orderItemRef["accountingrefid"]);
			$orderItemRef = $this->accounting->getReference("orderitem", $searchData, '', '', false);
		}

		foreach ($this->spool["response"][$salesLineRetTag] as $productData) {

			if (!array_key_exists("TxnLineID", $productData)) {
				throw new QBException("Unable to find product TxnLineID for order ID: " . $this->spool["nodeId"], array("order" => $this->spool["nodeData"], "product" => $productData));
			}

			if (!array_key_exists("ItemRef", $productData) || !is_array($productData["ItemRef"]) || !array_key_exists("ListID", $productData["ItemRef"])) {
				throw new QBException("Unable to find product ListID for order ID: " . $this->spool["nodeId"], array("order" => $this->spool["nodeData"], "product" => $productData));
			}

			/**
			 * OK, recird the TxnListID. Unfortunately we do not know if this product is a normal one, a variation, a shipping cost
			 * or a tax component, so check for all
			 */
			$checkTypes = array("product", "productvariation", "prerequisite");
			$productType = "";

			foreach ($checkTypes as $checkType) {
				if ($this->accounting->getReference($checkType, '', $productData["ItemRef"]["ListID"], '', false)) {
					$productType = $checkType;
					break;
				}
			}

			/**
			 * If no reference then something is wrong
			 */
			if (trim($productType) == '') {
				throw new QBException("Unable to find product reference for order ID: " . $this->spool["nodeId"], array("order" => $this->spool["nodeData"], "product" => $productData));
			}

			/**
			 * OK, we've got the "product" reference data, now create the orderitem reference
			 */
			$referenceData = array(
				"TxnLineID" => $productData["TxnLineID"],
				"ListID" => $productData["ItemRef"]["ListID"],
				"OrderID" => $this->spool["nodeId"],
				"Type" => $productType
			);
			/**
			 * This one is setting up the orderitem (product) in the database in the accountingref table
			 */
			$refId = $this->accounting->setReference("orderitem", $referenceData, '', $productData["TxnLineID"], $prodRef["accountingrefnodeid"]);

			if (!isId($refId)) {
				throw new QBException("Unable to create product reference for order ID: " . $this->spool["nodeId"], array("order" => $this->spool["nodeData"], "product" => $productData));
			}
		}

		return $output;
	}

	/**
	 * Check to see if the customer has been imported and create a child service if not
	 *
	 * Method will check to see if the customer in the customer data array $customer has already been imported. If the
	 * customer has not been imported then method will create a child service and return the output from that service.
	 * The service will be created using the self::execChildService() method, so you don't have to worry about setting
	 * the current spool and all that
	 *
	 * @access protected
	 * @param array $customer The optional customer data array. Default will look for $this->spool["nodeData"]["customer"]
	 * @return string The output of the child service if the customer has not been created, TRUE if already created,
	 *                throw a QBException on error
	 */
	protected function validateCustomer($customer=null)
	{
		if (!is_array($customer)) {
			$nodeData = $this->spool["nodeData"];
			if (array_key_exists("customer", $nodeData)) {
				$customer = $nodeData["customer"];
			}
		}

		/**
		 * Firstly we need to check if this order has a customer id but pointing nowhere (meaning the order was created
		 * but the customer was deleted later on). The orders are filter out beforehand if this is the case and it hasn't
		 * been imported into QB yet, so this will only work on orders that have already been synced across
		 */
		if (isId($this->spool["nodeData"]["ordcustid"]) && !is_array($customer)) {
			return true;
		}

		/**
		 * If we have no customer then check to see if we have guest checkout enabled
		 */
		$hasGuestCheckout = false;
		if (GetConfig("GuestCheckoutEnabled")) {
			$hasGuestCheckout = true;
		}

		if (!is_array($customer) && !$hasGuestCheckout) {
			throw new QBException("Unable to find customer data for " . get_class($this), $customer);
		}

		if (is_array($customer) && (!array_key_exists("customerid", $customer) || !isId($customer["customerid"]))) {
			throw new QBException("Unable to find customer ID for " . get_class($this), $customer);
		}

		if (is_array($customer)) {
			$ref = $this->accounting->getReference("customer", '', '', $customer["customerid"]);
		} else {
			$searchData = array(
								"OrderID" => $this->spool["nodeId"],
								"FirstName" => $nodeData["ordbillfirstname"],
								"LastName" => $nodeData["ordbilllastname"]
			);

			$ref = $this->accounting->getReference("customerguest", $searchData);
		}

		/**
		 * Are we already imported? If so then return true
		 */
		if ($ref) {
			 return true;
		} else {

			/**
			 * Check through all the kids to see if we already added this but failed. If that is the case
			 * then throw an error
			 */
			foreach ($this->spool["children"] as $child) {
				if ($child["nodeType"] == "customer" && $child["errNo"] > 0) {
					throw new QBException("Unable to create customer for " . get_class($this), array("order" => $this->spool, "customer" => $child));
				}

				if ($child["nodeType"] == "customerguest" && $child["errNo"] > 0) {
					throw new QBException("Unable to create customer (guest) for " . get_class($this), array("order" => $this->spool, "customer" => $child));
				}
			}

			if (is_array($customer)) {
				return $this->execChildService("customer", "add", $customer["customerid"]);
			} else {
				return $this->execChildService("customerguest", "add", $searchData);
			}
		}
	}

	/**
	 * Check to see if all the products have been imported and create a child service if not
	 *
	 * Method will check to see if each product in the products data array $products have already been imported. If a
	 * product has not been imported then method will create a child service and return the output from that service.
	 * The service will be created using the self::execChildService() method, so you don't have to worry about setting
	 * the current spool and all that
	 *
	 * @access protected
	 * @param array $products The optional array of product data arrays. Default will look for $this->spool["nodeData"]["products"]
	 * @return string The output of the child service if a product has not been created, NULL if already created,
	 *                throw a QBException on error
	 */
	protected function validateProducts($products=null)
	{
		if (!is_array($products)) {
			$nodeData = $this->spool["nodeData"];
			if (array_key_exists("products", $nodeData)) {
				$products = $nodeData["products"];
			}
		}

		if (!is_array($products)) {
			throw new QBException("Unable to find product data for " . get_class($this), $customer);
		}

		foreach ($products as $product) {
			if (!array_key_exists("productid", $product) || !isId($product["productid"])) {
				throw new QBException("Unable to find product ID for " . get_class($this), $product);
			}

			if (isset($product["prodordvariationid"]) && isId($product["prodordvariationid"])) {
				$prodType = "productvariation";
				$prodId = $product["prodordvariationid"];
			} else {
				$prodType = "product";
				$prodId = $product["productid"];
			}

			$ref = $this->accounting->getReference($prodType, '', '', $prodId);

			/**
			 * Are we already imported? If so then look for the next one, else create a run a child spool
			 */
			if ($ref) {
				continue;
			} else {

				/**
				 * Loop through all the child services. If any of them failed AND it is the same as this current
				 * child product spool then throw an error
				 */
				$pass = true;

				foreach ($this->spool["children"] as $child) {
					if ($child["errNo"] > 0 && $child["nodeType"] == $prodType && $child["nodeId"] == $prodId) {
						throw new QBException("Unable to create " . $prodType . " for " . get_class($this), array("order" => $this->spool, "product" => $child));
					}
				}

				/**
				 * Else run the product service
				 */
				return $this->execChildService($prodType, "add", $prodId);
			}
		}

		return true;
	}

	/**
	 * Validate the uniqueness of the order on QuickBooks
	 *
	 * Method will check to see if the last child service was a query. If not then create and execute a query child
	 * service. If it was then check the response to see if this order does not exist in QuickBooks
	 *
	 * @access protected
	 * @return mixed The output string if the child service was create, TRUE if it is unique, FALSE if it is not
	 */
	protected function validateUniqueOrder($setAsCurrent=true)
	{
		/**
		 * Do we have any kids and is the last one an order query?
		 */
		$createService = false;
		$lastKid = false;

		if (!is_array($this->spool["children"]) || empty($this->spool["children"])) {
			$createService = true;
		} else {
			$kids = $this->spool["children"];
			$lastKid = end($kids);

			if ($lastKid["nodeType"] !== "order" || $lastKid["service"] !== "query") {
				$createService = true;
			}
		}

		if ($createService) {
			return $this->execChildService("order", "query", $this->spool["nodeData"]);
		}

		/**
		 * OK, the child query was already done, now we look at the result and if this order is unique
		 */
		if ($lastKid["errNo"] > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Handle the order errors
	 *
	 * Method will handle all the order errors. This is defined as adding and editing an order will most likely
	 * have the same errors and (long) solutions
	 *
	 * @access protected
	 * @return string The child service output if needed, else thow an exception on error
	 */
	protected function handleOrderError()
	{
		switch ($this->spool["errNo"]) {
			case 3000:

				/**
				 * Unfortunately QB doesn't set aside the failed ListID by itself so we'll need to compare
				 * it against the error message
				 */
				$customer = @$this->spool["nodeData"]["customer"];
				$products = @$this->spool["nodeData"]["products"];

				if (!is_array($customer) || empty($customer)) {
					throw new QBException("Missing/Invalid customer record in the order spool", $this->spool);
				}

				if (!is_array($products) || empty($products)) {
					throw new QBException("Missing/Invalid product record in the order spool", $this->spool);
				}

				$reference = $this->getReference("customer", '', '', $customer["customerid"], false);
				$pass = true;

				if (!is_array($reference)) {
					$pass = false;
				} else if (!isset($reference["accountingrefexternalid"]) || strpos($this->spool["errMsg"], $reference["accountingrefexternalid"]) !== -1) {
					$pass = false;
					$this->unsetReference("customer", $reference["accountingrefid"]);
				}

				/**
				 * Did the  customer pass?
				 */
				if (!$pass) {
					return $this->execChildService("customer", "add", $customer);
				}

				/**
				 * If it did then it must be in the products
				 */
				foreach ($products as $prodData) {
					if (isset($prodData["prodvariationid"]) && isId($prodData["prodvariationid"])) {
						$prodType = "productvariation";
						$prodId = $prodData["prodvariationid"];
					} else {
						$prodType = "product";
						$prodId = $prodData["productid"];
					}

					$reference = $this->getReference($prodType, '', '', $prodId, false);
					$pass = true;

					if (!is_array($reference)) {
						$pass = false;
					} else if (!isset($reference["accountingrefexternalid"]) || strpos($this->spool["errMsg"], $reference["accountingrefexternalid"]) !== -1) {
						$pass = false;
						$this->unsetReference($prodType, $reference["accountingrefid"]);
					}

					/**
					 * Did the product pass?
					 */
					if (!$pass) {
						return $this->execChildService($prodType, "add", $prodId);
					}
				}

				/**
				 * If we are here then I've NFI on what happened
				 */
				throw new QBException("Invalid order data. Either missing customer and products OR something else", $this->spool);
				break;
		}
	}
}