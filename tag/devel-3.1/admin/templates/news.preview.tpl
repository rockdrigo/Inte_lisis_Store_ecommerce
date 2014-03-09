


	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" >


	<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">


		<HEAD>


			<TITLE>{% lang 'PreviewNewsPost' %}</TITLE>


			<LINK href="Styles/windowstyles.css" type="text/css" rel="stylesheet">


		</HEAD>


		<BODY>


			<div class='Bar'>{% lang 'PreviewNewsPost' %}


				(<A href="javascript:window.close()">{% lang 'CloseWindow' %}</A>)


			</div>


			<table id="Table" class="BodyContainer">


				<tr>


					<td class="Heading">


						{{ Title|safe }}


					</td>


				</tr><tr>


				<tr>


					<td class="Small">


						{% lang 'NewsPublished' %} {{ NewsDate|safe }}


					</td>


				</tr><tr>


					<td class="Text">


						<br />{{ Content|safe }}


					</td>


				</tr>


			</table>


		</BODY>


	</HTML>