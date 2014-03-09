<?php
require_once(dirname(__FILE__) . "/../classes/class.batch.importer.php");

class ISC_BATCH_IMPORTER_CUSTOMERS extends ISC_BATCH_IMPORTER_BASE
{
	private $customerEntity;
	private $groupEntity;
	private $shippingEntity;

	/**
	 * @var string The type of content we're importing. Should be lower case and correspond with template and language variable names.
	 */
	protected $type = "customers";

	protected $_RequiredFields = array(
		"custconemail"
	);

	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('batch.importer');

		/**
		 * @var array Array of importable fields and their friendly names.
		 */
		$this->_ImportFields = array(
			"custconemail" => GetLang('CustEmail'),
			"custpassword" => GetLang('CustPassword'),
			"custconfirstname" => GetLang('CustFirstName'),
			"custconlastname" => GetLang('CustLastName'),
			"custconcompany" => GetLang('CustCompany'),
			"custconphone" => GetLang('CustPhone'),
			"shipfirstname" => GetLang('CustomerAddressFirstName'),
			"shiplastname" => GetLang('CustomerAddressLastName'),
			"shipaddress1" => GetLang('CustomerAddressLine1'),
			"shipaddress2" => GetLang('CustomerAddressLine2'),
			"shipcity" => GetLang('CustomerAddressCity'),
			"shipstate" => GetLang('CustomerAddressState'),
			"shipzip" => GetLang('CustomerAddressZip'),
			"shipcountry" => GetLang('CustomerAddressCountry'),
			"shipphone" => GetLang('CustomerAddressPhone'),
			'custstorecredit' => GetLang('StoreCredit'),
			'custgroup' => GetLang('CustomerGroup'),
			'custnotes' => GetLang('CustomerNotes')
		);

		/**
		 * Tag on the customer and address custom fields
		 */
		$fields = array();
		$fields += $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT);
		$fields += $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS);

		if (is_array($fields) && !empty($fields)) {
			$this->_ImportFields['custom'] = array();

			foreach (array_keys($fields) as $fieldId) {
				if ($fields[$fieldId]->record['formfieldprivateid'] !== '') {
					continue;
				}

				$this->_ImportFields['custom'][$fieldId] = htmlentities($fields[$fieldId]->record['formfieldlabel']);
			}
		}

		$this->customerEntity = new ISC_ENTITY_CUSTOMER();
		$this->groupEntity = new ISC_ENTITY_CUSTOMERGROUP();
		$this->shippingEntity = new ISC_ENTITY_SHIPPING();

		parent::__construct($fields);
	}

	/**
	 * Custom step 1 code specific to product importing. Calls the parent ImportStep1 funciton.
	 */
	protected function _ImportStep1($MsgDesc="", $MsgStatus="")
	{
		if ($MsgDesc != "" && !isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		// Set up generic import options
		parent::_ImportStep1();
	}

	/**
	 * Custom step 2 code specific to product importing. Calls the parent ImportStep2 funciton.
	 */
	protected function _ImportStep2($MsgDesc="", $MsgStatus="")
	{
		if (!empty($_POST)) {
			$this->ImportSession['IsBulkEdit'] = isset($_POST['BulkEditTemplate']);
			if ($this->ImportSession['IsBulkEdit']) {
				$_POST['OverrideDuplicates'] = 1;
				$_POST['Headers'] = 1;
			}
		}

		// Set up generic import options

		if ($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		parent::_ImportStep2();
	}

	protected function _GetMultiFields()
	{
		if (!$this->ImportSession['IsBulkEdit']) {
			return array();
		}

		// look for images
		$multiFields = array(
			'addresses' => array(
				'prefix' => 'ship',
				'regex' => 'Address Line 1',
				'fields' => array(
					"shipid" => GetLang('CustomerAddressID'),
					"shipfirstname" => GetLang('CustomerAddressFirstName'),
					"shiplastname" => GetLang('CustomerAddressLastName'),
					"shipaddress1" => GetLang('CustomerAddressLine1'),
					"shipaddress2" => GetLang('CustomerAddressLine2'),
					"shipcity" => GetLang('CustomerAddressCity'),
					"shipstate" => GetLang('CustomerAddressState'),
					"shipzip" => GetLang('CustomerAddressZip'),
					"shipcountry" => GetLang('CustomerAddressCountry'),
					"shipphone" => GetLang('CustomerAddressPhone')
				)
			)
		);

		return $multiFields;
	}

	/**
	 * Imports an actual product record in to the database.
	 *
	 * @param array Array of record data
	 */
	protected function _ImportRecord($record)
	{
		static $customerGroups=array();

		if(!$record['custconemail']) {
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportCustomersMissingEmail');
			return;
		}

		if(!is_email_address($record['custconemail'])) {
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportCustomersInvalidEmail');
			return;
		}

		// Is there an existing customer with the same email?
		$existingCustomer = null;
		$existingFormSessionId = 0;
		$searchFields = array(
			"custconemail" => array(
								"value" => isc_strtolower($record["custconemail"]),
								"func" => "LOWER"
							)
		);

		$customerId = $this->customerEntity->search($searchFields);
		if (isId($customerId)) {
			$existingCustomer = $this->customerEntity->get($customerId);
		} else {
			$customerId = 0;
		}

		if(is_array($existingCustomer)) {
			// Overriding existing customer, set the customer id
			if(isset($this->ImportSession['OverrideDuplicates']) && $this->ImportSession['OverrideDuplicates'] == 1) {
				$this->ImportSession['Results']['Updates'][] = $record['custconfirstname']." ".$record['custconlastname']." (".$record['custconemail'].")";
			}
			else {
				$this->ImportSession['Results']['Duplicates'][] = $record['custconfirstname']." ".$record['custconlastname']." (".$record['custconemail'].")";
				return;
			}

			if (isId($existingCustomer['custformsessionid'])) {
				$existingFormSessionId = $existingCustomer['custformsessionid'];
			}
		} else {

			/**
			 * Fill in the blanks only if we are adding in a customer
			 */
			$fillin = array('custconcompany', 'custconfirstname', 'custconlastname', 'custconphone');
			foreach ($fillin as $fillkey) {
				if (!isset($record[$fillkey])) {
					$record[$fillkey] = '';
				}
			}
		}

		$customerData = array();

		foreach (array_keys($this->_ImportFields) as $field) {
			if (substr($field, 0, 4) == "cust" && array_key_exists($field, $record)) {
				$customerData[$field] = $record[$field];
			}
		}

		if(isset($customerData["custstorecredit"])) {
			$customerData["custstorecredit"] = DefaultPriceFormat($customerData["custstorecredit"]);
		}

		if (array_key_exists("custgroup", $customerData)) {
			$customerData["custgroupid"] = $customerData["custgroup"];
		}

		if (isId($customerId)) {
			$customerData["customerid"] = $customerId;
		}

		// Are we placing the customer in a customer group?
		$groupId = 0;
		if (array_key_exists("custgroup", $record) && trim($record["custgroup"]) !== "") {
			$groupName = strtolower($record['custgroup']);
			if(isset($customerGroups[$groupName])) {
				$groupId = $customerGroups[$groupName];
			}
			else {
				$searchFields = array(
					"groupname" => array(
									"value" => isc_strtolower($groupName),
									"func" => "LOWER"
								)
				);

				$groupId = $this->groupEntity->search($searchFields);

				// Customer group doesn't exist, create it
				if(!isId($groupId)) {

					$newGroup = array(
						'groupname' => $record['custgroup'],
						'discount' => 0,
						'isdefault' => 0,
						'categoryaccesstype' => 'all'
					);

					$groupId = $this->groupEntity->add($newGroup);
				}

				if($groupId) {
					$customerGroups[$groupName] = $groupId;
				}
			}
		}
		$customerData['custgroupid'] = $groupId;

		// Do we have a shipping address?
		$shippingData = array();

		 if (!$this->ImportSession['IsBulkEdit']) {
			// Don't import the address if we are missing the street address
			if(isset($record['shipaddress1']) && trim($record['shipaddress1']) !== "" && (isset($record['shipfirstname']) || isset($record['shipaddress2']) || isset($record['shipcity']) || isset($record['shipstate']) || isset($record['shipzip']) || isset($record['shipcountry']))) {
				$shippingData[] = $this->ParseAddress($record, $customerId);
			}
		}
		else { // bulk edit import
			// search for addresses
			for ($x = 1; $x <= $this->ImportSession['AddressCount']; $x++) {
				if(isset($record['shipaddress1' . $x]) && trim($record['shipaddress1' . $x]) !== "" && (isset($record['shipfirstname' . $x]) || isset($record['shipaddress2' . $x]) || isset($record['shipcity' . $x]) || isset($record['shipstate' . $x]) || isset($record['shipzip' . $x]) || isset($record['shipcountry' . $x]))) {
					$shippingData[] = $this->ParseAddress($record, $customerId, $x);
				}
			}
		}

		if (!empty($shippingData)) {
			$customerData['addresses'] = $shippingData;
		}

		/**
		 * Handle any of the customer custom fields that we might have
		 */
		if (!empty($this->customFields) && array_key_exists('custom', $record)) {
			$formSessionId = $this->_importCustomFormfields(FORMFIELDS_FORM_ACCOUNT, $record['custom'], $existingFormSessionId);

			if (isId($formSessionId)) {
				$customerData['custformsessionid'] = $formSessionId;
			}
		}

		$customerData['is_import'] = true;

		// New customer, insert in to DB
		if($customerId == 0) {
			// Set a temporary password, retrievable later via lost password function
			if(!isset($customerData['custpassword']) || $customerData['custpassword'] == '') {
				$customerData['custpassword'] = isc_substr(uniqid(rand(), true), 0, 10);
			}

			$customerData['customertoken'] = GenerateCustomerToken();

			$rtn = $this->customerEntity->add($customerData);
			++$this->ImportSession['Results']['SuccessCount'];
		}
		// Updating an existing customer
		else {
			$rtn = $this->customerEntity->edit($customerData);
		}
	}

	private function ParseAddress($record, $customerId, $index = '')
	{
		$shippingData = array();

		$fillin = array('shipaddress1', 'shipaddress2', 'shipcity', 'shipstate', 'shipzip', 'shipcountry');
		foreach ($fillin as $fillkey) {
			if (!isset($record[$fillkey . $index])) {
				$record[$fillkey . $index] = '';
			}
		}

		if (isId($customerId)) {
			$shippingData["shipcustomerid"] = $customerId;
		}

		$shippingData['shipid'] = 0;
		if (!empty($record['shipid' . $index])) {
			$shippingData['shipid'] = $record['shipid' . $index];
		}

		$shippingData['shipfirstname'] = $record['shipfirstname' . $index];
		$shippingData['shiplastname'] = $record['shiplastname' . $index];
		$shippingData['shipaddress1'] = $record['shipaddress1' . $index];
		$shippingData['shipaddress2'] = $record['shipaddress2' . $index];
		$shippingData['shipcity'] = $record['shipcity' . $index];
		$shippingData['shipstate'] = $record['shipstate' . $index];
		$shippingData['shipzip'] = $record['shipzip' . $index];
		$shippingData['shipcountry'] = $record['shipcountry' . $index];
		$shippingData['shipstateid'] = 0;
		$shippingData['shipcountryid'] = 0;
		$shippingData['shipdestination'] = '';

		// Find the country and state
		$shippingData['shipcountryid'] = (int)GetCountryByName($record['shipcountry' . $index]);
		if(!$shippingData['shipcountryid']) {
			$shippingData['shipcountryid'] = (int)GetCountryIdByISO2($record['shipcountry' . $index]);
		}

		// Still nothing? 0 for the shipping country ID
		if(!$shippingData['shipcountryid']) {
			$shippingData['shipcountryid'] = 0;
		}

		if(isset($record['shipstate' . $index])) {
			$shippingData['shipstateid'] = GetStateByName($record['shipstate' . $index], $shippingData['shipcountryid']);
		}

		// Still nothing? 0 for the shipping state ID
		if(!$shippingData['shipstateid']) {
			$shippingData['shipstateid'] = 0;
		}

		if(!isset($record['shipphone' . $index]) && isset($record['custconphone' . $index])) {
			$shippingData['shipphone'] = $record['custconphone' . $index];
		}
		else {
			$shippingData['shipphone'] = $record['shipphone' . $index];
		}

		/**
		 * Handle any of the address custom fields that we might have
		 */
		if (!empty($this->customFields) && array_key_exists('custom', $record)) {
			$shippingData['shipformsessionid'] = $this->_importCustomFormfields(FORMFIELDS_FORM_ADDRESS, $record['custom' . $index]);

			if (!isId($shippingData['shipformsessionid'])) {
				unset($shippingData['shipformsessionid']);
			}
		}

		return $shippingData;
	}
}