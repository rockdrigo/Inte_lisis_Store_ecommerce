
	<form action="index.php?ToDo=saveUpdatedAnalyticsSettings" name="frmAnalyticsSettings" id="frmAnalyticsSettings" method="post" onsubmit="return ValidateForm(CheckAnalyticsSettingsForm)">
		<div class="BodyContainer">
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'AnalyticsSettings' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'AnalyticsSettingsIntro' %}				</p>
				{{ Message|safe }}
				<p>
					<input type="submit" value="{% lang 'Save' %}" class="FormButton" />
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<ul id="tabnav">
					<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'GeneralSettings' %}</a></li>
					<li style="display:none"><a href="#" id="tab1" onclick="ShowTab(1)"></a></li>
					{{ AnalyticsTabs|safe }}
				</ul>
			</td>
		</tr>
		<tr>
			<td>
				<input id="currentTab" name="currentTab" value="0" type="hidden">
				<div id="div0" style="padding-top: 10px;">
					<table width="100%" class="Panel">
						<tr>
							<td class="FieldLabel">
								<label for="storename">{% lang 'AnalyticsMethods' %}:</label>
							</td>
							<td class="PanelBottom">
								<select size="5" multiple name="analyticsproviders[]" id="analyticsproviders" class="Field250 ISSelectReplacement">
									{{ AnalyticsProviders|safe }}
								</select>
								<img onmouseout="HideHelp('d5');" onmouseover="ShowHelp('d5', '{% lang 'AnalyticsMethods' %}', '{% lang 'AnalyticsMethodsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d5"></div>
							</td>
						</tr>
					</table>
				</div>
				<div id="div1" style="padding-top: 10px;">


				</div>
				{{ AnalyticsDivs|safe }}
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">
							&nbsp;
						</td>
						<td>
							<input class="FormButton" type="submit" value="{% lang 'Save' %}">
							<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		</div>
	</form>

	<script type="text/javascript">

		function package_selected(package_id) {
			var ap = document.getElementById("analyticsproviders_old");

			for(i = 0; i < ap.options.length; i++) {
				if(ap.options[i].value == package_id && ap.options[i].selected)
					return true;
			}

			return false;
		}

		function ShowTab(T)
		{
			i = 0;
			while (document.getElementById("tab" + i) != null) {
				document.getElementById("div" + i).style.display = "none";
				document.getElementById("tab" + i).className = "";
				i++;
			}

			document.getElementById("div" + T).style.display = "";
			document.getElementById("tab" + T).className = "active";
			document.getElementById("currentTab").value = T;
		}

		function ConfirmCancel()
		{
			if(confirm("{% lang 'ConfirmCancelAnalyticsSettings' %}"))
				document.location.href = "index.php?ToDo=viewAnalyticsSettings";
		}

		function CheckAnalyticsSettingsForm() {
			{{ AnalyticsJavaScript|safe }}
		}

		// Load the main analytics settings tab by default
		ShowTab({{ CurrentTab|safe }});

	</script>
