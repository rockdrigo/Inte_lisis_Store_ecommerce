<fieldset style="margin:10px">
	<legend>{% lang 'SMSNotificationTest' %}</legend>
	<div style="padding:10px">
		<table border="0">
			<tr>
				<td valign="top">
					<img src="images/{{ Icon|safe }}.gif" align="left" style="padding-right:5px" />
				</td>
				<td class="text" valign="top">
					{{ SMSResultMessage|safe }}
					<br /><br /><input type="button" value="{% lang 'CloseWindow' %}" onclick="window.close()" class="text" />
				</td>
			</tr>
		</table>
	</div>
	</legend>
</fieldset>