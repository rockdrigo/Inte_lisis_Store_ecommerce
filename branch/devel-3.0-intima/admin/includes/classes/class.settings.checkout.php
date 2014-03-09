<?php

class ISC_ADMIN_SETTINGS_CHECKOUT extends ISC_ADMIN_BASE
{
	/**
	 * @var object Instance of the template class.
	 */
	protected $template = null;

	public function __construct()
	{
		parent::__construct();

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings.checkout');

		include_once(ISC_BASE_PATH . '/lib/form.php');
		$GLOBALS['ISC_CLASS_FORM'] = new ISC_FORM();
	}

	/**
	 * Handle the action for this section.
	 *
	 * @param string The name of the action to do.
	 */
	public function HandleToDo($Do)
	{
		if (isset($_REQUEST['currentTab'])) {
			$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
		}
		else {
			$GLOBALS['CurrentTab'] = 0;
		}

		$GLOBALS['BreadcrumEntries'] = array (
			GetLang('Home') => "index.php",
			GetLang('Settings') => "index.php?ToDo=viewSettings",
			GetLang('CheckoutSettings') => "index.php?ToDo=viewCheckoutSettings"
		);

		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		switch(isc_strtolower($Do))
		{
			case "saveupdatedcheckoutsettings":
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->SaveUpdatedCheckoutSettings();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "viewcheckoutsettings":
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->ManageCheckoutSettings();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;

			default:
				$this->ManageCheckoutSettings();
				break;
		}
	}

	private function ManageCheckoutSettings($messages=array())
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		// Get the getting started box if we need to
		$GLOBALS['GettingStartedStep'] = '';
		if(empty($GLOBALS['Message']) && (isset($_GET['wizard']) && $_GET['wizard']==1) && !in_array('paymentMethods', GetConfig('GettingStartedCompleted')) && !GetConfig('DisableGettingStarted')) {
			$GLOBALS['GettingStartedTitle'] = GetLang('WizardPaymentMethods');
			$GLOBALS['GettingStartedContent'] = GetLang('WizardPaymentMethodsDesc');
			$GLOBALS['GettingStartedStep'] = $this->template->render('Snippets/GettingStartedModal.html');
		}

		$GLOBALS['CheckoutJavaScript'] = "";
		$GLOBALS['CheckoutProviders'] = $this->GetCheckoutProvidersAsOptions();

		// Which checkout modules are enabled?
		$checkouts = GetEnabledCheckoutModules();
		$GLOBALS['CheckoutTabs'] = "";
		$GLOBALS['CheckoutDivs'] = "";
		$count = 1;
		$builtInGateway = null;

		if (GetConfig('EnableBuiltInGateway')) {
			GetModuleById('checkout', $builtInGateway, GetConfig('BuiltInGateway'));
		}

		if(GetConfig('EnableBuiltInGateway') && !is_null($builtInGateway)) {
			$GLOBALS['BuiltInGateway'] = $builtInGateway->GetId();
			$GLOBALS['UseBuiltInGateway'] = GetLang('UseBuiltInGateway', array(
				'gatewayName' => $builtInGateway->GetName()
			));
			$GLOBALS['BuiltInGatewayProperties'] = $builtInGateway->GetPropertiesSheet(0, false, 'builtin');
			$GLOBALS['BuiltInGatewayIntro'] = $builtInGateway->GetHelpText();
			$GLOBALS['CheckoutProviderClass'] = 'CheckoutProviderListIndent';
			$errors = array();
			if($builtInGateway->IsSupported() == false) {
				foreach($builtInGateway->GetErrors() as $error) {
					$errors[] = MessageBox($error, MSG_ERROR);
				}
			}
			$GLOBALS['BuiltInGatewayErrors'] = implode('', $errors);
		}
		else {
			$this->template->Assign('HideBuiltInGateway', 'display: none');
		}

		// Setup each checkout module with its own tab
		if(GetConfig('EnableBuiltInGateway') && !is_null($builtInGateway) && (GetConfig('CheckoutMethods') == 'checkout_'.GetConfig('BuiltInGateway') || !GetConfig('CheckoutMethods'))) {
			$this->template->Assign('UseBuiltInGatewayChecked', 'checked="checked"');
		}
		else {
			$this->template->Assign('UseCustomGatewayChecked', 'checked="checked"');
			foreach ($checkouts as $checkout) {
				$GLOBALS['CheckoutTabs'] .= sprintf('<li><a href="#" id="tab%d" onclick="ShowTab(%d)">%s</a></li>', $count, $count, $checkout['name']);
				$GLOBALS['CheckoutDivs'] .= sprintf('<div id="div%d" style="padding-top: 10px;">%s</div>', $count, $checkout['object']->getpropertiessheet($count));
				$count++;
			}
		}

		// Get a list of order statuses for the status change notifications
		$statuses = explode(",", GetConfig('OrderStatusNotifications'));
		$GLOBALS['OrderStatusEmailList'] = '';
		$query = "SELECT * FROM [|PREFIX|]order_status ORDER BY statusorder ASC";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if (in_array($row['statusid'], $statuses)) {
				$sel = 'selected="selected"';
			} else {
				$sel = '';
			}

			$GLOBALS['OrderStatusEmailList'] .= sprintf("<option value='%s' %s>%s</option>", $row['statusid'], $sel, sprintf(GetLang('OrderStatusChangedTo'), $row['statusdesc']));
		}

		if (GetConfig('UpdateInventoryLevels') == 1) {
			$GLOBALS['UpdateInventorySuccessfulSelected'] = 'selected="selected"';
		}
		else {
			$GLOBALS['UpdateInventoryCompletedSelected'] = 'selected="selected"';
		}

		if (GetConfig('CurrencyLocation') == 'right') {
			$GLOBALS['RightCurrencyToken'] = GetConfig('CurrencyToken');
		} else {
			$GLOBALS['LeftCurrencyToken'] = GetConfig('CurrencyToken');
		}

		if(GetConfig('CheckoutType') == 'single') {
			$GLOBALS['CheckoutTypeSingleSelected'] = 'selected="selected"';
		}
		else {
			$GLOBALS['CheckoutTypeMultiSelected'] = 'selected="selected"';
		}

		if(GetConfig('GuestCheckoutEnabled')) {
			$GLOBALS['GuestCheckoutChecked'] = 'checked="checked"';
		}
		else {
			$GLOBALS['HideGuestCheckoutCreateAccounts'] = 'display: none';
		}

		if(GetConfig('GuestCheckoutCreateAccounts')) {
			$GLOBALS['GuestCheckoutCreateAccountsCheck'] = 'checked="checked"';
		}

		if(GetConfig('DigitalOrderHandlingFee') > 0) {
			$GLOBALS['DigitalOrderHandlingFeeChecked'] = 'checked="checked"';
			$GLOBALS['DigitalOrderHandlingFee'] = GetConfig('DigitalOrderHandlingFee');
		}
		else {
			$GLOBALS['HideDigitalOrderHandlingFee'] = 'display: none';
		}

		if(GetConfig('EnableOrderComments')) {
			$GLOBALS['IsEnableOrderComments'] = "checked=\"checked\"";
		}

		if(GetConfig('EnableOrderTermsAndConditions')) {
			$GLOBALS['IsEnableOrderTermsAndConditions'] = "checked=\"checked\"";
		}
		else {
			$GLOBALS['IsEnableOrderTermsAndConditions'] = "";
			$GLOBALS['HideOrderTermsAndConditions'] = 'display:none;';
		}

		if(GetConfig('OrderTermsAndConditionsType') != "textarea") {
			$GLOBALS['HideOrderTermsAndConditionsTextarea'] = 'display: none';
		} else {
			$GLOBALS['IsEnableOrderTermsAndConditionsTextarea'] = "checked=\"checked\"";
			$GLOBALS['OrderTermsAndConditions'] = GetConfig('OrderTermsAndConditions');
		}

		if(GetConfig('OrderTermsAndConditionsType') != "link") {
			$GLOBALS['HideOrderTermsAndConditionsLink'] = 'display: none';
			$GLOBALS['OrderTermsAndConditionsLink'] = "http://";
		} else {
			$GLOBALS['IsEnableOrderTermsAndConditionsLink'] = "checked=\"checked\"";
			$GLOBALS['OrderTermsAndConditionsLink'] = GetConfig('OrderTermsAndConditionsLink');
		}

		if(GetConfig('MultipleShippingAddresses') && gzte11(ISC_MEDIUMPRINT)) {
			$GLOBALS['IsMultipleShippingAddressesEnabled'] = "checked=\"checked\"";
		}
		else if(!gzte11(ISC_MEDIUMPRINT)) {
			$GLOBALS['HideMultiShipping'] = 'display: none';
		}
		
		if ($GLOBALS['ISC_CFG']['CheckoutUseExtraFields'] == NULL || $GLOBALS['ISC_CFG']['CheckoutUseExtraFields'] == 0)  $GLOBALS['CheckoutUseExtraFieldsChecked'] = '';
		elseif ($GLOBALS['ISC_CFG']['CheckoutUseExtraFields'] == 1)  $GLOBALS['CheckoutUseExtraFieldsChecked'] = ' checked="checked" ';
		
		if(GetConfig('CheckoutUseExtraFields')) {
			$GLOBALS['CheckoutUseExtraFieldsChecked'] = " checked=\"checked\" ";
			$GLOBALS['CheckoutExtraFieldRow1Display'] = "";
			$GLOBALS['CheckoutExtraFieldRow2Display'] = "";
			$GLOBALS['CheckoutExtraFieldRow3Display'] = "";
			$GLOBALS['CheckoutExtraFieldRow4Display'] = "";
			$GLOBALS['CheckoutExtraFieldRow5Display'] = "";
		}
		else {
			$GLOBALS['CheckoutUseExtraFieldsChecked'] = "";
			$GLOBALS['CheckoutExtraFieldRow1Display'] = "display: none;";
			$GLOBALS['CheckoutExtraFieldRow2Display'] = "display: none;";
			$GLOBALS['CheckoutExtraFieldRow3Display'] = "display: none;";
			$GLOBALS['CheckoutExtraFieldRow4Display'] = "display: none;";
			$GLOBALS['CheckoutExtraFieldRow5Display'] = "display: none;";
		}
		
		$numCheckoutExtraFields = 5;
		for ($i=1;$i<=5;$i++) {
		if(GetConfig('CheckoutExtraFieldActive'.$i)) {
			$GLOBALS['CheckoutExtraFieldActive'.$i.'Checked'] = " checked=\"checked\" ";
			$GLOBALS['CheckoutExtraFieldName'.$i.'Value'] = GetConfig('CheckoutExtraFieldName'.$i);
			$GLOBALS['CheckoutExtraFieldValue'.$i.'Value'] = GetConfig('CheckoutExtraFieldValue'.$i);
			if (GetConfig('CheckoutExtraFieldRequired'.$i) == 1) $GLOBALS['CheckoutExtraFieldRequired'.$i.'Checked'] = " checked=\"checked\" ";
			else $GLOBALS['CheckoutExtraFieldRequired'.$i.'Checked'] = "";
			$GLOBALS['DivCheckoutExtraFieldActive'.$i.'Display'] = "";
			$GLOBALS['CheckoutExtraFieldType'.$i.'Selected'.GetConfig('CheckoutExtraFieldType'.$i)] = " selected=\"selected\"";
		}
		else {
			$GLOBALS['CheckoutExtraFieldActive'.$i.'Checked'] = "";
			$GLOBALS['CheckoutExtraFieldRequired'.$i.'Checked'] = "";
			$GLOBALS['DivCheckoutExtraFieldActive'.$i.'Display'] = "display: none;";
		}
		}

		$this->template->display('settings.checkout.manage.tpl');
	}

	private function SaveUpdatedCheckoutSettings()
	{
		// Firstly we will delete *all* existing module variables for shippers. This way, if one
		// was previously configured and unchecked then its old variables wont be saved and it
		// wont be marked as configured even when it's not
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename like 'checkout_%'");

		if (!isset($_POST['checkoutproviders'])) {
			$_POST['checkoutproviders'] = array();
		}

		// If they've selected to use the built in provider, override any other selections
		// coming in from the request
		if(GetConfig('EnableBuiltInGateway') && $_POST['builtInGateway'] == 1) {
			$_POST['checkoutproviders'] = array(
				'checkout_'.GetConfig('BuiltInGateway')
			);
			$_POST['checkout_'.GetConfig('BuiltInGateway')] = $_POST['builtin'];
		}

		$enabledStack = array();
		$messages = array();

		// Can the selected payment modules be enabled?
		foreach ($_POST['checkoutproviders'] as $provider) {
			GetModuleById('checkout', $module, $provider);
			if (is_object($module)) {
			// Is this checkout provider supported on this server?
				if($module->IsSupported() == false) {
					$errors = $module->GetErrors();
					foreach($errors as $error) {
						FlashMessage($error, MSG_ERROR);
					}
					continue;
				}

				// Otherwise, this checkout provider is fine, so add it to the stack of enabled
				$enabledStack[] = $provider;
			}
		}

		// A list of the checkout modules we've just enabled
		$justEnabled = array_diff($enabledStack, explode(',', GetConfig('CheckoutMethods')));

		$checkoutproviders = implode(",", $enabledStack);
		$GLOBALS['ISC_NEW_CFG']['CheckoutMethods'] = $checkoutproviders;

		// Save the order settings they specified too
		if ($_POST['updateinventory'] == 1) {
			$GLOBALS['ISC_NEW_CFG']['UpdateInventoryLevels'] = 1;
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['UpdateInventoryLevels'] = 0;
		}

		$GLOBALS['ISC_NEW_CFG']['UpdateInventoryOnOrderEdit'] = (int)Interspire_Request::post('UpdateInventoryOnOrderEdit', 0);
		$GLOBALS['ISC_NEW_CFG']['UpdateInventoryOnOrderDelete'] = (int)Interspire_Request::post('UpdateInventoryOnOrderDelete', 0);

		$GLOBALS['ISC_NEW_CFG']['DigitalOrderHandlingFee'] = 0;
		if(isset($_POST['EnableDigitalOrderHandlingFee'])) {
			$GLOBALS['ISC_NEW_CFG']['DigitalOrderHandlingFee'] = $_POST['DigitalOrderHandlingFee'];
		}

		// Save any selected notification statuses
		$GLOBALS['ISC_NEW_CFG']['OrderStatusNotifications'] = '';
		if (isset($_POST['orderstatusemails']) && is_array($_POST['orderstatusemails'])) {
			$GLOBALS['ISC_NEW_CFG']['OrderStatusNotifications'] = implode(",", array_map("intval", $_POST['orderstatusemails']));
		}

		if($_POST['CheckoutType'] == 'single') {
			$GLOBALS['ISC_NEW_CFG']['CheckoutType'] = 'single';
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['CheckoutType'] = 'multipage';
		}

		if(isset($_POST['EnableOrderComments'])) {
			$GLOBALS['ISC_NEW_CFG']['EnableOrderComments'] = 1;
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['EnableOrderComments'] = 0;
		}


		if(isset($_POST['EnableOrderTermsAndConditions']) && isset($_POST['OrderTermsAndConditionsType'])) {

			if($_POST['OrderTermsAndConditionsType'] == 'link') {
				if(trim($_POST['OrderTermsAndConditionsLink']) == '' || trim($_POST['OrderTermsAndConditionsLink']) == "http://") {
					FlashMessage(GetLang('EnterTermsAndConditionsLink'), MSG_ERROR);
				} else {
					$GLOBALS['ISC_NEW_CFG']['OrderTermsAndConditionsLink'] = $_POST['OrderTermsAndConditionsLink'];
				}
			} else {
				if(trim($_POST['OrderTermsAndConditionsTextarea']) == '') {
					FlashMessage(GetLang('EnterTermsAndConditions'), MSG_ERROR);
				} else {
					$GLOBALS['ISC_NEW_CFG']['OrderTermsAndConditions'] = $_POST['OrderTermsAndConditionsTextarea'];
				}
			}
			$GLOBALS['ISC_NEW_CFG']['OrderTermsAndConditionsType'] = $_POST['OrderTermsAndConditionsType'];
			$GLOBALS['ISC_NEW_CFG']['EnableOrderTermsAndConditions'] = 1;
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['EnableOrderTermsAndConditions'] = 0;
			$GLOBALS['ISC_NEW_CFG']['OrderTermsAndConditions'] = "";
		}

		if(isset($_POST['MultipleShippingAddresses'])) {
			$GLOBALS['ISC_NEW_CFG']['MultipleShippingAddresses'] = 1;
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['MultipleShippingAddresses'] = 0;
		}

		$GLOBALS['ISC_NEW_CFG']['GuestCheckoutEnabled'] = 0;
		$GLOBALS['ISC_NEW_CFG']['GuestCheckoutCreateAccounts'] = 0;

		if(isset($_POST['GuestCheckoutEnabled'])) {
			$GLOBALS['ISC_NEW_CFG']['GuestCheckoutEnabled'] = 1;
			if(isset($_POST['GuestCheckoutCreateAccounts'])) {
				$GLOBALS['ISC_NEW_CFG']['GuestCheckoutCreateAccounts'] = 1;
			}
		}
		
		if (isset($_POST['CheckoutUseExtraFields'])) $GLOBALS['ISC_NEW_CFG']['CheckoutUseExtraFields'] = 1;
		else $GLOBALS['ISC_NEW_CFG']['CheckoutUseExtraFields'] = 0;
		
		$numCheckoutExtraFields = 5;
		for ($i=1;$i<=$numCheckoutExtraFields;$i++) {
			if (isset($_POST['CheckoutUseExtraFields']) && isset($_POST['CheckoutExtraFieldActive'.$i])) {
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldActive'.$i] = 1;
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldName'.$i] = $_POST['CheckoutExtraFieldName'.$i];
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldType'.$i] = $_POST['CheckoutExtraFieldType'.$i];
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldValue'.$i] = $_POST['CheckoutExtraFieldValue'.$i];
				if (isset($_POST['CheckoutExtraFieldRequired'.$i])) $GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldRequired'.$i] = 1;
				else $GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldRequired'.$i] = 0;
			}
			else {
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldActive'.$i] = 0;
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldName'.$i] = '';
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldType'.$i] = '';
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldValue'.$i] = '';
				$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldRequired'.$i] = 0;
			}
		}
		
		/*
		$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldName2'] = $_POST['CheckoutExtraFieldName2'];
		$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldName3'] = $_POST['CheckoutExtraFieldName3'];
		$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldName4'] = $_POST['CheckoutExtraFieldName4'];
		$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldName5'] = $_POST['CheckoutExtraFieldName5'];
		
		$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldType2'] = $_POST['CheckoutExtraFieldType2'];
		$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldType3'] = $_POST['CheckoutExtraFieldType3'];
		$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldType4'] = $_POST['CheckoutExtraFieldType4'];
		$GLOBALS['ISC_NEW_CFG']['CheckoutExtraFieldType5'] = $_POST['CheckoutExtraFieldType5'];
		*/

		$settings = GetClass('ISC_ADMIN_SETTINGS');
		$messages = array();
		if ($settings->CommitSettings($messages)) {
			// Save the module settings to the module_vars table
			// First, delete all existing entries

			foreach($messages as $message => $status) {
				FlashMessage($message, $status);
			}

			// Delete existing module configuration
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'checkout\_%'");

			// Now get all checkout variables (they are in an array from $_POST)
			foreach($enabledStack as $module_id) {
				$vars = array();
				if(isset($_POST[$module_id])) {
					$vars = $_POST[$module_id];
				}

				GetModuleById('checkout', $module, $module_id);
				if (!$module->SaveModuleSettings($vars)) {
					$errors = $module->GetErrors();
					foreach($errors as $error) {
						FlashMessage($error, MSG_ERROR);
					}
				}
			}

			// Rebuild the cache of the checkout module variables
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCheckoutModuleVars();

			if ($GLOBALS['ISC_CLASS_DB']->Error() == "") {

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();

				// Just configured tax
				$redirectUrl = 'index.php?ToDo=viewCheckoutSettings';
				$message = GetLang('CheckoutSettingsSavedSuccessfully');
				// If we haven't enabled anything new, we've just saved settings. So mark as complete
				if(!in_array('paymentMethods', GetConfig('GettingStartedCompleted')) && empty($justEnabled)) {
					GetClass('ISC_ADMIN_ENGINE')->MarkGettingStartedComplete('paymentMethods');
					$redirectUrl = 'index.php';
					$message = GetLang('CheckoutSettingsSavedNoConfigure');
				}

				FlashMessage($message, MSG_SUCCESS, $redirectUrl);
			}
			else {
				FlashMessage(GetLang('CheckoutSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewCheckoutSettings');

			}
		} else {
			FlashMessage(GetLang('CheckoutSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewCheckoutSettings');
		}
	}

	public function GetCheckoutProvidersAsOptions()
	{
		// Get a list of all available checkout providers as <option> tags
		$checkouts = GetAvailableModules('checkout');
		$output = "";

		foreach ($checkouts as $checkout) {
			$sel = '';
			if($checkout['enabled']) {
				$sel = 'selected="selected"';
			}
			$output .= sprintf("<option %s value='%s'>%s</option>", $sel, $checkout['id'], $checkout['name']);
		}

		return $output;
	}
}