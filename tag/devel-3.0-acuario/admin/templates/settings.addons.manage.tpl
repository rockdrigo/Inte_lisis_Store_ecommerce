
	<form action="index.php?ToDo=saveUpdatedAddonSettings" name="frmAddonSettings" id="frmAddonSettings" method="post" onsubmit="return ValidateForm(CheckAddonSettingsForm)">
		<div class="BodyContainer">
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'AddonSettings' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'AddonSettingsIntro' %}</p>
				{{ Message|safe }}
				<p>
					<input type="submit" value="{% lang 'Save' %}" class="FormButton" />
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
				</p>
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<ul id="tabnav">
					<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'GeneralSettings' %}</a></li>
					<!-- li style="display:none"><a href="#" id="tab1" onclick="ShowTab(1)"></a></li -->
					{{ AddonTabs|safe }}
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
								<label for="storename">{% lang 'AddonPackages' %}:</label>
							</td>
							<td class="PanelBottom">
								<select size="{{ AddonSelectBoxSize|safe }}" multiple name="addonpackages[]" id="addonpackages" class="Field250 ISSelectReplacement">
									{{ AddonProviders|safe }}
								</select>
								<img onmouseout="HideHelp('d5');" onmouseover="ShowHelp('d5', '{% lang 'AddonPackages' %}', '{% lang 'AddonPackagesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d5"></div>
							</td>
						</tr>
					</table>
				</div>
				{{ AddonDivs|safe }}
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain" id="SaveCancelBottom">
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

		var hide_buttons_on_tabs = '{{ TabIdsToHideButtonsFrom|safe }}';

		function package_selected(package_id) {
			var ap = document.getElementById("addonpackages_old");

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

			// Is this a tab on which we have to hide the save/cancel buttons
			if(hide_buttons_on_tabs.indexOf(T + '|') > -1) {
				$('#SaveCancelBottom').hide();
			}
			else {
				$('#SaveCancelBottom').show();
			}
		}

		function ConfirmCancel()
		{
			if(confirm("{% lang 'ConfirmCancelAddonsSettings' %}"))
				document.location.href = "index.php?ToDo=viewAddonSettings";
		}

		function CheckAddonSettingsForm() {
			{{ AddonJavaScript|safe }}
		}

		$(document).ready(function() {
			// Load the main addons settings tab by default
			ShowTab({{ CurrentTab|safe }});
		});


	</script>
