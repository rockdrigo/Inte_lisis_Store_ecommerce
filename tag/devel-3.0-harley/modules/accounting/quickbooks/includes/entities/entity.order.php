<?php

class ACCOUNTING_QUICKBOOKS_ENTITY_ORDER extends ACCOUNTING_QUICKBOOKS_ENTITY_BASE
{
/**
 * Amongst other things, this class and ACCOUNTING_QUICKBOOKS_SERVICE_ORDERSYNC are the ones used to write orders from the shopping cart into QuickBooks.
 * The ones for the opposite direction (QB->ISC) are service.orderadd.php and ACCOUNTING_QUICKBOOKS_SERVICE_ORDERSYNC.
 * 
 */
	public function buildXML()
	{
		if (isc_strtolower($this->spool["service"]) == "edit" && is_array($this->spoolReferenceData)) {
			$this->writeEscapedElement("TxnID", $this->spoolReferenceData["TxnID"]);
			$this->writeEscapedElement("EditSequence", $this->spoolReferenceData["EditSequence"]);
		}

		if (isId($this->spoolNodeData["ordcustid"])) {
			$customerRef = $this->accounting->getReference("customer", '', '', $this->spoolNodeData["ordcustid"], false);
		} else {
			$searchData = array(
								"OrderID" => $this->spool["nodeId"],
								"FirstName" => $this->spoolNodeData["ordbillfirstname"],
								"LastName" => $this->spoolNodeData["ordbilllastname"]
			);

			$customerRef = $this->accounting->getReference("customerguest", $searchData, '', '', false);
		}

		/**
		 * If this is an edit service and there is no reference EVEN though there is a customer ID in the order record, then the
		 * customer must have been deleted. If this is the case then don't construct the CustomerRef (we can't really)
		 */
		$noCustomerRef = false;
		if (isc_strtolower($this->spool["service"]) == "edit" && isId($this->spoolNodeData["ordcustid"])) {
			$customerAPI = new ISC_ENTITY_CUSTOMER();

			if (!$customerAPI->get($this->spoolNodeData["ordcustid"])) {
				$noCustomerRef = true;
			}
		}

		if (!$noCustomerRef && (!is_array($customerRef) || !isset($customerRef["accountingrefexternalid"]) || trim($customerRef["accountingrefexternalid"]) == '')) {
			throw new QBException("Unable to find customer ListID for order ID: " . $this->spool["nodeId"], $this->spool);
		}

		$this->xmlWriter->startElement("CustomerRef");
		$this->writeEscapedElement("ListID", $customerRef["accountingrefexternalid"]);
		$this->xmlWriter->endElement();

		if (array_key_exists("orddate", $this->spoolNodeData)) {
			$this->writeEscapedElement("TxnDate", date("Y-m-d", $this->spoolNodeData["orddate"]));
		}

		$this->writeEscapedElement("RefNumber", $this->accounting->orderID2QBOrderRefNum($this->spool["nodeId"]));

		/**
		 * The addresses
		 */
		foreach (array("Bill", "Ship") as $addressType) {
			$addressMap = array(
							"firstname" => "firstname",
							"lastname" => "lastname",
							"address1" => "street1",
							"address2" => "street2",
							"city" => "suburb",
							"state" => "state",
							"zip" => "zip",
							"country" => "country"
			);

			$address = array();
			$addressField = "ord" . isc_strtolower($addressType);

			foreach ($addressMap as $ourField => $ordField) {
				/**
				 * JMW - Bandaid that fixes the problem for now. Need to do a cleanup of the city/suburb names throughout the module.
				 */
				if (!array_key_exists($addressField . $ordField, $this->spoolNodeData)) {
					if ($addressField . $ordField != 'ordshipsuburb') {
						continue;
					}
				}
				if ($addressField . $ordField != 'ordshipsuburb') {
					$address["ship" . $ourField] = $this->spoolNodeData[$addressField . $ordField];
				} else {
					$address["ship" . $ourField] = $this->spoolNodeData[$addressField . 'city'];
				}
			}

			if (empty($address)) {
				continue;
			}

			$this->buildAddressBlock($addressType . "Address", $address);
		}

		if (trim($this->spoolNodeData["ordnotes"]) !== '') {
			$this->writeEscapedElement("Memo", $this->spoolNodeData["ordnotes"]);
		}

		/**
		 * Now for the products
		 */
		if (!array_key_exists("products", $this->spoolNodeData) || !is_array($this->spoolNodeData["products"])) {
			throw new QBException("Unable to find products for order ID: " . $this->spool["nodeId"], $this->spool);
		}

		foreach ($this->spoolNodeData["products"] as $product) {

			if (isset($product["prodordvariationid"]) && isId($product["prodordvariationid"])) {
				$prodType = "productvariation";
				$prodId = $product["prodordvariationid"];
			} else {
				$prodType = "product";
				$prodId = $product["productid"];
			}

			$productRef = $this->accounting->getReference($prodType, '', '', $prodId, false);

			if (!is_array($productRef) || !isset($productRef["accountingrefexternalid"]) || trim($productRef["accountingrefexternalid"]) == '') {
				throw new QBException("Unable to find product ListID for order ID: " . $this->spool["nodeId"], array("order" => $this->spool, "product" => $product));
			}

			if ($this->accounting->getValue("orderoption") == "order") {
				$tagName = "SalesOrderLine";
			} else {
				$tagName = "SalesReceiptLine";
			}

			if (isc_strtolower(trim($this->spool["service"])) == "edit") {
				$this->xmlWriter->startElement($tagName . "Mod");
			} else {
				$this->xmlWriter->startElement($tagName . "Add");
			}

			/**
			 * If this is an edit then we need to check for the TxnLineID as well
			 */
			if (isc_strtolower(trim($this->spool["service"])) == "edit") {

				$searchData = array(
									"ListID" => $productRef["accountingrefexternalid"],
									"OrderID" => $this->spool["nodeId"]
				);

				$orderItemRef = $this->accounting->getReference("orderitem", $searchData, '', '', false);

				/**
				 * If there is a reference then it is an existing item, else it is a new one
				 */
				if (is_array($orderItemRef) && isset($orderItemRef["accountingrefexternalid"])) {
					$this->writeEscapedElement("TxnLineID", $orderItemRef["accountingrefexternalid"]);
				} else {
					$this->writeEscapedElement("TxnLineID", "-1");
				}
			}

			$this->xmlWriter->startElement("ItemRef");
			$this->writeEscapedElement("ListID", $productRef["accountingrefexternalid"]);
			$this->xmlWriter->endElement();

			$this->writeEscapedElement("Desc", isc_substr($product["prodname"], 0, 4000));
			$this->writeEscapedElement("Quantity", $product["prodorderquantity"]);
			$this->writeEscapedElement("Amount", number_format($product["prodorderamount"] * $product["prodorderquantity"], 2, ".", ""));

			$this->xmlWriter->endElement();
		}

		/**
		 * Now add in the shipping cost and tax if we have any (add it in regardless)
		 */
		$otherProductMap = array(
								"shipping" => "shipping_cost_ex_tax",
								"tax" => "total_tax",
								"discount" => "coupon_discount",
		);

		foreach ($otherProductMap as $refType => $columnName) {
			if (!array_key_exists($columnName, $this->spoolNodeData) || trim($this->spoolNodeData[$columnName]) == '') {
				$otherProductTotal = 0;
			} else {
				$otherProductTotal = (float)$this->spoolNodeData[$columnName];
				if($refType == 'shipping'){
					$otherProductTotal += (float)$this->spoolNodeData['handling_cost_ex_tax'];
					$otherProductTotal += (float)$this->spoolNodeData['wrapping_cost_ex_tax'];
				}elseif($refType == 'discount'){
					$otherProductTotal -= (float)$this->spoolNodeData['orddiscountamount'];
					$otherProductTotal -= (float)$this->spoolNodeData['coupon_discount'];
					$otherProductTotal -= (float)$this->spoolNodeData['coupon_discount'];
				}
			}

			/**
			 * If casting it to a float cleared it
			 */
			if (trim($otherProductTotal) == '') {
				$otherProductTotal = 0;
			}

			$otherProductListID = $this->accounting->getOtherProductListId($refType);

			if (trim($otherProductListID) == '') {
				throw new QBException("Unable to find " . $refType . " ListID for order ID: " . $this->spool["nodeId"], $this->spool);
			}

			if ($this->accounting->getValue("orderoption") == "order") {
				$tagName = "SalesOrderLine";
			} else {
				$tagName = "SalesReceiptLine";
			}

			if (isc_strtolower(trim($this->spool["service"])) == "edit") {
				$this->xmlWriter->startElement($tagName . "Mod");
			} else {
				$this->xmlWriter->startElement($tagName . "Add");
			}

			/**
			 * Same deal with the products where we have to find the TxnLineID aswell
			 */
			if (isc_strtolower(trim($this->spool["service"])) == "edit") {

				$searchData = array(
									"ListID" => $otherProductListID,
									"OrderID" => $this->spool["nodeId"],
									"Type" => $refType
				);

				$otherProductRef = $this->accounting->getReference("orderitem", $searchData, '', '', false);

				/**
				 * Is there a reference for it?
				 */
				if (is_array($otherProductRef) && isset($otherProductRef["accountingrefexternalid"])) {
					$this->writeEscapedElement("TxnLineID", $otherProductRef["accountingrefexternalid"]);
				} else {
					$this->writeEscapedElement("TxnLineID", "-1");
				}
			}

			$this->xmlWriter->startElement("ItemRef");
			$this->writeEscapedElement("ListID", $otherProductListID);
			$this->xmlWriter->endElement();

			$this->writeEscapedElement("Desc", isc_substr($otherProductRef["accountingrefvalue"]["Name"], 0, 4000));
			$this->writeEscapedElement("Quantity", 1);
			$this->writeEscapedElement("Amount", number_format($otherProductTotal, 2, ".", ""));

			$this->xmlWriter->endElement();
		}

		return $this->buildOutput();
	}

	public function buildQueryXML()
	{
		/**
		 * Use the ListID if we can
		 */
		if (is_array($this->spoolReferenceData) && array_key_exists("TxnID", $this->spoolReferenceData)) {
			$this->writeEscapedElement("TxnID", $this->spoolReferenceData["TxnID"]);

		/**
		 * Else just use the order ID
		 */
		} else {
			$this->writeEscapedElement("RefNumber", $this->accounting->orderID2QBOrderRefNum($this->spool["nodeId"]));
		}

		return $this->buildOutput(true);
	}

	public function buildQueryDelXML()
	{
		if ($this->accounting->getValue("orderoption") == "order") {
			$this->writeEscapedElement("TxnDelType", "SalesOrder");
		} else {
			$this->writeEscapedElement("TxnDelType", "SalesReceipt");
		}

		return $this->buildOutput(true);
	}

	public function buildDelXML()
	{
		if ($this->accounting->getValue("orderoption") == "order") {
			$txnType = "SalesOrder";
		} else {
			$txnType = "SalesReceipt";
		}

		if (array_key_exists("ReferenceList", $this->spoolNodeData) && is_array($this->spoolNodeData["ReferenceList"])) {
			foreach ($this->spoolNodeData["ReferenceList"] as $reference) {

				$this->xmlWriter->startElement("TxnDelRq");
				$this->writeEscapedElement("TxnDelType", $txnType);
				$this->writeEscapedElement("TxnID", $reference["accountingrefexternalid"]);
				$this->xmlWriter->endElement();
			}
		}

		return $this->buildOutputEmpty();
	}

	public function buildQuerySyncXML()
	{
		if (!array_key_exists("modifiedDate", $this->spoolNodeData)) {
			$this->spoolNodeData["modifiedDate"] = time();
		}

		/**
		 * Add an hour if we are in daylight savings
		 */
		if ($GLOBALS['ISC_CFG']['StoreDSTCorrection'] == "1") {
			$this->spoolNodeData["modifiedDate"] += 3600;
		}

		$fromModifiedDate = isc_date_tz($this->spoolNodeData["modifiedDate"]);
		$this->xmlWriter->startElement("ModifiedDateRangeFilter");
		$this->writeEscapedElement("FromModifiedDate", $fromModifiedDate);
		$this->xmlWriter->endElement();

		$this->writeEscapedElement("IncludeLineItems", "true");

		return $this->buildOutput(true);
	}
}
