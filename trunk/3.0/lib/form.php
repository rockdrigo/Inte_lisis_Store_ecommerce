<?php

define("___FORM_DEFAULT_NAME___", "FormField");

require_once(dirname(__FILE__) . '/formfields/formfield.base.php');

class ISC_FORM
{
	private $fieldPath;
	private $fieldsUsed;

	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		$this->fieldPath = realpath(dirname(__FILE__) . '/formfields');
		$this->fieldsUsed = array();
	}

	/**
	 * Get available form fields
	 *
	 * Method will return an array of all the avaliable form fields. Each key will be the field
	 * name and the value will hold the instantiated object
	 *
	 * @access public
	 * @return array An array of all the instantiated form field objects
	 */
	public function getAvailableFields()
	{
		$fields = array();
		$files = scandir($this->fieldPath);
		foreach ($files as $file) {

			$filepath = $this->fieldPath . '/' . $file;

			/**
			 * Make sure we only get the valid files
			 */
			if ($file == '.' || $file == '..' || $file == 'formfield.base.php' || $file == 'formfield.selectortext.php' || !is_file($filepath)) {
				continue;
			}

			$typeToLower = isc_strtolower(substr($file, 10, -4));
			$className = "ISC_FORMFIELD_" . isc_strtoupper($typeToLower);

			if (!class_exists($className)) {
				include_once($filepath);
			}

			if (!method_exists($className, 'getDetails')) {
				continue;
			}

			$fields[$typeToLower] = call_user_func(array($className, 'getDetails'));
		}

		return $fields;
	}

	/**
	 * Include the form field code file
	 *
	 * Method will include (once) the form field code file
	 *
	 * @access private
	 * @param string $fieldType The field type to include
	 * @return string The class name of the field class if the code source file was found and
	 *                included successfully, FALSE if not
	 */
	private function includeFormFieldCode($fieldType)
	{
		$typeToLower = isc_strtolower($fieldType);

		if ($typeToLower == '' || $typeToLower == 'base' || preg_match('/[^a-z]+/', $typeToLower)) {
			return false;
		}

		$filepath = $this->fieldPath . '/formfield.' . $typeToLower . '.php';

		if (!file_exists($filepath) || !is_file($filepath)) {
			return false;
		}

		$className = "ISC_FORMFIELD_" . isc_strtoupper($fieldType);

		if (!class_exists($className)) {
			include_once($filepath);
		}

		return $className;
	}

	/**
	 * Add a field to the fieldsUsed array
	 *
	 * Method will add a field to the fieldsUsed, which will be then used later on the construct
	 * any JS events that have been attached to the field
	 *
	 * @access public
	 * @param object $field The field object
	 * @return NULL
	 */
	public function addFormFieldUsed($field)
	{
		$this->fieldsUsed[$field->record['formfieldid']] = $field;
	}

	/**
	 * Build the required JS
	 *
	 * Method will build the required JS that will contain all the lang variables and any events
	 *
	 * @access public
	 * @param bool $includeTags TRUE to also include the <script> tags, FALSE not to. Default is FALSE
	 * @return string The footer JS HTML
	 */
	public function buildRequiredJS($includeTags=false)
	{
		$js = '
		lang.CustomFieldsValidationRequired = "' . GetLang('CustomFieldsValidationRequired') . '";
		lang.CustomFieldsValidationOptionRequired = "' . GetLang('CustomFieldsValidationOptionRequired') . '";
		lang.CustomFieldsValidationNumbersOnly = "' . GetLang('CustomFieldsValidationNumbersOnly') . '";
		lang.CustomFieldsValidationNumbersToLow = "' . GetLang('CustomFieldsValidationNumbersToLow') . '";
		lang.CustomFieldsValidationNumbersToHigh = "' . GetLang('CustomFieldsValidationNumbersToHigh') . '";
		lang.CustomFieldsValidationDateToLow = "' . GetLang('CustomFieldsValidationDateToLow') . '";
		lang.CustomFieldsValidationDateToHigh = "' . GetLang('CustomFieldsValidationDateToHigh') . '";
		lang.CustomFieldsValidationDateInvalid = "' . GetLang('CustomFieldsValidationDateInvalid') . '";
		';

		if (!empty($this->fieldsUsed)) {
			$js .= '
				$(document).ready(
					function()
					{
			';
			foreach (array_keys($this->fieldsUsed) as $fieldId) {
				$js = trim($js . "\n" . $this->fieldsUsed[$fieldId]->loadEventsForFrontend());
			}

			$js .= '
					}
				);
			';
		}

		if ($includeTags) {
			$js = "<script type=\"text/javascript\"><!--\n" . $js . "\n//--></script>\n";
		}

		return $js;
	}

	/**
	 * Get an instansiated formfield object
	 *
	 * Method will return the instansiated formfield object corresponding to either the form
	 * field ID $formFieldId or the form field type $type. Must have either parameter. If both
	 * are passed then the $formFieldId will be used
	 *
	 * @access public
	 * @param int $formId The form ID
	 * @param mixed $formFieldId The optional form field ID or form field record array
	 * @param string $type The optional form field type
	 * @param bool $setRequestValue TRUE to automaticaly assign the requested value if found,
	 *                              FALSE not to. Default is FALSE
	 * @param int $formSessionId The formsession ID to assign the stored session values to,
	 *                           FALSE not to. Default is FALSE
	 * @return object The form field object if type points to a valid form field, FALSE if not
	 */
	public function getFormField($formId, $formField='', $type='', $setRequestValue=false, $formSessionId=false)
	{
		if (!isId($formId)) {
			return false;
		}

		if (!isId($formField) && !is_array($formField) && $type == '') {
			return false;
		}

		$fieldData = array();

		/**
		 * Load from database if we can
		 */

		if (isId($formField)) {
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]formfields WHERE formfieldid=" . (int)$formField);
			$fieldData = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if (!$fieldData || $fieldData['formfieldtype'] == '') {
				return false;
			}

			$type = $fieldData['formfieldtype'];

		/**
		 * Else use the array if we have it
		 */
		} else if (is_array($formField)) {
			$fieldData = $formField;

			if (!array_key_exists('formfieldtype', $fieldData) || $fieldData['formfieldtype'] == '') {
				return false;
			}

			$type = $fieldData['formfieldtype'];
		}

		$className = $this->includeFormFieldCode($type);

		if (!$className) {
			return false;
		}

		/**
		 * Load data from either the database or $_REQUEST
		 */
		if (is_array($fieldData) && !empty($fieldData)) {

			if (!array_key_exists('formfieldid', $fieldData) || !isId($fieldData['formfieldid'])) {
				return false;
			}

			$field = new $className($formId, $fieldData);

			/**
			 * Set the posted value if we are told to. Only do it here as we are 'loaded'
			 */
			if ($setRequestValue) {
				$value = $field->getFieldRequestValue();
				$field->setValue($value);

			/**
			 * Else if we have a formsession ID the try to retrieve the stored values from that
			 */
			} else if (isId($formSessionId)) {
				$sessdata = $this->getSavedSessionData($formSessionId);

				if (is_array($sessdata) && array_key_exists($fieldData['formfieldid'], $sessdata)) {
					$field->setValue($sessdata[$fieldData['formfieldid']], true);
				}
			}

		/**
		 * Else just do an empty shell of an object
		 */
		} else {
			$field = new $className($formId);
		}

		return $field;
	}

	/**
	 * Get an instance of a copied formfield object
	 *
	 * Method will return a copied instansiated formfield object corresponding to $copyFieldId.
	 * The return object will basically be a copy of the $copyFieldId field but with certain
	 * fields, like fieldID, being reset.
	 *
	 * @access public
	 * @param int $copyFormId The copied field form ID
	 * @param int $copyFieldId The field ID to copy from
	 * @return object The copied form field object on success, FALSE if not
	 */
	public function copyFormField($copyFormId, $copyFieldId)
	{
		if (!isId($copyFormId) || !isId($copyFieldId)) {
			return false;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]formfields WHERE formfieldid=" . (int)$copyFieldId);
		$fieldData = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if (!$fieldData || $fieldData['formfieldtype'] == '') {
			return false;
		}

		$className = $this->includeFormFieldCode($fieldData['formfieldtype']);

		if (!$className) {
			return false;
		}

		$newCopy = new $className($copyFormId, $fieldData, true);

		return $newCopy;
	}

	/**
	 * Get an array of all the instansiated formfield objects
	 *
	 * Method will return an array of all the instansiated formfield objects corresponding to the
	 * form id $formId, where each array field key will be the form field ID and the value being
	 * the instansiated object
	 *
	 * @access public
	 * @param int $formId The form ID
	 * @param bool $setRequestValue TRUE to automaticaly assign the requested value if found, FALSE
	 *                              not to. Default is FALSE
	 * @param mixed $formSessionId The formsession ID/array to assign the stored session values to,
	 *                             FALSE not to. Default is FALSE
	 * @return array The array of all the instansiated formfield objects on success, FALSE otherwise
	 */
	public function getFormFields($formId, $setRequestValue=false, $formSessionId=false)
	{
		if (!isId($formId)) {
			return false;
		}

		$fields = array();
		$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]formfields WHERE formfieldformid=" . (int)$formId . " ORDER BY formfieldsort ASC");

		while ($field = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$fields[$field['formfieldid']] = self::getFormField($formId, $field, '', $setRequestValue, false);
		}

		/**
		 * Assign the form session here as we can only hit the database once instead of for
		 * each field
		 */
		if (isId($formSessionId) || is_array($formSessionId)) {
			if (isId($formSessionId)) {
				$sessdata = $this->getSavedSessionData($formSessionId);
			} else {

				/**
				 * We need to make sure that this array is only the basic return from self::getSavedSessionData()
				 * as the full return will be a multi-dimensional array. If it is then massage it into a
				 * standard single dimensional array.
				 */
				reset($formSessionId);
				if (is_array(current($formSessionId))) {
					$sessdata = array();
					foreach ($formSessionId as $fieldId => $fieldData) {
						$sessdata[$fieldId] = $fieldData['value'];
					}
				} else {
					$sessdata = $formSessionId;
				}
			}

			if (is_array($sessdata) && !empty($sessdata)) {
				foreach (array_keys($fields) as $fieldId) {
					if (array_key_exists($fieldId, $sessdata)) {
						$fields[$fieldId]->setValue($sessdata[$fieldId], true);
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Copy 1st form field values to 2nd form fields and return 2nd form fields
	 *
	 * Method will get 2 form fields groups ($formFormId and $toFormId), load up the first group with
	 * the requested value, apply those value from the first groups private fields to the second
	 * group's private fields (basically transplant values from $formFormId to $toFormId). Once
	 * finished method will return the second group's fields
	 *
	 * @access public
	 * @param int $formFormId The form field to copy from
	 * @param int $toFormId The form field to copy to
	 * @return array The $toFormId form widgerts on success, FALSE on failure
	 */
	public function copyFormFieldValues($fromFormId, $toFormId)
	{
		if (!isId($fromFormId) || !isId($toFormId)) {
			return false;
		}

		$oldFields = $this->getFormFields($fromFormId, true);
		$newFields = $this->getFormFields($toFormId, false);
		$fieldMap = array();

		if (!$oldFields || !$newFields) {
			return false;
		}

		foreach (array_keys($oldFields) as $fieldId) {
			if ($oldFields[$fieldId]->record['formfieldprivateid'] == '') {
				continue;
			}

			$fieldMap[$oldFields[$fieldId]->record['formfieldprivateid']] = array(
				'from' => $fieldId
			);
		}

		foreach (array_keys($newFields) as $fieldId) {
			if ($newFields[$fieldId]->record['formfieldprivateid'] == '') {
				continue;
			}

			if (!isset($fieldMap[$newFields[$fieldId]->record['formfieldprivateid']])) {
				continue;
			}

			$fieldMap[$newFields[$fieldId]->record['formfieldprivateid']]['to'] = $fieldId;
		}

		/**
		 * Now map the values form the old one to the new one
		 */
		foreach ($fieldMap as $setup) {
			if (!isset($setup['from']) || !isset($setup['to'])) {
				continue;
			}

			$newFields[$setup['to']]->setValue($oldFields[$setup['from']]->getValue());
		}

		return $newFields;
	}

	/**
	 * Get an array of all the instansiated formfield objects and assign the requested values
	 * to each one
	 *
	 * Method will call getFormFields() with the argument to assign the requested value to each
	 * form field as TRUE ($setRequestValue = TRUE)
	 *
	 * @access public
	 * @param int $formId The form ID
	 * @return array The array of all the instansiated formfield objects on success, FALSE otherwise
	 */
	public function getFormFieldsRequested($formId)
	{
		return $this->getFormFields($formId, true);
	}

	/**
	 * Get an array of all the instansiated formfield objects and assign the stored formfieldsession
	 * values to each one
	 *
	 * Method will call getFormFields() with the argument to assign the formfieldsession record
	 * values to each one.
	 *
	 * @access public
	 * @param int $formId The form ID
	 * @param int $formSessionId The formsession ID
	 * @return array The array of all the instansiated formfield objects on success, FALSE otherwise
	 */
	public function getFormFieldsSession($formId, $formSessionId)
	{
		if (!isId($formSessionId)) {
			return false;
		}

		return $this->getFormFields($formId, false, $formSessionId);
	}

	/**
	 * Delete a form field
	 *
	 * Method will delete a form field
	 *
	 * @access public
	 * @param int $formId The form ID
	 * @param int $formFieldId The form field ID
	 * @param bool $clearSessionData TRUE to also clear out the stored formfieldsession data, FALSE
	 *                               to leave it alone. Default is TRUE
	 * @return bool TRUE if the form field was deleted, FALSE if arguments are bad or if the
	 *              field was not found
	 */
	public function deleteFormField($formId, $formFieldId, $clearSessionData=true)
	{
		if (!isId($formId) || !isId($formFieldId)) {
			return false;
		}

		$field = $this->getFormField($formId, $formFieldId);

		if (!$field) {
			return false;
		}

		if ($GLOBALS['ISC_CLASS_DB']->DeleteQuery('formfields', 'WHERE formfieldisimmutable = 0 AND formfieldid = ' . (int)$formFieldId) === false) {
			return false;
		}

		/**
		 * Remove the sesion data if we have to
		 */
		if ($clearSessionData) {
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('formfieldsessions', 'WHERE formfieldfieldid = ' . (int)$formFieldId);
		}

		/**
		 * Update the sort order
		 */
		$query = "UPDATE [|PREFIX|]formfields
					SET formfieldsort=(formfieldsort-1)
					WHERE formfieldformid=" . (int)$formId . " AND formfieldsort > " . (int)$field->record['formfieldsort'] . "
					ORDER BY formfieldsort ASC";

		$GLOBALS['ISC_CLASS_DB']->Query($query);

		return true;
	}

	/**
	 * Load a form field and return the HTML
	 *
	 * Method will load the form field $formFieldId and return the HTML either from the form field
	 * ID $formFieldId or the form field type $type. Must have either parameter. If both are passed
	 * then the $formFieldId will be used
	 *
	 * @access public
	 * @param int $formFieldId The optional form field ID
	 * @param string $type The optional form field type
	 * @param bool $loadForFrontend The optional flag to build either for the frontend or the
	 *                              backend. Default is TRUE (frontend)
	 * @return string The form field HTML on success, FALSE otherwise
	 */
	public function loadFormField($formId, $formFieldId='', $type='', $loadForFrontend=true)
	{
		if (!isId($formId) || (!isId($formFieldId) && $type == '')) {
			return false;
		}

		$field = self::getFormField($formId, $formFieldId, $type);

		if (!$field) {
			return false;
		}

		if ($loadForFrontend) {
			return $field->loadForFrontend();
		} else {
			return $field->loadForBackend();
		}
	}

	/**
	 * Save the form(s) session
	 *
	 * Method will save the form(s) session in the database
	 *
	 * @access public
	 * @param mixed $formIdx Either the form ID or an array of form IDs to save the form session
	 * @param bool $noPrivateData TRUE to only save non-private form field posted data, FALSE
	 *                            for everything. Default is TRUE
	 * @param int $existingFormSessionId The optional existing formsession ID to update the existing
	 *                                   form session record. Default is NULL (new formsession record)
	 * @return int The formsession ID on success, FALSE on failure
	 */
	public function saveFormSession($formIdx, $noPrivateData=true, $existingFormSessionId=null)
	{
		if (isId($formIdx)) {
			$formIdx = array($formIdx);
		}

		if (!is_array($formIdx)) {
			return false;
		}

		$formIdx = array_filter($formIdx, 'isId');

		if (empty($formIdx)) {
			return false;
		}

		/**
		 * Create/Update the formsession record
		 */
		$savedata = array(
			'formsessiondate' => time(),
			'formsessionformidx' => implode(',', $formIdx)
		);

		if (!isId($existingFormSessionId)) {
			$formSessionId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('formsessions', $savedata);
		} else {
			$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('formsessions', $savedata, 'formsessionid=' .	(int)$existingFormSessionId);
			$formSessionId = false;
			if ($result !== false) {
				$formSessionId = $existingFormSessionId;
			}
		}

		if (!isId($formSessionId)) {
			return false;
		}

		/**
		 * If we are updating then remove all previous formfieldsession records as the existing form
		 * fields could have changed. DO NOT delete a password field entry if we are given one to
		 * save that is blank (trying to simulate a 'leave password blank to not change' thing)
		 */
		if (isId($existingFormSessionId)) {
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('formfieldsessions', 'WHERE formfieldsessioniformsessionid=' . (int)$existingFormSessionId . " AND formfieldfieldtype != 'password'");
		}

		/**
		 * Loop through all the forms and get their fields
		 */
		foreach ($formIdx as $formId) {
			$fields = $this->getFormFields($formId, true);

			if (!is_array($fields)) {
				continue;
			}

			/**
			 * Loop throuh all fields and record it in the formfieldsessions table
			 */
			foreach (array_keys($fields) as $fieldId) {

				if ($noPrivateData && $fields[$fieldId]->record['formfieldprivateid'] !== '') {
					continue;
				}

				/**
				 * If this is a password already in an existing session WITH a value, then delete its
				 * previous record. If it has no value then DO NOT save it (just skip it)
				 */
				if ($fields[$fieldId]->record['formfieldtype'] == 'password' && isId($existingFormSessionId)) {
					if ($fields[$fieldId]->getValue() !== '') {
						$GLOBALS['ISC_CLASS_DB']->DeleteQuery('formfieldsessions', 'WHERE formfieldsessioniformsessionid=' . (int)$existingFormSessionId . " AND formfieldfieldid=" . (int)$fieldId);
					} else {
						continue;
					}
				}

				$savedata = array(
					'formfieldsessioniformsessionid' => $formSessionId,
					'formfieldfieldid' => $fieldId,
					'formfieldformid' => $fields[$fieldId]->record['formfieldformid'],
					'formfieldfieldtype' => $fields[$fieldId]->record['formfieldtype'],
					'formfieldfieldlabel' => $fields[$fieldId]->record['formfieldlabel'],
					'formfieldfieldvalue' => $fields[$fieldId]->getValue()
				);

				/**
				 * Serialize the value as there might be arrays and other scary things in there
				 */
				$savedata['formfieldfieldvalue'] = serialize($savedata['formfieldfieldvalue']);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('formfieldsessions', $savedata);
			}
		}

		return $formSessionId;
	}

	/**
	 * Save the form session using the data given
	 *
	 * Method will read the $formFieldData array, with the keys being the form field ID and the
	 * value being the submitted value
	 *
	 * @access public
	 * @param array $formFieldData The form field submitted array
	 * @param int $existingFormSessionId The optional existing formsession ID to update the existing
	 *                                   form session record. Default is NULL (new formsession record)
	 * @return int The formsession ID on success, FALSE on failure
	 */
	public function saveFormSessionManual($formFieldData, $existingFormSessionId=null)
	{
		if (!is_array($formFieldData)) {
			return false;
		}

		$formFieldDataKeys = array_keys($formFieldData);
		$formFieldDataKeys = array_filter($formFieldDataKeys, 'isId');

		if (empty($formFieldDataKeys)) {
			return false;
		}

		/**
		 * Create the formsession record
		 */
		$savedata = array(
			'formsessiondate' => time()
		);

		if (!isId($existingFormSessionId)) {
			$formSessionId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('formsessions', $savedata);
		} else {
			$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('formsessions', $savedata, 'formsessionid=' . (int)$existingFormSessionId);
			$formSessionId = false;
			if ($result !== false) {
				$formSessionId = $existingFormSessionId;
			}
		}

		if (!isId($formSessionId)) {
			return false;
		}

		/**
		 * If we are updating then remove all previous formfieldsession records as the existing
		 * form fields could have changed
		 */
		if (isId($existingFormSessionId)) {
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('formfieldsessions', 'WHERE formfieldsessioniformsessionid=' . (int)$existingFormSessionId . " AND formfieldfieldtype != 'password'");
		}

		$formIdx = array();
		$query = "SELECT *
					FROM [|PREFIX|]formfields
					WHERE formfieldid IN(" . implode(',', $formFieldDataKeys) . ")
					ORDER BY formfieldsort ASC";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

			if (!array_key_exists($row['formfieldid'], $formFieldData)) {
				return false;
			}

			/**
			 * If this is a password already in an existing session WITH a value, then delete its
			 * previous record. If it has no value then DO NOT save it (just skip it)
			 */
			if ($row['formfieldtype'] == 'password' && isId($existingFormSessionId)) {
				if ($formFieldData[$row['formfieldid']] !== '') {
					$GLOBALS['ISC_CLASS_DB']->DeleteQuery('formfieldsessions', 'WHERE formfieldsessioniformsessionid=' . (int)$existingFormSessionId . " AND formfieldfieldid=" . (int)$row['formfieldid']);
				} else {
					continue;
				}
			}

			$savedata = array(
				'formfieldsessioniformsessionid' => $formSessionId,
				'formfieldfieldid' => $row['formfieldid'],
				'formfieldformid' => $row['formfieldformid'],
				'formfieldfieldtype' => $row['formfieldtype'],
				'formfieldfieldlabel' => $row['formfieldlabel'],
				'formfieldfieldvalue' => $formFieldData[$row['formfieldid']]
			);

			/**
			 * Serialize the value as there might be arrays and other scary things in there
			 */
			$savedata['formfieldfieldvalue'] = serialize($savedata['formfieldfieldvalue']);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery('formfieldsessions', $savedata);

			$formIdx[] = $row['formfieldformid'];
		}

		/**
		 * Associate our form IDs for the formsession record
		 */
		if (!empty($formIdx)) {
			$formIdx = array_unique($formIdx);
			$savedata = array(
				'formsessionformidx' => implode(',', $formIdx)
			);

			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('formsessions', $savedata, 'formsessionid=' . (int)$formSessionId);
		}

		return $formSessionId;
	}

	/**
	 * Delete a form session record
	 *
	 * Method will delete a form session record
	 *
	 * @access public
	 * @param int $formSessionId The form session to delete
	 * @return bool TRUE if the form session was deleted, FALSE if not
	 */
	public function deleteFormSession($formSessionId)
	{
		if (!isId($formSessionId)) {
			return false;
		}

		$query = "DELETE f, ff
					FROM [|PREFIX|]formsessions f
						LEFT JOIN [|PREFIX|]formfieldsessions ff ON f.formsessionid = ff.formfieldsessioniformsessionid
					WHERE f.formsessionid = " . (int)$formSessionId;

		return ($GLOBALS['ISC_CLASS_DB']->Query($query) !== false);
	}

	/**
	 * Get the saved form session data
	 *
	 * Method will return an array containing the field ID as the key and the saved form session
	 * data as the value
	 *
	 * @access public
	 * @param int $formSessionId
	 * @param array $fieldIdx An array of field IDs to limit the search with
	 * @param array $formIdx An array of field form IDs to limit the search with
	 * @param bool $fullReturn TRUE to return additional information (formId, label, type), FALSE
	 *                         just for the value. Default is FALSE
	 * @return array The form session data array on success, empty array for no matching session,
	 *               FALSE on failure
	 */
	public function getSavedSessionData($formSessionId, $fieldIdx=array(), $formIdx=array(), $fullReturn=false)
	{
		if (!isId($formSessionId)) {
			return false;
		}

		if (!is_array($fieldIdx)) {
			$fieldIdx = array($fieldIdx);
		}

		if (!is_array($formIdx)) {
			$formIdx = array($formIdx);
		}

		$fieldIdx = array_filter($fieldIdx, 'isId');
		$formIdx = array_filter($formIdx, 'isId');
		$data = array();
		$extraSelect = '';

		if ($fullReturn) {
			$extraSelect = ', formfieldformid, formfieldfieldtype, formfieldfieldlabel';
		}

		$query = "SELECT formfieldfieldid, formfieldfieldvalue " . $extraSelect . "
					FROM [|PREFIX|]formfieldsessions
					WHERE formfieldsessioniformsessionid = " . (int)$formSessionId;

		if (!empty($fieldIdx)) {
			$query .= " AND formfieldfieldid IN (" . implode(',', $fieldIdx) . ")";
		}

		if (!empty($formIdx)) {
			$query .= " AND formfieldformid IN (" . implode(',', $formIdx) . ")";
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if ($fullReturn) {
				$value = array(
							'value' => @unserialize($row['formfieldfieldvalue']),
							'formId' => $row['formfieldformid'],
							'type' => $row['formfieldfieldtype'],
							'label' => $row['formfieldfieldlabel']
				);
			} else {
				$value = @unserialize($row['formfieldfieldvalue']);
			}

			$data[$row['formfieldfieldid']] = $value;
		}

		return $data;
	}

	/**
	 * Find a form ID based on the formsessionid
	 *
	 * Method will return an array containing all the FormIDs that are associated with the
	 * saved form session(s)
	 *
	 * @access public
	 * @param mixed $formSessionIdx The form session ID(s) to search with (either int or array of IDs)
	 * @return array An array containing all the found form IDs, FALSE on failure
	 */
	public function findFormIdBySessionId($formSessionIdx)
	{
		if (isId($formSessionIdx)) {
			$formSessionIdx = array($formSessionIdx);
		}

		$formSessionIdx = array_filter($formSessionIdx, 'isId');

		if (empty($formSessionIdx)) {
			return false;
		}

		$formIdx = array();
		$query = "SELECT formsessionformidx
					FROM [|PREFIX|]formsessions
					WHERE formsessionid IN (" . implode(',', $formSessionIdx) . ")";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$formIdx += explode(',', $row['formsessionformidx']);
		}

		$formIdx = array_unique($formIdx);

		return $formIdx;
	}

	/**
	 * Find a form ID based on the formsessionid
	 *
	 * Method will return an array containing all the FormIDs that are associated with the
	 * field(s)
	 *
	 * @access public
	 * @param mixed $fieldIdx The field ID(s) to search with (either int or array of IDs)
	 * @return array An array containing all the found form IDs, false on failure
	 */
	public function findFormIdByFieldId($fieldIdx)
	{
		if (isId($fieldIdx)) {
			$fieldIdx = array($fieldIdx);
		}

		$fieldIdx = array_filter($fieldIdx, 'isId');

		if (empty($fieldIdx)) {
			return false;
		}

		$formIdx = array();
		$query = "SELECT DISTINCT formfieldformid
					FROM [|PREFIX|]formfields
					WHERE formfieldid IN (" . implode(',', $fieldIdx) . ")";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$formIdx[] = $row['formfieldformid'];
		}

		return $formIdx;
	}

	/**
	 * Map billing field IDs to shipping IDs or vise-versa
	 *
	 * Method will map all the field IDs in $fieldIdx to their matching field IDs based on the
	 * type $typeID
	 *
	 * @access public
	 * @param int $typeId The address type to map from (billing or shipping formID)
	 * @param array $fieldIdx The array of field IDs to map against
	 * @return array An array with the original field IDs as the key and the matching field ID as
	 *               the value on success, FALSE on failure
	 */
	public function mapAddressFieldList($typeId, $fieldIdx)
	{
		if (!isId($typeId) || !is_array($fieldIdx)) {
			return false;
		}

		$fieldIdx = array_filter($fieldIdx, 'isId');

		if (empty($fieldIdx)) {
			return array();
		}

		if ($typeId == FORMFIELDS_FORM_BILLING) {
			$mapToId = FORMFIELDS_FORM_SHIPPING;
		} else {
			$typeId = FORMFIELDS_FORM_SHIPPING;
			$mapToId = FORMFIELDS_FORM_BILLING;
		}

		$mapList = array();

		$query = "SELECT s.formfieldid AS sourcefieldid, t.formfieldid AS targetfieldid
					FROM [|PREFIX|]formfields t
						JOIN [|PREFIX|]formfields s ON t.formfieldlabel = s.formfieldlabel
					WHERE t.formfieldformid = " . $mapToId . " AND s.formfieldformid = " . $typeId . "
						AND s.formfieldid IN(" . implode(',', $fieldIdx) . ")";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$mapList[$row['sourcefieldid']] = $row['targetfieldid'];
		}

		return $mapList;
	}
}