<?php

	class ISC_ADMIN_DISCOUNTS extends ISC_ADMIN_BASE
	{
		public function HandleToDo($Do)
		{
			if(!gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			}

			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('discounts');
			switch (isc_strtolower($Do)) {
				case "editdiscountenabled":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Discounts)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Discounts') => "index.php?ToDo=viewDiscounts");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditEnabled();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "editdiscounthalt":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Discounts)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Discounts') => "index.php?ToDo=viewDiscounts");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditHalt();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "editdiscount2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Discounts)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Discounts') => "index.php?ToDo=viewDiscounts");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditDiscountStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "editdiscount":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Discounts)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Discounts') => "index.php?ToDo=viewDiscounts", GetLang('EditDiscount') => "index.php?ToDo=editDiscount");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditDiscountStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "creatediscount2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Discounts)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Discounts') => "index.php?ToDo=viewDiscounts");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateDiscountStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "creatediscount":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Discounts)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Discounts') => "index.php?ToDo=viewDiscounts", GetLang('CreateDiscount') => "index.php?ToDo=createDiscount");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateDiscountStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "deletediscounts":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Discounts)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Discounts') => "index.php?ToDo=viewDiscounts");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteDiscounts();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				default:
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Discounts)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Discounts') => "index.php?ToDo=viewDiscounts");

						$GLOBALS['InfoTip'] = GetLang('InfoTipManageDiscounts');

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}

						$this->ManageDiscounts();

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
			}
		}

		private function ManageDiscountsGrid(&$numDiscounts)
		{
			// Show a list of discounts in a table
			$page = 0;
			$start = 0;
			$numDiscounts = 0;
			$numPages = 0;
			$GLOBALS['DiscountGrid'] = "";
			$GLOBALS['Nav'] = "";
			$max = 0;

			if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'desc') {
				$sortOrder = 'desc';
			} else {
				$sortOrder = "asc";
			}

			$sortLinks = array(
				"DiscountName" => "c.discountname",
				"DiscountMaxUses" => "c.discountmaxuses",
				"DiscountCurrentUses" => "c.discountcurrentuses",
				"DiscountExpiryDate" => "c.discountexpiry",
				"DiscountEnabled" => "c.discountenabled",
			);

			if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("ManageDiscounts", $_REQUEST['sortField'], $sortOrder);
			} else {
				list($sortField, $sortOrder) = GetDefaultSortField("ManageDiscounts", "c.discountid", $sortOrder);
			}

			if (isset($_GET['page'])) {
				$page = (int)$_GET['page'];
			} else {
				$page = 1;
			}
			$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);

			$GLOBALS['SortURL'] = $sortURL;

			// Get the results for the query
			$discountResult = $this->_GetDiscountList($start, $sortField, $sortOrder, $numDiscounts);

			$GLOBALS['SortField'] = $sortField;

			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewDiscounts&amp;page=".$page, $sortField, $sortOrder);

			if ($numDiscounts > 0) {
				// Display the discounts
				while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($discountResult)) {
					$GLOBALS['DiscountGrid'] .= $this->BuildDiscountGridRow($row);
				}
				return $this->template->render('discounts.manage.grid.tpl');
			}
		}

		public function BuildDiscountGridRow($discount)
		{
			if (!is_array($discount) || empty($discount)) {
				return '';
			}

			$GLOBALS['DiscountId'] = isc_html_escape($discount['discountid']);
			$GLOBALS['RowId'] = 'Sort_'.$discount['discountid'];
			$GLOBALS['SortOrder'] = $discount['sortorder'];

			$GLOBALS['Name'] = isc_html_escape($discount['discountname']);
			$GLOBALS['MaxUses'] = (int) $discount['discountmaxuses'];

			if ($GLOBALS['MaxUses'] == 0) {
				$GLOBALS['MaxUses'] = 'Unlimited';
			}

			$GLOBALS['CurrentUses'] = (int) $discount['discountcurrentuses'];

			if ($discount['discountexpiry'] != 0) {
				$GLOBALS['ExpiryDate'] = date("m/d/Y", isc_html_escape($discount['discountexpiry']));
			} else {
				$GLOBALS['ExpiryDate'] = 'N/A';
			}

			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Discounts)) {
				$GLOBALS['EditDiscountLink'] = sprintf("<a title='%s' class='Action' href='index.php?ToDo=editDiscount&amp;discountId=%d'>%s</a>", GetLang('DiscountEdit'), $discount['discountid'], GetLang('Edit'));
				if ($discount['discountenabled'] == 1) {
					$GLOBALS['Enabled'] = sprintf("<a title='%s' href='index.php?ToDo=editDiscountEnabled&amp;discountId=%d&amp;enabled=0'><img border='0' src='images/tick.gif'></a>", GetLang('ClickToDisableDiscount'), $discount['discountid']);
				} else {
					$GLOBALS['Enabled'] = sprintf("<a title='%s' href='index.php?ToDo=editDiscountEnabled&amp;discountId=%d&amp;enabled=1'><img border='0' src='images/cross.gif'></a>", GetLang('ClickToEnableDiscount'), $discount['discountid']);
				}
				if ($discount['halts'] == 1) {
					$GLOBALS['Halt'] = sprintf("<a title='%s' href='index.php?ToDo=editDiscountHalt&amp;discountId=%d&amp;halt=0'><img border='0' src='images/tick.gif'></a>", GetLang('ClickToDisableHaltDiscount'), $discount['discountid']);
				} else {
					$GLOBALS['Halt'] = sprintf("<a title='%s' href='index.php?ToDo=editDiscountHalt&amp;discountId=%d&amp;halt=1'><img border='0' src='images/cross.gif'></a>", GetLang('ClickToEnableHaltDiscount'), $discount['discountid']);
				}

			} else {
				$GLOBALS['EditDiscountLink'] = sprintf("<a class='Action' disabled>%s</a>", GetLang('Edit'));
				if ($discount['discountenabled'] == 1) {
					$GLOBALS['Enabled'] = '<img border="0" src="images/tick.gif" alt="tick" />';
				} else {
					$GLOBALS['Enabled'] = '<img border="0" src="images/cross.gif" alt="cross" />';
				}
				if ($discount['halts'] == 1) {
					$GLOBALS['Halt'] = '<img border="0" src="images/tick.gif" alt="tick" />';
				} else {
					$GLOBALS['Halt'] = '<img border="0" src="images/cross.gif" alt="cross" />';
				}
			}

			return $this->template->render('discounts.manage.row.tpl');
		}

		private function ManageDiscounts($MsgDesc = "", $MsgStatus = "")
		{
			$numDiscounts = 0;
			// Fetch any results, place them in the data grid
			$GLOBALS['DiscountsDataGrid'] = $this->ManageDiscountsGrid($numDiscounts);

			// Was this an ajax based sort? Return the table now
			if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['DiscountsDataGrid'];
				return;
			}

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			// Do we need to disable the delete button?
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Discounts) || $numDiscounts == 0) {
				$GLOBALS['DisableDelete'] = "DISABLED";
			}

			if($numDiscounts == 0) {
				// There are no discounts in the database
				$GLOBALS['DisplayGrid'] = "none";
				$GLOBALS['DisplaySearch'] = "none";
				$GLOBALS['Message'] = MessageBox(GetLang('NoDiscounts'), MSG_SUCCESS);
			}

			$GLOBALS["DiscountShowNextBatchItems"] = sprintf(GetLang("DiscountShowNextBatchItems"), ISC_DISCOUNTS_PER_SHOW);
			if ($numDiscounts <= ISC_DISCOUNTS_PER_PAGE) {
				$GLOBALS["HideSeeMoreDiscountBox"] = "none";
			}

			$GLOBALS['DiscountIntro'] = GetLang('ManageDiscountIntro');

			$this->template->display('discounts.manage.tpl');
		}

		private function _GetDiscountList($Start, $SortField, $SortOrder, &$NumResults)
		{
			$query = "SELECT * FROM [|PREFIX|]discounts ORDER BY sortorder";
			$countQuery = "SELECT COUNT(*) FROM [|PREFIX|]discounts";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
			$NumResults = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_DISCOUNTS_PER_PAGE);
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			return $result;
		}

		private function DeleteDiscounts()
		{
			if (isset($_POST['discount'])) {
				$discountids = implode(",", array_map('intval', $_POST['discount']));

				$query = sprintf("delete from [|PREFIX|]discounts where discountid in (%s)", $discountids);
				$GLOBALS["ISC_CLASS_DB"]->Query($query);

				$err = $GLOBALS["ISC_CLASS_DB"]->Error();
				if ($err != "") {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['discount']));

					$this->ManageDiscounts($err, MSG_ERROR);
				} else {
					$this->ManageDiscounts(GetLang('DiscountsDeletedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts();
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		private function CreateDiscountStep1()
		{
			$GLOBALS['Title'] = GetLang('CreateDiscount');
			$GLOBALS['Intro'] = GetLang('CreateDiscountIntro');
			$GLOBALS['Enabled'] = 'checked="checked"';
			$GLOBALS['FormAction'] = "createDiscount2";
			$GLOBALS['DiscountTypes'] = '';
			$GLOBALS['CurrentData'] = '';
			$GLOBALS['DiscountJavascriptValidation'] = '';
			$GLOBALS['DiscountEnabledCheck'] = 'checked="checked"';
			$rules = GetAvailableModules('rule', false, false, false);
			$GLOBALS['CurrentRule'] = 'null';
			$GLOBALS['RuleList'] = '';

			$GLOBALS['Vendor'] = '0';
			if(gzte11(ISC_HUGEPRINT)) {
				$GLOBALS['Vendor'] = 1;
			}

			foreach ($rules as $rule) {
				$rulesSorted[$rule['object']->getRuleType()][] = $rule;
			}

			$first = true;

			foreach ($rulesSorted as $type => $ruleType) {

				if ($first) {
					$GLOBALS['RuleList'] .= '<h4 style="margin-top:5px; margin-bottom:5px;">'.$type.' '.GetLang('BasedRule').'</h4>';
				} else {
					$GLOBALS['RuleList'] .= '<h4 style="margin-bottom:5px;">'.$type.' '.GetLang('BasedRule').'</h4>';
				}
				$first = false;

				foreach ($ruleType as $rule) {

					$GLOBALS['RuleList'] .= '<label><input type="radio" class="discountRadio" onClick="UpdateModule(this.id, '.(int)$rule['object']->vendorSupport().')" name="RuleType" value="'.$rule['id'].'" id="'.$rule['id'].'"> ';

					if (!(int)$rule['object']->vendorSupport() && $GLOBALS['Vendor'] == 1) {
						$GLOBALS['RuleList'] .= '<span class="aside">'.$rule['object']->getDisplayName().'</span>';
					} else {
						$GLOBALS['RuleList'] .= '<span>'.$rule['object']->getDisplayName().'</span>';
					}

					$GLOBALS['RuleList'] .= '</input></label><br /><div id="ruleWrapper'.$rule['id'].'" class="ruleWrapper" style="display : none; "><img src="images/nodejoin.gif" style="vertical-align: middle; float:left; padding-right : 10px;" /><span class="ruleSettings" id="ruleSettings'.$rule['id'].'"></span><br /></div>';
					$GLOBALS['DiscountJavascriptValidation'] .= $rule['object']->getJavascriptValidation();

				}
			}

			$this->template->assign('freeShippingMessage', getLang('FreeShippingMessageDefault'));

			$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
			$GLOBALS['MaxUses'] = '';
			$GLOBALS['DiscountExpiryFields'] = 'display : none';
			$GLOBALS['DiscountMaxUsesDisabled'] = 'readonly="readonly"';
			$GLOBALS['DiscountExpiryDateDisabled'] = 'readonly="readonly"';
			$GLOBALS['DiscountMaxUses'] = 1;

			$this->template->display('discount.form.tpl');

		}

		private function CreateDiscountStep2()
		{
			$_POST['halts'] = 0;

			$error = $this->_CommitDiscount();
			if (empty($error)) {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(GetLang('DiscountCreatedSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('DiscountCreatedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(sprintf("Error %s", $error), MSG_ERROR);

				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrDiscountNotCreated'), $error), MSG_ERROR);
				}
			}
		}

		private function _CommitDiscount($DiscountId=0)
		{
			require_once(ISC_BASE_PATH.'/lib/api/discount.api.php');
			$discount = new API_DISCOUNT();
			$freeShippingMesgLocation = array();

			if ($DiscountId != 0) {
				$discount->load($DiscountId);
			}

			$_POST['discountmaxuses'] = 0;
			if (isset($_POST['discountruleexpiresuses'])) {
				$_POST['discountmaxuses'] = $_POST['discountruleexpiresusesamount'];
			}

			$_POST['discountcurrentuses'] =  0;

			$query = sprintf("select max(sortorder) from [|PREFIX|]discounts");
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			$_POST['discountenabled'] =  0;
			if (isset($_POST['enabled'])) {
				$_POST['discountenabled'] =  1;
			}

			$_POST['discountexpiry'] = 0;
			if (isset($_POST['discountruleexpiresdateamount']) && !empty($_POST['discountruleexpiresdateamount'])) {
				$_POST['discountexpiry'] = ConvertDateToTime($_POST['discountruleexpiresdateamount']);
			}

			$_POST['discountruletype'] = 0;
			$_POST['free_shipping_message'] = '';
			if (isset($_POST['RuleType']) && !empty($_POST['RuleType'])) {
				$_POST['discountruletype'] = $_POST['RuleType'];

				// if the selected rule related to free shipping, we will be collecting
				// additional message here.
				if (in_array($_POST['RuleType'], array('rule_buyxgetfreeshipping', 'rule_freeshippingwhenoverx'))) {
					if (!empty ($_POST['FreeShippingMessage']) && !empty ($_POST['ShowFreeShippingMesgOn'])) {
						$_POST['free_shipping_message'] = $_POST['FreeShippingMessage'];
						$freeShippingMesgLocation = $_POST['ShowFreeShippingMesgOn'];
					}

				}
			}

			$_POST['configdata'] = '';
			$cd = array();

			foreach($_POST as $module_id => $vars) {

				// Checkout variables start with checkout_
				if (isc_substr($module_id, 0, 4) != "var_" && isc_substr($module_id,0,5) != "varn_") {
					continue;
				}

				if (is_array($vars)) {
					$vars = implode(',', $vars);
				}

				if (isc_substr($module_id,0,5) == "varn_") {
					$vars = DefaultPriceFormat($vars);
				}

				$cd[isc_html_escape($module_id)] = isc_html_escape($vars);
			}

			$_POST['configdata'] = serialize($cd);
			$_POST['free_shipping_message_location'] = serialize($freeShippingMesgLocation);

			GetModuleById('rule', $ruleModule, $_POST['discountruletype']);
			if(!is_object($ruleModule)) {
				// Something really bad went wrong >_<
				return 'Rule Type Doesn\'t Exist';
			}

			if($DiscountId == 0) {
				$_POST['sortorder'] = $row['max(sortorder)']+1;
				$DiscountId = $discount->create();
			}
			else {
				$_POST['sortorder'] = $discount->getSortOrder();
				$discount->save();
			}

			return $discount->error;
		}

		private function GetDiscountData($methodId)
		{
			$query = "
				SELECT *
				FROM [|PREFIX|]discounts
				WHERE discountid='".(int)$methodId."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$method = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			return $method;
		}

		private function EditDiscountStep1()
		{
			$GLOBALS['Title'] = GetLang('EditDiscount');
			$GLOBALS['Intro'] = GetLang('EditDiscountIntro');
			$GLOBALS['Enabled'] = 'checked="checked"';
			$GLOBALS['FormAction'] = "editDiscount2";
			$GLOBALS['DiscountTypes'] = '';
			$GLOBALS['Edit'] = 'display : none;';
			$GLOBALS['DiscountJavascriptValidation'] = '';
			$GLOBALS['DiscountEnabledCheck'] = 'checked="checked"';

			$rules = GetAvailableModules('rule', false, false, false);

			$GLOBALS['RuleList'] = '';

			$GLOBALS['MaxUses'] = '';
			$GLOBALS['DiscountExpiryFields'] = 'display : none';
			$GLOBALS['DiscountMaxUsesDisabled'] = 'readonly="readonly"';
			$GLOBALS['DiscountExpiryDateDisabled'] = 'readonly="readonly"';

			require_once(ISC_BASE_PATH.'/lib/api/discount.api.php');
			$discountAPI = new API_DISCOUNT();

			$discountId = (int) $_GET['discountId'];

			if ($discountAPI->DiscountExists($discountId)) {

				$discount = $this->GetDiscountData($discountId);
				$freeShippingMessageLocations = unserialize($discount['free_shipping_message_location']);
				$GLOBALS['DiscountId'] = $discountId;
				$GLOBALS['DiscountName'] = isc_html_escape($discount['discountname']);

				$module = explode('_',$discount['discountruletype']);

				if (isset($module[1])) {
					GetModuleById('rule', $ruleModule, $module[1]);
					if(!is_object($ruleModule)) {
						// Something really bad went wrong >_<
						exit;
					}
				}
				else {
					die('Can\'t find the module');
				}

				$cd = unserialize($discount['configdata']);

				if (!empty($cd)) {
					foreach ($cd as $var => $data) {

						if (isc_substr($var,0,5) == "varn_") {
							$data = FormatPrice($data, false, false);
						}

						$GLOBALS[$var] = $data;
					}
				}

				$ruleModule->initialize($discount);
				$ruleModule->initializeAdmin();

				$GLOBALS['RuleList'] = '';

				$GLOBALS['Vendor'] = '0';
				if(gzte11(ISC_HUGEPRINT)) {
					$GLOBALS['Vendor'] = 1;
				}

				foreach ($rules as $rule) {
					$rulesSorted[$rule['object']->getRuleType()][] = $rule;
				}

				$first = true;
				$GLOBALS['CurrentRule'] = 'null';

				foreach ($rulesSorted as $type => $ruleType) {

					if ($first) {
						$GLOBALS['RuleList'] .= '<h4 style="margin-top:5px; margin-bottom:5px;">'.$type.' '.GetLang('BasedRule').'</h4>';
					} else {
						$GLOBALS['RuleList'] .= '<h4 style="margin-bottom:5px;">'.$type.' '.GetLang('BasedRule').'</h4>';
					}
					$first = false;

					foreach ($ruleType as $rule) {

						$GLOBALS['RuleList'] .= '<label><input type="radio" class="discountRadio" onClick="UpdateModule(this.id,'.(int)$rule['object']->vendorSupport().')" name="RuleType" value="'.$rule['id'].'" ';
						if ($rule['id'] == $discount['discountruletype']) {
							$GLOBALS['RuleList'] .= ' checked="checked" ';
							$GLOBALS['CurrentRule'] = "'".$rule['id']."'";
						}

						$GLOBALS['RuleList'] .= 'id="'.$rule['id'].'"> ';

						if (!(int)$rule['object']->vendorSupport() && $GLOBALS['Vendor'] == 1) {
							$GLOBALS['RuleList'] .= '<span class="aside">'.$rule['object']->getDisplayName().'</span>';
						} else {
							$GLOBALS['RuleList'] .= '<span>'.$rule['object']->getDisplayName().'</span>';
						}

						$GLOBALS['RuleList'] .= '</input></label><br /><div id="ruleWrapper'.$rule['id'].'" class="ruleWrapper"';

						if ($rule['id'] != $discount['discountruletype'])
							$GLOBALS['RuleList'] .= 'style="display : none; "';

						$GLOBALS['RuleList'] .= '><img src="images/nodejoin.gif" style="vertical-align: middle; float:left; padding-right : 10px;" /><span class="ruleSettings" id="ruleSettings'.$rule['id'].'">';

						if ($rule['id'] == $discount['discountruletype'])
							$GLOBALS['RuleList'] .= $ruleModule->getTemplateClass()->render('module.'.$module[1].'.tpl');

						$GLOBALS['RuleList'] .= '</span><br /></div>';
						$GLOBALS['DiscountJavascriptValidation'] .= $rule['object']->getJavascriptValidation();

					}
				}

				$GLOBALS['DiscountMaxUses'] = isc_html_escape($discount['discountmaxuses']);

				if ($discount['discountexpiry'] != 0) {
					$GLOBALS['DiscountExpiryDate'] = date("m/d/Y", isc_html_escape($discount['discountexpiry']));
				} else {
					$GLOBALS['DiscountExpiryDate'] = '';
				}

				$GLOBALS['DiscountExpiryFields'] = 'display : none';
				$GLOBALS['DiscountMaxUsesDisabled'] = 'readonly="readonly"';
				$GLOBALS['DiscountDisabled'] = 'readonly="readonly"';

				if (!empty($GLOBALS['DiscountMaxUses']) || !empty($GLOBALS['DiscountExpiryDate'])) {
					$GLOBALS['DiscountExpiryCheck'] = 'checked="checked"';
					$GLOBALS['DiscountExpiryFields'] = '';
				}

				if (!empty($GLOBALS['DiscountMaxUses'])) {
					$GLOBALS['DiscountMaxUsesCheck'] = 'checked="checked"';
					$GLOBALS['DiscountMaxUsesDisabled'] = '';
				}
				if (!empty($GLOBALS['DiscountExpiryDate'])) {
					$GLOBALS['DiscountExpiryDateCheck'] = 'checked="checked"';
					$GLOBALS['DiscountExpiryDateDisabled'] = '';
				}

				$GLOBALS['DiscountEnabled'] = isc_html_escape($discount['discountenabled']);

				if (empty($GLOBALS['DiscountEnabled'])) {
					$GLOBALS['DiscountEnabledCheck'] = '';
				}

				$GLOBALS['DiscountCurrentUses'] = isc_html_escape($discount['discountcurrentuses']);

				$GLOBALS['MaxUses'] = (int) $discount['discountmaxuses'];
				if($GLOBALS['MaxUses'] > 0) {
					$GLOBALS['MaxUsesChecked'] = 'checked="checked"';
				}
				else {
					$GLOBALS['DiscountMaxUses'] = 1;
					$GLOBALS['MaxUsesHide'] = 'none';
				}
				$this->template->assign('freeShippingMessage', $discount['free_shipping_message']);
				$this->template->assign('freeShippingMessageLocations', $freeShippingMessageLocations);

				$this->template->display('discount.form.tpl');

			}
			else {
				// The discount doesn't exist
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(GetLang('DiscountDoesntExist'), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		private function EditDiscountStep2()
		{
			// Get the information from the form and add it to the database
			$discountId = (int) $_POST['discountId'];

			$error = $this->_CommitDiscount($discountId);

			// Commit the values to the database
			if (empty($error)) {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(GetLang('DiscountUpdatedSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('DiscountUpdatedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(sprintf(GetLang('ErrDiscountNotUpdated'), $error), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrDiscountNotUpdated'), $error), MSG_ERROR);
				}
			}
		}

		private function EditEnabled()
		{
			// Update the enabled status of a discount with a simple query
			$discountId = (int) $_GET['discountId'];
			require_once(ISC_BASE_PATH.'/lib/api/discount.api.php');
			$discount = new API_DISCOUNT();
			$discount->load($discountId);

			if ($discount->updateField('discountenabled', (int) $_GET['enabled'])) {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(GetLang('DiscountEnabledSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('DiscountEnabledSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(sprintf(GetLang('ErrDiscountEnabledNotChanged'), $discount->error), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrDiscountEnabledNotChanged'), $discount->error), MSG_ERROR);
				}
			}
		}
		private function EditHalt()
		{
			// Update the halt status of a discount with a simple query
			$discountId = (int) $_GET['discountId'];
			require_once(ISC_BASE_PATH.'/lib/api/discount.api.php');
			$discount = new API_DISCOUNT();
			$discount->load($discountId);

			if ($discount->updateField('halts', (int) $_GET['halt'])) {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(GetLang('DiscountHaltSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('DiscountEnabledSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Discounts)) {
					$this->ManageDiscounts(sprintf(GetLang('ErrDiscountHaltNotChanged'), $discount->error), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrDiscountEnabledNotChanged'), $discount->error), MSG_ERROR);
				}
			}
		}
	}