<?php

class Job_EmailIntegration_RemoveSubscriberFromList extends Job_EmailIntegration
{
	public function perform()
	{
		$moduleId = $this->args['module'];
		GetModuleById('emailintegration', /** @var EMAILINTEGRATION_MAILCHIMP */$module, $moduleId);

		$listId = $this->args['listId'];
		$email = $this->args['email'];

		$subscription = new Interspire_EmailIntegration_Subscription_Newsletter($email, '');

		$result = $module->removeSubscriberFromList($listId, $subscription, false);

		return $result->success;
	}
}
