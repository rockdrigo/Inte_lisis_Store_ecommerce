<?php

class ISC_FORMFIELD_MULTILINE extends ISC_FORMFIELD_BASE
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
			'defaultvalue' => '',
			'rows' => '5',
			'cols' => '',
			'class' => 'Textbox Field200',
			'style' => '',
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
			'name' => GetLang('FormFieldMultiLineName'),
			'desc' => GetLang('FormFieldMultiLineDesc'),
			'img' => 'multitext.png',
		);
	}

	/**
	 * Build the frontend HTML for the form field
	 *
	 * Method will build and return the frontend HTML of the loaded form field. The form field must be
	 * loaded before hand
	 *
	 * @access public
	 * @return string The frontend form field HTML if the form field was loaded beforehand, FALSE if not
	 */
	public function loadForFrontend()
	{
		if (!$this->isLoaded()) {
			return false;
		}

		$args = array();
		$tmpArgs = $this->extraInfo;
		unset($tmpArgs['defaultvalue']);

		/**
		 * Add in the needed formfield class name
		 */
		if (!isset($tmpArgs['class'])) {
			$tmpArgs['class'] = '';
		}

		$tmpArgs['class'] = trim($tmpArgs['class'] . ' FormField');

		/**
		 * Give a default of 5 rows
		 */
		if (!isset($tmpArgs['rows']) || $tmpArgs['rows'] == '') {
			$tmpArgs['rows'] = 5;
		}

		/**
		 * Create all the form element arguments
		 */
		foreach ($tmpArgs as $arg => $val) {
			if ($val == '') {
				continue;
			}

			$args[] = $arg . '="' . isc_html_escape($val) . '"';
		}

		/**
		 * Set the value
		 */
		if ($this->value == '' && $this->extraInfo['defaultvalue'] !== '') {
			$defaultValue = $this->extraInfo['defaultvalue'];
		} else {
			$defaultValue = $this->value;
		}

		$GLOBALS['FormFieldValue'] = isc_html_escape($defaultValue);
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
		$GLOBALS['FormFieldRows'] = isc_html_escape($this->extraInfo['rows']);
		$GLOBALS['FormFieldCols'] = isc_html_escape($this->extraInfo['cols']);
		$GLOBALS['FormFieldClass'] = isc_html_escape($this->extraInfo['class']);
		$GLOBALS['FormFieldStyle'] = isc_html_escape($this->extraInfo['style']);
		$GLOBALS['FormFieldDefaultValue'] = isc_html_escape($this->extraInfo['defaultvalue']);

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
