<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_ADMIN_REMOTE_EMAILINTEGRATION extends ISC_ADMIN_REMOTE_BASE
{
	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings.emailintegration');
		GetLib('class.json');
		parent::__construct();
	}

	/**
	* Job ids are md5 so prevent injections in the keystore, escape them to only include a-f A-F and 0-9 characters
	*
	* @param string $id
	* @return string
	*/
	public function escapeJobId($id)
	{
		return preg_replace('#[^a-fA-F0-9]#', '', $id);
	}

	/**
	* Handles XHR requests from the settings > email integration forms and some other locations in the control panel
	*
	* @return mixed
	*/
	public function HandleToDo($data = null)
	{
		if ($data === null) {
			$data = $_POST;
		}

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

	/** @var ISC_EMAILINTEGRATION */
	protected $_module;

	/**
	* Request router for the rule export dialog, routes to various _ruleExport??? methods depending on the exportType and exportStep requested
	*
	* @param array $data
	*/
	public function handleRuleExport($data)
	{
		if (!isset($data['exportType'])) {
			$data['exportType'] = '';
		}

		$method = '_ruleExport' . ucfirst($data['exportType']) . ucfirst($data['exportStep']);
		if (is_callable(array($this, $method))) {
			return $this->$method($data);
		}
	}

	protected function _ruleExportCustomerInit($data)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Customers)) {
			return;
		}

		// create a dummy rule object so we can get language info
		$rule = 'Interspire_EmailIntegration_Rule_' . $data['exportRule'];
		/** @var Interspire_EmailIntegration_Rule */
		$rule = new $rule('', '', 0, '');
		$this->template->assign('rule', $rule);

		$this->template->assign('modalTitle', 'Export Customers via ' . $rule->getPluralName());

		$this->template->display('emailintegration.ruleexport.tpl');
	}

	/**
	* Begins an actual export job sequence based on requested data -- the job class itself will validate most of this info before attempting an export
	*
	* @param array $data
	*/
	public function _ruleExportCustomerCommence($data)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Customers)) {
			return;
		}

		$keystore = Interspire_KeyStore::instance();

		// find a unique export id to use
		do {
			$id = md5(uniqid('',true));
		} while ($keystore->exists('email:rule_export:id:' . $id));
		$keystore->set('email:rule_export:id:' . $id, $id);

		$prefix = 'email:rule_export:' . $id;

		if (!isset($data['exportSearch'])) {
			$data['exportSearch'] = array();
		}

		$keystore->set($prefix . ':started', time());
		$keystore->set($prefix . ':abort', 0);
		$keystore->set($prefix . ':skip', 0);
		$keystore->set($prefix . ':type', $data['exportType']);
		$keystore->set($prefix . ':rule', $data['exportRule']);
		$keystore->set($prefix . ':search', ISC_JSON::encode($data['exportSearch']));
		$keystore->set($prefix . ':success_count', 0);
		$keystore->set($prefix . ':error_count', 0);
		$keystore->set($prefix . ':doubleoptin', $data['exportDoubleOptin']);
		$keystore->set($prefix . ':updateexisting', $data['exportUpdateExisting']);

		// so we can send an email later, or diagnose troublesome users
		$user = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUser();
		$keystore->set($prefix . ':owner:id', $user['pk_userid']);
		$keystore->set($prefix . ':owner:username', $user['username']);
		$keystore->set($prefix . ':owner:email', $user['useremail']);

		$jobData = array(
			'id' => $id,
		);

		$json = array(
			'success' => (bool)Interspire_TaskManager::createTask('emailintegration', 'Job_EmailIntegration_RuleExport', $jobData),
			'id' => $id,
		);

		if (isset($data['return']) && $data['return']) {
			return $json;
		}

		// @codeCoverageIgnoreStart
		ISC_JSON::output($json);
		// @codeCoverageIgnoreEnd
	}

	/**
	* Request router for the module export dialog, routes to various _moduleExport??? methods depending on the exportType and exportStep requested
	*
	* @param array $data
	*/
	public function handleModuleExport($data)
	{
		if (!isset($data['exportType'])) {
			$data['exportType'] = '';
		}

		$method = '_moduleExport' . ucfirst($data['exportType']) . ucfirst($data['exportStep']);
		if (is_callable(array($this, $method))) {
			if (isset($data['exportModule'])) {
				GetModuleById('emailintegration', $this->_module, $data['exportModule']);
			}

			return $this->$method($data);
		}
		return false;
	}

	/**
	* Handle remote request to mark an export as aborted, for any type
	*
	* @param array $data
	*/
	protected function _moduleExportAbort($data)
	{
		$id = $this->escapeJobId($data['exportId']);

		// flag export as aborted
		$keystore = Interspire_KeyStore::instance();
		$keystore->set('email:module_export:' . $id . ':abort', 1);

		// if the export job has crashed, the abort will never be detected and data will never be cleaned up
		Interspire_TaskManager::createTask('emailintegration', 'Job_EmailIntegration_ModuleExport', array(
			'id' => $id,
		));

		ISC_JSON::output(array(
			'success' => true
		));
	}

	/**
	* Initialise step of a customer module export -- outputs the dialog html for use by the client-side FSM
	*
	* @param array $data
	*/
	protected function _moduleExportCustomerInit($data)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Customers)) {
			return;
		}

		$this->template->assign('modalTitle', 'Export Customers to ' . $this->_module->GetName());
		$this->template->assign('module', $this->_module);
		$this->template->assign('lists', $this->_module->getLists());
		$this->template->assign('typePlural', 'Customers');
		$this->template->assign('typeSingular', 'Customer');

		$user = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUser();
		$this->template->assign('useremail', $user['useremail']);

		$this->template->display('emailintegration.moduleexport.tpl');
	}

	/**
	* Initialise step of an order module export -- outputs the dialog html for use by the client-side FSM
	*
	* @param array $data
	*/
	protected function _moduleExportOrderInit($data)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Orders)) {
			return;
		}

		$this->template->assign('modalTitle', 'Export Orders to ' . $this->_module->GetName());
		$this->template->assign('module', $this->_module);
		$this->template->assign('lists', $this->_module->getLists());
		$this->template->assign('typePlural', 'Orders');
		$this->template->assign('typeSingular', 'Order');

		$user = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUser();
		$this->template->assign('useremail', $user['useremail']);

		$this->template->display('emailintegration.moduleexport.tpl');
	}

	protected function _moduleExportCustomerCommence($data)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Customers)) {
			return false;
		}

		return $this->_moduleExportCommenceCommon($data);
	}

	protected function _moduleExportOrderCommence($data)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Orders)) {
			return false;
		}

		return $this->_moduleExportCommenceCommon($data);
	}

	/**
	* Begins an actual export job sequence based on requested data -- the job class itself will validate most of this info before attempting an export
	*
	* @param array $data
	*/
	protected function _moduleExportCommenceCommon($data)
	{
		$keystore = Interspire_KeyStore::instance();

		// find a unique export id to use
		do {
			$id = md5(uniqid('',true));
		} while ($keystore->exists('email:module_export:id:' . $id));
		$keystore->set('email:module_export:id:' . $id, $id);

		$prefix = 'email:module_export:' . $id;

		if (!isset($data['exportSearch'])) {
			$data['exportSearch'] = array();
		}

		if (!isset($data['exportMap'])) {
			$data['exportMap'] = array();
		}

		$keystore->set($prefix . ':started', time());
		$keystore->set($prefix . ':abort', 0);
		$keystore->set($prefix . ':skip', 0);
		$keystore->set($prefix . ':type', $data['exportType']);
		$keystore->set($prefix . ':module', $data['exportModule']);
		$keystore->set($prefix . ':list', $data['exportList']);
		$keystore->set($prefix . ':search', ISC_JSON::encode($data['exportSearch']));
		$keystore->set($prefix . ':map', ISC_JSON::encode($data['exportMap']));
		$keystore->set($prefix . ':success_count', 0);
		$keystore->set($prefix . ':error_count', 0);
		$keystore->set($prefix . ':doubleoptin', $data['exportDoubleOptin']);
		$keystore->set($prefix . ':updateexisting', $data['exportUpdateExisting']);

		// so we can send an email later, or diagnose troublesome users
		$user = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUser();
		$keystore->set($prefix . ':owner:id', $user['pk_userid']);
		$keystore->set($prefix . ':owner:username', $user['username']);
		$keystore->set($prefix . ':owner:email', $user['useremail']);

		$jobData = array(
			'id' => $id,
		);

		$json = array(
			'success' => (bool)Interspire_TaskManager::createTask('emailintegration', 'Job_EmailIntegration_ModuleExport', $jobData),
			'id' => $id,
		);

		if (isset($data['return']) && $data['return']) {
			return $json;
		}

		ISC_JSON::output($json);
	}
}
