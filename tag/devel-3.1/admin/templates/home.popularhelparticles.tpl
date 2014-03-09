<!-- Popular help articles RSS feed. -->
<table width="100%" cellspacing="0" cellpadding="0" class="DashboardPanel">
	<tr>
		<td class="Heading2">
			<div class="PanelHeader" id="HomeHelpTitle">{% lang 'PopularHelpArticles' %}</div>
		</td>
	</tr>
	<tr>
		<td class="PanelContent" id="HelpeRSS">
		</td>
	</tr>
</table>
<script type="text/javascript">
$(document).ready(function() {
	$('#HelpeRSS').load('index.php?ToDo=HelpRSS');
});
</script>