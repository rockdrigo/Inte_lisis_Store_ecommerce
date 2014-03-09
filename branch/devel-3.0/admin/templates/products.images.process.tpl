<div id="ProgressContainer">
	<table id="OuterPanel" cellSpacing="0" cellPadding="0" width="95%">
		<tr>
			<td class="Intro">
				<div>{% lang 'ProcessingImagesIntro' %}</div>
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
</div>
<script type="text/javascript">
	ProcessProductImages.updateProgress(0, '{% lang 'LoadingProcessImages' %}');
	ProcessProductImages.runProcess(0);
</script>