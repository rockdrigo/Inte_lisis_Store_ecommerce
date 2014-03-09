<div class="ModalTitle">
	{% lang 'GoogleSitemap' %}
</div>
<div class="ModalContent">
	<div id="exportIntro">
		<p>
			{% lang 'GoogleSitemapIntro' %}
		</p>

		<table border="0">
			<tr>
				<td width="1"><img src="images/froogle.gif" height="16" width="16" hspace="5" alt="" /></td>
				<td><a href="http://www.google.com/support/webmasters/bin/answer.py?hl=en&answer=156184"  style="color:#005FA3; font-weight:bold" target="_blank">{% lang 'GoogleSitemapLearnMore' %}</a></td>
			</tr>
		</table>

		<p>{% lang 'GoogleSiteMapLocated' %}</p>
		<p style="padding-left: 25px">
			<input type="text" class="Field300" onclick="this.select()" readonly="readonly" value="{{ SiteMapUrl|safe }}" />
		</p>
	</div>
</div>
<div class="ModalButtonRow">
	<input type="button" value="{% lang 'Close' %}" onclick="$.iModal.close()" class="SubmitButton" />
</div>