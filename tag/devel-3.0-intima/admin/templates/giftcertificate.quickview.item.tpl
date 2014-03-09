<table width="100%" border="0" cellspacing="1" cellpadding="1">
	<tr>
		<td valign="top">
			<h5 style="margin: 0pt 0pt 5pt 10px"><a href="index.php?ToDo=viewOrders&amp;searchQuery={{ OrderId|safe }}" target="_blank">{% lang 'OrderNo' %}{{ OrderPrefix|safe }}{{ OrderId|safe }}</a></h5>
			<table width="95%" border="0" align="right">
				<tr>
					<td width="150" class="text">{% lang 'OrderDate1' %}:</td>
					<td class="text">{{ OrderDate|safe }}</td>
				</tr>
				<tr>
					<td width="150" class="text">{% lang 'Customer' %}:</td>
					<td class="text"><a href="index.php?ToDo=viewCustomers&amp;searchQuery={{ CustomerId|safe }}" target="_blank">{{ CustomerName|safe }}</a></td>
				</tr>
				<tr>
					<td class="text">{% lang 'BalanceUsed' %}:</td>
					<td class="text">{{ BalanceUsed|safe }} ({{ BalanceRemaining|safe }} {% lang 'Remaining' %})</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<br />