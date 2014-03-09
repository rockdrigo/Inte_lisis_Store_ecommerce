<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang '301Redirects' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'RedirectsIntro' %}</p>
				<p id="TemplateMsgBox">{{ Message|safe }}</p>
					<input type="button" id="AddNewRedirectButton" value="{% lang 'AddNewRedirect' %}" /> <input type="button" id="DeleteSelectedRedirects" value="{% lang 'DeleteSelected' %}" /> <input type="button" id="ExportRedirects" value="{% lang 'ExportRedirects' %}"> <input type="button" id="BulkImportRedirects" value="{% lang 'BulkImportRedirects' %}" /> &nbsp;<a href="#" onclick="LaunchHelp(890);">{% lang 'LearnMoreImportingRedirects' %}</a>
			</td>
		</tr>
		<tr>
			<td>
				<div id="div0" style="">
					<div class="MessageBox MessageBoxInfo" style="display: none;" id="NoRedirects">
						{% lang 'NoRedirects' %}
					</div>
					<form method="post" action="index.php?ToDo=startExport&t=redirects&tempId=4" id="frmRedirects" onsubmit="return false;">
						<div id="RedirectsTable">

						</div>
					</form>
				</div>
			</td>
		</tr>
	</table>
</div>
<script type="text/javascript" src="script/redirects.js?{{ JSCacheToken|safe }}"></script>
<script type="text/javascript" src="script/linker.js?{{ JSCacheToken|safe }}"></script>
<script type="text/javascript">
lang.SelectRedirectsToDelete = "{% lang 'SelectRedirectsToDelete' %}";
lang.NoBulkImportFile = "{% lang 'NoBulkImportFile' %}";
lang.EnterAnOldUrl = "{% lang 'EnterAnOldUrl' %}";
lang.ConfirmDeleteSelected = "{% lang 'ConfirmDeleteSelected' %}";
lang.ConfirmDeleteRedirect = "{% lang 'ConfirmDeleteRedirect' %}";
lang.ClickHereToEnterAURL = "{% lang 'ClickHereToEnterAURL' %}";
lang.ChangeLink = "{% lang 'ChangeLink' %}";
$(document).ready(function() {
	Redirects.LoadTable();
});

</script>
