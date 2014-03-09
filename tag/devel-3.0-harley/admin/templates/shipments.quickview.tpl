<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td valign="top" width="33%" class="QuickViewPanel">
			<h5>{% lang 'BillingDetails' %}</h5>
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td class="text" width="120" valign="top"=>{% lang 'CustomerDetails' %}:</td>
					<td class="text">
						{{ BillingAddress|safe }}
					</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'Email' %}:</td>
					<td class="text">{{ BillingEmail|safe }}</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'PhoneNumber' %}:</td>
					<td class="text">{{ BillingPhone|safe }}</td>
				</tr>
				<tr>
					<td class="text">{% lang 'ShipmentOrderId' %}</td>
					<td class="text"><a href="index.php?ToDo=viewOrders&amp;orderId={{ OrderId|safe }}" target="_blank">#{{ OrderId|safe }}</a>
				<tr>
					<td class="text" valign="top">{% lang 'ShipmentOrderDate' %}:</td>
					<td class="text">{{ OrderDate|safe }}</td>
				</tr>
				<tr style="{{ HideVendor|safe }}">
					<td class="text" valign="top">{% lang 'Vendor' %}:</td>
					<td class="text">{{ VendorName|safe }}</td>
				</tr>

			</table>
		</td>
		<td valign="top" width="33%" class="QuickViewPanel" style="padding-left:15px">
			<h5>{% lang 'ShippingDetails' %}</h5>
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td class="text" valign="top">{% lang 'CustomerDetails' %}:</td>
					<td class="text">
						{{ ShippingAddress|safe }}
					</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'Email' %}:</td>
					<td class="text">{{ ShippingEmail|safe }}</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'PhoneNumber' %}:</td>
					<td class="text">{{ ShippingPhone|safe }}</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'ShippingMethod' %}:</td>
					<td class="text">{{ ShippingMethod|safe }}</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'ShippingDate' %}:</td>
					<td class="text">{{ ShipmentDate|safe }}</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'TrackingNumber' %}:</td>
					<td class="text">{{ TrackingNo|safe }}</td>
				</tr>
			</table>
		</td>
		<td valign="top" width="33%" style="padding-left:10px">
			<h5>{% lang 'ShippedItems' %}</h5>
			{{ ProductsTable|safe }}
			<div style="{{ HideShipmentComments|safe }}">
				<h5 style="margin-top: 10px;">{% lang 'ShipmentComments' %}</h5>
				<div style="margin-left: 20px;">
					{{ ShipmentComments|safe }}
				</div>
			</div>
		</td>
	</tr>
</table>