<table width="100%" border="0" cellspacing="1" cellpadding="1">
	<tr>
		<td valign="top">
			<h5 style="margin: 0pt 0pt 5pt 0pt">{% lang 'GiftCertificateDetails' %}</h5>
			<table width="95%" border="0" align="right">
				<tr>
					<td width="150" class="text">{% lang 'GiftCertificateSentTo' %}:</td>
					<td class="text"><a href="mailto:{{ ToEmail|safe }}">{{ ToName|safe }}</a></td>
				</tr>
				<tr>
					<td width="150" class="text">{% lang 'GiftCertificateSentFrom' %}:</td>
					<td class="text"><a href="mailto:{{ FromEmail|safe }}">{{ FromName|safe }}</a></td>
				</tr>
				<tr>
					<td width="150" class="text">{% lang 'GiftCertificateMessage' %}:</td>
					<td class="text">{{ Message|safe }}</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<br />
<h5 style="margin: 0pt 0pt 5pt 0pt">{% lang 'GiftCertificateHistory' %}</h5>
{{ GiftCertificateHistory|safe }}