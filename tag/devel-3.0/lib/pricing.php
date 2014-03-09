<?php

if (!defined('ISC_BASE_PATH')) {
	die(" ");
}

require_once(ISC_BASE_PATH."/lib/currency.php");

/**
 * Return a formatted price for a product for display on product catalogs.
 * Catalogs are defined as lists of more than one product (such as the contents
 * of a category.
 *
 * @see formatProductPrice
 * @param array $product Array containing the product to format the price for.
 * @param array $options Array of options, passed to formatProductPrice.
 * @return string Generated HTML to display the price for the product.
 */
function formatProductCatalogPrice($product, array $options = array())
{
	$displayFormat = getConfig('taxDefaultTaxDisplayCatalog');
	$options['displayInclusive'] = $displayFormat;

	if($displayFormat != TAX_PRICES_DISPLAY_BOTH) {
		return formatProductPrice($product, $product['prodcalculatedprice'], $options);
	}

	$options['displayInclusive'] = TAX_PRICES_DISPLAY_INCLUSIVE;
	$priceIncTax = formatProductPrice($product, $product['prodcalculatedprice'], $options);

	$options['displayInclusive'] = TAX_PRICES_DISPLAY_EXCLUSIVE;
	$priceExTax = formatProductPrice($product, $product['prodcalculatedprice'], $options);

	$output = '<span class="CatalogPriceIncTax">';
	$output .= $priceIncTax;
	$output .= getLang('CatalogPriceIncTaxLabel', array(
		'label' => getConfig('taxLabel')
	));
	$output .= '</span> ';
	$output .= '<span class="CatalogPriceExTax">';
	$output .= $priceExTax;
	$output .= getLang('CatalogPriceExTaxLabel', array(
		'label' => getConfig('taxLabel')
	));
	$output  .= '</span>';
	return $output;
}

/**
 * Return a formatted price for a product for display on product detail pages.
 * Detail pages are defined as those product pages which contain the primary
 * details for a product.
 *
 * @see formatProductPrice
 * @param array $product Array containing the product to format the price for.
 * @param array $options Array of options, passed to formatProductPrice
 * @return string Generated HTML to display the price for the product.
 */
function formatProductDetailsPrice($product, array $options = array())
{
	$displayFormat = getConfig('taxDefaultTaxDisplayProducts');
	$options['displayInclusive'] = $displayFormat;

	if($displayFormat != TAX_PRICES_DISPLAY_BOTH) {
		return formatProductPrice($product, $product['prodcalculatedprice'], $options);
	}

	$options['displayInclusive'] = TAX_PRICES_DISPLAY_INCLUSIVE;
	$priceIncTax = formatProductPrice($product, $product['prodcalculatedprice'], $options);

	$options['displayInclusive'] = TAX_PRICES_DISPLAY_EXCLUSIVE;
	$priceExTax = formatProductPrice($product, $product['prodcalculatedprice'], $options);

	$output = '<span class="ProductDetailsPriceIncTax">';
	$output .= $priceIncTax;
	$output .= getLang('ProductDetailsPriceIncTaxLabel', array(
		'label' => getConfig('taxLabel')
	));
	$output .= '</span> ';
	$output .= '<span class="ProductDetailsPriceExTax">';
	$output .= $priceExTax;
	$output .= getLang('ProductDetailsPriceExTaxLabel', array(
		'label' => getConfig('taxLabel')
	));
	$output  .= '</span>';
	return $output;
}

/**
 * Generate and calculate a formatted price for a product. This function will
 * take in a product and a price, apply tax where necessary, convert it to
 * the displayed currency, format it, and if there's a retail price for the
 * product, show it struck out.
 *
 * Options passed as $options include:
 * - currencyConvert (true) - Convert the price in to the active currency
 * - strikeRetail (true) - If there is an RRP, strike it out & show before the product
 * - displayInclusive (false) - Set to true if the returned price should include tax
 * - includesTax (null) - Set to true if $price already includes tax
 * - localeFormat (true) - Perform any locale formatting (formatPrice)
 *
 * @param array $product Array containing the product to format the price for.
 * @param double $price Price of the product to be formatted.
 * @param array $options Array of options for formatting the price.
 */
function formatProductPrice($product, $price, array $options = array())
{
	$defaultOptions = array(
		'currencyConvert' => true,
		'strikeRetail' => true,
		'displayInclusive' => false,
		'includesTax' => null,
		'localeFormat' => true
	);
	$options = array_merge($defaultOptions, $options);

	$actualPrice = calculateFinalProductPrice($product, $price, $options);

	// Apply taxes to the price
	$actualPrice = getClass('ISC_TAX')->getPrice(
		$actualPrice,
		$product['tax_class_id'],
		$options['displayInclusive']
	);

	// Convert to the current currency
	if($options['currencyConvert']) {
		$actualPrice = convertPriceToCurrency($actualPrice);
	}

	$output = '';

	if(!$options['localeFormat']) {
		return $actualPrice;
	}

	if($product['prodretailprice'] > 0 && $options['strikeRetail'] && $product['prodretailprice'] > $actualPrice ) {
		$rrp = calculateFinalProductPrice($product, $product['prodretailprice']);
		$rrp = getClass('ISC_TAX')->getPrice(
			$rrp,
			$product['tax_class_id'],
			$options['displayInclusive']
		);
		$rrp = convertPriceToCurrency($rrp);

		$output .= '<strike class="RetailPriceValue">'.formatPrice($rrp).'</strike> ';
	}

	if($product['prodsaleprice'] > 0 && $product['prodsaleprice'] < $product['prodprice']) {
		$output .= '<br class="Clear"><span class="SalePrice">'.formatPrice($actualPrice).'</span>';
	}
	else {
		$output .= formatPrice($actualPrice);
	}

	return $output;
}

/**
 * Given the price of a product ($basePrice), calculate the
 * final price of the product when the following price adjustments are made:
 *
 * 1. Variation modifier price modifiers are applied.
 * 2. Quantity discounts are applied.
 * 3. Customer group discounts are applied.
 *
 * The supplied arguments determine which price modification calls should
 * be run.
 *
 * $options is an associative array, for which the following can be supplied:
 * - quantity - Calculate the price (per quantity) this item should be at this
 * quantity. Pass as 0 to ignore.
 * - variationModifier - Variation modifier if any (fixed, add, subtract)
 * - variationAdjustment - Amount price should be adjusted with above modifier.
 * - customerGroup - Customer group to calculate the price for. By default, uses
 * the group of the current logged in customer. Set to 0 to disable.
 *
 * @param array $product Array of details about the product.
 * @param float $basePrice Base price to use for calculations - excluding tax.
 * @param array $options Array of options (see declaration for details)
 * @return float Calculated product price, excluding tax.
 */
function calculateFinalProductPrice($product, $basePrice, array $options = array())
{
	$newPrice = $basePrice;
	$defaultOptions = array(
		'quantity'		=> 0,
		'variationModifier'	=> '',
		'variationAdjustment'	=> 0,
		'customerGroup'		=> null
	);
	$options = array_merge($defaultOptions, $options);

	// Calculate the price for the variation first
	if($options['variationModifier'] !== '') {
		$adjustment = $options['variationAdjustment'];
		$newPrice = calcProductVariationPrice($newPrice, $options['variationModifier'], $adjustment);
	}

	// Assuming this product isn't a variation, calculate the quantity discount
	// next.
	if($options['variationModifier'] === '' && $options['quantity'] > 0) {
		$newPrice = calculateQuantityDiscount($product['productid'], $newPrice, $options['quantity']);
	}

	// Apply the customer group discount at the end
	if($options['customerGroup'] !== 0) {
		$newPrice = calcProdCustomerGroupPrice($product, $newPrice, $options['customerGroup']);
	}

	return $newPrice;
}

/**
 * Determine the group based price for a particular product.
 *
 * note: product level > category level > storewide
 *
 * @param array Array of information for the product.
 * @param string The price we want to fetch the adjusted value for.
 * @param int The group id to fetch the pricing for. If none specified, the current customers group is used.
 */
function CalcProdCustomerGroupPrice($product, $price, $groupId=null)
{

	// If the group is not passed, get the group for the current customer
	$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
	if($groupId === null && !defined('ISC_ADMIN_CP')) {
		$group = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup();
	}
	else {
		$group = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup($groupId);
	}

	// If here isn't a customer group then we just return the price we already had
	if(!is_array($group)) {
		return $price;
	}

	// ISC-708: attempt to retrieve product level group discount (now that group id is known)
	if (!isset($product['prodgroupdiscount']) || $product['prodgroupdiscount'] == 0) {
		$query = "
			SELECT p.*, ".GetProdCustomerGroupPriceSQL($group['customergroupid'])."
			FROM [|PREFIX|]products p
			WHERE p.productid='".(int)$product['productid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$product = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	}

	// Does this product have a custom price?
	if(isset($product['prodgroupdiscount']) && $product['prodgroupdiscount'] > 0) {
		return CalculateDiscount($price,$product['discountmethod'], $product['prodgroupdiscount']);
	}

	// Do we have a group price for any of the categories this product is in?
	$categories = explode(',', $product['prodcatids']);
	$discountPrice = $price;

	foreach($categories as $category) {
		$catDiscount = GetGroupCategoryDiscount($group['customergroupid'], $category);

		if(isset($catDiscount['discountAmount'])) {
			$currentDiscountPrice = CalculateDiscount($price, $catDiscount['discountMethod'], $catDiscount['discountAmount']);

			//get the lowest discount for the product
			$discountPrice = min($discountPrice, $currentDiscountPrice);
		}
	}

	if($discountPrice < $price) {
		return $discountPrice;
	}

	// Otherwise, if the group has a default discount then we use it
	if($group['discount'] > 0) {
		$discountPrice = CalculateDiscount($price, $group['discountmethod'], $group['discount']);

		return $discountPrice;
	}

	return $price;
}

function CalculateDiscount($price, $discountMethod, $discount)
{
	//percentage discount
	if($discountMethod=='percent') {
		$price -= $price * ($discount / 100);
	}

	//price discount
	elseif($discountMethod=='price') {
		$price = $price - $discount;

	//fix price discount
	} elseif($discountMethod=='fixed') {
		$price = $discount;
	}

	if($price < 0) {
		$price = 0;
	}

	return $price;
}


function CalculateCustGroupDiscount($productId, $price, $custGroup=null)
{
	static $products = array();

	//apply customer group discount on top of the quantity discount
	if(!isset($products[$productId])) {
		$query = "
			SELECT p.*, ".GetProdCustomerGroupPriceSQL()."
			FROM [|PREFIX|]products p
			WHERE p.productid='".(int)$productId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$products[$productId] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	}

	$price = CalcProdCustomerGroupPrice($products[$productId], $price, $custGroup);
	return $price;
}

/**
 * Fetch the discount percentage for a particular customer group in a specific category. This will read
 * the data from the data store based on the passed group id.
 *
 * @param int The group ID to fetch the discount for.
 * @param int The category ID to fetch the discount for this group of.
 * @return string The discount that applies to this group.
 */
function GetGroupCategoryDiscount($groupId, $categoryId)
{
	$customerGroup = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup($groupId);

	// If the group doesn't exist, it's a 0% discount of course!
	if(!isset($customerGroup['customergroupid'])) {
		return 0;
	}

	$groupCacheData = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('CustomerGroupsCategoryDiscounts', false, $groupId);
	if(isset($groupCacheData[$categoryId])) {
		return $groupCacheData[$categoryId];
	}
	else {
		return 0;
	}
}
/**
 * Build a portion of the SQL query to be used in product queries to fetch out any pricing information for the each
 * product with the group that the current customer is in.
 *
 * @param mixed The group ID to build the SQL for. If null, the current customers group will be used. Otherwise, a group ID.
 * @param string The alias for the products table in the query. Defaults to 'p', but can be changed to whatever the query uses.
 * @return string The SQL to fetch the product pricing for this group.
 */
function GetProdCustomerGroupPriceSQL($groupId=null, $prodTable = 'p')
{
	// If the group is not passed, get the group for the current customer
	$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
	if($groupId === 0) {
		return '0 AS prodgroupdiscount';
	}
	if($groupId === null && !defined('ISC_ADMIN_CP')) {
		$group = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup();
	}
	else {
		$group = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup($groupId);
	}

	// If here isn't a customer group then we just return a simple query that returns 0 for the price
	if(!is_array($group)) {
		return '0 AS prodgroupdiscount';
	}

	else {
		return "(SELECT discountpercent FROM [|PREFIX|]customer_group_discounts disc WHERE disc.discounttype='PRODUCT' AND disc.customergroupid='".$group['customergroupid']."' AND disc.catorprodid=".$prodTable.".productid) AS prodgroupdiscount, (SELECT discountmethod FROM [|PREFIX|]customer_group_discounts disc WHERE disc.discounttype='PRODUCT' AND disc.customergroupid='".$group['customergroupid']."' AND disc.catorprodid=".$prodTable.".productid) AS discountmethod";
	}
}

/**
 * Generate an INNER JOIN SQL statement to be used to ensure a customer can only view products
 * in the categories they have permission to view.
 *
 * @param int The group ID to fetch the permissions SQL for. If null, the current group of the customer is used.
 * @return string The SQL to be appended to product queries to determine the list of viewable products.
 */
function GetProdCustomerGroupPermissionsSQL($groupId=null, $PrependAdd=true)
{
	// If the group is not passed, get the group for the current customer
	$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
	if($groupId == null) {
		$group = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup();
	}
	else {
		$group = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup($groupId);
	}

	// If here isn't a customer group then they have access
	if(!is_array($group)) {
		return '';
	}
	else if($group['categoryaccesstype'] == 'all') {
		return '';
	}
	else if ($group['categoryaccesstype'] == "none") {
		$categories[] = 0;
	}
	else {
		$categories = $group['accesscategories'];
	}

	$query = '(SELECT caperm.productid
				FROM  [|PREFIX|]categoryassociations caperm
				WHERE caperm.productid = p.productid AND caperm.categoryid IN (' . implode(",", $categories) . ')
				LIMIT 1)';

	if($PrependAdd) {
		return ' AND '.$query;
	} else {
		return $query;
	}
}

/**
 * Check if a customer group has access to a particular category.
 *
 * @param int The ID of the category to check the permissions for.
 * @param int The group ID to check for the category permissions. If null, the current customers group is used.
 * @return boolean True if the customer has access to the particular category, false if not.
 */
function CustomerGroupHasAccessToCategory($categoryId, $groupId=null)
{
	// If the group is not passed, get the group for the current customer
	$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
	if($groupId == null) {
		$group = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup();
	}
	else {
		$group = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerGroup($groupId);
	}

	// If here isn't a customer group then they have access
	if(!is_array($group)) {
		return true;
	}

	switch ($group['categoryaccesstype']) {
		case "all":
			return true;
		case "specific":
			if (in_array($categoryId, $group['accesscategories'])) {
				return true;
			}
	}

	return false;
}

/**
 * Convert and format a price
 *
 * Function will convert and format a price. Function is a wrapper for FormatPrice and FormatCurrency
 *
 * @access public
 * @param float $price The price to convert and format
 * @param array $currency The currency record array. Default is the one stored within the currency session
 * @return string The converted and formatted price
 */
function CurrencyConvertFormatPrice($price, $currency=null, $exchangeRate=null, $includeCurrencyCode=false)
{
	$price = ConvertPriceToCurrency($price, $currency, $exchangeRate, null);
	return FormatPrice($price, false, true, false, $currency, $includeCurrencyCode);
}

/**
 * Format the price
 *
 * Function will format the price based on the currency record that is provided. The default currency record will be the
 * one stored in the current session
 *
 * @access public
 * @param float $price The price to format
 * @param array $currency The currency record. Default is the one stored within the currency session
 * @return string The formatted price
 */
function FormatPrice($price, $strip_decimals=false, $add_token=true, $strip_thousandsep=false, $currency=null, $includeCurrencyCode=false)
{
	// Because we're going to have prices come as floats, we're likely to have
	// precision issues. Round everything to 4 decimal places before formatting
	// a price, because that's the max internally storable in the DB anyway.
	// Better yet would be to store prices in cents, rather than dollars but
	// this is not feasible at the moment.
	$price = round($price, 4);

	if (is_null($currency)) {
		if(!isset($GLOBALS['CurrentCurrency'])) {
			$defaultCurrency = GetDefaultCurrency();
			$GLOBALS['CurrentCurrency'] = $defaultCurrency['currencyid'];
		}
		$currency = GetCurrencyById($GLOBALS['CurrentCurrency']);
	}

	if(!is_array($currency)) {
		$currency = GetCurrencyById($currency);
	}

	if(!isset($currency['currencyid'])) {
		$currency = GetDefaultCurrency();
	}

	if ($strip_thousandsep) {
		$currency['currencythousandstring'] = '';
	}

	$negative = false;
	if($price < 0) {
		$negative = true;
		$price = substr($price, 1);
	}

	$num = number_format($price, $currency['currencydecimalplace'], $currency['currencydecimalstring'], $currency['currencythousandstring']);
	// Do we strip decimal places? If so just return the whole number portion
	if ($strip_decimals) {
		$tmp = explode($currency['currencydecimalstring'], $num);
		$num = $tmp[0];
	}

	if ($add_token) {
		if (strtolower($currency['currencystringposition']) == "left") {
			$num = $currency['currencystring'] . $num;
		}
		else {
			$num = $num . $currency['currencystring'];
		}
	}

	if($includeCurrencyCode == true) {
		$num .= ' '.$currency['currencycode'];
	}

	if($negative) {
		$num = '-'.$num;
	}

	return $num;
}

/**
 * Calculate the price adjustment for a variation of a product.
 *
 * @var decimal The base price of the product.
 * @var string The type of adjustment to be performed (empty, add, subtract, fixed)
 * @var decimal The value to be adjusted by
 * @return decimal The adjusted value
 */
function CalcProductVariationPrice($basePrice, $type, $difference, $product=null)
{
	switch (strtolower($type)) {
		case "fixed":
			$newPrice = $difference;
			break;
		case "add":
			$newPrice = $basePrice + $difference;
			break;
		case "subtract":
			$adjustedCost = $basePrice - $difference;
			if($adjustedCost <= 0) {
				$adjustedCost = 0;
			}
			$newPrice = $adjustedCost;
			break;
		default:
			$newPrice =$basePrice;
	}

	if(!is_null($product)) {
		$newPrice = CalcProdCustomerGroupPrice($product, $newPrice);
	}

	// apply any tax if applicable.
	$newPrice = CalcRealPrice($newPrice, 0, 0);

	return $newPrice;
}

function CalcRealPrice($price, $salePrice)
{
	// Calculate the real price for this product based sale price, etc
	if($salePrice > 0) {
		$price = $salePrice;
	}

	return $price;
}

/**
 * Convert a localized price (such as 45,99) to the standard western format of 45.99 for storage in the database
 *
 * @param Float $Price The price to convert
 * @param boolean $numberFormat Set to false to not call a number_format() on the resulting price.
 * @return Float
*/
function DefaultPriceFormat($Val, $numberFormat=null, $allowNegative=null)
{
	if ($numberFormat === null) {
		$numberFormat = true;
	}

	if ($allowNegative === null) {
		$allowNegative = false;
	}

	$regex = "#[^0-9";
	if ($allowNegative) {
		$regex .= '\-';
	}
	$regex .= "\\" . GetConfig('DecimalToken');
	$regex .= "\\" . GetConfig('ThousandsToken');
	$regex .= "]+#i";

	$Val = preg_replace($regex, "", $Val);
	$Val = str_replace(GetConfig('ThousandsToken'), "", $Val);
	$Val = str_replace(GetConfig('DecimalToken'), ".", $Val);
	$Val = doubleval($Val);
	if($numberFormat) {
		$Val = number_format($Val, GetConfig('DecimalPlaces'), ".", "");
	}
	return $Val;
}

function CPrice($Val)
{
	$val = CFloat($Val);
	$val = number_format($val, GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
	return $val;
}

/**
 * Get a bulk discount record by quantity
 *
 * Function will return a bulk discount record based upon the quantity
 *
 * @access public
 * @param int $productId The product ID
 * @param int $quantity The quantity amount of products purchased
 * @return array The bulk discount record on success, FALSE otherwise
 */
function GetBulkDiscountByQuantity($productId, $quantity)
{
	// Check to see if we are set to do this
	if (!GetConfig('BulkDiscountEnabled')) {
		return false;
	}

	$discounts = array();
	$query = "
		SELECT *
		FROM [|PREFIX|]product_discounts
		WHERE discountprodid='".(int)$productId."'
		ORDER BY IF(discountquantitymax = 0, discountquantitymin, discountquantitymax)
	";
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	while($discount = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
		$discounts[] = $discount;
	}

	if (empty($discounts)) {
		return false;
	}

	// Are we in one of our discount ranges? Firstly we need to fill in the blanks due to '0' being wildcards
	for ($i=0; $i<count($discounts); $i++) {

		// Check for the min quantity
		if ($discounts[$i]['discountquantitymin'] == 0) {
			if ($i > 0) {
				$discounts[$i]['discountquantitymin'] = $discounts[$i-1]['discountquantitymax']+1;
			} else {
				$discounts[$i]['discountquantitymin'] = 0;
			}
		}

		// Check for the max quantity
		if ($discounts[$i]['discountquantitymax'] == 0) {
			for ($j=$i+1; $j<count($discounts); $j++) {
				if ($discounts[$j]['discountquantitymin'] > 0) {
					$discounts[$i]['discountquantitymax'] = $discounts[$j]['discountquantitymin']-1;
					break;
				}
				if ($discounts[$j]['discountquantitymax'] > 0) {
					$discounts[$i]['discountquantitymax'] = $discounts[$j]['discountquantitymax']-1;
					break;
				}
			}

			// If we couldn't find any either then invent the unlimited number or assign -1
			if ($discounts[$i]['discountquantitymax'] == 0) {
				$discounts[$i]['discountquantitymax'] = -1;
			}
		}
	}

	// OK we have our filtered ranges, now we see if our quantity is in there
	foreach ($discounts as $discount) {
		if ($quantity >= $discount['discountquantitymin'] && ($quantity <= $discount['discountquantitymax'] || $discount['discountquantitymax'] == -1)) {
			return $discount;
		}
	}

	return false;
}

/**
 * Calculate a quantity/bulk discount tier for a price based on the passed product price
 * and discount details.
 *
 * @param int $productId ID of the product.
 * @param float $price The price we're adjusting the quantity discount for.
 * @param int $quantity Quantity to calculate tier discount for.
 * @return The adjusted price for the product based on the tier pricing, if there is any.
 */
function calculateQuantityDiscount($productId, $price, $quantity)
{
	$discount = getBulkDiscountByQuantity($productId, $quantity);
	if(!is_array($discount)) {
		return $price;
	}

	switch ($discount['discounttype']) {
		case 'price':
			$price -= (float)$discount['discountamount'];
			break;

		case 'percent':
			$price -= (((int)$discount['discountamount'] / 100) * $price);
			break;

		case 'fixed':
			$price = $discount['discountamount'];
			break;
	}

	if ($price < 0) {
		$price = 0;
	}

	return $price;
}
