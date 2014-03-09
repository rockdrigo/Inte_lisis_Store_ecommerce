<?php

class EMAILINTEGRATION_MAILCHIMP extends ISC_EMAILINTEGRATION
{
	/**
	* Storage for instances of the MCAPI - implemented as an array incase separate keys are used in the one request
	*
	* @var array
	*/
	protected static $_instances = array();

	public function __construct ()
	{
		parent::__construct();
		$this->SetName(GetLang($this->id . '_name'));

		Interspire_Event::bind('Interspire_EmailIntegration_MailChimp/error', array($this, 'onApiError'));
	}

	public function onApiError (Interspire_Event $event)
	{
		$data = $event->data;

		/** @var Interspire_EmailIntegration_MailChimp */
		$api = $data['api'];
		$method = $data['method'];
		$params = $data['params'];

		switch ($api->errorCode)
		{
			case 200:
				// invalid list id, we should remove it locally and notify admin
				// for mailchimp, the 'id' param always seems to be the list id that was sent in a list-related request
				$this->removeProviderLists(array($params['id']));
				break;
		}
	}

	public function SetCustomVars ()
	{
		parent::SetCustomVars();

		// for saving purposes only; the html form is a manual template
		$this->_variables['apikey'] = array(
			"name" => GetLang("MailChimpApiKey"),
			"type" => "textbox",
			"help" => GetLang('MailChimpApiKeyHelp'),
			"default" => "",
			"required" => true,
		);
	}

	public function getSettingsTemplate ()
	{
		return 'settings.emailintegration.mailchimp.tpl';
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
		return true;
	}

	public function getProviderLibClassName ()
	{
		return 'MailChimp';
	}

	public function getAvailableMailFormatPreferences ()
	{
		return array(
			Interspire_EmailIntegration_Subscription::FORMAT_PREF_HTML,
			Interspire_EmailIntegration_Subscription::FORMAT_PREF_TEXT,
			Interspire_EmailIntegration_Subscription::FORMAT_PREF_MOBILE,
		);
	}

	public function getDefaultMailFormatPreference ()
	{
		return Interspire_EmailIntegration_Subscription::FORMAT_PREF_HTML;
	}

	/**
	* Returns the list_fields.provider_field_id value that corresponds to the email field for this provider. This is defined as its own method as email addresses are typically used as unique identifiers for list subscribers.
	*
	* For mailchimp, this is always 'EMAIL' - it's not possibel to remove the EMAIL merge tag on mailchimp and it's not possible to save another field with the same tag.
	*
	* @return string
	*/
	public function getEmailProviderFieldId ()
	{
		return 'EMAIL';
	}

	public function getIpProviderFieldId ()
	{
		return 'OPTINIP';
	}

	public function updateSubscriptionIP ($email, $ip, $asynchronous = true)
	{
		if ($asynchronous)
		{
			Interspire_TaskManager::createTask('emailintegration', 'Job_EmailIntegration_UpdateSubscriptionIP', array(
				'module' => $this->GetId(),
				'email' => $email,
				'ip' => $ip,
			));
			return true;
		}

		// the only way to update the ip of a subscription I can find for mailchimp is to fetch existing details and send a full update back
		$api = $this->getApiInstance();

		$lists = $api->listsForEmail($email);
		if (!$lists)
		{
			return false;
		}

		foreach ($lists as $list)
		{
			$member = $api->listMemberInfo($list, $email);
			if (!$member)
			{
				return false;
			}

			$member['merges'][$this->getIpProviderFieldId()] = $ip;

			if (!$api->listSubscribe($list, $email, $member['merges'], $member['email_type'], false, true, false, false))
			{
				return false;
			}
		}

		return true;
	}

	/**
	* Returns an instance of the MCAPI configured with the provided key
	*
	* @param string $key
	* @return Interspire_EmailIntegration_MailChimp $key Use provided key, or leave as null to use configured key
	*/
	public function getApiInstance ($key = null)
	{
		// no key provided? get configured default
		if ($key === null) {
			$key = $this->GetValue('apikey');
		}

		// no key configured? fatal error
		if ($key === null) {
			throw new Exception("MailChimp API called with no key provided or configured.");
		}

		if (!isset(self::$_instances[$key])) {
			// no api instance for this key yet so create one
			$api = new Interspire_EmailIntegration_MailChimp($key, true);
			self::$_instances[$key] = $api;
		}

		return self::$_instances[$key];
	}

	public function remoteRefreshLists ($auth, $data)
	{
		$result = $this->downloadLists();
		if ($result === false)
		{
			return array(
				'success' => false,
				'message' => GetLang('MailChimpListsDownloadFailed'),
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

	/**
	* Verifies access to the api with the given authorisation details. This is primarily called from the 'verify api' buttons on the provider forms.
	*
	* @param array $auth
	* @return array Response data to send back to the browser
	*/
	public function remoteVerifyApi ($auth, $data)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		// ping service with ajax-supplied auth details
		$result = $this->pingService($auth);
		$key = $auth['key'];
		if ($result !== Interspire_EmailIntegration_MailChimp::PING_OK) {
			// failed
			$api = $this->getApiInstance($key);

			$replacements = array(
				'key' => $key,
				'errorCode' => $api->errorCode,
				'errorMessage' => $api->errorMessage,
			);

			$summary = GetLang('MailChimpApiVerifyFailed', $replacements);
			$message = GetLang('MailChimpApiVerifyFailedLog', $replacements);

			$log->LogSystemError(array('emailintegration', $this->GetName()), $summary, $message);
			return array('success' => false, 'message' => $summary);
		}

		// if details are good then store them
		$settings = array(
			'apikey' => $key,
			'isconfigured' => true,
		);

		$this->SaveModuleSettings($settings);
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateEmailIntegrationModuleVars();

		$replacements = array(
			'key' => $key,
		);

		$summary = GetLang('MailChimpApiVerified', $replacements);
		$message = GetLang('MailChimpApiVerifiedLog', $replacements);
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

	/**
	* Pings the service to see if it's up or verify API information
	*
	* @param mixed $auth
	* @return mixed
	*/
	public function pingService ($auth = null)
	{
		if (!$auth) {
			$key = null;
		} else {
			$key = $auth['key'];
		}

		return $this->getApiInstance($key)->ping();
	}

	/**
	* download list id/names from mailchimp and store locally
	*
	* @return array list cache array based on results from updateProviderLists, or false if an error occurred - store log will contain specifics
	*/
	public function downloadLists ()
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];
		$api = $this->getApiInstance();
		$lists = $api->lists();

		if ($api->errorCode) {
			$replacements = array(
				'errorCode' => $api->errorCode,
				'errorMessage' => $api->errorMessage,
			);
			$summary = GetLang('MailChimpListsDownloadFailed', $replacements);
			$message = GetLang('MailChimpListsDownloadFailedLog', $replacements);
			$log->LogSystemError(array('emailintegration', $this->GetName()), $summary, $message);
			return false;
		}

		// translate response from mailchimp into local list cache
		$cache = array();
		foreach ($lists as $list) {
			$cache[] = array(
				'provider_list_id' => $list['id'],
				'name' => $list['name'],
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

		$tags = $this->getApiInstance()->listMergeVars($list['provider_list_id']);
		if (!$tags) {
			return false;
		}

		$cache = array();
		foreach ($tags as $tag) {
			if ($tag['req']) {
				$required = 1;
			} else {
				$required = 0;
			}

			$cache[] = array(
				'provider_field_id' => $tag['tag'],
				'name' => $tag['name'],
				'type' => $tag['field_type'],
				'size' => $tag['size'],
				'required' => $required,
			);
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

		$result = $api->listMemberInfo($listId, $subscriber->getSubscriptionEmail());
		if (!is_array($result)) {
			return false;
		}

		$return = new Interspire_EmailIntegration_Subscription_Existing;
		$return->setSubscriptionEmail($result['email']);
		if (isset($result['ip_opt']) && $result['ip_opt']) {
			$return->setSubscriptionIP($result['ip_opt']);
		} else if (isset($result['ip_signup']) && $result['ip_signup']) {
			$return->setSubscriptionIP($result['ip_signup']);
		}
		$return->setSubscriptionData($result['merges']);

		return $return;
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
		if (empty($subscribers))
		{
			return array();
		}

		// for mailchimp, these can only be set per-batch so use the preferences from the first subscription
 		$doubleOptIn = $subscribers[0]->getDoubleOptIn();
		$updateExisting = $subscribers[0]->getUpdateExisting();

		// send batch to mailchimp, retrieve results
		$results = $this->getApiInstance()->listBatchSubscribe($listId, $this->getMergeData($subscribers, $listId, $fieldMap, true), $doubleOptIn, $updateExisting);

		if (!is_array($results))
		{
			// other error without individual batch item information
			return false;
		}

		// translate mailchimp results into module-neutral set of Interspire_EmailIntegration_AddSubscriberResult instances

		$addSubscriberResults = array();

		// work backwards through the batch
		$subscribers = array_reverse($subscribers);
		foreach ($subscribers as /** @var Interspire_EmailIntegration_Subscription */$subscription)
		{
			$result = new Interspire_EmailIntegration_AddSubscriberResult($this->GetId(), $listId, false, true, null);
			$result->subscription = $subscription;

			// check errors to see if this subscription's email address appears in the error list
			if (isset($results['errors']) && count($results['errors']))
			{
				foreach ($results['errors'] as $errorIndex => $error)
				{
					if ($error['row']['EMAIL'] == $subscription->getSubscriptionEmail())
					{
						// this subscription's email appears in the error list
						$result->success = false;
						$result->apiErrorCode = $error['code'];
						$result->apiErrorMessage = $error['message'];

						// remove this error from the batch so it's not detected twice (this combined with array_reverse above helps prevent false errors on duplicate subscriptions)
						unset($results['errors'][$errorIndex]);
						break;
					}
				}
			}

			$addSubscriberResults[] = $result;
		}

		return $addSubscriberResults;
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

		// fields contains a generic map, translate it to mailchimp merge format with real subscriber data
		$merge = $this->getMergeData($subscription, $list['provider_list_id'], $fieldMap);

		$api = $this->getApiInstance();

		$exists = $this->findListSubscriber($list['provider_list_id'], $subscription);
		if (is_array($exists)) {
			$exists = true;
		} else {
			$exists = false;
			if ($api->errorCode) {
				// problem, mailchimp?
				switch ($api->errorCode)
				{
					case 215: // List_NotSubscribed
					case 233: // Email_NotSubscribed
					case 232: // Email_NotExists
						// the mailchimp api actually produces an error when you try and get info about a non-existant member, so, we have to handle that
						break;

					default:
						// log all others
						$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationSubscriberAddFailed', array(
							'email' => $subscription->getSubscriptionEmail(),
							'provider' => $this->GetName(),
							'list' => $list['name'],
						)), $api->errorMessage);

						$this->notifyAdmin($subscription, $merge, $api->errorMessage);

						$result = new Interspire_EmailIntegration_AddSubscriberResult($this->GetId(), $listId, false, false, null);
						$result->subscription = $subscription;
						$result->apiErrorCode = $api->errorCode;
						$result->apiErrorMessage = $api->errorMessage;
						return $result;
				}
			}
		}

		$format = $this->getEmailFormat($subscription);

		if (!$subscription->getDoubleOptIn()) {
			// if no double opt-in is being sent, provide mailchimp with the IP that was stored when the subscription was created
			$merge[$this->getIpProviderFieldId()] = $subscription->getSubscriptionIP();
		}

		if (empty($merge)) {
			// http://www.mailchimp.com/api/1.2/listsubscribe.func.php - see note about blank arrays
			$merge[] = '';
		}

		$success = $api->listSubscribe($list['provider_list_id'], $subscription->getSubscriptionEmail(), $merge, $format, $subscription->getDoubleOptIn(), true, true, $subscription->getSendWelcome());

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
			)), $api->errorMessage);

			$this->notifyAdmin($subscription, $merge, $api->errorMessage);
		}

		$result = new Interspire_EmailIntegration_AddSubscriberResult($this->GetId(), $listId, false, $success, $exists);
		$result->subscription = $subscription;
		return $result;
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

		$exists = $this->findListSubscriber($list['provider_list_id'], $subscription);
		if (is_array($exists)) {
			$exists = true;
		} else {
			$exists = false;
		}

		if ($exists) {
			$api = $this->getApiInstance();
			$success = $api->listUnsubscribe($listId, $subscription->getSubscriptionEmail(), true, false, false);
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
			)), $api->errorMessage);
		}

		return new Interspire_EmailIntegration_RemoveSubscriberResult($this->GetId(), $listId, false, $success, $exists);
	}

	/**
	* Return mail format string to send to mailchimp based on subscription object
	*
	* @param Interspire_EmailIntegration_Subscription $subscription
	* @return string
	*/
	protected function getEmailFormat (Interspire_EmailIntegration_Subscription $subscription)
	{
		switch ($subscription->getEmailFormatPreference()) {
			case Interspire_EmailIntegration_Subscription::FORMAT_PREF_MOBILE:
				return 'mobile';

			case Interspire_EmailIntegration_Subscription::FORMAT_PREF_TEXT:
				return 'text';

			default:
				return 'html';
		}
	}
}
