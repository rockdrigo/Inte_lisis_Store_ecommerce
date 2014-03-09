<?php

class ISC_ADMIN_UPGRADE_1800 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		"add_variation_tables",
		"convert_options_to_variations",
		"drop_product_options",
		"fix_creditcard_encryption",
		"rename_module_variables",
		"create_billing_fields",
		"add_variation_permissions"
	);

	public function add_variation_tables()
	{
		$query = "CREATE TABLE `[|PREFIX|]product_variation_combinations` (
		  `combinationid` int(11) NOT NULL auto_increment,
		  `vcproductid` int(11) NOT NULL default '0',
		  `vcvariationid` int(11) NOT NULL default '0',
		  `vcenabled` tinyint(4) NOT NULL default '1',
		  `vcoptionids` varchar(100) NOT NULL default '',
		  `vcsku` varchar(50) NOT NULL default '',
		  `vcpricediff` enum('','add','subtract','fixed') NOT NULL default '',
		  `vcprice` decimal(10,4) NOT NULL default '0',
		  `vcweightdiff` enum('','add','subtract','fixed') NOT NULL default '',
		  `vcweight` decimal(10,4) NOT NULL default '0',
		  `vcimage` varchar(100) NOT NULL default '',
		  `vcthumb` varchar(100) NOT NULL default '',
		  `vcstock` int(11) NOT NULL default '0',
		  `vclowstock` int(11) NOT NULL default '0',
		  PRIMARY KEY  (`combinationid`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "CREATE TABLE `[|PREFIX|]product_variation_options` (
		  `voptionid` int(11) NOT NULL auto_increment,
		  `vovariationid` int(11) NOT NULL default '0',
		  `voname` varchar(255) NOT NULL default '',
		  `vovalue` text,
		  PRIMARY KEY  (`voptionid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "CREATE TABLE `[|PREFIX|]product_variations` (
		  `variationid` int(11) NOT NULL auto_increment,
		  `vname` varchar(100) NOT NULL default '',
		  `vnumoptions` int(11) NOT NULL default '0',
		  PRIMARY KEY  (`variationid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]products` ADD `prodvariationid` int(11) NOT NULL default '0'";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]order_products` ADD `ordprodvariationid` int(11) NOT NULL default '0'";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]order_products` ADD `ordprodoptions` text";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]returns` ADD `retprodvariationid` INT( 11 ) NOT NULL default '0' AFTER `retprodid`;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]returns` ADD `retprodoptions` text AFTER `retprodvariationid`;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function convert_options_to_variations()
	{
		// Truncate option tables etc here (so if they hit from a failed upgrade, we don't get duplicates)
		$GLOBALS['ISC_CLASS_DB']->Query("TRUNCATE [|PREFIX|]product_variations");
		$GLOBALS['ISC_CLASS_DB']->Query("TRUNCATE [|PREFIX|]product_variation_options");
		$GLOBALS['ISC_CLASS_DB']->Query("TRUNCATE [|PREFIX|]product_variation_combinations");

		$query = "
			SELECT o.*, p.prodname
			FROM [|PREFIX|]product_options o
			LEFT JOIN [|PREFIX|]products p ON (p.productid=o.optprodid)
			ORDER BY optprodid, optname
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$createdSets = array();
		while($option = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if(!isset($createdSets[$option['optprodid']])) {
				$newVariation = array(
					"vname" => $option['prodname']." Options",
					"vnumoptions" => 0
				);
				$variationid = $GLOBALS['ISC_CLASS_DB']->InsertQuery("product_variations", $newVariation);

				// Update the product so it knows about this set
				$updatedProduct = array(
					"prodvariationid" => $variationid
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("products", $updatedProduct, "productid='".$option['optprodid']."'");
				$createdSets[$option['optprodid']] = $variationid;
			}
			else {
				$variationid = $createdSets[$option['optprodid']];
			}

			// Now we create the variation options record
			$newOption = array(
				"vovariationid" => $variationid,
				"voname" => $option['optname'],
				"vovalue" => $option['optvalue']
			);
			$optionid = $GLOBALS['ISC_CLASS_DB']->InsertQuery("product_variation_options", $newOption);
			if(!$optionid) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			// Now the combination setting
			$newCombination = array(
				"vcproductid" => $option['optprodid'],
				"vcvariationid" => $variationid,
				"vcenabled" => 1,
				"vcoptionids" => $optionid,
				"vcsku" => "",
				"vcpricediff" => "",
				"vcprice" => 0,
				"vcweightdiff" => "",
				"vcweight" => 0,
				"vcimage" => "",
				"vcthumb" => "",
				"vcstock" => $option['optcurrentstock'],
				"vclowstock" => $option['optlowstock']
			);
			if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery("product_variation_combinations", $newCombination)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			// Update the number of combinations for this product
			$query = "UPDATE [|PREFIX|]product_variations SET vnumoptions=vnumoptions+1 WHERE variationid='".$variationid."'";
			$GLOBALS['ISC_CLASS_DB']->Query($query);
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function drop_product_options()
	{
		$queries = array(
			"DROP TABLE [|PREFIX|]product_options",
			"ALTER TABLE [|PREFIX|]order_products DROP ordprodoptionid"
		);
		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function fix_creditcard_encryption()
	{
		// Select out all orders with extrainfo != ''
		$query = "SELECT orderid, extrainfo FROM [|PREFIX|]orders WHERE extrainfo!=''";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// OK, so what needs to be done here is unserialize the info, base64_encode the cc_ccno and cc_cvv2 fields and update it again
			$row['extrainfo'] = @unserialize($row['extrainfo']);

			// Only do those not already encoded
			if(!isset($row['extrainfo']['cc64'])) {
				$row['extrainfo']['cc_ccno'] = base64_encode($row['extrainfo']['cc_ccno']);
				if(isset($row['extrainfo']['cc_cvv2'])) {
					$row['extrainfo']['cc_cvv2'] = base64_encode($row['extrainfo']['cc_cvv2']);
				}
				$row['extrainfo']['cc64'] = 1;
				$updatedOrder = array("extrainfo" => serialize($row['extrainfo']));
				if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid='".$row['orderid']."'")) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function rename_module_variables()
	{
		// Replace any instances of modulename_ in variable name with ''
		$query = "UPDATE [|PREFIX|]module_vars SET variablename=REPLACE(variablename, CONCAT(modulename, '_'), '');";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function create_billing_fields()
	{
		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordbillphone` varchar(50) NOT NULL default '' AFTER `ordbillcountry`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordbillemail` varchar(250) NOT NULL default '' AFTER `ordbillphone`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "DELETE FROM [|PREFIX|]customers WHERE custdeleted=1";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]customers DROP `custdeleted`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function add_variation_permissions()
	{
		$query = "SELECT pk_userid FROM [|PREFIX|]users";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($user = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// Insert the new permission
			$newPermission = array(
				"permuserid" => $user['pk_userid'],
				"permpermissionid" => 164
			);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery("permissions", $newPermission);
		}
		return true;
	}
}