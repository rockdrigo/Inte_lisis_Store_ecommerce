<?php

abstract class Job_EmailIntegration extends Job_Store_Abstract
{
	/**
	* @var ISC_ADMIN_ENGINE
	*/
	protected $_engine;

	/**
	* @var ISC_LOG
	*/
	protected $_log;

	public function setUp()
	{
		parent::setUp();
		$this->_engine = getClass('ISC_ADMIN_ENGINE');
		$this->_engine->LoadLangFile('settings.emailintegration');
		$this->_log = $GLOBALS['ISC_CLASS_LOG'];
	}

	protected function _logDebug($summary, $message = '')
	{
		$this->_log->LogSystemDebug('emailintegration', GetLang('EmailIntegration_Log_Prefix', array('id' => $this->args['id'])) . ' ' . $summary, $message);
	}

	protected function _logError($summary, $message = '')
	{
		$this->_log->LogSystemError('emailintegration', GetLang('EmailIntegration_Log_Prefix', array('id' => $this->args['id'])) . ' ' . $summary, $message);
	}

	protected function _logSuccess($summary, $message = '')
	{
		$this->_log->LogSystemSuccess('emailintegration', GetLang('EmailIntegration_Log_Prefix', array('id' => $this->args['id'])) . ' ' . $summary, $message);
	}

	protected function _logNotice($summary, $message = '')
	{
		$this->_log->LogSystemNotice('emailintegration', GetLang('EmailIntegration_Log_Prefix', array('id' => $this->args['id'])) . ' ' . $summary, $message);
	}
}
