	<form enctype="multipart/form-data" action="index.php?ToDo=importProducts&Step=3" id="frmImport" method="post" onsubmit="return ValidateForm(CheckImportProductForm)">
	<input type="hidden" name="ImportSession" value="{{ ImportSession|safe }}" />
	<div class="BodyContainer">
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'ImportProductsStep2' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'ImportProductsStep2Desc' %}</p>
				{{ Message|safe }}
			</td>
		</tr>
		<tr>
			<td>
				<div>
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
					<input type="submit" value="{% lang 'Next' %} &raquo;" class="FormButton" />
				</div>
				<br />
			</td>
		</tr>

		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan="2">{% lang 'ImportLinkFields' %}</td>
				</tr>
				<tr>
					<table width="600">
						{{ ImportFieldList|safe }}
					</table>
				</tr>
			 </table>
			</td>
		</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
			<tr>
				<td class="Field250">
					&nbsp;
				</td>
				<td>
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
					<input type="submit" value="{% lang 'Next' %} &raquo;" class="FormButton" />
				</td>
			</tr>
		</table>
		<script type="text/javascript">
		function ConfirmCancel()
		{
			if(confirm('{% lang 'ConfirmCancelImport' %}'))
				window.location = 'index.php?ToDo=importProducts';
		}

		function CheckImportProductForm()
		{
			var f = document.getElementById('Matchprodname');
			if(f.selectedIndex <= 0)
			{
				alert('{% lang 'NoMatchProductName' %}');
				f.focus();
				return false;
			}

			if(g('categoryTypeSingle').style.display != 'none') {
				var f = document.getElementById('Matchcategory');
				if(1 == 0{{ CategoryRequired|safe }} && f.selectedIndex <= 0)
				{
					alert('{% lang 'NoMatchCategory' %}');
					f.focus();
					return false;
				}
			}
			return true;
		}
		</script>
	</div>
</form>
