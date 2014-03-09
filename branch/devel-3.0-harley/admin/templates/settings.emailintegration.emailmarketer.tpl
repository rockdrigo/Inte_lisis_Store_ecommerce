{% extends "settings.emailintegration.common.tpl" %}

{% block common %}
	{% import "macros/forms.tpl" as forms %}
	{% import "macros/util.tpl" as util %}

	{{ forms.startForm() }}

		{{ forms.heading(lang.EmailMarketerIntegrationSettings) }}

		{{ forms.startRow([
			'label': lang.EmailMarketerXMLApiUrl ~ ':', 'required': true,
		]) }}

			{{ forms.input('text', module.id ~ '[url]', module.object.GetValue('url'), [
				'class': 'Field250',
			]) }}
			{{ util.tooltip('EmailMarketerXMLApiUrl', 'EmailMarketerXMLApiUrlHelp') }}

		{{ forms.endRow }}

		{{ forms.startRow([
			'label': lang.EmailMarketerXMLApiUsername ~ ':', 'required': true,
		]) }}

			{{ forms.input('text', module.id ~ '[username]', module.object.GetValue('username'), [
				'class': 'Field250',
			]) }}
			{{ util.tooltip('EmailMarketerXMLApiUsername', 'EmailMarketerXMLApiUsernameHelp') }}

		{{ forms.endRow }}

		{{ forms.startRow([
			'label': lang.EmailMarketerXMLApiUsertoken ~ ':', 'required': true,
		]) }}

			{{ forms.input('text', module.id ~ '[usertoken]', module.object.GetValue('usertoken'), [
				'class': 'Field250',
			]) }}
			{{ util.tooltip('EmailMarketerXMLApiUsertoken', 'EmailMarketerXMLApiUsertokenHelp') }}

		{{ forms.endRow }}

		{{ forms.startRow([
			'label': ' '
		]) }}
			<input type="button" class="button {{ module.id }}_verifyApiKey" style="width:120px;" value="{{ lang.EmailMarketerVerifyApi }}" />
			&nbsp;
			<a href="#" class="EmailIntegration_EmailMarketer_ApiKeyHelp">{% lang 'WhereCanIFindMyApiDetails' %}</a>

			<span class="apiConfiguredContainer" {% if not module.object.isConfigured %}style="display:none;"{% endif %}>
				&nbsp;
				<input type="button" class="button {{ module.id }}_refreshLists" style="width:100px;" value="{{ lang.EmailIntegrationRefreshLists }}" />
			</span>

			<span class="apiNotConfiguredContainer" {% if module.object.isConfigured %}style="display:none;"{% endif %}>
				&nbsp;
				<input type="button" class="button {{ module.id }}_refreshLists" style="width:100px;" value="{{ lang.EmailIntegrationRefreshLists }}" disabled="disabled" title="{{ lang.VerifyApiDetailsFirst }}" />
			</span>
		{{ forms.endRow }}

	{{ forms.endForm() }}

	{% parent %}

	<script language="javascript" type="text/javascript">//<![CDATA[
		{{ util.jslang([
			'emailintegration_emailmarketer_name',
			'EmailMarketerXMLApiUrlRequired',
			'EmailMarketerXMLApiUsernameRequired',
			'EmailMarketerXMLApiUsertokenRequired',
			'EmailMarketerApiVerifyRequired',
		]) }}
	//]]></script>

{% endblock %}
