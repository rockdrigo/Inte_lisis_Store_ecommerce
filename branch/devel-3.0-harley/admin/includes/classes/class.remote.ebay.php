<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

/**
 * A class processing the remote request for eBay functions
 */
class ISC_ADMIN_REMOTE_EBAY extends ISC_ADMIN_REMOTE_BASE {

	/**
	 * The constructor.
	 */
	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('ebay');
		parent::__construct();

		GetLib('class.json');
	}

	public function HandleToDo()
	{
		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Ebay_Selling) || !gzte11(ISC_LARGEPRINT)) {
			exit;
		}

		$what = isc_strtolower(@$_REQUEST['w']);

		$methodName = $what . 'Action';
		if(!method_exists($this, $methodName)) {
			exit;
		}

		$this->$methodName();
	}

	/**
	 * Get the information regarding to the user's eBay store
	 * @return array Return a result of eBay API calls
	 */
	public function getEbayStoreAction()
	{
		// Check if the eBay keys are exist for API connection
		if (!ISC_ADMIN_EBAY::checkEbayConfig()) {
			ISC_JSON::output(GetLang('EbayStoreKeysMissing'), false);
		}

		try {
			$xml = ISC_ADMIN_EBAY_OPERATIONS::getStore();
		}
		catch (ISC_EBAY_API_EXCEPTION $ex) {
			ISC_JSON::output(GetLang('NoEbayStoreFound'), false, array('noStore' => true));
		}

		// otherwise, save the eBay store
		$GLOBALS['ISC_NEW_CFG']['EbayStore'] = (string)$xml->Store->Name;
		$settings = GetClass('ISC_ADMIN_SETTINGS');

		// if something goes wrong when we saving the configuration, return error message to caller
		if(!$settings->CommitSettings()) {
			ISC_JSON::output(GetLang('EbaySettingsNotSaved'), false);
		}

		// Log this action if we are in the control panel
		if (defined('ISC_ADMIN_CP')) {
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
		}

		ISC_JSON::output(GetLang('EbayGetStoreSuccess'), true, array('storeName' => (string)$xml->Store->Name));
	}


	/**
	* Get the dialog for listing products on eBay
	*
	*/
	private function getListProductsDialogAction()
	{
		$productOptions = $_POST['productOptions'];

		// check if ebay is configured
		$ebay = new ISC_ADMIN_EBAY();
		if (!ISC_ADMIN_EBAY::checkEbayConfig()) {
			$this->template->assign('message', GetLang('EbayListingStoreNotConfigured'));
			$this->template->display('ebay.listproducts.error.tpl');
			exit;
		}

		// get the templates
		$query = 'SELECT id, name FROM [|PREFIX|]ebay_listing_template ORDER BY name';
		$res = $this->db->Query($query);
		$templates = array();
		while ($row = $this->db->Fetch($res)) {
			$templates[$row['id']] = $row['name'];
		}

		if (empty($templates)) {
			$this->template->assign('message', GetLang('EbayListingNoTemplates'));
			$this->template->display('ebay.listproducts.error.tpl');
			exit;
		}

		$where = ISC_ADMIN_EBAY_LIST_PRODUCTS::getWhereFromOptions($productOptions);
		$productCount = ISC_ADMIN_EBAY_LIST_PRODUCTS::getProductCount($where);

		$this->template->assign('templates', $templates);
		$this->template->assign('productOptions', ISC_JSON::encode($productOptions));
		$this->template->assign('productCount', $productCount);
		$this->template->display('ebay.listproducts.tpl');
	}

	/**
	* Gets the estimated fees and costs of a listing for eBay
	*
	*/
	private function getEstimatedListingCostsAction()
	{
		$productOptions = $_POST['productOptions'];
		$productCount = (int)$_POST['productCount'];
		$templateId = (int)$_POST['templateId'];
		$listingDate = $_POST['listingDate'];
		$scheduleDate = $_POST['scheduleDate'];

		$testProduct = array(
			'productid'			=> 0,
			'prodname'			=> 'Test Product',
			'proddesc'			=> 'Description',
			'prodcode'			=> 'SKU',
			'prodprice'			=> 50.00,
			'prodcondition'		=> 'New',
			'prodweight'		=> 5,
			'prodwidth'			=> 5,
			'prodheight'		=> 5,
			'proddepth'			=> 5,
			'prodvariationid'	=> 0,
		);

		try {
			$template = new ISC_ADMIN_EBAY_TEMPLATE($templateId);

			if ($listingDate == 'schedule') {
				$isoScheduleDate = date('c', $scheduleDate);

				$template->setScheduleDate($isoScheduleDate);
			}

			$result = ISC_ADMIN_EBAY_LIST_PRODUCTS::verifyListItem($testProduct, $template);

			// was there item level errors? we want to display those.
			if (!$result->isValid()) {
				$errorMessage = implode('<br />', $result->getErrors());
				throw new Exception($errorMessage);
			}
		}
		catch (Exception $ex) {
			ISC_JSON::output(isc_html_escape($ex->getMessage()), false);
		}

		$firstFee = current($result->getFees());
		$currencyCode = $firstFee['currency'];
		$currency = GetCurrencyByCode($currencyCode);

		$estimatedTotal = 0;
		$perItem = 0;
		$fees = array();
		foreach ($result->getFees() as $fee) {
			if ($fee['fee'] == 0 || $fee['name'] == 'ListingFee') {
				continue;
			}

			$fees[] = array(
				'name' => GetLang('EbayFee' . $fee['name']),
				'fee' => $this->formatFee($fee['fee'], $currency)
			);

			$perItem += $fee['fee'];
		}

		$quantityPerProduct = 1;
		if ($template->getSellingMethod() == ISC_ADMIN_EBAY::CHINESE_AUCTION_LISTING) {
			$quantityPerProduct = $template->getQuantityToSell();
		}
		$this->template->assign('quantity', $quantityPerProduct);

		$itemCount = $productCount * $quantityPerProduct;

		// estimated total fees
		$itemsTotal = $perItem * $productCount;
		$grandTotal = $itemsTotal * $quantityPerProduct;

		$this->template->assign('fees', $fees);
		$this->template->assign('currencyCode', $currencyCode);
		$this->template->assign('itemsTotal', $this->formatFee($itemsTotal, $currency));
		$this->template->assign('grandTotal', $this->formatFee($grandTotal, $currency));
		$this->template->assign('perItem', $this->formatFee($perItem, $currency));
		$this->template->assign('productCount', $productCount);
		$this->template->assign('itemCount', $itemCount);

		// build description of extra fees
		$extraFeesString = GetLang('EbayListingEstimatedCostsExtraFees');
		$extraFees = array();
		if ($template->getQuantityToSell() > $template->getTrueQuantityToSell()) {
			$extraFees[] = GetLang('EbayListingEstimatedCostsAuctionWarning', array('quantity' => $template->getQuantityToSell()));
		}
		$extraFees[] = GetLang('EbayListingEstimatedCostsFinalValueFee');

		foreach ($extraFees as $x => $fee) {
			$extraFeesString .= "\n\n" . ($x + 1) . '. ' . $fee;
		}

		$out = array(
			'html' 		=> $this->template->render('ebay.listproducts.estimatedcosts.tpl'),
			'extraFees' => $extraFeesString,
		);

		ISC_JSON::output('', true, $out);
	}

	/**
	* Formats a fee amount in the given currency. If $currencyId is false then basic formatting will be used.
	*
	* @param double $fee The fee to format
	* @param mixed $currency Array of currency data or Id of the currency to format the fee in, or false to use basic formatting
	* @return string The formatted fee
	*/
	private function formatFee($fee, $currency)
	{
		// format the price in the appropriate currency if we have it
		if ($currency !== false) {
			$formattedFee = FormatPrice($fee, false, true, false, $currency);
		}
		else {
			// use basic formatting
			$formattedFee = number_format($fee, 2, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
		}

		return $formattedFee;
	}

	/**
	* Initiliazes a job to list products on eBay
	*
	*/
	private function initProductListingAction()
	{
		$productOptions = $_POST['productOptions'];
		$productCount = (int)$_POST['productCount'];
		$templateId = (int)$_POST['templateId'];

		try {
			$template = new ISC_ADMIN_EBAY_TEMPLATE($templateId);
		}
		catch (Exception $ex) {
			ISC_JSON::output($ex->getMessage(), false);
		}

		$estimatedTotal = $productCount * $template->getQuantityToSell();

		$where = ISC_ADMIN_EBAY_LIST_PRODUCTS::getWhereFromOptions($productOptions);

		// list 'now' or 'schedule'
		$listingDate = $_POST['listingDate'];
		$isoScheduleDate = '';

		if ($listingDate == 'schedule') {
			// user's timestamp date in GMT
			$scheduleDate = $_POST['scheduleDate'];

			// convert to ISO 8601
			$isoScheduleDate = date('c', $scheduleDate);
		}

		$keystore = Interspire_KeyStore::instance();

		// find a unique export id to use
		do {
			$id = md5(uniqid('',true));
		} while ($keystore->exists('ebay:list_products:id:' . $id));
		$keystore->set('ebay:list_products:id:' . $id, $id);

		$prefix = 'ebay:list_products:' . $id;

		$keystore->set($prefix . ':started', time());
		$keystore->set($prefix . ':abort', 0);
		$keystore->set($prefix . ':offset', 0);
		$keystore->set($prefix . ':actual_processed', 0);
		$keystore->set($prefix . ':actual_listed', 0);
		$keystore->set($prefix . ':estimated_total', $estimatedTotal);
		$keystore->set($prefix . ':templateid', $templateId);
		$keystore->set($prefix . ':template_name', $template->getTemplateName());
		$keystore->set($prefix . ':listing_date', $listingDate);
		$keystore->set($prefix . ':schedule_date', $isoScheduleDate);
		$keystore->set($prefix . ':where', $where);
		$keystore->set($prefix . ':options', ISC_JSON::encode($productOptions));
		$keystore->set($prefix . ':success_count', 0);
		$keystore->set($prefix . ':warning_count', 0);
		$keystore->set($prefix . ':error_count', 0);

		// so we can send an email later, or diagnose troublesome users
		$user = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUser();
		$keystore->set($prefix . ':owner:id', $user['pk_userid']);
		$keystore->set($prefix . ':owner:username', $user['username']);
		$keystore->set($prefix . ':owner:email', $user['useremail']);

		$jobData = array(
			'id' => $id,
		);

		$json = array(
			'success' => (bool)Interspire_TaskManager::createTask('ebay', 'Job_Ebay_ListProducts', $jobData),
			'id' => $id,
		);

		ISC_JSON::output($json);
	}

	/**
	* Aborts an in-progress listing job
	*
	*/
	private function abortProductListingAction()
	{
		$jobId = $_POST['jobId'];
		$jobKey = 'ebay:list_products:id:' . $jobId;
		$abortFlag = 'ebay:list_products:' . $jobId . ':abort';

		$keystore = Interspire_KeyStore::instance();

		if ($keystore->exists($jobKey)) {
			$keystore->set($abortFlag, 1);

			// if the export job has crashed, the abort will never be detected and data will never be cleaned up
			Interspire_TaskManager::createTask('ebay', 'Job_Ebay_ListProducts', array(
				'id' => $jobId,
			));
		}
	}

	/**
	* Gets the current progress of a specific listing job
	*
	*/
	private function getListingProgressAction()
	{
		$jobId = $_POST['jobId'];
		$jobKey = 'ebay:list_products:id:' . $jobId;
		$prefix = 'ebay:list_products:' . $jobId . ':';

		$keystore = Interspire_KeyStore::instance();

		$percent = 0;
		$eta = '';
		$progress = '';
		$progressBasic = '';

		if ($keystore->exists($jobKey)) {
			$offset = (int)$keystore->get($prefix . 'actual_processed');
			$abort = (bool)$keystore->get($prefix . 'abort');
			$error = (int)$keystore->get($prefix . 'error_count');
			$total = (int)$keystore->get($prefix . 'estimated_total');

			if ($total) {
				if ($offset) {
					$percent = round($offset / $total * 100);
					if ($percent > 100) {
						$percent = 100;
					}

					$per = (time() - (int)$keystore->get($prefix . 'started')) / $offset;
					$remaining = Store_DateTime::duration(($total - $offset) * $per, Store_DateTime::DURATION_MINUTES);
					$eta = "<br />" . GetLang('EbayListingETA', array(
						'remaining' => $remaining,
					));
				}

				$total = GetLang('of') . ' ' . number_format($total, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
			}
			else {
				$total = '';
			}

			$offset = number_format($offset, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));
			$error = number_format($error, 0, GetConfig('DecimalToken'), GetConfig('ThousandsToken'));

			$progress = GetLang('EbayListingProgress', array(
				'offset' => $offset,
				'total' => $total,
				'error' => $error,
				'eta' => $eta
			));

			$progressBasic = GetLang('EbayListingProgressBasic', array(
				'offset' => $offset,
				'total' => $total,
			));
		}
		else {
			$percent = 100;
		}

		ISC_JSON::output('', true, array('percent' => $percent, 'progress' => $progress, 'progressBasic' => $progressBasic));
	}

	/**
	* Gets the html for the select category dialog
	*
	*/
	private function getSelectCategoryDialogAction()
	{
		$this->template->display('ebay.selectcategory.tpl');
	}

	/**
	* Gets a list of categories for a particular parent ID
	*
	*/
	private function getCategoryListAction()
	{
		// variables init
		$selectedCategoryId = (int)$_POST['categoryId'];
		$nextLevel = (int)$_POST['currentLevel'] + 1;
		$categoryType = $_POST['categoryType'];
		$siteId = $_POST['siteId'];

		//$GLOBALS['ExistingCatIds'] = $_REQUEST['existingCatIds'];

		try {
			// get the categories for the eBay site
			if ($categoryType == 'ebay') {
				$categories = ISC_ADMIN_EBAY_CATEGORIES::getCategories($selectedCategoryId, $nextLevel, $siteId);
			}
			else {
				$categories = ISC_ADMIN_EBAY_CATEGORIES::getStoreCategories($selectedCategoryId, $nextLevel, $siteId);
			}
		}
		catch (ISC_EBAY_API_EXCEPTION $ex)
		{
			ISC_JSON::output($ex->getMessage());
		}

		// Restoring existing selected cats
		/*
		$existingCatIds = explode('_', $GLOBALS['ExistingCatIds']);
		$nextCatId = -2;
		if (!empty ($existingCatIds) && $existingCatIds[0] != '') {
			$nextCatId = array_shift($existingCatIds);
			$GLOBALS['ExistingCatIds'] = implode('_', $existingCatIds);
		}
		*/

		$this->template->assign('parentId', $selectedCategoryId);
		$this->template->assign('categories', $categories);

		$html = $this->template->render('ebay.categorybox.tpl');

		$output = array(
			'html' => $html,
			'selectedcatid' => $selectedCategoryId
		);

		//'nextcatid' => $nextCatId

		// return the categories in the correct html template
		ISC_JSON::output('', true, $output);
	}

	/**
	* Gets specific details and available item conditions for a specific category
	*
	*/
	private function getCategoryFeaturesAction()
	{
		$categoryId = (int)$_POST['categoryId'];
		$siteId = (int)$_POST['siteId'];

		try {
			$categoryFeatures = ISC_ADMIN_EBAY_CATEGORIES::getCategoryFeatures($categoryId, $siteId);

			$categoryPath = ISC_ADMIN_EBAY_CATEGORIES::getFormattedCategoryPath($categoryId, $siteId);
			$categoryFeatures['path'] = $categoryPath;
		}
		catch (Exception $ex) {
			ISC_JSON::output('The category information could not be retrieved.', false);
		}

		$output = array(
			'categoryFeatures' => $categoryFeatures
		);

		// are we requesting features for the primary category?
		if (!empty($_POST['primaryCategory'])) {
			// include conditions
			if ($categoryFeatures['has_conditions']) {
				$conditions = array('-- Do Not Map --');
				$conditions += $categoryFeatures['conditions'];

				$this->template->assign('conditions', $conditions);
				$this->template->assign('conditionRequired', $categoryFeatures['conditions_required']);
				$output['conditionsHTML'] = $this->template->render('ebay.mapconditions.tpl');
			}

			// generate a list of features
			$this->template->assign('categoryOptions', $categoryFeatures);
			$currencyId = GetClass('ISC_ADMIN_EBAY')->getCurrencyFromSiteId($siteId);
			$currency = GetCurrencyById($currencyId);
			$this->template->assign('currency', $currency);
			$output['categoryFeaturesList'] = $this->template->render('ebay.template.featureslist.tpl');
		}

		ISC_JSON::output('', true, $output);
	}

	/**
	* Displays the list of supported category features
	*
	*/
	private function getCategoryFeaturesListAction()
	{
		$templateId = (int)$_POST['templateId'];
		$productOptions = $_POST['productOptions'];
		$originalProductCount = $_POST['originalProductCount'];
		$productCount = $_POST['productCount'];
		$output['productOptions'] = $productOptions;
		$output['productCount'] = $productCount;
		$message = '';
		try {
			$template = new ISC_ADMIN_EBAY_TEMPLATE($templateId);
		}
		catch (Exception $ex) {
			ISC_JSON::output('', false);
		}

		$categoryOptions = $template->getPrimaryCategoryOptions();
		$secondaryCategoryOptions = $template->getSecondaryCategoryOptions();
		$secCatNotSupportVariations = (isset ($secondaryCategoryOptions['variations_supported']) && $secondaryCategoryOptions['variations_supported'] == 0);
		// if product variations are not supported in both primary, secondary categories, or the selling method is fixed selling method
		if ((empty ($categoryOptions['variations_supported'])
		|| $secCatNotSupportVariations)
		|| $template->getSellingMethod() != ISC_ADMIN_EBAY::FIXED_PRICE_LISTING) {
			$updatedProducts['productIds'] = array();
			if ($where = ISC_ADMIN_EBAY_LIST_PRODUCTS::getWhereFromOptions($productOptions)) {
				$where .= " AND ";
			}
			$where .= " p.prodvariationid <= 0 ";
			$res = ISC_ADMIN_EBAY_LIST_PRODUCTS::getProducts($where);
			while ($row = $this->db->Fetch($res)) {
				$updatedProducts['productIds'][] = urlencode('products[]='.$row['productid']);
			}

			$validProductCount = count($updatedProducts['productIds']);
			$invalidProductsCount = $originalProductCount - $validProductCount;
			if (empty ($validProductCount)) {
				// all selected products have variation
				$message = GetLang('VariationInvalidProducts');
				$output['productCount'] = 0;
				$output['productOptions'] = '';
			} else if ($validProductCount != $originalProductCount) {
				// some products couldn't be listed
				$message = sprintf(GetLang('PartialVariationInvalidProducts'), $invalidProductsCount);
				$output['productOptions']['productIds'] = implode('&', $updatedProducts['productIds']);
				$output['productCount'] = $validProductCount;
			}
		}

		// generate a list of features
		$this->template->assign('secCatSelectedNotSupportVariations', ($secCatNotSupportVariations));
		$this->template->assign('categoryOptions', $categoryOptions);
		$this->template->assign('secondaryCategoryOptionsData', $secondaryCategoryOptions);
		$this->template->assign('currency', $template->getCurrency());
		$this->template->assign('message', $message);
		$this->template->assign('sellingMethod', $template->getSellingMethod());

		$output['html'] = $this->template->render('ebay.template.featureslist.tpl');

		ISC_JSON::output('', true, $output);
	}

	/**
	* Checks if a template name is in use
	*
	*/
	private function checkTemplateNameAction($return = false)
	{
		$templateName = $_POST['templateName'];
		if (trim($templateName) == '') {
			ISC_JSON::output('Please enter a template name.', false);
		}

		$query = "
			SELECT
				COUNT(*)
			FROM
				[|PREFIX|]ebay_listing_template
			WHERE
				name = '" . $this->db->Quote($templateName) . "'
		";

		if (!empty($_POST['templateId'])) {
			$query .= ' AND id != ' . (int)$_POST['templateId'];
		}

		$res = $this->db->Query($query);
		if ($this->db->FetchOne($res) > 0) {
			ISC_JSON::output(GetLang('TemplateNameInUse', array('templateName' => $templateName)), false);
		}

		if (!$return) {
			ISC_JSON::output('', true);
		}
	}

	/**
	* Gets the updated template listing html based on the site id
	*
	*/
	private function loadTemplateFormAction()
	{
		$siteId = (int)$_POST['siteId'];
		$categoryOptions = $_POST['categoryOptions'];
		$templateId = (int)$_POST['templateId'];

		try {
			$listingTemplateHtml = GetClass('ISC_ADMIN_EBAY')->getTemplateForm($siteId, $categoryOptions, $templateId);
		}
		catch (Exception $ex) {
			ISC_JSON::output(GetLang('TemplateFormNotLoaded', array('error' => $ex->getMessage())), false);
		}

		$output = array(
			'listingTemplateHtml' => $listingTemplateHtml
		);

		ISC_JSON::output('', true, $output);
	}

	/**
	* Saves a template
	*
	*/
	public function saveTemplateAction()
	{
		$this->checkTemplateNameAction(true);

		$this->normalizePostData($_POST);

		$templateId = (int)$_POST['templateId'];

		$error = '';
		$templateId = GetClass('ISC_ADMIN_EBAY')->saveEbayTemplate($error, $templateId);
		if ($templateId === false) {
			ISC_JSON::output(GetLang('ErrorSavingTemplate'), false);
		}
		else {
			ISC_JSON::output('', true, array('templateId' => $templateId));
		}
	}

	public function normalizePostData(&$data)
	{
		foreach ($data as $key => &$value) {
			if (is_array($value)) {
				$this->normalizePostData($value);
			}
			elseif ($value === 'true') {
				$data[$key] = true;
			}
			elseif ($value === 'false') {
				$data[$key] = false;
			}
		}
	}

	/**
	* Gets the html for the end listing dialog
	*
	*/
	private function getEndListingDialogAction()
	{
		$this->template->assign('itemCount', $_REQUEST['itemCount']);
		$this->template->display('ebay.endlisting.tpl');
	}

	/**
	* Gets the form for available reason of ending the listing
	*
	*/
	private function getEndingReasonAction()
	{
		$endReasons =  array(
			'Incorrect' => GetLang('EndingReasonIncorrect'),
			'LostOrBroken' => GetLang('EndingReasonLostOrBroken'),
			'NotAvailable' => GetLang('EndingReasonNotAvailable'),
			'OtherListingError' => GetLang('EndingReasonOtherListingError'),
			'SellToHighBidder' => GetLang('EndingReasonSellToHighBidder'),
		);
		$this->template->assign('endReasons', $endReasons);
		$output['endinglistingreasonHTML'] = $this->template->render('ebay.endinglistingreason.tpl');
		ISC_JSON::output('', true, $output);
	}

	/**
	 * This function will send the command to eBay to end the listing
	 */
	private function endListingAction()
	{
		$output['Success'] = false;
		$output['PartiallySuccess'] = false;
		$output['Failure'] = false;
		$output['UpdatedListingRows'] = false;
		$selectedItems = $_REQUEST['selectedItems'];
		$selectedReason = $_REQUEST['selectedReason'];
		$itemData = array();
		$deleteItemIds = array();
		$responseHtml = '';
		$endItemResults = array();
		foreach ($selectedItems as $selectedItem) {
			$itemData[] = array(
				'Id' => $selectedItem,
				'EndingReason' => $selectedReason
			);
		}
		while (!empty ($itemData)) {
			$doItem = array_splice($itemData, 0, 10);
			$tempEndItemResults = ISC_ADMIN_EBAY::endEbayListing($doItem);
			if (is_array($tempEndItemResults)) {
				$endItemResults = array_merge($endItemResults, $tempEndItemResults);
			}
		}

		if (!empty ($endItemResults)) {
			foreach ($endItemResults as $endItemResult) {
				$item = ISC_ADMIN_EBAY::getEbayListingRefByItemId($endItemResult->getItemId());
				if( !empty ($item)) {
					$item = array_shift($item);
					if ($endItemResult->hasErrors()) {
						$GLOBALS['ItemRowText'] = sprintf(GetLang('ItemEndedFailure'), $item['title'], $item['ebay_item_id']);
						$output['PartiallySuccess'] = true;
					} else {
						$GLOBALS['ItemRowText'] = sprintf(GetLang('ItemEndedSuccess'), $item['title'], $item['ebay_item_id']);
						$deleteItemIds[] = $item['ebay_item_id'];
					}
					$responseHtml .= $this->template->render('ebay.endinglistingresult.row.tpl');
				}
			}
			if (!$output['PartiallySuccess']) {
				$output['AllSuccess'] = true;
				$responseHtml = '';
			}
		} else {
			$output['Failure'] = true;
		}

		if (!empty ($deleteItemIds)) {
			ISC_ADMIN_EBAY::deleteEbayListingRefByItemId($deleteItemIds);
			$output['UpdatedListingRows'] = GetClass('ISC_ADMIN_EBAY')->ManageEbayLiveListingGrid();
		}
		$output['UpdatedListingRows'] = GetClass('ISC_ADMIN_EBAY')->ManageEbayLiveListingGrid();
		$output['endinglistingresultHTML'] = $responseHtml;
		ISC_JSON::output('', true, $output);

	}

	/**
	* Initializes the eBay caching dialog
	*
	*/
	private function initEbayCacheUpdateAction()
	{
		$ebayClass = GetClass('ISC_ADMIN_EBAY');
		$expiredSites = $ebayClass->getExpiredCacheSites();

		$this->template->assign('totalSites', count($expiredSites));
		$this->template->display('ebay.ajaxupdate.tpl');
	}

	/**
	* Processes the next site that needs its cache updated
	*
	*/
	private function continueEbayCacheUpdateAction()
	{
		$ebayClass = GetClass('ISC_ADMIN_EBAY');
		$expiredSites = $ebayClass->getExpiredCacheSites();

		// no more to update
		if (empty($expiredSites)) {
			ISC_JSON::output('', true, array('done' => true));
		}

		// the next site to update
		$nextSiteId = current($expiredSites);

		$keystore = Interspire_KeyStore::instance();
		$prefix = 'ebay:details:last_update:site:';

		try {
			$xml = $ebayClass->GeteBayDetailsAction($nextSiteId);
		}
		catch (ISC_EBAY_API_EXCEPTION $ex) {
			FlashMessage($ex->getMessage(), MSG_ERROR, '', 'EbayConfig');
			ISC_JSON::output('', false);
		}

		// Write the content to local file system
		if(!$ebayClass->WriteCache($nextSiteId, $xml->asXML())) {
			FlashMessage(GetLang('EbayRequestProblem'), MSG_ERROR, '', 'EbayConfig');
			ISC_JSON::output('', false);
		}

		// set the last update time for the site
		if (!$keystore->set($prefix . $nextSiteId, time())) {
			FlashMessage(GetLang('EbayRequestProblem'), MSG_ERROR, '', 'EbayConfig');
			ISC_JSON::output('', false);
		}

		ISC_JSON::output('', true);
	}

	/**
	* Action that may assist in debugging platform notification issues.
	* Optioanlly supply an 'itemId' param in the request url to retrieve specific notification details pertaining to that item.
	*
	*/
	private function getNotificationsUsageAction()
	{
		$itemId = null;
		if (!empty($_GET['itemId'])) {
			$itemId = $_GET['itemId'];
		}

		$xml = ISC_ADMIN_EBAY_OPERATIONS::getNotificationsUsage(null, $itemId, null);

		header('Content-Type: application/xml');

		// output formatted XML if we can
		if (function_exists('dom_import_simplexml')) {
			$dom = new DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML($xml->asXML());
			echo $dom->saveXML();
		}
		else {
			$xml->asXML();
		}
	}
}
