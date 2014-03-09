	<form action="index.php?ToDo=viewSearchStats" name="frmStats" id="frmStats" method="post">
	<input id="currentTab" name="currentTab" value="0" type="hidden">
	<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">Search Statistics</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'StoreOverviewIntro' %}</p>
			{{ Message|safe }}
		</td>
	</tr>
	<tr>
		<td class="Intro">
			<div>
				<input type="button" name="clearSearchStats" value="Delete Statistics" onclick="clearStatsClick()" class="SmallButton" />
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<ul id="tabnav">
				<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'StatsOverview' %}</a></li>
				<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'SearchStatsKeywordsResults' %}</a></li>
				<li><a href="#" id="tab2" onclick="ShowTab(2)">{% lang 'SearchStatsKeywordsNoResults' %}</a></li>
				<li><a href="#" id="tab3" onclick="ShowTab(3)">{% lang 'SearchStatsBestPerforming' %}</a></li>
				<li><a href="#" id="tab4" onclick="ShowTab(4)">{% lang 'SearchStatsWorstPerforming' %}</a></li>
				<li><a href="#" id="tab5" onclick="ShowTab(5)">{% lang 'SearchStatsCorrections' %}</a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td>
			<br />
			<div id="introText" style="padding:0px 0px 5px 10px" class="Text"></div>
			<div style="padding:5px 0px 5px 10px" class="Text FloatLeft">
				<table border=0 cellspacing=0 cellpadding=0>
					<tr>
						<td style="background: #eee; padding: 3px 5px;" width="1">
							<img src="images/dateicon.gif" />
						</td>
						<td style="background: #eee;">Date Range:</td>
						<td style="background: #eee; padding: 3px 5px;" width="1">
							<select name="Calendar[DateType]" id="Calendar" class="CalendarSelect" onchange="doCustomDate(this, 7)">
								<option value="Today">{% lang 'Today' %}</option>
								<option value="Yesterday">{% lang 'Yesterday' %}</option>
								<option value="Last24Hours">{% lang 'Last24Hours' %}</option>
								<option value="Last7Days">{% lang 'Last7Days' %}</option>
								<option value="Last30Days">{% lang 'Last30Days' %}</option>
								<option value="ThisMonth">{% lang 'ThisMonth' %}</option>
								<option value="LastMonth">{% lang 'LastMonth' %}</option>
								<option value="AllTime" SELECTED>{% lang 'AllTime' %}</option>
								<option value="Custom">{% lang 'Custom' %}</option>
							</select>
						</td>
						<td style="background: #eee;">
							<span id=customDate7 style="display:none">&nbsp;
							<select name="Calendar[From][Day]" class="CalendarSelectSmall" style="margin-bottom:3px">
								{{ OverviewFromDays|safe }}
							</select>
							<select name="Calendar[From][Mth]" class="CalendarSelectSmall" style="margin-bottom:3px">
								{{ OverviewFromMonths|safe }}
							</select>
							<select name="Calendar[From][Yr]" class="CalendarSelectSmall" style="margin-bottom:3px">
								{{ OverviewFromYears|safe }}
							</select>
							<span class=body>{% lang 'To1' %}</span>
							<select name="Calendar[To][Day]" class="CalendarSelectSmall" style="margin-bottom:3px">
								{{ OverviewToDays|safe }}
							</select>
							<select name="Calendar[To][Mth]" class="CalendarSelectSmall" style="margin-bottom:3px">
								{{ OverviewToMonths|safe }}
							</select>
							<select name="Calendar[To][Yr]" class="CalendarSelectSmall" style="margin-bottom:3px">
								{{ OverviewToYears|safe }}
							</select>
							</span>&nbsp;
						</td>
						<td style="background: #eee; padding: 3px 5px;"><input type="submit" value="Go" class="Text" /></td>
					</tr>
				</table>
			</div>
			<div id="div0" style="padding-top:10px">
				<table width="100%" border="0" class="text" style="padding-left:10px; clear: both;">
					<tr>
						<td valign=top width="250" nowrap>
							<div class="MidHeading" style="width:100%"><img src="images/order.gif" align="absMiddle">&nbsp;{% lang 'OverviewSearchSummary' %}</div>
							<ul class="Text">
								<li>{% lang 'OverviewNoSearches' %}: {{ OverviewNumSearches|safe }}</li>
								<li>{% lang 'OverviewMostSearchesDay' %}: {{ OverviewMostSearchesDay|safe }}</li>
								<li>{% lang 'OverviewAvgSearchesDay' %}: {{ OverviewAverageSearchesDay|safe }}</li>
								<li>{% lang 'OverviewPopularTermsResults' %}: {{ OverviewMostPopularTerms|safe }}</li>
								<li>{% lang 'OverviewPopularTermsNoResults' %}: {{ OverviewMostPopularTermsNoResults|safe }}</li>
							</ul>
						</td>
						<td width="100%" align="center">
							<div style="display: {{ HideChart|safe }}">
								<strong>{{ ChartTitle|safe }}</strong>
								<div id="flashcontent" style="clear: both; width: 100%;">
								</div>
								<script type="text/javascript" src="includes/amcharts/swfobject.js?{{ JSCacheToken }}"></script>
								<script type="text/javascript">
									$(document).ready(function() {
										var so = new SWFObject("{{ ShopPath|safe }}/admin/includes/amcharts/ampie.swf", "ampie", "100%", "430", "8", "#FFFFFF");
										so.addVariable("path", "{{ ShopPath|safe }}/admin/includes/amcharts/");
										so.addVariable("settings_file", escape("{{ ShopPath|safe }}/admin/includes/amcharts/searchoverview.xml"));
										so.addVariable("data_file", escape("{{ ShopPath|safe }}/admin/index.php?ToDo=searchStatsOverviewData&from={{ OverviewFromStamp|safe }}&to={{ OverviewToStamp|safe }}"));
										so.addVariable("preloader_color", "#000000");
										so.write("flashcontent");
									});
								</script>
							</div>
						</td>
					</tr>
				</table>
			</div>
			<div id="div1" style="padding-top:10px; padding-left:10px" class="text">
				<div id="keywordsWithResultsGrid">
				</div>
			</div>
			<div id="div2" style="padding-top:10px; padidng-left:10px" class="text">
				<div id="keywordsWithoutResultsGrid">
				</div>
			</div>
			<div id="div3" style="padding-top:10px; padding-left:10px" class="text">
				<div id="bestPerformingKeywordsGrid">
				</div>
			</div>
			<div id="div4" style="padding-top:10px; padding-left:10px" class="text">
				<div id="worstPerformingKeywordsGrid">
				</div>
			</div>
			<div id="div5" style="padding-top:10px; padding-left:10px" class="text">
				<div id="searchCorrectionsGrid">
				</div>
			</div>
			</form>
		</td>
	</tr>
	</table>
	</div>

	<script type="text/javascript">

	var resultsPerPage = 20;

	var keywordsWithResultsLoaded = false;
	var keywordsWithResultsFromLink = false;
	var keywordsWithResultsCurrentPage = 1;
	var keywordsWithResultsSortField = '';
	var keywordsWithResultsSortOrder = '';

	var keywordsWithoutResultsLoaded = false;
	var keywordsWithoutResultsFromLink = false;
	var keywordsWithoutResultsCurrentPage = 1;
	var keywordsWithoutResultsSortField = '';
	var keywordsWithoutResultsSortOrder = '';

	var bestPerformingKeywordsLoaded = false;
	var bestPerformingKeywordsFromLink = false;
	var bestPerformingKeywordsCurrentPage = 1;
	var bestPerformingKeywordsSortField = '';
	var bestPerformingKeywordsSortOrder = '';

	var worstPerformingKeywordsLoaded = false;
	var worstPerformingKeywordsFromLink = false;
	var worstPerformingKeywordsCurrentPage = 1;
	var worstPerformingKeywordsSortField = '';
	var worstPerformingKeywordsSortOrder = '';

	var searchCorrectionsLoaded = false;
	var searchCorrectionsFromLink = false;
	var searchCorrectionsCurrentPage = 1;
	var searchCorrectionsSortField = '';
	var searchCorrectionsSortOrder = '';

	function ShowTab(T) {

		i = 0;

		while (document.getElementById("tab" + i) != null) {
			document.getElementById("div" + i).style.display = "none";
			document.getElementById("tab" + i).className = "";
			i++;
		}

		document.getElementById("div" + T).style.display = "";
		document.getElementById("tab" + T).className = "active";
		document.getElementById("currentTab").value = T;

		// What should the intro text be?
		switch(T) {
			case 0: {
				$('#introText').html('{% lang 'StatsSearchOverviewIntro1' %}');
				break;
			}
			case 1: {
				$('#introText').html('{% lang 'StatsSearchOverviewIntro2' %}');

				if(!keywordsWithResultsLoaded) {
					LoadKeywordsWithResultsGrid();
					keywordsWithResultsLoaded = true;
				}
				break;
			}
			case 2: {
				$('#introText').html('{% lang 'StatsSearchOverviewIntro3' %}');

				if(!keywordsWithoutResultsLoaded) {
					LoadKeywordsWithoutResultsGrid();
					keywordsWithoutResultsLoaded = true;
				}
				break;
			}
			case 3: {
				$('#introText').html('{% lang 'StatsSearchOverviewIntro4' %}');

				if(!bestPerformingKeywordsLoaded) {
					LoadBestPerformingKeywordsGrid();
					bestPerformingKeywordsLoaded = true;
				}
				break;
			}
			case 4: {
				$('#introText').html('{% lang 'StatsSearchOverviewIntro5' %}');

				if(!worstPerformingKeywordsLoaded) {
					LoadWorstPerformingKeywordsGrid();
					worstPerformingKeywordsLoaded = true;
				}
				break;
			}
			case 5: {
				$('#introText').html('{% lang 'StatsSearchOverviewIntro6' %}');

				if(!searchCorrectionsLoaded) {
					LoadSearchCorrectionsGrid();
					searchCorrectionsLoaded = true;
				}
				break;
			}
		}
	}

	function ChangeKeywordsWithResultsPerPage(ResultsPerPage) {
		// Change how many results are shown per page
		resultsPerPage = ResultsPerPage;
		keywordsWithResultsCurrentPage = 1;
		keywordsWithResultsFromLink = true;
		LoadKeywordsWithResultsGrid();
	}

	function ChangeKeywordsWithResultsPage(Page) {
		// Change which page of results we're viewing
		keywordsWithResultsCurrentPage = Page;
		keywordsWithResultsFromLink = true;
		LoadKeywordsWithResultsGrid();
	}

	function SortKeywordsWithResults(field, order) {
		keywordsWithResultsSortField = field;
		keywordsWithResultsSortOrder = order;
		keywordsWithResultsFromLink = true;
		LoadKeywordsWithResultsGrid();
	}

	function LoadKeywordsWithResultsGrid() {
		// Load the search keywords with results and jump to a specific page
		jQuery.ajax({url: 'index.php?ToDo=searchStatsWithResultsGrid&FromLink='+keywordsWithResultsFromLink+'&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+keywordsWithResultsCurrentPage+'&Show='+resultsPerPage+'&SortBy='+keywordsWithResultsSortField+'&SortOrder='+keywordsWithResultsSortOrder,
			     success: function(data) {
				$('#keywordsWithResultsGrid').html(data)
			     }
			}
		);
	}

	function ChangeKeywordsWithoutResultsPerPage(ResultsPerPage) {
		// Change how many results are shown per page
		resultsPerPage = ResultsPerPage;
		keywordsWithoutResultsCurrentPage = 1;
		keywordsWithoutResultsFromLink = true;
		LoadKeywordsWithoutResultsGrid();
	}

	function ChangeKeywordsWithoutResultsPage(Page) {
		// Change which page of results we're viewing
		keywordsWithoutResultsCurrentPage = Page;
		keywordsWithoutResultsFromLink = true;
		LoadKeywordsWithoutResultsGrid();
	}

	function SortKeywordsWithoutResults(field, order) {
		keywordsWithoutResultsSortField = field;
		keywordsWithoutResultsSortOrder = order;
		keywordsWithoutResultsFromLink = true;
		LoadKeywordsWithoutResultsGrid();
	}

	function LoadKeywordsWithoutResultsGrid() {
		// Load the search keywords without results and jump to a specific page
		jQuery.ajax({url: 'index.php?ToDo=searchStatsWithoutResultsGrid&FromLink='+keywordsWithoutResultsFromLink+'&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+keywordsWithoutResultsCurrentPage+'&Show='+resultsPerPage+'&SortBy='+keywordsWithoutResultsSortField+'&SortOrder='+keywordsWithoutResultsSortOrder,
			     success: function(data) {
				$('#keywordsWithoutResultsGrid').html(data)
			     }
			}
		);
	}

	function ChangeBestPerformingKeywordsPerPage(ResultsPerPage) {
		// Change how many results are shown per page
		resultsPerPage = ResultsPerPage;
		bestPerformingKeywordsCurrentPage = 1;
		bestPerformingKeywordsFromLink = true;
		LoadBestPerformingKeywordsGrid();
	}

	function ChangeBestPerformingKeywordsPage(Page) {
		// Change which page of results we're viewing
		bestPerformingKeywordsCurrentPage = Page;
		bestPerformingKeywordsFromLink = true;
		LoadBestPerformingKeywordsGrid();
	}

	function SortBestPerformingKeywords(field, order) {
		bestPerformingKeywordsSortField = field;
		bestPerformingKeywordsSortOrder = order;
		bestPerformingKeywordsFromLink = true;
		LoadBestPerformingKeywordsGrid();
	}

	function LoadBestPerformingKeywordsGrid() {
		// Load the best performing search keywords and jump to a specific page
		jQuery.ajax({url: 'index.php?ToDo=searchStatsBestPerformingGrid&FromLink='+bestPerformingKeywordsFromLink+'&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+bestPerformingKeywordsCurrentPage+'&Show='+resultsPerPage+'&SortBy='+bestPerformingKeywordsSortField+'&SortOrder='+bestPerformingKeywordsSortOrder,
			     success: function(data) {
				$('#bestPerformingKeywordsGrid').html(data)
			     }
			}
		);
	}

	function ChangeWorstPerformingKeywordsPerPage(ResultsPerPage) {
		// Change how many results are shown per page
		resultsPerPage = ResultsPerPage;
		worstPerformingKeywordsCurrentPage = 1;
		worstPerformingKeywordsFromLink = true;
		LoadWorstPerformingKeywordsGrid();
	}

	function ChangeWorstPerformingKeywordsPage(Page) {
		// Change which page of results we're viewing
		worstPerformingKeywordsCurrentPage = Page;
		worstPerformingKeywordsFromLink = true;
		LoadWorstPerformingKeywordsGrid();
	}

	function SortWorstPerformingKeywords(field, order) {
		worstPerformingKeywordsSortField = field;
		worstPerformingKeywordsSortOrder = order;
		worstPerformingKeywordsFromLink = true;
		LoadWorstPerformingKeywordsGrid();
	}

	function LoadWorstPerformingKeywordsGrid() {
		// Load the worst performing search keywords and jump to a specific page
		jQuery.ajax({url: 'index.php?ToDo=searchStatsWorstPerformingGrid&FromLink='+worstPerformingKeywordsFromLink+'&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+worstPerformingKeywordsCurrentPage+'&Show='+resultsPerPage+'&SortBy='+worstPerformingKeywordsSortField+'&SortOrder='+worstPerformingKeywordsSortOrder,
			     success: function(data) {
				$('#worstPerformingKeywordsGrid').html(data)
			     }
			}
		);
	}

	function ChangeSearchCorrectionsPerPage(ResultsPerPage) {
		// Change how many results are shown per page
		resultsPerPage = ResultsPerPage;
		searchCorrectionsCurrentPage = 1;
		searchCorrectionsFromLink = true;
		LoadSearchCorrectionsGrid();
	}

	function ChangeSearchCorrectionsPage(Page) {
		// Change which page of results we're viewing
		searchCorrectionsCurrentPage = Page;
		searchCorrectionsFromLink = true;
		LoadSearchCorrectionsGrid();
	}

	function SortSearchCorrections(field, order) {
		searchCorrectionsSortField = field;
		searchCorrectionsSortOrder = order;
		searchCorrectionsFromLink = true;
		LoadSearchCorrectionsGrid();
	}


	function LoadSearchCorrectionsGrid() {
		// Load the search corrections grid for the page we're viewing
		jQuery.ajax({url: 'index.php?ToDo=searchStatsCorrectionsGrid&FromLink='+searchCorrectionsFromLink+'&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+searchCorrectionsCurrentPage+'&Show='+resultsPerPage+'&SortBy='+searchCorrectionsSortField+'&SortOrder='+searchCorrectionsSortOrder,
			     success: function(data) {
				$('#searchCorrectionsGrid').html(data)
			     }
			}
		);
	}


	$(document).ready(function() {

		ShowTab({{ CurrentTab|safe }});

		// Which date range is selected?
		var current_date = '{{ CurrentDate|safe }}';
		var Calendar = g('Calendar');

		for(i = 0; i < Calendar.options.length; i++) {
			if(Calendar.options[i].value == current_date) {
				Calendar.options[i].selected = true;
				break;
			}
		}

		// Is it custom? If so, show the custom date ranges
		if(current_date == 'Custom') {
			doCustomDate(g('Calendar'), 7);
		}
	});

	function clearStatsClick()
	{
		if(confirm('{% lang 'ConfirmDeleteSearchStats' %}'))
			window.location = 'index.php?ToDo=clearSearchStats';
	}

	</script>
