
	<table cellspacing="0" cellpadding="0" width="100%"  align="center">
		<tr>
			<td class="Heading1">{% lang 'UpdateTemplates' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ NewTemplateIntro|safe }}</p>
			{{ Message|safe }}
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top" height="35">
				<input type="button" name="IndexDownloadButton" value="{% lang 'DownloadSelected' %}" id="IndexDownloadButton" class="Button" style="width:180px" onclick="DownloadSelected()" {{ DisableDownload|safe }} /> &nbsp;<input type="button" name="BackButton" value="{% lang 'ManageTemplates' %}" id="BackButton" class="Button" onclick="document.location.href='index.php?ToDo=viewDownloader'" />
			</td>
			<td class="SmallSearch" align="right">
				&nbsp;
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr>
		<td>
			<table class="GridPanel" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%; display: {{ DisplayGrid|safe }}">
			<form name="frmPlans" id="frmTemplates" action="index.php?ToDo=downloadNewTemplates" method="post">
			<tr class="Heading3">
				<td align="center" style="width:18px"><input type="checkbox" onclick="ToggleInstallBoxes(this.checked)" checked="checked"></td>
				<td>&nbsp;</td>
				<td>
					{% lang 'TemplateName' %} &nbsp;
				</td>
				<td>
					{% lang 'Description' %} &nbsp;
				</td>
				<td>
					{% lang 'Price' %} &nbsp;
				</td>
				<td style="width:150px">
					{% lang 'ColorPreview' %}
				</td>
			</tr>
			{{ TemplateGrid|safe }}
			<tr align="right">
				<td colspan="6">
					{{ Nav|safe }}
				</td>
			</tr>
		</table>
		</td></tr>
	</table>


	<script type="text/javascript">

function TemplatePurchase(TemplateName){
		var w = screen.availWidth;
		var h = screen.availHeight;
		var l = (w/2) - 120;
		var t = (h/2) - 25;

{{ TemplatePurchaseCode|safe }}

}

function PreviewNewTemplate(TemplateURL)
	{
		var w = screen.availWidth;
		var h = screen.availHeight;
		var l =  100;
		var t = 85;
		var height = screen.availHeight * 0.8;
		var width = screen.availWidth * 0.8;

		TemplateURL = '{{ TemplatesPreviewPath|safe }}' + TemplateURL;

		var win = window.open("{{ TemplateWebsite|safe }}/"+TemplateURL, "preview", "width="+width+",height="+height+",top="+t+",left="+l);
		win.focus();
	}
function DownloadNewTemplates(Sel)
	{
		var w = screen.availWidth;
		var h = screen.availHeight;
		var l = (w/2) - 120;
		var t = (h/2) - 25;

		var win = window.open("index.php?ToDo=downloadernew1&zips="+Sel, "downloadTemplates", "top="+t+",left="+l+",width=300,height=150, status=yes");
		win.focus();
	}
		function ToggleInstallBoxes(Status)
		{
			var fp = document.getElementById("frmTemplates").elements;

			for(i = 0; i < fp.length; i++)
			{
				//if(fp[i].value != 1)
					fp[i].checked = Status;
			}
		}

		function DownloadSelected()
		{
			var fp = document.getElementById("frmTemplates").elements;
			var c = 0;
			var sel = "";

			for(i = 0; i < fp.length; i++)
			{
				if(fp[i].type == "checkbox" && fp[i].checked)
				{
					if(fp[i].value != "on")
					{
						c++;
						sel = sel + fp[i].value + ",";
					}
				}
			}

			if(c > 0)
			{
				DownloadNewTemplates(sel);
			}
			else
			{
				alert("{% lang 'ChooseTemplate1' %}");
			}
		}

	</script>