<?php
class ISC_ADMIN_UPGRADE_5003 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'add_sales_receipt_option',
		'modify_accountingspoolserial_column',
		'add_channel_countries',
		'add_bill_email_index',
		'add_coupon_values_table',
		'add_new_permissions',
		'add_discount_amount',
		'remove_bankcard',
		'remove_tinymce_cache'
	);

	public function add_sales_receipt_option()
	{
		if (!$this->EnumExists('[|PREFIX|]accountingspool', 'accountingspooltype', 'salesorder')) {
			$query = "ALTER TABLE [|PREFIX|]accountingspool MODIFY accountingspooltype ENUM('customer','customergroup','product','order','salesorder','salesreceipt','salestaxcode','account','inventorylevel') NOT NULL";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$savedata = array(
				'accountingspooltype' => 'salesorder'
			);

			if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('accountingspool', $savedata, "accountingspooltype = 'order'")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

				$query = "ALTER TABLE [|PREFIX|]accountingspool MODIFY accountingspooltype ENUM('customer','customergroup','product','salesorder','salesreceipt','salestaxcode','account','inventorylevel') NOT NULL";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->EnumExists('[|PREFIX|]accountingref', 'accountingreftype', 'salesorder')) {
			$query = "ALTER TABLE [|PREFIX|]accountingref MODIFY accountingreftype ENUM('customer','customergroup','product','order','salesorder','salesreceipt','salestaxcode','account','inventorylevel','orderlineitem') NOT NULL";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$savedata = array(
				'accountingreftype' => 'salesorder'
			);

			if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('accountingref', $savedata, "accountingreftype = 'order'")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$query = "ALTER TABLE [|PREFIX|]accountingref MODIFY accountingreftype ENUM('customer','customergroup','product','salesorder','salesreceipt','salestaxcode','account','inventorylevel','orderlineitem') NOT NULL";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function modify_accountingspoolserial_column()
	{
		$query = "ALTER TABLE [|PREFIX|]accountingspool MODIFY accountingspoolserial longtext";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_channel_countries()
	{
		// Jersey
		$query = "SELECT * FROM [|PREFIX|]countries WHERE countryname = 'Jersey'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$insert = array(
					'countryname'		=> 'Jersey',
					'countryiso2'		=> 'JE',
					'countryiso3'		=> 'JEY'
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery('countries', $insert);
		}

		// Guernsey
		$query = "SELECT * FROM [|PREFIX|]countries WHERE countryname = 'Guernsey'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$insert = array(
					'countryname'		=> 'Guernsey',
					'countryiso2'		=> 'GG',
					'countryiso3'		=> 'GGY'
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery('countries', $insert);
		}

		return true;
	}

	public function add_bill_email_index()
	{
		if (!$this->IndexExists('[|PREFIX|]orders', 'i_orders_ordbillemail')) {
			$query = "ALTER TABLE `[|PREFIX|]orders` ADD INDEX `i_orders_ordbillemail` (`ordbillemail`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_coupon_values_table()
	{
		if (!$this->TableExists('coupon_values')) {
			$query = "
				CREATE TABLE `[|PREFIX|]coupon_values` (
					`couponid` int(11) NOT NULL,
					`valueid` int(11) NOT NULL,
					PRIMARY KEY  (`couponid`,`valueid`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci
			";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			// if the coupon applies to values column exists, move all the values into our new table
			if ($this->ColumnExists('[|PREFIX|]coupons', 'couponappliestovalues')) {
				$query = "
					SELECT
						couponid,
						couponappliestovalues
					FROM
						[|PREFIX|]coupons
					";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$values = explode(",", $row['couponappliestovalues']);

					foreach ($values as $value) {
						$couponvalue = array(
							'couponid' => $row['couponid'],
							'valueid' => $value
						);

						$GLOBALS['ISC_CLASS_DB']->InsertQuery('coupon_values', $couponvalue);
					}
				}

				// remove the applies to values column
				$query = "ALTER TABLE [|PREFIX|]coupons DROP COLUMN couponappliestovalues";
				if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	public function add_new_permissions()
	{
		// Array of new permission => insert in to users that have the following permission
		$perms = array(
			AUTH_Manage_Images => AUTH_Manage_Pages
		);

		// Delete any existing occurances of these permissions
		$permIds = array_keys($perms);
		$permIds = implode(',', $permIds);
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('permissions', "WHERE permpermissionid IN (".$permIds.")");

		// OK, do some magic.. well it's not actually manage nor is it anything cool, but you get the point.
		foreach($perms as $permission => $insertWhere) {
			$query = $GLOBALS['ISC_CLASS_DB']->Query("SELECT permuserid FROM [|PREFIX|]permissions WHERE permpermissionid='".(int)$insertWhere."'");
			while($user = $GLOBALS['ISC_CLASS_DB']->Fetch($query)) {
				$newPermission = array(
					'permuserid' => $user['permuserid'],
					'permpermissionid' => $permission
				);
				if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('permissions', $newPermission)) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	public function add_discount_amount()
	{
		if (!$this->ColumnExists('[|PREFIX|]orders', 'orddiscountamount')) {
			$query = "ALTER TABLE `[|PREFIX|]orders` ADD `orddiscountamount` DECIMAL(20, 4) NOT NULL";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function remove_bankcard()
	{
		// remove the bankcard from manual credit card variables so we don't get checkout issues
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename = 'checkout_creditcardmanually' AND variablename = 'acceptedcards' AND variableval = 'AUBANKCARD'");

		return true;
	}

	public function remove_tinymce_cache()
	{
		// since tinymce has been updated, we want to remove the cached gzip copy(s) of the editor

		$dir = ISC_BASE_PATH . "/cache/";

		if (is_dir($dir)) {
			if ($handle = opendir($dir)) {
				 while (false !== ($file = readdir($handle))) {
					if (is_file($dir . $file) && preg_match("#^tiny_mce_(.*).gz$#", $file)) {
						 @unlink($file);
					}
				 }
				 closedir($handle);
			}
		}

		return true;
	}
}