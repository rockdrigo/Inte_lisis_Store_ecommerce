<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11">
<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">
<head>
	<title>{% lang 'ControlPanel' %}</title>
	<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
	<style type="text/css" media="screen">@import "templates/iphone/iui/iui.css";</style>
	<script type="application/x-javascript" src="templates/iphone/iui/iui.js?{{ JSCacheToken }}"></script>
	<meta http-equiv="Content-Type" content="text/html; charset={{ CharacterSet }}" />
	<meta name="robots" content="noindex, nofollow" />
</head>

<body>
    <div class="toolbar">
        <h1 id="pageTitle"></h1>
    </div>
    <div id="Login" title="{% lang 'LoginBelow' %}" class="panel" selected="true">
	<fieldset>
	<form action="index.php?ToDo={{ SubmitAction|safe }}" class="dialog" onsubmit="return CheckLoginForm()" method="post">
	    <div class="row">
                <label>{% lang 'Username' %}</label>
                <input type="text" name="username" id="username" value="{{ Username|safe }}"/>
            </div>
            <div class="row">
                <label>{% lang 'Password' %}</label>
                <input type="password" autocomplete="off" name="password" id="password" value="{{ Password|safe }}"/>
            </div>
	</fieldset>
    	<input type="submit" value="{% lang 'Login' %}" style="margin-top:-10px" />
	</form>
    </div>

    <script type="text/javascript">

	function CheckLoginForm() {
		var u = document.getElementById("username");
		var p = document.getElementById("password");

		if(u.value == "") {
			alert('{% lang 'NoUsername' %}');
			u.focus();
			return false;
		}

		if(p.value == "") {
			alert('{% lang 'NoPassword' %}');
			p.focus();
			return false;
		}

		// Everything is OK
		return true;
	}

    </script>

</body>
</html>