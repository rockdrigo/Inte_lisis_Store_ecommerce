<script language="javascript" type="text/javascript">//<![CDATA[
$(function(){
	// check for window opener
	var opener = window.opener;
	if (!(opener && opener.Common && opener.Common.Picnik && opener.Common.Picnik.callback)) {
		// parent window not open or picnik code not accessible from this window, abort
		try { window.close(); } catch (err) { }
		return;
	}

	opener.Common.Picnik.cancelEdit();

	try { opener.focus(); } catch (err) { }
	try { window.close(); } catch (err) { }
});
//]]></script>
