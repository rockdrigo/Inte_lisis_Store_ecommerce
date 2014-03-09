<div class="ModalTitle">
	{{ modalTitle }}
</div>

<div class="ModalContent">
	<div class="ExportMachine_StateContainer">
		<div class="ExportMachine_State_ConfirmExport" style="display:none;">
			<br />
			You're about to export <i>x</i> existing customers using the {{ rule.getPluralName }} configured in your store.<br />
			<br />
			<table>
				<tr>
					<td><input type="checkbox" id="ExportMachine_ConfirmExport_DoubleOptin" checked="checked" /></td>
					<td><label for="ExportMachine_ConfirmExport_DoubleOptin">Send an opt-in confirmation email for each new email address</label></td>
				</tr>
				<tr>
					<td><input type="checkbox" id="ExportMachine_ConfirmExport_SendWelcome" checked="checked" /></td>
					<td><label for="ExportMachine_ConfirmExport_SendWelcome">Send a welcome email for each new subscription</label></td>
				</tr>
				<tr>
					<td valign="top"><input type="checkbox" id="ExportMachine_ConfirmExport_UpdateExisting" checked="checked" /></td>
					<td>
						<label for="ExportMachine_ConfirmExport_UpdateExisting">Update existing subscriptions if an email address is already subscribed</label><br />
						<div class="ExportMachine_Note">Note: if this option is not selected, existing email addresses may be reported as errors by your email integration providers during the export. However, these can be safely ignored.</div>
					</td>
				</tr>
			</table>
			<br />
			To begin the export now, click the Finish button below.<br />
		</div>

		<div class="ExportMachine_State_CommenceExport" style="display:none;">
			<br />
			Export is starting up, please wait...
		</div>

		<div class="ExportMachine_State_ExportFailed" style="display:none;">
			<br />
			An error prevented the export from commencing. The store log may contain more specific details.<br />
			<br />
			You may either go back and try again or cancel. If the issue persists, please lodge a support ticket.<br />
		</div>

		<div class="ExportMachine_State_Confirmation" style="display:none;">
			<br />
			Confirmation that the export is underway to go here.<br />
			<br />
			Inform user they will receive an email at <i>email.address@example.com</i> when the export is finished.<br />
		</div>
	</div>
</div>

<div class="ModalButtonRow ExportMachine_ButtonRow">
	<button class="ExportMachine_BackButton" disabled="disabled" accesskey="b">&lt; {% lang 'Back_AccessKeyB' %}</button><button class="ExportMachine_FinishButton" disabled="disabled" accesskey="f">{% lang 'Finish_AccessKeyF' %}</button><button class="ExportMachine_CloseButton" style="display:none;" accesskey="o">{% lang 'Close_AccessKeyO' %}</button>
	&nbsp;
	<button class="ExportMachine_CancelButton" disabled="disabled" accesskey="c">{% lang 'Cancel_AccessKeyC' %}</button>
</div>
