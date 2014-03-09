<?php
define('ISC_AJAX_EXPORT_PER_PAGE', 20);

abstract class ISC_ADMIN_AJAXEXPORTER extends ISC_ADMIN_BASE
{
	protected $exportName = '';
	protected $className = '';
	protected $session;
	public $sessionid;
	protected $handle;
	protected $filename;
	protected $resultcount = 0;
	protected $displayAutoExport = false;
	protected $exportIcon = 'export.gif';

	public function __construct()
	{
		parent::__construct();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('ajax.export');
	}

	public function HandleToDo($Do)
	{
		$Do = str_ireplace($this->className, '', $Do);


		if(method_exists($this, $Do)) {
			call_user_func(array($this, $Do));
			exit;
		}
	}

	public function AutoExport()
	{
		if(empty($_GET['t'])) {
			exit;
		}

		// Check that this user token exists
		$query = "
			SELECT pk_userid
			FROM [|PREFIX|]users
			WHERE usertoken='".$GLOBALS['ISC_CLASS_DB']->Quote($_GET['t'])."' AND userstatus=1
		";
		// Invalid token
		if(!$GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
			exit;
		}

		// Perform the export
		$exportResult = $this->Export(true);
		if($exportResult == false) {
			exit;
		}

		$file = APP_ROOT."/../cache/".$exportResult;
		Interspire_Download::downloadFile($file, $exportResult, true, 'application/force-download', GetConfig('CharacterSet'));
	}

	private function CancelExport()
	{
		if(isset($this->filename) && basename($this->filename) == $this->filename && file_exists(APP_ROOT."../cache/".$this->filename)) {
			@unlink(APP_ROOT."../cache/".$this->filename);
			unset($this->filename);
		}

		unset($_SESSION['AjaxExport'][$this->sessionid]);

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('AjaxExportCancelled'), MSG_ERROR);
	}

	private function ExportIntro()
	{
		while (@ob_end_clean());

		// remove any old ajax exports
		unset($_SESSION['AjaxExport']);

		$rnd = rand(1, 99999);
		$this->sessionid = uniqid($rnd, true);
		$_SESSION['AjaxExport'][$this->sessionid] = &$this;

		$this->filename = '';

		$vars = array(
			'ExportGeneratingIntro',
			'ExportGenerating',
			'ExportGeneratedIntro',
			'ExportDownload'
		);

		foreach($vars as $var) {
			$GLOBALS[$var] = GetLang($var, array('exportName' => $this->exportName));
		}

		$GLOBALS['ClassName'] = $this->className;

		if (!$this->displayAutoExport) {
			$GLOBALS['DisplayAutoExport'] = 'none';
		}

		$count = $this->GetResultCount();
		if($count == 0) {
			$GLOBALS['HideExportIntro'] = "none";
		}
		else {
			$GLOBALS['HideNoProducts'] = "none";
		}

		$GLOBALS['ExportSessionId'] = $this->sessionid;
		$GLOBALS['ExportIcon'] = $this->exportIcon;

		$user = GetClass('ISC_ADMIN_AUTH')->GetUser();
		// If the user hasn't already enabled the XML API then chances are
		// they don't have a token for it. We generate a token if that is
		// the case, but we don't explicitly enable the XML API.
		if(!$user['usertoken']) {
			$user['usertoken'] = md5(uniqid(true));
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('users', array(
				'usertoken' => $user['usertoken']
			), "pk_userid='".$user['pk_userid']."'");
		}
		$GLOBALS['ExportUrl'] = GetConfig('ShopPathNormal').'/xml.php?action=Auto' . $this->className . 'Export&t='.$user['usertoken'];

		$this->template->display('ajax.export.tpl');
		exit;
	}

	protected function Export($generateFull = false)
	{
		if(!isset($_REQUEST['start'])) {
			// This is our first visit to the export function. We create the file and the export session

			if(isset($this->filename) && basename($this->filename) == $this->filename && file_exists(APP_ROOT."../cache/".$this->filename)) {
				@unlink(APP_ROOT."../cache/".$this->filename);
				unset($this->filename);
			}

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();

			$this->resultcount = $this->GetResultCount();

			$exportFile = "ajax-export-".time().".xml";
			$this->handle = fopen(APP_ROOT."/../cache/".$exportFile, "w+");
			if(!$this->handle) {
				return false;
			}

			// write header data
			$this->WriteHeader();

			$start = 0;
		}
		else {
			$start = (int)$_REQUEST['start'];
			$exportFile = '';
			if(isset($this->filename) && basename($this->filename) == $this->filename && file_exists(APP_ROOT."/../cache/".$this->filename)) {
				$exportFile = $this->filename;
			}

			if(!$exportFile) {
				echo "<script type='text/javascript'>self.parent.AjaxExportError('".GetLang('AjaxExportInvalidFile')."');</script>";
				exit;
			}

			$this->handle = fopen(APP_ROOT."/../cache/".$exportFile, "a");
			if(!$this->handle) {
				return false;
			}
		}

		@ob_end_clean();

		$result = $this->GetResult($generateFull, $start);

		$wroteRows = $this->WriteRows($result);

		// Export returns true when finished
		if($generateFull || !$wroteRows) {
			$this->WriteFooter();
			if ($generateFull) {
				fclose($this->handle);
				return $exportFile;
			}
			echo "<script type='text/javascript'>self.parent.AjaxExportComplete();</script>";
		}
		else {
			$this->filename = $exportFile;
			// Spit out a progress bar update
			$done = $start + ISC_AJAX_EXPORT_PER_PAGE;
			$percent = ceil($done / $this->resultcount * 100);
			if($percent > 100) {
				$percent = 100;
			}
			echo sprintf("<script type='text/javascript'>self.parent.UpdateAjaxExportProgress('%s');</script>", $percent);
			echo sprintf("<script type='text/javascript'>window.location='index.php?ToDo=AjaxExport&exportsess=%s&action=export&start=%d';</script>", $this->sessionid, $done);
		}

		fclose($this->handle);

		return true;
	}

	private function DownloadExport()
	{
		unset($_SESSION['AjaxExport'][$this->sessionid]);

		$exportFile = '';
		if(isset($this->filename) && basename($this->filename) == $this->filename && file_exists(APP_ROOT."/../cache/".$this->filename)) {
			$exportFile = $this->filename;
		}

		if(!$exportFile) {
			echo "<script type='text/javascript'>self.parent.AjaxExportError('".GetLang('AjaxExportInvalidFile')."');</script>";
			exit;
		}

		unset($this->filename);

		$file = APP_ROOT."/../cache/".$exportFile;

		Interspire_Download::downloadFile($file, $this->GetExportFileName(), true, 'application/force-download', GetConfig('CharacterSet'));
	}

	protected function GetExportFileName()
	{
		return $this->filename;
	}

	abstract protected function GetResultCount();

	abstract protected function WriteHeader();

	abstract protected function WriteFooter();

	abstract protected function GetResult($generateFull = false, $start = 0);

	abstract public function WriteRow($row);

	protected function WriteRows($result)
	{
		$wroteRows = false;
		if (!empty($result)) {
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$wroteRows = true;
				$this->WriteRow($row);
			}
		}

		return $wroteRows;
	}
}
