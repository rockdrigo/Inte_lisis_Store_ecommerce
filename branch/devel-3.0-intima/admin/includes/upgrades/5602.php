<?php
/**
 * Upgrade class for 5.6.2
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */

class ISC_ADMIN_UPGRADE_5602 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'dropSessionsSessionId',
		'changeSessionsSessionHashUnique',
		'fixProductsInventoryTracking'
	);

	public function dropSessionsSessionId()
	{
		if(!$this->columnExists('[|PREFIX|]sessions', 'sessionid')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]sessions
			DROP sessionid
		";
		return (bool)$GLOBALS['ISC_CLASS_DB']->query($query);
	}

	public function changeSessionsSessionHashUnique()
	{
		if($this->indexExists('[|PREFIX|]sessions', 'sessionhash')) {
			$query = "
				ALTER TABLE [|PREFIX|]sessions
				DROP INDEX sessionhash
			";
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				return false;
			}
		}

		$query = "
			ALTER IGNORE TABLE [|PREFIX|]sessions
			ADD UNIQUE INDEX (sessionhash)
		";
		return (bool)$GLOBALS['ISC_CLASS_DB']->query($query);
	}

	public function fixProductsInventoryTracking()
	{
		// Clear prodinvtracking and inventory levels for non
		// physical products
		$query = "
			UPDATE [|PREFIX|]products
			SET prodinvtrack = 0, prodcurrentinv = 0, prodlowinv = 0
			WHERE prodtype != 1";

		return (bool)$GLOBALS['ISC_CLASS_DB']->query($query);
	}
}