	<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center">
			<input type="checkbox" name="coupon[]" value="{{ CouponId|safe }}">
		</td>
		<td align="center" style="width:18px;">
			<img src='images/coupon.gif'>
		</td>
		<td class="{{ SortedFieldNameClass|safe }}">
			{{ Name|safe }}
		</td>
		<td class="{{ SortedFieldCouponClass|safe }}">
			<input type="text" value="{{ Coupon|safe }}" class="Field100" />
		</td>
		<td class="{{ SortedFieldDiscountClass|safe }}">
			{{ Discount|safe }}
		</td>
		<td class="{{ SortedFieldExpiryClass|safe }}">
			{{ Date|safe }}
		</td>
		<td class="{{ SortedFieldNumUsesClass|safe }}">
			{{ NumUses|safe }}
		</td>
		<td align="center" class="{{ SortedFieldEnabledClass|safe }}">
			{{ Enabled|safe }}
		</td>
		<td nowrap="nowrap">
			{{ EditCouponLink|safe }}&nbsp;&nbsp;&nbsp;
			<a title='{% lang 'CopyCouponClip' %}' href="javascript:CouponClipboard('{{ Coupon|safe }}')">{% lang 'CopyToClipboard' %}</a>
			{{ ViewOrdersLink|safe }}
		</td>
	</tr>