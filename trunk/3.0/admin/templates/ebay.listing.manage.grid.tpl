			<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%; margin-top:10px">
				<tr align="right">
					<td colspan="12" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ Nav|safe }}
					</td>
				</tr>
				<tr class="Heading3">
					<td align="center">
						<input type="checkbox" id="checkalllisting" onclick="$('.ItemCheckBox').attr('checked', this.checked);">
					</td>
					<td>
					</td>
					<td>
						{% lang 'ListingItem' %} &nbsp;
						{{ SortLinksItem|safe }}
					</td>
					<td  style="width:100px;">
						<span onmouseover="ShowQuickHelp(this, '{% lang 'ListingDateListed' %}', '{% lang 'ListingDateListedHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'ListingDateListed' %}</span>&nbsp;{{ SortLinksDateListed|safe }}
					</td>
					<td  style="width:70px;">
						{% lang 'ListingType' %} &nbsp;
						{{ SortLinksType|safe }}
					</td>
					<td  style="width:70px;">
						{% lang 'ListingStatus' %} &nbsp;
						{{ SortLinksStatus|safe }}
					</td>
					<td style="width:80px;">
						<span onmouseover="ShowQuickHelp(this, '{% lang 'ListingQuantityRemaining' %}', '{% lang 'ListingQuantityRemainingHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'ListingQuantityRemaining' %}</span>&nbsp;{{ SortLinksQuantityRemaining|safe }}
					</td>
					<td style="width:60px;">
						<span onmouseover="ShowQuickHelp(this, '{% lang 'ListingBidCount' %}', '{% lang 'ListingBidCountHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'ListingBidCount' %}</span>&nbsp;{{ SortLinksBidCount|safe }}
					</td>
					<td align="right"  style="width:110px;">
						<span onmouseover="ShowQuickHelp(this, '{% lang 'ListingCurrentPrice' %}', '{% lang 'ListingCurrentPriceHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'ListingCurrentPrice' %}</span>&nbsp;{{ SortLinksCurrentPrice|safe }}
					</td>
					<td align="right" style="width:110px;">
						<span onmouseover="ShowQuickHelp(this, '{% lang 'ListingBinPrice' %}', '{% lang 'ListingBinPriceHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'ListingBinPrice' %}</span>&nbsp;{{ SortLinksBinPrice|safe }}
					</td>
					<td align="center">
						<span onmouseover="ShowQuickHelp(this, '{% lang 'ListingOrderNumber' %}', '{% lang 'ListingOrderNumberHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText">{% lang 'ListingOrderNumber' %}</span>&nbsp;{{ SortLinksOrderNumber|safe }}
					</td>
					<td>
						{% lang 'Action' %}
					</td>
				</tr>
				{{ EbayListingGrid|safe }}
				<tr align="right">
					<td colspan="12" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ Nav|safe }}
					</td>
				</tr>
		</table>