<div style="margin-top:-20px">
	<h2>{% lang 'LivePersonCreated' %}</h2>
	<p class="Intro">{% lang 'LivePersonCreatedIntro' %}</p>
	<div class="Text">
	<ul>
		<li>{% lang 'LivePersonCreatedStep1' %}</li>
		<li>{% lang 'LivePersonCreatedStep2' %}
			<ul>
				<li>{% lang 'LivePersonSiteId' %}: {{ SiteId|safe }}</li>
				<li>{% lang 'LivePersonCreatedStep22' %}</li>
			</ul>
		</li>
		<li>{% lang 'LivePersonCreatedStep3' %}</li>
	</ul>
	<p style="text-align: center;"><input type="button" value="{% lang 'LivePersonFollowedSteps' %}" onclick="window.parent.IntegrateLivePerson({{ SiteId|safe }});" class="FormButton" style="width:190px" /></p>
	</div>
</div>