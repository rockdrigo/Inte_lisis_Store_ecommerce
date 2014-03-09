<?php
class ISC_ADMIN_RETURNS extends ISC_ADMIN_BASE
{
	public $_customSearch = array();

	public function __construct()
	{
		parent::__construct();
		// Initialise custom searches functionality
		require_once(dirname(__FILE__).'/class.customsearch.php');
		$GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH'] = new ISC_ADMIN_CUSTOMSEARCH('returns');
	}

	public function HandleToDo($Do)
	{
		if(!gzte11(ISC_LARGEPRINT)) {
			exit;
		}
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('returns');
		switch (isc_strtolower($Do))
		{
			case "createreturnview":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS['ISC_LANG']['Home'] => "index.php",
						$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns",
						$GLOBALS['ISC_LANG']['CreateReturnView'] => "index.php?ToDo=createReturnView"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->CreateView();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "deletecustomreturnsearch":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS['ISC_LANG']['Home'] => "index.php",
						$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->DeleteCustomSearch();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "customreturnsearch":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {
					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS['ISC_LANG']['Home'] => "index.php",
						$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns",
						$GLOBALS['ISC_LANG']['CustomView'] => "index.php?ToDo=customReturnSearch"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->CustomSearch();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "issuereturncredit":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {
					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS['ISC_LANG']['Home'] => "index.php",
						$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->IssueReturnCredit();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "searchreturnsredirect":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS['ISC_LANG']['Home'] => "index.php",
						$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns",
						$GLOBALS['ISC_LANG']['SearchResults'] => "index.php?ToDo=searchReturns"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SearchReturnsRedirect();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "searchreturns":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS['ISC_LANG']['Home'] => "index.php",
						$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns",
						$GLOBALS['ISC_LANG']['SearchReturns'] => "index.php?ToDo=searchReturns"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SearchReturns();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "deletereturns":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {

					$GLOBALS['BreadcrumEntries'] = array(
						$GLOBALS['ISC_LANG']['Home'] => "index.php",
						$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns"
					);

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->DeleteReturns();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			default:
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {

					if(isset($_GET['searchQuery'])) {
						$GLOBALS['BreadcrumEntries'] = array(
							$GLOBALS['ISC_LANG']['Home'] => "index.php",
							$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns",
							$GLOBALS['ISC_LANG']['SearchResults'] => "index.php?ToDo=viewReturns"
						);
					}
					else {
						$GLOBALS['BreadcrumEntries'] = array(
							$GLOBALS['ISC_LANG']['Home'] => "index.php",
							$GLOBALS['ISC_LANG']['Returns'] => "index.php?ToDo=viewReturns"
						);
					}

					if(!isset($_REQUEST['ajax'])) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					}
					$this->ManageReturns();
					if(!isset($_REQUEST['ajax'])) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					}
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
		}
	}

	private function ManageReturnsGrid(&$numReturns)
	{
		// Show a list of products in a table
		$page = 0;
		$start = 0;
		$numPages = 0;
		$GLOBALS['ReturnGrid'] = "";
		$GLOBALS['Nav'] = "";
		$catList = "";
		$max = 0;

		// Is this a custom search?
		if(isset($_GET['searchId'])) {
			$this->_customSearch = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($_GET['searchId']);
			$_REQUEST = array_merge($_REQUEST, (array)$this->_customSearch['searchvars']);

			// Override custom search sort fields if we have a requested field
			if(isset($_GET['sortField'])) {
				$_REQUEST['sortField'] = $_GET['sortField'];
			}
			if(isset($_GET['sortOrder'])) {
				$_REQUEST['sortOrder'] = $_GET['sortOrder'];
			}
		}
		else if(isset($_GET['searchQuery'])) {
			$GLOBALS['Query'] = $_GET['searchQuery'];
		}

		if(isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == "asc") {
			$sortOrder = "asc";
		}
		else {
			$sortOrder = "desc";
		}

		$validSortFields = array('returnid', 'retprodname', 'custname', 'retorderid', 'retdaterequested', 'retstatus');
		if(isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
			$sortField = $_REQUEST['sortField'];
			SaveDefaultSortField("ManageReturns", $_REQUEST['sortField'], $sortOrder);
		}
		else {
			list($sortField, $sortOrder) = GetDefaultSortField("ManageReturns", "returnid", $sortOrder);
		}

		if(isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		} else {
			$page = 1;
		}

		// Build the pagination and sort URL
		$searchURL = '';
		foreach($_GET as $k => $v) {
			if($k == "sortField" || $k == "sortOrder" || $k == "page" || $k == "new" || $k == "ToDo" || $k == "SubmitButton1" || !$v) {
				continue;
			}
			$searchURL .= sprintf("&%s=%s", $k, urlencode($v));
		}

		$sortURL = sprintf("%s&amp;sortField=%s&amp;sortOrder=%s", $searchURL, $sortField, $sortOrder);

		$GLOBALS['SortURL'] = $sortURL;

		// Limit the number of returns returned
		if ($page == 1) {
			$start = 1;
		} else {
			$start = ($page * ISC_RETURNS_PER_PAGE) - (ISC_RETURNS_PER_PAGE-1);
		}

		$start = $start-1;

		// Get the results for the query
		$returnResult = $this->_GetReturnsList($start, $sortField, $sortOrder, $numReturns);

		$numPages = ceil($numReturns / ISC_RETURNS_PER_PAGE);

		// Add the "(Page x of n)" label
		if($numReturns > ISC_RETURNS_PER_PAGE) {
			$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
			$GLOBALS['Nav'] .= BuildPagination($numReturns, ISC_RETURNS_PER_PAGE, $page, sprintf("index.php?ToDo=viewReturns%s", $sortURL));
		}
		else {
			$GLOBALS['Nav'] = "";
		}

		if(isset($_GET['searchQuery'])) {
			$query = $_GET['searchQuery'];
		} else {
			$query = "";
		}

		$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');
		$GLOBALS['SearchQuery'] = $query;
		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;

		$sortLinks = array(
			"Id" => "returnid",
			"ReturnItem" => "retprodname",
			"Order" => "retorderid",
			"Cust" => "custname",
			"Date" => "retdaterequested",
			"Status" => "retstatus"
		);
		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewReturns&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);

		$GLOBALS['ReturnStatusList'] = $this->GetReturnStatusOptions();

		// Display the returns
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($returnResult)) {
			$GLOBALS['ReturnId'] = $row['returnid'];
			$GLOBALS['OrderId'] = $row['retorderid'];

			$GLOBALS['ProductId'] = $row['retprodid'];
			$GLOBALS['ProductLink'] = ProdLink($row['retprodname'], $row['retprodname']);
			$GLOBALS['ProdName'] = isc_html_escape($row['retprodname']);

			$GLOBALS['ReturnedProductOptions'] = '';
			if($row['retprodoptions'] != '') {
				$options = @unserialize($row['retprodoptions']);
				if(!empty($options)) {
					$GLOBALS['ReturnedProductOptions'] = "<div style=\"margin-top: 3px; padding-left: 10px;\">(";
					$comma = '';
					foreach($options as $name => $value) {
						$GLOBALS['ReturnedProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
						$comma = ', ';
					}
					$GLOBALS['ReturnedProductOptions'] .= ")</div>";
				}
			}


			$GLOBALS['ReturnQty'] = (int)$row['retprodqty'] . " x ";

			$returnAmount = $row['retprodcost'] * $row['retprodqty'];

			$GLOBALS['AmountPaid'] = FormatPrice($returnAmount);

			$GLOBALS['CustomerId'] = (int)$row['retcustomerid'];
			$GLOBALS['Customer'] = isc_html_escape($row['custname']);
			$GLOBALS['Date'] = isc_date(GetConfig('DisplayDateFormat'), $row['retdaterequested']);

			$GLOBALS['ReturnStatus'] = (int)$row['retstatus'];
			$GLOBALS['ReturnStatusDisabled'] = '';
			if($row['retstatus'] == 5) {
				//$GLOBALS['ReturnStatusDisabled'] = 'disabled="disabled"';
			}

			$GLOBALS['ReturnStatusOptions'] = $this->GetReturnStatusOptions($row['retstatus']);

			$GLOBALS['IssueCreditLink'] = '';
			if(GetConfig('ReturnCredits') && !$row['retreceivedcredit']) {
				$GLOBALS['IssueCreditLink'] = sprintf("<a href='index.php?ToDo=issueReturnCredit&amp;returnId=%d' class='Action' onclick='return ConfirmIssueCredit(\"%s\");'>%s</a>", $row['returnid'], $GLOBALS['AmountPaid'], GetLang('ReturnIssueCredit'));
			}

			// local context for this render
			$context = array(
				'return' => $row,
			);

			$GLOBALS['ReturnGrid'] .= $this->template->render('returns.manage.row.tpl', $context);
		}
		return $this->template->render('returns.manage.grid.tpl');
	}

	private function IssueReturnCredit()
	{
		if(!GetConfig('ReturnCredits')) {
			$this->ManageReturns(GetLang('Unauthorized'), MSG_ERROR);
		}

		// Fetch the return
		$query = "
			SELECT r.*, o.ordcurrencyid, o.ordcurrencyexchangerate
			FROM [|PREFIX|]returns r
			LEFT JOIN [|PREFIX|]orders o ON (r.retorderid=o.orderid)
			WHERE r.returnid='".(int)$_REQUEST['returnId']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$return = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if(!$return['returnid']) {
			$this->ManageReturns(GetLang('InvalidReturn'), MSG_ERROR);
			return;
		}

		// Grab the order if it still exists to provide a refund on the tax as well
		$order = GetOrder($return['retorderid'], null, null, true);
		if (!$order) {
			return false;
		}

		// Does the current user have permission to view this return?
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $order['ordvendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
			FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewReturns');
		}

		if($return['retreceivedcredit']) {
			$this->ManageReturns(GetLang('InvalidReturn'), MSG_ERROR);
			return;
		}

		// If the review status is not already "Refunded", then we need to also process the refund.
		if($return['retstatus'] != 5) {
			if(!$this->UpdateReturnStatus($return, 5, true)) {
				$this->ManageReturns(GetLang('FailedToUpdateReturn'), MSG_ERROR);
				return;
			}
		}

		$GLOBALS['ISC_CLASS_DB']->Query("START TRANSACTION");

		$additionalCredit = $return['retprodcost']*$return['retprodqty'];

		// Issue credit to the customer
		$customer = GetCustomer($return['retcustomerid']);
		$updatedCustomer = array(
			"custstorecredit" => $customer['custstorecredit'] + $additionalCredit
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("customers", $updatedCustomer, "customerid='".$GLOBALS['ISC_CLASS_DB']->Quote($return['retcustomerid'])."'");

		// Log the credit in to the database
		$creditLog = array(
			"customerid" => $return['retcustomerid'],
			"creditamount" => $additionalCredit,
			"credittype" => "return",
			"creditdate" => time(),
			"creditrefid" => $return['returnid'],
			"credituserid" => $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUserId(),
			"creditreason" => $return['retreason']
		);
		$GLOBALS['ISC_CLASS_DB']->InsertQuery("customer_credits", $creditLog);

		// Update the return to mark it as credit received
		$updatedReturn = array(
			"retreceivedcredit" => 1
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("returns", $updatedReturn, "returnid='".$GLOBALS['ISC_CLASS_DB']->Quote($return['returnid'])."'");

		// Fetch the customers name
		$query = sprintf("SELECT CONCAT(custconfirstname, ' ', custconlastname) FROM [|PREFIX|]customers WHERE customerid='%d'", $return['retcustomerid']);
		$custName = $GLOBALS['ISC_CLASS_DB']->FetchOne($GLOBALS['ISC_CLASS_DB']->Query($query));

		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg() == "") {
			$GLOBALS['ISC_CLASS_DB']->Query("COMMIT");
			$GLOBALS['ISC_LANG']['ReturnCreditIssued'] = sprintf(GetLang('ReturnCreditIssued'), $custName, FormatPrice($additionalCredit));
			$this->ManageReturns(GetLang('ReturnCreditIssued'), MSG_SUCCESS);
		}
		else {
			$GLOBALS['ISC_LANG']['FailedIssueReturnCredit'] = sprintf(GetLang('FailedIssueReturnCredit'), $custName, FormatPrice($additionalCredit));
			$this->ManageReturns(GetLang('FailedIssueReturnCredit'), MSG_ERROR);
		}
	}

	public function UpdateReturnStatus(&$return, $status, $crediting = false)
	{

		// Start a transaction
		$GLOBALS['ISC_CLASS_DB']->Query("START TRANSACTION");

		// Changing the status of this return to "Refunded", so we need to perform some additional things
		if($status == 5 && $return['retstatus'] != 5) {
			$refundAmount = $return['retprodcost'] * $return['retprodqty'];
			$updatedProduct = array(
				"ordprodrefundamount" => $return['retprodcost'],
				"ordprodrefunded" => $return['retprodqty'],
				"ordprodreturnid" => $return['returnid']
			);

			$order = getOrder($return['retorderid']);
			if (!$order) {
				return false;
			}

			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("order_products", $updatedProduct, "orderprodid='".$GLOBALS['ISC_CLASS_DB']->Quote($return['retordprodid'])."'");

			$query = "
				UPDATE [|PREFIX|]orders
				SET ordrefundedamount = ordrefundedamount + ".$refundAmount."
				WHERE orderid='".$return['retorderid']."'
			";
			$this->db->query($query);

			// Have all items in this order been refunded? Mark the order as refunded.
			$query = sprintf("SELECT SUM(ordprodqty-ordprodrefunded) FROM [|PREFIX|]order_products WHERE orderorderid=%d", $return['retorderid']);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$remainingItems = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
			if($remainingItems == 0) {
				$updatedOrder = array(
					'ordstatus' => 4
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid='".$GLOBALS['ISC_CLASS_DB']->Quote($return['retorderid'])."'");
			}

			// Update the status of this return
			$updatedReturn = array(
				"retstatus" => 5,
				"retuserid" => $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUserId()
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("returns", $updatedReturn, "returnid='".$GLOBALS['ISC_CLASS_DB']->Quote($return['returnid'])."'");

			// Update the product inventory for this returned item
			$query = sprintf("SELECT * FROM [|PREFIX|]order_products WHERE ordprodid='%d'", $return['retordprodid']);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			UpdateInventoryOnReturn($return['retordprodid']);

			// dont send a refund through the checkout module if a store credit was issued
			if (!$crediting) {
				// If the checkout module that was used for an order is still enabled and has a function
				// to handle a status change, then call that function
				$valid_checkout_modules = GetAvailableModules('checkout', true, true);
				$valid_checkout_module_ids = array();
				foreach ($valid_checkout_modules as $valid_module) {
					$valid_checkout_module_ids[] = $valid_module['id'];
				}

				$newStatus = $order['ordstatus'];
				if (isset($updatedOrder['ordstatus'])) {
					$newStatus = $updatedOrder['ordstatus'];
				}

				// attempt to refund this amount with the checkout provider
				$order = GetOrder($return['retorderid'], false);
				if (in_array($order['orderpaymentmodule'], $valid_checkout_module_ids)) {
					GetModuleById('checkout', $checkout_module, $order['orderpaymentmodule']);
					if (method_exists($checkout_module, 'HandleStatusChange')) {
						call_user_func(array($checkout_module, 'HandleStatusChange'), $return['retorderid'], $order['ordstatus'], $newStatus, $refundAmount);
					}
				}
			}
		}
		else {
			// Update the status of this return
			$updatedReturn = array(
				"retstatus" => $status
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("returns", $updatedReturn, "returnid='".$GLOBALS['ISC_CLASS_DB']->Quote($return['returnid'])."'");
		}

		$return['retstatus'] = $status;

		if(GetConfig('NotifyOnReturnStatusChange') == 1) {
			$this->EmailReturnStatusChange($return);
		}

		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg() == "") {
			$GLOBALS['ISC_CLASS_DB']->Query("COMMIT");
			return true;
		}
		else {
			return false;
		}
	}

	private function EmailReturnStatusChange($return)
	{
		// Get the customer's details
		$query = sprintf("SELECT custconfirstname, custconlastname, custconemail FROM [|PREFIX|]customers WHERE customerid='%d'", $return['retcustomerid']);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$customer = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		$GLOBALS['ReturnId'] = $return['returnid'];

		$GLOBALS['CustomerFirstName'] = $customer['custconfirstname'];
		$GLOBALS['CustomerName'] = $customer['custconfirstname'] . " " . $customer['custconlastname'];
		$GLOBALS['CustomerEmail'] = $customer['custconemail'];

		$GLOBALS['ProductQuantity'] = $return['retprodqty'];
		$GLOBALS['ProductName'] = $return['retprodname'];

		$GLOBALS['ReturnStatus'] = $this->_FetchReturnStatus($return['retstatus']);

		$GLOBALS['SNIPPETS']['Products'] = '';
		if($return['retstatus'] == 5) {

			// Manually convert the cost to the exchage rate when it was bought
			$GLOBALS['StoreCreditAmount'] = CurrencyConvertFormatPrice($return['retprodcost'] * $return['retprodqty'], $return['ordcurrencyid'], $return['ordcurrencyexchangerate']);
			$GLOBALS['ReturnReceivedCredit'] = sprintf(GetLang('RefundAccountCredited'), $GLOBALS['StoreCreditAmount']);
		}
		else if($return['retstatus'] == 3) {
			$instructions = nl2br($GLOBALS['ReturnInstructions']);
			if($instructions) {
				$GLOBALS['ReturnInstructions'] = '<p><strong>'.GetLang('ReturnInstructions').':</strong><br />'.$instructions.'</p>';
			}
		}

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("return_statuschange_email");
		$message = $emailTemplate->ParseTemplate(true);

		// Create a new email API object to send the email
		$store_name = str_replace("&#39;", "'", GetConfig('StoreName'));

		require_once(ISC_BASE_PATH . "/lib/email.php");
		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), $store_name);
		$obj_email->Set("Subject", sprintf(GetLang('ReturnStatusUpdate'), $store_name));
		$obj_email->AddBody("html", $message);
		$obj_email->AddRecipient($customer['custconemail'], "", "h");
		$email_result = $obj_email->Send();

		// If the email was sent ok, show a confirmation message
		if($email_result['success']) {
			return true;
		}
		else {
			// Email error
			return false;
		}
	}

	public function SendReturnConfirmation($return, $items)
	{

		// Get the customer's details
		$query = sprintf("SELECT custconfirstname, custconlastname, custconemail FROM [|PREFIX|]customers WHERE customerid='%d'", $return['retcustomerid']);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$customer = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		$emailTemplate = FetchEmailTemplateParser();

		$GLOBALS['ReturnId'] = $return['returnid'];

		$GLOBALS['CustomerFirstName'] = $customer['custconfirstname'];
		$GLOBALS['CustomerName'] = $customer['custconfirstname'] . " " . $customer['custconlastname'];
		$GLOBALS['CustomerEmail'] = $customer['custconemail'];

		$GLOBALS['SNIPPETS']['ReturnItems'] = '';
		foreach($items as $product) {
			$GLOBALS['ProductName'] = $product['retprodname'];
			$GLOBALS['ProductId'] = $product['retprodid'];
			$GLOBALS['ProductQty'] = $product['retprodqty'];
			$GLOBALS['SNIPPETS']['ReturnItems'] .= $emailTemplate->GetSnippet("ReturnConfirmationItem");
		}

		$GLOBALS['ReturnReason'] = $return['retreason'];
		if(!$GLOBALS['ReturnReason']) {
			$GLOBALS['ReturnReason'] = GetLang('NA');
		}

		$GLOBALS['ReturnAction'] = $return['retaction'];
		if(!$GLOBALS['ReturnAction']) {
			$GLOBALS['ReturnAction'] = GetLang('NA');
		}
		$GLOBALS['ReturnStatus'] = $this->_FetchReturnStatus($return['retstatus']);

		$GLOBALS['ReturnInstructions'] = nl2br($GLOBALS['ReturnInstructions']);
		if(!$GLOBALS['ReturnInstructions']) {
			$GLOBALS['ReturnInstructions'] = '';
		}

		$GLOBALS['ReturnComments'] = nl2br($return['retcomment']);

		$emailTemplate->SetTemplate("return_confirmation_email");
		$message = $emailTemplate->ParseTemplate(true);

		// Create a new email API object to send the email
		$store_name = str_replace("&#39;", "'", GetConfig('StoreName'));

		require_once(ISC_BASE_PATH . "/lib/email.php");
		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), $store_name);
		$obj_email->Set("Subject", sprintf(GetLang('NotificationYourReturnOn'), $store_name));
		$obj_email->AddBody("html", $message);
		$obj_email->AddRecipient($GLOBALS['CustomerEmail'], "", "h");
		$email_result = $obj_email->Send();

		// If the email was sent ok, show a confirmation message
		if($email_result['success']) {
			return true;
		}
		else {
			// Email error
			return false;
		}
	}

	public function SendNewReturnNotification($return, $items)
	{
		// Get the customer's details
		$query = sprintf("SELECT custconfirstname, custconlastname, custconemail FROM [|PREFIX|]customers WHERE customerid='%d'", $return['retcustomerid']);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$customer = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		$GLOBALS['ReturnId'] = $return['returnid'];

		$GLOBALS['CustomerFirstName'] = $customer['custconfirstname'];
		$GLOBALS['CustomerName'] = $customer['custconfirstname'] . " " . $customer['custconlastname'];
		$GLOBALS['CustomerEmail'] = $customer['custconemail'];

		$emailTemplate = FetchEmailTemplateParser();

		$GLOBALS['SNIPPETS']['ReturnItems'] = '';
		foreach($items as $product) {
			$GLOBALS['ProductName'] = $product['retprodname'];
			$GLOBALS['ProductId'] = $product['retprodid'];
			$GLOBALS['ProductQty'] = $product['retprodqty'];
			$GLOBALS['SNIPPETS']['ReturnItems'] .= $emailTemplate->GetSnippet("ReturnConfirmationItem");
		}

		$GLOBALS['ReturnReason'] = $return['retreason'];
		if(!$GLOBALS['ReturnReason']) {
			$GLOBALS['ReturnReason'] = GetLang('NA');
		}

		$GLOBALS['ReturnAction'] = $return['retaction'];
		if(!$GLOBALS['ReturnAction']) {
			$GLOBALS['ReturnAction'] = GetLang('NA');
		}
		$GLOBALS['ReturnStatus'] = $this->_FetchReturnStatus($return['retstatus']);

		$GLOBALS['ReturnComments'] = nl2br($return['retcomment']);

		$emailTemplate->SetTemplate("return_notification_email");
		$message = $emailTemplate->ParseTemplate(true);

		// Create a new email API object to send the email
		$store_name = str_replace("&#39;", "'", GetConfig('StoreName'));

		require_once(ISC_BASE_PATH . "/lib/email.php");
		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), $store_name);
		$obj_email->Set("Subject", sprintf(GetLang('NotificationNewReturnRequestOn'), $store_name));
		$obj_email->AddBody("html", $message);

		if ($return['retvendorid']) {
			$query = "SELECT vendoremail FROM [|PREFIX|]vendors WHERE vendorid = " . $return['retvendorid'];
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$recipient = $vendor['vendoremail'];
			}
			else {
				return false;
			}
		}
		else {
			$recipient = GetConfig('OrderEmail');
		}

		$obj_email->AddRecipient($recipient, "", "h");
		$email_result = $obj_email->Send();

		// If the email was sent ok, show a confirmation message
		if($email_result['success']) {
			return true;
		}
		else {
			// Email error
			return false;
		}
	}

	private function _GetReturnById($returnId)
	{
		static $returnCache;
		if(isset($returnCache[$returnId])) {
			return $returnCache[$returnId];
		}
		else {
			$query = sprintf("SELECT * FROM [|PREFIX|]returns WHERE returnid='%d'", $returnId);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$returnCache[$returnId] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			return $returnCache[$returnId];
		}
	}

	public function ManageReturns($MsgDesc = "", $MsgStatus = "")
	{
		$GLOBALS['HideClearResults'] = "none";
		$status = array();
		$num_custom_searches = 0;

		// Fetch any results, place them in the data grid
		$numReturns = 0;
		$GLOBALS['ReturnsDataGrid'] = $this->ManageReturnsGrid($numReturns);

		// Was this an ajax based sort? Return the table now
		if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
			echo $GLOBALS['ReturnsDataGrid'];
			return;
		}

		if(isset($this->_customSearch['searchname'])) {
			$GLOBALS['ViewName'] = $this->_customSearch['searchname'];
		}
		else {
			$GLOBALS['ViewName'] = GetLang('AllReturns');
			$GLOBALS['HideDeleteViewLink'] = "none";
		}

		// Do we need to disable the delete button?
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns) || $numReturns == 0) {
			$GLOBALS['DisableDelete'] = "DISABLED";
		}

		if(isset($_REQUEST['searchQuery']) || isset($_GET['searchId'])) {
			$GLOBALS['HideClearResults'] = "";
		}

		if($numReturns > 0) {
			if($MsgDesc == "" && (isset($_REQUEST['searchQuery']) || isset($_GET['searchId']))) {
				if($numReturns == 1) {
					$MsgDesc = GetLang('ReturnSearchResultsBelow1');
				}
				else {
					$MsgDesc = sprintf(GetLang('ReturnSearchResultsBelowX'), $numReturns);
				}

				$MsgStatus = MSG_SUCCESS;
			}
		}

		// Get the custom search as option fields
		$GLOBALS['CustomSearchOptions'] = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->GetSearchesAsOptions(@$_GET['searchId'], $num_custom_searches, "AllReturns", "viewReturns", "customReturnSearch");

		if(!isset($_REQUEST['searchId'])) {
			$GLOBALS['HideDeleteCustomSearch'] = "none";
		} else {
			$GLOBALS['CustomSearchId'] = (int)$_REQUEST['searchId'];
		}

		// No returns
		if($numReturns == 0) {
			$GLOBALS['DisplayGrid'] = "none";

			// Performing a search of some kind
			if(count($_GET) > 1) {
				if ($MsgDesc == "") {
					$GLOBALS['Message'] = MessageBox(GetLang('NoReturnResults'), MSG_ERROR);
				}
			} else {
				$GLOBALS['Message'] = MessageBox(GetLang('NoReturns'), MSG_SUCCESS);
				$GLOBALS['DisplaySearch'] = "none";
			}
		}

		if($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		$this->template->display('returns.manage.tpl');

	}

	private function _GetReturnsList($Start, $SortField, $SortOrder, &$NumReturns)
	{
		// ISC-1141 orders are on a LEFT JOIN here so returns attached to orders marked as deleted should probably stay
		// visible for now to keep the existing behaviour

		$query = "
			SELECT
				r.*,
				o.orderid as order_orderid,
				o.deleted as order_deleted,
				CONCAT(c.custconfirstname, ' ', c.custconlastname) AS custname
			FROM
				[|PREFIX|]returns r
				LEFT JOIN [|PREFIX|]customers c ON r.retcustomerid = c.customerid
				LEFT JOIN [|PREFIX|]orders o ON o.orderid = r.retorderid
		";

		$countQuery = "SELECT COUNT(*) FROM [|PREFIX|]returns r";

		$queryWhere = ' WHERE 1=1 ';

		if(isset($_REQUEST['searchQuery']) && $_REQUEST['searchQuery'] != "") {
			$search_query = $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['searchQuery']);
			$queryWhere .= " AND (returnid='".$search_query."' OR ";
			$queryWhere .= " CONCAT(custconfirstname, ' ', custconlastname) LIKE '%".$search_query."%' OR";
			$queryWhere .= " retprodname LIKE '%".$search_query."%')";

			// If we're doing a search query, we need to add a join with the customers table to the count query
			$countQuery .= " LEFT JOIN [|PREFIX|]customers c ON (r.retcustomerid=c.customerid)";
		}


		if(isset($_REQUEST['orderId']) && $_REQUEST['orderId'] != "") {
			$order_id = (int)$_REQUEST['orderId'];
			$queryWhere .= sprintf(" AND retorderid='%d'", $order_id);
		}

		if(isset($_REQUEST['productId']) && $_REQUEST['productId'] != "") {
			$product_id = (int)$_REQUEST['productId'];
			$queryWhere .= sprintf(" AND retprodid='%d'", $order_id);
		}

		if(isset($_REQUEST['returnStatus']) && $_REQUEST['returnStatus'] != "") {
			// note: 0 means all status
			$return_status = (int)$_REQUEST['returnStatus'];
			if ($return_status != 0) {
				$queryWhere .= sprintf(" AND retstatus='%d'", $return_status);
			}
		}

		if(isset($_REQUEST['returnFrom']) && $_REQUEST['returnFrom'] != "") {
			$return_from = (int)$_REQUEST['returnFrom'];
			$queryWhere .= sprintf(" AND returnid >= '%d'", $return_from);
		}
		if(isset($_REQUEST['returnTo']) && $_REQUEST['returnTo'] != "") {
			$return_to = (int)$_REQUEST['returnTo'];
			$queryWhere .= sprintf(" AND returnid <= '%d'", $return_to);
		}

		// Limit results to a particular date range
		if(isset($_REQUEST['dateRange']) && $_REQUEST['dateRange'] != "") {
			$range = $_REQUEST['dateRange'];
			switch($range) {
				// Returns within the last day
				case "today":
					$from_stamp = mktime(0, 0, 0, isc_date("m"), isc_date("d"), isc_date("Y"));
					break;
				// Returns received in the last 2 days
				case "yesterday":
					$from_stamp = mktime(0, 0, 0, isc_date("m"), isc_date("d")-1, isc_date("Y"));
					$to_stamp = mktime(0, 0, 0, isc_date("m"), isc_date("d")-1, isc_date("Y"));
					break;
				// Returns received in the last 24 hours
				case "day":
					$from_stamp = time()-60*60*24;
					break;
				// Returns received in the last 7 days
				case "week":
					$from_stamp = time()-60*60*24*7;
					break;
				// Returns received in the last 30 days
				case "month":
					$from_stamp = time()-60*60*24*30;
					break;
				// Returns received this month
				case "this_month":
					$from_stamp = mktime(0, 0, 0, isc_date("m"), 1, isc_date("Y"));
					break;
				// Returns received this year
				case "this_year":
					$from_stamp = mktime(0, 0, 0, 1, 1, isc_date("Y"));
					break;
				// Custom date
				default:
					if(isset($_REQUEST['fromDate']) && $_REQUEST['fromDate'] != "") {
						$from_date = $_REQUEST['fromDate'];
						$from_data = explode("/", $from_date);
						$from_stamp = mktime(0, 0, 0, $from_data[0], $from_data[1], $from_data[2]);
					}
					if(isset($_REQUEST['toDate']) && $_REQUEST['toDate'] != "") {
						$to_date = $_REQUEST['toDate'];
						$to_data = explode("/", $to_date);
						$to_stamp = mktime(0, 0, 0, $to_data[0], $to_data[1], $to_data[2]);
					}
			}

			if(isset($from_stamp)) {
				$queryWhere .= sprintf(" AND retdaterequested >= '%d'", $from_stamp);
			}
			if(isset($to_stamp)) {
				$queryWhere .= sprintf(" AND retdaterequested <= '%d'", $to_stamp);
			}
		}

		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
			$queryWhere .= " AND retvendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
		}

		$query .= $queryWhere;
		$countQuery .= $queryWhere;

		$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
		$NumReturns = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		if($NumReturns > 0) {
			$query .= " ORDER BY ".$SortField." ".$SortOrder;

			// Add the limit
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($Start, ISC_RETURNS_PER_PAGE);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		}

		return $result;
	}

	private function DeleteReturns()
	{
		$queries = array();

		if(isset($_POST['returns'])) {
			$returnIds = implode(",", array_map("intval", $_POST['returns']));
			$queryWhere = '';
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				$queryWhere = " AND retvendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
			}
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('returns', "WHERE returnid IN (".$returnIds.")".$queryWhere);

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['returns']));
			$this->ManageReturns();
		} else {
			if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Returns)) {
				$this->ManageReturns();
			} else {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			}
		}
	}

	private function SearchReturns()
	{
		$GLOBALS['ReturnStatusOptions'] = $this->GetReturnStatusOptions();
		$this->template->display('returns.search.tpl');
	}

	/**
	*	This function checks to see if the user wants to save the search details as a custom search,
	*	and if they do one is created. They are then forwarded onto the search results
	*/
	private function SearchReturnsRedirect()
	{

		// Are we saving this as a custom search?
		if(isset($_GET['viewName']) && $_GET['viewName'] != '') {
			$search_id = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->SaveSearch($_GET['viewName'], $_GET);

			if($search_id > 0) {

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($search_id, $_GET['viewName']);

				ob_end_clean();
				header(sprintf("Location:index.php?ToDo=customReturnSearch&searchId=%d&new=true", $search_id));
				exit;
			}
			else {
				$this->ManageReturns(sprintf(GetLang('ViewAlreadExists'), $_GET['viewName']), MSG_ERROR);
			}
		}
		// Plain search
		else {
			$this->ManageReturns();
		}
	}

	/**
	*	Load a custom search
	*/
	private function CustomSearch()
	{

		$this->_customSearch = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($_GET['searchId']);
		$_REQUEST = array_merge($_REQUEST, (array)$this->_customSearch['searchvars']);

		if(isset($_REQUEST['new'])) {
			$this->ManageReturns(GetLang('CustomSearchSaved'), MSG_SUCCESS);
		} else {
			$this->ManageReturns();
		}
	}

	private function DeleteCustomSearch()
	{

		if($GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->DeleteSearch($_GET['searchId'])) {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_GET['searchId']);

			$this->ManageReturns(GetLang('DeleteCustomSearchSuccess'), MSG_SUCCESS);
		}
		else {
			$this->ManageReturns(GetLang('DeleteCustomSearchFailed'), MSG_ERROR);
		}
	}

	/**
	*	Create a view for returns. Uses the same form as searching but puts the
	*	name of the view at the top and it's mandatory instead of optional.
	*/
	private function CreateView()
	{
		$GLOBALS['ReturnStatusOptions'] = $this->GetReturnStatusOptions();
		$this->template->display('returns.view.tpl');
	}

	private function GetReturnStatusOptions($selected=0)
	{
		$returnStatuses = array(
			1 => "ReturnStatusPending",
			2 => "ReturnStatusReceived",
			3 => "ReturnStatusAuthorized",
			4 => "ReturnStatusRepaired",
			5 => "ReturnStatusRefunded",
			6 => "ReturnStatusRejected",
			7 => "ReturnStatusCancelled",
		);

		$statuses = '';
		foreach($returnStatuses as $id => $status) {
			$sel = '';
			if($id == $selected) {
				$sel = 'selected="selected"';
			}
			$statuses .= sprintf('<option value="%d" %s>%s</option>', $id, $sel, GetLang($status));
		}
		return $statuses;
	}

	private function _FetchReturnStatus($status)
	{
		$returnStatuses = array(
			1 => "ReturnStatusPending",
			2 => "ReturnStatusReceived",
			3 => "ReturnStatusAuthorized",
			4 => "ReturnStatusRepaired",
			5 => "ReturnStatusRefunded",
			6 => "ReturnStatusRejected",
			7 => "ReturnStatusCancelled",
		);
		return GetLang($returnStatuses[$status]);
	}
}
