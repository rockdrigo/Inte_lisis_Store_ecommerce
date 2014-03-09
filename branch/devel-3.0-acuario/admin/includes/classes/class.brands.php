<?php

	class ISC_ADMIN_BRANDS extends ISC_ADMIN_BASE
	{
		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('brands');
			switch (isc_strtolower($Do)) {
				case "saveeditedbrand":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Brands)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Brands') => "index.php?ToDo=viewBrands");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SaveEditedBrand();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "editbrand":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Brands)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Brands') => "index.php?ToDo=viewBrands", GetLang('EditBrand') => "index.php?ToDo=editBrand");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditBrand();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "savenewbrands":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Brands)) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SaveNewBrands();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "addbrand":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Brands)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Brands') => "index.php?ToDo=viewBrands", GetLang('AddBrands') => "index.php?ToDo=addBrand");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddBrands();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "deletebrands":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Brands)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Brands') => "index.php?ToDo=viewBrands");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteBrands();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				default:
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Brands)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Brands') => "index.php?ToDo=viewBrands");

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}

						$this->ManageBrands();

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
			}
		}

		public function _GetBrandList(&$Query, $Start, $SortField, $SortOrder, &$NumResults)
		{
			// Return an array containing details about brands.
			// Takes into account search too.

			$Query = trim($Query);

			$query = "
				SELECT *, (SELECT COUNT(productid) FROM [|PREFIX|]products p WHERE p.prodbrandid=b.brandid) AS products
				FROM [|PREFIX|]brands b
			";

			$countQuery = "SELECT COUNT(*) FROM [|PREFIX|]brands b";

			$queryWhere = ' WHERE 1=1 ';
			if ($Query != "") {
				$queryWhere .= " AND b.brandname LIKE '%".$GLOBALS['ISC_CLASS_DB']->Quote($Query)."%'";
			}

			$query .= $queryWhere;
			$countQuery .= $queryWhere;

			$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
			$NumResults = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			if($NumResults > 0) {
				$query .= " ORDER BY ".$SortField." ".$SortOrder;

				// Add the limit
				$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_BRANDS_PER_PAGE);
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
				return $result;
			}
			else {
				return false;
			}
		}

		public function ManageBrandsGrid(&$numBrands)
		{
			// Show a list of news in a table
			$page = 0;
			$start = 0;
			$numBrands = 0;
			$numPages = 0;
			$GLOBALS['BrandGrid'] = "";
			$GLOBALS['Nav'] = "";
			$max = 0;
			$searchURL = '';

			if (isset($_GET['searchQuery'])) {
				$query = $_GET['searchQuery'];
				$GLOBALS['Query'] = isc_html_escape($query);
				$searchURL .'searchQuery='.urlencode($query);
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
				"Brand" => "b.brandname",
				"Products" => "products",
			);

			if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("ManageBrands", $_REQUEST['sortField'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("ManageBrands", "b.brandname", $sortOrder);
			}

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			}
			else {
				$page = 1;
			}

			$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
			$GLOBALS['SortURL'] = $sortURL;

			// Limit the number of brands returned
			if ($page == 1) {
				$start = 1;
			}
			else {
				$start = ($page * ISC_BRANDS_PER_PAGE) - (ISC_BRANDS_PER_PAGE-1);
			}

			$start = $start-1;

			// Get the results for the query
			$brandResult = $this->_GetBrandList($query, $start, $sortField, $sortOrder, $numBrands);
			$numPages = ceil($numBrands / ISC_BRANDS_PER_PAGE);

			// Workout the paging navigation
			if($numBrands > ISC_BRANDS_PER_PAGE) {
				$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);

				$GLOBALS['Nav'] .= BuildPagination($numBrands, ISC_BRANDS_PER_PAGE, $page, sprintf("index.php?ToDo=viewBrands%s", $sortURL));
			}
			else {
				$GLOBALS['Nav'] = "";
			}

			$GLOBALS['SearchQuery'] = $query;
			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewBrands&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);


			// Workout the maximum size of the array
			$max = $start + ISC_BRANDS_PER_PAGE;

			if ($max > count($brandResult)) {
				$max = count($brandResult);
			}

			if($numBrands > 0) {
				// Display the news
				while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($brandResult)) {
					$GLOBALS['BrandId'] = (int) $row['brandid'];
					$GLOBALS['BrandName'] = isc_html_escape($row['brandname']);
					$GLOBALS['Products'] = (int) $row['products'];

					// Workout the edit link -- do they have permission to do so?
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Brands)) {
						$GLOBALS['EditBrandLink'] = sprintf("<a title='%s' class='Action' href='index.php?ToDo=editBrand&amp;brandId=%d'>%s</a>", GetLang('BrandEdit'), $row['brandid'], GetLang('Edit'));
					} else {
						$GLOBALS['EditNewsLink'] = sprintf("<a class='Action' disabled>%s</a>", GetLang('Edit'));
					}

					$GLOBALS['BrandGrid'] .= $this->template->render('brands.manage.row.tpl');
				}
				return $this->template->render('brands.manage.grid.tpl');
			}
		}

		public function ManageBrands($MsgDesc = "", $MsgStatus = "")
		{
			// Fetch any results, place them in the data grid
			$numBrands = 0;
			$GLOBALS['BrandsDataGrid'] = $this->ManageBrandsGrid($numBrands);

			// Was this an ajax based sort? Return the table now
			if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['BrandsDataGrid'];
				return;
			}

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			if (isset($_GET['searchQuery'])) {
				$GLOBALS['ClearSearchLink'] = '<a id="SearchClearButton" href="index.php?ToDo=viewBrands">'.GetLang('ClearResults').'</a>';
			} else {
				$GLOBALS['ClearSearchLink'] = '';
			}

			$GLOBALS['BrandIntro'] = GetLang('ManageBrandsIntro');

			// Do we need to disable the delete button?
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Brands) || $numBrands == 0) {
				$GLOBALS['DisableDelete'] = "DISABLED";
			}

			// No results
			if($numBrands == 0) {
				$GLOBALS['DisplayGrid'] = "none";
				if(count($_GET) > 1) {
					if ($MsgDesc == "") {
						$GLOBALS['Message'] = MessageBox(GetLang('NoBrandResults'), MSG_ERROR);
					}
				}
				else {
					$GLOBALS['DisplaySearch'] = "none";
					$GLOBALS['Message'] = MessageBox(GetLang('NoBrands'), MSG_SUCCESS);
				}
			}

			$this->template->display('brands.manage.tpl');
		}

		public function DeleteBrands()
		{
			if (isset($_POST['brands'])) {

				$brandids = implode("','", $GLOBALS['ISC_CLASS_DB']->Quote($_POST['brands']));

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['brands']));

				// Delete the brands
				$query = sprintf("delete from [|PREFIX|]brands where brandid in ('%s')", $brandids);
				$GLOBALS["ISC_CLASS_DB"]->Query($query);

				// Delete the brand associations
				$updatedProducts = array(
					"prodbrandid" => 0
				);

				// Delete the search record
				$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("brand_search", "WHERE brandid IN('" . $brandids . "')");

				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("products", $updatedProducts, "prodbrandid IN ('".$brandids."')");
				$err = $GLOBALS["ISC_CLASS_DB"]->Error();
				if ($err != "") {
					$this->ManageBrands($err, MSG_ERROR);
				} else {
					$this->ManageBrands(GetLang('BrandsDeletedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Brands)) {
					$this->ManageBrands();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		public function AddBrands()
		{
			$GLOBALS['BrandTitle'] = GetLang('AddBrands');
			$GLOBALS['BrandIntro'] = GetLang('AddBrandIntro');
			$GLOBALS['CancelMessage'] = GetLang('CancelCreateBrand');
			$GLOBALS['FormAction'] = "SaveNewBrands";

			$this->template->display('brand.form.tpl');
		}

		public function GetBrandsAsOptions($SelectedBrandId = 0)
		{
			// Return a list of brands as options for a select box.
			$output = "";
			$sel = "";
			$query = "SELECT * FROM [|PREFIX|]brands ORDER BY brandname asc";
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

			while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				if($row['brandid'] == $SelectedBrandId) {
					$sel = "selected=\"selected\"";
				}
				else {
					$sel = "";
				}

				$output .= sprintf("<option value='%d' %s>%s</option>", $row['brandid'], $sel, isc_html_escape($row['brandname']));
			}

			return $output;
		}

		public function GetBrandsAsArray(&$RefArray)
		{
			/*
				Return a list of brands as an array. This will be used to check
				if a brand already exists. It's more efficient to do one query
				rather than one query per brand check.

				$RefArray - An array passed in by reference only
			*/

			$query = "select brandname from [|PREFIX|]brands";
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

			while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result))
				$RefArray[] = isc_strtolower($row['brandname']);
		}

		public function SaveNewBrands()
		{
			$brands_added = 0;
			$message = "";
			$current_brands = array();
			$this->GetBrandsAsArray($current_brands);

			if(isset($_POST['brands'])) {
				$brands = $_POST['brands'];
				$brand_list = explode("\n", $brands);

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($brand_list);

				// Save the brands to the database
				foreach($brand_list as $brand) {
					$brand = trim($brand);
					if(!in_array(isc_strtolower($brand), $current_brands) && trim($brand) != "") {
						$newBrand = array(
							"brandname" => $brand,
							"brandpagetitle" => "",
							"brandmetakeywords" => "",
							"brandmetadesc" => "",
							"brandsearchkeywords" => ""
						);

						$newBrandId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("brands", $newBrand);

						if (isId($newBrandId)) {

							// Save to our brand search table
							$searchData = array(
								"brandid" => $newBrandId,
								"brandname" => $brand,
								"brandpagetitle" => "",
								"brandsearchkeywords" => ""
							);

							$GLOBALS['ISC_CLASS_DB']->InsertQuery("brand_search", $searchData);

							// Save the words to the brand_words table for search spelling suggestions
							Store_SearchSuggestion::manageSuggestedWordDatabase("brand", $newBrandId, $brand);
						}

						++$brands_added;
					}
				}

				// Check for an error message from the database
				if($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg() == "") {
					// No error
					if($brands_added == 1) {
						$message = GetLang('OneBrandAddedSuccessfully');
					}
					else {
						$message = sprintf(GetLang('MultiBrandsAddedSuccessfully'), $brands_added);
					}

					$this->ManageBrands($message, MSG_SUCCESS);
				}
				else {
					// Something went wrong
					$message = sprintf(GetLang('BrandAddError'), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
					$this->ManageBrands($message, MSG_ERROR);
				}
			}
			else {
				ob_end_clean();
				header("Location: index.php?ToDo=viewBrands");
				die();
			}
		}

		public function EditBrand($MsgDesc = "", $MsgStatus = "")
		{
			if(isset($_GET['brandId'])) {
				if ($MsgDesc != "") {
					$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
				}

				$brandId = (int)$_GET['brandId'];
				$query = sprintf("select * from [|PREFIX|]brands where brandid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($brandId));
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

				if($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
					$GLOBALS['BrandId'] = $row['brandid'];
					$GLOBALS['BrandName'] = isc_html_escape($row['brandname']);
					$GLOBALS['BrandPageTitle'] = isc_html_escape($row['brandpagetitle']);
					$GLOBALS['BrandMetaKeywords'] = isc_html_escape($row['brandmetakeywords']);
					$GLOBALS['BrandMetaDesc'] = isc_html_escape($row['brandmetadesc']);
					$GLOBALS['BrandSearchKeywords'] = isc_html_escape($row['brandsearchkeywords']);
					$GLOBALS['BrandTitle'] = GetLang('EditBrand');
					$GLOBALS['BrandIntro'] = GetLang('EditBrandIntro');
					$GLOBALS['CancelMessage'] = GetLang('CancelEditBrand');
					$GLOBALS['FormAction'] = "SaveEditedBrand";
					$GLOBALS['BrandImageMessage'] = '';
					if ($row['brandimagefile'] !== '') {
						$image = '../' . GetConfig('ImageDirectory') . '/' . $row['brandimagefile'];
						$GLOBALS['BrandImageMessage'] = sprintf(GetLang('BrandImageDesc'), $image, $row['brandimagefile']);
					}

					$this->template->display('brand.edit.form.tpl');
				}
				else {
					ob_end_clean();
					header("Location: index.php?ToDo=viewBrands");
					die();
				}
			}
			else {
				ob_end_clean();
				header("Location: index.php?ToDo=viewBrands");
				die();
			}
		}

		public function SaveEditedBrand()
		{
			if(isset($_POST['brandName'])) {
				$brandId = (int)$_POST['brandId'];
				$oldBrandName = $_POST['oldBrandName'];
				$brandName = $_POST['brandName'];
				$brandPageTitle = $_POST['brandPageTitle'];
				$brandMetaKeywords = $_POST['brandMetaKeywords'];
				$brandMetaDesc = $_POST['brandMetaDesc'];
				$brandSearchKeywords = $_POST['brandSearchKeywords'];

				// Make sure the brand doesn't already exist
				$query = sprintf("select count(brandid) as num from [|PREFIX|]brands where brandname='%s' and brandname !='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($brandName), $GLOBALS['ISC_CLASS_DB']->Quote($oldBrandName));
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
				$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

				if($row['num'] == 0) {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['brandId'], $_POST['brandName']);

					// No duplicates
					$updatedBrand = array(
						"brandname" => $brandName,
						"brandpagetitle" => $brandPageTitle,
						"brandmetakeywords" => $brandMetaKeywords,
						"brandmetadesc" => $brandMetaDesc,
						"brandsearchkeywords" => $brandSearchKeywords
					);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("brands", $updatedBrand, "brandid='".$GLOBALS['ISC_CLASS_DB']->Quote($brandId)."'");
					if($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg() == "") {

						// Update our brand search table
						$searchData = array(
							"brandid" => $brandId,
							"brandname" => $brandName,
							"brandpagetitle" => $brandPageTitle,
							"brandsearchkeywords" => $brandSearchKeywords
						);

						$query = "SELECT brandsearchid
									FROM [|PREFIX|]brand_search
									WHERE brandid=" . (int)$brandId;

						$searchId = $GLOBALS["ISC_CLASS_DB"]->FetchOne($query);

						if (isId($searchId)) {
							$GLOBALS['ISC_CLASS_DB']->UpdateQuery("brand_search", $searchData, "brandsearchid = " . (int)$searchId);
						} else {
							$GLOBALS['ISC_CLASS_DB']->InsertQuery("brand_search", $searchData);
						}

						// Save the words to the brand_words table for search spelling suggestions
						Store_SearchSuggestion::manageSuggestedWordDatabase("brand", $brandId, $brandName);

						if (array_key_exists('delbrandimagefile', $_POST) && $_POST['delbrandimagefile']) {
							$this->DelBrandImage($brandId);
							$GLOBALS['ISC_CLASS_DB']->UpdateQuery('brands', array('brandimagefile' => ''), "brandid='" . (int)$brandId . "'");
						} else if (array_key_exists('brandimagefile', $_FILES) && ($brandimagefile = $this->SaveBrandImage())) {
							$GLOBALS['ISC_CLASS_DB']->UpdateQuery('brands', array('brandimagefile' => $brandimagefile), "brandid='" . (int)$brandId . "'");
						}

						$this->ManageBrands(GetLang('BrandUpdatedSuccessfully'), MSG_SUCCESS);
					}
					else {
						$this->EditBrand(sprintf(GetLang('UpdateBrandError'), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR);
					}
				}
				else {
					// Duplicate brand name, take them back to the 'Edit' page
					$_GET['brandId'] = $brandId;
					$this->EditBrand(sprintf(GetLang('DuplicateBrandName'), $brandName), MSG_ERROR);
				}
			}
			else {
				ob_end_clean();
				header("Location: index.php?ToDo=viewBrands");
				die();
			}
		}

		private function SaveBrandImage()
		{
			if (!array_key_exists('brandimagefile', $_FILES) || $_FILES['brandimagefile']['error'] !== 0 || strtolower(substr($_FILES['brandimagefile']['type'], 0, 6)) !== 'image/') {
				return false;
			}

			// Attempt to set the memory limit
			ISC_IMAGE_LIBRARY_FACTORY::setImageFileMemLimit($_FILES['brandimagefile']['tmp_name']);

			// Generate the destination path
			$randomDir = strtolower(chr(rand(65, 90)));
			$destPath = realpath(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory'));

			if (!is_dir($destPath . '/' . $randomDir)) {
				if (!isc_mkdir($destPath . '/' . $randomDir)) {
					$randomDir = '';
				}
			}

			$destFile = GenRandFileName($_FILES['brandimagefile']['name'], 'category');
			$destPath = $destPath . '/' . $randomDir . '/' . $destFile;
			$returnPath = $randomDir . '/' . $destFile;

			$tmp = explode('.', $_FILES['brandimagefile']['name']);
			$ext = strtolower($tmp[count($tmp)-1]);

			if ($ext == 'jpg') {
				$srcImg = imagecreatefromjpeg($_FILES['brandimagefile']['tmp_name']);
			} else if($ext == 'gif') {
				$srcImg = imagecreatefromgif($_FILES['brandimagefile']['tmp_name']);
				if(!function_exists('imagegif')) {
					$gifHack = 1;
				}
			} else {
				$srcImg = imagecreatefrompng($_FILES['brandimagefile']['tmp_name']);
			}

			$srcWidth = imagesx($srcImg);
			$srcHeight = imagesy($srcImg);
			$widthLimit = GetConfig('BrandImageWidth');
			$heightLimit = GetConfig('BrandImageHeight');

			// If the image is small enough, simply move it and leave it as is
			if($srcWidth <= $widthLimit && $srcHeight <= $heightLimit) {
				imagedestroy($srcImg);
				move_uploaded_file($_FILES['brandimagefile']['tmp_name'], $destPath);
				// set the image to be writable
				isc_chmod($destPath, ISC_WRITEABLE_FILE_PERM);
				return $returnPath;
			}

			// Otherwise, the image needs to be resized
			$attribs = getimagesize($_FILES['brandimagefile']['tmp_name']);
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
			@unlink($_FILES['brandimagefile']['tmp_name']);

			// Change the permissions on the thumbnail file
			isc_chmod($destPath, ISC_WRITEABLE_FILE_PERM);

			return $returnPath;
		}

		private function DelBrandImage($file)
		{
			if (isId($file)) {
				if (!($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($GLOBALS["ISC_CLASS_DB"]->Query("SELECT * FROM [|PREFIX|]brands WHERE brandid='" . (int)$file . "'")))) {
					return false;
				}

				if ($row['brandimagefile'] == '') {
					return true;
				} else {
					$file = $row['brandimagefile'];
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
	}