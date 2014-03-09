<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_ADMIN_REMOTE_SETTINGS_EMAILINTEGRATION extends ISC_ADMIN_REMOTE_BASE
{
	private $customerEntity;

	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings.emailintegration');
		GetLib('class.json');
		parent::__construct();
	}

	/**
	* Handles XHR requests from the settings > email integration forms
	*
	*/
	public function HandleToDo()
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_EmailMarketing)) {
			ISC_JSON::output(GetLang('NoPermission'), false);
			return;
		}

		$data = $_POST;
		if (!isset($data['w'])) {
			return;
		}

		$what = $data['w'];
		unset($data['w'], $data['remoteSection']);

		$method = 'handle' . $what;

		if ($method != 'handletodo' && is_callable(array($this, $method))) {
			return $this->$method($data);
		}
	}

	/**
	* Handles XHR 'providerAction 'requests from the settings > email integration forms, and routes them to the correct provider module
	*
	* @param array $data
	*/
	public function handleProviderAction($data)
	{
		$providerAction = $data['providerAction'];
		$provider = $data['provider'];
		unset($data['providerAction'], $data['provider']);

		GetModuleById('emailintegration', /** @var ISC_EMAILINTEGRATION */$module, $provider);
		if (!$module) {
			ISC_JSON::output('Unknown module: ' . $provider, false);
			return;
		}

		$method = 'remote' . $providerAction;

		if (!is_callable(array($module, $method))) {
			ISC_JSON::output('Provider action not "' . $providerAction . '" found for provider "' . $provider . '"', false);
			return;
		}

		// api auth details will be included in the request, based on the form - this should be separated before sending it to the provider module
		$auth = @$data['auth'];
		if (!$auth) {
			$auth = array();
		}
		unset($data['auth']);

		$result = $module->$method($auth, $data);

		// result expected from provider module is array containing at least 'message' and 'success'; any other elements will be sent back as json too but message and success are stripped out and handled separately due to how ISC_JSON works
		$message = @$result['message'];
		$success = !!$result['success'];
		unset($result['message'], $result['success']);
		ISC_JSON::output($message, $success, $result);
	}
}
