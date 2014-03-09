<?php

/*
 * Controller class for job tracking used primarily by the progress
 * bar jQuery plugin.
 **/
class ISC_ADMIN_JOBSTATUS extends ISC_ADMIN_BASE
{
	/**
	 * Constructs a new job tracker "controller" and sets defaults.
	 *
	 * @return Isc_Admin_JobTracker
	 */
	public function __construct()
	{
		parent::__construct();

		GetLib('class.json');
		// parse the language file
		// $this->engine->loadLangFile('shoppingcomparison');
	}

	/**
	 * Does internal settings page routing. Something like this should be built in.
	 *
	 * @param string $todo The "action" for this "controller"
	 * @return void
	 */
	public function HandleToDo($todo)
	{
		// todo method name
		$todo = $todo . 'Action';

		// the page to render is returned as a string from the action
		$render = null;

		// process action routing
		if (method_exists($this, $todo) || method_exists($this, '__call')) {
			$render = $this->$todo();
		}

		// process template routing
		if ($render && is_string($render)) {
			if ($this->renderLayout) {
				$this->engine->printHeader();
			}

			$this->template->display($render . '.tpl');

			if ($this->renderLayout) {
				$this->engine->printFooter();
			}
		}
	}

	/**
	 * Handles any undefined actions.
	 *
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		// do page not found
	}

	public function jobStatusGetProgressAction()
	{
		if(!isset($_REQUEST['id']) || !($id = $_REQUEST['id']))
			return;

		$controller = Job_Controller::get($id);
		echo ISC_JSON::output($controller->getProgress());
	}
}
