<table id="OuterPanel" cellSpacing="0" cellPadding="0" width="95%">
	<tr>
		<td class="Heading1">
			{% lang 'UpgradeInProgress' %}
		</td>
	</tr>
	<tr>
		<td class="Intro">
			<div>{{ UpgradeIntro|safe }}</div>
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
		</td>
	</tr>
</table>
<script type="text/javascript">
	function UpdateProgress(status, percentage)
	{
		var f = document.getElementById('progressBarPercentage');
		f.style.width = parseInt(percentage) + "%";
		var f = document.getElementById('progressPercent');
		f.innerHTML = parseInt(percentage) + "%";
		var f = document.getElementById('progressBarStatus');
		f.innerHTML = status;
	}

	function UpgradeFinished()
	{
		self.parent.location = 'index.php?ToDo=showUpgradeThanks';
	}

	function ShowErrorPage()
	{
		self.parent.location = 'index.php?ToDo=showUpgradeErrors';
	}

	UpdateProgress('{{ RunningStepOfX|safe }}', '{{ PercentComplete|safe }}');
</script>
<!-- iframe which does all of the work -->
<iframe src="index.php?ToDo=runUpgrade&random={{ Random|safe }}" width="1" height="1" frameborder="0" border="0"></iframe>