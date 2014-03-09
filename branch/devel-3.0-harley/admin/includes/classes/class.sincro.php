<?php
//REQ11162 JIB
class ISC_ADMIN_SINCRO extends ISC_ADMIN_BASE
{
	public function HandleToDo($Do)
		{
			switch(isc_strtolower($Do)) {
				case "viewsincro":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Sincro)){
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						echo $this->ShowSincro();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					}
					else{
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'),MSG_ERROR);
					}
				break;
				case "deletesystemsincro":
						if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Sincro)) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
							$this->DeleteSystemSincroEntries();
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
							die();
						}
						else {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
						}
				break;
				case "deleteallsystemsincro":
				
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Sincro)) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->ClearSystemSincro();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					}
					else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				default:
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", "Store Logs" => "index.php?ToDo=viewsincro");
				
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->ShowSincro();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					}
					else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
			}
		}
	
		private function ClearSystemSincro()
		{
			$query = "DELETE FROM [|PREFIX|]sincronizacion";
			$GLOBALS["ISC_CLASS_DB"]->Query($query);
			$err = $GLOBALS["ISC_CLASS_DB"]->GetError();
		
			if ($err[0] != "") {
				FlashMessage($err[0], MSG_ERROR, 'index.php?ToDo=viewsincro');
			} else {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
		
				FlashMessage(GetLang('SystemLogClearedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewsincro');
			}
		}
		
		private function DeleteSystemSincroEntries()
		{		
			if (isset($_POST['delete'])) {
				$ids = implode(",", array_map("intval", $_POST['delete']));
				$query = sprintf("DELETE FROM [|PREFIX|]sincronizacion WHERE consecutivo IN (%s)", $ids);
				$GLOBALS["ISC_CLASS_DB"]->Query($query);
				$err = $GLOBALS["ISC_CLASS_DB"]->GetError();
		
				if ($err[0] != "") {
					FlashMessage($err[0], MSG_ERROR, 'index.php?ToDo=viewsincro');
				} else {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
					FlashMessage(GetLang('SystemLogsDeletedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewsincro');
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Logs)) {
					$this->ShowSystemLog();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}
		
		private function ShowSincro()
		{
			// Show a the system log in a data grid.
			$page =	 1;
			$start = 0;
			$numEntries = 0;
			$numPages = 0;
			$GLOBALS['SincroGrid'] = '';
			$GLOBALS['Nav'] = '';
			
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('logs');
		
			if(isset($_GET['sortOrder']) && $_GET['sortOrder'] == "asc") {
				$sortOrder = "asc";
			}
			else {
				$sortOrder = "desc";
			}
		
			$validSortFields = array("sincstatus", "xmlsummary");
			if(isset($_GET['sortField']) && in_array($_GET['sortField'], $validSortFields)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("SystemSincro", $_REQUEST['sortField'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("SystemSincro", "estatus", $sortOrder);
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
		
			if(isset($_REQUEST['SincStatus']) && $_REQUEST['SincStatus'] != 0) {
				$where .= sprintf(" AND estatus='%d'", $GLOBALS['ISC_CLASS_DB']->Quote((int)$_REQUEST['SincStatus']));
				$sortURL .= sprintf("&estatus=%d", (int)$_REQUEST['SincStatus']);
				$GLOBALS['Estatus'.(int)$_REQUEST['SincStatus'].'Selected'] = "selected=\"selected\"";
				$GLOBALS['HideClearResults'] = '';
			}
		
			if(isset($_REQUEST['xmlsummary']) && $_REQUEST['xmlsummary'] != ''){
				$where .= sprintf(" AND xml LIKE '%%%s%%'", $GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['xmlsummary']));
				$sortURL .= sprintf("&xmlsummary=%d", $_REQUEST['xmlsummary']);
				$GLOBALS['HideClearResults'] = '';
				$GLOBALS['XmlValue'] = $_REQUEST['xmlsummary'];
			}
		
			// Build the list of sincro status
			$sincroTypes = array(
					'NEW',
					'SLC',
					'RES'
			);
		
			$GLOBALS['SincroSearchTypeSelect'] = '';
			foreach($sincroTypes as $type => $label) {
				if(isset($hiddenTypes) && in_array($type, $hiddenTypes)) {
					continue;
				}
		
				$sel = '';
				if(isset($_REQUEST['SincStatus'])) {
					$sel = 'selected="selected"';
				}
		
				$GLOBALS['SincroSearchTypeSelect'] .= '<option value="'.$type.'" '.$sel.'">'.isc_html_escape($label).'</option>';
			}
		
			// Limit the number of of log entries returned
			if ($page == 1) {
				$start = 1;
			} else {
				$start = ($page * ISC_LOG_ENTRIES_PER_PAGE) - (ISC_LOG_ENTRIES_PER_PAGE-1);
			}
			$start = $start-1;
		
			// Get the results for the query
			$query = sprintf("SELECT COUNT(consecutivo) FROM [|PREFIX|]sincronizacion WHERE %s", $where);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$numEntries = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		
			$numPages = ceil($numEntries / ISC_LOG_ENTRIES_PER_PAGE);
		
			if($numEntries > ISC_LOG_ENTRIES_PER_PAGE) {
				$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
				$GLOBALS['Nav'] .= BuildPagination($numEntries, ISC_LOG_ENTRIES_PER_PAGE, $page, sprintf("index.php?ToDo=viewsincro%s", $sortURL));
			}
			else {
				$GLOBALS['Nav'] = "";
			}
			$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');
		
			$GLOBALS['SortField'] = $sortField;
		
			$GLOBALS['SortOrder'] = $sortOrder;
		
			$sortLinks = array(
					"Xml" => "xml",
					"Estatus" => "estatus",
			);
			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewsincro&amp;".$sortURL."&amp;page=".$page, $sortField, $sortOrder);
			if ($numEntries > 0) {
				$query = sprintf("SELECT consecutivo, xml, creado, estatus FROM [|PREFIX|]sincronizacion WHERE %s ORDER BY %s %s, consecutivo DESC", $where, $sortField, $sortOrder);
				$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_LOG_ENTRIES_PER_PAGE);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
					
					$GLOBALS['SincroId'] = $row['consecutivo'];
		
					$GLOBALS['SincroEstatus'] = $row['estatus'];
					
		
					$GLOBALS['ExpandLink'] = sprintf("<a href=\"#\" onclick=\"ShowLogInfo('%d'); return false;\"><img id=\"expand%d\" src=\"images/plus.gif\" align=\"left\" width=\"19\" class=\"ExpandLink\" height=\"16\" title=\"%s\" border=\"0\"></a>", $row['consecutivo'], $row['consecutivo'], GetLang('ClickToViewLogInfo'));
	
					$GLOBALS['SincroSummary'] = substr($row['xml'],0,150);
					$GLOBALS['SincroSum'] = $GLOBALS['SincroSummary'];		
				
					$GLOBALS['SincroDate'] = isc_date(GetConfig('ExtendedDisplayDateFormat'), $row['creado']);
		
					$GLOBALS['SincroGrid'] .= $this->template->render('sincro.system.row.tpl');
				}
			}
			else {
				$GLOBALS['SincroGrid'] = '';
			}
			$GLOBALS['DisableDelete'] = '';
		
			if(!$GLOBALS['SincroGrid']) {
				if($GLOBALS['HideClearResults'] == "none") {
					$msg = GetLang('SystemLogEmpty');
				}
				else {
					$msg = GetLang('SystemLogNoResults');
				}
				$GLOBALS['DisableDelete'] = "disabled=\"disabled\"";
				$GLOBALS['SincroGrid'] = "<tr>
				<td colspan=\"8\"><em>".$msg."</em></td>
				</tr>";
			}
		
			return $this->template->render('sincro.system.grid.tpl');
		
		}
}