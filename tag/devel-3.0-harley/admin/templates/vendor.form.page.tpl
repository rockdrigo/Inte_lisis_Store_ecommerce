<tr class="GridRow" onmouseover="$(this).addClass('GridRowOver');" onmouseout="$(this).removeClass('GridRowOver');">
	<td style="text-align: center; width: 1px;"><input type="checkbox" name="pages[]" class="check" value="{{ PageId|safe }}" /></td>
	<td><img src="images/page.gif" alt="" /></td>
	<td>{{ PageTitle|safe }}</td>
	<td style="text-align: center;">{{ PageStatus|safe }}</td>
	<td style="width: 200px;">
		<a href="index.php?ToDo=editVendorPage&amp;vendorId={{ VendorId|safe }}&amp;pageId={{ PageId|safe }}">{% lang 'Edit' %}</a>
		<a href="index.php?ToDo=deleteVendorPages&amp;vendorId={{ VendorId|safe }}&amp;pages[]={{ PageId|safe }}" onclick="return ConfirmDelete();">{% lang 'Delete' %}</a>
	</td>
</tr>