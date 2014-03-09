<?php

	if (!defined('ISC_BASE_PATH')) {
		die();
	}

	require_once(ISC_BASE_PATH.'/lib/xml.php');

	class ISC_PAGE
	{

		private $_pageid = 0;
		private $_pagetype = 0;
		private $_pagetitle = "";
		private $_pagefeed = "";
		private $_pagecontent = "";
		private $_pagekeywords = "";
		private $_pagedesc = "";
		private $_pagesearchkeywords = "";
		private $_pagemetatitle = "";
		private $_pagelayoutfile = "";
		private $_pageparentlist = "";
		private $_pagerow = null;
		private $_page_enable_optimizer = '';

		public function __construct($PageId=0, $IsHomePage=false, $PageRow=null)
		{
			if(!defined("ISC_ADMIN_CP")) {
				if($IsHomePage) {
					$this->LoadPageFromArray($PageRow);
				} else {
					$this->_SetPageData($PageId);
				}
			}
		}

		public function LoadPredefinedPages($content)
		{
			if(is_numeric(isc_strpos($content, "%%Syndicate%%"))) {
				if (!isset($GLOBALS['syndicateText'])) {
					$GLOBALS['syndicateText'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetPanelContent("Syndicate");
				}
				$content = str_replace("%%Syndicate%%", $GLOBALS['syndicateText'], $content);
			}
			return $content;
		}

		/**
		 * Load up the details for the page to be displayed.
		 *
		 * @param integer $PageId The ID number for the current page which should correspond to a row in the database
		 *
		 * @return void Doesn't return anything
		*/
		public function _SetPageData($PageId=0)
		{
			if((int)$PageId === 0) {
				if(isset($_REQUEST['pageid'])) {
					$_REQUEST['page_id'] = $_REQUEST['pageid'];
				}
				if(isset($_REQUEST['page_id'])) {
					$pageid = (int)$_REQUEST['page_id'];
					$query = sprintf("select * from [|PREFIX|]pages where pageid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($pageid));
				}
				else if(isset($GLOBALS['PathInfo'][1])) {
					$page = preg_replace('#\.html$#i', '', $GLOBALS['PathInfo'][1]);
					$page = $GLOBALS['ISC_CLASS_DB']->Quote(MakeURLNormal($page));
					$query = sprintf("select * from [|PREFIX|]pages where pagetitle='%s'", $page);
				}
				else {
					$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
					$GLOBALS['ISC_CLASS_404']->HandlePage();
					exit;
				}
			}
			else {
				$query = sprintf("select * from [|PREFIX|]pages where pageid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($PageId));
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if (!is_array($row) || empty($row)) {
				$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
				$GLOBALS['ISC_CLASS_404']->HandlePage();
				die();
			}

			$row['pagecontent'] = $this->LoadPredefinedPages($row['pagecontent']);
			$GLOBALS['ActivePage'] = $row['pageid'];
			$this->_pagerow   = &$row;
			$this->_pageid    = $row['pageid'];
			$this->_pagetype  = $row['pagetype'];
			$this->_pagetitle = $row['pagetitle'];
			$this->_pagefeed  = $row['pagefeed'];
			$this->_pagedesc  = $row['pagedesc'];
			$this->_pagesearchkeywords = $row['pagesearchkeywords'];
			$this->_pagecontent    = $row['pagecontent'];
			$this->_pagekeywords   = $row['pagekeywords'];
			$this->_pagemetatitle  = $row['pagemetatitle'];
			$this->_pageparentlist = $row['pageparentlist'];
			$this->_page_enable_optimizer = $row['page_enable_optimizer'];
			$this->setLayoutFile($row['pagelayoutfile']);

			// If the customer is not logged in and this page is set to customers only, then show an error message
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			if($row['pagecustomersonly'] == 1 && !$GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()) {
				$GLOBALS['ErrorMessage'] = sprintf(GetLang('ForbiddenToAccessPage'), $GLOBALS['ShopPathNormal']);
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
				exit;
			}
		}

		/**
		 * Load in the page data from an array passed in. Sets up all the member variables in the class, nothing is output.
		 *
		 * @param array $pageRow An associative array of a database row for a page
		 *
		 * @return void Doesn't return anything
		 */

		public function LoadPageFromArray($pageRow)
		{
			$row = $pageRow;
			$row['pagecontent'] = $this->LoadPredefinedPages($row['pagecontent']);
			$this->_pagerow     = &$row;
			$this->_pageid      = $row['pageid'];
			$this->_pagetype    = $row['pagetype'];
			$this->_pagetitle   = $row['pagetitle'];
			$this->_pagefeed    = @$row['pagefeed'];
			$this->_pagecontent = $row['pagecontent'];
			$this->_pagedesc    = $row['pagedesc'];
			$this->_pagesearchkeywords = $row['pagesearchkeywords'];
			$this->_pagekeywords  = $row['pagekeywords'];
			$this->_pagemetatitle = $row['pagemetatitle'];
			$this->_pageparentlist = $row['pageparentlist'];
			$this->_page_enable_optimizer = $row['page_enable_optimizer'];
			$this->setLayoutFile($row['pagelayoutfile']);
		}

		private function getLayoutFile()
		{
			$layoutFile = $this->_pagelayoutfile;

			if($GLOBALS['ISC_CLASS_TEMPLATE']->getTemplateFilePath($layoutFile)) {
				return $layoutFile;
			}
			else {
				return $this->_prodlayoutfile = 'page';
			}
		}

		private function setLayoutFile($layoutFile)
		{
			$this->_pagelayoutfile = str_replace(array(".html", ".htm"), "", $layoutFile);
		}

		public function GetPageId()
		{
			return $this->_pageid;
		}

		public function GetPageTitle()
		{
			return $this->_pagetitle;
		}

		public function GetPageParentList()
		{
			return $this->_pageparentlist;
		}

		public function HandlePage()
		{
			$action = "";
			if(isset($_REQUEST['action'])) {
				$action = isc_strtolower($_REQUEST['action']);
			}

			switch($action) {
				case "sendcontactform": {
					$this->SendContactForm();
					break;
				}
				default: {
					$this->ShowPage();
				}
			}
		}

		public function ShowPage()
		{
			if($this->_pageid > 0) {
				$GLOBALS['PageTitle'] = isc_html_escape($this->_pagetitle);

				// What kind of page is it?
				if($this->_pagetype == 0) {
					// It's a normal page
					$GLOBALS['PageContent'] = $this->_pagecontent;
				}
				else if($this->_pagetype == 2) {
					// It's an RSS feed
					$feed = $this->_LoadFeed($this->_pagefeed, 0, 600, md5($this->_pagetitle . $this->_pagefeed));

					if($feed) {
						$GLOBALS['PageContent'] = $feed;
					}
					else {
						$GLOBALS['PageContent'] = sprintf(GetLang('ErrLoadingRSSFeed'), $this->_pagefeed);
					}
				}
				else if($this->_pagetype == 3) {
					// It's a contact form
					$GLOBALS['PageContent'] = $this->_ContactForm();
				}
				else {
					ob_end_clean();
					$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
					$GLOBALS['ISC_CLASS_404']->HandlePage();
					exit;
				}

				if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
					$GLOBALS['PageContent'] = str_replace($GLOBALS['ShopPathNormal'], $GLOBALS['ShopPathSSL'], $GLOBALS['PageContent']);
				}

				if(!empty($this->_pagekeywords)) {
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetMetaKeywords($this->_pagekeywords);
				}

				if(!empty($this->_pagedesc)) {
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetMetaDescription($this->_pagedesc);
				}

				// If they've set a meta title, use it. Otherwise fall back to the 'page title' field.
				if(!empty($this->_pagemetatitle)) {
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->_pagemetatitle);
				} else {
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->_pagetitle);
				}

				$this->_insertOptimizerScripts();
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate($this->getLayoutFile());
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
			else {
				ob_end_clean();
				$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
				$GLOBALS['ISC_CLASS_404']->HandlePage();
				exit;
			}
		}

		/**
		*	Load up an RSS feed, parse its contents and return it.
		*/
		public function _LoadFeed($FeedURL, $NumEntries=0, $CacheTime=0, $FeedId="", $RSSFeedSnippet="", $helpLinks = false)
		{
			$reload = true;
			if($CacheTime > 0) {
				if($FeedId != "") {
					$FeedID = md5($FeedURL);
				}
				$reload = false;
				if(!is_dir(ISC_BASE_PATH."/cache/feeds")) {
					isc_mkdir(ISC_BASE_PATH."/cache/feeds/");
				}
				// Using a cached version that hasn't expired yet
				if(file_exists(ISC_BASE_PATH."/cache/feeds/".$FeedId) && filemtime(ISC_BASE_PATH."/cache/feeds/".$FeedId) > time()-$CacheTime) {
					$contents = file_get_contents(ISC_BASE_PATH."/cache/feeds/".$FeedId);
					// Cache was bad, recreate
					if(!$contents) {
						$reload = true;
					}
				}
				else {
					$reload = true;
				}
			}

			if ($reload === true) {
				$contents = PostToRemoteFileAndGetResponse($FeedURL);
				// Do we need to cache this version?
				if ($CacheTime > 0 && $contents != "") {
					@file_put_contents(ISC_BASE_PATH."/cache/feeds/".$FeedId, $contents);
				}
			}

			$output = "";
			$count = 0;

			// Could not load the feed, return an error
			if(!$contents) {
				return false;
			}


			// Silence errors to not polute out logs with peoples invalid XML feeds
			if($xml = @simplexml_load_string($contents)) {
				require_once(ISC_BASE_PATH . "/lib/xml.php");

				$rss = new ISC_XML();
				$entries = $rss->ParseRSS($xml);

				foreach($entries as $entry) {
					$GLOBALS['RSSTitle'] = $entry['title'];
					$GLOBALS['RSSDescription'] = $entry['description'];
					$GLOBALS['RSSLink'] = $entry['link'];

					if ($RSSFeedSnippet != "") {
						if ($helpLinks) {
							preg_match('#/questions/([0-9]+)/#si', $entry['link'], $matches);
							if (!empty($matches)) {
								$GLOBALS['RSSLink'] = $matches[1];
							}
						}
						if(defined('ISC_ADMIN_CP')) {
							$output .= Interspire_Template::getInstance('admin')->render('Snippets/'.$RSSFeedSnippet.'.html');
						}
						else {
							$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($RSSFeedSnippet);
						}
					} else {
						if(defined('ISC_ADMIN_CP')) {
							$output .= Interspire_Template::getInstance('admin')->render('Snippets/PageRSSItem.html');
						}
						else {
							$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("PageRSSItem");
						}
					}

					if($NumEntries > 0 && ++$count >= $NumEntries) {
						break;
					}
				}

				return $output;
			}
			else {
				return false;
			}
		}

		/**
		*	Build a contact form to include along with the page content
		*/
		public function _ContactForm()
		{

			// Load the captcha class
			$GLOBALS['ISC_CLASS_CAPTCHA'] = GetClass('ISC_CAPTCHA');

			// Did captcha fail?
			if(!empty($_POST)) {
				$GLOBALS['ContactName'] = isc_html_escape($_POST['contact_fullname']);
				$GLOBALS['ContactEmail'] = isc_html_escape($_POST['contact_email']);
				$GLOBALS['ContactCompanyName'] = isc_html_escape($_POST['contact_companyname']);
				$GLOBALS['ContactPhone'] = isc_html_escape($_POST['contact_phone']);
				$GLOBALS['ContactOrderNo'] = isc_html_escape($_POST['contact_orderno']);
				$GLOBALS['ContactRMA'] = isc_html_escape($_POST['contact_rma']);
				$GLOBALS['ContactQuestion'] = isc_html_escape($_POST['contact_question']);
				$GLOBALS['ContactFormError'] = GetLang('BadContactFormCaptcha');
			}
			else {
				// Hide the captcha error message
				$GLOBALS['HideFormError'] = "none";
			}

			// Which fields should we include in the form?
			$fields = $this->_pagerow['pagecontactfields'];

			if(!is_numeric(isc_strpos($fields, "fullname"))) {
				$GLOBALS['HideFullName'] = "none";
			}

			if(!is_numeric(isc_strpos($fields, "companyname"))) {
				$GLOBALS['HideCompanyName'] = "none";
			}

			if(!is_numeric(isc_strpos($fields, "phone"))) {
				$GLOBALS['HidePhone'] = "none";
			}

			if(!is_numeric(isc_strpos($fields, "orderno"))) {
				$GLOBALS['HideOrderNo'] = "none";
			}

			if(!is_numeric(isc_strpos($fields, "rma"))) {
				$GLOBALS['HideRMANo'] = "none";
			}

			$GLOBALS['PageId'] = $this->_pageid;

			if(GetConfig('CaptchaEnabled') == 0) {
				$GLOBALS['HideCaptcha'] = "none";
			}
			else {
				$GLOBALS['ISC_CLASS_CAPTCHA']->CreateSecret();
				$GLOBALS['CaptchaImage'] = $GLOBALS['ISC_CLASS_CAPTCHA']->ShowCaptcha();
			}

			$output = $this->_pagecontent;
			$output .= "<p />";

			// Do we need to integrate ActiveKB's ARS into this page?
			if(GetConfig('AKBIsConfigured') && GetConfig('ARSIntegrated') && in_array($this->_pageid, explode(",", GetConfig('ARSPageIds')))) {
				$GLOBALS['AKBPath'] = isc_html_escape(GetConfig('AKBPath'));
				$GLOBALS['ARSPanel'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetPanelContent("ActiveKB_ARS");
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("page_contact_form");
			$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
			return $output;
		}

		/**
		*	Send a contact form from a page
		*/
		public function SendContactForm()
		{
			// If the pageid or captcha is not set then just show the page and exit
			if (!isset($_POST['page_id']) || !isset($_POST['captcha'])) {
				$this->ShowPage();
				return;
			}

			// Load the captcha class
			$GLOBALS['ISC_CLASS_CAPTCHA'] = GetClass('ISC_CAPTCHA');

			// Load the form variables
			$page_id = (int)$_POST['page_id'];
			$this->_SetPageData($page_id);

			$captcha = $_POST['captcha'];

			if(GetConfig('CaptchaEnabled') == 0) {
				$captcha_check = true;
			}
			else {
				if(isc_strtolower($captcha) == isc_strtolower($GLOBALS['ISC_CLASS_CAPTCHA']->LoadSecret())) {
					// Captcha validation succeeded
					$captcha_check = true;
				}
				else {
					// Captcha validation failed
					$captcha_check = false;
				}
			}

			if($captcha_check) {
				// Valid captcha, let's send the form. The template used for the contents of the
				// email is page_contact_email.html
				$from = @$_POST['contact_fullname'];
				$GLOBALS['PageTitle'] = $this->_pagetitle;
				$GLOBALS['FormFieldList'] = "";

				$emailTemplate = FetchEmailTemplateParser();

				// Which fields should we include in the form?
				$fields = $this->_pagerow['pagecontactfields'];

				if(is_numeric(isc_strpos($fields, "fullname"))) {
					$GLOBALS['FormField'] = GetLang('ContactName');
					$GLOBALS['FormValue'] = isc_html_escape($_POST['contact_fullname']);
					$GLOBALS['FormFieldList'] .= $emailTemplate->GetSnippet("ContactFormField");
				}

				$GLOBALS['FormField'] = GetLang('ContactEmail');
				$GLOBALS['FormValue'] = isc_html_escape($_POST['contact_email']);
				$GLOBALS['FormFieldList'] .= $emailTemplate->GetSnippet("ContactFormField");

				if(is_numeric(isc_strpos($fields, "companyname"))) {
					$GLOBALS['FormField'] = GetLang('ContactCompanyName');
					$GLOBALS['FormValue'] = isc_html_escape($_POST['contact_companyname']);
					$GLOBALS['FormFieldList'] .= $emailTemplate->GetSnippet("ContactFormField");
				}

				if(is_numeric(isc_strpos($fields, "phone"))) {
					$GLOBALS['FormField'] = GetLang('ContactPhone');
					$GLOBALS['FormValue'] = isc_html_escape($_POST['contact_phone']);
					$GLOBALS['FormFieldList'] .= $emailTemplate->GetSnippet("ContactFormField");
				}

				if(is_numeric(isc_strpos($fields, "orderno"))) {
					$GLOBALS['FormField'] = GetLang('ContactOrderNo');
					$GLOBALS['FormValue'] = isc_html_escape($_POST['contact_orderno']);
					$GLOBALS['FormFieldList'] .= $emailTemplate->GetSnippet("ContactFormField");
				}

				if(is_numeric(isc_strpos($fields, "rma"))) {
					$GLOBALS['FormField'] = GetLang('ContactRMANo');
					$GLOBALS['FormValue'] = isc_html_escape($_POST['contact_rma']);
					$GLOBALS['FormFieldList'] .= $emailTemplate->GetSnippet("ContactFormField");
				}

				$GLOBALS['Question'] = nl2br(isc_html_escape($_POST['contact_question']));

				$GLOBALS['ISC_LANG']['ContactPageFormSubmitted'] = sprintf(GetLang('ContactPageFormSubmitted'), $GLOBALS['PageTitle']);

				$emailTemplate->SetTemplate("page_contact_email");
				$message = $emailTemplate->ParseTemplate(true);

				// Send the email
				require_once(ISC_BASE_PATH . "/lib/email.php");
				$obj_email = GetEmailClass();
				$obj_email->Set('CharSet', GetConfig('CharacterSet'));
				$obj_email->From($_POST['contact_email'], $from);
				$obj_email->ReplyTo = $_POST['contact_email'];
				$obj_email->Set("Subject", GetLang('ContactPageFormSubmitted'));
				$obj_email->AddBody("html", $message);
				$obj_email->AddRecipient($this->_pagerow['pageemail'], "", "h");
				$email_result = $obj_email->Send();

				// If the email was sent ok, show a confirmation message
				$GLOBALS['MessageTitle'] = $GLOBALS['PageTitle'];

				if($email_result['success']) {
					$GLOBALS['MessageIcon'] = "IcoInfo";
					$GLOBALS['MessageText'] = sprintf(GetLang('PageFormSent'), $GLOBALS['ShopPath']);
				}
				else {
					// Email error
					$GLOBALS['MessageIcon'] = "IcoError";
					$GLOBALS['MessageText'] = GetLang('PageFormNotSent');
				}

				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("message");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
			else {
				// Bad captcha, take them back to the form
				$this->ShowPage();
			}
		}


		private function _insertOptimizerScripts()
		{

			if(isset($_GET['optimizer'])){
				return;
			}

			//if optimizer is not enabled for this category
			if(!isset($this->_page_enable_optimizer) || $this->_page_enable_optimizer != 1) {
				return;
			}

			$optimizer = getClass('ISC_OPTIMIZER_PERPAGE');
			$optimizerDetails = $optimizer->getOptimizerDetails('page', $this->_pageid);
			if(empty($optimizerDetails)) {
				return;
			}

			$GLOBALS['PerPageOptimizerEnabled'] = 1;

			$GLOBALS['OptimizerControlScript'] = $optimizerDetails['optimizer_control_script'];
			$GLOBALS['OptimizerTrackingScript'] = $optimizerDetails['optimizer_tracking_script'];

			$GLOBALS['PageNameOptimizerScriptTag'] = '<script>utmx_section("PageName")</script>';
			$GLOBALS['PageNameOptimizerNoScriptTag'] = '</noscript>';

			$GLOBALS['PageDescriptionOptimizerScriptTag'] = '<script>utmx_section("PageDescription")</script>';
			$GLOBALS['PageDescriptionOptimizerNoScriptTag'] = '</noscript>';

		}


		/**
		 * Get the search SQL
		 *
		 * Method will return the search SQL
		 *
		 * @access public
		 * @param array $searchQuery The search query array. Currently will only understand the 'search_query' option
		 * @param int $start The optional start position of the result total. Default is 0
		 * @param int $limit The optional limit position of the result total. Default is -1 (no limit)
		 * @param string $fieldsToUse the optional fields to select from. Default is * (all) plus the score
		 * @param bool $includeOrder TRUE to include the ORDER BY statement. Default is TRUE
		 * @return string The search SQL on success, FALSE on error
		 */
		static public function searchForItemsSQL($searchQuery, $start=0, $limit=-1, $fieldsToUse="", $includeOrder=true)
		{
			if (!is_array($searchQuery)) {
				return false;
			}

			if (!array_key_exists("search_query", $searchQuery) || trim($searchQuery["search_query"]) == "") {
				return false;
			}

			if (CustomerIsSignedIn()) {
				$customerLoggedIn = "TRUE";
			} else {
				$customerLoggedIn = "FALSE";
			}

			$fullTextFields = array("ps.pagetitle", "ps.pagecontent", "ps.pagedesc", "ps.pagesearchkeywords");

			if (trim($fieldsToUse) == "") {
				$fieldsToUse = "SQL_CALC_FOUND_ROWS p.*, v.vendorfriendlyname ";
			}

			$fieldsToUse = trim($fieldsToUse);

			// Hard code in the score SQL
			if (substr($fieldsToUse, -1) !== ",") {
				$fieldsToUse .= ", ";
			}

			$fieldsToUse .= " (IF(p.pagetitle='" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "', 10000, 0) +
							   ((" . $GLOBALS["ISC_CLASS_DB"]->FullText(array("ps.pagetitle"), $searchQuery["search_query"], false) . ") * 10) +
								" . $GLOBALS["ISC_CLASS_DB"]->FullText($fullTextFields, $searchQuery["search_query"], false) . ") AS score";

			$query = "SELECT " . $fieldsToUse . "
						FROM [|PREFIX|]pages p
							INNER JOIN [|PREFIX|]page_search ps ON p.pageid = ps.pageid
							LEFT JOIN [|PREFIX|]vendors v ON p.pagevendorid = v.vendorid
						WHERE p.pagestatus = 1 AND (p.pagecustomersonly = 0 OR " . $customerLoggedIn . ")";

			$searchPart = array();

			if (GetConfig("SearchOptimisation") == "fulltext" || GetConfig("SearchOptimisation") == "both") {
				$searchPart[] = $GLOBALS["ISC_CLASS_DB"]->FullText($fullTextFields, $searchQuery["search_query"], true);
			}

			if (GetConfig("SearchOptimisation") == "like" || GetConfig("SearchOptimisation") == "both") {
				$searchPart[] = "p.pagetitle LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
				$searchPart[] = "p.pagesearchkeywords LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
			}

			$query .= " AND (" . implode(" OR ", $searchPart) . ") ";

			if ($includeOrder) {
				$query .= " ORDER BY score DESC";
			}

			if (is_numeric($limit) && $limit > 0) {
				if (is_numeric($start) && $start > 0) {
					$query .= " LIMIT " . (int)$start . "," . (int)$limit;
				} else {
					$query .= " LIMIT " . (int)$limit;
				}
			}

			return $query;
		}

		/**
		 * Build the search SQL used in the 'content' search
		 *
		 * Method will build the SQL used in the 'content' search
		 *
		 * @access public
		 * @param array $searchQuery The search query array. Currently will only understand the 'search_query' option
		 * @param int $start The optional start position of the result total. Default is 0
		 * @param int $limit The optional limit position of the result total. Default is -1 (no limit)
		 * @param bool $isFirst TRUE to specify that this is the first SELECT in the UNION. Default is TRUE
		 * @return string The search SQL on success, FALSE on error
		 */
		static public function searchForItemsSQLAsContent($searchQuery, $start=0, $limit=-1, $isFirst=true)
		{
			$fields = "";

			if ($isFirst) {
				$fields .= " SQL_CALC_FOUND_ROWS ";
			}

			$fields .= "'page' AS nodetype, p.pageid AS nodeid, p.pagetitle AS nodetitle, p.pagecontent AS nodecontent,
						p.pagelink AS nodelink, p.pagetype AS nodepagetype, p.pagevendorid AS nodevendorid,
						v.vendorfriendlyname AS nodevendorfriendlyname";

			return self::searchForItemsSQL($searchQuery, $start, $limit, $fields, false);
		}

		/**
		 * Search for pages
		 *
		 * Method will search for all the pages and return an array for page records
		 *
		 * @access public
		 * @param array $searchQuery The search query array. Currently will only understand the 'search_query' option
		 * @param int &$totalAmount The referenced variable to store in the total amount of the result
		 * @param int $start The optional start position of the result total. Default is 0
		 * @param int $limit The optional limit position of the result total. Default is -1 (no limit)
		 * @return array The array result set on success, FALSE on error
		 */
		static public function searchForItems($searchQuery, &$totalAmount, $start=0, $limit=-1)
		{
			if (!is_array($searchQuery)) {
				return false;
			}

			$totalAmount = 0;
			$query = self::searchForItemsSQL($searchQuery, $start, $limit);

			if (trim($query) == "") {
				return array();
			}

			$pages = array();
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

			if (!$row) {
				return array();
			}

			$totalAmount = $GLOBALS["ISC_CLASS_DB"]->FetchOne("SELECT FOUND_ROWS()");
			$pages[] = $row;

			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$pages[] = $row;
			}

			return $pages;
		}

		/**
		 * Build the searched item results HTML
		 *
		 * Method will build the searched item results HMTL. Method will work with the ISC_SEARCH class to get the results
		 * so make sure that the object is initialised and the DoSearch executed.
		 *
		 * @access public
		 * @return string The search item result HTML on success, empty string on error
		 */
		static public function buildSearchResultsHTML()
		{
			if (!isset($GLOBALS["ISC_CLASS_SEARCH"]) || !is_object($GLOBALS["ISC_CLASS_SEARCH"])) {
				return "";
			}

			$totalRecords = $GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("page");

			if ($totalRecords == 0) {
				return "";
			}

			$results = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("page");
			$totalPages = $GLOBALS['ISC_CLASS_SEARCH']->GetNumPages("page");
			$currentPage = $GLOBALS['ISC_CLASS_SEARCH']->GetPage("page");
			$resultHTML = "";

			if (!array_key_exists("results", $results) || !is_array($results["results"])) {
				return "";
			}

			foreach ($results["results"] as $page) {
				$resultHTML .= self::buildSearchResultHTML($page);
			}

			$resultHTML = trim($resultHTML);
			return $resultHTML;
		}

		/**
		 * Build the content searched item result HTML
		 *
		 * Method will build the content searched item result HMTL
		 *
		 * @access public
		 * @param array $page The content page search record array
		 * @return string The search item result HTML on success, empty string on error
		 */
		static public function buildContentSearchResultHTML($page)
		{
			if (!is_array($page)) {
				return "";
			}

			$map = array(
				"nodeid" => "pageid",
				"nodetitle" => "pagetitle",
				"nodepagetype" => "pagetype",
				"nodecontent" => "pagecontent",
				"nodelink" => "pagelink",
				"nodevendorid" => "pagevendorid",
				"nodevendorfriendlyname" => "vendorfriendlyname"
			);

			$remappedPage = array();

			foreach ($map as $fromKey => $toKey) {
				if (!array_key_exists($fromKey, $page)) {
					$remappedPage[$toKey] = "";
				} else {
					$remappedPage[$toKey] = $page[$fromKey];
				}
			}

			return self::buildSearchResultHTML($remappedPage);
		}

		/**
		 * Build the searched item result HTML
		 *
		 * Method will build the searched item result HMTL
		 *
		 * @access public
		 * @param array $oage The page search record array
		 * @return string The search item result HTML on success, empty string on error
		 */
		static public function buildSearchResultHTML($page)
		{
			if (!is_array($page) || !array_key_exists("pageid", $page)) {
				continue;
			}

			$GLOBALS["PageTitle"] = isc_html_escape($page["pagetitle"]);

			if ($page["pagetype"] == 1) {
				$GLOBALS["PageSmallContent"] = "";
				$GLOBALS["PageURL"] = $page["pagelink"];
			} else {
				$normalContent = strip_tags($page["pagecontent"]);
				$smallContent = substr($normalContent, 0, 199);

				if (strlen($normalContent) > 200 && substr($smallContent, -1, 1) !== ".") {
					$smallContent .= " ...";
				}

				$GLOBALS["PageSmallContent"] = $smallContent;

				$vendor = array();
				if (isId($page["pagevendorid"]) && trim($page["vendorfriendlyname"]) !== "") {
					$vendor = array(
									"vendorid" => $page["pagevendorid"],
									"vendorfriendlyname" => $page["vendorfriendlyname"]
					);
				}

				$GLOBALS["PageURL"] = PageLink($page["pageid"], $page["pagetitle"], $vendor);
			}

			return $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SearchResultPage");
		}

		/**
		 * Build the array of searched item results for the AJAX request
		 *
		 * Method will build an array of searched item results for the AJAX request. Method will work with the ISC_SEARCH
		 * class to get the results so make sure that the object is initialised and the DoSearch executed.
		 *
		 * Each key in the array will be the 'score' value (as a string) so it can be merged in with other results and can
		 * then be further sorted using any PHP array sorting functions, so output would be something like this:
		 *
		 * EG: return = array(10, // result count
		 *                    array(
		 *                        "12.345" => array(
		 *                                          0 => [page HTML]
		 *                                          1 => [page HTML]
		 *                                          2 => [page HTML]
		 *                                    ),
		 *                        "2.784" => array(
		 *                                          0 => [page HTML]
		 *                                    ),
		 *                        "6.242" => array(
		 *                                          0 => [page HTML]
		 *                                          1 => [page HTML]
		 *                                   )
		 *                    )
		 *              );
		 *
		 * @access public
		 * @return array An array with two values, first is total number of search results. Other is the search item results AJAX array on success, empty array on error
		 */
		static public function buildSearchResultsAJAX()
		{
			if (!isset($GLOBALS["ISC_CLASS_SEARCH"]) || !is_object($GLOBALS["ISC_CLASS_SEARCH"])) {
				return array();
			}

			$totalRecords = $GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("page");

			if ($totalRecords == 0) {
				return array(0, array());
			}

			$results = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("page");
			$ajaxArray = array();

			if (!array_key_exists("results", $results) || !is_array($results["results"])) {
				return array(0, array());
			}

			foreach ($results["results"] as $page) {
				if (!isset($page["score"])) {
					$page["score"] = 0;
				}

				$GLOBALS["PageTitle"] = isc_html_escape($page["pagetitle"]);

				if ($page["pagetype"] == 1) {
					$GLOBALS["PageURL"] = $page["pagelink"];
					$GLOBALS["PageSmallContent"] = "";
				} else {
					$normalContent = strip_tags($page["pagecontent"]);
					$smallContent = substr($normalContent, 0, 49);

					if (strlen($normalContent) > 50 && substr($smallContent, -1, 1) !== ".") {
						$smallContent .= " ...";
					}

					$GLOBALS["PageSmallContent"] = isc_html_escape($smallContent);

					$vendor = array();
					if (isId($page["pagevendorid"]) && trim($page["vendorfriendlyname"]) !== "") {
						$vendor = array(
										"vendorid" => $page["pagevendorid"],
										"vendorfriendlyname" => $page["vendorfriendlyname"]
						);
					}

					$GLOBALS["PageURL"] = PageLink($page["pageid"], $page["pagetitle"], $vendor);
				}

				$sortKey = (string)$page["score"];

				if (!array_key_exists($sortKey, $ajaxArray) || !is_array($ajaxArray[$sortKey])) {
					$ajaxArray[$sortKey] = array();
				}

				$ajaxArray[$sortKey][] = $GLOBALS["ISC_CLASS_TEMPLATE"]->GetSnippet("SearchResultAJAXPage");
			}

			return array($totalRecords, $ajaxArray);
		}
	}