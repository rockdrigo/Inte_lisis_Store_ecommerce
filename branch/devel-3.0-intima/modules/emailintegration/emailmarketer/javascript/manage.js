;(function($){

Interspire_EmailIntegration_Provider_EmailMarketer = function(){
	var self = this;
	Interspire_EmailIntegration_ProviderModel.call(this, 'emailintegration_emailmarketer');

	self.getApiAuthData = function () {
		return {
			url: $('input[name="' + self.id + '[url]"]').val(),
			username: $('input[name="' + self.id + '[username]"]').val(),
			usertoken: $('input[name="' + self.id + '[usertoken]"]').val()
		};
	};

	self.validateApiInputs = function () {
		var auth = self.getApiAuthData();

		if (!auth.url) {
			alert(lang.EmailMarketerXMLApiUrlRequired);
			return false;
		}

		if (!auth.username) {
			alert(lang.EmailMarketerXMLApiUsernameRequired);
			return false;
		}

		if (!auth.usertoken) {
			alert(lang.EmailMarketerXMLApiUsertokenRequired);
			return false;
		}

		return true;
	};

	self.validateSettingsForm = function () {
		if (!self.validateApiInputs()) {
			return false;
		}

		var auth = self.getApiAuthData();
		var configured = self.configuredAuthData;
		if (auth.url !== configured.url || auth.username !== configured.username || auth.usertoken !== configured.usertoken) {
			alert(lang.EmailMarketerApiVerifyRequired);
			return false;
		}

		return true;
	};
};

var emailmarketer = new Interspire_EmailIntegration_Provider_EmailMarketer();

$(function(){
	$('.emailintegration_emailmarketer_verifyApiKey').click(function(event){
		event.preventDefault();
		if (!emailmarketer.validateApiInputs()) {
			return;
		}
		emailmarketer.verifyApi();
	});

	$('.emailintegration_emailmarketer_refreshLists').click(function(event){
		event.preventDefault();
		if (!confirm(lang.EmailIntegration_ConfirmRefreshLists.replace(/:provider/gi, lang.emailintegration_emailmarketer_name).replace(/:nl/gi, "\n"))) {
			return;
		}

		emailmarketer.refreshLists();
	});

	$('.EmailIntegration_EmailMarketer_ApiKeyHelp').click(function(event){
		event.preventDefault();
		LaunchHelp('905');
	});
});

})(jQuery);
