<?php

	/**
	* This is the Royal Mail shipping module for Interspire Shopping Cart. To enable
	* Royal Mail in Interspire Shopping Cart login to the control panel and click the
	* Settings -> Shipping Settings tab in the menu.
	*/
	class SHIPPING_ROYALMAIL extends ISC_SHIPPING
	{

		/**
		* Variables for the Royal Mail shipping module
		*/

		/*
			The delivery type for Royal Mail shipments
		*/
		private $_deliverytype = "";

		/*
			The destination country ISO code for Royal Mail shipments
		*/
		private $_destcountry = "";

		/*
			The destination country zip for Royal Mail shipments
		*/
		private $_destzip = "";

		/*
			The total length dimension of all products in the shipment
		*/
		private $_totallength = 0;

		/*
			The total width dimension of all products in the shipment
		*/
		private $_totalwidth = 0;

		/*
			The total height dimension of all products in the shipment
		*/
		private $_totalheight = 0;

		/*
			The total number of packages in the shipment
		*/
		private $_totalpackages = 0;

		/*
			The types of delivery types supported by this provider
		*/
		private $_deliverytypes = array();

		/**
		 * @var array Array containing the shipping method's delivery types that are international.
		 */
		private $internationalTypes = array(
			'InternationalAirmailPackets',
			'InternationalSurfaceSmallPackets'
		);

		/**
		* Functions for the Royal Mail shipping module
		*/

		/*
			Shipping class constructor
		*/
		public function __construct()
		{

			// Setup the required variables for the Royal Mail shipping module
			parent::__construct();
			$this->_name = GetLang('RoyalMailName');
			$this->_image = "royalmail_logo.gif";
			$this->_description = GetLang('RoyalMailDesc');
			$this->_help = GetLang('RoyalMailHelp');
			$this->_height = 315;
			$this->_deliverytypes = array(
				"FirstClass" => GetLang('RoyalMailDeliveryType1'),
				"SecondClass" => GetLang('RoyalMailDeliveryType2'),
				"SpecialDeliveryNextDay" => GetLang('RoyalMailDeliveryType4'),
				"SpecialDelivery9am" => GetLang('RoyalMailDeliveryType5'),
				"StandardParcel" => GetLang('RoyalMailDeliveryType6'),
				"InternationalSurfaceSmallPackets" => GetLang('RoyalMailDeliveryType7'),
				"InternationalAirmailPackets" => GetLang('RoyalMailDeliveryType8')
			);

			// Royal Mail is only available in United Kingdom
			$this->_countries = array("United Kingdom");
		}

		/**
		* Custom variables for the shipping module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{
			$this->_variables['deliverytypes'] = array("name" => "Delivery Types",
			   "type" => "dropdown",
			   "help" => GetLang('RoyalMailDeliveryTypesHelp'),
			   "default" => "",
			   "required" => true,
			   "options" => array(),
				"multiselect" => true,
				"multiselectheight" => 7
			);

			foreach($this->_deliverytypes as $type => $name) {
				$this->_variables['deliverytypes']['options'][$name] = $type;
			}

			$this->_variables['packingmethod'] = array("name" => "Packing Method",
			   "type" => "dropdown",
			   "help" => GetLang('RoyalMailPackingMethodHelp'),
			   "default" => "",
			   "required" => false,
			   "options" => array(GetLang('RoyalMailPackingMethod1') => "single",
							  GetLang('RoyalMailPackingMethod2') => "multiple"
							),
				"multiselect" => false
			);
		}

		/**
		* Test the shipping method by displaying a simple HTML form
		*/
		public function TestQuoteForm()
		{

			$GLOBALS['DeliveryTypes'] = "";
			$del_types = $this->GetValue("deliverytypes");

			if(!is_array($del_types)) {
				$del_types = array($del_types);
			}

			// Load up the module variables
			$this->SetCustomVars();

			foreach($this->_variables['deliverytypes']['options'] as $k => $v) {
				if(in_array($v, $del_types)) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				$GLOBALS['DeliveryTypes'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $v, $k);
			}

			// Which countries has the user chosen to ship orders to?
			$GLOBALS['Countries'] = GetCountryList("Australia");

			$GLOBALS['WeightUnit'] = GetConfig('WeightMeasurement');

			$GLOBALS['Image'] = $this->_image;

			$this->ParseTemplate("module.royalmail.test");
		}

		/**
		* Get the shipping quote and display it in a form
		*/
		public function TestQuoteResult()
		{

			// Add a single test item
			$this->AddItem($_POST['delivery_weight']);

			$this->_deliverytype = $_POST['delivery_type'];
			$this->_destcountry = GetCountryISO2ById($_POST['delivery_country']);
			$this->_destzip = $_POST['delivery_postcode'];

			$result = '';

			if (in_array($this->_deliverytype, $this->internationalTypes)) {
				if($this->_origin_country['country_iso'] == $this->_destcountry) {
					$this->SetError(GetLang('RoyalMailShipInternationalToLocalError'));
				}
				else {
					$result = $this->GetQuote();
				}
			}
			else {
				if($this->_origin_country['country_iso']  == $this->_destcountry) {
					$result = $this->GetQuote();
				}
				else {
					$this->SetError(GetLang('RoyalMailShipLocalToInternationalError'));
				}

			}

			// Next actually retrieve the quote

			if(!is_object($result) && !is_array($result)) {
				$GLOBALS['Color'] = "red";
				$GLOBALS['Status'] = GetLang('StatusFailed');
				$GLOBALS['Label'] = GetLang('ShipErrorMessage');
				$GLOBALS['Message'] = implode('<br />', $this->GetErrors());
			}
			else {
				$GLOBALS['Color'] = "green";
				$GLOBALS['Status'] = GetLang('StatusSuccess');
				$GLOBALS['Label'] = GetLang('ShipQuotePrice');

				// Get each available shipping option and display it
				$GLOBALS['Message'] = "";

				if(!is_array($result)) {
					$result = array($result);
				}

				foreach($result as $quote) {
					if(count($result) > 1) {
						$GLOBALS['Message'] .= "<li>";
					}

					$GLOBALS['Message'] .= $quote->getdesc(false) . " - " . $quote->getprice() . " GBP";

					if(count($result) > 1) {
						$GLOBALS['Message'] .= "</li>";
					}
				}
			}

			$GLOBALS['Image'] = $this->_image;
			$this->ParseTemplate("module.royalmail.testresult");
		}

		private function GetQuote()
		{
			$packages = $this->BuildPackages();
			$shippingCharge = 0;
			$transitTime = 0;

			// Set the description of the method
			$description = "";

			// Load up the module variables
			$this->SetCustomVars();

			foreach($this->_variables['deliverytypes']['options'] as $k => $v) {
				if($v == $this->_deliverytype) {
					$description = $k;
				}
			}

			$handle = fopen(dirname(__FILE__)."/data/royalmail-2010.csv", "r");

			$shippingData = array();

			while (($data = fgetcsv($handle, 1000, ",")) !== false) {
					$shippingData[] = $data;
			}

			fclose($handle);

			// Now loop through all of the packages we'll be sending
			foreach($packages as $package) {
				// Convert the dimensions, and convert the weight to grams
				$weight = ConvertWeight($package['weight'], 'kilograms');

				foreach (array_keys($shippingData) as $key) {
					if ($shippingData[$key][0] == $this->_deliverytype && $weight <= (float)$shippingData[$key][1]) {
						$shippingCharge = $shippingData[$key][2];
						break;
					}
				}
			}

			if ($shippingCharge == 'Unavailable') {
				return '';
			}

			// OK, so create the actual quote
			$packageCount = '';
			if(count($packages) > 1) {
				$packageCount = count($packages). ' x ';
			}

			$quote = new ISC_SHIPPING_QUOTE($this->GetId(), $this->GetDisplayName(), number_format($shippingCharge, 2), $packageCount.$description);
			return $quote;
		}

		public function GetServiceQuotes()
		{
			$this->ResetErrors();
			$quoteList = array();

			// Set the Royal Mail-specific variables
			$this->_destcountry = $this->_destination_country['country_iso'];
			$this->_destzip = $this->_destination_zip;

			// Get the selected delivery types
			$delivery_types = $this->GetValue("deliverytypes");
			if($delivery_types == null) {
				// No delivery type selected.
				return $quoteList;
			}
			elseif(!is_array($delivery_types) && $delivery_types != "") {
				$delivery_types = array($delivery_types);
			}

			foreach($delivery_types as $delivery_type) {
				// Only perform lookups for quotes we can fetch - so don't bother trying for quotes for local services when shipping internationally
				if((in_array($delivery_type, $this->internationalTypes))) {
					if($this->_origin_country['country_iso'] == $this->_destcountry) {
						continue;
					}
				}
				else {
					if($this->_origin_country['country_iso'] != $this->_destcountry) {
						continue;
					}
				}

				$this->_deliverytype = $delivery_type;

				// Next actually retrieve the quote
				$err = "";
				$result = $this->GetQuote($err);

				// Was it a valid quote?
				if(is_object($result)) {
					$quoteList[] = $result;
				// Invalid quote, log the error
				} else {
					foreach($this->GetErrors() as $error) {
						$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_deliverytypes[$delivery_type].": " .GetLang('ShippingQuoteError'), $error);
					}
				}
			}

			return $quoteList;
		}

		public function BuildPackages()
		{
			$packages = array();

			// Everything is going to be sent in different packages, break down per item
			if($this->GetValue('packingmethod') == 'multiple') {
				foreach($this->_products as $product) {
					for($i = 1; $i <= $product->GetQuantity(); ++$i) {
						$packages[] = array(
							'height'	=> $product->GetHeight(),
							'width'		=> $product->GetWidth(),
							'length'	=> $product->GetLength(),
							'weight'	=> $product->GetWeight(),
							'items'		=> 1
						);
					}
				}
				return $packages;
			}
			// Everything is going in the one package, simply calculate the dimensions
			else {
				// Workout the dimensions of the package Royal Mail measures in kilogram
				$dimensions = $this->getcombinedshipdimensions();
				$dimensions['weight'] = $this->GetWeight();
				$dimensions['items'] = count($this->_products);
				$packages[] = $dimensions;
				return $packages;
			}
		}

		/**
		 * Get a human readable list of of the delivery methods available for the shipping module
		 *
		 * @return array
		 **/
		public function GetAvailableDeliveryMethods()
		{
			$methods = array();

			// Get the selected delivery types
			$delivery_types = $this->GetValue("deliverytypes");

			if(!is_array($delivery_types) && $delivery_types != "") {
				$delivery_types = array($delivery_types);
			}

			foreach($delivery_types as $delivery_type) {
				$methods[] = $this->GetDisplayName() . ' (' . $this->_deliverytypes[$delivery_type].')';
			}

			return $methods;
		}

		public function GetTrackingLink($trackingLink = "")
		{
			return "http://www.royalmail.com/portal/rm/track?trackNumber=" . urlencode($trackingLink);
		}
	}