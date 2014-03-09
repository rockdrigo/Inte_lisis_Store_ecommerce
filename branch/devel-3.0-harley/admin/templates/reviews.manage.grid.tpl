			<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
				<tr align="right">
					<td colspan="9" style="padding:6px 0px 6px 0px" class="PagingNav">
						{{ Nav|safe }}
					</td>
				</tr>
			<tr class="Heading3">
				<td align="center" style="width:18px"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td>&nbsp;</td>
				<td style="width:25%">
					{% lang 'ReviewTitle' %} &nbsp;
					{{ SortLinksReview|safe }}
				</td>
				<td>
					{% lang 'Product' %} &nbsp;
					{{ SortLinksName|safe }}
				</td>
				<td>

					{% lang 'Rating' %} &nbsp;
					{{ SortLinksRating|safe }}
				</td>
				<td>
					{% lang 'PostedBy' %} &nbsp;
					{{ SortLinksBy|safe }}
				</td>
				<td>
					{% lang 'Date' %} &nbsp;
					{{ SortLinksDate|safe }}
				</td>
				<td style="width:70px">
					{% lang 'Status' %} &nbsp;
					{{ SortLinksStatus|safe }}
				</td>
				<td style="width:80px">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ ReviewGrid|safe }}
			<tr align="right">
				<td colspan="9" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
		</table>
		<a href="?searchQuery={{ Query|safe }}&amp;page={{ Page|safe }}{{ SortURL|safe }}" id="ReviewSortURL"></a>