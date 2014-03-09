<?php

	/**
	* Metodo de calculo de costo de envio basado en un porcentaje global que se aplica al subtotal del pedido 
	*/
	class SHIPPING_PERCENTAGE extends ISC_SHIPPING
	{

		/**
		* Variables for the flat rate shipping module
		*/

		/*
			The flat rate per item shipping cost for all orders
		*/
		private $_shippingcost = 0;

		private $rules = array(
			'percentage' => array(),
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
			$this->_name = GetLang('PercentageName');
			$this->_image = "";
			$this->_description = GetLang('PercentageDesc');
			$this->_help = GetLang('PercentageHelp');
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
				'percentage' => array (
					"name" => GetLang('ShippingPercentage'),
					"type" => "textbox",
					"help" => GetLang('DefaultShippingPercentageHelp'),
					"default" => "",
					"required" => false,
					"size" => 7,
					'format' => 'price',
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
			$Error = false;
			$this->_shippingcost = $this->GetCost();
			
			if($this->_shippingcost === false) {
				return false;
			}

			$pi_quote = new ISC_SHIPPING_QUOTE($this->GetId(), $this->getDisplayName(), $this->_shippingcost);
			return $pi_quote;
		}

		/*
		public function getpropertiessheet($tab_id)
		{
			// Load up the module variables
			$this->SetCustomVars();

			$output = $this->ParseTemplate('weight_range_header', true);
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
				$this->LoadPercentageRule();
			}

			if (empty($this->rules['cost'])) {
				$GLOBALS['POS'] = 0;
				$GLOBALS['COST_VAL'] = '';
				$GLOBALS['LOWER_VAL'] = '';
				$GLOBALS['UPPER_VAL'] = '';
				return $this->ParseTemplate('weight_range_row', true);
			}

			$output = '';

			// Sorts the indexes so 0 01 011 0111
			ksort($this->rules['cost']);

			foreach ($this->rules['cost'] as $id => $cost) {
				$GLOBALS['POS'] = $id;
				$GLOBALS['COST_VAL'] = FormatPrice($this->rules['cost'][$id], false, false);

				$GLOBALS['LOWER_VAL'] = '';
				if($this->rules['lower'][$id] !== '') {
					$GLOBALS['LOWER_VAL'] = FormatWeight($this->rules['lower'][$id], false);
				}

				$GLOBALS['UPPER_VAL'] = '';
				if($this->rules['upper'][$id] !== '') {
					$GLOBALS['UPPER_VAL'] = FormatWeight($this->rules['upper'][$id], false);
				}
				$output .= $this->ParseTemplate('weight_range_row', true);
			}

			return $output;
		}

		public function GetJavascript()
		{
			return $this->ParseTemplate('weight_range_javascript', true);
		}
		*/

		private function LoadPercentageRule()
		{
			$query = "SELECT variablename, variableval
			FROM [|PREFIX|]shipping_vars
			WHERE methodid='".$this->methodId."' AND modulename='shipping_percentage'
			AND (
				variablename LIKE 'percentage'
			)";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$this->rules = array (
				'percentage' => array(),
			);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				//list($name, $pos) = explode('_', $row['variablename'], 2);
				$this->rules[$row['variablename']] = $row['variableval'];
			}

			return $this->rules;
		}

		private function GetCost()
		{
			if (empty($this->rules['percentage'])) {
				$this->LoadPercentageRule();
			}

			/*
			$default = $this->GetValue('defaultcost');
			if($default === '') {
				$default = false;
			}
			*/

			if(empty($this->rules['percentage'])) {
				return 0;
			}

			//$keys = array_keys($this->rules['percentage']);
			//$last_rule = max($keys);

			if ($this->rules['percentage'] > 0) {
				return $this->_subtotal * ($this->rules['percentage'] / 100);
			}

			/*
			if (($this->rules['lower'][0] === '' && $this->rules['upper'][0] !== '') && $this->_weight < $this->rules['upper'][0]) {
				return $this->rules['cost'][0];
			} elseif (($this->rules['upper'][$last_rule] === '' && $this->rules['lower'][0] !== '') && $this->_weight >= $this->rules['lower'][$last_rule]) {
				return $this->rules['cost'][$last_rule];
			}

			foreach (array_keys($this->rules['cost']) as $key) {
				if (isset($this->rules['lower'][$key]) && $this->_weight >= $this->rules['lower'][$key] && isset($this->rules['upper'][$key]) && $this->_weight < $this->rules['upper'][$key]) {
					return $this->rules['cost'][$key];
				}
			}
			*/

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
						if($settings[$setting] !== '') {
							$settings[$setting] = DefaultDimensionFormat($settings[$setting]);
						}
						break;
					case 'cost':
						$settings[$setting] = DefaultPriceFormat($settings[$setting]);
						break;
				}
			}
			parent::SaveModuleSettings($settings);
		}
	}
