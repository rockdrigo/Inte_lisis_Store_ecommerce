	<form action="index.php?ToDo=saveUpdatedGiftCertificateSettings" name="frmGiftCertificateSettings" id="frmGiftCertificateSettings" method="post" onsubmit="return ValidateForm(CheckGiftCertificateSettingsForm)">
		<div class="BodyContainer">
		{% if ManageGiftCertificateTemplatesNotice %}
		<div class="MessageBox MessageBoxInfo" style="">{{ ManageGiftCertificateTemplatesNotice|safe }}</div>
		{% endif %}
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'GiftCertificateSettings' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'GiftCertificateSettingsIntro' %}</p>
				{{ Message|safe }}
				<p>
				<input type="submit" value="{% lang 'Save' %}" class="FormButton" />
				<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<table width="100%" class="Panel">
					<tr>
						<td class="Heading2" colspan="2">{% lang 'GiftCertificateSettings' %}</td>
					</tr>

					<tr>
						<td class="FieldLabel">
							{% lang 'EnableGiftCertificates' %}
						</td>
						<td>
							<label><input type="checkbox" name="EnableGiftCertificates" id="EnableGiftCertificates" value="1" {{ IsEnableGiftCertificates|safe }} onclick="ToggleGiftCertificatesStatus(this.checked)" /> {% lang 'YesEnableGiftCertificates' %}</label>
							<img onmouseout="HideHelp('gifts1');" onmouseover="ShowHelp('gifts1', '{% lang 'EnableGiftCertificates' %}', '{% lang 'EnableGiftCertificatesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="gifts1"></div>
						</td>
					</tr>

					<tr class="HideOnDisabled">
						<td class="FieldLabel">
							{% lang 'GiftCertificateAmounts' %}:
						</td>
						<td style="padding-left: 25px;">
							<label><input type="radio" name="GiftCertificateCustomAmounts" id="GiftCertificateCustomAmountsNo"  onclick="$('#AmountsSelect').show();  $('#AmountsRange').hide();" value="0" {{ IsGiftCertificateSelectAmounts|safe }} /> {% lang 'GiftCertificateSelectAmounts' %}</label><br />
							<div id="AmountsSelect" style="display: {{ HideSelectAmounts|safe }}">
								<img src="images/nodejoin.gif" style="vertical-align: top;" />
								<textarea name="GiftCertificateAmounts" id="GiftCertificateAmounts" class="Field250" rows="6">{{ GiftCertificateAmountsArea|safe }}</textarea>
								<img onmouseout="HideHelp('gifts2');" onmouseover="ShowHelp('gifts2', '{% lang 'GiftCertificateAmounts' %}', '{% lang 'GiftCertificateAmountsHelp' %}')" src="images/help.gif" width="24" height="16" border="0" style="vertical-align: top;" />
								<div style="display:none" id="gifts2"></div>
							</div>
							<label><input type="radio" onclick="$('#AmountsSelect').hide(); $('#AmountsRange').show();" name="GiftCertificateCustomAmounts" id="GiftCertificateCustomAmounts" value="1" {{ IsGiftCertificateCustomAmounts|safe }} />{% lang 'GiftCertificateCustomAmounts' %}</label>
							<div id="AmountsRange" style="display: {{ HideCustomAmounts|safe }}">
								<img src="images/nodejoin.gif" style="vertical-align: middle;" />
								{% lang 'GiftCertificateMinAmount' %} {{ CurrencyTokenLeft|safe }} <input type="text" name="GiftCertificateMinimum" id="GiftCertificateMinimum" value="{{ GiftCertificateMinimum|safe }}" class="Field40" /> {{ CurrencyTokenRight|safe }}
								&nbsp;&nbsp;&nbsp;
								{% lang 'GiftCertificateMaxAmount' %} {{ CurrencyTokenLeft|safe }} <input type="text" name="GiftCertificateMaximum" id="GiftCertificateMaximum" value="{{ GiftCertificateMaximum|safe }}" class="Field40" /> {{ CurrencyTokenRight|safe }}
							</div>
						</td>
					</tr>

					<tr class="HideOnDisabled">
						<td class="FieldLabel PanelBottom">
							{% lang 'GiftCertificateExpiry' %}:
						</td>
						<td style="padding-left: 25px;" class="PanelBottom">
							<label><input type="checkbox" name="EnableGiftCertificateExpiry" id="EnableGiftCertificateExpiry" value="1" onclick="if(this.checked == true) { $('#EnableExpiryOptions').show(); } else { $('#EnableExpiryOptions').hide(); }" {{ IsGiftCertificateExpiry|safe }} /> {% lang 'YesEnableGiftCertificateExpiry' %}</label>

							<img onmouseout="HideHelp('gifts5');" onmouseover="ShowHelp('gifts5', '{% lang 'GiftCertificateExpiry' %}', '{% lang 'GiftCertificateExpiryHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="gifts5"></div>

							<div id="EnableExpiryOptions">
								<img src="images/nodejoin.gif" style="vertical-align: middle;" />
								{% lang 'GiftCertificateExpiryOptions' %}
								<input type="text" name="GiftCertificateExpiry" id="GiftCertificateExpiry" value="{{ ExpiresAfter|safe }}" class="Field40" />
								<select name="GiftCertificateExpiryRange" id="GiftCertificateExpiryRange">
									<option value="days">{% lang 'RangeDays' %}</option>
									<option value="weeks" {{ RangWeeksSelected|safe }}>{% lang 'RangeWeeks' %}</option>
									<option value="months" {{ RangeMonthsSelected|safe }}>{% lang 'RangeMonths' %}</option>
									<option value="years" {{ RangeYearsSelected|safe }}>{% lang 'RangeYears' %}</option>
								</select>
							</div>

						</td>
					</tr>
				</table>

				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">
							&nbsp;
						</td>
						<td>
							<input class="FormButton" type="submit" value="{% lang 'Save' %}">
							<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		</div>
	</form>

	<script type="text/javascript">
		function ConfirmCancel() {
			if(confirm('{% lang 'ConfirmCancelSettings' %}')) {
				document.location.href='index.php?ToDo=viewGiftCertificateSettings';
			}
			else {
				return false;
			}
		}

		function CheckGiftCertificateSettingsForm() {
			if($('enablereturns').get().checked == true && $('#returnreasons').val() == "") {
				alert('{% lang 'EnterReturnReason' %}');
				$('#returnreasons').focus();
				$('#returnreasons').select();
				return false;
			}

			return true;
		}

		function ToggleGiftCertificateCustomAmounts(status) {
			if(status == true) {
				$('#EnableCustomAmountOptions').show();
			}
			else {
				$('#EnableCustomAmountOptions').hide();
			}
		}

		function ToggleGiftCertificatesStatus(status) {
			if(status == true) {
				$('.HideOnDisabled').show();
			}
			else {
				$('.HideOnDisabled').hide();
			}
		}

		function UpdateGiftCertificatePreview(id, name) {
			if(g('ThemePreview_'+id)) {
				$('#ThemePreview img').hide();
				$('#ThemePreview #ThemePreview_'+id).show();
				$('#ThemePreview div').html('{% lang 'GiftCertificateViewingPreview' %}'.replace('%s', name));
			}
			else {
				$('#ThemePreview img').hide();
				$('#ThemePreview .NoPreview').show();
				$('#ThemePreview div').html('');
			}
		}

		$(document).ready(function() {
			if ($('#EnableGiftCertificates').attr('checked') == true) {
				$('.HideOnDisabled').show();
			} else {
				$('.HideOnDisabled').hide();
			}

			if ($('#EnableGiftCertificateExpiry').attr('checked') == true) {
				$('#EnableExpiryOptions').show();
			}
			else {
				$('#EnableExpiryOptions').hide();
			}
		});

	</script>
