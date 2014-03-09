<script type="text/javascript" src="../javascript/jquery/plugins/ajax.file.upload.js?{{ JSCacheToken|safe }}"></script>
<div id="ModalTitle">{% lang 'UploadBulkFile' %}</div>

<div id="ModalContent">


<p><b>{% lang 'SelectImportFile' %}:</b></p>

<input type="file" id="BulkImportRedirectsFile" name="BulkImportRedirectsFile" />&nbsp;{{ ImportMaxSize|safe }}<br /><br />

<label><input type="checkbox" id="Headers" name="Headers" value="1" /> {% lang 'FileContainsHeaders' %}</label>

</div>
<div id="ModalButtonRow">
	<input type="button" class="Button" value="{% lang 'Cancel' %}" onclick="$.iModal.close();" style="float: left;" />
	<input class="Submit" class="Submit" type="submit" value="{% lang 'BulkImport' %}"  onclick="Redirects.UploadBulkFile();"  />
</div>
