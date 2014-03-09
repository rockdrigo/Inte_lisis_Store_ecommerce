{% import "macros/forms.tpl" as formBuilder %}
{% import "macros/util.tpl" as util %}

{{ formBuilder.startForm(['type':'vertical']) }}

{{ formBuilder.startHeading }}
	{% lang 'Interspire_EmailIntegration_Rule_OrderCompleted_Name_Plural' %}
	{{ util.tooltip('Interspire_EmailIntegration_Rule_OrderCompleted_Name_Plural', 'NewCustomerSubscriptionRulesHelp') }}
{{ formBuilder.endHeading }}

{{ formBuilder.startRow() }}

	<table class="emailIntegrationRuleBuilder newCustomerSubscriptionRuleBuilder">
		<thead>
			<tr>
				<th>{{ lang.WhenSomeoneOrders }}</th>
				<th>{{ lang.ChooseCategoryBrandProduct }}</th>
				<th>{{ lang.TakeThisAction }}</th>
				<th>{{ lang.ChooseAList }}</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
		<tfoot>
			{# rule template #}
			<tr class="rule">
				<td class="order">
					<input type="hidden" class="customerrules_id" />
					<select class="Field150 customerrules_order" class="Field150">
						<option value=""></option>
						<option value="any">{{ lang.AnythingInMyStore }}</option>
						<option value="category">{{ lang.FromThisCategory }}</option>
						<option value="brand">{{ lang.FromThisBrand }}</option>
						<option value="product">{{ lang.ASpecificProduct }}</option>
					</select>
				</td>
				<td class="orderCriteria">
					<input type="hidden" class="customerrules_ordercriteria" />
					<div class="orderCriteriaContainer orderCriteriaNone orderCriteriaAny">
						<input type="text" class="Field150" value="{{ lang.NotRequired }}" disabled="disabled" />
					</div>
					<div class="orderCriteriaContainer orderCriteriaCategory orderCriteriaBrand orderCriteriaProduct" style="display:none;">
						<input type="text" class="Field150 orderCriteriaDisplay" readonly="readonly" /><a class="orderCriteriaLinker" href="#"><img src="images/find.gif" width="16" height="16" /></a>
					</div>
				</td>
				<td class="action">
					<select class="Field150 customerrules_action" disabled="disabled">
						<option value=""></option>
						<option value="listAdd">{{ lang.AddToList }}</option>
						<option value="listRemove">{{ lang.RemoveFromList }}</option>
					</select>
				</td>
				<td class="list">
					<select class="customerrules_list Field160" disabled="disabled"></select>
				</td>
				<td class="ruleBuilderActions">
					<input type="hidden" class="customerrules_map" />
					<span class="ruleListMapEnabledContainer" style="display:none;">
						<a href="#" class="ruleListMap"><span>{{ lang.SyncOrderFields }}</span></a>
					</span>

					<span class="ruleListMapDisabledContainer">
						<span class="Disabled">{{ lang.SyncOrderFields }}</span>
					</span>

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
	var builder = moduleContainer.find('.newCustomerSubscriptionRuleBuilder');
	var ruleContainer = builder.find('tbody');

	var getFieldSelector = function (field) {
		return '.customerrules_' + field;
	};

	var bindEvents = function (newRule) {
		// this exists because for some reason .live and .delegate for change events are NOT working for these fields in IE
		newRule.find(getFieldSelector('order')).change(function(event){
			var $$ = $(this);
			var val = $$.val();
			var rule = $$.closest('.rule');

			rule.find('.orderCriteriaContainer').hide();
			if (!val) {
				var nextContainer = rule.find('.orderCriteriaNone');
			} else {
				var nextContainer = rule.find('.orderCriteria' + val.ucfirst());
			}
			nextContainer.show();
			rule.find(getFieldSelector('ordercriteria')).val('');
			rule.find('.orderCriteriaDisplay').val('');

			next = rule.find(getFieldSelector('action'));
			if (val) {
				next.removeAttr('disabled');
			} else {
				next.val('');
				next.change();
				next.attr('disabled', 'disabled');
			}
		});

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
					next[0].options[i+1] = option;
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
					next[0].options[0] = option;
					next.attr('selectedIndex', 0).trigger('change');
				}
			});
		});

		newRule.find(getFieldSelector('list')).change(function(event){
			var $$ = $(this);
			var listId = $$.val();
			var previous = $$.data('previousListId');
			$$.data('previousListId', listId);
			var rule = $$.closest('.rule');

			var ruleListMapEnabled = true;
			var disabledReason = '';
			var ruleAction = rule.find(getFieldSelector('action')).val();

			if (ruleAction == 'listRemove') {
				ruleListMapEnabled = false;
				disabledReason = lang.EmailIntegrationFieldSyncNotRequired;
			} else if (!listId) {
				ruleListMapEnabled = false;
				disabledReason = lang.EmailIntegrationChooseAListToSync;
			}

			if (ruleListMapEnabled) {
				rule.find('.ruleListMapEnabledContainer').show();
				rule.find('.ruleListMapDisabledContainer').hide().data('disabledReason', disabledReason);
			} else {
				rule.find('.ruleListMapEnabledContainer').hide();
				rule.find('.ruleListMapDisabledContainer').show().data('disabledReason', disabledReason);
			}

			if (previous && ruleListMapEnabled) {
				// when switching between valid 'add' actions, attempt to re-map rules

				try {
					var existingMap = JSON.parse(rule.find(getFieldSelector('map')).val());
				} catch (ex) {
					var existingMap = false;
				}

				if (existingMap) {
					module.getList(listId, function(list){
						list.fields.get(function(fields){
							// examine our current map and drop any mappings that have no equivalent in the new list's fields
							var newMap = {};
							$.each(existingMap, function(provider, local){
								$.each(fields, function(index, field){
									if (field.provider_field_id == provider) {
										newMap[provider] = local;
										return false;
									}
								});
							});
							rule.find(getFieldSelector('map')).val(JSON.stringify(newMap));
						});
					});
				}
			}
		});
	};

	$(function(){
		builder.delegate('.ruleListMapDisabledContainer', 'click', function(event){
			event.preventDefault();
			var $$ = $(this);
			var message = $$.data('disabledReason');
			if (message) {
				alert(message);
			}
		});

		builder.delegate('.ruleListMap', 'click', function(event){
			event.preventDefault();
			var $$ = $(this);
			var rule = $$.closest('.rule');
			var list = rule.find(getFieldSelector('list')).val();
			if (!list) {
				return;
			}

			$('.newCustomerSubscriptionRuleBuilder').data('syncingFieldsFor', rule);

			var urlData = {
				remoteSection: 'settings_emailintegration',
				w: 'providerAction',
				provider: module.provider,
				providerAction: 'getFieldSyncForm',
				listId: list,
				map: rule.find('.customerrules_map').val()
			};

			$.iModal({
				type: 'ajax',
				method: 'post',
				width: 520,
				url: 'remote.php',
				urlData: urlData
			});
		});

		builder.delegate('.rule .ruleCopy, .rule .ruleAdd', 'click', function(event){
			event.preventDefault();

			var $$ = $(this);
			var rule = $$.closest('.rule');
			var copy = rule.clone();
			rule.after(copy);
			bindEvents(copy);

			var fields = ['order', 'orderCriteria', 'action', 'list'];
			if ($$.hasClass('ruleAdd')) {
				// if we're adding a rule, reset the new row
				copy.find(getFieldSelector('order')).attr('selectedIndex', 0).trigger('change');
				copy.find(getFieldSelector('action')).attr('selectedIndex', 0).trigger('change');
				copy.find(getFieldSelector('map')).val('');
			} else {
				// otherwise, select the copied row's values
				for (var i = 0; i < fields.length; i++) {
					var field = copy.find(getFieldSelector(fields[i]));
					field.attr('selectedIndex', rule.find(getFieldSelector(fields[i])).attr('selectedIndex'));
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
				rule.find(getFieldSelector('order') + ', ' + getFieldSelector('action') + ', ' + getFieldSelector('list')).attr('selectedIndex', 0).trigger('change');
				rule.find(getFieldSelector('map') + ', ' + getFieldSelector('ordercriteria')).val('');
			} else {
				rule.remove();
			}
		});

		builder.delegate('.rule .orderCriteriaLinker, .rule .orderCriteriaDisplay', 'click', function(event){
			event.preventDefault();

			var $$ = $(this);
			var rule = $$.closest('.rule');
			builder.linkerRule = rule;
			StoreLinker.openModal(0, rule.find(getFieldSelector('order')).val());
		});

		var rules = [];
		{% for rule in module.object.getOrderCompletedRules %}
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

			copy.find(getFieldSelector('order')).val(rule.eventCriteria.orderType).trigger('change');

			copy.find(getFieldSelector('ordercriteria')).val(rule.eventCriteria.orderCriteria).trigger('change');
			copy.find('.orderCriteriaDisplay').val(rule.eventCriteria.orderCriteriaName);

			switch (rule.action) {
				case ACTION_ADD:
					var action = 'listAdd';
					break;

				case ACTION_REMOVE:
					var action = 'listRemove';
					break;
			}
			copy.find(getFieldSelector('action')).val(action).trigger('change');

			// make sure lists are downloaded
			module.lists.get(function(lists){
				copy.find(getFieldSelector('list')).val(rule.listId).trigger('change');
				copy.find(getFieldSelector('map')).val(JSON.stringify(rule.fieldMap));
			});
		});

		builder.find('tfoot .rule').remove();
	});

	// override the default storelinker behaviour, which is tied to redirects
	StoreLinker.onModalClose = function () {
		if (typeof StoreLinker.selectedItem.id == 'undefined') {
			return;
		}

		var rule = builder.linkerRule;
		delete builder.linkerRule;

		rule.find(getFieldSelector('ordercriteria')).val(StoreLinker.selectedItem.id);
		rule.find('.orderCriteriaDisplay').val(StoreLinker.selectedItem.title);
	};

});
//]]></script>
