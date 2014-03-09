<?php

class ShoppingComparison_Bizrate extends Isc_ShoppingComparison
{
	/**
	 * Logo images and urls for bizrate, shopzilla and beso
	 */
	protected $logoImages = array(
		'bizrate55.png',
		'shopzilla55.png',
		'beso55.png');

	protected $logoUrls = array(
		'http://www.bizrate.com',
		'http://www.shopzilla.com',
		'http://www.beso.com'
		);

	/**
	 * Export file settings
	 */
	protected $exportFileExtension = ".txt";
	protected $exportFileSeparator = "\t";

    /**
     * Category taxonomy file
     */
    protected $taxonomyFile = 'shopzilla.taxonomy.100601.txt';

	/**
	 * Export field definitions
	 */
	public function fields($row=null)
	{
		return array(
			"Category ID" => array(
				$row,
				array('bizcat')),

			/* Manufacturer */
			"Manufacturer" => array(
				$row['brandname'],
				array('nohtml', 'maxlen' => 100, 'bizratetsv')),

			/* Title */
			"Title" => array(
				$row['prodname'],
				array('nohtml', 'maxlen' => 100, 'bizratetsv')),

			/* Description */
			"Description" => array(
				$row['proddesc'],
				array('nohtml', 'maxlen' => 900, 'bizratetsv')),

			/* Product URL */
			"Product URL" => array(
				$row['prodname'],
				array('prodlink')),

			/* Image URL */
			"Image URL" => array(
				$row,
				array('imagelink')),

			/* SKU */
			"SKU" => $row['productid'],

			/* Availability */
			"Availability" => array(
				$row,
				array('prodavailability')
				),

			/* Condition */
			"Condition" => array(
				$row['prodcondition'],
				array('lower', 'ucfirst')
				),

			/* Ship Weight */
			"Ship Weight" => array(
				$row,
				array('weight' => array('format' => false))
				),

			/* Ship Cost */
			"Ship Cost" => array(
				$row,
				array('shippingcost')
				),

			/* Bid */
			"Bid" => null,

			/* Promo */
			"Promotional Code" => null,

			/* UPC */
			"UPC" => $row['upc'],

			/* Price */
			"Price" => array(
				$row,
				array('calcprice')),
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
