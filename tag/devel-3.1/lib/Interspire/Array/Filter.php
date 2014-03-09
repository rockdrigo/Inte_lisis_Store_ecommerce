<?php
/**
 * Interspire_Array_Filter class file.
 */

/**
 * Interspire_Array_Filter is a utility class for modifying values in
 * an array using callback functions. @todo: documentation + refactor
 * filter methods into separate objects to be selectively included
 * as needed. eg
 * DefaultFilters (capitalize, nohtml, maxlen),
 * BizrateFilters (bizcatname, bizcatid),
 * ShopcomFilters (shopcom)
 *
 * Usage:
 *
 * $values = array(
 *     'title' => array(
 *       $title,
 *     	 array('nohtml', 'capitalize', 'maxlength' => 100)
 *     ),
 *
 *     'description' => array(
 *       $description,
 *       array('nohtml', 'maxlength' => 1000)
 *     ),
 * );
 *
 * $filter = new Interspire_Array_Filter();
 * $processedValues = $filter->process($values);
 */
class Interspire_Array_Filter
{
	public function __construct()
	{
	}

	/**
	 * Pricegrabber Categorization field filter
	 *
	 * @param array Shopping comparison product export row
	 */
	public function pgcatFilter($row, $args=null)
	{
		if(!empty($row['shopping_comparison_category_id']))
		{
			$category = $row['shopping_comparison_category_name'];

			if(!empty($row['shopping_comparison_category_path']))
				$category = $row['shopping_comparison_category_path'] . ' > ' . $category;
		}
		else
		{
			$category = $this->nohtmlFilter($row['catname']);
			$category = $this->maxlenFilter($category, 100);
		}

		$category = $this->csvFilter($category);

		return $category;
	}

	public function nextagcatFilter($row, $args=null)
	{
		if(!empty($row['shopping_comparison_category_id']))
		{
			$category = $row['shopping_comparison_category_id']. ' : ';

			if(!empty($row['shopping_comparison_category_path']))
				$category .= str_replace(">", "/", $row['shopping_comparison_category_path']) . ' / ';

			$category .= $row['shopping_comparison_category_name'];
		}
		else
		{
			$category = $this->nohtmlFilter($row['catname']);
			$category = $this->maxlenFilter($category, 100);
		}

		$category = $this->csvFilter($category);

		return $category;
	}

	public function shopcomcatidFilter($row, $args=null)
	{
		if(!empty($row['shopping_comparison_category_id']))
			return $row['shopping_comparison_category_id'];

		return null;
	}

	public function shopcomcatnameFilter($row, $args=null)
	{
		if(!empty($row['shopping_comparison_category_name']))
		{
			$category = $row['shopping_comparison_category_name'];

			if(!empty($row['shopping_comparison_category_path']))
				$category = $row['shopping_comparison_category_path'] . ' > ' . $category;
		}
		else
		{
			$category = $this->nohtmlFilter($row['catname']);
			$category = $this->maxlenFilter($category, 100);
		}

		$category = $this->csvFilter($category);

		return $category;
	}

	public function bizcatFilter($row, $args=null)
	{
		if(!empty($row['shopping_comparison_category_id']))
			return $row['shopping_comparison_category_id'];

		$category = $this->nohtmlFilter($row['catname']);
		$category = $this->maxlenFilter($category, 100);
		$category = $this->csvFilter($category);

		return $category;
	}

	public function nohtmlFilter($v, $args=null)
	{
		$v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');

		// Replace numeric entities, as html_entity_decode
		// seems to be missing some eg: &#8217 and &#8212.
		// Following snippet from PHP Docs for html-entity-decode
		//$v = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $v);
	    //$v = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $v);
		return strip_tags($v);
	}

	public function maxlenFilter($v, $max)
	{
		return isc_substr($v, 0, $max);
	}

	public function defaultFilter($v, $defaultValue)
	{
		if($v!==null && $v!=="")
			return $v;

		return $defaultValue;
	}

	public function lowerFilter($string, $args)
	{
		return isc_strtolower($string);
	}

	public function ucfirstFilter($string, $args)
	{
		return ucfirst($string);
	}

	public function prodavailabilityFilter($row, $args)
	{
		if($row['prodinvtrack'] && $row['prodcurrentinv'] <= 0)
			return 'No';

		return 'Yes';
	}

	public function csvFilter($v, $args=null)
	{
		$v = preg_replace("/[\n\r]/", " ", $v);
		$v = str_replace('"', '""', $v);
		return '"'.$v.'"';
	}

	/**
	 * Escapes a value using the Shopzilla tab separated (tsv)
	 * format. Special notes: Shopzilla does not support quote
	 * wrappers, so tabs must be stripped. Double quote escaping
	 * is also not supported, but we do it anyway as it is
	 * required for the columns to line up correctly in Excel.
	 *
	 * @param string value to be tsv formatted.
	 *
	 * @return string the formatted value.
	 */
	public function bizratetsvFilter($v, $args=null)
	{
		$v = preg_replace("/[\n\r\t]/"," ", $v);
		$v = str_replace('"', '""', $v);
		return '"'.$v.'"';
	}

	/**
	 * Returns a product details url.
	 *
	 * @param string the product name
	 *
	 * @return string product details page url.
	 */
	public function prodlinkFilter($name, $args=null)
	{
		return ProdLink($name);
	}

	/**
	 * Returns the product sale price if the product is
	 * on sale, otherwise returns the standard product
	 * price.
	 */
	public function calcpriceFilter($product, $args=null)
	{
		$price = getClass('ISC_TAX')->getPrice(
			$product['prodcalculatedprice'],
			$product['tax_class_id'],
			getConfig('taxDefaultTaxDisplayProducts'));

		return $price;
	}

	/**
	 * Returns the product shipping cost if free shipping
	 * is set, or if prodfixedshipping cost is greater
	 * than 0.
	 */
	public function shippingcostFilter($row)
	{
		if($row['prodfreeshipping'])
			return 0.0;

		if(!empty($row['prodfixedshippingcost']))
			return $row['prodfixedshippingcost'];

		return null;
	}

	public function weightFilter($row, $args)
	{
		$weight = $row['prodweight'];
		if(!empty($args['format']))
			return FormatWeight($weight, false);
		else
			return $row['prodweight'];
	}

	public function imagelinkFilter($v, $args=null)
	{
		if(!$v['imagefile'])
			return null;

		$image = new ISC_PRODUCT_IMAGE();
		$image->populateFromDatabaseRow($v);

		try {
			$url = $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, true, false);
			return $url;
		}
		catch(Exception $e)
		{
			return null;
		}
	}

	public function process($values)
	{
		$processedValues = array();
		foreach($values as $field => $valueFilterPair) {
			if(is_array($valueFilterPair)) {
				$v = $valueFilterPair[0];
				$filters = $valueFilterPair[1];
			}
			else
			{
				$v = $valueFilterPair;
				$filters = null;
			}

			if(is_array($filters)) {
				foreach($filters as $filter => $args) {
					if(is_int($filter)) {
						$filter = $args;
						$args = null;
					}

					$filterCall = $filter.'Filter';
					$v = $this->{$filterCall}($v, $args);
				}
			}

			$processedValues[$field] = $v;
		}

		return $processedValues;
	}
}
