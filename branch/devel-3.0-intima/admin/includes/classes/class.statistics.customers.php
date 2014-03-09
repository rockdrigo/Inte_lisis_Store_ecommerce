<?php
class ISC_ADMIN_STATISTICS_CUSTOMERS extends ISC_ADMIN_STATISTICS
{
	/**
	*	Show customer statistics
	*/
	public function CustomerStats()
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

		$GLOBALS['FromStamp'] = $from_stamp;
		$GLOBALS['ToStamp'] = $to_stamp;

		// Orders by revenue should workout the min and max revenues (like shop by price) and show a range of average order revenues on a pie chart

		$this->template->display('stats.customers.tpl');
	}

	/**
	*	Load up the customers and organize them based on the date when they registered.
	*	The "from" and "to" timestamps are passed in to determine between which dates
	*	we will retrieve the customers.
	*/
	public function CustomerStatsByDateData()
	{

		if(isset($_GET['from']) && is_numeric($_GET['from']) && isset($_GET['to']) && is_numeric($_GET['to'])) {

			$customers = array();
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
				// Get customers and show them for each hour
				$num_val = $num_days;

				if($num_val == 0) {
					$num_val = 1;
				}

				for($i = 0; $i < $num_val*25; $i++) {
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", isc_date($day_format, $from + (3600 * $i)), isc_date($day_format, $from + (3600 * $i)));
					$customers[isc_date($day_format, $from + (3600 * $i))] = 0;
				}
			}
			else if($num_days > 1 && $num_days <= 60) {
				// Get customers and show them for each day
				for($i = 0; $i < $num_days; $i++) {
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i)), isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i)));
					$customers[isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i))] = 0;
					$visitor_dates[] = array("format" => isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i)),
											 "stamp" => $from + (86400 * $i)
					);

					// Track the conversion rate
					$conversions[isc_date(GetConfig('DisplayDateFormat'), $from + (86400 * $i))] = 0;
				}
			}
			else if($num_days > 60 && $num_days <= 182) {
				// Get customers and show them for each week
				$num_weeks = ceil($num_days / 7);

				for($i = 0; $i < $num_weeks+1; $i++) {
					$extended_stamp = sprintf("%s -\n %s", isc_date(GetConfig('DisplayDateFormat'), $from + (604800 * $i)), isc_date(GetConfig('DisplayDateFormat'), $from + (604800 * $i + (86400*7))));
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", isc_date($week_format, $from + (604800 * $i)), $extended_stamp);
					$customers[isc_date($week_format, $from + (604800 * $i))] = 0;
					$visitor_dates[] = array("format" => isc_date(GetConfig('DisplayDateFormat'), $from + (604800 * $i)),
											 "stamp" => $from + (604800 * $i)
					);

					// Track the conversion rate
					$conversions[isc_date(GetConfig('DisplayDateFormat'), $from + (604800 * $i))] = 0;
				}
			}
			else if($num_days > 182 && $num_days <= 730) {
				// Get customers and show them for each month
				$num_months = ceil($num_days / 31)+1;

				$from_month = isc_date("m", $from);
				$from_year = isc_date("Y", $from);

				for($i = 0; $i < $num_months+1; $i++) {
					// Workout the timestamp for the first day of the month
					$first_day_stamp = isc_mktime(0, 0, 0, $from_month+$i, 1, $from_year);
					$output_format = isc_date($month_format, $first_day_stamp);
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", $output_format, $output_format);
					$customers[$output_format] = 0;
					$visitor_dates[] = array("format" => $output_format,
											 "stamp" => $first_day_stamp
					);

					// Track the conversion rate
					$conversions[$output_format] = 0;
				}
			}
			else if($num_days > 730) {
				// Get customers and show them for each year
				$num_years = ceil($num_days / 365)+1;
				$from_year = isc_date("Y", $from);

				for($i = 0; $i < $num_years+1; $i++) {
					// Workout the timestamp for the first day of the year
					$first_day_stamp = isc_mktime(0, 0, 0, 1, 1, $from_year+$i);
					$output_format = isc_date($year_format, $first_day_stamp);
					$xml .= sprintf("		<value xid=\"%s\">%s</value>\n", $output_format, $output_format);
					$customers[isc_date($year_format, $from + (31536000 * $i))] = 0;
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

			// Start the graph that shows number of customers

			if($num_days <= 1) {
				// Get customers and show them for each hour
				$query = sprintf("select custdatejoined from [|PREFIX|]customers where custdatejoined >= '%s' and custdatejoined <= '%s'", $from, $to);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the customers based on the day they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					if(isset($customers[isc_date($day_format, $row['custdatejoined'])])) {
						$customers[isc_date($day_format, $row['custdatejoined'])]++;
					}
				}

				// We now have the customers in an array based on the date they joined,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($customers as $join_date=>$join_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $join_date, $join_count);
				}
			}
			else if($num_days > 1 && $num_days <= 60) {
				// Get customers and show them for each day
				$query = sprintf("select custdatejoined from [|PREFIX|]customers where custdatejoined >= '%s' and custdatejoined <= '%s'", $from, $to);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the customers based on the day they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					@$customers[isc_date(GetConfig('DisplayDateFormat'), $row['custdatejoined'])]++;
				}

				// We now have the customers in an array based on the date they joined,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($customers as $join_date=>$join_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $join_date, $join_count);
					$conversions[$join_date] = array("customers" => $join_count,
													  "visitors" => 0
					);
				}

				// Build the XML for number of unique visitors
				$query = sprintf("select datestamp, numuniques from [|PREFIX|]unique_visitors where datestamp >= '%d' and datestamp <= '%d'", $from, $to);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$visitor_rows[$row['datestamp']] = $row['numuniques'];
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
						$conversion_rate = number_format((($conversions[$date_format]['customers'] / $conversions[$date_format]['visitors'])*100), 2);
					}
					else {
						// Avoid a divide by zero error
						$conversion_rate = 0;
					}

					$conversion_xml .= sprintf("			<value xid=\"%s\">%.2f</value>\n", $date_format, $conversion_rate);
				}
			}
			else if($num_days > 60 && $num_days <= 182) {
				// Get customers and show them for each week
				$query = sprintf("select custdatejoined from [|PREFIX|]customers where custdatejoined >= '%s' and custdatejoined <= '%s'", $from, $to);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the customers based on the week they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$customers[isc_date($week_format, $row['custdatejoined'])]++;
				}

				// We now have the customers in an array based on the date t,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($customers as $join_date=>$join_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $join_date, $join_count);
					$conversions[$join_date] = array("customers" => $join_count,
													  "visitors" => 0
					);
				}

				// Loop through each week and calculate the number of visitors during that week
				foreach($visitor_dates as $visit_week) {
					$week_starts = $visit_week['stamp'];
					$week_ends = $week_starts + (3600*7);
					$query = sprintf("select sum(numuniques) as total from [|PREFIX|]unique_visitors where datestamp >= '%d' and datestamp <= '%d'", $week_starts, $week_ends);
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
					$visitor_xml .= sprintf("			<value xid=\"%s\">%d</value>\n", isc_date($week_format, $week_starts), $row['total']);

					// Update the conversion array
					$conversions[isc_date($week_format, $week_starts)]['visitors'] = $row['total'];

					// Workout the conversion rate and add it to the XML
					if($conversions[isc_date($week_format, $week_starts)]['visitors'] > 0) {
						$conversion_rate = number_format((($conversions[isc_date($week_format, $week_starts)]['customers'] / $conversions[isc_date($week_format, $week_starts)]['visitors'])*100), 2);
					}
					else {
						// Avoid a divide by zero error
						$conversion_rate = 0;
					}

					$conversion_xml .= sprintf("			<value xid=\"%s\">%.2f</value>\n", isc_date($week_format, $week_starts), $conversion_rate);
				}
			}
			else if($num_days > 182 && $num_days <= 730) {
				// Get customers and show them for each month
				$query = sprintf("select custdatejoined from [|PREFIX|]customers where custdatejoined >= '%s' and custdatejoined<= '%s'", $from, $to);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the customers based on the week they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$customers[isc_date($month_format, $row['custdatejoined'])]++;
				}

				// We now have the customers in an array based on the date they joined,
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($customers as $join_date=>$join_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $join_date, $join_count);
					$conversions[$join_date] = array("customers" => $join_count,
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
						$conversion_rate = number_format((($conversions[isc_date($month_format, $month_starts)]['customers'] / $conversions[isc_date($month_format, $month_starts)]['visitors'])*100), 2);
					}
					else {
						// Avoid a divide by zero error
						$conversion_rate = 0;
					}

					$conversion_xml .= sprintf("			<value xid=\"%s\">%.2f</value>\n", isc_date($month_format, $month_starts), $conversion_rate);
				}
			}
			else if($num_days > 730) {
				// Get customers and show them for each month
				$query = sprintf("select custdatejoined from [|PREFIX|]customers where custdatejoined >= '%s' and custdatejoined <= '%s'", $from, $to);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				// Split the customers based on the week they came in
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$customers[isc_date($year_format, $row['custdatejoined'])]++;
				}

				// We now have the customers in an array based on the date they registered
				// so we can loop through them to create the first graph on the chart

				$x_counter = 0;

				foreach($customers as $join_date=>$join_count) {
					$xml .= sprintf("			<value xid=\"%s\">%d</value>\n", $join_date, $join_count);
					$conversions[$join_date] = array("customers" => $join_count,
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
						$conversion_rate = number_format((($conversions[isc_date($year_format, $year_starts)]['customers'] / $conversions[isc_date($year_format, $year_starts)]['visitors'])*100), 2);
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
	*	Build the grid that will be shown on the "Customers by Date" tab
	**/
	public function CustomerStatsByDateGrid()
	{
		$GLOBALS['CustomerGrid'] = "";

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

			$GLOBALS['CustomersPerPage'] = $per_page;
			$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';

			// Should we limit the records returned?
			if(isset($_GET['Page'])) {
				$page = (int)$_GET['Page'];
			}
			else {
				$page = 1;
			}

			$GLOBALS['CustomersByDateCurrentPage'] = $page;

			// Workout the start and end records
			$start = ($per_page * $page) - $per_page;
			$end = $start + ($per_page - 1);

			// How many customers are there in total?
			$query = "
				SELECT
					COUNT(*) AS num
				FROM
					[|PREFIX|]customers
				WHERE
					custdatejoined >= '" . $from_stamp . "' AND
					custdatejoined <= '" . $to_stamp . "'
				";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$total_customers = $row['num'];

			// Are there any customers?
			if($total_customers > 0) {

				// Workout the paging
				$num_pages = ceil($total_customers / $per_page);
				$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
				$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

				// Is there more than one page? If so show the &laquo; to jump back to page 1
				if($num_pages > 1) {
					$paging .= "<a href='javascript:void(0)' onclick='ChangeCustomersByDatePage(1)'>&laquo;</a> | ";
				}
				else {
					$paging .= "&laquo; | ";
				}

				// Are we on page 2 or above?
				if($page > 1) {
					$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeCustomersByDatePage(%d)'>%s</a> | ", $page-1, GetLang('Prev'));
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
							$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeCustomersByDatePage(%d)'>%d</a> | ", $i, $i);
						}
					}
				}

				// Are we on page 2 or above?
				if($page < $num_pages) {
					$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeCustomersByDatePage(%d)'>%s</a> | ", $page+1, GetLang('Next'));
				}
				else {
					$paging .= sprintf("%s | ", GetLang('Next'));
				}

				// Is there more than one page? If so show the &raquo; to go to the last page
				if($num_pages > 1) {
					$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeCustomersByDatePage(%d)'>&raquo;</a> | ", $num_pages);
				}
				else {
					$paging .= "&raquo; | ";
				}

				$paging = rtrim($paging, ' |');
				$GLOBALS['Paging'] = $paging;

				// Should we set focus to the grid?
				if(isset($_GET['FromLink']) && $_GET['FromLink'] == "true") {
					$GLOBALS['JumpToCustomersByDateGrid'] = "<script type=\"text/javascript\">document.location.href='#customersByDateAnchor';</script>";
				}

				if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "desc") {
					$sortOrder = 'desc';
				}
				else {
					$sortOrder = 'asc';
				}

				$sortFields = array('customerid','name','custconemail','custdatejoined','numorders','orderamount');
				if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
					$sortField = $_GET['SortBy'];
					SaveDefaultSortField("CustomersStatsByDate", $_REQUEST['SortBy'], $sortOrder);
				}
				else {
					list($sortField, $sortOrder) = GetDefaultSortField("CustomersStatsByDate", "custdatejoined", $sortOrder);
				}

				$sortLinks = array(
					"CustId" => "customerid",
					"Cust" => "name",
					"Email" => "custconemail",
					"Date" => "custdatejoined",
					"NumOrders" => "numorders",
					"AmountSpent" => "orderamount"
				);
				BuildAdminSortingLinks($sortLinks, "javascript:SortCustomersByDate('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);

				// Fetch the customers for this page
				$query = sprintf("
					SELECT
						customerid,
						CONCAT(custconfirstname, ' ', custconlastname) AS name,
						custconemail,
						custdatejoined,
						COUNT(orderid) AS numorders,
						SUM(total_inc_tax) AS orderamount
					FROM
						[|PREFIX|]customers
						LEFT JOIN [|PREFIX|]orders ON (ordcustid = customerid AND deleted = 0 AND ordstatus IN (".implode(',', GetPaidOrderStatusArray())."))
					WHERE
						custdatejoined >= '%d' AND custdatejoined <= '%d'
					GROUP BY
						customerid
					ORDER BY
						%s %s",
					$from_stamp,
					$to_stamp,
					$sortField,
					$sortOrder
				);

				// Add the limit
				$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, $per_page);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$viewOrders = '';

					if($row['numorders'] > 0) {
						$viewOrders = sprintf("<a href=\"index.php?ToDo=viewOrders&searchId=0&searchQuery=%s\" target=\"_blank\">%s</a>", urlencode($row['custconemail']), GetLang('StatsViewOrders'));
					} else {
						$viewOrders = sprintf("<span class=\"Disabled\">%s</span>", GetLang('StatsViewOrders'));
					}

					$GLOBALS['CustomerGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldCustIdClass']."\">
								<a href='index.php?ToDo=viewCustomers&amp;searchQuery=%d' target='_blank'>%d</a>
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldCustClass']."\">
								<a href='index.php?ToDo=viewCustomers&amp;searchQuery=%d' target='_blank'>%s</a>
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldEmailClass']."\">
								<a href=\"mailto:%s\">%s</a>
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldDateClass']."\">
								%s
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldNumOrdersClass']."\">
								%s
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldAmountSpentClass']."\">
								%s
							</td>
							<td nowrap>
								%s
							</td>
						</tr>

					", (int) $row['customerid'],
					   (int) $row['customerid'],
					   (int) $row['customerid'],
					   isc_html_escape($row['name']),
					   isc_html_escape($row['custconemail']),
					   isc_html_escape($row['custconemail']),
					   isc_date(GetConfig('DisplayDateFormat'), $row['custdatejoined']),
					   number_format($row['numorders']),
					   FormatPrice($row['orderamount']),
					   $viewOrders
					);
				}

				$this->template->display('stats.customers.bydategrid.tpl');
			}
		}
	}

	public function CustomerStatsByRevenueGrid()
	{
		$GLOBALS['CustomerGrid'] = "";

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

			$GLOBALS['CustomersPerPage'] = $per_page;
			$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';

			// Should we limit the records returned?
			if(isset($_GET['Page'])) {
				$page = (int)$_GET['Page'];
			}
			else {
				$page = 1;
			}

			$GLOBALS['RevenueByCustomersCurrentPage'] = $page;

			// Workout the start and end records
			$start = ($per_page * $page) - $per_page;
			$end = $start + ($per_page - 1);


			// How many customers with orders between this period are there in total?
			$query = "
				SELECT
					COUNT(*) AS num
				FROM
					[|PREFIX|]orders o
					LEFT JOIN [|PREFIX|]customers ON ordcustid = customerid
				WHERE
					ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
					o.deleted = 0 AND
					orddate >= '" . $from_stamp . "' AND
					orddate <= '" . $to_stamp . "'
				GROUP BY
					ordcustid
			";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$total_customers = $row['num'];

			// Workout the paging
			$num_pages = ceil($total_customers / $per_page);
			$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
			$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

			// Is there more than one page? If so show the &laquo; to jump back to page 1
			if($num_pages > 1) {
				$paging .= "<a href='javascript:void(0)' onclick='ChangeRevenuePerCustomerPage(1)'>&laquo;</a> | ";
			}
			else {
				$paging .= "&laquo; | ";
			}

			// Are we on page 2 or above?
			if($page > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeRevenuePerCustomerPage(%d)'>%s</a> | ", $page-1, GetLang('Prev'));
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
						$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeRevenuePerCustomerPage(%d)'>%d</a> | ", $i, $i);
					}
				}
			}

			// Are we on page 2 or above?
			if($page < $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeRevenuePerCustomerPage(%d)'>%s</a> | ", $page+1, GetLang('Next'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Next'));
			}

			// Is there more than one page? If so show the &raquo; to go to the last page
			if($num_pages > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeRevenuePerCustomerPage(%d)'>&raquo;</a> | ", $num_pages);
			}
			else {
				$paging .= "&raquo; | ";
			}

			$paging = rtrim($paging, ' |');
			$GLOBALS['Paging'] = $paging;

			// Should we set focus to the grid?
			if(isset($_GET['FromLink']) && $_GET['FromLink'] == "true") {
				$GLOBALS['JumpToOrdersByItemsSoldGrid'] = "<script type=\"text/javascript\">document.location.href='#revenuePerCustomerAnchor';</script>";
			}

			if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "asc") {
				$sortOrder = 'asc';
			}
			else {
				$sortOrder = 'desc';
			}

			$sortFields = array('customerid','name','custconemail','custdatejoined','numorders','revenue');
			if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
				$sortField = $_GET['SortBy'];
				SaveDefaultSortField("CustomerStatsByRevenue", $_REQUEST['SortBy'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("CustomerStatsByRevenue", "revenue", $sortOrder);
			}

			$sortLinks = array(
				"Cust" => "name",
				"Email" => "custconemail",
				"Date" => "custdatejoined",
				"NumOrders" => "numorders",
				"AmountSpent" => "revenue"
			);
			BuildAdminSortingLinks($sortLinks, "javascript:SortRevenuePerCustomer('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);

			// Fetch the actual results for this page
			$query = sprintf("
				SELECT
						customerid,
						IF (SUM(ordcustid) = 0 AND ordbillemail = '', '".$GLOBALS['ISC_CLASS_DB']->quote(GetLang('Guests'))."',
							IF (SUM(ordcustid) = 0 AND ordbillemail != '', CONCAT(custconfirstname, ' ',  custconlastname, ' ', '".$GLOBALS['ISC_CLASS_DB']->quote(GetLang('Guest'))."'), CONCAT(custconfirstname, ' ',  custconlastname))) AS name,
						IF (SUM(ordcustid) = 0 AND ordbillemail = '', '".$GLOBALS['ISC_CLASS_DB']->quote(GetLang('Guests'))."',
							IF (SUM(ordcustid) = 0 AND ordbillemail != '', CONCAT(ordbillfirstname, ' ',  ordbilllastname, ' ', '".$GLOBALS['ISC_CLASS_DB']->quote(GetLang('Guest'))."'), CONCAT(ordbillfirstname, ' ',  ordbilllastname))) AS billname,
						ordbillemail,
						custconemail,
						IF (SUM(ordcustid) = 0, '', custdatejoined) AS custdatejoined,
						COUNT(orderid) AS numorders,
						SUM(total_inc_tax) AS revenue
				FROM
					[|PREFIX|]orders o
					LEFT JOIN [|PREFIX|]customers ON ordcustid = customerid
				WHERE
					ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
					o.deleted = 0 AND
					orddate >= '%d' AND
					orddate <= '%d'
				GROUP BY
					ordbillemail
				ORDER BY
					%s %s",
				$from_stamp,
				$to_stamp,
				$sortField,
				$sortOrder
			);

			// Add the Limit
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, $per_page);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					if(!is_null($row['customerid'])) {
						$customerLink = "<a href=\"index.php?ToDo=viewCustomers&searchQuery=".(int) $row['customerid']."\">".isc_html_escape($row['name'])."</a>";
						$email = $row['custconemail'];
					}
					else {
						$customerLink = isc_html_escape($row['billname']);
						$email = $row['ordbillemail'];
					}
					$orderEmail = GetLang('NA');
					$joinedDate = GetLang('NA');
					if (!empty ($email)) {
						$orderEmail = "<a href=\"mailto:$email\">$email</a>";
					}
					if (!empty ($row['custdatejoined'])) {
						$joinedDate = isc_date(GetConfig('DisplayDateFormat'), $row['custdatejoined']);
					}
					$GLOBALS['CustomerGrid'] .= "
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldCustClass']."\">
								$customerLink
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldEmailClass']."\">
								".$orderEmail."
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldDateClass']."\">
								$joinedDate
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldNumOrdersClass']."\">
								{$row['numorders']}
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldAmountSpentClass']."\">
								".FormatPrice($row['revenue'])."
							</td>
						</tr>
					";
				}
			}
			else {
					$GLOBALS['HideStatsRows'] = "none";
					$GLOBALS['CustomerGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" colspan=\"6\">
								<em>%s</em>
							</td>
						</tr>
					", GetLang('StatsNoCustomersForDate')
					);
			}
			$this->template->display('stats.customers.byrevenue.tpl');
		}
	}
}
