<form method="post" id="AdminLogForm" action="index.php?ToDo=systemLog" onsubmit="return SearchAdminLog(this)">
		<input type="hidden" name="AdminSortURL" id="AdminSortURL" value="index.php?ToDo=administratorLogGrid{{ SortURL|safe }}" />
		<input type="hidden" name="CurrentTab" id="CurrentTab2" value="{{ CurrentTab|safe }}" />
		<table id="AdminLogOptions" class="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
				<td class="Intro" style="padding-top: 10px;">
					<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelectedAdmin()" {{ DisableDelete|safe }}  />
					<input type="button" name="DeleteAll" value="{% lang 'DeleteAll' %}" class="SmallButton" onclick="ConfirmDeleteAllAdmin()" {{ DisableDelete|safe }}  />
				</td>
				<td align="right" nowrap="nowrap" style="padding-top: 10px;">
					<select name="userid" id="adminUserId" onchange="SearchAdminLog(this)">
						<option value="0">{% lang 'AllAdministrators' %}</option>
						{{ AdministratorList|safe }}
					</select>
				</td>
				<td width="1" style="padding-left: 5px; padding-top: 10px;">
					<input id="SearchButton" type="image" border="0" style="vertical-align: middle;" src="images/searchicon.gif" name="SearchButton"/>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align="right">
					<a href="index.php?ToDo=systemLog&amp;CurrentTab=1" onclick="ClearAdminSearchResults(this); return false;" style="display: {{ HideClearAdminResults|safe }}" id="AdminSearchClearButton">{% lang 'ClearResults' %}</a>
				</td>
				<td>&nbsp;</td>
			</tr>
		</table>
		<table class="GridPanel SortableGrid" cellspacing="1" cellpadding="2" border="0" id="AdminLogGrid" style="width:100%;">
			<tr align="right">
				<td colspan="6" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>

			<tr class="Heading3">
				<td align="center" width="1"><input type="checkbox" onclick="$(this).parents('form').find('input[type=checkbox]').attr('checked', this.checked);"></td>
				<td>&nbsp;</td>
				<td>
					{% lang 'Username' %}
					{{ SortLinksName|safe }}
				</td>
				<td>
					{% lang 'Action' %}
				</td>
				<td>
					{% lang 'Date' %}
					{{ SortLinksDate|safe }}
				</td>
				<td nowrap="nowrap">
					{% lang 'IPAddress' %}
					{{ SortLinksIP|safe }}
				</td>
			</tr>
			{{ LogGrid|safe }}
			<tr align="right">
				<td colspan="6" style="padding:6px 0px 6px 0px" class="PagingNav">
					{{ Nav|safe }}
				</td>
			</tr>
		</table>
</form>