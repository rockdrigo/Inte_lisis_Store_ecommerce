<tr id="tr{{ PaymentId|safe }}" class="GridRow">
	<td style="text-align: center; width: 23px;">
		<input type="checkbox" name="payments[]" value="{{ PaymentId|safe }}" />
	</td>
	<td style="text-align: center; width: 15px;">
		<a href="#" onclick="VendorPayments.Expand('{{ PaymentId|safe }}'); return false;" style="{{ HideExpandLink|safe }}">
			<img id="expand{{ PaymentId|safe }}" src="images/plus.gif" align="left" width="19" class="ExpandLink" height="16" title="{% lang 'ExpandQuickView' %}" border="0" />
		</a>
	</td>
	<td style="text-align: center; width: 18px;">
		<img src="images/payment.gif" alt="" />
	</td>
	<td class="{{ SortedFieldIdClass|safe }}">
		{{ PaymentId|safe }}
	</td>
	<td class="{{ SortedFieldVendorClass|safe }}">
		{{ Vendor|safe }}
	</td>
	<td class="{{ SortedFieldDateClass|safe }}">
		{{ PaymentFrom|safe }} - {{ PaymentTo|safe }}
	</td>
	<td class="{{ SortedFieldAmountClass|safe }}">
		{{ PaymentAmount|safe }}
	</td>
	<td class="{{ SortedFieldPaymentDateClass|safe }}">
		{{ PaymentDate|safe }}
	</td>
	<td class="{{ SortedFieldMethodClass|safe }}">
		{{ PaymentMethod|safe }}
	</td>
</tr>
<tr id="trQ{{ PaymentId|safe }}" style="display: none">
	<td>&nbsp;</td>
	<td colspan="8" class="QuickView">
		<h5>{% lang 'Comments' %}:</h5>
		<blockquote>{{ PaymentComments|safe }}</blockquote>
	</td>
</tr>