
	<form action="index.php?ToDo=viewOrdStats" name="frmStats" id="frmStats" method="post">
	<input id="currentTab" name="currentTab" value="0" type="hidden">
	<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">{% lang 'OrderStatistics' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'OrderStatsIntro' %}</p>
			{{ Message|safe }}
		</td>
	</tr>
	<tr>
		<td>
			<ul id="tabnav">
				<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'OrdersByDate' %}</a></li>
				<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'OrdersByNumSold' %}</a></li>
				<li><a href="#" id="tab2" onclick="ShowTab(2)">{% lang 'OrdersByRevenue' %}</a></li>
				<li><a href="#" id="tab3" onclick="ShowTab(3)">{% lang 'SalesTaxReport' %}</a></li>
				<li><a href="#" id="tab4" onclick="ShowTab(4)">{% lang 'OrdersByAbandon' %}</a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td>
			<br />
			<div id="exportbutton" style="float: right; overflow: hidden; display: none;">
				<input type="button" value="{% lang 'Export' %}" />
			</div>
			<div id="introText" style="padding:0px 0px 5px 10px" class="Text"></div>
			<div id="taxTotals" style="display: none; padding:5px 0px 5px 10px">
				<div class="MessageBox MessageBoxInfo">
					{{ SalesTaxSummary|safe }}
				</div>
			</div>
			<div id="abandonedTotals" style="display:none; padding:5px 0px 5px 10px">
				<div class="MessageBox MessageBoxInfo" style="display:none;">
					<!-- to be populated by js -->
				</div>
			</div>
			<div style="padding:5px 0px 5px 10px" class="Text FloatLeft">
				<table border=0 cellspacing=0 cellpadding=0>
					<tr>
						<td style="background: #eee; padding: 3px 5px;" width="1">
							<img src="images/dateicon.gif" />
						</td>
						<td style="background: #eee;">{% lang 'DateRange' %}:</td>
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
						<td class="ListByCol" style="background: #eee; padding: 3px 5px; display: none;" width="1">
							<img src="images/dateicon.gif" />
						<td class="ListByCol" style="background: #eee; display: none;">List by:</td>
						<td class="ListByCol" style="background: #eee; padding: 3px 5px; display: none;" width="1">
							<select name="TaxListBy" id="TaxListBy">
								<option value="Day" {{ TaxListByDay|safe }}>{% lang 'Day' %}</option>
								<option value="Month" {{ TaxListByMonth|safe }}>{% lang 'Month' %}</option>
								<option value="Year" {{ TaxListByYear|safe }}>{% lang 'Year' %}</option>
							</select>
						</td>
						<td style="background: #eee; padding: 3px 5px; {{ HideVendorList|safe }}" width="1">
							<img src="images/vendor.gif" />
						</td>
						<td style="background: #eee; {{ HideVendorList|safe }}">{% lang 'Vendor' %}:</td>
						<td style="background: #eee; padding: 3px 5px; {{ HideVendorList|safe }}" width="1">
							<select name="vendorId">
								{{ VendorSelect|safe }}
							</select>
						</td>
						<td style="background: #eee; padding: 3px 5px;"><input type="submit" value="Go" class="Text" /></td>
					</tr>
				</table>
			</div>
			<div id="div0" style="padding-top:10px" class="text">
				<center>
					<strong>{{ ByDateChartTitle|safe }} <span style="display:{{ HideNoAdvancedStatsMessage|safe }}; color:#CACACA"><br />({% lang 'NoOrderData2Days' %})</span></strong>
				</center>
				<div id="flashcontent" style="width: 100%; clear: both;">

				</div>
				<script type="text/javascript" src="includes/amcharts/swfobject.js?{{ JSCacheToken }}"></script>
				<script type="text/javascript">
					$(document).ready(function() {
						var so = new SWFObject("{{ ShopPath|safe }}/admin/includes/amcharts/amline/amline.swf", "amline", "98%", "430", "8", "#FFFFFF");
						so.addVariable("path", "{{ ShopPath|safe }}/admin/includes/amcharts/");
						so.addVariable("settings_file", escape("{{ ShopPath|safe }}/admin/includes/amcharts/overviewgeneral.xml"));
						so.addVariable("data_file", escape("{{ ShopPath|safe }}/admin/index.php?ToDo=overviewStatsData&from={{ OverviewFromStamp|safe }}&to={{ OverviewToStamp|safe }}&vendorId={{ VendorId|safe }}"));
						so.addVariable("preloader_color", "#000000");
						so.write("flashcontent");
					});
				</script>
				<div id="ordersByDateGrid">
				</div>
			</div>
			<div id="div1" style="padding-top:10px; padding-left:10px" class="text">
				<div id="ordersByItemsSoldGrid">
				</div>
			</div>
			<div id="div2" style="padding-top:10px; padding-left:10px; clear: both;" class="text">
				<table width="100%" border="0">
					<tr>
						<td width="30%" valign="top" class="text">
							<div id="ordersByRevenueGrid">
							</div>
						</td>
						<td width="70%" valign="top" nowrap style="padding-left:10px" class="text">
							<center>
								<strong>{{ ByRevenueChartTitle|safe }}</strong>
							</center>
							<div id="flashcontent1" style="width: 100%; clear: both;">
							</div>
							<script type="text/javascript">
								$(document).ready(function() {
									var so = new SWFObject("includes/amcharts/ampie.swf", "ampie", "100%", "600", "8", "#FFFFFF");
									so.addVariable("path", "includes/amcharts/");
									so.addVariable("settings_file", escape("includes/amcharts/ordersbyrevenue.xml"));
									so.addVariable("data_file", escape("index.php?ToDo=ordStatsByRevenueData&from={{ OverviewFromStamp|safe }}&to={{ OverviewToStamp|safe }}&vendorId={{ VendorId|safe }}"));
									so.addVariable("preloader_color", "#000000");
									so.write("flashcontent1");
								});
							</script>
						</td>
					</tr>
				</table>
			</div>
			<div id="div3" style="padding-top:10px; padding-left:10px; clear: both;" class="text">
				<div id="taxByDateGrid">
				</div>
			</div>

			<div id="div4" style="padding-top:10px; padding-left:10px" class="text">
				<div id="ordersByAbandonGrid">
				</div>
			</div>
			</form>
		</td>
	</tr>
	</table>
	</div>

	<script type="text/javascript">

		var ordersPerPage = 20;

		var ordersByDateCurrentPage = 1;
		var ordersByDateFromLink = false;
		var ordersByDateSortField = '';
		var ordersByDateSortOrder = '';

		var ordersByItemsSoldCurrentPage = 1;
		var ordersByItemsSoldFromLink = false;
		var ordersByItemsSoldLoaded = false;
		var ordersByItemsSoldSortField = '';
		var ordersByItemsSoldSortOrder = '';

		var ordersByRevenueLoaded = false;

		var ordersByAbandonCurrentPage = 1;
		var ordersByAbandonFromLink = true;
		var ordersByAbandonLoaded = false;
		var ordersByAbandonSortField = '';
		var ordersByAbandonSortOrder = '';

		var taxPerPage = 20;
		var taxByDateLoaded = false;
		var taxByDateFromLink = false;
		var taxByDateCurrentPage = 1;
		var taxByDateSortField = '';
		var taxByDateSortOrder = '';

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

			$(".ListByCol").hide();
			$("#exportbutton").hide();
			$("#taxTotals").hide();
			$("#abandonedTotals").hide();

			// What should the intro text be?
			switch(T) {
				case 0: {
					$('#introText').html('{% lang 'OrdersByDateIntro' %}');
					break;
				}
				case 1: {
					$('#introText').html('{% lang 'OrdersByItemsSoldIntro' %}');

					if(!ordersByItemsSoldLoaded) {
						LoadOrdersByItemsSoldGrid();
						ordersByItemsSoldLoaded = true;
					}
					break;

				}
				case 2: {
					$('#introText').html('{% lang 'OrdersByRevenueIntro' %}');

					if(!ordersByRevenueLoaded) {
						LoadOrdersByRevenueGrid();
						ordersByRevenueLoaded = true;
					}
					break;
				}
				case 3: {
					$('#introText').html('{% lang 'SalesTaxIntro' %}');
					$(".ListByCol").show();
					$("#exportbutton").show();
					$("#taxTotals").show();

					if(!taxByDateLoaded) {
						LoadTaxByDateGrid();
						taxByDateLoaded = true;
					}

					break;
				}
				case 4: {
					$('#introText').html('{% lang 'OrdersByAbandonIntro' %}');
					$('#exportbutton').show();
					$("#abandonedTotals").show();

					if(!ordersByAbandonLoaded) {
						LoadOrdersByAbandonGrid();
						ordersByAbandonLoaded = true;
					}
					break;

				}
			}
		}

		function ChangeOrdersByDatePerPage(OrdersPerPage) {
			// Change how many orders are shown per page
			ordersPerPage = OrdersPerPage;
			ordersByDateCurrentPage = 1;
			ordersByDateFromLink = true;
			LoadOrdersByDateGrid();
		}

		function ChangeOrdersByDatePage(Page) {
			// Change which page of orders we're viewing
			ordersByDateCurrentPage = Page;
			ordersByDateFromLink = true;
			LoadOrdersByDateGrid();
		}

		function SortOrdersByDate(field, order) {
			ordersByDateSortField = field;
			ordersByDateSortOrder = order;
			ordersByDateFromLink = true;
			LoadOrdersByDateGrid();
		}

		function LoadOrdersByDateGrid() {
			// Load the orders and jump to a specific page
			jQuery.ajax({url: 'index.php?ToDo=ordStatsByDateGrid&FromLink='+ordersByDateFromLink+'&vendorId={{ VendorId|safe }}&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+ordersByDateCurrentPage+'&Show='+ordersPerPage+'&SortBy='+ordersByDateSortField+'&SortOrder='+ordersByDateSortOrder,
					success: function(data) {
						$('#ordersByDateGrid').html(data);
					}
				}
			);
		}

		function LoadOrdersByItemsSoldGrid() {
			// Load orders by items sold
			jQuery.ajax({url: 'index.php?ToDo=ordStatsByItemsSoldGrid&FromLink='+ordersByItemsSoldFromLink+'&vendorId={{ VendorId|safe }}&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+ordersByItemsSoldCurrentPage+'&Show='+ordersPerPage+'&SortBy='+ordersByItemsSoldSortField+'&SortOrder='+ordersByItemsSoldSortOrder,
					success: function(data) {
						$('#ordersByItemsSoldGrid').html(data);
					}
				}
			);
		}

		function LoadOrdersByRevenueGrid() {
			// Load orders by revenue
			jQuery.ajax({url: 'index.php?ToDo=ordStatsByRevenueGrid&vendorId={{ VendorId|safe }}&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}',
					success: function(data) {
						$('#ordersByRevenueGrid').html(data);
					}
				}
			);
		}

		function ChangeOrdersByItemsSoldPerPage(OrdersPerPage) {
			// Change how many orders are shown per page
			ordersPerPage = OrdersPerPage;
			ordersByItemsSoldCurrentPage = 1;
			ordersByItemsSoldFromLink = true;
			LoadOrdersByItemsSoldGrid();
		}

		function ChangeOrdersByItemsSoldPage(Page) {
			// Change which page of orders we're viewing
			ordersByItemsSoldCurrentPage = Page;
			ordersByItemsSoldFromLink = true;
			LoadOrdersByItemsSoldGrid();
		}

		function SortOrdersByItemsSold(field, order) {
			ordersByItemsSoldSortField = field;
			ordersByItemsSoldSortOrder = order;
			ordersByItemsSoldFromLink = true;
			LoadOrdersByItemsSoldGrid();
		}

		function ChangeOrdersByAbandonPerPage(PerPage) {
			// Change how many abandon records are shown per page
			ordersPerPage = PerPage;
			ordersByAbandonCurrentPage = 1;
			ordersByAbandonFromLink = true;
			LoadOrdersByAbandonGrid();
		}

		function ChangeOrdersByAbandonPage(Page) {
			// Change which page of abandon we're viewing
			ordersByAbandonCurrentPage = Page;
			ordersByAbandonFromLink = true;
			LoadOrdersByAbandonGrid();
		}

		function LoadOrdersByAbandonGrid() {
			// Load orders by items sold
			jQuery.ajax({url: 'index.php?ToDo=ordStatsByAbandonGrid&FromLink='+ordersByAbandonFromLink+'&vendorId={{ VendorId|safe }}&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+ordersByAbandonCurrentPage+'&Show='+ordersPerPage+'&SortBy='+ordersByAbandonSortField+'&SortOrder='+ordersByAbandonSortOrder,
					success: function(data) {
						$('#ordersByAbandonGrid').html(data);
					}
				}
			);
		}

		function SortOrdersByAbandon(field, order) {
			ordersByAbandonSortField = field;
			ordersByAbandonSortOrder = order;
			ordersByAbandonFromLink = true;
			LoadOrdersByAbandonGrid();
		}

		// ======================

		function ChangeTaxByDatePerPage(PerPage) {
			// Change how many tax records are shown per page
			taxPerPage = PerPage;
			taxByDateCurrentPage = 1;
			taxByDateFromLink = true;
			LoadTaxByDateGrid();
		}

		function ChangeTaxByDatePage(Page) {
			// Change which page of tax we're viewing
			taxByDateCurrentPage = Page;
			taxByDateFromLink = true;
			LoadTaxByDateGrid();
		}

		function LoadTaxByDateGrid() {
			// Load the orders and jump to a specific page
			jQuery.ajax({url: 'index.php?ToDo=taxStatsByDateGrid&FromLink='+taxByDateFromLink+'&vendorId={{ VendorId|safe }}&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+taxByDateCurrentPage+'&Show='+taxPerPage+'&TaxListBy={{ TaxListBy|safe }}&SortBy='+taxByDateSortField+'&SortOrder='+taxByDateSortOrder,
					 success: function(data) {
						$('#taxByDateGrid').html(data);
					 }
				}
			);
		}

		function SortTaxStats(field, order)	{
			taxByDateCurrentPage = 1;
			taxByDateSortField = field;
			taxByDateSortOrder = order;
			taxByDateFromLink = true;
			LoadTaxByDateGrid();
		}

		$("#exportbutton input:button").click(function() {
			var currentTab = $("#currentTab").val();
			switch(currentTab) {
				case '3': {
					document.location = 'index.php?ToDo=startExport&t=salestax&vendorId={{ VendorId|safe }}&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&TaxListBy={{ TaxListBy|safe }}';
					break;
				}
				case '4': {
					document.location = 'index.php?ToDo=startExport&t=abandonorder&vendorId={{ VendorId|safe }}&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}';
					break;
				}
			}
		});

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

			// Load the orders table for the selected date range
			LoadOrdersByDateGrid();
		});

	</script>
