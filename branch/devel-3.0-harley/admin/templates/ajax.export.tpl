<div class="ModalTitle">
	{{ ExportName|safe }}
</div>
<div class="ModalContent">
	<div id="exportIntro" style="display: {{ HideExportIntro|safe }}">
		<p>
			{{ ExportIntro|safe }}
		</p>

		<table border="0">
			<tr>
				<td width="1"><img src="images/{{ ExportIcon|safe }}" height="16" width="16" hspace="5" alt="" /></td>
				<td><a href="#" onclick="StartAjaxExport(); return false;"  style="color:#005FA3; font-weight:bold">{{ ExportGenerate|safe }}</a></td>
			</tr>
		</table>

		<div style="display: {{ DisplayAutoExport|safe }}">
			<p><strong>{% lang 'SchedulingAutomaticUpdates' %}</strong></p>
			<p>{% lang 'AutomaticExportIntro' %}</p>
			<p>{% lang 'AutomaticExportIntro2' %}</p>
			<p style="padding-left: 25px">
				<input type="text" class="Field300" onclick="this.select()" readonly="readonly" value="{{ ExportUrl|safe }}" />
			</p>
		</div>
	</div>
	<div id="exportStatus" style="display: none;">
		<p>
			{{ ExportGeneratingIntro|safe }}
		</p>
		<div style="border: 1px solid #ccc; width: 300px; padding: 0px; margin: 0 auto; position: relative;">
			<div id="ProgressBarPercentage" style="margin: 0; padding: 0; background: url(images/progressbar.gif) no-repeat; height: 20px; width: 0%;">
				&nbsp;
			</div>
			<div style="position: absolute; top: 0; left: 0; text-align: center; width: 300px; font-weight: bold;" id="ProgressPercent">&nbsp;</div>
		</div>
		<div id="ProgressBarStatus" style="text-align: center; font-size: 11px; font-family: Tahoma;">{{ ExportGenerating|safe }}</div>
	</div>
	<div id="exportComplete" style="display: none;">
		<p>
			{{ ExportGeneratedIntro|safe }}
		</p>
		<table border="0">
			<tr>
				<td width="1"><img src="images/save.gif" height="16" width="16" hspace="5" alt="" /></td>
				<td><a href="#" onclick="DownloadAjaxExport(); return false;"  style="color:#005FA3; font-weight:bold">{{ ExportDownload|safe }}</a></td>
			</tr>
		</table>
	</div>
	<div id="exportNoProducts" style="display: {{ HideNoProducts|safe }};">
		<p>
			{{ ExportIntro|safe }}
		</p>
		<table border="0">
			<tr>
				<td width="1" valign="top"><img src="images/error.gif" height="16" width="16" hspace="5" alt="" /></td>
				<td style="font-weight: bold;">{% lang 'ExportNoData' %}</td>
			</tr>
		</table>
		<br />
	</div>
</div>
<div class="ModalButtonRow">
	<input type="button" value="{% lang 'Close' %}" onclick="$.iModal.close()" class="SubmitButton" />
</div>
<script type="text/javascript" src="../javascript/jquery/plugins/idletimer/cookie.js?{{ JSCacheToken }}"></script>
<script type="text/javascript">
	function StartAjaxExport() {
		$('#exportStatus').show();
		$('#exportIntro').hide();
		if(g('ExportFrame')) {
			$('#ExportFrame').remove();
		}
		$('#exportStatus').append('<iframe src="index.php?ToDo=AjaxExport&exportsess={{ ExportSessionId|safe }}&action=Export" border="0" frameborder="0" height="1" width="1" id="ExportFrame"></iframe>');
	}

	function AjaxExportError(msg) {
	//	tb_remove();
		alert(msg);
	}

	function UpdateAjaxExportProgress(percentage) {
		$('#ProgressBarPercentage').css('width', parseInt(percentage) + "%");
		$('#ProgressPercent').html(parseInt(percentage) + "%");
		$.cookie('ISC_IdleTimer_LastEvent', new Date);
	}

	function AjaxExportComplete() {
		$('#exportStatus').hide();
		$('#exportComplete').show();
	}

	function CancelAjaxExport() {
		if($('#exportStatus').css('display') != "none") {
			window.location = 'index.php?ToDo=AjaxExport&exportsess={{ ExportSessionId|safe }}&action=CancelExport';
		}
	}

	function DownloadAjaxExport() {
		$.iModal.close();
		window.location = 'index.php?ToDo=AjaxExport&exportsess={{ ExportSessionId|safe }}&action=DownloadExport';
	}
</script>