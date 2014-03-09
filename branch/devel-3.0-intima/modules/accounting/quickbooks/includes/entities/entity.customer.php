<?php

class ACCOUNTING_QUICKBOOKS_ENTITY_CUSTOMER extends ACCOUNTING_QUICKBOOKS_ENTITY_BASE
{
	public function buildXML()
	{
		if (isc_strtolower($this->spool["service"]) == "edit" && is_array($this->spoolReferenceData)) {
			$this->writeEscapedElement("ListID", $this->spoolReferenceData["ListID"]);
			$this->writeEscapedElement("EditSequence", $this->spoolReferenceData["EditSequence"]);
		}

		/**
		 * If we have no name then use the email address as that is required through-out the site
		 */
		$name = trim($this->spoolNodeData["custconfirstname"] . " " . $this->spoolNodeData["custconlastname"]);
		if ($name == "") {
			$name = $this->spoolNodeData["custconemail"];
		}

		$this->buildCustomerNameNode($name, $this->spoolNodeData["customerid"]);
		$this->writeEscapedElement("IsActive", "true");

		/**
		 * Set the normal customer parent
		 */
		$customerTypeListId = $this->accounting->getCustomerParentTypeListId(false);

		if (!$customerTypeListId || trim($customerTypeListId) == '') {
			throw new QBException("Unable to find customer parent type reference for normal customer in customer", $this->spool);
		}

		$this->xmlWriter->startElement("ParentRef");
		$this->writeEscapedElement("ListID", $customerTypeListId);
		$this->xmlWriter->endElement();

		$this->writeEscapedElement("CompanyName", isc_substr($this->spoolNodeData["custconcompany"], 0, 41));

		/**
		 * Cannot be set if it is empty
		 */
		if ($this->spoolNodeData["custconfirstname"] !== '') {
			$this->writeEscapedElement("FirstName", isc_substr($this->spoolNodeData["custconfirstname"], 0, 25));
		}

		/**
		 * Same with this one
		 */
		if ($this->spoolNodeData["custconlastname"] !== '') {
			$this->writeEscapedElement("LastName", isc_substr($this->spoolNodeData["custconlastname"], 0, 25));
		}

		/**
		 * Addresses
		 */
		if (isset($this->spoolNodeData["addresses"]) && is_array($this->spoolNodeData["addresses"])) {

			/**
			 * If we have only one address then replicate it for the shipping address
			 */
			if (count($this->spoolNodeData["addresses"]) == 1) {
				$this->spoolNodeData["addresses"][] = $this->spoolNodeData["addresses"][count($this->spoolNodeData["addresses"])-1];
			}

			/**
			 * Make sure we only have 2 addresses
			 */
			if (count($this->spoolNodeData["addresses"]) > 2) {
				$this->spoolNodeData["addresses"] = array_slice($this->spoolNodeData["addresses"], 0, 2);
			}

			$addresses = array_values($this->spoolNodeData["addresses"]);
			$tagName = "";

			foreach ($addresses as $address) {
				if ($tagName == "") {
					$tagName = "BillAddress";
				} else {
					$tagName = "ShipAddress";
				}

				$this->buildAddressBlock($tagName, $address);
			}
		}

		$this->writeEscapedElement("Phone", $this->spoolNodeData["custconphone"]);
		$this->writeEscapedElement("Email", $this->spoolNodeData["custconemail"]);

		/**
		 * Add in the customer group reference if we can
		 */
		if ($this->compareClientVersion("4.0") && isset($this->spoolNodeData["custgroupid"]) && isId($this->spoolNodeData["custgroupid"])) {
			$reference = $this->accounting->getReference("customergroup", '', '', $this->spoolNodeData["custgroupid"]);

			if ($reference) {
				$this->xmlWriter->startElement("PriceLevelRef");
				$this->writeEscapedElement("ListID", $reference["ListID"]);
				$this->xmlWriter->endElement();
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
		 * Else just use the customer name
		 */
		} else {

			/**
			 * If we have no name the use the email address as that is required through-out the site
			 */
			$name = trim($this->spoolNodeData["custconfirstname"] . " " . $this->spoolNodeData["custconlastname"]);
			if ($name == "") {
				$name = $this->spoolNodeData["custconemail"];
			}

			$this->buildCustomerNameNode($name, $this->spoolNodeData["customerid"], true);
		}

		return $this->buildOutput(true);
	}

	public function buildQueryDelXML()
	{
		$this->writeEscapedElement("ListDelType", "Customer");

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
				$this->writeEscapedElement("ListDelType", "Customer");
				$this->writeEscapedElement("ListID", $reference["accountingrefexternalid"]);
				$this->xmlWriter->endElement();

				/**
				 * And this is to set them as inactive incase deleteing them failed (its a fallback pretty much)
				 */

				if (!is_array($reference["accountingrefvalue"]) || !array_key_exists("EditSequence", $reference["accountingrefvalue"])) {
					continue;
				}

				$this->xmlWriter->startElement("CustomerModRq");
				$this->xmlWriter->startElement("CustomerMod");
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
		return $this->buildOutput(true);
	}

	/**
	 * Write the customer name XML node
	 *
	 * Method will create the customer name XML name. Method will take care of all the encoding, truncating and
	 * adding the parent name if required
	 *
	 * @access private
	 * @param string $customerName The customer name
	 * @param int $customerId The customer ID
	 * @param bool $useFullName TRUE to use the full name, FALSE for just the customer name. Default is FALSE
	 * @return bool TRUE if the node was created, FASLE on error
	 */
	private function buildCustomerNameNode($customerName, $customerId, $useFullName=false)
	{
		if (trim($customerName) == "" || !isId($customerId)) {
			return false;
		}

		$customerName = str_replace(":", ";", $customerName);
		$customerName = $this->accounting->customerNameId2QBCustomerShortName($customerId, $customerName);

		if ($useFullName) {

			$customerType = $this->accounting->getCustomerParentTypeListId(false, true);

			if (!is_array($customerType) || !isset($customerType["accountingrefvalue"]) || !isset($customerType["accountingrefvalue"]["Name"])) {
				throw new QBException("Unable to find product parent type reference for normal customer in customer", $this->spool);
			}

			$customerName = $customerType["accountingrefvalue"]["Name"] . ":" . $customerName;
			$nodeName = "FullName";
		} else {
			$nodeName = "Name";
		}

		return $this->writeRawElement($nodeName, $customerName);
	}
}
