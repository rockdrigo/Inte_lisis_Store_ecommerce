<?php
class COMMENTS_BUILTINCOMMENTS extends ISC_COMMENTS {
	protected $availableCommentTypes = array(self::PRODUCT_COMMENTS);

	public function __construct()
	{
		parent::__construct();

		$this->SetName(GetLang('BuiltInName'));
		$this->SetDescription(GetLang('BuiltInDescription'));
		$this->SetHelpText(GetLang('BuiltInHelp'));
	}

	public function getCommentsHTMLForType($commentType, $objectReference)
	{
		switch ($commentType) {
			case self::PRODUCT_COMMENTS:
				return $this->loadProductComments($objectReference);
		}
	}

	private function loadProductComments($productId)
	{
		$GLOBALS['ProductId'] = $productId;

		// Are there any reviews for this product? If so, load them
		if ($GLOBALS['ISC_CLASS_PRODUCT']->GetNumReviews() == 0) {
			$GLOBALS['NoReviews'] = GetLang('NoReviews');
		}
		else {
			// Setup paging data
			$reviewsTotal = $GLOBALS['ISC_CLASS_PRODUCT']->GetNumReviews();
			$reviewsPerPage = GetConfig('ProductReviewsPerPage');
			$pages = ceil($reviewsTotal / $reviewsPerPage);

			$revpage = 1;
			$start = 0;

			if (isset($_GET['revpage'])) {
				$revpage = (int)$_GET['revpage'];
			}

			if ($revpage < 1) {
				$revpage = 1;
			}
			elseif ($revpage > $pages) {
				$revpage = $pages;
			}

			$start = ($revpage - 1) * $reviewsPerPage;

			$GLOBALS['ProductNumReviews'] = $reviewsTotal;
			$GLOBALS['ReviewStart'] = $start + 1;
			$GLOBALS['ReviewEnd'] = $start + $reviewsPerPage;

			// do we need to show paging?
			if ($pages > 1) {
				// Form the previous and next links
				$reviewLink = ProdLink($GLOBALS['ISC_CLASS_PRODUCT']->GetProductName());
				if($GLOBALS['EnableSEOUrls'] == 1) {
					$reviewLink .= '?revpage=';
				}
				else {
					$reviewLink .= '&revpage=';
				}

				if ($GLOBALS['ReviewEnd'] > $reviewsTotal) {
					$GLOBALS['ReviewEnd'] = $reviewsTotal;
				}

				// show a previous link
				if ($revpage > 1) {
					$GLOBALS["ReviewLink"] = $reviewLink . ($revpage - 1);
					$GLOBALS["PrevRevLink"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductReviewPreviousLink");
				}

				// show a next link
				if ($revpage < $pages) {
					$GLOBALS["ReviewLink"] = $reviewLink . ($revpage + 1);
					$GLOBALS["NextRevLink"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductReviewNextLink");
				}

				$GLOBALS['ProductReviewPaging'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductReviewPaging");
			}

			// Load all reviews for this product
			$query = "
				SELECT *
				FROM [|PREFIX|]reviews
				WHERE revproductid='".(int)$GLOBALS['ISC_CLASS_PRODUCT']->GetProductId()."' AND revstatus='1'
				ORDER BY revdate DESC
			";
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, $reviewsPerPage);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$GLOBALS['ProductReviews'] = "";

			$GLOBALS['AlternateReviewClass'] = '';
			$GLOBALS['ReviewNumber'] = $GLOBALS['ReviewStart'];
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$GLOBALS['ReviewRating'] = (int) $row['revrating'];
				$GLOBALS['ReviewTitle'] = isc_html_escape($row['revtitle']);
				$GLOBALS['ReviewDate'] = isc_date(GetConfig('DisplayDateFormat'), $row['revdate']);

				if ($row['revfromname'] != "") {
					$GLOBALS['ReviewName'] = isc_html_escape($row['revfromname']);
				} else {
					$GLOBALS['ReviewName'] = GetLang('Unknown');
				}

				$GLOBALS['ReviewText'] = nl2br(isc_html_escape($row['revtext']));

				$GLOBALS['ProductReviews'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductReviewItem");
				++$GLOBALS['ReviewNumber'];
				if($GLOBALS['AlternateReviewClass']) {
					$GLOBALS['AlternateReviewClass'] = '';
				}
				else {
					$GLOBALS['AlternateReviewClass'] = 'Alt';
				}
			}

			$GLOBALS['ProductReviewList'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductReviewList");
		}

		// Is captcha enabled?
		if (GetConfig('CaptchaEnabled') == false) {
			$GLOBALS['HideReviewCaptcha'] = "none";
		}
		else {
			// Generate the captcha image
			$GLOBALS['ISC_CLASS_CAPTCHA'] = GetClass('ISC_CAPTCHA');
			$GLOBALS['ISC_CLASS_CAPTCHA']->CreateSecret();
			$GLOBALS['CaptchaImage'] = $GLOBALS['ISC_CLASS_CAPTCHA']->ShowCaptcha();
		}

		$GLOBALS['ProductReviewFlashMessages'] = GetFlashMessageBoxes('reviews');

		// If we've got review data in the session then we need to show the review form
		if(!empty($_SESSION['productReviewData']['product_id'])) {
			// But only if it's for the current product
			 if($_SESSION['productReviewData']['product_id'] == $productId) {
				$GLOBALS['AutoShowReviewForm'] = 1;

				$reviewFields = array(
					'RevTitle' => 'revtitle',
					'RevText' => 'revtext',
					'RevFromName' => 'revfromname',
				);

				foreach($reviewFields as $templateVar => $field) {
					if(!empty($_SESSION['productReviewData'])) {
						$GLOBALS[$templateVar] = isc_html_escape($_SESSION['productReviewData'][$field]);
					}
				}

				if(isset($_SESSION['productReviewData']['revrating'])) {
					$GLOBALS['ReviewRating'.(int)$_SESSION['productReviewData']['revrating']] = 'selected="selected"';
				}
			}

			// Make sure we remove any review data
			unset($_SESSION['productReviewData']);
		}

		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("product_comments");
		return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
	}
}