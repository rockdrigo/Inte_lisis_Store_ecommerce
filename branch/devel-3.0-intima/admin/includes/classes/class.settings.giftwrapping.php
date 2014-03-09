<?php
/**
 * Gift wrapping system management
 */
class ISC_ADMIN_SETTINGS_GIFTWRAPPING extends ISC_ADMIN_BASE
{
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings.giftwrapping');
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
			GetLang('Settings') => "index.php?ToDo=viewSettings",
			GetLang('GiftWrappingSettings') => "index.php?ToDo=viewGiftWrapping"
		);
		switch(isc_strtolower($Do))
		{
			case "editgiftwrap":
				$GLOBALS['BreadcrumEntries'][GetLang('EditGiftWrap')] = '';
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->EditGiftWrap();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "saveupdatedgiftwrap":
				$GLOBALS['BreadcrumEntries'][GetLang('EditGiftWrap')] = '';
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->SaveUpdatedGiftWrap();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "addgiftwrap":
				$GLOBALS['BreadcrumEntries'][GetLang('AddGiftWrap')] = '';
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->AddGiftWrap();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "savenewgiftwrap":
				$GLOBALS['BreadcrumEntries'][GetLang('AddGiftWrap')] = '';
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->SaveNewGiftWrap();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			case "deletegiftwrap":
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->DeleteGiftWrap();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			default:
				if(!isset($_REQUEST['ajax'])) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				}
				$this->ManageGiftWrapping();
				if(!isset($_REQUEST['ajax'])) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				}
				break;
		}
	}

	/**
	 * Generate a grid containing the different types of configured gift wrapping.
	 *
	 * @param int The number of gift wrapping types (passed by reference)
	 * @return string the HTML for the grid of gift wrapping types.
	 */
	private function ManageGiftWrappingGrid(&$numGiftWrap)
	{
		$page = 0;
		$start = 0;
		$numGiftWrap = 0;
		$GLOBALS['GiftWrapGrid'] = '';
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
			$start = ($page * ISC_GIFTWRAP_PER_PAGE) - (ISC_GIFTWRAP_PER_PAGE);
		}

		// Fetch the list of available gift wrapping
		$query = "SELECT COUNT(wrapid) FROM [|PREFIX|]gift_wrapping";
		$numGiftWrap = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);

		// If there aren't any wrapping papers set up, just return nothing here
		if($numGiftWrap == 0) {
			return '';
		}

		$validSortFields = array(
			'wrapname',
			'wrapprice',
			'wrapvisible'
		);

		if(isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == "asc") {
			$sortOrder = "asc";
		}
		else {
			$sortOrder = "desc";
		}

		if(isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
			$sortField = $_REQUEST['sortField'];
			SaveDefaultSortField("ManageGiftWrapping", $_REQUEST['sortField'], $sortOrder);
		} else {
			list($sortField, $sortOrder) = GetDefaultSortField("ManageGiftWrapping", "wrapname", $sortOrder);
		}

		$numPages = ceil($numGiftWrap / ISC_GIFTWRAP_PER_PAGE);

		// Add the "(Page x of n)" label
		if($numGiftWrap > ISC_GIFTWRAP_PER_PAGE) {
			$GLOBALS['Nav'] = "(".GetLang('Page')." ".$page." of ".$numPages.") &nbsp;&nbsp;&nbsp;";
			$pagingUrl =  "index.php?ToDo=viewGiftWrapping&sortOrder=".$sortOrder."&sortField=".$sortField;
			$GLOBALS['Nav'] .= BuildPagination($numGiftWrap, ISC_GIFTWRAP_PER_PAGE, $page, $pagingUrl);
		}
		else {
			$GLOBALS['Nav'] = "";
			$GLOBALS['HidePaging'] = 'display: none';
		}

		$sortLinks = array(
			"WrapName" => "wrapname",
			"WrapPrice" => "wrapprice",
			"WrapVisible" => "wrapvisible"
		);

		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewGiftWrapping&amp;page=".$page, $sortField, $sortOrder);

		// Start fetching out the actual wrapping types
		$query = "
			SELECT *
			FROM [|PREFIX|]gift_wrapping
			ORDER BY ".$sortField." ".$sortOrder."
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_GIFTWRAP_PER_PAGE);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while($wrap = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['WrapName'] = isc_html_escape($wrap['wrapname']);
			$GLOBALS['WrapId'] = $wrap['wrapid'];
			$GLOBALS['WrapPrice'] = FormatPrice($wrap['wrapprice']);
			if($wrap['wrapvisible'] == 1) {
				$GLOBALS['WrapVisibleImage'] = 'tick.gif';
			}
			else {
				$GLOBALS['WrapVisibleImage'] = 'cross.gif';
			}

			$GLOBALS['GiftWrapGrid'] .= $this->template->render('giftwrapping.manage.row.tpl');
		}
		return $this->template->render('giftwrapping.manage.grid.tpl');
	}

	/**
	 * Generate and display the gift wrapping page.
	 */
	public function ManageGiftWrapping()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		// Fetch any wrapping types and place them in a data grid
		$GLOBALS['GiftWrapDataGrid'] = $this->ManageGiftWrappingGrid($numGiftWrap);

		// Was this an ajax based sort? Return the table now
		if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
			echo $GLOBALS['GiftWrapDataGrid'];
			return;
		}

		// No gift wrapping types have been configured yet
		if($numGiftWrap == 0) {
			$GLOBALS['DisableDelete'] = 'disabled="disabled"';
			$GLOBALS['DisplayGrid'] = "none";
			$GLOBALS['Message'] = MessageBox(GetLang('NoGiftWrappingTypes'), MSG_SUCCESS);
		}

		$this->template->display('giftwrapping.manage.tpl');
	}

	/**
	 * Add a new type of gift wrapping.
	 */
	private function AddGiftWrap()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$GLOBALS['FormAction']		= 'SaveNewGiftWrap';
		$GLOBALS['Title']			= GetLang('AddGiftWrap');
		$GLOBALS['Intro']			= GetLang('AddGiftWrapIntro');
		$GLOBALS['HideCurrentWrapImage'] = 'display: none';

		if (GetConfig('CurrencyLocation') == 'right') {
			$GLOBALS['RightCurrencyToken'] = GetConfig('CurrencyToken');
		}
		else {
			$GLOBALS['LeftCurrencyToken'] = GetConfig('CurrencyToken');
		}

		$GLOBALS['GiftWrapAllowCommentsChecked'] = 'checked="checked"';
		$GLOBALS['GiftWrapVisibleChecked']		 = 'checked="checked"';

		$this->template->display('giftwrapping.form.tpl');
	}

	/**
	 * Save a new type of gift wrapping.
	 */
	private function SaveNewGiftWrap()
	{
		$message = '';
		if(!$this->ValidateWrap($_POST, $message)) {
			FlashMessage($message, MSG_ERROR);
			$this->AddGiftWrap();
			return;
		}

		if(!$this->CommitWrap($_POST)) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingGiftWrap').$error, MSG_ERROR);
			$this->AddGiftWrap();
			return;
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['wrapname']);
			FlashMessage(GetLang('GiftWrapCreated'), MSG_SUCCESS, 'index.php?ToDo=viewGiftWrap');
		}
	}

	/**
	 * Validate the passed input for a gift wrapping type for errors.
	 *
	 * @param array An array of data about the gift wrapping type.
	 * @param string An error message (if any) from validation errors (on return false - by reference)
	 * @return boolean True if successful, false if not.
	 */
	private function ValidateWrap($data, &$error)
	{
		$requiredFields = array(
			'wrapname' => GetLang('EnterGiftWrapName'),
			'wrapprice' => GetLang('EnterGiftWrapPrice')
		);
		foreach($requiredFields as $field => $message) {
			if(!isset($data[$field]) || trim($data[$field]) == '') {
				$error = $message;
				return false;
			}
		}

		// Does a wrapping type exist with the same name?
		$wrapIdQuery = '';
		if(isset($data['wrapId']) && $data['wrapId'] > 0) {
			$wrapIdQuery = " AND wrapid!='".(int)$data['wrapId']."'";
		}

		// Check that a duplicate wrapping type doesn't exist with this name
		$query = "
			SELECT wrapid
			FROM [|PREFIX|]gift_wrapping
			WHERE wrapname='".$GLOBALS['ISC_CLASS_DB']->Quote($data['wrapname'])."'".$wrapIdQuery;

		// if an image was uploaded, validate that
		try {
			$files = ISC_UPLOADHANDLER::getUploadedFiles();
			foreach ($files as /** @var UploadHandlerFile */$file) {
				if ($file->fieldName == 'wrapimage') {
					$dest = ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/wrap_images/' . GenRandFileName($file->name);
					$file->moveAs($dest);
					$image = @getimagesize($dest);
					if ($image === false) {
						unlink($dest);
						$error = GetLang("WrapImageInvalid");
						return false;
					}
					isc_chmod($dest, ISC_WRITEABLE_FILE_PERM);
					break;
				}
			}
		} catch (UploadHandlerException $exception) {
			$error = $exception->getMessage();
			return false;
		}

		if($GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
			$error = GetLang('DuplicateGiftWrapName');
			return false;
		}

		// Otherwise it's valid
		return true;
	}

	/**
	 * Fetch a gift wrapping type from the database based on the passed ID.
	 *
	 * @param string The gift wrapping type ID.
	 * @return array An array of information about the gift wrapping type as from the database.
	 */
	private function GetGiftWrapData($wrapId)
	{
		$query = "SELECT * FROM [|PREFIX|]gift_wrapping WHERE wrapid='".(int)$wrapId."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		return $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	}

	/**
	 * Commit a gift wrapping type to the database (either create a new one or update an existing one)
	 *
	 * @param array An array of data about the gift wrapping type.
	 * @param int If updating an existing wrap, the ID.
	 * @return boolean True if successful, false if not.
	 */
	private function CommitWrap($data, $wrapId=0)
	{
		if(!isset($data['wrapvisible'])) {
			$data['wrapvisible'] = 0;
		}

		if(!isset($data['wrapallowcomments'])) {
			$data['wrapallowcomments'] = '';
		}

		// image validation is performed in ValidateWrap
		$files = ISC_UPLOADHANDLER::getUploadedFiles();
		foreach ($files as /** @var UploadHandlerFile */$file) {
			if ($file->fieldName == 'wrapimage') {
				if ($file->getIsMoved()) {
					// only save if file was moved by ValidateWrap
					$data['wrappreview'] = str_replace(ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/', '', $file->getMovedDestination());
				}
				break;
			}
		}

		$wrapData = array(
			'wrapname' => $data['wrapname'],
			'wrapprice' => DefaultPriceFormat($data['wrapprice']),
			'wrapvisible' => (int)$data['wrapvisible'],
			'wrapallowcomments' => (int)$data['wrapallowcomments'],
		);

		if(isset($data['wrappreview'])) {
			$wrapData['wrappreview'] = $data['wrappreview'];
		}

		if($wrapId == 0) {
			$wrapId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('gift_wrapping', $wrapData);
		}
		else {
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('gift_wrapping', $wrapData, "wrapid='".(int)$wrapId."'");
		}

		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateGiftWrapping();

		// Couldn't save? return an error message
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			return false;
		}

		return true;
	}

	/**
	 * Update a specific type of gift wrapping.
	 */
	private function EditGiftWrap()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();
		$wrap = $this->GetGiftWrapData($_REQUEST['wrapId']);

		// If the wrapping type doesn't exist, show an error message
		if(!isset($wrap['wrapid'])) {
			FlashMessage(GetLang('InvalidGiftWrap'), MSG_ERROR, 'index.php?ToDo=viewGiftWrapping');
		}

		// Set up the form title and action
		$GLOBALS['FormAction']	= 'SaveUpdatedGiftWrap';
		$GLOBALS['Title']		= GetLang('EditGiftWrap');
		$GLOBALS['Intro']		= GetLang('EditGiftWrapIntro');

		if (GetConfig('CurrencyLocation') == 'right') {
			$GLOBALS['RightCurrencyToken'] = GetConfig('CurrencyToken');
		}
		else {
			$GLOBALS['LeftCurrencyToken'] = GetConfig('CurrencyToken');
		}

		// Set the form values
		$GLOBALS['WrapId']			= (int)$wrap['wrapid'];
		$GLOBALS['WrapName']		= isc_html_escape($wrap['wrapname']);
		$GLOBALS['WrapImage']		= isc_html_escape($wrap['wrappreview']);
		if($wrap['wrappreview'] == '') {
			$GLOBALS['HideCurrentWrapImage'] = 'display: none';
		}

		$GLOBALS['GiftWrapPrice'] = number_format($wrap['wrapprice'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), "");

		$GLOBALS['GiftWrapAllowCommentsChecked'] = '';
		if($wrap['wrapallowcomments']) {
			$GLOBALS['GiftWrapAllowCommentsChecked'] = 'checked="checked"';
		}

		$GLOBALS['GiftWrapVisibleChecked'] = '';
		if($wrap['wrapvisible']) {
			$GLOBALS['GiftWrapVisibleChecked'] = 'checked="checked"';
		}

		$this->template->display('giftwrapping.form.tpl');
	}

	/**
	 * Actually save the updated gift wrapping type.
	 */
	private function SaveUpdatedGiftWrap()
	{
		$wrap = $this->GetGiftWrapData($_REQUEST['wrapId']);
		// If the wrapping type doesn't exist, show an error message
		if(!isset($wrap['wrapid'])) {
			FlashMessage(GetLang('InvalidGiftWrap'), MSG_ERROR, 'index.php?ToDo=viewGiftWrapping');
		}

		$message = '';
		// Validate and if there's an error, show the edit page again for this wrapping type
		if(!$this->ValidateWrap($_POST, $message)) {
			FlashMessage($message, MSG_ERROR);
			$this->EditGiftWrap();
			return;
		}

		// OK, so now it's valid, save the wrapping in the database
		if(!$this->CommitWrap($_POST, $wrap['wrapid'])) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingGiftWrap').$error, MSG_ERROR);
			$this->EditGiftWrap();
			return;
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($wrap['wrapid'], $_POST['wrapname']);
			FlashMessage(GetLang('GiftWrapUpdated'), MSG_SUCCESS, 'index.php?ToDo=viewGiftWrapping');
		}
	}

	/**
	 * Delete one or more pieces of gift wrapping.
	 */
	private function DeleteGiftWrap()
	{
		if(!isset($_REQUEST['wrap'])) {
			ob_end_clean();
			header("Location: index.php?ToDo=viewGiftWrapping");
		}

		$wrapIds = array_map('intval', $_REQUEST['wrap']);
		$wrapIds[] = 0;

		$wrapIds = implode("','", $wrapIds);

		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('gift_wrapping', "WHERE wrapid IN ('".$wrapIds."')");

		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateGiftWrapping();

		$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
		if($err) {
			FlashMessage($err, MSG_ERROR, 'index.php?ToDo=viewGiftWrapping');
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminaction(count($_POST['wrap']));
			FlashMessage(GetLang('GiftWrapDeleted'), MSG_SUCCESS, 'index.php?ToDo=viewGiftWrapping');
		}
	}
}