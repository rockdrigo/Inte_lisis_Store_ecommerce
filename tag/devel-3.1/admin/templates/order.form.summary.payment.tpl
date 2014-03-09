{% import "macros/forms.tpl" as formBuilder %}
{% import "macros/orderform.tpl" as orderForm %}

{% if payment %}
	{{ formBuilder.startRow }}
		{% lang 'PaymentDetailInfo' with ['provider': payment.provider] %}
	{{ formBuilder.endRow }}

	{% if payment.transactionId or payment.status %}
		{{ formBuilder.startRow }}
			<table cellspacing="0" class="orderFormPaymentDetailsTable">
				{% if payment.transactionId %}
					<tr>
						<th>{{ lang.TransactionId ~ ':' }}</th>
						<td>{{ payment.transactionId }}</td>
					</tr>
				{% endif %}

				{% if payment.status %}
					<tr>
						<th>{{ lang.PaymentStatus ~ ':' }}</th>
						<td>{{ payment.status }}</td>
					</tr>
				{% endif %}
			</table>
		{{ formBuilder.endRow }}
	{% endif %}
{% else %}
	{{ formBuilder.startRow }}
		<select name="paymentMethod" id="paymentMethod" class="Field200">
			<option value="">-- Select a Payment Method --</option>
			{% for moduleId, module in modules %}
				{% if not controlPanelSecure and module.requiresSSL %}
					<option value="" disabled="disabled">{{ module.name }} (requires secure control panel)</option>
				{% else %}
					<option value="{{ moduleId }}">{{ module.name }}</option>
				{% endif %}
			{% endfor %}
		</select>
	{{ formBuilder.endRow }}

	{% for moduleId, module in modules %}
		{% if (controlPanelSecure and module.requiresSSL) or not module.requiresSSL %}
			{{ orderForm.paymentModuleForm(module) }}

			{{ module.javascript|safe }}
		{% endif %}
	{% endfor %}
{% endif %}