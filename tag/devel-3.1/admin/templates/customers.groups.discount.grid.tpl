			<table class="GridPanel SortableGrid" cellspacing="0" cellpadding="0" border="0" style="width:100%;">
			<tr align="right" style="display:{{ HidePagingNav|safe }}">
				<td style="padding:6px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
			<tr>
				<td id="{{ Type|safe }}DataGridContainer">{{ CustomerGroupDiscountGrid|safe }}</td>
			</tr>
			<tr align="right" style="display:{{ HidePagingNav|safe }}">
				<td style="padding:6px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
		</table>