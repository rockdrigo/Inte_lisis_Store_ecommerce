			<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
				<tr align="right">
					<td colspan="9" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ Nav|safe }}
					</td>
				</tr>
			<tr class="Heading3">
				<td align="center"><input type="checkbox" onclick="$(this).parents('form').find('input[type=checkbox]').attr('checked', this.checked);"></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td nowrap="nowrap">
					{% lang 'GiftCertificateCode' %} &nbsp;
					{{ SortLinksCode|safe }}
				</td>
				<td nowrap="nowrap">
					{% lang 'GiftCertificatePurchasedBy' %} &nbsp;
					{{ SortLinksCust|safe }}
				</td>
				<td nowrap="nowrap">
					{% lang 'GiftCertificateAmount' %} &nbsp;
					{{ SortLinksCertificateAmount|safe }}
				</td>
				<td nowrap="nowrap">
					{% lang 'GiftCertificateBalance' %} &nbsp;
					{{ SortLinksCertificateBalance|safe }}
				</td>
				<td nowrap="nowrap">
					{% lang 'GiftCertificatePurchaseDate' %} &nbsp;
					{{ SortLinksPurchaseDate|safe }}
				</td>
				<td nowrap="nowrap">
					{% lang 'Status' %} &nbsp;
					{{ SortLinksStatus|safe }}
				</td>
			</tr>
			{{ GiftCertificatesGrid|safe }}
			<tr align="right">
				<td colspan="9" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
		</table>