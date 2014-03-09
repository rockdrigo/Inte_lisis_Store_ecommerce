
	<tr id="tr{{ CustomerId|safe }}" class="GridRow {{ GridRowSel|safe }}" onmouseover="this.className='GridRowOver {{ GridRowSelOver|safe }}'" onmouseout="this.className='GridRow {{ GridRowSel|safe }}'">
		<td align="center" style="width:25px">
			<input type="checkbox" name="groups[]" value="{{ CustomerGroupId|safe }}">
		</td>
		<td align="center" style="width:18px">
			<img src="images/customer_group.gif" width="16" height="16">
		</td>
		<td class="{{ SortedFieldGroupNameClass|safe }}">
			{{ GroupName|safe }} {{ DefaultText|safe }}
		</td>
		<td class="{{ SortedFieldDiscountClass|safe }}">
			{{ Discount|safe }}
		</td>
		<td class="{{ SortedFieldDiscountRulesClass|safe }}">
			{{ DiscountRules|safe }}
		</td>
		<td class="{{ SortedFieldCustomersInGroupClass|safe }}">
			{{ CustomersInGroup|safe }}
		</td>
		<td class="{{ SortedFieldNumOrdersClass|safe }}">
			{{ EditLink|safe }}
		</td>
	</tr>
