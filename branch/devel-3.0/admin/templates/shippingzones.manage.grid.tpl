<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0">
	<tr style="{{ HidePaging|safe }}">
		<td colspan="6" style="text-align: right; padding: 6px 0;" class="PagingNav">
			{{ Nav|safe }}
		</td>
	</tr>
	<tr class="Heading3">
		<td style="text-align: center; width: 10px;"><input type="checkbox" onclick="$(this.form).find('input:checkbox').not(':disabled').attr('checked', this.checked);" /></td>
		<td style="width: 10px;">&nbsp;</td>
		<td>{% lang 'ZoneName' %}</td>
		<td style="width: 120px;">{% lang 'ZoneType' %}</td>
		<td style="width: 50px; text-align: center;">{% lang 'Status' %}</td>
		<td style="width: 220px;">{% lang 'Action' %}</td>
	</tr>
	{{ ZonesGrid|safe }}
	<tr>
		<td colspan="6" style="text-align: right; padding: 6px 0;" class="PagingNav">
			{{ Nav|safe }}
		</td>
	</tr>
</table>