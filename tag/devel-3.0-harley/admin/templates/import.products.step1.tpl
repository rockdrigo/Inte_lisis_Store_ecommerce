<form enctype="multipart/form-data" action="index.php?ToDo=importProducts&Step=2" onsubmit="return ValidateForm(CheckImportProductForm)" id="frmImport" method="post">
<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">{% lang 'ImportProductsStep1' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'ImportProductsStep1Desc' %}</p>
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
			  <td class="Heading2" colspan=2>{% lang 'ImportDetails' %}</td>
			</tr>
			<tr>
				<td class="FieldLabel">
					<span class="Required">&nbsp;</span>&nbsp;{% lang 'ImportBulkEditCSV' %}
				</td>
				<td>
					<label><input type="checkbox" name="BulkEditTemplate" id="BulkEditTemplate" value="1" /> {% lang 'ImportBulkEditCSVYes' %}</label>
				</td>
			</tr>
			<tr class="BulkImportRowHide">
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'ImportProductsCategory' %}:
				</td>
				<td>
					<div>
						<label><input type="checkbox" name="AutoCategory" value="1" onclick="ToggleCategory();" id="AutoCategoryCheck" {{ AutoCategoryChecked|safe }} /> {% lang 'AutoDetectCategories' %}</label>
					</div>
					<div id="ManualCategory" style="display:none; padding-top: 5px; padding-left: 25px;">
						<div style="display:{{ HideCategorySelect|safe }}">
							<select name="CategoryId" id="CategoryId" class="Field250">
								<option value="">{% lang 'ChooseACategory' %}</option>
								{{ CategoryOptions|safe }}
							</select>
							<img onmouseout="HideHelp('a1');" onmouseover="ShowHelp('a1', '{% lang 'ImportProductsCategory' %}', '{% lang 'ImportProductsCategoryDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display: none;" id="a1"></div>
						</div>

						<div style="display:{{ HideCategoryTextbox|safe }}" id="HideCategoryBox">
							<input type="text" name="CategoryName" id="CategoryName" class="Field250" />
							<img onmouseout="HideHelp('b1');" onmouseover="ShowHelp('b1', '{% lang 'ImportProductsCategory' %}', '{% lang 'ImportProductsCategoryCreateDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display: none;" id="b1"></div>
						</div>
					</div>
				</td>
			</tr>

			<tr class="BulkImportRowHide">
				<td class="FieldLabel">
					<span class="Required">&nbsp;</span>&nbsp;{% lang 'ImportOverride' %}
				</td>
				<td>
					<label><input type="checkbox" name="OverrideDuplicates" id="Override" value="1" /> {% lang 'YesImportOverride' %}</label>
					<img onmouseout="HideHelp('a2');" onmouseover="ShowHelp('a2', '{% lang 'ImportOverride' %}', '{% lang 'ImportOverrideDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
					<div style="display:none" id="a2"></div>

					<div style="display: none" id="HideOverrideOptions">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="DeleteImages" value="1" /> {% lang 'DeleteExistingImages' %}</label>
					<br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="DeleteDownloads" value="1" /> {% lang 'DeleteExistingDownloads' %}</label>
					<div style="display: none;" id="b1"></div>
				</div>
				</td>
			</tr>
			<tr>
				<td class="FieldLabel">
					<span class="Required">&nbsp;</span>&nbsp;{% lang 'ImportIgnoreBlanks' %}
				</td>
				<td>
				<label><input type="checkbox" name="IgnoreBlankFields" value="1" checked="checked"/>&nbsp;{% lang 'ImportIgnoreBlankFields' %}<label>
					<img onmouseout="HideHelp('IgnoreBlankFieldsHelp');" onmouseover="ShowHelp('IgnoreBlankFieldsHelp', '{% lang 'ImportIgnoreBlankFieldsHelpTitle' %}', '{% lang 'ImportIgnoreBlankFieldsHelp' %}')" src="images/help.gif" width="24" height="16" border="0"><div id="IgnoreBlankFieldsHelp" style="display:none"></div>
				</td>
			</tr>
		</table>
		  <table class="Panel">
			<tr>
			  <td class="Heading2" colspan=2>{% lang 'ImportFileDetails' %}</td>
			</tr>
			<tr>
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'ImportFile' %}:
				</td>
				<td>
					<div>
						<label>
							<input id="ProductImportUseUpload" type="radio" name="useserver" value="0" checked="checked" onclick="ToggleSource();" />
							{% lang 'ImportFileUpload' %}
							{% lang 'ImportMaxSize' with [
								'maxSize': ImportMaxSize
							]%}
						</label>
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'ImportFileUpload' %}', '{% lang 'ImportFileUploadDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display: none;" id="d1"></div>
					</div>
					<div id="ProductImportUploadField" style="margin-left: 25px;">
						<input type="file" name="importfile" id="ImportFile" class="Field250" />
					</div>

					<div>
						<label><input id="ProductImportUseServer" type="radio" name="useserver" value="1" onclick="ToggleSource();" /> {% lang 'ImportFileServer' %}</label>
						<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'ImportFileServer' %}', '{% lang 'ImportFileServerDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display: none;" id="d2"></div>
					</div>
					<div id="ProductImportServerField" style="margin-left: 25px; display: none;">
						<select name="serverfile" id="ServerFile" class="Field250">
							<option value="">{% lang 'ImportChooseFile' %}</option>
							{{ ServerFiles|safe }}
						</select>
					</div>
					<div id="ProductImportServerNoList" style="margin: 5px 0 0 25px; display: none; font-style: italic;" class="Field250">
						{% lang 'FieldNoServerFiles' %}
					</div>
				</td>
			</tr>

			<tr class="BulkImportRowHide">
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'ImportContainsHeaders' %}
				</td>
				<td>
					<label><input type="checkbox" name="Headers" id="Headers" value="1" /> {% lang 'YesImportContainsHeaders' %}</label>
					<img onmouseout="HideHelp('d3');" onmouseover="ShowHelp('d3', '{% lang 'ImportContainsHeaders' %}', '{% lang 'ImportContainsHeadersDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
					<div style="display:none" id="d3"></div>
				</td>
			</tr>

			<tr class="BulkImportRowHide">
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'ImportFieldSeparator' %}:
				</td>
				<td>
					<input type="text" name="FieldSeparator" id="FieldSeparator" class="Field250" value="{{ FieldSeparator|safe }}" />
					<img onmouseout="HideHelp('d4');" onmouseover="ShowHelp('d4', '{% lang 'ImportFieldSeparator' %}', '{% lang 'ImportFieldSeparatorDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
					<div style="display:none" id="d4"></div>
				</td>
			</tr>

			<tr class="BulkImportRowHide">
				<td class="FieldLabel">
					<span class="Required">*</span>&nbsp;{% lang 'ImportFieldEnclosure' %}:
				</td>
				<td>
					<input type="text" name="FieldEnclosure" id="FieldEnclosure" class="Field250" value='{{ FieldEnclosure|safe }}' />
					<img onmouseout="HideHelp('d5');" onmouseover="ShowHelp('d5', '{% lang 'ImportFieldEnclosure' %}', '{% lang 'ImportFieldEnclosureDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
					<div style="display:none" id="d5"></div>
				</td>
			</tr>
		</table>
		<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
			<tr>
				<td width="200" class="FieldLabel">
					&nbsp;
				</td>
				<td>
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
					<input type="submit" value="{% lang 'Next' %} &raquo;" class="FormButton" />
				</td>
			</tr>
		</table>
		</td>
	</tr>
	</table>
</div>
</form>

<script type="text/javascript">
	function ConfirmCancel()
	{
		if(confirm('{% lang 'ConfirmCancelImport' %}'))
			window.location = 'index.php?ToDo=manageProducts';
	}

	function ToggleCategory()
	{
		var e = document.getElementById('AutoCategoryCheck');
		if(e.checked == true)
		{
			document.getElementById('ManualCategory').style.display = 'none';
		}
		else
		{
			document.getElementById('ManualCategory').style.display = '';
		}
	}
	ToggleCategory();

	function CheckImportProductForm()
	{

		var f= document.getElementById('AutoCategoryCheck');
		if(f.checked != true)
		{
			if(document.getElementById('HideCategoryBox').style.display == "none")
			{
				var f = document.getElementById('CategoryId');
				if(f.selectedIndex < 1)
				{
					alert('{% lang 'NoSelectedCategoryName' %}');
					f.focus();
					return false;
				}
			}
			else
			{
				var f = document.getElementById('CategoryName');
				if(f.value == '')
				{
					alert('{% lang 'NoCategoryName' %}');
					f.focus();
					return false;
				}
			}
		}
		var f = document.getElementById('ProductImportUseUpload');
		if(f.checked == true)
		{
			var f = document.getElementById('ImportFile');
			if(f.value == '')
			{
				alert('{% lang 'NoImportFile' %}');
				f.focus();
				return false;
			}
		}
		else
		{
			var f = document.getElementById('ServerFile');
			if(f.value < 1)
			{
				alert('{% lang 'NoImportFile' %}');
				f.focus();
				return false;
			}
		}

		var f = document.getElementById('FieldSeparator');
		if(f.value == '')
		{
			alert('{% lang 'NoImportFieldSeparator' %}');
			f.focus();
			return false;
		}

		var f = document.getElementById('FieldEnclosure');
		if(f.value == '')
		{
			alert('{% lang 'NoImportFieldEnclosure' %}');
			f.focus();
			return false;
		}
		return true;
	}

	function ToggleSource()
	{
		var file = document.getElementById('ProductImportUseUpload');
		if(file.checked == true)
		{
			document.getElementById('ProductImportUploadField').style.display = '';
			document.getElementById('ProductImportServerField').style.display = 'none';
			document.getElementById('ProductImportServerNoList').style.display = 'none';
		}
		else
		{
			document.getElementById('ProductImportUploadField').style.display = 'none';
			if(document.getElementById('ProductImportServerField').getElementsByTagName('SELECT')[0].options.length == 1)
			{
				document.getElementById('ProductImportServerNoList').style.display = '';
			}
			else
			{
				document.getElementById('ProductImportServerField').style.display = '';
			}
		}
	}

	$("#BulkEditTemplate").change(function() {
		var disabled = '';
		if ($(this).attr('checked')) {
			disabled = 'disabled';
			$("#AutoCategoryCheck").attr('checked', true);
			ToggleCategory();
		}

		$("#Headers").attr({
			'checked': $(this).attr('checked'),
			'disabled': disabled

		});

		$("#Override").attr({
			'checked': $(this).attr('checked'),
			'disabled': disabled
		});

		$("#Override").change();

		$(".BulkImportRowHide").toggle();
	});

	$("#Override").change(function() {
		if ($(this).attr('checked')) {
			$("#HideOverrideOptions").show();
		}
		else {
			$("#HideOverrideOptions").hide();
		}
	});
</script>
