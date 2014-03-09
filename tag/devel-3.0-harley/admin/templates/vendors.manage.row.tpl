<tr class="GridRow" onmouseover="$(this).addClass('GrodRowOver');" onmouseout="$(this).removeClass('GridRowOver');">
	<td style="text-align: center;"><input type="checkbox" class="check" name="vendors[]" value="{{ VendorId|safe }}" /></td>
	<td><img src="images/vendor.gif" alt="" /></td>
	<td>{{ VendorName|safe }}</td>
	<td>{{ VendorUsers|safe }}</td>
	<td>
		<a href="index.php?ToDo=editVendor&amp;vendorId={{ VendorId|safe }}">{% lang 'Edit' %}</a>
		<a href="index.php?ToDo=viewUsers&amp;vendorId={{ VendorId|safe }}">{% lang 'ManageUsers' %}</a>
		<a href="index.php?ToDo=deleteVendors&amp;vendors[]={{ VendorId|safe }}" onclick="return ConfirmDeleteVendor();">{% lang 'Delete' %}</a>
	</td>
</tr>