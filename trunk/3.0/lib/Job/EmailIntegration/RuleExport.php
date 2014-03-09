<?php

class Job_EmailIntegration_RuleExport extends Job_EmailIntegration
{
	/** @var int export this many records per job */
	const BATCH_SIZE = 200;

	/** @var mysqldb local copy of store db instance */
	protected $_db;

	/** @var Interspire_KeyStore local copy of Interspire_KeyStore::instance for convenience */
	protected $_keystore;

	/** @var string the prefix for data for this job in the keystore */
	protected $_prefix;

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

	/** @var string rule class that the export should be routing subscriptions through */
	protected $_rule;

	public function setUp()
	{
		parent::setUp();
		$this->_keystore = Interspire_KeyStore::instance();
		$this->_prefix = 'email:rule_export:' . $this->args['id'] . ':';
		$this->_db = $GLOBALS['ISC_CLASS_DB'];
	}

	/** @return bool true if this export job exists, otherwise false */
	protected function _exportExists()
	{
		return $this->_keystore->exists('email:rule_export:id:' . $this->args['id']);
	}

	/**
	* removes all data for this export job from the keystore
	*
	* @return void
	*/
	protected function _removeExport()
	{
		$this->_logDebug('remove data');
		$this->_keystore->multiDelete('email:rule_export:' . $this->args['id'] . ':*');
		$this->_keystore->delete('email:rule_export:id:' . $this->args['id']);
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
			'success_count' => (int)$this->_getExportData('success_count'),
			'error_count' => (int)$this->_getExportData('error_count'),
			'start' => isc_date(GetConfig('ExtendedDisplayDateFormat'), $started),
			'end' => isc_date(GetConfig('ExtendedDisplayDateFormat'), $finished),
		);

		$replacements['total'] = $replacements['success_count'] + $replacements['error_count'];

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
			$GLOBALS['EmailIntegration_Export_End_Email_Errors_Heading'] = GetLang('EmailIntegration_Export_End_Email_Errors_Heading', $replacements);

			$dl = new Xhtml_Dl();

			$errors = $this->_keystore->multiGet($this->_prefix . 'error:*');
			while ($error = array_shift($errors)) {
				$error = ISC_JSON::decode($error, true);
				$dl->appendChild(new Xhtml_Dt($error['row']['EMAIL']));

				$dd = new Xhtml_Dd($error['message']);
				$dd->attribute('style', 'margin-bottom:0.8em;');

				$dl->appendChild($dd);
			}

			$GLOBALS['EmailIntegration_Export_End_Email_Errors'] = $dl->render();
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
	* validates all data for this job, setting up ...
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
			case 'Order':
				break;

			default:
				$error = GetLang('EmailIntegration_Log_InvalidExportType', array(
					'type' => $this->_type,
				));
				$this->_logError($error);
				$this->_errorExport($error);
				return false;
		}

		// check if the export is pointing to a valid rule
		$this->_rule = $this->_getExportData('rule');
		if (!class_exists('Interspire_EmailIntegration_Rule_' . $this->_rule)) {
			$error = GetLang('EmailIntegration_Log_InvalidExportRule', array(
				'rule' => $this->_rule,
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
				$GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH'] = new ISC_ADMIN_CUSTOMSEARCH('customers');
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

		$query = 'SELECT /*:columns*/ FROM `[|PREFIX|]customers` ' . $join . ' ' . $where;

		if (!$this->_skip) {
			$count = $this->_db->FetchOne(str_replace('/*:columns*/', 'COUNT(*)', $query));
			$this->_log->LogSystemNotice('emailintegration', GetLang('EmailIntegration_Log_JobCommencingCustomerExport', array(
				'count' => $count,
			)));
			$this->_keystore->set($this->_prefix . 'estimate', $count);
		}

		// no matter what the search specified, always order by id so new customers do not mess up the batch logic
		$query .= ' ORDER BY customerid LIMIT ';

		if ($this->_skip) {
			$query .= $this->_skip . ',';
		}

		$query .= self::BATCH_SIZE;

		$query = $this->_db->Query(str_replace('/*:columns*/', '`customerid`,`custconemail`,`custconfirstname`', $query));
		if (!$query) {
			$error = GetLang('EmailIntegration_Log_JobCustomerDatabaseError');
			$this->_logError($error);
			$this->_errorExport($error);
			return false;
		}

		// currently the only possible routing for existing customers is via newsletter subscription rules so we don't need to check $this->_rule here and we don't need to perform field mapping because it's always based on email and first-name

		$subscriptions = array();
		while ($row = $this->_db->Fetch($query))
		{
			$subscriptions[] = new Interspire_EmailIntegration_Subscription_Newsletter($row['custconemail'], $row['custconfirstname']);
		}

		ISC_EMAILINTEGRATION::routeSubscriptions('onNewsletterSubscribed', $subscriptions, false);

		return count($subscriptions);
	}

	protected function _performOrderExport()
	{
		sleep(3);
		return true;
	}

	public function perform()
	{
		if (!$this->_validateExport()) {
			$this->_logDebug('export validation failed');
			$this->_removeExport();
			return;
		}

		$this->_logDebug('export ' . $this->_type . ' via ' . $this->_rule . ' starting from row ' . $this->_skip . ' with batch size ' . self::BATCH_SIZE);

		$method = '_perform' . $this->_type . 'Export';
		$counter = $this->$method();
		if (!$counter) {
			// only repeat if the type-specific export actually exported something
			$this->_endExport();
			return;
		}

		$this->_keystore->increment($this->_prefix . 'skip', $counter);
		$this->_repeatExport();
	}
}
