<form action="index.php?ToDo=deleteVendors" name="frmVendors" id="frmVendors" method="post" onsubmit="return ConfirmDeleteSelected();">
	<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%">
	<tr>
		<td class="Heading1">{% lang 'Vendors' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'VendorsIntro' %}</p>
			{{ Message|safe }}
			<p>
				<input type="button" onclick="window.location='index.php?ToDo=addVendor';" value="{% lang 'AddVendor' %}" class="SmallButton" />
				<input name="DeleteButton" type="submit" value="{% lang 'DeleteSelected' %}" class="SmallButton" {{ DisableDelete|safe }} />
			</p>
		</td>
	</tr>
	<tr>
		<td>
			<div class="GridContainer">
				{{ VendorDataGrid|safe }}
			</div>
		</td>
	</tr>
	</table>
	</div>
</form>

<script type="text/javascript">
	function ConfirmDeleteSelected()
	{
		if(!$('.GridContainer input[type=checkbox].check:checked').length) {
			alert('{% lang 'SelectOneMoreVendorsDelete' %}');
			return false;
		}
		if(confirm('{% lang 'ConfirmDeleteVendors' %}')) {
			return true;
		}
		else {
			return false;
		}
	}
</script>
