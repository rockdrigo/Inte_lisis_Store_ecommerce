<?php

	class ISC_ADMIN_NEWS extends ISC_ADMIN_BASE
	{
		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('news');
			switch (isc_strtolower($Do))
			{
				case "prevnews":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
						$this->PreviewNews();
					} else {
						echo "<script type=\"text/javascript\">window.close();</script>";
					}

					break;
				}
				case "editnewsvisibility":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_News)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('News') => "index.php?ToDo=viewNews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditVisibility();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "editnews2":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_News)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('News') => "index.php?ToDo=viewNews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditNewsStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "editnews":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_News)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('News') => "index.php?ToDo=viewNews", GetLang('EditNews') => "index.php?ToDo=editNews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditNewsStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "addnews2":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_News)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('News') => "index.php?ToDo=viewNews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddNewsStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "addnews":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_News)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('News') => "index.php?ToDo=viewNews", GetLang('AddNews') => "index.php?ToDo=addNews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->AddNewsStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				case "deletenews":
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_News)) {
						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('News') => "index.php?ToDo=viewNews");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteNews();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					break;
				}
				default:
				{
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
						if(isset($_GET['searchQuery'])) {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('News') => "index.php?ToDo=viewNews", GetLang('SearchResults') => "index.php?ToDo=viewNews");
						}
						else {
							$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('News') => "index.php?ToDo=viewNews");
						}

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}

						$this->ManageNews();

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
			}
		}

		public function ManageNewsGrid(&$numNews)
		{
			// Show a list of news in a table
			$page = 0;
			$start = 0;
			$numNews = 0;
			$numPages = 0;
			$GLOBALS['NewsGrid'] = "";
			$GLOBALS['Nav'] = "";
			$max = 0;
			$searchURL = '';

			if (isset($_GET['searchQuery'])) {
				$query = $_GET['searchQuery'];
				$GLOBALS['Query'] = $query;
				$searchURL = '&amp;searchQuery='.$query;
			} else {
				$query = "";
				$GLOBALS['Query'] = "";
			}

			if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'asc') {
				$sortOrder = 'asc';
			} else {
				$sortOrder = "desc";
			}

			$sortLinks = array(
				"Title" => "n.newstitle",
				"Date" => "n.newsdate",
				"Visible" => "n.newsvisible"
			);

			if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("ManageNews", $_REQUEST['sortField'], $sortOrder);
			}
			else {
				$sortField = "n.newsdate";
				list($sortField, $sortOrder) = GetDefaultSortField("ManageNews", "n.newsdate", $sortOrder);
			}

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			} else {
				$page = 1;
			}

			$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
			$GLOBALS['SortURL'] = $sortURL;

			// Limit the number of questions returned
			if ($page == 1) {
				$start = 1;
			} else {
				$start = ($page * ISC_NEWS_PER_PAGE) - (ISC_NEWS_PER_PAGE-1);
			}

			$start = $start-1;

			// Get the results for the query
			$newsResult = $this->_GetNewsList($query, $start, $sortField, $sortOrder, $numNews);
			$numPages = ceil($numNews / ISC_NEWS_PER_PAGE);

			// Add the "(Page x of n)" label
			if($numNews > ISC_NEWS_PER_PAGE) {
				$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
				$GLOBALS['Nav'] .= BuildPagination($numNews, ISC_NEWS_PER_PAGE, $page, sprintf("index.php?ToDo=viewNews%s", $sortURL));
			}
			else {
				$GLOBALS['Nav'] = "";
			}

			$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');
			$GLOBALS['SearchQuery'] = $query;
			$GLOBALS['SortField'] = $sortField;
			$GLOBALS['SortOrder'] = $sortOrder;

			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewNews&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);

			// Workout the maximum size of the array
			$max = $start + ISC_NEWS_PER_PAGE;

			if ($max > count($newsResult)) {
				$max = count($newsResult);
			}

			if($numNews > 0) {
				// Display the news
				while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($newsResult))
				{
					$GLOBALS['Title'] = isc_html_escape($row['newstitle']);

					if (isc_strlen($row['newscontent']) > 100) {
						$GLOBALS['Content'] = isc_substr(strip_tags($row['newscontent']), 0, 100) . "...";
					} else {
						$GLOBALS['Content'] = $row['newscontent'];
					}

					$GLOBALS['Date'] = CDate($row['newsdate']);
					$GLOBALS['NewsId'] = $row['newsid'];

					// If they have permission to edit news, they can change
					// the visibility status of a news post by clicking on the icon

					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_News)) {
						if ($row['newsvisible'] == 1) {
							$GLOBALS['Visible'] = sprintf("<a title='%s' href='index.php?ToDo=editNewsVisibility&amp;newsId=%d&amp;visible=0'><img border='0' src='images/tick.gif'></a>", GetLang('ClickToHideNews'), $row['newsid']);
						} else {
							$GLOBALS['Visible'] = sprintf("<a title='%s' href='index.php?ToDo=editNewsVisibility&amp;newsId=%d&amp;visible=1'><img border='0' src='images/cross.gif'></a>", GetLang('ClickToShowNews'), $row['newsid']);
						}
					} else {
						if ($row['newsvisible'] == 1) {
							$GLOBALS['Visible'] = "<img border='0' src='images/tick.gif'>";
						} else {
							$GLOBALS['Visible'] = "<img border='0' src='images/cross.gif'>";
						}
					}

					// Workout the edit link -- do they have permission to do so?
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_News)) {
						$GLOBALS['EditNewsLink'] = sprintf("<a title='%s' class='Action' href='index.php?ToDo=editNews&amp;newsId=%d'>%s</a>", GetLang('NewsEdit'), $row['newsid'], GetLang('Edit'));
					} else {
						$GLOBALS['EditNewsLink'] = sprintf("<a class='Action' disabled>%s</a>", GetLang('Edit'));
					}

					$GLOBALS['NewsGrid'] .= $this->template->render('news.manage.row.tpl');
				}

				return $this->template->render('news.manage.grid.tpl');
			}
		}

		public function ManageNews($MsgDesc = "", $MsgStatus = "")
		{
			$numNews = 0;
			// Fetch any results, place them in the data grid
			$GLOBALS['NewsDataGrid'] = $this->ManageNewsGrid($numNews);

			// Was this an ajax based sort? Return the table now
			if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['NewsDataGrid'];
				return;
			}

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			// Do we need to disable the delete button?
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_News) || $numNews == 0) {
				$GLOBALS['DisableDelete'] = "DISABLED";
			}

			$GLOBALS['NewsIntro'] = GetLang('ManageNewsIntro');

			if($numNews == 0) {
				// No results
				$GLOBALS['DisplayGrid'] = "none";
				if(count($_GET) > 1) {
					if ($MsgDesc == "") {
						$GLOBALS['Message'] = MessageBox(GetLang('NoNewsResults'), MSG_ERROR);
					}
				}
				else {
					$GLOBALS['Message'] = MessageBox(GetLang('NoNews'), MSG_SUCCESS);
					$GLOBALS['DisplaySearch'] = "none";
				}
			}

			$this->template->display('news.manage.tpl');
		}

		private function _GetNewsList(&$Query, $Start, $SortField, $SortOrder, &$NumResults)
		{
			// Return an array containing details about news.
			// Takes into account search too.

			// PostgreSQL is case sensitive for likes, so all matches are done in lower case
			$Query = trim($Query);

			$query = "SELECT * FROM [|PREFIX|]news n";
			$countQuery = "SELECT COUNT(newsid) FROM [|PREFIX|]news n";

			$queryWhere = '';
			if($Query != '') {
				$queryWhere .= " WHERE newstitle LIKE '%".$GLOBALS['ISC_CLASS_DB']->Quote($Query)."%' OR newscontent LIKE '%".$GLOBALS['ISC_CLASS_DB']->Quote($Query)."%'";
			}

			// Add any conditions on to the query
			$query .= $queryWhere;
			$countQuery .= $queryWhere;

			$query .= " ORDER BY ".$SortField." ".$SortOrder;

			$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
			$NumResults = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			// Add the limit
			$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_NEWS_PER_PAGE);
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			return $result;
		}

		public function DeleteNews()
		{
			$filteredIdx = array();

			if (isset($_POST['news']) && is_array($_POST['news'])) {
				$filteredIdx = array_filter($_POST['news'], "isId");
			}

			if (is_array($filteredIdx) && !empty($filteredIdx)) {
				$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("news", "WHERE newsid IN(" . implode(",", $filteredIdx) . ")");
				$err = $GLOBALS["ISC_CLASS_DB"]->Error();

				if ($err != "") {
					$this->ManageNews($err, MSG_ERROR);
				} else {

					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['news']));

					$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("news_search", "WHERE newsid IN(" . implode(",", $filteredIdx) . ")");

					$this->ManageNews(GetLang('NewsDeletedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
					$this->ManageNews();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		public function AddNewsStep1()
		{
			$GLOBALS['Title'] = GetLang('AddNews');
			$GLOBALS['Intro'] = GetLang('AddNewsIntro');
			$GLOBALS['FormAction'] = "addNews2";

			$wysiwygOptions = array(
				'id'		=> 'wysiwyg',
				'width'		=> '100%',
				'height'	=> '500px',
				'value'		=> GetLang('TypeNewsPostHere')
			);
			$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);
			$GLOBALS['NewsVisible'] = 'checked="checked"';

			$this->template->display('news.form.tpl');
		}

		public function AddNewsStep2()
		{
			// Commit the values to the database

			if(!isset($_POST['newsvisible'])) {
				$_POST['newsvisible'] = 0;
			}

			$error = $this->_CommitNews();
			if (empty($error)) {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
					$this->ManageNews(GetLang('NewsAddedSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('NewsAddedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
					$this->ManageNews(sprintf(GetLang('ErrNewsNotAdded'), $error), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrNewsNotAdded'), $error), MSG_ERROR);
				}
			}
		}

		private function _GetNewsData($NewsId, &$RefArray)
		{
			if ($NewsId == 0) {
				$RefArray['newsid'] = 0;
				$RefArray['newstitle'] = $_POST['newstitle'];
				$RefArray['newscontent'] = $_POST['wysiwyg'];
				$RefArray['newssearchkeywords'] = $_POST['newssearchkeywords'];
				$RefArray['newsdate'] = time();

				if (isset($_POST['newsvisible'])) {
					$RefArray['newsvisible'] = 1;
				} else {
					$RefArray['newsvisible'] = 0;
				}
			} else {
				// Get the data for this news post from the database
				$query = sprintf("select * from [|PREFIX|]news where newsid='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($NewsId));
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

				if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
					$RefArray = $row;
				}
			}
		}
		public function _CommitNews($NewsId=0)
		{
			// Commit the details for the news post to the database
			include_once(ISC_BASE_PATH.'/lib/api/news.api.php');
			$news = new API_NEWS();
			if ($NewsId == 0) {
				if(isset($_POST['wysiwyg_html'])) {
					$_POST['newscontent'] = $_POST['wysiwyg_html'];
				}
				else {
					$_POST['newscontent'] = $_POST['wysiwyg'];
				}
				$_POST['newsdate'] = time();
				$NewsId = $news->create();
			} else {
				if(isset($_POST['wysiwyg_html'])) {
					$_POST['newscontent'] = $_POST['wysiwyg_html'];
				}
				else {
					$_POST['newscontent'] = $_POST['wysiwyg'];
				}
				if(isset($_POST['newsvisible'])) {
					$_POST['newsvisible' ] = 1;
				}
				else {
					$_POST['newsvisible'] = 0;
				}
				$news->load($NewsId);
				$news->save();
			}

			if(!$news->error) {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($NewsId, $_POST['newstitle']);

				$savedata = array(
					"newsid" => $NewsId,
					"newstitle" => $_POST["newstitle"],
					"newscontent" => stripHTMLForSearchTable($_POST["newscontent"]),
					"newssearchkeywords" => $_POST["newssearchkeywords"]
				);

				$query = "SELECT newssearchid
							FROM [|PREFIX|]news_search
							WHERE newsid=" . (int)$NewsId;

				$searchId = $GLOBALS["ISC_CLASS_DB"]->FetchOne($query);

				if (isId($searchId)) {
					$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("news_search", $savedata, "newssearchid=" . (int)$searchId);
				} else {
					$GLOBALS["ISC_CLASS_DB"]->InsertQuery("news_search", $savedata);
				}

				// Save the words to the news_words table for search spelling suggestions
				Store_SearchSuggestion::manageSuggestedWordDatabase("news", $NewsId, $_POST["newstitle"]);
			}

			return $news->error;
		}

		public function EditNewsStep1()
		{
			// Show the form to edit a news
			$newsId = (int)$_GET['newsId'];
			$arrData = array();

			if (NewsExists($newsId)) {
				$this->_GetNewsData($newsId, $arrData);

				$GLOBALS['NewsId'] = $newsId;
				$GLOBALS['NewsTitle'] = $arrData['newstitle'];
				$GLOBALS['NewsSearchKeywords'] = $arrData['newssearchkeywords'];

				$GLOBALS['Title'] = GetLang('EditNewsTitle');
				$GLOBALS['Intro'] = GetLang('EditNewsIntro');
				$GLOBALS['FormAction'] = "editNews2";

				$wysiwygOptions = array(
					'id'		=> 'wysiwyg',
					'width'		=> '100%',
					'height'	=> '500px',
					'value'		=> $arrData['newscontent']
				);
				$GLOBALS['WYSIWYG'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

				if ($arrData['newsvisible'] == 1) {
					$GLOBALS['NewsVisible'] = 'checked="checked"';
				}

				$this->template->display('news.form.tpl');
			} else {
				// The news post doesn't exist
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
					$this->ManageNews(GetLang('NewsDoesntExist'), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		public function EditNewsStep2()
		{
			// Get the information from the form and add it to the database
			$newsId = (int) $_POST['newsId'];

			// Commit the values to the database
			$error = $this->_CommitNews($newsId);
			if (empty($error)) {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
					$this->ManageNews(GetLang('NewsUpdatedSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('NewsUpdatedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
					$this->ManageNews(sprintf(GetLang('ErrNewsNotUpdated'), $error), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrNewsNotUpdated'), $error), MSG_ERROR);
				}
			}
		}

		public function EditVisibility()
		{
			// Update the visibility of a news post with a simple query

			$newsId = (int)$_GET['newsId'];
			$visible = (int)$_GET['visible'];

			$arrData = array();
			$this->_GetNewsData($newsId, $arrData);

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($arrData['newsid'], $arrData['newstitle']);

			$updatedNews = array(
				"newsvisible" => $visible
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("news", $updatedNews, "newsid='".$GLOBALS['ISC_CLASS_DB']->Quote($newsId)."'");

			if ($GLOBALS["ISC_CLASS_DB"]->Error() == "") {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
					$this->ManageNews(GetLang('NewsVisibleSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('NewsVisibleSuccessfully'), MSG_SUCCESS);
				}
			} else {
				$err = '';
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_News)) {
					$this->ManageNews(sprintf(GetLang('ErrNewsVisibilityNotChanged'), $err), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrNewsVisibilityNotChanged'), $err), MSG_ERROR);
				}
			}
		}

		public function PreviewNews()
		{
			// Print a packing slip for an order
			ob_end_clean();

			if (isset($_GET['newsId'])) {
				$newsId = $_GET['newsId'];
				$newsId = (int)$newsId;

				// Get the details for this news post from the database
				$query = sprintf("select * from [|PREFIX|]news where newsid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($newsId));
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

				if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
					$GLOBALS['Title'] = $row['newstitle'];
					$GLOBALS['Content'] = $row['newscontent'];
					$GLOBALS['NewsDate'] = CDate($row['newsdate']);

					$this->template->display('news.preview.tpl');
					die();
				} else {
					echo "<script type=\"text/javascript\">window.close();</script>";
				}
			} else {
				echo "<script type=\"text/javascript\">window.close();</script>";
			}
		}
	}