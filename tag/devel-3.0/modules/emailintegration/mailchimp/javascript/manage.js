;(function($){

Interspire_EmailIntegration_Provider_MailChimp = function(){
	var self = this;
	Interspire_EmailIntegration_ProviderModel.call(this, 'emailintegration_mailchimp');

	self.getApiAuthData = function () {
		return {
			key: $('input[name="' + self.id + '[apikey]"]').val()
		};
	};

	self.validateSettingsForm = function () {
		var key = $('input[name="' + self.id + '[apikey]"]');
		if (!key.val()) {
			alert(lang.MailChimpApiRequired);
			return false;
		}

		var auth = self.getApiAuthData();
		if (auth.key !== self.configuredAuthData.key) {
			alert(lang.MailChimpApiVerifyRequired);
			return false;
		}

		return true;
	};
};

var mailchimp = new Interspire_EmailIntegration_Provider_MailChimp();

$(function(){
	$('.emailintegration_mailchimp_verifyApiKey').click(function(event){
		event.preventDefault();
		mailchimp.verifyApi();
	});

	$('.emailintegration_mailchimp_refreshLists').click(function(event){
		event.preventDefault();
		if (!confirm(lang.EmailIntegration_ConfirmRefreshLists.replace(/:provider/gi, lang.emailintegration_mailchimp_name).replace(/:nl/gi, "\n"))) {
			return;
		}

		mailchimp.refreshLists();
	});

	$('.EmailIntegration_MailChimp_ApiKeyHelp').click(function(event){
		event.preventDefault();
		LaunchHelp('893');
	});
});

})(jQuery);
