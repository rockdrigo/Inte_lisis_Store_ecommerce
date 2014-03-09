<?php
	class ISC_ADMIN_ORDERS extends ISC_ADMIN_BASE
	{
		protected $orderEntity;
		protected $customerEntity;

		protected $_customSearch = array();

		/**
		 * The constructor.
		 */
		public function __construct()
		{
			parent::__construct();
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('orders');

			// Initialise custom searches functionality
			require_once(dirname(__FILE__).'/class.customsearch.php');
			$GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH'] = new ISC_ADMIN_CUSTOMSEARCH('orders');

			$this->orderEntity = new ISC_ENTITY_ORDER();
			$this->customerEntity = new ISC_ENTITY_CUSTOMER();
		}

		public function HandleToDo($Do)
		{
			$GLOBALS['BreadcrumEntries'] = array(
				GetLang('Home') => "index.php",
				GetLang('Orders') => 'index.php?ToDo=viewOrders'
			);

			switch (isc_strtolower($Do))
			{
				case 'editordermultiaddressframe':
					$this->editOrderMultiAddressFrame();
					break;
				case 'editorder':
					$this->editOrder();
					break;
				case 'addorder':
					$this->addOrder();
					break;
				case "createorderview":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$GLOBALS['BreadcrumEntries'][GetLang('CreateOrderView')] = "index.php?ToDo=createOrderView";
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateView();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "printmultiorderinvoices":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$this->PrintMultiInvoices();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "deletecustomordersearch":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteCustomSearch();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "customordersearch":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$GLOBALS['BreadcrumEntries'][GetLang('CustomView')] = "index.php?ToDo=customOrderSearch";
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CustomSearch();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "searchordersredirect":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$GLOBALS['BreadcrumEntries'][GetLang('SearchResults')] = "index.php?ToDo=searchOrders";
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SearchOrdersRedirect();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "searchorders":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$GLOBALS['BreadcrumEntries'][GetLang('SearchResults')] = "index.php?ToDo=searchOrders";
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SearchOrders();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "saveupdatedordermessage":
					if(!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('ViewMessages') => "index.php?ToDo=saveUpdatedOrderMessage");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SavedUpdatedOrderMessage();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "editordermessage":
					if(!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('ViewMessages') => "index.php?ToDo=viewOrderMessages&orderId=" . @(int)$_GET['orderId'], GetLang('EditMessage') => "index.php?ToDo=editOrderMessage");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditOrderMessage();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "savenewordermessage":
					if(!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('ViewMessages') => "index.php?ToDo=saveUpdatedOrderMessage");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SaveNewOrderMessage();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "createordermessage":
					if(!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('ViewMessages') => "index.php?ToDo=viewOrderMessages&orderId=" . @(int)$_GET['orderId'], GetLang('CreateMessage') => "index.php?ToDo=createOrderMessage");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateOrderMessage();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "deleteordermessages":
					if(!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('ViewMessages') => "index.php?ToDo=saveUpdatedOrderMessage");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteOrderMessages();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "flagordermessage":
					if(!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('ViewMessages') => "index.php?ToDo=viewOrderMessages");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->FlagOrderMessage();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "updateordermessagestatus":
					if(!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('ViewMessages') => "index.php?ToDo=viewOrderMessages");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->UpdateOrderMessageStatus();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "viewordermessages":
					if(!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('ViewMessages') => "index.php?ToDo=viewOrderMessages");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->ViewOrderMessages();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "deleteorders":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Delete_Orders)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteOrders();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "undeleteorders":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Undelete_Orders)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders");
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->UndeleteOrders();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "purgeorders":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Purge_Orders)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders");
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->PurgeOrders();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "printorderinvoice":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$this->PrintInvoice();
					} else {
						echo "<script type=\"text/javascript\">window.close();</script>";
					}
					break;
				case "importordertrackingnumbers":
					if(gzte11(ISC_MEDIUMPRINT)) {
						if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Import_Order_Tracking_Numbers)) {
							if (!gzte11(ISC_MEDIUMPRINT)) {
								exit;
							}
							$this->ImportTrackingNumbers();
						} else {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
						}
					}
					break;
				case "viewsingleorder":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$output = GetClass('ISC_ADMIN_REMOTE')->GetOrderQuickView();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						echo $output;
					}
					break;
				case "updatemultiorderstatus":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$this->template->display('pageheader.popup.tpl');
						$this->updateOrderStatusBox();
						$this->template->display('pagefooter.popup.tpl');
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "refundorder":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->RefundOrder();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				default:
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {

						if(isset($_GET['searchQuery'])) {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders", GetLang('SearchResults') => "index.php?ToDo=viewOrders");
						}
						else {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Orders') => "index.php?ToDo=viewOrders");
						}

						if (GetSession('ordersearch') > 0) {
							if (!isset($_GET['searchId'])) {
								$_GET['searchId'] = GetSession('ordersearch');
								$_REQUEST['searchId'] = GetSession('ordersearch');
							}

							if ($_GET['searchId'] > 0) {
								$GLOBALS['BreadcrumEntries'] = array_merge($GLOBALS['BreadcrumEntries'], array(GetLang('CustomView') => "index.php?ToDo=customOrderSearch"));
							}
						}

						if (!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}
						if (GetSession('ordersearch') > 0) {
							$this->CustomSearch();
						} else {
							UnsetSession('ordersearch');
							$this->ManageOrders();
						}
						if (!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
			}
		}

		public function getQuoteSession($id)
		{
			if(!isset($_SESSION['QUOTE_SESSIONS'][$id])) {
				return false;
			}

			return $_SESSION['QUOTE_SESSIONS'][$id];
		}

		public function deleteQuoteSession($id)
		{
			if(isset($_SESSION['QUOTE_SESSIONS'][$id])) {
				unset($_SESSION['QUOTE_SESSIONS'][$id]);
			}
			return true;
		}

		public function generateEditOrderItemsTable(ISC_QUOTE $quote)
		{
			$this->template->assign('quote', $quote);
			return $this->template->render('order.form.items.tpl');
		}

		public function generateEditOrderItemRow(ISC_QUOTE_ITEM $item)
		{
			$this->template->assign('item', $item);
			return $this->template->render('order.form.item.tpl');
		}

		public function generateOrderPaymentForm($order = null)
		{
			// for a new order or if no payment was previously taken, show the payment methods
			if ($order == null || empty($order['orderpaymentmodule'])) {
				$availableModules = GetManualOrderCheckoutModules();

				$templateModules = array();
				foreach ($availableModules as $moduleId => $module) {
					/** @var ISC_CHECKOUT_PROVIDER */
					$moduleObject = $module['object'];

					// get the form fields for the method (if any)
					$paymentFields = $moduleObject->GetManualPaymentFields();

					$templateModule = array(
						'id'					=> $moduleId,
						'name' 					=> $module['name'],
						'requiresSSL'			=> $moduleObject->RequiresSSL(),
						'supportsMultiAddress'	=> $moduleObject->IsMultiShippingCompatible(),
						'formFields'			=> $paymentFields,
					);

					if (method_exists($moduleObject, 'GetManualPaymentJavascript')) {
						$this->template->assign('PaymentMethodId', $moduleId);
						$templateModule['javascript'] = $moduleObject->GetManualPaymentJavascript();
					}

					$templateModules[$moduleId] = $templateModule;
				}

				// add a custom payment method
				$templateModules['custom'] = array(
					'id'					=> 'custom',
					'name' 					=> GetLang('ManualPayment'),
					'requiresSSL'			=> false,
					'supportsMultiAddress'	=> true,
					'formFields'			=> array(
						'custom_name' => array(
							'type' 		=> 'text',
							'title' 	=> 'Name',
							'value' 	=> '',
							'required'	=> false,
						),
					),
				);

				$this->template->assign('modules', $templateModules);
				$this->template->assign('controlPanelSecure', ($_SERVER['HTTPS'] == 'on'));
			}
			else {
				// show a summary of the previous payment
				$payment = array(
					'provider'		=> $order['orderpaymentmethod'],
					'status'		=> ucfirst($order['ordpaymentstatus']),
					'transactionId'	=> $order['ordpayproviderid'],
				);

				$this->template->assign('payment', $payment);
			}

			return $this->template->render('order.form.summary.payment.tpl');
		}

		protected function addOrder()
		{
			do {
				$sessionId = md5(uniqid());
			} while(isset($_SESSION['QUOTE_SESSIONS'][$sessionId]));

			$quote = new ISC_QUOTE;
			$quote->setOrderStatus(ORDER_STATUS_INCOMPLETE);

			$_SESSION['QUOTE_SESSIONS'][$sessionId] = $quote;

			return $this->displayAddEditOrder($sessionId);
		}

		protected function editOrder()
		{
			$orderId = $_GET['orderId'];
			if (!isId($orderId)) {
				exit;
			}

			$entity = new ISC_ENTITY_ORDER;
			$order = $entity->get($orderId);
			$quote = $entity->convertOrderToQuote($orderId, false);
			unset($entity);

			if (!$quote) {
				exit;
			}

			do {
				$sessionId = md5(uniqid($orderId));
			} while(isset($_SESSION['QUOTE_SESSIONS'][$sessionId]));
			$_SESSION['QUOTE_SESSIONS'][$sessionId] = $quote;

			if ($order['deleted']) {
				FlashMessage(GetLang('EditDeletedOrderNotice'), MSG_ERROR);
			} else {
				FlashMessage(GetLang('EditOrderNotice'), MSG_INFO);
			}

			return $this->displayAddEditOrder($sessionId, $orderId);
		}

		/**
		* This was inside addOrder, moved out for use by editing and for split-shipping allocation
		*
		* @param int $formId one of FORMFIELD_ form-type constants
		* @param ISC_QUOTE_ADDRESS $quoteAddress
		* @return array of field=>value variables suitable for setting as template data
		*/
		public function populateQuoteAddressFormFields($formId, ISC_QUOTE_ADDRESS $quoteAddress = null)
		{
			require_once ISC_BASE_PATH . '/lib/addressvalidation.php';

			if ($quoteAddress) {
				$quoteAddressFields = convertAddressArrayToFieldArray($quoteAddress->getAsArray());
			}

			$countryFieldId = 0;
			$stateFieldId = 0;
			$zipFieldId = 0;

			$formFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields($formId);
			foreach($formFields as $fieldId => /** @var ISC_FORMFIELD_BASE */$field) {
				$field->setRequired(false);
				$formFieldPrivateId = $field->record['formfieldprivateid'];
				
				// if($formFieldPrivateId && !gzte11(ISC_MEDIUMPRINT)) {
					// unset($fieldId);
				// }

				// for display purposes, pre-populate the form field with existing quote address info
				if ($quoteAddress && $quoteAddressFields) {
					if (!$formFieldPrivateId) {
						$customField = $quoteAddress->getCustomField($field->record['formfieldid']);
						if ($customField) {
							$field->setValue($customField['value']);
						}
					} else if (isset($quoteAddressFields[$formFieldPrivateId])) {
						$field->setValue($quoteAddressFields[$formFieldPrivateId], true);
					}
				}


				if($formFieldPrivateId == 'Country') {
					$field->setRequired(true);
					$countryFieldId = $fieldId;
				}
				else if($formFieldPrivateId == 'State') {
					$stateFieldId = $fieldId;
				}
				else if ($formFieldPrivateId == 'Zip') {
					$zipFieldId = $fieldId;
					$field->setRequired(true);
				}
				

				$GLOBALS['ISC_CLASS_FORM']->addFormFieldUsed($field);
			}
	
			// This is a massive hack, and a poorly designed feature. Seriously.
			if($countryFieldId) {
				$formFields[$countryFieldId]->setOptions(array_values(GetCountryListAsIdValuePairs()));
				if ($formFields[$countryFieldId]->getValue() == '') {
					$formFields[$countryFieldId]->setValue(GetConfig('CompanyCountry'));
				}

				if ($stateFieldId) {
					$formFields[$countryFieldId]->addEventHandler('change', 'FormFieldEvent.SingleSelectPopulateStates', array('countryId' => $countryFieldId, 'stateId' => $stateFieldId));
					$countryId = GetCountryByName($formFields[$countryFieldId]->getValue());
					$stateOptions = GetStateListAsIdValuePairs($countryId);

					if (is_array($stateOptions) && !empty($stateOptions)) {
						$formFields[$stateFieldId]->setOptions($stateOptions);
					}
					else {
						// no states for our country, we need to mark this as not required
						$formFields[$stateFieldId]->setRequired(false);
					}

					if ($formFields[$stateFieldId]->getValue() == '') {
						$formFields[$stateFieldId]->setValue(getConfig('CompanyState'));
					}
				}
			}

			if ($zipFieldId && getConfig('CompanyZip') && $formFields[$zipFieldId]->getValue() == '') {
				$formFields[$zipFieldId]->setValue(getConfig('CompanyZip'));
			}

			return $formFields;
		}

		/**
		* This was inside addOrder, moved out for use by editing and for split-shipping allocation
		*
		* @param ISC_QUOTE $quote
		*/
		public function populateQuoteFormFields(ISC_QUOTE $quote)
		{
			require_once(ISC_BASE_PATH . '/lib/addressvalidation.php');

			$formTypes = array(
				'accountFormFields'		=> FORMFIELDS_FORM_ACCOUNT,
				'billingFormFields'		=> FORMFIELDS_FORM_BILLING,
				'shippingFormFields'	=> FORMFIELDS_FORM_SHIPPING,
			);

			$this->template->assign('formFieldTypes', $formTypes);

			foreach($formTypes as $templateVar => $formId) {
				/** @var ISC_QUOTE_ADDRESS quote address with which to populate form field values */
				$quoteAddress = null;
				switch ($formId) {
					case FORMFIELDS_FORM_ACCOUNT:
					case FORMFIELDS_FORM_BILLING:
						$quoteAddress = $quote->getBillingAddress();
						break;

					case FORMFIELDS_FORM_SHIPPING:
						if ($quote->getIsSplitShipping()) {
							break;
						}
						$quoteAddress = $quote->getShippingAddress();
						break;
				}

				$formFields = $this->populateQuoteAddressFormFields($formId, $quoteAddress);

				$this->template->assign($templateVar, $formFields);
			}

			$this->engine->bodyScripts[] = '../javascript/formfield.js';
			$this->template->assign('formFieldJavascript', $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS());
		}

		protected function displayAddEditOrder($sessionId, $orderId = null)
		{
			$order = null;

			if ($orderId) {
				$order = new ISC_ENTITY_ORDER;
				$order = $order->get($orderId);
				if (!$order) {
					exit;
				}

				$forEditing = true;
				$this->template->assign('editingOrder', $orderId);
				$this->template->assign('addingOrder', false);

				// could be useful
				$this->template->assign('order', $order);
			} else {
				$forEditing = false;
				$this->template->assign('editingOrder', false);
				$this->template->assign('addingOrder', true);
			}

			/** @var ISC_QUOTE */
			$quote = $_SESSION['QUOTE_SESSIONS'][$sessionId];
			$this->template->assign('quote', $quote);

			if ($quote->getCustomerId()) {
				// verify the customer still exists
				$customer = new ISC_ENTITY_CUSTOMER;
				if (!$customer->get($quote->getCustomerId())) {
					FlashMessage(GetLang('OrderCustomerDoesNotExist'), MSG_ERROR);
					$quote->setCustomerId(0);
				}
			}

			$incTax = (getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE);

			require ISC_BASE_PATH . '/lib/addressvalidation.php';

			$this->engine->printHeader();

			$this->template->assign('quoteSession', $sessionId);
			$this->template->assign('statusList', getOrderStatusList());

			$this->template->assign('subtotal', FormatPrice($quote->getSubTotal($incTax))); // would prefer this as {{ quote.subTotal|formatPrice }} but it relies on $incTax variable parameter

			$this->populateQuoteFormFields($quote);

			$shipItemsTo = 'billing';
			if ($forEditing) {
				if ($quote->getIsSplitShipping()) {
					$shipItemsTo = 'multiple';
				} else {
					$shipItemsTo = 'single';
				}
			}
			$this->template->assign('shipItemsTo', $shipItemsTo);

			$accountCustomerGroups = array();
			if(gzte11(ISC_MEDIUMPRINT)) {
				$query = "
					SELECT customergroupid, groupname
					FROM [|PREFIX|]customer_groups
					ORDER BY groupname
				";
				$result = $this->db->query($query);
				while($group = $this->db->fetch($result)) {
					$accountCustomerGroups[$group['customergroupid']] = $group['groupname'];
				}
				array_unshift($accountCustomerGroups, GetLang('CustomerGroupNotAssoc'));
			}
			$this->template->assign('accountCustomerGroups', $accountCustomerGroups);

			$this->template->assign('itemsTable', $this->generateEditOrderItemsTable($quote));

			if ($forEditing && $quote->getIsSplitShipping()) {
				$this->template->assign('multiShippingTable', $this->renderMultiShippingTable($quote));
			}

			$allowGiftCertificates = gzte11(ISC_LARGEPRINT);
			$this->template->assign('allowGiftCertificates', $allowGiftCertificates);

			$this->template->assign('paymentForm', $this->generateOrderPaymentForm($order));

			$this->template->display('order.form.tpl');
			$this->engine->printFooter();
		}

		public function renderMultiShippingTable(ISC_QUOTE $quote)
		{
			if (!$quote->getIsSplitShipping()) {
				return false;
			}

			$unallocatedItems = array();
			$allocatedItems = array();
			foreach ($quote->getItems(PT_PHYSICAL) as $item) {
				if($item->getAddressId() == ISC_QUOTE_ADDRESS::ID_UNALLOCATED) {
					$unallocatedItems[] = $item;
				} else {
					$allocatedItems[] = $item;
				}
			}

			$context = array(
				'allocatedItems' => $allocatedItems,
				'unallocatedItems' => $unallocatedItems,
				'shippingAddresses' => $quote->getShippingAddresses(),
			);

			return $this->template->render('order.form.multishippingtable.tpl', $GLOBALS + $context);
		}

		/**
		* Displays the contents of the iframe for allocating items to an address in a split-shipping order
		*
		*/
		protected function editOrderMultiAddressFrame()
		{
			if (empty($_GET['quoteSession']) || (empty($_GET['item']) && empty($_GET['address']))) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_GET['quoteSession']);
			if(!$quote) {
				exit;
			}

			$quote->setIsSplitShipping(true);

			$customer = $quote->getCustomerId();
			if ($customer) {
				$customer = getClass('ISC_CUSTOMER')->getCustomerInfo($customer);
			}

			require_once ISC_BASE_PATH . '/lib/addressvalidation.php';

			// @todo @hack placing billing address into 'existing' address list as the address frame doesn't currently have a radio to select between billing/different address
			$addresses = array(
				convertAddressArrayToFieldArray($quote->getBillingAddress()->getAsArray()),
			);

			if ($customer && $customer['customerid']) {
				$addresses = getClass('ISC_CUSTOMER')->getCustomerShippingAddresses($customer['customerid']);
				foreach($addresses as $index => $address) {
					$address = convertAddressArrayToFieldArray($address);
					$countryIso = getCountryISO2ByName($address['Country']);
					if(file_exists(ISC_BASE_PATH.'/lib/flags/'.strtolower($countryIso.'.gif'))) {
						$address['countryFlag'] = strtolower($countryIso);
					}
					$addresses[$index] = $address;
				}
			}
			$this->template->assign('addresses', $addresses);

			$this->populateQuoteFormFields($quote);

			if (Interspire_Request::get('address', false)) {
				// take items from address being edited
				$address = Interspire_Request::get('address');
				/** @var ISC_QUOTE_ADDRESS */
				$address = $quote->getAddressById($address);
				if (!$address) {
					exit;
				}
				$items = $address->getItems();
				$this->template->assign('address', $address);

				$this->template->assign('shippingFormFields', $this->populateQuoteAddressFormFields(FORMFIELDS_FORM_SHIPPING, $address));
			} else {
				// take items from posted list to be added to new address
				$items = array();
				foreach ($_GET['item'] as $itemId) {
					$items[] = $quote->getItemById($itemId);
				}
			}

			$this->template->assign('items', $items);

			$this->template->assign('quoteSession', $_GET['quoteSession']);

			$this->engine->stylesheets[] = 'Styles/order.form.css';
			$this->engine->bodyScripts[] = 'script/order.form.js';
			$this->engine->setupHeaderFooter();
			$this->template->display('order.form.allocate.frame.tpl');
		}

		protected function PurgeOrders ()
		{
			// final permission checks
			$canManage = $this->auth->HasPermission(AUTH_Manage_Orders);
			$canPurge = $this->auth->HasPermission(AUTH_Purge_Orders);

			if (!$canPurge) {
				if ($canManage) {
					$this->ManageOrders(GetLang('Unauthorized'), MSG_ERROR);
					return;
				}
				$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				return;
			}

			// input validation
			$orderIds = array();
			if (isset($_POST['orders']) && is_array($_POST['orders']) && !empty($_POST['orders'])) {
				$orderIds = array_map('intval', $_POST['orders']);
			}

			if (empty($orderIds)) {
				if ($canManage) {
					$this->ManageOrders();
					return;
				}
				$this->engine->DoHomePage();
				return;
			}

			// do the order delete
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($orderIds));

			$entity = new ISC_ENTITY_ORDER;
			foreach ($orderIds as $orderId) {
				if (!$entity->purge($orderId)) {
					if ($canManage) {
						$this->ManageOrders($entity->getError(), MSG_ERROR);
						return;
					}
					$this->engine->DoHomePage($entity->getError(), MSG_ERROR);
					return;
				}
			}

			if ($canManage) {
				$this->ManageOrders(GetLang('OrdersPurgedSuccessfully'), MSG_SUCCESS);
				return;
			}
			$this->engine->DoHomePage(GetLang('OrdersPurgedSuccessfully'), MSG_SUCCESS);
		}

		protected function UndeleteOrders ()
		{
			// final permission checks
			$canManage = $this->auth->HasPermission(AUTH_Manage_Orders);
			$canUndelete = $this->auth->HasPermission(AUTH_Undelete_Orders);

			if (!$canUndelete) {
				if ($canManage) {
					$this->ManageOrders(GetLang('Unauthorized'), MSG_ERROR);
					return;
				}
				$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				return;
			}

			// input validation
			$orderIds = array();
			if (isset($_POST['orders']) && is_array($_POST['orders']) && !empty($_POST['orders'])) {
				$orderIds = array_map('intval', $_POST['orders']);
			}

			if (empty($orderIds)) {
				if ($canManage) {
					$this->ManageOrders();
					return;
				}
				$this->engine->DoHomePage();
				return;
			}

			// do the order delete
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($orderIds));

			$entity = new ISC_ENTITY_ORDER;
			foreach ($orderIds as $orderId) {
				if (!$entity->undelete($orderId)) {
					if ($canManage) {
						$this->ManageOrders($entity->getError(), MSG_ERROR);
						return;
					}
					$this->engine->DoHomePage($entity->getError(), MSG_ERROR);
					return;
				}
			}

			if ($canManage) {
				$this->ManageOrders(GetLang('OrdersUndeletedSuccessfully'), MSG_SUCCESS);
				return;
			}
			$this->engine->DoHomePage(GetLang('OrdersUndeletedSuccessfully'), MSG_SUCCESS);
		}

		/**
		 * This method marks orders as deleted using ISC_ENTITY_ORDER::delete
		 *
		 * @return void
		 */
		protected function DeleteOrders ()
		{
			// final permission checks
			$canManage = $this->auth->HasPermission(AUTH_Manage_Orders);
			$canDelete = $this->auth->HasPermission(AUTH_Delete_Orders);

			if (!$canDelete) {
				if ($canManage) {
					$this->ManageOrders(GetLang('Unauthorized'), MSG_ERROR);
					return;
				}
				$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				return;
			}

			// input validation
			$orderIds = array();
			if (isset($_POST['orders']) && is_array($_POST['orders']) && !empty($_POST['orders'])) {
				$orderIds = array_map('intval', $_POST['orders']);
			}

			if (empty($orderIds)) {
				if ($canManage) {
					$this->ManageOrders();
					return;
				}
				$this->engine->DoHomePage();
				return;
			}

			// do the order delete
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($orderIds));

			// determine which delete method to use based on store settings
			$deleteMethod = 'delete';
			if (GetConfig('DeletedOrdersAction') == 'purge') {
				$deleteMethod = 'purge';
			}

			$entity = new ISC_ENTITY_ORDER;
			foreach ($orderIds as $orderId) {
				if (!$entity->$deleteMethod($orderId)) {
					if ($canManage) {
						$this->ManageOrders($entity->getError(), MSG_ERROR);
						return;
					}
					$this->engine->DoHomePage($entity->getError(), MSG_ERROR);
					return;
				}
			}

			$message = GetLang('OrdersDeletedSuccessfully');

			if ($canManage) {
				$this->ManageOrders($message, MSG_SUCCESS);
				return;
			}
			$this->engine->DoHomePage($message, MSG_SUCCESS);
		}

		protected function ManageOrdersGrid(&$numOrders, &$numDeletedOrders = 0)
		{
			// Show a list of products in a table
			$page = 0;
			$start = 0;
			$GLOBALS['OrderGrid'] = "";
			$catList = "";
			$max = 0;

			// Is this a custom search?
			if(isset($_GET['searchId'])) {
				// Override custom search sort fields if we have a requested field
				if(isset($_GET['sortField'])) {
					$_REQUEST['sortField'] = $_GET['sortField'];
				}
				if(isset($_GET['sortOrder'])) {
					$_REQUEST['sortOrder'] = $_GET['sortOrder'];
				}
			}

			if(isset($_GET['searchQuery'])) {
				$GLOBALS['QueryEscaped'] = isc_html_escape($_GET['searchQuery']);
			}

			if(isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == "asc") {
				$sortOrder = "asc";
			}
			else {
				$sortOrder = "desc";
			}

			$validSortFields = array('orderid', 'custname', 'orddate', 'ordstatus', 'newmessages', 'total_inc_tax');
			if(isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
				$sortField = $_REQUEST['sortField'];
				SaveDefaultSortField("ManageOrders", $_REQUEST['sortField'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("ManageOrders", "orderid", $sortOrder);
			}

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			} else {
				$page = 1;
			}

			if (isset($_GET['perpage'])) {
				$perPage = (int)$_GET['perpage'];
				SaveDefaultPerPage("ManageOrders", $perPage);
			}
			else {
				$perPage = GetDefaultPerPage("ManageOrders", ISC_ORDERS_PER_PAGE);
			}

			// Build the pagination and sort URL
			$searchURL = $_GET;
			unset($searchURL['sortField'], $searchURL['sortOrder'], $searchURL['page'], $searchURL['new'], $searchURL['ToDo'], $searchURL['SubmitButton1'], $searchURL['SearchButton_x'], $searchURL['SearchButton_y']);
			$searchURL['sortField'] = $sortField;
			$searchURL['sortOrder'] = $sortOrder;
			$this->template->assign('searchURL', $searchURL);

			$sortURL = $searchURL;
			unset($sortURL['sortField'], $sortURL['sortOrder']);

			// Limit the number of orders returned
			if ($page == 1) {
				$start = 1;
			} else {
				$start = ($page * $perPage) - ($perPage-1);
			}

			$start = $start-1;

			// Get the results for the query
			$orderResult = $this->_GetOrderList($start, $sortField, $sortOrder, $numOrders, $perPage, $numDeletedOrders);

			$GLOBALS['perPage'] = $perPage;
			$GLOBALS['numOrders'] = $numOrders;
			$GLOBALS['pageURL'] = "index.php?ToDo=viewOrders&" . http_build_query($searchURL);
			$GLOBALS['currentPage'] = $page;

			$this->template->assign('numDeletedOrders', $numDeletedOrders);

			if ($numOrders && $numDeletedOrders) {
				$searchGet = $_GET;
				if (isset($searchGet['searchId']) && $searchGet['searchId'] == 0) {
					unset($searchGet['searchId']);
				}

				if (count($searchGet) > 1) {
					$deletedUrl = $searchGet;
					$deletedUrl['searchDeletedOrders'] = 'only';
					$deletedUrl = 'index.php?' . http_build_query($deletedUrl);
					$this->template->assign('viewDeletedOrdersUrl', $deletedUrl);
					unset($deletedUrl);
				}
				unset($searchGet);
			}

			if(isset($_GET['searchQuery'])) {
				$query = $_GET['searchQuery'];
			} else {
				$query = "";
			}

			$GLOBALS['SearchQuery'] = $query;
			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			$sortLinks = array(
				"Id" => "orderid",
				"Cust" => "custname",
				"Date" => "orddate",
				"Status" => "ordstatus",
				"Message" => "newmessages",
				"Total" => "total_inc_tax"
			);
			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewOrders&amp;".http_build_query($sortURL)."&amp;page=".$page, $sortField, $sortOrder);

			// Workout the maximum size of the array
			$max = $start + $perPage;

			if ($max > count($orderResult)) {
				$max = count($orderResult);
			}

			if(!gzte11(ISC_LARGEPRINT)) {
				$GLOBALS['HideOrderMessages'] = "none";
				$GLOBALS['CustomerNameSpan'] = 2;
			}

			// Display the orders
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($orderResult)) {
				$this->template->assign('order', $row);
				$GLOBALS['OrderId'] = $row['orderid'];
				$GLOBALS['CustomerId'] = $row['ordcustid'];
				$GLOBALS['OrderId1'] = $row['orderid'];
				$GLOBALS['Customer'] = isc_html_escape($row['custname']);

				$GLOBALS['Date'] = isc_date(GetConfig('DisplayDateFormat'), $row['orddate']);
				$GLOBALS['OrderStatusOptions'] = $this->GetOrderStatusOptions($row['ordstatus']);

				$GLOBALS['Total'] = FormatPriceInCurrency($row['total_inc_tax'], $row['orddefaultcurrencyid'], null, true);

				$GLOBALS['NotesIcon'] = "";
				$GLOBALS['CommentsIcon'] = "";

				// Look up the country for the IP address of this order
				if(gzte11(ISC_LARGEPRINT)) {
					$suspiciousOrder = false;
					$GLOBALS['FlagCellClass'] = $GLOBALS['FlagCellTitle'] = '';
					if($row['ordgeoipcountrycode'] != '') {
						$flag = strtolower($row['ordgeoipcountrycode']);
						// If the GeoIP based country code and the billing country code don't match, we flag this order as a different colour
						if(strtolower($row['ordgeoipcountrycode']) != strtolower($row['ordbillcountrycode'])) {
							$GLOBALS['FlagCellClass'] = "Suspicious";
							$suspiciousOrder = true;

						}
						$countryName = $row['ordgeoipcountry'];
					}
					else {
						$flag = strtolower($row['ordbillcountrycode']);
						$countryName = $row['ordbillcountry'];
						$GLOBALS['FlagCellTitle'] = $row['ordbillcountry'];
					}
					// Do we have a country flag to show?
					if(file_exists(ISC_BASE_PATH."/lib/flags/".$flag.".gif")) {
						$flag = GetConfig('AppPath')."/lib/flags/".$flag.".gif";
						if($suspiciousOrder == true) {
							$title = sprintf(GetLang('OrderCountriesDontMatch'), $row['ordbillcountry'], $row['ordgeoipcountry']);
							$GLOBALS['OrderCountryFlag'] = "<span onmouseout=\"HideQuickHelp(this);\" onmouseover=\"ShowQuickHelp(this, '".GetLang('PossibleFraudulentOrder')."', '".$title."');\"><img src=\"".$flag."\" alt='' /></span>";
						}
						else {
							$GLOBALS['OrderCountryFlag'] = "<img src=\"".$flag."\" alt='' title=\"".$countryName."\" />";
						}
					}
					else {
						$GLOBALS['OrderCountryFlag'] = '';
					}
				}
				else {
					$GLOBALS['HideCountry'] = "none";
				}

				// If this is ebay item, we will have the icon as eBay icon
				$GLOBALS['OrderIcon'] = 'order.gif';
				if ($row['ebay_order_id'] != '0') {
					$GLOBALS['OrderIcon'] = 'ebay.gif';
				}

				// Workout the message link -- do they have permission to view order messages?
				$GLOBALS["HideMessages"] = "none";

				if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Order_Messages) && $row['ordcustid'] > 0) {
					$numMessages = GetLang('Messages');
					if($row['nummessages'] == 1) {
						$numMessages = GetLang('OrderMessage');
					}
					$newMessages = '0 '.GetLang('NewText');
					if($row['newmessages'] > 0) {
						$newMessages = "<strong>" . $row['newmessages'] . " " . GetLang('NewText') . "</strong>";
					}
					$GLOBALS['MessageLink'] = sprintf("<a title='%s' class='Action' href='index.php?ToDo=viewOrderMessages&amp;ord
					erId=%d'>%s %s</a><br />(%s)",
						GetLang('MessageOrder'),
						$row['orderid'],
						$row['nummessages'],
						$numMessages,
						$newMessages
					);

					if($row["numunreadmessages"] > 0 && gzte11(ISC_LARGEPRINT)) {
						$GLOBALS["HideMessages"] = "";
						$GLOBALS["NumMessages"] = $row['numunreadmessages'];
					}
				}
				else {
					$GLOBALS['MessageLink'] = sprintf("<a class='Action' disabled>%s (0)</a>", GetLang('Messages'));
				}

				if(!gzte11(ISC_LARGEPRINT)) {
					$GLOBALS["HideMessages"] = "none";
				}

				// If the customer still exists, link to the customer page
				if(trim($row['custname']) != '') {
					$GLOBALS['CustomerLink'] = "<a href='index.php?ToDo=viewCustomers&amp;idFrom=".$GLOBALS['CustomerId']."&idTo=".$GLOBALS['CustomerId']."'>".$GLOBALS['Customer']."</a>";
				}
				else {
					$GLOBALS['CustomerLink'] = $row['ordbillfirstname'].' '.$row['ordbilllastname'];
				}

				if($row['ordcustid'] == 0) {
					$GLOBALS['CustomerLink'] .= " <span style=\"color: gray;\">".GetLang('GuestCheckoutCustomer')."</span>";
				}

				// If the order has any notes, flag it
				if($row['ordnotes'] != '') {
					$GLOBALS['NotesIcon'] = '<a href="#" onclick="Order.HandleAction(' . $row['orderid'] . ', \'orderNotes\');"><img src="images/note.png" alt="" title="' . GetLang('OrderHasNotes') . '" style="border-style: none;" /></a>';
					$GLOBALS['HasNotesClass'] = 'HasNotes';
				}
				else {
					$GLOBALS['HasNotesClass'] = '';
				}

				// does the order have a customer message?
				if (!empty($row['ordcustmessage'])) {
					$GLOBALS['CommentsIcon'] = '<a href="#" onclick="Order.HandleAction(' . $row['orderid'] . ', \'orderNotes\');"><img src="images/user_comment.png" alt="" title="' . GetLang('OrderHasComments') . '" style="border-style: none;" /></a>';
				}

				// If the order has any shipable items, show the link to ship items
				$GLOBALS['ShipItemsLink'] = '';
				if (!$row['deleted'] && isset($row['ordtotalshipped']) && isset($row['ordtotalqty'])) {
					if($row['ordisdigital'] == 0 && ($row['ordtotalqty']-$row['ordtotalshipped']) > 0) {
						$addClass = '';
						if($row['shipping_address_count'] > 1) {
							$addClass = 'MultipleAddresses';
						}
						$GLOBALS['ShipItemsLink'] = '<option id="ShipItemsLink'.$row['orderid'].'"  value="shipItems'.$addClass.'">'.GetLang('ShipItems').'</option>';
					}
				}

				//Show payment status blow order status
				$GLOBALS['PaymentStatus'] = '';
				$GLOBALS['HidePaymentStatus'] = 'display:none;';
				$GLOBALS['PaymentStatusColor'] = '';
				if($row['ordpaymentstatus'] != '') {
					$GLOBALS['HidePaymentStatus'] = '';
					$GLOBALS['PaymentStatusColor'] = '';
					switch($row['ordpaymentstatus']) {
						case 'authorized':
							$GLOBALS['PaymentStatusColor'] = 'PaymentAuthorized';
							break;
						case 'captured':
							$GLOBALS['PaymentStatus'] = GetLang('Payment')." ".ucfirst($row['ordpaymentstatus']);
							$GLOBALS['PaymentStatusColor'] = 'PaymentCaptured';
							break;
						case 'refunded':
						case 'partially refunded':
						case 'voided':
							$GLOBALS['PaymentStatus'] = GetLang('Payment')." ".ucwords($row['ordpaymentstatus']);
							$GLOBALS['PaymentStatusColor'] = 'PaymentRefunded';
							break;
					}
				}


				// If the allow payment delayed capture, show the link to Delayed capture
				$GLOBALS['DelayedCaptureLink'] = '';
				$GLOBALS['VoidLink'] = '';
				$GLOBALS['RefundLink'] ='';
				$transactionId = trim($row['ordpayproviderid']);

				//if orginal transaction id exist and payment provider is currently enabled
				if($transactionId != '' && GetModuleById('checkout', $provider, $row['orderpaymentmodule']) && $provider->IsEnabled() && !gzte11(ISC_HUGEPRINT)) {
					//if the payment module allow delayed capture and the current payment status is authorized
					//display delay capture option
					if(method_exists($provider, "DelayedCapture") && $row['ordpaymentstatus'] == 'authorized') {
						$GLOBALS['DelayedCaptureLink'] = '<option value="delayedCapture">'.GetLang('CaptureFunds').'</option>';

						$GLOBALS['PaymentStatus'] .= '<a onclick="Order.DelayedCapture('.$row['orderid'].'); return false;" href="#">'.GetLang('CaptureFunds').'</a>';
					}

					//if the payment module allow void transaction and the current payment status is authorized
					//display void option
					if(method_exists($provider, "DoVoid") && $row['ordpaymentstatus'] == 'authorized') {
						$GLOBALS['VoidLink'] = '<option value="voidTransaction">'.GetLang('VoidTransaction').'</option>';
					}

					//if the payment module allow refund and the current payment status is authorized
					//display refund option
					if(method_exists($provider, "DoRefund") && ($row['ordpaymentstatus'] == 'captured' || $row['ordpaymentstatus'] == 'partially refunded')) {
						$GLOBALS['RefundLink'] = '<option value="refundOrder">'.GetLang('Refund').'</option>';
					}
				}

				$GLOBALS["OrderStatusText"] = GetOrderStatusById($row['ordstatus']);
				$GLOBALS['OrderStatusId'] = $row['ordstatus'];
				$GLOBALS['OrderGrid'] .= $this->template->render('order.manage.row.tpl');
			}

			// Close the GeoIP database if we used it
			if(isset($gi)) {
				geoip_close($gi);
			}

			// Hide the message box in templates/iphone/MessageBox.html if we're not searching
			if(!isset($_REQUEST["searchQuery"]) && isset($_REQUEST["page"])) {
				$GLOBALS["HideYellowMessage"] = "none";
			}

			$GLOBALS['CurrentPage'] = $page;

			return $this->template->render('orders.manage.grid.tpl');
		}

		protected function ManageOrders($MsgDesc = "", $MsgStatus = "")
		{
			$GLOBALS['HideClearResults'] = "none";
			$status = array();
			$num_custom_searches = 0;
			$numOrders = 0;

			// Fetch any results, place them in the data grid
			$GLOBALS['OrderDataGrid'] = $this->ManageOrdersGrid($numOrders, $numDeletedOrders);

			// Was this an ajax based sort? Return the table now
			if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['OrderDataGrid'];
				return;
			}

			if(isset($_REQUEST['searchQuery']) || isset($_GET['searchId'])) {
				$GLOBALS['HideClearResults'] = "";
			}

			if(isset($this->_customSearch['searchname'])) {
				$GLOBALS['ViewName'] = $this->_customSearch['searchname'];

				if(!empty($this->_customSearch['searchlabel'])) {
					$GLOBALS['HideDeleteViewLink'] = "none";
				}
			}
			else {
				$GLOBALS['ViewName'] = GetLang('AllOrders');
				$GLOBALS['HideDeleteViewLink'] = "none";
			}

			// Do we display the add order buton?
			if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Add_Orders)) {
				$GLOBALS['AddOrderButton'] = '<input type="button" value="' . GetLang('AddAnOrder') . '..." class="FormButton" style="width:100px" onclick="document.location.href=\'index.php?ToDo=addOrder\'" />';
			} else {
				$GLOBALS['AddOrderButton'] = '';
			}

			$GLOBALS['OrderActionOptions'] = '<option selected="1">' . GetLang('ChooseAction') . '</option>';

			$searchDeletedOrders = 'no';
			if (isset($_REQUEST['searchDeletedOrders'])) {
				$searchDeletedOrders = $_REQUEST['searchDeletedOrders'];
			}

			if ($searchDeletedOrders != 'only') {
				// Do we need to disable the delete button?
				if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Delete_Orders) || $numOrders == 0) {
					$args = 'disabled="disabled"';
				} else {
					$args = 'value="delete"';
				}

				$GLOBALS['OrderActionOptions'] .= '<option ' . $args . '>' . GetLang('DeleteSelected') . '</option>';
			}

			$searchGet = $_GET;
			if (isset($searchGet['searchId']) && $searchGet['searchId'] == 0) {
				// this is a nasty hack but I can't right now figure out a better way of making count($_GET) work as
				// expected when the clicking 'view: all orders' which is '&ToDo=viewOrders&searchId=0'
				unset($searchGet['searchId']);
			}

			if ($searchDeletedOrders != 'no') {
				if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Undelete_Orders) || $numOrders == 0) {
					$GLOBALS['OrderActionOptions'] .= '<option disabled="disabled">' . isc_html_escape(GetLang('UndeleteSelected')) . '</option>';
				} else {
					$GLOBALS['OrderActionOptions'] .= '<option value="undelete">' . isc_html_escape(GetLang('UndeleteSelected')) . '</option>';
				}

				if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Purge_Orders) || $numOrders == 0) {
					$GLOBALS['OrderActionOptions'] .= '<option disabled="disabled">' . isc_html_escape(GetLang('PurgeSelected')) . '</option>';
				} else {
					$GLOBALS['OrderActionOptions'] .= '<option value="purge">' . isc_html_escape(GetLang('PurgeSelected')) . '</option>';
				}
			}

			if ($searchDeletedOrders == 'only' && GetConfig('DeletedOrdersAction') == 'purge') {
				// show a notice about searching for deleted orders when the feature is turned off
				FlashMessage(GetLang('OrderArchivingIsTurnedOff'), MSG_INFO);
			}

			if($numOrders > 0) {
				if($MsgDesc == "" && (isset($_REQUEST['searchQuery']) || count($searchGet) > 1) && !isset($_GET['selectOrder'])) {
					if($numOrders == 1) {
						$MsgDesc = GetLang('OrderSearchResultsBelow1');
					}
					else {
						$MsgDesc = sprintf(GetLang('OrderSearchResultsBelowX'), $numOrders);
					}

					$MsgStatus = MSG_SUCCESS;
				}
				$args1 = 'value="printInvoice"';
				$args2 = 'value="printSlip"';
			}
			else {
				$args1 = 'disabled="disabled"';
				$args2 = 'disabled="disabled"';
			}

			$GLOBALS['OrderActionOptions'] .= '<option ' . $args1 . '>' . GetLang('PrintInvoicesSelected') . '</option>';
			$GLOBALS['OrderActionOptions'] .= '<option ' . $args2 . '>' . GetLang('PrintPackingSlipsSelected') . '</option>';

			if(!gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS[base64_decode('SGlkZUV4cG9ydA==')] = "none";
				$GLOBALS[B('ZGlzYWJsZU9yZGVyRXhwb3J0cw==')] = true;
			}

			$GLOBALS['OrderActionOptions'] .= '<option disabled="disabled"></option><optgroup label="' . GetLang('BulkOrderStatus') . '">';

			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]order_status ORDER BY statusorder ASC");
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$GLOBALS['OrderActionOptions'] .= '<option value="updateStatus' . $row['statusid'] . '">' . $row['statusdesc'] . '</option>';
			}
			$GLOBALS['OrderActionOptions'] .= '</optgroup>';

			if (!isset($_REQUEST['searchId'])) {
				$_REQUEST['searchId'] = 0;
			}

			// Get the custom search as option fields
			$GLOBALS['CustomSearchOptions'] = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->GetSearchesAsOptions($_REQUEST['searchId'], $num_custom_searches, "AllOrders", "viewOrders", "customOrderSearch");

			// the above is pre-formatted, need it as raw data for the iphone
			$GLOBALS['customSearchList'] = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->GetSearches();

			if(!isset($_REQUEST['searchId'])) {
				$GLOBALS['HideDeleteCustomSearch'] = "none";
			} else {
				$GLOBALS['CustomSearchId'] = (int)$_REQUEST['searchId'];
			}

			$GLOBALS['OrderIntro'] = GetLang('ManageOrdersIntro');
			$GLOBALS['Message'] = '';

			// No orders
			if($numOrders == 0) {
				$GLOBALS['DisplayGrid'] = "none";

				if(count($searchGet) > 1) {
					// Performing a search of some kind
					if ($MsgDesc == "") {
						$GLOBALS['Message'] = MessageBox(GetLang('NoOrderResults'), MSG_ERROR);
						if ($numDeletedOrders) {
							$deletedUrl = $searchGet;
							$deletedUrl['searchDeletedOrders'] = 'only';
							$deletedUrl = 'index.php?' . http_build_query($deletedUrl);

							$GLOBALS['Message'] .= MessageBox(GetLang('DeletedOrdersMatchedYourSearch', array(
								'viewDeletedOrdersUrl' => $deletedUrl,
							)), MSG_INFO, 'MessageBoxTrash');
							unset($deletedUrl);
						}
					}
				} else {
					$GLOBALS['Message'] = MessageBox(GetLang('NoOrders'), MSG_SUCCESS);
					$GLOBALS['DisplaySearch'] = "none";
				}

				unset($searchGet);
			}

			if($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			$flashMessages = GetFlashMessages();
			if(is_array($flashMessages)) {
				foreach($flashMessages as $flashMessage) {
					$GLOBALS['Message'] .= MessageBox($flashMessage['message'], $flashMessage['type']);
				}
			}

			if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Orders)) {
				$GLOBALS['ExportAction'] = "index.php?ToDo=startExport&t=orders";
				if (isset($GLOBALS['CustomSearchId']) && $GLOBALS['CustomSearchId'] != '0') {
					$GLOBALS['ExportAction'] .= "&searchId=" . $GLOBALS['CustomSearchId'];
				}
				else {
					$params = $_GET;
					unset($params['ToDo']);

					if (!empty($params)) {
						$GLOBALS['ExportAction'] .= "&" . http_build_query($params);
					}
				}
			}

			$selectOrder = '';
			if (!empty($_GET['selectOrder']) && isId($_GET['selectOrder'])) {
				$selectOrder = 'QuickView(' . $_GET['selectOrder'] . ');';
			}
			$GLOBALS['SelectOrder'] = $selectOrder;

			// Used for iPhone interface
			$GLOBALS['OrderStatusOptions'] = $this->GetOrderStatusOptions();

			if ($numOrders && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Orders)) {
				$exportAction = "index.php?ToDo=startExport&t=orders";
				if (isset($GLOBALS['CustomSearchId']) && $GLOBALS['CustomSearchId'] != '0') {
					$exportAction .= "&searchId=" . $GLOBALS['CustomSearchId'];
				}
				else {
					$params = $_GET;
					unset($params['ToDo']);

					if (!empty($params)) {
						$exportAction .= "&" . http_build_query($params);
					}
				}

				$searchQueryForExport = $_POST + $_GET;
				foreach ($searchQueryForExport as $index => $value) {
					if ($value === '') {
						unset($searchQueryForExport[$index]);
					}
				}
				unset($searchQueryForExport['ToDo'], $searchQueryForExport['SubmitButton1'], $searchQueryForExport['sortField'], $searchQueryForExport['sortOrder'], $searchQueryForExport['SearchButton_x'], $searchQueryForExport['SearchButton_y']);
				$searchQueryForExport = isc_json_encode($searchQueryForExport);

				$orderExportMenu = array();

				$orderExportMenu[] = array(
					array(
						'backgroundImage' => 'images/export.gif',
						'label' => GetLang('EmailIntegrationExportToFile'),
						'class' => 'exportMenuLink',
						'href' => $exportAction,
					),
				);

				$emailModules = ISC_EMAILINTEGRATION::getConfiguredModules();
				foreach ($emailModules as /** @var ISC_EMAILINTEGRATION */$emailModule) {
					if (!$emailModule->supportsBulkExport()) {
						// not all modules have to support bulk exports
						continue;
					}

					$orderExportMenuModules[] = array(
						'backgroundImage' => '../modules/' . str_replace('_', '/', $emailModule->GetId()) . '/images/16x16.png',
						'label' => GetLang('EmailIntegrationExportToModule', array('module' => $emailModule->GetName())),
						'href' => 'javascript:Interspire_EmailIntegration_ModuleExportMachine.start({ exportType: "Order", exportModule: "' . $emailModule->GetId() . '", exportSearch: ' . $searchQueryForExport . ' });',
					);
				}

				if (!empty($orderExportMenuModules)) {
					$orderExportMenu[] = $orderExportMenuModules;

					$this->engine->bodyScripts[] = '../javascript/fsm.js';
					$this->engine->bodyScripts[] = '../javascript/jquery/plugins/disabled/jquery.disabled.js';
					$this->engine->bodyScripts[] = '../javascript/ajaxDataProvider.js';
					$this->engine->bodyScripts[] = 'script/emailintegration.js';
					$this->engine->bodyScripts[] = 'script/emailintegration.export.js';
				}

				$this->template->assign('orderExportMenu', $orderExportMenu);
			} else {
				$this->template->assign('disableOrderExports', true);
			}

			$this->template->display('orders.manage.tpl');
		}

		/**
		* Gets a list of orders as a result set
		*
		* @param int $Start The starting position to retrieve orders from
		* @param string $SortField The field to sort the orders on
		* @param string $SortOrder The order in which to sort the orders by, ASC or DESC
		* @param variable $NumOrders $NumOrders will be set to the number of orders that are retrieved
		* @param mixed $limit The max orders to retrieve, or false to not limit
		* @param variable $numDeletedOrders will be set to the number of deleted orders that match the provided query
		* @return resource The database result set of orders
		*/
		public function _GetOrderList($Start, $SortField, $SortOrder, &$NumOrders, $limit = ISC_ORDERS_PER_PAGE, &$numDeletedOrders = 0)
		{
			$extraFields = '';
			$extraJoins = '';

			if(isset($_REQUEST['couponCode']) && trim($_REQUEST['couponCode']) != '') {
				$extraFields = 'DISTINCT(co.ordcouporderid), ';
				$extraJoins = sprintf("INNER JOIN [|PREFIX|]order_coupons co ON (co.ordcouporderid=o.orderid AND co.ordcouponcode='%s')", $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['couponCode']));
			}

			// Return an array containing details about orders.
			$query = sprintf("
				SELECT %so.*, c.*, s.statusdesc AS ordstatustext, CONCAT(custconfirstname, ' ', custconlastname) AS custname,
					(
						SELECT COUNT(messageid)
						FROM [|PREFIX|]order_messages
						WHERE messageorderid=orderid
					) AS nummessages,
					(
						SELECT COUNT(messageid)
						FROM [|PREFIX|]order_messages
						WHERE messageorderid=orderid AND messagestatus != 'read'
					) AS numunreadmessages,
					(
						SELECT COUNT(messageid)
						FROM [|PREFIX|]order_messages
						WHERE messageorderid=orderid AND messagefrom='customer' AND messagestatus='unread'
					) AS newmessages
				FROM [|PREFIX|]orders o
				LEFT JOIN [|PREFIX|]customers c ON (o.ordcustid=c.customerid)
				LEFT JOIN [|PREFIX|]order_status s ON (s.statusid=o.ordstatus)
				%s", $extraFields, $extraJoins);

			$countQuery = "SELECT COUNT(o.orderid) FROM [|PREFIX|]orders o";
			if (!empty($extraJoins)) {
				$countQuery .= ' '.$extraJoins;
			}

			if(isset($_REQUEST['newMessages'])) {
				$countQuery .= " LEFT JOIN [|PREFIX|]order_messages ON (messageorderid=orderid) AND messagefrom='customer' AND messagestatus='unread'";
			}

			if (Interspire_Request::request('searchDeletedOrders', 'no') == 'no' && !is_numeric(Interspire_Request::request('searchQuery', ''))) {
				// setup to also search for deleted orders using the same parameters
				$deletedQuery = true;
				$deletedCountQuery = $countQuery;
				$deletedRequest = $_REQUEST;
				$deletedRequest['searchDeletedOrders'] = 'only';
			} else {
				// the current search scope includes deleted orders, don't bother searching for them again
				$deletedQuery = false;
				$numDeletedOrders = 0;
			}

			// Are there any search parameters?
			$res = $this->BuildWhereFromVars($_REQUEST);
			$query .= " WHERE 1=1 " . $res["query"];
			$countQuery .= " " . $res['count'] . " WHERE 1=1 " . $res['query'];

			if ($deletedQuery) {
				$res = $this->BuildWhereFromVars($deletedRequest);
				$deletedCountQuery .= " " . $res['count'] . " WHERE 1=1 " . $res['query'];
				$deletedCountQuery .= ' AND deleted = 1';
			}

			// Only those with new messages?
			if (isset($_REQUEST['newMessages'])) {
				// @todo should this also adjust countQuery?
				$query .= " HAVING newmessages >= 1";
			}

			// How many results do we have?
			$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
			$NumOrders = (int)$GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			if ($deletedQuery) {
				$deletedResult = $this->db->Query($deletedCountQuery);
				$numDeletedOrders = (int)$this->db->FetchOne($deletedResult);
			}

			// Add the limit
			$query .= sprintf(" order by %s %s", $SortField, $SortOrder);
			if($limit !== false) {
				$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($Start, $limit);
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) == 0) {
				$GLOBALS['HideViewAllLink'] = 'none';
			}

			return $result;
		}

		/**
		* Builds a where statement for order listing based on values in an array
		*
		* @param mixed $array
		* @return mixed
		*/
		public function BuildWhereFromVars($array)
		{
			$queryWhere = "";
			$countQuery = "";

			// Is this a custom search?
			if(!empty($array['searchId'])) {
				$this->_customSearch = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($array['searchId']);
				$array = array_merge($array, (array)$this->_customSearch['searchvars']);
			}

			if(isset($array['orderId']) && $array['orderId'] != '') {
				// this should search deleted orders
				$queryWhere .= " AND orderid='".(int)$array['orderId']."'";
				return array("query" => $queryWhere,  "count" => $countQuery);
			}

			if(isset($array['customerId']) && $array['customerId'] != '') {
				// hide deleted orders when viewing orders for a customer
				$queryWhere .= " AND ordcustid='".(int)$array['customerId']."' AND deleted = 0 ";
				return array("query" => $queryWhere,  "count" => $countQuery);
			}

			// defaults for un/deleted searching
			$searchUndeletedOrders = true;
			$searchDeletedOrders = false;

			if (isset($array['searchDeletedOrders'])) {
				switch (strtolower($array['searchDeletedOrders'])) {
					case 'both':
						$searchDeletedOrders = true;
						break;

					case 'only':
						$searchUndeletedOrders = false;
						$searchDeletedOrders = true;
						break;
				}
			}

			if(isset($array['orderStatus']) && $array['orderStatus'] != "") {
				$order_status = $GLOBALS['ISC_CLASS_DB']->Quote((int)$array['orderStatus']);
				$queryWhere .= sprintf(" AND ordstatus='%d'", $order_status);
			}
			// Otherwise, only fetch complete orders
			else {
				$queryWhere .= " AND ordstatus > 0";
			}

			if(isset($array['searchQuery']) && $array['searchQuery'] != "") {
				$search_query = $GLOBALS['ISC_CLASS_DB']->Quote($array['searchQuery']);
				// only limit results to un/deleted if the search query is not numeric - otherwise it should search for order ids regardless
				if (!is_numeric($search_query)) {
					if (!$searchDeletedOrders) {
						$queryWhere .= " AND deleted = 0";
					} else if (!$searchUndeletedOrders) {
						$queryWhere .= " AND deleted = 1";
					}
				}
				$queryWhere .= " AND (
					orderid='".(int)$search_query."'
					OR ordpayproviderid='".$search_query."'
					OR CONCAT(custconfirstname, ' ', custconlastname) LIKE '%".$search_query."%'
					OR CONCAT(ordbillfirstname, ' ', ordbilllastname) LIKE '%".$search_query."%'
					OR custconemail    LIKE '%".$search_query."%'
					OR ordbillstreet1  LIKE '%".$search_query."%'
					OR ordbillstreet2  LIKE '%".$search_query."%'
					OR ordbillsuburb   LIKE '%".$search_query."%'
					OR ordbillstate    LIKE '%".$search_query."%'
					OR ordbillzip      LIKE '%".$search_query."%'
					OR ordbillcountry  LIKE '%".$search_query."%'
				) ";
				$countQuery .= " LEFT JOIN [|PREFIX|]customers c ON (o.ordcustid=c.customerid)";
			} else {
				// no search query specified, show/hide deleted orders by default as specified by orderDeleted parameter above
				if (!$searchDeletedOrders) {
					$queryWhere .= " AND deleted = 0";
				} else if (!$searchUndeletedOrders) {
					$queryWhere .= " AND deleted = 1";
				}
			}

			if(isset($array['paymentMethod']) && $array['paymentMethod'] != "") {
				$payment_method = $GLOBALS['ISC_CLASS_DB']->Quote($array['paymentMethod']);
				$queryWhere .= sprintf(" AND orderpaymentmodule='%s'", $payment_method);
			}

			if(isset($_REQUEST['shippingMethod']) && $_REQUEST['shippingMethod'] != "") {
				$shipping_method = $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['shippingMethod']);
				$queryWhere .= sprintf(" AND (
					SELECT order_id
					FROM [|PREFIX|]order_shipping
					WHERE module='%s'
					LIMIT 1
				)", $shipping_method);
			}

			if(isset($array['ebayOrderId'])) {
				if ($array['ebayOrderId'] == -1) {
					$queryWhere .= " AND o.ebay_order_id != 0";
				}
				else {
					$queryWhere .= " AND o.ebay_order_id = " . (int)$array['ebayOrderId'];
				}
			}

			if(isset($array['ebayItemId'])) {
				$ebayItemId = $GLOBALS['ISC_CLASS_DB']->Quote($array['ebayItemId']);
				$queryWhere .= " AND (
					SELECT opf.orderprodid
					FROM [|PREFIX|]order_products opf
					WHERE o.orderid=opf.orderorderid AND opf.ebay_item_id ='".$ebayItemId."'
				)";
			}

			if(isset($array['orderFrom']) && isset($array['orderTo']) && $array['orderFrom'] != "" && $array['orderTo'] != "") {
				$order_from = (int)$array['orderFrom'];
				$order_to = (int)$array['orderTo'];
				$queryWhere .= sprintf(" AND (orderid >= '%d' and orderid <= '%d')", $GLOBALS['ISC_CLASS_DB']->Quote($order_from), $GLOBALS['ISC_CLASS_DB']->Quote($order_to));
			}
			else if(isset($array['orderFrom']) && $array['orderFrom'] != "") {
				$order_from = (int)$array['orderFrom'];
				$queryWhere .= sprintf(" AND orderid >= '%d'", $order_from);
			}
			else if(isset($array['orderTo']) && $array['orderTo'] != "") {
				$order_to = (int)$array['orderTo'];
				$queryWhere .= sprintf(" AND orderid <= '%d'", $order_to);
			}

			if(isset($array['totalFrom']) && $array['totalFrom'] != "" && isset($array['totalTo']) && $array['totalTo'] != "") {
				$from_total = $array['totalFrom'];
				$to_total = $array['totalTo'];
				$queryWhere .= sprintf(" AND total_inc_tax >= '%s' and total_inc_tax <= '%s'", $GLOBALS['ISC_CLASS_DB']->Quote($from_total), $GLOBALS['ISC_CLASS_DB']->Quote($to_total));
			}
			else if(isset($array['totalFrom']) && $array['totalFrom'] != "") {
				$from_total = $array['totalFrom'];
				$queryWhere .= sprintf(" AND total_inc_tax >= '%s'", $GLOBALS['ISC_CLASS_DB']->Quote($from_total));
			}
			else if(isset($array['totalTo']) && $array['totalTo'] != "") {
				$to_total = $array['totalTo'];
				$queryWhere .= sprintf(" AND total_inc_tax <= '%s'", $GLOBALS['ISC_CLASS_DB']->Quote($to_total));
			}

			// Limit results to a particular date range
			if(isset($array['dateRange']) && $array['dateRange'] != "") {
				$range = $array['dateRange'];
				switch($range) {
					// Orders within the last day
					case "today":
						$from_stamp = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d"), isc_date("Y"));
						break;
					// Orders received in the last 2 days
					case "yesterday":
						$from_stamp = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d")-1, isc_date("Y"));
						$to_stamp = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d"), isc_date("Y"));
						break;
					// Orders received in the last 24 hours
					case "day":
						$from_stamp = time()-60*60*24;
						break;
					// Orders received in the last 7 days
					case "week":
						$from_stamp = time()-60*60*24*7;
						break;
					// Orders received in the last 30 days
					case "month":
						$from_stamp = time()-60*60*24*30;
						break;
					// Orders received this month
					case "this_month":
						$from_stamp = isc_gmmktime(0, 0, 0, isc_date("m"), 1, isc_date("Y"));
						break;
					// Orders received this year
					case "this_year":
						$from_stamp = isc_gmmktime(0, 0, 0, 1, 1, isc_date("Y"));
						break;
					// Custom date
					default:
						if(isset($array['fromDate']) && $array['fromDate'] != "") {
							$from_date = urldecode($array['fromDate']);
							$from_data = explode("/", $from_date);
							$from_stamp = isc_gmmktime(0, 0, 0, $from_data[0], $from_data[1], $from_data[2]);
						}
						if(isset($array['toDate']) && $array['toDate'] != "") {
							$to_date = urldecode($array['toDate']);
							$to_data = explode("/", $to_date);
							$to_stamp = isc_gmmktime(23, 59, 59, $to_data[0], $to_data[1], $to_data[2]);
						}
				}

				if (!isset($array['SearchByDate']) || $array['SearchByDate'] == 0) {
					if(isset($from_stamp)) {
						$queryWhere .= " AND orddate >= '".(int)$from_stamp."'";
					}
					if(isset($to_stamp)) {
						$queryWhere .= " AND orddate <='".(int)$to_stamp."'";
					}
				} else if ($array['SearchByDate'] == 1) {
					if(isset($from_stamp)) {
						$queryWhere .= " AND (
							SELECT opf.orderprodid
							FROM [|PREFIX|]order_products opf
							WHERE o.orderid=opf.orderorderid AND opf.ordprodeventdate >='".(int)$from_stamp."'
						)";
					}
					if(isset($to_stamp)) {
						$queryWhere .= " AND (
							SELECT opt.orderprodid
							FROM [|PREFIX|]order_products opt
							WHERE o.orderid=opt.orderorderid AND opt.ordprodeventdate <='".(int)$to_stamp."'
						)";
					}
				} else if ($array['SearchByDate'] == 2) {
					if(isset($from_stamp)) {
						$queryWhere .= " AND (orddate >= '".(int)$from_stamp."' OR (
							SELECT opf.orderprodid
							FROM [|PREFIX|]order_products opf
							WHERE o.orderid=opf.orderorderid AND opf.ordprodeventdate >='".(int)$from_stamp."'
						))";
					}

					if(isset($to_stamp)) {
						$queryWhere .= " AND (orddate <= '".(int)$to_stamp."' OR (
							SELECT opt.orderprodid
							FROM [|PREFIX|]order_products opt
							WHERE o.orderid=opt.orderorderid AND opt.ordprodeventdate <='".(int)$to_stamp."'
						))";
					}
					if(isset($to_stamp)) {
						$queryWhere .= " AND orddate <='".(int)$from_stamp."'";
					}
				}
			}

			// Orders which contain a particular product?
			if(isset($array['productId'])) {
				$queryWhere .= " AND (
					SELECT sp.orderprodid
					FROM [|PREFIX|]order_products sp
					WHERE sp.ordprodid='".(int)$array['productId']."' AND sp.orderorderid=o.orderid
					LIMIT 1
				)";
			}

			// Orders by product name
			if(isset($array['productName'])) {
				$queryWhere .= " AND (
					SELECT sp.orderprodid
					FROM [|PREFIX|]order_products sp
					WHERE sp.ordprodname LIKE '%".$GLOBALS['ISC_CLASS_DB']->Quote($array['productName'])."%' AND sp.orderorderid=o.orderid
					LIMIT 1
				)";
			}

			// orders that do or do not contain pre-order products
			if (isset($_REQUEST['preorders']) &&  !(in_array('0', $_REQUEST['preorders']) && in_array('1', $_REQUEST['preorders']))) {
				// preorders is set but not set to show both - filter accordingly (if it is not set or it is set to show both no filtering is necessary)
				$queryWhere .= " AND (
					SELECT
						COUNT(*)
					FROM
						[|PREFIX|]order_products sop,
						[|PREFIX|]products sp
					WHERE
						sop.orderorderid = o.orderid
						AND sp.productid = sop.ordprodid
						AND sp.prodpreorder = 1
					) ";

				if (in_array('1', $_REQUEST['preorders'])) {
					$queryWhere .= " > 0";
				} else {
					$queryWhere .= " = 0";
				}
			}

			return array("query" => $queryWhere,  "count" => $countQuery);
		}

		/**
		 * Get all the available order status as html options (without the <select> tags)
		 *
		 * @param integer $SelectedStatus The status to mark as selected
		 *
		 * @return string The html with the option tags in it
		 */
		public function GetOrderStatusOptions($SelectedStatus = null)
		{
			// Get all order status options from the database
			static $statuses = null;
			$output = "";

			// Only do the database query the first time
			if ($statuses === null) {
				$statuses = array();
				if($SelectedStatus === 0 || $SelectedStatus === '0') {
					$statuses[] = array(
						"statusid" => 0,
						"statusdesc" => GetLang('Incomplete')
					);
				}
				$query = "select statusid, statusdesc from [|PREFIX|]order_status order by statusorder asc";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$statuses[] = $row;
				}
			}

			foreach ($statuses as $row) {
				// Only show the 0 status if it's our current status
				if($row['statusid'] == 0 && $SelectedStatus != 0) {
					continue;
				}
				if ($row['statusid'] == $SelectedStatus) {
					$sel = 'selected="selected"';
				} else {
					$sel = '';
				}
				$output .= sprintf("<option value='%d' %s>%s</option>", $row['statusid'], $sel, $row['statusdesc']);
			}

			return $output;
		}

		/**
		*	Get a list of order messages and return them as an array. Also pass
		*	back the number of new and total messages to the 2nd and 3rd reference params
		*/
		protected function GetOrderMessages($OrderId, $SortField, $SortOrder, &$NewMessages, &$TotalMessages)
		{
			$messages = array();
			$query = sprintf("select *, (select username from [|PREFIX|]users where pk_userid=staffuserid) as uname, (select userfirstname from [|PREFIX|]users where pk_userid=staffuserid) as ufname, (select userlastname from [|PREFIX|]users where pk_userid=staffuserid) as ulname from [|PREFIX|]order_messages where messageorderid='%d' order by %s %s", $GLOBALS['ISC_CLASS_DB']->Quote($OrderId), $SortField, $SortOrder);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				array_push($messages, $row);

				if($row['messagefrom'] == "customer" && $row['messagestatus'] == "unread") {
					$NewMessages++;
				}

				$TotalMessages++;
			}

			// If we're on the iPhone then reset the message stack to 0 unread
			if(defined("IS_IPHONE")) {
				$updatedMessage = array(
					"messagestatus" => "read"
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("order_messages", $updatedMessage, "messageorderid='".$GLOBALS['ISC_CLASS_DB']->Quote($OrderId)."'");
			}

			return $messages;
		}

		protected function ViewOrderMessages($MsgDesc = "", $MsgStatus = "")
		{
			$new_messages = 0;
			$total_messages = 0;
			$GLOBALS['MessageGrid'] = "";
			$GLOBALS['Indent'] = 0;

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			if(isset($_GET['sortField'])) {
				$sort_field = $_GET['sortField'];
			} else {
				$sort_field = "messageid";
			}

			if(isset($_GET['sortOrder'])) {
				$sort_order = $_GET['sortOrder'];
			} else {
				$sort_order = "asc";
			}

			if(isset($_REQUEST['orderId'])) {
				$order_id = (int)$_REQUEST['orderId'];
				$GLOBALS['OrderId'] = $order_id;

				$order = GetOrder($order_id, null, null, true);

				$message_list = $this->GetOrderMessages($order_id, $sort_field, $sort_order, $new_messages, $total_messages);

				if($total_messages == 1) {
					$lang = "OrderMessagesIntro1";
				} else {
					$lang = "OrderMessagesIntroX";
				}

				$GLOBALS['MessageIntro'] = sprintf(GetLang($lang), $total_messages, $new_messages, $order_id);

				if (!empty($message_list)) {
					foreach($message_list as $message) {
						$GLOBALS['MessageId'] = $message['messageid'];
						$GLOBALS['Subject'] = $message['subject'];
						$GLOBALS['MessageDate'] = isc_date(GetConfig('ExtendedDisplayDateFormat'), $message['datestamp']);

						// If the message isn't read then we'll wrap the subject in bold tags
						if($message['messagestatus'] == "unread" && $message['messagefrom'] == "customer") {
							$GLOBALS['Subject'] = sprintf("<strong>%s</strong>", $GLOBALS['Subject']);
						}

						$GLOBALS['OrderMessage'] = nl2br(isc_html_escape($message['message']));

						if($message['messagefrom'] == "customer") {
							$GLOBALS['OrderFrom'] = GetLang('FromCustomer');
						}
						else {
							if($message['ufname'] != "" || $message['ulname'] != "") {
								$GLOBALS['OrderFrom'] = trim(sprintf("%s %s", $message['ufname'], $message['ulname']));
							} else {
								$GLOBALS['OrderFrom'] = $message['uname'];
							}
						}

						if($message['messagefrom'] == "admin") {
							$GLOBALS['MessageStatus'] = GetLang('NA');
						}
						else if($message['messagefrom'] == "customer" && $message['messagestatus'] == "unread") {
							$GLOBALS['MessageStatus'] = sprintf(GetLang('MessageUnRead'), $GLOBALS['ShopPath'], $order_id, $message['messageid']);
						}
						else {
							$GLOBALS['MessageStatus'] = sprintf(GetLang('MessageRead'), $GLOBALS['ShopPath'], $order_id, $message['messageid']);
						}

						// Is the message flagged?
						if($message['isflagged'] == "0") {
							$GLOBALS['FlagState'] = "1";
							$GLOBALS['HideFlag'] = "none";
							$GLOBALS['FlagText'] = GetLang('Flag');
						}
						else {
							$GLOBALS['FlagState'] = "0";
							$GLOBALS['HideFlag'] = "";
							$GLOBALS['FlagText'] = GetLang('ClearFlag');
						}

						$GLOBALS['MessageGrid'] .= $this->template->render('message.manage.row.tpl');

						// If they're sorted by default fields then indent each message
						if($sort_field == "messageid" && $sort_order == "asc") {
							$GLOBALS['Indent'] += 20;
						}
					}
				}
				else {
					$GLOBALS['DisplayGrid'] = "none";
					$GLOBALS['DisableDelete'] = "disabled readonly";
				}

				$GLOBALS['MessageSubject'] = $this->GetRecentCustomerMessage($order_id);
				$GLOBALS['ViewOrderMessages'] = sprintf(GetLang('ViewOrderMessages'), $order_id);
				$this->template->display('ordermessages.manage.tpl');
			}
		}

		protected function UpdateOrderMessageStatus()
		{
			if (isset($_GET['orderId']) && isset($_GET['messageId']) && isset($_GET['status'])) {
				$order_id = (int)$_GET['orderId'];
				$message_id = (int)$_GET['messageId'];
				$status = $_GET['status'];

				// Does this user have permission to view this order?
				$order = GetOrder($order_id);
				if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $order['ordvendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				$updatedMessage = array(
					"messagestatus" => $status
				);

				if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery("order_messages", $updatedMessage, "messageid='".$GLOBALS['ISC_CLASS_DB']->Quote($message_id)."'")) {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($message_id, $_GET['status']);

					$this->ViewOrderMessages(sprintf(GetLang('OrderMessageStatusChanged'), $status), MSG_SUCCESS);
				} else {
					$this->ViewOrderMessages(sprintf(GetLang('OrderMessageStatusChangeFailed'), $status), MSG_ERROR);
				}
			}
		}

		protected function FlagOrderMessage()
		{
			if(isset($_GET['flagState']) && isset($_GET['orderId']) && isset($_GET['messageId'])) {
				$flag_state = (int)$_GET['flagState'];
				$order_id = (int)$_GET['orderId'];

				// Does this user have permission to view this order?
				$order = GetOrder($order_id, null, null, true);
				if (!$order) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				if ($order['deleted']) {
					FlashMessage(GetLang('OrderDeletedGeneralNotice'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				$message_id = (int)$_GET['messageId'];

				$updatedMessage = array(
					"isflagged" => $flag_state
				);
				if($GLOBALS['ISC_CLASS_DB']->UpdateQuery("order_messages", $updatedMessage, "messageid='".$GLOBALS['ISC_CLASS_DB']->Quote($message_id)."'")) {
					if($flag_state == "0") {
						// Log this action
						$GLOBALS['ISC_CLASS_LOG']->LogAdminAction("cleared", $message_id);

						$lang = "OrderFlagCleared";
					}
					else {
						// Log this action
						$GLOBALS['ISC_CLASS_LOG']->LogAdminAction("flagged", $message_id);

						$lang = "OrderFlaggedOK";
					}

					$this->ViewOrderMessages(GetLang($lang), MSG_SUCCESS);
				}
				else {
					$this->ViewOrderMessages(sprintf(GetLang('OrderFlaggedFailed'), $flag_state), MSG_ERROR);
				}
			}
		}

		protected function DeleteOrderMessages()
		{
			if(isset($_POST['orderId']) && is_array($_POST['messages'])) {
				$order_id = (int)$_POST['orderId'];

				// Does this user have permission to view this order?
				$order = GetOrder($order_id, null, null, true);
				if (!$order) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				if ($order['deleted']) {
					FlashMessage(GetLang('OrderDeletedGeneralNotice'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				$message_ids = implode("','", $GLOBALS['ISC_CLASS_DB']->Quote($_POST['messages']));
				$query = sprintf("delete from [|PREFIX|]order_messages where messageorderid='%d' and messageid in('%s')", $GLOBALS['ISC_CLASS_DB']->Quote($order_id), $message_ids);

				if($GLOBALS['ISC_CLASS_DB']->Query($query)) {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($order_id, count($_POST['messages']));

					$this->ViewOrderMessages(GetLang('OrderMessagesDeletedOK'), MSG_SUCCESS);
				}
				else {
					$this->ViewOrderMessages(GetLang('OrderMessagesDeletedFailed'), MSG_ERROR);
				}
			}
		}

		public function GetCustomerNameByOrderId($OrderId)
		{
			$query = sprintf("select ordcustid, (select concat(custconfirstname, ' ', custconlastname) from [|PREFIX|]customers where customerid=ordcustid) as custname, (select custconemail from [|PREFIX|]customers where customerid=ordcustid) as custemail  from [|PREFIX|]orders where orderid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($OrderId));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				return sprintf("%s <%s>", $row['custname'], $row['custemail']);
			} else {
				return sprintf(GetLang('CustomerForOrderX'), $OrderId);
			}
		}

		public function GetCustomerEmailByOrderId($OrderId)
		{
			$query = sprintf("select ordcustid, (select custconemail from [|PREFIX|]customers where customerid=ordcustid) as custemail  from [|PREFIX|]orders where orderid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($OrderId));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				return $row['custemail'];
			} else {
				return "";
			}
		}

		/**
		*	Get the subject of the most recent customer message. If none is available just use "Re: Order #xxxx"
		*/
		public function GetRecentCustomerMessage($OrderId)
		{
			$query = sprintf("select subject from [|PREFIX|]order_messages where messageorderid='%d' and messagefrom='customer' order by messageid desc limit 1", $GLOBALS['ISC_CLASS_DB']->Quote($OrderId));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				return sprintf(GetLang('OrderMessageRe'), $row['subject']);
			} else {
				return sprintf(GetLang('OrderMessageDefaultSubject'), $OrderId);
			}
		}

		protected function CreateOrderMessage()
		{
			if(isset($_GET['orderId'])) {
				$order_id = (int)$_GET['orderId'];

				// Does this user have permission to view this order?
				$order = GetOrder($order_id, null, null, true);
				if (!$order) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				if ($order['deleted']) {
					FlashMessage(GetLang('OrderDeletedGeneralNotice'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				$GLOBALS['OrderId'] = $order_id;
				$GLOBALS['FormAction'] = "saveNewOrderMessage";
				$GLOBALS['Title'] = GetLang('CreateMessage');
				$GLOBALS['Intro'] = GetLang('CreateMessageIntro');
				$GLOBALS['ButtonAction'] = GetLang('SendMessage');

				$GLOBALS['MessageToFrom'] = GetLang('MessageTo');
				$GLOBALS['MessageTo'] = $this->GetCustomerNameByOrderId($order_id);
				$GLOBALS['MessageSubject'] = $this->GetRecentCustomerMessage($order_id);

				$this->template->display('ordermessage.form.tpl');
			}
		}

		protected function SaveNewOrderMessage()
		{
			if(isset($_POST['orderId']) && isset($_POST['subject']) && isset($_POST['message'])) {
				$order_id = (int)$_POST['orderId'];

				// Does this user have permission to view this order?
				$order = GetOrder($order_id);
				if (!$order) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				$subject = $_POST['subject'];
				$message = $_POST['message'];

				// Save the message to the database first
				$newMessage = array(
					"messagefrom" => "admin",
					"subject" => $subject,
					"message" => $message,
					"datestamp" => time(),
					"messageorderid" => $order_id,
					"messagestatus" => "unread",
					"staffuserid" => $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUserId(),
					"isflagged" => 0
				);
				$message_id =  $GLOBALS['ISC_CLASS_DB']->InsertQuery("order_messages", $newMessage);
				if($message_id) {
					$message_id = $GLOBALS['ISC_CLASS_DB']->LastId();

					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($message_id, $order_id);

					// Now send a notification email to the customer
					$customer_email = $this->GetCustomerEmailByOrderId($order_id);

					// Create a new email API object to send the email
					$store_name = GetConfig('StoreName');

					$emailTemplate = FetchEmailTemplateParser();
					$emailTemplate->SetTemplate("ordermessage_notification");
					$message = $emailTemplate->ParseTemplate(true);

					require_once(ISC_BASE_PATH . "/lib/email.php");
					$obj_email = GetEmailClass();
					$obj_email->Set('CharSet', GetConfig('CharacterSet'));
					$obj_email->From(GetConfig('OrderEmail'), $store_name);
					$obj_email->Set("Subject", $subject);
					$obj_email->AddBody("html", $message);
					$obj_email->AddRecipient($customer_email, "", "h");
					$email_result = $obj_email->Send();

					if($email_result['success']) {
						$this->ViewOrderMessages(GetLang('OrderMessageSentOK'), MSG_SUCCESS);
					}
					else {
						$this->ViewOrderMessages(GetLang('OrderMessagesSentEmailFailed'), MSG_ERROR);
					}
				}
				else {
					$this->ViewOrderMessages(GetLang('OrderMessagesSentFailed'), MSG_ERROR);
				}
			}
		}

		protected function EditOrderMessage()
		{
			if(isset($_GET['orderId']) && isset($_GET['messageId'])) {
				$order_id = (int)$_GET['orderId'];

				// Does this user have permission to view this order?
				$order = GetOrder($order_id, null, null, true);
				if (!$order) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				if ($order['deleted']) {
					FlashMessage(GetLang('OrderDeletedGeneralNotice'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				$message_id = (int)$_GET['messageId'];
				$query = sprintf("select * from [|PREFIX|]order_messages where messageid='%d' and messageorderid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($message_id), $GLOBALS['ISC_CLASS_DB']->Quote($order_id));
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				if($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$GLOBALS['OrderId'] = $order_id;
					$GLOBALS['FormAction'] = "saveUpdatedOrderMessage";
					$GLOBALS['Title'] = GetLang('EditMessage');
					$GLOBALS['Intro'] = GetLang('EditMessageIntro');
					$GLOBALS['ButtonAction'] = GetLang('SaveMessage');
					$GLOBALS['MessageId'] = $message_id;
					$GLOBALS['MessageTo'] = $this->GetCustomerNameByOrderId($order_id);
					$GLOBALS['MessageSubject'] = $row['subject'];
					$GLOBALS['MessageContent'] = str_replace("<br />", "\n", $row['message']);

					if($row['messagefrom'] == "customer") {
						$GLOBALS['MessageToFrom'] = GetLang('MessageFrom');
					} else {
						$GLOBALS['MessageToFrom'] = GetLang('MessageTo');
					}

					$this->template->display('ordermessage.form.tpl');
				}
				else {
					$this->ViewOrderMessages(GetLang('OrderMessageSentOK'), MSG_SUCCESS);
				}
			}
		}

		protected function SavedUpdatedOrderMessage()
		{
			if(isset($_POST['orderId']) && isset($_POST['messageId']) && isset($_POST['subject']) && isset($_POST['message'])) {
				$order_id = (int)$_POST['orderId'];

				// Does this user have permission to view this order?
				$order = GetOrder($order_id);
				if (!$order) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				$message_id = (int)$_POST['messageId'];
				$subject = $_POST['subject'];
				// $message = str_replace("\n", "<br />", $_POST['message']);
				$message = $_POST['message'];
				$updatedMessage = array(
					"subject" => $subject,
					"message" => $message
				);
				if($GLOBALS['ISC_CLASS_DB']->UpdateQuery("order_messages", $updatedMessage, "messageid='".$GLOBALS['ISC_CLASS_DB']->Quote($message_id)."'")) {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($message_id, $order_id);

					$this->ViewOrderMessages(GetLang('OrderMessageUpdatedOK'), MSG_SUCCESS);
				}
				else {
					$this->ViewOrderMessages(GetLang('OrderMessagesUpdatedFailed'), MSG_ERROR);
				}
			}
		}

		protected function SearchOrders()
		{
			$GLOBALS['OrderPaymentOptions'] = "";
			$GLOBALS['OrderShippingOptions'] = "";
			$GLOBALS['OrderTypeOptions'] = "";

			$checkout_providers = GetCheckoutModulesThatCustomerHasAccessTo();
			$shipping_providers = GetAvailableModules('shipping', false, true, false);

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}

			foreach ($checkout_providers as $provider) {
				$GLOBALS['OrderPaymentOptions'] .= sprintf("<option value='%s'>%s</option>", $provider['object']->GetId(), $provider['object']->GetName());
			}

			foreach ($shipping_providers as $provider) {
				$GLOBALS['OrderShippingOptions'] .= sprintf("<option value='%s'>%s</option>", $provider['object']->GetId(), $provider['object']->GetName());
			}

			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Ebay_Selling) && gzte11(ISC_LARGEPRINT)) {
				$GLOBALS['OrderTypeOptions'] = $this->GetOrderTypeOptions();
			}

			$GLOBALS['OrderStatusOptions'] = $this->GetOrderStatusOptions();
			$this->template->display('orders.search.tpl');
		}

		/**
		*	This function checks to see if the user wants to save the search details as a custom search,
		*	and if they do one is created. They are then forwarded onto the search results
		*/
		protected function SearchOrdersRedirect()
		{
			// Format totals back to the western standard
			if (isset($_GET['totalFrom']) && $_GET['totalFrom'] != "") {
				$_GET['totalFrom'] = $_REQUEST['totalFrom'] = DefaultPriceFormat($_GET['totalFrom']);
			}

			if (isset($_GET['totalTo']) && $_GET['totalTo'] != "") {
				$_GET['totalTo'] = $_REQUEST['totalTo'] = DefaultPriceFormat($_GET['totalTo']);
			}

			// Are we saving this as a custom search?
			if(isset($_GET['viewName']) && $_GET['viewName'] != '') {
				$search_id = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->SaveSearch($_GET['viewName'], $_GET);

				if($search_id > 0) {

					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($search_id, $_GET['viewName']);

					ob_end_clean();
					header(sprintf("Location:index.php?ToDo=customOrderSearch&searchId=%d&new=true", $search_id));
					exit;
				}
				else {
					$this->ManageOrders(sprintf(GetLang('ViewAlreadExists'), $_GET['viewName']), MSG_ERROR);
				}
			}
			// Plain search
			else {
				$this->ManageOrders();
			}
		}

		/**
		*	Load a custom search
		*/
		protected function CustomSearch()
		{
			SetSession('ordersearch', (int) $_GET['searchId']);

			if ($_GET['searchId'] > 0) {
				$this->_customSearch = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($_GET['searchId']);
				$_REQUEST = array_merge($_REQUEST, $this->_customSearch['searchvars']);
			}

			if (isset($_REQUEST['new'])) {
				$this->ManageOrders(GetLang('CustomSearchSaved'), MSG_SUCCESS);
			} else {
				$this->ManageOrders();
			}
		}

		protected function DeleteCustomSearch()
		{

			if($GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->DeleteSearch($_GET['searchId'])) {
				// remove the saved search from the session to default to All Orders
				UnsetSession('ordersearch');

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_GET['searchId']);

				$this->ManageOrders(GetLang('DeleteCustomSearchSuccess'), MSG_SUCCESS);
			}
			else {
				$this->ManageOrders(GetLang('DeleteCustomSearchFailed'), MSG_ERROR);
			}
		}

		/**
		*	Print an invoice for an order. If $EndWithPageBreak is true then we will output a page break
		*/
		protected function DoInvoicePrinting($orderIds)
		{
			require_once ISC_BASE_PATH . '/lib/order.printing.php';
			$invoice = generatePrintableInvoicePage($orderIds);

			echo $invoice;
		}

		protected function PrintInvoice()
		{
			// Print an invoice for an order
			ob_end_clean();

			if(isset($_GET['orderId'])) {
				$order_id = (int)$_GET['orderId'];

				$order = GetOrder($order_id, null, null, true);
				if (!$order) {
					FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
				}

				$this->DoInvoicePrinting(array($order_id));
			}
			else {
				echo "<script type=\"text/javascript\">window.close();</script>";
			}

			die();
		}

		protected function PrintMultiInvoices()
		{
			// Print multiple invoices and separate each one with a page break
			ob_end_clean();

			if(isset($_POST['orders'])) {
				$orderIds = $_POST['orders'];
				sort($orderIds, SORT_NUMERIC);

				// Check permissions for each order
				for($i = 0; $i < count($orderIds); $i++) {
					$orderId = $orderIds[$i];

					$order = GetOrder($orderId, null, null, true);
					if (!$order) {
						FlashMessage(GetLang('Unauthorized'), MSG_ERROR, 'index.php?ToDo=viewOrders');
					}
				}

				// Generate and output the invoice
				$this->DoInvoicePrinting($orderIds);
			}
			else {
				echo "<script type=\"text/javascript\">window.close();</script>";
			}

			die();
		}

		/**
		*	Create a view for orders. Uses the same form as searching but puts the
		*	name of the view at the top and it's mandatory instead of optional.
		*/
		protected function CreateView()
		{
			$GLOBALS['OrderPaymentOptions'] = "";
			$GLOBALS['OrderShippingOptions'] = "";
			$GLOBALS['OrderTypeOptions'] = "";

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}


			$checkout_providers = GetCheckoutModulesThatCustomerHasAccessTo();
			$shipping_providers = GetAvailableModules('shipping', false, true, false);

			foreach($checkout_providers as $provider) {
				$GLOBALS['OrderPaymentOptions'] .= sprintf("<option value='%s'>%s</option>", $provider['object']->GetId(), $provider['object']->GetName());
			}

			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Ebay_Selling) && gzte11(ISC_LARGEPRINT)) {
				$GLOBALS['OrderTypeOptions'] = $this->GetOrderTypeOptions();
			}

			foreach($shipping_providers as $provider) {
				$GLOBALS['OrderShippingOptions'] .= sprintf("<option value='%s'>%s</option>", $provider['object']->GetId(), $provider['object']->GetName());
			}

			$GLOBALS['OrderStatusOptions'] = $this->GetOrderStatusOptions();
			$this->template->display('orders.view.tpl');
		}


		protected function ImportTrackingNumbers()
		{
			require_once dirname(__FILE__)."/../importer/tracking_numbers.php";
			$importer = new ISC_BATCH_IMPORTER_TRACKING_NUMBERS();
		}

		protected function updateOrderStatusBox()
		{
			if (array_key_exists('orders', $_REQUEST) && array_key_exists('statusId', $_REQUEST) && isId($_REQUEST['statusId'])) {
				$GLOBALS['StatusID'] = $_REQUEST['statusId'];
				$GLOBALS['JavaScriptOrderIds'] = $_REQUEST['orders'];
				$this->template->display('orders.updatestatus.popup.tpl');
			}
		}

		/**
		* Delete order configurable product fields and the files uploaded with the order
		*
		* @param string $orderIds order ids separate by comma
		*
		*/
		protected function _DeleteOrderProductFields($orderIds)
		{
			$fieldsQuery = "Select * from [|PREFIX|]order_configurable_fields WHERE orderid IN ('".$orderIds."');";
			$fieldsResult = $GLOBALS['ISC_CLASS_DB']->Query($fieldsQuery);
			$fieldIds[] = array(0);
			while($field = $GLOBALS['ISC_CLASS_DB']->Fetch($fieldsResult)) {
				//remove uploaded file if there is any
				if($field['filename'] != '') {
					@unlink(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/configured_products/'.$field['filename']);
				}
				$fieldIds[] = $field['orderfieldid'];
			}
			$fieldIdsString = implode("','", array_map('intval', $fieldIds));

			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('order_configurable_fields', "WHERE orderfieldid IN ('".$fieldIdsString."')");
		}

		/**
		*	Load order product configurable fields layout in imodal.
		*
		*/
		public function LoadOrderProductFieldsFullView()
		{
			if(!isset($_REQUEST['orderId'])) {
				exit;
			}

			$ordprodid = 0;
			$GLOBALS['OrderProducts'] = '';

			$fieldsArray = $this->GetOrderProductFieldsData($_REQUEST['orderId']);

			$query = "SELECT ordprodname, orderprodid
						FROM [|PREFIX|]order_products
						WHERE orderorderid=".(int)$_REQUEST['orderId'];

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			//each item in the order
			while($orderProd = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				//if this order item doesn't has any configurable fields, go to the next item
				if(!isset($fieldsArray[$orderProd['orderprodid']])) {
					continue;
				}

				$productFields = '';
				$productFields = $this->LoadOrderProductFieldRow($fieldsArray[$orderProd['orderprodid']], true);

				//only load products with configurable fields
				if($productFields != '') {
					$GLOBALS['OrderProductName'] = isc_html_escape($orderProd['ordprodname']);
					$GLOBALS['OrderProductFields'] = $productFields;
					$GLOBALS['OrderProducts'] .= $this->template->render('Snippets/OrderProductFields.html');
				}
			}

			$this->template->display('order.productfields.tpl');
			exit;
		}

		public function LoadOrderProductFieldRow($fields, $fullView = false)
		{
			if(empty($fields)) {
				return '' ;
			}
			$productFields = '';

			//each configurable field customer submited
			foreach($fields as $row) {

				if (empty($row['textcontents']) && empty($row['filename'])) {
					continue;
				}

				$fieldValue = '-';
				$fieldName = $row['fieldname'];
				switch($row['fieldtype']) {
					case 'file': {
						$fieldValue = '<a href="'.GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/configured_products/'.urlencode($row['originalfilename']).'">'.isc_html_escape($row['originalfilename']).'</a>';
						break;
					}
					default: {
						if(isc_strlen($row['textcontents'])>50 && !$fullView) {
							$fieldValue = isc_html_escape(isc_substr($row['textcontents'], 0, 50))." ..";
						} else {
							$fieldValue = isc_html_escape($row['textcontents']);
						}
						break;
					}
				}

				$productFields .= "<dt>".isc_html_escape($fieldName).":</dt>";
				$productFields .= "<dd>".$fieldValue."</dd>";
			}

			return $productFields;
		}

		/**
		* get the product fields data for each order
		*
		* @param int $orderId, order id
		*
		* @return array an array of product fields data
		*/
		public function GetOrderProductFieldsData($orderId)
		{
			$query = "SELECT o.*
						FROM [|PREFIX|]order_configurable_fields o
							JOIN [|PREFIX|]product_configurable_fields p ON o.fieldid = p.productfieldid
						WHERE
							o.orderid=".(int)$orderId."
						ORDER BY p.fieldsortorder ASC";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$fields = array();
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$fields[$row['ordprodid']][] = $row;
			}

			return $fields;
		}

		/**
		 * Load and return an array of all of the shipping addresses associated with a particular
		 * customer ID. Will also generate a 'preview string' of all of the address details concatenated.
		 *
		 * @param int The ID of the customer to fetch the addresses for.
		 * @return array An array of addresses.
		 */
		public function LoadCustomerAddresses($customerId)
		{
			$customer = GetClass('ISC_CUSTOMER');
			$addresses = $customer->GetCustomerShippingAddresses($customerId);
			$addressResponse = array();
			foreach($addresses as $address) {
				$fields = array(
					'shipfullname',
					'shipcompany',
					'shipaddress1',
					'shipaddress2',
					'shipcity',
					'shipstate',
					'shipzip',
					'shipcountry',
					'shipcustomfields'
				);

				$formattedAddress = '';
				foreach($fields as $field) {

					/**
					 * Load in the custom fields if we have any
					 */
					if ($field == 'shipcustomfields' && isId($address['shipformsessionid'])) {
						$address[$field] = $GLOBALS['ISC_CLASS_FORM']->getSavedSessionData($address['shipformsessionid']);
						continue;
					}

					if(!isset($address[$field])) {
						continue;
					}
					$formattedAddress .= $address[$field] .', ';
				}

				/**
				 * Because we have both the billing and shipping forms in the one page, we have
				 * to assign the same values to both forms. We need to find out what is the original
				 * form so we can map it to the other
				 */
				if (isset($address['shipcustomfields']) && !empty($address['shipcustomfields'])) {
					$fieldIdx = array_keys($address['shipcustomfields']);
					$formIdx = $GLOBALS['ISC_CLASS_FORM']->findFormIdByFieldId($fieldIdx[0]);

					if (is_array($formIdx) && !empty($formIdx)) {
						$fieldMap = $GLOBALS['ISC_CLASS_FORM']->mapAddressFieldList($formIdx[0], $fieldIdx);
						$newCustom = array();

						/**
						 * OK, we got the map. now we can create our new custom fields data
						 */
						foreach ($fieldMap as $sourceFieldId => $targetFieldId) {
							if (!isset($address['shipcustomfields'][$sourceFieldId])) {
								continue;
							}

							$newCustom[$sourceFieldId] = $address['shipcustomfields'][$sourceFieldId];
							$newCustom[$targetFieldId] = $address['shipcustomfields'][$sourceFieldId];
						}

						$address['shipcustomfields'] = $newCustom;
					}
				}

				$formattedAddress = rtrim($formattedAddress, ', ');
				$address['preview'] = $formattedAddress;
				$addressResponse[] = $address;
			}
			return $addressResponse;
		}

		protected function RefundOrder()
		{
			$message = '';
			$messageStatus = MSG_ERROR;
			$provider = null;

			if(!isset($_REQUEST['orderid'])) {
				return false;
			}

			$orderId = $_REQUEST['orderid'];
			$order = GetOrder($_REQUEST['orderid']);
			if (!$order || !isset($order['orderid'])) {
				return false;
			}


			/* Validate posted data*/
			$refundType = '';
			if(!isset($_REQUEST['refundType'])) {
				return false;
			}

			$refundType = $_REQUEST['refundType'];

			//preset the refund amount to the available amount of the order
			$refundAmt = $order['total_inc_tax'] - $order['ordrefundedamount'];

			//refund partial amount
			if($refundType== 'partial') {
				//is refund amount specified
				if(!isset($_REQUEST['refundAmt']) || $_REQUEST['refundAmt'] == '') {
					$message = GetLang('EnterRefundAmount');
				}
				//is refund amount specified a valid format
				else if(!is_numeric($_REQUEST['refundAmt']) || $_REQUEST['refundAmt'] <= 0) {
					$message = GetLang('InvalidRefundAmountFormat');
				}
				//is refund amount larger than the original order amount
				else if($_REQUEST['refundAmt'] + $order['ordrefundedamount']  > $order['total_inc_tax']) {
					$message = GetLang('InvalidRefundAmount');
				}
				else {
					$refundAmt = $_REQUEST['refundAmt'];
				}
			}

			//there is an error message
			if($message != '') {
				FlashMessage($message, $messageStatus, 'index.php?ToDo=viewOrders');
			}

			$transactionId = trim($order['ordpayproviderid']);
			if($transactionId == '') {
				$message = GetLang('OrderTranscationIDNotFound');
			}
			else if(!GetModuleById('checkout', $provider, $order['orderpaymentmodule'])) {
				$message = GetLang('PaymentMethodNotExist');
			}
			else if(!$provider->IsEnabled()) {
				$message = GetLang('PaymentProviderIsDisabled');
			}
			else if(!method_exists($provider, "DoRefund")) {
				$message = GetLang('RefundNotAvailable');
			}
			else {
				//still here, perform a delay capture
				if($provider->DoRefund($order, $message, $refundAmt)) {
					$messageStatus = MSG_SUCCESS;

					//update order status
					$orderStatus = ORDER_STATUS_REFUNDED;
					UpdateOrderStatus($order['orderid'], $orderStatus, true);
				}
			}
			FlashMessage($message, $messageStatus, 'index.php?ToDo=viewOrders');

			return $message;
		}

		/**
		 * Format an address for display in the control panel for an order or shipment.
		 *
		 * @param array An array of details about the address.
		 * @param boolean Set to false to not include a flag image for the address country.
		 * @return string The generated HTML of the formatted address.
		 */
		public function BuildOrderAddressDetails($address, $includeFlag=true)
		{
			if(!isset($address['countrycode'])) {
				$address['countrycode'] = GetCountryISO2ByName($address['shipcountry']);
			}

			$countryFlag = '';
			if($includeFlag && $address['countrycode'] != '' && file_exists(ISC_BASE_PATH."/lib/flags/".strtolower($address['countrycode']).".gif")) {
				$countryFlag = "
					&nbsp;&nbsp;
					<img src=\"".GetConfig('AppPath')."/lib/flags/".strtolower($address['countrycode']).".gif\" style=\"vertical-align: middle;\" alt=\"\" />
				";
			}

			$addressPieces = array(
				isc_html_escape($address['shipfirstname']).' '.isc_html_escape($address['shiplastname']),
				isc_html_escape($address['shipcompany']),
				isc_html_escape($address['shipaddress1']),
				isc_html_escape($address['shipaddress2']),
				trim(isc_html_escape($address['shipcity'].', '.$address['shipstate'].' '.$address['shipzip']), ', '),
				isc_html_escape($address['shipcountry']).$countryFlag
			);

			$addressDetails = '';
			foreach($addressPieces as $piece) {
				if(!trim($piece)) {
					continue;
				}
				else if($addressDetails) {
					$addressDetails .= '<br />';
				}
				$addressDetails .= $piece;
			}
			return $addressDetails;
		}

		/**
		 * Get the available option for order type
		 */
		public function GetOrderTypeOptions()
		{
			$output = '';
			$orderTypes = array('StoreType', 'EbayType');
			foreach ($orderTypes as $key => $orderType) {
				$output .= sprintf("<option value='%s' >%s</option>", $key, GetLang($orderType));
			}
			return $output;
		}
	}
