<?php

/**
 * Upgrade class for 6.0.13
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6013 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'add_secondary_category_options_to_ebay_listing_template_table',
		'addEmailProviderListFieldsSettingsColumn',
		'modifyTasksClassColumnLength',
		'modifyTaskStatusClassColumnLength',
		'modifyPicniktokensImageidColumnLength',
		'deleteExcessiveProductSessionViews',
	);

	public function add_secondary_category_options_to_ebay_listing_template_table()
	{
		// add attempt_lockout timestamp col to users table
		if ($this->ColumnExists('[|PREFIX|]ebay_listing_template', 'secondary_category_options') == false) {
			$query = "ALTER TABLE `[|PREFIX|]ebay_listing_template` ADD COLUMN `secondary_category_options` TEXT NOT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function addEmailProviderListFieldsSettingsColumn ()
	{
		if (!$this->ColumnExists('[|PREFIX|]email_provider_list_fields', 'settings')) {
			$query = "ALTER TABLE [|PREFIX|]email_provider_list_fields ADD COLUMN `settings` TEXT NOT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function modifyTasksClassColumnLength ()
	{
		if (strtolower($this->getColumnType('tasks', 'class')) == 'varchar(256)') {
			// the alter will fail under strict mode if column contains content > new width, pre-truncate first
			$query = "UPDATE `[|PREFIX|]tasks` SET `class` = LEFT(`class`, 255) WHERE LENGTH(`class`) > 255";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
			$query = "ALTER TABLE `[|PREFIX|]tasks` MODIFY COLUMN `class` VARCHAR(255) NOT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function modifyTaskStatusClassColumnLength ()
	{
		if (strtolower($this->getColumnType('task_status', 'class')) == 'varchar(256)') {
			// the alter will fail under strict mode if column contains content > new width, pre-truncate first
			$query = "UPDATE `[|PREFIX|]task_status` SET `class` = LEFT(`class`, 255) WHERE LENGTH(`class`) > 255";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
			$query = "ALTER TABLE `[|PREFIX|]task_status` MODIFY COLUMN `class` VARCHAR(255) NOT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function modifyPicniktokensImageidColumnLength ()
	{
		if (strtolower($this->getColumnType('picniktokens', 'imageid')) == 'varchar(256)') {
			// the alter will fail under strict mode if column contains content > new width, pre-truncate first
			$query = "UPDATE `[|PREFIX|]picniktokens` SET `imageid` = LEFT(`imageid`, 255) WHERE LENGTH(`imageid`) > 255";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
			$query = "ALTER TABLE `[|PREFIX|]picniktokens` MODIFY COLUMN `imageid` VARCHAR(255) NOT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function deleteExcessiveProductSessionViews ()
	{
		// snapshot of the current max views setting as of 6013
		$maxViews = 50;

		// find sessions with > maxViews product views and delete them
		$query = "SELECT `session` FROM [|PREFIX|]product_views GROUP BY `session` HAVING COUNT(product) > " . $maxViews;
		$result = $this->db->Query($query);
		if (!$result) {
			$this->SetError($this->db->GetErrorMsg());
			return false;
		}

		while ($row = $this->db->Fetch($result)) {
			$query = "DELETE FROM [|PREFIX|]product_views WHERE `session` = '" . $this->db->Quote($row['session']) . "'";
			if (!$this->db->Query($query)) {
				$this->SetError($this->db->GetErrorMsg());
				return false;
			}
		}

		return true;
	}
}
