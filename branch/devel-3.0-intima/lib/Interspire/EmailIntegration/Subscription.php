<?php

abstract class Interspire_EmailIntegration_Subscription
{
	// the numeric value of these preferences determines display order on front end
	// if these values change, make sure to update language text for EmailFormatDescription_<n>
	const FORMAT_PREF_NONE = 0;
	const FORMAT_PREF_HTML = 1;
	const FORMAT_PREF_TEXT = 2;
	const FORMAT_PREF_MOBILE = 3;

	abstract public function getSubscriptionTypeLang();

	abstract public function getSubscriptionEventId();

	abstract public function getSubscriptionFields();

	abstract public function getSubscriptionEmail();

	abstract public function getSubscriptionEmailField();

	abstract public function getSubscriptionData();

	protected $_subscriptionIP;

	public function getSubscriptionIP()
	{
		return $this->_subscriptionIP;
	}

	public function setSubscriptionIP($value)
	{
		$this->_subscriptionIP = $value;
	}

	protected $_doubleOptIn = true;

	public function getDoubleOptIn()
	{
		return $this->_doubleOptIn;
	}

	public function setDoubleOptIn($value)
	{
		$this->_doubleOptIn = (bool)$value;
	}

	protected $_emailFormatPreference = self::FORMAT_PREF_NONE;

	public function getEmailFormatPreference()
	{
		return $this->_emailFormatPreference;
	}

	public function setEmailFormatPreference($value)
	{
		$this->_emailFormatPreference = (int)$value;
	}

	protected $_sendWelcome = true;

	public function getSendWelcome()
	{
		return $this->_sendWelcome;
	}

	public function setSendWelcome($value)
	{
		$this->_sendWelcome = (bool)$value;
	}

	/** @var bool whether or not to update subscription if it already exists at provider */
	protected $_updateExisting = true;

	/** @return bool whether or not to update subscription if it already exists at provider */
	public function getUpdateExisting()
	{
		return $this->_updateExisting;
	}

	/** @param bool $value whether or not to update subscription if it already exists at provider */
	public function setUpdateExisting($value)
	{
		$this->_updateExisting = (bool)$value;
	}

	/**
	* Route this subscription to email providers based on rules configured for the store
	*
	* @param bool $asynchronous
	* @return mixed An array of Interspire_EmailIntegration_AddSubscriberResult objects for synchronous calls, otherwise null
	*/
	public function routeSubscription($asynchronous = true)
	{
		$event = $this->getSubscriptionEventId();
		if (!$event) {
			// this subscription type does not support routing via subscription rules
			return;
		}

		return ISC_EMAILINTEGRATION::routeSubscription($event, $this, $asynchronous);
	}

	/**
	* Retrieves subscription fields for this type without group information. If any fields inside groups have id collissions, fields in subsequent groups will overwrite fields from earlier groups. It's assumed that fields with the same id share the same configuration.
	*
	* @return array An array of field id => Interspire_EmailIntegration_Field instance
	*/
	public function getFlatSubscriptionFields()
	{
		$flattened = array();
		$groups = $this->getSubscriptionFields();
		foreach ($groups as $fields) {
			foreach ($fields as $fieldId => $field) {
				$flattened[$fieldId] = $field;
			}
		}
		return $flattened;
	}
}
