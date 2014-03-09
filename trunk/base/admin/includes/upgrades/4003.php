<?php
class ISC_ADMIN_UPGRADE_4003 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'upgrade_customer_group_categories',
		'add_order_coupon_type',
		'add_order_original_cost',
		'update_order_products'
	);

	public function upgrade_customer_group_categories()
	{
		if (!$this->TableExists('customer_group_categories')) {
			$query = "
				CREATE TABLE IF NOT EXISTS `[|PREFIX|]customer_group_categories` (
					`customergroupid` INT( 11 ) NOT NULL ,
					`categoryid` INT( 11 ) NOT NULL ,
					PRIMARY KEY ( `customergroupid` , `categoryid` )
				);
			";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]customer_groups', 'categoryaccesstype')) {
			$query = "ALTER TABLE `[|PREFIX|]customer_groups` ADD `categoryaccesstype` ENUM( 'none', 'all', 'specific' ) NOT NULL ;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if($this->ColumnExists('[|PREFIX|]customer_groups', 'accesscategories')) {
			$query = "SELECT * FROM [|PREFIX|]customer_groups";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				// extract individual categories
				$categories = explode(",", $row['accesscategories']);

				// check the access type if only one entry in the list
				if (count($categories) == 1) {
					$category = $categories[0];

					// no categories
					if ($category <= 0) {
						if ($category == 0) {
							$update = array(
								"categoryaccesstype" => "none"
							);
						}
						// all categories
						elseif ($category == -1) {
							$update = array(
								"categoryaccesstype" => "all"
							);
						}

						// set the access type and continue to next group
						if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('customer_groups', $update, "customergroupid = " . $row['customergroupid'])) {
							$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
							return false;
						}

						continue;
					}
				}

				$update = array(
					"categoryaccesstype" => "specific"
				);

				// set the access type
				if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('customer_groups', $update, "customergroupid = " . $row['customergroupid'])) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}

				//add each category to new table
				foreach ($categories as $category) {
					$insert = array(
						"customergroupid" => $row['customergroupid'],
						"categoryid" => $category
					);

					$GLOBALS['ISC_CLASS_DB']->InsertQuery('customer_group_categories', $insert);
				}
			}


			$query = "ALTER TABLE [|PREFIX|]customer_groups DROP accesscategories";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	* Adds the coupon type (percent or fixed amount) and sets that field using value in the coupon amount field.
	* Also updates the coupon amount to remove the percent or dollar symbol
	*
	*/
	public function add_order_coupon_type()
	{
		if(!$this->ColumnExists('[|PREFIX|]order_coupons', 'ordcoupontype')) {
			$query = "ALTER TABLE `[|PREFIX|]order_coupons` ADD `ordcoupontype` tinyint(4) NOT NULL ;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			// extract the type of coupon from the coupon amount and update the coupon
			$query = "SELECT * FROM [|PREFIX|]order_coupons";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$update = array(
					"ordcouponamount" => substr($row['ordcouponamount'], 0, -1)
				);

				if (substr($row['ordcouponamount'], -1, 1) == "%") {
					$update["ordcoupontype"] = 1;
				}
				else {
					$update["ordcoupontype"] = 0;
				}

				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('order_coupons', $update, 'ordcoupid = ' . $row['ordcoupid']);
			}


			// Updates coupon records to use the auto id for the order_product records instead of the actual product id
			$prodids = array();
			$coupids = array();

			$query = "
					SELECT DISTINCT
						oc.ordcoupid,
						op.orderprodid
					FROM
						[|PREFIX|]order_coupons oc
						LEFT JOIN [|PREFIX|]order_products op ON op.ordprodid = oc.ordcoupprodid AND op.orderorderid = oc.ordcouporderid
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if (in_array($row['orderprodid'], $prodids) || in_array($row['ordcoupid'], $coupids)) {
					continue;
				}

				$update = array(
					'ordcoupprodid' => $row['orderprodid']
				);

				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('order_coupons', $update, 'ordcoupid = ' . $row['ordcoupid']);

				$prodids[] = $row['orderprodid'];
				$coupids[] = $row['ordcoupid'];
			}
		}

		return true;
	}

	public function add_order_original_cost()
	{
		if(!$this->ColumnExists('[|PREFIX|]order_products', 'ordprodoriginalcost')) {
			$query = "ALTER TABLE `[|PREFIX|]order_products` ADD `ordprodoriginalcost` decimal(20, 4) NOT NULL AFTER `ordprodcost`;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$query = "UPDATE [|PREFIX|]order_products SET ordprodoriginalcost = ordprodcost";
			$GLOBALS['ISC_CLASS_DB']->Query($query);

			// get any coupons applied to order products and calculate the real original price
			$query = "SELECT * FROM [|PREFIX|]order_coupons";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				// percent type
				if ($row['ordcoupontype'] == 1) {
					$value = 'ordprodoriginalcost / (1 - (' . $row['ordcouponamount'] . '/ 100))';
				}
				else { // fixed amount
					$value = 'ordprodoriginalcost + ' . $row['ordcouponamount'];
				}

				$query = "UPDATE [|PREFIX|]order_products SET ordprodoriginalcost = " . $value . " WHERE orderprodid = " . $row['ordcoupprodid'];
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}
		}

		return true;
	}

	public function update_order_products()
	{
		$update_clause = "";

		if(!$this->ColumnExists('[|PREFIX|]order_products', 'ordprodistaxable')) {
			$query = "ALTER TABLE `[|PREFIX|]order_products` ADD `ordprodistaxable` tinyint(1) NOT NULL default '1'";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$update_clause = "op.ordprodistaxable = p.prodistaxable";
		}

		if(!$this->ColumnExists('[|PREFIX|]order_products', 'ordprodfixedshippingcost')) {
			$query = "ALTER TABLE `[|PREFIX|]order_products` ADD `ordprodfixedshippingcost` decimal(20,4) NOT NULL default '0'";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			if ($update_clause) {
				$update_clause .= ", ";
			}
			$update_clause .= "op.ordprodfixedshippingcost = p.prodfixedshippingcost";
		}

		// attempt to update the order products with data from products table
		if ($update_clause) {
			$query = "
				UPDATE
					[|PREFIX|]order_products op,
					[|PREFIX|]products p
				SET
					" . $update_clause . "
				WHERE
					 p.productid = op.ordprodid";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}
}