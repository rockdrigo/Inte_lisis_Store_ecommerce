<table id="OuterPanel" cellSpacing="0" cellPadding="0" width="95%">
	<tr>
		<td class="Heading1">
			{% lang 'BackupInProgress' %}
		</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'BackupInProgressIntro' %}</p>
		</td>
	</tr>
	<tr>
		<td class="Intro">
			<div class="IntroItem">
				<div>
					<div style="position: relative;border: 1px solid #ccc; width: 300px; padding: 0px; margin: 0 auto;">
						<div id="progressBarPercentage" style="margin: 0; padding: 0; background: url(images/progressbar.gif) no-repeat; height: 20px; width: 0%;">
							&nbsp;
						</div>
						<div style="position: absolute; top: 2px; left: 0; text-align: center; width: 300px; font-weight: bold;" id="progressPercent">&nbsp;</div>
					</div>
					<div id="progressBarStatus" style="text-align: center;">&nbsp;</div>
				</div>
			</div>
			<div style="text-align: center;"><input type="button" value="{% lang 'CancelBackup' %}" class="SmallButton" onclick="CancelBackup()" /></div>
		</td>
	</tr>
</table>
<script type="text/javascript">
	function updateProgress(percentage, status)
	{
		var f = document.getElementById('progressBarPercentage');
		f.style.width = parseInt(percentage) + "%";
		var f = document.getElementById('progressPercent');
		f.innerHTML = parseInt(percentage) + "%";
		var f = document.getElementById('progressBarStatus');
		f.innerHTML = status;
	}

	function CancelBackup()
	{
		if(confirm('{% lang 'ConfirmCancelBackup' %}'))
		{
			self.parent.tb_remove();
			self.parent.location = 'index.php?ToDo=cancelBackup&file={{ BackupFile|safe }}';
		}
	}
</script>