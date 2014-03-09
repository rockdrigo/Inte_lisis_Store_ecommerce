{% import 'tooltip.tpl' as tooltip %}

<link rel="stylesheet" type="text/css" href="Styles/shoppingcomparison.css"/>
<form action="index.php?ToDo=saveShoppingComparison" method="post">
	<div class="BodyContainer">
		<table class="Table13" cellspacing="0" cellpadding="0" width="100%">
			<tbody>
				<tr>
					<td class="Heading1">{% lang 'ShoppingComparisonManagePageTitle' %}</td>
				</tr>
				<tr>
					<td class="Intro">
						<div>{{ Message|safe }}</div>
						<p>{% lang 'ShoppingComparisonManagePageDescription' %}</p>
					</td>
				</tr>
				<tr>
					<td>

						<div id="tabs">
							<ul id="tabnav">
								<li><a href="#sites">{% lang 'ShoppingComparisonChooseSites' %}</a></li>
								{% for module in modules %}
								{% if module.checkEnabled() %}
								<li><a href="#{{ module.id }}">{{ module.getName() }}</a></li>
								{% endif %}
								{% endfor %}
							</ul>

							{# First tab: Module selector #}
							<div id="sites">
								<table class="Panel">
									<tbody>
										<tr>
											<td class="FieldLabel">{% lang 'ShoppingComparisonSites' %}</td>
											<td>
												<select name="modules[]" class="Field250 ISSelectReplacement" multiple="multiple" style="height: 108px;">
													{% for module in modules %}
													<option value="{{ module.getId() }}"{% if module.checkEnabled() %} selected="selected"{% endif %}>{{ module.getName() }}</option>
													{% endfor %}
												</select>
												{{ tooltip.tooltip('shoppingComparisonToolTip', 'ShoppingComparisonToolTipTitle', 'ShoppingComparisonToolTipContent') }}
											</td>
										</tr>
										<tr>
											<td class="Gap" colspan="2"></td>
										</tr>
									</tbody>
								</table>

								<table>
									<tr>
										<td style="width: 183px;"></td>
										<td>
											<button class="FormButton" type="submit">{% lang 'Save' %}</button>
											<button class="FormButton cancel" type="button">{% lang 'Cancel' %}</button>
										</td>
									</tr>
								</table>
							</div>

							{# Module tabs: One tab per active comparison module #}
							{% for module in modules %}
							{% if module.checkEnabled() %}
							<div class="module" id="{{ module.getId() }}">
								<table class="Panel">
									<tbody>
										<tr>
											<td>
											{% for logo in module.getLogos() %}
											<a target="_blank" href="{{logo.url}}">
											<img class="ShoppingComparisonLogo" src="{{logo.image}}"/>
											</a>
											{% endfor %}

											</td>

										</tr>
										<tr>
											<td>
												<p>{{module.getHelpText()|safe}}</p>

												<div class="content">
												{% for message in module.getMessages() %}
												<div class="MessageBox MessageBox{{message.type}}">{{message.content|safe}}</div>
												{% endfor %}
												</div>
												<button class='generateFeed smallbutton' value="Generate New Export">Regenerate Feed...</button>
												<button class='downloadFeed smallbutton' href="index.php?ToDo=downloadShoppingComparisonFeed&mid={{module.getId()}}">Download Feed File</button>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							{% endif %}
							{% endfor %}
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</form>

<div id="unmappedCategoriesModal" style="display: none;">
	<div class="ModalTitle">Wait! 48 Categories Aren't Mapped Correctly</div>
	<div class="ModalContent">
		<table class="Panel" width="100%">
			<tr>
				<td><span class="content">
					</span>
					<span class="contentTemplate" style="display:none">

					</span>
				</td>
			</tr>
		</table>
	</div>
	<div class="ModalButtonRow">
		<input type="button" class="generateFeed" value="Generate Feed File Anyway"/>
		<input type="button" class="close FormButton" value="{% lang 'Close' %}"/>
	</div>
</div>

<script type="text/javascript" src="script/jobtracker.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/shoppingcomparison.js?{{ JSCacheToken }}"></script>
<script type='text/javascript'>

(function($){
$('document').ready(function(){
	$.extend(lang, {
		ShoppingComparisonUnmappedCategoriesModalTitle : "{% jslang 'ShoppingComparisonUnmappedCategoriesModalTitle' %}",
		ShoppingComparisonUnmappedCategoriesModalHelp : "{% jslang 'ShoppingComparisonUnmappedCategoriesModalHelp' %}",
		ShoppingComparisonFeedBeingGenerated : "{% jslang 'ShoppingComparisonFeedBeingGenerated' %}",
		ConfirmCancel : "{% lang 'ConfirmCancel' %}"
	});

	$.extend(ShoppingComparison, {
		startJobUrl : 'index.php?ToDo=generateShoppingComparisonFeed&mid=:moduleid',
		stopJobUrl : 'index.php?ToDo=stopShoppingComparisonFeed&mid=:moduleid',
		categoriesUrl : 'index.php?ToDo=viewCategories'
	});

	{% for module in modules %}
		{% if module.checkEnabled() %}
			ShoppingComparison.modules['{{module.getId()}}'] = {
				name : '{{ module.getName() }}',
				jobid : '{{ module.exportTask().getId() }}',
				unmappedCategories : {{ module.numUnmappedCategories() }}
			};
		{% endif %}
	{% endfor %}

	ShoppingComparison.init();
});
})(jQuery);
</script>