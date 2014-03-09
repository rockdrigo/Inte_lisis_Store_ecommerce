<?php

/**
 * Load up our personal libraries
 */
include_once(dirname(__FILE__) . "/includes/classes/class.exception.php");

define("ACCOUNTING_QUICKBOOKS_TYPE_QUICKBOOKS", "quickbooks");
define("ACCOUNTING_QUICKBOOKS_TYPE_SHOPPINGCART", "shoppingcart");
@ini_set('display_errors', 'Off');

class ACCOUNTING_QUICKBOOKS extends ISC_ACCOUNTING
{
	const ownerId = "0749cafc-62f5-102b-83a8-001a4d0000c0";
	const qbType = "QBFS";
	const supportedQBWCVersion = 2;
	const supportedXMLVersion = 6.0;
	const generalErrNo = 69;
	const generalErrMsg = "Cart internal error [%s]";
	const orderIDPrefix = "EC:";
	const parentSeparator = ":";
	const parentSeparatorReplacement = ";";
	const customerShortNameRegEx = '#^(.+)\\[(\\d+)\\]$#';
	const customerShortNameFormat = "%s[%05d]";
	const customerShortNameLength = 41;
	const customerShortNameNameLength = 34;
	const customerGuestShortNameRegEx = '#^(.+)\\[(\\d+)G\\]$#';
	const customerGuestShortNameFormat = "%s[%05dG]";
	const customerGuestShortNameLength = 41;
	const customerGuestShortNameNameLength = 33;
	const productShortNameRegEx = '#^(.+)\\[(\\d+)\\]$#';
	const productShortNameFormat = "%s[%05d]";
	const productShortNameLength = 31;
	const productShortNameNameLength = 24;
	const productVariationShortNamePostFix = "V]";
	const productVariationShortNameRegEx = '#^(.+)\\[(\\d+)V\\]$#';
	const productVariationShortNameFormat = "%s[%05dV]";
	const productVariationShortNameLength = 31;
	const productVariationShortNameNameLength = 23;

	private $password;
	private $username;
	private $fileId;
	private $initData;
    private $logcount;
	private $qbInvalidCharMap;
	private $qbInvalidRefMap;

	public $supportURL;

	public function __construct()
	{
		// Setup the required variables for the module
		parent::__construct();
		$this->name = GetLang("QuickBooksName");
		$this->description = GetLang("QuickBooksDesc");
		$this->help = GetLang("QuickBooksHelp");

		// Details for the QBWC file
		$this->password = $this->getSetupVariable("password");
		$this->username = $this->getSetupVariable("username");
		$this->fileId  = $this->getSetupVariable("fileid");
		$this->fileId .= substr(self::ownerId, 8);

		// Edit the help so we can display the download link and password for the QBWC file
		if (ModuleIsConfigured($this->getid())) {
			$this->help .= sprintf(GetLang('QuickBooksPasswordSection'), $this->password);
		}

		$this->supportURL = GetConfig("ShopPathSSL") . "/accountinggateway.php?action=showSupportQuickBooks";

		$this->initData = array(
									array(
											"Name" => GetLang("QuickBooksIncomeAccountName"),
											"Desc" => GetLang("QuickBooksIncomeAccountDesc"),
											"Service" => "Account",
											"Type" => "income",
											"AccountType" => "Income"
										),
									array(
											"Name" => GetLang("QuickBooksCOGSAccountName"),
											"Desc" => GetLang("QuickBooksCOGSAccountDesc"),
											"Service" => "Account",
											"Type" => "cogs",
											"AccountType" => "CostOfGoodsSold"
										),
									array(
											"Name" => GetLang("QuickBooksAssetAccountName"),
											"Desc" => GetLang("QuickBooksAssetAccountDesc"),
											"Service" => "Account",
											"Type" => "fixed",
											"AccountType" => "FixedAsset"
										),
									array(
											"Name" => GetLang("QuickBooksTaxItem"),
											"Desc" => GetLang("QuickBooksTaxItemDesc"),
											"Service" => "ItemOtherCharge",
											"Type" => "tax"
										),
									array(
											"Name" => GetLang("QuickBooksShippingItem"),
											"Desc" => GetLang("QuickBooksShippingItemDesc"),
											"Service" => "ItemOtherCharge",
											"Type" => "shipping"
										),
									array(
											"Name" => GetLang("QuickBooksDiscountItem"),
											"Desc" => GetLang("QuickBooksDiscountItemDesc"),
											"Service" => "ItemOtherCharge",
											"Type" => "discount"
										),
									array(
											"Name" => GetLang("QuickBooksParentTypeCustomerNormal"),
											"Desc" => GetLang("QuickBooksParentTypeCustomerNormalDesc"),
											"Service" => "Customer",
											"Type" => "normal",
										),
									array(
											"Name" => GetLang("QuickBooksParentTypeCustomerGuestCheckout"),
											"Desc" => GetLang("QuickBooksParentTypeCustomerGuestCheckoutDesc"),
											"Service" => "Customer",
											"Type" => "guestcheckout"
										),
									array(
											"Name" => GetLang("QuickBooksParentTypeProductNormal"),
											"Desc" => GetLang("QuickBooksParentTypeProductNormalDesc"),
											"Service" => "ItemInventory",
											"Type" => "normal"
										),
									array(
											"Name" => GetLang("QuickBooksParentTypeProductVariations"),
											"Desc" => GetLang("QuickBooksParentTypeProductVariationsDesc"),
											"Service" => "ItemInventory",
											"Type" => "productvariation"
										),
								);

		$this->qbInvalidCharMap = array(
			"8482" => "2122",
			"169" => "00A9",
			"174" => "00AE"
		);

		$this->qbInvalidRefMap = array(
			"8482" => "153"
		);

		// Setup the custom variables
		$this->setCustomVars();

		// Load saved variables from the database
		$this->loadCustomVars();
	}

	/**
	* Custom variables for the checkout module. Custom variables are stored in the following format:
	* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	* variable_type types are: text,number,password,radio,dropdown
	* variable_options is used when the variable type is radio or dropdown and is a name/value array.
	*/
	public function setCustomVars()
	{
		$this->_variables["type"] = array(
				"name" => GetLang("QuickBooksType"),
				"type" => "dropdown",
				"help" => GetLang("QuickBooksTypeHelp"),
				"default" => "ProStandard",
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
								GetLang("QuickBooksTypeProStandard") => "ProStandard",
								GetLang("QuickBooksTypePremierStandard") => "PremierStandard",
								GetLang("QuickBooksTypeProUK") => "ProUK",
								GetLang("QuickBooksTypePremierUK") => "PremierUK",
								GetLang("QuickBooksTypeProCanada") => "ProCanada",
								GetLang("QuickBooksTypePremierCanada") => "PremierCanada"
				),
				"multiselect" => false
			);

		$this->_variables["scheduler"] = array(
				"name" => GetLang("QuickBooksScheduler"),
				"type" => "dropdown",
				"help" => GetLang("QuickBooksSchedulerHelp"),
				"default" => "never",
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
								GetLang("QuickBooksScheduler1day") => "1440",
								GetLang("QuickBooksScheduler2day") => "2880",
								GetLang("QuickBooksScheduler1week") => "10080"
				),
				"multiselect" => false
			);

		$this->_variables["sync"] = array(
				"heading" => GetLang("QuickBooksSyncSettings"),
				"name" => GetLang("QuickBooksShowSync"),
				"type" => "dropdown",
				"help" => GetLang("QuickBooksShowSyncHelp"),
				"required" => false,
				"options" => array(
								GetLang("QuickBooksShowSyncCustomerOption") => "customer",
								GetLang("QuickBooksShowSyncProductOption") => "product",
								GetLang("QuickBooksShowSyncOrderOption") => "order"
				),
				"multiselect" => true
			);

		$this->_variables["orderoption"] = array(
				"heading" => GetLang("QuickBooksOrderSettings"),
				"name" => GetLang("QuickBooksShowSalesOrderOption"),
				"type" => "custom",
				"callback" => "buildCustomFieldCallback",
				"required" => true,
				"help" => GetLang("QuickBooksShowSalesOrderOptionHelp")
			);

		$this->_variables["invlevels"] = array(
				"heading" => GetLang("QuickBooksProductSettings"),
				"name" => GetLang("QuickBooksShowInvLevels"),
				"type" => "dropdown",
				"help" => GetLang("QuickBooksShowInvLevelsHelp"),
				"default" => ACCOUNTING_QUICKBOOKS_TYPE_QUICKBOOKS,
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
								GetLang("QuickBooksShowInvLevelsQuickBooksOption") => ACCOUNTING_QUICKBOOKS_TYPE_QUICKBOOKS,
								GetLang("QuickBooksShowInvLevelsShoppingCartOption") => ACCOUNTING_QUICKBOOKS_TYPE_SHOPPINGCART,
								GetLang("QuickBooksShowInvLevelsNoSyncOption") => "none"
				),
				"multiselect" => false
			);

		$this->_variables["newprodcategoryidx"] = array(
				"name" => GetLang("QuickBooksShowNewProdCategoryIDX"),
				"type" => "custom",
				"callback" => "buildCustomFieldCallback",
				"help" => GetLang("QuickBooksShowNewProdCategoryIDXHelp"),
				"required" => true
			);

		if (!ModuleIsConfigured($this->getId())) {
			$this->_variables["sync"]["default"] = array("customer", "product", "order");
		}
	}

	/**
	 * Check to see if QuickBooks can run or not
	 *
	 * Method will check to see if this module has all the included functions to work
	 *
	 * @access public
	 * @return bool TRUE if the module is supported, FALSE if not
	 */
	public function IsSupported()
	{
		if (!class_exists("SoapServer")) {
			$this->SetError(GetLang("QuickBooksSOAPNotAvailable"));
			return false;
		}

		if (!function_exists("simplexml_load_string") || !class_exists("SimpleXMLElement")) {
			$this->SetError(GetLang("QuickBooksXMLNotAvailable"));
			return false;
		}

		if (!class_exists("XMLWriter")) {
			$this->SetError(GetLang("QuickBooksXMLWriterNotAvailable"));
			return false;
		}

		return true;
	}

	/**
	 * Get the supported XML version
	 *
	 * Method will return the supported XML version
	 *
	 * @access public
	 * @return float The supported XML version
	 */
	public function getSupportedXMLVersion()
	{
		return (float)self::supportedXMLVersion;
	}

	/**
	 * Get the supported QBWC version
	 *
	 * Method will return the supported QBWC version
	 *
	 * @access public
	 * @return float The supported QBWC version
	 */
	public function getSupportedQBWCVersion()
	{
		return (float)self::supportedQBWCVersion;
	}

	/**
	 * Build the custom field
	 *
	 * Method will build the custom field used in the settings admin
	 *
	 * @access public
	 * @param string $id The field ID
	 * @return string The built field HTML on success, empty string on error
	 */
	public function buildCustomFieldCallback($id)
	{
		if (trim($id) == '') {
			return '';
		}

		switch (isc_strtolower($id)) {
			case "orderoption":
				$orderOption = $this->getValue("orderoption");

				if ($orderOption == "order") {
					$GLOBALS["OrderTypeReceiptSelected"] = "";
					$GLOBALS["OrderTypeOrderSelected"] = " selected";
				} else {
					$GLOBALS["OrderTypeReceiptSelected"] = " selected";
					$GLOBALS["OrderTypeOrderSelected"] = "";
				}

				return $this->ParseTemplate("order.type", true);
				break;

			case "newprodcategoryidx":
				$GLOBALS["ISC_CLASS_ADMIN_CATEGORY"] = GetClass("ISC_ADMIN_CATEGORY");

				if (!ModuleIsConfigured($this->getid())) {
					$newProdCategoryIDX = array();

					$query = "SELECT categoryid FROM [|PREFIX|]categories";
					$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

					while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
						$newProdCategoryIDX[] = $row["categoryid"];
					}

				} else {
					$newProdCategoryIDX = $this->getValue("newprodcategoryidx");

					if (isId($newProdCategoryIDX)) {
						array($newProdCategoryIDX);
					}

					if (!is_array($newProdCategoryIDX)) {
						$newProdCategoryIDX = array();
						$newProdCategoryIDX[] = $this->getValue("newprodcategoryidx");
					}
				}
				$newProdCategoryIDX = array_filter($newProdCategoryIDX, "isId");

				$GLOBALS["CategoryOptions"] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions($newProdCategoryIDX, "<option %s value='%d'>%s</option>", "selected=\"selected\"", "", false);

				return $this->ParseTemplate("product.category", true);
				break;

			default:
				return "";
				break;
		}
	}

	/**
	 * Get the last time the import was executed
	 *
	 * Method will return the last time the import was executed. Return 0 if this is the first time
	 *
	 * @access public
	 * @param string $type The optional type of node to find the last imported date. Dedault is all
	 * @return mixed The timestamps of the last import. 0 If this is the first one
	 */
	public function getLastImportedTimeStamp($type='')
	{
		$timestamps = $this->getSetupVariable("lastimportedtime");

		if (trim($timestamps) == '') {
			return 0;
		}

		$timestamps = explode("|", $timestamps);
		$newTimeStamps = array();

		foreach ($timestamps as $timestamp) {
			$parts = explode(":", $timestamp, 2);

			if (!is_array($parts) || count($parts) != 2) {
				continue;
			}

			$newTimeStamps[$parts[0]] = $parts[1];
		}

		if (trim($type) == '') {
			if (is_array($newTimeStamps)) {
				return $newTimeStamps;
			} else {
				return array();
			}
		} else if (isset($newTimeStamps[$type])) {
			if (is_numeric($newTimeStamps[$type])) {
				return $newTimeStamps[$type];
			} else {
				return 0;
			}
		}
	}


	/**
	 * Set the last time the import was executed
	 *
	 * Method will set the last time the import was executed
	 *
	 * @access public
	 * @param string $type The option node type. Default is all node types
	 * @param int $timestamp The optional timestamp. Default is now
	 * @return bool TRUE if the time was saved, FALSE on error
	 */
	public function setLastImportedTimeStamp($type='', $timestamp='')
	{
		if (trim($timestamp) == '') {
			$timestamp = time();
		}

		if (!is_numeric($timestamp)) {
			return false;
		}

		/**
		 * Add 5 seconds to the timestamp as it looks like QB has delayed transactions when
		 * we sync the data across
		 */
		$timestamp += 5;

		$newTimeStamp = $this->getLastImportedTimeStamp();

		if (!is_array($newTimeStamp)) {
			$newTimeStamp = array();
		}

		if (trim($type) !== '') {
			$newTimeStamp[trim($type)] = $timestamp;
		} else {
			foreach (array_keys($newTimeStamp) as $key) {
				$newTimeStamp[$key] = $timestamp;
			}
		}

		$compressedTimeStamps = array();

		foreach ($newTimeStamp as $timeType => $timeValue) {
			$compressedTimeStamps[] = $timeType . ":" . $timeValue;
		}

		$compressedTimeStamps = implode("|", $compressedTimeStamps);

		if ($this->setSetupVariable("lastimportedtime", $compressedTimeStamps)) {
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateAccountingModuleVars();
			return true;
		}

		return false;
	}

	/**
	 * Wrapper method for the parent::buildSpool2Get() method
	 *
	 * Method is basically a wrapper for the parent::buildSpool2Get() method. This is defined to handle the special
	 * case of the 'prerequisite' spool. All error handling should still be handled by the parent::buildSpool2Get()
	 * method
	 *
	 * @access protected
	 * @param int $spool The saved spool array
	 * @return array The spool data array
	 */
	protected function buildSpool2Get($spool)
	{
		$parsedSpool = parent::buildSpool2Get($spool);

		if (isset($parsedSpool["nodeType"]) && $parsedSpool["nodeType"] == "prerequisite") {

			$searchData = array(
				"Service" => $spool["nodeData"]["Service"],
				"Type" => $spool["nodeData"]["Type"]
			);

			$reference = $this->getReference($parsedSpool["nodeType"], $searchData, '', '', false);

			if (is_array($reference)) {
				$parsedSpool["referenceId"] = $reference["accountingrefid"];
				$parsedSpool["referenceData"] = $reference["accountingrefvalue"];
			}
		}

		return $parsedSpool;
	}

	/**
	 * Set an internal error for a spool
	 *
	 * Method will set an internal error for a spool. Basically its a way for us to mark an error for a spool. The error
	 * number is self::generalErrNo which is not being used by QB.
	 *
	 * This method is mainly used in the ACCOUNTING_QUICKBOOKS_HANDLER_RECEIVERESPONSEXML() class
	 *
	 * @access public
	 * @param mixed $spool The spool ID/array
	 * @param string $errMsg The optional error message to be used inside self::generalErrMsg
	 * @return bool TRUE if the error was set, FALSE on error
	 */
	public function setInternalSpoolError($spool, $errMsg="")
	{
		$errMsg = sprintf(self::generalErrMsg, $errMsg);
		return $this->setSpoolError($spool, self::generalErrNo, $errMsg);
	}

	/**
	 * Initialise the module
	 *
	 * Method will run the necessary operations for initialising the module. Each accounting module will have this module
	 *
	 * @access public
	 * @return bool true if is the initialising was asuccessful, FALSE otherwise
	 */
	public function initModule()
	{
		/**
		 * Create and save our auth details if we haven't already
		 */
		if ($this->password == '') {
			$this->setSetupVariable("password", substr(md5(uniqid(mt_rand(), true)), 0, 12));
			$this->setSetupVariable("username", GetConfig("AdminEmail"));
			$this->setSetupVariable("fileid", substr(md5(GetConfig("AdminEmail")), 0, 8));
		}


	}

	/**
	 * Pre-initialise the import process
	 *
	 * Method will get called in the parent import method parent::initImport() before the main initialisation
	 *
	 * @access protected
	 * @return bool TRUE if the pre-initialisation was successful, FALSE if not
	 */
	protected function initPreImport()
	{
		/**
		 * Check to see if all the account services have already been done
		 */
 		foreach ($this->initData as $data) {
			$searchData = array(
				"Service" => $data["Service"],
				"Type" => $data["Type"],
			);

			if ($this->getReference("prerequisite", $searchData)) {
				$this->setSpool("prerequisite", "edit", $data);
			} else {
				$this->setSpool("prerequisite", "add", $data);
			}
 		}
		$GLOBALS['custcount'] = 0;
		/**
		 * Next we add in out syncing spools if we can
		 */
		$syncOptions = $this->getValue("sync");

		if (!is_array($syncOptions) && is_scalar($syncOptions)) {
			$syncOptions = array($syncOptions);
		}

		if (is_array($syncOptions)) {
			$syncProductLevels = false;

			/**
			 * Rework the ordering as 'order' must be first
			 */
			$newSyncOptions = array();

			if (in_array("order", $syncOptions)) {
				$newSyncOptions[] = "order";

				foreach ($syncOptions as $syncType) {
					if ($syncType == "order") {
						continue;
					}

					$newSyncOptions[] = $syncType;
				}
			}

			foreach ($newSyncOptions as $syncType) {
				$this->setSpool($syncType, "sync", array());

				/**
				 * Special case here for product variations
				 */
				if (isc_strtolower(trim($syncType)) == "product") {
					$this->setSpool("productvariation", "sync", array());
					$syncProductLevels = true;
				}
			}

			/**
			 * Inventory levels should be synced after we've imported all the products and orders
			 */
			if ($syncProductLevels && $this->getValue("invlevels") !== "none") {
				$this->setSpool("productlevel", "sync", array());
			}
		}

		return true;
	}

	/**
	 * Post-initialise the import process
	 *
	 * Method will get called in the parent import method parent::initImport() after the main initialisation
	 *
	 * @access protected
	 * @return bool TRUE if the post-initialisation was successful, FALSE if not
	 */
	protected function initPostImport()
	{
		return true;
	}

	/**
	 * Pre-closing the import process
	 *
	 * Method will get called in the parent import method parent::closeImport() before the import is closed
	 *
	 * @access protected
	 * @return bool TRUE if the pre-close (closeation?) was successful, FALSE if not
	 */
	protected function closePreImport()
	{
		return true;
	}

	/**
	 * Post-closing the import process
	 *
	 * Method will get called in the parent import method parent::closeImport() after the import has been closed
	 *
	 * @access protected
	 * @return bool TRUE if the post-close (closeified?) was successful, FALSE if not
	 */
	protected function closePostImport()
	{
		return true;
	}

	/**
	 * Build the spool data record
	 *
	 * Method will build the spool record for saving
	 *
	 * @access protected
	 * @param int $spoolId The spool ID
	 * @param string $type The node type (customer, product, order, etc)
	 * @param string $service The node service. This is module dependent
	 * @param mixed $node The node ID/record array
	 * @param int $parentSpoolId The optional parent node ID. Default is 0 (no parent)
	 * @return array The spool data array
	 */
	final protected function buildSpool2Set($spoolId, $type, $service, $node, $parentSpoolId=0)
	{

		$spool = parent::buildSpool2Set($spoolId, $type, $service, $node, $parentSpoolId);

		if (!is_array($spool)) {
			return false;
		}

		$spool["realService"] = $this->findRealService($type, $service, $node);
		$spool["storeService"] = $type . $service;

		return $spool;
	}

	/**
	 * Find the real service to execute
	 *
	 * Method will return the real service string to execute
	 *
	 * @acces public
	 * @param string $type The spool type
	 * @param string $service The spool service.
	 * @param array $data The spool data record (needed for the prerequisite services)
	 * @return string The real service to execute on success, FALSE if no service could be found
	 */
	public function findRealService($type, $service, $data)
	{
		if (trim($type) == "" || trim($service) == "") {
			return false;
		}

		/**
		 * Special case for our personal syncing services, this is so we can keep the real service
		 * name's the same as what we call it and not in a QB format
		 */
		if (isc_strtolower($service) == "sync") {
			return ucfirst(isc_strtolower($type)) . ucfirst(isc_strtolower($service));
		}

		$realService = "";

		switch (isc_strtolower($type)) {
			case "customer":
			case "customerguest":
				$realService = "Customer";
				break;

			case "product":
			case "productvariation":
				$realService = "ItemInventory";
				break;

			case "productlevel":
				$realService = "InventoryAdjustment";
				break;

			case "order":
				if (isc_strtolower($this->getValue("orderoption")) == "order") {
					$realService = "SalesOrder";
				} else {
					$realService = "SalesReceipt";
				}
				break;

			case "prerequisite":
				if (!is_array($data) || !array_key_exists("Service", $data) || trim($data["Service"]) == "") {
					return false;
				}

				$realService = $data["Service"];
				break;

			default:
				return false;
				break;
		}

		switch (isc_strtolower($service)) {
			case "add":
				$realService .= "Add";
				break;

			case "queryinactive":
			case "query":
				$realService .= "Query";
				break;

			case "edit":
				$realService .= "Mod";
				break;

			/**
			 * Special cases for the deleting stages
			 */
			case "querydel":
				if (isc_strtolower($type) == "order") {
					$realService = "TxnDeletedQuery";
				} else {
					$realService = "ListDeletedQuery";
				}

				break;

			case "del":
				if (isc_strtolower($type) == "order") {
					$realService = "TxnDel";
				} else {
					$realService = "ListDel";
				}

				break;

			default:
				return false;
				break;
		}

		return $realService;
	}

	/**
	 * Generate the QBWC file
	 *
	 * Method will generate and return the QBWC file string
	 *
	 * @access private
	 * @return string The QBWC file
	 */
	private function generateQBWC()
	{
		$GLOBALS["AppName"] = isc_html_escape(GetLang("QuickBooksApplicationName"));
		$GLOBALS["AppURL"] = isc_html_escape(GetConfig("ShopPathSSL") . "/accountinggateway.php?accounting=" . $this->getId());
		$GLOBALS["AppDescription"] = isc_html_escape(GetLang("QuickBooksApplicationDescription"));
		$GLOBALS["AppSupportURL"] = $this->supportURL;
		$GLOBALS["CertURL"] = isc_html_escape(GetConfig("ShopPathSSL"));
		$GLOBALS["Username"] = isc_html_escape($this->username);
		$GLOBALS["OwnerID"] = isc_html_escape("{" . self::ownerId . "}");
		$GLOBALS["FileID"] = isc_html_escape("{" . $this->fileId . "}");
		$GLOBALS["QBType"] = isc_html_escape(self::qbType);
		$GLOBALS["Scheduler"] = "";

		$scheduler = $this->GetValue("scheduler");

		if (strtolower($scheduler) !== "never") {
			$GLOBALS["Scheduler" ] = "<Scheduler>
			<RunEveryNMinutes>" . isc_html_escape($scheduler) . "</RunEveryNMinutes>
		</Scheduler>";
		}

		return $this->ParseTemplate("qbwc", true);
	}

	/**
	 * Output the QBWC to the browser
	 *
	 * Method will generate and output the QBWC file to the browser
	 *
	 * @access public
	 */
	public function downloadQBWC()
	{
		$xml = $this->generateQBWC();

		header("Content-Type: application/x-download");
		header("Content-Disposition: attachment; filename=shoppingcart.qwc");
		header("Content-Length: " . strlen($xml));
		header("Pragma: public");
		header("Cache-Control: max-age=0");
		print $xml;
		exit;
	}

	/**
	 * Handle the SOAP gateway requests
	 *
	 * Method will setup the SOAP server and handle the SOAP request
	 *
	 * @access public
	 * @return void
	 */
	public function handleGateway()
	{
		$setup = $this->findModuleClass("classes", "soapserver", false);

		if (!is_array($setup)) {
			$this->logError("Unable to load the SoapServer class", $setup);
			exit;
		}

		$wsdl = realpath(dirname(__FILE__) . "/templates/wsdl.tpl");

		if ($wsdl == '' || !file_exists($wsdl) || !is_readable($wsdl)) {
			$this->logError("Unable to load WSDL file", "FilePath: " . $wsdl);
			exit;
		}

		if (!@include_once($setup["file"])) {
			$this->logError("Unable to load the SoapServer file", "FilePath: " . $setup["file"]);
			exit;
		}

		$soapClass = $setup["class"];
		$soap = new $soapClass($wsdl, $this);
		$soap->handle();
		exit;
	}

	/**
	 * Generate the XML used in the request transaction
	 *
	 * Method will generate the XML that is used to send to QBWC
	 *
	 * @access public
	 * @param string $realService The case sensitive real service
	 * @param string $xml The XML of the entity
	 * @param bool $isQuery TRUE to say that this XML request is a query request, FALSE not to. Default is FALSE
	 * @param string $vesionNo The optional version number. Default is self::supportedXMLVersion
	 * @return string The generated XML on success, FALSE on error (error will be recorded)
	 */
	public function generateXML($realService, $xml, $isQuery=false, $versionNo=null)
	{
		if (trim($realService) == '' || $trim($xml) == '') {
			$xargs = func_get_args();
			$this->logError("Unable to create XML string", $xargs);
			return false;
		}

		if (is_null($version) || $version == '') {
			$version = (string)self::supportedXMLVersion;
		}

		$GLOBALS["VersionNo"] = $versionNo;
		$GLOBALS["ServiceString"] = $realService;
		$GLOBALS["XMLString"] = $xml;

		if ($isQuery) {
			return $this->ParseTemplate("qbxml.query", true);
		} else {
			return $this->ParseTemplate("qbxml", true);
		}
	}

	/**
	 * Run the spool service
	 *
	 * Method will run the spool service. If $response is NULL then it will run the execRequest() method on the service
	 * class. If $response is an array then it will run the execResponse() method on the service class
	 *
	 * @access public
	 * @param object $spool The spool object
	 * @param bool $isResponse TRUE to mark this service as a response, FALSE for a request. Default is FALSE
	 * @return mixed The output of the executed service, FALSE on error
	 */
	public function runService($spool, $isResponse=false)
	{
		if (!is_array($spool)) {
			$this->setImportSessionError(array("Invalid argument when trying to run the service", $spool));
			return false;
		}

		$this->logDebug("Run service for SpoolID: " . $spool["id"], $spool);

		/**
		 * Load up the service class
		 */
		$service = $spool["storeService"];

		if (trim($service) == '') {
			$this->setImportSessionError(array("Cannot generate real service", $spool));
			return false;
		}

		$setup = $this->findModuleClass("services", $service);

		if (!is_array($setup)) {
			$this->setImportSessionError("Unable to load service setup " . $service);
			return false;
		}

		$serviceFile = $setup["file"];
		$serviceClass = $setup["class"];

		if (!file_exists($serviceFile) || !is_file($serviceFile) || !is_readable($serviceFile)) {
			$this->setImportSessionError("Unable to load service file " . $service);
			return false;
		}

		@include_once($serviceFile);

		if (!class_exists($serviceClass)) {
			$this->setImportSessionError("Unable to find service class " . $serviceClass);
			return false;
		}

		/**
		 * Try to run the service
		 */
		$output = "";

		try {
			$service = new $serviceClass($spool, $this, $isResponse);

			if (!is_object($service)) {
				throw new QBException("Unable to instantiate service class " . $serviceClass);
			}

			if ($isResponse) {
				$xmlType = "Response";
			} else {
				$xmlType = "Request";
			}

			$this->logDebug("Runnning the " . $xmlType . " for SpoolID: " . $spool["id"], $spool);

			if (!$isResponse) {
				$output = $service->execRequest();
			} else {
				$output = $service->execResponse();
			}

		} catch (QBException $e) {
			$this->setImportSessionError($e->getQBMessage() . ' ' . $e->getLine());
			return false;

		/**
		 * Just in case if I threw a normal Exception
		 */
		} catch (Exception $e) {
			$this->setImportSessionError(array("Error when calling " . $serviceClass, $e->getMessage()));
			return false;
		}

		return $output;
	}

	/**
	 * Get the file path and the class name of a class file
	 *
	 * Method will return an array where "file" will be the file path and "name" will be the class name
	 *
	 * @access public
	 * @param string $type The class type ("classes", "handlers", "services" or "entities")
	 * @param string $class The class name (Not the full name)
	 * @param bool $includeParentBase TRUE to also find and include the base parent. Default is TRUE
	 * @return array An array with the class file path and full class name on success, NULL if no result, FALSE on error
	 */
	public function findModuleClass($type, $class, $includeParentBase=true)
	{
		if (trim($type) == '' || trim($class) == '') {
			return false;
		}

		$filePathPrefix = dirname(__FILE__) . "/includes";
		$classNamePrefix = "ACCOUNTING_QUICKBOOKS_";

		switch (isc_strtolower($type)) {
			case "classes":
				$filePathPrefix .= "/classes/class.";
				$classNamePrefix .= "CLASS_";
				break;

			case "handlers":
				$filePathPrefix .= "/handlers/handler.";
				$classNamePrefix .= "HANDLER_";
				break;

			case "services":
				$filePathPrefix .= "/services/service.";
				$classNamePrefix .= "SERVICE_";
				break;

			case "entities":
				$filePathPrefix .= "/entities/entity.";
				$classNamePrefix .= "ENTITY_";
				break;

			default:
				return null;
		}

		$filePath = realpath($filePathPrefix . isc_strtolower($class) . ".php");
		$className = $classNamePrefix . isc_strtoupper($class);

		if ($filePath == '' || !file_exists($filePath)) {
			$xargs = func_get_args();
			$this->logError("Cannot find module class file", $xargs);
			return null;
		}

		if ($includeParentBase) {
			$basePath = realpath(realpath($filePathPrefix . "base.php"));

			if ($basePath == '' || !file_exists($basePath)) {
				$xargs = func_get_args();
				$this->logError("Cannot find module base class file", $xargs);
				return null;
			} else {
				@include_once($basePath);
			}
		}

		return array("file" => $filePath, "class" => $className);
	}

	/**
	 * Get the asset type accountingrefexternalid
	 *
	 * Method will return the asset's accountingrefexternalid (the ListID)
	 *
	 * @access public
	 * @param string $type The asset type (The "AccountType" field)
	 * @return string The accountingrefexternalid on success, FALSE on error
	 */
	public function getAccountListId($type)
	{
		static $_cacheExternalIdx = array();

		if (trim($type) == '') {
			return false;
		}

		if (array_key_exists($type, $_cacheExternalIdx)) {
			return $_cacheExternalIdx[$type];
		}

		$searchData = array(
			"Service" => "Account",
			"AccountType" => $type
		);

		$reference = $this->getReference("prerequisite", $searchData, '', '', false);

		if (!is_array($reference)) {
			return false;
		}

		$_cacheExternalIdx[$type] = $reference["accountingrefexternalid"];

		return $reference["accountingrefexternalid"];
	}

	/**
	 * Get the other product accountingrefexternalid
	 *
	 * Method will return the other product's accountingrefexternalid (the ListID)
	 *
	 * @access public
	 * @param string $type The other product type (The "Type" field)
	 * @return string The accountingrefexternalid on success, FALSE on error
	 */
	public function getOtherProductListId($type)
	{
		static $_cacheExternalIdx = array();

		if (trim($type) == '') {
			return false;
		}

		if (array_key_exists($type, $_cacheExternalIdx)) {
			return $_cacheExternalIdx[$type];
		}

		$searchData = array(
			"Service" => "ItemOtherCharge",
			"Type" => $type
		);

		$reference = $this->getReference("prerequisite", $searchData, '', '', false);

		if (!is_array($reference)) {
			return false;
		}

		$_cacheExternalIdx[$type] = $reference["accountingrefexternalid"];

		return $reference["accountingrefexternalid"];
	}

	/**
	 * Get the customer parent type accountingrefexternalid
	 *
	 * Method will return the customer parent type accountingrefexternalid (the ListID)
	 *
	 * @access public
	 * @param bool $isGuestCheckout TRUE for the guest checkout parent type, FALSE for normal. Default is FALSE
	 * @param bool $returnFullRecord TRUE to return the full record, FALSE just for the externalId. Default is FALSE
	 * @return string The accountingrefexternalid on success, FALSE on error
	 */
	public function getCustomerParentTypeListId($isGuestCheckout=false, $returnFullRecord=false)
	{
		static $_cacheExternalIdx = array();

		if ($isGuestCheckout) {
			$key = "guest";
		} else {
			$key = "normal";
		}

		if (!array_key_exists($key, $_cacheExternalIdx)) {

			$searchData = array(
				"Service" => "Customer"
			);

			if ($isGuestCheckout) {
				$searchData["Type"] = "guestcheckout";
			} else {
				$searchData["Type"] = "normal";
			}

			$reference = $this->getReference("prerequisite", $searchData, '', '', false);

			if (!is_array($reference)) {
				return false;
			}

			$_cacheExternalIdx[$key] = $reference;
		}

		if (!array_key_exists($key, $_cacheExternalIdx)) {
			return false;
		} else if ($returnFullRecord) {
			return $_cacheExternalIdx[$key];
		} else {
			return $_cacheExternalIdx[$key]["accountingrefexternalid"];
		}
	}

	/**
	 * Get the product parent type accountingrefexternalid
	 *
	 * Method will return the product parent type accountingrefexternalid (the ListID)
	 *
	 * @access public
	 * @param bool $isProductVariation TRUE for the product variation parent type, FALSE for normal. Default is FALSE
	 * @param bool $returnFullRecord TRUE to return the full record, FALSE just for the externalId. Default is FALSE
	 * @return string The accountingrefexternalid on success, FALSE on error
	 */
	public function getProductParentTypeListId($isProductVariation=false, $returnFullRecord=false)
	{
		static $_cacheExternalIdx = array();

		if ($isProductVariation) {
			$key = "variation";
		} else {
			$key = "normal";
		}

		if (!array_key_exists($key, $_cacheExternalIdx)) {

			$searchData = array(
				"Service" => "ItemInventory"
			);

			if ($isProductVariation) {
				$searchData["Type"] = "productvariation";
			} else {
				$searchData["Type"] = "normal";
			}

			$reference = $this->getReference("prerequisite", $searchData, '', '', false);

			if (!is_array($reference)) {
				return false;
			}

			$_cacheExternalIdx[$key] = $reference;
		}

		if (!array_key_exists($key, $_cacheExternalIdx)) {
			return false;
		} else if ($returnFullRecord) {
			return $_cacheExternalIdx[$key];
		} else {
			return $_cacheExternalIdx[$key]["accountingrefexternalid"];
		}
	}

	/**
	 * Find the real name of an element from the response from QB
	 *
	 * Method will real name of an element from the response from QB by exploding on the self::parentSeparator separator
	 * and returning the last element. Also calls self::revertParentSeparator()
	 *
	 * @access public
	 * @param string $fullName The name of the element
	 * @return string The real name of the element
	 */
	public function fullName2RealName($fullName)
	{
		$realName = $fullName;

		if (strpos($fullName, self::parentSeparator) !== false) {
			$realName = explode(self::parentSeparator, $fullName);
			if (is_array($realName) && !empty($realName)) {
				$realName = $realName[count($realName)-1];
			} else {
				$realName = $fullName;
			}
		}

		$realName = $this->revertParentSeparator($realName);

		return $realName;
	}

	/**
	 * Replaces the parent separator in QB with self::parentSeparatorReplacement
	 *
	 * Method will replace (escape) the parent separator in QB with self::parentSeparatorReplacement
	 *
	 * @access public
	 * @param string $name The node name to escape
	 * @return string The escaped name
	 */
	public function escapeParentSeparator($name)
	{
		return str_replace(self::parentSeparator, self::parentSeparatorReplacement, $name);
	}

	/**
	 * Reverts the self::parentSeparatorReplacement with the parent separator in QB with
	 *
	 * Method will reverts (unescape) the self::parentSeparatorReplacement with the parent separator in QB with
	 *
	 * @access public
	 * @param string $name The node name to unescape
	 * @return string The unescaped name
	 */
	public function revertParentSeparator($name)
	{
		return str_replace(self::parentSeparatorReplacement, self::parentSeparator, $name);
	}

	/**
	 * Convert the order ID to the QB order reference number
	 *
	 * Method will convert the order ID to the QB order reference number
	 *
	 * @access public
	 * @param int $orderId The order ID
	 * @return string The QB order reference number on success, FALSE on error
	 */
	public function orderID2QBOrderRefNum($orderId)
	{
		if (!isId($orderId)) {
			return false;
		}

		return sprintf("%s%08d", self::orderIDPrefix, $orderId);
	}

	/**
	 * Convert the QB order reference number to the order ID
	 *
	 * Method will convert the QB order reference number to the order ID
	 *
	 * @access public
	 * @param string $orderRefNum The QB order reference number
	 * @return string The order ID on success, FALSE on error
	 */
	public function qbOrderRefNum2OrderId($orderRefNum)
	{
		if (trim($orderRefNum) == '' || substr($orderRefNum, 0, strlen(self::orderIDPrefix)) !== self::orderIDPrefix) {
			return false;
		}

		$orderId = substr($orderRefNum, strlen(self::orderIDPrefix));
		$orderId = (int)$orderId;

		if (!isId($orderId)) {
			return false;
		}

		return $orderId;
	}

	/**
	 * Convert the customer name and ID into the customer short name
	 *
	 * Method will convert the customer ID $customerId and customer name $customerName into the QB customer
	 * short name
	 *
	 * @access public
	 * @param int $customerId The customer ID
	 * @param string $customerName The customer name
	 * @return string The customer short name, FALSE on error
	 */
	public function customerNameId2QBCustomerShortName($customerId, $customerName)
	{
		if (!isId($customerId) || trim($customerName) == '') {
			return false;
		}

		$customerName = $this->escapeXMLNodeValue($customerName, self::customerShortNameNameLength);
		$customerName = sprintf(self::customerShortNameFormat, $customerName, $customerId);

		return $customerName;
	}

	/**
	 * Convert a QB customer short name to array of ID and name
	 *
	 * Method will conver the QB customer short name to an array containing the customer ID (first element)
	 * and the customer short name (second element)
	 *
	 * @access public
	 * @param string $customerName The customer short name
	 * @param bool $returnIdOnly TRUE to return the customer ID only. Default is FALSE
	 * @return mixed An array containing the customer ID and short name or just the customer ID on success, FALSE if not customer short name
	 */
	public function qbCustomerShortName2CustomerNameId($customerName, $returnIdOnly=false)
	{
		if (trim($customerName) == '') {
			return false;
		}

		if (!preg_match(self::customerShortNameRegEx, $customerName, $matches)) {
			return false;
		}

		if ($returnIdOnly) {
			return (int)$matches[2];
		} else {
			return array("customerid" => (int)$matches[2], "customername" => $matches[1]);
		}
	}

	/**
	 * Is the customer name $customerName a QB short customer name?
	 *
	 * Method will return TRUE if $customerName is a QB short customer name, FALSE if it is not
	 *
	 * @access public
	 * @param string $customerName The customer name to check for
	 * @return bool TRUE if $customerName is a QB short customer name, FALSE if not
	 */
	public function isCustomerShortName($customerName)
	{
		$shortNameParts = self::qbCustomerShortName2CustomerNameId($customerName);

		if (is_array($shortNameParts) && count($shortNameParts) == 2) {
			return true;
		}

		return false;
	}

	/**
	 * Convert the customer guest name and ID into the customer guest short name
	 *
	 * Method will convert the customer guest name $customerGuestName and order ID $orderID into the QB customer
	 * guest short name
	 *
	 * @access public
	 * @param int $orderId The order Id
	 * @param string $customerGuestName The customer guest name
	 * @return string The customer guest short name, FALSE on error
	 */
	public function customerGuestNameId2QBCustomerGuestShortName($orderId, $customerGuestName)
	{
		if (!isId($orderId) || trim($customerGuestName) == '') {
			return false;
		}

		$customerGuestName = $this->escapeXMLNodeValue($customerGuestName, self::customerGuestShortNameNameLength);
		$customerGuestName = sprintf(self::customerGuestShortNameFormat, $customerGuestName, $orderId);

		return $customerGuestName;
	}

	/**
	 * Convert a QB customer guest short name to array of order ID and customer name
	 *
	 * Method will conver the QB customer guest short name to an array containing the order ID (first element)
	 * and the customer guest short name (second element)
	 *
	 * @access public
	 * @param string $customerGuestName The customer guest short name
	 * @return mixed An array containing the order ID and customer guest short name on success, FALSE if not
	 *               a customer guest short name
	 */
	public function qbCustomerGuestShortName2CustomerGuestNameId($customerGuestName)
	{
		if (trim($customerGuestName) == '') {
			return false;
		}

		if (!preg_match(self::customerGuestShortNameRegEx, $customerGuestName, $matches)) {
			return false;
		}

		return array("orderid" => (int)$matches[2], "customerguestname" => $matches[1]);
	}

	/**
	 * Is the customer guest name $customerGuestName a QB short customer guest name?
	 *
	 * Method will return TRUE if $customerGuestName is a QB short customer guest name, FALSE if it is not
	 *
	 * @access public
	 * @param string $customerGuestName The customer guest name to check for
	 * @return bool TRUE if $customerGuestName is a QB short customer guest name, FALSE if not
	 */
	public function isCustomerGuestShortName($customerGuestName)
	{
		$shortNameParts = self::qbCustomerGuestShortName2CustomerGuestNameId($customerGuestName);

		if (is_array($shortNameParts) && count($shortNameParts) == 2) {
			return true;
		}

		return false;
	}

	/**
	 * Convert the product name and ID into the product short name
	 *
	 * Method will convert the product ID $productId and product name $productName into the QB product
	 * short name
	 *
	 * @access public
	 * @param int $productId The product ID
	 * @param string $productName The product name
	 * @return string The product short name, FALSE on error
	 */
	public function productNameId2QBProductShortName($productId, $productName)
	{
		if (!isId($productId) || trim($productName) == '') {
			return false;
		}
		$productName = $this->escapeXMLNodeValue($productName, self::productShortNameLength, self::productShortNameNameLength, $wasTruncated);
		/**
		 * If we were truncated then append the productId
		 */
		if ($wasTruncated) {
			$productName = sprintf(self::productShortNameFormat, $productName, $productId);
		}

		return $productName;
	}

	/**
	 * Convert a QB product short name to array of ID and name
	 *
	 * Method will conver the QB product short name to an array containing the product ID (first element)
	 * and the product short name (second element)
	 *
	 * @access public
	 * @param string $productName The product short name
	 * @param bool $returnIdOnly TRUE to return the variation combination ID only. Default is FALSE
	 * @return mixed An array containing the product ID and short name or just the product ID on success, FALSE if not product short name
	 */
	public function qbProductShortName2ProductNameId($productName, $returnIdOnly=false)
	{
		if (trim($productName) == '') {
			return false;
		}

		if (!preg_match(self::productShortNameRegEx, $productName, $matches)) {
			return false;
		}

		if ($returnIdOnly) {
			return (int)$matches[2];
		} else {
			return array("productid" => (int)$matches[2], "prodname" => $matches[1]);
		}
	}

	/**
	 * Is the product name $productName a QB short product name?
	 *
	 * Method will return TRUE if $productName is a QB short product name, FALSE if it is not
	 *
	 * @access public
	 * @param string $productName The product name to check for
	 * @return bool TRUE if $productName is a QB short product name, FALSE if not
	 */
	public function isProductShortName($productName)
	{
		$shortNameParts = self::qbProductShortName2ProductNameId($productName);

		if (is_array($shortNameParts) && count($shortNameParts) == 2) {
			return true;
		}

		return false;
	}

	/**
	 * Is product name $productName a candidate for a QB product short name?
	 *
	 * Method will check the product name $productName to see if it should be a QB product short name
	 *
	 * @access public
	 * @param string $productName The product name to check for
	 * @return bool TRUE if $productName should be converted to QB short name, FALSE if not needed
	 */
	public function isProductShortNameCandidate($productName)
	{
		if (strlen(trim($productName)) > self::productShortNameLength) {
			return true;
		}

		return false;
	}

	/**
	 * Convert the product variation details into the product short name
	 *
	 * Method will convert the product ID $productId and product name $productName into the QB product
	 * short name
	 *
	 * @access public
	 * @param int $variationId The variation ID
	 * @param string $productName The product name
	 * @return string The product short name, FALSE on error
	 */
	public function productVariationNameId2QBProductVariationShortName($variationId, $productName)
	{
		if (!isId($variationId) || trim($productName) == '') {
			return false;
		}

		$productName = $this->escapeXMLNodeValue($productName, self::productVariationShortNameNameLength);
		$productName = sprintf(self::productVariationShortNameFormat, $productName, $variationId);

		return $productName;
	}

	/**
	 * Convert a QB product variation short name to array of ID and name
	 *
	 * Method will conver the QB product variation short name to an array containing the variation ID (first element),
	 * the product ID (second element) and the product short name (third element)
	 *
	 * @access public
	 * @param string $productName The product variation short name
	 * @param bool $returnIdOnly TRUE to return the variation combination ID only. Default is FALSE
	 * @return mixed An array containing the variation ID and short name or just the variation ID on success, FALSE if not product short name
	 */
	public function qbProductVariationShortName2ProductVariationNameId($productName, $returnIdOnly=false)
	{
		if (trim($productName) == '') {
			return false;
		}

		if (!preg_match(self::productVariationShortNameRegEx, $productName, $matches)) {
			return false;
		}

		if ($returnIdOnly) {
			return (int)$matches[2];
		} else {
			return array("variationid" => (int)$matches[2], "prodname" => $matches[1]);
		}
	}

	/**
	 * Is the product name $productName a QB short product variation name?
	 *
	 * Method will return TRUE if $productName is a QB short product variation name, FALSE if it is not
	 *
	 * @access public
	 * @param string $productName The product variation name to check for
	 * @return bool TRUE if $productName is a QB short product variation name, FALSE if not
	 */
	public function isProductVariationShortName($productName)
	{
		$shortNameParts = self::qbProductVariationShortName2ProductVariationNameId($productName);

		if (is_array($shortNameParts) && count($shortNameParts) == 2) {
			return true;
		}

		return false;
	}

	/**
	 * Get the variation short name post fix
	 *
	 * Method will return the variation short name post fix string
	 *
	 * @access public
	 * @return string The variation short name post fix
	 */
	public function getProductVariationNamePostFix()
	{
		return self::productVariationShortNamePostFix;
	}

	/**
	 * Check the product query response to see if product is a product variation
	 *
	 * Method will check the product query response to see if this product is a product variation. This method is mainly used
	 * when creating products for an order and when syncing products
	 *
	 * @access public
	 * @param array $productResponse The product query response array
	 * @return bool TRUE if product is a product variation, FALSE if not
	 */
	public function isProductProductVariation($productResponse)
	{
		if (!is_array($productResponse) || !isset($productResponse["ParentRef"]["ListID"]) || trim($productResponse["ParentRef"]["ListID"]) == '') {
			return false;
		}

		if ($this->getProductParentTypeListId(true) == $productResponse["ParentRef"]["ListID"]) {
			return true;
		}

		return false;
	}

	/**
	 * Check to see if the product is one of the product parent records
	 *
	 * Method will check to see if the product is one of the product parent records
	 *
	 * @access public
	 * @param array $productResponse The product query response array
	 * @return bool TRUE if product is one of the product parent records, FALSE if not
	 */
	public function isProductParent($productResponse)
	{
		if (!is_array($productResponse) || !isset($productResponse["ListID"]) || trim($productResponse["ListID"]) == '') {
			return false;
		}

		if ($this->getProductParentTypeListId(true) == $productResponse["ListID"] || $this->getProductParentTypeListId(false) == $productResponse["ListID"]) {
			return true;
		}

		return false;
	}

	/**
	 * Check the customer query response to see if customer is a guest checkout
	 *
	 * Method will check the customer query response to see if this customer is a guest checkout. This method is mainly used
	 * when creating a customer for an order and when syncing customers
	 *
	 * @access public
	 * @param array $customerResponse The customer query response array
	 * @return bool TRUE if customer is a guest checkout, FALSE if not
	 */
	public function isCustomerGuestCheckout($customerResponse)
	{
		if (!is_array($customerResponse) || !isset($customerResponse["ParentRef"]["ListID"]) || trim($customerResponse["ParentRef"]["ListID"]) == '') {
			return false;
		}

		if ($this->getCustomerParentTypeListId(true) == $customerResponse["ParentRef"]["ListID"]) {
			return true;
		}

		return false;
	}

	/**
	 * Check to see if the customer is one of the customer parent records
	 *
	 * Method will check to see if the customer is one of the customer parent records
	 *
	 * @access public
	 * @param array $customerResponse The customer query response array
	 * @return bool TRUE if customer is one of the customer parent records, FALSE if not
	 */
	public function isCustomerParent($customerResponse)
	{
		if (!is_array($customerResponse) || !isset($customerResponse["ListID"]) || trim($customerResponse["ListID"]) == '') {
			return false;
		}

		if ($this->getCustomerParentTypeListId(true) == $customerResponse["ListID"] || $this->getCustomerParentTypeListId(false) == $customerResponse["ListID"]) {
			return true;
		}
        //HOW TO CREATE A PARENT CUSTOMER
		return false;
	}

	/**
	 * Escape an XML node value
	 *
	 * Method will escape (entities) a node value
	 *
	 * @access public
	 * @param string $value The value to escape
	 * @param int $maxLength The maximum length of the string
	 * @param int $bufferLength The buffer length to also leave behind if $maxLength is used
	 * @param bool &$wasTruncated The referenced variable will be set to TRUE if the value was truncated, FALSE if not
	 * @param bool &$wasChanged The referenced variable will be set to TRUE if the value was changed (encoded), FALSE if not
	 * @return string The encoded value
	 */
	public function escapeXMLNodeValue($value, $maxLength=0, $bufferLength=0, &$wasTruncated=false, &$wasChanged=false)
	{
		$totalLength = 0;

		if ($maxLength > 0) {
			if ($bufferLength > 0) {
				$totalLength = $bufferLength;
			} else {
				$totalLength = $maxLength;
			}
		}

		$value = $this->filterInvalidQBXMLChars($value, $wasChanged);
		$valLength = isc_strlen($value);

		/**
		 * If something was changed then we need to make sure that we don't truncate it in the middle of
		 * any of the HTML entities
		 */
		if ($wasChanged && $maxLength > 0 && $valLength > $maxLength) {
			preg_match_all("/&#[0-9]+;/", $value, $matches, PREG_OFFSET_CAPTURE);
			$wasTruncated = true;

			if (isset($matches[0]) && is_array($matches[0])) {
				foreach ($matches[0] as $match) {

					/**
					 * $match[1] is outside the total length
					 */
					if ($match[1] >= $totalLength) {
						continue;

					/**
					 * $match[1] is in between the total length
					 */
					} else if ($match[1] < $totalLength && (isc_strlen($match[0]) + $match[1]) > $totalLength) {
						$value = isc_substr($value, 0, $match[1]);
					}
				}
			}
		}

		if ($maxLength > 0 && $valLength > $maxLength) {
			$wasTruncated = true;
			$value = isc_substr($value, 0, $totalLength);
		}

		return $value;
	}

	/**
	 * Entitise any character that are above the 127 ASCII value
	 *
	 * Method will encode (html entitise) any characters that are above the 127 ASCII value as QBWC cannot interpret
	 * them properly, even when using CDATA. We cannot use isc_convert_charset("UTF-8", "HTML-ENTITIES", "") or anything
	 * simple like that because QBWC can understand &#8482; bot NOT &trade;, so do it all manually
	 *
	 * @access private
	 * @param string $str The string to search in
	 * @param bool &$wasConverted The referenced variable will be set to TRUE if $str was changed, FALSE if not
	 * @return string The filtered string on success, FALSE on error
	 */
	private function filterInvalidQBXMLChars($str, &$wasConverted)
	{
		$wasConverted = false;

		if (!is_string($str)) {
			return false;
		} else if (trim($str) == "") {
			return $str;
		}

		/**
		 * Decode eberything first
		 */
		$str = html_entity_decode($str, ENT_QUOTES, "UTF-8");

		$strlen = isc_strlen($str);
		$newStr = "";

		for ($i=0; $i<$strlen; $i++) {
			$char = isc_substr($str, $i, 1);
			$unicode = uniord($char);

			if ($unicode <= 127) {
				$newStr .= $char;
				continue;
			}

			$wasConverted = true;
			$newStr .= "&#" . $unicode . ";";
		}


		/**
		 * We also need to entities the &, <, >, ' and " characters. Don't use htmlspecialchars() as that will not
		 * replcae it with the numerical value
		 */
		$ordMap = array(
			"<" => 60,
			">" => 62,
			"'" => 39,
			'"' => 34,
			"&" => 38
		);

		foreach ($ordMap as $chr => $ord) {

			$amount = 0;

			/**
			 * Special case for the ampersand
			 */
			if ($chr == "&") {
				$newStr = preg_replace("/&([^#]{1})/", "&#" . $ord . ";\\1", $newStr, -1, $amount);
			} else {
				$newStr = str_replace($chr, "&#" . $ord . ";", $newStr, $amount);
			}

			if ($amount > 0) {
				$wasConverted = true;
			}
		}


		return $newStr;
	}

	/**
	 * Revert all the HTML entities in $str to normal UTF-8
	 *
	 * Method will revert all HTML entities in $str to normal UTF-8. Method should work interchangable
	 * with self::filterInvalidQBXMLChars()
	 *
	 * @access public
	 * @param string $str The string to revert
	 * @return string The reverted string on success, FALSE on error
	 */
	public function revertInvalidQBXMLChars($str)
	{
		if (!is_string($str)) {
			return false;
		} else if (trim($str) == "") {
			return $str;
		} else {
			return isc_convert_charset("HTML-ENTITIES", "UTF-8", $str);
		}
	}

	/**
	 * Recursively revert all the HTML entities to normal UTF-8 in the array of strings
	 *
	 * Method will recursively revert all the HTML entities to normal UTF-8 in the array of strings. Calls
	 * self::revertInvalidQBXMLChars() internally. Array keys are preserved
	 *
	 * @access public
	 * @param array $arr The array of strings to revert
	 * @return array The reverted array on success, FALSE on error
	 */
	public function revertArrayInvalidQBXMLChars($arr)
	{
		if (!is_array($arr)) {
			return false;
		}

		foreach (array_keys($arr) as $key) {
			if (is_array($arr[$key])) {
				$arr[$key] = self::revertArrayInvalidQBXMLChars($arr[$key]);
			} else {
				$arr[$key] = self::revertInvalidQBXMLChars($arr[$key]);
			}
		}

		return $arr;
	}
}