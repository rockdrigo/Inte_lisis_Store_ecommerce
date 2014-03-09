<?php

	/**
	* This is the UPS shipping module for Interspire Shopping Cart. To enable
	* UPS in Interspire Shopping Cart login to the control panel and click the
	* Settings -> Shipping Settings tab in the menu.
	*/
	class SHIPPING_UPS extends ISC_SHIPPING
	{

		/**
		* Variables for the UPS shipping module
		*/

		/*
			The delivery type for UPS shipments
		*/
		private $_deliverytype = "";

		/*
			The destination country ISO code for UPS shipments
		*/
		private $_destcountry = "";

		/*
			The destination country zip for UPS shipments
		*/
		private $_destzip = "";

		/*
			The shipping rate UPS shipments
		*/
		private $_shippingrate = "";

		/*
			The packaging type for UPS shipments
		*/
		private $_packagingtype = "";

		/*
			The destination type (residential or commercial) for UPS shipments
		*/
		private $_destination = "";

		/*
			Shipping class constructor
		*/
		public function __construct()
		{

			// Setup the required variables for the UPS shipping module
			parent::__construct();
			$this->_name = GetLang('UPSName');
			$this->_image = "ups_logo.gif";
			$this->_description = GetLang('UPSDesc');
			$this->_help = GetLang('UPSHelp');
			$this->_height = 310;

			$this->_deliverytypes = array(
				"1DM" => GetLang('UPSDeliveryType1'),
				"1DA" => GetLang('UPSDeliveryType2'),
				"1DP" => GetLang('UPSDeliveryType3'),
				"2DM" => GetLang('UPSDeliveryType4'),
				"2DA" => GetLang('UPSDeliveryType5'),
				"3DS" => GetLang('UPSDeliveryType6'),
				"GND" => GetLang('UPSDeliveryType7'),
				"STD" => GetLang('UPSDeliveryType8'),
				"XPR" => GetLang('UPSDeliveryType9'),
				"XDM" => GetLang('UPSDeliveryType10'),
				"XPD" => GetLang('UPSDeliveryType11')
			);

			// UPS is only available in USA
			$this->_countries = array("United States");
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
			   "help" => GetLang('UPSDeliveryTypesHelp'),
			   "default" => "",
			   "required" => true,
			   "options" => array(),
				"multiselect" => true,
				"multiselectheight" => 7
			);

			foreach($this->_deliverytypes as $type => $name) {
				$this->_variables['deliverytypes']['options'][$name] = $type;
			}

			$this->_variables['packagingtype'] = array("name" => "Packaging Type",
			   "type" => "dropdown",
			   "help" => GetLang('UPSPackagingTypeHelp'),
			   "default" => "",
			   "required" => true,
			   "options" => array(GetLang('UPSPackagingType1') => "00",
							  GetLang('UPSPackagingType2') => "01",
							  GetLang('UPSPackagingType3') => "03",
							  GetLang('UPSPackagingType4') => "21",
							  GetLang('UPSPackagingType5') => "24",
							  GetLang('UPSPackagingType6') => "25"
							),
				"multiselect" => false
			);

			$this->_variables['shippingrate'] = array("name" => "Shipping Rate",
			   "type" => "dropdown",
			   "help" => GetLang('UPSShippingRateHelp'),
			   "default" => "",
			   "required" => true,
			   "options" => array(GetLang('UPSShippingRate1') => "Regular+Daily+Pickup",
								GetLang('UPSShippingRate2') => "On+Call+Air",
								GetLang('UPSShippingRate3') => "One+Time+Pickup",
								GetLang('UPSShippingRate4') => "Letter+Center",
								GetLang('UPSShippingRate5') => "Customer+Counter"
							),
				"multiselect" => false
			);

			$this->_variables['destination'] = array("name" => "Destination Type",
			   "type" => "dropdown",
			   "help" => GetLang('UPSDestinationHelp'),
			   "default" => "RES",
			   "required" => true,
			   "options" => array(GetLang('UPSDestination1') => "RES",
								GetLang('UPSDestination2') => "COM"
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
			$GLOBALS['WeightUnit'] = GetConfig('WeightMeasurement');

			foreach($this->_variables['deliverytypes']['options'] as $k => $v) {
				if(in_array($v, $del_types)) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				$GLOBALS['DeliveryTypes'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $v, $k);
			}

			// Which countries has the user chosen to ship orders to?
			$GLOBALS['Countries'] = GetCountryList("United States");

			$GLOBALS['Image'] = $this->_image;

			$this->ParseTemplate("module.ups.test");
		}

		/**
		* Get the shipping quote and display it in a form
		*/
		public function TestQuoteResult()
		{

			// Add a single test item - no dimensions needed for UPS
			$this->additem($_POST['delivery_weight']);

			// Setup all of the shipping variables
			$this->_deliverytype = $_POST['delivery_type'];
			$this->_destcountry = GetCountryISO2ById($_POST['delivery_country']);
			$this->_destzip = $_POST['delivery_zip'];
			$this->_shippingrate = $this->GetValue("shippingrate");
			$this->_packagingtype = $this->GetValue("packagingtype");
			if($_POST['delivery_destination'] == 'COM') {
				$this->_destination = "0";
			} else {
				$this->_destination = "1";
			}
			// Convert the weight to pounds
			$this->_weight = ConvertWeight($this->_weight, 'pounds');


			$result = $this->GetQuote();

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

					$GLOBALS['Message'] .= $quote->getdesc(false) . " - $" . number_format($quote->getprice(), GetConfig('DecimalPlaces')) . " USD";

					if(count($result) > 1) {
						$GLOBALS['Message'] .= "</li>";
					}
				}
			}

			$GLOBALS['Image'] = $this->_image;
			$this->ParseTemplate("module.ups.testresult");
		}

		private function GetQuote()
		{

			// The following array will be returned to the calling function.
			// It will contain at least one ISC_SHIPPING_QUOTE object if
			// the shipping quote was successful.

			$ups_quote = array();

			// Connect to UPS.com to retrieve a live shipping quote
			$result = "";
			$valid_quote = false;
			$action = "3";
			$ups_url = "http://www.ups.com/using/services/rave/qcostcgi.cgi?accept_UPS_license_agreement=yes&";

			// for some reason the options are stored url encoded (like with + instead of space)
			$shippingRate = urldecode($this->_shippingrate);

			// UPS will only recognise the ZIP part of ZIP+4 for US addresses - drop it
			$zip = $this->_destzip;
			if ($this->_destcountry == 'US') {
				// replace either XXXXXYYYY or XXXXX-YYYY with just XXXX
				// shouldn't affect quotes since UPS will throw an error for anything other than 5 digits
				$zip = preg_replace('#^(\d{5})-?\d{4}$#', '\1', $zip);
			}

			$post_vars = array(
				"10_action" => $action,
				"13_product" => $this->_deliverytype,
				"14_origCountry" => $this->_origin_country['country_iso'],
				"15_origPostal" => $this->_origin_zip,
				"19_destPostal" => $zip,
				"22_destCountry" => $this->_destcountry,
				"23_weight" => $this->_weight,
				"47_rate_chart" => $shippingRate,
				"48_container" => $this->_packagingtype,
				"49_residential" => $this->_destination,
			);

			// build a query here for use with either curl or fopen (though, this should probably be
			// using PostToRemoteFileAndGetResponse)

			$post_vars = http_build_query($post_vars);

			if(function_exists("curl_exec")) {
				// Use CURL if it's available
				$ch = @curl_init($ups_url);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vars);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				// Setup the proxy settings if there are any
				if (GetConfig('HTTPProxyServer')) {
					curl_setopt($ch, CURLOPT_PROXY, GetConfig('HTTPProxyServer'));
					if (GetConfig('HTTPProxyPort')) {
						curl_setopt($ch, CURLOPT_PROXYPORT, GetConfig('HTTPProxyPort'));
					}
				}

				if (GetConfig('HTTPSSLVerifyPeer') == 0) {
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				}

				$result = curl_exec($ch);

				if($result != "") {
					$valid_quote = true;
				}
			}
			else {
				// Use fopen instead
				if($fp = @fopen($ups_url . $post_vars, "rb")) {
					$result = "";

					while(!feof($fp))
						$result .= fgets($fp, 4096);

					@fclose($fp);
					$valid_quote = true;
				}
			}

			$this->SetCustomVars();

			if($valid_quote) {
				$result = explode("%", $result);

				if(count($result) > 5) {
					$Error = false;
					$quote_desc = "";

					// Set the description of the method
					foreach($this->_variables['deliverytypes']['options'] as $k => $v) {
						if($v == $result[1]) {
							$quote_desc = $k;
						}
					}

					// Create a quote object
					$quote = new ISC_SHIPPING_QUOTE($this->GetId(), $this->GetDisplayName(), $result[8], $quote_desc);
					return $quote;
				}
				else {
					$this->SetError($result[1]);
					return false;
				}
			}
			else {
				// Couldn't get to UPS.com
				$this->SetError(GetLang('UPSOpenError'));
				return false;
			}

			return $ups_quote;
		}

		public function GetServiceQuotes()
		{
			$this->ResetErrors();
			$QuoteList = array();
			// Set the UPS-specific variables
			$this->_destcountry = $this->_destination_country['country_iso'];
			$this->_destzip = $this->_destination_zip;
			$this->_shippingrate = $this->GetValue("shippingrate");
			$this->_packagingtype = $this->GetValue("packagingtype");
			$this->_destination_rescom = $this->GetValue("destination");

			if($this->_destination_rescom == "COM") {
				$this->_destination = "0";
			} else {
				$this->_destination = "1";
			}

			// Convert the weight to pounds
			$this->_weight = ConvertWeight($this->_weight, 'pounds');

			// Return quotes for all available UPS service types
			$services = $this->GetValue("deliverytypes");

			if(!is_array($services) && $services != "") {
				$services = array($services);
			}

			foreach($services as $service) {
				// Set the service type
				$this->_deliverytype = $service;

				// Next actually retrieve the quote
				$err = "";
				$result = $this->GetQuote($err);

				// Was it a valid quote?
				if(is_object($result)) {
					array_push($QuoteList, $result);
				// Invalid quote, log the error
				} else {
					foreach($this->GetErrors() as $error) {
						$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_deliverytypes[$service].": " .GetLang('ShippingQuoteError'), $error);
					}
				}
			}
			return $QuoteList;
		}

		/**
		 * Get a human readable list of of the delivery methods available for the shipping module
		 *
		 * @return array
		 **/
		public function GetAvailableDeliveryMethods()
		{
			// Return quotes for all available UPS service types
			$methods = $this->GetValue("deliverytypes");

			if (!is_array($methods) && $methods != "") {
				$methods = array($methods);
			} elseif (!is_array($methods)) {
				$methods = array();
			}

			$displayName = $this->GetDisplayName();

			foreach ($methods as $key => $method) {
				$methods[$key] = $displayName.' ('.$this->_deliverytypes[$method].')';
			}

			return $methods;
		}

		/**
		* Generate a link to track items for UPS.
		*
		* @return string The tracking URL for UPS shipments.
		*/
		public function GetTrackingLink($trackingNumber = "")
		{
			//return "http://www.ups.com/WebTracking/track?loc=en_US&WT.svl=PNRO_L1";
			return "http://wwwapps.ups.com/WebTracking/processRequest?&tracknum=" . urlencode($trackingNumber);
		}
	}
