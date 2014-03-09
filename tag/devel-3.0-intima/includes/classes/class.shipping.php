<?php
	require_once(dirname(__FILE__).'/class.module.php');

	/**
	* The Interspire Shopping Cart shipping base class, used by all shipping modules
	*/
	class ISC_SHIPPING extends ISC_MODULE
	{
		/**
		 * @var boolean Is this shipping provider mutually exclusive (such as flat rate)
		 */
		public $_flatrate = false;

		/**
		* @var array The products whose weight and dimensions will be used to calculate shipping fees
		*/
		protected $_products = array();

		/**
		* @var integer The total weight of all products in the _products array in LBS
		*/
		protected $_weight = 0;

		/**
		* @var boolean Should we show a "Test Shipping Provider" link? Defaults to yes
		*/
		public $_showtestlink = true;

		/**
		* @var string $type The type of module this is
		*/
		protected $type = 'shipping';

		/**
		 * @var int The method ID we're using to fetch settings for this shipping module for.
		 */
		protected $methodId = null;

		/**
		 * @var int The vendor ID we're using to fetch the settings for.
		 */
		protected $vendorId = 0;

		/*
			Which countries of origin is this shipping module available for? Defaults to all
		*/
		protected $_countries = array("all");

		/* ----------------------------------------------------------------------------------
		*  Common shipping variables which will be used by all modules when generating quotes
		*  --------------------------------------------------------------------------------- */
		public $_origin_country = array(
			"country_name" => "",
			"country_iso" => 0
		);

		public $_origin_state = array(
			"state_name" => "",
			"state_iso" => ''
		);

		protected $_origin_zip = "";

		protected $_origin_city = "";

		public $_destination_country = array(
			"country_name" => "",
			"country_iso" => 0
		);

		public $_destination_state = array(
			"state_name" => "",
			"state_iso" => ''
		);

		protected $_destination_zip = "";

		protected $_destination_rescom = "RES";

		protected $_subtotal = 0;

		protected $_height = 400;


		public function __construct()
		{

			// set our default origin settings to the store location
			$this->SetOriginCountry(GetCountryIdByName(GetConfig('CompanyCountry')));
			$this->SetOriginState(GetConfig('CompanyState'));
			$this->SetOriginZip(GetConfig('CompanyZip'));
			$this->SetOriginCity(GetConfig('CompanyCity'));

			parent::__construct();
		}


		/*
			Set the subtotal for the order
		*/
		public function setSubtotal($subtotal)
		{
			$this->_subtotal = $subtotal;
		}

		/*
			Set the origin country details. The country id will be passed in
		*/
		public function SetOriginCountry($countryId)
		{
			$countryInfo = GetCountryInfoById($countryId);
			if(isset($countryInfo['countryname'])) {
				$this->_origin_country['country_name'] = $countryInfo['countryname'];
				$this->_origin_country['country_iso'] = $countryInfo['countryiso2'];
			}
		}

		/*
			Set the origin state details. The state name will be passed in
		*/
		public function SetOriginState($StateName)
		{
			$this->_origin_state['state_name'] = $StateName;
			$this->_origin_state['state_iso'] = GetStateISO2ByName($StateName);
		}

		/*
			Set the origin zip/postcode
		*/
		public function SetOriginZip($Zip)
		{
			$this->_origin_zip = $Zip;
		}

		/*
			Set the origin city
		*/
		public function SetOriginCity($City)
		{
			$this->_origin_city = $City;
		}

		/*
			Set the destination country details. The country id will be passed in
		*/
		public function SetDestinationCountry($countryId)
		{
			$countryInfo = GetCountryInfoById($countryId);
			if(isset($countryInfo['countryname'])) {
				$this->_destination_country['country_name'] = $countryInfo['countryname'];
				$this->_destination_country['country_iso'] = $countryInfo['countryiso2'];
			}
		}

		/*
			Set the destination state details. The state name will be passed in
		*/
		public function SetDestinationState($StateName)
		{
			$this->_destination_state['state_name'] = $StateName;
			$this->_destination_state['state_iso'] = GetStateISO2ByName($StateName);
		}

		/*
			Set the destination zip/postcode
		*/
		public function SetDestinationZip($Zip)
		{
			$this->_destination_zip = $Zip;
		}

		/*
			Set the destination type - residential or commercial.
			Possible values are RES (residential) or COM (commercial)
		*/
		public function SetDestinationType($DestinationType)
		{
			$this->_destination_rescom = $DestinationType;
		}

		/**
		 * Get the display name of the shipping module.
		 */
		public function GetDisplayName()
		{
			if(!is_null($this->methodId)) {
				$method = GetShippingMethodById($this->methodId);
				if(isset($method['methodname'])) {
					return $method['methodname'];
				}
			}
			return $this->GetName();
		}

		/*
			Return the list of origin countries where this shipping module can be used
		*/
		public function getcountries()
		{
			return $this->_countries;
		}

		/*
			Return the height for the popup quote generator window
		*/
		public function getheight()
		{
			return $this->_height;
		}

		/*
			Return the weight of the shipment
		*/
		public function getweight()
		{
			return $this->_weight;
		}

		/**
		* Add an item to be shipped
		*/
		public function additem($weight, $length=0, $width=0, $height=0, $qty=1, $desc="",$cost=0)
		{

			// Each product to be shipped will be added to the _products array.
			// The sum of weight and dimensions will then be used by each shipping provider.
			// We will instantiate a new ISC_SHIPPING_ITEM object for each product.
			$new_product = new ISC_SHIPPING_ITEM($weight, $length, $width, $height, $qty, $desc, $cost);
			array_push($this->_products, $new_product);

			// Update the cumulative weight
			$this->_weight += ($new_product->getweight() * $qty);

			$this->_subtotal += ($cost * $qty);
		}

		protected function CheckEnabled()
		{
			$shipping_methods = explode(",", GetConfig('ShippingMethods'));
			if (in_array($this->GetId(), $shipping_methods)) {
				return true;
			} else {
				return false;
			}
		}

		/*
			Return the number of products in the quote
		*/
		public function getnumproducts()
		{
			return count($this->_products);
		}

		/*
			Return a reference to the products in the quote
		*/
		public function getproducts()
		{
			return $this->_products;
		}

		/*
			Return a HTML-formatted list of properties for this shipping module
		*/
		public function getpropertiessheet($tab_id)
		{

			$this->tabId = $tab_id;

			$GLOBALS['ShippingJavaScript'] = "";
			$GLOBALS['HelpText'] = $this->gethelptext();
			$GLOBALS['HelpIcon'] = "success";
			$GLOBALS['Properties'] = "";
			$GLOBALS['ShipperId'] = $this->GetName();

			$mod_dir = str_replace($this->type.'_', '', $this->GetId());

			$GLOBALS['HideSelectAllLinks'] = 'display: none';

			// Add the logo
			$image = $this->GetImage();
			if ($image != "") {
				$GLOBALS['HelpTip'] = "";
				$GLOBALS['PropertyBox'] = sprintf("<img style='margin-top:5px' src='%s' />", $this->GetImage());
				$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
			}

			foreach ($this->GetCustomVars() as $id => $var) {
				$GLOBALS['PropertyBox'] = "";
				$GLOBALS['PropertyName'] = $var['name'] . ":";
				$GLOBALS['HelpTip'] = "";
				$GLOBALS['FieldId'] = $this->GetId().'_'.$id;

				if($var['type'] == 'dropdown' && isset($var['multiselect']) && $var['multiselect'] == true) {
					$GLOBALS['HideSelectAllLinks'] = '';
				}
				else {
					$GLOBALS['HideSelectAllLinks'] = 'display: none';
				}

				$GLOBALS['PropertyBox'] = $this->_buildformitem($id, $var, false);
				$help_id = rand(1000,100000);

				if ($var['help'] != "") {
					$GLOBALS['HelpTip'] = sprintf("<img onmouseout=\"HideHelp('d%d')\" onmouseover=\"ShowHelp('d%d', '%s', '%s')\" src=\"images/help.gif\" width=\"24\" height=\"16\" border=\"0\"><div style=\"display:none\" id=\"d%d\"></div>", $help_id, $help_id, $var['name'], $var['help'], $help_id);
				}

				$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
			}

			$GLOBALS['ShippingJavaScript'] .= $GLOBALS['ValidationJavascript'];

			// First check if the shipping provider is configured.
			$configured = false;
			if(!empty($this->moduleVariables)) {
				$configured = true;
			}

			// Add the test connection link
			if ($this->_showtestlink) {
				$GLOBALS['HideSelectAllLinks'] = 'display: none';
				if ($configured) {
					$GLOBALS['PropertyBox'] = sprintf("<a href='javascript:void(0)' onclick='openwin(\"index.php?ToDo=testShippingProvider&methodId=%s\", \"%s\", 500, %s)'>%s</a>", $this->methodId, $this->GetId(), $this->getheight(), GetLang('GetShippingQuote'));
				} else {
					$GLOBALS['PropertyBox'] = sprintf("<a href='javascript:void(0)' onclick='alert(\"%s\")'>%s</a>", GetLang('ShippingProviderNotSetup'), GetLang('TestShippingProvider'));
				}

				$help_id = rand(1000,100000);
				$GLOBALS['PropertyName'] = "";
				$GLOBALS['Required'] = "";
				$GLOBALS['PanelBottom'] = "PanelBottom";
				$GLOBALS['HelpTip'] = sprintf("<img onmouseout=\"HideHelp('d%d')\" onmouseover=\"ShowHelp('d%d', '%s', '%s')\" src=\"images/help.gif\" width=\"24\" height=\"16\" border=\"0\"><div style=\"display:none\" id=\"d%d\"></div>", $help_id, $help_id, GetLang('TestShippingProvider'), GetLang('TestShippingProviderHelp'), $help_id);

				$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
			}

			if (empty($this->_variables)) {
				// Hide the heading of the property sheet if there aren't any properties
				$GLOBALS['HidePropSheet'] = "none";
			}
			else {
				$GLOBALS['HidePropSheet'] = "";
			}


			$sheet = Interspire_Template::getInstance('admin')->render('module.propertysheet.tpl');
			return $sheet;
		}

		/**
		*	Workout the box size required hold all products it there's more than one product.
		*	We will calculated the largest width and length of an item and sum the heights to
		*	produce the appropriate box size for shipping calculations
		*/
		public function getcombinedshipdimensions()
		{

			$dimensions = array("width" => 0,
								"height" => 0,
								"length" => 0
			);

			$factoringDimension = GetConfig('ShippingFactoringDimension');
			if ($factoringDimension == 'depth') {
				$factoringDimension = 'length';
			}

			foreach ($this->_products as $product) {
				$prodDimensions = array(
					'width' => $product->getwidth(),
					'height' => $product->getheight(),
					'length' => $product->getlength()
				);

				$dimensions[$factoringDimension] += $prodDimensions[$factoringDimension] * $product->getquantity();
				unset($prodDimensions[$factoringDimension]);

				foreach ($prodDimensions as $key => $dim) {
					$dimensions[$key] = max($dimensions[$key], $dim);
				}
			}

			return $dimensions;
		}

		/**
		*	Some shipping providers allow tracking of packages online. By default we will assume
		*	they don't and just return an empty link. Those that do will override this function
		*	and return the tracking link.
		*
		* @param $trackingNumber An optional tracking number to construct the tracking link
		*/
		public function GetTrackingLink($trackingNumber = "")
		{
			return "";
		}

		/**
		 * Set the shipping method ID we'll be using. We use this to fetch the settings for this particular method.
		 */
		public function SetMethodId($methodId)
		{
			$this->methodId = $methodId;
			$this->loadedVars = false;
			$this->moduleVariables = array();

			$shippingMethod = GetShippingMethodById($methodId);
			// is this a vendor specific shipping method? load vendor origin details
			if ($shippingMethod['methodvendorid']) {
				$this->SetOriginByVendorId($shippingMethod['methodvendorid']);
			}
		}

		/**
		* Sets the origin location of the shipping module to the location of the vendor
		*
		* @param int The vendor ID to load the location details for
		*/
		public function SetOriginByVendorId($vendorId)
		{
			if ($vendorId) {
				$query = "SELECT * FROM [|PREFIX|]vendors WHERE vendorid = '" . (int)$vendorId . "'";
				$vendorResult = $GLOBALS['ISC_CLASS_DB']->Query($query);
				if ($vendorData = $GLOBALS['ISC_CLASS_DB']->Fetch($vendorResult)) {
					$this->SetOriginCountry(GetCountryIdByName($vendorData['vendorcountry']));
					$this->SetOriginState($vendorData['vendorstate']);
					$this->SetOriginZip($vendorData['vendorzip']);
					$this->SetOriginCity($vendorData['vendorcity']);
				}
			}
		}

		/**
		 * Load any custom variables for the module.
		 */
		public function LoadCustomVars()
		{
			if($this->methodId === null) {
				trigger_error('Before calling LoadCustomVars() on a shipping module, call SetMethodId first.', E_USER_ERROR);
				return;
			}

			$this->loadedVars = true;

			$vars = LoadShippingVars($this->methodId, $this->GetId());
			foreach($vars as $row) {
				$varName = str_replace($row['modulename'] . "_", "", $row['variablename']);

				if(isset($this->moduleVariables[$varName])) {
					if(!is_array($this->moduleVariables[$varName])) {
						$this->moduleVariables[$varName] = array($this->moduleVariables[$varName]);
					}
					$this->moduleVariables[$varName][] = $row['variableval'];
				}
				else {
					$this->moduleVariables[$varName] = $row['variableval'];
				}
			}
		}

		/**
		 * Get a human readable list of of the delivery methods available for the shipping module
		 *
		 * @return array
		 **/
		public function GetAvailableDeliveryMethods()
		{
			return array($this->GetDisplayName());
		}

		/**
		 * Save the configuration variables for this module that come in from the POST
		 * array.
		 *
		 * @param array An array of configuration variables.
		 * @return boolean True if successful.
		 */
		public function SaveModuleSettings($settings=array())
		{
			// Delete any current settings the module has
			$this->DeleteModuleSettings();

			// Insert the new settings
			if(empty($settings)) {
				return true;
			}

			$shippingMethod = GetShippingMethodById($this->methodId);

			// Mark the module as being configured
			$newVar = array(
				'zoneid'		=> $shippingMethod['zoneid'],
				'methodid'		=> $this->methodId,
				'modulename'	=> $this->GetId(),
				'variablename'	=> 'is_setup',
				'variableval'	=> 1,
				'varvendorid'	=> $shippingMethod['methodvendorid']
			);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery("shipping_vars", $newVar);

			$moduleVariables = $this->GetCustomVars();

			// Loop through the options that this module has
			foreach($settings as $name => $value) {
				$format = '';
				if(isset($moduleVariables[$name]['format'])) {
					$format = $moduleVariables[$name]['format'];
				}
				if(is_array($value)) {
					foreach($value as $childValue) {

						// Specifically set it to empty if we have an empty value and if its not required
						if($value == '' && isset($moduleVariables[$name]['required']) && !$moduleVariables[$name]['required']) {
							$value = '';
						} else {
							switch($format) {
								case 'price':
									$value = DefaultPriceFormat($childValue);
									break;
								case 'weight':
								case 'dimension':
									$value = DefaultDimensionFormat($value);
									break;
							}
						}
						// Mark the module as being configured
						$newVar = array(
							'zoneid'		=> $shippingMethod['zoneid'],
							'methodid'		=> $this->methodId,
							'modulename'	=> $this->GetId(),
							'variablename'	=> $name,
							'variableval'	=> $childValue,
							'varvendorid'	=> $shippingMethod['methodvendorid']
						);
						$GLOBALS['ISC_CLASS_DB']->InsertQuery("shipping_vars", $newVar);

					}
				}
				else {
					// Specifically set it to empty if we have an empty value and if its not required
					if($value == '' && isset($moduleVariables[$name]['required']) && !$moduleVariables[$name]['required']) {
						$value = '';
					} else {
						switch($format) {
							case 'price':
								$value = DefaultPriceFormat($value);
								break;
							case 'weight':
							case 'dimension':
								$value = DefaultDimensionFormat($value);
								break;
						}
					}
					// Mark the module as being configured
					$newVar = array(
						'zoneid'		=> $shippingMethod['zoneid'],
						'methodid'		=> $this->methodId,
						'modulename'	=> $this->GetId(),
						'variablename'	=> $name,
						'variableval'	=> $value,
						'varvendorid'	=> $shippingMethod['methodvendorid']
					);
					$GLOBALS['ISC_CLASS_DB']->InsertQuery("shipping_vars", $newVar);

				}
			}

			return true;
		}

		/**
		 * Delete all of the configuration/settings associated with this module.
		 *
		 */
		public function DeleteModuleSettings()
		{
			// Delete the existing settings for this module
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_vars', "WHERE methodid='".$this->methodId."'");
		}
	}