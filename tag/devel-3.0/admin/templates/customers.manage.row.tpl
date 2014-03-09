
	<tr id="tr{{ CustomerId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center" style="width:25px">
			<input type="checkbox" name="customers[]" value="{{ CustomerId|safe }}" class="exportSelectableItem" />
		</td>
		<td align="center" style="width:15px">
			<a href="#" onclick="OrderView('{{ CustomerId|safe }}');" style="display:{{ HideExpand|safe }}"><img id="expand{{ CustomerId|safe }}" class="ExpandLink"  src="images/plus.gif" align="left" width="19" height="16" title="{% lang 'ExpandCustQuickView' %}" style="vertical-align: middle;" border="0"></a>
		</td>
		<td align="center" style="width:18px">
			<img src="images/customer.gif" width="16" height="16">
		</td>
		<td class="{{ SortedFieldNameClass|safe }}">
			{{ Name|safe }}
		</td>
		<td class="{{ SortedFieldEmailClass|safe }}">
			{{ Email|safe }}
		</td>
		<td class="{{ SortedFieldPhoneClass|safe }}">
			{{ Phone|safe }}
		</td>
		<td class="{{ SortedFieldGroup|safe }}" style="display: {{ HideGroup|safe }}">
			{{ Group|safe }}
		</td>
		<td nowrap="nowrap" class="{{ SortedFieldStoreCreditClass|safe }}" style="display: {{ HideStoreCredit|safe }}">
			{{ StoreCredit|safe }}
		</td>
		<td class="{{ SortedFieldDateClass|safe }}">
			{{ Date|safe }}
		</td>
		<td class="{{ SortedFieldNumOrdersClass|safe }}">
			{{ NumOrders|safe }}
		</td>
		<td>
			{{ LoginLink|safe }}
			{{ ViewNotesLink|safe }}
			{{ EditCustomerLink|safe }}
		</td>
	</tr>
	<tr id="trQ{{ CustomerId|safe }}" style="display:none">
		<td colspan="2"></td>
		<td colspan="3" id="tdQ{{ CustomerId|safe }}" class="QuickView"></td>
		<td colspan="5"></td>
	</tr>
