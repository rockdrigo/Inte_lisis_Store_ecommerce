<?php

class Job_Ebay_ListProducts extends Job_Store_Abstract {
	/** @var int listing this many records per job */
	const BATCH_SIZE = 5;

	/** @var mysqldb local copy of store db instance */
	protected $_db;

	/** @var Interspire_KeyStore local copy of Interspire_KeyStore::instance for convenience */
	protected $_keystore;

	/** @var string the prefix for data for this job in the keystore */
	protected $_prefix;

	/**
	* @var ISC_ADMIN_ENGINE
	*/
	protected $_engine;

	/**
	* @var ISC_LOG
	*/
	protected $_log;

	public function setUp()
	{
		parent::setUp();

		GetLib('class.json');
		$this->_keystore = Interspire_KeyStore::instance();
		$this->_prefix = 'ebay:list_products:' . $this->args['id'] . ':';
		$this->_db = $GLOBALS['ISC_CLASS_DB'];
		$this->_engine = getClass('ISC_ADMIN_ENGINE');
		$this->_engine->LoadLangFile('ebay');
		$this->_log = $GLOBALS['ISC_CLASS_LOG'];
	}

	protected function _logDebug($summary, $message = '')
	{
		$this->_log->LogSystemDebug('ebay', GetLang('Ebay_Listing_Log_Prefix', array('id' => $this->args['id'])) . ' ' . $summary, $message);
	}

	protected function _logError($summary, $message = '')
	{
		$this->_log->LogSystemError('ebay', GetLang('Ebay_Listing_Log_Prefix', array('id' => $this->args['id'])) . ' ' . $summary, $message);
	}

	protected function _logSuccess($summary, $message = '')
	{
		$this->_log->LogSystemSuccess('ebay', GetLang('Ebay_Listing_Log_Prefix', array('id' => $this->args['id'])) . ' ' . $summary, $message);
	}

	protected function _logNotice($summary, $message = '')
	{
		$this->_log->LogSystemNotice('ebay', GetLang('Ebay_Listing_Log_Prefix', array('id' => $this->args['id'])) . ' ' . $summary, $message);
	}

	public function perform()
	{
		// validate the listing
		if (!$this->_validateListing()) {
			$this->_logDebug('Listing validation failed.');
			$this->_removeListing();
			return;
		}

		$templateId = $this->_getListingData('templateid');
		$where = $this->_getListingData('where');
		$offset = $this->_getListingData('offset');
		$listingDate = $this->_getListingData('listing_date');
		$scheduleDate = $this->_getListingData('schedule_date');

		$this->_logDebug('listing template Id ' . $templateId . ' starting from row ' . $offset . ' with batch size ' . self::BATCH_SIZE);

		// get the template object
		try {
			$template = new ISC_ADMIN_EBAY_TEMPLATE($templateId);
		}
		catch (Exception $ex) {
			$error = GetLang('Ebay_Listing_Log_TemplateNotFound', array('id' => $templateId));
			$this->_logError($error);
			$this->_errorListing($error);
			$this->_endListing();
			return;
		}

		$primaryOptions = $template->getPrimaryCategoryOptions();
		$secondaryOptions = $template->getSecondaryCategoryOptions();

		// did the user choose to schedule the listing for a future date?
		if ($listingDate == 'schedule') {
			$template->setScheduleDate($scheduleDate);
		}

		// query for the products to export
		$query = ISC_ADMIN_EBAY_LIST_PRODUCTS::getListQuery($where, self::BATCH_SIZE, $offset);

		$this->_logDebug('query', $query);

		$res = $this->_db->Query($query);
		if (!$res) {
			$error = GetLang('Ebay_Listing_Log_JobDatabaseError');
			$this->_logError($error);
			$this->_errorListing($error);
			$this->_endListing();
			return;
		}

		$successCount = 0;
		$warningCount = 0;
		$errorCount = 0;
		$connectFailCount = 0;

		// nothing left to list?
		$resultCount = $this->_db->CountResult($res);
		if ($resultCount == 0) {
			$this->_logDebug('no more items to list');
			$this->_endListing();
			return;
		}

		$productsToList = array();

		while ($row = $this->_db->Fetch($res)) {
			// does this product have a variation?
			if ($row['prodvariationid']) {
				$variationError = '';

				// if the primary category or selling method doesn't support them?
				if ((empty ($primaryOptions['variations_supported'])
				||  (isset ($secondaryOptions['variations_supported']) && $secondaryOptions['variations_supported'] == 0))
				|| $template->getSellingMethod() == ISC_ADMIN_EBAY::CHINESE_AUCTION_LISTING) {

					$variationError = GetLang('EbayListingVariationsNotSupported');
				}
				// does the product have more than 120 combinations (eBay max)?
				elseif (($totalCombinations = Store_Variations::getCombinationsCount($row['productid'], $row['prodvariationid'])) > 120) {
					$variationError = GetLang('EbayListingVariationCombinationsExceeded', array('totalCombinations' => $totalCombinations));
				}

				// log error and skip this product
				if ($variationError) {
					$error = array(
						'prodname' => $row['prodname'],
						'time' => time(),
						'message' => $variationError,
					);

					$this->_keystore->set($this->_prefix . 'error:' . md5($row['productid'] . uniqid('', true)), ISC_JSON::encode($error));

					$errorCount++;
					continue;
				}
			}

			// add any custom fields and configurable fields to the product
			ISC_ADMIN_EBAY_LIST_PRODUCTS::addCustomAndConfigurableFields($row);

			$productsToList[$row['productid']] = $row;
		}


		$itemsToAdd = 1;
		// for chinese auctions if we're selling more than one item, then create multiple items
		if ($template->getSellingMethod() == ISC_ADMIN_EBAY::CHINESE_AUCTION_LISTING && $template->getQuantityToSell() > 1) {
			$itemsToAdd = $template->getQuantityToSell();
		}

		$thisBatchSize = $resultCount;
		$actualProcessed = $thisBatchSize * $itemsToAdd;
		$actualListed = count($productsToList) * $itemsToAdd;

		// don't have any products to list for this batch (would be due to disallowed variations)?
		if (empty($productsToList)) {
			$this->_keystore->increment($this->_prefix . 'error_count', $errorCount);
			$this->_keystore->increment($this->_prefix . 'actual_processed', $actualProcessed);
			$this->_keystore->increment($this->_prefix . 'offset', $thisBatchSize);

			$this->_logDebug('processed 0 items');

			$this->_repeatListing();
			return;
		}

		$this->_logDebug($actualListed . ' items to list', '<pre>' . var_export($productsToList, true) . '</pre>');

		try {
			// list the items on eBay
			$results = array();
			for ($x = 0; $x < $itemsToAdd; $x++) {
				$results = array_merge($results, ISC_ADMIN_EBAY_LIST_PRODUCTS::listItems($productsToList, $template));
			}

			foreach ($results as /** @var ISC_ADMIN_EBAY_LIST_ITEM_RESULT */$result) {
				if (!$result->isValid()) {
					// log error
					$error = array(
						'prodname' => $result->getProductName(),
						'time' => time(),
						'message' => implode('<br />', $result->getErrors()),
					);

					$this->_keystore->set($this->_prefix . 'error:' . md5($result->getProductId() . uniqid('', true)), ISC_JSON::encode($error));

					$errorCount++;
					continue;
				}

				// valid listing, but has errors
				if ($result->hasErrors()) {
					// log warning
					$error = array(
						'prodname' => $result->getProductName(),
						'time' => time(),
						'message' => implode('<br />', $result->getErrors()),
					);

					$this->_keystore->set($this->_prefix . 'warning:' . md5($result->getProductId() . uniqid('', true)), ISC_JSON::encode($error));

					$warningCount++;
				}

				// ensure template has correct data set so we can calculate prices for the DB
				$template->setProductData($result->getProductData());

				// add the new item to our local database
				$insertItem = array(
					'product_id'				=> $result->getProductId(),
					'ebay_item_id'				=> $result->getItemId(),
					'title'						=> $result->getProductName(),
					'start_time'				=> $result->getStartTimeISO(),
					'end_time'					=> $result->getEndTimeISO(),
					'datetime_listed'			=> time(),
					'listing_type'				=> $template->getSellingMethod(),
					'listing_status'			=> 'pending', // this will be updated to 'Active' when we receive ItemListed notification
					'current_price_currency' 	=> $template->getCurrencyCode(),
					'current_price'				=> $template->getStartPrice(),
					'buyitnow_price'			=> $template->getBuyItNowPrice(),
					'buyitnow_price_currency'	=> $template->getCurrencyCode(),
					'bid_count'					=> 0,
					'quantity_remaining'		=> $template->getTrueQuantityToSell(),
					'site_id'					=> $template->getSiteId(),
				);

				$dbItemId = $this->_db->InsertQuery('ebay_items', $insertItem);

				// process the listing fees
				foreach ($result->getFees() as $fee) {
					$insertFee = array(
						'item_id'		=> $dbItemId,
						'name'			=> $fee['name'],
						'amount'		=> $fee['fee'],
						'currency_code'	=> $fee['currency']
					);

					$this->_db->InsertQuery('ebay_item_fees', $insertFee);
				}

				$successCount++;
			}
		}
		catch (ISC_EBAY_API_CONNECTION_EXCEPTION $ex) {
			// connection failed
			$connectFailCount++;

			// more than one connection failure? abort the listing
			if ($connectFailCount > 1) {
				$this->_abortListing();
			}

			// did the entire request fail?
			$this->logBatchException($productsToList, $ex);

			$errorCount += $actualListed;
		}
		catch (ISC_EBAY_API_REQUEST_EXCEPTION $ex) {
			// did the entire request fail?
			$this->logBatchException($productsToList, $ex);

			$errorCount += $actualListed;
		}

		$this->_keystore->increment($this->_prefix . 'success_count', $successCount);
		$this->_keystore->increment($this->_prefix . 'warning_count', $warningCount);
		$this->_keystore->increment($this->_prefix . 'error_count', $errorCount);
		$this->_keystore->increment($this->_prefix . 'actual_processed', $actualProcessed);
		$this->_keystore->increment($this->_prefix . 'actual_listed', $actualListed);
		$this->_keystore->increment($this->_prefix . 'offset', $thisBatchSize);

		$this->_logDebug('processed ' . $actualListed . ' items');

		$this->_repeatListing();
	}

	/**
	* Logs an exception on the entire request
	*
	* @param array $products The products in the batch
	* @param ISC_EBAY_API_EXCEPTION $ex The exception that was caught
	*/
	private function logBatchException($products, $ex)
	{
		$ids = "Product ID's: " . implode(', ', array_keys($products));

		$this->_logDebug('exception processing batch', $ex->getMessage());

		// log error
		$error = array(
			'prodname' => $ids,
			'time' => time(),
			'message' => $ex->getMessage(),
		);

		$this->_keystore->set($this->_prefix . 'error:' . md5($ids . uniqid('', true)), ISC_JSON::encode($error));
	}



	/** @return bool true if this listing job exists, otherwise false */
	protected function _listingExists()
	{
		return $this->_keystore->exists('ebay:list_products:id:' . $this->args['id']);
	}

	/**
	* Validates all data for this job
	*
	* @return bool true if the job can proceed
	*/
	protected function _validateListing()
	{
		// check if listing data exists
		if (!$this->_listingExists()) {
			$this->_logDebug(GetLang('Ebay_Listing_Log_DoesNotExist'));
			return false;
		}

		// check if listing is aborted
		if ((bool)$this->_getListingData('abort')) {
			$this->_abortListing();
			return false;
		}

		// check if the template exists
		$query = 'SELECT COUNT(*) FROM [|PREFIX|]ebay_listing_template WHERE id = ' . $this->_getListingData('templateid');
		$res = $this->_db->Query($query);
		if ($this->_db->FetchOne($res) == 0) {
			$this->_errorlisting(GetLang('/* todo template not found message */'));
			return false;
		}

		return true;
	}

	/**
	* call this when the job is flagged as aborted
	*
	* @return void
	*/
	protected function _abortListing()
	{
		$this->_logNotice(GetLang('Ebay_Listing_Log_AbortFlag'));

		$replacements = array(
			'template' => $this->_getListingData('template_name'),
			'offset' => $this->_getListingData('actual_processed'),
		);

		// notify user that the listing has been aborted due to user action
		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
		$obj_email->Set("Subject", GetLang("Ebay_Listing_Abort_Email_Subject", $replacements));
		$obj_email->AddRecipient($this->_getlistingData('owner:email'), "", "h");

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("ebay_listing_aborted");

		$emailTemplate->Assign('EmailHeader', GetLang("Ebay_Listing_Abort_Email_Subject", $replacements));
		$emailTemplate->Assign('Ebay_Listing_Abort_Email_Message_1', GetLang('Ebay_Listing_Abort_Email_Message_1', $replacements));
		$emailTemplate->Assign('Ebay_Listing_Abort_Email_Message_2', GetLang('Ebay_Listing_Abort_Email_Message_2', $replacements));

		$body = $emailTemplate->ParseTemplate(true);

		$obj_email->AddBody("html", $body);
		$email_result = $obj_email->Send();

		$this->_removeListing();

	}

	/**
	* call this when a job needs to be terminated due to an error
	*
	* @param string $message
	* @return void
	*/
	protected function _errorListing($message)
	{
		$this->_logDebug(GetLang('Ebay_Listing_Log_ErrorFlag', array('message' => $message)));

		// notify user that the listing has been aborted due to an error
		$replacements = array(
			'template' => $this->_getListingData('template_name')
		);

		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
		$obj_email->Set("Subject", GetLang("Ebay_Listing_Error_Email_Subject", $replacements));
		$obj_email->AddRecipient($this->_getlistingData('owner:email'), "", "h");

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("ebay_listing_failed");

		$emailTemplate->Assign('EmailHeader', GetLang("Ebay_Listing_Error_Email_Subject", $replacements));
		$emailTemplate->Assign('Ebay_Listing_Error_Email_Message_1', GetLang('Ebay_Listing_Error_Email_Message_1', $replacements));
		$emailTemplate->Assign('Ebay_listing_Error_Email_Error_Heading', GetLang('Ebay_Listing_Error_Email_Error_Heading', $replacements));
		$emailTemplate->Assign('Ebay_listing_Error_Email_Error', $message);
		$emailTemplate->Assign('Ebay_Listing_Error_Email_Error_Footer', GetLang('Ebay_Listing_Error_Email_Error_Footer', $replacements));

		$body = $emailTemplate->ParseTemplate(true);

		$obj_email->AddBody("html", $body);
		$email_result = $obj_email->Send();

		$this->_removeListing();
	}

	/**
	* call this when the job reaches the end of its data to list
	*
	* @return void
	*/
	protected function _endListing()
	{
		$this->_logDebug('end');

		$started = (int)$this->_getListingData('started');
		$finished = time();

		$replacements = array(
			'template' => $this->_getListingData('template_name'),
			'success_count' => (int)$this->_getListingData('success_count'),
			'error_count' => (int)$this->_getListingData('error_count'),
			'warning_count' => (int)$this->_getListingData('warning_count'),
			'start' => isc_date(GetConfig('ExtendedDisplayDateFormat'), $started),
			'end' => isc_date(GetConfig('ExtendedDisplayDateFormat'), $finished),
			'total' => (int)$this->_getListingData('actual_processed'),
		);

		// notify user that started export of completion
		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
		$obj_email->Set("Subject", GetLang("Ebay_Listing_End_Email_Subject", $replacements));
		$obj_email->AddRecipient($this->_getListingData('owner:email'), "", "h");

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("ebay_listing_finished");

		$emailTemplate->Assign('EmailHeader', GetLang("Ebay_Listing_End_Email_Subject", $replacements));

		$emailTemplate->Assign('Ebay_Listing_End_Email_Message_1', GetLang('Ebay_Listing_End_Email_Message_1', $replacements));
		$emailTemplate->Assign('Ebay_Listing_End_Email_Message_2', GetLang('Ebay_Listing_End_Email_Message_2', $replacements));
		$emailTemplate->Assign('Ebay_Listing_End_Email_Message_3', GetLang('Ebay_Listing_End_Email_Message_3', $replacements));
		$emailTemplate->Assign('Ebay_Listing_End_Email_Message_4', GetLang('Ebay_Listing_End_Email_Message_4', $replacements));

		// process errors
		if ($replacements['error_count']) {
			$errors = $this->_keystore->multiGet($this->_prefix . 'error:*');
			$errorHTML = '';

			$limit = 100; // limit number of errors reported via email to 100
			while (!empty($errors) && $limit) {
				$error = array_pop($errors);
				$error = ISC_JSON::decode($error, true);
				if (!$error) {
					// json decode error?
					continue;
				}

				$errorHTML .= '
					<dt>' . $error['prodname'] . '</dt>
					<dd>' . $error['message'] . '</dd>
					<br />
				';

				$limit--;
			}

			if ($errorHTML) {
				$errorHTML = '<dl>' . $errorHTML . '</dl>';

				// only show the heading if error info was successfully generated
				$emailTemplate->Assign('Ebay_Listing_End_Email_Errors_Heading', GetLang('Ebay_Listing_End_Email_Errors_Heading', $replacements));
				$emailTemplate->Assign('Ebay_Listing_End_Email_Errors', $errorHTML);
			}
		}

		// process warnings
		if ($replacements['warning_count']) {
			$errors = $this->_keystore->multiGet($this->_prefix . 'warning:*');
			$errorHTML = '';

			$limit = 100; // limit number of warnings reported via email to 100
			while (!empty($errors) && $limit) {
				$error = array_pop($errors);
				$error = ISC_JSON::decode($error, true);
				if (!$error) {
					// json decode error?
					continue;
				}

				$errorHTML .= '
					<dt>' . $error['prodname'] . '</dt>
					<dd>' . $error['message'] . '</dd>
					<br />
				';

				$limit--;
			}

			if ($errorHTML) {
				$errorHTML = '<dl>' . $errorHTML . '</dl>';

				// only show the heading if error info was successfully generated
				$emailTemplate->Assign('Ebay_Listing_End_Email_Warnings_Heading', GetLang('Ebay_Listing_End_Email_Warnings_Heading', $replacements));
				$emailTemplate->Assign('Ebay_Listing_End_Email_Warnings', $errorHTML);
			}
		}

		$body = $emailTemplate->ParseTemplate(true);

		$obj_email->AddBody("html", $body);
		$email_result = $obj_email->Send();

		$this->_removeListing();
	}


	/**
	* removes all data for this listing job from the keystore
	*
	* @return void
	*/
	protected function _removeListing()
	{
		$this->_logDebug('remove data');
		$this->_keystore->multiDelete('ebay:list_products:' . $this->args['id'] . ':*');
		$this->_keystore->delete('ebay:list_products:id:' . $this->args['id']);
	}

	/**
	* requeue this listing job so it can process the next batch
	*
	* @return mixed result of Interspire_TaskManager::createTask
	*/
	protected function _repeatListing()
	{
		return Interspire_TaskManager::createTask('ebay', get_class($this), $this->args);
	}

	/**
	* local shortcut for getting a piece of data specifically for this listing, $this->_prefix must be populated first before calling
	*
	* @param string $key
	* @return mixed
	*/
	protected function _getListingData($key)
	{
		return $this->_keystore->get($this->_prefix . $key);
	}

	/**
	* local shortcut for setting a piece of data specifically for this listing, $this->_prefix must be populated first before calling
	*
	* @param string $key
	* @return mixed
	*/
	protected function _setListingData($key, $value)
	{
		return $this->_keystore->set($this->_prefix . $key, $value);
	}
}