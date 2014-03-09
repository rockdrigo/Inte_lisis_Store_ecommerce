<?php
require_once("lib/iDEALConnector.php");

class CHECKOUT_IDEAL extends ISC_CHECKOUT_PROVIDER
{
	protected $_keyFile; // private key file
	protected $_certFile; // private certificate file
	protected $_configFile; // ideal config file

	protected $_currenciesSupported = array("EUR");

	protected $supportsVendorPurchases = true;
	protected $supportsMultiShipping = true;

	/*
		Checkout class constructor
	*/
	public function __construct()
	{
		// Setup the required variables for the iDeal Connect checkout module
		parent::__construct();
		$this->_name = GetLang('IdealName');
		$this->_image = "ideal_logo.jpg";
		$this->_description = GetLang('IdealDesc');
		$this->_help = sprintf(GetLang('IdealHelp'), $GLOBALS['ShopPathSSL'], $GLOBALS['ShopPathSSL'], $GLOBALS['ShopPathSSL']);

		$this->_keyFile = dirname(__FILE__) . "/lib/includes/security/isc_privatekey.pem";
		$this->_certFile = dirname(__FILE__) . "/lib/includes/security/isc_privatecert.crt";
		$this->_configFile = dirname(__FILE__) . "/lib/includes/security/config.conf";
	}

	/*
	 * Check if this checkout module can be enabled or not.
	 *
	 * @return boolean True if this module is supported on this install, false if not.
	 */
	public function IsSupported()
	{
		$currency = GetDefaultCurrency();

		// Check if the default currency is supported by the payment gateway
		if (!in_array($currency['currencycode'], $this->_currenciesSupported)) {
			$this->SetError(sprintf(GetLang('IdealCurrecyNotSupported'), implode(',',$this->_currenciesSupported)));
		}

		// check for openssl support
		if (!function_exists('openssl_pkey_new') || !function_exists('openssl_csr_new')) {
			$this->SetError(GetLang('IdealOpenSSLRequired'));
		}

		// check for writable files and folders
		include_once(ISC_BASE_PATH.'/lib/class.file.php');
		$f = new FileClass();

		// check config file is writable
		if (file_exists($this->_configFile) && !$f->CheckFileWritable($this->_configFile)) {
			$this->SetError(GetLang('IdealConfigFileNotWritable', array("configFile" => $this->_configFile)));
		}

		// check the security folder is writable
		$securityFolder = dirname(__FILE__) . "/lib/includes/security";
		if (!$f->CheckDirWritable($securityFolder)) {
			$this->SetError(GetLang('IdealSecurityFolderNotWritable', array("securityFolder" => $securityFolder)));
		}

		// check key file is writable if it exists
		if (file_exists($this->_keyFile) && !$f->CheckFileWritable($this->_keyFile)) {
			$this->SetError(GetLang('IdealKeyFileNotWritable', array("keyFile" => $this->_keyFile)));
		}

		// check certificate file is writable if it exists
		if (file_exists($this->_certFile) && !$f->CheckFileWritable($this->_certFile)) {
			$this->SetError(GetLang('IdealCertFileNotWritable', array("certFile" => $this->_certFile)));
		}

		if($this->HasErrors()) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	* Custom variables for the checkout module. Custom variables are stored in the following format:
	* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	* variable_type types are: text,number,password,radio,dropdown
	* variable_options is used when the variable type is radio or dropdown and is a name/value array.
	*/
	public function SetCustomVars()
	{
		$this->_variables['displayname'] = array("name" => GetLang('DisplayName'),
		   "type" => "textbox",
		   "help" => GetLang('DisplayNameHelp'),
		   "default" => $this->getname(),
		   "required" => true
		);

		$this->_variables['bank'] = array("name" => GetLang('IdealBank'),
			"type" => "dropdown",
			"help" => GetLang('IdealBankHelp'),
			"default" => "",
			"required" => true,
			"multiselect" => false
		);

		$banks = $this->GetBanks();
		$options = array();
		foreach ($banks as $id => $bank) {
			$options[$bank['name']] = $id;
		}

		$this->_variables['bank']['options'] = $options;

		$this->_variables['merchantid'] = array("name" => GetLang('IdealMerchantID'),
		   "type" => "textbox",
		   "help" => GetLang('IdealMerchantIDHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['testmode'] = array("name" => GetLang('TestMode'),
		   "type" => "dropdown",
		   "help" => GetLang("IdealTestModeHelp"),
		   "default" => "no",
		   "required" => true,
		   "options" => array(GetLang("IdealTestModeNo") => "NO",
						  GetLang("IdealTestModeYes") => "YES"
			),
			"multiselect" => false
		);
	}

	/**
	* Gets the list of banks available for use with iDeal
	*
	* @return array Array containing information about the available banks
	*/
	private function GetBanks()
	{
		$banks = array(
			"ING" => array(
				"name" => GetLang('IdealBankING'),
				"testurl" => "ssl://idealtest.secure-ing.com:443/ideal/iDeal",
				"liveurl" => "ssl://ideal.secure-ing.com:443/ideal/iDeal"
			),
			"RABO" => array(
				"name" => GetLang('IdealBankRabobank'),
				"testurl" => "ssl://idealtest.rabobank.nl:443/ideal/iDeal",
				"liveurl" => "ssl://ideal.rabobank.nl:443/ideal/iDeal"
			)
		);

		/*
		Don't yet have the url's for these banks yet..

			"ABNAMRO" => array(
				"name" => GetLang('IdealBankABNAMRO'),
			),
			"FORTRIS" => array(
				"name" => GetLang('IdealBankFortis'),
			),
			"SNS" => array(
				"name" => GetLang('IdealBankSNSBank'),
			)
		*/

		return $banks;
	}

	/**
	* Gets the test and live URLs to the iDeal system for a specified bank
	*
	* @param string The bank for which to retrieve the URLs
	* @return array Array containing 'test' and 'live' URLs
	*/
	private function GetIDealURLs($bankid)
	{
		$banks = $this->GetBanks();

		$bank = $banks[$bankid];

		return array("test" => $bank['testurl'], "live" => $bank['liveurl']);
	}


	/**
	* Override the save module function so we can generate the private key and certificate
	*
	* @param mixed $settings
	* @param mixed $deleteFirst
	* @return boolean
	*/
	public function SaveModuleSettings($settings=array(), $deleteFirst=true)
	{
		// save settings
		if (!parent::SaveModuleSettings($settings, $deleteFirst)) {
			return false;
		}

		// update configuration file
		$this->UpdateConfig();

		// create new private key and certificate
		if ($this->GenerateKeyAndCertificate()) {
			// cert was generated, notify user that they need to upload certificate in iDeal dashboard
			$this->SetError(GetLang('IdealCertGenerated', array('certFile' => GetConfig('ShopPath') . $this->_certFile)));
		}
		else {
			// Only possible to update issuers (requests) once certificate has been uploaded
			$this->UpdateIssuers();
		}

		return !$this->HasErrors();
	}

	/**
	* Updates the iDeal config.conf file
	*
	*/
	private function UpdateConfig()
	{
		$GLOBALS['PrivateKeyPass'] = GetConfig('EncryptionToken');
		$GLOBALS['MerchantID'] = $this->GetValue('merchantid');
		$GLOBALS['ReturnURL'] = GetConfig('ShopPathSSL') . "/finishorder.php";

		$urls = $this->GetIDealURLs($this->GetValue('bank'));

		if ($this->GetValue('testmode') == "YES") {
			$acquirerURL = $urls['test'];
		}
		else {
			$acquirerURL = $urls['live'];
		}
		$GLOBALS['AcquirerURL'] = $acquirerURL;

		$configContents = $this->ParseTemplate('config', true);

		if (($handle = fopen($this->_configFile, "wb")) === false) {
			// could not open config file for writing
			$this->SetError(GetLang('IdealCantOpenConfigFile', array('configFile' => $this->_configFile)));

			return false;
		}

		fwrite($handle, $configContents);
		fclose($handle);
	}

	/**
	* Generates the private key and certificate used by iDeal
	*
	* @return bool True on success, false on failure
	*/
	private function GenerateKeyAndCertificate()
	{
		if (file_exists($this->_keyFile) && file_exists($this->_certFile)) {
			return false;
		}

		// Create the keypair
		if (($key = openssl_pkey_new()) === false) {
			// could not create key
			$this->SetError(GetLang('IdealCantCreateKeyPair'));

			return false;
		}

		if (file_exists($this->_keyFile)) {
			if (!unlink($this->_keyFile)) {
				// could not delete old key file
				$this->SetError(GetLang('IdealCantDeleteKeyFile', array("keyFile" => $this->_keyFile)));

				return false;
			}
		}

		// export our key
		if (!openssl_pkey_export_to_file($key, $this->_keyFile, GetConfig('EncryptionToken'))) {
			// could not export key
			$this->SetError(GetLang('IdealCantExportKey'));

			return false;
		}

		chmod($this->_keyFile, ISC_WRITEABLE_FILE_PERM);

		$dn = array(
			"countryName" => GetCountryISO2ByName(GetConfig('CompanyCountry')),
			"stateOrProvinceName" => GetConfig('CompanyState'),
			"localityName" => GetConfig('CompanyCity'),
			"organizationName" => GetConfig('CompanyName'),
			"organizationalUnitName" => GetConfig('CompanyName'),
			"commonName" => GetConfig('CompanyName'),
			"emailAddress" => GetConfig('AdminEmail')
		);

		// create our certificate
		if (($csr = openssl_csr_new($dn, $key)) === false) {
			// could not create cert
			$this->SetError(GetLang('IdealCantCreateCert'));

			return false;
		}

		// self sign our certificate
		if (($sscert = openssl_csr_sign($csr, null, $key, 3650)) === false) {
			// could not sign cert
			$this->SetError(GetLang('IdealCantSignCert'));

			return false;
		}

		if (file_exists($this->_certFile)) {
			if (!unlink($this->_certFile)) {
				// could not delete old cert file
				$this->SetError(GetLang('IdealCantDeleteCertFile', array("certFile" => $this->_certFile)));

				return false;
			}
		}

		// export certificate to file
		if (!openssl_x509_export_to_file($sscert, $this->_certFile)) {
			// could not export cert
			$this->SetError(GetLang('IdealCantExportCert'));

			return false;
		}

		chmod($this->_certFile, ISC_WRITEABLE_FILE_PERM);

		return true;
	}

	/**
	* Retrieves the list of issuers and caches them in the datastore
	*
	* @return mixed Returns an array of issuers or false if it failed to get the list
	*/
	private function UpdateIssuers()
	{
		$GLOBALS['ISC_CLASS_DATA_STORE']->Delete('IdealIssuers');

		// can't update without a merchant id
		if (!$this->GetValue('merchantid')) {
			return false;
		}

		$iDEALConnector = new iDEALConnector();

		// retrieve list of issuers
		$result = $iDEALConnector->GetIssuerList();

		if ($result === false) {
			$result = $iDEALConnector->getError();
		}

		if($result->IsResponseError()) {
			// request failed
			$this->SetError(GetLang('IdealCantRetrieveIssuers',	array('code' => $result->getErrorCode(), "message" => $result->getErrorMessage())));

			return false;
		}
		$issuerArray = $result->getIssuerFullList();

		if (empty($issuerArray)) {
			// no banks registered in iDeal ... cancel
			$this->SetError(GetLang('IdealNoIssuersConfigured'));

			return false;
		}

		$issuers = array();
		foreach ($issuerArray as $issuer) {
			$issuers[$issuer->issuerID] = array("name" => $issuer->issuerName, "list" => $issuer->issuerListType);
		}

		// cache list of issuers
		$GLOBALS['ISC_CLASS_DATA_STORE']->Save('IdealIssuers', $issuers);

		return $issuers;
	}

	public function ShowPaymentForm()
	{
		$GLOBALS['HideIdealError'] = "none";

		// retrieve our list of issuers from cache
		$issuers = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('IdealIssuers');

		// no issuers or cache doesnt exist
		if (empty($issuers)) {
			// attempt to regenerate the issuers
			$issuers = $this->UpdateIssuers();
		}

		$issuerOptions = "";

		// still no issuers? we can't proceed with payment
		if (empty($issuers)) {
			$GLOBALS['HideIdealError'] = "";
			$GLOBALS['IdealErrorMessage'] = GetLang('IdealNoIssuersConfigured');
		}
		else {
			// sort issuers into banks and other banks (whatever that is..)
			$issuerList = array();
			$issuerListOther = array();
			foreach ($issuers as $issuerID => $issuer) {
				if($issuer['list'] == "Short") {
					$issuerList[$issuerID] = $issuer['name'];
				}
				else { // other banks
					$issuerListOther[$issuerID] = $issuer['name'];
				}
			}

			// build select options
			if (!empty($issuerList)) {
				$issuerOptions = '<optgroup label="' . isc_html_escape(GetLang("IdealSelectBank")) . '">';

				foreach ($issuerList as $issuerID => $issuerName) {
					$issuerOptions .= '<option value="' . isc_html_escape($issuerID) . '">' . isc_html_escape($issuerName) . '</option>';
				}

				$issuerOptions .= '</optgroup>';
			}

			if (!empty($issuerListOther)) {
				$issuerOptions .= '<optgroup label="' . isc_html_escape(GetLang("IdealOtherBanks")) . '">';

				foreach ($issuerListOther as $issuerID => $issuerName) {
					$issuerOptions .= '<option value="' . isc_html_escape($issuerID) . '">' . isc_html_escape($issuerName) . '</option>';
				}

				$issuerOptions .= '</optgroup>';
			}

			$GLOBALS['IdealIssuerOptions'] = $issuerOptions;
			$GLOBALS['IdealPaymentForm'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetPanelContent('IdealPaymentForm');
		}

		if ($this->HasErrors()) {
			$GLOBALS['HideIdealError'] = "";
			$errors = $this->GetErrors();
			$errorMessage = "";
			foreach ($errors as $error) {
				if ($errorMessage) {
					$errorMessage .= "<br />";
				}
				$errorMessage .= $error;
			}
			$GLOBALS['IdealErrorMessage'] = $errorMessage;
		}

		// Collect their details to send through to Authorize.NET
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("ideal");
		return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
	}

	public function ProcessPaymentForm()
	{
		// any error that we need to show from a previous transaction attempt?
		if (isset($_SESSION['IdealError'])) {
			$this->SetError($_SESSION['IdealError']);
			unset($_SESSION['IdealError']);

			return false;
		}

		// check that an issuer was selected
		if (!isset($_POST['Ideal_issuer']) || empty($_POST['Ideal_issuer'])) {
			$this->SetError(GetLang('IdealIssuerNotSelected'));

			return false;
		}

		$issuer = $_POST['Ideal_issuer'];
		$orderID = $this->GetCombinedOrderId();
		$amount = round($this->GetGatewayAmount() * 100);
		$description = GetLang("IdealTransactionDescription", array("shopName" => GetConfig('StoreName'), "orderID" => $orderID));
		// used to authenticate the redirect
		$entranceCode = md5(GetConfig('EncryptionToken').$_COOKIE['SHOP_ORDER_TOKEN']);

		// instantiate the ideal connector
		$iDEALConnector = new iDEALConnector();

		// create transaction request and set data
		$request = $iDEALConnector->RequestTransaction(
			$issuer,
			$orderID,
			$amount,
			$description,
			$entranceCode
		);

		if ($request === false) {
			$request = $iDEALConnector->getError();
		}

		// bad transaction
		if ($request->IsResponseError()) {
			$error = GetLang('IdealErrorTransactionRequest', array('code' => $request->getErrorCode(), "message" => $request->getErrorMessage()));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $error, $request->getErrorDetail());

			$this->SetError($error);

			return false;
		}

		// ID of this transaction...we need to store this somewhere before redirecting
		$transactionID = $request->getTransactionID();
		$_SESSION['IdealTransactionID'] = $transactionID;

		//Get IssuerURL
		$acquirerID = $request->getAcquirerID();
		$issuerURL = $request->getIssuerAuthenticationURL();
		$issuerURL = html_entity_decode($issuerURL);

		//Redirect the issuer authentication site
		ob_end_clean();
		header("Location: " . $issuerURL);
		exit;
	}


	public function VerifyOrderPayment()
	{
		// verify the transaction status
		if (!isset($_SESSION['IdealTransactionID'])) {
			return false;
		}

		// couldn't authenticate redirect, we shouldn't proceed
		if (!isset($_GET["ec"]) || $_GET["ec"] != md5(GetConfig('EncryptionToken').$_COOKIE['SHOP_ORDER_TOKEN'])) {
			return false;
		}

		$transactionID = $_SESSION['IdealTransactionID'];

		// instantiate the ideal connector
		$iDEALConnector = new iDEALConnector();

		// request the status of the transaction
		$request = $iDEALConnector->RequestTransactionStatus($transactionID);

		if ($request === false) {
			$request = $iDEALConnector->getError();
		}

		if ($request->IsResponseError()) {
			$error = GetLang('IdealErrorTransactionRequest', array('code' => $request->getErrorCode(), "message" => $request->getErrorMessage()));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $error, $request->getErrorDetail());

			$this->SetError($error);

			return false;
		}

		// get consumer details and status of transaction
		$consumerName = $request->getConsumerName();
		$consumerAccountNumber = $request->getConsumerAccountNumber();
		$consumerCity = $request->getConsumerCity();
		$status = $request->getStatus();

		$friendlyStatus = GetLang('IdealStatus' . $this->GetStatusName($status));

		$details = GetLang('IdealTransactionDetails', array(
			"status" 			=> $friendlyStatus,
			"transactionID"		=> $transactionID
		));

		if ($consumerName) {
			$details .= GetLang('IdealConsumerDetails', array(
				"consumerName"		=> $consumerName,
				"consumerCity"		=> $consumerCity,
				"consumerAccount"	=> $consumerAccountNumber
			));
		}

		if ($status == IDEAL_TX_STATUS_SUCCESS) {
			$this->SetPaymentStatus(PAYMENT_STATUS_PAID);

			$updatedOrder = array(
				'ordpayproviderid' => $transactionID
			);
			$this->UpdateOrders($updatedOrder);

			// log successfull transaction
			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(
				array('payment', $this->GetName()),
				GetLang('IdealTransactionSuccess', array("transactionID" => $transactionID)),
				$details
			);

			return true;
		}

		// log failed transaction
		$GLOBALS['ISC_CLASS_LOG']->LogSystemError(
			array('payment', $this->GetName()),
			GetLang('IdealTransactionFailure', array("transactionID" => $transactionID, "status" => $friendlyStatus)),
			$details
		);

		// redirect to cart if cancelled
		if ($status == IDEAL_TX_STATUS_CANCELLED) {
			ob_end_clean();
			header("Location: " . GetConfig('ShopPathSSL') . "/cart.php");
			exit;
		}

		// transaction has expired, we inform the customer and they can try again
		if ($status == IDEAL_TX_STATUS_EXPIRED) {
			$_SESSION['IdealError'] = GetLang('IdealTransactionExpired');
			ob_end_clean();
			header("Location: " . GetConfig('ShopPathSSL') . "/checkout.php?action=process_payment");
			exit;
		}

		return false;
	}

	/**
	* Gets the status name based on iDEAL transaction status
	*
	* @param mixed The iDEAL transaction status
	* @return string Status name
	*/
	private function GetStatusName($status)
	{
		$statusName = "";

		switch ($status) {
			case IDEAL_TX_STATUS_CANCELLED:
				$statusName = "Cancelled";
				break;
			case IDEAL_TX_STATUS_EXPIRED:
				$statusName = "Expired";
				break;
			case IDEAL_TX_STATUS_FAILURE:
				$statusName = "Failure";
				break;
			case IDEAL_TX_STATUS_INVALID:
				$statusName = "Invalid";
				break;
			case IDEAL_TX_STATUS_OPEN:
				$statusName = "Open";
				break;
			case IDEAL_TX_STATUS_SUCCESS:
				$statusName = "Success";
				break;
		}

		return $statusName;
	}
}