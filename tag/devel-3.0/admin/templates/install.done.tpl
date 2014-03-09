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
	<script type="text/javascript" src="../javascript/jquery.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/install.js?{{ JSCacheToken }}"></script>
</head>

<body>
	<div id="box">
		<br /><br /><br /><br />
		<table><tr><td style="border:solid 2px #DDD; padding:20px; background-color:#FFF; width:450px">
		<table>
		  <tr>
			<td class="Heading1">
				<img src="images/logo.jpg" />
			</td>
		  </tr>
		  <tr>
			<td style="padding:10px 0px 5px 0px">
				{% lang 'InstallationCompleted' %}
				<br /><br />
				<input type="button" value="{% lang 'LoginNow' %}" onclick="document.location.href='./'" style="font-size:11px" />
			</td>
		  </tr>
		</table>
		</td></tr></table>
		<div style="padding:10px; margin-bottom:20px; text-align:center">{{ Copyright|safe }}</div>
	</div>
	{{ HiddenImage|safe }}
</body>
</html>