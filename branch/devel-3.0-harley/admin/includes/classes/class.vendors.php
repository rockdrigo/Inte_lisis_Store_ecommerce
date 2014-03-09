<?php
class ISC_ADMIN_VENDORS extends ISC_ADMIN_BASE
{
	const VENDOR_LOGO = 'logo';
	const VENDOR_PHOTO = 'photo';

	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('vendors');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings.shipping');

		$GLOBALS['CurrentVendor'] = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
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

		$GLOBALS['BreadcrumEntries'][GetLang('Home')] = "index.php";
		if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
			$GLOBALS['BreadcrumEntries'][GetLang('Vendors')] = "index.php?ToDo=viewVendors";
		}

		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && isc_strtolower($Do) != 'editvendor' && isc_strtolower($Do) != 'saveupdatedvendor' && @$_REQUEST['vendorId'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				return;
			}
		}

		switch(isc_strtolower($Do))
		{
			case "editvendor":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
					if(isset($_REQUEST['vendorId'])) {
						$vendor = $this->GetVendorData($_REQUEST['vendorId']);
						$GLOBALS['BreadcrumEntries'][$vendor['vendorname']] = '';
					}
				}
				else {
					$GLOBALS['BreadcrumEntries'][GetLang('VendorProfile')] = '';
				}
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->EditVendor();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "saveupdatedvendor":
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->SaveUpdatedVendor();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "addvendor":
				$GLOBALS['BreadcrumEntries'][GetLang('AddVendor')] = '';
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->AddVendor();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "savenewvendor":
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->SaveNewVendor();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "deletevendors":
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->DeleteVendors();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			default:
				if(!isset($_REQUEST['ajax'])) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				}
				$this->ManageVendors();
				if(!isset($_REQUEST['ajax'])) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				}
				break;
		}
	}

	/**
	 * Generate a grid containing a list of vendors.
	 *
	 * @param int The number of vendors currently set up (by reference)
	 * @return string The HTML for the vendor grid.
	 */
	private function ManageVendorsGrid(&$numVendors)
	{
		$page = 0;
		$start = 0;
		$numVendors = 0;
		$GLOBALS['VendorsGrid'] = '';
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
			$start = ($page * ISC_VENDORS_PER_PAGE) - (ISC_VENDORS_PER_PAGE);
		}

		// Fetch the list of vendors
		$query = "SELECT COUNT(vendorid) FROM [|PREFIX|]vendors";
		$numVendors = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);

		// If there aren't any vendors set up, just return nothing here
		if($numVendors == 0) {
			return '';
		}

		$numPages = ceil($numVendors / ISC_VENDORS_PER_PAGE);

		// Add the "(Page x of n)" label
		if($numVendors > ISC_VENDORS_PER_PAGE) {
			$GLOBALS['Nav'] = "(".GetLang('Page')." ".$page." of ".$numPages.") &nbsp;&nbsp;&nbsp;";
			$GLOBALS['Nav'] .= BuildPagination($numVendors, ISC_VENDORS_PER_PAGE, $page, "index.php?ToDo=viewVendors&currentTab=1");
		}
		else {
			$GLOBALS['Nav'] = "";
			$GLOBALS['HidePaging'] = 'display: none';
		}

		// Fetch out the list of users associated with vendors
		$vendorUsers = array();
		$query = "SELECT * FROM [|PREFIX|]users WHERE uservendorid!='0'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($user = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$vendorUsers[$user['uservendorid']][] = $user;
		}

		// Start fetching out the actual vendors
		$query = "
			SELECT *
			FROM [|PREFIX|]vendors
			ORDER BY vendorname ASC
			";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_VENDORS_PER_PAGE);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['VendorName'] = isc_html_escape($vendor['vendorname']);
			$GLOBALS['VendorId'] = $vendor['vendorid'];

			if(isset($vendorUsers[$vendor['vendorid']])) {
				$userList = array();
				foreach($vendorUsers[$vendor['vendorid']] as $user) {
					$userList[] = '<a href="index.php?ToDo=editUser&amp;userId='.$user['pk_userid'].'">'.isc_html_escape($user['username']).'</a>';
				}
				$userList = implode(', ', $userList);
			}
			else {
				$userList = GetLang('xNone');
			}
			$GLOBALS['VendorUsers'] = $userList;

			$GLOBALS['VendorsGrid'] .= $this->template->render('vendors.manage.row.tpl');
		}
		return $this->template->render('vendors.manage.grid.tpl');
	}

	/**
	 * Show the "Manage Vendors" page.
	 */
	public function ManageVendors()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		// Fetch any vendors and place them in a data grid
		$GLOBALS['VendorDataGrid'] = $this->ManageVendorsGrid($numVendors);

		// Was this an ajax based sort? Return the table now
		if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
			echo $GLOBALS['VendorDataGrid'];
			return;
		}

		// No vendors have been configured yet
		if($numVendors == 0) {
			$GLOBALS['DisableDelete'] = 'disabled="disabled"';
			$GLOBALS['DisplayGrid'] = "none";
			$GLOBALS['Message'] = MessageBox(GetLang('NoVendors'), MSG_SUCCESS);
		}

		$this->template->display('vendors.manage.tpl');
	}

	/*
	 * Show the "Add a New Vendor" page.
	 */
	private function AddVendor()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$GLOBALS['FormAction']		= 'SaveNewVendor';
		$GLOBALS['Title']			= GetLang('AddVendor');
		$GLOBALS['Intro']			= GetLang('AddVendorIntro');

		$stateOptions = GetStatesByCountryNameAsOptions(GetConfig('CompanyCountry'), $numStates, GetConfig('CompanyState'));

		if ($numStates > 0) {
			// Show the states dropdown list
			$GLOBALS['StateList'] = $stateOptions;
			$GLOBALS['HideStateBox'] = 'display: none';
		}
		else {
			// Show the states text box
			$GLOBALS['HideStateList'] = 'display: none';
		}

		$vendorLogoSize = GetConfig('VendorLogoSize');
		if(!$vendorLogoSize) {
			$GLOBALS['HideLogoUpload'] = 'display: none';
		}
		else {
			$GLOBALS['HideCurrentVendorLogo'] = 'display: none';
		}

		$vendorPhotoSize = GetConfig('VendorPhotoSize');
		if(!$vendorPhotoSize) {
			$GLOBALS['HidePhotoUpload'] = 'display: none';
		}
		else {
			$GLOBALS['HideCurrentVendorPhoto'] = 'display: none';
		}

		$GLOBALS['AccessAllCategories'] = 'checked="checked"';
		$GLOBALS['HideAccessCategories'] = 'display: none';
		$categoryClass = GetClass('ISC_ADMIN_CATEGORY');
		$GLOBALS['AccessCategoryOptions'] = $categoryClass->GetCategoryOptions('',  "<option %s value='%d'>%s</option>", 'selected="selected"', "", false);


		$GLOBALS['VendorProfitMargin'] = number_format('0', GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), '');

		$GLOBALS['CountryList'] = GetCountryList(GetConfig('CompanyCountry'));

		$GLOBALS['HideForwardInvoiceEmails'] = 'display: none';
		$GLOBALS['HideShipping'] = 'display: none';

		$wysiwygOptions = array(
			'id'		=> 'wysiwyg',
			'width'		=> '100%',
			'height'	=> '500px',
		);
		$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

		$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndAddAnother');
		$this->template->display('vendor.form.tpl');
	}

	/**
	 * Actually save a new vendor in the database.
	 */
	private function SaveNewVendor()
	{
		// If we've had any input from the WYSIWYG editor, then copy it across to the appropriate place
		if(isset($_POST['wysiwyg_html'])) {
			$_POST['vendorbio'] = FormatWYSIWYGHTML($_POST['wysiwyg_html']);
		}
		else {
			$_POST['vendorbio'] = FormatWYSIWYGHTML($_POST['wysiwyg']);
		}

		if(!isset($_POST['forwardvendoremails'])) {
			unset($_POST['vendororderemail']);
		}

		$message = '';
		if(!$this->ValidateVendor($_POST, $message)) {
			FlashMessage($message, MSG_ERROR);
			$this->AddVendor();
			return;
		}

		if(!$this->CommitVendor($_POST)) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingVendor').$error, MSG_ERROR);
			$this->AddVendor();
			return;
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['vendorname']);
			if(isset($_POST['addAnother'])) {
				$url = 'index.php?ToDo=addVendor';
			}
			else {
				$url = 'index.php?ToDo=viewVendors';
			}
			FlashMessage(GetLang('VendorCreated'), MSG_SUCCESS, $url);
		}
	}

	/**
	 * Validate a new or updated vendor - check for required fields etc.
	 *
	 * @param array Array of data about the vendor.
	 * @param string An error message, if there is one (by reference)
	 * @return boolean True if the vendor is valid, false if not.
	 */
	private function ValidateVendor($data, &$error)
	{
		$requiredFields = array(
			'vendorname' => GetLang('EnterVendorName'),
			'vendorphone' => GetLang('EnterVendorPhone'),
			'vendorbio' => GetLang('EnterVendorBio'),
			'vendoraddress' => GetLang('EnterVendorAddress'),
			'vendorcity' => GetLang('EnterVendorCity'),
			'vendorcountry' => GetLang('EnterVendorCountry'),
			'vendorzip' => GetLang('EnterVendorZipCode'),
			'vendoremail' => GetLang('EnterVendorEmail')
		);
		foreach($requiredFields as $field => $message) {
			if(!isset($data[$field]) || trim($data[$field]) == '') {
				$error = $message;
				return false;
			}
		}

		// Does a vendor exist with the same name?
		$vendorIdQuery = '';
		if(isset($data['vendorId']) && $data['vendorId'] > 0) {
			$vendorIdQuery = " AND vendorid!='".(int)$data['vendorId']."'";
		}

		// Check that a duplicate vendor doesn't exist with this name
		$query = "
			SELECT vendorid
			FROM [|PREFIX|]vendors
			WHERE vendorname='".$GLOBALS['ISC_CLASS_DB']->Quote($data['vendorname'])."'".$vendorIdQuery;

		if($GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
			$error = GetLang('DuplicateVendorName');
			return false;
		}

		// Otherwise it's valid
		return true;
	}

	/**
	 * Fetch a vendor from the database based on the passed ID.
	 *
	 * @param int The vendor ID.
	 * @return array Array of information about the vendor.
	 */
	private function GetVendorData($vendorId)
	{
		$query = "SELECT * FROM [|PREFIX|]vendors WHERE vendorid='".(int)$vendorId."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		return $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	}

	/**
	 * Actually save a new vendor in the database or update an existing one.
	 *
	 * @param array Array of data about the vendor to save.
	 * @param int The existing vendor ID to update, if we have one.
	 * @return boolean True if successful, false if not.
	 */
	private function CommitVendor($data, $vendorId=0)
	{
		$data['vendorcountry'] = GetCountryById((int)$data['vendorcountry']);

		if (isset($data['vendorstate']) && $data['vendorstate'] != "") {
			$data['vendorstate'] = GetStateById((int)$data['vendorstate']);
		}
		else {
			$data['vendorstate'] = $_POST['vendorstate1'];
		}

		$existingName = '';
		if($vendorId > 0) {
			$existingVendor = $this->GetVendorData($vendorId);
			$existingName = $existingVendor['vendorfriendlyname'];
		}

		if(!isset($data['vendororderemail'])) {
			$data['vendororderemail'] = '';
		}

		if(!isset($data['vendorshipping']) || $data['vendorshipping'] == 0) {
			$data['vendorshipping'] = 0;

			if($vendorId > 0) {
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_zones', "WHERE zonevendorid='".(int)$vendorId."'");
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_zone_locations', "WHERE locationvendorid='".(int)$vendorId."'");
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_methods', "WHERE methodvendorid='".(int)$vendorId."'");
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_vars', "WHERE varvendorid='".(int)$vendorId."'");
			}
		}
		else {
			if($vendorId > 0 && $existingVendor['vendorshipping'] == 0) {
				// Find the default zone for the store and copy it
				$query = "
					SELECT *
					FROM [|PREFIX|]shipping_zones
					WHERE zonedefault='1' AND zonevendorid='0'
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$masterZone = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				unset($masterZone['zoneid']);
				$masterZone['zonevendorid'] = $vendorId;
				$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zones', $masterZone);
			}
		}

		$vendorData = array(
			'vendorname' => $data['vendorname'],
			'vendorphone' => $data['vendorphone'],
			'vendorbio' => $data['vendorbio'],
			'vendoraddress' => $data['vendoraddress'],
			'vendorcity' => $data['vendorcity'],
			'vendorcountry' => $data['vendorcountry'],
			'vendorstate' => $data['vendorstate'],
			'vendorzip' => $data['vendorzip'],
			'vendorfriendlyname' => $this->GenerateVendorFriendlyName($data['vendorname'], $vendorId, $existingName),
			'vendororderemail' => $data['vendororderemail'],
			'vendorshipping' => (int)$data['vendorshipping'],
			'vendoremail' => $data['vendoremail'],
		);

		// If we have permission to, set the permissions for the vendor we're creating/editing
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() == 0 || $vendorId == 0) {
			$vendorData['vendorprofitmargin'] = DefaultPriceFormat($data['vendorprofitmargin']);
			$vendorData['vendoraccesscats'] = '';
			if(!isset($data['vendorlimitcats']) && is_array($data['vendoraccesscats'])) {
				$data['vendoraccesscats'] = array_map('intval', $data['vendoraccesscats']);
				$vendorData['vendoraccesscats'] = implode(',', $data['vendoraccesscats']);
			}
		}

		if($vendorId == 0) {
			$vendorId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('vendors', $vendorData);

			$updatedVendor = array();
			// If we chose to upload a logo for this vendor, save it too
			foreach(array(self::VENDOR_LOGO, self::VENDOR_PHOTO) as $image) {
				$vendorImage = $this->SaveVendorImage($vendorId, $image);
				if($vendorImage === false) {
					$GLOBALS['ISC_CLASS_DB']->DeleteQuery('vendors', "WHERE vendorid='".(int)$vendorId."'");
					return false;
				}
				else {
					$updatedVendor['vendor'.$image] = $vendorImage;
				}
			}

			if(!empty($updatedVendor)) {
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('vendors', $updatedVendor, "vendorid='".(int)$vendorId."'");
			}
		}
		else {
			// If we chose to upload a logo for this vendor, save it too
			foreach(array(self::VENDOR_LOGO, self::VENDOR_PHOTO) as $image) {
				// Did we choose to delete a logo?
				if(isset($data['deletevendor'.$image])) {
					$this->DeleteVendorImage($vendorId, $image);
					$vendorData['vendor'.$image] = '';
				}

				// Maybe we chose to upload an image?
				$vendorImage = $this->SaveVendorImage($vendorId, $image);
				if($vendorImage === false) {
					return false;
				}
				else if($vendorImage) {
					$vendorData['vendor'.$image] = $vendorImage;
				}
			}

			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('vendors', $vendorData, "vendorid='".(int)$vendorId."'");
		}

		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateVendors();

		// Couldn't save? return an error message
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			return false;
		}

		return true;
	}

	/**
	 * Show the "Edit Vendor" form.
	 */
	private function EditVendor()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();
		$vendor = $this->GetVendorData($_REQUEST['vendorId']);

		// If the vendor doesn't exist, show an error message
		if(!isset($vendor['vendorid'])) {
			FlashMessage(GetLang('InvalidVendor'), MSG_ERROR, 'index.php?ToDo=viewVendors');
		}

		// Set up the form title and action
		$GLOBALS['FormAction']	= 'SaveUpdatedVendor';
		if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
			$GLOBALS['Title']		= GetLang('EditVendor');
		}
		else {
			$GLOBALS['Title']		= GetLang('VendorProfile');
		}
		$GLOBALS['Intro']		= GetLang('EditVendorIntro');

		// Set the form values
		$GLOBALS['VendorId']		= (int)$vendor['vendorid'];
		$GLOBALS['VendorName']		= isc_html_escape($vendor['vendorname']);
		$GLOBALS['VendorPhone']		= isc_html_escape($vendor['vendorphone']);
		$GLOBALS['VendorAddress']	= isc_html_escape($vendor['vendoraddress']);
		$GLOBALS['VendorCity']		= isc_html_escape($vendor['vendorcity']);
		$GLOBALS['VendorZip']		= isc_html_escape($vendor['vendorzip']);
		$GLOBALS['CountryList']		= GetCountryList($vendor['vendorcountry']);
		$GLOBALS['VendorEmail']		= isc_html_escape($vendor['vendoremail']);
		$GLOBALS['VendorState']		= isc_html_escape($vendor['vendorstate']);

		$vendorLogoSize = GetConfig('VendorLogoSize');
		if(!$vendorLogoSize) {
			$GLOBALS['HideLogoUpload'] = 'display: none';
		}
		else {
			$GLOBALS['HideCurrentVendorLogo'] = 'display: none';
			if($vendor['vendorlogo']) {
				$GLOBALS['HideCurrentVendorLogo'] = '';
				$GLOBALS['CurrentVendorLogoLink'] = GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/'.isc_html_escape($vendor['vendorlogo']);
				$GLOBALS['CurrentVendorLogo'] = isc_html_escape($vendor['vendorlogo']);
			}
		}

		$vendorPhotoSize = GetConfig('VendorPhotoSize');
		if(!$vendorPhotoSize) {
			$GLOBALS['HidePhotoUpload'] = 'display: none';
		}
		else {
			$GLOBALS['HideCurrentVendorPhoto'] = 'display: none';
			if($vendor['vendorphoto']) {
				$GLOBALS['HideCurrentVendorPhoto'] = '';
				$GLOBALS['CurrentVendorPhotoLink'] = GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/'.isc_html_escape($vendor['vendorphoto']);
				$GLOBALS['CurrentVendorPhoto'] = isc_html_escape($vendor['vendorphoto']);
			}
		}

		if($vendor['vendororderemail'] != '') {
			$GLOBALS['VendorForwardInvoices'] = 'checked="checked"';
			$GLOBALS['VendorOrderEmail'] = isc_html_escape($vendor['vendororderemail']);
		}
		else {
			$GLOBALS['HideForwardInvoiceEmails'] = 'display: none';
		}

		$GLOBALS['VendorProfitMargin'] = number_format($vendor['vendorprofitmargin'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), '');

		// Vendor has configured shipping
		if($vendor['vendorshipping'] == 1) {
			$GLOBALS['VendorShippingCustom'] = 'checked="checked"';
			$GLOBALS['HideStoreMethodsList'] = 'display: none';

			// Fetch any shipping methods set up
			$GLOBALS['HideShippingNotConfigured'] = 'display: none';

			// Fetch any shipping zones, place them in the data grid
			$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_SHIPPING'] = GetClass('ISC_ADMIN_SETTINGS_SHIPPING');
			$GLOBALS['ShippingZonesGrid'] = $GLOBALS['ISC_CLASS_ADMIN_SETTINGS_SHIPPING']->ManageShippingZonesGrid($numZones);

			// No shipping zones have been configured yet
			if($numZones == 0) {
				$GLOBALS['DisableDeleteZones'] = 'disabled="disabled"';
				$GLOBALS['DisplayZoneGrid'] = "none";
				$GLOBALS['NoZonesMessage'] = MessageBox(GetLang('NoShippingZones'), MSG_SUCCESS);
			}

		}
		// Using store shipping
		else {
			$GLOBALS['VendorShippingDefault'] = 'checked="checked"';
			$GLOBALS['HideShippingZonesGrid'] = 'display: none';
		}

		// Fetch a list of the shipping methods available for the entire store
		$GLOBALS['StoreShippingMethods'] = $this->GetStoreShippingMethods();

		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() > 0) {
			$GLOBALS['HidePermissions'] = 'display: none';
		}
		// Showing the permissions table, so generate the list for that
		else {
			$GLOBALS['HidePermissions'] = '';

			$accessibleCategories = '';
			if($vendor['vendoraccesscats']) {
				$accessibleCategories = explode(',', $vendor['vendoraccesscats']);
				$accessibleCategories = array_map('intval', $accessibleCategories);
			}

			if(empty($accessibleCategories)) {
				$GLOBALS['AccessAllCategories'] = 'checked="checked"';
				$GLOBALS['HideAccessCategories'] = 'display: none';
			}

			$categoryClass = GetClass('ISC_ADMIN_CATEGORY');
			$GLOBALS['AccessCategoryOptions'] = $categoryClass->GetCategoryOptions($accessibleCategories,  "<option %s value='%d'>%s</option>", 'selected="selected"', "", false);
		}

		$stateOptions = GetStatesByCountryNameAsOptions($vendor['vendorcountry'], $numStates, $vendor['vendorstate']);

		if ($numStates > 0) {
			// Show the states dropdown list
			$GLOBALS['StateList'] = $stateOptions;
			$GLOBALS['HideStateBox'] = 'display: none';
		}
		else {
			// Show the states text box
			$GLOBALS['HideStateList'] = 'display: none';
		}
		// Initialize the WYSIWYG editor
		$wysiwygOptions = array(
			'id'		=> 'wysiwyg',
			'width'		=> '100%',
			'height'	=> '500px',
			'value'		=> $vendor['vendorbio']
		);
		$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

		$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndContinueEditing');
		$this->template->display('vendor.form.tpl');
	}

	/**
	 * Get the list of store wide shipping zones/methods and return it as a list.
	 *
	 * @return string The list of shipping methods available.
	 */
	public function GetStoreShippingMethods()
	{
		$methodsList = '<ul style="margin-bottom: 0;">';
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_zones
			WHERE zonevendorid='0'
			ORDER BY zonedefault DESC, zonename ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$zones = array();
		while($zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$zones[$zone['zoneid']] = $zone;
			$methods[$zone['zoneid']] = array();
		}

		$query = "
			SELECT methodname, zoneid
			FROM [|PREFIX|]shipping_methods
			WHERE zoneid IN (".implode(',', array_keys($zones)).")
			ORDER BY methodname
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($method = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$methods[$method['zoneid']][] = $method['methodname'];
		}

		foreach($zones as $zone) {
			$methodsList .= '<li><strong>'.isc_html_escape($zone['zonename']).'</strong>';
			$zoneMethods = '<ul>';
			foreach($methods[$zone['zoneid']] as $method) {
				$zoneMethods .= '<li>'.isc_html_escape($method).'</li>';
			}
			if($zoneMethods) {
				$zoneMethods .= '</ul>';
				$methodsList .= $zoneMethods;
			}
			$methodsList .'</li>';
		}

		$methodsList .= '</ul>';
		return $methodsList;
	}

	/**
	 * Actually save the changes made to a vendor.
	 */
	private function SaveUpdatedVendor()
	{
		$vendor = $this->GetVendorData($_REQUEST['vendorId']);
		// If the vendor doesn't exist, show an error message
		if(!isset($vendor['vendorid'])) {
			FlashMessage(GetLang('InvalidVendor'), MSG_ERROR, 'index.php?ToDo=viewVendors');
		}

		// If we've had any input from the WYSIWYG editor, then copy it across to the appropriate place
		if(isset($_POST['wysiwyg_html'])) {
			$_POST['vendorbio'] = FormatWYSIWYGHTML($_POST['wysiwyg_html']);
		}
		else {
			$_POST['vendorbio'] = FormatWYSIWYGHTML($_POST['wysiwyg']);
		}

		if(!isset($_POST['forwardvendoremails'])) {
			unset($_POST['vendororderemail']);
		}

		$message = '';
		// Validate and if there's an error, show the edit page again for this vendor
		if(!$this->ValidateVendor($_POST, $message)) {
			FlashMessage($message, MSG_ERROR);
			$this->EditVendor();
			return;
		}

		// OK, so now it's valid, save the vendor in the database
		if(!$this->CommitVendor($_POST, $vendor['vendorid'])) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingVendor').$error, MSG_ERROR);
			$this->EditVendor();
			return;
		}
		else {
			// Log this action
			if($vendor['vendorshipping'] == 0 && $_POST['vendorshipping'] == 1) {
				$url = 'index.php?ToDo=editVendor&vendorId='.$vendor['vendorid'].'&currentTab=1';
			}
			else if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() || isset($_POST['addAnother'])) {
				$url = 'index.php?ToDo=editVendor&vendorId='.$vendor['vendorid'];
			}
			else {
				$url = 'index.php?ToDo=viewVendors';
			}
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($vendor['vendorid'], $_POST['vendorname']);
			FlashMessage(GetLang('VendorUpdated'), MSG_SUCCESS, $url);
		}
	}

	/**
	 * Delete one or more selected vendors.
	 */
	private function DeleteVendors()
	{
		if(!isset($_REQUEST['vendors'])) {
			ob_end_clean();
			header("Location: index.php?ToDo=viewVendors");
		}

		$vendorIds = array_map('intval', $_REQUEST['vendors']);
		$vendorIds[] = -1;

		$vendorIds = implode("','", $vendorIds);

		// Delete the vendors from the database
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('vendors', "WHERE vendorid IN ('".$vendorIds."')");
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('users', "WHERE uservendorid IN ('".$vendorIds."')");
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('pages', "WHERE pagevendorid IN ('".$vendorIds."')");

		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateVendors();

		$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
		if($err) {
			FlashMessage($err, MSG_ERROR, 'index.php?ToDo=viewVendors');
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminaction(count($_POST['vendors']));
			FlashMessage(GetLang('VendorsDeleted'), MSG_SUCCESS, 'index.php?ToDo=viewVendors');
		}
	}

	/**
	 * Generate a unique "friendly name" for a vendor.
	 *
	 * @param string The name of the vendor.
	 * @param int If we have one, the current ID of the vendor
	 * @param string If we have one, the existing friendly name
	 * @return string The friendly name.
	 */
	private function GenerateVendorFriendlyName($vendorName, $existingId, $existingFriendlyName)
	{
		$friendlyName = isc_strtolower(trim($vendorName));
		$friendlyName = preg_replace("#\s#", "-", $friendlyName);
		$friendlyName = preg_replace("#([^a-zA-Z0-9-_])#", "", $friendlyName);
		$friendlyName = preg_replace("#\-{2,}#", '', $friendlyName);

		if(!$friendlyName) {
			return '';
		}
		else if($existingFriendlyName && $friendlyName == $existingFriendlyName) {
			return $friendlyName;
		}
		// Otherwise, generate a friendly ID
		else {
			$friendlyCount = 0;
			$currentFriendlyName = $friendlyName;
			do {
				$query = "
					SELECT vendorid
					FROM [|PREFIX|]vendors
					WHERE vendorfriendlyname='".$GLOBALS['ISC_CLASS_DB']->Quote($currentFriendlyName)."' AND vendorid!='".(int)$existingId."'
				";
				$exists = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
				if($exists) {
					++$friendlyCount;
					$currentFriendlyName = $friendlyName.$friendlyCount;
				}
				// Found a place, insert and then get out asap!
				else {
					return $currentFriendlyName;
				}
			} while($exists);
		}
	}

	/**
	 * Save an incoming vendor image (from the user's browser) in to the file system.
	 *
	 * @param int The vendor ID that this image should be attached to.
	 * @param string The type of image to upload - either self::VENDOR_LOGO or self::VENDOR_PHOTO
	 * @return string The path to the vendor image uploaded.
	 */
	private function SaveVendorImage($vendorId, $imageType)
	{
		// No image to save, so it's OK
		if(!isset($_FILES['vendor'.$imageType]) || !is_uploaded_file($_FILES['vendor'.$imageType]['tmp_name'])) {
			return '';
		}

		$maxDimensions = GetConfig('Vendor'.ucfirst($imageType).'Size');
		if(!$maxDimensions) {
			@unlink($_FILES['vendor'.$imageType]['tmp_name']);
			return '';
		}
		list($maxWidth, $maxHeight) = explode('x', $maxDimensions);

		$ext = GetFileExtension($_FILES['vendor'.$imageType]['name']);
		$imageName = 'vendor_images/'.$vendorId.'_'.$imageType.'.'.$ext;
		$destLocation = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/'.$imageName;

		// Attempt to move the image over (some hosts have problems working with files in the temp directory)
		if(!move_uploaded_file($_FILES['vendor'.$imageType]['tmp_name'], $destLocation)) {
			@unlink($_FILES['vendor'.$imageType]['tmp_name']);
			return false;
		}

		try {
			$image = ISC_IMAGE_LIBRARY_FACTORY::getImageLibraryInstance($destLocation);
			$image->loadImageFileToScratch();
			$image->resampleScratchToMaximumDimensions($maxWidth, $maxHeight);

			// simulate behaviour of old GenerateThumbnail function which would save to the same format as the original
			switch ($image->getImageType()) {
				case IMAGETYPE_GIF:
					$writeOptions = new ISC_IMAGE_WRITEOPTIONS_GIF;
					break;

				case IMAGETYPE_JPEG:
					$writeOptions = new ISC_IMAGE_WRITEOPTIONS_JPEG;
					break;

				case IMAGETYPE_PNG:
					$writeOptions = new ISC_IMAGE_WRITEOPTIONS_PNG;
					break;
			}

			$image->saveScratchToFile($destLocation, $writeOptions);
		} catch (Exception $exception) {
			return false;
		}

		// Otherwise, return the location of the image
		return $imageName;
	}

	/**
	 * Delete a vendor image from the database and the file system.
	 *
	 * @param int The vendor ID to delete the image of.
	 * @param string The type of image to delete - either self::VENDOR_LOGO or self::VENDOR_PHOTO
	 * @return boolean True if successful, false if not.
	 */
	private function DeleteVendorImage($vendorId, $imageType)
	{
		$vendor = $this->GetVendorData($vendorId);
		if(!isset($vendor['vendorid'])) {
			return false;
		}

		if(!isset($vendor['vendor'.$imageType]) || !$vendor['vendor'.$imageType]) {
			return true;
		}

		// Delete the file from the filesystem
		@unlink(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').$vendor['vendor'.$imageType]);
		return true;
	}
}