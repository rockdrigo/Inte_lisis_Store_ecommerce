<table>
	<tr>
		<td class="FieldLabel">
			&nbsp;&nbsp;&nbsp;{% lang 'DownloadDescription' %}:
		</td>
		<td>
			<input type="text" name="downloads[{{ DownloadId|safe }}][downdescription]" id="DownloadDescription{{ DownloadId|safe }}" value="{{ DownloadDescription|safe }}" class="Field200" />
		</td>
	</tr>
	<tr>
		<td class="FieldLabel">
			&nbsp;&nbsp;&nbsp;{% lang 'ExpiresAfter' %}:
		</td>
		<td>
			<input type="text" name="downloads[{{ DownloadId|safe }}][downexpiresafter]" id="DownloadExpiresAfter{{ DownloadId|safe }}" value="{{ ExpiresAfter|safe }}" class="Field40" />
			<select name="downloads[{{ DownloadId|safe }}][downexpiresrange]" id="DownloadExpiresRange{{ DownloadId|safe }}">
				<option value="days">{% lang 'RangeDays' %}</option>
				<option value="weeks" {{ RangWeeksSelected|safe }}>{% lang 'RangeWeeks' %}</option>
				<option value="months" {{ RangeMonthsSelected|safe }}>{% lang 'RangeMonths' %}</option>
				<option value="years" {{ RangeYearsSelected|safe }}>{% lang 'RangeYears' %}</option>
			</select>
			<img onmouseout="HideHelp('dlexpires{{ DownloadId|safe }}');" onmouseover="ShowHelp('dlexpires{{ DownloadId|safe }}', '{% lang 'ExpiresAfter' %}', '{% lang 'ExpiresAfterHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
			<div style="display:none" id="dlexpires{{ DownloadId|safe }}"></div>
		</td>
	</tr>
	<tr>
		<td class="FieldLabel">
			&nbsp;&nbsp;&nbsp;{% lang 'MaximumDownloads' %}:
		</td>
		<td>
			<input type="text" name="downloads[{{ DownloadId|safe }}][downmaxdownloads]" id="DownloadMaxDownloads{{ DownloadId|safe }}" class="Field40" value="{{ MaxDownloads|safe }}" />
			<img onmouseout="HideHelp('dldownloads{{ DownloadId|safe }}');" onmouseover="ShowHelp('dldownloads{{ DownloadId|safe }}', '{% lang 'MaximumDownloads' %}', '{% lang 'MaximumDownloadsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
			<div style="display:none" id="dldownloads{{ DownloadId|safe }}"></div>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="button" value="{% lang 'SaveDownload' %}" onclick="saveDownload();" class="SaveButton SmallButton" style="width: 90px;" />
			<input type="button" value="{% lang 'CancelEdit' %}" onclick="cancelDownloadEdit();" class="CancelButton SmallButton" style="width: 60px" />
		</td>
	</tr>
</table>