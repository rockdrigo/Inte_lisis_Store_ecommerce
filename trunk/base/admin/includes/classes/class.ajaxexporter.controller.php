<?php
class ISC_ADMIN_AJAXEXPORTER_CONTROLLER extends ISC_ADMIN_BASE {
	public function Export()
	{
		if (!empty($_REQUEST['exportsess']) && !empty($_SESSION['AjaxExport'][$_REQUEST['exportsess']])) {
			$sessionid = $_REQUEST['exportsess'];
		}
		else {
			return;
		}

		//$GLOBALS['ISC_CLASS_LOG']->LogSystemError('php', 'session', '<pre>' . var_export($_SESSION['AjaxExport'], true) . '</pre>');
		$exporter = &$_SESSION['AjaxExport'][$sessionid];
		$exporter->sessionid = $sessionid;
		$exporter->HandleToDo($_REQUEST['action']);
	}
}
