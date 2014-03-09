<div style="font-weight: bold; font-size: 14px; padding-bottom: 5px;">{{ Name|safe }}</div>
<a href='{{ DefaultPreviewImageFull|safe }}?height=300&amp;width=300' id="{{ PreviewIDURL|safe }}" class="thickbox">
	<img src='{{ DefaultPreviewImageSmall|safe }}' style='border: 1px solid #CCCCCC;' width="200" height="80" id="{{ PreviewID|safe }}"></a><br/><br/>
	<input type="button" class="SmallButton" value="{{ ButtonText|safe }}" onclick="DownloadNewLogo('{{ LogoId|safe }}', {{ PopupWidth|safe }}, {{ PopupHeight|safe }});" />
<br/>
