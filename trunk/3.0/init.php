<?php
require_once(dirname(__FILE__).'/lib/init.php');

define("APP_ROOT", dirname(__FILE__));

define("SEARCH_SIMPLE", 0);
define("SEARCH_ADVANCED", 1);

if (GetConfig('isSetup') === false) {
	header("Location: admin/");
	die();
}

$GLOBALS['PathInfo'] = array();
$GLOBALS['RewriteRules'] = array(
	"index" => array(
		"class" => "class.index.php",
		"name" => "ISC_INDEX",
		"global" => "ISC_CLASS_INDEX",
		"checkdatabase" => true,
	),
	"store" => array(
		"class" => "class.index.php",
		"name" => "ISC_INDEX",
		"global" => "ISC_CLASS_INDEX",
		"checkdatabase" => true,
	),
	"shop" => array(
		"class" => "class.index.php",
		"name" => "ISC_INDEX",
		"global" => "ISC_CLASS_INDEX",
		"checkdatabase" => true,
	),
	"products" => array(
		"class" => "class.product.php",
		"name" => "ISC_PRODUCT",
		"global" => "ISC_CLASS_PRODUCT"
	),
	"pages" => array(
		"class" => "class.page.php",
		"name" => "ISC_PAGE",
		"global" => "ISC_CLASS_PAGE"
	),
	"categories" => array(
		"class" => "class.category.php",
		"name" => "ISC_CATEGORY",
		"global" => "ISC_CLASS_CATEGORY"
	),
	"brands" => array(
		"class" => "class.brands.php",
		"name" => "ISC_BRANDS",
		"global" => "ISC_CLASS_BRANDS"
	),
	"news" => array(
		"class" => "class.news.php",
		"name" => "ISC_NEWS",
		"global" => "ISC_CLASS_NEWS"
	),
	"compare" => array(
		"class" => "class.compare.php",
		"name" => "ISC_COMPARE",
		"global" => "ISC_CLASS_COMPARE"
	),
	"404" => array(
		"class" => "class.404.php",
		"name" => "ISC_404",
		"global" => "ISC_CLASS_404"
	),
	"tags" => array(
		"class" => "class.tags.php",
		"name" => "ISC_TAGS",
		"global" => "ISC_CLASS_TAGS"
	),
	"vendors" => array(
		"class" => "class.vendors.php",
		"name" => "ISC_VENDORS",
		"global" => "ISC_CLASS_VENDORS"
	),
	"sitemap" => array(
		"class" => "class.sitemap.php",
		"name" => "ISC_SITEMAP",
		"global" => "ISC_CLASS_SITEMAP"
	)
);

$GLOBALS['RewriteURLBase'] = '';

// Initialise our session
require_once(ISC_BASE_PATH . "/includes/classes/class.session.php");
$GLOBALS['ISC_CLASS_SESSION'] = new ISC_SESSION();

// Is purchasing disabled in the store?
if(!GetConfig("AllowPurchasing")) {
	$GLOBALS['HidePurchasingOptions'] = "none";
}

// Are prices disabled in the store?
if(!GetConfig("ShowProductPrice")) {
	$GLOBALS['HideCartOptions'] = "none";
}

// Is the wishlist disabled in the store?
if(!GetConfig("EnableWishlist")) {
	$GLOBALS['HideWishlist'] = "none";
}

// Is account creation disabled in the store?
if(!GetConfig("EnableAccountCreation")) {
	$GLOBALS['HideAccountOptions'] = "none";
}

// Setup our currency. If we don't have one in our session then get/set our currency based on our geoIP location
SetupCurrency();

// Do we need to show the cart contents side box at all?
if(!isset($_SESSION['QUOTE']) || getCustomerQuote()->getNumItems() == 0) {
	$GLOBALS['HidePanels'][] = "SideCartContents";
}

$GLOBALS['ISC_CLASS_TEMPLATE'] = new TEMPLATE("ISC_LANG");
$GLOBALS['ISC_CLASS_TEMPLATE']->FrontEnd();

if(isset($_GET['fullSite']))
{
	if($_GET['fullSite'] > 0) {
		isc_setcookie('mobileViewFullSite', 1);
	}
	else {
		isc_setcookie('mobileViewFullSite', 0);
		$_COOKIE['mobileViewFullSite'] = 0;
	}
}

// Is this a mobile device?
if(isset($_GET['forceMobile']) || (canViewMobileSite() && empty($_GET['fullSite']) && empty($_COOKIE['mobileViewFullSite'])) ) {
	define('IS_MOBILE', true);
	$GLOBALS['ISC_CLASS_TEMPLATE']->setIsMobileDevice(true);

	// Reload the template configuration based on the mobile template
	$GLOBALS['TPL_CFG'] = $GLOBALS['ISC_CLASS_TEMPLATE']->getTemplateConfiguration();
}

$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplateBase(ISC_BASE_PATH . "/templates");
$GLOBALS['ISC_CLASS_TEMPLATE']->panelPHPDir = ISC_BASE_PATH . "/includes/display/";
$GLOBALS['ISC_CLASS_TEMPLATE']->templateExt = "html";

// Disable product comparisons if the template does not support them
if($GLOBALS['TPL_CFG']['MaxComparisonProducts'] == 0) {
	$GLOBALS['ISC_CFG']['EnableProductComparisons'] = false;
}

// check if the store is down for maintenance
if(GetConfig('DownForMaintenance')) {
	// we have token coming through
	if (!empty($_GET['ctk'])) {
		$token = $_GET['ctk'];
		// check if the token is valid for a user
		$query = "SELECT pk_userid FROM [|PREFIX|]users where token = '" . $GLOBALS['ISC_CLASS_DB']->Quote($token) . "'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			// set the cookie so the admin auth class will function
			ISC_SetCookie('STORESUITE_CP_TOKEN', $token, 0, true);
			$_COOKIE['STORESUITE_CP_TOKEN'] = $token;
		}
	}
	$GLOBALS['ISC_CLASS_ADMIN_AUTH'] = GetClass('ISC_ADMIN_AUTH');
	if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->IsLoggedIn() || !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_See_Store_During_Maintenance)) {
		define('MAINTENANCE_IS_ADMIN', false);
		Store_DownForMaintenance::showDownForMaintenance();
		die();
	}

	define('MAINTENANCE_IS_ADMIN', true);

	if(isset($_GET['showStore']) && $_GET['showStore'] == 'yes') {
		$_SESSION['AdminShowStore'] = true;
	} elseif (isset($_GET['showStore']) && $_GET['showStore'] == 'no') {
		$_SESSION['AdminShowStore'] = false;
	}

	if(!isset($_SESSION['AdminShowStore']) || (isset($_SESSION['AdminShowStore'])  && $_SESSION['AdminShowStore'] === false)) {
		Store_DownForMaintenance::showDownForMaintenance();
		die();
	}

	$GLOBALS['MaintenanceNotice'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("MaintenanceNotice");
}

$GLOBALS['ISC_CLASS_VISITOR'] = GetClass('ISC_VISITOR');

if(isset($GLOBALS['ShowStoreUnavailable'])) {
	$GLOBALS['ErrorMessage'] = GetLang('StoreUnavailable');
	$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
	$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	exit;
}

// Set the default page title
$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName'));

// Get the number of items in the cart if any
if(isset($_SESSION['QUOTE'])) {
	$quote = getCustomerQuote();
	$numItems = $quote->getNumItems();
	$items = $quote->getItems();
	foreach($items as $item) {
		if(!$item->getProductId()) {
			continue;
		}

		$GLOBALS['CartQuantity'.$item->getProductId()] = $item->getQuantity();
	}

	if ($numItems == 1) {
		$GLOBALS['CartItems'] = ' ('.GetLang('OneItem').')';
	}
	else if ($numItems > 1) {
		$GLOBALS['CartItems'] = ' ('.GetLang('XItems', array('count' => $numItems)).')';
	} else {
		$GLOBALS['CartItems'] = '';
	}
}

// Define our checkout link to use
$GLOBALS['CheckoutLink'] = CheckoutLink();

// If there's a design mode token in the URL, grab it, cookie it and then redirect to the current page.
// If we don't redirect and instead output the page, it's possible to grab the authenticaiton token
// from the URL via CSRF etc.
if(!empty($_GET['designModeToken']) && getClass('ISC_ADMIN_AUTH')->isDesignModeAuthenticated($_GET['designModeToken'])) {
	isc_setCookie('designModeToken', $_GET['designModeToken'], 0, true);
	ob_end_clean();
	header('Location: '.getConfig('ShopPathNormal'));
	exit;
}