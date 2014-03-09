<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'entity.base.php');

class ISC_ENTITY_PRODUCT extends ISC_ENTITY_BASE
{
	public function __construct()
	{
		$schema = array(
				"productid" => "int",
				"prodname" => "text",
				"prodtype" => "int",
				"prodcode" => "text",
				"prodfile" => "text",
				"proddesc" => "text",
				"prodsearchkeywords" => "text",
				"prodavailability" => "text",
				"prodprice" => "price",
				"prodcostprice" => "price",
				"prodretailprice" => "price",
				"prodsaleprice" => "price",
				"prodcalculatedprice" => "price",
				"prodsortorder" => "int",
				"prodvisible" => "bool",
				"prodfeatured" => "bool",
				"prodvendorfeatured" => "bool",
				"prodrelatedproducts" => "text",
				"prodcurrentinv" => "int",
				"prodlowinv" => "int",
				"prodoptionsrequired" => "bool",
				"prodwarranty" => "text",
				"prodweight" => "measurement",
				"prodwidth" => "measurement",
				"prodheight" => "measurement",
				"proddepth" => "measurement",
				"prodfixedshippingcost" => "price",
				"prodfreeshipping" => "bool",
				"prodinvtrack" => "int",
				"prodratingtotal" => "int",
				"prodnumratings" => "int",
				"prodnumsold" => "int",
				"proddateadded" => "date",
				"prodbrandid" => "int",
				"prodnumviews" => "int",
				"prodpagetitle" => "text",
				"prodmetakeywords" => "text",
				"prodmetadesc" => "text",
				"prodlayoutfile" => "text",
				"prodvariationid" => "int",
				"prodallowpurchases" => "bool",
				"prodhideprice" => "bool",
				"prodcallforpricinglabel" => "text",
				"prodcatids" => "text",
				"prodlastmodified" => "date",
				"prodvendorid" => "int",
				"prodhastags" => "bool",
				"prodwrapoptions" => "text",
				"prodconfigfields" => "text",
				"prodeventdaterequired" => "bool",
				"prodeventdatefieldname" => "text",
				"prodeventdatelimited" => "bool",
				"prodeventdatelimitedtype" => "int",
				"prodeventdatelimitedstartdate" => "date",
				"prodeventdatelimitedenddate" => "date",
				"prodmyobasset" => "text",
				"prodmyobincome" => "text",
				"prodmyobexpense" => "text",
				"prodpeachtreegl" => "text",
				"prodcondition" => "text",
				"prodshowcondition" => "bool",
				"product_enable_optimizer" => "bool",
				"prodpreorder" => "bool",
				"prodreleasedate" => "int",
				"prodreleasedateremove" => "bool",
				"prodpreordermessage" => "text",
				"prodminqty" => "int",
				"prodmaxqty" => "int",
				'tax_class_id' => 'int',
				"opengraph_type" => "text",
				"opengraph_use_product_name" => "bool",
				"opengraph_title" => "text",
				"opengraph_use_meta_description" => "bool",
				"opengraph_description" => "text",
				"opengraph_use_image" => "bool",
				"upc" => "text",
				"disable_google_checkout" => "int",
				"last_import" => "int",
		);

		$tableName = "products";
		$primaryKeyName = "productid";
		$searchFields = array(
				"productid",
				"prodname",
				"prodcode"
		);

		$customKeyName = "";

		parent::__construct($schema, $tableName, $primaryKeyName, $searchFields, $customKeyName);
	}

	protected function parseprodconditionHook($value)
	{
		switch (isc_strtolower($value)) {
			case "used":
				$value = "Used";
				break;

			case "refurbished";
				$value = "Refurbished";
				break;

			default:
				$value = "New";
				break;
		}

		return $value;
	}

	protected function addPrehook(&$savedata, $rawInput)
	{
		/**
		 * Workout the calculated price for this product as it will be displayed
		 */
		if (isset($rawInput["prodprice"]) && isset($rawInput["prodretailprice"]) && isset($rawInput["prodsaleprice"])) {
			$savedata["prodcalculatedprice"] = CalcRealPrice($rawInput["prodprice"], $rawInput["prodsaleprice"]);
		}

		/**
		 * If inventory tracking is on a product option basis, then product options are required
		 */
		if (array_key_exists("prodinvtrack", $savedata) && $savedata["prodinvtrack"] == 2) {
			$savedata["prodoptionsrequired"] = 1;
		}

		/**
		 * If we are importing and don"t have any variations
		 */
		if (!array_key_exists("prodvariationid", $savedata)) {
			$savedata["prodvariationid"] = 0;
		}

		if (!array_key_exists("prodallowpurchases", $savedata)) {
			$savedata["prodallowpurchases"] = 1;
			$savedata["prodhideprice"] = 0;
			$savedata["prodcallforpricinglabel"] = "";
		}

		if (!array_key_exists("prodhideprice", $savedata)) {
			$savedata["prodhideprice"] = 0;
		}

		if (!array_key_exists("prodpreorder", $savedata)) {
			$savedata["prodpreorder"] = 0;
			$savedata["prodreleasedate"] = 0;
			$savedata["prodreleasedateremove"] = 0;
		}

		if (!array_key_exists("prodreleasedate", $savedata)) {
			$savedata["prodreleasedate"] = 0;
			$savedata["prodreleasedateremove"] = 0;
		}

		if (!array_key_exists("prodreleasedateremove", $savedata)) {
			$savedata["prodreleasedateremove"] = 0;
		}

		if (!array_key_exists("prodcallforpricinglabel", $savedata)) {
			$savedata["prodcallforpricinglabel"] = "";
		}

		if (array_key_exists("prodcats", $rawInput)) {
			$savedata["prodcatids"] = implode(",", $rawInput["prodcats"]);
		} else {
			$savedata["prodcatids"] = "";
		}

		if (!isset($savedata["prodvendorid"])) {
			$savedata["prodvendorid"] = 0;
		}

		if (isset($rawInput["prodtags"]) && $rawInput["prodtags"] != "") {
			$savedata["prodhastags"] = 1;
		} else {
			$savedata["prodhastags"] = 0;
		}

		if (!isset($savedata["prodvendorfeatured"])) {
			$savedata["prodvendorfeatured"] = 0;
		}

		if (!isset($savedata["prodwrapoptions"])) {
			$savedata["prodwrapoptions"] = 0;
		}

		if (!isset($savedata["prodeventdatefieldname"])) {
			$savedata["prodeventdaterequired"] = 0;
			$savedata["prodeventdatefieldname"] = "";
			$savedata["prodeventdatelimited"] = 0;
			$savedata["prodeventdatelimitedtype"] = 0;
			$savedata["prodeventdatelimitedstartdate"] = 0;
			$savedata["prodeventdatelimitedenddate"] = 0;
		}

		if(!isset($savedata['tax_class_id'])) {
			$savedata['tax_class_id'] = 0;
		}
		if (!isset($savedata['opengraph_type'])) {
			$savedata['opengraph_type'] = 'product';
			$savedata['opengraph_use_product_name'] = 1;
			$savedata['opengraph_title'] = '';
			$savedata['opengraph_use_meta_description'] = 1;
			$savedata['opengraph_description'] = '';
			$savedata['opengraph_use_image'] = 1;
		}

		if(!isset($savedata['upc'])) {
			$savedata['upc'] = '';
		}

		if(!isset($savedata['disable_google_checkout'])){
			$savedata['disable_google_checkout'] = 0;
		}

		if(!isset($savedata['last_import'])){
			$savedata['last_import'] = 0;
		}

		$savedata["proddateadded"] = time();
		$savedata["prodlastmodified"] = time();

		return true;
	}

	/**
	 * Handle updates for additional data after a product is created.
	 *
	 * @param int $productId ID of the product.
	 * @param array $data Changes made to the product.
	 * @param array $rawInput Raw data passed to the entity before validation.
	 * @return boolean True on success, false on failure.
	 */
	protected function addPostHook($productId, $data, $rawInput)
	{
		if(!$this->saveTaxPricing($productId, $data)) {
			return false;
		}

		return true;
	}

	/**
	 * Handle updates for additional data after a product is updated.
	 *
	 * @param int $productId ID of the product.
	 * @param array $data Changes made to the product.
	 * @param array $rawInput Raw data passed to the entity before validation.
	 * @return boolean True on success, false on failure.
	 */
	protected function editPostHook($productId, $data, $rawInput)
	{
		if(!$this->saveTaxPricing($productId, $data)) {
			return false;
		}

		return true;
	}

	/**
	 * Update the tax pricing table for the prices of this product when the
	 * product is saved.
	 *
	 * @see self::addPostHook
	 * @see self::editPostHook
	 * @return boolean True if successful, false on a failure.
	 */
	protected function saveTaxPricing($productId, $savedata)
	{
		$priceColumns = array(
			'prodprice',
			'prodsaleprice',
			'prodcostprice',
			'prodretailprice'
		);

		if(!isset($savedata['tax_class_id'])) {
			$query = "
				SELECT tax_class_id
				FROM [|PREFIX|]products
				WHERE productid=".$productId;
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			$savedata['tax_class_id'] = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		}

		foreach($priceColumns as $column) {
			if(!isset($savedata[$column])) {
				continue;
			}
			getClass('ISC_TAX')->updateProductTaxPricing(
				$savedata[$column],
				$savedata['tax_class_id']
			);
		}

		return true;
	}

	protected function editPrehook($productId, &$savedata, $rawInput)
	{
		/**
		 * Workout the calculated price for this product as it will be displayed
		 */
		if (isset($rawInput["prodprice"]) && isset($rawInput["prodretailprice"]) && isset($rawInput["prodsaleprice"])) {
			$savedata["prodcalculatedprice"] = CalcRealPrice($rawInput["prodprice"], $rawInput["prodsaleprice"]);
		}

		/**
		 * If inventory tracking is on a product option basis, then product options are required
		 */
		if (array_key_exists("prodinvtrack", $savedata) && $savedata["prodinvtrack"] == 2) {
			$savedata["prodoptionsrequired"] = 1;
		}

		if (isset($rawInput["prodcats"])) {
			$savedata["prodcatids"] = implode(",", $rawInput["prodcats"]);
		}

		$savedata["prodlastmodified"] = time();

		return true;
	}

	/**
	 * Assign the categories to a product
	 *
	 * Method will assign an array of category IDs $categories to product $productId
	 *
	 * @access public
	 * @param int $productId The product ID
	 * @param array $categories An array of category IDs to assign to the product
	 * @return int The total amount of categories assigned on success, FALSE on error
	 */
	public function assignCategories($productId, $categories)
	{
		if (isId($categories)) {
			$categories = array($categories);
		}

		$categories = array_filter($categories, "isId");

		if (!isId($productId) || empty($categories)) {
			return false;
		}

		$total = 0;

		foreach ($categories as $categoryId) {
			$savedata = array(
							"productid" => $productId,
							"categoryid" => $categoryId
			);

			if ($GLOBALS["ISC_CLASS_DB"]->InsertQuery("categoryassociations", $savedata) !== false) {
				$total++;
			}
		}

		return $total;
	}

	public function deletePosthook($productId, $node)
	{
		ISC_PRODUCT_IMAGE::deleteOrphanedProductImages();
		ISC_PRODUCT_VIEWS::onProductDelete($productId);
		return true;
	}
}
