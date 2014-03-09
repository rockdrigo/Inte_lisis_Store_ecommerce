<?php

class SHOPPINGCOMPARISON_PRICEGRABBER extends ISC_SHOPPINGCOMPARISON
{
	/**
	 * Shopping comparison logo destination url
	 */
	protected $logoUrls = array('http://www.pricegrabber.com');

	/**
	 * Export file settings
	 */
	protected $exportFileExtension = ".csv";
	protected $exportFileSeparator = ",";

	/**
	 * Taxonomy file
	 */
	protected $taxonomyFile = "pricegrabber.taxonomy.100528.txt";

	/**
	 * Export field definitions
	 */
	public function fields($row=null)
	{
		return array(
			"Unique Retailer SKU" => $row['productid'],

			"Manufacturer Name" => array(
				$row['brandname'],
				array('maxlen' => 100, 'csv')
				),

			"Manufacturer Part Number" =>
				$row['prodcode'],

			"Product Title" => array(
				$row['prodname'],
				array('nohtml', 'maxlen' => 100, 'csv')),

			"Categorization" => array(
				$row,
				array('pgcat')),

			"Product Url" => array(
				$row['prodname'],
				array('prodlink')),

			"Image Url" => array(
				$row,
				array('imagelink')),

			"Detailed Description" => array(
				$row['proddesc'],
				array('nohtml', 'maxlen' => 900, 'csv')),

			"Selling Price" => array(
				$row,
				array('calcprice')),

			"Product Condition" => array(
				$row['prodcondition'],
				array('lower', 'ucfirst')
				),

			"Availability" => array(
				$row,
				array('prodavailability')
				),

			"UPC" => $row['upc'],

			"Shipping Cost" => array(
				$row,
				array('shippingcost')
				),

			"Weight" => array(
				$row,
				array('weight')
				)
			);
	}

	public function writeHead()
	{
		$columns = array_keys($this->fields());

		return implode($columns, $this->exportFileSeparator)."\n";

	}

	public function writeRow($row)
	{
		$values = array_values($this->fields($row));

		$filter = new Interspire_Array_Filter();
		$processedValues = $filter->process($values);

		return implode($processedValues, $this->exportFileSeparator)."\n";
	}
}
