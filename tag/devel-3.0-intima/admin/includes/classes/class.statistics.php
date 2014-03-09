<?php

class ISC_ADMIN_STATISTICS extends ISC_ADMIN_BASE
{
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('statistics');
	}

	public function HandleToDo($Do)
	{
		$GLOBALS['GoogleMapsAPIKey'] = isc_html_escape(GetConfig('GoogleMapsAPIKey'));
		$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Statistics') => "index.php?ToDo=viewStats");

		switch (isc_strtolower($Do)) {
			case "prodstatsbyinventorygrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Products)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_PRODUCTS');
				$statsClass->ProductStatsByInventoryGrid();
				break;
			}
			case "prodstatsbynumviewsgrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Products)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_PRODUCTS');
				$statsClass->ProductStatsByNumViewsGrid();
				break;
			}
			case "prodstatsbynumsoldgrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Products)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_PRODUCTS');
				$statsClass->ProductStatsByNumSoldGrid();
				break;
			}
			case "viewprodstats": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Products)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_PRODUCTS');
				$statsClass->ProductStats();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			}
			case "custstatsbyrevenuegrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Customers)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_CUSTOMERS');
				$statsClass->CustomerStatsByRevenueGrid();
				break;
			}
			case "ordstatsbyrevenuegrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_ORDERS');
				$statsClass->OrderStatsByRevenueGrid();
				break;
			}
			case "ordstatsbyrevenuedata": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders)) {
					exit;
				}
				$this->SendGraphDataHeaders();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_ORDERS');
				$statsClass->OrderStatsByRevenueData();
				break;
			}
			case "ordstatsbyitemssoldgrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_ORDERS');
				$statsClass->OrderStatsByItemsSoldGrid();
				break;
			}
			case "ordstatsbydategrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_ORDERS');
				$statsClass->OrderStatsByDateGrid();
				break;
			}
			case "custstatsbydategrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Customers)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_CUSTOMERS');
				$statsClass->CustomerStatsByDateGrid();
				break;
			}
			case "custstatsbydatedata": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Customers)) {
					exit;
				}
				$this->SendGraphDataHeaders();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_CUSTOMERS');
				$statsClass->CustomerStatsByDateData();
				break;
			}
			case "viewcuststats": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Customers)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_CUSTOMERS');
				$statsClass->CustomerStats();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			}
			case "viewordstats": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_ORDERS');
				$statsClass->OrderStats();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			}
			case "overviewstatsordlocationchart": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Overview)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_OVERVIEW');
				$statsClass->OverviewOrderLocationChart();
				break;
			}
			case "overviewstatstop20prods": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Overview)) {
					exit;
				}
				$this->SendGraphDataHeaders();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_OVERVIEW');
				$statsClass->GetOverviewStatsTop20ProductsData();
				break;
			}
			case "overviewstatstop20custdata": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Overview)) {
					exit;
				}
				$this->SendGraphDataHeaders();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_OVERVIEW');
				$statsClass->GetOverviewStatsTop20CustomersData();
				break;
			}
			case "overviewstatsdata": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Overview) && !$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders)) {
					exit;
				}
				$this->SendGraphDataHeaders();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_OVERVIEW');
				$statsClass->GetOverviewStatsData();
				break;
			}
			case "viewsearchstats": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Search)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_SEARCH');
				$statsClass->SearchStats();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			}
			case "searchstatsoverviewdata": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Search)) {
					exit;
				}
				$this->SendGraphDataHeaders();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_SEARCH');
				$statsClass->GetSearchStatsOverviewData();
				break;
			}
			case "searchstatswithresultsgrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Search)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_SEARCH');
				$statsClass->SearchStatsWithResultsGrid();
				break;
			}
			case "searchstatswithoutresultsgrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Search)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_SEARCH');
				$statsClass->SearchStatsWithoutResultsGrid();
				break;
			}
			case "searchstatsbestperforminggrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Search)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_SEARCH');
				$statsClass->SearchStatsPerformanceGrid();
				break;
			}
			case "searchstatsworstperforminggrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Search)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_SEARCH');
				$statsClass->SearchStatsPerformanceGrid();
				break;
			}
			case "searchstatscorrectionsgrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Search)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_SEARCH');
				$statsClass->SearchStatsCorrectionsGrid();
				break;
			}
			case "clearsearchstats": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Search)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_SEARCH');
				$statsClass->ClearSearchStats();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
			}
			case "ordstatsbyabandongrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_ORDERS');
				$statsClass->OrderStatsByAbandonGrid();
				break;
			}
			case "taxstatsbydategrid": {
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders)) {
					exit;
				}
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_ORDERS');
				$statsClass->TaxStatsByDateGrid();
				break;
			}
			default:
				if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Overview)) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$statsClass = GetClass('ISC_ADMIN_STATISTICS_OVERVIEW');
				$statsClass->Overview();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
		}
	}

	/**
	 * Send the headers that are required to get the graph to display under https in IE6
	 *
	 * @return void
	 * @author Rodney Amato
	 **/
	protected function SendGraphDataHeaders()
	{
		header('Pragma: private');
		header("Cache-control: private");
	}

	/**
		*	Return a list of days as option tags
		*/
	protected function _GetDayOptions($Selected=0)
	{
		$output = "";

		for($i = 1; $i <= 31; $i++) {
			if($Selected == $i) {
				$sel = 'selected="selected"';
			}
			else {
				$sel = "";
			}

			$output .= sprintf("<option value='%d' %s>%s</option>", $i, $sel, $i);
		}

		return $output;
	}

	/**
		*	Return a list of months as option tags
		*/
	protected function _GetMonthOptions($Selected=0)
	{
		$output = "";

		for($i = 1; $i <= 12; $i++) {
			if($Selected == $i) {
				$sel = 'selected="selected"';
			}
			else {
				$sel = "";
			}

			$stamp = isc_gmmktime(0, 0, 0, $i, 1, 2000);
			$month = isc_date("M", $stamp);
			$output .= sprintf("<option value='%d' %s>%s</option>", $i, $sel, $month);
		}

		return $output;
	}

	/**
		*	Return a list of years as option tags
		*/
	protected function _GetYearOptions($Selected=0)
	{

		$output = "";

		for($i = isc_date("Y")-5; $i <= isc_date("Y")+5; $i++) {
			if($Selected == $i) {
				$sel = 'selected="selected"';
			}
			else {
				$sel = "";
			}

			$output .= sprintf("<option value='%d' %s>%s</option>", $i, $sel, $i);
		}

		return $output;
	}

	/**
		*	Get a list of date types as option tags
		*/
	protected function _GetCalendarDateTypesAsOptions($Selected = "")
	{

		$output = "";
		$date_types = array("Today" => GetLang('Today'),
			"Yesterday" => GetLang('Yesterday'),
			"Last24Hours" => GetLang('Last24Hours'),
			"Last7Days" => GetLang('Last7Days'),
			"Last30Days" => GetLang('Last30Days'),
			"ThisMonth" => GetLang('ThisMonth'),
			"AllTime" => GetLang('AllTime'),
			"Custom" => GetLang('Custom')
			);

		foreach($date_types as $val=>$text) {
			if($val == $Selected) {
				$sel = 'selected="selected"';
			}
			else {
				$sel = "";
			}

			$output .= sprintf("<option value='%s' %s>%s</option>", $val, $sel, $text);
		}

		return $output;
	}

	/**
		*	Return a fromdate and todate between which to show stats
		*/
	protected function CalculateCalendarRestrictions($calendarinfo=array())
	{

		$rightnow = isc_gmmktime(isc_date("H"), isc_date("i"), isc_date("s"), isc_date("m"), isc_date("d"), isc_date("Y"));

		$today = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d"), isc_date("Y"));
		$yesterday = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d")-1, isc_date("Y"));

		if(isset($calendarinfo['DateType'])) {
			switch(isc_strtolower($calendarinfo['DateType'])) {
				case "today": {
					$startdate = $today;
					$enddate = $rightnow;
					break;
				}
				case "yesterday": {
					$startdate = $yesterday;
					$enddate = $today-1;
					break;
				}
				case "last24hours": {
					$startdate = $rightnow - 86400;
					$enddate = $rightnow;
					break;
				}
				case "last7days": {
					$startdate = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d") - 7, isc_date("Y"));
					$enddate = $rightnow;
					break;
				}
				case "last30days": {
					$startdate = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d")-30, isc_date("Y"));
					$enddate = $rightnow;
					break;
				}
				case "thismonth": {
					$startdate = isc_gmmktime(0, 0, 0, isc_date("m"), 1, isc_date("Y"));
					$enddate = $rightnow;
					break;
				}
				case "lastmonth": {
					$startdate = isc_gmmktime(0, 0, 0, isc_date("m")-1, 1, isc_date("Y"));
					$enddate = isc_gmmktime(23, 59, 59, isc_date("m"), 0, isc_date("Y"));
					break;
				}
				case "alltime": {
					$startdate = GetConfig('InstallDate');
					$enddate = $rightnow;
					break;
				}
				case "custom": {
					$startdate = isc_gmmktime(0, 0, 0, $calendarinfo['From']['Mth'], $calendarinfo['From']['Day'], $calendarinfo['From']['Yr']);
					$enddate = isc_gmmktime(23, 59, 59, $calendarinfo['To']['Mth'], ($calendarinfo['To']['Day']), $calendarinfo['To']['Yr']);
					break;
				}
			}
		}
		else {
			// Default to last 30 days
			$startdate = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d")-30, isc_date("Y"));
			$enddate = $rightnow;
		}

		return array("start" => $startdate,
			"end" => $enddate
			);
	}

	/**
	*	Get the total revenue and number of completed orders (ordstatus=2 or 10) between two timestamps
	*/
	protected function _GetOrderValueForPeriod($FromStamp, $ToStamp)
	{
		$vendorRestriction = $this->GetVendorRestriction();
		$vendorSql = '';
		if($vendorRestriction !== false) {
			$vendorSql = " AND ordvendorid='".(int)$vendorRestriction."'";
		}

		$query = "
			SELECT count(orderid) AS num, SUM(total_inc_tax) AS total
			FROM [|PREFIX|]orders
			WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".(int)$FromStamp."' AND orddate < '".(int)$ToStamp."' ".$vendorSql."
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$vals = array(
			"total" => $row['total'],
			"count" => $row['num']
		);

		//searching unique vistor based on the GMT time, because the unique visitors are saved in GMT time
		$FromStamp = mktime(0, 0, 0, isc_date("m", $FromStamp), isc_date("d", $FromStamp), isc_date("y", $FromStamp));
		$ToStamp = mktime(0, 0, 0, isc_date("m", $ToStamp), isc_date("d", $ToStamp), isc_date("y", $ToStamp));

		// Workout the number of unique visitors for the period
		$query = "
			SELECT SUM(numuniques) AS visitors
			FROM [|PREFIX|]unique_visitors
			WHERE datestamp >='".(int)$FromStamp."' AND datestamp <= '".(int)$ToStamp."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$vals['uniques'] = $row['visitors'];

		return $vals;
	}

	/**
	 * Return the SQL for the restriction of statistics to certain vendors.
	 * If the current user belongs to a vendor, it will return that vendor ID.
	 * If not, and a vendor ID is in the request, that vendor ID will be returned.
	 * Otherwise, there is no restriction (show from all vendors) - returns false
	 *
	 * @return mixed Integer for the vendor ID if we're filtering on a vendor ID, false if not.
	 */
	protected function GetVendorRestriction()
	{
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
			return (int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
		}
		else if(isset($_REQUEST['vendorId']) && $_REQUEST['vendorId'] !== '') {
			return (int)$_REQUEST['vendorId'];
		}
		else {
			return false;
		}
	}
}
