<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11">
<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">
<head>
	<title>{% lang 'InstallInterspireShoppingCart' %}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={{ CharacterSet }}" />
	<meta name="robots" content="noindex, nofollow" />
	<style type="text/css">
		@import url("Styles/styles.css");
		@import url('Styles/tabmenu.css');
		@import url("Styles/iselector.css");
	</style>
	<!--[if IE]>
	<style type="text/css">
		@import url("Styles/ie.css");
	</style>
	<![endif]-->
	<style>
		h3 { font-size:13px; }
	</style>
	<script type="text/javascript">
		var url = 'remote.php';
		var critical_errors = "{{ CriticalErrors|safe }}";
		var is_trial = '{{ IsTrial|safe }}';
	</script>
	<script type="text/javascript" src="../javascript/jquery.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/menudrop.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="../javascript/thickbox.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/common.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="../javascript/iselector.js?{{ JSCacheToken }}"></script>
	<link rel="stylesheet" href="Styles/thickbox.css?{{ JSCacheToken }}" type="text/css" media="screen" />
</head>

<body>
	<form action="index.php?ToDo=RunInstallation" method="post" name="frmInstall" id="frmInstall">
	<div id="box">
		<br /><br /><br /><br />
		<table><tr><td style="border:solid 2px #DDD; padding:20px; background-color:#FFF; width:565px">
		<table>
		  <tr>
			<td class="Heading1">
				<img src="images/logo.jpg" />
			</td>
		  </tr>
		  <tr>
			<td class="HelpInfo">
				{{ Message|safe }}
				<div style="{{ HideInstallWarning|safe }}" class="MessageBox MessageBoxInfo">
					{{ InstallWarning|safe }}
				</div>
			</td>
		  </tr>
		  <tr class="FormContent">
			<td>
				<table>
					<tr style="{{ HideLicenseKey|safe }}">
						<td nowrap style="padding:10px 10px 10px 0px" colspan="2"><h3>{% lang 'LicenseDetails' %}</h3></td>
					</tr>
					<tr style="{{ HideLicenseKey|safe }}">
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'LicenseKey' %}:</td>
						<td><input type="text" name="LK" id="LK" class="Field250" value="{{ LicenseKey|safe }}"> <img onmouseout="HideHelp('keyhelp');" onmouseover="ShowHelp('keyhelp', '{% lang 'LicenseKey' %}', '{% lang 'LicenseKeyHelp' %}')" src="images/help.gif" width="24" height="16" border="0"><div style="display:none" id="keyhelp"></div></td>
					</tr>
					<tr>
						<td nowrap style="padding:10px 10px 10px 0px" colspan="2"><h3>{% lang 'StoreDetails' %}</h3></td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'ShopPath' %}:</td>
						<td><input type="text" name="ShopPath" id="ShopPath" class="Field250" value="{{ ShopPath|safe }}"> <img onmouseout="HideHelp('shoppathhelp');" onmouseover="ShowHelp('shoppathhelp', '{% lang 'ShopPath' %}', '{% lang 'ShopPathHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="shoppathhelp"></div></td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'StoreCountryLocation' %}:</td>
						<td>
							<select name="StoreCountryLocationId" id="StoreCountryLocationId" class="Field250">
								<option value="0">-- {% lang 'ChooseACountry' %} --</option>
								{{ StoreCountryList|safe }}
							</select><img onmouseout="HideHelp('storecountrylocationhelp');" onmouseover="ShowHelp('storecountrylocationhelp', '{% lang 'StoreCountryLocation' %}', '{% lang 'StoreCountryLocationHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="storecountrylocationhelp"></div>
						</td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'StoreCurrencyCode' %}:</td>
						<td><input type="text" name="StoreCurrencyCode" id="StoreCurrencyCode" maxlength="3" class="Field50" value="{{ StoreCurrencyCode|safe }}"> <img onmouseout="HideHelp('storecurrencycodehelp');" onmouseover="ShowHelp('storecurrencycodehelp', '{% lang 'StoreCurrencyCode' %}', '{% lang 'StoreCurrencyCodeHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="storecurrencycodehelp"></div></td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px">&nbsp;</td>
						<td>
							<label><input type="checkbox" name="installSampleData" id="installSampleData" value="1" {{ InstallSampleData|safe }} /> {% lang 'InstallSampleData' %}</label>
							<img onmouseout="HideHelp('sampledatahelp');" onmouseover="ShowHelp('sampledatahelp', '{% lang 'InstallSampleData' %}', '{% lang 'InstallSampleDataHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="sampledatahelp"></div></td>
					</tr>
					<tr>
						<td nowrap style="padding:10px 10px 10px 0px" colspan="2"><h3>{% lang 'UserAccountDetails' %}</h3></td>
					</tr>
					<tr style="{{ HideTrialFields|safe }}">
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'FullName' %}:</td>
						<td><input type="text" name="FullName" id="FullName" class="Field150" value="{{ FullName|safe }}"> <img onmouseout="HideHelp('fullnamehelp');" onmouseover="ShowHelp('fullnamehelp', '{% lang 'FullName' %}', '{% lang 'InstallFullNameHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="fullnamehelp"></div></td>
					</tr>
					<tr style="{{ HideTrialFields|safe }}">
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'PhoneNo' %}:</td>
						<td><input type="text" name="PhoneNumber" id="PhoneNumber" class="Field150" value="{{ PhoneNumber|safe }}"> <img onmouseout="HideHelp('phonenohelp');" onmouseover="ShowHelp('phonenohelp', '{% lang 'PhoneNo' %}', '{% lang 'PhoneNoHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
						<div style="display:none" id="phonenohelp"></div></td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'EmailAddress' %}:</td>
						<td><input type="text" name="UserEmail" id="UserEmail" class="Field150" value="{{ UserEmail|safe }}"> <img onmouseout="HideHelp('useremailhelp');" onmouseover="ShowHelp('useremailhelp', '{% lang 'EmailAddress' %}', '{% lang 'InstallEmailAddressHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="useremailhelp"></div></td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'ChooseAPassword' %}:</td>
						<td>
							<input type="password" autocomplete="off" name="UserPass" id="UserPass" class="Field150" value="{{ UserPass|safe }}">
							<div class="PasswordStrengthMeter" id="meterid"></div>
							<small class="note PasswordStrengthTip" id="tipid"></small>
						</td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'ConfirmYourPassword' %}:</td>
						<td><input type="password" autocomplete="off" name="UserPass1" id="UserPass1" class="Field150" value="{{ UserPass|safe }}"> <img onmouseout="HideHelp('userpass1help');" onmouseover="ShowHelp('userpass1help', '{% lang 'ConfirmYourPassword' %}', '{% lang 'ConfirmYourPasswordHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="userpass1help"></div></td>
					</tr>
					<tr>
						<td nowrap style="padding:10px 10px 10px 0px" colspan="2"><h3>{% lang 'MySQLDetails' %}</h3></td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px" colspan="2"><input type="radio" name="dbChoice" id="dbChoice1" value="ON"> <label for="dbChoice1">{% lang 'HasDB' %}</label></td>
					</tr>
				</table>
				<table class="DBDetails" style="padding:10px 10px 10px 20px">
					<tr class="DBDetails">
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'DatabaseUser' %}:</td>
						<td><input type="text" name="dbUser" id="dbUser" class="Field150" value="{{ dbUser|safe }}"> <img onmouseout="HideHelp('dbuserhelp');" onmouseover="ShowHelp('dbuserhelp', '{% lang 'DatabaseUser' %}', '{% lang 'DatabaseUserHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="dbuserhelp"></div></td>
					</tr>
					<tr class="DBDetails">
						<td nowrap style="padding:0px 10px 0px 10px">&nbsp;&nbsp; {% lang 'DatabasePassword' %}:</td>
						<td><input type="password" autocomplete="off" name="dbPass" id="dbPass" class="Field150" value="{{ dbPass|safe }}"> <img onmouseout="HideHelp('dbpasshelp');" onmouseover="ShowHelp('dbpasshelp', '{% lang 'DatabasePassword' %}', '{% lang 'DatabasePasswordHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="dbpasshelp"></div></td>
					</tr>
					<tr class="DBDetails">
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'DatabaseHostname' %}:</td>
						<td><input type="text" name="dbServer" id="dbServer" class="Field150" value="{{ dbServer|safe }}"> <img onmouseout="HideHelp('dbhostnamehelp');" onmouseover="ShowHelp('dbhostnamehelp', '{% lang 'DatabaseHostname' %}', '{% lang 'DatabaseHostnameHelp' %}')" src="images/help.gif" width="24" height="16" border="0"><div style="display:none" id="dbhostnamehelp"></div></td>
					</tr>
					<tr class="DBDetails">
						<td nowrap style="padding:0px 10px 0px 10px"><span class="Required">*</span> {% lang 'DatabaseName' %}:</td>
						<td><input type="text" name="dbDatabase" id="dbDatabase" class="Field150" value="{{ dbDatabase|safe }}"> <img onmouseout="HideHelp('dbnamehelp');" onmouseover="ShowHelp('dbnamehelp', '{% lang 'DatabaseName' %}', '{% lang 'DatabaseNameHelp' %}')" src="images/help.gif" width="24" height="16" border="0"><div style="display:none" id="dbnamehelp"></div></td>
					</tr>
					<tr class="DBDetails">
						<td nowrap style="padding:0px 10px 0px 10px">&nbsp;&nbsp; {% lang 'DatabaseTablePrefix' %}:</td>
						<td><input type="text" name="tablePrefix" id="tablePrefix" class="Field150" value="{{ tablePrefix|safe }}"> <img onmouseout="HideHelp('dbprefixhelp');" onmouseover="ShowHelp('dbprefixhelp', '{% lang 'DatabaseTablePrefix' %}', '{% lang 'DatabaseTablePrefixHelp' %}')" src="images/help.gif" width="24" height="16" border="0"><div style="display:none" id="dbprefixhelp"></div></td>
					</tr>
				</table>
				<table>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px" colspan="2" ><input type="radio" name="dbChoice" id="dbChoice2" value="ON"> <label for="dbChoice2">{% lang 'HasNoDB' %}</label></td>
					</tr>
				</table>
				<table class="DBHelp" style="padding:10px 10px 10px 20px">
					<tr class="DBHelp">
						<td colspan="2" class="HelpInfo"><h3 style="padding-bottom:10px">{% lang 'WhatIsMySQLDB' %}</h3>{% lang 'DBHelpText' %}</td>
					</tr>
				</table>
				<table>
					<tr>
						<td nowrap style="padding:10px 10px 10px 0px" colspan="2"><h3>{% lang 'ServerConfigurationDetails' %}</h3></td>
					</tr>
					<tr>
						<td nowrap style="padding:0px 10px 0px 10px" colspan="2"><input type="checkbox" name="sendServerDetails" id="sendServerDetails" value="ON" checked="checked"> <label for="sendServerDetails">{% lang 'SendServerDetails' %}</label><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="alert('{% lang 'ServerDetailsInfo' %}')" style="color:gray">{% lang 'WhatWillBeSent' %}</a></td>
					</tr>
					<tr>
					<td>&nbsp;</td>
						<td>
							<br /><input type="submit" name="SubmitButton" value="{% lang 'Continue' %}" class="FormButton">
						</td>
					</tr>
					<tr>
						<td class="Gap"></td>
					</tr>
				</table>
			</td>
		  </tr>
		</table>
		</td></tr></table>
		<div style="padding:10px; margin-bottom:20px; text-align:center" class="PageFooter">
			<!-- Removing this "Powered by" link will violate your license agreement with Interspire -->
			Powered by <a href="http://www.interspire.com/shoppingcart/" target="_blank">Interspire Shopping Cart {{ ProductVersion|safe }}</a> &copy; 2004-{{ Year|safe }} Interspire Pty. Ltd.
		</div>
	</div>
	</form>

	<div id="permissionsBox" style="display:none">
		<div style="background-image:url('images/permissions_error.gif'); background-position:right bottom; background-repeat:no-repeat; height:100%">{{ PermissionErrors|safe }}</div>
	</div>

	<script type="text/javascript">{{ AutoJS|safe }}</script>
	<script type="text/javascript" src="../javascript/passwordmeter.js"></script>
	<script type="text/javascript">
		lang.PasswordStrengthMeter_MsgDefault = "{% jslang 'PasswordStrengthMeter_MsgDefault' %}";
		lang.PasswordStrengthMeter_MsgTooShort = "{% jslang 'PasswordStrengthMeter_MsgTooShort' %}";
		lang.PasswordStrengthMeter_MsgNoAlphaNum = "{% jslang 'PasswordStrengthMeter_MsgNoAlphaNum' %}";
		lang.PasswordStrengthMeter_MsgWeak = "{% jslang 'PasswordStrengthMeter_MsgWeak' %}";
		lang.PasswordStrengthMeter_MsgStrong = "{% jslang 'PasswordStrengthMeter_MsgStrong' %}";
		lang.PasswordStrengthMeter_MsgVeryStrong = "{% jslang 'PasswordStrengthMeter_MsgVeryStrong' %}";
		lang.PasswordStrengthMeter_Tip = "{% jslang 'PasswordStrengthMeter_Tip' %}";
		var pmeter = new PasswordStrengthMeter('UserPass', 'meterid', 'tipid', {{ PCIPasswordMinLen }});
		$(document).ready(function() {
			pmeter.init();
		});
	</script>
	<script type="text/javascript" src="script/install.js?{{ JSCacheToken }}"></script>
</body>
</html>