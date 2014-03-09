<?php
class ISC_ADMIN_STATISTICS_OVERVIEW extends ISC_ADMIN_STATISTICS
{
	/**
	*	Show a general overview of how the shop is performing including number of orders
	*/
	public function Overview()
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

		// Set the global variables for the select boxes
		$from_stamp = $cal['start'];
		$to_stamp = $cal['end'];

		$from_days = $from_stamp / 86400;
		$to_days = $to_stamp / 86400;
		$num_days = floor($to_days - $from_days)+1;

		// If we're looking at only one day then we don't show unique visitors
		// or conversion rates because they're stored per-day and we don't
		// have hourly values for them
		if($num_days > 1) {
			$GLOBALS['HideNoAdvancedStatsMessage'] = "none";
		}

		$from_day = isc_date("d", $from_stamp);
		$from_month = isc_date("m", $from_stamp);
		$from_year = isc_date("Y", $from_stamp);

		$to_day = isc_date("d", $to_stamp);
		$to_month = isc_date("m", $to_stamp);
		$to_year = isc_date("Y", $to_stamp);

		// Get the total cost and number of orders for the period
		$order_details = $this->_GetOrderValueForPeriod($from_stamp, $to_stamp);

		if(is_array($order_details)) {
			$GLOBALS['OverviewOrderTotal'] = $order_details['total'];
			$GLOBALS['OverviewOrderCount'] = $order_details['count'];
			$GLOBALS['OverviewUniqueVisitors'] = $order_details['uniques'];

			// Workout the conversion rate
			if($order_details['uniques'] > 0) {
				$conversion_rate = ($order_details['count'] / $order_details['uniques']) * 100;
			}
			else {
				$conversion_rate = 0;
			}

			$GLOBALS['OverviewConversionRate'] = sprintf("%.2f%%", $conversion_rate);
		}
		else {
			$GLOBALS['OverviewOrderTotal'] = 0;
			$GLOBALS['OverviewOrderCount'] = 0;
			$GLOBALS['OverviewUniqueVisitors'] = 0;
			$GLOBALS['OverviewConversionRate'] = 0;
		}

		// Set the title of the chart
		if($GLOBALS['OverviewOrderCount'] == 1) {
			$lang_var = "OverviewChartTitle1";
		}
		else {
			$lang_var = "OverviewChartTitleX";
		}

		$GLOBALS['OverviewOrderTotal'] = FormatPrice($GLOBALS['OverviewOrderTotal']);
		$GLOBALS['OverviewUniqueVisitors'] = number_format($GLOBALS['OverviewUniqueVisitors']);

		$GLOBALS['ChartTitle'] = sprintf(GetLang($lang_var), $GLOBALS['OverviewOrderCount'], sprintf("%s", $GLOBALS['OverviewOrderTotal']), isc_date(GetConfig('DisplayDateFormat'), $from_stamp), isc_date(GetConfig('DisplayDateFormat'), $to_stamp));

		$GLOBALS['OverviewFromDays'] = $this->_GetDayOptions($from_day);
		$GLOBALS['OverviewFromMonths'] = $this->_GetMonthOptions($from_month);
		$GLOBALS['OverviewFromYears'] = $this->_GetYearOptions($from_year);

		$GLOBALS['OverviewToDays'] = $this->_GetDayOptions($to_day);
		$GLOBALS['OverviewToMonths'] = $this->_GetMonthOptions($to_month);
		$GLOBALS['OverviewToYears'] = $this->_GetYearOptions($to_year);

		// Set the from and to date stamps
		$GLOBALS['OverviewFromStamp'] = $cal['start'];
		$GLOBALS['OverviewToStamp'] = $cal['end'];

		if(isset($_POST['currentTab'])) {
			$GLOBALS['CurrentTab'] = (int)$_POST['currentTab'];
		}
		else {
			$GLOBALS['CurrentTab'] = 0;
		}

		$GLOBALS['IntroText0'] = sprintf("%s \'%s\' %s", GetLang('StatsOverviewIntro1'), $GLOBALS['StoreName'], GetLang('StatsOverviewIntro2'));
		$GLOBALS['IntroText1'] = GetLang('Top20CustomersIntro');
		$GLOBALS['IntroText2'] = GetLang('BestSellingProductsIntro');

		// Is a Google Maps API key set? If not show an error
		if(GetConfig('GoogleMapsAPIKey') != "") {
			$GLOBALS['IntroText3'] = GetLang('CustomerLocationsIntro');
		}
		else {
			$GLOBALS['IntroText3'] = GetLang('CustomerLocationsIntroNoAPIKey');
		}

		// Load the top 20 customers grid
		$GLOBALS['TopCustomersGrid'] = $this->_Top20CustomersGrid($from_stamp, $to_stamp, $num);

		// Hide the top 20 customers chart if there are no results
		if($num == 0) {
			$GLOBALS['HideTop20CustomersChart'] = "none";
		}

		// Load the top 20 products grid
		$GLOBALS['TopProductsGrid'] = $this->_Top20ProductsGrid($from_stamp, $to_stamp, $num);

		// Hide the top 20 products chart if there are no results
		if($num == 0) {
			$GLOBALS['HideTop20ProductsChart'] = "none";
		}

		$GLOBALS['FromStamp'] = $from_stamp;
		$GLOBALS['ToStamp'] = $to_stamp;
		$this->template->display('stats.overview.tpl');
	}

	/**
	*	Get a list of the top 20 customers and return them as <tr> tags
	*/
	public function _Top20CustomersGrid($FromStamp, $ToStamp, &$NumResults)
	{

		$output = "";
		$query = "
			SELECT
				COUNT(orderid) AS numorders,
				SUM(total_inc_tax) AS total,
				ordcustid,
				CONCAT(custconfirstname, ' ', custconlastname) AS name,
				custconemail,
				CONCAT(ordbillfirstname, ' ',  ordbilllastname) AS billname,
				ordbillemail,
				customerid
			FROM
				[|PREFIX|]orders o
				LEFT JOIN [|PREFIX|]customers ON ordcustid = customerid
			WHERE
				ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
				o.deleted = 0 AND
				orddate >= '" . $FromStamp . "' AND
				orddate <= '" . $ToStamp . "'
			GROUP BY
				ordbillemail
			ORDER BY
				numorders DESC
			LIMIT
				20
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$output = sprintf("
			<table width=\"100%%\" border=0 cellspacing=1 cellpadding=5 class=\"text\">
				<tr class=\"Heading3\">
					<td nowrap align=\"left\">
						%s
					</td>
					<td nowrap align=\"left\">
						%s
					</td>
					<td nowrap align=\"left\">
						%s
					</td>
					<td nowrap align=\"left\">
						%s
					</td>
				</tr>
		", GetLang('StatsCustomerName'), GetLang('StatsEmail'), GetLang('StatsOrders'), GetLang('StatsAmountSpent'), GetLang('StatsDateJoined'));

		$NumResults = $GLOBALS['ISC_CLASS_DB']->CountResult($result);

		if($NumResults > 0) {
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if(!is_null($row['customerid'])) {
					$customerLink = "<a href=\"index.php?ToDo=viewCustomers&searchQuery=".(int) $row['customerid']."\">".isc_html_escape($row['name'])."</a>";
					$email = $row['custconemail'];
				}
				else {
					$customerLink = isc_html_escape($row['billname']);
					$email = $row['ordbillemail'];
				}

				$output .= sprintf("
					<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
						<td nowrap height=\"22\">
							%s
						</td>
						<td nowrap>
							<a href=\"mailto:%s\">%s</a>
						</td>
						<td nowrap>
							%d
						</td>
						<td nowrap>
							%s
						</td>
					</tr>
				", $customerLink, isc_html_escape($email), isc_html_escape($email), $row['numorders'], FormatPrice($row['total']), isc_date(GetConfig('DisplayDateFormat')));
			}
		}
		else {
				$output .= sprintf("
					<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
						<td colspan=\"5\" nowrap height=\"22\">
							<em>%s</em>
						</td>
					</tr>
				", GetLang('StatsNoTopCustomers'));
		}

		$output .= "</table>";
		return $output;
	}

	/**
	*	Load up the orders and organize them based on the date when they were ordered.
	*	The "from" and "to" timestamps are passed in to determine between which dates
	*	we will retrieve the orders.
	*/
	public function GetOverviewStatsData()
	{
		if(isset($_GET['from']) && is_numeric($_GET['from']) && isset($_GET['to']) && is_numeric($_GET['to'])) {

			$orders = array();
			$conversions = array();
			$from = (int)$_GET['from'];
			$to = (int)$_GET['to'];
			$x_counter = 0;

			$visitor_xml = "";
			$visitor_dates = array();
			$visitor_rows = array();
			$conversion_xml = "";

			// Create the first components of the XML block
			$xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
			$xml .= "<chart>\n";
			$xml .= "	<xaxis>\n";

			// Start by building the axis' on the chart based on the selected date range
			// Convert the from and to dates into days then substract "to" from "from" to
			// workout the number of days worth of data we need to chart

			$from_days = $from / 86400;
			$to_days = $to / 86400;
			$num_days = floor($to_days - $from_days)+1;
			// How many days do we have to show data for? We will break up the data as follows:
			//		0-1 days (shown as hours on x axis)
			//		1-60 days (shown as days on x axis)
			//		61-182 days (shown as weeks on x axis)
			//		182-730 days (shown as months on x axis)
			//		730+ days (shown as years on x axis)

			$day_format = "g:00 A (jS M Y)";
			$week_format = "\W\e\e\k W";
			$month_format = "M Y";
			$year_format = "Y";

			if($num_days <= 1) {
				// Get orders and show them for each hour
				$num_val = $num_days;

				if($num_val == 0) {
					$num_val = 1;
				}

				for($i = 0; $i < $num_val*25; $i++) {
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", isc_date($day_format, $from + (3600 * $i)), isc_date($day_format, $from + (3600 * $i)));
					$orders[isc_date($day_format, $from + (3600 * $i))] = 0;
				}
			}
			else if($num_days > 1 && $num_days <= 60) {
				// Get orders and show them for each day
				for($i = 0; $i < $num_days; $i++) {
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i)), isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i)));
					$orders[isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i))] = 0;
					$visitor_dates[] = array("format" => isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i)),
											 "stamp" => $from + (86400 * $i)
					);

					// Track the conversion rate
					$conversions[isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i))] = 0;
				}
			}
			else if($num_days > 60 && $num_days <= 182) {
				// Get orders and show them for each week
				$num_weeks = ceil($num_days / 7);

				for($i = 0; $i < $num_weeks+1; $i++) {
					$extended_stamp = sprintf("%s -\n %s", isc_date(GetConfig('DisplayDateFormat'), $from + (604800 * $i)), isc_date(GetConfig('DisplayDateFormat'), $from + (604800 * $i + (86400*7))));
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", isc_date($week_format, $from + (604800 * $i)), $extended_stamp);
					$orders[isc_date($week_format, $from + (604800 * $i))] = 0;
					$visitor_dates[] = array("format" => isc_date(GetConfig('DisplayDateFormat'), $from + (604800 * $i)),
											 "stamp" => $from + (604800 * $i)
					);

					// Track the conversion rate
					$conversions[isc_date(GetConfig('DisplayDateFormat'), $from + (604800 * $i))] = 0;
				}
			}
			else if($num_days > 182 && $num_days <= 730) {
				// Get orders and show them for each month
				$num_months = ceil($num_days / 31)+1;

				$from_month = isc_date("m", $from);
				$from_year = isc_date("Y", $from);

				for($i = 0; $i < $num_months+1; $i++) {
					// Workout the timestamp for the first day of the month
					$first_day_stamp = isc_mktime(0, 0, 0, $from_month+$i, 1, $from_year);
					$output_format = isc_date($month_format, $first_day_stamp);
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", $output_format, $output_format);
					$orders[$output_format] = 0;
					$visitor_dates[] = array("format" => $output_format,
											 "stamp" => $first_day_stamp
					);

					// Track the conversion rate
					$conversions[$output_format] = 0;
				}
			}
			else if($num_days > 730) {
				// Get orders and show them for each year
				$num_years = ceil($num_days / 365)+1;
				$from_year = isc_date("Y", $from);

				for($i = 0; $i < $num_years+1; $i++) {
					// Workout the timestamp for the first day of the year
					$first_day_stamp = isc_mktime(0, 0, 0, 1, 1, $from_year+$i);
					$output_format = isc_date($year_format, $first_day_stamp);
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", $output_format, $output_format);
					$orders[isc_date($year_format, $from + (31536000 * $i))] = 0;
					$visitor_dates[] = array("format" => isc_date($year_format, $from + (31536000 * $i)),
											 "stamp" => $from + (31536000 * $i)
					);

					// Track the conversion rate
					$conversions[isc_date($year_format, $from + (31536000 * $i))] = 0;
				}
			}

			$xml .= "	</xaxis>\n";
			$xml .= "	<graphs>\n";
			$xml .= "		<graph gid=\"1\">\n";

			// Start the graph that shows number of orders

			// Only fetch products this user can actually see
			$vendorRestriction = $this->GetVendorRestriction();
			$vendorSql = '';
			if($vendorRestriction !== false) {
				$vendorSql = " AND ordvendorid='".(int)$vendorRestriction."'";
			}

			if($num_days <= 1) {
				// Get orders and show them for each hour
				$query = "
					SELECT orddate
					FROM [|PREFIX|]orders
					WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".$from."' AND orddate <= '".$to."'
					".$vendorSql."
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the orders based on the day they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					if(isset($orders[isc_date($day_format, $row['orddate'])])) {
						$orders[isc_date($day_format, $row['orddate'])]++;
					}
				}

				// We now have the orders in an array based on the date they were ordered,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($orders as $order_date=>$order_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $order_date, $order_count);
				}
			}
			else if($num_days > 1 && $num_days <= 60) {
				// Get orders and show them for each day
				$query = "
					SELECT orddate
					FROM [|PREFIX|]orders
					WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".$from."' AND orddate <= '".$to."'
					".$vendorSql."
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the orders based on the day they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					if (isset($orders[isc_date(GetConfig('DisplayDateFormat'), $row['orddate'])])) {
						$orders[isc_date(GetConfig('DisplayDateFormat'), $row['orddate'])]++;
					} else {
						$orders[isc_date(GetConfig('DisplayDateFormat'), $row['orddate'])] = 1;
					}
				}

				// We now have the orders in an array based on the date they were ordered,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($orders as $order_date=>$order_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $order_date, $order_count);
					$conversions[$order_date] = array("orders" => $order_count,
													  "visitors" => 0
					);
				}

				// Build the XML for number of unique visitors
				$query = sprintf("select datestamp, numuniques from [|PREFIX|]unique_visitors where datestamp >= '%d' and datestamp <= '%d'", $from, $to);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$isc_date_stamp =  isc_gmmktime(0, 0, 0, isc_date("m", $row['datestamp']), isc_date("d", $row['datestamp']), isc_date("Y", $row['datestamp']));

					$visitor_rows[$isc_date_stamp] = $row['numuniques'];
				}

				for($i = 0; $i < count($visitor_dates); $i++) {
					$date_format = $visitor_dates[$i]['format'];
					$date_stamp = isc_gmmktime(0, 0, 0, isc_date("m", $visitor_dates[$i]['stamp']), isc_date("d", $visitor_dates[$i]['stamp']), isc_date("Y", $visitor_dates[$i]['stamp']));

					// Were there any visitors for this day?
					if(isset($visitor_rows[$date_stamp])) {
						$uniques = $visitor_rows[$date_stamp];
					}
					else {
						$uniques = 0;
					}

					$visitor_xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $date_format, $uniques);

					// Update the conversion array
					$conversions[$date_format]['visitors'] = $uniques;

					// Workout the conversion rate and add it to the XML
					if($conversions[$date_format]['visitors'] > 0) {
						$conversion_rate = number_format((($conversions[$date_format]['orders'] / $conversions[$date_format]['visitors'])*100), 2);
					}
					else {
						// Avoid a divide by zero error
						$conversion_rate = 0;
					}

					$conversion_xml .= sprintf("			<value xid=\"%s\">%.2f</value>\n", $date_format, $conversion_rate);
				}
			}
			else if($num_days > 60 && $num_days <= 182) {
				// Get orders and show them for each week
				$query = "
					SELECT orddate
					FROM [|PREFIX|]orders
					WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".$from."' AND orddate <= '".$to."'
					".$vendorSql."
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				// Split the orders based on the week they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$orders[isc_date($week_format, $row['orddate'])]++;
				}

				// We now have the orders in an array based on the date they were ordered,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($orders as $order_date=>$order_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $order_date, $order_count);
					$conversions[$order_date] = array("orders" => $order_count,
													  "visitors" => 0
					);
				}

				// Loop through each week and calculate the number of visitors during that week
				foreach($visitor_dates as $visit_week) {
					$week_starts =  mktime(0, 0, 0, isc_date("m", $visit_week['stamp']), isc_date("d", $visit_week['stamp']), isc_date("Y", $visit_week['stamp']));
					$week_ends = $week_starts + (7 * 24 * 60 * 60);

					$query = sprintf("select sum(numuniques) as total from [|PREFIX|]unique_visitors where datestamp >= '%d' and datestamp <= '%d'", $week_starts, $week_ends);
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
					$visitor_xml .= sprintf("			<value xid=\"%s\">%d</value>\n", isc_date($week_format, $week_starts), $row['total']);

					// Update the conversion array
					$conversions[isc_date($week_format, $week_starts)]['visitors'] = $row['total'];

					// Workout the conversion rate and add it to the XML
					if($conversions[isc_date($week_format, $week_starts)]['visitors'] > 0) {
						$conversion_rate = number_format((($conversions[isc_date($week_format, $week_starts)]['orders'] / $conversions[isc_date($week_format, $week_starts)]['visitors'])*100), 2);
					}
					else {
						// Avoid a divide by zero error
						$conversion_rate = 0;
					}

					$conversion_xml .= sprintf("			<value xid=\"%s\">%.2f</value>\n", isc_date($week_format, $week_starts), $conversion_rate);
				}
			}
			else if($num_days > 182 && $num_days <= 730) {
				// Get orders and show them for each month
				$query = "
					SELECT orddate
					FROM [|PREFIX|]orders
					WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".$from."' AND orddate <= '".$to."'
					".$vendorSql."
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the orders based on the week they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$orders[isc_date($month_format, $row['orddate'])]++;
				}

				// We now have the orders in an array based on the date they were ordered,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($orders as $order_date=>$order_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $order_date, $order_count);
					$conversions[$order_date] = array("orders" => $order_count,
													  "visitors" => 0
					);
				}

				// Loop through each month and calculate the number of visitors during that month
				foreach($visitor_dates as $visit_month) {
					$month_starts = $visit_month['stamp'];
					$month_ends = $month_starts + 2592000;
					$query = sprintf("select sum(numuniques) as total from [|PREFIX|]unique_visitors where datestamp >= '%d' and datestamp <= '%d'", $month_starts, $month_ends);
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
					$visitor_xml .= sprintf("			<value xid=\"%s\">%d</value>\n", isc_date($month_format, $month_starts), $row['total']);

					// Update the conversion array
					$conversions[isc_date($month_format, $month_starts)]['visitors'] = $row['total'];

					// Workout the conversion rate and add it to the XML
					if($conversions[isc_date($month_format, $month_starts)]['visitors'] > 0) {
						$conversion_rate = number_format((($conversions[isc_date($month_format, $month_starts)]['orders'] / $conversions[isc_date($month_format, $month_starts)]['visitors'])*100), 2);
					}
					else {
						// Avoid a divide by zero error
						$conversion_rate = 0;
					}

					$conversion_xml .= sprintf("			<value xid=\"%s\">%.2f</value>\n", isc_date($month_format, $month_starts), $conversion_rate);
				}
			}
			else if($num_days > 730) {
				// Get orders and show them for each month
				$query = "
					SELECT orddate
					FROM [|PREFIX|]orders
					WHERE ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND deleted = 0 AND orddate >= '".$from."' AND orddate <= '".$to."'
					".$vendorSql."
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the orders based on the week they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$orders[isc_date($year_format, $row['orddate'])]++;
				}

				// We now have the orders in an array based on the date they were ordered,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($orders as $order_date=>$order_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $order_date, $order_count);
					$conversions[$order_date] = array("orders" => $order_count,
													  "visitors" => 0
					);
				}

				// Loop through each year and calculate the number of visitors during that year
				foreach($visitor_dates as $visit_year) {
					$year_starts = $visit_year['stamp'];
					$year_ends = $year_starts + 31536000;
					$query = sprintf("select sum(numuniques) as total from [|PREFIX|]unique_visitors where datestamp >= '%d' and datestamp <= '%d'", $year_starts, $year_ends);
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
					$visitor_xml .= sprintf("			<value xid=\"%s\">%d</value>\n", isc_date($year_format, $year_starts), $row['total']);

					// Update the conversion array
					$conversions[isc_date($year_format, $year_starts)]['visitors'] = $row['total'];

					// Workout the conversion rate and add it to the XML
					if($conversions[isc_date($year_format, $year_starts)]['visitors'] > 0) {
						$conversion_rate = number_format((($conversions[isc_date($year_format, $year_starts)]['orders'] / $conversions[isc_date($year_format, $year_starts)]['visitors'])*100), 2);
					}
					else {
						// Avoid a divide by zero error
						$conversion_rate = 0;
					}

					$conversion_xml .= sprintf("			<value xid=\"%s\">%.2f</value>\n", isc_date($year_format, $year_starts), $conversion_rate);
				}
			}

			$xml .= "		</graph>\n";

			// Only show visitor data if we're reporting on 2 or more days
			if($num_days > 1) {
				$xml .= "		<graph gid=\"2\">\n";
				$xml .= $visitor_xml;
				$xml .= "		</graph>\n";
				$xml .= "		<graph gid=\"3\">\n";
				$xml .= $conversion_xml;
				$xml .= "		</graph>\n";
			}

			$xml .= "	</graphs>\n";
			$xml .= "</chart>";

			// Send the XML back to the browser
			echo $xml;
		}
	}

	/**
	*	Return a basic pie chart containing details for the top 20 customers
	*/
	public function GetOverviewStatsTop20CustomersData()
	{

		if(isset($_GET['from']) && is_numeric($_GET['from']) && isset($_GET['to']) && is_numeric($_GET['to'])) {

			$from = (int)$_GET['from'];
			$to = (int)$_GET['to'];

			$output = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
			$output .= "<pie>\n";
			$query = "
				SELECT
					COUNT(orderid) AS numorders,
					SUM(total_inc_tax) AS total,
					IF(ISNULL(customerid), CONCAT(ordbillfirstname, ' ',  ordbilllastname), CONCAT(custconfirstname, ' ', custconlastname)) AS name
				FROM
					[|PREFIX|]orders o
					LEFT JOIN [|PREFIX|]customers ON ordcustid = customerid
				WHERE
					ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
					o.deleted = 0 AND
					orddate >= '" . $from . "' AND
					orddate <= '" . $to . "'
				GROUP BY
					ordbillemail
				ORDER BY
					numorders DESC
				LIMIT
					20
			";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$output .= sprintf("\t<slice title=\"%s\" pull_out=\"false\">%s</slice>\n", isc_html_escape(isc_convert_charset(GetConfig('CharacterSet'), 'UTF-8', $row['name'])), number_format($row['total'], GetConfig('DecimalPlaces'), GetConfig('DecimalToken'), ""));
				}
			}

			$output .= "</pie>";
			echo $output;
		}
	}

	public function GetOverviewStatsTop20ProductsData()
	{

		if(isset($_GET['from']) && is_numeric($_GET['from']) && isset($_GET['to']) && is_numeric($_GET['to'])) {

			$from = (int)$_GET['from'];
			$to = (int)$_GET['to'];

			// Only fetch products this user can actually see
			$vendorRestriction = '';
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				$vendorRestriction = " AND prodvendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
			}

			$output = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
			$output .= "<pie>\n";
			$query = "
				SELECT
					ordprodname,
					SUM(ordprodqty) AS numsold
				FROM
					[|PREFIX|]order_products
					INNER JOIN [|PREFIX|]orders o ON (orderorderid=orderid)
					LEFT JOIN [|PREFIX|]products ON (ordprodid=productid)
				WHERE
					ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
					o.deleted = 0 AND
					ordprodtype != 'giftcertificate' AND
					ordprodid != 0 AND
					orddate >= '".$from."' AND
					orddate <= '".$to."'
					" . $vendorRestriction . "
				GROUP BY
					ordprodid
				ORDER BY
					numsold DESC
			";
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 20);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$output .= sprintf("\t<slice title=\"%s\" pull_out=\"false\">%s</slice>\n", isc_html_escape(isc_convert_charset(GetConfig('CharacterSet'), 'UTF-8', $row['ordprodname'])), (int) $row['numsold']);
				}
			}

			$output .= "</pie>";
			echo $output;
		}
	}

	/**
	*	Get a list of the top 20 products and return them as <tr> tags
	*/
	public function _Top20ProductsGrid($FromStamp, $ToStamp, &$NumResults)
	{
		// Only fetch products this user can actually see
		$vendorRestriction = '';
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
			$vendorRestriction = " AND prodvendorid='" . $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() . "'";
		}

		$output = "";
		$query = "
			SELECT
				ordprodname,
				SUM(ordprodqty) AS numsold,
				SUM(op.total_inc_tax) as total,
				productid
			FROM
				[|PREFIX|]order_products op
				INNER JOIN [|PREFIX|]orders o ON orderorderid = orderid
				LEFT JOIN [|PREFIX|]products ON ordprodid = productid
			WHERE
				ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
				o.deleted = 0 AND
				ordprodtype != 'giftcertificate' AND
				ordprodid != 0 AND
				orddate >= '".$FromStamp."' AND
				orddate <= '".$ToStamp."'
				" . $vendorRestriction . "
			GROUP BY
				ordprodid
			ORDER BY
				numsold DESC
		";

		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 20);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$output = sprintf("
			<table width=\"100%%\" border=0 cellspacing=1 cellpadding=5 class=\"text\">
				<tr class=\"Heading3\">
					<td nowrap align=\"left\">
						%s
					</td>
					<td nowrap align=\"left\">
						%s
					</td>
					<td nowrap align=\"left\">
						%s
					</td>
				</tr>
		", GetLang('StatsProductName'), GetLang('StatsNumSold'), GetLang('StatsRevenue'));

		$NumResults = $GLOBALS['ISC_CLASS_DB']->CountResult($result);

		if($NumResults > 0) {

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$prodlink = $row['ordprodname'];
				if (!is_null($row['productid'])) {
					$prodlink = "<a href='" . ProdLink($row['ordprodname']) . "' target='_blank'>" . isc_html_escape($row['ordprodname']) . "</a>";
				}

				$output .= sprintf("
					<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
						<td nowrap height=\"22\">
							%s
						</td>
						<td nowrap>
							%s
						</td>
						<td nowrap>
							%s
						</td>
					</tr>
				", $prodlink, (int) $row['numsold'], FormatPrice($row['total']));
			}
		}
		else {
				$output .= sprintf("
					<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
						<td colspan=\"5\" nowrap height=\"22\">
							<em>%s</em>
						</td>
					</tr>
				", GetLang('StatsNoTopProducts'));
		}

		$output .= "</table>";
		return $output;
	}

	/**
	*	Generate the code to display a Google map containing the location of the store's
	*	top 100 customers during the selected date range.
	*/
	public function OverviewOrderLocationChart()
	{

		if(isset($_GET['from']) && isset($_GET['to'])) {

			$from = (int)$_GET['from'];
			$to = (int)$_GET['to'];

			// Workout the top 100 customers for the selected date period
			$address_list = "";
			$query = "
				SELECT
					custconcompany,
					custconfirstname,
					custconlastname,
					custconemail,
					custconphone,
					ordbillstreet1,
					ordbillstreet2,
					ordbillsuburb,
					ordbillstate,
					ordbillzip,
					ordbillcountry,
					customerid,
					CONCAT(ordbillstreet1, ' ', ordbillstreet2, ' ', ordbillsuburb, ' ', ordbillstate, ' ', ordbillzip, ' ', ordbillcountry) AS custaddress
				FROM
					[|PREFIX|]orders o
					INNER JOIN [|PREFIX|]customers ON ordcustid = customerid
				WHERE
					ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
					o.deleted = 0 AND
					orddate >= '" . $from . "' AND
					orddate <= '" . $to . "'
				GROUP BY
					custaddress
			";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$num_orders = $GLOBALS['ISC_CLASS_DB']->CountResult($result);

			if($num_orders > 0) {
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$address_list .= "var customerDetails = {
						name: '".addslashes($row['custconfirstname'])." ".addslashes($row['custconlastname'])."',
						id: '".(int) $row['customerid']."',
						phone: '".addslashes($row['custconphone'])."',
						address1: '".addslashes($row['ordbillstreet1'])."',
						address2: '".addslashes($row['ordbillstreet2'])."',
						suburb: '".addslashes($row['ordbillsuburb'])."',
						state: '".addslashes($row['ordbillstate'])."',
						country: '".addslashes($row['ordbillcountry'])."',
						zip: '".addslashes($row['ordbillzip'])."'
					};\r\n";
					$address_list .= "showAddress(customerDetails);";
				}

				$mapScript = "http://maps.google.com/maps?file=api&amp;v=2&amp;key=" . GetConfig('GoogleMapsAPIKey');
				?>
					<html>
						<head>
							<style>
								* { font-family:Arial; font-size:11px; }
								body { margin:0px; }
							</style>
							<link rel="stylesheet" type="text/css" href="styles/thickbox.css" />
							<script src="<?php echo $mapScript; ?>"></script>
							<script src="../javascript/jquery.js"></script>
							<script src="../javascript/thickbox.js"></script>

							<script type="text/javascript">

								var map = null;
								var geocoder = null;

								function gmap_initialize() {
									if(GBrowserIsCompatible()) {
										map = new GMap2(document.getElementById("map_canvas"));
										map.addControl(new GLargeMapControl());
										map.addControl(new GMapTypeControl());
										map.setCenter(new GLatLng(37.4419, -122.1419), 2);
										geocoder = new GClientGeocoder();
									}
								}
								function showAddress(info) {
									if(geocoder) {
										// Build the address to show
										var address = info.address1+" "+info.address2+" "+info.suburb+" "+info.state+" "+info.zip+" "+info.country;
										address = address.replace(/N\/A/i, '');
										address = address.replace(/C\/O/i, '');
										geocoder.getLatLng(
											address,
											function(point) {
												if(!point) {
													// If the whole address was not found, strip out the street etc
													var address = info.suburb+" "+info.state+" "+info.zip+" "+info.country;
													address = address.replace('/N\/A/i', '');
													address = address.replace('/C\/O/i', '');
													geocoder.getLatLng(
														address,
														function(point) {
															if(point) {
																DrawOverlay(info, point);
															}
														}
													);
													//alert(address + " not found");
												}
												else {
													DrawOverlay(info, point);
												}
											}
										);
									}
								}

								function DrawOverlay(info, point) {
									var infoWindow = "<div style='font-weight: bold; font-size: 11px;'>"+info.name+" (<a target='_parent' href='index.php?ToDo=viewCustomers&amp;searchQuery="+info.id+"' style='color:blue'><?php echo GetLang('ViewOrderHistory'); ?></a>)</div><br />"+info.suburb+", "+info.state+"<br />"+info.country+" "+info.zip;
									var marker = new GMarker(point);
									map.addOverlay(marker);
									GEvent.addListener(marker, "click", function() {
										marker.openInfoWindowHtml(infoWindow);
									});
								}

								function gDo() {
									window.setTimeout("gBuild();", 1000);
								}

								function gBuild() {
									gmap_initialize();
									<?php echo $address_list; ?>
								}

							</script>
						</head>
						<body onload="gDo()">
							<div id="map_canvas" style="width: 99%; height: 99%; border:solid 1px #CCC"></div>
						</body>
					</html>
				<?php
			} else {
				// show an alert if no result found
				?>
				<script type="text/javascript">
					alert('<?php GetLang('StatsNoOrdersForDate'); ?>');
				</script>
				<?php
			}
		}
	}
}
