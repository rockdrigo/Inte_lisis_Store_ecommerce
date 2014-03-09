<li class="group">{{ Subject|safe }}</li>
<li>
	{{ OrderMessage|safe }}
	<div style="color:gray; font-size:11px">
		{% lang 'From' %} {{ OrderFrom|safe }} {% lang 'OnWord' %} {{ MessageDate|safe }}
	</div>
</li>
