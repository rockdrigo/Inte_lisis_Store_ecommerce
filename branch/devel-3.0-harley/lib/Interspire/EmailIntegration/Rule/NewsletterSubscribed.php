<?php

class Interspire_EmailIntegration_Rule_NewsletterSubscribed extends Interspire_EmailIntegration_Rule
{
	public $eventId = 'onNewsletterSubscribed';

	/**
	* Newsletter subscription rules have no criteria for qualifications, so, return true for all.
	*
	* @return bool
	*/
	public function qualifySubscription(Interspire_EmailIntegration_Subscription $subscription)
	{
		return true;
	}
}
