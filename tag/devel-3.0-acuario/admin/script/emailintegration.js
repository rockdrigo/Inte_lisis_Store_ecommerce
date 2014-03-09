;(function($){
/**
* This file contains code common to most email integration related functionality.
*/

// "blah".ucfirst() === "Blah"
if (typeof String.prototype.ucfirst == 'undefined') {
	String.prototype.ucfirst = function () {
		return this.charAt(0).toUpperCase() + this.substr(1);
	};
}

if (typeof Interspire_EmailIntegration == 'undefined') { Interspire_EmailIntegration = {}; }

Interspire_EmailIntegration.validateFieldSyncForm = function () {
	var form = $('.emailIntegrationFieldSyncForm');
	var valid = true;

	// search for duplicate fields
	if (form.find('.duplicate').length) {
		// duplicate rows exist - flash the already-showing message
		$('#emailIntegrationFieldSyncFormDuplicateMessage').hide().fadeIn();
		valid = false;
	}

	// search for unmatched fields
	form.find('.mapLocal, .mapProvider').removeClass('incomplete');
	var unmatched = false;
	form.find('.emailIntegrationFieldSyncFormContent tr').each(function(){
		var $$ = $(this);
		var local = $.trim($$.find('.mapLocal').val());
		var provider = $.trim($$.find('.mapProvider').val());

		if (local && !provider) {
			unmatched = $$.find('.mapProvider').addClass('incomplete');
		} else if (provider && !local) {
			unmatched = $$.find('.mapLocal').addClass('incomplete');
		}
	});

	if (unmatched === false) {
		$('#emailIntegrationFieldSyncFormUnmatchedMessage').html('');
	} else {
		valid = false;
		display_error('emailIntegrationFieldSyncFormUnmatchedMessage', lang.FieldSyncFormMapUnmatchedFields);
		unmatched.focus();
	}

	return valid;
};

Interspire_EmailIntegration.serializeFieldSyncForm = function () {
	var form = $('.emailIntegrationFieldSyncForm');
	var map = {};

	form.find('.emailIntegrationFieldSyncFormContent tr').each(function(){
		var $$ = $(this);
		var local = $$.find('.mapLocal');
		var provider = $$.find('.mapProvider');

		if (local.attr('disabled') || provider.attr('disabled')) {
			// don't store/send locked fields
			return;
		}

		local = $.trim(local.val());
		provider = $.trim(provider.val());
		if (!local || !provider) {
			// don't store empty fields
			return;
		}

		map[provider] = local;
	});

	return map;
};

/**
* Class for storing structured information about a subscriber routing rule
*/
Interspire_EmailIntegration_RuleModel = function (id, module, event, action, list, fieldMap, criteria) {
	var self = this;

	if (!module || !event || !action || !list) {
		var error = new Error(lang.EmailIntegrationIncompleteRuleError);
		error.isc = {
			'id': id,
			'module': module
		};
		throw error;
	}

	self.id = parseInt(id, 10);
	self.module = module;
	self.event = event;
	self.action = action;
	self.list = list;
	self.fieldMap = fieldMap;
	self.criteria = criteria;
};

Interspire_EmailIntegration_RuleModel.parseNewsletterRule = function (module, container) {
	var $$ = $(container);
	$$.removeClass('invalidRule');
	var action = $.trim($$.find('.newsletterrules_action').val());
	var list = $.trim($$.find('.newsletterrules_list').val());

	if (!action && !list) {
		// valid, but blank rule
		return false;
	}

	var mapFirstNameTo = $$.find('.newsletterrules_map_subfirstname').val();

	var id = $$.find('.newsletterrules_id').val();
	var event = 'onNewsletterSubscribed';
	var criteria = {};
	var fieldMap = {};

	fieldMap[mapFirstNameTo] = 'subfirstname';

	try {
		return new Interspire_EmailIntegration_RuleModel(id, module, event, action, list, fieldMap, criteria);
	} catch (error) {
		$$.addClass('invalidRule');
		error.isc.container = container;
		throw error;
	}
};

Interspire_EmailIntegration_RuleModel.parseCustomerRule = function (module, container) {
	var $$ = $(container);
	$$.removeClass('invalidRule');
	var id = $$.find('.customerrules_id').val();
	var event = 'onOrderCompleted';
	var criteria = {};
	var action = $$.find('.customerrules_action').val();
	var list = $$.find('.customerrules_list').val();
	var fieldMap = $$.find('.customerrules_map').val();
	if (fieldMap) {
		fieldMap = JSON.parse(fieldMap);
	} else {
		fieldMap = {};
	}

	criteria.orderType = $$.find('.customerrules_order').val();
	criteria.orderCriteria = $$.find('.customerrules_ordercriteria').val();

	if (!criteria.orderType) {
		// valid, but blank rule
		return;
	}

	try {
		return new Interspire_EmailIntegration_RuleModel(id, module, event, action, list, fieldMap, criteria);
	} catch (error) {
		$$.addClass('invalidRule');
		error.isc.container = container;
		throw error;
	}
};

/**
* Class for storing structured information about a subscriber list field
* @constructor
*/
Interspire_EmailIntegration_ListFieldModel = function (list, id, provider_field_id, name) {
	var self = this;

	self.list = list;
	self.id = id;
	self.provider_field_id = provider_field_id;
	self.name = name;
};

/**
* Class for storing structured information about a subscriber list
* @constructor
*/
Interspire_EmailIntegration_ListModel = function (provider, id, provider_list_id, name, fields) {
	var self = this;

	self.provider = provider;
	self.id = id;
	self.provider_list_id = provider_list_id;
	self.name = name;

	var _fields = null;
	if (typeof fields != 'undefined') {
		_fields = fields;
	}

	// not ideal because this doubles up on self.provider.ajax, can't really be helped though I think
	self.fields = new AjaxDataProvider({
		url: 'remote.php',
		type: 'POST',
		dataType: 'json',
		data: {
			remoteSection: 'settings_emailintegration',
			w: 'providerAction',
			provider: self.provider.provider,
			providerAction: 'getListFields',
			listId: self.provider_list_id
		},
		process: function (data) {
			if (!data) {
				display_error('Status', lang.EmailIntegrationGetListFieldsFailed);
				return [];
			}

			if (!data.success || !data.fields) {
				if (data.message) {
					display_error('Status', data.message);
				} else {
					display_error('Status', lang.EmailIntegrationGetListFieldsFailed);
				}
				return [];
			}

			var fields = [];
			$.each(data.fields, function(index, field){
				fields.push(new Interspire_EmailIntegration_ListFieldModel(self, field.id, field.provider_field_id, field.name));
			});

			return fields;
		}
	});

	/**
	* retrieves a field by id for this module - because this is based on ajax data, a success handler must be provided, and field data will be sent to it
	* if field data is not available locally immediately, an ajax request will be sent and the success handler will be called once field data is received
	*/
	self.getField = function (id, success, loading) {
		// pipe the request to the fields.get function since we need all the fields first, then create our own success wrapper function, which properly calls the success function handed to getField
		self.fields.get(function(fields){
			for (var i = fields.length; i--;) {
				if (fields[i].provider_field_id == id) {
					success(fields[i]);
					return;
				}
			}
			success(false);
		}, loading);
	};
};

/**
* Class for storing structured information about an email integration provider
* @constructor
*/
Interspire_EmailIntegration_ProviderModel = function (id, lists, configured) {
	var self = this;

	self.id = id;
	self.provider = self.id.match(/_(.+?)$/)[1];

	// add this provider to by-provider list
	Interspire_EmailIntegration_ProviderModel.providers[self.provider] = self;
	Interspire_EmailIntegration_ProviderModel.modules[self.id] = self;

	var _lists_success = [];
	var _lists = null;
	if (typeof lists != 'undefined') {
		_lists = lists;
	}

	// create an object with auth data key/value pairs -- this should be overwritten by module-specific objects
	self.getApiAuthData = function () {
		return {};
	};

	self.configuredAuthData =  {};
	self.configured = false;

	self.getConfigured = function () { return self.configured; };

	self.setConfigured = function (value) {
		self.configured = !!value;

		$('input[name="' + self.id + '[isconfigured]"]').val(self.configured ? 1 : 0);

		if (self.configured) {
			$('#' + self.id + ' .apiConfiguredContainer').show();
			$('#' + self.id + ' .apiNotConfiguredContainer').hide();
		} else {
			$('#' + self.id + ' .apiConfiguredContainer').hide();
			$('#' + self.id + ' .apiNotConfiguredContainer').show();
		}

		self.configuredAuthData = self.getApiAuthData();
	}

	if (typeof configured != 'undefined') {
		self.setConfigured(configured);
	}

	self.isSelected = function () {
		return $('select[name="modules[]"] option[value=' + self.id + ']:selected').length == 1;
	};

	/**
	* retrieves lists available for this module - because this is based on ajax data, a success handler must be provided, and list data will be sent to it
	* if list data is not available locally immediately, an ajax request will be sent and the success handler will be called once list data is received
	*/
	// not ideal because this doubles up on self.provider.ajax, can't really be helped though I think
	self.lists = new AjaxDataProvider({
		url: 'remote.php',
		type: 'POST',
		dataType: 'json',
		data: {
			remoteSection: 'settings_emailintegration',
			w: 'providerAction',
			provider: self.provider,
			providerAction: 'getLists'
		},
		process: function (data) {
			if (!data) {
				display_error('Status', lang.EmailIntegrationGetListsFailed);
				return [];
			}

			if (!data.success || !data.lists) {
				if (data.message) {
					display_error('Status', data.message);
				} else {
					display_error('Status', lang.EmailIntegrationGetListsFailed);
				}
				return [];
			}

			var lists = [];
			$.each(data.lists, function(index, list){
				lists.push(new Interspire_EmailIntegration_ListModel(self, list.id, list.provider_list_id, list.name));
			});

			return lists;
		}
	});

	/**
	* retrieves a list by id for this module - because this is based on ajax data, a success handler must be provided, and list data will be sent to it
	* if list data is not available locally immediately, an ajax request will be sent and the success handler will be called once list data is received
	*/
	self.getList = function (id, success, loading) {
		// pipe the request to the getLists function since we need all the lists first, then create our own success wrapper function, which properly calls the getList success function
		self.lists.get(function(lists){
			for (var i = lists.length; i--;) {
				if (lists[i].provider_list_id == id) {
					success(lists[i]);
					return;
				}
			}
			success(false);
		}, loading);
	};

	/**
	* a shortcut method to jquery ajax stuff which points straight to the remote json handler for this module
	*/
	self.ajax = function (providerAction, options) {
		options = $.extend({
			data: {},
			success: false,
			error: function (xhr, status, error) {
				display_error('Status', 'An unexpected error occurred. More information may be available from the store log (error: ' + status + ')');
			}
		}, options);

		var original = {
			success: options.success
		};

		options.success = function (data) {
			if (data.success) {
				display_success('Status', data.message);
			} else {
				display_error('Status', data.message);
			}

			if (original.success) {
				original.success(data);
			}
		};

		options.url = 'remote.php';
		options.type = 'POST';
		options.dataType = 'json';

		options.data.remoteSection = 'settings_emailintegration';
		options.data.w = 'providerAction';
		options.data.provider = self.provider;
		options.data.providerAction = providerAction;

		// fetch auth data for this provider from the settings form and encode it as php-array-like post parameters
		var auth = self.getApiAuthData();
		var authKey;
		for (authKey in auth) {
			options.data['auth[' + authKey + ']'] = auth[authKey];
		}

		$.ajax(options);
	};

	/**
	* a shortcut method for calling a basic server-side action for this module - will display a resulting message on the page
	*/
	self.ajaxAction = function (action, options) {
		options = $.extend({data:{}}, options);

		options.url = 'remote.php';
		options.type = 'POST';
		options.dataType = 'json';

		options.data.remoteSection = 'settings_emailintegration';
		options.data.w = 'providerAction';
		options.data.provider = self.provider;
		options.data.providerAction = action;

		// fetch auth data for this provider from the settings form and encode it as php-array-like post parameters
		var auth = self.getApiAuthData();
		var authKey;
		for (authKey in auth) {
			options.data['auth[' + authKey + ']'] = auth[authKey];
		}

		var originalSuccess = options.success;
		var originalError = options.error;

		options.success = function (data) {
			if (data.success) {
				display_success('Status', data.message);
				if (originalSuccess) {
					originalSuccess(data);
				}
			} else {
				display_error('Status', data.message);
				if (originalError) {
					originalError({}, data.message);
				}
			}
		};

		options.error = function (xhr, status, error) {
			display_error('Status', 'An unexpected error occurred. More information may be available from the store log (error: ' + status + ')');
			if (originalError) {
				originalError(xhr, status, error);
			}
		};

		$.ajax(options);
	};

	self.refreshLists = function (options) {
		var container = $('#' + self.id);

		options = $.extend({ success: false }, options);

		var original = {
			success: options.success
		};

		options.success = function (data) {
			self.setConfigured(data.success);

			if (data.newsletterRules || data.customerRules) {
				self.lists.flush();
				if (data.newsletterRules) {
					container.find('.newsletterRulesBuilderContainer').html(data.newsletterRules);
				}
				if (data.customerRules) {
					container.find('.customerRulesBuilderContainer').html(data.customerRules);
				}
			}

			if (original.success) {
				original.success(data);
			}
		};

		self.ajax('refreshLists', options);
	};

	self.verifyApi = function (options) {
		var container = $('#' + self.id);

		options = $.extend({ success: false }, options);

		var original = {
			success: options.success
		};

		options.success = function (data) {
			self.setConfigured(data.success);

			if (data.newsletterRules || data.customerRules) {
				self.lists.flush();
				if (data.newsletterRules) {
					container.find('.newsletterRulesBuilderContainer').html(data.newsletterRules);
				}
				if (data.customerRules) {
					container.find('.customerRulesBuilderContainer').html(data.customerRules);
				}
			}

			if (original.success) {
				original.success(data);
			}
		};

		self.ajax('verifyApi', options);
	};

	/**
	* Generate instances of Interspire_EmailIntegration_RuleModel for this provider based on the rules setup in the UI
	*/
	self.generateRuleModels = function () {
		var rules = [];
		var container = $('#' + self.id);

		var throwError;

		container.find('.newsletterSubscriptionRuleBuilder .rule').each(function(){
			try {
				var rule = Interspire_EmailIntegration_RuleModel.parseNewsletterRule(self.id, this);
				if (rule) {
					rules.push(rule);
				}
			} catch (error) {
				throwError = error;
			}
		});

		container.find('.newCustomerSubscriptionRuleBuilder .rule').each(function(){
			try {
				var rule = Interspire_EmailIntegration_RuleModel.parseCustomerRule(self.id, this);
				if (rule) {
					rules.push(rule);
				}
			} catch (error) {
				throwError = error;
			}
		});

		if (throwError) {
			throw throwError;
		}

		return rules;
	};

	self.validateSettingsForm = function () {
		// to be overwritted by modules as needed
		return true;
	};
};

// storage for global list of providers as an object with providers assigned to keys by their id
Interspire_EmailIntegration_ProviderModel.providers = {};
Interspire_EmailIntegration_ProviderModel.modules = {};

$('.emailIntegrationFieldSyncForm .mapLocal').live('change', function(event){
	event.preventDefault();
	var $$ = $(this);
	var row = $$.closest('tr');
	row.find('.mapLocal, .mapProvider').removeClass('incomplete');
	$('#emailIntegrationFieldSyncFormUnmatchedMessage').html('');

	if (!$('#fieldSyncFormGuessFields').attr('checked')) {
		// don't bother if the option is off
		return;
	}

	var provider = row.find('.mapProvider');

	if (!$$.val()) {
		// if the new value is blank, blank out the provider field
		provider.attr('selectedIndex', 0).change();
		return;
	}

	var local = this.options[this.selectedIndex].text;

	// see if we can match a remote field based on label
	var provider = provider[0];
	for (var i = provider.options.length; i--;) {
		if (provider.options[i].text == local) {
			provider.selectedIndex = i;
			$(provider).change();
			return;
		}
	}
});

$('.emailIntegrationFieldSyncForm .mapProvider').live('change', function(event){
	// check that the same provider field is not mapped twice

	var self = this;
	var $$ = $(self);
	var row = $$.closest('tr');
	row.find('.mapLocal, .mapProvider').removeClass('incomplete');
	$('#emailIntegrationFieldSyncFormUnmatchedMessage').html('');

	var duplicate = false;
	var duplicates = {};

	// scan for duplicates
	$('.emailIntegrationFieldSyncFormContent .mapProvider').each(function(){
		var $$ = $(this);
		var val = $$.val();
		if (!val) {
			return;
		}

		if (typeof duplicates[val] == 'undefined') {
			duplicates[val] = 0;
		} else {
			duplicates[val]++;
		}
	});

	// mark duplicates
	$('.emailIntegrationFieldSyncFormContent .mapProvider').each(function(){
		var $$ = $(this);
		var val = $$.val();

		if (duplicates[val]) {
			duplicate = true;
			$$.closest('tr').addClass('duplicate');
		} else {
			$$.closest('tr').removeClass('duplicate');
		}
	});

	var messageId = 'emailIntegrationFieldSyncFormDuplicateMessage';
	if (duplicate) {
		if (!$('#' + messageId).html()) {
			// there were duplicates and no error is showing
			display_error(messageId, lang.FieldSyncFormDuplicateFieldsClientError);
		}
	} else {
		// hide error
		$('#' + messageId).html('');
	}
});

$('.emailIntegrationFieldSyncForm .mapAdd').live('click', function(event){
	event.preventDefault();

	var template = $('.emailIntegrationFieldSyncFormTemplate');
	var clone = template.clone();
	clone.removeClass('emailIntegrationFieldSyncFormTemplate');

	var content = $('.emailIntegrationFieldSyncFormContent');
	content.append(clone);

	content.find('.mapLocal').attr('name', 'local[]');
	content.find('.mapProvider').attr('name', 'provider[]');

	var rowCount = content.find('>tr').length;

	if (rowCount > 1) {
		content.find('.mapDelete').show();
	} else {
		content.find('.mapDelete').hide();
	}

	$('.ExportMachine_ConfigureFields, .emailIntegrationFieldSyncForm').each(function(){
		// hack: selecting the uses of the sync form rather than a common class, should be fixed to be cleaner
		$(this).scrollTop(this.scrollHeight);
	});
});

$('.emailIntegrationFieldSyncForm .mapDelete').live('click', function(event){
	event.preventDefault();

	var $$ = $(this);
	var row = $$.closest('tr');

	if (row.find('select[disabled]').length) {
		// cannot delete a locked row
		return;
	}

	row.remove();

	var content = $('.emailIntegrationFieldSyncFormContent');
	var rowCount = content.find('>tr').length;

	if (rowCount > 1) {
		content.find('.mapDelete').show();
	} else {
		content.find('.mapDelete').hide();
	}

	// trigger duplicate check
	content.find('.mapProvider').eq(0).trigger('change');
	$('#emailIntegrationFieldSyncFormUnmatchedMessage').html('');
});

$('.EmailIntegration_LearnMoreLink').click(function(event){
	event.preventDefault();
	LaunchHelp('892');
});

})(jQuery);
