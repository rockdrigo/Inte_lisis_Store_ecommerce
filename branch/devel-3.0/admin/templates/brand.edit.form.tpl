<form enctype="multipart/form-data" action="index.php?ToDo={{ FormAction|safe }}" onSubmit="return ValidateForm(CheckForm)" name="frmAddBrand" method="post">
<input type="hidden" name="brandId" value="{{ BrandId|safe }}">
<input type="hidden" name="oldBrandName" value="{{ BrandName|safe }}">
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
				<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"><br /><img src="images/blank.gif" width="1" height="10" />
			</div>
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
						<span class="Required">*</span>&nbsp;{% lang 'BrandName' %}:
					</td>
					<td>
						<input type="text" name="brandName" id="brandName" class="Field400" value="{{ BrandName|safe }}">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'PageTitle' %}:
					</td>
					<td>
						<input type="text" id="brandPageTitle" name="brandPageTitle" class="Field400" value="{{ BrandPageTitle|safe }}" />
						<img onmouseout="HideHelp('metataghelp');" onmouseover="ShowHelp('metataghelp', '{% lang 'PageTitle' %}', '{% lang 'BrandPageTitleHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="metataghelp"></div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'MetaKeywords' %}:
					</td>
					<td>
						<input type="text" id="brandMetaKeywords" name="brandMetaKeywords" class="Field400" value="{{ BrandMetaKeywords|safe }}" />
						<img onmouseout="HideHelp('metataghelp');" onmouseover="ShowHelp('metataghelp', '{% lang 'MetaKeywords' %}', '{% lang 'MetaKeywordsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="metataghelp"></div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'MetaDescription' %}:
					</td>
					<td>
						<input type="text" id="brandMetaDesc" name="brandMetaDesc" class="Field400" value="{{ BrandMetaDesc|safe }}" />
						<img onmouseout="HideHelp('metadeschelp');" onmouseover="ShowHelp('metadeschelp', '{% lang 'MetaDescription' %}', '{% lang 'MetaDescriptionHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="metadeschelp"></div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SearchKeywords' %}:
					</td>
					<td>
						<input type="text" id="brandSearchKeywords" name="brandSearchKeywords" class="Field400" value="{{ BrandSearchKeywords|safe }}">
						<img onmouseout="HideHelp('searchkeywords');" onmouseover="ShowHelp('searchkeywords', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="searchkeywords"></div>
					</td>
				</tr>
			</table>

			<table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'BrandImage' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'BrandImage' %}:
					</td>
					<td>
						<input type="file" id="brandimagefile" name="brandimagefile" class="Field" />
						<img onmouseout="HideHelp('dimage');" onmouseover="ShowHelp('dimage', '{% lang 'BrandImage' %}', '{% lang 'BrandImageHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="dimage"></div>{{ BrandImageMessage|safe }}
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
		var brands = document.getElementById("brandName");
		var bimg = document.getElementById("brandimagefile");

		if(brands.value == "") {
			alert("{% lang 'EnterBrand' %}");
			brands.focus();
			return false;
		}

		if(bimg.value != "") {
			// Make sure it has a valid extension
			img = bimg.value.split(".");
			ext = img[img.length-1].toLowerCase();

			if(ext != "jpg" && ext != "png" && ext != "gif") {
				alert("{% lang 'ChooseValidImage' %}");
				bimg.focus();
				bimg.select();
				return false;
			}
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
