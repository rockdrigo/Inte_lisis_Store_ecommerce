<form action="index.php?ToDo=deleteGiftWrap" name="frmGiftWrap" id="frmGiftWrap" method="post" onsubmit="return ConfirmDeleteSelected();">
	<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%">
	<tr>
		<td class="Heading1">{% lang 'GiftWrappingSettings' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'GiftWrappingIntro' %}</p>
			{{ Message|safe }}
			<p>
				<input type="button" onclick="window.location='index.php?ToDo=addGiftWrap';" value="{% lang 'AddGiftWrap' %}" class="SmallButton" />
				<input type="submit" value="{% lang 'DeleteSelected' %}" class="SmallButton" {{ DisableDelete|safe }} />
			</p>
		</td>
	</tr>
	<tr>
		<td>
			<div class="GridContainer">
				{{ GiftWrapDataGrid|safe }}
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
			alert('{% lang 'SelectOneMoreGiftWrapDelete' %}');
			return false;
		}
		if(confirm('{% lang 'ConfirmDeleteGiftWrap' %}')) {
			return true;
		}
		else {
			return false;
		}
	}

	function ConfirmDeleteWrap()
	{
		if(confirm('{% lang 'ConfirmDeleteGiftWrap' %}')) {
			return true;
		}

		return false;
	}
</script>
