{% extends "settings.emailintegration.common.tpl" %}

{% block common %}
	{% import "macros/forms.tpl" as formBuilder %}
	{% import "macros/util.tpl" as util %}

	{{ formBuilder.startForm() }}

		{{ formBuilder.heading(lang.MailChimpIntegrationSettings) }}

		{{ formBuilder.startRow([ 'label': lang.MailChimpApiKey ~ ':', 'required': true ]) }}
			{{ formBuilder.input('text', module.id ~ '[apikey]', module.object.GetValue('apikey'), [ 'class': 'Field250' ]) }}
			{{ util.tooltip('MailChimpApiKey', 'MailChimpApiKeyHelp') }}

			<input type="button" class="button {{ module.id }}_verifyApiKey" style="width:100px;" value="{{ lang.MailChimpVerifyApi }}" />

			&nbsp;
			<a href="#" class="EmailIntegration_MailChimp_ApiKeyHelp">{% lang 'WhereCanIFindMyApiKey' %}</a>

			<span class="apiConfiguredContainer" {% if not module.object.isConfigured %}style="display:none;"{% endif %}>
				&nbsp;
				<input type="button" class="button {{ module.id }}_refreshLists" style="width:100px;" value="{{ lang.EmailIntegrationRefreshLists }}" />
			</span>
		{{ formBuilder.endRow() }}

	{{ formBuilder.endForm() }}

	{% parent %}

	<script language="javascript" type="text/javascript">//<![CDATA[
	if (typeof lang == 'undefined') { lang = {}; }
	lang.MailChimpApiRequired = "{% jslang 'MailChimpApiRequired' %}";
	lang.MailChimpApiVerifyRequired = "{% jslang 'MailChimpApiVerifyRequired' %}";
	//]]></script>

{% endblock %}
