		{{ GettingStartedStep|safe }}
	</div>
	<div class="PageFooter" style="text-align: right;">
		{{ DebugDetails|safe }}
		{{ AdminCopyright|safe }}
	</div>
</div>

{% for script in bodyScripts %}
	<script type="text/javascript" src="{{ script }}?{{ JSCacheToken }}"></script>
{% endfor %}

{{ taskManagerScript|safe }}

{% if idletime %}
<script type="text/javascript" src="../javascript/jquery/plugins/idletimer/cookie.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="../javascript/jquery/plugins/idletimer/idletimer.js?{{ JSCacheToken }}"></script>
<script type="text/javascript">
(function($){
	$.idleTimer({{ idletime }}, {});
})(jQuery);
</script>
{% endif %}

</body>
</html>
