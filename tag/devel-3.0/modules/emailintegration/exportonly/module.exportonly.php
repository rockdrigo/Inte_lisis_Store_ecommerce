<?php

class EMAILINTEGRATION_EXPORTONLY extends ISC_EMAILINTEGRATION
{
	public function __construct ()
	{
		parent::__construct();

		$this->name = GetLang('ExportOnlyModuleName');
	}

	/**
	* The export only module is always considered configured, if enabled
	*
	* @return bool true
	*/
	public function isConfigured ()
	{
		return $this->isEnabled();
	}

	/**
	* The export module is always considered enabled
	*
	* @return true
	*/
	public function isEnabled ()
	{
		return true;
	}

	/**
	* The export only module does not have the same export interface as other regular modules
	*
	*/
	public function supportsBulkExport ()
	{
		return false;
	}

	public function supportsSubscriberUpdates ()
	{
		return false;
	}

	public function getSettingsTemplate ()
	{
		return 'settings.emailintegration.exportonly.tpl';
	}

	public function getSettingsJavaScript ()
	{
		return 'manage.js';
	}

	public function isSelectable ()
	{
		return false;
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

	public function pingService ($auth = null)
	{
		// not used
	}

	public function downloadLists ()
	{
		// not used
	}

	public function downloadListFields ($list)
	{
		// not used
	}

	/**
	* Find and return information about a subscriber for a list
	*
	* @param string $listId
	* @param Interspire_EmailIntegration_Subscription $subscriber
	* @return Interspire_EmailIntegration_Subscription_Existing or false if the subscriber was not found
	*/
	public function findListSubscriber ($listId, Interspire_EmailIntegration_Subscription $subscription)
	{
		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];
		$result = $db->FetchOne("SELECT * FROM `[|PREFIX|]subscribers` WHERE subemail = '" . $db->Quote($insert['subemail']) . "'");
		if (!$result) {
			return false;
		}

		$return = new Interspire_EmailIntegration_Subscription_Existing;
		$return->setSubscriptionEmail($result['subemail']);
		$return->setSubscriptionData($result);

		return $return;
	}

	public function removeSubscriberFromList ($listId, Interspire_EmailIntegration_Subscription $subscription, $asynchronous = true)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		$exists = $this->findListSubscriber($list['provider_list_id'], $subscription);
		if (is_array($exists)) {
			$exists = true;
		} else {
			$exists = false;
		}

		if ($existed) {
			$success = $db->DeleteQuery('subscribers', "WHERE `` = '" . $db->Quote($subscription->getSubscriptionEmail()) . "'");
		} else {
			$success = true;
		}

		if ($success) {
			$log->LogSystemSuccess(array('emailintegration', $this->GetName()), 'Subscriber removed.');
		} else {
			$log->LogSystemError(array('emailintegration', $this->GetName()), 'Failed to remove subscriber due to database error.');
		}

		return new Interspire_EmailIntegration_RemoveSubscriberResult($this->GetId(), $listId, $success, $existed);
	}

	public function addSubscriberToList ($listId, Interspire_EmailIntegration_Subscription $subscription, $fieldMap, $asynchronous = true)
	{
		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		// the field mapping is most likely subscribers.subfirstname -> $subscription->firstName but, just incase, use the provided field map in the rule anyway
		$data = $subscription->getSubscriptionData();

		$insert = array(
			'subemail' => $subscription->getSubscriptionEmail(),
			'subfirstname' => $data[$fieldMap['subfirstname']],
		);

		$existed = (bool)$db->FetchOne("SELECT COUNT(*) FROM [|PREFIX|]subscribers WHERE subemail = '" . $db->Quote($insert['subemail']) . "'");

		if (!$existed) {
			$success = (bool)$db->InsertQuery('subscribers', $insert);
		} else {
			$success = true;
		}

		if ($success) {
			$log->LogSystemSuccess(array('emailintegration', $this->GetName()), GetLang('EmailIntegration_ExportOnly_Stored', array(
				'email' => $insert['subemail'],
			)));
		} else {
			$log->LogSystemError(array('emailintegration', $this->GetName()), GetLang('EmailIntegration_ExportOnly_FailedToStore'));
		}

		return new Interspire_EmailIntegration_AddSubscriberResult($this->GetId(), $listId, false, $success, $existed);
	}

	public function addSubscribersToList ($listId, $subscribers, $fieldMap)
	{
		// no batch insert support for internal subscriber table
		return false;
	}

	public function remoteVerifyApi ($auth, $data)
	{
		// not used
	}

	public function remoteRefreshLists ($auth, $data)
	{
		// not used
	}

	public function getEmailProviderFieldId ()
	{
		// not used
	}

	public function remoteDeleteSavedSubscribers ()
	{
		$success = $GLOBALS['ISC_CLASS_DB']->Query("TRUNCATE TABLE `[|PREFIX|]subscribers`");
		if ($success)
		{
			return array(
				'success' => true,
				'message' => GetLang('EmailIntegration_ExportOnly_Deleted'),
			);
		}

		return array(
			'success' => false,
			'message' => GetLang('EmailIntegration_ExportOnly_FailedToDelete'),
		);
	}

	/**
	* IP address is not a fixed / supported field in exportonly.
	*
	* @return bool
	*/
	public function getIpProviderFieldId ()
	{
		return false;
	}

	public function updateSubscriptionIP ($email, $ip, $asynchronous = true)
	{
		// not used
	}

	/**
	* Return a count of all internally stored subscribers
	*
	* @return int
	*/
	public function getSubscriptionCount ()
	{
		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		return (int)$db->FetchOne("SELECT COUNT(*) FROM `[|PREFIX|]subscribers`");
	}

	public function getProviderLibClassName ()
	{
		// not used
		return false;
	}
}
