<?php

	class ISC_ADMIN_QUICKSEARCH extends ISC_ADMIN_BASE
	{
		private $query = "";

		public function __construct()
		{
			parent::__construct();
			if(isset($_REQUEST['query'])) {
				$this->query = $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['query']);
			}
			else {
				header("Location:index.php");
			}
		}

		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
			$this->QuickSearch();
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
		}

		/**
		*	Perform a search for all orders, products and customers that match the specified
		*	search query and show them in a list with a link to view all results for each
		*/
		private function QuickSearch()
		{
			require_once(dirname(__FILE__) . "/class.orders.php");
			require_once(dirname(__FILE__) . "/class.customers.php");
			require_once(dirname(__FILE__) . "/class.product.php");

			$orders = GetClass('ISC_ADMIN_ORDERS');
			$customers = GetClass('ISC_ADMIN_CUSTOMERS');
			$products = GetClass('ISC_ADMIN_PRODUCT');

			$num_orders = $num_customers = $num_products = 0;

			// Get the number of orders
			$_REQUEST['searchQuery'] = $this->query;
			$orders->_GetOrderList(0, "orderid", "asc", $num_orders, ISC_ORDERS_PER_PAGE, $numDeletedOrders);

			// Get the number of customers
			$_REQUEST['searchQuery'] = $this->query;
			$customers->_GetCustomerList(0, "customerid", "asc", $num_customers);

			// Get the number of products
			$_REQUEST['searchQuery'] = $this->query;
			$products->_GetProductList(0, "productid", "asc", $num_products);

			$num_results = $num_orders + $numDeletedOrders + $num_customers + $num_products;

			if($num_results == 1) {
				$msg = GetLang('QuickSearchResults1');
			}
			else {
				$msg = sprintf(GetLang('QuickSearchResultsX'), $num_results);
			}

			$this->query = isc_html_escape($this->query);

			if($num_orders == 1) {
				$GLOBALS['OrdersLink'] = sprintf(GetLang('QuickSearchOrders1'), $this->query);
			}
			else {
				$GLOBALS['OrdersLink'] = sprintf(GetLang('QuickSearchOrdersX'), $num_orders, $this->query);
			}

			$this->template->assign('numDeletedOrders', $numDeletedOrders);

			if($num_customers == 1) {
				$GLOBALS['CustomersLink'] = sprintf(GetLang('QuickSearchCustomers1'), $this->query);
			}
			else {
				$GLOBALS['CustomersLink'] = sprintf(GetLang('QuickSearchCustomersX'), $num_customers, $this->query);
			}

			if($num_products == 1) {
				$GLOBALS['ProductsLink'] = sprintf(GetLang('QuickSearchProducts1'), $this->query);
			}
			else {
				$GLOBALS['ProductsLink'] = sprintf(GetLang('QuickSearchProductsX'), $num_products, $this->query);
			}

			$GLOBALS['Message'] = MessageBox($msg, MSG_INFO);
			$GLOBALS['searchQuery'] = urlencode($this->query);

			$this->template->display('quicksearch.tpl');
		}
	}
