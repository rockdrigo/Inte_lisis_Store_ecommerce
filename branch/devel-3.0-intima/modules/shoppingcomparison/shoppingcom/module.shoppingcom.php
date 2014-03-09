<?php

class SHOPPINGCOMPARISON_SHOPPINGCOM extends ISC_SHOPPINGCOMPARISON
{
	/**
	 * Logos and destination urls for shopping.com and mySimon.
	 */
	protected $logoImages = array(
			'shopcom55.png',
			'simon55.png');

	protected $logoUrls = array(
			'http://www.shopping.com',
			'http://www.mysimon.com'
		);


	/**
	 * Category taxonomy file
	 */
	protected $taxonomyFile = 'shopcom.taxonomy.100607.txt';

	/**
	 * Export file settings
	 */
	protected $exportFileExtension = '.csv';
	protected $exportFileSeparator = ',';

	/**
	 * Export field definitions
	 */
	public function fields($row=null)
	{
		return array(
			"Unique Merchant SKU" => array(
				$row['prodcode'],
				array('default' => $row['productid'])),

			"Product Name " => array(
				$row['prodname'],
				array('nohtml', 'maxlen' => 89, 'csv')),

			"Product URL " => array(
				$row['prodname'],
				array('prodlink')),

			"Image URL" => array(
				$row,
				array('imagelink')),

			"Price " =>  array(
				$row,
				array('calcprice')),

			"MPN" =>
				$row['prodcode'],

			"UPC" =>
				$row['upc'],

			"Manufacturer" => array(
				$row['brandname'],
				array('nohtml', 'maxlen' => 99, 'csv')),

			"Category ID" => array(
				$row,
				array('shopcomcatid')),

			"Category Name" => array(
				$row,
				array('shopcomcatname')),

			"Product Description " => array(
				$row['proddesc'],
				array('nohtml', 'maxlen' => 3999, 'csv')),

			"Condition" =>	array(
				$row['prodcondition'],
				array('lower', 'ucfirst')
				),

			"Stock Availability" => array(
				$row,
				array('prodavailability')
				),

			"Shipping Rate" => array(
				$row,
				array('shippingcost')
				),

			"Shipping Weight" =>  array(
				$row,
				array('weight')),
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

		return implode(array_values($processedValues), $this->exportFileSeparator)."\n";
	}
}
