<?php
class ISC_ADMIN_EBAY_LIST_PRODUCTS {
	/**
	* Builds a WHERE clause from the given set of product options
	*
	* @param array $productOptions Array of data that can contain the individually selected product Ids, or a search Id and/or search term
	* @return string The WHERE clause
	*/
	public static function getWhereFromOptions($productOptions)
	{
		// build where if individual products were selected
		if (!empty($productOptions['productIds'])) {

			parse_str(urldecode($productOptions['productIds']), $productIds);
			$productIds = $productIds['products'];

			$where = 'p.productid IN (' . implode(',', $productIds) . ')';
		}
		// build from search id/search query if available
		else {
			$res = GetClass('ISC_ADMIN_PRODUCT')->BuildWhereFromVars($productOptions);
			$where = $res['query'];

			// strip AND from beginning and end of statement
			$where = preg_replace("/^( ?AND )?|( AND ?)?$/i", "", $where);
		}

		return $where;
	}

	/**
	* Gets the number of products to be listed with a given WHERE clause
	*
	* @param string $where Optional WHERE clause (without WHERE keyword) to filter the products by
	* @return int The number of products
	*/
	public static function getProductCount($where = '')
	{
		if ($where) {
			$where .= " AND ";
		}

		// restrict to physical products only with no variations
		$where .= "p.prodtype = " . PT_PHYSICAL;

		// get the number of products to be listed
		$countQuery = "
				SELECT
					COUNT(DISTINCT p.productid) AS ProductCount
				FROM
					[|PREFIX|]categoryassociations ca
					INNER JOIN [|PREFIX|]products p ON p.productid = ca.productid
					INNER JOIN [|PREFIX|]product_search ps ON p.productid = ps.productid
					LEFT JOIN [|PREFIX|]brands b ON b.brandid = p.prodbrandid
				WHERE " . $where;



		$countResult = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
		$productCount = $GLOBALS['ISC_CLASS_DB']->FetchOne($countResult);

		return $productCount;
	}

	/**
	* Gets the products to be listed with a given WHERE clause
	*
	* @param string $where Optional WHERE clause (without WHERE keyword) to filter the products by
	* @return Mixed Returns false if the query is empty or if there is no result. Otherwise returns the result of the query.
	*/
	public static function getProducts($where = '')
	{
		$rows = array();
		if ($where) {
			$where .= " AND ";
		}

		// restrict to physical products only with no variations
		$where .= "p.prodtype = " . PT_PHYSICAL;

		// get the number of products to be listed
		$query = "
				SELECT
					DISTINCT p.productid
				FROM
					[|PREFIX|]categoryassociations ca
					INNER JOIN [|PREFIX|]products p ON p.productid = ca.productid
					INNER JOIN [|PREFIX|]product_search ps ON p.productid = ps.productid
					LEFT JOIN [|PREFIX|]brands b ON b.brandid = p.prodbrandid
				WHERE " . $where;

		$results = $GLOBALS['ISC_CLASS_DB']->Query($query);
		return $results;
	}

	/**
	* Gets the SQL query statement to retrieve products for listing
	*
	* @param string $where Optional where clause to filter products
	* @return string The query
	*/
	public static function getListQuery($where = '', $limit = 5, $offset = 0)
	{
		if ($where) {
			$where .= " AND ";
		}

		// restrict to physical products only with no variations
		$where .= "p.prodtype = " . PT_PHYSICAL;

		$listQuery = "
			SELECT
				p.productid,
				p.prodname,
				p.proddesc,
				p.prodcode,
				p.prodprice,
				p.prodcondition,
				p.prodweight,
				p.prodwidth,
				p.prodheight,
				p.proddepth,
				p.prodvariationid,
				b.brandname,
				p.upc,
				pi.*
			FROM
				(
					SELECT
						DISTINCT ca.productid
					FROM
						[|PREFIX|]categoryassociations ca
						INNER JOIN [|PREFIX|]products p ON p.productid = ca.productid
						INNER JOIN [|PREFIX|]product_search ps ON p.productid = ps.productid
						LEFT JOIN [|PREFIX|]brands b ON b.brandid = p.prodbrandid
					WHERE " . $where . "
				) AS ca
				INNER JOIN [|PREFIX|]products p ON p.productid = ca.productid
				LEFT JOIN [|PREFIX|]brands b ON b.brandid = p.prodbrandid
				LEFT JOIN [|PREFIX|]product_images pi ON (pi.imageisthumb = 1 AND p.productid = pi.imageprodid)
			ORDER BY
				p.productid
			LIMIT
			";

		if ($offset) {
			$listQuery .= $offset . ', ';
		}

		$listQuery .= $limit;

		return $listQuery;
	}

	/**
	* Adds custom and configurable field data to the supplied product row
	*
	* @param array $productRow Array of product table data
	*/
	public static function addCustomAndConfigurableFields(&$productRow)
	{
		//$productRow['configurable_fields'] = array();
		$productRow['custom_fields'] = array();

		/*
		// add configurable fields to the product
		$query = "
			SELECT
				*
			FROM
				[|PREFIX|]product_configurable_fields
			WHERE
				fieldprodid = " . $productRow['productid'] . "
		";

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($configRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$productRow['configurable_fields'][] = $configRow;
		}
		*/

		// add custom fields to the product
		$query = "
			SELECT
				*
			FROM
				[|PREFIX|]product_customfields
			WHERE
				fieldprodid = " . $productRow['productid'] . "
		";

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($customRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$productRow['custom_fields'][] = $customRow;
		}
	}

	/**
	* Verifies an item listing with eBay.
	* Used to determine fees before performing an actual live listing.
	*
	* @param array $product An array of product row data
	* @param ISC_ADMIN_EBAY_TEMPLATE $template The template to list the product with
	* @return ISC_ADMIN_EBAY_LIST_ITEM_RESULT The result object for the new listing
	*/
	public static function verifyListItem($product, $template)
	{
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($template->getSiteId());

		$xml = ISC_ADMIN_EBAY_OPERATIONS::verifyAddItem($product, $template);

		return new ISC_ADMIN_EBAY_LIST_ITEM_RESULT($product, $xml);
	}

	/**
	* Lists an item with eBay
	*
	* @param array $product An array of product row data
	* @param ISC_ADMIN_EBAY_TEMPLATE $template The template to list the product with
	* @return ISC_ADMIN_EBAY_LIST_ITEM_RESULT The result object for the new listing
	*/
	public static function listItem($product, $template)
	{
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($template->getSiteId());

		$xml = ISC_ADMIN_EBAY_OPERATIONS::addItem($product, $template);

		return new ISC_ADMIN_EBAY_LIST_ITEM_RESULT($product, $xml);
	}

	/**
	* Lists up to 5 items with eBay
	*
	* @param array $products An array of products to list
	* @param ISC_ADMIN_EBAY_TEMPLATE $template The template to list the product with
	* @return array Array of ISC_ADMIN_EBAY_LIST_ITEM_RESULT objects for each item listed
	*/
	public static function listItems($products, $template)
	{
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($template->getSiteId());

		$results = array();
		$regularProducts = array();

		// any products with a variation should be listed separately with a FixedPriceItem request
		foreach ($products as $productId => $product) {
			if ($product['prodvariationid']) {
				try {
					$xml = ISC_ADMIN_EBAY_OPERATIONS::addFixedPriceItem($product, $template);
				}
				catch (ISC_EBAY_API_REQUEST_EXCEPTION $ex) {
					$xml = $ex->getResponseXML();
				}

				$results[] = new ISC_ADMIN_EBAY_LIST_ITEM_RESULT($product, $xml);
			}
			else {
				$regularProducts[$productId] = $product;
			}
		}

		// process the remainder of the items
		if (!empty($regularProducts)) {
			try {
				$xml = ISC_ADMIN_EBAY_OPERATIONS::addItems($regularProducts, $template);
			}
			catch (ISC_EBAY_API_REQUEST_EXCEPTION $ex) {
				$xml = $ex->getResponseXML();

				// if we have item level data then we want to process the results to get any errors for each item
				// otherwise, throw the exception and it will get caught in the job as a per-request level error
				if (!isset($xml->AddItemResponseContainer)) {
					throw $ex;
				}
			}

			// process each add item response
			foreach ($xml->AddItemResponseContainer as $item) {
				$productId = (int)$item->CorrelationID;
				$results[] = new ISC_ADMIN_EBAY_LIST_ITEM_RESULT($regularProducts[$productId], $item);
			}
		}

		return $results;
	}
}

/**
* This class processes the XML result for an item listing request.
*/
class ISC_ADMIN_EBAY_LIST_ITEM_RESULT {
	private $_productData;

	private $_itemID;

	private $_startTime;

	private $_startTimeISO;

	private $_endTime;

	private $_endTimeISO;

	private $_primaryCategoryID = null;

	private $_secondaryCategoryID = null;

	private $_fees;

	private $_errors = array();

	private $_isValid = true;

	/**
	* @param array The associated product data for the item
	* @param SimpleXMLElement $xml
	* @return ISC_ADMIN_EBAY_LIST_ITEM_RESULT
	*/
	public function __construct($productData, $xml)
	{
		$this->_productData = $productData;

		// errors will only be available for multi-item listings
		if (isset($xml->Errors)) {
			foreach ($xml->Errors as $error) {
				if ((string)$error->SeverityCode == 'Error') {
					$this->_isValid = false;
				}

				$this->setError(isc_html_escape((string)$error->LongMessage) . ' (' . (string)$error->ErrorCode . ')');
			}
		}

		if (!$this->_isValid) {
			return;
		}

		if (!isset($xml->ItemID)) {
			$this->setError('Missing ItemID.');
			$this->_isValid = false;
			return;
		}

		// can't use (int) cast as eBay uses 64 bit ints
		$this->_itemID = (string)$xml->ItemID;

		// date-times are returned in ISO 8601
		// @TODO check how timezone is parsed
		$this->_startTime = strtotime((string)$xml->StartTime);
		$this->_startTimeISO = (string)$xml->StartTime;

		$this->_endTime = strtotime((string)$xml->EndTime);
		$this->_endTimeISO = (string)$xml->EndTime;

		// procees the fees
		$fees = array();
		foreach ($xml->Fees->Fee as $fee) {
			$attr = $fee->Fee->attributes();

			$fees[] = array(
				'name'		=> (string)$fee->Name,
				'fee'		=> (double)$fee->Fee,
				'currency'	=> (string)$attr['currencyID']
			);
		}

		// only set if Item.CategoryMappingAllowed is true and the category was mapped to a new category
		if (isset($xml->CategoryID)) {
			$this->_primaryCategoryID = (int)$xml->CategoryID;
		}

		if (isset($xml->Category2ID)) {
			$this->_secondaryCategoryID = (int)$xml->Category2ID;
		}

		$this->_fees = $fees;
	}

	/**
	* Gets the ID of the associated product for the listing
	*
	* @return int The product ID
	*/
	public function getProductId()
	{
		return $this->_productData['productid'];
	}

	/**
	* Gets the name of the associated product for the listing
	*
	* @return string The product name
	*/
	public function getProductName()
	{
		return $this->_productData['prodname'];
	}

	/**
	* Gets the associated product data for the listing
	*
	* @return array The product data
	*/
	public function getProductData()
	{
		return $this->_productData;
	}

	/**
	* Gets the price of the associated product
	*
	* @return double The product price
	*/
	public function getProductPrice()
	{
		return $this->_productData['prodprice'];
	}

	/**
	* Gets the unique item ID for the new listing
	*
	* @return int The item ID
	*/
	public function getItemId()
	{
		return $this->_itemID;
	}

	/**
	* Gets the Unix timestamp starting date and time for the listing
	*
	* @return int The starting time
	*/
	public function getStartTime()
	{
		return $this->_startTime;
	}

	/**
	* Gets the ISO 8601 starting date and time for the listing
	*
	* @return int The starting time
	*/
	public function getStartTimeISO()
	{
		return $this->_startTimeISO;
	}

	/**
	* Gets the Unix timestamp ending date and time for the listing
	*
	* @return int The ending time
	*/
	public function getEndTime()
	{
		return $this->_endTime;
	}

	/**
	* Gets the ISO 8601 ending date and time for the listing
	*
	* @return int The ending time
	*/
	public function getEndTimeISO()
	{
		return $this->_endTimeISO;
	}

	/**
	* Gets an array of fees for the new listing in the following format:
	*
	* array(
	*	'name' 		=> The name of the fee
	* 	'fee'		=> The amount of the fee
	* 	'currency'	=> The currency code of the fee
	* )
	*
	* @return array The array of fees
	*/
	public function getFees()
	{
		return $this->_fees;
	}

	/**
	* Gets the ID of the primary category in which the category was listed.
	* Only set if CategoryMappingAllowed was set to true and the ID passed was mapped to a new category ID.
	*
	* @return int The ID of the new category or null otherwise
	*/
	public function getPrimaryCategoryId()
	{
		return $this->_primaryCategoryID;
	}

	/**
	* Gets the ID of the secondary category in which the category was listed.
	* Only set if CategoryMappingAllowed was set to true and the ID passed was mapped to a new category ID.
	*
	* @return int The ID of the new category or null otherwise
	*/
	public function getSecondaryCategoryId()
	{
		return $this->_secondaryCategoryID;
	}

	/**
	* Sets an error
	*
	* @param strin $error The error message to store
	*/
	private function setError($error)
	{
		$this->_errors[] = $error;
	}

	/**
	* Checks if this result has errors
	*
	* @return bool True if the result has errors, false otherwise
	*/
	public function hasErrors()
	{
		return !empty($this->_errors);
	}

	/**
	* Gets the array of errors
	*
	* @return array The errors array
	*/
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	* Checks if the listing was valid.
	* Errors from warnings may still be set even if the listing was successfull.
	*
	* @return bool True for a successfull listing, false otherwise
	*/
	public function isValid()
	{
		return $this->_isValid;
	}
}