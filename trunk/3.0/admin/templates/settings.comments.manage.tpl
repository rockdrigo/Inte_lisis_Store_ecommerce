{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}
<div id="content">
	<form action="index.php?ToDo=saveCommentSystemSettings" method="post" id="commentSystemForm" accept-charset="utf-8">
		<input type="hidden" name="currentTab" id="currentTab" value="{{ currentTab }}" />
		<h1>{% lang 'CommentSettings' %}</h1>
		<p class="intro">
			{% lang 'CommentSettingsIntro' %}
		</p>

		{{ Message|safe }}

		<p class="intro">
			<input type="submit" value="{{ lang.Save }}" />
			or <a href="#" id="cancelComments">{% lang 'Cancel' %}</a>
		</p>

		<div id="tabs" class="tabs">
			{{ util.tabs(tabs) }}

			<div class="tabContent" id="general">
				{{ formBuilder.startForm }}

				{{ formBuilder.heading(lang.ChooseCommentSystem) }}

				{{ formBuilder.startRow(['label': lang.UseCommentSystem, 'required': true]) }}
					<div id="commentSystemList" class="ISSelect" style="height: 80px;">
						{% for system in commentSystems %}
							<label><input type="radio" name="commentSystem" value="{{ system.value }}" {% if system.selected %}checked="checked"{% endif %}/>{{ system.label }}</label>
						{% endfor %}
					</div>
				{{ formBuilder.endRow }}

				{{ formBuilder.endForm }}
			</div>

			{{ moduleTabContent|safe }}
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

			$("#cancelComments").click(function() {
				if(confirm('{% lang 'ConfirmCancel' %}')) {
					window.location = 'index.php?ToDo=viewCommentSystemSettings';
				}
			});
		});
	</script>
</div>