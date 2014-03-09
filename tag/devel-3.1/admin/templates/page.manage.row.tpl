				<li id="ele-{{ PageId|safe }}" class="{{ SortableClass|safe }}">
					<table class="GridPanel" cellspacing="0" cellpadding="0" border="0" style="width:100%;">
						<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
							<td width="1">
								<input type="checkbox" name="page[]" value="{{ PageId|safe }}" />
							</td>
							<td width="150" style="{{ HideVendorColumn|safe }}">
								{{ VendorName|safe }}
							</td>
							<td class="{{ SortableDragClass|safe }} {{ SortedFieldTitleClass|safe }}">
								{{ Title|safe }}
							</td>
							<td width="120" class="HideOnDrag {{ SortedFieldTypeClass|safe }}">{{ Type|safe }}</td>
							<td width="80" class="HideOnDrag {{ SortedFieldVisibleClass|safe }}" align="center">{{ Visible|safe }}</td>
							<td width="80" class="HideOnDrag">
								{{ PreviewPageLink|safe }}&nbsp;&nbsp;&nbsp;
								{{ EditPageLink|safe }}
							</td>
						</tr>
					</table>
					{{ SubPages|safe }}
				</li>