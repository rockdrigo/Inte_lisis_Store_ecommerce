
<form action="index.php?ToDo={{ FormAction|safe }}" onSubmit="return ValidateForm(CheckForm);" name="frmAddBrand" method="post">
{{ hiddenFields|safe }}
<div class="BodyContainer">
<table class="OuterPanel">
	  <tr>
		<td class="Heading1">{{ BrandTitle|safe }}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ BrandIntro|safe }}</p>
			{{ Message|safe }}
		</td>
	  </tr>

	  <tr>
		    <td>
				<div>
					<input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">
					<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"><br /><img src="images/blank.gif" width="1" height="10" /></div>
			</td>
		  </tr>
			<tr>
				<td>
				  <table class="Panel">
					<tr>
					  <td class="Heading2" colspan=2>{% lang 'BrandDetails' %}</td>
					</tr>
					<tr>
						<td class="FieldLabel">
							<span class="Required">*</span>&nbsp;{% lang 'BrandNames' %}:
						</td>
						<td>
							<textarea name="brands" id="brands" class="Field250" rows="5" value=""></textarea>
							<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'BrandNames' %}', '{% lang 'BrandNamesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="d1"></div>

						</td>
					</tr>
				</table>
			<table class="Panel">
				<tr>
					<td class="FieldLabel">&nbsp;</td>
					<td>
						<input type="submit" name="SubmitButton2" value="{% lang 'Save' %}" class="FormButton">
						<input type="button" name="CancelButton2" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
				<tr><td class="Gap"></td></tr>
		 </table>
		</td>
	</tr>
</table>
</div>
</form>

<script type="text/javascript">

	function CheckForm() {
		var brands = document.getElementById("brands");

		if(brands.value == "") {
			alert("{% lang 'EnterBrands' %}");
			brands.focus();
			return false;
		}

		return true;
	}

	function ConfirmCancel()
	{
		if(confirm('{{ CancelMessage|safe }}'))
			document.location.href='index.php?ToDo=viewBrands';
		else
			return false;
	}

</script>
