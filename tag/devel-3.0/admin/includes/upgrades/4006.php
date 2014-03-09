<?php
class ISC_ADMIN_UPGRADE_4006 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'remove_uk_counties'
	);

	public function remove_uk_counties()
	{
		// remove the counties
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('country_states', "WHERE statecountry = 225");

		// update addresses
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('shipping_addresses', array("shipstateid" => 0), "shipcountryid = 225");

		// update orders
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', array("ordbillstateid" => 0), "ordbillcountryid = 225");
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', array("ordshipstateid" => 0), "ordshipcountryid = 225");

		return true;
	}
}