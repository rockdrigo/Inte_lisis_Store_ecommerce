<?php
/**
 * URL Redirects Library Class
 *
 * This class holds a number of static functions related to 301 redirect URLs
 */
class ISC_REDIRECTS
{
	/**
	* Redirect type constants
	* After 'manual', these should be alphabetical.
	* Used 100's so new types can be inserted into their correct alphabetical place.
	* e.g. If you add a new type bwteen 100 and 200, make it 150 (not 101 or 199)
	*/
	const REDIRECT_TYPE_NOREDIRECT= -1;
	const REDIRECT_TYPE_MANUAL    = 0;
	const REDIRECT_TYPE_BRAND     = 100;
	const REDIRECT_TYPE_CATEGORY  = 200;
	const REDIRECT_TYPE_PAGE      = 300;
	const REDIRECT_TYPE_PRODUCT   = 400;

	/**
	* This function takes a URL and checks to see if there is a redirect for it, and if there is it redirects the user to the new URL
	*
	* @param string $originalURL The URL to check for redirect
	*/
	public static function checkRedirect($urlPath)
	{
		// @codeCoverageIgnoreStart
		$newUrl = self::generateRedirectUrl($urlPath);

		if (!$newUrl) {
			return false;
		}

		self::redirect($newUrl);
		// @codeCoverageIgnoreEnd
	}

	/**
	* Finds and returns a redirect for $urlPath which is typically the current REQUEST_URI (including app path for sub-dir installs)
	*
	* @param mixed $urlPath
	*/
	public function generateRedirectUrl($urlPath)
	{
		$urlPath = isc_substr($urlPath, strlen(GetConfig('AppPath')));
		$check = self::loadRedirectByURL($urlPath);

		if(!$check) {
			return false;
		}

		// if we made it here, we have redirect, we now need to find what item it's for and redirect
		if($check['redirectassoctype'] == self::REDIRECT_TYPE_MANUAL) {
			// it's a manual redirect, so the new URL has already been specified
			if(!is_null($check['redirectmanual'])) {
				$url = parse_url($check['redirectmanual']);
				if (isset($url['host'])) {
					return $check['redirectmanual'];
				} else {
					$url = GetConfig('AppPath') . $url['path'];
					if (!$url) {
						return '/';
					}
					return $url;
				}
			}
			return false;
		}

		GetLib("class.urls");

		$newUrl = null;
		$check['redirectassoctype'] = (int)$check['redirectassoctype'];
		switch ($check['redirectassoctype']) {
			case self::REDIRECT_TYPE_PRODUCT:
				$newUrl = ISC_URLS::getProductUrl($check['redirectassocid']);
				break;
			case self::REDIRECT_TYPE_CATEGORY:
				$newUrl = ISC_URLS::getCategoryUrl($check['redirectassocid']);
				break;
			case self::REDIRECT_TYPE_BRAND:
				$newUrl = ISC_URLS::getBrandUrl($check['redirectassocid']);
				break;
			case self::REDIRECT_TYPE_PAGE:
				$newUrl = ISC_URLS::getPageUrl($check['redirectassocid']);
				break;
			default:
				return false;
		}

		if(!$newUrl) {
			return false;
		}

		return $newUrl;
	}

	public function getTypeFromLabel($label)
	{
		if (empty($label)) {
			return false;
		}

		$label = isc_strtolower(trim($label));

		$labels = array(
			'Product'	=> self::REDIRECT_TYPE_PRODUCT,
			'Category'	=> self::REDIRECT_TYPE_CATEGORY,
			'Brand'		=> self::REDIRECT_TYPE_BRAND,
			'Page'		=> self::REDIRECT_TYPE_PAGE,
		);

		foreach ($labels as $redirectLabel => $redirectType) {
			if (isc_strtolower(GetLang($redirectLabel)) == $label) {
				return $redirectType;
			}

		}

		return false;
	}

	/**
	* This function takes a URL and checks to see if there is a match for it in the database
	*
	* @param string $originalURL The URL to grab the redirect information for.
	*/
	public static function loadRedirectByURL($originalURL)
	{
		$checkURL = $originalURL;
		$checkURL = $GLOBALS['ISC_CLASS_DB']->Quote($checkURL);
		$resource = $GLOBALS['ISC_CLASS_DB']->Query('select * from `[|PREFIX|]redirects` where redirectpath="' . $checkURL .'"');
		$redirectInfo = $GLOBALS['ISC_CLASS_DB']->Fetch($resource);

		if(is_array($redirectInfo)) {
			return $redirectInfo;
		}

		return false;
	}

	/**
	* This function takes a id and loads the corresponding row from the db
	*
	* @param string $id The id to grab the redirect information for.
	*/
	public static function loadRedirectById($id)
	{
		$resource = $GLOBALS['ISC_CLASS_DB']->Query('select * from `[|PREFIX|]redirects` where redirectid="' . $id .'"');
		$redirectInfo = $GLOBALS['ISC_CLASS_DB']->Fetch($resource);

		if(is_array($redirectInfo)) {
			return $redirectInfo;
		}

		return false;
	}

	/**
	 * Takes a URL and ensures it fits the format used in the database so comparisons can be made.
	 *
	 * @param string $url The URL to normalize
	 */
	public static function normalizeURLForDatabase($url, &$error)
	{
		$urlPieces = @parse_url($url);

		// Invalid URL was supplied
		if(!is_array($urlPieces)) {
			$error = GetLang('OldURLInvalid');
			return false;
		}

		$cleanUrl = '';
		$skip = false;
		if(!empty($urlPieces['path'])) {
			// Strip the slash from the passed URL, as it's added automatically
			$urlPieces['path'] = preg_replace('#^/#', '', $urlPieces['path']);

			// Remove the application path if it's at the start of the URL
			if(substr('/'.$urlPieces['path'].'/', 0, strlen(getConfig('AppPath').'/')) == getConfig('AppPath').'/') {
				$skip = true;
				$urlPieces['path'] = substr($urlPieces['path'], strlen(getConfig('AppPath')));
			}

			$cleanUrl .= $urlPieces['path'];
		}

		if(!empty($urlPieces['query'])) {
			$cleanUrl .= '?'.$urlPieces['query'];
		}

		// non empty path must contain at least a slash or a dot
		if (!empty($cleanUrl) && !isset($urlPieces['scheme']) && !$skip) {
			if (strpos($cleanUrl, '/') === false && strpos($cleanUrl, '.') === false) {
				$error = GetLang('OldURLInvalid');
				return false;
			}
		}

		// Redirects should always begin with a slash
		$cleanUrl = '/'.$cleanUrl;

		return $cleanUrl;
	}

	/**
	* The 'New' for this refers to the fact that this is for normalising redirect target urls (as opposed to 'old', source urls) and has nothing to do with the age of the method.
	*
	* @param string $url
	* @param string $error
	*/
	public static function normalizeNewURLForDatabase($url, &$error = '')
	{
		// only allow valid urls
		$url = parse_url($url);
		if (!$url) {
			$error = GetLang('NewURLInvalid');
			return false;
		}

		// build a list of urls this store is known by
		$storeUrls = array();

		$primary = parse_url(GetConfig('ShopPath'));
		$storeUrls[] = $primary;

		if (GetConfig('ShopPathSSL') && GetConfig('ShopPathSSL') != GetConfig('ShopPath')) {
			$storeUrls[] = parse_url(GetConfig('ShopPathSSL'));
		}

		if (isset($url['scheme'])) {
			// if a scheme is specified, only allow http
			if ($url['scheme'] != 'http' && $url['scheme'] != 'https') {
				$error = GetLang('NewURLInvalid');
				return false;
			}
		} else {
			if (!isset($url['path']) || isc_substr($url['path'], 0, 1) != '/') {
				// hostless paths must begin with a /
				$error = GetLang('NewURLInvalid');
				return false;
			}

			$path = $url['path'];
			unset($url['path']);

			$url = array_merge($url, $primary);
			if (isset($url['path'])) {
				$url['path'] .= $path;
			} else {
				$url['path'] = $path;
			}

		}

		GetLib('class.urls');
		$url = ISC_URLS::unparseUrl($url);

		// see if the redirect url matches any url this store is known by
		foreach ($storeUrls as $storeUrl) {
			// yeah, this ends up parsing and unparsing the stored urls but it means we get a reliable, well-formatted check
			$storeUrl = ISC_URLS::unparseUrl($storeUrl);

			if (isc_substr($url, 0, isc_strlen($storeUrl)) === $storeUrl) {
				$url = isc_substr($url, isc_strlen($storeUrl));
				break;
			}
		}

		return $url;
	}

	/**
	 * Sends a 301 header to the browser to redirect the user to the correct page and then dies.
	 *
	 * @param string $url The URL to redirect the user to
	 */
	public static function redirect($url)
	{
		// @codeCoverageIgnoreStart
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: " . $url);
		die();
		// @codeCoverageIgnoreEnd
	}

	/**
	* Checks if an automatic redirect based on the RedirectWWW setting should be performed
	*
	* @param string $uri The URI that to redirect to
	* @return mixed The new URL to redirect or false if no redirect is required
	*/
	public static function checkRedirectWWW($uri)
	{
		$redirectWWW = GetConfig('RedirectWWW');
		if ($redirectWWW == REDIRECT_NO_PREFERENCE) {
			return false;
		}

		$host = $_SERVER['SERVER_NAME'];

		$protocol = 'http://';
		if ($_SERVER['HTTPS'] == 'on') {
			// if we're using shared ssl or subdomain ssl then we shouldn't redirect
			if (GetConfig('UseSSL') == SSL_SHARED || GetConfig('UseSSL') == SSL_SUBDOMAIN) {
				return false;
			}

			$protocol = 'https://';
		}

		$port = '';
		if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
			$port = ':' . $_SERVER['SERVER_PORT'];
		}

		$redirectURL = '';

		// redirecting to www and the host doesn't contain www
		if ($redirectWWW == REDIRECT_TO_WWW && isc_substr($host, 0, 4) != 'www.') {
			// add www. into the URL
			$newHost = 'www.' . $host;
		}
		// redirecting to no-www and the host does contain www.
		elseif ($redirectWWW == REDIRECT_TO_NO_WWW && isc_substr($host, 0, 4) == 'www.') {
			// remove www. from the URL
			$newHost = isc_substr($host, 4);
		}
		else {
			return false;
		}

		$redirectURL = $protocol . $newHost . $port . $uri;

		// perform our redirect
		return $redirectURL;
	}


	/**
	* Adds or removes www. to the ShopPath depending on the RedirectWWW config setting.
	* This is so the store doesn't unnecessarily keep redirecting if the RedirectWWW setting doesn't match with the ShopPath.
	*
	* @param string $shopPath The ShopPath to normalize
	* @param REDIRECT_NO_PREFERENCE|REDIRECT_TO_WWW|REDIRECT_TO_NO_WWW $redirectWWW The chosen redirect www preference
	* @return string The normalized shop path
	*/
	public static function normalizeShopPath($shopPath, $redirectWWW)
	{
		if ($redirectWWW == REDIRECT_NO_PREFERENCE) {
			return $shopPath;
		}

		$info = @parse_url($shopPath);
		if ($info === false || empty($info['host'])) {
			return $shopPath;
		}

		$host = $info['host'];

		// set to redirect to www and their shop path doesn't contain www.
		if ($redirectWWW == REDIRECT_TO_WWW && isc_substr($host, 0, 4) != 'www.') {
			// add www. to host
			$newHost = 'www.' . $host;
		}
		// set to redirect to no-www but their shop path contains www?
		elseif ($redirectWWW == REDIRECT_TO_NO_WWW  && isc_substr($host, 0, 4) == 'www.') {
			// remove the www. from host
			$newHost = isc_substr($host, 4);
		}
		else {
			return $shopPath;
		}

		// reconstruct shop path
		$info['host'] = $newHost;

		GetLib('class.urls');
		$newShopPath = ISC_URLS::unparseUrl($info);

		return $newShopPath;
	}
}
