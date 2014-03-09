<div style="font-weight: bold; font-size: 14px; padding-bottom: 5px;">{{ Name|safe }}</div>
<a href='{{ DefaultPreviewImageFull|safe }}' id="{{ PreviewIDURL|safe }}" class="thickbox">
	<img src='{{ DefaultPreviewImageSmall|safe }}' style='border: 0px' class="previewImage" />
</a>
<div style="padding-top:5px;padding-bottom:5px;">
{{ ColorList|safe }}
</div>
<input type="button" class="SmallButton" value="{{ ButtonText|safe }}" onclick="disableLoadingIndicator = true; DownloadTemplate('{{ TemplateId|safe }}', {{ PopupHeight|safe }}, {{ PopupWidth|safe }});" />
<br />