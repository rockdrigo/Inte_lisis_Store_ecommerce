<form enctype="multipart/form-data" action="index.php?ToDo=importCustomers&Step=2" onsubmit="return ValidateForm(CheckImportCustomerForm)" id="frmImport" method="post">
<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">{% lang 'ImportCustomersStep1' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'ImportCustomersStep1Desc' %}</p>
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
					<span class="Required">&nbsp;</span>&nbsp;{% lang 'ImportOverride' %}
				</td>
				<td>
					<label><input type="checkbox" name="OverrideDuplicates" id="Override" value="1" /> {% lang 'YesImportOverride' %}</label>
					<img onmouseout="HideHelp('a2');" onmouseover="ShowHelp('a2', '{% lang 'ImportOverride' %}', '{% lang 'ImportOverrideDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
					<div style="display:none" id="a2"></div>
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
							<input id="CustomerImportUseUpload" type="radio" name="useserver" value="0" checked="checked" onclick="ToggleSource();" />
							{% lang 'ImportFileUpload' %}
							{% lang 'ImportMaxSize' with [
								'maxSize': ImportMaxSize
							]%}
						</label>
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'ImportFileUpload' %}', '{% lang 'ImportFileUploadDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display: none;" id="d1"></div>
					</div>
					<div id="CustomerImportUploadField" style="margin-left: 25px;">
						<input type="file" name="importfile" id="ImportFile" class="Field250" />
					</div>

					<div>
						<label><input id="CustomerImportUseServer" type="radio" name="useserver" value="1" onclick="ToggleSource();" /> {% lang 'ImportFileServer' %}</label>
						<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'ImportFileServer' %}', '{% lang 'ImportFileServerDesc' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display: none;" id="d2"></div>
					</div>
					<div id="CustomerImportServerField" style="margin-left: 25px; display: none;">
						<select name="serverfile" id="ServerFile" class="Field250">
							<option value="">{% lang 'ImportChooseFile' %}</option>
							{{ ServerFiles|safe }}
						</select>
					</div>
					<div id="CustomerImportServerNoList" style="margin: 5px 0 0 25px; display: none; font-style: italic;" class="Field250">
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
			window.location = 'index.php?ToDo=manageCustomers';
	}

	function CheckImportCustomerForm()
	{
		var f = document.getElementById('CustomerImportUseUpload');
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
		var file = document.getElementById('CustomerImportUseUpload');
		if(file.checked == true)
		{
			document.getElementById('CustomerImportUploadField').style.display = '';
			document.getElementById('CustomerImportServerField').style.display = 'none';
			document.getElementById('CustomerImportServerNoList').style.display = 'none';
		}
		else
		{
			document.getElementById('CustomerImportUploadField').style.display = 'none';
			if(document.getElementById('CustomerImportServerField').getElementsByTagName('SELECT')[0].options.length == 1)
			{
				document.getElementById('CustomerImportServerNoList').style.display = '';
			}
			else
			{
				document.getElementById('CustomerImportServerField').style.display = '';
			}
		}
	}

	$("#BulkEditTemplate").change(function() {
		var disabled = '';
		if ($(this).attr('checked')) {
			disabled = 'disabled';
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
</script>
