
	<form action="index.php?ToDo=viewStats" name="frmStats" id="frmStats" method="post">
	<input id="currentTab" name="currentTab" value="0" type="hidden">
	<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">{% lang 'StoreOverview' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'StoreOverviewIntro' %}</p>
			{{ Message|safe }}
		</td>
	</tr>
	<tr>
		<td>
			<ul id="tabnav">
				<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'StoreSnapshot' %}</a></li>
				<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'Top20Customers' %}</a></li>
				<li><a href="#" id="tab2" onclick="ShowTab(2)">{% lang 'BestSellingProducts' %}</a></li>
				<li><a href="#" id="tab3" onclick="ShowTab(3)">{% lang 'OrderLocations' %}</a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td>
			<br />
			<div id="introText" style="padding:0px 0px 5px 10px" class="Text"></div>
			<div style="padding:5px 0px 5px 10px" class="Text">
				<form name="customDateForm" method="post" action="index.php?Page=stats&Action=ProcessCalendar&SubAction=List&NextAction=ViewSummary&list=11" style="margin: 0px;">
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
				</form>
			</div>
			<div id="div0" style="padding-top:10px">
				<table width="100%" border="0" class="text" style="padding-left:10px">
					<tr>
						<td valign=top width="250" nowrap>
							<div class="MidHeading" style="width:100%"><img src="images/order.gif" align="absMiddle">&nbsp;Order Summary</div>
							<ul class="Text">
								<li>{% lang 'TotalRevenue' %}: {{ OverviewOrderTotal|safe }}</li>
								<li>{% lang 'UniqueVisitors' %}: {{ OverviewUniqueVisitors|safe }}</li>
								<li>{% lang 'CompletedOrders' %}: {{ OverviewOrderCount|safe }}</li>
								<li>{% lang 'ConversionRate' %}: {{ OverviewConversionRate|safe }}</li>
							</ul>
						</td>
						<td width="100%" align="center">
							<strong>{{ ChartTitle|safe }} <span style="display:{{ HideNoAdvancedStatsMessage|safe }}; color:#CACACA"><br />({% lang 'NoOrderData2Days' %})</span></strong>
							<div id="flashcontent">

							</div>
							<script type="text/javascript" src="includes/amcharts/swfobject.js?{{ JSCacheToken }}"></script>
							<script type="text/javascript">
								$(document).ready(function() {
									var so = new SWFObject("{{ ShopPath|safe }}/admin/includes/amcharts/amline/amline.swf", "amline", "98%", "430", "8", "#FFFFFF");
									so.addVariable("path", "{{ ShopPath|safe }}/admin/includes/amcharts/");
									so.addVariable("wmode", "transparent");
									so.addVariable("settings_file", escape("{{ ShopPath|safe }}/admin/includes/amcharts/overviewgeneral.xml"));
									so.addVariable("data_file", escape("{{ ShopPath|safe }}/admin/index.php?ToDo=overviewStatsData&from={{ OverviewFromStamp|safe }}&to={{ OverviewToStamp|safe }}"));
									so.addVariable("preloader_color", "#000000");
									so.write("flashcontent");
								});
							</script>
						</td>
					</tr>
				</table>
			</div>
			<div id="div1" style="padding-top:10px; padding-left:10px">
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="text">
					<tr>
						<td width="40%" valign="top">
							{{ TopCustomersGrid|safe }}
						</td>
						<td width="60%" valign="top">

							<div id="flashcontent1" style="width: 100%; border: solid 0px black; display:{{ HideTop20CustomersChart|safe }}">

							</div>
							<script type="text/javascript" src="includes/amcharts/swfobject.js?{{ JSCacheToken }}"></script>
							<script type="text/javascript">
								$(document).ready(function() {
									var so = new SWFObject("{{ ShopPath|safe }}/admin/includes/amcharts/ampie.swf", "ampie", "100%", "400", "8", "#FFFFFF");
									so.addVariable("path", "{{ ShopPath|safe }}/admin/includes/amcharts/");
									so.addVariable("settings_file", escape("{{ ShopPath|safe }}/admin/includes/amcharts/top20customers.xml"))
									so.addVariable("data_file", escape("{{ ShopPath|safe }}/admin/index.php?ToDo=overviewStatsTop20CustData&from={{ OverviewFromStamp|safe }}&to={{ OverviewToStamp|safe }}"));
									so.addVariable("preloader_color", "#999999");
									so.write("flashcontent1");
								});
							</script>
						</td>
					</tr>
				</table>
			</div>
			<div id="div2" style="padding-top:10px; padidng-left:10px">
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="text" style="padding-left:10px">
					<tr>
						<td width="40%" valign="top">
							{{ TopProductsGrid|safe }}
						</td>
						<td width="60%" valign="top">

							<div id="flashcontent2" style="width: 100%; border: solid 0px black; display:{{ HideTop20ProductsChart|safe }}">

							</div>
							<script type="text/javascript">
								//<![CDATA[
								$(document).ready(function() {
									var so = new SWFObject("{{ ShopPath|safe }}/admin/includes/amcharts/ampie.swf", "ampie", "100%", "400", "8", "#FFFFFF");
									so.addVariable("path", "{{ ShopPath|safe }}/admin/includes/amcharts/");
									so.addVariable("settings_file", escape("{{ ShopPath|safe }}/admin/includes/amcharts/top20products.xml"))
									so.addVariable("data_file", escape("{{ ShopPath|safe }}/admin/index.php?ToDo=overviewStatsTop20Prods&from={{ OverviewFromStamp|safe }}&to={{ OverviewToStamp|safe }}"));
									so.addVariable("preloader_color", "#999999");
									so.write("flashcontent2");
								});
								//]]>
							</script>
						</td>
					</tr>
				</table>
			</div>

			<div id="div3" style="padding-top:10px; padding-left:10px">
				<iframe id="orderMap" style="width:100%; height:600px; border:0px" frameborder="none"></iframe>
			</div>
			</form>
		</td>
	</tr>
	</table>
	</div>

	<script type="text/javascript">

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
				$('#introText').html('{{ IntroText0|safe }}');
				break;

			}
			case 1: {
				$('#introText').html('{{ IntroText1|safe }}');
				break;

			}
			case 2: {
				$('#introText').html('{{ IntroText2|safe }}');
				break;

			}
			case 3: {
				$('#introText').html('{{ IntroText3|safe }}');
				if(g('orderMap').src == '' && "{{ GoogleMapsAPIKey|safe }}" != "") {
					g('orderMap').src = "index.php?ToDo=overviewStatsOrdLocationChart&from={{ FromStamp|safe }}&to={{ ToStamp|safe }}";
				}
				break;
			}
		}
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

	</script>
