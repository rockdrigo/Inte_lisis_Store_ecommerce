<?php

	/*
		Include the XMLize xml class
	*/
	require_once(ISC_BASE_PATH."/includes/classes/class.xmlize.php");

	/**
	* This is the Canada Post shipping module for Interspire Shopping Cart. To enable
	* Canada Post in Interspire Shopping Cart login to the control panel and click the
	* Settings -> Shipping Settings tab in the menu.
	*/
	class SHIPPING_CANADAPOST extends ISC_SHIPPING
	{

		/**
		* Variables for the Canada Post shipping module
		*/

		/*
			The merchant identifier for Canada Post
		*/
		private $_merchantid = "";

		/*
			Are products ready to ship
		*/
		private $_readytoship = "";

		/*
			The destination country for Canada Post shipments
		*/
		private $_destcountry = "";

		/*
			The destination state for Canada Post shipments
		*/
		private $_deststate = "";

		/*
			The destination country zip for Canada Post shipments
		*/
		private $_destzip = "";

		/**
		* Functions for the Canada Post shipping module
		*/

		/*
			Shipping class constructor
		*/
		public function __construct()
		{

			// Setup the required variables for the Canada Post shipping module
			parent::__construct();
			$this->_name = GetLang('CanadaPostName');
			$this->_image = "canadapost_logo.gif";
			$this->_description = GetLang('CanadaPostDesc');
			$this->_help = GetLang('CanadaPostHelp');
			$this->_height = 310;

			// Canada Post is only available in USA
			$this->_countries = array("Canada");
		}

		/*
		 * Check if this shipping module can be enabled or not.
		 *
		 * @return boolean True if this module is supported on this install, false if not.
		 */
		public function IsSupported()
		{
			$errors = array();
			if(!function_exists("curl_exec")) {
				$this->SetError(GetLang('CanadaPostNoCurl'));
			}

			if(!$this->HasErrors()) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		* Custom variables for the shipping module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{
			$this->_variables['merchantid'] = array("name" => GetLang("CanadaPostMerchantIdentification"),
			   "type" => "textbox",
			   "help" => GetLang('CanadaPostMerchantHelp'),
			   "default" => "CPC_DEMO_XML",
			   "required" => true
			);

			$this->_variables['readytoship'] = array("name" => GetLang("CanadaPostReadyToShip"),
			   "type" => "dropdown",
			   "help" => GetLang('CanadaPostReadyToShipHelp'),
			   "default" => "no",
				"savedvalue" => array(),
			   "required" => true,
			   "options" => array(GetLang("CanadaPostNo") => "no",
							  GetLang("CanadaPostYes") => "yes"
				),
				"multiselect" => false
			);
		}

		/**
		* Test the shipping method by displaying a simple HTML form
		*/
		public function TestQuoteForm()
		{

			// Which countries has the user chosen to ship orders to?
			$GLOBALS['Countries'] = GetCountryList("Canada");
			$GLOBALS['Image'] = $this->_image;

			$GLOBALS['WeightUnit'] = GetConfig('WeightMeasurement');
			$GLOBALS['LengthUnit'] = GetConfig('LengthMeasurement');

			$this->ParseTemplate("module.canadapost.test");
		}

		/**
		* Get the shipping quote and display it in a form
		*/
		public function TestQuoteResult()
		{

			// Add a single test item - dimensions needed for Canada Post
			$this->AddItem($_POST['delivery_weight'], $_POST['delivery_length'], $_POST['delivery_width'], $_POST['delivery_height'], 1, "Item #1");

			$this->_merchantid = $this->GetValue("merchantid");
			$this->_destcountry = GetCountryById($_POST['delivery_country']);
			$this->_deststate = $_POST['delivery_state'];
			$this->_destzip = $_POST['delivery_zip'];
			$this->_readytoship = $this->GetValue("readytoship");
			// Next actually retrieve the quote
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

					if($quote->GetTransit() != -1) {
						if($quote->GetTransit() == 0) {
							// Same day
							$transit = ", today";
						}
						else {
							$transit = ", " . $quote->GetTransit() . " day(s)";
						}
					}
					else {
						$transit = "";
					}

					$GLOBALS['Message'] .= $quote->GetDesc(false) . " - $" . $quote->GetPrice() . " CAD" . $transit;

					if(count($result) > 1) {
						$GLOBALS['Message'] .= "</li>";
					}
				}
			}

			$GLOBALS['Image'] = $this->_image;
			$this->ParseTemplate("module.canadapost.testresult");
		}

		private function GetQuote()
		{

			// The following array will be returned to the calling function.
			// It will contain at least one ISC_SHIPPING_QUOTE object if
			// the shipping quote was successful.

			$cp_quote = array();

			// Connect to Canada Post to retrieve a live shipping quote
			$items = "";
			$result = "";
			$valid_quote = false;
			$cp_url = "http://sellonline.canadapost.ca:30000?";
			$readytoship = '';
			if($this->_readytoship == 'yes') {
				$readytoship = "<readyToShip/>";
			}

			foreach($this->_products as $product) {
				$items .= sprintf("<item>
								<quantity>%d</quantity>
								<weight>%s</weight>
								<length>%s</length>
								<width>%s</width>
								<height>%s</height>
								<description><![CDATA[%s]]></description>
								%s
						</item>",
						$product->getquantity(),
						ConvertWeight($product->GetWeight(), 'kgs'),
						ConvertLength($product->getlength(), "cm"),
						ConvertLength($product->getwidth(), "cm"),
						ConvertLength($product->getheight(), "cm"),
						$product->getdesc(),
						$readytoship
						);
			}

			$cp_xml = sprintf("<" . "?" . "xml version=\"1.0\" ?" . ">
				<eparcel>
						<language>en</language>
						<ratesAndServicesRequest>
								<merchantCPCID>%s</merchantCPCID>
								<fromPostalCode>%s</fromPostalCode>
								<lineItems>
									%s
							   </lineItems>
								<city></city>
								<provOrState>%s</provOrState>
								<country>%s</country>
								<postalCode>%s</postalCode>
						</ratesAndServicesRequest>
				</eparcel>
			", $this->_merchantid, $this->_origin_zip, $items, $this->_deststate, isc_strtoupper($this->_destcountry), $this->_destzip);

			$post_vars = implode("&",
			array("XMLRequest=$cp_xml"
				)
			);

			$result = PostToRemoteFileAndGetResponse($cp_url, $post_vars);
			if($result) {
				$valid_quote = true;
			}

			if(!$valid_quote) {
				$this->SetError(GetLang('CanadaPostOpenError'));
				return false;
			}
			$xml = @simplexml_load_string($result);

			if(!is_object($xml)) {
				$this->SetError(GetLang('CanadaPostBadResponse'));
				return false;
			}

			if(isset($xml->error)) {
				$this->SetError((string)$xml->error->statusMessage);
				return false;
			}

			if(isset($xml->ratesAndServicesResponse)) {
				foreach($xml->ratesAndServicesResponse->product as $ship_method) {
					// Calculate the transit time
					$transit_time = -1;

					$today = $ship_method->shippingDate;
					$arr_today = explode("-", $today);
					$today_stamp = mktime(0, 0, 0, $arr_today[1], $arr_today[2], $arr_today[0]);

					$delivered = $ship_method->deliveryDate;
					$arr_delivered = explode("-", $delivered);

					if(count($arr_delivered) == 3) {
						$delivered_stamp = mktime(0, 0, 0, $arr_delivered[1], $arr_delivered[2], $arr_delivered[0]);
						$transit_time = $delivered_stamp - $today_stamp;

						// Convert transit time to days
						$transit_time = floor($transit_time/60/60/24);
					}

					$quote = new ISC_SHIPPING_QUOTE($this->GetId(), $this->GetDisplayName(), (float)$ship_method->rate, (string)$ship_method->name, $transit_time);
					$cp_quote[] = $quote;
				}
			}
			return $cp_quote;
		}

		public function GetServiceQuotes()
		{
			$QuoteList = array();
			// Set the Canada Post-specific variables
			$this->_merchantid = $this->GetValue("merchantid");
			$this->_destcountry = $this->_destination_country['country_name'];
			$this->_deststate = $this->_destination_state['state_name'];
			$this->_destzip = $this->_destination_zip;
			$this->_readytoship = $this->GetValue("readytoship");

			// Next actually retrieve the quote
			$err = "";
			$result = $this->GetQuote();

			// Was it a valid quote?
			if(is_array($result)) {
				// Split up each quote and return them separately
				foreach($result as $quote) {
					$newQuote = new ISC_SHIPPING_QUOTE($this->GetId(), $this->GetDisplayName(), $quote->getprice(), $quote->getdesc(), $quote->gettransit());
					$shipper_quote = array($newQuote);
					array_push($QuoteList, $shipper_quote);
				}
			}
			// Invalid quote, log the error
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), GetLang('ShippingQuoteError'), $error);
				}
			}
			return $QuoteList;
		}

		public function GetTrackingLink($trackingNumber = "")
		{
			return "https://www.canadapost.ca/cpotools/apps/track/personal/findByTrackNumber?trackingNumber=" . urlencode($trackingNumber);
		}
	}