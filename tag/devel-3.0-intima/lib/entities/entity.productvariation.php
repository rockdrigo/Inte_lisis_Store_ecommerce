<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'entity.base.php');

class ISC_ENTITY_PRODUCTVARIATION extends ISC_ENTITY_BASE
{
	private $productEntity;

	public function __construct()
	{
		$schema = array(
			"combinationid" => "int",
			"vcproductid" => "int",
			"vcproducthash" => "text",
			"vcvariationid" => "int",
			"vcenabled" => "bool",
			"vcoptionids" => "text",
			"vcsku" => "text",
			"vcpricediff" => "text",
			"vcprice" => "price",
			"vcweightdiff" => "text",
			"vcweight" => "measurement",
			"vcimage" => "text",
			"vcimagezoom" => "text",
			"vcimagestd" => "text",
			"vcimagethumb" => "text",
			"vcstock" => "int",
			"vclowstock" => "int"
		);

		$tableName = "product_variation_combinations";
		$primaryKeyName = "combinationid";
		$searchFields = array(
				"combinationid",
				"vcproductid",
				"vcsku"
		);

		$customKeyName = "";

		parent::__construct($schema, $tableName, $primaryKeyName, $searchFields, $customKeyName);

		$this->productEntity = new ISC_ENTITY_PRODUCT();
	}

	private function vcDifferenceHook($value)
	{
		switch (isc_strtolower($value)) {
			case "add";
				return "add";
				break;

			case "subtract":
				return "subtract";
				break;

			case "fixed":
				return "fixed";
				break;

			default:
				return "";
		}
	}

	protected function parsevcpricediffHook($value)
	{
		return $this->vcDifferenceHook($value);
	}

	protected function parsevcweightdiffHook($value)
	{
		return $this->vcDifferenceHook($value);
	}

	/**
	 * Add a product variation combination
	 *
	 * Method will add a product variation combination to the database (NOT IMPLEMENTED!!!)
	 *
	 * @access public
	 * @param array $input The product variation combination details
	 * @return int The product variation combination record ID on success, FALSE otherwise
	 */
	public function add($input)
	{
		return false;
	}

	protected function editPrehook($combinationId, &$savedata, $rawInput)
	{
		/**
		 * We need to unset these values for the time being
		 */
		$unsetKeys = array(
					"vcproductid",
					"vcproducthash",
					"vcvariationid",
					"vcenabled",
					"vcoptionids"
		);

		foreach ($unsetKeys as $key) {
			if (array_key_exists($key, $savedata)) {
				unset($savedata[$key]);
			}
		}

		return true;
	}

	/**
	 * Delete a product variation
	 *
	 * Method will delete a product variation (NOT IMPLEMENTED!!!)
	 *
	 * @access public
	 * @param int $variationId The product variation combination ID
	 * @return bool TRUE if the product was deleted successfully, FASLE otherwise
	 */
	public function delete($variationId)
	{
		return false;
	}

	/**
	 * Does product variation combination exists?
	 *
	 * Method will return TRUE/FLSE depending if the product variation combination exists
	 *
	 * @access public
	 * @param int $variationId The product variation combination ID
	 * @param int $productId The optional product ID for the combination
	 * @return bool TRUE if the product variation combination exists, FALASE otherwise
	 */
	public function exists($variationId, $productId='')
	{
		if (!isId($variationId) || !$this->get($variationId, $productId, false)) {
			return false;
		}

		return true;
	}

	protected function getPosthook($combinationId, &$combination)
	{
		$combination["prodvariationname"] = array();
		$combination["prodvariationarray"] = array();

		$query = "SELECT voname, vovalue
					FROM [|PREFIX|]product_variation_options
					WHERE voptionid IN (" . $combination["vcoptionids"] . ")
						AND vovariationid = " . $combination["vcvariationid"];

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($option = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$combination["prodvariationname"][] = $option["voname"] . ": " . $option["vovalue"];
			$combination["prodvariationarray"][$option["voname"]] = $option["vovalue"];
		}

		$combination["prodvariationname"] = implode(",", $combination["prodvariationname"]);
		return true;
	}

	/**
	 * Get the product variation combination record
	 *
	 * Method will return the product variation combination record
	 *
	 * @access public
	 * @param int $variationId The product variation combination ID
	 * @param int $productId The optional product ID for the combination
	 * @param bool $mergeProductDetails TRUE to also get the product details, FALSE not to. Default is TRUE
	 * @return array The product variation combination array on success, NULL if no record could be found, FALSE on error
	 */
	public function get($variationId, $productId='', $mergeProductDetails=true)
	{
		$variation = parent::get($variationId);

		if (!is_array($variation)) {
			return false;
		}

		if (isId($productId) && (!isset($variation["vcproductid"]) || $variation["vcproductid"] !== $productId)) {
			return false;
		}

		/**
		 * Merge in the product details if we can
		 */
		if ($mergeProductDetails && isset($variation["vcproductid"]) && isId($variation["vcproductid"])) {
			$product = $this->productEntity->get($variation["vcproductid"]);

			if (is_array($product)) {
				$variation += $product;
			}
		}

		return $variation;
	}
}
