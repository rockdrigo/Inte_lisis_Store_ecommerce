
	<form action="index.php?ToDo=viewProdStats" name="frmStats" id="frmStats" method="post">
	<input id="currentTab" name="currentTab" value="0" type="hidden">
	<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">{% lang 'ProductStatistics' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'ProductStatsIntro' %}</p>
			{{ Message|safe }}
		</td>
	</tr>
	<tr>
		<td>
			<ul id="tabnav">
				<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'ProductsByNumberSold' %}</a></li>
				<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'MostViewedProducts' %}</a></li>
				<li style="display: {{ HideInventoryTab|safe }};"><a href="#" id="tab2" onclick="ShowTab(2)">{% lang 'InventoryReport' %}</a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td>
			<br />
			<div id="introText" style="padding:0px 0px 5px 10px" class="Text"></div>
			<div id="dateBlock" style="padding:5px 0px 5px 10px" class="Text FloatLeft">
				<table border=0 cellspacing=0 cellpadding=0>
					<tr>
						<td style="background: #eee; padding: 3px 5px;" width="1" class="dateField">
							<img src="images/dateicon.gif" />
						</td>
						<td style="background: #eee;" class="dateField">Date Range:</td>
						<td style="background: #eee; padding: 3px 5px;" width="1" class="dateField">
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
						<td style="background: #eee;" class="dateField">
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
						<td style="background: #eee; padding: 3px 5px; {{ HideVendorList|safe }}" width="1">
							<img src="images/vendor.gif" />
						</td>
						<td style="background: #eee; {{ HideVendorList|safe }}">{% lang 'Vendor' %}:</td>
						<td class="VendorSelect" style="background: #eee; padding: 3px 5px; {{ HideVendorList|safe }}" width="1">
							<select name="vendorId">
								{{ VendorSelect|safe }}
							</select>
						</td>
						<td class="GoButton" style="background: #eee; padding: 3px 5px;"><input type="submit" value="Go" class="Text" /></td>
						<td>
							<div id="ShowHideVariationsButtonContainer">
								<input id="ShowHideVariationsButton" type="button" class="Text" onclick="ChangeProductsByInventoryVariations();return false;" value="{% lang 'HideVariations' %}" />
							</div>
						</td>
					</tr>
				</table>
			</div>
			<div id="div0" style="padding-top:10px; padding-left:10px" class="text">
				<div id="productsByNumSoldGrid">
				</div>
			</div>
			<div id="div1" style="padding-top:10px; padding-left:10px" class="text">
				<div id="productsByNumViewsGrid">
				</div>
			</div>
			<div id="div2" style="padding-top:10px; padding-left:10px; display: {{ HideInventoryTab|safe }};" class="text">
				<div id="productsByInventoryGrid">
				</div>
			</div>
			</form>
		</td>
	</tr>
	</table>
	</div>

	<script type="text/javascript">

		var productsPerPage = 20;

		var productsByNumSoldCurrentPage = 1;
		var productsByNumSoldLoaded = false;
		var productsByNumSoldSortField = '';
		var productsByNumSoldSortOrder = '';

		var productsByNumViewsCurrentPage = 1;
		var productsByNumViewsLoaded = false;
		var productsByNumViewsSortField = '';
		var productsByNumViewsSortOrder = '';

		var productsByInventoryVariations = 1;
		var productsByInventoryCurrentPage = 1;
		var productsByInventoryLoaded = false;
		var productsByInventorySortField = '';
		var productsByInventorySortOrder = '';

		var showProductInventory = '{{ ShowInventoryGrid|safe }}';

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
					$('#introText').html('{% lang 'ProductsByNumSoldIntro' %}');
					$('#dateBlock .dateField').show();
					$('#dateBlock .GoButton').show();
					$('#ShowHideVariationsButtonContainer').hide();
					if(!productsByNumSoldLoaded) {
						LoadProductsByNumSoldGrid();
						productsByNumSoldLoaded = true;
					}
					break;
				}
				case 1: {
					$('#introText').html('{% lang 'ProductsByNumViewsIntro' %}');
					$('#dateBlock .dateField').hide();
					$('#ShowHideVariationsButtonContainer').hide();
					if($('#dateBlock .VendorSelect').css('display') != 'none') {
						$('#dateBlock .GoButton').show();
					}
					else {
						$('#dateBlock .GoButton').hide();
					}
					if(!productsByNumViewsLoaded) {
						LoadProductsByNumViewsGrid();
						productsByNumViewsLoaded = true;
					}
					break;
				}
				case 2: {
					if (showProductInventory !== '1') {
						break;
					}
					$('#ShowHideVariationsButtonContainer').show();
					$('#introText').html('{% lang 'ProductsByInventoryIntro' %}');
					$('#dateBlock .dateField').hide();
					if($('#dateBlock .VendorSelect').css('display') != 'none') {
						$('#dateBlock .GoButton').show();
					}
					else {
						$('#dateBlock .GoButton').hide();
					}
					if(!productsByInventoryLoaded) {
						LoadProductsByInventoryGrid();
						productsByInventoryLoaded = true;
					}
					break;
				}
			}
		}

		function ChangeProductsByNumSoldPerPage(ProductsPerPage) {
			// Change how many products are shown per page
			productsPerPage = ProductsPerPage;
			productsByNumSoldCurrentPage = 1;
			LoadProductsByNumSoldGrid();
		}

		function ChangeProductsByNumSoldPage(Page) {
			// Change which page of orders we're viewing
			productsByNumSoldCurrentPage = Page;
			LoadProductsByNumSoldGrid();
		}

		function SortProductsByNumSold(field, order) {
			productsByNumSoldSortField = field;
			productsByNumSoldSortOrder = order;
			LoadProductsByNumSoldGrid();
		}

		function ChangeProductsByNumViewsPerPage(ProductsPerPage) {
			// Change how many products are shown per page
			productsPerPage = ProductsPerPage;
			productsByNumViewsCurrentPage = 1;
			LoadProductsByNumViewsGrid();
		}

		function ChangeProductsByNumViewsPage(Page) {
			// Change which page of orders we're viewing
			productsByNumViewsCurrentPage = Page;
			LoadProductsByNumViewsGrid();
		}

		function SortProductsByNumViews(field, order) {
			productsByNumViewsSortField = field;
			productsByNumViewsSortOrder = order;
			LoadProductsByNumViewsGrid();
		}

		function ChangeProductsByInventoryPerPage(ProductsPerPage) {
			// Change how many products are shown per page
			productsPerPage = ProductsPerPage;
			productsByInventoryCurrentPage = 1;
			LoadProductsByInventoryGrid();
		}

		function ChangeProductsByInventoryVariations() {
			productsByInventoryVariations = productsByInventoryVariations ? 0 : 1;

			var buttonText = '{% lang 'ShowVariations' %}';
			if (productsByInventoryVariations) {
				buttonText = '{% lang 'HideVariations' %}';
			}
			$('#ShowHideVariationsButton').val(buttonText);

			productsByInventoryCurrentPage = 1;
			LoadProductsByInventoryGrid();
		}

		function ChangeProductsByInventoryPage(Page) {
			// Change which page of orders we're viewing
			productsByInventoryCurrentPage = Page;
			LoadProductsByInventoryGrid();
		}

		function SortProductsByInventory(field, order) {
			productsByInventorySortField = field;
			productsByInventorySortOrder = order;
			LoadProductsByInventoryGrid();
		}

		function LoadProductsByNumSoldGrid() {
			jQuery.ajax({url: 'index.php?ToDo=prodStatsByNumSoldGrid&vendorId={{ VendorId|safe }}&From={{ FromStamp|safe }}&To={{ ToStamp|safe }}&Page='+productsByNumSoldCurrentPage+'&Show='+productsPerPage+'&SortBy='+productsByNumSoldSortField+'&SortOrder='+productsByNumSoldSortOrder,
					 success: function(data) {
					$('#productsByNumSoldGrid').html(data);
					 }
				}
			);
		}

		function LoadProductsByNumViewsGrid() {
			jQuery.ajax({url: 'index.php?ToDo=prodStatsByNumViewsGrid&vendorId={{ VendorId|safe }}&Page='+productsByNumViewsCurrentPage+'&Show='+productsPerPage+'&SortBy='+productsByNumViewsSortField+'&SortOrder='+productsByNumViewsSortOrder,
					 success: function(data) {
					$('#productsByNumViewsGrid').html(data);
					 }
				}
			);
		}

		function LoadProductsByInventoryGrid() {
			if (showProductInventory !== '1') {
				return;
			}

			jQuery.ajax({url: 'index.php?ToDo=prodStatsByInventoryGrid&vendorId={{ VendorId|safe }}&Variations='+productsByInventoryVariations+'&Page='+productsByInventoryCurrentPage+'&Show='+productsPerPage+'&SortBy='+productsByInventorySortField+'&SortOrder='+productsByInventorySortOrder,
					 success: function(data) {
					$('#productsByInventoryGrid').html(data);
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

	</script>
