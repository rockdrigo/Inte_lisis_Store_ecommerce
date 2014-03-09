<?php

	/**
	* This is the flat rate per item shipping module for Interspire Shopping Cart. To enable
	* flat rate per item shipping in Interspire Shopping Cart login to the control panel and click the
	* Settings -> Shipping Settings tab in the menu.
	*/
	class SHIPPING_BYTOTAL extends ISC_SHIPPING
	{

		/**
		* Variables for the flat rate shipping module
		*/

		/*
			The flat rate per item shipping cost for all orders
		*/
		private $_shippingcost = 0;

		private $rules = array(
			'cost' => array(),
			'upper' => array(),
			'lower' => array(),
		);
		/**
		* Functions for the flat rate shipping module
		*/

		/*
			Shipping class constructor
		*/
		public function __construct()
		{

			// Setup the required variables for the flat rate per item shipping module
			parent::__construct();
			$this->_name = GetLang('ByTotalName');
			$this->_image = "";
			$this->_description = GetLang('ByTotalDesc');
			$this->_help = GetLang('ByTotalHelp');
			$this->_height = 0;
			$this->_showtestlink = false;
			$this->_flatrate = true;

			// Flat rate is available worldwide
			$this->_countries = array("all");
		}

		/**
		* Custom variables for the shipping module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{

			$this->_variables = array (
				'defaultcost' => array (
					"name" => GetLang('DefaultShippingCost'),
					"type" => "textbox",
					"help" => GetLang('DefaultShippingCostHelp'),
					"default" => "",
					"required" => false,
					"size" => 7,
					'format' => 'price',
				),
				'placeholder' => array (
					'name' => 'Total Ranges',
					'type' => 'custom',
					'callback' => 'BuildForm',
					'default' => '',
					'required' => false,
					'help' => '',
					'javascript' => '',
				),
			);

		}

		public function GetQuote()
		{
			// The following array will be returned to the calling function.
			// It will contain at one ISC_SHIPPING_QUOTE object

			$pi_quote = array();

			// Workout the cost by multiplying peritemcost * numproducts
			$num_items = 0;

			// Create a quote object
			$this->_shippingcost = $this->GetCost();
			if($this->_shippingcost === false) {
				return false;
			}
			$pi_quote = new ISC_SHIPPING_QUOTE($this->GetId(), $this->getDisplayName(), $this->_shippingcost);
			return $pi_quote;
		}

		public function getpropertiessheet($tab_id)
		{
			// Load up the module variables
			$this->SetCustomVars();

			$output = $this->ParseTemplate('total_range_header', true);
			$this->_variables['placeholder']['javascript'] = $this->GetJavascript();

			$output .= parent::getpropertiessheet($tab_id);
			return $output;
		}

		public function BuildForm()
		{

			if (GetConfig('CurrencyLocation') === 'left') {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			} else {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			}

			if (empty($this->rules['cost'])) {
				$this->LoadTotalRanges();
			}

			if (empty($this->rules['cost'])) {
				$GLOBALS['POS'] = 0;
				$GLOBALS['COST_VAL'] = '';
				$GLOBALS['LOWER_VAL'] = '';
				$GLOBALS['UPPER_VAL'] = '';
				return $this->ParseTemplate('total_range_row', true);
			}

			$output = '';

			// Sorts the indexes so 0 01 011 0111
			ksort($this->rules['cost']);

			foreach ($this->rules['cost'] as $id => $cost) {
				$GLOBALS['POS'] = $id;
				$GLOBALS['COST_VAL'] = FormatPrice($this->rules['cost'][$id], false, false);

				$GLOBALS['LOWER_VAL'] = '';
				if($this->rules['lower'][$id] !== '') {
					$GLOBALS['LOWER_VAL'] = FormatPrice($this->rules['lower'][$id], false, false);
				}

				$GLOBALS['UPPER_VAL'] = '';
				if($this->rules['upper'][$id] !== '') {
					$GLOBALS['UPPER_VAL'] = FormatPrice($this->rules['upper'][$id], false, false);
				}

				$output .= $this->ParseTemplate('total_range_row', true);
			}

			return $output;
		}

		public function GetJavascript()
		{
			return $this->ParseTemplate('total_range_javascript', true);
		}

		private function LoadTotalRanges()
		{
			$query = "SELECT variablename, variableval
			FROM [|PREFIX|]shipping_vars
			WHERE methodid='".$this->methodId."' AND modulename='shipping_bytotal'
			AND (
				variablename LIKE 'cost_%'
				OR variablename LIKE 'lower_%'
				OR variablename LIKE 'upper_%'
			)";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$this->rules = array (
				'cost' => array(),
				'lower' => array(),
				'upper' => array(),
			);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				list($name, $pos) = explode('_', $row['variablename'], 2);
				$this->rules[$name][$pos] = $row['variableval'];
			}

			return $this->rules;
		}

		private function GetCost()
		{
			if (empty($this->rules['cost'])) {
				$this->LoadTotalRanges();
			}

			$default = $this->GetValue('defaultcost');
			if($default === '') {
				$default = false;
			}

			$keys = array_keys($this->rules['cost']);
			$last_rule = max($keys);

			if (($this->rules['lower'][0] === '' && $this->rules['upper'][0] !== '') && $this->_subtotal < $this->rules['upper'][0]) {
				return $this->rules['cost'][0];
			} elseif (($this->rules['upper'][$last_rule] === '' && $this->rules['lower'][0] !== '') && $this->_subtotal >= $this->rules['lower'][$last_rule]) {
				return $this->rules['cost'][$last_rule];
			}

			for ($i = 0; $i < $last_rule+1; $i++) {
				if (isset($this->rules['lower'][$i]) && $this->_subtotal >= $this->rules['lower'][$i] && isset($this->rules['upper'][$i]) && $this->_subtotal < $this->rules['upper'][$i]) {
					return $this->rules['cost'][$i];
				}
			}

			return $default;

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
			foreach(array_keys($settings) as $setting) {
				list($fieldType,) = explode('_', $setting, 2);
				switch($fieldType) {
					case 'upper':
					case 'lower':
					case 'cost':
						if($settings[$setting] !== '') {
							$settings[$setting] = DefaultPriceFormat($settings[$setting]);
						}
						break;
				}
			}
			parent::SaveModuleSettings($settings);
		}

	}