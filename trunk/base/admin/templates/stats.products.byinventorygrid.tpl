<a name="productsByInventoryAnchor"></a>
<div style="text-align:right">
	<div style="padding-bottom:10px">
		{% lang 'ProductsPerPage' %}:
		<select onchange="ChangeProductsByInventoryPerPage(this.options[this.selectedIndex].value)">
			<option {{ IsShowPerPage5|safe }} value="5">5</option>
			<option {{ IsShowPerPage10|safe }} value="10">10</option>
			<option {{ IsShowPerPage20|safe }} value="20">20</option>
			<option {{ IsShowPerPage30|safe }} value="30">30</option>
			<option {{ IsShowPerPage50|safe }} value="50">50</option>
			<option {{ IsShowPerPage100|safe }} value="100">100</option>
		</select>
	</div>
	{{ Paging|safe }}
	<br style="clear:left;" />
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
			<span onmouseover="ShowQuickHelp(this, '{% lang 'TrackingMethod' %}', '{% lang 'TrackingMethodHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'TrackingMethod' %}</span>
		</td>
		<td align="left">
			<span onmouseover="ShowQuickHelp(this, '{% lang 'StatsInStock' %}', '{% lang 'StatsInStockHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'StatsInStock' %}</span> &nbsp;
			{{ SortLinksStock|safe }}
		</td>
		<td align="left">
			{% lang 'Action' %}</span>
		</td>
	</tr>
	{{ OrderGrid|safe }}
</table>
