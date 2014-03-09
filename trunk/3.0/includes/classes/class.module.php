<?php

class ISC_MODULE
{
	/**
	 * @var string A unique id for this particular module (set automatically)
	 */
	protected $id = '';

	/**
	 * @var string The name of this module.
	 */
	protected $name = '';

	/**
	 * @var int The ID of the tab used to build the settings for this module.
	 */
	protected $tabId = 1;

	/**
	 * @var array A list of variables that this module has.
	 */
	protected $_variables = array();

	/**
	 * @var string The description of this module.
	 */
	protected $description = '';

	/**
	 * @var string The image to show for this module.
	 */
	protected $image = '';

	/**
	 * @var string The help text for this module.
	 */
	protected $help = '';

	/**
	 * @var string The type of help text to show (info or help)
	 */
	protected $helpType = 'help';

	/**
	 * @var string The type of module this is (analytics, checkout, shipping or notification)
	 */
	protected $type = '';

	/**
	 * @var string Any error messages set for this module.
	 */
	protected $errors = array();

	/**
	 * @var array Array of module variables for this module.
	 */
	protected $moduleVariables = array();

	/**
	* @var boolean Are the module variables loaded from the database
	*/
	protected $loadedVars = false;

	/**
	 * @var Interspire_Template Instance of the template class used to parse templates for this module.
	 */
	protected $template;

	/**
	 * The constructor will load any language variables for this module
	 *
	 */
	public function __construct()
	{
		$this->SetId(strtolower(get_class($this)));
		$this->LoadLanguageFile();
	}

	/**
	 * Return the ID of this module.
	 *
	 * @return string The ID of this module.
	 */
	public function GetId()
	{
		return $this->id;
	}

	/**
	 * Set the ID of this module.
	 *
	 * @param string The ID to set this module as.
	 */
	protected function SetId($Id)
	{
		$this->id = $Id;
	}

	/**
	 * Sets the type of this module.
	 *
	 * @param string $type
	 * @return void
	 */
	protected function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * Get the friendly name of this module.
	 *
	 * @return string The friendly name of this module.
	 */
	public function GetName()
	{
		// Kept for backwards compatibility
		if(!empty($this->_name)) {
			return $this->_name;
		}

		return $this->name;
	}

	/**
	 * Set the friendly name of this module.
	 *
	 * @param string The friendly name to set this module to.
	 */
	protected function SetName($Name)
	{
		$this->name = $Name;
	}

	/**
	 * Get the description of this module.
	 *
	 * @return string The description of this module.
	 */
	public function GetDescription()
	{
		// Kept for backwards compatibility
		if(isset($this->_description)) {
			return $this->_description;
		}

		return $this->description;
	}

	/**
	 * Set the friendly name of this module.
	 *
	 * @param string The description to set this module to.
	 */
	protected function SetDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Checks if this module is enabled or not.
	 *
	 * @return boolean True if the module is enabled, false if not.
	 */
	public function IsEnabled()
	{
		if(method_exists($this, '_CheckEnabled')) {
			return $this->_CheckEnabled();
		}
		else if(method_exists($this, 'CheckEnabled')) {
			return $this->CheckEnabled();
		}
	}

	/**
	 * Check if this module is supported or not.
	 *
	 * @return boolean True if supported, false if unsupported.
	 */
	public function IsSupported()
	{
		// In the base class, assume all are supported.
		return true;
	}

	/**
	 * Get the help text to show for this module.
	 *
	 * @return string The help text to show for this module.
	 */
	public function GetHelpText()
	{
		// Kept for backwards compatibility
		if(isset($this->_help)) {
			return $this->_help;
		}

		return $this->help;
	}

	/**
	 * Set the help text to show for this module.
	 *
	 * @param string The text to set as the help text for this module.
	 * @param string The type of help text that this is. Set to 'info' to show the yellow info message.
	 */
	protected function SetHelpText($HelpText, $type='')
	{
		$this->help = $HelpText;
		if($type != '') {
			$this->helpType = $type;
		}
	}

	/**
	 * Get the type of help text we're showing.
	 *
	 * @return string The type of help text
	 */
	public function GetHelpTextType()
	{
		return $this->helpType;
	}

	/**
	 * Specify an image to use for this module's logo.
	 *
	 * @param string The filename of the image (relative to the folder for this module)
	 */
	protected function SetImage($ImageFile)
	{
		$this->image = $ImageFile;
	}

	/**
	 * Return the image to show as this module's logo.
	 *
	 * @return string The image file name for this module.
	 */
	public function GetImage()
	{
		// Kept for backwards compatibility
		if(isset($this->_image)) {
			$this->image = $this->_image;
		}

		if(!$this->image) {
			return '';
		}

		$image = $this->GetImagePath(false).$this->image;
		if(file_exists(ISC_BASE_PATH.$image)) {
			return GetConfig('ShopPath').$image;
		}
		else {
			return '';
		}
	}

	/**
	 * Returns the path to the module's images directory.
	 * @var boolean True to return a fully qualified url path
	 */
	public function GetImagePath($fullPath=true)
	{
		$idBits = explode('_', $this->GetId(), 2);
		if($idBits[0] == 'addon') {
			$imagePath = '/addons';
		}
		else {
			$imagePath = '/modules/'.$idBits[0];
		}
		$imagePath .= '/'.$idBits[1];
		if($idBits[0] != 'addon') {
			$imagePath .= '/images';
		}

		if($fullPath){
			$imagePath = GetConfig('ShopPath').$imagePath;
		}

		return $imagePath.'/';
	}

	/**
	 * Load the language file for this module in to the global language scope.
	 * Any language variable sthat conflict with existing language variables will be ignored.
	 */
	protected function LoadLanguageFile()
	{
		if (!isset($this->id) || empty($this->id)) {
			return;
		}

		if (!isset($this->type) || empty($this->type)) {
			return;
		}

		$lang = 'en';

		if (strpos(GetConfig('Language'), '/') === false) {
			$lang = GetConfig('Language');
		}

		$mod_id = str_replace($this->type.'_', '', $this->id);

		if($this->type == 'addon') {
			$directory = ISC_BASE_PATH.'/addons/';
		}
		else {
			$directory = ISC_BASE_PATH.'/modules/'.$this->type.'/';
		}

		$lang_file = $directory.$mod_id.'/lang/'.$lang.'/language.ini';

		// Try and fall back to english if the module hasn't been translated yet
		if (!is_file($lang_file)) {
			$lang_file = $directory.$mod_id.'/lang/en/language.ini';
		}

		if (!is_file($lang_file)) {
			return;
		}

		ParseLangFile($lang_file);
	}

	/**
	 * Return an instance of the template system bound to this particular module.
	 *
	 * @return Interspire_Template Instance of the template system (Interspire_Template)
	 */
	public function getTemplateClass()
	{
		$mod_id = str_replace($this->type.'_', '', $this->id);

		if($this->type == 'addon') {
			$templateDir = ISC_BASE_PATH.'/addons/'.$mod_id.'/templates/';
		}
		else {
			$templateDir = ISC_BASE_PATH.'/modules/'.$this->type.'/'.$mod_id.'/templates/';
		}

		$template = Interspire_Template::getInstance('module_'.$this->type.'_'.$mod_id, $templateDir, array(
			'cache' => getAdminTwigTemplateCacheDirectory(),
			'auto_reload' => true
			)
		);

		return $template;
	}

	/**
	 * Gets & parses a module specific template. This has its own method because module specific templates are
	 * stored independently of the rest of the store.
	 *
	 * @param string The name of the template.
	 * @param boolean True to return the template, false to output.
	 */
	public function ParseTemplate($template, $return = false)
	{
		$class = $this->getTemplateClass();
		if($return) {
			return $class->render($template.'.tpl');
		}

		$class->display($template.'.tpl');
	}

	/**
	 * Set an error message for this particular module.
	 *
	 * @param string The message to be set.
	 */
	protected function SetError($message)
	{
		$this->errors[] = $message;
	}

	/**
	 * Retrieve any error messages that this module has set.
	 *
	 * @return array Array of error messages.
	 */
	public function GetErrors()
	{
		return $this->errors;
	}

	/**
	 * Does this module have any errors set?
	 *
	 * @return boolean True if there are errors. False if not.
	 */
	public function HasErrors()
	{
		if(empty($this->errors)) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Reset the list of errors back to an empty array.
	 */
	public function ResetErrors()
	{
		$this->errors = array();
	}

	/**
	 * Get a list of the configurable variables for this module.
	 */
	public function GetCustomVars()
	{
		$this->SetCustomVars();
		return $this->_variables;
	}

	/**
	 * Set up any custom variables for the module.
	 */
	public function SetCustomVars()
	{
		return true;
	}

	/**
	 * Load any custom variables for the module.
	 */
	public function LoadCustomVars()
	{
		$this->loadedVars = true;

		$this->moduleVariables = array();

		$moduleBits = explode("_", $this->GetId(), 2);
		// First try to load a cached version of this module's settings
		$cachedModuleVars = $GLOBALS['ISC_CLASS_DATA_STORE']->Read(ucfirst($moduleBits[0]).'ModuleVars');
		if($cachedModuleVars !== false && isset($cachedModuleVars[$this->GetId()])) {
			$cachedModuleSettings = $cachedModuleVars[$this->GetId()];
			foreach($cachedModuleSettings as $varName => $varValue) {
				$this->moduleVariables[$varName] = $varValue;
			}
		}

		// Otherwise, fall back to the default
		else {
			$query = "SELECT * FROM [|PREFIX|]module_vars WHERE modulename='".$GLOBALS['ISC_CLASS_DB']->Quote($this->GetId())."'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
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
	}

	/**
	* Set a value of a variable for a module - does not update the database and is primarily used for test harnesses.
	*
	* @param string $varName
	* @param mixed $value
	* @param bool $loaded If true (default) will also mark vars for this module as 'loaded' so as not to attempt to query the database
	* @return void
	*/
	public function SetValue($varName, $value, $loaded = true)
	{
		if ($loaded) {
			$this->loadedVars = true;
		}

		$this->moduleVariables[$varName] = $value;
	}

	/**
	 * Get the value of a variable for a module
	 *
	 * @param string The name of the variable to get
	 * @return mixed The value of the variable or NULL if the value wasn't found
	 */
	public function GetValue($varName)
	{
		if (!$this->loadedVars) {
			$this->LoadCustomVars();
		}

		if(isset($this->moduleVariables[$varName])) {
			return $this->moduleVariables[$varName];
		}

		return null;
	}

	/**
	 * Log a message of status Debug to the Shopping Cart system log
	 *
	 * @param mixed The var to log.
	 * @param boolean Should we escape the html in the var
	 *
	 * @return void
	 **/
	public function DebugLog($var='', $escape=true)
	{
		$output = print_r($var, true);

		if ($escape) {
			$output = isc_html_escape($output);
		}

		$trace = debug_backtrace();
		$last_call = array_shift($trace);

		$called_from = 'Called from file '.str_replace(ISC_BASE_PATH, '', $last_call['file']).' at line '.$last_call['line']."<br />\n";

		$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug(array('payment', $this->_name), 'DEBUG: '.$called_from.'<pre>'."\n".$output."\n</pre>\n");
	}

	/**
	 * Build the HTML form item for each module variable.
	 *
	 * @param string The identifier for this
	 */
	protected function _BuildFormItem($id, &$var, $useTabs=true, $moduleId='')
	{
		// What type of variable is it?
		$item = "";

		if(!$moduleId) {
			$moduleId = $this->GetId();
		}

		if(!isset($GLOBALS['ValidationJavascript'])) {
			$GLOBALS['ValidationJavascript'] = '';
		}

		if($useTabs == true) {
			$showTab = "ShowTab(".$this->tabId.");";
		}
		else {
			$showTab = '';
		}

		if(!isset($var['type'])) {
			return '';
		}

		switch ($var['type']) {
			case "blank": {
				$item = "";
				$GLOBALS['Required'] = "";
				break;
			}
			case "label": {
				$item = $var['label'];
				$GLOBALS['Required'] = "&nbsp;&nbsp;";
				break;
			}
			case "custom": {
				$item = '';
				if (method_exists($this, $var['callback'])) {
					$item = call_user_func(array($this, $var['callback']), $id);
				}
				if(isset($var['javascript'])) {
					$GLOBALS['ValidationJavascript'] .= $var['javascript'];
				}

				if (isset($var['required']) && $var['required']) {
					$GLOBALS['Required'] = "<span class=\"Required\">*</span>";
				} else {
					$GLOBALS['Required'] = "&nbsp;&nbsp;";
				}

				break;
			}
			case "checkbox":
				$default = false;
				if (isset($var['default']) && $var['default'] != "") {
					$default = $var['default'];
				}

				if($this->GetValue($id)) {
					$default = $this->GetValue($id);
				}

				$checked = "";
				if ($default) {
					$checked = 'checked="checked"';
				}

				$txtName = $moduleId."[".$id."]";
				$txtId = $moduleId."_".$id;

				$item = '<input type="checkbox" name="' . $txtName . '" id="' . $txtId . '" value="1" ' . $checked . '/>';

				$GLOBALS['Required'] = "&nbsp;&nbsp;";

				if (isset($var['label']) && $var['label']) {
					$item = "<label>" . $item . $var['label'] . "</label>";
				}
				break;
			case 'text':
			case "textbox":
			case "password":

				$default = "";

				if (isset($var['default']) && $var['default'] != "") {
					$default = $var['default'];
				}

				if($this->GetValue($id)) {
					$default = $this->GetValue($id);
				}


				if(isset($var['format']) && $default !== '') {
					switch($var['format']) {
						case 'price':
							$default = FormatPrice($default, false, false);
							break;
						case 'weight':
						case 'dimension':
							$default = FormatWeight($default, false);
							break;
					}
				}

				$default = isc_html_escape($default);

				if (isset($var['size'])) {
					$txt_size = $var['size'];
					$txtClass = "Field";
				}
				else {
					$txt_size = "";
					$txtClass = "Field250";
				}

				if (isset($var['prefix'])) {
					$txtPrefix = $var['prefix'] . " ";
				} else {
					$txtPrefix = "";
				}

				if (isset($var['suffix'])) {
					$txtSuffix = ' '.$var['suffix'];
				} else {
					$txtSuffix = '';
				}

				if($var['type'] == 'password') {
					$type = 'password';
				}
				else {
					$type = 'text';
				}

				$readOnly = '';
				if(isset($var['readonly']) && $var['readonly'] == true) {
					$readOnly = 'readonly="readonly"';
				}

				$txtName = $moduleId."[".$id."]";
				$txtId = $moduleId."_".$id;
				$item = $txtPrefix."<input type=\"".$type."\" class=\"".$txtClass."\" name=\"".$txtName."\" id=\"".$txtId."\" value=\"".isc_html_escape($default)."\" size=\"".$txt_size."\" ".$readOnly." />".$txtSuffix;

				if (isset($var['required']) && $var['required']) {
					$GLOBALS['Required'] = "<span class=\"Required\">*</span>";
				} else {
					$GLOBALS['Required'] = "&nbsp;&nbsp;";
				}

				if (isset($var['required']) && $var['required']) {
					$message = addslashes(sprintf(GetLang('EnterValueForField'), $var['name']));
					$GLOBALS['ValidationJavascript'] .= "
						if(!$('#".$txtId."').val()) {
							".$showTab."
							alert('".$message."');
							$('#".$txtId."').focus();
							return false;
						}
					";
				}

				break;
			case "textarea": {
				$default = "";

				if ($var['default'] != "") {
					$default = $var['default'];
				}

				if($this->GetValue($id)) {
					$default = $this->GetValue($id);
				}

				if(isset($var['format']) && $default !== '') {
					switch($var['format']) {
						case 'price':
							$default = FormatPrice($default, false, false);
							break;
						case 'weight':
						case 'dimension':
							$default = FormatWeight($default, false);
							break;
					}
				}

				$default = isc_html_escape($default);

				if(isset($var['rows'])) {
					$txtRows = $var['rows'];
				}
				else {
					$txtRows = 5;
				}

				if(isset($var['prefix'])) {
					$txtPrefix = $var['prefix'] . " ";
				}
				else {
					$txtPrefix = "";
				}

				$txtName = sprintf("%s[%s]", $moduleId, $id);
				$txtId = sprintf("%s_%s", $moduleId, $id);
				if(!isset($var['class']) || $var['class'] == '') {
					$txtClass = "Field250";
				} else {
					$txtClass = $var['class'];
				}
				$item = sprintf("%s<textarea class='%s' name='%s' id='%s' rows='%d'>%s</textarea>", $txtPrefix, $txtClass, $txtName, $txtId, $txtRows, $default);

				if($var['required']) {
					$GLOBALS['Required'] = "<span class=\"Required\">*</span>";
				}
				else {
					$GLOBALS['Required'] = "&nbsp;&nbsp;";
				}

				if($var['required']) {
					$message = addslashes(sprintf(GetLang('EnterValueForField'), $var['name']));
					$GLOBALS['ValidationJavascript'] .= "
						if(!$('#".$txtId."').val()) {
							".$showTab."
							alert('".$message."');
							$('#".$txtId."').focus();
							return false;
						}
					";
				}

				break;
			}
			case "dropdown": {
				$additionalClass = '';
				if (isset($var['multiselect']) && $var['multiselect']) {
					if (isset($var['multiselectheight'])) {
						$multiSelect = sprintf("multiple size='%s'", $var['multiselectheight']);
					}
					else {
						$multiSelect = "multiple size='7'";
					}
					$additionalClass = "ISSelectReplacement";
				}
				else {
					$multiSelect = "";
				}

				if ($multiSelect) {
					$selName = sprintf("%s[%s][]", $moduleId, $id);
				}
				else {
					$selName = sprintf("%s[%s]", $moduleId, $id);
				}

				$selId = sprintf("%s_%s", $moduleId, $id);

				$item = sprintf("<select %s class='Field250 %s' name='%s' id='%s'>", $multiSelect, $additionalClass, $selName, $selId);

				if ($var['required']) {
					$GLOBALS['Required'] = "<span class=\"Required\">*</span>";
				} else {
					$GLOBALS['Required'] = "&nbsp;&nbsp;";
				}

				$default = '';
				if(isset($var['default'])) {
					$default = $var['default'];
				}

				if($this->GetValue($id)) {
					$default = $this->GetValue($id);
				}

				if(!is_array($default)) {
					$default = array($default);
				}

				// Loop through each of the options
				foreach ($var['options'] as $k => $v) {
					$sel = '';
					if(in_array($v, $default)) {
						$sel = 'selected="selected"';
					}
					$item .= "<option ".$sel." value='".isc_html_escape($v)."'>".isc_html_escape($k)."</option>";
				}

				$item .= "</select>";

				if ($var['required']) {
					$message = addslashes(sprintf(GetLang('ChooseOptionForField'), $var['name']));
					$GLOBALS['ValidationJavascript'] .= "
						if($('#".$selId."').val() == -1) {
							".$showTab."
							alert('".$message."');
							$('#".$selId."').focus();
							return false;
						}
					";
				}

				break;
			}
		}

		return $item;
	}

	/**
	 * Get the number of settings that this module has.
	 *
	 * @return int The number of settings.
	 */
	public function GetNumSettings()
	{
		return count($this->_variables);
	}


	/**
	 * Save the configuration variables for this module that come in from the POST
	 * array.
	 *
	 * @param array An array of configuration variables.
	 * @param bool RUE to delete any existing module settings, FALSE not to. Default is TRUE
	 * @return boolean True if successful.
	 */
	public function SaveModuleSettings($settings=array(), $deleteFirst=true)
	{
		// @todo this always returns true
		// Delete any current settings the module has if we are set to
		if ($deleteFirst) {
			$this->DeleteModuleSettings();
		}

		// If we weren't supplied any settings and this module has one or more settings
		// don't continue and don't mark it as being set up yet
		if(empty($settings) && $this->GetNumSettings() > 0) {
			return true;
		}

		// Mark the module has being configured
		$newVar = array(
			'modulename' => $this->GetId(),
			'variablename' => 'is_setup',
			'variableval' => 1
		);
		$GLOBALS['ISC_CLASS_DB']->InsertQuery('module_vars', $newVar);

		$moduleVariables = $this->GetCustomVars();

		$this->moduleVariables = array();

		// Loop through the options that this module has
		foreach($settings as $name => $value) {
			$format = '';
			if(isset($moduleVariables[$name]['format'])) {
				$format = $moduleVariables[$name]['format'];
			}

			if(is_array($value)) {
				foreach($value as $childValue) {
					switch($format) {
						case 'price':
							$value = DefaultPriceFormat($childValue);
							break;
						case 'weight':
						case 'dimension':
							$value = DefaultDimensionFormat($value);
							break;
					}
					$newVar = array(
						'modulename' => $this->GetId(),
						'variablename' => $name,
						'variableval' => $childValue
					);
					$GLOBALS['ISC_CLASS_DB']->InsertQuery('module_vars', $newVar);

					$this->moduleVariables[$name][] = $childValue;
				}
			}
			else {
				switch($format) {
					case 'price':
						$value = DefaultPriceFormat($value);
						break;
					case 'weight':
					case 'dimension':
						$value = DefaultDimensionFormat($value);
						break;
				}
				$newVar = array(
					'modulename' => $this->GetId(),
					'variablename' => $name,
					'variableval' => $value
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('module_vars', $newVar);

				$this->moduleVariables[$name] = $value;
			}
		}

		$this->loadedVars = true;

		return true;
	}

	/**
	 * Delete all of the configuration/settings associated with this module.
	 *
	 */
	public function DeleteModuleSettings()
	{
		// Delete the existing settings for this module
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename='".$this->GetId()."'");
		$GLOBALS['ISC_CLASS_DB']->OptimizeTable('[|PREFIX|]module_vars');
	}

	/**
	 * Common method for preparing and returning the properties sheet HTML for all module types - this is a temporary measure before further refactoring, so as to boil code from each module into one spot
	 *
	 * @param string $tab_id Tab index / id for HTML/JS purposes
	 * @param string $idGlobal Global key for this module type's module "name"
	 * @param string $jsGlobal Global key for this module type's javascript
	 * @param string $jsSelectedFunction Name of the javascript function that is used to determine if a module is selected
	 * @param array $customVars An array of vars for this module, if not supplied or supplied as empty will be taken from GetCustomVars()
	 * @param string $moduleId Id of this module (can be builtin modules for certain module types), if not supplied or supplied as null will be taken from GetId()
	 * @return string HTML result of preparing template data and parsing module.propertysheet.tpl
	 *
	 */
	public function GetPropertiesSheet($tab_id, $idGlobal, $jsGlobal, $jsSelectedFunction, $customVars = array(), $moduleId = null)
	{
		$this->PreparePropertiesSheet($tab_id, $idGlobal, $jsGlobal, $jsSelectedFunction, $customVars, $moduleId);
		return Interspire_Template::getInstance('admin')->render('module.propertysheet.tpl');
	}

	/**
	 * Common method for preparing the properties sheet globals / template data for all module types - this is a temporary measure before further refactoring, so as to boil code from each module into one spot
	 *
	 * @param string $tab_id Tab index / id for HTML/JS purposes
	 * @param string $idGlobal Global key for this module type's module "name"
	 * @param string $jsGlobal Global key for this module type's javascript
	 * @param string $jsSelectedFunction Name of the javascript function that is used to determine if a module is selected
	 * @param array $customVars An array of vars for this module, if not supplied or supplied as empty will be taken from GetCustomVars()
	 * @param string $moduleId Id of this module (can be builtin modules for certain module types), if not supplied or supplied as null will be taken from GetId()
	 * @return void Nothing is returned, any module calling this directly should then render the module.propertysheet.tpl template, after doing module/module-type specific stuff
	 *
	 */
	public function PreparePropertiesSheet($tab_id, $idGlobal, $jsGlobal, $jsSelectedFunction, $customVars = array(), $moduleId = null)
	{
		$this->tabId = $tab_id;

		if (!$moduleId) {
			$moduleId = $this->GetId();
		}

		if (!isset($GLOBALS[$jsGlobal])) {
			$GLOBALS[$jsGlobal] = "";
		}

		$GLOBALS['ValidationJavascript'] = '';

		$GLOBALS['HideHeaderRow'] = '';
		$GLOBALS['HelpText'] = $this->GetHelpText();
		$GLOBALS['HelpIcon'] = "success";
		$GLOBALS['Properties'] = "";
		$GLOBALS[$idGlobal] = $this->GetName();
		$GLOBALS['PropertyClass'] = "properties_" . $moduleId;
		$GLOBALS['PropertyBox'] = "";
		$GLOBALS['PropertyName'] = "";
		$GLOBALS['HelpTip'] = "";
		$GLOBALS['PanelBottom'] = "";
		$GLOBALS['FieldId'] = 0;
		$GLOBALS['Required'] = '';

		$GLOBALS['HideSelectAllLinks'] = 'display: none';

		// Add the logo
		$image = $this->GetImage();
		if ($image != "") {
			$GLOBALS['HelpTip'] = "";
			$GLOBALS['PropertyBox'] = sprintf("<img style='margin-top:5px' src='%s' />", $this->GetImage());
			$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
		}

		// Build the JavaScript to check the fields if required
		$GLOBALS[$jsGlobal] .= "
			if(" . $jsSelectedFunction . "('" . $moduleId . "')) {
		";

		if (empty($customVars)) {
			$customVars = $this->GetCustomVars();
		}

		/**
		 * Check to see if we have tabs for this module
		 */
		reset($customVars);
		$first = current($customVars);
		$hasTabs = false;
		$currentModuleTabId = 0;

		if (isset($first["tabname"])) {
			$hasTabs = true;
			$GLOBALS["ModuleTabs"] = "";
			$GLOBALS["ModuleId"] = $moduleId;

			if (isset($_REQUEST["currentTab" . $moduleId])) {
				$currentModuleTabId = $_REQUEST["currentTab" . $moduleId];
			}

			$GLOBALS["CurrentModuleTabId"] = $currentModuleTabId;

			foreach ($customVars as $moduleTabId => $customVar) {
				$GLOBALS["ModuleTabs"] .= "<li><a href=\"#\" ";

				if ($currentModuleTabId == $moduleTabId) {
					$GLOBALS["ModuleTabs"] .= "class=\"active\" ";
				}

				$GLOBALS["ModuleTabs"] .= " id=\"tab" . $moduleId . $moduleTabId . "\" onclick=\"AdminAccountingSettings.showModuleTab(" . $moduleTabId . ", '" . addslashes($moduleId) . "'); return false;\">" . isc_html_escape($customVar["tabname"]) . "</a></li>\n";
			}

			$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
		}

		$propertiesHTML = '';

		$i = 0;
		$propertyCount = count($customVars);
		foreach ($customVars as $id => $customVar) {
			++$i;
			if ($hasTabs) {

				$GLOBALS["ModuleTabID"] = $moduleId . $id;
				$GLOBALS["ModuleTabHTML"] = "";
				$GLOBALS["ModuleTabActiveClass"] = "";
				$GLOBALS["HideModuleTab"] = "";

				if ($currentModuleTabId == $id) {
					$GLOBALS["ModuleTabActiveClass"] = "class=\"active\" ";
				} else {
					$GLOBALS["HideModuleTab"] = "display:none";
				}

				if (!isset($customVar["tabitems"]) || !is_array($customVar["tabitems"])) {
					continue;
				}

				foreach ($customVar["tabitems"] as $itemId => $itemData) {
					$GLOBALS["ModuleTabHTML"] .= $this->GetItemPropertiesSheet($itemId, $itemData, $moduleId, $propertyCount, $i, false);
				}

				$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl'); // todo: this was taken as-is from the accounting module type, but this might need to be $propertiesHTML .= rather than $GLOBALS['Properties'] .=
			} else {
				$propertiesHTML .= $this->GetItemPropertiesSheet($id, $customVar, $moduleId, $propertyCount, $i);
			}
		}

		if ($hasTabs) {
			$GLOBALS["Properties"] .= "<tr><td colspan=\"2\">" . $propertiesHTML . "</td></tr>";
		} else {
			$GLOBALS["Properties"] .= $propertiesHTML;
		}

		if (empty($customVars)) {
			// Hide the heading of the property sheet if there aren't any properties
			$GLOBALS['HidePropSheet'] = "none";
		} else {
			$GLOBALS['HidePropSheet'] = "";
		}

		// If we have tabs then hide the header
		if ($hasTabs) {
			$GLOBALS["HideHeaderRow"] = "display:none";
		} else {
			$GLOBALS["HideHeaderRow"] = "";
		}

		$GLOBALS[$jsGlobal] .= $GLOBALS['ValidationJavascript'];
		$GLOBALS[$jsGlobal] .= "}";
	}

	/**
	 * Per item template preparation for a module's properties sheet; simply a shift of code which was inside a loop in PreparePropertiesSheet() so as to make refactoring of the accounting module type easier
	 *
	 * @param string $itemId Module variable item id
	 * @param string $itemData Module variable data
	 * @param string $moduleId The module id
	 * @param int $propertyCount A count of module variable items
	 * @param int $i Index of this module variable item among the list of all items
	 * @param bool $useTabs Whether or not to use tabs when calling _buildformitems (seemingly only used as 'false' by accounting modules)
	 * @return string HTML result of either rendering module.property.tpl, or a custom template if configured as such through the contents of $itemData
	 */
	public function GetItemPropertiesSheet($itemId, $itemData, $moduleId, $propertyCount, $i, $useTabs = true)
	{
		$html = "";

		if (array_key_exists("heading", $itemData)) {
			$html .= "<tr><td colspan='2'>&nbsp;</td></tr>";
			$html .= "</table><table class=\"Panel\" width=\"100%\">";
			$html .= "<tr><td class='Heading2' colspan='2'>" . isc_html_escape($itemData["heading"]) . "</td></tr>";
		}

		$GLOBALS['PropertyBox'] = "";

		if (isset($itemData['name'])) {
			$GLOBALS['PropertyName'] = $itemData['name'];
			$lastChar = $itemData['name'][strlen($itemData['name'])-1];
			if ($lastChar != '?') {
				// append colon, if label name does not end with question mark
				$GLOBALS['PropertyName'] .= ':';
			}
		} else {
			$GLOBALS['PropertyName'] = '';
		}

		$GLOBALS['HelpTip'] = "";
		$GLOBALS['Required'] = '';
		$GLOBALS['HideProperty'] = '';
		$GLOBALS['PanelBottom'] = '';

		$GLOBALS['FieldId'] = $moduleId . '_' . $itemId;

		if (isset($itemData['required']) && $itemData['required']) {
			$GLOBALS['Required'] = '<span class="Required">*</span>';
		}

		if (isset($itemData["type"]) && $itemData['type'] == 'dropdown' && isset($itemData['multiselect']) && $itemData['multiselect'] == true) {
			$GLOBALS['HideSelectAllLinks'] = '';
		} else {
			$GLOBALS['HideSelectAllLinks'] = 'display: none';
		}

		$GLOBALS['PropertyBox'] = $this->_buildformitem($itemId, $itemData, $useTabs, $moduleId);
		$help_id = rand(1000, 100000);

		if ($i == $propertyCount) {
			$GLOBALS['PanelBottom'] = "PanelBottom";
		}

		if (isset($itemData['help']) && $itemData['help'] != "") {
			$GLOBALS['HelpTip'] = sprintf("<img onmouseout=\"HideHelp('d%d')\" onmouseover=\"ShowHelp('d%d', '%s', '%s')\" src=\"images/help.gif\" width=\"24\" height=\"16\" border=\"0\" /><div style=\"display:none\" id=\"d%d\"></div>", $help_id, $help_id, $itemData['name'], $itemData['help'], $help_id);
		}

		if (isset($itemData['visible']) && $itemData['visible'] == false) {
			$GLOBALS['HideProperty'] = 'display: none';
		}

		/**
		 * Check for personal template
		 */
		if (array_key_exists("template", $itemData) && $itemData["template"] != "") {
			$html .= $this->ParseTemplate($itemData["template"], true);
		} else if (array_key_exists("notemplate", $itemData) && $itemData["notemplate"]) {
			$html .= $GLOBALS["PropertyBox"];
		} else {
			$html .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
		}

		return $html;
	}

	/**
	* usort() callback for sorting a list of ISC_MODULE objects by language name
	*
	* the ISC_MODULE type is not enforced, but a public GetName() method is assumed
	*
	* @param ISC_MODULE $a
	* @param ISC_MODULE $b
	* @return int
	*/
	protected static function moduleSortByNameCallback($a, $b)
	{
		return strcmp($a->GetName(), $b->GetName());
	}
}
