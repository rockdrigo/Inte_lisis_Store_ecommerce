<tr id="tr{{ shipment.shipmentid }}" class="GridRow">
	{% if orderView == false %}
		<td style="text-align: center; width: 23px;">
			<input type="checkbox" name="shipments[]" value="{{ shipment.shipmentid }}" />
		</td>
	{% endif %}
	<td style="text-align: center; width: 15px;">
		<a href="#" onclick="Shipments.Expand('{{ shipment.shipmentid }}'); return false;">
			<img id="expand{{ shipment.shipmentid }}" src="images/plus.gif" align="left" width="19" class="ExpandLink" height="16" title="{% lang 'ExpandQuickView' %}" border="0" />
		</a>
	</td>
	<td style="text-align: center; width: 18px;">
		<img src="images/shipments.gif" alt="" />
	</td>
	<td class="{{ SortedFieldIdClass|safe }}">
		{{ shipment.shipmentid }}
	</td>
	<td class="{{ SortedFieldNameClass|safe }}">
		{{ shipment.shipshipfirstname }} {{ shipment.shipshiplastname }}
	</td>
	<td class="{{ SortedFieldDateClass|safe }}">
		{{ shipment.shipdate|date }}
	</td>
	<td style="width: 180px;" class="{{ SortedFieldTrackingNoClass|safe }}">
		<input type="text" name="trackingNo" class="Field70" value="{{ shipment.shiptrackno }}" />
		<input type="hidden" name="id" value="{{ shipment.shipmentid }}" />
		<input type="button" value="{{ lang.Save }}" class="saveTrackingNoButton" />
	</td>
	<td class="{{ SortedFieldOrderDateClass|safe }}">
		{{ shipment.shiporderdate|date }}
	</td>
	<td>
		<a title='{% lang 'PrintPackingSlipTitle' %}' href="#" onclick="Shipments.PrintPackingSlip('{{ shipment.shipmentid }}', '{{ shipment.shiporderid }}'); return false;">{% lang 'PrintPackingSlip' %}</a>
	</td>
</tr>
<tr id="trQ{{ shipment.shipmentid }}" style="display: none">
	<td>&nbsp;</td>
	<td colspan="{% if orderView %}7{% else %}8{% endif %}" class="QuickView">&nbsp;</td>
</tr>