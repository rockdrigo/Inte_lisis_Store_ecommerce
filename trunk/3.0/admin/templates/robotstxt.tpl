{% import "macros/forms.tpl" as formBuilder %}
<div id="content">
	<h1>{{ lang.EditRobotsTxtFile }}</h1>
	<p class="intro">{{ lang.RobotsIntro }}</p>
	{{ flashMessages|safe }}
	<form action="index.php?ToDo=saveRobotsTxt" method="post" id="robotsForm">
		<p class="intro">
			<input type="submit" value="{{ lang.Save }}" class="saveButton" />
			<input type="submit" value="{{ lang.RobotsRevertButton }}" id="robotstxtRevertButton" name="robotstxtRevertButton" />
			&nbsp;{% lang 'Or' %}&nbsp;<a href="#" id="robotstxtCancelLink">{% lang 'Cancel' %}</a>
		</p>
		{{ formBuilder.startForm }}
			{{ formBuilder.heading(lang.RobotsTxt) }}
			{{ formBuilder.startRow }}
			<textarea class="Field600" rows="20" name="robotstxtFileContent">{{ fileContent|safe }}</textarea>
			{{ formBuilder.endRow }}
		{{ formBuilder.endForm }}
	</form>
</div>
<script type="text/javascript" charset="utf-8">
	$('#robotstxtRevertButton').bind('click', function () {
		var r = confirm("{{ lang.RobotsRevertPrompt }}");
		return r;
	});

	$('#robotstxtCancelLink').bind('click', function () {
		var r = confirm( "{{ lang.RobotsCancelPrompt }}");
		if (r == true) {
			window.location = 'index.php?ToDo=ViewEditRobotsTxt';
		}

		return false;
	});
</script>
