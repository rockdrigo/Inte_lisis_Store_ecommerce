<table id="OuterPanel" cellSpacing="0" cellPadding="0" width="95%">
	<tr>
		<td class="Heading1">
			{% lang 'QuickBooksShowSyncPopupHeading' %}
		</td>
	</tr>
	<tr>
		<td class="Intro" id="ImportList">
			<div id="ProgressBarBlock" class="IntroItem">
				<div>
					<div style="position: relative;border: 1px solid #ccc; width: 300px; padding: 0px; margin: 0 auto;">
						<div id="ProgressBarBar" style="margin: 0; padding: 0; background: url(images/progressbar.gif) no-repeat; height: 20px; width: 0%;">
							&nbsp;
						</div>
						<div style="position: absolute; top: 2px; left: 0; text-align: center; width: 300px; font-weight: bold;" id="ProgressBarPercentage">&nbsp;</div>
					</div>
					<div id="ProgressBarStatus" style="text-align: center;">&nbsp;</div>
				</div>
			</div>
		</td>
	</tr>
</table>
<script type="text/javascript"><!--

	lang.QuickBooksShowSyncPopupSuccess = "{% lang 'QuickBooksShowSyncPopupSuccess' %}";
	lang.QuickBooksShowSyncPopupSectionFailed = "{% lang 'QuickBooksShowSyncPopupSectionFailed' %}";
	lang.QuickBooksShowSyncPopupFailed = "{% lang 'QuickBooksShowSyncPopupFailed' %}";
	lang.QuickBooksShowSyncPopupSection = "{% lang 'QuickBooksShowSyncPopupSection' %}";

	var AccountingImport = {
		'Sections': [],
		'CurrentSectionKey': 0,
		'ModuleId': '',
		'Total': 0,
		'SectionTotal': 0,
		
		'Init':
			function(sections, moduleid)
			{
				var i, msg, passed = false;
				
				if (typeof(sections.pop) == 'undefined' || sections.length == 0 || moduleid == '') {
					return false;
				}
				
				AccountingImport.Sections = sections;
				AccountingImport.CurrentSectionKey = 0;
				AccountingImport.ModuleId = moduleid;
				
				AccountingImport.Import(true);
			},
		
		'Import':
			function(reset)
			{
				var data = {
					'ToDo' : 'importAccountingSettingsSyncNodes',
					'section' : AccountingImport.Sections[AccountingImport.CurrentSectionKey],
					'moduleid' : AccountingImport.ModuleId
				}
				
				if (typeof(reset) !== 'undefined' && reset == true) {
					data['reset'] = 1;
					
					AccountingImport.SectionTotal = 0;
					AccountingImport.MovePercentageBar(0);
				}
				
				$.ajax({
					'url': 'index.php',
					'type': 'post',
					'dataType': 'json',
					'data': data,
					'success': AccountingImport.ImportCallback
				});
			},
		
		'ImportCallback':
			function(data)
			{
				if (data.status) {
					AccountingImport.SectionTotal += data.total;
					AccountingImport.Total += data.total;
					AccountingImport.MovePercentageBar(data.percent);
				}
				
				if (!data.status || parseInt(data.percent) == 100) {
					AccountingImport.CurrentSectionKey++;
					
					if (AccountingImport.CurrentSectionKey < AccountingImport.Sections.length) {
						return AccountingImport.Import(true);
					} else {
						return AccountingImport.Finish();
					}
				}
				
				AccountingImport.Import();
			},
		
		'Finish':
			function()
			{
				var msg;
				
				msg = lang.QuickBooksShowSyncPopupSuccess;
				msg = msg.replace(/\%s/, AccountingImport.Total);
				
				alert(msg);
			
				window.parent.location.href = 'index.php?ToDo=viewAccountingSettings&currentTab=2';
			},
		
		'MovePercentageBar':
			function(percent)
			{
				var section = AccountingImport.Sections[AccountingImport.CurrentSectionKey];
				
				$('#ProgressBarStatus').text(lang.QuickBooksShowSyncPopupSection + ' ' + AccountingImport.SectionTotal + ' ' + section + "(s)");
				$('#ProgressBarPercentage').text(percent + '%');
				$('#ProgressBarBar').css('width', percent + '%');
			}
	}
	
	$(document).ready(
		function()
		{
			AccountingImport.Init({{ ImportCategories|safe }}, '{{ ModuleID|safe }}');
		}
	);

//--></script>
