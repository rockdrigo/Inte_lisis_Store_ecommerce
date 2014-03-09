<?php
function NormalizeSSLSettings()
{
	// Some hosts do not set $_SERVER['HTTPS'], so the only way we can detect SSL urls is if
	// they set the SCRIPT_URI and it contains https://
	if(isset($_SERVER['SCRIPT_URI']) && substr($_SERVER['SCRIPT_URI'], 0, 8) == 'https://') {
		$_SERVER['HTTPS'] = 'on';
	}

	// Normalise $_SERVER['HTTPS']
	if(isset($_SERVER['HTTPS'])) {
		switch(strtolower($_SERVER['HTTPS'])) {
			case "true":
			case "on":
			case "1":
				$_SERVER['HTTPS'] = 'on';
				break;
			default:
				$_SERVER['HTTPS'] = 'off';
		}
	}
	else {
		$_SERVER['HTTPS'] = 'off';
	}
}

function SetupSSLOptions()
{
	$ShopPath = GetConfig('ShopPath');
	$GLOBALS['ISC_CFG']['ShopPathNormal'] = $ShopPath;

	$useSSL = GetConfig('UseSSL');

	// Is SSL enabled for the checkout process? If so setup the checkout links as such
	if($useSSL != SSL_NONE) {
		// determine which url we should be on
		if ($useSSL == SSL_NORMAL) {
			$ShopPathSSL = $ShopPath;
		}
		else if ($useSSL == SSL_SHARED) {
			$ShopPathSSL = GetConfig("SharedSSLPath");
		}
		elseif ($useSSL == SSL_SUBDOMAIN) {
			$ShopPathSSL = GetConfig("SubdomainSSLPath");
		}

		$ShopPathSSL = str_replace("http://", "https://", $ShopPathSSL);

		// Are we on a page that should use SSL?
		$ssl_pages = array (
			"account.php",
			"checkout.php",
			"login.php"
		);

		$uri = explode("/", $_SERVER['PHP_SELF']);
		$page = $uri[count($uri)-1];

		if (in_array($page, $ssl_pages)) {
			$ShopPath = $ShopPathSSL;
			// If we're not accessing this page via HTTPS then we need to redirect the user to the HTTPS version
			if($_SERVER['HTTPS'] == "off" && $_SERVER['REQUEST_METHOD'] == "GET") {
				$location = getCurrentLocation(true);
				// for shared ssl we need to transfer the session to the new site; append the the session token to the url
				if ($useSSL == SSL_SHARED && isset($_COOKIE['SHOP_SESSION_TOKEN']) && !isset($_GET['tk'])) {
					if(!empty($_GET)) {
						$location .= "&";
					}
					else {
						$location .= "?";
					}

					$location .= "tk=" . $_COOKIE['SHOP_SESSION_TOKEN'];
				}


				header("Location: " . $ShopPathSSL . '/' . $location);
				exit;
			}
		}

		$GLOBALS['ISC_CFG']['ShopPathSSL'] = $ShopPathSSL;
	}
	else {
		$GLOBALS['ISC_CFG']['ShopPathSSL'] = $ShopPath;
	}

	// If we're still on a HTTPS link (maybe this page requires it for a certain checkout module)
	// override the shop path with the HTTPS version
	if($_SERVER['HTTPS'] == "on") {
		$GLOBALS['ISC_CFG']['ShopPath'] = GetConfig('ShopPathSSL');
	}

	// Now that the variables are stored in the config section, we just map them back to the existing globals we had
	$GLOBALS['ShopPath'] = GetConfig('ShopPath');
	$GLOBALS['ShopPathSSL'] = GetConfig('ShopPathSSL');
	$GLOBALS['ShopPathNormal'] = GetConfig('ShopPathNormal');
}