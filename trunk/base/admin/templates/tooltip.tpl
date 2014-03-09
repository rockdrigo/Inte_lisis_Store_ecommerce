{% macro tooltip(id, title, content, titleReplacements, contentReplacements) %}
<img
	onmouseout="HideHelp('{{ id }}');" 
	onmouseover="ShowHelp('{{ id }}', '{% lang title with titleReplacements %}', '{% lang content with contentReplacements %}')" 
	src="images/help.gif" 
	width="24" 
	height="16" 
	border="0" 
	style="margin-top: 5px;"
/>
<div id="{{ id }}"></div>
{% endmacro %}