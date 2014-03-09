{% import "macros/forms.tpl" as formBuilder %}
{% import "macros/util.tpl" as util %}

<script language="javascript" type="text/javascript">//<![CDATA[
if (typeof lang == 'undefined') {
	lang = {};
}
lang.EmailIntegrationGetListsFailed = "{% jslang 'EmailIntegrationGetListsFailed' %}";
lang.EmailIntegrationGetListFieldsFailed = "{% jslang 'EmailIntegrationGetListFieldsFailed' %}";
lang.NoneString = "{% jslang 'NoneString' %}";
lang.EmailIntegrationIncompleteRuleError = "{% jslang 'EmailIntegrationIncompleteRuleError' %}";
lang.EmailIntegrationChooseAListToSync = "{% jslang 'EmailIntegrationChooseAListToSync' %}";
lang.EmailIntegrationFieldSyncNotRequired = "{% jslang 'EmailIntegrationFieldSyncNotRequired' %}";
lang.EmailIntegration_ConfirmRefreshLists = "{% jslang 'EmailIntegration_ConfirmRefreshLists' %}";
//]]></script>

{% for module in modules %}
	{% if module.object.isConfigured and module.object.getSettingsJavaScript and module.object.getLists|length %}
		<script language="javascript" type="text/javascript">//<![CDATA[
			$(function(){
				if (Interspire_EmailIntegration_ProviderModel.modules["{{ module.id|js }}"]) {
					// load some commong language for each module
					lang.{{ module.id }}_name = "{% jslang module.id ~ '_name' %}";

					// preload this module's list information
					var provider = Interspire_EmailIntegration_ProviderModel.modules["{{ module.id|js }}"];
					provider.lists.result = [];
					{% for list in module.object.getLists %}
						provider.lists.result.push(new Interspire_EmailIntegration_ListModel(provider, "{{ list.provider_list_id|js }}", "{{ list.provider_list_id|js }}", "{{ list.name|js }}"));
					{% endfor %}
				}
			});
		//]]></script>
	{% endif %}
{% endfor %}

<script language="javascript" type="text/javascript">//<![CDATA[
(function($){
	$(function(){
		$('#emailIntegrationSettingsForm').submit(function(event){
			$.each(Interspire_EmailIntegration_ProviderModel.modules, function(moduleId, module){
				if (!module.isSelected()) {
					return;
				}

				if (!module.validateSettingsForm()) {
					event.preventDefault();
					$('#tabs').tabs('select', module.id);
					return false;
				}
			});
		});
	});
})(jQuery);
//]]></script>

<form action="index.php?ToDo=saveUpdatedEmailIntegrationSettings" id="emailIntegrationSettingsForm" method="post">
	<input id="currentTab" name="currentTab" value="{{ tab }}" type="hidden" />
	<div id="content">
		<h1>{% lang 'EmailMarketing' %}</h1>

		<p class="intro">{% lang 'EmailIntegrationSettingsIntro' %}</p>

		<div id="Status">{{ message|safe }}</div>

		{{ formBuilder.startButtonRow }}
			{{ formBuilder.saveButton }}
			{{ formBuilder.cancelButton }}
		{{ formBuilder.endButtonRow }}

		{# setup tabs for email integration and call on each module separately to produce its own settings display #}

		<div id="tabs" class="tabs">
			{{ util.tabs(tabs) }}

			<div id="modules" class="{% if tab and tab != 'modules'%}ui-tabs-hide{% endif %}">
				{% include 'settings.emailintegration.general.tpl' %}
			</div>

			{% for module in modules %}
				{% if module.enabled %}
					<input type="hidden" name="{{ module.id }}[isconfigured]" value="{{ module.object.isConfigured|default(0) }}" />
					<input type="hidden" name="{{ module.id }}[rules]" value="" />
					<div id="{{ module.id }}" class="{% if tab != module.id %}ui-tabs-hide{% endif %}">
						{% if module.object.getSettingsTemplate %}
							{% include module.object.getSettingsTemplate %}
						{% else %}
							{% include "settings.emailintegration.common.tpl" %}
						{% endif %}
					</div>
				{% endif %}
			{% endfor %}
		</div>
	</div>
</form>

<script language="javascript" type="text/javascript">//<![CDATA[
(function($){

	$(function(){
		$('#tabs').tabs({
			select: function(event, ui){
				$('#currentTab').val(ui.panel.id);
			}
		});

		{% if tab %}$('#tabs').tabs('select', '{{ tab|js }}');{% endif %}

		$('.cancelButton').click(function(event){
			event.preventDefault();

			if (confirm("{% lang 'ConfirmCancel' %}")) { {# ConfirmCancel has encoded newlines in it #}
				document.location.href = "index.php?ToDo=viewEmailIntegrationSettings";
			}
		});
	});

})(jQuery);
//]]></script>
