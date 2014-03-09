<?php
/**
 * Map and build the address form fields
 *
 * Method will call mapFieldData() to map the field data and then biuld each
 * field HTML
 *
 * @access private
 * @param array $fields The field list
 * @param array $data The optional database record to map against
 * @return string The constructed HTML on success, empty string on failure
 */
function buildFieldData($addressId=0, $customerId=0)
{
	$data = array();

	/**
	 * Do we have a valid address record ID?
	 */
	if (isId($addressId)) {

		/**
		 * We must also have a customer ID
		 */
		if (!isId($customerId)) {
			$customerId = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
		}

		if (!isId($customerId)) {
			return '';
		}

		$entity = new ISC_ENTITY_SHIPPING();
		$data = $entity->get($addressId, $customerId);

		if (!$data) {
			return '';
		}
	}

	$getFromRequest = false;
	$getFromFormSessionId = '';

	if (isId($addressId) && isId($data['shipformsessionid'])) {
		$getFromFormSessionId = $data['shipformsessionid'];
	} else {
		$getFromRequest = true;
	}

	$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, $getFromRequest, $getFromFormSessionId);

	/**
	 * OK, we got the fields, now we need to map the database record to it. This method has to
	 * be called as it also adds in the country and state options, so call this regardless
	 */
	if (!mapFieldData($fields, $data)) {
		return '';
	}

	/**
	 * Remove the 'Save this address' option as this is for single page checkout only
	 */
	$html = '';
	foreach (array_keys($fields) as $fieldId) {
		if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'savethisaddress' || isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'shiptoaddress') {
			continue;
		}

		$html .= $fields[$fieldId]->loadForFrontend();
	}

	return $html;
}

/**
 * Map the address form fields
 *
 * Method will map each database record element in $data to the corresponding field
 * in $fields and set its value. Will also add in the country and state options
 *
 * @access private
 * @param array &$fields The referenced field list
 * @param array $data The optional database record to map against
 * @return bool TRUE if the mapping was successful, FALSE if not
 */
function mapFieldData(&$fields, $data=array())
{
	if (!is_array($fields) || !is_array($data)) {
		return false;
	}

	$fieldMap = getAddressFormMapping();
	$countryFieldId = '';
	$stateFieldId = '';

	foreach (array_keys($fields) as $fieldId) {
		if (!array_key_exists($fields[$fieldId]->record['formfieldprivateid'], $fieldMap)) {
			continue;
		}

		$key = 'ship' . $fieldMap[$fields[$fieldId]->record['formfieldprivateid']];

		if (array_key_exists($key, $data)) {
			$fields[$fieldId]->setValue($data[$key]);
		}

		if ($key == 'shipcountry') {
			$countryFieldId = $fieldId;
		} else if ($key == 'shipstate') {
			$stateFieldId = $fieldId;
		}
	}

	if ($countryFieldId) {
		$fields[$countryFieldId]->setOptions(array_values(GetCountryListAsIdValuePairs()));
		if ($fields[$countryFieldId]->getValue() == '') {
			$fields[$countryFieldId]->setValue(GetConfig('CompanyCountry'));
		}

		if (isId($stateFieldId)) {
			$fields[$countryFieldId]->addEventHandler('change', 'FormFieldEvent.SingleSelectPopulateStates', array('countryId' => $countryFieldId, 'stateId' => $stateFieldId));

			$countryId = GetCountryByName($fields[$countryFieldId]->getValue());
			$stateOptions = GetStateListAsIdValuePairs($countryId);

			if (is_array($stateOptions) && !empty($stateOptions)) {
				$fields[$stateFieldId]->setOptions($stateOptions);
			}
			else {
				// no states for our country, we need to mark this as not required
				$fields[$stateFieldId]->setRequired(false);
			}
		}
	}

	return true;
}


/**
 * Validate the submitted field data
 *
 * Method will run the validation for the submitted shipping field data
 *
 * @access private
 * @param array $fields The fields to validate
 * @param string &$errmsg The referenced variable to store the error message in
 * @return bool TRUE if the validation was successful, FALSE if validation failed
 */
function validateFieldData($fields, &$errmsg)
{
	if (!is_array($fields)) {
		return false;
	}

	foreach (array_keys($fields) as $fieldId) {
		if (!$fields[$fieldId]->runValidation($errmsg)) {
			return false;
		}
	}

	return true;
}


/**
 * Parse the submitted field data into an associative array
 *
 * Method will parse the submitted field data and convert it into an associative array
 * that resembles the shipping_addresses table structure
 *
 * @access private
 * @param array $fields The field list to parse from
 * @param int $formSessionId The optional form session ID
 * @return array The parsed array on success, FALSE on failure
 */
function parseFieldData($fields, $formSessionId='')
{
	if (!is_array($fields)) {
		return false;
	}

	$fieldMap = getAddressFormMapping();
	$savedata = array();
	$countryFieldId = '';
	$stateFieldId = '';

	foreach (array_keys($fields) as $fieldId) {
		if (!array_key_exists($fields[$fieldId]->record['formfieldprivateid'], $fieldMap)) {
			continue;
		}

		$key = 'ship' . $fieldMap[$fields[$fieldId]->record['formfieldprivateid']];
		$savedata[$key] = $fields[$fieldId]->getValue();

		if ($key == 'shipcountry') {
			$countryFieldId = $fieldId;
		} else if ($key == 'shipstate') {
			$stateFieldId = $fieldId;
		}
	}

	$savedata['shipcustomerid'] = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

	/**
	 * Fill in the country and state IDs
	 */
	$savedata['shipcountryid'] = GetCountryByName($fields[$countryFieldId]->getValue());

	if (isId($savedata['shipcountryid'])) {
		$savedata['shipstateid'] = GetStateByName($fields[$stateFieldId]->getValue(), $savedata['shipcountryid']);
	} else {
		$savedata['shipstateid'] = 0;
	}

	/**
	 * Now save the form session record
	 */
	$formSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ADDRESS, true, $formSessionId);

	if (isId($formSessionId)) {
		$savedata['shipformsessionid'] = $formSessionId;
	}

	return $savedata;
}

/**
 * Convert a supplied set of address form fields in to an array
 * that closely resembles the shipping_addresses table.
 *
 * @param array $formFields Array of address form field objects.
 * @return array Address array closely resembling the shipping_addresses table.
 */
function convertAddressFieldsToArray($formFields)
{
	$fieldMap = getAddressFormMapping();
	$addressArray = array();
	foreach($formFields as $formField) {
		if(!$formField->record['formfieldprivateid']) {
			continue;
		}

		$mapField = $fieldMap[$formField->record['formfieldprivateid']];
		$addressArray['ship' . $mapField] = $formField->getValue();
	}

	return $addressArray;
}

/**
 * Get the map of shipping_address and form field private ID
 * values.
 *
 * @return array Mapping of fields.
 */
function getAddressFormMapping()
{
	return array(
		'EmailAddress' => 'email',
		'FirstName' => 'firstname',
		'LastName' => 'lastname',
		'CompanyName' => 'company',
		'AddressLine1' => 'address1',
		'AddressLine2' => 'address2',
		'City' => 'city',
		'State' => 'state',
		'Country' => 'country',
		'Zip' => 'zip',
		'Phone' => 'phone',
	);
}
/**
 * Convert an address from the shipping_addresses table in to
 * an array named appropriately according to the form field private
 * IDs. Opposite of convertAddressFieldsToArray.
 *
 * @param array $address Address from shipping_addresses table.
 * @return array Array of fields mapped to form field private IDs.
 */
function convertAddressArrayToFieldArray($address)
{
	$formAddress = array();
	$fieldMap = getAddressFormMapping();
	foreach($fieldMap as $formFieldId => $addressField) {
		if(isset($address[$addressField])) {
			$formAddress[$formFieldId] = $address[$addressField];
		}
		else if(isset($address['ship' . $addressField])) {
			$formAddress[$formFieldId] = $address['ship' . $addressField];
		}
	}

	return $formAddress;
}
