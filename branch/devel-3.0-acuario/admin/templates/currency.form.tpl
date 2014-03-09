
	<form action="index.php?ToDo={{ FormAction|safe }}" onsubmit="return ValidateForm(CheckForm)" name="frmAddCurrency" method="post">
	<input type="hidden" name="setCurrencyAsDefault" id="setCurrencyAsDefault" value="" />
	{{ hiddenFields|safe }}
	<div class="BodyContainer">
	<table class="OuterPanel">
		  <tr>
			<td class="Heading1">{{ CurrencyTitle|safe }}</td>
			</tr>
			<tr>
			<td class="Intro">
				<p>{% lang 'CurrencyIntro' %}</p>
				{{ Message|safe }}
			</td>
		  </tr>
		  <tr>
			    <td>
					<div>
						<input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton" />
						<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" /><br /><img src="images/blank.gif" width="1" height="10" /></div>
				</td>
			  </tr>
				<tr>
					<td>
					  <table class="Panel">
						<tr>
						  <td class="Heading2" colspan=2>{% lang 'CurrencyDetails' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'CurrencyName' %}:
							</td>
							<td>
								<input type="text" name="currencyname" id="currencyname" class="Field250" value="{{ CurrencyName|safe }}" />
								<img onmouseout="HideHelp('currname');" onmouseover="ShowHelp('currname', '{% lang 'CurrencyName' %}', '{% lang 'CurrencyNameHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="currname"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'CurrencyOrigin' %}:
							</td>
							<td>
								<select name="currencyorigin" id="currencyorigin" class="Field250" onchange="toggleOrigin();" size="10">
									{{ OriginList|safe }}
								</select>
								<input type="hidden" id="currencyorigintype" name="currencyorigintype" value="{{ CurrencyOriginType|safe }}" />
								<img onmouseout="HideHelp('currorigin');" onmouseover="ShowHelp('currorigin', '{% lang 'CurrencyOrigin' %}', '{% lang 'CurrencyOriginHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="currorigin"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'CurrencyCode' %}:
							</td>
							<td>
								<input maxlength="3" type="text" name="currencycode" id="currencycode" class="Field50" value="{{ CurrencyCode|safe }}" />
								<img onmouseout="HideHelp('currcode');" onmouseover="ShowHelp('currcode', '{% lang 'CurrencyCode' %}', '{% lang 'CurrencyCodeHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="currcode"></div>
							</td>
						</tr>
						<tr {{ HideOnDefault|safe }}>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'CurrencyExchangeRate' %}:
							</td>
							<td>
								{{ ConverterList|safe }}
							</td>
						</tr>
						<tr {{ HideOnDefault|safe }}>
							<td class="FieldLabel">
								&nbsp;
							</td>
							<td>
								<div id="currencyexchangeratediv"><img src="images/nodejoin.gif" align="left" />&nbsp;{{ CurrencyConverterBox|safe }}<input type="text" id="currencyexchangerate" name="currencyexchangerate" value="{{ CurrencyExchangeRate|safe }}" class="Field50"/>
								<img onmouseout="HideHelp('currexrate');" onmouseover="ShowHelp('currexrate', '{% lang 'CurrencyExchangeRate' %}', '{{ CurrencyExchangeRateHelp|safe }}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="currexrate"></div></div>
							</td>
						</tr>
						<tr {{ HideOnDefault|safe }}>
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'CurrencyEnabled' %}
							</td>
							<td>
								<input {{ CurrencyEnabled|safe }} type="checkbox" name="currencystatus" id="currencystatus" /> <label for="currencystatus">{% lang 'YesCurrencyEnabled' %}</label>
								<img onmouseout="HideHelp('currstatus');" onmouseover="ShowHelp('currstatus', '{% lang 'CurrencyEnabled' %}', '{% lang 'CurrencyEnabledHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="currstatus"></div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="EmptyRow" style="height:15px">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td class="Heading2" colspan=2>{% lang 'CurrencyDisplay' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="currencystringposition">{% lang 'CurrencyStringPosition' %}:</label>
							</td>
							<td>
								<select name="currencystringposition" id="currencystringposition" class="Field50">
									<option value="left"{{ CurrencyLocationIsLeft|safe }}>{% lang 'Left' %}</option>
									<option value="right"{{ CurrencyLocationIsRight|safe }}>{% lang 'Right' %}</option>
								</select>
								<img onmouseout="HideHelp('currstrpos');" onmouseover="ShowHelp('currstrpos', '{% lang 'CurrencyStringPosition' %}', '{% lang 'CurrencyStringPositionHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="currstrpos"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="currencystring">{% lang 'CurrencyString' %}:</label>
							</td>
							<td>
								<input type="text" name="currencystring" id="currencystring" value="{{ CurrencyString|safe }}" class="Field40" />
								<img onmouseout="HideHelp('currtoken');" onmouseover="ShowHelp('currtoken', '{% lang 'CurrencyString' %}', '{% lang 'CurrencyStringHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="currtoken"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="currencydecimalstring">{% lang 'CurrencyDecimalString' %}:</label>
							</td>
							<td>
								<input type="text" name="currencydecimalstring" id="currencydecimalstring" value="{{ CurrencyDecimalString|safe }}" class="Field40" maxlength="1" />
								<img onmouseout="HideHelp('currdectoken');" onmouseover="ShowHelp('currdectoken', '{% lang 'CurrencyDecimalString' %}', '{% lang 'CurrencyDecimalStringHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="currdectoken"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="currencythousandstring">{% lang 'CurrencyThousandString' %}:</label>
							</td>
							<td>
								<input type="text" name="currencythousandstring" id="currencythousandstring" value="{{ CurrencyThousandString|safe }}" class="Field40" maxlength="1" />
								<img onmouseout="HideHelp('currthousandstr');" onmouseover="ShowHelp('currthousandstr', '{% lang 'CurrencyThousandString' %}', '{% lang 'CurrencyThousandStringHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="currthousandstr"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> <label for="currencydecimalplace">{% lang 'CurrencyDecimalPlace' %}:</label>
							</td>
							<td class="PanelBottom">
								<input type="text" name="currencydecimalplace" id="currencydecimalplace" value="{{ CurrencyDecimalPlace|safe }}" class="Field40" />
								<img onmouseout="HideHelp('currdecplace');" onmouseover="ShowHelp('currdecplace', '{% lang 'CurrencyDecimalPlace' %}', '{% lang 'CurrencyDecimalPlaceHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="currdecplace"></div>
							</td>
						</tr>
					 </table>
					</td>
				</tr>
			</table>
			<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
				<tr>
					<td width="200" class="FieldLabel">
						&nbsp;
					</td>
					<td>
						<input type="submit" value="{% lang 'Save' %}" class="FormButton" />
						<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
					</td>
				</tr>
			</table>
		</div>
	</form>

	<script type="text/javascript">

		$(document).ready(function() {
			$('#currencyname').focus();
		});

		function toggleOrigin()
		{
			var origin = document.getElementById("currencyorigin");
			var id     = $(origin.options[origin.selectedIndex]).parent().attr("id");

			if (matches = id.match(/^currencyorigintype\-([a-z]+)$/i))
				$("#currencyorigintype").val(matches[1]);
		}

		function checkCurrencyCode(code)
		{
			var regexp = /^[a-z]{3}$/i;
			return regexp.test(code);
		}

		function toggleExchangeConverter(id)
		{
			var currentCurrencyNode = document.getElementById("currencyconverter" + id);
			var otherCurrencyNodes  = currentCurrencyNode.parentNode.getElementsByTagName("INPUT");

			for (var i in otherCurrencyNodes) {
				if (otherCurrencyNodes[i].type == "radio" && otherCurrencyNodes[i].id !== "currencyconvertermanual")
					document.getElementById("currencyconverterupdate" + otherCurrencyNodes[i].value).style.display = "none";
			}

			if (id !== "manual")
				document.getElementById("currencyconverterupdate" + id).style.display = "inline";
		}

		function getExchangeRate(id)
		{
			if (!checkCurrencyCode($("#currencycode").val())) {
				alert("{% lang 'ErrorEnterCurrencyCodeForConverter' %}");
				$("#currencycode").focus();
				return false;
			}
			$.ajax({
				type   : "POST",
				url    : url,
				data   : "w=getExchangeRate&cid=" + id + "&ccode="+ $("#currencycode").val(),
				success: processExchangeRate
				});
		}

		function processExchangeRate(data)
		{
			eval('var data = ' + data);
			if (!data.status) {
				alert(data.message);
				$("#currencycode").focus();
				return false;
			}

			$("#currencyexchangerate").val(data.rate);
			alert(data.message);
			return true;
		}

		function CheckForm()
		{
			var checkElements = new Array(
				{"name": "currencyname", "err": "{% lang 'EnterCurrencyName' %}"},
				{"name": "currencyorigin", "err": "{% lang 'EnterCurrencyOrigin' %}"},
				{"name": "currencycode", "err": "{% lang 'EnterCurrencyCode' %}"},
				{"name": "currencyexchangerate", "err": "{% lang 'EnterCurrencyExchangeRate' %}"},
				{"name": "currencystringposition", "err": "{% lang 'EnterCurrencyStringPosition' %}"},
				{"name": "currencystring", "err": "{% lang 'EnterCurrencyString' %}"},
				{"name": "currencydecimalstring", "err": "{% lang 'EnterCurrencyDecimalString' %}"},
				{"name": "currencythousandstring", "err": "{% lang 'EnterCurrencyThousandString' %}"},
				{"name": "currencydecimalplace", "err": "{% lang 'EnterCurrencyDecimalPlace' %}"}
			);

			for (var i=0; i<checkElements.length; i++) {
				if ($("#" + checkElements[i].name).val() == "" || $("#" + checkElements[i].name).val() == null) {
					alert(checkElements[i].err);
					$("#" + checkElements[i].name).focus();
					return false;
				}
			}

			if (isNaN(priceFormat($("#currencyexchangerate").val()))) {
				alert("{% lang 'InvalidCurrencyExchangeRate' %}");
				$("#currencyexchangerate").focus();
				return false;
			}

			if (!checkCurrencyCode($("#currencycode").val())) {
				alert("{% lang 'InvalidCurrencyCode' %}");
				$("#currencycode").focus();
				return false;
			}

			if (isNaN(parseInt($("#currencydecimalplace").val()))) {
				alert("{% lang 'InvalidCurrencyDecimalPlace' %}");
				$("#currencydecimalplace").focus();
				return false;
			}

			var oneCharElements = new Array(
				{"name": "currencydecimalstring", "err": "{% lang 'InvalidCurrencyDecimalString' %}"},
				{"name": "currencythousandstring", "err": "{% lang 'InvalidCurrencyThousandString' %}"}
			);

			for (var i=0; i<oneCharElements.length; i++) {
				if ($("#" + oneCharElements[i].name).val().length > 1 || (/\d+/).test($("#" + oneCharElements[i].name).val())) {
					alert(oneCharElements[i].err);
					$("#" + oneCharElements[i].name).focus();
					return false;
				}
			}

			if ($("#currencydecimalstring").val() == $("#currencythousandstring").val()) {
				alert("{% lang 'InvalidCurrencyStringMatch' %}");
				$("#currencydecimalplace").focus();
				return false;
			}

			return true;
		}

		function ConfirmCancel()
		{
			if(confirm('{{ CancelMessage|safe }}'))
				document.location.href='index.php?ToDo=viewCurrencySettings';
			else
				return false;
		}

	</script>
