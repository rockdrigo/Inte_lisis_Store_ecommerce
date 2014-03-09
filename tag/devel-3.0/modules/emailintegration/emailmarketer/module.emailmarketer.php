<?php

class EMAILINTEGRATION_EMAILMARKETER extends ISC_EMAILINTEGRATION
{
	/**
	* Storage for instances of Interspire_EmailIntegration_EmailMarketer - implemented as an array incase separate keys are used in the one request
	*
	* @var array
	*/
	protected static $_instances = array();

	public function __construct ()
	{
		parent::__construct();
		$this->SetName(GetLang('EmailMarketerModuleName'));
	}

	/**
	* Returns an instance of the EmailMarketer API configured with the provided auth details or, if no details are provided, using the built-in values
	*
	* @param string $url
	* @param string $username
	* @param string $usertoken
	* @return Interspire_EmailIntegration_EmailMarketer
	*/
	public function getApiInstance ($url = null, $username = null, $usertoken = null)
	{
		if ($url === null) {
			$url = $this->GetValue('url');
		}

		if ($username === null) {
			$username = $this->GetValue('username');
		}

		if ($usertoken === null) {
			$usertoken = $this->GetValue('usertoken');
		}

		if (!$url || !$username || !$usertoken) {
			// this shouldn't happen, calls to getApiInstance should check for valid details first
			throw new Exception("EmailMarketer API called without complete auth details either provided or configured.");
		}

		$hash = md5($url.$username.$usertoken);

		if (!isset(self::$_instances[$hash])) {
			// no api instance for these details yet so create one
			$api = new Interspire_EmailIntegration_EmailMarketer($url, $username, $usertoken);
			self::$_instances[$hash] = $api;
		}

		return self::$_instances[$hash];
	}

	/**
	* Pings the service to see if it's up or verify API information
	*
	* @param array $auth auth details to ping with otherwise leave as null to try using configured values
	* @return Interspire_EmailIntegration_EmailMarketer_XmlApiResponse
	*/
	public function pingService ($auth = null)
	{
		if ($auth === null) {
			$api = $this->getApiInstance();
		} else {
			$api = $this->getApiInstance($auth['url'], $auth['username'], $auth['usertoken']);
		}

		return $api->xmlApiTest();
	}

	public function remoteVerifyApi ($auth, $data)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		try {
			$response = $this->pingService($auth);
		} catch (Interspire_EmailIntegration_EmailMarketer_Exception $exception) {
			return array(
				'success' => false,
				'message' => GetLang(get_class($exception) . '_Message'),
			);
		}

		if (!$response->isSuccess()) {
			return array(
				'success' => false,
				'message' => GetLang('EmailMarketerApiVerifyFailed', array(
					'error' => $response->getErrorMessage()
				)),
			);
		}

		$settings = array(
			'url' => $auth['url'],
			'username' => $auth['username'],
			'usertoken' => $auth['usertoken'],
			'isconfigured' => true,
		);

		$this->SaveModuleSettings($settings);
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateEmailIntegrationModuleVars();

		$replacements = $auth;
		$summary = GetLang('EmailMarketerApiVerified', $replacements);
		$message = GetLang('EmailMarketerApiVerifiedLog', $replacements);
		$log->LogSystemSuccess(array('emailintegration', $this->GetName()), $message);

		// build a response containing success info and a refresh of the rules
		$return = array(
			'success' => true,
			'message' => $summary,
		);

		$this->downloadLists();

		$template = Interspire_Template::getInstance('admin');

		// 'module' has to be compatible with the result of GetAvailableModules, which isn't an instance of the module class but an array
		$mod_details = array (
			"id" => $this->GetId(),
			"name" => $this->GetName(),
			"enabled" => $this->IsEnabled(),
			"object" => $this,
		);
		$template->assign('module', $mod_details);

		$return['newsletterRules'] = $template->render('settings.emailintegration.newsletterrules.tpl');
		$return['customerRules'] = $template->render('settings.emailintegration.customerrules.tpl');

		return $return;
	}

	public function downloadLists ()
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];
		$api = $this->getApiInstance();

		try {
			$response = $api->getLists();
		} catch (Interspire_EmailIntegration_EmailMarketer_Exception $exception) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang(get_class($exception) . '_Message'));
			return false;
		}

		if (!$response->isSuccess()) {
			$replacements = array(
				'errorMessage' => $lists->getErrorMessage(),
			);
			$summary = GetLang('EmailMarketerListsDownloadFailed', $replacements);
			$message = GetLang('EmailMarketerListsDownloadFailedLog', $replacements);
			$log->LogSystemError(array('emailintegration', $this->GetName()), $summary, $message);
			return false;
		}

		// translate response from mailchimp into local list cache
		$cache = array();
		foreach ($response->getData()->children() as /** @var SimpleXMLElement */$list) {
			$cache[] = array(
				'provider_list_id' => (string)$list->listid,
				'name' => (string)$list->name,
			);
		}

		$log->LogSystemDebug(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationListsDownloaded', array(
			'count' => count($cache),
			'provider' => $this->GetName(),
		)));

		$this->SaveModuleSettings(array('last_list_update' => time()));
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateEmailIntegrationModuleVars();

		return $this->updateProviderLists($cache);
	}

	/**
	* Fetch list fields from provider
	*
	* @param mixed $listId
	* @return array or false on error
	*/
	public function downloadListFields ($listId)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		$listId = $listId;
		$list = $db->FetchRow("SELECT * FROM `[|PREFIX|]email_provider_lists` WHERE `provider_list_id` = '" . $db->Quote($listId) . "'");
		if (!$list) {
			return false;
		}

		try {
			$fields = $this->getApiInstance()->getCustomFields($list['provider_list_id']);
		} catch (Interspire_EmailIntegration_EmailMarketer_Exception $exception) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang(get_class($exception) . '_Message'));
			return false;
		}

		if (!$fields->isSuccess()) {
			return false;
		}

		$cache = array();
		foreach ($fields->getData()->children() as $field) {
			if ((int)$field->required) {
				$required = 1;
			} else {
				$required = 0;
			}

			$settings = @unserialize((string)$field->fieldsettings);
			if ($settings === false) {
				$settings = array();
			}

			$size = '';
			if (isset($settings['MaxLength']) && $settings['MaxLength']) {
				$size = $settings['MaxLength'];
			}

			$providerField = array(
				'provider_field_id' => (string)$field->fieldid,
				'name' => (string)$field->name,
				'type' => (string)$field->fieldtype,
				'size' => $size,
				'required' => $required,
				'settings' => serialize($settings), // unserializing and reserializing acts as a form of validation for this incoming data just incase it ever changes or is corrupt
			);

			$cache[] = $providerField;
		}

		$log->LogSystemDebug(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationListFieldsDownloaded', array(
			'count' => count($cache),
			'provider' => $this->GetName(),
		)));

		return $this->updateProviderListFields($list['provider_list_id'], $cache);
	}

	/**
	* Find and return information about a subscriber for a list
	*
	* @param string $listId
	* @param Interspire_EmailIntegration_Subscription $subscriber
	* @return Interspire_EmailIntegration_Subscription_Existing or false if the subscriber was not found
	*/
	public function findListSubscriber ($listId, Interspire_EmailIntegration_Subscription $subscriber)
	{
		$api = $this->getApiInstance();

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		try {
			$result = $api->getSubscribers($listId, array(
				'Email' => $subscriber->getSubscriptionEmail(),
			));

			if (!$result || !$result->isSuccess()) {
				return false;
			}
		} catch (Interspire_EmailIntegration_EmailMarketer_Exception $exception) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang(get_class($exception) . '_Message'));
			return false;
		}

		$result = $result->getData()->subscriberlist->children();
		if (empty($result)) {
			return false;
		}

		$result = $result[0];
		$subscriberId = (string)$result->subscriberid;

		$return = new Interspire_EmailIntegration_Subscription_Existing;
		$return->setSubscriptionEmail($result->emailaddress);

		// try and find custom field information to add to the subscription data -- as far as I can tell this is the only way of doing it with IEM
		$listFields = $this->getListFields($listId);
		$customFieldIds = array();
		foreach ($listFields as $listField) {
			$customFieldIds[] = $listField['provider_field_id'];
		}
		unset($listFields);

		$data = array();
		$result = $api->getAllSubscriberCustomFields($listId, array(), $subscriberId, $customFieldIds);
		if ($result && $result->isSuccess()) {
			foreach ($result->getData()->item->children() as /** @var SimpleXMLElement */$field) {
				$data[(string)$field->fieldid] = (string)$field->data;
			}
		}

		$return->setSubscriptionData($data);

		return $return;
	}

	/**
	* Add a single subscriber to a list, either immediately or via delayed task.
	*
	* @param string $listId email_provider_lists.provider_list_id
	* @param Interspire_EmailIntegration_Subscription $subscriber
	* @param array $fieldMap Associative array to control field mapping - supply provider field ids as keys, and subscriber fields as values
	* @param bool $asynchronous If true (default) will schedule the add for later, otherwise will attempt to add immediately
	* @return Interspire_EmailIntegration_AddSubscriberResult
	*/
	public function addSubscriberToList ($listId, Interspire_EmailIntegration_Subscription $subscription, $fieldMap, $asynchronous = true)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		if ($asynchronous) {
			// queue job for later and return immediately
			Interspire_TaskManager::createTask('emailintegration', 'Job_EmailIntegration_AddSubscriberToList', array(
				'module' => str_replace('emailintegration_', '', $this->GetId()),
				'listId' => $listId,
				'subscription' => serialize($subscription),
				'fields' => $fieldMap,
			));
			$result = new Interspire_EmailIntegration_AddSubscriberResult($this->GetId(), $listId, true);
			$result->subscription = $subscription;
			return $result;
		}

		// run immediately
		$list = $this->getList($listId);
		if (!$list) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationAddSubscriberListDoesntExist', array(
				'email' => $subscription->getSubscriptionEmail(),
				'provider' => $this->GetName(),
				'list' => $listId,
			)));
			$result = new Interspire_EmailIntegration_AddSubscriberResult($this->GetId(), $listId, false, false);
			$result->subscription = $subscription;
			return $result;
		}

		// fields contains a generic map, translate it to IEM merge format with real subscriber data
		$merge = $this->getMergeData($subscription, $list['provider_list_id'], $fieldMap);

		$api = $this->getApiInstance();

		$exists = $api->isSubscriberOnList($subscription->getSubscriptionEmail(), $listId);
		if ($exists && $exists->isSuccess() && (string)$exists->getData()) {
			$exists = true;
		} else {
			$exists = false;
		}

		switch ($subscription->getEmailFormatPreference()) {
			case Interspire_EmailIntegration_Subscription::FORMAT_PREF_TEXT:
				$format = 'text';
				break;
			default:
				$format = 'html';
		}

		$error = '';
		$responseBody = '';
		try {
			$result = $api->addSubscriberToList($subscription->getSubscriptionEmail(), $list['provider_list_id'], $format, !$subscription->getDoubleOptIn(), $merge);
			$success = $result && $result->isSuccess();
			if (!$success && $result) {
				$error = $result->getErrorMessage();
				$responseBody = $result->getResponseBody();
			}
		} catch (Interspire_EmailIntegration_EmailMarketer_Exception $exception) {
			$success = false;
			$error = GetLang(get_class($exception) . '_Message');
		}

		$addSubscriberResult = new Interspire_EmailIntegration_AddSubscriberResult($this->GetId(), $list['provider_list_id'], false, $success, $exists);
		$addSubscriberResult->subscription = $subscription;

		if ($success) {
			if ($exists) {
				$log->LogSystemSuccess(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationSubscriberAddUpdated', array(
					'email' => $subscription->getSubscriptionEmail(),
					'provider' => $this->GetName(),
					'list' => $list['name'],
				)));
			} else {
				$log->LogSystemSuccess(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationSubscriberAdded', array(
					'email' => $subscription->getSubscriptionEmail(),
					'provider' => $this->GetName(),
					'list' => $list['name'],
				)));
			}
		} else {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationSubscriberAddFailed', array(
				'email' => $subscription->getSubscriptionEmail(),
				'provider' => $this->GetName(),
				'list' => $list['name'],
			)), $error);

			$this->notifyAdmin($subscription, $merge, $error);

			$addSubscriberResult->apiErrorMessage = $error;
			$addSubscriberResult->apiResponseBody = $responseBody;
		}

		return $addSubscriberResult;
	}

	/**
	* Adds a batch of subscribers to a specific list for this provider (currently only used by export jobs, so not available in asynchronous form)
	*
	* @param string $listId provider list id
	* @param array<Interspire_EmailIntegration_Subscription> $subscribers an array of Interspire_EmailIntegration_Subscription instances
	* @param array $fieldMap field map to apply to each subscription
	* @return array<Interspire_EmailIntegration_AddSubscriberResult> or false on error or not supported
	*/
	public function addSubscribersToList ($listId, $subscribers, $fieldMap)
	{
		// the IEM XML API does not have a batch method so each one must be done via individual add calls
		// this is not ideal or efficient, and may be very slow if the IEM installation is remote from the ISC installation - requires usage testing
		// note: do not use $this->addSubscriberToList because it will attempt to validate the list id and send notifications for each call when this should only happen once per batch

		$api = $this->getApiInstance();
		$list = $this->getList($listId);
		if (!$list) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationAddSubscriberListDoesntExist', array(
				'email' => $subscription->getSubscriptionEmail(),
				'provider' => $this->GetName(),
				'list' => $listId,
			)));
			return false;
		}

		$results = array();

		foreach ($subscribers as /** @var Interspire_EmailIntegration_Subscription */$subscription) {
			// fields contains a generic map, translate it to IEM merge format with real subscriber data
			$merge = $this->getMergeData($subscription, $list['provider_list_id'], $fieldMap);

			switch ($subscription->getEmailFormatPreference()) {
				case Interspire_EmailIntegration_Subscription::FORMAT_PREF_TEXT:
					$format = 'text';
					break;
				default:
					$format = 'html';
			}

			$error = '';
			try {
				$result = $api->addSubscriberToList($subscription->getSubscriptionEmail(), $list['provider_list_id'], $format, !$subscription->getDoubleOptIn(), $merge);
				$success = $result && $result->isSuccess();
				if (!$success && $result) {
					$error = $result->getErrorMessage();
				}
			} catch (Interspire_EmailIntegration_EmailMarketer_Exception $exception) {
				$success = false;
				$error = GetLang(get_class($exception) . '_Message');
			}

			$addSubscriberResult = new Interspire_EmailIntegration_AddSubscriberResult($this->GetId(), $list['provider_list_id'], false, $success);
			$addSubscriberResult->subscription = $subscription;
			if (!$success && $result) {
				$addSubscriberResult->apiErrorMessage = $result->getErrorMessage();
				$addSubscriberResult->apiResponseBody = $result->getResponseBody();
			}
			$results[] = $addSubscriberResult;
		}

		return $results;
	}

	/**
	* Removes a subscriber from a specific list for this provider
	*
	* @param mixed $listId
	* @param Interspire_EmailIntegration_Subscription $subscription
	* @param bool $asynchronous
	* @return Interspire_EmailIntegration_RemoveSubscriberResult
	*/
	public function removeSubscriberFromList ($listId, Interspire_EmailIntegration_Subscription $subscription, $asynchronous = true)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		if ($asynchronous) {
			// queue job for later and return immediately
			Interspire_TaskManager::createTask('emailintegration', 'Job_EmailIntegration_RemoveSubscriberFromList', array(
				'module' => str_replace('emailintegration_', '', $this->GetId()),
				'listId' => $listId,
				'email' => $subscription->getSubscriptionEmail(),
			));
			return new Interspire_EmailIntegration_RemoveSubscriberResult($this->GetId(), $listId, true);
		}

		// run immediately
		$list = $this->getList($listId);
		if (!$list) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationRemoveSubscriberListDoesntExist', array(
				'email' => $subscription->getSubscriptionEmail(),
				'provider' => $this->GetName(),
				'list' => $listId,
			)));
			return new Interspire_EmailIntegration_RemoveSubscriberResult($this->GetId(), $listId, false, false);
		}

		$api = $this->getApiInstance();

		$exists = $api->isSubscriberOnList($subscription->getSubscriptionEmail(), $listId);
		if ($exists && $exists->isSuccess() && (string)$exists->getData()) {
			$exists = true;
		} else {
			$exists = false;
		}

		if ($exists) {
			try {
				$result = $api->deleteSubscriber($listId, $subscription->getSubscriptionEmail());
				$success = $result->isSuccess();
			} catch (Interspire_EmailIntegration_EmailMarketer_Exception $exception) {
				$error = GetLang(get_class($exception) . '_Message');
				$log->LogSystemError(array('emailintegration', $this->GetName()), $error);
				$success = false;
			}
		} else {
			$success = true;
		}

		if ($success) {
			if ($exists) {
				$log->LogSystemSuccess(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationSubscriberRemoved', array(
					'email' => $subscription->getSubscriptionEmail(),
					'provider' => $this->GetName(),
					'list' => $list['name'],
				)));
			} else {
				$log->LogSystemDebug(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationRemoveSubscriberDoesntExist', array(
					'email' => $subscription->getSubscriptionEmail(),
					'provider' => $this->GetName(),
					'list' => $list['name'],
				)));
			}
		} else {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationSubscriberRemoveFailed', array(
				'email' => $subscription->getSubscriptionEmail(),
				'provider' => $this->GetName(),
				'list' => $list['name'],
			)), $error);
		}

		return new Interspire_EmailIntegration_RemoveSubscriberResult($this->GetId(), $listId, false, $success, $exists);
	}

	public function remoteRefreshLists ($auth, $data)
	{
		$result = $this->downloadLists();
		if ($result === false)
		{
			return array(
				'success' => false,
				'message' => GetLang('EmailMarketerListsDownloadFailed'),
			);
		}

		// build a response containing success info and a refresh of the rules
		$return = array(
			'success' => true,
			'message' => GetLang('EmailIntegrationListsRefreshed', array(
				'provider' => $this->GetName(),
			)),
		);

		$template = Interspire_Template::getInstance('admin');

		// 'module' has to be compatible with the result of GetAvailableModules, which isn't an instance of the module class but an array
		$mod_details = array (
			"id" => $this->GetId(),
			"name" => $this->GetName(),
			"enabled" => $this->IsEnabled(),
			"object" => $this,
		);
		$template->assign('module', $mod_details);

		$return['newsletterRules'] = $template->render('settings.emailintegration.newsletterrules.tpl');
		$return['customerRules'] = $template->render('settings.emailintegration.customerrules.tpl');

		return $return;
	}

	public function getEmailProviderFieldId ()
	{
		// not used by IEM, email is not shown in custom field lists via XML API
		return false;
	}

	public function getIpProviderFieldId ()
	{
		// not used by IEM, ip is not shown in custom field lists via XML API
		return false;
	}

	public function updateSubscriptionIP ($email, $ip, $asynchronous = true)
	{
		if ($asynchronous) {
			// queue job for later and return immediately
			Interspire_TaskManager::createTask('emailintegration', 'Job_EmailIntegration_UpdateSubscriptionIP', array(
				'module' => $this->GetId(),
				'email' => $email,
				'ip' => $ip,
			));
			return true;
		}

		$api = $this->getApiInstance();

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		try {
			// get lists to update for this subscriber
			$lists = $api->getAllListsForEmailAddress($email);
			if (!$lists || !$lists->isSuccess()) {
				return false;
			}

			// update this subscriber's ip address on all lists
			foreach ($lists->getData()->children() as $list) {
				$result = $api->updateSubscriberIP($email, (string)$list->listid, $ip);
				if (!$result || !$result->isSuccess()) {
					return false;
				}
			}
		} catch (Interspire_EmailIntegration_EmailMarketer_Exception $exception) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang(get_class($exception) . '_Message'));
			return false;
		}

		return true;
	}

	public function SetCustomVars ()
	{
		parent::SetCustomVars();

		// for saving purposes only; the html form is a manual template
		$this->_variables['url'] = array(
			"name" => '',
			"type" => "textbox",
			"help" => '',
			"default" => "",
			"required" => true,
		);

		$this->_variables['username'] = $this->_variables['url'];
		$this->_variables['usertoken'] = $this->_variables['url'];
	}

	public function getSettingsTemplate ()
	{
		return 'settings.emailintegration.emailmarketer.tpl';
	}

	public function getSettingsJavaScript ()
	{
		return 'manage.js';
	}

	public function isSelectable ()
	{
		return true;
	}

	public function supportsBulkExport ()
	{
		return true;
	}

	public function supportsSubscriberUpdates ()
	{
		return false;
	}

	public function getAvailableMailFormatPreferences ()
	{
		return array(
			Interspire_EmailIntegration_Subscription::FORMAT_PREF_HTML,
			Interspire_EmailIntegration_Subscription::FORMAT_PREF_TEXT,
		);
	}

	public function getDefaultMailFormatPreference ()
	{
		return Interspire_EmailIntegration_Subscription::FORMAT_PREF_HTML;
	}

	public function getProviderLibClassName ()
	{
		return 'EmailMarketer';
	}
}
