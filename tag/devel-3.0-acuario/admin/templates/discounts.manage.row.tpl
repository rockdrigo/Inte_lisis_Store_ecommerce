	<li id="{{ RowId|safe }}" class="SortableRow"  style="width:100%;">
		<table class="GridPanel SortablePanel" cellspacing="0" cellpadding="0" border="0" style="width : 100%;">
		<tr class="GridRow">
		<td align="left" width="10px">
			<input class="DiscountsIdx" type="checkbox" name="discount[]" value="{{ DiscountId|safe }}" />
			<input type="hidden" class="DiscountSortOrder" value="{{ SortOrder|safe }}" />
		</td>
		<td align="left" width="30px">
			<img src='images/discountrule.gif'>
		</td>
		<td class="{{ SortedFieldNameClass|safe }} DragMouseDown sort-handle">
			{{ Name|safe }}
		</td>
		<td align="left" class="{{ SortedFieldMaxUsesClass|safe }}" width="100px">
			{{ MaxUses|safe }}
		</td>
		<td align="left" class="{{ SortedFieldCurrentUsesClass|safe }}" width="100px">
			{{ CurrentUses|safe }}
		</td>
		<td align="left" class="{{ SortedFieldExpiryDateClass|safe }}" width="98px">
			{{ ExpiryDate|safe }}
		</td>
		<td align="left" class="{{ SortedFieldEnabledClass|safe }}" width="80px">
			{{ Enabled|safe }}
		</td>
		<td align="left" class="{{ SortedFieldHaltClass|safe }}" width="130px">
			{{ Halt|safe }}
		</td>
		<td nowrap="nowrap" width="80px">
			{{ EditDiscountLink|safe }}
			{{ DeleteDiscountLink|safe }}
		</td>
		</tr>
		</table>
	</li>