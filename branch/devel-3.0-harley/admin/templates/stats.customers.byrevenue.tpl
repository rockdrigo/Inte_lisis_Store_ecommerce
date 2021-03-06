<a name="customersByRevenueAnchor"></a>
<div style="text-align:right; display: {{ HideStatsRows|safe }}">
	<div style="padding-bottom:10px">
		{% lang 'CustomersPerPage' %}:
		<select onchange="ChangeCustomersByRevenuePerPage(this.options[this.selectedIndex].value)">
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
			{% lang 'StatsCustomerName' %} &nbsp;
			{{ SortLinksCust|safe }}
		</td>
		<td align="left">
			{% lang 'StatsEmail' %} &nbsp;
			{{ SortLinksEmail|safe }}
		</td>
		<td align="left">
			{% lang 'StatsDateJoined' %} &nbsp;
			{{ SortLinksDate|safe }}
		</td>
		<td align="right">
			<span onmouseover="ShowQuickHelp(this, '{% lang 'StatsOrders' %}', '{% lang 'RevenuePerCustomerOrdersHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'StatsOrders' %}</span> &nbsp;
			{{ SortLinksNumOrders|safe }}
		</td>
		<td align="right">
			<span onmouseover="ShowQuickHelp(this, '{% lang 'StatsAmountSpent' %}', '{% lang 'RevenuePerCustomerAmountSpentHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'StatsAmountSpent' %}</span> &nbsp;
			{{ SortLinksAmountSpent|safe }}
		</td>
	</tr>
	{{ CustomerGrid|safe }}
</table>
{{ JumpToRevenuePerCustomerGrid|safe }}
