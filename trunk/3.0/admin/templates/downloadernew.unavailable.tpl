<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}"><HEAD>
<TITLE>{% lang 'TemplateUnavailable' %}</TITLE>
<style>

	.Message
	{
		Color: #000000;
		Font-Family: Tahoma;
		Font-size: 11px;
		padding-top: 5px;
	}

</style>

</HEAD>
<body>

	<div class="Message" align="center">{{ Message|safe }}</div>
</body>
</html>
