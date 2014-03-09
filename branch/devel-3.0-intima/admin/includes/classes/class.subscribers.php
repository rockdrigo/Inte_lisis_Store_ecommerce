<?php

	define('ISC_EXPORT_SUBSCRIBERS_PER_PAGE', 1);

	class ISC_ADMIN_SUBSCRIBERS extends ISC_ADMIN_BASE
	{

		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('subscribers');
			switch(isc_strtolower($Do)) {
				case "cancelsubscribersexport": {
					if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Newsletter_Subscribers)) {
						$this->CancelSubscribersExport();
					}
					else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
				case "downloadsubscribersexport":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Newsletter_Subscribers)) {
						$this->DownloadSubscribersExport();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "exportsubscribersintro":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Newsletter_Subscribers)) {
						$this->ExportSubscribersIntro();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				case "exportsubscribers": {
					if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Newsletter_Subscribers)) {
						$this->ExportSubscribers();
					}
					else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
			}
		}

		public function CancelSubscribersExport()
		{
			if(isset($_SESSION['Subscribers']['SubscribersFile']) && basename($_SESSION['Subscribers']['SubscribersFile']) == $_SESSION['Subscribers']['SubscribersFile'] && file_exists(APP_ROOT."../cache/".$_SESSION['Subscribers']['SubscribersFile'])) {
				@unlink(APP_ROOT."../cache/".$_SESSION['Subscribers']['SubscribersFile']);
				unset($_SESSION['Subscribers']);
			}
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('SubscribersExportCancelled'), MSG_ERROR);
		}

		public function ExportSubscribersIntro()
		{
			$_SESSION['Subscribers'] = array();

			$query = "SELECT COUNT(subscriberid) FROM [|PREFIX|]subscribers";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$numSubscribers = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			$GLOBALS['ISC_LANG']['SubscribersListGeneratedIntro'] = sprintf(GetLang('SubscribersListGeneratedIntro'), $numSubscribers);

			if($numSubscribers == 0) {
				$GLOBALS['HideExportIntro'] = "none";
			}
			else {
				$GLOBALS['HideNoSubscribers'] = "none";
			}

			$this->template->display('subscribers.intro.tpl');
			exit;
		}

		public function ExportSubscribers()
		{
			if(!isset($_REQUEST['start'])) {
				// This is our first visit to the export function. We create the file and the export session

				if(isset($_SESSION['Subscribers']['SubscribersFile']) && basename($_SESSION['Subscribers']['SubscribersFile']) == $_SESSION['Subscribers']['SubscribersFile'] && file_exists(APP_ROOT."../cache/".$_SESSION['Subscribers']['SubscribersFile'])) {
					@unlink(APP_ROOT."../cache/".$_SESSION['Subscribers']['SubscribersFile']);
					unset($_SESSION['Subscribers']['SubscribersFile']);
				}

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();

				$exportFile = "subscribers-export-".time().".csv";
				$_SESSION['Subscribers']['SubscribersFile'] = $exportFile;
				$fp = fopen(APP_ROOT."/../cache/".$exportFile, "w+");
				if(!$fp) {
					echo "<script type='text/javascript'>self.parent.SubscribersExportError('".GetLang('SubscribersExportUnableCreate')."');</script>";
					exit;
				}

				/**
				 * Add in the header information
				 */
				fputcsv($fp, array(GetLang('SubscribersExportColumnEmail'), GetLang('SubscribersExportColumnFirstName')));

				$start = 0;

				$query = "SELECT COUNT(subscriberid) FROM [|PREFIX|]subscribers";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$_SESSION['Subscribers']['NumSubscribers'] = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
			}
			else {
				$exportFile = '';
				if(isset($_SESSION['Subscribers']['SubscribersFile']) && basename($_SESSION['Subscribers']['SubscribersFile']) == $_SESSION['Subscribers']['SubscribersFile'] && file_exists(APP_ROOT."/../cache/".$_SESSION['Subscribers']['SubscribersFile'])) {
					$exportFile = $_SESSION['Subscribers']['SubscribersFile'];
				}

				if(!$exportFile) {
					echo "<script type='text/javascript'>self.parent.SubscribersExportError('".GetLang('SubscribersExportInvalidFile')."');</script>";
					exit;
				}

				$fp = fopen(APP_ROOT."/../cache/".$exportFile, "a");
				if(!$fp) {
					echo "<script type='text/javascript'>self.parent.SubscribersExportError('".GetLang('SubscribersExportUnableCreate')."');</script>";
					exit;
				}
				$start = $_REQUEST['start'];
			}

			ob_end_clean();

			$lastPercent = 0;
			$total = $_SESSION['Subscribers']['NumSubscribers'];
			$done = $start;

			$query = "SELECT * FROM [|PREFIX|]subscribers ORDER BY subscriberid";
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_EXPORT_SUBSCRIBERS_PER_PAGE);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result))
			{
				fputcsv($fp, array($row['subemail'], $row['subfirstname']));
				++$done;
				$percent = ceil($done/$total*100);
				// Spit out a progress bar update
				if($percent != $lastPercent) {
					echo sprintf("<script type='text/javascript'>self.parent.UpdateSubscribersExportProgress('%s');</script>", $percent);
					flush();
				}
			}
			$end = $start + ISC_EXPORT_SUBSCRIBERS_PER_PAGE;
			if($end >= $_SESSION['Subscribers']['NumSubscribers']) {
				echo "<script type='text/javascript'>self.parent.SubscribersExportComplete();</script>";
			}
			else {
				echo sprintf("<script type='text/javascript'>window.location='index.php?ToDo=exportSubscribers&start=%d';</script>", $end);
			}
			fclose($fp);
			exit;
		}

		public function DownloadSubscribersExport()
		{
			$exportFile = '';
			if(isset($_SESSION['Subscribers']['SubscribersFile']) && basename($_SESSION['Subscribers']['SubscribersFile']) == $_SESSION['Subscribers']['SubscribersFile'] && file_exists(APP_ROOT."/../cache/".$_SESSION['Subscribers']['SubscribersFile'])) {
				$exportFile = $_SESSION['Subscribers']['SubscribersFile'];
			}

			if(!$exportFile) {
				echo "<script type='text/javascript'>self.parent.SubscribersExportError('".GetLang('SubscribersExportInvalidFile')."');</script>";
				exit;
			}
			$file = APP_ROOT."/../cache/".$exportFile;

			ob_end_clean();

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-Disposition: attachment; filename=subscribers.csv;");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . filesize($file));

			$fp = fopen($file, "r");
			while (!feof($fp)) {
				echo fread($fp, 8192);
				flush();
			}

			@unlink($file);
			unset($_SESSION['Subscribers']);
			exit;
		}

	}