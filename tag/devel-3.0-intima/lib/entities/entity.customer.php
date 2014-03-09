<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'entity.base.php');

class ISC_ENTITY_CUSTOMER extends ISC_ENTITY_BASE
{
	private $shipping;
	private $group;

	protected $useTransactions;
	
	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$schema = array(
				"customerid" => "int",
				"custpassword" => "text",
				"custconcompany" => "text",
				"custconfirstname" => "text",
				"custconlastname" => "text",
				"custconemail" => "text",
				"custconphone" => "text",
				"customertoken" => "text",
				"customerpasswordresettoken" => "text",
				"customerpasswordresetemail" => "text",
				"custdatejoined" => "date",
				"custlastmodified" => "date",
				"custimportpassword" => "text",
				"custstorecredit" => "price",
				"custregipaddress" => "text",
				"custgroupid" => "int",
				"custnotes" => "text",
				"custformsessionid" => "int",
				"custRFC" => "varchar",
		);

		$tableName = "customers";
		$primaryKeyName = "customerid";
		$searchFields = array(
				"customerid",
				"custgroupid",
				"custconfirstname",
				"custconlastname",
				"custconemail",
				"custconphone"
		);

		$customKeyName = "custformsessionid";

		parent::__construct($schema, $tableName, $primaryKeyName, $searchFields, $customKeyName);

		$this->shipping = new ISC_ENTITY_SHIPPING();
		$this->group = new ISC_ENTITY_CUSTOMERGROUP();
		
		$this->useTransactions = true;
	}

	protected function getPosthook($customerId, &$customer)
	{
		$customer["addresses"] = array();
		$query = "SELECT *
					FROM [|PREFIX|]shipping_addresses
					WHERE shipcustomerid = " . (int)$customerId;

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($addr = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$customer["addresses"][] = $this->shipping->get($addr["shipid"], $customerId);
		}

		if (!isId($customer["custgroupid"])) {
			$query = "SELECT *
						FROM [|PREFIX|]customer_groups
						WHERE isdefault='1'";

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

			if ($row) {
				$customer["custgroupid"] = $row["customergroupid"];
			}
		}

		$customer['customergroup'] = null;
		if (isId($customer["custgroupid"])) {
			$customer["customergroup"] = $this->group->get($customer["custgroupid"]);
		}
	}

	protected function addPrehook(&$savedata, $rawInput)
	{
		if (!array_key_exists("custgroupid", $rawInput) || !isId($rawInput["custgroupid"])) {
			$savedata["custgroupid"] = 0;
		}

		if (!array_key_exists("is_import", $rawInput) || !$rawInput["is_import"]) {
			$savedata["custregipaddress"] = GetIP();
		}

		$savedata["custdatejoined"] = time();
		$savedata["custlastmodified"] = time();

		return true;
	}

	protected function addPosthook($customerId, $savedata, $rawInput)
	{
		$this->updatePassword($customerId, $rawInput['custpassword']);
		$newUser = true;
		if(GetConfig('AccountCreationInactiveUsers') && $savedata['custnotes'] == 'INACTIVO'){
			$newUser = false;
		}
		
		if($newuser && !isFromCron('pendingjobs') && GetConfig('isIntelisis') && (GetConfig('syncIWSurl') != '' || GetConfig('syncDropboxActive') == 1)){
			
			foreach ($rawInput["addresses"] as $key => $address) {
				$address["shipcustomerid"] = $customerId;
				if(!isset($address['GUID']) || $address['GUID'] == '') $rawInput["addresses"][$key]['GUID'] = gen_uuid();
			}
			
			$IWS_customer = new ISC_INTELISIS_WS_CUSTOMER($customerId, $rawInput, 'add');
			if(!$IWS_customer->prepareRequest()) return false;
		}
		
		$StoreOrigin = getCustomerStoreOrigin($customerId);
		if(!isFromCron('pendingjobs') && GetConfig('isIntelisis') && is_numeric($StoreOrigin)){
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_customers', array('Sucursal' => $StoreOrigin), 'customerid = "'.$customerId.'"');
		}
		
		if (array_key_exists("addresses", $rawInput) && is_array($rawInput["addresses"]) && !empty($rawInput["addresses"])) {

			/**
			 * Sanatise the addresses, just in case
			 */
			if (is_associative_array($rawInput["addresses"])) {
				$rawInput["addresses"] = array($rawInput["addresses"]);
			}

			foreach ($rawInput["addresses"] as $address) {
				$address["shipcustomerid"] = $customerId;
				$this->shipping->add($address);
			}
		}
		
		return true;
	}

	protected function editPrehook($customerId, &$savedata, $rawInput)
	{
		$savedata["custlastmodified"] = time();

		return true;
	}
	
	protected function editPosthook($customerId, &$savedata, $rawInput)
	{
		$newUser = true;
		if(GetConfig('AccountCreationInactiveUsers') && $savedata['custnotes'] == 'INACTIVO'){
			$newUser = false;
		}
		
		if($newUser && !isFromCron('pendingjobs') && GetConfig('isIntelisis') && (GetConfig('syncIWSurl') != '' || GetConfig('syncDropboxActive') == 1)){
			$IWS_customer = new ISC_INTELISIS_WS_CUSTOMER($customerId, $rawInput, 'edit');
			if(!$IWS_customer->prepareRequest()) return false;
		}

		$StoreOrigin = getCustomerStoreOrigin($customerId);
		if(!isFromCron('pendingjobs') && GetConfig('isIntelisis') && is_numeric($StoreOrigin)){
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_customers', array('Sucursal' => $StoreOrigin), 'customerid = "'.$customerId.'"');
		}
	}

	/**
	 * Edit a customer
	 *
	 * Method will edit a customer's details
	 *
	 * @access public
	 * @param array $input The customer's details
	 * @param int $customerId The optional customer ID. Default will be $input[$this->primaryKeyName]
	 * @param bool $isFullAddressBook TRUE to delete any addresses first before adding them in. Default is FALSE
	 * @param bool $filterUniqueAddress TRUE to only insert unique addresses, FALSE not to check. Default is FALSE
	 * @return bool TRUE if the customer exists and the details were updated successfully, FALSE oterwise
	 */
	public function edit($input, $customerId='', $isFullAddressBook=false, $filterUniqueAddress=false)
	{
		if (!parent::edit($input, $customerId)) {
			return false;
		}

		if (!isId($customerId)) {
			$customerId = $input[$this->primaryKeyName];
		}

		if (array_key_exists("addresses", $input) && is_array($input["addresses"]) && !empty($input["addresses"])) {

			/**
			 * Do we need to clear out our address book first?
			 */
			if ($isFullAddressBook) {
				$this->shipping->deleteByCustomer($customerId);
			}

			/**
			 * Sanatise the addresses, just in case
			 */
			if (is_associative_array($input["addresses"])) {
				$input["addresses"] = array($input["addresses"]);
			}

			foreach ($input["addresses"] as $address) {
				$address['shipcustomerid'] = $customerId;

				if (!$isFullAddressBook && array_key_exists("shipid", $address) && isId($address["shipid"])) {
					$this->shipping->edit($address);
				} else if (!$filterUniqueAddress || !$this->shipping->basicSearch($address)) {
					$this->shipping->add($address);
				}
			}
		}

		if (isset($input['custpassword']) && !empty($input['custpassword'])) {
			// change password from edit screen
			$this->updatePassword($customerId, $input['custpassword']);
		}

		return true;
	}

	/**
	 * Update customer password
	 *
	 * @param integer $customerid The customer ID.
	 * @param string  $password   The new password in plain text
	 *
	 * @return boolean
	 */
	public function updatePassword($customerid, $password)
	{
		$where = "customerid='".$GLOBALS['ISC_CLASS_DB']->Quote($customerid)."'";
		$salt = substr(md5(uniqid()), 0, 16);
		$customer = array(
			'salt' => $salt,
			'custpassword' => $this->generatePasswordHash($password, $salt),
			'custlastmodified' => time(),
			'custimportpassword' => '',
		);
		$GLOBALS['ISC_CLASS_DB']->updateQuery('customers', $customer, $where);

		return true;

	}//end updatePassword()


	/**
	 * Generate the hashed password
	 *
	 * @param string  $password The plain text password to hash
	 * @param string  $salt     The salt string
	 *
	 * @return string
	 */
	public function generatePasswordHash($password, $salt)
	{
		$hash = md5($salt.sha1($salt.$password));
		$hash = substr($hash, 0, 50);

		return $hash;

	}//end generatePasswordHash()

	/**
	 * Edit the customer's group ID
	 *
	 * Method will only edit the customer's group ID
	 *
	 * @access public
	 * @param int $customerId The customer ID
	 * @param int $customerGroupId The new customer group ID. Default is 0 (the default group)
	 * @return bool TRUE if the customer's group was successfully updated, FALSE otherwise
	 */
	public function editGroup($customerId, $customerGroupId=0)
	{
		if (!isId($customerId) || trim($customerGroupId) == "") {
			return false;
		}

		$savedata = array(
			"custgroupid" => $customerGroupId
		);

		return $this->edit($savedata, $customerId);
	}

	protected function deletePosthook($customerId, $customer)
	{
		/**
		 * Delete all of the associated addresses
		 */
		$query = "SELECT shipid
					FROM [|PREFIX|]shipping_addresses
					WHERE shipcustomerid = " . (int)$customerId;

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$this->shipping->delete($row["shipid"]);
		}

		return true;
	}
}
