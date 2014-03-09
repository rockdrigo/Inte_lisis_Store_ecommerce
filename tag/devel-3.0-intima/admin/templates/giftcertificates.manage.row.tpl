	<tr id="tr{{ GiftCertificateId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center" style="width:23px">
			<input type="checkbox" name="certificates[]" value="{{ GiftCertificateId|safe }}" class="DeleteCheck">
		</td>
		<td align="center" style="width:15px">
			<a href="#" onclick="QuickGiftCertificateView('{{ GiftCertificateId|safe }}'); return false;">
				<img id="expand{{ GiftCertificateId|safe }}" src="images/plus.gif" class="ExpandLink" align="left" width="19" height="16" title="{% lang 'ExpandGiftCertificateQuickView' %}" border="0">
			</a>
		</td>
		<td align="center" style="width:18px">
			<img src='images/giftcertificate.gif' height="16" width="16" />
		</td>
		<td class="{{ SortedFieldCodeClass|safe }}">
			{{ GiftCertificateCode|safe }}
		</td>
		<td class="{{ SortedFieldCustClass|safe }}">
			<a href="index.php?ToDo=viewCustomers&amp;searchQuery={{ GiftCertificateCustomerId|safe }}" target="_blak">{{ GiftCertificateCustomerName|safe }}</a>
		</td>
		<td class="{{ SortedFieldCertificateAmountClass|safe }}">
			{{ GiftCertificateAmount|safe }}
		</td>
		<td class="{{ SortedFieldCertificateBalanceClass|safe }}">
			{{ GiftCertificateBalance|safe }}
		</td>
		<td class="{{ SortedFieldPurchaseDateClass|safe }}">
			{{ GiftCertificatePurchaseDate|safe }}
		</td>
		<td class="{{ SortedFieldStatusClass|safe }}">
			<select name="certificate_status_{{ GiftCertificateId|safe }}" id="status_{{ GiftCertificateId|safe }}" class="Field" onchange="UpdateGiftCertificateStatus({{ GiftCertificateId|safe }}, this.options[this.selectedIndex].value, this.options[this.selectedIndex].text)">
				{{ GiftCertificateStatusOptions|safe }}
			</select>
			&nbsp;
		</td>
	</tr>
	<tr id="trQ{{ GiftCertificateId|safe }}" style="display: none;">
		<td colspan="3">&nbsp;</td>
		<td colspan="2" id="tdQ{{ GiftCertificateId|safe }}" class="QuickView">
		</td>
		<td colspan="3">&nbsp;</td>
	</tr>
