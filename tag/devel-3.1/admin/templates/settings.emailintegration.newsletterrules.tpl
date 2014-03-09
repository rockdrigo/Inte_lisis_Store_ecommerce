{% import "macros/forms.tpl" as formBuilder %}
{% import "macros/util.tpl" as util %}

{{ formBuilder.startForm(['type':'vertical']) }}

{{ formBuilder.startHeading }}
	{% lang 'Interspire_EmailIntegration_Rule_NewsletterSubscribed_Name_Plural' %}
	{{ util.tooltip('Interspire_EmailIntegration_Rule_NewsletterSubscribed_Name_Plural', 'NewsletterSubscriptionRulesHelp') }}
{{ formBuilder.endHeading }}

{{ formBuilder.startRow() }}

	<table class="emailIntegrationRuleBuilder newsletterSubscriptionRuleBuilder">
		<thead>
			<tr>
				<th>{{ lang.WhenSomeoneSubscribes }}</th>
				<th>{{ lang.ChooseAList }}</th>
				<th>{{ lang.SaveFirstNameTo }}</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
		<tfoot>
			{# rule template #}
			<tr class="rule">
				<td class="action">
					<input type="hidden" class="newsletterrules_id" />
					<select class="Field150 newsletterrules_action">
						<option value=""></option>
						<option value="listAdd">{{ lang.AddToList }}</option>
						<option value="listRemove">{{ lang.RemoveFromList }}</option>
					</select>
				</td>
				<td class="list">
					<select class="Field170 newsletterrules_list" disabled="disabled"></select>
				</td>
				<td class="map">
					<select class="Field150 newsletterrules_map_subfirstname" disabled="disabled"></select>
				</td>
				<td class="ruleBuilderActions">
					<a href="#" class="ruleAdd" title="{{ lang.Add }}"><img src="images/addicon.png" width="16" height="16" alt="{{ lang.Add }}" /></a>
					<a href="#" class="ruleDelete" title="{{ lang.Delete }}"><img src="images/delicon.png" width="16" height="16" alt="{{ lang.Delete }}" /></a>
					<a href="#" class="ruleCopy" title="{{ lang.Copy }}"><img class="ruleCopy" src="images/page.gif" width="16" height="16" alt="{{ lang.Copy }}" /></a>
				</td>
			</tr>
		</tfoot>
	</table>

{{ formBuilder.endRow() }}

{{ formBuilder.endForm() }}

<script language="javascript" type="text/javascript">//<![CDATA[
$(function($){

	{# did not have time to make this a generic rule builder, instead had to c&p between customer and newsletter :( #}

	var ACTION_ADD = 1;
	var ACTION_REMOVE = 2;

	var moduleId = "{{ module.id|js }}";
	var module = Interspire_EmailIntegration_ProviderModel.modules[moduleId];
	var moduleContainer = $('#' + moduleId);
	var builder = moduleContainer.find('.newsletterSubscriptionRuleBuilder');
	var ruleContainer = builder.find('tbody');

	var getFieldSelector = function (field) {
		return '.newsletterrules_' + field;
	};

	var bindEvents = function (newRule) {
		// this exists because for some reason .live and .delegate for change events are NOT working for these fields in IE

		newRule.find(getFieldSelector('action')).change(function(event){
			var $$ = $(this);
			var val = $$.val();
			var rule = $$.closest('.rule');

			var next = rule.find(getFieldSelector('list'));
			var oldValueOfNextField = next.val();
			next.empty();
			next.attr('disabled', 'disabled');

			if (!val) {
				next.attr('selectedIndex', 0).trigger('change');
				return;
			}

			module.lists.get(function(lists, delayed){
				next.empty();

				var option = document.createElement('OPTION');
				option.value = '';
				option.text = '';
				next[0].options[0] = option;
				next.attr('selectedIndex', 0).trigger('change');

				var list;
				for (var i = 0; i < lists.length; i++) {
					list = lists[i];
					option = document.createElement('OPTION');
					option.value = list.provider_list_id;
					option.text = list.name;
					next[0].options[next[0].options.length] = option;
				}

				next.removeAttr('disabled');

				if (!delayed && oldValueOfNextField) {
					// see if we can re-select the next field's value
					next.val(oldValueOfNextField);
					next.trigger('change');
				}

			}, {
				loading: function(){
					var option = document.createElement('OPTION');
					option.value = '';
					option.text = 'Loading, please wait...';
					next[0].options[next[0].options.length] = option;
					next.attr('selectedIndex', 0).trigger('change');
				}
			});
		});

		newRule.find(getFieldSelector('list')).change(function(event){
			var $$ = $(this);
			var val = $$.val();
			var rule = $$.closest('.rule');

			var next = rule.find(getFieldSelector('map_subfirstname'));
			var oldValueOfNextField = next.val();
			next.empty();
			next.attr('disabled', 'disabled');

			if (!val) {
				return;
			}

			module.getList(val, function(list){
				if (!list) {
					// list was not found
					return;
				}

				if (rule.find(getFieldSelector('action')).val() == 'listRemove') {
					var option;
					option = document.createElement('OPTION');
					option.value = '';
					option.text = '(Not Required)';
					next[0].options[next[0].options.length] = option;
					return;
				}

				list.fields.get(function(/** array of FieldModel instances */fields){
					next.empty();

					// fields were ajax-loaded successfully, or fields available locally
					var option;
					option = document.createElement('OPTION');
					option.value = '';
					option.text = '(' + lang.NoneString + ')';
					next[0].options[next[0].options.length] = option;

					var field;
					for (var i = 0; i < fields.length; i++) {
						field = fields[i];
						if (field.provider_field_id == "{{ module.object.getEmailProviderFieldId }}") {
							continue;
						}

						option = document.createElement('OPTION');
						option.value = field.provider_field_id;
						option.text = field.name;
						next[0].options[next[0].options.length] = option;
					}

					next.removeAttr('disabled');
				}, {
					loading: function(){
						// called if getFields() needs to do an ajax; we use it to indicate we're loading fields
						var option = document.createElement('OPTION');
						option.value = '';
						option.text = 'Loading, please wait...';
						next[0].options[next[0].options.length] = option;
					}
				});
			});
		});
	};

	$(function(){
		builder.delegate('.rule .ruleAdd, .rule .ruleCopy', 'click', function(event){
			event.preventDefault();

			var $$ = $(this);
			var rule = $$.closest('.rule');
			var copy = rule.clone();
			rule.after(copy);
			bindEvents(copy);

			var fields = ['action', 'list', 'map_subfirstname'];

			if ($$.hasClass('ruleAdd')) {
				// if we're adding a rule, reset the new row
				copy.find(getFieldSelector(fields[0])).attr('selectedIndex', 0).trigger('change');
			} else {
				// otherwise, select the copied row's values
				for (var i = 0; i < fields.length; i++) {
					copy.find(getFieldSelector(fields[i])).attr('selectedIndex', rule.find(getFieldSelector(fields[i])).attr('selectedIndex'));
				}
			}

			// in both cases, erase the copys id
			copy.find(getFieldSelector('id')).val('');
		});

		builder.delegate('.rule .ruleDelete', 'click', function(event){
			event.preventDefault();

			var $$ = $(this);
			var rule = $$.closest('.rule');
			var ruleContainer = rule.closest('tbody');

			if (ruleContainer.find('.rule').length <= 1) {
				// reset the final rule instead of deleting it
				rule.find(getFieldSelector('action')).attr('selectedIndex', 0).trigger('change');
			} else {
				rule.remove();
			}
		});

		var rules = [];
		{% for rule in module.object.getNewsletterSubscribedRules %}
			rules.push({{ rule.toJavaScript|safe }});
		{% endfor %}

		if (!rules.length) {
			// if no rules exist, move the rule template in
			bindEvents(builder.find('tfoot .rule').appendTo(ruleContainer));
			return;
		}

		// rules exist; introduce them by duplicating the template and amending
		var template = builder.find('tfoot .rule');

		$.each(rules, function(index, rule){
			var copy = template.clone();
			copy.appendTo(ruleContainer);
			bindEvents(copy);

			copy.find(getFieldSelector('id')).val(rule.id);

			switch (rule.action) {
				case ACTION_ADD:
					var action = 'listAdd';
					break;

				case ACTION_REMOVE:
					var action = 'listRemove';
					break;
			}
			copy.find(getFieldSelector('action')).val(action).trigger('change');

			// the above will trigger a load of list data, but the below requires that lists have been loaded to js - use ajaxdataprovider to enter the queue for this data

			module.lists.get(function(lists){
				// lists loaded
				copy.find(getFieldSelector('list')).val(rule.listId).trigger('change');

				// the above will trigger a load of field data, but the below requires that fields for the list above have been loaded to js - use ajaxdataprovider to enter the queue for this data
				module.getList(rule.listId, function(list){
					if (!list) {
						return
					}
					list.fields.get(function(fields){
						// fields are now available locally; fill in the field map selects
						var providerFieldName;
						for (providerFieldName in rule.fieldMap) {
							var mapFieldTo = rule.fieldMap[providerFieldName];
							copy
								.find(getFieldSelector('map_' + mapFieldTo))
								.val(providerFieldName)
								.trigger('change');
						}
					});
				});
			});
		});

		builder.find('tfoot .rule').remove();
	});

});
//]]></script>
