<?php
class SHIPPING_USPS extends ISC_SHIPPING
{
	/*
		The UPS service to ship with
	*/
	private $_service = "";

	/*
		Various shipping settings that are USPS specific
	*/
	private $_expressmailcontainertype = "";
	private $_expressmailpackagesize = "";
	private $_firstclasscontainertype = "";
	private $_firstclasspackagesize = "";
	private $_prioritycontainertype = "";
	private $_prioritypackagesize = "";
	private $_parcelpostmachpackagesize = "";
	private $_bpmpackagesize = "";
	private $_librarypackagesize = "";
	private $_mediapackagesize = "";

	const USPS_EXPRESS_SERVICE_TYPE = 0;
	const USPS_FIRST_CLASS_SERVICE_TYPE = 1;
	const USPS_PRIORITY_SERVICE_TYPE = 2;
	const USPS_PARCEL_SERVICE_TYPE = 3;
	const USPS_BPM_SERVICE_TYPE = 4;
	const USPS_LIBRARY_SERVICE_TYPE = 5;
	const USPS_MEDIA_SERVICE_TYPE = 6;

	/**
	* remaps country name differences for usps
	*/
	private $_mapcountries = array();

	/**
	* These are the service ID's for each international service.
	* The response from USPS for an international quote contains one of these ID's.
	* Using these ID's to enable or disable a specific service.
	*
	* @var array service types
	*/
	private $internationalTypes = array(
		'ExpressMailIntl'		=> array('1', '10'),
		'PriorityMailIntl'		=> array('2', '8', '9', '11'),
		'GlobalExpress' 		=> array('4', '5', '6', '7', '12'),
		'FirstClassMailIntl' 	=> array('13', '14', '15')
	);

	/**
	* Functions for the USPS shipping module
	*/

	/*
		Shipping class constructor
	*/
	public function __construct()
	{
		// Setup the required variables for the USPS shipping module
		parent::__construct();
		$this->_name = GetLang('USPSName');
		$this->_image = "usps_logo.gif";
		$this->_description = GetLang('USPSDesc');
		$this->_help = GetLang('USPSHelp');
		$this->_height = 390;

		// USPS is only available in USA
		$this->_countries = array("United States");

		// read in our list of countries
		$importer = new Interspire_Csv(dirname(__FILE__) . "/data/countries.csv");
		foreach ($importer as $row) {
			$this->_mapcountries[$row[0]] = $row[1];
		}
	}

	/*
	 * Check if this shipping module can be enabled or not.
	 *
	 * @return boolean True if this module is supported on this install, false if not.
	 */
	public function IsSupported()
	{
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
		$this->_variables['username'] = array(
			"name" => GetLang('USPSUsername'),
			"type" => "textbox",
			"help" => GetLang('USPSUsernameHelp'),
			"default" => "",
			"required" => true
		);

		$this->_variables['servertype'] = array(
			"name" => GetLang('USPSServer'),
			"type" => "dropdown",
			"help" => GetLang('USPSServerTypeHelp'),
			"default" => "",
			"required" => true,
			"options" => array(
							GetLang('USPSServerType1') => "test",
							GetLang('USPSServerType2') => "production"
						),
			"multiselect" => false
		);

		$this->_variables['domesticsettings'] = array(
			"name" => "<strong>" . GetLang('USPSDomesticSettings') . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		$this->_variables['expressmailsettings'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;<strong>" . GetLang('USPSExpressMailSettings') . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		$this->_variables['expressmailstatus'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('Status'),
			"type" => "dropdown",
			"help" => "",
			"default" => "disabled",
			"required" => false,
			"options" => array(
							GetLang('Enabled') => "enabled",
							GetLang('Disabled') => "disabled"
						),
			"multiselect" => false
		);

		$this->_variables['expressmailpackagesize'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSPackageSize'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
							GetLang('USPSRegular') => "R",
							GetLang('USPSLarge') => "L"
						),
			"multiselect" => false
		);

		$this->_variables['expressmailcontainertype'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSContainerType'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
				GetLang('USPSCustomContainer') => '',
				GetLang('USPSFlatRateEnvelope') => "F"
			),
			"multiselect" => false
		);

		$this->_variables['expressmailweightlimit'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSWeightLimit'),
			"type" => "label",
			"help" => "",
			"label" => "70 lbs"
		);

		$this->_variables['firstclasssettings'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;<strong>" . GetLang('USPSFirstClassSettings') . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		$this->_variables['firstclassstatus'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('Status'),
			"type" => "dropdown",
			"help" => "",
			"default" => "disabled",
			"required" => false,
			"options" => array(
							GetLang('Enabled') => "enabled",
							GetLang('Disabled') => "disabled"
						),
			"multiselect" => false
		);

		$this->_variables['firstclasspackagesize'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSPackageSize'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
							GetLang('USPSRegular') => "R",
							GetLang('USPSLarge') => "L"
						),
			"multiselect" => false
		);

		$this->_variables['firstclassweightlimit'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSWeightLimit'),
			"type" => "label",
			"help" => "",
			"label" => "13 ounces"
		);

		$this->_variables['prioritymailsettings'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;<strong>" . GetLang('USPSPriorityMailSettings') . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		$this->_variables['prioritymailstatus'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('Status'),
			"type" => "dropdown",
			"help" => "",
			"default" => "disabled",
			"required" => false,
			"options" => array(
							GetLang('Enabled') => "enabled",
							GetLang('Disabled') => "disabled"
						),
			"multiselect" => false
		);

		$this->_variables['prioritymailpackagesize'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSPackageSize'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
							GetLang('USPSRegular') => "R",
							GetLang('USPSLarge') => "L"
						),
			"multiselect" => false
		);

		$this->_variables['prioritymailcontainertype'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSContainerType'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
				GetLang('USPSCustomContainer') => '',
				GetLang('USPSFlatRateEnvelope') => "F",
				GetLang('USPSFlatRateBox') => "B"
			),
			"multiselect" => false
		);

		$this->_variables['prioritymailweightlimit'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSWeightLimit'),
			"type" => "label",
			"help" => "",
			"label" => "70 lbs"
		);

		$this->_variables['parcelpostmachinablesettings'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;<strong>" . GetLang('USPSParcelPostSettings') . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		$this->_variables['parcelpostmachinablestatus'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('Status'),
			"type" => "dropdown",
			"help" => "",
			"default" => "disabled",
			"required" => false,
			"options" => array(
							GetLang('Enabled') => "enabled",
							GetLang('Disabled') => "disabled"
						),
			"multiselect" => false
		);

		$this->_variables['parcelpostmachinablepackagesize'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSPackageSize'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
							GetLang('USPSRegular') => "R",
							GetLang('USPSLarge') => "L",
							GetLang('USPSOversize') => "O"
						),
			"multiselect" => false
		);

		$this->_variables['parcelpostmachinableweightlimit'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSWeightLimit'),
			"type" => "label",
			"help" => "",
			"label" => "70 lbs"
		);

		$this->_variables['bpmsettings'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;<strong>" . GetLang('USPSBPMSettings') . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		$this->_variables['bpmstatus'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('Status'),
			"type" => "dropdown",
			"help" => "",
			"default" => "disabled",
			"required" => false,
			"options" => array(
							GetLang('Enabled') => "enabled",
							GetLang('Disabled') => "disabled"
						),
			"multiselect" => false
		);

		$this->_variables['bpmpackagesize'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSPackageSize'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
							GetLang('USPSRegular') => "R",
							GetLang('USPSLarge') => "L"
						),
			"multiselect" => false
		);


		$this->_variables['bpmweightlimit'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSWeightLimit'),
			"type" => "label",
			"help" => "",
			"label" => "15 lbs"
		);

		$this->_variables['librarysettings'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;<strong>" . GetLang('USPSLibrarySettings') . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		$this->_variables['librarystatus'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('Status'),
			"type" => "dropdown",
			"help" => "",
			"default" => "disabled",
			"required" => false,
			"options" => array(
							GetLang('Enabled') => "enabled",
							GetLang('Disabled') => "disabled"
						),
			"multiselect" => false
		);

		$this->_variables['librarypackagesize'] = array(
			"name" =>"&nbsp;&nbsp;&nbsp;" .  GetLang('USPSPackageSize'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
				GetLang('USPSRegular') => "R",
				GetLang('USPSLarge') => "L"
			),
			"multiselect" => false
		);


		$this->_variables['libraryweightlimit'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSWeightLimit'),
			"type" => "label",
			"help" => "",
			"label" => "70 lbs"
		);

		$this->_variables['mediasettings'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;<strong>" . GetLang('USPSMediaSettings') . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		$this->_variables['mediastatus'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('Status'),
			"type" => "dropdown",
			"help" => "",
			"default" => "disabled",
			"required" => false,
			"options" => array(
					GetLang('Enabled') => "enabled",
					GetLang('Disabled') => "disabled"
				),
			"multiselect" => false
		);

		$this->_variables['mediapackagesize'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSPackageSize'),
			"type" => "dropdown",
			"help" => "",
			"default" => "",
			"required" => false,
			"options" => array(
							GetLang('USPSRegular') => "R",
							GetLang('USPSLarge') => "L"
						),
			"multiselect" => false
		);


		$this->_variables['mediaweightlimit'] = array(
			"name" => "&nbsp;&nbsp;&nbsp;" . GetLang('USPSWeightLimit'),
			"type" => "label",
			"help" => "",
			"label" => "70 lbs"
		);

		// international methods
		$this->_variables['internationalsettings'] = array(
			"name" => "<strong>" . GetLang("USPSInternationalSettings") . "</strong>",
			"type" => "blank",
			"help" => ""
		);

		foreach ($this->internationalTypes as $typeName => $type) {
			$options = array();
			foreach ($type as $service) {
				$options[GetLang('USPSIntlService_' . $service)] = $service;
			}

			$this->_variables[$typeName] = array(
				'name' => GetLang('USPS' . $typeName),
				'type' => 'dropdown',
				'required' => false,
				'help' => '',
				'options' => $options,
				'multiselect' => true,
				'multiselectheight' => count($type) * 3
			);
		}
	}

	/**
	* Test the shipping method by displaying a simple HTML form
	*/
	public function TestQuoteForm()
	{
		// Which countries has the user chosen to ship orders to?
		$GLOBALS['Countries'] = GetCountryList($this->_origin_country['country_name']);
		$GLOBALS['WeightMeasurement'] = GetConfig('WeightMeasurement');
		$GLOBALS['Image'] = $this->GetImage();

		$this->ParseTemplate("module.usps.test");
	}

	/**
	* Get the shipping quote and display it in a form
	*/
	public function TestQuoteResult()
	{
		$this->AddItem($_POST['weight']);
		$this->SetDestinationZip($_POST['destinationZip']);
		$this->SetDestinationCountry($_POST['destinationCountry']);

		$quotes = $this->GetServiceQuotes();

		if(is_object($quotes)) {
			$quotes = array($quotes);
		}

		if(empty($quotes)) {
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
		$this->ParseTemplate("module.usps.testresult");
	}

	private function GetQuote()
	{
		// The following array will be returned to the calling function.
		// It will contain at least one ISC_SHIPPING_QUOTE object if
		// the shipping quote was successful.

		$usps_quote = array();

		$origincountry = $this->GetCountry($this->_origin_country['country_iso']);
		$destcountry = $this->GetCountry($this->_destination_country['country_iso']);

		// Is this an international quote?
		if($origincountry != $destcountry) {
			$api = "IntlRate";
		} else {
			$api = "RateV3";
		}

		$uspsXML = new SimpleXMLElement('<'.$api.'Request USERID="'.$this->GetValue('username').'" />');

		$package = $uspsXML->addChild('Package');
		$package->addAttribute('ID', 0);

		if($api != 'IntlRate') {
			$package->addChild('Service', $this->_service);

			if($this->_service == "FIRST CLASS" || $this->_service == "PARCEL") {
				$package->addChild('FirstClassMailType', 'PARCEL');
			}
		}

		// Get the amount of pounds
		$fractionalPounds = ConvertWeight($this->_weight, 'pounds');
		$pounds = floor($fractionalPounds);

		// Get the amount of ounces for the fractional remainder
		$ounces = round(ConvertWeight($fractionalPounds - $pounds, 'ounces', 'pounds'), 2);

		$weight_xml = sprintf("<Pounds>%s</Pounds>", $pounds);
		$weight_xml .= sprintf("<Ounces>%s</Ounces>", $ounces);

		// International rates require the weight before the mail type
		if($api == "IntlRate") {
			$package->addChild('Pounds', $pounds);
			$package->addChild('Ounces', $ounces);
			$package->addChild('MailType', 'Package');
			$package->addChild('Country', $destcountry);
		}
		// Domestic rates require the destination before the weight
		else {
			$package->addChild('ZipOrigination', $this->_origin_zip);
			$package->addChild('ZipDestination', $this->_destination_zip);
			$package->addChild('Pounds', $pounds);
			$package->addChild('Ounces', $ounces);

			// Which container to use depends on which method was chosen
			switch($this->_service) {
				case "EXPRESS": {
					$containerType = $this->_expressmailcontainertype;
					$containerSize = $this->_expressmailpackagesize;
					break;
				}
				case "FIRST CLASS": {
					$containerType = $this->_firstclasscontainertype;
					$containerSize = $this->_firstclasspackagesize;
					break;
				}
				case "PRIORITY": {
					$containerType = $this->_prioritycontainertype;
					$containerSize = $this->_prioritypackagesize;
					break;
				}
				case "PARCEL": {
					$containerSize = $this->_parcelpostmachpackagesize;
					break;
				}
				case "BPM": {
					$containerSize = $this->_bpmpackagesize;
					break;
				}
				case "LIBRARY": {
					$containerSize = $this->_librarypackagesize;
					break;
				}
				case "MEDIA": {
					$containerSize = $this->_mediapackagesize;
					break;
				}
			}

			if(!empty($containerType)) {
				$containerType = $this->GetContainerType($containerType);
			}
			else {
				$containerType ='';
			}

			$package->addChild('Container', $containerType);

			$containerSize = $this->GetContainerSize($containerSize);
			$package->addChild('Size', $containerSize);

			if($this->_service == "PRIORITY" && $containerSize == "LARGE") {
				$dimensions = $this->Getcombinedshipdimensions();
				$package->addChild('Width', number_format(ConvertLength($dimensions['width'], "in"), 2));
				$package->addChild('Length', number_format(ConvertLength($dimensions['length'], "in"), 2));
				$package->addChild('Height', number_format(ConvertLength($dimensions['height'], "in"), 2));
			}

			// Add the Machinable element if it's a parcel post
			if($this->_service == "PARCEL") {
				$package->addChild('Machinable', 'true');
			}
		}

		// Should we test on the test or production server?
		if($this->GetValue("servertype") == "test") {
			$uspsURL = "http://testing.shippingapis.com/ShippingAPITest.dll";
		}
		else {
			$uspsURL = "http://production.shippingapis.com/ShippingAPI.dll";
		}

		$postVars = array(
			'API' => $api,
			'XML' => $uspsXML->asXML()
		);
		$postVars = http_build_query($postVars);

		$result = postToRemoteFileAndGetResponse($uspsURL, $postVars);

		if(!$result) {
			// Couldn't get to USPS
			$this->SetError(GetLang('USPSOpenError'));
			return false;
		}

		// Parse the XML response from USPS
		$xml = simplexml_load_string($result);
		if(!is_object($xml)) {
			$this->SetError(GetLang('USPSOpenError'));
			return false;
		}

		// Invalid username or access credentials supplied to USPS
		if(isc_strpos($result, "Authorization failure") !== false) {
			$this->SetError(GetLang('USPSAuthError'));
			return false;
		}

		// Return with the error message if the USPS request returned an error
		if(isset($xml->Package->Error)) {
			// Bad quote
			$this->SetError((string)$xml->Package->Error->Description);
			return false;
		}

		// Domestic quote responses return a single shipping quote
		// as we supplied a particular service
		if($api == 'RateV3') {
			$classId = (string)$xml->Package->Postage['CLASSID'];
			$service = $this->GetDomesticServiceByClassId($classId);

			$quote = new ISC_SHIPPING_QUOTE(
				$this->GetId(),
				$this->GetDisplayName(),
				(string)$xml->Package->Postage->Rate,
				$service['description']
			);
			return $quote;
		}

		// International quotes return a series of available shipping services
		// so we need to loop through them and return an array of matching
		// quotes
		$quotes = array();
		$enabledServices = $this->GetIntlServices($this->_service);
		foreach($xml->Package->Service as $service) {
			$attributes = $service->attributes();
			$serviceId = (int)$attributes['ID'];

			// Check if this service is enabled
			if (!in_array($serviceId, $enabledServices)) {
				continue;
			}

			// Create a quote object
			$quotes[] = new ISC_SHIPPING_QUOTE(
				$this->GetId(),
				$this->GetDisplayName(),
				(string)$service->Postage,
				GetLang('USPSIntlService_' . $serviceId)
			);
		}

		if(empty($quotes)) {
			$this->SetError(GetLang('USPSNoShippingMethods'));
			return false;
		}

		return $quotes;
	}

	public function GetServiceQuotes()
	{
		$quoteList = array();

		// check if the countries are supported by USPS
		$origincountry = $this->GetCountry($this->_origin_country['country_iso']);
		if ($origincountry == false) {
			$error = GetLang('USPSCountryNotSupported', array('country' => $this->_origin_country['country_name']));
			$this->SetError($error);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
		}

		$destcountry = $this->GetCountry($this->_destination_country['country_iso']);
		if ($destcountry == false) {
			$error = GetLang('USPSCountryNotSupported', array('country' => $this->_destination_country['country_name']));
			$this->SetError($error);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
			return array();
		}

		if($origincountry != $destcountry) {
			// Next actually retrieve the quote
			$result = $this->GetQuote();
			if(is_array($result) && !empty($result)) {
				$quoteList = $result;
			}
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
				}
			}
			return $quoteList;
		}

		// Is express mail enabled?
		if($this->GetValue("expressmailstatus") == "enabled") {
			$this->_service = "EXPRESS";
			$this->_expressmailcontainertype = $this->GetValue("expressmailcontainertype");
			$this->_expressmailpackagesize = $this->GetValue("expressmailpackagesize");

			// Next actually retrieve the quote
			$result = $this->GetQuote();
			if(is_object($result)) {
				$quoteList[] = $result;
			}
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
				}
			}
		}

		// Is first class enabled?
		if($this->GetValue("firstclassstatus") == "enabled") {
			$this->_service = "FIRST CLASS";
			$this->_firstclasscontainertype = "F";
			$this->_firstclasspackagesize = $this->GetValue("firstclasspackagesize");

			// Next actually retrieve the quote
			$result = $this->GetQuote();
			if(is_object($result)) {
				$quoteList[] = $result;
			}
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
				}
			}
		}

		// Is priority mail enabled?
		if($this->GetValue("prioritymailstatus") == "enabled") {
			$this->_service = "PRIORITY";
			$this->_prioritycontainertype = $this->GetValue("prioritymailcontainertype");
			$this->_prioritypackagesize = $this->GetValue("prioritymailpackagesize");

			// If it's a large box we need to specify dimensions
			if($this->_prioritypackagesize == "L") {
				$this->_prioritycontainertype = "R";
			}

			// Next actually retrieve the quote
			$result = $this->GetQuote();
			if(is_object($result)) {
				$quoteList[] = $result;
			}
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
				}
			}
		}

		// Is parcel post (machinable) enabled?
		if($this->GetValue("parcelpostmachinablestatus") == "enabled") {
			$this->_service = "PARCEL";
			$this->_parcelpostmachpackagesize = $this->GetValue("parcelpostmachinablepackagesize");

			// Next actually retrieve the quote
			$result = $this->GetQuote();
			if(is_object($result)) {
				$quoteList[] = $result;
			}
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
				}
			}
		}

		// Is BPM enabled?
		if($this->GetValue("bpmstatus") == "enabled") {
			$this->_service = "BPM";
			$this->_bpmpackagesize = $this->GetValue("bpmpackagesize");

			// Next actually retrieve the quote
			$result = $this->GetQuote();
			if(is_object($result)) {
				$quoteList[] = $result;
			}
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
				}
			}
		}

		// Is library enabled?
		if($this->GetValue("librarystatus") == "enabled") {
			$this->_service = "LIBRARY";
			$this->_librarypackagesize = $this->GetValue("librarypackagesize");

			// Next actually retrieve the quote
			$result = $this->GetQuote();
			if(is_object($result)) {
				$quoteList[] = $result;
			}
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
				}
			}
		}

		// Is media enabled?
		if($this->GetValue("mediastatus") == "enabled") {
			$this->_service = "MEDIA";
			$this->_mediapackagesize = $this->GetValue("mediapackagesize");

			// Next actually retrieve the quote
			$result = $this->GetQuote();
			if(is_object($result)) {
				$quoteList[] = $result;
			}
			else {
				foreach($this->GetErrors() as $error) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('shipping', $this->GetName()), $this->_service.": " .GetLang('ShippingQuoteError'), $error);
				}
			}
		}

		if(empty($quoteList)) {
			$this->SetError(GetLang('USPSNoShippingMethods'));
		}

		return $quoteList;
	}

	private function GetContainerType($container)
	{
		$result = '';

		switch($container) {
			case "F": {
				$result = "FLAT RATE ENVELOPE";
				break;
			}
			case "B": {
				$result = "FLAT RATE BOX";
				break;
			}
			case "R": {
				$result = "RECTANGULAR";
				break;
			}
			case "N": {
				$result = "NONRECTANGULAR";
				break;
			}
		}

		return $result;
	}

	private function GetContainerSize($size)
	{
		$result = '';

		switch($size) {
			case "R": {
				$result = "Regular";
				break;
			}
			case "L": {
				$result = "Large";
				break;
			}
			case "O": {
				$result = "Oversize";
				break;
			}
		}

		return $result;
	}

	/**
	 * Get a human readable list of of the delivery methods available for the shipping module
	 *
	 * @return array
	 **/
	public function GetAvailableDeliveryMethods()
	{
		$methods = array();

		$domesticServices = $this->GetDomesticServices();
		foreach ($domesticServices as $service) {
			$methods[] = $service['description'];
		}

		// Get the international services
		$intlServices = $this->GetIntlServices();
		foreach ($intlServices as $service) {
			$methods[] = GetLang('USPSIntlService_' . $service);
		}

		$displayName = $this->GetDisplayName();

		foreach ($methods as $key => $method) {
			$methods[$key] = $displayName.' ('.$method.')';
		}

		return $methods;
	}

	public function GetTrackingLink($trackingNumber = "")
	{
		return "http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?strOrigTrackNum=" . urlencode($trackingNumber);
	}

	/**
	* Gets the correct country name for use by USPS. Returns a remapped country if it exists, otherwise returns passed in country
	*
	* @param string country name
	* @return string country name
	*/
	private function GetCountry($country)
	{
		if (isset($this->_mapcountries[$country])) {
			return $this->_mapcountries[$country];
		}

		return false;
	}

	/**
	 * Returns an array of enabled domestic services
	 *
	 * @return array enabled domestic services
	 */
	private function GetDomesticServices()
	{
		static $enabledDomesticServices = null;

		if(!$enabledDomesticServices) {
			$enabledDomesticServices = array();
			$domesticServices = array(
				self::USPS_EXPRESS_SERVICE_TYPE => array(
					'name' => 'Express Mail',
					'status' => $this->GetValue('expressmailstatus'),
					'containerType' => $this->GetValue('expressmailcontainertype'),
				),
				self::USPS_FIRST_CLASS_SERVICE_TYPE => array(
					'name' => 'First Class Mail',
					'status' => $this->GetValue('firstclassstatus'),
					'containerSize' => $this->GetValue('firstclasspackagesize'),
				),
				self::USPS_PRIORITY_SERVICE_TYPE => array(
					'name' => 'Priority Mail',
					'status' => $this->GetValue('prioritymailstatus'),
					'containerType' => $this->GetValue('prioritymailcontainertype'),
				),
				self::USPS_PARCEL_SERVICE_TYPE => array(
					'name' => 'Parcel Post',
					'status' => $this->GetValue('parcelpostmachinablestatus'),
					'containerSize' => $this->GetValue('parcelpostmachinablepackagesize'),
				),
				self::USPS_BPM_SERVICE_TYPE => array(
					'name' => 'Bound Printed Matter',
					'status' => $this->GetValue('bpmstatus'),
				),
				self::USPS_LIBRARY_SERVICE_TYPE => array(
					'name' => 'Library Mail',
					'status' => $this->GetValue('librarystatus'),
					'containerSize' => $this->GetValue('librarypackagesize'),
				),
				self::USPS_MEDIA_SERVICE_TYPE => array(
					'name' => 'Media Mail',
					'status' => $this->GetValue('mediastatus'),
					'containerSize' => $this->GetValue('mediapackagesize'),
				),
			);

			foreach($domesticServices as $serviceType => $service) {
				if($service['status'] == 'enabled') {
					$service['description'] = $this->GetDomesticServiceDescription($service);
					$enabledDomesticServices[$serviceType] = $service;
				}
			}
		}

		return $enabledDomesticServices;
	}

	/**
	 * Returns the domestic service details for the given CLASSID
	 * value, as returned by the RateV3 API.
	 *
	 * @param integer the domestic service CLASSID
	 *
	 * @return array the domestic service
	 */
	private function GetDomesticServiceByClassId($classId)
	{
		static $classIdMap = array(
			0 => self::USPS_FIRST_CLASS_SERVICE_TYPE,
			1 => self::USPS_PRIORITY_SERVICE_TYPE,
			2 => self::USPS_EXPRESS_SERVICE_TYPE,
			3 => self::USPS_EXPRESS_SERVICE_TYPE,
			4 => self::USPS_PARCEL_SERVICE_TYPE,
			5 => self::USPS_BPM_SERVICE_TYPE,
			6 => self::USPS_MEDIA_SERVICE_TYPE,
			7 => self::USPS_LIBRARY_SERVICE_TYPE,
			12 => self::USPS_FIRST_CLASS_SERVICE_TYPE,
			13 => self::USPS_EXPRESS_SERVICE_TYPE,
			16 => self::USPS_PRIORITY_SERVICE_TYPE,
			17 => self::USPS_PRIORITY_SERVICE_TYPE,
			18 => self::USPS_PRIORITY_SERVICE_TYPE,
			19 => self::USPS_FIRST_CLASS_SERVICE_TYPE,
			22 => self::USPS_PRIORITY_SERVICE_TYPE,
			23 => self::USPS_EXPRESS_SERVICE_TYPE,
			25 => self::USPS_EXPRESS_SERVICE_TYPE,
			27 => self::USPS_EXPRESS_SERVICE_TYPE,
			28 => self::USPS_PRIORITY_SERVICE_TYPE,
		);

		$services = $this->GetDomesticServices();
		$classId = (int)$classId;

		if(isset($classIdMap[$classId]) && isset($services[$classIdMap[$classId]])) {
			return $services[$classIdMap[$classId]];
		}

		return null;
	}

	/**
	 * Generates a description for a domestic services consisting of
	 * the service name followed by the container type or size
	 */
	private function GetDomesticServiceDescription($service)
	{
		$displayName = $service['name'];

		if(isset($service['containerType'])) {
			$containerType = $this->getContainerType($service['containerType']);
		}

		if(isset($service['containerSize'])) {
			$containerSize = $this->getContainerSize($service['containerSize']);
		}

		if(!empty($containerType)) {
			$displayName .= ' '.$containerType;
		}

		if(!empty($containerSize)) {
			$displayName .= ' '.$containerSize;
		}

		return $displayName;
	}

	/**
	* Gets an array of enabled international services
	*
	* @return array enabled services
	*/
	private function GetIntlServices($service = "")
	{
		$services = array();
		foreach ($this->internationalTypes as $typeName => $type) {
			if ($service != "" && $typeName != $service) {
				continue;
			}

			$deliveryTypes = $this->GetValue($typeName);
			if($deliveryTypes != '') {
				if (is_array($deliveryTypes)) {
					$services = array_merge($deliveryTypes, $services);
				} else {
					$services[] = $deliveryTypes;
				}
			}
		}
		return $services;
	}

}
