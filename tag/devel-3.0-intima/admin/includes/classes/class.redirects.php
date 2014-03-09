<?php

class ISC_ADMIN_REDIRECTS extends ISC_ADMIN_BASE
{

	public function HandleToDo($Do)
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('redirects');

		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Redirects)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$GLOBALS['BreadcrumEntries'] = array (
			GetLang('Home') => "index.php",
			GetLang('301Redirects') => "index.php?ToDo=viewRedirects",
		);

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->ManageRedirects();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	public function ManageRedirects()
	{
		$this->template->display('redirects.tpl');
	}

	public function getRedirectsTable()
	{
		GetLib('class.redirects');
		GetLib('class.urls');

		$perPage = 20;
		$page    = max((int)@$_GET['page'], 1);
		$start   = ($page * $perPage) - $perPage;
		$NumResults = 0;
		$GLOBALS['RedirectsGrid']  = "";
		$GLOBALS['RedirectPaging'] = "";

		$sortOrder = 'desc';

		if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'asc') {
			$sortOrder = 'asc';
		}

		$sortLinks = array(
			"OldUrl" => "r.redirectpath",
			"NewUrl" => "r.redirectassoctype",
			"RedirectId" => "r.redirectid",
		);

		if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
			$sortField = $_GET['sortField'];
		}
		else {
			$sortField = "r.redirectid";
		}

		$sortURL = '&sortField=' . $sortField . '&sortOrder=' . $sortOrder;
		$GLOBALS['SortURL'] = $sortURL;

		// Get the results for the query
		$redirectResult = $this->getRedirectRows($start, $sortField, $sortOrder, $perPage, $NumResults);
		$numPages = ceil($NumResults / $perPage);

		if(($start+1) > $NumResults && $start > 1) {
			$_GET['page'] = 1;
			return $this->getRedirectsTable();
		}

		// Add the "(Page x of n)" label
		if($NumResults > $perPage) {
			$GLOBALS['RedirectPaging'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
			$GLOBALS['RedirectPaging'] .= BuildPagination($NumResults, $perPage, $page, 'remote.php?remoteSection=redirects&w=getRedirectsTable' . $sortURL);
		}

		$GLOBALS['RedirectPaging'] = rtrim($GLOBALS['RedirectPaging'], ' |');
		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;

		BuildAdminSortingLinks($sortLinks, "remote.php?remoteSection=redirects&amp;w=getRedirectsTable&amp;page=".$page, $sortField, $sortOrder);

		if($NumResults > 0) {
			// Display the redirects
			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($redirectResult))
			{
				$GLOBALS['RedirectId'] = $row['redirectid'];
				$GLOBALS['OldURL'] = $row['redirectpath'];
				$redirectManual = $row['redirectmanual'];
				if ($redirectManual == '') {
					$redirectManual = GetLang('ClickHereToEnterAURL');
				}
				$GLOBALS['NewURL'] = $redirectManual;
				$GLOBALS['RedirectTypeAutoSelected'] = "selected='selected'";
				$GLOBALS['RedirectTypeManualSelected'] = '';
				$GLOBALS['RedirectTypeManualDisplay'] = "display: none;";
				$GLOBALS['RedirectTypeAutoDisplay'] = "";
				$GLOBALS['NewURLTitle'] = '';
				$GLOBALS['LinkerTitle'] = GetLang('ChangeLink');
				$GLOBALS['RedirectTestLink'] = GetConfig('ShopPath') . $row['redirectpath'];

				$row['redirectassoctype'] = (int)$row['redirectassoctype'];

				switch($row['redirectassoctype']) {
					case ISC_REDIRECTS::REDIRECT_TYPE_NOREDIRECT:
					case ISC_REDIRECTS::REDIRECT_TYPE_MANUAL:
						$GLOBALS['RedirectTypeManualSelected'] = "selected='selected'";
						$GLOBALS['RedirectTypeAutoSelected'] = "";
						$GLOBALS['RedirectTypeManualDisplay'] = "";
						$GLOBALS['RedirectTypeAutoDisplay'] = "display: none;";
						$GLOBALS['LinkerTitle'] = GetLang('BrowseForLink');
						break;
					case ISC_REDIRECTS::REDIRECT_TYPE_PRODUCT:
						$urlInfo = ISC_URLS::getProductUrl($row['redirectassocid'], true);
						if(is_array($urlInfo)  && !empty($urlInfo['title'])) {
							$GLOBALS['NewURL'] = $urlInfo['url'];
							$GLOBALS['NewURLTitle'] = GetLang('Product') . ': ' . $urlInfo['title'];
						}
						break;
					case ISC_REDIRECTS::REDIRECT_TYPE_CATEGORY:
						$urlInfo = ISC_URLS::getCategoryUrl($row['redirectassocid'], true);
						if(is_array($urlInfo)  && !empty($urlInfo['title'])) {
							$GLOBALS['NewURL'] = $urlInfo['url'];
							$GLOBALS['NewURLTitle'] = GetLang('Category') . ': ' .  $urlInfo['title'];
						}
						break;
					case ISC_REDIRECTS::REDIRECT_TYPE_BRAND:
						$urlInfo = ISC_URLS::getBrandUrl($row['redirectassocid'], true);
						if(is_array($urlInfo)  && !empty($urlInfo['title'])) {
							$GLOBALS['NewURL'] = $urlInfo['url'];
							$GLOBALS['NewURLTitle'] = GetLang('Brand') . ': ' . $urlInfo['title'];
						}
						break;
					case ISC_REDIRECTS::REDIRECT_TYPE_PAGE:
						$urlInfo = ISC_URLS::getPageUrl($row['redirectassocid'], true);
						if(is_array($urlInfo)  && !empty($urlInfo['title'])) {
							$GLOBALS['NewURL'] = $urlInfo['url'];
							$GLOBALS['NewURLTitle'] = GetLang('Page') . ': ' . $urlInfo['title'];
						}
						break;
				}

				$GLOBALS['RedirectsGrid'] .= $this->template->render('redirects.row.tpl');
			}
		}

		return array($this->template->render('redirects.grid.tpl'), $NumResults);
	}

	public function getRedirectRows($Start, $SortField, $SortOrder, $perPage, &$NumResults)
	{
		$query = "SELECT * FROM `[|PREFIX|]redirects` r";
		$countQuery = "SELECT COUNT(redirectid) FROM `[|PREFIX|]redirects` r";

		$query .= " ORDER BY ".$SortField." ".$SortOrder;

		$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
		$NumResults = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		// Add the limit
		$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, $perPage);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

		return $result;
	}
}