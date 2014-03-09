<?php

class ISC_FORMFIELD_SINGLESELECT extends ISC_FORMFIELD_BASE
{
	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @param mixed $fieldId The optional form field Id/array
	 * @param bool $copyField If TRUE then this field will copy the field $fieldId EXCEPT for the field ID. Default is FALSE
	 * @return void
	 */
	public function __construct($formId, $fieldId='', $copyField=false)
	{
		$defaultExtraInfo = array(
			'class' => 'Field200',
			'style' => '',
			'chooseprefix' => GetLang('FormFieldSetupChoosePrefixDefault'),
			'options' => array()
		);

		parent::__construct($formId, $defaultExtraInfo, $fieldId, $copyField);
	}

	/**
	 * Get the form field description
	 *
	 * Static method will return an array with the form field name and description as the elements
	 *
	 * @access public
	 * @return array The description array
	 */
	public static function getDetails()
	{
		return array(
			'name' => GetLang('FormFieldSingleSelectName'),
			'desc' => GetLang('FormFieldSingleSelectDesc'),
			'img' => 'picklist.png',
		);
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
		if (!parent::runValidation($errmsg)) {
			return false;
		}

		if ($this->getValue() == '') {
			return true;
		}

		/**
		 * Just need to check that all our selected values actually existing within our options array
		 */
		if (empty($this->extraInfo['options'])) {
			return true;
		}

		if (!Store_Array::inArrayCI($this->getValue(), $this->extraInfo['options'])) {
			$errmsg = sprintf(GetLang('CustomFieldsValidationInvalidSelectOption'), $this->label);
			return false;
		}

		return true;
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
	 * @param bool $assignRealValue TRUE to filter out any values that is not in the options array,
	 *                              FALSE to set as is. Default is TRUE
	 */
	public function setValue($value, $setFromDB=false, $assignRealValue=true)
	{
		if ($assignRealValue && !empty($this->extraInfo['options'])) {
			$index = Store_Array::searchCI($value, $this->extraInfo['options']);

			if ($index !== false) {
				$value = $this->extraInfo['options'][$index];
			} else {
				$value = '';
			}
		}

		parent::setValue($value, $setFromDB);
	}

	/**
	 * Set the select options
	 *
	 * Method will set the select option for the frontend select box, overriding any perviously set options
	 *
	 * @access public
	 * @param array $options The options array with the key as the options value and the value as the options text
	 * @return bool TRUE if the options were set, FALSE if options were not an array
	 */
	public function setOptions($options)
	{
		if (!is_array($options)) {
			return false;
		}

		$options = array_filter($options);

		if (empty($options)) {
			return false;
		}

		$this->extraInfo['options'] = $options;
	}

	/**
	 * Build the frontend HTML for the form field
	 *
	 * Method will build and return the frontend HTML of the loaded form field. The form field must be
	 * loaded before hand
	 *
	 * @access public
	 * @return string The form field HTML if the form field was loaded beforehand, FALSE if not
	 */
	public function loadForFrontend()
	{
		if (!$this->isLoaded()) {
			return false;
		}

		/**
		 * Look for the 'options' array to construct the HTML options with
		 */
		$options = array();
		if (!empty($this->extraInfo['options'])) {
			$options = $this->extraInfo['options'];
		}

		$GLOBALS['FormFieldOptions'] = '';
		if ($this->extraInfo['chooseprefix'] !== '') {
			$GLOBALS['FormFieldOptions'] .= '<option value="">' . isc_html_escape($this->extraInfo['chooseprefix']) . '</option>' . "\n";
			$this->addExtraHiddenArgs('ChoosePrefix', $this->extraInfo['chooseprefix']);
		}

		foreach ($options as $val) {
			if (trim($val) == '') {
				continue;
			}

			$GLOBALS['FormFieldOptions'] .= '<option value="' . isc_html_escape($val) . '"';

			if ($val == (string)$this->value) {
				$GLOBALS['FormFieldOptions'] .= ' selected="selected"';
			}

			$GLOBALS['FormFieldOptions'] .= '>' . isc_html_escape($val) . '</option>' . "\n";
		}

		$args = array();
		$tmpArgs = $this->extraInfo;
		unset($tmpArgs['chooseprefix']);
		unset($tmpArgs['options']);

		/**
		 * Add in the needed formfield class name
		 */
		if (!isset($tmpArgs['class'])) {
			$tmpArgs['class'] = '';
		}

		$tmpArgs['class'] = trim($tmpArgs['class'] . ' FormField');

		foreach ($tmpArgs as $arg => $val) {
			$args[] = $arg . '="' . isc_html_escape($val) . '"';
		}

		$GLOBALS['FormFieldDefaultArgs'] = implode(' ', $args);
		$GLOBALS['FormFieldDefaultArgs'] .= ' id="' . isc_html_escape($this->getFieldId()) . '" name="' . isc_html_escape($this->getFieldName()) . '" ';

		return $this->buildForFrontend();
	}

	/**
	 * Build the backend HTML for the form field
	 *
	 * Method will build and return the backend HTML of the form field
	 *
	 * @access public
	 * @return string The backend form field HTML
	 */
	public function loadForBackend()
	{
		$GLOBALS['FormFieldClass'] = isc_html_escape($this->extraInfo['class']);
		$GLOBALS['FormFieldStyle'] = isc_html_escape($this->extraInfo['style']);
		$GLOBALS['FormFieldChoosePrefix'] = isc_html_escape($this->extraInfo['chooseprefix']);
		$GLOBALS['FormFieldOptions'] = trim(implode("\n", $this->extraInfo['options']));

		return parent::buildForBackend();
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
		return parent::saveForBackend($data, $error);
	}

	/**
	* Returns a class name representing the type of data this field presents for email integration.
	*
	* @return string A name of one of Interspire_EmailIntegration_Field_* types, or false if not supported as field for sending to email providers.
	*/
	public function getEmailIntegrationFieldClassName()
	{
		return 'Interspire_EmailIntegration_Field_String';
	}
}
