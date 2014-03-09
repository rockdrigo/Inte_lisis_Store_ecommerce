<?php

	define("ISC_START_TIME", microtime(true));
	define("SERIALIZED_FALSE", "b:0;");
	define("JSON_NULL", "null");

	/**
	 * Run stripslashes on every value of a multidimension array
	 *
	 * @param mixed $value The variable to run stripslashes on
	 * @return mixed
	 **/
	function stripslashes_deep($value)
	{
		if (is_array($value)) {
			$value = array_map('stripslashes_deep', $value);
		} else {
			$value = stripslashes($value);
		}

		return $value;
	}

	// Development environment checks
	if(!defined('ISC_AJAX')) {
		// If the textmate integration environment variable exists, integrate
		if(isset($_SERVER['TEXTMATE_INTEGRATION']) && $_SERVER['HTTP_HOST'] == 'localhost') {
			define('TEXTMATE_ERRORS', 1);
			@include_once '/Applications/TextMate.app/Contents/SharedSupport/Bundles/PHP.tmbundle/Support/textmate.php';
		}
	}

	error_reporting(E_ALL & ~E_STRICT);
	ini_set("track_errors","1");
	@set_time_limit(0);
	@ob_start();
	ini_set("magic_quotes_runtime", "off");

	// If the PHP timezone function exists, set the default to GMT so calls to date()
	// will return the GMT time. Our date functions then apply the store timezone on top
	// of this
	if(function_exists('date_default_timezone_set')) {
		date_default_timezone_set('GMT');
	}

	// If magic_quotes_gpc is on, strip all the slashes it added. By doing
	// this we can be sure that all the gpc vars never have slashes and so
	// we will always need to treat them the same way
	if (get_magic_quotes_gpc()) {
		$_POST		= stripslashes_deep($_POST);
		$_GET		= stripslashes_deep($_GET);
		$_COOKIE	= stripslashes_deep($_COOKIE);
		$_REQUEST	= stripslashes_deep($_REQUEST);
	}
	// The absolute filesystem root to Interspire Shopping Cart

	// We fetch the real path to index.php in the main directory and then dirname() it
	// due to a strange bug on some Windows based servers where simply resolving up a directory
	// returns false for realpath().
	define('ISC_BASE_PATH', dirname(realpath(dirname(__FILE__).'/../index.php')));

	define('ISC_CONFIG_FILE', ISC_BASE_PATH.'/config/config.php');
	define('ISC_CONFIG_BACKUP_FILE', ISC_BASE_PATH.'/config/config.backup.php');
	define('ISC_CONFIG_DEFAULT_FILE', ISC_BASE_PATH.'/config/config.default.php');

	// The minimum version of PHP required to run Interspire Shopping Cart
	define("PHP_VERSION_REQUIRED", "5.1.4");

	// The minimum version of MySQL required to run Interspire Shopping Cart
	define("MYSQL_VERSION_REQUIRED", "4.1.0");

	// What version are we running?
	define('PRODUCT_ID', 'ISC');
	define('PRODUCT_VERSION', '6.1.1');
	define('PRODUCT_VERSION_CODE', 6101);

	define("ISC_SMALLPRINT", 1);
	define("ISC_MEDIUMPRINT", 2);
	define("ISC_LARGEPRINT", 4);
	define('ISC_HUGEPRINT', 8);

	define('ORDER_STATUS_INCOMPLETE', 0);
	define('ORDER_STATUS_PENDING', 1);
	define('ORDER_STATUS_SHIPPED', 2);
	define('ORDER_STATUS_PARTIALLY_SHIPPED', 3);
	define('ORDER_STATUS_REFUNDED', 4);
	define('ORDER_STATUS_CANCELLED', 5);
	define('ORDER_STATUS_DECLINED', 6);
	define('ORDER_STATUS_AWAITING_PAYMENT', 7);
	define('ORDER_STATUS_AWAITING_PICKUP', 8);
	define('ORDER_STATUS_AWAITING_SHIPMENT', 9);
	define('ORDER_STATUS_COMPLETED', 10);
	define('ORDER_STATUS_AWAITING_FULFILLMENT', 11);

	define('PAYMENT_STATUS_PAID', 1);
	define('PAYMENT_STATUS_PENDING', 2);
	define('PAYMENT_STATUS_DECLINED', 3);

	define("PT_PHYSICAL", 1);
	define("PT_DIGITAL", 2);
	define("PT_GIFTCERTIFICATE", 3);
	define("PT_VIRTUAL", 4);

	define("GIFT_CERTIFICATE_LENGTH", 15);

	define("FORMFIELDS_FORM_ACCOUNT", 1);
	define("FORMFIELDS_FORM_BILLING", 2);
	define("FORMFIELDS_FORM_SHIPPING", 3);

	// Create a general address ID
	define("FORMFIELDS_FORM_ADDRESS", FORMFIELDS_FORM_BILLING);

	define("MSG_ERROR", 0);
	define("MSG_SUCCESS", 1);
	define("MSG_INFO", 2);
	define('MSG_WARNING', 3);

	define('ISC_CACHE_DIRECTORY', ISC_BASE_PATH."/cache/");
	define('ISC_EMAIL_TEMPLATES_DIRECTORY', ISC_BASE_PATH."/templates/__emails");

	define("ISC_BACKUP_DIRECTORY", ISC_BASE_PATH."/admin/backups/");
	define("BACKUP_BUFFER_SIZE", 1024);

	define('ISC_ADMIN_TEMPLATE_CACHE_DIRECTORY', ISC_CACHE_DIRECTORY.'/templates/admin/');
	define('ISC_FRONT_TEMPLATE_CACHE_DIRECTORY', ISC_CACHE_DIRECTORY.'/templates/front/');

	define("FT_DOWNLOAD", 501);
	define("FT_IMAGE", 502);

	// SSL options
	define("SSL_NONE", 0);
	define("SSL_NORMAL", 1);
	define("SSL_SHARED", 2);
	define("SSL_SUBDOMAIN", 3);

	// These permissions should be used to chmod a file or directory as writeable in all cases EXCEPT for
	// displaying permission errors at the start of an install or upgrade
	define('ISC_WRITEABLE_FILE_PERM', fileperms(ISC_CONFIG_FILE));
	define('ISC_WRITEABLE_DIR_PERM', fileperms(dirname(ISC_CONFIG_FILE)));
	
	// Nissim: Special permisions added for template dirs
	define('ISC_TEMPLATE_FILE_PERM', '666');
	define('ISC_TEMPLATE_DIR_PERM', '777');	

	define('ISC_SAFEMODE', (bool) ini_get('safe_mode'));

	// All generated passwords will be this length
	define('GENERATED_PASSWORD_LENGTH', 12);

	// the maximum length (both width and height) for any product image uploaded to isc -- any images larger than this will be sized down for storage, any settings in the control panel for image sizes will be capped to this
	define('ISC_PRODUCT_IMAGE_MAXLONGEDGE', 1280);

	define('ISC_PRODUCT_IMAGE_SIZE_ZOOM', 1);
	define('ISC_PRODUCT_IMAGE_SIZE_STANDARD', 2);
	define('ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL', 3);
	define('ISC_PRODUCT_IMAGE_SIZE_TINY', 4);

	define('ISC_PRODUCT_IMAGE_DIMENSIONS_WIDTH', 0);
	define('ISC_PRODUCT_IMAGE_DIMENSIONS_HEIGHT', 1);

	// The minimum dimension to enable image zoom.
	define('ISC_PRODUCT_IMAGE_MIN_ZOOM_WIDTH', 300);
	define('ISC_PRODUCT_IMAGE_MIN_ZOOM_HEIGHT', 300);

	define('ISC_PRODUCT_DEFAULT_IMAGE_SIZE_ZOOM', 1280);
	define('ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD', 220);
	define('ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL', 120);
	define('ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY', 30);

	define('REDIRECT_NO_PREFERENCE', 0);
	define('REDIRECT_TO_WWW', 1);
	define('REDIRECT_TO_NO_WWW', 2);

	define('TAX_PRICES_ENTERED_INCLUSIVE', 1);
	define('TAX_PRICES_ENTERED_EXCLUSIVE', 0);

	define('TAX_BASED_ON_BILLING_ADDRESS', 0);
	define('TAX_BASED_ON_SHIPPING_ADDRESS', 1);
	define('TAX_BASED_ON_STORE_ADDRESS', 2);

	define('TAX_PRICES_DISPLAY_INCLUSIVE', 0);
	define('TAX_PRICES_DISPLAY_EXCLUSIVE', 1);
	define('TAX_PRICES_DISPLAY_BOTH', 2);

	define('TAX_BREAKDOWN_SUMMARY', 0);
	define('TAX_BREAKDOWN_RATE', 1);
	define("FEATURE_AVAILABLE_LIVE_EBAY_LISTING", 1);

	define('ISC_NESTEDSET_DEPTH_ALL', -1);
	define('ISC_NESTEDSET_START_ROOT', 0);

	if (version_compare(PHP_VERSION, PHP_VERSION_REQUIRED, '<')) {
		die("<h1>PHP ".PHP_VERSION_REQUIRED." or higher is required to run Interspire Shopping Cart.</h1>");
	}

	$GLOBALS['ProductVersion'] = PRODUCT_VERSION;

	// Register our autoloader
	require_once(ISC_BASE_PATH.'/lib/Interspire/Autoloader.php');
	Interspire_Autoloader::addPath(ISC_BASE_PATH.'/lib');
	Interspire_Autoloader::register();

	require_once(ISC_BASE_PATH.'/lib/general.php');
	require_once(ISC_BASE_PATH.'/lib/pricing.php');
	require_once(ISC_BASE_PATH.'/lib/multibyte.php');
	require_once(ISC_BASE_PATH . "/lib/orders.php");
	require_once(ISC_BASE_PATH . "/lib/shipping.php");
	require_once(ISC_BASE_PATH . "/lib/notification.php");
	require_once(ISC_BASE_PATH . "/lib/analytics.php");
	require_once(ISC_BASE_PATH . "/lib/addons.php");
	require_once(ISC_BASE_PATH . "/lib/checkout.php");
	require_once(ISC_BASE_PATH . "/lib/currency.php");
	require_once(ISC_BASE_PATH . "/lib/ssl.php");
	require_once(ISC_BASE_PATH . "/lib/templates/template.php");
	require_once(ISC_BASE_PATH . "/lib/templates/panel.php");
	require_once(ISC_BASE_PATH . "/lib/templates/products_panel.php");
	require_once(ISC_BASE_PATH . "/admin/includes/whitelabel.php");
	
	require_once(ISC_BASE_PATH.'/lib/intelisis.php');

	require(ISC_CONFIG_DEFAULT_FILE);
	require(ISC_CONFIG_FILE);

	// If a backup configuration file exists, attempt to load from it
	if(GetConfig('isSetup') == false && file_exists(ISC_CONFIG_BACKUP_FILE)) {
		if(RevertToBackupConfig()) {
			require(ISC_CONFIG_BACKUP_FILE);
		}
		else {
			echo "Your <strong>config/config.php</strong> file is not writeable and cannot be restored to a previously backed up version. Please change the file permissions of this file so that it is writeable (CHMOD 757, 777 etc)";
			exit;
		}
	}

	if(file_exists(ISC_BASE_PATH.'/custom/config.php')) {
		require ISC_BASE_PATH.'/custom/config.php';
	}

	if (GetConfig('HostingId')) {
		// forced config settings for BigCommerce
		$GLOBALS['ISC_CFG']['DeletedOrdersAction'] = 'delete';
		$GLOBALS['ISC_CFG']['GiftCertificateCustomDirectory'] = '__custom/GiftThemes';
		$GLOBALS['ISC_CFG']['GiftCertificateMasterDirectory'] = '__master/__gift_themes';
	}

	require(ISC_BASE_PATH . '/lib/database/mysql.php');
	// Set the character encoding to use

	$GLOBALS['Year'] = isc_date('Y');

	header("Content-Type: text/html; charset=" . GetConfig('CharacterSet'));
	STSSetEncoding(GetConfig('CharacterSet'));

	// Connect to the database - MySQL or PostgreSQL
	if (GetConfig('isSetup')) {
		NormalizeSSLSettings();

		// Are they accessing the store via an alternate URL?
		if(!empty($_SERVER['HTTP_HOST'])) {
			$protocol = 'http';
			if($_SERVER['HTTPS'] == 'on') {
				$protocol = 'https';
			}
			$currentLocation = $protocol.'://'.$_SERVER['HTTP_HOST'].'/'.trim(GetCurrentLocation(), '/').'/';
			$alternateUrls = GetConfig('AlternateURLs');
			if(!empty($alternateUrls) && strpos($currentLocation, GetConfig('ShopPath').'/') === false) {
				foreach(GetConfig('AlternateURLs') as $url) {
					$url = rtrim($url, '/').'/';
					if(strpos($currentLocation, $url) !== false) {
						$parsedLocation = ParseShopPath($url);
						$GLOBALS['ISC_CFG']['ShopPath'] = $parsedLocation['shopPath'];
						break;
					}
				}
			}
		}

		// Setup SSL options and links
		SetupSSLOptions();

		// Store the currently used protocol
		$protocol = 'http';
		if($_SERVER['HTTPS'] == 'on') {
			$protocol = 'https';
		}
		$GLOBALS['Protocol'] = $protocol;

		// Setup the application path based on the current location
		$GLOBALS['ISC_CFG']['ShopPath'] = rtrim(GetConfig('ShopPath'), '/');
		$parsedLocation = ParseShopPath($GLOBALS['ISC_CFG']['ShopPath']);
		if(!empty($parsedLocation['appPath'])) {
			$GLOBALS['ISC_CFG']['AppPath'] = $parsedLocation['appPath'];
		}
		else {
			$GLOBALS['ISC_CFG']['AppPath'] = '';
		}

		// Load the configuration file for the active template.
		// Even though we may be in the control panel, we need to do this here as the email templates
		// can reference these locations as well and we have emails that can be sent via the control panel
		require_once(ISC_BASE_PATH . "/templates/" . GetConfig('template') . "/config.php");

		// Setup our available template and image paths
		$GLOBALS['TPL_PATH'] = GetConfig('ShopPath').'/templates/'.GetConfig('template');

		if(!empty($GLOBALS['TPL_CFG']['ImagePath'])) {
			$GLOBALS['IMG_PATH'] = $GLOBALS['TPL_CFG']['ImagePath'];
		}
		else {
			$GLOBALS['IMG_PATH'] = $GLOBALS['TPL_PATH'].'/images';
		}

		if(!empty($GLOBALS['TPL_CFG']['StylesheetPath'])) {
			$GLOBALS['STYLE_PATH'] = $GLOBALS['TPL_CFG']['StylesheetPath'];
		}
		else {
			$GLOBALS['STYLE_PATH'] = $GLOBALS['TPL_PATH'].'/Styles';
		}

		$db_type = 'MySQLDb';
		$db = new $db_type();

		if(isset($GLOBALS['Debug']) && $GLOBALS['Debug'] == 1) {
			if(defined('ISC_ADMIN_CP')) {
				$logFile = 'admin-';
			}
			else {
				$logFile = 'front-';
			}
			$logFile .= gmdate('Y-m-d-H');

			$db->QueryLog = ISC_BASE_PATH.'/logs/'.$logFile.'.queries.txt';
			$db->TimeLog = ISC_BASE_PATH.'/logs/'.$logFile.'.query-time.txt';
			$db->ErrorLog = ISC_BASE_PATH.'/logs/'.$logFile.'.errors.txt';
		}

		$db->TablePrefix = GetConfig("tablePrefix");
		$db->charset = GetConfig('dbEncoding');
		$db->timezone = '+0:00'; // Tell the database server to always do its time operations in GMT +0. We perform adjustments in the code for the timezone

		$connection = $db->Connect(GetConfig("dbServer"), GetConfig("dbUser"), GetConfig("dbPass"), GetConfig("dbDatabase"));

		// Create a reference to the database object
		$GLOBALS['ISC_CLASS_DB'] = &$db;

		if (!$connection) {
			list($error, $level) = $db->GetError();
			// If we're in the control panel, we can show the actual message
			if(defined("ISC_ADMIN_CP")) {
				$error = str_replace(GetConfig('dbServer'), "[database server]", $error);
				$error = str_replace(GetConfig('dbUser'), "[database user]", $error);
				$error = str_replace(GetConfig('dbPass'), "[database pass]", $error);
				$error = str_replace(GetConfig('dbDatabase'), "[database]", $error);

				echo "<strong>Unable to connect to database: </strong>".$error;
				exit;
			}
			else {
				$GLOBALS['ShowStoreUnavailable'] = 1;
			}
		}

		// Initialise the logging system
		require_once(ISC_BASE_PATH . "/lib/class.log.php");
		$GLOBALS['ISC_CLASS_LOG'] = new ISC_LOG();
		set_error_handler(array($GLOBALS['ISC_CLASS_LOG'], 'HandlePHPErrors'));

		$GLOBALS['StoreName'] = isc_html_escape(GetConfig('StoreName'));

		$public_config_vars = array (
			'AppPath',
			'SiteColor',
			'template',
			'StoreLogo',
			'DownloadDirectory',
			'ImageDirectory',
			'JSCacheToken',
		);

		foreach ($public_config_vars as $var) {
			$GLOBALS[$var] = GetConfig($var);
		}

		$GLOBALS['ThousandsToken'] = str_replace("'", '&apos;', GetConfig('ThousandsToken'));
		$GLOBALS['DecimalToken'] = str_replace("'", '&apos;', GetConfig('DecimalToken'));
		$GLOBALS['DimensionsThousandsToken'] = str_replace("'", '&apos;', GetConfig('DimensionsThousandsToken'));
		$GLOBALS['DimensionsDecimalToken'] = str_replace("'", '&apos;', GetConfig('DimensionsDecimalToken'));

		// If debug mode (control panel enabled) is set up, tell the DB class to spit
		// out any queries at the bottom of the page
		if(GetConfig('DebugMode') && isset($_REQUEST['debug'])) {
			$GLOBALS['ISC_CLASS_DB']->StoreQueryList = true;
		}

		//Initialize the data store system
		require_once ISC_BASE_PATH."/lib/class.datastore.php";
		$GLOBALS['ISC_CLASS_DATA_STORE'] = new ISC_DATA_STORE();

		//Initialize the form widget system
		require_once ISC_BASE_PATH . "/lib/form.php";
		$GLOBALS['ISC_CLASS_FORM'] = new ISC_FORM();

		// Are SEO urls automatically enabled?
		$GLOBALS['EnableSEOUrls'] = GetConfig('EnableSEOUrls');
		if(GetConfig('EnableSEOUrls') == 2) {
			if(isset($_SERVER['SEO_SUPPORT']) && $_SERVER['SEO_SUPPORT'] == 1) {
				$GLOBALS['EnableSEOUrls'] = 1;
			} elseif (isset($_SERVER["HTTP_X_REWRITE_URL"]) && !empty($_SERVER["HTTP_X_REWRITE_URL"])) {
				$GLOBALS['EnableSEOUrls'] = 1;
			} else {
				$GLOBALS['EnableSEOUrls'] = 0;
			}
		}
		
		function WorkingAfterHours()
		{
		$time = time();
		date_default_timezone_set('America/Mexico_City');
		
		$nowHour = date('G');
		$nowMinute = date('i');
			
		if($GLOBALS['ISC_CFG']['StoreHoursFromHours'] <= $GLOBALS['ISC_CFG']['StoreHoursToHours'])
		{
			if($nowHour < $GLOBALS['ISC_CFG']['StoreHoursFromHours'])
			{
				return true;
			}
			if ($nowHour > $GLOBALS['ISC_CFG']['StoreHoursToHours'])
			{
				return true;
			}
			if($nowHour == $GLOBALS['ISC_CFG']['StoreHoursFromHours'])
			{
				if($nowMinute < $GLOBALS['ISC_CFG']['StoreHoursFromMinutes'])
				{
					return true;
				}
			}
			if($nowHour == $GLOBALS['ISC_CFG']['StoreHoursToHours'])
			{
				if($nowMinute > $GLOBALS['ISC_CFG']['StoreHoursToMinutes'])
				{
					return true;
				}
			}
		}
		else
		{
			if( ($nowHour < $GLOBALS['ISC_CFG']['StoreHoursFromHours']) && ($nowHour > $GLOBALS['ISC_CFG']['StoreHoursToHours']) )
			{
				return true;
			}
			if($nowHour == $GLOBALS['ISC_CFG']['StoreHoursFromHours'])
			{
				if($nowMinute < $GLOBALS['ISC_CFG']['StoreHoursFromMinutes'])
				{
					return true;
				}
			}
			if($nowHour == $GLOBALS['ISC_CFG']['StoreHoursToHours'])
			{
				if($nowMinute > $GLOBALS['ISC_CFG']['StoreHoursToMinutes'])
				{
					return true;
				}
			}
		}
		return false;
		}
	}
