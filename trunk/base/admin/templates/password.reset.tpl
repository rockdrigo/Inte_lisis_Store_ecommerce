<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11">
<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">
<head>
	<title>{% lang 'ControlPanel' %}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={{ CharacterSet }}" />
	<meta name="robots" content="noindex, nofollow" />
	<style type="text/css">
		@import url("Styles/styles.css");
	</style>
	<!--[if IE]>
	<style type="text/css">
		@import url("Styles/ie.css");
	</style>
	<![endif]-->
	<script type="text/javascript" src="../javascript/jquery.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/common.js?{{ JSCacheToken }}"></script>
</head>

<body>
	<form action="index.php?ToDo=forgotPass&step=reset&t={{ Token}}" method="post" name="frmResetPass" id="frmResetPass">
	<div id="box">
		<table><tr><td style="border:solid 2px #DDD; padding:20px; background-color:#FFF; width:300px">
		<table>
			<tr>
				<td class="Heading1"><a href="index.php">{{ AdminLogo|safe }}</a></td>
			</tr>
			<tr>
				<td style="padding:0 0 0 10px">{% lang 'ResetPasswordIntro' %}</td>
			</tr>
			<tr>
				<td>{{ FlashMessages|safe }}</td>
			</tr>
			<tr>
			  <td>
				<table>
				<tr>
					<td nowrap style="padding:0px 10px 0px 10px">{% lang 'UsernameLabel' %}</td>
					<td>
						<input type="text" name="username" id="username" class="Field150" value="{{ Username|safe }}">
					</td>
				</tr>
				<tr>
					<td nowrap style="padding:0px 10px 0px 10px">{% lang 'NewPassword' %}:</td>
					<td>
						<input type="password" autocomplete="off" name="newpassword" id="newpassword" class="Field150" value="">
						<div class="PasswordStrengthMeter" id="meterid"></div>
					</td>
				</tr>
				<tr>
					<td nowrap style="padding:0px 10px 0px 10px">{% lang 'NewPasswordConfirm' %}:</td>
					<td>
						<input type="password" autocomplete="off" name="newpassword2" id="newpassword2" class="Field150" value="">
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
					  <input type="submit" name="SubmitButton" value="{% lang 'Update' %}" class="FormButton">
					</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				</table>
			  </td>
			</tr>
		</table>
		</td></tr></table>
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
		var passwordMeter = new PasswordStrengthMeter('newpassword', 'meterid', 'tipid', {{ PCIPasswordMinLen }});

		$('#frmResetPass').submit(function() {
			if($('#username').val() == '') {
				alert('{% lang 'NoUsername' %}');
				$('#username').focus();
				return false;
			}

			var pass1 = $('#newpassword').val();
			var pass2 = $('#newpassword2').val();
			if(pass1 == '') {
				alert('{% lang 'NoNewPassword' %}');
				$('#newpassword').focus();
				return false;
			}

			if(pass2 == '') {
				alert('{% lang 'NoNewPassword2' %}');
				$('#newpassword2').focus();
				return false;
			}

			if (pass1 != pass2) {
				alert('{% lang 'PasswordDontMatch' %}');
				$('#newpassword2').focus();
				return false;
			}

			// client side password validation (change password)
			var res = passwordMeter.validate(pass1);
			if (res.valid == false) {
				alert(res.msg);
				$('#newpassword').focus();
				return false;
			}

			// Everything is OK
			return true;
		});

		function sizeBox() {
			var w = $(window).width();
			var h = $(window).height();
			$('#box').css('position', 'absolute');
			$('#box').css('top', h/2-($('#box').height()/2)-50);
			$('#box').css('left', w/2-($('#box').width()/2));
		}

		$(document).ready(function() {
			sizeBox();
			$('#username').focus();
			passwordMeter.init();
		});

		$(window).resize(function() {
			sizeBox();
		});

	</script>

</body>
</html>