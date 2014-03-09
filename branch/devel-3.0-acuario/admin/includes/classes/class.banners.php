<?php

	class ISC_ADMIN_BANNERS extends ISC_ADMIN_BASE
	{
		/**
		 * The constructor.
		 */
		public function __construct()
		{
			parent::__construct();
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('banners');
		}

		public function HandleToDo($Do)
		{
			switch (isc_strtolower($Do)) {
				case "editbanner2": {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Banners)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Banners') => "index.php?ToDo=viewBanners");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditBannerStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				}
				case "editbanner": {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Banners)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Banners') => "index.php?ToDo=viewBanners", GetLang('EditBanner') => "index.php?ToDo=editBanner");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditBannerStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				}
				case "deletebanners": {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Banners)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Banners') => "index.php?ToDo=viewBanners");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteBanners();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				}
				case "editbannervisibility": {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Banners)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Banners') => "index.php?ToDo=viewBanners");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditBannerVisibility();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				}
				case "createbanner2": {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Banners)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Banners') => "index.php?ToDo=viewBanners");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddBannerStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				}
				case "createbanner": {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Banners)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Banners') => "index.php?ToDo=viewBanners", GetLang('CreateBanner') => "index.php?ToDo=addBanner");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddBannerStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				}
				default: {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Banners)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Banners') => "index.php?ToDo=viewBanners");

						if (!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}

						$this->ManageBanners();

						if (!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
			}
		}

		private function _GetBannerList($Query, $SortField, $SortOrder, &$NumResults)
		{
			$Query = trim($Query);

			$query = "
				SELECT *,
					CASE page
						WHEN 'category_page' THEN (SELECT catname FROM [|PREFIX|]categories WHERE categoryid=catorbrandid)
						WHEN 'brand_page' THEN (SELECT brandname FROM [|PREFIX|]brands WHERE brandid=catorbrandid)
						ELSE ''
					END AS location_name
				FROM [|PREFIX|]banners
			";

			$countQuery = "SELECT COUNT(bannerid) FROM [|PREFIX|]banners";

			$queryWhere = ' WHERE 1=1 ';
			if($Query != '') {
				$queryWhere .= " AND name LIKE '%".$GLOBALS['ISC_CLASS_DB']->Quote($Query)."%'";
			}

			$query .= $queryWhere;
			$countQuery .= $queryWhere;

			// Add the sorting options
			$query .= sprintf("order by %s %s", $SortField, $SortOrder);

			$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
			$NumResults = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);


			if($NumResults > 0) {
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			} else {
				$result = false;
			}
			return $result;
		}

		private function ManageBannersGrid(&$numBanners)
		{
			$GLOBALS['BannerGrid'] = '';
			$GLOBALS['Nav'] = '';
			$searchURL = '';

			if (isset($_GET['searchQuery'])) {
				$query = $_GET['searchQuery'];
				$GLOBALS['Query'] = isc_html_escape($query);
				$searchURL .= '&amp;searchQuery='.urlencode($query);
			} else {
				$query = "";
				$GLOBALS['Query'] = "";
			}

			if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'desc') {
				$sortOrder = 'desc';
			} else {
				$sortOrder = "asc";
			}

			$sortLinks = array(
				"Name" => "name",
				"Location" => "page",
				"Date" => "datecreated",
				"Status" => "status"
			);

			if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("ManageBanners", $_REQUEST['sortField'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("ManageBanners", "name", $sortOrder);
			}

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			} else {
				$page = 1;
			}

			$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
			$GLOBALS['SortURL'] = $sortURL;

			// Get the results for the query
			$bannerResult = $this->_GetBannerList($query, $sortField, $sortOrder, $numBanners);

			if(!$numBanners) {
				return '';
			}

			$GLOBALS['SearchQuery'] = isc_html_escape($query);
			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewBanners&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);


			$GLOBALS['NameSortUpLink'] = sprintf("index.php?ToDo=viewBanners&searchQuery=%s&&sortField=name&sortOrder=asc", $query);
			$GLOBALS['NameSortDownLink'] = sprintf("index.php?ToDo=viewBanners&searchQuery=%s&sortField=name&sortOrder=desc", $query);

			$GLOBALS['LocationSortUpLink'] = sprintf("index.php?ToDo=viewBanners&searchQuery=%s&&sortField=page&sortOrder=asc", $query);
			$GLOBALS['LocationSortDownLink'] = sprintf("index.php?ToDo=viewBanners&searchQuery=%s&sortField=page&sortOrder=desc", $query);

			$GLOBALS['DateSortUpLink'] = sprintf("index.php?ToDo=viewBanners&searchQuery=%s&&sortField=datecreated&sortOrder=asc", $query);
			$GLOBALS['DateSortDownLink'] = sprintf("index.php?ToDo=viewBanners&searchQuery=%s&sortField=datecreated&sortOrder=desc", $query);

			$GLOBALS['StatusSortUpLink'] = sprintf("index.php?ToDo=viewBanners&searchQuery=%s&&sortField=status&sortOrder=asc", $query);
			$GLOBALS['StatusSortDownLink'] = sprintf("index.php?ToDo=viewBanners&searchQuery=%s&sortField=status&sortOrder=desc", $query);

			while ($banner = $GLOBALS["ISC_CLASS_DB"]->Fetch($bannerResult)) {
				$GLOBALS['BannerId'] = (int) $banner['bannerid'];
				$GLOBALS['Name'] = isc_html_escape($banner['name']);

				$GLOBALS['Location'] = "";

				switch ($banner['page']) {
					case "home_page": {
						$GLOBALS['Location'] = sprintf("<a target='_blank' href='../'>%s</a>", GetLang('BannerHomePage'));
						break;
					}
					case "category_page": {
						$GLOBALS['Location'] = sprintf("<a target='_blank' href='%s'>%s %s</a>", CatLink($banner['catorbrandid'], $banner["location_name"]), $banner['name'], GetLang('BannerCategory'));
						break;
					}
					case "brand_page": {
						$GLOBALS['Location'] = sprintf("<a target='_blank' href='%s'>%s %s</a>", BrandLink($banner['location_name']), $banner["location_name"], GetLang('ProductsPage'));
						break;
					}
					case "search_page": {
						$GLOBALS['Location'] = sprintf("<a target='_blank' href='../search.php?mode=advanced'>%s</a>", GetLang('SearchResultsPage'));
						break;
					}
				}

				if ($banner['location'] == "top") {
					$GLOBALS['Location'] .= sprintf(" (%s)", GetLang('BannerTopOfPage'));
				}
				else {
					$GLOBALS['Location'] .= sprintf(" (%s)", GetLang('BannerBottomOfPage'));
				}

				$GLOBALS['Date'] = isc_date(GetConfig('ExportDateFormat'), $banner['datecreated']);

				if ($banner['status'] == 1) {
					$GLOBALS['Visible'] = sprintf("<a title='%s' href='index.php?ToDo=editBannerVisibility&amp;bannerId=%d&amp;visible=0'><img border='0' src='images/tick.gif'></a>", GetLang('ClickToHideBanner'), $banner['bannerid']);
				}
				else {
					$GLOBALS['Visible'] = sprintf("<a title='%s' href='index.php?ToDo=editBannerVisibility&amp;bannerId=%d&amp;visible=1'><img border='0' src='images/cross.gif'></a>", GetLang('ClickToShowBanner'), $banner['bannerid']);
				}

				$GLOBALS['BannerGrid'] .= $this->template->render('banner.manage.row.tpl');
			}
			return $this->template->render('banners.manage.grid.tpl');
		}

		private function ManageBanners()
		{
			// Fetch any results, place them in the data grid
			$numBanners = 0;
			$GLOBALS['BannersGrid'] = $this->ManageBannersGrid($numBanners);

			// Was this an ajax based sort? Return the table now
			if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['BannersGrid'];
				return;
			}

			$flashMessages = GetFlashMessages();
			if(is_array($flashMessages)) {
				$GLOBALS['Message'] = '';
				foreach($flashMessages as $flashMessage) {
					$GLOBALS['Message'] .= MessageBox($flashMessage['message'], $flashMessage['type']);
				}
			}

			// Do we need to disable the delete button?
			if ($numBanners == 0) {
				$GLOBALS['DisableDelete'] = "DISABLED";
			}

			if ($numBanners == 0) {
				$GLOBALS['DisplayGrid'] = "none";

				if (count($_GET) == 1) {
					$GLOBALS['Message'] = MessageBox(GetLang('NoBanners'), MSG_SUCCESS);
					$GLOBALS['DisplaySearch'] = "none";
					$GLOBALS['DisableDelete'] = "DISABLED";
				} else {
					$GLOBALS['Message'] = MessageBox(GetLang('NoBannerResults'), MSG_SUCCESS);
					$GLOBALS['DisplaySearch'] = "";
					$GLOBALS['DisableDelete'] = "";
				}
			}

			$this->template->display('banners.manage.tpl');
		}

		private function GetDays($Selected=0)
		{
			$output = "";

			for ($i = 1; $i <= 31; $i++) {
				if ($i == $Selected) {
					$sel = "selected=\"selected\"";
				} else {
					$sel = "";
				}

				$output .= sprintf("<option value='%d' %s>%d</option>", $i, $sel, $i);
			}

			return $output;
		}

		private function GetMonths($Selected=0)
		{
			$output = "";

			for ($i = 1; $i <= 12; $i++) {
				if ($i == $Selected) {
					$sel = "selected=\"selected\"";
				} else {
					$sel = "";
				}

				$stamp = mktime(0, 0, 0, $i, 1, 2000);
				$month = date("M", $stamp);
				$output .= sprintf("<option value='%d' %s>%s</option>", $i, $sel, $month);
			}

			return $output;
		}

		private function GetYears($Selected=0)
		{
			$output = "";

			for ($i = isc_date("Y"); $i <= isc_date("Y")+3; $i++) {
				if ($i == $Selected) {
					$sel = "selected=\"selected\"";
				} else {
					$sel = "";
				}

				$output .= sprintf("<option value='%d' %s>%d</option>", $i, $sel, $i);
			}

			return $output;
		}

		private function AddBannerStep1()
		{
			$GLOBALS['FormAction'] = "createBanner2";
			$GLOBALS['Title'] = GetLang('CreateBanner');
			$GLOBALS['ISC_CLASS_ADMIN_EDITOR'] = GetClass('ISC_ADMIN_EDITOR');
			$wysiwygOptions = array(
				'id'		=> 'wysiwyg',
			);
			$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);
			$GLOBALS['Visible']  = 'checked="checked"';
			$GLOBALS['IsAlwaysDate'] = 'checked="checked"';
			$GLOBALS['FromDayOptions'] = $this->GetDays(isc_date("d"));
			$GLOBALS['FromMonthOptions'] = $this->GetMonths(isc_date("m"));
			$GLOBALS['FromYearOptions'] = $this->GetYears(isc_date("Y"));
			$GLOBALS['ToDayOptions'] = $this->GetDays(isc_date("d"));

			// Set the "to" date to a month in advance
			if (isc_date("m") == 12) {
				$to_month = 1;
				$to_year = isc_date("Y")+1;
			}
			else {
				$to_month = isc_date("m")+1;
				$to_year = isc_date("Y");
			}

			$GLOBALS['ToMonthOptions'] = $this->GetMonths($to_month);
			$GLOBALS['ToYearOptions'] = $this->GetYears($to_year);

			$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
			$GLOBALS['CategoryOptions'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions(0, "<option %s value='%d'>%s</option>", "selected=\"selected\"", "", false);

			$GLOBALS['ISC_CLASS_ADMIN_BRANDS'] = GetClass('ISC_ADMIN_BRANDS');
			$GLOBALS['BrandOptions'] = $GLOBALS['ISC_CLASS_ADMIN_BRANDS']->GetBrandsAsOptions();

			$this->template->display('banner.form.tpl');
		}

		/**
		*	Add a banner to the database. If $ExistingBannerId is passed in the deleted
		*	the existing banner before adding a new one.
		*/
		private function _CommitBanner($ExistingBannerId = 0)
		{
			$name = $_POST['bannername'];
			if(isset($_POST['wysiwyg_html'])) {
				$content = $_POST['wysiwyg_html'];
			}
			else {
				$content = $_POST['wysiwyg'];
			}
			$page = $_POST['bannerpage'];
			$category = (int)$_POST['bannercat'];
			$brand = (int)$_POST['bannerbrand'];
			$bannerdate = $_POST['bannerdate'];
			$from_day = (int)$_POST["from_day"];
			$from_month = (int)$_POST["from_month"];
			$from_year = (int)$_POST["from_year"];
			$to_day = (int)$_POST["to_day"];
			$to_month = (int)$_POST["to_month"];
			$to_year = (int)$_POST["to_year"];

			if (isset($_POST['bannerstatus'])) {
				$status = 1;
			} else {
				$status = 0;
			}

			$location = $_POST['bannerloc'];

			if ($_POST['bannerpage'] == "category_page") {
				$catorbrandid = $category;
			}
			else if ($_POST['bannerpage'] == "brand_page") {
				$catorbrandid = $brand;
			}
			else {
				$catorbrandid = 0;
			}

			if ($bannerdate == "custom") {
				$from_stamp = mktime(0, 0, 0, $from_month, $from_day, $from_year);
				$to_stamp = mktime(0, 0, 0, $to_month, $to_day, $to_year);
			}
			else {
				$from_stamp = 0;
				$to_stamp = 0;
			}

			// Build the insert query
			$banner = array(
				"name" => $name,
				"content" => $content,
				"page" => $page,
				"catorbrandid" => $catorbrandid,
				"location" => $location,
				"datecreated" => time(),
				"datetype" => $bannerdate,
				"datefrom" => $from_stamp,
				"dateto" => $to_stamp,
				"status" => $status
			);

			// Creating a new banner
			if ($ExistingBannerId == 0) {
				return $GLOBALS['ISC_CLASS_DB']->InsertQuery("banners", $banner);
			}
			// Updating an existing banner
			else {
				if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery("banners", $banner, "bannerid='".$GLOBALS['ISC_CLASS_DB']->Quote((int)$ExistingBannerId)."'")) {
					return $ExistingBannerId;
				}
				else {
					return false;
				}
			}
		}

		private function AddBannerStep2()
		{
			if (($bannerid = $this->_CommitBanner())) {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($bannerid, $_POST['bannername']);
				FlashMessage(GetLang('AddBannerSuccess'), MSG_SUCCESS, 'index.php?ToDo=viewBanners');
			}
			else {
				FlashMessage(GetLang('AddBannerFailed'), MSG_ERROR, 'index.php?ToDo=viewBanners');
			}
		}

		private function EditBannerVisibility()
		{
			if (isset($_GET['bannerId']) && isset($_GET['visible'])) {
				$banner_id = (int)$_GET['bannerId'];
				$visible = (int)$_GET['visible'];

				$banner = $this->GetBannerById($banner_id);

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($banner_id, $banner['name']);

				$updatedBanner = array(
					"status" => $visible
				);

				if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery("banners", $updatedBanner, "bannerid='".$GLOBALS['ISC_CLASS_DB']->Quote($banner_id)."'")) {
					FlashMessage(GetLang('ChangeBannerStatusSuccess'), MSG_SUCCESS, 'index.php?ToDo=viewBanners');
				}
				else {
					FlashMessage(sprintf(GetLang('ChangeBannerStatusFailed'), $GLOBALS["ISC_CLASS_DB"]->Error()), MSG_ERROR, 'index.php?ToDo=viewBanners');
				}
			}
			else {
				$this->ManageBanners();
			}
		}

		private function DeleteBanners()
		{
			if (isset($_POST['banner'])) {
				$banner_ids = implode(",", array_map('intval', $_POST['banner']));

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['banner']));

				$query = sprintf("delete from [|PREFIX|]banners where bannerid in (%s)", $banner_ids);

				if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
					FlashMessage(sprintf(GetLang('BannersDeletedFailed'), $GLOBALS["ISC_CLASS_DB"]->Error()), MSG_ERROR, 'index.php?ToDo=viewBanners');
				}
				else {
					FlashMessage(sprintf(GetLang('BannersDeletedSuccessfully'), $GLOBALS["ISC_CLASS_DB"]->Error()), MSG_SUCCESS, 'index.php?ToDo=viewBanners');
				}
			}
			else {
				$this->ManageBanners();
			}
		}

		private function GetBannerById($BannerId)
		{
			$query = sprintf("select * from [|PREFIX|]banners where bannerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($BannerId));
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

			if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				return $row;
			} else {
				return false;
			}
		}

		private function EditBannerStep1()
		{
			if (isset($_GET['bannerId']) && is_numeric($_GET['bannerId'])) {
				$banner_id = (int)$_GET['bannerId'];

				if ($banner = $this->GetBannerById($banner_id)) {

					$GLOBALS['BannerId'] = (int) $banner_id;
					$GLOBALS['FormAction'] = "editBanner2";
					$GLOBALS['Title'] = GetLang('EditBanner');
					$GLOBALS['BannerName'] = isc_html_escape($banner['name']);

					$GLOBALS['ISC_CLASS_ADMIN_EDITOR'] = GetClass('ISC_ADMIN_EDITOR');
					$wysiwygOptions = array(
						'id'	=> 'wysiwyg',
						'value'	=> $banner['content']
					);
					$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

					if ($banner['status'] == 1) {
						$GLOBALS['Visible']  = 'checked="checked"';
					}

					switch ($banner['page']) {
						case "home_page": {
							$GLOBALS['IsHomePage'] = 'checked="checked"';
							$GLOBALS['SelectedJS'] = "selected_page='home_page';";
							break;
						}
						case "category_page": {
							$GLOBALS['IsCategory'] = 'checked="checked"';
							$GLOBALS['ShowCategory'] = "$('#page_category').css('display', '');";
							$GLOBALS['SelectedJS'] = "selected_page='category_page';";
							break;
						}
						case "brand_page": {
							$GLOBALS['IsBrand'] = 'checked="checked"';
							$GLOBALS['ShowBrand'] = "$('#page_brand').css('display', '');";
							$GLOBALS['SelectedJS'] = "selected_page='brand_page';";
							break;
						}
						case "search_page": {
							$GLOBALS['IsSearch'] = 'checked="checked"';
							$GLOBALS['SelectedJS'] = "selected_page='search_page';";
							break;
						}
					}

					$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');

					// Is it a per-category banner?
					if ($banner['page'] == "category_page") {
						$GLOBALS['CategoryOptions'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions(array($banner['catorbrandid']), "<option %s value='%d'>%s</option>", "selected=\"selected\"", "", false);
					}
					else {
						$GLOBALS['CategoryOptions'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions(0, "<option %s value='%d'>%s</option>", "selected=\"selected\"", "", false);
					}

					// Is it a per-brand banner?
					$GLOBALS['ISC_CLASS_ADMIN_BRANDS'] = GetClass('ISC_ADMIN_BRANDS');
					if ($banner['page'] == "brand_page") {
						$GLOBALS['BrandOptions'] = $GLOBALS['ISC_CLASS_ADMIN_BRANDS']->GetBrandsAsOptions($banner['catorbrandid']);
					}
					else {
						$GLOBALS['BrandOptions'] = $GLOBALS['ISC_CLASS_ADMIN_BRANDS']->GetBrandsAsOptions();
					}

					if ($banner['datetype'] == "always") {
						$GLOBALS['IsAlwaysDate'] = 'checked="checked"';
					}
					else {
						$GLOBALS['IsCustomDate'] = 'checked="checked"';
					}

					if ($banner['datetype'] == "always") {
						$GLOBALS['FromDayOptions'] = $this->GetDays(isc_date("d"));
						$GLOBALS['FromMonthOptions'] = $this->GetMonths(isc_date("m"));
						$GLOBALS['FromYearOptions'] = $this->GetYears(isc_date("Y"));
						$GLOBALS['ToDayOptions'] = $this->GetDays(isc_date("d"));

						// Set the "to" date to a month in advance
						if (isc_date("m") == 12) {
							$to_month = 1;
							$to_year = isc_date("Y")+1;
						}
						else {
							$to_month = isc_date("m")+1;
							$to_year = isc_date("Y");
						}

						$GLOBALS['ToMonthOptions'] = $this->GetMonths($to_month);
						$GLOBALS['ToYearOptions'] = $this->GetYears($to_year);
					}
					else {
						$from_day = isc_date("d", $banner['datefrom']);
						$from_month = isc_date("m", $banner['datefrom']);
						$from_year = isc_date("Y", $banner['datefrom']);
						$to_day = isc_date("d", $banner['dateto']);
						$to_month = isc_date("m", $banner['dateto']);
						$to_year = isc_date("Y", $banner['dateto']);

						$GLOBALS['FromDayOptions'] = $this->GetDays($from_day);
						$GLOBALS['FromMonthOptions'] = $this->GetMonths($from_month);
						$GLOBALS['FromYearOptions'] = $this->GetYears($from_year);

						$GLOBALS['ToDayOptions'] = $this->GetDays($to_day);
						$GLOBALS['ToMonthOptions'] = $this->GetMonths($to_month);
						$GLOBALS['ToYearOptions'] = $this->GetYears($to_year);

						$GLOBALS['ShowCustomDate'] = "$('#trCustomDate').css('display', '');";
					}

					if ($banner['location'] == "top") {
						$GLOBALS['IsLocationTop'] = "selected=\"selected\"";
					}
					else {
						$GLOBALS['IsLocationBottom'] = "selected=\"selected\"";
					}

					$this->template->display('banner.form.tpl');
				}
				else {
					$this->ManageBanners();
				}
			}
			else {
				$this->ManageBanners();
			}
		}

		private function EditBannerStep2()
		{
			if (isset($_POST['bannerId']) && is_numeric($_POST['bannerId'])) {

				$banner_id = (int)$_POST['bannerId'];

				if ($this->_CommitBanner($banner_id)) {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($banner_id, $_POST['bannername']);
					FlashMessage(GetLang('EditBannerSuccess'), MSG_SUCCESS, 'index.php?ToDo=viewBanners');
				}
				else {
					FlashMessage(GetLang('EditBannerFailed'), MSG_ERROR, 'index.php?ToDo=viewBanners');
				}
			}
			else {
				$this->ManageBanners();
			}
		}
	}