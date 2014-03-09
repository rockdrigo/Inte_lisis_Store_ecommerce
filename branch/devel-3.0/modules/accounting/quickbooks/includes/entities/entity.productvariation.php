<?php
class ACCOUNTING_QUICKBOOKS_ENTITY_PRODUCTVARIATION extends ACCOUNTING_QUICKBOOKS_ENTITY_BASE
{
	public function buildXML()
	{
		if (isc_strtolower($this->spool["service"]) == "edit" && is_array($this->spoolReferenceData)) {
			$this->writeEscapedElement("ListID", $this->spoolReferenceData["ListID"]);
			$this->writeEscapedElement("EditSequence", $this->spoolReferenceData["EditSequence"]);
		}

		$this->buildProductVariationNameNode($this->spoolNodeData["prodname"], $this->spoolNodeData["combinationid"]);
		$this->writeEscapedElement("IsActive", "true");

		/**
		 * Set the product variation parent. Only do this for adding (want to edit the least amount as possible)
		 */
		if (isc_strtolower($this->spool["service"]) == "add") {
			$productTypeListId = $this->accounting->getProductParentTypeListId(true);

			if (!$productTypeListId || trim($productTypeListId) == '') {
				throw new QBException("Unable to find product parent type reference for product variation in productvariation", $this->spool);
			}

			$this->xmlWriter->startElement("ParentRef");
			$this->writeEscapedElement("ListID", $productTypeListId);
			$this->xmlWriter->endElement();
		}

		if ($this->compareClientVersion("7.0") && isset($this->spoolNodeData["vcsku"]) && $this->spoolNodeData["vcsku"] !== "") {
			$this->writeEscapedElement("ManufacturerPartNumber", $this->spoolNodeData["vcsku"]);
		}

		/**
		 * OK, different tag names for different versions for different countries. Good times, good times
		 */
		if ($this->compareClientCountry("uk") || $this->compareClientCountry("ca")) {
			if ($this->compareClientVersion("3.0")) {
				$this->xmlWriter->startElement("TaxCodeForSaleRef");
			} else {
				$this->xmlWriter->startElement("TaxCodeRef");
			}
		} else {
			$this->xmlWriter->startElement("SalesTaxCodeRef");
		}

		$this->writeEscapedElement("FullName", "NON");
		$this->xmlWriter->endElement();

		$this->writeEscapedElement("SalesDesc", isc_substr($this->spoolNodeData["prodvariationname"], 0, 4095));
		$prodPrice = CalcProductVariationPrice($this->spoolNodeData["prodprice"], $this->spoolNodeData["vcpricediff"], $this->spoolNodeData["vcprice"]);
		$this->writeEscapedElement("SalesPrice", number_format($prodPrice, 2, ".", ""));

		/**
		 * We can only set this for the add process as the mod process is only available in versions 7.0 and above
		 */
		if (isc_strtolower($this->spool["service"]) == "add" || $this->compareClientVersion("7.0")) {

			$incomeAccountListId = $this->accounting->getAccountListId("income");

			if (trim($incomeAccountListId) == '') {
				throw new QBException("Cannot find the income account ListID for product variation ID: " . $this->spoolNodeData["combinationid"], $this->spool);
			}

			$this->xmlWriter->startElement("IncomeAccountRef");
			$this->writeEscapedElement("ListID", $incomeAccountListId);
			$this->xmlWriter->endElement();
		}

		if (isset($this->spoolNodeData["prodcostprice"]) && $this->spoolNodeData["prodcostprice"] > 0) {
			$this->writeEscapedElement("PurchaseDesc", isc_substr($this->spoolNodeData["prodvariationname"], 0, 4095));
			$this->writeEscapedElement("PurchaseCost", number_format($this->spoolNodeData["prodcostprice"], 2, ".", ""));
		}

		$cogsAccountListId = $this->accounting->getAccountListId("costofgoodssold");

		if (trim($cogsAccountListId) == '') {
			throw new QBException("Cannot find the cogs account ListID for product variation ID: " . $this->spoolNodeData["combinationid"], $this->spool);
		}

		$this->xmlWriter->startElement("COGSAccountRef");
		$this->writeEscapedElement("ListID", $cogsAccountListId);
		$this->xmlWriter->endElement();

		$fixedAccountListId = $this->accounting->getAccountListId("fixedasset");

		if (trim($fixedAccountListId) == '') {
			throw new QBException("Cannot find the fixed account ListID for product ID: " . $this->spoolNodeData["combinationid"], $this->spool);
		}

		$this->xmlWriter->startElement("AssetAccountRef");
		$this->writeEscapedElement("ListID", $fixedAccountListId);
		$this->xmlWriter->endElement();

		/**
		 * Only do this is we are a new product OR if we are handling the inventory levels
		 */
		if (isc_strtolower($this->spool["service"]) == "add" || $this->accounting->getValue("invlevels") == ACCOUNTING_QUICKBOOKS_TYPE_SHOPPINGCART) {
			$this->writeEscapedElement("ReorderPoint", (int)$this->spoolNodeData["vclowstock"]);
		}

		if ($this->compareClientCountry("uk") && $this->compareClientVersion("2.0")) {
			if (GetConfig("PricesIncludeTax")) {
				$this->writeEscapedElement("AmountIncludesVAT", "1");
			} else {
				$this->writeEscapedElement("AmountIncludesVAT", "0");
			}
		}

		return $this->buildOutput();
	}

	public function buildQueryXML()
	{
		/**
		 * Use the ListID if we can
		 */
		if (is_array($this->spoolReferenceData) && array_key_exists("ListID", $this->spoolReferenceData)) {
			$this->writeEscapedElement("ListID", $this->spoolReferenceData["ListID"]);
		} else if (is_array($this->spoolNodeData) && array_key_exists("ListID", $this->spoolNodeData)) {
			$this->writeEscapedElement("ListID", $this->spoolNodeData["ListID"]);

		/**
		 * Else just use the product name
		 */
		} else {
			$this->buildProductVariationNameNode($this->spoolNodeData["prodname"], $this->spoolNodeData["combinationid"], true);
		}

		return $this->buildOutput(true);
	}

	public function buildQueryDelXML()
	{
		$this->writeEscapedElement("ListDelType", "ItemInventory");

		return $this->buildOutput(true);
	}

	public function buildQueryInactiveXML()
	{
		$this->writeEscapedElement("ActiveStatus", "InactiveOnly");
		$this->writeEscapedElement("IncludeRetElement", "ListID");

		return $this->buildOutput(true);
	}

	public function buildDelXML()
	{
		if (array_key_exists("ReferenceList", $this->spoolNodeData) && is_array($this->spoolNodeData["ReferenceList"])) {

			foreach ($this->spoolNodeData["ReferenceList"] as $reference) {

				/**
				 * This is for deleting them on QB
				 */

				$this->xmlWriter->startElement("ListDelRq");
				$this->writeEscapedElement("ListDelType", "ItemInventory");
				$this->writeEscapedElement("ListID", $reference["accountingrefexternalid"]);
				$this->xmlWriter->endElement();

				/**
				 * And this is to set them as inactive incase deleteing them failed (its a fallback pretty much)
				 */

				if (!is_array($reference["accountingrefvalue"]) || !array_key_exists("EditSequence", $reference["accountingrefvalue"])) {
					continue;
				}

				$this->xmlWriter->startElement("ItemInventoryModRq");
				$this->xmlWriter->startElement("ItemInventoryMod");
				$this->writeEscapedElement("ListID", $reference["accountingrefexternalid"]);
				$this->writeEscapedElement("EditSequence", $reference["accountingrefvalue"]["EditSequence"]);
				$this->writeEscapedElement("IsActive", "false");
				$this->xmlWriter->endElement();
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
		$this->writeEscapedElement("ActiveStatus", "ActiveOnly");
		$this->writeEscapedElement("FromModifiedDate", $fromModifiedDate);

		$this->xmlWriter->startElement("NameFilter");
		$this->writeEscapedElement("MatchCriterion", "EndsWith");
		$this->writeEscapedElement("Name", $this->accounting->getProductVariationNamePostFix());
		$this->xmlWriter->endElement();

		return $this->buildOutput(true);
	}

	/**
	 * Write the product variation name XML node
	 *
	 * Method will create the product variation name XML name. Method will take care of all the encoding, truncating and
	 * adding the parent name if required
	 *
	 * @access private
	 * @param string $productName The product variation name
	 * @param int $productId The product variation combination ID
	 * @param bool $useFullName TRUE to use the full name, FALSE for just the product variation name. Default is FALSE
	 * @return bool TRUE if the node was created, FASLE on error
	 */
	private function buildProductVariationNameNode($productName, $productId, $useFullName=false)
	{
		if (trim($productName) == "" || !isId($productId)) {
			return false;
		}

		$productName = str_replace(":", ";", $productName);
		$productName = $this->accounting->productVariationNameId2QBProductVariationShortName($productId, $productName);

		if ($useFullName) {

			$productType = $this->accounting->getProductParentTypeListId(true, true);

			if (!is_array($productType) || !isset($productType["accountingrefvalue"]) || !isset($productType["accountingrefvalue"]["Name"])) {
				throw new QBException("Unable to find product parent type reference for product variation in productvariation", $this->spool);
			}

			$productName = $productType["accountingrefvalue"]["Name"] . ":" . $productName;
			$nodeName = "FullName";
		} else {
			$nodeName = "Name";
		}

		return $this->writeRawElement($nodeName, $productName);
	}
}
