<?php
class SHIPPING_FEDEX extends ISC_SHIPPING {
	/*
		The service for FedEx shipments
	*/
	private $_service = "";

	/*
		The destination country ISO code for FedEx shipments
	*/
	private $_destcountry = "";
	/*
		The destination state ISO code for FedEx shipments
	*/
	private $_deststate = "";

	/*
		The destination country zip for FedEx shipments
	*/
	private $_destzip = "";

	/*
		The types of delivery types supported by this provider
	*/
	private $_deliverytypes = array();

	/**
	 * A list of international service types. All other services are domestic.
	 */
	private $_internationalservices = array(
		'INTERNATIONAL_PRIORITY',
		'INTERNATIONAL_ECONOMY',
		'INTERNATIONAL_FIRST',
		'INTERNATIONAL_PRIORITY_FREIGHT',
		'INTERNATIONAL_ECONOMY_FREIGHT',
		'EUROPE_FIRST_INTERNATIONAL_PRIORITY'
	);

	/**
	* List of all the FedEx service types
	*
	* @var array
	*/
	private $_servicetypes = array();

	/**
	* The countries that FedEx allows intra-country shipping
	*
	* @var array
	*/
	private $_intracountrycountries = array('US', 'CA', 'MX');


	public function __construct()
	{

		// Setup the required variables for the FedEx shipping module
		parent::__construct();
		$this->_name = GetLang('FedExName');
		$this->_image = "fedex_logo.gif";
		$this->_description = GetLang('FedExDesc');
		$this->_help = GetLang('FedExHelp');

		$this->_servicetypes = array(
				"PRIORITY_OVERNIGHT" => GetLang('FedExServiceType1'),
				"STANDARD_OVERNIGHT" => GetLang('FedExServiceType2'),
				"FIRST_OVERNIGHT" => GetLang('FedExServiceType3'),
				"FEDEX_2_DAY" => GetLang('FedExServiceType4'),
				"FEDEX_EXPRESS_SAVER" => GetLang('FedExServiceType5'),
				"INTERNATIONAL_PRIORITY" => GetLang('FedExServiceType6'),
				"INTERNATIONAL_ECONOMY" => GetLang('FedExServiceType7'),
				"INTERNATIONAL_FIRST" => GetLang('FedExServiceType8'),
				"FEDEX_1_DAY_FREIGHT" => GetLang('FedExServiceType9'),
				"FEDEX_2_DAY_FREIGHT" => GetLang('FedExServiceType10'),
				"FEDEX_3_DAY_FREIGHT" => GetLang('FedExServiceType11'),
				"FEDEX_GROUND" => GetLang('FedExServiceType12'),
				"GROUND_HOME_DELIVERY" => GetLang('FedExServiceType13'),
				"INTERNATIONAL_PRIORITY_FREIGHT" => GetLang('FedExServiceType14'),
				"INTERNATIONAL_ECONOMY_FREIGHT" => GetLang('FedExServiceType15'),
				"EUROPE_FIRST_INTERNATIONAL_PRIORITY" => GetLang('FedExServiceType16')
			);

		// FedEx is available worldwide
		$this->_countries = array("all");
	}

	public function SetCustomVars()
	{

		$this->_variables['key'] = array("name" => GetLang('FedExKey'),
		   "type" => "textbox",
		   "help" => GetLang('FedExKeyHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['password'] = array("name" => GetLang('FedExPassword'),
		   "type" => "password",
		   "help" => GetLang('FedExPasswordHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['accountno'] = array("name" => GetLang('FedExAccountNumber'),
		   "type" => "textbox",
		   "help" => GetLang('FedExAccountNumberHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['meterno'] = array("name" => GetLang('FedExMeterNumber'),
		   "type" => "textbox",
		   "help" => GetLang('FedExMeterNumberHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['service'] = array("name" => GetLang('FedExServiceTypes'),
		   "type" => "dropdown",
		   "help" => GetLang('FedExServiceHelp'),
		   "default" => "",
		   "required" => true,
		   "options" => array(),
			"multiselect" => true,
			"multiselectheight" => 5
		);

		foreach($this->_servicetypes as $type => $name) {
			$this->_variables['service']['options'][$name] = $type;
		}

		$this->_variables['dropofftype'] = array("name" => GetLang('FedExDropOffType'),
			"type" => "dropdown",
			"help" => GetLang('FedExDropOffTypeHelp'),
			"default" => "",
			"required" => true,
			"options" => array(
				GetLang('FedExDropOffType1') => "REGULAR_PICKUP",
				GetLang('FedExDropOffType2') => "REQUEST_COURIER",
				GetLang('FedExDropOffType3') => "DROP_BOX",
				GetLang('FedExDropOffType4') => "BUSINESS_SERVICE_CENTER",
				GetLang('FedExDropOffType5') => "STATION"
			),
			"multiselect" => false
		);

		$this->_variables['packagingtype'] = array("name" => GetLang('FedExPackagingType'),
			"type" => "dropdown",
			"help" => GetLang('FedExPackagingTypeHelp'),
			"default" => "YOURPACKAGING",
			"required" => true,
			"options" => array(
				GetLang('FedExPackagingType1') => "FEDEX_ENVELOPE",
				GetLang('FedExPackagingType2') => "FEDEX_PAK",
				GetLang('FedExPackagingType3') => "FEDEX_BOX",
				GetLang('FedExPackagingType4') => "FEDEX_TUBE",
				GetLang('FedExPackagingType5') => "FEDEX_10KG_BOX",
				GetLang('FedExPackagingType6') => "FEDEX_25KG_BOX",
				GetLang('FedExPackagingType7') => "YOUR_PACKAGING"
			),
			"multiselect" => false
		);

		$this->_variables['ratetype'] = array("name" => GetLang('FedExRateType'),
		   "type" => "dropdown",
		   "help" => GetLang('FedExRateTypeHelp'),
		   "default" => "",
		   "required" => true,
		   "options" => array(
						GetLang('FedExListRate') => "LIST",
						GetLang('FedExAccountRate') => "ACCOUNT",
						),
			"multiselect" => false
		);

		$this->_variables['destinationtype'] = array("name" => GetLang('FedExDestinationType'),
		   "type" => "dropdown",
		   "help" => GetLang('FedExDestinationTypeHelp'),
		   "default" => 1,
		   "required" => true,
		   "options" => array(
						GetLang('FedExResidential') => 'residential',
						GetLang('FedExBusiness') => 'business',
						),
			"multiselect" => false
		);

		$this->_variables['testmode'] = array("name" => GetLang('FedExTestMode'),
		   "type" => "dropdown",
		   "help" => GetLang("FedExTestModeHelp"),
		   "default" => "NO",
		   "required" => true,
		   "options" => array(GetLang("FedExTestModeNo") => "NO",
						  GetLang("FedExTestModeYes") => "YES"
			),
			"multiselect" => false
		);
	}

	/**
	* Test the shipping method by displaying a simple HTML form
	*/
	public function TestQuoteForm()
	{

		$GLOBALS['ServiceTypes'] = "";
		$service_types = $this->GetValue("service");

		if(!is_array($service_types)) {
			$service_types = array($service_types);
		}

		// Load up the module variables
		$this->SetCustomVars();

		foreach($this->_variables['service']['options'] as $k => $v) {
			if(in_array($v, $service_types)) {
				$sel = 'selected="selected"';
			} else {
				$sel = "";
			}
			$GLOBALS['ServiceTypes'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $v, $k);
		}

		// Which countries has the user chosen to ship orders to?
		$first_country = "United States";
		$GLOBALS['Countries'] = GetCountryList("United States");

		$GLOBALS['WeightUnit'] = GetConfig('WeightMeasurement');

		$num_states = 0;
		$GLOBALS['StateList'] = $state_options = GetStatesByCountryNameAsOptions($first_country, $num_states);

		$GLOBALS['Image'] = $this->_image;

		$this->ParseTemplate("module.fedex.test");
	}

	/**
	* Get the shipping quote and display it in a form
	*/
	public function TestQuoteResult()
	{

		// Add a single test item - no dimensions needed for FedEx
		$this->additem($_POST['delivery_weight']);

		// Setup all of the shipping variables
		$this->_destcountry = GetCountryISO2ById($_POST['delivery_country']);
		$this->_deststate = GetStateISO2ById($_POST['delivery_state']);
		$this->_destzip = $_POST['delivery_zip'];
		$this->_service = $_POST['service_type'];

		// Fedex doesn't allow non US/CA/MX intra-country service (eg. AU to AU), raise an error
		if (!in_array($this->_origin_country['country_iso'], $this->_intracountrycountries) && $this->_origin_country['country_iso'] == $this->_destcountry) {
			$this->SetError(GetLang('FedExIntraCountryError'));
			$result = false;
		}
		else {
			// Next actually retrieve the quote
			$result = $this->GetQuote();
		}

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

				$GLOBALS['Message'] .= $quote->getdesc(false) . " - $" . $quote->getprice() . " USD";

				if(count($result) > 1) {
					$GLOBALS['Message'] .= "</li>";
				}
			}
		}

		$GLOBALS['Image'] = $this->_image;
		$this->ParseTemplate("module.fedex.testresult");
	}

	private function GetQuote()
	{
		$shipperAddress = array(
			'CountryCode'	=> $this->_origin_country['country_iso'],
			'PostalCode'	=> $this->_origin_zip
		);

		// United States or Canada require state/province
		if ($this->_origin_country['country_iso'] == 'US' || $this->_origin_country['country_iso'] == 'CA') {
			$shipperAddress['StateOrProvinceCode'] = $this->_origin_state['state_iso'];
		}

		$recipientAddress = array(
			'CountryCode'	=> $this->_destcountry,
			'PostalCode'	=> $this->_destzip,
			'Residential'	=> ($this->GetValue('destinationtype') == 'residential'),
		);

		// United States or Canada require state/province
		if ($this->_destcountry == 'US' || $this->_destcountry == "CA") {
			$recipientAddress['StateOrProvinceCode'] = $this->_deststate;
		}

		$weight = number_format(max(ConvertWeight($this->_weight, 'lbs'), 0.1), 1, '.', '');

		// if today is on the weekend, set the date to the next monday
		$shipTime = isc_gmmktime();
		$day = date('l', $shipTime);
		if ($day == 'Saturday') {
			$shipTime += 172800;
		}
		elseif ($day == 'Sunday') {
			$shipTime += 86400;
		}
		// create the shipment
		$shipDate = date('c', $shipTime);

		$xml = array(
			'WebAuthenticationDetail' => array (
				'UserCredential' => array(
					'Key' 		=> $this->GetValue('key'),
					'Password'	=> $this->GetValue('password')
				)
			),
			'ClientDetail' => array(
				'AccountNumber'	=> $this->GetValue('accountno'),
				'MeterNumber'	=> $this->GetValue('meterno')
			),
			'Version' => array(
				'ServiceId'		=> 'crs',
				'Major' 		=> '7',
				'Intermediate'	=> '0',
				'Minor' 		=> '0'
			),
			'ReturnTransitAndCommit' => true,
			'RequestedShipment' => array(
				'Shipper' => array(
					'Address' => $shipperAddress
				),
				'Recipient' => array(
					'Address' => $recipientAddress
				),
				'ShippingChargesPayment' => array(
					'PaymentType' => 'SENDER'
				),
				'RateRequestTypes' 	=> $this->GetValue('ratetype'),
				'PackageCount'		=> 1,
				'PackageDetail'		=> 'INDIVIDUAL_PACKAGES',
				'PackagingType'		=> $this->GetValue('packagingtype'),
				'DropoffType'		=> $this->GetValue('dropofftype'),
				'ShipTimestamp'		=> $shipDate,
				'RequestedPackageLineItems' => array(
					'Weight' => array(
						'Units' => 'LB',
						'Value' => $weight
					)
				),
			)
		);

		if (!empty($this->_service)) {
			$xml['RequestedShipment']['ServiceType'] = $this->_service;
			$services = array($this->_service);
		}
		else {
			$services = $this->GetValue("service");
			if(!is_array($services) && $services != "") {
				$services = array($services);
			}
		}

		$new_xml['RateRequest'] = $xml;

		require_once(dirname(__FILE__) . "/../../../lib/nusoap/nusoap.php");

		if ($this->GetValue('testmode') == "NO") {
			$wsdl = "RateService_v7.wsdl";
		}
		else {
			$wsdl = "RateService_v7_dev.wsdl";
		}

		$client = new nusoap_client(dirname(__FILE__) . "/" . $wsdl, 'wsdl');
		$result = $client->call('getRates', $new_xml);

		if ($result['HighestSeverity'] == 'FAILURE' || $result['HighestSeverity'] == 'ERROR' || !isset($result['RateReplyDetails'])) {
			if (isset($result['Notifications'])) {
				$notifications = $result['Notifications'];
				if (key($notifications) != '0') {
					$notifications = array($notifications);
				}
				foreach ($notifications as $notification) {
					$this->SetError($notification['Severity'] . ' - ' . $notification['Message']);
				}
			}
			else {
				$this->SetError(GetLang('FedExBadResponse'));
			}
			return false;
		}

		if ($this->GetValue('ratetype') == 'LIST') {
			$responseRateTypes = array('RATED_LIST', 'PAYOR_LIST');
			$preferredRateType = 'RATED_LIST';
		}
		else {
			$responseRateTypes = array('RATED_ACCOUNT', 'PAYOR_ACCOUNT');
			$preferredRateType = 'RATED_ACCOUNT';
		}

		$currency = GetDefaultCurrency();

		$quotes = array();

		$rateReplyDetails = $result['RateReplyDetails'];
		if (key($rateReplyDetails) != '0') {
			$rateReplyDetails = array($rateReplyDetails);
		}

		$serviceQuotes = array();
		$serviceRateTypes = array();

		foreach ($rateReplyDetails as $rate) {
			// skip if  this service hasn't been enabled
			if (!in_array($rate['ServiceType'], $services)) {
				continue;
			}

			$shipmentDetails = $rate['RatedShipmentDetails'];
			if (key($shipmentDetails) != '0') {
				$shipmentDetails = array($shipmentDetails);
			}

			foreach ($shipmentDetails as $shipmentRate) {
				$rateDetail = $shipmentRate['ShipmentRateDetail'];

				// ensure we have the correct rate type response
				if (!in_array($rateDetail['RateType'], $responseRateTypes)) {
					continue;
				}

				// multiple rate types for the same service can be returned, we preferabbly want the 'RATED' rate type
				if (isset($serviceRateTypes[$rate['ServiceType']]) && $serviceRateTypes[$rate['ServiceType']] == $preferredRateType) {
					continue;
				}

				// ensure the amount is in the currency of the store
				$totalNetCharge = $rateDetail['TotalNetCharge'];
				if ($totalNetCharge['Currency'] != $currency['currencycode']) {
					$this->SetError(GetLang('FedExUnexpectedCurrency', array('quoteCurrency' => $totalNetCharge['Currency'], 'storeCurrency' => $currency['currencycode'])));
					return false;
				}

				// build a new shipping quote
				$serviceQuotes[$rate['ServiceType']] = new ISC_SHIPPING_QUOTE(
					$this->GetId(),
					$this->GetDisplayName(),
					(float)$totalNetCharge['Amount'],
					$this->_servicetypes[$rate['ServiceType']]
				);

				// store the type of rate for this service
				$serviceRateTypes[$rate['ServiceType']] = $rateDetail['RateType'];
			}

			$quotes = array_values($serviceQuotes);
		}

		return $quotes;
	}


	public function GetServiceQuotes()
	{
		$this->ResetErrors();
		$QuoteList = array();

		// Set the FedEx-specific variables
		$this->_destcountry = $this->_destination_country['country_iso'];
		$this->_destzip = $this->_destination_zip;
		$this->_deststate = $this->_destination_state['state_iso'];
		$this->_carriercode = $this->GetValue("carriercode");

		// Return all available FedEx service types
		$services = $this->GetValue("service");
		if(!is_array($services) && $services != "") {
			$services = array($services);
		}

		if (empty($services)) {
			return array();
		}

		// Fedex doesn't allow non US/CA/MX intra-country service (eg. AU to AU) so skip
		if (!in_array($this->_origin_country['country_iso'], $this->_intracountrycountries) && $this->_origin_country['country_iso'] == $this->_destcountry) {
			return array();
		}

		// Next actually retrieve the quote
		$QuoteList = $this->GetQuote();

		if (empty($QuoteList)) {
			// Invalid quote, log the error
			foreach($this->GetErrors() as $error) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), GetLang('ShippingQuoteError'), $error);
			}
		}

		return $QuoteList;
	}

	public function GetTrackingLink($trackingNumber = "")
	{
		return "http://www.fedex.com/Tracking?cntry_code=us&tracknumbers=" . $trackingNumber;
	}

	/**
	 * Get a human readable list of of the delivery methods available for the shipping module
	 *
	 * @return array
	 **/
	public function GetAvailableDeliveryMethods()
	{
		$methods = array();

		// Get the display name for this module
		$displayName = $this->GetDisplayName();

		// Return quotes for all available UPS service types
		$services = $this->GetValue('service');

		if (!is_array($services) && $services != '') {
			$services = array($services);
		} elseif (!is_array($services)) {
			$services = array();
		}

		foreach ($services as $key => $service) {
			$methods[$key] = $displayName.' ('.$this->_servicetypes[$service].')';
		}

		return $methods;
	}
}