<form name="redirectform" action="{{ AuthURL|safe }}" method="POST">
<input type="hidden" name="PaReq" value="{{ PaReq|safe }}"/>
<input type="hidden" name="TermUrl" value="{{ TermUrl|safe }}"/>
<input type="hidden" name="MD" value="{{ MD|safe }}"/>
<NOSCRIPT>
<center><p>{% lang 'ProtxVspDirect3DRedirectInstruction' %}</p><input type="submit" value="Go"/></p></center>
</NOSCRIPT>
</form>
<script type="text/javascript">
//<![CDATA[
function SubmitForm() {
	document.redirectform.submit();
}

window.onload = SubmitForm;
//]]>
</script>
