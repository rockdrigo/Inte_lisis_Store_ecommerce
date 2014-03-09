{% import "macros/forms.tpl" as formBuilder %}
{% import "macros/util.tpl" as util %}

<div id="ModalTitle">Sync Order Fields</div>

<div id="ModalContent">
	<div class="emailIntegrationFieldSyncFormMessages">
		<div id="emailIntegrationFieldSyncFormDuplicateMessage"></div>
		<div id="emailIntegrationFieldSyncFormUnmatchedMessage"></div>
	</div>

	{% include 'emailintegration.fieldsyncform.modalcontent.tpl' %}
</div>

<div id="ModalButtonRow">
	{% if listFields|length %}
		<div style="float:left;">
			<input type="checkbox" id="fieldSyncFormGuessFields" /> <label for="fieldSyncFormGuessFields">{% lang 'FieldSyncFormGuessFieldsLabel' with [
				'provider': module.name
			] %}</label>
		</div>
	{% endif %}
	<div style="float:right;">
		<input type="button" class="Button emailIntegrationFieldSyncFormCancelButton" value="{% lang 'Cancel' %}" />
		<input type="button" {% if not listFields|length %}disabled="disabled"{% endif %} class="Submit emailIntegrationFieldSyncFormSubmitButton" value="{% lang 'Save' %}" />
	</div>
	<br style="clear:both;" />
</div>
