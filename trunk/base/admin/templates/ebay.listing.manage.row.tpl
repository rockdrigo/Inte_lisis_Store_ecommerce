	<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center">
			<input class="ItemCheckBox" type="checkbox" name="listings[]" value="{{ EbayItemId|safe }}">
		</td>
		<td>
			<img width="16" height="16" alt="product" src="images/ebay.gif">
		</td>
		<td class="ItemTitle">{{ Item|safe }}</td>
		<td>
			{{ DateListed|safe }}
		</td>
		<td class="{{ SortedFieldTypeClass|safe }}">
			{{ Type|safe }}
		</td>
		<td class="{{ SortedFieldStatusClass|safe }}">
		{% if Status == 'Pending' %}
			<span onmouseover="ShowQuickHelp(this, '{{ Status|safe }}', '{% lang 'PendingStatusHelp' %}');" onmouseout="HideQuickHelp(this);" class="OrangeHelpText">{{ Status|safe }}</span>
		{% else %}
			{{ Status|safe }}
		{% endif %}
		</td>
		<td class="{{ SortedFieldQuantityRemainingClass|safe }}" align="center">
			{{ QuantityRemaining|safe }}
		</td>
		<td class="{{ SortedFieldBidCountClass|safe }}">
			{{ BidCount|safe }}
		</td>
		<td class="{{ SortedFieldCurrentPriceClass|safe }}" align="right">
			{{ CurrentPrice|safe }}
		</td>
		<td class="{{ SortedFieldBinPriceClass|safe }}" align="right">
			{{ BinPrice|safe }}
		</td>
		<td class="{{ SortedFieldOrderNumberClass|safe }}" align="center">
			{% if OrderNumber%}
				<a id="OrderLink" href="index.php?ToDo=viewOrders&ebayItemId={{ EbayItemId|safe }}">{{ OrderNumber|safe }}</a>
			{% else %}
				{% lang 'NA' %}
			{% endif %}
		</td>
		<td>
			<a id="RemoveListing" class="removeListing" title="{% lang 'RemoveListingRef' %}" href="index.php?ToDo=deleteLocalEbayListing&listings={{ EbayItemId|safe }}">{% lang 'Remove' %}</a>
			<a id="CancelListing"  class="cancelListing" title="{% lang 'EndListingFromEbayRef' %}" href="#">{% lang 'EndListing' %}</a>
		</td>
	</tr>
