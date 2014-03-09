<?php

/**
* This class represents a subscription based on a customer. This subscription type is not used by routing rules, it is only used by bulk export. For the routing-rule based export of "new customers", refer to the Order subscription type.
*/
class Interspire_EmailIntegration_Subscription_Customer extends Interspire_EmailIntegration_Subscription
{
	protected $_data = array();

	public function __construct($customerId = null)
	{
		// use the same settings as orders by default
		$this->setDoubleOptIn(GetConfig('EmailIntegrationOrderDoubleOptin'));
		$this->setSendWelcome(GetConfig('EmailIntegrationOrderSendWelcome'));

		if (!$customerId) {
			return;
		}

		$entity = new ISC_ENTITY_CUSTOMER();

		$data = $entity->get($customerId);
		if (!$data) {
			throw new Interspire_EmailIntegration_Subscription_Exception();
		}

		unset($data['custpassword']);

		$this->_data = $data;
		unset($data);

		$this->setSubscriptionIP($this->_data['custregipaddress']);

		// customer custom form fields

		/** @var ISC_FORM */
		$form = $GLOBALS["ISC_CLASS_FORM"];

		// populate empty form fields as a starting point -- this makes exports of imported customers work OK because they may not have a custformsessionid, or this ensures that export data is current with configured form fields even if the stored form fields are out of date
		$formFields = $form->getFormFields(FORMFIELDS_FORM_ACCOUNT);
		foreach ($formFields as /** @var ISC_FORMFIELD_BASE */$formField) {
			if ($formField->getFieldPrivateId()) {
				continue;
			}
			$this->_data[$formField->getFieldId()] = '';
		}

		// load saved data for this customer
		if (isId($this->_data['custformsessionid'])) {
			$customFields = $form->getSavedSessionData($this->_data['custformsessionid']);
			foreach ($customFields as $fieldId => $value) {
				$this->_data['FormField_' . $fieldId] = $value;
			}
		}

		// for email integration purposes, money values must be stored in an array as both numeric and formatted to allow for translation to both number fields and text fields, while maintaining currency information
		SetupCurrency();
		$moneyFields = array('custstorecredit');
		foreach ($moneyFields as $moneyFieldId) {
			$this->_data[$moneyFieldId] = array(
				'numeric' => $this->_data[$moneyFieldId],
				'formatted' => FormatPriceInCurrency($this->_data[$moneyFieldId]),
			);
		}

		unset($this->_data['addresses']); // the addresses provided by entity class are mixed billing/shipping addresses, can't be sure so discard them
		// find last used _billing_ address for this customer by non-deleted orders
		$order = $GLOBALS['ISC_CLASS_DB']->FetchRow("SELECT ordformsessionid, ordbillstreet1, ordbillstreet2, ordbillsuburb, ordbillstate, ordbillzip, ordbillcountryid FROM `[|PREFIX|]orders` WHERE ordcustid = " . (int)$customerId . " AND deleted = 0 ORDER BY orddate DESC LIMIT 1");
		if (is_array($order)) {
			// create fields specifically for email integration based on customer data

			if (isId($order['ordformsessionid'])) {
				$customFields = $form->getSavedSessionData($order['ordformsessionid']);
				foreach ($customFields as $fieldId => $value) {
					$this->_data['CustomerSubscription_Address_FormField_' . $fieldId] = $value;
				}
			}

			$this->_data['CustomerSubscription_Address'] = array(
				'addr1' => $order['ordbillstreet1'],
				'addr2' => $order['ordbillstreet2'],
				'city' => $order['ordbillsuburb'],
				'state' => $order['ordbillstate'],
				'zip' => $order['ordbillzip'],
				'country' => GetCountryById($order['ordbillcountryid']),
				'countryiso2' => GetCountryISO2ById($order['ordbillcountryid']),
				'countryiso3' => GetCountryISO3ById($order['ordbillcountryid']),
			);

			$this->_data['CustomerSubscription_Address_address1'] = $this->_data['CustomerSubscription_Address']['addr1'];
			$this->_data['CustomerSubscription_Address_address2'] = $this->_data['CustomerSubscription_Address']['addr2'];
			$this->_data['CustomerSubscription_Address_city'] = $this->_data['CustomerSubscription_Address']['city'];
			$this->_data['CustomerSubscription_Address_state'] = $this->_data['CustomerSubscription_Address']['state'];
			$this->_data['CustomerSubscription_Address_zip'] = $this->_data['CustomerSubscription_Address']['zip'];
			$this->_data['CustomerSubscription_Address_country'] = $this->_data['CustomerSubscription_Address']['country'];
			$this->_data['CustomerSubscription_Address_countryiso2'] = $this->_data['CustomerSubscription_Address']['countryiso2'];
			$this->_data['CustomerSubscription_Address_countryiso3'] = $this->_data['CustomerSubscription_Address']['countryiso3'];
		}

		// transform customer group data if available
		if ($this->_data['customergroup']) {
			$this->_data['customergroupid'] = $this->_data['customergroup']['customergroupid'];
			$this->_data['groupname'] = $this->_data['customergroup']['groupname'];
		}
		else
		{
			$this->_data['customergroupid'] = '';
			$this->_data['groupname'] = '';
		}
		unset($this->_data['customergroup']);
	}

	public function getSubscriptionEventId()
	{
		// not supported for Customer type, use Order instead
		return false;
	}

	public function getSubscriptionTypeLang()
	{
		return GetLang('EmailIntegration_Subscription_Customer');
	}

	/**
	* This method maps provided form fields objects to email integration fields based on a mapping array
	*
	* @param array $formFields array of ISC_FORMFIELD_ classes, usually from ISC_FORM->getFormFields(...)
	* @param array $mapping array of form field id => subscription data field maps
	*/
	public function mapFormFields($formFields, $mapping, $customFieldPrefix = '')
	{
		$fields = array();
		foreach ($formFields as /** @var ISC_FORMFIELD_BASE */$field) {
			$privateId = $field->getFieldPrivateId();

			// determine id of field based on mappings
			if (!$privateId) {
				// custom field created by store owner
				$fieldId = $customFieldPrefix . $field->getFieldId();
			} else if (isset($mapping[$privateId])) {
				// built-in field we are mapping
				$fieldId = $mapping[$privateId];
			} else {
				// other built-in field we are not mapping -- ignore it
				continue;
			}

			$integrationFieldClass = $field->getEmailIntegrationFieldClassName();
			if (!$integrationFieldClass) {
				// the form field is set to not allow email integration -- ignore it
				continue;
			}

			$fields[$fieldId] = new $integrationFieldClass($fieldId, $field->getFieldLabel());
		}
		return $fields;
	}

	public function getSubscriptionFields()
	{
		// can't rely on ISC_ADMIN_ENGINE or admin lang stuff from here because this code may be run by the task manager
		$languagePath = ISC_BASE_PATH . '/language/' . GetConfig('Language') . '/admin';
		ParseLangFile($languagePath . '/common.ini');
		ParseLangFile($languagePath . '/customers.ini');
		ParseLangFile($languagePath . '/settings.emailintegration.ini');
		ParseLangFile($languagePath . '/export.filetype.customers.ini');

		/** @var ISC_FORM */
		$form = $GLOBALS['ISC_CLASS_FORM'];

		$groups = array();

		$accountLang = GetLang('EmailIntegrationCustomerSubscriptionAccountFields');
		$billingLang = GetLang('EmailIntegrationCustomerSubscriptionBillingFields');

		// these don't exist as form fields so create them manually
		$groups[$accountLang] = array(
			'custconfirstname' => new Interspire_EmailIntegration_Field_String('custconfirstname', GetLang('customerFirstName')),
			'custconlastname' => new Interspire_EmailIntegration_Field_String('custconlastname', GetLang('customerLastName')),
			'custconcompany' => new Interspire_EmailIntegration_Field_String('custconcompany', GetLang('customerCompany')),
			'customerid' => new Interspire_EmailIntegration_Field_Number('customerid', GetLang('customerID')),
		);

		$groups[$accountLang] += $this->mapFormFields($form->getFormFields(FORMFIELDS_FORM_ACCOUNT), array(
			'EmailAddress' => 'custconemail',
		));

		// these don't exist as form fields so create them manually
		$groups[$accountLang] += array(
			'custconphone' => new Interspire_EmailIntegration_Field_String('custconphone', GetLang('PhoneNumber')),
			'custstorecredit' => new Interspire_EmailIntegration_Field_Currency('custstorecredit', GetLang('StoreCredit')),
			'custdatejoined' => new Interspire_EmailIntegration_Field_Date('custdatejoined', GetLang('DateJoined')),
			'custlastmodified' => new Interspire_EmailIntegration_Field_Date('custlastmodified', GetLang('LastModified')),
			'groupname' => new Interspire_EmailIntegration_Field_String('groupname', GetLang('customerGroup')),
			'customergroupid' => new Interspire_EmailIntegration_Field_Number('customergroupid', GetLang('customerGroupID')),
		);

		// add generated fields that contain full address information as an array
		$groups[$billingLang]['CustomerSubscription_Address'] = new Interspire_EmailIntegration_Field_Address('CustomerSubscription_Address', GetLang('EmailIntegrationOrderSubscriptionFullAddress'));

		$groups[$billingLang] += $this->mapFormFields($form->getFormFields(FORMFIELDS_FORM_BILLING), array(
			'AddressLine1' => 'CustomerSubscription_Address_address1',
			'AddressLine2' => 'CustomerSubscription_Address_address2',
			'City' => 'CustomerSubscription_Address_city',
			'Country' => 'CustomerSubscription_Address_country',
			'State' => 'CustomerSubscription_Address_state',
			'Zip' => 'CustomerSubscription_Address_zip',
		), 'CustomerSubscription_Address_');

		$groups[$billingLang]['CustomerSubscription_Address_countryiso2'] = new Interspire_EmailIntegration_Field_String('CustomerSubscription_Address_countryiso2', GetLang('CountryIso2'));
		$groups[$billingLang]['CustomerSubscription_Address_countryiso3'] = new Interspire_EmailIntegration_Field_String('CustomerSubscription_Address_countryiso3', GetLang('CountryIso3'));

		return $groups;
	}

	public function getSubscriptionEmailField()
	{
		return 'custconemail';
	}

	public function getSubscriptionEmail()
	{
		return $this->_data[$this->getSubscriptionEmailField()];
	}

	public function getSubscriptionData()
	{
		return $this->_data;
	}

	/**
	* Returns true if this subscription is from an order which contains the specified product
	*
	* @param int $productId
	* @return bool
	*/
	public function containsProduct($productId)
	{
		return in_array($productId, $this->_products);
	}

	/**
	* Returns true if this subscription is from an order which contains a product from the specified category
	*
	* @param int $categoryId
	* @return bool
	*/
	public function containsCategory($categoryId)
	{
		return in_array($categoryId, $this->_categories);
	}

	/**
	* Returns true if this subscription is from an order which contains a product of the specified brand
	*
	* @param int $brandId
	* @return bool
	*/
	public function containsBrand($brandId)
	{
		return in_array($brandId, $this->_brands);
	}
}
