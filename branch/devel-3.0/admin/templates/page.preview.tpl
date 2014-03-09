<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{% lang 'PreviewPage' %}</title>
	<link href="Styles/windowstyles.css" type="text/css" rel="stylesheet" />
	{{ Stylesheets|safe }}
</head>
<body style="background-image: none;">
	<div class='Bar' style="margin: 0;">{% lang 'PreviewPage' %}
		(<A href="javascript:window.close()">{% lang 'CloseWindow' %}</A>)
	</div>

	<div class="Content Wide" style="margin: 0; padding-top: 0;" id="LayoutColumn2">
		<div class="Block Moveable" id="PageContent">
			<h2 style="margin-top: 0;">{{ PageTitle|safe }}</h2>
			<div class="BlockContent">
				{{ PageContent|safe }}
			</div>
		</div>
	</div>
</body>
</html>