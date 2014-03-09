	<tr id="tr{{ ReturnId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center" style="width:23px">
			<input type="checkbox" name="returns[]" value="{{ ReturnId|safe }}" class="DeleteCheck">
		</td>
		<td align="center" style="width:15px">
			<a href="#" onclick="QuickReturnView('{{ ReturnId|safe }}'); return false;">
				<img id="expand{{ ReturnId|safe }}" src="images/plus.gif" class="ExpandLink" align="left" width="19" height="16" title="{% lang 'ExpandReturnQuickView' %}" border="0">
			</a>
		</td>
		<td align="center" style="width:18px">
			<img src='images/return.gif' height="16" width="16" />
		</td>
		<td class="{{ SortedFieldIdClass|safe }}">
			{{ ReturnId|safe }}
		</td>
		<td class="{{ SortedFieldReturnItemClass|safe }}">
			{{ ReturnQty|safe }}<a href="{{ ProductLink|safe }}" target="_blank">{{ ProdName|safe }}</a>
			{{ ReturnedProductOptions|safe }}
		</td>
		<td class="{{ SortedFieldOrderClass|safe }}">
			<a href="index.php?ToDo=viewOrders&amp;searchQuery={{ OrderId|safe }}">{% lang 'OrderNo' %}{{ OrderId|safe }}</a> {% if not return.orderid or return.order_deleted %}<span class="light">({% lang 'deleted' %})</span>{% endif %}
		</td>
		<td class="{{ SortedFieldCustClass|safe }}">
			<a href="index.php?ToDo=viewCustomers&amp;searchQuery={{ CustomerId|safe }}">{{ Customer|safe }}</a>
		</td>
		<td nowrap="nowrap" class="{{ SortedFieldDateClass|safe }}">
			{{ Date|safe }}
		</td>
		<td class="{{ SortedFieldStatusClass|safe }}">
			<select {{ ReturnStatusDisabled|safe }} name="return_status_{{ ReturnId|safe }}" id="status_{{ ReturnId|safe }}" class="Field returnStatusSelect">
				{{ ReturnStatusOptions|safe }}
			</select>
			<img id="ajax_status_{{ ReturnId|safe }}" src="images/ajax-loader.gif" style="visibility: hidden;" />
		</td>
		<td>
			{{ IssueCreditLink|safe }}
		</td>
	</tr>
	<tr id="trQ{{ ReturnId|safe }}" style="display:none">
		<td colspan="3">
			&nbsp;
		</td>
		<td colspan="6" id="tdQ{{ ReturnId|safe }}" class="QuickView">
		</td>
		<td colspan="1">&nbsp;</td>
	</tr>
