<?php

class ISC_ADMIN_CUSTOM_FIELDS extends ISC_ADMIN_BASE {

	protected $renderLayout = true;

	public function __construct()
	{
		parent::__construct();
		$this->engine->LoadLangFile('products');
	}
	
	public function HandleToDo($todo)
	{
		if (!$this->auth->HasPermission(AUTH_Manage_Variations)) {
			$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		GetLib('class.json');

		// todo method name
		$todo = $todo . 'Action';

		$render = null;

		if (method_exists($this, $todo)) {
			$render = $this->$todo();
		}

		// process template routing
		if ($render && is_string($render)) {
			if ($this->renderLayout) {
				$this->engine->printHeader();
			}

			$this->template->display($render);

			if ($this->renderLayout) {
				$this->engine->printFooter();
			}
		}
	}
	
	public function customFieldsAction($MsgDesc = "", $MsgStatus = "", $PreservePost=false)
	{
		if($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		if (!$this->auth->HasPermission(AUTH_Manage_Products)) {
			$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		return 'custom.fields.tpl';
	}
}
?>