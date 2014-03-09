<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">
<head>
	<title>{% lang 'ControlPanel' %}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={{ CharacterSet }}" />
	<meta name="robots" content="noindex, nofollow" />
	<link rel="stylesheet" type="text/css">
	<style type="text/css">
		@import url("Styles/styles.css");
		@import url("Styles/tabmenu.css");
		@import url("Styles/iselector.css");
	</style>
	<!--[if IE]>
	<style type="text/css">
		@import url("Styles/ie.css");
	</style>
	<![endif]-->
	<script type="text/javascript" src="../javascript/jquery.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/common.js?{{ JSCacheToken }}"></script>
	<script langauge="javascript"><!--

	$(document).ready(function() {
		this.onkeyup = function(e){
			if (e == null) { // ie
				keycode = event.keyCode;
			} else { // mozilla
				keycode = e.which;
			}
			if(keycode == 27){ // close
				window.parent.tb_remove();
			}
		};

		// We need to ensure that this popup has focus, otherwise the onkeyup won't get fired
		window.setTimeout('window.focus();', 10);
	});

	//-->
	</script>
</head>

<body class="popupBodySlim">