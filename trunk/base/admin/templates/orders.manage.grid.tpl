{% import "macros/util.tpl" as util %}
		<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
			<tr align="right">
				<td colspan="12" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ util.paging(numOrders, perPage, currentPage, pageURL, true) }}
				</td>
			</tr>
			<tr class="Heading3">
				<td align="center"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td nowrap>
					{% lang 'OrderId' %} &nbsp;
					{{ SortLinksId|safe }}
				</td>
				<td colspan="{{ CustomerNameSpan|safe }}">
					{% lang 'Customer' %} &nbsp;
					{{ SortLinksCust|safe }}
				</td>
				<td nowrap>
					{% lang 'Date' %} &nbsp;
					{{ SortLinksDate|safe }}
				</td>
				<td>
					{% lang 'Status' %} &nbsp;
					{{ SortLinksStatus|safe }}
				</td>
				<td style="text-align: center; display: {{ HideOrderMessages|safe }}" nowrap>
					{% lang 'NewMessages' %} &nbsp;
					{{ SortLinksMessage|safe }}
				</td>
				<td style="width:80px; text-align: center;">
					{% lang 'Total' %} &nbsp;
					{{ SortLinksTotal|safe }}
				</td>
				<td>&nbsp;</td>
				<td style="display: {{ HideCountry|safe }}">
					&nbsp;
				</td>
				<td style="width:100px">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ OrderGrid|safe }}
			{% if viewDeletedOrdersUrl %}
				<tr class="GridRow orderGridDeletedNotice">
					<td colspan="12">
						{% lang 'DeletedOrdersMatchedYourSearch' with [
							'viewDeletedOrdersUrl': viewDeletedOrdersUrl
						] %}
					</td>
				</tr>
			{% endif %}
			<tr align="right">
				<td colspan="12" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ util.paging(numOrders, perPage, currentPage, pageURL, true) }}
				</td>
			</tr>
		</table>
		<input type="hidden" id="CurrentPage" name="CurrentPage" value="{{ CurrentPage|safe }}" />
