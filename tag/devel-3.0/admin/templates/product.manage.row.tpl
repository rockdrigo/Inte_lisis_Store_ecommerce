<tr id="tr{{ ProductId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
	<td align="center" style="width:25px">
		<input type="checkbox" name="products[]" value="{{ ProductId|safe }}">
	</td>
	<td align="center" style="width:15px; display: {{ HideInventoryOptions|safe }}">
		{{ StockExpand|safe }}
	</td>
	<td align="center" style="width:20px">
		<img src="images/product.gif" alt="product" height="16" width="16" />
	</td>
	<td style="width:50px" nowrap class="ImageField" align="center">
		{{ ProductImage|safe }}
	</td>
	<td style="width:100px" class="{{ SortedFieldCodeClass|safe }}">
		{{ SKU|safe }}
	</td>
	<td id="InStock{{ ProductId|safe }}" class="{{ SortedFieldStockClass|safe }} {{ LowStockStyle|safe }}" style="display: {{ HideInventoryOptions|safe }}">
		{{ StockInfo|safe }}
	</td>
	<td colspan="{{ ProductNameSpan|safe }}" class="{{ SortedFieldNameClass|safe }}">
		{{ Name|safe }}
	</td>
	<td style="text-align: right;" class="{{ SortedFieldPriceClass|safe }}">
		{{ Price|safe }}
	</td>
	<td style="padding-left: 15px;" class="{{ SortedFieldStatusClass|safe }}">
		{{ Status }}
	</td>
	<td align="center" class="{{ SortedFieldVisibleClass|safe }}">
		{{ Visible|safe }}
	</td>
	<td align="center" class="{{ SortedFieldFeaturedClass|safe }}">
		{{ Featured|safe }}
	</td>
	<td>
		{{ EditProductLink|safe }}
		{{ CopyProductLink|safe }}
	</td>
</tr>
<tr id="trQ{{ ProductId|safe }}" style="display:none">
	<td colspan="6">
		&nbsp;
	</td>
	<td colspan="2" class="ProductQuickView QuickView" id="tdQ{{ ProductId|safe }}">
	</td>
	<td colspan="3">
		&nbsp;
	</td>
</tr>