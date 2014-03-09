<?php
class SHIPPING_UPSONLINE extends ISC_SHIPPING
{
	/**
	 * @var array An array of the domestic delivery methods that UPS supports.
	 */
	private $domesticTypes = array(
		'14' => 'NextDayAirEarlyAM',
		'01' => 'NextDayAir',
		'13' => 'NextDayAirSaver',
		'59' => '2ndDayAirAM',
		'02' => '2ndDayAir',
		'12' => '3DaySelect',
		'03' => 'Ground'
	);

	/**
	 * @var array An array of the international delivery methods that UPS supports.
	 */
	private $internationalTypes = array(
		'11' => 'WorldwideStandard',
		'07' => 'WorldwideExpress',
		'54' => 'WorldwideExpressPlus',
		'08' => 'WorldwideExpedited',
		'65' => 'WorldwideSaver'
	);

	/**
	 * @var array An array of pickup types that UPS supports.
	 */
	private $pickupTypes = array(
		'01' => 'DailyPickup',
		'03' => 'CustomerCounter',
		'06' => 'OneTime',
		'07' => 'OnCall',
		'11' => 'SuggestedRetail',
		'19' => 'LetterCenter',
		'20' => 'AirService'
	);

	/**
	 * @var array A list of packing types that UPS supports.
	 */
	private $packagingTypes = array(
		'00' => 'Unknown',
		'01' => 'UPSLetter',
		'02' => 'Package',
		'03' => 'Tube',
		'04' => 'Pak',
		'21' => 'ExpressBox',
		'24' => '25kgBox',
		'25' => '10kgBox',
		'30' => 'Pallet',
		'2A' => 'SmallExpressBox',
		'2B' => 'MediumExpressBox',
		'2C' => 'LargeExpressBox'
	);


	/**
	 * @var array A list of destination types that UPS supports.
	 */
	private $DestinationTypes = array(
		'0' => 'Commercial',
		'1' => 'Residential',
	);

	/**
	 * @var array A list of destination types that UPS supports.
	 */
	private $TestMode = array(
		'0' => 'No',
		'1' => 'Yes',
	);

	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->SetName(GetLang('UPSOnlineTools'));
		$this->SetImage('ups_logo.gif');
		$this->SetHelpText(GetLang('UPSOnlineToolsHelp'));
	}

	/**
	 * Set up the configuration options for the UPS module.
	 */
	public function SetCustomVars()
	{
		$this->_variables['accesslicenseno'] = array(
			'name' => GetLang('AccessLicenseNo'),
			'type' => 'text',
			'help' => GetLang('AccessLicenseNoHelp'),
			'required' => true,
		);

		$this->_variables['accessuserid'] = array(
			'name' => GetLang('AccessUserId'),
			'type' => 'text',
			'help' => GetLang('AccessUserIdHelp'),
			'required' => true
		);

		$this->_variables['accesspassword'] = array(
			'name' => GetLang('AccessPassword'),
			'type' => 'password',
			'help' => GetLang('AccessPasswordHelp'),
			'required' => true
		);

		$this->_variables['upsaccount'] = array(
			'name' => GetLang('UPSAccount'),
			'type' => 'textbox',
			'help' => GetLang('UPSAccountHelp'),
			'required' => false
		);

		$this->_variables['destinationtype'] = array(
			'name' => GetLang('DestinationType'),
			'type' => 'dropdown',
			'help' => GetLang('DestinationTypeHelp'),
			'required' => true,
			'options' => array(),
			'multiselect' => false
		);

		foreach($this->DestinationTypes as $type => $langVar) {
			$this->_variables['destinationtype']['options'][GetLang('DestinationType'.$langVar)] = $type;
		}

		$this->_variables['pickuptypes'] = array(
			'name' => GetLang('PickupType'),
			'type' => 'dropdown',
			'help' => GetLang('PickupTypeHelp'),
			'required' => true,
			'options' => array(),
			'multiselect' => false,
		);

		foreach($this->pickupTypes as $type => $langVar) {
			$this->_variables['pickuptypes']['options'][GetLang('PickupType'.$langVar)] = $type;
		}

		$this->_variables['packagingtype'] = array(
			'name' => GetLang('PackagingType'),
			'type' => 'dropdown',
			'help' => GetLang('PackagingTypeHelp'),
			'required' => true,
			'options' => array(),
			'multiselect' => false
		);

		foreach($this->packagingTypes as $type => $langVar) {
			$this->_variables['packagingtype']['options'][GetLang('PackagingType'.$langVar)] = $type;
		}

		$this->_variables['deliverytypes'] = array(
			'name' => GetLang('DeliveryTypes'),
			'type' => 'dropdown',
			'help' => GetLang('DeliveryTypesHelp'),
			'required' => true,
			'options' => array(),
			'multiselect' => true
		);

		foreach($this->domesticTypes as $type => $langVar) {
			$this->_variables['deliverytypes']['options'][GetLang('DeliveryType'.$langVar)] = $type;
		}



		foreach($this->internationalTypes as $type => $langVar) {
			$this->_variables['deliverytypes']['options'][GetLang('DeliveryType'.$langVar)] = $type;
		}


		$this->_variables['testmode'] = array(
			'name' => GetLang('TestMode'),
			'type' => 'dropdown',
			'help' => GetLang('TestModeHelp'),
			'required' => true,
			'options' => array(),
			'multiselect' => false
		);

		foreach($this->TestMode as $key => $langVar) {
			$this->_variables['testmode']['options'][GetLang('TestMode'.$langVar)] = $key;
		}

	}

	/**
	 * Test the shipping method by displaying a simple HTML form
	 */
	public function TestQuoteForm()
	{
		// Load up the module variables
		$this->SetCustomVars();

		// Which countries has the user chosen to ship orders to?
		$GLOBALS['Countries'] = GetCountryList($this->_origin_country['country_name']);
		$GLOBALS['StateList'] = GetStatesByCountryNameAsOptions($this->_origin_country['country_name'], $numStates);
		$GLOBALS['WeightMeasurement'] = GetConfig('WeightMeasurement');
		if(!$GLOBALS['StateList']) {
			$GLOBALS['StateNameAppend'] = '2';
			$GLOBALS['HideStatesList'] = 'display: none';
		}

		$GLOBALS['Image'] = $this->GetImage();
		$this->ParseTemplate("module.upsonline.test");
	}

	/**
	 * Get the shipping quote and display it in a form
	 */
	public function TestQuoteResult()
	{
		$this->AddItem($_POST['weight']);
		$this->SetDestinationZip($_POST['destinationZip']);
		$this->SetDestinationCountry($_POST['destinationCountry']);
		if(isset($_POST['destinationState'])) {
			$this->SetDestinationState(GetStateById($_POST['destinationState']));
		}
		$quotes = $this->FetchQuotes();
		if(!is_array($quotes)) {
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
			$GLOBALS['Message'] = '<ul style="margin-left: 0; padding-left: 0">';

			foreach($quotes as $quote) {
				$GLOBALS['Message'] .= '<li style="color: green">'.$quote->GetDesc(false).' - '.FormatPrice($quote->GetPrice()).'</li>';
			}
			$GLOBALS['Message'] .= '</li>';
		}
		$GLOBALS['Image'] = $this->GetImage();
		$this->ParseTemplate("module.upsonline.testresult");
	}

	/**
	 * Generate the XML to be sent to UPS to calculate shipping quotes.
	 *
	 * @return string The generated XML.
	 */
	private function GenerateRateXML()
	{
		$shipFromCity = $this->_origin_city;
		$shipFromState = $this->_origin_state['state_iso'];
		$shipFromZip = $this->_origin_zip;
		$shipFromCountry = $this->_origin_country['country_iso'];

		// Build the XML for the shipping quote
		$xml = new SimpleXMLElement("<AccessRequest xml:lang='en-US'/>");
		$xml->addChild('AccessLicenseNumber', $this->GetValue('accesslicenseno'));
		$xml->addChild('UserId', $this->GetValue('accessuserid'));
		$xml->addChild('Password', $this->GetValue('accesspassword'));
		$accessRequest = $xml->asXML();

		$xml = new SimpleXMLElement('<RatingServiceSelectionRequest/>');
		$request = $xml->addChild('Request');

		// Add in the transaction reference
		$transRef = $request->addChild('TransactionReference');
		$transRef->addChild('CustomerContext', 'Rating and Service');
		$transRef->addChild('XpciVersion', '1.0');
		$request->addChild('RequestAction', 'Rate');
		$request->addChild('RequestOption', 'Shop');

		// Add in the pickup type we'll be using
		$xml->addChild('PickupType')->addChild('Code', $this->GetValue('pickuptypes'));

		// Provide information about the shipment
		$shipment = $xml->addChild('Shipment');

		// Add the information about the shipper
		$shipper = $shipment->addChild('Shipper');

		$shipperNumber = $this->GetValue('upsaccount');
		if($shipperNumber) {
			$shipper->addChild('ShipperNumber', $shipperNumber);
			$rateInformation = $shipment->addChild('RateInformation');
			$rateInformation->addChild('NegotiatedRatesIndicator');
		}
		$address = $shipper->addChild('Address');
		$address->addChild('City', $shipFromCity);
		$address->addChild('StateProvinceCode', $shipFromState);
		$address->addChild('PostalCode', $shipFromZip);
		$address->addChild('CountryCode', $shipFromCountry);

		// Now add the information about the destination address
		$address = $shipment->addChild('ShipTo')->addChild('Address');
		//$address->addChild('City', 'Sydney');

		if($this->_destination_state['state_iso']) {
			$state = $this->_destination_state['state_iso'];
		}
		else {
			$state = $this->_destination_state['state_name'];
		}

		$address->addChild('StateProvinceCode', $state);
		$address->addChild('PostalCode', $this->_destination_zip);
		$address->addChild('CountryCode', $this->_destination_country['country_iso']);
		//is the destination residential address
		if($this->GetValue('destinationtype') == 1) {
			$address->addChild('ResidentialAddress');
		}



		// Add in the location we're shipping from
		$shipFrom = $shipment->addChild('ShipFrom');
		$address = $shipFrom->addChild('Address');
		$address->addChild('City', $shipFromCity);
		$address->addChild('StateProvinceCode', $shipFromState);
		$address->addChild('PostalCode', $shipFromZip);
		$address->addChild('CountryCode', $shipFromCountry);


		// Add in the package information
		$package = $shipment->addChild('Package');
		$package->addChild('PackagingType')->addChild('Code', $this->GetValue('packagingtype'));

		$packageWeight = $package->addChild('PackageWeight');
		switch(strtolower($shipFromCountry)) {
			case 'us':
			case 'lr':
			case 'mm':
			case 'ca':
				$weightCode = 'LBS';
				$dimensionsCode = 'IN';
				break;
			default:
				$weightCode = 'KGS';
				$dimensionsCode = 'CM';
		}

		$packageWeight->addChild('UnitOfMeasurement')->addChild('Code', $weightCode);

		$weight = ConvertWeight($this->_weight, $weightCode);
		if ($weight < 0.1) {
			$weight = 0.1;
		} else if ($weight > 150) {
			$weight = 150;
		}
		$packageWeight->addChild('Weight', $weight);

		/**
		* Quotes are wildly inaccurate when adding dimensions, they come out very expensive.
		* Not supplying dimensions returns quotes that are correct and equal to what is entered
		* even with dimensions on the UPS site (ie. weight must be the correct factor).
		*/
		$shipmentDimensions = $this->GetCombinedShipDimensions();

		if($shipmentDimensions['width']+$shipmentDimensions['height']+$shipmentDimensions['length'] > 0) {
			$dimensions = $package->addChild('Dimensions');
			$dimensions->addChild('UnitOfMeasurement')->addChild('Code', $dimensionsCode);
			$dimensions->addChild('Length', number_format(ConvertLength($shipmentDimensions['length'], $dimensionsCode),2, '.', ''));
			$dimensions->addChild('Width', number_format(ConvertLength($shipmentDimensions['width'], $dimensionsCode),2, '.', ''));
			$dimensions->addChild('Height', number_format(ConvertLength($shipmentDimensions['height'], $dimensionsCode),2, '.', ''));
		}


		$combinedXML = $accessRequest.$xml->asXML();

		return $combinedXML;
	}

	/**
	 * Actually fetch the shipping quotes based on the set information.
	 *
	 * @return array An array of shipping quotes.
	 */
	private function FetchQuotes()
	{
		if($this->GetValue('testmode') == 1) {
			$upsUrl = 'https://wwwcie.ups.com/ups.app/xml/Rate';
		} else {
			$upsUrl = 'https://www.ups.com/ups.app/xml/Rate';
		}
		$shipmentXML = $this->GenerateRateXML();
		$result = PostToRemoteFileAndGetResponse($upsUrl, $shipmentXML);

		if($result === false) {
			$this->SetError(GetLang('UPSOpenError'));
			return false;
		}

		$x = @simplexml_load_string($result);
		if(!is_object($x)) {
			$this->SetError(GetLang('UPSOpenError'));
			return false;
		}

		// Was an error returned from UPS? If so, set that and return
		if(isset($x->Response->ResponseStatusCode) && $x->Response->ResponseStatusCode == 0) {
			$this->SetError((string)$x->Response->Error->ErrorDescription);
			return false;
		}

		$quotes = array();

		if(!isset($x->RatedShipment[0])) {
			$quoteXML = array($x->RatedShipment);
		}
		else {
			$quoteXML = $x->RatedShipment;
		}

		$deliveryTypes = $this->GetValue('deliverytypes');
		if (!is_array($deliveryTypes)) {
			$deliveryTypes = array($deliveryTypes);
		}
		foreach($quoteXML as $quote) {
			// Fetch the friendly name of the shipping service for this quote
			$serviceName = GetLang('Unknown');
			$service = (string)$quote->Service->Code;

			// We're not offering this delivery type in the store so skip it
			if(!in_array($service, $deliveryTypes)) {
				continue;
			}

			if(isset($this->internationalTypes[$service])) {
				$serviceName = GetLang('DeliveryType'.$this->internationalTypes[$service]);
			}
			else if(isset($this->domesticTypes[$service])) {
				$serviceName = GetLang('DeliveryType'.$this->domesticTypes[$service]);
			}

			//display negotiated rate when the account number is entered
			if (trim($this->GetValue('upsaccount')) != ''&& isset($quote->NegotiatedRates->NetSummaryCharges->GrandTotal)) {
				$cost = (float)$quote->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue;
			} else {
				$cost = (float)$quote->TotalCharges->MonetaryValue;
			}

			$currencyCode = (string)$quote->TotalCharges->CurrencyCode;
			$quoteCurrency = GetCurrencyByCode($currencyCode);
			if($quoteCurrency == false) {
				$this->SetError(sprintf(GetLang('UPSCurrencyCodeError'), $currencyCode));
				continue;
			}
			$transitTime = 0;
			if(isset($quote->GuaranteedDaysToDelivery)) {
				$transitTime = (string)$quote->GuaranteedDaysToDelivery;
			}
			$cost = ConvertPriceToDefaultCurrency($cost, $quoteCurrency);
			$quotes[] = new ISC_SHIPPING_QUOTE($this->GetId(), $this->GetDisplayName(), $cost, $serviceName, $transitTime);
		}

		return $quotes;
	}

	/**
	 * Calculate the shipping quotes on the front end of the store.
	 *
	 * @return array An array of shipping quotes.
	*/
	public function GetServiceQuotes()
	{
		$quotes = $this->FetchQuotes();

		// The quote failed, so log the errors
		if(!is_array($quotes)) {
			foreach($this->GetErrors() as $error) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), GetLang('ShippingQuoteError'), $error);
			}
			return false;
		}

		// Return the quotes
		return $quotes;
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

		foreach($this->domesticTypes as $type => $langVar) {
			$this->_variables['deliverytypes']['options'][GetLang('DeliveryType'.$langVar)] = $type;
		}

		foreach($this->internationalTypes as $type => $langVar) {
			$this->_variables['deliverytypes']['options'][GetLang('DeliveryType'.$langVar)] = $type;
		}



		foreach ($methods as $key => $method) {
			if(isset($this->domesticTypes[$method])) {
				$value = GetLang('DeliveryType'.$this->domesticTypes[$method]);
			}
			else if(isset($this->internationalTypes[$method])) {
				$value = GetLang('DeliveryType'.$this->internationalTypes[$method]);
			}
			$methods[$key] = $displayName.' ('.$value.')';
		}

		return $methods;
	}
}
