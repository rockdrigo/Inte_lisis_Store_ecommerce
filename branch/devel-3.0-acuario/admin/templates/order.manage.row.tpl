
	<tr id="tr{{ OrderId|safe }}" class="GridRow {% if order.deleted %}orderGridDeleted{% endif %}" onmouseover="$(this).addClass('GridRowOver').removeClass('GridRow')" onmouseout="$(this).addClass('GridRow').removeClass('GridRowOver')">
		<td align="center" style="width:23px">
			<input type="checkbox" name="orders[]" value="{{ OrderId1|safe }}" class="exportSelectableItem" />
		</td>
		<td align="center" style="width:15px">
			<a href="#" onclick="QuickView('{{ OrderId|safe }}'); return false;"><img id="expand{{ OrderId|safe }}" src="images/plus.gif" align="left" width="19" class="ExpandLink" height="16" title="{% lang 'ExpandQuickView' %}" border="0"></a>
		</td>
		<td align="center" style="width:18px">
			<img src="images/{{ OrderIcon|safe }}" width="16" height="16" />
		</td>
		<td class="orderGridOrderId {{ SortedFieldIdClass|safe }}">
			<span class="orderIdText" {% if order.deleted %}title="{% lang 'deletedOrderToolTip'  %}"{% endif %}>{{ OrderId|safe }}</span>
			{% if order.deleted %}<span class="orderDeletedText">({% lang 'deleted' %})</span>{% endif %}
		</td>
		<td colspan="{{ CustomerNameSpan|safe }}" class="{{ SortedFieldCustClass|safe }}">
			{{ CustomerLink|safe }}
		</td>
		<td class="{{ SortedFieldDateClass|safe }}">
			{{ Date|safe }}
		</td>
		<td id="order_status_column_{{ OrderId|safe }}" style="border-left-style: solid; border-left-width: 10px; width:165px;" class="{{ SortedFieldStatusClass|safe }} OrderStatus OrderStatus{{ OrderStatusId|safe }}" nowrap="nowrap">
			<select {% if order.deleted %}disabled="disabled" title="{% lang 'OrderDeletedStatusChangeNotice' %}"{% endif %} onclick="order_status_before_change=this.selectedIndex; status_box=this" id="status_{{ OrderId|safe }}" name="status_{{ OrderId|safe }}" class="Field" onchange="update_order_status('{{ OrderId|safe }}', this.options[this.selectedIndex].value, this.options[this.selectedIndex].text)">
				{{ OrderStatusOptions|safe }}
			</select>
			<img id="ajax_status_{{ OrderId|safe }}" src="images/ajax-blank.gif" />
			<div class="{{ PaymentStatusColor|safe }}" style="{{ HidePaymentStatus|safe }}">
				{{ PaymentStatus|safe }}
			</div>
		</td>
		<td style="text-align: center; display: {{ HideOrderMessages|safe }}" class="{{ SortedFieldMessageClass|safe }}">
			{{ MessageLink|safe }}
		</td>
		<td style="text-align: right;" class="{{ SortedFieldTotalClass|safe }}">
			{{ Total|safe }}
		</td>
		<td nowrap="nowrap" align="right">
			{{ NotesIcon|safe }}
			{{ CommentsIcon|safe }}
		</td>
		<td align="center" class="{{ FlagCellClass|safe }}" style="width: 18px; display: {{ HideCountry|safe }}">
			{{ OrderCountryFlag|safe }}
		</td>
		<td>
			<select name="order_options_{{ OrderId|safe }}" id="order_action_{{ OrderId|safe }}" onchange="Order.HandleAction('{{ OrderId|safe }}', $(this).val());">
				<option value="">-- {% lang 'Actions' %} --</option>
				<option value="editOrder">{% lang 'EditOrder' %}</option>
				<option value="printInvoice">{% lang 'PrintInvoice' %}</option>
				<option value="printPackingSlip">{% lang 'PrintPackingSlip' %}</option>
				<option value="orderNotes" class="{{ HasNotesClass|safe }}">{% lang 'OrderNotesLink' %}</option>
				{{ ShipItemsLink|safe }}
				{% if order.ordtotalshipped > 0 %}
					<option value="viewShipments">{{ lang.ViewShipments }}</option>
				{% endif %}
				{{ DelayedCaptureLink|safe }}
				{{ VoidLink|safe }}
				{{ RefundLink|safe }}
			</select>
		</td>
	</tr>
	<tr id="trQ{{ OrderId|safe }}" style="display:none">
		<td></td>
		<td colspan="12" id="tdQ{{ OrderId|safe }}" class="QuickView"></td>
	</tr>
