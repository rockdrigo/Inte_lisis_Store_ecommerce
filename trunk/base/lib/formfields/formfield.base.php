<?php

abstract class ISC_FORMFIELD_BASE
{
	protected $fieldId;
	protected $formId;
	protected $label;
	protected $value;
	protected $valueSetFromDB;
	protected $events;
	protected $validation;
	protected $defaultExtraInfo;
	protected $extraInfo;
	protected $extraHiddenArgs;
	protected $isRequired;
	protected $errMsg;

	public $record;

	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @param int $formId The form ID that this form field is associated with
	 * @param array $defaultExtraInfo The default $extraInfo setup for the form field
	 * @param mixed $fieldId The optional form field Id/array
	 * @param bool $copyField If TRUE then this field will copy the field $fieldId EXCEPT for the field ID. Default is FALSE
	 * @return void
	 */
	public function __construct($formId, $defaultExtraInfo, $fieldId='', $copyField=false)
	{
		if (!isId($formId)) {
			trigger_error("Invalid form ID (1st argument) used to load form field " . $this->getFieldType(), E_USER_ERROR);
		}

		if (!is_array($defaultExtraInfo)) {
			trigger_error("Invalid default extra info (2nd argument) used to load form field " . $this->getFieldType(), E_USER_ERROR);
		}

		$this->fieldId = $fieldId;
		$this->formId = $formId;
		$this->label = '';
		$this->value = '';
		$this->valueSetFromDB = false;
		$this->events = array();
		$this->validation = array();
		$this->defaultExtraInfo = $defaultExtraInfo;
		$this->extraInfo = $defaultExtraInfo;
		$this->extraHiddenArgs = array();
		$this->isRequired = null;
		$this->errMsg = array();
		$this->privateField = false;
		$this->record = array();

		$rtn = true;

		/**
		 * Load our info
		 */
		if (isId($fieldId)) {
			$rtn = self::loadFromId($fieldId);
		} else if (is_array($fieldId)) {
			$rtn = self::loadFromArray($fieldId);
		}

		if (!$rtn) {
			trigger_error("Invalid field ID (3rd argument) used to load form field " . $this->getFieldType(), E_USER_ERROR);
		}

		/**
		 * If we are copying this field then reset the variables that should not be copied
		 */
		if ($copyField) {
			$this->label = GetLang('CopyOf') . $this->label;
			$this->fieldId = '';
			$this->privateField = '';
			$this->record = array();
		}
	}

	/**
	 * Check to see if the form field is loaded
	 *
	 * Method will check to see if the form field is loaded with all the form data
	 *
	 * @access protected
	 * @return bool TRUE if the form field is loaded, FALSE if not
	 */
	protected function isLoaded()
	{
		if (!isId($this->fieldId)) {
			return false;
		}

		return true;
	}

	/**
	 * Load the form field data from the database
	 *
	 * Method will load the form field data based on the database record $fieldId
	 *
	 * @access public
	 * @param int $fieldId The form field Id
	 * @return bool TRUE if the record was found and loaded successfully, FALSE otherwise
	 */
	public function loadFromId($fieldId)
	{
		if (!isId($fieldId)) {
			return false;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]formfields WHERE formfieldid=" . (int)$fieldId);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if (!$row) {
			return false;
		}

		return self::loadFromArray($row);
	}

	/**
	 * Load the form field data from an existing database record array
	 *
	 * Method will load the form field data based on the passed database record array
	 *
	 * @access public
	 * @param array $fieldArray The form field record array
	 * @return bool TRUE if the record was loaded successfully, FALSE otherwise
	 */
	public function loadFromArray($fieldArray)
	{
		if (!is_array($fieldArray) || !array_key_exists('formfieldid', $fieldArray)) {
			return false;
		}

		$this->fieldId = (int)$fieldArray['formfieldid'];
		$this->isRequired = (bool)$fieldArray['formfieldisrequired'];
		$this->record = $fieldArray;
		$this->label = (string)$fieldArray['formfieldlabel'];

		/**
		 * Merge our extrInfo data with the default one
		 */
		$extraInfo = @unserialize($fieldArray['formfieldextrainfo']);

		if (!is_array($extraInfo)) {
			$extraInfo = array();
		}

		$extraInfo = array_merge($this->extraInfo, $extraInfo);
		$this->extraInfo = $extraInfo;

		return true;
	}

	/**
	 * Get the requested (POST or GET) value of a field
	 *
	 * Method will search through all the POST and GET array values are return the field
	 * value if found. Method will the POST and GET arrays in order based on the PHP INI
	 * value 'variables_order' (the GPC order)
	 *
	 * @access public
	 * @param string $fieldName Value will be passed to self::getFieldName() to generate the
	 *                          field name to search for. Default is empty string
	 * @return mixed The value of the form field, if found. Empty string if not found
	 */
	public function getFieldRequestValue($fieldName='')
	{
		$field = $this->getFieldName($fieldName);
		$order = str_split(strtolower(ini_get('variables_order')));
		$global = null;

		/**
		 * Our name could have sub-scripts in them (basically it could be an array)
		 */
		if (preg_match('/[^\]]+\[[^\]]+\]/', $field)) {
			$name = array_filter(preg_split('/\]?\[/', $field));
			$name[count($name)-1] = substr($name[count($name)-1], 0, -1);
		} else {
			$name = array($field);
		}

		foreach ($order as $type) {
			switch (isc_strtolower($type)) {
				case 'g':
					$global =& $_GET;
					break;

				case 'p':
					$global =& $_POST;
					break;

				case 'c':
					$global =& $_COOKIE;
					break;
			}

			if (!is_array($global)) {
				continue;
			}

			/**
			 * Look in the global. If the full array path exists then we have a match
			 */
			for ($i=0; $i<count($name); $i++) {
				if (!array_key_exists($name[$i], $global)) {
					break;
				}

				$global =& $global[$name[$i]];
			}

			if ($i >= count($name)) {
				return $global;
			}
		}

		return '';
	}

	/**
	 * Get the child field information array
	 *
	 * Method will return the child field information array
	 *
	 * @access public
	 * @return array An array conatining information about the child field
	 */
	public function getFieldInfoArray()
	{
		return call_user_func(array(get_class($this), 'getDetails'));
	}

	/**
	 * Get the child field type
	 *
	 * Method will return the child field type
	 *
	 * @access public
	 * @return string The child field type
	 */
	public function getFieldType()
	{
		return isc_strtolower(substr(get_class($this), 14));
	}

	/**
	 * Get the form field field id
	 *
	 * Method will return the form field field id
	 *
	 * @access public
	 * @param string $insert The optional string to insert in the id after 'Field'
	 * @return string The form field id
	 */
	public function getFieldId($insert='')
	{
		if (!self::isLoaded()) {
			return false;
		}

		return ___FORM_DEFAULT_NAME___ . $insert . '_' . $this->fieldId;
	}

	/**
	* Get the form field's label
	*
	* @access public
	* @return string
	*/
	public function getFieldLabel()
	{
		return $this->label;
	}

	/**
	 * Get the form field field name
	 *
	 * Method will return the form field field name
	 *
	 * @access public
	 * @param string $insert The optional string to insert in the name after 'Field'
	 * @return string The form field name
	 */
	public function getFieldName($insert='')
	{
		if (!self::isLoaded()) {
			return false;
		}

		/**
		 * Field name's will need to have the formId in it
		 */
		return ___FORM_DEFAULT_NAME___ . $insert . '[' . $this->formId . '][' . $this->fieldId . ']';
	}

	/**
	* Get the form field private id, if any
	*
	* @return string The form field's private id, or a blank sting if the field does not have one
	*/
	public function getFieldPrivateId()
	{
		if (!self::isLoaded()) {
			return false;
		}

		return $this->record['formfieldprivateid'];
	}

	/**
	 * Get the form field container id
	 *
	 * Method will return the form field container id
	 *
	 * @access public
	 * @return string The form field container id
	 */
	public function getContainerId()
	{
		if (!self::isLoaded()) {
			return false;
		}

		return ___FORM_DEFAULT_NAME___ . 'FieldContainer_' . $this->formId . '_' . $this->fieldId;
	}

	/**
	 * Get the field value
	 *
	 * Method will get the field value
	 *
	 * @access public
	 * @return mixed The default value
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Set the field value
	 *
	 * Method will set the field value, overriding the existing one
	 *
	 * @access public
	 * @param mixed $value The default value to set
	 * @param bool $setFromDB TRUE to specify that this value is from the DB, FALSE from the request.
	 *                        Default is FALSE
	 */
	public function setValue($value, $setFromDB=false)
	{
		$this->value = $value;
		$this->valueSetFromDB = $setFromDB;
	}

	/**
	 * Add in any hidden inputs for the frontend
	 *
	 * Method will add in any new hidden inputs for the frontend
	 *
	 * @access proteected
	 * @param string $key The hidden input postfix for the hidden class
	 * @param string $val The value for the hidden input
	 * @param string $name The optional name for this hidden element. Leav blank for no name. Default
	 *                     is empty string (no name)
	 * @return bool TRUE if the value was added, FALSE on error
	 */
	protected function addExtraHiddenArgs($key, $val, $name='')
	{
		if ($key == '') {
			return false;
		}

		$this->extraHiddenArgs[$key] = array(
										'value' => $val,
										'name' => $name
		);

		return true;
	}

	/**
	 * Set an event handler for this field
	 *
	 * Method will set an event handler for this fields. You can call this method multiple times
	 * using the same event. Each JS function passed will have the JS Field as the first argument
	 *
	 * @access public
	 * @param string $event The event to bind the field object with
	 * @param string $func The JS function to call
	 * @param array $args Optional additional arguments to pass to the JS function
	 * @return bool TRUE if the event was added, FALSE on error
	 */
	public function addEventHandler($event, $func, $args=array())
	{
		if ($event == '' || $func == '') {
			return false;
		}

		if (!array_key_exists($event, $this->events)) {
			$this->events[$event] = array();
		}

		/**
		 * Add in out field ID at the start
		 */
		$args = array('fieldId' => $this->fieldId) + $args;

		$this->events[$event][] = array(
			'func' => $func,
			'args' => $args
		);

		return true;
	}

	/**
	 * Add validation callback for this field
	 *
	 * Method will add a validating callback function for this field. The first argument is the
	 * type of validation, which can be one of:
	 *
	 *     'required': Marks the field value as required. Will get set automatically based upon
	 *                 the $this->isRequired flag so you don;t have to worry about this one.
	 *
	 *     'regex'   : Sets a regular expression for the field value to match. The regex can be
	 *                 passed in as the 4th argument of this method
	 *
	 *     'func'    : Calls a JS function for the validation. The 3rd argument will be the actual
	 *                 JS function to call. The first argument for this JS function will be the
	 *                 field JS object. Other arguments can be passed in as the 3rd argument of
	 *                 this method
	 *
	 * @access public
	 * @param string $type The type of validation (as explained above)
	 * @param string $errmsg The error message to alert if the validation fails
	 * @param mised $args Either the regex string or the optional arguments array
	 * @return bool TRUE if the validation was added successfully, FALSE if not
	 */
	public function addValidation($type, $errmsg, $func='', $args=array())
	{
		$validTypes = array('required', 'regex', 'func');

		if ($type == '' || !in_array($type, $validTypes)) {
			return false;
		}

		/**
		 * We need an error message, or we'll just alert an empty popup
		 */
		if (trim($errmsg) == '') {
			return false;
		}

		/**
		 * The regex MUST have a regex pattern
		 */
		if ($type == 'regex' && (is_array($args) || trim($args) == '')) {
			return false;
		}

		/**
		 * Likewise, the JS function MUST have an actual function to execute
		 */
		if ($type == 'func' && $func == '') {
			return false;
		}

		if (!array_key_exists($type, $this->validation)) {
			$this->validation[$type] = array();
		}

		$this->validation[$type][] = array(
			'errmsg' => $errmsg,
			'func' => $func,
			'args' => $args
		);

		return true;
	}

	/**
	 * Run validation on the server side
	 *
	 * Method will run the validation on the server side (will not run the JS function type) and return
	 * the result
	 *
	 * @access public
	 * @param string &$errmsg The error message if the validation fails
	 * @return bool TRUE if the validation was successful, FALSE if it failed
	 */
	public function runValidation(&$errmsg)
	{
		/**
		 * If we are required then shove in the validation
		 */
		if ($this->isRequired) {
			if ($this->getFieldType() == 'checkboxselect' || $this->getFieldType() == 'radioselect') {
				$msg = GetLang('CustomFieldsValidationOptionRequired');
			} else {
				$msg = GetLang('CustomFieldsValidationRequired');
			}

			$this->addValidation('required', sprintf($msg, $this->label));
		}

		foreach ($this->validation as $type => $validations) {

			$value = $this->getValue();
			foreach ($validations as $validation) {

				switch (strtolower($type)) {
					case 'required':
						if ((is_scalar($value) && $value == '') || (is_array($value) && empty($value))) {
							$errmsg = $validation['errmsg'];
							return false;
						};
						break;

					case 'regex':
						if ($validation['args'] == '') {
							break;
						}

						$pattern = '/' . $validation['args'] . '/';

						if ($value == '' || !preg_match($pattern, $value)) {
							$errmsg = $validation['errmsg'];
							return false;
						};
						break;

					case 'func':
						/**
						 * This we cannot run as it is referring to a JS function, so just break
						 */
						break;
				}
			}
		}

		return true;
	}

	/**
	 * Set the required flag
	 *
	 * Method will set the field required flag
	 *
	 * @access public
	 * @param bool $required TRUE to set the field as required, FALSE not to. Default is TRUE
	 */
	public function setRequired($required=true)
	{
		$this->isRequired = $required;
	}

	/**
	 * Build the form field HTML
	 *
	 * Method will build the form field HTML string. All global replacements will need
	 * to be defined beforehand
	 *
	 * @access private
	 * @param bool $loadForFrontend The optional flag to build either for the frontend or
	 *                              the backend. Default is TRUE (frontend)
	 * @param string $altTemplate The alternative template to display. Default will be the field's
	 *                            template itself (Only set this if you know what you're doing)
	 * @return string The form field HTML string on success, FALSE if the template file could
	 *                not be found/read
	 */
	private function buildFormField($loadForFrontend=true, $altTemplate='')
	{
		if ($altTemplate !== '') {
			$templateFile = $altTemplate;
		} else {
			$templateFile = $this->getFieldType();
		}

		if ($loadForFrontend) {
			$templateFile .= '.frontend.html';
		}
		else {
			$templateFile .= '.backend.html';
		}

		return $this->getTemplateClass()->render($templateFile);
	}

	/**
	 * Build the form field event JavaScript for the frontend
	 *
	 * Method will build the JavaScript events for the form field
	 *
	 * @access public
	 * @return string The event JavaScript if there is any, empty string if not
	 */
	public function loadEventsForFrontend()
	{
		if (!self::isLoaded()) {
			return '';
		}

		if (!is_array($this->events) || empty($this->events)) {
			return '';
		}

		$eventJS = '';

		foreach ($this->events as $type => $events) {
			foreach ($events as $event) {
				$func = $event['func'];
				$args = isc_json_encode($event['args']);
				$eventJS .= '$(FormField.GetField(' . $this->fieldId . ')).live("' . $type . '", ' . $args . ', ' . $func . ');' . "\n";
			}
		}

		return $eventJS;
	}

	/**
	 * Build the form field HTML for the frontend
	 *
	 * Method will build the form field HTML for the frontend. The form field MUST be loaded first!
	 *
	 * @access protected
	 * @param string $altTemplate The alternative template to display. Default will be the field's
	 *                            template itself (Only set this if you know what you're doing)
	 * @return string The form field HTML on success, FALSE if the form field is not loaded or template file could no be found/read
	 */
	protected function buildForFrontend($altTemplate='')
	{
		if (!self::isLoaded()) {
			return false;
		}

		/**
		 * Add in our needed hidden arguments
		 */
		$this->addExtraHiddenArgs('Id', $this->fieldId);
		$this->addExtraHiddenArgs('FormId', $this->formId);
		$this->addExtraHiddenArgs('Type', $this->getFieldType());
		$this->addExtraHiddenArgs('PrivateId', $this->record['formfieldprivateid']);

		/**
		 * Now build them
		 */
		$GLOBALS['FormFieldExtraHidden'] = '';

		foreach ($this->extraHiddenArgs as $key => $val) {
			$GLOBALS['FormFieldExtraHidden'] .= '<input type="hidden" class="FormField' . isc_html_escape($key) . '"';
			if ($val['name'] !== '') {
				$GLOBALS['FormFieldExtraHidden'] .= ' name="' . $this->getFieldName($val['name']) . '"';
			}
			$GLOBALS['FormFieldExtraHidden'] .= ' value="' . isc_html_escape($val['value']) . '" />';
		}

		/**
		 * Add in the colon after the label if we do not have a colon or a question mark at the end
		 */
		$label = trim($this->label);
		if (substr($label, -1) !== '?' && substr($label, -1) !== ':') {
			$label = $label . ':';
		}

		$GLOBALS['FormFieldLabel'] = $label;

		/**
		 * Are we required?
		 */
		if ($this->isRequired) {
			$GLOBALS['FormFieldRequiredVisable'] = 'visible';
		} else {
			$GLOBALS['FormFieldRequiredVisable'] = 'hidden';
		}

		/**
		 * Add this field to our usedField list so we can build the JS for it
		 */
		$GLOBALS['ISC_CLASS_FORM']->addFormFieldUsed($this);

		$GLOBALS['FormFieldFieldData'] = $this->buildFormField(true, $altTemplate);

		if(defined('ISC_ADMIN_CP')) {
			return $this->getTemplateClass()->render('formfield.frontend.admin.html');
		}
		else {
			return $this->getTemplateClass()->render('formfield.frontend.html');
		}
	}

	/**
	 * Build the form field HTML for the backend
	 *
	 * Method will build the form field HTML for the backend. Form field does not need to be loaded, otherwise you could not
	 * create any new instances.
	 *
	 * @access protected
	 * @return string The form field HTML on success, FALSE template file could no be found/read
	 */
	protected function buildForBackend()
	{
		$GLOBALS['FormFieldID'] = $this->fieldId;
		$GLOBALS['FormFieldFormID'] = $this->formId;
		$GLOBALS['FormFieldType'] = $this->getFieldType();
		$GLOBALS['FormFieldName'] = isc_html_escape($this->label);
		$GLOBALS['FormFieldIsRequiredChecked'] = '';

		if ($this->isRequired) {
			$GLOBALS['FormFieldIsRequiredChecked'] = ' checked="1"';
		}

		/**
		 * If we are immutable then disable the 'is required' option
		 */
		if (isset($this->record['formfieldisimmutable']) && (int)$this->record['formfieldisimmutable'] === 1) {
			$GLOBALS['FormFieldImmutableFlag'] = ' readonly="1"';
			$GLOBALS['FormFieldImmutableRequired'] = '&nbsp;&nbsp;';
		} else {
			$GLOBALS['FormFieldImmutableFlag'] = '';
			$GLOBALS['FormFieldImmutableRequired'] = '<span class="Required">*</span>';
		}

		$info = $this->getFieldInfoArray();
		if (!$this->isLoaded()) {
			$GLOBALS['FormFieldSetupPopupHeading'] = sprintf(GetLang('FormFieldPopupHeadingCreate'), $info['name']);
		} else {
			$GLOBALS['FormFieldSetupPopupHeading'] = sprintf(GetLang('FormFieldPopupHeadingEdit'), $info['name']);
		}

		$GLOBALS['FormFieldTabWorkSpace'] = $this->buildFormField(false);

		return $this->getTemplateClass()->render('formfield.backend.html');
	}

	protected function getTemplateClass()
	{
		$templatePath = dirname(__FILE__).'/templates/';
		$template = Interspire_Template::getInstance('formfields', $templatePath, array(
			'cache' => getAdminTwigTemplateCacheDirectory(),
			'auto_reload' => true
		));
		return $template;
	}

	/**
	 * Save the field record
	 *
	 * Method will save the field record into the database
	 *
	 * @access protected
	 * @param array $data The field data record set
	 * @param string &$error The referenced variable to store the error in
	 * @return bool TRUE if the field was saved successfully, FALSE if not
	 */
	public function saveForBackend($data, &$error)
	{
		if (!is_array($data)) {
			return false;
		}

		/**
		 * Check for the required fields
		 */
		if (!$this->isLoaded() && (!array_key_exists('formId', $data) || !isId($data['formId']))) {
			return false;
		}

		if ($this->isLoaded() && (!array_key_exists('fieldId', $data) || !isId($data['fieldId']))) {
			return false;
		}

		if (!array_key_exists('name', $data) || trim($data['name']) == '') {
			$error = GetLang('FormFieldSetupInvalidName');
			return false;
		}

		/**
		 * Do a quick check to see if this field name is already in use for this form
		 */
		$query = "SELECT *
					FROM [|PREFIX|]formfields
					WHERE formfieldformid=" . (int)$data['formId'] . " AND formfieldlabel='" .  $GLOBALS['ISC_CLASS_DB']->Quote($data['name']) . "'";

		if ($this->isLoaded()) {
			$query .= " AND formfieldid != " . (int)$data['fieldId'];
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if ($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
			$error = GetLang('FormFieldSetupDuplicateName');
			return false;
		}

		/**
		 * Now merge the sent extra info with the default setup
		 */
		if (!array_key_exists('extraInfo', $data) || !is_array($data['extraInfo'])) {
			$data['extraInfo'] = array();
		}

		$data['extraInfo'] = array_merge($this->defaultExtraInfo, $data['extraInfo']);

		/**
		 * OK, no we save the data and return either the new field or a bool TRUE if all is good
		 */
		$savedata = array(
			'formfieldlabel' => $data['name'],
			'formfieldextrainfo' => serialize($data['extraInfo']),
			'formfieldlastmodified' => time()
		);

		/**
		 * Do not modify this if we are immutable
		 */
		$editable = (!isset($this->record['formfieldisimmutable']) || (int)$this->record['formfieldisimmutable'] !== 1);
		if (!$this->isLoaded() || $editable) {
			if (array_key_exists('isRequired', $data)) {
				$savedata['formfieldisrequired'] = (int)$data['isRequired'];
			} else {
				$savedata['formfieldisrequired'] = 0;
			}
		}

		/**
		 * Now the saving part
		 */
		if (!$this->isLoaded()) {
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT MAX(formfieldsort) AS totalsort FROM [|PREFIX|]formfields WHERE formfieldformid=" . (int)$data['formId']);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			$savedata['formfieldformid'] = $data['formId'];
			$savedata['formfieldtype'] = $this->getFieldType();
			$savedata['formfieldsort'] = $row['totalsort']+1;

			$rtn = $GLOBALS['ISC_CLASS_DB']->InsertQuery('formfields', $savedata);
		} else {
			$rtn = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('formfields', $savedata, "formfieldid=" . (int)$data['fieldId']);
		}

		if ($rtn === false) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			return false;
		}

		/**
		 * All good, now we get out of here
		 */
		if (isId($rtn)) {
			return $rtn;
		} else {
			return true;
		}
	}

	/**
	* Returns a class name representing the type of data this field presents for email integration.
	*
	* @return string A name of one of Interspire_EmailIntegration_Field_* types, or false if not supported as field for sending to email providers.
	*/
	abstract public function getEmailIntegrationFieldClassName();
}
