<div style="margin-top: -20px; height:85%">
	<h2>{% lang 'LivePersonCreateAccount' %}</h2>
	<p class="Intro">{% lang 'LivePersonCreateAccountIntro' %}</p>
	<form method="get" action="http://server.iad.liveperson.net/hc/" onsubmit="return ValidateLivePersonForm();">
		<input type="hidden" name="cmd" value="oemRegisterNewUser" />
		<input type="hidden" name="oem" value="LA" />
		<input type="hidden" name="varId" value="4970" />
		<input type="hidden" name="siteClass" value="3" />
		<input type="hidden" name="url" value="{{ ShopPathNormal|safe }}" />
		<input type="hidden" name="returnUrl" value="{{ ShopPathNormal|safe }}/admin/index.php?ToDo=liveChatSettingsCallback&amp;module=livechat_liveperson&amp;func=PerformLivePersonRegistration" />
		<table class="Panel">
			<tr>
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'Username' %}:
				</td>
				<td>
					<input type="text" name="user" id="lp_username" class="Field200" value="{{ CurrentUser|safe }}" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'Password' %}:
				</td>
				<td>
					<input type="password" name="password" id="lp_password" class="Field200" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'EmailAddress' %}:
				</td>
				<td>
					<input type="text" name="email" id="lp_email" class="Field200" value="{{ CurrentEmail|safe }}" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'LivePersonPosition' %}:
				</td>
				<td>
					<select name="lp_position" id="lp_position" class="Field200">
						<option value='panel'>{% lang 'LivePersonPositionSide' %}</option>
						<option value='header'>{% lang 'LivePersonPositionHeader' %}</option>
					</select>
				</td>
			</tr>
		</table>
		<table class="PanelPlain">
			<tr>
				<td class="FieldLabel">&nbsp;</td>
				<td><input type="submit" value="{% lang 'LivePersonCreateAccount' %}" class="FormButton" style="width:110px" /> <input type="button" value="{% lang 'Cancel' %}" onclick="window.parent.tb_remove()" class="FormButton" /></td>
			</tr>
		</table>
	</form>
</div>
<script type="text/javascript">
function ValidateLivePersonForm()
{
	if(!$('#lp_username').val()) {
		alert('Please enter the username to use for your LivePerson account');
		$('#lp_username').focus();
		return false;
	}

	if(!$('#lp_password').val()) {
		alert('Please enter the password to use for your LivePerson account');
		$('#lp_password').focus();
		return false;
	}

	if($('#lp_email').val().indexOf('@') == -1) {
		alert('Please enter the email address to use for your LivePerson account');
		$('#lp_email').focus();
		return false;
	}
	window.parent.UpdatePosition($('#lp_position').val());
	return true;
}
</script>