<script type="text/javascript">
function PurchaseTemplate(TemplateName){
		var w = screen.availWidth;
		var h = screen.availHeight;
		var l = (w/2) - 120;
		var t = (h/2) - 25;
		{{ TemplatePurchaseCode|safe }}
}

function InsertLicense(){
	var key = document.getElementById('lKey').value;
	if(key == ''){
		alert('{% lang 'NoKeyEntered' %}');
	}else{
		// do the ajax request
		jQuery.ajax({
			url: 'remote.php',
			data: 'w=checktemplatekey&template={{ TemplateName|safe }}&key='+$('#lKey').val(),
			dataType: 'xml',
			type: 'POST',
			success: InsertLicenseReturn
		});
	}
}

function InsertLicenseReturn(data){
	if(data.getElementsByTagName('status')[0].firstChild.nodeValue == 1){
		DownloadFile();
	}else{
		alert(data.getElementsByTagName('message')[0].firstChild.nodeValue);
	}
}

function DownloadFile(){
	// do the ajax request
	jQuery.ajax({
		url: 'remote.php',
		data: 'w=downloadtemplatefile&template={{ TemplateName|safe }}&key='+$('#lKey').val(),
		type: 'POST',
		dataType: 'xml',
		success: DownloadFileReturn
	});

	$("#TB_ajaxContent").animate({height: '58px'}, 100);
	document.getElementById('ContentDiv').innerHTML = '<center><fieldset>	<legend id="legendText">{{ DownloadPleaseWait|safe }}</legend> <div id="contentDiv">		<img src="images/loadingAnimation.gif" > </div></fieldset></center>';
}

function DownloadFileReturn(data){
	if(data.getElementsByTagName('status')[0].firstChild.nodeValue == 1){
		$('#dl_' + data.getElementsByTagName('template')[0].firstChild.nodeValue).hide('normal');
	}else {

	}

	$("#ContentDiv").fadeOut("normal");
	$("#TB_ajaxContent").animate({height: '120px'}, 100);

	// message can be a success or error message
	document.getElementById('ContentDiv').innerHTML = '<fieldset>	<legend id="legendText">{% lang 'DownloadComplete' %}</legend><div class="Text"style="text-align: center;">' + data.getElementsByTagName('message')[0].firstChild.nodeValue + '<br/><br/><input type="button" class="Button" value="OK" accesskey="O" onclick="tb_remove();document.location.href=\'index.php?ToDo=viewTemplates\';" style="width: 50px"></div></fieldset>';

	$("#ContentDiv").fadeIn("normal");
}
</script>
<div id="ContentDiv">
	<fieldset>
		<legend>{% lang 'WouldLikeToPurchase' %}</legend>
		<div class="Text">{{ Message|safe }}</div>
		<center><input type="button" onclick="javascript:PurchaseTemplate('{{ TemplateName|safe }}');" value="{% lang 'PurchaseTemplate' %} {{ TemplateAmount|safe }}" class="SmallButton"></center>
	</fieldset>

	<br/>

	<fieldset>
		<legend>{% lang 'IAlreadyPurchased' %}</legend>
		<div class="Text" >{% lang 'EnterLicense' %}:</div>
		<input type="text" name="lKey" id="lKey" class="Field"  style="width: 135px"><input type="button" onclick="javascript:InsertLicense();" value="{% lang 'OkKey' %}" class="SmallButton" style="width: 120px">
	</fieldset>
</div>