<?php
if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_ADMIN_REMOTE_VENDORS extends ISC_ADMIN_REMOTE_BASE
{
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('orders');
		parent::__construct();
	}

	/**
	 * Handle the incoming action.
	 */
	public function HandleToDo()
	{
		$what = isc_strtolower(@$_REQUEST['w']);

		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
			exit;
		}

		switch ($what) {
			case "getvendorpaymentdetails":
				$this->GetVendorPaymentDetails();
				break;
		}
	}

	/**
	 * Fetch the payment details (outstanding balance etc) for a specific vendor.
	 */
	private function GetVendorPaymentDetails()
	{
		if(!isset($_REQUEST['vendorId'])) {
			exit;
		}

		$paymentClass = GetClass('ISC_ADMIN_VENDOR_PAYMENTS');
		$paymentDetails = $paymentClass->CalculateOutstandingVendorBalance($_REQUEST['vendorId']);

		$tags[] = $this->MakeXMLTag('status', 1);
		$tags[] = $this->MakeXMLTag('fromDate', CDate($paymentDetails['lastPaymentDate']), true);
		$tags[] = $this->MakeXMLTag('toDate', CDate(time()), true);
		$tags[] = $this->MakeXMLTag('outstandingBalance', FormatPrice($paymentDetails['outstandingBalance']), true);
		$tags[] = $this->MakeXMLTag('balanceForward', FormatPrice($paymentDetails['balanceForward']), true);
		$tags[] = $this->MakeXMLTag('issuedCredit', "-" . FormatPrice($paymentDetails['issuedCredit']), true);
		$tags[] = $this->MakeXMLTag('issuedCreditRaw', $paymentDetails['issuedCredit'], true);
		$tags[] = $this->MakeXMLTag('profitMargin', FormatPrice($paymentDetails['profitMargin']), true);
		$tags[] = $this->MakeXMLTag('profitMarginPercentage', $paymentDetails['profitMarginPercentage'], true);
		$tags[] = $this->MakeXMLTag('totalOrders', FormatPrice($paymentDetails['totalOrders']), true);
		$this->SendXMLHeader();
		$this->SendXMLResponse($tags);
		exit;
	}
}