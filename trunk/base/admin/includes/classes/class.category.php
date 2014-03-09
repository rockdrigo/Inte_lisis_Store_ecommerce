<?php

	include_once(ISC_BASE_PATH.'/lib/api/category.api.php');

	class ISC_ADMIN_CATEGORY extends ISC_ADMIN_BASE
	{
		public $nestedset;

		private $categoryAPI;

		public function __construct()
		{
			parent::__construct();
			$this->nestedset = new ISC_NESTEDSET_CATEGORIES;
			$this->categoryAPI = new API_CATEGORY();
		}

		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('categories');
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('optimizer');
			switch (isc_strtolower($Do))
			{
				case 'getreassigncategorystep1data':
				{
					$data = $this->getReassignCategoryStep1Data();
					echo isc_json_encode($data);
					break;
				}
				case 'getreassigncategorystep2data':
				{
					echo $this->getParentLineage($_POST['parentCat']);
					break;
				}
				case 'reassigncategory':
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Categories)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Categories') => "index.php?ToDo=viewCategories");
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->reassignCategory();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				}
				case "saveupdatedcategory":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Categories)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Categories') => "index.php?ToDo=viewCategories");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SaveUpdatedCategory();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "editcategory":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Categories)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Categories') => "index.php?ToDo=viewCategories", GetLang('EditCategory1') => "index.php?ToDo=editCategory");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCategory();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "savecategory":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Create_Category)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Categories') => "index.php?ToDo=viewCategories");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SaveCategory();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "createcategory":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Create_Category)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Categories') => "index.php?ToDo=viewCategories", GetLang('CreateCategory') => "index.php?ToDo=addCategory");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateCategory();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "editcategoryvisibility":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Categories)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Categories') => "index.php?ToDo=viewCategories");

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}

						$this->EditCategoryVisibility();

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}

						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "deletecategory":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Categories)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Categories') => "index.php?ToDo=viewCategories");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteCategory();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
				default:
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Categories)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Categories') => "index.php?ToDo=viewCategories");

						$GLOBALS['InfoTip'] = GetLang('InfoTipManageCategories');

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->ManageCategories();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
			}
		}

		/**
		* Rebuilds the entire nested set tree for the categories table
		*
		* @return void
		*/
		public function rebuildNestedSetTree()
		{
			$this->nestedset->rebuildTree();
		}

		/**
		* getCats
		* Returns an array of categories, each indented and prefixed depending
		* on it's position in the category structure.
		*
		* @param string $stalk What to prefix a question with to signify it is
		* a descendant of its parent
		* @param int $parentid The category id to get descdendants of
		* @param string $prefix This string grows with whitespace depending on
		* the depth of the item in the tree
		*
		* @return array The formatted array of categories
		*/
		public function getCats($stalk = "\xE2\x94\x94 ", $parentid=0, $prefix='', $visible='')
		{
			if ($visible) {
				$categories = $this->nestedset->getTree(array('categoryid', 'catname'), $parentid, ISC_NESTEDSET_DEPTH_ALL, null, null, true, array('MIN(`parent`.`catvisible`) = 1'));
			} else {
				$categories = $this->nestedset->getTree(array('categoryid', 'catname'), $parentid, ISC_NESTEDSET_DEPTH_ALL, null, null, true);
			}

			$formatted = array();
			foreach ($categories as $category) {
				$indent = str_repeat('&nbsp;', 3 * (int)$category['catdepth']);
				if ($indent) {
					$indent .= $stalk;
				}

				$formatted[$category['categoryid']] = $prefix . $indent . isc_html_escape($category['catname']);
			}

			return $formatted;
		}

		public function GetCatNameFromArray(&$CatArray, $CatId)
		{
			// Pass in an array by reference and loop through to find
			// the indented category name. Once found, return it.

			foreach ($CatArray as $c) {
				if ($c[0] == $CatId) {
					if (isset($c[1])) {
						return $c[1];
					}
					else {
						return "";
					}
				}
			}

			return "";
		}

		public function _BuildCategoryList($parentid = 0)
		{
			if ($parentid == 0) {
				$depth = 0;
			}
			else {
				$depth  = 1;
			}

			$categories = $this->nestedset->getTree(
				array('categoryid', 'catname', 'catvisible', 'catnsetleft', 'catnsetright', 'cataltcategoriescache'),
				$parentid,
				$depth
			);

			if ($depth > 0) {
				 array_shift($categories);
			}

			if (empty($categories)) {
				return '';
			}

			$shoppingComparisonModules = Isc_ShoppingComparison::getModulesWithTaxonomies();
			$this->template->assign('ShoppingComparisonModules', $shoppingComparisonModules);

			foreach ($categories as &$category) {
				// if the left and right values aren't sequential it means that this node has children
				$category['haschildren'] = ($category['catnsetleft'] + 1) < $category['catnsetright'];
				$category['cataltcategoriescache'] = (array)json_decode($category['cataltcategoriescache']);
				foreach($category['cataltcategoriescache'] as &$altcat) {
					$altcat->path = addslashes($altcat->path);
				}

				// get amount of products in just this category
				$query = "
					SELECT
						COUNT(*) as prodcount,
						(
							SELECT
								COUNT(*)
							FROM
								[|PREFIX|]categoryassociations ca
								LEFT JOIN [|PREFIX|]categories c ON c.categoryid = ca.categoryid
							WHERE
								c.catnsetleft > " . $category['catnsetleft'] . " AND
								c.catnsetright < " . $category['catnsetright'] . "
						) AS subcatcount
					FROM
						[|PREFIX|]categoryassociations ca
					WHERE
						categoryid = " . $category['categoryid'];

				$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$countRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res);
				$category['prodcount'] = $countRow['prodcount'];
				$category['subcatprodcount'] = $countRow['subcatcount'];

				$category['link'] = CatLink($category['categoryid'], $category['catname']);
			}

			$this->template->assign('categoryId', $parentid);
			$this->template->assign('categories', $categories);

			$this->template->assign('hasCreateCatPermission', $GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Create_Category));
			$this->template->assign('hasEditCatPermission', $GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Categories));
			$this->template->assign('hasDeleteCatPermission', $GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Categories));

			if ($parentid > 0) {
				$this->template->assign('isChild', true);
			}

			return $this->template->render('category.grid.tpl');
		}

		private function ManageCategories($MsgDesc = "", $MsgStatus = "")
		{
			// Show a list of categories to edit/delete
			$arrCatList = array();
			$GLOBALS['CategoryGrid'] = "";

			// If d is set, we've deleted a category. For some strange reason
			// it shows a duplicate list of categories if we don't explicitly
			// refresh the page.
			if (isset($_GET['d'])) {
				$MsgDesc = GetLang('CatDeletedSuccessfully');
				$MsgStatus = MSG_SUCCESS;
			}

			$GLOBALS['Message'] = '';
			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}
			$GLOBALS['Message'] .= GetFlashMessageBoxes();

			$categoryList = $this->_BuildCategoryList(0);
			$this->template->assign('CategoryList', $categoryList);

			// Get shopping comparison engine modules that are anabled and
			// have category taxonomies to provide bulk mapping options via
			// the bulk editing select box.
			$this->template->assign(
				'ShoppingComparisonModules',
				Isc_ShoppingComparison::getModulesWithTaxonomies());

			if ($categoryList != '') {
				$this->template->display('category.manage.tpl');
			} else {
				// There aren't any questions, show a message so they can create some
				$MsgDesc = GetLang('NoCategories');
				$MsgStatus = MSG_SUCCESS;
				if ($MsgDesc === '') {
					$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
				}

				$GLOBALS['DisableDelete'] = "DISABLED";

				$GLOBALS['Title'] = GetLang('ManageCategories');
				$GLOBALS['ManageCatIntro'] = GetLang('ManageCatIntro');
				$GLOBALS['ButtonText'] = GetLang('CreateCategory');
				$GLOBALS['ButtonClass'] = "Field150";
				$GLOBALS['URL'] = "index.php?ToDo=createCategory";
				$GLOBALS['DisplayGrid'] = "none";

				$this->template->display('category.manage.tpl');
			}
		}

		private function DeleteCategory($cids=array(), $redirect=true)
		{
			if (empty($cids)) {
				$cids = $_POST['categories'];
			}

			if ($this->categoryAPI->multiDelete($cids)) {
				$catIds = array_keys($cids);
				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				$optimizer->deletePerItemOptimizerConfig('category', $catIds);

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($catIds));

				if ($redirect) {
					ob_end_clean();
					header("Location: index.php?ToDo=viewCategories&d=1");
					die();
				}
			} else {
				echo $this->categoryAPI->error;
				return false;
			}

			return true;
		}

		private function CreateCategory($MsgDesc = "", $MsgStatus = "", $IsError = false)
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			// Create a new category
			$cat = array();
			$enableOptimizer = 0;

			if (isset($_GET['parentId'])) {
				$cat[] = $_GET['parentId'];
			}

			$GLOBALS['CategorySort'] = 0;
			if ($IsError) {
				// The user has tried to create a category that already exists
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);

				$arrData = $this->_GetCatData(ISC_SOURCE_FORM, 0);
				$cat[] = $arrData['category'];
				$enableOptimizer = $arrData['cat_enable_optimizer'];

				$GLOBALS['CategoryName'] = isc_html_escape($arrData['catname']);
				$GLOBALS['CategorySort'] = $arrData['catsort'];

				$GLOBALS['CategoryPageTitle'] = $arrData['catpagetitle'];
				$GLOBALS['CategoryMetaKeywords'] = $arrData['catmetakeywords'];
				$GLOBALS['CategoryMetaDesc'] = $arrData['catmetadesc'];

			}

			$GLOBALS['CategoryOptions'] = $this->GetCategoryOptions($cat);

			$GLOBALS['CategorySearchKeywords'] = '';

			$wysiwygOptions = array(
				'id'		=> 'wysiwyg',
				'width'		=> '656px',
				'height'	=> '250px'
			);
			$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

			$GLOBALS['FormAction'] = "saveCategory";
			$GLOBALS['CatTitle'] = GetLang('AddCatTitle');
			$GLOBALS['CatIntro'] = GetLang('AddCatIntro');
			$GLOBALS['CancelMessage'] = GetLang('CancelCreateCategory');

			if (empty($cat) || in_array("0", $cat)) {
				$GLOBALS['DisableFileUpload'] = 'disabled="disabled"';
				$GLOBALS['ShowFileUploadMessage'] = '';
			} else {
				$GLOBALS['DisableFileUpload'] = '';
				$GLOBALS['ShowFileUploadMessage'] = 'none';
			}

			// Get a list of all layout files
			$GLOBALS['LayoutFiles'] = GetCustomLayoutFilesAsOptions("category.html");


			if(isset($_REQUEST['currentTab'])) {
				$GLOBALS['CurrentTab'] = $_REQUEST['currentTab'];
			}
			else {
				$GLOBALS['CurrentTab'] = 'details';
			}

			$GLOBALS['GoogleWebsiteOptimizerIntro'] = GetLang('EnableGoogleWebsiteOptimizerAfterSave');
			$GLOBALS['ShowEnableGoogleWebsiteOptimzer'] = 'display:none';
			$GLOBALS['DisableOptimizerCheckbox'] = 'DISABLED=DISABLED';
			$GLOBALS['SkipOptimizerConfirmMsg'] = 'true';
			$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndAddAnother');

			// Get shopping comparison options
			$shoppingComparisonModules = Isc_ShoppingComparison::getModulesWithTaxonomies();
			$GLOBALS['ShoppingComparisonModules'] = $shoppingComparisonModules;

			$this->template->assign('ShowYesUseImageRow', 'none');
			$this->template->display('category.form.tpl');
		}

		private function SaveCategory()
		{
			//Validate Google Website Optimizer form
			if(isset($_POST['catenableoptimizer'])) {
				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				$error = $optimizer -> validateConfigForm();
				if($error!='') {
					$this->CreateCategory($error, MSG_ERROR, true);
					exit;
				}
			}

			$error = $this->_CommitCategory();

			if (empty($error)) {
				$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateRootCategories();

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['catname']);

				if(isset($_POST['AddAnother'])) {
					$location = 'index.php?ToDo=createCategory';
				}
				else {
					$location= 'index.php?ToDo=viewCategories';
				}

				FlashMessage(sprintf(GetLang('CatSavedSuccessfully'), isc_html_escape($_POST['catname'])), MSG_SUCCESS, $location);
			} else {
				$this->CreateCategory(sprintf(GetLang('CatSaveFailed'), isc_html_escape($_POST['catname']), $error), MSG_ERROR, true);
				exit;
			}
		}

		private function _GetCatData($DataSource, $CatId = 0)
		{
			$arrCat = array();

			if(isset($_POST["wysiwyg_html"])) {
				$_POST['catdesc'] = ISC_ADMIN_PRODUCT::FormatWYSIWYGHTML($_POST['wysiwyg_html']);
			}
			elseif(isset($_POST["wysiwyg"])) {
				$_POST['catdesc'] = ISC_ADMIN_PRODUCT::FormatWYSIWYGHTML($_POST['wysiwyg']);
			}

			if ($DataSource == ISC_SOURCE_FORM) {
				// Get the details of the category from the database
				$arrCat['catname'] = $_POST['catname'];
				$arrCat['catdesc'] = $_POST['catdesc'];
				$arrCat['category'] = $_POST['catparentid'];
				$arrCat['catparentid'] = $_POST['catparentid'];
				$arrCat['oldCatId'] = @$_POST['categoryId'];
				$arrCat['catsort'] = (int)$_POST['catsort'];
				$arrCat['catpagetitle'] = $_POST['catpagetitle'];
				$arrCat['catmetakeywords'] = $_POST['catmetakeywords'];
				$arrCat['catmetadesc'] = $_POST['catmetadesc'];
				$arrCat['catsearchkeywords'] = $_POST['catsearchkeywords'];
				$arrCat['catlayoutfile'] = $_POST['catlayoutfile'];

				if(isset($_POST['catenableoptimizer'])) {
					$arrCat['cat_enable_optimizer'] = 1;
				} else {
					$arrCat['cat_enable_optimizer'] = 0;
				}

			} else {
				// Get category details from the database
				$query = sprintf("select * from [|PREFIX|]categories where categoryid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($CatId));
				$catResult = $GLOBALS["ISC_CLASS_DB"]->Query($query);

				if ($GLOBALS["ISC_CLASS_DB"]->CountResult($catResult) == 1) {
					$arrCat = $GLOBALS["ISC_CLASS_DB"]->Fetch($catResult);
				}
			}

			return $arrCat;
		}

		public function _CommitCategory($catID = 0, $updateCache = true)
		{
			if (isId($catID)) {
				$this->categoryAPI->load($catID);
			}

			// Handle the image first
			$catData = $this->_GetCatData(ISC_SOURCE_FORM);
			$catImage = $this->categoryAPI->catimagefile;

			if ($this->categoryAPI->catimagefile !== '') {

				// Are we deleting the existing image?
				if (array_key_exists('delcatimagefile', $_POST) && $_POST['delcatimagefile']) {
					$this->DelCategoryImage($this->categoryAPI->catimagefile);
					$catImage = '';
				}

				// Also forcefully delete the image if it is a root category
				if ($catData['category'] == "0") {
					$this->DelCategoryImage($this->categoryAPI->catimagefile);
					$catImage = '';
				}

				// Delete the image if we do uncheck the image selection.
				if (empty ($_POST['YesUseImage'])) {
					$this->DelCategoryImage($this->categoryAPI->catimagefile);
					$catImage = '';
				}
			}

			// Saving a new image
			if (array_key_exists('catimagefile', $_FILES) && (int)$_FILES['catimagefile']['error'] == 0 && !empty ($_POST['YesUseImage'])) {

				// Delete the old image if we are uploading a new one
				if ($this->categoryAPI->catimagefile !== '') {
					$this->DelCategoryImage($this->categoryAPI->catimagefile);
				}

				$catImage = $this->SaveCategoryImage();
			}

			$_POST['catimagefile'] = $catImage;

			// Clean up the description
			if(isset($_POST["wysiwyg_html"])) {
				$_POST['catdesc'] = ISC_ADMIN_PRODUCT::FormatWYSIWYGHTML($_POST['wysiwyg_html']);
			}
			elseif(isset($_POST["wysiwyg"])) {
				$_POST['catdesc'] = ISC_ADMIN_PRODUCT::FormatWYSIWYGHTML($_POST['wysiwyg']);
			}


			if(isset($_POST["catenableoptimizer"])) {
				$_POST['cat_enable_optimizer'] = 1;
			}
			else {
				$_POST['cat_enable_optimizer'] = 0;
			}

			// Now we save using the API
			if (!isId($catID)) {
				$catID = $this->categoryAPI->create($updateCache);
				if($catID) {
					$GLOBALS['NewCategoryId'] = $catID;
				}
			} else {
				$this->categoryAPI->save();
			}

			if(empty($this->categoryAPI->error)) {
				// Save shopping comparison category mappings
				$shoppingComparisonModules = Isc_ShoppingComparison::getModulesWithTaxonomies();
				$altCategoriesCache = (array)json_decode($this->categoryAPI->cataltcategoriescache);
				foreach($shoppingComparisonModules as $module) {
					$id = $_POST[$module->getId().'_categoryid'];
					if(isset($altCategoriesCache[$module->getId()]))
						$oldid = $altCategoriesCache[$module->getId()]->categoryid;
					else
						$oldid = '';

					if($oldid == $id)
						continue;
					else if($oldid && $id === '') {
						$module->deleteCategoryMappings($catID);
						continue;
					}
					else
					{
						//@todo: efficiency tweak - need to map multiple categories
						//in one go, rather than in seperate mapCategories calls, also
						//should not rely on the $path passed in the form.
						$path = $_POST[$module->getId().'_categorypath'];
						$module->mapCategories($catID, $id, $path);
					}
				}

				// Save optimizer settings for this category
				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				if(isset($_POST["catenableoptimizer"])) {
					$optimizer->savePerItemOptimizerConfig('category', $catID);
				} else {
					$optimizer->deletePerItemOptimizerConfig('category', array($catID));
				}
			}

			return $this->categoryAPI->error;
		}

		/**
		 * Get the list of category options to show as parent categories. Prunes impossible
		 * parents from the list
		 *
		 * @param int $CategoryId The Category Id that we are choosing a parent for
		 * @param array The array of selected category ids
		 *
		 * @return string
		 **/
		public function GetCategoryParentOptions($CategoryId, $SelectedCats)
		{
			$cats = '';
			$Container = "<option %s value='%d'>%s</option>";
			$Sel = "selected=\"selected\"";
			$Divider = "- ";

			// storage for a list of category ids to filter out from the parent options (which should be the category itself plus all its current child nodes)
			$impossible_parents = array();
			foreach ($this->nestedset->getTree(array('categoryid'), $CategoryId) as $childCategory) {
				$impossible_parents[] = (int)$childCategory['categoryid'];
			}

			$categories = $this->getCats();

			$noParent = '';
			if (in_array('0', $SelectedCats)) {
				$noParent = ' selected="selected"';
			}

			$cats = "<option value='0'" . $noParent . ">-- " . isc_html_escape(GetLang("NoParent")) . " --</option>\n";

			foreach ($categories as $cid => $cname) {
				if (in_array($cid, $impossible_parents)) {
					continue;
				}
				if (in_array($cid, $SelectedCats)) {
					$s = $Sel;
				} else {
					$s = '';
				}
				$cats .= sprintf($Container, $s, $cid, $cname);
			}

			return $cats;
		}

		/**
		* GetCategoryOptions
		* Get an html options box with categories in it. Categories which are pre
		* selected can be specified as can the format of the html
		*
		* @param array $SelectedCats The cats to pre select in the list
		* @param string $Container The html to use for the option
		* @param string $sel The html to use to signify a cat is selected
		* @param string $Divider The text to prefix sub cats with
		* @param bool $IncludeEmpty Add an option at the top for "
		* please select a category"
		* @param array $hide If not empty then hide catids in this array
		* @param array $visibleCats A list of categories (array) that should be in the select.
		*
		* @return string The html for the options
		*/
		public function GetCategoryOptions($SelectedCats = 0, $Container = "<option %s value='%d'>%s</option>", $Sel = "selected=\"selected\"", $Divider = "- ", $IncludeEmpty = true, $visible='', $visibleCats=array())
		{
			// Get a list of categories as <option> tags
			$cats = '';

			// Make sure $SelectedCats is an array
			if (!is_array($SelectedCats)) {
				$SelectedCats = array();
			}

			if (empty($SelectedCats) || in_array("0", $SelectedCats)) {
				$sel = 'selected="selected"';
			} else {
				$sel = "";
			}

			// Do we include the no parent category item in the list ?
			if ($IncludeEmpty) {
				$cats = sprintf("<option %s value='0'>-- %s --</option>\n", $sel, GetLang("NoParent"));
			}

			// Get a formatted list of all the categories in the system
			$categories = $this->getCats($Divider, 0, '', $visible);

			foreach ($categories as $cid => $cname) {
				// If we're on the front end of the store, do we have permission to view this category?
				if(!defined('ISC_ADMIN_CP') && !CustomerGroupHasAccessToCategory($cid)) {
					continue;
				}
				// Not showing this category in the list
				else if(!empty($visibleCats) && !in_array($cid, $visibleCats)) {
					continue;
				}
				if (in_array($cid, $SelectedCats)) {
					$s = $Sel;
				} else {
					$s = '';
				}
				$cats .= sprintf($Container, $s, $cid, $cname);
			}

			return $cats;
		}

		private function EditCategory()
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			if (isset($_GET['catId'])) {
				$catId = (int) $_GET['catId'];

				$this->categoryAPI->load($catId);

				$GLOBALS['CategoryName'] = isc_html_escape($this->categoryAPI->catname);
				$GLOBALS['CategoryOptions'] = $this->GetCategoryParentOptions($catId, array($this->categoryAPI->catparentid));
				$GLOBALS['CategorySort'] = isc_html_escape($this->categoryAPI->catsort);
				$GLOBALS['CategoryPageTitle'] = isc_html_escape($this->categoryAPI->catpagetitle);
				$GLOBALS['CategoryMetaKeywords'] = isc_html_escape($this->categoryAPI->catmetakeywords);
				$GLOBALS['CategoryMetaDesc'] = isc_html_escape($this->categoryAPI->catmetadesc);
				$GLOBALS['CategorySearchKeywords'] = isc_html_escape($this->categoryAPI->catsearchkeywords);

				$wysiwygOptions = array(
					'id'		=> 'wysiwyg',
					'width'		=> '656px',
					'height'	=> '250px',
					'value'		=> $this->categoryAPI->catdesc
				);
				$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

				$GLOBALS['FormAction'] = "saveUpdatedCategory";
				$GLOBALS['CatTitle'] = GetLang('EditCatTitle');
				$GLOBALS['CatIntro'] = GetLang('EditCatIntro');
				$GLOBALS['CancelMessage'] = GetLang('CancelEditCategory');
				$GLOBALS['hiddenFields'] = sprintf("<input type='hidden' name='categoryId' value='%d'>", $catId);

				if ($this->categoryAPI->catparentid == '0') {
					$GLOBALS['DisableFileUpload'] = 'disabled="disabled"';
					$GLOBALS['ShowFileUploadMessage'] = '';
				} else {
					$GLOBALS['DisableFileUpload'] = '';
					$GLOBALS['ShowFileUploadMessage'] = 'none';
				}

				// Get a list of all layout files
				$layoutFile = 'category.html';
				if($this->categoryAPI->catlayoutfile != '') {
					$layoutFile = $this->categoryAPI->catlayoutfile;
				}
				$GLOBALS['LayoutFiles'] = GetCustomLayoutFilesAsOptions("category.html", $layoutFile);

				$GLOBALS["CatImageMessage"] = '';
				$this->template->assign('ShowYesUseImageRow', 'none');
				if ($this->categoryAPI->catimagefile !== '') {
					$image = '../' . GetConfig('ImageDirectory') . '/' . $this->categoryAPI->catimagefile;
					$GLOBALS["CatImageMessage"] = sprintf(GetLang('CatImageDesc'), $image, $this->categoryAPI->catimagefile);
					$this->template->assign('CatImageFile', basename($this->categoryAPI->catimagefile));
					$this->template->assign('ShowYesUseImageRow', 'block');
					$this->template->assign('CatImageLink', $image);
				}


				//Google website optimizer
				$GLOBALS['GoogleWebsiteOptimizerIntro'] = GetLang('CatGoogleWebsiteOptimizerIntro');
				$GLOBALS['HideOptimizerConfigForm'] = 'display:none;';
				$GLOBALS['CheckEnableOptimizer'] = '';
				$GLOBALS['SkipOptimizerConfirmMsg'] = 'true';

				$enabledOptimizers = GetConfig('OptimizerMethods');
				if(!empty($enabledOptimizers)) {
					foreach ($enabledOptimizers as $id => $date) {
						GetModuleById('optimizer', $optimizerModule, $id);
						if ($optimizerModule->_testPage == 'categories' || $optimizerModule->_testPage == 'all') {
							$GLOBALS['SkipOptimizerConfirmMsg'] = 'false';
							break;
						}
					}
				}
				if($this->categoryAPI->cat_enable_optimizer == '1') {
					$GLOBALS['HideOptimizerConfigForm'] = '';
					$GLOBALS['CheckEnableOptimizer'] = 'Checked';
				}

				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				$GLOBALS['OptimizerConfigForm'] = $optimizer->showPerItemConfigForm('category', $catId, CatLink($catId, $this->categoryAPI->catname));

				if(isset($_REQUEST['currentTab'])) {
					$GLOBALS['CurrentTab'] = $_REQUEST['currentTab'];
				}
				else {
					$GLOBALS['CurrentTab'] = 'details';
				}

				// Get shopping comparison options
				$shoppingComparisonModules = Isc_ShoppingComparison::getModulesWithTaxonomies();
				$GLOBALS['AlternateCategoriesCache'] = (array)json_decode($this->categoryAPI->cataltcategoriescache);
				$GLOBALS['ShoppingComparisonModules'] = $shoppingComparisonModules;

				$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndContinueEditing');

				$this->template->display('category.form.tpl');
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Categories)) {
					$this->ManageCategories();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		private function SaveUpdatedCategory()
		{
			$catData = $this->_GetCatData(ISC_SOURCE_FORM);
			$existingData = $this->_GetCatData(ISC_SOURCE_DATABASE, $catData['oldCatId']);

			$currentTab='details';
			if(isset($_POST['currentTab']) && $_POST['currentTab'] != '') {
				$currentTab = '&currentTab='.$_POST['currentTab'];
			}

			//Validate Google Website Optimizer form
			if(isset($_POST['catenableoptimizer'])) {
				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				$error = $optimizer -> validateConfigForm();
				if($error!='') {
					FlashMessage($error, MSG_ERROR, 'index.php?ToDo=editCategory&catId='.(int)$catData['oldCatId']).'&currentTab=optimizer';
				}
			}

			$error = $this->_CommitCategory($_POST['categoryId']);

			if (trim($error) == '') {

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($catData['oldCatId'], $catData['category']);

				if ($existingData['catparentid'] !== $catData['catparentid'] || $existingData['catname'] !== $catData['catname'] || $existingData['catsort'] !== $catData['catsort']) {
					// if the category parent id, name or sort order value has changed, this could trigger a change in the order in which categories are displayed
					// this must happen first before any other cache updates since other caches may rely on it
					$this->rebuildNestedSetTree();
				}

				// If the category doesn't have a parent, rebuild the root categories cache
				$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateRootCategories();

				if($existingData['catparentid'] != $catData['catparentid']) {

					// Rebuild the parent list
					$parentList = $this->categoryAPI->BuildParentList($catData['oldCatId']);
					$updatedCategory = array(
						"catparentlist" => $parentList
					);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("categories", $updatedCategory, "categoryid='".$GLOBALS['ISC_CLASS_DB']->Quote((int)$catData['oldCatId'])."'");

					// Now we also need to update the parent list of all child pages for this category
					$query = sprintf("SELECT categoryid FROM [|PREFIX|]categories WHERE CONCAT(',', catparentlist, ',') LIKE '%%,%s,%%'", $GLOBALS['ISC_CLASS_DB']->Quote($catData['oldCatId']));
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					while($child = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$parentList = $this->categoryAPI->BuildParentList($child['categoryid']);
						// Update the parent list for this child
						$updatedCategory = array(
							"catparentlist" => $parentList
						);
						$GLOBALS['ISC_CLASS_DB']->UpdateQuery("categories", $updatedCategory, "categoryid='".$GLOBALS['ISC_CLASS_DB']->Quote($child['categoryid'])."'");
					}

					// Rebuild the group pricing caches
					$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCustomerGroupsCategoryDiscounts();
				}

				if(isset($_POST['AddAnother'])) {
					$location = 'index.php?ToDo=editCategory&catId='.(int)$catData['oldCatId'].$currentTab;
				}
				else {
					$location= 'index.php?ToDo=viewCategories';
				}

				//save optimizer settings for this product
				$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
				if(isset($_POST['catenableoptimizer'])) {
					$optimizer->savePerItemOptimizerConfig('category', $catData['oldCatId']);
				} else {
					$optimizer->deletePerItemOptimizerConfig('category', array($catData['oldCatId']));
				}

				FlashMessage(GetLang('CatUpdateSuccessfully'), MSG_SUCCESS, $location);
			} else {
				FlashMessage(sprintf(GetLang('CatUpdateFailed'), isc_html_escape($existingData['catname']), $error), MSG_ERROR, 'index.php?ToDo=editCategory&catId='.(int)$catData['oldCatId']).$currentTab;
			}
		}

		public function RemoveRootImages()
		{
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]categories WHERE catparentid='0' AND catimagefile != ''");

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$this->DelCategoryImage($row['categoryid']);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('categories', array('catimagefile' => ''), "categoryid='" . (int)$row['categoryid'] . "'");
			}
		}

		private function SaveCategoryImage()
		{
			if (!array_key_exists('catimagefile', $_FILES) || $_FILES['catimagefile']['error'] !== 0 || strtolower(substr($_FILES['catimagefile']['type'], 0, 6)) !== 'image/') {
				return false;
			}

			// Attempt to set the memory limit so we can resize this image
			ISC_IMAGE_LIBRARY_FACTORY::setImageFileMemLimit($_FILES['catimagefile']['tmp_name']);

			// Determine the destination directory
			$randomDir = strtolower(chr(rand(65, 90)));
			$destPath = realpath(ISC_BASE_PATH.'/' . GetConfig('ImageDirectory'));

			if (!is_dir($destPath . '/' . $randomDir)) {
				if (!isc_mkdir($destPath . '/' . $randomDir)) {
					$randomDir = '';
				}
			}

			$destFile = GenRandFileName($_FILES['catimagefile']['name'], 'category');
			$destPath = $destPath . '/' . $randomDir . '/' . $destFile;
			$returnPath = $randomDir . '/' . $destFile;

			$tmp = explode('.', $_FILES['catimagefile']['name']);
			$ext = strtolower($tmp[count($tmp)-1]);

			if ($ext == 'jpg') {
				$srcImg = imagecreatefromjpeg($_FILES['catimagefile']['tmp_name']);
			} else if($ext == 'gif') {
				$srcImg = imagecreatefromgif($_FILES['catimagefile']['tmp_name']);
				if(!function_exists('imagegif')) {
					$gifHack = 1;
				}
			} else {
				$srcImg = imagecreatefrompng($_FILES['catimagefile']['tmp_name']);
			}

			$srcWidth = imagesx($srcImg);
			$srcHeight = imagesy($srcImg);
			$widthLimit = GetConfig('CategoryImageWidth');
			$heightLimit = GetConfig('CategoryImageHeight');

			// If the image is small enough, simply move it
			if($srcWidth <= $widthLimit && $srcHeight <= $heightLimit) {
				imagedestroy($srcImg);
				move_uploaded_file($_FILES['catimagefile']['tmp_name'], $destPath);
				// set image to be writable
				isc_chmod($destPath, ISC_WRITEABLE_FILE_PERM);
				return $returnPath;
			}

			// Otherwise, resize it
			$attribs = getimagesize($_FILES['catimagefile']['tmp_name']);
			$width = $attribs[0];
			$height = $attribs[1];

			if($width > $widthLimit) {
				$height = ceil(($widthLimit/$width)*$height);
				$width = $widthLimit;
			}

			if($height > $heightLimit) {
				$width = ceil(($heightLimit/$height)*$width);
				$height = $heightLimit;
			}

			$dstImg = imagecreatetruecolor($width, $height);
			if($ext == "gif" && !isset($gifHack)) {
				$colorTransparent = imagecolortransparent($srcImg);
				imagepalettecopy($srcImg, $dstImg);
				imagecolortransparent($dstImg, $colorTransparent);
				imagetruecolortopalette($dstImg, true, 256);
			}
			else if($ext == "png") {
				ImageColorTransparent($dstImg, ImageColorAllocate($dstImg, 0, 0, 0));
				ImageAlphaBlending($dstImg, false);
			}

			imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);

			if ($ext == "jpg") {
				imagejpeg($dstImg, $destPath, 100);
			} else if($ext == "gif") {
				if(isset($gifHack) && $gifHack == true) {
					$thumbFile = isc_substr($destPath, 0, -3)."jpg";
					imagejpeg($dstImg, $destPath, 100);
				}
				else {
					imagegif($dstImg, $destPath);
				}
			} else {
				imagepng($dstImg, $destPath);
			}

			@imagedestroy($dstImg);
			@imagedestroy($srcImg);
			@unlink($_FILES['catimagefile']['tmp_name']);

			// Change the permissions on the thumbnail file
			isc_chmod($destPath, ISC_WRITEABLE_FILE_PERM);

			return $returnPath;
		}

		private function DelCategoryImage($file)
		{
			if (isId($file)) {
				if (!($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($GLOBALS["ISC_CLASS_DB"]->Query("SELECT * FROM [|PREFIX|]categories WHERE categoryid='" . (int)$file . "'")))) {
					return false;
				}

				if ($row['catimagefile'] == '') {
					return true;
				} else {
					$file = $row['catimagefile'];
				}
			}

			$file = realpath(ISC_BASE_PATH.'/' . GetConfig('ImageDirectory') . '/' . $file);

			if ($file == '') {
				return false;
			}

			if (file_exists($file)) {
				@unlink($file);
				clearstatcache();
			}

			return !file_exists($file);
		}

		/**
		 * Construct the array of required information.
		 *
		 * @return array The data to pass to javascript.
		 **/
		public function getReassignCategoryStep1Data()
		{
			// Get the product count.
			$cats = array();
			foreach ($_POST['categories'] as $c => $v) {
				$cats[] = (int) $c;
			}
			$ids = $this->categoryAPI->getSubCategories($cats);
			$prods = $this->categoryAPI->getExclusiveProductsForCategories($ids);

			// Get the HTML for new cat selector.
			$showCats = $this->categoryAPI->getSubCategories(0, $ids);
			$format = "<option %s value='%d'>%s</option>";
			$sel = 'selected="selected"';
			$first = 0;
			foreach ($showCats as $k => $v) {
				// Select the first option as default.
				$first = array($v);
				break;
			}
			$options = $this->getCategoryOptions($first, $format, $sel, '-&nbsp;', false, '', $showCats);

			$data = array(
				'categories' => $cats,
				'catCount' => count($cats),
				'products' => $prods,
				'prodCount' => count($prods),
				'options' => $options,
			);

			return $data;
		}

		/**
		 * Returns parent lineage of a particular category.
		 *
		 * @param int $catid The Category ID that we are getting lineage for.
		 * @param string $separator The separator string between each parent.
		 *
		 * @return string The parent lineage.
		 **/
		public function getParentLineage($catid, $separator = ' > ')
		{
			$catid = (int)$catid;

			if (!$catid) {
				return getLang("NoParent");
			}

			$parents = array();
			foreach ($this->nestedset->getParentPath(array('catname'), $catid) as $category) {
				$parents[] = $category['catname'];
			}

			return implode($separator, $parents);
		}

		/**
		 * Reassigns products and deletes selected categories.
		 *
		 * @return void
		 **/
		public function reassigncategory()
		{
			// Sanitise input.
			$newCat = (int) $_POST['parentCat'];
			$categories = array();
			foreach ($_POST['categories'] as $c) {
				$categories[(int) $c] = '';
			}

			$res = true;
			$reassign = false;
			if ($_POST['reassign'] == 'true') {
				$reassign = true;
			}

			if ($reassign) {
				// Reassign exclusive products to new category.
				foreach ($_POST['products'] as $p) {
					$values = array(
						'productid' => (int) $p,
						'categoryid' => $newCat,
					);
					$res = $this->db->insertQuery("categoryassociations", $values);
					if ($res == false) {
						break;
					}
				}
			}

			if ($res) {
				// Delete selected categories.
				$res = $this->deleteCategory($categories, false);
			}

			if ($res) {
				if ($reassign) {
					flashMessage(getLang('ReassignSuccessFlashMsg'), MSG_SUCCESS);
				} else {
					flashMessage(getLang('CatDeletedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				flashMessage(getLang('ReassignErrorFlashMsg'), MSG_ERROR);
			}
		}
	}
