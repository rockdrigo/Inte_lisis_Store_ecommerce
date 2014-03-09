<?php

	class ISC_ADMIN_PAGES extends ISC_ADMIN_BASE
	{
		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('pages');
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('optimizer');
			switch (isc_strtolower($Do))
			{
				case "editpage2":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Pages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Pages') => "index.php?ToDo=viewPages");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditPageStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "editpage":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Pages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Pages') => "index.php?ToDo=viewPages", GetLang('EditPage') => "index.php?ToDo=editPage");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditPageStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "createpage2":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Pages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Pages') => "index.php?ToDo=viewPages");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddPageStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "createpage":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Pages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Pages') => "index.php?ToDo=viewPages", GetLang('CreateAWebPage') => "index.php?ToDo=addPage");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddPageStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "deletepages":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Pages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Pages') => "index.php?ToDo=viewPages");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeletePages();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "editpagevisibility":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Pages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Pages') => "index.php?ToDo=viewPages");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditVisibility();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						break;
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
				case "previewpage":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Pages)) {
						$this->PreviewPage();
						break;
					} else {
						echo "<script type=\"text/javascript\">window.close();</script>";
					}
				}
				default:
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Pages)) {

						if(isset($_GET['searchQuery'])) {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Pages') => "index.php?ToDo=viewPages", GetLang('SearchResults') => "index.php?ToDo=viewPages");
						}
						else {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Pages') => "index.php?ToDo=viewPages");
						}

						$GLOBALS['InfoTip'] = GetLang('InfoTipManagePages');

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->ManagePages();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
			}
		}

		private function ManagePages($MsgDesc = "", $MsgStatus = "")
		{
			// Show a list of pages in a table
			$GLOBALS['PageGrid'] = "";
			$GLOBALS['Nav'] = "";
			$numSubPages = 0;
			$searchURL = '';

			if (isset($_GET['searchQuery'])) {
				$query = $_GET['searchQuery'];
				$GLOBALS['Query'] = $query;
				$searchURL .= '&amp;searchQuery='.urlencode($query);
			} else {
				$query = "";
				$GLOBALS['Query'] = "";
			}

			if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'desc') {
				$sortOrder = 'desc';
			} else {
				$sortOrder = "";
			}

			$sortLinks = array(
				"Title" => "p.pagetitle",
				"Type" => "p.pagetype",
				"Visible" => "p.pagestatus"
			);

			if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("ManagePages", $_REQUEST['sortField'], $sortOrder);
			}
			else {
				$sortField = "n.newsdate";
				list($sortField, $sortOrder) = GetDefaultSortField("ManagePages", "p.pagesort asc, p.pagetitle asc", "");
			}

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			} else {
				$page = 1;
			}

			$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
			$GLOBALS['SortURL'] = $sortURL;

			// Get the results for the query
			$GLOBALS['Message'] = '';
			if($MsgDesc != "") {
				$GLOBALS['Message'] .= MessageBox($MsgDesc, $MsgStatus);
			}

			$GLOBALS['Message'] .= GetFlashMessageBoxes();

			$GLOBALS['SearchQuery'] = $query;
			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewPages&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);

			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				$vendorId = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
			}
			else {
				$vendorId = 0;
			}
			$GLOBALS['PageGrid'] = $this->_BuildPageList(0, $sortField, $sortOrder, 0, $vendorId);

			$GLOBALS['VendorPagesGrid'] = '';
			$GLOBALS['HideTabs'] = 'display: none';
			if(gzte11(ISC_HUGEPRINT) && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() == 0) {
				// Get all pages that belong to vendors
				$GLOBALS['VendorPagesGrid'] = $this->_BuildPageList(0, $sortField, $sortOrder, 0, -1);
				if($GLOBALS['VendorPagesGrid']) {
					$GLOBALS['HideTabs'] = '';
				}
			}

			// Do we need to disable the delete button?
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Pages) || !$GLOBALS['PageGrid']) {
				$GLOBALS['DisableDelete'] = "disabled='disabled'";
			}

			if(!$GLOBALS['PageGrid'] && !$GLOBALS['VendorPagesGrid']) {
				// There are no news posts in the database
				$GLOBALS['DisplayGrid'] = "none";
				$GLOBALS['Message'] = MessageBox(GetLang('NoPages'), MSG_SUCCESS);
			}
			else if(!$GLOBALS['PageGrid']) {
				$GLOBALS['NoPagesMessage'] = MessageBox(GetLang('NoPages'), MSG_SUCCESS);
			}
			else if(!$GLOBALS['VendorPagesGrid']) {
				$GLOBALS['NoVendorPagesMessage'] = MessageBox(GetLang('NoVendorPages'), MSG_SUCCESS);
			}

			$GLOBALS['PageIntro'] = GetLang('ManagePagesIntro');
			$this->template->display('pages.manage.tpl');
		}

		private function _BuildPageParentList($pageid)
		{
			static $pagecache, $i;

			if(!$pagecache) {
				$query = "SELECT pageid, pageparentid FROM [|PREFIX|]pages";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$pagecache[$row['pageid']] = $row;
				}
			}

			$trail = '';

			if(isset($pagecache[$pageid])) {
				$page = $pagecache[$pageid];
				if(isset($pagecache[$page['pageparentid']])) {
					$trail = $this->_BuildPageParentList($page['pageparentid']) . $trail;
				}
				if($trail != '') {
					$trail .= ',';
				}
				$trail .= $page['pageid'];
			}
			return $trail;
		}

		private function _BuildPageList($parentid=0, $sortField='', $sortOrder='', $depth=0, $vendorId=0)
		{
			static $pagecache;

			if(!$sortField) {
				$sortField = "pagesort";
			}

			if(!is_array($pagecache) || $depth == 0) {
				$pagecache = array();
				$query = "
					SELECT p.*, v.vendorname
					FROM [|PREFIX|]pages p
					LEFT JOIN [|PREFIX|]vendors v ON (v.vendorid=p.pagevendorid)
				";
				if($vendorId == -1) {
					$query .= " WHERE pagevendorid != 0";
				}
				else {
					$query .= " WHERE pagevendorid='".(int)$vendorId."'";
				}
				$query .= " ORDER BY vendorname, ".$sortField;

				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$pagecache[$row['pageparentid']][] = $row;
				}
			}

			if(!isset($pagecache[$parentid])) {
				return '';
			}

			$pageList = '';

			foreach($pagecache[$parentid] as $p) {
				$GLOBALS['SubPages'] = $this->_BuildPageList($p['pageid'], '', '', ++$depth, $vendorId);
				if($GLOBALS['SubPages']) {
					$GLOBALS['SubPages'] = sprintf('<ul class="SortableList">%s</ul>', $GLOBALS['SubPages']);
				}

				// Output the main pages details
				$GLOBALS['PageId'] = (int) $p['pageid'];
				$GLOBALS['Title'] = isc_html_escape($p['pagetitle']);
				$GLOBALS['Type'] = GetLang("PageType" . $p['pagetype']);
				$GLOBALS['Order'] = (int) $p['pagesort'];

				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Pages)) {
					if ($p['pagestatus'] == 1) {
						$GLOBALS['Visible'] = sprintf("<a title='%s' href='index.php?ToDo=editPageVisibility&amp;pageId=%d&amp;visible=0'><img border='0' src='images/tick.gif'></a>", GetLang('ClickToHidePage'), $p['pageid']);
					} else {
						$GLOBALS['Visible'] = sprintf("<a title='%s' href='index.php?ToDo=editPageVisibility&amp;pageId=%d&amp;visible=1'><img border='0' src='images/cross.gif'></a>", GetLang('ClickToShowPage'), $p['pageid']);
					}
				} else {
					if ($p['pagestatus'] == 1) {
						$GLOBALS['Visible'] = "<img border='0' src='images/tick.gif'>";
					} else {
						$GLOBALS['Visible'] = "<img border='0' src='images/cross.gif'>";
					}
				}

				// Workout the edit link -- do they have permission to do so?
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Pages)) {
					$GLOBALS['EditPageLink'] = sprintf("<a title='%s' href='index.php?ToDo=editPage&amp;pageId=%d'>%s</a>", GetLang('PageEdit'), $p['pageid'], GetLang('Edit'));
				} else {
					$GLOBALS['EditPageLink'] = sprintf("<a disabled>%s</a>", GetLang('Edit'));
				}

				// Workout the preview link
				if ($p['pagetype'] == 0) {
					$GLOBALS['PreviewPageLink'] = sprintf("<a title='%s' href='javascript:PreviewPage(%s)'>%s</a>", GetLang('PreviewPage'), $p['pageid'], GetLang('Preview'));
				} else {
					$GLOBALS['PreviewPageLink'] = sprintf("<span title='%s' class='Disabled'>%s</span>", GetLang('CantPreviewPage'), GetLang('Preview'));
				}

				$GLOBALS['SortableClass'] = 'SortableRow';
				$GLOBALS['HideVendorColumn'] = 'display: none';
				$GLOBALS['SortableDragClass'] = 'DragMouseDown sort-handle';
				if($vendorId == -1) {
					$GLOBALS['HideVendorColumn'] = '';
					$GLOBALS['VendorName'] = $p['vendorname'];
					$GLOBALS['SortableClass'] = '';
					$GLOBALS['SortableDragClass'] = '';
				}

				$pageList .= $this->template->render('page.manage.row.tpl');
			}
			return $pageList;
		}

		private function PreviewPage()
		{
			if (isset($_GET['pageId'])) {
				$pageId = (int)$_GET['pageId'];
				$query = sprintf("select pagetitle, pagecontent from [|PREFIX|]pages where pageid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($pageId));
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

				if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
					$GLOBALS['PageTitle'] = $row['pagetitle'];
					$GLOBALS['PageContent'] = $row['pagecontent'];

					$this->template->display('page.preview.tpl');
				} else {
					echo '<script type="text/javascript">window.close();</script>';
				}
			} else {
				echo '<script type="text/javascript">window.close();</script>';
			}
		}

		private function EditVisibility()
		{
			// Update the visibility of a page with a simple query
			$pageId = (int)$_GET['pageId'];
			$visible = (int)$_GET['visible'];

			$query = sprintf("SELECT pagetitle, pagevendorid FROM [|PREFIX|]pages WHERE pageid='%d'", $_GET['pageId']);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$page = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			// Does this user have permission to edit this page?
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $arrData['pagevendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewPages');
			}

			$updatedPage = array(
				"pagestatus" => $visible
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("pages", $updatedPage, "pageid='".$GLOBALS['ISC_CLASS_DB']->Quote($pageId)."'");

			// Update the pages cache
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdatePages();

			if ($GLOBALS["ISC_CLASS_DB"]->Error() == "") {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($pageId, $page['pagetitle']);

				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Pages)) {
					$this->ManagePages(GetLang('PageVisibleSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('PageVisibleSuccessfully'), MSG_SUCCESS);
				}
			} else {
				$err = '';
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Pages)) {
					$this->ManagePages(sprintf(GetLang('ErrPageVisibilityNotChanged'), $err), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrPageVisibilityNotChanged'), $err), MSG_ERROR);
				}
			}
		}

		private function DeletePages()
		{
			if (isset($_POST['page'])) {
				$err = array('');

				$pageIds = $_POST['page'];
				foreach ($pageIds as $key => $pageId) {
					$pageIds[$key] = (int)$pageId;
				}

				// if a vendor is logged in, check all ids passed in to make sure they are allowed to be deleted
				$vendorId = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
				if ($vendorId) {
					$sql = "SELECT pageid FROM `[|PREFIX|]pages` WHERE pageid IN (" . implode(",", $pageids) . ") and pagevendorid = " . $vendorId;
					$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);
					$pageIds = array();
					while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$pageIds[] = $row['pageid'];
					}
				}

				if (empty($pageIds)) {
					// if no page ids were provided or no page ids were deletable, show the management page
					$this->ManagePages();
					return;
				}

				$nested = new ISC_NESTEDSET_PAGES();
				foreach ($pageIds as $pageId) {
					if(!$this->deletePageSearch($pageId)) {
						$err = $GLOBALS["ISC_CLASS_DB"]->GetError();
						break;
					}
					$nested->deleteNode($pageId);
				}

				// Update the pages cache
				$GLOBALS['ISC_CLASS_DATA_STORE']->UpdatePages();

				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				$optimizer->deletePerItemOptimizerConfig('page', $_POST['page']);

				if ($err[0] != "") {
					FlashMessage($err[0], MSG_ERROR, 'index.php?ToDo=viewPages');
				} else {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['page']));

					FlashMessage(GetLang('PagesDeletedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewPages');
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Pages)) {
					$this->ManagePages();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		/**
		 * Delete a page and all its children
		 *
		 * Method will delete a page and recursively delete all its children
		 *
		 * @access private
		 * @param int $pageId The page ID
		 * @return bool TRUE if the page was deleted, FALSE on error
		 */
		private function deletePageSearch($pageId)
		{
			if (!isId($pageId)) {
				return false;
			}

			// Delete the search result
			$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("page_search", "WHERE pageid=" . (int)$pageId);

			// Now delete the kids
			$query = "SELECT pageid
						FROM [|PREFIX|]pages
						WHERE pageparentid=" . (int)$pageId;

			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->GetVendorId()) {
				$query .= " AND pagevendorid=".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
			}

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$this->deletePageSearch($row["pageid"]);
			}

			return true;
		}

		private function AddPageStep1($MsgDesc = "", $MsgStatus = "", $IsError = false)
		{
			$GLOBALS['Message'] = '';
			if($MsgDesc != "") {
				$GLOBALS['Message'] .= MessageBox($MsgDesc, $MsgStatus);
			}

			$GLOBALS['Message'] .= GetFlashMessageBoxes();

			$GLOBALS['Title'] = GetLang('CreatePage');
			$GLOBALS['FormAction'] = "createPage2";

			$arrData = array();

			$this->_GetPageData(0, $arrData);

			// Was the page submitted with a duplicate page name?
			if($IsError) {
				if($_POST['pagetype'] == 0) {
					$GLOBALS['SelType0'] = 'checked="checked"';
					$GLOBALS['SetupType'] = "SwitchType(0);";
				}
				else if($_POST['pagetype'] == 1) {
					$GLOBALS['SelType1'] = 'checked="checked"';
					$GLOBALS['SetupType'] = "SwitchType(1);";
				}
				else if($_POST['pagetype'] == 2) {
					$GLOBALS['SelType2'] = 'checked="checked"';
					$GLOBALS['SetupType'] = "SwitchType(2);";
				}
				else if($_POST['pagetype'] == 3) {
					$GLOBALS['SelType2'] = 'checked="checked"';
					$GLOBALS['SetupType'] = "SwitchType(3);";
				}

				$GLOBALS['PageTitle'] = $arrData['pagetitle'];

				$wysiwygOptions = array(
					'id'		=> 'wysiwyg',
					'value'		=> $arrData['pagecontent']
				);
				$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

				$GLOBALS['PageLink'] = $arrData['pagelink'];
				$GLOBALS['PageFeed'] = $arrData['pagefeed'];
				$GLOBALS['Visible'] = '';
				if($arrData['pagestatus'] == 1) {
					$GLOBALS['Visible'] = 'checked="checked"';
				}
				$GLOBALS['ParentPageOptions'] = $this->GetParentPageOptions($arrData['pageparentid'], 0, $arrData['pagevendorid']);
				$GLOBALS['PageKeywords'] = $arrData['pagekeywords'];
				$GLOBALS['PageMetaTitle'] = $arrData['pagemetatitle'];
				$GLOBALS['PageDesc'] = $arrData['pagedesc'];
				$GLOBALS['PageSearchKeywords'] = $arrData['pagesearchkeywords'];
				$GLOBALS['PageSort'] = $arrData['pagesort'];
				$GLOBALS['PageEmail'] = $arrData['pageemail'];

				if(isset($_POST['contactfields']['fullname'])) {
					$GLOBALS['IsContactFullName'] = 'checked="checked"';
				}

				if(isset($_POST['contactfields']['companyname'])) {
					$GLOBALS['IsContactCompanyName'] = 'checked="checked"';
				}

				if(isset($_POST['contactfields']['phone'])) {
					$GLOBALS['IsContactPhone'] = 'checked="checked"';
				}

				if(isset($_POST['contactfields']['orderno'])) {
					$GLOBALS['IsContactOrderNo'] = 'checked="checked"';
				}

				if(isset($_POST['contactfields']['rma'])) {
					$GLOBALS['IsContactRMA'] = 'checked="checked"';
				}

				if(isset($_POST['pagecustomersonly'])) {
					$GLOBALS['IsCustomersOnly'] = "checked=\"checked\"";
				}

				$selectedVendor = 0;
				if(isset($_POST['pagevendorid'])) {
					$selectedVendor = $_POST['pagevendorid'];
				}

			}
			else {
				// Nope, use the default values
				$GLOBALS['Visible'] = 'checked="checked"';
				$GLOBALS['SelType0'] = 'checked="checked"';

				$wysiwygOptions = array(
					'id'		=> 'wysiwyg',
				);
				$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

				$GLOBALS['PageLink'] = "http://";
				$GLOBALS['PageFeed'] = "http://";
				$GLOBALS['SetupType'] = "SwitchType(0);";
				$GLOBALS['ParentPageOptions'] = $this->GetParentPageOptions(0, 0, $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId());
				$GLOBALS['PageEmail'] = GetConfig('AdminEmail');
				$selectedVendor = '0';
			}

			$GLOBALS['IsVendor'] = 'false';

			if(!gzte11(ISC_HUGEPRINT)) {
				$GLOBALS['HideVendorOption'] = 'display: none';
			}
			else {
				$vendorData = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendor();
				if(isset($vendorData['vendorid'])) {
					$GLOBALS['HideVendorSelect'] = 'display: none';
					$GLOBALS['IsVendor'] = 'true';
					$GLOBALS['CurrentVendor'] = isc_html_escape($vendorData['vendorname']);
				}
				else {
					$GLOBALS['HideVendorLabel'] = 'display: none';
					$GLOBALS['VendorList'] = $this->BuildVendorSelect($selectedVendor);
				}
			}


			$GLOBALS['CurrentTab'] = '0';
			if(isset($_REQUEST['currentTab'])) {
				$GLOBALS['CurrentTab'] = $_REQUEST['currentTab'];
			}

			//Google website optimizer
			$GLOBALS['GoogleWebsiteOptimizerIntro'] = GetLang('EnableGoogleWebsiteOptimizerAfterSave');
			$GLOBALS['ShowEnableGoogleWebsiteOptimzer'] = 'display:none';
			$GLOBALS['DisableOptimizerCheckbox'] = 'DISABLED=DISABLED';

			$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndAddAnother');

			// Get a list of all layout files
			$GLOBALS['LayoutFiles'] = GetCustomLayoutFilesAsOptions("page.html");

			$this->template->display('page.form.tpl');
		}

		public function GetParentPageOptions($SelectedPage = 0, $DontDisplay = 0, $vendorId=0)
		{
			// Return a list of page option tags
			$sel = "";
			$output = "";

			$nested = new ISC_NESTEDSET_PAGES();

			$exclusions = array();

			if ($DontDisplay) {
				$exclusions[] = $DontDisplay;
			}

			// Get a formatted list of all of the pages in the system
			$pages = $nested->getTree(
				array('pageid', 'pagetitle', 'pagevendorid'),
				ISC_NESTEDSET_START_ROOT,
				ISC_NESTEDSET_DEPTH_ALL,
				null,
				null,
				true,
				array('pagevendorid = ' . $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()),
				$exclusions
			);


			foreach($pages as $page) {
				if($page['pageid'] == $SelectedPage) {
					$sel = 'selected="selected"';
				}
				else {
					$sel = '';
				}

				$pad = '';
				if ($page['pagedepth']) {
					$pad = str_replace(' ', '&nbsp;', str_pad('', 4 * $page['pagedepth'])) . '`- ';
				}

				$output .= sprintf("<option %s value='%d'>%s</option>", $sel, $page['pageid'], $pad . $page['pagetitle']);
			}

			return $output;
		}

		private function AddPageStep2()
		{
			$err = "";
			$arrData = array();
			$this->_GetPageData(0, $arrData);

			if(!$this->_IsDuplicateTitle($arrData['pagetitle'], 0, $arrData['pagevendorid'])) {

				//Validate Google Website Optimizer form
				if(isset($_POST['pageEnableOptimizer'])) {
					$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
					$error = $optimizer -> validateConfigForm();
					if($error != '') {
						$this->AddPageStep1($error, MSG_ERROR, true);
						exit;
					}
				}

				// Commit the values to the database
				if (($pageId = $this->_CommitPage(0, $arrData, $err))) {

					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($pageId, $arrData['pagetitle']);

					if(isset($_POST['addAnother']) || isset($_POST['addAnother2'])) {
						$url = 'index.php?ToDo=createPage';
					}
					else {
						$url = 'index.php?ToDo=viewPages';
					}
					FlashMessage(GetLang('PageAddedSuccessfully'), MSG_SUCCESS, $url);
				}
				else {
				//	$url = 'index.php?ToDo=createPage'.$currentTab;
				//	FlashMessage(sprintf(GetLang('ErrPageNotAdded', $err)), MSG_ERROR, $url);
					$this->AddPageStep1(sprintf(GetLang('ErrPageNotAdded'), $err[0]), MSG_ERROR, true);
				}
			}
			else {
				$this->AddPageStep1(GetLang('DuplicatePageTitle'), MSG_ERROR, true);
			}
		}

		private function _GetPageData($PageId, &$RefArray)
		{
			if ($PageId == 0 && !empty($_POST)) {
				$RefArray['pageid'] = 0;
				$RefArray['pagetitle'] = $_POST['pagetitle'];
				$RefArray['pagelink'] = $_POST['pagelink'];
				$RefArray['pagefeed'] = $_POST['pagefeed'];
				$RefArray['pageemail'] = $_POST['pageemail'];
				$RefArray['pagecontactfields'] = "";

				if(isset($_POST['contactfields'])) {
					$RefArray['pagecontactfields'] = implode(",", $_POST['contactfields']);
				}

				if(isset($_POST["wysiwyg_html"])) {
					$RefArray['pagecontent'] = @$_POST["wysiwyg_html"];
				}
				else {
					$RefArray['pagecontent'] = @$_POST['wysiwyg'];
				}

				if (isset($_POST['pagestatus'])) {
					$RefArray['pagestatus'] = 1;
				} else {
					$RefArray['pagestatus'] = 0;
				}

				if(isset($_POST['pageishomepage'])) {
					$RefArray['pageishomepage'] = 1;
				}
				else {
					$RefArray['pageishomepage'] = 0;
				}

				$RefArray['pageparentid'] = $_POST['pageparentid'];
				$RefArray['pagesort'] = (int)$_POST['pagesort'];
				$RefArray['pagelayoutfile'] = $_POST['pagelayoutfile'];
				$RefArray['pagekeywords'] = $_POST['pagekeywords'];
				$RefArray['pagemetatitle'] = $_POST['pagemetatitle'];
				$RefArray['pagedesc'] = $_POST['pagedesc'];
				$RefArray['pagesearchkeywords'] = $_POST['pagesearchkeywords'];
				$RefArray['pagetype'] = $_POST['pagetype'];

				if (isset($_POST['pagecustomersonly'])) {
					$RefArray['pagecustomersonly'] = 1;
				} else {
					$RefArray['pagecustomersonly'] = 0;
				}

				if (isset($_POST['pageEnableOptimizer'])) {
					$RefArray['page_enable_optimizer'] = 1;
				} else {
					$RefArray['page_enable_optimizer'] = 0;
				}

				$RefArray['pagevendorid'] = 0;
				if(gzte11(ISC_HUGEPRINT)) {
					$vendorData = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendor();
					// User is assigned to a vendor so any pages they create must be too
					if(isset($vendorData['vendorid'])) {
						$RefArray['pagevendorid'] = $vendorData['vendorid'];
					}
					else if(isset($_POST['vendor'])) {
						$RefArray['pagevendorid'] = (int)$_POST['vendor'];
					}
				}
			} else {
				// Get the data for this news post from the database
				$query = sprintf("select * from [|PREFIX|]pages where pageid='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($PageId));
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

				if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
					$RefArray = $row;
				}
			}
		}

		private function _CommitPage($PageId, &$Data, &$err)
		{
			// Commit the details for the page to the database
			$query = "";
			$err = null;

			// Update other pages if this page is set as the home page
			if($Data['pageishomepage'] == 1) {
				$updatedPage = array(
					"pageishomepage" => 0
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("pages", $updatedPage);
			}

			if ($PageId == 0) {
				// ----- Build the query for the news table -----

				// Linked pages can't be the home page
				if ((int) $Data['pagetype'] == 1) {
					$Data['pageishomepage'] = 0;
				}

				$newPage = array(
					"pagetitle" => $Data['pagetitle'],
					"pagelink" => $Data['pagelink'],
					"pagefeed" => $Data['pagefeed'],
					"pageemail" => $Data['pageemail'],
					"pagecontent" => $Data['pagecontent'],
					"pagestatus" => (int)$Data['pagestatus'],
					"pageparentid" => (int)$Data['pageparentid'],
					"pagesort" => $Data['pagesort'],
					"pagekeywords" => $Data['pagekeywords'],
					"pagemetatitle" => $Data['pagemetatitle'],
					"pagedesc" => $Data['pagedesc'],
					"pagesearchkeywords" => $Data['pagesearchkeywords'],
					"pagetype" => (int)$Data['pagetype'],
					"pagecontactfields" => $Data['pagecontactfields'],
					"pageishomepage" => 0,
					"pagelayoutfile" => $Data['pagelayoutfile'],
					"pagecustomersonly" => $Data['pagecustomersonly'],
					"pageparentlist" => "",
					'pagevendorid' => (int)$Data['pagevendorid'],
					"page_enable_optimizer" => (int)$Data['page_enable_optimizer'],
				);

				if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
					$newPage["pageishomepage"] = (int)$Data['pageishomepage'];
				}

				$PageId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("pages", $newPage);

				if($PageId) {
					// Now we need to store the page parent list
					$parentList = $this->_BuildPageParentList($PageId);
					$updatedPage = array(
						"pageparentlist" => $parentList
					);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("pages", $updatedPage, "pageid='".$GLOBALS['ISC_CLASS_DB']->Quote((int)$PageId)."'");

					// Rebuild the nested-set tree
					// @todo if this process becomes too slow on sites with many pages, this can be optimized to do only a partial update - see: how category create works
					$nested = new ISC_NESTEDSET_PAGES();
					$nested->rebuildTree();
				}

				$err = $GLOBALS["ISC_CLASS_DB"]->GetError();

			} else {
				$query = "";

				// Only a normal page can be a home page
				if ((int) $Data['pagetype'] == 1) {
					$Data['pageishomepage'] = 0;
				}

				// Update the existing pages details
				$updatedPage = array(
					"pagetitle" => $Data['pagetitle'],
					"pagelink" => $Data['pagelink'],
					"pagefeed" => $Data['pagefeed'],
					"pageemail" => $Data['pageemail'],
					"pagecontent" => $Data['pagecontent'],
					"pagestatus" => (int)$Data['pagestatus'],
					"pageparentid" => (int)$Data['pageparentid'],
					"pagesort" => $Data['pagesort'],
					"pagekeywords" => $Data['pagekeywords'],
					"pagemetatitle" => $Data['pagemetatitle'],
					"pagedesc" => $Data['pagedesc'],
					"pagesearchkeywords" => $Data['pagesearchkeywords'],
					"pagetype" => (int)$Data['pagetype'],
					"pagecontactfields" => $Data['pagecontactfields'],
					"pageishomepage" => (int)$Data['pageishomepage'],
					"pagelayoutfile" => $Data['pagelayoutfile'],
					"pagecustomersonly" => $Data['pagecustomersonly'],
					'pagevendorid' => (int)$Data['pagevendorid'],
					"page_enable_optimizer" => (int)$Data['page_enable_optimizer'],
				);

				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("pages", $updatedPage, "pageid='".$GLOBALS['ISC_CLASS_DB']->Quote((int)$PageId)."'");
				$err = $GLOBALS["ISC_CLASS_DB"]->GetError();

				if($err[0] == "") {
					// Rebuild the nested-set tree
					// @todo if this process becomes too slow on sites with many pages, this can be optimized to do only a partial update
					$nested = new ISC_NESTEDSET_PAGES();
					$nested->rebuildTree();
				}
			}

			// Update the pages cache
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdatePages();

			$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
			if(isset($Data["page_enable_optimizer"]) && $Data["page_enable_optimizer"] == 1) {
				$optimizer->savePerItemOptimizerConfig('page', $PageId);
			} else {
				$optimizer->deletePerItemOptimizerConfig('page', array($PageId));
			}


			if($err[0] != "") {
				return false;
			}

			// Add/edit out search record
			$savedata = array(
				"pageid" => $PageId,
				"pagetitle" => $Data['pagetitle'],
				"pagecontent" => stripHTMLForSearchTable($Data['pagecontent']),
				"pagedesc" => stripHTMLForSearchTable($Data['pagedesc']),
				"pagesearchkeywords" => $Data['pagesearchkeywords']
			);

			$query = "SELECT pagesearchid
						FROM [|PREFIX|]page_search
						WHERE pageid=" . (int)$PageId;

			$searchId = $GLOBALS["ISC_CLASS_DB"]->FetchOne($query);

			if (isId($searchId)) {
				$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("page_search", $savedata, "pagesearchid=" . (int)$searchId);
			} else {
				$GLOBALS["ISC_CLASS_DB"]->InsertQuery("page_search", $savedata);
			}

			// Save the words to the news_words table for search spelling suggestions
			Store_SearchSuggestion::manageSuggestedWordDatabase("page", $PageId, $Data['pagetitle']);

			return true;
		}

		private function EditPageStep1($MsgDesc = "", $MsgStatus = "", $IsError = false)
		{
			$GLOBALS['Message'] = '';
			if($MsgDesc != "") {
				$GLOBALS['Message'] .= MessageBox($MsgDesc, $MsgStatus);
			}

			$GLOBALS['Message'] .= GetFlashMessageBoxes();

			$pageId = (int)$_REQUEST['pageId'];
			$arrData = array();

			if(PageExists($pageId)) {

				// Was the page submitted with a duplicate page name?
				if($IsError) {
					$this->_GetPageData(0, $arrData);
				}
				else {
					$this->_GetPageData($pageId, $arrData);
				}

				$GLOBALS['CurrentTab'] = '0';
				if(isset($_REQUEST['currentTab'])) {
					$GLOBALS['CurrentTab'] = $_REQUEST['currentTab'];
				}
				// Does this user have permission to edit this product?
				if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $arrData['pagevendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewPages');
				}

				$GLOBALS['PageId'] = (int) $pageId;
				$GLOBALS['SetupType'] = sprintf("SwitchType(%d);", $arrData['pagetype']);
				$GLOBALS['Title'] = GetLang('EditPage');
				$GLOBALS['FormAction'] = "editPage2";
				$GLOBALS['PageTitle'] = isc_html_escape($arrData['pagetitle']);

				$wysiwygOptions = array(
					'id'		=> 'wysiwyg',
					'value'		=> $arrData['pagecontent']
				);
				$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

				$GLOBALS['PageLink'] = isc_html_escape($arrData['pagelink']);
				$GLOBALS['PageFeed'] = isc_html_escape($arrData['pagefeed']);
				$GLOBALS['PageEmail'] = isc_html_escape($arrData['pageemail']);
				$GLOBALS['ParentPageOptions'] = $this->GetParentPageOptions($arrData['pageparentid'], $pageId, $arrData['pagevendorid']);
				$GLOBALS['PageKeywords'] = isc_html_escape($arrData['pagekeywords']);
				$GLOBALS['PageMetaTitle'] = isc_html_escape($arrData['pagemetatitle']);
				$GLOBALS['PageDesc'] = isc_html_escape($arrData['pagedesc']);
				$GLOBALS['PageSearchKeywords'] = isc_html_escape($arrData['pagesearchkeywords']);
				$GLOBALS['PageSort'] = (int) $arrData['pagesort'];

				if($arrData['pagestatus'] == 1) {
					$GLOBALS['Visible'] = 'checked="checked"';
				}

				if($arrData['pagecustomersonly'] == 1) {
					$GLOBALS['IsCustomersOnly'] = "checked=\"checked\"";
				}

				if(is_numeric(isc_strpos($arrData['pagecontactfields'], "fullname"))) {
					$GLOBALS['IsContactFullName'] = 'checked="checked"';
				}

				if(is_numeric(isc_strpos($arrData['pagecontactfields'], "companyname"))) {
					$GLOBALS['IsContactCompanyName'] = 'checked="checked"';
				}

				if(is_numeric(isc_strpos($arrData['pagecontactfields'], "phone"))) {
					$GLOBALS['IsContactPhone'] = 'checked="checked"';
				}

				if(is_numeric(isc_strpos($arrData['pagecontactfields'], "orderno"))) {
					$GLOBALS['IsContactOrderNo'] = 'checked="checked"';
				}

				if(is_numeric(isc_strpos($arrData['pagecontactfields'], "rma"))) {
					$GLOBALS['IsContactRMA'] = 'checked="checked"';
				}

				// Is this page the default home page?
				if($arrData['pageishomepage'] == 1) {
					$GLOBALS['IsHomePage'] = 'checked="checked"';
				}

				$GLOBALS['IsVendor'] = 'false';

				if(!gzte11(ISC_HUGEPRINT)) {
					$GLOBALS['HideVendorOption'] = 'display: none';
				}
				else {
					$vendorData = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendor();
					if(isset($vendorData['vendorid'])) {
						$GLOBALS['HideVendorSelect'] = 'display: none';
						$GLOBALS['IsVendor'] = 'true';
						$GLOBALS['CurrentVendor'] = isc_html_escape($vendorData['vendorname']);
					}
					else {
						$GLOBALS['HideVendorLabel'] = 'display: none';
						$GLOBALS['VendorList'] = $this->BuildVendorSelect($arrData['pagevendorid']);
					}
				}

				// Get a list of all layout files
				$layoutFile = 'page.html';
				if($arrData['pagelayoutfile'] != '') {
					$layoutFile = $arrData['pagelayoutfile'];
				}
				$GLOBALS['LayoutFiles'] = GetCustomLayoutFilesAsOptions("page.html", $layoutFile);



				//Google website optimizer
				$GLOBALS['GoogleWebsiteOptimizerIntro'] = GetLang('PageGoogleWebsiteOptimizerIntro');

				$GLOBALS['HideOptimizerConfigForm'] = 'display:none;';
				$GLOBALS['CheckEnableOptimizer'] = '';
				$GLOBALS['SkipOptimizerConfirmMsg'] = 'true';

				$enabledOptimizers = GetConfig('OptimizerMethods');
				if(!empty($enabledOptimizers)) {
					foreach ($enabledOptimizers as $id => $date) {
						GetModuleById('optimizer', $optimizerModule, $id);
						if ($optimizerModule->_testPage == 'pages' || $optimizerModule->_testPage == 'all') {
							$GLOBALS['SkipOptimizerConfirmMsg'] = 'false';
							break;
						}
					}
				}

				if($arrData['page_enable_optimizer']) {
					$GLOBALS['HideOptimizerConfigForm'] = '';
					$GLOBALS['CheckEnableOptimizer'] = 'Checked';
				}

				$pageUrl = PageLink($pageId, $arrData['pagetitle']);
				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				$GLOBALS['OptimizerConfigForm'] = $optimizer->showPerItemConfigForm('page', $pageId, $pageUrl);

				$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndContinueEditing');

				$this->template->display('page.form.tpl');
			}
			else {
				// The news page doesn't exist
				FlashMessage(GetLang('PageDoesntExist'), MSG_ERROR, 'index.php?ToDo=viewPages');
			}
		}

		private function EditPageStep2()
		{
			// Get the information from the form and add it to the database
			$pageId = (int)$_POST['pageId'];
			$arrData = array();
			$err = "";

			$existingPage = array();
			$this->_GetPageData($pageId, $existingPage);

			// Does this user have permission to edit this product?
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $existingPage['pagevendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewPages');
			}

			$this->_GetPageData(0, $arrData);
			$arrData['pageid'] = $pageId;

			$currentTab = '0';
			if(isset($_POST['currentTab']) && $_POST['currentTab'] != ''){
				$currentTab = '&currentTab='.$_POST['currentTab'];
			}
			if(isset($_POST['addAnother']) || isset($_POST['addAnother2'])) {
				$url = 'index.php?ToDo=editPage&pageId='.$pageId.$currentTab;
			}
			else {
				$url = 'index.php?ToDo=viewPages';
			}

			if($this->_IsDuplicateTitle($arrData['pagetitle'], $pageId, $arrData['pagevendorid'])) {
				$this->EditPageStep1(GetLang('DuplicatePageTitle'), MSG_ERROR, true);
				die();
			}

			// Get a formatted list of all of the pages in the system
			$nested = new ISC_NESTEDSET_PAGES();
			$pages = $nested->getTree(
				array('pageid', 'pagetitle', 'pagevendorid'),
				$pageId,
				ISC_NESTEDSET_DEPTH_ALL,
				null,
				null,
				true,
				array('pagevendorid = ' . $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId())
			);

			$childList = array();
			foreach ($pages as $page) {
				if ($page['pageid'] != $pageId) {
					$childList[] = $page['pageid'];
				}
			}

			// don't let the current page assign itself or any decendants as it's parent page
			if($pageId == $arrData['pageparentid'] || in_array($arrData['pageparentid'], $childList)) {
				$this->EditPageStep1(GetLang('InvalidParentPage'), MSG_ERROR, true);
				die();
			}

			//Validate Google Website Optimizer form
			if(isset($arrData['page_enable_optimizer']) && $arrData['page_enable_optimizer']==1) {
				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				$error = $optimizer->validateConfigForm();
				if($error!='') {
					$this->EditPageStep1($error, MSG_ERROR, true);
				}
			}

			// Commit the values to the database
			if ($this->_CommitPage($pageId, $arrData, $err)) {

				if($existingPage['pageparentid'] != $arrData['pageparentid']) {
					// Rebuild the parent list
					$parentList = $this->_BuildPageParentList($pageId);

					$updatedPage = array(
						"pageparentlist" => $parentList
					);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("pages", $updatedPage, "pageid='".$GLOBALS['ISC_CLASS_DB']->Quote((int)$pageId)."'");

					// Now we also need to update the parent list of all child pages for this page
					$query = sprintf("SELECT pageid FROM [|PREFIX|]pages WHERE CONCAT(',', pageparentlist, ',') LIKE '%%,%s,%%'", $pageId);
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					while($child = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$parentList = $this->_BuildPageParentList($child['pageid']);
						// Update the parent list for this child
						$updatedPage = array(
							"pageparentlist" => $parentList
						);
						$GLOBALS['ISC_CLASS_DB']->UpdateQuery("pages", $updatedPage, "pageid='".$GLOBALS['ISC_CLASS_DB']->Quote($child['pageid'])."'");
					}
				}

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($pageId, $arrData['pagetitle']);
				FlashMessage(GetLang('PageUpdatedSuccessfully'), MSG_SUCCESS, $url);
			} else {
				FlashMessage(sprintf(GetLang('ErrPageNotUpdated'), $err), MSG_ERROR, $url);
			}
		}

		/**
		 * Check if a page title is already in use elsewhere.
		 *
		 * @param string The title of the page.
		 * @param int The ID of the current page being edited (if any) as to not match that.
		 * @param int The ID of the vendor that this page belongs to.
		 * @return boolean True if the page title is already in use, false if not.
		 */
		private function _IsDuplicateTitle($title, $existingId=0, $vendorId=0)
		{
			$query = "
				SELECT pageid
				FROM [|PREFIX|]pages
				WHERE pagetitle='".$GLOBALS['ISC_CLASS_DB']->Quote($title)."'
			";
			if($existingId > 0) {
				$query .= " AND pageid != '".(int)$existingId."'";
			}

			$query .= " AND pagevendorid ='".(int)$vendorId."'";

			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 1);

			if($GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		* GetContactPagesAsOptions
		* Return a list of <option> tags containing the id and names of all pages
		* that are of type "contact page"
		*
		* @param $SelectedPages Array An ID of page id's whose option tags should be selected
		* @return String
		*/
		public function GetContactPagesAsOptions($SelectedPages=null)
		{
			$query = "select pageid, pagetitle from [|PREFIX|]pages where pagetype='3' order by pagetitle asc";
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$output = "";

			while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				if(is_array($SelectedPages) && in_array($row['pageid'], $SelectedPages)) {
					$sel = 'selected="selected"';
				}
				else {
					$sel = "";
				}

				$output .= sprintf("<option %s value='%d'>%s</option>", $sel, $row['pageid'], $row['pagetitle']);
			}

			return $output;
		}

		/**
		 * Build a list of vendors that can be chosen for a product.
		 *
		 * @param int The vendor ID to select, if any.
		 * @return string The HTML options for the select box of vendors.
		 */
		public function BuildVendorSelect($selectedVendor=0)
		{
			$options = '<option value="0">'.GetLang('NoVendor').'</option>';
			$query = "
				SELECT vendorid, vendorname
				FROM [|PREFIX|]vendors
				ORDER BY vendorname ASC
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$sel = '';
				if($selectedVendor == $vendor['vendorid']) {
					$sel = 'selected="selected"';
				}
				$options .= '<option value='.(int)$vendor['vendorid'].' '.$sel.'>'.isc_html_escape($vendor['vendorname']).'</option>';
			}
			return $options;
		}

		/**
		 * Gets a 1-dimensional array of pages available to the site or vendor. Structure is indicated by the 'depth' element
		 *
		 * @param int The parent page id to get pages from
		 * @param String The field to sort the pages on
		 * @param String The order in which to use the sort field. ASC or DESC
		 *
		 * @return Array Array of pages
		 */
		public function _getPagesArray($parentid = 0, $sortField = 'pagesort', $sortOrder = 'ASC', &$pages = array(), $depth = 0)
		{
			//construct sql
			$query = "
				SELECT
					p.pageid,
					p.pagetitle,
					p.pagelink,
					p.pagevendorid,
					p.pagetype,
					p.pagesort,
					p.pageparentid,
					v.vendorname
				FROM
					[|PREFIX|]pages p
					LEFT JOIN [|PREFIX|]vendors v ON (v.vendorid = p.pagevendorid)
				WHERE
					pageparentid = '" . $parentid . "'
				";


			// Only fetch pages which belong to the current vendor
			$vendorid = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
			if ($vendorid) {
				$query .= " AND pagevendorid = '" . $vendorid . "'";
			}

			$query .= " ORDER BY vendorname, " . $sortField . " " . $sortOrder;

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				//$pages[$parentid]][] = $row;
				$row['depth'] = $depth;
				$pages[] = $row;

				$this->_getPagesArray($row['pageid'], $sortField, $sortOrder, $pages, $depth + 1);
			}

			if ($depth == 0) {
				return $pages;
			}
		}
	}
