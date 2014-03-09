<?php
GetLib('class.json');

class ISC_ADMIN_GIFTCERTIFICATES extends ISC_ADMIN_BASE
{
	public $_customSearch = array();

	public function __construct()
	{
		parent::__construct();
		// Initialise custom searches functionality
		require_once(dirname(__FILE__).'/class.customsearch.php');
		$GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH'] = new ISC_ADMIN_CUSTOMSEARCH('giftcertificates');
	}

	public function HandleToDo($Do)
	{
		if(!gzte11(ISC_LARGEPRINT)) {
			exit;
		}
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('giftcertificates');
		switch (isc_strtolower($Do))
		{
			case "creategiftcertificateview":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS["ISC_LANG"]['Home'] => "index.php",
						$GLOBALS["ISC_LANG"]['GiftCertificates'] => "index.php?ToDo=viewGiftCertificates",
						$GLOBALS["ISC_LANG"]['CreateGiftCertificateView'] => "index.php?ToDo=createGiftCertificateView"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->CreateView();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "deletecustomgiftcertificatesearch":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS["ISC_LANG"]['Home'] => "index.php",
						$GLOBALS["ISC_LANG"]['GiftCertificates'] => "index.php?ToDo=viewGiftCertificates"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->DeleteCustomSearch();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "customgiftcertificatesearch":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {
					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS["ISC_LANG"]['Home'] => "index.php",
						$GLOBALS["ISC_LANG"]['GiftCertificates'] => "index.php?ToDo=viewGiftCertificates",
						$GLOBALS["ISC_LANG"]['CustomView'] => "index.php?ToDo=customGiftCertificateSearch"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->CustomSearch();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "searchgiftcertificatesredirect":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS["ISC_LANG"]['Home'] => "index.php",
						$GLOBALS["ISC_LANG"]['GiftCertificates'] => "index.php?ToDo=viewGiftCertificates",
						$GLOBALS["ISC_LANG"]['SearchResults'] => "index.php?ToDo=searchGiftCertificates"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SearchGiftCertificatesRedirect();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "searchgiftcertificates":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS["ISC_LANG"]['Home'] => "index.php",
						$GLOBALS["ISC_LANG"]['GiftCertificates'] => "index.php?ToDo=viewGiftCertificates",
						$GLOBALS["ISC_LANG"]['SearchGiftCertificates'] => "index.php?ToDo=searchGiftCertificates"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SearchGiftCertificates();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "deletegiftcertificates":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS["ISC_LANG"]['Home'] => "index.php",
						$GLOBALS["ISC_LANG"]['GiftCertificates'] => "index.php?ToDo=viewGiftCertificates"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->DeleteGiftCertificates();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "editgiftcertificatetheme":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_GiftCertificates)) {
					$this->editGiftCertificateTheme();
				}
				break;
			case "savegiftcertificate":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {
					$this->saveGiftCertificate();
				}
				break;
			case "togglegiftcertificateenabled":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {
					$this->toggleGiftCertificateEnabled();
				}
				break;
			case "restoregiftcertificate":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {
					$this->restoreGiftCertificate();
				}
				break;
			case "examplegiftcertificate":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {
					$this->exampleGiftCertificate();
				}
				break;
			default:
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {
					if(isset($_GET['searchQuery'])) {
						$GLOBALS['BreadcrumEntries'] = array(
							$GLOBALS["ISC_LANG"]['Home'] => "index.php",
							$GLOBALS["ISC_LANG"]['GiftCertificates'] => "index.php?ToDo=viewGiftCertificates",
							$GLOBALS["ISC_LANG"]['SearchResults'] => "index.php?ToDo=viewGiftCertificates"
						);
					}
					else {
						$GLOBALS['BreadcrumEntries'] = array(
							$GLOBALS["ISC_LANG"]['Home'] => "index.php",
							$GLOBALS["ISC_LANG"]['GiftCertificates'] => "index.php?ToDo=viewGiftCertificates"
						);
					}

					if(!isset($_REQUEST['ajax'])) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					}
					$this->ManageGiftCertificates();
					if(!isset($_REQUEST['ajax'])) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					}
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
		}
	}

	private function ManageGiftCertificatesGrid(&$numGiftCertificates)
	{
		// Show a list of products in a table
		$page = 0;
		$start = 0;
		$numPages = 0;
		$GLOBALS['GiftCertificatesGrid'] = "";
		$GLOBALS['Nav'] = "";
		$catList = "";
		$max = 0;

		// Is this a custom search?
		if(isset($_GET['searchId'])) {
			$this->_customSearch = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($_GET['searchId']);
			$_REQUEST = array_merge($_REQUEST, (array)$this->_customSearch['searchvars']);

			// Override custom search sort fields if we have a requested field
			if(isset($_GET['sortField'])) {
				$_REQUEST['sortField'] = $_GET['sortField'];
			}
			if(isset($_GET['sortOrder'])) {
				$_REQUEST['sortOrder'] = $_GET['sortOrder'];
			}
		}
		else if(isset($_GET['searchQuery'])) {
			$GLOBALS['Query'] = isc_html_escape($_GET['searchQuery']);
		}

		if(isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == "asc") {
			$sortOrder = "asc";
		}
		else {
			$sortOrder = "desc";
		}

		$validSortFields = array('giftcertid', 'giftcertcode', 'giftcertto', 'giftcertfrom', 'giftcertcustid', 'giftcertamount', 'giftcertbalance', 'giftcertstatus', 'giftcertpurchasedate', 'giftcertexpiry', 'customername');
		if(isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
			$sortField = $_REQUEST['sortField'];
			SaveDefaultSortField("ManageGiftCertificates", $_REQUEST['sortField'], $sortOrder);
		} else {
			list($sortField, $sortOrder) = GetDefaultSortField("ManageGiftCertificates", "giftcertid", $sortOrder);
		}

		if(isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		} else {
			$page = 1;
		}

		// Build the pagination and sort URL
		$searchURL = '';
		foreach($_GET as $k => $v) {
			if($k == "sortField" || $k == "sortOrder" || $k == "page" || $k == "new" || $k == "ToDo" || $k == "SubmitButton1" || !$v) {
				continue;
			}
			$searchURL .= sprintf("&%s=%s", $k, urlencode($v));
		}

		$sortURL = sprintf("%s&amp;sortField=%s&amp;sortOrder=%s", $searchURL, $sortField, $sortOrder);

		$GLOBALS['SortURL'] = $sortURL;

		// Limit the number of gift certificates returned
		if ($page == 1) {
			$start = 1;
		} else {
			$start = ($page * ISC_GIFTCERTIFICATES_PER_PAGE) - (ISC_GIFTCERTIFICATES_PER_PAGE-1);
		}

		$start = $start-1;

		// Get the results for the query
		$certificateResult = $this->_GetGiftCertificatesList($start, $sortField, $sortOrder, $numGiftCertificates);

		$numPages = ceil($numGiftCertificates / ISC_GIFTCERTIFICATES_PER_PAGE);

		// Add the "(Page x of n)" label
		if($numGiftCertificates > ISC_GIFTCERTIFICATES_PER_PAGE) {
			$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
			$GLOBALS['Nav'] .= BuildPagination($numGiftCertificates, ISC_GIFTCERTIFICATES_PER_PAGE, $page, sprintf("index.php?ToDo=viewGiftCertificates%s", $sortURL));
		}
		else {
			$GLOBALS['Nav'] = "";
		}

		if(isset($_GET['searchQuery'])) {
			$query = $_GET['searchQuery'];
		} else {
			$query = "";
		}

		$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');
		$GLOBALS['SearchQuery'] = $query;
		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;

		$sortLinks = array(
			"Id" => "giftcertid",
			"CertificateAmount" => "giftcertamount",
			"CertificateBalance" => "giftcertbalance",
			"PurchaseDate" => "giftcertpurchasedate",
			"Status" => "giftcertstatus",
			"Code" => "giftcertcode",
			"Cust" => "customername"
		);
		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewGiftCertificates&amp;page=".$page, $sortField, $sortOrder);


		$GLOBALS['GiftCertificateStatusList'] = $this->GetGiftCertificateStatusOptions();

		// Display the gift certificates
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($certificateResult)) {
			$GLOBALS['GiftCertificateId'] = (int) $row['giftcertid'];
			$GLOBALS['GiftCertificateCode'] = isc_html_escape($row['giftcertcode']);
			$GLOBALS['GiftCertificateTo'] = isc_html_escape($row['giftcertto']);
			$GLOBALS['GiftCertificateToEmail'] = isc_html_escape($row['giftcerttoemail']);
			$GLOBALS['GiftCertificateFrom'] = isc_html_escape($row['giftcertfrom']);
			$GLOBALS['GiftCertificateFromEmail'] = isc_html_escape($row['giftcertfromemail']);
			$GLOBALS['GiftCertificateCustomerId'] = (int) $row['giftcertcustid'];
			$GLOBALS['GiftCertificateCustomerName'] = isc_html_escape($row['customername']);
			$GLOBALS['GiftCertificateAmount'] = FormatPrice($row['giftcertamount']);
			$GLOBALS['GiftCertificateBalance'] = FormatPrice($row['giftcertbalance']);
			$GLOBALS['GiftCertificatePurchaseDate'] = isc_date(GetConfig('DisplayDateFormat'), $row['giftcertpurchasedate']);
			if($row['giftcertexpirydate'] != 0) {
				$GLOBALS['GiftCertificateExpiryDate'] = isc_date(GetConfig('DisplayDateFormat'), $row['giftcertexpirydate']);
			}
			else {
				$GLOBALS['GiftCertificateExpiryDate'] = GetLang('NA');
			}

			// Something of this gift certificate has been sent so we need to show the expand icon
			if($row['giftcertbalance'] != $row['giftcertamount']) {
				$GLOBALS['ExpandIcon'] = '+';
			}
			else {
				$GLOBALS['ExpandIcon'] = '';
			}

			$GLOBALS['GiftCertificateStatusOptions'] = $this->GetGiftCertificateStatusOptions($row['giftcertstatus']);

			$GLOBALS['GiftCertificatesGrid'] .= $this->template->render('giftcertificates.manage.row.tpl');
		}
		return $this->template->render('giftcertificates.manage.grid.tpl');
	}

	private function ManageGiftCertificates($MsgDesc = "", $MsgStatus = "")
	{
		$GLOBALS['HideClearResults'] = "none";
		$status = array();
		$num_custom_searches = 0;

		// Fetch any results, place them in the data grid
		$GLOBALS['GiftCertificatesDataGrid'] = $this->ManageGiftCertificatesGrid($numCertificates);

		// Was this an ajax based sort? Return the table now
		if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
			echo $GLOBALS['GiftCertificatesDataGrid'];
			return;
		}

		if(isset($this->_customSearch['searchname'])) {
			$GLOBALS['ViewName'] = $this->_customSearch['searchname'];
		}
		else {
			$GLOBALS['ViewName'] = GetLang('AllGiftCertificates');
			$GLOBALS['HideDeleteViewLink'] = "none";
		}

		if(isset($_REQUEST['searchQuery']) || isset($_GET['searchId'])) {
			$GLOBALS['HideClearResults'] = "";
		}

		if($numCertificates > 0) {
			if($MsgDesc == "" && (isset($_REQUEST['searchQuery']) || isset($_GET['searchId']))) {
				if($numCertificates == 1) {
					$MsgDesc = GetLang('GiftCertificateSearchResultsBelow1');
				}
				else {
					$MsgDesc = sprintf(GetLang('GiftCertificateSearchResultsBelowX'), $numCertificates);
				}

				$MsgStatus = MSG_SUCCESS;
			}
		}

		// Get the custom search as option fields
		$GLOBALS['CustomSearchOptions'] = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->GetSearchesAsOptions(@$_GET['searchId'], $num_custom_searches, "AllGiftCertificates", "viewGiftCertificates", "customGiftCertificateSearch");

		if(!isset($_REQUEST['searchId'])) {
			$GLOBALS['HideDeleteCustomSearch'] = "none";
		} else {
			$GLOBALS['CustomSearchId'] = (int)$_REQUEST['searchId'];
		}

		// No gift certificatess
		if($numCertificates == 0) {
			$GLOBALS['DisplayGrid'] = "none";

			// Performing a search of some kind
			if(count($_GET) > 1) {
				if ($MsgDesc == "") {
					$GLOBALS['Message'] = MessageBox(GetLang('NoGiftCertificateResults'), MSG_ERROR);
				}
			} else {
				$GLOBALS['Message'] = MessageBox(GetLang('NoGiftCertificates'), MSG_SUCCESS);
				$GLOBALS['DisplaySearch'] = "none";
			}
			$GLOBALS['DisableDelete'] = "disabled=\"disabled\"";
		}

		if($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		$this->template->display('giftcertificates.manage.tpl');

	}

	private function _GetGiftCertificatesList($Start, $SortField, $SortOrder, &$NumGiftCertificates)
	{
		// Return a a MySQL result for a query about gift certificates.
		$fields = "g.*, CONCAT(c.custconfirstname, ' ', c.custconlastname) AS customername";

		// Are there any search parameters?
		$queryWhere = " WHERE 1=1 and ";
		$innerJoin = '';

		if(isset($_REQUEST['searchQuery']) && $_REQUEST['searchQuery'] != "") {
			$search_query = $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['searchQuery']);
			$queryWhere .= sprintf("(giftcertid='%d' OR giftcertcode LIKE '%%%s%%') and ", $GLOBALS['ISC_CLASS_DB']->Quote($search_query), $GLOBALS['ISC_CLASS_DB']->Quote($search_query));
		}

		if(isset($_REQUEST['orderId']) && $_REQUEST['orderId'] != 0) {
			$fields = "DISTINCT(giftcertid), " . $fields;
			$queryWhere .= sprintf("historderid='%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['orderId']));
			$innerJoin = "INNER JOIN [|PREFIX|]gift_certificate_history h ON (h.histgiftcertid=giftcertid)";
		}

		if(isset($_REQUEST['toEmail']) && $_REQUEST['toEmail'] != "") {
			$to_email = $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['toEmail']);
			$queryWhere .= sprintf("giftcerttoemail LIKE '%%%s%%') and ( ", $GLOBALS['ISC_CLASS_DB']->Quote($to_email), $GLOBALS['ISC_CLASS_DB']->Quote($to_email));
		}

		if(isset($_REQUEST['fromEmail']) && $_REQUEST['fromEmail'] != "") {
			$from_email = $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['fromEmail']);
			$queryWhere .= sprintf("giftcertfromemail LIKE '%%%s%%') and ( ", $GLOBALS['ISC_CLASS_DB']->Quote($from_email), $GLOBALS['ISC_CLASS_DB']->Quote($from_email));
		}

		if(isset($_REQUEST['customerId']) && $_REQUEST['customerId'] != "") {
			$customer_id = (int)$_REQUEST['customerId'];
			$queryWhere .= sprintf("giftcertcustid='%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($customer_id));
		}

		if(isset($_REQUEST['certificateStatus']) && $_REQUEST['certificateStatus'] != "") {
			$certificate_status = (int)$_REQUEST['certificateStatus'];
			$queryWhere .= sprintf("giftcertstatus='%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($certificate_status));
		}

		if(isset($_REQUEST['certificateFrom']) && isset($_REQUEST['certificateTo']) && $_REQUEST['certificateFrom'] != "" && $_REQUEST['certificateTo'] != "") {
			$certificate_from = (int)$_REQUEST['certificateFrom'];
			$certificate_to = (int)$_REQUEST['certificateTo'];
			$queryWhere .= sprintf("(giftcertid >= '%d' and giftcertid <= '%d') and ", $GLOBALS['ISC_CLASS_DB']->Quote($certificate_from), $GLOBALS['ISC_CLASS_DB']->Quote($certificate_to));
		}
		else if(isset($_REQUEST['certificateFrom']) && $_REQUEST['certificateFrom'] != "") {
			$certificate_from = (int)$_REQUEST['certificateFrom'];
			$queryWhere .= sprintf("giftcertid >= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($certificate_from));
		}
		else if(isset($_REQUEST['certificateTo']) && $_REQUEST['certificateTo'] != "") {
			$certificate_to = (int)$_REQUEST['certificateTo'];
			$queryWhere .= sprintf("giftcertid <= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($certificate_to));
		}

		if(isset($_REQUEST['amountFrom']) && isset($_REQUEST['amountTo']) && $_REQUEST['amountFrom'] != "" && $_REQUEST['amountTo'] != "") {
			$amount_from = FormatPrice($_REQUEST['amountFrom']);
			$amount_to = FormatPrice($_REQUEST['amountTo']);
			$queryWhere .= sprintf("(giftcertamount >= '%d' and giftcertamount <= '%d') and ", $GLOBALS['ISC_CLASS_DB']->Quote($amount_from), $GLOBALS['ISC_CLASS_DB']->Quote($amount_to));
		}
		else if(isset($_REQUEST['amountFrom']) && $_REQUEST['amountFrom'] != "") {
			$amount_from = FormatPrice($_REQUEST['amountFrom']);
			$queryWhere .= sprintf("giftcertamount >= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($amount_from));
		}
		else if(isset($_REQUEST['amountTo']) && $_REQUEST['amountTo'] != "") {
			$amount_to = FormatPrice($_REQUEST['amountTo']);
			$queryWhere .= sprintf("giftcertamount <= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($amount_to));
		}

		if(isset($_REQUEST['balanceFrom']) && isset($_REQUEST['balanceTo']) && $_REQUEST['balanceFrom'] != "" && $_REQUEST['balanceTo'] != "") {
			$balance_from = FormatPrice($_REQUEST['balanceFrom']);
			$balance_to = FormatPrice($_REQUEST['balanceTo']);
			$queryWhere .= sprintf("(giftcertbalance >= '%d' and giftcertbalance <= '%d') and ", $GLOBALS['ISC_CLASS_DB']->Quote($balance_from), $GLOBALS['ISC_CLASS_DB']->Quote($balance_to));
		}
		else if(isset($_REQUEST['balanceFrom']) && $_REQUEST['balanceFrom'] != "") {
			$balance_from = FormatPrice($_REQUEST['balanceFrom']);
			$queryWhere .= sprintf("giftcertbalance >= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($balance_from));
		}
		else if(isset($_REQUEST['balanceTo']) && $_REQUEST['balanceTo'] != "") {
			$balance_to = FormatPrice($_REQUEST['balanceTo']);
			$queryWhere .= sprintf("giftcertbalance <= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($balance_to));
		}

		// Limit results to a particular date range
		if(isset($_REQUEST['dateRange']) && $_REQUEST['dateRange'] != "") {
			$range = $_REQUEST['dateRange'];
			switch($range) {
				// Gift Certificates purchased within the last day
				case "today":
					$from_stamp = mktime(0, 0, 0, isc_date("m"), isc_date("d"), isc_date("Y"));
					break;
				// Gift Certificates purchased in the last 2 days
				case "yesterday":
					$from_stamp = mktime(0, 0, 0, isc_date("m"), isc_date("d")-1, isc_date("Y"));
					$to_stamp = mktime(0, 0, 0, isc_date("m"), isc_date("d")-1, isc_date("Y"));
					break;
				// Gift Certificates purchased in the last 24 hours
				case "day":
					$from_stamp = time()-60*60*24;
					break;
				// Gift Certificates purchased in the last 7 days
				case "week":
					$from_stamp = time()-60*60*24*7;
					break;
				// Gift Certificates purchased in the last 30 days
				case "month":
					$from_stamp = time()-60*60*24*30;
					break;
				// Gift Certificates purchased this month
				case "this_month":
					$from_stamp = mktime(0, 0, 0, isc_date("m"), 1, isc_date("Y"));
					break;
				// Gift Certificates purchased this year
				case "this_year":
					$from_stamp = mktime(0, 0, 0, 1, 1, isc_date("Y"));
					break;
				// Custom date
				default:
					if(isset($_REQUEST['fromDate']) && $_REQUEST['fromDate'] != "") {
						$from_date = $_REQUEST['fromDate'];
						$from_data = explode("/", $from_date);
						$from_stamp = mktime(0, 0, 0, $from_data[0], $from_data[1], $from_data[2]);
					}
					if(isset($_REQUEST['toDate']) && $_REQUEST['toDate'] != "") {
						$to_date = $_REQUEST['toDate'];
						$to_data = explode("/", $to_date);
						$to_stamp = mktime(0, 0, 0, $to_data[0], $to_data[1], $to_data[2]);
					}
			}

			if(isset($from_stamp)) {
				$queryWhere .= sprintf("giftcertpurchasedate >= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($from_stamp));
				unset($from_stamp);
			}
			if(isset($to_stamp)) {
				$queryWhere .= sprintf("giftcertpurchasedate <= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($to_stamp));
				unset($to_stamp);
			}
		}

		// Limit results to a particular date range
		if(isset($_REQUEST['expiryRange']) && $_REQUEST['expiryRange'] != "") {
			$range = $_REQUEST['expiryRange'];
			switch($range) {
				// Gift certificates that expired within the last day
				case "today":
					$from_stamp = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
					break;
				// Gift certificates that expired in the last 2 days
				case "yesterday":
					$from_stamp = mktime(0, 0, 0, date("m"), date("d")-1, date("Y"));
					$to_stamp = mktime(0, 0, 0, date("m"), date("d")-1, date("Y"));
					break;
				case "tomorrow":
					$from_stamp = mktime(0, 0, 0, date("m"), date("d")+1, date("Y"));
					$to_stamp = mktime(0, 0, 0, date("m"), date("d")+1, date("Y"));
					break;
				// Gift certificates that expired in the last 24 hours
				case "day":
					$from_stamp = time()-60*60*24;
					break;
				// Gift certificates that expire in the next 7 days
				case "week":
					$from_stamp = time()+60*60*24*7;
					break;
				// Gift certificates that expire in the next 30 days
				case "month":
					$from_stamp = time()+60*60*24*30;
					break;
				// Gift certificates that expired this month
				case "this_month":
					$from_stamp = mktime(0, 0, 0, date("m"), 1, date("Y"));
					break;
				// Gift certificates that expire next month
				case "next_month":
					$from_stamp = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
					break;
				//Gift certificates that expired this year
				case "this_year":
					$from_stamp = mktime(0, 0, 0, 1, 1, date("Y"));
					break;
				//Gift certificates that expire next year
				case "next_year":
					$from_stamp = mktime(0, 0, 0, 1, 1, date("Y")+1);
					break;

				// Custom date
				default:
					if(isset($_REQUEST['expiryFromDate']) && $_REQUEST['expiryFromDate'] != "") {
						$from_date = $_REQUEST['expiryFromDate'];
						$from_data = explode("/", $from_date);
						$from_stamp = mktime(0, 0, 0, $from_data[0], $from_data[1], $from_data[2]);
					}
					if(isset($_REQUEST['expiryToDate']) && $_REQUEST['expiryToDate'] != "") {
						$to_date = $_REQUEST['expiryToDate'];
						$to_data = explode("/", $to_date);
						$to_stamp = mktime(0, 0, 0, $to_data[0], $to_data[1], $to_data[2]);
					}
			}

			if(isset($from_stamp)) {
				$queryWhere .= sprintf("giftcertexpirydate >= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($from_stamp));
			}
			if(isset($to_stamp)) {
				$queryWhere .= sprintf("giftcertexpirydate <= '%d' and ", $GLOBALS['ISC_CLASS_DB']->Quote($to_stamp));
			}
		}

		// Strip out a trailing "or" if there is one
		$queryWhere= preg_replace('#and $#si', "", $queryWhere);

		$countQuery = sprintf("SELECT COUNT(giftcertid) FROM [|PREFIX|]gift_certificates g %s %s", $innerJoin, $queryWhere);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($countQuery);
		$NumGiftCertificates = $GLOBALS["ISC_CLASS_DB"]->FetchOne($result);

		$queryWhere .= sprintf(" order by %s %s", $SortField, $SortOrder);

		// Add the limit
		$queryWhere .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_GIFTCERTIFICATES_PER_PAGE);

		$query = sprintf("SELECT %s FROM [|PREFIX|]gift_certificates g %s LEFT JOIN [|PREFIX|]customers c ON (g.giftcertcustid=c.customerid) %s", $fields, $innerJoin, $queryWhere);

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		return $result;
	}

	private function DeleteGiftCertificates()
	{
		if(isset($_POST['certificates'])) {
			$certificateIds = implode("','", $GLOBALS['ISC_CLASS_DB']->Quote($_POST['certificates']));
			$query = sprintf("DELETE FROM [|PREFIX|]gift_certificates WHERE giftcertid IN ('%s')", $certificateIds);
			$GLOBALS['ISC_CLASS_DB']->Query($query);

			$query = sprintf("DELETE FROM [|PREFIX|]gift_certificate_history WHERE histgiftcertid IN ('%s')", $certificateIds);
			$GLOBALS['ISC_CLASS_DB']->Query($query);

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['certificates']));

			$this->ManageGiftCertificates(GetLang('GiftCertificatesDeleted'), MSG_SUCCESS);
		} else {
			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_GiftCertificates)) {
				$this->ManageGiftCertificates();
			} else {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			}
		}
	}

	private function SearchGiftCertificates()
	{
		$GLOBALS['GiftCertificateStatusOptions'] = $this->GetGiftCertificateStatusOptions();
		$this->template->display('giftcertificates.search.tpl');
	}

	/**
	*	This function checks to see if the user wants to save the search details as a custom search,
	*	and if they do one is created. They are then forwarded onto the search results
	*/
	private function SearchGiftCertificatesRedirect()
	{

		// Are we saving this as a custom search?
		if(isset($_GET['viewName']) && $_GET['viewName'] != '') {
			$search_id = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->SaveSearch($_GET['viewName'], $_GET);

			if($search_id > 0) {

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($search_id, $_GET['viewName']);

				ob_end_clean();
				header(sprintf("Location:index.php?ToDo=customGiftCertificateSearch&searchId=%d&new=true", $search_id));
				exit;
			}
			else {
				$this->ManageGiftCertificates(sprintf(GetLang('ViewAlreadExists'), $_GET['viewName']), MSG_ERROR);
			}
		}
		// Plain search
		else {
			$this->ManageGiftCertificates();
		}
	}

	/**
	*	Load a custom search
	*/
	private function CustomSearch()
	{

		$this->_customSearch = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($_GET['searchId']);
		$_REQUEST = array_merge($_REQUEST, $this->_customSearch['searchvars']);

		if(isset($_REQUEST['new'])) {
			$this->ManageGiftCertificates(GetLang('CustomSearchSaved'), MSG_SUCCESS);
		} else {
			$this->ManageGiftCertificates();
		}
	}

	private function DeleteCustomSearch()
	{

		if($GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->DeleteSearch($_GET['searchId'])) {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_GET['searchId']);

			$this->ManageGiftCertificates(GetLang('DeleteCustomSearchSuccess'), MSG_SUCCESS);
		}
		else {
			$this->ManageGiftCertificates(GetLang('DeleteCustomSearchFailed'), MSG_ERROR);
		}
	}

	/**
	*	Create a view for returns. Uses the same form as searching but puts the
	*	name of the view at the top and it's mandatory instead of optional.
	*/
	private function CreateView()
	{
		$GLOBALS['GiftCertificateStatusOptions'] = $this->GetGiftCertificateStatusOptions();
		$this->template->display('giftcertificates.view.tpl');
	}

	private function GetGiftCertificateStatusOptions($selected=0)
	{
		$certificateStatuses = array(
			1 => "GiftCertificateStatusPending",
			2 => "GiftCertificateStatusActive",
			3 => "GiftCertificateStatusDisabled",
			4 => "GiftCertificateStatusExpired"
		);

		if (GetConfig('CurrencyLocation') == 'right') {
			$GLOBALS['CurrencyTokenLeft'] = '';
			$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
		} else {
			$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
			$GLOBALS['CurrencyTokenRight'] = '';
		}

		$statuses = '';
		foreach($certificateStatuses as $id => $status) {
			$sel = '';
			if($id == $selected) {
				$sel = 'selected="selected"';
			}
			$statuses .= sprintf('<option value="%d" %s>%s</option>', $id, $sel, GetLang($status));
		}
		return $statuses;
	}

	private function toggleGiftCertificateEnabled()
	{
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : null;

		if(!$theme = ISC_GIFTCERTIFICATE_THEME::findById($id)) {
			return;
		}

		$result = $theme->toggleEnabled();
		$data = array('enabled' => $theme->isEnabled());

		ISC_JSON::output('', $result, $data);
	}

	private function editGiftCertificateTheme()
	{
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : null;

		if(!$theme = ISC_GIFTCERTIFICATE_THEME::findById($id)) {
			return;
		}

		$template = $theme->getTemplateContents();

		// Replace image path and global shop path variables so images
		// are displayed unbroken in the editor.
		$template = str_ireplace('%%GLOBAL_IMG_PATH%%', $GLOBALS['IMG_PATH'], $template);
		$template = str_ireplace('%%GLOBAL_SHOPPATH%%', $GLOBALS['ShopPath'], $template);

		$wysiwygOptions = array(
			'id'			=> 'giftCertificateEditor',
			'width'			=> '100%',
			'height'		=> '500px',
			'value'			=> $template,
			'editorOnly'	=> true,
			'delayLoad'		=> true,
			'validElementsSet'	=> 'all',
		);

		$editor = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);
		$data = array('editor' => $editor, 'loadFunc' => 'LoadEditor_giftCertificateEditor');

		ISC_JSON::output('', true, $data);
	}

	private function saveGiftCertificate()
	{
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : null;
		$html = !empty($_REQUEST['html']) ? $_REQUEST['html'] : "";

		if(!$theme = ISC_GIFTCERTIFICATE_THEME::findById($id)) {
			return;
		}

		if($theme->saveTemplateContents($html)){
			ISC_JSON::output(GetLang('GiftCertificateTemplateUpdated'), true);
			return;
		}

		ISC_JSON::output(GetLang('GiftCertificateTemplateUpdateError'), false);
	}

	private function restoreGiftCertificate()
	{
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : null;
		$editGiftCertificateUrl = 'index.php?ToDo=viewTemplates&forceTab=7';

		if(!$theme = ISC_GIFTCERTIFICATE_THEME::findById($id)) {
			$message = getLang('GiftCertificateRestoreErrorInvalid');
			flashMessage($message, MSG_ERROR, $editGiftCertificateUrl);
			return;
		}

		// Replacements for the success and fail messages
		$replacements = array('name' => $theme->name);

		if($theme->restoreFromMaster()) {
			$message = getLang('GiftCertificateRestored', $replacements);
			flashMessage($message, MSG_SUCCESS, $editGiftCertificateUrl);

			return;
		}

		$message = getLang('GiftCertificateRestoreError', $replacements);
		flashMessage($message, MSG_ERROR, $editGiftCertificateUrl);
	}

	/**
	 * Generates html for a gift certificate using sample data.
	 * Ouputs the result in JSON.
	 **/
	private function exampleGiftCertificate()
	{
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : null;
		$html = !empty($_REQUEST['html']) ? $_REQUEST['html'] : null;

		if($id) {
			// load a gift certificate theme by id
			if(!$theme = ISC_GIFTCERTIFICATE_THEME::findById($id)) {
				return;
			}
		}
		else if($html) {
			// build a temporary theme object using template html
			$theme = new ISC_GIFTCERTIFICATE_THEME();
			$theme->setTemplateContents($html);
		}
		else {
			// no id or template html passed, abort
			return;
		}

		$certificate = array(
			"giftcertto" => GetLang('GiftCertificateSampleTo'),
			"giftcerttoemail" => GetLang('GiftCertificateSampleToEmail'),
			"giftcertfrom" => GetLang('GiftCertificateSampleFrom'),
			"giftcertfromemail" => GetLang('GiftCertificateSampleFromEmail'),
			"giftcertmessage" => GetLang('GiftCertificateSampleMessage'),
			"giftcertcode" => GetLang('GiftCertificateSampleCode'),
			"giftcertamount" => GetLang('GiftCertificateSampleAmount'),
			"giftcertexpirydate" => GetLang('GiftCertificateSampleExpiryDate'),
			);

		$html = $theme->generateGiftCertificateHTML($certificate);
		$data = array('html' => $html);

		ISC_JSON::output('', true, $data);
	}
}