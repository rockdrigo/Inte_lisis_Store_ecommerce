<form method="post" id="LogForm" action="index.php?ToDo=systemLog" onsubmit="return SearchSystemLog(this);">
	<input type="hidden" name="SortURL" id="SortURL" value="index.php?ToDo=systemLogGrid{{ SortURL|safe }}" />
	<input type="hidden" name="CurrentTab" id="CurrentTab1" value="{{ CurrentTab|safe }}" />
	<table id="SystemLogOptions" class="IntroTable" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td class="Intro" style="padding-top: 10px;">
				<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }}  />
				<input type="button" name="DeleteAll" value="{% lang 'DeleteAll' %}" class="SmallButton" onclick="ConfirmDeleteAll()" {{ DisableDelete|safe }}  />
			</td>
			<td align="right" nowrap="nowrap" style="padding-top: 10px;">
				<select name="severity" id="logSeverity">
					<option value="0">{% lang 'LogAllSeverities' %}</option>
					<option value="1" {{ Severity1Selected|safe }}>{% lang 'LogSeverity1' %}</option>
					<option value="2" {{ Severity2Selected|safe }}>{% lang 'LogSeverity2' %}</option>
					<option value="3" {{ Severity3Selected|safe }}>{% lang 'LogSeverity3' %}</option>
					<option value="4" {{ Severity4Selected|safe }}>{% lang 'LogSeverity4' %}</option>
				</select>
				&nbsp;
				<select name="logtype" id="logType">
					<option value="">{% lang 'LogAllTypes' %}</option>
					{{ LogSearchTypeSelect|safe }}
				</select>
				&nbsp;
				<input type="text" id="logSummary" class="Button" value="{{ SummaryValue|safe }}" size="20" />
			</td>
			<td width="1" style="padding-left: 5px;">
				<input id="SearchButton" type="image" border="0" style="vertical-align: middle;" src="images/searchicon.gif" name="SearchButton" />
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="right">
				<a href="index.php?ToDo=systemLog" style="display: {{ HideClearResults|safe }}" id="SearchClearButton" onclick="return ClearSystemResults(this);">{% lang 'ClearResults' %}</a>
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
	<table class="GridPanel SortableGrid" cellspacing="1" cellpadding="2" border="0" style="width:100%;">
		<tr align="right">
			<td colspan="8" style="padding:6px 0px 6px 0px" class="PagingNav">
				{{ Nav|safe }}
			</td>
		</tr>

		<tr class="Heading3">
			<td align="center" width="1"><input type="checkbox" onclick="$(this).parents('form').find('input[type=checkbox]').attr('checked', this.checked);"></td>
			<td colspan="2">
				{% lang 'LogSeverity' %} &nbsp;
				{{ SortLinksSeverity|safe }}
			</td>
			<td>&nbsp;</td>
			<td>
				{% lang 'LogType' %} &nbsp;
				{{ SortLinksType|safe }}
			</td>
			<td>
				{% lang 'LogModule' %} &nbsp;
				{{ SortLinksModule|safe }}
			</td>
			<td>
				{% lang 'LogSummary' %} &nbsp;
				{{ SortLinksSummary|safe }}
			</td>
			<td>
				{% lang 'Date' %} &nbsp;
				{{ SortLinksDate|safe }}
			</td>
		</tr>
		{{ LogGrid|safe }}
		<tr align="right">
			<td colspan="8" style="padding:6px 0px 6px 0px" class="PagingNav">
				{{ Nav|safe }}
			</td>
		</tr>
	</table>
</form>