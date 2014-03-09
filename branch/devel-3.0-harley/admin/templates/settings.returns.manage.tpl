	<form action="index.php?ToDo=saveUpdatedReturnsSettings" name="frmReturnsSettings" id="frmReturnsSettings" method="post" onsubmit="return ValidateForm(CheckReturnsSettingsForm)">
		<div class="BodyContainer">
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'ReturnsSettings' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'ReturnsSettingsIntro' %}</p>
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
						<td class="Heading2" colspan="2">{% lang 'ReturnsSettings' %}</td>
					</tr>

					<tr>
						<td class="FieldLabel">
							{% lang 'EnableReturnsSystem' %}
						</td>
						<td>
							<label><input type="checkbox" name="enablereturns" id="enablereturns" value="1" {{ IsEnableReturns|safe }} onclick="ToggleReturnsStatus(this.checked)" /> {% lang 'YesEnableReturnsSystem' %}</label>
							<img onmouseout="HideHelp('returns1');" onmouseover="ShowHelp('returns1', '{% lang 'EnableReturnsSystem' %}', '{% lang 'EnableReturnsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="returns1"></div>
						</td>
					</tr>

					<tr class="HideOnDisabled">
						<td class="FieldLabel">
							{% lang 'ReturnInstructions' %}:
						</td>
						<td style="padding-left: 25px;">
							<textarea name="returninstructions" id="returninstructions" class="Field300" rows="6">{{ ReturnInstructions|safe }}</textarea>
							<img onmouseout="HideHelp('returns2');" onmouseover="ShowHelp('returns2', '{% lang 'ReturnInstructions' %}', '{% lang 'ReturnInstructionsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="returns2"></div>
						</td>
					</tr>

					<tr class="HideOnDisabled">
						<td class="FieldLabel">
							{% lang 'ReturnReasons' %}:
						</td>
						<td style="padding-left: 25px;">
							<textarea name="returnreasons" id="returnreasons" class="Field300" rows="6">{{ ReturnReasonsArea|safe }}</textarea>
							<img onmouseout="HideHelp('returns3');" onmouseover="ShowHelp('returns3', '{% lang 'ReturnReasons' %}', '{% lang 'ReturnReasonsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="returns3"></div>
						</td>
					</tr>

					<tr class="HideOnDisabled">
						<td class="FieldLabel">
							{% lang 'ReturnActions' %}:
						</td>
						<td style="padding-left: 25px;">
							<textarea name="returnactions" id="returnactions" class="Field300" rows="6">{{ ReturnActionsArea|safe }}</textarea>
							<img onmouseout="HideHelp('returns4');" onmouseover="ShowHelp('returns4', '{% lang 'ReturnActions' %}', '{% lang 'ReturnActionsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="returns4"></div>
						</td>
					</tr>

					<tr class="HideOnDisabled PanelBottom">
						<td class="FieldLabel">
							{% lang 'ReturnCredits' %}
						</td>
						<td style="padding-left: 25px;">
							<label><input type="checkbox" name="returncredits" id="returncredits" value="1" {{ IsReturnCredits|safe }} /> {% lang 'YesEnableReturnCredits' %}</label>
							<img onmouseout="HideHelp('returns5');" onmouseover="ShowHelp('returns5', '{% lang 'ReturnCredits' %}', '{% lang 'ReturnCreditsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="returns5"></div>
						</td>
					</tr>
				</table>

				<table width="100%" class="Panel HideOnDisabled">
					<tr>
						<td class="Heading2" colspan="2">{% lang 'ReturnsNotifications' %}</td>
					</tr>

					<tr>
						<td class="FieldLabel">
							{% lang 'NotifyOnReturn' %}:
						</td>
						<td>
							<label><input type="checkbox" name="returnotifyowner" id="returnotifyowner" value="1" {{ IsReturnNotifyOwner|safe }} /> {% lang 'YesEnableReturnNotifyOwner' %}</label><br />
							<label><input type="checkbox" name="returnnotifycustomer" id="returnnotifycustomer" value="1" {{ IsReturnNotifyCustomer|safe }} /> {% lang 'YesEnableReturnNotifyCustomer' %}</label><br />
							<label><input type="checkbox" name="returnnotifystatus" id="returnnotifystatus" value="1" {{ IsReturnNotifyStatusChange|safe }} /> {% lang 'YesEnableReturnNotifyStatusChange' %}</label><br />
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
				document.location.href='index.php?ToDo=viewReturnsSettings';
			}
			else {
				return false;
			}
		}

		function CheckReturnsSettingsForm() {
			if($('enablereturns').get().checked == true && $('#returnreasons').val() == "") {
				alert('{% lang 'EnterReturnReason' %}');
				$('#returnreasons').focus();
				$('#returnreasons').select();
				return false;
			}

			return true;
		}

		function ToggleReturnsStatus(status) {
			if(status == true) {
				$('.HideOnDisabled').show();
			}
			else {
				$('.HideOnDisabled').hide();
			}
		}

		$(document).ready(function () {
			if ($('#enablereturns').attr('checked') == true) {
				$('.HideOnDisabled').show();
			} else {
				$('.HideOnDisabled').hide();
			}
		});
	</script>
