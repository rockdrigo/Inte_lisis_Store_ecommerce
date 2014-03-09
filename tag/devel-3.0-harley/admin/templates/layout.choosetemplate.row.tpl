<div class="TemplateBox {{ TemplateInstalledClass|safe }} {{ CurrentTemplateClass|safe }} TemplateId_{{ TemplateId|safe }}">
	<div class="TemplateHeading">
			<span class="TemplateName">{{ TemplateName|safe }}</span> - <span class="TemplateColor">{{ TemplateColor|safe }}</span>
	</div>
	<a href="{{ TemplatePreviewFull|safe }}" class="TplPreviewImage" title="{% lang 'PreviewLargeImage' %}">
		<img class="TemplatePreviewThumb" src="{{ TemplatePreviewThumb|safe }}" alt="{{ TemplateName|safe }} - {{ TemplateColor|safe }}" />
	</a>
	<a href="#" class="ActivateLink" title="{{ TemplateName|safe }} - {{ TemplateColor|safe }}">{% lang 'ApplyThisTemplate' %}</a>
</div>