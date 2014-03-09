<?php
class ISC_ADMIN_UPGRADE_3510 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		"add_wishlist_token",
		'add_order_products_index'
	);

	public function pre_upgrade_checks()
	{
		if(is_dir(ISC_BASE_PATH."/modules/shipping/percountry")) {
			$this->SetError('Please delete the /modules/shipping/percountry/ directory from your store. This module has been replaced by shipping zones and is no longer required.');
		}
	}

	public function add_wishlist_token()
	{

		if(!$this->ColumnExists('[|PREFIX|]wishlists', 'wishlisttoken')) {
			$query = "ALTER TABLE `[|PREFIX|]wishlists` ADD `wishlisttoken` VARCHAR( 255 ) NOT NULL DEFAULT ''";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$query = "SELECT * FROM [|PREFIX|]wishlists";
			if(!$wishlists = $GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			while ($wishlist = $GLOBALS['ISC_CLASS_DB']->Fetch($wishlists)) {

				$query = "UPDATE [|PREFIX|]wishlists SET wishlisttoken='".md5(uniqid())."' WHERE wishlistid='".$wishlist['wishlistid']."'";
				if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}
		return true;
	}

	public function add_order_products_index()
	{
		if(!$this->IndexExists('[|PREFIX|]order_products', 'i_order_products_ordprodid')) {
			$query = "ALTER TABLE `[|PREFIX|]order_products` ADD INDEX i_order_products_ordprodid ( `orderorderid` , `ordprodid` );";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}
}