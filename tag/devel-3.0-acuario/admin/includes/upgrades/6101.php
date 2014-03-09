<?php

/**
 * Upgrade class for 6.1.1
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6101 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'removeEbayJobs',
		'removeShoppingComparisonJobs',
		'updateEbayNotificationURL',
	);

	/**
	* Remove any current eBay jobs.
	* Due to broken task manager in 6.1, we don't want to start listing products on eBay from 'stuck' exports once the task manager resumes in 6.1.1
	*
	*/
	public function removeEbayJobs()
	{
		if (!$this->db->DeleteQuery('tasks', "WHERE queue = 'ebay'")) {
			$this->SetError($this->db->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function removeShoppingComparisonJobs()
	{
		if (!$this->db->DeleteQuery('tasks', "WHERE queue = 'shoppingcomparison'")) {
			$this->SetError($this->db->GetErrorMsg());
			return false;
		}

		return true;
	}

	/**
	* Update the eBay notification URL to hopefully fix pending item issue
	*
	*/
	public function updateEbayNotificationURL()
	{
		// is ebay enabled/configured?
		if (ISC_ADMIN_EBAY::checkEbayConfig()) {
			// lets ensure our notification URL is correct
			ISC_ADMIN_EBAY::resubscribeNotifications();
		}

		return true;
	}
}
