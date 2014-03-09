<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'entity.base.php');

class ISC_ENTITY_SHIPPING extends ISC_ENTITY_BASE
{
	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$schema = array(
				"shipid" => "int",
				"shipcustomerid" => "int",
				"shipfirstname" => "text",
				"shiplastname" => "text",
				"shipcompany" => "text",
				"shipaddress1" => "text",
				"shipaddress2" => "text",
				"shipcity" => "text",
				"shipstate" => "text",
				"shipzip" => "text",
				"shipcountry" => "text",
				"shipphone" => "text",
				"shipstateid" => "int",
				"shipcountryid" => "int",
				"shipdestination" => "text",
				"shiplastused" => "int",
				"shipformsessionid" => "int",
		);

		$tableName = "shipping_addresses";
		$primaryKeyName = "shipid";
		$searchFields = array(
				"shipid",
				"shipcustomerid",
				"shipfirstname",
				"shiplastname",
				"shipaddress1",
				"shipzip"
		);

		$customKeyName = "shipformsessionid";

		parent::__construct($schema, $tableName, $primaryKeyName, $searchFields, $customKeyName);
	}

	protected function addPrehook(&$savedata, $rawInput, $calledByEdit=false)
	{
		if (!array_key_exists("shipdestination", $rawInput)) {
			$rawInput["shipdestination"] = "residential";
		}

		if (isc_strtolower($rawInput["shipdestination"]) == "residential" || isc_strtolower($rawInput["shipdestination"]) == "commercial") {
			$savedata["shipdestination"] = isc_strtolower($rawInput["shipdestination"]);
		} else {
			$savedata["shipdestination"] = "residential";
		}

		if (!$calledByEdit) {
			$savedata["shiplastused"] = time();
		}

		return true;
	}

	protected function editPrehook($shipId, &$savedata, $rawInput)
	{
		return $this->addPrehook($savedata, $rawInput, true);
	}

	/**
	 * Delete multiple addresses by customer ID(s)
	 *
	 * Method will delete all addresses based on the int/array of customer IDs
	 *
	 * @access public
	 * @param mixed $customerIdx The customer ID / array of customer IDs
	 * @return bool TRUE if address(es) were cussfully deleted, FALSE on error
	 */
	public function deleteByCustomer($customerIdx)
	{
		if (!is_array($customerIdx)) {
			$customerIdx = array($customerIdx);
		}

		$customerIdx = array_filter($customerIdx, "isId");

		if (!is_array($customerIdx) || empty($customerIdx)) {
			return false;
		}

		$shipIdx = array();
		$query = "SELECT shipid
					FROM [|PREFIX|]shipping_addresses
					WHERE shipcustomerid IN(" . implode(",", $customerIdx) . ")";

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$shipIdx[] = $row["shipid"];
		}

		return self::multiDelete($shipIdx);
	}

	/**
	 * Search for a matching address
	 *
	 * Method will do a predefined search for a matching address
	 *
	 * @access public
	 * @param array $address The address details array
	 * @return int The matching address ID if found, FALSE if no match
	 */
	public function basicSearch($address)
	{
		if (!is_array($address)) {
			return false;
		}

		/**
		 * Clean our address array
		 */
		$address = Interspire_Array::clean($address);

		if (!is_array($address) || !isset($address["shipcustomerid"]) || !isset($address["shipfirstname"]) || !isset($address["shiplastname"]) || !isset($address["shipaddress1"])) {
			return false;
		}

		$searchFields = array();
		$searchFields["shipcustomerid"] = $address["shipcustomerid"];
		$searchFields["shipfirstname"] = array(
												"value" => $address["shipfirstname"],
												"func" => "LOWER"
										);

		$searchFields["shiplastname"] = array(
												"value" => $address["shiplastname"],
												"func" => "LOWER"
										);

		$searchFields["shipaddress1"] = array(
												"value" => $address["shipaddress1"],
												"func" => "LOWER"
										);

		$formSessionId = 0;
		if (isset($address["shipformsessionid"])) {
			$formSessionId = $address["shipformsessionid"];
		}

		return parent::search($searchFields, array(), array(), $formSessionId);
	}
}
