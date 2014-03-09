<tr class="GridRow" onmouseover="$(this).addClass('GridRowOver');" onmouseout="$(this).removeClass('GridRowOver');">
	<td style="text-align: center; width: 1px;"><input type="checkbox" name="methods[]" class="check" value="{{ MethodId|safe }}" /></td>
	<td><img src="images/shippingmethod.gif" alt="" /></td>
	<td>{{ MethodName|safe }}</td>
	<td>{{ MethodModule|safe }}</td>
	<td style="text-align: center;">{{ MethodStatus|safe }}</td>
	<td style="width: 200px;">
		<a href="index.php?ToDo=editShippingZoneMethod&amp;methodId={{ MethodId|safe }}">{% lang 'Edit' %}</a>
		<a href="#" onclick="openwin('index.php?ToDo=testShippingProvider&methodId={{ MethodId|safe }}', {{ MethodId|safe }}, 550, {{ TestQuoteHeight|safe }}); return false;" style="{{ HideTestQuoteLink|safe }}">{% lang 'GetQuote' %}</a>
	</td>
</tr>