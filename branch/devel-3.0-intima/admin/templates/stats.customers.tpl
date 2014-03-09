
	<form action="index.php?ToDo=viewCustStats" name="frmStats" id="frmStats" method="post">
	<input id="currentTab" name="currentTab" value="0" type="hidden">
	<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">{% lang 'CustomerStatistics' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'CustomerStatsIntro' %}</p>
			{{ Message|safe }}
		</td>
	</tr>
	<tr>
		<td>
			<ul id="tabnav">
				<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'CustomersByDate' %}</a></li>
				<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'RevenuePerCustomer' %}</a></li>
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
			<div id="div0" style="padding-top:10px" class="text">
				<center>
					<strong><span style="display:{{ HideNoAdvancedStatsMessage|safe }}; color:#CACACA"><br />({% lang 'NoOrderData2Days' %})</span></strong>
				</center>
				<div id="flashcontent" style="width: 100%; clear: both;">
				</div>
				<script type="text/javascript" src="{{ ShopPath|safe }}/admin/includes/amcharts/swfobject.js?{{ JSCacheToken }}"></script>
				<script type="text/javascript">
					$(document).ready(function() {
						var so = new SWFObject("{{ ShopPath|safe }}/admin/includes/amcharts/amline/amline.swf", "amline", "98%", "430", "8", "#FFFFFF");
						so.addVariable("path", "{{ ShopPath|safe }}/admin/includes/amcharts/");
						so.addVariable("settings_file", escape("{{ ShopPath|safe }}/admin/includes/amcharts/customersbydate.xml"));
						so.addVariable("data_file", escape("{{ ShopPath|safe }}/admin/index.php?ToDo=custStatsByDateData&from={{ OverviewFromStamp|safe }}&to={{ OverviewToStamp|safe }}"));
						so.addVariable("preloader_color", "#000000");
						so.write("flashcontent");
					});
				</script>
				<div id="customersByDateGrid">
				</div>
			</div>
			<div id="div1" style="padding-top:10px; padding-left:10px" class="text">
				<div id="revenuePerCustomerGrid">
				</div>
			</div>

			</form>
		</td>
	</tr>
	</table>
	</div>

	<script type="text/javascript">

	var customersPerPage = 20;

	var customersByDateFromLink = false;
	var customersByDateCurrentPage = 1;
	var customersByDateSortField = '';
	var customersByDateSortOrder = '';

	var revenuePerCustomerLoaded = false;
	var revenuePerCustomerFromLink = false;
	var revenuePerCustomerCurrentPage = 1;
	var revenuePerCustomerSortField = '';
	var revenuePerCustomerSortOrder = '';

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
				$('#introText').html('{% lang 'CustomersByDateIntro' %}');
				break;
			}
			case 1: {
				$('#introText').html('{% lang 'RevenuePerCustomerIntro' %}');

				if(!revenuePerCustomerLoaded) {
					LoadRevenuePerCustomerGrid();
					revenuePerCustomerLoaded = true;
				}
				break;

			}
		}
	}

	function ChangeCustomersByDatePerPage(CustomersPerPage) {
		// Change how many customers are shown per page
		customersPerPage = CustomersPerPage;
		customersByDateCurrentPage = 1;
		customersByDateFromLink = true;
		LoadCustomersByDateGrid();
	}

	function ChangeCustomersByDatePage(Page) {
		// Change which page of customers we're viewing
		customersByDateCurrentPage = Page;
		customersByDateFromLink = true;
		LoadCustomersByDateGrid();
	}

	function SortCustomersByDate(field, order) {
		customersByDateSortField = field;
		customersByDateSortOrder = order;
		customersByDateFromLink = true;
		LoadCustomersByDateGrid();
	}

	function LoadCustomersByDateGrid() {
		// Load the customers and jump to a specific page
		jQuery.ajax({url: 'index.php?ToDo=custStatsByDateGrid&FromLink='+customersByDateFromLink+'&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+customersByDateCurrentPage+'&Show='+customersPerPage+'&SortBy='+customersByDateSortField+'&SortOrder='+customersByDateSortOrder,
			     success: function(data) {
				$('#customersByDateGrid').html(data)
			     }
			}
		);
	}

	function LoadRevenuePerCustomerGrid() {
		// Load revenue per customer
		jQuery.ajax({url: 'index.php?ToDo=custStatsByRevenueGrid&FromLink='+revenuePerCustomerFromLink+'&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+revenuePerCustomerCurrentPage+'&Show='+customersPerPage+'&SortBy='+revenuePerCustomerSortField+'&SortOrder='+revenuePerCustomerSortOrder,
			     success: function(data) {
				$('#revenuePerCustomerGrid').html(data)
			     }
			}
		);
	}

	function SortRevenuePerCustomer(field, order) {
		revenuePerCustomerSortField = field;
		revenuePerCustomerSortOrder = order;
		revenuePerCustomerFromLink = true;
		LoadRevenuePerCustomerGrid();
	}

	function ChangeRevenuePerCustomerPage(Page) {
		// Change which page of orders we're viewing
		revenuePerCustomerCurrentPage = Page;
		revenuePerCustomerFromLink = true;
		LoadRevenuePerCustomerGrid();
	}


	function ChangeCustomersByRevenuePerPage(CustomersPerPage) {
		// Change how many customers are shown per page
		customersPerPage = CustomersPerPage;
		revenuePerCustomerCurrentPage = 1;
		revenuePerCustomerFromLink = true;
		LoadRevenuePerCustomerGrid();
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
		// Load the customers table for the selected date range
		LoadCustomersByDateGrid();

	});

	</script>
