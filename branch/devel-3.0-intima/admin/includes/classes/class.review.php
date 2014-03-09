<?php

	define("ISC_REVIEWS_PER_PAGE", 20);

	class ISC_ADMIN_REVIEW extends ISC_ADMIN_BASE
	{
		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('reviews');
			switch(isc_strtolower($Do))
			{
				case "editreview2": {
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Edit_Reviews)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Reviews') => "index.php?ToDo=viewReviews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditReviewStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "editreview": {
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Edit_Reviews)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Reviews') => "index.php?ToDo=viewReviews", GetLang('EditReview') => "index.php?ToDo=editReview");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditReviewStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "previewreview": {
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Reviews)) {
						$this->PreviewReview();
						die();
					} else {
						echo '<script type="text/javascript">window.close();</script>';
					}

					break;
				}
				case "disapprovereviews": {
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Edit_Reviews)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Reviews') => "index.php?ToDo=viewReviews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DisapproveReviews();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "approvereviews": {
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Edit_Reviews)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Reviews') => "index.php?ToDo=viewReviews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->ApproveReviews();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "deletereviews": {
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Delete_Reviews)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Reviews') => "index.php?ToDo=viewReviews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteReviews();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				default: {
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Reviews)) {
						if(isset($_GET['searchQuery'])) {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Reviews') => "index.php?ToDo=viewReviews", GetLang('SearchResults') => "index.php?ToDo=viewReviews");
						}
						else {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Reviews') => "index.php?ToDo=viewReviews");
						}

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}

						$this->ManageReviews();

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
			}
		}

		private function EditReviewStep2()
		{
			// Save the updated review
			$reviewId = (int)$_POST['reviewId'];
			$arrData = array();
			$existingData = array();
			// Fetch the existing review
			$this->_GetReviewData($reviewId, $existingData);
			$this->_GetReviewData(0, $arrData);
			// If the rating has changed and this review is approved we need to remove the old rating from the total
			if($existingData['revrating'] != $arrData['revrating'] && $existingData['revstatus'] == 1) {
				$query = sprintf("update [|PREFIX|]products set prodratingtotal=prodratingtotal-%d+%d where productid=%d", $existingData['revrating'], $arrData['revrating'], $existingData['revproductid']);
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}
			// Has the status changed?
			if($arrData['revstatus'] != $existingData['revstatus']) {
				// This view is now approved
				if($arrData['revstatus'] == 1) {
					$query = sprintf("update [|PREFIX|]products set prodnumratings=prodnumratings+1, prodratingtotal=prodratingtotal+%d where productid=%d", $arrData['revrating'], $existingData['revproductid']);
					$GLOBALS['ISC_CLASS_DB']->Query($query);
				}
				// Review is now unapproved
				else {
					// has the rating
					$totalUpdate = '';
					if ($existingData['revstatus'] == 1) {
						$totalUpdate = sprintf(", prodratingtotal=prodratingtotal-%d", $arrData['revrating']);
						$query = sprintf("update [|PREFIX|]products set prodnumratings=prodnumratings-1 %s where productid=%s", $totalUpdate, $existingData['revproductid']);
						$GLOBALS['ISC_CLASS_DB']->Query($query);
					}
				}
			}
			$updatedReview = array(
				"revfromname" => $arrData['revfromname'],
				"revrating" => $arrData['revrating'],
				"revtext" => $arrData['revtext'],
				"revtitle" => $arrData['revtitle'],
				"revstatus" => $arrData['revstatus']
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("reviews", $updatedReview, "reviewid='".$GLOBALS['ISC_CLASS_DB']->Quote($reviewId)."'");
			$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			if ($err != "") {
				$this->ManageReviews($err, MSG_ERROR);
			} else {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($reviewId, $arrData['revtitle']);

				$this->ManageReviews(GetLang('ReviewUpdatedSuccessfully'), MSG_SUCCESS);
			}
		}

		private function _GetReviewData($ReviewId, &$RefArray)
		{
			// Gets the details of a review Returns the data to the array
			// referenced by the $RefArray variable.

			if ($ReviewId == 0) {
				// Get the data from the form
				$RefArray['reviewid'] = $_POST['reviewId'];
				$RefArray['revfromname'] = $_POST['revfromname'];
				$RefArray['revrating'] = $_POST['revrating'];
				$RefArray['revtext'] = $_POST['revtext'];
				$RefArray['revtitle'] = $_POST['revtitle'];
				$RefArray['revstatus'] = $_POST['revstatus'];
			} else {
				// Get the data from the database
				$query = "
					SELECT r.*, p.prodvendorid
					FROM [|PREFIX|]reviews r
					LEFT JOIN [|PREFIX|]products p ON (p.productid=r.revproductid)
					WHERE reviewid='".(int)$ReviewId."'
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$RefArray = $row;
				}
			}
		}

		private function _GetStatusOptions($Status = -1)
		{
			// Output option fields containing status values
			if ($Status == 0) {
				$sel = "selected=\"selected\"";
			} else {
				$sel = "";
			}

			$output = sprintf("<option value=0 %s>%s</option>", $sel, GetLang('Pending'));

			if ($Status == 1) {
				$sel = "selected=\"selected\"";
			} else {
				$sel = "";
			}

			$output .= sprintf("<option value=1 %s>%s</option>", $sel, GetLang('Approved'));

			if ($Status == 2) {
				$sel = 'selected="selected"';
			} else {
				$sel = "";
			}

			$output .= sprintf("<option value=2 %s>%s</option>", $sel, GetLang('Disapproved'));

			return $output;
		}

		private function _GetRatingOptions($Rating)
		{
			// Output option fields containing rating values
			if ($Rating == 1) {
				$sel = "selected=\"selected\"";
			} else {
				$sel = "";
			}

			$output = sprintf("<option value=1 %s>%s</option>", $sel, GetLang('1Star'));

			if ($Rating == 2) {
				$sel = "selected=\"selected\"";
			} else {
				$sel = "";
			}

			$output .= sprintf("<option value=2 %s>%s</option>", $sel, GetLang('2Stars'));

			if ($Rating == 3) {
				$sel = "selected=\"selected\"";
			} else {
				$sel = "";
			}

			$output .= sprintf("<option value=3 %s>%s</option>", $sel, GetLang('3Stars'));

			if ($Rating == 4) {
				$sel = "selected=\"selected\"";
			} else {
				$sel = "";
			}

			$output .= sprintf("<option value=4 %s>%s</option>", $sel, GetLang('4Stars'));

			if ($Rating == 5) {
				$sel = "selected=\"selected\"";
			} else {
				$sel = "";
			}

			$output .= sprintf("<option value=5 %s>%s</option>", $sel, GetLang('5Stars'));
			return $output;
		}

		private function EditReviewStep1()
		{
			// Show the form to edit a product
			$reviewId = (int)$_GET['reviewId'];
			$arrData = array();

			// Make sure the product exists
			if (ReviewExists($reviewId)) {
				$this->_GetReviewData($reviewId, $arrData);

				// Does this user have permission to edit this review?
				if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $arrData['prodvendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewReviews');
				}

				$GLOBALS['ReviewId'] = $reviewId;
				$GLOBALS['FromName'] = isc_html_escape($arrData['revfromname']);
				$GLOBALS['Title'] = isc_html_escape($arrData['revtitle']);
				$GLOBALS['Review'] = isc_html_escape($arrData['revtext']);
				$GLOBALS['StatusOptions'] = $this->_GetStatusOptions($arrData['revstatus']);
				$GLOBALS['RatingOptions'] = $this->_GetRatingOptions($arrData['revrating']);

				$this->template->display('review.form.tpl');
			} else {
				// The review doesn't exist
				$this->ManageReviews(GetLang('ReviewDoesntExist'), MSG_ERROR);
			}
		}

		private function PreviewReview()
		{
			$GLOBALS['Rating'] = "";

			// Preview a review
			if (isset($_GET['reviewId'])) {
				$reviewId = $_GET['reviewId'];
				$query = sprintf("select * from [|PREFIX|]reviews r inner join [|PREFIX|]products p on r.revproductid=p.productid where r.reviewid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($reviewId));
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$GLOBALS['Product'] = isc_html_escape($row['prodname']);
					$GLOBALS['Title'] = isc_html_escape($row['revtitle']);
					$GLOBALS['Review'] = str_replace("\n", "<br />", isc_html_escape($row['revtext']));

					$ratingText = sprintf(GetLang('ReviewRated'), $row['revrating']);

					if ($row['revfromname'] == "") {
						$GLOBALS['Author'] = GetLang('NA');
					} else {
						$GLOBALS['Author'] = isc_html_escape($row['revfromname']);
					}

					for ($r = 0; $r < $row['revrating']; $r++) {
						$GLOBALS['Rating'] .= sprintf("<img title='%s' width='13' height='12' src='images/rating_on.gif'>", $ratingText);
					}

					for ($r = $row['revrating']; $r < 5; $r++) {
						$GLOBALS['Rating'] .= sprintf("<img title='%s' width='13' height='12' src='images/rating_off.gif'>", $ratingText);
					}

					$this->template->display('review.preview.tpl');
				} else {
					echo '<script type="text/javascript">window.close();</script>';
				}
			} else {
				echo '<script type="text/javascript">window.close();</script>';
			}
		}

		private function ApproveReviews()
		{
			if (isset($_POST['reviews'])) {
				$err = '';
				$msg = $this->DoApproveReviews($_POST['reviews'], $err);
				if ($err != "") {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['reviews']));
					$this->ManageReviews($err, MSG_ERROR);
				} else {
					$this->ManageReviews($msg, MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Reviews)) {
					$this->ManageReviews();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		private function DisapproveReviews()
		{
			if (isset($_POST['reviews'])) {
				$err = '';
				$msg = $this->DoDisapproveReviews($_POST['reviews'], $err);
				if ($err != "") {
					$this->ManageReviews($err, MSG_ERROR);
				} else {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['reviews']));

					$this->ManageReviews($msg, MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Reviews)) {
					$this->ManageReviews();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		private function DeleteReviews()
		{
			if (isset($_POST['reviews'])) {
				$err = '';
				$msg = $this->DoDeleteReviews($_POST['reviews'], $err);
				if ($err != "") {
					$this->ManageReviews($err, MSG_ERROR);
				} else {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['reviews']));

					$this->ManageReviews($msg, MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Reviews)) {
					$this->ManageReviews();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		public function DoApproveReviews($reviews, &$err)
		{
			$this->DoReviews($reviews, 'approve');

			$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			if($err) {
				return false;
			}

			return GetLang('ReviewsApprovedSuccessfully');
		}

		public function DoDisapproveReviews($reviews, &$err)
		{
			$this->DoReviews($reviews, 'disapprove');

			$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			if ($err != "") {
				return false;
			}

			return GetLang('ReviewsDisapprovedSuccessfully');
		}

		public function DoDeleteReviews($reviews, &$err)
		{
			$this->DoReviews($reviews, 'delete');

			$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			if ($err != "") {
				return false;
			}
			return GetLang('ReviewsDeletedSuccessfully');
		}

		private function DoReviews($reviews, $method)
		{

			if(!is_array($reviews)) {
				$reviews = array($reviews);
			}

			$reviewids = implode(",", array_map('intval', $reviews));

			// We need to fetch the product for each review to update it accordingly
			$vendorId = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
			$queryWhere = '';
			if($vendorId > 0) {
				$queryWhere .= " AND prodvendorid='".(int)$vendorId."'";
			}
			$query = "	SELECT reviewid, revproductid
						FROM [|PREFIX|]reviews r
						INNER JOIN [|PREFIX|]products p ON (p.productid=r.revproductid)
						WHERE reviewid IN (".$reviewids.")".$queryWhere;

			$updatedReviews = array();
			$updatedProducts = array();

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($review = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$updatedReviews[] = (int)$review['reviewid'];
				$updatedProducts[] = (int)$review['revproductid'];
			}

			$updatedProducts = array_unique($updatedProducts);

			// Now we update the reviews to approve them
			$reviewids = implode("','", $updatedReviews);
			if($reviewids) {

				$reviewUpdate = array();

				if ($method == 'approve') {
					$reviewUpdate = array("revstatus" => 1);
				}
				else if ($method == 'disapprove') {
					$reviewUpdate = array("revstatus" => 0);
				}

				if ($method == 'delete') {
					$GLOBALS['ISC_CLASS_DB']->DeleteQuery('reviews', "WHERE reviewid IN ('".$reviewids."')");
				}
				else {
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("reviews", $reviewUpdate, "reviewid IN ('".$reviewids."')");
				}

				// Now we need to update the products with the new review total
				foreach($updatedProducts as $productid) {

					$query = "	SELECT revrating
								FROM [|PREFIX|]reviews r
								WHERE revproductid = $productid AND revstatus = 1 ";

					$revtotal = 0;
					$revcount = 0;

					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

					while($review = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$revtotal += (int)$review['revrating'];
						$revcount++;
					}

					$query = "	UPDATE [|PREFIX|]products
								SET prodratingtotal=$revtotal, prodnumratings=$revcount
								WHERE productid=$productid";

					$GLOBALS['ISC_CLASS_DB']->Query($query);
				}
			}
		}

		public function ManageReviewsGrid(&$numReviews)
		{
			// Show a list of reviews in a table
			$page = 0;
			$start = 0;
			$numReviews = 0;
			$numPages = 0;
			$GLOBALS['ReviewGrid'] = "";
			$GLOBALS['Nav'] = "";
			$max = 0;
			$searchURL = '';

			if (isset($_GET['searchQuery'])) {
				$query = $_GET['searchQuery'];
				$GLOBALS['Query'] = $query;
				$searchURL = sprintf("&amp;sarchQuery=%s", urlencode($query));
			} else {
				$query = "";
				$GLOBALS['Query'] = "";
			}

			if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'desc') {
				$sortOrder = 'asc';
			} else {
				$sortOrder = "desc";
			}

			$sortLinks = array(
				"Review" => "r.revtitle",
				"Name" => "p.prodname",
				"By" => "r.revfromname",
				"Rating" => "r.revrating",
				"Date" => "r.revdate",
				"Status" => "r.revstatus"
			);

			if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("ManageReviews", $_REQUEST['sortField'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("ManageReviews", "r.reviewid", $sortOrder);
			}

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			} else {
				$page = 1;
			}
			$GLOBALS['Page'] = $page;

			$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
			$GLOBALS['SortURL'] = $sortURL;

			// Limit the number of questions returned
			if ($page == 1) {
				$start = 1;
			} else {
				$start = ($page * ISC_REVIEWS_PER_PAGE) - (ISC_REVIEWS_PER_PAGE-1);
			}

			$start = $start-1;

			// Get the results for the query
			$reviewResult = $this->_GetReviewList($query, $start, $sortField, $sortOrder, $numReviews);
			$numPages = ceil($numReviews / ISC_REVIEWS_PER_PAGE);

			// Add the "(Page x of n)" label
			if($numReviews > ISC_REVIEWS_PER_PAGE) {
				$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
				$GLOBALS['Nav'] .= BuildPagination($numReviews, ISC_REVIEWS_PER_PAGE, $page, sprintf("index.php?ToDo=viewReviews%s", $sortURL));
			}
			else {
				$GLOBALS['Nav'] = "";
			}

			$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');
			$GLOBALS['SearchQuery'] = $query;
			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewReviews&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);

			// Workout the maximum size of the array
			$max = $start + ISC_REVIEWS_PER_PAGE;

			if ($max > $numReviews) {
				$max = $numReviews;
			}

			if($numReviews > 0) {
				// Display the reviews
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($reviewResult)) {
					$GLOBALS['ReviewId'] = $row['reviewid'];
					$GLOBALS['ProdName'] = isc_html_escape($row['prodname']);
					$GLOBALS['ProdLink'] = ProdLink($row['prodname']);

					if (isc_strlen($row['revtext']) > 100) {
						$GLOBALS['ReviewTitle'] = isc_html_escape(sprintf("%s...", isc_substr($row['revtitle'], 0, 100)));
					} else {
						$GLOBALS['ReviewTitle'] = isc_html_escape($row['revtitle']);
					}

					$GLOBALS['Rating'] = "";
					$ratingText = sprintf(GetLang('ReviewRated'), $row['revrating']);

					for ($r = 0; $r < $row['revrating']; $r++) {
						$GLOBALS['Rating'] .= sprintf("<img title='%s' width='13' height='12' src='images/rating_on.gif'>", $ratingText);
					}

					for ($r = $row['revrating']; $r < 5; $r++) {
						$GLOBALS['Rating'] .= sprintf("<img title='%s' width='13' height='12' src='images/rating_off.gif'>", $ratingText);
					}

					if ($row['revfromname'] != "") {
						$GLOBALS['PostedBy'] = isc_html_escape($row['revfromname']);
					} else {
						$GLOBALS['PostedBy'] = GetLang('NA');
					}

					$GLOBALS['Date'] = CDate($row['revdate']);
					$GLOBALS['PreviewLink'] = sprintf("<a title='%s' href='javascript:PreviewReview(%d)'>%s</a>", GetLang('PreviewReview'), $row['reviewid'], GetLang('Preview'));

					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Edit_Reviews)) {
						$GLOBALS['EditLink'] = sprintf("<a title='%s' href='index.php?ToDo=editReview&amp;reviewId=%d'>%s</a>", GetLang('EditReview'), $row['reviewid'], GetLang('Edit'));
					} else {
						$GLOBALS['EditLink'] = sprintf("<a class='Action' disabled>%s</a>", GetLang('Edit'));
					}

					switch($row['revstatus'])
					{
						case "0":
						{
							$GLOBALS['Status'] = GetLang('Pending');
							break;
						}
						case "1":
						{
							$GLOBALS['Status'] = sprintf("<font color='green'>%s</font>", GetLang('Approved'));
							break;
						}
						case "2":
						{
							$GLOBALS['Status'] = sprintf("<font color='red'>%s</font>", GetLang('Disapproved'));
							break;
						}
					}

					$GLOBALS['ReviewGrid'] .= $this->template->render('reviews.manage.row.tpl');
				}

				return $this->template->render('reviews.manage.grid.tpl');
			}
		}

		private function ManageReviews($MsgDesc = "", $MsgStatus = "")
		{
			$GLOBALS['Message'] = '';

			// check which comment system we're using
			if (GetConfig('CommentSystemModule') != 'comments_builtincomments') {
				if (GetModuleById('comments', $commentModule, GetConfig('CommentSystemModule'))) {
					$GLOBALS['Message'] .= MessageBox(GetLang('NotUsingBuiltInWarning', array('moduleName' => $commentModule->GetName())), MSG_INFO);
				}
			}

			// Fetch any results, place them in the data grid
			$numReviews = 0;
			$GLOBALS['ReviewDataGrid'] = $this->ManageReviewsGrid($numReviews);

			// Was this an ajax based sort? Return the table now
			if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['ReviewDataGrid'];
				return;
			}

			if ($MsgDesc != "") {
				$GLOBALS['Message'] .= MessageBox($MsgDesc, $MsgStatus);
			}

			// Do we need to disable the delete button?
			if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Delete_Reviews) || $numReviews == 0) {
				$GLOBALS['DisableDelete'] = "DISABLED";
			}

			if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Edit_Reviews) || $numReviews == 0) {
				$GLOBALS['DisableApproved'] = "DISABLED";
				$GLOBALS['DisableDisapproved'] = "DISABLED";
			}

			$GLOBALS['ReviewIntro'] = GetLang('ManageReviewsIntro');

			if($numReviews == 0) {
				$GLOBALS['DisplayGrid'] = "none";

				if(count($_GET) > 1) {
					if ($MsgDesc == "") {
						$GLOBALS['Message'] .= MessageBox(GetLang('NoReviews'), MSG_ERROR);
					}
				}
				else {
					$GLOBALS['Message'] .= MessageBox(GetLang('NoReviews1'), MSG_SUCCESS);
					$GLOBALS['DisplaySearch'] = "none";
				}
			}

			$this->template->display('reviews.manage.tpl');
		}

		private function _GetReviewList(&$Query, $Start, $SortField, $SortOrder, &$NumReviews)
		{
			// Return an array containing details about reviews.
			// Takes into account search values too.

			// PostgreSQL is case sensitive for likes, so all matches are done in lower case
			$Query = trim(isc_strtolower($Query));

			$query = "
				SELECT r.*, p.prodname
				FROM [|PREFIX|]reviews r
				INNER JOIN [|PREFIX|]products p ON (p.productid=r.revproductid)
			";
			$countQuery = "
				SELECT COUNT(reviewid)
				FROM [|PREFIX|]reviews r
				INNER JOIN [|PREFIX|]products p ON (p.productid=r.revproductid)
			";

			$queryWhere = ' WHERE 1=1 ';
			if($Query != '') {
				$queryWhere .= " AND ".$GLOBALS['ISC_CLASS_DB']->FullText("revtext, revtitle, revfromname", $Query, true);
			}

			// Only fetch product reviews which belong to the current vendor
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				$queryWhere .= " AND prodvendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
			}

			$query .= $queryWhere;
			$countQuery .= $queryWhere;

			$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
			$NumReviews = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			$query .= " ORDER BY ".$SortField." ".$SortOrder;

			// Add the limit
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($Start, ISC_REVIEWS_PER_PAGE);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			return $result;
		}
	}