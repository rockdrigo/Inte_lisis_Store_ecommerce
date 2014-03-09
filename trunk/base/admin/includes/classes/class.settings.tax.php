<?php
class ISC_ADMIN_SETTINGS_TAX extends ISC_ADMIN_BASE
{
	public function __construct()
	{
		parent::__construct();
		$this->log = $GLOBALS['ISC_CLASS_LOG'];
		$this->engine->loadLangFile('settings.tax');
	}

	/**
	 * Route the incoming action to the specified action handler.
	 *
	 * @param string $todo The incoming action.
	 */
	public function handleToDo($todo)
	{
		// Tax settings share the same permissions as settings - they don't have their own.
		if (!$this->auth->hasPermission(AUTH_Manage_Settings)) {
			$this->engine->doHomePage(getLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$todo = $todo . 'Action';

		// Process action routing
		if (!method_exists($this, $todo) && !method_exists($this, '__call')) {
			header('Location: index.php');
			exit;
		}

		$this->engine
			->addBreadcrumb('Settings', 'index.php?ToDo=viewSettings')
			->addBreadcrumb('Tax Settings', 'index.php?ToDo=viewTaxSettings')
		;

		$pendingChanges = getConfig('taxPendingChanges');
		if(!empty($pendingChanges)) {
			$this->template->assign('pendingChangesToApply', true);
		}

		$this->$todo();
	}

	/**
	 * Build and display the tax settings / tax zones page.
	 *
	 * @param string $activeTab Name of the tab to activate on page load.
	 */
	public function viewTaxSettingsAction($activeTab = '')
	{
		if(!empty($_GET['rebuilt'])) {
			flashMessage(getLang('TaxPricingRebuilt'), MSG_SUCCESS);
		}

		$this->template->assign('activeTab', $activeTab);
		$this->template->assign('flashMessages', getFlashMessageBoxes());

		// Make settings available to the template system. Do this rather than
		// making all of the configuration available as there's certain vars
		// which a customer should NEVER see.
		$shownSettings = array(
			'taxLabel',
			'taxEnteredWithPrices',
			'taxCalculationBasedOn',
			'taxDefaultCountry',
			'taxDefaultState',
			'taxDefaultZipCode',
			'taxDefaultTaxDisplayCatalog',
			'taxDefaultTaxDisplayProducts',
			'taxDefaultTaxDisplayCart',
			'taxDefaultTaxDisplayOrders',
			'taxChargesOnOrdersBreakdown',
			'taxChargesInCartBreakdown',
			'taxShippingTaxClass',
			'taxGiftWrappingTaxClass',
		);
		$settings = array();
		foreach($shownSettings as $setting) {
			$settings[$setting] = getConfig($setting);
		}
		$this->template->assign('settings', $settings);

		// Get the list of tax zones
		$this->template->assign('taxZoneGrid', $this->getTaxZoneGrid());

		// And the list of tax classes
		$taxClasses = array(
			0 => getLang('DefaultTaxClass')
		) + getClass('ISC_TAX')->getTaxClasses();

		$this->template->assign('taxClasses', $taxClasses);

		$countryList = array(0 => getLang('ChooseACountry'))
			+ getCountryListAsIdValuePairs()
		;

		$this->template->assign('countryList', $countryList);
		$defaultState = getConfig('taxDefaultState');

		$stateList = array(0 => getLang('ChooseAState'))
			+ (array)getStateListAsIdValuePairs(getConfig('taxDefaultCountry'))
		;

		$this->template->assign('stateList', $stateList);

		// Get the getting started box if we need to
		if(!empty($_GET['wizard']) &&
			!in_array('taxSettings', getConfig('GettingStartedCompleted')) &&
			!getConfig('DisableGettingStarted')) {
				$this->template->assign('GettingStartedTitle', getLang('WizardTaxSettings'));
				$this->template->assign('GettingStartedContent', getLang('WizardTaxSettingsDesc'));
				$taxWizard = $this->template->render('Snippets/GettingStartedModal.html');
				$this->template->assign('GettingStartedStep', $taxWizard);
		}

		$this->engine->printHeader();
		$this->template->display('settings.tax.manage.tpl');
		$this->engine->printFooter();
	}

	/**
	 * Generate a HTML grid of tax rates for the specified tax zone.
	 *
	 * @param string $zoneId The ID of the tax zone to generate the rates grid for.
	 * @return string The HTML grid containing tax rates.
	 */
	public function getTaxRateGrid($zoneId)
	{
		$taxRates = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]tax_rates
			WHERE tax_zone_id='".(int)$zoneId."'
			ORDER BY priority ASC, name ASC
		";
		$result = $this->db->query($query);
		while($taxRate = $this->db->fetch($result)) {
			$taxRate['rates'] = array();

			// Divide by one to remove the database applied formatting from the decimal column
			$taxRate['default_rate'] /= 1;

			$taxRates[$taxRate['id']] = $taxRate;
		}

		if(!empty($taxRates)) {
			$taxRateIds = array_keys($taxRates);
			$query = "
				SELECT r.tax_rate_id, r.rate, c.name
				FROM [|PREFIX|]tax_rate_class_rates r
				JOIN [|PREFIX|]tax_classes c ON (c.id=r.tax_class_id)
				WHERE r.tax_rate_id IN (".implode(',', $taxRateIds).")
				ORDER BY name ASC
			";
			$result = $this->db->query($query);
			while($taxClassRate = $this->db->fetch($result)) {
				// Divide by one to remove the database applied formatting from the decimal column
				$taxRates[$taxClassRate['tax_rate_id']]['rates'][$taxClassRate['name']] =
					$taxClassRate['rate'] / 1;
			}
		}

		if(empty($taxRates)) {
			return false;
		}

		$this->template->assign('taxRates', $taxRates);
		return $this->template->render('settings.tax.rates.grid.tpl');
	}

	/**
	 * Generate a HTML grid of tax zones.
	 *
	 * @return string The HTML grid containing the list of tax zones.
	 */
	public function getTaxZoneGrid()
	{
		$taxZones = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]tax_zones
			ORDER BY `default` DESC, name ASC
		";
		$result = $this->db->query($query);
		while($taxZone = $this->db->fetch($result)) {
			$taxZone['customerGroups'] = array();
			$taxZones[$taxZone['id']] = $taxZone;
		}

		if(!empty($taxZones)) {
			$taxZoneIds = array_keys($taxZones);
			$query = "
				SELECT z.tax_zone_id, g.groupname, z.customer_group_id
				FROM [|PREFIX|]tax_zone_customer_groups z
				JOIN [|PREFIX|]customer_groups g ON (g.customergroupid=z.customer_group_id)
				WHERE z.tax_zone_id IN (".implode(',', $taxZoneIds).")
			";
			$result = $this->db->query($query);
			while($customerGroup = $this->db->fetch($result)) {
				if($customerGroup['customer_group_id'] == 0) {
					continue;
				}
				$taxZones[$customerGroup['tax_zone_id']]['customerGroups'][] =
					$customerGroup['groupname'];
			}
		}
		$this->template->assign('taxZones', $taxZones);
		return $this->template->render('settings.tax.zones.grid.tpl');
	}

	/**
	 * Save the updated tax settings.
	 */
	public function saveTaxSettingsAction()
	{
		$hasErrors = false;
		$stringSettings = array(
			'taxLabel'
		);

		foreach($stringSettings as $key => $setting) {
			if(empty($_POST[$setting])) {
				flashMessage(getLang('InvalidTaxSetting'.ucfirst($setting)), MSG_ERROR);
				$hasErrors = true;
			}
		}

		$enumSettings = array(
			'taxEnteredWithPrices' => array(
				TAX_PRICES_ENTERED_INCLUSIVE,
				TAX_PRICES_ENTERED_EXCLUSIVE
			),
			'taxCalculationBasedOn' => array(
				TAX_BASED_ON_BILLING_ADDRESS,
				TAX_BASED_ON_SHIPPING_ADDRESS,
				TAX_BASED_ON_STORE_ADDRESS
			),
			'taxDefaultTaxDisplayCatalog' => array(
				TAX_PRICES_DISPLAY_INCLUSIVE,
				TAX_PRICES_DISPLAY_EXCLUSIVE,
				TAX_PRICES_DISPLAY_BOTH
			),
			'taxDefaultTaxDisplayProducts' => array(
				TAX_PRICES_DISPLAY_INCLUSIVE,
				TAX_PRICES_DISPLAY_EXCLUSIVE,
				TAX_PRICES_DISPLAY_BOTH
			),
			'taxDefaultTaxDisplayCart' => array(
				TAX_PRICES_DISPLAY_INCLUSIVE,
				TAX_PRICES_DISPLAY_EXCLUSIVE,
			),
			'taxDefaultTaxDisplayOrders' => array(
				TAX_PRICES_DISPLAY_INCLUSIVE,
				TAX_PRICES_DISPLAY_EXCLUSIVE,
			),
			'taxChargesOnOrdersBreakdown' => array(
				TAX_BREAKDOWN_SUMMARY,
				TAX_BREAKDOWN_RATE,
			),
			'taxChargesInCartBreakdown' => array(
				TAX_BREAKDOWN_SUMMARY,
				TAX_BREAKDOWN_RATE,
			),
		);

		foreach($enumSettings as $setting => $options) {
			if(!isset($_POST[$setting]) || !in_array($_POST[$setting], $options)) {
				flashMessage(getLang('InvalidTaxSetting'.ucfirst($setting)), MSG_ERROR);
				$hasErrors = true;
			}
		}

		if(empty($_POST['taxDefaultCountry']) ||
			!getCountryById($_POST['taxDefaultCountry'])) {
				flashMessage(getLang('InvalidTaxSettingTaxDefaultCountry'), MSG_ERROR);
				$hasErrors = true;
		}

		if(!empty($_POST['taxDefaultState']) &&
			!getStateById($_POST['taxDefaultState'])) {
				flashMessage(getLang('InvalidTaxSettingTaxDefaultState'), MSG_ERROR);
				$hasErrors = true;
		}

		if($hasErrors) {
			$this->handleToDo('viewTaxSettings');
			return;
		}

		$allSettings = array_merge($stringSettings, array_keys($enumSettings));

		$allSettings[] = 'taxDefaultCountry';
		$allSettings[] = 'taxDefaultState';
		$allSettings[] = 'taxDefaultZipCode';
		$allSettings[] = 'taxShippingTaxClass';
		$allSettings[] = 'taxGiftWrappingTaxClass';

		foreach($allSettings as $setting) {
			if(isset($_POST[$setting])) {
				$GLOBALS['ISC_NEW_CFG'][$setting] = $_POST[$setting];
			}
		}

		// Determine if the product tax pricing needs to be rebuilt based
		// on which tax settings have changed.
		$rebuildSettings = array(
			'taxEnteredWithPrices',
			'taxDefaultCountry',
			'taxDefaultState',
			'taxDefaultZipCode',
		);
		$requiresPriceRebuild = false;
		foreach($rebuildSettings as $setting) {
			if(isset($GLOBALS['ISC_NEW_CFG']) &&
				getConfig($setting) != $GLOBALS['ISC_NEW_CFG'][$setting]) {
					$requiresPriceRebuild = true;
					break;
			}
		}

		// Mark it as requiring a price rebuild
		if($requiresPriceRebuild) {
			$this->markProductPricingRequiresRebuild();
		}

		$messages = array();
		if(!getClass('ISC_ADMIN_SETTINGS')->commitSettings($messages)) {
			flashMessage(getLang('TaxSettingsNotSaved'), MSG_ERROR);
			$this->viewTaxSettingsAction();
			return;
		}

		// Rebuild the default customer groups/tax cache
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateDefaultTaxZones();

		// We've just configured tax - mark it as so.
		if(!in_array('taxSettings', getConfig('GettingStartedCompleted'))) {
			getClass('ISC_ADMIN_ENGINE')->markGettingStartedComplete('taxSettings');
		}

		$this->log->logAdminAction();
		flashMessage(getLang('TaxSettingsSaved'), MSG_SUCCESS,
			'index.php?ToDo=viewTaxSettings'
		);
	}

	/**
	 * Save an updated list of tax classes.
	 */
	public function saveTaxClassesAction()
	{
		$errors = array();

		$this->db->startTransaction();

		$updatedTaxClasses = array();
		if(!empty($_POST['taxClass']['existing'])) {
			unset($_POST['taxClass']['existing'][0]);
			$updatedTaxClassIds = array_keys($_POST['taxClass']['existing']);
		}

		// Determine which tax classes were deleted
		$existingTaxClassIds = array_keys(getClass('ISC_TAX')->getTaxClasses());
		$deletedTaxClasses = array_diff($existingTaxClassIds, $updatedTaxClassIds);
		if(!$this->deleteTaxClassesById($deletedTaxClasses)) {
			$errors[] = getLang('ErrorDeletingTaxClass');
		}

		// Attempt to save new tax classes
		if(!empty($_POST['taxClass']['new'])) {
			foreach($_POST['taxClass']['new'] as $name) {
				$taxClass = array(
					'name' => $name
				);

				if(!$this->validateTaxClass($taxClass, $classErrors)) {
					$errors += $classErrors;
					continue;
				}

				if(!$this->commitTaxClass($taxClass)) {
					$errors[] = getLang('ErrorSavingTaxClass', array(
						'name' => $name
					));
				}
			}
		}

		// Attempt to save those tax classes which were updated
		if(!empty($updatedTaxClassIds)) {
			foreach($_POST['taxClass']['existing'] as $id => $name) {
				$taxClass = array(
					'id' => $id,
					'name' => $name
				);

				if(!$this->validateTaxClass($taxClass, $classErrors)) {
					$errors += $classErrors;
					continue;
				}

				if(!$this->commitTaxClass($taxClass, $id)) {
					$errors[] = getLang('ErrorSavingTaxClass', array(
						'name' => $name
					));
				}
			}
		}

		if(!empty($errors)) {
			foreach($errors as $message) {
				flashMessage($message, MSG_ERROR);
			}
			$this->db->rollbackTransaction();
			$this->viewTaxSettingsAction('taxClassesTab');
			return;
		}

		$this->db->commitTransaction();
		$this->log->logAdminAction();
		flashMessage(getLang('TaxClassesSaved'), MSG_SUCCESS,
			'index.php?ToDo=viewTaxSettings#taxClassesTab'
		);
	}

	/**
	 * Display the page that allows the creation of new tax zones.
	 */
	public function addTaxZoneAction()
	{
		$this->template->assign('flashMessages', getFlashMessageBoxes());

		$this->engine
			->addBreadcrumb(getLang('TaxZones'), 'index.php?ToDo=viewTaxSettings#taxZonesTab')
			->addBreadcrumb(getLang('AddATaxZoneBreadcrumb'))
		;

		$defaultValues = array(
			'type' => 'country',
			'enabled' => 1,
			'applies_to' => 'all',
			'countries' => array(),
		);

		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$defaultValues = $_POST;
			if(!empty($_POST['type']) && $_POST['type'] == 'state' && isset($_POST['state_countries'])) {
				$defaultValues['countries'] = $_POST['state_countries'];
			}
		}

		$this->template->assign('taxZone', $defaultValues);

		$this->template->assign('countryList', getCountryListAsIdValuePairs());
		$this->template->assign('countryStateList', $this->getCountryStateList($defaultValues['countries']));

		$this->template->assign('customerGroupList', $this->getCustomerGroups());


		$this->engine->printHeader();
		$this->template->display('settings.tax.zone.form.tpl');
		$this->engine->printFooter();
	}

	/**
	 * Handle the saving of a new tax zone.
	 */
	public function saveNewTaxZoneAction()
	{
		$taxZoneData = $_POST;
		if(!empty($taxZoneData['zip_codes'])) {
			$taxZoneData['zip_codes'] = explode("\n", $taxZoneData['zip_codes']);
		}

		if(!$this->validateTaxZone($taxZoneData, $errors)) {
			foreach($errors as $error) {
				flashMessage($error, MSG_ERROR);
			}
			$this->handleToDo('addTaxZone');
			return;
		}

		$taxZoneId = $this->commitTaxZone($taxZoneData);
		if(!$taxZoneId) {
			flashMessage(getLang('ProblemSavingTaxZone'), MSG_ERROR);
			$this->handleToDo('addTaxZone');
			return;
		}

		// Log this action
		$this->log->logAdminAction($taxZoneId, $taxZoneData['name']);

		// Redirect to step 2
		$url = 'index.php?ToDo=editTaxZone&id='.$taxZoneId.'&created=1#taxRatesTab';
		flashMessage(getLang('TaxZoneCreated'), MSG_SUCCESS, $url);
	}

	/**
	 * Display the page that allows tax zones to be edited.
	 */
	public function editTaxZoneAction()
	{
		if(!empty($_REQUEST['created'])) {
			$this->template->assign('created', true);
		}

		$this->template->assign('flashMessages', getFlashMessageBoxes());

		$taxZone = $this->getTaxZoneById($_REQUEST['id'], true);
		if(empty($taxZone)) {
			flashMessage(getLang('InvalidTaxZone'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		$this->engine
			->addBreadcrumb(getLang('TaxZones'), 'index.php?ToDo=viewTaxSettings#taxZonesTab')
			->addBreadcrumb(isc_html_escape($taxZone['name']))
		;

		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			if(!empty($_POST['zip_codes'])) {
				$_POST['zip_codes'] = explode("\n", $_POST['zip_codes']);
			}

			if($taxZone['default']) {
				$_POST['default'] = 1;
			}
			$this->template->assign('taxZone', $_POST);
		}
		else {
			$this->template->assign('taxZone', $taxZone);
		}

		$this->template->assign('countryList', getCountryListAsIdValuePairs());
		$this->template->assign('countryStateList', $this->getCountryStateList($taxZone['countries']));

		$this->template->assign('customerGroupList', $this->getCustomerGroups());

		// Get the list of tax rates
		$this->template->assign('taxRateGrid', $this->getTaxRateGrid($taxZone['id']));

		$this->engine->printHeader();
		$this->template->display('settings.tax.zone.form.tpl');
		$this->engine->printFooter();
	}

	/**
	 * Handle the saving of an updated tax zone.
	 */
	public function saveUpdatedTaxZoneAction()
	{
		$updatedTaxZone = $_POST;
		if(!empty($updatedTaxZone['zip_codes'])) {
			$updatedTaxZone['zip_codes'] = explode("\n", $updatedTaxZone['zip_codes']);
		}

		$taxZone = $this->getTaxZoneById($updatedTaxZone['id']);
		if(empty($taxZone)) {
			flashMessage(getLang('InvalidTaxZone'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		if(!$this->validateTaxZone($updatedTaxZone, $errors)) {
			foreach($errors as $error) {
				flashMessage($error, MSG_ERROR);
			}
			$this->handleToDo('editTaxZone');
			return;
		}

		$taxZoneId = $this->commitTaxZone($updatedTaxZone, $_POST['id']);
		if(!$taxZoneId) {
			flashMessage(getLang('ProblemSavingTaxZone'), MSG_ERROR);
			$this->handleToDo('editTaxZone');
			return;
		}

		// Log this action
		$this->log->logAdminAction($taxZoneId, $_POST['name']);

		flashMessage(getLang('TaxZoneUpdated'), MSG_SUCCESS,
			'index.php?ToDo=viewTaxSettings#taxZonesTab'
		);
	}

	/**
	 * Handle the deletion of one or more tax zones from the tax grid list.
	 */
	public function deleteTaxZonesAction()
	{
		if(empty($_POST['taxZone'])) {
			flashMessage(getLang('SelectTaxZonesToDelete'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings#taxZonesTab'
			);
		}

		if(!$this->deleteTaxZonesById($_POST['taxZone'])) {
			flashMessage(getLang('ProblemDeletingTaxZones'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings#taxZonesTab'
			);
		}

		// Log this action
		$this->log->logAdminAction(count($_POST['taxZone']));

		flashMessage(getLang('TaxRatesDeleted'), MSG_SUCCESS,
			'index.php?ToDo=viewTaxSettings#taxZonesTab'
		);
	}

	/**
	 * Display the page that allows a new tax rate to be created in a zone.
	 */
	public function addTaxRateAction()
	{
		$this->template->assign('flashMessages', getFlashMessageBoxes());

		$taxZone = $this->getTaxZoneById($_REQUEST['tax_zone_id']);
		if(empty($taxZone)) {
			flashMessage(getLang('InvalidTaxZone'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		$defaultValues = array(
			'enabled' => 1,
			'priority' => 0
		);

		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->template->assign('taxRate', $_POST);
		}
		else {
			$this->template->assign('taxRate', $defaultValues);
		}

		$this->template->assign('taxZone', $taxZone);
		$this->template->assign('taxClasses', getClass('ISC_TAX')->getTaxClasses());

		$this->engine
			->addBreadcrumb(getLang('TaxZones'), 'index.php?ToDo=viewTaxSettings#taxZonesTab')
			->addBreadcrumb(isc_html_escape($taxZone['name']), 'index.php?ToDo=editTaxZone&id='.$taxZone['id'])
			->addBreadcrumb(getLang('TaxRates'), 'index.php?ToDo=editTaxZone&id='.$taxZone['id'].'#taxRatesTab')
			->addBreadcrumb(getLang('AddTaxRateBreadcrumb'))
		;

		$this->engine->printHeader();
		$this->template->display('settings.tax.rate.form.tpl');
		$this->engine->printFooter();
	}

	/**
	 * Handle the saving of a new tax rate for a tax zone.
	 */
	public function saveNewTaxRateAction()
	{
		if(!$this->validateTaxRate($_POST, $errors)) {
			foreach($errors as $error) {
				flashMessage($error, MSG_ERROR);
			}
			$this->handleToDo('addTaxRate');
			return;
		}

		$taxZone = $this->getTaxZoneById($_POST['tax_zone_id']);

		$taxRateId = $this->commitTaxRate($_POST);

		if(!$taxRateId) {
			flashMessage(getLang('ProblemSavingTaxRate'), MSG_ERROR);
			$this->handleToDo('addTaxRate');
			return;
		}

		// Log this action
		$this->log->logAdminAction($taxZone['id'], $taxZone['name'], $taxRateId, $_POST['name']);

		$url = 'index.php?ToDo=editTaxZone&id='.$taxZone['id'].'#taxRatesTab';
		flashMessage(getLang('TaxRateCreated'), MSG_SUCCESS, $url);
	}

	/**
	 * Display the page that allows a tax rate to be updated.
	 */
	public function editTaxRateAction()
	{
		$this->template->assign('flashMessages', getFlashMessageBoxes());

		$taxRate = $this->getTaxRateById($_REQUEST['id'], true);
		if(empty($taxRate)) {
			flashMessage(getLang('InvalidTaxRate'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		$this->template->assign('taxClasses', getClass('ISC_TAX')->getTaxClasses());

		$taxZone = $this->getTaxZoneById($taxRate['tax_zone_id']);
		$this->template->assign('taxZone', $taxZone);

		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->template->assign('taxRate', $_POST);
		}
		else {
			$this->template->assign('taxRate', $taxRate);
		}

		$this->engine
			->addBreadcrumb(getLang('TaxZones'), 'index.php?ToDo=viewTaxSettings#taxZonesTab')
			->addBreadcrumb(isc_html_escape($taxZone['name']), 'index.php?ToDo=editTaxZone&id='.$taxZone['id'])
			->addBreadcrumb(getLang('TaxRates'), 'index.php?ToDo=editTaxZone&id='.$taxZone['id'].'#taxRatesTab')
			->addBreadcrumb(isc_html_escape($taxRate['name']))
		;

		$this->engine->printHeader();
		$this->template->display('settings.tax.rate.form.tpl');
		$this->engine->printFooter();
	}

	/**
	 * Handle the saving of an updated tax rate.
	 */
	public function saveUpdatedTaxRateAction()
	{
		$taxRate = $this->getTaxRateById($_REQUEST['id']);
		if(empty($taxRate)) {
			flashMessage(getLang('InvalidTaxRate'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		if(!$this->validateTaxRate($_POST, $errors)) {
			foreach($errors as $error) {
				flashMessage($error, MSG_ERROR);
			}
			$this->handleToDo('editTaxRate');
			return;
		}

		$taxZone = $this->getTaxZoneById($taxRate['tax_zone_id']);
		$taxRateId = $this->commitTaxRate($_POST, $taxRate['id']);
		if(!$taxRateId) {
			flashMessage(getLang('ProblemSavingTaxRate'), MSG_ERROR);
			$this->handleToDo('editTaxRate');
			return;
		}

		// Log this action
		$this->log->logAdminAction($taxZone['id'], $taxZone['name'], $taxRateId, $_POST['name']);

		$url = 'index.php?ToDo=editTaxZone&id='.$taxZone['id'].'#taxRatesTab';
		flashMessage(getLang('TaxRateUpdated'), MSG_SUCCESS, $url);
	}

	public function deleteTaxRatesAction()
	{
		if(empty($_POST['taxRate'])) {
			flashMessage(getLang('SelectTaxRatesToDelete'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings#taxZonesTab'
			);
		}

		// Grab the first tax rate in the list so we can redirect back
		// to the same tax zone
		$taxRate = $this->getTaxRateById($_POST['taxRate'][0]);
		if(empty($taxRate)) {
			flashMessage(getLang('SelectTaxRatesToDelete'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings#taxZonesTab'
			);
		}

		if(!$this->deleteTaxRatesById($_POST['taxRate'])) {
			flashMessage(getLang('ProblemDeletingTaxRates'), MSG_ERROR,
				'index.php?ToDo=editTaxZone&id='.$taxRate['id'].'#taxRatesTab'
			);
		}

		// Log this action
		$this->log->logAdminAction(count($_POST['taxRate']));

		flashMessage(getLang('TaxRatesDeleted'), MSG_SUCCESS,
			'index.php?ToDo=editTaxZone&id='.$taxRate['tax_zone_id'].'#taxRatesTab'
		);
	}

	/**
	 * Toggle the status of a tax zone between active and inactive.
	 */
	public function toggleTaxZoneStatusAction()
	{
		if(empty($_POST['id']) || !isset($_POST['status'])) {
			$response = array(
				'status' => MSG_ERROR,
				'message' => getLang('InvalidTaxZone')
			);
			echo isc_json_encode($response);
			exit;
		}

		$taxZone = $this->getTaxZoneById($_POST['id']);
		if(empty($taxZone)) {
			$response = array(
				'status' => MSG_ERROR,
				'message' => getLang('InvalidTaxZone')
			);
			echo isc_json_encode($response);
			exit;
		}

		// Make sure the status can only be toggled between two states
		if($_POST['status'] == 1) {
			$status = 1;
		}
		else {
			$status = 0;
		}

		$updatedTaxZone = array(
			'enabled' => $status
		);

		// Attempt to save the updated tax zone
		if(!$this->commitTaxZone($updatedTaxZone, $_POST['id'])) {
			$response = array(
				'status' => MSG_ERROR,
				'message' => getLang('ProblemSavingTaxZone')
			);
			echo isc_json_encode($response);
			exit;
		}

		// Log this action
		$this->log->logAdminAction($taxZone['id'], $taxZone['name']);

		$response = array(
			'status' => MSG_SUCCESS,
		);
		echo isc_json_encode($response);
		exit;
	}

	/**
	 * Toggle the status of a tax rate between active and inactive.
	 */
	public function toggleTaxRateStatusAction()
	{
		if(empty($_POST['id']) || !isset($_POST['status'])) {
			$response = array(
				'status' => 'a'.MSG_ERROR,
				'message' => getLang('InvalidTaxRate')
			);
			echo isc_json_encode($response);
			exit;
		}

		$taxRate = $this->getTaxRateById($_POST['id']);
		if(empty($taxRate)) {
			$response = array(
				'status' => 'b'.MSG_ERROR,
				'message' => getLang('InvalidTaxRate')
			);
			echo isc_json_encode($response);
			exit;
		}

		// Make sure the status can only be toggled between two states
		if($_POST['status'] == 1) {
			$status = 1;
			$message = getLang('TaxRateEnabled');
		}
		else {
			$status = 0;
			$message = getLang('TaxRateDisabled');
		}

		$updatedTaxRate = array(
			'enabled' => $status
		);

		// Attempt to save the updated tax rate
		if(!$this->commitTaxRate($updatedTaxRate, $_POST['id'])) {
			$response = array(
				'status' => MSG_ERROR,
				'message' => getLang('ProblemSavingTaxRate')
			);
			echo isc_json_encode($response);
			exit;
		}

		// Log this action
		$this->log->logAdminAction($taxRate['tax_zone_id'], $taxRate['id'], $taxRate['name']);

		$response = array(
			'status' => MSG_SUCCESS,
		);
		echo isc_json_encode($response);
		exit;
	}

	/**
	 * Handle the ability to copy a tax zone.
	 */
	public function copyTaxZoneAction()
	{
		if(empty($_POST['id'])) {
			flashMessage(getLang('InvalidTaxZone'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		$taxZone = $this->getTaxZoneById($_POST['id'], true);
		if(empty($taxZone)) {
			flashMessage(getLang('InvalidTaxZone'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		// Update the name
		$newName = getLang('TaxZoneCopyName', array(
			'name' => $taxZone['name']
		));

		// Save the tax zone with a new ID
		$taxZoneId = $this->copyTaxZone($taxZone['id'], $newName);
		if(!$taxZoneId) {
			flashMessage(getLang('ProblemSavingTaxZone'), MSG_ERROR);
			$this->handleToDo('editTaxZone');
			return;
		}

		// Log this action
		$this->log->logAdminAction($taxZoneId, $taxZone['name'], $newName);

		flashMessage(getLang('TaxZoneCopied'), MSG_SUCCESS,
			'index.php?ToDo=editTaxZone&id='.$taxZoneId
		);
	}

	/**
	 * Handle the ability to copy a tax rate.
	 */
	public function copyTaxRateAction()
	{
		if(empty($_POST['id'])) {
			flashMessage(getLang('InvalidTaxZone'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		$taxRate = $this->getTaxRateById($_POST['id'], true);
		if(empty($taxRate)) {
			flashMessage(getLang('InvalidTaxRate'), MSG_ERROR,
				'index.php?ToDo=viewTaxSettings'
			);
		}

		// Update the name
		$newName = getLang('TaxRateCopyName', array(
			'name' => $taxRate['name']
		));

		// Save the tax rate with a new ID
		$taxRateId = $this->copyTaxRate($taxRate['id'], $newName);
		if(!$taxRateId) {
			flashMessage(getLang('ProblemSavingTaxRate'), MSG_ERROR);
			$this->handleToDo('editTaxRate');
			return;
		}

		// Log this action
		$this->log->logAdminAction($taxRateId, $taxRate['name'], $newName);

		flashMessage(getLang('TaxRateCopied'), MSG_SUCCESS,
			'index.php?ToDo=editTaxRate&id='.$taxRateId
		);
	}

	/**
	 * Copy the specified tax zone to a new tax zone with the given name.
	 *
	 * @param int $id The ID of the tax zone to copy.
	 * @param string $newName The name to give the new tax zone.
	 * @return false|id False on failure. ID of new tax zone if successful.
	 */
	public function copyTaxZone($id, $newName)
	{
		$taxZone = $this->getTaxZoneById($id, true);

		$taxZone['name'] = $newName;
		$taxZone['default'] = 0;

		$this->db->startTransaction();

		// Save the tax zone with a new ID
		$taxZoneId = $this->commitTaxZone($taxZone);
		if(!$taxZoneId) {
			return false;
		}

		// Now copy the tax rates for the zone
		$query = "
			SELECT id
			FROM [|PREFIX|]tax_rates
			WHERE tax_zone_id='".(int)$id."'
		";
		$result = $this->db->query($query);
		while($rateId = $this->db->fetch($result)) {
			$taxRate = $this->getTaxRateById($rateId['id'], true);
			$taxRate['tax_zone_id'] = $taxZoneId;
			if(!$this->commitTaxRate($taxRate)) {
				$this->db->rollbackTransaction();
				return false;
			}
		}

		$this->db->commitTransaction();
		return $taxZoneId;
	}

	/**
	 * Validate that a tax zone is valid.
	 *
	 * @param array $data Array of information about a tax zone.
	 * @param array &$messages Array of error messages if there are any, by reference.
	 * @return boolean True if valid, false if not.
	 */
	public function validateTaxZone($data, &$messages)
	{
		$zoneIdQuery = '';
		$locationZoneIdQuery = '';
		if(!empty($data['id'])) {
			$zoneIdQuery = " AND id!='".(int)$data['id']."'";
			$locationZoneIdQuery = " AND l.tax_zone_id!='".(int)$data['id']."'";
		}

		$requiredFields = array(
			'name' => 'TaxZoneMissingName',
		);

		if(!is_array($messages)) {
			$messages = array();
		}

		foreach($requiredFields as $field => $message) {
			if(!isset($data[$field]) || trim($data[$field] === '')) {
				$messages[] = getLang($message);
			}
		}

		// Check there is no tax zone with this name already
		$query = "
			SELECT id
			FROM [|PREFIX|]tax_zones
			WHERE name='".$this->db->quote($data['name'])."' ".$zoneIdQuery
		;

		if($this->db->fetchOne($query)) {
			$messages[] = getLang('TaxZoneDuplicateName');
		}

		// This is an existing tax zone being edited.
		if(!empty($data['id'])) {
			$existingZone = $this->getTaxZoneById($data['id']);
			if(empty($existingZone)) {
				return false;
			}

			if(!$existingZone['default'] && empty($data['type'])) {
				$messages[] = getLang('TaxZoneSelectType');
			}
		}

		if(!empty($data['applies_to']) && $data['applies_to'] == 'groups' && !empty($data['groups'])) {
			$groupLimit = array_map('intval', $data['groups']);
		}
		else {
			$groupLimit = array(0);
		}

		$groupRestriction = "
			JOIN [|PREFIX|]tax_zone_customer_groups g
				ON (g.customer_group_id IN (".implode(', ', $groupLimit).")";
		if(!empty($data['id'])) {
			$groupRestriction .= " AND g.tax_zone_id != ".(int)$data['id'];
		}
		$groupRestriction .= ')';

		// Check that a country based zone does not have any overlapping countries
		if(!empty($data['type']) && $data['type'] == 'country') {
			// Make sure at least one country was selected
			if(empty($data['countries'])) {
				$data['countries'] = array();
				$messages[] = getLang('TaxZoneSelectOneMoreCountries');
			}

			$countryIds = implode(',', array_map('intval', $data['countries']));
			$query = "
				SELECT DISTINCT value
				FROM [|PREFIX|]tax_zone_locations l
				".$groupRestriction."
				WHERE type='country' AND value_id IN (".$countryIds.")".$locationZoneIdQuery
			;
			$result = $this->db->query($query);
			$countryList = array();
			while($country = $this->db->fetch($result)) {
				$countryList[] = $country['value'];
			}

			if(!empty($countryList)) {
				$messages[] = getLang('TaxZoneDuplicateCountries', array(
					'countries' => implode(', ', $countryList)
				));
			}
		}
		// Check that there are no overlapping states in a state based zone
		else if(!empty($data['type']) && $data['type'] == 'state') {
			// Make sure at least one state was selected
			if(empty($data['states'])) {
				$data['states'] = array();
				$messages[] = getLang('TaxZoneSelectOneMoreStates');
			}

			$stateList = array();
			foreach($data['states'] as $state) {
				$state = explode('-', $state);
				$query = "
					SELECT l.id, statename, countryname
					FROM [|PREFIX|]tax_zone_locations l
					LEFT JOIN [|PREFIX|]countries ON (countryid=l.country_id)
					LEFT JOIN [|PREFIX|]country_states ON (stateid=l.value_id)
					".$groupRestriction."
					WHERE
						l.type='state' AND
						l.value_id='".(int)$state[1]."' AND
						l.country_id='".(int)$state[0]."' ".$locationZoneIdQuery
				;
				$result = $this->db->query($query);
				$dbState = $this->db->fetch($result);

				if(!empty($dbState)) {
					if(!$dbState['statename'] && $state[1] == 0) {
					$stateList[] = 'All states in '.$dbState['countryname'];
					}
					else {
					$stateList[] = $dbState['statename'].' ('.$dbState['countryname'].')';
					}
				}
			}

			if(!empty($stateList)) {
				$messages[] = getLang('TaxZoneDuplicateStates', array(
					'states' => implode(', ', $stateList)
				));
			}
		}
		// Check that there are no overlapping states in a state based zone
		else if(!empty($data['type']) && $data['type'] == 'zip') {
			if(empty($data['country'])) {
				$messages[] = getLang('TaxZoneSelectCountry');
			}

			if(empty($data['zip_codes'])) {
				$messages[] = getLang('TaxZoneEnterOneMoreZipCodes');
			}

			if(!empty($data['country']) && !empty($data['zip_codes'])) {
				$countryId = $data['country'];
				foreach($data['zip_codes'] as $zipCode) {
					$zipCode = trim($zipCode);
					$query = "
						SELECT l.id
						FROM [|PREFIX|]tax_zone_locations l
						".$groupRestriction."
						WHERE
							value='".$this->db->quote($zipCode)."' AND
							country_id='".(int)$countryId."' ".$locationZoneIdQuery
					;

					if($this->db->fetchOne($query)) {
						$zipCodeList[] = $zipCode;
					}
				}
			}

			if(!empty($zipCodeList)) {
				$messages[] = getLang('TaxZoneDuplicateZipCodes', array(
					'zipCodes' => $zipCodeList
				));
			}
		}

		// Make sure that the supplied customer groups are valid
		if(!empty($data['applies_to']) && $data['applies_to'] == 'groups') {
			if(empty($data['groups']) || !is_array($data['groups'])) {
				$messages[] = getLang('TaxZoneSelectOneMoreGroups');
			}
			else {
				$existingGroups = array();
				$query = "
					SELECT customergroupid
					FROM [|PREFIX|]customer_groups
					WHERE customergroupid IN (".implode(',', array_map('intval', $data['groups'])).")
				";
				$result = $this->db->query($query);
				while($existingGroup = $this->db->fetch($result)) {
					$existingGroups[] = $existingGroup['customergroupid'];
				}

				$invalidGroups = array_diff($data['groups'], $existingGroups);
				if(!empty($invalidGroups)) {
					$messages[] = getLang('TaxZoneInvalidGroupsSelected');
				}
			}
		}

		if(!empty($messages)) {
			return false;
		}

		// Valid
		return true;
	}

	public function commitTaxZone($data, $taxZoneId = 0)
	{
		$validFields = array(
			'name',
			'type',
			'enabled',
		);
		$databaseData = array();
		foreach($validFields as $field) {
			if(isset($data[$field])) {
				$databaseData[$field] = $data[$field];
			}
		}

		if(empty($databaseData)) {
			return false;
		}

		$this->db->startTransaction();

		// Inserting a new tax zone
		if(!$taxZoneId) {
			$taxZoneId = $this->db->insertQuery('tax_zones', $databaseData);
			if(!$taxZoneId) {
				$this->db->rollbackTransaction();
				return false;
			}

			// Mark it as requiring a price rebuild
			$this->markProductPricingRequiresRebuild();
		}
		// Updating an existing tax zone
		else if(!$this->db->updateQuery('tax_zones', $databaseData, "id='".(int)$taxZoneId."'")) {
			$this->db->rollbackTransaction();
			return false;
		}

		if(!empty($data['applies_to']) && !$this->commitTaxZoneCustomerGroups($taxZoneId, $data)) {
			$this->db->rollbackTransaction();
			return false;
		}

		// Save the selected locations for the zone
		if(!empty($data['type']) && !$this->commitTaxZoneLocations($taxZoneId, $data)) {
			$this->db->rollbackTransaction();
			return false;
		}

		$this->db->commitTransaction();

		// Rebuild the default customer groups/tax cache
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateDefaultTaxZones();

		return $taxZoneId;
	}

	public function commitTaxZoneCustomerGroups($taxZoneId, $data)
	{
		// Delete any cuustomer groups attached to this tax zone
		$this->db->deleteQuery('tax_zone_customer_groups', 'WHERE tax_zone_id='.(int)$taxZoneId);

		// Tax zone doesn't apply to any groups, ignore
		if(empty($data['groups']) || $data['applies_to'] != 'groups') {
			$newGroup = array(
				'tax_zone_id' => $taxZoneId,
				'customer_group_id' => 0,
			);
			if(!$this->db->insertQuery('tax_zone_customer_groups', $newGroup)) {
				return false;
			}

			return true;
		}

		foreach($data['groups'] as $groupId) {
			$newGroup = array(
				'tax_zone_id' => $taxZoneId,
				'customer_group_id' => $groupId
			);
			if(!$this->db->insertQuery('tax_zone_customer_groups', $newGroup)) {
				return false;
			}
		}

		return true;
	}

	public function commitTaxZoneLocations($taxZoneId, $data)
	{
		// Delete old zone locations first
		$this->db->deleteQuery('tax_zone_locations', "WHERE tax_zone_id='".(int)$taxZoneId."'");

		// Handle a country based tax zone
		if($data['type'] == 'country') {
			$countryList = getCountryListAsIdValuePairs();
			foreach($data['countries'] as $countryId) {
				// Ignore unknown countreis
				if(empty($countryList[$countryId])) {
					continue;
				}

				$newLocation = array(
					'tax_zone_id' => $taxZoneId,
					'type' => 'country',
					'value' => $countryList[$countryId],
					'value_id' => $countryId,
				);
				if(!$this->db->insertQuery('tax_zone_locations', $newLocation)) {
					return false;
				}
			}
		}
		// Handle state based tax zones
		else if($data['type'] == 'state') {
			$countryList = getCountryListAsIdValuePairs();
			$stateList = array();

			foreach($data['states'] as $stateRecord) {
				$state = explode('-', $stateRecord, 2);

				// Caching - if we haven't loaded the list of states for this country do so now
				if(empty($stateList[$state[0]])) {
					$stateList[$state[0]] = array();
					$query = "
						SELECT *
						FROM [|PREFIX|]country_states
						WHERE statecountry='".(int)$state[0]."'
					";
					$result = $this->db->query($query);
					while($stateResult = $this->db->fetch($result)) {
						$stateList[$stateResult['statecountry']][$stateResult['stateid']] = $stateResult['statename'];
					}
				}

				if(isset($stateList[$state[0]][$state[1]])) {
					$stateName = $stateList[$state[0]][$state[1]];
				}
				else {
					$stateName = '';
				}

				$newLocation = array(
					'tax_zone_id' => $taxZoneId,
					'type' => 'state',
					'value' => $stateName,
					'value_id' => (int)$state[1],
					'country_id' => (int)$state[0]
				);
				if(!$this->db->insertQuery('tax_zone_locations', $newLocation)) {
					return false;
				}
			}
		}
		// Zip code based zone
		else if($data['type'] == 'zip') {
			$countryId = $data['country'];
			$countryName = getCountryById($countryId);
			if(!$countryName) {
				return false;
			}

			// Now save all of the codes that were entered
			foreach($data['zip_codes'] as $zipCode) {
				$zipCode = trim($zipCode);
				if(!$zipCode) {
					continue;
				}

				$newLocation = array(
					'tax_zone_id' => $taxZoneId,
					'type' => 'zip',
					'value' => $zipCode,
					'value_id' => '0',
					'country_id' => $countryId,
				);
				if(!$this->db->insertQuery('tax_zone_locations', $newLocation)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Validate that a tax rate is valid.
	 *
	 * @param array $data Array of information about a tax rate.
	 * @param array &$messages Array of error messages if there are any, by reference.
	 * @return boolean True if valid, false if not.
	 */
	public function validateTaxRate($data, &$messages)
	{
		if(!is_array($messages)) {
			$messages = array();
		}

		$rateIdQuery = '';
		if(!empty($data['id'])) {
			$rateIdQuery = " AND id!='".(int)$data['id']."'";
		}
		else {
			$requiredFields = array(
				'tax_zone_id' => 'TaxRateMissingZone',
				'name' => 'TaxRateMissingName',
			);


			foreach($requiredFields as $field => $message) {
				if(!isset($data[$field]) || trim($data[$field] === '')) {
					$messages[] = getLang($message);
				}
			}

			// Check that the tax zone exists
			$taxZone = $this->getTaxZoneById($data['tax_zone_id']);
			if(empty($taxZone)) {
				$messages[] = getLang('TaxRateMissingZone');
				return false;
			}
		}

		// Check there is no tax rate with this name already
		if(!empty($data['name'])) {
			$query = "
				SELECT id
				FROM [|PREFIX|]tax_rates
				WHERE
					name='".$this->db->quote($data['name'])."' AND
					tax_zone_id='".(int)$data['tax_zone_id']."'
					".$rateIdQuery
			;

			if($this->db->fetchOne($query)) {
				$messages[] = 'TaxRateDuplicateName';
			}
		}

		// This is an existing tax zone being edited.
		if(!empty($data['id'])) {
			$taxRate = $this->getTaxRateById($data['id']);
			if(empty($taxRate)) {
				$messages[] = getLang('InvalidTaxRate');
				return false;
			}
		}

		if(!empty($data['default_rate'])) {
			if(!is_numeric($data['default_rate']) || $data['default_rate'] < 0 || $data['default_rate'] > 100) {
				$messages[] = getLang('InvalidTaxRateDefaultClass');
			}
		}

		if(!empty($data['rates'])) {
			foreach($data['rates'] as $taxClassId => $rate) {
				if(!is_numeric($rate) || $rate < 0 || $rate > 100) {
					$taxClass = $this->getTaxClassById($taxClassId);
					$messages[] = getLang('InvalidTaxRateXClass', array(
						'name' => $taxClass['name']
					));
				}
			}
		}

		if(!empty($messages)) {
			return false;
		}

		// Valid
		return true;
	}

	public function commitTaxRate($data, $taxRateId = 0)
	{
		$changedFieldsRequiringRebuild = array(
			'enabled',
			'priority',
		);

		$defaultValues = array(
			'enabled' => 1,
			'priority' => 0,
			'default_rate' => 0,
		);

		$validFields = array(
			'name',
			'priority',
			'enabled',
			'default_rate',
			'tax_zone_id',
		);

		$databaseData = array();
		foreach($validFields as $field) {
			if(isset($data[$field])) {
				if (empty($data[$field])) {
					if ($field == 'enabled') {
						// not checked, disabled
						$databaseData[$field] = 0;
					} else {
						// not set, use default
						$databaseData[$field] = $defaultValues[$field];
					}
				} else {
					$databaseData[$field] = $data[$field];
				}
			}
			else if($taxRateId == 0 && isset($defaultValues[$field])) {
				$databaseData[$field] = $defaultValues[$field];
			}
		}

		if(empty($databaseData)) {
			return false;
		}

		if(!empty($taxRateId)) {
			$existingTaxRate = $this->getTaxRateById($taxRateId);
		}

		$this->db->startTransaction();
		$requiresRebuild = false;

		// Inserting a new tax rate
		if(!$taxRateId) {
			$taxRateId = $this->db->insertQuery('tax_rates', $databaseData);
			if(!$taxRateId) {
				$this->db->rollbackTransaction();
				return false;
			}

			// New tax rates require a rebuild of the zone
			$requiresRebuild = true;
		}
		// Updating an existing tax rate
		else if(!$this->db->updateQuery('tax_rates', $databaseData, "id='".(int)$taxRateId."'")) {
			$this->db->rollbackTransaction();
			return false;
		}

		if(!empty($existingTaxRate)) {
			$data['tax_zone_id'] = $existingTaxRate['tax_zone_id'];

			// Loop through all of the fields that if changed, would require a price rebuild
			// and if the field has changed, set the rebuild flag.
			foreach($changedFieldsRequiringRebuild as $field) {
				if(isset($data[$field]) && $data[$field] != $existingTaxRate[$field]) {
					$requiresRebuild = true;
				}
			}
		}

		// Save the select tax class rates for this rate
		if(!empty($data['rates']) && !$this->commitTaxRateClassRates($data['tax_zone_id'], $taxRateId, $data['rates'])) {
			$this->db->rollbackTransaction();
			return false;
		}

		// If a price rebuild is required for this zone, mark it as requiring such
		if($requiresRebuild) {
			// Mark it as requiring a price rebuild
			$this->markProductPricingRequiresRebuild();
		}

		// We've just configured tax - mark it as so.
		if(!in_array('taxSettings', getConfig('GettingStartedCompleted'))) {
			getClass('ISC_ADMIN_ENGINE')->markGettingStartedComplete('taxSettings');
		}

		$this->db->commitTransaction();
		return $taxRateId;
	}

	public function commitTaxRateClassRates($taxZoneId, $taxRateId, $rates)
	{
		$existingRates = array();
		$query = "
			SELECT tax_class_id, rate
			FROM [|PREFIX|]tax_rate_class_rates
			WHERE tax_rate_id='".(int)$taxRateId."'
		";
		$result = $this->db->query($query);
		while($existingRate = $this->db->fetch($result)) {
			$existingRates[$existingRate['tax_class_id']] = $existingRate['rate'] / 1;
		}

		$requiresRebuild = false;

		foreach($rates as $taxClassId => $rate) {
			$newRate = array(
				'tax_rate_id' => (int)$taxRateId,
				'tax_class_id' => (int)$taxClassId,
				'rate' => $rate / 1
			);

			// Insert or update the class rate if it's changed
			if(!isset($existingRates[$taxClassId])) {
				if(!$this->db->insertQuery('tax_rate_class_rates', $newRate)) {
					return false;
				}

				$requiresRebuild = true;
			}
			else if($existingRates[$taxClassId] != $newRate['rate']) {
				if(!$this->db->updateQuery('tax_rate_class_rates', $newRate,
					"tax_rate_id='".(int)$taxRateId."' AND tax_class_id='".(int)$taxClassId."'"
				)) {
					return false;
				}

				$requiresRebuild = true;
			}
		}

		if($requiresRebuild) {
			// Mark it as requiring a price rebuild
			$this->markProductPricingRequiresRebuild();
		}

		return true;
	}

	public function validateTaxClass($data, &$messages)
	{
		$classIdQuery = '';
		if(!empty($data['id'])) {
			$classIdQuery = " AND id!='".(int)$data['id']."'";
		}

		$requiredFields = array(
			'name' => 'TaxClassMissingName',
		);

		if(!is_array($messages)) {
			$messages = array();
		}

		foreach($requiredFields as $field => $message) {
			if(!isset($data[$field]) || trim($data[$field] === '')) {
				$messages[] = getLang($message);
			}
		}

		// Check there is no tax class with this name already
		$query = "
			SELECT id
			FROM [|PREFIX|]tax_classes
			WHERE name='".$this->db->quote($data['name'])."' ".$classIdQuery
		;

		if($this->db->fetchOne($query)) {
			$messages[] = getLang('TaxClassDuplicateName', array(
				'name' => $data['name']
			));
		}

		if(!empty($messages)) {
			return false;
		}

		return true;
	}

	public function commitTaxClass($data, $taxClassId = 0)
	{
		$validFields = array(
			'name',
			'default',
		);
		$databaseData = array();
		foreach($validFields as $field) {
			if(isset($data[$field])) {
				$databaseData[$field] = $data[$field];
			}
		}

		if(empty($databaseData)) {
			return false;
		}

		// Inserting a new tax class
		if(!$taxClassId) {
			$taxClassId = $this->db->insertQuery('tax_classes', $databaseData);
			if(!$taxClassId) {
				return false;
			}
		}
		// Updating an existing tax zone
		else if(!$this->db->updateQuery('tax_classes', $databaseData, "id='".(int)$taxClassId."'")) {
			return false;
		}

		return $taxClassId;
	}

	public function getTaxZones()
	{
		$taxZones = array();
		$query = "
			SELECT id, name
			FROM [|PREFIX|]tax_zones
			ORDER BY `default` DESC, name ASC
		";
		$result = $this->db->query($query);
		while($taxZone = $this->db->fetch($result)) {
			$taxZones[$taxZone['id']] = $taxZone['name'];
		}
		return $taxZones;
	}

	public function getTaxZoneById($taxZoneId, $getLocations = false)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]tax_zones
			WHERE id='".(int)$taxZoneId."'
		";
		$result = $this->db->query($query);
		$taxZone = $this->db->fetch($result);

		if(empty($taxZone)) {
			return false;
		}

		$taxZone['countries'] = array();
		$taxZone['states'] = array();
		$taxZone['zip_codes'] = array();
		$taxZone['country'] = 0;

		if($getLocations && !$taxZone['default']) {
			$query = "
				SELECT *
				FROM [|PREFIX|]tax_zone_locations
				WHERE tax_zone_id='".$taxZone['id']."'
			";
			$result = $this->db->query($query);
			while($location = $this->db->fetch($result)) {
				if($taxZone['type'] == 'country') {
					$taxZone['countries'][] = $location['value_id'];
					$taxZone['states'][] = $location['value_id'].'-0';
				}
				else if($taxZone['type'] == 'state') {
					$taxZone['states'][] = $location['country_id'].'-'.$location['value_id'];
					$taxZone['countries'][] = $location['country_id'];
				}
				else if($taxZone['type'] == 'zip') {
					$taxZone['country'] = $location['country_id'];
					$taxZone['zip_codes'][] = $location['value'];
				}
			}
		}

		if($taxZone['type'] == 'zip') {
			$taxZone['countries'][] = $taxZone['country'];
			$taxZone['states'][] = $taxZone['country'].'-0';
		}

		// Fetch the groups this tax zone is associated with
		$taxZone['groups'] = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]tax_zone_customer_groups
			WHERE tax_zone_id='".$taxZone['id']."'
		";
		$result = $this->db->query($query);
		while($group = $this->db->fetch($result)) {
			if($group['customer_group_id'] == 0) {
				continue;
			}
			$taxZone['groups'][] = $group['customer_group_id'];
		}

		return $taxZone;
	}

	public function getTaxRateById($taxRateId, $loadClassRates = false)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]tax_rates
			WHERE id='".(int)$taxRateId."'
		";
		$result = $this->db->query($query);
		$taxRate = $this->db->fetch($result);

		// Divide by one to remove the database applied formatting from the decimal column
		$taxRate['default_rate'] /= 1;

		if(!empty($taxRate) && $loadClassRates) {
			$taxRate['rates'] = array();
			$query = "
				SELECT *
				FROM [|PREFIX|]tax_rate_class_rates
				WHERE tax_rate_id='".(int)$taxRateId."'
			";
			$result = $this->db->query($query);
			while($classRate = $this->db->fetch($result)) {
				// Divide by one to remove the database applied formatting from the decimal column
				$taxRate['rates'][$classRate['tax_class_id']] = $classRate['rate'] / 1;
			}
		}

		return $taxRate;
	}

	public function getTaxClassById($taxClassId)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]tax_classes
			WHERE id='".(int)$taxClassId."'
		";
		$result = $this->db->query($query);
		return $this->db->fetch($result);
	}

	public function deleteTaxRatesById($ids)
	{
		if(!is_array($ids)) {
			$ids = array($ids);
		}

		// Clean up to prevent SQL injection
		array_walk($ids, 'intval');

		if(empty($ids)) {
			return true;
		}

		$idString = implode(',', $ids);

		// Select the tax zones the rates belong to
		$taxZones = array();
		$query = "
			SELECT DISTINCT(tax_zone_id)
			FROM [|PREFIX|]tax_rates
			WHERE id IN (".$idString.")
		";
		$result = $this->db->query($query);
		while($rate = $this->db->fetch($result)) {
			$taxZones[] = $rate['tax_zone_id'];
		}

		$this->db->startTransaction();

		$deleteTables = array(
			'tax_rate_class_rates' => 'tax_rate_id',

			// The actual tax_rates table should be last in the list
			'tax_rates' => 'id'
		);
		foreach($deleteTables as $table => $column) {
			if(!$this->db->deleteQuery($table, "WHERE ".$column." IN (".$idString.")")) {
				$this->db->rollbackTransaction();
				return false;
			}
		}

		// Mark it as requiring a price rebuild
		$this->markProductPricingRequiresRebuild();

		$this->db->commitTransaction();
		return true;
	}

	public function deleteTaxZonesById($ids)
	{
		if(!is_array($ids)) {
			$ids = array($ids);
		}

		// Clean up to prevent SQL injection
		array_walk($ids, 'intval');

		if(empty($ids)) {
			return true;
		}

		$idString = implode(',', $ids);

		$this->db->startTransaction();

		// Find and delete associated tax rates from this zone
		$taxRateIds = array();
		$query = "
			SELECT id
			FROM [|PREFIX|]tax_rates
			WHERE tax_zone_id IN (".$idString.")
		";
		$result = $this->db->query($query);
		while($taxRate = $this->db->fetch($result)) {
			$taxRateIds[] = $taxRate['id'];
		}

		if(!$this->deleteTaxRatesById($taxRateIds)) {
			return false;
		}

		$deleteTables = array(
			'tax_zone_customer_groups' => 'tax_zone_id',
			'tax_zone_locations' => 'tax_zone_id',

			// The actual tax_zones table should be last in the list
			'tax_zones' => 'id'
		);
		foreach($deleteTables as $table => $column) {
			if(!$this->db->deleteQuery($table, "WHERE ".$column." IN (".$idString.")")) {
				$this->db->rollbackTransaction();
				return false;
			}
		}

		// Mark them as requiring deletion
		$this->markProductPricingRequiresRebuild('deleteZone', $ids);

		$this->db->commitTransaction();

		// Rebuild the default customer groups/tax cache
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateDefaultTaxZones();

		return true;
	}

	public function copyTaxRate($id, $newName)
	{
		$taxRate = $this->getTaxRateById($id, true);

		$taxRate['name'] = $newName;

		$this->db->startTransaction();

		// Save the tax zone with a new ID
		$taxRateId = $this->commitTaxRate($taxRate);
		if(!$taxRateId) {
			return false;
		}

		$this->db->commitTransaction();
		return $taxRateId;
	}

	public function deleteTaxClassesById($ids)
	{
		if(!is_array($ids)) {
			$ids = array($ids);
		}

		// Clean up to prevent SQL injection
		array_walk($ids, 'intval');

		if(empty($ids)) {
			return true;
		}

		$this->db->startTransaction();

		// Update any products using this tax class to use the default
		$updatedProducts = array(
			'tax_class_id' => 0
		);
		if(!$this->db->updateQuery('products', $updatedProducts,
			'tax_class_id IN ('.implode(',', $ids).')')) {
			return false;
		}

		// Delete any associated tax rates
		if(!$this->db->deleteQuery('tax_rate_class_rates',
			'WHERE tax_class_id IN ('.implode(',', $ids).')')) {
				$this->db->rollbackTransaction();
				return false;
		}

		// And finally delete the tax class
		if(!$this->db->deleteQuery('tax_classes',
			'WHERE id IN ('.implode(',', $ids).')')) {
				$this->db->rollbackTransaction();
				return false;
		}

		$this->markProductPricingRequiresRebuild('deleteClass', $ids);
		$this->db->commitTransaction();
		return true;
	}

	public function getCustomerGroups()
	{
		$customerGroups = array();
		$query = "
			SELECT customergroupid, groupname
			FROM [|PREFIX|]customer_groups
			ORDER BY groupname ASC
		";
		$result = $this->db->query($query);
		while($group = $this->db->fetch($result)) {
			$customerGroups[$group['customergroupid']] = $group['groupname'];
		}
		return $customerGroups;
	}

	public function getCountryStateList(array $countryIds = array())
	{
		if(empty($countryIds)) {
			return array();
		}

		$countryIds = implode(',', $countryIds);
		$countries = array();
		$query = "
			SELECT countryid, countryname
			FROM [|PREFIX|]countries
			WHERE countryid IN (".$countryIds.")
			ORDER BY countryname
		";
		$result = $this->db->query($query);
		while($country = $this->db->fetch($result)) {
			$countries[$country['countryid']] = array(
				'id' => $country['countryid'],
				'name' => $country['countryname'],
				'states' => array()
			);
		}

		// Load in the selected states for the countries
		$query = "
			SELECT stateid, statename, statecountry
			FROM [|PREFIX|]country_states
			WHERE statecountry IN (".$countryIds.")
			ORDER BY statename ASC
		";
		$result = $this->db->query($query);
		while($state = $this->db->fetch($result)) {
			$countries[$state['statecountry']]['states'][$state['stateid']] = $state['statename'];
		}
		return $countries;
	}

	/**
	 * Rebuild the product tax pricing table.
	 *
	 * @param int $empty Intentionally unused argument.
	 * @param int $start Starting position in products table.
	 * @param int $limit Number of product prices to prices this iteration.
	 * @return int Number of prices updated.
	 */
	public function rebuildProductPricing($empty = 0, $start = 0, $limit = 200)
	{
		$priceColumns = array(
			'prodprice',
			'prodsaleprice',
			'prodcostprice',
			'prodretailprice'
		);

		$query = "
			SELECT ".implode(',', $priceColumns).", tax_class_id
			FROM [|PREFIX|]products
			ORDER BY productid ASC
		";
		$query .= $this->db->addLimit($start, $limit);

		// Truncate on first run
		if($start == 0) {
			$this->db->query('TRUNCATE [|PREFIX|]product_tax_pricing');
		}

		$updatedRows = 0;
		$result = $this->db->query($query);
		while($price = $this->db->fetch($result)) {
			foreach($priceColumns as $column) {
				if($price[$column] == 0) {
					continue;
				}

				getClass('ISC_TAX')->updateProductTaxPricing(
					$price[$column],
					$price['tax_class_id']
				);
			}
			$updatedRows += 1;
		}

		return $updatedRows;
	}

	public function deleteTaxPricingByZone($zoneIds, $start = 0, $limit = 200)
	{
		if(!is_array($zoneIds)) {
			$zoneIds = array($zoneIds);
		}
		$zoneIds = array_map('intval', $zoneIds);

		$query = "
			DELETE FROM [|PREFIX|]product_tax_pricing
			WHERE tax_zone_id IN (".implode(',', $zoneIds).")
		";
		if($limit) {
			$query .= " LIMIT ".(int)$limit;
		}

		$this->db->query($query);
		return $this->db->numAffected();
	}

	public function deleteTaxPricingByClass($classIds, $start = 0, $limit = 200)
	{
		if(!is_array($classIds)) {
			$classIds = array($classIds);
		}
		$classIds = array_map('intval', $classIds);

		$query = "
			DELETE FROM [|PREFIX|]product_tax_pricing
			WHERE tax_class_id IN (".implode(',', $classIds).")
		";
		if($limit) {
			$query .= " LIMIT ".(int)$limit;
		}

		$this->db->query($query);
		return $this->db->numAffected();
	}

	public function rebuildTaxZonePricesAction()
	{
		$pendingChanges = getConfig('taxPendingChanges');

		// Initial request to the rebuild page so show the status window
		if(!isset($_POST['run'])) {
			if(isset($pendingChanges['deleteZone']) || isset($pendingChanges['deleteClass'])) {
				$this->template->assign('isDeleting', true);
			}
			else {
				$this->template->assign('isUpdating', true);
			}

			$this->template->display('settings.tax.pricerebuild.tpl');
			exit;
		}

		$start = 0;
		if(isset($_POST['start'])) {
			$start = (int)$_POST['start'];
		}

		$callableActions = array(
			'deleteZone' => 'deleteTaxPricingByZone',
			'deleteClass' => 'deleteTaxPricingByClass',
			'rebuildPricing' => 'rebuildProductPricing',
		);

		$callback = null;
		foreach($callableActions as $action => $callback) {
			if(isset($pendingChanges[$action])) {
				break;
			}
		}

		// Nothing was found left to do, we're finished rebuilding
		if($callback === null || !isset($pendingChanges[$action])) {
			$GLOBALS['ISC_NEW_CFG']['taxPendingChanges'] = null;
			getClass('ISC_ADMIN_SETTINGS')->commitSettings($messages);
			echo isc_json_encode(array(
				'finished' => true
			));
			exit;
		}

		// If we're still here, then $callback needs to be run
		$changes = $this->$callback($pendingChanges[$action], $start);

		// No changes were made, so we're finished with $action
		if($changes === 0) {
			unset($pendingChanges[$action]);
			$GLOBALS['ISC_NEW_CFG']['taxPendingChanges'] = $pendingChanges;
			getClass('ISC_ADMIN_SETTINGS')->commitSettings($messages);
			$nextStart = 0;
		}
		else {
			$nextStart = $start + $changes;
		}

		echo isc_json_encode(array(
			'action' => $action,
			'changes' => $changes,
			'nextStart' => $nextStart
		));
	}

	public function markProductPricingRequiresRebuild($what = 'rebuildPricing', $ids = array(0))
	{
		$pendingChanges = getConfig('taxPendingChanges');
		if(!is_array($pendingChanges)) {
			$pendingChanges = array();
		}

		if(!isset($pendingChanges[$what])) {
			$pendingChanges[$what] = array();
		}

		if(!is_array($ids)) {
			$ids = array($ids);
		}

		$pendingChanges[$what] = array_merge($pendingChanges[$what], $ids);
		$pendingChanges[$what] = array_unique($pendingChanges[$what]);

		$GLOBALS['ISC_NEW_CFG']['taxPendingChanges'] = $pendingChanges;
		getClass('ISC_ADMIN_SETTINGS')->commitSettings($messages);
		return true;
	}
}