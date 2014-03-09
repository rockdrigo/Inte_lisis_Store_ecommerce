<?php
/**
 * Vendor Payment Manager.
 */
class ISC_ADMIN_VENDOR_PAYMENTS extends ISC_ADMIN_BASE
{
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('vendor.payments');
	}

	/**
	 * Handle the incoming action.
	 *
	 * @param string The action to perform.
	 */
	public function HandleToDo($do)
	{
		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Vendors)) {
			exit;
		}

		if(!gzte11(ISC_HUGEPRINT)) {
			ob_end_clean();
			header('Location: index.php');
			exit;
		}

		// Set up some generic breadcrumb entries as these will be used on most pages
		$GLOBALS['BreadcrumEntries'] = array(
			GetLang('Home') => 'index.php',
			GetLang('VendorPayments') => 'index.php?ToDo=viewVendorPayments'
		);

		switch(strtolower($do)) {
			case 'addvendorpayment':
				$this->AddVendorPayment();
				break;
			case 'savenewvendorpayment':
				$this->SaveNewVendorPayment();
				break;
			case 'exportvendorpayments':
				$this->ExportVendorPayments();
				break;
			case 'deletevendorpayments':
				$this->DeleteVendorPayments();
			default:
				$this->ManageVendorPayments();
				break;
		}
	}

	/**
	 * Delete one or more selected vendor payments from the database.
	 */
	private function DeleteVendorPayments()
	{
		if(!isset($_POST['payments']) || !is_array($_POST['payments'])) {
			ob_end_clean();
			header('Location: index.php?ToDo=viewPayments');
			exit;
		}

		// Now it's safe to delete the payments
		$paymentIds = implode(',', array_map('intval', $_POST['payments']));
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('vendor_payments', "WHERE paymentid IN (".$paymentIds.")");

		if(!$GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['payments']));
			FlashMessage(GetLang('VendorPaymentsDeleted'), MSG_SUCCESS, 'index.php?ToDo=viewVendorPayments');
		}
		// If there was an error, redirect and show the error
		else {
			FlashMessage($GLOBALS['ISC_CLASS_DB']->GetErrorMsg(), MSG_ERROR, 'index.php?ToDo=viewVendorPayments');
		}
	}

	/**
	 * Export vendor payments (for all vendors or a specific vendor) to a CSV or XML file.
	 */
	private function ExportVendorPayments()
	{
		// Validate the sort order
		if(isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == 'asc') {
			$sortOrder = 'asc';
		}
		else {
			$sortOrder = 'desc';
		}

		// Which fields can we sort by?
		$validSortFields = array(
			'paymentid',
			'paymentfrom',
			'vendorname',
			'paymentamount',
			'paymentmethod',
			'paymentdate'
		);
		if(isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
			$sortField = $_REQUEST['sortField'];
			SaveDefaultSortField('ManageVendorPayments', $_REQUEST['sortField'], $sortOrder);
		}
		else {
			list($sortField, $sortOrder) = GetDefaultSortField('ManageVendorPayments', 'paymentid', $sortOrder);
		}

		ob_end_clean();

		// Grab the queries we'll be executing
		$paymentQueries = $this->BuildVendorPaymentSearchQuery(0, $sortField, $sortOrder, false);
		$numPayments = $GLOBALS['ISC_CLASS_DB']->FetchOne($paymentQueries['countQuery']);
		if(!$numPayments) {
			header('Location: index.php?ToDo=viewVendorPayments');
			exit;
		}

		// Set up the list of columns
		$columns = array(
			'paymentid' => 'PAYMENT ID',
			'paymentfrom' => 'PAYMENT FROM',
			'paymentto' => 'PAYMENT TO',
			'paymentvendorid' => 'PAYMENT VENDOR ID',
			'vendorname' => 'PAYMENT VENDOR NAME',
			'paymentamount' => 'PAYMENT AMOUNT',
			'paymentforwardbalance' => 'OUTSTANDING BALANCE',
			'paymentdate' => 'PAYMENT DATE',
			'paymentmethod' => 'PAYMENT METHOD',
			'paymentcomments' => 'PAYMENT COMMENTS'
		);

		if(!isset($_GET['format']) || $_GET['format'] == "csv") {
			$ext = 'csv';
		}
		else {
			$ext = 'xml';
		}

		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(isc_strtoupper($_REQUEST['format']));

		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=\"payments-".isc_date("Y-m-d").".".$ext."\";");

		if($ext == 'csv') {
			$row = '';
			foreach($columns as $field) {
				$row .= EXPORT_FIELD_ENCLOSURE.$field.EXPORT_FIELD_ENCLOSURE.EXPORT_FIELD_SEPARATOR;
			}
			echo rtrim($row, EXPORT_FIELD_SEPARATOR);
			echo EXPORT_RECORD_SEPARATOR;
		}
		else {
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			echo  "<payments>\n";
		}

		// Export the payments
		$result = $GLOBALS['ISC_CLASS_DB']->Query($paymentQueries['query']);
		while($payment = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// If CSV export, handle that now
			if($ext == 'csv') {
				$row = '';
				foreach($columns as $k => $v) {
					switch($k) {
						case 'paymentfrom':
						case 'paymentto':
						case 'paymentdate':
							$value = isc_date(GetConfig('ExportDateFormat'), $payment[$k]);
							break;
						case 'paymentamount':
						case 'paymentforwardbalance':
							$value = FormatPrice($payment[$k]);
						default:
							$value = $payment[$k];
					}

					$value = str_replace(EXPORT_FIELD_ENCLOSURE, EXPORT_FIELD_ENCLOSURE . EXPORT_FIELD_ENCLOSURE, $value);
					$row .= EXPORT_FIELD_ENCLOSURE.$value.EXPORT_FIELD_ENCLOSURE.EXPORT_FIELD_SEPARATOR;
				}
				echo rtrim($row, EXPORT_FIELD_SEPARATOR);
				echo EXPORT_RECORD_SEPARATOR;
				@flush();
			}
			// XML is easy!
			else {
				echo "\t<payment paymentid=\"".$payment['paymentid']."\">\n";
				foreach($columns as $k => $v) {
					switch($k) {
						case 'paymentfrom':
						case 'paymentto':
						case 'paymentdate':
							$value = isc_date(GetConfig('ExportDateFormat'), $payment[$k]);
							break;
						case 'paymentamount':
						case 'paymentforwardbalance':
							$value = FormatPrice($payment[$k]);
						default:
							$value = $payment[$k];
					}

					echo "\t\t<".$k."><![CDATA[".$value."]]></".$k.">\n";
					flush();
				}
				echo "\t</payment>\n";
			}
		}

		if($ext == 'xml') {
			echo "</payments>";
		}
		exit;
	}

	/**
	 * Log a payment to a vendor.
	 */
	private function AddVendorPayment()
	{
		$GLOBALS['VendorList'] = $this->BuildVendorList();

		if (GetConfig('CurrencyLocation') == 'right') {
			$GLOBALS['RightCurrencyToken'] = GetConfig('CurrencyToken');
		}
		else {
			$GLOBALS['LeftCurrencyToken'] = GetConfig('CurrencyToken');
		}

		// Grab a unique list of all of the previously used payment methods
		$paymentMethods = array();
		$query = "
			SELECT DISTINCT paymentmethod
			FROM [|PREFIX|]vendor_payments
			ORDER BY paymentmethod
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($method = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if(isset($paymentMethods[strtolower($method['paymentmethod'])])) {
				continue;
			}
			$paymentMethods[strtolower($method['paymentmethod'])] = $method['paymentmethod'];
		}

		$GLOBALS['PaymentMethodList'] = '';
		foreach($paymentMethods as $method) {
			$GLOBALS['PaymentMethodList'] .= "'".addslashes($method)."', ";
		}
		$GLOBALS['PaymentMethodList'] = rtrim($GLOBALS['PaymentMethodList'], ', ');

		$GLOBALS['BreadcrumEntries'][GetLang('AddVendorPayment')] = '';
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('vendorpayments.form.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	/**
	 * Actually save the new payment for the vendor in the database.
	 */
	private function SaveNewVendorPayment()
	{
		$message = '';
		if(isset($_POST['paymentvendorid'])) {
			$paymentDetails = $this->CalculateOutstandingVendorBalance($_POST['paymentvendorid']);
			$_POST['paymentfrom'] = $paymentDetails['lastPaymentDate'];
			$_POST['paymentto'] = time();
		}
		if(!$this->ValidateVendorPayment($_POST, $message)) {
			FlashMessage($message, MSG_ERROR);
			$this->AddVendorPayment();
			return;
		}

		if(!$this->CommitVendorPayment($_POST)) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			FlashMessage(GetLang('ProblemSavingPayment').$error, MSG_ERROR);
			$this->AddVendorPayment();
			return;
		}
		else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['paymentamount']);
			FlashMessage(GetLang('VendorPaymentCreated'), MSG_SUCCESS, 'index.php?ToDo=viewVendorPayments');
		}
	}

	/**
	 * Validate the new vendor payment before it's saved.
	 *
	 * @param array An array of details about the payment.
	 * @param string Any error message, passed back by reference.
	 * @return boolean True if the payment is valid, false if not.
	 */
	private function ValidateVendorPayment($data, &$error)
	{
		$requiredFields = array(
			'paymentfrom' => GetLang('EnterPaymentFromPeriod'),
			'paymentto' => GetLang('EnterPaymentToPeriod'),
			'paymentvendorid' => GetLang('SelectPaymentVendor'),
			'paymentamount' => GetLang('EnterPaymentAmount'),
			'paymentmethod' => GetLang('EnterPaymentMethod')
		);
		foreach($requiredFields as $field => $message) {
			if(!isset($data[$field]) || $data[$field] === '') {
				$error = $message;
				echo $field;
				return false;
			}
		}

		// Was an invalid payment amount entered?
		if(CNumeric($data['paymentamount']) <= 0) {
			$error = GetLang('EnterPaymentAmount');
			return false;
		}

		// Otherwise it's valid
		return true;
	}

	/**
	 * Actually commit a vendor payment to the database.
	 *
	 * @param array An array of details about the vendor payment.
	 * @return int The ID of the new vendor payment that was just created.
	 */
	private function CommitVendorPayment($data)
	{
		if(!isset($data['paymentdeducted'])) {
			$data['paymentdeducted'] = 0;
		}

		if(!isset($data['paymentcomments'])) {
			$data['paymentcomments'] = '';
		}

		$paymentDetails = $this->CalculateOutstandingVendorBalance($data['paymentvendorid']);

		$balanceForward = number_format($paymentDetails['balanceForward'], GetConfig('DecimalPlaces'));
		$totalOrders = number_format($paymentDetails['totalOrders'], GetConfig('DecimalPlaces'));
		$profitMargin = number_format($paymentDetails['profitMargin'], GetConfig('DecimalPlaces'));

		$forwardBalance = $balanceForward + $totalOrders - $profitMargin;
		if($data['paymentdeducted']) {
			$forwardBalance -= $data['paymentamount'];
		}

		$data['paymentamount'] = CNumeric($data['paymentamount']);
		$newPayment = array(
			'paymentfrom' => $data['paymentfrom'],
			'paymentto' => $data['paymentto'],
			'paymentvendorid' => $data['paymentvendorid'],
			'paymentamount' => $data['paymentamount'],
			'paymentforwardbalance' => $forwardBalance,
			'paymentmethod' => $data['paymentmethod'],
			'paymentdate' => time(),
			'paymentdeducted' => $data['paymentdeducted'],
			'paymentcomments' => $data['paymentcomments']
		);
		$paymentId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('vendor_payments', $newPayment);

		if(isset($data['notifyvendor'])) {
			$query = "
				SELECT vendorname, vendoremail
				FROM [|PREFIX|]vendors
				WHERE vendorid='".(int)$data['paymentvendorid']."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			$emailTemplate = FetchEmailTemplateParser();
			$GLOBALS['VendorName'] = isc_html_escape($vendor['vendorname']);
			$GLOBALS['VendorPaymentEmail1'] = sprintf(GetLang('VendorPaymentEmail1'), isc_html_escape(GetConfig('StoreName')), CDate($data['paymentfrom']), CDate($data['paymentto']));
			$GLOBALS['SalesFrom'] = CDate($data['paymentfrom']);
			$GLOBALS['SalesTo'] = CDate($data['paymentto']);
			$GLOBALS['OrderTotal'] = FormatPrice($paymentDetails['totalOrders']);
			$GLOBALS['PaymentAmount'] = FormatPrice($data['paymentamount']);
			$GLOBALS['PaymentMethod'] = isc_html_escape($data['paymentmethod']);
			if($data['paymentcomments']) {
				$GLOBALS['Comments'] = '<strong>'.GetLang('Comments').':</strong><br />'.isc_html_escape($data['paymentcomments']);
			}
			$GLOBALS['AccountBalance'] = FormatPrice($forwardBalance);

			$emailTemplate->SetTemplate("vendor_payment");
			$message = $emailTemplate->ParseTemplate(true);

			// Create a new email API object to send the email
			$storeName = GetConfig('StoreName');
			$subject = sprintf(GetLang('VendorPaymentEmailSubject'), $storeName);

			require_once(ISC_BASE_PATH . "/lib/email.php");
			$objEmail = GetEmailClass();
			$objEmail->Set('CharSet', GetConfig('CharacterSet'));
			$objEmail->From(GetConfig('AdminEmail'), $storeName);
			$objEmail->Set('Subject', $subject);
			$objEmail->AddBody("html", $message);
			$objEmail->AddRecipient($vendor['vendoremail'], '', "h");
			$objEmail->Send();
		}

		if(!$paymentId) {
			return false;
		}

		return $paymentId;
	}

	/**
	 * Show the page listing the vendor payments.
	 */
	private function ManageVendorPayments()
	{
		// Fetch any payments and place them in a data grid
		$GLOBALS['PaymentDataGrid'] = $this->ManageVendorPaymentsGrid();

		// Was this an ajax based sort? Return the table now
		if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
			echo $GLOBALS['PaymentDataGrid'];
			return;
		}

		$GLOBALS['HideClearResults'] = 'display: none';
		if(isset($_REQUEST['vendorId']) && $_REQUEST['vendorId'] != 0) {
			$GLOBALS['HideClearResults'] = "";
			$selectedVendor = $_REQUEST['vendorId'];
		}
		else {
			$selectedVendor = 0;
		}

		$GLOBALS['VendorList'] = $this->BuildVendorList($selectedVendor);

		$GLOBALS['Message'] = GetFlashMessageBoxes();

		// Do we need to disable the export button and hide the search options?
		if(!$GLOBALS['PaymentDataGrid']) {
			$GLOBALS['DisableExport'] = 'disabled="disabled"';
			$GLOBALS['DisplayGrid'] = 'display: none';
			$GLOBALS['DisableDelete'] = 'disabled="disabled"';

			$vendorCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Vendors');
			if(count($_GET) > 1) {
				$GLOBALS['Message'] = MessageBox(GetLang('NoPaymentsForVendor'), MSG_ERROR);
			}
			else if(empty($vendorCache)) {
				$GLOBALS['DisableAdd'] = 'disabled="disabled"';
				$GLOBALS['DisplaySearch'] = 'display: none';
				$GLOBALS['Message'] = MessageBox(GetLang('NoVendorsConfigured'), MSG_ERROR);
			}
			else {
				$GLOBALS['Message'] = MessageBox(GetLang('NoVendorPayments'), MSG_SUCCESS);
				$GLOBALS['DisplaySearch'] = 'display: none';
			}
		}

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('vendorpayments.manage.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	/**
	 * Generate a grid containing vendor payments for the current result set.
	 *
	 * @return string The generated payment grid.
	 */
	private function ManageVendorPaymentsGrid()
	{
		$page = 0;
		$start = 0;
		$numPages = 0;

		$paymentGrid = '';
		$GLOBALS['Nav'] = '';

		// Validate the sort order
		if(isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == 'asc') {
			$sortOrder = 'asc';
		}
		else {
			$sortOrder = 'desc';
		}

		// Which fields can we sort by?
		$validSortFields = array(
			'paymentid',
			'paymentfrom',
			'vendorname',
			'paymentamount',
			'paymentmethod',
			'paymentdate'
		);
		if(isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
			$sortField = $_REQUEST['sortField'];
			SaveDefaultSortField('ManageVendorPayments', $_REQUEST['sortField'], $sortOrder);
		}
		else {
			list($sortField, $sortOrder) = GetDefaultSortField('ManageVendorPayments', 'paymentid', $sortOrder);
		}

		if (isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		} else {
			$page = 1;
		}

		// Build the pagination and sort URL
		$searchURL = '';
		foreach($_GET as $k => $v) {
			if($k == "sortField" || $k == "sortOrder" || $k == "page" || $k == "new" || $k == "ToDo" || !$v) {
				continue;
			}
			$searchURL .= '&'.$k.'='.urlencode($v);
		}
		$sortURL = $searchURL.'&sortField='.$sortField.'&sortOrder='.$sortOrder;
		$GLOBALS['SortURL'] = $sortURL;

		// Limit the number of payments returned
		if($page == 1) {
			$start = 0;
		}
		else {
			$start = ($page-1) * ISC_VENDOR_PAYMENTS_PER_PAGE;
		}

		// Grab the queries we'll be executing
		$paymentQueries = $this->BuildVendorPaymentSearchQuery($start, $sortField, $sortOrder);

		// How many results do we have?
		$numPayments = $GLOBALS['ISC_CLASS_DB']->FetchOne($paymentQueries['countQuery']);
		$numPages = ceil($numPayments / ISC_VENDOR_PAYMENTS_PER_PAGE);

		// Add the "(Page x of y)" label
		if($numPayments > ISC_VENDOR_PAYMENTS_PER_PAGE) {
			$GLOBALS['Nav'] = '('.GetLang('Page').' '.$page.' '.GetLang('Of').' '.$numPages.')&nbsp;&nbsp;&nbsp;';
			$GLOBALS['Nav'] .= BuildPagination($numPayments, ISC_VENDOR_PAYMENTS_PER_PAGE, $page, 'index.php?ToDo=viewVendorPayments'.$sortURL);
		}
		else {
			$GLOBALS['Nav'] = '';
		}

		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;
		$sortLinks = array(
			'Id' => 'paymentid',
			'Date' => 'paymentfrom',
			'Vendor' => 'vendorname',
			'Amount' => 'paymentamount',
			'Method' => 'paymentmethod',
			'PaymentDate' => 'paymentdate'
		);
		BuildAdminSortingLinks($sortLinks, 'index.php?ToDo=viewVendorPayments&amp;'.$searchURL.'&amp;page='.$page, $sortField, $sortOrder);

		// Display the payments
		$result = $GLOBALS['ISC_CLASS_DB']->Query($paymentQueries['query']);
		while($payment = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['PaymentId'] = $payment['paymentid'];
			$GLOBALS['PaymentAmount'] = FormatPrice($payment['paymentamount']);
			$GLOBALS['PaymentMethod'] = isc_html_escape($payment['paymentmethod']);
			$GLOBALS['PaymentDate'] = CDate($payment['paymentdate']);
			$GLOBALS['PaymentFrom'] = CDate($payment['paymentfrom']);
			$GLOBALS['PaymentTo'] = CDate($payment['paymentto']);
			$GLOBALS['PaymentComments'] = nl2br(isc_html_escape($payment['paymentcomments']));
			$GLOBALS['Vendor'] = isc_html_escape($payment['vendorname']);
			if(!$GLOBALS['PaymentComments']) {
				$GLOBALS['HideExpandLink'] = 'display: none';
			}
			else {
				$GLOBALS['HideExpandLink'] = '';
			}

			$paymentGrid .= $this->template->render('vendorpayments.manage.row.tpl');
		}

		if(!$paymentGrid) {
			return '';
		}

		$GLOBALS['PaymentGrid'] = $paymentGrid;
		return $this->template->render('vendorpayments.manage.grid.tpl');
	}

	/**
	 * Build a list of all of the vendors in the store.
	 *
	 * @param int The ID of the vendor to select in the list, if there is one.
	 * @return string The generated <option> tags for the list of vendors.
	 */
	private function BuildVendorList($selectedVendor=0)
	{
		$vendorList = '';
		$query = "
			SELECT vendorid, vendorname
			FROM [|PREFIX|]vendors
			ORDER BY vendorname
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$sel = '';
			if($vendor['vendorid'] == $selectedVendor) {
				$sel = 'selected="selected"';
			}

			$vendorList .= '<option value="'.$vendor['vendorid'].'" '.$sel.'>'.isc_html_escape($vendor['vendorname']).'</option>';
		}

		return $vendorList;
	}

	/**
	 * Calculate information about a specific vendor's outstanding balance & last payment.
	 *
	 * @param int The vendor ID to generate the information for.
	 * @return array Array containing the total amount of orders, forward balance, last payment date and the outstanding balance.
	 */
	public function CalculateOutstandingVendorBalance($vendorId)
	{
		// Grab the date of the last payment sent to the vendor and the balance owing at the time
		$query = "
			SELECT paymentdate, paymentforwardbalance, vendorprofitmargin
			FROM [|PREFIX|]vendors
			LEFT JOIN [|PREFIX|]vendor_payments ON (paymentvendorid=vendorid AND paymentdeducted='1')
			WHERE vendorid='".($vendorId)."'
			ORDER BY paymentdate DESC
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 1);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$vendorPaymentDetails = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if(!$vendorPaymentDetails['paymentdate']) {
			$vendorPaymentDetails['paymentdate'] = 0;
			$vendorPaymentDetails['paymentforwardbalance'] = 0;

			// Try and grab the date of the first order for this vendor
			$query = "
				SELECT orddate
				FROM [|PREFIX|]orders
				WHERE ordvendorid='".(int)$vendorId."'
				ORDER BY orddate ASC
				LIMIT 1
			";
			$vendorPaymentDetails['paymentdate'] = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		}

		if(!$vendorPaymentDetails['paymentdate']) {
			$vendorPaymentDetails['paymentdate'] = time();
		}

		// Grab the total amount of orders since the last payment
		$query = "
			SELECT SUM(total_inc_tax)
			FROM [|PREFIX|]orders
			WHERE ordvendorid='".(int)$vendorId."' AND orddate >= '".(int)$vendorPaymentDetails['paymentdate']."'
				AND ordstatus IN (".implode(',', GetPaidOrderStatusArray()).")
		";
		$GLOBALS['ISC_CLASS_DB']->Query($query);
		$totalOrders = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);

		$profitMargin = 0;
		if($vendorPaymentDetails['vendorprofitmargin'] > 0) {
			$profitMargin = ($totalOrders/100)*$vendorPaymentDetails['vendorprofitmargin'];
		}

		// check if the vendor has issued any store credit for a return, we need to deduct that from the total
		$query = "
			SELECT
				cc.creditamount
			FROM
				[|PREFIX|]returns r
				LEFT JOIN [|PREFIX|]customer_credits cc ON cc.creditrefid = r.returnid
			WHERE
				cc.credittype = 'return' AND
				r.retreceivedcredit = 1 AND
				r.retvendorid = '" . (int)$vendorId . "' AND
				cc.creditdate >= '" . (int)$vendorPaymentDetails['paymentdate'] . "'
		";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$issuedCredit = 0;
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$issuedCredit += $row['creditamount'];
		}

		$summary = array(
			'totalOrders' => $totalOrders,
			'balanceForward' => $vendorPaymentDetails['paymentforwardbalance'],
			'issuedCredit' => $issuedCredit,
			'lastPaymentDate' => $vendorPaymentDetails['paymentdate'],
			'outstandingBalance' => ($totalOrders-$profitMargin) + $vendorPaymentDetails['paymentforwardbalance'] - $issuedCredit,
			'profitMargin' => $profitMargin,
			'profitMarginPercentage' => number_format($vendorPaymentDetails['vendorprofitmargin'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), '')
		);
		return $summary;
	}

	/**
	 * Generate the database select and count queries for building the list of vendors.
	 *
	 * @param int The starting position to add to the query LIMIT.
	 * @param string The field to sort by.
	 * @param string The direction to sort in.
	 * @param boolean True to add the LIMIT clause to the select query, false to not.
	 * @return array Array containing the select & count queries.
	 */
	private function BuildVendorPaymentSearchQuery($start, $sortField, $sortOrder, $addLimit=true)
	{
		$query = "
			SELECT p.*, v.vendorname
			FROM [|PREFIX|]vendor_payments p
			INNER JOIN [|PREFIX|]vendors v ON (v.vendorid=p.paymentvendorid)
		";

		$countQuery = "
			SELECT COUNT(paymentid)
			FROM [|PREFIX|]vendor_payments
		";

		// Let's add in any search arguments
		$queryWhere = '';

		if(isset($_REQUEST['vendorId']) && $_REQUEST['vendorId'] != 0) {
			$queryWhere .= " AND paymentvendorid='".(int)$_REQUEST['vendorId']."'";
		}

		// Construct the actual query
		$query .= " WHERE 1=1 ".$queryWhere;
		$countQuery .= " WHERE 1=1 ".$queryWhere;

		$query .= " ORDER BY ".$sortField." ".$sortOrder;
		if($addLimit) {
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_VENDOR_PAYMENTS_PER_PAGE);
		}

		// Return or generated queries
		return array(
			'query' => $query,
			'countQuery' => $countQuery
		);
	}
}
