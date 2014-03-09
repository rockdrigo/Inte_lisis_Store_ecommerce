
	<form enctype="multipart/form-data" action="index.php" id="frmSearch" method="get" onsubmit="return ValidateForm(CheckSearchForm)">
	<input type="hidden" name="ToDo" value="searchCustomersRedirect" />
	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{% lang 'SearchCustomers' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'SearchCustomersIntro' %}</p>
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
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsCustHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d1"></div>
					</td>
				</tr>

				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'CustPhone' %}:
					</td>
					<td>
						<input type="text" id="phone" name="phone" class="Field250">
					</td>
				</tr>

				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'CustCountry' %}:
					</td>
					<td>
						<select name="country" id="country" class="Field250" onchange="GetStates(this, 'state', 'state_1')">
							<option value="">{% lang 'ChooseCustCountry' %}</option>
							{{ CountryList|safe }}
						</select>
					</td>
				</tr>

				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'CustState' %}:
					</td>
					<td>
						<select style="display:{{ HideStateList|safe }}" name="state" id="state" class="Field250">
							{{ StateList|safe }}
						</select>
						<input style="display:{{ HideStateBox|safe }}" type="text" name="state_1" id="state_1" value="{{ AddressState|safe }}" class="Field250" />
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
						{% lang 'SearchFrom' %} &nbsp;&nbsp;<input type="text" id="idFrom" name="idFrom" class="Field50"> {% lang 'SearchTo' %}
						 &nbsp;&nbsp;<input type="text" id="idTo" name="idTo" class="Field50">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'NumberOfOrders' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} &nbsp;&nbsp;<input type="text" id="ordersFrom" name="ordersFrom" class="Field50"> {% lang 'SearchTo' %}
						 &nbsp;&nbsp;<input type="text" id="ordersTo" name="ordersTo" class="Field50">
					</td>
				</tr>

				<tr style="display: {{ HideStoreCredit|safe }}">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'StoreCredit' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} {{ CurrencyTokenLeft|safe }}<input type="text" id="storeCreditFrom" name="storeCreditFrom" class="Field50" /> {{ CurrencyTokenRight|safe }}
						{% lang 'SearchTo' %} {{ CurrencyTokenLeft|safe }}<input type="text" id="storeCreditTo" name="storeCreditTo" class="Field50" /> {{ CurrencyTokenRight|safe }}
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
						<select name="dateRange" id="dateRange" onchange="ToggleRange()" class="Field250">
							<option value="">{% lang 'ChooseRegDate' %}</option>
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
							{% lang 'SearchFrom' %} <input class="plain" name="fromDate" id="dc1" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fStartPop(document.getElementById('dc1'),document.getElementById('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
							{% lang 'SearchTo' %} <input class="plain" name="toDate" id="dc2" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fEndPop(document.getElementById('dc1'),document.getElementById('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
						</div>
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr style="display: {{ HideCustomerGroups|safe }}">
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'SearchByGroup' %}</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'CustomerGroup' %}:
					</td>
					<td>
						<select name="custGroupId" id="custGroupId" class="Field250">
							<option value="">{% lang 'ChooseACustomerGroup' %}</option>
							{{ CustomerGroups|safe }}
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
				  <td class="Heading2" colspan=2>{% lang 'SortOrder' %}</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SortOrder' %}:
					</td>
					<td>
						<select name="sortField" class="Field120">
							<option value="customerid">{% lang 'CustID' %}</option>
							<option value="custconlastname">{% lang 'CustLastName' %}</option>
							<option value="custconfirstname">{% lang 'CustFirstName' %}</option>
							<option value="custconemail">{% lang 'Email' %}</option>
							<option value="custconphone">{% lang 'Phone' %}</option>
							<option value="custconcompany">{% lang 'CustCompany' %}</option>
							<option value="custdatejoined">{% lang 'CustDateCreated' %}</option>
							<option value="numorders">{% lang 'NumOrders' %}</option>
						</select>
						in&nbsp;
						<select name="sortOrder" class="Field110">
						<option value="asc">{% lang 'AscendingOrder' %}</option>
						<option value="desc">{% lang 'DescendingOrder' %}</option>
					</td>
				</tr>
				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap"><input type="submit" name="SubmitButton1" value="{% lang 'Search' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
			 </table>
			</td>
		</tr>
	</table>
	<iframe width=132 height=142 name="gToday:contrast:agenda.js" id="gToday:contrast:agenda.js" src="calendar/ipopeng.htm" scrolling="no" frameborder="0" style="visibility:visible; z-index:999; position:absolute; left:-500px; top:0px;"></iframe>
	</div>
	</form>

	<script type="text/javascript" src="{{ ShopPath|safe }}/javascript/callback.js"></script>

	<script type="text/javascript">
		function GetStates(selObj, dest, stateTextBox)
		{
			var country = selObj.options[selObj.selectedIndex].value;

			selDest = document.getElementById(dest);
			otherBox = document.getElementById(stateTextBox);
			DoCallback("w=countryStates&c="+country);
		}

		function ResetStates(ShowChoose)
		{
			selDest.options.length = 0;

			if(ShowChoose)
				selDest.options[selDest.options.length] = new Option("{% lang 'ChooseState' %}", "");
		}

		function ProcessData(html)
		{
			ResetStates(true);
			states = html.split("~");
			numStates = 0;

			for(i = 0; i < states.length; i++)
			{
				vals = states[i].split("|");

				if(states[i].length > 0) {
					selDest.options[selDest.options.length] = new Option(vals[0], vals[1]);
					numStates++;
				}
			}

			// If there are no states then hide the state dropdown list
			if(numStates == 0) {
				selDest.style.display = "none";
				otherBox.style.display = "";
			}
			else {
				selDest.style.display = "";
				otherBox.style.display = "none";
			}
		}

		function ToggleRange()
		{
			var range = document.getElementById('dateRange');
			if(range.options[range.selectedIndex].value == "custom")
			{
				document.getElementById('dateRangeCustom').style.display = '';
			}
			else
			{
				document.getElementById('dateRangeCustom').style.display = 'none';
			}
		}

		ToggleRange();

		function ConfirmCancel() {
			if(confirm("{% lang 'ConfirmCancelSearch' %}"))
				document.location.href = "index.php?ToDo=viewCustomers";
		}

		function CheckSearchForm() {
			var ordersFrom = document.getElementById("ordersFrom");
			var ordersTo = document.getElementById("ordersTo");

			if(ordersFrom.value != "" && isNaN(ordersFrom.value)) {
				alert("{% lang 'SearchEnterValidordersId' %}");
				ordersFrom.focus();
				ordersFrom.select();
				return false;
			}

			if(ordersTo.value != "" && isNaN(ordersTo.value)) {
				alert("{% lang 'SearchEnterValidordersId' %}");
				ordersTo.focus();
				ordersTo.select();
				return false;
			}

			var storeCreditFrom = document.getElementById("storeCreditFrom");
			var storeCreditTo = document.getElementById("storeCreditTo");

			if(storeCreditFrom.value != "" && isNaN(priceFormat(storeCreditFrom.value))) {
				alert("{% lang 'SearchEnterValidStoreCredit' %}");
				storeCreditFrom.focus();
				storeCreditFrom.select();
				return false;
			}

			if(storeCreditTo.value != "" && isNaN(priceFormat(storeCreditTo.value))) {
				alert("{% lang 'SearchEnterValidStoreCredit' %}");
				storeCreditTo.focus();
				storeCreditTo.select();
				return false;
			}

			return true;
		}

	</script>
