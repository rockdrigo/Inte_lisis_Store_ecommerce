<?php
class ISC_ADMIN_STATISTICS_SEARCH extends ISC_ADMIN_STATISTICS
{
	/**
	 * Show the search statistics tab.
	 */
	public function SearchStats($MsgDesc = "", $MsgStatus = "")
	{
		if(isset($_POST['Calendar'])) {
			$cal = $this->CalculateCalendarRestrictions($_POST['Calendar']);
			$GLOBALS['CurrentDate'] = $_POST['Calendar']['DateType'];
		}
		else {
			$cal = $this->CalculateCalendarRestrictions();
			$GLOBALS['CurrentDate'] = "Last30Days";
		}

		if ($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		$GLOBALS['CalendarDateTypeOptions'] = $this->_GetCalendarDateTypesAsOptions($GLOBALS['CurrentDate']);

		// Set the global variables for the select boxes
		$from_stamp = $cal['start'];
		$to_stamp = $cal['end'];

		$from_days = $from_stamp / 86400;
		$to_days = $to_stamp / 86400;
		$num_days = floor($to_days - $from_days)+1;

		$from_day = isc_date("d", $from_stamp);
		$from_month = isc_date("m", $from_stamp);
		$from_year = isc_date("Y", $from_stamp);

		$to_day = isc_date("d", $to_stamp);
		$to_month = isc_date("m", $to_stamp);
		$to_year = isc_date("Y", $to_stamp);

		// Get the total cost and number of orders for the period
		$search_details = $this->_GetSearchStatsForPeriod($from_stamp, $to_stamp);

		if(is_array($search_details)) {
			$GLOBALS['OverviewNumSearches'] = $search_details['total'];
			$GLOBALS['OverviewMostSearchesDay'] = $search_details['max_searches_day'];
			$GLOBALS['OverviewAverageSearchesDay'] = $search_details['avg_searches_day'];
			$GLOBALS['OverviewMostPopularTerms'] = $search_details['most_popular'];
			$GLOBALS['OverviewMostPopularTermsNoResults'] = $search_details['most_popular_no_results'];
		}
		else {
			$GLOBALS['OverviewNumSearches'] = 0;
			$GLOBALS['OverviewMostSearchesDay'] = 0;
			$GLOBALS['OverviewAverageSearchesDay'] = 0;
			$GLOBALS['OverviewMostPopularTerms'] = 'N/A';
			$GLOBALS['OverviewMostPopularTermsNoResults'] = 'N/A';
		}

		if($GLOBALS['CurrentDate'] == "Today") {
			$GLOBALS['ChartTitle'] = GetLang('Top10SearchKeywordsToday');
		}
		else if($GLOBALS['CurrentDate'] == "Yesterday") {
			$GLOBALS['ChartTitle'] = GetLang('Top10SearchKeywordsYesterday');
		}
		else if($num_days == 1) {
			$GLOBALS['ChartTitle'] =  sprintf(GetLang('Top10SearchKeywordsDay'), isc_date(GetConfig('DisplayDateFormat'), $to_stamp));
		}
		else if($from_stamp > 0) {
			$GLOBALS['ChartTitle'] = sprintf(GetLang('Top10SearchKeywordsFromTo'), isc_date(GetConfig('DisplayDateFormat'), $from_stamp), isc_date(GetConfig('DisplayDateFormat'), $to_stamp));
		}
		else {
			$GLOBALS['ChartTitle'] = sprintf(GetLang('Top10SearchKeywordsTo'), isc_date(GetConfig('DisplayDateFormat'), $to_stamp));
		}

		$GLOBALS['HideChart'] = 0;
		if($search_details['total'] == 0) {
			$GLOBALS['HideChart'] = "none";
		}


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
		$this->template->display('stats.search.tpl');
	}

	/**
	 * Clear the search statistics.
	 */
	public function ClearSearchStats()
	{

		// Build a list of queries to execute
		$queries = array();

		$queries[] = "truncate [|PREFIX|]searches_extended";
		$queries[] = "truncate [|PREFIX|]search_corrections";

		foreach ($queries as $query) {
			$GLOBALS['ISC_CLASS_DB']->Query($query);
		}

		// Log this action
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();


		$this->SearchStats(GetLang('SearchStatsDeletedSuccessfully'), MSG_SUCCESS);
	}



	/**
	*	Get the overview of search statistics between two periods
	*/
	public function _GetSearchStatsForPeriod($FromStamp, $ToStamp)
	{
		$vals = array();

		// Fetch total number of searches
		$query = sprintf("select count(searchid) as num from [|PREFIX|]searches_extended where searchdate >= '%s' and searchdate <= '%s'", $FromStamp, $ToStamp);
		$result= $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$vals['total'] = $row['num'];

		// No searches performed within this period, no point in continuing
		if($row['num'] == 0) {
			return;
		}

		// Fetch the most popular search term
		$query = sprintf("select searchtext, count(searchid) as num from [|PREFIX|]searches_extended where numresults > 0 and searchdate >= '%s' and searchdate <= '%s' group by searchid order by num desc limit 1", $FromStamp, $ToStamp);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$vals['most_popular'] = $row['searchtext'];
		$vals['most_popular_count'] = $row['num'];

		// Fetch the most popular search term with no results
		$query = sprintf("select searchtext, count(searchid) as num from [|PREFIX|]searches_extended where numresults = 0 and searchdate >= '%s' and searchdate <= '%s' group by searchid order by num asc limit 1", $FromStamp, $ToStamp);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$vals['most_popular_no_results'] = $row['searchtext'];
		$vals['most_popular_no_results_count'] = $row['num'];


		// Fetch the most searches performed within a day
		$query = sprintf("select count(searchid) as numsearches from [|PREFIX|]searches_extended where searchdate >= '%s' and searchdate <= '%s' group by date(from_unixtime(searchdate)) order by numsearches desc limit 1", $FromStamp, $ToStamp);
		$vals['max_searches_day'] = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);

		// Average number of searches a day
		$query = sprintf("select count(searchid) / count(DISTINCT(date(from_unixtime(searchdate)))) from [|PREFIX|]searches_extended where searchdate >= '%s' and searchdate <= '%s'", $FromStamp, $ToStamp);
		$result= $GLOBALS['ISC_CLASS_DB']->Query($query);
		$vals['avg_searches_day'] = ceil($GLOBALS['ISC_CLASS_DB']->FetchOne($result));
		return $vals;
	}

	/**
	 * Build the top 10 search keywords pie graph.
	 */
	public function GetSearchStatsOverviewData()
	{
		if(isset($_GET['from']) && is_numeric($_GET['from']) && isset($_GET['to']) && is_numeric($_GET['to'])) {

			$from = (int)$_GET['from'];
			$to = (int)$_GET['to'];

			$output = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
			$output .= "<pie>\n";
			$query = sprintf("select distinct(searchtext), count(searchid) as numsearches from [|PREFIX|]searches_extended where searchdate >= '%d' and searchdate <= '%d' group by searchtext order by numsearches desc limit 10", $from, $to);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$output .= sprintf("\t<slice title=\"%s\" pull_out=\"false\">%s</slice>\n", isc_html_escape(isc_convert_charset(GetConfig('CharacterSet'), 'UTF-8', $row['searchtext'])), $row['numsearches']);
				}
			}

			$output .= "</pie>";
			echo $output;
		}
	}


	/**
	 * Fetch a data grid for search terms with results.
	 */
	public function SearchStatsWithResultsGrid()
	{
		$GLOBALS['ResultsGrid'] = "";

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

			$GLOBALS['ResultsPerPage'] = $per_page;
			$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';

			// Should we limit the records returned?
			if(isset($_GET['Page'])) {
				$page = (int)$_GET['Page'];
			}
			else {
				$page = 1;
			}

			$GLOBALS['KeywordsWithResultsCurrentPage'] = $page;

			// Workout the start and end records
			$start = ($per_page * $page) - $per_page;
			$end = $start + ($per_page - 1);

			// How many searches with results are there in total?
			$query = sprintf("select count(distinct(searchtext)) as num
								from [|PREFIX|]searches_extended
								where numresults > 0
								and searchdate >= '%d' and searchdate <= '%d'",
								$from_stamp,
								$to_stamp
			);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$total_results = $row['num'];

			if($total_results == 0) {
				$GLOBALS['HidePagingLinks'] = "display: none";
			}

			// Workout the paging
			$num_pages = ceil($total_results / $per_page);
			$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
			$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

			// Is there more than one page? If so show the &laquo; to jump back to page 1
			if($num_pages > 1) {
				$paging .= "<a href='javascript:void(0)' onclick='ChangeKeywordsWithResultsPage(1)'>&laquo;</a> | ";
			}
			else {
				$paging .= "&laquo; | ";
			}

			// Are we on page 2 or above?
			if($page > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeKeywordsWithResultsPage(%d)'>%s</a> | ", $page-1, GetLang('Prev'));
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
						$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeKeywordsWithResultsPage(%d)'>%d</a> | ", $i, $i);
					}
				}
			}

			// Are we on page 2 or above?
			if($page < $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeKeywordsWithResultsPage(%d)'>%s</a> | ", $page+1, GetLang('Next'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Next'));
			}

			// Is there more than one page? If so show the &raquo; to go to the last page
			if($num_pages > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeKeywordsWithResultsPage(%d)'>&raquo;</a> | ", $num_pages);
			}
			else {
				$paging .= "&raquo; | ";
			}

			$paging = rtrim($paging, ' |');
			$GLOBALS['Paging'] = $paging;

			if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "asc") {
				$sortOrder = 'asc';
			}
			else {
				$sortOrder = 'desc';
			}

			$sortFields = array('searchtext', 'numresults', 'numsearches', 'lastperformed');
			if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
				$sortField = $_GET['SortBy'];
				SaveDefaultSortField("SearchStatsWithResults", $_REQUEST['SortBy'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("SearchStatsWithResults", "numsearches", $sortOrder);
			}

			$sortLinks = array(
				"SearchTerms" => "searchtext",
				"NumberOfSearches" => "numsearches",
				"NumberOfResults" => "numresults",
				"LastPerformed" => "lastperformed"
			);
			BuildAdminSortingLinks($sortLinks, "javascript:SortKeywordsWithResults('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);


			// Should we set focus to the grid?
			if(isset($_GET['FromLink']) && $_GET['FromLink'] == "true") {
				$GLOBALS['JumpToKeywordsWithResultsGrid'] = "<script type=\"text/javascript\">document.location.href='#keywordsWithResultsAnchor';</script>";
			}

			$query = sprintf("select distinct(searchtext), max(numresults) as numresults, count(searchid) as numsearches, max(searchdate) as lastperformed
								from [|PREFIX|]searches_extended
								where numresults > 0
								and searchdate >= '%d' and searchdate <= '%d'
								group by searchtext
								order by %s %s",
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
					$GLOBALS['ResultsGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldSearchTermsClass']."\">
								<a href=\"../search.php?search_query=%s\" target=\"_blank\">%s</a>
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldNumberOfSearchesClass']."\">
								%s
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldNumberOfResultsClass']."\">
								%s
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldLastPerformedClass']."\">
								%s
							</td>
						</tr>
					", urlencode($row['searchtext']),
						isc_html_escape($row['searchtext']),
						number_format($row['numsearches']),
						number_format($row['numresults']),
						isc_date(GetConfig('DisplayDateFormat'), $row['lastperformed'])
					);
				}
			}
			else {
					$GLOBALS['HideStatsRows'] = "none";
					$GLOBALS['ResultsGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" colspan=\"4\">
								<em>%s</em>
							</td>
						</tr>
					", GetLang('StatsNoSearchesWithResultsForDate')
					);
			}
			$this->template->display('stats.search.withresultsgrid.tpl');
		}
	}

	/**
	 * Fetch a data grid for search terms without results.
	 */
	public function SearchStatsWithoutResultsGrid()
	{
		$GLOBALS['ResultsGrid'] = "";

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

			$GLOBALS['ResultsPerPage'] = $per_page;
			$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';

			// Should we limit the records returned?
			if(isset($_GET['Page'])) {
				$page = (int)$_GET['Page'];
			}
			else {
				$page = 1;
			}

			$GLOBALS['KeywordsWithoutResultsCurrentPage'] = $page;

			// Workout the start and end records
			$start = ($per_page * $page) - $per_page;
			$end = $start + ($per_page - 1);

			// How many searches with results are there in total?
			$query = sprintf("select count(distinct(searchtext)) as num
								from [|PREFIX|]searches_extended
								where numresults = 0
								and searchdate >= '%d' and searchdate <= '%d'",
								$from_stamp,
								$to_stamp
			);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$total_results = $row['num'];

			if($total_results == 0) {
				$GLOBALS['HidePagingLinks'] = "display: none";
			}

			// Workout the paging
			$num_pages = ceil($total_results / $per_page);
			$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
			$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

			// Is there more than one page? If so show the &laquo; to jump back to page 1
			if($num_pages > 1) {
				$paging .= "<a href='javascript:void(0)' onclick='ChangeKeywordsWithoutResultsPage(1)'>&laquo;</a> | ";
			}
			else {
				$paging .= "&laquo; | ";
			}

			// Are we on page 2 or above?
			if($page > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeKeywordsWithoutResultsPage(%d)'>%s</a> | ", $page-1, GetLang('Prev'));
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
						$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeKeywordsWithoutResultsPage(%d)'>%d</a> | ", $i, $i);
					}
				}
			}

			// Are we on page 2 or above?
			if($page < $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeKeywordsWithoutResultsPage(%d)'>%s</a> | ", $page+1, GetLang('Next'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Next'));
			}

			// Is there more than one page? If so show the &raquo; to go to the last page
			if($num_pages > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeKeywordsWithoutResultsPage(%d)'>&raquo;</a> | ", $num_pages);
			}
			else {
				$paging .= "&raquo; | ";
			}

			$paging = rtrim($paging, ' |');
			$GLOBALS['Paging'] = $paging;

			if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "asc") {
				$sortOrder = 'asc';
			}
			else {
				$sortOrder = 'desc';
			}

			$sortFields = array('searchtext', 'numsearches', 'searchdate');
			if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
				$sortField = $_GET['SortBy'];
				SaveDefaultSortField("SearchStatsWithoutResults", $_REQUEST['SortBy'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("SearchStatsWithoutResults", "numsearches", $sortOrder);
			}

			$sortLinks = array(
				"SearchTerms" => "searchtext",
				"NumberOfSearches" => "numsearches",
				"LastPerformed" => "searchdate"
			);
			BuildAdminSortingLinks($sortLinks, "javascript:SortKeywordsWithoutResults('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);

			// Should we set focus to the grid?
			if(isset($_GET['FromLink']) && $_GET['FromLink'] == "true") {
				$GLOBALS['JumpToKeywordsWithoutResultsGrid'] = "<script type=\"text/javascript\">document.location.href='#keywordsWithoutResultsAnchor';</script>";
			}

			$query = sprintf("select distinct(searchtext), count(searchid) as numsearches, max(searchdate) as lastperformed
								from [|PREFIX|]searches_extended
								where numresults = 0
								and searchdate >= '%d' and searchdate <= '%d'
								group by searchtext
								order by %s %s",
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
					$GLOBALS['ResultsGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldSearchTermsClass']."\">
								<a href=\"../search.php?search_query=%s\" target=\"_blank\">%s</a>
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldNumberOfSearchesClass']."\">
								%s
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldLastPerformedClass']."\">
								%s
							</td>
						</tr>
					", urlencode($row['searchtext']),
						isc_html_escape($row['searchtext']),
						number_format($row['numsearches']),
						isc_date(GetConfig('DisplayDateFormat'), $row['lastperformed'])
					);
				}
			}
			else {
					$GLOBALS['HideStatsRows'] = "none";
					$GLOBALS['ResultsGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" colspan=\"3\">
								<em>%s</em>
							</td>
						</tr>
					", GetLang('StatsNoSearchesWithoutResultsForDate')
					);
			}
			$this->template->display('stats.search.withoutresultsgrid.tpl');
		}
	}

	/**
	 * Fetch a data grid of either the best or worst performing search keywords.
	 */
	public function SearchStatsPerformanceGrid()
	{
		$GLOBALS['ResultsGrid'] = "";

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

			$GLOBALS['ResultsPerPage'] = $per_page;
			$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';


			// Should we limit the records returned?
			if(isset($_GET['Page'])) {
				$page = (int)$_GET['Page'];
			}
			else {
				$page = 1;
			}

			// Showing the worst performing (no clicks)?
			if(isc_strtolower($_REQUEST['ToDo']) == "searchstatsworstperforminggrid") {
				$clickwhere = 0;
				$pagingFunction = "ChangeWorstPerformingKeywordsPage";
				$GLOBALS['Anchor'] = "worstPerformingKeywordsAnchor";
				$GLOBALS['ChangePerPageFunction'] = "ChangeWorstPerformingKeywordsPerPage";
				$GLOBALS['SortGridFunction'] = "SortWorstPerformingKeywords";
				$NoResultsError = "StatsNoSearchesWithoutClickthru";
				$SortSave = "SearchStatsWorstPerforming";
			}
			// Or those with click thrus.
			else {
				$clickwhere = 1;
				$pagingFunction = "ChangeBestPerformingKeywordsPage";
				$GLOBALS['ChangePerPageFunction'] = "ChangeBestPerformingKeywordsPerPage";
				$GLOBALS['Anchor'] = "bestPerformingKeywordsAnchor";
				$GLOBALS['SortGridFunction'] = "SortBestPerformingKeywords";
				$NoResultsError = "StatsNoSearchesWithClickthru";
				$SortSave = "SearchStatsBestPerforming";
			}

			$GLOBALS['PerformingCurrentPage'] = $page;

			// Workout the start and end records
			$start = ($per_page * $page) - $per_page;
			$end = $start + ($per_page - 1);

			// How many searches with results are there in total?
			$query = sprintf("select count(distinct(searchtext)) as num
								from [|PREFIX|]searches_extended
								where numresults > 0
								and clickthru='%d'
								and searchdate >= '%d' and searchdate <= '%d'",
								$clickwhere,
								$from_stamp,
								$to_stamp
			);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$total_results = $row['num'];

			if($total_results == 0) {
				$GLOBALS['HidePagingLinks'] = "display: none";
			}

			// Workout the paging
			$num_pages = ceil($total_results / $per_page);
			$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
			$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

			// Is there more than one page? If so show the &laquo; to jump back to page 1
			if($num_pages > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='%s(1)'>&laquo;</a> | ", $pagingFunction);
			}
			else {
				$paging .= "&laquo; | ";
			}

			// Are we on page 2 or above?
			if($page > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='%s(%d)'>%s</a> | ", $pagingFunction, $page-1, GetLang('Prev'));
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
						$paging .= sprintf("<a href='javascript:void(0)' onclick='%s(%d)'>%d</a> | ", $pagingFunction, $i, $i);
					}
				}
			}

			// Are we on page 2 or above?
			if($page < $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='%s(%d)'>%s</a> | ", $pagingFunction, $page+1, GetLang('Next'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Next'));
			}

			// Is there more than one page? If so show the &raquo; to go to the last page
			if($num_pages > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='%s(%d)'>&raquo;</a> | ", $pagingFunction, $num_pages);
			}
			else {
				$paging .= "&raquo; | ";
			}

			$paging = rtrim($paging, ' |');
			$GLOBALS['Paging'] = $paging;

			if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "asc") {
				$sortOrder = 'asc';
			}
			else {
				$sortOrder = 'desc';
			}

			$sortFields = array('searchtext', 'numresults', 'numsearches', 'searchdate');
			if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
				$sortField = $_GET['SortBy'];
				SaveDefaultSortField($SortSave, $_REQUEST['SortBy'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField($SortSave, "numsearches", $sortOrder);
			}

			$sortLinks = array(
				"SearchTerms" => "searchtext",
				"NumberOfSearches" => "numsearches",
				"NumberOfResults" => "numresults",
				"LastPerformed" => "searchdate"
			);
			BuildAdminSortingLinks($sortLinks, "javascript:".$GLOBALS['SortGridFunction']."('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);

			// Should we set focus to the grid?
			if(isset($_GET['FromLink']) && $_GET['FromLink'] == "true") {
				$GLOBALS['JumpTo'] = sprintf("<script type=\"text/javascript\">document.location.href='#%s';</script>", $GLOBALS['Anchor']);
			}

			$query = sprintf("select distinct(searchtext), max(numresults) as numresults, count(searchid) as numsearches, max(searchdate) as lastperformed
								from [|PREFIX|]searches_extended
								where numresults > 0
								and clickthru='%d'
								and searchdate >= '%d' and searchdate <= '%d'
								group by searchtext
								order by %s %s",
								$clickwhere,
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
					$GLOBALS['ResultsGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldSearchTermsClass']."\">
								<a href=\"../search.php?search_query=%s\" target=\"_blank\">%s</a>
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldNumberOfSearchesClass']."\">
								%s
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldNumberOfResultsClass']."\">
								%s
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldLastPerformedClass']."\">
								%s
							</td>
						</tr>
					", urlencode($row['searchtext']),
						isc_html_escape($row['searchtext']),
						number_format($row['numsearches']),
						number_format($row['numresults']),
						isc_date(GetConfig('DisplayDateFormat'), $row['lastperformed'])
					);
				}
			}
			else {
					$GLOBALS['HideStatsRows'] = "none";
					$GLOBALS['ResultsGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" colspan=\"4\">
								<em>%s</em>
							</td>
						</tr>
					", GetLang($NoResultsError)
					);
			}
			$this->template->display('stats.search.performancegrid.tpl');
		}
	}

	/**
	 * Fetch a data grid for search terms with results.
	 */
	public function SearchStatsCorrectionsGrid()
	{
		$GLOBALS['ResultsGrid'] = "";

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

			$GLOBALS['ResultsPerPage'] = $per_page;
			$GLOBALS["IsShowPerPage" . $per_page] = 'selected="selected"';

			// Should we limit the records returned?
			if(isset($_GET['Page'])) {
				$page = (int)$_GET['Page'];
			}
			else {
				$page = 1;
			}

			$GLOBALS['CorrectionsCurrentPage'] = $page;

			// Workout the start and end records
			$start = ($per_page * $page) - $per_page;
			$end = $start + ($per_page - 1);

			// How many searches with results are there in total?
			$query = sprintf("select count(distinct(concat(correction,oldsearchtext))) as num
								from [|PREFIX|]search_corrections
								where correctdate >= '%d' and correctdate <= '%d'",
								$from_stamp,
								$to_stamp
			);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$total_results = $row['num'];

			if($total_results == 0) {
				$GLOBALS['HidePagingLinks'] = "display: none";
			}

			// Workout the paging
			$num_pages = ceil($total_results / $per_page);
			$paging = sprintf(GetLang('PageXOfX'), $page, $num_pages);
			$paging .= "&nbsp;&nbsp;&nbsp;&nbsp;";

			// Is there more than one page? If so show the &laquo; to jump back to page 1
			if($num_pages > 1) {
				$paging .= "<a href='javascript:void(0)' onclick='ChangeSearchCorrectionsPage(1)'>&laquo;</a> | ";
			}
			else {
				$paging .= "&laquo; | ";
			}

			// Are we on page 2 or above?
			if($page > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeSearchCorrectionsPage(%d)'>%s</a> | ", $page-1, GetLang('Prev'));
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
						$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeSearchCorrectionsPage(%d)'>%d</a> | ", $i, $i);
					}
				}
			}

			// Are we on page 2 or above?
			if($page < $num_pages) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeSearchCorrectionsPage(%d)'>%s</a> | ", $page+1, GetLang('Next'));
			}
			else {
				$paging .= sprintf("%s | ", GetLang('Next'));
			}

			// Is there more than one page? If so show the &raquo; to go to the last page
			if($num_pages > 1) {
				$paging .= sprintf("<a href='javascript:void(0)' onclick='ChangeSearchCorrectionsPage(%d)'>&raquo;</a> | ", $num_pages);
			}
			else {
				$paging .= "&raquo; | ";
			}

			$paging = rtrim($paging, ' |');
			$GLOBALS['Paging'] = $paging;

			if(isset($_GET['SortOrder']) && $_GET['SortOrder'] == "asc") {
				$sortOrder = 'asc';
			}
			else {
				$sortOrder = 'desc';
			}

			$sortFields = array('oldsearchtext', 'oldnumresults', 'correction', 'numresults', 'numoccurances', 'correctiontype');
			if(isset($_GET['SortBy']) && in_array($_GET['SortBy'], $sortFields)) {
				$sortField = $_GET['SortBy'];
				SaveDefaultSortField("SearchStatsCorrections", $_REQUEST['SortBy'], $sortOrder);
			}
			else {
				list($sortField, $sortOrder) = GetDefaultSortField("SearchStatsCorrections", "numoccurances", $sortOrder);
			}

			$sortLinks = array(
				"SearchTerms" => "oldsearchtext",
				"ProductsShownBefore" => "oldnumresults",
				"CorrectedSearchTerms" => "correction",
				"ProductsShownAfter" => "numresults",
				"NumberOfOccurances" => "numoccurances",
				"CorrectionType" => "correctiontype"
			);
			BuildAdminSortingLinks($sortLinks, "javascript:SortSearchCorrections('%%SORTFIELD%%', '%%SORTORDER%%');", $sortField, $sortOrder);

			// Should we set focus to the grid?
			if(isset($_GET['FromLink']) && $_GET['FromLink'] == "true") {
				$GLOBALS['JumpToSearchCorrectionsGrid'] = "<script type=\"text/javascript\">document.location.href='#searchCorrectionsAnchor';</script>";
			}

			$query = sprintf("select count(correctionid) as numoccurances, correctiontype, correction, numresults, oldsearchtext, oldnumresults
								from [|PREFIX|]search_corrections
								where numresults > 0
								and correctdate >= '%d' and correctdate <= '%d'
								group by concat(oldsearchtext,correction)
								order by %s %s",
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
					if($row['correctiontype'] == "correction") {
						$type = GetLang('SearchCorrection');
					} else if($row['correctiontype'] == "recommendation") {
						$type = GetLang('SearchRecommendation');
					}

					$GLOBALS['ResultsGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap class=\"".$GLOBALS['SortedFieldSearchTermsClass']."\">
								<a href=\"../search.php?search_query=%s\" target=\"_blank\">%s</a>
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldProductsShownBeforeClass']."\">
								%s
							</td>
							<td nowrap class=\"".$GLOBALS['SortedFieldCorrectedSearchTermsClass']."\">
								<a href=\"../search.php?search_query=%s\" target=\"_blank\">%s</a>
							</td>
							<td nowrap align='right' class=\"".$GLOBALS['SortedFieldProductsShownAfterClass']."\">
								%s
							</td>
							<td nowrap align='right'  class=\"".$GLOBALS['SortedFieldNumberOfOccurancesClass']."\">
								%s
							</td>
							<td nowrap height=\"22\" class=\"".$GLOBALS['SortedFieldCorrectionTypeClass']."\">
								%s
							</td>
						</tr>
					", urlencode($row['oldsearchtext']),
						isc_html_escape($row['oldsearchtext']),
						number_format($row['oldnumresults']),
						urlencode($row['correction']),
						isc_html_escape($row['correction']),
						number_format($row['numresults']),
						number_format($row['numoccurances']),
						isc_html_escape($type)
					);
				}
			}
			else {
					$GLOBALS['HideStatsRows'] = "none";
					$GLOBALS['ResultsGrid'] .= sprintf("
						<tr class=\"GridRow\" onmouseover=\"this.className='GridRowOver';\" onmouseout=\"this.className='GridRow';\">
							<td nowrap height=\"22\" colspan=\"6\">
								<em>%s</em>
							</td>
						</tr>
					", GetLang('StatsNoCorrectionsForDate')
					);
			}
			$this->template->display('stats.search.correctionsgrid.tpl');
		}
	}
}