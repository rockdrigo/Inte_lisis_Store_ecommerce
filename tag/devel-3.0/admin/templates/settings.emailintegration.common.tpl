{% import "macros/forms.tpl" as formBuilder %}
{% import "macros/util.tpl" as util %}

<div class="apiNotConfiguredContainer" {% if module.object.isConfigured %}style="display:none;"{% endif %}>
	{% block notconfigured %}
		<div class="MessageBox MessageBoxInfo">{% lang module.id ~ '_ConfigureEmailModuleFirst' with [
			'provider': module.name
		] %}</div>
	{% endblock %}
</div>

{% block common %}

<script language="javascript" type="text/javascript">//<![CDATA[
$(function(){
	Interspire_EmailIntegration_ProviderModel.modules["{{ module.id|js }}"].setConfigured({% if module.object.isConfigured %}true{% else %}false{% endif %});
});
//]]></script>

<div class="apiConfiguredContainer" {% if not module.object.isConfigured %}style="display:none;"{% endif %}>
	{% block configured %}
		<div class="ruleBuildersContainer">
			{% block newsletterRuleBuilder %}
				<div class="newsletterRulesBuilderContainer">{% include 'settings.emailintegration.newsletterrules.tpl' %}</div>
			{% endblock %}

			{% block customerRuleBuilder %}
				<div class="customerRulesBuilderContainer">{% include 'settings.emailintegration.customerrules.tpl' %}</div>
			{% endblock %}

			<p class="note">{% lang 'EmailIntegrationRulesFooterNote' with [
				'provider': module.name
			] %}</p>
		</div>
	{% endblock %}
</div>

{% endblock %}
