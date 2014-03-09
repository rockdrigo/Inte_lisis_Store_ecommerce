<?php
class ISC_ADMIN_SETTINGS_SHIPPING extends ISC_ADMIN_BASE
{
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		if(isset($_REQUEST['vendorId'])) {
			$GLOBALS['VendorIdAdd'] = '&vendorId='.(int)$_REQUEST['vendorId'];
		}
		else {
			$GLOBALS['VendorIdAdd'] = '';
		}

		$GLOBALS['CurrentVendor'] = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings.shipping');
	}

	/**
	 * Handle the action for this section.
	 *
	 * @param string The name of the action to do.
	 */
	public function HandleToDo($Do)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		if (isset($_REQUEST['currentTab'])) {
			$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
		}
		else {
			$GLOBALS['CurrentTab'] = 0;
		}

		$GLOBALS['BreadcrumEntries'] = array (
			GetLang('Home') => "index.php",
		);

		switch(isc_strtolower($Do))
		{
			case "testshippingprovider":
				$this->TestShippingProvider();
				break;
			case "testshippingproviderquote":
				$this->TestShippingProviderQuote();
				break;
			case "editshippingzone":
				$this->EditShippingZone();
				break;
			case "saveupdatedshippingzone":
				$this->SaveUpdatedShippingZone();
				break;
			case "addshippingzone":
				$this->AddShippingZone();
				break;
			case "savenewshippingzone":
				$this->SaveNewShippingZone();
				break;
			case "deleteshippingzones":
				$this->DeleteShippingZones();
				break;
			case "addshippingzonemethod":
				$this->AddShippingZoneMethod();
				break;
			case "savenewshippingzonemethod":
				$this->SaveNewShippingZoneMethod();
				break;
			case "editshippingzonemethod":
				$this->EditShippingZoneMethod();
				break;
			case "saveupdatedshippingzonemethod":
				$this->SaveUpdatedShippingZoneMethod();
				break;
			case "toggleshippingzonestatus":
				$this->ToggleShippingZoneStatus();
				break;
			case "toggleshippingzonemethodstatus":
				$this->ToggleShippingZoneMethodStatus();
				break;
			case "copyshippingzone":
				$this->CopyShippingZone();
				break;
			case "deleteshippingzonemethods":
				$this->DeleteShippingZoneMethods();
				break;
			case "saveupdatedshippingsettings":
				$this->SaveUpdatedShippingSettings();
				break;
			default:
				$this->ManageShippingSettings();
				break;
		}
	}

	/**
	 * Change the status of a shipping method between active and inactive.
	 */
	private function ToggleShippingZoneMethodStatus()
	{
		if(!isset($_REQUEST['methodId'])) {
			FlashMessage(GetLang('InvalidShippingMethod'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		$method = $this->GetShippingMethodData($_REQUEST['methodId']);
		// If the method doesn't exist, show an error message
		if(!isset($method['methodid'])) {
			FlashMessage(GetLang('InvalidShippingMethod'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($method['methodvendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $method['methodvendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$method['methodvendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		if($method['methodenabled'] == 1) {
			$updatedMethod = array(
				'methodenabled' => 0
			);
			$message = GetLang('ShippingMethodDisabled');
		}
		else {
			$updatedMethod = array(
				'methodenabled' => 1
			);
			$message = GetLang('ShippingMethodEnabled');
		}
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('shipping_methods', $updatedMethod, "methodid='".$method['methodid']."'");
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($method['methodid'], $method['methodname']);
		$url = 'index.php?ToDo=editShippingZone&zoneId='.$method['zoneid'].'&currentTab=1';
		if($method['methodvendorid'] > 0) {
			$url .= '&vendorId='.(int)$method['methodvendorid'];
		}
		FlashMessage($message, MSG_SUCCESS, $url);
	}

	/**
	 * Toggle the status of a shipping zone between active and inactive.
	 */
	private function ToggleShippingZoneStatus()
	{
		if(!isset($_REQUEST['zoneId'])) {
			FlashMessage(GetLang('InvalidShippingZone'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		$zone = $this->GetShippingZoneData($_REQUEST['zoneId']);
		// If the zone doesn't exist, show an error message
		if(!isset($zone['zoneid'])) {
			FlashMessage(GetLang('InvalidShippingZone'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($zone['zonevendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $zone['zonevendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$zone['zonevendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		if($zone['zoneenabled'] == 1) {
			$updatedZone = array(
				'zoneenabled' => 0
			);
			$message = GetLang('ShippingZoneDisabled');
		}
		else {
			$updatedZone = array(
				'zoneenabled' => 1
			);
			$message = GetLang('ShippingMethodEnabled');
		}
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('shipping_zones', $updatedZone, "zoneid='".$zone['zoneid']."'");
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($zone['zoneid'], $zone['zonename']);
		if($zone['zonevendorid'] > 0) {
			$url = 'index.php?ToDo=editVendor&currentTab=1&vendorId='.(int)$zone['zonevendorid'];
		}
		else {
			$url = 'index.php?ToDo=viewShippingZones&currentTab=1';
		}
		FlashMessage($message, MSG_SUCCESS, $url);
	}

	/**
	 * Get a list of shipping modules as <option> tags.
	 *
	 * @param string The module ID of the currently selected module.
	 * @return string The built <option> tags for the available shipping modules.
	 */
	public function GetShippingCompaniesAsOptions($selected='')
	{
		// Get a list of all available shipping modules as <option> tags
		$shippers = GetAvailableModules('shipping', false, false, true);
		$output = "";
		$ns_start_done = false;
		$ns_end_done = false;
		$mu_start_done = false;
		$mu_end_done = false;

		foreach ($shippers as $ship) {
			// List all shipping providers except free shipping which will get its own chechbox
			if ($ship['id'] != "shipping_freeshipping" && $ship['id'] != "shipping_percountry") {
				if (!$ns_start_done && !$ship['object']->_flatrate) {
					$output .= sprintf("<optgroup label='%s'>", GetLang('RealTimeShippingQuotes'));
					$ns_start_done = true;
				}

				if (!$ns_end_done && $ship['object']->_flatrate) {
					$output .= "</optgroup>";
					$ns_end_done = true;
				}

				if (!$mu_start_done && $ship['object']->_flatrate) {
					$output .= sprintf("<optgroup label='%s'>", GetLang('FixedShippingQuotes'));
					$mu_start_done = true;
				}

				$sel = '';
				if($selected == $ship['id']) {
					$sel = "selected=\"selected\"";
				}

				$output .= sprintf("<option %s value='%s'>%s</option>", $sel, $ship['id'], $ship['name']);
			}
		}

		if (!$mu_end_done && $ship['object']->_flatrate) {
			$output .= "</optgroup>";
			$ns_end_done = true;
		}

		return $output;
	}

	/**
	 * Retrieve a shipping method from the database based off the passed shipping method id.
	 *
	 * @param string The shipping method ID.
	 * @return array Array containing the shipping method from the database.
	 */
	private function GetShippingMethodData($methodId)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_methods
			WHERE methodid='".(int)$methodId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$method = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $method;
	}

	/**
	 * Add a new shipping method to a shipping zone.
	 */
	private function AddShippingZoneMethod()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$zone = $this->GetShippingZoneData($_REQUEST['zoneId']);

		// If the zone doesn't exist, show an error message
		if(!isset($zone['zoneid'])) {
			FlashMessage(GetLang('InvalidShippingZone'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($zone['zonevendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $zone['zonevendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$zone['zonevendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		// Generate the breadcrumb
		if($zone['zonevendorid'] > 0) {
			$vendorCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Vendors');
			$vendor = $vendorCache[$zone['zonevendorid']];
			if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
				$GLOBALS['BreadcrumEntries'][GetLang('Vendors')] = "index.php?ToDo=viewVendors";
				$GLOBALS['BreadcrumEntries'][isc_html_escape($vendor['vendorname'])] = 'index.php?ToDo=editVendor&amp;vendorId='.$vendor['vendorid'];
			}
			else {
				$GLOBALS['BreadcrumEntries'][GetLang('VendorProfile')] = 'index.php?ToDo=editVendor&amp;vendorId='.$vendor['vendorid'];
			}
		}
		else {
			$GLOBALS['BreadcrumEntries'][GetLang('Settings')] = "index.php?ToDo=viewSettings";
			$GLOBALS['BreadcrumEntries'][GetLang('ShippingSettings')] = "index.php?ToDo=viewShippingSettings";
		}
		$GLOBALS['BreadcrumEntries'][$zone['zonename']] = 'index.php?ToDo=editShippingZone&amp;zoneId='.$zone['zoneid'];
		$GLOBALS['BreadcrumEntries'][GetLang('AddShippingMethod')] = '';


		$GLOBALS['ZoneId']		=  $zone['zoneid'];
		$GLOBALS['FormAction']	= 'SaveNewShippingZoneMethod';
		$GLOBALS['Title']		= sprintf(GetLang('AddNewShippingMethodTo'), isc_html_escape($zone['zonename']));
		$GLOBALS['Intro']		= GetLang('AddShippingMethodIntro');

		// If handling is enabled per module, show the handling section
		if($zone['zonehandlingtype'] == 'module') {
			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['RightCurrencyToken'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['LeftCurrencyToken'] = GetConfig('CurrencyToken');
			}
		}
		else {
			$GLOBALS['HideHandlingFee'] = 'display: none;';
		}

		if(isset($_POST['methodenabled']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
			$GLOBALS['MethodEnabledCheck'] = 'checked="checked"';
		}

		$GLOBALS['HideModuleName'] = 'display: none;';

		// Get a list of available shipping modules - when one is selected, via JS the options are loaded in
		$GLOBALS['ShippingProviders'] = $this->GetShippingCompaniesAsOptions();

		// Now we get the actual modules and check their requirements. If the requirements aren't meant, we add them to a Javascript
		// error message stack so that if someone tries to enable this shipping module for this method they get an error
		$providerErrors = array();
		$providers = GetAvailableModules('shipping');

		$companyCountry = GetConfig('CompanyCountry');
		if($zone['zonevendorid']) {
			$query = "
				SELECT vendorcountry
				FROM [|PREFIX|]vendors
				WHERE vendorid='".(int)$zone['zonevendorid']."'
			";
			$companyCountry = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		}

		foreach($providers as $provider) {
			// Is this shipping provider supported on this server?
			if($provider['object']->IsSupported() == false) {
				foreach($provider['object']->GetErrors() as $error) {
					$providerErrors[$provider->GetId()][] = $error;
				}
			}

			// Is the shipping provider available in the shop owners country?
			$shipperCountries = $provider['object']->GetCountries();
			if($zone['zonevendorid']) {
				$message = GetLang('VendorProviderOnlyShipsFrom');
			}
			else {
				$message = GetLang('ProviderOnlyShipsFrom');
			}
			if($shipperCountries[0] != 'all' && $shipperCountries[0] != '' && !in_array($companyCountry, $shipperCountries)) {
				$msg = sprintf($message, $provider['object']->GetName(), implode(", ", $provider['object']->GetCountries()));
				$providerErrors[$provider['object']->GetId()][] = $msg;
			}
		}

		$GLOBALS['ShippingProviderErrors'] = 'var providerErrors = {';
		if(!empty($providerErrors)) {
			foreach($providerErrors as $provider => $errors) {
				$GLOBALS['ShippingProviderErrors'] .= $provider.': "';
				$errors = implode("\n", $errors);
				$errors = addslashes($errors);
				$GLOBALS['ShippingProviderErrors'] .= $errors;
				$GLOBALS['ShippingProviderErrors'] .= '",';
			}
			$GLOBALS['ShippingProviderErrors'] = trim($GLOBALS['ShippingProviderErrors'], "\n,");
			$GLOBALS['ShippingProviderErrors'] .= "};";
		}

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('shippingzone.method.form.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	/**
	 * Save a new shipping zone in the database.
	 */
	private function SaveNewShippingZoneMethod()
	{
		$zone = $this->GetShippingZoneData($_REQUEST['zoneId']);

		// If the zone doesn't exist, show an error message
		if(!isset($zone['zoneid'])) {
			FlashMessage(GetLang('InvalidShippingZone'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($zone['zonevendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $zone['zonevendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$zone['zonevendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		if(!$this->ValidateShippingZoneMethod($_POST, $error)) {
			FlashMessage($error, MSG_ERROR);
			$this->AddShippingZoneMethod();
			return;
		}

		if(!$this->CommitShippingZoneMethod($_POST)) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingShippingMethod').$error, MSG_ERROR);
			$this->AddShippingZoneMethod();
			return;
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($zone['zoneid'], $_POST['methodname']);
			FlashMessage(GetLang('ShippingMethodCreated'), MSG_SUCCESS, 'index.php?ToDo=editShippingZone&zoneId='.$zone['zoneid'].'&currentTab=1'.$GLOBALS['VendorIdAdd']);
		}
	}

	/**
	 * Validate the data for a shipping method before it's inserted in to the database.
	 *
	 * @param array Array of data about this shipping zone.
	 * @param string Passed by reference, any error messages that need to be shown.
	 * @return boolean True if the shipping method data is valid.
	 */
	public function ValidateShippingZoneMethod($data, &$message)
	{
		$methodId = 0;
		if (isset($data['methodId']) && $data['methodId'] > 0) {
			$methodId = $data['methodId'];
		}

		// Check that the select method exists
		if (!isId($methodId) && (!isset($data['methodmodule']) || trim($data['methodmodule']) == '')) {
			$message = GetLang('SelectShippingMethod');
			return false;
		}

		$methodIdQuery = '';
		if(isId($methodId)) {
			$methodIdQuery = " AND methodid!='".(int)$methodId."'";
		}

		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
			$methodIdQuery .= " AND methodvendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
		}
		else if(isset($_REQUEST['vendorId'])) {
			$methodIdQuery .= " AND methodvendorid='".(int)$_REQUEST['vendorId']."'";
		}
		else {
			$methodIdQuery .= " AND methodvendorid='0'";
		}

		// Check that a duplicate zone doesn't exist with this name
		$query = "
			SELECT methodid
			FROM [|PREFIX|]shipping_methods
			WHERE methodname='".$GLOBALS['ISC_CLASS_DB']->Quote($data['methodname'])."' AND zoneid='".(int)$data['zoneId']."'".$methodIdQuery
		;
		if($GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
			$message = GetLang('DuplicateShippingMethodName');
			return false;
		}

		return true;
	}

	/**
	 * Save a new shipping method in the database or update an existing one with the supplied data.
	 *
	 * @param array Array of data about the shipping method.
	 * @param int The shipping method ID of the existing method if we're doing an update.
	 * @return boolean True on success, false if there was a problem.
	 */
	public function CommitShippingZoneMethod($data, $methodId = 0)
	{
		// If the method id is 0, then we're creating a new method
		if($methodId > 0) {
			$existingMethod = $this->GetShippingMethodData($methodId);
		}

		if(!trim($data['methodname'])) {
			return false;
		}

		if($methodId) {
			$data['zoneId'] = $existingMethod['zoneid'];
			$data['methodmodule'] = $existingMethod['methodmodule'];
		}

		$zone = $this->GetShippingZoneData($data['zoneId']);

		if($zone['zonehandlingtype'] != 'module') {
			$data['methodhandlingfee'] = 0;
		}

		if(!isset($data['methodenabled'])) {
			$data['methodenabled'] = 0;
		}
		else {
			$data['methodenabled'] = 1;
		}

		$methodData = array(
			'zoneid' => (int)$data['zoneId'],
			'methodname' => $data['methodname'],
			'methodmodule' => $data['methodmodule'],
			'methodhandlingfee' => $data['methodhandlingfee'],
			'methodenabled' => $data['methodenabled'],
			'methodvendorid' => $zone['zonevendorid'],
		);

		if($methodId == 0) {
			$methodId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("shipping_methods", $methodData);
		}
		else {
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("shipping_methods", $methodData, "methodid='".(int)$methodId."'");
		}

		// Couldn't save? return an error message
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			return false;
		}

		GetModuleById('shipping', $module, $data['methodmodule']);
		$moduleVars = array();
		if(isset($_POST[$data['methodmodule']])) {
			$moduleVars = $_POST[$data['methodmodule']];
		}

		$module->SetMethodId($methodId);
		$module->SaveModuleSettings($moduleVars);

		// Couldn't save? return an error message
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			return false;
		}

		// We've just configured shipping - mark it as so.
		if(!in_array('shippingOptions', GetConfig('GettingStartedCompleted'))) {
			GetClass('ISC_ADMIN_ENGINE')->MarkGettingStartedComplete('shippingOptions');
		}

		return $methodId;
	}

	/**
	 * Edit a shipping zone method.
	 */
	private function EditShippingZoneMethod()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$method = $this->GetShippingMethodData($_REQUEST['methodId']);

		// If the method doesn't exist, show an error message
		if(!isset($method['methodid'])) {
			FlashMessage(GetLang('InvalidShippingMethod'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($method['methodvendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $method['methodvendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$method['methodvendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$zone = $this->GetShippingZoneData($method['zoneid']);

		// Generate the breadcrumb
		if($method['methodvendorid'] > 0) {
			$vendorCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Vendors');
			$vendor = $vendorCache[$method['methodvendorid']];
			if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
				$GLOBALS['BreadcrumEntries'][GetLang('Vendors')] = "index.php?ToDo=viewVendors";
				$GLOBALS['BreadcrumEntries'][isc_html_escape($vendor['vendorname'])] = 'index.php?ToDo=editVendor&amp;vendorId='.$vendor['vendorid'];
			}
			else {
				$GLOBALS['BreadcrumEntries'][GetLang('VendorProfile')] = 'index.php?ToDo=editVendor&amp;vendorId='.$vendor['vendorid'];
			}
		}
		else {
			$GLOBALS['BreadcrumEntries'][GetLang('Settings')] = "index.php?ToDo=viewSettings";
			$GLOBALS['BreadcrumEntries'][GetLang('ShippingSettings')] = "index.php?ToDo=viewShippingSettings";
		}

		$GLOBALS['BreadcrumEntries'][$zone['zonename']] = 'index.php?ToDo=editShippingZone&amp;zoneId='.$zone['zoneid'];
		$GLOBALS['BreadcrumEntries'][GetLang('EditShippingMethod')] = '';

		$GLOBALS['MethodId']	= $method['methodid'];
		$GLOBALS['ZoneId']		= $zone['zoneid'];
		$GLOBALS['FormAction']	= 'SaveUpdatedShippingZoneMethod';
		$GLOBALS['Title']		= sprintf(GetLang('EditShippingMethodIn'), isc_html_escape($zone['zonename']));
		$GLOBALS['Intro']		= GetLang('EditShippingMethodIntro');

		// If handling is enabled per module, show the handling section
		if($zone['zonehandlingtype'] == 'module') {
			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['RightCurrencyToken'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['LeftCurrencyToken'] = GetConfig('CurrencyToken');
			}
			$GLOBALS['HandlingFee'] = number_format($method['methodhandlingfee'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), '');
		}
		else {
			$GLOBALS['HideHandlingFee'] = 'display: none;';
		}

		if(isset($_POST['methodenabled']) || ($_SERVER['REQUEST_METHOD'] != 'POST' && $method['methodenabled'])) {
			$GLOBALS['MethodEnabledCheck'] = 'checked="checked"';
		}

		// Get the module
		GetModuleById('shipping', $shippingModule, $method['methodmodule']);

		if(!is_object($shippingModule)) {
			FlashMessage(GetLang('InvalidShippingModue'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		$shippingModule->SetMethodId($method['methodid']);

		$GLOBALS['MethodName'] = $method['methodname'];
		$GLOBALS['MethodBasedOn'] = $shippingModule->GetName();
		$GLOBALS['HideModuleSelect'] = 'display: none';
		$GLOBALS['HideChooseMethod'] = 'display: none';

		$GLOBALS['ShippingModuleProperties'] = $shippingModule->GetPropertiesSheet(0);

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('shippingzone.method.form.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	/**
	 * Save the updated version of a shipping zone method.
	 */
	private function SaveUpdatedShippingZoneMethod()
	{
		$method = $this->GetShippingMethodData($_REQUEST['methodId']);

		// If the method doesn't exist, show an error message
		if(!isset($method['methodid'])) {
			FlashMessage(GetLang('InvalidShippingMethod'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($method['methodvendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $method['methodvendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$method['methodvendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		if(!$this->ValidateShippingZoneMethod($_POST, $error)) {
			FlashMessage($error, MSG_ERROR);
			$this->AddShippingZoneMethod();
			return;
		}

		if(!$this->CommitShippingZoneMethod($_POST, $method['methodid'])) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingShippingMethod').$error, MSG_ERROR);
			$this->EditShippingZoneMethod();
			return;
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($method['methodid'], $_POST['methodname']);
			FlashMessage(GetLang('ShippingMethodUpdated'), MSG_SUCCESS, 'index.php?ToDo=editShippingZone&zoneId='.$method['zoneid'].'&currentTab=1'.$GLOBALS['VendorIdAdd']);
		}
	}

	/**
	 * Delete one or more selected shipping methods.
	 */
	private function DeleteShippingZoneMethods()
	{
		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		if(isset($_POST['methods'])) {
			$methodIds = array_map("intval", $_POST['methods']);
			$firstMethod = $methodIds[0];
			$methodIds = implode("','", $methodIds);

			// We can only delete what we have permission to, so if we're a vendor - only those we've created
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() > 0) {
				$query = "
					SELECT methodid
					FROM [|PREFIX|]shipping_methods
					WHERE methodvendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."' AND methodid IN ('".$methodIds."')
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$methodIds = array();
				while($method = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$methodIds[] = $method['methodid'];
				}
				$methodIds = implode("','", $methodIds);
			}

			// Fetch the ID of the zone that these methods belong to. We'll use it later
			$firstMethod = $this->GetShippingMethodData($firstMethod);

			// Delete the methods from the database
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_vars', "WHERE methodid IN ('".$methodIds."')");
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_methods', "WHERE methodid IN ('".$methodIds."')");

			$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			if($err) {
				FlashMessage($err, MSG_ERROR, 'index.php?ToDo=editShippingZone&zoneId='.$firstMethod['zoneid'].'&currentTab=1');
			}
			else {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminaction(count($_POST['methods']));
				FlashMessage(GetLang('ShippingMethodsDeleted'), MSG_SUCCESS, 'index.php?ToDo=editShippingZone&zoneId='.$firstMethod['zoneid'].'&currentTab=1');
			}
		}
	}

	/**
	 * Show the "Test Shipping Provider" form to test a shipping provider.
	 */
	private function TestShippingProvider()
	{
		$shipper = null;

		if (isset($_GET['methodId'])) {
			$method = $this->GetShippingMethodData($_GET['methodId']);
			if (GetModuleById('shipping', $shipper, $method['methodmodule'])) {
				$shipper->SetMethodId($method['methodid']);
				$GLOBALS['MethodId'] = $method['methodid'];
				$this->template->display('module.pageheader.tpl');
				$shipper->TestQuoteForm();
				$this->template->display('module.pagefooter.tpl');
			}
		}
	}

	/**
	 * Perform the actual shipping provider test and throw the results in to a window.
	 */
	private function TestShippingProviderQuote()
	{
		$shipper = null;

		if (isset($_POST['methodId'])) {
			$method = $this->GetShippingMethodData($_REQUEST['methodId']);
			if (GetModuleById('shipping', $shipper, $method['methodmodule'])) {
				$shipper->SetMethodId($method['methodid']);
				$GLOBALS['MethodId'] = $method['methodid'];
				$this->template->display('module.pageheader.tpl');
				$shipper->TestQuoteResult();
				$this->template->display('module.pagefooter.tpl');
			}
		}
	}

	/**
	 * Fetch a shipping zone from the database based on the specified ID.
	 *
	 * @param int The shipping zone ID.
	 * @return array Array containing the shipping zone.
	 */
	private function GetShippingZoneData($zoneId)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_zones
			WHERE zoneid='".(int)$zoneId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $zone;
	}

	/**
	 * Edit an existing shipping zone.
	 */
	private function EditShippingZone()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$zone = $this->GetShippingZoneData($_REQUEST['zoneId']);

		// Generate the breadcrumb
		if($zone['zonevendorid'] > 0) {
			$vendorCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Vendors');
			$vendor = $vendorCache[$zone['zonevendorid']];

			if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
				$GLOBALS['BreadcrumEntries'][GetLang('Vendors')] = "index.php?ToDo=viewVendors";
				$GLOBALS['BreadcrumEntries'][isc_html_escape($vendor['vendorname'])] = 'index.php?ToDo=editVendor&amp;vendorId='.$vendor['vendorid'];
			}
			else {
				$GLOBALS['BreadcrumEntries'][GetLang('VendorProfile')] = 'index.php?ToDo=editVendor&amp;vendorId='.$vendor['vendorid'];
			}
		}
		else {
			$GLOBALS['BreadcrumEntries'][GetLang('Settings')] = "index.php?ToDo=viewSettings";
			$GLOBALS['BreadcrumEntries'][GetLang('ShippingSettings')] = "index.php?ToDo=viewShippingSettings";
		}
		if(!isset($_REQUEST['created'])) {
			$GLOBALS['BreadcrumEntries'][$zone['zonename']] = 'index.php?ToDo=editShippingZone&amp;zoneId='.$zone['zoneid'];
		}
		else {
			$GLOBALS['BreadcrumEntries'][GetLang('AddShippingZoneStep2')] = '';
		}

		// If the zone doesn't exist, show an error message
		if(!isset($zone['zoneid'])) {
			FlashMessage(GetLang('InvalidShippingZone'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($zone['zonevendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $zone['zonevendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$zone['zonevendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$GLOBALS['FormAction']	= 'SaveUpdatedShippingZone';
		$GLOBALS['Intro']		= GetLang('EditShippingZoneIntro');

		$GLOBALS['NextButton'] = "Save";

		if (GetConfig('CurrencyLocation') == 'right') {
			$GLOBALS['RightCurrencyToken'] = GetConfig('CurrencyToken');
		} else {
			$GLOBALS['LeftCurrencyToken'] = GetConfig('CurrencyToken');
		}

		// Hide everything by default
		if($zone['zonedefault'] == 1) {
			$GLOBALS['HideZoneTypeFields']	= 'display: none;';
			$GLOBALS['HideZoneEnabled']		= 'display: none';
		}
		else {
			$GLOBALS['HideDefaultZoneType']	= 'display: none;';
		}

		if(isset($_POST['zoneenabled']) || ($_SERVER['REQUEST_METHOD'] != 'POST' && $zone['zoneenabled'])) {
			$GLOBALS['ZoneEnabledCheck'] = 'checked="checked"';
		}

		$GLOBALS['HideZoneTypeCountry']		= 'display: none';
		$GLOBALS['HideZoneTypeStates']		= 'display: none';
		$GLOBALS['HideZoneTypePostCodes']	= 'display: none';
		$GLOBALS['HideStateSelect']			= 'display: none';

		$selectedCountries = array();
		$selectedStates = array();
		$selectedPostCodes = array();

		// If we're coming back here - set up the values
		if($_SERVER['REQUEST_METHOD'] == "POST") {
			$zone['zonename'] = $_POST['zonename'];

			if(isset($_POST['zonefreeshipping'])) {
				$zone['zonefreeshipping'] = $_POST['zonefreeshipping'];
			}
			else {
				$zone['zonefreeshipping'] = 0;
			}

			if(isset($_POST['zonefreeshippingtotal'])) {
				$zone['zonefreeshippingtotal'] = $_POST['zonefreeshippingtotal'];
			}
			else {
				$zone['zonefreeshippingtotal'] = 0;
			}

			if(isset($_POST['zonehandlingtype'])) {
				$zone['zonehandlingtype'] = $_POST['zonehandlingtype'];
			}
			else {
				$zone['zonehandlingtype'] = 'none';
			}

			if(isset($_POST['zonehandlingfee'])) {
				$zone['zonehandlingfee'] = $_POST['zonehandlingfee'];
			}
			else {
				$zone['zonehandlingfee'] = 0;
			}

			if(isset($_POST['zonehandlingseparate'])) {
				$zone['zonehandlingseparate'] = $_POST['zonehandlingseparate'];
			}
			else {
				$zone['zonehandlingseparate'] = 0;
			}

			$zone['zonetype'] = $_POST['zonetype'];

			if($zone['zonetype'] == 'country') {
				$selectedCountries = $_POST['zonetype_country_list'];
			}
			else if($zone['zonetype'] == 'state') {
				foreach($_POST['zonetype_states'] as $state) {
					$state = explode('-', $state);
					$selectedStates[$state[0]][] = $state[1];
					$selectedCountries[$state[0]] = $state[0];
				}
			}
			else if($zone['zonetype'] == 'zip') {
				$selectedCountries[] = $_POST['zonetype_zip_country'];
				$GLOBALS['ZonePostCodes'] = isc_html_escape($_POST['zonetype_zip_list']);
			}
		}
		else {
			if($zone['zonetype'] == 'country') {
				// Load the list of countries this zone applies to
				$query = "
					SELECT *
					FROM [|PREFIX|]shipping_zone_locations
					WHERE zoneid='".$zone['zoneid']."' AND locationtype='country'
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($location = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$selectedCountries[] = $location['locationvalueid'];
				}
			}
			else if($zone['zonetype'] == 'state') {
				// Load the list of states that this zone applies to
				$query = "
					SELECT *
					FROM [|PREFIX|]shipping_zone_locations
					WHERE zoneid='".$zone['zoneid']."' AND locationtype='state'
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($location = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$selectedStates[$location['locationcountryid']][] = $location['locationvalueid'];
					$selectedCountries[$location['locationcountryid']] = $location['locationcountryid'];
				}
			}
			else if($zone['zonetype'] == 'zip') {
				// Load the list of post codes this zone applies to
				$query = "
					SELECT *
					FROM [|PREFIX|]shipping_zone_locations
					WHERE zoneid='".$zone['zoneid']."' AND locationtype='zip'
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$country = 0;
				while($location = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$selectedPostCodes[] = $location['locationvalue'];
					$country = $location['locationcountryid'];
				}

				// And to which country do these post codes belong? It's also stored in the zone_locations table
				$selectedCountries[] = $country;
			}

			$GLOBALS['ZonePostCodes']= implode("\n", $selectedPostCodes);
		}

		if($zone['zonehandlingtype'] == 'none') {
			$GLOBALS['HideHandlingSeparate'] = 'display: none';
		}

		// if the zone is country based, then show the country options
		if($zone['zonetype'] == 'country') {
			$GLOBALS['HideZoneTypeCountry'] = '';
			$GLOBALS['TypeCountriesChecked'] = 'checked="checked"';
		}

		// If the zone type is state based, then show the state options
		else if($zone['zonetype'] == 'state') {
			$GLOBALS['HideZoneTypeStates'] = '';
			$GLOBALS['HideStateSelect'] = '';
			$GLOBALS['HideStateSelectNode'] = 'display: none;';
			$GLOBALS['TypeStatesChecked'] = 'checked="checked"';
		}

		// Finally, if it's post code/zip code based then we show the zip code textbox
		else if($zone['zonetype'] == 'zip') {
			$GLOBALS['HideZoneTypePostCodes'] = '';
			$GLOBALS['TypeZipChecked'] = 'checked="checked"';
		}

		// Now we can build the select lists
		$GLOBALS['SingleCountrySelect']		= GetCountryList($selectedCountries);
		$GLOBALS['MultipleCountrySelect']	= GetCountryList($selectedCountries, false);
		if(!empty($selectedStates)) {
			$GLOBALS['StateSelect'] 		= GetMultiCountryStateOptions($selectedStates);
			$GLOBALS['HideStateSelectNone'] = "display: none;";
		}

		// If free shipping is enabled, then we need to show that option and fill in the free shipping total field
		if($zone['zonefreeshipping'] == 1) {
			$GLOBALS['FreeShippingChecked'] = 'checked="checked"';
			$GLOBALS['FreeShippingTotal'] = number_format($zone['zonefreeshippingtotal'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), "");
		}
		else {
			$GLOBALS['HideFreeShipping'] = 'display: none';
		}

		$GLOBALS['HideHandlingFeeGlobal'] = 'display: none';

		// No handling applied? Check that box
		if($zone['zonehandlingtype'] == 'none') {
			$GLOBALS['HandlingNoneChecked'] = 'checked="checked"';
		}
		// Handling is specified on a global level for this zone
		else if($zone['zonehandlingtype'] == 'global') {
			$GLOBALS['HandlingGlobalChecked'] = 'checked="checked"';
			$GLOBALS['HideHandlingFeeGlobal'] = '';
			$GLOBALS['HandlingFee'] = number_format($zone['zonehandlingfee'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), "");
		}
		// Handling is specified per shipping option in this zone
		else if($zone['zonehandlingtype'] == 'module') {
			$GLOBALS['HandlingOptionChecked'] = 'checked="checked"';
		}

		// Handling separate? Check
		if($zone['zonehandlingseparate']) {
			$GLOBALS['HandlingSeparateChecked'] = 'checked="checked"';
		}

		if(isset($_REQUEST['currentTab'])) {
			$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
		}

		// Generate a list of shipping methods for this shipping zone
		$GLOBALS['ShippingZoneMethods'] = '';
		$query = "SELECT * FROM [|PREFIX|]shipping_methods WHERE zoneid='".$zone['zoneid']."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($method = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['MethodId'] = $method['methodid'];
			$GLOBALS['MethodName'] = isc_html_escape($method['methodname']);
			GetModuleById('shipping', $shippingModule, $method['methodmodule']);
			if(is_object($shippingModule)) {
				$GLOBALS['MethodModule'] = $shippingModule->GetName();
			}
			else {
				$GLOBALS['MethodModule'] = '';
			}

			if(!$shippingModule->_showtestlink) {
				$GLOBALS['HideTestQuoteLink'] = 'display: none';
				$GLOBALS['TestQuoteHeight'] = 0;
			}
			else {
				$GLOBALS['TestQuoteHeight'] = $shippingModule->GetHeight();
				$GLOBALS['HideTestQuoteLink'] = '';
			}

			if($method['methodenabled'] == 1) {
				$statusImage = 'tick.gif';
			}
			else {
				$statusImage = 'cross.gif';
			}
			$GLOBALS['MethodStatus'] = "<a href='index.php?ToDo=toggleShippingZoneMethodStatus&amp;methodId=".$method['methodid']."'><img src='images/".$statusImage."' alt='' border='0' /></a>";

			$GLOBALS['ShippingZoneMethods'] .= $this->template->render('shippingzone.form.method.tpl');
		}

		if(!$GLOBALS['ShippingZoneMethods']) {
			$GLOBALS['DisableDeleteMethods'] = 'disabled="disabled"';
			$GLOBALS['HideShippingMethodsGrid'] =  'display: none;';
			$GLOBALS['MethodsMessage'] = MessageBox(GetLang('ShippingZoneNoMethods'), MSG_INFO);
		}

		$GLOBALS['ZoneName'] = isc_html_escape($zone['zonename']);
		$GLOBALS['ZoneId'] = $zone['zoneid'];

		if(isset($_REQUEST['created'])) {
			$GLOBALS['Title'] = GetLang('AddShippingZoneStep2');
		}
		else {
			$GLOBALS['Title'] = GetLang('EditShippingZone');
		}

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('shippingzone.form.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	/**
	 * Validate the data for a shipping zone before it's inserted in to the database.
	 *
	 * @param array Array of data about this shipping zone.
	 * @param string Passed by reference, any error messages that need to be shown.
	 * @return boolean True if the shipping method data is valid.
	 */
	public function ValidateShippingZone($data, &$message)
	{
		$zoneIdQuery = '';
		if(isset($data['zoneId']) && $data['zoneId'] > 0) {
			$zoneIdQuery = " AND zoneid!='".$data['zoneId']."'";
			$existingZone = $this->GetShippingZoneData($data['zoneId']);
		}

		$zoneVendorQuery = '';
		$locationVendorQuery = '';
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
			$zoneVendorQuery .= " AND zonevendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
			$locationVendorQuery .= " AND locationvendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
		}
		else if(isset($_REQUEST['vendorId'])) {
			$zoneVendorQuery .= " AND zonevendorid='".(int)$_REQUEST['vendorId']."'";
			$locationVendorQuery .= " AND locationvendorid='".(int)$_REQUEST['vendorId']."'";
		}
		else if(isset($existingZone)) {
			$zoneVendorQuery .= " AND zonevendorid='".(int)$existingZone['zonevendorid']."'";
			$locationVendorQuery .= " AND locationvendorid='".(int)$existingZone['zonevendorid']."'";
		}

		// Check that a duplicate zone doesn't exist with this name
		$query = "
			SELECT zoneid
			FROM [|PREFIX|]shipping_zones
			WHERE zonename='".$GLOBALS['ISC_CLASS_DB']->Quote($data['zonename'])."'".$zoneIdQuery.$zoneVendorQuery
		;

		if($GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
			$message = GetLang('DuplicateShippingZoneName');
			return false;
		}

		if(isset($data['zoneId']) && isset($existingZone['zonedefault']) && $existingZone['zonedefault'] == 1) {
			return true;
		}

		switch($data['zonetype']) {
			case 'country':
				$countryIds = implode(',', array_map('intval', $data['zonetype_country_list']));
				$query = "
					SELECT DISTINCT locationvalue
					FROM [|PREFIX|]shipping_zone_locations
					WHERE locationtype='country' AND locationvalueid IN (".$countryIds.")".$zoneIdQuery.$locationVendorQuery
				;
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$countryList = array();
				while($country = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$countryList[] = $country['locationvalue'];
				}
				if(!empty($countryList)) {
					$message = sprintf(GetLang('ShippingZoneDuplicateCountries'), implode(', ', $countryList));
					return false;
				}
				break;
			case 'state':
				$stateList = array();
				foreach($data['zonetype_states'] as $state) {
					$state = explode('-', $state);
					$query = "
						SELECT locationid, statename, countryname
						FROM [|PREFIX|]shipping_zone_locations
						LEFT JOIN [|PREFIX|]countries ON (countryid=locationcountryid)
						LEFT JOIN [|PREFIX|]country_states ON (stateid=locationvalueid)
						WHERE locationtype='state' AND locationvalueid='".(int)$state[1]."' AND locationcountryid='".(int)$state[0]."' ".$zoneIdQuery.$locationVendorQuery."
					";
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$dbState = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
					if(is_array($dbState)) {
						if(!$dbState['statename'] && $state[1] == 0) {
							$stateList[] = 'All states in '.$dbState['countryname'];
						}
						else {
							$stateList[] = $dbState['statename'].' ('.$dbState['countryname'].')';
						}
					}
				}
				if(!empty($stateList)) {
					$message = sprintf(GetLang('ShippingZoneDuplicateStates'), implode(', ', $stateList));
					return false;
				}
				break;
			case 'zip':
				$countryId = $data['zonetype_zip_country'];
				$zipCodes = explode("\n", $data['zonetype_zip_list']);
				foreach($zipCodes as $zipCode) {
					$zipCode = trim($zipCode);
					$query = "
						SELECT zoneid
						FROM [|PREFIX|]shipping_zone_locations
						WHERE locationvalue='".$GLOBALS['ISC_CLASS_DB']->Quote($zipCode)."' AND locationcountryid='".(int)$countryId."'".$zoneIdQuery.$locationVendorQuery."
					";
					if($GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
						$zipCodeList[] = $zipCode;
					}
				}
				if(!empty($zipCodeList)) {
					$message = sprintf(GetLang('ShippingZoneDuplicateZipCodes'), implode(', ', $zipCodeList));
					return false;
				}
				break;
		}

		return true;
	}

	/**
	 * Commit the changes (or a new shipping zone) to the database.
	 *
	 * @param array Array of information to insert/update about the shipping zone.
	 * @param int The shipping zone ID if we're updating an existing zone.
	 * @return boolean True if successful, false if there was an error.
	 */
	public function CommitShippingZone($data, $zoneId=0)
	{
		// If the zone ID is 0, then we're creating a new zone
		if($zoneId > 0) {
			$existingZone = $this->GetShippingZoneData($zoneId);
		}
		else {
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() > 0) {
				$data['zonevendorid'] = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
			}
			else if(isset($_REQUEST['vendorId'])) {
				$data['zonevendorid'] = (int)$_REQUEST['vendorId'];
			}
		}

		if(!trim($data['zonename'])) {
			return false;
		}

		if(($data['zonetype'] != 'state' && $data['zonetype'] != 'zip') || $zoneId == 1) {
			$data['zonetype'] = 'country';
		}

		if(!isset($data['zonefreeshipping'])) {
			$data['zonefreeshipping'] = 0;
			$data['zonefreeshippingtotal'] = 0;
		}

		if(!isset($data['zoneenabled'])) {
			$data['zoneenabled'] = 0;
		}
		else {
			$data['zoneenabled'] = 1;
		}

		if(!isset($data['zonehandlingseparate'])) {
			$data['zonehandlingseparate'] = 0;
		}

		if(!isset($data['zonehandlingtype'])) {
			$data['zonehandlingtype'] = 'none';
		}

		if(!isset($data['zonehandlingfee'])) {
			$data['zonehandlingfee'] = 0;
		}

		$zoneData = array(
			'zonename' => $data['zonename'],
			'zonetype' => $data['zonetype'],
			'zonefreeshipping' => $data['zonefreeshipping'],
			'zonefreeshippingtotal' => DefaultPriceFormat($data['zonefreeshippingtotal']),
			'zonehandlingtype' => $data['zonehandlingtype'],
			'zonehandlingfee' => DefaultPriceFormat($data['zonehandlingfee']),
			'zonehandlingseparate' => $data['zonehandlingseparate'],
			'zoneenabled' => $data['zoneenabled']
		);

		if(isset($data['zonevendorid'])) {
			$zoneData['zonevendorid'] = $data['zonevendorid'];
		}
		else if(isset($existingZone)) {
			$zoneData['zonevendorid'] = $existingZone['zonevendorid'];
		} else {
			$zoneData['zonevendorid'] = 0;
		}

		if($zoneId == 0) {
			$zoneId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("shipping_zones", $zoneData);
		}
		else {
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("shipping_zones", $zoneData, "zoneid='".(int)$zoneId."'");
		}

		$GLOBALS['ZoneId'] = $zoneId;

		// Couldn't save? return an error message
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			return false;
		}

		if(!isset($existingZone) || (isset($existingZone) && $existingZone['zonedefault'] != 1)) {
			// Delete the old locations first
			if(isset($existingZone)) {
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_zone_locations', "WHERE zoneid='".$zoneId."'");
			}

			// Now we insert the locations for this zone type.
			switch($data['zonetype']) {
				case 'country':
					$countryList = GetCountryListAsIdValuePairs();
					foreach($data['zonetype_country_list'] as $countryId) {
						if(!isset($countryList[$countryId])) {
							continue;
						}
						$newLocation = array(
							'zoneid'			=> $zoneId,
							'locationtype'		=> 'country',
							'locationvalue'		=> $countryList[$countryId],
							'locationvalueid'	=> $countryId,
							'locationvendorid'	=> (int)$zoneData['zonevendorid'],
						);
						$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zone_locations', $newLocation);
					}
					break;
				case 'state':
					$countryList = GetCountryListAsIdValuePairs();
					$stateList = array();
					foreach($data['zonetype_states'] as $stateRecord) {
						$state = explode('-', $stateRecord, 2);
						if(!isset($stateList[$state[0]])) {
							// Load the states in this country as we haven't done that before
							$stateList[$state[0]] = array();
							$query = "SELECT * FROM [|PREFIX|]country_states WHERE statecountry='".(int)$state[0]."'";
							$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
							while($stateResult = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
								$stateList[$stateResult['statecountry']][$stateResult['stateid']] = $stateResult['statename'];
							}
						}

						// Start storing what we received
						if(isset($stateList[$state[0]][$state[1]])) {
							$stateName = $stateList[$state[0]][$state[1]];
						}
						else {
							$stateName = '';
						}
						$newLocation = array(
							'zoneid'			=> $zoneId,
							'locationtype'		=> 'state',
							'locationvalue'		=> $stateName,
							'locationvalueid'	=> (int)$state[1],
							'locationcountryid'	=> (int)$state[0],
							'locationvendorid'	=> (int)$zoneData['zonevendorid'],
						);
						$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zone_locations', $newLocation);
					}
					break;
				case 'zip':
					$countryId = $data['zonetype_zip_country'];
					$countryName = GetCountryById($countryId);
					if(!$countryName) {
						return false;
					}

					// Now save all of the codes that were entered
					$zipCodes = explode("\n", $data['zonetype_zip_list']);
					foreach($zipCodes as $zipCode) {
						$zipCode = trim($zipCode);
						if(!$zipCode) {
							continue;
						}
						$newLocation = array(
							'zoneid'			=> $zoneId,
							'locationtype'		=> 'zip',
							'locationvalue'		=> $zipCode,
							'locationvalueid'	=> '0',
							'locationcountryid' => $countryId,
							'locationvendorid'	=> (int)$zoneData['zonevendorid'],
						);
						$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zone_locations', $newLocation);
					}
					break;
			}
		}

		// We've just configured shipping - mark it as so.
		if(!in_array('shippingOptions', GetConfig('GettingStartedCompleted'))) {
			GetClass('ISC_ADMIN_ENGINE')->MarkGettingStartedComplete('shippingOptions');
		}

		return $zoneId;
	}

	/**
	 * Save the changes to an existing shipping zone.
	 */
	private function SaveUpdatedShippingZone()
	{
		$zone = $this->GetShippingZoneData($_REQUEST['zoneId']);

		// If the zone doesn't exist, show an error message
		if(!isset($zone['zoneid'])) {
			FlashMessage(GetLang('InvalidShippingZone'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($zone['zonevendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $zone['zonevendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$zone['zonevendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$message = '';
		if(!$this->ValidateShippingZone($_POST, $message)) {
			FlashMessage($message, MSG_ERROR);
			$this->EditShippingZone();
			return;
		}

		if(!$this->CommitShippingZone($_POST, $zone['zoneid'])) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingShippingZone').$error, MSG_ERROR);
			$this->EditShippingZone();
			return;
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($zone['zoneid'], $_POST['zonename']);
			if($zone['zonevendorid'] > 0) {
				$url = 'index.php?ToDo=editVendor&currentTab=1&vendorId='.(int)$zone['zonevendorid'];
			}
			else {
				$url = 'index.php?ToDo=viewShippingZones&currentTab=1';
			}
			FlashMessage(GetLang('ShippingZoneUpdated'), MSG_SUCCESS, $url);
		}
	}

	/**
	 * Add a new shipping zone.
	 */
	private function AddShippingZone()
	{
		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$GLOBALS['FormAction']	= 'SaveNewShippingZone';
		$GLOBALS['Intro']		= GetLang('AddShippingZoneIntro');

		// Generate the breadcrumb
		if(isset($_REQUEST['vendorId']) || $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				$vendorId = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
			}
			else {
				$vendorId = (int)$_REQUEST['vendorId'];
			}

			$vendorCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Vendors');
			$vendor = $vendorCache[$vendorId];
			if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
				$GLOBALS['BreadcrumEntries'][GetLang('Vendors')] = "index.php?ToDo=viewVendors";
				$GLOBALS['BreadcrumEntries'][isc_html_escape($vendor['vendorname'])] = 'index.php?ToDo=editVendor&amp;vendorId='.$vendor['vendorid'];
			}
			else {
				$GLOBALS['BreadcrumEntries'][GetLang('VendorProfile')] = 'index.php?ToDo=editVendor&amp;vendorId='.$vendor['vendorid'];
			}
		}
		else {
			$GLOBALS['BreadcrumEntries'][GetLang('Settings')] = "index.php?ToDo=viewSettings";
			$GLOBALS['BreadcrumEntries'][GetLang('ShippingSettings')] = "index.php?ToDo=viewShippingSettings";
		}
		$GLOBALS['BreadcrumEntries'][GetLang('AddShippingZone')] = '';

		if (GetConfig('CurrencyLocation') == 'right') {
			$GLOBALS['RightCurrencyToken'] = GetConfig('CurrencyToken');
		} else {
			$GLOBALS['LeftCurrencyToken'] = GetConfig('CurrencyToken');
		}

		$GLOBALS['HideZoneTypeCountry']		= 'display: none';
		$GLOBALS['HideZoneTypeStates']		= 'display: none';
		$GLOBALS['HideStateSelect']			= 'display: none';
		$GLOBALS['HideZoneTypePostCodes']	= 'display: none';
		$GLOBALS['HideFreeShipping']		= 'display: none';
		$GLOBALS['HideHandlingFeeGlobal']	= 'display: none';
		$GLOBALS['HideShippingMethods']		= 'display: none';
		$GLOBALS['HideDefaultZoneType']		= 'display: none;';
		$GLOBALS['HideHandlingSeparate']	= 'display: none';

		$GLOBALS['NextButton'] = "Next &raquo;";

		$GLOBALS['Title'] = GetLang('AddShippingZone');

		$selectedCountries = array();
		$selectedStates = array();
		$selectedPostCodes = array();

		// If we're coming back here - set up the values
		if($_SERVER['REQUEST_METHOD'] == "POST") {
			$GLOBALS['ZoneName'] = isc_html_escape($_POST['zonename']);

			// Was free shipping checked?
			if(isset($_POST['zonefreeshipping'])) {
				$GLOBALS['FreeShippingChecked'] = 'checked="checked"';
				$GLOBALS['FreeShippingTotal'] = number_format($_POST['zonefreeshippingtotal'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), "");
			}
			else {
				$GLOBALS['HideFreeShipping'] = 'display: none';
			}

			// Handling set up?
			if(isset($_POST['zonehandlingtype'])) {

				// No handling applied? Check that box
				if($_POST['zonehandlingtype'] == 'none') {
					$GLOBALS['HandlingNoneChecked'] = 'checked="checked"';
				}
				// Handling is specified on a global level for this zone
				else if($_POST['zonehandlingtype'] == 'global') {
					$GLOBALS['HandlingGlobalChecked'] = 'checked="checked"';
					$GLOBALS['HideHandlingFeeGlobal'] = '';
					$GLOBALS['HandlingFee'] = number_format($_POST['zonehandlingfee'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), "");
				}
				// Handling is specified per shipping option in this zone
				else if($_POST['zonehandlingtype'] == 'module') {
					$GLOBALS['HandlingOptionChecked'] = 'checked="checked"';
				}
			}

			if(isset($_POST['zonehandlingseparate'])) {
				$GLOBALS['HandlingSeparateChecked'] = 'checked="checked"';
			}
			else {
				$zone['zonehandlingseparate'] = 0;
			}

			// if the zone is country based, then show the country options
			if($_POST['zonetype'] == 'country') {
				$GLOBALS['HideZoneTypeCountry'] = '';
				$GLOBALS['TypeCountriesChecked'] = 'checked="checked"';
				$selectedCountries = $_POST['zonetype_country_list'];
			}

			// If the zone type is state based, then show the state options
			else if($_POST['zonetype'] == 'state') {
				$GLOBALS['HideZoneTypeStates'] = '';
				$GLOBALS['HideStateSelect'] = '';
				$GLOBALS['HideStateSelectNode'] = 'display: none;';
				$GLOBALS['TypeStatesChecked'] = 'checked="checked"';
				foreach($_POST['zonetype_states'] as $state) {
					$state = explode('-', $state);
					$selectedStates[$state[0]][] = $state[1];
					$selectedCountries[$state[0]] = $state[0];
				}
			}

			// Finally, if it's post code/zip code based then we show the zip code textbox
			else if($_POST['zonetype'] == 'zip') {
				$GLOBALS['HideZoneTypePostCodes'] = '';
				$GLOBALS['TypeZipChecked'] = 'checked="checked"';
				$selectedCountries[] = $_POST['zonetype_zip_country'];
				$GLOBALS['ZonePostCodes'] = isc_html_escape($_POST['zonetype_zip_list']);
			}

			// Now we can build the select lists
			$GLOBALS['SingleCountrySelect']		= GetCountryList($selectedCountries);
			$GLOBALS['MultipleCountrySelect']	= GetCountryList($selectedCountries, false);
			if(!empty($selectedStates)) {
				$GLOBALS['StateSelect'] 		= GetMultiCountryStateOptions($selectedStates);
				$GLOBALS['HideStateSelectNone'] = "display: none;";
			}
		}

		// Fetch & set up the defaults
		else {
			$GLOBALS['SingleCountrySelect']		= GetCountryList();
			$GLOBALS['MultipleCountrySelect']	= GetCountryList('', false);

			$GLOBALS['HandlingSeparateChecked'] = 'checked="checked"';
			$GLOBALS['HandlingNoneChecked']		= "checked=\"checked\"";
		}

		if(isset($_POST['zoneenabled']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
			$GLOBALS['ZoneEnabledCheck'] = 'checked="checked"';
		}

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('shippingzone.form.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	/**
	 * Save the new shipping zone in the database.
	 */
	private function SaveNewShippingZone()
	{
		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$message = '';

		if(!$this->ValidateShippingZone($_POST, $message)) {
			FlashMessage($message, MSG_ERROR);
			$this->AddShippingZone();
			return;
		}

		if(!$this->CommitShippingZone($_POST)) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingShippingZone').$error, MSG_ERROR);
			$this->AddShippingZone();
			return;
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['zonename']);
			FlashMessage(GetLang('ShippingZoneCreated'), MSG_SUCCESS, 'index.php?ToDo=editShippingZone&zoneId='.$GLOBALS['ZoneId'].'&currentTab=1&created=1');
		}
	}

	/**
	 * Copy a shipping zone to a new shipping zone and take the user to the edit page for it.
	 */
	private function CopyShippingZone()
	{
		if(!isset($_REQUEST['zoneId'])) {
			FlashMessage(GetLang('InvalidShippingZone'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		$zoneData = $this->GetShippingZoneData($_REQUEST['zoneId']);

		if(!isset($zoneData['zoneid'])) {
			FlashMessage(GetLang('InvalidShippingZone'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		if($zoneData['zonevendorid'] && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() != $zoneData['zonevendorid']) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}
		else if(!$zoneData['zonevendorid'] && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		// Copy the zone
		$newZone = $zoneData;
		unset($newZone['zoneid']);
		$newZone['zonename'] = 'Copy of '.$zoneData['zonename'];
		$newZone['zonedefault'] = 0;
		$newZoneId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zones', $newZone);

		if(!$newZoneId) {
			FlashMessage($GLOBALS['ISC_CLASS_DB']->GetErrorMsg(), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab=1');
		}

		// Copy the shipping locations
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_zone_locations
			WHERE zoneid='".$zoneData['zoneid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($location = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$location['zoneid'] = $newZoneId;
			unset($location['locationid']);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zone_locations', $location);
		}

		// Copy the shipping methods
		$newMethods = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_methods
			WHERE zoneid='".$zoneData['zoneid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($method = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$methodId = $method['methodid'];
			unset($method['methodid']);
			$method['zoneid'] = $newZoneId;
			$newMethods[$methodId] = $GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_methods', $method);
		}

		// Now copy the shipping vars
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_vars
			WHERE zoneid='".$zoneData['zoneid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($var = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$var['methodid'] = $newMethods[$var['methodid']];
			$var['zoneid'] = $newZoneId;
			unset($var['variableid']);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_vars', $var);
		}

		// OK, everything has been copied that will be copied. Take them to the edit zone page to configure the locations
		FlashMessage(GetLang('ShippingZoneCopied'), MSG_SUCCESS, 'index.php?ToDo=editShippingZone&zoneId='.$newZoneId);
	}

	/**
	 * Delete one or more selected shipping zones.
	 */
	private function DeleteShippingZones()
	{
		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		if(isset($_REQUEST['zones'])) {
			$zoneIds = array_map('intval', $_REQUEST['zones']);
			$zoneIds[] = 0;
			$zoneIds = implode("','", $zoneIds);

			// We can only delete what we have permission to, so if we're a vendor - only those we've created
			$vendorRestriction = '';
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() > 0) {
				$vendorRestriction = " AND zonevendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
			}

			// Make sure we're not deleting a default zone
			$vendorId = 0;
			$query = "
				SELECT zoneid, zonevendorid
				FROM [|PREFIX|]shipping_zones
				WHERE zoneid IN ('".$zoneIds."') AND zonedefault='0' ".$vendorRestriction."
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$zoneIds = array();
			while($zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$zoneIds[] = $zone['zoneid'];
				$vendorId = $zone['zonevendorid'];
			}
			$zoneIds = implode("','", $zoneIds);

			// Delete the zones from the database
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_zones', "WHERE zoneid IN ('".$zoneIds."')");
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_zone_locations', "WHERE zoneid IN ('".$zoneIds."')");
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_vars', "WHERE zoneid IN ('".$zoneIds."')");
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_methods', "WHERE zoneid IN ('".$zoneIds."')");

			$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();

			if($vendorId > 0) {
				$url = 'index.php?ToDo=editVendor&vendorId='.$vendorId.'&currentTab=1';
			}
			else {
				$url = 'index.php?ToDo=viewShippingSettings&currentTab=1';
			}
			if($err) {
				FlashMessage($err, MSG_ERROR, $url);
			}
			else {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminaction(count($_POST['zones']));
				FlashMessage(GetLang('ShippingZonesDeleted'), MSG_SUCCESS, $url);
			}
		}
	}

	/**
	 * Generate a table-based grid of shipping zones.
	 *
	 * @param int Passed by reference, the number of shipping zones we'll be showing.
	 * @return string The built HTML for the grid containing the shipping zones.
	 */
	public function ManageShippingZonesGrid(&$numZones)
	{
		$page = 0;
		$start = 0;
		$numZones = 0;
		$GLOBALS['ZonesGrid'] = '';
		$GLOBALS['Nav'] = '';

		if(isset($_REQUEST['page'])) {
			$page = (int)$_REQUEST['page'];
		}
		else {
			$page = 1;
		}

		// Where are we starting at?
		if($page == 1) {
			$start = 0;
		}
		else {
			$start = ($page * ISC_SHIPPING_ZONES_PER_PAGE) - (ISC_SHIPPING_ZONES_PER_PAGE);
		}

		// Get the names of the countries we support
		$countryList = GetCountryListAsIdValuePairs();

		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() > 0) {
			$vendorRestriction = " AND zonevendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
		}
		else if(isset($_REQUEST['vendorId'])) {
			$vendorRestriction = " AND zonevendorid='".(int)$_REQUEST['vendorId']."'";
		}
		else {
			$vendorRestriction = " AND zonevendorid='0'";
		}

		// Fetch the list of shipping zones
		$query = "
			SELECT COUNT(zoneid)
			FROM [|PREFIX|]shipping_zones
			WHERE 1=1 ".$vendorRestriction."
		";
		$numZones = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);

		// If there aren't any zones set up, just return nothing here
		if($numZones == 0) {
			return '';
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_zones
			WHERE 1=1 ".$vendorRestriction."
			ORDER BY zonedefault DESC, zonename ASC
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_SHIPPING_ZONES_PER_PAGE);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

		$numPages = ceil($numZones / ISC_SHIPPING_ZONES_PER_PAGE);

		// Add the "(Page x of n)" label
		if($numZones > ISC_SHIPPING_ZONES_PER_PAGE) {
			$GLOBALS['Nav'] = "(".GetLang('Page')." ".$page." of ".$numPages.") &nbsp;&nbsp;&nbsp;";
			$GLOBALS['Nav'] .= BuildPagination($numZones, ISC_SHIPPING_ZONES_PER_PAGE, $page, "index.php?ToDo=viewShippingSettings&currentTab=1".$GLOBALS['VendorIdAdd']);
		}
		else {
			$GLOBALS['Nav'] = "";
			$GLOBALS['HidePaging'] = 'display: none';
		}

		$zones = array();
		while($zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['ZoneId'] = $zone['zoneid'];
			$GLOBALS['ZoneName'] = isc_html_escape($zone['zonename']);
			$GLOBALS['ZoneLocations'] = '';

			if($zone['zonedefault'] == 1) {
				$GLOBALS['ZoneClass'] = 'GridRowSel';
				$GLOBALS['ZoneDeleteCheckbox'] = " disabled='disabled'";
				$GLOBALS['ZoneName'] .= " <span style='margin-left: font-size: 11px; font-weight: bold;'>(".GetLang('ShippingZoneEverywhereElse')." - <a href='#' onclick='alert(\"".GetLang('ShippingZoneEverywhereElseHelp')."\"); this.blur();'>".GetLang('WhatDoesThisMean')."</a>)</span>";
				$GLOBALS['ZoneType'] = GetLang('ZoneTypeGlobal');
				$GLOBALS['ZoneStatus'] = '&nbsp;';
				$GLOBALS['HideDeleteZone'] = 'display: none';
			}
			else {
				$GLOBALS['HideDeleteZone'] = '';
				$GLOBALS['ZoneClass'] = 'GridRow';
				$GLOBALS['ZoneDeleteCheckbox'] = '';

				if($zone['zonetype'] == "state") {
					$GLOBALS['ZoneType'] = GetLang('ZoneTypeState');
				}
				else if($zone['zonetype'] == "zip") {
					$GLOBALS['ZoneType'] = GetLang('ZoneTypeZip');
				}
				else {
					$GLOBALS['ZoneType'] = GetLang('ZoneTypeCountry');
				}

				if($zone['zoneenabled'] == 1) {
					$statusImage = 'tick.gif';
				}
				else {
					$statusImage = 'cross.gif';
				}
				$GLOBALS['ZoneStatus'] = "<a href='index.php?ToDo=toggleShippingZoneStatus&amp;zoneId=".$zone['zoneid']."'><img src='images/".$statusImage."' alt='' border='0' /></a>";
			}

			$gridRow = $this->template->render('shippingzones.manage.row.tpl');
			if($zone['zoneid'] == 1) {
				$GLOBALS['ZonesGrid'] = $gridRow . $GLOBALS['ZonesGrid'];
			}
			else {
				$GLOBALS['ZonesGrid'] .= $gridRow;
			}
		}

		return $this->template->render('shippingzones.manage.grid.tpl');
	}

	/**
	 * Save the updated store location settings.
	 */
	private function SaveUpdatedShippingSettings()
	{
		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$companyCountry = GetCountryById((int)$_POST['companycountry']);
			if (isset($_POST['companystate']) && $_POST['companystate'] != "") {
			$companyState = GetStateById((int)$_POST['companystate']);
		}
		else {
			$companyState = $_POST['companystate1'];
		}

		$companyZip = $_POST['companyzip'];

		// Push everything to globals and save
		$GLOBALS['ISC_NEW_CFG']['CompanyName'] = $_POST['companyname'];
		$GLOBALS['ISC_NEW_CFG']['CompanyAddress'] = $_POST['companyaddress'];
		$GLOBALS['ISC_NEW_CFG']['CompanyCity'] = $_POST['companycity'];
		$GLOBALS['ISC_NEW_CFG']['CompanyCountry'] = $companyCountry;
		$GLOBALS['ISC_NEW_CFG']['CompanyState'] = $companyState;
		$GLOBALS['ISC_NEW_CFG']['CompanyZip'] = $companyZip;

		$settings = GetClass('ISC_ADMIN_SETTINGS');
		$messages = array();
		if ($settings->CommitSettings($messages)) {
			if (is_array($messages)) {
				foreach($messages as $message => $status) {
					FlashMessage($message, $status);
				}
			}
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();

			$redirectUrl = 'index.php?ToDo=viewShippingSettings';
			if(!in_array('shippingOptions', GetConfig('GettingStartedCompleted'))) {
				$redirectUrl = 'index.php?ToDo=viewShippingSettings&currentTab=1';
			}
			FlashMessage(GetLang('ShippingSettingsSavedSuccessfully'), MSG_SUCCESS, $redirectUrl);
		}
		else {
			FlashMessage(GetLang('ShippingSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewShippingSettings&currentTab='.((int) $_POST['currentTab']));
		}
	}

	/**
	 * Manage the store location settings and show a list of shipping zones for management.
	 */
	private function ManageShippingSettings()
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$GLOBALS['Message'] = GetFlashMessageBoxes();

		// Get the getting started box if we need to
		$GLOBALS['GettingStartedStep'] = '';
		if(empty($GLOBALS['Message']) && (isset($_GET['wizard']) && $_GET['wizard']==1) && !in_array('shippingOptions', GetConfig('GettingStartedCompleted')) && !GetConfig('DisableGettingStarted')) {
			$GLOBALS['GettingStartedTitle'] = GetLang('WizardShippingOptions');
			$GLOBALS['GettingStartedContent'] = GetLang('WizardShippingOptionsDesc');
			$GLOBALS['GettingStartedStep'] = $this->template->render('Snippets/GettingStartedModal.html');
		}

		// Generate the breadcrumb
		$GLOBALS['BreadcrumEntries'][GetLang('Settings')] = "index.php?ToDo=viewSettings";
		$GLOBALS['BreadcrumEntries'][GetLang('ShippingSettings')] = "index.php?ToDo=viewShippingSettings";

		if(isset($_REQUEST['currentTab'])) {
			$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
		}

		// Fetch any shipping zones, place them in the data grid
		$GLOBALS['ZoneDataGrid'] = $this->ManageShippingZonesGrid($numZones);

		// Was this an ajax based sort? Return the table now
		if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
			echo $GLOBALS['ZoneDataGrid'];
			return;
		}

		// No shipping zones have been configured yet
		if($numZones == 0) {
			$GLOBALS['DisableDelete'] = 'disabled="disabled"';
			$GLOBALS['DisplayGrid'] = "none";
			$GLOBALS['Message'] = MessageBox(GetLang('NoShippingZones'), MSG_SUCCESS);
		}

		$GLOBALS['CompanyName'] = GetConfig('CompanyName');
		$GLOBALS['CompanyAddress'] = GetConfig('CompanyAddress');
		$GLOBALS['CompanyCity'] = GetConfig('CompanyCity');
		$GLOBALS['CompanyZip'] = GetConfig('CompanyZip');

		$stateOptions = sprintf("<option value=''>%s</option>", GetLang('ChooseState'));
		$stateOptions .= GetStatesByCountryNameAsOptions(GetConfig('CompanyCountry'), $numStates, GetConfig('CompanyState'));

		if (GetConfig('CompanyState') != "") {
			$GLOBALS['HideStateNote'] = "none";
		} else {
			$GLOBALS['HideStateBox'] = "none";
		}

		if ($numStates > 0) {
			// Show the states dropdown list
			$GLOBALS['StateList'] = $stateOptions;
			$GLOBALS['HideStateBox'] = "none";
		}
		else {
			// Show the states text box
			$GLOBALS['CompanyState'] = GetConfig('CompanyState');
			$GLOBALS['HideStateList'] = "none";
		}

		$GLOBALS['CountryList'] = GetCountryList(GetConfig('CompanyCountry'));
		$GLOBALS['ShippingProviders'] = $this->GetShippingCompaniesAsOptions();

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('settings.shipping.manage.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}
}