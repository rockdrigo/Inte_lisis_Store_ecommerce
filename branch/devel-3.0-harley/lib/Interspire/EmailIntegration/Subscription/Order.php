<?php

/**
* This class represents a subscription based on a new order. In the user interface, this is referred to as a "new customer", but it is named "Order" internally because it is mainly used at the end of an order process. This is as opposed to the "Customer" subscription type, which is mainly used for bulk exporting existing customers.
*/
class Interspire_EmailIntegration_Subscription_Order extends Interspire_EmailIntegration_Subscription
{
	protected $_data = array();

	protected $_products = array();
	protected $_brands = array();
	protected $_categories = array();

	public function __construct($orderId = null)
	{
		$this->setDoubleOptIn(GetConfig('EmailIntegrationOrderDoubleOptin'));
		$this->setSendWelcome(GetConfig('EmailIntegrationOrderSendWelcome'));
		$this->setSubscriptionIP(GetIP());

		if (!$orderId) {
			return;
		}

		$entity = new ISC_ENTITY_ORDER;

		$data = $entity->get($orderId);
		if (!$data) {
			throw new Interspire_EmailIntegration_Subscription_Exception;
		}
		$this->_data = $data;
		unset($data);

		// copy any form fields associated with the order + associated customer and place into local subscription data

		if (isId($this->_data['ordformsessionid'])) {
			/** @var ISC_FORM */
			$form = $GLOBALS["ISC_CLASS_FORM"];

			$customFields = array();

			$formData = $form->getSavedSessionData($this->_data['customer']['custformsessionid']);
			if ($formData && !empty($formData)) {
				$customFields += $formData;
			}

			$formData = $form->getSavedSessionData($this->_data['ordformsessionid']);
			if ($formData && !empty($formData)) {
				$customFields += $formData;
			}

			foreach ($customFields as $fieldId => $value) {
				$this->_data['FormField_' . $fieldId] = $value;
			}
		}

		// generate fields specifically for email integration based on order data (ones that aren't covered by simple order data or by Form Fields)

		// get the first shipping address record because IEM had shipping method as mappable field
		$this->_data['shipping_method'] = '';
		$shippingMethod = $GLOBALS['ISC_CLASS_DB']->FetchOne("SELECT `method` FROM [|PREFIX|]order_shipping WHERE order_id = " . (int)$orderId . " LIMIT 1", 'method');
		if ($shippingMethod) {
			$this->_data['shipping_method'] = $shippingMethod;
		}

		// pre-formated 'full address' mappable field to pass to providers like mailchimp
		$this->_data['OrderSubscription_BillingAddress'] = array(
			'addr1' => $this->_data['ordbillstreet1'],
			'addr2' => $this->_data['ordbillstreet2'],
			'city' => $this->_data['ordbillsuburb'],
			'state' => $this->_data['ordbillstate'],
			'zip' => $this->_data['ordbillzip'],
			'country' => $this->_data['ordbillcountrycode'],
		);

		// country-code specific fields to pass to providers like MailChimp or IEM that support (or require in IEM's case) country codes
		$this->_data['OrderSubscription_BillingAddress_countryiso2'] = $this->_data['ordbillcountrycode'];
		$this->_data['OrderSubscription_BillingAddress_countryiso3'] = GetCountryISO3ById($this->_data['ordbillcountryid']);

		// for email integration, we prefer sending the value of an order as the total amount rather than the stored (charged) total - which could be less than the value due to store credit or gift certificates
		// so, generate some columns which are internal to this subscription data and map to those instead of total_ex and total_inc
		$this->_data['total_ex_tax'] = $this->_data['subtotal_ex_tax'] + $this->_data['shipping_cost_ex_tax'] + $this->_data['handling_cost_ex_tax'] + $this->_data['wrapping_cost_ex_tax'];
		$this->_data['total_inc_tax'] = $this->_data['subtotal_inc_tax'] + $this->_data['shipping_cost_inc_tax'] + $this->_data['handling_cost_inc_tax'] + $this->_data['wrapping_cost_inc_tax'];

		// generated fields: end

		// currency values must be stored in the subscription data as both numeric and formatted so that, when translated to the mail provider, it can be sent as either a number or string depending on the destination field
		$moneyFields = array(
			'subtotal_ex_tax',
			'subtotal_inc_tax',
			'subtotal_tax',
			'total_ex_tax',
			'total_inc_tax',
			'total_tax',
			'shipping_cost_ex_tax',
			'shipping_cost_inc_tax',
			'shipping_cost_tax',
			'handling_cost_ex_tax',
			'handling_cost_inc_tax',
			'handling_cost_tax',
			'wrapping_cost_ex_tax',
			'wrapping_cost_inc_tax',
			'wrapping_cost_tax',
			'ordrefundedamount',
			'ordstorecreditamount',
			'ordgiftcertificateamount',
			'orddiscountamount',
			'coupon_discount',
		);

		foreach ($moneyFields as $moneyFieldId) {
			$this->_data[$moneyFieldId] = array(
				'numeric' => $this->_data[$moneyFieldId],
				'formatted' => FormatPriceInCurrency($this->_data[$moneyFieldId], $this->_data['orddefaultcurrencyid']),
			);
		}

		$set = new ISC_NESTEDSET_CATEGORIES;

		// instead of storing full product information, just store the data pertinent to integration rules
		foreach ($this->_data['products'] as $product) {
			$this->_products[] = $product['productid'];
			$this->_brands[] = $product['prodbrandid'];

			if ($product['prodcatids']) {
				foreach (explode(',', $product['prodcatids']) as $categoryId) {
					$this->_categories[] = $categoryId;

					// also include parent categories to trigger rules related to them
					$parents = $set->getParentPath(array('categoryid'), (int)$categoryId);
					foreach ($parents as $parentCategory) {
						$this->_categories[] = $parentCategory['categoryid'];
					}
				}
			}
		}

		$this->_products = array_unique($this->_products);
		$this->_brands = array_unique($this->_brands);
		$this->_categories = array_unique($this->_categories);

		sort($this->_products);
		sort($this->_brands);
		sort($this->_categories);

		// for now, don't need to store these - may need to store products when this is changed to supply ecommerce info
		unset($this->_data['customer']);
		unset($this->_data['products']);
	}

	public function getSubscriptionEventId()
	{
		return 'onOrderCompleted';
	}

	public function getSubscriptionTypeLang()
	{
		return GetLang('EmailIntegration_Subscription_Order');
	}

	/**
	* This method maps provided form fields objects to email integration fields based on a mapping array
	*
	* @param array $formFields array of ISC_FORMFIELD_ classes, usually from ISC_FORM->getFormFields(...)
	* @param array $mapping array of form field id => subscription data field maps
	*/
	public function mapFormFields($formFields, $mapping)
	{
		$fields = array();
		foreach ($formFields as /** @var ISC_FORMFIELD_BASE */$field) {
			$privateId = $field->getFieldPrivateId();

			// determine id of field based on mappings
			if (!$privateId) {
				// custom field created by store owner
				$fieldId = $field->getFieldId();
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
		ParseLangFile($languagePath . '/settings.emailintegration.ini');
		ParseLangFile($languagePath . '/export.filetype.orders.ini');

		/** @var ISC_FORM */
		$form = $GLOBALS['ISC_CLASS_FORM'];

		$groups = array();

		$accountLang = GetLang('EmailIntegrationOrderSubscriptionAccountFields');
		$billingLang = GetLang('EmailIntegrationOrderSubscriptionBillingFields');
		$orderLang = GetLang('EmailIntegrationOrderSubscriptionOrderFields');

		$groups[$accountLang] = $this->mapFormFields($form->getFormFields(FORMFIELDS_FORM_ACCOUNT), array(
			'EmailAddress' => 'ordbillemail',
		));

		$groups[$orderLang] = array(
			'orderid' => new Interspire_EmailIntegration_Field_Int('orderid', GetLang('orderID')),
			'orddate' => new Interspire_EmailIntegration_Field_Date('orddate', GetLang('orderDate')),
			'subtotal_ex_tax' => new Interspire_EmailIntegration_Field_Currency('subtotal_ex_tax', GetLang('orderSubtotalEx')),
			'subtotal_inc_tax' => new Interspire_EmailIntegration_Field_Currency('subtotal_inc_tax', GetLang('orderSubtotalInc')),
			'total_ex_tax' => new Interspire_EmailIntegration_Field_Currency('total_ex_tax', GetLang('orderTotalAmountEx')),
			'total_inc_tax' => new Interspire_EmailIntegration_Field_Currency('total_inc_tax', GetLang('orderTotalAmountInc')),
			'ordipaddress' => new Interspire_EmailIntegration_Field_Ip('ordipaddress', GetLang('CustomerIPAddress')),
			'orderpaymentmethod' => new Interspire_EmailIntegration_Field_String('orderpaymentmethod', GetLang('orderPayMethod')),
			'shipping_method' => new Interspire_EmailIntegration_Field_String('shipping_method', GetLang('orderShipMethod')),
		);

		// add generated fields that contain full address information as an array
		$groups[$billingLang]['OrderSubscription_BillingAddress'] = new Interspire_EmailIntegration_Field_Address('OrderSubscription_BillingAddress', GetLang('EmailIntegrationOrderSubscriptionFullAddress'));

		$groups[$billingLang] += $this->mapFormFields($form->getFormFields(FORMFIELDS_FORM_BILLING), array(
			'FirstName' => 'ordbillfirstname',
			'LastName' => 'ordbilllastname',
			'CompanyName' => 'ordbillcompany',
			'Phone' => 'ordbillphone',
			'AddressLine1' => 'ordbillstreet1',
			'AddressLine2' => 'ordbillstreet2',
			'City' => 'ordbillsuburb',
			'Country' => 'ordbillcountry',
			'State' => 'ordbillstate',
			'Zip' => 'ordbillzip',
		));

		$groups[$billingLang]['OrderSubscription_BillingAddress_countryiso2'] = new Interspire_EmailIntegration_Field_String('OrderSubscription_BillingAddress_countryiso2', GetLang('CountryIso2'));
		$groups[$billingLang]['OrderSubscription_BillingAddress_countryiso3'] = new Interspire_EmailIntegration_Field_String('OrderSubscription_BillingAddress_countryiso3', GetLang('CountryIso3'));

		return $groups;
	}

	public function getSubscriptionEmailField()
	{
		return 'ordbillemail';
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
