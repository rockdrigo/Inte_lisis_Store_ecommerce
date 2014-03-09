<p>{% lang 'EbayListingEstimatedCostsCurrency' with ['currencyCode': currencyCode] %}</p>

<table style="width: 392px;">
	<tr class="Heading3">
		<td align="left">{{ lang.EbayListingFee }}</th>
		<td align="right">{{ lang.EbayListingCost }}</th>
	</tr>
	<tr class="GridRow" style="font-weight: bold;">
		<td>{{ lang.EbayListingPerItem }}</td>
		<td align="right">{{ perItem }}</td>
	</td>
	{% for fee in fees %}
		<tr class="GridRow">
			<td>&nbsp;&nbsp;&nbsp;{{ fee.name }}</td>
			<td align="right">{{ fee.fee }}</td>
		</tr>
	{% endfor %}
	<tr class="GridRow" style="font-weight: bold;">
		<td style="background-color: #dbf3d1;">{% lang 'EbayListingEstimatedCostsTotal' with ['count': itemCount] %}</td>
		<td align="right" style="background-color: #dbf3d1;">{{ grandTotal }}</td>
	</tr>
</table>