<a name="productsByNumSoldAnchor"></a>
<div style="text-align:right">
	<div style="padding-bottom:10px">
		{% lang 'ProductsPerPage' %}:
		<select onchange="ChangeProductsByNumSoldPerPage(this.options[this.selectedIndex].value)">
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
			{% lang 'ProductID' %} &nbsp;
			{{ SortLinksProductId|safe }}
		</td>
		<td align="left">
			{% lang 'ProductSKU' %} &nbsp;
			{{ SortLinksCode|safe }}
		</td>
		<td align="left">
			{% lang 'Item' %} &nbsp;
			{{ SortLinksName|safe }}
		</td>
		<td align="left">
			<span onmouseover="ShowQuickHelp(this, '{% lang 'UnitsSold' %}', '{% lang 'OrdersByItemsSoldUnitsSoldHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'UnitsSold' %}</span> &nbsp;
			{{ SortLinksUnitsSold|safe }}
		</td>
		<td align="left">
			{% lang 'StatsRevenue' %} &nbsp;
			{{ SortLinksRevenue|safe }}
		</td>
		<td align="left">
			<span onmouseover="ShowQuickHelp(this, '{% lang 'StatsProfit' %}', '{% lang 'ProductsByNumSoldProfitHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'StatsProfit' %}</span> &nbsp;
			{{ SortLinksProfit|safe }}
		</td>
	</tr>
	{{ OrderGrid|safe }}
</table>
{{ JumpToOrdersByItemsSoldGrid|safe }}
