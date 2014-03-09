<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" style="width:100%;">
	<tr style="text-align: right">
		<td colspan="9" style="padding:6px 0px 6px 0px" class="PagingNav">
			{{ Nav|safe }}
		</td>
	</tr>
	<tr class="Heading3">
		<td align="center"><input type="checkbox" onclick="$(this.form).find('input:checkbox').not(':disabled').attr('checked', this.checked);" /></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td nowrap="nowrap" style="width: 120px;">
			{% lang 'PaymentId' %} &nbsp;
			{{ SortLinksId|safe }}
		</td>
		<td>
			{% lang 'Vendor' %} &nbsp;
			{{ SortLinksVendor|safe }}
		</td>
		<td nowrap="nowrap">
			{% lang 'SalesPeriod' %} &nbsp;
			{{ SortLinksDate|safe }}
		</td>
		<td nowrap="nowrap" style="width: 150px;">
			{% lang 'PaymentAmount' %} &nbsp;
			{{ SortLinksAmount|safe }}
		</td>
		<td nowrap="nowrap" style="width: 120px;">
			{% lang 'DatePaid' %} &nbsp;
			{{ SortLinksPaymentDate|safe }}
		</td>
		<td nowrap="nowrap">
			{% lang 'PaymentMethod' %} &nbsp;
			{{ SortLinksMethod|safe }}
		</td>
	</tr>
	{{ PaymentGrid|safe }}
	<tr style="text-align: right">
		<td colspan="9" style="padding:6px 0px 6px 0px" class="PagingNav">
			{{ Nav|safe }}
		</td>
	</tr>
</table>