<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11">
<html {% if rtl %}dir="rtl"{% endif %} xml:lang="{{ language }}" lang="{{ language }}">
<head>
	<title>{% lang 'UpgradeInterspireShoppingCart' %}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={{ CharacterSet }}" />
	<meta name="robots" content="noindex, nofollow" />
	<style type="text/css">
		@import url("Styles/styles.css");
		@import url('Styles/tabmenu.css');
		@import url("Styles/iselector.css");
	</style>
	<!--[if IE]>
	<style type="text/css">
		@import url("Styles/ie.css");
	</style>
	<![endif]-->
	<style>
		h3 { font-size:13px; }
	</style>
	<script type="text/javascript">
		var url = 'remote.php';
		var critical_errors = "{{ CriticalErrors|safe }}";
	</script>
	<script type="text/javascript" src="../javascript/jquery.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/menudrop.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="../javascript/thickbox.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/common.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="script/install.js?{{ JSCacheToken }}"></script>
	<script type="text/javascript" src="../javascript/iselector.js?{{ JSCacheToken }}"></script>
	<link rel="stylesheet" href="Styles/thickbox.css?{{ JSCacheToken }}" type="text/css" media="screen" />
</head>

<body>
	<div id="box">
		<br /><br /><br /><br />
		<table><tr><td style="border:solid 2px #DDD; padding:20px; background-color:#FFF; width:450px">
		<table>
		  <tr>
			<td class="Heading1">
				<img src="images/logo.jpg" />
			</td>
		  </tr>
		  <tr>
			<td style="padding:10px 0px 5px 0px">
				<div style="display: {{ HideUpgradeWelcome|safe }}">
					<strong>{% lang 'UpgradeInterspireShoppingCart' %}</strong>
					<div style="{{ HideUpgradeWarning|safe }}" class="MessageBox MessageBoxInfo">
						{{ UpgradeWarning|safe }}
					</div>
					<p>{{ UpgradeFromTo|safe }}</p>
					<p>{% lang 'UpgradeWelcomeStart' %}</p>
					<p>
						<label><input type="checkbox" name="sendServerDetails" id="sendServerDetails" value="1" checked="checked" style="vertical-align: middle;" /> {% lang 'SendServerDetails' %}</label>
						<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="alert('{% lang 'ServerDetailsInfo' %}')" style="color:gray">{% lang 'WhatWillBeSent' %}</a>
					</p>

					<input type="button" value="{% lang 'StartUpgrade' %}" onclick="RunUpgrade()" class="FormButton Field100" />
				</div>
				<div style="display: {{ HideUpgradeContinue|safe }}">
					<strong>{% lang 'UpgradeInterspireShoppingCart' %}</strong>
					<p>{% lang 'UpgradeContinueWelcome' %}</p>
					<input type="button" value="{% lang 'ContinueUpgrade' %}" onclick="RunUpgrade()" class="Field100" />
				</div>
				<div style="display: {{ HideUpgradeErrors|safe }}">
					<strong>{% lang 'UpgradeInterspireShoppingCart' %}</strong>
					<p>{{ UpgradeFromTo|safe }}</p>
					<p><strong style="color: red;">{% lang 'OopsUpgradePreChecks' %}</strong></p>
					<ul>
						{{ UpgradeErrors|safe }}
					</ul>
					<p>{% lang 'UpgradePreChecksRetry' %}</p>
					<input type="button" value="{% lang 'Retry' %}" onclick="document.location.reload()" class="Field100" />
				</div>

			</td>
		  </tr>
		</table>
		</td></tr></table>
		<div style="padding:10px; margin-bottom:20px; text-align:center">{{ Copyright|safe }}</div>
	</div>
	<script type="text/javascript">
	function RunUpgrade() {
		var urlAppend = '';
		if($('#sendServerDetails:checked').val()) {
			urlAppend = '&sendServerDetails=1';
		}
		tb_show('', 'index.php?ToDo=showUpgradeFrame'+urlAppend+'&keepThis=true&TB_iframe=true&height=230&width=400&modal=true&random='+new Date().getTime(), '');
	}
	</script>
	{{ HiddenImage|safe }}
</body>
</html>