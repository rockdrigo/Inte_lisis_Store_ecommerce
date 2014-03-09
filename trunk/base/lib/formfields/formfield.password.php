<?php

class ISC_FORMFIELD_PASSWORD extends ISC_FORMFIELD_BASE
{
	private $setLeaveBlankLabel;

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
			'size' => '',
			'maxlength' => '',
			'class' => 'Textbox Field200',
			'style' => '',
		);

		$this->setLeaveBlankLabel = true;

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
			'name' => GetLang('FormFieldPasswordName'),
			'desc' => GetLang('FormFieldPasswordDesc'),
			'img' => 'passwordfield.png',
		);
	}

	/**
	 * Set the 'Leave password blank' label to display or not
	 *
	 * Method will set the 'Leave password blank' label to display or not
	 *
	 * @access public
	 * @param bool $blank TRUE to set the label to display, FALSE not to. Default is TRUE
	 */
	public function setLeaveBlankLabel($blank=true)
	{
		$this->setLeaveBlankLabel = (bool)$blank;
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
		 * Because this can be left unchanged without passing a value to it (frontend only),
		 * then we cannot really set this as required if we originally have a value for it
		 */
		if (!defined('ISC_ADMIN_CP') && parent::getFieldRequestValue('AlreadySet') == '1') {
			$this->setRequired(false);
		}

		if (!parent::runValidation($errmsg)) {
			return false;
		}

		return true;
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
		 * Create all the form element arguments
		 */
		foreach ($tmpArgs as $arg => $val) {
			if ($val == '') {
				continue;
			}

			$args[] = $arg . '="' . isc_html_escape($val) . '"';
		}

		if ($this->value == '' && $this->extraInfo['defaultvalue'] !== '') {
			$defaultValue = $this->extraInfo['defaultvalue'];
		} else {
			$defaultValue = $this->value;
		}

		/**
		 * If we are in the admin, don't show leave blank msg.
		 */
		if (defined('ISC_ADMIN_CP') && ISC_ADMIN_CP) {
			$this->setLeaveBlankLabel = false;
			$GLOBALS['FormFieldValue'] = isc_html_escape($defaultValue);

		/**
		 * Else because this can be left unchanged without passing a value to it (frontend only),
		 * then we cannot really set this as required if we originally have a value for it
		 */
		} else {
			$alreadySet = parent::getFieldRequestValue('AlreadySet');
			if ($this->valueSetFromDB || $alreadySet == '1') {
				$this->addExtraHiddenArgs('AlreadySet', '1', 'AlreadySet');
			}
		}

		/**
		 * Are we displaying the 'leave password blank' message?
		 */
		if (!$this->setLeaveBlankLabel) {
			$GLOBALS['FormFieldHidePasswordMsg'] = 'none';
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
		$GLOBALS['FormFieldSize'] = isc_html_escape($this->extraInfo['size']);
		$GLOBALS['FormFieldMaxLength'] = isc_html_escape($this->extraInfo['maxlength']);
		$GLOBALS['FormFieldClass'] = isc_html_escape($this->extraInfo['class']);
		$GLOBALS['FormFieldStyle'] = isc_html_escape($this->extraInfo['style']);

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
		// password should not be sent to email providers; it's both a security risk and (should be) hashed anyway so pointless
		return false;
	}
}
