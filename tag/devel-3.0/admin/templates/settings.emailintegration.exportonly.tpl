{% import "macros/forms.tpl" as formBuilder %}
{% import "macros/util.tpl" as util %}

<script language="javascript" type="text/javascript">//<![CDATA[
if (typeof lang == 'undefined') {
	lang = {};
}
lang.EmailIntegration_ExportOnly_Delete_Confirm = "{% jslang 'EmailIntegration_ExportOnly_Delete_Confirm' %}";

//]]></script>


<div class="MessageBox MessageBoxInfo">
	{% lang 'EmailIntegration_ExportOnly_Intro' %}
</div>

{{ formBuilder.startForm() }}

	{{ formBuilder.heading(lang.EmailIntegration_ExportOnly_Header) }}

	{{ formBuilder.startRow([ 'label': lang.EmailIntegration_ExportOnly_Current_Label ~ ':', 'last': true ]) }}

		<input type="text" id="emailintegration_exportonly_subscribercount" class="chromeless Field50" value="{{ module.object.getSubscriptionCount }}" readonly="readonly" style="text-align:right;" />

		<span id="emailintegration_exportonly_subscriberactions" style="{% if not module.object.getSubscriptionCount %}display:none;{% endif %}">
			(<a href="#" class="exportSubscriptionsButton">{% lang 'EmailIntegration_ExportOnly_Export_Button' %}</a>
			{% lang 'Or' %}
			<a href="#" class="deleteSubscriptionsButton">{% lang 'EmailIntegration_ExportOnly_Delete_Button' %}</a>)
		</span>

	{{ formBuilder.endRow() }}

{{ formBuilder.endForm() }}
