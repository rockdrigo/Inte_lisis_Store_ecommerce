<?php

class ACCOUNTING_QUICKBOOKS_ENTITY_PREREQUISITE extends ACCOUNTING_QUICKBOOKS_ENTITY_BASE
{
	public function buildXML()
	{
		if (isc_strtolower($this->spool["service"]) == "edit" && is_array($this->spoolReferenceData)) {
			$this->writeEscapedElement("ListID", $this->spoolReferenceData["ListID"]);
			$this->writeEscapedElement("EditSequence", $this->spoolReferenceData["EditSequence"]);
		}

		$this->writeEscapedElement("Name", $this->spoolNodeData["Name"]);
		$this->writeEscapedElement("IsActive", "true");

		if (!array_key_exists("Service", $this->spoolNodeData) || trim($this->spoolNodeData["Service"]) == "") {
			throw new QBException("Unable to find the 'Service' key in the prerequisite service", $this->spoolNodeData);
		}

		switch (isc_strtolower($this->spoolNodeData["Service"])) {
			case "account":
				$this->writeEscapedElement("AccountType", $this->spoolNodeData["AccountType"]);
				$this->writeEscapedElement("Desc", $this->spoolNodeData["Desc"]);
				break;

			case "itemothercharge":
				$assetAccountListId = $this->accounting->getAccountListId("fixedasset");

				if (trim($assetAccountListId) == "") {
					throw new QBException("Unable to find the Fixed Asset Account ListID when adding " . $this->spoolNodeData["Service"] . " in the prerequisite service", $this->spool);
				}

				if (isc_strtolower($this->spool["service"]) == "edit") {
					$this->xmlWriter->startElement("SalesOrPurchaseMod");
				} else {
					$this->xmlWriter->startElement("SalesOrPurchase");
				}

				$this->writeEscapedElement("Desc", $this->spoolNodeData["Desc"]);
				$this->xmlWriter->startElement("AccountRef");
				$this->writeEscapedElement("ListID", $assetAccountListId);
				$this->xmlWriter->endElement();
				$this->xmlWriter->endElement();
				break;

			case "customer":
				if ($this->compareClientVersion("3.0")) {
					$this->writeEscapedElement("Notes", $this->spoolNodeData["Desc"]);
				}

				break;

			case "iteminventory":

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

				$this->writeEscapedElement("SalesDesc", $this->spoolNodeData["Desc"]);

				/**
				 * We can only set this for the add process as the mod process is only available in versions 7.0 and above
				 */
				if (isc_strtolower($this->spool["service"]) == "add" || $this->compareClientVersion("7.0")) {

					$incomeAccountListId = $this->accounting->getAccountListId("income");

					if (trim($incomeAccountListId) == '') {
						throw new QBException("Unable to find the Income Asset Account ListID when adding " . $this->spoolNodeData["Service"] . " in the prerequisite service", $this->spool);
					}

					$this->xmlWriter->startElement("IncomeAccountRef");
					$this->writeEscapedElement("ListID", $incomeAccountListId);
					$this->xmlWriter->endElement();
				}

				$cogsAccountListId = $this->accounting->getAccountListId("costofgoodssold");

				if (trim($cogsAccountListId) == '') {
					throw new QBException("Unable to find the COGS Asset Account ListID when adding " . $this->spoolNodeData["Service"] . " in the prerequisite service", $this->spool);
				}

				$this->xmlWriter->startElement("COGSAccountRef");
				$this->writeEscapedElement("ListID", $cogsAccountListId);
				$this->xmlWriter->endElement();

				$fixedAccountListId = $this->accounting->getAccountListId("fixedasset");

				if (trim($fixedAccountListId) == '') {
					throw new QBException("Unable to find the Fixed Asset Account ListID when adding " . $this->spoolNodeData["Service"] . " in the prerequisite service", $this->spool);
				}

				$this->xmlWriter->startElement("AssetAccountRef");
				$this->writeEscapedElement("ListID", $fixedAccountListId);
				$this->xmlWriter->endElement();
				break;
		}

		return $this->buildOutput();
	}

	public function buildQueryXML()
	{
		if (is_array($this->spoolReferenceData) && array_key_exists("ListID", $this->spoolReferenceData)) {
			$this->writeEscapedElement("ListID", $this->spoolReferenceData["ListID"]);
		} else {
			$this->writeEscapedElement("FullName", $this->spoolNodeData["Name"]);
		}

		return $this->buildOutput(true);
	}
}
