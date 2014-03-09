<a name="productsByInventoryAnchor"></a>
<div style="text-align:right">
	<div style="padding-bottom:10px">
		{% lang 'ItemsPerPage' %}:
		<select onchange="ChangeTaxByDatePerPage(this.options[this.selectedIndex].value)">
			<option {{ IsShowPerPage5|safe }} value="5">5</option>
			<option {{ IsShowPerPage10|safe }} value="10">10</option>
			<option {{ IsShowPerPage20|safe }} value="20">20</option>
			<option {{ IsShowPerPage30|safe }} value="30">30</option>
			<option {{ IsShowPerPage50|safe }} value="50">50</option>
			<option {{ IsShowPerPage100|safe }} value="100">100</option>
		</select>
	</div>
	{{ Paging|safe }}
</div>
<br />
<table width="100%" border=0 cellspacing=1 cellpadding=5 class="text">
	<tr class="Heading3">
		<td align="left">
			{% lang 'Period' %} &nbsp;
			{{ SortLinksPeriod|safe }}
		</td>
		<td align="left">
			{% lang 'TaxType' %} &nbsp;
			{{ SortLinksTaxType|safe }}
		</td>
		<td align="center">
			{% lang 'Rate' %} &nbsp;
			{{ SortLinksTaxRate|safe }}
		</td>
		<td align="center">
			{% lang 'NumberOfOrders' %} &nbsp;
			{{ SortLinksNumOrders|safe }}
		</td>
		<td align="left" width="100">
			{% lang 'TaxAmount' %} &nbsp;
			{{ SortLinksTaxAmount|safe }}
		</td>
	</tr>
	{{ TaxGrid|safe }}
</table>