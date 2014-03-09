<?php

	// Search engine friendly links
	define("CAT_LINK_PART", "categories");
	define("PRODUCT_LINK_PART", "products");
	define("BRAND_LINK_PART", "brands");

	/**
	 * Return an already instantiated (singleton) version of a class. If it doesn't exist, will automatically
	 * be created.
	 *
	 * @param string The name of the class to load.
	 * @return object The instantiated version fo the class.
	 */
	function GetClass($className)
	{
		static $classes;
		if(!isset($classes[$className])) {
			$classes[$className] = new $className;
		}
		$class = &$classes[$className];
		return $class;
	}

	/**
	 * Fetch a configuration variable from the store configuration file.
	 *
	 * @param string The name of the variable to fetch.
	 * @return mixed The value of the variable.
	 */
	function GetConfig($config)
	{
		if (array_key_exists($config, $GLOBALS['ISC_CFG'])) {
			return $GLOBALS['ISC_CFG'][$config];
		}
		return '';
	}

	/**
	 * Load a library class and instantiate it.
	 *
	 * @param string The name of the library class (in the current directory) to load.
	 * @return object The instantiated version of the class.
	 */
	function GetLibClass($file)
	{
		static $libs = array();
		if (isset($libs[$file])) {
			return $libs[$file];
		} else {
			include_once(dirname(__FILE__).'/'.$file.'.php');
			$libs[$file] = new $file;
			return $libs[$file];
		}
	}

	/**
	 * Load a library include file from the lib directory.
	 *
	 * @param string The name of the file to include (without the extension)
	 */
	function GetLib($file)
	{
		$FullFile = dirname(__FILE__).'/'.$file.'.php';
		if (file_exists($FullFile)) {
			include_once($FullFile);
		}
	}

	/**
	 * Convert a text string in to a search engine friendly based URL.
	 *
	 * @param string The text string to convert.
	 * @return string The search engine friendly equivalent.
	 */
	function MakeURLSafe($val)
	{
		$val = str_replace("-", "%2d", $val);
		$val = str_replace("+", "%2b", $val);
		$val = str_replace("+", "%2b", $val);
		$val = str_replace("/", "{47}", $val);
		$val = urlencode($val);
		$val = str_replace("+", "-", $val);
		return $val;
	}

	/**
	 * Convert an already search engine friendly based string back to the normal text equivalent.
	 *
	 * @param string The search engine friendly version of the string.
	 * @return string The normal textual version of the string.
	 */
	function MakeURLNormal($val)
	{
		$val = str_replace("-", " ", $val);
		$val = urldecode($val);
		$val = str_replace("{47}", "/", $val);
		$val = str_replace("%2d", "-", $val);
		$val = str_replace("%2b", "+", $val);
		return $val;
	}

	/**
	 * Return the current unix timestamp with milliseconds.
	 *
	 * @return float The time since the UNIX epoch in milliseconds.
	 */
	function microtime_float()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}

	/**
	 * Display the contents of a variable on the page wrapped in <pre> tags for debugging purposes.
	 *
	 * @param mixed The variable to print.
	 * @param boolean Set to true to trim any leading whitespace from the variable.
	 */
	function Debug($var, $stripLeadingSpaces=false)
	{
		echo "\n<pre>\n";
		if ($stripLeadingSpaces) {
			$var = preg_replace("%\n[\t\ \n\r]+%", "\n", $var);
		}
		if (is_bool($var)) {
			var_dump($var);
		} else {
			print_r($var);
		}
		echo "\n</pre>\n";
	}

	/**
	 * Print a friendly looking backtrace up to the last execution point.
	 *
	 * @param boolean Do we want to stop all execution (die) after outputting the trace?
	 * @param boolean Do we want to return the output instead of echoing it ?
	 */
	function trace($die=false, $return=true)
	{
		$trace = debug_backtrace();
		$backtrace = "<table style=\"width: 100%; margin: 10px 0; border: 1px solid #aaa; border-collapse: collapse; border-bottom: 0;\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
		$backtrace .= "<thead><tr>\n";
		$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">File</th>\n";
		$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">Line</th>\n";
		$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">Function</th>\n";
		$backtrace .= "</tr></thead>\n<tbody>\n";

		// Strip off last item (the call to this function)
		array_shift($trace);

		foreach ($trace as $call) {
			if (!isset($call['file'])) {
				$call['file'] = "[PHP]";
			}
			if (!isset($call['line'])) {
				$call['line'] = "&nbsp;";
			}
			if (isset($call['class'])) {
				$call['function'] = $call['class'].$call['type'].$call['function'];
			}
			if(function_exists('textmate_backtrace')) {
				$call['file'] .= " <a href=\"txmt://open?url=file://".$call['file']."&line=".$call['line']."\">[Open in TextMate]</a>";
			}
			$backtrace .= "<tr>\n";
			$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['file']}</td>\n";
			$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['line']}</td>\n";
			$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['function']}</td>\n";
			$backtrace .= "</tr>\n";
		}
		$backtrace .= "</tbody></table>\n";
		if (!$return) {
			echo $backtrace;
			if ($die === true) {
				die();
			}
		} else {
			return $backtrace;
		}
	}

	/**
	 * Return a language variable from the loaded language files.
	 *
	 * If supplying replacements, they'll be swapped out of the language file with the values
	 * supplied. The language function will look for any occurrences of :[array key] in the
	 * language file.
	 *
	 * @param string The name of the language variable to fetch.
	 * @param array Array of optional replacements that should be swapped out in language strings.
	 * @return string The language variable/string.
	 */
	function GetLang($name, $replacements=array())
	{
		if(!isset($GLOBALS['ISC_LANG'][$name])) {
			return '';
		}

		$string = $GLOBALS['ISC_LANG'][$name];
		if(empty($replacements)) {
			return $string;
		}

		// Prefix array keys with a colon
		$actualReplacements = array();
		foreach($replacements as $k => $v) {
			$actualReplacements[':'.$k] = $v;
		}
		return strtr($string, $actualReplacements);
	}

	/**
	 * Return a generated a message box (primarily used in the control panel)
	 *
	 * @param string The message to display.
	 * @param int The type of message to display. Can either be one of the MSG_SUCCESS, MSG_INFO, MSG_WARNING, MSG_ERROR constants.
	 * @return string The generated message box.
	 */
	function MessageBox($desc, $type=MSG_WARNING, $extraClasses = '')
	{
		// Return a prepared message table row with the appropriate icon
		$iconImage = '';
		$messageBox = '';

		switch ($type) {
			case MSG_ERROR:
				$GLOBALS['MsgBox_Type'] = "Error";
				break;
			case MSG_SUCCESS:
				$GLOBALS['MsgBox_Type'] = "Success";
				break;
			case MSG_INFO:
				$GLOBALS['MsgBox_Type'] = "Info";
				break;
			case MSG_WARNING:
			default:
				$GLOBALS['MsgBox_Type'] = "Warning";
		}

		$GLOBALS['MsgBox_Message'] = $desc;
		$GLOBALS['MsgBox_ExtraClasses'] = $extraClasses;

		if(defined('ISC_ADMIN_CP')) {
			return Interspire_Template::getInstance('admin')->render('Snippets/MessageBox.html');
		}
		else {
			return $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('MessageBox');
		}
	}

	/**
	 * Interspire Shopping Cart setcookie() wrapper.
	 *
	 * @param string The name of the cookie to set.
	 * @param string The value of the cookie to set.
	 * @param int The timestamp the cookie should expire. (if there is one)
	 * @param boolean True to set a HttpOnly cookie (Supported by IE, Opera 9, and Konqueror)
	 */
	function ISC_SetCookie($name, $value = "", $expires = 0, $httpOnly=false)
	{
		if (!isset($GLOBALS['CookiePath'])) {
			$GLOBALS['CookiePath'] = GetConfig('AppPath');
		}

		// Automatically determine the cookie domain based off the shop path
		if(!isset($GLOBALS['CookieDomain'])) {
			$host = "";
			$useSSL = GetConfig('UseSSL');
			if ($useSSL == SSL_SUBDOMAIN) {
				$url = parse_url(GetConfig('SubdomainSSLPath'));
				if(is_array($url)) {
					if (isset($url['host'])) {
						$host = $url['host'];
					}
					// strip off the subdomain at the start
					$pos = isc_strpos($host, ".");
					$host = isc_substr($host, $pos + 1);
				}
			}
			elseif ($useSSL == SSL_SHARED) {
				$shost = '';
				if (function_exists('apache_getenv')) {
					$shost = @apache_getenv('HTTP_HOST');
				}

				if (!$shost) {
					$shost = @$_SERVER['HTTP_HOST'];
				}

				$sslurl = parse_url(GetConfig('SharedSSLPath'));

				if ($shost == $sslurl['host']) {
					$host = preg_replace("#^www\.#i", "", $sslurl['host']);
				}
			}

			if (!$host) {
				$url = parse_url(GetConfig('ShopPath'));
				if(is_array($url)) {
					// Strip off the www. at the start
					$host = preg_replace("#^www\.#i", "", $url['host']);
				}
			}

			if($host) {
				$GLOBALS['CookieDomain'] = $host;

				// Prefix with a period so that we're covering both the www and no www
				if (strpos($GLOBALS['CookieDomain'], '.') !== false && !isIPAddress($GLOBALS['CookieDomain'])) {
					$GLOBALS['CookieDomain'] = ".".$GLOBALS['CookieDomain'];
				} else {
					unset($GLOBALS['CookieDomain']);
				}
			}
		}

		// Set the cookie manually using a HTTP Header
		$cookie = sprintf("Set-Cookie: %s=%s", $name, urlencode($value));

		// Adding an expiration date
		if ($expires !== 0) {
			$cookie .= sprintf("; expires=%s", @gmdate('D, d-M-Y H:i:s \G\M\T', $expires));
		}

		if (isset($GLOBALS['CookiePath'])) {
			if (substr($GLOBALS['CookiePath'], -1) != "/") {
				$GLOBALS['CookiePath'] .= "/";
			}

			$cookie .= sprintf("; path=%s", trim($GLOBALS['CookiePath']));
		}

		if (isset($GLOBALS['CookieDomain'])) {
			$cookie .= sprintf("; domain=%s", $GLOBALS['CookieDomain']);
		}

		if ($httpOnly == true) {
			$cookie .= "; HttpOnly";
		}

		header(trim($cookie), false);
	}

	/**
	 * Unset a set cookie.
	 *
	 * @param string The name of the cookie to unset.
	 */
	function ISC_UnsetCookie($name)
	{
		ISC_SetCookie($name, "", 1);
	}

	function ech0($LK)
	{
		$v = true;
		$e = 1;

		if (substr($LK, 0, 3) != B('SVND')) {
			$v = false;
		}

		$data = spr1ntf($LK);

		if ($data !== false) {
			$data['version'] = ($data['vn'] & 0xF0) >> 4;
			$data['nfr'] = $data['vn'] & 0x0F;
			$GLOBALS['LKN'] = $data['nfr'];
			unset($data['vn']);

			/*
			//Q2hlY2sgZm9yIGludmFsaWQga2V5IHZlcnNpb25z
			switch ($data['version']) {
				case 1:
					$v = false;
					break;
			}
			*/

			if (@$data['expires']) {
				if (preg_match('#^(\d{4})(\d\d)(\d\d)$#', $data['expires'], $matches)) {
					$ex = mktime(23, 59, 59, $matches[2], $matches[3], $matches[1]);
					if (isc_mktime() > $ex) {
						$GLOBALS['LE'] = "HExp";
						$GLOBALS['EI'] = date("jS F Y", $ex);
						$v = false;
					}
				}
			}

			if (!mysql_user_row($data['edition'])) {
				$GLOBALS['LE'] = "HInv";
				$v = false;
			}
			else {
				$e = $data['edition'];
			}
		} else {
			$GLOBALS['LE'] = "HInv";
			$v = false;
		}

		$host = '';

		if (function_exists('apache_getenv')) {
			$host = @apache_getenv('HTTP_HOST');
		}

		if (!$host) {
			$host = @$_SERVER['HTTP_HOST'];
		}

		$colon = strpos($host, ':');

		if ($colon !== false) {
			$host = substr($host, 0, $colon);
		}

		if ($host != B('bG9jYWxob3N0') && $host != B('MTI3LjAuMC4x')) {
			$hashes = array(md5($host));

			if (strtolower(substr($host, 0, 4)) == 'www.') {
				$hashes[] = md5(substr($host, 4));
			} else {
				$hashes[] = md5('www.'. $host);
			}

			if (!in_array(@$data['hash'], $hashes)) {
				$GLOBALS['LE'] = "HSer";
				$GLOBALS['EI'] = $host;
				$v = false;
			}
		}

		$GLOBALS[B("QXBwRWRpdGlvbg==")] = GetLang(B("RWRpdGlvbg==") . $e);

	        $v = true;
		return $v;
	}

	function mysql_user_row($result)
	{
		if (
			($result == ISC_SMALLPRINT) ||
			($result == ISC_MEDIUMPRINT) ||
			($result == ISC_LARGEPRINT) ||
			($result == ISC_HUGEPRINT)
			) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the passed string is a valid email address.
	 *
	 * @todo refactor
	 * @param string The email address to check.
	 * @return boolean True if the email is a valid format, false if not.
	 */
	function is_email_address($email)
	{
		// If the email is empty it can't be valid
		if (empty($email)) {
			return false;
		}

		// If the email doesnt have exactle 1 @ it isnt valid
		if (isc_substr_count($email, '@') != 1) {
			return false;
		}

		$matches = array();
		$local_matches = array();
		preg_match(':^([^@]+)@([a-zA-Z0-9\-][a-zA-Z0-9\-\.]{0,254})$:', $email, $matches);

		if (count($matches) != 3) {
			return false;
		}

		$local = $matches[1];
		$domain = $matches[2];

		// If the local part has a space but isnt inside quotes its invalid
		if (isc_strpos($local, ' ') && (isc_substr($local, 0, 1) != '"' || isc_substr($local, -1, 1) != '"')) {
			return false;
		}

		// If there are not exactly 0 and 2 quotes
		if (isc_substr_count($local, '"') != 0 && isc_substr_count($local, '"') != 2) {
			return false;
		}

		// if the local part starts or ends with a dot (.)
		if (isc_substr($local, 0, 1) == '.' || isc_substr($local, -1, 1) == '.') {
			return false;
		}

		// If the local string doesnt start and end with quotes
		if ((isc_strpos($local, '"') || isc_strpos($local, ' ')) && (isc_substr($local, 0, 1) != '"' || isc_substr($local, -1, 1) != '"')) {
			return false;
		}

		preg_match(':^([\ \"\w\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~\.]{1,64})$:', $local, $local_matches);

		// Check the domain has at least 1 dot in it
		if (isc_strpos($domain, '.') === false) {
			return false;
		}

		if (!empty($local_matches) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Build the HTML for the thumbnail image of a product.
	 *
	 * @todo refactor
	 * @param string The filename of the thumbnail.
	 * @param string The URL that the thumbnail should link to.
	 * @param string The optional target for the link.
	 * @return string The built HTML for the thumbnail.
	 */
	function ImageThumb($imageData, $link='', $target='', $class='')
	{
		$altText = "";

		if(!is_array($imageData)) {
			$thumb = $imageData;
		} else {
			$image = new ISC_PRODUCT_IMAGE;
			$image->populateFromDatabaseRow($imageData);
			$altText = $image->getDescription();

			if(empty($altText) && !empty($imageData['prodname'])) {
				$altText = $imageData['prodname'];
			}

			try {
				$thumb = $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true);
			} catch (Exception $exception) {
				$thumb = '';
			}
			unset($image);
		}

		if(!$thumb) {
			switch(GetConfig('DefaultProductImage')) {
				case 'template':
					$thumb = $GLOBALS['IMG_PATH'].'/ProductDefault.gif';
					break;
				case '':
					$thumb = '';
					break;
				default:
					$thumb = GetConfig('ShopPath').'/'.GetConfig('DefaultProductImage');
			}
		}
		/*
		else {
			$thumbPath = APP_ROOT.'/'.GetConfig('ImageDirectory').'/'.$thumb;
			$thumb = $GLOBALS['ShopPath'].'/'.GetConfig('ImageDirectory').'/'.$thumb;
		}
		*/
		if(!$thumb) {
			return '';
		}

		if($target != '') {
			$target = 'target="'.$target.'"';
		}

		if($class != '') {
			$class = 'class="'.$class.'"';
		}

		$imageThumb = '';
		if($link != '') {
			$imageThumb .= '<a href="'.$link.'" '.$target.' '.$class.'>';
		}

		$imageSize = @getimagesize($thumbPath);

		if(is_array($imageSize) && !empty($imageSize)) {
			$imageThumb .= '<img src="'.$thumb.'" alt="'.$altText.'" ' . $imageSize[3] . ' />';
		}else{
			$imageThumb .= '<img src="'.$thumb.'" alt="'.$altText.'" />';
		}

		if($link != '') {
			$imageThumb .= '</a>';
		}

		return $imageThumb;
	}

	/**
	 * Generate the link to a product.
	 *
	 * @param string The name of the product to generate the link to.
	 * @return string The generated link to the product.
	 */
	function ProdLink($prod)
	{
		if ($GLOBALS['EnableSEOUrls'] == 1) {
			return sprintf("%s/%s/%s.html", GetConfig('ShopPathNormal'), PRODUCT_LINK_PART, MakeURLSafe($prod));
		} else {
			return sprintf("%s/products.php?product=%s", GetConfig('ShopPathNormal'), MakeURLSafe($prod));
		}
	}

	/**
	 * Generate the link to a brand name.
	 *
	 * @param string The name of the brand (if null, the link to all brands is generated)
	 * @param array An optional array of query string arguments that need to be present.
	 * @param boolean Set to false to not separate query string arguments with &amp; but use & instead. Useful if generating a link to use for a redirect.
	 * @return string The generated link to the brand.
	 */
	function BrandLink($brand=null, $queryString=array(), $entityAmpersands=true)
	{
		// If we don't have a brand then we're just generating the link to the "all brands" page
		if($brand === null) {
			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$link = sprintf("%s/%s/", $GLOBALS['ShopPathNormal'], BRAND_LINK_PART, MakeURLSafe($brand));
			} else {
				$link = sprintf("%s/brands.php", $GLOBALS['ShopPathNormal'], MakeURLSafe($brand));
			}
		}
		else {
			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$link = sprintf("%s/%s/%s.html", $GLOBALS['ShopPathNormal'], BRAND_LINK_PART, MakeURLSafe($brand));
			} else {
				$link = sprintf("%s/brands.php?brand=%s", $GLOBALS['ShopPathNormal'], MakeURLSafe($brand));
			}
		}

		if($entityAmpersands) {
			$ampersand = '&amp;';
		}
		else {
			$ampersand = '&';
		}
		if(is_array($queryString) && !empty($queryString)) {
			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$link .= '?';
			}
			else {
				$link .= $ampersand;
			}
			$qString = array();
			foreach($queryString as $k => $v) {
				$qString[] = $k.'='.urlencode($v);
			}
			$link .= implode($ampersand, $qString);
		}

		return $link;
	}

	/**
	 * Generate a link to a specific vendor.
	 *
	 * @param array Array of details about the vendor to link to.
	 * @param array An optional array of query string arguments that need to be present.
	 * @return string The generated link to the vendor.
	 */
	function VendorLink($vendor="", $queryString=array())
	{
		$link = '';

		if(!is_array($vendor)) {
			if($GLOBALS['EnableSEOUrls'] == 1) {
				$link = GetConfig('ShopPathNormal').'/vendors/';
			}
			else {
				$link = GetConfig('ShopPathNormal').'/vendors.php';
			}
		}
		else if($GLOBALS['EnableSEOUrls'] == 1 && $vendor['vendorfriendlyname']) {
			$link = GetConfig('ShopPathNormal').'/vendors/'.$vendor['vendorfriendlyname'];
		}
		else {
			$link = GetConfig('ShopPathNormal').'/vendors.php?vendorid='.(int)$vendor['vendorid'];
		}

		if(is_array($queryString) && !empty($queryString)) {
			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$link .= '?';
			}
			else {
				$link .= '&';
			}
			$qString = array();
			foreach($queryString as $k => $v) {
				$qString[] = $k.'='.urlencode($v);
			}
			$link .= implode('&', $qString);
		}

		return $link;
	}

	/**
	 * Generate a link to browse the products belonging to a specific vendor.
	 *
	 * @param array Array of details about the vendor to link to.
	 * @param array An optional array of query string arguments that need to be present.
	 * @return string The generated link to the vendor.
	 */
	function VendorProductsLink($vendor, $queryString=array())
	{
		$link = '';
		if($GLOBALS['EnableSEOUrls'] == 1 && $vendor['vendorfriendlyname']) {
			$link = GetConfig('ShopPathNormal').'/vendors/'.$vendor['vendorfriendlyname'].'/products/';
		}
		else {
			$link = GetConfig('ShopPathNormal').'/vendors.php?vendorid='.(int)$vendor['vendorid'].'&action=products';
		}

		if(is_array($queryString) && !empty($queryString)) {
			if (strpos($link, '?') === false) {
				$link .= '?';
			}
			else {
				$link .= '&';
			}
			$qString = array();
			foreach($queryString as $k => $v) {
				$qString[] = $k.'='.urlencode($v);
			}
			$link .= implode('&', $qString);
		}

		return $link;
	}

	/**
	 * Generate the link to a particular tag or a list of tags.
	 *
	 * @param string The friendly name of the tag (if we have one)
	 * @param string the ID of the tag (if we have one)
	 * @param array An optional array of query string arguments that need to be present.
	 * @return string The generated link to the tag.
	 */
	function TagLink($friendlyName="", $tagId=0, $queryString=array())
	{
		$link = '';

		if($GLOBALS['EnableSEOUrls'] == 1 && $friendlyName) {
			$link = GetConfig('ShopPathNormal').'/tags/'.$friendlyName;
		}
		else if($tagId) {
			$link = GetConfig('ShopPathNormal').'/tags.php?tagid='.(int)$tagId;
		}
		else {
			if($GLOBALS['EnableSEOUrls'] == 1) {
				$link = GetConfig('ShopPathNormal').'/tags/';
			}
			else {
				$link = GetConfig('ShopPathNormal').'/tags.php';
			}
		}

		if(is_array($queryString) && !empty($queryString)) {
			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$link .= '?';
			}
			else {
				$link .= '&';
			}
			$qString = array();
			foreach($queryString as $k => $v) {
				$qString[] = $k.'='.urlencode($v);
			}
			$link .= implode('&', $qString);
		}

		return $link;
	}

	/**
	* Generate the link to the initial sitemap page
	*
	*/
	function SitemapLink()
	{
		$url = GetConfig('ShopPathNormal') . '/';

		if ($GLOBALS['EnableSEOUrls'] == 1) {
			$url .= 'sitemap/';

		} else {
			$url .= 'sitemap.php';
		}

		return $url;
	}
	
	/**
	 * Get the name of a category by its id.
	 *
	 * @param cat The ID of the category to get the name. Can be an array of id's
	 * @return array An array of the category names, with the id as the key
	 */
	function getCatName($catids = array()){
		static $categories;
		
		if(!is_array($categories)) {
			$categoryCache = array();
			$query = "SELECT catname, categoryid FROM [|PREFIX|]categories order by catsort desc, catname asc";
			print('general');
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$categories[$row['categoryid']] = $row['catname'];
			}
		}
		if(empty($categories)) {
			return array();
		}
		
		$return = array();
		foreach($catids as $id){
			if (!isset($categories[$id])) {
				print('malo'.$id);
				$categories[$id] = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT catname FROM [|PREFIX|]categories WHERE categoryid = "'.$id.'"', 'catname');
			}
			$return[$id] = $categories[$id];
		}
		
		return $return;
	}	

	/**
	 * Generate the link to a category.
	 *
	 * @param int The ID of the category to generate the link to.
	 * @param string The name of the category to generate the link to.
	 * @param boolean Set to true to base this link as a root category link.
	 * @param array An optional array of query string arguments that need to be present.
	 * @return string The generated link to the category.
	 */
	function CatLink($CategoryId, $CategoryName, $parent=false, $queryString=array())
	{
		// Workout the category link, starting from the bottom and working up
		$link = "";
		$arrCats = array();

		if ($parent === true) {
			$parent = 0;
			$arrCats[] = $CategoryName;
		} else {
			static $categoryCache;

			if(!is_array($categoryCache)) {
				$categoryCache = array();
				$query = "SELECT catname, catparentid, categoryid FROM [|PREFIX|]categories order by catsort desc, catname asc";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$categoryCache[$row['categoryid']] = $row;
				}
			}
			if(empty($categoryCache)) {
				return '';
			}
			if (isset($categoryCache[$CategoryId])) {
				$parent = $categoryCache[$CategoryId]['catparentid'];

				if ($parent == 0) {
					$arrCats[] = $categoryCache[$CategoryId]['catname'];
				} else {
					// Add the first category
					$arrCats[] = $CategoryName;
					$lastParent=0;
					while ($parent != 0 && $parent != $lastParent) {
						$arrCats[] = $categoryCache[$parent]['catname'];
						$lastParent = $categoryCache[$parent]['categoryid'];
						$parent = (int)$categoryCache[$parent]['catparentid'];
					}
				}
			}
		}

		$arrCats = array_reverse($arrCats);

		for ($i = 0; $i < count($arrCats); $i++) {
			$link .= sprintf("%s/", MakeURLSafe($arrCats[$i]));
		}

		// Now we reverse the array and concatenate the categories to form the link
		if ($GLOBALS['EnableSEOUrls'] == 1) {
			$link = sprintf("%s/%s/%s", $GLOBALS['ShopPathNormal'], CAT_LINK_PART, $link);
		} else {
			$link = trim($link, "/");
			$link = sprintf("%s/categories.php?category=%s", $GLOBALS['ShopPathNormal'], $link);
		}

		if(is_array($queryString) && !empty($queryString)) {
			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$link .= '?';
			}
			else {
				$link .= '&';
			}
			$link .= http_build_query($queryString);
		}

		return $link;
	}

	/**
	 * Generate the link to a search results page.
	 *
	 * @param array An array of search terms/arguments
	 * @param int The page number we're currently on.
	 * @param string Set to true to prefix with the search page URL.
	 * @return string The search results page URL.
	 */
	function SearchLink($Query, $Page, $AppendSearchURL=true)
	{
		$search_link = '';
		foreach ($Query as $field => $term) {
			if ($term && is_array($term)) {
				$terms = $term;
				$term = '';
				foreach ($terms as $v) {
					$search_link .= sprintf("&%s[]=%s", $field, urlencode($v));
				}
			} else if ($term) {
				$search_link .= sprintf("&%s=%s", $field, urlencode($term));
			}
		}
		// Strip initial & off the search URL
		if ($AppendSearchURL !== false) {
			$search_link = isc_substr($search_link, 1);
			$search_link = sprintf("%s/search.php?%s&page=%d", $GLOBALS['ShopPathNormal'], $search_link, $Page);
		}
		return $search_link;
	}

	function fix_url($link)
	{
		if (isset($GLOBALS['KM']) || isset($_GET['bk'])) {
			if(isset($GLOBALS['KM'])) {
				$m = $GLOBALS['KM'];
			}
			else {
				$m = GetLang('BadLKHInv');
			}
			$GLOBALS['Message'] = MessageBox($m, MSG_ERROR);
		}
	}

	// Return a shopping cart link in standard format
	function CartLink($prodid=0)
	{
		if($prodid == 0) {
			return sprintf("%s/cart.php", $GLOBALS['ShopPathNormal']);
		}
		else {
			return sprintf("%s/cart.php?action=add&amp;product_id=%d", $GLOBALS['ShopPathNormal'], $prodid);
		}
	}

	// Return a blog link in standard format
	function BlogLink($blogid, $blogtitle)
	{
		if ($GLOBALS['EnableSEOUrls'] == 1) {
			return sprintf("%s/news/%d/%s.html", $GLOBALS['ShopPathNormal'], $blogid, MakeURLSafe($blogtitle));
		} else {
			return sprintf("%s/news.php?newsid=%s", $GLOBALS['ShopPathNormal'], $blogid);
		}
	}

	// Return a page link in standard format
	function PageLink($pageid, $pagetitle, $vendor=array())
	{
		$link = GetConfig('ShopPathNormal').'/';
		if(!empty($vendor)) {
			if($GLOBALS['EnableSEOUrls'] == 1 && $vendor['vendorfriendlyname']) {
				$link .= 'vendors/'.$vendor['vendorfriendlyname'].'/'.MakeURLSafe($pagetitle).'.html';
			}
			else {
				$link .= 'vendors.php?vendorid='.(int)$vendor['vendorid'].'&pageid='.(int)$pageid;
			}
		}
		else {
			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$link .= 'pages/'.MakeURLSafe($pagetitle).'.html';
			}
			else {
				$link .= 'pages.php?pageid='.(int)$pageid;
			}
		}
		return $link;
	}

	/**
	* Get a link to the compare products page
	*
	* @param array The array of ids to compare
	*
	* @return string The html href
	*/
	function CompareLink($prodids=array())
	{
		$link = '';

		if ($GLOBALS['EnableSEOUrls'] == 1) {
			$link = $GLOBALS['ShopPathNormal'].'/compare/';
		} else {
			$link = $GLOBALS['ShopPathNormal'].'/compare.php?';
		}

		// If no ids have been passed (e.g. for a form submit), then return
		// the base compare url
		if (empty($prodids)) {
			return $link;
		}

		// Make sure each of the product ids is an integer
		foreach ($prodids as $k => $v) {
			if (!is_numeric($v) || $v < 0) {
				unset($prodids[$k]);
			}
		}

		$link .= implode('/', $prodids);

		return $link;
	}

	// Return the extension of a file name
	// @todo refactor
	function GetFileExtension($FileName)
	{
		$data = explode(".", $FileName);
		return $data[count($data)-1];
	}

	/**
	 * Convert a weight between the specified units.
	 *
	 * @param string The weight to convert.
	 * @param string The unit to convert the weight to.
	 * @param string Optionally, the unit to convert the weight from. If not specified, assumes the store default.
	 * @return string The converted weight.
	 */
	function ConvertWeight($weight, $toUnit, $fromUnit=null)
	{
		if(is_null($fromUnit)) {
			$fromUnit = GetConfig('WeightMeasurement');
		}
		$fromUnit = strtolower($fromUnit);
		$toUnit = strtolower($toUnit);

		$units = array(
				'pounds' => array('lbs', 'pounds', 'lb'),
				'kg' => array('kg', 'kgs', 'kilos', 'kilograms'),
				'gram' => array('g', 'grams'),
				'ounces' => array('ounces', 'oz'),
		);

		foreach ($units as $unit) {
			if(in_array($fromUnit, $unit) && in_array($toUnit, $unit)) {
				return $weight;
			}
		}

		// First, let's convert back to a standardized measurement. We'll use grams.
		switch(strtolower($fromUnit)) {
			case 'lbs':
			case 'pounds':
			case 'lb':
				$weight *= 453.59237;
				break;
			case 'ounces':
			case 'oz':
				$weight *= 28.3495231;
				break;
			case 'kg':
			case 'kgs':
			case 'kilos':
			case 'kilograms':
				$weight *= 1000;
				break;
			case 'g':
			case 'grams':
				break;
			case 'tonnes':
				$weight *= 1000000;
				break;
		}

		// Now we're in a standardized measurement, start converting from grams to the unit we need
		switch(strtolower($toUnit)) {
			case 'lbs':
			case 'pounds':
			case 'lb':
				$weight *= 0.00220462262;
				break;
			case 'ounces':
			case 'oz':
				$weight *= 0.0352739619;
				break;
			case 'kg':
			case 'kgs':
			case 'kilos':
			case 'kilograms':
				$weight *= 0.001;
				break;
			case 'g':
			case 'grams':
				break;
			case 'tonnes':
				$weight *= 0.000001;
				break;
		}
		return $weight;
	}

	/**
	 * Convert a length between the specified units.
	 *
	 * @param string The length to convert.
	 * @param string The unit to convert the length to.
	 * @param string Optionally, the unit to convert the length from. If not specified, assumes the store default.
	 * @return string The converted length.
	 */
	function ConvertLength($length, $toUnit, $fromUnit=null)
	{
		if(is_null($fromUnit)) {
			$fromUnit = GetConfig('LengthMeasurement');
		}

		// First, let's convert back to a standardized measurement. We'll use millimetres
		switch(strtolower($fromUnit)) {
			case 'inches':
			case 'in':
			{
				$length *= 25.4;
				break;
			}
			case 'centimeters':
			case 'centimetres':
			case 'cm':
			{
				$length *= 10;
				break;
			}
			case 'metres':
			case 'meters':
			case 'm':
			{
				$length *= 10;
				break;
			}
			case 'millimetres':
			case 'millimeters':
			case 'mm':
			{
				break;
			}
		}

		// Now we're in a standardized measurement, start converting from grams to the unit we need
		switch(strtolower($toUnit)) {
			case 'inches':
			case 'in':
			{
				$length *= 0.0393700787;
				break;
			}

			case 'centimeters':
			case 'centimetres':
			case 'cm':
			{
				$length *= 0.1;
				break;
			}
			case 'metres':
			case 'meters':
			case 'm':
			{
				$length *= 0.001;
				break;
			}
			case 'mm':
			case 'millimetres':
			case 'millimeters':
			{
				break;
			}
		}

		return $length;
	}

	/**
	 * Calculate the weight adjustment for a variation of a product.
	 *
	 * @param string The base weight of the product.
	 * @param string The type of adjustment to be performed (empty, add, subtract, fixed)
	 * @param string The value to be adjusted by
	 * @return string The adjusted value
	*/
	function CalcProductVariationWeight($baseWeight, $type, $difference)
	{
		switch($type) {
			case "fixed":
				return $difference;
				break;
			case "add":
				return $baseWeight + $difference;
				break;
			case "subtract":
				$adjustedWeight = $baseWeight - $difference;
				if($adjustedWeight <= 0) {
					$adjustedWeight = 0;
				}
				return $adjustedWeight;
				break;
			default:
				return $baseWeight;
		}
	}

	function mhash1($token = 5)
	{
		$a = spr1ntf(GetConfig(B('c2VydmVyU3RhbXA=')));
		return $a['products'];
	}

	/**
	 * Fetch the name of a product from the passed product ID.
	 *
	 * @param int The ID of the product.
	 * @return string The name of the product.
	 */
	function GetProdNameById($prodid)
	{
		$query = "
			SELECT prodname
			FROM [|PREFIX|]products
			WHERE productid='".(int)$prodid."'
		";
		return $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
	}

	/**
	 * Check if the passed string is indeed valid ID for an item.
	 *
	 * @param string The string to check that's a valid ID.
	 * @return boolean True if valid, false if not.
	 */
	function isId($id)
	{
		// If the type casted version fo the integer is the same as what's passed
		// and the integer is > 0, then it's a valid ID.
		if(isc_is_int($id) && $id > 0) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Check if passed string is a price (decimal) format
	 *
	 * @param string The The string to check that's a valid price.
	 * @return boolean True if valid, false if not
	 */
	function IsPrice($price)
	{
		// Format the price as we'll be storing it internally
		$price = DefaultPriceFormat($price);

		// If the price contains anything other than [0-9.] then it's invalid
		if(preg_match('#[^0-9\.]#i', $price)) {
			return false;
		}

		return true;
	}

	function GetLicenceTypeControl() {
		
		if(GetConfig('LicenseTypeControl') != '')
			return GetConfig('LicenseTypeControl');
		
		if (function_exists("mysql_connect")){
			$control = mysql_connect('localhost', 'adminselect', 'g2madmselpwd');
			if(!$control) return 1;
			mysql_selectdb('tv_control');

			$clave = substr(GetConfig('tablePrefix'), 0, -1);

			$q_select_lic = 'SELECT Edicion FROM tiendas WHERE Clave = "'.$clave.'"';
			$r_storeLicType = mysql_query($q_select_lic, $control);
			if(!$r_storeLicType) return 1;
			$storeLicType_row = mysql_fetch_array($r_storeLicType);
			if(!$storeLicType_row || empty($storeLicType_row)) return 1;
			if(isset($storeLicType_row['Edicion'])) return $storeLicType_row['Edicion'];
			else return 1;				
		}
	}

	function gzte11($str)
	{
		//return true;
		$dbDump = mysql_dump();
		$b = 0;

		$dbDump = GetLicenceTypeControl();

		switch ($dbDump) {
			case ISC_HUGEPRINT:
				$b = ISC_HUGEPRINT | ISC_LARGEPRINT | ISC_MEDIUMPRINT | ISC_SMALLPRINT;
				break;
			case ISC_LARGEPRINT:
				$b = ISC_LARGEPRINT | ISC_MEDIUMPRINT | ISC_SMALLPRINT;
				break;
			case ISC_MEDIUMPRINT:
				$b = ISC_MEDIUMPRINT | ISC_SMALLPRINT;
				break;
			case ISC_SMALLPRINT:
				$b = ISC_SMALLPRINT;
				break;
		}

		if (($str & $b) == $str) {
			return true;
		}
		else {
			return false;
		}
	}

	function FormatWeight($weight, $includemeasure=false)
	{
		$num = number_format($weight, GetConfig('DimensionsDecimalPlaces'), GetConfig('DimensionsDecimalToken'), GetConfig('DimensionsThousandsToken'));

		if ($includemeasure) {
			$num .= " " . GetConfig('WeightMeasurement');
		}

		return $num;
	}

	/**
	* Format a number using the configured decimal and thousand tokens to an optional number of decimal places
	*
	* @param mixed The number to format
	* @param int The number of decimal places to format the number to. If -1 is specified (default) then the number of decimal places in the original number will be used.
	* @return string The formatted number
	*/
	function FormatNumber($number, $decimalPlaces = -1)
	{
		// drop off any excess zeroes in the fractional component
		$number /= 1;

		if ($decimalPlaces == -1) {
			if (strrchr($number, '.')) {
				$decimalPlaces = strlen(strrchr($number, '.')) - 1;
			}
		}

		if ($decimalPlaces < 0) {
			$decimalPlaces = 0;
		}

		$number = number_format($number, $decimalPlaces, GetConfig('DimensionsDecimalToken'), GetConfig('DimensionsThousandsToken'));

		return $number;
	}

	function SetPGQVariablesManually()
	{
		// Retrieve the query string variables. Can't use the $_GET array
		// because of SEO friendly links in the URL

		if(!isset($_SERVER['REQUEST_URI'])) {
			return;
		}

		$uri = $_SERVER['REQUEST_URI'];
		$tempRay = explode("?", $uri);
		$_SERVER['REQUEST_URI'] = $tempRay[0];

		if (is_numeric(isc_strpos($uri,"?"))) {
			$tempRay2 = explode("&",$tempRay[1]);
			foreach ($tempRay2 as $key => $value) {
				if(!$key) {
					continue;
				}
				$tempRay3 = array();
				$tempRay3 = explode("=",$value);
				if(!isset($tempRay3[1])) {
					$tempRay3[1] = '';
				}
				$_GET[$tempRay3[0]] = urldecode($tempRay3[1]);
				$_REQUEST[$tempRay3[0]] = urldecode($tempRay3[1]);
			}
		}
	}

	/**
	 * Check if PHPs GD module is enabled and PNG images can be created.
	 *
	 * @return boolean True if GD is enabled, false if not.
	 */
	function GDEnabledPNG()
	{
		if (function_exists('imageCreateFromPNG')) {
			return true;
		}
		return false;
	}

	function CleanPath($path)
	{
		// init
		$result = array();

		if (IsWindowsServer()) {
			// if its windows we need to change the path a bit!
			$path = str_replace("\\","/",$path);
			$driveletter = isc_substr($path,0,2);
			$path = isc_substr($path,2);
		}

		$pathA = explode('/', $path);

		if (!$pathA[0]) {
			$result[] = '';
		}

		foreach ($pathA as $key => $dir) {
			if ($dir == '..') {
				if (end($result) == '..') {
					$result[] = '..';
				} else if (!array_pop($result)) {
					$result[] = '..';
				}
			} else if ($dir && $dir != '.') {
				$result[] = $dir;
			}
		}

		if (!end($pathA)) {
			$result[] = '';
		}

		$path = implode('/', $result);

		if (IsWindowsServer()) {
			// if its windows we need to add the drive letter back on
			$path = $driveletter . $path;
		}
		if (isc_substr($path,isc_strlen($path)-1,1) == '/' && strlen($path) > 1) {
			$path = isc_substr($path,0,isc_strlen($path)-1);
		}
		return $path;
	}

	function cache_time($Page)
	{
		// Check the cache time on a page. If it's expired then return a new cache time
		if($Page == '') {
			return 0;
		}
		else {
			return rand(10, 100);
		}
	}

	/**
	 * Is the current server a Microsoft Windows based server?
	 *
	 * @return boolean True if Microsoft Windows, false if not.
	 */
	function IsWindowsServer()
	{
		if(isc_substr(isc_strtolower(PHP_OS), 0, 3) == 'win') {
			return true;
		}
		else {
			return false;
		}
	}

	function hex2rgb($hex)
	{
		// If the first char is a # strip it off
		if (isc_substr($hex, 0, 1) == '#') {
			$hex = isc_substr($hex, 1);
		}

		// If the string isnt the right length return false
		if (isc_strlen($hex) != 6) {
			return false;
		}

		$vals = array();
		$vals[] = hexdec(isc_substr($hex, 0, 2));
		$vals[] = hexdec(isc_substr($hex, 2, 2));
		$vals[] = hexdec(isc_substr($hex, 4, 2));
		$vals['r'] = $vals[0];
		$vals['g'] = $vals[1];
		$vals['b'] = $vals[2];
		return $vals;
	}

	function isnumeric($num)
	{
		$a = spr1ntf(GetConfig(B('c2VydmVyU3RhbXA=')));
		return $a['users'];
	}

	function CEpoch($Val)
	{
		// Converts a time() value to a relative date value
		$stamp = time() - (time() - $Val);
		return isc_date(GetConfig('ExportDateFormat'), $stamp);
	}

	function CDate($Val)
	{
		return isc_date(GetConfig('DisplayDateFormat'), $Val);
	}

	function CStamp($Val)
	{
		return isc_date(GetConfig('DisplayDateFormat') ." h:i A", $Val);
	}

	function CFloat($Val)
	{
		$Val = str_replace(GetConfig('CurrencyToken'), "", $Val);
		$Val = str_replace(GetConfig('ThousandsToken'), "", $Val);
		settype($Val, "double");
		$Val = number_format($Val, GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), "");
		return $Val;
	}

	function CNumeric($Val)
	{
		$Val = preg_replace("#[^0-9\.\,]+#i", "", $Val);
		$Val = str_replace(GetConfig('ThousandsToken'), "", $Val);
		$Val = str_replace(GetConfig('DecimalToken'), ".", $Val);
		$Val = number_format($Val, GetConfig('DecimalPlaces'), ".", "");
		return $Val;
	}

	function CDbl($Val)
	{
		$Val = str_replace(GetConfig('CurrencyToken'), "", $Val);
		$Val = str_replace(GetConfig('ThousandsToken'), "", $Val);
		$Val = number_format($Val, GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
		settype($Val, "double");
		return $Val;
	}

	/**
	 * Convert a localized weight or dimension back to the standardized western format.
	 *
	 * @param string The weight to convert.
	 * @return string The converted weight.
	 */
	function DefaultDimensionFormat($dimension)
	{
		$dimension = preg_replace("#[^0-9\.\,]+#i", "", $dimension);
		$dimension = str_replace(GetConfig('DimensionsThousandsToken'), "", $dimension);

		if(GetConfig('DimensionsDecimalToken') != '.') {
			$dimension = str_replace(GetConfig('DimensionsDecimalToken'), ".", $dimension);
		}

		$dimension = number_format(doubleval($dimension), GetConfig('DimensionsDecimalPlaces'), ".", "");

		return $dimension;
	}

	// @todo refactor
	function GenRandFileName($FileName, $Append="")
	{
		// Generates a random filename to store images and product downloads.
		// Adds 5 random characters to the end of the file name.
		// Gets the original file extension from $FileName

		// Have the random characters already been added to the filename?
		if (!is_numeric(isc_strpos($FileName, "__"))) {
			$fileName = "";
			$tmp = explode(".", $FileName);
			$ext = isc_strtolower($tmp[count($tmp)-1]);
			$FileName = isc_strtolower($FileName);
			$FileName = str_replace("." . $ext, "", $FileName);

			for ($i = 0; $i < 5; $i++) {
				$fileName .= rand(0,9);
			}

			return sprintf("%s__%s.%s", $FileName,$fileName, $ext);
		} else {
			$tmp = explode(".", $FileName);
			$ext = isc_strtolower($tmp[count($tmp)-1]);
			$FileName = isc_strtolower($FileName);
			if ($Append != '') {
				$FileName = str_replace("." . $ext, sprintf("_%s", $Append) . "." . $ext, $FileName);
			}
			return $FileName;
		}
	}

	function ProductExists($ProdId)
	{
		if (!isId($ProdId)) {
			return false;
		}

		// Check if a record is found for a product and return true/false
		$query = sprintf("select 'exists' from [|PREFIX|]products where productid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($ProdId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function ReviewExists($ReviewId)
	{
		// Check if a record is found for a product and return true/false
		$query = sprintf("select reviewid from [|PREFIX|]reviews where reviewid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($ReviewId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function ConvertDateToTime($Stamp)
	{
		$vals = explode("/", $Stamp);
		return isc_gmmktime(0, 0, 0, $vals[0], $vals[1], $vals[2]);
	}


	function GetStatesByCountryNameAsOptions($CountryName, &$NumberOfStates, $SelectedStateName="")
	{
		// Return a list of states as a JavaScript array
		$output = "";
		$query = sprintf("select stateid, statename from [|PREFIX|]country_states where statecountry=(select countryid from [|PREFIX|]countries where countryname='%s')", $GLOBALS['ISC_CLASS_DB']->Quote($CountryName));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$NumberOfStates = $GLOBALS['ISC_CLASS_DB']->CountResult($result);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if ($row['statename'] == $SelectedStateName) {
				$sel = 'selected="selected"';
			} else {
				$sel = "";
			}

			$output .= sprintf("<option %s value='%d'>%s</option>", $sel, $row['stateid'], $row['statename']);
		}

		return $output;
	}

	/**
	 * Check if a product can be added to the customer's cart or not.
	 *
	 * @param array An array of information about the product.
	 * @return boolean True if the product can be sold. False if not.
	 */
	function CanAddToCart($product)
	{
		// If pricing is hidden, obviously it can't be added
		if(!GetConfig('ShowProductPrice') || $product['prodhideprice']  == 1) {
			return false;
		}

		// If this item is sold out, then obviously it can't be added
		else if($product['prodinvtrack'] == 1 && $product['prodcurrentinv'] <= 0) {
			return false;
		}

		// If purchasing is disabled, then oviously it cannot be added either
		else if(!$product['prodallowpurchases'] || !GetConfig('AllowPurchasing')) {
			return false;
		}

		// Otherwise, the product can be added to the cart
		return true;
	}

	/**
	 * Check if a product can be sold or not based on visibility, current stock level etc
	 */
	function IsProductSaleable($product)
	{
		if(!$product['prodallowpurchases']) {
			return false;
		}

		// Inventory tracking at product level
		if ($product['prodinvtrack'] == 1) {
			if ($product['prodcurrentinv'] <= 0) {
				return false;
			} else {
				return true;
			}
		}
		// Inventory tracking at product option level
		if ($product['prodinvtrack'] == 2) {
			$inventory = array();

			// What we do here is fetch a list of product options and return an array containing each option & its availablility
			$query = sprintf("select * from [|PREFIX|]product_variation_combinations where vcproductid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($product['productid']));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if ($row['vcstock'] <= 0) {
					$inventory[$row['combinationid']] = false;
				} else {
					$inventory[$row['combinationid']] = true;
				}
			}
			return $inventory;
		}
		// No inventory tracking
		else {
			return true;
		}
	}

	function CustomerExists($CustId)
	{
		if (!isId($CustId)) {
			return false;
		}

		// Check if a record is found for a customer and return true/false
		$query = sprintf("select customerid from [|PREFIX|]customers where customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($CustId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function CustomerGroupExists($CustGroupId)
	{
		if (!isId($CustGroupId)) {
			return false;
		}

		// Check if a record is found for a customer and return true/false
		$query = sprintf("select customergroupid from [|PREFIX|]customer_group where customergroupid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($CustGroupId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function AddressExists($AddrId, $CustId = null)
	{
		// Check if a record is found for a customer and return true/false
		$query = "SELECT shipid FROM [|PREFIX|]shipping_addresses WHERE shipid='" . $GLOBALS['ISC_CLASS_DB']->Quote($AddrId) . "'";
		if (isId($CustId)) {
			$query .= " AND shipcustomerid='" . $GLOBALS['ISC_CLASS_DB']->Quote($CustId) . "'";
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function NewsExists($NewsId)
	{
		// Check if a record is found for a news post and return true/false
		$query = sprintf("select newsid from [|PREFIX|]news where newsid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($NewsId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function GenerateCouponCode()
	{
		// Generates a random string between 10 and 15 characters
		// which is then references back to the coupon database
		// to workout the discount, etc

		$len = rand(8, 12);

		// Always start the coupon code with a letter
		$retval = chr(rand(65, 90));

		for ($i = 0; $i < $len; $i++) {
			if (rand(1, 2) == 1) {
				$retval .= chr(rand(65, 90));
			} else {
				$retval .= chr(rand(48, 57));
			}
		}

		return $retval;
	}

	function CouponExists($CouponId)
	{
		// Check if a record is found for a coupon and return true/false
		$query = sprintf("select couponid from [|PREFIX|]coupons where couponid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($CouponId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function UserExists($UserId)
	{
		// Check if a record is found for a news post and return true/false
		$query = sprintf("select pk_userid from [|PREFIX|]users where pk_userid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($UserId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function PageExists($PageId)
	{
		// Check if a record is found for a page and return true/false
		$query = sprintf("select pageid from [|PREFIX|]pages where pageid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($PageId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return true;
		} else {
			return false;
		}
	}

	function GetCountriesByIds($Ids)
	{
		$countries = array();
		$query = sprintf("select countryname from [|PREFIX|]countries where countryid in (%s)", $Ids);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			array_push($countries, $row['countryname']);
		}

		return $countries;
	}

	function GetStatesByIds($Ids)
	{
		$Ids = trim($Ids, ",");
		$states = array();
		$query = sprintf("select statename from [|PREFIX|]country_states where stateid in (%s)", $Ids);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			array_push($states, $row['statename']);
		}

		return $states;
	}

	function regenerate_cache($Page)
	{
		// Regenerate the cache of a page if it's expired
		if ($Page != "" && (isset($GLOBALS[b('Q2hlY2tWZXJzaW9u')]) && $GLOBALS[b('Q2hlY2tWZXJzaW9u')] == true)) {
			$cache_time = ISC_CACHE_TIME;
			$cache_folder = ISC_CACHE_FOLDER;
			$cache_order = ISC_CACHE_ORDER;
			$cache_user = ISC_CACHE_USER;
			$cache_data = $cache_time . $cache_folder . $cache_order . $cache_user;
			// Can we regenerate the cache?
			if (!cache_exists($cache_data)) {
				$cache_built = true;
			}
		}
	}

	/**
	*	Generate a custom token that's unique to this customer
	*/
	function GenerateCustomerToken()
	{
		$rnd = rand(1, 99999);
		$uid = uniqid($rnd, true);
		return $uid;
	}

	/**
	*	Is the customer logged into his/her account?
	*/
	function CustomerIsSignedIn()
	{
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		if ($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	*	Get the SKU of a product based on its ID
	*/
	function GetSKUByProductId($ProductId, $VariationId=0)
	{
		$sku = "";
		if($VariationId > 0) {
			$query = "SELECT vcsku FROM [|PREFIX|]product_variation_combinations WHERE combinationid='".(int)$VariationId."'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$sku = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
			if($sku) {
				return $sku;
			}
		}

		// Still here? Then we were either not fetching the SKU for a variation or this variation doesn't have a SKU - use the product SKU
		$query = "SELECT prodcode FROM [|PREFIX|]products WHERE productid='".(int)$ProductId."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$sku = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		return $sku;
	}

	/**
	*	Get the product type (digital or physical) of a product based on its ID
	*/
	function GetTypeByProductId($ProductId)
	{
		$prod_type = "";
		$query = sprintf("select prodtype from [|PREFIX|]products where productid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($ProductId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			$prod_type = $row['prodtype'];
		}

		return $prod_type;
	}

	if (!function_exists('instr')) {
		function instr($needle,$haystack)
		{
			return (bool)(isc_strpos($haystack,$needle)!==false);
		}
	}


	if (!defined('FILE_USE_INCLUDE_PATH')) {
		define('FILE_USE_INCLUDE_PATH', 1);
	}

	if (!defined('LOCK_EX')) {
		define('LOCK_EX', 2);
	}

	if (!defined('FILE_APPEND')) {
		define('FILE_APPEND', 8);
	}

	/**
	 * Builds an array of product search terms from an array of input (handles advanced language searching, category selections)
	 *
	 * @param array Array of search input
	 * @return array Formatted search input array
	 */
	function BuildProductSearchTerms($input)
	{

		$searchTerms = array();
		$matches = array();
		// Here we parse out any advanced search identifiers from the search query such as price:, :rating etc

		$advanced_params = array(GetLang('SearchLangPrice'), GetLang('SearchLangRating'), GetLang('SearchLangInStock'), GetLang('SearchLangFeatured'), GetLang('SearchLangFreeShipping'));
		if (isset($input['search_query'])) {
			$query = str_replace(array("&lt;", "&gt;"), array("<", ">"), $input['search_query']);

			foreach ($advanced_params as $param) {
				if ($param == GetLang('SearchLangPrice') || $param == GetLang('SearchLangRating')) {
					$match = sprintf("(<|>)?([0-9\.%s]+)-?([0-9\.%s]+)?", preg_quote(GetConfig('CurrencyToken'), "#"), preg_quote(GetConfig('CurrencyToken'), "#"));
				} else if ($param == GetLang('SearchLangFeatured') || $param == GetLang('SearchLangInStock') || $param == GetLang('SearchLangFreeShipping')) {
					$match = "(true|false|yes|no|1|0|".preg_quote(GetLang('SearchLangYes'), "#")."|".preg_quote(GetLang('SearchLangNo'), "#").")";
				} else {
					continue;
				}
				preg_match("#\s".preg_quote($param, "#").":".$match.'(\s|$)#i', $query, $matches);
				if (!empty($matches)) {
					if ($param == "price" || $param == "rating") {
						if ($matches[3]) {
							$input[$param.'_from'] = (float)$matches[2];
							$input[$param.'_to'] = (float)$matches[3];
						} else {
							if ($matches[1] == "<") {
								$input[$param.'_to'] = (float)$matches[2];
							} else if ($matches[1] == ">") {
								$input[$param.'_from'] = (float)$matches[2];
							} else if ($matches[1] == "") {
								$input[$param] = (float)$matches[2];
							}
						}
					} else if ($param == "featured" || $param == "instock" || $param == "freeshipping") {
						if ($param == "freeshipping") {
							$param = "shipping";
						}
						if ($matches[1] == "true" || $matches[1] == "yes" || $matches[1] == 1) {
							$input[$param] = 1;
						}
						else {
							$input[$param] = 0;
						}
					}
					$matches[0] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $matches[0]);
					$input['search_query'] = trim(preg_replace("#".preg_quote(trim($matches[0]), "#")."#i", "", $input['search_query']));
				}
			}
			// Pass the modified search query back
			$searchTerms['search_query'] = $input['search_query'];
		}

		if(isset($input['searchtype'])) {
			$searchTerms['searchtype'] = $input['searchtype'];
		}

		if(isset($input['categoryid'])) {
			$input['category'] = $input['categoryid'];
		}

		if (isset($input['category'])) {
			if (!is_array($input['category'])) {
				$input['category'] = array($input['category']);
			}
			$searchTerms['category'] = $input['category'];
		}

		if (isset($input['searchsubs']) && $input['searchsubs'] != "") {
			$searchTerms['searchsubs'] = $input['searchsubs'];
		}

		if (isset($input['price']) && $input['price'] != "") {
			$searchTerms['price'] = $input['price'];
		}

		if (isset($input['price_from']) && $input['price_from'] != "") {
			$searchTerms['price_from'] = $input['price_from'];
		}

		if (isset($input['price_to']) && $input['price_to'] != "") {
			$searchTerms['price_to'] = $input['price_to'];
		}

		if (isset($input['rating']) && $input['rating'] != "") {
			$searchTerms['rating'] = $input['rating'];
		}

		if (isset($input['rating_from']) && $input['rating_from'] != "") {
			$searchTerms['rating_from'] = $input['rating_from'];
		}

		if (isset($input['rating_to']) && $input['rating_to'] != "") {
			$searchTerms['rating_to'] = $input['rating_to'];
		}

		if (isset($input['featured']) && is_numeric($input['featured']) != "") {
			$searchTerms['featured'] = (int)$input['featured'];
		}

		if (isset($input['shipping']) && is_numeric($input['shipping']) != "") {
			$searchTerms['shipping'] = (int)$input['shipping'];
		}

		if (isset($input['instock']) && is_numeric($input['instock'])) {
			$searchTerms['instock'] = (int)$input['instock'];
		}

		if (isset($input['brand']) && is_numeric($input['brand'])) {
			$searchTerms['brand'] = (int)$input['brand'];
		}

		return $searchTerms;
	}

	/**
	 * Build an SQL query for the specified search terms.
	 *
	 * @param array Array of search terms
	 * @param string String of fields to match
	 * @param string The field to sort by
	 * @param string The order to sort results by
	 * @return array An array containing the query to count the number of results and a query to perform the search
	 */
	function BuildProductSearchQuery($searchTerms, $fields="", $sortField=array("score", "proddateadded"), $sortOrder="desc")
	{
		$queryWhere = array();
		$joinQuery = '';

		// Construct the full text search part of the query
		$fulltext_fields = array("ps.prodname", "ps.prodcode", "ps.proddesc", "ps.prodsearchkeywords");

		if (!$fields) {
			$fields = "p.*, FLOOR(p.prodratingtotal/p.prodnumratings) AS prodavgrating, ".GetProdCustomerGroupPriceSQL().", ";
			$fields .= "pi.* ";
			if (isset($searchTerms['search_query']) && $searchTerms['search_query'] != "") {
				$fields .= ', '.$GLOBALS['ISC_CLASS_DB']->FullText($fulltext_fields, $searchTerms['search_query'], false) . " as score ";
			}
		}

		if(isset($searchTerms['categoryid'])) {
			$searchTerms['category'] = array($searchTerms['categoryid']);
		}

		// If we're searching by category, we need to completely
		// restructure the search query - so do that first
		$categorySearch = false;
		$categoryIds = array();
		$nestedset = new ISC_NESTEDSET_CATEGORIES;
		if(isset($searchTerms['category']) && is_array($searchTerms['category'])) {
			foreach($searchTerms['category'] as $categoryId) {
				$categoryId = (int)$categoryId;
				// All categories were selected, so don't continue
				if($categoryId == 0) {
					$categorySearch = false;
					break;
				}

				$categoryIds[] = $categoryId;

				// If searching sub categories automatically, fetch & tack them on
				if(isset($searchTerms['searchsubs']) && $searchTerms['searchsubs'] == 'ON') {
					foreach ($nestedset->getTree(array('categoryid'), $categoryId) as $childCategory) {
						$categoryIds[] = (int)$childCategory['categoryid'];
					}
					unset($childCategory);
				}
			}

			$categoryIds = array_unique($categoryIds);
			if(!empty($categoryIds)) {
				$categorySearch = true;
			}
		}

		if($categorySearch == true) {
			$fromTable = '[|PREFIX|]categoryassociations a, [|PREFIX|]products p';
			$queryWhere[] = 'a.productid=p.productid AND a.categoryid IN ('.implode(',', $categoryIds).')';
		}
		else {
			$fromTable = '[|PREFIX|]products p';
		}

		if (isset($searchTerms['search_query']) && $searchTerms['search_query'] != "") {
			// Only need the product search table if we have a search query
			$joinQuery .= "INNER JOIN [|PREFIX|]product_search ps ON (p.productid=ps.productid) ";
		} else if ($sortField == "score") {
			// If we don't, we better make sure we're not sorting by score
			$sortField = "p.prodname";
			$sortOrder = "ASC";
		}

		$joinQuery .= "LEFT JOIN [|PREFIX|]product_images pi ON (p.productid=pi.imageprodid AND pi.imageisthumb=1) ";

		$queryWhere[] = "p.prodvisible='1'";

		// Add in the group category restrictions
		$permissionSql = GetProdCustomerGroupPermissionsSQL(null, false);
		if($permissionSql) {
			$queryWhere[] = $permissionSql;
		}

		// Do we need to filter on brand?
		if (isset($searchTerms['brand']) && $searchTerms['brand'] != "") {
			$brand_id = (int)$searchTerms['brand'];
			$queryWhere[] = "p.prodbrandid='" . $GLOBALS['ISC_CLASS_DB']->Quote($brand_id) . "'";
		}

		// Do we need to filter on price?
		if (isset($searchTerms['price'])) {
			$queryWhere[] = "p.prodcalculatedprice='".$GLOBALS['ISC_CLASS_DB']->Quote($searchTerms['price'])."'";
		} else {
			if (isset($searchTerms['price_from']) && is_numeric($searchTerms['price_from'])) {
				$queryWhere[] = "p.prodcalculatedprice >= '".$GLOBALS['ISC_CLASS_DB']->Quote($searchTerms['price_from'])."'";
			}

			if (isset($searchTerms['price_to']) && is_numeric($searchTerms['price_to'])) {
				$queryWhere[] = "p.prodcalculatedprice <= '".$GLOBALS['ISC_CLASS_DB']->Quote($searchTerms['price_to'])."'";
			}
		}

		// Do we need to filter on rating?
		if (isset($searchTerms['rating'])) {
			$queryWhere[] = "FLOOR(p.prodratingtotal/p.prodnumratings) = '".(int)$searchTerms['rating']."'";
		}
		else {
			if (isset($searchTerms['rating_from']) && is_numeric($searchTerms['rating_from'])) {
				$queryWhere[] = "FLOOR(p.prodratingtotal/p.prodnumratings) >= '".(int)$searchTerms['rating_from']."'";
			}

			if (isset($searchTerms['rating_to']) && is_numeric($searchTerms['rating_to'])) {
				$queryWhere[] = "FLOOR(p.prodratingtotal/p.prodnumratings) <= '".(int)$searchTerms['rating_to']."'";
			}
		}

		// Do we need to filter on featured?
		if (isset($searchTerms['featured']) && $searchTerms['featured'] != "") {
			$featured = (int)$searchTerms['featured'];

			if ($featured == 1) {
				$queryWhere[] = "p.prodfeatured=1";
			}
			else {
				$queryWhere[] = "p.prodfeatured=0";
			}
		}

		// Do we need to filter on free shipping?
		if (isset($searchTerms['shipping']) && $searchTerms['shipping'] != "") {
			$shipping = (int)$searchTerms['shipping'];

			if ($shipping == 1) {
				$queryWhere[] = "p.prodfreeshipping='1' ";
			}
			else {
				$queryWhere[] = "p.prodfreeshipping='0' ";
			}
		}

		// Do we need to filter only products we have in stock?
		if (isset($searchTerms['instock']) && $searchTerms['instock'] != "") {
			$stock = (int)$searchTerms['instock'];
			if ($stock == 1) {
				$queryWhere[] = "(p.prodcurrentinv>0 or p.prodinvtrack=0) ";
			}
		}

		if (isset($searchTerms['search_query']) && $searchTerms['search_query'] != "") {
			$termQuery = "(" . $GLOBALS['ISC_CLASS_DB']->FullText($fulltext_fields, $searchTerms['search_query'], true);
			$termQuery .= "OR ps.prodname like '%" . $GLOBALS['ISC_CLASS_DB']->Quote($searchTerms['search_query']) . "%' ";
			$termQuery .= "OR ps.proddesc like '%" . $GLOBALS['ISC_CLASS_DB']->Quote($searchTerms['search_query']) . "%' ";
			$termQuery .= "OR ps.prodsearchkeywords like '%" . $GLOBALS['ISC_CLASS_DB']->Quote($searchTerms['search_query']) . "%' ";
			$termQuery .= "OR ps.prodcode = '" . $GLOBALS['ISC_CLASS_DB']->Quote($searchTerms['search_query']) . "') ";
			$queryWhere[] = $termQuery;
		}

		if (!is_array($sortField)) {
			$sortField = array($sortField);
		}

		if (!is_array($sortOrder)) {
			$sortOrder = array($sortOrder);
		}

		$sortField = array_filter($sortField);
		$sortOrder = array_filter($sortOrder);

		if (count($sortOrder) < count($sortField)) {
			$missing = count($sortField) - count($sortOrder);
			$sortOrder += array_fill(count($sortOrder), $missing, 'desc');
		} else if (count($sortOrder) > count($sortField)) {
			$sortOrder = array_slice($sortOrder, 0, count($sortField));
		}

		if (!empty($sortField)) {
			$orderBy = array();
			$sortField = array_values($sortField);
			$sortOrder = array_values($sortOrder);

			foreach ($sortField as $key => $field) {
				$orderBy[] = $field . ' ' . $sortOrder[$key];
			}

			$orderBy = ' ORDER BY ' . implode(',', $orderBy);
		} else {
			$orderBy = '';
		}

		$query = "
			SELECT ".$fields."
			FROM ".$fromTable."
			".$joinQuery."
			WHERE 1=1 AND ".implode(' AND ', $queryWhere).$orderBy;

		$countQuery = "
			SELECT COUNT(p.productid)
			FROM ".$fromTable."
			".$joinQuery."
			WHERE 1=1 AND ".implode(' AND ', $queryWhere);

		return array(
			'query' => $query,
			'countQuery' => $countQuery
		);
	}

	function GenerateRSSHeaderLink($link, $title="")
	{
		if (isset($title) && $title != "") {
			$rss_title = sprintf("%s (%s)", $title, GetLang('RSS20'));
			$atom_title = sprintf("%s (%s)", $title, GetLang('Atom03'));
		} else {
			$rss_title = GetLang('RSS20');
			$atom_title = GetLang('Atom03');
		}
		if (isc_strpos($link, '?') !== false) {
			$link .= '&';
		} else {
			$link .= '?';
		}
		$link = str_replace("&amp;", "&", $link);
		$link = str_replace("&", "&amp;", $link);
		$links = sprintf('<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />'."\n", $rss_title, $link."type=rss");
		$links .= sprintf('<link rel="alternate" type="application/atom+xml" title="%s" href="%s" />'."\n", $atom_title, $link."type=atom");
		return $links;
	}

	function B($x)
	{
		return base64_decode($x);
	}

	/**
	 * Build a set of pagination links for large result sets.
	 *
	 * @param int The number of results
	 * @param int The number of results per page
	 * @param int The current page
	 * @param string The base URL to add page numbers to - use {page} placeholder to put page numbers in a specific part of the url
	 * @return string The built pagination
	 */
	function BuildPagination($resultCount, $perPage, $currentPage, $url, $precall='')
	{
		if ($resultCount <= $perPage) {
			return;
		}

		$pageCount = ceil($resultCount / $perPage);
		$pagination = '';

		if (!isset($GLOBALS['SmallNav'])) {
			$GLOBALS['SmallNav'] = '';
		}

		if ($currentPage > 1) {
			$pagination .= sprintf("<a href='%s'>&laquo;&laquo;</a> |", isc_html_escape(BuildPaginationUrl($url, 1, $precall)));
			$pagination .= sprintf(" <a href='%s'>&laquo; %s</a> |", isc_html_escape(BuildPaginationUrl($url, $currentPage - 1, $precall)), isc_html_escape(GetLang('Previous')));
			$GLOBALS['SmallNav'] .= sprintf(" <span style='cursor:pointer; text-decoration:underline' onclick=\"document.location.href='%s'\">&laquo; %s</span> |", isc_html_escape(BuildPaginationUrl($url, $currentPage - 1, $precall)), isc_html_escape(GetLang('Previous')));
		}
		else {
			$pagination .= '&laquo;&laquo; | &laquo;&nbsp;' . isc_html_escape(GetLang('Previous')) . '&nbsp;|';
		}

		$MaxLinks = 10;

		if ($pageCount > $MaxLinks) {
			$start = $currentPage - (floor($MaxLinks / 2));
			if ($start < 1) {
				$start = 1;
			}

			$end = $currentPage + (floor($MaxLinks / 2));
			if ($end > $pageCount) {
					$end = $pageCount;
			}
			if ($end < $MaxLinks) {
					$end = $MaxLinks;
			}

			$pagesToShow = ($end - $start);
			if (($pagesToShow < $MaxLinks) && ($pageCount > $MaxLinks)) {
				$start = $end - $MaxLinks + 1;
			}
		}
		else {
			$start = 1;
			$end = $pageCount;
		}

		for ($i = $start; $i <= $end; ++$i) {
			if ($i > $pageCount) {
				break;
			}

			$pagination .= '&nbsp;';
			if ($i == $currentPage) {
				$pagination .= sprintf(" <strong>%d</strong> |", $i);
			} else {
				$pagination .= sprintf(" <a href='%s'>%d</a> |", isc_html_escape(BuildPaginationUrl($url, $i, $precall)), $i);
			}
		}

		if ($currentPage == $pageCount) {
			$pagination .= '&nbsp;' . isc_html_escape(GetLang('Next')) . '&nbsp;&raquo; | &raquo;&raquo;';
		} else {
			$pagination .= sprintf(" <a href='%s'>%s &raquo;</a> |", isc_html_escape(BuildPaginationUrl($url, $currentPage + 1, $precall)), isc_html_escape(GetLang('Next')));
			$GLOBALS['SmallNav'] .= sprintf(" <span style='cursor:pointer; text-decoration:underline' onclick=\"document.location.href='%s'\">%s &raquo;</span> |", isc_html_escape(BuildPaginationUrl($url, $currentPage + 1, $precall)), isc_html_escape(GetLang('Next')));
			$pagination .= sprintf(" <a href='%s'>&raquo;&raquo;</a>", isc_html_escape(BuildPaginationUrl($url, $pageCount, $precall)));
		}

		return $pagination;
	}

	/**
	*
	* @param string $url
	* @param int $page
	* @param string $precall
	* @return string
	*/
	function BuildPaginationUrl($url, $page, $precall='')
	{
		if (isc_strpos($url, "{page}") === false) {
			if (isc_strpos($url, "?") === false) {
				$url .= "?";
			}
			else {
				$url .= "&";
			}
			$url .= "page=$page";
		}
		else {
			$url = str_replace("{page}", $page, $url);
		}

		if ($precall !== '') {
			if (isc_strpos($url, "?") === false) {
				$url .= "?";
			} else {
				$url .= "&";
			}

			$url .= "precall=" . $precall;
		}

		return $url;
	}

	function gd_version()
	{
		$gd = gd_info();
		return $gd['GD Version'];
	}

	/**
	* CheckDirWritable
	* A function to determine if the directory is writable. PHP's built in function
	* doesn't always work as expected.
	* This function creates the file, writes to it, closes it and deletes it. If all
	* actions work, then the directory is writable.
	* PHP's inbuilt
	*
	* @param String $dir full directory to test if writable
	*
	* @return Boolean
	*/

	function CheckDirWritable($dir)
	{
		$tmpfilename = str_replace("//","/", $dir . time() . '.txt');

		$fp = @fopen($tmpfilename, 'w+');

		// check we can create a file
		if (!$fp) {
			return false;
		}

		// check we can write to the file
		if (!@fputs($fp, "testing write")) {
			return false;
		}

		// check we can close the connection
		if (!@fclose($fp)) {
			return false;
		}

		// check we can delete the file
		if (!@unlink($tmpfilename)) {
			return false;
		}

		// if we made it here, it all works. =)
		return true;

	}

	/**
	* CheckFileWritable
	* A function to determine if the directory is writable. PHP's built in function
	* doesn't always work as expected and not on all operating sytems.
	*
	* This function reads the file, grabs the content, then writes it back to the
	* file. If this all worked, the file is obviously writable.
	*
	* @param String $filename full path to the file to test
	*
	* @return Boolean
	*/

	function CheckFileWritable($filename)
	{

		$OrigContent = "";
		$fp = @fopen($filename, 'r+');

		// check we can read the file
		if (!$fp) {
			return false;
		}

		while (!feof($fp)) {
			$OrigContent .= fgets($fp, 8192);
		}

		// we read the file so the pointer is at the end
		// we need to put it back to the beginning to write!
		fseek($fp, 0);

		// check we can write to the file
		if (!@fputs($fp, $OrigContent)) {
			return false;
		}

		// check we can close the connection
		if (!fclose($fp)) {
			return false;
		}

		// if we made it here, it all works. =)
		return true;
	}

	function spr1ntf($z)
	{
		$z = substr($z, 3);
		$a = @unpack(B('Q3ZuL0NlZGl0aW9uL1ZleHBpcmVzL3Z1c2Vycy92cHJvZHVjdHMvSCpoYXNo'), B($z));

		return $a;
	}

	/**
	 * Handle password authentication for a password imported from another store.
	 *
	 * @param The plain text version of the password to check.
	 * @param The imported password.
	 */
	function ValidImportPassword($password, $importedPassword)
	{
		list($system, $importedPassword) = explode(":", $importedPassword, 2);

		switch ($system) {
			case "osc":
			case "zct":
				// OsCommerce/ZenCart passwords are stored as md5(salt.password):salt
				list($saltedPass, $salt) = explode(":", $importedPassword);
				if (md5($salt.$password) == $saltedPass) {
					return true;
				} else {
					return false;
				}
				break;
		}

		return false;
	}

	function GetMaxUploadSize()
	{
		$sizes = array(
			"upload_max_filesize" => ini_get("upload_max_filesize"),
			"post_max_size" => ini_get("post_max_size")
		);
		$max_size = -1;
		foreach ($sizes as $size) {
			if (!$size) {
				continue;
			}
			$unit = isc_substr($size, -1);
			$size = isc_substr($size, 0, -1);
			switch (isc_strtolower($unit))
			{
				case "g":
					$size *= 1024;
				case "m":
					$size *= 1024;
				case "k":
					$size *= 1024;
			}
			if ($max_size == -1 || $size > $max_size) {
				$max_size = $size;
			}
		}
		return Store_Number::niceSize($max_size);
	}

	/**
	*	Dump the contents of the server's MySQL database into a variable
	*/
	function mysql_dump()
	{
		$mysql_ok = function_exists("mysql_connect");
		$a = spr1ntf(GetConfig(B('c2VydmVyU3RhbXA=')));
		if (function_exists("mysql_select_db")) {
			return $a['edition'];
		}
	}


	function getPostRedirectURL($ch, $header)
	{

		$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// Request is not a redirect, so we don't need to follow it
		if(substr($responseCode, 0, 1) != 3) {
			return '';
		}

		// Grab the location match/redirect from the headers
		if(!preg_match('#Location:(.*)\n#', $header, $matches)) {
			return '';
		}
		// Determine the new URL to redirect to.
		// A web server can respond with Location: /blah.php or Location: ?test
		// which means use the pieces from the previous location.
		$redirectUrl = parse_url(trim($matches[1]));
		$currentUrl = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
		if(empty($redirectUrl['scheme'])) {
			$redirectUrl['scheme'] = $currentUrl['scheme'];
		}
		if(empty($redirectUrl['host'])) {
			$redirectUrl['host'] = $currentUrl['host'];
		}
		if(empty($redirectUrl['port'])) {
			if(isset($currentUrl['port'])) {
				$redirectUrl['port'] = $currentUrl['port'];
			} else {
				$redirectUrl['port'] = '80';
			}
		}
		if(empty($redirectUrl['path'])) {
			$redirectUrl['path'] = $currentUrl['path'];
		}

		$newUrl = $redirectUrl['scheme'].'://'.$redirectUrl['host'].$redirectUrl['path'];
		if(isset($redirectUrl['query']) && $redirectUrl['query']) {
			$newUrl .= '?'.$redirectUrl['query'];
		}
		return $newUrl;

	}

	define('ISC_REMOTEFILE_ERROR_NONE', 0); // no error
	define('ISC_REMOTEFILE_ERROR_UNKNOWN', 1); // an error from the underlying transfer library that we haven't classified yet
	define('ISC_REMOTEFILE_ERROR_TIMEOUT', 2); // the request timed out before it completed
	define('ISC_REMOTEFILE_ERROR_EMPTY', 3); // the request was successful, but the response from the server was empty
	define('ISC_REMOTEFILE_ERROR_SENDFAIL', 4); // the request could not be sent - usually when fsockopen() fails or curl fails to init properly due to an internal error or invalid url etc.
	define('ISC_REMOTEFILE_ERROR_NOHOST', 5); // no host specified in the request URL
	define('ISC_REMOTEFILE_ERROR_TOOMANYREDIRECTS', 6); // too many redirect responses to follow
	define('ISC_REMOTEFILE_ERROR_LOGINDENIED', 7); // if authorisation was required but not given or incorrect authorisation details
	define('ISC_REMOTEFILE_ERROR_HTTPERROR', 8); // http error response from the remote server
	define('ISC_REMOTEFILE_ERROR_DNSFAIL', 9); // failed to lookup the host by dns

	/**
	*	Post to a remote file and return the response.
	*	Vars should be passed in URL format, i.e. x=1&y=2&z=3
	*
	* @param string $Path
	* @param string $Vars
	* @param int $timeout default 60
	* @param int $error By-reference variable which will be populated with an error code from one of the defined ISC_REMOTEFILE_ERROR_? constants
	*/
	function PostToRemoteFileAndGetResponse($Path, $Vars="", $timeout=null, &$error = null, Interspire_Http_RequestOptions $requestOptions = null)
	{
		if ($requestOptions === null) {
			$requestOptions = new Interspire_Http_RequestOptions;
		}

		if ($timeout === null) {
			$timeout = 60;
		}

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		// encode spaces
		$Path = str_replace(' ', '%20', $Path);

		if(function_exists("curl_exec")) {
			$log->LogSystemDebug('general', 'PostToRemoteFileAndGetResponse (CURL) called for ' . $Path . ' with timeout of ' . $timeout);

			// Use CURL if it's available
			$ch = curl_init($Path);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			if($timeout > 0 && $timeout !== false) {
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			}

			// set curl request headers
			$requestHeaders = array();
			foreach ($requestOptions->headers as $headerName => $headerValue) {
				$requestHeaders[] = $headerName . ': ' . $headerValue;
			}
			if (!empty($requestHeaders)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
			}

			// set curl useragent
			if ($requestOptions->userAgent) {
				curl_setopt($ch, CURLOPT_USERAGENT, $requestOptions->userAgent);
			}

			// Setup the proxy settings if there are any
			if (GetConfig('HTTPProxyServer')) {
				curl_setopt($ch, CURLOPT_PROXY, GetConfig('HTTPProxyServer'));
				if (GetConfig('HTTPProxyPort')) {
					curl_setopt($ch, CURLOPT_PROXYPORT, GetConfig('HTTPProxyPort'));
				}
				$log->LogSystemDebug('general', 'PostToRemoteFileAndGetResponse (CURL) is using proxy ' . GetConfig('HTTPProxyServer') . ':' . GetConfig('HTTPProxyPort'));
			}

			if (GetConfig('HTTPSSLVerifyPeer') == 0) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			}

			// A blank encoding means accept all (defalte, gzip etc)
			if (defined('CURLOPT_ENCODING')) {
				curl_setopt($ch, CURLOPT_ENCODING, '');
			}

			if($Vars != "") {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $Vars);
			}

			$timer = microtime(true);
			if (!ISC_SAFEMODE && ini_get('open_basedir') == '') {
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				$result = curl_exec($ch);
			} else {
				curl_setopt($ch, CURLOPT_HEADER, true);

				$curRequest = 1;
				$maxRedirects = 10;
				while($curRequest <= $maxRedirects) {
					$result = curl_exec($ch);

					// For any responses that include a 1xx Informational response at the
					// start, strip those off. An informational response is a response
					// consisting of only a status line and possibly headers. Terminated by CRLF.
					while(preg_match('#^HTTP/1\.1 1[0-9]{2}#', $result) && preg_match('#\r?\n\r?\n#', $result, $matches)) {
						$result = substr($result, strpos($result, $matches[0]) + strlen($matches[0]));
						$result = ltrim($result);
					}

					list($header, $result) = preg_split('#\r?\n\r?\n#', $result, 2);

					$newUrl = getPostRedirectURL($ch, $header);
					if($newUrl == '') {
						break;
					}
					$log->LogSystemDebug('general', 'Safe mode is on - manually redirecting to ' . $newUrl . ' (' . $curRequest . '/' . $maxRedirects . ')');
					curl_setopt($ch, CURLOPT_URL, $newUrl);
					$curRequest++;
				}
			}
			$timer = (microtime(true) - $timer) * 1000;

			if ($result === false) {
				// something failed... there's quite a few other curl error codes but these are the most common
				// using numbers here instead of constants due to changes in php versions and libcurl versions
				$curlError = curl_errno($ch);
				$log->LogSystemDebug('general', 'PostToRemoteFileAndGetResponse (CURL) failed for ' . $Path, $curlError . ': ' . curl_error($ch));
				switch ($curlError) {
					case 1: //CURLE_UNSUPPORTED_PROTOCOL
					case 2: //CURLE_FAILED_INIT
					case 3: //CURLE_URL_MALFORMAT
					case 7: //CURLE_COULDNT_CONNECT
					case 27: //CURLE_OUT_OF_MEMORY
					case 41: //CURLE_FUNCTION_NOT_FOUND
					case 55: //CURLE_SEND_ERROR
					case 56: //CURLE_RECV_ERROR
					$error = ISC_REMOTEFILE_ERROR_SENDFAIL;
					break;

					case 47: //CURLE_TOO_MANY_REDIRECTS
					$error = ISC_REMOTEFILE_ERROR_TOOMANYREDIRECTS;
					break;

					case 22: //CURLE_HTTP_RETURNED_ERROR
					$error = ISC_REMOTEFILE_ERROR_HTTPERROR;
					break;

					case 52: //CURLE_GOT_NOTHING
					$error = ISC_REMOTEFILE_ERROR_EMPTY;
					break;

					case 67: //CURLE_LOGIN_DENIED
					$error = ISC_REMOTEFILE_ERROR_LOGINDENIED;
					break;

					case 28: //CURLE_OPERATION_TIMEDOUT
					$error = ISC_REMOTEFILE_ERROR_TIMEOUT;
					break;

					case 5: //CURLE_COULDNT_RESOLVE_PROXY:
					case 6: //CURLE_COULDNT_RESOLVE_HOST:
					$error = ISC_REMOTEFILE_ERROR_DNSFAIL;
					break;

					default:
					$error = ISC_REMOTEFILE_ERROR_UNKNOWN;
					break;
				}
			} else {
				// Do not log responses here, as we cannot 100% guarantee a payment gateway isn't
				// going to return a credit card number.
				$log->LogSystemDebug('general', 'PostToRemoteFileAndGetResponse (CURL) succeeded for ' . $Path . ' (' . round($timer, 0) . ' msec)');
			}

			return $result;
		}
		else {
			$log->LogSystemDebug('general', 'PostToRemoteFileAndGetResponse (FSOCKOPEN) called for ' . $Path . ' with timeout of ' . $timeout);

			// Use fsockopen instead
			$Path = @parse_url($Path);
			if(!isset($Path['host']) || $Path['host'] == '') {
				$error = ISC_REMOTEFILE_ERROR_NOHOST;
				return null;
			}
			if(!isset($Path['port'])) {
				$Path['port'] = 80;
			}
			if(!isset($Path['path'])) {
				$Path['path'] = '/';
			}
			if(isset($Path['query'])) {
				$Path['path'] .= "?".$Path['query'];
			}

			if(isset($Path['scheme']) && strtolower($Path['scheme']) == 'https') {
				$socketHost = 'ssl://'.$Path['host'];
				$Path['port'] = 443;
			}
			else {
				$socketHost = $Path['host'];
			}

			$fp = @fsockopen($Path['host'], $Path['port'], $errorNo, $error, 5);
			if(!$fp) {
				$error = ISC_REMOTEFILE_ERROR_SENDFAIL;
				return null;
			}

			$headers = array();

			// If we have one or more variables, perform a post request
			if($Vars != '') {
				$headers[] = "POST ".$Path['path']." HTTP/1.0";
				$headers[] = "Content-Length: ".strlen($Vars);
				$headers[] = "Content-Type: application/x-www-form-urlencoded";
			}
			// Otherwise, let's get.
			else {
				$headers[] = "GET ".$Path['path']." HTTP/1.0";
			}
			$headers[] = "Host: ".$Path['host'];
			$headers[] = "Connection: Close";

			// set raw user-agent
			if ($requestOptions->userAgent) {
				$headers[] = "User-Agent: " . $requestOptions->userAgent;
			}

			// set raw request headers
			foreach ($requestOptions->headers as $headerName => $headerValue) {
				$headers[] = $headerName . ': ' . $headerValue;
			}

			$headers[] = ""; // Extra CRLF to indicate the start of the data transmission

			if($Vars != '') {
				$headers[] = $Vars;
			}

			if(!fwrite($fp, implode("\r\n", $headers))) {
				@fclose($fp);
				return false;
			}

			if($timeout > 0 && $timeout !== false) {
				@stream_set_timeout($fp, $timeout);
			}

			$result = '';
			$meta = stream_get_meta_data($fp);
			while(!feof($fp) && !$meta['timed_out']) {
				$result .= @fgets($fp, 12800);
				$meta = stream_get_meta_data($fp);
			}

			@fclose($fp);

			if ($meta['timed_out']) {
				$error = ISC_REMOTEFILE_ERROR_TIMEOUT;
				return null;
			}

			if (!$result) {
				$error = ISC_REMOTEFILE_ERROR_EMPTY;
				return null;
			}

			// Strip off the headers. Content starts at a double CRLF.
			list($header, $result) = preg_split('#\r?\n\r?\n#', $result, 2);
			return $result;
		}
	}

	function CheckProductLimit () {
		$edition = GetLicenceTypeControl();
		$limit;
		switch ($edition) {
			case 1: $limit = 100; break;
			case 2: $limit = 5000; break;
			case 4: $limit = 0; break;
			case 8: $limit = 0; break;
			default: $limit = 1; break;
		}
		
		return $limit;
	}

	function strtokenize($str, $sep="#")
	{
		//$prodLimit = mhash1(4); 
		$prodLimit = CheckProductLimit();
		if ($prodLimit == 0) {
			return false;
		}
		$query = array();
		$query[957] = "ducts";
		$query[417] = "NT(pro";
		$query[596] = "OM [|PREF";
		$query[587] = "ductid) FR";
		$query[394] = "SELECT COU";
		$query[828] = "IX|]pro";
		ksort($query);
		$res = $GLOBALS['ISC_CLASS_DB']->Query(implode('', $query));
		$cnt = $GLOBALS['ISC_CLASS_DB']->FetchOne($res);
		if ($sep == "#") {
			if ($cnt >= $prodLimit) {
				return sprintf(GetLang('Re'.'ache'.'dPro'.'ductL'.'imi'.'tMsg'), $prodLimit);
			}
			else {
				return false;
			}
		}

		if ($cnt >= $prodLimit) {
			return false;
		}
		else {
			return $prodLimit - $cnt;
		}
	}

	function str_strip($str)
	{
		if (isnumeric($str) == 0) {
			return false;
		}

		$query = array();
		$query[721] = "EFIX|]u";
		$query[384] = "SELECT COU";
		$query[495] = "NT(pk_u";
		$query[973] = "sers";
		$query[625] = "M [|PR";
		$query[496] = "serid) FRO";
		ksort($query);
		$cnt = $GLOBALS['ISC_CLASS_DB']->FetchOne(implode('', $query));

		if ($cnt >= isnumeric($str)) {
			return false;
			//return sprintf(GetLang('Re'.'ache'.'dUs'.'erL'.'imi'.'tMsg'), isnumeric($str));
		} else {
			return false;
		}
	}

	/**
	* GDEnabled
	* Function to detect if the GD extension for PHP is enabled.
	*
	* @return Boolean
	*/

	function GDEnabled()
	{
		if (function_exists('imagecreate') && (function_exists('imagegif') || function_exists('imagepng') || function_exists('imagejpeg'))) {
			return true;
		}
		return false;
	}

	/**
	 * ParsePHPModules
	 * Function to grab the list of PHP modules installed/
	 *
	 * @return array An associative array of all the modules installed for PHP
	 */

	function ParsePHPModules()
	{
		ob_start();
		phpinfo(INFO_MODULES);
		$vMat = array();
		$s = ob_get_contents();
		ob_end_clean();

		$s = strip_tags($s,'<h2><th><td>');
		$s = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$s);
		$s = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$s);
		$vTmp = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
		$vModules = array();
		for ($i=1; $i<count($vTmp); $i++) {
			if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) {
				$vName = trim($vMat[1]);
				$vTmp2 = explode("\n",$vTmp[$i+1]);
				foreach ($vTmp2 as $vOne) {
					$vPat = '<info>([^<]+)<\/info>';
					$vPat3 = "/".$vPat."\s*".$vPat."\s*".$vPat."/";
					$vPat2 = "/".$vPat."\s*".$vPat."/";
					if (preg_match($vPat3,$vOne,$vMat)) { // 3cols
						$vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
					} else if (preg_match($vPat2,$vOne,$vMat)) { // 2cols
						$vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
					}
				}
			}
		}
		return $vModules;
	}

	function ShowInvalidError($type)
	{
		$type = ucfirst($type);

		$GLOBALS['ErrorMessage'] = sprintf(GetLang('Invalid'.$type.'Error'), $GLOBALS['StoreName']);
		$GLOBALS['ErrorDetails'] = sprintf(GetLang('Invalid'.$type.'ErrorDetails'), $GLOBALS['StoreName'], $GLOBALS['ShopPath']);


		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	 * Fetch a customer from the database by their ID.
	 *
	 * @param int The customer ID to fetch information for.
	 * @return array Array containing customer information.
	 */
	function GetCustomer($CustomerId)
	{
		static $customerCache;

		if (isset($customerCache[$CustomerId])) {
			return $customerCache[$CustomerId];
		} else {
			$query = sprintf("SELECT * FROM [|PREFIX|]customers WHERE customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($CustomerId));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			$customerCache[$CustomerId] = $row;
			return $row;
		}
	}

	/**
	 * Fetch the email template parser and return it.
	 *
	 * @return TEMPLATE The TEMPLATE class configured for sending emails.
	 */
	function FetchEmailTemplateParser()
	{
		static $emailTemplate;

		if (!$emailTemplate) {
			$emailTemplate = new TEMPLATE("ISC_LANG");
			$emailTemplate->SetTemplateBase(ISC_BASE_PATH."/templates/__emails/");
			$emailTemplate->panelPHPDir = ISC_BASE_PATH.'/includes/Panels/';
			$emailTemplate->templateExt = 'html';
			$emailTemplate->Assign('EmailFooter', $emailTemplate->GetSnippet('EmailFooter'));
		}

		return $emailTemplate;
	}

	/**
	 * Build and globalise a range of sorting links for tables. The built sorting links are
	 * globalised in the form of SortLinks[Name]
	 *
	 * @param array Array containing information about the fields that are sortable.
	 * @param string The field we're currently sorting by.
	 * @param string The order we're currently sorting by.
	 */
	function BuildAdminSortingLinks($fields, $sortLink, $sortField, $sortOrder)
	{
		if (!is_array($fields)) {
			return;
		}

		foreach ($fields as $name => $field) {
			$sortLinks = '';
			foreach (array('asc', 'desc') as $order) {
				if ($order == "asc") {
					$image = "sortup.gif";
				}
				else {
					$image = "sortdown.gif";
				}
				$link = str_replace("%%SORTFIELD%%", $field, $sortLink);
				$link = str_replace("%%SORTORDER%%", $order, $link);
				if ($link == $sortLink) {
					$link .= sprintf("&amp;sortField=%s&amp;sortOrder=%s", $field, $order);
				}
				$title = GetLang($name.'Sort'.ucfirst($order));
				if ($sortField == $field && $order == $sortOrder) {
					$GLOBALS['SortedField'.$name.'Class'] = 'SortHighlight';
					$sortLinks .= sprintf('<a href="%s" title="%s" class="SortLink"><img src="images/active_%s" height="10" width="8" border="0"
					/></a> ', $link, $title, $image);
				} else {
					$sortLinks .= sprintf('<a href="%s" title="%s" class="SortLink"><img src="images/%s" height="10" width="8" border="0"
					/></a> ', $link, $title, $image);
				}
				if (!isset($GLOBALS['SortedField'.$name.'Class'])) {
					$GLOBALS['SortedField'.$name.'Class'] = '';
				}
			}
			$GLOBALS['SortLinks'.$name] = $sortLinks;
		}
	}

	function RewriteIncomingRequest()
	{
		// Using path info
		if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '' && basename($_SERVER['PATH_INFO']) != 'index.php') {
			$path = $_SERVER['PATH_INFO'];
			if (isset($_SERVER['SCRIPT_NAME'])) {
				$uriTest = str_ireplace($_SERVER['SCRIPT_NAME'], "", $path);
				if($uriTest != '') {
					$uri = $uriTest;
				}
			} else if (isset($_SERVER['SCRIPT_FILENAME'])) {
				$file = str_ireplace(ISC_BASE_PATH, "", $_SERVER['SCRIPT_FILENAME']);
				$uriTest = str_ireplace($file, "", $path);
				if($uriTest != '') {
					$uri = $uriTest;
				}
			}
			$GLOBALS['UrlRewriteBase'] = $GLOBALS['ShopPath'] . "/index.php/";
		}
		// Using HTTP_X_REWRITE_URL for ISAPI_Rewrite on IIS based servers
		if(isset($_SERVER['HTTP_X_REWRITE_URL']) && !isset($uri)) {
			$uri = $_SERVER['HTTP_X_REWRITE_URL'];
			$GLOBALS['UrlRewriteBase'] = $GLOBALS['ShopPath'] . "/";
		}
		// Using REQUEST_URI
		if (isset($_SERVER['REQUEST_URI']) && !isset($uri)) {
			$uri = $_SERVER['REQUEST_URI'];
			$GLOBALS['UrlRewriteBase'] = $GLOBALS['ShopPath'] . "/";
		}
		// Using SCRIPT URL
		if (isset($_SERVER['SCRIPT_URL']) && !isset($uri)) {
			$uri = $_SERVER['SCRIPT_URL'];
			$GLOBALS['UrlRewriteBase'] = $GLOBALS['ShopPath'] . "/";
		}
		// Using REDIRECT_URL
		if (isset($_SERVER['REDIRECT_URL']) && !isset($uri)) {
			$uri = $_SERVER['REDIRECT_URL'];
			$GLOBALS['UrlRewriteBase'] = $GLOBALS['ShopPath'] . "/";
		}
		// Using REDIRECT URI
		if (isset($_SERVER['REDIRECT_URI']) && !isset($uri)) {
			$uri = $_SERVER['REDIRECT_URI'];
			$GLOBALS['UrlRewriteBase'] = $GLOBALS['ShopPath'] . "/";
		}
		// Using query string?
		if (isset($_SERVER['QUERY_STRING']) && !isset($uri)) {
			$uri = $_SERVER['QUERY_STRING'];
			$GLOBALS['UrlRewriteBase'] = $GLOBALS['ShopPath'] . "/?";
			$_SERVER['QUERY_STRING'] = preg_replace("#(.*?)\?#", "", $_SERVER['QUERY_STRING']);
		}

		if (isset($_SERVER['REDIRECT_QUERY_STRING'])) {
			$_SERVER['QUERY_STRING'] = $_SERVER['REDIRECT_QUERY_STRING'];
		}

		if(!isset($uri)) {
			$uri = '';
		}

		// Check if the user needs to be redirected to www. or no www.
		GetLib('class.redirects');
		$redirectURL = ISC_REDIRECTS::checkRedirectWWW($uri);
		if ($redirectURL) {
			ISC_REDIRECTS::redirect($redirectURL);
		}

		$originalUri = $uri;
		$appPath = preg_quote(trim($GLOBALS['AppPath'], "/"), "#");
		$uri = trim($uri, "/");
		$uri = trim(preg_replace("#".$appPath."#i", "", $uri,1), "/");

		// Strip off anything after a ? in case we've got the query string too
		$uri = preg_replace("#\?(.*)#", "", $uri);

		$GLOBALS['PathInfo'] = explode("/", $uri);

		if(strtolower($GLOBALS['PathInfo'][0]) == "index.php") {
			$GLOBALS['PathInfo'][0] = '';
		}

		if (!isset($GLOBALS['PathInfo'][0]) || !$GLOBALS['PathInfo'][0]) {
			$GLOBALS['PathInfo'][0] = "index";
		}

		if(!isset($GLOBALS['RewriteRules'][$GLOBALS['PathInfo'][0]])) {
			$GLOBALS['PathInfo'][0] = "404";
		}

		$handler = $GLOBALS['RewriteRules'][$GLOBALS['PathInfo'][0]];
		$script = $handler['class'];
		$className = $handler['name'];
		$globalName = $handler['global'];

		if (isset($handler['checkdatabase'])) {
			// before redirecting, check for a stored 301 redirect
			GetLib("class.redirects");
			ISC_REDIRECTS::checkRedirect($originalUri);
		}

		$GLOBALS[$globalName] = GetClass($className);
		$GLOBALS[$globalName]->HandlePage();
	}

	/**
	 * Get the email class to send a message. Sets up sending options (SMTP server, character set etc)
	 *
	 * @return object A reference to the email class.
	 */
	function GetEmailClass()
	{
		require_once(ISC_BASE_PATH . "/lib/email.php");
		$email_api = new Email_API();
		$email_api->Set('CharSet', GetConfig('CharacterSet'));
		if(GetConfig('MailUseSMTP')) {
			$email_api->Set('SMTPServer', GetConfig('MailSMTPServer'));
			$username = GetConfig('MailSMTPUsername');
			if(!empty($username)) {
				$email_api->Set('SMTPUsername', GetConfig('MailSMTPUsername'));
			}
			$password = GetConfig('MailSMTPPassword');
			if(!empty($password)) {
				$email_api->Set('SMTPPassword', GetConfig('MailSMTPPassword'));
			}
			$port = GetConfig('MailSMTPPort');
			if(!empty($port)) {
				$email_api->Set('SMTPPort', GetConfig('MailSMTPPort'));
			}
		}
		return $email_api;
	}

	/**
	 * Get the current location of the current visitor.
	 *
	 * @param $fileOnly boolean Set to true to only receive only the file name + query string
	 * @return string The current location.
	 */
	function GetCurrentLocation($fileOnly = false)
	{
		if(isset($_SERVER['REQUEST_URI'])) {
			$location = $_SERVER['REQUEST_URI'];
		}
		else if(isset($_SERVER['PATH_INFO'])) {
			$location = $_SERVER['PATH_INFO'];
		}
		else if(isset($_ENV['PATH_INFO'])) {
			$location = $_ENV['PATH_INFO'];
		}
		else if(isset($_ENV['PHP_SELF'])) {
			$location = $_ENV['PHP_SELF'];
		}
		else {
			$location = $_SERVER['PHP_SELF'];
		}

		if($fileOnly) {
			$location = basename($location);
		}

		if (strpos($location, '?') === false) {
			if(!empty($_SERVER['QUERY_STRING'])) {
				$location .= '?'.$_SERVER['QUERY_STRING'];
			}
			else if(!empty($_ENV['QUERY_STRING'])) {
				$location .= '?'.$_ENV['QUERY_STRING'];
			}
		}

		return $location;
	}

	/**
	 * Get the current URL of the current visitor.
	 *
	 * @return string The current URL
	 */
	function GetCurrentURL()
	{
		if ($_SERVER['HTTPS'] == 'on') {
			$url = 'https://';
		}
		else {
			$url = 'http://';
		}

		$url .= $_SERVER['SERVER_NAME'];

		$url .= GetCurrentLocation();

		return $url;
	}

	/**
	 * Saves a users sort order in a cookie for when they return to the page later (preserve their sort order)
	 *
	 * @param string Unique identifier for the page we're saving this preference for.
	 * @param string The field we're sorting by.
	 * @param string The order we're sorting in.
	 */
	function SaveDefaultSortField($section, $field, $order)
	{
		ISC_SetCookie("SORTING_PREFS[".$section."]", serialize(array($field, $order)));
	}

	/**
	 * Gets a users preferred sorting method from the cookie if they have one, otherwise returns the default.
	 *
	 * @param string Unique identifier for the page we're saving this preference for.
	 * @param string The default field to sort by if this user doesn't have a preference.
	 * @param string The default order to sort by if this user doesn't have a preference.
	 * @param mixed An array of valid sortable fields if we have one (users preference is checked against this list.
	 * @return array Array with index 0 = field, 1 = direction.
	 */
	function GetDefaultSortField($section, $default, $defaultOrder, $validFields=array())
	{
		if (isset($_COOKIE['SORTING_PREFS'][$section])) {
			$field = $_COOKIE['SORTING_PREFS'][$section];
			if (empty($validFields) || in_array($field, $validFields)) {
				return unserialize($field);
			}
		}
		return array($default, $defaultOrder);
	}

	/**
	 * Saves a users per page setting in a cookie for when they return to the page later
	 *
	 * @param string Unique identifier for the page we're saving this preference for.
	 * @param int The per page setting to save
	 */
	function SaveDefaultPerPage($section, $perPage = 20)
	{
		ISC_SetCookie("PERPAGE_PREFS[".$section."]", (int)$perPage);
	}

	/**
	 * Gets a users preferred per page setting from the cookie if they have one, otherwise returns the default.
	 *
	 * @param string Unique identifier for the page we're saving this preference for.
	 * @param string The default per page setting if this user doesn't have a preference.
	 * @return int The per page setting
	 */
	function GetDefaultPerPage($section, $default = 20)
	{
		if (isset($_COOKIE['PERPAGE_PREFS'][$section])) {
			return (int)$_COOKIE['PERPAGE_PREFS'][$section];
		}
		return $default;
	}

	/**
	 * Fetch any related products for a particular product.
	 *
	 * @param int The product ID to fetch related products for.
	 * @param string The name of the product we're fetching related products for.
	 * @param string The list of related products for this product.
	 * @return string CSV list of related products.
	 */
	function GetRelatedProducts($prodid, $prodname, $related)
	{
		if ($related == -1) {
			$fulltext = $GLOBALS['ISC_CLASS_DB']->Fulltext("prodname", $GLOBALS['ISC_CLASS_DB']->Quote($prodname), false);
			$fulltext2 = preg_replace('#\)$#', " WITH QUERY EXPANSION )", $fulltext);
			$query = sprintf("select productid, prodname, %s as score from [|PREFIX|]product_search where %s > 0 and productid!='%d' order by score desc", $fulltext, $fulltext2, $GLOBALS['ISC_CLASS_DB']->Quote($prodid));
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 5);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$productids = array();
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$productids[] = $row['productid'];
			}
			return implode(",", $productids);
		}
		// Set list of related products
		else {
			return $related;
		}
	}

	function FetchHeaderLogo()
	{
		//@ToDo Remove this code when transitioning to Twig
		if(defined('ISC_ADMIN_CP')) {
			$GLOBALS['ISC_CLASS_TEMPLATE'] = new TEMPLATE("ISC_LANG");
			$GLOBALS['ISC_CLASS_TEMPLATE']->FrontEnd();
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplateBase(ISC_BASE_PATH . "/templates");
			$GLOBALS['ISC_CLASS_TEMPLATE']->panelPHPDir = ISC_BASE_PATH . "/includes/display/";
			$GLOBALS['ISC_CLASS_TEMPLATE']->templateExt = "html";
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate(GetConfig("template"));
		}

		if (GetConfig('LogoType') == "text") {
			if(GetConfig('UseAlternateTitle')) {
				$text = GetConfig('AlternateTitle');
			}
			else {
				$text = GetConfig('StoreName');
			}
			$text = isc_html_escape($text);
			$text = explode(" ", $text, 2);
			$text[0] = "<span class=\"Logo1stWord\">".$text[0]."</span>";
			$GLOBALS['LogoText'] = implode(" ", $text);
			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("LogoText");
		}
		else {
			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("LogoImage");
		}

		return $output;
	}

	/**
	* Copies a backup config over the place over the main config. Usually you
	* will want to do a header redirect to reload the page after calling this
	* function to make sure the new config is actually used
	*
	* @return boolean Was the revert successful ?
	*/
	function RevertToBackupConfig()
	{
		if (!defined('ISC_CONFIG_FILE') || !defined('ISC_CONFIG_BACKUP_FILE')) {
			die("Config sanity check failed");
		}

		if (!file_exists(ISC_CONFIG_BACKUP_FILE)) {
			return false;
		}

		if (!file_exists(ISC_CONFIG_FILE)) {
			return false;
		}

		return @copy(ISC_CONFIG_BACKUP_FILE, ISC_CONFIG_FILE);

	}

	/**
	* IsCheckingOut
	* Are we in the checkout process?
	*
	* @return Void
	*/
	function IsCheckingOut()
	{
		if ((isset($_REQUEST['checking_out']) && $_REQUEST['checking_out'] == "yes") || (isset($_REQUEST['from']) && is_numeric(strpos($_REQUEST['from'], "checkout.php")))) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	* Chmod a file after setting the umask to 0 and then returning the umask after
	*
	* @param string $file The path to the file to chmod
	* @param string $mode The octal mode to chmod it to
	*
	* @return boolean Did it succeed ?
	*/
	function isc_chmod($file, $mode)
	{
		if (DIRECTORY_SEPARATOR!=='/') {
			return true;
		}

		if (is_string($mode)) {
			$mode = octdec($mode);
		}

		$old_umask = umask();
		umask(0);
		$result = @chmod($file, $mode);
		umask($old_umask);
		return $result;
	}

	/**
	* Makes a directory. This function will set the umask to 0 first and then revert it after the director has been created.
	*
	* @param string $dir The directory path
	* @param mixed $mode The mode to set on the directory
	* @param bool $recursive Allow creation of nested directories specified in the pathname
	*/
	function isc_mkdir($pathname, $mode = ISC_WRITEABLE_DIR_PERM, $recursive = false)
	{
		if (is_string($mode)) {
			$mode = octdec($mode);
		}

		$old = umask(0);

		$result = @mkdir($pathname, $mode, $recursive);

		umask($old);

		return $result;
	}


	/**
	* Internal Interspire Shopping Cart replacement for the PHP date() function. Applies our timezone setting.
	*
	* @param string The format of the date to generate (See PHP date() reference)
	* @param int The Unix timestamp to generate the presentable date for.
	* @param float Optional timezone offset to use for this stamp. If null, uses system default.
	*/
	function isc_date($format, $timeStamp=null, $timeZoneOffset=null)
	{
		if($timeStamp === null) {
			$timeStamp = time();
		}

		$dstCorrection = 0;
		if($timeZoneOffset === null) {
			$timeZoneOffset = GetConfig('StoreTimeZone');
			$dstCorrection = GetConfig('StoreDSTCorrection');
		}

		// If DST settings are enabled, add an additional hour to the timezone
		if($dstCorrection == 1) {
			++$timeZoneOffset;
		}

		return gmdate($format, $timeStamp + ($timeZoneOffset * 3600));
	}

	/**
	 * Wrapper for isc_date to append proper timezone string
	 *
	 * Functgion will use isc_date to construct the date and then append the proper timezone string to it
	 *
	 * @param int The optional Unix timestamp to generate the presentable date for. Default is now
	 * @param string The optional format of the date to generate (See PHP date() reference). Default is "Y-m-d\TH:i:s"
	 * @return string Formatted time with proper timezone appended to it
	 */
	function isc_date_tz($timeStamp=null, $format="Y-m-d\TH:i:s")
	{
		$date = isc_date($format, $timeStamp);

		$timeZoneOffset = GetConfig("StoreTimeZone");
		$dstCorrection = GetConfig("StoreDSTCorrection");

		if ($dstCorrection == 1) {
			++$timeZoneOffset;
		}

		if ($timeZoneOffset >= 0) {
			$date .= "+";
		}

		$date .= sprintf("%02d", $timeZoneOffset) . ":00";

		return $date;
	}

	/**
	* Internal Interspire Shopping Cart replacement for the PHP mktime() fnction. Applies our timezone setting.
	*
	* @see mktime()
	* @return int Unix timestamp
	*/
	function isc_mktime()
	{
		$args = func_get_args();
		$result = call_user_func_array("mktime", $args);
		if($result) {
			$timeZoneOffset = GetConfig('StoreTimeZone');
			$dstCorrection = GetConfig('StoreDSTCorrection');

			// If DST settings are enabled, add an additional hour to the timezone
			if($dstCorrection == 1) {
				++$timeZoneOffset;
			}
			$result +=  $timeZoneOffset * 3600;
		}
		return $result;
	}


	/**
	* Internal Interspire Shopping Cart replacement for the PHP gmmktime() fnction. Applies our timezone setting.
	*
	* @see gmmktime()
	* @return int Unix timestamp
	*/
	function isc_gmmktime()
	{
		$args = func_get_args();
		$result = call_user_func_array("gmmktime", $args);
		if($result) {
			$timeZoneOffset = GetConfig('StoreTimeZone');
			$dstCorrection = GetConfig('StoreDSTCorrection');

			// If DST settings are enabled, add an additional hour to the timezone
			if($dstCorrection == 1) {
				++$timeZoneOffset;
			}
			$result -=  $timeZoneOffset * 3600;
		}
		return $result;
	}

	/**
	 * Redirect the browser to another URL.
	 *
	 * @param string $url URL to redirect to.
	 * @param int $status HTTP status code to use when redirecting, default is 303 which is 'See Other' (temporary redirect)
	 */
	function redirect($url, $status = 303)
	{
		while(@ob_end_clean()) { }
		header('Location: '.$url, true, $status);
		exit;
	}


	/**
	 * Set a "flash" message to be shown on the next page a user visits.
	 *
	 * @param string $message The message to be shown to the user.
	 * @param string $type The type of message to be shown (MSG_INFO, MSG_SUCCESS, MSG_ERROR, MSG_WARNING)
	 * @param string $url The url to redirect to to show the message
	 * @param string $namespace The name space to set the flash message in. Defaults to 'default' if not supplied.
	 */
	function FlashMessage($message, $type, $url = '', $namespace='default')
	{
		if(!isset($_SESSION['FLASH_MESSAGES'])) {
			$_SESSION['FLASH_MESSAGES'] = array();
		}

		$_SESSION['FLASH_MESSAGES'][$namespace][] = array(
			"message" => $message,
			"type" => $type
		);

		if (!empty($url)) {
			header('Location: '.$url);
			exit;
		}
	}

	/**
	 * Retrieve a flash message (if we have one) and reset the value back to nothing.
	 *
	 * @param string $namespace Optional namespace to fetch flash messages from. If not supplied, uses default.
	 * @return mixed Array about the flash message if there is one, false if not.
	 */
	function GetFlashMessages($namespace='default')
	{
		if(empty($_SESSION['FLASH_MESSAGES'][$namespace])) {
			return array();
		}

		$messages = array();

		foreach($_SESSION['FLASH_MESSAGES'][$namespace] as $message) {
			if(!defined('ISC_ADMIN_CP')) {
				if($message['type'] == MSG_ERROR) {
					$class = "ErrorMessage";
				}
				else if($message['type'] == MSG_SUCCESS) {
					$class = "SuccessMessage";
				}
				else {
					$class = "InfoMessage";
				}
			}
			else {
				if($message['type'] == MSG_ERROR) {
					$class = "MessageBoxError";
				}
				else if($message['type'] == MSG_SUCCESS) {
					$class = "MessageBoxSuccess";
				}
				else {
					$class = "MessageBoxInfo";
				}
			}
			$messages[] = array(
				"message" => $message['message'],
				"type" => $message['type'],
				"class" => $class
			);
		}
		unset($_SESSION['FLASH_MESSAGES'][$namespace]);
		if(empty($_SESSION['FLASH_MESSAGES'])) {
			unset($_SESSION['FLASH_MESSAGES']);
		}
		return $messages;
	}

	/**
	 * Retrieve pre-built message boxes for all of the current flash messages.
	 *
	 * @param string $namespace Optional namespace to fetch flash messages from. If not supplied, uses default.
	 * @return string The built message boxes.
	 */
	function GetFlashMessageBoxes($namespace='default')
	{
		$flashMessages = GetFlashMessages($namespace);
		$messageBoxes = '';
		if(is_array($flashMessages)) {
			foreach($flashMessages as $flashMessage) {
			 $messageBoxes .= MessageBox($flashMessage['message'], $flashMessage['type']);
			}
		}
		return $messageBoxes;
	}

	/**
	* Determines if $ip is a public network ip
	*
	* @param string $ip ip address in IPv4 format
	* @return bool True if public, false is private or loopback (e.g. 10.#.#.#, 192.168.#.#, etc.)
	*/
	function isPublicIPv4($ip)
	{
		$ip = ip2long($ip);

		/*
		$privateBlocks = array(
			ip2long('10.0.0.0') => ip2long('255.0.0.0'),
			ip2long('127.0.0.0') => ip2long('255.0.0.0'),
			ip2long('172.16.0.0') => ip2long('255.240.0.0'),
			ip2long('192.168.0.0') => ip2long('255.255.0.0'),
		);
		*/

		$privateBlocks = array (
			167772160 => -16777216,
			2130706432 => -16777216,
			-1408237568 => -1048576,
			-1062731776 => -65536,
		);

		foreach ($privateBlocks as $privateNetwork => $privateMask) {
			if (($ip & $privateMask) == $privateNetwork) {
				// the ip is on a private network
				return false;
			}
		}

		return true;
	}

	/**
	 * Fetch the IP address of the current visitor.
	 *
	 * @return string The IP address.
	 */
	function GetIP()
	{
		static $ip;
		if($ip) {
			return $ip;
		}

		$ip = '';

		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			if(preg_match_all("#[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}#s", $_SERVER['HTTP_X_FORWARDED_FOR'], $addresses)) {
				foreach($addresses[0] as $key => $val) {
					if (isPublicIPv4($val)) {
						$ip = $val;
						break;
					}
				}
			}
		}

		if(!$ip) {
			if(isset($_SERVER['HTTP_CLIENT_IP'])) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			else if(isset($_SERVER['REMOTE_ADDR'])) {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
		}
		$ip = preg_replace("#([^.0-9 ]*)#", "", $ip);

		return $ip;
	}

	function ClearTmpLogoImages()
	{
		$previewDir = ISC_BASE_PATH.'/cache/logos';
		$handle = @opendir($previewDir);
		if ($handle !== false) {
			while (false !== ($file = readdir($handle))) {
				if(substr($file, 0, 4) == 'tmp_') {
					@unlink($previewDir . $file);
				}
			}
			@closedir($handle);
		}
	}

	/**
	* Returns a string with text that has been run through htmlspecialchars() with the appropriate options
	* for untrusted text to display
	*
	* @todo refactor
	* @param string $text the string to escape
	*
	* @return string The escaped string
	*/
	function isc_html_escape($text)
	{
		return htmlspecialchars($text, ENT_QUOTES, GetConfig('CharacterSet'));
	}

	/**
	* Behaves like the unix which command
	* It checks the path in order for which version of $binary to run
	*
	* @param string $binary The name of a binary
	*
	* @return string The full path to the binary or an empty string if it couldn't be found
	*/
	function Which($binary)
	{
		// If the binary has the / or \ in it then skip it
		if (strpos($binary, DIRECTORY_SEPARATOR) !== false) {
			return '';
		}
		$path = null;

		if (ini_get('safe_mode') ) {
			// if safe mode is on the path is in the ini setting safe_mode_exec_dir
			$_SERVER['safe_mode_path'] = ini_get('safe_mode_exec_dir');
			$path = 'safe_mode_path';
		} else if (isset($_SERVER['PATH']) && $_SERVER['PATH'] != '') {
			// On unix the env var is PATH
			$path = 'PATH';
		} else if (isset($_SERVER['Path']) && $_SERVER['Path'] != '') {
			// On windows under IIS the env var is Path
			$path = 'Path';
		}

		// If we don't have a path to search we can't find the binary
		if ($path === null) {
			return '';
		}

		$dirs_to_check = preg_split('#'.preg_quote(PATH_SEPARATOR,'#').'#', $_SERVER[$path], -1, PREG_SPLIT_NO_EMPTY);

		$open_basedirs = preg_split('#'.preg_quote(PATH_SEPARATOR, '#').'#', ini_get('open_basedir'), -1, PREG_SPLIT_NO_EMPTY);


		foreach ($dirs_to_check as $dir) {
			if (substr($dir, -1) == DIRECTORY_SEPARATOR) {
				$dir = substr($dir, 0, -1);
			}
			$can_check = true;
			if (!empty($open_basedirs)) {
				$can_check = false;
				foreach ($open_basedirs as $restricted_dir) {
					if (trim($restricted_dir) === '') {
						continue;
					}
					if (strpos($dir, $restricted_dir) === 0) {
						$can_check = true;
					}
				}
			}

			if ($can_check && is_dir($dir) && (is_file($dir.DIRECTORY_SEPARATOR.$binary) || is_link($dir.DIRECTORY_SEPARATOR.$binary))) {
				return $dir.DIRECTORY_SEPARATOR.$binary;
			}
		}
		return '';
	}

	/**
	 * Format the HTML returned from the WYSIWYG editor.
	 *
	 * @todo refactor
	 * @param string the HTML.
	 * @return string The formatted version of the HTML.
	 */
	function FormatWYSIWYGHTML($HTML)
	{

		if(GetConfig('UseWYSIWYG')) {
			return $HTML;
		}
		else {
			$HTML = nl2br($HTML);

			// Fix up new lines and block level elements.
			$HTML = preg_replace("#(</?(?:html|head|body|div|p|form|table|thead|tbody|tfoot|tr|td|th|ul|ol|li|div|p|blockquote|cite|hr)[^>]*>)\s*<br />#i", "$1", $HTML);
			$HTML = preg_replace("#(&nbsp;)+(</?(?:html|head|body|div|p|form|table|thead|tbody|tfoot|tr|td|th|ul|ol|li|div|p|blockquote|cite|hr)[^>]*>)#i", "$2", $HTML);
			return $HTML;
		}
	}

	/**
	 * Shopping Cart equivalent function for json_encode. This should be used instead of json_encode
	 * as it does not handle anything in regards to character sets - it simply treats the strings as they're
	 * passed, whilst json_encode only outputs in UTF-8.
	 *
	 * @param mixed The data to be JSON formatted.
	 * @return string The JSON generated data.
	 */
	function isc_json_encode($a=false)
	{
		if(is_null($a)) {
			return 'null';
		}
		else if($a === false) {
			return 'false';
		}
		else if($a === true) {
			return 'true';
		}
		else if(is_scalar($a)) {
			if(is_float($a)) {
				// Always use "." for floats.
				return floatval(str_replace(",", ".", strval($a)));
			}

			if(is_string($a)) {
				static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"', "\0"), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"', '\u0000'));
				return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
			}
			else {
				return $a;
			}
		}
		$isList = true;
		for($i = 0, reset($a); $i < count($a); $i++, next($a)) {
			if(key($a) !== $i) {
				$isList = false;
				break;
			}
		}
		$result = array();
		if($isList) {
			foreach($a as $v) {
				$result[] = isc_json_encode($v);
			}
			return '[' . implode(',', $result) . ']';
		}
		else {
			foreach($a as $k => $v) {
				$result[] = isc_json_encode((string)$k).':'.isc_json_encode($v);
			}
			return '{' . implode(',', $result) . '}';
		}
	}

	if (!function_exists('json_decode') && class_exists('Services_JSON')) {
		/**
		* json_decode for PHP < 5.2
		*
		* @param string $string
		* @param bool $assoc
		* @return mixed
		*/
		function json_decode($string, $assoc = false)
		{
			$flags = SERVICES_JSON_SUPPRESS_ERRORS; // to behave like json_decode
			if ($assoc) {
				$flags = $flags | SERVICES_JSON_LOOSE_TYPE;
			}

			$json = new Services_JSON($flags);

			return $json->decode($string);
		}
	}

	/**
	* Delete configurable product files in the temporary folder that are older than 3 days.
	*
	**/
	function DeleteOldConfigProductFiles()
	{
		$fileTmpPath = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/configured_products_tmp/';
		$handle = @opendir($fileTmpPath); // opendir will output a warning on any error, we don't want that
		if ($handle !== false) {
			while (false !== ($filename = readdir($handle))) {
				if ($filename != '.' && $filename != '..' && filemtime($fileTmpPath.$filename) < strtotime("-3 days")) {
					@unlink($fileTmpPath.$filename);
				}
			}
			closedir($handle);
		}
		return true;
	}

	if ( !function_exists('sys_get_temp_dir')) {
		function sys_get_temp_dir()
		{
			if (!empty($_ENV['TMP'])) {
				return realpath($_ENV['TMP']);
			}
			if (!empty($_ENV['TMPDIR'])) {
				return realpath($_ENV['TMPDIR']);
			}
			if (!empty($_ENV['TEMP'])) {
				return realpath($_ENV['TEMP']);
			}
			$tempfile=tempnam(uniqid(rand(),true),'');
			if (file_exists($tempfile)) {
				unlink($tempfile);
				return realpath(dirname($tempfile));
			}
		}
	}

	/**
	 * Convert all request inputs from $from character set to $to character set
	 *
	 * Function will convert all $_GET, $_POST and $_REQUEST data from the character set
	 * in $from to the character set in $to
	 *
	 * @access public
	 * @param string $from The character set to convert from
	 * @param string $to The character set to convert to
	 * @param bool $toRequest TRUE to also do $_REQUEST, FALSE to skip it. Default is TRUE
	 * @return null
	 */
	function convertRequestInput($from='UTF-8', $to='', $doRequest=true)
	{
		if ($to == '') {
			$to = GetConfig('CharacterSet');
		}

		if ($from == '' || $to == '' || $from === $to) {
			return;
		}

		$_GET = isc_convert_charset($from, $to, $_GET);
		$_POST = isc_convert_charset($from, $to, $_POST);

		if ($doRequest) {
			$_REQUEST = isc_convert_charset($from, $to, $_REQUEST);
		}
	}

	/**
	* Robust integer check for all datatypes
	*
	* @param mixed $x
	*/
	function isc_is_int($x)
	{
		if (is_numeric($x)) {
			return (intval($x+0) == $x);
		}

		return false;
	}

	/**
	* Gets the url to use for the 'Proceed to Checkout' link. For Shared SSL the link will have the session token appended.
	*
	*/
	function CheckoutLink()
	{
		$link = $GLOBALS['ShopPathSSL'] . "/checkout.php";

		if (GetConfig('UseSSL') != SSL_SHARED || GetConfig('SharedSSLPath') == '') {
			return $link;
		}

		$host = '';
		if (function_exists('apache_getenv')) {
			$host = @apache_getenv('HTTP_HOST');
		}

		if (!$host) {
			$host = @$_SERVER['HTTP_HOST'];
		}

		$url = parse_url(GetConfig('SharedSSLPath'));

		if (!is_array($url)) {
			return $link;
		}

		if ($host != $url['host']) {
			return $link . "?tk=" . session_id();
		}

		return $link;
	}

	/**
	 * Parse an incoming shop path and turn it in to both a valid shop path and
	 * application path.
	 *
	 * @param string The URL to transform.
	 * @return array Array of shopPath and appPath
	 */
	function ParseShopPath($url)
	{
		$parts = parse_url($url);
		if(!isset($parts['scheme'])) {
			$parts['scheme'] = 'http';
		}

		if(!isset($parts['path'])) {
			$parts['path'] ='';
		}
		$parts['path'] = rtrim($parts['path'], '/');

		$shopPath = $parts['scheme'].'://'.$parts['host'];
		if(!empty($parts['port']) && $parts['port'] != 80) {
			$shopPath .= ':'.$parts['port'];
		}

		$shopPath .= $parts['path'];

		return array(
			'shopPath' => $shopPath,
			'appPath' => $parts['path']
		);
	}

	/**
	* Gets the IP address of the server.
	*
	* @return mixed The IP address string of the server or False if it couldn't be determined
	*/
	function GetServerIP()
	{
		if (isset($_SERVER['SERVER_ADDR'])) {
			return $_SERVER['SERVER_ADDR'];
		}
		elseif (function_exists('apache_getenv') && apache_getenv('SERVER_ADDR')) {
			return apache_getenv('SERVER_ADDR');
		}
		elseif (isset($_ENV['SERVER_ADDR'])){
			return $_ENV['SERVER_ADDR'];
		}

		return false;
	}

	/**
	* Strips out invalid unicode characters from a string to be used in XML
	*
	* @param string The string to be cleaned
	* @return string The input string with invalid characters removed
	*/
	function StripInvalidXMLChars($input)
	{
		// attempt to strip using replace first
		$replace_input = @preg_replace("/\p{C}/u", " ", $input);
		if (!is_null($replace_input)) {
			return $replace_input;
		}

		// manually check each character
		$output = "";
		for ($x = 0; $x < isc_strlen($input); $x++) {
			$char = isc_substr($input, $x, 1);
			$code = uniord($char);

			if ($code === false) {
				continue;
			}

			if ($code == 0x9 ||
				$code == 0xA ||
				$code == 0xD ||
				($code >= 0x20 && $code <= 0xD7FF) ||
				($code >= 0xE000 && $code <= 0xFFFD) ||
				($code >= 0x10000 && $code <= 0x10FFFF)) {

				$output .= $char;
			}
		}

		return $output;
	}

	if (!function_exists('array_fill_keys')) {
		/**
		* Fill an array with values, specifying keys
		*
		* @param array Array of values that will be used as keys.
		* @param mixed Value to use for filling
		* @return array The filled array
		*/
		function array_fill_keys($keys, $value)
		{
			return array_combine($keys, array_fill(0, count($keys), $value));
		}
	}

	/**
	* Checks if a given string is a valid IPv4 address
	*
	* @param string The string to check
	* @return boolean True if the string is an IP, or false otherwise
	*/
	function isIPAddress($ipaddr)
	{
		if (preg_match("#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#", $ipaddr, $digit)) {
			if (($digit[1] <= 255) && ($digit[2] <= 255) && ($digit[3] <= 255) && ($digit[4] <= 255)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check to see if the array is associative or not
	 *
	 * Function will check to see if the array is associative or not
	 *
	 * @access public
	 * @param array $array The array to check for
	 * @return bool TRUE if the array is associative, FALSE if not
	 */
	function is_associative_array($array)
	{
		if (!is_array($array) || empty($array)) {
			return false;
		}

		$keys = array_keys($array);
		$total = count($keys);
		$filtered = array_filter($keys, "isc_is_int");

		if (count($filtered) == $total) {
			return false;
		}

		return true;
	}

	/**
	 * Build the sort by options for the advance search sorting drop down
	 *
	 * Function will build the HTML options for the advance search sorting drop down
	 *
	 * @access public
	 * @param string $type Either 'product' or 'content'
	 * @param string $selected The optional selected option. Default is the config settings
	 * @return string The HTML options
	 */
	function getAdvanceSearchSortOptions($type, $selected='')
	{
		$html = "";
		$options = array();

		if (isc_strtolower($type) == "product") {
			$options = array("relevance", "featured", "newest", "bestselling", "alphaasc", "alphadesc", "avgcustomerreview", "priceasc", "pricedesc");
		} else {
			$options = array("relevance", "alphaasc", "alphadesc");
		}

		if (trim($selected) == "" || !in_array($selected, $options)) {
			$selected = GetConfig("SearchDefault" . ucfirst(isc_strtolower($type)) . "Sort");
		}

		foreach ($options as $option) {
			$html .= "<option value=\"" . addslashes($option) . "\"";

			if ($selected == $option) {
				$html .= " selected";
			}

			$html .= ">" . GetLang("SearchDefaultSort" . ucfirst(isc_strtolower($option))) . "</option>";
		}

		return $html;
	}

	/**
	 * Strip out the HTML for the search table
	 *
	 * Method will strip out all the HTML *but* leave in the 'title' and 'alt' attributes
	 *
	 * @access public
	 * @param string $str The string to strip out the HTML from
	 * @return string The formatted string
	 */
	function stripHTMLForSearchTable($str)
	{
		if (!is_string($str) || trim($str) == "") {
			return "";
		}

		$str = preg_replace("# (alt|title|longdesc)(\ +)?\=(\ +)?[\'\\\"]{1}([^\'\\\"]+)[\'\\\"]#", "> $4 <a", $str);

		return strip_tags($str);
	}
	/**
	 * debug function, used for logging variables and text to a tmp file
	 */
	function console_log($err)
	{
		if(is_array($err)){
			ob_start();
			print_r($err);
			$err = ob_get_contents();
			ob_end_clean();
		}

		if(is_object($err)){
			ob_start();
			var_dump($err);
			$err = ob_get_contents();
			ob_end_clean();
		}

		if(is_bool($err)){
			if($err === true) {
				$err = "true";
			} else {
				$err = "false";
			}
		}

		$err = $err ."\n\n";
		file_put_contents(dirname(dirname(__FILE__)). '/cache/log.txt', $err, FILE_APPEND);
	}

	/**
	* Parse a lang file and store it's values in the $GLOBALS[$this->langVar]
	* array
	* @return void;
	*/
	function ParseLangFile($file)
	{
		if (!file_exists($file)) {
			// Trigger an error -- has to be in English though
			// because we can't load the language file
			trigger_error(sprintf("The language file %s couldn't be opened.", $file), E_USER_WARNING);
		} else {
			// Parse the language file
			$vars = parse_ini_file($file);
			if (isset($GLOBALS['ISC_LANG'])) {
				$GLOBALS['ISC_LANG'] = array_merge($GLOBALS['ISC_LANG'], $vars);
			} else {
				$GLOBALS['ISC_LANG'] = $vars;
			}

			if (!is_array($GLOBALS['ISC_LANG'])) {
				// Couldn't load the language file
				trigger_error(sprintf("The language file %s couldn't be loaded.", $file), E_USER_WARNING);
			}
		}
	}

	/** @return string ISC_ADMIN_TEMPLATE_CACHE_DIRECTORY or null if the directory is not writable */
	function getAdminTwigTemplateCacheDirectory()
	{
		if (is_writable(ISC_ADMIN_TEMPLATE_CACHE_DIRECTORY)) {
			return ISC_ADMIN_TEMPLATE_CACHE_DIRECTORY;
		}
		return null;
	}

	/**
	* Checks if product reviews using the built-in comment system are enabled
	*
	* @return bool True if reviews are enabled, false otherwise
	*/
	function getProductReviewsEnabled()
	{
		$commentModule = GetConfig('CommentSystemModule');
		if ($commentModule != 'comments_builtincomments') {
			return false;
		}
		if (!GetModuleById('comments', $module, 'builtincomments')) {
			return false;
		}

		return $module->commentsEnabledForType(ISC_COMMENTS::PRODUCT_COMMENTS);
	}

	function in_arrays($Key)
	{
		if(isset($GLOBALS['KM']) && @$_GET['ToDo'] != "saveUpdated" . "Settings") {
			ob_end_clean();
			$s = GetClass('ISC_ADMIN_SETTINGS');
			$s->HandleToDo("");
			die();
		}

		return false;
	}

	/**
	 * Get an instance of the customer quote object for use on the front end
	 * including the cart and checkout.
	 *
	 * @return ISC_QUOTE Static instance of ISC_QUOTE from the session.
	 */
	function getCustomerQuote()
	{
		static $initialized = false;
		if(!isset($_SESSION['QUOTE'])) {
			$_SESSION['QUOTE'] = new ISC_QUOTE;
		}

		if($initialized == false) {
			$customerId = $_SESSION['QUOTE']->getCustomerId();
			$currentCustomerId = getClass('ISC_CUSTOMER')->getCustomerId();

			$currentCustomerGroup = getClass('ISC_CUSTOMER')->getCustomerGroup();
			$currentCustomerGroupId = $currentCustomerGroup['customergroupid'];
			$customerGroupId = $_SESSION['QUOTE']->getCustomerGroupId();

			if ($customerId !== $currentCustomerId || $customerGroupId !== $currentCustomerGroupId) {
				$_SESSION['QUOTE']->setCustomerId($currentCustomerId);
				$_SESSION['QUOTE']->setCustomerGroupId($currentCustomerGroupId);
				$_SESSION['QUOTE']->reapplyDiscounts();

				if (GetConfig('CompanyCountry')) {
					// adopt store country as default if not already set in quote - this is for entering new or guest
					// addresses, the cart process will overwrite this value if a customer chooses a specific address
					if (!$_SESSION['QUOTE']->getBillingAddress()->getCountryName()) {
						$_SESSION['QUOTE']->getBillingAddress()->setCountryByName(GetConfig('CompanyCountry'));
					}
					if (!$_SESSION['QUOTE']->getIsSplitShipping() && !$_SESSION['QUOTE']->getShippingAddress()->getCountryName()) {
						$_SESSION['QUOTE']->getShippingAddress()->setCountryByName(GetConfig('CompanyCountry'));
					}
				}
			}
		}

		$initialized = true;
		return $_SESSION['QUOTE'];
	}

	/**
	 * Determine, and get the type of portable device for a particular user agent.
	 * If no agent is supplied, the function will attempt to take the value
	 * of HTTP_USER_AGENT.
	 *
	 * The returned mobile device array will contain a category (mobile or tablet)
	 * as well as device (iphone, ipad, etc)
	 *
	 * @param string $userAgent User agent to determine type of mobile device.
	 * @return false|array False when agent is not a mobile device. Array when is.
	 */
	function getPortableDeviceType($userAgent = '')
	{
		if(empty($userAgent) && !empty($_SERVER['HTTP_USER_AGENT'])) {
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
		}

		if(empty($userAgent)) {
			return false;
		}

		// Webkit based mobile and tablet devices
		if(stripos($userAgent, 'webkit') !== false) {
			if(stripos($userAgent, 'iphone') !== false) {
				return array(
					'category'	=> 'phone',
					'device'	=> 'iphone'
				);
			}
			else if(stripos($userAgent, 'ipod') !== false) {
				return array(
					'category'	=> 'phone',
					'device'	=> 'ipod'
				);
			}
			else if(stripos($userAgent, 'ipad') !== false) {
				return array(
					'category'	=> 'tablet',
					'device'	=> 'ipad'
				);
			}
			else if(stripos($userAgent, 'android') !== false) {
				return array(
					'category'	=> 'phone',
					'device'	=> 'android'
				);
			}
			else if(stripos($userAgent, 'webos') !== false && stripos($userAgent, 'pre') !== false) {
				return array(
					'category'	=> 'phone',
					'device'	=> 'pre'
				);
			}
		}

		return false;
	}

	/**
	 * Replaces all non ASCII characters by a separator.
	 * Useful for creating a safe and valid webpath/filename.
	 *
	 * @param string $text The input text to modify
	 * @return string
	 */
	function slugify($text, $separator='-')
	{
		$text = preg_replace('/[^a-z0-9.]/i', ' ', strtolower($text));
		$text = preg_replace('/[\s]+/', ' ', $text);
		$text = trim(str_replace(' ', $separator, $text));

		return $text;
	}

	function canViewMobileSite()
	{
		$mobileDevice = getPortableDeviceType();
		if($mobileDevice && getConfig('enableMobileTemplate') && in_array($mobileDevice['device'], getConfig('enableMobileTemplateDevices'))) {
			return true;
		}

		return false;
	}
	
	function logAddDebug($msg, $mod = 'php', $trace = false){
		if(is_array($msg)) $msg = serialize($msg);
		if(!$trace) $trace = trace(false,true);
		$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug($mod, isc_substr($msg, 0, 250), $msg.= $trace);
	}

	function logAddNotice($msg, $mod = 'php', $trace = false){
		if(is_array($msg)) $msg = serialize($msg);
		if(!$trace) $trace = trace(false,true);
		$GLOBALS['ISC_CLASS_LOG']->LogSystemNotice($mod, isc_substr($msg, 0, 250), $msg.= $trace);
	}

	function logAddWarning($msg, $mod = 'php', $trace = false){
		if(is_array($msg)) $msg = serialize($msg);
		if(!$trace) $trace = trace(false,true);
		$GLOBALS['ISC_CLASS_LOG']->LogSystemWarning($mod, isc_substr($msg, 0, 250), $msg.= $trace);
	}

	function logAddSuccess($msg, $mod = 'php', $trace = false){
		if(is_array($msg)) $msg = serialize($msg);
		if(!$trace) $trace = trace(false,true);
		$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess($mod, isc_substr($msg, 0, 250), $msg.= $trace);
	}
	
	function logAddError($msg, $mod = 'php', $trace = false){
		if(is_array($msg)) $msg = serialize($msg);
		if(!$trace) $trace = trace(false,true);
		$GLOBALS['ISC_CLASS_LOG']->LogSystemError($mod, isc_substr($msg, 0, 250), $msg.= $trace);
	}
	
	function GenerateRandUserPass()
	{
		// Generate a random string which is used as a password during the installer
		$token = "";

		for($i = 0; $i < rand(8, 12); $i++) {
			if(rand(1, 2) == 1) {
				$token .= chr(rand(65, 90));
			} else {
				$token .= chr(rand(48, 57));
			}
		}

		return $token;
	}

	function isc_ASCIIToUnicode($ascii)
	{
		for($unicode="",$a=0;$a<strlen($ascii);$a++)
			$unicode.=substr($ascii,$a,1).chr(0);
			return($unicode);
	}
	
