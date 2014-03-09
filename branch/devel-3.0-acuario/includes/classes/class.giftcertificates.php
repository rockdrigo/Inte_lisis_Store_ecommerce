<?php

class ISC_GIFTCERTIFICATES
{

	public function __construct()
	{
		$GLOBALS['GiftCertificateMinimum'] = GetConfig('GiftCertificateMinimum');
		$GLOBALS['GiftCertificateMaximum'] = GetConfig('GiftCertificateMaximum');

	}

	public function HandlePage()
	{
		$action = "";
		if(isset($_REQUEST['action'])) {
			$action = isc_strtolower($_REQUEST['action']);
		}

		// Don't allow any access to this file if gift certificates aren't enabled
		if(GetConfig('EnableGiftCertificates') == 0) {
			ob_end_clean();
			header("Location: " . $GLOBALS['ShopPath']);
			die();
		}

		if(!gzte11(ISC_LARGEPRINT)) {
			ob_end_clean();
			header("Location: " . $GLOBALS['ShopPath']);
			die();
		}

		switch($action) {
			case "do_purchase": {
				if($_SERVER['REQUEST_METHOD'] == "POST") {
					$this->DoPurchaseGiftCertificate();
					break;
				}
				else {
					$this->PurchaseGiftCertificate();
				}
			}
			case "balance": {
				$this->CheckGiftCertificateBalance();
				break;
			}
			case "preview": {
				$this->PreviewGiftCertificate();
				break;
			}
			case "redeem": {
				$this->RedeemGiftCertificate();
				break;
			}
			default: {
				$this->PurchaseGiftCertificate();
			}
		}
	}

	// Check the remaining balance of a gift certificate
	private function CheckGiftCertificateBalance()
	{
		if(isset($_REQUEST['giftcertificatecode']) && $_REQUEST['giftcertificatecode']) {
			$query = sprintf("SELECT * FROM [|PREFIX|]gift_certificates WHERE giftcertcode='%s' AND (giftcertstatus=2 OR giftcertstatus=4)", $GLOBALS['ISC_CLASS_DB']->Quote(trim($_REQUEST['giftcertificatecode'])));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$certificate = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if(!$certificate['giftcertid']) {
				$GLOBALS['ErrorMessage'] = GetLang('InvalidGiftCertificateCode');
				$GLOBALS['HidePanels'][] = "GiftCertificateBalanceRemaining";
				$GLOBALS['HideIntro'] = "none";
				$GLOBALS['HideExampleImage'] = "none";
			}
			else if($certificate['giftcertexpirydate'] != 0 && $certificate['giftcertexpirydate'] <= time()) {
				if(!$certificate['giftcertexpirydate']) {
					$certificate['giftcertexpirydate'] = $certificate['giftcertpurchasedate'];
				}
				$GLOBALS['ErrorMessage'] = sprintf(GetLang('GiftCertificateExpired'), CDate($certificate['giftcertexpirydate']));
				$GLOBALS['HidePanels'][] = "GiftCertificateBalanceRemaining";
				$GLOBALS['HideExampleImage'] = "none";
				$GLOBALS['HideIntro'] = "none";
			}
			// Success!
			else {
				$GLOBALS['RemainingBalance'] = sprintf(GetLang('RemainingGiftCertificateBalance'), CurrencyConvertFormatPrice($certificate['giftcertbalance']));
				$GLOBALS['HideGiftCertificateError'] = "none";
				$GLOBALS['GiftCertificateCode'] = $certificate['giftcertcode'];
				$GLOBALS['HideIntro'] = "none";
				$GLOBALS['HideExampleImage'] = "none";
				$GLOBALS['HideTitle'] = "none";
			}
		}
		else {
			$GLOBALS['HideGiftCertificateError'] = "none";
		}

		$GLOBALS['BalanceTitle'] = GetLang('CheckBalanceOfGiftCertificate');

		if(!isset($GLOBALS['RemainingBalance'])) {
			$GLOBALS['HidePanels'][] = "GiftCertificateBalanceRemaining";
		}

		// Show the gift certificates balance page
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('GiftCertificates')));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("giftcertificates_balance");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();

	}

	// Validate and add the incoming gift certificate to the cart
	private function DoPurchaseGiftCertificate()
	{
		$errors = array();
		// Begin validation!
		if(GetConfig('GiftCertificateCustomAmounts') == 1) {

			// Is there a minimum and maximum limit?
			$minAmount = ConvertPriceToCurrency(GetConfig('GiftCertificateMinimum'));
			$maxAmount = ConvertPriceToCurrency(GetConfig('GiftCertificateMaximum'));

			if(!GetConfig('GiftCertificateCustomAmounts')) {
				$errors[] = GetLang('SelectValidGiftCertificateAmount');
			}
			else if(!isset($_POST['certificate_amount']) || !is_numeric($_POST['certificate_amount'])) {
				$errors[] = GetLang('EnterValidGiftCertificateAmount');
			}
			else if(($minAmount > 0 && $_POST['certificate_amount'] < $minAmount) || ($maxAmount > 0 && $_POST['certificate_amount'] > $maxAmount)) {
				$errors[] = GetLang('EnterGiftCertificateValueBetween');
			}
			else {
				$amount = ConvertPriceToDefaultCurrency($_POST['certificate_amount']);
			}
		}
		// Selected a gift certificate amount from the list
		else {
			if(!in_array($_POST['selected_amount'], GetConfig('GiftCertificateAmounts'))) {
				$errors[] = GetLang('SelectValidGiftCertificateAmount');
			}
			else {
				$amount = $_POST['selected_amount'];
			}
		}

		// Did they not enter who they wanted the certificate to be sent to?
		if(!isset($_POST['to_name']) || trim($_POST['to_name']) == "") {
			$errors[] = Getlang('EnterValidCertificateToName');
		}

		if(!isset($_POST['to_email']) || !is_email_address($_POST['to_email'])) {
			$errors[] = GetLang('EnterValidCertificateToEmail');
		}

		// Missing from details?
		if(!isset($_POST['from_name']) ||trim($_POST['from_name']) == "") {
			$errors[] = GetLang('EnterValidCertificateFromName');
		}

		if(!isset($_POST['from_email']) || !is_email_address($_POST['from_email'])) {
			$errors[] = GetLang('EnterValidCertificateFromEmail');
		}

		$message = '';
		if(isset($_POST['message']) && isc_strlen($_POST['message']) > 200) {
			$errors[] = GetLang('GiftCertificateMessageTooLong');
		}
		else if(isset($_POST['message'])) {
			$message = $_POST['message'];
		}

		// Did they select a valid theme?
		$enabledThemes = explode(",", GetConfig('GiftCertificateThemes'));

		// Only one theme enabled, automagically select it
		if(count($enabledThemes) == 1) {
			$_POST['certificate_theme'] = $enabledThemes[0];
		}
		else if(empty($enabledThemes)) {
			$_POST['certificate_theme'] = 'General';
		}
		else if(!isset($_POST['certificate_theme']) || !in_array($_POST['certificate_theme'], $enabledThemes)) {
			$errors[] = GetLang('SelectValidGiftCertificateTheme');
		}

		// Now the validation is all done, were there any errors? If yes, throw back to the purchase page
		if(!empty($errors)) {
			$this->PurchaseGiftCertificate($errors);
		}

		// Else, add the gift certificate to the cart
		else {
			$newCertificate = array(
				"amount" => $amount,
				"to_name" => $_POST['to_name'],
				"to_email" => $_POST['to_email'],
				"from_name" => $_POST['from_name'],
				"from_email" => $_POST['from_email'],
				"message" => $_POST['message'],
				"theme" => $_POST['certificate_theme']
			);

			if(isset($_POST['cartitemid']) && $_POST['cartitemid'] >= 0) {
				$this->UpdateGiftCertificateInCart($_POST['cartitemid'], $newCertificate);
				$message =  getLang('GiftCertificateUpdated');
			}
			else {
				$this->AddGiftCertificateToCart($newCertificate);
				$message = getLang('GiftCertificateAddedToCart');
			}

			flashMessage($message, MSG_SUCCESS, 'cart.php');
		}
	}

	// Add the gift certificate to the cart
	public function AddGiftCertificateToCart($certificateData)
	{
		$name = currencyConvertFormatPrice($certificateData['amount']) . ' ' . GetLang('GiftCertificate');
		$quote = getCustomerQuote();
		$item = new ISC_QUOTE_ITEM_GIFTCERTIFICATE;
		$item
			->setQuote($quote)
			->setName($name)
			->setBasePrice($certificateData['amount'], true)
			->setQuantity(1)
			->setRecipientName($certificateData['to_name'])
			->setRecipientEmail($certificateData['to_email'])
			->setSenderName($certificateData['from_name'])
			->setSenderEmail($certificateData['from_email'])
			->setMessage($certificateData['message'])
			->setTheme($certificateData['theme']);
		$quote->addItem($item);
		return true;
	}

	public function UpdateGiftCertificateInCart($itemid, $certificateData)
	{
		$name = currencyConvertFormatPrice($certificateData['amount']) . ' ' . GetLang('GiftCertificate');
		$quote = getCustomerQuote();
		if(!$quote->hasItem($itemid) ||
			$quote->getItemById($itemid)->getType() != PT_GIFTCERTIFICATE) {
				return false;
		}

		$item = $quote->getItemById($itemid);
		$item
			->setName($name)
			->setBasePrice($certificateData['amount'], true)
			->setQuantity(1)
			->setRecipientName($certificateData['to_name'])
			->setRecipientEmail($certificateData['to_email'])
			->setSenderName($certificateData['from_name'])
			->setSenderEmail($certificateData['from_email'])
			->setMessage($certificateData['message'])
			->setTheme($certificateData['theme']);
		return true;
	}

	private function PurchaseGiftCertificate($errors = array())
	{

		// Coming back to this page with one or more errors?
		$GLOBALS['HideErrorMessage'] = 'none';
		if(is_array($errors)) {
			$errors = implode("<br />", $errors);
		}
		if($errors != "") {
			$GLOBALS['HideErrorMessage'] = '';
			$GLOBALS['ErrorMessage'] = $errors;
		}

		$editing = false;

		$GLOBALS['CartItemId'] = -1;

		$quote = getCustomerQuote();

		if(!$errors) {
			// Editing an existing cart item
			if(isset($_REQUEST['itemid'])) {
				$itemid = $_REQUEST['itemid'];
				if($quote->hasItem($itemid) &&
					$quote->getItemById($itemid)->getType() == PT_GIFTCERTIFICATE) {
						$item = $quote->getItemById($itemid);
						$_POST = array(
							'selected_amount' => $item->getPrice(),
							'certificate_amount' => convertPriceToCurrency($item->getPrice()),
							'to_name' => $item->getRecipientName(),
							'to_email' => $item->getRecipientEmail(),
							'from_name' => $item->getSenderName(),
							'from_email' => $item->getSenderEmail(),
							'message' => $item->getMessage(),
							'certificate_theme' => $item->getTheme()
						);
						$editing = true;
						$GLOBALS['CartItemId'] = $item->getId();
				}
			}
		}
		else {
			if(isset($_REQUEST['cartitemid'])) {
				$editing = true;
				$GLOBALS['CartItemId'] = isc_html_escape($_REQUEST['cartitemid']);
			}
		}

		if($editing == true) {
			$GLOBALS['SaveGiftCertificateButton'] = GetLang('UpdateCertificateCart');
			$GLOBALS['CertificateTitle'] = GetLang('UpdateGiftCertificate');
		}
		else {
			$GLOBALS['SaveGiftCertificateButton'] = GetLang('AddCertificateCart');
			$GLOBALS['CertificateTitle'] = GetLang('PurchaseAGiftCertificate');
		}

		if($editing == true || $errors) {
			$GLOBALS['AgreeChecked'] = "checked=\"checked\"";
		}

		// Can the user select from one or more predefined amounts?
		$GLOBALS['GiftCertificateAmountSelect'] = '';
		if(GetConfig('GiftCertificateCustomAmounts') == 0) {
			foreach(GetConfig('GiftCertificateAmounts') as $amount) {
				$displayAmount = CurrencyConvertFormatPrice($amount);
				$sel = '';
				if(isset($_POST['selected_amount']) && $_POST['selected_amount'] == $amount) {
					$sel = 'selected=\"selected\"';
				}
				$GLOBALS['GiftCertificateAmountSelect'] .= sprintf("<option value='%s' %s>%s</option>", $amount, $sel, $displayAmount);
			}
			$GLOBALS['HideGiftCertificateCustomAmount'] = "none";
		}

		// Can the user enter their own amount?
		else {
			if(isset($_POST['certificate_amount'])) {
				$GLOBALS['CustomCertificateAmount'] = isc_html_escape($_POST['certificate_amount']);
				$GLOBALS['CustomAmountChecked'] = 'checked="checked"';
			}

			$GLOBALS['HideGiftCertificateAmountSelect'] = "none";

			// Is there a minimum and maximum limit? Firstly convert them to our selected currency
			$GLOBALS['GiftCertificateMinimum'] = ConvertPriceToCurrency(GetConfig('GiftCertificateMinimum'));
			$GLOBALS['GiftCertificateMaximum'] = ConvertPriceToCurrency(GetConfig('GiftCertificateMaximum'));

			if(GetConfig('GiftCertificateMinimum') > 0 && GetConfig('GiftCertificateMaximum') > 0) {
				$GLOBALS['GiftCertificateRange'] = sprintf(GetLang('GiftCertificateValueBetween'), CurrencyConvertFormatPrice(GetConfig('GiftCertificateMinimum')), CurrencyConvertFormatPrice(GetConfig('GiftCertificateMaximum')));
			}
			else if(GetConfig('GiftCertificateMinimum')) {
				$GLOBALS['GiftCertificateRange'] = sprintf(GetLang('GiftCertificateValueGreaterThan'), CurrencyConvertFormatPrice(GetConfig('GiftCertificateMinimum')));
			}
			else if(GetConfig('GiftCertificateMaximum')) {
				$GLOBALS['GiftCertificateRange'] = sprintf(GetLang('GetCertificateValueLessThan'), CurrencyConvertFormatPrice(GetConfig('GiftCertificateMaximum')));
			}
		}

		// If there is an expiry date for gift certificates, we need to show it just so the user is aware
		if(GetConfig('GiftCertificateExpiry') > 0) {
			$days = GetConfig('GiftCertificateExpiry')/86400;
			if(($days % 365) == 0) {
				if(($days/365) == 1) {
					$GLOBALS['ExpiresAfter'] = "1 ".GetLang('YearLower');
				} else {
					$GLOBALS['ExpiresAfter'] = number_format($days/365)." ".GetLang('YearsLower');
				}
			}
			else if(($days % 30) == 0) {
				if($days/30 == 1) {
					$GLOBALS['ExpiresAfter'] = "1 ".GetLang('MonthLower');
				} else {
					$GLOBALS['ExpiresAfter'] = number_format($days/30)." ".GetLang('MonthsLower');
				}
			}
			else if(($days % 7) == 0) {
				if(($days/7) == 1) {
					$GLOBALS['ExpiresAfter'] = "1 ".GetLang('WeeksLower');
				} else {
					$GLOBALS['ExpiresAfter'] = number_format($days/7)." ".GetLang('WeeksLower');
				}
			}
			else {
				if($days == 1) {
					$GLOBALS['ExpiresAfter'] = "1 ".GetLang('DayLower');
				} else {
					$GLOBALS['ExpiresAfter'] = number_format($days)." ".GetLang('DaysLower');
				}
			}
		}

		if(isset($GLOBALS['ExpiresAfter'])) {
			$GLOBALS['GiftCertificateTerms'] = sprintf(GetLang('GiftCertificateTermsExpires'), $GLOBALS['ExpiresAfter']);
		}
		else {
			$GLOBALS['HideExpiryInfo'] = "none";
		}

		// Get a list of the gift certificate themes
		$themes = @scandir(APP_ROOT."/templates/__master/__gift_themes/");
		$enabledThemes = explode(",", GetConfig('GiftCertificateThemes'));

		$GLOBALS['GiftCertificateThemes'] = '';
		if(count($enabledThemes) == 1) {
			$GLOBALS['HideThemeSelect'] = "none";
		}
		foreach($enabledThemes as $theme) {
			// Just double check this theme still actually exists
			if(in_array($theme, $themes)) {
				$themeName = preg_replace('#\.html$#i', "", $theme);
				$sel = '';
				if((isset($_POST['certificate_theme']) && $_POST['certificate_theme'] == $theme) || count($enabledThemes) == 1) {
					$sel = 'checked="checked"';
					$GLOBALS['SelectedCertificateTheme'] = $theme;
				}
				$GLOBALS['GiftCertificateThemes'] .= sprintf('<label><input type="radio" class="themeCheck" name="certificate_theme" value="%s" %s /> %s</label><br />', $theme, $sel, $themeName);
			}
		}

		if(!GetConfig('GiftCertificateThemes')) {
			$GLOBALS['HideErrorMessage'] = '';
			$GLOBALS['ErrorMessage'] = GetLang('NoGiftCertificateThemes');
			$GLOBALS['HideGiftCertificateForm'] = "none";
		}

		// Do we need to pre-fill the to details with anything?
		if(isset($_POST['to_name'])) {
			$GLOBALS['CertificateTo'] = isc_html_escape($_POST['to_name']);
		}
		else {
			$GLOBALS['CertificateTo'] = '';
		}
		if(isset($_POST['to_email'])) {
			$GLOBALS['CertificateToEmail'] = isc_html_escape($_POST['to_email']);
		}
		else {
			$GLOBALS['CertifcateToEmail'] = '';
		}

		$customer = null;
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');

		// From details
		if(isset($_POST['from_name'])) {
			$GLOBALS['CertificateFrom'] = isc_html_escape($_POST['from_name']);
		}
		else {
			$customer = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();
			if(is_array($customer)) {
				$GLOBALS['CertificateFrom'] = isc_html_escape($customer['custconfirstname'] . ' ' . $customer['custconlastname']);
			}
		}
		if(isset($_POST['from_email'])) {
			$GLOBALS['CertificateFromEmail'] = isc_html_escape($_POST['from_email']);
		}
		else {
			if($customer === null) {
				$customer = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();
			}
			if(is_array($customer)) {
				$GLOBALS['CertificateFromEmail'] = isc_html_escape($customer['custconemail']);
			}
		}

		if(isset($_POST['message'])) {
			$GLOBALS['CertificateMessage'] = isc_html_escape($_POST['message']);
		}

		$GLOBALS['GiftCertificatePreviewModalTitle'] = GetLang('GiftCertificatePreviewModalTitle');

		// Show the gift certificates main page
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('GiftCertificates')));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("giftcertificates");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	private function RedeemGiftCertificate()
	{
		// Show the gift certificates main page
		$GLOBALS['ISC_LANG']['RedeemGiftCertificateIntro'] = sprintf(GetLang('RedeemGiftCertificateIntro'), $GLOBALS['StoreName']);
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('GiftCertificates')));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("giftcertificates_redeem");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}


	// Show a preview of a gift certificate before purchasing
	private function PreviewGiftCertificate()
	{
		if(!isset($_REQUEST['certificate_theme'])) {
			$_REQUEST['certificate_theme'] = 'General';
		}

		$_REQUEST['certificate_theme'] = basename($_REQUEST['certificate_theme']);

		$certificate = array(
			"giftcertto" => $_REQUEST['to_name'],
			"giftcerttoemail" => $_REQUEST['to_email'],
			"giftcertfrom" => $_REQUEST['from_name'],
			"giftcertfromemail" => $_REQUEST['from_email'],
			"giftcertmessage" => $_REQUEST['message'],
			"giftcerttemplate" => $_REQUEST['certificate_theme'],
			"giftcertcode" => 'XXX-XXX-XXX-XXX'
		);

		if(GetConfig('GiftCertificateExpiry') > 0) {
			$certificate['giftcertexpirydate'] = time() + GetConfig('GiftCertificateExpiry');
		}

		if(isset($_REQUEST['selected_amount']) && $_REQUEST['selected_amount'] != "") {
			$certificate['giftcertamount'] = $_REQUEST['selected_amount'];
		}
		else {
			// Revert this currecny to the default one as this price is user input and therefor is not based on the default currency
			$certificate['giftcertamount'] = ConvertPriceToDefaultCurrency($_REQUEST['certificate_amount']);
		}
		echo $this->GenerateGiftCertificate($certificate);
		die();
	}

	public function GenerateGiftCertificate($certificate)
	{
		$filename = !empty($certificate['giftcerttemplate']) ? $certificate['giftcerttemplate'] : 'General.html';

		if($theme = ISC_GIFTCERTIFICATE_THEME::findByFilename($filename)) {
			return $theme->generateGiftCertificateHTML($certificate);
		}

		return null;
	}

	/**
	 * Generate a random gift certificate code and check that it isn't currently in use.
	 *
	 * @return string The generated gift certificate code.
	 */
	private function _GenerateGiftCertificateCode()
	{
		$retval = '';
		do {
			for($i = 1; $i <= 12; $i++) {
				if(rand(1, 2) == 1) {
					$retval .= chr(rand(65, 90));
				} else {
					$retval .= chr(rand(48, 57));
				}

				if(($i % 3) == 0 && $i != 12) {
					$retval .= "-";
				}
			}

			// Is this certificate code already in use?
			$query = sprintf("SELECT giftcertid FROM [|PREFIX|]gift_certificates WHERE giftcertcode='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($retval));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$existingCode = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		} while($existingCode);

		return $retval;
	}

	/**
	 * Applies one or more gift certificates from an order to the actual order.
	 * This function subtracts the balances from the gift certificates as well as
	 * logs a record in the gift certificates table. It is called once an order is successfully
	 * placed.
	 *
	 * @param float The total of the order.
	 * @param array Array of gift certificates that are being applied to the order. This is the array from the checkout session.
	 * @param float The remaining balance of the order if there is one, passed back by reference
	 * @param array An array passed back by reference containing details for all the bad gift certificates that can't applied to the order.
	 *
	 */
	public function GiftCertificatesApplicableToOrder($orderTotal, $giftCertificates, &$remainingBalance, &$badGiftCertificates)
	{
		// If no gift certificates were used in this order, we don't need to do anything
		if(!is_array($giftCertificates) || empty($giftCertificates)) {
			return;
		}

		$remainingBalance = $orderTotal;
		$certificates = array();

		// Load the gift certificates from the database. This will use up the smallest amounts on gift certificates
		// first before using larger amounts - so you don't end up with 10 x 20c gift certificates for example.
		$giftCertificateIds = array();
		foreach($giftCertificates as $giftCertificate) {
			$giftCertificateIds[] = $giftCertificate['id'];
		}
		$giftCertificateIds = implode("','", $giftCertificateIds);

		$query = sprintf("SELECT * FROM [|PREFIX|]gift_certificates WHERE giftcertid IN (%s) ORDER BY giftcertbalance ASC", $giftCertificateIds);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($certificate = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$certificates[$certificate['giftcertid']] = $certificate;
		}

		foreach($giftCertificates as $cert) {
			$id = $cert['id'];
			if(!isset($certificates[$id])) {
				$badGiftCertificates[$cert['code']] = "invalid";
				continue;
			}
			else {
				$certificate = $certificates[$id];

				// There is no remaining balance on this gift certificate - how did they even use it?
				if($certificate['giftcertbalance'] == 0) {
					$badGiftCertificates[$certificate['code']] = "balance";
					continue;
				}
				else if($certificate['giftcertstatus'] != 2 && $certificate['giftcertstatus'] != 4) {
					$badGiftCertificates[$cert['code']] = "invalid";
					continue;
				}
				else if($certificate['giftcertexpirydate'] != 0 && $certificate['giftcertexpirydate'] < time()) {
					if($certificate['giftcertstatus'] != 4) {
						$updatedCert = array(
							"giftcertstatus" => 4
						);
						$GLOBALS['ISC_CLASS_DB']->UpdateQuery("gift_certificates", $updatedCert, "giftcertid='".$GLOBALS['ISC_CLASS_DB']->Quote($certificate['giftcertid'])."'");
					}
					$badGiftCertificates[$cert['code']] = array("expired", $certificate['giftcertexpirydate']);
					continue;
				}
				else {
					// Using all of this gift certificate
					if($remainingBalance >= $certificate['giftcertbalance']) {
						$remainingBalance -= $certificate['giftcertbalance'];
						$balanceUsed = $certificate['giftcertbalance'];
						$newCertificateBalance =  0;
					}
					// Using part of this balance
					else {
						$newCertificateBalance = $certificate['giftcertbalance'] - $remainingBalance;
						$balanceUsed = $certificate['giftcertbalance'] - $newCertificateBalance;
						$remainingBalance = 0;
					}
				}
			}
		}
		if(!empty($badGiftCertificates)) {
			return false;
		}
		return true;
	}

	/**
	 * Applies one or more gift certificates from an order to the actual order.
	 * This function subtracts the balances from the gift certificates as well as
	 * logs a record in the gift certificates table. It is called once an order is successfully
	 * placed.
	 *
	 * @param int The order ID the gift certificates are being used for.
	 * @param float The totalof the order.
	 * @param array Array of gift certificates that are being applied to the order. This is the array from the checkout session.
	 * @param array An array passed back by reference containing details for all the gift certificates that were successfully applied to the order.
	 *
	 */
	public function ApplyGiftCertificatesToOrder($orderId, $orderTotal, $giftCertificates, &$usedCertificates)
	{
		$remainingBalance = $orderTotal;

		// If no gift certificates were used in this order, we don't need to do anything
		if(!is_array($giftCertificates) || empty($giftCertificates)) {
			return;
		}

		$remainingBalance = $orderTotal;
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		$customerId = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

		$giftCertificateIds = array();
		foreach($giftCertificates as $giftCertificate) {
			$giftCertificateIds[] = $giftCertificate['id'];
		}
		$giftCertificateIds = implode("','", $giftCertificateIds);

		$query = sprintf("SELECT * FROM [|PREFIX|]gift_certificates WHERE giftcertid IN ('%s') ORDER BY giftcertbalance ASC", $giftCertificateIds);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($certificate = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$certid = $certificate['giftcertid'];
			$balance = $certificate['giftcertbalance'];

			// has this certificate already been saved for this order?
			$query = "SELECT * FROM [|PREFIX|]gift_certificate_history WHERE histgiftcertid = " . $certid." AND historderid=".(int)$orderId;
			$certresult = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($hist = $GLOBALS['ISC_CLASS_DB']->Fetch($certresult)) {
				// temporarily recredit the balance
				$balance += $hist['histbalanceused'];

				// remove this record
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('gift_certificate_history', 'WHERE historyid = ' . $hist['historyid']);
			}

			// Using all of this gift certificate
			if($remainingBalance >= $balance) {
				$remainingBalance -= $balance;
				$newCertificateBalance =  0;
			}
			// Using part of this balance
			else {
				$newCertificateBalance = $balance - $remainingBalance;
			}

			// Update the balance of this gift certificate
			$updatedCertificate = array(
				"giftcertbalance" => $newCertificateBalance
			);
			if($newCertificateBalance == 0) {
				$updatedCertificate['giftcertstatus'] = 4;
			}

			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("gift_certificates", $updatedCertificate, "giftcertid='" . $GLOBALS['ISC_CLASS_DB']->Quote($certid) . "'");

			// Log the balance change in the history table
			$newHistoryEntry = array(
				"histgiftcertid" => $certid,
				"historderid" => $orderId,
				"histcustomerid" => $customerId,
				"histbalanceused" => $balance - $newCertificateBalance,
				"histbalanceremaining" => $newCertificateBalance,
				"historddate" => time()
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery("gift_certificate_history", $newHistoryEntry);

			// Send back this gift certificate so we can tell the customer it was used
			$usedCertificates[] = array(
				"giftcertcode" => $certificate['giftcertcode'],
				"giftcertbalance" => $newCertificateBalance,
				"giftcertexpiry" => $certificate['giftcertexpirydate']
			);
		}

		// check for any gift certificates in an order that have been removed and recredit them
		$query = sprintf("SELECT * FROM [|PREFIX|]gift_certificate_history WHERE historderid = %s AND histgiftcertid NOT IN (%s)", $orderId, $giftCertificateIds);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// recredit and activate the certificate
			$query = "UPDATE [|PREFIX|]gift_certificates SET giftcertbalance = giftcertbalance + " . $row['histbalanceused'] . ", giftcertstatus = 2 WHERE giftcertid = " . $row['histgiftcertid'];
			$GLOBALS['ISC_CLASS_DB']->Query($query);
		}

		// delete the removed certificates
		$query = sprintf("DELETE FROM [|PREFIX|]gift_certificate_history WHERE historderid = %s  AND histgiftcertid NOT IN (%s)", $orderId, $giftCertificateIds);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	}

	/**
	 * Activate the gift certificates that were purchased for a specific order.
	 * This function will update the status of all gift certificates for a specific order
	 * and set them to enabled so they can be used. It is called once a user has successfully placed (and
	 * paid for) an order containging one or more gift certificates or when an order status is changed from
	 * 'Awaiting Payment'. This function will also send each of the recipients their gift certificate via email.
	 *
	 * @param int The order ID to activate purchased gift certificates for .
	 */
	public function ActivateGiftCertificates($orderId)
	{
		$certificateUpdates = array();
		// Select all of the inactive gift certificates for this order
		$query = sprintf("SELECT * FROM [|PREFIX|]gift_certificates WHERE giftcertorderid='%d' AND (giftcertstatus=1 OR giftcertstatus=0)", $GLOBALS['ISC_CLASS_DB']->Quote($orderId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($certificate = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$certificateUpdates[] = $certificate['giftcertid'];

			// Send the gift certificate to the recipient
			$this->SendGiftCertificateEmail($certificate);
		}

		// Are gift certificates set to expire?
		if(GetConfig('GiftCertificateExpiry') > 0) {
			$expiry = time() + GetConfig('GiftCertificateExpiry');
		}
		else {
			$expiry = 0;
		}

		// If there were any gift certificates activated, update their status to active
		if (!empty($certificateUpdates)) {
			$updatedCertificate = array(
				"giftcertstatus" => 2,
				"giftcertexpirydate" => $expiry
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("gift_certificates", $updatedCertificate, "giftcertid IN (".implode(",", $GLOBALS['ISC_CLASS_DB']->Quote($certificateUpdates)).")");
		}
	}

	/**
	 * Email a gift certificate to a defined recipient.
	 * This function will email a gift certificate to a recipient. It generates the gift certificate from
	 * the selected template and attaches it to the gift certificate email.
	 */
	public function SendGiftCertificateEmail($giftCertificate)
	{
		if(!$giftCertificate['giftcerttoemail']) {
			return;
		}

		$certificate = $this->GenerateGiftCertificate($giftCertificate);


		if(!isset($GLOBALS['ShopPathNormal'])) {
			$GLOBALS['ShopPathNormal'] = $GLOBALS['ShopPath'];
		}

		// Build the email
		$GLOBALS['ToName'] = isc_html_escape($giftCertificate['giftcertto']);
		$GLOBALS['FromName'] = isc_html_escape($giftCertificate['giftcertfrom']);
		$GLOBALS['FromEmail'] = isc_html_escape($giftCertificate['giftcertfromemail']);
		$GLOBALS['Amount'] = FormatPrice($giftCertificate['giftcertamount']);
		$GLOBALS['Intro'] = sprintf(GetLang('GiftCertificateEmailIntro'), $GLOBALS['FromName'], $GLOBALS['FromEmail'], $GLOBALS['Amount'], $GLOBALS['ShopPathNormal'], $GLOBALS['StoreName']);
		$GLOBALS['ISC_LANG']['GiftCertificateEmailInstructions'] = sprintf(GetLang('GiftCertificateEmailInstructions'), $GLOBALS['ShopPathNormal']);
		$GLOBALS['ISC_LANG']['GiftCertificateFrom'] = sprintf(GetLang('GiftCertificateFrom'), $GLOBALS['StoreName'], isc_html_escape($giftCertificate['giftcertfrom']));
		if($giftCertificate['giftcertexpirydate'] != 0) {
			$expiry = CDate($giftCertificate['giftcertexpirydate']);
			$GLOBALS['GiftCertificateExpiryInfo'] = sprintf(GetLang('GiftCertificateEmailExpiry'), $expiry);
		}

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("giftcertificate_email");
		$message = $emailTemplate->ParseTemplate(true);

		// Create a new email API object to send the email
		$store_name = GetConfig('StoreName');
		$subject = sprintf(GetLang('GiftCertificateEmailSubject'), $giftCertificate['giftcertfrom'], $store_name);
		require_once(ISC_BASE_PATH . "/lib/email.php");
		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), $store_name);
		$obj_email->Set('Subject', $subject);
		$obj_email->AddBody("html", $message);
		$obj_email->AddRecipient($giftCertificate['giftcerttoemail'], "", "h");
		$obj_email->AddAttachmentData($certificate, GetLang('GiftCertificate') . ' #' . $giftCertificate['giftcertid'].".html");
		$email_result = $obj_email->Send();
	}

	/**
	 * Create the purchased gift certificates from a pending order.
	 * This function takes a list of gift certificates purchased for an order and will insert them in
	 * to the gift certificates table with the status passed. The status passed sets if these gift
	 * certificates are enabled and can be used to make purchases with.
	 *
	 * @param int The order ID that purchased the gift certificates.
	 * @param array An array of gift certificates purchased.
	 * These are ISC_QUOTE_ITEM_GIFTCERTIFICATE instances.
	 * @param int The status to set the gift certificates as. Status of 0 means not active, status of 1 means active and can be used.
	 */
	public function CreateGiftCertificatesFromOrder($orderId, $certificates, $status=0)
	{
		if(!is_array($certificates) || empty($certificates)) {
			return;
		}

		$customerId = getClass('ISC_CUSTOMER')->getCustomerId();

		foreach($certificates as $item) {
			// Skip anything that isn't an actual gift certificate.
			if($item->getType() != PT_GIFTCERTIFICATE) {
				continue;
			}

			$amount = $item->getPrice();

			// Are gift certificates set to expire?
			if(GetConfig('GiftCertificateExpiry') > 0) {
				$expiry = time() + GetConfig('GiftCertificateExpiry');
			}
			else {
				$expiry = 0;
			}

			// Insert a new gift certificate for the quantity purchased
			$quantity = $item->getQuantity();
			for($i = 1; $i <= $quantity; ++$i) {
				$newCertificate = array(
					'giftcertcode'			=> $this->_GenerateGiftCertificateCode(),
					'giftcertto'			=> $item->getRecipientName(),
					'giftcerttoemail'		=> $item->getRecipientEmail(),
					'giftcertfrom'			=> $item->getSenderName(),
					'giftcertfromemail'		=> $item->getSenderEmail(),
					'giftcertcustid'		=> $customerId,
					'giftcertamount'		=> $amount,
					'giftcertbalance'		=> $amount,
					'giftcertstatus'		=> $status,
					'giftcerttemplate'		=> $item->getTheme(),
					'giftcertmessage'		=> $item->getMessage(),
					'giftcertpurchasedate'	=> time(),
					'giftcertexpirydate'	=> $expiry,
					'giftcertorderid'		=> $orderId,
				);

				// Insert the gift cetificate
				$newCertificate['giftcertid'] = $GLOBALS['ISC_CLASS_DB']->InsertQuery("gift_certificates", $newCertificate);

				// If this certificate is active, email it to the customer
				if($status == 2) {
					$this->SendGiftCertificateEmail($newCertificate);
				}
			}
		}
	}
}