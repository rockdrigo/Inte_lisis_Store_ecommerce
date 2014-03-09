<form method="post" action="#" onsubmit="Order.SaveShipment(); return false;" id="ShipmentDetails">
	<input type="hidden" name="orderId" value="{{ OrderId|safe }}" />
	<input type="hidden" name="addressId" value="{{ address.id }}" />
	<div id="ModalTitle">
		{% lang 'CreateShipmentFromOrder' %} #{{ OrderId|safe }} ({{ OrderDate|safe }})
	</div>
	<div id="ModalContent" style="min-height: 100px; max-height: 400px; overflow: auto;">
		<p class="MessageBox MessageBoxInfo">{% lang 'CreateShipmentIntro' %}</p>
		<br />
		<table class="GridPanel ShipmentTable" cellspacing="0" cellpadding="0">
			<tr class="Heading3">
				<td>{% lang 'ShipmentProduct' %}</td>
				<td style="width: 100px; text-align: center;">{% lang 'QtyToShip' %}</td>
			</tr>
			{{ ProductList|safe }}
		</table>
		<br />
		<strong style="color: #000">{% lang 'ShipmentOptions' %}</strong>
		<table cellspacing="5" cellpadding="0" border="0" width="100%">
			<tr>
				<td class="FieldLabel">{% lang 'ShippingMethod' %}:</td>
				<td>
					<select name="shipping_module" class="Field150">
						<option value="">{{ lang.xNone }}</option>
						{% for module, name in shippingModules %}
							<option value="{{ module }}" {% if module == shipping.module %}selected="selected"{% endif %}>{{ name }}</option>
						{% endfor %}
					</select>
					<input type="text" class="Field150" name="shipmethod" value="{{ shipping.method }}" />
				</td>
			</tr>

			<tr>
				<td class="FieldLabel">{% lang 'TrackingNumber' %}:</td>
				<td>
					<input type="text" class="Field300" name="shiptrackno" value="{{ TrackingNumber|safe }}" />
				</td>
			</tr>

			<tr>
				<td class="FieldLabel">{% lang 'ShipmentComments' %}:</td>
				<td>
					<textarea name="shipcomments" cols="10" rows="4" class="Field300">{{ OrderComments|safe }}</textarea>
				</td>
			</tr>

			<tr>
				<td class="FieldLabel">{% lang 'OrderStatus' %}:</td>
				<td style="padding-top: 4px;"><label><input type="checkbox" name="ordstatus" value="1" checked="checked" /> {% lang 'UpdateOrderStatus' %}</label></td>
			</tr>
		</table>
	</div>
	<div id="ModalButtonRow">
		<div class="FloatLeft"><input class="CloseButton" type="button" value="{% lang 'Close' %}" onclick="$.modal.close();" /></div>
		<img src="images/loading.gif" alt="" style="display: none" class="LoadingIndicator" />
		<input type="submit" class="Submit SubmitButton" value="{% lang 'CreateShipment' %}" />
	</div>
</form>