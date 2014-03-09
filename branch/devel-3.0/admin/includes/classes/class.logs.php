<?php
class ISC_ADMIN_LOGS extends ISC_ADMIN_BASE
{
	public function __construct()
	{
		parent::__construct();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('logs');

		if(!gzte11(ISC_LARGEPRINT)) {
			$GLOBALS[base64_decode('SGlkZVN0YWZmTG9ncw==')] = "none";
			$GLOBALS[base64_decode('SGlkZUFkbWluTG9ncw==')] = "none";
		}
	}

	public function HandleToDo($Do)
	{
		switch(isc_strtolower($Do)) {
			case "administratorloggrid":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs) && gzte11(ISC_LARGEPRINT)) {
					echo $this->AdministratorLogGrid();
					die();
				}
				else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "systemloggrid":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
					echo $this->SystemLogGrid();
					die();
				}
				else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "deletesystemlogs":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->DeleteSystemLogEntries();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					die();
				}
				else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "deleteallsystemlogs":

				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ClearSystemLog();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					die();
				}
				else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "deleteadminlogs":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->DeleteAdminLogEntries();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					die();
				}
				else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			case "deletealladminlogs":
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ClearAdminLog();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					die();
				}
				else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
			default:
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
					$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", "Store Logs" => "index.php?ToDo=systemLog");

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ShowSystemLog();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					die();
				}
				else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				break;
		}
	}

	private function ClearAdminLog()
	{
		$_REQUEST['CurrentTab'] = 1;
		$query = "DELETE FROM [|PREFIX|]administrator_log";
		$GLOBALS["ISC_CLASS_DB"]->Query($query);
		$err = $GLOBALS["ISC_CLASS_DB"]->GetError();

		if ($err[0] != "") {
			FlashMessage($err[0], MSG_ERROR, 'index.php?ToDo=systemLog&CurrentTab=1');
		} else {
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
			FlashMessage(GetLang('AdminLogClearedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=systemLog&CurrentTab=1');
		}
	}

	private function DeleteAdminLogEntries()
	{
		if (isset($_POST['delete'])) {
			$_REQUEST['CurrentTab'] = 1;
			$ids = implode(",", array_map("intval", $_POST['delete']));
			$query = sprintf("DELETE FROM [|PREFIX|]administrator_log WHERE logid IN (%s)", $ids);
			$GLOBALS["ISC_CLASS_DB"]->Query($query);
			$err = $GLOBALS["ISC_CLASS_DB"]->GetError();

			if ($err[0] != "") {
				FlashMessage($err[0], MSG_ERROR, 'index.php?ToDo=systemLog&CurrentTab=1');
			} else {
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
				FlashMessage(GetLang('AdminLogsDeletedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=systemLog&CurrentTab=1');
			}
		} else {
			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
				$this->ShowSystemLog();
			} else {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			}
		}
	}

	private function ClearSystemLog()
	{
		$query = "DELETE FROM [|PREFIX|]system_log";
		$GLOBALS["ISC_CLASS_DB"]->Query($query);
		$err = $GLOBALS["ISC_CLASS_DB"]->GetError();

		if ($err[0] != "") {
			FlashMessage($err[0], MSG_ERROR, 'index.php?ToDo=systemLog');
		} else {
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();

			FlashMessage(GetLang('SystemLogClearedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=systemLog');
		}
	}

	private function DeleteSystemLogEntries()
	{
		if (isset($_POST['delete'])) {
			$ids = implode(",", array_map("intval", $_POST['delete']));
			$query = sprintf("DELETE FROM [|PREFIX|]system_log WHERE logid IN (%s)", $ids);
			$GLOBALS["ISC_CLASS_DB"]->Query($query);
			$err = $GLOBALS["ISC_CLASS_DB"]->GetError();

			if ($err[0] != "") {
				FlashMessage($err[0], MSG_ERROR, 'index.php?ToDo=systemLog');
			} else {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
				FlashMessage(GetLang('SystemLogsDeletedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=systemLog');
			}
		} else {
			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
				$this->ShowSystemLog();
			} else {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			}
		}
	}

	private function AdministratorLogGrid()
	{
		if(!gzte11(ISC_LARGEPRINT)) {
			return '';
		}

		// Show a list of administrator log options in a table
		$page =	 1;
		$start = 0;
		$numEntries = 0;
		$numPages = 0;
		$GLOBALS['LogGrid'] = '';
		$GLOBALS['Nav'] = '';

		if(isset($_GET['sortOrder']) && $_GET['sortOrder'] == "asc") {
			$sortOrder = "asc";
		}
		else {
			$sortOrder = "desc";
		}

		$validSortFields = array("username", "logip", "logdate");
		if(isset($_GET['sortField']) && in_array($_GET['sortField'], $validSortFields)) {
			$sortField = $_GET['sortField'];
			SaveDefaultSortField("AdministratorLog", $_REQUEST['sortField'], $sortOrder);
		}
		else {
			list($sortField, $sortOrder) = GetDefaultSortField("AdministratorLog", "logdate", $sortOrder);
		}

		if (isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		} else {
			$page = 1;
		}

		$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
		$GLOBALS['SortURL'] = $sortURL;

		$where = '1=1';
		$GLOBALS['HideClearAdminResults'] = 'none';

		if(isset($_REQUEST['userid']) && $_REQUEST['userid'] > 0) {
			$where .= sprintf(" AND loguserid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote((int)$_REQUEST['userid']));
			$sortURL .= sprintf("&userid=%d", (int)$_REQUEST['userid']);
			$GLOBALS['HideClearAdminResults'] = '';
		}

		// Limit the number of of log entries returned
		if ($page == 1) {
			$start = 1;
		} else {
			$start = ($page * ISC_LOG_ENTRIES_PER_PAGE) - (ISC_LOG_ENTRIES_PER_PAGE-1);
		}
		$start = $start-1;

		// Get the results for the query
		$query = sprintf("SELECT COUNT(logid) FROM [|PREFIX|]administrator_log WHERE %s", $where);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$numEntries = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		$numPages = ceil($numEntries / ISC_LOG_ENTRIES_PER_PAGE);

		if($numEntries > ISC_LOG_ENTRIES_PER_PAGE) {
			$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
			$GLOBALS['Nav'] .= BuildPagination($numEntries, ISC_LOG_ENTRIES_PER_PAGE, $page, sprintf("index.php?ToDo=administratorLogGrid%s", $sortURL));
		}
		else {
			$GLOBALS['Nav'] = "";
		}
		$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');

		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;

		$sortLinks = array(
			"Name" => "username",
			"Date" => "logdate",
			"IP" => "logip"
			);
		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=administratorLogGrid&amp;".$sortURL."&amp;page=".$page, $sortField, $sortOrder);

		if ($numEntries > 0) {
			$query = sprintf("
				SELECT l.*, u.username
				FROM [|PREFIX|]administrator_log l
				LEFT JOIN [|PREFIX|]users u ON (u.pk_userid=l.loguserid)
				WHERE %s
				ORDER BY %s %s",
				$where,
				$sortField,
				$sortOrder
				);
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_LOG_ENTRIES_PER_PAGE);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				if(!$row['username']) {
					$GLOBALS['Username'] = GetLang('Unknown');
				}
				else {
					$GLOBALS['Username'] = sprintf("<a href='index.php?ToDo=editUser&amp;userId=%d'>%s</a>", $row['loguserid'], $row['username']);
				}
				$data = @unserialize($row['logdata']);
				$GLOBALS['LogId'] = $row['logid'];
				$GLOBALS['Action'] = $this->_GetAdministratorAction($row['logtodo'], $data);
				$GLOBALS['Date'] = isc_date(GetConfig('ExtendedDisplayDateFormat'), $row['logdate']);
				$GLOBALS['Ip'] = $row['logip'];

				$GLOBALS['LogGrid'] .= $this->template->render('logs.administrator.row.tpl');
			}
			$GLOBALS['DisableDelete'] = '';
		}
		else {
			$GLOBALS['LogGrid'] = '';
		}

		if(!$GLOBALS['LogGrid']) {
			if($GLOBALS['HideClearAdminResults'] == "none") {
				$msg = GetLang('AdminLogEmpty');
			}
			else {
				$query = sprintf("SELECT username FROM [|PREFIX|]users WHERE pk_userid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote((int)$_REQUEST['userid']));
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$username = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
				$msg = sprintf(GetLang('AdminLogNoResults'), $username);
			}
			$GLOBALS['LogGrid'] = "<tr>
				<td colspan=\"5\"><em>".$msg."</em></td>
				</tr>";
		}

		// Get the list of Administrators
		$GLOBALS['AdministratorList'] = '';
		$query = "SELECT pk_userid, username FROM [|PREFIX|]users ORDER BY username ASC";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$selected = '';
			if(isset($_REQUEST['userid']) && $_REQUEST['userid'] == $row['pk_userid']) {
				$selected = "selected=\"selected\"";
			}
			$GLOBALS['AdministratorList'] .= sprintf("<option value=\"%s\" %s>%s</option>", (int) $row['pk_userid'], $selected, isc_html_escape($row['username']));
		}

		return $this->template->render('logs.administrator.grid.tpl');

	}

	private function SystemLogGrid()
	{
		// Show a the system log in a data grid.
		$page =	 1;
		$start = 0;
		$numEntries = 0;
		$numPages = 0;
		$GLOBALS['LogGrid'] = '';
		$GLOBALS['Nav'] = '';

		if(isset($_GET['sortOrder']) && $_GET['sortOrder'] == "asc") {
			$sortOrder = "asc";
		}
		else {
			$sortOrder = "desc";
		}

		$validSortFields = array("logtype", "logmodule", "logseverity", "logsummary", "logdate");
		if(isset($_GET['sortField']) && in_array($_GET['sortField'], $validSortFields)) {
			$sortField = $_GET['sortField'];
			SaveDefaultSortField("SystemLog", $_REQUEST['sortField'], $sortOrder);
		}
		else {
			list($sortField, $sortOrder) = GetDefaultSortField("SystemLog", "logdate", $sortOrder);
		}

		if (isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		} else {
			$page = 1;
		}

		$GLOBALS['HideClearResults'] = "none";
		$where = "1=1";
		$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
		$GLOBALS['SortURL'] = $sortURL;

		if(isset($_REQUEST['logseverity']) && $_REQUEST['logseverity'] != 0) {
			$where .= sprintf(" AND logseverity='%d'", $GLOBALS['ISC_CLASS_DB']->Quote((int)$_REQUEST['logseverity']));
			$sortURL .= sprintf("&logseverity=%d", (int)$_REQUEST['logseverity']);
			$GLOBALS['Severity'.(int)$_REQUEST['logseverity'].'Selected'] = "selected=\"selected\"";
			$GLOBALS['HideClearResults'] = '';
		}

		if(isset($_REQUEST['logtype']) && $_REQUEST['logtype'] != '') {
			$where .= sprintf(" AND logtype='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['logtype']));
			$sortURL .= sprintf("&logtype=%s", urlencode($_REQUEST['logtype']));
			$GLOBALS['HideClearResults'] = '';
		}

		if(isset($_REQUEST['logsummary']) && $_REQUEST['logsummary'] != '') {
			$where .= sprintf(" AND (logsummary LIKE '%%%s%%' OR logmodule LIKE '%%%s%%')", $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['logsummary']), $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['logsummary']));
			$sortURL .= sprintf("&logsummary=%d", $_REQUEST['logsummary']);
			$GLOBALS['HideClearResults'] = '';
			$GLOBALS['SummaryValue'] = $_REQUEST['logsummary'];
		}

		// Are there any log types we're hiding?
		if(GetConfig('HiddenStoreLogTypes')) {
			$hiddenTypes = explode(',', GetConfig('HiddenStoreLogTypes'));
			$hiddenTypes = array_map('trim', $hiddenTypes);
			$where .= " AND logtype NOT IN ('".implode("','", $hiddenTypes)."')";
		}

		// Build the list of log types
		$logTypes = array(
			'general' => GetLang('LogSearchGeneral'),
			'payment' => GetLang('LogSearchPayment'),
			'shipping' => GetLang('LogSearchShipping'),
			'notification' => GetLang('LogSearchNotification'),
			'sql' => GetLang('LogSearchSQL'),
			'php' => GetLang('LogSearchPHP'),
			'emailintegration' => GetLang('LogSearchEmailIntegration'),
			'ebay' => GetLang('LogSearchEbay'),
			'shoppingcomparison' => GetLang('LogSearchShoppingComparison'),
		);

		$GLOBALS['LogSearchTypeSelect'] = '';
		foreach($logTypes as $type => $label) {
			if(isset($hiddenTypes) && in_array($type, $hiddenTypes)) {
				continue;
			}

			$sel = '';
			if(isset($_REQUEST['logtype'])) {
				$sel = 'selected="selected"';
			}

			$GLOBALS['LogSearchTypeSelect'] .= '<option value="'.$type.'" '.$sel.'">'.isc_html_escape($label).'</option>';
		}

		// Limit the number of of log entries returned
		if ($page == 1) {
			$start = 1;
		} else {
			$start = ($page * ISC_LOG_ENTRIES_PER_PAGE) - (ISC_LOG_ENTRIES_PER_PAGE-1);
		}
		$start = $start-1;

		// Get the results for the query
		$query = sprintf("SELECT COUNT(logid) FROM [|PREFIX|]system_log WHERE %s", $where);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$numEntries = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		$numPages = ceil($numEntries / ISC_LOG_ENTRIES_PER_PAGE);

		if($numEntries > ISC_LOG_ENTRIES_PER_PAGE) {
			$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
			$GLOBALS['Nav'] .= BuildPagination($numEntries, ISC_LOG_ENTRIES_PER_PAGE, $page, sprintf("index.php?ToDo=systemLogGrid%s", $sortURL));
		}
		else {
			$GLOBALS['Nav'] = "";
		}
		$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');

		$GLOBALS['SortField'] = $sortField;

		$GLOBALS['SortOrder'] = $sortOrder;

		$sortLinks = array(
			"Severity" => "logseverity",
			"Type" => "logtype",
			"Module" => "logmodule",
			"Summary" => "logsummary",
			"Date" => "logdate"
			);
		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=systemLogGrid&amp;".$sortURL."&amp;page=".$page, $sortField, $sortOrder);

		if ($numEntries > 0) {
			$query = sprintf("SELECT * FROM [|PREFIX|]system_log WHERE %s ORDER BY %s %s, logid DESC", $where, $sortField, $sortOrder);
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_LOG_ENTRIES_PER_PAGE);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				if(!$row['logsummary']) {
					$row['logsummary'] = $row['logmsg'];
				}
				$GLOBALS['LogId'] = $row['logid'];
				$GLOBALS['Summary'] = $row['logsummary'];

				$GLOBALS['Type'] = GetLang('LogType'.ucfirst($row['logtype']));
				if($row['logmodule']) {
					$GLOBALS['Module'] = $row['logmodule'];
				}
				else {
					$GLOBALS['Module'] = GetLang('NA');
				}

				if($row['logmsg'] && $row['logmsg'] != $row['logsummary']) {
					$GLOBALS['ExpandLink'] = sprintf("<a href=\"#\" onclick=\"ShowLogInfo('%d'); return false;\"><img id=\"expand%d\" src=\"images/plus.gif\" align=\"left\" width=\"19\" class=\"ExpandLink\" height=\"16\" title=\"%s\" border=\"0\"></a>", $row['logid'], $row['logid'], GetLang('ClickToViewLogInfo'));
				}
				else {
					$GLOBALS['ExpandLink'] = '&nbsp;';
				}

				$GLOBALS['SeverityClass'] = "severity".$row['logseverity'];
				$GLOBALS['Severity'] = GetLang('LogSeverity'.$row['logseverity']);

				$GLOBALS['Date'] = isc_date(GetConfig('ExtendedDisplayDateFormat'), $row['logdate']);

				$GLOBALS['LogGrid'] .= $this->template->render('logs.system.row.tpl');
			}
		}
		else {
			$GLOBALS['LogGrid'] = '';
		}
		$GLOBALS['DisableDelete'] = '';

		if(!$GLOBALS['LogGrid']) {
			if($GLOBALS['HideClearResults'] == "none") {
				$msg = GetLang('SystemLogEmpty');
			}
			else {
				$msg = GetLang('SystemLogNoResults');
			}
			$GLOBALS['DisableDelete'] = "disabled=\"disabled\"";
			$GLOBALS['LogGrid'] = "<tr>
				<td colspan=\"8\"><em>".$msg."</em></td>
				</tr>";
		}

		return $this->template->render('logs.system.grid.tpl');

	}

	private function ShowSystemLog($MsgDesc = "", $MsgStatus = "")
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$GLOBALS['CurrentTab'] = 0;
		$GLOBALS['SystemLog'] = $GLOBALS['AdministratorLog'] = '';

		if(isset($_REQUEST['CurrentTab'])) {
			$GLOBALS['CurrentTab'] = (int)$_REQUEST['CurrentTab'];
		}

		if(GetConfig('SystemLogging')) {
			$GLOBALS['SystemLog'] = $this->SystemLogGrid();
			if(!$GLOBALS['SystemLog']) {
				$GLOBALS['CurrentTab'] = 1;
				$GLOBALS['HideSystemLog'] = "none";
			}
		}
		else {
			$GLOBALS['HideSystemLog'] = "none";
			$GLOBALS['CurrentTab'] = 1;
		}

		if(GetConfig('AdministratorLogging')) {
			$GLOBALS['AdministratorLog'] = $this->AdministratorLogGrid();
			if(!$GLOBALS['AdministratorLog']) {
				$GLOBALS['HideAdminLog'] = 'none';

			}
		}
		else {
			$GLOBALS['HideAdminLog'] = "none";
		}
		if(!GetConfig('AdministratorLogging') && !GetConfig('SystemLogging')) {
			$GLOBALS['Message'] .= MessageBox(GetLang('LoggingDisabled'), MSG_ERROR);
			$GLOBALS['HideTabs'] = "none";
		}

		if(!$GLOBALS['SystemLog'] && !$GLOBALS['AdministratorLog'] && !$GLOBALS['Message']) {
			$GLOBALS['Message'] .= MessageBox(GetLang('NoLogEntries'), MSG_ERROR);
			$GLOBALS['HideTabs'] = "none";
		}

		$this->template->display('logs.system.tpl');
	}

	private function _GetAdministratorAction($ToDo, $data)
	{
		if(!is_array($data)) {
			$data = array(0 => $data);
		}
		else if(empty($data)) {
			$data = array(0 => "");
		}

		if($ToDo == "processLogin") {
			if($data[0] == "valid") {
				$ToDo = "processLoginValid";
			}
			else {
				$ToDo = "processLoginInvalid";
			}
		}
		else if($ToDo == "exportCustomers") {
			if($data[0] == "CSV") {
				$ToDo = "exportCustomersCSV";
			}
			else if($data[0] == "XML") {
				$ToDo = "exportCustomersXML";
			}
		}
		else if($ToDo == "exportProducts") {
			if($data[0] == "CSV") {
				$ToDo = "exportProductsCSV";
			}
			else if($data[0] == "XML") {
				$ToDo = "exportProductsXML";
			}
		}
		else if($ToDo == "exportOrders") {
			if($data[0] == "CSV") {
				$ToDo = "exportOrdersCSV";
			}
			else if($data[0] == "XML") {
				$ToDo = "exportOrdersXML";
			}
		}
		else if($ToDo == "flagOrderMessage") {
			if($data[0] == "flagged") {
				$ToDo = "flagOrderMessageFlagged";
			}
			else {
				$ToDo = "flagOrderMessageUnFlagged";
			}
		}
		else if($ToDo == "saveProductDownload") {
			if($data[0] == "created") {
				$ToDo = "saveProductDownloadCreated";
			}
			else {
				$ToDo = "saveProductDownloadUpdated";
			}
		}

		$data = array_map('isc_html_escape', $data);

		if(isset($GLOBALS['ISC_LANG']['AdministratorLogAction_'.$ToDo])) {
			$data = array_reverse($data);
			$data[] = GetLang('AdministratorLogAction_'.$ToDo);

			// Flip the array back to what it was so we can simply call_user_func_array it
			$data = array_reverse($data);
			$string = @call_user_func_array("sprintf", $data);
			if(!$string) {
				$string = $data[0];
			}
			return $string;
		}
		else {
			$action = $ToDo;
			if(!empty($data)) {
				$data = implode(", ", $data);
				if($data != '') {
					$action .= ' ('.$data.')';
				}
			}
			return $action;
		}
	}
}