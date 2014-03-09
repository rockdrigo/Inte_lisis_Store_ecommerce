<div class="ModalTitle">
	<span class="ExportMachine_State_CommenceExport ExportMachine_State_ExportFailed" style="display:none;">
		{{ modalTitle }}
	</span>

	<span class="ExportMachine_State_SelectList ExportMachine_State_PrepareConfigureFields" style="display:none;">
		{{ modalTitle }} ({% lang 'EmailIntegration_Export_Dialog_StepTemplate' with [ 'a': 1, 'b': 3 ] %})
	</span>
	<span class="ExportMachine_State_ConfigureFields" style="display:none;">
		{{ modalTitle }} ({% lang 'EmailIntegration_Export_Dialog_StepTemplate' with [ 'a': 2, 'b': 3 ] %})
	</span>
	<span class="ExportMachine_State_ConfirmExport" style="display:none;">
		{{ modalTitle }} ({% lang 'EmailIntegration_Export_Dialog_StepTemplate' with [ 'a': 2, 'b': 3 ] %})
	</span>
	<span class="ExportMachine_State_Confirmation" style="display:none;">
		{% lang 'EmailIntegration_Export_Dialog_CommencedTitle' %}
	</span>
</div>

<div class="ModalContent">
	<div class="ExportMachine_StateContainer">
		{% if not lists|length %}
			<div class="ExportMachine_State_NoLists" style="display:none">
				<br />
				{% lang 'EmailIntegration_Export_Dialog_NoLists' with [
						'module_id': module.id,
						'module_name': module.name
				] %}
			</div>
		{% else %}
			<div class="ExportMachine_State_SelectList" style="display:none;">
				<br />
				{% lang 'EmailIntegration_Export_Dialog_WhichList' with [
					'provider': module.name,
					'type': typePlural|lower
				] %}<br />
				<br />
				<select class="Field100pct ExportMachine_SelectList" size="8" accesskey="l">
					{% for list in lists %}
						<option value="{{ list.provider_list_id }}">{{ list.name }}</option>
					{% endfor %}
				</select><br />
				<br />
				{% lang 'EmailIntegration_Export_Dialog_DontSeeList' %}<br />
			</div>

			<div class="ExportMachine_State_PrepareConfigureFields" style="display:none;">
				<br />
				{% lang 'EmailIntegration_Export_Dialog_LoadingFields' %}<br />
			</div>

			<div class="ExportMachine_State_ConfigureFields ExportMachine_ConfigureFields" style="display:none;">
				<br />
				{% lang 'FieldSyncFormIntro' with [
					'provider': module.name
				] %}<br />
				<br />
				<div class="ExportMachine_ConfigureFields_OkMessage MessageBox MessageBoxSuccess" style="display:none;">{% lang 'EmailIntegration_Export_Dialog_MappingValid' %}</div>
				<div class="ExportMachine_ConfigureFields_ErrorMessage MessageBox MessageBoxError" style="display:none;">{% lang 'EmailIntegration_Export_Dialog_MappingInvalid' %}</div>

				{# to be populated by ajax, see emailintegration.export.js - SelectList state, ClickNext transition #}
				<div class="ExportMachine_ConfigureFields_Container"></div>
			</div>

			<div class="ExportMachine_State_ConfigureFields ExportMachine_GuessFields" style="display:none;">
				<input type="checkbox" id="fieldSyncFormGuessFields" /> <label for="fieldSyncFormGuessFields">{% lang 'FieldSyncFormGuessFieldsLabel' with [
					'provider': module.name
				] %}</label>
			</div>

			<div class="ExportMachine_State_ConfirmExport" style="display:none;">
				<br />
				{% lang 'EmailIntegration_Export_Dialog_Confirmation' with [
					'type': typePlural|lower,
					'module': module.name,
					'list': '<span class="ExportMachine_ConfirmExport_ListName"></span>'
				] %}<br />
				<br />
				<table cellspacing="0">
					<tr>
						<td valign="top"><input type="checkbox" id="ExportMachine_ConfirmExport_DoubleOptin" checked="checked" /></td>
						<td><label for="ExportMachine_ConfirmExport_DoubleOptin">{% lang 'EmailIntegration_Export_Dialog_DoubleOptin' %}</label></td>
					</tr>
					{% if module.object.supportsSubscriberUpdates %}
						<tr>
							<td valign="top"><input type="checkbox" id="ExportMachine_ConfirmExport_UpdateExisting" checked="checked" /></td>
							<td><label for="ExportMachine_ConfirmExport_UpdateExisting">{% lang 'EmailIntegration_Export_Dialog_UpdateExisting' %}</label></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
								<div class="ExportMachine_Note">{% lang 'EmailIntegration_Export_Dialog_UpdateExisting_Note' %}</div>
							</td>
						</tr>
					{% else %}
						<tr>
							<td valign="top"><input type="checkbox" disabled="disabled" /></td>
							<td><label class="Disabled">{% lang 'EmailIntegration_Export_Dialog_UpdateExisting' %}</label></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
								<div class="ExportMachine_Note">{% lang 'EmailIntegration_Export_Dialog_UpdateExisting_Disabled' with [
									'module': module.name
								] %}</div>
							</td>
						</tr>
					{% endif %}
				</table>
				<br />
				{% lang 'EmailIntegration_Export_Dialog_ToBegin' %}<br />
			</div>

			<div class="ExportMachine_State_CommenceExport" style="display:none;">
				<br />
				{% lang 'EmailIntegration_Export_Dialog_Commencing' %}<br />
			</div>

			<div class="ExportMachine_State_ExportFailed" style="display:none;">
				<br />
				<div class="MessageBox MessageBoxError">
					{% lang 'EmailIntegration_Export_Dialog_CommencError_1' %}
				</div>
				<br />
				{% lang 'EmailIntegration_Export_Dialog_CommencError_2' %}<br />
			</div>

			<div class="ExportMachine_State_Confirmation" style="display:none;">
				<br />
				{% lang 'EmailIntegration_Export_Dialog_Commenced' with [
					'useremail': useremail
				] %}
			</div>
		{% endif %}
	</div>
</div>

<div class="ModalButtonRow ExportMachine_ButtonRow">
	<div style="float:left;">
		<button class="ExportMachine_CancelButton" disabled="disabled" accesskey="c">{% lang 'Cancel_AccessKeyC' %}</button>
	</div>

	<div style="float:right">
		<button class="ExportMachine_BackButton" disabled="disabled" accesskey="b">&lt; {% lang 'Back_AccessKeyB' %}</button><button class="ExportMachine_NextButton" disabled="disabled" accesskey="n">{% lang 'Next_AccessKeyN' %} &gt;</button><button class="ExportMachine_FinishButton" style="display:none;" accesskey="f">{% lang 'Finish_AccessKeyF' %}</button><button class="ExportMachine_CloseButton" style="display:none;" accesskey="o">{% lang 'Close_AccessKeyO' %}</button>
	</div>

	<br clear="both" />
</div>
