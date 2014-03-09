
	<form action="index.php?ToDo={{ FormAction|safe }}" onsubmit="return ValidateForm(CheckForm)" name="frmAddTaxRate" method="post">
	{{ hiddenFields|safe }}
	<div class="BodyContainer">
	<table class="OuterPanel">
		  <tr>
			<td class="Heading1">{{ TaxRateTitle|safe }}</td>
			</tr>
			<tr>
			<td class="Intro">
				<p>{% lang 'TaxRateIntro' %}</p>
				{{ Message|safe }}
			</td>
		  </tr>
		<tr>
			<td class="Intro">
				<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" />
				<input type="submit" value="{{ SaveAndContinue|safe }}" name="addAnother" />
				<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
			</td>
		</tr>
				<tr>
					<td>
					  <table class="Panel">
						<tr>
						  <td class="Heading2" colspan=2>{% lang 'TaxRateDetails' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'TaxName' %}:
							</td>
							<td>
								<input type="text" name="taxratename" id="taxratename" class="Field250" value="{{ TaxRateName|safe }}" />
								<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'TaxName' %}', '{% lang 'TaxNameHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d1"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'BasedOn' %}:
							</td>
							<td>
								<select name="taxratebasedon" id="taxratebasedon" class="Field250">
									<option {{ BasedOnSubTotal|safe }} value="subtotal">{% lang 'OrderSubTotal' %}</option>
									<option {{ BasedOnSubTotalAndShipping|safe }} value="subtotal_and_shipping">{% lang 'OrderSubTotalAndShipping' %}</option>
								</select>
								<img onmouseout="HideHelp('d3');" onmouseover="ShowHelp('d3', '{% lang 'BasedOn' %}', '{% lang 'BasedOnHelp' %}')" src="images/help.gif" width="24" height="16"border="0">
								<div style="display:none" id="d3"></div>
								<div class="NodeJoin">
									<img src="images/nodejoin.gif" alt="" /> <label><input type="checkbox" id="taxshippingfortaxableorder" name="taxshippingfortaxableorder" value="1" {{ TaxShippingForTaxableOrder|safe }} />{% lang 'TaxShippingForTaxableOrder' %}</label>
									<img onmouseout="HideHelp('d7');" onmouseover="ShowHelp('d7', '{% lang 'TaxShipping' %}', '{% lang 'TaxShippingForTaxableOrderHelp' %}')" src="images/help.gif" width="24" height="16"border="0">
								<div style="display:none" id="d7"></div>
								</div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'ApplyTo' %}:
							</td>
							<td>
								<select size="15" name="taxratecountry" id="taxratecountry" class="Field250" onchange="GetStates(this, 'taxratestates')">
									{{ CountryList|safe }}
								</select>
								<img onmouseout="HideHelp('d4');" onmouseover="ShowHelp('d4', '{% lang 'ApplyTo' %}', '{% lang 'ApplyToHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d4"></div>
							</td>
						</tr>
						<tr id="trstates" style="display:{{ HideStateList|safe }}">
							<td class="FieldLabel">
								&nbsp;
							</td>
							<td>
								<select multiple size="10" name="taxratestates[]" id="taxratestates" class="Field250 ISSelectReplacement">
									{{ StateList|safe }}
								</select>
								<img onmouseout="HideHelp('d6');" onmouseover="ShowHelp('d6', '{% lang 'ApplyTo' %}', '{% lang 'ApplyToStateHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d6"></div>
							</td>
						</tr>
							<tr>
								<td class="FieldLabel">
									<span class="Required">*</span>&nbsp;{% lang 'TaxedAddress' %}:
								</td>
								<td>
									<select name="taxaddress" id="taxaddress" class="Field250">
										<option {{ TaxAddressBilling|safe }} value="billing">{% lang 'TaxedAddressBilling' %}</option>
										<option {{ TaxAddressShipping|safe }} value="shipping">{% lang 'TaxedAddressShipping' %}</option>
									</select>
									<img onmouseout="HideHelp('taxaddress_help');" onmouseover="ShowHelp('taxaddress_help', '{% lang 'TaxedAddress' %}', '{% lang 'TaxedAddressHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
									<div style="display:none" id="taxaddress_help"></div>
								</td>
							</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'TaxRate' %}:
							</td>
							<td>
								<input type="text" name="taxratepercent" id="taxratepercent" class="Field50" value="{{ TaxRatePercent|safe }}" />%
								<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'TaxRate' %}', '{% lang 'TaxRateHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d2"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'TaxEnabled' %}
							</td>
							<td>
								<input {{ TaxEnabled|safe }} type="checkbox" name="taxratestatus" id="taxratestatus" /> <label for="taxratestatus">{% lang 'YesTaxEnabled' %}</label>
								<img onmouseout="HideHelp('d5');" onmouseover="ShowHelp('d5', '{% lang 'TaxEnabled' %}', '{% lang 'TaxEnabledHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d5"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">&nbsp;</td>
							<td>
								<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" />
								<input type="submit" value="{{ SaveAndContinue|safe }}" name="addAnother" />
								<input type="button" name="CancelButton2" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
							</td>
						</tr>
						<tr><td class="Gap"></td></tr>
					 </table>
					</td>
				</tr>
			</table>
		</div>
	</form>

	<script type="text/javascript">

		function CheckForm() {
			var taxratename = document.getElementById("taxratename");
			var taxratepercent = document.getElementById("taxratepercent");

			if(taxratename.value == "") {
				alert("{% lang 'EnterTaxRateName' %}");
				taxratename.focus();
				return false;
			}

			if(isNaN(priceFormat(taxratepercent.value)) || taxratepercent.value == "") {
				alert("{% lang 'EnterValidTaxRate' %}");
				taxratepercent.focus();
				taxratepercent.select();
				return false;
			}

			return true;
		}

		function ConfirmCancel()
		{
			if(confirm('{{ CancelMessage|safe }}'))
				document.location.href='index.php?ToDo=viewTaxSettings';
			else
				return false;
		}

		function GetStates(selObj, dest)
		{
			var country = selObj.options[selObj.selectedIndex].value;
			if(g(dest+'_old')) {
				selDest = document.getElementById(dest+'_old');
			}
			else {
				selDest = document.getElementById(dest);
			}

			// Get all of the states for this country
			DoCallback("w=countryStates&c="+country);
		}

		function ProcessData(html)
		{
			states = html.split("~");
			numStates = 0;

			if(html != "") {
				document.getElementById("trstates").style.display = "";
				selDest.options.length = 0;
				selDest.options[selDest.options.length] = new Option("{% lang 'AllStates' %}", 0);

				for(i = 0; i < states.length; i++)
				{
					vals = states[i].split("|");

					if(states[i].length > 0) {
						selDest.options[selDest.options.length] = new Option(vals[0], vals[1]);
						numStates++;
					}
				}

				selDest.selectedIndex = 0;
				if(g('taxratestates_old')) {
					g('taxratestates').parentNode.removeChild(g('taxratestates'));
					g('taxratestates_old').id = 'taxratestates';
					ISSelectReplacement.replace_select(g('taxratestates'));
				}
				$('#taxratestates').show();
			}
			else {
				document.getElementById("trstates").style.display = "none";
			}
		}

		$(document).ready(function() {
			$("#taxratebasedon").change(function() {
				if (this.value == 'subtotal_and_shipping') {
					$(this).nextAll('.NodeJoin:first').show();
				}
				else {
					$(this).nextAll('.NodeJoin:first').hide();
				}
			});
			$("#taxratebasedon").trigger('change');
		});
	</script>
