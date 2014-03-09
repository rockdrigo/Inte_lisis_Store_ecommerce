	<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td width="20" align="center">
			<input type="checkbox" name="taxrates[]" value="{{ TaxRateId|safe }}" />
		</td>
		<td align="center" style="width:18px;">
			<img src='images/tax.gif' />
		</td>
		<td>
			{{ TaxName|safe }}
		</td>
		<td>
			{{ TaxRate|safe }}%
		</td>
		<td style="width:300px">
			{{ AppliesTo|safe }}
		</td>
		<td align="center">
			{{ Status|safe }}
		</td>
		<td>
			{{ EditRateLink|safe }}
		</td>
	</tr>
