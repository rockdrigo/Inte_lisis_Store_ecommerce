{{ formBuilder.startForm() }}

	{{ formBuilder.heading(lang.EmailIntegrationSettings) }}

	{{ formBuilder.startRow(['label': lang.EmailIntegrationProviders ~ ':']) }}

		{{ formBuilder.select('modules[]', selectableModules, selectedModules, [
			'multiple': 'multiple',
			'size': 5,
			'class': 'Field300 ISSelectReplacement'
		]) }}

	{{ formBuilder.endRow(lang.EmailIntegrationNoProvidersNote) }}

{{ formBuilder.endForm() }}

{{ formBuilder.startForm() }}

	{{ formBuilder.startRowGroup }}

		{{ formBuilder.heading(lang.NewsletterSettings) }}

		{{ formBuilder.startRow([
			'label': lang.ShowMailingListDuringCheckout ~ '?',
			'for': 'ShowMailingListInvite',
		]) }}

			<input type="hidden" name="ShowMailingListInvite" value="0" />
			{{ formBuilder.checkbox([
				'name': 'ShowMailingListInvite',
				'id': 'ShowMailingListInvite',
				'label': lang.YesShowMailingListDuringCheckout,
				'value': 1,
				'checked': config.ShowMailingListInvite,
				'class': 'CheckboxTogglesOtherElements',
			]) }}

			{{ util.tooltip('ShowMailingListDuringCheckout', 'ShowMailingListDuringCheckout_Help') }}

		{{ formBuilder.endRow() }}

	{{ formBuilder.endRowGroup }}

	{{ formBuilder.startRowGroup([ 'class': 'ShowIf_ShowMailingListInvite_Checked', 'hidden': not config.ShowMailingListInvite ]) }}

		{{ formBuilder.startRow([
			'label': lang.AutomaticallyTickDuringCheckout ~ '?',
			'for': 'MailAutomaticallyTickNewsletterBox',
		]) }}

			{{ formBuilder.nodeJoin }}

			<input type="hidden" name="MailAutomaticallyTickNewsletterBox" value="0" />
			{{ formBuilder.checkbox([
				'id': 'MailAutomaticallyTickNewsletterBox',
				'name': 'MailAutomaticallyTickNewsletterBox',
				'value': 1,
				'checked': config.MailAutomaticallyTickNewsletterBox,
				'label': lang.YesTickNewsletterBox,
			]) }}

			{{ util.tooltip('AutomaticallyTickDuringCheckout', 'AutomaticallyTickDuringCheckout_Help') }}

		{{ formBuilder.endRow() }}

		{% if enabledSelectableModules %}

			{{ formBuilder.startRow([
				'label': lang.EmailIntegrationNewsletterDoubleOptin ~ ':',
				'for': 'newsletterDoubleOptIn',
				'class': 'formRowIndent1',
			]) }}

				{{ formBuilder.radioList('newsletterDoubleOptIn', [
					1: lang.EmailIntegrationNewsletterDoubleOptin_yes,
					0: lang.EmailIntegrationNewsletterDoubleOptin_no,
				], config.EmailIntegrationNewsletterDoubleOptin, [
					'id': 'newsletterDoubleOptIn',
				]) }}

				{{ util.tooltip('EmailIntegrationNewsletterDoubleOptin', 'EmailIntegrationDoubleOptInHelp') }}

			{{ formBuilder.endRow() }}

			{{ formBuilder.startRow([
				'label': lang.EmailIntegrationNewsletterSendWelcome ~ ':',
				'for': 'newsletterSendWelcome',
				'class': 'formRowIndent1',
			]) }}

				{{ formBuilder.radioList('newsletterSendWelcome', [
					1: lang.EmailIntegrationNewsletterSendWelcome_yes,
					0: lang.EmailIntegrationNewsletterSendWelcome_no,
				], config.EmailIntegrationNewsletterSendWelcome, [
					'id': 'newsletterSendWelcome',
				]) }}

				{{ util.tooltip('EmailIntegrationNewsletterSendWelcome', 'EmailIntegrationSendWelcomeHelp') }}

			{{ formBuilder.endRow() }}

		{% endif %}

	{{ formBuilder.endRowGroup }}

{{ formBuilder.endForm() }}

{% if enabledSelectableModules %}

	{{ formBuilder.startForm() }}

		{{ formBuilder.heading(lang.NewCustomerSubscriptionSettings) }}

		{{ formBuilder.startRow([
			'label': lang.EmailIntegrationAutomaticallyTickOrderDuringCheckout ~ '?',
			'for': 'MailAutomaticallyTickOrderBox',
		]) }}

			<input type="hidden" name="MailAutomaticallyTickOrderBox" value="0" />
			{{ formBuilder.checkbox([
				'id': 'MailAutomaticallyTickOrderBox',
				'name': 'MailAutomaticallyTickOrderBox',
				'value': 1,
				'label': lang.EmailIntegrationAutomaticallyTickOrderDuringCheckoutYes,
				'checked': config.MailAutomaticallyTickOrderBox,
			]) }}

			{{ util.tooltip('EmailIntegrationAutomaticallyTickOrderDuringCheckout', 'EmailIntegrationAutomaticallyTickOrderDuringCheckoutHelp') }}

		{{ formBuilder.endRow() }}

		{{ formBuilder.startRow([
			'label': lang.EmailIntegrationOrderDoubleOptin ~ ':',
			'for': 'orderDoubleOptIn',
		]) }}

			{{ formBuilder.radioList('orderDoubleOptIn', [
				1: lang.EmailIntegrationOrderDoubleOptin_yes,
				0: lang.EmailIntegrationOrderDoubleOptin_no,
			], config.EmailIntegrationOrderDoubleOptin, [
				'id': 'orderDoubleOptIn',
			]) }}

			{{ util.tooltip('EmailIntegrationNewsletterDoubleOptin', 'EmailIntegrationDoubleOptInHelp') }}

		{{ formBuilder.endRow() }}

		{{ formBuilder.startRow([
			'label': lang.EmailIntegrationOrderSendWelcome ~ ':',
			'for': 'orderSendWelcome',
		]) }}

			{{ formBuilder.radioList('orderSendWelcome', [
				1: lang.EmailIntegrationOrderSendWelcome_yes,
				0: lang.EmailIntegrationOrderSendWelcome_no,
			], config.EmailIntegrationOrderSendWelcome, [
				'id': 'orderSendWelcome',
			]) }}

			{{ util.tooltip('EmailIntegrationNewsletterSendWelcome', 'EmailIntegrationSendWelcomeHelp') }}

		{{ formBuilder.endRow() }}

	{{ formBuilder.endForm() }}

{% endif %}
