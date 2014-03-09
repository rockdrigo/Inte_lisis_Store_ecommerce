	<tr class="{{ ClassName|safe }}" onmouseover="this.className='{{ ClassName|safe }}Over'" onmouseout="this.className='{{ ClassName|safe }}'">
		<td width="20" align="center">
			<input type="checkbox" name="currencies[]" value="{{ CurrencyId|safe }}" {{ DeleteStatus|safe }}/>
		</td>
		<td align="center" style="width:18px;">
			<img src='images/tax.gif' />
		</td>
		<td>
			{{ CurrencyName|safe }}
		</td>
		<td>
			{{ CurrencyCode|safe }}
		</td>
		<td>
			<div id="currencyexchangerate-{{ CurrencyId|safe }}">{{ CurrencyRate|safe }}</div>
		</td>
		<td align="center">
			{{ Status|safe }}
		</td>
		<td>
			{{ CurrencyLinks|safe }}
		</td>
	</tr>
