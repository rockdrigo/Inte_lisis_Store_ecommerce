<a name="ordersByAbandonAnchor"></a>
<div style="text-align:right">
	<div style="padding-bottom:10px">
		{% lang 'OrdersPerPage' %}:
		<select onchange="ChangeOrdersByAbandonPerPage(this.options[this.selectedIndex].value)">
			<option {{ IsShowPerPage5|safe }} value="5">5</option>
			<option {{ IsShowPerPage10|safe }} value="10">10</option>
			<option {{ IsShowPerPage20|safe }} value="20">20</option>
			<option {{ IsShowPerPage30|safe }} value="30">30</option>
			<option {{ IsShowPerPage50|safe }} value="50">50</option>
			<option {{ IsShowPerPage100|safe }} value="100">100</option>
			<option {{ IsShowPerPage200|safe }} value="200">200</option>
		</select>
	</div>
	{{ Paging|safe }}
</div>
<br />
<table width="100%" border=0 cellspacing=1 cellpadding=5 class="text">
	<tr class="Heading3">
		<td nowrap align="left">
			{% lang 'OrderID' %} &nbsp;
			{{ SortLinksOrderId|safe }}
		</td>

		<td nowrap align="left">
			{% lang 'Customer' %} &nbsp;
			{{ SortLinksCustomerName|safe }}
		</td>
		<td nowrap align="left">
			{% lang 'Email' %} &nbsp;
			{{ SortLinksCustomerEmail|safe }}
		</td>
		<td nowrap align="left">
			{% lang 'Phone' %} &nbsp;
			{{ SortLinksCustomerPhone|safe }}
		</td>
		<td nowrap align="left">
			{% lang 'Date' %} &nbsp;
			{{ SortLinksDate|safe }}
		</td>
		<td nowrap align="left">
			{% lang 'OrdersByAbandonTotalOrderAmount' %} &nbsp;
			{{ SortLinksOrderTotal|safe }}
		</td>
	</tr>
	{{ OrderGrid|safe }}
</table>
{{ JumpToOrdersByAbandonGrid|safe }}
<script type="text/javascript" language="javascript">//<![CDATA[
$(function(){
	$('#abandonedTotals > .MessageBox').show().html({{ AbandonedSummary|safe }});
});
//]]></script>
