<?php
define("ITEMS_PER_PAGE", 15);

require_once(APP_ROOT . "/includes/exporter/class.exportfiletype.factory.php");
require_once(APP_ROOT . "/includes/exporter/class.exportmethod.factory.php");
require_once(APP_ROOT . "/includes/exporter/class.exportoptions.php");

class ISC_ADMIN_EXPORT extends ISC_ADMIN_BASE
{
	private $templates;
	private $type;
	private $title;
	private $type_title;

	private $filetype;

	public function HandleToDo($do)
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('export');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('exporttemplates');

		$this->templates = GetClass('ISC_ADMIN_EXPORTTEMPLATES');
		$this->type = isc_strtolower($_GET['t']);

		// load the file type for this export
		if (!$this->filetype = ISC_ADMIN_EXPORTFILETYPE_FACTORY::GetExportFileType($this->type)) {
			FlashMessage(GetLang("InvalidType"), MSG_ERROR, 'index.php?ToDo=viewExportTemplates');
		}

		// does user have permission to export this type?
		if (!$this->filetype->HasPermission() || !gzte11(ISC_MEDIUMPRINT)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		$details = $this->filetype->GetTypeDetails();
		$title = $details['title'];
		$this->type_title = $title;
		$this->title = sprintf(GetLang("ExportTitle"), $title);

		switch (isc_strtolower($do)) {
			case 'startexport':
				$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", $title => $details['viewlink'], GetLang('Export') => "");

				if (!isset($_REQUEST['ajax'])) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				}

				$this->StartExport();

				if (!isset($_REQUEST['ajax'])) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				}

				break;
			case 'runexport':
				$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Export') => "", $title => "");

				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->RunExport();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
		}
	}

	/**
	* Builds a select box for templates.
	*
	* @param mixed $type
	* @param mixed $templateid
	*/
	private function BuildTemplatesSelect($type, $templateid = 0)
	{
		$has_templates = false;

		$html = "<select id=\"template\" name=\"template\" size=\"8\" class=\"Field200\" >
		";

		if ($this->filetype->ignore) {
			$html .= "<optgroup label=\"" . GetLang("BuiltInTemplates") . "\">";
			$details = $this->filetype->GetTypeDetails();
			$html .= "<option value=\"0\" selected=\"selected\">" . $details['title'] . "</option>";
			$html .= "</optgroup>";
			$html .= "</select>";

			return $html;
		}

		//<option value=\"\">" . GetLang("SelectTemplate") . "</option>

		$result = $this->templates->GetTemplates(false, true);
		if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
			$has_templates = true;

			$html .= "<optgroup label=\"" . GetLang("BuiltInTemplates") . "\">";
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if (!in_array($type, explode(",", $row['usedtypes']))) {
					continue;
				}

				if ($row["exporttemplateid"] == $templateid) {
					$row_selected = " selected=\"selected\"";
				}
				else {
					$row_selected = "";
				}

				$html .= "<option value=\"" . $row["exporttemplateid"] . "\"" . $row_selected . ">" . $row["exporttemplatename"] . "</option>";
			}
			$html .= "</optgroup>";
		}

		$result = $this->templates->GetTemplates(true, false);
		if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
			$has_templates = true;

			$has_mytemplates = false;

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if (!in_array($type, explode(",", $row['usedtypes']))) {
					continue;
				}
				$has_mytemplates = true;


				if ($row["exporttemplateid"] == $templateid) {
					$row_selected = " selected=\"selected\"";
				}
				else {
					$row_selected = "";
				}

				$html .= "<option value=\"" . $row["exporttemplateid"] . "\"" . $row_selected . ">" . $row["exporttemplatename"] . "</option>";
			}

			if ($has_mytemplates) {
				$html .= "</optgroup>";
			}
		}

		$html .= "</select>";

		if (!$has_templates) {
			throw new Exception(GetLang("NoExportTemplatesFound"));
		}

		return $html;
	}


	/**
	* Gets a grid of data for the current export
	*
	* @param string $where The WHERE statement to use to get the data
	*/
	private function GetGrid($where = "", $having = "")
	{
		// get current page
		if (isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		} else {
			$page = 1;
		}

		// Limit the number of orders returned
		if ($page == 1) {
			$start = 1;
		} else {
			$start = ($page * ITEMS_PER_PAGE) - (ITEMS_PER_PAGE - 1);
		}

		$start = $start - 1;

		// set sort order
		if(isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'asc') {
			$sortOrder = 'asc';
		}
		else {
			$sortOrder = "desc";
		}

		// get the columns to display in the grid
		$columns = $this->filetype->GetListColumns();
		$new_columns = array();

		$sortLinks = $this->filetype->GetListSortLinks();
		$sortField = '';

		if (!empty($sortLinks)) {
			// get the field to sort on
			if(isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("Export" . $this->type, $_REQUEST['sortField'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("Export" . $this->type, current($sortLinks), $sortOrder);
			}

			$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
			$GLOBALS['SortURL'] = $sortURL;

			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=startExport" . $this->GetSearchURL(true) . "&amp;page=" . $page, $sortField, $sortOrder);


			$sortKeys = array_keys($sortLinks);

			// modify columns to include a sort link
			foreach ($columns as $x => $value) {
				$new_columns[] = $value . " &nbsp; ".$GLOBALS['SortLinks'.$sortKeys[$x]];
			}
		}
		else {
			$new_columns = $columns;
		}

		// get the icon to show for this type
		//$details = $this->filetype->GetTypeDetails();
		//$GLOBALS['TypeIcon'] = $details['icon'];

		// get number of records total
		$query = $this->filetype->GetListCountQuery($where, $having);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$total_items = $row['ListCount'];

		$GLOBALS['DataSummary'] = sprintf(GetLang("ExportSummary"), number_format($total_items), isc_strtolower($this->type_title));

		// generate navigation links
		$this->GetNav($page, $total_items);

		// get the query to list the data
		$query = $this->filetype->GetListQuery($where, $having, $sortField, $sortOrder);
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ITEMS_PER_PAGE);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
			throw new Exception(sprintf(GetLang("NoDataFound"), isc_strtolower($this->type_title)));
		}


		$GLOBALS['ColSpan'] = count($columns) + 1;

		$gridData = "<tr class=\"Heading3\">\n" . $this->BuildTableRow($new_columns) . "\n</tr>";

		// Build the items for the grid
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// get a formatted row
			$output = $this->filetype->GetListRow($row);

			$new_row = $output;
			foreach ($output as $id => $value) {
				if (!$value) {
					$new_row[$id] = "N/A";
				}
			}

			$GLOBALS['RowData'] = $this->BuildTableRow($new_row);

			$gridData .= $this->template->render('export.grid.row.tpl');
		}

		return $gridData;
	}

	/**
	* Builds a table row from an array of data
	*
	* @param mixed $row
	*/
	private function BuildTableRow($row)
	{
		$html = "";

		foreach ($row as $column => $value) {
			$html .= "\n<td style=\"height: 21px;\">" . $value . "</td>";
		}

		return $html;
	}

	private function GetSearchURL($remove_sort = false)
	{
		// Build the pagination URL
		$searchURL = '';
		foreach($_GET as $k => $v) {
			if ($k == "ToDo" || $k == "page" || !$v || is_array($v)) {
				continue;
			}
			if ($remove_sort && ($k == "sortField" || $k == "sortOrder")) {
				continue;
			}
			$searchURL .= sprintf("&%s=%s", $k, urlencode($v));
		}

		if (isset($_REQUEST[$this->type]) && is_array($_REQUEST[$this->type])) {
			$searchURL .= "&ids=" . urlencode(implode(",", $_REQUEST[$this->type]));
		}

		return $searchURL;
	}


	/**
	* Builds the pagination and navigation links
	*
	* @param int $page The current page we're on
	* @param int $total_items The total number of items to be paginated
	*/
	private function GetNav($page, $total_items)
	{
		$searchURL = $this->GetSearchURL();

		$numPages = ceil($total_items / ITEMS_PER_PAGE);

		// Add the "(Page x of n)" label
		if($total_items > ITEMS_PER_PAGE) {
			$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);

			$GLOBALS['Nav'] .= BuildPagination($total_items, ITEMS_PER_PAGE, $page, "index.php?ToDo=startExport" . $searchURL);
		}
		else {
			$GLOBALS['Nav'] = "";
		}

		$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');
	}

	private function StartExport()
	{
		$GLOBALS['ExportIntro'] = sprintf(GetLang("ExportIntro"), isc_strtolower($this->type_title));
		$GLOBALS['hiddenFields'] = sprintf("<input type='hidden' name='type' value='%s'>", $this->type);

		$templateid = 0;
		if (isset($_GET["tempId"])) {
			$templateid = (int)$_GET["tempId"];
		}

		$GLOBALS['TemplateTitle'] = $this->title;

		try {
			$GLOBALS['TemplatesList'] = $this->BuildTemplatesSelect($this->type, $templateid);

			$where = "";
			$having = "";

			$details = $this->filetype->GetTypeDetails();

			if (!empty($details['instructions'])) {
				FlashMessage($details['instructions'], MSG_WARNING);
			}

			// were specific records selected?
			if (isset($_REQUEST[$this->type])) {
				$ids = $_REQUEST[$this->type];
			}
			elseif (isset($_REQUEST["ids"])) {
				$ids = explode(",", urldecode($_REQUEST["ids"]));
			}

			if (isset($ids)) {
				// get the id field for this type
				$idfield = $details['idfield'];

				$where = $idfield . " IN (" . implode(', ', array_map(array($GLOBALS['ISC_CLASS_DB'], "Quote"), $ids)) . ")";

				$GLOBALS['hiddenFields'] .= sprintf("<input type='hidden' name='ids' value='%s'>", implode(",", $ids));
			}
			else {
				//$GLOBALS['TemplateTitle'] .= " - " . sprintf(GetLang("AllData"), ucfirst($this->type));
				$params = $this->GetParams();
				if (!empty($params)) {
					$GLOBALS['hiddenFields'] .= sprintf("<input type='hidden' name='params' value='%s'>", http_build_query($params));

					$ret = $this->filetype->BuildWhereFromFields($params);

					if (is_array($ret)) {
						$where = $ret['where'];
						if (isset($ret['having'])) {
							$having = $ret['having'];
						}
					}
					else {
						$where = $ret;
					}
				}
			}

			// Generate the grid
			$GLOBALS['GridData'] = $this->GetGrid($where, $having);
			$GLOBALS['DataGrid'] = $this->template->render('export.grid.tpl');

			if (isset($_REQUEST['ajax'])) {
				echo $GLOBALS['DataGrid'];
				die;
			}

			// create a list of methods the user can choose from
			$methods = ISC_ADMIN_EXPORTMETHOD_FACTORY::GetExportMethodList();
			$method_list = "";

			$GLOBALS['MethodChecked'] = "checked=\"checked\"";

			foreach ($methods as $file => $method) {
				//$GLOBALS['MethodIcon'] = $method['icon'];
				$GLOBALS['MethodName'] = $method['name'];
				$GLOBALS['MethodTitle'] = $method['title'];
				$GLOBALS['MethodHelp'] = $method['help'];

				$method_list .= $this->template->render('export.method.tpl');

				$GLOBALS['MethodChecked'] = "";
			 }

			 $GLOBALS['Methods'] = $method_list;

			 $GLOBALS['ViewLink'] = $details['viewlink'];
		}
		catch (Exception $ex) {
			FlashMessage($ex->getMessage(), MSG_ERROR);

			$GLOBALS['HideForm'] = "display: none;";
		}

		$GLOBALS['Message'] = GetFlashMessageBoxes();
		$GLOBALS['FormAction'] = "runExport&t=" . $this->type;

		$this->template->display('export.step1.tpl');
	}

	private function RunExport()
	{
		try {
			// check for a selected template
			if (empty($_REQUEST["template"]) && !$this->filetype->ignore) {
				throw new Exception(GetLang("NoTemplateSelected"));
			}

			if (!isset($_REQUEST['format'])) {
				throw new Exception(GetLang("NoMethodSelected"));
			}

			$details = $this->filetype->GetTypeDetails();

			$templateid = $_REQUEST["template"];

			if (!$this->filetype->ignore) {
				// check template exists
				$template = $this->templates->GetTemplate($templateid);

				// check the file type is available for this template
				if (!in_array($this->type, explode(",", $template['usedtypes']))) {
					throw new Exception(sprintf(GetLang("TypeNotAvailable"), $this->type));
				}

				$templateName = $template['exporttemplatename'];
			}
			else {
				$templateName = $details['title'];
			}

			$where = "";
			$having = "";

			// get the custom search fields
			if (isset($_REQUEST['ids'])) {
				$ids = explode(',', $_REQUEST['ids']);
				$ids = implode(', ', array_map(array($GLOBALS['ISC_CLASS_DB'], "Quote"), $ids));

				$where = $details['idfield'] . " IN (" . $_REQUEST["ids"] . ")";
			}
			elseif (isset($_REQUEST['params'])) {
				$params = $this->GetParams($_REQUEST['params']);
				$ret = $this->filetype->BuildWhereFromFields($params);

				if (is_array($ret)) {
					$where = $ret['where'];
					if (isset($ret['having'])) {
						$having = $ret['having'];
					}
				}
				else {
					$where = $ret;
				}
			}

			// get the export method the user has chosen
			$method = ISC_ADMIN_EXPORTMETHOD_FACTORY::GetExportMethod($_REQUEST['format']);
			$method_details = $method->GetMethodDetails();

			$options = new ISC_ADMIN_EXPORTOPTIONS();
			$options->setFileType($this->filetype)
				->setTemplateId($templateid)
				->setWhere($where)
				->setHaving($having);

			// Initialise the export
			$method->Init($options);

			// run the export
			$file = $method->HandleToDo('ExportIntro');

			exit;
		}
		catch (Exception $ex) {
			FlashMessage($ex->getMessage(), MSG_ERROR);
			$this->StartExport();
		}
	}

	private function GetParams($query_string = "")
	{
		if (!$query_string) {
			$params = $_GET;
		} else {
			parse_str($query_string, $params);
		}

		unset($params['ToDo'], $params['t'], $params['tempId'], $params['SearchButton.x'], $params['SearchButton.y']);
		return $params;
	}
}
