<?php

class ISC_FORMFIELD_DATECHOOSER extends ISC_FORMFIELD_BASE
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
			'class' => '',
			'style' => '',
			'defaultvalue' => '',
			'limitfrom' => '',
			'limitto' => ''
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
			'name' => GetLang('FormFieldDateChooserName'),
			'desc' => GetLang('FormFieldDateChooserDesc'),
			'img' => 'datefield.png',
		);
	}

	/**
	 * Get the requested (POST or GET) value of a field
	 *
	 * Method will search through all the POST and GET array values are return the field
	 * value if found. Method will the POST and GET arrays in order based on the PHP INI
	 * value 'variables_order' (the GPC order)
	 *
	 * @access public
	 * @return mixed The value of the form field, if found. Empty string if not found
	 */
	public function getFieldRequestValue()
	{
		$day = parent::getFieldRequestValue('Day');
		$month = parent::getFieldRequestValue('Month');
		$year = parent::getFieldRequestValue('Year');

		if ($day == '' || $month == '' || $year == '') {
			return '';
		} else {
			return date('Y-m-d', mktime(1, 1, 1, $month, $day, $year));
		}
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

		$value = $this->getValue();

		if ($value == '') {
			return true;
		}

		$value = explode('-', $value);
		$value = mktime(1, 1, 1, (int)$value[1], (int)$value[2], (int)$value[0]);

		if ($this->extraInfo['limitfrom'] !== '') {
			$limitfrom = explode('-', $this->extraInfo['limitfrom']);
			$limitfrom = mktime(1, 1, 1, (int)$limitfrom[1], (int)$limitfrom[2], (int)$limitfrom[0]);

			if ($value < $limitfrom) {
				$errmsg = sprintf(GetLang('CustomFieldsValidationNumbersToLow'), $this->label, $this->extraInfo['limitfrom']);
				return false;
			}
		}

		if ($this->extraInfo['limitto'] !== '') {
			$limitto = explode('-', $this->extraInfo['limitto']);
			$limitto = mktime(1, 1, 1, (int)$limitto[1], (int)$limitto[2], (int)$limitto[0]);

			if ($value > $limitto) {
				$errmsg = sprintf(GetLang('CustomFieldsValidationNumbersToHigh'), $this->label, $this->extraInfo['limitto']);
				return false;
			}
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
	 * @param bool $fromUserImport TRUE if this value is from a user import, FALSE if not. If TRUE then
	 *                             the value will pass through a few filters to get the proper value.
	 *                             Default is FALSE
	 */
	public function setValue($value, $setFromDB=false, $fromUserImport=false)
	{
		$realValue = $value;

		/**
		 * Are we setting the value from a user import?
		 */
		if ($fromUserImport) {
			$exploded = preg_split('/[\s-\/\.]+/', $value);
			$exploded = array_filter($exploded);

			if (count($exploded) !== 3) {
				$realValue = '';

			/**
			 * If the first digit is 4 characters long then it is Y-m-d
			 */
			} else if (strlen($exploded[0]) == 4) {
				$realValue = implode('-', $exploded);

			/**
			 * Else it is m-d-Y
			 */
			} else {
				$realValue = date('Y-m-d', mktime(1, 1, 1, (int)$exploded[0], (int)$exploded[1], (int)$exploded[2]));
			}
		}

		return parent::setValue($realValue, $setFromDB);
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

		$GLOBALS['FormFieldDayFieldArgs'] = '';
		$GLOBALS['FormFieldMonthFieldArgs'] = '';
		$GLOBALS['FormFieldYearFieldArgs'] = '';
		$GLOBALS['FormFieldDefaultArgs'] = 'id="' . isc_html_escape($this->getFieldId()) . '" class="FormField"';

		if ($this->extraInfo['limitfrom'] !== '') {
			$this->addExtraHiddenArgs('LimitFrom', $this->extraInfo['limitfrom']);
		}

		if ($this->extraInfo['limitto'] !== '') {
			$this->addExtraHiddenArgs('LimitTo', $this->extraInfo['limitto']);
		}

		if ($this->extraInfo['class'] !== '') {
			$GLOBALS['FormFieldDayFieldArgs'] .= 'class="' . isc_html_escape($this->extraInfo['class']) . ' FormFieldDay" ';
			$GLOBALS['FormFieldMonthFieldArgs'] .= 'class="' . isc_html_escape($this->extraInfo['class']) . ' FormFieldMonth" ';
			$GLOBALS['FormFieldYearFieldArgs'] .= 'class="' . isc_html_escape($this->extraInfo['class']) . ' FormFieldYear" ';
		} else {
			$GLOBALS['FormFieldDayFieldArgs'] .= 'class="FormFieldDay"';
			$GLOBALS['FormFieldMonthFieldArgs'] .= 'class="FormFieldMonth"';
			$GLOBALS['FormFieldYearFieldArgs'] .= 'class="FormFieldYear"';
		}

		if ($this->extraInfo['style'] !== '') {
			$GLOBALS['FormFieldDayFieldArgs'] .= 'style="' . isc_html_escape($this->extraInfo['style']) . '" ';
			$GLOBALS['FormFieldMonthFieldArgs'] .= 'style="' . isc_html_escape($this->extraInfo['style']) . '" ';
			$GLOBALS['FormFieldYearFieldArgs'] .= 'style="' . isc_html_escape($this->extraInfo['style']) . '" ';
		}

		$GLOBALS['FormFieldDayFieldName'] = $this->getFieldName('Day');
		$GLOBALS['FormFieldMonthFieldName'] = $this->getFieldName('Month');
		$GLOBALS['FormFieldYearFieldName'] = $this->getFieldName('Year');

		/**
		 * Set the value
		 */
		if ($this->value == '' && $this->extraInfo['defaultvalue'] !== '') {
			$defaultValue = $this->extraInfo['defaultvalue'];
		} else if ($this->value == '') {
			if ($this->extraInfo['limitfrom'] !== '') {
				$defaultValue = $this->extraInfo['limitfrom'];
			} else {
				$defaultValue = '';
			}
		} else {
			$defaultValue = $this->value;
		}

		/**
		 * Now the day, month and year options
		 */
		$defaultDate = array();
		if ($defaultValue !== '') {
			$defaultDate = explode('-', $defaultValue);
		}

		$defaultDate = array_filter($defaultDate, 'is_numeric');

		if (count($defaultDate) !== 3) {
			$defaultDate = array();
		}

		/**
		 * Find the available date ranges
		 */
		$ranges = $this->findAvailableDateRange();

		/**
		 * Day
		 */
		if (empty($defaultDate)) {
			$GLOBALS['FormFieldDayOptions'] = '<option value="" selected>--</option>';
		} else {
			$GLOBALS['FormFieldDayOptions'] = '<option value="">--</option>';
		}

		$range = $ranges['day'];
		for ($i=$range['from']; $i<=$range['to']; $i++) {
			$GLOBALS['FormFieldDayOptions'] .= '<option value="' . (int)$i . '"';

			if (isset($defaultDate[2]) && (int)$defaultDate[2] == $i) {
				$GLOBALS['FormFieldDayOptions'] .= ' selected="selected"';
			}

			$GLOBALS['FormFieldDayOptions'] .= '>' . isc_html_escape(Store_Number::addOrdinalSuffix($i)) . '</option>';
		}

		/**
		 * Month
		 */
		if (empty($defaultDate)) {
			$GLOBALS['FormFieldMonthOptions'] = '<option value="" selected>---</option>';
		} else {
			$GLOBALS['FormFieldMonthOptions'] = '<option value="">---</option>';
		}

		$range = $ranges['month'];
		for ($i=$range['from']; $i<=$range['to']; $i++) {
			$month = date('F', mktime(1, 1, 1, $i, 1, date('Y')));
			$month = ucfirst(isc_strtolower($month)) . 'Short';
			$GLOBALS['FormFieldMonthOptions'] .= '<option value="' . (int)$i . '"';

			if (isset($defaultDate[1]) && (int)$defaultDate[1] == $i) {
				$GLOBALS['FormFieldMonthOptions'] .= ' selected="selected"';
			}

			$GLOBALS['FormFieldMonthOptions'] .= '>' . isc_html_escape(GetLang($month)) . '</option>';
		}

		/**
		 * Year
		 */
		if (empty($defaultDate)) {
			$GLOBALS['FormFieldYearOptions'] = '<option value="" selected>----</option>';
		} else {
			$GLOBALS['FormFieldYearOptions'] = '<option value="">----</option>';
		}

		$range = $ranges['year'];
		for ($i=$range['from']; $i<=$range['to']; $i++) {
			$GLOBALS['FormFieldYearOptions'] .= '<option value="' . (int)$i . '"';

			if (isset($defaultDate[0]) && (int)$defaultDate[0] == $i) {
				$GLOBALS['FormFieldYearOptions'] .= ' selected="selected"';
			}

			$GLOBALS['FormFieldYearOptions'] .= '>' . (int)$i . '</option>';
		}

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
		$defaultValue = explode('-', $this->extraInfo['defaultvalue']);
		$limitFrom = explode('-', $this->extraInfo['limitfrom']);
		$limitTo = explode('-', $this->extraInfo['limitto']);
		$year = date('Y');

		$GLOBALS['FormFieldDateDefaultValueDays'] = '<option value="">--</option>';
		$GLOBALS['FormFieldDateLimitFromDays'] = '<option value="">--</option>';
		$GLOBALS['FormFieldDateLimitToDays'] = '<option value="">--</option>';
		for ($i=1; $i<=31; $i++) {
			if (isset($defaultValue[2]) && (int)$defaultValue[2] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateDefaultValueDays'] .= '<option value="' . (int)$i . '"' . $selected . '>' . (int)$i . '</option>';

			if (isset($limitFrom[2]) && (int)$limitFrom[2] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateLimitFromDays'] .= '<option value="' . (int)$i . '"' . $selected . '>' . (int)$i . '</option>';

			if (isset($limitTo[2]) && (int)$limitTo[2] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateLimitToDays'] .= '<option value="' . (int)$i . '"' . $selected . '>' . (int)$i . '</option>';
		}

		$GLOBALS['FormFieldDateDefaultValueMonths'] = '<option value="">---</option>';
		$GLOBALS['FormFieldDateLimitFromMonths'] = '<option value="">---</option>';
		$GLOBALS['FormFieldDateLimitToMonths'] = '<option value="">---</option>';
		for ($i=1; $i<=12; $i++) {
			if (isset($defaultValue[1]) && (int)$defaultValue[1] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateDefaultValueMonths'] .= '<option value="' . (int)$i . '"' . $selected . '>' . isc_html_escape(date('M', mktime(1,1,1,$i,1,$year))) . '</option>';

			if (isset($limitFrom[1]) && (int)$limitFrom[1] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateLimitFromMonths'] .= '<option value="' . (int)$i . '"' . $selected . '>' . isc_html_escape(date('M', mktime(1,1,1,$i,1,$year))) . '</option>';

			if (isset($limitTo[1]) && (int)$limitTo[1] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateLimitToMonths'] .= '<option value="' . (int)$i . '"' . $selected . '>' . isc_html_escape(date('M', mktime(1,1,1,$i,1,$year))) . '</option>';
		}

		$GLOBALS['FormFieldDateDefaultValueYears'] = '<option value="">----</option>';
		$GLOBALS['FormFieldDateLimitFromYears'] = '<option value="">----</option>';
		$GLOBALS['FormFieldDateLimitToYears'] = '<option value="">----</option>';
		for ($i=($year-50); $i<=($year+50); $i++) {
			if (isset($defaultValue[0]) && (int)$defaultValue[0] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateDefaultValueYears'] .= '<option value="' . (int)$i . '"' . $selected . '>' . (int)$i . '</option>';

			if (isset($limitFrom[0]) && (int)$limitFrom[0] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateLimitFromYears'] .= '<option value="' . (int)$i . '"' . $selected . '>' . (int)$i . '</option>';

			if (isset($limitTo[0]) && (int)$limitTo[0] == $i) {
				$selected = ' SELECTED';
			} else {
				$selected = '';
			}

			$GLOBALS['FormFieldDateLimitToYears'] .= '<option value="' . (int)$i . '"' . $selected . '>' . (int)$i . '</option>';
		}

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
	 * Find the available date ranges
	 *
	 * Method will find the available day, month and year ranges for the frontend
	 *
	 * @access private
	 * @return array An array with the available date ranges for day, month and year
	 */
	private function findAvailableDateRange()
	{
		$days = $months = $years = array();
		$default = array(
				'day' => array(
							'from' => 1,
							'to' => 31
						),
				'month' => array(
							'from' => 1,
							'to' => 12
						),
				'year' => array(
							'from' => date('Y')-100,
							'to' => date('Y')+100
						)
			);

		if ($this->extraInfo['limitfrom'] !== '' && $this->extraInfo['limitto'] !== '') {
			$limitFrom = explode('-', $this->extraInfo['limitfrom']);
			$limitTo = explode('-', $this->extraInfo['limitto']);

			$years = array(
						'from' => (int)$limitFrom[0],
						'to' => (int)$limitTo[0]
					);

			if ($limitFrom[0] == $limitTo[0]) {
				$months = array(
							'from' => (int)$limitFrom[1],
							'to' => (int)$limitTo[1]
						);

				if ($limitFrom[1] == $limitTo[1]) {
					$days = array(
								'from' => (int)$limitFrom[2],
								'to' => (int)$limitTo[2]
							);
				}
			}
		}

		$range = array();

		if (!empty($years)) {
			$range['year'] = $years;
		} else {
			$range['year'] = $default['year'];
		}

		if (!empty($months)) {
			$range['month'] = $months;
		} else {
			$range['month'] = $default['month'];
		}

		if (!empty($days)) {
			$range['day'] = $days;
		} else {
			$range['day'] = $default['day'];
		}

		return $range;
	}

	/**
	* Returns a class name representing the type of data this field presents for email integration.
	*
	* @return string A name of one of Interspire_EmailIntegration_Field_* types, or false if not supported as field for sending to email providers.
	*/
	public function getEmailIntegrationFieldClassName()
	{
		return 'Interspire_EmailIntegration_Field_Date';
	}
}
