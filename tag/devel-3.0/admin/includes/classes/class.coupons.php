<?php

	class ISC_ADMIN_COUPONS extends ISC_ADMIN_BASE
	{
		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('coupons');
			switch (isc_strtolower($Do)) {
				case "editcouponenabled":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Coupons)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Coupons') => "index.php?ToDo=viewCoupons");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditEnabled();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "editcoupon2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Coupons)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Coupons') => "index.php?ToDo=viewCoupons");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCouponStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "editcoupon":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Coupons)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Coupons') => "index.php?ToDo=viewCoupons", GetLang('EditCoupon') => "index.php?ToDo=editCoupon");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->EditCouponStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "createcoupon2":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Coupons)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Coupons') => "index.php?ToDo=viewCoupons");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateCouponStep2();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "createcoupon":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Coupons)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Coupons') => "index.php?ToDo=viewCoupons", GetLang('CreateCoupon') => "index.php?ToDo=createCoupon");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->CreateCouponStep1();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				case "deletecoupons":
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Coupons)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Coupons') => "index.php?ToDo=viewCoupons");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->DeleteCoupons();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						die();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				break;
				default:
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Coupons)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Coupons') => "index.php?ToDo=viewCoupons");

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						}

						$this->ManageCoupons();

						if(!isset($_REQUEST['ajax'])) {
							$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						}
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
			}
		}

		protected function ManageCouponsGrid(&$numCoupons)
		{
			// Show a list of coupons in a table
			$page = 0;
			$start = 0;
			$numCoupons = 0;
			$numPages = 0;
			$GLOBALS['CouponGrid'] = "";
			$GLOBALS['Nav'] = "";
			$max = 0;

			if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'desc') {
				$sortOrder = 'desc';
			} else {
				$sortOrder = "asc";
			}

			$sortLinks = array(
				"Name" => "c.couponname",
				"Coupon" => "c.couponcode",
				"Discount" => "c.couponamount",
				"Expiry" => "c.couponexpires",
				"NumUses" => "c.couponnumuses",
				"Enabled" => "c.couponenabled"
			);

			if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
				$sortField = $_GET['sortField'];
				SaveDefaultSortField("ManageCoupons", $_REQUEST['sortField'], $sortOrder);
			} else {
				list($sortField, $sortOrder) = GetDefaultSortField("ManageCoupons", "c.couponid", $sortOrder);
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
				$start = ($page * ISC_COUPONS_PER_PAGE) - (ISC_COUPONS_PER_PAGE-1);
			}
			$start = $start-1;

			// Get the results for the query
			$couponResult = $this->_GetCouponList($start, $sortField, $sortOrder, $numCoupons);

			$numPages = ceil($numCoupons / ISC_COUPONS_PER_PAGE);

			if($numCoupons > ISC_COUPONS_PER_PAGE) {
				$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
				$GLOBALS['Nav'] .= BuildPagination($numCoupons, ISC_COUPONS_PER_PAGE, $page, sprintf("index.php?ToDo=viewCoupons%s", $sortURL));
			}
			else {
				$GLOBALS['Nav'] = "";
			}

			$GLOBALS['SortField'] = $sortField;

			BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewCoupons&amp;page=".$page, $sortField, $sortOrder);

			$max = $start + ISC_COUPONS_PER_PAGE;
			if ($max > count($couponResult)) {

				$max = count($couponResult);

			}
			if ($numCoupons > 0) {
				// Display the coupons
				while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($couponResult)) {
					$GLOBALS['Name'] = isc_html_escape($row['couponname']);
					$GLOBALS['CouponId'] = (int) $row['couponid'];
					$GLOBALS['Coupon'] = isc_html_escape($row['couponcode']);

					// Dollar off from each item
					if ($row['coupontype'] == 0) {
						$GLOBALS['Discount'] = formatPrice($row['couponamount']).' '.getLang('OffEachItem');
					}
					// Dollar off from total order
					else if($row['coupontype'] == 2) {
						$GLOBALS['Discount'] = formatPrice($row['couponamount']).' '.getLang('OffTheTotal');
					}
					// Dollar off from shipping cost
					else if ($row['coupontype'] == 3) {
						$GLOBALS['Discount'] = formatPrice($row['couponamount']).' '.getLang('OffTheShipping');
					}
					// Free Shipping
					else if ($row['coupontype'] == 4) {
						$GLOBALS['Discount'] = getLang('FreeShipping');
					}
					// Percentage value coupon code
					else {
						$GLOBALS['Discount'] = number_format($row['couponamount'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), GetConfig('ThousandsToken')) . '% '.getLang('OffEachItem');
					}

					if ($row['couponexpires'] > 0) {
						$GLOBALS['Date'] = CDate($row['couponexpires']);
					} else {
						$GLOBALS['Date'] = GetLang('NA');

					}

					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Coupons)) {
						$GLOBALS['EditCouponLink'] = sprintf("<a title='%s' class='Action' href='index.php?ToDo=editCoupon&amp;couponId=%d'>%s</a>", GetLang('CouponEdit'), $row['couponid'], GetLang('Edit'));
						if ($row['couponenabled'] == 1) {
							$GLOBALS['Enabled'] = sprintf("<a title='%s' href='index.php?ToDo=editCouponEnabled&amp;couponId=%d&amp;enabled=0'><img border='0' src='images/tick.gif'></a>", GetLang('ClickToDisableCoupon'), $row['couponid']);
						} else {
							$GLOBALS['Enabled'] = sprintf("<a title='%s' href='index.php?ToDo=editCouponEnabled&amp;couponId=%d&amp;enabled=1'><img border='0' src='images/cross.gif'></a>", GetLang('ClickToEnableCoupon'), $row['couponid']);
						}

					} else {
						$GLOBALS['EditCouponLink'] = sprintf("<a class='Action' disabled>%s</a>", GetLang('Edit'));
						if ($row['couponenabled'] == 1) {
							$GLOBALS['Enabled'] = '<img border="0" src="images/tick.gif" alt="tick" />';
						} else {
							$GLOBALS['Enabled'] = '<img border="0" src="images/cross.gif" alt="cross" />';
						}

					}
					$GLOBALS['NumUses'] = number_format($row['couponnumuses']);
					$GLOBALS['ViewOrdersLink'] = '';
					if($row['couponnumuses'] > 0) {
						$GLOBALS['ViewOrdersLink'] = sprintf("&nbsp;&nbsp;&nbsp;<a href='index.php?ToDo=viewOrders&amp;couponCode=%s' title='%s'>%s</a>", $row['couponcode'], GetLang('ViewOrdersWithCoupon'), GetLang('ViewOrders'));
					}

					$GLOBALS['CouponGrid'] .= $this->template->render('coupons.manage.row.tpl');
				}
				return $this->template->render('coupons.manage.grid.tpl');
			}
		}

		protected function ManageCoupons($MsgDesc = "", $MsgStatus = "")
		{
			$numCoupons = 0;
			// Fetch any results, place them in the data grid
			$GLOBALS['CouponsDataGrid'] = $this->ManageCouponsGrid($numCoupons);

			// Was this an ajax based sort? Return the table now
			if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
				echo $GLOBALS['CouponsDataGrid'];
				return;
			}

			if ($MsgDesc != "") {
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);

			}

			// Do we need to disable the delete button?
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Delete_Coupons) || $numCoupons == 0) {
				$GLOBALS['DisableDelete'] = "DISABLED";
			}

			if($numCoupons == 0) {
				// There are no coupons in the database
				$GLOBALS['DisplayGrid'] = "none";
				$GLOBALS['DisplaySearch'] = "none";
				$GLOBALS['Message'] = MessageBox(GetLang('NoCoupons'), MSG_SUCCESS);
			}

			$GLOBALS['CouponIntro'] = GetLang('ManageCouponIntro');

			$this->template->display('coupons.manage.tpl');
		}

		protected function _GetCouponList($Start, $SortField, $SortOrder, &$NumResults)
		{
			$query = "SELECT * FROM [|PREFIX|]coupons c ORDER BY ".$SortField." ".$SortOrder;
			$countQuery = "SELECT COUNT(*) FROM [|PREFIX|]coupons";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
			$NumResults = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_COUPONS_PER_PAGE);
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			return $result;
		}

		protected function DeleteCoupons($redirect = true)
		{
			if (isset($_POST['coupon'])) {
				$couponids = implode(",", array_map('intval', $_POST['coupon']));

				$query = sprintf("delete from [|PREFIX|]coupons where couponid in (%s)", $couponids);
				$GLOBALS["ISC_CLASS_DB"]->Query($query);
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('coupon_locations', "WHERE coupon_id IN (" . $couponids . ")");
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('coupon_shipping_methods', "WHERE coupon_id IN (" . $couponids . ")");

				$err = $GLOBALS["ISC_CLASS_DB"]->Error();
				if ($err != "") {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['coupon']));

					if ($redirect) {
						$this->ManageCoupons($err, MSG_ERROR);
					}
					return false;
				} else {
					if ($redirect) {
						$this->ManageCoupons(GetLang('CouponsDeletedSuccessfully'), MSG_SUCCESS);
					}
					return true;
				}
			} else {
				if ($redirect) {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Coupons)) {
						$this->ManageCoupons();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
				}
				return false;
			}
		}

		protected function CreateCouponStep1($loadFromPost = false)
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			$GLOBALS['Title'] = GetLang('CreateCoupon');
			$GLOBALS['Intro'] = GetLang('CreateCouponIntro');
			$GLOBALS['FormAction'] = "createCoupon2";
			$GLOBALS['Enabled'] = 'checked="checked"';
			$GLOBALS['UsedForCat'] = 'checked="checked"';

			if ($loadFromPost) {
				$this->_GetCouponData(0, $arrData);
			}
			else {
				$arrData = array(
					'couponid' => 0,
					'couponname' => '',
					'couponcode' => GenerateCouponCode(),
					'couponamount' => '',
					'coupontype' => 2,
					'couponexpires' => '',
					'couponminpurchase' => '',
					'couponenabled' => 1,
					'couponmaxuses' => '',
					'couponmaxusespercus' => '',
					'couponappliesto' => 'categories',
					'couponappliestovalues' => array(0),
					'location_restricted' => 0,
					'shipping_method_restricted' => 0,
				);
			}

			$this->_SetFormData($arrData);

			$GLOBALS['CurrencyToken'] = GetConfig('CurrencyToken');

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}

			$this->template->assign('CurrentTab', 0);
			$this->template->display('coupon.form.tpl');

		}

		protected function _GetCouponData($CouponId = 0, &$RefArray = array())
		{
			if ($CouponId == 0) {
				$RefArray['couponid'] = (int)$_POST['couponId'];
				$RefArray['couponname'] = $_POST['couponname'];
				$RefArray['coupontype'] = $_POST['coupontype'];
				$RefArray['couponamount'] = (int)$_POST['couponamount'];
				$RefArray['couponminpurchase'] = CFloat($_POST['couponminpurchase']);
				$RefArray['couponmaxuses'] = (int)$_POST['couponmaxuses'];
				$RefArray['couponmaxusespercus'] = 0;
				if (isset($_POST['couponmaxusespercus'])) {
					$RefArray['couponmaxusespercus'] = (int)$_POST['couponmaxusespercus'];
				}
				if ($_POST['couponexpires'] != "") {
					$RefArray['couponexpires'] = ConvertDateToTime($_POST['couponexpires']);
				} else {
					$RefArray['couponexpires'] = 0;
				}
				if (isset($_POST['couponenabled'])) {
					$RefArray['couponenabled'] = 1;
				} else {
					$RefArray['couponenabled'] = 0;
				}
				if (isset($_POST['couponcode']) && $_POST['couponcode'] != "") {
					$RefArray['couponcode'] = $_POST['couponcode'];
				} else {
					$RefArray['couponcode'] = GenerateCouponCode();
				}

				$RefArray['couponappliesto'] = $_POST['usedfor'];

				if ($_POST['usedfor'] == "categories") {
					$RefArray['couponappliestovalues'] = $_POST['catids'];
				}
				else {
					$RefArray['couponappliestovalues'] = $_POST['prodids'];
				}

				// Restore data of shipping location restriction.
				$RefArray['location_restricted'] = 0;
				$RefArray['restrictedLocations'] = array();
				$RefArray['restrictedLocationType'] = '';
				if (!empty ($_POST['YesLimitByLocation']) && !empty ($_POST['LocationType'])) {
					$RefArray['location_restricted'] = 1;

					if (!empty ($_POST['LocationType'])) {
						$RefArray['restrictedLocationType'] = $_POST['LocationType'];
						if ($RefArray['restrictedLocationType'] == 'country'
						&& !empty ($_POST['LocationTypeCountries'])
						&& is_array($_POST['LocationTypeCountries'])) {

							$countryList = GetCountryListAsIdValuePairs();
							foreach ($_POST['LocationTypeCountries'] as $countryId) {
								if(empty ($countryList[$countryId])) {
									continue;
								}
								$RefArray['restrictedLocations'][] = array(
									'coupon_id' => (int)$_POST['couponId'],
									'selected_type' => $RefArray['restrictedLocationType'],
									'value_id' => (int)$countryId,
									'value' => $countryList[$countryId],
									'country_id' => 0,
								);
							}
						}
						else if($RefArray['restrictedLocationType'] == 'state' && !empty ($_POST['LocationTypeStatesSelect'])) {
							$countryList = GetCountryListAsIdValuePairs();
							$stateList = array();
							foreach($_POST['LocationTypeStatesSelect'] as $stateRecord) {
								$state = explode('-', $stateRecord, 2);
								if(!isset($stateList[$state[0]])) {
									// Load the states in this country as we haven't done that before
									$stateList[$state[0]] = array();
									$query = "SELECT * FROM [|PREFIX|]country_states WHERE statecountry='".(int)$state[0]."'";
									$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
									while($stateResult = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
										$stateList[$stateResult['statecountry']][$stateResult['stateid']] = $stateResult['statename'];
									}
								}

								// Start storing what we received
								if(isset($stateList[$state[0]][$state[1]])) {
									$stateName = $stateList[$state[0]][$state[1]];
								}
								else {
									$stateName = '';
								}
								$RefArray['restrictedLocations'][] = array(
									'coupon_id'			=> (int)$_POST['couponId'],
									'selected_type'		=> $RefArray['restrictedLocationType'],
									'value'				=> $stateName,
									'value_id'			=> (int)$state[1],
									'country_id'		=> (int)$state[0],
								);
							}
						}
						else if($RefArray['restrictedLocationType'] == 'zip' && !empty ($_POST['LocationTypeZipPostCodes'])) {
							$zipCodes = explode("\n", $_POST['LocationTypeZipPostCodes']);
							foreach($zipCodes as $zipCode) {
								$zipCode = trim($zipCode);
								if(!$zipCode) {
									continue;
								}
								$RefArray['restrictedLocations'][] = array(
									'coupon_id'			=> (int)$_POST['couponId'],
									'selected_type'		=> $RefArray['restrictedLocationType'],
									'value'				=> $zipCode,
									'value_id'			=> '0',
									'country_id'		=> (int)$_POST['LocationTypeZipCountry'],
								);
							}
						}
					}
				}

				// Restore data of shipping method restriction.
				$RefArray['shipping_method_restricted'] = 0;
				$RefArray['restrictedShippingMethods'] = array();
				if (!empty ($_POST['YesLimitByShipping'])) {
					$RefArray['shipping_method_restricted'] = 1;
					if (!empty ($_POST['LocationTypeShipping'])) {
						foreach ($_POST['LocationTypeShipping'] as $shipper) {
							$RefArray['restrictedShippingMethods'][] = $shipper;
						}
					}
				}
			} else {
				// Get the data for this coupon code from the database
				$query = sprintf("select * from [|PREFIX|]coupons where couponid='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($CouponId));
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
				if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
					$row['couponappliestovalues'] = "";

					$RefArray = $row;

					// get the prods/cats this applies to
					$query = "SELECT * FROM [|PREFIX|]coupon_values WHERE couponid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($CouponId) . "'";
					$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
					while ($valuerow = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$RefArray['couponappliestovalues'][] = $valuerow['valueid'];
					}

					// Get the restricted shipping location of the coupon
					$RefArray['restrictedLocations'] = array();
					$RefArray['restrictedLocationType'] = '';
					$query = "
						SELECT *
						FROM [|PREFIX|]coupon_locations cl
						WHERE cl.coupon_id = ".$GLOBALS['ISC_CLASS_DB']->Quote((int)$CouponId)."
						ORDER BY cl.id
					";
					$locationResult = $this->db->query($query);
					if ($locationResult) {
						while($locationRow = $this->db->fetch($locationResult)) {
							$RefArray['restrictedLocationType'] = $locationRow['selected_type'];
							$RefArray['restrictedLocations'][] = $locationRow;
						}
					}

					// Get the restricted shipping methods of the coupon
					$RefArray['restrictedShippingMethods'] = array();
					$query = "
						SELECT *
						FROM [|PREFIX|]coupon_shipping_methods
						WHERE coupon_id = '" . $GLOBALS['ISC_CLASS_DB']->Quote($CouponId) . "'";
					$shippingMethodResult = $GLOBALS["ISC_CLASS_DB"]->Query($query);
					while ($shippingMethodsRow = $GLOBALS['ISC_CLASS_DB']->Fetch($shippingMethodResult)) {
						$RefArray['restrictedShippingMethods'][] = $shippingMethodsRow['module_id'];
					}
				}
			}
		}

		protected function _SetFormData($arrData)
		{
			$GLOBALS['CouponCode'] = isc_html_escape($arrData['couponcode']);
			$GLOBALS['CouponName'] = isc_html_escape($arrData['couponname']);

			// "Number of Uses" settings
			$GLOBALS['MaxUses'] = (int) $arrData['couponmaxuses'];
			$GLOBALS['CouponMaxUsesEnabled'] = false;
			if($GLOBALS['MaxUses'] > 0) {
				$GLOBALS['CouponMaxUsesEnabled'] = true;
			}
			$GLOBALS['MaxUsesPerCus'] = (int) $arrData['couponmaxusespercus'];
			$GLOBALS['CouponMaxUsesPerCustomerEnabled'] = false;
			if($GLOBALS['MaxUsesPerCus'] > 0) {
				$GLOBALS['CouponMaxUsesPerCustomerEnabled'] = true;
			}

			$sel_cats = '';
			if($arrData['couponappliesto'] == "categories") {
				// Show the categories list
				$GLOBALS['ToggleUsedFor'] = "ToggleUsedFor(0);";
				$sel_cats = $arrData['couponappliestovalues'];
				if(in_array('0', $sel_cats)) {
					$GLOBALS['AllCategoriesSelected'] = "selected=\"selected\"";
				}
			}
			else {
				// Show the products textbox
				$GLOBALS['ToggleUsedFor'] = "ToggleUsedFor(1);";

				// Select a list of the products that this coupon is active for
				if($arrData['couponappliestovalues'] != "") {
					$GLOBALS['SelectedProducts'] = '';
					$GLOBALS['ProductIds'] = '';
					$query = sprintf("SELECT productid, prodname FROM [|PREFIX|]products WHERE productid IN (%s) ORDER BY prodname ASC", implode(",", $arrData['couponappliestovalues']));
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$GLOBALS['SelectedProducts'] .= sprintf("<option value='%d'>%s</option>", $row['productid'], $row['prodname']);
						$GLOBALS['ProductIds'] .= $row['productid'].",";
					}
					$GLOBALS['ProductIds'] = isc_substr($GLOBALS['ProductIds'], 0, -1);
				}
			}
			$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
			$GLOBALS['CategoryList'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions($sel_cats, "<option %s value='%d'>%s</option>", "selected=\"selected\"", "- ", false);
			if ($arrData['coupontype'] != 1) {
				$GLOBALS['DiscountAmount'] = $arrData['couponamount'];
			}
			else {
				$GLOBALS['DiscountAmount'] = (int)$arrData['couponamount'];
			}
			if ($arrData['couponminpurchase'] == 0) {
				$GLOBALS['MinPurchase'] = 0;
			} else {
				$GLOBALS['MinPurchase'] = CPrice($arrData['couponminpurchase']);
			}
			if ($arrData['couponexpires'] > 0) {
				$GLOBALS['ExpiryDate'] = isc_date("m/d/Y", $arrData['couponexpires']);
			}
			if ($arrData['couponenabled'] == 1) {
				$GLOBALS['Enabled'] = 'checked="checked"';
			}


			// Advanced tab
			$availableCountries = GetCountryListAsIdValuePairs();
			$availableShippers = array();

			// retrieve data related to location restrictions.
			if ($arrData['location_restricted']) {

				$locationValueIds = $this->_getNameValueArray($arrData['restrictedLocations'], 'value_id');
				$locationValues = $this->_getNameValueArray($arrData['restrictedLocations'], 'value');
				$locationCountryIds = $this->_getNameValueArray($arrData['restrictedLocations'], 'country_id');

				if ($arrData['restrictedLocationType'] == 'country') {
					$this->template->assign('locationTypeCountries', $locationValueIds);
				} else if ($arrData['restrictedLocationType'] == 'state') {
					$locationTypeStatesSelect = array();
					$locationCountriesStatesIds = $this->_getNameValueArray($arrData['restrictedLocations'], array('country_id', 'value_id'));
					if (!empty ($locationCountriesStatesIds)) {
						$locationTypeStatesSelect = GetMultiCountryStateOptions($locationCountriesStatesIds);
					}
					$this->template->assign('locationTypeStatesCountries', $locationCountryIds);
					$this->template->assign('locationTypeStatesSelect', $locationTypeStatesSelect);
				} else if ($arrData['restrictedLocationType'] == 'zip') {
					$this->template->assign('locationTypeZipCountry', current($locationCountryIds));
					$this->template->assign('locationTypeZipPostCodes', implode("\n", $locationValues));
				}
				$this->template->assign('restrictedLocationType', $arrData['restrictedLocationType']);
			}

			// retrieve data related to shipping method restrictions.
			$shippers = GetAvailableModules('shipping', false, false, true);
			foreach ($shippers as $eachShipper) {
				$availableShippers[$eachShipper['id']] = $eachShipper['name'];
			}
			if ($arrData['shipping_method_restricted']) {
				$this->template->assign('selectedShippers', $arrData['restrictedShippingMethods']);
			}

			$this->template->assign('CurrentTab', 0);
			$this->template->assign('availableCountries', $availableCountries);
			$this->template->assign('shippingMethodRestricted', (int)$arrData['shipping_method_restricted']);
			$this->template->assign('availableShippers', $availableShippers);
			$this->template->assign('locationRestricted', (int)$arrData['location_restricted']);
			$this->template->assign('availableCountries', $availableCountries);

			$this->template->assign('coupon', $arrData);
			$GLOBALS['CouponId'] = (int)$arrData['couponid'];
		}

		protected function CreateCouponStep2()
		{
			// validate input first
			$error = $this->_ValidateInput();
			if ($error) {
				FlashMessage($error, MSG_ERROR);
				$this->CreateCouponStep1(true);

				return;
			}

			$error = $this->_CommitCoupon();

			if (empty($error) || (is_numeric($error) && (int)$error > 0)) {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Coupons)) {
					$this->ManageCoupons(GetLang('CouponCreatedSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('CouponCreatedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Coupons)) {
					$this->ManageCoupons(sprintf(GetLang('ErrCouponNotAdded'), $error), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrCouponNotAdded'), $error), MSG_ERROR);
				}
			}
		}

		protected function _ValidateInput($CouponId = 0)
		{
			if (!isset($_POST["couponcode"]) || !trim($_POST["couponcode"])) {
				return GetLang('EnterCouponCode');
			}

			if (!isset($_POST["couponname"]) || !trim($_POST["couponname"])) {
				return GetLang('EnterCouponName');
			}

			if (!isset($_POST['coupontype']) || !isc_is_int($_POST['coupontype'])) {
				return GetLang('EnterCouponType');
			}
			// if account type is freeshipping, the discount amount always set to 0
			else if ($_POST['coupontype'] == 4) {
				$_POST['couponamount'] = 0;
			}

			// check for existing coupon code
			$couponcode = trim($_POST['couponcode']);

			$query = "SELECT * FROM [|PREFIX|]coupons WHERE couponcode = '" . $GLOBALS['ISC_CLASS_DB']->Quote($couponcode) . "'";
			if ($CouponId) {
				$query .= " AND couponid != '" . $GLOBALS['ISC_CLASS_DB']->Quote($CouponId) . "'";
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
				return sprintf(GetLang('CouponCodeExists'), $couponcode);
			}

			// check for exisiting coupon name
			$couponname = trim($_POST['couponname']);

			$query = "SELECT * FROM [|PREFIX|]coupons WHERE couponname = '" . $GLOBALS['ISC_CLASS_DB']->Quote($couponname) . "'";
			if ($CouponId) {
				$query .= " AND couponid != '" . $GLOBALS['ISC_CLASS_DB']->Quote($CouponId) . "'";
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
				return sprintf(GetLang('CouponNameExists'), $couponname);
			}
			// check coupon amount is valid
			if (!(is_numeric($_POST['couponamount']) && $_POST['couponamount'] > 0)) {
				if ($_POST['coupontype'] != 4) {
					return GetLang('EnterValidAmount');
				}
			}

			// check min purchase amount is valid
			$minPurchase = str_replace(',', '', $_POST['couponminpurchase']);
			if ($_POST['couponminpurchase'] && !(is_numeric($minPurchase) && $minPurchase >= 0)) {
				return GetLang('EnterValidMinPrice');
			}

			// check max uses is valid
			if (!(isc_is_int($_POST['couponmaxuses']) && $_POST['couponmaxuses'] >= 0)) {
				return GetLang('EnterValidMaxUses');
			}
			if (!(isc_is_int($_POST['couponmaxusespercus']) && $_POST['couponmaxusespercus'] >= 0)) {
				return GetLang('EnterValidMaxUsesPerCus');
			}

			// check that at least one category or product is ticked
			if ($_POST['usedfor'] == "categories" && empty($_POST['catids'])) {
				return GetLang('ChooseCouponCategory');
			}
			elseif ($_POST['usedfor'] == "products" && empty($_POST['prodids'])) {
				return GetLang('EnterCouponProductId');
			}

			// Validation of coupon location restriction
			$validLocationOptions = array('country', 'state', 'zip');
			if (!empty ($_POST['YesLimitByLocation'])) {
				if (empty ($_POST['LocationType']) || !in_array($_POST['LocationType'], $validLocationOptions)) {
					return GetLang('EnterLocationOption');
				}
				if ($_POST['LocationType'] == 'country') {
					if (empty ($_POST['LocationTypeCountries'])) {
						return GetLang('EnterLocationTypeCountries');
					}
				}
				else if ($_POST['LocationType'] == 'state') {
					if (empty ($_POST['LocationTypeStatesCountries'])) {
						return GetLang('EnterLocationTypeStatesCountries');
					}
					if (empty ($_POST['LocationTypeStatesSelect'])) {
						return GetLang('EnterLocationTypeStatesSelect');
					}
				}
				else if ($_POST['LocationType'] == 'zip') {
					if (empty ($_POST['LocationTypeZipCountry'])) {
						return GetLang('EnterLocationTypeZipCountry');
					}
					$_POST['LocationTypeZipPostCodes'] = trim($_POST['LocationTypeZipPostCodes']);
					if (empty ($_POST['LocationTypeZipPostCodes'])) {
						return GetLang('EnterLocationTypeZipPostCodes');
					}
				}
			}

			// Validation for shipping methods restriction.
			if (!empty ($_POST['YesLimitByShipping'])) {
				if (empty ($_POST['LocationTypeShipping'])) {
					return GetLang('EnterLocationTypeShipping');
				}
			}
		}

		protected function _CommitCoupon($CouponId = 0)
		{
			$name = trim($_POST['couponname']);
			$type = $_POST['coupontype']; // dollar or percent
			$amount = DefaultPriceFormat($_POST['couponamount']);

			$appliesTo = $_POST['usedfor'];

			if($appliesTo == "categories") {
				$appliesValues = $_POST['catids'];
				// nothing selected then default to all categories
				if (empty($appliesValues)) {
					$appliesValues = array('0');
				}
			}
			else {
				$appliesValues = explode(",", $_POST['prodids']);
			}

			if (!empty($_POST['couponexpires'])) {
				$expires = ConvertDateToTime($_POST['couponexpires']);
			} else {
				$expires = 0;
			}

			if (!isset($_POST['couponcode']) || empty($_POST['couponcode'])) {
				$code = GenerateCouponCode();
			}
			else {
				$code = trim($_POST['couponcode']);
			}

			if (isset($_POST['couponenabled'])) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$minPurchase = DefaultPriceFormat($_POST['couponminpurchase']);

			$maxUses = 0;
			$maxUsesPerCus = 0;
			if (isset($_POST['couponmaxuses'])) {
				$maxUses = (int)$_POST['couponmaxuses'];
			}
			if (isset($_POST['couponmaxusespercus'])) {
				$maxUsesPerCus = (int)$_POST['couponmaxusespercus'];
			}

			$locationRestricted = 0;
			if (!empty ($_POST['YesLimitByLocation'])) {
				$locationRestricted = 1;
			}

			$shippingMethodRestricted = 0;
			if (!empty ($_POST['YesLimitByShipping'])) {
				$shippingMethodRestricted = 1;
			}

			$coupon = array(
				'couponname' => $name,
				'coupontype' => $type,
				'couponamount' => $amount,
				'couponminpurchase' => $minPurchase,
				'couponexpires' => $expires,
				'couponenabled' => $enabled,
				'couponcode' => $code,
				'couponappliesto' => $appliesTo,
				'couponmaxuses' => $maxUses,
				'couponmaxusespercus' => $maxUsesPerCus,
				'location_restricted' => $locationRestricted,
				'shipping_method_restricted' => $shippingMethodRestricted,
			);

			// update existing coupon
			if ($CouponId) {
				$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery("coupons", $coupon, "couponid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($CouponId) . "'");
				if (!$result) {
					return "Failed to update coupon";
				}

				//delete existing values
				$query = "DELETE FROM [|PREFIX|]coupon_values WHERE couponid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($CouponId) . "'";
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}
			else {
				// create new coupon
				$CouponId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("coupons", $coupon);

				if (!isId($CouponId)) {
					return "Failed to create coupon";
				}
			}

			// add applies to values
			if (!empty($appliesValues)) {
				foreach ($appliesValues as $value) {
					$couponvalue = array(
						'couponid' => $CouponId,
						'valueid' => $value
					);

					$GLOBALS['ISC_CLASS_DB']->InsertQuery("coupon_values", $couponvalue);
				}
			}

			// Location restriction
			// Remove all the existing ones if exist
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('coupon_locations', "WHERE coupon_id = " . $GLOBALS['ISC_CLASS_DB']->Quote($CouponId));
			if ($locationRestricted) {
				$selectedType = $_POST['LocationType'];

				if ($selectedType == 'country') {
					$countryList = GetCountryListAsIdValuePairs();
					foreach($_POST['LocationTypeCountries'] as $countryId) {
						if(empty ($countryList[$countryId])) {
							continue;
						}
						$newLocation = array(
							'coupon_id'			=> (int)$CouponId,
							'selected_type'		=> $selectedType,
							'value'				=> $countryList[$countryId],
							'value_id'			=> $countryId,
							'country_id'		=> 0,
						);
						$GLOBALS['ISC_CLASS_DB']->InsertQuery('coupon_locations', $newLocation);
					}
				}
				else if ($selectedType == 'state') {
					$countryList = GetCountryListAsIdValuePairs();
					$stateList = array();
					foreach($_POST['LocationTypeStatesSelect'] as $stateRecord) {
						$state = explode('-', $stateRecord, 2);
						if(!isset($stateList[$state[0]])) {
							// Load the states in this country as we haven't done that before
							$stateList[$state[0]] = array();
							$query = "SELECT * FROM [|PREFIX|]country_states WHERE statecountry='".(int)$state[0]."'";
							$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
							while($stateResult = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
								$stateList[$stateResult['statecountry']][$stateResult['stateid']] = $stateResult['statename'];
							}
						}

						// Start storing what we received
						if(isset($stateList[$state[0]][$state[1]])) {
							$stateName = $stateList[$state[0]][$state[1]];
						}
						else {
							$stateName = '';
						}
						$newLocation = array(
							'coupon_id'			=> (int)$CouponId,
							'selected_type'		=> $selectedType,
							'value'				=> $stateName,
							'value_id'			=> (int)$state[1],
							'country_id'		=> (int)$state[0],
						);
						$GLOBALS['ISC_CLASS_DB']->InsertQuery('coupon_locations', $newLocation);
					}
				}
				else if ($selectedType == 'zip') {
					$zipCodes = explode("\n", $_POST['LocationTypeZipPostCodes']);
					foreach($zipCodes as $zipCode) {
						$zipCode = trim($zipCode);
						if(!$zipCode) {
							continue;
						}
						$newLocation = array(
							'coupon_id'			=> (int)$CouponId,
							'selected_type'		=> $selectedType,
							'value'				=> $zipCode,
							'value_id'			=> '0',
							'country_id'		=> (int)$_POST['LocationTypeZipCountry'],
						);
						$GLOBALS['ISC_CLASS_DB']->InsertQuery('coupon_locations', $newLocation);
					}
				}
			}

			// Shipping Method restriction
			// Remove all the existing ones if exist
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('coupon_shipping_methods', "WHERE coupon_id = " . $GLOBALS['ISC_CLASS_DB']->Quote($CouponId));
			if ($shippingMethodRestricted) {
				foreach ($_POST['LocationTypeShipping'] as $shipper) {
					$newShippingMethod = array(
						'coupon_id' => (int)$CouponId,
						'module_id' => $shipper,
					);
					$GLOBALS['ISC_CLASS_DB']->InsertQuery('coupon_shipping_methods', $newShippingMethod);
				}
			}

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($CouponId, $_POST['couponcode']);
			return $CouponId;
		}

		protected function EditCouponStep1($loadFromPost = false)
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			// Show the form to edit a news
			$couponId = (int) $_GET['couponId'];
			$arrData = array();
			$sel_cats = array();

			if (GetConfig('CurrencyLocation') == 'right') {
				$GLOBALS['CurrencyTokenLeft'] = '';
				$GLOBALS['CurrencyTokenRight'] = GetConfig('CurrencyToken');
			} else {
				$GLOBALS['CurrencyTokenLeft'] = GetConfig('CurrencyToken');
				$GLOBALS['CurrencyTokenRight'] = '';
			}

			$GLOBALS['CurrencyToken'] = GetConfig('CurrencyToken');

			if (CouponExists($couponId)) {
				$this->_GetCouponData($couponId, $arrData);
				$GLOBALS['Title'] = GetLang('EditCoupon');
				$GLOBALS['Intro'] = GetLang('EditCouponIntro');
				$GLOBALS['FormAction'] = "editCoupon2";

				$this->_SetFormData($arrData);
				$this->template->display('coupon.form.tpl');

			}
			else {
				// The coupon doesn't exist
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Coupons)) {
					$this->ManageCoupons(GetLang('CouponDoesntExist'), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			}
		}

		protected function EditCouponStep2()
		{
			// Get the information from the form and add it to the database
			$couponId = (int)$_POST['couponId'];

			// validate input first
			$error = $this->_ValidateInput($couponId);
			if ($error) {
				FlashMessage($error, MSG_ERROR);
				$this->CreateCouponStep1(true);

				return;
			}

			$error = $this->_CommitCoupon($couponId);

			// Commit the values to the database
			if (empty($error) || (is_numeric($error) && (int)$error > 0)) {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Coupons)) {
					$this->ManageCoupons(GetLang('CouponUpdatedSuccessfully'), MSG_SUCCESS);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('CouponUpdatedSuccessfully'), MSG_SUCCESS);
				}
			} else {
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Coupons)) {
					$this->ManageCoupons(sprintf(GetLang('ErrCouponNotUpdated'), $error), MSG_ERROR);
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrCouponNotUpdated'), $error), MSG_ERROR);
				}
			}
		}

		protected function EditEnabled()
		{
			// Update the status of a coupon with a simple query
			$couponId = (int)$_GET['couponId'];

			if (CouponExists($couponId)) {
				$query = "UPDATE [|PREFIX|]coupons SET couponenabled = '" . (int)$_GET['enabled'] . "' WHERE couponid = '" . $couponId . "'";
				if ($GLOBALS['ISC_CLASS_DB']->Query($query)) {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Coupons)) {
						$this->ManageCoupons(GetLang('CouponEnabledSuccessfully'), MSG_SUCCESS);
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('CouponEnabledSuccessfully'), MSG_SUCCESS);
					}
				}
				else {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Coupons)) {
						$this->ManageCoupons(sprintf(GetLang('ErrCouponEnabledNotChanged'), $coupon->error), MSG_ERROR);
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(sprintf(GetLang('ErrCouponEnabledNotChanged'), $coupon->error), MSG_ERROR);
					}
				}
			}
		}

		/**
		 * Get the array with value of multi dimension array according to the key on second param
		 *
		 * @param array $searchArray The array where the data would be retrieved from
		 * @param mixed $key the keys that determine what will be retrieved
		 * @return array Return the format of the array that needed from the $searchArray
		 */
		protected function _getNameValueArray($searchArray, $key)
		{
			$results = array();
			foreach ($searchArray as $val) {
				if (!is_array($key) && isset ($val[$key])) {
					$results[] = $val[$key];
				} else if (is_array($key) && isset ($val[$key[0]]) && isset ($val[$key[1]])) {
					$results[$val[$key[0]]][] = $val[$key[1]];
				}
			}
			return $results;
		}
	}