{% import 'order.quickview.tpl' as helper %}

{% macro customFormField(field) %}
	<tr>
		<td class="text" width="120" valign="top">
			{{ field.label}}
		</td>
		<td class="text">
			{{ field.value|join('<br />') }}
		</td>
	</tr>
{% endmacro %}

{% macro address(address) %}
	{% if address.firstname or address.lastname %}
		<div>{{ address.firstname}} {{ address.lastname }}</div>
	{% else %}
		<div>{{ address.first_name}} {{ address.last_name }}</div>
	{% endif %}

	<div>{{ address.company }}</div>

	{% if address.address1 or address.address2 %}
		<div>{{ address.address1 }}</div>
		<div>{{ address.address2 }}</div>
	{% else %}
		<div>{{ address.address_1 }}</div>
		<div>{{ address.address_2 }}</div>
	{% endif %}

	<div>
		{{ address.city }}{% if address.city and (address.state or address.zip) %}, {% endif %}
		{{ address.state }}{% if address.state and address.zip %}, {% endif %}{{ address.zip }}
	</div>
	<div>
		{{ address.country }}

		{% if address.countryFlag %}
			<img src="../lib/flags/{{ address.countryFlag }}.gif" style="vertical-align: middle" alt="" />
		{% endif %}
	</div>
{% endmacro %}

<div class="toolbar">
	<h1 id="pageTitle">{% lang 'Order' %} #{{ order.orderid }}</h1>
        <a style="position:absolute; left:5px; top:8px; width:30px" class="button" href="javascript:history.go(-2)" type="submit">{% lang 'Back' %}</a>
	<a style="width:59px" class="button" href="index.php?ToDo=viewOrders" type="submit">{% lang 'AllOrders' %}</a>
</div>
<ul id="order" title="{% lang 'Order' %} #{{ order.orderid }}" selected="true">
	{{ message|safe }}
	{% if order.deleted %}
		<li class="deletedOrderNotice">
			<span>{% lang 'iphoneDeletedOrderNotice' %}</span>
		</li>
	{% endif %}
	<li style="height:25px;" class="subMenu">
		<ul class="tab">
			<li id="od" onclick="SubMenu(this)" class="tabSelected">{% lang 'OrderDetails' %}</li>
			<li id="om" onclick="SubMenu(this)">{% lang 'OrderMessages' %} {% if order.numunreadmessages > 0 %}<div class="newIcon newOrderIcon">{{ order.numunreadmessages }}</div>{% endif %}</li>
		</ul>
	</li>
	<li class="group">{% lang 'OrderItems' %}</li>
	<li>
		<table width="95%" align="center" border="0" cellspacing="0" cellpadding="0">
			{% for addressId, orderAddress in orderAddresses %}
				{% if loop.length > 1 %}
					<tr>
						<td>
							{% if addressId == 0 %}
								{{ lang.DigitalItemDetails }}
							{% else %}
								{{ lang.Destination }} #{{ loop.index }}
							{% endif %}
						</td>
					</tr>
				{% endif %}

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
								<blockquote style="padding-left: 10px; margin: 0">
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
						<td class="text" width="30%" align="right">
							{{ product.total|formatPrice }}
						</td>
					</tr>

					{% if product.ordprodwrapname %}
						<tr>
							<td style="padding: 2px 0 2px 15px">
								{{ lang.GiftWrapping }}: {{ product.ordprodwrapname }}
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
				<tr>
					<td colspan="2">
						<hr noshade="noshade" size="1" />
					</td>
				</tr>
			{% endfor %}
			{% for id, total in totalRows %}
				<tr>
					<td align="right">{{ total.label }}:</td>
					<td align="right">{{ total.value|formatPrice }}</td>
				</tr>
			{% endfor %}
		</table>

	</li>
	<li class="group">{% lang 'DateOrdered' %}</li>
	<li>
		{{ order.orddate|date('d M Y H:i:s') }}<br />
	</li>
	<li class="group">{% lang 'EmailAddress' %}</li>
	<li>
		{% if order.custconemail %}
			<a href="mailto:{{ order.custconemail }}">{{ order.custconemail }}</a>
		{% elseif order.ordbillemail %}
			<a href="mailto:{{ order.ordbillemail }}">{{ order.ordbillemail }}</a>
		{% else %}
			{{ lang.NA }}
		{% endif %}
	</li>
	{% if order.ordbillphone or order.ordcustconphone %}
		<li class="group">{% lang 'PhoneNumber' %}</li>
		<li>
			{% if order.ordbillphone %}
				<a href="tel:{{ order.ordbillphone }}" type="submit">{{ order.ordbillphone }}</a>
			{% elseif order.custconphone %}
				<a href="tel:{{ order.custconphone }}" type="submit">{{ order.custconphone }}</a>
			{% endif %}
		</li>
	{% endif %}
	<li class="group">{% lang 'BillingDetails' %}</li>
	<li>
		{{ helper.address(billingAddress) }}
		(<span style="text-decoration:underline; color:#194FDB" onclick="document.location.href='http://maps.google.com/maps?q={{ order.ordbillstreet1 ~ ' ' ~ order.ordbillstreet2 ~ ' ' ~ order.ordbillsuburb ~ ' ' ~ order.ordbillstate ~ ' ' ~ order.ordbillzip ~ ' ' ~ order.ordbillcountry }}'">{% lang 'MapThis' %}</span>)<hr />
		{% if order.orderpaymentmethod == false %}
			{{ lang.NA }}
		{% elseif order.orderpaymentmethod != "storecredit" and order.orderpaymentmethod != "giftcertificate" %}
			{{ order.orderpaymentmethod }}
		{% endif %}

		{% if order.ordstorecreditamount > 0 %}
			<div>
				{{ lang.PaymentStoreCredit }}
				({{ order.ordstorecreditamount|formatPrice }})
			</div>
		{% endif %}

		{% if order.ordgiftcertificateamount > 0 %}
			<div>
				{% lang 'PaymentGiftCertificates' with [
					'orderId': order.orderid
				] %}
				({{ order.ordgiftcertificateamount|formatPrice }})
			</div>
		{% endif %}
	</li>
	{% if order.ordisdigital == 0 %}
		<li class="group">{% lang 'ShippingDetails' %}</li>
		<li>
			{% for orderAddress in orderAddresses %}
				{% if orderAddress.address %}
					{% if loop.length > 1 %}
						<div>{{ lang.Destination }} #{{ loop.index }}</div>
					{% endif %}
					<div style="font-weight: normal; padding-left: 5px">
						{{ helper.address(orderAddress.address) }}
						(<span style="text-decoration:underline; color:#194FDB" onclick="document.location.href='http://maps.google.com/maps?q={{ orderAddress.address.address_1 ~ ' ' ~ orderAddress.address.address_2 ~ ' ' ~ orderAddress.address.city ~ ' ' ~ orderAddress.address.state ~ ' ' ~ orderAddress.address.zip ~ ' ' ~ orderAddress.address.country }}'">{% lang 'MapThis' %}</span>)
					</div>
					<div style="padding-left: 5px; margin-top: 10px;">
						{% if orderAddress.shipping.method %}
							{{ orderAddress.shipping.method }} ({{ orderAddress.shipping.cost|formatPrice }})
						{% endif %}
					</div>
					<hr />
				{% endif %}
			{% endfor %}
			{% if order.orddateshipped %}
				{{ order.orddateshipped|date('DisplayDateFormat') }}
			{% endif %}
		</li>
	{% endif %}
	{% if order.ordcustmessage %}
		<li class="group">{% lang 'OrderComments' %}</li>
		<li>
			{{ order.ordcustmessage|nl2br }}
		</li>
	{% endif %}
	<li class="group">{% lang 'OrderStatus1' %}</li>
	<li>
		<div id="statusMessage" style="width:94%; margin:-7px 0px 5px -10px; display:none; z-index: 10; background: url('images/info.gif') 5px 5px #FFF1AC; background-repeat:no-repeat; padding:5px 0px 5px 30px" onclick="this.style.display='none';">{% lang 'StatusUpdatedShort' %}</div>
		<select id="orderStatus" style="width:98%; font-size:16px" onblur="UpdateOrderStatus(this.value)" {% if order.deleted %}disabled="disabled"{% endif %}>
			{% for id, name in orderStatuses %}
				<option value="{{ id }}" {% if order.ordstatus == id %}selected="selected"{% endif %}>{{ name }}</option>
			{% endfor %}
		</select>
		<img id="statusLoader" style="display:none" src="images/ajax-loader.gif" width="16" height="16" />
	</li>
	{% if not order.deleted %}
		<li class="group">{% lang 'DeleteOrder' %}</li>
		<li>
			<form id="frmDelete" method="post" action="index.php?ToDo=deleteOrders" onsubmit="return CheckDeleteOrder()">
				<input type="hidden" name="orders[]" value="{{ order.orderid }}" />
				<input type="submit" value="{% lang 'DeleteThisOrder' %}" style="width:98%" />
			</form>
		</li>
	{% endif %}
</ul>

<script type="text/javascript">
	function UpdateOrderStatus(Status) {
		var os = document.getElementById("orderStatus");
		var sl = document.getElementById("statusLoader");
		os.style.width = "90%";
		os.style.margin = "0px 5px 0px 0px";
		sl.style.display = "";

		$.ajax({
			type: "POST",
			url: "remote.php",
			data: "w=updateOrderStatus&o={{ order.orderid }}&s="+Status,
			success: function(msg){
				$('#statusMessage').css('display', 'block');
				os.style.width = "98%";
				sl.style.display = "none";
				window.setTimeout("$('#statusMessage').hide();", 3000);
			}
		});
	}

	function CheckDeleteOrder() {
		if(confirm("{% lang 'AreYouSureShort' %}")) {
			return true;
		}
		else {
			return false;
		}
	}

	function SubMenu(Tab) {
		switch(Tab.id) {
			case "od": {
				document.location.reload();
				break;
			}
			case "om": {
				document.location.href = "index.php?ToDo=viewOrderMessages&orderId={{ order.orderid }}";
				break;
			}
		}
	}

	$(function(){
		$('.deletedOrderNotice .restoreOrderLink').click(function(event){
			event.preventDefault();
			if (!confirm("{% jslang 'iphoneRestoreOrderConfirmation' %}")) {
				return;
			}

			var id = {{ order.orderid }};
			$.ajax({
				type: 'POST',
				url: 'remote.php',
				data: {
					remoteSection: 'orders',
					w: 'restoreOrder',
					orderId: {{ order.orderid }},
				},
				dataType: 'json',
				success: function (data) {
					if (data && data.success) {
						location.reload();
						return;
					}
					alert("{% jslang 'iphoneRestoreOrderError' %}");
				},
				error: function () {
					alert("{% jslang 'iphoneRestoreOrderError' %}");
				}
			});
		});
	});

</script>
