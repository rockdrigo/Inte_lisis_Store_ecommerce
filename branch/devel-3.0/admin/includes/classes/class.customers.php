<?php
require_once(ISC_BASE_PATH . '/lib/addressvalidation.php');
	class ISC_ADMIN_CUSTOMERS extends ISC_ADMIN_BASE
	{
		private $_customSearch = array();
		private $_customerGroups = array();
		private $customerEntity;
		private $groupEntity;
		private $shippingEntity;
		private $shippingMap;

		public function __construct()
		{
			parent::__construct();
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('customers');

			if (!gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS[base64_decode('SGlkZUV4cG9ydA==')] = "none";
				$GLOBALS[base64_decode('SGlkZVN0b3JlQ3JlZGl0')] = "none";
				$GLOBALS[base64_decode('SGlkZUN1c3RvbUZpZWxkcw==')] = "none";
			}

			// Initialize the categories
			$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');

			// Initialise custom searches functionality
			$GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH'] = new ISC_ADMIN_CUSTOMSEARCH('customers');

			$this->customerEntity = new ISC_ENTITY_CUSTOMER();
			$this->groupEntity = new ISC_ENTITY_CUSTOMERGROUP();
			$this->shippingEntity = new ISC_ENTITY_SHIPPING();

			$this->shippingMap = array(
						'FirstName' => 'shipfirstname',
						'LastName' => 'shiplastname',
						'CompanyName' => 'shipcompany',
						'AddressLine1' => 'shipaddress1',
						'AddressLine2' => 'shipaddress2',
						'City' => 'shipcity',
						'Country' => 'shipcountry',
						'State' => 'shipstate',
						'Zip' => 'shipzip',
						'Phone' => 'shipphone'
					);
		}

		public function HandleToDo($Do)
		{
			switch (isc_strtolower($Do)) {
				case "editcustomergroup2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('CustomerGroups') => "index.php?ToDo=viewCustomerGroups", GetLang("EditACustomerGroup") => "index.php?ToDo=editCustomerGroup");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCustomerGroup2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "editcustomergroup":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('CustomerGroups') => "index.php?ToDo=viewCustomerGroups", GetLang("EditACustomerGroup") => "index.php?ToDo=editCustomerGroup");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCustomerGroup();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "createcustomergroup2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('CustomerGroups') => "index.php?ToDo=viewCustomerGroups");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateCustomerGroup2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "createcustomergroup":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('CustomerGroups') => "index.php?ToDo=viewCustomerGroups", GetLang('CreateACustomerGroup') => "index.php?ToDo=createCustomerGroup");

						$GLOBALS['InfoTip'] = GetLang('InfoTipCustomerGroups');

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateCustomerGroup();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "deletecustomergroups":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('CustomerGroups') => "index.php?ToDo=viewCustomerGroups");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteCustomerGroups();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "viewcustomergroups":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups) && gzte11(ISC_MEDIUMPRINT)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('CustomerGroups') => "index.php?ToDo=viewCustomerGroups", GetLang('CustomerGroups') => "index.php?ToDo=viewCustomerGroups");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->ManageCustomerGroups();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "viewcustomergroupdiscounts":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups) && gzte11(ISC_MEDIUMPRINT)) {
						print $this->ManageGroupDiscountGrid();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "createcustomerview":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('CreateCustomerView') => "index.php?ToDo=createCustomerView");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateView();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "importcustomers":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Import_Customers)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('ImportCustomers') => "index.php?ToDo=importCustomers");

						$this->ImportCustomers();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "deletecustomers":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Customers)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteCustomers();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "deletecustomcustomersearch":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteCustomSearch();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "customcustomersearch":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CustomSearch();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "searchcustomersredirect":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SearchCustomersRedirect();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "searchcustomers":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('SearchCustomers') => "index.php?ToDo=searchCustomers");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->SearchCustomers();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "addcustomer":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Customer)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('AddCustomer') => "index.php?ToDo=addCustomers");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddCustomerStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "addcustomer2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Customer)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('AddCustomer') => "index.php?ToDo=addCustomers");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddCustomerStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "editcustomer":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {

						$customer = $this->customerEntity->get(@$_REQUEST['customerId']);

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", isc_html_escape(trim($customer['custconfirstname'] . ' ' . $customer['custconlastname'])) => "index.php?ToDo=editCustomer&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])));

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCustomerStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "editcustomer2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {

						$customer = $this->customerEntity->get(@$_REQUEST['customerId']);
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", isc_html_escape(trim($customer['custconfirstname'] . ' ' . $customer['custconlastname'])) => "index.php?ToDo=editCustomer&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])));

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCustomerStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "logincustomer":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {
						$this->LoginCustomer();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "viewcustomeraddressgrid":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {
						print $this->ManageCustomerAddressGrid();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "addcustomeraddress":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Customer)) {

						$customer = $this->customerEntity->get(@$_REQUEST['customerId']);
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", isc_html_escape(trim($customer['custconfirstname'] . ' ' . $customer['custconlastname'])) => "index.php?ToDo=editCustomer&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])), GetLang('CustomerAddShippingAddressBreadCrumb') => "index.php?ToDo=addCustomerAddress&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])));

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddCustomerAddressStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "addcustomeraddress2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Customer)) {

						$customer = $this->customerEntity->get(@$_REQUEST['customerId']);
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", isc_html_escape(trim($customer['custconfirstname'] . ' ' . $customer['custconlastname'])) => "index.php?ToDo=editCustomer&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])), GetLang('CustomerAddShippingAddressBreadCrumb') => "index.php?ToDo=addCustomerAddress&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])));

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddCustomerAddressStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "editcustomeraddress":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {

						$customer = $this->customerEntity->get(@$_REQUEST['customerId']);
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", isc_html_escape(trim($customer['custconfirstname'] . ' ' . $customer['custconlastname'])) => "index.php?ToDo=editCustomer&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])), GetLang('CustomerEditShippingAddressBreadCrumb') => "index.php?ToDo=editCustomerAddress&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])) . "&addressId=" . urlencode(isc_html_escape(@$_REQUEST['addressId'])));

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCustomerAddressStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "editcustomeraddress2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {

						$customer = $this->customerEntity->get(@$_REQUEST['customerId']);
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", isc_html_escape(trim($customer['custconfirstname'] . ' ' . $customer['custconlastname'])) => "index.php?ToDo=editCustomer&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])), GetLang('CustomerEditShippingAddressBreadCrumb') => "index.php?ToDo=editCustomerAddress&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])) . "&addressId=" . urlencode(isc_html_escape(@$_REQUEST['addressId'])));

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCustomerAddressStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "deletecustomeraddress":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {

						$customer = $this->customerEntity->get(@$_REQUEST['customerId']);
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", isc_html_escape(trim($customer['custconfirstname'] . ' ' . $customer['custconlastname'])) => "index.php?ToDo=editCustomer&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])), GetLang('CustomerEditShippingAddressBreadCrumb') => "index.php?ToDo=editCustomerAddress&customerId=" . urlencode(isc_html_escape(@$_REQUEST['customerId'])) . "&addressId=" . urlencode(isc_html_escape(@$_REQUEST['addressId'])));

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteCustomerAddress();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				default:
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {

						if (isset($_GET['searchQuery'])) {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers", GetLang('SearchResults') => "index.php?ToDo=viewCustomers");
						}
						else {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Customers') => "index.php?ToDo=viewCustomers");
						}

						if (!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}
						$this->ManageCustomers();
						if (!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}
					}
					else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
			}
		}

		protected function buildLetterSearchUrlData($searchURL)
		{
			// generate list of letters from lang and assign to template
			$this->template->assign('letters', preg_split('%\s*,\s*%s', GetLang('Alphabet')));

			// assign the current search letter if any
			if (isset($searchURL['letter'])) {
				$this->template->assign('activeLetter', $searchURL['letter']);
			}

			// create url data for ajax letter links
			unset($searchURL['letter']);
			$searchURL['ajax'] = 1;
			$this->template->assign('letterURL', $searchURL);

			return $searchURL;
		}

		/**
		* @param array $searchURL current request data, probably $_GET or $_REQUEST
		* @param string $sortField
		* @param string $sortOrder
		*/
		public function buildSearchUrlData($searchURL, $sortField, $sortOrder)
		{
			unset($searchURL['page'], $searchURL['new'], $searchURL['ToDo'], $searchURL['SubmitButton1']);
			$searchURL['sortField'] = $sortField;
			$searchURL['sortOrder'] = $sortOrder;
			$this->template->assign('searchURL', $searchURL);
			return $searchURL;
		}

		private function ManageCustomersGrid(&$numCustomers)
		{
			// Show a list of customers in a table
			$page = 0;
			$start = 0;
			$numCustomers = 0;
			$numGroups = 0;
			$GLOBALS['CustomerGrid'] = "";
			$max = 0;

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}

			$this->_customerGroups = &$this->_GetGroupList($numGroups);

			if (!gzte11(ISC_MEDIUMPRINT) || $numGroups == 0) {
				$GLOBALS[base64_decode('SGlkZUdyb3Vw')] = "none";
			}

			// Is this a custom search?
			if (isset($_GET['searchId'])) {
				// Override custom search sort fields if we have a requested field
				if (isset($_GET['sortField'])) {
					$_REQUEST['sortField'] = $_GET['sortField'];
				}
				if (isset($_GET['sortOrder'])) {
					$_REQUEST['sortOrder'] = $_GET['sortOrder'];
				}
			}

			if (isset($_GET['searchQuery'])) {
				$GLOBALS['EscapedQuery'] = isc_html_escape($_GET['searchQuery']);
			}

			if (isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == "asc") {
				$sortOrder = "asc";
			}
			else {
				$sortOrder = "desc";
			}

			$validSortFields = array('customerid', 'custconlastname', 'custconfirstname', 'custconemail', 'custconphone', 'custconcompany', 'custdatejoined', 'numorders', 'custstorecredit');
			if (isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
				$sortField = $_REQUEST['sortField'];
				SaveDefaultSortField("ManageCustomers", $_REQUEST['sortField'], $sortOrder);
			} else {
				list($sortField, $sortOrder) = GetDefaultSortField("ManageCustomers", "customerid", $sortOrder);
			}

			$searchURL = $this->buildSearchUrlData($_GET, $sortField, $sortOrder);
			$this->buildLetterSearchUrlData($searchURL);

			$sortURL = $searchURL;
			unset($sortURL['sortField'], $sortURL['sortOrder']);


			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			}
			else {
				$page = 1;
			}

			if (isset($_GET['perpage'])) {
				$perPage = (int)$_GET['perpage'];
				SaveDefaultPerPage("ManageCustomers", $perPage);
			}
			else {
				$perPage = GetDefaultPerPage("ManageCustomers", ISC_CUSTOMERS_PER_PAGE);
			}

			// Limit the number of customers returned
			if ($page == 1) {
				$start = 1;
			}
			else {
				$start = ($page * $perPage) - ($perPage-1);
			}

			$start = $start-1;

			// Get the results for the query
			$customerResult = $this->_GetCustomerList($start, $sortField, $sortOrder, $numCustomers, $perPage);

			$GLOBALS['perPage'] = $perPage;
			$GLOBALS['numCustomers'] = $numCustomers;
			$GLOBALS['pageURL'] = "index.php?ToDo=viewCustomers&" . http_build_query($searchURL);
			$GLOBALS['currentPage'] = $page;

			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			$sortLinks = array(
				"CustId" => "customerid",
				"Name" => "custconlastname",
				"Email" => "custconemail",
				"Phone" => "custconphone",
				"StoreCredit" => "custstorecredit",
				"Date" => "custdatejoined",
				"NumOrders" => "numorders"
			);
			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewCustomers&amp;".http_build_query($sortURL)."&amp;page=".$page, $sortField, $sortOrder);

			// Workout the maximum size of the array
			$max = $start + $perPage;

			if ($max > $GLOBALS["ISC_CLASS_DB"]->CountResult($customerResult)) {
				$max = $GLOBALS["ISC_CLASS_DB"]->CountResult($customerResult);
			}

			if (!empty($this->_customerGroups)) {
				$showGroups = true;
			}
			else {
				$showGroups = false;
			}

			if ($numCustomers > 0) {
				while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($customerResult)) {
					$GLOBALS['CustomerId'] = (int) $row['customerid'];
					$GLOBALS['Name'] = isc_html_escape($row['custfullname']);
					$GLOBALS['Email'] = sprintf("<a href='mailto:%s'>%s</a>", urlencode($row['custconemail']), isc_html_escape($row['custconemail']));

					if ($row['custconphone'] != "") {
						$GLOBALS['Phone'] = isc_html_escape($row['custconphone']);
					} else {
						$GLOBALS['Phone'] = GetLang('NA');
					}

					$GLOBALS['Group'] = "";
					if ($showGroups) {
						if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {
							$GLOBALS['Group'] = $this->_BuildGroupDropdown($row['customerid'], $row['custgroupid'], $row['custfullname']);
						}
						elseif ($row['custgroupid']) {
							$GLOBALS['Group'] = $this->_customerGroups[$row['custgroupid']]['groupname'];
						}
					}

					if ($row['custconcompany'] != "") {
						$GLOBALS['Company'] = isc_html_escape($row['custconcompany']);
					} else {
						$GLOBALS['Company'] = GetLang('NA');
					}

					$GLOBALS['Date'] = CDate($row['custdatejoined']);
					$GLOBALS['NumOrders'] = (int) $row['numorders'];

					// Hide the plus symbol if the customer has no orders
					if ($row['numorders'] == 0) {
						$GLOBALS['HideExpand'] = "none";
					} else {
						$GLOBALS['HideExpand'] = "";
					}

					// If the customer has any notes, flag them
					$HasNotesClass = '';
					if($row['custnotes'] != '') {
						$HasNotesClass = 'HasNotes';
					}

					// Workout which links the user can have
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {
						$GLOBALS['StoreCreditAmount'] = FormatPrice($row['custstorecredit'], false, false, false);
						$GLOBALS['StoreCredit'] = $this->template->render('Snippets/CustomerRowStoreCredit.html');
						$GLOBALS['LoginLink'] = "<a href='index.php?ToDo=loginCustomer&amp;customerId=" . $row['customerid'] . "' class='Action' target='_blank'>" . GetLang('CustomerLoginAsThem') . "</a>";
						$GLOBALS['ViewNotesLink'] = "<a href='#' class='" . $HasNotesClass . " ViewNotesLink' onclick='Customers.ViewNotes(".$row['customerid']."); return false;'>" . GetLang("CustomerNotesLink") . "</a>";
						$GLOBALS['EditCustomerLink'] = sprintf("<a title='%s' class='Action' href='index.php?ToDo=editCustomer&amp;customerId=%d'>%s</a>", GetLang('CustomerEdit'), $row['customerid'], GetLang('Edit'));
					} else {
						$GLOBALS['StoreCredit'] = FormatPrice($row['custstorecredit']);
						$GLOBALS['LoginLink'] = sprintf("<a class='Action' disabled>%s</a>", GetLang('CustomerLoginAsThem'));
						$GLOBALS['ViewNotesLink'] = sprintf("<a class='Action' disabled>%s</a>", GetLang('CustomerNotesLink'));
						$GLOBALS['EditCustomerLink'] = sprintf("<a class='Action' disabled>%s</a>", GetLang('Edit'));
					}

					$GLOBALS['CustomerGrid'] .= $this->template->render('customers.manage.row.tpl');
				}
			}
			else {
				if (isset($_REQUEST['letter'])) {
					$GLOBALS['CustomerGrid'] = sprintf('<tr>
						<td colspan="11" style="padding:10px"><em>%s</em></td>
					</tr>', sprintf(GetLang('CustomerLetterSortNoResults'), isc_strtoupper($_REQUEST['letter'])));
				}
			}

			return $this->template->render('customers.manage.grid.tpl');
		}

		private function ManageCustomers($MsgDesc = "", $MsgStatus = "")
		{
			$GLOBALS['HideClearResults'] = "none";

			$numCustomers = 0;

			// Fetch any results, place them in the data grid
			$GLOBALS['CustomerDataGrid'] = $this->ManageCustomersGrid($numCustomers);

			// Was this an ajax based sort? Return the table now
			if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['CustomerDataGrid'];
				return;
			}

			if (isset($this->_customSearch['searchname'])) {
				$GLOBALS['ViewName'] = isc_html_escape($this->_customSearch['searchname']);
			}
			else {
				$GLOBALS['ViewName'] = GetLang('AllCustomers');
				$GLOBALS['HideDeleteViewLink'] = "none";
			}

			if (isset($this->_customSearch['searchname'])) {
				$GLOBALS['CustomSearchName'] = ": ".isc_html_escape($this->_customSearch['searchname']);
			}

			// Get the custom search as option fields
			$num_custom_searches = 0;
			$GLOBALS['CustomSearchOptions'] = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->GetSearchesAsOptions(@$_GET['searchId'], $num_custom_searches, "AllCustomers", "viewCustomers", "customCustomerSearch");

			if (!isset($_REQUEST['searchId'])) {
				$GLOBALS['HideDeleteCustomSearch'] = "none";
			}
			else {
				$GLOBALS['CustomSearchId'] = (int)$_REQUEST['searchId'];
			}

			// Do we need to disable the add button?
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Customer)) {
				$GLOBALS['DisableAdd'] = "DISABLED";
			}

			// Do we need to disable the delete button?
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Customers) || $numCustomers == 0) {
				$GLOBALS['DisableDelete'] = "DISABLED";
			}

			// Do we need to disable the expory button?
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Export_Customers) || $numCustomers == 0) {
				$GLOBALS['DisableExport'] = "DISABLED";
			}

			if (isset($_REQUEST['searchQuery']) || isset($_GET['searchId'])) {
				$GLOBALS['HideClearResults'] = "";
			}

			$GLOBALS['CustomerIntro'] = GetLang('ManageCustomersIntro');

			if ($numCustomers > 0) {
				if ($MsgDesc == "" && (isset($_REQUEST['searchQuery']) || isset($_GET['searchId']))) {
					if ($numCustomers == 1) {
						$MsgDesc = GetLang('CustomerSearchResultsBelow1');
					}
					else {
						$MsgDesc = sprintf(GetLang('CustomerSearchResultsBelowX'), $numCustomers);
					}

					$MsgStatus = MSG_SUCCESS;
				}
			}
			else {
				$GLOBALS['DisplayGrid'] = "none";
				if (count($_GET) > 1) {
					if ($MsgDesc == "") {
						$GLOBALS['Message'] = MessageBox(GetLang('NoCustomerResults'), MSG_ERROR);
					}
				}
				else {
					// No actual custoemrs
					$GLOBALS['DisplaySearch'] = "none";
					$GLOBALS['Message'] = MessageBox(GetLang('NoCustomers'), MSG_SUCCESS);
				}
			}

			if (!gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS[base64_decode('SGlkZUV4cG9ydA==')] = "none";
			}

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			$flashMessages = GetFlashMessages();
			if(is_array($flashMessages) && !empty($flashMessages)) {
				$GLOBALS['Message'] = '';
				foreach($flashMessages as $flashMessage) {
					$GLOBALS['Message'] .= MessageBox($flashMessage['message'], $flashMessage['type']);
				}
			}

			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Export_Customers)) {
				$exportAction = "index.php?ToDo=startExport&t=customers";
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

				$customerExportMenu = array();

				$customerExportMenu[] = array(
					array(
						'backgroundImage' => 'images/export.gif',
						'label' => GetLang('EmailIntegrationExportToFile'),
						'class' => 'exportMenuLink',
						'href' => $exportAction,
					),
				);

				$customerExportMenuModules = array();

				$emailModules = ISC_EMAILINTEGRATION::getConfiguredModules();
				foreach ($emailModules as /** @var ISC_EMAILINTEGRATION */$emailModule) {
					if (!$emailModule->supportsBulkExport()) {
						// not all modules have to support bulk exports
						continue;
					}

					$customerExportMenuModules[] = array(
						'backgroundImage' => '../modules/' . str_replace('_', '/', $emailModule->GetId()) . '/images/16x16.png',
						'label' => GetLang('EmailIntegrationExportToModule', array('module' => $emailModule->GetName())),
						'href' => 'javascript:Interspire_EmailIntegration_ModuleExportMachine.start({ exportType: "Customer", exportModule: "' . $emailModule->GetId() . '", exportSearch: ' . $searchQueryForExport . ' });',
					);
				}

				if (!empty($customerExportMenuModules)) {
					$customerExportMenu[] = $customerExportMenuModules;

					$this->engine->bodyScripts[] = '../javascript/fsm.js';
					$this->engine->bodyScripts[] = '../javascript/jquery/plugins/disabled/jquery.disabled.js';
					$this->engine->bodyScripts[] = '../javascript/ajaxDataProvider.js';
					$this->engine->bodyScripts[] = 'script/emailintegration.js';
					$this->engine->bodyScripts[] = 'script/emailintegration.export.js';

					// disabled for now until batch rule processing is implemented, as the existing, single-subscription rule processing is too slow for bulk export

	//				$customerExportMenu[] = array(
	//					array(
	//						'backgroundImage' => 'images/export_to_rules.gif',
	//						'label' => GetLang('EmailIntegrationExportToNewsletterRules'),
	//						'href' => 'javascript:Interspire_EmailIntegration_RuleExportMachine.start({ exportType: "Customer", exportRule: "NewsletterSubscribed", exportSearch: ' . $searchQueryForExport . ' });',
	//					),
	//				);
				}

				$this->template->assign('customerExportMenu', $customerExportMenu);
			}

			$this->template->display('customers.manage.tpl');
		}

		/**
		* Gets a list of customers as a result set
		*
		* @param int $Start The starting position to retrieve customers from
		* @param string $SortField The field to sort the customers on
		* @param string $SortOrder The order in which to sort the customers by, ASC or DESC
		* @param variable $NumCustomers $NumCustomers will be set to the number of customers that are retrieved
		* @param mixed $limit The max customers to retrieve, or false to not limit
		* @return resource The database result set of customers
		*/
		public function _GetCustomerList($Start, $SortField, $SortOrder, &$NumCustomers, $limit = ISC_CUSTOMERS_PER_PAGE)
		{
			$res = $this->BuildWhereFromVars($_REQUEST);
			$queryWhere = $res['query'];
			$queryJoin = $res['join'];
			$queryHaving = $res['having'];

			if ($queryWhere) {
				$queryWhere = " WHERE 1=1 " . $queryWhere;
			}

			if ($queryHaving) {
				$queryHaving = " HAVING 1=1 " . $queryHaving;
			}

			// build query for customers
			$query = "
				SELECT
					customerid,
					customerid,
					custconlastname,
					custconfirstname,
					CONCAT(custconfirstname, ' ', custconlastname) as custfullname,
					custconemail,
					custconphone,
					custconcompany,
					custdatejoined,
					custgroupid,
					COUNT(o.orderid) AS numorders,
					custstorecredit,
					custnotes
				FROM
					[|PREFIX|]customers
					LEFT JOIN [|PREFIX|]orders o ON ordcustid = customerid AND o.ordstatus != 0 AND o.deleted = 0
				" . $queryJoin . "
				" . $queryWhere . "
				GROUP BY
					customerid
				" . $queryHaving . "
			";

			if ($SortField != "") {
				$query .= " ORDER BY " . $SortField . " " . $SortOrder . " ";
			}

			// Add the limit
			if ($limit !== false) {
				$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, $limit);
			}

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);


			// count the number of customers for the query
			$countQuery = "
				SELECT
					COUNT(*) AS NumCustomers
				FROM
					(
						SELECT
							customerid,
							COUNT(orderid) AS numorders
						FROM
							[|PREFIX|]customers
							LEFT JOIN [|PREFIX|]orders ON ordcustid = customerid AND ordstatus != 0 AND deleted = 0
						" . $queryJoin . "
						" . $queryWhere . "
						GROUP BY
							customerid
						" . $queryHaving . "
					) AS customercount
			";

			// Fetch the number of results we have
			$NumCustomers = $GLOBALS['ISC_CLASS_DB']->FetchOne($countQuery);

			return $result;
		}

		public function BuildWhereFromVars($array)
		{
			$queryWhere = "";
			$queryJoin = "";
			$queryHaving = "";

			// Is this a custom search?
			if(!empty($array['searchId'])) {
				$this->_customSearch = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($array['searchId']);
				$array = array_merge($array, (array)$this->_customSearch['searchvars']);
			}

			if (isset($array['searchQuery']) && $array['searchQuery'] != "") {
				// PostgreSQL is case sensitive for likes, so all matches are done in lower case
				$search_query = $GLOBALS['ISC_CLASS_DB']->Quote(trim($array['searchQuery']));
				$queryWhere .= "
					AND (
						customerid = '" . $search_query . "' OR
						custconfirstname LIKE '%" . $search_query . "%' OR
						custconlastname LIKE '%" . $search_query . "%' OR
						custconemail LIKE '%" . $search_query . "%' OR
						CONCAT(custconfirstname, ' ', custconlastname) LIKE '%" . $search_query . "%' OR
						custconcompany LIKE '%" . $search_query . "%'
					)";
			}

			if (isset($array['letter']) && $array['letter'] != '') {
				$letter = chr(ord($array['letter']));
				if ($array['letter'] == '0-9') {
					$queryWhere .= " AND custconlastname NOT REGEXP('^[a-zA-Z]')";
				}
				else if (isc_strlen($letter) == 1) {
					$queryWhere .= " AND custconlastname LIKE '".$GLOBALS['ISC_CLASS_DB']->Quote($letter)."%'";
				}
			}

			if (isset($array['phone']) && $array['phone'] != "") {
				$phone = $GLOBALS['ISC_CLASS_DB']->Quote(trim($array['phone']));
				$queryWhere .= sprintf(" AND custconphone LIKE '%%%s%%'", $phone);
			}

			if (isset($array['idFrom']) && $array['idFrom'] != "") {
				$id_from = $GLOBALS['ISC_CLASS_DB']->Quote((int)$array['idFrom']);
				$queryWhere .= sprintf(" AND customerid >= '%d'", $id_from);
			}
			if (isset($array['idTo']) && $array['idTo']) {
				$id_to = $GLOBALS['ISC_CLASS_DB']->Quote((int)$array['idTo']);
				$queryWhere .= sprintf(" AND customerid <= '%d'", $id_to);
			}

			// limit by number of orders
			if (!empty($array['ordersFrom'])) {
				$orders_from = $GLOBALS['ISC_CLASS_DB']->Quote((int)$array['ordersFrom']);
				$queryHaving .= sprintf(" AND numorders >= '%d'", $orders_from);
			}

			if (!empty($array['ordersTo'])) {
				$orders_to = $GLOBALS['ISC_CLASS_DB']->Quote((int)$array['ordersTo']);
				$queryHaving .= sprintf(" AND numorders <= '%d'", $orders_to);
			}

			if (isset($array['storeCreditFrom']) && $array['storeCreditFrom'] != "") {
				$credit_from = $GLOBALS['ISC_CLASS_DB']->Quote((int)$array['storeCreditFrom']);
				$queryWhere .= sprintf(" AND custstorecredit >= '%d'", $credit_from);
			}

			if (isset($array['storeCreditTo']) && $array['storeCreditTo'] != "") {
				$credit_to = $GLOBALS['ISC_CLASS_DB']->Quote((int)$array['storeCreditTo']);
				$queryWhere .= sprintf(" AND custstorecredit <= '%d'", $credit_to);
			}

			// Limit results to a particular join date range
			if (isset($array['dateRange']) && $array['dateRange'] != "") {
				$range = $array['dateRange'];
				switch($range) {
					// Registrations within the last day
					case "today":
						$from_stamp = mktime(0, 0, 0, isc_date("m"), isc_date("d"), isc_date("Y"));
						break;
					// Registrations received in the last 2 days
					case "yesterday":
						$from_stamp = mktime(0, 0, 0, isc_date("m"), date("d")-1, isc_date("Y"));
						$to_stamp = mktime(0, 0, 0, isc_date("m"), isc_date("d")-1, isc_date("Y"));
						break;
					// Registrations received in the last 24 hours
					case "day":
						$from_stamp = time()-60*60*24;
						break;
					// Registrations received in the last 7 days
					case "week":
						$from_stamp = time()-60*60*24*7;
						break;
					// Registrations received in the last 30 days
					case "month":
						$from_stamp = time()-60*60*24*30;
						break;
					// Registrations received this month
					case "this_month":
						$from_stamp = mktime(0, 0, 0, isc_date("m"), 1, isc_date("Y"));
						break;
					// Orders received this year
					case "this_year":
						$from_stamp = mktime(0, 0, 0, 1, 1, isc_date("Y"));
						break;
					// Custom date
					default:
						if (isset($array['fromDate']) && $array['fromDate'] != "") {
							$from_date = $array['fromDate'];
							$from_data = explode("/", $from_date);
							$from_stamp = mktime(0, 0, 0, $from_data[0], $from_data[1], $from_data[2]);
						}
						if (isset($array['toDate']) && $array['toDate'] != "") {
							$to_date = $array['toDate'];
							$to_data = explode("/", $to_date);
							$to_stamp = mktime(0, 0, 0, $to_data[0], $to_data[1], $to_data[2]);
						}
				}

				if (isset($from_stamp)) {
					$queryWhere .= sprintf(" AND custdatejoined >= '%d'", $from_stamp);
				}
				if (isset($to_stamp)) {
					$queryWhere .= sprintf(" AND custdatejoined <= '%d'", $to_stamp);
				}
			}

			if (isset($array['custGroupId']) && is_numeric($array['custGroupId'])) {
				$custGroupId = (int)$array['custGroupId'];

				// is this group the default group? we should then search for customers with a groupid of 0 as well
				$groupQuery = 'SELECT * FROM [|PREFIX|]customer_groups WHERE customergroupid = ' . $custGroupId . ' AND isdefault = 1';
				$groupRes = $this->db->Query($groupQuery);
				if ($this->db->CountResult($groupRes)) {
					$queryWhere .= ' AND (custgroupid = ' . $custGroupId . ' OR custgroupid = 0)';
				}
				else {
					$queryWhere .= ' AND custgroupid = ' . $custGroupId;
				}
			}

			// Search for users with a particular shipping country & state
			if (isset($array['country']) && $array['country'] != "") {
				$country = $GLOBALS['ISC_CLASS_DB']->Quote((int)$array['country']);

				$queryJoin .= " LEFT JOIN [|PREFIX|]shipping_addresses ON shipcustomerid = customerid";
				$queryWhere .= sprintf(" AND shipcountryid='%s'", $country);

				$state = '';
				if (isset($array['state']) && $array['state'] != "") {
					$state = GetStateById($array['state']);
				}
				else if (isset($array['state_1']) && $array['state_1'] != "") {
					$state = $array['state_1'];
				}

				// Searching by state too
				if ($state != '') {
					$queryWhere .= " AND shipstate='".$GLOBALS['ISC_CLASS_DB']->Quote($state)."'";
				}
			}

			return array("query" => $queryWhere, "join" => $queryJoin, "having" => $queryHaving);
		}

		private function DeleteCustomers()
		{
			$queries = array();

			if (isset($_POST['customers'])) {
				if (!$this->customerEntity->multiDelete($_POST['customers'])) {
					$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
					$this->ManageCustomers($err, MSG_ERROR);
				} else {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['customers']));

					$this->ManageCustomers(GetLang('CustomersDeletedSuccessfully'), MSG_SUCCESS);
				}
			}
			else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {
					$this->ManageCustomers();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		private function SearchCustomers()
		{
			$GLOBALS['CountryList'] = GetCountryList("", false);
			$GLOBALS['HideStateList'] = "none";

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}

			if (gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['CustomerGroups'] = $this->GetCustomerGroupsAsOptions();
			}
			else {
				$GLOBALS['HideCustomerGroups'] = "none";
			}

			$this->template->display('customers.search.tpl');
		}

		/**
		*	This function checks to see if the user wants to save the search details as a custom search,
		*	and if they do one is created. They are then forwarded onto the search results
		*/
		private function SearchCustomersRedirect()
		{
			// Format totals back to the western standard
			if (isset($_GET['storeCreditFrom']) && $_GET['storeCreditFrom'] != "") {
				$_GET['storeCreditFrom'] = $_REQUEST['storeCreditFrom'] = DefaultPriceFormat($_GET['storeCreditFrom']);
			}

			if (isset($_GET['storeCreditTo']) && $_GET['storeCreditTo'] != "") {
				$_GET['storeCreditTo'] = $_REQUEST['storeCreditTo'] = DefaultPriceFormat($_GET['storeCreditTo']);
			}

			// Are we saving this as a custom search?
			if (isset($_GET['viewName']) && $_GET['viewName'] != '') {
				$search_id = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->SaveSearch($_GET['viewName'], $_GET);
				if ($search_id > 0) {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($search_id, $_REQUEST['viewName']);

					ob_end_clean();
					header(sprintf("Location:index.php?ToDo=customCustomerSearch&searchId=%d&new=true", $search_id));
					exit;
				}
				else {
					$this->ManageCustomers(sprintf(GetLang('ViewAlreadExists'), $_GET['viewName']), MSG_ERROR);
				}
			}
			// Plain search
			else {
				$this->ManageCustomers();
			}
		}

		/**
		*	Load a custom search
		*/
		private function CustomSearch()
		{

			$this->_customSearch = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($_GET['searchId']);
			$_REQUEST = array_merge($_REQUEST, $this->_customSearch['searchvars']);

			if (isset($_REQUEST['new'])) {
				$this->ManageCustomers(GetLang('CustomSearchSaved'), MSG_SUCCESS);
			}
			else {
				$this->ManageCustomers();
			}
		}

		private function DeleteCustomSearch()
		{

			if ($GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->DeleteSearch($_GET['searchId'])) {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();

				$this->ManageCustomers(GetLang('DeleteCustomSearchSuccess'), MSG_SUCCESS);
			}
			else {
				$this->ManageCustomers(GetLang('DeleteCustomSearchFailed'), MSG_ERROR);
			}
		}

		private function ImportCustomers()
		{
			require_once dirname(__FILE__)."/../importer/customers.php";
			$importer = new ISC_BATCH_IMPORTER_CUSTOMERS();
		}

		/**
		*	Create a view for customers. Uses the same form as searching but puts the
		*	name of the view at the top and it's mandatory instead of optional.
		*/
		private function CreateView()
		{
			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}

			$GLOBALS['CountryList'] = GetCountryList("", false);
			$GLOBALS['HideStateList'] = "none";

			if (gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['CustomerGroups'] = $this->GetCustomerGroupsAsOptions();
			}
			else {
				$GLOBALS['HideCustomerGroups'] = "none";
			}

			$this->template->display('customers.view.tpl');
		}

		/**
		* ManageCustomerGroups
		* Show a list of existing customer groups as well as the ability to create new groups
		*
		* @return Void
		*/
		private function ManageCustomerGroups($MsgDesc = "", $MsgStatus = "")
		{
			$numCustomerGroups = 0;

			// Fetch any results, place them in the data grid
			$GLOBALS['CustomerGroupsDataGrid'] = $this->ManageCustomerGroupsGrid($numCustomerGroups);

			// Was this an ajax based sort? Return the table now
			if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['CustomerGroupsDataGrid'];
				return;
			}

			// Do we need to disable the delete button?
			if ($numCustomerGroups == 0) {
				$GLOBALS['DisableDelete'] = "DISABLED";

				if (empty($_POST)) {
					$GLOBALS['Message'] = MessageBox(GetLang('NoCustomerGroups'), MSG_SUCCESS);
				}
				else {
					$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
				}
			}

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			$this->template->display('customers.groups.manage.tpl');
		}

		private function ManageCustomerGroupsGrid(&$numCustomerGroups)
		{
			// Show a list of customer groups in a table
			$page = 0;
			$start = 0;
			$numCustomerGroups = 0;
			$numPages = 0;
			$GLOBALS['CustomerGroupsGrid'] = "";
			$GLOBALS['Nav'] = "";
			$max = 0;

			if (isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == "asc") {
				$sortOrder = "asc";
			}
			else {
				$sortOrder = "desc";
			}

			$validSortFields = array('groupname', 'discount', 'discountrules', 'customersingroup');

			if (isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
				$sortField = $_REQUEST['sortField'];
				SaveDefaultSortField("ViewCustomerGroups", $_REQUEST['sortField'], $sortOrder);
			} else {
				list($sortField, $sortOrder) = GetDefaultSortField("ViewCustomerGroups", "customergroupid", "asc");
			}

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			}
			else {
				$page = 1;
			}

			// Build the pagination and sort URL
			$searchURL = '';
			foreach($_GET as $k => $v) {
				if ($k == "sortField" || $k == "sortOrder" || $k == "page" || $k == "new" || $k == "ToDo" || $k == "SubmitButton1" || !$v) {
					continue;
				}
				$searchURL .= sprintf("&%s=%s", $k, urlencode($v));
			}

			$sortURL = sprintf("%s&sortField=%s&sortOrder=%s", $searchURL, $sortField, $sortOrder);
			$GLOBALS['SortURL'] = $sortURL;

			// Limit the number of questions returned
			if ($page == 1) {
				$start = 1;
			}
			else {
				$start = ($page * ISC_CUSTOMER_GROUPS_PER_PAGE) - (ISC_CUSTOMER_GROUPS_PER_PAGE-1);
			}

			$start = $start-1;

			// Get the results for the query
			$customerGroupResult = $this->_GetCustomerGroupList($start, $sortField, $sortOrder, $numCustomerGroups);
			$numPages = ceil($numCustomerGroups / ISC_CUSTOMER_GROUPS_PER_PAGE);

			// Add the "(Page x of n)" label
			if ($numCustomerGroups > ISC_CUSTOMER_GROUPS_PER_PAGE) {
				$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);

				$GLOBALS['Nav'] .= BuildPagination($numCustomerGroups, ISC_CUSTOMER_GROUPS_PER_PAGE, $page, sprintf("index.php?ToDo=viewCustomerGroups%s", $sortURL));
			}
			else {
				$GLOBALS['Nav'] = "";
			}

			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			$sortLinks = array(
				"GroupName" => "groupname",
				"Discount" => "discount",
				"DiscountMethod" => "discountmethod",
				"DiscountRules" => "discountrules",
				"CustomersInGroup" => "customersingroup"
			);
			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewCustomerGroups&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);

			// Workout the maximum size of the array
			$max = $start + ISC_CUSTOMER_GROUPS_PER_PAGE;

			if ($max > $GLOBALS["ISC_CLASS_DB"]->CountResult($customerGroupResult)) {
				$max = $GLOBALS["ISC_CLASS_DB"]->CountResult($customerGroupResult);
			}

			if ($numCustomerGroups > 0) {
				while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($customerGroupResult)) {
					$GLOBALS['CustomerGroupId'] = (int) $row['customergroupid'];
					$GLOBALS['GroupName'] = isc_html_escape($row['groupname']);

					// Show the row as yellow if it's default
					if ($row['isdefault'] == 1) {
						$GLOBALS['DefaultText'] = "<span style='margin-left: 10px; font-size: 0.8em; font-weight: bold;'>(default)</span>";
						$GLOBALS['GridRowSel'] = "GridRowSel";
						$GLOBALS['GridRowSelOver'] = "GridRowSelOver";
					}
					else {
						$GLOBALS['DefaultText'] = "";
						$GLOBALS['GridRowSel'] = "";
						$GLOBALS['GridRowSelOver'] = "";
					}

					$GLOBALS['DiscountRules'] = $row['discountrules'];

					switch (strtolower($row['discountmethod'])) {
						case 'percent':
							$GLOBALS['Discount'] = GetLang('DiscountRow', array("price" => FormatNumber($row['discount']) . '%'));
							break;

						case 'fixed':
							$GLOBALS['Discount'] = GetLang('FixedPriceRow', array("price" => FormatPrice($row['discount'])));
							break;

						default:
							$GLOBALS['Discount'] = GetLang('DiscountRow', array("price" => FormatPrice($row['discount'])));
							break;

					}


					$GLOBALS['CustomersInGroup'] = $row['customersingroup'];

					// Show a "view" link if there's more than one customer in the group
					if ($row['customersingroup'] > 0) {
						$GLOBALS['CustomersInGroup'] .= sprintf(" (<a href='index.php?ToDo=searchCustomersRedirect&amp;custGroupId=%d'>%s</a>)", $row['customergroupid'], GetLang("View"));

					}

					$GLOBALS['EditLink'] = sprintf("<a title='%s' class='Action' href='index.php?ToDo=editCustomerGroup&amp;groupId=%d'>%s</a>", GetLang('CustomerGroupEdit'), $row['customergroupid'], GetLang('Edit'));

					$GLOBALS['CustomerGroupsGrid'] .= $this->template->render('customers.groups.manage.row.tpl');
				}
			}
			else {
				$GLOBALS['DisplayGrid'] = "none";
			}

			return $this->template->render('customers.groups.manage.grid.tpl');
		}

		private function _GetCustomerGroupList($Start, $SortField, $SortOrder, &$NumCustomerGroups, $AddLimit=true)
		{
			// Return an array containing details about customers.
			// Takes into account search values too.
			$query = "
				SELECT *, (SELECT COUNT(custgroupid) FROM [|PREFIX|]customers WHERE custgroupid=customergroupid) as customersingroup, (SELECT COUNT(customergroupid) FROM [|PREFIX|]customer_group_discounts d WHERE d.customergroupid=g.customergroupid) as discountrules
				FROM [|PREFIX|]customer_groups g ";

			if ($SortField != "") {
				$query .= "ORDER BY " . $SortField . " " . $SortOrder . " ";
			}

			$countQuery = "SELECT COUNT(customergroupid) FROM [|PREFIX|]customer_groups";

			// Fetch the number of results we have
			$NumCustomerGroups = $GLOBALS['ISC_CLASS_DB']->FetchOne($countQuery);

			// Add the limit
			if ($AddLimit) {
				$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_CUSTOMER_GROUPS_PER_PAGE);
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			}

			return $result;
		}

		private function DeleteCustomerGroups()
		{
			$queries = array();

			if (isset($_POST['groups'])) {
				$groupids = implode("','", $GLOBALS['ISC_CLASS_DB']->Quote($_POST['groups']));

				if (!$this->groupEntity->multiDelete($_POST['groups'])) {
					$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
					$this->ManageCustomers($err, MSG_ERROR);
				} else {
					// Reset the custgroupid field to 0 for any customers in these groups
					$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT customerid FROM [|PREFIX|]customers WHERE custgroupid in ('".$GLOBALS['ISC_CLASS_DB']->Quote($groupids)."')");

					while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$this->customerEntity->editGroup($row["customerid"], 0);
					}

					$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();

					if ($err == "") {
						// Delete all discounts that were setup for this group
						if ($GLOBALS['ISC_CLASS_DB']->DeleteQuery("customer_group_discounts", "WHERE customergroupid in ('".$GLOBALS['ISC_CLASS_DB']->Quote($groupids)."')")) {
							// Log this action
							$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['groups']));

							// Update the customer groups data store
							$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCustomerGroups();

							// Delete any group category discount caches we have
							foreach($_POST['groups'] as $groupId) {
								$GLOBALS['ISC_CLASS_DATA_STORE']->Delete('CustomerGroupsCategoryDiscounts'.$groupId);
							}

							$this->ManageCustomerGroups(GetLang('CustomerGroupsDeletedSuccessfully'), MSG_SUCCESS);
						}
						else {
							$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
							$this->ManageCustomers($err, MSG_ERROR);
						}
					}
					else {
						$this->ManageCustomers($err, MSG_ERROR);
					}
				}
			}
			else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Customer_Groups)) {
					$this->ManageCustomerGroups();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		/**
		* CreateCustomerGroup
		* Create a new customer group with discounts, etc
		*
		* @return Void
		*/
		private function CreateCustomerGroup($MsgDesc = "", $MsgStatus = "", $PreservePost = false)
		{
			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			$data = $this->GetCustomerGroupData(0);

			$GLOBALS['GroupName'] = isc_html_escape($data['groupname']);
			$GLOBALS['Discount'] = isc_html_escape($data['discount']);

			if ($data['discountmethod'] == '') {
				$data['discountmethod'] = 'price';
			}

			$GLOBALS['StoreDiscountMethod' . ucfirst(isc_strtolower($data['discountmethod']))] = 'selected';

			if ($data['isdefault']) {
				$GLOBALS['IsDefault'] = "checked='checked'";
			}

			if ($data['categoryaccesstype'] == "all") {
				$GLOBALS['AccessAllCategories'] = "checked='checked'";
				$GLOBALS['HideAccessCategories'] = "none";
				$GLOBALS['HideAccessCatLinks'] = "none";
			}

			$accessCats = $data['accesscategorieslist'];
			$discountList = $data['discountlist'];

			if (!$PreservePost) {
				$GLOBALS['AccessAllCategories'] = "checked='checked'";
				$GLOBALS['HideAccessCategories'] = "none";
				$GLOBALS['HideAccessCatLinks'] = "none";
			}

			$GLOBALS['FormAction'] = "createCustomerGroup2";
			$GLOBALS['Title'] = GetLang("CreateACustomerGroup");

			// Reuse them for the categories which the group has access to and make them all selected by default
			$GLOBALS['AccessCategoryOptions'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions($accessCats, "<option %s value='%d'>%s</option>", 'selected="selected"', "", false);

			$GLOBALS['HiddenDiscounts'] = $this->buildHiddenDiscountFields($discountList, $products, $categories);

			$GLOBALS['HideNoCatgeory'] = '';
			$GLOBALS['HideNoProduct'] = '';
			$GLOBALS['HideCategoryGridContainer'] = '';
			$GLOBALS['HideProductGridContainer'] = '';

			if (empty($categories['items'])) {
				$GLOBALS['HideCategoryGridContainer'] = 'none';
			} else {
				$GLOBALS['HideNoCatgeory'] = 'none';
			}

			if (empty($products['items'])) {
				$GLOBALS['HideProductGridContainer'] = 'none';
			} else {
				$GLOBALS['HideNoProduct'] = 'none';
			}

			/**
			 * Add in the category and product items
			 */
			$GLOBALS['DiscountsPerPage'] = ISC_GROUPDISCOUNT_ITEMS_PER_PAGE;
			$GLOBALS['CategoryDataGrid'] = $this->ManageGroupDiscountGrid($categories);
			$GLOBALS['ProductDataGrid'] = $this->ManageGroupDiscountGrid($products);

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['AmountPrefix'] = '';
				$GLOBALS['AmountPostfix'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['AmountPrefix'] = GetConfig('CurrencyToken');
				$GLOBALS['AmountPostfix'] = '';
			}

			$this->template->display('customers.group.form.tpl');
		}

		/**
		* .
		* Save the details of the new customer group
		*
		* @return Void
		*/
		private function CreateCustomerGroup2($MsgDesc = "", $MsgStatus = "")
		{
			$data = $this->GetCustomerGroupData();

			if (!$this->ValidateCustomerGroupData($data, $error)) {
				return $this->CreateCustomerGroup($error, MSG_ERROR);
			}

			// Create the new group
			$newGroup = $data;

			if (array_key_exists("accesscategorieslist", $data) && is_array($data["accesscategorieslist"])) {
				$newGroup["accesscategories"] = $data["accesscategorieslist"];
			}

			$GroupId = $this->groupEntity->add($newGroup);

			if (!isId($GroupId)) {
				$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
				return $this->CreateCustomerGroup(sprintf(GetLang("CustomerGroupDBError"), $err), MSG_ERROR, true);
			}

			if (!$this->_CreateGroupLevelDiscounts($GroupId, $data['discountlist'])) {
				$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
				return $this->CreateCustomerGroup(sprintf(GetLang("CustomerGroupDBError"), $err), MSG_ERROR, true);
			}

			// Create a customer view to go with the group
			$viewData = array();
			$viewData['custGroupId'] = $GroupId;
			$view_id = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->SaveSearch($data['groupname'], $viewData);

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($data['groupname']);

			// Update the customer groups data store
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCustomerGroups();

			// Group was saved
			$this->ManageCustomerGroups(GetLang("CustomerGroupCreated"), MSG_SUCCESS);
		}

		private function GetCustomerGroupData($groupId=0)
		{
			if (isId($groupId)) {
				$data = $this->groupEntity->get($groupId);

				if (!$data) {
					return false;
				}

				$data['accesscategorieslist'] = array();
				$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]customer_group_categories WHERE customergroupid = " . (int)$groupId);
				while ($category = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$data['accesscategorieslist'][] = $category['categoryid'];
				}

				$data['discountlist'] = array();
				$prodCount = 0;
				$catCount = 0;

				$query = "
							SELECT *
							FROM [|PREFIX|]customer_group_discounts
							WHERE customergroupid=" . (int)$groupId . "
							ORDER BY groupdiscountid ASC";
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
				while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {

					if (isc_strtolower($row['discounttype']) == 'product') {
						$prodCount++;
						$discountId = $prodCount;
					} else {
						$catCount++;
						$discountId = $catCount;
					}

					$data['discountlist'][] = array(
							'discountid' => $discountId,
							'discounttype' => $row['discounttype'],
							'catorprodid' => $row['catorprodid'],
							'discountpercent' => $row['discountpercent'],
							'appliesto' => $row['appliesto'],
							'discountmethod' => $row['discountmethod']
					);
				}
			} else {
				$data = array();
				$data['groupname'] = @$_POST['groupname'];

				if (isset($_POST['groupId'])) {
					$data['customergroupid'] = (int)$_POST['groupId'];
				} else {
					$data['customergroupid'] = '';
				}

				if (isset($_POST['discount'])) {
					$data['discount'] = $_POST['discount'];
				} else {
					$data['discount'] = 0;
				}

				if (isset($_POST['storeDiscountMethod'])) {
					$data['discountmethod'] = $_POST['storeDiscountMethod'];
				} else {
					$data['discountmethod'] = 'price';
				}

				if (isset($_POST['isdefault'])) {
					$data['isdefault'] = 1;
				} else {
					$data['isdefault'] = 0;
				}

				$data['accesscategorieslist'] = array();

				if (isset($_POST['accesscategories'])) {
					$data['categoryaccesstype'] = 'all';
				} else {
					if (isset($_POST['accesscategorieslist'])) {
						$data['accesscategorieslist'] = array_filter($_POST['accesscategorieslist'], 'isc_is_int');
					}

					if (!empty($data['accesscategorieslist'])) {
						$data['categoryaccesstype'] = 'specific';
					} else {
						$data['categoryaccesstype'] = 'none';
					}
				}

				$data['discountlist'] = array();
				$prodCount = 0;
				$catCount = 0;

				if (isset($_POST['discountlist'])) {
					foreach ($_POST['discountlist'] as $type => $discounts) {
						if (isc_strtolower($type) !== 'product' && isc_strtolower($type) !== 'category') {
							continue;
						}

						foreach ($discounts as $discount) {
							if (!isset($discount['discounttype']) || !isset($discount['catorprodid']) || !isset($discount['discountpercent']) || !isset($discount['appliesto']) || !isset($discount['discountmethod'])) {
								continue;
							}

							if (isc_strtolower($type) == 'product') {
								$prodCount++;
								$discountId = $prodCount;
							} else {
								$catCount++;
								$discountId = $catCount;
							}

							$data['discountlist'][] = array(
								'discountid' => $discountId,
								'discounttype' => isc_strtoupper($discount['discounttype']),
								'catorprodid' => $discount['catorprodid'],
								'discountpercent' => $discount['discountpercent'],
								'appliesto' => $discount['appliesto'],
								'discountmethod' => $discount['discountmethod']
							);
						}
					}
				}
			}

			return $data;
		}

		private function ValidateCustomerGroupData($data, &$error)
		{
			if (trim($data['groupname']) == '') {
				$error = GetLang('CustomerGroupEnterName');
				return false;
			}

			$nameCheckSQL = "
				SELECT *
				FROM [|PREFIX|]customer_groups
				WHERE groupname = '" . $GLOBALS['ISC_CLASS_DB']->Quote($data['groupname']) . "'
			";

			if (isId($data['customergroupid'])) {
				$nameCheckSQL .= " AND customergroupid != " . (int)$data['customergroupid'];
			}

			if ($GLOBALS['ISC_CLASS_DB']->CountResult($nameCheckSQL)) {
				$error = sprintf(GetLang('CustomerGroupAlreadyExists'), isc_html_escape($data['groupname']));
				return false;
			}

			if (isc_strtolower($data['categoryaccesstype']) == 'specific' && (!is_array($data['accesscategorieslist']) || empty($data['accesscategorieslist']))) {
				$error = GetLang('CustomerGroupsEmptyCategoryList');
				return false;
			}

			if (isc_strtolower($data['discountmethod']) == 'percent' && (CFloat($data['discount']) < 0 || CFloat($data['discount']) > 100)) {
				$error = GetLang('CustomerGroupEnterStoreDiscount');
				return false;
			}

			if (isc_strtolower($data['discountmethod']) !== 'percent' && CPrice($data['discount']) == '') {
				$error = GetLang('CustomerGroupEnterStoreDiscount');
				return false;
			}

			$products = array();
			$categories = array();

			if (is_array($data['discountlist']) && !empty($data['discountlist'])) {
				foreach ($data['discountlist'] as $discount) {

					if (isc_strtolower($discount['discounttype']) == 'product') {
						$stack =& $products;
						$countSQL = "SELECT * FROM [|PREFIX|]products WHERE productid = " . (int)$discount['catorprodid'];
						$pageNavVar = 'pageNavToProduct';
					} else {
						$stack =& $categories;
						$countSQL = "SELECT * FROM [|PREFIX|]categories WHERE categoryid = " . (int)$discount['catorprodid'];
						$pageNavVar = 'pageNavToCategory';
					}

					if (!isId($discount['catorprodid']) || !$GLOBALS['ISC_CLASS_DB']->CountResult($countSQL)) {
						$GLOBALS[$pageNavVar] = max(1, ceil($discount['discountid'] / ISC_GROUPDISCOUNT_ITEMS_PER_PAGE));
						$error = sprintf(GetLang('CustomerGroupsInvalid' . ucfirst(isc_strtolower($discount['discounttype']))), $discount['discountid']);
						return false;
					}

					if (array_key_exists($discount['catorprodid'], $stack)) {
						$GLOBALS[$pageNavVar] = max(1, ceil($discount['discountid'] / ISC_GROUPDISCOUNT_ITEMS_PER_PAGE));
						$error = sprintf(GetLang('CustomerGroupsDuplicate' . ucfirst(isc_strtolower($discount['discounttype']))), $discount['discountid'], $stack[$discount['catorprodid']]);
						return false;
					} else {
						$stack[$discount['catorprodid']] = $discount['discountid'];
					}

					if (trim($discount['discountpercent']) == '') {
						$GLOBALS[$pageNavVar] = max(1, ceil($discount['discountid'] / ISC_GROUPDISCOUNT_ITEMS_PER_PAGE));
						$error = sprintf(GetLang('CustomerGroupsInvalid' . ucfirst(isc_strtolower($discount['discounttype'])) . 'Amount'), $discount['discountid']);
						return false;
					}

					if (isc_strtolower($discount['discountmethod']) == 'percent' && (CFloat($discount['discountpercent']) < 0 || CFloat($discount['discountpercent']) > 100)) {
						$GLOBALS[$pageNavVar] = max(1, ceil($discount['discountid'] / ISC_GROUPDISCOUNT_ITEMS_PER_PAGE));
						$error = sprintf(GetLang('CustomerGroupsInvalid' . ucfirst(isc_strtolower($discount['discounttype'])) . 'Amount'), $discount['discountid']);
						return false;
					}

					if (isc_strtolower($discount['discountmethod']) !== 'percent' && CPrice($discount['discountpercent']) == '') {
						$GLOBALS[$pageNavVar] = max(1, ceil($discount['discountid'] / ISC_GROUPDISCOUNT_ITEMS_PER_PAGE));
						$error = sprintf(GetLang('CustomerGroupsInvalid' . ucfirst(isc_strtolower($discount['discounttype'])) . 'Amount'), $discount['discountid']);
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Return the discount product/category grid
		 *
		 * Method will return either the products or categories HTML grid
		 *
		 * @access public
		 * @return string The email accounts HTML grid
		 */
		public function ManageGroupDiscountGrid($data=null)
		{
			if (!is_array($data)) {
				$data = $_POST;
			}

			if (!is_array($data)) {
				$data = array();
			}

			if (!isset($data['items']) || !is_array($data['items'])) {
				$data['items'] = array();
			}

			if (!isset($data['type']) || !is_string($data['type'])) {
				$data['type'] = '';
			}

			if (!isset($data['total']) || !isc_is_int($data['total'])) {
				$data['total'] = 0;
			}

			$items = $data['items'];
			$type = isc_strtolower($data['type']);
			$total = $data['total'];

			if (isset($_GET['page']) && $_GET['page'] > 1) {
				$page = $_GET['page'];
			} else {
				$page = 1;
			}

			/**
			 * Special global for naving ot a page when displaying an error
			 */
			if (isc_strtolower($type) == 'product' && isset($GLOBALS['pageNavToProduct'])) {
				$page = $GLOBALS['pageNavToProduct'];
			} else if (isc_strtolower($type) == 'category' && isset($GLOBALS['pageNavToCategory'])) {
				$page = $GLOBALS['pageNavToCategory'];
			}

			if ($page == 1) {
				$start = 1;
			} else {
				$start = ($page * ISC_GROUPDISCOUNT_ITEMS_PER_PAGE) - (ISC_GROUPDISCOUNT_ITEMS_PER_PAGE - 1);
			}

			$start = $start-1;
			$numPages = ceil($total / ISC_GROUPDISCOUNT_ITEMS_PER_PAGE);

			if ($total > ISC_GROUPDISCOUNT_ITEMS_PER_PAGE) {
				$GLOBALS['Nav'] = sprintf('(%s %d of %d) &nbsp;&nbsp;&nbsp;', GetLang('Page'), $page, $numPages);
				$GLOBALS['Nav'] .= BuildPagination($total, ISC_GROUPDISCOUNT_ITEMS_PER_PAGE, $page, 'index.php?ToDo=viewCustomerGroupDiscounts&type=' . urlencode($type), 'getGroupDiscountDataBlock');
			} else {
				$GLOBALS['Nav'] = '';
			}

			$html = '';

			foreach ($items as $item) {
				$GLOBALS['DiscountMethodPercent'] = '';
				$GLOBALS['DiscountMethodPrice'] = '';
				$GLOBALS['DiscountMethodFixed'] = '';

				if ($type == 'product') {
					$GLOBALS['ProductId'] = (int)$item['discountid'];
					$GLOBALS['CatOrProdId'] = (int)$item['catorprodid'];

					if (isId($item['discountid'])) {
						$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT prodname FROM [|PREFIX|]products WHERE productid=" . (int)$item['catorprodid']);
						$prodname = $GLOBALS['ISC_CLASS_DB']->FetchOne($result, 'prodname');
					}

					if (!isId($item['discountid']) || $prodname == '') {
						$item['catorprodid'] = '';
						$GLOBALS['SelectedItemName'] = GetLang('NoneSelected');
					} else {
						$GLOBALS['SelectedItemName'] = isc_html_escape($prodname);
					}
				} else {
					$GLOBALS['CategoryId'] = (int)$item['discountid'];
					$GLOBALS['CategoryOptions'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions(array($item['catorprodid']), "<option %s value='%d'>%s</option>", "selected=\"selected\"", "", false);
					$GLOBALS['DiscountCatTypeCat'] = '';
					$GLOBALS['DiscountCatTypeCatAndSub'] = '';

					if (isc_strtolower($item['appliesto']) == 'category_and_subcats') {
						$GLOBALS['DiscountCatTypeCatAndSub'] = 'selected';
					} else {
						$GLOBALS['DiscountCatTypeCat'] = 'selected';
					}
				}

				if ($item['discountmethod'] == 'percent') {
					$GLOBALS['AmountPrefix'] = '';
					$GLOBALS['AmountPostfix'] = '%';
					$GLOBALS['DiscountMethodPercent'] = 'selected';
				} else {
					if ($item['discountmethod'] == 'fixed') {
						$GLOBALS['DiscountMethodFixed'] = 'selected';
					} else {
						$GLOBALS['DiscountMethodPrice'] = 'selected';
					}

					if (GetConfig('CurrencyLocation') == 'right') {
						$GLOBALS['AmountPrefix'] = '';
						$GLOBALS['AmountPostfix'] = GetConfig('CurrencyToken');
					} else {
						$GLOBALS['AmountPrefix'] = GetConfig('CurrencyToken');
						$GLOBALS['AmountPostfix'] = '';
					}
				}

				if ((int)$item['discountpercent'] == $item['discountpercent']) {
					$GLOBALS['DiscountAmount'] = (int)$item['discountpercent'];
				} else {
					$GLOBALS['DiscountAmount'] = CFloat($item['discountpercent']);
				}

				$html .= $this->template->render('Snippets/CustomerGroup'.ucfirst(isc_strtolower($type)).'Row.html');
			}

			if ($numPages <= 1) {
				$GLOBALS['HidePagingNav'] = 'none';
			} else {
				$GLOBALS['HidePagingNav'] = '';
			}

			$GLOBALS['Type'] = isc_html_escape($type);
			$GLOBALS['CustomerGroupDiscountGrid'] = $html;
			return $this->template->render('customers.groups.discount.grid.tpl');
		}

		private function buildHiddenDiscountFields($discounts, &$products, &$categories)
		{
			$products = array(
				'items' => array(),
				'type' => 'product',
				'total' => 0
			);

			$categories = array(
				'items' => array(),
				'type' => 'category',
				'total' => 0
			);

			$html = '';

			foreach ($discounts as $discount) {
				if (isc_strtolower($discount['discounttype']) == 'product') {
					if ($products['total'] < ISC_GROUPDISCOUNT_ITEMS_PER_PAGE) {
						$products['items'][] = $discount;
						$GLOBALS['Type'] = 'product';
					}

					$products['total']++;
				} else if (isc_strtolower($discount['discounttype']) == 'category') {
					if ($categories['total'] < ISC_GROUPDISCOUNT_ITEMS_PER_PAGE) {
						$categories['items'][] = $discount;
						$GLOBALS['Type'] = 'category';
					}

					$categories['total']++;
				}

				$GLOBALS['DiscountId'] = (int)$discount['discountid'];
				$GLOBALS['DiscountType'] = isc_html_escape($discount['discounttype']);
				$GLOBALS['CatOrProdId'] = isc_html_escape($discount['catorprodid']);
				$GLOBALS['DiscountPercent'] = isc_html_escape($discount['discountpercent']);
				$GLOBALS['AppliesTo'] = isc_html_escape($discount['appliesto']);
				$GLOBALS['DiscountMethod'] = isc_html_escape($discount['discountmethod']);

				$html .= $this->template->render('Snippets/CustomerGroupHiddenBlock.html');
			}

			return $html;
		}

		/**
		 * EditCustomerGroup
		 * Load up a customer group's details for editing. Why am I working on this at 11:10pm on a Saturday night?! - Mitch
		 *
		 * @param string $MsgDesc The optional message to display
		 * @param string $MsgStatus The optional status of the message
		 * @param bool $PreservePost TRUE to use the REQUEST variable, FALSE to read from the database. Default is FALSE
		 * @return Void
		 */
		private function EditCustomerGroup($MsgDesc = "", $MsgStatus = "", $PreservePost = false)
		{
			$groupId = 0;

			if (isset($_GET['groupId']) && is_numeric($_GET['groupId'])) {
				$groupId = (int)$_GET['groupId'];
			}

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			if ($PreservePost) {
				$data = $this->GetCustomerGroupData(0);
			} else {
				$data = $this->GetCustomerGroupData($groupId);
			}

			if (!$data) {
				return $this->ManageCustomerGroups();
			}

			$GLOBALS['GroupId'] = $groupId;
			$GLOBALS['GroupName'] = isc_html_escape($data['groupname']);
			if ($data['discountmethod'] == 'percent') {
				$GLOBALS['Discount'] = FormatNumber($data['discount']);
			}
			else {
				$GLOBALS['Discount'] = FormatPrice($data['discount'], false, false);
			}

			if ($data['discountmethod'] == '') {
				$data['discountmethod'] = 'price';
			}

			$GLOBALS['StoreDiscountMethod' . ucfirst(isc_strtolower($data['discountmethod']))] = 'selected';

			if ($data['isdefault']) {
				$GLOBALS['IsDefault'] = "checked='checked'";
			}

			$access_cats = array();
			if ($data['categoryaccesstype'] == "all") {
				$GLOBALS['AccessAllCategories'] = "checked='checked'";
				$GLOBALS['HideAccessCategories'] = "none";
				$GLOBALS['HideAccessCatLinks'] = "none";
			}

			$GLOBALS['FormAction'] = "editCustomerGroup2";
			$GLOBALS['Title'] = GetLang("EditACustomerGroup");

			// Reuse them for the categories which the group has access to and make them all selected by default
			$GLOBALS['AccessCategoryOptions'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions($data['accesscategorieslist'], "<option %s value='%d'>%s</option>", 'selected="selected"', "", false);

			$GLOBALS['HiddenDiscounts'] = $this->buildHiddenDiscountFields($data['discountlist'], $products, $categories);

			$GLOBALS['HideNoCatgeory'] = '';
			$GLOBALS['HideNoProduct'] = '';
			$GLOBALS['HideCategoryGridContainer'] = '';
			$GLOBALS['HideProductGridContainer'] = '';

			if (empty($categories['items'])) {
				$GLOBALS['HideCategoryGridContainer'] = 'none';
			} else {
				$GLOBALS['HideNoCatgeory'] = 'none';
			}

			if (empty($products['items'])) {
				$GLOBALS['HideProductGridContainer'] = 'none';
			} else {
				$GLOBALS['HideNoProduct'] = 'none';
			}

			/**
			 * Add in the category and product items
			 */
			$GLOBALS['DiscountsPerPage'] = ISC_GROUPDISCOUNT_ITEMS_PER_PAGE;
			$GLOBALS['CategoryDataGrid'] = $this->ManageGroupDiscountGrid($categories);
			$GLOBALS['ProductDataGrid'] = $this->ManageGroupDiscountGrid($products);

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['AmountPrefix'] = '';
				$GLOBALS['AmountPostfix'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['AmountPrefix'] = GetConfig('CurrencyToken');
				$GLOBALS['AmountPostfix'] = '';
			}

			$this->template->display('customers.group.form.tpl');
		}

		/**
		* EditCustomerGroup2
		* Save the details of the updated customer group
		*
		* @return Void
		*/
		private function EditCustomerGroup2()
		{
			$data = $this->GetCustomerGroupData();

			if (!$this->ValidateCustomerGroupData($data, $error)) {
				return $this->CreateCustomerGroup($error, MSG_ERROR);
			}

			// Create the new group
			$savedata = $data;

			if (array_key_exists("accesscategorieslist", $data) && is_array($data["accesscategorieslist"])) {
				$savedata["accesscategories"] = $data["accesscategorieslist"];
			}

			if (!$this->groupEntity->edit($savedata)) {
				$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
				return $this->EditCustomerGroup(sprintf(GetLang("CustomerGroupDBError"), $err), MSG_ERROR, true);
			}

			if (!$this->_CreateGroupLevelDiscounts($data['customergroupid'], $data['discountlist'], true)) {
				$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
				return $this->EditCustomerGroup(sprintf(GetLang("CustomerGroupDBError"), $err), MSG_ERROR, true);
			}

			// Create a customer view to go with the group
			$viewData = array();
			$viewData['custGroupId'] = $data['customergroupid'];
			$view_id = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->SaveSearch($data['groupname'], $viewData);

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($data['groupname']);

			// Update the customer groups data store
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCustomerGroups();

			// Group was saved
			$this->ManageCustomerGroups(GetLang("CustomerGroupUpdated"), MSG_SUCCESS);
		}

		/**
		 * Login as the customer
		 *
		 * Method will log in the user as the customer in the front end of the store
		 *
		 * @access private
		 * @param int $customerId The optional customer ID. Default will be $_REQUEST['customerId']
		 * @return Void
		 */
		private function LoginCustomer($customerId=null)
		{
			if (is_null($customerId)) {
				$customerId = isc_html_escape((int)$_REQUEST['customerId']);
			}

			// Make sure the customer exists
			if (!CustomerExists($customerId)) {
				// The customer doesn't exist
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {
					$this->ManageCustomers(GetLang('CustomerDoesntExist'), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				return;
			}

			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			$GLOBALS['ISC_CLASS_CUSTOMER']->LoginCustomerById($customerId);
		}

		/**
		 * Add a customer page
		 *
		 * Method will construct the add customer page
		 *
		 * @access public
		 * @param string $MsgDesc The optional message to display
		 * @param string $MsgStatus The optional status of the message
		 * @param bool $PreservePost TRUE to use the REQUEST variable, FALSE to read from the database. Default is FALSE
		 * @return Void
		 */
		public function AddCustomerStep1($MsgDesc = "", $MsgStatus = "", $PreservePost=false)
		{
			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			$flashMessages = GetFlashMessages();
			if(is_array($flashMessages) && !empty($flashMessages)) {
				$GLOBALS['Message'] = '';
				foreach($flashMessages as $flashMessage) {
					$GLOBALS['Message'] .= MessageBox($flashMessage['message'], $flashMessage['type']);
				}
			}

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}

			if (isset($_REQUEST['currentTab'])) {
				$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
			} else {
				$GLOBALS['CurrentTab'] = 0;
			}

			$customerGroupId = 0;

			if($PreservePost == true) {
				$customer = $this->_GetCustomerData(0);
				$customerGroupId = $customer['custgroupid'];

				$GLOBALS['CustomerFirstName'] = $customer['custconfirstname'];
				$GLOBALS['CustomerLastName'] = $customer['custconlastname'];
				$GLOBALS['CustomerCompany'] = $customer['custconcompany'];
				$GLOBALS['CustomerEmail'] = $customer['custconemail'];
				$GLOBALS['CustomerPhone'] = $customer['custconphone'];
				$GLOBALS['CustomerPassword'] = $customer['custpassword'];

				if (array_key_exists('custPasswordConfirm', $_POST)) {
					$GLOBALS['CustomerPasswordConfirm'] = $_POST['custPasswordConfirm'];
				}

				$GLOBALS['CustomerStoreCredit'] = FormatPrice($customer['custstorecredit'], false, false);
				$GLOBALS['CustomerGroupId'] = $customer['custgroupid'];
				$GLOBALS['CustomerShippingAddressGrid'] = $this->ManageCustomerAddressGrid();
			}

			$GLOBALS['CustomerAddressDeleteDisabled'] = 'disabled="disabled"';
			$GLOBALS['CustomerGroupOptions'] = $this->GetCustomerGroupsAsOptions($customerGroupId);

			$GLOBALS['FormAction'] = "addCustomer2";
			$GLOBALS['Title'] = GetLang('AddCustomerTitle');
			$GLOBALS['Intro'] = GetLang('AddCustomerIntro');
			$GLOBALS['CustomerAddressListWarning'] = GetLang('CustomerAddressNoAddressesNewCustomer');
			$GLOBALS['HideCustomerAddressButtons'] = 'none';
			$GLOBALS['PasswordRequired'] = '<span class="Required">*</span>';
			$GLOBALS['PasswordConfirmRequired'] = '<span class="Required">*</span>';
			$GLOBALS['PasswordRequiredCheck'] = '1';
			$GLOBALS['PasswordLabel'] = GetLang('CustomerPassword');
			$GLOBALS['PasswordHelp'] = GetLang('CustomerPasswordHelp');
			$GLOBALS['PasswordConfirmHelp'] = GetLang('CustomerPasswordConfirmHelp');
			$GLOBALS['PasswordConfirmError'] = GetLang('CustomerPasswordConfirmError');
			$GLOBALS['CustomFieldsAccountFormId'] = FORMFIELDS_FORM_ACCOUNT;
			$GLOBALS['CustomFields'] = '';

			/**
			 * Custom fields
			 */
			if (gzte11(ISC_MEDIUMPRINT)) {
				if ($PreservePost) {
					$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);
				} else {
					$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT);
				}

				foreach (array_keys($fields) as $fieldId) {
					if ($fields[$fieldId]->record['formfieldprivateid'] !== '') {
						continue;
					}

					$GLOBALS['CustomFields'] .= $fields[$fieldId]->loadForFrontend() . "\n";
				}
			}

			/**
			 * Add this to generate our JS event script
			 */
			$GLOBALS['FormFieldEventData'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

			$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndAddAnother');
			$GLOBALS['CancelMessage'] = GetLang('ConfirmCancelCustomer');
			$this->template->display('customer.form.tpl');
		}

		/**
		 * Add a customer
		 *
		 * Method will add a customer from the add customer screen
		 *
		 * @access public
		 * @return Void
		 */
		public function AddCustomerStep2()
		{
			// Get the information from the form and add it to the database
			$StoreCustomer = array();
			$Postcustomer = $this->_GetCustomerData(0, false);
			$err = "";

			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);
			$errmsg = '';
			if (!validateFieldData($fields, $errmsg)) {
				return $this->AddCustomerStep1($errmsg, MSG_ERROR, true);
			}
			
			if (!$this->_ValidateCustomerFormData(0, $Error)) {
				return $this->AddCustomerStep1($Error, MSG_ERROR, true);
			}

			$StoreCustomer = $Postcustomer;

			if ($StoreCustomer['custgroupid'] == '') {
				$StoreCustomer['custgroupid'] = '0';
			}

			if (gzte11(ISC_MEDIUMPRINT)) {
				$formSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ACCOUNT);
				if (isId($formSessionId)) {
					$StoreCustomer['custformsessionid'] = $formSessionId;
				}
			}

			$customerId = $this->customerEntity->add($StoreCustomer);
			if (isId($customerId)) {

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($customerId, trim($Postcustomer['custconfirstname'] . ' ' . $Postcustomer['custconlastname']));

				if (isset($_POST['addanother'])) {
					$this->AddCustomerStep1(GetLang('CustomerAddedSuccessfully'), MSG_SUCCESS);
				} else if (isset($_POST['addaddresses'])) {
					$_REQUEST['customerId'] = $customerId;
					$this->AddCustomerAddressStep1(GetLang('CustomerAddedSuccessfullyGoToAddress'), MSG_SUCCESS);
				} else {
					FlashMessage(GetLang('CustomerAddedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewCustomers');
				}
			} else {
				$this->AddCustomerStep1(sprintf(GetLang("CustomerAddedFailed"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR, true);
			}
		}

		/**
		 * Return the customer address grid
		 *
		 * Method will return the customer address HTML grid
		 *
		 * @access public
		 * @return string The customer address HTML grid
		 */
		public function ManageCustomerAddressGrid()
		{
			// Show a list of products in a table
			$page = 0;
			$start = 0;
			$numAddresses = 0;
			$numPages = 0;
			$GLOBALS['Nav'] = "";
			$max = 0;
			$customerId = 0;
			$customerHash = "";

			if (isset($_GET['customerId'])) {
				$customerId = $_GET['customerId'];
			} else if (isset($GLOBALS['CustomerId'])) {
				$customerId = $GLOBALS['CustomerId'];
			}

			if (isset($_REQUEST['customerHash'])) {
				$customerHash = $_POST['customerHash'];
			} else if (isset($GLOBALS['CustomerHash'])) {
				$customerHash = $GLOBALS['CustomerHash'];
			}

			$customerId = urlencode(isc_html_escape($customerId));
			$customerHash = urlencode(isc_html_escape($customerHash));

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			} else {
				$page = 1;
			}

			if ($page < 1) {
				$page = 1;
			}

			// Limit the number of questions returned
			if ($page == 1) {
				$start = 0;
			} else {
				$start = ($page - 1) * ISC_CUSTOMER_ADDRESS_PER_PAGE;
			}

			$result = $this->_GetCustomerShippingList($start, $numAddresses, $customerId, $customerHash);
			$numPages = ceil($numAddresses / ISC_CUSTOMER_ADDRESS_PER_PAGE);

			// Add the "(Page x of n)" label
			if($numAddresses > ISC_CUSTOMER_ADDRESS_PER_PAGE) {
				$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numAddresses);
				$GLOBALS['Nav'] .= BuildPagination($numAddresses, ISC_CUSTOMER_ADDRESS_PER_PAGE, $page, 'index.php?ToDo=viewCustomerAddressGrid&customerId=' . $customerId . '&customerHash=' . $customerHash);
			}
			else {
				$GLOBALS['Nav'] = "";
			}

			$GLOBALS['AddressGrid'] = '';

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$row = array_map('isc_html_escape', $row);

				$GLOBALS['AddressId'] = $row['shipid'];
				$GLOBALS['CustomerId'] = $row['shipcustomerid'];
				$GLOBALS['FullName'] = trim($row['shipfirstname'] . ' ' . $row['shiplastname']);
				$GLOBALS['StreetAddress'] = $row['shipaddress1'];

				if (!is_null($row['shipaddress2']) && $row['shipaddress2'] !== '') {
					$GLOBALS['StreetAddress'] .= '<br />' . $row['shipaddress2'];
				}

				$GLOBALS['City'] = $row['shipcity'];
				$GLOBALS['State'] = $row['shipstate'];
				$GLOBALS['PostCode'] = $row['shipzip'];
				$GLOBALS['Country'] = $row['shipcountry'];
				$GLOBALS['CountryCode'] = $row['shipcountrycode'];
				$GLOBALS['Phone'] = $row['shipphone'];

				if (file_exists(ISC_BASE_PATH . '/lib/flags/' . strtolower($row['shipcountrycode']) . '.gif')) {
					$GLOBALS['CountryImg'] = '<img src="' . GetConfig('AppPath') . '/lib/flags/' . strtolower($row['shipcountrycode']) . '.gif" alt="" style="vertical-align: middle;" />';
				} else {
					$GLOBALS['CountryImg'] = '';
				}

				// Are we allowed to edit this address?
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {
					$GLOBALS['EditCustomerLink'] = '<a title="' . GetLang('EditCustomerAddress') . ' class="Action" href="index.php?ToDo=editCustomerAddress&amp;customerId=' . urlencode(isc_html_escape($row['shipcustomerid'])) . '&addressId=' . urlencode(isc_html_escape($row['shipid'])) . '">' . GetLang('Edit') . '</a>';
				} else {
					$GLOBALS['EditCustomerLink'] = '<a class="Action" disabled>' . GetLang('Edit') . '</a>';
				}

				// Are we allowed to delete this address?
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Customers)) {
					$GLOBALS['DeleteCustomerLink'] = '<a href="#" onclick="confirmDeleteAddressBoxes(' . (int)$row['shipid'] . '); return false;">' . GetLang('Delete') . '</a>';
				} else {
					$GLOBALS['DeleteCustomerLink'] = '<a class="Action" disabled>' . GetLang('Delete') . '</a>';
				}

				if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {
					$GLOBALS['CustomerAddressDeleteDisabled'] = 'DISABLED';
				}

				if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Customers)) {
					$GLOBALS['CustomerAddressDeleteDisabled'] = 'DISABLED';
				}

				$GLOBALS['AddressGrid'] .= $this->template->render('customer.address.manage.row.tpl');
			}

			return $this->template->render('customer.address.manage.grid.tpl');
		}

		/**
		 * Get the customer address result resource
		 *
		 * Method will return the customer address database result resource that is used in the ManageCustomerAddressGrid()
		 *
		 * @access private
		 * @return object The customer address database result resource
		 */
		private function _GetCustomerShippingList($start, &$numAddresses, $customerId)
		{
			$query = "SELECT SQL_CALC_FOUND_ROWS a.*, IF(CHAR_LENGTH(a.shipstate) > 0, a.shipstate, s.statename) AS shipstate, IF(CHAR_LENGTH(a.shipcountry) > 0, a.shipcountry, c.countryname) AS shipcountry, c.countryiso2 AS shipcountrycode
					FROM [|PREFIX|]shipping_addresses a
					LEFT JOIN [|PREFIX|]countries c ON a.shipcountryid = c.countryid
					LEFT JOIN [|PREFIX|]country_states s ON a.shipstateid = s.stateid
					WHERE a.shipcustomerid = '" . $GLOBALS["ISC_CLASS_DB"]->Quote($customerId) . "' " . $GLOBALS["ISC_CLASS_DB"]->AddLimit($start, ISC_CUSTOMER_ADDRESS_PER_PAGE);

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$total = $GLOBALS["ISC_CLASS_DB"]->Fetch($GLOBALS["ISC_CLASS_DB"]->Query("SELECT FOUND_ROWS() AS Total"));
			$numAddresses = $total["Total"];

			return $result;
		}

		/**
		 * Add a customer address page
		 *
		 * Method will construct the add customer address page
		 *
		 * @access public
		 * @param string $MsgDesc The optional message to display
		 * @param string $MsgStatus The optional status of the message
		 * @param bool $PreservePost TRUE to use the REQUEST variable, FALSE to read from the database. Default is FALSE
		 * @return Void
		 */
		public function AddCustomerAddressStep1($MsgDesc = "", $MsgStatus = "", $PreservePost=false)
		{
			// Show the form to add the customer address
			$customerId = isc_html_escape((int)$_REQUEST['customerId']);

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			$GLOBALS['CurrentTab'] = 0;
			$GLOBALS['FormAction'] = 'addCustomerAddress2';
			$GLOBALS['Title'] = GetLang('AddCustomerAddressTitle');
			$GLOBALS['Intro'] = GetLang('AddCustomerAddressIntro');
			$GLOBALS['CustomerId'] = $customerId;

			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {
				$GLOBALS['CancelGoToManager'] = '1';
			} else {
				$GLOBALS['CancelGoToManager'] = '';
			}

			$GLOBALS['CustomFieldsAddressFormId'] = FORMFIELDS_FORM_ADDRESS;
			$GLOBALS['AddressFields'] = $this->generateAddressFields('address', $PreservePost);
			$GLOBALS['CustomFields'] = '';

			if (gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['CustomFields'] = $this->generateAddressFields('custom', $PreservePost);
			}

			if ($GLOBALS['CustomFields'] == '') {
				$GLOBALS[base64_decode('SGlkZUN1c3RvbUZpZWxkcw==')] = "none";
			}

			/**
			 * Add this to generate our JS event script
			 */
			$GLOBALS['FormFieldEventData'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

			$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndAddAnother');
			$GLOBALS['CancelMessage'] = GetLang('ConfirmCancelCustomerAddress');
			$this->template->display('customer.address.form.tpl');
		}

		/**
		 * Add a customer address
		 *
		 * Method will add a customer address from the add customer address screen
		 *
		 * @access public
		 * @return Void
		 */
		public function AddCustomerAddressStep2()
		{
			// Get the information from the form and add it to the database
			$customerId = isc_html_escape((int)@$_POST['customerId']);
			$err = "";

			if (!isId($customerId)) {
				return $this->AddCustomerAddressStep1(GetLang('CustomerAddressAddedInvalid'), MSG_ERROR, true);
			}

			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, true);

			if (!$this->vaidateAddressFields($fields, $errmsg)) {
				return $this->AddCustomerAddressStep1($errmsg, MSG_ERROR, true);
			}

			$addressId = $this->saveAddressFields($fields, $customerId);

			if (isId($addressId)) {

				/**
				 * Log the action. Find the name first
				 */
				$firstName = $lastName = '';
				foreach (array_keys($fields) as $fieldId) {
					if (strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'firstname') {
						$firstName = $fields[$fieldId]->getValue();
					} else if (strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'lastname') {
						$lastName = $fields[$fieldId]->getValue();
					}
				}

				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($addressId, trim($firstName . ' ' . $lastName));

				if (isset($_POST['addanother'])) {
					$_REQUEST['customerId'] = $customerId;
					$this->AddCustomerAddressStep1(GetLang('CustomerAddressAddedSuccessfully'), MSG_SUCCESS);
				} else if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {
					$_GET['customerId'] = $customerId;
					$_REQUEST['currentTab'] = 1;
					$this->EditCustomerStep1(GetLang('CustomerAddressAddedSuccessfully'), MSG_SUCCESS);
				} else {
					FlashMessage(GetLang('CustomerAddressAddedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewCustomers');
				}
			} else {
				$this->AddCustomerAddressStep1(sprintf(GetLang("CustomerAddressAddedFailed"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR, true);
			}
		}

		/**
		 * Edit a customer address page
		 *
		 * Method will construct the edit customer address page
		 *
		 * @access public
		 * @param string $MsgDesc The optional message to display
		 * @param string $MsgStatus The optional status of the message
		 * @param bool $PreservePost TRUE to use the REQUEST variable, FALSE to read from the database. Default is FALSE
		 * @return Void
		 */
		public function EditCustomerAddressStep1($MsgDesc = "", $MsgStatus = "", $PreservePost=false)
		{
			// Show the form to edit the customer address
			$addressId = isc_html_escape((int)$_REQUEST['addressId']);
			$customerId = isc_html_escape((int)$_REQUEST['customerId']);

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			// Make sure the address exists
			if (!$this->shippingEntity->get($addressId, $customerId)) {
				$GLOBALS['CurrentTab'] = 1;
				$_GET['customerId'] = $customerId;
				$this->EditCustomerStep1(GetLang('CustomerAddressDoesnotExist'), MSG_ERROR, true);
				return;
			}

			$GLOBALS['CurrentTab'] = 0;
			$GLOBALS['Title'] = GetLang('EditCustomerAddressTitle');
			$GLOBALS['Intro'] = GetLang('EditCustomerAddressIntro');
			$GLOBALS['FormAction'] = 'editCustomerAddress2';
			$GLOBALS['AddressId'] = $addressId;
			$GLOBALS['CustomerId'] = $customerId;
			$GLOBALS['CustomFieldsAddressFormId'] = FORMFIELDS_FORM_ADDRESS;
			$GLOBALS['AddressFields'] = $this->generateAddressFields('address', $PreservePost, $addressId);
			$GLOBALS['CustomFields'] = '';

			if (gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['CustomFields'] = $this->generateAddressFields('custom', $PreservePost, $addressId);
			}

			if ($GLOBALS['CustomFields'] == '') {
				$GLOBALS[base64_decode('SGlkZUN1c3RvbUZpZWxkcw==')] = "none";
			}

			/**
			 * Add this to generate our JS event script
			 */
			$GLOBALS['FormFieldEventData'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

			$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndContinueEditing');
			$GLOBALS['CancelMessage'] = GetLang('ConfirmCancelCustomerAddress');
			$this->template->display('customer.address.form.tpl');
		}

		/**
		 * Edit a customer address
		 *
		 * Method will edit a customer address from the edit customer address screen
		 *
		 * @access public
		 * @return Void
		 */
		public function EditCustomerAddressStep2()
		{
			// Get the information from the form and add it to the database
			$addressId = (int)@$_POST['addressId'];
			$customerId = (int)@$_POST['customerId'];
			$errmsg = '';

			if (!isId($addressId) || !isId($customerId)) {
				return $this->EditCustomerAddressStep1(GetLang('CustomerAddressUpdatedInvalid'), MSG_ERROR, true);
			}

			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, true);

			if (!$this->vaidateAddressFields($fields, $errmsg)) {
				return $this->EditCustomerAddressStep1($errmsg, MSG_ERROR, true);
			}

			if ($this->saveAddressFields($fields, $customerId, $addressId)) {

				/**
				 * Log the action. Find the name first
				 */
				$firstName = $lastName = '';
				foreach (array_keys($fields) as $fieldId) {
					if (strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'firstname') {
						$firstName = $fields[$fieldId]->getValue();
					} else if (strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'lastname') {
						$lastName = $fields[$fieldId]->getValue();
					}
				}

				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($addressId, trim($firstName . ' ' . $lastName));

				if (isset($_POST['addanother'])) {
					$_GET['addressId'] = $addressId;
					$this->EditCustomerAddressStep1(GetLang('CustomerAddressUpdatedSuccessfully'), MSG_SUCCESS);
				} else {
					FlashMessage(GetLang('CustomerAddressUpdatedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=editCustomer&customerId=' . (int)$customerId . '&currentTab=1');
				}
			} else {
				$this->EditCustomerAddressStep1(sprintf(GetLang("CustomerAddressUpdatedFailed"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR);
			}
		}

		/**
		 * Delete customer(s)
		 *
		 * Method will delete a list of customers in the $_POST['addresses'] string/array variable
		 *
		 * @access public
		 * @return Void
		 */
		public function DeleteCustomerAddress()
		{
			$addresses = $_POST['addresses'];
			$customerId = isc_html_escape((int)@$_POST['customerId']);
			$_REQUEST['currentTab'] = 1;

			if (!is_array($addresses)) {
				$addresses = array($addresses);
			} else {
				$addresses = array_map("isc_html_escape", $addresses);
			}

			$addresses = array_filter($addresses, "isId");

			if (!is_array($addresses) || empty($addresses)) {
				if (isId($customerId)) {
					$_GET['customerId'] = $customerId;
					$this->EditCustomerStep1(GetLang('CustomerAddressDeleteInvalid'), MSG_ERROR);
				} else {
					$this->AddCustomerStep1(GetLang('CustomerAddressDeleteInvalid'), MSG_ERROR, true);
				}

				return;
			}

			if ($this->shippingEntity->multiDelete($addresses)) {
				if (isId($customerId)) {
					$_GET['customerId'] = $customerId;
					$this->EditCustomerStep1(GetLang('CustomerAddressDeleteSuccess'), MSG_SUCCESS);
				} else {
					$this->AddCustomerStep1(GetLang('CustomerAddressDeleteSuccess'), MSG_SUCCESS, true);
				}
			} else {
				if (isId($customerId)) {
					$_GET['customerId'] = $customerId;
					$this->EditCustomerStep1(sprintf(GetLang("CustomerAddressDeleteFailed"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR);
				} else {
					$this->AddCustomerStep1(sprintf(GetLang("CustomerAddressDeleteFailed"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR, true);
				}
			}
		}

		/**
		 * Edit a customer page
		 *
		 * Method will construct the edit customer page
		 *
		 * @access public
		 * @param string $MsgDesc The optional message to display
		 * @param string $MsgStatus The optional status of the message
		 * @param bool $PreservePost TRUE to use the REQUEST variable, FALSE to read from the database. Default is FALSE
		 * @return Void
		 */
		public function EditCustomerStep1($MsgDesc = "", $MsgStatus = "", $PreservePost=false)
		{
			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			$flashMessages = GetFlashMessages();
			if(is_array($flashMessages) && !empty($flashMessages)) {
				$GLOBALS['Message'] = '';
				foreach($flashMessages as $flashMessage) {
					$GLOBALS['Message'] .= MessageBox($flashMessage['message'], $flashMessage['type']);
				}
			}

			// Show the form to edit a customer
			$customerId = isc_html_escape((int)$_GET['customerId']);

			// Make sure the customer exists
			if (!CustomerExists($customerId)) {
				// The customer doesn't exist
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Customers)) {
					$this->ManageCustomers(GetLang('CustomerDoesntExist'), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				return;
			}

			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Customer)) {
				$GLOBALS['CustomerAddressAddDisabled'] = 'DISABLED';
			}

			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Customers)) {
				$GLOBALS['CustomerAddressDeleteDisabled'] = 'DISABLED';
			}

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}

			if ($PreservePost == true) {
				$customer = $this->_GetCustomerData(0);
			} else {
				$customer = $this->_GetCustomerData($customerId);
			}

			if (isset($_REQUEST['currentTab'])) {
				$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
			} else {
				$GLOBALS['CurrentTab'] = 0;
			}

			$query = "SELECT *
						FROM [|PREFIX|]shipping_addresses
						WHERE shipcustomerid='" . $GLOBALS['ISC_CLASS_DB']->Quote((int)$customerId) . "'";

			if ($GLOBALS['ISC_CLASS_DB']->CountResult($GLOBALS['ISC_CLASS_DB']->Query($query))) {
				$GLOBALS['CustomerAddressEmptyShow'] = 'none';
			} else {
				$GLOBALS['CustomerAddressEmptyHide'] = 'none';
			}

			$GLOBALS['FormAction'] = "editCustomer2";
			$GLOBALS['CustomerId'] = $customerId;
			$GLOBALS['Title'] = GetLang('EditCustomerTitle');
			$GLOBALS['Intro'] = GetLang('EditCustomerIntro');
			$GLOBALS['CustomerAddressListWarning'] = GetLang('CustomerAddressNoAddresses');
			$GLOBALS['CustomerFirstName'] = $customer['custconfirstname'];
			$GLOBALS['CustomerLastName'] = $customer['custconlastname'];
			$GLOBALS['CustomerCompany'] = $customer['custconcompany'];
			/*$GLOBALS['CustomerRFC'] = $customer['custRFC'];*/
			$GLOBALS['CustomerEmail'] = $customer['custconemail'];
			$GLOBALS['CustomerPhone'] = $customer['custconphone'];
			$GLOBALS['CustomerStoreCredit'] = FormatPrice($customer['custstorecredit'], false, false);
			$GLOBALS['CustomerGroupId'] = $customer['custgroupid'];
			$GLOBALS['CustomerGroupOptions'] = $this->GetCustomerGroupsAsOptions($customer['custgroupid']);
			$GLOBALS['CustomerShippingAddressGrid'] = $this->ManageCustomerAddressGrid();
			$GLOBALS['PasswordRequired'] = '&nbsp;&nbsp;';
			$GLOBALS['PasswordLabel'] = GetLang('CustomerNewPassword');
			$GLOBALS['PasswordHelp'] = GetLang('CustomerNewPasswordHelp');
			$GLOBALS['PasswordConfirmHelp'] = GetLang('CustomerNewPasswordConfirmHelp');
			$GLOBALS['PasswordConfirmError'] = GetLang('CustomerNewPasswordConfirmError');
			$GLOBALS['PasswordConfirmRequired'] = '&nbsp;&nbsp;';
			$GLOBALS['CustomFieldsAccountFormId'] = FORMFIELDS_FORM_ACCOUNT;
			$GLOBALS['CustomFields'] = '';

			/**
			 * Custom fields
			 */
			if (gzte11(ISC_MEDIUMPRINT)) {
				if ($PreservePost) {
					$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);
				} else if (isset($customer['custformsessionid']) && isId($customer['custformsessionid'])) {
					$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, false, $customer['custformsessionid']);
				} else {
					$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT);
				}

				foreach (array_keys($fields) as $fieldId) {
					if ($fields[$fieldId]->record['formfieldprivateid'] !== '') {
						continue;
					}

					$GLOBALS['CustomFields'] .= $fields[$fieldId]->loadForFrontend() . "\n";
				}
			}

			/**
			 * Add this to generate our JS event script
			 */
			$GLOBALS['FormFieldEventData'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

			$GLOBALS['SaveAndAddAnother'] = GetLang('SaveAndContinueEditing');
			$GLOBALS['CancelMessage'] = GetLang('ConfirmCancelCustomer');
			$this->template->display('customer.form.tpl');
		}

		/**
		 * Edit a customer
		 *
		 * Method will edit a customer from the edit customer screen
		 *
		 * @access public
		 * @return Void
		 */
		public function EditCustomerStep2()
		{
			// Get the information from the form and add it to the database
			$customerId = isc_html_escape((int)$_POST['customerId']);
			$StoreCustomer = array();
			$PostCustomer = $this->_GetCustomerData(0, false);
			$err = "";
			
			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);

			$errmsg = '';
			if (!validateFieldData($fields, $errmsg)) {
				$_GET['customerId'] = (int)$_POST['customerId'];
				return $this->EditCustomerStep1($errmsg, MSG_ERROR, true);
			}

			if (!$this->_ValidateCustomerFormData($customerId, $Error)) {
				$_GET['customerId'] = (int)$_POST['customerId'];
				return $this->EditCustomerStep1($Error, MSG_ERROR, true);
			}

			$StoreCustomer = $PostCustomer;
			$StoreCustomer['customerid'] = $customerId;

			if ($StoreCustomer['custgroupid'] == '') {
				$StoreCustomer['custgroupid'] = '0';
			}

			if (array_key_exists("custpassword", $StoreCustomer) && trim($StoreCustomer["custpassword"]) == "") {
				unset($StoreCustomer["custpassword"]);
			}

			if (gzte11(ISC_MEDIUMPRINT)) {
				$existingCustomer = $this->customerEntity->get($customerId);
				if (isId($existingCustomer['custformsessionid'])) {
					$GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ACCOUNT, true, $existingCustomer['custformsessionid']);
				} else {
					$formSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ACCOUNT);
					if (isId($formSessionId)) {
						$StoreCustomer['custformsessionid'] = $formSessionId;
					}
				}
			}

			if ($this->customerEntity->edit($StoreCustomer)) {

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($customerId, trim($PostCustomer['custconfirstname'] . ' ' . $PostCustomer['custconlastname']));

				if (isset($_POST['addanother'])) {
					$_GET['customerId'] = $customerId;
					$this->EditCustomerStep1(GetLang('CustomerUpdatedSuccessfully'), MSG_SUCCESS);
				} else {
					FlashMessage(GetLang('CustomerUpdatedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewCustomers');
				}
			} else {
				$_GET['customerId'] = $customerId;
				$this->EditCustomerStep1(sprintf(GetLang("CustomerUpdatedFailed"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR);
			}
		}

		/**
		 * Validate customer input data
		 *
		 * Method will read the POST data and validate the customer data
		 *
		 * @access private
		 * @param int $CustomerId The optional customer ID relating to the POST data
		 * @param string &$Error The referenced string to store any error messages to
		 * @return bool TRUE if the validation was successful, FALSE otherwise
		 */
		private function _ValidateCustomerFormData($CustomerId=null, &$Error = "")
		{
			$checkFields = array('FirstName', 'LastName', 'Email');

			if (!isId($CustomerId)) {
				$checkFields[] = 'Password';
				$checkFields[] = 'PasswordConfirm';
			}

			foreach ($checkFields as $field) {
				if (!array_key_exists('cust' . $field, $_POST) || trim($_POST['cust' . $field]) == '') {
					$Error = stripslashes(GetLang('Customer' . $field . 'Required'));
					return false;
				}
			}

			if (!is_email_address($_POST['custEmail'])) {
				$Error = stripslashes(GetLang('CustomerEmailInvalue'));
				return false;
			}

			if ($_POST['custPhone'] !== '') {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				if (!$GLOBALS['ISC_CLASS_CUSTOMER']->ValidatePhoneNumber($_POST['custPhone'])) {
					$Error = stripslashes(GetLang('CustomerPhoneInvalid'));
					return false;
				}
			}

			if (!isId($CustomerId) && $_POST['custPassword'] !== $_POST['custPasswordConfirm']) {
				$Error = stripslashes(GetLang('CustomerPasswordConfirmError'));
				return false;
			}

			if ($_POST['custStoreCredit'] !== '' && !isPrice($_POST['custStoreCredit'])) {
				$Error = stripslashes(GetLang('CustomerStoreCreditError'));
				return false;
			}

			$query = "SELECT *
						FROM [|PREFIX|]customers
						WHERE custconemail='" . $GLOBALS['ISC_CLASS_DB']->Quote($_POST['custEmail']) . "'";

			if (isId($CustomerId)) {
				$query .= " AND customerid != " . (int)$CustomerId;
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				$Error = stripslashes(GetLang('CustomerEmailNotUnique'));
				return false;
			}

			return true;
		}

		/**
		 * Get the customer data
		 *
		 * Method will either get the customer POST data if $CustomerId is null, or the database data if $CustomerId is not null
		 *
		 * @access private
		 * @param int $CustomerId The optional customer ID. If supplied will return the customer POST data, else return data from the database
		 * @param bool $entitiesData TRUE to entities the data using isc_html_escape, FALSE to leave as is. Default is TRUE
		 * @return Void
		 */
		private function _GetCustomerData($CustomerId=null, $entitiesData=true)
		{
			$customer = null;

			if (!isId($CustomerId)) {
				// Get the data for this customer from the form. The arrays
				// index names will match the table field names exactly.

				if (!isset ($_POST['custStoreCredit'])) {
					$_POST['custStoreCredit'] = '';
				}

				$customer = array();
				$customer['customerid'] = 0;
				$customer['custconfirstname'] = $_POST['custFirstName'];
				$customer['custconlastname'] = $_POST['custLastName'];
				$customer['custconcompany'] = $_POST['custCompany'];
				$customer['custconemail'] = $_POST['custEmail'];
				$customer['custconphone'] = $_POST['custPhone'];
				$customer['custpassword'] = $_POST['custPassword'];
				$customer['custstorecredit'] = DefaultPriceFormat($_POST['custStoreCredit']);
				$customer['custgroupid'] = @$_POST['custGroupId'];
				/*$customer['custRFC'] = strtoupper($_POST['custRFC']);*/
			} else {
				// Get the data for this customer from the database
				$query = "SELECT *
							FROM [|PREFIX|]customers
							WHERE customerid='" . $GLOBALS['ISC_CLASS_DB']->Quote($CustomerId) . "'";

				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
				$customer = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);
			}

			if (is_array($customer) && $entitiesData) {
				$customer = array_map('isc_html_escape', $customer);
			}

			return $customer;
		}

		/**
		* _GetGroupList
		* Get a list of groups and return them as an array
		*
		* @param Int $NumGroups A reference variable to hold the number of groups
		* @return Array The list of groups
		*/
		private function _GetGroupList(&$NumGroups)
		{
			$groups = array();
			$query = "SELECT * FROM [|PREFIX|]customer_groups ORDER BY groupname ASC";
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$NumGroups = $GLOBALS["ISC_CLASS_DB"]->CountResult($result);

			while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$groups[$row['customergroupid']] = $row;
			}

			return $groups;
		}

		/**
		* _BuildGroupDropdown
		* Build the select box to show a customer's group on the View Customers page
		*
		* @param Int $CustomerId The ID of the customer from the customers table
		* @param Int $SelectedGroup The ID of the group that the customer belongs to
		* @param String CustomerName The customer's name for the flashmessage when the group is changed
		*
		* @return String The select box's HTML
		*/
		private function _BuildGroupDropdown($CustomerId, $SelectedGroup, $CustomerName)
		{
			$output = sprintf("<select onchange='updateCustomerGroup(%d, this.options[this.selectedIndex].value, \"%s\", this.options[this.selectedIndex].text)' name='customergroup_%d' id='customergroup_%d'>", $CustomerId, isc_html_escape($CustomerName), $CustomerId, $CustomerId);
			$output .= sprintf("<option value='0'>%s</option>", GetLang("SelectCustomerGroup"));

			$output .= $this->GetCustomerGroupsAsOptions($SelectedGroup);

			$output .= "</select>";
			return $output;
		}

		/**
		* GetCustomerGroupsAsOptions
		* Return a list of option tags containing name/values of customer groups
		*
		* @param Int $SelectedGroup The group to mark as selected in the option tags
		*
		* @return String The HTML <option> tags
		*/
		public function GetCustomerGroupsAsOptions($SelectedGroup=0)
		{
			$this->_customerGroups = &$this->_GetGroupList($numGroups);
			$options = "";

			foreach($this->_customerGroups as $group) {
				if ($SelectedGroup == $group['customergroupid']) {
					$sel = "selected='selected'";
				}
				else {
					$sel = "";
				}

				$options .= sprintf("<option value='%d' %s>%s</option>", (int) $group['customergroupid'], $sel, isc_html_escape($group['groupname']));
			}

			return $options;
		}

		/**
		* _CreateGroupLevelDiscounts
		* Create the group-level discounts for a new/updated group
		*
		* @param Int $GroupId The group to which the discounts belong
		* @return Boolean True if they were created, false on DB error
		*/
		private function _CreateGroupLevelDiscounts($groupId, $discounts, $removeFirst=false)
		{
			if (!isId($groupId) || !is_array($discounts)) {
				return false;
			}

			if ($removeFirst) {
				$status = $GLOBALS['ISC_CLASS_DB']->DeleteQuery("customer_group_discounts", "WHERE customergroupid=" . (int)$groupId);
				if ($status === false) {
					return false;
				}
			}

			foreach ($discounts as $discount) {
				$savedata = array(
					'customergroupid' => $groupId,
					'discounttype' => isc_strtoupper($discount['discounttype']),
					'catorprodid' => (int)$discount['catorprodid'],
					'discountpercent' => DefaultPriceFormat($discount['discountpercent']),
					'discountmethod' => $discount['discountmethod']
				);

				if (isc_strtolower($discount['discounttype']) == 'product') {
					$savedata['appliesto'] = 'NOT_APPLICABLE';
				} else {
					$savedata['appliesto'] = isc_strtoupper($discount['appliesto']);
				}

				if ($GLOBALS['ISC_CLASS_DB']->InsertQuery("customer_group_discounts", $savedata) === false) {
					return false;
				}
			}

			// Build the cache again
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCustomerGroupsCategoryDiscounts($groupId);

			return true;
		}

		/**
		* _GetAccessCatsForGroup
		* Get the list of categories to which this group has access
		*
		* @return String The CSV of category ID's which they can access
		*				 0 - No categories
		*				-1 - All categories
		*			 X,Y,Z - The CSV of category ID's they can access
		*/
		private function _GetAccessCatsForGroup()
		{
			$access_cats = array();
			if (isset($_POST['accesscategorieslist'])) {
				$access_cats = $_POST['accesscategorieslist'];

				foreach ($_POST['accesscategorieslist'] as $k => $v) {
					if (!is_numeric($v)) {
						unset($access_cats[$k]);
					}
				}
			}

			return $access_cats;
		}

		private function _GetAccessTypeForGroup()
		{
			if (isset($_POST['accesscategories'])) {
				return "all";
			}

			if (isset($_POST['accesscategorieslist'])) {
				return "specific";
			}

			return "none";
		}

		/**
		 * Generate the shipping address form
		 *
		 * Method will generate the shipping address form based upon the section $section
		 *
		 * @access private
		 * @param string $section the different section to generate ('address' or 'custom')
		 * @param bool $preservePost TRUE to alse preserver the post, FALSE not to. Default is FALSE
		 * @param int $shippingId The optional shipping address ID. Default is 0 (no database record)
		 */
		private function generateAddressFields($section, $preservePost=false, $shippingId=0)
		{
			$section = strtolower($section);

			if ($section !== 'address' && $section !== 'custom') {
				return '';
			}

			$formSessionId = 0;
			$address = false;

			if (isId($shippingId) && !$preservePost) {
				$address = $this->shippingEntity->get($shippingId);

				if (!$address) {
					return '';
				}

				if ($section == 'custom' && isId($address['shipformsessionid'])) {
					$formSessionId = $address['shipformsessionid'];
				}
			}

			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, $preservePost, $formSessionId);

			if ($section == 'address') {

				/**
				 * Are we reading from the database?
				 */
				if (isId($shippingId) && !$preservePost) {
					foreach (array_keys($fields) as $fieldId) {
						$privateId = $fields[$fieldId]->record['formfieldprivateid'];

						if ($privateId == '' | !array_key_exists($privateId, $this->shippingMap) || !array_key_exists($this->shippingMap[$privateId], $address)) {
							continue;
						}

						$fields[$fieldId]->setValue($address[$this->shippingMap[$privateId]]);
					}
				}

				/**
				 * We'll need to add in the country and state options plus the country event
				 */
				$countryId = $stateId = 0;
				$selectedCountry = '';

				foreach (array_keys($fields) as $fieldId) {
					if (strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'country') {
						$fields[$fieldId]->setOptions(array_values(GetCountryListAsIdValuePairs()));
						$countryId = $fieldId;
						$selectedCountry = $fields[$fieldId]->getValue();
					} else if (strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'state') {
						$stateId = $fieldId;
					}
				}

				/**
				 * Do we have a selected country?
				 */
				if ($selectedCountry == '' && GetConfig('CompanyCountry') !== '') {
					$selectedCountry = GetConfig('CompanyCountry');
					$fields[$countryId]->setValue($selectedCountry);
				}

				/**
				 * Add the event
				 */
				$fields[$countryId]->addEventHandler('change', 'FormFieldEvent.SingleSelectPopulateStates', array('countryId' => $countryId, 'stateId' => $stateId));

				/**
				 * Now add in the state options if we can
				 */
				if ($selectedCountry !== '') {
					$countryRecordId = GetCountryByName($selectedCountry);
					if (isId($countryRecordId)) {
						$stateOptions = GetStateListAsIdValuePairs($countryRecordId);
						if (is_array($stateOptions) && !empty($stateOptions)) {
							$fields[$stateId]->setOptions($stateOptions);
						}
					}
				}
			}

			/**
			 * OK, now generate them all
			 */
			$html = '';

			foreach (array_keys($fields) as $fieldId) {
				$privateId = $fields[$fieldId]->record['formfieldprivateid'];

				if ($section == 'address' && $privateId == '') {
					continue;
				}

				if ($section == 'custom' && $privateId !== '') {
					continue;
				}

				/**
				 * We don't want these fields
				 */
				if ($section == 'address' && (strtolower($privateId) == 'savethisaddress' || strtolower($privateId) == 'shiptoaddress')) {
					continue;
				}

				$html .= $fields[$fieldId]->loadForFrontend() . "\n";
			}

			return $html;
		}

		/**
		 * Save the submitted shipping address form data
		 *
		 * Method will map and save all the shipping address data
		 *
		 * @access private
		 * @param array $fields The form fields to save
		 * @param int $customerId The customerId
		 * @param int $shippingId The optional shipping ID. Default is 0 (new record)
		 * @return mixed The new shipping ID on successful new record, TRUE if record successfully
		 *               updated, FALSE on error
		 */
		private function saveAddressFields($fields, $customerId, $shippingId=0)
		{
			if (!is_array($fields) || empty($fields) || !isId($customerId)) {
				return false;
			}

			$savedata = array(
				'shipcustomerid' => $customerId
			);

			if (isId($shippingId)) {
				$savedata['shipid'] = $shippingId;
			}

			/**
			 * Map the private data
			 */
			$country = $state = '';

			foreach (array_keys($fields) as $fieldId) {
				$privateId = $fields[$fieldId]->record['formfieldprivateid'];

				if ($privateId == '' || !array_key_exists($privateId, $this->shippingMap)) {
					continue;
				}

				$savedata[$this->shippingMap[$privateId]] = $fields[$fieldId]->getValue();

				if (strtolower($privateId) == 'country') {
					$country = $fields[$fieldId]->getValue();
				} else if (strtolower($privateId) == 'state') {
					$state = $fields[$fieldId]->getValue();
				}
			}

			/**
			 * Find the country and state ID if we can
			 */
			$countryId = $stateId = 0;

			if ($country !== '') {
				$countryId = GetCountryByName($country);
			}

			if ($state !== '' && isId($countryId)) {
				$stateId = GetStateByName($state, $countryId);
			}

			$savedata['shipcountryid'] = (int)$countryId;
			$savedata['shipstateid'] = (int)$stateId;

			/**
			 * Save our custom (non private) fields if we are allowed
			 */
			if (gzte11(ISC_MEDIUMPRINT)) {

				/**
				 * Do we already have a form session ID for this address?
				 */
				$formSessionId = 0;
				if (isId($shippingId)) {
					$address = $this->shippingEntity->get($shippingId);
					if (is_array($address) && isset($address['shipformsessionid']) && isId($address['shipformsessionid'])) {
						$formSessionId = $address['shipformsessionid'];
					}
				}

				if (isId($formSessionId)) {
					$GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ADDRESS, true, $formSessionId);
				} else {
					$formSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ADDRESS);
					if (isId($formSessionId)) {
						$savedata['shipformsessionid'] = $formSessionId;
					}
				}
			}

			if (isId($shippingId)) {
				return $this->shippingEntity->edit($savedata);
			} else {
				return $this->shippingEntity->add($savedata);
			}
		}

		/**
		 * Validate the submitted shipping address form data
		 *
		 * Method will validate all the shipping address data
		 *
		 * @access private
		 * @param array $fields The form fields to save
		 * @param string &$errmsg The referenced string to store the error message in
		 * @return bool TRUE if the validation was successful, FALSE if not
		 */
		private function vaidateAddressFields($fields, &$errmsg)
		{
			if (!is_array($fields)) {
				return false;
			}

			$phoneNo = '';
			$validateCustomFields = true;

			if (!gzte11(ISC_MEDIUMPRINT)) {
				$validateCustomFields = false;
			}

			foreach (array_keys($fields) as $fieldId) {

				/**
				 * Only validate the customer fields (non private) if we are allowed to
				 */
				if ($fields[$fieldId]->record['formfieldprivateid'] == '' && !$validateCustomFields) {
					continue;
				}

				if (!$fields[$fieldId]->runValidation($errmsg)) {
					return false;
				}

				if (strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'phone') {
					$phoneNo = $fields[$fieldId]->getValue();
				}
			}

			if ($phoneNo !== '') {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				if (!$GLOBALS['ISC_CLASS_CUSTOMER']->ValidatePhoneNumber($phoneNo)) {
					$errmsg = stripslashes(GetLang('CustomerAddressPhoneInvalid'));
					return false;
				}
			}

			return true;
		}
	}
