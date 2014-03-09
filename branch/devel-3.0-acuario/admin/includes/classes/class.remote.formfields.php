<?php

	if (!defined('ISC_BASE_PATH')) {
		die();
	}

	class ISC_ADMIN_REMOTE_FORMFIELDS extends ISC_ADMIN_REMOTE_BASE
	{
		public function __construct()
		{
			$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS'] = new ISC_ADMIN_FORMFIELDS();
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('formfields');

			parent::__construct();
		}

		public function HandleToDo()
		{
			$what = isc_strtolower(@$_REQUEST['w']);

			switch ($what) {
				case "getformfieldgrid":
					$this->getFormFieldGrid();
					break;

				case 'resortformfieldgrid':
					$this->resortFormFieldGrid();
					break;

				case 'getfieldsetuppopup':
					$this->getFieldSetupPopup();
					break;

				case 'addfieldsetuppopup':
					$this->addFieldSetupPopup();
					break;

				case 'copyfieldsetuppopup':
					$this->copyFieldSetupPopup();
					break;

				case 'deletefield':
					$this->deleteField();
					break;

				case 'deletemultifield':
					$this->deleteMultiField();
					break;

				case 'savefieldsetup':
					$this->saveFieldSetup();
					break;
			}
		}

		private function getFormFieldGrid()
		{
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_FormFields)) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('grid', '', true);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			if (!isset($_POST['formId']) || !isId($_POST['formId'])) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('grid', '', true);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			$GLOBALS['ISC_ADMIN_FORMFIELDS'] = GetClass('ISC_ADMIN_FORMFIELDS');
			$grid = $GLOBALS['ISC_ADMIN_FORMFIELDS']->ManageFormFieldsGrid($_POST['formId']);

			if ($grid == '') {
				$grid = '<li><div class="MessageBox MessageBoxInfo" style="margin:0;">' . GetLang('FormFieldsSectionNoFields') . '</div></li>';
			}

			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('grid', $grid, true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function resortFormFieldGrid()
		{
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_FormFields)) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('grid', '', true);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			if (!isset($_POST['formId']) || !isId($_POST['formId']) || !isset($_POST['sortorder'])) {
				exit;
			}

			$idx = explode(',', $_POST['sortorder']);
			$idx = array_filter($idx, 'isId');

			if (!is_array($idx) || empty($idx)) {
				exit;
			}

			$sort = 1;
			foreach ($idx as $fieldId) {
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('formfields', array('formfieldsort' => $sort++), 'formfieldid=' . (int)$fieldId . ' AND formfieldformid=' . (int)$_POST['formId']);
			}

			$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS']->CommitAddressRankingChanges();

			exit;
		}

		private function getFieldSetupPopup()
		{
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_FormFields)) {
				exit;
			}

			if (!isset($_GET['fieldId']) || !isId($_GET['fieldId']) || !isset($_GET['formId']) || !isId($_GET['formId'])) {
				exit;
			}

			$field = $GLOBALS['ISC_CLASS_FORM']->getFormField($_GET['formId'], $_GET['fieldId']);

			$GLOBALS['FormFieldSetupPopupHeading'] = GetLang('FormFieldSetupPopupHeadingEdit');

			print $field->loadForBackend();
			exit;
		}

		private function addFieldSetupPopup()
		{
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_FormFields) || !gzte11(ISC_MEDIUMPRINT)) {
				exit;
			}

			if (!isset($_GET['fieldType']) || trim($_GET['fieldType']) == '' || !isset($_GET['formId']) || !isId($_GET['formId'])) {
				exit;
			}

			$field = $GLOBALS['ISC_CLASS_FORM']->getFormField($_GET['formId'], '', $_GET['fieldType']);

			$GLOBALS['FormFieldSetupPopupHeading'] = GetLang('FormFieldSetupPopupHeadingAdd');

			print $field->loadForBackend();
			exit;
		}

		private function copyFieldSetupPopup()
		{
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_FormFields) || !gzte11(ISC_MEDIUMPRINT)) {
				exit;
			}

			if (!isset($_GET['fieldId']) || !isId($_GET['fieldId']) || !isset($_GET['formId']) || !isId($_GET['formId'])) {
				exit;
			}

			$field = $GLOBALS['ISC_CLASS_FORM']->copyFormField($_GET['formId'], $_GET['fieldId']);

			$GLOBALS['FormFieldSetupPopupHeading'] = GetLang('FormFieldSetupPopupHeadingEdit');

			print $field->loadForBackend();
			exit;
		}

		private function deleteField()
		{
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_FormFields) || !gzte11(ISC_MEDIUMPRINT)) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('fieldId', @$_POST['fieldId']);
				$tags[] = $this->MakeXMLTag('msg', GetLang('Unauthorized'));
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			if (!isset($_POST['fieldId']) || !isId($_POST['fieldId']) || !isset($_POST['formId']) || !isId($_POST['formId'])) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('fieldId', @$_POST['fieldId']);
				$tags[] = $this->MakeXMLTag('msg', GetLang('FormFieldDeleteInvalid'));
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			if (!$GLOBALS['ISC_CLASS_FORM']->deleteFormField($_POST['formId'], $_POST['fieldId'])) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('msg', GetLang('FormFieldDeleteFailed'));
			} else {
				$tags[] = $this->MakeXMLTag('status', 1);
				$tags[] = $this->MakeXMLTag('msg', GetLang('FormFieldDeleteSuccess'));

				$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS']->CommitAddressDeletion();
			}

			$tags[] = $this->MakeXMLTag('fieldId', $_POST['fieldId']);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function deleteMultiField()
		{
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_FormFields) || !gzte11(ISC_MEDIUMPRINT)) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('msg', GetLang('Unauthorized'));
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			if (!isset($_POST['fieldIdx']) || trim($_POST['fieldIdx']) == '' || !isset($_POST['formId']) || !isId($_POST['formId'])) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('msg', sprintf(GetLang('FormFieldDeleteSelectedFailed'), ''));
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			$selectedIdx = explode(',', $_POST['fieldIdx']);
			$selectedIdx = array_filter($selectedIdx, 'isId');

			if (!is_array($selectedIdx) || empty($selectedIdx)) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('msg', sprintf(GetLang('FormFieldDeleteSelectedFailed'), ''));
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			foreach ($selectedIdx as $fieldId) {
				$GLOBALS['ISC_CLASS_FORM']->deleteFormField($_POST['formId'], $fieldId);
			}

			$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS']->CommitAddressDeletion();

			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('msg', GetLang('FormFieldDeleteSelectedSuccess'));
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function saveFieldSetup()
		{
			if (!isset($_POST['fieldId']) || !isId($_POST['fieldId'])) {
				$perm = AUTH_Add_FormFields;
			} else {
				$perm = AUTH_Edit_FormFields;
			}

			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission($perm)) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('msg', GetLang('Unauthorized'));
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			$errmsg = '';
			if (!$this->validateFieldSetup($errmsg)) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('msg', $errmsg, 1);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			/**
			 * If we have no field ID then we are saving a new field
			 */
			if (!isset($_POST['fieldId']) || !isId($_POST['fieldId'])) {
				$type = isc_html_escape($_POST['fieldType']);
				$fieldId = '';
			} else {
				$fieldId = $_POST['fieldId'];
				$type = '';
			}

			$field = $GLOBALS['ISC_CLASS_FORM']->getFormField($_POST['formId'], $fieldId, $type);

			$oldLabel = '';
			if (isset($field->record['formfieldlabel'])) {
				$oldLabel = $field->record['formfieldlabel'];
			}

			$rtn = $field->saveForBackend($_POST, $msg);

			if (!$rtn) {
				if (isset($_POST['fieldId']) && isId($_POST['fieldId'])) {
					$errmsg = 'FormFieldSetupAddedFailed';
				} else {
					$errmsg = 'FormFieldSetupUpdateFailed';
				}

				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('msg', sprintf(GetLang($errmsg), $msg), 1);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			/**
			 * Commit (replicate) our changes if we are an address field
			 */
			if ((int)$_POST['formId'] == FORMFIELDS_FORM_BILLING) {
				$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS']->CommitAddressChanges($_POST['name'], $oldLabel);
			}

			/**
			 * OK, all is good
			 */
			switch ((int)$_POST['formId']) {
				case FORMFIELDS_FORM_ACCOUNT:
					$type = GetLang('FormFieldsSectionAccountSmall');
					break;

				case FORMFIELDS_FORM_BILLING:
					$type = GetLang('FormFieldsSectionBillingSmall');
					break;
			}

			if (isset($_POST['fieldId']) && isId($_POST['fieldId'])) {
				$errmsg = 'FormFieldSetupUpdateSuccess';
			} else {
				$errmsg = 'FormFieldSetupAddedSuccess';
			}

			$name = isc_html_escape($_POST['name']);

			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('msg', sprintf(GetLang($errmsg), $type, $name), 1);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function validateFieldSetup(&$errmsg)
		{
			$fieldId = 0;
			if (isset($_POST['fieldId']) && isId($_POST['fieldId'])) {
				$fieldId = $_POST['fieldId'];
				$failed = 'FormFieldSetupAddedFailed';
			} else {
				$failed = 'FormFieldSetupUpdateFailed';
			}

			if (!isset($_POST['formId']) || !isId($_POST['formId'])) {
				$errmsg = sprintf(GetLang($failed), '');
				return false;
			}

			if (!isset($_POST['name']) || $_POST['name'] == '') {
				$errmsg = GetLang('FormFieldSetupUpdateFailedLabel');
				return false;
			}

			if ($fieldId == 0 && (!isset($_POST['fieldType']) || trim($_POST['fieldType']) == '')) {
				$errmsg = sprintf(GetLang($failed), '');
				return false;
			}

			$query = "SELECT *
						FROM [|PREFIX|]formfields
						WHERE formfieldformid = " . (int)$_POST['formId'] . "
							AND formfieldlabel='" . $GLOBALS['ISC_CLASS_DB']->Quote($_POST['name']) . "'";

			if (isId($fieldId)) {
				$query .= " AND formfieldid != " . $fieldId;
			}

			if ($GLOBALS['ISC_CLASS_DB']->CountResult($query) > 0) {
				$errmsg = sprintf(GetLang('FormFieldSetupUpdateFailedDuplicateLabel'), $_POST['name']);
				return false;
			}

		return true;
		}
	}