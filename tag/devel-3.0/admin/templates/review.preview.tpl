
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" >
	<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">
		<HEAD>
			<TITLE>{% lang 'PreviewReview' %}</TITLE>
			<LINK href="Styles/windowstyles.css" type="text/css" rel="stylesheet">
		</HEAD>
		<BODY>
			<div class='Bar'>{% lang 'PreviewReview' %}
				(<A href="javascript:window.close()">{% lang 'CloseWindow' %}</A>)
			</div>
			<table id="Table" class="BodyContainer">
				<tr>
					<td class="Heading">
						{{ Title|safe }}
						<br />{{ Rating|safe }}
					</td>
				</tr>
				<tr>
					<td class="Intro">
						<hr size="1" noshade>
						<strong>{% lang 'Product' %}: {{ Product|safe }}</strong>
						<br /><strong>{% lang 'PostedBy' %}: {{ Author|safe }}</strong>
						<hr size="1" noshade>
					</td>
				</tr>
				<tr>
					<td class="Intro">
						{{ Review|safe }}
						<br />
					</td>
				</tr>
			</table>
		</BODY>
	</HTML>