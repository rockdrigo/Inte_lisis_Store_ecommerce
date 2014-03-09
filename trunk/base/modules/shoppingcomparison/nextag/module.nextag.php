<?php

class SHOPPINGCOMPARISON_NEXTAG extends ISC_SHOPPINGCOMPARISON
{
	/**
	 * Export file settings
	 */
	protected $exportFileExtension = ".csv";
	protected $exportFileSeparator = ",";

	/**
	 * Shopping comparison logo destination url
	 */
	protected $logoUrls = array('http://merchants.nextag.com/serv/main/buyer/sellerprograms.jsp?mpc=18');

	/**
	 * Taxonomy file
	 */
    protected $taxonomyFile = 'nextag.taxonomy.100715.txt';

	/**
	 * Export field definitions
	 */
	public function fields($row=null)
	{
		return array(
			"Manufacturer" => array(
				$row['brandname'],
				array('maxlen' => 100, 'csv')
				),

			"Manufacturer Part #" =>
				$row['prodcode'],

			"Product Name" => array(
				$row['prodname'],
				array('nohtml', 'maxlen' => 80, 'csv')),

			"Product Description" => array(
				$row['proddesc'],
				array('nohtml', 'maxlen' => 500, 'csv')),

			"Click-Out URL" => array(
				$row['prodname'],
				array('prodlink')),

			"Price" => array(
				$row,
				array('calcprice')),

			"Category: Other Format" =>
				$row['catname'],

			"Category: NexTag Numeric ID" => array(
				$row,
				array('nextagcat')),

			"Image Url" => array(
				$row,
				array('imagelink')),

			"Ground Shipping" => array(
				$row,
				array('shippingcost')
				),

			"Stock Status" => array(
				$row,
				array('prodavailability')
				),

			"Product Condition" => array(
				$row['prodcondition'],
				array('lower', 'ucfirst')
				),

			"Marketing Message" =>
				null,

			"Weight" => array(
				$row,
				array('weight')
				),

			"Cost-per-Click" =>
				null,

			"UPC" =>
				$row['upc'],

			"Distributor ID" =>
				null,

			"MUZE ID" =>
				null,

			"ISBN" =>
				null,

			"Seller Part #" =>
				$row['productid']
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

		try {
			$filter = new Interspire_Array_Filter();
			$processedValues = $filter->process($values);
		}
		catch(Exception $e) {
			return null;
		}

		return implode($processedValues, $this->exportFileSeparator)."\n";
	}
}
