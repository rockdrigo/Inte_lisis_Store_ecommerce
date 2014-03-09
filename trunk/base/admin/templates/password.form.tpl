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
	<form action="index.php?ToDo=forgotPass&step=sendEmail" method="post" name="frmForgotPass" id="frmForgotPass">
	<div id="box">
		<table><tr><td style="border:solid 2px #DDD; padding:20px; background-color:#FFF; width:300px">
		<table>
		  <tr>
			<td class="Heading1">
				<a href="index.php">{{ AdminLogo|safe }}</a>
			</td>
		  </tr>
		  <tr>
			<td style="padding:0 0 0 10px">{{ Message|safe }}</td>
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
					<td>&nbsp;</td>
					<td>
					  <input type="submit" name="SubmitButton" value="{% lang 'SendEmail' %}" class="FormButton">
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

		$('#frmForgotPass').submit(function() {
			if($('#username').val() == '') {
				alert('{% lang 'NoUsername' %}');
				$('#username').focus();
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
		});

		$(window).resize(function() {
			sizeBox();
		});

	</script>

</body>
</html>