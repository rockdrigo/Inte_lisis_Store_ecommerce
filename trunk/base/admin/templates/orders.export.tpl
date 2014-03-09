
	<form enctype="multipart/form-data" action="index.php?ToDo=exportOrders2" id="frmExport" method="post" onsubmit="return ValidateForm(CheckExportForm)">
	<div class="BodyContainer">
	<table class="OuterPanel">
		<tr>
			<td class="Heading1" id="tdHeading">{% lang 'ExportOrders' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'ExportOrdersIntro' %}</p>
				{{ Message|safe }}
			</td>
		</tr>
		<tr>
			<td style="padding-bottom:10px">
				<div><input type="submit" name="SubmitButton1" value="{% lang 'Export' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></div>
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
						{% lang 'SearchFrom' %} {{ CurrencyToken|safe }}<input type="text" id="totalFrom" name="totalFrom" class="Field50"> {% lang 'SearchTo' %}
						{{ CurrencyToken|safe }}<input type="text" id="totalTo" name="totalTo" class="Field50">
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
						{% lang 'SearchFrom' %} <input class="plain" name="fromDate" id="dc1" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fStartPop(document.getElementById('dc1'),document.getElementById('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
						{% lang 'SearchTo' %} <input class="plain" name="toDate" id="dc2" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fEndPop(document.getElementById('dc1'),document.getElementById('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
					</td>
				</tr>
				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap"><input type="submit" name="SubmitButton1" value="{% lang 'Export' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
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

		function ConfirmCancel() {
			if(confirm("{% lang 'ConfirmCancelSearch' %}"))
				document.location.href = "index.php?ToDo=viewOrders";
		}

		function CheckExportForm() {
			var orderFrom = document.getElementById("orderFrom");
			var orderTo = document.getElementById("orderTo");
			var totalFrom = document.getElementById("totalFrom");
			var totalTo = document.getElementById("totalTo");
			var fromDate = document.getElementById("dc1");
			var toDate = document.getElementById("dc2");

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

			if(totalFrom.value != "" && isNaN(totalFrom.value)) {
				alert("{% lang 'SearchEnterValidTotal' %}");
				totalFrom.focus();
				totalFrom.select();
				return false;
			}

			if(totalTo.value != "" && isNaN(totalTo.value)) {
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
