	<div class="BodyContainer">
	<form method="post" action="index.php?ToDo=deleteBackups">
	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">{% lang 'ManageLocalBackups' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'ManageLocalBackupsIntro' %}</p>
			{{ Message|safe }}
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top" style="padding-bottom:10px">
				<input type="button" name="CreateBackup" value="{% lang 'CreateBackup' %}..." onclick="createBackup()" class="SmallButton" />
				 &nbsp;
				<input type="submit" name="DeleteSelected" value="{% lang 'DeleteSelected' %}" onclick="return deleteBackups()" class="SmallButton" {{ DisableDelete|safe }} />
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr>
		<td style="display: {{ DisplayGrid|safe }}">
			<table class="GridPanel" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
			<tr class="Heading3">
				<td align="center"><input type="checkbox" onclick="$('.DeleteBackup').attr('checked', this.checked);"></td>
				<td>&nbsp;</td>
				<td width="50%" nowrap>
					{% lang 'BackupFileName' %}
				</td>
				<td width="10%" nowrap align="right">
					{% lang 'BackupSize' %}
				</td>
				<td nowrap>
					{% lang 'BackupMTime' %}
				</td>
				<td nowrap>
					{% lang 'Action' %}
				</td>

			</tr>
			{{ BackupGrid|safe }}
			</table>
		</td></tr>
	</table>
	</form>
	</div>
	<script type="text/javascript">
		function createBackup() {
			window.location = 'index.php?ToDo=createBackup';
		}

		function deleteBackups() {
			if($(".DeleteBackup:checked").length == 0)
			{
				alert('{% lang 'NoBackupsSelected' %}');
				return false;
			}

			// Otherwise confirm?
			if(confirm('{% lang 'ConfirmDeleteBackups' %}'))
			{
				return true;
			}

			return false;
		}
	</script>