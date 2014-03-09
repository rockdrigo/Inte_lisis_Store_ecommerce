<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
	<td align="center" style="width:25px">
		<input type="checkbox" name="brands[]" value="{{ BrandId|safe }}">
	</td>
	<td align="center" style="width:18px;">
		<img src='images/brand.gif' width="15" height="15">
	</td>
	<td class="{{ SortedFieldBrandClass|safe }}">
		{{ BrandName|safe }}
	</td>
	<td class="{{ SortedFieldProductsClass|safe }}">
		{{ Products|safe }}
	</td>
	<td>
		{{ EditBrandLink|safe }}
	</td>
</tr>