<?php
class ACCOUNTING_QUICKBOOKS_ENTITY_CUSTOMERGUEST extends ACCOUNTING_QUICKBOOKS_ENTITY_BASE
{
	public function buildXML()
	{
		if (isc_strtolower($this->spool["service"]) !== "add" && is_array($this->spoolReferenceData)) {
			$this->writeEscapedElement("ListID", $this->spoolReferenceData["ListID"]);
			$this->writeEscapedElement("EditSequence", $this->spoolReferenceData["EditSequence"]);
		}elseif(isc_strtolower($this->spool["service"]) !== "add"){
			$fullName = $this->spoolNodeData["FirstName"] . ' ' . $this->spoolNodeData["LastName"];
			$query = "SELECT * FROM [|PREFIX|]accountingref WHERE accountingreftype='customerguest' AND accountingrefvalue LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($fullName) . "%' ORDER BY accoutingrefid DESC LIMIT 1";
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$this->writeEscapedElement("ListID", @unserialize($row["ListID"]));
				$this->writeEscapedElement("EditSequence", @unserialize($row['EditSequence']));
			}
		}

		$this->buildCustomerGuestNameNode($name, $this->spoolNodeData["OrderID"]);

		$this->writeEscapedElement("Name", isc_substr($this->spoolNodeData["FirstName"] . ' ' . $this->spoolNodeData["LastName"], 0, 50));
		$this->writeEscapedElement("IsActive", "true");

		$customerTypeListId = $this->accounting->getCustomerParentTypeListId(true);

		if (!$customerTypeListId || trim($customerTypeListId) == '') {
				throw new QBException("Unable to find customer parent type reference for guest checkout in customerguest", $this->spool);
		}

		$this->xmlWriter->startElement("ParentRef");
		if (isc_strtolower($this->spool["service"]) == "add") {
			$this->writeEscapedElement("FullName", 'Cart Guest Checkout Customers');
		}else{
			$this->writeEscapedElement("ListID", $customerTypeListId);
		}
		$this->xmlWriter->endElement();


		/**
		 * Cannot be set if it is empty
		 */
		if ($this->spoolNodeData["FirstName"] !== '') {
			$this->writeEscapedElement("FirstName", isc_substr($this->spoolNodeData["FirstName"], 0, 25));
		}

		/**
		 * Same with this one
		 */
		if ($this->spoolNodeData["LastName"] !== '') {
			$this->writeEscapedElement("LastName", isc_substr($this->spoolNodeData["LastName"], 0, 25));
		}


		if (isset($this->spoolNodeData["ordbillphone"]) && $this->spoolNodeData["ordbillphone"] !== '') {
			$this->writeEscapedElement("Phone", $this->spoolNodeData["ordbillphone"]);
		} elseif (isset($this->spoolNodeData["Phone"]) && $this->spoolNodeData["Phone"] !== '') {
			$this->writeEscapedElement("Phone", $this->spoolNodeData["Phone"]);
		} else {
			$this->writeEscapedElement("Phone", '555-555-6666');
		}

		if (isset($this->spoolNodeData["ordbillemail"]) && $this->spoolNodeData["ordbillemail"] !== '') {
			$this->writeEscapedElement("Email", $this->spoolNodeData["ordbillemail"]);
		} elseif (isset($this->spoolNodeData["Email"]) && $this->spoolNodeData["Email"] !== '') {
			$this->writeEscapedElement("Email", $this->spoolNodeData["Email"]);
		} else {
			$this->writeEscapedElement("Email", 'guest@mystore.com');
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
			$this->buildCustomerGuestNameNode($name, $this->spoolNodeData["OrderID"], true);
		}

		return $this->buildOutput(true);
	}

	/**
	 * Write the customer guest name XML node
	 *
	 * Method will create the customer guest name XML name. Method will take care of all the encoding, truncating and
	 * adding the parent name if required
	 *
	 * @access private
	 * @param string $customerGuestName The customer guest name
	 * @param int $orderId The order ID
	 * @param bool $useFullName TRUE to use the full name, FALSE for just the customer guest name. Default is FALSE
	 * @return bool TRUE if the node was created, FASLE on error
	 */
	private function buildCustomerGuestNameNode($customerGuestName, $orderId, $useFullName=false)
	{
		if (trim($customerGuestName) == "" || !isId($orderId)) {
			return false;
		}

		$customerGuestName = str_replace(":", ";", $customerGuestName);
		$customerGuestName = $this->accounting->customerGuestNameId2QBCustomerGuestShortName($orderId, $customerGuestName);

		if ($useFullName) {

			$customerType = $this->accounting->getCustomerParentTypeListId(true, true);

			if (!is_array($customerType) || !isset($customerType["accountingrefvalue"]) || !isset($customerType["accountingrefvalue"]["Name"])) {
				throw new QBException("Unable to find customer parent type reference for guest checkout in customerguest", $this->spool);
			}

			$customerGuestName = $customerType["accountingrefvalue"]["Name"] . ":" . $customerGuestName;
			$nodeName = "FullName";
		} else {
			$nodeName = "Name";
		}

		return $this->writeRawElement($nodeName, $customerGuestName);
	}
}
