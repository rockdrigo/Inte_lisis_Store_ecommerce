<?php
	/**
	 * ISC_ADMIN_INSTALL
	 *
	 * This class handles the install wizard used for installing Interspire Shopping Cart.
	 *
	 * @version     $Id: class.install.php,v 1.00 2007-12-26 17:18:14 mitch Exp $
	 * @author      Mitchell Harper
	 * @copyright   Copyright (c) 2004-2008 Interspire Pty. Ltd.
	 * @package     Interspire Shopping Cart
	 *
	 */

	define("IS_OK", 5000);
	define("DOESNT_EXIST", 50001);
	define("NOT_WRITABLE", 50002);

	define('IS_TRIAL', false);

	class ISC_ADMIN_INSTALL
	{

		private $apiMode = false;

		public $FoldersToCheck = array(
			'addons',
			'admin/backups',
			'cache',
			'cache/datastore',
			'cache/ebaydata',
			'cache/feeds',
			'cache/import',
			'cache/logos',
			'cache/spool',
			'cache/templates/admin',
			'cache/templates/front',
			'cache/tplthumbs',
			'config',
			'config/config.php',
			'language/en/front_language.ini',
			'product_downloads',
			'product_downloads/a',
			'product_downloads/b',
			'product_downloads/c',
			'product_downloads/d',
			'product_downloads/e',
			'product_downloads/f',
			'product_downloads/g',
			'product_downloads/h',
			'product_downloads/i',
			'product_downloads/import',
			'product_downloads/j',
			'product_downloads/k',
			'product_downloads/l',
			'product_downloads/m',
			'product_downloads/n',
			'product_downloads/o',
			'product_downloads/p',
			'product_downloads/q',
			'product_downloads/r',
			'product_downloads/s',
			'product_downloads/t',
			'product_downloads/u',
			'product_downloads/v',
			'product_downloads/w',
			'product_downloads/x',
			'product_downloads/y',
			'product_downloads/z',
			'product_images',
			'product_images/a',
			'product_images/b',
			'product_images/c',
			'product_images/configured_products',
			'product_images/configured_products_tmp',
			'product_images/d',
			'product_images/e',
			'product_images/f',
			'product_images/g',
			'product_images/h',
			'product_images/header_images',
			'product_images/i',
			'product_images/j',
			'product_images/k',
			'product_images/l',
			'product_images/m',
			'product_images/n',
			'product_images/o',
			'product_images/p',
			'product_images/q',
			'product_images/r',
			'product_images/s',
			'product_images/t',
			'product_images/u',
			'product_images/uploaded_images',
			'product_images/v',
			'product_images/w',
			'product_images/wrap_images',
			'product_images/x',
			'product_images/y',
			'product_images/z',
			'templates',
		);

		public $ErrorMessage;

		private $template = null;

		/**
		 * Constructor function that determines whether or not the installer should be run.
		 */
		public function __construct()
		{
			$this->template = Interspire_Template::getInstance('admin');

			// is it already setup? if so, don't run the installer!
			if(!GetConfig("isSetup")) {
				if(isset($_GET['ToDo'])) {
					$todo = $_GET['ToDo'];
				} else {
					$todo = "";
				}

				// Load the installation language file
				$file = ISC_BASE_PATH.'/language/'.GetConfig('Language').'/admin/install.ini';
				ParseLangFile($file);

				if(defined('IS_TRIAL') && IS_TRIAL == true) {
					$GLOBALS['HideLicenseKey'] = 'display: none';
					$GLOBALS['HideTrialFields'] = '';
				}
				else {
					$GLOBALS['HideLicenseKey'] = '';
					$GLOBALS['HideTrialFields'] = 'display: none';
				}

				// Make the folders appear in alphabetical order to make it easier to go through and fix up permissions
				natsort($this->FoldersToCheck);

				// Then order them by their depth
				usort($this->FoldersToCheck, array($this, 'dir_depth_compare'));

				// Installation is being performed via the command line
				if(defined('CLI_INSTALL')) {
					$this->apiMode = 'cli';

					// Setup the CLI install request, move the incoming variables in to the _POST array
					$this->SetupCliInstall();

					// Check requirements
					$this->CheckInstallationPrerequisites();

					// Run the installation
					$this->RunInstall();
					exit;
				}

				if((isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == "application/xml") || (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == "application/xml")) {
					$this->apiMode = 'xml';

					// Set up the API request, move the incoming variables in to the _POST array
					$this->SetupApiInstall();

					// Check the prerequisites
					$this->CheckInstallationPrerequisites();

					// All that can be accessed in the install XML API is the RunInstall method, so run it now
					$this->RunInstall();
					exit;
				}

				switch(strtolower($todo)) {
					case "runinstallation": {
						$this->RunInstall();
						break;
					}
					default: {
						$this->GetVariables();
					}
				}

				// We don't want anything running after the installer!
				die();
			}
		}

		private function ShowInstallErrors($message, $errors, $showRetry = false, $onRunInstall = false)
		{
			if($this->apiMode == 'cli') {
				fwrite(STDOUT, "Error:\n");
				fwrite(STDOUT, $message."\n");
				foreach($errors as $error) {
					$error['message'] = strip_tags($error['message']);
					fwrite(STDOUT, " - ".$error['message']." (".$error['code'].")");
				}
				// Exit with an errornous status code
				exit(1);
			}
			else if($this->apiMode == 'xml') {
				header("Content-Type: text/xml");
				echo '<'.'?xml version="1.0" encoding="'.GetConfig("CharacterSet").'" ?'.">\n";
				echo "<response>\n";
				echo "  <status>ERROR</status>\n";
				echo "  <message>".isc_html_escape($message)."</message>\n";
				echo "  <errors>\n";
				foreach($errors as $error) {
					$error['message'] = strip_tags($error['message']);
					if(isset($error['extra']) && $error['extra'] != '') {
						$extra = " extra=\"".$error['extra']."\"";
					}
					else {
						$extra = '';
					}
					echo "      <error code=\"".$error['code']."\"".$extra.">".isc_html_escape($error['message'])."</error>\n";
				}
				echo "  </errors>\n";
				echo "</response>";
				exit;
			}
			else {
				// Currently in the middle of the install, need to redirect back to the main screen
				if($onRunInstall == true) {
					$this->GetVariables(true, $message);
				}
				// Only on the main screen, just set globals
				else {
					$GLOBALS['PermissionErrors'] = "<h3 style='padding-bottom:10px'>" . GetLang("InstallInterspireShoppingCart") . "</h3>";
					$GLOBALS['PermissionErrors'] .= $message;
					$GLOBALS['PermissionErrors'] .= "<br /><br /><ul>";
					foreach($errors as $error) {
						$GLOBALS['PermissionErrors'] .= "<li>".$error['message']."</li>";
					}
					$GLOBALS['PermissionErrors'] .= "</ul>";
					if($showRetry == true) {
						$GLOBALS['PermissionErrors'] .= "<br /><input type='button' value='Try Again' style='margin-bottom:20px; font-size:11px' onclick=\"document.location.href='./'\" />";
					}
					$GLOBALS['CriticalErrors'] = 1;
				}
			}

		}

		/**
		 * Set up a command line installation request. This method will move all
		 * of the environment variables configured in to the $_POST array that
		 * is used for the install.
		 */
		private function SetupCliInstall()
		{
			$environmentVariables = array(
				'PHP_LICENSE_KEY' => 'LK',
				'PHP_SHOP_PATH' => 'ShopPath',
				'PHP_COUNTRY' => 'StoreCountryLocationId',
				'PHP_CURRENCY_CODE' => 'StoreCurrencyCode',
				'PHP_USER_EMAIL' => 'UserEmail',
				'PHP_USER_PASS' => 'UserPass',
				'PHP_DB_USER' => 'dbUser',
				'PHP_DB_PASS' => 'dbPass',
				'PHP_DB_NAME' => 'dbDatabase',
				'PHP_DB_SERVER' => 'dbServer',
				'PHP_TBL_PREFIX' => 'tablePrefix',
				'PHP_SAMPLE_DATA' => 'installSampleData',
				'PHP_SEND_STATUS' => 'sendServerDetails',
				'PHP_STORE_NAME' => 'StoreName',
			);
			foreach($environmentVariables as $env => $post) {
				if(isset($_ENV[$env])) {
					$_POST[$post] = $_ENV[$env];
				}
			}

			if(isset($_POST['ShopPath'])) {
				$url = parse_url($_POST['ShopPath']);
				$_SERVER['HTTP_HOST'] = $url['host'];
				if(!empty($url['port']) && $url['port'] != 80) {
					$_SERVER['HTTP_HOST'] .= ':'.$url['port'];
				}
			}
		}

		private function SetupApiInstall()
		{
			$request = file_get_contents("php://input");
			if(!$request) {
				exit;
			}

			$request = @simplexml_load_string($request);
			if(!is_object($request)) {
				$errors = array(
					0 => array(
						'code' => 'invalidRequest',
						'message' => ''
					)
				);
				$this->ShowInstallErrors('The request contained invalid XML.', $errors, false, false);
			}

			if(isset($request->install->licenseKey)) {
				$_POST['LK'] = strval($request->install->licenseKey);
			}

			if(isset($request->install->shopPath)) {
				$_POST['ShopPath'] = strval($request->install->shopPath);
			}

			if(isset($request->install->storeCountryLocationId)) {
				$_POST['StoreCountryLocationId'] = strval($request->install->storeCountryLocationId);
			}
			else {
				$_POST['StoreCountryLocationId'] = 226; // United States
			}

			if(isset($request->install->storeCurrencyCode)) {
				$_POST['StoreCurrencyCode'] = strval($request->install->storeCurrencyCode);
			}
			else {
				$_POST['StoreCurrencyCode'] = 'USD';
			}

			if(isset($request->install->user->email)) {
				$_POST['UserEmail'] = strval($request->install->user->email);
			}

			if(isset($request->install->user->password)) {
				$_POST['UserPass'] = strval($request->install->user->password);
			}

			if(isset($request->install->database->dbUser)) {
				$_POST['dbUser'] = strval($request->install->database->dbUser);
			}

			if(isset($request->install->database->dbPass)) {
				$_POST['dbPass'] = strval($request->install->database->dbPass);
			}

			if(isset($request->install->database->dbDatabase)) {
				$_POST['dbDatabase'] = strval($request->install->database->dbDatabase);
			}

			if(isset($request->install->database->dbServer)) {
				$_POST['dbServer'] = strval($request->install->database->dbServer);
			}

			if(isset($request->install->database->tablePrefix)) {
				$_POST['tablePrefix'] = strval($request->install->database->tablePrefix);
			}
			else {
				$_POST['tablePrefix'] = '';
			}

			if(isset($request->install->storeName)) {
				$_POST['StoreName'] = strval($request->install->storeName);
			}

			if(isset($request->install->sampleData)) {
				$sampleData = strval($request->install->sampleData);
				if($sampleData == 1 || $sampleData == "true") {
					$_POST['installSampleData'] = 1;
				}
			}

			if(isset($request->install->sendStats)) {
				$sendStats = strval($request->install->sendStats);
				if($sendStats == 1 || $sendStats == "true") {
					$_POST['sendServerDetails'] = 1;
				}
			}
		}

		/**
		 * Retrieve a license key for a trial installation of Interspire Shopping Cart.
		 *
		 * @return string The license key.
		 */
		private function GetTrialLicenseKey()
		{
			// Already tried to install, no need to fetch the license key again
			if(isset($_SESSION['LK'.md5(strtolower($_POST['ShopPath']))])) {
				return $_SESSION['LK'.md5(strtolower($_POST['ShopPath']))];
			}

			// First we need to fetch the license key from Interspire
			$licenseRequest = array(
				'licenserequest' => array(
					'product' => 'isc',
					'customer' => array(
						'name' => $_POST['FullName'],
						'email' => $_POST['UserEmail'],
						'url' => $_POST['ShopPath'],
						'phone' => $_POST['PhoneNumber'],
						'country' => GetCSVCountryNameById($_POST['StoreCountryLocationId'])
					),
					'aps' => 'true'
				)
			);

			if(file_exists(ISC_BASE_PATH.'/lib/trial_source.txt')) {
				$licenseRequest['licenserequest']['leadsource'] = file_get_contents(ISC_BASE_PATH.'/lib/trial_source.txt');
			}

			// Send the XML request off to the remote server
			$licenseUrl = 'http://go2market.mx';

			// Send the XML request off to the remote server
			$response = PostToRemoteFileAndGetResponse($licenseUrl, $this->BuildXMLFromArray($licenseRequest));
			$xml = @simplexml_load_string($response);
			if($response === false || !is_object($xml)) {
				$this->ShowInstallErrors('There was a problem communicating with the Interspire licensing server. Please try again in a few moments.', $errors, false, false);
				exit;
			}

			// Got a valid license key
			if($xml->status == "OK") {
				$_SESSION['LK'.md5(strtolower($_POST['ShopPath']))] = (string)$xml->licensekey;
				return (string)$xml->licensekey;
			}
			else {
				$this->ShowInstallErrors('There was a problem retrieving your license key. Please try again in a few moments. (Error: '.$xml->error.')', $errors, false, false);
				exit;
			}
		}

		/**
		 * _CheckPermissions
		 * Create the database and perform other install-orientated tasks
		 *
		 * @param none
		 *
		 * @return void
		 */
		private function RunInstall()
		{
			// Check for the required fields
			if(defined('IS_TRIAL') && IS_TRIAL == true) {
				$_POST['LK'] = $this->GetTrialLicenseKey();
			}
			else {
				$lk = '';
				if(isset($_POST['LK'])) {
					$lk = ech0($_POST['LK']);
				}

				if(!$lk) {
					$installMessage = GetLang('LKBad');
					$installCode = "badLicenseKey";
				}
			}

			if(!isset($_POST['StoreCountryLocationId']) || !isId($_POST['StoreCountryLocationId'])) {
				$_POST['StoreCountryLocationId'] = 227; // United States
			}

			if(!isset($_POST['StoreCurrencyCode']) || $_POST['StoreCurrencyCode'] == '') {
				$_POST['StoreCurrencyCode'] = 'USD';
			}

			if(!isset($_POST['ShopPath']) || $_POST['ShopPath'] == '') {
				$installMessage = GetLang('InstallMissingShopPath');
				$installCode = "missingShopPath";
			}
			else if (isc_strlen($_POST['StoreCurrencyCode']) > 3) {
				$installMessage = GetLang('InstallInvalidStoreCurrencyCode');
				$installCode = "invalidStoreCurrencyCode";
			}
			else if(!isset($_POST['ShopPath']) || $_POST['ShopPath'] == '') {
				$installMessage = GetLang('InstallMissingShopPath');
				$installCode = "missingShopPath";
			}
			else if(!isset($_POST['UserEmail']) || $_POST['UserEmail'] == '') {
				$installMessage = GetLang('InstallMissingUserEmail');
				$installCode = "missingUserEmail";
			}
			else if(!isset($_POST['UserPass']) || $_POST['UserPass'] == '') {
				$installMessage = GetLang('InstallMissingUserPass');
				$installCode = "missingUserPass";
			}
			else if(!isset($_POST['dbServer']) || $_POST['dbServer'] == '') {
				$installMessage = GetLang('InstallMissingDbServer');
				$installCode = "missingDbServer";
			}
			else if(!isset($_POST['dbUser']) || $_POST['dbUser'] == '') {
				$installMessage = GetLang('InstallMissingDbUser');
				$installCode = "missingDbUser";
			}
			else if(!isset($_POST['dbPass'])) {
				$installMessage = GetLang('InstallMissingDbPass');
				$installCode = "missingDbPass";
			}
			else if(!isset($_POST['dbDatabase']) || $_POST['dbDatabase'] == '') {
				$installMessage = GetLang('InstallMissingDbDatabase');
				$installCode = "missingDbDatabase";
			}

			if(!isset($_POST['tablePrefix'])) {
				$_POST['tablePrefix'] = '';
			}

			// One or more error messages were detected
			if(isset($installMessage)) {
				$errors = array(
					0 => array(
						"code" => $installCode,
						"message" => $installMessage
					)
				);
				$this->ShowInstallErrors($installMessage, $errors, false, true);
				return;
			}

			// Try to connect to the database
			$db_type = GetConfig("dbType") . 'Db';
			$db = new $db_type();

			if(isset($GLOBALS['ISC_CFG']["dbEncoding"])) {
				$db->charset = $GLOBALS['ISC_CFG']["dbEncoding"];
			}

			$connection = $db->Connect($_POST['dbServer'], $_POST['dbUser'], $_POST['dbPass'], $_POST['dbDatabase']);
			$db->TablePrefix = $_POST['tablePrefix'];

			if($connection) {
				$GLOBALS["ISC_CLASS_DB"] = &$db;

				// Are we running the required version of MySQL?
				$ver = $GLOBALS["ISC_CLASS_DB"]->FetchOne("select version() as ver");

				$mysql_check = version_compare($ver, MYSQL_VERSION_REQUIRED);

				if($mysql_check < 0) {
					$message = sprintf(GetLang("MySQLV4Message"), MYSQL_VERSION_REQUIRED, $ver);
					$errors = array(
						0 => array(
							"code" => "mysqlVersion",
							"extra" => $ver,
							"message" => $message
						)
					);
					$this->ShowInstallErrors($message, $errors, false, true);
					return;
				}
				else {
					// Run the database commands
					$queries = $this->template->render('install.schema.tpl');
					$queries = str_replace("\r", "\n", str_replace("\r\n", "\n", $queries));
					$queries = explode(";\n", $queries);
					$GLOBALS["ISC_CLASS_DB"]->Query("start transaction");

					// Initialize the admin auth class to get the list of permissions
					$auth = new ISC_ADMIN_AUTH();

					require_once(dirname(__FILE__) . "/class.user.php");
					$userManager = GetClass('ISC_ADMIN_USER');
					$pass = $_POST['UserPass'];
					$token = $userManager->_GenerateUserToken();

					foreach($queries as $query) {
						$query = str_replace("%%PREFIX%%", $_POST['tablePrefix'], $query);
						$query = str_replace("%%EMAIL%%", $GLOBALS["ISC_CLASS_DB"]->Quote($_POST['UserEmail']), $query);
						$query = str_replace("%%TOKEN%%", $GLOBALS["ISC_CLASS_DB"]->Quote($token), $query);

						if(trim($query) != "") {
							$GLOBALS["ISC_CLASS_DB"]->Query($query);
						}
					}

					// update admin user password
					$user_id = $userManager->getUserByField('username', 'admin');
					$userManager->updatePassword($user_id, $pass);

					// Give the admin user permissions
					$constants = get_defined_constants();

					foreach($constants as $constant => $val) {
						if(is_numeric(strpos($constant, "AUTH_")) && strpos($constant, "AUTH_") == 0) {
							$newPermission = array(
								"permuserid" => $user_id,
								"permpermissionid" => $val
							);
							$GLOBALS['ISC_CLASS_DB']->InsertQuery("permissions", $newPermission);
						}
					}

					// Set the version
					$db_version = array(
						'database_version' => PRODUCT_VERSION_CODE
					);
					$GLOBALS['ISC_CLASS_DB']->InsertQuery('config', $db_version);

					// Install our default currency. We need to do it here as it also needs to be in the config file
					$GLOBALS['ISC_CLASS_DB']->Query("DELETE FROM [|PREFIX|]currencies");
					$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE [|PREFIX|]currencies AUTO_INCREMENT=1");
					$currency = array(
						'currencycountryid'			=> $_POST['StoreCountryLocationId'],
						'currencycode'				=> isc_strtoupper($_POST['StoreCurrencyCode']),
						'currencyname'				=> GetLang('InstallDefaultCurrencyName'),
						'currencyexchangerate'		=> GetConfig('DefaultCurrencyRate'),
						'currencystring'			=> html_entity_decode(GetLang('InstallDefaultCurrencyString')),
						'currencystringposition'	=> isc_strtolower(GetLang('InstallDefaultCurrencyStringPosition')),
						'currencydecimalstring'		=> GetLang('InstallDefaultCurrencyDecimalString'),
						'currencythousandstring'	=> GetLang('InstallDefaultCurrencyThousandString'),
						'currencydecimalplace'		=> GetLang('InstallDefaultCurrencyDecimalPlace'),
						'currencylastupdated'		=> time(),
						'currencyisdefault'			=> 1,
						'currencystatus'			=> 1
					);
					$defaultCurrencyId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('currencies', $currency);

					// Insert the default/master shipping zone
					$GLOBALS['ISC_CLASS_DB']->Query("DELETE FROM [|PREFIX|]shipping_zones");
					$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE [|PREFIX|]shipping_zones AUTO_INCREMENT=1");
					$masterZone = array(
						'zonename' => 'Default Zone',
						'zonetype' => 'country',
						'zonefreeshipping' => 0,
						'zonefreeshippingtotal' => 0,
						'zonehandlingtype' => 'none',
						'zonehandlingfee' => 0,
						'zonehandlingseparate' => 1,
						'zoneenabled' => 1,
						'zonedefault' => 1
					);
					$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zones', $masterZone);

					// Is there a custom SQL file to include?
					$customPath = ISC_BASE_PATH.'/custom';
					if(file_exists($customPath.'/install.schema.tpl')) {
						$template = Interspire_Template::getInstance('custominstall', $customPath, array(
							'cache' => getAdminTwigTemplateCacheDirectory(),
							'auto_reload' => true
						));
						$queries = $template->render('install.schema.tpl');
						$queries = str_replace("\r", "\n", str_replace("\r\n", "\n", $queries));
						$queries = explode(";\n", $queries);
						$GLOBALS['ISC_CLASS_DB']->StartTransaction();
						foreach($queries as $query) {
							$query = str_replace("%%PREFIX%%", $_POST['tablePrefix'], $query);
							if(trim($query)) {
								$GLOBALS['ISC_CLASS_DB']->Query($query);
							}
						}
						$GLOBALS['ISC_CLASS_DB']->CommitTransaction();
					}

					// Was there an error?
					if($GLOBALS["ISC_CLASS_DB"]->Error() == "") {
						$GLOBALS["ISC_CLASS_DB"]->Query("commit");

						// Save the config file
						foreach($_POST as $k => $v) {
							$GLOBALS['ISC_NEW_CFG'][$k] = $v;
						}

						// Set the email address for this user as the store admin/order email address
						$GLOBALS['ISC_NEW_CFG']['AdminEmail'] = $_POST['UserEmail'];
						$GLOBALS['ISC_NEW_CFG']['OrderEmail'] = $_POST['UserEmail'];

						$GLOBALS['ISC_NEW_CFG']['serverStamp'] = $_POST['LK'];
						$GLOBALS['ISC_CFG']['serverStamp'] = $_POST['LK'];

						$settings = GetClass('ISC_ADMIN_SETTINGS');

						$GLOBALS['ISC_NEW_CFG']['HostingProvider'] = "";

						// Can we send server details back to Interspire?
						// If we can, the HostingProvider global will also be set
						if(isset($_POST['sendServerDetails'])) {
							$this->SendServerDetails();
							if(isset($GLOBALS['InfoImage'])) {
								$GLOBALS['HiddenImage'] = $GLOBALS['InfoImage'];
							}
						}

						$GLOBALS['ISC_NEW_CFG']['ShopPath'] = $_POST['ShopPath'];
						$GLOBALS['ISC_NEW_CFG']['DefaultCurrencyID'] = $defaultCurrencyId;

						if (isset($GLOBALS['ISC_NEW_CFG']['StoreCountryLocationId'])) {
							unset($GLOBALS['ISC_NEW_CFG']['StoreCountryLocationId']);
						}
						if (isset($GLOBALS['ISC_NEW_CFG']['StoreCurrencyCode'])) {
							unset($GLOBALS['ISC_NEW_CFG']['StoreCurrencyCode']);
						}

						// set up the product images sizes
						// load the product image class to get the constants
						GetClass('ISC_PRODUCT_IMAGE');
						$GLOBALS['ISC_NEW_CFG']['ProductImagesStorewideThumbnail_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesStorewideThumbnail_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesProductPageImage_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesProductPageImage_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesGalleryThumbnail_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesGalleryThumbnail_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesZoomImage_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_ZOOM;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesZoomImage_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_ZOOM;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesTinyThumbnailsEnabled'] = 1;
						$GLOBALS['ISC_NEW_CFG']['ProductImagesImageZoomEnabled'] = 1;

						// Build the unique encryption token
						$GLOBALS['ISC_NEW_CFG']['EncryptionToken'] = $this->_BuildEncryptionToken();

						// Set the install date
						$GLOBALS['ISC_NEW_CFG']['InstallDate'] = time();

						if ($settings->CommitSettings()) {
							// Calling commit settings a second time to ensure the config.backup.php file
							// Is written with valid data
							$settings->CommitSettings();

							// The installation is complete
							$GLOBALS['Password'] = $pass;

							// Do we need to install the sample product data? Copy that across
							if(isset($_POST['installSampleData']) && $_POST['installSampleData'] == 1) {
								$this->InstallSampleData();
							}

							// The install schemas can't predict the nested set values if custom install scripts arbitrarily add categories or pages
							// Rebuilt any nested sets instead of including their values in the install schema
							$nestedSet = new ISC_NESTEDSET_CATEGORIES();
							$nestedSet->rebuildTree();

							$nestedSet = new ISC_NESTEDSET_PAGES();
							$nestedSet->rebuildTree();

							// Remove any existing cookies
							ISC_UnsetCookie("STORESUITE_CP_TOKEN");

							//Initialize the data store system
							require_once ISC_BASE_PATH."/lib/class.datastore.php";
							$GLOBALS['ISC_CLASS_DATA_STORE'] = new ISC_DATA_STORE();

							// Clear the data store just in case it contains something
							$GLOBALS['ISC_CLASS_DATA_STORE']->Clear();

							$GLOBALS['ISC_LANG']['InstallationCompleted'] = sprintf(GetLang('InstallationCompleted'), $pass);

							unset($_SESSION['LK'.md5(strtolower($_POST['ShopPath']))]);

							// The installation was complete!
							if($this->apiMode == 'cli') {
								fwrite(STDOUT, "Success:\n");
								fwrite(STDOUT, "\n");
								fwrite(STDOUT, "ShopPath: ".$_POST['ShopPath']."\n");
								fwrite(STDOUT, "ControlPanel: ".$_POST['ShopPath']."admin/index.php\n");
								fwrite(STDOUT, "Username: admin\n");
								fwrite(STDOUT, "Password: ".$_POST['UserPass']);

								// Exit with a successful status code
								exit(0);
							}
							else if($this->apiMode == 'xml') {
								echo '<'.'?xml version="1.0" encoding="'.GetConfig("CharacterSet").'" ?'.">\n";
								echo "<response>\n";
								echo "  <status>OK</status>\n";
								echo "  <shop>\n";
								echo "      <shopPath>".$_POST['ShopPath']."</shopPath>\n";
								echo "      <controlPanel>".$_POST['ShopPath']."admin/index.php</controlPanel>\n";
								echo "  </shop>\n";
								echo "  <user>\n";
								echo "      <username>admin</username>\n";
								echo "      <password>".$_POST['UserPass']."</password>\n";
								echo "  </user>\n";
								echo "</response>\n";
								exit;
							}
							else {
								$this->template->display('install.done.tpl');
							}
						}
						else {
							$message = GetLang("ConfigErr");
							$errors = array(
								0 => array(
									"code" => "unableSaveConfig",
									"message" => $message
								)
							);
							$this->ShowInstallErrors($message, $errors, false, true);
							return;
						}
					}
					else {
						list($error, $level) = $db->GetError();
						$GLOBALS["ISC_CLASS_DB"]->Query("rollback");
						$message = sprintf(GetLang("DBErr"), $error);
						$errors = array(
							0 => array(
								"code" => "dbError",
								"message" => $GLOBALS["ISC_CLASS_DB"]->Error()
							)
						);
						$this->ShowInstallErrors($message, $errors, false, true);
						return;
					}
				}
			}
			else {
				list($error, $level) = $db->GetError();
				$message = sprintf(GetLang("DBErr"), $error);
				$errors = array(
					0 => array(
						"code" => "dbConnectError",
						"message" => $error
					)
				);
				$this->ShowInstallErrors($message, $errors, false, true);
				return;
			}
		}

		/**
		 * _CheckPermissions
		 * Make sure files/folders have appropriate permissions
		 *
		 * @param none
		 *
		 * @return Array containing folders with permissions
		 */
		private function _CheckPermissions()
		{

			$result = array();

			$old_umask = umask(0);

			include_once(ISC_BASE_PATH.'/lib/class.file.php');

			$f = new FileClass();

			foreach($this->FoldersToCheck as $folder) {
				$path = ISC_BASE_PATH . '/' . $folder;

				if(file_exists($path)) {
					if (is_file($path)) {
						$file = true;
						$mode = '0666';
					} elseif (is_dir($path)) {
						$file = false;
						$mode = '0777';
					}
					//@isc_chmod($path, $mode);

					if(is_dir($path) && $f->CheckDirWritable($path)) {
						$result[] = array($folder, IS_OK, $file);
					}
					else if (is_file($path) && $f->CheckFileWritable($path)) {
						$result[] = array($folder, IS_OK, $file);
					}
					else {
						$result[] = array($folder, NOT_WRITABLE, $file);
					}
				}
				else {
					$result[] = array($folder, DOESNT_EXIST);
				}
			}

			umask($old_umask);

			return $result;
		}

		public function CheckInstallationPrerequisites()
		{
			// Check the permissions on required files/folders
			$folders = $this->_CheckPermissions();
			$bad_folders = 0;
			$folder_messages = array();

			foreach($folders as $folder) {
				switch($folder[1]) {
					case NOT_WRITABLE: {
						if ($folder[2]) {
							$type = "file";
						} else {
							$type = "folder";
						}
						$message = "The ".$type." <strong>" . $folder[0] . "</strong> is not writable. Please CHMOD it to ";
						if (isset($folder[2]) && $folder[2] === true) {
							$message .= "646 or 664 or 666";
						} else {
							$message .= "757 or 775 or 777";
						}
						$code = "filePermissions";
						break;
					}
					case DOESNT_EXIST: {
						$message = "The file/folder <strong>" . $folder[0] . "</strong> doesn't exist. Please create it.";
						$code = "doesntExist";
						break;
					}
					default: {
						$code = '';
						$message = '';
					}
				}
				if($code != '' && $message != '') {
					$folder_messages[] = array(
						"code" => $code,
						"extra" => $folder[0],
						"message" => $message
					);
				}
			}

			if(!empty($folder_messages)) {
				$this->ShowInstallErrors(GetLang('PermissionsError'), $folder_messages, true);
			}

			// Are we running the required version of PHP?
			$php_check = version_compare(PHP_VERSION, PHP_VERSION_REQUIRED);

			if($php_check < 0) {
				$errors = array(
					0 => array(
						"code" => "phpVersion",
						"extra" => PHP_VERSION,
						"message" => sprintf(GetLang("PHPV5Message"), PHP_VERSION_REQUIRED, PHP_VERSION)
					)
				);
				$this->ShowInstallErrors(GetLang('BadPHPVersion'), $errors);
			}

			// Is GD enabled?
			if(!GDEnabled()) {
				$errors = array(
					0 => array(
						"code" => "gdRequired",
						"message" => GetLang('GDRequiredMessage'),
					)
				);
				$this->ShowInstallErrors(GetLang('GDRequired'), $errors);
			}

			// Is simpleXML supported?
			if(!function_exists('simplexml_load_string')) {
				$errors = array(
					0 => array(
						"code" => "simpleXMLRequired",
						"message" => GetLang('SimpleXMLRequiredMessage'),
					)
				);
				$this->ShowInstallErrors(GetLang('SimpleXMLRequired'), $errors);
			}
		}

		/**
		 * GetVariables
		 * This is step 1 of the installation process. It checks all the right files
		 * and folders have write permissions and prompts the user for all install variables.
		 *
		 * @param Boolean $Error defaults to false, if its set to true, something went wrong!
		 *
		 * @return Void
		 */
		private function GetVariables($Error = false, $Message = "")
		{
			if(defined('INSTALL_WARNING_MSG') && INSTALL_WARNING_MSG) {
				$GLOBALS['InstallWarning'] = INSTALL_WARNING_MSG;
			}
			else {
				$GLOBALS['HideInstallWarning'] = 'display: none';
			}

			// Check the prerequisites
			$this->CheckInstallationPrerequisites();

			if($Error) {
				$GLOBALS['Message'] = "<h3 style='padding-bottom:10px; color:red'>" . GetLang("Oops") . "</h3>" . $Message;
				$GLOBALS['LicenseKey'] = $_POST['LK'];
				$GLOBALS['ShopPath'] = $_POST['ShopPath'];
				$GLOBALS['StoreCountryLocationId'] = $_POST['StoreCountryLocationId'];
				$GLOBALS['StoreCurrencyCode'] = $_POST['StoreCurrencyCode'];

				$GLOBALS['InstallSampleData'] = '';
				if(isset($_POST['installSampleData'])) {
					$GLOBALS['InstallSampleData'] = 'checked="checked"';
				}
				$GLOBALS['UserEmail'] = $_POST['UserEmail'];
				$GLOBALS['UserPass'] = $_POST['UserPass'];
				$GLOBALS['dbUser'] = $_POST['dbUser'];
				$GLOBALS['dbPass'] = $_POST['dbPass'];
				$GLOBALS['dbServer'] = $_POST['dbServer'];
				$GLOBALS['dbDatabase'] = $_POST['dbDatabase'];
				$GLOBALS['tablePrefix'] = $_POST['tablePrefix'];
				$GLOBALS['AutoJS'] = "window.setTimeout(\"$('#dbChoice1').click(); $('.DBDetails').show();\", 100);";
			}
			else {
				if(defined('IS_TRIAL') && IS_TRIAL == true) {
					$GLOBALS['IsTrial'] = 1;
					$GLOBALS['Message'] = "<h3 style='padding-bottom:10px'>" . GetLang("InstallTrial") . "</h3>" . GetLang("InstallTrialIntro");
				}
				else {
					$GLOBALS['IsTrial'] = 0;
					$GLOBALS['Message'] = "<h3 style='padding-bottom:10px'>" . GetLang("InstallInterspireShoppingCart") . "</h3>" . GetLang("InstallIntro");
				}
				$GLOBALS['InstallSampleData'] = 'checked="checked"';
				$GLOBALS['StoreCountryLocationId'] = 0;
			}

			if(isset($GLOBALS['LicenseKey']) && isset($GLOBALS['LE'])) {
				$GLOBALS['serverStamp'] = $GLOBALS['LicenseKey'];
				getClass('ISC_ADMIN_AUTH')->SavePerms("");
				if(isset($GLOBALS['KM'])) {
					$GLOBALS['Message'] = "<h3 style='padding-bottom:10px; color:red'>" . GetLang("Oops") . "</h3>" . $Message;
				}
			}

			if (!isset($GLOBALS['dbServer']) || $GLOBALS['dbServer'] == "") {
				if (isset($_ENV['DATABASE_SERVER'])) {
					// mediatemple.net kindly sets an environment variable with the
					// correct mysql host, so if it exists, lets make use of it!
					$GLOBALS['dbServer'] = isc_html_escape($_ENV['DATABASE_SERVER']);
				} else {
					$GLOBALS['dbServer'] = "localhost";
				}
			}

			if(!isset($GLOBALS['tablePrefix']) || $GLOBALS['tablePrefix'] == "") {
				$GLOBALS['tablePrefix'] = "isc_";
			}

			$app_path = dirname(dirname($_SERVER['PHP_SELF']))."/";
			if(!isset($GLOBALS['ShopPath']) || $GLOBALS['ShopPath'] == "" || $GLOBALS['ShopPath'] == "http://") {
				$GLOBALS['ShopPath'] = rtrim("http://" . $_SERVER["HTTP_HOST"] . $app_path, '/').'/';
			}

			$GLOBALS['StoreCountryList'] = GetCountryList($GLOBALS['StoreCountryLocationId'], false, "AllCountries", 0, true, false);

			$this->template->assign('PCIPasswordMinLen', GetConfig('PCIPasswordMinLen'));
			$this->template->display('install.form.tpl');
		}

		/**
		 * Send anonymous details about the server back.
		 *
		 * @param int The type of install (1 for an upgrade, 0 for new install)
		 * @param int The previous version number if there was one.
		 */
		public function SendServerDetails($installtype=0,$prev_version=0)
		{
			require_once(ISC_BASE_PATH.'/lib/server_stats/server_stats.php');
			$sending = serverStats_Send($installtype, $prev_version, PRODUCT_VERSION, PRODUCT_ID);
			if($sending['InfoSent'] === false) {
				$GLOBALS['InfoImage'] = $sending['InfoImage'];
			}
		}

		private function InstallSampleData()
		{
			$queries = $this->template->render('install.sample.tpl');
			$queries = preg_replace('#^--.*$#m', '', $queries);
			$queries = preg_split('#;\s*\n#', $queries, -1 , PREG_SPLIT_NO_EMPTY);

			foreach($queries as $query) {
				$query = trim($query);
				if (empty($query)) {
					continue;
				}

				$query = str_ireplace("%%PREFIX%%", $_POST['tablePrefix'], $query);
				if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
					$installMessage = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
					$errors = array(
						0 => array(
							"code" => 'installSampleData',
							"message" => $installMessage
						)
					);
					return $this->ShowInstallErrors($installMessage, $errors, false, true);
				}
			}
		}

		/**
		* _BuildEncryptionToken
		* Create a token which will be used to encrypt credit card details
		*
		* @return String The encrypted token
		*/
		private function _BuildEncryptionToken()
		{
			return md5(uniqid());
		}

		private function dir_depth_compare($a, $b)
		{
			if (substr_count($a, '/') > substr_count($b, '/')) {
				return 1;
			} elseif (substr_count($a, '/') < substr_count($b, '/')) {
				return -1;
			} else {
				return 0;
			}
		}

		/**
		 * Build an XML request from the passed array.
		 *
		 * @param array An array of tags/keys/values to build the XML from.
		 * @return string The built XML.
		 */
		public function BuildXMLFromArray($array)
		{
			$xml = '';
			foreach($array as $k => $v) {
				$xml .= '<'.$k.'>';
				if(is_array($v)) {
					$xml .= "\n";
					$xml .= $this->BuildXMLFromArray($v);
					$xml .= "\n";
				}
				else {
					$xml .= $v;
				}
				$xml .= '</'.$k.'>';
			}
			return $xml;
		}
	}
