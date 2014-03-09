{% lang 'PicnikDirectLoading' %}
<form method="post" id="picnikLaunchForm" action="{{ PicnikServiceUrl }}">
	<input type="hidden" name="_apikey" value="{{ PicnikApiKey }}" />
	<input type="hidden" name="_import" value="{{ PicnikImageUrl }}" />
	<input type="hidden" name="_export" value="{{ PicnikSaveHandler }}" />
	<input type="hidden" name="_export_title" value="{{ PicnikSaveTitle }}" />
	<input type="hidden" name="_export_agent" value="browser" />
	<input type="hidden" name="_close_target" value="{{ PicnikCloseHandler }}" />
	<input type="hidden" name="_exclude" value="out" />
</form>
<script language="javascript" type="text/javascript">//<![CDATA[
$(function(){
	$('#picnikLaunchForm').submit();
});
//]]></script>
