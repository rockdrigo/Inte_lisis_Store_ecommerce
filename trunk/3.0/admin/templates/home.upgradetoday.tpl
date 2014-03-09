<table width="100%" cellspacing="0" cellpadding="0" class="DashboardPanel UpgradeNotice">
	<tr>
		<td class="Heading2">
			<div class="PanelHeader" id="HomeUpgradeTitle">{% lang 'UpgradeStoreToday' %}</div>
		</td>
	</tr>
	<tr>
		<td class="PanelContent">
			{{ ExpiryMessage|safe }}
			<div style="{{ HideUpgradeDetails|safe }}">
				<p>{{ UpgradeCurrentlyRunning|safe }} {% lang 'UpgradeToday' %}</p>
				{% lang 'UpgradeTodayFeatures' %}
			</div>
			<p style="text-align: left;"><a href="http://www.interspire.com/shoppingcart/compare.php" target="_blank"><img src="images/learnMore.gif" alt="" border="0" /></a></p>
		</td>
	</tr>
</table>