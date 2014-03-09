
	<form enctype="multipart/form-data" action="index.php?ToDo=exportCustomers2" id="frmSearch" method="post" onsubmit="return ValidateForm(CheckSearchForm)">
	<div class="BodyContainer">
	<table class="OuterPanel">
		<tr>
			<td class="Heading1" id="tdHeading">{% lang 'ExportCustomers' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'ExportCustomersIntro' %}</p>
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

						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsCustHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
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
						&nbsp;&nbsp;&nbsp;{% lang 'CustomerID' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} <input type="text" id="idFrom" name="idFrom" class="Field50"> {% lang 'SearchTo' %}
						<input type="text" id="idTo" name="idTo" class="Field50">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'NumberOfOrders' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} <input type="text" id="ordersFrom" name="ordersFrom" class="Field50"> {% lang 'SearchTo' %}
						<input type="text" id="ordersTo" name="ordersTo" class="Field50">
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
						&nbsp;&nbsp;&nbsp;{% lang 'DateJoined' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} <input class="plain" name="fromDate" id="dc1" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fStartPop(document.getElementById('dc1'),document.getElementById('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
						{% lang 'SearchTo' %} <input class="plain" name="toDate" id="dc2" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fEndPop(document.getElementById('dc1'),document.getElementById('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap"><input type="submit" name="SubmitButton1" value="{% lang 'Export' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>

			 </table>
			</td>
		</tr>
	</table>
	<iframe width=132 height=142 name="gToday:contrast:agenda.js" id="gToday:contrast:agenda.js" src="calendar/ipopeng.htm" scrolling="no" frameborder="0" style="visibility:visible; z-index:999; position:absolute; left:-500px; top:0px;"></iframe>
	</div>
	</form>

	<script type="text/javascript">

		function ConfirmCancel() {
			if(confirm("{% lang 'ConfirmCancelExportCustomers' %}"))
				document.location.href = "index.php?ToDo=viewCustomers";
		}

		function CheckSearchForm() {
			var idFrom = document.getElementById("idFrom");
			var idTo = document.getElementById("idTo");
			var ordersFrom = document.getElementById("ordersFrom");
			var ordersTo = document.getElementById("ordersTo");
			var fromDate = document.getElementById("dc1");
			var toDate = document.getElementById("dc2");

			if(idFrom.value != "" && isNaN(idFrom.value)) {
				alert("{% lang 'CustEnterValidCustId' %}");
				idFrom.focus();
				idFrom.select();
				return false;
			}

			if(idTo.value != "" && isNaN(idTo.value)) {
				alert("{% lang 'CustEnterValidCustId' %}");
				idTo.focus();
				idTo.select();
				return false;
			}

			if(ordersFrom.value != "" && isNaN(ordersFrom.value)) {
				alert("{% lang 'CustEnterValidNumOrders' %}");
				ordersFrom.focus();
				ordersFrom.select();
				return false;
			}

			if(ordersTo.value != "" && isNaN(ordersTo.value)) {
				alert("{% lang 'CustEnterValidNumOrders' %}");
				ordersTo.focus();
				ordersTo.select();
				return false;
			}

			if(fromDate.value != "" && fromDate.value == toDate.value) {
				alert("{% lang 'SearchChooseDifferentDates' %}");
				return false;
			}

			return true;
		}

	</script>
