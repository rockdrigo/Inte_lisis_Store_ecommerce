<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'" id="CustomerAddress{{ AddressId|safe }}">
	<td width="20" align="center">
		<input type="checkbox" name="addresses[]" value="{{ AddressId|safe }}" /><input type="hidden" name="addressDisplayStatus[]" value="display" />
	</td>
	<td style="width:15%;">
		{{ FullName|safe }}
	</td>
	<td style="width:10%;">
		{{ Phone|safe }}
	</td>
	<td style="width:55%;">
		{{ StreetAddress|safe }}<br />
		{{ City|safe }}, {{ State|safe }} {{ PostCode|safe }}<br />
		{{ Country|safe }} {{ CountryImg|safe }}
	</td>
	<td>
		{{ EditCustomerLink|safe }}
		{{ DeleteCustomerLink|safe }}
	</td>
</tr>