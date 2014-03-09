<?php

	if (!defined('ISC_BASE_PATH')) {
		die();
	}

	class ISC_ADMIN_REMOTE_DISCOUNTS extends ISC_ADMIN_REMOTE_BASE
	{
		public function __construct()
		{
			$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->LoadLangFile('discounts');
			$GLOBALS["ISC_CLASS_ADMIN_DISCOUNTS"] = GetClass("ISC_ADMIN_DISCOUNTS");
			parent::__construct();
		}

		public function HandleToDo()
		{
			$what = isc_strtolower(@$_REQUEST['w']);

			switch ($what) {
				case "getmorediscounts":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Discounts)) {
						$this->getMoreDiscounts();
					}
					exit;
					break;

				case "updatediscountorder":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Discounts)) {
						$this->UpdateDiscountOrder();
					}
					exit;
					break;

				case "getrulemoduleproperties":
					if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Discounts)) {
						$this->getRuleModuleProperties();
					}
					exit;
					break;

			}
		}

		private function getMoreDiscounts()
		{
			if(!isset($_POST["lastSortOrder"]) || !isId($_POST["lastSortOrder"])) {
				exit;
			}

			$items = "";
			$query = "SELECT SQL_CALC_FOUND_ROWS *
						FROM [|PREFIX|]discounts
						WHERE sortorder > " . (int)$_POST["lastSortOrder"] . "
						ORDER BY sortorder ASC
						LIMIT " . ISC_DISCOUNTS_PER_SHOW;

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);
			$more = 0;

			if ($row) {
				if ($GLOBALS["ISC_CLASS_DB"]->FetchOne("SELECT FOUND_ROWS()") > ISC_DISCOUNTS_PER_SHOW) {
					$more = 1;
				}

				do {
					$items .= $GLOBALS["ISC_CLASS_ADMIN_DISCOUNTS"]->BuildDiscountGridRow($row);
				} while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result));
			}

			$tags[] = $this->MakeXMLTag("status", 1);
			$tags[] = $this->MakeXMLTag("items", $items, true);
			$tags[] = $this->MakeXMLTag("more", $more);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function UpdateDiscountOrder()
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('discounts');

			if (!isset($_POST['sortorder']) || trim($_POST['sortorder']) == '') {
				exit;
			}

			$idx = explode(',', $_POST['sortorder']);
			$idx = array_filter($idx, "isId");

			if (!is_array($idx) || empty($idx)) {
				exit;
			}

			$sort = 1;
			foreach ($idx as $fieldId) {
				$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("discounts", array("sortorder"=>$sort++), "discountid=" . (int)$fieldId);
			}

			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('message', GetLang('DiscountOrdersUpdated'), true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			die();
		}

		private function getRuleModuleProperties()
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('discounts');

			$module = explode('_',$_REQUEST['module']);

			GetModuleById('rule', $ruleModule, $module[1]);

			if(!is_object($ruleModule)) {
				exit;
			}

			$ruleModule->initializeAdmin();

			echo $ruleModule->getTemplateClass()->render('module.'.$module[1].'.tpl');
			exit;
		}
	}