<?php
class ISC_ADMIN_INDEX extends ISC_ADMIN_BASE
{
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->engine->LoadLangFile('index');
	}

	/**
	 * Handle the incoming action (by default shows the dashboard)
	 */
	public function HandleToDo()
	{
		// iPhone cannot access the store control panel home page
		if(defined('IS_PHONE')) {
			redirect('index.php?ToDo=viewOrders');
		}

		$this->ShowDashboard();
	}

	/**
	 * Show the dashboard page.
	 */
	public function ShowDashboard()
	{
		// Check if there are one or more checkout methods enabled that aren't setup
		$enabledCheckoutMethods = preg_split('/[,\s]+/s', GetConfig('CheckoutMethods'), -1, PREG_SPLIT_NO_EMPTY);
		$numSetupMethods = 0;

		if (!empty($enabledCheckoutMethods)) {
			$query = "
				SELECT count(*)
				FROM [|PREFIX|]module_vars
				WHERE modulename IN ('".implode("','", $GLOBALS['ISC_CLASS_DB']->Quote($enabledCheckoutMethods))."')
				AND variablename='is_setup'
				AND variableval='1'
			";

			$numSetupMethods = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		}

		if (!empty($enabledCheckoutMethods) && $numSetupMethods == 0) {
			FlashMessage(GetLang('CheckoutNotSetup'), MSG_ERROR);
		}

		// Are there any messages to be shown on the home page?
		$this->template->Assign('Messages', GetFlashMessageBoxes());

		// Fetch the store statistics
		$overviewStatistics = $this->GenerateStoreOverview();
		$statsList = '';
		$i = 0;
		$statsCount = count($overviewStatistics);
		foreach($overviewStatistics as $statistic) {
			++$i;
			$this->template->Assign('Count', $statistic['count']);
			$this->template->Assign('Label', $statistic['label']);
			$this->template->Assign('Link', $statistic['link']);
			if($statsCount == $i) {
				$this->template->Assign('Class', 'Last');
			}
			$statsList .= $this->template->render('Snippets/DashboardAtGlanceItem.html');
		}

		$this->template->assign('AtGlanceItems', $statsList);

		if(empty($statsList)) {
			$this->template->Assign('HideAtAGlance', 'display: none');
		}

		// Hide popular help articles if they're disabled
		if(!GetConfig('LoadPopularHelpArticles')) {
			$this->template->Assign('HidePopularHelpArticles', 'display: none');
		}
		else {
			$this->template->Assign('ViewKnowledgeBaseLink', GetConfig('ViewKnowledgeBaseLink'));
			if(GetConfig('SearchKnowledgeBaseUrl') == '') {
				$this->template->Assign('HideSearchKnowledgeBase', 'display: none');
			}
			else {
				$this->template->Assign('SearchKnowledgeBaseUrl', GetConfig('SearchKnowledgeBaseUrl'));
			}
		}

		// Load up current notifications and assign them if supported
		$currentNotifications = $this->GetCurrentNotifications();
		if(empty($currentNotifications)) {
			$this->template->Assign('HideNotificationsList', 'display: none');
		}
		else {
			$this->template->Assign('NotificationsList', $currentNotifications);
		}

		$gettingStarted = $this->GenerateGettingStarted();

		// If getting started is disabled or isn't supported, hide the toggle links
		if($gettingStarted === false) {
			$this->template->Assign('HideToggleGettingStartedAtGlance', 'display: none');
		}
		else {
			$this->template->Assign('GettingStarted', $gettingStarted['steps']);
		}

		// Getting started shouldn't be enabled, or is completed. Show the at a glance by default
		if($gettingStarted === false || $gettingStarted['hasIncomplete'] == false) {
			$this->template->Assign('HideGettingStarted', 'display: none');
		}
		else {
			$this->template->Assign('HideOverview', 'display: none');
		}

		// Have we toggled to a specific tab?
		if($gettingStarted !== false && isset($_COOKIE['DashboardMode'])) {
			switch($_COOKIE['DashboardMode']) {
				case 'gettingstarted':
					$this->template->Assign('HideGettingStarted', '');
					$this->template->Assign('HideOverview', 'display: none');
					break;
				default:
					$this->template->Assign('HideGettingStarted', 'display: none');
					$this->template->Assign('HideOverview', '');
			}
		}

		// Is the "Learn more about using?" disabled? If so, hide it
		if(GetConfig('HideLearnMoreAboutUsing')) {
			$GLOBALS['DisableLearnMoreAboutUsing'] = 'display: none';
		}
		else {
			$this->template->Assign('LearnMoreAboutUsing1Url', GetConfig('LearnMoreAboutUsing1Url'));
			$this->template->Assign('LearnMoreAboutUsing1Class', GetConfig('LearnMoreAboutUsing1Class'));
			$this->template->Assign('LearnMoreAboutUsing1Title', GetConfig('LearnMoreAboutUsing1Title'));

			$this->template->Assign('LearnMoreAboutUsing2Url', GetConfig('LearnMoreAboutUsing2Url'));
			$this->template->Assign('LearnMoreAboutUsing2Class', GetConfig('LearnMoreAboutUsing2Class'));
			$this->template->Assign('LearnMoreAboutUsing2Title', GetConfig('LearnMoreAboutUsing2Title'));
		}


		// Load in the list of recent orders and set up the status indicators for the selected value
		$recentOrders = $this->LoadRecentOrders();
		if($recentOrders !== false) {
			if(isset($_COOKIE['DashboardRecentOrdersStatus'])) {
				$selectedItem = ucfirst($_COOKIE['DashboardRecentOrdersStatus']);
			}
			else {
				$selectedItem = 'Recent';
			}
			$this->template->Assign('RecentOrdersActive'.$selectedItem.'Class', 'Active');
			$this->template->Assign('RecentOrdersList', $recentOrders);
		}

		if($recentOrders == false) {
			$this->template->Assign('HideRecentOrders', 'display: none');
		}

		// Calculate the performance indicator statistics
		$performanceIndicators = $this->GeneratePerformanceIndicatorsTable();
		if($performanceIndicators) {
			if(isset($_COOKIE['DashboardPerformanceIndicatorsPeriod'])) {
				$selectedItem = ucfirst($_COOKIE['DashboardPerformanceIndicatorsPeriod']);
			}
			else {
				$selectedItem = 'Week';
			}
			$this->template->Assign('PerformanceIndicatorsActive'.$selectedItem, 'Active');
			$this->template->Assign('PerformanceIndicatorsTable', $performanceIndicators);
		}
		else {
			$this->template->Assign('HidePerformanceIndicators', 'display: none');
		}

		// Generate the breakdown graph for orders and assign it, if supported.
		$orderGraph = $this->GenerateOrderBreakdownGraph();
		if($orderGraph) {
			$this->template->Assign('DashboardBreakdownGraph', $orderGraph);
		}
		else {
			$this->template->Assign('HideDashboardBreakdownGraph', 'display: none');
		}

		$versionCheckSetup = $this->SetupVersionCheck();
		$this->template->Assign('VersionCheckMessage', $versionCheckSetup);

		// Do we have permission to manage orders?
		if(!$this->auth->HasPermission(AUTH_Manage_Orders)) {
			$this->template->Assign('HideManageOrdersLink', 'display: none');
		}

		// Do we have permission to create products?
		if(!$this->auth->HasPermission(AUTH_Create_Product)) {
			$this->template->Assign('HideAddProductLink', 'display: none');
		}

		// Do we have permission to view the performance indicators?
		if(!$this->auth->HasPermission(AUTH_Statistics_Orders)) {
			$this->template->Assign('HideDashboardPerformanceIndcators', 'display: none');
		}

		// Are they running an expiring trial?
		$l = spr1ntf(GetConfig(B('c2VydmVyU3RhbXA=')));
		if($l['expires'] != '') {
			$d = preg_match('#^(\d{4})(\d\d)(\d\d)$#', $l['expires'], $matches);
			$s = mktime(23, 59, 59, $matches[2], $matches[3], $matches[1]);
			$n = isc_mktime();
			$day = floor(($s - $n) / 86400);
			if ($day == 0) {
				$day = 1;
			}
			if ($day > 0) {
				$this->template->Assign('TrialExpiryDetails', sprintf(GetLang('TrialExpiresInXDays'), $day));
				$this->template->Assign('TrialExpiryMessage', $this->template->render('Snippets/DashboardTrialExpiryMessage.html'));
			}
		}

		$this->engine->stylesheets[] = 'Styles/dashboard.css';
		$this->engine->bodyScripts[] = 'script/dashboard.js';

		$this->engine->PrintHeader();

		// Do we need to re-generate the cache for this page?
		if (cache_time("class.engine.php") > 0) {
			regenerate_cache("class.engine.php");
		}
		$this->template->display('home.tpl');
		$this->engine->PrintFooter();
	}

	/**
	 * Setup all of the functionality to perform version checking and generate
	 * the "New Version" message if one should be shown.
	 *
	 * @return string The version check message, if one should be shown.
	 */
	private function SetupVersionCheck()
	{
		$GLOBALS['CheckVersion'] = false;
		$latestVersion = false;

		$versionCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('LatestVersion');
		// No version is currently cached, we need to check
		if($versionCache === false) {
			$GLOBALS['CheckVersion'] = true;
		}
		// Version was last checked > 24 hours ago, check again
		else if($versionCache['lastCheck'] < time()-86400) {
			$GLOBALS['CheckVersion'] = true;
		}

		if(GetConfig('DisableVersionCheck')) {
			return false;
		}

		if(isset($versionCache['latest'])) {
			$latestVersion = $versionCache['latest'];
		}

		$this->template->Assign('CurrentVersionNumber', PRODUCT_VERSION);

		// Have we specifically hidden the version check for this version? If so, hide the box
		if(isset($_COOKIE['HideVersionCheck']) && $_COOKIE['HideVersionCheck'] == $latestVersion) {
			$this->template->Assign('HideDashboardVersionCheck', 'display: none');
		}
		else if(version_compare($latestVersion, PRODUCT_VERSION) != 1) {
			$this->template->Assign('HideDashboardVersionCheck', 'display: none');
		}

		$this->template->Assign('VersionCheckMessage', sprintf(GetLang('NewVersionAvailable'), $latestVersion));
		return $this->template->render('Snippets/DashboardVersionCheck.html');
	}

	/**
	 * Generate the HTML and other information if we need to show the getting started steps.
	 *
	 * @return boolean|array Returns false if the getting started steps aren't supported and shouldn't be available. Returns an array otherwise, of the steps list HTML and boolean if the steps should be shown by default.
	 */
	public function GenerateGettingStarted()
	{
		$user = $this->auth->GetUser();
		if($this->auth->GetVendorId() || GetConfig('DisableGettingStarted') || $user['username'] != 'admin' ||  $user['pk_userid'] != 1) {
			return false;
		}

		$availableSteps = array(
			'settings',
			'design',
			'products',
			'paymentMethods',
			'taxSettings',
			'shippingOptions'
		);

		$completedSteps = GetConfig('GettingStartedCompleted');
		$incompleteSteps = array_diff($availableSteps, $completedSteps);

		foreach($incompleteSteps as $step) {
			$templateVar = 'HideCompletedStep'.ucfirst($step);
			$this->template->Assign($templateVar, 'display: none');
		}

		foreach($completedSteps as $step) {
			$templateVar = 'CompletedStep'.ucfirst($step).'Class';
			$this->template->Assign($templateVar, 'StepComplete');
		}

		// Get the getting started box if we need to
		$GLOBALS['GettingStartedStep'] = '';
		if(empty($incompleteSteps) && !in_array('storeComplete', GetConfig('GettingStartedCompleted')) && !GetConfig('DisableGettingStarted')) {
			$GLOBALS['GettingStartedTitle'] = GetLang('WizardSetupComplete');
			$GLOBALS['GettingStartedContent'] = GetLang('WizardSetupCompleteDesc');
			$GLOBALS['HideGettingStartedCancel'] = 'display: none';
			$GLOBALS['GettingStartedStep'] = $this->template->render('Snippets/GettingStartedModal.html');
			GetClass('ISC_ADMIN_ENGINE')->MarkGettingStartedComplete('storeComplete');
		}

		if(GetConfig('DisableVideoWalkthrough')) {
			$this->template->Assign('HideVideoWalkthrough', 'display: none');
		}

		return array(
			'steps' => $this->template->render('Snippets/DashboardGettingStarted.html'),
			'hasIncomplete' => !empty($incompleteSteps)
		);
	}

	/**
	 * Collect various overview statistics to be shown on the control panel dashboard.
	 *
	 * @return array An array of the statistics to be shown.
	 */
	public function GenerateStoreOverview()
	{
		static $statistics = array();

		if(!empty($statistics)) {
			return $statistics;
		}

		// Orders
		if($this->auth->HasPermission(AUTH_Manage_Orders)) {
			$query = "
				SELECT COUNT(*)
				FROM [|PREFIX|]orders
				WHERE ordstatus > 0 AND deleted = 0
			";
			if($this->auth->GetVendorId()) {
				$query .= " AND ordvendorid='".$this->auth->GetVendorId()."'";
			}
			$count = $this->db->FetchOne($query);
			if($count == 1) {
				$label = GetLang('Order');
			}
			else {
				$label = GetLang('Orders');
			}
			$statistics['orders'] = array(
				'count' => number_format($count),
				'label' => $label,
				'link' => 'index.php?ToDo=viewOrders',
			);
		}

		// How many products do we have in the store?
		if($this->auth->HasPermission(AUTH_Manage_Products)) {
			$query = "
				SELECT COUNT(*)
				FROM [|PREFIX|]products
			";
			if($this->auth->GetVendorId()) {
				$query .= " WHERE prodvendorid='".$this->auth->GetVendorId()."'";
			}
			$count = $this->db->FetchOne($query);
			if($count == 1) {
				$label = GetLang('Product');
			}
			else {
				$label = GetLang('Products');
			}
			$statistics['products'] = array(
				'count' => number_format($count),
				'label' => $label,
				'link' => 'index.php?ToDo=viewProducts',
			);
		}

		if($this->auth->HasPermission(AUTH_Manage_Categories)) {
			// How many categories?
			$query = "
				SELECT COUNT(*)
				FROM [|PREFIX|]categories
			";
			$count = $this->db->FetchOne($query);
			if($count == 1) {
				$label = GetLang('Category');
			}
			else {
				$label = GetLang('Categories');
			}
			$statistics['categories'] = array(
				'count' => number_format($count),
				'label' => $label,
				'link' => 'index.php?ToDo=viewCategories',
			);
		}

		// Customers
		if($this->auth->HasPermission(AUTH_Manage_Customers)) {
			$query = "
				SELECT COUNT(*)
				FROM [|PREFIX|]customers
			";
			$customerCount = $this->db->FetchOne($query);
			$count = $this->db->FetchOne($query);
			if($count == 1) {
				$label = GetLang('Customer');
			}
			else {
				$label = GetLang('Customers');
			}
			$statistics['customers'] = array(
				'count' => number_format($count),
				'label' => $label,
				'link' => 'index.php?ToDo=viewCustomers',
			);
		}

		return $statistics;
	}

	/**
	 * Load and echo a list of the popular help articles, as defined by the
	 * URL in the white label file. Results are cached for a day.
	 */
	public function LoadPopularHelpArticles()
	{
		if(!GetConfig('LoadPopularHelpArticles')) {
			exit;
		}

		$GLOBALS['ISC_CLASS_PAGE'] = GetClass('ISC_PAGE');
		$contents = $GLOBALS['ISC_CLASS_PAGE']->_LoadFeed(GetConfig('HelpRSS'), 10, 86400, "admin-help.xml","PageRSSItemHelp", true);

		if ($contents === false) {
			echo GetLang('ErrorLoadingFeed');
			return;
		}

		echo "<ul>";
		echo $contents;
		echo "</ul>";
	}

	/**
	 * Load and return a listing of the recent orders placed on this store.
	 * Orders are loaded for a specific status if one is passed in via the GET
	 * or via a cookie.
	 *
	 * @return string The recent list of orders HTML.
	 */
	public function LoadRecentOrders()
	{
		// Do we have permission to view this widget?
		if(!$this->auth->HasPermission(AUTH_Manage_Orders)) {
			return false;
		}

		// If we don't have a status coming in via the URL, use the default
		if(!isset($_GET['status'])) {
			// Maybe it's set in a cookie? Use that
			if(isset($_COOKIE['DashboardRecentOrdersStatus'])) {
				$status = $_COOKIE['DashboardRecentOrdersStatus'];
			}
			else {
				$status = 'recent';
			}
		}
		else {
			$status = $_GET['status'];
		}

		$orderWhere = '1=1';
		$statusIn = array();
		// Determine which statuses we'll be showing orders for. Will be used in the query.
		switch($status)
		{
			case 'pending':
				$statusIn = array(
					ORDER_STATUS_PENDING,
					ORDER_STATUS_PARTIALLY_SHIPPED,
					ORDER_STATUS_AWAITING_PAYMENT,
					ORDER_STATUS_AWAITING_SHIPMENT,
					ORDER_STATUS_AWAITING_FULFILLMENT,
					ORDER_STATUS_AWAITING_PICKUP,
				);
				break;
			case 'completed':
				$statusIn = array(
					ORDER_STATUS_SHIPPED,
					ORDER_STATUS_COMPLETED
				);
				break;
			case 'refunded':
				$statusIn = array(
					ORDER_STATUS_REFUNDED,
					ORDER_STATUS_CANCELLED
				);
				break;
			default:
				$status = 'recent';
		}

		// If they've just changed statuses, store it in a cookie
		if(isset($_GET['status'])) {
			isc_setcookie('DashboardRecentOrdersStatus', $status);
		}

		if(!empty($statusIn)) {
			$orderWhere .= " AND ordstatus IN (".implode(',', $statusIn).")";
		}

		// Only get orders for this vendor
		if($this->auth->GetVendorId()) {
			$orderWhere .= " AND ordvendorid='".$this->auth->GetVendorId()."'";
		}

		// Fetch orders
		$query = "
			SELECT orderid, ordbillfirstname, ordbilllastname, ordstatus, orddate, total_inc_tax
			FROM [|PREFIX|]orders
			WHERE ".$orderWhere." AND ordstatus != 0 AND deleted = 0
			ORDER BY orddate DESC
		";
		$query .= $this->db->AddLimit(0, 10);
		$result = $this->db->Query($query);
		$orderList = '';
		while($order = $this->db->Fetch($result)) {
			$this->template->Assign('OrderId', $order['orderid']);
			$this->template->Assign('OrderStatusId', $order['ordstatus']);
			$this->template->Assign('OrderStatus', GetOrderStatusById($order['ordstatus']));
			$customerName = $order['ordbillfirstname'].' '.$order['ordbilllastname'];
			if(!trim($customerName)) {
				$customerName = GetLang('Guest');
			}
			$this->template->Assign('CustomerName', isc_html_escape($customerName));
			$orderSummary = sprintf(GetLang('RecentOrdersDateAndTotal'), Store_DateTime::niceDate($order['orddate'], true), FormatPrice($order['total_inc_tax']));
			$this->template->Assign('OrderSummary', $orderSummary);
			$orderList .= $this->template->render('Snippets/DashboardRecentOrdersItem.html');
		}

		if(!$orderList) {
			$orderList = $this->template->render('Snippets/DashboardRecentOrdersNone.html');
		}

		return $orderList;
	}

	/**
	 * Generate the HTML necessary to show a list of pending actions/notifications
	 * on the dashboard. Things like pending reviews, orders, low inventory etc.
	 *
	 * @return string The generated HTML/list containing the notifications. Empty otherwise.
	 */
	private function GetCurrentNotifications()
	{
		// Vendors don't see store notifications in the control panel
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() > 0) {
			return '';
		}

		$notifications = array();
		$numLowInventory = 0;
		$numLowInventoryVariation = 0;

		// Get the number of products which have reached their inventory warning levels
		$query = "
			SELECT COUNT(productid)
			FROM [|PREFIX|]products
			WHERE prodcurrentinv<=prodlowinv AND prodlowinv > 0 AND prodinvtrack=1
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$numLowInventory = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		$query = "
			SELECT COUNT(DISTINCT pv.vcproductid)
			FROM [|PREFIX|]product_variation_combinations pv
			LEFT JOIN [|PREFIX|]products p ON (p.productid = pv.vcproductid)
			WHERE pv.vcstock<=pv.vclowstock AND pv.vclowstock > 0 AND p.prodinvtrack=2
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$numLowInventoryVariation = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		if ($numLowInventoryVariation > 0) {
			$numLowInventory += $numLowInventoryVariation;
		}

		if ($numLowInventory>0) {
			$langString = 'StoreNotificationLowInventory';
			if ($numLowInventory > 1) {
				$langString .= 'Multiple';
			}
			$notifications[] = sprintf(GetLang($langString), $numLowInventory);
		}

		// Select the number of pending orders
		$query = "
			SELECT COUNT(orderid)
			FROM [|PREFIX|]orders
			WHERE ordstatus=1 AND deleted=0
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$numPendingOrders = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		if ($numPendingOrders > 0) {
			$langString = 'StoreNotificationPendingOrder';
			if ($numPendingOrders > 1) {
				$langString .= 'Multiple';
			}

			$notifications[] = sprintf(GetLang($langString), $numPendingOrders);
		}

		// Select the number of returns requests that are pending
		if (GetConfig('EnableReturns') == 1) {
			$query = "
				SELECT COUNT(returnid)
				FROM [|PREFIX|]returns
				WHERE retstatus=1
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$numReturnRequests = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
			if ($numReturnRequests > 0) {
				$langString = 'StoreNotificationReturnRequest';
				if ($numReturnRequests > 1) {
					$langString .= 'Multiple';
				}

				$notifications[] = sprintf(GetLang($langString), $numReturnRequests);
			}
		}

		// Select the number of new messages
		$query = "
			SELECT COUNT(messageid)
			FROM [|PREFIX|]order_messages
			WHERE messagefrom='customer' AND messagestatus='unread'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$numNewMessages = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		if ($numNewMessages > 0) {
			$langString = 'StoreNotificationOrderMessage';
			if ($numNewMessages > 1) {
				$langString .= 'Multiple';
			}

			$notifications[] = sprintf(GetLang($langString), $numNewMessages);
		}

		// Select the number pending reviews
		$query = "
			SELECT COUNT(reviewid)
			FROM [|PREFIX|]reviews
			WHERE revstatus=0
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$numPendingReviews = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		if ($numPendingReviews > 0) {
			$langString = 'StoreNotificationPendingReview';
			if ($numPendingReviews > 1) {
				$langString .= 'Multiple';
			}

			$notifications[] = sprintf(GetLang($langString), $numPendingReviews);
		}

		// Get the number of un-shipped pre-orders
		$query = "
			SELECT
				COUNT(*)
			FROM
				[|PREFIX|]orders o,
				[|PREFIX|]order_products op,
				[|PREFIX|]products p
			WHERE
				o.ordstatus IN (" . ORDER_STATUS_PENDING . "," . ORDER_STATUS_PARTIALLY_SHIPPED . "," . ORDER_STATUS_AWAITING_PAYMENT . "," . ORDER_STATUS_AWAITING_PICKUP . "," . ORDER_STATUS_AWAITING_SHIPMENT . "," . ORDER_STATUS_AWAITING_FULFILLMENT . ")
				AND o.deleted = 0
				AND op.orderorderid = o.orderid
				AND p.productid = op.ordprodid
				AND p.prodpreorder = 1";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$numPreOrders = (int)$GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		if ($numPreOrders === 1) {
			$notifications[] = GetLang('StoreNotificationPendingPreOrder', array('pending' => $numPreOrders));
		} else if ($numPreOrders) {
			$notifications[] = GetLang('StoreNotificationPendingPreOrderMultiple', array('pending' => $numPreOrders));
		}

		$notifications += $this->getEmailIntegrationNotifications();

		$notifications += $this->getEbayListingNotifications();

		// get list of inactive users
		if ($this->auth->HasPermission(AUTH_Manage_Users)) {
			$userManager = GetClass('ISC_ADMIN_USER');
			$inactiveUsers = $userManager->getInactiveUsers();
			$count = count($inactiveUsers);
			if ($count == 1) {
				$notifications[] = GetLang('StoreNotificationInactiveUser', array('count' => $count));
			} else if ($count) {
				$notifications[] = GetLang('StoreNotificationInactiveUserMultiple', array('count' => $count));
			}
		}

		if (!empty($notifications)) {
			return "<li>".implode("</li>\n<li>", $notifications)."</li>";
		}
	}

	protected function getEmailIntegrationNotifications()
	{
		$notifications = array();

		// Show information about email integration tasks in progress
		$keystore = Interspire_KeyStore::instance();
		$exports = $keystore->multiGet('email:module_export:id:*');
		foreach ($exports as $exportId) {
			$prefix = 'email:module_export:' . $exportId . ':';
			$type = $keystore->get($prefix . 'type');

			$module = $keystore->get($prefix . 'module');
			GetModuleById('emailintegration', $module, $module);
			if ($module) {
				$module = $module->GetName();
			}

			$skip = (int)$keystore->get($prefix . 'skip');
			$abort = (bool)$keystore->get($prefix . 'abort');
			$error = (int)$keystore->get($prefix . 'error_count');
			$total = (int)$keystore->get($prefix . 'estimate');
			$eta = '';

			if ($total) {
				if ($skip) {
					$per = (time() - (int)$keystore->get($prefix . 'started')) / $skip;
					$remaining = Store_DateTime::duration(($total - $skip) * $per, Store_DateTime::DURATION_MINUTES);
					if ($remaining) {
						$eta = '<br />' . GetLang('EmailIntegration_Notifications_InProgress_ETA', array(
							'remaining' => $remaining,
						));
					}
				}
				$total = GetLang('of') . ' ' . number_format($total, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
			} else {
				$total = '';
			}

			$skip = number_format($skip, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
			$error = number_format($error, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));

			$notice = '';

			if ($abort) {
				$notice = GetLang('EmailIntegration_Notifications_Abort', array(
					'type' => $type,
					'module' => $module,
				));
			}
			else
			{
				$notice = GetLang('EmailIntegration_Notifications_InProgress', array(
					'type' => $type,
					'module' => $module,
					'skip' => $skip,
					'total' => $total,
					'error' => $error,
					'eta' => $eta,
				)) . '<br /><a href="#" class="EmailIntegration_Export_Abort" id="EmailIntegration_Export_Abort_' . isc_html_escape($exportId) . '" title="' . isc_html_escape(GetLang('EmailIntegration_Notifications_InProgress_Abort_Title')) . '">' . GetLang('EmailIntegration_Notifications_InProgress_Abort_Label') . '</a>';
			}

			if ($notice) {
				$notifications[] = $notice;
			}
		}

		return $notifications;
	}

	/**
	 * Generate the graph showing orders over the past 7 days.
	 * If not supported or there are no values to be shown, an false is
	 * returned.
	 *
	 * @return string|boolean False if there are no results, otherwise the HTML for the graph.
	 */
	public function GenerateOrderBreakdownGraph()
	{
		if(!$this->auth->HasPermission(AUTH_Statistics_Overview)) {
			return false;
		}

		$graphFrom = isc_gmmktime(0, 0, 0, isc_date('m'), isc_date('d')-6, isc_date('y'));
		$maximumValue = 0;
		$graphValues = array();

		// Calculate the number of seconds from GMT +0 that we are in. We'll be adjusting
		// the orddate in the query below so that it becomes timezone specific (remember, MySQL thinks we're +0)
		$timezoneAdjustment = GetConfig('StoreTimeZone');
		if(GetConfig('StoreDSTCorrection')) {
			++$timezoneAdjustment;
		}
		$timezoneAdjustment *= 3600;

		$vendorAdd = '';
		if($this->auth->GetVendorId()) {
			$vendorAdd .= " AND ordvendorid='".$this->auth->GetVendorId()."'";
		}

		// Select orders, group them by the date so we can retrieve them later easily
		$query = "
			SELECT
				DATE_FORMAT(FROM_UNIXTIME(orddate+".$timezoneAdjustment."), '%Y-%m-%d') AS formatteddate,
				SUM(total_inc_tax) AS totalrevenue, COUNT(*) AS numorders, orddate
			FROM [|PREFIX|]orders
			WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".$graphFrom."' ".$vendorAdd."
			GROUP BY formatteddate
		";
		$result = $this->db->Query($query);
		while($day = $this->db->Fetch($result)) {
			$maximumValue = max($maximumValue, $day['totalrevenue']);
			$graphValues[$day['formatteddate']] = $day;
		}

		// If there are no orders, set the graph max to $50. Shows $0 -> $80
		if(!$maximumValue) {
			$maximumValue = 50;
		}

		// Calculate the magnitude (log value) of the max value, and the interval for the graph
		$magnitude = floor(log10($maximumValue));
		$interval = pow(10, ($magnitude + 1));
		$numIntervals = 4; // We want to show 4 series intervals for this graph
		$graphYMax = $this->CalculateGraphYMax($maximumValue, $interval);

		// Intentional loop. Broken below.
		while(true) {
			// Check if the mantissa is either 5,2 or 1 and convert between them.
			// This allows us to have a true scaling chart.
			$mantissa = substr($interval, 0, 1);
			if($mantissa == 5) {
				$nextInterval = $interval * (2/5);
			}
			else if($mantissa == 2 ) {
				$nextInterval = $interval*(1/2);
			}
			else if($mantissa == 1) {
				$nextInterval = $interval * (5/10);
			}
			else {
				break;
			}

			// Attempt to calculate the max Y axis again
			$calculatedYMax = $this->CalculateGraphYMax($maximumValue, $nextInterval);

			// If we've just calculated more intervals than necessary, break.
			if($calculatedYMax/$nextInterval > $numIntervals) {
				break;
			}

			$interval = $nextInterval;
			$graphYMax = $calculatedYMax;
		}

		// Less than we need? Fudge the numbers.
		if($graphYMax/$interval < $numIntervals) {
			$graphYMax = $interval*$numIntervals;
		}

		$now = time();
		$todaysDate = isc_date('Y-m-d', $now);
		$yesterdaysDate = isc_date('Y-m-d', $now-86400);

		// Start building the graph rows
		$graphRows = '';
		for($i=$graphFrom; $i <= time(); $i += 86400) {
			$statsDate = isc_date('Y-m-d', $i);
			if(!isset($graphValues[$statsDate])) {
				$numOrders = 0;
				$revenue = 0;
			}
			else {
				$numOrders = $graphValues[$statsDate]['numorders'];
				$revenue = $graphValues[$statsDate]['totalrevenue'];
			}

			if($statsDate == $todaysDate) {
				$label =  GetLang('Today');
			}
			else if($statsDate == $yesterdaysDate) {
				$label = GetLang('Yesterday');
			}
			else {
				$label = isc_date('l', $i);
			}

			$this->template->Assign('GraphDateTitle', $label);
			if($numOrders > 0) {
				$dateline = isc_date('m/d/Y', $i);
				$label = '<a href="index.php?ToDo=viewOrders&amp;fromDate='.$dateline.'&amp;toDate='.$dateline.'">'.$label.'</a>';
			}

			$this->template->Assign('GraphDateLabel', $label);

			$width = floor(($revenue/$graphYMax)*100);
			$this->template->Assign('GraphItemWidth', $width);
			if($width == 0) {
				$this->template->Assign('GraphRangeLabelClass', 'RangeEmpty');
			}
			else {
				$this->template->Assign('GraphRangeLabelClass', '');
			}
			if($width < 50) {
				$this->template->Assign('HideInsideRangeLabels', 'display: none');
				$this->template->Assign('HideOutsideRangeLabels', '');
			}
			else {
				$this->template->Assign('HideInsideRangeLabels', '');
				$this->template->Assign('HideOutsideRangeLabels', 'display: none');
			}
			$this->template->Assign('GraphDataRevenue', FormatPrice($revenue));
			$this->template->Assign('GraphDataNumOrders', number_format($numOrders));
			$graphRows .= $this->template->render('Snippets/DashboardOrderBreakdownGraphRow.html');
		}

		// Get the series labels for the axis
		$this->template->Assign('GraphSeriesLabel0', FormatPrice(0, true));
		for($i=1; $i <= $numIntervals; ++$i) {
			$this->template->Assign('GraphSeriesLabel'.$i, FormatPrice($interval*$i, true));
		}

		return $graphRows;
	}

	/**
	 * Calculate the Y axis/height for the graph based on the supplied maximum
	 * value in the data series, and the interval to increase by on the chart.
	 *
	 * @param int The maximum value for the series.
	 * @param int The interval to increase by along the chart.
	 * @return int The height/Y axis for the chart
	 */
	private function CalculateGraphYMax($max, $interval)
	{
		$yMax = $max / $interval;
		$yMax = floor($yMax);
		$yMax += 1;
		$yMax *= $interval;
		return $yMax;
	}

	/**
	 * Generate the KPI table for orders, visitors, conversion rate etc.
	 * Will use the time period from the request if one exists (GET or COOKIE)
	 * or falls back to the last week.
	 *
	 * @return string The generated HTML for the performance indicators table.
	 */
	public function GeneratePerformanceIndicatorsTable()
	{
		if(!$this->auth->HasPermission(AUTH_Statistics_Overview)) {
			return false;
		}

		// If we don't have a period coming in via the URL, use the default
		if(!isset($_GET['period'])) {
			// Is it set in a cookie?
			if(isset($_COOKIE['DashboardPerformanceIndicatorsPeriod'])) {
				$period = $_COOKIE['DashboardPerformanceIndicatorsPeriod'];
			}
			else {
				$period = 'week';
			}
		}
		else {
			$period = $_GET['period'];
		}

		// Determine for which dates we need to fetch the statistics
		switch($period) {
			case 'week':
				$lastPeriodFrom = isc_gmmktime(0, 0, 0, isc_date('m'), isc_date('d')-13, isc_date('y'));
				$thisPeriodFrom = isc_gmmktime(0, 0, 0, isc_date('m'), isc_date('d')-6, isc_date('y'));
				break;
			case 'month':
				$lastPeriodFrom = isc_gmmktime(0, 0, 0, isc_date('m')-2, isc_date('d'), isc_date('y'));
				$thisPeriodFrom = isc_gmmktime(0, 0, 0, isc_date('m')-1, isc_date('d'), isc_date('y'));
				break;
			case 'year':
				$lastPeriodFrom = isc_gmmktime(0, 0, 0, isc_date('m'), isc_date('d'), isc_date('y')-2);
				$thisPeriodFrom = isc_gmmktime(0, 0, 0, isc_date('m'), isc_date('d'), isc_date('y')-1);
				break;
			default:
				$period = 'day';
				$lastPeriodFrom = isc_gmmktime(0, 0, 0, isc_date('m'), isc_date('d')-1, isc_date('y'));
				$thisPeriodFrom = isc_gmmktime(0, 0, 0, isc_date('m'), isc_date('d'), isc_date('y'));
		}

		$this->template->Assign('LastPeriodHeader', GetLang('Last'.ucfirst($period)));
		$this->template->Assign('ThisPeriodHeader', GetLang('This'.ucfirst($period)));

		// Run up until 1 second before the current period. Subtracting 1 second allows us to generate displayable dates for the period.
		$lastPeriodTo = $thisPeriodFrom-1;

		if($period != 'day') {
			$this->template->Assign('LastPeriodDateRange', CDate($lastPeriodFrom).' - '.CDate($lastPeriodTo));
			$this->template->Assign('ThisPeriodDateRange', CDate($thisPeriodFrom).' - '.CDate(time()));
		}
		else {
			$this->template->Assign('LastPeriodDateRange', CDate($lastPeriodFrom));
			$this->template->Assign('ThisPeriodDateRange', CDate($thisPeriodFrom));
		}

		// Calculate the number of orders and the total revenue
		$vendorAdd = '';
		if($this->auth->GetVendorId()) {
			$vendorAdd .= " AND ordvendorid='".$this->auth->GetVendorId()."'";
		}

		$query = "
			SELECT SUM(total_inc_tax) AS totalrevenue, COUNT(orderid) AS numorders
			FROM [|PREFIX|]orders
			WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".$lastPeriodFrom."' AND orddate <= '".$lastPeriodTo."' ".$vendorAdd."
		";
		$result = $this->db->Query($query);
		$lastPeriodOrderStats = $this->db->Fetch($result);

		$query = "
			SELECT SUM(total_inc_tax) AS totalrevenue, COUNT(orderid) AS numorders
			FROM [|PREFIX|]orders
			WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".$thisPeriodFrom."' ".$vendorAdd."
		";
		$result = $this->db->Query($query);
		$thisPeriodOrderStats = $this->db->Fetch($result);

		// Calculate the number of visitors
		if(!$this->auth->GetVendorId()) {
			$query = "
				SELECT SUM(numuniques)
				FROM [|PREFIX|]unique_visitors
				WHERE datestamp >= '".$lastPeriodFrom."' AND datestamp <= '".$lastPeriodTo."'
			";
			$lastPeriodVisitorStats = $this->db->FetchOne($query);
			$query = "
				SELECT SUM(numuniques)
				FROM [|PREFIX|]unique_visitors
				WHERE datestamp >= '".$thisPeriodFrom."'
			";
			$thisPeriodVisitorStats = $this->db->FetchOne($query);

			// Calculate the percentage change in visitors between the last period and the current period
			$visitorChange = $thisPeriodVisitorStats - $lastPeriodVisitorStats;
			$prefix = '';
			if($visitorChange == 0) {
				$visitorChangePercent = 0;
			}
			else if($lastPeriodVisitorStats > 0) {
				$visitorChangePercent = round(($visitorChange / $lastPeriodVisitorStats) * 100, 2);
			}
			else {
				$visitorChangePercent = 100;
			}

			if($visitorChangePercent > 0) {
				$prefix = '+';
				$this->template->Assign('NumVisitorsChangeClass', 'Positive');
			}
			else if($visitorChangePercent < 0) {
				$this->template->Assign('NumVisitorsChangeClass', 'Negative');
			}
			$visitorChangePercent = $prefix.number_format($visitorChangePercent, 2).'%';

			$this->template->Assign('LastPeriodNumVisitors', number_format($lastPeriodVisitorStats));
			$this->template->Assign('ThisPeriodNumVisitors', number_format($thisPeriodVisitorStats));
			$this->template->Assign('NumVisitorsChange', $visitorChangePercent);

			$lastConversion = 0;
			if($lastPeriodVisitorStats > 0) {
				$lastConversion = ($lastPeriodOrderStats['numorders'] / $lastPeriodVisitorStats) * 100;
			}
			$this->template->Assign('LastPeriodConversionRate', number_format(round($lastConversion, 2), 2));

			$thisConversion = 0;
			if($thisPeriodVisitorStats > 0) {
				$thisConversion = ($thisPeriodOrderStats['numorders'] / $thisPeriodVisitorStats) * 100;
			}
			$this->template->Assign('ThisPeriodConversionRate', number_format(round($thisConversion, 2), 2));

			// Calculate the difference between the two conversion dates to get the change
			$conversionChangePercent = $thisConversion - $lastConversion;
			$prefix = '';
			if($conversionChangePercent > 0) {
				$prefix = '+';
				$this->template->Assign('ConversionChangeClass', 'Positive');
			}
			else if($conversionChangePercent < 0) {
				$this->template->Assign('ConversionChangeClass', 'Negative');
			}
			$conversionChangePercent = $prefix.number_format($conversionChangePercent, 2).'%';
			$this->template->Assign('ConversionChange', $conversionChangePercent);

		}
		else {
			$this->template->Assign('HideConversionRate', 'display: none');
			$this->template->Assign('HideVisitorStats', 'display: none');
		}

		// Calculate the percentage change in revenue between the last period and the current period
		$revenueChange = $thisPeriodOrderStats['totalrevenue'] - $lastPeriodOrderStats['totalrevenue'];
		$prefix = '';
		if($revenueChange == 0) {
			$revenueChangePercent = 0;
		}
		else if($lastPeriodOrderStats['totalrevenue'] > 0) {
			$revenueChangePercent = round(($revenueChange / $lastPeriodOrderStats['totalrevenue']) * 100, 2);
		}
		else {
			$revenueChangePercent = 100;
		}

		if($revenueChangePercent > 0) {
			$prefix = '+';
			$this->template->Assign('TotalRevenueChangeClass', 'Positive');
		}
		else if($revenueChangePercent < 0) {
			$this->template->Assign('TotalRevenueChangeClass', 'Negative');
		}
		$revenueChangePercent = $prefix.number_format($revenueChangePercent, 2).'%';

		// Calculate the percentage change in the number of orders in the last period and the current period
		$numOrdersChange = $thisPeriodOrderStats['numorders'] - $lastPeriodOrderStats['numorders'];
		$prefix = '';
		if($numOrdersChange == 0) {
			$numOrdersChangePercent = 0;
		}
		else if($lastPeriodOrderStats['numorders'] > 0) {
			$numOrdersChangePercent = round(($numOrdersChange / $lastPeriodOrderStats['numorders']) * 100, 2);
		}
		else {
			$numOrdersChangePercent = 100;
		}

		if($numOrdersChangePercent > 0) {
			$prefix = '+';
			$this->template->Assign('NumOrdersChangeClass', 'Positive');
		}
		else if($numOrdersChangePercent < 0) {
			$this->template->Assign('NumOrdersChangeClass', 'Negative');
		}
		$numOrdersChangePercent = $prefix.number_format($numOrdersChangePercent, 2).'%';

		$this->template->Assign('LastPeriodRevenue', FormatPrice($lastPeriodOrderStats['totalrevenue']));
		$this->template->Assign('LastPeriodNumOrders', number_format($lastPeriodOrderStats['numorders']));

		$this->template->Assign('ThisPeriodRevenue', FormatPrice($thisPeriodOrderStats['totalrevenue']));
		$this->template->Assign('ThisPeriodNumOrders', number_format($thisPeriodOrderStats['numorders']));

		$this->template->Assign('TotalRevenueChange',  $revenueChangePercent);
		$this->template->Assign('NumOrdersChange', $numOrdersChangePercent);

		// If they've just changed periods, store it in a cookie
		if(isset($_GET['period'])) {
			isc_setcookie('DashboardPerformanceIndicatorsPeriod', $period);
		}

		return $this->template->render('Snippets/DashboardPerformanceIndicators.html');
	}

	protected function getEbayListingNotifications()
	{
		$notifications = array();

		// Show information about email integration tasks in progress
		$keystore = Interspire_KeyStore::instance();
		$exports = $keystore->multiGet('ebay:list_products:id:*');
		foreach ($exports as $exportId) {
			$prefix = 'ebay:list_products:' . $exportId . ':';

			$offset = (int)$keystore->get($prefix . 'true_offset');
			$abort = (bool)$keystore->get($prefix . 'abort');
			$error = (int)$keystore->get($prefix . 'error_count');
			$total = (int)$keystore->get($prefix . 'estimated_total');
			$eta = '';

			if ($total) {
				if ($offset) {
					$per = (time() - (int)$keystore->get($prefix . 'started')) / $offset;
					$remaining = Store_DateTime::duration(($total - $offset) * $per, Store_DateTime::DURATION_MINUTES);
					if ($remaining) {
						$eta = '<br />' . GetLang('Ebay_Notifications_InProgress_ETA', array(
							'remaining' => $remaining,
						));
					}
				}
				$total = GetLang('of') . ' ' . number_format($total, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
			} else {
				$total = '';
			}

			$offset = number_format($offset, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
			$error = number_format($error, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
			$template = $keystore->get($prefix . 'template_name');

			$notice = '';

			if ($abort) {
				$notice = GetLang('Ebay_Notifications_Abort', array(
					'template' => $template,
				));
			}
			else
			{
				$notice = GetLang('Ebay_Notifications_InProgress', array(
					'offset' => $offset,
					'total' => $total,
					'error' => $error,
					'eta' => $eta,
					'template' => $template,
				)) . '<br /><a href="#" class="Ebay_Export_Abort" id="Ebay_Export_Abort_' . isc_html_escape($exportId) . '" title="' . isc_html_escape(GetLang('Ebay_Notifications_InProgress_Abort_Title')) . '">' . GetLang('Ebay_Notifications_InProgress_Abort_Label') . '</a>';
			}

			if ($notice) {
				$notifications[] = $notice;
			}
		}

		return $notifications;
	}
}
