<tr>
	<td class="Heading2" colspan="2">{% lang 'CSVSettingsTitle' %}</td>
</tr>
<tr>
	<td class="FieldLabel">
		<span class="Required">*</span>&nbsp;{% lang 'FieldSeparator' %}:
	</td>
	<td>
		<input type="text" id="FieldSeparator" name="CSV[FieldSeparator]" style="width: 50px;" maxlength="3" value="{{ SettingFieldSeparator|safe }}" />
		<img onmouseout="HideHelp('dfieldSeparator');" onmouseover="ShowHelp('dfieldSeparator', '{% lang 'FieldSeparator' %}', '{% lang 'FieldSeparatorHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
		<div style="display: none;" id="dfieldSeparator"></div>
	</td>
</tr>
<tr>
	<td class="FieldLabel">
		&nbsp;&nbsp;{% lang 'FieldEnclosure' %}:
	</td>
	<td>
		<input type="text" id="FieldEnclosure" name="CSV[FieldEnclosure]" style="width: 50px;" maxlength="1" value="{{ SettingFieldEnclosure|safe }}" />
		<img onmouseout="HideHelp('dfieldEnclosure');" onmouseover="ShowHelp('dfieldEnclosure', '{% lang 'FieldEnclosure' %}', '{% lang 'FieldEnclosureHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
		<div style="display: none;" id="dfieldEnclosure"></div>
	</td>
</tr>
<tr>
	<td class="FieldLabel">
		&nbsp;&nbsp;{% lang 'IncludeHeader' %}
	</td>
	<td>
		<label><input type="checkbox" id="IncludeHeader" name="CSV[IncludeHeader]" value="1" {{ SettingIncludeHeader|safe }} />{% lang 'YesIncludeHeader' %}</label>&nbsp;
		<img onmouseout="HideHelp('dincludeHeader');" onmouseover="ShowHelp('dincludeHeader', '{% lang 'IncludeHeader' %}', '{% lang 'IncludeHeaderHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
		<div style="display: none;" id="dincludeHeader"></div>
	</td>
</tr>
<tr>
	<td class="FieldLabel">
		&nbsp;&nbsp;{% lang 'BlankLine' %}
	</td>
	<td>
		<label><input type="checkbox" id="BlankLine" name="CSV[BlankLine]" value="1" {{ SettingBlankLine|safe }} />{% lang 'YesBlankLine' %}</label>&nbsp;
	</td>
</tr>
<tr>
	<td class="FieldLabel">
		&nbsp;&nbsp;{% lang 'SubItems' %}
	</td>
	<td>
		<select id="SubItems" name="CSV[SubItems]">
			<option value="combine" {{ SettingcombineSelected|safe }}>{% lang 'CombineSubItems' %}</option>
			<option value="expand" {{ SettingexpandSelected|safe }}>{% lang 'ExpandSubItems' %}</option>
			<option value="rows" {{ SettingrowsSelected|safe }}>{% lang 'RowsSubItems' %}</option>
		</select>
		<img onmouseout="HideHelp('dsubItems');" onmouseover="ShowHelp('dsubItems', '{% lang 'SubItems' %}', '{% lang 'CombineSubItemsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
		<div style="display: none;" id="dsubItems"></div>
		<div style="margin-top: 3px; {{ DisplaySubItems|safe }}" id="SubItemToggle">
			<img src="images/nodejoin.gif" style="vertical-align: middle;" />
			{% lang 'SubItemSeparator' %}: <input type="text" id="SubItemSeparator" name="CSV[SubItemSeparator]" style="width: 50px;" maxlength="1" value="{{ SettingSubItemSeparator|safe }}" />
			<img onmouseout="HideHelp('dsubItemSep');" onmouseover="ShowHelp('dsubItemSep', '{% lang 'SubItemSeparator' %}', '{% lang 'SubItemSeparatorHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
			<div style="display: none;" id="dsubItemSep"></div>
		</div>
	</td>
</tr>
<tr>
	<td class="FieldLabel">
		&nbsp;&nbsp;{% lang 'LineEnding' %}:
	</td>
	<td>
		<select class="Field100" name="CSV[LineEnding]" id="LineEnding">
			<option value="Windows" {{ SettingWindowsSelected|safe }}>Windows</option>
			<option value="Unix" {{ SettingUnixSelected|safe }}>Mac/Unix</option>
		</select>
	</td>
</tr>

<script type="text/javascript">
	$("#SubItems").change(function() {
		if ($("#SubItems").val() == "combine") {
			$("#SubItemToggle").show();
		}
		else {
			$("#SubItemToggle").hide();
		}
	});
	$("#SubItems").change();

	function ValidateCSV() {
		if ($('#FieldSeparator').val().length == 0 || ($('#FieldSeparator').val().length > 1 && $('#FieldSeparator').val().toLowerCase() != 'tab')) {
			alert('{% lang 'FieldSeparatorValidate' %}');
			$('#FieldSeparator').focus();
			return false;
		}

		if ($('#FieldEnclosure').val().length == 0) {
			alert('{% lang 'FieldEnclosureValidate' %}');
			$('#fieldEnclosure').focus();
			return false;
		}

		if ($('#SubItemSeparator').val().length == 0) {
			alert('{% lang 'SubItemSeparatorValidate' %}');
			$('#SubItemSeparator').focus();
			return false;
		}
		else if ($('#SubItemSeparator').val() == $('#FieldSeparator').val()) {
			alert('{% lang 'SubItemSeparatorIsSame' %}');
			$('#SubItemSeparator').focus();
			return false;
		}

		return true;
	}
</script>
