<?php
class ISC_ADMIN_STATISTICS_PRODUCTS extends ISC_ADMIN_STATISTICS
{
	/**
	*	Show statistics for products
	*/
	public function ProductStats()
	{

		if(isset($_POST['Calendar'])) {
			$cal = $this->CalculateCalendarRestrictions($_POST['Calendar']);
			$GLOBALS['CurrentDate'] = $_POST['Calendar']['DateType'];
		}
		else {
			$cal = $this->CalculateCalendarRestrictions();
			$GLOBALS['CurrentDate'] = "Last30Days";
		}

		$GLOBALS['CalendarDateTypeOptions'] = $this->_GetCalendarDateTypesAsOptions($GLOBALS['CurrentDate']);

		if(isset($_POST['currentTab'])) {
			$GLOBALS['CurrentTab'] = (int)$_POST['currentTab'];
		}
		else {
			$GLOBALS['CurrentTab'] = 0;
		}

		// Set the global variables for the select boxes
		$from_stamp = $cal['start'];
		$to_stamp = $cal['end'];

		$from_day = isc_date("d", $from_stamp);
		$from_month = isc_date("m", $from_stamp);
		$from_year = isc_date("Y", $from_stamp);

		$to_day = isc_date("d", $to_stamp);
		$to_month = isc_date("m", $to_stamp);
		$to_year = isc_date("Y", $to_stamp);

		$GLOBALS['OverviewFromDays'] = $this->_GetDayOptions($from_day);
		$GLOBALS['OverviewFromMonths'] = $this->_GetMonthOptions($from_month);
		$GLOBALS['OverviewFromYears'] = $this->_GetYearOptions($from_year);

		$GLOBALS['OverviewToDays'] = $this->_GetDayOptions($to_day);
		$GLOBALS['OverviewToMonths'] = $this->_GetMonthOptions($to_month);
		$GLOBALS['OverviewToYears'] = $this->_GetYearOptions($to_year);

		$GLOBALS['FromStamp'] = $from_stamp;
		$GLOBALS['ToStamp'] = $to_stamp;

		$vendorRestriction = $this->GetVendorRestriction();
		if($vendorRestriction !== false) {
			$GLOBALS['VendorId'] = (int)$vendorRestriction;
		}
		else {
			$GLOBALS['VendorId'] = '';
		}

		// If we can, get a list of the available vendors
		$GLOBALS['HideVendorList'] = 'display: none';
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() == 0 && gzte11(ISC_HUGEPRINT)) {
			$GLOBALS['VendorSelect'] = '';
			// All vendors option
			$sel = '';
			if (!isset($_REQUEST['vendorId']) || $_REQUEST['vendorId'] == "") {
				$sel = 'selected="selected"';
			}
			$GLOBALS['VendorSelect'] .= "<option value='' ".$sel.">".GetLang('AllVendors')."</option>";

			// No vendor option
			$sel = '';
			if(isset($_REQUEST['vendorId']) && $_REQUEST['vendorId'] == "0") {
				$sel = 'selected="selected"';
			}
			$GLOBALS['VendorSelect'] .= "<option value='0' ".$sel.">".GetLang('NoSelVendor')."</option>";
			$query = "
				SELECT vendorid, vendorname
				FROM [|PREFIX|]vendors
				ORDER BY vendorname ASC
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$hasVendors = false;
			while($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$hasVendors = true;
				$sel = '';
				if(isset($_REQUEST['vendorId']) && $_REQUEST['vendorId'] == $vendor['vendorid']) {
					$sel = 'selected="selected"';
				}
				$GLOBALS['VendorSelect'] .= "<option value='".$vendor['vendorid']."' ".$sel.">".isc_html_escape($vendor['vendorname'])."</option>";
			}
			if($hasVendors) {
				$GLOBALS['HideVendorList'] = '';
			}
		}

		/**
		 * Hide the inventory screen if we are starter
		 */
		if (!gzte11(ISC_MEDIUMPRINT)) {
			$GLOBALS['HideInventoryTab'] = 'none';
			$GLOBALS['ShowInventoryGrid'] = '0';
		} else {
			$GLOBALS['HideInventoryTab'] = '';
			$GLOBALS['ShowInventoryGrid'] = '1';
		}

		$this->template->display('stats.products.tpl');
	}

	public function ProductStatsByNumSoldGrid()
	{

		$GLOBALS['OrderGrid'] = "";

		if(isset($_GET['From']) && isset($_GET['To'])) {

			$from_stamp = (int)$_GET['From'];
			$to_stamp = (int)$_GET['To'];

			// How many records per page?
			if(isset($_GET['Show'])) {
				$per_page = (int)$_GET['Show'];
			}
			else {
				$per_page = 20;
			}

			$GLOBALS['ProductsPerPage'] = $per_page;
			$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';

			// Should we limit the records returned?
			if(isset($_GET['Page'])) {
				$page = (int)$_GET['Page'];
			}
			else {
				$page = 1;
			}

			$GLOBALS['ProductsByNumSoldCurrentPage'] = $page;

			// Workout the start and end records
			$start = ($per_page * $page) - $per_page;
			$end = $start + ($per_page - 1);

			// Only fetch products this user can actually see
			$vendorRestriction = $this->GetVendorRestriction();
			$vendorSql = '';
			if($vendorRestriction !== false) {
				$vendorSql = " AND prodvendorid='" . $GLOBALS['ISC_CLASS_DB']->Quote($vendorRestriction) . "'";
			}

			// How many products are there in total?
			$query = "
				SELECT
					COUNT(*) AS num
				FROM
					[|PREFIX|]order_products
					INNER JOIN [|PREFIX|]orders o ON orderorderid = orderid
					LEFT JOIN [|PREFIX|]products ON ordprodid = productid
				WHERE
					ordstatus IN (".implode(',', GetPaidOrderStatusArray()).")
					AND o.deleted = 0
					AND ordprodtype != 'giftcertificate'
					AND ordprodid != 0
					AND orddate >= '" . $from_stamp . "'
					AND orddate <= '" . $to_stamp . "'" .
					$vendorSql;

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$total_products = $row['num'];

			if ($total_products > 0) {
				// Workout the paging
				$num_pages = ceil($total_products / $per_page);
				$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
				$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

				// Is there more than one page? If so show the &laquo; to jump back to page 1
				if($num_pages > 1 && $page != 1) {
					$paging .= "<a href='javascript:void(0)' onclick='ChangeProductsByNumSoldPage(1)'>&laquo;</a> | ";
				}
				else {
					$paging .= "&laquo; | ";
				}

				// Are we on page 2 or above?
				if($page > 1) {
					$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByNumSoldPage(%d)'>%s</a> | ", $page-1, GetLang('Prev'));
				}
				else {
					$paging .= sprintf("%s | ", GetLang('Prev'));
				}

				for($i = 1; $i <= $num_pages; $i++) {
					// Only output paging -5 and +5 pages from the page we're on
					if($i >= $page-6 && $i <= $page+5) {
						if($page == $i) {
							$paging .= sprintf("<strong>%d</strong> | ", $i);
						}
						else {
							$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByNumSoldPage(%d)'>%d</a> | ", $i, $i);
						}
					}
				}

				// Are we on page 2 or above?
				if($page < $num_pages) {
					$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByNumSoldPage(%d)'>%s</a> | ", $page+1, GetLang('Next'));
				}
				else {
					$paging .= sprintf("%s | ", GetLang('Next'));
				}

				// Is there more than one page? If so show the &raquo; to go to the last page
				if($num_pages > 1 && $page != $num_pages) {
					$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByNumSoldPage(%d)'>&raquo;</a> | ", $num_pages);
				}
				else {
					$paging .= "&raquo; | ";
				}

				$paging = rtrim($paging, ' |');
				$GLOBALS['Paging'] = $paging;

				// Should we set focus to the grid?
				if(isset($_GET['FromLink']) && $_GET['FromLink'] == "true") {
					$GLOBALS['JumpToOrdersByItemsSoldGrid'] = "<script type=\"text/javascript\">document.location.href='#ordersByItemsSoldAnchor';</script>";
				}

				if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "asc") {
					$sortOrder = 'asc';
				}
				else {
					$sortOrder = 'desc';
				}

				$sortFields = array('ordprodid','ordprodsku','ordprodname','revenue','numitemssold', 'totalprofit');
				if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
					$sortField = $_GET['SortBy'];
					SaveDefaultSortField("ProductStatsBySold", $_REQUEST['SortBy'], $sortOrder);
				}
				else {
					list($sortField, $sortOrder) = GetDefaultSortField("ProductStatsBySold", "numitemssold", $sortOrder);
				}

				$sortLinks = array(
					"ProductId" => "ordprodid",
					"Code" => "ordprodsku",
					"Name" => "ordprodname",
					"UnitsSold" => "numitemssold",
					"Revenue" => "revenue",
					"Profit" => "totalprofit"
				);
				BuildAdminSortingLinks($sortLinks, "javascript:SortProductsByNumSold('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);

				$itemPriceColumn = 'op.price_ex_tax';
				$costPriceColumn = 'op.cost_price_ex_tax';
				if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
					$itemPriceColumn = 'op.price_inc_tax';
					$costPriceColumn = 'op.cost_price_inc_tax';
				}

				// Fetch the orders for this page
				$query = "
					SELECT
						ordprodid,
						ordprodsku,
						ordprodname,
						SUM(".$itemPriceColumn." * ordprodqty) AS revenue,
						SUM(ordprodqty) as numitemssold,
						IF(cost_price_inc_tax > '0', SUM((".$itemPriceColumn." - ".$costPriceColumn.") * ordprodqty), 0) AS totalprofit,
						productid
					FROM
						[|PREFIX|]order_products op
						INNER JOIN [|PREFIX|]orders o ON op.orderorderid = o.orderid
						LEFT JOIN [|PREFIX|]products p ON p.productid = op.ordprodid
					WHERE
						ordstatus IN (".implode(',', GetPaidOrderStatusArray()).")
						AND o.deleted = 0
						AND ordprodtype != 'giftcertificate'
						AND orddate >= '" . $from_stamp . "'
						AND orddate <= '" . $to_stamp . "'
						AND ordprodid != 0 " .
						$vendorSql . "
					GROUP BY
						ordprodid
					ORDER BY " .
						 $sortField . " " . $sortOrder;

				// Add the Limit
				$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, $per_page);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
					while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

						if($row['totalprofit'] > 0) {
							$total_profit = sprintf("%s", FormatPrice($row['totalprofit']));
						}
						else {
							$total_profit = GetLang('NA');
						}

						$sku = GetLang('NA');
						if($row['ordprodsku']) {
							$sku = isc_html_escape($row['ordprodsku']);
						}

						$prodlink = $row['ordprodname'];
						if (!is_null($row['productid'])) {
							$prodlink = "<a href='" . ProdLink($row['ordprodname']) . "' target='_blank'>" . isc_html_escape($row['ordprodname']) . "</a>";
						}

						$GLOBALS['OrderGrid'] .= sprintf("
							<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
								<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldProductIdClass']."\">
									%d
								</td>
								<td nowrap class=\"".$GLOBALS['SortedFieldCodeClass']."\">
									%s
								</td>
								<td nowrap class=\"".$GLOBALS['SortedFieldNameClass']."\">
									%s</a>
								</td>
								<td nowrap class=\"".$GLOBALS['SortedFieldUnitsSoldClass']."\">
									%s
								</td>
								<td nowrap class=\"".$GLOBALS['SortedFieldRevenueClass']."\">
									%s
								</td>
								<td nowrap class=\"".$GLOBALS['SortedFieldProfitClass']."\">
									%s
								</td>
							</tr>
						", $row['ordprodid'],
						  $sku,
						   $prodlink,
						   (int) $row['numitemssold'],
						   FormatPrice($row['revenue']),
						   $total_profit
						);
					}
				}
			}
			else {
				$GLOBALS['OrderGrid'] .= sprintf("
					<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
						<td nowrap height=\"22\" colspan=\"7\">
							<em>%s</em>
						</td>
					</tr>
				", GetLang('StatsNoOrdersForDate')
				);
			}

			$this->template->display('stats.products.bynumsoldgrid.tpl');
		}
	}

	/**
	*	Show how many times each product has been viewed
	*/
	public function ProductStatsByNumViewsGrid()
	{

		$GLOBALS['OrderGrid'] = "";

		// How many records per page?
		if(isset($_GET['Show'])) {
			$per_page = (int)$_GET['Show'];
		}
		else {
			$per_page = 20;
		}

		$GLOBALS['ProductsPerPage'] = $per_page;
		$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';

		// Should we limit the records returned?
		if(isset($_GET['Page'])) {
			$page = (int)$_GET['Page'];
		}
		else {
			$page = 1;
		}

		$GLOBALS['ProductsByNumViewsCurrentPage'] = $page;

		// Workout the start and end records
		$start = ($per_page * $page) - $per_page;
		$end = $start + ($per_page - 1);

		// Only fetch products this user can actually see
		$vendorRestriction = $this->GetVendorRestriction();
		$vendorSql = '';
		if($vendorRestriction !== false) {
			$vendorSql = " WHERE prodvendorid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($vendorRestriction) . "'";
		}

		// How many products are there in total?
		$query = "
			SELECT
				COUNT(*) AS num
			FROM
				[|PREFIX|]products
			" . $vendorSql;

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$total_products = $row['num'];

		if ($total_products > 0) {
			// Workout the paging
			$num_pages = ceil($total_products / $per_page);
			$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
			$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

			// Is there more than one page? If so show the &laquo; to jump back to page 1
			if($num_pages > 1 && $page != 1) {
				$paging .= "<a href='javascript:void(0)' onclick='ChangeProductsByNumViewsPage(1)'>&laquo;</a> | ";
			}
			else {
				$paging .= "&laquo; | ";
			}

			// Are we on page 2 or above?
			if($page > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByNumViewsPage(%d)'>%s</a> | ", $page-1, GetLang('Prev'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Prev'));
			}

			for($i = 1; $i <= $num_pages; $i++) {
				// Only output paging -5 and +5 pages from the page we're on
				if($i >= $page-6 && $i <= $page+5) {
					if($page == $i) {
						$paging .= sprintf("<strong>%d</strong> | ", $i);
					}
					else {
						$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByNumViewsPage(%d)'>%d</a> | ", $i, $i);
					}
				}
			}

			// Are we on page 2 or above?
			if($page < $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByNumViewsPage(%d)'>%s</a> | ", $page+1, GetLang('Next'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Next'));
			}

			// Is there more than one page? If so show the &raquo; to go to the last page
			if($num_pages > 1 && $page != $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByNumViewsPage(%d)'>&raquo;</a> | ", $num_pages);
			}
			else {
				$paging .= "&raquo; | ";
			}

			$paging = rtrim($paging, ' |');
			$GLOBALS['Paging'] = $paging;

			// Should we set focus to the grid?
			if(isset($_GET['FromLink']) && $_GET['FromLink'] == "true") {
				$GLOBALS['JumpToOrdersByItemsSoldGrid'] = "<script type=\"text/javascript\">document.location.href='#ordersByItemsSoldAnchor';</script>";
			}

			if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "asc") {
				$sortOrder = 'asc';
			}
			else {
				$sortOrder = 'desc';
			}

			$sortFields = array('productid','prodcode','prodname','prodnumviews','avgrating');
			if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
				$sortField = $_GET['SortBy'];
				SaveDefaultSortField("ProductStatsByViews", $_REQUEST['SortBy'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("ProductStatsByViews", "prodnumviews", $sortOrder);
			}

			$sortLinks = array(
				"ProductId" => "productid",
				"Code" => "prodcode",
				"Name" => "prodname",
				"Views" => "prodnumviews",
				"AverageRating" => "avgrating"
			);
			$numSoldCounter = '921124412848294';
			BuildAdminSortingLinks($sortLinks, "javascript:SortProductsByNumViews('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);

			// Fetch the orders for this page
			$query = "
				SELECT
					productid,
					prodcode,
					prodname,
					prodnumviews,
					IF(prodnumratings > 0, prodratingtotal / prodnumratings, 0) AS avgrating
				FROM
					[|PREFIX|]products
				" . $vendorSql . "
				ORDER BY
					" . $sortField . " " . $sortOrder;

			// Add the Limit
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, $per_page);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$code = GetLang('NA');
					if($row['prodcode'] != '') {
						$code = isc_html_escape($row['prodcode']);
					}
					$GLOBALS['OrderGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldProductIdClass']."\">
								%d
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldCodeClass']."\">
								%s
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldNameClass']."\">
								<a href='%s' target='_blank'>%s</a>
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldViewsClass']."\">
								%s
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldAverageRatingClass']."\">
								<img src='".$GLOBALS['IMG_PATH']."/IcoRating%d.gif' />
							</td>
						</tr>
					", $row['productid'],
					   $code,
					   ProdLink($row['prodname']),
					   isc_html_escape($row['prodname']),
					   number_format($row['prodnumviews']),
					   $row['avgrating']
					);
				}
			}
		}
		else {
			$GLOBALS['OrderGrid'] .= sprintf("
				<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
					<td nowrap height=\"22\" colspan=\"5\">
						<em>%s</em>
					</td>
				</tr>
			", GetLang('StatsNoProducts')
			);
		}

		$this->template->display('stats.products.bynumviewsgrid.tpl');
	}

	/**
	*	Show all products by inventory levels
	*/
	public function ProductStatsByInventoryGrid()
	{

		$GLOBALS['OrderGrid'] = "";

		// How many records per page?
		if(isset($_GET['Show'])) {
			$per_page = (int)$_GET['Show'];
		}
		else {
			$per_page = 20;
		}

		$GLOBALS['ProductsPerPage'] = $per_page;
		$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';

		// Should we limit the records returned?
		if(isset($_GET['Page'])) {
			$page = (int)$_GET['Page'];
		}
		else {
			$page = 1;
		}

		$GLOBALS['ProductsByInventoryCurrentPage'] = $page;

		// Workout the start and end records
		$start = ($per_page * $page) - $per_page;
		$end = $start + ($per_page - 1);

		// Only fetch products this user can actually see
		$vendorRestriction = $this->GetVendorRestriction();
		$vendorSql = '';
		if($vendorRestriction !== false) {
			$vendorSql = " WHERE prodvendorid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($vendorRestriction) . "'";
		}

		$showVariations = (@$_GET['Variations'] == '1');

		if ($showVariations) {
			$GLOBALS['ShowHideVariationsText'] = GetLang('HideVariations');
			$GLOBALS['ShowHideVariationsNewValue'] = 0;
		} else {
			$GLOBALS['ShowHideVariationsText'] = GetLang('ShowVariations');
			$GLOBALS['ShowHideVariationsNewValue'] = 1;
		}

		// How many products are there in total?
		if ($showVariations) {
			//	the structure of this query is different when variations are displayed
			$query = "
				SELECT
					COUNT(*) AS num
				FROM
					[|PREFIX|]products
				LEFT JOIN
					[|PREFIX|]product_variation_combinations pvc ON pvc.vcproductid = productid AND pvc.vcvariationid = prodvariationid AND prodinvtrack = 2
				" . $vendorRestriction;
		} else {
			$query = "
				SELECT
					COUNT(*) AS num
				FROM
					[|PREFIX|]products
				" . $vendorRestriction;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$total_products = $row['num'];

		if ($total_products > 0) {
			// Workout the paging
			$num_pages = ceil($total_products / $per_page);
			$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
			$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

			// Is there more than one page? If so show the &laquo; to jump back to page 1
			if($num_pages > 1 && $page != 1) {
				$paging .= "<a href='javascript:void(0)' onclick='ChangeProductsByInventoryPage(1)'>&laquo;</a> | ";
			}
			else {
				$paging .= "&laquo; | ";
			}

			// Are we on page 2 or above?
			if($page > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByInventoryPage(%d)'>%s</a> | ", $page-1, GetLang('Prev'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Prev'));
			}

			for($i = 1; $i <= $num_pages; $i++) {
				// Only output paging -5 and +5 pages from the page we're on
				if($i >= $page-6 && $i <= $page+5) {
					if($page == $i) {
						$paging .= sprintf("<strong>%d</strong> | ", $i);
					}
					else {
						$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByInventoryPage(%d)'>%d</a> | ", $i, $i);
					}
				}
			}

			// Are we on page 2 or above?
			if($page < $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByInventoryPage(%d)'>%s</a> | ", $page+1, GetLang('Next'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Next'));
			}

			// Is there more than one page? If so show the &raquo; to go to the last page
			if($num_pages > 1 && $page != $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeProductsByInventoryPage(%d)'>&raquo;</a> | ", $num_pages);
			}
			else {
				$paging .= "&raquo; | ";
			}

			$paging = rtrim($paging, ' |');
			$GLOBALS['Paging'] = $paging;

			if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "desc") {
				$sortOrder = 'desc';
			}
			else {
				$sortOrder = 'asc';
			}

			$sortFields = array('productid', 'prodcode', 'prodname', 'prodnumviews', 'prodcurrentinv');
			if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
				$sortField = $_GET['SortBy'];
				SaveDefaultSortField("ProductStatsByInventory", $_REQUEST['SortBy'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("ProductStatsByInventory", "prodcurrentinv", $sortOrder);
			}

			$sortLinks = array(
				"ProductId" => "productid",
				"Code" => "prodcode",
				"Name" => "prodname",
				"Views" => "prodnumviews",
				"Stock" => "prodcurrentinv"
			);
			BuildAdminSortingLinks($sortLinks, "javascript:SortProductsByInventory('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);

			// Fetch the products and inventory levels for this page
			if ($showVariations) {
				//	the structure of this query is different when variations are displayed
				$query = "
					SELECT /* ISC_ADMIN_STATISTICS_PRODUCTS->ProductStatsByInventoryGrid */
						productid,
						prodcode,
						prodname,
						prodnumviews,
						prodinvtrack,
						combinationid,
						voname,
						vcstock,
						vcsku,
						prodcurrentinv,
						vcoptionids
					FROM [|PREFIX|]products
					LEFT JOIN [|PREFIX|]product_variation_combinations pvc ON pvc.vcproductid = productid AND pvc.vcvariationid = prodvariationid AND prodinvtrack = 2
					LEFT JOIN [|PREFIX|]product_variation_options pvo ON pvo.voptionid = pvc.vcoptionids
					 " . $vendorSql . "
					ORDER BY ". $sortField . " " . $sortOrder . ", productid " . $sortOrder . ", vcsku " . $sortOrder
				;
			} else {
				$query = "
					SELECT /* ISC_ADMIN_STATISTICS_PRODUCTS->ProductStatsByInventoryGrid */
						productid,
						prodcode,
						prodname,
						prodnumviews,
						prodinvtrack,
						0 as combinationid,
						prodcurrentinv
					FROM [|PREFIX|]products
					 " . $vendorSql . "
					ORDER BY ". $sortField . " " . $sortOrder
				;

			}

			// Add the Limit
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, $per_page);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			// for displaying variations we need to track each row to see if we're starting a new 'product'
			$previousProductId = 0;

			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

					switch($row['prodinvtrack']) {
						case 0: { // Not tracking
							$tracking_method = GetLang('NA');
							break;
						}
						case 1: { // By product
							$tracking_method = GetLang('StatsByProduct');
							break;
						}
						case 2: { // By option
							$tracking_method = GetLang('StatsByProductOption');
							break;
						}
					}

					switch($row['prodinvtrack']) {
						case 0: {
							$stock_level = GetLang('NA');
							$edit_link = sprintf("<span class='disabled'>%s</span>", GetLang('UpdateStockLevels'));
							break;
						}
						default: {
							$stock_level = number_format($row['prodcurrentinv']);
							$edit_link = sprintf("<a href='index.php?ToDo=viewProducts&amp;productId=%d' target='_blank'>%s</span>", $row['productid'], GetLang('UpdateStockLevels'));

							if($stock_level == 0) { // Flag if zero
								$stock_level = sprintf("<b style='color:red'>%s</strong>", $stock_level);
							}
						}
					}
					$sku = GetLang('NA');
					if($row['prodcode'] != '') {
						$sku = isc_html_escape($row['prodcode']);
					}

					$productHasVariations = ((int)$row['prodinvtrack'] == 2);

					if ($row['productid'] != $previousProductId) {
						// this is a product without variations, or a product with variations that we're displaying for the first time in this loop

						$GLOBALS['OrderGrid'] .= sprintf("
							<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
								<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldProductIdClass']."\">
									%d
								</td>
								<td class=\"".$GLOBALS['SortedFieldCodeClass']."\">
									%s
								</td>
								<td class=\"".$GLOBALS['SortedFieldNameClass']."\">
									<a href='%s' target='_blank'>%s</a>
								</td>
								<td>
									%s
								</td>
								<td class=\"".$GLOBALS['SortedFieldStockClass']."\">
									%s
								</td>
								<td>
									%s
								</td>
							</tr>
						", $row['productid'],
						   $sku,
						   ProdLink($row['prodname']),
						   isc_html_escape($row['prodname']),
						   $tracking_method,
						   $stock_level,
						   $edit_link
						);
					}

					if ($productHasVariations && $showVariations) {
						// this is a product variation
						$variationRowTitle = sprintf(GetLang('VariationOf'), $row['prodname'], $sku);

						$variationSku = @$row['vcsku'];
						if (!$variationSku) {
							$variationSku = GetLang('NA');
						}

						// selecting name of variation combination was originally in the main query above, but was moved to here for performance reasons
						$sql = "SELECT GROUP_CONCAT(CONCAT(voname, ': ', vovalue) ORDER BY vooptionsort ASC SEPARATOR ', ') AS `description` FROM [|PREFIX|]product_variation_options WHERE voptionid IN (" . $row['vcoptionids'] . ")";
						$description = $this->db->FetchRow($sql);

						$stock_level = $row['vcstock'];
						if (!$stock_level) {
							$stock_level = "<b style='color:red'>" . $stock_level . "</strong>";
						}

						$GLOBALS['OrderGrid'] .= '<tr class="GridRow" onmouseover="this.className=\'GridRowOver\';" onmouseout="this.className=\'GridRow\';" title="' . isc_html_escape($variationRowTitle) . '">' .
													'<td nowrap="nowrap" height="22" class="Variation ' . $GLOBALS['SortedFieldProductIdClass'] . '">' . $row['productid'] . '</td>' .
													'<td class="Variation VariationSku VariationBackground ' . $GLOBALS['SortedFieldCodeClass'] . '">' . isc_html_escape($variationSku) . '</td>' .
													'<td class="Variation VariationItem ' . $GLOBALS['SortedFieldNameClass'] . '">' . isc_html_escape($description['description']) . '</td>' .
													'<td>&nbsp;</td>' .
													'<td class="Variation VariationStock ' . $GLOBALS['SortedFieldStockClass'] . '">' . $stock_level . '</td>' .
													'<td>&nbsp;</td>' .
													'</tr>';
					}

					$previousProductId = $row['productid'];
				}
			}
		}
		else {
			$GLOBALS['OrderGrid'] .= sprintf("
				<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
					<td nowrap height=\"22\" colspan=\"6\">
						<em>%s</em>
					</td>
				</tr>
			", GetLang('StatsNoProducts')
			);
		}

		$this->template->display('stats.products.byinventorygrid.tpl');
	}
}
