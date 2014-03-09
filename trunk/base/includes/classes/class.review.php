<?php
class ISC_REVIEW
{

	public function HandlePage()
	{
		$action = @$_POST['action'];

		switch($action) {
			case "post_review": {
				$this->PostReview();
				break;
			}

			default: {
				// Abandon ship!
				ob_end_clean();
				header("Location:" . $GLOBALS['ShopPath']);
				die();
			}
		}
	}

	public function PostReview()
	{
		$product_id = (int)$_POST['product_id'];

		$query = "SELECT * FROM [|PREFIX|]products WHERE productid='".(int)$product_id."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$product = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$prodLink = ProdLink($product['prodname']);

		if(GetConfig('EnableProductTabs') == 0) {
			$prodReviewsLink = $prodLink.'#reviews';
		}
		else {
			$prodReviewsLink = Interspire_Url::modifyParams($prodLink, array('tab' => 'ProductReviews'));
		}

		if(!$product['prodname']) {
			// Abandon ship!
			ob_end_clean();
			header("Location:" . $GLOBALS['ShopPath']);
			die();
		}

		// Check that the customer has permisison to view this product
		$canView = false;
		$productCategories = explode(',', $product['prodcatids']);
		foreach($productCategories as $categoryId) {
			// Do we have permission to access this category?
			if(CustomerGroupHasAccessToCategory($categoryId)) {
				$canView = true;
			}
		}
		if($canView == false) {
			$noPermissionsPage = GetClass('ISC_403');
			$noPermissionsPage->HandlePage();
			exit;
		}

		// Are reviews disabled? Just send the customer back to the product page
		if(!getProductReviewsEnabled()) {
			header("Location: ".$prodReviewsLink);
			exit;
		}

		// Setup an array containing all of the fields from the POST that we care about for reviews.
		// We'll use this below, and in the case that we need to redirect back to the product page.
		$reviewPostData = array();
		$reviewFields = array(
			'revrating',
			'revtitle',
			'revtext',
			'revfromname',
			'product_id'
		);
		foreach($reviewFields as $field) {
			if(!isset($_POST[$field])) {
				$reviewPostData[$field] = '';
				continue;
			}
			$reviewPostData[$field] = $_POST[$field];
		}

		// Check all required fields have been supplied
		$requiredFields = array(
			'revrating',
			'revtitle',
			'revtext'
		);
		foreach($requiredFields as $field) {
			if(!isset($_POST[$field]) || !trim($_POST[$field])) {
				$_SESSION['productReviewData'] = $reviewPostData;
				FlashMessage(GetLang('InvalidReviewFormInput'), MSG_ERROR, $prodReviewsLink, 'reviews');
				exit;
			}
		}

		$captcha = '';
		if(isset($_POST['captcha'])) {
			$captcha = $_POST['captcha'];
		}
		$captcha_check = true;

		// Should reviews be approved automatically?
		if(GetConfig('AutoApproveReviews')) {
			$status = 1;
		}
		else {
			$status = 0;
		}

		// Do we need to check captcha?
		if(GetConfig('CaptchaEnabled') && isc_strtolower($captcha) != isc_strtolower($GLOBALS['ISC_CLASS_CAPTCHA']->LoadSecret())) {
			$_SESSION['productReviewData'] = $reviewPostData;
			FlashMessage(GetLang('ReviewBadCaptcha'), MSG_ERROR, $prodReviewsLink, 'reviews');
			exit;
		}

		// Save the review in the database
		$newReview = array(
			"revproductid" => (int)$reviewPostData['product_id'],
			"revfromname" => $reviewPostData['revfromname'],
			"revdate" => time(),
			"revrating" => max(1, min(5, $reviewPostData['revrating'])),
			"revtext" => $reviewPostData['revtext'],
			"revtitle" => $reviewPostData['revtitle'],
			"revstatus" => $status
		);

		if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery("reviews", $newReview)) {
			$_SESSION['productReviewData'] = $reviewPostData;
			FlashMessage(GetLang('ReviewBadCaptcha'), MSG_ERROR, $prodReviewsLink, 'reviews');
			exit;
			}

		// Determine what the success message should be - is the review live
		// or is it pending approval from the site owner?

		// If this is an automagically approved review, we need to show that & update the average rating
		if($status == 1) {
			$query = "
				UPDATE [|PREFIX|]products
				SET prodnumratings=prodnumratings+1, prodratingtotal=prodratingtotal+'".(int)$reviewPostData['revrating']."'
				WHERE productid='".$product['productid']."'
			";
			$GLOBALS['ISC_CLASS_DB']->Query($query);
			$flashMessage = GetLang('ReviewSavedApproved');
		}
		else {
			$flashMessage = GetLang('ReviewSavedPending');
		}

		FlashMessage($flashMessage, MSG_SUCCESS, $prodReviewsLink, 'reviews');
		exit;
	}
}