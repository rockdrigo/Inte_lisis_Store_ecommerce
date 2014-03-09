<?php
require_once(ISC_BASE_PATH . '/lib/addressvalidation.php');

class ISC_CHECKOUTNEWADDRESSFORM_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		// this panel should only be shown for guests entering an address
		if(CustomerIsSignedIn()) {
			$this->DontDisplay = true;
			return;
		}

		$formHtml = '';

		// Enter a billing address
		if($GLOBALS['ShippingFormAction'] == 'save_biller') {
			$formFieldType = FORMFIELDS_FORM_BILLING;
			$quoteAddress = getCustomerQuote()->getBillingAddress();

			// load the email address field
			$GLOBALS['ISC_CLASS_FORM']->addFormFieldUsed($GLOBALS['ISC_CLASS_FORM']->getFormField(FORMFIELDS_FORM_ACCOUNT, '1', '', true));

			// load html for email field
			$formHtml .= $GLOBALS['ISC_CLASS_FORM']->loadFormField(FORMFIELDS_FORM_ACCOUNT, '1');

			$GLOBALS['CheckEmail'] = 'true';
		}
		else {
			$formFieldType = FORMFIELDS_FORM_SHIPPING;
			$quoteAddress = getCustomerQuote()->setIsSplitShipping(false)
				->getShippingAddress();
		}

		$addressFormFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields($formFieldType, false);

		// Coming back here from an error, so use the $_POST values
		$savedFormFieldValues = array();
		if(!empty($GLOBALS['ErrorMessage']) && !empty($_POST['FormField'][$formFieldType])) {
			$savedFormFieldValues = $_POST['FormField'][$formFieldType];
		}
		// Use the address already saved in the quote if there is one
		else {
			// An array containing the methods available in $quoteAddress and the form field "private ID"
			$quoteAddressFields = array(
				'EmailAddress' => 'getEmail',
				'FirstName' => 'getFirstName',
				'LastName' => 'getLastName',
				'CompanyName' => 'getCompany',
				'AddressLine1' => 'getAddress1',
				'AddressLine2' => 'getAddress2',
				'City' => 'getCity',
				'Zip' => 'getZip',
				'State' => 'getStateName',
				'Country' => 'getCountryName',
				'Phone' => 'getPhone',
			);
			foreach($addressFormFields as $formFieldId => $formField) {
				$formFieldPrivateId = $formField->record['formfieldprivateid'];
				if(isset($quoteAddressFields[$formFieldPrivateId])) {
					$method = $quoteAddressFields[$formFieldPrivateId];
					$savedFormFieldValues[$formFieldId] = $quoteAddress->$method();
				}
				else {
					$customField = $quoteAddress->getCustomField($formFieldId);
					if($customField !== false) {
						$savedFormFieldValues[$formFieldId] = $customField;
					}
				}
			}
		}

		$countryFieldId = 0;
		$stateFieldId = 0;
		foreach($addressFormFields as $formFieldId => $formField) {
			$formFieldPrivateId = $formField->record['formfieldprivateid'];
			if(isset($savedFormFieldValues[$formFieldId])) {
				$formField->setValue($savedFormFieldValues[$formFieldId]);
			}

			if($formFieldPrivateId == 'Country') {
				$countryFieldId = $formFieldId;
			}
			else if($formFieldPrivateId == 'State') {
				$stateFieldId = $formFieldId;
			}
		}

		if($countryFieldId) {
			$addressFormFields[$countryFieldId]->setOptions(array_values(GetCountryListAsIdValuePairs()));
			if ($addressFormFields[$countryFieldId]->getValue() == '') {
				$addressFormFields[$countryFieldId]->setValue(GetConfig('CompanyCountry'));
			}

			if ($stateFieldId) {
				$addressFormFields[$countryFieldId]->addEventHandler('change', 'FormFieldEvent.SingleSelectPopulateStates', array('countryId' => $countryFieldId, 'stateId' => $stateFieldId));
				$countryId = GetCountryByName($addressFormFields[$countryFieldId]->getValue());
				$stateOptions = GetStateListAsIdValuePairs($countryId);

				if (is_array($stateOptions) && !empty($stateOptions)) {
					$addressFormFields[$stateFieldId]->setOptions($stateOptions);
				}
				else {
					// no states for our country, we need to mark this as not required
					$addressFormFields[$stateFieldId]->setRequired(false);
				}
			}
		}

		foreach($addressFormFields as $formField) {
			if (isc_strtolower($formField->record['formfieldprivateid']) == 'savethisaddress' ||
				isc_strtolower($formField->record['formfieldprivateid']) == 'shiptoaddress') {
					continue;
			}

			$formHtml .= $formField->loadForFrontend();
			$GLOBALS['ISC_CLASS_FORM']->addFormFieldUsed($formField);
		}

		$GLOBALS['ShipCustomFields'] = $formHtml;
		$GLOBALS['AddressFormFieldID'] = $formFieldType;
		$GLOBALS['FormFieldRequiredJS'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();
	}
}