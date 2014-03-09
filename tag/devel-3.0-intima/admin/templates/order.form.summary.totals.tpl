<table cellspacing="0" class="orderFormSummaryTable">
	<tbody>
		{% for id, total in totals %}
			<tr class="orderFormSummaryTable-{{ id }} {% if total.type %}orderFormSummaryTable-type-{{ total.type }}{% endif %}">
				<th>
					{{ total.label }}
					{% if total.type == 'coupon' %}
						<br /><small><a href="#" onclick="Order_Form.removeCouponById({{ total.id }}); return false;">{{ lang.SmallRemoveLink }}</a></small>
					{% elseif  total.type == 'giftCertificate' %}
						<br /><small><a href="#" onclick="Order_Form.removeGiftCertificateById({{ total.id }}); return false;">{{ lang.SmallRemoveLink }}</a></small>
					{% endif %}
				</th>
				<td valign="top">{{ total.value|formatPrice }}</td>
			</tr>
		{% endfor %}
	</tbody>
</table>
