{% import 'order.quickview.tpl' as helper %}
{% import 'macros/util.tpl' as util %}

{% macro customFormField(field) %}
	<tr>
		<td class="text" width="120" valign="top">
			{{ field.label}}:
		</td>
		<td class="text">
			{{ field.value|join('<br />') }}
		</td>
	</tr>
{% endmacro %}

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td valign="top" width="33%" class="QuickViewPanel" style="border: 0">
			<h5>{{ lang.BillingDetails }}</h5>
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td class="text" width="120" valign="top">{% lang 'CustomerDetails' %}:</td>
					<td class="text">
						{{ util.address(billingAddress) }}
					</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'Email' %}:</td>
					<td class="text">
						{% if order.custconemail %}
							<a href="mailto:{{ order.custconemail }}">{{ order.custconemail }}</a>
						{% elseif order.ordbillemail %}
							<a href="mailto:{{ order.ordbillemail }}">{{ order.ordbillemail }}</a>
						{% else %}
							{{ lang.NA }}
						{% endif %}
					</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'PhoneNumber' %}:</td>
					<td class="text">
						{% if order.ordbillphone %}
							{{ order.ordbillphone }}
						{% elseif order.custconphone %}
							{{ order.ordcustconphone }}
						{% else %}
							{{ lang.NA }}
						{% endif %}
					</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'OrderDate1' %}:</td>
					<td class="text">
						{{ order.orddate|date('d M Y H:i:s') }}
					</td>
				</tr>
				<tr>
					<td class="text" valign="top">{% lang 'IPAddress' %}:</td>
					<td class="text">
						<a href="http://ws.arin.net/rest/ip/{{ order.ordipaddress }}" target="_blank">
							{{ order.ordipaddress }}
						</a>
					</td>
				</tr>

				{% if vendor %}
					<tr>
						<td class="text">{{ lang.Vendor}}:</td>
						<td class="text">{{ vendor.vendorname }}</td>
					</tr>
				{% endif %}

				<tr>
					<td class="text" valign="top">
						{{ lang.PaymentMethod }}
					</td>
					<td class="text">
						{% if order.orderpaymentmethod == false %}
							{{ lang.NA }}
						{% elseif order.orderpaymentmethod != "storecredit" and order.orderpaymentmethod != "giftcertificate" %}
							{{ order.orderpaymentmethod }}
						{% endif %}

						{% if order.ordstorecreditamount > 0 %}
							<div>
								{{ lang.PaymentStoreCredit }}
								({{ order.ordstorecreditamount|currencyFormatPrice(order.orddefaultcurrencyid) }})
							</div>
						{% endif %}

						{% if order.ordgiftcertificateamount > 0 %}
							<div>
								{% lang 'PaymentGiftCertificates' with [
									'orderId': order.orderid
								] %}
								({{ order.ordgiftcertificateamount|currencyFormatPrice(order.orddefaultcurrencyid) }})
							</div>
						{% endif %}
					</td>
				</tr>

				{% if order.ordpayproviderid %}
					<tr>
						<td class="text" valign="top">
							{{ lang.TransactionId }}:
						</td>
						<td class="text">
							{{ order.ordpayproviderid }}
						</td>
					</tr>
				{% endif %}

				{% if order.ordpaymentstatus or paymentMessage %}
					<tr>
						<td class="text" valing="top">{{ lang.PaymentStatus }}:</td>
						<td class="text">
							{% if order.ordpaymentstatus %}
								{{ order.ordpaymentstatus|capitalize }}
							{% endif %}

							<div>{{ paymentMessage|safe }}</div>
						</td>
					</tr>
				{% endif %}
			</table>

			{{ orderExtraInfo|safe }}

			{% if billingCustomFields %}
				<div style="margin-top: 10px">
					<h5> {{ lang.BillingDetailsQuickView }}</h5>
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						{% for field in billingCustomFields %}
							{{ helper.customFormField(field) }}
						{% endfor %}
					</table>
				</div>
			{% endif %}

			{% if order.ordcustmessage %}
				<h5 style="margin-top: 10px">{{ lang.OrderComments }}</h5>
				<div style="margin-left: 20px">
					{{ order.ordcustmessage|nl2br }}
				</div>
			{% endif %}
		</td>

		<td valign="top" width="67%" class="QuickViewPanel" style="border: 0; padding-left: 15px; border-left: 3px solid #B8E6A6;">
			{% for addressId, orderAddress in orderAddresses %}
				<div class="orderQuickViewShippingBlock">
					{% if addressId == 0 %}
						<h5>{{ lang.DigitalItemDetails }}</h5>
					{% else %}
						<span class="orderQuickViewShipItemsLink">
							{% if orderAddress.shipping.total_shipped < orderAddress.address.total_items %}
								{% if order.deleted %}
									<span class="Disabled" title="{% lang 'deletedOrderToolTip' %}">{% lang 'ShipItems' %}</span>
								{% else %}
									<a href="#" onclick="Order.ShipItems({{ order.orderid }}, {{ addressId }}); return false;">{{ lang.ShipItems }}</a>
								{% endif %}
							{% endif %}
							{% if orderAddress.shipping.total_shipped > 0 %}
								<a href="#" onclick="Order.viewShipments({{ order.orderid }}); return false">{{ lang.ViewShipments }}</a>
							{% endif %}
						</span>
						<h5>{% lang 'ShippingDetails' %}</h5>
					{% endif %}
					<div class="orderQuickViewShippingBlockDetails">
						{% if addressId == 0 %}
							<p style="padding: 5px; background-color: lightyellow" class="text" colspan="2">
								{{ lang.DigitalItemsNotice }}
							</p>
						{% else %}
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td class="text" valign="top" width="120">{% lang 'CustomerDetails' %}:</td>
									<td class="text">
										{{ util.address(orderAddress.address) }}
									</td>
								</tr>

								<tr>
									<td class="text" valign="top">{% lang 'Email' %}:</td>
									<td class="text">
										{% if order.custconemail %}
											<a href="mailto:{{ order.custconemail }}">{{ order.custconemail }}</a>
										{% elseif orderAddress.address.email %}
											<a href="mailto:{{ orderAddress.address.email }}">{{ orderAddress.address.email }}</a>
										{% else %}
											{{ lang.NA }}
										{% endif %}
									</td>
								</tr>

								<tr>
									<td class="text" valign="top">{% lang 'PhoneNumber' %}:</td>
									<td class="text">
										{% if orderAddress.address.phone %}
											{{ orderAddress.address.phone }}
										{% elseif order.custconphone %}
											{{ order.ordcustconphone }}
										{% else %}
											{{ lang.NA }}
										{% endif %}
									</td>
								</tr>

								{% if orderAddress.address.shipping_zone_name %}
									<tr>
										<td class="text" valign="top">{% lang 'ShippingZone' %}:</td>
										<td class="text">
											<a href="index.php?ToDo=editShippingZone&amp;zoneId={{ orderAddress.address.shipping_zone_id }}">{{ orderAddress.address.shipping_zone_name }}</a>
										</td>
									</tr>
								{% endif %}

								<tr>
									<td class="text" valign="top">{% lang 'ShippingMethod' %}:</td>
									<td class="text">
										{{ orderAddress.shipping.method }}
									</td>
								</tr>

								<tr>
									<td class="text" valign="top">{% lang 'ShippingCost' %}:</td>
									<td class="text">
										{{ orderAddress.shipping.cost|currencyFormatPrice(order.orddefaultcurrencyid) }}
									</td>
								</tr>

								<tr>
									<td class="text" valign="top">{% lang 'ShippingDate' %}:</td>
									<td class="text">
										{% if order.orddateshipped %}
											{{ order.orddateshipped|date('DisplayDateFormat') }}
										{% else %}
											{{ lang.NA }}
										{% endif %}
									</td>
								</tr>

								{% if order.ordtrackingno %}
									<!-- Kept for legacy reasons for orders that may still have a tracking number -->
									<tr>
										<td class="text" valign="top">{{ lang.OrdTrackingNo }}:</td>
										<td class="text">{{ order.ordtrackingno }}</td>
									</tr>
								{% endif %}
							</table>

							{% if orderAddress.customFields %}
								<div style="margin-top: 10px">
									<h5> {{ lang.ShippingDetailsQuickView }}</h5>
									<table width="100%" border="0" cellspacing="0" cellpadding="0">
										{% for field in orderAddress.customFields %}
											{{ helper.customFormField(field) }}
										{% endfor %}
									</table>
								</div>
							{% endif %}
						{% endif %}
					</div>
					<div class="orderQuickViewShippingBlockItems">
						<table width="95%" align="center" border="0" cellspacing="0" cellpadding="2">
							{% for product in orderAddress.products %}
								<tr>
									<td style="padding-left: 12px; padding-top: 5px" width="70%" class="text">
										{% if product.ordprodrefunded == product.ordprodqty %}<del>{% endif %}

										{{ product.ordprodqty }}
										x

										{% if product.prodname %}
											<a href="{{ product.prodlink }}" target="_blank">{{ product.ordprodname }}</a>
										{% else %}
											{{ product.ordprodname }}
										{% endif %}

										{% if product.ordprodrefunded == product.ordprodqty %}</del>{% endif %}

										{% if product.ordprodsku %}
											<br /><em>{{ product.ordprodsku }}</em>
										{% endif %}

										{% if product.options %}
											<blockquote sty;e="padding-left: 10px; margin: 0">
												{% for name, value in product.options %}
													<div>{{ name }}: {{ value }}</div>
												{% endfor %}
											</blockquote>
										{% endif %}

										{% if product.preorder_message %}
											<br />
											<em>({{ product.preorder_message }})</em>
										{% endif %}
									</td>
									<td class="text" class="orderQuickViewPriceColumn" align="right">
										{{ product.total|currencyFormatPrice(order.orddefaultcurrencyid) }}
									</td>
								</tr>

								{% if product.ordprodwrapname %}
									<tr>
										<td style="padding: 2px 0 2px 15px">
											{{ lang.GiftWrapping }}: {{ product.ordprodwrapname }}
											[<a href="#" onclick="$.iModal({type: 'ajax', url: 'remote.php?remoteSection=orders&w=viewGiftWrappingDetails&orderprodid={{ product.orderprodid }}' }); return false;">
												{{ lang.ViewDetails }}
											</a>]
										</td>
									</tr>
								{% endif %}

								{% if product.ordprodeventdate %}
									<tr>
										<td style="padding: 2px 0 2px 15px">
											{{ product.ordprodeventname }}: {{ product.ordprodeventdate }}
										</td>
									</tr>
								{% endif %}

								{% if product.configurable_fields %}
									<tr>
										<td class="text" colspan="2" style="padding-left: 20px">
											<strong>{{ lang.ConfigurableFields }}</strong>
											<br />
											<dl class="HorizontalFormContainer">
												{% for field in product.configurable_fields %}
													<dt>{{ field.fieldname }}:</dt>
													<dd>
														{% if field.fieldtype == 'file' %}
															<a href="{{ ShopPath }}/viewfile.php?orderprodfield={{ field.orderfieldid }}" target="_blank">
																{{ field.originalfilename }}
															</a>
														{% elseif field.fieldtype == 'checkbox' %}
															{{ lang.Checked }}
														{% elseif field.textcontents|length > 50 %}
															<a href="#" onclick="Order.LoadOrderProductFieldData({{ order.orderid }}); return false">
																<em>{{ lang.More }}</em>
															</a>
														{% else %}
															{{ field.textcontents }}
														{% endif %}
													</dd>
												{% endfor %}
											</dl>
										</td>
									</tr>
								{% endif %}

								{% if product.ordprodqtyshipped or product.ordprodrefunded %}
									<tr>
										<td class="text" colspan="2" style="padding-left: 20px">
											{% if product.ordprodqtyshipped %}
												<div class="Shipped">
													{% lang 'OrderProductsShippedX' with [
														'quantity': product.ordprodqtyshipped
													] %}
												</div>
											{% endif %}

											{% if product.ordprodrefunded %}
												<div class="Refunded">
													{% if product.ordprodrefunded == product.ordprodqty %}
														{{ lang.OrderProductRefunded }}
													{% else %}
														{% lang 'OrderProductsRefundedX' with [
															'quantity': product.ordprodrefunded
														] %}
													{% endif %}
												</div>
											{% endif %}
										</td>
									</tr>
								{% endif %}
							{% endfor %}
						</table>
					</div>
					<br style="clear: both" />
				</div>
			{% endfor %}

			<!-- Total Rows -->
			<div class="orderQuickViewShippingBlockLast">
				<div class="orderQuickViewShippingBlockDetails">
					&nbsp;
				</div>
				<div class="orderQuickViewShippingBlockItems">
					<table width="95%" align="center" border="0" cellspacing="0" cellpadding="2">
						{% for id, total in totalRows %}
							<tr class="orderQuickViewTotal{{ id|capitalize }}">
								<td height="18" class="text" align="right">{{ total.label }}:</td>
								<td align="right" class="orderQuickViewPriceColumn">{{ total.value|currencyFormatPrice(order.orddefaultcurrencyid) }}</td>
							</tr>
						{% endfor %}
						{% if order.ordrefundedamount > 0 %}
							<tr class="orderQuickViewTotal{{ id|capitalize }}">
								<td height="18" class="text" align="right" style="color: maroon">{{ lang.Refunded }}:</td>
								<td align="right" class="orderQuickViewPriceColumn" style="color: maroon">-{{ order.ordrefundedamount|currencyFormatPrice(order.orddefaultcurrencyid) }}</td>
							</tr>
						{% endif %}
					</table>
				</div>
				<br style="clear: both" />
			</div>
		</td>
	</tr>
</table>
