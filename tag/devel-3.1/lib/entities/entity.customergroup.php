<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'entity.base.php');

class ISC_ENTITY_CUSTOMERGROUP extends ISC_ENTITY_BASE
{
	public function __construct()
	{
		$schema = array(
				"customergroupid" => "int",
				"groupname" => "text",
				"discount" => "price",
				"discountmethod" => "text",
				"isdefault" => "bool",
				"categoryaccesstype" => "text",
		);

		$tableName = "customer_groups";
		$primaryKeyName = "customergroupid";
		$searchFields = array(
				'customergroupid',
				'groupname'
		);

		$customKeyName = "";

		parent::__construct($schema, $tableName, $primaryKeyName, $searchFields, $customKeyName);
	}

	protected function parsecategoryaccess($value)
	{
		if (isc_strtolower($value) == "none" || isc_strtolower($value) == "all" || isc_strtolower($value) == "specific") {
			return isc_strtolower($value);
		}

		return "none";
	}

	protected function addPosthook($groupId, $saveData, $rawInput)
	{
		if (array_key_exists("accesscategories", $rawInput) && is_array($rawInput["accesscategories"])) {
			$this->addAccessCategories($groupId, $rawInput["accesscategories"]);
		}

		/**
		 * Update other records if it's the default group
		 */
		if (array_key_exists("isdefault", $rawInput) && (string)$rawInput["isdefault"] == "1") {

			$savedata = array(
				"isdefault" => 0
			);

			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("customer_groups", $savedata, "customergroupid != " . (int)$groupId);
		}

		return true;
	}

	protected function editPosthook($groupId, $saveData, $rawInput)
	{
		return $this->addPosthook($groupId, $saveData, $rawInput);
	}

	protected function deletePosthook($groupId)
	{
		$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("customer_group_categories", "WHERE customergroupid = " . (int)$groupId);
		return true;
	}

	private function addAccessCategories($groupId, $categories)
	{
		$categories = @array_filter($categories, "isId");

		if (!isId($groupId) || !is_array($categories) || empty($categories)) {
			return false;
		}

		$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("customer_group_categories", "WHERE customergroupid = " . (int)$groupId);

		foreach ($categories as $category) {
			$insert = array(
				"customergroupid" => $groupId,
				"categoryid" => $category
			);

			$GLOBALS["ISC_CLASS_DB"]->InsertQuery("customer_group_categories", $insert);
		}

		return true;
	}
}
