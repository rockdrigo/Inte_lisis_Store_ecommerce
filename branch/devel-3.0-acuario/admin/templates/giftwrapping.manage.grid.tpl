<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0">
	<tr style="{{ HidePaging|safe }}">
		<td colspan="6" style="text-align: right; padding: 0 0 6px 0;" class="PagingNav">
			{{ Nav|safe }}
		</td>
	</tr>
	<tr class="Heading3">
		<td style="text-align: center; width: 10px;"><input type="checkbox" onclick="$(this.form).find('input:checkbox').not(':disabled').attr('checked', this.checked);" /></td>
		<td style="width: 10px;">&nbsp;</td>
		<td>
			{% lang 'WrapName' %}
			{{ SortLinksWrapName|safe }}
		</td>
		<td style="width: 150px;">
			{% lang 'Price' %}
			{{ SortLinksWrapPrice|safe }}
		</td>
		<td style="width: 80px; text-align: center;">
			{% lang 'Visible' %}
			{{ SortLinksWrapVisible|safe }}
		</td>
		<td style="width: 160px;">{% lang 'Action' %}</td>
	</tr>
	{{ GiftWrapGrid|safe }}
	<tr>
		<td colspan="6" style="text-align: right; padding: 6px 0;" class="PagingNav">
			{{ Nav|safe }}
		</td>
	</tr>
</table>