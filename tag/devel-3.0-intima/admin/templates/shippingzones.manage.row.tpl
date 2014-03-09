<tr class="{{ ZoneClass|safe }}" onmouseover="$(this).addClass('{{ ZoneClass|safe }}Over');" onmouseout="$(this).removeClass('{{ ZoneClass|safe }}Over');">
	<td style="text-align: center;"><input type="checkbox" class="check" {{ ZoneDeleteCheckbox|safe }} name="zones[]" value="{{ ZoneId|safe }}" /></td>
	<td><img src="images/zone.gif" alt="" /></td>
	<td>{{ ZoneName|safe }}</td>
	<td>{{ ZoneType|safe }}</td>
	<td style="text-align: center;">{{ ZoneStatus|safe }}</td>
	<td>
		<a href="index.php?ToDo=editShippingZone&amp;zoneId={{ ZoneId|safe }}">{% lang 'EditSettings' %}</a>
		<a href="index.php?ToDo=editShippingZone&amp;zoneId={{ ZoneId|safe }}&amp;currentTab=1">{% lang 'EditMethods' %}</a>
		<a href="index.php?ToDo=copyShippingZone&amp;zoneId={{ ZoneId|safe }}">{% lang 'Copy' %}</a>
		<a href="index.php?ToDo=deleteShippingZones&amp;zones[]={{ ZoneId|safe }}" onclick="return ConfirmDeleteZone();" style="{{ HideDeleteZone|safe }}">{% lang 'Delete' %}</a>
	</td>
</tr>