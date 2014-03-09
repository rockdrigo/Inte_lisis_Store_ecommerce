<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">
<head>
	<title>{{ config.StoreName }}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={{ CharacterSet }}" />
	<meta name="robots" content="noindex, nofollow" />
	<style type="text/css">
		{# probably not all of these are needed but this is taken from pageheader.tpl #}
		@import url("Styles/styles.css?{{ JSCacheToken }}");
		@import url('Styles/new.css?{{ JSCacheToken }}');
		@import url('Styles/tabmenu.css?{{ JSCacheToken }}');
		@import url("Styles/iselector.css?{{ JSCacheToken }}");
		@import url('../javascript/jquery/plugins/imodal/imodal.css?{{ JSCacheToken }}');
		@import url('Styles/iconsearchbox.css?{{ JSCacheToken }}');
		@import url('Styles/thickbox.css?{{ JSCacheToken }}');

		@import url('Styles/iframe.css?{{ JSCacheToken }}');
		{{ AdditionalStylesheets|safe }}
	</style>
	<link rel="SHORTCUT ICON" href="{{ FaviconPath }}" />
	<!--[if IE]>
	<style type="text/css">
		@import url("Styles/ie.css?{{ JSCacheToken }}");
	</style>
	<![endif]-->

	{{ RTLStyles|safe }}

	{% for script in headScripts %}
		<script type="text/javascript" src="{{ script }}?{{ JSCacheToken }}"></script>
	{% endfor %}

	<script type="text/javascript">
		$(document).ready(function() {
			$('.GridPanel input:checkbox').shiftcheckbox();
		});
		config.ProductName = '{{ ProductName|safe }}';
		config.ShopPath = '{{ ShopPath|safe }}';
		var ThousandsToken = '{{ ThousandsToken }}';
		var DecimalToken = '{{ DecimalToken }}';
		var DimensionsThousandsToken = '{{ DimensionsThousandsToken }}';
		var DimensionsDecimalToken = '{{ DimensionsDecimalToken }}';
		{{ DefineLanguageVars|safe }}
		var url = 'remote.php';
	</script>
</head>
<body class="iframe">
	<div id="AjaxLoading"><img src="images/ajax-loader.gif" />&nbsp; {% lang 'LoadingPleaseWait' %}</div>

	{% block body %}{% endblock %}

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
