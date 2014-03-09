<table width="100%" border=0 cellspacing=1 cellpadding=5 class="text">
	<tr class="Heading3">
		<td nowrap align="left">
			{% lang 'StatsRevenue' %}
		</td>
		<td nowrap align="left">
			{% lang 'NumberOfOrders' %}
		</td>
	</tr>
	{{ OrderGrid|safe }}
</table>
