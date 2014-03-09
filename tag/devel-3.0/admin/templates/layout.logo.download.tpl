<div class="Text" id="LogoTemplateIntro">
	<a href="javascript:CheckNewLogos()">{% lang 'ClickToDownloadLogos' %}</a>. {% lang 'DownloadLogoIntro' %}
</div>


<div style="padding: 0px 0px 5px 10px; display: none;" class="Text" id="DownloadTemplateMessage"></div>
<form method='post' action="" id="frmTemplates">
<div style="text-align: center; display: inline; clear:both;" id="LogoGrid">
	{{ LogoGrid|safe }}
</div>