<?php

class ISC_FORMFIELD_SELECTORTEXT extends ISC_FORMFIELD_BASE
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
			'chooseprefix' => '',
			'options' => array(),
			'size' => '',
			'maxlength' => ''
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
			'name' => GetLang('FormFieldSelectOrTextName'),
			'desc' => GetLang('FormFieldSelectOrTextDesc'),
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
		/**
		 * If this was a textbox when it was submitted then turn of the required flag
		 */
		$isText = $this->getFieldRequestValue('IsText');

		if ($isText !== '' && (int)$isText == 1) {
			$this->setRequired(false);
		}

		return parent::runValidation($errmsg);
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

		$options = array();
		if (!empty($this->extraInfo['options'])) {
			$options = $this->extraInfo['options'];
		}

		$selectOptions = '';
		if ($this->extraInfo['chooseprefix'] !== '') {
			$selectOptions .= '<option value="">' . isc_html_escape($this->extraInfo['chooseprefix']) . '</option>' . "\n";
			$this->addExtraHiddenArgs('ChoosePrefix', $this->extraInfo['chooseprefix']);
		}

		foreach ($options as $val) {
			if (trim($val) == '') {
				continue;
			}

			$selected = '';
			if ($val == (string)$this->value) {
				$selected = 'selected="selected"';
			}

			$selectOptions .= '<option value="'.isc_html_escape($val).'" '.$selected.'>'.isc_html_escape($val).'</option>'."\n";
		}

		/**
		 * Determine the text based value of the field
		 */
		if ($this->value == '' && !empty($options)) {
			if ($this->extraInfo['chooseprefix'] == '') {
				reset($options);
				$defaultValue = current($options);
			} else {
				$defaultValue = '';
			}
		} else {
			$defaultValue = $this->value;
		}

		$defaultValue = isc_html_escape($defaultValue);

		$fieldName = isc_html_escape($this->GetFieldName());
		$fieldId = isc_html_escape($this->GetFieldId());
		$fieldClass = isc_html_escape($this->extraInfo['class']);
		$fieldStyle = isc_html_escape($this->extraInfo['style']);

		$additionalTextOptions = '';
		if ($this->extraInfo['maxlength'] > 0) {
			$additionalTextOptions .= ' maxlength="'.(int)$this->extraInfo['maxlength'].'"';
		}

		/**
		 * Build only a text box if there are no options
		 */
		if (empty($options)) {
			$formField = '<input type="text" name="' . $fieldName . '" id="' . $fieldId . '" class="FormField JSHidden ' . $fieldClass . '" style="display: none; ' . $fieldStyle . '" value="' . $defaultValue . '" ' . $additionalTextOptions . '/>';

			/**
			 * Also too, add this hidden in so we can tell if it was a textbox or selectbox on the
			 * PHP server side
			 */
			$formField .= '<input type="hidden" name="' . isc_html_escape($this->GetFieldName('IsText')) . '" value="1" />';

		/**
		 * Else build the select box
		 */
		} else {
			$formField = '<select name="' . $fieldName . '" id="' . $fieldId . '" class="FormField JSHidden ' . $fieldClass . '" style="display: none; ' . $fieldStyle . '">';
			$formField .= $selectOptions;
			$formField .= '</select>';
		}

		$GLOBALS['FormField'] = $formField;

		// Set up template variables for the version when users have Javascript disabled
		$GLOBALS['FormFieldName'] = $fieldName;
		$GLOBALS['FormFieldId'] = $fieldId;
		$GLOBALS['FormFieldClass'] = $fieldClass;
		$GLOBALS['FormFieldStyle'] = $fieldStyle;
		$GLOBALS['FormFieldValue'] = $defaultValue;
		$GLOBALS['FormFieldTextOptions'] = $additionalTextOptions;

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
		$GLOBALS['FormFieldChoosePrefix'] = isc_html_escape($this->extraInfo['chooseprefix']);
		$GLOBALS['FormFieldSelectOptions'] = trim(implode("\n", $this->extraInfo['options']));

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
