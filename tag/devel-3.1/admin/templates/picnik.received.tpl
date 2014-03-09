{% if PicnikError %}
	<h3>{% lang 'PicnikError' %}</h3>
	<p>{{ PicnikError }}</p>
	<p>{% lang 'PicnikRemoteFile' %}</p>
	<ul>
		<li><a href="{{ PicnikRemoteFile }}" target="_blank">{{ PicnikRemoteFile }}</a></li>
	</ul>
	<p>{% lang 'PicnikErrorInClosing' %}</p>
	<p><input type="button" id="picnikCloseWindow" value="{% lang 'CloseWindow' %}" /></p>
	<script language="javascript" type="text/javascript">//<![CDATA[
	$('#picnikCloseWindow').click(function(){
		try { opener.focus(); } catch (err) { }
		try { window.close(); } catch (err) { }
	});
	//]]></script>
{% else %}
	<script language="javascript" type="text/javascript">//<![CDATA[
	$(function(){
		// check for window opener
		var opener = window.opener;
		if (!(opener && opener.Common && opener.Common.Picnik && opener.Common.Picnik.callback)) {
			// parent window not open or picnik code not accessible from this window, abort
			try { window.close(); } catch (err) { }
			return;
		}

		opener.Common.Picnik.callback({{ PicnikCallbackData|safe }});

		try { opener.focus(); } catch (err) { }
		try { window.close(); } catch (err) { }
	});
	//]]></script>
{% endif %}
