<?php

class Job_EmailIntegration_ModuleExport extends Job_EmailIntegration
{
	/** @var int export this many records per job */
	const BATCH_SIZE = 200;

	/** @var mysqldb local copy of store db instance */
	protected $_db;

	/** @var Interspire_KeyStore local copy of Interspire_KeyStore::instance for convenience */
	protected $_keystore;

	/** @var string the prefix for data for this job in the keystore */
	protected $_prefix;

	/** @var ISC_EMAILINTEGRATION the module this job is exporting to, will be populated by _validateExport */
	protected $_module;

	/** @var array storage for list data as returned by ISC_EMAILINTEGRATION->getList, will be populated by _validateExport */
	protected $_list;

	/** @var array field mapping defined for this export as key/value pair of providerField => localField , will be populated by _validateExport */
	protected $_map;

	/** @var array search parameters for this export, if any, will be populated by _validateExport */
	protected $_search;

	/** @var int amount of records to skip, which controls the batch progress, will be populated by _validateExport */
	protected $_skip;

	/** @var int whether or not to send double optin emails, will be populated by _validateExport */
	protected $_doubleOptin;

	/** @var int whether or not to overwrite existing subscriptions, will be populated by _validateExport */
	protected $_updateExisting;

	/** @var string type of export being performed, will be populated by _validateExport */
	protected $_type;

	public function setUp()
	{
		parent::setUp();
		GetLib('class.json');
		$this->_keystore = Interspire_KeyStore::instance();
		$this->_prefix = 'email:module_export:' . $this->args['id'] . ':';
		$this->_db = $GLOBALS['ISC_CLASS_DB'];
	}

	/** @return bool true if this export job exists, otherwise false */
	protected function _exportExists()
	{
		return $this->_keystore->exists('email:module_export:id:' . $this->args['id']);
	}

	/**
	* removes all data for this export job from the keystore
	*
	* @return void
	*/
	protected function _removeExport()
	{
		$this->_logDebug('remove data');
		$this->_keystore->multiDelete('email:module_export:' . $this->args['id'] . ':*');
		$this->_keystore->delete('email:module_export:id:' . $this->args['id']);
	}

	/**
	* call this when the job is flagged as aborted
	*
	* @return void
	*/
	protected function _abortExport()
	{
		$this->_logNotice(GetLang('EmailIntegration_Log_AbortFlag'));

		$replacements = array(
			'type' => $this->_type,
			'module' => $this->_module->GetName(),
			'skip' => $this->_skip,
		);

		// notify user that the export has been aborted due to user action
		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
		$obj_email->Set("Subject", GetLang("EmailIntegration_Export_Abort_Email_Subject", $replacements));
		$obj_email->AddRecipient($this->_getExportData('owner:email'), "", "h");

		$GLOBALS['EmailHeader'] = GetLang("EmailIntegration_Export_Abort_Email_Subject", $replacements);

		$GLOBALS['EmailIntegration_Export_Abort_Email_Message_1'] = GetLang('EmailIntegration_Export_Abort_Email_Message_1', $replacements);
		$GLOBALS['EmailIntegration_Export_Abort_Email_Message_2'] = GetLang('EmailIntegration_Export_Abort_Email_Message_2', $replacements);

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("email_integration_export_aborted");
		$message = $emailTemplate->ParseTemplate(true);

		$obj_email->AddBody("html", $message);
		$email_result = $obj_email->Send();

		$this->_removeExport();
	}

	/**
	* call this when the job reaches the end of its data to export
	*
	* @return void
	*/
	protected function _endExport()
	{
		$this->_logDebug('end');

		$started = (int)$this->_getExportData('started');
		$finished = time();

		$replacements = array(
			'type' => $this->_type,
			'module' => $this->_module->GetName(),
			'success_count' => (int)$this->_getExportData('success_count'),
			'error_count' => (int)$this->_getExportData('error_count'),
			'start' => isc_date(GetConfig('ExtendedDisplayDateFormat'), $started),
			'end' => isc_date(GetConfig('ExtendedDisplayDateFormat'), $finished),
			'total' => (int)$this->_getExportData('skip'),
		);

		// notify user that started export of completion
		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
		$obj_email->Set("Subject", GetLang("EmailIntegration_Export_End_Email_Subject", $replacements));
		$obj_email->AddRecipient($this->_getExportData('owner:email'), "", "h");

		$GLOBALS['EmailHeader'] = GetLang("EmailIntegration_Export_End_Email_Subject", $replacements);

		$GLOBALS['EmailIntegration_Export_End_Email_Message_1'] = GetLang('EmailIntegration_Export_End_Email_Message_1', $replacements);
		$GLOBALS['EmailIntegration_Export_End_Email_Message_2'] = GetLang('EmailIntegration_Export_End_Email_Message_2', $replacements);
		$GLOBALS['EmailIntegration_Export_End_Email_Message_3'] = GetLang('EmailIntegration_Export_End_Email_Message_3', $replacements);
		$GLOBALS['EmailIntegration_Export_End_Email_Message_4'] = GetLang('EmailIntegration_Export_End_Email_Message_4', $replacements);

		if ($replacements['error_count']) {
			$dl = new Xhtml_Dl();

			$errors = $this->_keystore->multiGet($this->_prefix . 'error:*');

			$limit = 100; // limit number of errors reported via email to 100
			while (!empty($errors) && $limit)
			{
				$error = array_pop($errors);
				$error = ISC_JSON::decode($error, true);
				if (!$error) {
					// json decode error?
					continue;
				}

				$dl->appendChild(new Xhtml_Dt($error['email']));

				$dd = new Xhtml_Dd($error['message']);
				$dd->attribute('style', 'margin-bottom:0.8em;');

				$dl->appendChild($dd);
				$limit--;
			}

			$GLOBALS['EmailIntegration_Export_End_Email_Errors'] = $dl->render();

			if ($GLOBALS['EmailIntegration_Export_End_Email_Errors']) {
				// only show the heading if error info was successfully generated
				$GLOBALS['EmailIntegration_Export_End_Email_Errors_Heading'] = GetLang('EmailIntegration_Export_End_Email_Errors_Heading', $replacements);
			}

			unset($dl);
		}

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("email_integration_export_finished");
		$message = $emailTemplate->ParseTemplate(true);

		$obj_email->AddBody("html", $message);
		$email_result = $obj_email->Send();

		$this->_removeExport();
	}

	/**
	* call this when a job needs to be terminated due to an error
	*
	* @param string $message
	* @return void
	*/
	protected function _errorExport($message)
	{
		$this->_logDebug('error');

		// notify user that the export has been aborted due to an error
		$replacements = array(
			'type' => $this->_type,
			'module' => $this->_module->GetName(),
		);

		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
		$obj_email->Set("Subject", GetLang("EmailIntegration_Export_Error_Email_Subject", $replacements));
		$obj_email->AddRecipient($this->_getExportData('owner:email'), "", "h");

		$GLOBALS['EmailHeader'] = GetLang("EmailIntegration_Export_Error_Email_Subject", $replacements);

		$GLOBALS['EmailIntegration_Export_Error_Email_Message_1'] = GetLang('EmailIntegration_Export_Error_Email_Message_1', $replacements);
		$GLOBALS['EmailIntegration_Export_Error_Email_Error_Heading'] = GetLang('EmailIntegration_Export_Error_Email_Error_Heading', $replacements);
		$GLOBALS['EmailIntegration_Export_Error_Email_Error'] = $message;
		$GLOBALS['EmailIntegration_Export_Error_Email_Error_Footer'] = GetLang('EmailIntegration_Export_Error_Email_Error_Footer', $replacements);

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("email_integration_export_failed");
		$message = $emailTemplate->ParseTemplate(true);

		$obj_email->AddBody("html", $message);
		$email_result = $obj_email->Send();

		$this->_removeExport();
	}

	/**
	* requeue this export job so it can process the next batch
	*
	* @return mixed result of Interspire_TaskManager::createTask
	*/
	protected function _repeatExport()
	{
		$this->_logDebug('repeating');
		return Interspire_TaskManager::createTask('emailintegration', get_class($this), $this->args);
	}

	/**
	* local shortcut for getting a piece of data specifically for this export, $this->_prefix must be populated first before calling
	*
	* @param string $key
	* @return mixed
	*/
	protected function _getExportData($key)
	{
		return $this->_keystore->get($this->_prefix . $key);
	}

	/**
	* local shortcut for setting a piece of data specifically for this export, $this->_prefix must be populated first before calling
	*
	* @param string $key
	* @return mixed
	*/
	protected function _setExportData($key, $value)
	{
		return $this->_keystore->set($this->_prefix . $key, $value);
	}

	/**
	* validates all data for this job, setting up $this->_module, $this->_list, etc.
	* @return bool true if the job can proceed
	*/
	protected function _validateExport()
	{
		$this->_skip = (int)$this->_getExportData('skip');
		$this->_doubleOptin = (int)$this->_getExportData('doubleoptin');
		$this->_updateExisting = (int)$this->_getExportData('updateexisting');

		// check if export data exists
		if (!$this->_exportExists()) {
			$this->_logDebug(GetLang('EmailIntegration_Log_DoesNotExist'));
			return false;
		}

		// check for valid export type
		$this->_type = $this->_getExportData('type');
		switch ($this->_type)
		{
			case 'Customer':
				$searchtype = 'customers';
				break;

			case 'Order':
				$searchtype = 'orders';
				break;

			default:
				$error = GetLang('EmailIntegration_Log_InvalidExportType', array(
					'type' => $this->_type,
				));
				$this->_logError($error);
				$this->_errorExport($error);
				return false;
		}

		// check if specified module exists
		$module = $this->_getExportData('module');
		GetModuleById('emailintegration', /** @var ISC_EMAILINTEGRATION */$this->_module, $module);
		if (!$this->_module) {
			$error = GetLang('EmailIntegration_Log_ModuleNotFound', array(
				'module' => $module,
			));
			$this->_logError($error);
			$this->_errorExport($error);
			return false;
		}

		// check if export is aborted
		if ((bool)$this->_getExportData('abort')) {
			$this->_abortExport();
			return false;
		}

		// check if module is enabled
		if (!$this->_module->IsEnabled()) {
			$error = GetLang('EmailIntegration_Log_ModuleNotEnabled', array(
				'module' => $this->_module->GetName(),
			));
			$this->_logError($error);
			$this->_errorExport($error);
			return false;
		}

		// check if module is configured
		if (!$this->_module->isConfigured()) {
			$error = GetLang('EmailIntegration_Log_ModuleNotConfigured', array(
				'module' => $this->_module->GetName(),
			));
			$this->_logError($error);
			$this->_errorExport($error);
			return false;
		}

		// check if the destination list exists, at least locally
		$list = $this->_getExportData('list');
		$this->_list = $this->_module->getList($list);
		if (!$this->_list) {
			$error = GetLang('EmailIntegration_Log_ListNotFound', array(
				'list' => $list,
			));
			$this->_logError($error);
			$this->_errorExport($error);
			return false;
		}

		// parse field mapping
		$map = $this->_getExportData('map');
		if (!$map) {
			$map = array();
		}
		else
		{
			$map = ISC_JSON::decode($map, true);
			if (!is_array($map)) {
				$error = GetLang('EmailIntegration_Log_JobInvalidFieldMapData');
				$this->_logError($error);
				$this->_errorExport($error);
				return false;
			}
		}
		$this->_map = $map;

		// parse search parameters
		$search = $this->_getExportData('search');
		if (!$search) {
			$search = array();
		}
		else
		{
			$search = ISC_JSON::decode($search, true);
			if (!is_array($search)) {
				$error = GetLang('EmailIntegration_Log_JobInvalidSearchParameters');
				$this->_logError($error);
				$this->_errorExport($error);
				return false;
			}

			if (isset($search['searchId']) && isId($search['searchId'])) {
				// simulate ISC_ADMIN_CUSTOMERS->CustomSearch if a searchId is specified
				$GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH'] = new ISC_ADMIN_CUSTOMSEARCH($searchtype);
				$search = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->LoadSearch($search['searchId']);
				if (!is_array($search)) {
					$error = GetLang('EmailIntegration_Log_SearchNotFound');
					$this->_logError($error);
					$this->_errorExport($error);
					return false;
				}

				$search = $search['searchvars'];
			}
		}
		$this->_search = $search;

		return true;
	}

	protected function _performCustomerExport()
	{
		if (!empty($this->_search)) {
			// there are search parameters, use them to filter customer data before exporting
			/** @var ISC_ADMIN_CUSTOMERS */
			$customerClass = GetClass('ISC_ADMIN_CUSTOMERS');
			$search = $customerClass->BuildWhereFromVars($this->_search);

			$where = ' WHERE 1=1 ' . $search['query'] . ' ';
			$join = $search['join'];

			unset($search);
		}
		else
		{
			$where = '';
			$join = '';
		}

		$query = 'SELECT /* Job_EmailIntegration_ModuleExport->_performCustomerExport */ /*:columns*/ FROM `[|PREFIX|]customers` ' . $join . ' ' . $where;

		if (!$this->_skip) {
			$count = $this->_db->FetchOne(str_replace('/*:columns*/', 'COUNT(*)', $query));
			$this->_log->LogSystemNotice(array('emailintegration', $this->_module->GetName()), GetLang('EmailIntegration_Log_JobCommencingCustomerExport', array(
				'count' => $count,
			)));
			$this->_keystore->set($this->_prefix . 'estimate', $count);
		}

		// no matter what the search specified, always order by id so new customers do not mess up the batch logic
		$query .= ' ORDER BY `customerid` LIMIT ';

		if ($this->_skip) {
			$query .= $this->_skip . ',';
		}

		$query .= self::BATCH_SIZE;

		$query = $this->_db->Query(str_replace('/*:columns*/', '`customerid`', $query));
		if (!$query) {
			$error = GetLang('EmailIntegration_Log_JobCustomerDatabaseError');
			$this->_logError($error);
			$this->_errorExport($error);
			return false;
		}

		Interspire_TimerStack::start();
		$batch = array();
		while ($row = $this->_db->Fetch($query))
		{
			$subscription = new Interspire_EmailIntegration_Subscription_Customer($row['customerid']);
			$subscription->setDoubleOptIn($this->_doubleOptin);
			$subscription->setUpdateExisting($this->_updateExisting);
			$batch[] = $subscription;
		}
		unset($subscription);

		if (!empty($batch)) {
			$this->_logDebug('generated ' . count($batch) . ' subscriptions in ' . number_format(Interspire_TimerStack::stop(), 3) . ' sec, beginning subscribeBatch call...');
			$this->_subscribeBatch($batch);
		} else {
			Interspire_TimerStack::stop();
		}

		return count($batch);
	}

	protected function _performOrderExport()
	{
		if (!empty($this->_search)) {
			// there are search parameters, use them to filter customer data before exporting
			/** @var ISC_ADMIN_ORDERS */
			$orderClass = GetClass('ISC_ADMIN_ORDERS');
			$search = $orderClass->BuildWhereFromVars($this->_search);

			$where = ' WHERE 1=1 ' . $search['query'] . ' ';
			$join = $search['join'];

			unset($search);
		}
		else
		{
			$where = '';
			$join = '';
		}

		$query = 'SELECT /* Job_EmailIntegration_ModuleExport->_performOrderExport */ /*:columns*/ FROM `[|PREFIX|]orders` ' . $join . ' ' . $where;

		if (!$this->_skip) {
			$count = $this->_db->FetchOne(str_replace('/*:columns*/', 'COUNT(*)', $query));
			$this->_log->LogSystemNotice(array('emailintegration', $this->_module->GetName()), GetLang('EmailIntegration_Log_JobCommencingOrderExport', array(
				'count' => $count,
			)));
			$this->_keystore->set($this->_prefix . 'estimate', $count);
		}

		// no matter what the search specified, always order by id so new customers do not mess up the batch logic
		$query .= ' ORDER BY `orderid` LIMIT ';

		if ($this->_skip) {
			$query .= $this->_skip . ',';
		}

		$query .= self::BATCH_SIZE;

		$query = $this->_db->Query(str_replace('/*:columns*/', '`orderid`', $query));
		if (!$query) {
			$error = GetLang('EmailIntegration_Log_JobOrderDatabaseError');
			$this->_logError($error);
			$this->_errorExport($error);
			return false;
		}

		$batch = array();
		while ($row = $this->_db->Fetch($query))
		{
			$subscription = new Interspire_EmailIntegration_Subscription_Order($row['orderid']);
			$subscription->setDoubleOptIn($this->_doubleOptin);
			$subscription->setUpdateExisting($this->_updateExisting);
			$batch[] = $subscription;
		}
		unset($subscription);

		if (!empty($batch)) {
			$this->_logDebug('batch subscribing ' . count($batch) . ' orders');
			$this->_subscribeBatch($batch);
		}

		return count($batch);
	}

	/**
	* @param array<Interspire_EmailIntegration_Subscription> $batch
	*/
	protected function _subscribeBatch($batch)
	{
		$results = $this->_module->addSubscribersToList($this->_list['provider_list_id'], $batch, $this->_map);

		if (!is_array($results)) {
			$error = GetLang('EmailIntegration_Log_JobCustomerBatchApiError');
			$this->_logError($error);
			$this->_errorExport($error);
			return false;
		}

		$successCount = 0;
		$errorCount = 0;
		$errorMessages = '';

		foreach ($results as /** @var Interspire_EmailIntegration_AddSubscriberResult */$result) {
			if ($result->success) {
				$successCount++;
			}
			else
			{
				$errorCount++;

				$message = $result->apiErrorMessage . ' (' . $result->apiErrorCode . ')';

				$error = array(
					'email' => $result->subscription->getSubscriptionEmail(),
					'time' => time(),
					'message' => $message,
				);

				$this->_keystore->set($this->_prefix . 'error:' . md5($result->subscription->getSubscriptionEmail() . uniqid('',true)), ISC_JSON::encode($error));

				$errorMessages .= GetLang('EmailIntegration_Log_BatchSubscribeError_MessageTemplate', array(
					'email' => $result->subscription->getSubscriptionEmail(),
					'error' => $message,
				)) . "\n";
			}
		}

		if ($errorMessages) {
			$this->_log->LogSystemError(array('emailintegration', $this->_module->GetName()), GetLang('EmailIntegration_Log_BatchSubscribeError', array(
				'errorcount' => $errorCount,
			)), nl2br($errorMessages));
		}

		$this->_log->LogSystemNotice(array('emailintegration', $this->_module->GetName()), GetLang('EmailIntegration_Log_BatchComplete', array(
			'start' => ($this->_skip + 1),
			'end' => ($this->_skip + count($batch)),
			'success_count' => $successCount,
			'error_count' => $errorCount,
		)));

		$this->_keystore->increment($this->_prefix . 'success_count', $successCount);
		$this->_keystore->increment($this->_prefix . 'error_count', $errorCount);

		return $results;
	}

	public function perform()
	{
		Interspire_TimerStack::start();

		if (!$this->_validateExport()) {
			$this->_logDebug('export validation failed');
			$this->_removeExport();
			Interspire_TimerStack::stop();
			return;
		}

		if ($this->_getExportData('pause')) {
			// not ideal to simply requeue the task, but this is for dev purposes only
			$this->_logDebug('job is paused, requeueing task');
			$this->_repeatExport();
			Interspire_TimerStack::stop();
			return;
		}

		$this->_logDebug('export ' . $this->_type . ' starting from row ' . $this->_skip . ' with batch size ' . self::BATCH_SIZE);

		$method = '_perform' . $this->_type . 'Export';
		$counter = $this->$method();

		$duration = Interspire_TimerStack::stop();

		if ($counter) {
			// track average time taken per item over batches sent
			// may be used in future to dynamically adjust the batch size to fit into pre-determined request times
			// can also be used to compare speeds between modules
			$samples = $this->_keystore->increment('email:module_export:' . $this->_module->GetId() . ':avg_item_samples');
			$peritem = $duration / $counter;
			if ($samples > 1) {
				$previous = (float)$this->_keystore->get('email:module_export:' . $this->_module->GetId() . ':avg_item');
				$peritem = $previous + (($peritem - $previous) / $samples);
			}
			$this->_keystore->set('email:module_export:' . $this->_module->GetId() . ':avg_item', $peritem);
		}

		if (!$counter) {
			// only repeat if the type-specific export actually exported something
			$this->_endExport();
			return;
		}

		$this->_keystore->increment($this->_prefix . 'skip', $counter);
		$this->_repeatExport();
	}
}
