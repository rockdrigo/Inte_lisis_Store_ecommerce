
	<form enctype="multipart/form-data" action="index.php?ToDo={{ FormAction|safe }}" onsubmit="return ValidateForm(CheckUserForm)" id="frmUser" method="post">
	<input type="hidden" name="userId" value="{{ UserId|safe }}">
	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{{ Title|safe }}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'UserIntro' %}</p>
			{{ Message|safe }}
			{{ FlashMessages|safe }}
			<p>
				<input type="submit" name="SaveButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp;
				<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
			</p>
		</td>
	  </tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'NewUserDetails' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'Username' %}:
					</td>
					<td>
						<input type="text" id="username" name="username" class="Field250" autocomplete="off" value="{{ Username|safe }}" {{ DisableUser|safe }}>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{{ PassReq|safe }}&nbsp;{% lang 'UserPass' %}:
					</td>
					<td>
						<input type="password" id="userpass" name="userpass" class="Field250" autocomplete="off" value="{{ UserPass|safe }}">
						<div class="PasswordStrengthMeter" id="PasswordStrengthMeter"></div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						{{ PassReq|safe }}&nbsp;{% lang 'UserPass1' %}:
					</td>
					<td>
						<input type="password" id="userpass1" name="userpass1" class="Field250" autocomplete="off" value="{{ UserPass|safe }}">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'UserEmail' %}:
					</td>
					<td>
						<input type="text" id="useremail" name="useremail" class="Field250" value="{{ UserEmail|safe }}">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'UserFirstName' %}:
					</td>
					<td>
						<input type="text" id="userfirstname" name="userfirstname" class="Field250" value="{{ UserFirstName|safe }}">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'UserLastName' %}:
					</td>
					<td>
						<input type="text" id="userlastname" name="userlastname" class="Field250" value="{{ UserLastName|safe }}">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'UserStatus' %}:
					</td>
					<td>
						<select id="userstatus" name="userstatus" class="Field250" {{ DisableStatus|safe }}>
							<option value="1" {{ Active1|safe }}>{% lang 'UserActive' %}</option>
							<option value="0" {{ Active0|safe }}>{% lang 'UserInactive' %}</option>
						</select>
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'UserStatus' %}', '{% lang 'UserStatusHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d1"></div><br />
					</td>
				</tr>
				<tr style="{{ HideVendorOptions|safe }}">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'Vendor' %}:
					</td>
					<td>
						<div style="{{ HideVendorSelect|safe }}">
							<select id="uservendorid" name="uservendorid" class="Field250">
								<option value="">{% lang 'UserNoVendor' %}</option>
								{{ VendorList|safe }}
							</select>
							<img onmouseout="HideHelp('uservendorhelp');" onmouseover="ShowHelp('uservendorhelp', '{% lang 'Vendor' %}', '{% lang 'VendorHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="uservendorhelp"></div>
						</div>
						<div style="{{ HideVendorLabel|safe }}">
							{{ Vendor|safe }}
						</div>
					</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Sep" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'Permissions' %}</td>
				</tr>
			</table>
			<table class="Panel">
				<tr>
					<td colspan="2">
						<p class="HelpInfo">
							{% lang 'PermissionsHelp1' %} <a href="javascript:void(0)" onclick="LaunchHelp(686)">{% lang 'PermissionsHelp2' %}</a>.
						</p>
					</td>
				</tr>
			</table>
			<table class="Panel">
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'UserRole' %}:
					</td>
					<td>
						<select name="userrole" class="Field250" onchange="UpdateRole(this.options[this.selectedIndex].value)" {{ DisablePermissions|safe }}>
							{{ UserRoleOptions|safe }}
						</select>
						<img onmouseout="HideHelp('userrolehelp');" onmouseover="ShowHelp('userrolehelp', '{% lang 'UserRole' %}', '{% lang 'UserRoleHelp' %}')" src="images/help.gif" alt="" />
						<div style="display:none" id="userrolehelp"></div>
					</td>
				</tr>
				{{ PermissionSelects|safe }}
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp; <label for="StoreName">{% lang 'EnableXMLAPI' %}?</label>
					</td>
					<td>
						<input type="checkbox" name="userapi" id="userapi" value="ON" {{ IsXMLAPI|safe }} /> <label for="userapi">{% lang 'YesEnableXMLAPI' %}</label>
						<img onmouseout="HideHelp('xmlapi');" onmouseover="ShowHelp('xmlapi', '{% lang 'EnableXMLAPI' %}', '{% lang 'EnableXMLAPIHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="xmlapi"></div><br />
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="LaunchHelp(683)" style="color:gray">{% lang 'WhatIsXMLAPI' %}</a><br/><br />
						<table cellspacing="0" cellpadding="2" border="0" class="panel" style="display: block;" id="sectionXMLToken" style="display:none">
							<tr>
								<td width="90">
									<img width="20" height="20" src="images/nodejoin.gif"/>&nbsp; {% lang 'XMLPath' %}:
								</td>
								<td>
									<input type="text" readonly="" class="Field250" value="{{ XMLPath|safe }}" id="xmlpath" name="xmlpath"/><img onmouseout="HideHelp('xmlpathhelp');" onmouseover="ShowHelp('xmlpathhelp', '{% lang 'XMLPath' %}', '{% lang 'XMLPathHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
									<div style="display:none" id="xmlpathhelp"></div>
								</td>
							</tr>
							<tr>
								<td width="90">
									<img width="20" height="20" src="images/blank.gif"/>&nbsp; {% lang 'XMLToken' %}:
								</td>
								<td>
									<input type="text" onfocus="select(this);" readonly="" class="Field250" value="{{ XMLToken|safe }}" id="xmltoken" name="xmltoken"/> <img onmouseout="HideHelp('xmltokenhelp');" onmouseover="ShowHelp('xmltokenhelp', '{% lang 'XMLToken' %}', '{% lang 'XMLTokenHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
									<div style="display:none" id="xmltokenhelp"></div>
								</td>
							</tr>
							<tr>
								<td>
									&nbsp;
								</td>
								<td>
									<a style="color: gray;" href="javascript:void(0)" id="regenlink">{% lang 'RegenerateToken' %}</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap">
						<input type="submit" name="SaveButton2" value="{% lang 'Save' %}" class="FormButton">&nbsp;
						<input type="button" name="CancelButton2" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Sep" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>

	</table>

	</div>
	</form>

	<script type="text/javascript" src="../javascript/passwordmeter.js"></script>
	<script type="text/javascript">
		lang.PasswordStrengthMeter_MsgDefault = "{% jslang 'PasswordStrengthMeter_MsgDefault' %}";
		lang.PasswordStrengthMeter_MsgTooShort = "{% jslang 'PasswordStrengthMeter_MsgTooShort' %}";
		lang.PasswordStrengthMeter_MsgNoAlphaNum = "{% jslang 'PasswordStrengthMeter_MsgNoAlphaNum' %}";
		lang.PasswordStrengthMeter_MsgWeak = "{% jslang 'PasswordStrengthMeter_MsgWeak' %}";
		lang.PasswordStrengthMeter_MsgStrong = "{% jslang 'PasswordStrengthMeter_MsgStrong' %}";
		lang.PasswordStrengthMeter_MsgVeryStrong = "{% jslang 'PasswordStrengthMeter_MsgVeryStrong' %}";
		lang.PasswordStrengthMeter_Tip = "{% jslang 'PasswordStrengthMeter_Tip' %}";
		var meter = new PasswordStrengthMeter('userpass', 'PasswordStrengthMeter', 'PasswordStrengthTip', {{ PCIPasswordMinLen }});

		function UpdateRole(role)
		{
			// Start our selections
			if(role == 'admin') {
				SetupPermissions('sales', true);
				SetupPermissions('manager', true);
				SetupPermissions('admin', true);
			}
			else if(role == 'manager') {
				SetupPermissions('sales', true);
				SetupPermissions('manager', true);
				SetupPermissions('admin', false);
			}
			else if(role == 'sales') {
				SetupPermissions('sales', true);
				SetupPermissions('manager', false);
				SetupPermissions('admin', false);
			}
			else {
				// Revert all permissions
				SetupPermissions('sales', false);
				SetupPermissions('manager', false);
				SetupPermissions('admin', false);

				// Now reselect based on the role
				$('.permission_select .'+role+'_role input').attr('checked', false);
				$('.permission_select .'+role+'_role input').trigger('click');
			}
		}

		function ConfirmCancel()
		{
			if(confirm("{% lang 'ConfirmCancelUser' %}"))
				document.location.href = "index.php?ToDo=viewUsers";
		}

		function CheckUserForm()
		{
			var un = document.getElementById("username");
			var up1 = document.getElementById("userpass");
			var up2 = document.getElementById("userpass1");
			var ue = document.getElementById("useremail");

			if(un.value == "") {
				alert("{% lang 'UserEnterUsername' %}");
				un.focus();
				return false;
			}

			if("{{ Adding|safe }}" == "1") {
				// client side password validation (create/copy user)
				var res = meter.validate(up1.value);
				if (res.valid == false) {
					alert(res.msg);
					up1.focus();
					return false;
				}

				if(up1.value == "") {
					alert("{% lang 'UserEnterPassword' %}");
					up1.focus();
					return false;
				}

				if(up1.value != up2.value) {
					alert("{% lang 'UserPasswordsDontMatch' %}");
					up2.focus();
					up2.select();
					return false;
				}
			}
			else
			{
				if (up1.value != '' || up2.value != '') {
					// client side password validation (edit user)
					var res = meter.validate(up1.value);
					if (res.valid == false) {
						alert(res.msg);
						up1.focus();
						return false;
					}

					if (up1.value != up2.value) {
						alert("{% lang 'UserPasswordsDontMatch' %}");
						up2.focus();
						up2.select();
						return false;
					}
				}
			}

			if(ue.value.indexOf(".") == -1 || ue.value.indexOf("@") == -1) {
				alert("{% lang 'UserInvalidEmail' %}");
				ue.focus();
				ue.select();
				return false;
			}

			if(!HasSelectedPermissions('sales') && !HasSelectedPermissions('manager') && !HasSelectedPermissions('admin')) {
				$('#permissions_sales').focus();
				alert("{% lang 'UserNoPermissions' %}");
				return false;
			}

			// Everything is OK
			return true;
		}

		function HasSelectedPermissions(type) {
			if(g('permissions_'+type+'_old')) {
				var f = $('#permissions_'+type+'_old').val();
			}
			else {
				var f = $('#permissions_'+type).val();
			}
			return f;
		}

		function SetupPermissions(type, status)
		{
			if($('#permissions_'+type).length != 1) {
				return;
			}

			if($('#permissions_'+type+'_old').length == 1) {
				if($('#permissions_'+type+'_old').attr('disabled') == true) {
					return;
				}

 				if (status) {
					$('#permissions_'+type+' li').not(".SelectedRow").trigger('click');
				} else {
					$('#permissions_'+type+' .SelectedRow').trigger('click');
				}
			}
			else {
				$('#permissions_'+type+' option').attr('selected', status);
			}
		}

		function ToggleAPI(State) {
			if(State) {
				$('#sectionXMLToken').show();
			}
			else {
				$('#sectionXMLToken').hide();
			}
		}

		function RegenerateToken() {
			$.get("{{ ShopPath|safe }}/admin/remote.php?w=generateAPIKey", null, function(data) { $('#xmltoken').val(data); } );
		}

		$(document).ready(function() {
			if('{{ IsXMLAPI|safe }}' == 'checked="checked"') {
				ToggleAPI(true);
			}
			else {
				ToggleAPI(false);
			}

			// init the password meter
			meter.init();
		});

		$('#userapi').click(function() {
			if($('#userapi').attr('checked')) {
				ToggleAPI(true);
				if($('#xmltoken').val() == '') {
					RegenerateToken();
				}
			}
			else {
				ToggleAPI(false);
			}
		});

		$('#regenlink').click(function() {
			RegenerateToken();
		});

	</script>
