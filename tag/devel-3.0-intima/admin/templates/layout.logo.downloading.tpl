<center><fieldset style="width: 230px;">
<legend id="legendText">{{ DownloadPleaseWait|safe }}</legend>
	<div id="contentDiv">
		<img src="images/loadingAnimation.gif" width="220" height="19" >
	</div>
</fieldset></center>
<script type="text/javascript">
window.setTimeout('DownloadFile()', 1000);

function DownloadFile(){
	// do the ajax request
	jQuery.ajax({ url: 'remote.php', type: 'POST', dataType: 'xml',
		data: {'w': 'downloadlogofile', 'logo' : '{{ LogoId|safe }}'},
		success: function(xml) {
			DownloadFileReturn(xml);
		}
	});
}

function DownloadFileReturn(xml){
	if($('status', xml).text() == 1){
		$('#dl_' + $('logo', xml).text()).hide('normal');
	}

	$("#contentDiv").fadeOut("normal");
	$("#TB_ajaxContent").animate({height: '180px'}, 100);
	document.getElementById('contentDiv').innerHTML = '<div class="Text">' + $('message', xml).text() + '<br/><br/><input type="button" class="Button" value="OK" accesskey="O" onclick="tb_remove();document.location.href=\'index.php?ToDo=viewTemplates\';" style="width: 50px"></div>';
	document.getElementById('legendText').innerHTML = '{% lang 'DownloadComplete' %}';
	$("#contentDiv").fadeIn("normal");
}


</script>