<?php

include_once(dirname(__FILE__) . "/service.syncbase.php");

class ACCOUNTING_QUICKBOOKS_SERVICE_PRODUCTLEVELSYNC extends ACCOUNTING_QUICKBOOKS_SERVICE_SYNCBASE
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

		$this->type = "productlevel";
		$this->disableAutoRecordReference = true;
	}

	/**
	 * Update the product inventory levels from QB
	 *
	 * Method will override the parent::syncResponseRecords2Store() function to update the product inventory levels
	 * with the data from QB
	 *
	 * @access public
	 * @param array $responseData The reponse data from QB
	 * @return bool TRUE if the product levels were updated, FALSE on error
	 */
	public function syncResponseRecords2Store($responseData)
	{
		if (!is_array($responseData)) {
			$xargs = func_get_args();
			throw new QBException("Invalid arguments when syncing customer record from QB", $xargs);
		}

		/**
		 * No product levels, which would be the case if there was no products in QB
		 */
		if (!array_key_exists("ItemInventoryRet", $responseData) || empty($responseData["ItemInventoryRet"])) {
			return true;
		}

		/**
		 * OK, get a list of all the "products" (products and variations) to be used as a map
		 */
		$productMap = array();

		foreach (array("product", "productvariation") as $refType) {
			$products = $this->accounting->getReferencesByType($refType);

			if (!is_array($products)) {
				continue;
			}

			foreach ($products as $product) {
				$productMap[$product["accountingrefexternalid"]] = array(
					"nodeId" => $product["accountingrefnodeid"],
					"type" => $refType
				);
			}
		}

		/**
		 * Alright, we got the map, now loop through the resposse data
		 */
		foreach ($responseData["ItemInventoryRet"] as $response) {
			if (!is_array($response) || !isset($response["ListID"]) || !isset($response["QuantityOnHand"])) {
				continue;
			}

			if (!array_key_exists($response["ListID"], $productMap) || trim($response["QuantityOnHand"]) == '') {
				continue;
			}

			$product = $productMap[$response["ListID"]];

			if ($product["type"] == "productvariation") {
				$saveData = array("vcstock" => (int)$response["QuantityOnHand"]);
				$columnName = "combinationid";
				$tableName = "product_variation_combinations";
			} else {
				$saveData = array("prodcurrentinv" => (int)$response["QuantityOnHand"]);
				$columnName = "productid";
				$tableName = "products";
			}

			$GLOBALS["ISC_CLASS_DB"]->UpdateQuery($tableName, $saveData, $columnName . " = " . $product["nodeId"]);
		}

		return true;
	}

	public function execRequest()
	{
		/**
		 * We need to check first if we are syncing our product levels to QB or vise-versa
		 */
		if ($this->accounting->getValue("invlevels") == ACCOUNTING_QUICKBOOKS_TYPE_QUICKBOOKS) {

			$state = $this->getSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_PRODLEVEL_QUERY);

			switch ($state) {

				case ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_PRODLEVEL_QUERY:
					$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_PRODLEVEL_QUERY);

					$nodeData = array(
						"Products" => array()
					);

					foreach (array("product", "productvariation") as $refType) {
						$products = $this->accounting->getReferencesByType($refType);

						if (!is_array($products)) {
							continue;
						}

						foreach ($products as $product) {
							$nodeData["Products"][] = $product["accountingrefexternalid"];
						}
					}

					/**
					 * Do we have any data?
					 */
					if (empty($nodeData["Products"])) {
						return $this->execNextService();
					}

					return $this->execChildService("productlevel", "query", $nodeData);
					break;

				case ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_PRODLEVEL_QUERY:

					reset($this->spool["children"]);
					$childSpool = current($this->spool["children"]);

					/**
					 * If there were no errors then that means that there are product level records to sync up
					 */
					if ($childSpool["errNo"] == 0) {
						$this->syncResponseRecords2Store($childSpool["response"]);
					}

					break;
			}

		/**
		 * Else we are the authoritative on the product levels
		 */
		} else {

			$state = $this->getSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_PRODLEVEL_QUERY);

			switch ($state) {

				case ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_PRODLEVEL_QUERY:
					$this->setSyncState(ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_PRODLEVEL_QUERY);

					$nodeData = array(
						"Products" => array()
					);

					$products = $this->accounting->getReferencesByType("product");
					$variations = $this->accounting->getReferencesByType("productvariation");

					/**
					 * Get all the product inventory levels
					 */
					if (is_array($products) && !empty($products)) {

						$parsedProducts = array();
						foreach ($products as $product) {
							$parsedProducts[$product["accountingrefnodeid"]] = $product["accountingrefexternalid"];
						}

						$query = "SELECT productid, prodcurrentinv
									FROM [|PREFIX|]products
									WHERE prodinvtrack = 1";

						$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

						while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
							if (!array_key_exists($row["productid"], $parsedProducts)) {
								continue;
							}

							$nodeData["Products"][] = array(
								"ListID" => $parsedProducts[$row["productid"]],
								"NewQuantity" => $row["prodcurrentinv"]
							);
						}
					}

					/**
					 * Get all the variation inventory levels
					 */
					if (is_array($variations) && !empty($variations)) {

						$parsedVariations = array();
						foreach ($variations as $variation) {
							$parsedVariations[$variation["accountingrefnodeid"]] = $variation["accountingrefexternalid"];
						}

						$query = "SELECT c.combinationid, c.vcstock
									FROM [|PREFIX|]product_variation_combinations c
										JOIN [|PREFIX|]products p ON c.vcproductid = p.productid AND c.vcvariationid = p.prodvariationid
									WHERE p.prodinvtrack = 2";

						$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

						while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
							if (!array_key_exists($row["combinationid"], $parsedVariations)) {
								continue;
							}

							$nodeData["Products"][] = array(
								"ListID" => $parsedVariations[$row["combinationid"]],
								"NewQuantity" => $row["vcstock"]
							);
						}
					}

					/**
					 * Do we have any data?
					 */
					if (empty($nodeData["Products"])) {
						return $this->execNextService();
					}

					return $this->execChildService("productlevel", "add", $nodeData);
					break;
			}
		}

		return $this->execNextService();
	}
}
