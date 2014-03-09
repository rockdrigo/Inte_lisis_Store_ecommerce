<?php

require_once(dirname(__FILE__).'/class.module.php');

/**
* The Interspire Shopping Cart notification base class, used by all notification modules
*/
abstract class ISC_EMAILINTEGRATION extends ISC_MODULE
{
	/**
	* Time in seconds to keep downloaded lists before checking again for updates
	*
	* @var int
	*/
	const GC_MAX_LIST_AGE = 86400; // 24 hours

	/**
	* Time in seconds to keep downloaded fields before checking again for updates
	*
	* @var int
	*/
	const GC_MAX_FIELD_AGE = 86400; // 24 hours

	const LIST_NAME_MAX_LEN = 200; // utf-8 varchar(200)

	/**
	* Storage for integration rules for this module
	*
	* @var array An array of Interspire_EmailIntegration_Rule instances - this property starts as null however, until populated by calling getRules()
	*/
	protected static $_rules = null;

	/**
	* Storage for module lists
	*
	* @var array An array of objects which extend ISC_EMAILINTEGRATION
	*/
	protected static $_enabledModules = null;

	/**
	* Storage for module lists
	*
	* @var array An array of objects which extend ISC_EMAILINTEGRATION
	*/
	protected static $_configuredModules = null;

	/**
	* The type of module this is
	*
	* @var string
	*/
	protected $type = 'emailintegration';

	/**
	* Flushes the cached values for enabled / configured modules.
	*
	* @return void
	*/
	public static function flushEnabledModuleCache ()
	{
		self::$_enabledModules = null;
		self::$_configuredModules = null;
	}

	/**
	* Returns a list of enabled and configured email integration modules, or an empty array if none are enabled and configured
	*
	* @return array An array of objects which extend ISC_EMAILINTEGRATION
	*/
	public static function getConfiguredModules()
	{
		if (self::$_configuredModules === null) {
			$configuredModules = array();
			$enabledModules = self::getEnabledModules();
			foreach ($enabledModules as $module) {
				if ($module->isConfigured()) {
					$configuredModules[] = $module;
				}
			}
			self::$_configuredModules = $configuredModules;
		}
		return self::$_configuredModules;
	}

	/**
	* Returns a list of ids of enabled modules (safe to call multiple times as the result is cached)
	*
	* @return array<ISC_EMAILINTEGRATION> An array of email integration module instances
	*/
	public static function getEnabledModules()
	{
		if (self::$_enabledModules === null) {
			// split the methods defined in config into an array, or empty array if none defined
			$methods = trim(GetConfig('EmailIntegrationMethods'));
			if ($methods) {
				self::$_enabledModules = explode(',', $methods);
			} else {
				self::$_enabledModules = array();
			}

			// always include exportonly module in 'enabled' modules
			if (!in_array('exportonly', self::$_enabledModules)) {
				self::$_enabledModules[] = 'exportonly';
			}

			// replace strings with actual module objects
			foreach (self::$_enabledModules as $index => $moduleId) {
				if (!$moduleId) {
					unset(self::$_enabledModules[$index]);
					continue;
				}

				GetModuleById('emailintegration', /** @var ISC_EMAILINTEGRATION */$module, $moduleId);
				self::$_enabledModules[$index] = $module;
			}

			usort(self::$_enabledModules, array('ISC_MODULE', 'moduleSortByNameCallback'));
		}
		return self::$_enabledModules;
	}

	/**
	* Remove any cached rules so rules are reloaded on next call to getRules()
	*
	* @return void
	*/
	public static function flushRules()
	{
		self::$_rules = null;
	}

	/**
	* Returns a list of configured email integration routing rules
	*
	* @param string $eventId Optional, if provided, will only return rules that match the given eventId
	* @return array An array of Interspire_EmailIntegration_Rule if an empty array if no rules were configured
	*/
	public static function getRules($eventId = null)
	{
		if (self::$_rules === null) {
			// note: this loads all rules because it's a left-over from when rules were stored serialized in the store config

			/** @var mysqldb */
			$db = $GLOBALS['ISC_CLASS_DB'];
			$result = $db->Query("SELECT * FROM `[|PREFIX|]email_rules`");
			if (!$result) {
				return array();
			}

			self::$_rules = array();
			while ($row = $db->Fetch($result)) {
				$rule = Interspire_EmailIntegration_Rule::fromDatabaseRow($row);
				GetModuleById('emailintegration', /** @var ISC_EMAILINTEGRATION */$module, $rule->moduleId);
				if (!$module || !$module->IsEnabled() || !$module->isConfigured()) {
					// ignore rules for modules that doesn't exist / are not enabled / are not configured
					continue;
				}
				self::$_rules[] = $rule;
			}
		}

		if ($eventId === null) {
			return self::$_rules;
		}

		$rules = array();
		foreach (self::$_rules as /** @var Interspire_EmailIntegration_Rule */$rule) {
			if ($rule->eventId == $eventId) {
				$rules[] = $rule;
			}
		}

		if ($eventId == 'onNewsletterSubscribed' && empty($rules)) {
			// exception: if the event is newsletter subscription, and no rules are configured, provide a built-in rule that routes it to the export only module
			$rules[] = self::getBuiltInExportOnlyRule();
		}

		return $rules;
	}

	public static function getBuiltInExportOnlyRule()
	{
		return new Interspire_EmailIntegration_Rule_NewsletterSubscribed(null, 'exportonly', Interspire_EmailIntegration_Rule::ACTION_ADD, 1, array(
			'subfirstname' => 'subfirstname',
		));
	}

	/**
	* Returns a list of configured email integration routing rules for this module
	*
	* @param string $eventId Optional, if provided, will only return rules that match the given eventId
	* @return array An array of Interspire_EmailIntegration_Rule if an empty array if no rules were configured
	*/
	public function getModuleRules($eventId = null)
	{
		$allRules = self::getRules($eventId);

		$rules = array();
		foreach ($allRules as /** @var Interspire_EmailIntegration_Rule */$rule) {
			if ($rule->moduleId != $this->GetId()) {
				continue;
			}
			$rules[] = $rule;
		}
		return $rules;
	}

	/**
	* Returns a list of configured email integration routing rules for this module for the 'newsletter subscribed' event
	*
	* @return array An array of Interspire_EmailIntegration_Rule if an empty array if no rules were configured
	*/
	public function getNewsletterSubscribedRules()
	{
		return $this->getModuleRules('onNewsletterSubscribed');
	}

	/**
	* Returns a list of configured email integration routing rules for this module for the 'order completed' event
	*
	* @param bool $lookup Also look up category, brand, product names and place them in field orderCriteriaName - NOTE: if no match is found the rule will be dropped from this method's return
	* @return array An array of Interspire_EmailIntegration_Rule if an empty array if no rules were configured
	*/
	public function getOrderCompletedRules($lookup = true)
	{
		$rules = $this->getModuleRules('onOrderCompleted');

		if ($lookup) {
			// perform database lookups to also supply brand, category and product names
			foreach ($rules as $index => /** @var Interspire_EmailIntegration_Rule */$rule) {
				if (!isset($rule->eventCriteria['orderType'])) {
					continue;
				}

				/** @var mysqldb */
				$db = $GLOBALS['ISC_CLASS_DB'];
				$name = '';

				switch ($rule->eventCriteria['orderType']) {
					case 'category':
						$name = $db->FetchOne("SELECT `catname` FROM `[|PREFIX|]categories` WHERE `categoryid` = " . (int)$rule->eventCriteria['orderCriteria']);
						break;

					case 'brand':
						$name = $db->FetchOne("SELECT `brandname` FROM `[|PREFIX|]brands` WHERE `brandid` = " . (int)$rule->eventCriteria['orderCriteria']);
						break;

					case 'product':
						$name = $db->FetchOne("SELECT `prodname` FROM `[|PREFIX|]products` WHERE `productid` = " . (int)$rule->eventCriteria['orderCriteria']);
						break;
				}

				if ($rule->eventCriteria['orderCriteria'] && !$name) {
					// invalid rule: criteria specified, but no match found
					unset($rules[$index]);
					continue;
				}

				$rule->eventCriteria['orderCriteriaName'] = $name;
			}
		}

		return $rules;
	}

	public function GetCustomVars()
	{
		$vars = parent::GetCustomVars();

		// also include hidden/common vars used by each email integration module
		$vars['isconfigured'] = array(
			"name" => "",
			"type" => "",
			"help" => "",
			"default" => "",
			"format" => "boolean",
		);

		$vars['last_list_update'] = array(
			"name" => "",
			"type" => "",
			"help" => "",
			"default" => "",
		);

		return $vars;
	}

	/**
	* The email integration modules save differently than other module types, so this method is overridden.
	*
	* @param array $settings
	* @param bool $deleteFirst This is ignored for email integration modules
	* @return boolean
	*/
	public function SaveModuleSettings($settings = array(), $deleteFirst = true)
	{
		$moduleVariables = $this->GetCustomVars();

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		// general/api settings
		foreach ($settings as $name => $value) {
			if (!isset($moduleVariables[$name])) {
				continue;
			}

			$moduleVariable = $moduleVariables[$name];

			if (isset($moduleVariable['format'])) {
				$format = $moduleVariable['format'];
			} else {
				$format = '';
			}

			switch($format) {
				case 'boolean':
					if ($value) {
						$value = 1;
					} else {
						$value = 0;
					}
					break;

				case 'price':
					$value = DefaultPriceFormat($value);
					break;

				case 'weight':
				case 'dimension':
					$value = DefaultDimensionFormat($value);
					break;
			}

			$exists = $db->FetchOne("SELECT COUNT(*) FROM [|PREFIX|]module_vars WHERE modulename = '" . $db->Quote($this->GetId()) . "' AND variablename = '" . $db->Quote($name) . "'");
			if ($exists > 0) {
				$db->UpdateQuery('module_vars', array('variableval' => $value), "modulename = '" . $db->Quote($this->GetId()) . "' AND variablename = '" . $db->Quote($name) . "'");
			} else {
				$row = array(
					'modulename' => $this->GetId(),
					'variablename' => $name,
					'variableval' => $value
				);
				$db->InsertQuery('module_vars', $row);
			}

			$this->moduleVariables[$name] = $value;
		}

		// if provided, process rule builder POST data and serialize to config

		$commitRules = false;
		$decodedRules = false;
		$returnValue = true;

		GetLib('class.json');

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		// if rules are not set, do not save them
		if (isset($settings['rules'])) {
			if ($settings['rules']) {
				$decodedRules = ISC_JSON::decode($settings['rules'], true);
				if ($decodedRules) {
					$commitRules = true;
				} else {
					// do not save rules if they are set but failed to decode
					$log->LogSystemError(array('emailintegration', $this->GetName()), 'Failed to decode email integration rules JSON packet', $settings['rules']);
					$returnValue = false;
				}
			} else {
				// rules are set, but blank, so delete all rules
				$decodedRules = array();
				$commitRules = true;
			}
		}

		if ($commitRules) {
			// parse and save each rule - _Rule class will handle insert or update
			$ruleIds = array();

			$db->StartTransaction();

			$transactionOk = true;

			$ruleIds = array();
			if (!empty($decodedRules)) {
				foreach ($decodedRules as $rule) {
					$rule = Interspire_EmailIntegration_Rule::fromJSON($rule);

					if (!$rule) {
						$log->LogSystemError(array('emailintegration', $this->GetName()), 'Failed to translate email integration rule from JSON to EmailIntegration_Rule class.', $settings['rules']);
						$transactionOk = false;
						continue;
					}

					if (!$rule->save()) {
						$transactionOk = false;
						continue;
					}

					$ruleIds[] = $rule->id;
				}

				if ($transactionOk) {
					// rules saved ok - delete rules that should no longer exist
					if (!$db->DeleteQuery('email_rules', "WHERE `provider` = '" . $db->Quote($this->GetId()) . "' AND `id` NOT IN (" . implode(',', $ruleIds) . ")")) {
						$transactionOk = false;
						$log->LogSystemError(array('emailintegration', $this->GetName()), 'Failed to remove old integration rules due to database error.');
					}
				}
			} else {
				if (!$db->DeleteQuery('email_rules', "WHERE `provider` = '" . $db->Quote($this->GetId()) . "'")) {
					$transactionOk = false;
					$log->LogSystemError(array('emailintegration', $this->GetName()), 'Failed to remove old integration rules due to database error.');
				}
			}

			if ($transactionOk) {
				$db->CommitTransaction();
				self::flushRules();
			}
			else
			{
				$db->RollbackTransaction();
				$returnValue = false;
			}
		}

		return $returnValue;
	}

	/**
	* Routes a batch of subscriptions to lists per configured rules. Not available asynchronously.
	*
	* @param string $eventId
	* @param array<Interspire_EmailIntegration_Subscription> $subscriptions
	*/
	public static function routeSubscriptions($eventId, $subscriptions)
	{
		// for each subscription, run it through the rules configured for $eventId and generate a list of batches on a per-module, per-list basis
		// so as to end up with something like...
		//
		//	$batches = array(
		//		module_id => array(
		//			provider_list_id => array(
		//				array(merge data),
		//				array(merge data),
		//				array(merge data),
		//				...
		//			),
		//			...
		//		),
		//		...
		//	);
		//
		// then, for each list, call that module's batch subscribe method with the generated merge data
	}

	/**
	* Routes a subscriber to lists per configured rules
	*
	* @param string $eventId
	* @param Interspire_EmailIntegration_Subscription $subscriber
	* @param array $data
	* @param bool $asynchronous
	* @return array An array of Interspire_EmailIntegration_SubscriberActionResult objects
	*/
	public static function routeSubscription($eventId, Interspire_EmailIntegration_Subscription $subscriber, $asynchronous = true)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		$results = array();

		$log->LogSystemDebug('emailintegration', 'Processing subscription rules for event: ' . $eventId, var_export($subscriber, true));

		foreach (self::getRules($eventId) as /** @var Interspire_EmailIntegration_Rule */$rule) {
			$log->LogSystemDebug('emailintegration', 'Processing subscription rule...', var_export($rule, true));

			GetModuleById('emailintegration', /** @var ISC_EMAILINTEGRATION */$module, $rule->moduleId);
			if (!$module) {
				$log->LogSystemDebug('emailintegration', 'GetModuleById failed for emailintegration_' . $rule->moduleId);
				continue;
			}

			if (!$module->IsEnabled()) {
				$log->LogSystemDebug('emailintegration', 'Module ' . $rule->moduleId . ' is not enabled.');
				continue;
			}

			if (!$module->isConfigured()) {
				$log->LogSystemDebug('emailintegration', 'Module ' . $rule->moduleId . ' is not configured.');
				continue;
			}

			if (!$rule->qualifySubscription($subscriber)) {
				// subscription did not match criteria defined in this rule - skip
				$log->LogSystemDebug('emailintegration', 'Subscription did not qualify for this rule.');
				continue;
			}

			// nothing prevented this from being routed, so...
			$log->LogSystemDebug('emailintegration', 'Subscription OK for this rule.');
			switch ($rule->action) {
				case Interspire_EmailIntegration_Rule::ACTION_ADD:
					$result = $module->addSubscriberToList($rule->listId, $subscriber, $rule->fieldMap, $asynchronous);
					break;

				case Interspire_EmailIntegration_Rule::ACTION_REMOVE:
					$result = $module->removeSubscriberFromList($rule->listId, $subscriber, $asynchronous);
					break;
			}

			$results[] = $result;
		}

		return $results;
	}

	/**
	* Sends an update to a subscriber's IP address on any enabled email providers that support an IP address field
	*
	* @param string $email
	* @param string $ip
	* @param bool $asynchronous
	* @return void
	*/
	public static function routeSubscriptionIpUpdate($email, $ip, $asyncronous = true)
	{
		$modules = self::getConfiguredModules();
		foreach ($modules as /** @var ISC_EMAILINTEGRATION */$module) {
			if (!$module->getIpProviderFieldId()) {
				continue;
			}
			$module->updateSubscriptionIP($email, $ip, $asyncronous);
		}
		// @todo this should really return a set of results like routeSubscription
	}

	/**
	* Returns true if this module is enabled, otherwise false
	*
	* @return bool True if this module is enabled, otherwise false
	*/
	protected function CheckEnabled()
	{
		$modules = explode(",", GetConfig('EmailIntegrationMethods'));
		if (in_array($this->GetId(), $modules)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Build a property/configuration sheet for this module.
	 *
	 * @param string $tabId The ID of the tab.
	 * @return string The generated configuration page/sheet.
	 */
	public function GetPropertiesSheet($tabId)
	{
		throw new Exception('is this used?');
		$this->PreparePropertiesSheet($tabId, 'ModuleId', 'ModuleJavaScript', 'ModuleSelected');

		return Interspire_Template::getInstance('admin')->render('module.propertysheet.tpl');
	}

	/**
	* Determine if this module is both enabled and properly configured to contact the email provider
	*
	* @return bool True if enabled and properly configured
	*/
	public function isConfigured()
	{
		if ($this->GetValue('isconfigured')) {
			return true;
		}
		return false;
	}

	/**
	* Contacts the integration service to check if it is alive and configured OK
	*
	* @return bool True if this module is enabled
	*/
	abstract public function pingService($auth = null);

	/**
	* Finds and returns subscriber information using all fields in the provided $subscriber object
	*
	* @param string $listId
	* @param Interspire_EmailIntegration_Subscription $subscriber
	* @return Interspire_EmailIntegration_Subscription_Existing or false if the subscriber was not found
	*/
	abstract public function findListSubscriber($listId, Interspire_EmailIntegration_Subscription $subscriber);

	/**
	* Adds a subscriber to a specific list for this provider
	*
	* @param string $listId provider list id
	* @param Interspire_EmailIntegration_Subscription $subscriber
	* @param array $fieldMap
	* @param bool $asynchronous
	* @return Interspire_EmailIntegration_AddSubscriberResult
	*/
	abstract public function addSubscriberToList($listId, Interspire_EmailIntegration_Subscription $subscription, $fieldMap, $asynchronous = true);

	/**
	* Adds a batch of subscribers to a specific list for this provider (currently only used by export jobs, so not available in asynchronous form)
	*
	* @param string $listId provider list id
	* @param array<Interspire_EmailIntegration_Subscription> $subscribers an array of Interspire_EmailIntegration_Subscription instances
	* @param array $fieldMap field map to apply to each subscription
	* @return array<Interspire_EmailIntegration_AddSubscriberResult> or false on error or not supported
	*/
	abstract public function addSubscribersToList($listId, $subscribers, $fieldMap);

	/**
	* Removes a subscriber from a specific list for this provider
	*
	* @param mixed $listId
	* @param Interspire_EmailIntegration_Subscription $subscription
	* @param bool $asynchronous
	* @return Interspire_EmailIntegration_RemoveSubscriberResult
	*/
	abstract public function removeSubscriberFromList($listId, Interspire_EmailIntegration_Subscription $subscription, $asynchronous = true);

	abstract public function downloadLists();

	abstract public function downloadListFields($listId);

	/**
	* Generic call that each module must implement to be front-end compatible; verifying the API details entered by the user
	*
	* @param mixed $auth
	* @param mixed $data
	*/
	abstract public function remoteVerifyApi($auth, $data);

	/**
	* Generic call that each module must implement to be front-end compatible; refreshing the provider lists
	*
	* @param mixed $auth
	* @param mixed $data
	*/
	abstract public function remoteRefreshLists($auth, $data);

	/**
	* Returns the list_fields.provider_field_id value that corresponds to the email field for this provider. This is defined as its own method as email addresses are typically used as unique identifiers for list subscribers.
	*
	* @return string
	*/
	abstract public function getEmailProviderFieldId();

	/**
	* Returns the list_fields.provider_field_id value that corresponds to the IP address field for this provider. This is defined as its own method as IP addresses are not always supported as updatable fields but, if they are, are typically a fixed field.
	*
	* @return mixed Field id as string if supported, otherwise false or blank string if not supported.
	*/
	abstract public function getIpProviderFieldId();

	/**
	* If supported by this module, updates the IP address of a subscriber.
	*
	* @param string $email
	* @param string $ip
	* @param bool $asynchronous
	* @return bool
	*/
	abstract public function updateSubscriptionIP($email, $ip, $asynchronous = true);

	/**
	* Returns an admin template filename to use as the settings tab for this template, or anything false-like if none is implemented (e.g. blank string).
	*
	* @return string
	*/
	abstract public function getSettingsTemplate();

	/**
	* Returns an admin script filename to load on the settings page for this template, or anything false-like if none is implemented (e.g. blank string).
	*
	* @return string
	*/
	abstract public function getSettingsJavaScript();

	/**
	* Determines if this module is selectable on the settings page (e.g., the exportonly module cannot be added/removed as it's considered 'built-in')
	*
	* @return bool
	*/
	abstract public function isSelectable();

	/**
	* Determines if this module supports bulk exporting of details (via customers page, and others if implemented)
	*
	* @return bool
	*/
	abstract public function supportsBulkExport();

	/**
	* Determines if this modules supports sending updates to existing subscriptions
	*
	* @return bool
	*/
	abstract public function supportsSubscriberUpdates ();

	/**
	* Returns an array of Interspire_EmailIntegration_Subscription::FORMAT_PREF_ constants detailing the available mail format preferences for this module.
	*
	* @return array
	*/
	abstract public function getAvailableMailFormatPreferences();

	/**
	* Returns the Interspire_EmailIntegration_Subscription::FORMAT_PREF_ constant that points to the default mail format preference for this module (when no preference is specified, or if the specified preference falls outside of the options available for this module)
	*
	* @return int
	*/
	abstract public function getDefaultMailFormatPreference();

	/**
	* Checks if the local cache of provider list id/names has expired
	*
	* @return bool
	*/
	public function hasListCacheExpired()
	{
		$lastCheck = (int)$this->GetValue('last_list_update');
		$expire = $lastCheck + self::GC_MAX_LIST_AGE;
		if (time() > $expire) {
			return true;
		}
		return false;
	}

	/**
	* Retrieves cached lists for this module
	*
	* @return array or false if an error occurred - store logs will contain specifics
	*/
	public function getLists()
	{
		$provider = str_replace('emailintegration_', '', $this->GetId());

		if ($this->hasListCacheExpired()) {
			// list cache has expired for this module; skip local records and download again
			/** @var ISC_LOG */
			$log = $GLOBALS['ISC_CLASS_LOG'];
			$log->LogSystemDebug(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationListsExpired', array(
				'provider' => $this->GetName(),
			)));
			return $this->downloadLists();
		}

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];
		$result = $db->Query("SELECT * FROM `[|PREFIX|]email_provider_lists` WHERE `provider` = '" . $db->Quote($provider) . "' ORDER BY `name`");
		if (!$result) {
			return false;
		}

		$lists = array();
		while ($row = $db->Fetch($result)) {
			$lists[] = $row;
		}

		if (!empty($lists)) {
			return $lists;
		}

		return $this->downloadLists();
	}

	/**
	* Return a specific list from the cache, or check remotely if not found
	*
	* @param string $listId email_provider_lists.provider_list_id
	* @return array
	*/
	public function getList($listId)
	{
		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		$provider = str_replace('emailintegration_', '', $this->GetId());
		$result = $db->FetchRow("SELECT * FROM `[|PREFIX|]email_provider_lists` WHERE `provider` = '" . $db->Quote($provider) . "' AND `provider_list_id` = '" . $db->Quote($listId) . "'");
		if ($result) {
			// found locally
			return $result;
		}

		// not found locally...

		if (!$this->hasListCacheExpired()) {
			// list cache has not expired; don't bother checking remotely
			return false;
		}

		// call getLists, which will check remotely
		$lists = $this->getLists();

		foreach ($lists as $list) {
			if ($list['provider_list_id'] == $listId) {
				return $list;
			}
		}

		// not found locally or remotely
		return false;
	}

	/**
	* Retrieves cached list fields for this module
	*
	* @param mixed $listId email_provider_lists.provider_list_id
	* @return array
	*/
	public function getListFields($listId)
	{
		$provider = str_replace('emailintegration_', '', $this->GetId());

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		$sql = "SELECT * FROM `[|PREFIX|]email_provider_lists` WHERE `provider` = '" . $db->Quote($provider) . "' AND `provider_list_id` = '" . $db->Quote($listId) . "'";
		$list = $db->FetchRow($sql);
		if (!$list) {
			$log->LogSystemDebug(array('emailintegration', $this->GetName()), 'getListFields could not find list id '. $listId, trace(false, true));
			return false;
		}

		if ((int)$list['last_field_update'] < time() - self::GC_MAX_FIELD_AGE) {
			// field cache has expired for this list; skip local records and download again
			$log->LogSystemDebug(array('emailintegration', $this->GetName()), GetLang('EmailIntegrationListFieldsExpired', array(
				'provider' => $this->GetName(),
			)));
			return $this->downloadListFields($list['provider_list_id']);
		}

		$sql = "
			SELECT
				lf.*
			FROM
				`[|PREFIX|]email_provider_lists` l,
				`[|PREFIX|]email_provider_list_fields` lf
			WHERE
				l.`provider` = '" . $db->Quote($provider) . "'
				AND l.`provider_list_id` = '" . $db->Quote($list['provider_list_id']) . "'
				AND lf.`email_provider_list_id` = l.`id`
			ORDER BY
				lf.`name`";

		$result = $db->Query($sql);
		if (!$result) {
			$log->LogSystemDebug(array('emailintegration', $this->GetName()), 'getListFields could not select existing fields for list ' . $listId);
			return false;
		}

		$fields = array();
		while ($row = $db->Fetch($result)) {
			$fields[] = $row;
		}

		if (!empty($fields)) {
			return $fields;
		}

		return $this->downloadListFields($list['provider_list_id']);
	}

	/**
	* Handle request from settings UI for lists
	*
	* @param mixed $auth
	* @param mixed $data
	* @return mixed
	*/
	public function remoteGetLists($auth, $data)
	{
		$lists = $this->getLists();

		if (!is_array($lists)) {
			return array('success' => false, 'message' => GetLang('EmailIntegrationGetListsFailed'));
		}

		if (empty($lists)) {
			return array('success' => false, 'message' => GetLang('EmailIntegrationProviderHasNoLists', array(
				'provider' => $this->GetName(),
			)));
		}

		return array('success' => true, 'lists' => $lists);
	}

	/**
	* Handle request from settings UI for list fields
	*
	* @param mixed $auth
	* @param mixed $data
	* @return mixed
	*/
	public function remoteGetListFields($auth, $data)
	{
		$list = $this->getList($data['listId']);
		if (!$list) {
			return array('success' => false, 'message' => GetLang('EmailIntegrationGetListFieldsFailed'));
		}

		$fields = $this->getListFields($list['provider_list_id']);
		if (!$fields) {
			return array('success' => false, 'message' => GetLang('EmailIntegrationGetListFieldsFailed'));
		}

		return array('success' => true, 'fields' => $fields);
	}

	/**
	* Handle request from settings UI for a field sync form
	*
	* @param mixed $auth
	* @param mixed $data
	* @return mixed
	*/
	public function remoteGetFieldSyncForm($auth, $data)
	{
		$template = Interspire_Template::getInstance('admin');

		$listId = $data['listId'];

		if (isset($data['modalContentOnly'])) {
			$modalContentOnly = (bool)$data['modalContentOnly'];
		} else {
			$modalContentOnly = false;
		}

		$lists = $this->getLists(); // use this to force a refresh from provider if necessary

		foreach ($lists as $list) {
			if ($list['provider_list_id'] == $listId) {
				$listFields = $this->getListFields($listId);

				$template->assign('listFields', $listFields);

				/** @var ISC_FORM */
				$form = $GLOBALS['ISC_CLASS_FORM'];

				if (isset($data['subscriptionType'])) {
					$subscription = 'Interspire_EmailIntegration_Subscription_' . $data['subscriptionType'];
					$subscription = new $subscription();
				}
				else
				{
					$subscription = new Interspire_EmailIntegration_Subscription_Order();
				}

				$mappings = array();

				if (isset($data['map']) && $data['map']) {
					$map = ISC_JSON::decode($data['map'], true);
					if (is_array($map)) {
						foreach ($map as $provider => $local) {
							$mappings[$provider] = $local;
						}
					}
				}

				$formFields = $subscription->getSubscriptionFields();
				$template->assign('formFields', $formFields);

				$template->assign('module', $this);
				$template->assign('mappings', $mappings);
				break;
			}
		}

		if ($modalContentOnly) {
			return array(
				'success' => true,
				'html' => $template->render('emailintegration.fieldsyncform.modalcontent.tpl'),
			);
		} else {
			$template->display('settings.emailintegration.fieldsyncform.tpl');
			die();
		}
	}

	/**
	* Remove the provider lists, updating rules and sending notifications as needed - generally used in reaction to an invalid list id error from the mail provider
	*
	* @param array $lists array of provider list ids
	* @return bool false on error
	*/
	public function removeProviderLists($lists)
	{
		if (!is_array($lists)) {
			$lists = array($lists);
		}

		if (empty($lists)) {
			return true;
		}

		$provider = str_replace('emailintegration_', '', $this->GetId());

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];
		$dbErrorMessage = GetLang('EmailIntegrationUpdateProviderListsFailedDatabase', array('provider' => $this->GetName()));

		// rule-delete notification emails need access to list names so select the lists from the db first
		$query = "SELECT `provider_list_id`, `name` FROM `[|PREFIX|]email_provider_lists` WHERE `provider` = '" . $db->Quote($provider) . "' AND `provider_list_id` IN ('" . implode("','", array_map(array($db, 'Quote'), $lists)) . "')";
		$query = $db->Query($query);
		if ($query === false) {
			return false;
		}

		// map the lists found for removal to an array using the provider id (which could be a string)
		$deletedLists = array();
		while ($list = $db->Fetch($query)) {
			$deletedLists[$list['provider_list_id']] = $list;
		}

		// delete the lists

		$db->StartTransaction();
		$commit = true;

		foreach ($deletedLists as $deletedList) {
			$providerListId = $deletedList['provider_list_id'];
			// delete the list's fields
			$sql = "
				DELETE FROM lf
				USING
					`[|PREFIX|]email_provider_list_fields` lf
					LEFT JOIN `[|PREFIX|]email_provider_lists` l ON lf.email_provider_list_id = l.id
				WHERE
					l.provider_list_id = '" . $db->Quote($providerListId) . "'";
			if (!$db->Query($sql)) {
				$commit = false;
				break;
			}

			// delete the list itself
			if (!$db->DeleteQuery('email_provider_lists', "WHERE provider_list_id = '" . $db->Quote($providerListId) . "' AND provider = '" . $db->Quote($provider) . "'")) {
				$commit = false;
				break;
			}
		}

		if (!$commit) {
			// a deletion failed, rollback and abort now
			$db->RollbackTransaction();
			return false;
		}

		if (!$db->CommitTransaction()) {
			// commit failed, abort now
			return false;
		}

		// lists have been deleted, check for affected rules and delete them

		$rules = $this->getModuleRules();
		if (empty($rules)) {
			// module has no rules - nothing more to do
			return true;
		}

		$deletedRules = array();
		foreach ($rules as /** @var Interspire_EmailIntegration_Rule */$rule) {
			if (array_key_exists($rule->listId, $deletedLists)) {
				// the list this rule points to has been deleted, so, delete the rule too

				$summary = GetLang('EmailIntegrationRuleDeletedByListDeletion', array(
					'provider' => $this->GetName(),
					'list' => $deletedLists[$rule->listId]['name'],
				));

				$log->LogSystemNotice(array('emailintegration', $this->GetName()), $summary, $rule->toJavaScript());
				$rule->delete();
				$deletedRules[] = $rules;
			}
		}

		if (empty($deletedRules)) {
			// no rules were deleted - nothing more to do
			return true;
		}

		self::flushRules();

		// rules were deleted - send email notification
		$GLOBALS['EmailHeader'] = GetLang("NoticeOfEmailIntegrationRulesDeletion");

		$replacements = array(
			'provider' => $this->GetName(),
		);

		$GLOBALS['NoticeOfEmailIntegrationRulesDeletion_1'] = GetLang('NoticeOfEmailIntegrationRulesDeletion_1', $replacements);
		$GLOBALS['NoticeOfEmailIntegrationRulesDeletion_2'] = GetLang('NoticeOfEmailIntegrationRulesDeletion_2', $replacements);
		$GLOBALS['NoticeOfEmailIntegrationRulesDeletion_3'] = GetLang('NoticeOfEmailIntegrationRulesDeletion_3', $replacements);
		$GLOBALS['NoticeOfEmailIntegrationRulesDeletion_4'] = GetLang('NoticeOfEmailIntegrationRulesDeletion_4', $replacements);
		$GLOBALS['NoticeOfEmailIntegrationRulesDeletion_Lists'] = GetLang('NoticeOfEmailIntegrationRulesDeletion_Lists', $replacements);

		$GLOBALS['DeletedLists'] = '';
		foreach ($deletedLists as $list) {
			$GLOBALS['DeletedLists'] .= '<li>' . isc_html_escape($list['name']) . '</li>';
		}

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("email_integration_ruledeleted_email");
		$message = $emailTemplate->ParseTemplate(true);

		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
		$obj_email->Set("Subject", GetLang("NoticeOfEmailIntegrationRulesDeletion"));
		$obj_email->AddBody("html", $message);
		$obj_email->AddRecipient(GetConfig('AdminEmail'), "", "h");
		$email_result = $obj_email->Send();

		return true;
	}

	/**
	* Takes a list of lists from the module's provider and updates the local cache
	*
	* @param mixed $lists
	* @return array or false on error - store log will contain specifics
	*/
	public function updateProviderLists($lists)
	{
		// can't rely on ISC_ADMIN_ENGINE or admin lang stuff from here because this code may be run by the task manager
		$languagePath = ISC_BASE_PATH . '/language/' . GetConfig('Language') . '/admin';
		ParseLangFile($languagePath . '/common.ini');
		ParseLangFile($languagePath . '/settings.emailintegration.ini');

		$provider = str_replace('emailintegration_', '', $this->GetId());

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];
		$dbErrorMessage = GetLang('EmailIntegrationUpdateProviderListsFailedDatabase', array('provider' => $this->GetName()));

		$quotedIdList = array();

		$db->StartTransaction();

		if (!empty($lists)) {
			// no db api method for multiple inserts, so...
			$sql = "INSERT INTO `[|PREFIX|]email_provider_lists` (provider, provider_list_id, `name`) VALUES ";

			$first = true;
			foreach ($lists as &$list) {
				$quotedIdList[] = $db->Quote($list['provider_list_id']);
				$list['provider'] = $provider;

				if (!$first) {
					$sql .= ",";
				}

				$sql .= "('" . $db->Quote($list['provider']) . "', '" . $db->Quote($list['provider_list_id']) . "', '" . $db->Quote(isc_substr($list['name'], 0, self::LIST_NAME_MAX_LEN)) . "')";
				$first = false;
			}
			unset($list); // reference unset

			// we use INSERT ON DUPLICATE KEY UPDATE so that local list ids are maintained instead of being deleted and re-inserted
			$sql .= " ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), last_field_update = 0";
			$result = $db->Query($sql);

			if (!$result) {
				$db->RollbackTransaction();
				$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage, trace(false, true));
				return false;
			}
		}

		// so that we can update rules, fetch a list of lists that will be deleted
		$result = $db->Query("SELECT * FROM `[|PREFIX|]email_provider_lists` WHERE provider = '" . $db->Quote($provider) . "' AND provider_list_id NOT IN ('" . implode("','", $quotedIdList) . "')");
		if (!$result) {
			$db->RollbackTransaction();
			$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage, trace(false, true));
			return false;
		}

		$listsToDelete = array();
		while ($row = $db->Fetch($result))
		{
			$listsToDelete[] = $row['provider_list_id'];
		}
		$result = $this->removeProviderLists($listsToDelete);

		if (!$result) {
			$db->RollbackTransaction();
			$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage, trace(false, true));
			return false;
		}

		if (!$db->CommitTransaction()) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage, $db->GetErrorMsg());
			return false;
		}

		$message = GetLang('EmailIntegrationUpdatedProviderLists', array('count' => count($lists), 'provider' => $this->GetName()));
		$log->LogSystemSuccess(array('emailintegration', $this->GetName()), $message);

		// if empty, return immediately, otherwise the next $this->get... call will result in a loop of always requesting details from the provider
		if (empty($lists)) {
			return $lists;
		}

		return $this->getLists();
	}

	/**
	* Takes a list of fields from the module's provider and updates the local cache, will modify affected rules as a result and notify admin of changed or removed rules
	*
	* @param string $listId
	* @param array $fields
	*/
	public function updateProviderListFields($listId, $fields)
	{
		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];
		$dbErrorMessage = GetLang('EmailIntegrationUpdateProviderListFieldsFailedDatabase', array('provider' => $this->GetName()));

		$list = $this->getList($listId);
		if (!$list) {
			return false;
		}

		if ($this->getEmailProviderFieldId()) {
			// search for and discard the email address field if it's been provided -- it shouldn't be saved in provider_list_fields because you can't manually map to it
			foreach ($fields as $offset => $field) {
				if ($field['provider_field_id'] == $this->getEmailProviderFieldId()) {
					array_splice($fields, $offset, 1);
				}
			}
		}

		// are there any rules based on the list being updated? if so, track fields that get deleted so the rules can be updated, or even removed
		$rulesExistForThisList = false;
		$listRules = array();
		$allRules = self::getRules();
		foreach ($allRules as /** @var Interspire_EmailIntegration_Rule */$rule) {
			if ($rule->moduleId !== 'emailintegration_' . $list['provider']) {
				continue;
			}

			if ($list['provider_list_id'] == $rule->listId) {
				$listRules[] = $rule;
				$rulesExistForThisList = true;
			}
		}

		if ($rulesExistForThisList) {
			// get current fields
			$result = $db->Query("SELECT * FROM `[|PREFIX|]email_provider_list_fields` WHERE email_provider_list_id = '" . $db->Quote($listId) . "'");
			if (!$result) {
				return false;
			}

			$currentFields = array();
			while ($row = $db->Fetch($result))
			{
				$currentFields[$row['provider_field_id']] = $row;
			}
		}

		$db->StartTransaction();

		// no db api method for multiple inserts, so...
		$sql = "INSERT INTO `[|PREFIX|]email_provider_list_fields` (email_provider_list_id, provider_field_id, `name`, `type`, `size`, `required`, `settings`) VALUES ";

		$quotedIdList = array();
		$first = true;
		foreach ($fields as &$field) {
			$quotedIdList[] = $db->Quote($field['provider_field_id']);
			$field['email_provider_list_id'] = $list['id'];

			if (!$first) {
				$sql .= ",";
			}

			if (!isset($field['settings'])) {
				$field['settings'] = '';
			}

			$sql .= "('" . $db->Quote($field['email_provider_list_id']) . "', '" . $db->Quote($field['provider_field_id']) . "', '" . $db->Quote($field['name']) . "', '" . $db->Quote($field['type']) . "', '" . $db->Quote($field['size']) . "', " . (int)$field['required'] . ", '" . $db->Quote($field['settings']) . "')";
			$first = false;
		}
		unset($field); // reference unset

		// we use INSERT ON DUPLICATE KEY UPDATE so that local field ids are maintained instead of being deleted and re-inserted
		$sql .= " ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `type` = VALUES(`type`), `size` = VALUES(`size`), `required` = VALUES(`required`), `settings` = VALUES(`settings`)";
		$result = $db->Query($sql);
		if (!$result) {
			$db->RollbackTransaction();
			$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage);
			return false;
		}

		// notification emails need to know which fields are to be deleted so we need to select them first
		$result = $db->Query("SELECT provider_field_id, `name` FROM `[|PREFIX|]email_provider_list_fields` WHERE email_provider_list_id = '" . $list['id'] . "' AND provider_field_id NOT IN ('" . implode("','", $quotedIdList) . "')");
		if (!$result) {
			$db->RollbackTransaction();
			$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage);
			return false;
		}

		$deletedFields = array();
		while ($row = $db->Fetch($result)) {
			$deletedFields[$row['provider_field_id']] = $row;
		}

		// prune fields that aren't included in the update
		$result = $db->DeleteQuery('email_provider_list_fields', "WHERE email_provider_list_id = '" . $list['id'] . "' AND provider_field_id NOT IN ('" . implode("','", $quotedIdList) . "')");
		if (!$result) {
			$db->RollbackTransaction();
			$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage);
			return false;
		}

		// set the last field update timestamp
		$result = $db->UpdateQuery('email_provider_lists', array(
			'last_field_update' => time(),
		), "`id` = '" . $list['id'] . "'");

		if (!$result) {
			$db->RollbackTransaction();
			$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage);
			return false;
		}

		// all db updates done
		if (!$db->CommitTransaction()) {
			$log->LogSystemError(array('emailintegration', $this->GetName()), $dbErrorMessage);
			return false;
		}

		$message = GetLang('EmailIntegrationUpdatedProviderListFields', array(
			'count' => count($fields),
			'provider' => $this->GetName(),
			'list' => $list['name'],
		));
		$log->LogSystemSuccess(array('emailintegration', $this->GetName()), $message);

		if ($rulesExistForThisList) {
			// appropriately update any field mappings related to deleted fields, add appropriate logs

			$affectedRules = array();

			foreach ($listRules as /** @var Interspire_EmailIntegration_Rule */$rule) {
				// check each lists's field maps to see if that rule was specifically affected by field updates
				$specificListDeletedFields = array();
				foreach ($rule->fieldMap as $providerFieldId => $subscriptionFieldId) {
					if (!array_key_exists($providerFieldId, $deletedFields)) {
						continue;
					}
					$specificListDeletedFields[$providerFieldId] = $deletedFields[$providerFieldId];
					unset($rule->fieldMap[$providerFieldId]);
				}

				if (empty($specificListDeletedFields)) {
					continue;
				}

				// some fields were removed - update the rule
				$log->LogSystemNotice(array('emailintegration', $this->GetName()), "Email rules for the list '" . $list['name'] . "' need to be updated because some rules are mapped to " . $this->GetName() . " fields that no longer exist.", var_export($deletedFields, true));

				// the mappings were removed above so just save()
				$rule->save();

				$affectedRules[$rule->id] = $rule;
			}

			if (!empty($affectedRules)) {
				// some rules were updated - notify the admin by email
				self::flushRules();

				// can't rely on ISC_ADMIN_ENGINE or admin lang stuff from here because this code may be run by the task manager
				$languagePath = ISC_BASE_PATH . '/language/' . GetConfig('Language') . '/admin';
				ParseLangFile($languagePath . '/common.ini');
				ParseLangFile($languagePath . '/settings.emailintegration.ini');

				$GLOBALS['EmailHeader'] = GetLang("NoticeOfEmailIntegrationRulesUpdate");

				$replacements = array(
					'provider' => $this->GetName(),
					'list' => $list['name'],
				);

				$GLOBALS['NoticeOfEmailIntegrationRulesUpdate_1'] = GetLang('NoticeOfEmailIntegrationRulesUpdate_1', $replacements);
				$GLOBALS['NoticeOfEmailIntegrationRulesUpdate_2'] = GetLang('NoticeOfEmailIntegrationRulesUpdate_2', $replacements);
				$GLOBALS['NoticeOfEmailIntegrationRulesUpdate_3'] = GetLang('NoticeOfEmailIntegrationRulesUpdate_3', $replacements);
				$GLOBALS['NoticeOfEmailIntegrationRulesUpdate_4'] = GetLang('NoticeOfEmailIntegrationRulesUpdate_4', $replacements);
				$GLOBALS['NoticeOfEmailIntegrationRulesUpdate_MergeFields'] = GetLang('NoticeOfEmailIntegrationRulesUpdate_MergeFields', $replacements);

				$GLOBALS['MergeFieldsList'] = '';
				foreach ($deletedFields as $providerFieldId => $deletedField) {
					// @todo this function no longer has access to field names
					$GLOBALS['MergeFieldsList'] .= '<li>' . isc_html_escape($deletedField['name']) . '</li>';
				}

				$emailTemplate = FetchEmailTemplateParser();
				$emailTemplate->SetTemplate("email_integration_ruleupdated_email");
				$message = $emailTemplate->ParseTemplate(true);

				$obj_email = GetEmailClass();
				$obj_email->Set('CharSet', GetConfig('CharacterSet'));
				$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
				$obj_email->Set("Subject", GetLang("NoticeOfEmailIntegrationRulesUpdate"));
				$obj_email->AddBody("html", $message);
				$obj_email->AddRecipient(GetConfig('AdminEmail'), "", "h");
				$email_result = $obj_email->Send();
			}
		}

		// if empty, return immediately, otherwise the next $this->get... call will result in a loop of always requesting details from the provider
		if (empty($fields)) {
			return $fields;
		}

		return $this->getListFields($list['provider_list_id']);
	}

	/**
	* Notify admin by email of a failed subscription
	*
	* @param Interspire_EmailIntegration_Subscription $subscription Failed subscription
	* @param array $merge Failed merge data
	* @param string $errorMessage
	*/
	public function notifyAdmin(Interspire_EmailIntegration_Subscription $subscription, $merge, $errorMessage)
	{
		// can't rely on ISC_ADMIN_ENGINE or admin lang stuff from here because this code may be run by the task manager
		$languagePath = ISC_BASE_PATH . '/language/' . GetConfig('Language') . '/admin';
		ParseLangFile($languagePath . '/common.ini');
		ParseLangFile($languagePath . '/settings.emailintegration.ini');

		$replacements = array(
			'provider' => $this->GetName(),
			'time' => isc_date(GetConfig('ExtendedDisplayDateFormat'), time()),
		);

		$GLOBALS['EmailHeader'] = GetLang("NoCheckoutProvidersSubject");
		$GLOBALS['EmailMessage'] = sprintf(GetLang("NoCheckoutProvidersErrorLong"), $GLOBALS['ShopPath']);
		$GLOBALS['SubscriptionDetails'] = '';

		$GLOBALS['EmailIntegrationNotice_Header'] = GetLang('EmailIntegrationNotice_Header', $replacements);

		$GLOBALS['EmailIntegrationNotice_Intro'] = GetLang('EmailIntegrationNotice_Intro', $replacements);
		$GLOBALS['EmailIntegrationNotice_Error'] = GetLang('EmailIntegrationNotice_Error', $replacements);
		$GLOBALS['EmailIntegrationNotice_Message'] = $errorMessage;
		$GLOBALS['EmailIntegrationNotice_Time'] = GetLang('EmailIntegrationNotice_Time', $replacements);
		$GLOBALS['EmailIntegrationNotice_Details'] = GetLang('EmailIntegrationNotice_Details', $replacements);
		$GLOBALS['EmailIntegrationNotice_Type'] = $subscription->getSubscriptionTypeLang();

		$details = new Xhtml_Table();

		$row = new Xhtml_Tr();
		$row->appendChild(new Xhtml_Th(GetLang('EmailIntegrationNotice_Columns_Provider', $replacements)));
		$row->appendChild(new Xhtml_Th(GetLang('EmailIntegrationNotice_Columns_Subscription', $replacements)));
		$details->appendChild($row);

		$row = new Xhtml_Tr();
		$row->appendChild(new Xhtml_Td($this->getEmailProviderFieldId()));
		$row->appendChild(new Xhtml_Td($subscription->getSubscriptionEmail()));
		$details->appendChild($row);

		foreach ($merge as $field => $value) {
			$row = new Xhtml_Tr();
			$row->appendChild(new Xhtml_Td($field));
			$row->appendChild(new Xhtml_Td($value));
			$details->appendChild($row);
		}

		$GLOBALS['EmailIntegrationNotice_Subscription'] = $details->render();

		$GLOBALS['EmailIntegrationNotice_CommonCauses'] = GetLang('EmailIntegrationNotice_CommonCauses', $replacements);

		$GLOBALS['EmailIntegrationNotice_Cause1_Intro'] = GetLang('EmailIntegrationNotice_Cause1_Intro', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause1_Detail'] = GetLang('EmailIntegrationNotice_Cause1_Detail', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause2_Intro'] = GetLang('EmailIntegrationNotice_Cause2_Intro', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause2_Detail'] = GetLang('EmailIntegrationNotice_Cause2_Detail', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause3_Intro'] = GetLang('EmailIntegrationNotice_Cause3_Intro', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause3_Detail'] = GetLang('EmailIntegrationNotice_Cause3_Detail', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause4_Intro'] = GetLang('EmailIntegrationNotice_Cause4_Intro', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause4_Detail'] = GetLang('EmailIntegrationNotice_Cause4_Detail', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause5_Intro'] = GetLang('EmailIntegrationNotice_Cause5_Intro', $replacements);
		$GLOBALS['EmailIntegrationNotice_Cause5_Detail'] = GetLang('EmailIntegrationNotice_Cause5_Detail', $replacements);

		$GLOBALS['EmailIntegrationNotice_Closing'] = GetLang('EmailIntegrationNotice_Closing', $replacements);

		$emailTemplate = FetchEmailTemplateParser();
		$emailTemplate->SetTemplate("email_integration_notice_email");
		$message = $emailTemplate->ParseTemplate(true);

		$obj_email = GetEmailClass();
		$obj_email->Set('CharSet', GetConfig('CharacterSet'));
		$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
		$obj_email->Set("Subject", GetLang("EmailIntegrationEmailSubject"));
		$obj_email->AddBody("html", $message);
		$obj_email->AddRecipient(GetConfig('AdminEmail'), "", "h");
		$email_result = $obj_email->Send();
	}

	/**
	* Determines if - for all enabled and configured modules - at least one rule exists that adds a customer to a list when their order is completed. This is used to determine if the checkbox should show on checkout.
	*
	* Note: this is different from whether order rules exist at all, because we don't require user permission to remove them from a mailing list.
	*
	* @return bool
	*/
	public static function doOrderAddRulesExist()
	{
		$modules = self::getConfiguredModules();
		foreach ($modules as /** @var ISC_EMAILINTEGRATION */$module) {
			$rules = $module->getOrderCompletedRules(false);
			foreach ($rules as /** @var Interspire_EmailIntegration_Rule */$rule) {
				if ($rule->action == Interspire_EmailIntegration_Rule::ACTION_ADD) {
					// there is at least one module with an order rule that subscribes the customer
					return true;
				}
			}
		}
		return false;
	}

	/**
	* Generate merge data for a list subscription (or batch of list subscriptions) for this module based on provided field mapping.
	*
	* @param Interspire_EmailIntegration_Subscription $subscription single subscription of array of subscriptions
	* @param string $listId provider_list_id
	* @param array<provider_field_id=subscription_field_id> $fieldMap
	* @param bool $includeEmail include built-in email address field in merge data
	*/
	public function getMergeData($subscription, $listId, $fieldMap, $includeEmail = false)
	{
		Interspire_TimerStack::start();

		if (is_array($subscription)) {
			$single = false;
			$subscriptions = $subscription;
		}
		else
		{
			$single = true;
			$subscriptions = array($subscription);
		}

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		$list = $this->getList($listId);

		// get list fields so we know how to translate data
		$providerFields = $this->getListFields($list['provider_list_id']);
		foreach ($providerFields as $index => $providerField) {
			// change the fields array so it has a map of provider_field_id => field
			$providerFields[$providerField['provider_field_id']] = $providerField;
		}

		// note: this takes about 3 - 4 secs per 100 records, but I can't spot an optimisation for it right now

		$batch = array();
		foreach ($subscriptions as /** @var Interspire_EmailIntegration_Subscription */$subscription) {
			$item = array();

			$subscriptionFields = $subscription->getFlatSubscriptionFields();

			$subscriptionData = $subscription->getSubscriptionData();

			foreach ($fieldMap as $providerFieldId => $subscriptionFieldId) {
				if (!isset($subscriptionData[$subscriptionFieldId])) {
					// this used to log an error but with the customer class not all data is always available so it's changed to a debug
					$log->LogSystemDebug(array('emailintegration', $this->GetName()), 'Invalid field mapping specified. Local field "' . $subscriptionFieldId . '" specified in mapping, but not found in subscription data.', var_export($subscriptionData, true));
					continue;
				}

				if (!isset($providerFields[$providerFieldId])) {
					$log->LogSystemError(array('emailintegration', $this->GetName()), 'Invalid field mapping specified. Provider field "' . $providerFieldId . '" specified in mapping, but not found in provider field list.', var_export($providerFields, true));
					continue;
				}

				if (!isset($subscriptionFields[$subscriptionFieldId])) {
					$log->LogSystemError(array('emailintegration', $this->GetName()), 'Invalid field mapping specified. Local field "' . $subscriptionFieldId . '" specified in mapping, but not found in subscription-class field list.', var_export($subscriptionFields, true));
					continue;
				}

				$item[$providerFieldId] = $this->translateMergeField(
					$subscriptionFields[$subscriptionFieldId],
					$providerFields[$providerFieldId],
					$subscriptionData[$subscriptionFieldId],
					$subscriptionData
				);
			}

			if ($includeEmail) {
				$item[$this->getEmailProviderFieldId()] = $subscription->getSubscriptionEmail();
			}

			$batch[] = $item;
		}

		$log->LogSystemDebug(array('emailintegration', $this->GetName()), 'batch data generated for ' . count($batch) . ' subscriptions in ' . number_format(Interspire_TimerStack::stop(), 3) . ' secs');

		if ($single) {
			return $batch[0];
		}

		return $batch;
	}

	/**
	* Returns the {provider} in 'Interspire_EmailIntegration_{provider}' - e.g., 'MailChimp' for mailchimp.
	*
	* This is used as a basis for merge field translation to automatically fetch classes.
	*
	* @return string
	*/
	abstract public function getProviderLibClassName();

	/**
	* This method takes a piece of data from a subscription and translates it into a suitable value for sending to {provider} based on the type of local and remote fields being mapped
	*
	* This method essentially automates a call to Interspire_EmailIntegration_{provider}_Field_{provider_field_type}->fromSubscriptionToProvider({subcription_field_type}, {subscription_field_value})
	*
	* @param Interspire_EmailIntegration_Field $subscriptionField
	* @param array $providerField A record from email_provider_list_fields
	* @param mixed $subscriptionValue The individual piece of data to be translated
	* @param array $subscriptionData The full subscription data (as some translations require access to other fields to work properly, such as order value formatting w/ correct exchange rates)
	*/
	public function translateMergeField(Interspire_EmailIntegration_Field $subscriptionField, $providerField, $subscriptionValue, $subscriptionData)
	{
		$baseClassName = $this->getProviderLibClassName();

		$providerFieldClassName = 'Interspire_EmailIntegration_' . $baseClassName . '_Field_' . ucfirst($providerField['type']);

		if (!class_exists($providerFieldClassName)) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemNotice(array('emailintegration', $this->GetName()), GetLang('EmailIntegration_Log_UnableToTranslateField', array(
				'provider' => $this->GetName(),
				'field_type' => $providerField['type'],
				'field_name' => $providerField['name'],
				'class_name' => $providerFieldClassName,
				'subscription_field_name' => $subscriptionField->description,
			)));

			if ($subscriptionField instanceof Interspire_EmailIntegration_Field_StringInterface) {
				return $subscriptionField->valueToString($value);
			}

			return '';
		}

		/** @var Interspire_EmailIntegration_ProviderField */
		$providerFieldClass = new $providerFieldClassName($providerField);
		$translation = $providerFieldClass->fromSubscriptionToProvider($subscriptionField, $subscriptionValue);

		if ($translation === null) {
			return '';
		}

		return $translation;
	}
}
