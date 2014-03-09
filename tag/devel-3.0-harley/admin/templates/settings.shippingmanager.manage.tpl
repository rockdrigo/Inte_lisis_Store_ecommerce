{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}

<div id="content">
	<form action="index.php?ToDo=saveShippingManagerSettings" method="post" id="shippingManagerForm" accept-charset="utf-8">
		<input type="hidden" name="currentTab" id="currentTab" value="{{ currentTab }}" />
		<h1>{{ lang.ShippingManagerSettings }}</h1>
		<p class="intro">
			{{ lang.ShippingManagerIntro }}
		</p>

		{{ Message|safe }}

		<p class="intro">
			<input type="submit" value="{{ lang.Save }}" />
			{{ lang.Or }} <a href="#" class="cancelLink">{{ lang.Cancel }}</a>
		</p>

		<div id="tabs" class="tabs">
			{{ util.tabs(tabs) }}

			<div class="tabContent" id="general">
				{{ formBuilder.startForm }}
					{{ formBuilder.heading(lang.ShippingManagerSettings) }}

					{{ formBuilder.startRow([
						'label': lang.ManageShippingWith,
						'required': true
					]) }}
						{{ formBuilder.multiSelect('shippingManagers[]', shippingManagers, enabledShippingManagers, [
							'id': 'shippingManagers',
							'class': 'Field300 ISSelectReplacement'
						]) }}
					{{ formBuilder.endRow }}
				{{ formBuilder.endForm }}
			</div>

			{{ moduleTabContent|safe }}
		</div>

		<div class="horizontalFormContainer" style="background-color: white;">
			{{ formBuilder.startButtonRow }}
				<input type="submit" value="{{ lang.Save }}" />
				or <a href="#" class="cancelLink">{% lang 'Cancel' %}</a>
			{{ formBuilder.endButtonRow }}
		</div>
	</form>

	<script type="text/javascript">
		$(document).ready(function() {
			$('#tabs').tabs({
				select: function(event, ui){
					$('#currentTab').val(ui.panel.id);
				}
			});

			{% if currentTab %}$('#tabs').tabs('select', '{{ currentTab|js }}');{% endif %}

			$('.cancelLink').click(function() {
				if(confirm('{% lang 'ConfirmCancel' %}')) {
					window.location = 'index.php?ToDo=viewShippingManagerSettings';
				} else {
					return false;
				}
			});
		});
	</script>
</div>