<?php

	if (!defined('ISC_BASE_PATH')) {
		die();
	}

	class ISC_ADMIN_REMOTE_CUSTOMERS extends ISC_ADMIN_REMOTE_BASE
	{
		private $customerEntity;

		public function __construct()
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('customers');
			parent::__construct();

			$this->customerEntity = new ISC_ENTITY_CUSTOMER();
		}

		public function HandleToDo()
		{
			$what = isc_strtolower(@$_REQUEST['w']);

			switch ($what) {
				case "getselectedstates":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers) || $GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Customer)) {
						$this->getSelectedStates();
					}
					exit;
					break;

				case "checkemailuniqueness":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers) || $GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Customer)) {
						$this->checkEmailUniqueness();
					}
					exit;
					break;
				case 'viewcustomernotes':
					$this->ViewCustomerNotes();
					break;
				case 'savecustomernotes':
					$this->SaveCustomerNotes();
					break;
				case 'viewordernotes':
					$this->ViewOrderNotes();
					break;
				case 'saveordernotes':
					$this->SaveOrderNotes();
					break;
				case 'adddiscountrule':
					$this->addDiscountRule();
					break;
			}
		}

		private function SaveCustomerNotes()
		{
			if(!isset($_REQUEST['customerId']) || !$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {
				exit;
			}

			$customer = $this->customerEntity->get($_REQUEST["customerId"]);
			if(!isset($customer['customerid'])) {
				exit;
			}

			$updatedCustomer = array(
				"customerid" => $_REQUEST["customerId"],
				"custnotes" => urldecode($_REQUEST["custnotes"])
			);

			if ($this->customerEntity->edit($updatedCustomer) === false) {
				exit;
			}

			$message = sprintf(GetLang('CustomerNotesSuccessMsg'), isc_html_escape($customer['custconfirstname'].' '.$customer['custconlastname']));
			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('message', $message, true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function ViewCustomerNotes()
		{
			if(!isset($_REQUEST['customerId']) || ! isId($_REQUEST['customerId'])) {
				exit;
			}

			// Grab the notes
			$query = "
				SELECT custnotes
				FROM [|PREFIX|]customers
				WHERE customerid='".(int)$_REQUEST['customerId']."'
			";
			$GLOBALS['CustomerNotes'] = isc_html_escape($GLOBALS['ISC_CLASS_DB']->FetchOne($query));
			$GLOBALS['CustomerId'] = (int)$_REQUEST['customerId'];

			$this->template->display('customers.notes.popup.tpl');
		}

		private function SaveOrderNotes()
		{
			if (!isset($_REQUEST['orderId'])) {
				exit;
			}

			$order = GetOrder($_REQUEST['orderId']);
			if (!$order || !isset($order['orderid'])) {
				exit;
			}

			$updatedOrder = array(
				'ordnotes' => urldecode($_REQUEST['ordnotes'])
			);

			if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid='".(int)$_REQUEST['orderId']."'")) {
				exit;
			}

			$message = sprintf(GetLang('OrderNotesSuccessMsg'), $order['orderid']);
			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('message', $message, true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function ViewOrderNotes()
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('orders');
			if (!isset($_REQUEST['orderId']) || ! isId($_REQUEST['orderId'])) {
				exit;
			}

			// Load the order
			$order = GetOrder($_REQUEST['orderId']);
			if (!$order || ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $order['ordvendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId())) {
				exit;
			}

			$GLOBALS['OrderID'] = $order['orderid'];
			$GLOBALS['OrderNotes'] = isc_html_escape($order['ordnotes']);
			$GLOBALS['ThankYouID'] = 'CustomerStatus';

			$this->template->display('orders.notes.popup.tpl');
		}

		private function getSelectedStates()
		{
			if (!array_key_exists('countryId', $_POST) || !isId($_POST['countryId'])) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('ismultiple', 0);
				$tags[] = $this->MakeXMLTag('message', GetLang('CustomerAddressEditInvalid'));
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			$html = '';
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]country_states WHERE statecountry = " . (int)$_POST['countryId']);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$html .= '<option value="' . $row['stateid'] . '">' . isc_html_escape($row['statename']) . '</option>';
			}

			if ($html == '') {
				$ismultiple = false;
			} else {
				$html = '<option value="0">' . GetLang('ChooseCustState') . '</option>' . $html;
				$ismultiple = true;
			}

			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('ismultiple', $ismultiple);
			$tags[] = $this->MakeXMLTag('message', $html, true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
		}

		private function checkEmailUniqueness()
		{
			$email = trim(Interspire_Request::post('email'));

			if (!$email) {
				$tags[] = $this->MakeXMLTag('result', 0);
				$tags[] = $this->MakeXMLTag('message', MessageBox(GetLang('CustomerEmailUniqueCheckErrorMissing'), MSG_ERROR), true);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			if (!is_email_address($email)) {
				$tags[] = $this->MakeXMLTag('result', 0);
				$tags[] = $this->MakeXMLTag('message', MessageBox(GetLang('CustomerEmailInvalue'), MSG_ERROR), true);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			$query = "SELECT * FROM [|PREFIX|]customers WHERE custconemail = '" . $GLOBALS['ISC_CLASS_DB']->Quote($email) . "'";
			if (array_key_exists('customerId', $_POST) && isId($_POST['customerId'])) {
				$query .= " AND customerid != " . (int)$_POST['customerId'];
			}

			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query($query))) {
				$tags[] = $this->MakeXMLTag('result', 0);
				$tags[] = $this->MakeXMLTag('message', MessageBox(GetLang('CustomerEmailUniqueCheckFailed'), MSG_ERROR), true);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;

			} else {
				$tags[] = $this->MakeXMLTag('result', 1);
				$tags[] = $this->MakeXMLTag('message', MessageBox(GetLang('CustomerEmailUniqueCheckSuccess'), MSG_SUCCESS), true);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}
		}

		private function addDiscountRule()
		{
			if (!isset($_POST['type']) || $_POST['type'] == '' || !isset($_POST['discountId']) || !isId($_POST['discountId']) || !$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups) || !gzte11(ISC_MEDIUMPRINT)) {
				$tags[] = $this->MakeXMLTag('status', 0);
				$tags[] = $this->MakeXMLTag('type', $_POST['type'], true);
				$tags[] = $this->MakeXMLTag('hidden', '');
				exit;
			}

			$GLOBALS['DiscountId'] = (int)$_POST['discountId'];
			$GLOBALS['DiscountType'] = isc_html_escape($_POST['type']);
			$GLOBALS['Type'] = isc_html_escape($_POST['type']);

			$hidden = $this->template->render('Snippets/CustomerGroupHiddenBlock.html');

			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('type', $_POST['type'], true);
			$tags[] = $this->MakeXMLTag('hidden', $hidden, true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}
	}
