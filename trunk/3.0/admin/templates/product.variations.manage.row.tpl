<tr id="tr{{ VariationId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
	<td align="center" style="width:25px">
		<input type="checkbox" name="variations[]" value="{{ VariationId|safe }}">
	</td>
	<td align="center" style="width:20px">
		<img src="images/product_variation.gif" alt="product" height="16" width="16" />
	</td>
	<td class="{{ SortedFieldNameClass|safe }}">
		{{ Name|safe }}
	</td>
	<td class="{{ SortedFieldOptionsClass|safe }}">
		{{ NumOptions|safe }}
	</td>
	<td class="{{ SortedFieldOptionsClass|safe }}">
		{{ Edit|safe }} | {{ Configure|safe }}
	</td>
</tr>