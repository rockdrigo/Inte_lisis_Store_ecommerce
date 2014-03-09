<form action="index.php?ToDo=testShippingProviderQuote" method="post" onsubmit="return ValidateForm(CheckQuoteForm)">
<input type="hidden" name="module" value="{{ ModuleFile|safe }}">
<fieldset style="margin:10px">
	<legend>{% lang 'NEmailNotificationTest' %}</legend>
	<div style="padding:10px">
		<table border="0">
			<tr>
				<td valign="top">
					<img src="images/{{ Icon|safe }}.gif" align="left" style="padding-right:5px" />
				</td>
				<td class="text" valign="top">
					{{ EmailResultMessage|safe }}
					<br /><br /><input type="button" value="{% lang 'CloseWindow' %}" onclick="window.close()" class="text" />
				</td>
			</tr>
		</table>
	</div>
</legend>

