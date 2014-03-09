<?php

class Job_EmailIntegration_UpdateSubscriptionIP extends Job_EmailIntegration
{
	public function perform()
	{
		$moduleId = $this->args['module'];
		GetModuleById('emailintegration', /** @var EMAILINTEGRATION_MAILCHIMP */$module, $moduleId);
		return $module->updateSubscriptionIP($this->args['email'], $this->args['ip'], false);
	}
}
