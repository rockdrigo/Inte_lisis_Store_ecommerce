
	<form action="index.php?ToDo=saveUpdatedAccountingSettings" name="frmAccountingSettings" id="frmAccountingSettings" method="post" onsubmit="return ValidateForm(AdminAccountingSettings.checkAccountingSettingsForm)">
	<input id="currentTab" name="currentTab" value="0" type="hidden">
		<div class="BodyContainer">
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'AccountingSettings' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'AccountingSettingsIntro' %}</p>
				{{ Message|safe }}
				<p>
					<input type="submit" value="{% lang 'Save' %}" class="FormButton" />
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="AdminAccountingSettings.confirmCancel()" />
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<ul id="tabnav">
					<li><a href="#" class="active" id="tab0" onclick="AdminAccountingSettings.showTab(0)">{% lang 'GeneralSettings' %}</a></li>
					<li style="display:none"><a href="#" id="tab1"></a></li>
					{{ AccountingTabs|safe }}
				</ul>
			</td>
		</tr>
		<tr>
			<td>
				<div id="div0" style="padding-top: 10px;">
					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'AccountingSettings' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="storename">{% lang 'AccountingMethods' %}:</label>
							</td>
							<td class="PanelBottom">
								<select size="11" multiple="multiple" name="accountingproviders[]" id="accountingproviders" class="Field300 ISSelectReplacement">
									{{ AccountingProviders|safe }}
								</select>
								<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'AccountingMethods' %}', '{% lang 'AccountingMethodsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d1"></div>
							</td>
						</tr>
					</table>
				</div>
				<div id="div1" style="padding-top: 10px;">


				</div>
				{{ AccountingDivs|safe }}
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain" id="BottomButtons">
					<tr>
						<td width="200" class="FieldLabel">
							&nbsp;
						</td>
						<td>
							<input class="FormButton" type="submit" value="Save">
							<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="AdminAccountingSettings.confirmCancel()" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		</div>
	</form>

	<script type="text/javascript">

		function get_selected() {
			if(g('accountingproviders_old')) {
				var cp = g('accountingproviders_old');
			} else {
				var cp = document.getElementById("accountingproviders");
			}

			var selected = [];

			for(i = 0; i < cp.options.length; i++) {
				if(cp.options[i].selected) {
					selected[selected.length] = cp.options[i].value;
				}
			}

			return selected;
		}

		function accounting_selected(accounting_id) {
			var selected = get_selected();
			for(i = 0; i < cp.selected; i++) {
				if(selected[i] == accounting_id)
					return true;
			}

			return false;
		}

	var AdminAccountingSettings = {
		'codeHooks': {
						'exec': [],
						'onload': [],
						'onsubmit': []
					},

		'addExecFunc':
			function(func)
			{
				AdminAccountingSettings.codeHooks.exec[AdminAccountingSettings.codeHooks.exec.length] = func;
			},

		'addOnLoadFunc':
			function(func)
			{
				AdminAccountingSettings.codeHooks.onload[AdminAccountingSettings.codeHooks.onload.length] = func;
			},

		'addOnSubmitFunc':
			function(func)
			{
				AdminAccountingSettings.codeHooks.onsubmit[AdminAccountingSettings.codeHooks.onsubmit.length] = func;
			},

		'showTab':
			function(tabId)
			{
				return AdminAccountingSettings.handleShowTab(tabId);
			},

		'showModuleTab':
			function(tabId, moduleId)
			{
				return AdminAccountingSettings.handleShowTab(tabId, moduleId);
			},

		'handleShowTab':
			function(tabId, moduleId)
			{
				if (typeof(moduleId) == "undefined" || moduleId == "") {
					moduleId = "";
				}

				i = 0;
				while (document.getElementById("tab" + moduleId + i) != null) {
					$("#div" + moduleId + i).hide();
					$("#tab" + moduleId + i).attr("class", "");
					i++;
				}

				$("#div" + moduleId + tabId).show();
				$("#tab" + moduleId + tabId).attr("class", "active");
				$("#currentTab" + moduleId).val(tabId);
			},

		'confirmCancel':
			function()
			{
				if(confirm('{% lang 'CancelAccountingMessage' %}')) {
					document.location.href='index.php?ToDo=viewAccountingSettings';
				}
				else {
					return false;
				}
			},

		'checkAccountingSettingsForm':
			function()
			{
				var selected = get_selected();

				if (selected.length > 0 && "{{ SSLIsConfigured|safe }}" == "0") {
					alert("{% lang 'QuickBooksRequireSSLError' %}");
					return false;
				}

				for (var i=0; i<AdminAccountingSettings.codeHooks.onsubmit.length; i++) {
					if (!AdminAccountingSettings.codeHooks.onsubmit[i]()) {
						return false;
					}
				}

				{{ AccountingOnSubmitJavaScript|safe }}
			}
	}

	// Do onload stuff here
	$(document).ready(
		function ()
		{
			// Load the main shipping settings tab by default
			AdminAccountingSettings.showTab({{ CurrentTab|safe }});

			for (var i=0; i<AdminAccountingSettings.codeHooks.onload.length; i++) {
				try {
					AdminAccountingSettings.codeHooks.onload[i]();
				} catch (e) {};
			}

			{{ AccountingOnLoadJavaScript|safe }}
		}
	);

	{{ AccountingExecJavaScript|safe }}

	</script>



