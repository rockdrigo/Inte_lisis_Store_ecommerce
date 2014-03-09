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
</head>

<body>
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
		</table>
		</td></tr></table>
	</div>

	<script type="text/javascript">
		function sizeBox() {
			var w = $(window).width();
			var h = $(window).height();
			$('#box').css('position', 'absolute');
			$('#box').css('top', h/2-($('#box').height()/2)-50);
			$('#box').css('left', w/2-($('#box').width()/2));
		}

		$(document).ready(function() {
			sizeBox();
		});

		$(window).resize(function() {
			sizeBox();
		});
	</script>
</body>
</html>