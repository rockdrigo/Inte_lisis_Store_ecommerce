
	<div class="BodyContainer">
	<table class="OuterPanel">
		<tr>
			<td class="Heading1">{% lang 'AddonPackages' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'AddonsIntro' %}</p>
			{{ Message|safe }}
		</td>
		</tr>
		<tr>
		<td>
			<table class="Panel">
				<tr>
				  <td class="Heading2">{% lang 'AddonsYouveDownloaded' %}</td>
				</tr>
				<tr>
					<td style="padding:5px 5px 5px 10px;">
						{{ ExistingAddons|safe }}
					</td>
				</tr>
			 </table>
			<div style="{{ HideDownloadAddons|safe }}">
			 <br />
				<table class="Panel">
					<tr>
					  <td class="Heading2">{% lang 'AddonsAvailableForDownload' %}</td>
					</tr>
					<tr>
						<td style="padding:5px 5px 5px 10px;">
							{{ DownloadableAddons|safe }}
						</td>
					</tr>
				 </table>
			</div>
		</td></tr>
	</table>
	</div>
