<?php

	class ISC_ADMIN_FORMFIELDS extends ISC_ADMIN_BASE
	{
		/**
		 * The constructor.
		 */
		public function __construct()
		{
			parent::__construct();
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('formfields');
		}

		public function HandleToDo($Do)
		{
			switch (isc_strtolower($Do)) {
				default:
					if (!isset($_REQUEST['ajax'])) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					}

					$this->ManageFormFields();

					if (!isset($_REQUEST['ajax'])) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					}
			}
		}

		public function ManageFormFields($msgDesc='', $msgStatus='')
		{
			if ($msgDesc !== '') {
				$GLOBALS['Message'] = MessageBox($msgDesc, $msgStatus);
			}

			$flashMessages = GetFlashMessages();

			if (is_array($flashMessages) && !empty($flashMessages)) {
				$GLOBALS['Message'] = '';
				foreach ($flashMessages as $flashMessage) {
					$GLOBALS['Message'] .= MessageBox($flashMessage['message'], $flashMessage['type']);
				}
			}

			$GLOBALS['FormFieldsGrid'] = $this->ManageFormFieldsGrid();
			$GLOBALS['FormFieldsAddField'] = sprintf(GetLang('FormFieldsAddField'), GetLang('FormFieldsSectionAccount'));
			$GLOBALS['FormFieldsOptions'] = '';
			$availableFields = $GLOBALS['ISC_CLASS_FORM']->getAvailableFields();

			if (is_array($availableFields)) {
				foreach ($availableFields as $name => $desc) {
					$GLOBALS['FormFieldsOptions'] .= '<li><a href="#" onclick="AddFormField(\'' . isc_html_escape($name) . '\'); return false;" style="background-image:url(\'images/fields/' . $desc['img'] . '\'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px; width:auto;">' . isc_html_escape($desc['name']) . '</a></li>';
				}
			}

			$GLOBALS['FormFieldsSectionAccount'] = sprintf(GetLang('FormFieldsSectionTab'), GetLang('FormFieldsSectionAccount'));
			$GLOBALS['FormFieldsSectionAddress'] = sprintf(GetLang('FormFieldsSectionTab'), GetLang('FormFieldsSectionAddress'));

			$GLOBALS['FormFieldsAccountFormId'] = FORMFIELDS_FORM_ACCOUNT;
			$GLOBALS['FormFieldsAddressFormId'] = FORMFIELDS_FORM_ADDRESS;

			if(!gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['HideFormFieldsButtons'] = 'display: none';
			}

			$GLOBALS['FormFieldsHideAddButton'] = '';
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_FormFields) || !gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['FormFieldsHideAddButton'] = 'none';
			}

			$GLOBALS['FormFieldsHideDeleteButton'] = '';
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_FormFields) || !gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['FormFieldsHideDeleteButton'] = 'none';
			}

			$GLOBALS['FormFieldsIsSortable'] = '';
			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_FormFields)) {
				$GLOBALS['FormFieldsIsSortable'] = '1';
			}

			$this->template->display('formfields.manage.tpl');
		}

		/**
		 * Return the customer address grid
		 *
		 * Method will return the customer address HTML grid
		 *
		 * @access public
		 * @param int $formFieldFormId The optional form ID to display. Default is FORMFIELDS_FORM_ACCOUNT (account form)
		 * @return string The customer address HTML grid
		 */
		public function ManageFormFieldsGrid($formFieldFormId=FORMFIELDS_FORM_ACCOUNT)
		{
			if (!isId($formFieldFormId)) {
				return '';
			}

			$grid = '<ul class="SortableList" style="padding-top: 1px; padding-bottom: 1px; background-color: #f9f9f9;">';

			if (!isId($formFieldFormId)) {
				return '';
			}

			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields($formFieldFormId);

			if (!$fields || empty($fields)) {
				return '';
			}

			foreach (array_keys($fields) as $fieldId) {

				$field =& $fields[$fieldId];
				$details = call_user_func(array(get_class($field), 'getDetails'));

				$GLOBALS['FormFieldId'] = $fieldId;
				$GLOBALS['FormFieldData'] = isc_html_escape($details['name']);
				$GLOBALS['FormFieldLastModified'] = date('M jS Y', $field->record['formfieldlastmodified']);

				if ($field->record['formfieldisimmutable']) {
					$GLOBALS['FormFieldType'] = GetLang('FormFieldsBuiltIn');
					$GLOBALS['DeleteStatus'] = 'disabled="disabled"';
				} else {
					$GLOBALS['FormFieldType'] = GetLang('FormFieldsUserDefined');
					$GLOBALS['DeleteStatus'] = '';
				}

				$GLOBALS['FormFieldName'] = isc_html_escape($field->record['formfieldlabel']);

				if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_FormFields)) {
					$disabled = 'disabled="disabled" style="color: gray;"';
					$onclick = 'alert(\'' . GetLang('FormFieldsEditNotAllowedNoPermission') . '\'); return false;';
				} else {
					$disabled = '';
					$onclick = 'EditFormField(' . (int)$fieldId . ', ' . (int)$formFieldFormId . '); return false;';
				}

				$GLOBALS['FormFieldAction'] = '<a href="#" class="Action" onclick="' . $onclick . '" ' . $disabled . '>' . GetLang('Edit') . '</a>';

				if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_FormFields) || isc_strtolower($field->record['formfieldtype']) == 'selectortext' || !gzte11(ISC_MEDIUMPRINT)) {
					$disabled = 'disabled="disabled" style="color: gray;"';

					if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_FormFields)) {
						$msg = GetLang('FormFieldsCopyNotAllowedNoPermission');
					} else {
						$msg = sprintf(GetLang('FormFieldsCopyNotAllowed'), $field->record['formfieldlabel']);
					}

					$onclick = 'alert(\'' . $msg . '\'); return false;';
				} else {
					$disabled = '';
					$onclick = 'CopyFormField(' . (int)$fieldId . ', ' . (int)$formFieldFormId . ');return false;';
				}

				$GLOBALS['FormFieldAction'] .= ' <a href="#" class="Action" onclick="' . $onclick . '" ' . $disabled . '>' . GetLang('Copy') . '</a>';

				if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_FormFields) || $field->record['formfieldisimmutable'] || !gzte11(ISC_MEDIUMPRINT)) {
					$disabled = 'disabled="disabled" style="color: gray;"';

					if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_FormFields)) {
						$msg = GetLang('FormFieldsDeleteNotAllowedNoPermission');
					} else {
						$msg = sprintf(GetLang('FormFieldsDeleteNotAllowed'), $field->record['formfieldlabel']);
					}

					$onclick = 'alert(\'' . $msg . '\'); return false;';
				} else {
					$disabled = '';
					$onclick = 'DeleteFormField(' . (int)$fieldId . ', ' . (int)$formFieldFormId . '); return false;';
				}

				$GLOBALS['FormFieldAction'] .= ' <a href="#" class="Action" onclick="' . $onclick . '" ' . $disabled . '>' . GetLang('Delete') . '</a>';

				/**
				 * Special case to stop customers from putting fields above the
				 * email and password fields
				 */
				if ($formFieldFormId == FORMFIELDS_FORM_ACCOUNT) {
					if (trim($field->record['formfieldprivateid']) !== '') {
						$privateSet = true;
					} else if ($privateSet) {
						$grid .= '</ul><ul class="SortableList" style="padding-top: 1px; padding-bottom: 1px; background-color: #f9f9f9;">';
						$privateSet = false;
					}
				}

				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_FormFields)) {
					$GLOBALS['FormFieldSortRow'] = 'DragMouseDown sort-handle';
				} else {
					$GLOBALS['FormFieldSortRow'] = 'HideOnDrag';
				}

				$grid .= $this->template->render('Snippets/FormFields.html');
			}

			$grid .= '</ul>';
			return $grid;
		}

		/**
		 * Replciate any billing address changes to the shipping address
		 *
		 * Method will replicate any billing address changes to the shipping address. Run this AFTER
		 * saving the field data
		 *
		 * @access public
		 * @param string $newFieldName The new field name (after saving)
		 * @param string $oldFieldName The optional old field name (before saving). Default to empty
		 *                              string (brand new field)
		 * @return bool TRUE if the replication was successful, FALSE if not
		 */
		public function CommitAddressChanges($newFieldName, $oldFieldName='')
		{
			if ($newFieldName == '') {
				return false;
			}

			/**
			 * If this is a brand new field then just duplicate the record. If its new then it
			 * should not have the immutable and privateId values
			 */
			if ($oldFieldName == '') {
				$query = "INSERT INTO [|PREFIX|]formfields
							SELECT NULL, " . FORMFIELDS_FORM_SHIPPING . ", formfieldtype, formfieldlabel,
									formfielddefaultval, formfieldextrainfo, formfieldisrequired,
									0, '', UNIX_TIMESTAMP(), formfieldsort
							FROM [|PREFIX|]formfields
							WHERE formfieldformid=" . FORMFIELDS_FORM_BILLING . "
								AND formfieldlabel='" . $GLOBALS['ISC_CLASS_DB']->Quote($newFieldName) . "'";

				$GLOBALS['ISC_CLASS_DB']->Query($query);

				return true;
			}

			/**
			 * Else we have to find the corresponding shipping field
			 */
			$query = "SELECT *
						FROM [|PREFIX|]formfields
						WHERE formfieldformid=" . FORMFIELDS_FORM_BILLING . "
							AND formfieldlabel='" . $GLOBALS['ISC_CLASS_DB']->Quote($newFieldName) . "'";

			$billingField = $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query($query));

			$query = "SELECT *
						FROM [|PREFIX|]formfields
						WHERE formfieldformid=" . FORMFIELDS_FORM_SHIPPING . "
							AND formfieldlabel='" . $GLOBALS['ISC_CLASS_DB']->Quote($oldFieldName) . "'";

			$shippingField = $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query($query));

			if (!$billingField || !$shippingField) {
				return false;
			}

			/**
			 * Now update its details
			 */
			$savedata = $billingField;
			unset($savedata['formfieldid']);
			unset($savedata['formfieldformid']);

			$rtn = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('formfields', $savedata, 'formfieldid=' . (int)$shippingField['formfieldid']);

			if ($rtn === false) {
				return false;
			}

			/**
			 * We'll need to do the re-ranking
			 */
			$this->CommitAddressRankingChanges($billingField['formfieldid'], $shippingField['formfieldid']);

			return true;
		}

		/**
		 * Replciate any billing address deletions to the shipping address
		 *
		 * Method will replicate any billing address deletions to the shipping address. Run this AFTER
		 * deleting the field data
		 *
		 * @access public
		 * @return bool TRUE if the replication was successful, FALSE if not
		 */
		public function CommitAddressDeletion()
		{
			$query = "SELECT s.formfieldid
						FROM [|PREFIX|]formfields s
						WHERE s.formfieldformid = " . FORMFIELDS_FORM_SHIPPING . "
							AND NOT EXISTS(SELECT *
											FROM [|PREFIX|]formfields b
											WHERE b.formfieldformid = " . FORMFIELDS_FORM_BILLING . "
												AND s.formfieldlabel = b.formfieldlabel)";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$fieldIdx = array();
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$GLOBALS['ISC_CLASS_FORM']->deleteFormField(FORMFIELDS_FORM_SHIPPING, $row['formfieldid']);
			}

			$this->CommitAddressRankingChanges();

			return true;
		}

		/**
		 * Replciate any billing address ranking changes to the shipping address
		 *
		 * Method will replicate any billing address ranking changes to the shipping address.
		 * Run this AFTER saving the field data
		 *
		 * @access public
		 * @return bool TRUE if the replication was successful, FALSE if not
		 */
		public function CommitAddressRankingChanges()
		{
			$query = "UPDATE [|PREFIX|]formfields b
							JOIN [|PREFIX|]formfields s ON b.formfieldlabel = s.formfieldlabel
						SET s.formfieldsort = b.formfieldsort
						WHERE b.formfieldformid = " . FORMFIELDS_FORM_BILLING . "
							AND s.formfieldformid = " . FORMFIELDS_FORM_SHIPPING;

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($result === false) {
				return false;
			}

			return true;
		}

		public function MapFormFieldSection($formId)
		{
			if (!isId($formId)) {
				return false;
			}

			switch ($formId) {
				case FORMFIELDS_FORM_ACCOUNT:
					return GetLang('FormFieldsSectionAccount');
					break;

				case FORMFIELDS_FORM_ADDRESS:
					return GetLang('FormFieldsSectionAddress');
					break;
			}

			return false;
		}
	}