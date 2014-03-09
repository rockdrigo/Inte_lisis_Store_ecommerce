<?php
require_once APP_ROOT . "/includes/exporter/class.exportfiletype.factory.php";
require_once APP_ROOT . "/includes/exporter/class.exportmethod.factory.php";

/**
* Manages CSV templates for exporting of order, product and customer data
*
* @author Ray Ward
*/
class ISC_ADMIN_EXPORTTEMPLATES extends ISC_ADMIN_BASE
{
	/**
	* Handles the incoming action/page request
	*
	* @param string $do The action or page requested
	*/
	public function HandleToDo($do)
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('exporttemplates');

		$do = isc_strtolower($do);

		if (($do != "viewexporttemplates" && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_ExportTemplates)) || !gzte11(ISC_MEDIUMPRINT)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		switch ($do) {
			case 'createexporttemplate':
				$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('ExportTemplates') => "index.php?ToDo=viewExportTemplates", GetLang('AddExportTemplate') => "index.php?ToDo=createExportTemplate");

				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->CreateTemplate();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();

				break;
			case "saveexporttemplate":
				$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('ExportTemplates') => "index.php?ToDo=viewExportTemplates");

				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->SaveTemplate();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();

				break;
			case 'editexporttemplate':
				$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('ExportTemplates') => "index.php?ToDo=viewExportTemplates", GetLang('EditExportTemplate') => "index.php?ToDo=editExportTemplate");

				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->EditTemplate();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();

				break;
			case "updateexporttemplate":
				$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('ExportTemplates') => "index.php?ToDo=viewExportTemplates");

				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->UpdateTemplate();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case 'deleteexporttemplate':
				$this->DeleteTemplate();
				break;
			case 'startexporttemplate':
				$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('ExportTemplates') => "index.php?ToDo=viewExportTemplates", "Export" => "index.php?ToDo=startExportTemplate");

				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->StartExport();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			default:
				$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('ExportTemplates') => "index.php?ToDo=viewExportTemplates");

				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->ManageTemplates();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
		}
	}

	/**
	* Allows management of templates in the store by listing existing templates and providing add/edit/delete functions
	*
	*/
	private function ManageTemplates()
	{
		$GLOBALS['TemplatesGrid'] = $this->BuildTemplatesGrid();

		$GLOBALS['Message'] = GetFlashMessageBoxes();

		if (empty($GLOBALS['TemplatesGrid'])) {
			// There aren't any templates, show a message so they can create one
			$GLOBALS['Message'] = MessageBox(GetLang('NoExportTemplates'), MSG_SUCCESS);

			$GLOBALS['DisableDelete'] = "DISABLED";

			$GLOBALS['Title'] = GetLang('ManageExportTemplates');
			$GLOBALS['ManageExportTemplatesIntro'] = GetLang('ManageExportTemplatesIntro');
		}

		$this->template->display('exporttemplates.manage.tpl');
	}

	/**
	* Gets a list of templates available to the user
	*
	*/
	public function GetTemplates($show_mine = true, $show_builtin = false, $sortField = "", $sortOrder = "")
	{
		$where = "";
		if ($show_mine) {
			if(gzte11(ISC_HUGEPRINT)) {
				$GLOBALS['VendorLabel'] = GetLang("VendorLabel");

				$vendorid = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
				if($vendorid) {
					$where = "et.vendorid = '" .  $vendorid . "'";
				}
			}
		}

		if ($show_builtin) {
			if ($show_mine && $where) {
				$where .= " OR ";
			}

			if (!$show_mine || ($show_mine && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId())) {
				$where .= "builtin = 1";
			}
		}
		else {
			if ($show_mine && $where) {
				$where .= " AND ";
			}

			$where .= "builtin = 0";
		}

		if ($where) {
			$where = " WHERE " . $where;
		}

		if ($sortField) {
			$order = $sortField . " " . $sortOrder;

			if ($sortField != "exporttemplatename") {
				$order .= ", exporttemplatename";
			}
		}
		else {
			$order ="
				builtin DESC,
				vendorname,
				exporttemplatename";
		}

		// Get the list of templates
		$query = "
			SELECT
				et.*,
				v.vendorname
			FROM
				[|PREFIX|]export_templates et
				LEFT JOIN [|PREFIX|]vendors v ON (v.vendorid = et.vendorid)
			" . $where . "
			ORDER BY
				" . $order;

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		return $result;
	}

	/**
	* Generates a grid that lists the templates
	*
	*/
	private function BuildTemplatesGrid()
	{
		// set sort order
		if(isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'asc') {
			$sortOrder = 'asc';
		}
		else {
			$sortOrder = "desc";
		}

		// define our sortable fields
		$sortLinks = array(
			"Title" => "exporttemplatename",
			"Type" => "builtin",
			"Vendor" => "vendorname"
		);

		// get the field to sort on
		if(isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
			$sortField = $_GET['sortField'];
			SaveDefaultSortField("ManageExportTemplates", $_REQUEST['sortField'], $sortOrder);
		}
		else {
			list($sortField, $sortOrder) = GetDefaultSortField("ManageExportTemplates", "builtin", $sortOrder);
		}

		$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
		$GLOBALS['SortURL'] = $sortURL;

		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;

		// get templates
		$result = $this->GetTemplates(true, true, $sortField, $sortOrder);

		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
			return "";
		}

		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewExportTemplates", $sortField, $sortOrder);

		if(gzte11(ISC_HUGEPRINT) && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {	//&& !$show_builtin
			$showvendor = true;
			$GLOBALS['VendorLabel'] = GetLang('VendorLabel');
			$GLOBALS['HideVendorColumn'] = "";
		}
		else {
			$showvendor = false;
			$GLOBALS['VendorLabel'] = "";
			$GLOBALS['HideVendorColumn'] = 'style="display: none;"';
		}

		// Build the items for the grid
		$templateGridData = "";
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['ExportTemplateId'] = $row['exporttemplateid'];
			$GLOBALS['ExportTemplateName'] = $row['exporttemplatename'];


			if ($row['builtin']) {
				$check_disabled = "disabled=\"disabled\"";
				$GLOBALS['TemplateType'] = GetLang("BuiltIn");
			}
			else {
				$GLOBALS['TemplateType'] = GetLang("Custom");
				$check_disabled = "";
			}

			$GLOBALS['CheckTemplate'] = "<input type=\"checkbox\" name=\"exporttemplates[" . $row['exporttemplateid'] . "]\" value=\"1\" " . $check_disabled . ">";

			if ($showvendor) {
				if ($row['vendorname']) {
					$vendorname = $row['vendorname'];
				}
				else {
					$vendorname = "N/A";
				}
				$GLOBALS['VendorName'] = $vendorname;
			}
			else {
				$GLOBALS['VendorName'] = "";
			}

			// generate actions for this template
			$types = explode(",", $row['usedtypes']);
			$options = "";

			// does user have permission to manage templates
			if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_ExportTemplates)) {
				// show copy option for built in templates
				if ($row['builtin']) {
					$options .= "<option value=\"edit\">" . GetLang("CreateCopy") . "</option>";
				}
				// show edit and delete options for other templates
				else {
					$options .= "<option value=\"edit\">" . GetLang("EditThisTemplate") . "</option>";
					$options .= "<option value=\"delete\">" . GetLang("Delete") . "</option>";
				}
			}

			foreach ($types as $type) {
				// does user have permission to export this type?
				$filetype = ISC_ADMIN_EXPORTFILETYPE_FACTORY::GetExportFileType($type);
				if ($filetype->HasPermission()) {
					$details = $filetype->GetTypeDetails();
					$options .= "<option value=\"" . $type . "\">" . sprintf(GetLang("RunExport"), $details['title']) . "</option>";
				}
			}

			$GLOBALS['TemplateActions'] = $options;

			$templateGridData .= $this->template->render('exporttemplates.manage.grid.row.tpl');
		}

		$GLOBALS['ExportTemplateGridData'] = $templateGridData;

		// Generate and return the grid
		return $this->template->render('exporttemplates.manage.grid.tpl');
	}

	/**
	* Generates a html select box containing vendors
	*
	* @param int $selectedVendor Optional vendor to select by default
	*/
	private function BuildVendorSelect($selectedVendor = 0)
	{

		$options = '<select name="vendor" id="vendor" class="Field200">
		<option value="0">'.GetLang('NoVendor').'</option>';
		$query = "
			SELECT vendorid, vendorname
			FROM [|PREFIX|]vendors
			ORDER BY vendorname ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$sel = '';
			if($selectedVendor == $vendor['vendorid']) {
				$sel = 'selected="selected"';
			}
			$options .= '<option value='.(int)$vendor['vendorid'].' '.$sel.'>'.isc_html_escape($vendor['vendorname']).'</option>';
		}

		$options .= "</select>";

		return $options;
	}

	/**
	* Displays the Create Template page
	*
	*/
	private function CreateTemplate($loadFromPost = false)
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$GLOBALS['TemplateId'] = 0;

		$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndAddAnother');
		$GLOBALS['FormAction'] = "saveExportTemplate";
		$GLOBALS['CancelMessage'] = GetLang("CancelCreateTemplate");
		$GLOBALS['TemplateTitle'] = GetLang("AddExportTemplate");
		$GLOBALS['Vendor'] = 0;

		if ($loadFromPost) {
			// reload posted data
			$this->SetGlobalsFromPost();
		}
		else {
			$GLOBALS['DateFormats'] = $this->FormatArray($this->GetDateFormats());
			$GLOBALS['PriceFormats'] = $this->FormatArray($this->GetPriceFormats());
			$GLOBALS['BoolFormats'] = $this->FormatArray($this->GetBoolFormats());

			// build tabs and grids for each type
			$types = $this->SetTypeData();

			// get any settings for each export method
			$ret = $this->BuildSettings($this->GetMethodSettings());
			$GLOBALS['Settings'] = $ret['html'];
			$GLOBALS['VerifyJS'] .= $ret['js'];
		}

		$GLOBALS['HideVendorRow'] = 'style="display: none;"';

		$this->template->display('exporttemplates.form.tpl');
	}

	private function GetMethodSettings($templateid = 0)
	{
		$settings = array();
		//$html = "";

		// get a list of each method
		$methods = ISC_ADMIN_EXPORTMETHOD_FACTORY::GetExportMethodList();
		foreach ($methods as $file => $details) {
			$method = ISC_ADMIN_EXPORTMETHOD_FACTORY::GetExportMethod($details['name']);

			if ($method->HasSettings()) {
				$settings[$details['name']] = $method->GetSettings($templateid);
			}
		}

		return $settings;
	}

	/**
	* Sets the global variables for methods based on the setting type
	*
	* @param mixed $settings
	*/
	private function BuildSettings($settings)
	{
		$js = "";
		$html = "";

		foreach ($settings as $method => $method_settings) {

			foreach ($method_settings as $id => $setting) {
				$value = $setting['value'];

				switch ($setting['type']) {
					case "text":
						$value = isc_html_escape($value);
						break;
					case "checkbox":
						if ($value) {
							$value = "checked=\"checked\"";
						}
						break;
					case "select":
						if (in_array($value, $setting['options'])) {
							$id = $value . "Selected";
							$value = "selected=\"selected\"";
						}
						break;
				}

				$GLOBALS['Setting' . $id] = $value;
			}

			$html .= $this->template->render('exporttemplates.settings.'.strtolower($method).'.tpl');

			$js .= "
			if (!Validate" . $method . "()) {
				return false;
			}";
		}

		return array("html" => $html, "js" => $js);
	}

	/**
	* Loads data into the settings from post
	*
	*/
	private function LoadSettingsFromPost()
	{
		$settings = $this->GetMethodSettings();
		$new_settings = $settings;

		foreach ($settings as $method => $method_settings) {
			foreach ($method_settings as $id => $setting) {

				switch ($setting['type']) {
					case "checkbox":
						$value = isset($_POST[$method][$id]);
						break;
					default:
						$value = $_POST[$method][$id];
						break;
				}

				$new_settings[$method][$id]['value'] = $value;
			}
		}

		return $new_settings;
	}

	/**
	* Saves posted setting data for the template
	*
	* @param mixed $templateid
	*/
	private function ProcessSettings($templateid)
	{
		$settings = $this->GetMethodSettings();

		foreach ($settings as $method => $method_settings) {
			foreach ($method_settings as $id => $setting) {
				if ((isset($setting['required']) && $setting['required']) && (!isset($_POST[$method][$id]) || !$_POST[$method][$id])) {
					// missing setting, error
					throw new Exception(sprintf(GetLang("SettingMissing"), GetLang($id)));
				}

				switch ($setting['type']) {
					case "checkbox":
						$value = (int)isset($_POST[$method][$id]);
						break;
					default:
						$value = $_POST[$method][$id];
						break;
				}

				$setting_array = array(
					"methodname" 		=> $method,
					"exporttemplateid" 	=> $templateid,
					"variablename"		=> $id,
					"variablevalue"		=> $value
				);

				// check if setting exists
				$query = "SELECT * FROM [|PREFIX|]export_method_settings WHERE exporttemplateid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($templateid) . "' AND methodname = '" . $method . "' AND variablename = '" . $id . "'";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

					// field exists, update the existing one
					$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('export_method_settings', $setting_array, "exportmethodid = '" . $row['exportmethodid'] . "'");
					if (!$result) {
						throw new Exception(sprintf(GetLang("FailedToUpdateSetting"), $setting['label']));
					}
				}
				else {
					// insert new field
					$exportmethodid = $GLOBALS['ISC_CLASS_DB']->InsertQuery('export_method_settings', $setting_array);
					if (!isId($exportmethodid)) {
						throw new Exception(sprintf(GetLang("FailedToAddSetting"), $setting['label']));
					}
				}
			}
		}
	}

	/**
	* Displays the Edit Template form
	*
	*/
	private function EditTemplate($loadFromPost = false, $templateid = 0)
	{
		 try {
			// no template supplied, 404
			if (!$templateid) {
				if (!isset($_GET["tempId"])) {
					throw new Exception(GetLang("NoTemplateId"));
				}

				$templateid = $_GET["tempId"];
			}

			$template = $this->GetTemplate($templateid);
		}
		catch (Exception $ex) {
			FlashMessage($ex->getMessage(), MSG_ERROR, 'index.php?ToDo=viewExportTemplates');
		}

		$GLOBALS['TemplateId'] = $templateid;
		$GLOBALS['FormAction'] = "updateExportTemplate";
		$GLOBALS['TemplateTitle'] = GetLang("EditTemplateTitle");
		$GLOBALS['CancelMessage'] = GetLang("CancelEditTemplate");
		$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndContinueEditing');
		$GLOBALS['hiddenFields'] = sprintf("<input type='hidden' name='tempId' value='%d'>", $templateid);

		$GLOBALS['Vendor'] = $template['vendorid'];

		if (isset($_GET["tab"])) {
			$GLOBALS['ShowTabScript'] = "ShowTab(" . (int)$_GET["tab"] . ");";
		}

		if ($loadFromPost) {
			// reload posted data
			$this->SetGlobalsFromPost();
		}
		else {
			// load template settings
			$GLOBALS['ExportTemplateName'] = isc_html_escape($template['exporttemplatename']);

			$GLOBALS['AssetAccount'] = isc_html_escape($template['myobassetaccount']);
			$GLOBALS['IncomeAccount'] = isc_html_escape($template['myobincomeaccount']);
			$GLOBALS['ExpenseAccount'] = isc_html_escape($template['myobexpenseaccount']);

			$GLOBALS['ReceivableAccount'] = isc_html_escape($template['peachtreereceivableaccount']);
			$GLOBALS['GLAccount'] = isc_html_escape($template['peachtreeglaccount']);
			if ($template['modifyforpeachtree']) {
				$GLOBALS['ModifyForPeachtree'] = "checked=\"checked\"";
			}

			$GLOBALS['DateFormats'] = $this->FormatArray($this->GetDateFormats(), $template['dateformat']);
			$GLOBALS['PriceFormats'] = $this->FormatArray($this->GetPriceFormats(), $template['priceformat']);
			$GLOBALS['BoolFormats'] = $this->FormatArray($this->GetBoolFormats(), $template['boolformat']);

			if ($template['blankforfalse']) {
				$GLOBALS['BlankForFalseChecked'] = "checked=\"checked\"";
			}

			if ($template['striphtml']) {
				$GLOBALS['StripHTMLChecked'] = "checked=\"checked\"";
			}

			$usedTypes = explode(",", $template['usedtypes']);

			// grid fields
			$types = $this->SetTypeData($templateid, $usedTypes);

			// method settings
			$ret = $this->BuildSettings($this->GetMethodSettings($templateid));
			$GLOBALS['Settings'] = $ret['html'];
			$GLOBALS['VerifyJS'] .= $ret['js'];

			// notify if trying to edit a built-in template
			if ($template['builtin']) {
				$GLOBALS['FormAction'] = "saveExportTemplate";
				$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndAddAnother');
				FlashMessage(GetLang("BuiltInEdit"), MSG_ERROR);
			}
		}

		if(gzte11(ISC_HUGEPRINT) && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $GLOBALS['Vendor']) {
			$GLOBALS['VendorLabel'] = GetLang("VendorLabel");

			$query = "SELECT * FROM [|PREFIX|]vendors WHERE vendorid = '" . $GLOBALS['Vendor'] . "'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$vendorData = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if(isset($vendorData['vendorid'])) {
				$GLOBALS['VendorData'] = isc_html_escape($vendorData['vendorname']);
			}
		}
		else {
			$GLOBALS['HideVendorRow'] = 'style="display: none;"';
		}


		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$this->template->display('exporttemplates.form.tpl');
	}

	/**
	* Retrieves a template record from the database
	*
	* @param int $templateid The template to get
	* @return array The template record
	*/
	public function GetTemplate($templateid)
	{
		$where = "";
		if(gzte11(ISC_HUGEPRINT)) {
			$GLOBALS['VendorLabel'] = GetLang("VendorLabel");

			$vendorData = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendor();
			if(isset($vendorData['vendorid'])) {
				$where = "AND (et.vendorid = '" . $vendorData['vendorid'] . "' OR builtin = 1)";
			}
		}

		// retrieve the template
		$query = "
			SELECT
				et.*
			FROM
				[|PREFIX|]export_templates et
			WHERE
				exporttemplateid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($templateid) . "'";

		$query .= $where;

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if (!$template = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// template not found
			throw new Exception(GetLang("TemplateNotFound"));
		}

		return $template;
	}

	/**
	* Assigns the GLOBAL variables for use in design template using the POST data.
	* ie. for when the form is displayed after a save action and an error is generated
	*
	*/
	private function SetGlobalsFromPost()
	{
		$GLOBALS['ShowTabScript'] = "ShowTab(" . (int)$_POST["currentTab"] . ");";

		$GLOBALS['ExportTemplateName'] = isc_html_escape($_POST['templateName']);

		$GLOBALS['AssetAccount'] = isc_html_escape($_POST['assetAccount']);
		$GLOBALS['IncomeAccount'] = isc_html_escape($_POST['incomeAccount']);
		$GLOBALS['ExpenseAccount'] = isc_html_escape($_POST['expenseAccount']);

		$GLOBALS['ReceivableAccount'] = isc_html_escape($_POST['receivableAccount']);
		$GLOBALS['GLAccount'] = isc_html_escape($_POST['glAccount']);
		if (isset($_POST['modifyForPeachtree'])) {
			$GLOBALS['ModifyForPeachtree'] = "checked=\"checked\"";
		}

		$GLOBALS['DateFormats'] = $this->FormatArray($this->GetDateFormats(), $_POST['dateFormat']);
		$GLOBALS['PriceFormats'] = $this->FormatArray($this->GetPriceFormats(), $_POST['priceFormat']);
		$GLOBALS['BoolFormats'] = $this->FormatArray($this->GetBoolFormats(), $_POST['boolFormat']);

		if (isset($_POST['blankForFalse'])) {
			$GLOBALS['BlankForFalseChecked'] = "checked=\"checked\"";
		}

		if (isset($_POST['stripHTML'])) {
			$GLOBALS['StripHTMLChecked'] = "checked=\"checked\"";
		}

		// grid fields
		$usedTypes = array();
		if (isset($_POST['includeType'])) {
			$usedTypes = array_keys($_POST['includeType']);
		}
		$types = $this->SetTypeData(0, $usedTypes, true);

		$ret = $this->BuildSettings($this->LoadSettingsFromPost());
		$GLOBALS['Settings'] = $ret['html'];
		$GLOBALS['VerifyJS'] .= $ret['js'];
	}

	/**
	* Validates the posted form data
	*
	* @param int $templateid The template used when checking for existing template name
	*/
	private function ValidateInput($templateid = 0)
	{
		// check for template name
		if (!isset($_POST["templateName"]) || !trim($_POST["templateName"])) {
			throw new Exception(GetLang("NoTemplateName"));
		}
		else {
			$templatename = trim($_POST["templateName"]);

			// check for existing template
			$query = "
				SELECT *
				FROM [|PREFIX|]export_templates
				WHERE builtin = 0 AND exporttemplatename = '" . $GLOBALS['ISC_CLASS_DB']->Quote($templatename) . "'
			";
			if ($templateid) {
				$query .= " AND exporttemplateid != '" . $GLOBALS['ISC_CLASS_DB']->Quote($templateid) . "'";
			}

			$vendorid = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();

			$query .= " AND vendorid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($vendorid) . "'";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
				throw new Exception(sprintf(GetLang("TemplateAlreadyExists"), $templatename));
			}
		}

		// ensure at least one file is selected
		if (!isset($_POST["includeType"])) {
			throw new Exception(GetLang("NoFilesSelected"));
		}

		// check for valid date format
		if (!array_key_exists($_POST['dateFormat'], $this->GetDateFormats())) {
			throw new Exception(GetLang("NoDateFormat"));
		}

		// check for valid price format
		if (!array_key_exists($_POST['priceFormat'], $this->GetPriceFormats())) {
			throw new Exception(GetLang("NoPriceFormat"));
		}

		// check for valid bool format
		if (!array_key_exists($_POST['boolFormat'], $this->GetBoolFormats())) {
			throw new Exception(GetLang("NoBoolFormat"));
		}

		// validate each type
		foreach ($_POST['includeType'] as $type => $blah) {
			// check that at least one field is checked for the type
			if (!isset($_POST[$type . "Field"])) {
				throw new Exception(sprintf(GetLang("NoFields"), $type));
			}

			// check that ticked fields have a header
			$filetype = ISC_ADMIN_EXPORTFILETYPE_FACTORY::GetExportFileType($type);
			$fields = $filetype->FlattenFields($filetype->LoadFields());
			foreach ($_POST[$type . "Field"] as $field => $val) {
				if (!isset($_POST[$type . "Header"][$field]) || !trim($_POST[$type . "Header"][$field])) {
					throw new Exception(GetLang("FieldNoHeader") . '"' . $fields[$field]['label'] . '"');
				}
			}
		}
	}

	/**
	* Saves a new template and either returns to the view templates list or shows the create form again
	*
	*/
	private function SaveTemplate()
	{
		$transaction_started = false;

		try {
			// validate input
			$this->ValidateInput();

			$useHeaders = false;
			if (isset($_POST['includeHeaders'])) {
				$useHeaders = true;
			}

			// begin template creation transaction
			$GLOBALS['ISC_CLASS_DB']->StartTransaction();
			$transaction_started = true;

			// create our template
			$templateid = $this->CommitTemplate();

			// save the fields
			$this->ProcessFields($templateid, $useHeaders);

			// save method settings
			$this->ProcessSettings($templateid);

			// commit transaction
			$GLOBALS['ISC_CLASS_DB']->CommitTransaction();
		}
		catch (Exception $ex) {
			// rollback transaction
			if ($transaction_started) {
				$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
			}

			// show error
			FlashMessage($ex->getMessage(), MSG_ERROR); //, 'index.php?ToDo=createExportTemplate');
			$this->CreateTemplate(true);

			return;
		}

		if(isset($_POST['AddAnother'])) {
			$location = 'index.php?ToDo=createExportTemplate';
		}
		else {
			$location= 'index.php?ToDo=viewExportTemplates';
		}

		// Log this action
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($templateid, $_POST['templateName']);

		FlashMessage(sprintf(GetLang('TemplateSavedSuccessfully'), isc_html_escape($_POST['templateName'])), MSG_SUCCESS, $location);
	}

	/**
	* Updates an existing template
	*
	*/
	private function UpdateTemplate()
	{
		$templateid = 0;
		$transaction_started = false;

		try {
			if (!isset($_POST["tempId"])) {
				throw new Exception(GetLang("NoTemplateId"));
			}

			$templateid = $_POST["tempId"];

			$template = $this->GetTemplate($templateid);

			if ($template['builtin']) {
				throw new Exception(GetLang("CannotSaveBuiltin"));
			}

			$useHeaders = false;
			if (isset($_POST['includeHeaders'])) {
				$useHeaders = true;
			}

			// validate input
			$this->ValidateInput($templateid);

			// begin template creation transaction
			$GLOBALS['ISC_CLASS_DB']->StartTransaction();

			$transaction_started = true;

			// commit the template
			$this->CommitTemplate($templateid);

			 // add fields for Order, Product and Customer
			$this->ProcessFields($templateid, $useHeaders);

			// save method settings
			$this->ProcessSettings($templateid);

			// commit transaction
			$GLOBALS['ISC_CLASS_DB']->CommitTransaction();
		}
		catch (Exception $ex) {
			// rollback transaction
			if ($transaction_started) {
				$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
			}

			// show error
			FlashMessage($ex->getMessage(), MSG_ERROR);
			$this->EditTemplate(true, $templateid);

			return;
		}

		if(isset($_POST['AddAnother'])) {
			$location = 'index.php?ToDo=editExportTemplate&tempId=' . $templateid . "&tab=" . $_POST["currentTab"];
		}
		else {
			$location= 'index.php?ToDo=viewExportTemplates';
		}

		// Log this action
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($templateid, $_POST['templateName']);

		FlashMessage(sprintf(GetLang('TemplateUpdatedSuccessfully'), isc_html_escape($_POST['templateName'])), MSG_SUCCESS, $location);
	}

	/**
	* Deletes selected templates then redirects back to manage templates page
	*
	*/
	private function DeleteTemplate()
	{
		$delcount = 0;

		try {
			// delete single template
			if (isset($_GET['tempId'])) {
				$tempId = (int)$_GET['tempId'];
				$template = $this->GetTemplate($tempId);
				// check if this template is built-in
				if ($template['builtin']) {
					throw new Exception(sprintf(GetLang("DeleteBuiltIn"), $template['exporttemplatename']));
				}

				$this->DeleteThisTemplate($tempId);

				$delcount = 1;
			}
			else { // delete multiple templates
				if (!isset($_POST["exporttemplates"]) || !is_array($_POST["exporttemplates"])) {
					throw new Exception(GetLang("NoTemplateId"));
				}

				foreach ($_POST["exporttemplates"] as $templateid => $val) {
					$template = $this->GetTemplate($templateid);
					// check if this template is built-in
					if ($template['builtin']) {
						continue; //skip built in templates
					}

					$templateid = $GLOBALS['ISC_CLASS_DB']->Quote($templateid);

					$this->DeleteThisTemplate($templateid);

					$delcount++;
				}
			}
		}
		catch (Exception $ex) {
			// log the error

			// show error
			FlashMessage($ex->getMessage(), MSG_ERROR, 'index.php?ToDo=viewExportTemplates');
			return;
		}

		if ($delcount) {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($delcount);

			FlashMessage(GetLang("TemplateDeletedSuccessfully"), MSG_SUCCESS, 'index.php?ToDo=viewExportTemplates');
		}
		else {
			$this->HandleToDo("viewexporttemplates");
		}
	}

	public function DeleteThisTemplate($templateid)
	{
		// delete the template
		$query = "DELETE FROM [|PREFIX|]export_templates WHERE exporttemplateid = '" . $templateid . "'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->NumAffected()) {
			throw new Exception("Template was not deleted");
		}

		// delete the template fields
		$query = "DELETE FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = '" . $templateid . "'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		// delete method settings
		$query = "DELETE FROM [|PREFIX|]export_method_settings WHERE exporttemplateid = '" . $templateid . "'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	}

	private function ProcessFields($templateid, $useHeaders)
	{
		$filetypes = ISC_ADMIN_EXPORTFILETYPE_FACTORY::GetExportFileTypeList();
		foreach ($filetypes as $file => $details) {
			$this->ProcessFieldsForType($details['name'], $templateid, $useHeaders);
		}
	}

	/**
	* Processes the posted fields for a field type  and inserts or updates DB records
	*
	* @param string $fieldType The type of fields being processed - order, product or customer
	* @param int $templateid The template of the fields
	* @param bool $useHeaders Whether the Use Header Line option was ticked. Checks for missing header data in selected fields
	*/
	private function ProcessFieldsForType($fieldType, $templateid, $useHeaders)
	{
		$type = ISC_ADMIN_EXPORTFILETYPE_FACTORY::GetExportFileType($fieldType);
		$fields = $type->FlattenFields($type->LoadFields());

		$keys = array_keys($_POST[$fieldType . "Header"]);

		// ensure posted field array is valid
		if (!isset($_POST[$fieldType . "Field"]) || !is_array($_POST[$fieldType . "Field"])) {
			throw new Exception(sprintf(GetLang("FieldsNotPosted"), $fieldtype));
		}

		// process the fields
		foreach ($fields as $id => $field) {
			$header = "";
			$used = isset($_POST[$fieldType . "Field"][$id]);

			$header = $_POST[$fieldType . 'Header'][$id];

			$field_array = array(
				"exporttemplateid"	=> $templateid,
				"fieldid"			=> $id,
				"fieldtype"			=> $fieldType,
				"fieldname"			=> $header,
				"includeinexport"	=> (int)$used,
				"sortorder"			=> array_search($id, $keys)
			);

			// check if field exists
			$query = "SELECT * FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($templateid) . "' AND fieldid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($id) . "'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
				// field exists, update the existing one
				$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('export_template_fields', $field_array, "exporttemplateid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($templateid) . "' AND fieldid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($id) . "'");
				if (!$result) {
					throw new Exception(sprintf(GetLang("FailedToUpdateField"), $field['label']));
				}
			}
			else {
				// insert new field
				$expotempfieldid = $GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $field_array);
				if (!isId($expotempfieldid)) {
					throw new Exception(sprintf(GetLang("FailedToAddField"), $field['label']));
				}
			}
		}
	}

	/**
	* Creates or updates a template from posted data
	*
	* @return int $id The ID of the new template
	*/
	private function CommitTemplate($templateid = 0)
	{
		$vendorid = 0;

		$vendorid = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();

		// which file types the user has selected
		$usedTypes = array();
		if (isset($_POST['includeType'])) {
			$usedTypes = implode(",", array_keys($_POST['includeType']));
		}

		$array = array(
			"exporttemplatename"			=> $_POST["templateName"],
			"myobassetaccount"				=> $_POST['assetAccount'],
			"myobincomeaccount"				=> $_POST['incomeAccount'],
			"myobexpenseaccount"			=> $_POST['expenseAccount'],
			"peachtreereceivableaccount"	=> $_POST['receivableAccount'],
			"peachtreeglaccount"			=> $_POST['glAccount'],
			"modifyforpeachtree"			=> (int)isset($_POST['modifyForPeachtree']),
			"dateformat"					=> $_POST['dateFormat'],
			"priceformat"					=> $_POST['priceFormat'],
			"boolformat"					=> $_POST['boolFormat'],
			"blankforfalse"					=> (int)isset($_POST['blankForFalse']),
			"striphtml"						=> (int)isset($_POST['stripHTML']),
			"vendorid"						=> $vendorid,
			"usedtypes"						=> $usedTypes,
			"builtin"						=> 0
		);

		if ($templateid) {
			// update template
			$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery("export_templates", $array, "exporttemplateid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($templateid) . "'");
			if (!$result) {
				throw new Exception(sprintf(GetLang('TemplateNotUpdated'), isc_html_escape($_POST['templateName'])));
			}
		}
		else {
			// new template
			$templateid = $GLOBALS['ISC_CLASS_DB']->InsertQuery("export_templates", $array);

			if (!isId($templateid)) {
				throw new Exception(sprintf(GetLang('TemplateNotCreated'), isc_html_escape($_POST['templateName'])));
			}
		}

		return $templateid;
	}


	/**
	* Sets the template globals to create a file type (tab, grid, used check box, javascript)
	*
	* @param mixed $templateid
	* @param mixed $usedTypes
	* @param mixed $load_from_post
	*/
	private function SetTypeData($templateid = 0, $usedTypes = array(), $load_from_post = false)
	{
		// get a list of available file types
		$filetypes = ISC_ADMIN_EXPORTFILETYPE_FACTORY::GetExportFileTypeList();
		$x = 2;
		$gridData = "";
		$tabData = "";
		$listJS = "";
		$includeData = "";
		$includeJS = "";
		$verifyJS = "";

		if ($templateid == 0 && !$load_from_post) {
			$use_defaults = true;
		}
		else {
			$use_defaults = false;
		}

		foreach ($filetypes as $file => $details) {
			// get the fields for this type
			$type = ISC_ADMIN_EXPORTFILETYPE_FACTORY::GetExportFileType($details['name']);
			$use_id = $templateid;
			if ($load_from_post) {
				$use_id = 0;
			}
			$fields = $type->LoadFields($use_id);
			if ($load_from_post) {
				$fields = $this->LoadFieldsFromPost($fields, $details['name']);
			}

			$GLOBALS['FileIndex'] = $x;

			if ($use_defaults || in_array($details['name'], $usedTypes)) {
				$GLOBALS['IncludeChecked'] = "checked=\"checked\"";
				$GLOBALS['TabDisplay'] = "";
			}
			else {
				$GLOBALS['IncludeChecked'] = "";
				$GLOBALS['TabDisplay'] = "style=\"display: none;\"";
			}

			// create a tab for the type
			$GLOBALS['TypeTitle'] = $details['title'];
			$tabData .= "\n" . $this->template->render('exporttemplates.form.tab.tpl');

			// get the grid data
			$ret = $this->GetFieldGrid($details['name'], $fields, $use_defaults);
			$GLOBALS['GridData'] = $ret["gridData"];
			$GLOBALS['TypeName'] = $details['name'];
			$GLOBALS['FieldGrid'] = $this->template->render('exporttemplates.form.grid.tpl');
			$GLOBALS['FileIndex'] = $x;
			$GLOBALS['TypeDisplay'] = "display: none;";
			$GLOBALS['TypeWidth'] = "";
			$gridData .= "\n\n" . $this->template->render('exporttemplates.form.type.tpl');

			$listJS .= $ret["listJS"];

			// generate a check box for this file type
			$GLOBALS['IncludeTypeLabel'] = sprintf(GetLang("AllowType"), $details['title']);
			$GLOBALS['IncludeType'] = $details['name'];
			$GLOBALS['YesIncludeType'] = sprintf(GetLang("YesAllowType"), isc_strtolower($details['title']));
			$includeData .= "\n" . $this->template->render('exporttemplates.form.includetype.tpl');

			// javascript for the checkbox to hide the tab
			$includeJS .= "
			 $(\"#include" . $details['name'] . "\").change(function() {
				if (this.checked)
					$(\"#tab" . $x . "\").animate({ opacity: \"show\", color: \"green\" }, \"slow\").animate({color: \"#666\"}, \"medium\");
				else
					$(\"#tab" . $x . "\").fadeOut(\"fast\");
			});\n";


			// type verification js
			$verifyJS .= "
			if (!VerifyList('" . $details['name'] . "', " . $x . ")) {
				return false;
			}
			";

			$x++;
		}

		$GLOBALS['TypeTabs'] = $tabData;
		$GLOBALS['TypeGrids'] = $gridData;
		$GLOBALS['CreateLists'] = $listJS;
		$GLOBALS['IncludeTypes'] = $includeData;
		$GLOBALS['IncludeJS'] = $includeJS;
		$GLOBALS['VerifyJS'] = $verifyJS;
	}

	/**
	* Generates a grid for entering CSV header and data format values
	*
	* @param string $fieldType The type of fields being built - order, product or customer
	* @param array $fields An array of fields to be used when generating the grid
	* @param bool $use_defaults Use default values for rows or use values in the array loaded from a template
	* @return string A html snippet containing the grid of fields
	*/
	private function GetFieldGrid($fieldType, $fields, $use_defaults = false, $setType = true, $depth = 0)
	{
		$gridData = "";

		// javascript snippet to create the sortable list
		$listJS = "\nCreateSortableList('" . $fieldType . "');";

		if ($setType) {
			$GLOBALS['FieldType'] = $fieldType;
		}

		$GLOBALS['TypeName'] = $fieldType;

		foreach ($fields as $id => $field) {
			// does this field have sub-fields?
			if (isset($field['fields'])) {
				// create a grid for the sub-fields
				$typeName = $id . "_" . $fieldType;
				$ret = $this->GetFieldGrid($typeName, $field['fields'], $use_defaults, false, $depth + 1);
				$GLOBALS['GridData'] = $ret["gridData"];
				$listJS .= $ret["listJS"];

				$GLOBALS['SubFields'] = $this->template->render('exporttemplates.form.grid.tpl');
				$GLOBALS['CheckAlign'] = "vertical-align: top;";
			}
			else {
				$GLOBALS['SubFields'] = "";
				$GLOBALS['CheckAlign'] = "";
			}

			if ($depth) {
				$GLOBALS['CheckColWidth'] = 45;
				$GLOBALS['NodeJoin'] = '<img src="images/nodejoin.gif" alt="" style="vertical-align: middle;"/>';
			}
			else {
				$GLOBALS['CheckColWidth'] = 25;
				$GLOBALS['NodeJoin'] = "";
			}

			$GLOBALS['TypeName'] = $fieldType;
			$GLOBALS['FieldID'] = $id;
			$GLOBALS['FieldLabel'] = $field['label'];
			$GLOBALS['FieldChecked'] = "";
			$GLOBALS['FieldReadOnly'] = "";
			$GLOBALS['FieldHelp'] = "";

			// set default header to the column label for a new template
			if ($use_defaults) {
				$GLOBALS['FieldHeader'] = $field['label'];
				$GLOBALS['FieldChecked'] = "checked=\"checked\"";
				$GLOBALS['FieldClass'] = "";
			}
			else {
				$GLOBALS['FieldHeader'] = $field['header'];
				if ($field['used']) {
					$GLOBALS['FieldClass'] = "";
					$GLOBALS['FieldLabelClass'] = "";
					$GLOBALS['FieldChecked'] = "checked=\"checked\"";
				}
				else {
					$GLOBALS['FieldClass'] = "FieldDisabled";
					$GLOBALS['FieldLabelClass'] = "FieldLabelDisabled";
					$GLOBALS['FieldReadOnly'] = "readonly=\"readonly\"";
				}
			}

			// does this field have a help tip?
			if (isset($field["help"])) {
				$help = "<img onmouseout=\"HideHelp('help" . $id . "');\" onmouseover=\"ShowHelp('help" . $id . "', '" . $field['label'] . "', '" . $field['help'] . "');\" src=\"images/help.gif\" width=\"24\" height=\"16\" border=\"0\">";
				$help .= "\n<div style=\"display:none\" id=\"help" . $id . "\"></div>";
				$GLOBALS['FieldHelp'] = $help;
			}

			// render the row
			$gridData .= "\n" . $this->template->render('exporttemplates.form.grid.row.tpl');
		}

		return array("gridData" => $gridData, "listJS" => $listJS);
	}

	private function LoadFieldsFromPost($fields, $type)
	{
		$keys = array_keys($_POST[$type . "Header"]);

		$new_fields = $fields;

		foreach ($keys as $fieldid) {
			$data = array(
				'header' 	=> $_POST[$type . "Header"][$fieldid],
				'used' 		=> isset($_POST[$type . "Field"][$fieldid]),
				'sortorder'	=> array_search($fieldid, $keys)
			);

			$this->SetFieldData($new_fields, $fieldid, $data);
		}

		// sort the field array by the sort order
		uasort($new_fields, array(&$this, "compare_fields"));

		return $new_fields;
	}

	private function SetFieldData(&$fields, $fieldid, $data)
	{
		if (isset($fields[$fieldid])) {
			foreach ($data as $col => $value) {
				$fields[$fieldid][$col] = $value;
			}
		}
		else {
			foreach ($fields as $id => &$field) {
				if (isset($field['fields'])) {
					$this->SetFieldData($field['fields'], $fieldid, $data);
				}
			}
		}
	}

	/**
	* Compares two fields sort order to determine their position in the list of fields
	*
	* @param array $field1
	* @param array $field2
	*/
	private function compare_fields($field1, $field2)
	{
		if ($field1["sortorder"] < $field2["sortorder"]) {
			return -1;
		}
		else {
			return 1;
		}
	}


	public function GetDateFormats()
	{
		$formats = array(
			"mdy-slash"			=> array("example"	=> "05/19/2008", "format"	=> "m/d/Y"),
			"mdy-slash-short"	=> array("example"	=> "05/19/08", "format"	=> "m/d/y"),
			"mdy-dash"			=> array("example"	=> "05-19-2008", "format"	=> "m-d-Y"),
			"mdy-dash-short"	=> array("example"	=> "05-19-08", "format"	=> "m-d-y"),
			"dmy-slash"			=> array("example"	=> "19/05/2008", "format"	=> "d/m/Y"),
			"dmy-slash-short"	=> array("example"	=> "19/05/08", "format"	=> "d/m/y"),
			"dmy-dash"			=> array("example"	=> "19-05-2008", "format"	=> "d-m-Y"),
			"dmy-dash-short"	=> array("example"	=> "19-05-08", "format"	=> "d-m-y"),
		);

		return $formats;
	}

	public function GetBoolFormats()
	{
		$formats = array(
			"onezero"	=> array("example" => GetLang("OneOrZero")),
			"truefalse"	=> array("example" => GetLang("TrueOrFalse")),
			"yesno"		=> array("example" => GetLang("YesOrNo")),
			"yn"		=> array("example" => GetLang("YOrN"))
		);

		return $formats;
	}

	private function GetPriceFormats()
	{
		 SetupCurrency();

		$currency = GetDefaultCurrency();

		$price = number_format(1543.987, $currency['currencydecimalplace'], $currency['currencydecimalstring'], '');

		$formats = array(
				"number" => $price,
				"formatted" => FormatPriceInCurrency(1543.987)
		);

		return $formats;
	}

	/**
	* Gets an HTML set of <option>'s using an array of formats
	*
	* @param array $formats An array of valid formats in the form (type => format)
	* @param string $select Optional format type to select by default
	* @return string A string of <option> tags of the formats
	*/
	private function FormatArray($formats, $select = "")
	{
		$format_str = "";

		foreach ($formats as $type => $format) {
			if (is_array($format)) {
				$label = $format["example"];
			}
			else {
				$label = $format;
			}

			if ($type == $select) {
				$item_select = " selected=\"selected\"";
			}
			else {
				$item_select = "";
			}

			$format_str .= "<option value=\"$type\"" . $item_select . ">" . $label . "</option>";
		}

		return $format_str;
	}
}