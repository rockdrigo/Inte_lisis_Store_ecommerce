<?php
require_once(dirname(__FILE__) . '/../../../init.php');
require_once(dirname(__FILE__) . "/types/customers.php");
require_once(dirname(__FILE__) . "/types/orders.php");
require_once(dirname(__FILE__) . "/types/products.php");
require_once(dirname(__FILE__) . "/types/common.php");
$GLOBALS['XMLReturn'] = '';
@ini_set('display_errors', 'Off');

//check for version number request post - this is done by SEOM before each communication to prove that the script exists.
if (isset($_REQUEST['setifunction']) && $_REQUEST['setifunction'] == 'sendversion') {
	if(isset($_REQUEST['omversion'])) {
		$version = $_REQUEST['omversion'];
		echo "SETIResponse: version=$version";
		die();
	} else {//it's not a required field so it may not be there...still need to return something
		echo "SETIResponse: version=5.500";
		die();
	}
}

//check for SSL. SSL URL is required. HTTPS SERVER is set to 'off' on some Windows servers so check for a value of 'off' also.
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'off') {

	//check for admin username and password in post, then validate.
	if (isset($_REQUEST['setiuser']) && $_REQUEST['setiuser'] != '' && isset($_REQUEST['password']) && $_REQUEST['password'] != '') {
		/* then they posted their user and password, so we need to verify the password. First get the user class. */
		$userManager = getClass('ISC_ADMIN_USER');
		/* now we need the UID for that username */
		$userQuery = "SELECT pk_userid FROM [|PREFIX|]users where username = '" . $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['setiuser']) . "'";
		$userReturn = $GLOBALS['ISC_CLASS_DB']->Query($userQuery);
		$uid = '';
		while($userinfo = $GLOBALS['ISC_CLASS_DB']->Fetch($userReturn)){
			$uid = $userinfo['pk_userid'];
		}
		if ($userManager->verifyPassword($uid, $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['password'])) == true) {
			switch ($_REQUEST['setifunction']) {
				//process order requests - setifunction values: ordercount or downloadorders
				case 'ordercount':
					$SEOMOrder = new ACCOUNTING_STONEEDGE_ORDERS();
					$GLOBALS['XMLReturn'] .= $SEOMOrder->ProcessOrderCount();
					break;

				case 'downloadorders':
					$SEOMOrder = new ACCOUNTING_STONEEDGE_ORDERS();
					$GLOBALS['XMLReturn'] .= $SEOMOrder->DownloadOrders();
					header("Content-type: text/xml");
					break;

				//process customer requests - setifunction values: getcustomerscount or downloadcustomers
				case 'getcustomerscount':
					$SEOMCustomer = new ACCOUNTING_STONEEDGE_CUSTOMERS();
					$GLOBALS['XMLReturn'] .= $SEOMCustomer->ProcessCustomerCount();
					break;

				case 'downloadcustomers':
					$SEOMCustomer = new ACCOUNTING_STONEEDGE_CUSTOMERS();
					header("Content-type: text/xml");
					$GLOBALS['XMLReturn'] .= $SEOMCustomer->DownloadCustomers();
					break;

				//process product requests - setifunction values: getproductscount or downloadprods
				case 'getproductscount':
					$SEOMProduct = new ACCOUNTING_STONEEDGE_PRODUCTS();
					$GLOBALS['XMLReturn'] .= $SEOMProduct->ProcessProductCount();
					break;

				case 'downloadprods':
					$SEOMProduct = new ACCOUNTING_STONEEDGE_PRODUCTS();
					header("Content-type: text/xml");
					$GLOBALS['XMLReturn'] .= $SEOMProduct->DownloadProducts();
					break;

				//process inventory requests - setifunction values: downloadqoh, qohreplace, or invupdate
				case 'downloadqoh':
					$SEOMProduct = new ACCOUNTING_STONEEDGE_PRODUCTS();
					header("Content-type: text/xml");
					$GLOBALS['XMLReturn'] .= $SEOMProduct->DownloadQuantities();
					break;

				case 'qohreplace':
					$SEOMProduct = new ACCOUNTING_STONEEDGE_PRODUCTS();
					$GLOBALS['XMLReturn'] .= $SEOMProduct->ReplaceQuantity();
					break;

				case 'invupdate':
					$SEOMProduct = new ACCOUNTING_STONEEDGE_PRODUCTS();
					$GLOBALS['XMLReturn'] .= $SEOMProduct->UpdateInventory();
					break;
				default:
					//this shouldn't ever happen, return an error message
					header("Content-type: text/xml");
					$GLOBALS['XMLReturn'] .= '<?xml version="1.0" encoding="UTF-8" ?>';
					$GLOBALS['XMLReturn'] .= "<SETIError>";
						$GLOBALS['XMLReturn'] .= "<Response>";
							$GLOBALS['XMLReturn'] .= "<ResponseCode>3</ResponseCode>";
							$GLOBALS['XMLReturn'] .= "<ResponseDescription>Error: There was an error in the transmission sent from Stone Edge Order Manager.</ResponseDescription>";
						$GLOBALS['XMLReturn'] .= "</Response>";
					$GLOBALS['XMLReturn'] .= "</SETIError>";
					break;
			}
		} else {
			//then the username or password was invalid so create an error message and kill the page.

			header("Content-type: text/xml");
			$GLOBALS['XMLReturn'] .= '<?xml version="1.0" encoding="UTF-8" ?>';
			$GLOBALS['XMLReturn'] .= "<SETIError>";
				$GLOBALS['XMLReturn'] .= "<Response>";
					$GLOBALS['XMLReturn'] .= "<ResponseCode>3</ResponseCode>";
					$GLOBALS['XMLReturn'] .= "<ResponseDescription>Error: Either the username or password was invalid.</ResponseDescription>";
				$GLOBALS['XMLReturn'] .= "</Response>";
			$GLOBALS['XMLReturn'] .= "</SETIError>";
		}
	} else {
		//then they didn't post a user or password so create an error message and kill the page.
			header("Content-type: text/xml");
			$GLOBALS['XMLReturn'] .= '<?xml version="1.0" encoding="UTF-8" ?>';
			$GLOBALS['XMLReturn'] .= "<SETIError>";
				$GLOBALS['XMLReturn'] .= "<Response>";
					$GLOBALS['XMLReturn'] .= "<ResponseCode>3</ResponseCode>";
					$GLOBALS['XMLReturn'] .= "<ResponseDescription>Error: Either a username or password was not provided. Please check your settings in Stone Edge Order Manager and enter a username and password for this shopping cart.</ResponseDescription>";
				$GLOBALS['XMLReturn'] .= "</Response>";
			$GLOBALS['XMLReturn'] .= "</SETIError>";
	}
} else {
	//return error saying that the URL entered into order manager wasn't https://
	header("Content-type: text/xml");
	$GLOBALS['XMLReturn'] .= '<?xml version="1.0" encoding="UTF-8" ?>';
	$GLOBALS['XMLReturn'] .= "<SETIError>";
		$GLOBALS['XMLReturn'] .= "<Response>";
			$GLOBALS['XMLReturn'] .= "<ResponseCode>3</ResponseCode>";
			$GLOBALS['XMLReturn'] .= "<ResponseDescription>Error: The URL entered into Stone Edge Order Manager is for an insecure connection. Please make sure that the URL begins with 'https://'</ResponseDescription>";
		$GLOBALS['XMLReturn'] .= "</Response>";
	$GLOBALS['XMLReturn'] .= "</SETIError>";
}

//echo $GLOBALS['XMLReturn'];
die($GLOBALS['XMLReturn']);
