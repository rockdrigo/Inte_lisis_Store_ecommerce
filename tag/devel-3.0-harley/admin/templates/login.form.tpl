<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11">
<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">
<head>
	<title>{% lang 'ControlPanel' %}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={{ CharacterSet }}" />
	<meta name="robots" content="noindex, nofollow" />
	<style type="text/css">
		@import url("Styles/styles.css");
		@import url('Styles/tabmenu.css');
		@import url("Styles/iselector.css");
	</style>
	<link rel="SHORTCUT ICON" href="favicon.ico" />
	<!--[if IE]>
	<style type="text/css">
		@import url("Styles/ie.css");
	</style>
	<![endif]-->
	<script type="text/javascript" src="../javascript/jquery.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/menudrop.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/common.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="../javascript/iselector.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="../javascript/thickbox.js?{{ JSCacheToken }}"></script>
	<link rel="stylesheet" href="Styles/thickbox.css?{{ JSCacheToken }}" type="text/css" media="screen" />
	<script type="text/javascript">
		var url = 'remote.php';
	</script>
</head>

<body>
	<form action="{{ ShopPath|safe }}/admin/index.php?ToDo={{ SubmitAction|safe }}" method="post" name="frmLogin" id="frmLogin">
	<div id="box">
		<table><tr><td style="border:solid 2px #DDD; padding:20px; background-color:#FFF; width:300px">
		<table>
		  <tr>
			<td class="Heading1">
				<a href="index.php">{{ AdminLogo|safe }}</a>
			</td>
		  </tr>
		  <tr>
			<td style="padding:0 0 0 5px">{{ Message|safe }}</td>
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
				  <td nowrap style="padding:0px 10px 0px 10px">{% lang 'PasswordLabel' %}</td>
				  <td>
					<input type="password" autocomplete="off" name="password" id="password" class="Field150" value="{{ Password|safe }}">
				  </td>
				</tr>
				{% if ShowRememberMe %}
				<tr>
				  <td nowrap>&nbsp;</td>
				  <td>&nbsp;<input type="checkbox" name="remember" id="remember" value="ON" style="margin-left:-0px"> <label for="remember">{% lang 'RememberMe' %}</label>
				  </td>
				</tr>
				{% endif %}
				  <tr>
					<td>&nbsp;</td>
					<td>
					  <input type="submit" name="SubmitButton" id="LoginSubmitButton" value="{% lang 'Login' %}" class="FormButton">
					  &nbsp;&nbsp;{% lang 'ForgotPassLink' %}
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

	<script type="text/javascript">

		$('#frmLogin').submit(function() {
			var f = document.frmLogin;

			if(f.username.value == '')
			{
				alert('{% lang 'NoUsername' %}');
				f.username.focus();
				f.username.select();
				return false;
			}

			if(f.password.value == '')
			{
				alert('{% lang 'NoPassword' %}');
				f.password.focus();
				f.password.select();
				return false;
			}

			// Everything is OK
			return true;
		});

		function sizeBox() {
			var w = $(window).width();
			var h = document.getElementsByTagName('html')[0].clientHeight
			$('#box').css('position', 'absolute');
			$('#box').css('top', h/2-($('#box').height()/2)-50);
			$('#box').css('left', w/2-($('#box').width()/2));
		}

		$(document).ready(function() {
			sizeBox();
			$('#username').focus();
		});

		$(window).resize(function() {
			sizeBox();
		});

	</script>

</body>
</html>