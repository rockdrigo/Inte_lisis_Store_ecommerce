<li class="orderManageRow {% if order.deleted %}orderManageRowDeleted{% endif %}">
<a href="{{ ShopPath|safe }}/admin/index.php?ToDo=viewSingleOrder&amp;o={{ OrderId|safe }}" target="_self">
    <span class="orderId">{% lang 'Order' %} #{{ OrderId|safe }}</span> <div style="display:{{ HideMessages|safe }}" class="newIcon">{{ NumMessages|safe }}</div>
    <div class="orderStatus" style="width:150px; font-size:13px; background-color:{{ StatusCol|safe }}">
	    {{ OrderStatusText|safe }}
    </div>
    <div style="font-size:11px; color:gray; font-weight:normal">
	{% lang 'OrderedOn' %} {{ Date|safe }}<br />
	{% lang 'ByWord' %} {{ Customer|safe }}<br />
	{% lang 'TotalIs' %} {{ Total|safe }}
    </div>
</a>
</li>
