<?php
class ISC_SHIPPINGMANAGER extends ISC_MODULE {
	protected $type = 'shippingmanager';

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Check to see if the module is enabled
	 *
	 * Method will to see if the module is enabled
	 *
	 * @access public
	 * @return bool TRUE if the module is enabled, FALSE if not
	 */
	public function CheckEnabled()
	{
		$shippingManagers = explode(',', GetConfig("ShippingManagerModules"));
		if (in_array($this->getid(), $shippingManagers)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Build the HTML property sheet
	 *
	 * Method will build and return the HTML property sheet for the module
	 *
	 * @access public
	 * @param int $tabId The tab ID that the property sheet will be displayed in
	 * @return string The HMTL property sheet
	 */
	public function getPropertiesSheet($tabId)
	{
		$this->tabId = $tabId;

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

		foreach ($this->GetCustomVars() as $id=>$var) {
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

		//$GLOBALS['ShippingJavaScript'] .= $GLOBALS['ValidationJavascript'];

		// First check if the shipping provider is configured.
		$configured = false;
		if(!empty($this->moduleVariables)) {
			$configured = true;
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
}