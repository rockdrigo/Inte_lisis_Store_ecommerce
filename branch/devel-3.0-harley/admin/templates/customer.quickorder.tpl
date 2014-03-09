<table width="100%" border="0" cellspacing="1" cellpadding="1">
	<tr>
		<td valign="top">
			<h5 style="margin: 0pt 10pt 5pt 0pt; display: inline;">{% lang 'OrderNo' %}{{ OrderPrefix|safe }}{{ OrderId|safe }}</h5>{{ OrderViewLink|safe }}<br />
			<table width="95%" border="0" align="right">
				<tr>
					<td width="50%" class="text">{% lang 'OrderStatus' %}:</td>
					<td class="text">{{ OrderStatus|safe }}</td>
				</tr>
				<tr>
					<td class="text">{% lang 'OrderTotal' %}:</td>
					<td class="text">{{ OrderTotal|safe }}</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'OrderDate1' %}:</td>
					<td class="text">{{ OrderDate|safe }}</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<br />