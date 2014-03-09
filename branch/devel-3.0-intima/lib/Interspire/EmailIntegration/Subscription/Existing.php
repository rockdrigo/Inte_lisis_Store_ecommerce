<?php

/**
* This class represents a subscription which already exists at the email integration provider. It's basically a container for subscription info with some specific method calls to get email address in a module-neutral way. This class is not used for routing new subscriptions to email providers, instances of this class are returned by calls to the findListSubscriber methods of the email integration module classes.
*/
class Interspire_EmailIntegration_Subscription_Existing extends Interspire_EmailIntegration_Subscription
{
	protected $_email;

	protected $_data = array();

	public function getSubscriptionEventId ()
	{
		// this class is not routable
		return false;
	}

	public function getSubscriptionTypeLang ()
	{
		// this class it not displayed anywhere so no language should be necessary
		return '';
	}

	public function getSubscriptionFields ()
	{
		$groups = array(
			'data' => array(),
		);

		foreach ($this->_data as $key => $value) {
			$groups['data'][$key] = $key;
		}

		return $groups;
	}

	public function getSubscriptionEmailField ()
	{
		// this is mainly used by other routable classes to match on the email field but it's not necessary for this class
		return '';
	}

	public function setSubscriptionEmail ($value)
	{
		$this->_email = (string)$value;
	}

	public function getSubscriptionEmail ()
	{
		return $this->_email;
	}

	public function setSubscriptionData ($data)
	{
		$this->_data = $data;
	}

	public function getSubscriptionData ()
	{
		return $this->_data;
	}
}
