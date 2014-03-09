
	<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
		<td align="center" style="width:18px"><input type="checkbox" name="reviews[]" value="{{ ReviewId|safe }}"></td>
		<td align="center" style="width:18px;">
			<img src='images/review.gif' width="20" height="20" />
		</td>
		<td class="{{ SortedFieldReviewClass|safe }}">
			{{ ReviewTitle|safe }}
		</td>
		<td class="{{ SortedFieldNameClass|safe }}">
			<a href="{{ ProdLink|safe }}" target="_blank">{{ ProdName|safe }}</a>
		</td>
		<td class="{{ SortedFieldRatingClass|safe }}">
			{{ Rating|safe }}
		</td>
		<td class="{{ SortedFieldByClass|safe }}">
			{{ PostedBy|safe }}
		</td>
		<td class="{{ SortedFieldDateClass|safe }}">
			{{ Date|safe }}
		</td>
		<td class="{{ SortedFieldStatusClass|safe }}">
			{{ Status|safe }}
		</td>
		<td>
			{{ PreviewLink|safe }}&nbsp;&nbsp;&nbsp;
			{{ EditLink|safe }}
		</td>
	</tr>