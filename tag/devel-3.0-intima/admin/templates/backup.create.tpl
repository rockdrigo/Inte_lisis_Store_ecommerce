
	<form action="index.php?ToDo=createBackup2" id="frmBanner" method="post">
	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{% lang 'CreateBackup' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'CreateBackupIntro' %}</p>
			{{ Message|safe }}
			<p>
				<input type="submit" name="SubmitButton1" style="width:100px" value="{% lang 'StartBackup' %}..." class="FormButton" onclick="StartBackup(); return false;">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
			</p>
		</td>
	  </tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'BackupSettings' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'BackupMethod' %}:
					</td>
					<td>
						<label style="display: {{ HideLocalMethod|safe }}"><input type="radio" name="backupmethod" value="local" {{ LocalChecked|safe }} /> {% lang 'BackupMethodLocal' %}<br /></label>
						<label style="display: {{ HideFTPMethod|safe }}"><input type="radio" name="backupmethod" value="ftp" {{ FTPChecked|safe }} /> {% lang 'BackupMethodRemoteFTP' %} ({{ RemoteFTPHost|safe }})</label>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">&nbsp;</span>&nbsp;{% lang 'BackupDatabase' %}
					</td>
					<td>
						<label><input type="checkbox" name="backupdb" value="1" checked="checked" onclick="ToggleDBBackup(this);" /> {% lang 'YesBackupDatabase' %}</label>
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'BackupDatabase' %}', '{% lang 'BackupDatabaseHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d1"></div>
						<div id="DBBackupDetails" style="display: none;">
							<ul>
								<li>{% lang 'BackupDatabaseTableCount' %} {{ TableCount|safe }}</li>
								<li>{% lang 'BackupDatabaseRowCount' %} {{ RowCount|safe }}</li>
								<li>{% lang 'BackupDatabaseMaxRows' %} {{ MaxRowCount|safe }} ({{ MaxRowTable|safe }})</li>
								<li>{% lang 'BackupDatabaseMinRows' %} {{ MinRowCount|safe }} ({{ MinRowTable|safe }})</li>
							</ul>
						</div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">&nbsp;</span>&nbsp;{% lang 'BackupProductImages' %}
					</td>
					<td>
						<label><input type="checkbox" name="backupimages" value="1" checked="checked" onclick="ToggleImageBackup(this);" /> {% lang 'YesBackupProductImages' %}</label>
						<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'BackupProductImages' %}', '{% lang 'BackupProductImagesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d2"></div>
						<div id="ImageBackupDetails" style="display: none;">
							<ul>
								<li>{% lang 'BackupProductImagesCount' %} {{ ImageCount|safe }}</li>
							</ul>
						</div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">&nbsp;</span>&nbsp;{% lang 'BackupDigitalProducts' %}
					</td>
					<td>
						<label><input type="checkbox" name="backupdigitalproducts" value="1" checked="checked" onclick="ToggleDigitalBackup(this);" /> {% lang 'YesBackupDigitalProducts' %}</label>
						<img onmouseout="HideHelp('d3');" onmouseover="ShowHelp('d3', '{% lang 'BackupDigitalProducts' %}', '{% lang 'BackupDigitalProductsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d3"></div>
						<div id="DigitalBackupDetails" style="display: none;">
							<ul>
								<li>{% lang 'BackupDigitalProductsCount' %} {{ DigitalProductCount|safe }}</li>
							</ul>
						</div>
					</td>
				</tr>

				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap"><input type="submit" name="SubmitButton1" style="width:100px" value="{% lang 'StartBackup' %}..." class="FormButton" onclick="StartBackup(); return false;">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Sep" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
	</table>

	</div>
	</form>

	<script type="text/javascript">
		function ToggleDBBackup(element)
		{
			if(element.checked == true) {
				document.getElementById('DBBackupDetails').style.display = '';
			}
			else {
				document.getElementById('DBBackupDetails').style.display = 'none';
			}
		}
		ToggleDBBackup(document.getElementsByTagName('input')[2]);

		function ToggleImageBackup(element)
		{
			if(element.checked == true) {
				document.getElementById('ImageBackupDetails').style.display = '';
			}
			else {
				document.getElementById('ImageBackupDetails').style.display = 'none';
			}
		}
		ToggleImageBackup(document.getElementsByTagName('input')[5]);

		function ToggleDigitalBackup(element)
		{
			if(element.checked == true) {
				document.getElementById('DigitalBackupDetails').style.display = '';
			}
			else {
				document.getElementById('DigitalBackupDetails').style.display = 'none';
			}
		}
		ToggleDigitalBackup(document.getElementsByTagName('input')[6]);

		function ConfirmCancel() {
			if(confirm("{% lang 'ConfirmCancelBackup' %}"))
				document.location.href = "index.php?ToDo=viewBackups";
		}

		function StartBackup() {
			var inputs = document.getElementsByTagName('INPUT');
			var url = '';
			for(var i = 0; i < inputs.length; ++i) {
				if(inputs[i].type == "submit" || inputs[i].type == "button" || ((inputs[i].type == "checkbox" || inputs[i].type == "radio") && inputs[i].checked == false) || inputs[i].offsetHeight == 0) continue;
				url += '&'+inputs[i].name+'='+inputs[i].value;
			}
			tb_show('', 'index.php?ToDo=initBackup'+url+'&keepThis=true&TB_iframe=tue&height=250&width=400&modal=true', '');
		}
	</script>