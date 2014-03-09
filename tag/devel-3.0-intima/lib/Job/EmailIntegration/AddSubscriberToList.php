<?php

class Job_EmailIntegration_AddSubscriberToList extends Job_EmailIntegration
{
	public function perform()
	{
		$moduleId = $this->args['module'];
		GetModuleById('emailintegration', /** @var EMAILINTEGRATION_MAILCHIMP */$module, $moduleId);

		$listId = $this->args['listId'];
		$subscription = unserialize($this->args['subscription']);
		$fields = $this->args['fields'];

		$result = $module->addSubscriberToList($listId, $subscription, $fields, false);

		return $result->success;
	}
}
