<form enctype="multipart/form-data" action="index.php" id="frmSearch" method="get" onsubmit="return ValidateForm(CheckSearchForm)">
	<input type="hidden" name="ToDo" value="searchShipmentsRedirect" />
	<div class="BodyContainer">
		<table class="OuterPanel">
		  <tr>
			<td class="Heading1" id="tdHeading">{% lang 'SearchShipments' %}</td>
			</tr>
			<tr>
			<td class="Intro">
				<p>{% lang 'SearchShipmentsIntro' %}</p>
				{{ Message|safe }}
				<p><input type="submit" name="SubmitButton1" value="{% lang 'Search' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></p>
			</td>
		  </tr>
			<tr>
				<td>
				  <table class="Panel">
					<tr>
					  <td class="Heading2" colspan=2>{% lang 'AdvancedSearch' %}</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'SearchKeywords' %}:
						</td>
						<td>
							<input type="text" id="searchQuery" name="searchQuery" class="Field250">
							<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsShipmentHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="d1"></div>
						</td>
					</tr>
					<tr><td class="Gap" colspan="2"></td></tr>
				 </table>
				</td>
			</tr>
			<tr>
				<td>
				  <table class="Panel">
					<tr>
					  <td class="Heading2" colspan=2>{% lang 'SearchByRange' %}</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'ShipmentId' %}:
						</td>
						<td>
							{% lang 'SearchFrom' %} &nbsp;&nbsp;
							<input type="text" id="shipmentFrom" name="shipmentFrom" class="Field50" />
							{% lang 'SearchTo' %} &nbsp;&nbsp;
							<input type="text" id="shipmentTo" name="shipmentTo" class="Field50" />
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'ShipmentOrderId' %}:
						</td>
						<td>
							{% lang 'SearchFrom' %} &nbsp;&nbsp;
							<input type="text" id="orderFrom" name="orderFrom" class="Field50" />
							{% lang 'SearchTo' %} &nbsp;&nbsp;
							<input type="text" id="orderTo" name="orderTo" class="Field50" />
						</td>
					</tr>
					<tr><td class="Gap" colspan="2"></td></tr>
				 </table>
				</td>
			</tr>
			<tr>
				<td>
				  <table class="Panel">
					<tr>
					  <td class="Heading2" colspan=2>{% lang 'SearchByDate' %}</td>
					</tr>
					<tr><td class="Gap"></td></tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'ShipDateRange' %}:
						</td>
						<td>
							<select name="shipdateRange" id="shipdateRange" onchange="ToggleRange($(this).val())" class="Field250">
								<option value="">{% lang 'ChooseShipDate' %}</option>
								<option value="today">{% lang 'Today' %}</option>
								<option value="yesterday">{% lang 'Yesterday' %}</option>
								<option value="day">{% lang 'Last24Hours' %}</option>
								<option value="week">{% lang 'Last7Days' %}</option>
								<option value="month">{% lang 'Last30Days' %}</option>
								<option value="this_month">{% lang 'ThisMonth' %}</option>
								<option value="this_year">{% lang 'ThisYear' %}</option>
								<option value="custom">{% lang 'CustomPeriod' %}</option>
							</select>
							<div id="shipdateRangeCustom" style="margin-left: 30px; margin-top: 10px;">
								{% lang 'SearchFrom' %}
								<input class="plain" name="shipdateFrom" id="dc1" size="12" onfocus="this.blur()" readonly="readonly" />
								<a href="#" onclick="if(self.gfPop)gfPop.fStartPop(document.getElementById('dc1'),document.getElementById('dc2'));return false;">
									<img name="popcal" align="absmiddle" src="images/calbtn.gif" border="0" alt="" />
								</a>
								{% lang 'SearchTo' %}
								<input class="plain" name="shipdateTo" id="dc2" size="12" onfocus="this.blur()" readonly="readonly" />
								<a href="#" onclick="if(self.gfPop)gfPop.fEndPop(document.getElementById('dc1'),document.getElementById('dc2'));return false;">
									<img name="popcal" align="absmiddle" src="images/calbtn.gif" border="0" alt="" />
								</a>
							</div>
						</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'OrderDateRange' %}:
						</td>
						<td>
							<select name="shiporderdateRange" id="shiporderdateRange" onchange="ToggleOrderRange($(this).val())" class="Field250">
								<option value="">{% lang 'ChooseOrderDate' %}</option>
								<option value="today">{% lang 'Today' %}</option>
								<option value="yesterday">{% lang 'Yesterday' %}</option>
								<option value="day">{% lang 'Last24Hours' %}</option>
								<option value="week">{% lang 'Last7Days' %}</option>
								<option value="month">{% lang 'Last30Days' %}</option>
								<option value="this_month">{% lang 'ThisMonth' %}</option>
								<option value="this_year">{% lang 'ThisYear' %}</option>
								<option value="custom">{% lang 'CustomPeriod' %}</option>
							</select>
							<div id="shiporderdateRangeCustom" style="margin-left: 30px; margin-top: 10px;">
								{% lang 'SearchFrom' %}
								<input class="plain" name="shiporderdateFrom" id="dc3" size="12" onfocus="this.blur()" readonly="readonly" />
								<a href="#" onclick="if(self.gfPop)gfPop.fStartPop(document.getElementById('dc3'),document.getElementById('dc4'));return false;">
									<img name="popcal" align="absmiddle" src="images/calbtn.gif" border="0" alt="" />
								</a>
								{% lang 'SearchTo' %}
								<input class="plain" name="shiporderdateTo" id="dc4" size="12" onfocus="this.blur()" readonly="readonly" />
								<a href="#" onclick="if(self.gfPop)gfPop.fEndPop(document.getElementById('dc3'),document.getElementById('dc4'));return false;">
									<img name="popcal" align="absmiddle" src="images/calbtn.gif" border="0" alt="" />
								</a>
							</div>
						</td>
					</tr>
					<tr><td class="Gap" colspan="2"></td></tr>
				 </table>
				</td>
			</tr>
			<tr>
				<td>
				  <table class="Panel">
					<tr>
					  <td class="Heading2" colspan="2">{% lang 'SortOrder' %}</td>
					</tr>
					<tr><td class="Gap"></td></tr>
					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'SortOrder' %}:
						</td>
						<td>
							<select name="sortField" class="Field120">
								<option value="shipmentid">{% lang 'ShipmentId' %}</option>
								<option value="shipdate">{% lang 'DateShipped' %}</option>
								<option value="shiporderid">{% lang 'ShipmentOrderId' %}</option>
								<option value="shiporderdate">{% lang 'ShipmentOrderDate' %}</option>
								<option value="shipfllname">{% lang 'ShippedTo' %}</option>
							</select>
							in&nbsp;
							<select name="sortOrder" class="Field110">
							<option value="asc">{% lang 'AscendingOrder' %}</option>
							<option value="desc">{% lang 'DescendingOrder' %}</option>
						</td>
					</tr>
					<tr>
						<td class="Gap">&nbsp;</td>
						<td class="Gap">
							<input type="submit" value="{% lang 'Search' %}" class="FormButton" />&nbsp;
							<input type="button" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
						</td>
					</tr>
					<tr><td class="Gap" colspan="2"></td></tr>
				 </table>
				</td>
			</tr>
		</table>
		<iframe width=132 height=142 name="gToday:contrast:agenda.js" id="gToday:contrast:agenda.js" src="calendar/ipopeng.htm" scrolling="no" frameborder="0" style="visibility:visible; z-index:999; position:absolute; left:-500px; top:0px;"></iframe>
	</div>
</form>
<script type="text/javascript">

	function ToggleRange(value)
	{
		if(value == 'custom') {
			$('#shipdateRangeCustom').show();
		}
		else {
			$('#shipdateRangeCustom').hide();
		}
	}

	function ToggleOrderRange(value)
	{
		if(value == 'custom') {
			$('#shiporderdateRangeCustom').show();
		}
		else {
			$('#shiporderdateRangeCustom').hide();
		}
	}

	$(document).ready(function() {
		ToggleRange($('#shipdateRange').val());
		ToggleOrderRange($('#shiporderdateRange').val());
	});

	function ConfirmCancel() {
		if(confirm("{% lang 'ConfirmCancelSearch' %}"))
			window.location = "index.php?ToDo=viewShipments";
	}

	function CheckSearchForm() {
		if($('#shipmentFrom').val() != '' && isNaN($('#shipmentFrom').val())) {
			alert('{% lang 'SearchEnterValidShipmentId' %}');
			$('#shipmentFrom').focus();
			$('#shipmentFrom').select();
			return false;
		}

		if($('#shipmentTo').val() != '' && isNaN($('#shipmentTo').val())) {
			alert('{% lang 'SearchEnterValidShipmentId' %}');
			$('#shipmentTo').focus();
			$('#shipmentTo').select();
			return false;
		}

		if($('#orderFrom').val() != '' && isNaN($('#orderFrom').val())) {
			alert('{% lang 'SearchEnterValidOrderId' %}');
			$('#orderFrom').focus();
			$('#orderFrom').select();
			return false;
		}

		if($('#orderTo').val() != '' && isNaN($('#orderTo').val())) {
			alert('{% lang 'SearchEnterValidOrderId' %}');
			$('#orderTo').focus();
			$('#orderTo').select();
			return false;
		}

		return true;
	}
</script>