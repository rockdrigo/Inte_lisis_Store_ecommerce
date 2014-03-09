<?php

class ISC_ADMIN_UPGRADE_1400 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		"add_ccmanual_fields",
		"rename_googleanalytics_module",
		"create_unique_index_brands_brandname",
		"drop_sequences_tables",
		"create_index_product_images_imageprodid",
		"create_index_products_brand_vis",
		"create_unique_index_products_prodname",
		"create_index_reviews_revproductid",
		"create_index_orders_ordcustid",
		"create_index_news_date_vis",
		"create_index_customers_customertoken",
		"create_index_products_prodnumsold",
		"create_index_products_feature_vis",
		"create_index_order_messages_messageorderid",
		"create_unique_index_coupons_couponcode",
		"create_unique_index_gift_certificates_giftcertcode",
		"create_ft_index_reviews_text_title_from",
		"remove_old_config_file",
	);

	public function pre_upgrade_checks()
	{
		include_once(ISC_BASE_PATH.'/lib/class.file.php');

		$f = new FileClass();

		if(!is_dir(ISC_BASE_PATH."/config")) {
			$this->SetError(GetLang('UpgradePreChecks1400ConfigDirectory'));
		}
		else if(!$f->CheckDirWritable(ISC_BASE_PATH."/config")) {
			$this->SetError(GetLang('UpgradePreChecks1400ConfigDirectory'));
		}
		else if(!$f->CheckFileWritable(ISC_BASE_PATH."/config/config.php")) {
			$this->SetError(GetLang('UpgradePreChecks1400ConfigDirectory'));
		}
		else if($f->CheckFileWritable(ISC_BASE_PATH."/config/config.backup.php") && !$f->CheckFileWritable(ISC_BASE_PATH."/config/config.backup.php")) {
			$this->SetError(GetLang('UpgradePreChecks1400ConfigDirectory'));
		}

		if($this->HasErrors()) {
			return false;
		}
		else {
			return true;
		}
	}

	public function add_ccmanual_fields()
	{
		$query = "ALTER TABLE `[|PREFIX|]pending_orders` ADD `extrainfo` TEXT NOT NULL;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]orders` ADD `extrainfo` TEXT NOT NULL;";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// Need to build the encryption token here too
		$GLOBALS['EncryptionToken'] = md5(uniqid());

		if(!$this->update_1300_settings()) {
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function rename_googleanalytics_module()
	{
		$values = array (
			'modulename' => 'analytics_googleanalytics'
		);

		if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('module_vars', $values, "modulename='analytics_google'")) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$enabled_modules = explode(',', $GLOBALS['AnalyticsMethods']);

		foreach ($enabled_modules as $k => $mod) {
			if ($mod == 'analytics_google') {
				$enabled_modules[$k] = 'analytics_googleanalytics';
			}
		}

		$GLOBALS['AnalyticsMethods'] = implode(',', $enabled_modules);

		if(!$this->update_1300_settings()) {
			return false;
		}

		return true;
	}

	public function create_unique_index_brands_brandname()
	{
		$query = "ALTER TABLE [|PREFIX|]brands ADD UNIQUE u_brands_brandname (brandname)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function drop_sequences_tables()
	{
		$queries[] = "DROP TABLE [|PREFIX|]sequences";
		$queries[] = "DROP TABLE [|PREFIX|]order_sequences";

		foreach ($queries as $query) {
		if (!$GLOBALS['ISC_CLASS_DB']->	Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function create_index_product_images_imageprodid()
	{
		$query = "ALTER TABLE [|PREFIX|]product_images ADD INDEX i_product_images_imageprodid (imageprodid)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_index_products_brand_vis()
	{
		$query = "ALTER TABLE [|PREFIX|]products ADD INDEX i_products_brand_vis (prodbrandid, prodvisible)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_unique_index_products_prodname()
	{
		$query = "ALTER TABLE [|PREFIX|]products ADD UNIQUE u_products_prodname (prodname)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_index_reviews_revproductid()
	{
		$query = "ALTER TABLE [|PREFIX|]reviews ADD INDEX i_reviews_revproductid (revproductid)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_index_orders_ordcustid()
	{
		$query = "ALTER TABLE [|PREFIX|]orders ADD INDEX i_orders_ordcustid (ordcustid)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_index_news_date_vis()
	{
		$query = "ALTER TABLE [|PREFIX|]news ADD INDEX i_news_date_vis (newsdate, newsvisible)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_index_customers_customertoken()
	{
		$query = "ALTER TABLE [|PREFIX|]customers ADD INDEX i_customers_customertoken (customertoken)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_index_products_prodnumsold()
	{
		$query = "ALTER TABLE [|PREFIX|]products ADD INDEX i_products_prodnumsold (prodnumsold)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_index_products_feature_vis()
	{
		$query = "ALTER TABLE [|PREFIX|]products ADD INDEX i_products_feature_vis (prodfeatured, prodvisible)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_index_order_messages_messageorderid()
	{
		$query = "ALTER TABLE [|PREFIX|]order_messages ADD INDEX i_order_mesages_messageorderid (messageorderid)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_unique_index_coupons_couponcode()
	{
		$query = "ALTER TABLE [|PREFIX|]coupons ADD UNIQUE u_coupons_couponcode (couponcode)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_unique_index_gift_certificates_giftcertcode()
	{
		$query = "ALTER TABLE [|PREFIX|]gift_certificates ADD UNIQUE u_gift_certificates (giftcertcode)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_ft_index_reviews_text_title_from()
	{
		$query = "ALTER TABLE [|PREFIX|]reviews ADD FULLTEXT ft_reviews_text_title_from (revtext,revtitle,revfromname)";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function remove_old_config_file()
	{
		// Rebuild the new config file
		$GLOBALS['ISC_CLASS_ADMIN_SETTINGS'] = GetClass('ISC_ADMIN_SETTINGS');
		$GLOBALS['ISC_CLASS_ADMIN_SETTINGS']->CommitSettings();

		if (is_dir(ISC_BASE_PATH.'/config') && is_file(ISC_CONFIG_FILE)) {
			$old_config = ISC_BASE_PATH.'/admin/includes/config.php';

			if (@unlink($old_config)) {
				return true;
			} else {
				$this->SetError("");
				return false;
			}

		}
	}

	public function update_1300_settings()
	{
		// This needs to be here so when we call CommitSettings() we aren't actually writing to a file that doesn't exist
		if (is_array($GLOBALS['ReturnReasons'])) {
			$ReturnReasons = $GLOBALS['ReturnReasons'];
			$GLOBALS['ReturnReasons'] = var_export($GLOBALS['ReturnReasons'], true);
			$GLOBALS['ReturnReasons'] = str_replace("\n", "\n\t\t", $GLOBALS['ReturnReasons']);
		}

		if (is_array($GLOBALS['ReturnActions'])) {
			$ReturnActions = $GLOBALS['ReturnActions'];
			$GLOBALS['ReturnActions'] = var_export($GLOBALS['ReturnActions'], true);
			$GLOBALS['ReturnActions'] = str_replace("\n", "\n\t\t", $GLOBALS['ReturnActions']);
		}

		if (is_array($GLOBALS['GiftCertificateAmounts'])) {
			$GiftCertificateAmounts = $GLOBALS['GiftCertificateAmounts'];
			$GLOBALS['GiftCertificateAmounts'] = var_export($GLOBALS['GiftCertificateAmounts'], true);
			$GLOBALS['GiftCertificateAmounts'] = str_replace("\n", "\n\t\t", $GLOBALS['GiftCertificateAmounts']);
		}

		$config_data = $this->template->render('config.file.tpl');

		// Swap variables back
		if (is_array($GLOBALS['ReturnReasons'])) {
			$GLOBALS['ReturnReasons'] = $ReturnReasons;
		}

		if (is_array($GLOBALS['ReturnActions'])) {
			$GLOBALS['ReturnActions'] = $ReturnActions;
		}

		if (is_array($GLOBALS['GiftCertificateAmounts'])) {
			$GLOBALS['GiftCertificateAmounts'] = $GiftCertificateAmounts;
		}

		// Always make sure we're saving the shop path as a HTTP link
		$GLOBALS["ShopPath"] = str_replace("https://", "http://", $GLOBALS["ShopPath"]);

		$setting_string = "<" . "?php\n\n";
		$setting_string .= "\t// Last Updated: ".isc_date("jS M Y @ g:i A") . "\n";
		$setting_string .= $config_data;
		$setting_string .= "?" . ">";

		// Try to write to the config file
		if (is_writable(ISC_BASE_PATH."/admin/includes/config.php")) {
			if ($fp = @fopen(ISC_BASE_PATH."/admin/includes/config.php", "wb+")) {
				if (!@fwrite($fp, $setting_string)) {
					$this->SetError(GetLang('CouldntSaveConfig'));
					return false;
				}
			}
			else {
				$this->SetError(GetLang('CouldntSaveConfig'));
				return false;
			}
		}
		else {
			$this->SetError(GetLang('CouldntSaveConfig'));
			return false;
		}

		return true;
	}
}