
	<form action="index.php" id="frmSearch" method="get" onsubmit="return ValidateForm(CheckViewForm)">
	<input type="hidden" name="ToDo" value="searchOrdersRedirect" />
	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{% lang 'CreateNewOrdersView' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'OrderViewIntro' %}</p>
			{{ Message|safe }}
			<p><input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></p>
		</td>
	  </tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'ViewDetails' %}</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'NameThisView' %}:
					</td>
					<td>
						<input type="text" id="viewName" name="viewName" class="Field250">
						<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'NameThisView' %}', '{% lang 'NameThisOrdersViewHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d2"></div>
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
				  <td class="Heading2" colspan=2>{% lang 'AdvancedSearch' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SearchKeywords' %}:
					</td>
					<td>
						<input type="text" id="searchQuery" name="searchQuery" class="Field250">
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsOrderHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d1"></div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'OrderStatus1' %}:
					</td>
					<td>
						<select id="orderStatus" name="orderStatus" class="Field250">
							<option value="">{% lang 'ChooseAnOrderStatus' %}</option>
							{{ OrderStatusOptions|safe }}
						</select>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'PaymentMethod' %}:
					</td>
					<td>
						<select id="paymentMethod" name="paymentMethod" class="Field250">
							<option value="">{% lang 'ChooseAPaymentMethod' %}</option>
							{{ OrderPaymentOptions|safe }}
						</select>
					</td>
				</tr>
				{% if OrderTypeOptions %}
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'OrderType' %}:
					</td>
					<td>
						<select id="ebayOrderId" name="ebayOrderId" class="Field250">
							<option value="">{% lang 'ChooseAnOrderType' %}</option>
							{{ OrderTypeOptions|safe }}
						</select>
					</td>
				</tr>
				{% endif %}
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'ShippingMethod' %}:
					</td>
					<td>
						<select id="shippingMethod" name="shippingMethod" class="Field250">
							<option value="">{% lang 'ChooseAShippingMethod' %}</option>
							{{ OrderShippingOptions|safe }}
						</select>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'CouponCode' %}:
					</td>
					<td>
						<input type="text" name="couponCode" class="Field250" />
						<img onmouseout="HideHelp('couponcode');" onmouseover="ShowHelp('couponcode', '{% lang 'CouponCode' %}', '{% lang 'OrderCouponCodeHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="couponcode"></div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'PreOrders' %}:
					</td>
					<td>
						<input type="checkbox" name="preorders[]" value="0" id="preorders_0" checked="checked" /> <label for="preorders_0">{% lang 'Includeordersthatdonotcontainpreorderproducts' %}</label><br />
						<input type="checkbox" name="preorders[]" value="1" id="preorders_1" checked="checked" /> <label for="preorders_1">{% lang 'Includeordersthatcontainpreorderproducts' %}</label><br />
					</td>
				</tr>
				{% if ISC_CFG.DeletedOrdersAction == 'delete' %}
				  <tr>
					  <td class="FieldLabel">
						  &nbsp;&nbsp;&nbsp;{% lang 'DeletedOrders' %}:
					  </td>
					  <td>
						  <label><input name="searchDeletedOrders" value="no" type="radio" checked="checked" />{% lang 'SearchDeletedOrders_No' %}</label><br />
						  <label><input name="searchDeletedOrders" value="both" type="radio" />{% lang 'SearchDeletedOrders_Both' %}</label><br />
						  <label><input name="searchDeletedOrders" value="only" type="radio" />{% lang 'SearchDeletedOrders_Only' %}</label>
					  </td>
				  </tr>
				{% endif %}
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
						&nbsp;&nbsp;&nbsp;{% lang 'OrderID' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} &nbsp;&nbsp;<input type="text" id="orderFrom" name="orderFrom" class="Field50"> {% lang 'SearchTo' %}
						&nbsp;&nbsp;<input type="text" id="orderTo" name="orderTo" class="Field50">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'OrderTotal' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} {{ CurrencyTokenLeft|safe }}<input type="text" id="totalFrom" name="totalFrom" class="Field50"> {{ CurrencyTokenRight|safe }} {% lang 'SearchTo' %}
						{{ CurrencyTokenLeft|safe }}<input type="text" id="totalTo" name="totalTo" class="Field50"> {{ CurrencyTokenRight|safe }}
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
						&nbsp;&nbsp;&nbsp;{% lang 'DateRange' %}:
					</td>
					<td>
						<select name="dateRange" id="dateRange" onchange="ToggleRange()" class="Field250">
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
						<div id="dateRangeCustom" style="margin-left: 30px; margin-top: 10px;">
							{% lang 'SearchFrom' %} <input class="plain" name="fromDate" id="dc1" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fStartPop(g('dc1'),g('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
							{% lang 'SearchTo' %} <input class="plain" name="toDate" id="dc2" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fEndPop(g('dc1'),g('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
						</div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SearchByDateType' %}:
					</td>
					<td>
						<label><input name="SearchByDate" value="0" type="radio" checked="checked" />{% lang 'SearchByOrderDate' %}</label><br />
						<label><input name="SearchByDate" value="1" type="radio" />{% lang 'SearchByEventDate' %}</label><br />
						<label><input name="SearchByDate" value="2" type="radio" />{% lang 'SearchByOrderAndEventDate' %}</label>
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
				  <td class="Heading2" colspan=2>{% lang 'SortOrder' %}</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SortOrder' %}:
					</td>
					<td>
						<select name="sortField" class="Field120">
							<option value="orderid">{% lang 'OrderId' %}</option>
							<option value="custname">{% lang 'Customer' %}</option>
							<option value="orddate">{% lang 'Date' %}</option>
							<option value="ordstats">{% lang 'Status' %}</option>
							<option value="newmessages">{% lang 'NewMessages' %}</option>
							<option value="total_inc_tax">{% lang 'Total' %}</option>
						</select>
						in&nbsp;
						<select name="sortOrder" class="Field110">
						<option value="asc">{% lang 'AscendingOrder' %}</option>
						<option value="desc">{% lang 'DescendingOrder' %}</option>
					</td>
				</tr>
				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap"><input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
				<tr><td class="Gap" colspan="2"></td></tr>
				<tr><td class="Sep" colspan="2"></td></tr>

			 </table>
			</td>
		</tr>
	</table>
	<iframe width=132 height=142 name="gToday:contrast:agenda.js" id="gToday:contrast:agenda.js" src="calendar/ipopeng.htm" scrolling="no" frameborder="0" style="visibility:visible; z-index:999; position:absolute; left:-500px; top:0px;"></iframe>
	</div>
	</form>

	<script type="text/javascript">

		function ToggleRange()
		{
			var range = g('dateRange');
			if(range.options[range.selectedIndex].value == "custom")
			{
				g('dateRangeCustom').style.display = '';
			}
			else
			{
				g('dateRangeCustom').style.display = 'none';
			}
		}

		ToggleRange();

		function ConfirmCancel() {
			if(confirm("{% lang 'ConfirmCancelSearch' %}")) {
				history.go(-1);
			}
		}

		function CheckViewForm() {
			var viewName = g("viewName");
			var orderFrom = g("orderFrom");
			var orderTo = g("orderTo");
			var totalFrom = g("totalFrom");
			var totalTo = g("totalTo");
			var fromDate = g("dc1");
			var toDate = g("dc2");

			if(viewName.value == "") {
				alert("{% lang 'EnterViewName' %}");
				viewName.focus();
				return false;
			}

			if(orderFrom.value != "" && isNaN(orderFrom.value)) {
				alert("{% lang 'SearchEnterValidOrderId' %}");
				orderFrom.focus();
				orderFrom.select();
				return false;
			}

			if(orderTo.value != "" && isNaN(orderTo.value)) {
				alert("{% lang 'SearchEnterValidOrderId' %}");
				orderTo.focus();
				orderTo.select();
				return false;
			}

			if(totalFrom.value != "" && isNaN(priceFormat(totalFrom.value))) {
				alert("{% lang 'SearchEnterValidTotal' %}");
				totalFrom.focus();
				totalFrom.select();
				return false;
			}

			if(totalTo.value != "" && isNaN(priceFormat(totalTo.value))) {
				alert("{% lang 'SearchEnterValidTotal' %}");
				totalTo.focus();
				totalTo.select();
				return false;
			}

			if(fromDate.value != "" && fromDate.value == toDate.value) {
				alert("{% lang 'SearchChooseDifferentDates' %}");
				return false;
			}

			return true;
		}

	</script>
