<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0">
	<tr style="{{ HidePaging|safe }}">
		<td colspan="5" style="text-align: right; padding: 0 0 6px 0;" class="PagingNav">
			{{ Nav|safe }}
		</td>
	</tr>
	<tr class="Heading3">
		<td style="text-align: center; width: 10px;"><input type="checkbox" onclick="$(this.form).find('input:checkbox').not(':disabled').attr('checked', this.checked);" /></td>
		<td style="width: 10px;">&nbsp;</td>
		<td>{% lang 'VendorName' %}</td>
		<td style="width: 300px;">{% lang 'VendorUsers' %}</td>
		<td style="width: 160px;">{% lang 'Action' %}</td>
	</tr>
	{{ VendorsGrid|safe }}
	<tr>
		<td colspan="5" style="text-align: right; padding: 6px 0;" class="PagingNav">
			{{ Nav|safe }}
		</td>
	</tr>
</table>