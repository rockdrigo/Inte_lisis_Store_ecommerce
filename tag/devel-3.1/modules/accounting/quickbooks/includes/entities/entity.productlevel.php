<?php

class ACCOUNTING_QUICKBOOKS_ENTITY_PRODUCTLEVEL extends ACCOUNTING_QUICKBOOKS_ENTITY_BASE
{
	public function buildXML()
	{
		$incomeAccountListId = $this->accounting->getAccountListId("income");

		if (trim($incomeAccountListId) == '') {
			throw new QBException("Cannot find the income account ListID for the product level service", $this->spool);
		}

		$this->xmlWriter->startElement("AccountRef");
		$this->writeEscapedElement("ListID", $incomeAccountListId);
		$this->xmlWriter->endElement();

		if (!array_key_exists("Products", $this->spoolNodeData) || !is_array($this->spoolNodeData["Products"]) || empty($this->spoolNodeData["Products"])) {
			throw new QBException("Missing/Invalid Products array for the product level service", $this->spool);
		}

		foreach ($this->spoolNodeData["Products"] as $product) {
			if (!isset($product["ListID"]) || !isset($product["NewQuantity"])) {
				continue;
			}

			$this->xmlWriter->startElement("InventoryAdjustmentLineAdd");

			$this->xmlWriter->startElement("ItemRef");
			$this->writeEscapedElement("ListID", $product["ListID"]);
			$this->xmlWriter->endElement();

			$this->xmlWriter->startElement("QuantityAdjustment");
			$this->writeEscapedElement("NewQuantity", $product["NewQuantity"]);
			$this->xmlWriter->endElement();

			$this->xmlWriter->endElement();
		}

		return $this->buildOutput();
	}

	public function buildQueryXML()
	{
		return $this->buildQuerySyncXML();
	}

	public function buildQuerySyncXML()
	{
		$this->writeEscapedElement("IncludeRetElement", "ListID");
		$this->writeEscapedElement("IncludeRetElement", "QuantityOnHand");

		return $this->buildOutput(true, "ItemInventoryQuery");
	}
}
