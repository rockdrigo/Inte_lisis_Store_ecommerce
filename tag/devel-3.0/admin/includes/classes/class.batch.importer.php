<?php
/**
 * A generic Interspire Shopping Cart batch (CSV) import class.
 *
 * Extended by methods for importing specific types of data.
 *
 * @author Chris Boulton <cboulton@interspire.com>
 * @id $Id$
 */
class ISC_BATCH_IMPORTER_BASE extends ISC_ADMIN_BASE
{

	/**
	 * @var array The array containing all of the import session data
	 */
	public $ImportSession = array();

	/**
	 * @var string The directory containing a list of importable files
	 */
	public $ServerImportDirectory;

	/**
	 * @var array The array containing the custom fields
	 */
	protected $customFields;

	/**
	 * Runs the import actions
	 */

	public function __construct($customFields=array())
	{
		parent::__construct();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('batch.importer');

		define("ISC_TMP_IMPORT_DIRECTORY", ISC_CACHE_DIRECTORY."/import");

		// Set the import session

		if(!isset($_REQUEST['ImportSession'])) {
			$_REQUEST['ImportSession'] = md5(uniqid(rand(), true).time());
		} else {
			require_once ISC_TMP_IMPORT_DIRECTORY . "/session-" . $_REQUEST['ImportSession'];
			if (isset($ImportSession)) {
				$this->ImportSession = $ImportSession;
			}
		}

		$this->ServerImportDirectory = dirname(__FILE__)."/../../import";

		/**
		 * Set the custom fields
		 */
		if (!is_array($customFields)) {
			$customFields = array();
		}

		$this->customFields = $customFields;

		if (!is_dir(ISC_TMP_IMPORT_DIRECTORY)) {
			isc_mkdir(ISC_TMP_IMPORT_DIRECTORY);
		}

		$GLOBALS['ToDo'] = $_REQUEST['ToDo'];

		if(!isset($_REQUEST['Step'])) {
			$_REQUEST['Step'] = 1;
		}
		switch($_REQUEST['Step'])
		{
			case 2:
				$this->_ImportStep2();
				break;
			case 3:
				$this->_ImportStep3();
				break;
			case 4:
				$this->_Import();
				break;
			case 5:
				$this->_GenerateImportSummary();
				break;
			case 'ImportFrame':
				$this->_ImportStatusFrame();
				break;
			case "ViewReport":
				$this->_GenerateReport($_REQUEST['ReportType']);
			default:
				$this->_ImportStep1();
		}
	}

	/**
	 * Clean up the cache import directory, remove any old files
	 */
	private function _CleanupDirectory()
	{
		$dh = @opendir(ISC_TMP_IMPORT_DIRECTORY);
		$dh = opendir($this->ServerImportDirectory);
		if ($dh === false) {
			return '';
		}

		$html = '';
		while (($file = readdir($dh)) !== false) {
			if (is_file(ISC_TMP_IMPORT_DIRECTORY . "/" . $file) && filemtime(ISC_TMP_IMPORT_DIRECTORY . "/" . $file) < time()-7200) {
				@unlink(ISC_TMP_IMPORT_DIRECTORY . "/" . $file);
			}
		}

		// Try to remove directory (will only succeed if the directory is empty)
		@unlink(ISC_TMP_IMPORT_DIRECTORY);
	}

	/**
	 * Generic first step of the importer. Sets standard fields, builds list of importable files and shows step 1 page.
	 */
	protected function _ImportStep1()
	{
		$this->_CleanupDirectory();

		$GLOBALS['FieldEnclosure'] = EXPORT_FIELD_ENCLOSURE;
		$GLOBALS['FieldSeparator'] = EXPORT_FIELD_SEPARATOR;

		if($_SERVER['REQUEST_METHOD'] == "POST") {
			if(isset($_POST['FieldEnclosure'])) {
				$GLOBALS['FieldEnclosure'] = $_POST['FieldEnclosure'];
			}

			if(isset($_POST['FieldSeparator'])) {
				$GLOBALS['FieldSeparator'] = $_POST['FieldSeparator'];
			}
		}

		// PHP >= 4.3.0 supports the enclosure field, only show it if we have that PHP ver.
		$GLOBALS['ShowEnclosureField'] = '';
		if (version_compare(phpversion(), '4.3.0') < 0) {
			$GLOBALS['ShowEnclosureField'] = 'none';
		}

		$GLOBALS['ServerFiles'] = $this->_GetImportFiles();

		$MaxSize = $this->_GetMaxUploadSize();

		$this->template->assign('ImportMaxSize', $MaxSize);

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('import.'.$this->type.'.step1.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	protected function _GetMaxUploadSize()
	{
		$sizes = array(
			"upload_max_filesize" => ini_get("upload_max_filesize"),
			"post_max_size" => ini_get("post_max_size")
		);
		$max_size = -1;
		foreach($sizes as $size) {
			if (!$size) {
				continue;
			}
			$unit = isc_substr($size, -1);
			$size = isc_substr($size, 0, -1);
			switch(isc_strtolower($unit)) {
				case "g":
					$size *= 1024;
				case "m":
					$size *= 1024;
				case "k":
					$size *= 1024;
			}
			if($max_size == -1 || $size > $max_size) {
				$max_size = $size;
			}
		}
		if($max_size >= 1048576) {
			$max_size = floor($max_size/1048576)."MB";
		} else {
			$max_size = floor($max_size/1024)."KB";
		}
		return $max_size;
	}

	/**
	 * Generic second step of the importer. Handles uploaded files, parses out first row and shows field matching page.
	 */
	protected function _ImportStep2()
	{
		$importer = new ISC_ADMIN_CSVPARSER;

		// Haven't been to this step before, need to parse CSV file
		if (!isset($this->ImportSession['FieldSeparator'])) {
			if (isset($_POST['Headers'])) {
				$this->ImportSession['Headers'] = $_POST['Headers'];
			}

			if (isset($_POST['OverrideDuplicates'])) {
				$this->ImportSession['OverrideDuplicates'] = $_POST['OverrideDuplicates'];
			}

			// Using a file off the server
			if (isset($_POST['serverfile']) && $_POST['serverfile'] != "") {
				$_POST['serverfile'] = basename($_POST['serverfile']);
				if (!is_file($this->ServerImportDirectory . "/".  $_POST['serverfile'])) {
					$this->_ImportStep1(GetLang('ImportInvalidServerFile'), MSG_ERROR);
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					exit;
				}
				$newfilename = $this->ServerImportDirectory . '/' . $_POST['serverfile'];
			} else {
				if (!isset($_FILES['importfile'])) {
					$this->_ImportStep1($this->_GetUploadError(0), MSG_ERROR);
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					exit;
				}
				if (!is_uploaded_file($_FILES['importfile']['tmp_name']) || $_FILES['importfile']['error']) {
					$this->_ImportStep1($this->_GetUploadError($_FILES['importfile']['error']), MSG_ERROR);
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					exit;
				}

				// Move the uploaded file to the cache directory temporarily with a new unique name
				while(true) {
					$newfilename = ISC_TMP_IMPORT_DIRECTORY . '/' . $this->type . '-import-' . md5(uniqid(rand(), true));
					if (!is_file($newfilename)) {
						break;
					}
				}

				if (!move_uploaded_file($_FILES['importfile']['tmp_name'], $newfilename)) {
					$this->_ImportStep1(GetLang('ImportUploadMoveFailed'), MSG_ERROR);
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					exit;
				}
			}

			$separator = html_entity_decode($_POST['FieldSeparator']);
			// convert to actual tab separator
			if (trim(isc_strtoupper($separator)) == "TAB") {
				$separator = "	";
			}

			$this->ImportSession['FieldEnclosure'] = html_entity_decode($_POST['FieldEnclosure']);
			$this->ImportSession['FieldSeparator'] = $separator;

			if (isset($this->ImportSession['FieldSeparator']) && $this->ImportSession['FieldSeparator'] != "") {
				$importer->FieldSeparator = $this->ImportSession['FieldSeparator'];
			}

			if (isset($this->ImportSession['FieldEnclosure']) && $this->ImportSession['FieldEnclosure'] != "") {
				$importer->FieldEnclosure = $this->ImportSession['FieldEnclosure'];
			}

			$this->ImportSession['ImportFile'] = $newfilename;

			$importer->OpenCSVFile($newfilename);
			$header = $importer->FetchNextRecord();
			$importer->CloseCSVFile();

			$this->ImportSession['TotalFileSize'] = filesize($newfilename);
			$this->ImportSession['LastPosition'] = 0;
			$this->ImportSession['PageSize'] = 3000;

			if (!$header) {
				$this->_ImportStep1('Invalid file', MSG_ERROR);
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				exit;
			}

			if (isset($_POST['Headers']) && $_POST['Headers'] == 1) {
				$this->ImportSession['Header'] = $header;
			}
		}
		// Already been past this step once, no need to reparse CSV file
		else {
			$importer->OpenCSVFile($this->ImportSession['ImportFile']);
			$header = $importer->FetchNextRecord();
			$importer->CloseCSVFile();
		}

		$this->_PreFieldMatch($header);

		$fieldlist = '';
		foreach($this->_ImportFields as $column => $field) {
			$fieldlist .= $this->_buildMatchField($column, $field, $header);
		}

		$GLOBALS['ImportFieldList'] = $fieldlist;

		$GLOBALS['ImportSession'] = $_REQUEST['ImportSession'];
		$this->SaveImportSession();

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('import.'.$this->type.'.step2.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	private function _PreFieldMatch($header)
	{
		if (!method_exists($this, '_GetMultiFields')) {
			return;
		}

		$multiFields = $this->_GetMultiFields();

		$tempFields = $this->_ImportFields;

		foreach ($multiFields as $id => $fieldInfo) {
			// count how many instances of a multifield we have in the header
			$matchCount = 0;
			foreach ($header as $field) {
				if (preg_match('/' . $fieldInfo['regex'] . ' - [0-9]+/', $field)) {
					$matchCount++;
				}
			}
			$this->ImportSession['MultiFieldCount'][$id] = $matchCount;

			// remove any related fields from list of fields to match
			foreach ($tempFields as $field => $label) {
				if (substr($field, 0, strlen($fieldInfo['prefix'])) == $fieldInfo['prefix']) {
					unset($this->_ImportFields[$field]);
				}
			}

			// now add a set of fields for each instance
			for($x = 1; $x <= $matchCount; $x++) {
				foreach ($fieldInfo['fields'] as $field => $label) {
					$this->_ImportFields[$field . $x] = $label . ' - ' . $x;
				}
			}
		}
	}

	private function _buildMatchField($column, $field, $header)
	{
		if ($column == "category2" || $column == "category3") {
			return '';
		} else if (is_scalar($column) && $column == 'custom') {
			$html = '';

			foreach ($field as $fieldId => $label) {
				$newColumn = array('custom', $fieldId);
				$html .= $this->_buildMatchField($newColumn, $label, $header);
			}

			return $html;
		}

		$GLOBALS['Extra'] = '';
		if($column == "prodimagefile") {
			$GLOBALS['Extra'] = '<br /><a href="#" onclick="LaunchHelp(\'718\'); return false;" style="color: gray;">'.GetLang('LearnMoreAboutImportingImages').'</a>';
		}
		else if($column == "prodfile") {
			$GLOBALS['Extra'] = '<br /><a href="#" onclick="LaunchHelp(\'728\'); return false;" style="color: gray;">'.GetLang('LearnMoreAboutImportingFiles').'</a>';
		}

		$columnName = $column;
		$columnId = $column;
		if (is_array($column)) {
			$columnName = implode('][', $column);
			$columnId = implode('_', $column);
		}

		$GLOBALS['FieldName'] = sprintf(GetLang('ImportMatchOption'), $field);
		$GLOBALS['OptionName'] = 'LinkField[' . $columnName . ']';
		$GLOBALS['FieldId'] = "Match" . $columnId;
		$GLOBALS['HelpId'] = "Help" . $columnId;
		$GLOBALS['FieldNameHelpTitle'] = str_replace("'", "\\'", sprintf(GetLang('ImportMatchOption'), $field));

		if (is_array($column) && isset($this->customFields[$column[1]]) && strtolower($this->customFields[$column[1]]->record['formfieldtype']) == 'datechooser') {
			$GLOBALS['FieldNameHelp'] = str_replace("'", "\\'", sprintf(GetLang('ImportMatchOptionDateHelp'), $field));
		} else {
			$GLOBALS['FieldNameHelp'] = str_replace("'", "\\'", sprintf(GetLang('ImportMatchOptionHelp'), $field));
		}

		$GLOBALS['Required'] = "&nbsp;";

		if(is_array($this->_RequiredFields) && is_scalar($column) && in_array($column, $this->_RequiredFields)) {
			$GLOBALS['Required'] = "*";
		}

		$optionlist = '';
		$AlreadyMatched = array();
		foreach($header as $k => $value) {
			if(isset($_POST['Headers']) && preg_match("#".preg_quote($field, "#")."#i", $value) && !isset($AlreadyMatched[$columnId])) {
				$AlreadyMatched[$columnId] = 1;
				$optionlist .= "<option value='{$k}' selected='selected'>{$value}</option>";
			}
			else {
				$optionlist .= "<option value='{$k}'>{$value}</option>";
			}
		}
		$GLOBALS['OptionList'] = $optionlist;
		if($column == "category") {
			return $this->template->render('Snippets/ImportMatchOptionCategory.html');
		}
		else {
			return $this->template->render('Snippets/ImportMatchOption.html');
		}
	}

	/**
	 * Generic third step of the importer. Saves matched fields, prepares to run import process (shows "Start Import" page)
	 */
	protected function _ImportStep3()
	{
		// Save the matched pairs of fields
		if(!isset($_POST['LinkField'])) {
			exit;
		}

		if(isset($_POST['categoryType'])) {
			if($_POST['categoryType'] == 'single') {
				unset($_POST['LinkField']['category1']);
				unset($_POST['LinkField']['category2']);
				unset($_POST['LinkField']['category3']);
			}
		}

		$this->ImportSession['FieldList'] = array();

		foreach($_POST['LinkField'] as $column => $index) {
			if ($column == 'custom') {
				$newIndex = array();

				foreach ($index as $fieldId => $val) {
					if ((int)$val == -1 || $val === null) {
						continue;
					} else {
						$newIndex[$fieldId] = $val;
					}
				}

				if (empty($newIndex)) {
					continue;
				} else {
					$index = $newIndex;
				}
			}

			if ((int)$index == -1 || $index === null) {
				continue;
			}
			$this->ImportSession['FieldList'][$column] = $index;
		}

		if(isset($this->_RequiredFields)) {
			foreach($this->_RequiredFields as $field) {
				if(!in_array($field, array_keys($this->ImportSession['FieldList']))) {
					$GLOBALS['ISC_LANG']['ImportNoRequiredField'] = sprintf(GetLang('ImportNoRequiredField'), $this->_ImportFields[$field]);
					$this->_ImportStep2(GetLang('ImportNoRequiredField'), MSG_ERROR);
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					exit;
				}
			}
		}

		// Ready some variables used later on
		$this->ImportSession['Results'] = array(
			"SuccessCount" => '0',
			"Failures" => array(),
			"Duplicates" => array(),
			"Updates" => array(),
			"Warnings" => array()
		);

		$GLOBALS['ImportSession'] = $_REQUEST['ImportSession'];
		$this->SaveImportSession();

		// Show the 'Are you ready to import?' screen
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('import.'.$this->type.'.step3.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	/**
	 * Generate a listing of importable files in the import directory.
	 *
	 * @return string A list of options of importable files for use within a <select>
	 */
	private function _GetImportFiles()
	{
		if(!is_dir($this->ServerImportDirectory)) {
			return '';
		}
		$dh = opendir($this->ServerImportDirectory);
		if($dh === false) {
			return '';
		}

		$html = '';
		while(($file = readdir($dh)) !== false) {
			if($file != "index.php") {
				if(is_file($this->ServerImportDirectory . "/" . $file) && is_readable($this->ServerImportDirectory . "/" . $file)) {
					$html .= '<option value="'.$file.'">'.$file.'</option>';
				}
			}
		}
		closedir($dh);
		return $html;
	}

	/**
	 * Fetch a friendly error message as to why the file upload failed.
	 *
	 * @param int The error ID (from $_FILE)
	 * @return string Friendly error message
	 */
	private function _GetUploadError($error)
	{
		switch($error)
		{
			case 0:
				return sprintf(GetLang('ImportUploadError'), $this->_GetMaxUploadSize());
			case UPLOAD_ERR_INI_SIZE:
				return GetLang('ImportUploadErrorIniSize');
			case UPLOAD_ERR_FORM_SIZE:
				return GetLang('ImportUploadErrorFormSize');
			case UPLOAD_ERR_PARTIAL:
				return GetLang('ImportUploadErrorPartial');
			case UPLOAD_ERR_NO_FILE:
				return GetLang('ImportUploadErrorNoFile');
			case UPLOAD_ERR_NO_TMP_DIR:
				return GetLang('ImportUploadErrorNoTmp');
			case UPLOAD_ERR_CANT_WRITE:
				return GetLang('ImportUploadErrorCantWrite');
			case UPLOAD_ERR_EXTENSION:
				return GetLang('ImportUploadErrorExtension');
		}
	}

	protected function StringToYesNoInt($string)
	{
		switch(isc_strtolower($string)) {
			case "yes":
			case "true":
			case 1:
			case "y":
			case "on":
				return 1;
				break;
			default:
				return 0;
		}
	}

	/**
	 * Show the iframe containing the import status.
	*/
	private function _ImportStatusFrame()
	{
		$this->template->display('pageheader.popup.tpl');

		$GLOBALS['ImportSession'] = $_REQUEST['ImportSession'];
		$GLOBALS['Report'] = $this->_FetchInlineReport();

		$GLOBALS['Type'] = ucfirst($this->type);

		$this->template->display('import.importpopup.tpl');

	}

	/**
	 * Performs the actual import - imports the current chunk from the data file.
	 */
	private function _Import()
	{
		$TypeLang = "Import".ucfirst($this->type);

		//$current_file = @array_shift($this->ImportSession['ChunkList']);

		if(!isset($this->ImportSession['DoneCount'])) {
			$this->ImportSession['DoneCount'] = 0;
		}

		if(!isset($this->ImportSession['StartTime'])) {
			$this->ImportSession['StartTime'] = time();
		}

		$done = 0;
		$percent = 0;
		if (isset($this->ImportSession['DoneCount'])) {
			$done = $this->ImportSession['DoneCount'];
			//$percent = ceil(($done/$this->ImportSession['TotalItems']) * 100);
			$percent = ceil(($this->ImportSession['LastPosition']/$this->ImportSession['TotalFileSize'])*100);
		}

		$importer = new ISC_ADMIN_CSVPARSER;

		if(isset($this->ImportSession['FieldSeparator']) && $this->ImportSession['FieldSeparator'] != "") {
			$importer->FieldSeparator = $this->ImportSession['FieldSeparator'];
		}

		if(isset($this->ImportSession['FieldEnclosure']) && $this->ImportSession['FieldEnclosure'] != "") {
			$importer->FieldEnclosure = $this->ImportSession['FieldEnclosure'];
		}

		$importer->SetRecordFields($this->ImportSession['FieldList']);
		$importer->OpenCSVFile($this->ImportSession['ImportFile'], $this->ImportSession['LastPosition'], 20);

		if ($this->ImportSession['LastPosition'] < $this->ImportSession['TotalFileSize']) {
			// This is our first iteration of the import, headers are enabled so skip past the first row
			if(isset($this->ImportSession['Headers']) && $this->ImportSession['Headers'] == 1 && !isset($this->ImportSession['InImport'])) {
				$importer->FetchNextRecord();
			}
			$this->ImportSession['InImport'] = 1;

			while(($record = $importer->FetchNextRecord(true)) !== false) {
				// Call the function to handle the record
				$this->_ImportRecord($record);

				$currentPosition = $importer->GetCurrentPosition();
				//$newPercent = ceil(($done/$this->ImportSession['TotalItems']* 100));
				$newPercent = ceil(($currentPosition/$this->ImportSession['TotalFileSize'])*100);
				if($newPercent > $percent) {
					$percent = $newPercent;
					$report = $this->_FetchInlineReport();

					// Update the status
					echo "<script type='text/javascript'>\n";
					echo sprintf("self.parent.UpdateImportStatusReport('%s');", str_replace(array("\n", "\r", "'"), array(" ", "", "\\'"), $report));
					$GLOBALS['ISC_LANG']['ImportInProgressDesc'] = sprintf(GetLang('ImportInProgressDesc'), $this->ImportSession['DoneCount']+$importer->GetRecordNum());
					echo sprintf("self.parent.UpdateImportStatus('%s', %d);", str_replace(array("\n", "\r", "'"), array(" ", "", "\\'"), GetLang('ImportInProgressDesc')), $percent);
					echo "</script>\n";
					flush();

				}

				$this->ImportSession['DoneCount']++;
			}

			$this->ImportSession['LastPosition'] = $importer->GetCurrentPosition();
			//$this->ImportSession['DoneCount'] += $importer->GetRecordNum();

		}

		$GLOBALS['ImportSession'] = $_REQUEST['ImportSession'];
		$this->SaveImportSession();

		// Nothing left to import, redirect to the finish page
		if($this->ImportSession['LastPosition'] === false || $this->ImportSession['LastPosition'] >= $this->ImportSession['TotalFileSize']) {

			$locationUrl = "index.php?ToDo=Import".ucfirst($this->type)."&Step=5&ImportSession=".urlencode($GLOBALS['ImportSession']);
			?>
			<script type="text/javascript">
				window.onload = function()
				{
					self.parent.parent.location= '<?php echo $locationUrl; ?>';
				}
			</script>
			<?php
			exit;
		}
		// Still importing, jump to next page
		else {
			$locationUrl = "index.php?ToDo=Import".ucfirst($this->type)."&Step=4&x=".rand(1, 50)."&ImportSession=".$GLOBALS['ImportSession'];
			?>
			<script type="text/javascript">
				window.onload = function()
				{
					setTimeout('window.location="<?php echo $locationUrl; ?>"', 10);
				}
			</script>
			<?php
			exit;
		}
	}

	protected function addImportResult($resultType, $message)
	{
		$line = $this->ImportSession['DoneCount'] + 1;

		$replacements = array('line' => $line, 'message' => $message);
		$this->ImportSession['Results'][$resultType][] = GetLang('ImportResultMessage', $replacements);
	}

	private function _FetchInlineReport()
	{
		$TypeLang = "Import".ucfirst($this->type);

		$report = '';
		foreach(array('SuccessCount', 'Failures', 'Duplicates', 'Updates', 'Warnings') as $type) {
			if($type == 'SuccessCount') {
				$amount = $this->ImportSession['Results'][$type];
			}
			else {
				$amount = count($this->ImportSession['Results'][$type]);
			}
			if($amount == 1) {
				$report .= GetLang($TypeLang . 'Progress_' . $type . '_One');
			}
			else {
				$amount = number_format($amount);
				$report .= sprintf(GetLang($TypeLang . 'Progress_' . $type . '_Many'), $amount);
			}
			$report .= '<br />';
		}
		return $report;
	}

	/**
	 * An import has just finished, this page generates the import summary.
	 */
	protected function _GenerateImportSummary()
	{
		$TypeLang = "Import".ucfirst($this->type);

		$report = '<ul>';
		foreach(array('SuccessCount', 'Updates') as $type) {
			if($type == 'SuccessCount') {
				$amount = $this->ImportSession['Results'][$type];
			}
			else {
				$amount = count($this->ImportSession['Results'][$type]);
			}
			$report .= "<li>\n";
			if($amount == 1) {
				$report .= GetLang($TypeLang . $type . '_One');
			}
			else {
				$amount = number_format($amount);
				$report .= sprintf(GetLang($TypeLang . $type . '_Many'), $amount);
			}
		}

		foreach(array('Duplicates', 'Failures', 'Warnings') as $type) {
			$amount = count($this->ImportSession['Results'][$type]);
			$report .= "<li>";

			if($amount > 0) {
				if($amount == 1) {
					$report .= sprintf(GetLang($TypeLang . $type . '_One_Link'), '"'.$type.'"');
				}
				else {
					$amount = number_format($amount);
					$report .= sprintf(GetLang($TypeLang . $type . '_Many_Link'), $amount, '"'.$type.'"');
				}
			}
			else {
				$report .= sprintf(GetLang($TypeLang . $type . '_Many'), $amount, '"'.$type.'"');
			}
		}

		if($link = $this->getViewImportLink()) {
			$report .= "<li>".$link."</li>";
		}

		$report .= "</ul>";

		$GLOBALS['Message'] = MessageBox(GetLang($TypeLang . 'Successful'), MSG_SUCCESS);
		$GLOBALS['Report'] = $report;

		// Cleanup any remaining files
		if(isset($this->ImportSession['ImportFile']) && is_file($this->ImportSession['ImportFile'])) {
			unlink($this->ImportSession['ImportFile']);
		}
		$GLOBALS['ImportSession'] = $_REQUEST['ImportSession'];
		$this->SaveImportSession();

		$this->_CleanupDirectory();

		// Log this action
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($this->ImportSession['Results']['SuccessCount']);

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('import.'.$this->type.'.step5.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	protected function getViewImportLink()
	{
		return false;
	}

	/**
	 * Generate a specific report for the current import session.
	 *
	 * @param string The report type (Duplicates, Failures)
	 */
	private function _GenerateReport($ReportType)
	{
		switch($ReportType) {
			case "Duplicates":
				$GLOBALS['Heading'] = GetLang('ImportReportDuplicates');
				$GLOBALS['Intro'] = GetLang('ImportReportDuplicatesIntro');

				$duplicates = '';
				foreach($this->ImportSession['Results']['Duplicates'] as $duplicate) {
					$duplicates .= isc_html_escape(trim($duplicate))."\n";
				}
				$GLOBALS['Results'] = $duplicates;
				break;
			case "Warnings":
				$GLOBALS['Heading'] = GetLang('ImportReportWarnings');
				$GLOBALS['Intro'] = GetLang('ImportReportWarningsIntro');

				$warnings = '';
				foreach($this->ImportSession['Results']['Warnings'] as $warning) {
					$warnings .= isc_html_escape(trim($warning))."\n";
				}
				$GLOBALS['Results'] = $warnings;
				break;
			default:
				$GLOBALS['Heading'] = GetLang('ImportReportFailures');
				$GLOBALS['Intro'] = GetLang('ImportReportFailuresIntro');

				$records = '';
				foreach($this->ImportSession['Results']['Failures'] as $record) {
					$records .= isc_html_escape(trim($record))."\n";
				}
				$GLOBALS['Results'] = $records;
				break;
		}
		$this->template->display('pageheader.popup.tpl');

		$this->template->display('import.resultspopup.tpl');

		$this->template->display('pagefooter.popup.tpl');

		exit;
	}

	private function SaveImportSession()
	{
		$ImportSession = var_export($this->ImportSession, true);
		$fp = fopen(ISC_TMP_IMPORT_DIRECTORY."/session-{$_REQUEST['ImportSession']}", "w");
		fwrite($fp,	"<"."?php\n\$ImportSession = $ImportSession;\n\n?".">");
		fclose($fp);
	}

	/**
	 * Import any custom fields
	 *
	 * Method will import the custom fields. Will also handle existing formsessions
	 *
	 * @access private
	 * @param int $type The type of form (the form ID in isc_formfields)
	 * @param array $fields The array of form field session data
	 * @param int $existingSessionId The optional existing form session Id. Default is 0 (new)
	 * @return mixed The form session Id on successful creation, TRUE of successful update if
	 *               $existingSessionId was given, FALSE on error
	 */
	protected function _importCustomFormfields($type, $fields, $existingSessionId=0)
	{
		if (!isId($type) || !is_array($fields) || empty($fields)) {
			return false;
		}

		$formSessData = array();

		foreach ($this->customFields as $fieldId => $field) {
			if (!isset($fields[$fieldId]) || (int)$field->record['formfieldformid'] !== (int)$type) {
				continue;
			}

			$fieldtype = strtolower($field->record['formfieldtype']);

			/**
			 * Explode the value if this is a checkbox field
			 */
			$recordValue = $fields[$fieldId];
			if ($fieldtype == 'checkboxselect') {
				$recordValue = explode(',', $recordValue);
				$recordValue = array_map('trim', $recordValue);
			}

			/**
			 * We'll also need to run the validation. If we fail then just skip it. Unset the
			 * required flag aswell as we can't ask them to fill it in
			 */
			$field->setRequired(false);
			$field->setValue($recordValue, false, true);
			$errmsg = '';

			if (!$field->runValidation($errmsg)) {
				continue;
			}

			$formSessData[$fieldId] = $field->getValue();
		}

		if (!empty($formSessData)) {
			return $GLOBALS['ISC_CLASS_FORM']->saveFormSessionManual($formSessData, $existingSessionId);
		}

		return false;
	}
}